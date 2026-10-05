<?php
/**
 * FILE PATH: /api/masterdata/visa/endpoints.php
 * TravHub — Visa Master Data API (rebuilt for Gen-3)
 *
 * visa_types ──────────────────────────────────────────────
 *   GET  ?action=list_types
 *   POST action=save_type          { name }
 *   POST action=toggle_type        { sys_id }
 *   POST action=delete_type        { sys_id }
 *
 * visa_categories ─────────────────────────────────────────
 *   GET  ?action=list_categories
 *   POST action=save_category      { name }
 *   POST action=toggle_category    { sys_id }
 *   POST action=delete_category    { sys_id }
 *
 * master_visa_services ────────────────────────────────────
 *   GET  ?action=list_services     [&country_sys_id=&status=]
 *   GET  ?action=get_service&sys_id=
 *   POST action=save_service       { sys_id?, title, country_sys_id, country_name,
 *                                    visa_type_sys_id, visa_category_sys_id,
 *                                    duration_days, duration_label,
 *                                    required_documents, description,
 *                                    b2c_price, purchase_price, currency,
 *                                    file_size_limit_kb }
 *   POST action=toggle_service     { sys_id }
 *   POST action=delete_service     { sys_id }
 */

ob_start();
session_start();
date_default_timezone_set('Asia/Dhaka');

require_once '../../../server/api_bootstrap.php';
require_once '../../../server/db_connection.php';
require_once '../../../server/sys_id_generator_v2.php';

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

$body = [];
if ($method === 'POST') {
    $raw  = file_get_contents('php://input');
    $body = json_decode($raw, true) ?? [];
    if (empty($action)) $action = $body['action'] ?? '';
}

function jsonResp(array $data, int $code = 200): void {
    ob_clean();
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
}

