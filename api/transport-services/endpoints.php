<?php
/**
 * FILE PATH: /api/transport-services/endpoints.php
 *
 * Transport Service Module — action-based routing
 * No Booking tab — Quotation → Confirmation → Task directly.
 *
 * GET:
 *   ?action=get&work_sys_id=...
 *
 * POST actions:
 *   init, save_quotation, delete_quotation,
 *   add_to_confirmation, update_confirmation, remove_confirmation,
 *   update_conf_status, confirm_and_create_task
 */

ob_start();
session_start();
date_default_timezone_set('Asia/Dhaka');
ini_set('display_errors', 0);

set_error_handler(function($errno, $errstr, $errfile, $errline) {
    ob_clean(); http_response_code(500);
    echo json_encode(['status'=>'error','message'=>"PHP Error: $errstr in $errfile:$errline"]); exit;
});
register_shutdown_function(function() {
    $e = error_get_last();
    if ($e && in_array($e['type'], [E_ERROR,E_PARSE,E_CORE_ERROR,E_COMPILE_ERROR])) {
        ob_clean(); http_response_code(500);
        echo json_encode(['status'=>'error','message'=>"Fatal: {$e['message']} in {$e['file']}:{$e['line']}"]);
    }
});

require_once '../../server/api_bootstrap.php';
require_once '../../server/db_connection.php';
require_once '../../server/sys_id_generator_v2.php';
require_once '../../server/generate_meta_data.php';

$method   = $_SERVER['REQUEST_METHOD'];
$action   = $_GET['action'] ?? '';
$userName = $_SESSION['user_name'] ?? 'system';

$body = [];
if ($method === 'POST') {
    $body   = json_decode(file_get_contents('php://input'), true) ?? [];
    $action = $body['action'] ?? $_POST['action'] ?? $action;
}

// ── Fetch row ─────────────────────────────────────────────────
function _tsFetch(PDO $pdo, string $workSysId): ?array {
    $s = $pdo->prepare("SELECT * FROM transport_services WHERE work_sys_id=? LIMIT 1");
    $s->execute([$workSysId]);
    $row = $s->fetch(PDO::FETCH_ASSOC);
    if (!$row) return null;
    foreach (['ts_quotations','ts_confirmations'] as $col)
        $row[$col] = $row[$col] ? (json_decode($row[$col], true) ?: []) : [];
    return $row;
}

// ── Save row ──────────────────────────────────────────────────
function _tsSave(PDO $pdo, string $workSysId, array $quotations, array $confirmations, ?string $existingMeta, string $userName): void {
    $meta = buildMetaData($existingMeta, $userName);
    $pdo->prepare("UPDATE transport_services SET ts_quotations=?, ts_confirmations=?, meta_data=? WHERE work_sys_id=?")
        ->execute([
            json_encode($quotations,    JSON_UNESCAPED_UNICODE),
            json_encode($confirmations, JSON_UNESCAPED_UNICODE),
            $meta, $workSysId,
        ]);
}

// ── Local ID (Q-001, C-001) ───────────────────────────────────
function _tsLocalId(array $existing, string $prefix): string {
    $max = 0;
    foreach ($existing as $item) {
        $id = $item['sys_id'] ?? '';
        if (strpos($id, $prefix.'-') === 0) {
            $num = (int)substr($id, strlen($prefix)+1);
            if ($num > $max) $max = $num;
        }
    }
    return $prefix.'-'.str_pad($max+1, 3, '0', STR_PAD_LEFT);
}

// ── Mark notes used ───────────────────────────────────────────
function _tsMarkNotesUsed(PDO $pdo, array $noteIds, string $qSysId): void {
    if (!$noteIds) return;
    $ph   = implode(',', array_fill(0, count($noteIds), '?'));
    $stmt = $pdo->prepare("SELECT sys_id, meta_data FROM task_notes WHERE sys_id IN ($ph)");
    $stmt->execute($noteIds);
    $upd  = $pdo->prepare("UPDATE task_notes SET meta_data=? WHERE sys_id=?");
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $r) {
        $meta = $r['meta_data'] ? (json_decode($r['meta_data'], true) ?: []) : [];
        $used = $meta['used_in_quotations'] ?? [];
        if (!in_array($qSysId, $used, true)) { $used[] = $qSysId; $meta['used_in_quotations'] = $used; }
        $upd->execute([json_encode($meta, JSON_UNESCAPED_UNICODE), $r['sys_id']]);
    }
}

