<?php
/**
 * FILE PATH: /api/works/generate-sop.php
 *
 * Can be called two ways:
 * 1. Direct require from move-to-work.php (uses existing $pdo, $workSysId, $userName)
 * 2. Standalone HTTP POST { work_sys_id, generated_by }
 */

function _generateSop(PDO $pdo, string $workSysId, string $userName): void {
    require_once __DIR__ . '/../../server/ai-gemini.php';
    require_once __DIR__ . '/../../server/sys_id_generator_v2.php';
    require_once __DIR__ . '/../../server/generate_meta_data.php';

    error_log("[generate-sop] START work={$workSysId} user={$userName}");

    $ws = $pdo->prepare("SELECT client_info, segment_data, service_type FROM works WHERE sys_id = ? LIMIT 1");
    $ws->execute([$workSysId]);
    $work = $ws->fetch(PDO::FETCH_ASSOC);
    if (!$work) {
        error_log("[generate-sop] ABORT — work not found: {$workSysId}");
        return;
    }

    $ci       = json_decode($work['client_info'],  true) ?? [];
    $segData  = json_decode($work['segment_data'], true) ?? [];
    $svcTypes = json_decode($work['service_type'], true) ?? [];
    $services = is_array($svcTypes) ? $svcTypes : [$svcTypes];
    $clientName = $ci['name'] ?? 'Unknown';
    $common     = $segData['common'] ?? [];

    error_log("[generate-sop] services=" . implode(',', $services) . " client={$clientName}");

    $system = "You are an expert travel operations manager.
Generate a concise, step-by-step workflow/SOP for processing this travel service.
Write in plain text — numbered steps, clear and actionable.
Keep it under 300 words. Focus on what the operations team needs to DO.
No markdown, no headers — just numbered steps.";

    $noteCount  = 0;
    $gdsWritten = false;

    foreach ($services as $slug) {
        $svcData = $segData[$slug] ?? [];
        if (empty($svcData)) {
            error_log("[generate-sop] SKIP {$slug} — no segment data");
            continue;
        }

        // common (pax, budget) merge করো
        $svcData = array_merge($common, $svcData);

        $svcLabel = ucwords(str_replace('_', ' ', $slug));
        $segs     = $svcData['segments'] ?? [$svcData];

        $segText = '';
        foreach ($segs as $i => $seg) {
            $parts = array_filter([
                isset($seg['from'])          ? "From: {$seg['from']}"            : '',
                isset($seg['to'])            ? "To: {$seg['to']}"                : '',
                isset($seg['from_city'])     ? "From: {$seg['from_city']}"       : '',
                isset($seg['to_city'])       ? "To: {$seg['to_city']}"           : '',
                isset($seg['departure_date'])? "Date: {$seg['departure_date']}"  : '',
                isset($seg['date'])          ? "Date: {$seg['date']}"            : '',
                isset($seg['travel_date'])   ? "Date: {$seg['travel_date']}"     : '',
                isset($seg['hotel_name'])    ? "Hotel: {$seg['hotel_name']}"     : '',
                isset($seg['city_name'])     ? "City: {$seg['city_name']}"       : '',
                isset($seg['nights'])        ? "Nights: {$seg['nights']}"        : '',
                isset($seg['country_name'])  ? "Country: {$seg['country_name']}" : '',
                isset($seg['airline'])       ? "Airline: {$seg['airline']}"      : '',
                isset($seg['class'])         ? "Class: {$seg['class']}"          : '',
            ]);
            if ($parts) $segText .= "Segment " . ($i + 1) . ": " . implode(', ', $parts) . "\n";
        }

        $paxAdult  = (int)($svcData['pax_adult']  ?? 1);
        $paxChild  = (int)($svcData['pax_child']  ?? 0);
        $paxInfant = (int)($svcData['pax_infant'] ?? 0);
        $paxStr    = "ADT:{$paxAdult} CHD:{$paxChild} INF:{$paxInfant}";

        $userPrompt = "Service: {$svcLabel}
Client: {$clientName}
Work ID: {$workSysId}
Pax: {$paxStr}"
. (!empty($svcData['budget'])  ? "\nBudget: {$svcData['budget']}" : '')
. ($segText                    ? "\nItinerary:\n{$segText}"        : '')
. "\nGenerate the operations workflow/SOP for processing this {$svcLabel} booking.";

        $result = geminiCall($system, $userPrompt, 800, 0.3);
        if (!($result['success'] ?? false)) {
            error_log("[generate-sop] SKIP {$slug} SOP — Gemini failed: " . ($result['error'] ?? 'unknown'));
            continue;
        }

        $sopText = trim($result['text'] ?? '');
        if (!$sopText) {
            error_log("[generate-sop] SKIP {$slug} SOP — empty response");
            continue;
        }

        $ids  = generateV2IDs($pdo, 'task_notes');
        $meta = buildMetaData(null, $userName);

        $pdo->prepare("
            INSERT INTO task_notes
                (uuid, sys_id, task_sys_id, work_sys_id, board_type, service_slug, board_name,
                 note_type, content, sort_order, created_by, meta_data)
            VALUES (?, ?, NULL, ?, 'work', ?, 'mindboard', 'text', ?, 1, ?, ?)
        ")->execute([
            $ids['uuid'], $ids['sys_id'],
            $workSysId, $slug,
            "🤖 AI Generated SOP:\n\n" . $sopText,
            $userName, $meta,
        ]);
        $noteCount++;
        error_log("[generate-sop] SOP note saved for {$slug}");

        // ── GDS Commands (air_ticket only) ────────────────────────────────
        if ($slug === 'air_ticket') {
            $commands = _buildAllGdsCommands($pdo, $svcData, $workSysId, $userName);

            $cmdJson = json_encode($commands, JSON_UNESCAPED_UNICODE);

            $atCheck = $pdo->prepare("SELECT id FROM air_tickets WHERE work_sys_id = ? LIMIT 1");
            $atCheck->execute([$workSysId]);

            if ($atCheck->fetchColumn()) {
                $pdo->prepare("UPDATE air_tickets SET commands = ? WHERE work_sys_id = ?")
                    ->execute([$cmdJson, $workSysId]);
                error_log("[generate-sop] GDS commands updated in existing air_tickets row");
            } else {
                $atIds  = generateV2IDs($pdo, 'air_tickets');
                $atMeta = buildMetaData(null, $userName);
                $pdo->prepare("
                    INSERT INTO air_tickets (uuid, sys_id, work_sys_id, commands, meta_data)
                    VALUES (?, ?, ?, ?, ?)
                ")->execute([$atIds['uuid'], $atIds['sys_id'], $workSysId, $cmdJson, $atMeta]);
                error_log("[generate-sop] GDS commands saved in new air_tickets row");
            }
            $gdsWritten = true;
        }
    }

    error_log("[generate-sop] DONE work={$workSysId} notes={$noteCount} gds=" . ($gdsWritten ? 'yes' : 'no'));
}

