<?php
/**
 * FILE PATH: /api/works/travelers.php
 *
 * works.traveler_sys_ids stores a JSON array of OBJECTS (not plain strings):
 *   [{"traveler_sys_id": "THR-TR-001", "name": "Rahim Ahmed"}, ...]
 * This avoids re-querying the travelers table just to show names in lists.
 *
 * GET  ?action=list&work_sys_id=                          → full traveler records (joined live from travelers table)
 * POST action=link    { work_sys_id, traveler_sys_id }    → add one traveler (name auto-fetched + stored)
 * POST action=unlink  { work_sys_id, traveler_sys_id }    → remove one traveler
 */
ob_start();
session_start();
date_default_timezone_set('Asia/Dhaka');
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { ob_clean(); http_response_code(200); exit; }

require_once '../../server/db_connection.php';
require_once '../../server/generate_meta_data.php';

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';
$body   = [];
if ($method === 'POST') {
    $body   = json_decode(file_get_contents('php://input'), true) ?? [];
    $action = $body['action'] ?? $action;
}
$userName = $_SESSION['user_name'] ?? 'system';

/**
 * Normalize whatever is stored in traveler_sys_ids into a clean
 * array of {traveler_sys_id, name} objects.
 * Handles legacy data where it might still be plain strings.
 */
function _normalizeTravelerRefs(array $raw): array
{
    $out = [];
    foreach ($raw as $item) {
        if (is_array($item) && isset($item['traveler_sys_id'])) {
            $out[] = ['traveler_sys_id' => $item['traveler_sys_id'], 'name' => $item['name'] ?? ''];
        } elseif (is_string($item)) {
            // legacy plain sys_id string — name unknown until refreshed
            $out[] = ['traveler_sys_id' => $item, 'name' => ''];
        }
    }
    return $out;
}

