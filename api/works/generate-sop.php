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
    }
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