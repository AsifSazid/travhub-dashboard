<?php
/**
 * FILE PATH: /api/hotel-services/generate-from-notes.php
 *
 * Mindboard থেকে select করা notes (text + image) → Gemini → Hotel info extract।
 * Summary অথবা Hotel Quotation generate করে।
 */

session_start();
date_default_timezone_set('Asia/Dhaka');
ini_set('display_errors', 0);
error_reporting(E_ALL);

require_once '../../server/api_bootstrap.php';
require_once '../../server/db_connection.php';
require_once '../../server/live_storage.php';
require_once '../../server/smb_upload_handler.php';
require_once '../../server/safe_folder_name.php';

header('Content-Type: application/json');

function htGenJsonOut(array $data): never {
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

$body       = json_decode(file_get_contents('php://input'), true) ?: [];
$workSysId  = trim($body['work_sys_id']  ?? '');
$noteSysIds = $body['note_sys_ids']      ?? [];
$action     = trim($body['action']       ?? 'quotation');

if (!$workSysId)        htGenJsonOut(['success'=>false,'message'=>'work_sys_id required']);
if (empty($noteSysIds)) htGenJsonOut(['success'=>false,'message'=>'note_sys_ids required']);

try {
    // ── Step 1: fetch notes ───────────────────────────────────
    $ph   = implode(',', array_fill(0, count($noteSysIds), '?'));
    $stmt = $pdo->prepare("
        SELECT sys_id, note_type, content, file_name, work_sys_id, service_slug, board_name
        FROM task_notes
        WHERE sys_id IN ($ph) AND work_sys_id = ?
        ORDER BY sort_order ASC, id ASC
    ");
    $stmt->execute([...$noteSysIds, $workSysId]);
    $notes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if (!$notes) htGenJsonOut(['success'=>false,'message'=>'Notes not found']);

    // ── Step 2: collect text + download images ────────────────
    $omv       = null;
    $textParts = [];
    $imageParts = []; // [['mime_type'=>..., 'data'=>base64...]]

    foreach ($notes as $n) {
        if ($n['note_type'] === 'text') {
            $textParts[] = trim($n['content'] ?? '');
            continue;
        }
        if (in_array($n['note_type'], ['image','pdf_images']) && $n['file_name']) {
            try {
                $ctx  = _htGenGetCtx($pdo, $n['work_sys_id'], $n['service_slug'] ?? 'hotel', $n['board_name'] ?? 'mindboard');
                $base = smbBuildPath($ctx);
                if (!$omv) $omv = new OMV_SMB_Manager();
                $tmp  = sys_get_temp_dir() . '/htgen_' . uniqid() . '_' . basename($n['file_name']);
                $ok   = $omv->get_file("{$base}/{$n['file_name']}", $tmp);
                if ($ok && file_exists($tmp)) {
                    $ext  = strtolower(pathinfo($tmp, PATHINFO_EXTENSION));
                    $mime = match($ext) { 'png'=>'image/png','webp'=>'image/webp','gif'=>'image/gif',default=>'image/jpeg' };
                    $imageParts[] = ['mime_type'=>$mime,'data'=>base64_encode(file_get_contents($tmp))];
                    unlink($tmp);
                }
            } catch (Throwable) {}
        }
    }

    $combinedText = implode("\n\n---\n\n", array_filter($textParts));
    $hasImages    = !empty($imageParts);

    if (!$textParts && !$hasImages) {
        htGenJsonOut(['success'=>false,'message'=>'No usable content in selected notes']);
    }

    // ── Step 3: Summary ───────────────────────────────────────
    if ($action === 'summary') {
        $system  = "You are helping a Bangladeshi travel agency. Summarize these hotel-related notes into a concise, structured summary in Bengali or English (match the language of the notes). Include hotel name, location, dates, room details, pricing if present.";
        $userTxt = $combinedText ?: 'See attached image(s).';
        $res = _htGenCall($system, $userTxt, $imageParts, 1024);
        if (!$res['success']) htGenJsonOut(['success'=>false,'message'=>$res['error']??'AI failed']);
        $summaryText = $res['text'];
        // Try to parse if JSON
        $parsed = json_decode(_htGenSanitize($summaryText), true);
        if (is_array($parsed) && isset($parsed['summary'])) $summaryText = $parsed['summary'];
        htGenJsonOut(['success'=>true,'summary_text'=>trim($summaryText)]);
    }

    // ── Step 4: Hotel Quotation extraction ───────────────────
    $system = <<<SYS
You are helping a Bangladeshi travel agency extract hotel quotation data from notes and/or screenshots.
Return ONLY a valid JSON object with this exact shape (no markdown, no explanation):
{
  "hotel_name": "",
  "city": "",
  "country": "",
  "star_rating": 0,
  "address": "",
  "phone": "",
  "email": "",
  "check_in": "",
  "check_out": "",
  "nights": 0,
  "room_type": "",
  "bed_config": "",
  "size_sqm": null,
  "max_adults": 2,
  "max_children": 0,
  "meal_plan": "bb",
  "rooms": 1,
  "currency": "BDT",
  "net_rate": 0,
  "sell_rate": 0,
  "cancellation_policy": "",
  "notes": []
}
Rules: star_rating 1-5 or 0; check_in/check_out YYYY-MM-DD or empty; nights: calculate from dates; meal_plan: room_only|bb|hb|fb|ai; currency 3-letter code; only extract data present, never invent.
SYS;

    $userTxt = $combinedText ?: 'Extract hotel quotation data from the attached image.';
    $res = _htGenCall($system, $userTxt, $imageParts, 2048);
    if (!$res['success']) htGenJsonOut(['success'=>false,'message'=>$res['error']??'AI failed']);

    $extracted = json_decode(_htGenSanitize($res['text']), true);
    if (!is_array($extracted)) htGenJsonOut(['success'=>false,'message'=>'AI returned invalid JSON: '.substr($res['text'],0,200)]);

    // ── Fix pricing: AI often returns TOTAL price as sell_rate ───
    // e.g. "480 USD for 7 nights" → AI writes sell_rate=480 but
    // our UI needs PER-NIGHT rate. Detect and divide by nights.
    $nights   = max(1, (int)($extracted['nights']    ?? 1));
    $rooms    = max(1, (int)($extracted['rooms']     ?? 1));
    $netRate  = (float)($extracted['net_rate']  ?? 0);
    $sellRate = (float)($extracted['sell_rate'] ?? 0);

    if ($sellRate > 0 && $netRate === 0.0) {
        // Only sell_rate given — check if it looks like a total
        $perNightGuess = $sellRate / $nights;
        if ($nights > 1 && $perNightGuess >= 30) {
            // Likely a total — divide to get per-night
            $extracted['sell_rate'] = round($perNightGuess, 2);
            $extracted['net_rate']  = round($perNightGuess, 2);
        } else {
            // Already per-night
            $extracted['net_rate'] = $sellRate;
        }
    } elseif ($sellRate > 0 && $netRate > 0 && $sellRate >= $netRate * $nights) {
        // sell_rate is a total (>= net_rate * nights) — divide by nights
        $extracted['sell_rate'] = round($sellRate / $nights, 2);
    }

    // ── Step 5: Masterdata match ──────────────────────────────
    $masterSuggestion = null;
    $hotelName = trim($extracted['hotel_name'] ?? '');

    if ($hotelName) {
        $like = '%'.$hotelName.'%';
        $mh = $pdo->prepare("SELECT sys_id,name,city_name,country_name,star_rating,address,phone,email FROM hotels WHERE (LOWER(name) LIKE LOWER(?) OR LOWER(search_terms) LIKE LOWER(?)) AND status='active' LIMIT 1");
        $mh->execute([$like,$like]);
        $matchedHotel = $mh->fetch(PDO::FETCH_ASSOC) ?: null;

        $matchedRoomType = null; $matchedRoomRate = null;

        if ($matchedHotel && !empty($extracted['room_type'])) {
            $mr = $pdo->prepare("SELECT sys_id,room_name,bed_config,max_adults,max_children,size_sqm FROM room_types WHERE hotel_sys_id=? AND LOWER(room_name) LIKE LOWER(?) AND status='active' LIMIT 1");
            $mr->execute([$matchedHotel['sys_id'],'%'.$extracted['room_type'].'%']);
            $matchedRoomType = $mr->fetch(PDO::FETCH_ASSOC) ?: null;

            if ($matchedRoomType && !empty($extracted['check_in'])) {
                $rr = $pdo->prepare("SELECT sys_id,meal_plan,net_cost,sell_price,valid_from,valid_to,currency_code FROM room_rates WHERE room_type_sys_id=? AND meal_plan=? AND valid_from<=? AND valid_to>=? AND status='active' ORDER BY valid_from DESC LIMIT 1");
                $rr->execute([$matchedRoomType['sys_id'],$extracted['meal_plan']??'bb',$extracted['check_in'],$extracted['check_in']]);
                $matchedRoomRate = $rr->fetch(PDO::FETCH_ASSOC) ?: null;
            }
        }

        $masterSuggestion = [
            'hotel'    => ['name'=>$hotelName,'city'=>$extracted['city']??'','country'=>$extracted['country']??'','star_rating'=>$extracted['star_rating']??0,'address'=>$extracted['address']??'','phone'=>$extracted['phone']??'','email'=>$extracted['email']??''],
            'room_type'=> ['room_name'=>$extracted['room_type']??'','bed_config'=>$extracted['bed_config']??'','max_adults'=>$extracted['max_adults']??2,'max_children'=>$extracted['max_children']??0,'size_sqm'=>$extracted['size_sqm']??null],
            'room_rate'=> ['meal_plan'=>$extracted['meal_plan']??'bb','net_cost'=>$extracted['net_rate']??0,'sell_price'=>$extracted['sell_rate']??0,'currency_code'=>$extracted['currency']??'BDT','valid_from'=>$extracted['check_in']??'','valid_to'=>$extracted['check_out']??'','cancellation_policy'=>$extracted['cancellation_policy']??''],
            'matched_hotel_sys_id'     =>$matchedHotel?$matchedHotel['sys_id']:null,
            'matched_hotel_data'       =>$matchedHotel,
            'matched_room_type_sys_id' =>$matchedRoomType?$matchedRoomType['sys_id']:null,
            'matched_room_type_data'   =>$matchedRoomType,
            'matched_room_rate_data'   =>$matchedRoomRate,
            'has_new_data'             =>true,
        ];
    }

    // ── Auto masterdata upsert ───────────────────────────────
    $upsertResult = null;
    if ($masterSuggestion) {
        try {
            $upsertPayload = json_encode([
                'matched_hotel_sys_id'     => $masterSuggestion['matched_hotel_sys_id'],
                'matched_room_type_sys_id' => $masterSuggestion['matched_room_type_sys_id'],
                'hotel'     => $masterSuggestion['hotel'],
                'room_type' => $masterSuggestion['room_type'],
                'room_rate' => $masterSuggestion['room_rate'],
                'city_sys_id'    => null,
                'country_sys_id' => null,
            ], JSON_UNESCAPED_UNICODE);

            // Detect base URL from server
            $scheme  = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
            $host    = $_SERVER['HTTP_HOST'] ?? 'localhost';
            $upsertUrl = $scheme . '://' . $host . '/dev/api/hotel-services/masterdata-upsert.php';

            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL            => $upsertUrl,
                CURLOPT_POST           => true,
                CURLOPT_POSTFIELDS     => $upsertPayload,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_CONNECTTIMEOUT => 5,
                CURLOPT_TIMEOUT        => 10,
                CURLOPT_HTTPHEADER     => [
                    'Content-Type: application/json',
                    'Cookie: ' . ($_SERVER['HTTP_COOKIE'] ?? ''),
                ],
            ]);
            $upsertRaw = curl_exec($ch);
            $upsertErr = curl_error($ch);
            curl_close($ch);

            if (!$upsertErr && $upsertRaw) {
                $upsertResult = json_decode($upsertRaw, true);
                if (!empty($upsertResult['hotel_sys_id'])) {
                    $masterSuggestion['matched_hotel_sys_id']     = $upsertResult['hotel_sys_id'];
                    $masterSuggestion['matched_room_type_sys_id'] = $upsertResult['room_type_sys_id'] ?? null;
                    $masterSuggestion['upsert_actions']           = $upsertResult['actions'] ?? [];
                }
            }
        } catch (Throwable $ue) {
            $upsertResult = ['success'=>false,'message'=>$ue->getMessage()];
        }
    }

    htGenJsonOut(['success'=>true,'quotation'=>array_merge($extracted,[
        'masterdata_suggestion' => $masterSuggestion,
        'masterdata_upsert'     => $upsertResult,
    ])]);

} catch (Throwable $e) {
    htGenJsonOut(['success'=>false,'message'=>$e->getMessage().' in '.$e->getFile().' line '.$e->getLine()]);
}

