<?php
/**
 * FILE PATH: /api/works/ai-mindboard.php
 *
 * POST { work_sys_id, service_slug }
 *
 * Reads Mind Board notes + work context (segment_data, special_instruction)
 * → Sends to Gemini → Returns HTML planning briefing
 */
ob_start();
session_start();
date_default_timezone_set('Asia/Dhaka');
ini_set('display_errors', 0);
header('Content-Type: application/json');

require_once '../../server/api_bootstrap.php';
require_once '../../server/db_connection.php';
require_once '../../server/ai-gemini.php';

$raw  = file_get_contents('php://input');
$body = json_decode($raw, true) ?? [];

$workSysId   = trim($body['work_sys_id']   ?? '');
$serviceSlug = trim($body['service_slug']  ?? 'air_ticket');
$userName    = $_SESSION['user_name'] ?? 'system';

if (!$workSysId) {
    ob_clean();
    echo json_encode(['status' => 'error', 'message' => 'work_sys_id required']);
    exit;
}

try {
    // 1. Fetch work context
    $ws = $pdo->prepare("SELECT client_info, segment_data, service_data, special_instruction, service_type FROM works WHERE sys_id = ? LIMIT 1");
    $ws->execute([$workSysId]);
    $work = $ws->fetch(PDO::FETCH_ASSOC);
    if (!$work) throw new Exception('Work not found');

    $ci          = json_decode($work['client_info'],  true) ?? [];
    $segData     = json_decode($work['segment_data'], true) ?? [];
    $svcData     = $segData[$serviceSlug] ?? [];
    $specialIns  = json_decode($work['special_instruction'], true) ?? [];
    $clientName  = $ci['name'] ?? 'Unknown';

    // 2. Fetch Mind Board notes for this service
    $ns = $pdo->prepare("
        SELECT content, note_type, file_name, meta_data
        FROM task_notes
        WHERE work_sys_id = ? AND board_type = 'work'
          AND service_slug = ? AND board_name = 'mindboard'
          AND note_type = 'text'
        ORDER BY sort_order ASC, id ASC
        LIMIT 30
    ");
    $ns->execute([$workSysId, $serviceSlug]);
    $notes = $ns->fetchAll(PDO::FETCH_ASSOC);

    if (empty($notes)) {
        ob_clean();
        echo json_encode(['status' => 'error', 'message' => 'No Mind Board notes found. Add some notes first.']);
        exit;
    }

    // 3. Build context for Gemini
    $notesText = '';
    foreach ($notes as $i => $n) {
        $meta = $n['meta_data'] ? json_decode($n['meta_data'], true) : [];
        $user = $meta['created_by_date']['user'] ?? '';
        $date = $meta['created_by_date']['date'] ?? '';
        $notesText .= ($i + 1) . ". [{$user} · {$date}] " . ($n['content'] ?? '') . "\n";
    }

    $segmentContext = '';
    if (!empty($svcData)) {
        $segmentContext = "\nSegment Data:\n" . json_encode($svcData, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    }

    $spInsText = '';
    if (!empty($specialIns)) {
        $items = is_array($specialIns) ? $specialIns : [$specialIns];
        $spInsText = "\nSpecial Instructions from Client:\n" . implode("\n", array_map(fn($i) => "• $i", $items));
    }

    $service = ucwords(str_replace('_', ' ', $serviceSlug));

    $system = "You are an expert travel operations planner for a travel agency. 
Your job is to analyze planning notes from an employee's Mind Board and generate a concise, actionable planning briefing.
Output ONLY clean HTML — no markdown, no code blocks. Use simple HTML: <p>, <ul>, <li>, <strong>, <br>.
Keep it practical, structured, and under 250 words.";

    $user = "Work: {$workSysId}
Client: {$clientName}
Service: {$service}
{$segmentContext}
{$spInsText}

Employee Mind Board Notes:
{$notesText}

Generate a planning briefing that:
1. Summarizes the key action items from the notes
2. Highlights any important deadlines or priorities
3. Flags potential issues or things to watch out for
4. Suggests next steps

Format as clean HTML paragraphs and bullet points.";

    $result = geminiJSON($system, $user, 1500);

    if ($result['success'] ?? false) {
        // geminiJSON returns parsed JSON — but we want HTML text
        // So use geminiCall instead
    }

    // Use geminiCall for HTML output
    $r = geminiCall($system, $user, 1500, 0.4);

    if (!$r['success']) {
        throw new Exception($r['error'] ?? 'AI generation failed');
    }

    $html = trim($r['text'] ?? '');
    // Strip any markdown code blocks just in case
    $html = preg_replace('/^```html?\s*/m', '', $html);
    $html = preg_replace('/```\s*$/m', '', $html);

    ob_clean();
    echo json_encode([
        'status' => 'success',
        'html'   => $html,
    ]);

} catch (Exception $e) {
    ob_clean();
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}