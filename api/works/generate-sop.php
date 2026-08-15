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


    $system = "You are an expert travel operations manager at a Bangladeshi travel agency.
Generate a concise, step-by-step workflow/SOP for processing this travel service.
Write in plain text — numbered steps, clear and actionable.
Keep it under 300 words. Focus on what the operations team needs to DO.
No markdown, no headers — just numbered steps.

Language: Write in simple conversational Bangla mixed with common English travel terms.
Use the language travel agency staff actually speak — NOT formal/archaic Bangla.

AVOID these words, use the alternatives instead:
- উপলব্ধ → available
- যাচাই করুন → check করুন
- রিজার্ভেশন → booking
- অবহিত করুন → জানান
- সম্পন্ন করুন → করুন / শেষ করুন
- চিহ্নিত করুন → খুঁজে বের করুন
- ইস্যুকৃত → দেওয়া
- প্রক্রিয়া করুন → process করুন
- অনুমোদন → approval / OK
- বিবরণ → details
- অনুরোধ → request
- নির্বাচিত → selected / chosen";

    $noteCount  = 0;
    $gdsWritten = false;

    foreach ($services as $slug) {
        $svcData = $segData[$slug] ?? [];
        if (empty($svcData)) {
                    continue;
        }

        // common (pax, budget) merge করো
        $svcData = array_merge($common, $svcData);

        $svcLabel = ucwords(str_replace('_', ' ', $slug));
        $segs     = $svcData['segments'] ?? [$svcData];

        $segText = '';
        foreach ($segs as $i => $seg) {
            $parts = array_filter([
                isset($seg['from'])           ? "From: {$seg['from']}"            : '',
                isset($seg['to'])             ? "To: {$seg['to']}"                : '',
                isset($seg['from_city'])      ? "From: {$seg['from_city']}"       : '',
                isset($seg['to_city'])        ? "To: {$seg['to_city']}"           : '',
                isset($seg['departure_date']) ? "Date: {$seg['departure_date']}"  : '',
                isset($seg['date'])           ? "Date: {$seg['date']}"            : '',
                isset($seg['hotel_name'])     ? "Hotel: {$seg['hotel_name']}"     : '',
                isset($seg['city_name'])      ? "City: {$seg['city_name']}"       : '',
                isset($seg['nights'])         ? "Nights: {$seg['nights']}"        : '',
                isset($seg['country_name'])   ? "Country: {$seg['country_name']}" : '',
                isset($seg['airline'])        ? "Airline: {$seg['airline']}"      : '',
                isset($seg['class'])          ? "Class: {$seg['class']}"          : '',
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

        $sopText = '';
        $result  = geminiCall($system, $userPrompt, 1500, 0.3);
        if ($result['success'] ?? false) {
            $sopText = trim($result['text'] ?? '');
        }

        if ($sopText) {
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
        } else {
                }

        // ── GDS Commands (air_ticket only) ──────────────────────────────────
        if ($slug === 'air_ticket') {
            $commands = _buildAllGdsCommands($pdo, $svcData, $workSysId, $userName);
            $cmdJson  = json_encode($commands, JSON_UNESCAPED_UNICODE);

            $atCheck = $pdo->prepare("SELECT id FROM air_tickets WHERE work_sys_id = ? LIMIT 1");
            $atCheck->execute([$workSysId]);

            if ($atCheck->fetchColumn()) {
                $pdo->prepare("UPDATE air_tickets SET commands = ? WHERE work_sys_id = ?")
                    ->execute([$cmdJson, $workSysId]);
            } else {
                $atIds  = generateV2IDs($pdo, 'air_tickets');
                $atMeta = buildMetaData(null, $userName);
                $pdo->prepare("
                    INSERT INTO air_tickets (uuid, sys_id, work_sys_id, commands, meta_data)
                    VALUES (?, ?, ?, ?, ?)
                ")->execute([$atIds['uuid'], $atIds['sys_id'], $workSysId, $cmdJson, $atMeta]);
                    }
            $gdsWritten = true;
        }
    }

}

// ══════════════════════════════════════════════════════════════════════════════
// GDS COMMAND BUILDER
// ══════════════════════════════════════════════════════════════════════════════

function _buildAllGdsCommands(PDO $pdo, array $svcData, string $workSysId, string $userName): array {
    // Mindboard = Research (Stage 1) + Quotation Checklist (Stage 2) — দুইটাই একসাথে
    $mindboard = array_merge(
        _gdsStage1_Code($svcData),
        _gdsStage2_AI($svcData)
    );

    return [
        'mindboard'    => $mindboard,                     // Mind Board   — Research + Checklist
        'quotation'    => _gdsStage3_Skeleton($svcData),  // Quotation tab — Booking commands
        'booking'      => _gdsStage4_Skeleton($svcData),  // Booking tab   — Confirmation/DOCS
        'confirmation' => [],                             // Confirmation tab — (traveler data এর পরে)
    ];
}

// ── Stage 1: Research — Pure code, guaranteed complete ────────────────────────
function _gdsStage1_Code(array $svcData): array {
    $segs  = $svcData['segments'] ?? [];
    $paxA  = (int)($svcData['pax_adult']  ?? 1);
    $paxC  = (int)($svcData['pax_child']  ?? 0);
    $paxI  = (int)($svcData['pax_infant'] ?? 0);
    $seats = $paxA + $paxC;

    $d = fn($l)             => ['divider'  => true,  'label' => $l];
    $c = fn($cmd, $note='') => ['cmd'      => $cmd,  'note'  => $note];

    // ── Infant DOB calculation ─────────────────────────────────────────────
    // Return date থাকলে সেখান থেকে, না থাকলে departure থেকে 265 দিন বিয়োগ
    $infantDob = '';
    if ($paxI > 0) {
        $refRaw = (count($segs) >= 2 ? ($segs[count($segs)-1]['departure_date'] ?? '') : '')
               ?: ($segs[0]['departure_date'] ?? $segs[0]['date'] ?? '');
        if ($refRaw && ($ts = strtotime($refRaw))) {
            $dobTs = $ts - (265 * 86400);
            static $mon = ['','JAN','FEB','MAR','APR','MAY','JUN','JUL','AUG','SEP','OCT','NOV','DEC'];
            $infantDob = sprintf('%02d', (int)date('d', $dobTs))
                       . ($mon[(int)date('m', $dobTs)] ?? '')
                       . date('y', $dobTs);
        }
    }

    // ── FS pax string ──────────────────────────────────────────────────────
    $adultNums = implode('.', range(1, $paxA));
    $childNums = $paxC > 0 ? implode('.', range($paxA + 1, $paxA + $paxC)) : '';
    $paxStrFS  = '+P' . $adultNums;
    if ($paxC > 0) $paxStrFS .= '*CNN10.' . $childNums . '*CNN10';
    if ($paxI > 0 && $infantDob) {
        $infStart = $paxA + $paxC + 1;
        for ($ii = 0; $ii < $paxI; $ii++) {
            $paxStrFS .= '.P' . ($infStart + $ii) . '*INF*' . $infantDob;
        }
    }

    // ── Carrier — first segment ────────────────────────────────────────────
    $carrier = strtoupper(trim($segs[0]['airline'] ?? $segs[0]['carrier'] ?? ''));

    $cmds = [];

    // ── SECTION 1: Timetable ───────────────────────────────────────────────
    $cmds[] = $d('Timetable');
    foreach ($segs as $seg) {
        [$from, $to] = _parseSegFromTo($seg);
        $dt = _formatDateGDS($seg['departure_date'] ?? $seg['date'] ?? '');
        if ($from && $to && $dt)
            $cmds[] = $c("TT{$dt}{$from}{$to}", "{$from}→{$to}");
    }

    // ── SECTION 2: Live Availability ──────────────────────────────────────
    $cmds[] = $d('Live Availability');

    // a) Core neutral
    foreach ($segs as $seg) {
        [$from, $to] = _parseSegFromTo($seg);
        $dt = _formatDateGDS($seg['departure_date'] ?? $seg['date'] ?? '');
        if ($from && $to && $dt)
            $cmds[] = $c("A{$dt}{$from}{$to}", "{$from}→{$to} neutral display");
    }

    // b) Direct access (most accurate)
    if ($carrier) {
        foreach ($segs as $seg) {
            [$from, $to] = _parseSegFromTo($seg);
            $dt = _formatDateGDS($seg['departure_date'] ?? $seg['date'] ?? '');
            if ($from && $to && $dt)
                $cmds[] = $c("A{$dt}{$from}{$to}*{$carrier}", "{$from}→{$to} direct — most accurate");
        }
    }

    // c) Round trip combined ++ (if 2 segs)
    if (count($segs) === 2) {
        [$f1, $t1] = _parseSegFromTo($segs[0]); $d1 = _formatDateGDS($segs[0]['departure_date'] ?? '');
        [$f2, $t2] = _parseSegFromTo($segs[1]); $d2 = _formatDateGDS($segs[1]['departure_date'] ?? '');
        if ($f1 && $t1 && $d1 && $f2 && $t2 && $d2)
            $cmds[] = $c("A{$d1}{$f1}{$t1}++{$d2}{$f2}{$t2}", "Round trip combined");
    }

    // d) Time bias — first segment
    if (!empty($segs[0])) {
        [$from, $to] = _parseSegFromTo($segs[0]);
        $dt = _formatDateGDS($segs[0]['departure_date'] ?? $segs[0]['date'] ?? '');
        if ($from && $to && $dt) {
            $cmds[] = $c("A{$dt}{$from}{$to}.0600", "Early morning flights");
            $cmds[] = $c("A{$dt}{$from}{$to}.1200", "Midday flights");
            $cmds[] = $c("A{$dt}{$from}{$to}.1800", "Evening flights");
        }
    }

    // e) Cabin class — first segment
    if ($carrier && !empty($segs[0])) {
        [$from, $to] = _parseSegFromTo($segs[0]);
        $dt = _formatDateGDS($segs[0]['departure_date'] ?? $segs[0]['date'] ?? '');
        if ($from && $to && $dt) {
            $cmds[] = $c("A{$dt}{$from}{$to}/{$carrier}-F", "First class");
            $cmds[] = $c("A{$dt}{$from}{$to}/{$carrier}-C", "Business class");
            $cmds[] = $c("A{$dt}{$from}{$to}/{$carrier}-W", "Premium economy");
            $cmds[] = $c("A{$dt}{$from}{$to}/{$carrier}-Y", "Economy");
        }
    }

    // f) Navigation helpers — always static
    $cmds[] = $c('A*O',  'Return availability — reverse city pair');
    $cmds[] = $c('A#',   'Move forward 1 day');
    $cmds[] = $c('A#3',  'Move forward 3 days');
    $cmds[] = $c('A-',   'Move back 1 day');
    $cmds[] = $c('AN',   'Next screen');
    $cmds[] = $c('AY',   'Previous screen');
    $cmds[] = $c('A*C',  'Expand connection details');
    $cmds[] = $c('A*1',  'Expand line 1 (change number as needed)');

    // ── SECTION 3: Priced Shopping ─────────────────────────────────────────
    $cmds[] = $d('Priced Shopping');

    if (count($segs) === 2) {
        // Round trip — both legs in ONE FS command
        [$f1, $t1] = _parseSegFromTo($segs[0]); $d1 = _formatDateGDS($segs[0]['departure_date'] ?? '');
        [$f2, $t2] = _parseSegFromTo($segs[1]); $d2 = _formatDateGDS($segs[1]['departure_date'] ?? '');
        if ($f1 && $t1 && $d1 && $d2)
            $cmds[] = $c("FS{$seats}{$f1}{$d1}{$t1}{$d2}{$f1}{$paxStrFS}", "Round trip {$seats} seats");
    } else {
        // One way or multi-city — one FS per segment
        foreach ($segs as $i => $seg) {
            [$from, $to] = _parseSegFromTo($seg);
            $dt = _formatDateGDS($seg['departure_date'] ?? $seg['date'] ?? '');
            if ($from && $to && $dt)
                $cmds[] = $c("FS{$seats}{$from}{$dt}{$to}{$paxStrFS}", "Seg " . ($i+1) . " {$seats} seats");
        }
    }

    // ── SECTION 4: Fare Display ───────────────────────────────────────────
    $cmds[] = $d('Fare Display');

    // First segment থেকে date/from/to নাও
    [$fdFrom, $fdTo] = _parseSegFromTo($segs[0]);
    $fdDt = _formatDateGDS($segs[0]['departure_date'] ?? $segs[0]['date'] ?? '');

    if ($fdFrom && $fdTo && $fdDt) {
        $cmds[] = $c("FD{$fdDt}{$fdFrom}{$fdTo}",               'Fare display — all carriers');
        $cmds[] = $c("FD{$fdDt}{$fdFrom}{$fdTo}" . ($carrier ? "/{$carrier}" : ''), 'Restricted to carrier');
        // Round trip with return date
        if (count($segs) === 2) {
            [$f2, $t2] = _parseSegFromTo($segs[1]);
            $d2 = _formatDateGDS($segs[1]['departure_date'] ?? '');
            if ($d2) $cmds[] = $c("FD{$fdDt}{$fdFrom}{$fdTo}.{$d2}", 'With return date — round trip fares');
        }
    }
    $cmds[] = $c('FDC1',   'Fare rule text for line 1 (change number as needed)');
    $cmds[] = $c('FN*1',   'Rule categories menu for fare line 1');

    // ── SECTION 5: Fare Rules ─────────────────────────────────────────────
    $cmds[] = $d('Fare Rules');
    $cmds[] = $c('FN*1/16', '⭐ Penalties — MOST IMPORTANT — read before quoting');
    $cmds[] = $c('FN*1/05', 'Advance reservation / ticketing requirements');
    $cmds[] = $c('FN*1/06', 'Minimum stay');
    $cmds[] = $c('FN*1/07', 'Maximum stay');
    $cmds[] = $c('FN*1/31', 'Voluntary changes');
    $cmds[] = $c('FN*1/33', 'Voluntary refunds');
    $cmds[] = $c('FN*1/ALL','Baggage · Refund · Changes — full summary');

    // ── SECTION 6: Fare Quote ─────────────────────────────────────────────
    $cmds[] = $d('Fare Quote');
    [$allFrom] = _parseSegFromTo($segs[0]);
    $allVia = implode('', array_map(fn($s) => _parseSegFromTo($s)[1], $segs));
    if ($allFrom && $allVia)
        $cmds[] = $c("FQPQ{$allFrom}{$allVia}", 'Through fare on all carriers');
    $cmds[] = $c('FQL',    'Fare + taxes breakdown');
    $cmds[] = $c('FQBB',   'Best Buy — lowest fare without rebooking');
    $cmds[] = $c('FQBA',   'Best Buy + rebook — lowest fare, auto rebooks');
    if ($carrier) $cmds[] = $c("FQ/{$carrier}", 'Quote plated on carrier');
    if ($paxC > 0) $cmds[] = $c('FQ*C05', 'Child discount — age 5 default');
    if ($paxI > 0) $cmds[] = $c('FQ*INF', 'Infant fare quote');

    return $cmds;
}

// ── Stage 2: Quotation Checklist — AI (visa/destination rules) ───────────────
function _gdsStage2_AI(array $svcData): array {
    $result = geminiJSON(_gdsSystemPrompt(), _buildStage2Prompt($svcData), 1200);

    if (!($result['success'] ?? false) || empty($result['data'])) {
            return _gdsStage2_Fallback($svcData);
    }

    $cmds = _normalizeGdsCmds($result['data']);
    return $cmds;
}

// ── Stage 3: Booking Skeleton ────────────────────────────────────────────────
function _gdsStage3_Skeleton(array $svcData): array {
    $paxA  = (int)($svcData['pax_adult']  ?? 1);
    $paxC  = (int)($svcData['pax_child']  ?? 0);
    $paxI  = (int)($svcData['pax_infant'] ?? 0);
    $seats = $paxA + $paxC;
    $segs  = $svcData['segments'] ?? [];

    $d = fn($l)             => ['divider'   => true,  'label' => $l];
    $c = fn($cmd, $note='') => ['cmd'       => $cmd,  'note'  => $note];
    $n = fn($note)          => ['note_only' => true,  'note'  => $note];

    $cmds   = [];
    $cmds[] = $n('⚠️ Traveler entry দেওয়ার পরে "Regenerate" করুন — real name দিয়ে commands আসবে');

    $cmds[] = $d('Sell itinerary');
    foreach ($segs as $i => $seg) {
        [$from, $to] = _parseSegFromTo($seg);
        $dt   = _formatDateGDS($seg['departure_date'] ?? $seg['date'] ?? '');
        $carr = strtoupper($seg['airline'] ?? '');
        if ($from && $to && $dt)
            $cmds[] = $c("A{$dt}{$from}{$to}" . ($carr ? "*{$carr}" : ''), "Seg " . ($i+1) . " — line select করুন");
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
    $cmds[] = $c('FQL',     'Fare breakdown');
    $cmds[] = $c('ER',      'Save priced itinerary');

    return $cmds;
}

// ── Stage 4: Confirmation/Documents Skeleton ─────────────────────────────────
function _gdsStage4_Skeleton(array $svcData): array {
    $paxA = (int)($svcData['pax_adult']  ?? 1);
    $paxC = (int)($svcData['pax_child']  ?? 0);
    $paxI = (int)($svcData['pax_infant'] ?? 0);

    $d = fn($l)             => ['divider'   => true, 'label' => $l];
    $c = fn($cmd, $note='') => ['cmd'       => $cmd, 'note'  => $note];
    $n = fn($note)          => ['note_only' => true, 'note'  => $note];

    $cmds   = [];
    $cmds[] = $n('⚠️ Traveler passport info দেওয়ার পরে "Regenerate" করুন — real passport commands আসবে');

    $cmds[] = $d('Passport (DOCS) — একজন per pax');
    for ($i = 1; $i <= $paxA; $i++)
        $cmds[] = $c("SI.P{$i}/SSRDOCSYYHK1/P/BD/PASSPORTNO/BD/DOB/M-F/EXPIRY/SURNAME/GIVEN", "Adult {$i}");
    for ($i = 1; $i <= $paxC; $i++)
        $cmds[] = $c("SI.P" . ($paxA+$i) . "/SSRDOCSYYHK1/P/BD/PASSPORTNO/BD/DOB/M-F/EXPIRY/SURNAME/GIVEN", "Child {$i}");
    for ($i = 1; $i <= $paxI; $i++)
        $cmds[] = $c("SI.P" . ($paxA+$paxC+$i) . "/SSRDOCSYYHK1/P/BD/PASSPORTNO/BD/DOB/FI/EXPIRY/SURNAME/GIVEN", "Infant {$i} — gender FI");

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

// ══════════════════════════════════════════════════════════════════════════════
// PROMPT BUILDERS
// ══════════════════════════════════════════════════════════════════════════════

function _gdsSystemPrompt(): string {
    return <<<PROMPT
You are a senior Galileo GDS expert working for a travel agency in Bangladesh.

RULES:
1. Return ONLY a valid JSON array — no explanation, no markdown, no backticks
2. Each item: {"cmd":"...","note":"..."} or {"divider":true,"label":"..."} or {"note_only":true,"note":"..."}
3. Galileo GDS syntax ONLY — NOT Amadeus, NOT Sabre
PROMPT;
}

function _buildStage2Prompt(array $svcData): string {
    $segs        = $svcData['segments'] ?? [];
    $segmentType = $svcData['segment_type'] ?? 'one_way';
    $paxA        = (int)($svcData['pax_adult']  ?? 1);
    $paxC        = (int)($svcData['pax_child']  ?? 0);
    $paxI        = (int)($svcData['pax_infant'] ?? 0);
    $budget      = $svcData['budget'] ?? '';

    $segLines = [];
    foreach ($segs as $i => $seg) {
        [$from, $to] = _parseSegFromTo($seg);
        $date  = $seg['departure_date'] ?? $seg['date'] ?? '';
        $flex  = $seg['date_flexibility'] ?? '';
        $line  = "  Seg " . ($i+1) . ": {$from}→{$to}" . ($date ? " {$date}" : '') . ($flex ? " [{$flex}]" : '');
        $segLines[] = $line;
    }

    $paxLine = "Pax: {$paxA}A / {$paxC}C / {$paxI}I";
    $segsStr = implode("\n", $segLines);

    // Destinations for visa context
    $destinations = array_unique(array_filter(array_map(fn($s) => _parseSegFromTo($s)[1], $segs)));
    $destStr = implode(', ', $destinations);

    return <<<PROMPT
Generate Stage 2: QUOTATION CHECKLIST for this air ticket booking.

Trip: {$segmentType}
{$paxLine}
Destinations: {$destStr}
Budget: {$budget}
Itinerary:
{$segsStr}

Generate a practical pre-quotation checklist covering:
1. "Passenger Checklist" — passport validity (min 6 months past return), visa requirements for {$destStr} from Bangladesh, health/insurance
2. "Fare & Budget" — fare validity (48hrs), budget alignment, refund/change conditions
3. "Segment Confirmation" — per-segment date confirmation, flexibility if any
4. "Special Notes" — any destination-specific requirements

Use dividers. Return JSON array only.
PROMPT;
}

// ══════════════════════════════════════════════════════════════════════════════
// FALLBACK — Stage 2 AI fail হলে
// ══════════════════════════════════════════════════════════════════════════════

function _gdsStage2_Fallback(array $svcData): array {
    $segs   = $svcData['segments'] ?? [];
    $paxA   = (int)($svcData['pax_adult']  ?? 1);
    $paxC   = (int)($svcData['pax_child']  ?? 0);
    $paxI   = (int)($svcData['pax_infant'] ?? 0);
    $budget = $svcData['budget'] ?? '';

    $d = fn($l)             => ['divider' => true, 'label' => $l];
    $c = fn($cmd, $note='') => ['cmd'     => $cmd, 'note'  => $note];
    $cmds = [];

    $cmds[] = $d('Passenger Checklist');
    $cmds[] = $c("PAX: {$paxA}A / {$paxC}C / {$paxI}I", 'Client এর সাথে confirm করুন');
    $cmds[] = $c('PASSPORT', 'Min 6 months validity past last travel date');
    $cmds[] = $c('VISA',     'Destination visa requirement check করুন');

    $cmds[] = $d('Fare & Budget');
    $cmds[] = $c('FARE VALIDITY', '48 ঘণ্টা — client কে জানান');
    if ($budget) $cmds[] = $c("BUDGET: {$budget}", 'Client এর সাথে align করুন');

    $cmds[] = $d('Segment Confirmation');
    foreach ($segs as $i => $seg) {
        [$from, $to] = _parseSegFromTo($seg);
        $dt   = _formatDateGDS($seg['departure_date'] ?? $seg['date'] ?? '');
        $flex = $seg['date_flexibility'] ?? '';
        $cmds[] = $c(
            "SEG " . ($i+1) . ": {$from}→{$to}" . ($dt ? " {$dt}" : ''),
            $flex ? "Flexibility: {$flex}" : 'Fixed date'
        );
    }

    return $cmds;
}

// ══════════════════════════════════════════════════════════════════════════════
// HELPERS
// ══════════════════════════════════════════════════════════════════════════════

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

function _normalizeGdsCmds(array $raw): array {
    $out = [];
    foreach ($raw as $item) {
        if (!is_array($item)) continue;
        if (!empty($item['divider']))   $out[] = ['divider'  => true, 'label' => (string)($item['label'] ?? '')];
        elseif (isset($item['cmd']))    $out[] = ['cmd'      => (string)$item['cmd'], 'note' => (string)($item['note'] ?? '')];
        elseif (isset($item['note_only'])) $out[] = ['note_only' => true, 'note' => (string)($item['note'] ?? '')];
    }
    return $out;
}

// ── Standalone HTTP mode ──────────────────────────────────────────────────────
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