try {
    switch ($action) {

        // ── LIST travelers linked to a work (full live records) ──
        case 'list':
            $workSysId = $_GET['work_sys_id'] ?? '';
            if (!$workSysId) throw new Exception('work_sys_id required');

            $wStmt = $pdo->prepare("SELECT traveler_sys_ids FROM works WHERE sys_id = ? LIMIT 1");
            $wStmt->execute([$workSysId]);
            $row = $wStmt->fetch(PDO::FETCH_ASSOC);
            if ($row === false) throw new Exception('Work not found');

            $refs = _normalizeTravelerRefs(json_decode($row['traveler_sys_ids'] ?? '[]', true) ?: []);
            if (!$refs) {
                ob_clean();
                echo json_encode(['status' => 'success', 'data' => []]);
                exit;
            }

            $sysIds = array_column($refs, 'traveler_sys_id');
            $placeholders = implode(',', array_fill(0, count($sysIds), '?'));
            $tStmt = $pdo->prepare("SELECT * FROM travelers WHERE sys_id IN ({$placeholders})");
            $tStmt->execute($sysIds);
            $travelers = $tStmt->fetchAll(PDO::FETCH_ASSOC);

            // Preserve link order, full live record (passport, phone etc. for the modal)
            $ordered = [];
            foreach ($refs as $ref) {
                foreach ($travelers as $t) {
                    if ($t['sys_id'] === $ref['traveler_sys_id']) { $ordered[] = $t; break; }
                }
            }

            ob_clean();
            echo json_encode(['status' => 'success', 'data' => $ordered, 'refs' => $refs]);
            break;

        // ── LINK a traveler to a work ─────────────────────────
        case 'link':
            $workSysId     = $body['work_sys_id']     ?? '';
            $travelerSysId = $body['traveler_sys_id'] ?? '';
            if (!$workSysId || !$travelerSysId) throw new Exception('work_sys_id and traveler_sys_id required');

            // Fetch traveler full record (passport_info included)
            $tCheck = $pdo->prepare("SELECT sys_id, name, passport_info, smb_path FROM travelers WHERE sys_id = ? LIMIT 1");
            $tCheck->execute([$travelerSysId]);
            $traveler = $tCheck->fetch(PDO::FETCH_ASSOC);
            if (!$traveler) throw new Exception('Traveler not found');

            // Parse passport_info JSON
            $passport = [];
            if (!empty($traveler['passport_info'])) {
                $passport = json_decode($traveler['passport_info'], true) ?? [];
            }

            // Build traveler ref object with passport snapshot
            $travelerRef = [
                'traveler_sys_id'   => $traveler['sys_id'],
                'name'              => $traveler['name'],
                'given_name'        => $passport['given_name']    ?? $passport['first_name']  ?? '',
                'surname'           => $passport['surname']       ?? $passport['last_name']   ?? '',
                'passport_no'       => $passport['passport_no']   ?? $passport['passport_number'] ?? '',
                'passport_expiry'   => $passport['expiry_date']   ?? $passport['date_of_expiry']  ?? '',
                'dob'               => $passport['date_of_birth'] ?? $passport['dob']              ?? '',
                'passport_smb_path' => $traveler['smb_path']      ?? '',
            ];

            $wStmt = $pdo->prepare("SELECT traveler_sys_ids, meta_data FROM works WHERE sys_id = ? LIMIT 1");
            $wStmt->execute([$workSysId]);
            $work = $wStmt->fetch(PDO::FETCH_ASSOC);
            if (!$work) throw new Exception('Work not found');

            $refs = _normalizeTravelerRefs(json_decode($work['traveler_sys_ids'] ?? '[]', true) ?: []);

            // Check duplicate
            foreach ($refs as $r) {
                if ($r['traveler_sys_id'] === $travelerSysId) {
                    ob_clean();
                    echo json_encode(['status' => 'success', 'message' => 'Already linked', 'traveler_sys_ids' => $refs]);
                    exit;
                }
            }
            $refs[] = $travelerRef;

            $meta = buildMetaData($work['meta_data'], $userName);
            $pdo->prepare("UPDATE works SET traveler_sys_ids = ?, meta_data = ? WHERE sys_id = ?")
                ->execute([json_encode($refs, JSON_UNESCAPED_UNICODE), $meta, $workSysId]);

            ob_clean();
            echo json_encode(['status' => 'success', 'message' => 'Traveler linked', 'traveler' => $travelerRef, 'traveler_sys_ids' => $refs]);
            break;

        // ── UNLINK a traveler from a work ─────────────────────
        case 'unlink':
            $workSysId     = $body['work_sys_id']     ?? '';
            $travelerSysId = $body['traveler_sys_id'] ?? '';
            if (!$workSysId || !$travelerSysId) throw new Exception('work_sys_id and traveler_sys_id required');

            $wStmt = $pdo->prepare("SELECT traveler_sys_ids, meta_data FROM works WHERE sys_id = ? LIMIT 1");
            $wStmt->execute([$workSysId]);
            $work = $wStmt->fetch(PDO::FETCH_ASSOC);
            if (!$work) throw new Exception('Work not found');

            $refs = _normalizeTravelerRefs(json_decode($work['traveler_sys_ids'] ?? '[]', true) ?: []);
            $refs = array_values(array_filter($refs, fn($r) => $r['traveler_sys_id'] !== $travelerSysId));

            $meta = buildMetaData($work['meta_data'], $userName);
            $pdo->prepare("UPDATE works SET traveler_sys_ids = ?, meta_data = ? WHERE sys_id = ?")
                ->execute([json_encode($refs, JSON_UNESCAPED_UNICODE), $meta, $workSysId]);

            ob_clean();
            echo json_encode(['status' => 'success', 'message' => 'Traveler removed', 'traveler_sys_ids' => $refs]);
            break;

        default:
            throw new Exception("Unknown action: '{$action}'");
    }

} catch (Exception $e) {
    ob_clean();
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}