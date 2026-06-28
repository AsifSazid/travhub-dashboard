<?php
/**
 * FILE PATH: /api/masterdata/services/endpoints.php
 * TravHub — Services Master Data API
 *
 * GET    ?action=all           → list all services
 * GET    ?action=get&id=SYS_ID → single service
 * POST   action=store          → create new service
 * POST   action=update         → update existing service
 * POST   action=toggle         → toggle is_active
 * POST   action=delete         → soft delete (is_active=0) or hard delete
 * POST   action=reorder        → update sort_order
 */

ob_start();
session_start();
date_default_timezone_set('Asia/Dhaka');

require_once '../../../server/api_bootstrap.php';
require_once '../../../server/db_connection.php';
require_once '../../../server/sys_id_generator_v2.php';
require_once '../../../server/generate_meta_data.php';

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? ($_POST['action'] ?? '');

// Parse JSON body for POST requests
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
                SELECT id, uuid, sys_id, name, slug, icon, color,
                       description, fields, sort_order, is_active, meta_data
                FROM services
                ORDER BY sort_order ASC, id ASC
            ");
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            foreach ($rows as &$r) {
                $r['fields']    = $r['fields']    ? json_decode($r['fields'], true)    : [];
                $r['meta_data'] = $r['meta_data'] ? json_decode($r['meta_data'], true) : [];
            }
            ob_clean();
            echo json_encode(['status' => 'success', 'data' => $rows]);
            break;

        // ── GET SINGLE ────────────────────────────────────────
        case 'get':
            $sysId = $_GET['id'] ?? '';
            if (!$sysId) throw new Exception('id is required');
            $stmt = $pdo->prepare("SELECT * FROM services WHERE sys_id = ? LIMIT 1");
            $stmt->execute([$sysId]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$row) throw new Exception('Service not found');
            $row['fields']    = $row['fields']    ? json_decode($row['fields'], true)    : [];
            $row['meta_data'] = $row['meta_data'] ? json_decode($row['meta_data'], true) : [];
            ob_clean();
            echo json_encode(['status' => 'success', 'data' => $row]);
            break;

        // ── STORE ─────────────────────────────────────────────
        case 'store':
            $name  = trim($body['name'] ?? '');
            if (!$name) throw new Exception('Service name is required');

            $slug  = _makeSlug($name, $pdo);
            $icon  = trim($body['icon']  ?? 'fa-circle');
            $color = trim($body['color'] ?? 'indigo');
            $desc  = trim($body['description'] ?? '');
            $fields = isset($body['fields']) ? json_encode($body['fields']) : null;
            $sort  = (int)($body['sort_order'] ?? 0);

            $ids  = generateV2IDs($pdo, 'services');
            $meta = buildMetaData(null, $userName);

            $stmt = $pdo->prepare("
                INSERT INTO services (uuid, sys_id, name, slug, icon, color, description, fields, sort_order, is_active, meta_data)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 1, ?)
            ");
            $stmt->execute([$ids['uuid'], $ids['sys_id'], $name, $slug, $icon, $color, $desc ?: null, $fields, $sort, $meta]);

            ob_clean();
            echo json_encode([
                'status'  => 'success',
                'message' => 'Service created successfully',
                'sys_id'  => $ids['sys_id'],
                'uuid'    => $ids['uuid'],
            ]);
            break;

        // ── UPDATE ────────────────────────────────────────────
        case 'update':
            $sysId = $body['sys_id'] ?? '';
            if (!$sysId) throw new Exception('sys_id is required');

            // Get existing meta
            $s = $pdo->prepare("SELECT meta_data FROM services WHERE sys_id = ?");
            $s->execute([$sysId]);
            $existing = $s->fetchColumn();
            if ($existing === false) throw new Exception('Service not found');

            $name   = trim($body['name']  ?? '');
            if (!$name) throw new Exception('Service name is required');
            $icon   = trim($body['icon']  ?? 'fa-circle');
            $color  = trim($body['color'] ?? 'indigo');
            $desc   = trim($body['description'] ?? '');
            $fields = isset($body['fields']) ? json_encode($body['fields']) : null;
            $sort   = (int)($body['sort_order'] ?? 0);
            $meta   = buildMetaData($existing, $userName);

            $stmt = $pdo->prepare("
                UPDATE services
                SET name=?, icon=?, color=?, description=?, fields=?, sort_order=?, meta_data=?
                WHERE sys_id=?
            ");
            $stmt->execute([$name, $icon, $color, $desc ?: null, $fields, $sort, $meta, $sysId]);

            ob_clean();
            echo json_encode(['status' => 'success', 'message' => 'Service updated successfully']);
            break;

        // ── TOGGLE ACTIVE ─────────────────────────────────────
        case 'toggle':
            $sysId = $body['sys_id'] ?? '';
            if (!$sysId) throw new Exception('sys_id is required');
            $s = $pdo->prepare("SELECT is_active, meta_data FROM services WHERE sys_id = ?");
            $s->execute([$sysId]);
            $row = $s->fetch(PDO::FETCH_ASSOC);
            if (!$row) throw new Exception('Service not found');
            $newActive = $row['is_active'] ? 0 : 1;
            $meta = buildMetaData($row['meta_data'], $userName);
            $pdo->prepare("UPDATE services SET is_active=?, meta_data=? WHERE sys_id=?")
                ->execute([$newActive, $meta, $sysId]);
            ob_clean();
            echo json_encode(['status' => 'success', 'is_active' => $newActive]);
            break;

        // ── DELETE ────────────────────────────────────────────
        case 'delete':
            $sysId = $body['sys_id'] ?? '';
            if (!$sysId) throw new Exception('sys_id is required');
            $pdo->prepare("DELETE FROM services WHERE sys_id=?")->execute([$sysId]);
            ob_clean();
            echo json_encode(['status' => 'success', 'message' => 'Service deleted']);
            break;

        // ── REORDER ───────────────────────────────────────────
        case 'reorder':
            // body: { items: [{sys_id:'...', sort_order:1}, ...] }
            $items = $body['items'] ?? [];
            if (empty($items)) throw new Exception('items array required');
            $stmt = $pdo->prepare("UPDATE services SET sort_order=? WHERE sys_id=?");
            foreach ($items as $item) {
                $stmt->execute([(int)$item['sort_order'], $item['sys_id']]);
            }
            ob_clean();
            echo json_encode(['status' => 'success', 'message' => 'Order updated']);
            break;

        default:
            throw new Exception("Unknown action: '{$action}'");
    }

} catch (Exception $e) {
    ob_clean();
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}

// ─── Helpers ─────────────────────────────────────────────────
function _makeSlug(string $name, PDO $pdo): string
{
    $slug = strtolower(trim(preg_replace('/[^a-zA-Z0-9]+/', '_', $name), '_'));
    // ensure uniqueness
    $base = $slug;
    $i    = 1;
    while (true) {
        $s = $pdo->prepare("SELECT COUNT(*) FROM services WHERE slug=?");
        $s->execute([$slug]);
        if ((int)$s->fetchColumn() === 0) break;
        $slug = $base . '_' . $i++;
    }
    return $slug;
}