// ── Auto-create task ──────────────────────────────────────────
function _tsAutoCreateTask(PDO $pdo, array $conf, string $workSysId, string $userName): ?string {
    try {
        $check = $pdo->prepare("SELECT sys_id FROM tasks WHERE confirmation_sys_id=? AND work_sys_id=? LIMIT 1");
        $check->execute([$conf['sys_id'], $workSysId]);
        if ($existing = $check->fetchColumn()) return $existing;

        $ws = $pdo->prepare("SELECT client_info FROM works WHERE sys_id=? LIMIT 1");
        $ws->execute([$workSysId]);
        $work = $ws->fetch(PDO::FETCH_ASSOC);
        if (!$work) return 'NO_WORK';
        $ci         = json_decode($work['client_info'], true) ?? [];
        $clientName = $ci['name']   ?? 'Unknown';
        $clientSysId= $ci['sys_id'] ?? null;

        $sw = $pdo->prepare("SELECT sys_id FROM service_works WHERE work_sys_id=? AND service_slug='transport' LIMIT 1");
        $sw->execute([$workSysId]);
        $swSysId = $sw->fetchColumn() ?: null;

        // Task name from first leg
        $legs     = $conf['legs'] ?? [];
        $firstLeg = $legs[0] ?? [];
        $route    = trim(($firstLeg['from'] ?? '') . ' → ' . ($firstLeg['to'] ?? ''));
        $taskName = 'Transport' . ($route ? ' — ' . $route : ' — ' . $conf['sys_id']);

        $ids  = generateV2IDs($pdo, 'tasks');
        $meta = buildMetaData(null, $userName);
        $pdo->prepare("
            INSERT INTO tasks (uuid, sys_id, service_work_sys_id, work_sys_id, client_sys_id, workname, client_name, status, service_slug, confirmation_sys_id, meta_data)
            VALUES (?, ?, ?, ?, ?, ?, ?, 'open', 'transport', ?, ?)
        ")->execute([
            $ids['uuid'], $ids['sys_id'],
            $swSysId, $workSysId, $clientSysId,
            $taskName, $clientName,
            $conf['sys_id'], $meta,
        ]);
        return $ids['sys_id'];
    } catch (Throwable $e) { return 'ERR:'.$e->getMessage(); }
}

// ─────────────────────────────────────────────────────────────
header('Content-Type: application/json; charset=utf-8');