// ── Gemini multimodal call (own implementation — no ai-gemini.php dependency) ──
function _htGenCall(string $system, string $userText, array $imageParts, int $maxTokens): array
{
    $apiKey = '';
    foreach ([__DIR__.'/../../gemini-apikey.txt', __DIR__.'/../../../gemini-apikey.txt'] as $p) {
        if (file_exists($p)) { $apiKey = trim(file_get_contents($p)); break; }
    }
    if (!$apiKey) return ['success'=>false,'error'=>'Gemini API key not configured'];

    $model = 'gemini-2.5-flash';
    $url   = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}";

    $parts = [];
    if ($userText !== '') $parts[] = ['text'=>$userText];
    foreach ($imageParts as $img) {
        $parts[] = ['inline_data'=>['mime_type'=>$img['mime_type'],'data'=>$img['data']]];
    }
    if (!$parts) return ['success'=>false,'error'=>'No content to send'];

    $payload = [
        'system_instruction' => ['parts'=>[['text'=>$system]]],
        'contents'           => [['role'=>'user','parts'=>$parts]],
        'generationConfig'   => ['maxOutputTokens'=>$maxTokens,'temperature'=>0.2,'responseMimeType'=>'application/json'],
    ];

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => json_encode($payload, JSON_UNESCAPED_UNICODE),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_TIMEOUT        => 45,
        CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
    ]);
    $raw  = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err  = curl_error($ch);
    curl_close($ch);

    if ($err)        return ['success'=>false,'error'=>'cURL: '.$err];
    if ($code !== 200) {
        $b = json_decode($raw, true);
        return ['success'=>false,'error'=>$b['error']['message']??"HTTP {$code}: ".substr($raw,0,200)];
    }

    $body   = json_decode($raw, true);
    $text   = $body['candidates'][0]['content']['parts'][0]['text'] ?? '';
    $finish = $body['candidates'][0]['finishReason'] ?? '';

    if (trim($text) === '') return ['success'=>false,'error'=>"Empty response (finishReason: {$finish})"];
    return ['success'=>true,'text'=>trim($text)];
}

