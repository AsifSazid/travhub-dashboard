<?php
/**
 * api/ai/suggest.php  (Gen-3)
 * POST { type, day_title?, country_name?, city_name?, existing_items?, limit? }
 *
 * type: activity | transport
 * Gemini suggests 3-5 relevant items based on day context,
 * then match-catalog runs on each suggestion label.
 * Returns merged: AI label + catalog candidates.
 */
session_start();
require_once '../../../server/api_bootstrap.php';
require_once '../../../server/db_connection.php';
require_once '../../../server/ai-gemini.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success'=>false,'message'=>'POST only']); exit;
}

$in             = json_decode(file_get_contents('php://input'), true) ?: [];
$type           = in_array($in['type']??'',['activity','transport']) ? $in['type'] : 'activity';
$day_title      = trim($in['day_title']      ?? '');
$country_name   = trim($in['country_name']   ?? '');
$city_name      = trim($in['city_name']      ?? '');
$country_sys_id = trim($in['country_sys_id'] ?? '');
$city_sys_id    = trim($in['city_sys_id']    ?? '');
$existing       = is_array($in['existing_items']??null) ? $in['existing_items'] : [];
$limit          = max(1,min(5,(int)($in['limit']??4)));

// ── Ask Gemini for suggestions ────────────────────────────────────────────
$system = "You are a travel itinerary assistant. Suggest realistic {$type} options for a travel day. Output valid JSON only — no prose, no markdown.";

$existingStr = $existing ? "\nAlready planned: " . implode(', ', array_column($existing,'title_snapshot')) : '';
$user = "Suggest {$limit} {$type} options for a day in {$city_name}{$country_name?' ('.$country_name.')':''}.
Day title: {$day_title}.{$existingStr}

Return JSON:
{\"suggestions\":[{\"label\":\"<name>\",\"reason\":\"<1 line why suitable>\"}]}";

$aiResult = geminiJSON($system, $user, 800);

if (!$aiResult['success']) {
    echo json_encode(['success'=>false,'message'=>$aiResult['error']??'AI error']); exit;
}

$suggestions = $aiResult['data']['suggestions'] ?? [];

// ── Run match-catalog for each suggestion ─────────────────────────────────
$results = [];
foreach ($suggestions as $s) {
    $label = trim($s['label'] ?? '');
    if (!$label) continue;

    // FULLTEXT search
    try {
        $params = [':q'=>$label];
        $extra  = '';
        if ($country_sys_id) { $extra.=" AND country_sys_id=:csid"; $params[':csid']=$country_sys_id; }
        if ($city_sys_id)    { $extra.=" AND city_sys_id=:ctsid";   $params[':ctsid']=$city_sys_id;   }

        if ($type === 'activity') {
            $stmt = $pdo->prepare("SELECT sys_id,name,category,city_name,duration_hours,images,MATCH(name,search_terms) AGAINST(:q IN NATURAL LANGUAGE MODE) AS score FROM activities WHERE status='active' AND is_package_override=0 $extra AND MATCH(name,search_terms) AGAINST(:q IN NATURAL LANGUAGE MODE) ORDER BY score DESC LIMIT 3");
        } else {
            $stmt = $pdo->prepare("SELECT sys_id,name,type,from_city_name,to_city_name,MATCH(name,search_terms) AGAINST(:q IN NATURAL LANGUAGE MODE) AS score FROM transport_services WHERE status='active' $extra AND MATCH(name,search_terms) AGAINST(:q IN NATURAL LANGUAGE MODE) ORDER BY score DESC LIMIT 3");
        }
        foreach ($params as $k=>$v) $stmt->bindValue($k,$v);
        $stmt->execute();
        $matches = $stmt->fetchAll();
    } catch (Throwable $e) {
        $matches = [];
    }

    $results[] = [
        'ai_label'  => $label,
        'reason'    => $s['reason'] ?? '',
        'matches'   => array_map(function($m) use($type) {
            if ($type === 'activity') {
                $imgs = json_decode($m['images']??'[]',true)?:[];
                return ['sys_id'=>$m['sys_id'],'name'=>$m['name'],'category'=>$m['category']??null,'city'=>$m['city_name']??null,'duration_hours'=>(float)$m['duration_hours'],'thumb'=>$imgs[0]['url']??null];
            }
            return ['sys_id'=>$m['sys_id'],'name'=>$m['name'],'type'=>$m['type']??null,'from'=>$m['from_city_name']??null,'to'=>$m['to_city_name']??null];
        }, $matches),
    ];
}

echo json_encode(['success'=>true,'type'=>$type,'results'=>$results], JSON_UNESCAPED_UNICODE);
