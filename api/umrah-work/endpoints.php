<?php
/**
 * FILE PATH: /api/umrah-work/endpoints.php
 *
 * Umrah Work Module — aggregates confirmed task data from all services
 * of a single work, plus manages the Umrah traveler group.
 *
 * GET:
 *   ?action=summary&work_sys_id=...
 *     → pulls confirmed flight/hotel/package data for this work
 *     → returns linked group info if exists
 *
 *   ?action=group&group_sys_id=...
 *     → full group data (travelers, leaders, NOCs)
 *
 * POST:
 *   action=create_group        → create/link traveler_groups row
 *   action=update_group        → update group name/description
 *   action=add_traveler        → add to group
 *   action=remove_traveler     → remove from group
 *   action=set_leaders         → set leader flags
 *   action=update_roaming_phone
 *   action=upload_noc          → individual NOC
 *   action=upload_auth_letter  → group auth letter
 */

ob_start();
session_start();
date_default_timezone_set('Asia/Dhaka');
ini_set('display_errors', 0);

set_error_handler(function($e,$s,$f,$l){ ob_clean(); http_response_code(500); echo json_encode(['status'=>'error','message'=>"$s in $f:$l"]); exit; });
register_shutdown_function(function(){ $e=error_get_last(); if($e&&in_array($e['type'],[E_ERROR,E_PARSE,E_CORE_ERROR,E_COMPILE_ERROR])){ ob_clean(); http_response_code(500); echo json_encode(['status'=>'error','message'=>"Fatal: {$e['message']}"]); } });

require_once '../../server/api_bootstrap.php';
require_once '../../server/db_connection.php';
require_once '../../server/sys_id_generator_v2.php';
require_once '../../server/generate_meta_data.php';

$method   = $_SERVER['REQUEST_METHOD'];
$action   = $_GET['action'] ?? '';
$userName = $_SESSION['user_name'] ?? 'system';
$body     = [];

if ($method === 'POST') {
    $body   = json_decode(file_get_contents('php://input'), true) ?? [];
    $action = $body['action'] ?? $action;
}

header('Content-Type: application/json; charset=utf-8');

// ── Helper: JSON decode column ────────────────────────────────
function _jd($v) { return $v ? (json_decode($v, true) ?: []) : []; }