// ── JSON sanitizer (same as air ticket pattern) ───────────────
function _htGenSanitize(string $text): string
{
    $text = trim($text);
    if (preg_match('/```(?:json)?\s*([\s\S]*?)\s*```/i', $text, $m)) $text = trim($m[1]);
    if (strlen($text) > 0 && $text[0] !== '{' && $text[0] !== '[') {
        $po = strpos($text,'{'); $pa = strpos($text,'[');
        if ($po===false) $s=$pa; elseif ($pa===false) $s=$po; else $s=min($po,$pa);
        if ($s!==false) $text = substr($text,$s);
    }
    $out=''; $inStr=false; $esc=false; $len=strlen($text);
    for ($i=0;$i<$len;$i++) {
        $ch=$text[$i];
        if ($inStr) {
            if ($esc)        { $out.=$ch; $esc=false; continue; }
            if ($ch==='\\') { $out.=$ch; $esc=true;  continue; }
            if ($ch==='"')   { $out.=$ch; $inStr=false; continue; }
            if ($ch==="\n") { $out.='\\n'; continue; }
            if ($ch==="\r") { $out.='\\r'; continue; }
            if ($ch==="\t") { $out.='\\t'; continue; }
            $out.=$ch;
        } else { if ($ch==='"') $inStr=true; $out.=$ch; }
    }
    return $out;
}

// ── SMB context helper ────────────────────────────────────────
function _htGenGetCtx(PDO $pdo, string $workSysId, string $serviceSlug, string $board): array
{
    $s = $pdo->prepare("SELECT JSON_UNQUOTE(JSON_EXTRACT(client_info,'$.sys_id')) AS cid, JSON_UNQUOTE(JSON_EXTRACT(client_info,'$.name')) AS cname FROM works WHERE sys_id=? LIMIT 1");
    $s->execute([$workSysId]);
    $w = $s->fetch(PDO::FETCH_ASSOC) ?: [];
    return [
        'work_sys_id'   => $workSysId,
        'task_sys_id'   => null,
        'client_sys_id' => $w['cid']   ?? null,
        'client_name'   => $w['cname'] ?? 'unknown',
        'service_slug'  => $serviceSlug,
        'sub_folder'    => "notes/{$board}",
    ];
}