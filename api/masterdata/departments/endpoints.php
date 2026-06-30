<?php
/**
 * FILE PATH: /api/masterdata/departments/endpoints.php
 * TravHub — Departments Master Data API
 *
 * GET  ?action=all           → list all departments
 * GET  ?action=get&id=SYS_ID → single department
 * POST action=store          → create
 * POST action=update         → update
 * POST action=toggle         → toggle is_active
 * POST action=delete         → delete
 */

ob_start();
session_start();
date_default_timezone_set('Asia/Dhaka');

require_once '../../../server/api_bootstrap.php';
require_once '../../../server/db_connection.php';
require_once '../../../server/sys_id_generator_v2.php';
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

        // ── LIST ALL ──────────────────────────────────────────
        case 'all':
            $stmt = $pdo->query("
                SELECT id, uuid, sys_id, name, slug, description, is_active, sort_order, meta_data
                FROM departments
                ORDER BY sort_order ASC, id ASC
            ");
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            foreach ($rows as &$r) {
                $r['meta_data'] = $r['meta_data'] ? json_decode($r['meta_data'], true) : [];
            }
            ob_clean();
            echo json_encode(['status' => 'success', 'data' => $rows]);
            break;

        // ── GET SINGLE ────────────────────────────────────────
        case 'get':
            $sysId = $_GET['id'] ?? '';
            if (!$sysId) throw new Exception('id is required');
            $stmt = $pdo->prepare("SELECT * FROM departments WHERE sys_id = ? LIMIT 1");
            $stmt->execute([$sysId]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$row) throw new Exception('Department not found');
            $row['meta_data'] = $row['meta_data'] ? json_decode($row['meta_data'], true) : [];
            ob_clean();
            echo json_encode(['status' => 'success', 'data' => $row]);
            break;

        // ── STORE ─────────────────────────────────────────────
        case 'store':
            $name = trim($body['name'] ?? '');
            if (!$name) throw new Exception('Department name is required');

            $slug = _makeDeptSlug($name, $pdo);
            $desc = trim($body['description'] ?? '');
            $sort = (int)($body['sort_order'] ?? 0);

            $ids  = generateV2IDs($pdo, 'departments');
            $meta = buildMetaData(null, $userName);

            $stmt = $pdo->prepare("
                INSERT INTO departments (uuid, sys_id, name, slug, description, sort_order, is_active, meta_data)
                VALUES (?, ?, ?, ?, ?, ?, 1, ?)
            ");
            $stmt->execute([$ids['uuid'], $ids['sys_id'], $name, $slug, $desc ?: null, $sort, $meta]);

            ob_clean();
            echo json_encode([
                'status'  => 'success',
                'message' => 'Department created successfully',
                'sys_id'  => $ids['sys_id'],
            ]);
            break;

        // ── UPDATE ────────────────────────────────────────────
        case 'update':
            $sysId = $body['sys_id'] ?? '';
            if (!$sysId) throw new Exception('sys_id is required');

            $s = $pdo->prepare("SELECT meta_data FROM departments WHERE sys_id = ?");
            $s->execute([$sysId]);
            $existing = $s->fetchColumn();
            if ($existing === false) throw new Exception('Department not found');

            $name = trim($body['name'] ?? '');
            if (!$name) throw new Exception('Department name is required');
            $desc = trim($body['description'] ?? '');
            $sort = (int)($body['sort_order'] ?? 0);
            $meta = buildMetaData($existing, $userName);

            $stmt = $pdo->prepare("
                UPDATE departments SET name=?, description=?, sort_order=?, meta_data=? WHERE sys_id=?
            ");
            $stmt->execute([$name, $desc ?: null, $sort, $meta, $sysId]);

            ob_clean();
            echo json_encode(['status' => 'success', 'message' => 'Department updated successfully']);
            break;

        // ── TOGGLE ────────────────────────────────────────────
        case 'toggle':
            $sysId = $body['sys_id'] ?? '';
            if (!$sysId) throw new Exception('sys_id is required');
            $s = $pdo->prepare("SELECT is_active, meta_data FROM departments WHERE sys_id = ?");
            $s->execute([$sysId]);
            $row = $s->fetch(PDO::FETCH_ASSOC);
            if (!$row) throw new Exception('Department not found');
            $newActive = $row['is_active'] ? 0 : 1;
            $meta = buildMetaData($row['meta_data'], $userName);
            $pdo->prepare("UPDATE departments SET is_active=?, meta_data=? WHERE sys_id=?")
                ->execute([$newActive, $meta, $sysId]);
            ob_clean();
            echo json_encode(['status' => 'success', 'is_active' => $newActive]);
            break;

        // ── DELETE ────────────────────────────────────────────
        case 'delete':
            $sysId = $body['sys_id'] ?? '';
            if (!$sysId) throw new Exception('sys_id is required');
            $pdo->prepare("DELETE FROM departments WHERE sys_id=?")->execute([$sysId]);
            ob_clean();
            echo json_encode(['status' => 'success', 'message' => 'Department deleted']);
            break;

        default:
            throw new Exception("Unknown action: '{$action}'");
    }

} catch (Exception $e) {
    ob_clean();
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}

function _makeDeptSlug(string $name, PDO $pdo): string
{
    $slug = strtolower(trim(preg_replace('/[^a-zA-Z0-9]+/', '_', $name), '_'));
    $base = $slug; $i = 1;
    while (true) {
        $s = $pdo->prepare("SELECT COUNT(*) FROM departments WHERE slug=?");
        $s->execute([$slug]);
        if ((int)$s->fetchColumn() === 0) break;
        $slug = $base . '_' . $i++;
    }
    return $slug;
}