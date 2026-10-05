<?php
/**
 * FILE PATH: /api/package-services/endpoints.php
 *
 * Package Service Module — Single endpoint, action-based routing
 * Mirrors hotel-services/endpoints.php pattern.
 *
 * GET  actions:
 *   ?action=get&work_sys_id=...        → full package_services record
 *   ?action=get_confirmed_services     → confirmed AT + Hotel from this work
 *
 * POST actions (JSON body):
 *   action=init                        → package_services row তৈরি করে
 *   action=save_quotation              → pk_quotations[] তে add
 *   action=update_quotation            → existing quotation update
 *   action=delete_quotation            → pk_quotations[] থেকে remove
 *   action=confirm_and_create_task     → quotation → confirmation + task create
 *   action=update_confirmation         → confirmed package update
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

// ── Fetch package_services row ─────────────────────────────────
function _pkFetch(PDO $pdo, string $workSysId): ?array
{
    $s = $pdo->prepare("SELECT * FROM package_services WHERE work_sys_id = ? LIMIT 1");
    $s->execute([$workSysId]);
    $row = $s->fetch(PDO::FETCH_ASSOC);
    if (!$row) return null;
    $row['pk_quotations']  = $row['pk_quotations']  ? (json_decode($row['pk_quotations'],  true) ?: []) : [];
    $row['pk_confirmation'] = $row['pk_confirmation'] ? (json_decode($row['pk_confirmation'], true) ?: null) : null;
    return $row;
}

// ── Save row back ──────────────────────────────────────────────
function _pkSave(PDO $pdo, string $workSysId, array $quotations, ?array $confirmation, ?string $existingMeta, string $userName): void
{
    $meta = buildMetaData($existingMeta, $userName);
    $pdo->prepare("
        UPDATE package_services
        SET pk_quotations=?, pk_confirmation=?, meta_data=?
        WHERE work_sys_id=?
    ")->execute([
        json_encode($quotations,   JSON_UNESCAPED_UNICODE),
        $confirmation ? json_encode($confirmation, JSON_UNESCAPED_UNICODE) : null,
        $meta,
        $workSysId,
    ]);
}

// ── Local ID generator (Q-001, C-001) ─────────────────────────
function _pkLocalId(array $existing, string $prefix): string
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

// ── Get confirmed AT + Hotel services for this work ────────────
function _pkGetConfirmedServices(PDO $pdo, string $workSysId): array
{
    $result = ['air_tickets' => [], 'hotels' => []];

    // Confirmed air tickets
    $atRow = $pdo->prepare("SELECT at_confirmations FROM air_tickets WHERE work_sys_id=? LIMIT 1");
    $atRow->execute([$workSysId]);
    $atData = $atRow->fetchColumn();
    if ($atData) {
        $confs = json_decode($atData, true) ?? [];
        foreach ($confs as $c) {
            if (($c['status'] ?? '') === 'confirmed') {
                $result['air_tickets'][] = [
                    'sys_id'  => $c['sys_id']  ?? '',
                    'label'   => ($c['segments'][0]['origin'] ?? '') . '-' . ($c['segments'][count($c['segments'])-1]['destination'] ?? ''),
                    'airline' => $c['airline']   ?? '',
                    'pnr'     => $c['pnr']       ?? '',
                    'depart'  => $c['depart_date'] ?? '',
                    'pax'     => $c['pax']        ?? 0,
                    'cost'    => $c['net_fare']   ?? 0,
                    'currency'=> $c['currency']   ?? 'BDT',
                ];
            }
        }
    }

    // Confirmed hotel services
    $htRow = $pdo->prepare("SELECT ht_confirmations FROM hotel_services WHERE work_sys_id=? LIMIT 1");
    $htRow->execute([$workSysId]);
    $htData = $htRow->fetchColumn();
    if ($htData) {
        $confs = json_decode($htData, true) ?? [];
        foreach ($confs as $c) {
            if (($c['status'] ?? '') === 'confirmed') {
                $nights = 0;
                if (!empty($c['check_in']) && !empty($c['check_out'])) {
                    try {
                        $d1 = new DateTime($c['check_in']);
                        $d2 = new DateTime($c['check_out']);
                        $nights = max(0, $d1->diff($d2)->days);
                    } catch(Exception $e) {}
                }
                $result['hotels'][] = [
                    'sys_id'     => $c['sys_id']     ?? '',
                    'hotel_name' => $c['hotel_name'] ?? '',
                    'city'       => $c['city']       ?? '',
                    'country'    => $c['country']    ?? '',
                    'check_in'   => $c['check_in']   ?? '',
                    'check_out'  => $c['check_out']  ?? '',
                    'nights'     => $nights,
                    'room_type'  => $c['room_type']  ?? '',
                    'rooms'      => $c['rooms']      ?? 1,
                    'meal_plan'  => $c['meal_plan']  ?? '',
                    'cost'       => ($c['net_rate'] ?? 0) * $nights * ($c['rooms'] ?? 1),
                    'currency'   => $c['currency']   ?? 'BDT',
                ];
            }
        }
    }

    return $result;
}

// ── Auto create task on confirmation ──────────────────────────
function _pkAutoCreateTask(PDO $pdo, array $conf, string $workSysId, string $userName): ?string
{
    try {
        $check = $pdo->prepare("SELECT sys_id FROM tasks WHERE confirmation_sys_id=? AND work_sys_id=? LIMIT 1");
        $check->execute([$conf['sys_id'], $workSysId]);
        if ($existing = $check->fetchColumn()) return $existing;

        $ws = $pdo->prepare("SELECT client_info FROM works WHERE sys_id=? LIMIT 1");
        $ws->execute([$workSysId]);
        $work = $ws->fetch(PDO::FETCH_ASSOC);
        if (!$work) return 'NO_WORK';

        $ci          = json_decode($work['client_info'], true) ?? [];
        $clientName  = $ci['name']   ?? 'Unknown';
        $clientSysId = $ci['sys_id'] ?? null;

        $sw = $pdo->prepare("SELECT sys_id FROM service_works WHERE work_sys_id=? AND service_slug='tour_package' LIMIT 1");
        $sw->execute([$workSysId]);
        $swSysId = $sw->fetchColumn() ?: null;

        $title    = $conf['title']   ?? '';
        $taskName = 'Package' . ($title ? ' — ' . $title : '');

        $ids  = generateV2IDs($pdo, 'tasks');
        $meta = buildMetaData(null, $userName);

        $pdo->prepare("
            INSERT INTO tasks (uuid, sys_id, service_work_sys_id, work_sys_id, client_sys_id, workname, client_name, status, service_slug, confirmation_sys_id, meta_data)
            VALUES (?, ?, ?, ?, ?, ?, ?, 'open', 'tour_package', ?, ?)
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

    // ── GET ────────────────────────────────────────────────────
    if ($method === 'GET') {
        $workSysId = trim($_GET['work_sys_id'] ?? '');
        if (!$workSysId) { echo json_encode(['status'=>'error','message'=>'work_sys_id required']); exit; }

        if ($action === 'get_confirmed_services') {
            $services = _pkGetConfirmedServices($pdo, $workSysId);
            ob_clean();
            echo json_encode(['status'=>'success','data'=>$services], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $row = _pkFetch($pdo, $workSysId);
        if (!$row) { echo json_encode(['status'=>'error','message'=>'Not found']); exit; }
        ob_clean();
        echo json_encode(['status'=>'success','data'=>$row], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // ── POST ───────────────────────────────────────────────────
    $workSysId = trim($body['work_sys_id'] ?? '');
    if (!$workSysId) { echo json_encode(['status'=>'error','message'=>'work_sys_id required']); exit; }

    switch ($action) {

        // ── init ──────────────────────────────────────────────
        case 'init': {
            $existing = _pkFetch($pdo, $workSysId);
            if ($existing) {
                ob_clean();
                echo json_encode(['status'=>'success','message'=>'Already initialized','data'=>$existing]);
                exit;
            }
            $ids  = generateV2IDs($pdo, 'package_services');
            $meta = buildMetaData(null, $userName);
            $pdo->prepare("
                INSERT INTO package_services (uuid, sys_id, work_sys_id, pk_quotations, pk_confirmation, meta_data)
                VALUES (?, ?, ?, '[]', NULL, ?)
            ")->execute([$ids['uuid'], $ids['sys_id'], $workSysId, $meta]);
            $row = _pkFetch($pdo, $workSysId);
            ob_clean();
            echo json_encode(['status'=>'success','data'=>$row], JSON_UNESCAPED_UNICODE);
            exit;
        }

        // ── save_quotation ────────────────────────────────────
        case 'save_quotation': {
            $row = _pkFetch($pdo, $workSysId);
            if (!$row) { echo json_encode(['status'=>'error','message'=>'Not initialized']); exit; }
            $quotations = $row['pk_quotations'];

            $newQ = [
                'sys_id'          => _pkLocalId($quotations, 'Q'),
                'title'           => trim($body['title']       ?? ''),
                'itinerary'       => trim($body['itinerary']   ?? ''),
                'destinations'    => $body['destinations']     ?? [],
                'duration_days'   => (int)($body['duration_days'] ?? 0),
                'pax'             => (int)($body['pax']        ?? 1),
                'services'        => $body['services']         ?? [],
                'pricing'         => $body['pricing']          ?? [],
                'source_note_ids' => $body['source_note_ids']  ?? [],
                'created_at'      => date('c'),
            ];

            $quotations[] = $newQ;
            _pkSave($pdo, $workSysId, $quotations, $row['pk_confirmation'], $row['meta_data'], $userName);
            ob_clean();
            echo json_encode(['status'=>'success','quotation'=>$newQ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        // ── update_quotation ──────────────────────────────────
        case 'update_quotation': {
            $qSysId = trim($body['q_sys_id'] ?? '');
            if (!$qSysId) { echo json_encode(['status'=>'error','message'=>'q_sys_id required']); exit; }
            $row = _pkFetch($pdo, $workSysId);
            if (!$row) { echo json_encode(['status'=>'error','message'=>'Not initialized']); exit; }
            $quotations = $row['pk_quotations'];
            $updated = false;
            foreach ($quotations as &$q) {
                if ($q['sys_id'] === $qSysId) {
                    if (isset($body['title']))         $q['title']         = trim($body['title']);
                    if (isset($body['itinerary']))     $q['itinerary']     = trim($body['itinerary']);
                    if (isset($body['destinations']))  $q['destinations']  = $body['destinations'];
                    if (isset($body['duration_days'])) $q['duration_days'] = (int)$body['duration_days'];
                    if (isset($body['pax']))           $q['pax']           = (int)$body['pax'];
                    if (isset($body['services']))      $q['services']      = $body['services'];
                    if (isset($body['pricing']))       $q['pricing']       = $body['pricing'];
                    $q['updated_at'] = date('c');
                    $updated = true;
                    break;
                }
            }
            unset($q);
            if (!$updated) { echo json_encode(['status'=>'error','message'=>'Quotation not found']); exit; }
            _pkSave($pdo, $workSysId, $quotations, $row['pk_confirmation'], $row['meta_data'], $userName);
            ob_clean();
            echo json_encode(['status'=>'success'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        // ── delete_quotation ──────────────────────────────────
        case 'delete_quotation': {
            $qSysId = trim($body['q_sys_id'] ?? '');
            if (!$qSysId) { echo json_encode(['status'=>'error','message'=>'q_sys_id required']); exit; }
            $row = _pkFetch($pdo, $workSysId);
            if (!$row) { echo json_encode(['status'=>'error','message'=>'Not initialized']); exit; }
            $quotations = array_values(array_filter($row['pk_quotations'], fn($q) => $q['sys_id'] !== $qSysId));
            _pkSave($pdo, $workSysId, $quotations, $row['pk_confirmation'], $row['meta_data'], $userName);
            ob_clean();
            echo json_encode(['status'=>'success'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        // ── confirm_and_create_task ───────────────────────────
        case 'confirm_and_create_task': {
            $qSysId = trim($body['q_sys_id'] ?? '');
            if (!$qSysId) { echo json_encode(['status'=>'error','message'=>'q_sys_id required']); exit; }
            $row = _pkFetch($pdo, $workSysId);
            if (!$row) { echo json_encode(['status'=>'error','message'=>'Not initialized']); exit; }

            // Find quotation
            $quotation = null;
            foreach ($row['pk_quotations'] as $q) {
                if ($q['sys_id'] === $qSysId) { $quotation = $q; break; }
            }
            if (!$quotation) { echo json_encode(['status'=>'error','message'=>'Quotation not found']); exit; }

            // Build confirmation
            $pricing    = $quotation['pricing'] ?? [];
            $conf = [
                'sys_id'           => 'C-001',
                'quotation_sys_id' => $qSysId,
                'title'            => $quotation['title']       ?? '',
                'itinerary'        => $quotation['itinerary']   ?? '',
                'destinations'     => $quotation['destinations'] ?? [],
                'duration_days'    => $quotation['duration_days'] ?? 0,
                'pax'              => $quotation['pax']          ?? 1,
                'services'         => $quotation['services']     ?? [],
                'pricing'          => $pricing,
                'currency'         => $pricing['currency']       ?? 'BDT',
                'gross_total'      => $pricing['gross_total']    ?? 0,
                'per_pax'          => $pricing['per_pax']        ?? 0,
                // These can be filled later via update_confirmation
                'client_paid'      => (float)($body['client_paid']   ?? 0),
                'vendor_ref'       => trim($body['vendor_ref']       ?? ''),
                'vendor_sys_id'    => trim($body['vendor_sys_id']    ?? ''),
                'vendor_cost'      => (float)($body['vendor_cost']   ?? 0),
                'note'             => trim($body['note']              ?? ''),
                'status'           => 'confirmed',
                'confirmed_at'     => date('c'),
            ];

            _pkSave($pdo, $workSysId, $row['pk_quotations'], $conf, $row['meta_data'], $userName);

            // Create task
            $taskSysId = _pkAutoCreateTask($pdo, $conf, $workSysId, $userName);

            ob_clean();
            echo json_encode([
                'status'        => 'success',
                'confirmation'  => $conf,
                'task_sys_id'   => $taskSysId,
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        // ── update_confirmation ───────────────────────────────
        case 'update_confirmation': {
            $row = _pkFetch($pdo, $workSysId);
            if (!$row || !$row['pk_confirmation']) { echo json_encode(['status'=>'error','message'=>'No confirmation']); exit; }
            $conf = $row['pk_confirmation'];

            $fields = ['title','itinerary','vendor_ref','vendor_sys_id','note','status'];
            foreach ($fields as $f) {
                if (isset($body[$f])) $conf[$f] = is_string($body[$f]) ? trim($body[$f]) : $body[$f];
            }
            $numFields = ['client_paid','vendor_cost','gross_total','per_pax','pax','duration_days'];
            foreach ($numFields as $f) {
                if (isset($body[$f])) $conf[$f] = (float)$body[$f];
            }
            if (isset($body['pricing']))     $conf['pricing']      = $body['pricing'];
            if (isset($body['services']))    $conf['services']     = $body['services'];
            if (isset($body['destinations'])) $conf['destinations'] = $body['destinations'];
            $conf['updated_at'] = date('c');

            _pkSave($pdo, $workSysId, $row['pk_quotations'], $conf, $row['meta_data'], $userName);
            ob_clean();
            echo json_encode(['status'=>'success','confirmation'=>$conf], JSON_UNESCAPED_UNICODE);
            exit;
        }

        default: {
            echo json_encode(['status'=>'error','message'=>"Unknown action: $action"]);
            exit;
        }
    }

} catch (Throwable $e) {
    ob_clean(); http_response_code(500);
    echo json_encode(['status'=>'error','message'=>$e->getMessage()]);
}