// ════════════════════════════════════════════════════════════════════════════
// GDS COMMAND BUILDER — সব 4 stage এক জায়গায়
// ════════════════════════════════════════════════════════════════════════════

function _buildAllGdsCommands(PDO $pdo, array $svcData, string $workSysId, string $userName): array {
    $__gdsStage1 =_gdsStage1_AI($svcData);
    $__gdsStage2 =_gdsStage1_AI($svcData);

    // var_dump($__gdsStage1, $__gdsStage2);
    // die;

    return [
        'mindboard'    => $__gdsStage1,         // Research     — AI
        'quotation'    => $__gdsStage2,         // Checklist    — AI
        'booking'      => _gdsStage3_Skeleton($svcData),   // Booking      — Skeleton (traveler দরকার)
        'confirmation' => _gdsStage4_Skeleton($svcData),   // Documents    — Skeleton (passport দরকার)
    ];

}

// ── Stage 1: Research — Gemini দিয়ে GDS commands ────────────────────────────
function _gdsStage1_AI(array $svcData): array {
    $prompt = _buildGdsPrompt($svcData, 1);
    $result = geminiJSON(
        _gdsSystemPrompt(),
        $prompt,
        1500
    );

    if (!($result['success'] ?? false) || empty($result['data'])) {
        error_log("[generate-sop] Stage 1 AI failed: " . ($result['error'] ?? 'empty'));
        return _gdsStage1_Fallback($svcData);
    }

    $cmds = _normalizeGdsCmds($result['data']);
    error_log("[generate-sop] Stage 1 AI OK — " . count($cmds) . " items");
    return $cmds;
}

