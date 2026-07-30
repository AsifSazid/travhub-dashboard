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

    $ws = $pdo->prepare("SELECT client_info, segment_data, service_type FROM works WHERE sys_id = ? LIMIT 1");
    $ws->execute([$workSysId]);
    $work = $ws->fetch(PDO::FETCH_ASSOC);
    if (!$work) return;

    $ci       = json_decode($work['client_info'],  true) ?? [];
    $segData  = json_decode($work['segment_data'], true) ?? [];
    $svcTypes = json_decode($work['service_type'], true) ?? [];
    $services = is_array($svcTypes) ? $svcTypes : [$svcTypes];
    $clientName = $ci['name'] ?? 'Unknown';

    $system = "You are an expert travel operations manager. 
Generate a concise, step-by-step workflow/SOP for processing this travel service.
Write in plain text — numbered steps, clear and actionable.
Keep it under 300 words. Focus on what the operations team needs to DO.
No markdown, no headers — just numbered steps.";

    foreach ($services as $slug) {
        $svcData = $segData[$slug] ?? [];
        if (empty($svcData)) continue;

        $svcLabel = ucwords(str_replace('_', ' ', $slug));
        $segs = $svcData['segments'] ?? [$svcData];

        $segText = '';
        foreach ($segs as $i => $seg) {
            $parts = array_filter([
                isset($seg['from'])        ? "From: {$seg['from']}"          : '',
                isset($seg['to'])          ? "To: {$seg['to']}"              : '',
                isset($seg['from_city'])   ? "From: {$seg['from_city']}"     : '',
                isset($seg['to_city'])     ? "To: {$seg['to_city']}"         : '',
                isset($seg['date'])        ? "Date: {$seg['date']}"          : '',
                isset($seg['travel_date']) ? "Date: {$seg['travel_date']}"   : '',
                isset($seg['hotel_name'])  ? "Hotel: {$seg['hotel_name']}"   : '',
                isset($seg['city_name'])   ? "City: {$seg['city_name']}"     : '',
                isset($seg['nights'])      ? "Nights: {$seg['nights']}"      : '',
                isset($seg['country_name'])? "Country: {$seg['country_name']}": '',
            ]);
            if ($parts) $segText .= "Segment " . ($i+1) . ": " . implode(', ', $parts) . "\n";
        }

        $pax   = $svcData['pax']         ?? '';
        $cabin = $svcData['cabin_class']  ?? '';

        $userPrompt = "Service: {$svcLabel}
Client: {$clientName}
Work ID: {$workSysId}"
. ($pax   ? "\nPax: {$pax}"       : '')
. ($cabin ? "\nClass: {$cabin}"   : '')
. ($segText ? "\nItinerary:\n{$segText}" : '')
. "\nGenerate the operations workflow/SOP for processing this {$svcLabel} booking.";

        $result = geminiCall($system, $userPrompt, 800, 0.3);
        if (!($result['success'] ?? false)) continue;

        $sopText = trim($result['text'] ?? '');
        if (!$sopText) continue;

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

        // ── GDS Commands (air_ticket only) — store in air_tickets.commands ──
        if ($slug === 'air_ticket') {
            $commands = [
                'mindboard'    => _buildGdsStage($svcData, '1'),
                'quotation'    => _buildGdsStage($svcData, '2'),
                'booking'      => _buildGdsStage($svcData, '3'),
                'confirmation' => _buildGdsStage($svcData, '4'),
            ];
            // Update or create air_tickets row
            $atCheck = $pdo->prepare("SELECT id FROM air_tickets WHERE work_sys_id = ? LIMIT 1");
            $atCheck->execute([$workSysId]);
            if ($atCheck->fetchColumn()) {
                $pdo->prepare("UPDATE air_tickets SET commands = ? WHERE work_sys_id = ?")
                    ->execute([json_encode($commands, JSON_UNESCAPED_UNICODE), $workSysId]);
            }
            // If no air_tickets row yet, it will be created when user opens Air Ticket tab (init action)
            // Store commands in a temp note so it's not lost
        }
    }
}

