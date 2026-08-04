<?php
/**
 * FILE PATH: /api/air-tickets/regenerate-gds.php
 *
 * Traveler entry দেওয়ার পরে Stage 3 + 4 AI দিয়ে regenerate করে।
 * ww-air-ticket.js এর "Regenerate" button এই endpoint call করবে।
 *
 * POST { work_sys_id, stages?: [3,4] }
 * → air_tickets.commands এ Stage 3 + 4 update করে
 * → { status, updated_stages, commands } return করে
 */

ob_start();
session_start();
date_default_timezone_set('Asia/Dhaka');
ini_set('display_errors', 0);
header('Content-Type: application/json');

require_once '../../server/api_bootstrap.php';
require_once '../../server/db_connection.php';
require_once '../../server/ai-gemini.php';

$body      = json_decode(file_get_contents('php://input'), true) ?? [];
$workSysId = trim($body['work_sys_id'] ?? '');
$stages    = $body['stages'] ?? [3, 4];  // default: দুটোই regenerate
$userName  = $_SESSION['user_name'] ?? 'system';

if (!$workSysId) {
    ob_clean();
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'work_sys_id required']);
    exit;
}

try {
    // ── 1. Work data fetch ───────────────────────────────────────────────────
    $wStmt = $pdo->prepare("
        SELECT segment_data, service_type, traveler_sys_ids, client_info
        FROM works WHERE sys_id = ? LIMIT 1
    ");
    $wStmt->execute([$workSysId]);
    $work = $wStmt->fetch(PDO::FETCH_ASSOC);
    if (!$work) throw new Exception('Work not found');

    $segData     = json_decode($work['segment_data'],    true) ?? [];
    $svcTypes    = json_decode($work['service_type'],    true) ?? [];
    $travelerRefs = json_decode($work['traveler_sys_ids'] ?? '[]', true) ?? [];
    $ci          = json_decode($work['client_info'],    true) ?? [];
    $clientName  = $ci['name'] ?? 'Unknown';

    $services = is_array($svcTypes) ? $svcTypes : [$svcTypes];
    if (!in_array('air_ticket', $services)) {
        throw new Exception('This work does not have an air_ticket service');
    }

    $common  = $segData['common']     ?? [];
    $svcData = array_merge($common, $segData['air_ticket'] ?? []);

    // ── 2. Travelers fetch — live from travelers table ───────────────────────
    $travelers = [];
    if (!empty($travelerRefs)) {
        $sysIds       = array_column($travelerRefs, 'traveler_sys_id');
        $placeholders = implode(',', array_fill(0, count($sysIds), '?'));
        $tStmt        = $pdo->prepare("
            SELECT sys_id, name, type, passport_info, personal_info
            FROM travelers WHERE sys_id IN ({$placeholders})
        ");
        $tStmt->execute($sysIds);
        $rows = $tStmt->fetchAll(PDO::FETCH_ASSOC);

        // Link order maintain করো (travelerRefs এর order = pax order)
        foreach ($travelerRefs as $ref) {
            foreach ($rows as $row) {
                if ($row['sys_id'] === $ref['traveler_sys_id']) {
                    $travelers[] = _parseTraveler($row, $ref);
                    break;
                }
            }
        }
    }

    // ── 3. Existing commands fetch ───────────────────────────────────────────
    $atStmt = $pdo->prepare("SELECT commands FROM air_tickets WHERE work_sys_id = ? LIMIT 1");
    $atStmt->execute([$workSysId]);
    $atRow    = $atStmt->fetch(PDO::FETCH_ASSOC);
    $commands = json_decode($atRow['commands'] ?? '{}', true) ?? [];

    // ── 4. Requested stages regenerate করো ──────────────────────────────────
    $updatedStages = [];

    if (in_array(3, $stages)) {
        $commands['booking'] = _regenerateStage3($svcData, $travelers);
        $updatedStages[]     = 3;
        error_log("[regenerate-gds] Stage 3 regenerated for work={$workSysId} travelers=" . count($travelers));
    }

    if (in_array(4, $stages)) {
        $commands['confirmation'] = _regenerateStage4($svcData, $travelers);
        $updatedStages[]          = 4;
        error_log("[regenerate-gds] Stage 4 regenerated for work={$workSysId} travelers=" . count($travelers));
    }

    // ── 5. Save back to air_tickets ──────────────────────────────────────────
    $cmdJson = json_encode($commands, JSON_UNESCAPED_UNICODE);

    if ($atRow) {
        $pdo->prepare("UPDATE air_tickets SET commands = ? WHERE work_sys_id = ?")
            ->execute([$cmdJson, $workSysId]);
    } else {
        // air_tickets row নেই — নতুন বানাও (edge case)
        require_once '../../server/sys_id_generator_v2.php';
        require_once '../../server/generate_meta_data.php';
        $atIds  = generateV2IDs($pdo, 'air_tickets');
        $atMeta = buildMetaData(null, $userName);
        $pdo->prepare("INSERT INTO air_tickets (uuid, sys_id, work_sys_id, commands, meta_data) VALUES (?,?,?,?,?)")
            ->execute([$atIds['uuid'], $atIds['sys_id'], $workSysId, $cmdJson, $atMeta]);
    }

    ob_clean();
    echo json_encode([
        'status'         => 'success',
        'updated_stages' => $updatedStages,
        'traveler_count' => count($travelers),
        'commands'       => $commands,   // frontend সাথে সাথে update করতে পারবে
    ]);

} catch (Throwable $e) {
    error_log('[regenerate-gds] ERROR: ' . $e->getMessage());
    ob_clean();
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}

// ════════════════════════════════════════════════════════════════════════════
// STAGE 3 — Booking (AI + real traveler names)
// ════════════════════════════════════════════════════════════════════════════
function _regenerateStage3(array $svcData, array $travelers): array {
    if (empty($travelers)) {
        // Traveler নেই — skeleton ই রাখো কিন্তু warning আপডেট করো
        return _stage3Skeleton($svcData, 'still_empty');
    }

    $prompt = _buildStage3Prompt($svcData, $travelers);
    $result = geminiJSON(_gdsSystemPromptRegen(), $prompt, 1500);

    if (!($result['success'] ?? false) || empty($result['data'])) {
        error_log('[regenerate-gds] Stage 3 AI failed: ' . ($result['error'] ?? 'empty') . ' — using name-filled skeleton');
        return _stage3Skeleton($svcData, 'ai_failed', $travelers);
    }

    $cmds = _normalizeGdsCmds($result['data']);
    error_log('[regenerate-gds] Stage 3 AI OK — ' . count($cmds) . ' items');
    return $cmds;
}

// ════════════════════════════════════════════════════════════════════════════
// STAGE 4 — Confirmation/Documents (AI + real passport data)
// ════════════════════════════════════════════════════════════════════════════
function _regenerateStage4(array $svcData, array $travelers): array {
    if (empty($travelers)) {
        return _stage4Skeleton($svcData, 'still_empty');
    }

    // Passport data আছে কিনা check করো
    $hasPasport = array_filter($travelers, fn($t) => !empty($t['passport_no']));
    if (empty($hasPasport)) {
        error_log('[regenerate-gds] Stage 4 — travelers linked but no passport data yet');
        return _stage4Skeleton($svcData, 'no_passport', $travelers);
    }

    $prompt = _buildStage4Prompt($svcData, $travelers);
    $result = geminiJSON(_gdsSystemPromptRegen(), $prompt, 2000);

    if (!($result['success'] ?? false) || empty($result['data'])) {
        error_log('[regenerate-gds] Stage 4 AI failed: ' . ($result['error'] ?? 'empty') . ' — using passport skeleton');
        return _stage4Skeleton($svcData, 'ai_failed', $travelers);
    }

    $cmds = _normalizeGdsCmds($result['data']);
    error_log('[regenerate-gds] Stage 4 AI OK — ' . count($cmds) . ' items');
    return $cmds;
}

// ════════════════════════════════════════════════════════════════════════════
// PROMPT BUILDERS
// ════════════════════════════════════════════════════════════════════════════

function _gdsSystemPromptRegen(): string {
    return <<<PROMPT
You are a senior Galileo GDS expert working for a travel agency in Bangladesh.
Generate accurate Galileo GDS command arrays using real passenger data.

RULES:
- Return ONLY a valid JSON array — no explanation, no markdown, no backticks
- Command: {"cmd": "N.AHMED/RAHMAN MR", "note": "Adult 1 name entry"}
- Divider: {"divider": true, "label": "Name Entries"}
- Use EXACT names as provided — surname/given split where available
- Date format: DDMMM (e.g. 14AUG, 03JAN1990)
- Gender codes: M = male, F = female, FI = female infant
- Galileo syntax only (not Amadeus or Sabre)
PROMPT;
}

function _buildStage3Prompt(array $svcData, array $travelers): string {
    $segs  = $svcData['segments'] ?? [];
    $seats = array_sum(array_map(fn($t) => $t['pax_type'] !== 'infant' ? 1 : 0, $travelers));
    $segmentType = $svcData['segment_type'] ?? (count($segs) > 1 ? 'multi_city' : 'one_way');

    // Segment lines
    $segLines = [];
    foreach ($segs as $i => $seg) {
        $from = strtoupper($seg['from'] ?? '');
        $to   = strtoupper($seg['to']   ?? '');
        $dt   = $seg['departure_date'] ?? $seg['date'] ?? '';
        $carr = $seg['airline'] ?? '';
        $segLines[] = "  Seg " . ($i+1) . ": {$from}→{$to} | Date: {$dt} | Airline: {$carr}";
    }

    // Passenger list
    $paxLines = [];
    foreach ($travelers as $i => $t) {
        $line = "  P" . ($i+1) . ": {$t['full_name']}";
        $line .= " | Type: {$t['pax_type']}";
        $line .= " | Gender: {$t['gender']}";
        if ($t['surname'])    $line .= " | Surname: {$t['surname']}";
        if ($t['given_name']) $line .= " | Given: {$t['given_name']}";
        if ($t['dob'])        $line .= " | DOB: {$t['dob']}";
        $paxLines[] = $line;
    }

    $segStr = implode("\n", $segLines);
    $paxStr = implode("\n", $paxLines);

    return <<<PROMPT
Generate Galileo GDS commands for Stage 3: BOOKING.

Trip type: {$segmentType}
Seats to sell: {$seats}
Segments:
{$segStr}

Passengers:
{$paxStr}

Generate these sections with dividers:
1. "Sell Itinerary" — A command to show availability, then N{$seats}M1 to sell seats
2. "Name Entries" — N. command for each passenger
   - Adult male: N.SURNAME/GIVEN MR
   - Adult female: N.SURNAME/GIVEN MRS (or MS if unmarried)
   - Child: N.SURNAME/GIVEN MSTR*P-C0{age} for male, MISS*P-C0{age} for female
   - Infant: N.I/SURNAME/GIVEN MSTR*DDMMMYY or MISS*DDMMMYY (DOB)
3. "Save" — P.T*TRAVHUB, T.T*, ER
4. "Price" — FQC/ET, FQL, ER

Return JSON array only.
PROMPT;
}

function _buildStage4Prompt(array $svcData, array $travelers): string {
    $paxLines = [];
    foreach ($travelers as $i => $t) {
        $line = "  P" . ($i+1) . ": {$t['full_name']}";
        $line .= " | Type: {$t['pax_type']}";
        $line .= " | Gender: {$t['gender']}";
        if ($t['surname'])         $line .= " | Surname: {$t['surname']}";
        if ($t['given_name'])      $line .= " | Given: {$t['given_name']}";
        if ($t['passport_no'])     $line .= " | Passport: {$t['passport_no']}";
        if ($t['nationality'])     $line .= " | Nationality: {$t['nationality']} (IATA country code)";
        if ($t['dob'])             $line .= " | DOB: {$t['dob']}";
        if ($t['passport_expiry']) $line .= " | Expiry: {$t['passport_expiry']}";
        if ($t['pax_type'] === 'infant' && $t['mother_pax_no'])
            $line .= " | Mother: P{$t['mother_pax_no']}";
        $paxLines[] = $line;
    }

    $paxStr = implode("\n", $paxLines);

    return <<<PROMPT
Generate Galileo GDS commands for Stage 4: CONFIRMATION DOCUMENTS.

Passengers:
{$paxStr}

Generate these sections with dividers:
1. "Passport (DOCS)" — SSRDOCSYYHK1 command for each passenger
   Format: SI.P{n}/SSRDOCSYYHK1/P/{nationality}/{passport_no}/{nationality}/{dob_ddmmmyy}/{gender_code}/{expiry_ddmmmyy}/{surname}/{given}
   - Gender code: M for male, F for female, FI for female infant
   - Dates in DDMMMYY format (e.g. 14AUG90, 31DEC30)
   - Infant uses mother's pax number reference in note
2. "Contact" — SSRPCTC placeholder
3. "Meals" — MOML for Muslim adults, CHML for children, BSCT*INFANT for bassinet (infant → mother pax number)
4. "Save & Verify" — ER, *SI

Return JSON array only.
PROMPT;
}

// ════════════════════════════════════════════════════════════════════════════
// FALLBACK SKELETONS — AI fail হলে বা data না থাকলে
// ════════════════════════════════════════════════════════════════════════════

function _stage3Skeleton(array $svcData, string $reason, array $travelers = []): array {
    $paxA  = (int)($svcData['pax_adult']  ?? 1);
    $paxC  = (int)($svcData['pax_child']  ?? 0);
    $paxI  = (int)($svcData['pax_infant'] ?? 0);
    $seats = $paxA + $paxC;
    $segs  = $svcData['segments'] ?? [];

    $d = fn($l)         => ['divider'   => true,  'label' => $l];
    $c = fn($cmd, $n='') => ['cmd'      => $cmd,  'note'  => $n];
    $w = fn($note)      => ['note_only' => true,  'note'  => $note];

    $cmds = [];

    if ($reason === 'still_empty') {
        $cmds[] = $w('⚠️ এখনো কোনো traveler link করা হয়নি — traveler add করে Regenerate করুন');
    } elseif ($reason === 'ai_failed') {
        $cmds[] = $w('⚠️ AI generate করতে পারেনি — নিচের name গুলো manually edit করুন');
    }

    $cmds[] = $d('Sell Itinerary');
    foreach ($segs as $i => $seg) {
        $from = strtoupper($seg['from'] ?? '');
        $to   = strtoupper($seg['to']   ?? '');
        $dt   = _fmtDateGDS($seg['departure_date'] ?? $seg['date'] ?? '');
        $carr = $seg['airline'] ?? '';
        if ($from && $to && $dt)
            $cmds[] = $c("A{$dt}{$from}{$to}" . ($carr ? "*{$carr}" : ''), "Seg " . ($i+1));
    }
    $cmds[] = $c("N{$seats}M1", "{$seats} seat sell");

    $cmds[] = $d('Name Entries');
    if (!empty($travelers)) {
        foreach ($travelers as $i => $t) {
            $nameCmd = _buildNameCmd($t, $i + 1);
            $cmds[] = $c($nameCmd['cmd'], $nameCmd['note']);
        }
    } else {
        for ($i = 1; $i <= $paxA; $i++)
            $cmds[] = $c("N.SURNAME/FIRSTNAME MR",           "Adult {$i}");
        for ($i = 1; $i <= $paxC; $i++)
            $cmds[] = $c("N.SURNAME/FIRSTNAME MSTR*P-C0X",   "Child {$i}");
        for ($i = 1; $i <= $paxI; $i++)
            $cmds[] = $c("N.I/SURNAME/FIRSTNAME MISS*DDMMMYY","Infant {$i}");
    }

    $cmds[] = $d('Save');
    $cmds[] = $c('P.T*TRAVHUB', 'Agency phone');
    $cmds[] = $c('T.T*',        'Time limit');
    $cmds[] = $c('ER',          'End record → PNR');

    $cmds[] = $d('Price');
    $cmds[] = $c('FQC/ET', 'Carrier দিয়ে price');
    $cmds[] = $c('FQL',    'Fare — client confirm করুন');
    $cmds[] = $c('ER',     'Save priced itinerary');

    return $cmds;
}

function _stage4Skeleton(array $svcData, string $reason, array $travelers = []): array {
    $paxA = (int)($svcData['pax_adult']  ?? 1);
    $paxC = (int)($svcData['pax_child']  ?? 0);
    $paxI = (int)($svcData['pax_infant'] ?? 0);

    $d = fn($l)         => ['divider'   => true, 'label' => $l];
    $c = fn($cmd, $n='') => ['cmd'      => $cmd, 'note'  => $n];
    $w = fn($note)      => ['note_only' => true, 'note'  => $note];

    $cmds = [];

    if ($reason === 'still_empty') {
        $cmds[] = $w('⚠️ এখনো কোনো traveler link করা হয়নি — traveler add করে Regenerate করুন');
    } elseif ($reason === 'no_passport') {
        $cmds[] = $w('⚠️ Traveler linked কিন্তু passport data নেই — passport upload করে Regenerate করুন');
    } elseif ($reason === 'ai_failed') {
        $cmds[] = $w('⚠️ AI generate করতে পারেনি — নিচের command গুলো manually fill করুন');
    }

    $cmds[] = $d('Passport (DOCS)');
    if (!empty($travelers)) {
        foreach ($travelers as $i => $t) {
            $n   = $i + 1;
            $pp  = $t['passport_no']     ? $t['passport_no']     : 'PASSPORTNO';
            $nat = $t['nationality']      ? $t['nationality']      : 'BD';
            $dob = $t['dob']             ? _dateToDDMMMYY($t['dob']) : 'DOB';
            $exp = $t['passport_expiry'] ? _dateToDDMMMYY($t['passport_expiry']) : 'EXPIRY';
            $sur = $t['surname']         ? $t['surname']          : 'SURNAME';
            $giv = $t['given_name']      ? $t['given_name']       : 'GIVEN';
            $gen = ($t['pax_type'] === 'infant' && $t['gender'] === 'F') ? 'FI'
                 : ($t['gender'] === 'F' ? 'F' : 'M');
            $cmds[] = $c(
                "SI.P{$n}/SSRDOCSYYHK1/P/{$nat}/{$pp}/{$nat}/{$dob}/{$gen}/{$exp}/{$sur}/{$giv}",
                $t['full_name'] . " — {$t['pax_type']}"
            );
        }
    } else {
        for ($i = 1; $i <= $paxA; $i++)
            $cmds[] = $c("SI.P{$i}/SSRDOCSYYHK1/P/BD/PASSPORTNO/BD/DOB/M/EXPIRY/SURNAME/GIVEN", "Adult {$i}");
        for ($i = 1; $i <= $paxC; $i++)
            $cmds[] = $c("SI.P" . ($paxA+$i) . "/SSRDOCSYYHK1/P/BD/PASSPORTNO/BD/DOB/M/EXPIRY/SURNAME/GIVEN", "Child {$i}");
        for ($i = 1; $i <= $paxI; $i++)
            $cmds[] = $c("SI.P" . ($paxA+$paxC+$i) . "/SSRDOCSYYHK1/P/BD/PASSPORTNO/BD/DOB/FI/EXPIRY/SURNAME/GIVEN", "Infant {$i}");
    }

    $cmds[] = $d('Contact');
    $cmds[] = $c('SI.SSRPCTCYYHK1/CTC NAME/NUMBER', 'Emergency contact');

    $cmds[] = $d('Meals');
    for ($i = 1; $i <= $paxA; $i++)
        $cmds[] = $c("SI.P{$i}/MOML", "Muslim meal — Adult {$i}");
    for ($i = 1; $i <= $paxC; $i++)
        $cmds[] = $c("SI.P" . ($paxA+$i) . "/CHML", "Child meal — Child {$i}");
    if ($paxI)
        $cmds[] = $c('SI.P{mother}/BSCT*INFANT', 'Bassinet — mother এর pax number দিন');

    $cmds[] = $d('Save & Verify');
    $cmds[] = $c('ER',  'End record');
    $cmds[] = $c('*SI', 'সব line HK status এ আছে কিনা check করুন');

    return $cmds;
}

// ════════════════════════════════════════════════════════════════════════════
// HELPERS
// ════════════════════════════════════════════════════════════════════════════

// travelers table row → clean pax object
function _parseTraveler(array $row, array $ref): array {
    $passport   = json_decode($row['passport_info']  ?? '{}', true) ?? [];
    $personalInfo = json_decode($row['personal_info'] ?? '{}', true) ?? [];

    // passport_info array format হতে পারে [{bio_info:{...}, page_type:'bio'}]
    if (isset($passport[0]['bio_info'])) {
        $passport = $passport[0]['bio_info'];
    }

    // pax_type: travelers.type field থেকে (adult/child/infant)
    $type = strtolower($row['type'] ?? 'adult');
    if (!in_array($type, ['adult', 'child', 'infant'])) $type = 'adult';

    // Gender
    $gender = strtolower($passport['sex'] ?? $passport['gender'] ?? $personalInfo['gender'] ?? 'male');
    $gender = in_array($gender, ['f', 'female']) ? 'F' : 'M';

    // Nationality — prefer IATA country code
    $nat = strtoupper($passport['nationality'] ?? $passport['country_code'] ?? 'BD');
    if (strlen($nat) > 2) $nat = 'BD'; // fallback

    return [
        'full_name'       => $row['name'],
        'surname'         => $passport['surname']       ?? $passport['last_name']    ?? $ref['surname']   ?? '',
        'given_name'      => $passport['given_names']   ?? $passport['first_name']   ?? $ref['given_name'] ?? '',
        'pax_type'        => $type,
        'gender'          => $gender,
        'passport_no'     => $passport['passport_no']   ?? $passport['document_number'] ?? $ref['passport_no'] ?? '',
        'dob'             => $passport['date_of_birth'] ?? $passport['dob']              ?? $ref['dob']          ?? '',
        'passport_expiry' => $passport['expiry_date']   ?? $passport['date_of_expiry']   ?? $ref['passport_expiry'] ?? '',
        'nationality'     => $nat,
        'mother_pax_no'   => null,  // infant mother link — future enhancement
    ];
}

// Name command builder — fallback skeleton এ use হয়
function _buildNameCmd(array $t, int $paxNo): array {
    $sur = strtoupper($t['surname']    ?: 'SURNAME');
    $giv = strtoupper($t['given_name'] ?: 'FIRSTNAME');
    $gen = $t['gender'] === 'F' ? 'MRS' : 'MR';

    if ($t['pax_type'] === 'adult') {
        return ['cmd' => "N.{$sur}/{$giv} {$gen}", 'note' => $t['full_name'] . " — Adult {$paxNo}"];
    }

    if ($t['pax_type'] === 'child') {
        $title = $t['gender'] === 'F' ? 'MISS' : 'MSTR';
        return ['cmd' => "N.{$sur}/{$giv} {$title}*P-C0X", 'note' => $t['full_name'] . " — Child {$paxNo} (age যোগ করুন)"];
    }

    // infant
    $dob = $t['dob'] ? _dateToDDMMMYY($t['dob']) : 'DDMMMYY';
    $title = $t['gender'] === 'F' ? 'MISS' : 'MSTR';
    return ['cmd' => "N.I/{$sur}/{$giv} {$title}*{$dob}", 'note' => $t['full_name'] . " — Infant {$paxNo}"];
}

// 2026-08-14 → 14AUG26
function _dateToDDMMMYY(string $date): string {
    static $months = ['','JAN','FEB','MAR','APR','MAY','JUN','JUL','AUG','SEP','OCT','NOV','DEC'];
    if (preg_match('/(\d{4})-(\d{2})-(\d{2})/', $date, $m)) {
        return $m[3] . ($months[(int)$m[2]] ?? '') . substr($m[1], 2);
    }
    return $date;
}

// 2026-08-14 → 14AUG (GDS availability format, no year)
function _fmtDateGDS(string $date): string {
    static $months = ['','JAN','FEB','MAR','APR','MAY','JUN','JUL','AUG','SEP','OCT','NOV','DEC'];
    if (preg_match('/(\d{4})-(\d{2})-(\d{2})/', $date, $m)) {
        return $m[3] . ($months[(int)$m[2]] ?? '');
    }
    return '';
}

// AI response normalize
function _normalizeGdsCmds(array $raw): array {
    $out = [];
    foreach ($raw as $item) {
        if (!is_array($item)) continue;
        if (!empty($item['divider'])) {
            $out[] = ['divider' => true, 'label' => (string)($item['label'] ?? '')];
        } elseif (isset($item['cmd'])) {
            $out[] = ['cmd' => (string)$item['cmd'], 'note' => (string)($item['note'] ?? '')];
        } elseif (isset($item['note_only'])) {
            $out[] = ['note_only' => true, 'note' => (string)($item['note'] ?? '')];
        }
    }
    return $out;
}