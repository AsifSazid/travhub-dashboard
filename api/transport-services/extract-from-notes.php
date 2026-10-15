<?php
/**
 * FILE PATH: /api/transport-services/extract-from-notes.php
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

function tsJsonOut(array $data): never { echo json_encode($data, JSON_UNESCAPED_UNICODE); exit; }

$body       = json_decode(file_get_contents('php://input'), true) ?: [];
$workSysId  = trim($body['work_sys_id']  ?? '');
$noteSysIds = $body['note_sys_ids']      ?? [];
$action     = trim($body['action']       ?? 'quotation');

if (!$workSysId)        tsJsonOut(['success'=>false,'message'=>'work_sys_id required']);
if (empty($noteSysIds)) tsJsonOut(['success'=>false,'message'=>'note_sys_ids required']);

try {
    // ── Step 1: fetch notes ───────────────────────────────────
    $ph   = implode(',', array_fill(0, count($noteSysIds), '?'));
    $stmt = $pdo->prepare("
        SELECT sys_id, note_type, content, file_name, work_sys_id, service_slug, board_name
        FROM task_notes WHERE sys_id IN ($ph) AND work_sys_id = ?
        ORDER BY sort_order ASC, id ASC
    ");
    $stmt->execute([...$noteSysIds, $workSysId]);
    $notes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if (!$notes) tsJsonOut(['success'=>false,'message'=>'Notes not found']);

    // ── Step 2: collect text + images from SMB ────────────────
    $omv = null; $textParts = []; $imageParts = [];
    foreach ($notes as $n) {
        if ($n['note_type'] === 'text') { $textParts[] = trim($n['content'] ?? ''); continue; }
        if (in_array($n['note_type'], ['image','pdf_images']) && $n['file_name']) {
            try {
                $ctx  = _tsGetCtxByWork($pdo, $n['work_sys_id'], $n['service_slug'] ?? 'transport', $n['board_name'] ?? 'mindboard');
                $base = smbBuildPath($ctx);
                if (!$omv) $omv = new OMV_SMB_Manager();
                $tmp  = sys_get_temp_dir() . '/tsgen_' . uniqid() . '_' . $n['file_name'];
                if ($omv->get_file("{$base}/{$n['file_name']}", $tmp) && file_exists($tmp)) {
                    $ext  = strtolower(pathinfo($tmp, PATHINFO_EXTENSION));
                    $mime = match($ext) { 'png'=>'image/png','webp'=>'image/webp','gif'=>'image/gif',default=>'image/jpeg' };
                    $imageParts[] = ['mime_type'=>$mime,'data'=>base64_encode(file_get_contents($tmp))];
                    unlink($tmp);
                }
            } catch (Throwable $e) { error_log('[ts-gen] image fetch failed: '.$e->getMessage()); }
        }
    }
    if (!$textParts && !$imageParts) tsJsonOut(['success'=>false,'message'=>'No usable content in selected notes']);

    // Images present → ignore text notes to avoid location contamination
    $combinedText = !empty($imageParts) ? '' : implode("\n\n---\n\n", array_filter($textParts));

    // ── Step 3: pax context only (NO country/location) ────────
    $workContext = '';
    try {
        $ws = $pdo->prepare("SELECT segment_data FROM works WHERE sys_id=? LIMIT 1");
        $ws->execute([$workSysId]);
        $sd = $ws->fetchColumn();
        if ($sd) {
            $parsed = json_decode($sd, true);
            $common = $parsed['common'] ?? [];
            if (!empty($common['pax_adult'])) {
                $workContext = "\n\nKnown pax for this work (use only if not visible in image): Adults: "
                    . ($common['pax_adult']??0) . ", Children: " . ($common['pax_child']??0);
            }
        }
    } catch (Throwable $e) {}

    // ── Step 4: Generate ──────────────────────────────────────
    if ($action === 'summary') {
        $res = _tsGenSummary($combinedText, $imageParts);
        if (!$res['success']) tsJsonOut(['success'=>false,'message'=>$res['error']??'AI failed']);
        tsJsonOut(['success'=>true,'summary_text'=>$res['text']]);
    }

    $res = _tsGenQuotation($combinedText, $imageParts, $workContext);
    if (!$res['success']) tsJsonOut(['success'=>false,'message'=>$res['error']??'AI failed']);
    tsJsonOut(['success'=>true,'quotation'=>$res['data']]);

} catch (Throwable $e) {
    error_log('[ts-gen] '.$e->getMessage());
    tsJsonOut(['success'=>false,'message'=>'Server error: '.$e->getMessage()]);
}

// ── SMB context builder ───────────────────────────────────────
function _tsGetCtxByWork(PDO $pdo, string $workSysId, string $serviceSlug, string $boardName='mindboard'): array {
    $s = $pdo->prepare("SELECT sys_id, JSON_UNQUOTE(JSON_EXTRACT(client_info,'$.sys_id')) AS cid, JSON_UNQUOTE(JSON_EXTRACT(client_info,'$.name')) AS cname FROM works WHERE sys_id=? LIMIT 1");
    $s->execute([$workSysId]); $r = $s->fetch(PDO::FETCH_ASSOC);
    return ['work_sys_id'=>$workSysId,'task_sys_id'=>null,'client_sys_id'=>$r['cid']??null,'client_name'=>$r['cname']??'unknown','service_slug'=>$serviceSlug,'sub_folder'=>"notes/{$boardName}"];
}

// ── Gemini multimodal call (same pattern as air ticket) ───────
function _tsMultimodalCall(string $system, string $userText, array $imageParts, int $maxTokens=4096): array {
    $apiKey = trim(@file_get_contents(__DIR__.'/../../gemini-apikey.txt') ?: @file_get_contents(__DIR__.'/../../../gemini-apikey.txt') ?: '');
    if (!$apiKey) return ['success'=>false,'error'=>'Gemini API key not found'];

    $parts = [];
    if ($userText !== '') $parts[] = ['text'=>$userText];
    foreach ($imageParts as $img) $parts[] = ['inline_data'=>['mime_type'=>$img['mime_type'],'data'=>$img['data']]];
    if (!$parts) return ['success'=>false,'error'=>'No content'];

    $payload = [
        'system_instruction' => ['parts'=>[['text'=>$system]]],
        'contents'           => [['role'=>'user','parts'=>$parts]],
        'generationConfig'   => ['maxOutputTokens'=>$maxTokens,'temperature'=>0.2,'responseMimeType'=>'application/json'],
    ];

    $ch = curl_init("https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key={$apiKey}");
    curl_setopt_array($ch, [CURLOPT_POST=>true, CURLOPT_POSTFIELDS=>json_encode($payload,JSON_UNESCAPED_UNICODE),
        CURLOPT_RETURNTRANSFER=>true, CURLOPT_CONNECTTIMEOUT=>10, CURLOPT_TIMEOUT=>45,
        CURLOPT_HTTPHEADER=>['Content-Type: application/json']]);
    $raw = curl_exec($ch); $code = curl_getinfo($ch,CURLINFO_HTTP_CODE); $err = curl_error($ch); curl_close($ch);

    if ($err) return ['success'=>false,'error'=>'cURL: '.$err];
    if ($code !== 200) { $b=json_decode($raw,true); return ['success'=>false,'error'=>$b['error']['message']??"HTTP {$code}"]; }

    $body = json_decode($raw,true);
    $text = $body['candidates'][0]['content']['parts'][0]['text'] ?? '';
    $fin  = $body['candidates'][0]['finishReason'] ?? '';
    if (trim($text)==='') return ['success'=>false,'error'=>"Empty response (finishReason:{$fin})"];
    return ['success'=>true,'text'=>trim($text)];
}

// ── JSON sanitizer (same as air ticket) ──────────────────────
function _tsSanitizeJson(string $text): string {
    $text = trim($text);
    if (preg_match('/```(?:json)?\s*([\s\S]*?)\s*```/i', $text, $m)) $text = trim($m[1]);
    if (strlen($text)>0 && $text[0]!=='{' && $text[0]!=='[') {
        $po=strpos($text,'{'); $pa=strpos($text,'[');
        if ($po===false) $s=$pa; elseif ($pa===false) $s=$po; else $s=min($po,$pa);
        if ($s!==false) $text=substr($text,$s);
    }
    $out=''; $inStr=false; $esc=false; $len=strlen($text);
    for ($i=0;$i<$len;$i++) {
        $ch=$text[$i];
        if ($inStr) {
            if ($esc) { $out.=$ch; $esc=false; continue; }
            if ($ch==='\\') { $out.=$ch; $esc=true; continue; }
            if ($ch==='"') { $out.=$ch; $inStr=false; continue; }
            if ($ch==="\n") { $out.='\\n'; continue; }
            if ($ch==="\r") { $out.='\\r'; continue; }
            if ($ch==="\t") { $out.='\\t'; continue; }
            $out.=$ch;
        } else { if ($ch==='"') $inStr=true; $out.=$ch; }
    }
    return $out;
}

// ── Summary ───────────────────────────────────────────────────
function _tsGenSummary(string $text, array $imgs): array {
    $sys = "You are helping a Bangladeshi travel agency. Summarize transport/transfer information from the notes or screenshots. Include: route (from→to), vehicle types, prices, dates, pax count. Be concise. Return ONLY a JSON object: {\"summary\": \"...\"}";
    $res = _tsMultimodalCall($sys, $text?:"See attached image(s).", $imgs, 1024);
    if (!$res['success']) return $res;
    $p = json_decode(_tsSanitizeJson($res['text']), true);
    $s = is_array($p) ? ($p['summary']??$res['text']) : $res['text'];
    return ['success'=>true,'text'=>trim($s)];
}

// ── Quotation ─────────────────────────────────────────────────
function _tsGenQuotation(string $text, array $imgs, string $ctx): array {
    $sys = <<<SYS
You are helping a Bangladeshi travel agency extract transport/transfer quotation data from a booking portal screenshot (Trip.com, Booking.com, etc.) or text notes.

STRICT RULES:
1. Copy location names EXACTLY as written in the image — airport name, hotel name, city. Do NOT invent, abbreviate, or substitute.
2. If the source shows multiple vehicle options for the SAME route, create ONE leg per vehicle option with its own price.
3. Use the EXACT currency symbol shown ($=USD, €=EUR, £=GBP, ﷼=SAR). Read the currency from the image only — never infer from country.
4. If any field is not clearly visible, use empty string — do NOT guess.

Return ONLY valid JSON (no markdown):
{
  "currency": "USD",
  "legs": [
    {
      "from": "exact location from image",
      "to": "exact location from image",
      "date": "YYYY-MM-DD or empty",
      "time": "HH:MM or empty",
      "vehicle_class": "minibus",
      "vehicle_name": "Minibus 9 Seater",
      "transfer_type": "private",
      "pax": 9,
      "qty": 1,
      "price_basis": "per_vehicle",
      "net_rate": 248.22,
      "note": ""
    }
  ]
}
vehicle_class: sedan|suv|van|minibus|coach|other
SYS;

    $user = $text ? "Notes:\n{$text}{$ctx}" : "Extract from the attached image.{$ctx}";
    $res  = _tsMultimodalCall($sys, $user, $imgs, 4096);
    if (!$res['success']) return $res;

    $raw  = _tsSanitizeJson($res['text']);
    $data = json_decode($raw, true);
    if (!is_array($data) || empty($data['legs'])) {
        error_log('[ts-gen] json parse failed: '.substr($raw,0,300));
        return ['success'=>false,'error'=>'Quotation structure not found — AI returned invalid JSON'];
    }
    return ['success'=>true,'data'=>$data];
}