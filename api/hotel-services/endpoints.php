<?php
/**
 * FILE PATH: /api/hotel-services/endpoints.php
 *
 * Hotel Service Module — Single endpoint, action-based routing
 * Mirrors air-tickets/endpoints.php pattern exactly.
 *
 * GET  actions:
 *   ?action=get&work_sys_id=...   → full hotel_services record
 *
 * POST actions (JSON body):
 *   action=init                     → hotel_services row তৈরি করে
 *   action=save_quotation           → ht_quotations array তে add/update
 *   action=update_quotation         → existing quotation update
 *   action=delete_quotation         → ht_quotations থেকে remove
 *   action=move_to_booking          → quotation → booking copy
 *   action=save_booking             → ht_bookings array তে add/update
 *   action=update_booking           → existing booking update
 *   action=delete_booking           → ht_bookings থেকে remove
 *   action=add_to_confirmation      → booking → ht_confirmations push
 *   action=update_confirmation      → confirmation update (ticket nos, note, files)
 *   action=remove_confirmation      → confirmation remove
 *   action=update_conf_status       → pending ↔ confirmed toggle
 *   action=confirm_and_create_task  → confirmation confirm + task create
 */

ob_start();
session_start();
date_default_timezone_set('Asia/Dhaka');

ini_set('display_errors', 0);
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    ob_clean(); http_response_code(500);
    echo json_encode(['status'=>'error','message'=>"PHP Error: $errstr in $errfile:$errline"]);
    exit;
});
register_shutdown_function(function() {
    $e = error_get_last();
    if ($e && in_array($e['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
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
    $raw  = file_get_contents('php://input');
    $body = json_decode($raw, true) ?? [];
    $action = $body['action'] ?? $_POST['action'] ?? $action;
}

// ── Fetch hotel_services row ───────────────────────────────────
function _htFetch(PDO $pdo, string $workSysId): ?array
{
    $s = $pdo->prepare("SELECT * FROM hotel_services WHERE work_sys_id = ? LIMIT 1");
    $s->execute([$workSysId]);
    $row = $s->fetch(PDO::FETCH_ASSOC);
    if (!$row) return null;
    foreach (['ht_quotations','ht_bookings','ht_confirmations'] as $col) {
        $row[$col] = $row[$col] ? (json_decode($row[$col], true) ?: []) : [];
    }
    return $row;
}

// ── Save row back ──────────────────────────────────────────────
function _htSave(PDO $pdo, string $workSysId, array $quotations, array $bookings, array $confirmations, ?string $existingMeta, string $userName): void
{
    $meta = buildMetaData($existingMeta, $userName);
    $pdo->prepare("
        UPDATE hotel_services
        SET ht_quotations=?, ht_bookings=?, ht_confirmations=?, meta_data=?
        WHERE work_sys_id=?
    ")->execute([
        json_encode($quotations,    JSON_UNESCAPED_UNICODE),
        json_encode($bookings,      JSON_UNESCAPED_UNICODE),
        json_encode($confirmations, JSON_UNESCAPED_UNICODE),
        $meta,
        $workSysId,
    ]);
}

// ── Local ID generator (Q-001, B-001, C-001) ──────────────────
function _htLocalId(array $existing, string $prefix): string
{
    $max = 0;
    foreach ($existing as $item) {
        $id = $item['sys_id'] ?? '';
        if (strpos($id, $prefix . '-') === 0) {
            $num = (int)substr($id, strlen($prefix) + 1);
            if ($num > $max) $max = $num;
        }
    }
    return $prefix . '-' . str_pad($max + 1, 3, '0', STR_PAD_LEFT);
}

// ── Mark mindboard notes as used in quotation ─────────────────
function _htMarkNotesUsed(PDO $pdo, array $noteIds, string $qSysId): void
{
    if (empty($noteIds)) return;
    $ph   = implode(',', array_fill(0, count($noteIds), '?'));
    $stmt = $pdo->prepare("SELECT sys_id, meta_data FROM task_notes WHERE sys_id IN ($ph)");
    $stmt->execute($noteIds);
    $upd  = $pdo->prepare("UPDATE task_notes SET meta_data=? WHERE sys_id=?");
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $r) {
        $meta = $r['meta_data'] ? (json_decode($r['meta_data'], true) ?: []) : [];
        $used = $meta['used_in_quotations'] ?? [];
        if (!in_array($qSysId, $used, true)) {
            $used[] = $qSysId;
            $meta['used_in_quotations'] = $used;
            $upd->execute([json_encode($meta, JSON_UNESCAPED_UNICODE), $r['sys_id']]);
        }
    }
}

// ── Auto-create task on confirmation ──────────────────────────
function _htAutoCreateTask(PDO $pdo, array $conf, string $workSysId, string $userName): ?string
{
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

        $sw = $pdo->prepare("SELECT sys_id FROM service_works WHERE work_sys_id=? AND service_slug='hotel' LIMIT 1");
        $sw->execute([$workSysId]);
        $swSysId = $sw->fetchColumn() ?: null;

        $hotelName  = $conf['hotel_name'] ?? '';
        $checkIn    = $conf['check_in']   ?? '';
        $taskName   = 'Hotel' . ($hotelName ? ' — ' . $hotelName : '') . ($checkIn ? ' — ' . $checkIn : '');

        $ids  = generateV2IDs($pdo, 'tasks');
        $meta = buildMetaData(null, $userName);

        $pdo->prepare("
            INSERT INTO tasks (uuid, sys_id, service_work_sys_id, work_sys_id, client_sys_id, workname, client_name, status, service_slug, confirmation_sys_id, meta_data)
            VALUES (?, ?, ?, ?, ?, ?, ?, 'open', 'hotel', ?, ?)
        ")->execute([
            $ids['uuid'], $ids['sys_id'],
            $swSysId, $workSysId, $clientSysId,
            $taskName, $clientName,
            $conf['sys_id'], $meta,
        ]);

        return $ids['sys_id'];
    } catch (Throwable $e) {
        return 'ERR:' . $e->getMessage();
    }
}