try {
    // ── GET ───────────────────────────────────────────────────
    if ($method === 'GET') {
        $wid = trim($_GET['work_sys_id'] ?? '');
        if (!$wid) { echo json_encode(['status'=>'error','message'=>'work_sys_id required']); exit; }
        $row = _tsFetch($pdo, $wid);
        if (!$row) { echo json_encode(['status'=>'error','message'=>'Not found']); exit; }
        ob_clean(); echo json_encode(['status'=>'success','data'=>$row], JSON_UNESCAPED_UNICODE); exit;
    }

    $workSysId = trim($body['work_sys_id'] ?? '');
    if (!$workSysId) { echo json_encode(['status'=>'error','message'=>'work_sys_id required']); exit; }

    switch ($action) {

        // ── init ──────────────────────────────────────────────
        case 'init': {
            $existing = _tsFetch($pdo, $workSysId);
            if ($existing) { ob_clean(); echo json_encode(['status'=>'success','message'=>'Already initialized','data'=>$existing]); exit; }
            $ids  = generateV2IDs($pdo, 'transport_services_module');
            $meta = buildMetaData(null, $userName);
            $pdo->prepare("INSERT INTO transport_services (uuid, sys_id, work_sys_id, ts_quotations, ts_confirmations, meta_data) VALUES (?,?,?,'[]','[]',?)")
                ->execute([$ids['uuid'], $ids['sys_id'], $workSysId, $meta]);
            ob_clean(); echo json_encode(['status'=>'success','sys_id'=>$ids['sys_id']]); break;
        }

        // ── save_quotation ────────────────────────────────────
        // Quotation = metadata + legs[]
        case 'save_quotation': {
            $row = _tsFetch($pdo, $workSysId);
            if (!$row) { echo json_encode(['status'=>'error','message'=>'Not initialized']); exit; }

            $quotations = $row['ts_quotations'];
            $existingId = trim($body['quotation_sys_id'] ?? '');

            // Legs validation — at least one leg
            $legs = $body['legs'] ?? [];
            if (empty($legs)) { echo json_encode(['status'=>'error','message'=>'কমপক্ষে একটা leg দিন']); exit; }

            // Compute totals from legs
            $totalNet  = array_sum(array_map(fn($l) => +(($l['net_rate'] ?? 0) * ($l['qty'] ?? 1)), $legs));
            $totalSell = array_sum(array_map(fn($l) => +(($l['sell_rate'] ?? $l['net_rate'] ?? 0) * ($l['qty'] ?? 1)), $legs));

            $q = [
                'sys_id'         => $existingId ?: _tsLocalId($quotations, 'Q'),
                'status'         => $existingId ? ($body['status'] ?? 'draft') : 'draft',
                'currency'       => strtoupper(trim($body['currency'] ?? 'BDT')),
                'markup_pct'     => (float)($body['markup_pct'] ?? 0),
                'total_net'      => $totalNet,
                'total_sell'     => $totalSell,
                'note'           => trim($body['note'] ?? ''),
                'legs'           => array_map(fn($l, $i) => [
                    'leg_no'            => $i + 1,
                    'from'              => trim($l['from']              ?? ''),
                    'to'                => trim($l['to']                ?? ''),
                    'date'              => trim($l['date']              ?? ''),
                    'time'              => trim($l['time']              ?? ''),
                    'vehicle_class'     => trim($l['vehicle_class']     ?? 'van'),
                    'transfer_type'     => trim($l['transfer_type']     ?? 'private'),
                    'pax'               => (int)($l['pax']              ?? 1),
                    'qty'               => (int)($l['qty']              ?? 1),
                    'price_basis'       => trim($l['price_basis']       ?? 'per_vehicle'),
                    'currency'          => strtoupper(trim($l['currency'] ?? $body['currency'] ?? 'BDT')),
                    'net_rate'          => (float)($l['net_rate']       ?? 0),
                    'markup_pct'        => (float)($l['markup_pct']     ?? $body['markup_pct'] ?? 0),
                    'sell_rate'         => (float)($l['sell_rate']       ?? 0),
                    'vendor_sys_id'     => trim($l['vendor_sys_id']     ?? ''),
                    'vendor_name'       => trim($l['vendor_name']        ?? ''),
                    'service_sys_id'    => trim($l['service_sys_id']    ?? ''),
                    'variant_sys_id'    => trim($l['variant_sys_id']    ?? ''),
                    'note'              => trim($l['note']               ?? ''),
                ], $legs, array_keys($legs)),
                'source_note_ids'=> $body['source_note_ids'] ?? [],
                'updated_at'     => date('Y-m-d H:i:s'),
            ];

            if ($existingId) {
                $found = false;
                foreach ($quotations as &$eq) {
                    if ($eq['sys_id'] === $existingId) {
                        $q['created_at'] = $eq['created_at'] ?? null;
                        $q['created_by'] = $eq['created_by'] ?? $userName;
                        $eq = $q; $found = true; break;
                    }
                } unset($eq);
                if (!$found) { echo json_encode(['status'=>'error','message'=>'Quotation not found']); exit; }
            } else {
                $q['created_at'] = date('Y-m-d H:i:s');
                $q['created_by'] = $userName;
                $quotations[] = $q;
            }

            if (!empty($q['source_note_ids'])) _tsMarkNotesUsed($pdo, $q['source_note_ids'], $q['sys_id']);

            _tsSave($pdo, $workSysId, $quotations, $row['ts_confirmations'], $row['meta_data'], $userName);
            ob_clean(); echo json_encode(['status'=>'success','quotation_sys_id'=>$q['sys_id']]); break;
        }

        // ── delete_quotation ──────────────────────────────────
        case 'delete_quotation': {
            $row = _tsFetch($pdo, $workSysId);
            if (!$row) { echo json_encode(['status'=>'error','message'=>'Not found']); exit; }
            $qId = trim($body['quotation_sys_id'] ?? '');
            $quotations = array_values(array_filter($row['ts_quotations'], fn($q) => $q['sys_id'] !== $qId));
            _tsSave($pdo, $workSysId, $quotations, $row['ts_confirmations'], $row['meta_data'], $userName);
            ob_clean(); echo json_encode(['status'=>'success']); break;
        }

        // ── add_to_confirmation ───────────────────────────────
        case 'add_to_confirmation': {
            $row = _tsFetch($pdo, $workSysId);
            if (!$row) { echo json_encode(['status'=>'error','message'=>'Not found']); exit; }
            $qId = trim($body['quotation_sys_id'] ?? '');
            $q   = null;
            foreach ($row['ts_quotations'] as &$quot) {
                if ($quot['sys_id'] === $qId) { $quot['status'] = 'moved_to_confirmation'; $q = $quot; break; }
            } unset($quot);
            if (!$q) { echo json_encode(['status'=>'error','message'=>'Quotation not found']); exit; }

            $confs = $row['ts_confirmations'];
            foreach ($confs as $c) {
                if ($c['quotation_sys_id'] === $qId && !in_array($c['status']??'', ['failed','cancelled'])) {
                    echo json_encode(['status'=>'error','message'=>'Already in confirmation']); exit;
                }
            }

            $cId  = _tsLocalId($confs, 'C');
            $conf = [
                'sys_id'          => $cId,
                'quotation_sys_id'=> $qId,
                'legs'            => $q['legs'] ?? [],
                'currency'        => $q['currency'] ?? 'BDT',
                'markup_pct'      => $q['markup_pct'] ?? 0,
                'total_net'       => $q['total_net']  ?? 0,
                'total_sell'      => $q['total_sell'] ?? 0,
                'status'          => 'pending',
                'note'            => '',
                'files_json'      => [],
                'added_at'        => date('Y-m-d H:i:s'),
                'added_by'        => $userName,
            ];
            $confs[] = $conf;

            _tsSave($pdo, $workSysId, $row['ts_quotations'], $confs, $row['meta_data'], $userName);
            ob_clean(); echo json_encode(['status'=>'success','conf_sys_id'=>$cId]); break;
        }

        // ── update_confirmation ───────────────────────────────
        case 'update_confirmation': {
            $row  = _tsFetch($pdo, $workSysId);
            if (!$row) { echo json_encode(['status'=>'error','message'=>'Not found']); exit; }
            $cId  = trim($body['conf_sys_id'] ?? '');
            $confs = $row['ts_confirmations'];
            foreach ($confs as &$c) {
                if ($c['sys_id'] === $cId) {
                    if (isset($body['note']))       $c['note']       = $body['note'];
                    if (isset($body['files_json'])) $c['files_json'] = $body['files_json'];
                    if (isset($body['legs']))       $c['legs']       = $body['legs']; // vendor assignment
                    $c['updated_at'] = date('Y-m-d H:i:s');
                    break;
                }
            } unset($c);
            _tsSave($pdo, $workSysId, $row['ts_quotations'], $confs, $row['meta_data'], $userName);
            ob_clean(); echo json_encode(['status'=>'success']); break;
        }

        // ── remove_confirmation ───────────────────────────────
        case 'remove_confirmation': {
            $row  = _tsFetch($pdo, $workSysId);
            if (!$row) { echo json_encode(['status'=>'error','message'=>'Not found']); exit; }
            $cId  = trim($body['conf_sys_id'] ?? '');
            $confs = array_values(array_filter($row['ts_confirmations'], fn($c) => $c['sys_id'] !== $cId));
            _tsSave($pdo, $workSysId, $row['ts_quotations'], $confs, $row['meta_data'], $userName);
            ob_clean(); echo json_encode(['status'=>'success']); break;
        }

        // ── update_conf_status ────────────────────────────────
        case 'update_conf_status': {
            $row  = _tsFetch($pdo, $workSysId);
            if (!$row) { echo json_encode(['status'=>'error','message'=>'Not found']); exit; }
            $cId  = trim($body['conf_sys_id'] ?? '');
            $newSt= trim($body['status']      ?? 'pending');
            $confs = $row['ts_confirmations'];
            foreach ($confs as &$c) {
                if ($c['sys_id'] === $cId) { $c['status'] = $newSt; $c['updated_at'] = date('Y-m-d H:i:s'); break; }
            } unset($c);
            _tsSave($pdo, $workSysId, $row['ts_quotations'], $confs, $row['meta_data'], $userName);
            ob_clean(); echo json_encode(['status'=>'success']); break;
        }

        // ── confirm_and_create_task ───────────────────────────
        case 'confirm_and_create_task': {
            $row  = _tsFetch($pdo, $workSysId);
            if (!$row) { echo json_encode(['status'=>'error','message'=>'Not found']); exit; }
            $cId  = trim($body['conf_sys_id'] ?? '');
            $conf = null;
            $confs = $row['ts_confirmations'];
            foreach ($confs as &$c) {
                if ($c['sys_id'] === $cId) {
                    $c['status']       = 'confirmed';
                    $c['confirmed_at'] = date('Y-m-d H:i:s');
                    $conf = $c; break;
                }
            } unset($c);
            if (!$conf) { echo json_encode(['status'=>'error','message'=>'Confirmation not found']); exit; }

            _tsSave($pdo, $workSysId, $row['ts_quotations'], $confs, $row['meta_data'], $userName);

            $taskSysId   = _tsAutoCreateTask($pdo, $conf, $workSysId, $userName);
            $taskCreated = $taskSysId && strpos($taskSysId, 'ERR:') !== 0 && $taskSysId !== 'NO_WORK';

            ob_clean(); echo json_encode([
                'status'       => 'success',
                'task_created' => $taskCreated,
                'auto_task_id' => $taskCreated ? $taskSysId : null,
                'work_sys_id'  => $workSysId,
            ]); break;
        }

        default:
            ob_clean(); http_response_code(400);
            echo json_encode(['status'=>'error','message'=>"Unknown action: $action"]);
    }

} catch (Throwable $e) {
    ob_clean(); http_response_code(500);
    echo json_encode(['status'=>'error','message'=>$e->getMessage()]);
}