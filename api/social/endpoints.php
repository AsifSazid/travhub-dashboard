<?php
/**
 * FILE PATH: /api/social/endpoints.php
 * GET  ?action=list&platform=&status=&limit=20&offset=0
 * GET  ?action=get&sys_id=
 * POST action=save   { ...post data }
 * POST action=delete { sys_id }
 * POST action=status { sys_id, status }
 * POST action=save_image { sys_id, image_base64, ratio }
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
require_once '../../server/sys_id_generator_v2.php';
require_once '../../server/generate_meta_data.php';

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';
$body   = [];
if ($method === 'POST') {
    $body   = json_decode(file_get_contents('php://input'), true) ?? [];
    $action = $body['action'] ?? $action;
}
$userName = $_SESSION['user_name'] ?? 'system';

try {
    switch ($action) {

        // ── LIST ──────────────────────────────────────────────
        case 'list':
            $platform = $_GET['platform'] ?? '';
            $status   = $_GET['status']   ?? '';
            $limit    = min((int)($_GET['limit']  ?? 20), 100);
            $offset   = (int)($_GET['offset'] ?? 0);

            $where  = [];
            $params = [];
            if ($platform) { $where[] = 'platform = ?'; $params[] = $platform; }
            if ($status)   { $where[] = 'status = ?';   $params[] = $status;   }
            $whereStr = $where ? 'WHERE ' . implode(' AND ', $where) : '';

            $stmt = $pdo->prepare("SELECT * FROM sm_posts {$whereStr} ORDER BY id DESC LIMIT ? OFFSET ?");
            $stmt->execute([...$params, $limit, $offset]);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Count
            $cStmt = $pdo->prepare("SELECT COUNT(*) FROM sm_posts {$whereStr}");
            $cStmt->execute($params);
            $total = (int)$cStmt->fetchColumn();

            // Decode JSON columns
            foreach ($rows as &$r) {
                foreach (['hashtags','keywords','tips'] as $col)
                    $r[$col] = $r[$col] ? json_decode($r[$col], true) : [];
            }

            ob_clean();
            echo json_encode(['status' => 'success', 'data' => $rows, 'total' => $total]);
            break;

        // ── GET ONE ──────────────────────────────────────────
        case 'get':
            $sysId = $_GET['sys_id'] ?? '';
            if (!$sysId) throw new Exception('sys_id required');
            $stmt = $pdo->prepare("SELECT * FROM sm_posts WHERE sys_id = ? LIMIT 1");
            $stmt->execute([$sysId]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$row) throw new Exception('Not found');
            foreach (['hashtags','keywords','tips'] as $col)
                $row[$col] = $row[$col] ? json_decode($row[$col], true) : [];
            ob_clean();
            echo json_encode(['status' => 'success', 'data' => $row]);
            break;

        // ── SAVE (create or update) ───────────────────────────
        case 'save':
            $sysId = $body['sys_id'] ?? '';
            $isUpdate = !empty($sysId);

            $fields = [
                'platform'     => $body['platform']     ?? 'facebook',
                'tone'         => $body['tone']         ?? null,
                'language'     => $body['language']     ?? null,
                'content_size' => $body['content_size'] ?? null,
                'word_limit'   => (int)($body['word_limit'] ?? 150),
                'temperature'  => (float)($body['temperature'] ?? 0.7),
                'raw_input'    => $body['raw_input']    ?? null,
                'post_text'    => $body['post_text']    ?? null,
                'hook'         => $body['hook']         ?? null,
                'cta'          => $body['cta']          ?? null,
                'hashtags'     => json_encode($body['hashtags']  ?? [], JSON_UNESCAPED_UNICODE),
                'keywords'     => json_encode($body['keywords']  ?? [], JSON_UNESCAPED_UNICODE),
                'tips'         => json_encode($body['tips']      ?? [], JSON_UNESCAPED_UNICODE),
                'has_image'    => isset($body['has_image']) ? (int)$body['has_image'] : 0,
                'image_url'    => $body['image_url']    ?? null,
                'image_prompt' => $body['image_prompt'] ?? null,
                'image_ratio'  => $body['image_ratio']  ?? null,
                'status'       => $body['status']       ?? 'draft',
            ];

            if ($isUpdate) {
                $stmt = $pdo->prepare("SELECT meta_data FROM sm_posts WHERE sys_id = ? LIMIT 1");
                $stmt->execute([$sysId]);
                $existing = $stmt->fetch(PDO::FETCH_ASSOC);
                if (!$existing) throw new Exception('Post not found');
                $meta = buildMetaData($existing['meta_data'], $userName);

                $sets   = implode(', ', array_map(fn($k) => "{$k} = ?", array_keys($fields)));
                $values = [...array_values($fields), $meta, $sysId];
                $pdo->prepare("UPDATE sm_posts SET {$sets}, meta_data = ? WHERE sys_id = ?")
                    ->execute($values);
            } else {
                $ids  = generateV2IDs($pdo, 'sm_posts');
                $meta = buildMetaData(null, $userName);
                $cols = 'uuid, sys_id, ' . implode(', ', array_keys($fields)) . ', meta_data';
                $phs  = '?, ?, ' . implode(', ', array_fill(0, count($fields), '?')) . ', ?';
                $pdo->prepare("INSERT INTO sm_posts ({$cols}) VALUES ({$phs})")
                    ->execute([$ids['uuid'], $ids['sys_id'], ...array_values($fields), $meta]);
                $sysId = $ids['sys_id'];
            }

            ob_clean();
            echo json_encode(['status' => 'success', 'sys_id' => $sysId, 'message' => $isUpdate ? 'Updated' : 'Saved']);
            break;

        // ── SAVE IMAGE (base64 → file) ────────────────────────
        case 'save_image':
            $sysId   = $body['sys_id']       ?? '';
            $b64     = $body['image_base64']  ?? '';
            $ratio   = $body['ratio']         ?? '1:1';
            if (!$sysId || !$b64) throw new Exception('sys_id and image_base64 required');

            // Strip data URL prefix
            $b64 = preg_replace('/^data:image\/\w+;base64,/', '', $b64);
            $bytes = base64_decode($b64);
            if (!$bytes) throw new Exception('Invalid base64 image');

            $dir  = dirname(__DIR__, 2) . '/uploads/sm-images/';
            if (!is_dir($dir)) mkdir($dir, 0775, true);

            $filename = 'sm-' . $sysId . '-' . time() . '.jpg';
            $path     = $dir . $filename;

            if (file_put_contents($path, $bytes) === false)
                throw new Exception('Failed to save image file');

            $imgUrl = '/uploads/sm-images/' . $filename;

            // Update DB
            $stmt = $pdo->prepare("SELECT meta_data FROM sm_posts WHERE sys_id = ? LIMIT 1");
            $stmt->execute([$sysId]);
            $existing = $stmt->fetch(PDO::FETCH_ASSOC);
            $meta = buildMetaData($existing['meta_data'] ?? null, $userName);

            $pdo->prepare("UPDATE sm_posts SET has_image=1, image_url=?, image_ratio=?, meta_data=? WHERE sys_id=?")
                ->execute([$imgUrl, $ratio, $meta, $sysId]);

            ob_clean();
            echo json_encode(['status' => 'success', 'image_url' => $imgUrl]);
            break;

        // ── CHANGE STATUS ────────────────────────────────────
        case 'status':
            $sysId  = $body['sys_id'] ?? '';
            $status = $body['status'] ?? 'draft';
            if (!$sysId) throw new Exception('sys_id required');
            $stmt = $pdo->prepare("SELECT meta_data FROM sm_posts WHERE sys_id = ? LIMIT 1");
            $stmt->execute([$sysId]);
            $existing = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$existing) throw new Exception('Not found');
            $meta = buildMetaData($existing['meta_data'], $userName);
            $pdo->prepare("UPDATE sm_posts SET status=?, meta_data=? WHERE sys_id=?")
                ->execute([$status, $meta, $sysId]);
            ob_clean();
            echo json_encode(['status' => 'success', 'message' => 'Status updated']);
            break;

        // ── DELETE ───────────────────────────────────────────
        case 'delete':
            $sysId = $body['sys_id'] ?? '';
            if (!$sysId) throw new Exception('sys_id required');
            $pdo->prepare("DELETE FROM sm_posts WHERE sys_id = ?")->execute([$sysId]);
            ob_clean();
            echo json_encode(['status' => 'success', 'message' => 'Deleted']);
            break;

        default:
            throw new Exception("Unknown action: '{$action}'");
    }

} catch (Exception $e) {
    ob_clean();
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}