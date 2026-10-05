<?php
/**
 * FILE PATH: /api/transport-services/extract-from-notes.php
 *
 * Mindboard notes → Gemini → Transport quotation (legs[])
 *
 * POST: { work_sys_id, note_sys_ids[], action: 'summary'|'quotation' }
 *
 * OUTPUT (quotation):
 *   { success, quotation: { currency, legs: [ { from, to, date, time,
 *     vehicle_class, pax, net_rate, note }, ... ] } }
 */

session_start();
date_default_timezone_set('Asia/Dhaka');
ini_set('display_errors', 0);

require_once '../../server/api_bootstrap.php';
require_once '../../server/db_connection.php';
require_once '../../server/live_storage.php';
require_once '../../server/smb_upload_handler.php';
require_once '../../server/safe_folder_name.php';
require_once __DIR__ . '/../../server/ai-gemini.php';

header('Content-Type: application/json');

function tsJsonOut(array $d): never { echo json_encode($d, JSON_UNESCAPED_UNICODE); exit; }

$body       = json_decode(file_get_contents('php://input'), true) ?: [];
$workSysId  = trim($body['work_sys_id']  ?? '');
$noteSysIds = $body['note_sys_ids']      ?? [];
$action     = trim($body['action']       ?? 'quotation');

if (!$workSysId || empty($noteSysIds)) tsJsonOut(['success'=>false,'message'=>'work_sys_id + note_sys_ids required']);

try {
    // ── Fetch notes ───────────────────────────────────────────
    $ph   = implode(',', array_fill(0, count($noteSysIds), '?'));
    $stmt = $pdo->prepare("SELECT sys_id, note_type, content, file_name, work_sys_id, service_slug, board_name FROM task_notes WHERE sys_id IN ($ph) AND work_sys_id=? ORDER BY sort_order ASC, id ASC");
    $stmt->execute([...$noteSysIds, $workSysId]);
    $notes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if (!$notes) tsJsonOut(['success'=>false,'message'=>'Notes not found']);

    $omv = null; $textParts = []; $imageParts = [];
    foreach ($notes as $n) {
        if ($n['note_type'] === 'text') { $textParts[] = trim($n['content'] ?? ''); continue; }
        if ($n['note_type'] === 'image' && $n['file_name']) {
            try {
                $ctx = _tsGetCtx($pdo, $n['work_sys_id'], $n['service_slug'] ?? 'transport');
                if (!$omv) $omv = new OMV_SMB_Manager();
                $base = smbBuildPath($ctx);
                $tmp  = sys_get_temp_dir().'/tsextract_'.uniqid().'_'.$n['file_name'];
                if ($omv->downloadFile($base.'/'.$n['file_name'], $tmp) && file_exists($tmp)) {
                    $ext  = strtolower(pathinfo($n['file_name'], PATHINFO_EXTENSION));
                    $mime = $ext==='png'?'image/png':($ext==='webp'?'image/webp':'image/jpeg');
                    $imageParts[] = ['mime_type'=>$mime, 'data'=>base64_encode(file_get_contents($tmp))];
                    @unlink($tmp);
                }
            } catch(Throwable $e) {}
        }
    }
    $combinedText = implode("\n\n", array_filter($textParts));

    // ── Summary ───────────────────────────────────────────────
    if ($action === 'summary') {
        $sys = "Summarize these transport/transfer notes for a Bangladeshi travel agency. Include routes, dates, vehicle types, and pricing if present.";
        $res = callGeminiMultimodal($sys, $combinedText ?: 'See image(s).', $imageParts, 1024);
        if (!$res['success']) tsJsonOut(['success'=>false,'message'=>$res['error']??'AI failed']);
        tsJsonOut(['success'=>true,'summary_text'=>trim($res['text'])]);
    }

    // ── Quotation extraction ──────────────────────────────────
    $sys = <<<SYS
You are helping a Bangladeshi travel agency extract transport/transfer quotation data from notes or screenshots.

Return ONLY a valid JSON object (no markdown) with this exact shape:
{
  "currency": "BDT",
  "legs": [
    {
      "from": "",
      "to": "",
      "date": "",
      "time": "",
      "vehicle_class": "van",
      "transfer_type": "private",
      "pax": 1,
      "qty": 1,
      "price_basis": "per_vehicle",
      "net_rate": 0,
      "note": ""
    }
  ]
}

Rules:
- Create one leg per distinct transfer/route segment
- vehicle_class: one of sedan | suv | van | minibus | coach | other
- transfer_type: "private" or "sic" (shared)
- date: YYYY-MM-DD if present, else empty string
- time: HH:MM 24h if present, else empty string
- price_basis: per_vehicle | per_person | per_day
- net_rate: vendor cost per unit (per vehicle or per person depending on price_basis)
- pax: number of passengers
- qty: number of vehicles/units
- currency: 3-letter code, default BDT
- Extract only what is stated — do not invent routes or prices
SYS;

    $res = callGeminiMultimodal($sys, $combinedText ?: 'See image(s).', $imageParts, 2048);
    if (!$res['success']) tsJsonOut(['success'=>false,'message'=>$res['error']??'AI failed']);

    $raw = trim(preg_replace('/```json|```/', '', $res['text']));
    $extracted = json_decode($raw, true);
    if (!is_array($extracted)) tsJsonOut(['success'=>false,'message'=>'AI returned invalid JSON']);

    tsJsonOut(['success'=>true,'quotation'=>$extracted]);

} catch (Throwable $e) { tsJsonOut(['success'=>false,'message'=>$e->getMessage()]); }

function _tsGetCtx(PDO $pdo, string $workSysId, string $slug): array {
    $s = $pdo->prepare("SELECT client_info FROM works WHERE sys_id=? LIMIT 1");
    $s->execute([$workSysId]);
    $w  = $s->fetch(PDO::FETCH_ASSOC);
    $ci = json_decode($w['client_info'] ?? '{}', true) ?? [];
    return ['client_sys_id'=>$ci['sys_id']??'', 'work_sys_id'=>$workSysId, 'service_slug'=>$slug, 'board'=>'mindboard'];
}