// ── Pull confirmed service data for a work ────────────────────
function _pullWorkSummary(PDO $pdo, string $workSysId): array
{
    $summary = [
        'flights'   => [],   // from air_tickets (at_confirmations + linked at_bookings)
        'hotels'    => [],   // from hotel_services (ht_confirmations + linked ht_bookings)
        'itinerary' => [],   // from package service (future)
        'group'     => null, // linked traveler_groups row
    ];

    // ── Flights: air_tickets row for this work ─────────────────
    $atRow = $pdo->prepare("SELECT * FROM air_tickets WHERE work_sys_id=? LIMIT 1");
    $atRow->execute([$workSysId]);
    $at = $atRow->fetch(PDO::FETCH_ASSOC);
    if ($at) {
        $confs    = _jd($at['at_confirmations']);
        $bookings = _jd($at['at_bookings']);
        foreach ($confs as $c) {
            if (($c['status'] ?? '') !== 'confirmed') continue;
            // Find linked booking for segment data
            $bId = $c['booking_sys_id'] ?? null;
            $b   = $bId ? (array_values(array_filter($bookings, fn($x) => $x['sys_id'] === $bId))[0] ?? null) : null;
            $summary['flights'][] = [
                'conf_sys_id'   => $c['sys_id'] ?? '',
                'booking_sys_id'=> $bId ?? '',
                'airline'       => $b['airline']       ?? '',
                'pnr'           => $b['pnr']           ?? '',
                'ticket_nos'    => $b['ticket_nos']    ?? [],
                'segments'      => $b['segments_json'] ?? [],
                'type'          => $b['type']          ?? 'gds',
                'total_payable' => $b['total_payable'] ?? 0,
                'confirmed_at'  => $c['added_at']      ?? '',
            ];
        }
    }

    // ── Hotels: hotel_services row for this work ───────────────
    $hsRow = $pdo->prepare("SELECT * FROM hotel_services WHERE work_sys_id=? LIMIT 1");
    $hsRow->execute([$workSysId]);
    $hs = $hsRow->fetch(PDO::FETCH_ASSOC);
    if ($hs) {
        $confs    = _jd($hs['ht_confirmations']);
        $bookings = _jd($hs['ht_bookings']);
        foreach ($confs as $c) {
            if (($c['status'] ?? '') !== 'confirmed') continue;
            $bId = $c['booking_sys_id'] ?? null;
            $b   = $bId ? (array_values(array_filter($bookings, fn($x) => $x['sys_id'] === $bId))[0] ?? null) : null;
            $summary['hotels'][] = [
                'conf_sys_id'  => $c['sys_id']       ?? '',
                'hotel_name'   => $b['hotel_name']   ?? '',
                'city'         => $b['city']         ?? '',
                'country'      => $b['country']      ?? '',
                'star_rating'  => $b['star_rating']  ?? 0,
                'check_in'     => $b['check_in']     ?? '',
                'check_out'    => $b['check_out']    ?? '',
                'nights'       => $b['nights']       ?? 0,
                'room_type'    => $b['room_type']    ?? '',
                'meal_plan'    => $b['meal_plan']    ?? '',
                'rooms'        => $b['rooms']        ?? 1,
                'booking_ref'  => $b['booking_ref']  ?? '',
                'total_sell'   => $b['total_sell']   ?? 0,
                'confirmed_at' => $c['added_at']     ?? '',
            ];
        }
    }

    // ── Linked group ───────────────────────────────────────────
    $grp = $pdo->prepare("SELECT * FROM traveler_groups WHERE linked_work_id=? AND type='umrah' AND status='active' ORDER BY created_at DESC LIMIT 1");
    $grp->execute([$workSysId]);
    $g = $grp->fetch(PDO::FETCH_ASSOC);
    if ($g) {
        $summary['group'] = [
            'sys_id'     => $g['sys_id'],
            'group_name' => $g['group_name'],
            'status'     => $g['status'],
            'created_at' => $g['created_at'],
        ];
    }

    return $summary;
}

