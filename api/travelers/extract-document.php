<?php
/**
 * Document Extraction API for Traveler Creation
 * Extracts detailed passport/NID data using Gemini AI
 */
session_start();
require_once '../../server/db_connection.php';
require_once '../../server/live_storage.php';
require_once '../../server/make-dir.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
error_reporting(E_ALL);
ini_set('display_errors', 0);

$GEMINI_API_KEY = trim(file_get_contents('../../gemini-apikey.txt'));
$GEMINI_MODEL   = trim(file_get_contents('../../gemini-model.txt'));
if (empty($GEMINI_API_KEY)) { echo json_encode(['success'=>false,'message'=>'Gemini API key not configured']); exit; }
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { echo json_encode(['success'=>false,'message'=>'POST only']); exit; }
if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) { echo json_encode(['success'=>false,'message'=>'No file uploaded']); exit; }

$documentType = $_POST['document_type'] ?? 'passport';
if (!in_array($documentType, ['passport','nid'])) { echo json_encode(['success'=>false,'message'=>'Invalid document type']); exit; }

$tmpDir = '../../tmp/';
if (!is_dir($tmpDir)) mkdir($tmpDir, 0777, true);

$file          = $_FILES['file'];
$fileExtension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
$allowed       = ['jpg','jpeg','png','webp','pdf'];
if (!in_array($fileExtension, $allowed)) { echo json_encode(['success'=>false,'message'=>'Invalid file type']); exit; }

$safeFileName = 'tmp_' . uniqid() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '', $file['name']);
$tmpFilePath  = $tmpDir . $safeFileName;
if (!move_uploaded_file($file['tmp_name'], $tmpFilePath)) { echo json_encode(['success'=>false,'message'=>'Failed to save file']); exit; }

try {
    $result = ($fileExtension === 'pdf')
        ? handlePDFExtraction($tmpFilePath, $GEMINI_API_KEY, $GEMINI_MODEL, $documentType)
        : handleImageExtraction($tmpFilePath, $fileExtension, $GEMINI_API_KEY, $GEMINI_MODEL, $documentType);

    echo json_encode([
        'success'            => true,
        'message'            => 'Document extracted successfully',
        'data'               => $result['display_data'],
        'full_extracted_data'=> $result['full_data'],
        'file_path'          => $tmpFilePath,
        'document_type'      => $documentType,
    ]);
} catch (Exception $e) {
    if (file_exists($tmpFilePath)) unlink($tmpFilePath);
    error_log('Extraction Error: ' . $e->getMessage());
    echo json_encode(['success'=>false,'message'=>'Extraction failed: ' . $e->getMessage()]);
}

// ── MIME detection ────────────────────────────────────────────
function detectMimeType($path) {
    $h = fopen($path, 'rb'); if (!$h) return 'image/jpeg';
    $hex = strtoupper(bin2hex(fread($h, 12))); fclose($h);
    if (strpos($hex,'89504E47')===0) return 'image/png';
    if (strpos($hex,'FFD8FF')===0)   return 'image/jpeg';
    if (strpos($hex,'52494646')===0 && strpos($hex,'57454250')!==false) return 'image/webp';
    $m=['jpg'=>'image/jpeg','jpeg'=>'image/jpeg','png'=>'image/png','webp'=>'image/webp'];
    return $m[strtolower(pathinfo($path,PATHINFO_EXTENSION))] ?? 'image/jpeg';
}

// ── Image extraction ──────────────────────────────────────────
function handleImageExtraction($path, $ext, $key, $model, $type) {
    if (!is_readable($path)) throw new Exception('Cannot read file');
    $mime  = detectMimeType($path);
    $data  = base64_encode(file_get_contents($path));
    $parts = [['text'=>buildPrompt($type)],['inline_data'=>['mime_type'=>$mime,'data'=>$data]]];
    return geminiRequest($key, $model, $parts, $type);
}

// ── PDF extraction ────────────────────────────────────────────
function handlePDFExtraction($path, $key, $model, $type) {
    if (!extension_loaded('imagick')) throw new Exception('Imagick required');
    $im = new Imagick(); $im->setResolution(150,150); $im->readImage($path);
    $n  = $im->getNumberImages(); if (!$n) throw new Exception('PDF empty');
    $parts = [['text'=>buildPrompt($type, true)]];
    for ($i=0; $i<min($n,3); $i++) {
        $im->setIteratorIndex($i); $im->setImageFormat('png');
        $b = base64_encode($im->getImageBlob());
        if ($b) $parts[] = ['inline_data'=>['mime_type'=>'image/png','data'=>$b]];
    }
    $im->clear(); $im->destroy();
    if (count($parts)<=1) throw new Exception('No images from PDF');
    return geminiRequest($key, $model, $parts, $type);
}

