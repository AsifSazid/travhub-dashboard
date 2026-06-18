<?php
/**
 * api/ai/suggest.php  (Gen-3)
 * POST { type, day_title?, country_name?, city_name?, existing_items?, limit? }
 */
session_start();
require_once '../../server/api_bootstrap.php';
require_once '../../server/db_connection.php';
require_once '../../server/ai-gemini.php';

// Set correct header for JSON output
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'POST only']); 
    exit;
}

$in             = json_decode(file_get_contents('php://input'), true) ?: [];
$type           = in_array($in['type'] ?? '', ['activity', 'transport']) ? $in['type'] : 'activity';
$day_title      = trim($in['day_title']      ?? '');
$country_name   = trim($in['country_name']   ?? '');
$city_name      = trim($in['city_name']      ?? '');
$country_sys_id = trim($in['country_sys_id'] ?? '');
$city_sys_id    = trim($in['city_sys_id']    ?? '');
$existing       = is_array($in['existing_items'] ?? null) ? $in['existing_items'] : [];
$limit          = max(1, min(5, (int)($in['limit'] ?? 4)));

// ── Ask Gemini for suggestions ────────────────────────────────────────────
$system = "You are an expert travel itinerary assistant. 
Your ONLY job is to output a raw JSON object matching the exact structure requested by the user. 
DO NOT include any markdown code blocks like ```json, DO NOT include introductory or concluding text. Output raw JSON only.";

$existingStr = '';
if (!empty($existing)) {
    // array_column safe-check, jodi key na thake tobe empty array ashbe
    $titles = array_filter(array_column($existing, 'title_snapshot'));
    if (!empty($titles)) {
        $existingStr = "\nAlready planned items for this day (DO NOT suggest these again): " . implode(', ', $titles);
    }
}

// User prompt ti ke un-escaped format e dile model bhalo bojhe
$user = "Suggest exactly {$limit} {$type} options for a day trip/plan in {$city_name}" . ($country_name ? " ({$country_name})" : "") . ".\n"
      . "Day itinerary theme/title: {$day_title}.{$existingStr}\n\n"
      . "Respond strictly with this JSON format structure:\n"
      . "{\n"
      . "  \"suggestions\": [\n"
      . "    { \"label\": \"Name of activity or transport\", \"reason\": \"One short sentence explaining why it fits this day\" }\n"
      . "  ]\n"
      . "}";

$aiResult = geminiJSON($system, $user, 800);

// ── Error Debugging & Sanitization ────────────────────────────────────────
if (!$aiResult['success']) {
    // Jodi function executei na hoy (api key error, timeout ityadi)
    echo json_encode(['success' => false, 'message' => 'AI API Connection Error: ' . ($aiResult['error'] ?? 'Unknown')]); 
    exit;
}

// Jodi Data empty thake ba string format e thake ja decode hoyni, ta clean korar cheshtha:
if (empty($aiResult['data']) && isset($aiResult['raw_response'])) {
    $raw = trim($aiResult['raw_response']);
    
    // Markdown syntax thakle ta remove korar regex (```json ... ``` bad deya)
    $raw = preg_replace('/^```(?:json)?/i', '', $raw);
    $raw = preg_replace('/
```$/', '', $raw);
    $raw = trim($raw);
    
    $decoded = json_decode($raw, true);
    if (json_last_error() === JSON_ERROR_NONE) {
        $aiResult['data'] = $decoded;
    }
}

// Final check jodi JSON asholei valid na hoy
if (empty($aiResult['data']['suggestions'])) {
    echo json_encode([
        'success' => false, 
        'message' => 'AI returned invalid structure or empty suggestions.',
        'debug_raw' => $aiResult['raw_response'] ?? 'No raw response'
    ]); 
    exit;
}

$suggestions = $aiResult['data']['suggestions'];

// ── Run match-catalog for each suggestion ─────────────────────────────────
$results = [];
foreach ($suggestions as $s) {
    $label = trim($s['label'] ?? '');
    if (!$label) continue;

    // FULLTEXT search - Using unique placeholders (:q1, :q2) to prevent PDO duplicate errors
    try {
        $params = [
            ':q1' => $label,
            ':q2' => $label
        ];
        $extra  = '';
        
        if (!empty($country_sys_id)) { 
            $extra .= " AND country_sys_id = :csid"; 
            $params[':csid'] = $country_sys_id; 
        }
        if (!empty($city_sys_id)) { 
            $extra .= " AND city_sys_id = :ctsid"; 
            $params[':ctsid'] = $city_sys_id; 
        }

        if ($type === 'activity') {
            $stmt = $pdo->prepare("SELECT sys_id, name, category, city_name, duration_hours, images, 
                MATCH(name, search_terms) AGAINST(:q1 IN NATURAL LANGUAGE MODE) AS score 
                FROM activities 
                WHERE status='active' AND is_package_override=0 $extra 
                AND MATCH(name, search_terms) AGAINST(:q2 IN NATURAL LANGUAGE MODE) 
                ORDER BY score DESC LIMIT 3");
        } else {
            $stmt = $pdo->prepare("SELECT sys_id, name, type, from_city_name, to_city_name, 
                MATCH(name, search_terms) AGAINST(:q1 IN NATURAL LANGUAGE MODE) AS score 
                FROM transport_services 
                WHERE status='active' $extra 
                AND MATCH(name, search_terms) AGAINST(:q2 IN NATURAL LANGUAGE MODE) 
                ORDER BY score DESC LIMIT 3");
        }
        
        $stmt->execute($params); // Passing array directly to execute is cleaner than explicit foreach loop bindValue
        $matches = $stmt->fetchAll();
    } catch (Throwable $e) {
        // Optional: Log $e->getMessage() here if debugging empty results
        $matches = [];
    }

    $results[] = [
        'ai_label'  => $label,
        'reason'    => $s['reason'] ?? '',
        'matches'   => array_map(function($m) use ($type) {
            if ($type === 'activity') {
                $imgs = json_decode($m['images'] ?? '[]', true) ?: [];
                return [
                    'sys_id'         => $m['sys_id'],
                    'name'           => $m['name'],
                    'category'       => $m['category'] ?? null,
                    'city'           => $m['city_name'] ?? null,
                    'duration_hours' => (float)$m['duration_hours'],
                    'thumb'          => $imgs[0]['url'] ?? null
                ];
            }
            return [
                'sys_id' => $m['sys_id'],
                'name'   => $m['name'],
                'type'   => $m['type'] ?? null,
                'from'   => $m['from_city_name'] ?? null,
                'to'     => $m['to_city_name'] ?? null
            ];
        }, $matches),
    ];
}

echo json_encode(['success' => true, 'type' => $type, 'results' => $results], JSON_UNESCAPED_UNICODE);