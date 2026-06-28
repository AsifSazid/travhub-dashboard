<?php
/**
 * FILE PATH: /api/masterdata/visa/endpoints.php
 * TravHub — Visa Masterdata API
 *
 * GET  ?action=list                        → all countries that have visa data
 * GET  ?action=get&country=COUNTRY_SYS_ID  → visa data for one country
 * POST action=save                         → create or update (upsert by country_sys_id)
 * POST action=toggle                       → toggle is_active
 * POST action=delete                       → hard delete by sys_id
 */

ob_start();
session_start();
date_default_timezone_set('Asia/Dhaka');

require_once '../../../server/api_bootstrap.php';
require_once '../../../server/db_connection.php';
require '../../../server/id_generator.php';
require_once '../../../server/generate_meta_data.php';

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

$body = [];
if ($method === 'POST') {
    $raw  = file_get_contents('php://input');
    $body = json_decode($raw, true) ?? [];
    if (empty($action)) $action = $body['action'] ?? '';
}

$userName = $_SESSION['user_name'] ?? 'system';

try {

    switch ($action) {

        /* ── LIST: all countries with visa data ─────────────── */
        case 'list':
            $stmt = $pdo->query("
                SELECT vm.id, vm.uuid, vm.sys_id,
                       vm.country_sys_id, vm.country_name,
                       vm.is_active, vm.meta_data,
                       JSON_LENGTH(vm.categories) AS category_count
                FROM visa_masterdata vm
                ORDER BY vm.country_name ASC
            ");
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            foreach ($rows as &$r) {
                $r['meta_data']      = $r['meta_data'] ? json_decode($r['meta_data'], true) : [];
                $r['category_count'] = (int)$r['category_count'];
            }
            ob_clean();
            echo json_encode(['status' => 'ok', 'data' => $rows]);
            break;

        /* ── GET: full data for one country ─────────────────── */
        case 'get':
            $countryId = $_GET['country'] ?? '';
            if (!$countryId) {
                ob_clean();
                echo json_encode(['status' => 'ok', 'data' => null]);
                break;
            }
            $stmt = $pdo->prepare("
                SELECT sys_id, country_sys_id, country_name, categories, is_active, meta_data
                FROM visa_masterdata
                WHERE country_sys_id = ? AND is_active = 1
                LIMIT 1
            ");
            $stmt->execute([$countryId]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$row) {
                ob_clean();
                echo json_encode(['status' => 'ok', 'data' => null]);
                break;
            }
            $row['categories'] = $row['categories'] ? json_decode($row['categories'], true) : [];
            $row['meta_data']  = $row['meta_data']  ? json_decode($row['meta_data'],  true) : [];
            ob_clean();
            echo json_encode(['status' => 'ok', 'data' => $row]);
            break;

        /* ── SAVE: upsert by country_sys_id ─────────────────── */
        case 'save':
            $countryId   = trim($body['country_sys_id'] ?? '');
            $countryName = trim($body['country_name']   ?? '');
            $categories  = $body['categories'] ?? [];

            if (!$countryId)   throw new Exception('country_sys_id is required');
            if (!$countryName) throw new Exception('country_name is required');

            // Validate & generate category IDs if missing
            foreach ($categories as &$cat) {
                if (empty($cat['id']))   $cat['id']   = 'cat_' . uniqid();
                if (!isset($cat['name']))              $cat['name']          = '';
                if (!isset($cat['instruction']))       $cat['instruction']   = '';
                if (!isset($cat['document_list']))     $cat['document_list'] = [];
                if (!isset($cat['requirements']))      $cat['requirements']  = [];
                if (!isset($cat['sub_categories']))    $cat['sub_categories'] = [];
                foreach ($cat['sub_categories'] as &$sub) {
                    if (empty($sub['id']))             $sub['id']            = 'sub_' . uniqid();
                    if (!isset($sub['name']))          $sub['name']          = '';
                    if (!isset($sub['instruction']))   $sub['instruction']   = '';
                    if (!isset($sub['document_list'])) $sub['document_list'] = [];
                    if (!isset($sub['requirements']))  $sub['requirements']  = [];
                }
                unset($sub);
            }
            unset($cat);

            $categoriesJson = json_encode($categories, JSON_UNESCAPED_UNICODE);

            // Check existing
            $check = $pdo->prepare("SELECT sys_id, meta_data FROM visa_masterdata WHERE country_sys_id = ? LIMIT 1");
            $check->execute([$countryId]);
            $existing = $check->fetch(PDO::FETCH_ASSOC);

            if ($existing) {
                // UPDATE
                $meta = buildMetaData($existing['meta_data'], $userName);
                $stmt = $pdo->prepare("
                    UPDATE visa_masterdata
                    SET country_name = ?, categories = ?, is_active = 1, meta_data = ?
                    WHERE sys_id = ?
                ");
                $stmt->execute([$countryName, $categoriesJson, $meta, $existing['sys_id']]);
                ob_clean();
                echo json_encode([
                    'status'  => 'ok',
                    'message' => 'Visa masterdata updated',
                    'sys_id'  => $existing['sys_id'],
                    'action'  => 'updated',
                ]);
            } else {
                // INSERT
                $ids  = generateIDs($pdo, 'visa_masterdata');
                $meta = buildMetaData(null, $userName);
                $stmt = $pdo->prepare("
                    INSERT INTO visa_masterdata
                        (uuid, sys_id, country_sys_id, country_name, categories, is_active, meta_data)
                    VALUES (?, ?, ?, ?, ?, 1, ?)
                ");
                $stmt->execute([$ids['uuid'], $ids['sys_id'], $countryId, $countryName, $categoriesJson, $meta]);
                ob_clean();
                echo json_encode([
                    'status'  => 'ok',
                    'message' => 'Visa masterdata created',
                    'sys_id'  => $ids['sys_id'],
                    'uuid'    => $ids['uuid'],
                    'action'  => 'created',
                ]);
            }
            break;

        /* ── TOGGLE is_active ────────────────────────────────── */
        case 'toggle':
            $sysId = $body['sys_id'] ?? '';
            if (!$sysId) throw new Exception('sys_id is required');
            $s = $pdo->prepare("SELECT is_active, meta_data FROM visa_masterdata WHERE sys_id = ?");
            $s->execute([$sysId]);
            $row = $s->fetch(PDO::FETCH_ASSOC);
            if (!$row) throw new Exception('Record not found');
            $newVal = $row['is_active'] ? 0 : 1;
            $meta   = buildMetaData($row['meta_data'], $userName);
            $pdo->prepare("UPDATE visa_masterdata SET is_active = ?, meta_data = ? WHERE sys_id = ?")
                ->execute([$newVal, $meta, $sysId]);
            ob_clean();
            echo json_encode(['status' => 'ok', 'is_active' => $newVal]);
            break;

        /* ── DELETE ──────────────────────────────────────────── */
        case 'delete':
            $sysId = $body['sys_id'] ?? '';
            if (!$sysId) throw new Exception('sys_id is required');
            $pdo->prepare("DELETE FROM visa_masterdata WHERE sys_id = ?")->execute([$sysId]);
            ob_clean();
            echo json_encode(['status' => 'ok', 'message' => 'Deleted']);
            break;

        default:
            throw new Exception("Unknown action: '{$action}'");
    }

} catch (Exception $e) {
    ob_clean();
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}