// ── Stage 2: Quotation Checklist — Gemini দিয়ে ──────────────────────────────
function _gdsStage2_AI(array $svcData): array {
    $prompt = _buildGdsPrompt($svcData, 2);
    $result = geminiJSON(
        _gdsSystemPrompt(),
        $prompt,
        1200
    );

    if (!($result['success'] ?? false) || empty($result['data'])) {
        error_log("[generate-sop] Stage 2 AI failed: " . ($result['error'] ?? 'empty'));
        return _gdsStage2_Fallback($svcData);
    }

    $cmds = _normalizeGdsCmds($result['data']);
    error_log("[generate-sop] Stage 2 AI OK — " . count($cmds) . " items");
    return $cmds;
}

// ── Stage 3: Booking — Skeleton, traveler entry এর পরে Regenerate করতে হবে ──
function _gdsStage3_Skeleton(array $svcData): array {
    $paxA  = (int)($svcData['pax_adult']  ?? 1);
    $paxC  = (int)($svcData['pax_child']  ?? 0);
    $paxI  = (int)($svcData['pax_infant'] ?? 0);
    $seats = $paxA + $paxC;
    $segs  = $svcData['segments'] ?? [];

    $d = fn($label)         => ['divider' => true,  'label' => $label];
    $c = fn($cmd, $note='') => ['cmd'     => $cmd,  'note'  => $note];
    $n = fn($note)          => ['note_only' => true, 'note'  => $note];

    $cmds = [];
    $cmds[] = $n('⚠️ Traveler entry দেওয়ার পরে "Regenerate" করুন — তাহলে real name দিয়ে commands আসবে');

    $cmds[] = $d('Sell itinerary');
    foreach ($segs as $i => $seg) {
        $from = strtoupper($seg['from'] ?? $seg['from_city'] ?? '');
        $to   = strtoupper($seg['to']   ?? $seg['to_city']   ?? '');
        $dateRaw = $seg['departure_date'] ?? $seg['date'] ?? '';
        $dateGDS = _formatDateGDS($dateRaw);
        $carr    = $seg['airline'] ?? '';
        if ($from && $to && $dateGDS)
            $cmds[] = $c("A{$dateGDS}{$from}{$to}" . ($carr ? "*{$carr}" : ''), "Seg " . ($i + 1) . " — line select করুন");
    }
    $cmds[] = $c("N{$seats}M1", "{$seats} seat sell");

    $cmds[] = $d('Name entries — Traveler entry দেওয়ার পরে Regenerate করুন');
    for ($i = 1; $i <= $paxA; $i++)
        $cmds[] = $c("N.SURNAME/FIRSTNAME MR", "Adult {$i} — placeholder");
    for ($i = 1; $i <= $paxC; $i++)
        $cmds[] = $c("N.SURNAME/FIRSTNAME MSTR*P-C0X", "Child {$i} — age যোগ করুন");
    for ($i = 1; $i <= $paxI; $i++)
        $cmds[] = $c("N.I/SURNAME/FIRSTNAME MISS*DDMMMYY", "Infant {$i} — DOB যোগ করুন");

    $cmds[] = $d('Save');
    $cmds[] = $c('P.T*TRAVHUB', 'Agency phone');
    $cmds[] = $c('T.T*',        'Time limit');
    $cmds[] = $c('ER',          'End record → PNR');

    $cmds[] = $d('Price');
    $cmds[] = $c('FQC/ET',  'Carrier দিয়ে price করুন');
    $cmds[] = $c('FQL',     'Fare breakdown — client কে confirm করুন');
    $cmds[] = $c('ER',      'Save priced itinerary');

    return $cmds;
}

