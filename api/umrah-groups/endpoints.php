<?php
// FILE PATH: /api/umrah-groups/endpoints.php

declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');

session_start();
require_once __DIR__ . '/../../server/db_connection.php';
require_once __DIR__ . '/../../server/sys_id_generator_v2.php';
require_once __DIR__ . '/../../server/generate_meta_data.php';
require_once __DIR__ . '/../../server/live_storage.php';

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? ($method === 'POST' ? (json_decode(file_get_contents('php://input'),true)['action']??'') : '');
$body   = $method === 'POST' ? (json_decode(file_get_contents('php://input'),true) ?? []) : [];

$userName = $_SESSION['user_name'] ?? 'system';

try {
    ob_clean();
    switch ($action) {

        // ── LIST GROUPS ──────────────────────────────────────────────
        case 'list': {
            $stmt = $pdo->query("
                SELECT g.*,
                       COUNT(DISTINCT m.traveler_id) AS traveler_count
                FROM   traveler_groups g
                LEFT JOIN traveler_group_members m ON m.group_id = g.sys_id
                WHERE  g.type = 'umrah' OR g.type IS NULL
                GROUP  BY g.id
                ORDER  BY g.created_at DESC
            ");
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            foreach ($rows as &$r) {
                $r['segments']  = $r['segments']  ? json_decode($r['segments'],  true) : [];
                $r['hotels']    = $r['hotels']    ? json_decode($r['hotels'],    true) : [];
                $r['itinerary'] = $r['itinerary'] ? json_decode($r['itinerary'], true) : [];
            }
            echo json_encode(['status'=>'success','data'=>$rows]);
            break;
        }

        // ── GET ONE GROUP ────────────────────────────────────────────
        case 'get': {
            $sysId = $_GET['sys_id'] ?? '';
            if (!$sysId) throw new Exception('sys_id required');
            $s = $pdo->prepare("SELECT * FROM traveler_groups WHERE sys_id = ? LIMIT 1");
            $s->execute([$sysId]);
            $row = $s->fetch(PDO::FETCH_ASSOC);
            if (!$row) throw new Exception('Group not found');
            $row['segments']  = $row['segments']  ? json_decode($row['segments'],  true) : [];
            $row['hotels']    = $row['hotels']    ? json_decode($row['hotels'],    true) : [];
            $row['itinerary'] = $row['itinerary'] ? json_decode($row['itinerary'], true) : [];
            echo json_encode(['status'=>'success','data'=>$row]);
            break;
        }

        // ── CREATE GROUP ─────────────────────────────────────────────
        case 'create': {
            $name = trim($body['group_name'] ?? '');
            if (!$name) throw new Exception('group_name required');

            $ids = generateV2IDs($pdo, 'traveler_groups');
            $meta = buildMetaData(null, $userName);

            $pdo->prepare("
                INSERT INTO traveler_groups
                    (uuid, sys_id, group_name, type, segments, hotels, itinerary, status, linked_work_id, description, created_by, created_at, meta_data)
                VALUES (?,?,?,?,?,?,?,?,?,?,?,NOW(),?)
            ")->execute([
                $ids['uuid'], $ids['sys_id'], $name,
                'umrah',
                json_encode($body['segments']  ?? [], JSON_UNESCAPED_UNICODE),
                json_encode($body['hotels']    ?? [], JSON_UNESCAPED_UNICODE),
                json_encode($body['itinerary'] ?? [], JSON_UNESCAPED_UNICODE),
                $body['status'] ?? 'active',
                $body['linked_work_id'] ?? null,
                $body['description'] ?? null,
                $userName,
                $meta,
            ]);
            echo json_encode(['status'=>'success','sys_id'=>$ids['sys_id'],'message'=>'Group created']);
            break;
        }

        // ── UPDATE GROUP ─────────────────────────────────────────────
        case 'update': {
            $sysId = $body['sys_id'] ?? '';
            if (!$sysId) throw new Exception('sys_id required');
            $sets = []; $params = [];
            if (isset($body['group_name']))  { $sets[] = 'group_name=?';  $params[] = $body['group_name']; }
            if (isset($body['status']))      { $sets[] = 'status=?';      $params[] = $body['status']; }
            if (isset($body['segments']))    { $sets[] = 'segments=?';    $params[] = json_encode($body['segments'], JSON_UNESCAPED_UNICODE); }
            if (isset($body['hotels']))      { $sets[] = 'hotels=?';      $params[] = json_encode($body['hotels'], JSON_UNESCAPED_UNICODE); }
            if (isset($body['itinerary']))   { $sets[] = 'itinerary=?';   $params[] = json_encode($body['itinerary'], JSON_UNESCAPED_UNICODE); }
            if (!$sets) throw new Exception('Nothing to update');
            $params[] = $sysId;
            $pdo->prepare("UPDATE traveler_groups SET " . implode(',',$sets) . " WHERE sys_id=?")->execute($params);
            echo json_encode(['status'=>'success','message'=>'Updated']);
            break;
        }

        // ── LIST TRAVELERS IN GROUP ──────────────────────────────────
        case 'list_travelers': {
            $groupSysId = $_GET['group_sys_id'] ?? $body['group_sys_id'] ?? '';
            if (!$groupSysId) throw new Exception('group_sys_id required');

            // Get group name too
            $gRow = $pdo->prepare("SELECT group_name FROM traveler_groups WHERE sys_id=? LIMIT 1");
            $gRow->execute([$groupSysId]);
            $groupName = $gRow->fetchColumn() ?: '';

            $s = $pdo->prepare("
                SELECT m.traveler_id AS traveler_sys_id,
                       m.is_leader, m.roaming_phone,
                       t.name       AS traveler_name,
                       t.passport_no, t.date_of_birth
                FROM   traveler_group_members m
                JOIN   travelers t ON t.sys_id = m.traveler_id
                WHERE  m.group_id = ?
                ORDER  BY m.is_leader DESC, t.name ASC
            ");
            $s->execute([$groupSysId]);
            $rows = $s->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode(['status'=>'success','data'=>$rows,'group_name'=>$groupName]);
            break;
        }

        // ── ADD TRAVELER TO GROUP ────────────────────────────────────
        case 'add_traveler': {
            $groupSysId   = $body['group_sys_id']   ?? '';
            $travelerSysId = $body['traveler_sys_id'] ?? '';
            if (!$groupSysId || !$travelerSysId) throw new Exception('group_sys_id and traveler_sys_id required');

            // Duplicate check
            $chk = $pdo->prepare("SELECT 1 FROM traveler_group_members WHERE group_id=? AND traveler_id=? LIMIT 1");
            $chk->execute([$groupSysId, $travelerSysId]);
            if ($chk->fetchColumn()) throw new Exception('Traveler already in group');

            $ids = generateV2IDs($pdo, 'traveler_group_members');
            $pdo->prepare("
                INSERT INTO traveler_group_members (uuid, sys_id, group_id, traveler_id, is_leader, roaming_phone, joined_at)
                VALUES (?,?,?,?,0,?,NOW())
            ")->execute([$ids['uuid'], $ids['sys_id'], $groupSysId, $travelerSysId, $body['roaming_phone'] ?? null]);
            echo json_encode(['status'=>'success','message'=>'Traveler added']);
            break;
        }

        // ── UPDATE ROAMING PHONE ─────────────────────────────────────
        case 'update_roaming_phone': {
            $groupSysId    = $body['group_sys_id']    ?? '';
            $travelerSysId = $body['traveler_sys_id'] ?? '';
            $phone         = trim($body['roaming_phone'] ?? '');
            if (!$groupSysId || !$travelerSysId) throw new Exception('group_sys_id and traveler_sys_id required');
            $pdo->prepare("UPDATE traveler_group_members SET roaming_phone=? WHERE group_id=? AND traveler_id=?")
                ->execute([$phone ?: null, $groupSysId, $travelerSysId]);
            echo json_encode(['status'=>'success','message'=>'Phone updated']);
            break;
        }

        // ── REMOVE TRAVELER ──────────────────────────────────────────
        case 'remove_traveler': {
            $groupSysId    = $body['group_sys_id']    ?? '';
            $travelerSysId = $body['traveler_sys_id'] ?? '';
            $pdo->prepare("DELETE FROM traveler_group_members WHERE group_id=? AND traveler_id=?")->execute([$groupSysId, $travelerSysId]);
            echo json_encode(['status'=>'success','message'=>'Removed']);
            break;
        }

        // ── SET LEADERS ──────────────────────────────────────────────
        case 'set_leaders': {
            $groupSysId  = $body['group_sys_id']  ?? '';
            $leaderSysIds = $body['leader_sys_ids'] ?? [];
            if (!$groupSysId) throw new Exception('group_sys_id required');
            if (count($leaderSysIds) > 3) throw new Exception('Maximum 3 leaders allowed');

            // Reset all
            $pdo->prepare("UPDATE traveler_group_members SET is_leader=0 WHERE group_id=?")->execute([$groupSysId]);
            // Set selected
            if ($leaderSysIds) {
                $ph = implode(',', array_fill(0, count($leaderSysIds), '?'));
                $pdo->prepare("UPDATE traveler_group_members SET is_leader=1 WHERE group_id=? AND traveler_id IN ($ph)")
                    ->execute(array_merge([$groupSysId], $leaderSysIds));
            }
            echo json_encode(['status'=>'success','message'=>'Leaders updated']);
            break;
        }

        // ── UPLOAD NOC ───────────────────────────────────────────────
        case 'upload_noc': {
            $groupSysId    = $_POST['group_sys_id']    ?? '';
            $travelerSysIds = json_decode($_POST['traveler_sys_ids'] ?? '[]', true);
            if (!$groupSysId) throw new Exception('group_sys_id required');
            if (empty($travelerSysIds)) throw new Exception('Select at least one traveler');
            if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) throw new Exception('No file uploaded');

            // Save file via SMB
            $file = $_FILES['file'];
            $ext  = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $allowed = ['pdf','jpg','jpeg','png'];
            if (!in_array($ext, $allowed)) throw new Exception('Invalid file type');

            $fileName = 'NOC_' . $groupSysId . '_' . uniqid() . '.' . $ext;
            $tmpPath  = $file['tmp_name'];

            // Store via SMB (same pattern as other uploads)
            $smbPath = null;
            try {
                $omv = new OMV_SMB_Manager();
                $SERVER_CUS_PATH = trim(file_get_contents('../../server-name.txt'));
                $dest = "{$SERVER_CUS_PATH}_umrah/{$groupSysId}/{$fileName}";
                $ok   = $omv->paste_file($tmpPath, $dest);
                if ($ok === true) $smbPath = $dest;
            } catch (Throwable $e) { error_log('[umrah_noc] SMB error: '.$e->getMessage()); }

            // Insert NOC record
            require_once __DIR__ . '/../../server/uuid_generator.php';
            $nocSysId = 'UN-' . strtoupper(substr(uniqid(), -8));
            $nocUuid  = generateUUID();
            $pdo->prepare("
                INSERT INTO umrah_nocs (uuid, sys_id, group_sys_id, traveler_sys_ids, smb_path, file_name, uploaded_by, uploaded_at)
                VALUES (?,?,?,?,?,?,?,NOW())
            ")->execute([$nocUuid, $nocSysId, $groupSysId, json_encode($travelerSysIds), $smbPath, $fileName, $userName]);

            echo json_encode(['status'=>'success','message'=>'NOC uploaded','noc_sys_id'=>$nocSysId]);
            break;
        }

        // ── PUBLIC INFO (QR page) ────────────────────────────────────
        case 'public_info': {
            $travelerSysId = $_GET['traveler_sys_id'] ?? '';
            $groupSysId    = $_GET['group_sys_id']    ?? '';
            if (!$travelerSysId || !$groupSysId) throw new Exception('traveler_sys_id and group_sys_id required');

            // Get group
            $gs = $pdo->prepare("SELECT * FROM traveler_groups WHERE sys_id=? LIMIT 1");
            $gs->execute([$groupSysId]);
            $group = $gs->fetch(PDO::FETCH_ASSOC);
            if (!$group) throw new Exception('Group not found');
            $group['segments']  = $group['segments']  ? json_decode($group['segments'],  true) : [];
            $group['hotels']    = $group['hotels']    ? json_decode($group['hotels'],    true) : [];
            $group['itinerary'] = $group['itinerary'] ? json_decode($group['itinerary'], true) : [];

            // Get traveler + membership
            $ts = $pdo->prepare("
                SELECT t.name AS traveler_name, t.passport_no, t.date_of_birth,
                       t.smb_path, m.roaming_phone, m.is_leader
                FROM travelers t
                JOIN traveler_group_members m ON m.traveler_id = t.sys_id
                WHERE t.sys_id=? AND m.group_id=?
                LIMIT 1
            ");
            $ts->execute([$travelerSysId, $groupSysId]);
            $traveler = $ts->fetch(PDO::FETCH_ASSOC);
            if (!$traveler) throw new Exception('Traveler not found in group');

            // Passport file URL (serve via serve.php or SMB path)
            $traveler['passport_file_url'] = null; // TODO: link to serve.php with doc_id when passport doc is stored

            // Leaders
            $ls = $pdo->prepare("
                SELECT t.name, m.roaming_phone
                FROM traveler_group_members m
                JOIN travelers t ON t.sys_id = m.traveler_id
                WHERE m.group_id=? AND m.is_leader=1
            ");
            $ls->execute([$groupSysId]);
            $leaders = $ls->fetchAll(PDO::FETCH_ASSOC);

            // NOCs for this traveler
            $ns = $pdo->prepare("SELECT * FROM umrah_nocs WHERE group_sys_id=?");
            $ns->execute([$groupSysId]);
            $allNocs = $ns->fetchAll(PDO::FETCH_ASSOC);
            $myNocs  = array_filter($allNocs, fn($n) =>
                in_array($travelerSysId, json_decode($n['traveler_sys_ids'] ?? '[]', true) ?: [])
            );
            $nocData = array_values(array_map(fn($n) => ['url'=>null,'file'=>$n['file_name']], $myNocs));

            echo json_encode([
                'status' => 'success',
                'data'   => compact('traveler','group','leaders','nocData'),
            ]);
            break;
        }

        default:
            throw new Exception('Unknown action: ' . $action);
    }
} catch (Throwable $e) {
    ob_clean();
    http_response_code(400);
    echo json_encode(['status'=>'error','message'=>$e->getMessage()]);
}