// ── Gemini API call ───────────────────────────────────────────
function geminiRequest($key, $model, $parts, $type) {
    $payload = json_encode([
        'contents'        => [['parts'=>$parts]],
        'generationConfig'=> ['temperature'=>0.1,'maxOutputTokens'=>8192],
    ], JSON_UNESCAPED_UNICODE);

    $ch = curl_init("https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$key}");
    curl_setopt_array($ch,[CURLOPT_RETURNTRANSFER=>true,CURLOPT_POST=>true,
        CURLOPT_POSTFIELDS=>$payload,CURLOPT_HTTPHEADER=>['Content-Type: application/json'],
        CURLOPT_TIMEOUT=>60]);
    $raw  = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err  = curl_error($ch);
    curl_close($ch);

    if ($err) throw new Exception('Curl: '.$err);
    if ($code !== 200) {
        $b = json_decode($raw,true);
        throw new Exception('Gemini API: '.($b['error']['message'] ?? "HTTP $code"));
    }

    $resp = json_decode($raw, true);

    // Check finish reason
    $finishReason = $resp['candidates'][0]['finishReason'] ?? 'UNKNOWN';
    if ($finishReason === 'SAFETY') throw new Exception('Gemini blocked response (SAFETY)');
    if ($finishReason === 'RECITATION') throw new Exception('Gemini blocked response (RECITATION)');

    $rawText = $resp['candidates'][0]['content']['parts'][0]['text'] ?? '';
    if (empty($rawText)) throw new Exception("Empty response from Gemini (finishReason: $finishReason)");

    // Extract JSON — find first { match closing } using depth counter (handles markdown fences)
    $extracted = null;
    $start     = strpos($rawText, '{');
    if ($start !== false) {
        $depth=0; $end=-1; $len=strlen($rawText);
        $inStr=false; $esc=false;
        for ($i=$start; $i<$len; $i++) {
            $c=$rawText[$i];
            if ($esc)  { $esc=false; continue; }
            if ($c==='\\' && $inStr) { $esc=true; continue; }
            if ($c==='"') { $inStr=!$inStr; continue; }
            if ($inStr) continue;
            if ($c==='{') $depth++;
            elseif ($c==='}') { $depth--; if ($depth===0) { $end=$i; break; } }
        }
        if ($end>$start) $extracted = json_decode(substr($rawText,$start,$end-$start+1), true);
    }

    if (!is_array($extracted)) {
        error_log('[extract] finishReason='.$finishReason.' rawText='.substr($rawText,0,300));
        throw new Exception("No valid JSON found (finishReason: $finishReason)");
    }

    $extracted = convertDates($extracted);
    return ['display_data'=>buildDisplay($extracted,$type),'full_data'=>$extracted];
}

// ── Prompt ────────────────────────────────────────────────────
function buildPrompt($type, $multi=false) {
    $note = $multi ? "Multiple pages — check ALL pages. " : "";
    if ($type==='passport') return $note.'Extract passport bio page. ALL dates DD-MM-YYYY. Return ONLY JSON:
{"page_type":"bio_page","bio_info":{"passport_number":"","country_code":"","surname":"","given_names":"","full_name":"","nationality":"","date_of_birth":"","sex":"","place_of_birth":"","date_of_issue":"","date_of_expiry":"","issuing_authority":"","father_name":"","mother_name":"","spouse_name":"","permanent_address":"","emergency_contact":{"name":"","relationship":"","address":"","telephone":""},"mrz_line_1":"","mrz_line_2":""}}';

    return $note.'Extract NID. ALL dates DD-MM-YYYY. Return ONLY JSON.
Front: {"page_type":"nid_front","nid_info":{"nid_number":"","full_name_en":"","full_name_bn":"","father_name_en":"","father_name_bn":"","mother_name_en":"","mother_name_bn":"","date_of_birth":"","date_of_birth_bn":"","photo_present":"true"}}
Back: {"page_type":"nid_back","nid_info":{"nid_number":"","full_name_en":"","full_name_bn":"","permanent_address_en":"","permanent_address_bn":"","place_of_birth_en":"","place_of_birth_bn":"","date_of_issue":"","blood_group":""}}';
}

// ── Date conversion ───────────────────────────────────────────
function convertDates($data) {
    $fields = ['date_of_birth','date_of_issue','date_of_expiry','date_of_birth_bn'];
    array_walk_recursive($data, function(&$v,$k) use($fields) {
        if (in_array($k,$fields) && !empty($v) && is_string($v)) $v=toDate($v);
    });
    return $data;
}
function toDate($s) {
    $s=trim($s);
    if (preg_match('/^\d{2}-\d{2}-\d{4}$/',$s)) return $s;
    foreach (['d M Y','d F Y','d-M-Y','d/m/Y','d-m-Y','d.m.Y','Y-m-d','Y/m/d','m/d/Y','M d, Y','F d, Y'] as $f) {
        $d=DateTime::createFromFormat($f,$s);
        if ($d && $d->format($f)===$s) return $d->format('d-m-Y');
    }
    $t=strtotime($s);
    return ($t&&$t>0) ? date('d-m-Y',$t) : $s;
}

// ── Display data ──────────────────────────────────────────────
function buildDisplay($data, $type) {
    $d=['full_name'=>'','date_of_birth'=>'','document_number'=>'','page_type'=>$data['page_type']??''];
    if ($type==='passport' && isset($data['bio_info'])) {
        $b=$data['bio_info'];
        $d['full_name']=$b['full_name']??'';
        $d['date_of_birth']=$b['date_of_birth']??'';
        $d['document_number']=$b['passport_number']??'';
    } elseif ($type==='nid' && isset($data['nid_info'])) {
        $n=$data['nid_info'];
        $d['full_name']=$n['full_name_en']??$n['full_name_bn']??'';
        $d['date_of_birth']=$n['date_of_birth']??'';
        $d['document_number']=$n['nid_number']??'';
    }
    return $d;
}