// ── Pull full group data ───────────────────────────────────────
function _pullGroupData(PDO $pdo, string $groupSysId): array
{
    $g = $pdo->prepare("SELECT * FROM traveler_groups WHERE sys_id=? LIMIT 1");
    $g->execute([$groupSysId]);
    $group = $g->fetch(PDO::FETCH_ASSOC);
    if (!$group) return [];

    // Members + traveler info
    $ms = $pdo->prepare("
        SELECT m.*, t.name, t.date_of_birth, t.passport_info, t.smb_path, t.type as pax_type
        FROM traveler_group_members m
        LEFT JOIN travelers t ON t.sys_id = m.traveler_id
        WHERE m.group_id=?
        ORDER BY m.is_leader DESC, m.joined_at ASC
    ");
    $ms->execute([$groupSysId]);
    $members = $ms->fetchAll(PDO::FETCH_ASSOC);

    foreach ($members as &$m) {
        $pi  = $m['passport_info'] ? (json_decode($m['passport_info'], true) ?: []) : [];
        $bio = [];
        if (is_array($pi)) {
            foreach ($pi as $page) {
                if (($page['page_type'] ?? '') === 'bio_page') { $bio = $page['bio_info'] ?? []; break; }
            }
        }
        $m['bio']          = $bio;
        $m['passport_no']  = $bio['passport_number'] ?? $bio['passport_no'] ?? '';
        $m['expiry']       = $bio['date_of_expiry']  ?? '';
        $m['given_name']   = $bio['given_names']     ?? $bio['given_name'] ?? '';
        $m['surname']      = $bio['surname']         ?? '';
        unset($m['passport_info']); // don't send raw JSON
    }
    unset($m);

    // NOCs
    $ns = $pdo->prepare("SELECT * FROM umrah_nocs WHERE group_sys_id=? ORDER BY uploaded_at DESC");
    $ns->execute([$groupSysId]);
    $nocs = $ns->fetchAll(PDO::FETCH_ASSOC);

    return [
        'group'   => $group,
        'members' => $members,
        'nocs'    => $nocs,
    ];
}

// ─────────────────────────────────────────────────────────────
try {

    // ── GET: summary ──────────────────────────────────────────
    if ($method === 'GET' && $action === 'summary') {
        $wid = trim($_GET['work_sys_id'] ?? '');
        if (!$wid) { echo json_encode(['status'=>'error','message'=>'work_sys_id required']); exit; }
        $summary = _pullWorkSummary($pdo, $wid);
        ob_clean(); echo json_encode(['status'=>'success','data'=>$summary], JSON_UNESCAPED_UNICODE); exit;
    }

    // ── GET: group ────────────────────────────────────────────
    if ($method === 'GET' && $action === 'group') {
        $gid = trim($_GET['group_sys_id'] ?? '');
        if (!$gid) { echo json_encode(['status'=>'error','message'=>'group_sys_id required']); exit; }
        $data = _pullGroupData($pdo, $gid);
        ob_clean(); echo json_encode(['status'=>'success','data'=>$data], JSON_UNESCAPED_UNICODE); exit;
    }

    // ── POST ──────────────────────────────────────────────────
    switch ($action) {

        // ── create_group ──────────────────────────────────────
        case 'create_group': {
            $workSysId  = trim($body['work_sys_id']  ?? '');
            $groupName  = trim($body['group_name']   ?? '');
            $description= trim($body['description']  ?? '');
            if (!$workSysId || !$groupName) { echo json_encode(['status'=>'error','message'=>'work_sys_id + group_name required']); exit; }

            // Check if group already linked
            $chk = $pdo->prepare("SELECT sys_id FROM traveler_groups WHERE linked_work_id=? AND type='umrah' AND status='active' LIMIT 1");
            $chk->execute([$workSysId]);
            if ($existing = $chk->fetchColumn()) { echo json_encode(['status'=>'success','group_sys_id'=>$existing,'message'=>'Already exists']); exit; }

            $ids  = generateV2IDs($pdo, 'traveler_groups');
            $meta = buildMetaData(null, $userName);
            $pdo->prepare("
                INSERT INTO traveler_groups (uuid, sys_id, group_name, type, status, linked_work_id, description, created_by, created_at, meta_data)
                VALUES (?, ?, ?, 'umrah', 'active', ?, ?, ?, NOW(), ?)
            ")->execute([$ids['uuid'], $ids['sys_id'], $groupName, $workSysId, $description ?: null, $userName, $meta]);

            ob_clean(); echo json_encode(['status'=>'success','group_sys_id'=>$ids['sys_id']]); break;
        }

        // ── update_group ──────────────────────────────────────
        case 'update_group': {
            $gSysId    = trim($body['group_sys_id'] ?? '');
            $groupName = trim($body['group_name']   ?? '');
            if (!$gSysId) { echo json_encode(['status'=>'error','message'=>'group_sys_id required']); exit; }
            $r = $pdo->prepare("SELECT meta_data FROM traveler_groups WHERE sys_id=? LIMIT 1");
            $r->execute([$gSysId]); $ex = $r->fetch();
            if (!$ex) { echo json_encode(['status'=>'error','message'=>'Group not found']); exit; }
            $meta = buildMetaData($ex['meta_data'], $userName);
            $fields = []; $params = [];
            if ($groupName)                  { $fields[]='group_name=?';    $params[]=$groupName; }
            if (isset($body['description'])) { $fields[]='description=?';   $params[]=trim($body['description']); }
            if ($fields) { $params[]=$gSysId; $params[]=$meta; $pdo->prepare("UPDATE traveler_groups SET ".implode(',',$fields).",meta_data=? WHERE sys_id=?")->execute(array_merge($params)); }
            ob_clean(); echo json_encode(['status'=>'success']); break;
        }

        // ── add_traveler ──────────────────────────────────────
        case 'add_traveler': {
            $gSysId    = trim($body['group_sys_id']    ?? '');
            $tSysId    = trim($body['traveler_sys_id'] ?? '');
            if (!$gSysId || !$tSysId) { echo json_encode(['status'=>'error','message'=>'group_sys_id + traveler_sys_id required']); exit; }
            $chk = $pdo->prepare("SELECT 1 FROM traveler_group_members WHERE group_id=? AND traveler_id=? LIMIT 1");
            $chk->execute([$gSysId, $tSysId]);
            if ($chk->fetchColumn()) { echo json_encode(['status'=>'error','message'=>'Already in group']); exit; }
            $ids = generateV2IDs($pdo, 'traveler_group_members');
            $pdo->prepare("INSERT INTO traveler_group_members (uuid, sys_id, group_id, traveler_id, is_leader, joined_at) VALUES (?,?,?,?,0,NOW())")
                ->execute([$ids['uuid'], $ids['sys_id'], $gSysId, $tSysId]);
            ob_clean(); echo json_encode(['status'=>'success']); break;
        }

        // ── remove_traveler ───────────────────────────────────
        case 'remove_traveler': {
            $gSysId = trim($body['group_sys_id']    ?? '');
            $tSysId = trim($body['traveler_sys_id'] ?? '');
            $pdo->prepare("DELETE FROM traveler_group_members WHERE group_id=? AND traveler_id=?")->execute([$gSysId, $tSysId]);
            ob_clean(); echo json_encode(['status'=>'success']); break;
        }

        // ── set_leaders ───────────────────────────────────────
        case 'set_leaders': {
            $gSysId  = trim($body['group_sys_id'] ?? '');
            $leaders = $body['traveler_sys_ids']  ?? [];
            if (!$gSysId) { echo json_encode(['status'=>'error','message'=>'group_sys_id required']); exit; }
            $pdo->prepare("UPDATE traveler_group_members SET is_leader=0 WHERE group_id=?")->execute([$gSysId]);
            if ($leaders) {
                $ph = implode(',', array_fill(0, count($leaders), '?'));
                $pdo->prepare("UPDATE traveler_group_members SET is_leader=1 WHERE group_id=? AND traveler_id IN ($ph)")
                    ->execute([$gSysId, ...$leaders]);
            }
            ob_clean(); echo json_encode(['status'=>'success']); break;
        }

        // ── update_roaming_phone ──────────────────────────────
        case 'update_roaming_phone': {
            $gSysId = trim($body['group_sys_id']    ?? '');
            $tSysId = trim($body['traveler_sys_id'] ?? '');
            $phone  = trim($body['roaming_phone']   ?? '');
            $pdo->prepare("UPDATE traveler_group_members SET roaming_phone=? WHERE group_id=? AND traveler_id=?")->execute([$phone, $gSysId, $tSysId]);
            ob_clean(); echo json_encode(['status'=>'success']); break;
        }

        // ── upload_noc ────────────────────────────────────────
        case 'upload_noc': {
            // Handled via multipart — re-route to umrah-groups/endpoints.php upload_noc action
            // For simplicity, delegate to existing endpoint
            require_once __DIR__ . '/noc-upload-helper.php';
            break;
        }

        default:
            ob_clean(); http_response_code(400);
            echo json_encode(['status'=>'error','message'=>"Unknown action: $action"]);
    }

} catch (Throwable $e) {
    ob_clean(); http_response_code(500);
    echo json_encode(['status'=>'error','message'=>$e->getMessage()]);
}