// ─────────────────────────────────────────────────────────────
// ── Routing ───────────────────────────────────────────────────
// ─────────────────────────────────────────────────────────────

header('Content-Type: application/json; charset=utf-8');

try {

    // ── GET: fetch full hotel_services record ──────────────────
    if ($method === 'GET') {
        $workSysId = trim($_GET['work_sys_id'] ?? '');
        if (!$workSysId) { echo json_encode(['status'=>'error','message'=>'work_sys_id required']); exit; }
        $row = _htFetch($pdo, $workSysId);
        if (!$row) { echo json_encode(['status'=>'error','message'=>'Not found']); exit; }
        ob_clean();
        echo json_encode(['status'=>'success','data'=>$row], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // ── POST ──────────────────────────────────────────────────
    $workSysId = trim($body['work_sys_id'] ?? '');
    if (!$workSysId) { echo json_encode(['status'=>'error','message'=>'work_sys_id required']); exit; }

    switch ($action) {

        // ── init ──────────────────────────────────────────────
        case 'init': {
            $existing = _htFetch($pdo, $workSysId);
            if ($existing) {
                ob_clean();
                echo json_encode(['status'=>'success','message'=>'Already initialized','data'=>$existing]);
                exit;
            }
            $ids  = generateV2IDs($pdo, 'hotel_services');
            $meta = buildMetaData(null, $userName);
            $pdo->prepare("
                INSERT INTO hotel_services (uuid, sys_id, work_sys_id, ht_quotations, ht_bookings, ht_confirmations, meta_data)
                VALUES (?, ?, ?, '[]', '[]', '[]', ?)
            ")->execute([$ids['uuid'], $ids['sys_id'], $workSysId, $meta]);
            ob_clean();
            echo json_encode(['status'=>'success','message'=>'Hotel service initialized','sys_id'=>$ids['sys_id']]);
            break;
        }

        // ── save_quotation ────────────────────────────────────
        case 'save_quotation': {
            $row = _htFetch($pdo, $workSysId);
            if (!$row) { echo json_encode(['status'=>'error','message'=>'Not initialized']); exit; }

            $quotations = $row['ht_quotations'];
            $existingId = trim($body['quotation_sys_id'] ?? '');

            // Fields
            $q = [
                'sys_id'          => $existingId ?: _htLocalId($quotations, 'Q'),
                'status'          => $existingId ? ($body['status'] ?? 'draft') : 'draft',
                'hotel_sys_id'    => trim($body['hotel_sys_id']    ?? ''),
                'hotel_name'      => trim($body['hotel_name']       ?? ''),
                'city'            => trim($body['city']             ?? ''),
                'country'         => trim($body['country']          ?? ''),
                'star_rating'     => (int)($body['star_rating']     ?? 0),
                'check_in'        => trim($body['check_in']         ?? ''),
                'check_out'       => trim($body['check_out']        ?? ''),
                'nights'          => (int)($body['nights']          ?? 0),
                'room_type'       => trim($body['room_type']        ?? ''),
                'room_type_sys_id'=> trim($body['room_type_sys_id'] ?? ''),
                'bed_config'      => trim($body['bed_config']       ?? ''),
                'size_sqm'        => $body['size_sqm'] !== '' ? (int)($body['size_sqm'] ?? 0) : null,
                'max_occupancy'   => (int)($body['max_occupancy']   ?? 2),
                'meal_plan'       => trim($body['meal_plan']        ?? 'bb'),
                'rooms'           => (int)($body['rooms']           ?? 1),
                'currency'        => strtoupper(trim($body['currency'] ?? 'BDT')),
                'net_rate'        => (float)($body['net_rate']      ?? 0),
                'markup_pct'      => (float)($body['markup_pct']    ?? 0),
                'sell_rate'       => (float)($body['sell_rate']     ?? 0),
                'total_net'       => (float)($body['total_net']     ?? 0),
                'total_sell'      => (float)($body['total_sell']    ?? 0),
                'note'            => trim($body['note']             ?? ''),
                'source_note_ids' => $body['source_note_ids'] ?? [],
                'created_at'      => $existingId ? null : date('Y-m-d H:i:s'),
                'updated_at'      => date('Y-m-d H:i:s'),
                'created_by'      => $existingId ? null : $userName,
            ];
            if ($existingId) {
                // update existing
                $found = false;
                foreach ($quotations as &$existing) {
                    if ($existing['sys_id'] === $existingId) {
                        $q['created_at'] = $existing['created_at'] ?? null;
                        $q['created_by'] = $existing['created_by'] ?? $userName;
                        $existing = $q; $found = true; break;
                    }
                }
                unset($existing);
                if (!$found) { echo json_encode(['status'=>'error','message'=>'Quotation not found']); exit; }
            } else {
                $quotations[] = $q;
            }

            // Mark notes as used
            if (!empty($q['source_note_ids'])) _htMarkNotesUsed($pdo, $q['source_note_ids'], $q['sys_id']);

            _htSave($pdo, $workSysId, $quotations, $row['ht_bookings'], $row['ht_confirmations'], $row['meta_data'], $userName);
            ob_clean();
            echo json_encode(['status'=>'success','quotation_sys_id'=>$q['sys_id']]);
            break;
        }

        // ── delete_quotation ──────────────────────────────────
        case 'delete_quotation': {
            $row = _htFetch($pdo, $workSysId);
            if (!$row) { echo json_encode(['status'=>'error','message'=>'Not found']); exit; }
            $qId = trim($body['quotation_sys_id'] ?? '');
            $quotations = array_values(array_filter($row['ht_quotations'], fn($q) => $q['sys_id'] !== $qId));
            _htSave($pdo, $workSysId, $quotations, $row['ht_bookings'], $row['ht_confirmations'], $row['meta_data'], $userName);
            ob_clean();
            echo json_encode(['status'=>'success']);
            break;
        }

        // ── move_to_booking ───────────────────────────────────
        case 'move_to_booking': {
            $row = _htFetch($pdo, $workSysId);
            if (!$row) { echo json_encode(['status'=>'error','message'=>'Not found']); exit; }
            $qId = trim($body['quotation_sys_id'] ?? '');
            $q   = null;
            foreach ($row['ht_quotations'] as &$quot) {
                if ($quot['sys_id'] === $qId) { $quot['status'] = 'moved_to_booking'; $q = $quot; break; }
            }
            unset($quot);
            if (!$q) { echo json_encode(['status'=>'error','message'=>'Quotation not found']); exit; }

            $bookings = $row['ht_bookings'];
            $bId      = _htLocalId($bookings, 'B');
            $booking  = array_merge($q, [
                'sys_id'          => $bId,
                'quotation_sys_id'=> $qId,
                'status'          => 'tentative',
                'booking_ref'     => '',
                'pcn'             => '',
                'hcn'             => '',
                'ticket_nos'      => [],
                'note'            => '',
                'files_json'      => [],
                'created_at'      => date('Y-m-d H:i:s'),
                'created_by'      => $userName,
            ]);
            $bookings[] = $booking;

            _htSave($pdo, $workSysId, $row['ht_quotations'], $bookings, $row['ht_confirmations'], $row['meta_data'], $userName);
            ob_clean();
            echo json_encode(['status'=>'success','booking_sys_id'=>$bId]);
            break;
        }

        // ── save_booking ──────────────────────────────────────
        case 'save_booking': {
            $row = _htFetch($pdo, $workSysId);
            if (!$row) { echo json_encode(['status'=>'error','message'=>'Not found']); exit; }
            $bookings   = $row['ht_bookings'];
            $existingId = trim($body['booking_sys_id'] ?? '');

            $b = [
                'sys_id'         => $existingId ?: _htLocalId($bookings, 'B'),
                'status'         => $body['status']       ?? 'tentative',
                'hotel_sys_id'   => trim($body['hotel_sys_id']    ?? ''),
                'hotel_name'     => trim($body['hotel_name']       ?? ''),
                'city'           => trim($body['city']             ?? ''),
                'star_rating'    => (int)($body['star_rating']     ?? 0),
                'check_in'       => trim($body['check_in']         ?? ''),
                'check_out'      => trim($body['check_out']        ?? ''),
                'nights'         => (int)($body['nights']          ?? 0),
                'room_type'      => trim($body['room_type']        ?? ''),
                'meal_plan'      => trim($body['meal_plan']        ?? 'bb'),
                'rooms'          => (int)($body['rooms']           ?? 1),
                'currency'       => strtoupper(trim($body['currency'] ?? 'BDT')),
                'net_rate'       => (float)($body['net_rate']      ?? 0),
                'markup_pct'     => (float)($body['markup_pct']    ?? 0),
                'sell_rate'      => (float)($body['sell_rate']     ?? 0),
                'total_net'      => (float)($body['total_net']     ?? 0),
                'total_sell'     => (float)($body['total_sell']    ?? 0),
                'booking_ref'    => trim($body['booking_ref']      ?? ''),
                'pcn'            => trim($body['pcn']              ?? ''),
                'hcn'            => trim($body['hcn']              ?? ''),
                'ticket_nos'     => $body['ticket_nos']            ?? [],
                'note'           => trim($body['note']             ?? ''),
                'files_json'     => $body['files_json']            ?? [],
                'quotation_sys_id'=> trim($body['quotation_sys_id'] ?? ''),
                'updated_at'     => date('Y-m-d H:i:s'),
            ];

            if ($existingId) {
                $found = false;
                foreach ($bookings as &$bk) {
                    if ($bk['sys_id'] === $existingId) { $b['created_at'] = $bk['created_at'] ?? null; $bk = $b; $found = true; break; }
                }
                unset($bk);
                if (!$found) { echo json_encode(['status'=>'error','message'=>'Booking not found']); exit; }
            } else {
                $b['created_at'] = date('Y-m-d H:i:s');
                $b['created_by'] = $userName;
                $bookings[] = $b;
            }

            _htSave($pdo, $workSysId, $row['ht_quotations'], $bookings, $row['ht_confirmations'], $row['meta_data'], $userName);
            ob_clean();
            echo json_encode(['status'=>'success','booking_sys_id'=>$b['sys_id']]);
            break;
        }

        // ── delete_booking ────────────────────────────────────
        case 'delete_booking': {
            $row = _htFetch($pdo, $workSysId);
            if (!$row) { echo json_encode(['status'=>'error','message'=>'Not found']); exit; }
            $bId = trim($body['booking_sys_id'] ?? '');
            $bookings = array_values(array_filter($row['ht_bookings'], fn($b) => $b['sys_id'] !== $bId));
            _htSave($pdo, $workSysId, $row['ht_quotations'], $bookings, $row['ht_confirmations'], $row['meta_data'], $userName);
            ob_clean();
            echo json_encode(['status'=>'success']);
            break;
        }

        // ── add_to_confirmation ───────────────────────────────
        case 'add_to_confirmation': {
            $row = _htFetch($pdo, $workSysId);
            if (!$row) { echo json_encode(['status'=>'error','message'=>'Not found']); exit; }
            $bId = trim($body['booking_sys_id'] ?? '');
            $b   = null;
            foreach ($row['ht_bookings'] as $bk) { if ($bk['sys_id'] === $bId) { $b = $bk; break; } }
            if (!$b) { echo json_encode(['status'=>'error','message'=>'Booking not found']); exit; }

            $confs = $row['ht_confirmations'];
            // Already in confirmation?
            foreach ($confs as $c) {
                if ($c['booking_sys_id'] === $bId && !in_array($c['status'] ?? '', ['failed','cancelled'])) {
                    echo json_encode(['status'=>'error','message'=>'Already in confirmation']); exit;
                }
            }
            $cId  = _htLocalId($confs, 'C');
            $conf = [
                'sys_id'         => $cId,
                'booking_sys_id' => $bId,
                'hotel_name'     => $b['hotel_name'] ?? '',
                'check_in'       => $b['check_in']   ?? '',
                'check_out'      => $b['check_out']  ?? '',
                'status'         => 'pending',
                'ticket_nos'     => [],
                'note'           => '',
                'files_json'     => [],
                'added_at'       => date('Y-m-d H:i:s'),
                'added_by'       => $userName,
            ];
            $confs[] = $conf;

            _htSave($pdo, $workSysId, $row['ht_quotations'], $row['ht_bookings'], $confs, $row['meta_data'], $userName);
            ob_clean();
            echo json_encode(['status'=>'success','conf_sys_id'=>$cId]);
            break;
        }

        // ── update_confirmation ───────────────────────────────
        case 'update_confirmation': {
            $row = _htFetch($pdo, $workSysId);
            if (!$row) { echo json_encode(['status'=>'error','message'=>'Not found']); exit; }
            $cId  = trim($body['conf_sys_id'] ?? '');
            $confs = $row['ht_confirmations'];
            foreach ($confs as &$c) {
                if ($c['sys_id'] === $cId) {
                    $c['ticket_nos']  = $body['ticket_nos']  ?? $c['ticket_nos'];
                    $c['note']        = $body['note']        ?? $c['note'];
                    $c['files_json']  = $body['files_json']  ?? $c['files_json'];
                    $c['updated_at']  = date('Y-m-d H:i:s');
                    break;
                }
            }
            unset($c);
            _htSave($pdo, $workSysId, $row['ht_quotations'], $row['ht_bookings'], $confs, $row['meta_data'], $userName);
            ob_clean();
            echo json_encode(['status'=>'success']);
            break;
        }

        // ── remove_confirmation ───────────────────────────────
        case 'remove_confirmation': {
            $row = _htFetch($pdo, $workSysId);
            if (!$row) { echo json_encode(['status'=>'error','message'=>'Not found']); exit; }
            $cId  = trim($body['conf_sys_id'] ?? '');
            $confs = array_values(array_filter($row['ht_confirmations'], fn($c) => $c['sys_id'] !== $cId));
            _htSave($pdo, $workSysId, $row['ht_quotations'], $row['ht_bookings'], $confs, $row['meta_data'], $userName);
            ob_clean();
            echo json_encode(['status'=>'success']);
            break;
        }

        // ── update_conf_status ────────────────────────────────
        case 'update_conf_status': {
            $row   = _htFetch($pdo, $workSysId);
            if (!$row) { echo json_encode(['status'=>'error','message'=>'Not found']); exit; }
            $cId   = trim($body['conf_sys_id'] ?? '');
            $newSt = trim($body['status']      ?? 'pending');
            $confs = $row['ht_confirmations'];
            foreach ($confs as &$c) {
                if ($c['sys_id'] === $cId) { $c['status'] = $newSt; $c['updated_at'] = date('Y-m-d H:i:s'); break; }
            }
            unset($c);
            _htSave($pdo, $workSysId, $row['ht_quotations'], $row['ht_bookings'], $confs, $row['meta_data'], $userName);
            ob_clean();
            echo json_encode(['status'=>'success']);
            break;
        }

        // ── confirm_and_create_task ───────────────────────────
        case 'confirm_and_create_task': {
            $row  = _htFetch($pdo, $workSysId);
            if (!$row) { echo json_encode(['status'=>'error','message'=>'Not found']); exit; }
            $cId  = trim($body['conf_sys_id'] ?? '');
            $conf = null;
            $confs = $row['ht_confirmations'];
            foreach ($confs as &$c) {
                if ($c['sys_id'] === $cId) { $c['status'] = 'confirmed'; $c['confirmed_at'] = date('Y-m-d H:i:s'); $conf = $c; break; }
            }
            unset($c);
            if (!$conf) { echo json_encode(['status'=>'error','message'=>'Confirmation not found']); exit; }

            _htSave($pdo, $workSysId, $row['ht_quotations'], $row['ht_bookings'], $confs, $row['meta_data'], $userName);

            $taskSysId   = _htAutoCreateTask($pdo, $conf, $workSysId, $userName);
            $taskCreated = $taskSysId && strpos($taskSysId, 'ERR:') !== 0 && $taskSysId !== 'NO_WORK';

            ob_clean();
            echo json_encode([
                'status'       => 'success',
                'task_created' => $taskCreated,
                'auto_task_id' => $taskCreated ? $taskSysId : null,
                'work_sys_id'  => $workSysId,
            ]);
            break;
        }

        default:
            ob_clean();
            http_response_code(400);
            echo json_encode(['status'=>'error','message'=>"Unknown action: $action"]);
    }

} catch (Throwable $e) {
    ob_clean();
    http_response_code(500);
    echo json_encode(['status'=>'error','message'=>$e->getMessage()]);
}