// ── Stage 4: Confirmation/Documents — Skeleton ───────────────────────────────
function _gdsStage4_Skeleton(array $svcData): array {
    $paxA  = (int)($svcData['pax_adult']  ?? 1);
    $paxC  = (int)($svcData['pax_child']  ?? 0);
    $paxI  = (int)($svcData['pax_infant'] ?? 0);

    $d = fn($label)         => ['divider'   => true, 'label' => $label];
    $c = fn($cmd, $note='') => ['cmd'       => $cmd, 'note'  => $note];
    $n = fn($note)          => ['note_only' => true, 'note'  => $note];

    $cmds = [];
    $cmds[] = $n('⚠️ Traveler passport info দেওয়ার পরে "Regenerate" করুন — real passport commands আসবে');

    $cmds[] = $d('Passport (DOCS) — একজন per pax');
    for ($i = 1; $i <= $paxA; $i++)
        $cmds[] = $c(
            "SI.P{$i}/SSRDOCSYYHK1/P/BD/PASSPORTNO/BD/DOB/M-F/EXPIRY/SURNAME/GIVEN",
            "Adult {$i} — placeholder"
        );
    for ($i = 1; $i <= $paxC; $i++)
        $cmds[] = $c(
            "SI.P" . ($paxA + $i) . "/SSRDOCSYYHK1/P/BD/PASSPORTNO/BD/DOB/M-F/EXPIRY/SURNAME/GIVEN",
            "Child {$i} — placeholder"
        );
    for ($i = 1; $i <= $paxI; $i++)
        $cmds[] = $c(
            "SI.P" . ($paxA + $paxC + $i) . "/SSRDOCSYYHK1/P/BD/PASSPORTNO/BD/DOB/FI/EXPIRY/SURNAME/GIVEN",
            "Infant {$i} — gender FI"
        );

    $cmds[] = $d('Contact');
    $cmds[] = $c('SI.SSRPCTCYYHK1/CTC NAME/NUMBER', 'Emergency contact');

    $cmds[] = $d('Meals');
    for ($i = 1; $i <= $paxA; $i++)
        $cmds[] = $c("SI.P{$i}/MOML", "Muslim meal — Adult {$i}");
    for ($i = 1; $i <= $paxC; $i++)
        $cmds[] = $c("SI.P" . ($paxA + $i) . "/CHML", "Child meal — Child {$i}");
    if ($paxI)
        $cmds[] = $c('SI.P{mother}/BSCT*INFANT', 'Bassinet — mother এর pax number দিন');

    $cmds[] = $d('Save & Verify');
    $cmds[] = $c('ER',   'End record');
    $cmds[] = $c('*SI',  'সব line HK status এ আছে কিনা check করুন');

    return $cmds;
}

// ════════════════════════════════════════════════════════════════════════════
// PROMPT BUILDERS
// ════════════════════════════════════════════════════════════════════════════

function _gdsSystemPrompt(): string {
    return <<<PROMPT
You are a senior Galileo GDS expert working for a travel agency in Bangladesh.
Your job is to generate accurate GDS command arrays for travel bookings.

IMPORTANT RULES:
- Return ONLY a valid JSON array — no explanation, no markdown, no backticks
- Each item is either a COMMAND object or a DIVIDER object:
  Command:  {"cmd": "A14AUGDACBKK*BG", "note": "DAC→BKK availability"}
  Divider:  {"divider": true, "label": "Live Availability"}
- Use real IATA airport codes (3-letter) and airline codes (2-letter)
- If airline name is given (e.g. "Biman Bangladesh"), convert to IATA code (BG)
- For unknown airlines, use the most likely IATA code based on the name
- Date format in GDS: DDMMM (e.g. 14AUG, 03JAN)
- Galileo GDS syntax — not Amadeus or Sabre
PROMPT;
}

function _buildGdsPrompt(array $svcData, int $stage): string {
    $segs        = $svcData['segments'] ?? [];
    $segmentType = $svcData['segment_type'] ?? (count($segs) > 1 ? 'multi_city' : 'one_way');
    $paxA        = (int)($svcData['pax_adult']  ?? 1);
    $paxC        = (int)($svcData['pax_child']  ?? 0);
    $paxI        = (int)($svcData['pax_infant'] ?? 0);
    $seats       = $paxA + $paxC;
    $budget      = $svcData['budget'] ?? '';

    // Segment list build করো
    $segLines = [];
    foreach ($segs as $i => $seg) {
        [$from, $to] = _parseSegFromTo($seg);

        $date    = $seg['departure_date']  ?? $seg['date'] ?? '';
        $airline = $seg['airline'] ?? '';
        $class   = $seg['class']   ?? 'Economy';
        $flex    = $seg['date_flexibility'] ?? '';

        $line = "  Segment " . ($i + 1) . ": {$from} → {$to}";
        if ($date)    $line .= " | Date: {$date}";
        if ($airline) $line .= " | Airline: {$airline}";
        if ($class)   $line .= " | Class: {$class}";
        if ($flex)    $line .= " | Flexibility: {$flex}";
        $segLines[] = $line;
    }

    $paxLine = "Pax: {$paxA} Adult" . ($paxC ? ", {$paxC} Child" : '') . ($paxI ? ", {$paxI} Infant" : '');
    $seatsLine = "Seats to sell: {$seats}";
    $segLinesStr = implode("\n", $segLines);

    if ($stage === 1) {
        return <<<PROMPT
Generate Galileo GDS commands for Stage 1: RESEARCH (Mind Board tab).

Trip type: {$segmentType}
{$paxLine}
{$seatsLine}
Itinerary:
{$segLinesStr}

Generate these 4 sections (use dividers):
1. "Timetable" — TT commands to check schedules
2. "Live Availability" — A commands
   - One way: ADateFromTo*CarrierCode
   - Round trip: ADateFromTo*CarrierCode++DateFromTo*CarrierCode (one combined command)
   - Multi-city: chain with ++ for each leg
3. "Priced Shopping" — FS commands with pax string
4. "Fare & Conditions" — FQPQ, FQL, FN*1/ALL

Include useful notes for each command. Return JSON array only.
PROMPT;
    }

    if ($stage === 2) {
        return <<<PROMPT
Generate Galileo GDS commands for Stage 2: QUOTATION CHECKLIST.

Trip type: {$segmentType}
{$paxLine}
Itinerary:
{$segLinesStr}
Budget: {$budget}

Generate a practical checklist that the agent should verify before quoting:
- Passenger document requirements (passport validity, visa needs per destination)
- Pax count confirmation
- Fare validity warning
- Budget alignment
- Per-segment confirmation with date flexibility if any
- Any special notes for the specific route/airline

Use dividers to group sections. Return JSON array only.
PROMPT;
    }

    return '[]';
}