try {

    switch ($action) {

        // ══════════════════════════════════════════════════════
        // VISA TYPES
        // ══════════════════════════════════════════════════════

        case 'list_types':
            $rows = $pdo->query("
                SELECT id, uuid, sys_id, name, status, created_at
                FROM visa_types
                ORDER BY name ASC
            ")->fetchAll(PDO::FETCH_ASSOC);
            jsonResp(['status' => 'success', 'data' => $rows]);
            break;

        case 'save_type':
            $name  = trim($body['name']   ?? '');
            $sysId = trim($body['sys_id'] ?? '');
            if (!$name) throw new Exception('name is required');

            if ($sysId) {
                // UPDATE
                $pdo->prepare("UPDATE visa_types SET name = ? WHERE sys_id = ?")
                    ->execute([$name, $sysId]);
                jsonResp(['status' => 'success', 'message' => 'Updated', 'sys_id' => $sysId]);
            } else {
                // INSERT
                $ids = generateV2IDs($pdo, 'visa_types');
                $pdo->prepare("INSERT INTO visa_types (uuid, sys_id, name) VALUES (?,?,?)")
                    ->execute([$ids['uuid'], $ids['sys_id'], $name]);
                jsonResp(['status' => 'success', 'message' => 'Created',
                          'sys_id' => $ids['sys_id'], 'uuid' => $ids['uuid']]);
            }
            break;

        case 'toggle_type':
            $sysId = $body['sys_id'] ?? '';
            if (!$sysId) throw new Exception('sys_id required');
            $row = $pdo->prepare("SELECT status FROM visa_types WHERE sys_id = ?");
            $row->execute([$sysId]);
            $cur = $row->fetchColumn();
            if ($cur === false) throw new Exception('Not found');
            $new = $cur ? 0 : 1;
            $pdo->prepare("UPDATE visa_types SET status = ? WHERE sys_id = ?")
                ->execute([$new, $sysId]);
            jsonResp(['status' => 'success', 'new_status' => $new]);
            break;

        case 'delete_type':
            $sysId = $body['sys_id'] ?? '';
            if (!$sysId) throw new Exception('sys_id required');
            // Check if in use
            $used = $pdo->prepare("SELECT COUNT(*) FROM master_visa_services WHERE visa_type_sys_id = ?");
            $used->execute([$sysId]);
            if ($used->fetchColumn() > 0)
                throw new Exception('Cannot delete — this visa type is used by one or more services.');
            $pdo->prepare("DELETE FROM visa_types WHERE sys_id = ?")->execute([$sysId]);
            jsonResp(['status' => 'success', 'message' => 'Deleted']);
            break;

        // ══════════════════════════════════════════════════════
        // VISA CATEGORIES
        // ══════════════════════════════════════════════════════

        case 'list_categories':
            $rows = $pdo->query("
                SELECT id, uuid, sys_id, name, status, created_at
                FROM visa_categories
                ORDER BY name ASC
            ")->fetchAll(PDO::FETCH_ASSOC);
            jsonResp(['status' => 'success', 'data' => $rows]);
            break;

        case 'save_category':
            $name  = trim($body['name']   ?? '');
            $sysId = trim($body['sys_id'] ?? '');
            if (!$name) throw new Exception('name is required');

            if ($sysId) {
                $pdo->prepare("UPDATE visa_categories SET name = ? WHERE sys_id = ?")
                    ->execute([$name, $sysId]);
                jsonResp(['status' => 'success', 'message' => 'Updated', 'sys_id' => $sysId]);
            } else {
                $ids = generateV2IDs($pdo, 'visa_categories');
                $pdo->prepare("INSERT INTO visa_categories (uuid, sys_id, name) VALUES (?,?,?)")
                    ->execute([$ids['uuid'], $ids['sys_id'], $name]);
                jsonResp(['status' => 'success', 'message' => 'Created',
                          'sys_id' => $ids['sys_id'], 'uuid' => $ids['uuid']]);
            }
            break;

        case 'toggle_category':
            $sysId = $body['sys_id'] ?? '';
            if (!$sysId) throw new Exception('sys_id required');
            $row = $pdo->prepare("SELECT status FROM visa_categories WHERE sys_id = ?");
            $row->execute([$sysId]);
            $cur = $row->fetchColumn();
            if ($cur === false) throw new Exception('Not found');
            $new = $cur ? 0 : 1;
            $pdo->prepare("UPDATE visa_categories SET status = ? WHERE sys_id = ?")
                ->execute([$new, $sysId]);
            jsonResp(['status' => 'success', 'new_status' => $new]);
            break;

        case 'delete_category':
            $sysId = $body['sys_id'] ?? '';
            if (!$sysId) throw new Exception('sys_id required');
            $used = $pdo->prepare("SELECT COUNT(*) FROM master_visa_services WHERE visa_category_sys_id = ?");
            $used->execute([$sysId]);
            if ($used->fetchColumn() > 0)
                throw new Exception('Cannot delete — this category is used by one or more services.');
            $pdo->prepare("DELETE FROM visa_categories WHERE sys_id = ?")->execute([$sysId]);
            jsonResp(['status' => 'success', 'message' => 'Deleted']);
            break;

        // ══════════════════════════════════════════════════════
        // MASTER VISA SERVICES
        // ══════════════════════════════════════════════════════

        case 'list_services':
            $where   = ['1=1'];
            $params  = [];
            if (!empty($_GET['country_sys_id'])) {
                $where[]  = 'mvs.country_sys_id = ?';
                $params[] = $_GET['country_sys_id'];
            }
            if (isset($_GET['status']) && $_GET['status'] !== '') {
                $where[]  = 'mvs.status = ?';
                $params[] = (int)$_GET['status'];
            }
            $whereStr = implode(' AND ', $where);
            $stmt = $pdo->prepare("
                SELECT mvs.id, mvs.uuid, mvs.sys_id,
                       mvs.title, mvs.country_sys_id, mvs.country_name,
                       mvs.visa_type_sys_id, mvs.visa_category_sys_id,
                       vt.name AS visa_type_name,
                       vc.name AS visa_category_name,
                       mvs.duration_days, mvs.duration_label,
                       mvs.b2c_price, mvs.purchase_price, mvs.currency,
                       mvs.file_size_limit_kb, mvs.status,
                       mvs.created_at, mvs.updated_at
                FROM master_visa_services mvs
                LEFT JOIN visa_types vt ON vt.sys_id = mvs.visa_type_sys_id
                LEFT JOIN visa_categories vc ON vc.sys_id = mvs.visa_category_sys_id
                WHERE {$whereStr}
                ORDER BY mvs.country_name ASC, mvs.title ASC
            ");
            $stmt->execute($params);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            jsonResp(['status' => 'success', 'data' => $rows]);
            break;

        case 'get_service':
            $sysId = $_GET['sys_id'] ?? '';
            if (!$sysId) throw new Exception('sys_id required');
            $stmt = $pdo->prepare("
                SELECT mvs.*,
                       vt.name AS visa_type_name,
                       vc.name AS visa_category_name
                FROM master_visa_services mvs
                LEFT JOIN visa_types vt ON vt.sys_id = mvs.visa_type_sys_id
                LEFT JOIN visa_categories vc ON vc.sys_id = mvs.visa_category_sys_id
                WHERE mvs.sys_id = ?
                LIMIT 1
            ");
            $stmt->execute([$sysId]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$row) throw new Exception('Not found');
            $row['required_documents'] = $row['required_documents']
                ? json_decode($row['required_documents'], true) : [];
            $row['description'] = $row['description']
                ? json_decode($row['description'], true) : [];
            jsonResp(['status' => 'success', 'data' => $row]);
            break;

        case 'save_service':
            // Required fields
            $sysId           = trim($body['sys_id']              ?? '');
            $title           = trim($body['title']               ?? '');
            $countrySysId    = trim($body['country_sys_id']      ?? '');
            $countryName     = trim($body['country_name']        ?? '');
            $visaTypeSysId   = trim($body['visa_type_sys_id']    ?? '');
            $visaCatSysId    = trim($body['visa_category_sys_id'] ?? '');
            $durationDays    = (int)($body['duration_days']      ?? 0);
            $durationLabel   = trim($body['duration_label']      ?? '');
            $b2cPrice        = (float)($body['b2c_price']        ?? 0);
            $purchasePrice   = (float)($body['purchase_price']   ?? 0);
            $currency        = trim($body['currency']            ?? 'BDT');
            $fileSizeLimit   = (int)($body['file_size_limit_kb'] ?? 300);
            $reqDocs         = $body['required_documents']       ?? [];
            $description     = $body['description']             ?? [];

            if (!$title)        throw new Exception('title is required');
            if (!$countrySysId) throw new Exception('country_sys_id is required');
            if (!$visaTypeSysId)  throw new Exception('visa_type_sys_id is required');
            if (!$visaCatSysId)   throw new Exception('visa_category_sys_id is required');

            $reqDocsJson  = json_encode($reqDocs,    JSON_UNESCAPED_UNICODE);
            $descJson     = json_encode($description, JSON_UNESCAPED_UNICODE);

            if ($sysId) {
                // UPDATE
                $pdo->prepare("
                    UPDATE master_visa_services SET
                        title = ?, country_sys_id = ?, country_name = ?,
                        visa_type_sys_id = ?, visa_category_sys_id = ?,
                        duration_days = ?, duration_label = ?,
                        required_documents = ?, description = ?,
                        b2c_price = ?, purchase_price = ?, currency = ?,
                        file_size_limit_kb = ?
                    WHERE sys_id = ?
                ")->execute([
                    $title, $countrySysId, $countryName,
                    $visaTypeSysId, $visaCatSysId,
                    $durationDays, $durationLabel,
                    $reqDocsJson, $descJson,
                    $b2cPrice, $purchasePrice, $currency,
                    $fileSizeLimit, $sysId
                ]);
                jsonResp(['status' => 'success', 'message' => 'Service updated', 'sys_id' => $sysId]);
            } else {
                // INSERT
                $ids = generateV2IDs($pdo, 'master_visa_services');
                $pdo->prepare("
                    INSERT INTO master_visa_services
                        (uuid, sys_id, title, country_sys_id, country_name,
                         visa_type_sys_id, visa_category_sys_id,
                         duration_days, duration_label,
                         required_documents, description,
                         b2c_price, purchase_price, currency, file_size_limit_kb)
                    VALUES (?,?,?,?,?, ?,?, ?,?, ?,?, ?,?,?,?)
                ")->execute([
                    $ids['uuid'], $ids['sys_id'],
                    $title, $countrySysId, $countryName,
                    $visaTypeSysId, $visaCatSysId,
                    $durationDays, $durationLabel,
                    $reqDocsJson, $descJson,
                    $b2cPrice, $purchasePrice, $currency, $fileSizeLimit
                ]);
                jsonResp(['status' => 'success', 'message' => 'Service created',
                          'sys_id' => $ids['sys_id'], 'uuid' => $ids['uuid']]);
            }
            break;

        case 'toggle_service':
            $sysId = $body['sys_id'] ?? '';
            if (!$sysId) throw new Exception('sys_id required');
            $row = $pdo->prepare("SELECT status FROM master_visa_services WHERE sys_id = ?");
            $row->execute([$sysId]);
            $cur = $row->fetchColumn();
            if ($cur === false) throw new Exception('Not found');
            $new = $cur ? 0 : 1;
            $pdo->prepare("UPDATE master_visa_services SET status = ? WHERE sys_id = ?")
                ->execute([$new, $sysId]);
            jsonResp(['status' => 'success', 'new_status' => $new]);
            break;

        case 'delete_service':
            $sysId = $body['sys_id'] ?? '';
            if (!$sysId) throw new Exception('sys_id required');
            $pdo->prepare("DELETE FROM master_visa_services WHERE sys_id = ?")->execute([$sysId]);
            jsonResp(['status' => 'success', 'message' => 'Deleted']);
            break;

        default:
            throw new Exception("Unknown action: '{$action}'");
    }

} catch (Exception $e) {
    ob_clean();
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}