function _buildGdsStage(array $svcData, string $stage): array {
    $segs  = $svcData['segments'] ?? [];
    $paxA  = (int)($svcData['pax_adult']  ?? $svcData['pax'] ?? 1);
    $paxC  = (int)($svcData['pax_child']  ?? 0);
    $paxI  = (int)($svcData['pax_infant'] ?? 0);
    $seats = $paxA + $paxC;
    $paxStr = '*P1' . ($paxA > 1 ? "-{$paxA}" : '') . ($paxC ? "*C0{$paxC}" : '') . ($paxI ? '*INF' : '');

    // Helper: parse segment
    $parseSeg = function(array $seg) {
        $from = strtoupper(trim($seg['from'] ?? $seg['from_city'] ?? ''));
        $to   = strtoupper(trim($seg['to']   ?? $seg['to_city']  ?? ''));
        if ((!$from || !$to) && !empty($seg['route'])) {
            $pts = preg_split('/\s*[=\-→]+\s*/', $seg['route']);
            $from = strtoupper(trim($pts[0] ?? ''));
            $to   = strtoupper(trim($pts[1] ?? ''));
        }
        $dateRaw = $seg['departure_date'] ?? $seg['travel_date'] ?? $seg['date'] ?? '';
        $dateGDS = '';
        if (preg_match('/(\d{4})-(\d{2})-(\d{2})/', $dateRaw, $m)) {
            $months = ['','JAN','FEB','MAR','APR','MAY','JUN','JUL','AUG','SEP','OCT','NOV','DEC'];
            $dateGDS = $m[3] . ($months[(int)$m[2]] ?? '');
        }
        $carr = strtoupper($seg['airline'] ?? '');
        return [$from, $to, $dateGDS, $carr];
    };

    // cmd helper: {cmd, note, divider?}
    $d = fn($label) => ['divider' => true, 'label' => $label];
    $c = fn($cmd, $note='') => ['cmd' => $cmd, 'note' => $note];

    $cmds = [];

    if ($stage === '1') {
        // Research
        $cmds[] = $d('Schedules');
        foreach ($segs as $seg) {
            [$from, $to, $dateGDS, $carr] = $parseSeg($seg);
            if ($from && $to && $dateGDS)
                $cmds[] = $c("TT{$dateGDS}{$from}{$to}" . ($carr?"/{$carr}":''), "{$from}→{$to} timetable");
        }
        $cmds[] = $d('Live availability');
        foreach ($segs as $seg) {
            [$from, $to, $dateGDS, $carr] = $parseSeg($seg);
            if ($from && $to && $dateGDS)
                $cmds[] = $c("A{$dateGDS}{$from}{$to}" . ($carr?"*{$carr}":''), "{$from}→{$to}");
        }
        $cmds[] = $d('Priced shopping');
        foreach ($segs as $seg) {
            [$from, $to, $dateGDS, $carr] = $parseSeg($seg);
            if ($from && $to && $dateGDS)
                $cmds[] = $c("FS{$seats}{$from}{$dateGDS}{$to}" . ($carr?"/{$carr}":'') . $paxStr, "{$seats} seats {$from}→{$to}");
        }
        $cmds[] = $d('Fare conditions');
        $allFrom = $parseSeg($segs[0] ?? [])[0] ?? '';
        $allVia  = implode('', array_map(fn($s) => $parseSeg($s)[1], $segs));
        if ($allFrom && $allVia) $cmds[] = $c("FQPQ{$allFrom}{$allVia}", 'Through fare');
        $cmds[] = $c('FQL', 'Fare + tax');
        $cmds[] = $c('FN*1/ALL', 'Baggage · Changes · Refund');

    } elseif ($stage === '2') {
        // Quotation — checklist items
        $cmds[] = $d('Client document checklist');
        $cmds[] = $c('PASSPORT', 'Valid min 6 months past travel date');
        $cmds[] = $c('VISA', 'Destination visa if required');
        $cmds[] = $c('CONFIRM PAX', "ADT:{$paxA} CHD:{$paxC} INF:{$paxI}");
        $cmds[] = $c('FARE VALIDITY', '48 hrs — advise client');
        $cmds[] = $c('BUDGET', $svcData['budget'] ?? 'Check with client');
        foreach ($segs as $i => $seg) {
            [$from, $to, $dateGDS, $carr] = $parseSeg($seg);
            $flex = $seg['date_flexibility'] ?? '';
            $cmds[] = $c("SEG " . ($i+1) . ": {$from}→{$to}" . ($dateGDS?" {$dateGDS}":''), $flex ? "Flex: {$flex}" : '');
        }

    } elseif ($stage === '3') {
        // Booking
        $cmds[] = $d('Sell itinerary');
        foreach ($segs as $i => $seg) {
            [$from, $to, $dateGDS, $carr] = $parseSeg($seg);
            if ($from && $to && $dateGDS)
                $cmds[] = $c("A{$dateGDS}{$from}{$to}" . ($carr?"*{$carr}":''), "Seg ".($i+1));
        }
        $cmds[] = $c("N{$seats}M1", "Sell {$seats} seats");
        $cmds[] = $d('Names');
        for ($i = 1; $i <= $paxA; $i++) $cmds[] = $c("N.SURNAME/FIRSTNAME MR", "Adult {$i}");
        for ($i = 1; $i <= $paxC; $i++) $cmds[] = $c("N.SURNAME/FIRSTNAME MSTR*P-C0X", "Child {$i} + age");
        for ($i = 1; $i <= $paxI; $i++) $cmds[] = $c("N.I/SURNAME/FIRSTNAME MISS*DDMMMYY", "Infant {$i} DOB");
        $cmds[] = $d('Save');
        $cmds[] = $c('P.T*TRAVHUB', 'Agency phone');
        $cmds[] = $c('T.T*', 'Time limit');
        $cmds[] = $c('ER', 'Save → PNR');
        $cmds[] = $d('Price');
        $allFrom = $parseSeg($segs[0] ?? [])[0] ?? '';
        $carr0   = $parseSeg($segs[0] ?? [])[3] ?? '';
        $cmds[] = $c("FQC" . ($carr0??'') . "/ET", 'Quote on carrier');
        $cmds[] = $c('FQL', 'Fare — read back');
        $cmds[] = $c('ER', 'Fare saved');

    } elseif ($stage === '4') {
        // Documents
        $cmds[] = $d('Passports — one per pax');
        for ($i = 1; $i <= $paxA; $i++) $cmds[] = $c("SI.P{$i}/SSRDOCSYYHK1/P/BD/PASSPORT/BD/DOB/M-F/EXPIRY/SURNAME/GIVEN", "Adult {$i}");
        for ($i = 1; $i <= $paxC; $i++) $cmds[] = $c("SI.P".($paxA+$i)."/SSRDOCSYYHK1/P/BD/PASSPORT/BD/DOB/M-F/EXPIRY/SURNAME/GIVEN", "Child {$i}");
        for ($i = 1; $i <= $paxI; $i++) $cmds[] = $c("SI.P".($paxA+$paxC+$i)."/SSRDOCSYYHK1/P/BD/PASSPORT/BD/DOB/FI/EXPIRY/SURNAME/GIVEN", "Infant {$i} — FI");
        $cmds[] = $d('Contact');
        $cmds[] = $c('SI.SSRPCTCYYHK1/CTC NAME TEL', 'Emergency contact');
        $cmds[] = $d('Meals');
        for ($i = 1; $i <= $paxA; $i++) $cmds[] = $c("SI.P{$i}/MOML", "Muslim meal adult {$i}");
        for ($i = 1; $i <= $paxC; $i++) $cmds[] = $c("SI.P".($paxA+$i)."/CHML", "Child meal");
        if ($paxI) $cmds[] = $c('SI.P{mother}/BSCT*INFANT', 'Bassinet — vs mother');
        $cmds[] = $d('Save & verify');
        $cmds[] = $c('ER', 'Save');
        $cmds[] = $c('*SI', 'All lines must read HK');
    }

    return $cmds;
}
    $segs     = $svcData['segments'] ?? [];
    $paxA     = (int)($svcData['pax_adult']  ?? $svcData['pax'] ?? 1);
    $paxC     = (int)($svcData['pax_child']  ?? 0);
    $paxI     = (int)($svcData['pax_infant'] ?? 0);
    $seats    = $paxA + $paxC;
    $cabin    = strtoupper(substr($svcData['cabin_class'] ?? 'Economy', 0, 1)); // E, B, F

    $lines = [];
    $lines[] = "📋 GDS Research Commands — {$clientName}";
    $lines[] = "Pax: {$paxA}A {$paxC}C {$paxI}I | Seats: {$seats} | Class: {$cabin}";
    $lines[] = str_repeat('─', 40);

    // Pax string for FS command
    $paxStr = '*P1' . ($paxA > 1 ? "-{$paxA}" : '');
    if ($paxC) $paxStr .= "*C0{$paxC}";
    if ($paxI) $paxStr .= '*INF';

    foreach ($segs as $i => $seg) {
        $from = strtoupper(trim($seg['from'] ?? $seg['from_city'] ?? ''));
        $to   = strtoupper(trim($seg['to']   ?? $seg['to_city']  ?? ''));

        // Parse route like "DAC = BKK"
        if ((!$from || !$to) && !empty($seg['route'])) {
            $parts = preg_split('/[=\-→]+/', $seg['route']);
            $from  = strtoupper(trim($parts[0] ?? ''));
            $to    = strtoupper(trim($parts[1] ?? ''));
        }

        // Format date: 2026-08-14 → 14AUG
        $dateRaw = $seg['departure_date'] ?? $seg['travel_date'] ?? $seg['date'] ?? '';
        $dateGDS = '';
        if (preg_match('/(\d{4})-(\d{2})-(\d{2})/', $dateRaw, $m)) {
            $months = ['','JAN','FEB','MAR','APR','MAY','JUN','JUL','AUG','SEP','OCT','NOV','DEC'];
            $dateGDS = $m[3] . ($months[(int)$m[2]] ?? '');
        }

        $flex = $seg['date_flexibility'] ?? '';
        $airlineCode = strtoupper($seg['airline'] ?? '');

        $lines[] = "";
        $lines[] = "── Segment " . ($i+1) . ": {$from} → {$to}" . ($dateGDS ? " ({$dateGDS})" : '') . ($flex ? " [{$flex}]" : '');

        if ($from && $to && $dateGDS) {
            $carr = $airlineCode ? "/{$airlineCode}" : '';
            $lines[] = "A{$dateGDS}{$from}{$to}{$carr}";
            $lines[] = "FS{$seats}{$from}{$dateGDS}{$to}{$carr}{$paxStr}";
        } elseif ($from && $to) {
            $carr = $airlineCode ? "/{$airlineCode}" : '';
            $lines[] = "A{$from}{$to}{$carr}";
        }
    }

    $lines[] = "";
    $lines[] = "── Fare & Conditions";

    // Build route string for FQ
    $allFrom = strtoupper(trim($segs[0]['from'] ?? preg_split('/[=\-→]+/', $segs[0]['route'] ?? '')[0] ?? ''));
    $allTo   = implode('', array_map(fn($s) => strtoupper(trim($s['to'] ?? preg_split('/[=\-→]+/', $s['route'] ?? '')[ 1 ] ?? '')), $segs));

    if ($allFrom && $allTo) {
        $lines[] = "FQPQ{$allFrom}{$allTo}";
    }
    $lines[] = "FQL";
    $lines[] = "FN*1/ALL";

    return implode("\n", $lines);
}

// ── Standalone HTTP mode ──────────────────────────────────────
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

    if (!$workSysId) exit;

    try {
        _generateSop($pdo, $workSysId, $userName);
        ob_clean();
        echo json_encode(['status' => 'success']);
    } catch (Exception $e) {
        error_log('[generate-sop] ' . $e->getMessage());
    }
}