// ════════════════════════════════════════════════════════════════════════════
// FALLBACK — AI fail হলে basic algorithmic commands
// ════════════════════════════════════════════════════════════════════════════

function _gdsStage1_Fallback(array $svcData): array {
    $segs  = $svcData['segments'] ?? [];
    $paxA  = (int)($svcData['pax_adult']  ?? 1);
    $paxC  = (int)($svcData['pax_child']  ?? 0);
    $paxI  = (int)($svcData['pax_infant'] ?? 0);
    $seats = $paxA + $paxC;
    $paxStr = '*P1' . ($paxA > 1 ? "-{$paxA}" : '') . ($paxC ? "*C0{$paxC}" : '') . ($paxI ? '*INF' : '');

    $d = fn($l)          => ['divider' => true, 'label' => $l];
    $c = fn($cmd, $note='') => ['cmd' => $cmd, 'note' => $note];
    $cmds = [];

    $cmds[] = $d('Timetable');
    foreach ($segs as $seg) {
        [$from, $to] = _parseSegFromTo($seg);
        $dt = _formatDateGDS($seg['departure_date'] ?? $seg['date'] ?? '');
        if ($from && $to && $dt) $cmds[] = $c("TT{$dt}{$from}{$to}", "{$from}→{$to}");
    }

    $cmds[] = $d('Live Availability');
    // Round trip → ++ chain
    if (count($segs) === 2) {
        [$f1, $t1] = _parseSegFromTo($segs[0]); $d1 = _formatDateGDS($segs[0]['departure_date'] ?? '');
        [$f2, $t2] = _parseSegFromTo($segs[1]); $d2 = _formatDateGDS($segs[1]['departure_date'] ?? '');
        if ($f1 && $t1 && $d1 && $f2 && $t2 && $d2)
            $cmds[] = $c("A{$d1}{$f1}{$t1}++{$d2}{$f2}{$t2}", "Round trip availability");
    } else {
        foreach ($segs as $seg) {
            [$from, $to] = _parseSegFromTo($seg);
            $dt = _formatDateGDS($seg['departure_date'] ?? $seg['date'] ?? '');
            if ($from && $to && $dt) $cmds[] = $c("A{$dt}{$from}{$to}", "{$from}→{$to}");
        }
    }

    $cmds[] = $d('Priced Shopping');
    foreach ($segs as $seg) {
        [$from, $to] = _parseSegFromTo($seg);
        $dt = _formatDateGDS($seg['departure_date'] ?? $seg['date'] ?? '');
        if ($from && $to && $dt) $cmds[] = $c("FS{$seats}{$from}{$dt}{$to}{$paxStr}", "{$seats} seats {$from}→{$to}");
    }

    $cmds[] = $d('Fare & Conditions');
    [$allFrom] = _parseSegFromTo($segs[0]);
    $allVia = implode('', array_map(fn($s) => _parseSegFromTo($s)[1], $segs));
    if ($allFrom && $allVia) $cmds[] = $c("FQPQ{$allFrom}{$allVia}", 'Through fare');
    $cmds[] = $c('FQL',      'Fare + taxes');
    $cmds[] = $c('FN*1/ALL', 'Baggage · Refund · Changes');

    return $cmds;
}

function _gdsStage2_Fallback(array $svcData): array {
    $segs  = $svcData['segments'] ?? [];
    $paxA  = (int)($svcData['pax_adult']  ?? 1);
    $paxC  = (int)($svcData['pax_child']  ?? 0);
    $paxI  = (int)($svcData['pax_infant'] ?? 0);
    $budget = $svcData['budget'] ?? '';

    $d = fn($l)          => ['divider' => true, 'label' => $l];
    $c = fn($cmd, $note='') => ['cmd' => $cmd, 'note' => $note];
    $cmds = [];

    $cmds[] = $d('Passenger checklist');
    $cmds[] = $c("PAX: {$paxA}A / {$paxC}C / {$paxI}I", 'Client এর সাথে confirm করুন');
    $cmds[] = $c('PASSPORT',      'Minimum 6 months validity past last travel date');
    $cmds[] = $c('VISA',          'Destination visa requirement check করুন');
    $cmds[] = $c('FARE VALIDITY', '48 ঘণ্টা — client কে জানান');
    if ($budget) $cmds[] = $c("BUDGET: {$budget}", 'Client এর সাথে align করুন');

    $cmds[] = $d('Segment confirmation');
    foreach ($segs as $i => $seg) {
        [$from, $to] = _parseSegFromTo($seg);
        $dt   = _formatDateGDS($seg['departure_date'] ?? $seg['date'] ?? '');
        $flex = $seg['date_flexibility'] ?? '';
        $cmds[] = $c(
            "SEG " . ($i + 1) . ": {$from}→{$to}" . ($dt ? " {$dt}" : ''),
            $flex ? "Flexibility: {$flex}" : 'Fixed date'
        );
    }

    return $cmds;
}

// ════════════════════════════════════════════════════════════════════════════
// HELPERS
// ════════════════════════════════════════════════════════════════════════════


// segment থেকে from/to parse — route field fallback সহ
function _parseSegFromTo(array $seg): array {
    $from = strtoupper(trim($seg['from'] ?? $seg['from_city'] ?? ''));
    $to   = strtoupper(trim($seg['to']   ?? $seg['to_city']   ?? ''));
    if ((!$from || !$to) && !empty($seg['route'])) {
        $pts  = preg_split('/\s*[=\-→]+\s*/', $seg['route']);
        $from = strtoupper(trim($pts[0] ?? ''));
        $to   = strtoupper(trim($pts[1] ?? ''));
    }
    return [$from, $to];
}

function _formatDateGDS(string $dateRaw): string {
    if (preg_match('/(\d{4})-(\d{2})-(\d{2})/', $dateRaw, $m)) {
        static $months = ['','JAN','FEB','MAR','APR','MAY','JUN','JUL','AUG','SEP','OCT','NOV','DEC'];
        return $m[3] . ($months[(int)$m[2]] ?? '');
    }
    return '';
}

// AI response normalize — {cmd, note} বা {divider, label} format নিশ্চিত করো
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

// heredoc এ array implode করার helper (closure scope issue এড়াতে)
function this_implode(array $arr, string $sep = "\n"): string {
    return implode($sep, $arr);
}

// ── Standalone HTTP mode ─────────────────────────────────────────────────────
if (basename($_SERVER['SCRIPT_FILENAME'] ?? '') === 'generate-sop.php') {
    ob_start();
    session_start();
    date_default_timezone_set('Asia/Dhaka');
    ini_set('display_errors', 0);
    header('Content-Type: application/json');

    require_once '../../server/api_bootstrap.php';
    require_once '../../server/db_connection.php';

    $raw  = file_get_contents('php://input');
    $body = json_decode($raw, true) ?? [];

    $workSysId = trim($body['work_sys_id']  ?? '');
    $userName  = $body['generated_by']      ?? ($_SESSION['user_name'] ?? 'system');

    if (!$workSysId) {
        ob_clean();
        echo json_encode(['status' => 'error', 'message' => 'work_sys_id required']);
        exit;
    }

    try {
        _generateSop($pdo, $workSysId, $userName);
        ob_clean();
        echo json_encode(['status' => 'success']);
    } catch (Throwable $e) {
        error_log('[generate-sop standalone] ' . $e->getMessage());
        ob_clean();
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
}