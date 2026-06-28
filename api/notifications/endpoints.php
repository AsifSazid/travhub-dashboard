<?php
/**
 * FILE PATH: /api/notifications/endpoints.php
 *
 * GET  ?action=list&user=EMP_SYS_ID&dept=DEPT_SYS_ID&limit=20
 *       → list notifications for a specific user OR dept
 * GET  ?action=unread_count&user=EMP_SYS_ID&dept=DEPT_SYS_ID
 * POST action=mark_read   { sys_id }
 * POST action=mark_all    { user_sys_id?, department_sys_id? }
 *
 * Filter logic:
 *  - user=X      → recipient_type='user' AND user_sys_id=X
 *  - dept=X      → recipient_type='department' AND department_sys_id=X
 *  - both given  → OR (user=X OR dept=Y)
 *  - neither     → all (admin view)
 */

ob_start();
session_start();
date_default_timezone_set('Asia/Dhaka');

require_once '../../server/api_bootstrap.php';
require_once '../../server/db_connection.php';

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';
$body   = [];

if ($method === 'POST') {
    $body   = json_decode(file_get_contents('php://input'), true) ?? [];
    $action = $body['action'] ?? $action;
}

// Current user from session
$sessionUser = $_SESSION['user_sys_id'] ?? $_SESSION['user_name'] ?? '';

try {
    switch ($action) {

        // ── LIST ─────────────────────────────────────────────
        case 'list':
            $userSysId  = $_GET['user']  ?? $sessionUser;
            $deptSysId  = $_GET['dept']  ?? '';
            $limit      = min((int)($_GET['limit'] ?? 30), 100);
            $onlyUnread = $_GET['unread'] ?? '';

            $params = [];
            $where  = [];

            // Build OR condition: user-level OR dept-level
            $orClauses = [];
            if ($userSysId) {
                $orClauses[] = "(recipient_type='user' AND user_sys_id=?)";
                $params[]    = $userSysId;
            }
            if ($deptSysId) {
                $orClauses[] = "(recipient_type='department' AND department_sys_id=?)";
                $params[]    = $deptSysId;
            }

            $whereStr = '';
            if (!empty($orClauses)) {
                $whereStr = 'WHERE (' . implode(' OR ', $orClauses) . ')';
            }

            if ($onlyUnread === '1') {
                $whereStr .= ($whereStr ? ' AND' : 'WHERE') . ' is_read=0';
            }

            $sql = "SELECT * FROM notifications {$whereStr} ORDER BY id DESC LIMIT ?";
            $params[] = $limit;

            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($rows as &$r) {
                $r['meta_data'] = $r['meta_data'] ? json_decode($r['meta_data'], true) : [];
            }

            ob_clean();
            echo json_encode(['status' => 'success', 'data' => $rows, 'count' => count($rows)]);
            break;

        // ── UNREAD COUNT ──────────────────────────────────────
        case 'unread_count':
            $userSysId = $_GET['user'] ?? $sessionUser;
            $deptSysId = $_GET['dept'] ?? '';

            $orClauses = [];
            $params    = [0]; // is_read=0
            $sql       = "SELECT COUNT(*) FROM notifications WHERE is_read=?";

            if ($userSysId) {
                $orClauses[] = "(recipient_type='user' AND user_sys_id=?)";
                $params[]    = $userSysId;
            }
            if ($deptSysId) {
                $orClauses[] = "(recipient_type='department' AND department_sys_id=?)";
                $params[]    = $deptSysId;
            }

            if (!empty($orClauses)) {
                $sql .= ' AND (' . implode(' OR ', $orClauses) . ')';
            }

            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $count = (int)$stmt->fetchColumn();

            ob_clean();
            echo json_encode(['status' => 'success', 'count' => $count]);
            break;

        // ── MARK ONE READ ─────────────────────────────────────
        case 'mark_read':
            $sysId = $body['sys_id'] ?? '';
            if (!$sysId) throw new Exception('sys_id required');
            $pdo->prepare("UPDATE notifications SET is_read=1, read_at=? WHERE sys_id=?")
                ->execute([date('d-m-Y H:i'), $sysId]);
            ob_clean();
            echo json_encode(['status' => 'success']);
            break;

        // ── MARK ALL READ ─────────────────────────────────────
        case 'mark_all':
            $userSysId = $body['user_sys_id']      ?? $sessionUser;
            $deptSysId = $body['department_sys_id'] ?? '';

            $orClauses = [];
            $params    = [date('d-m-Y H:i'), 0];
            $sql       = "UPDATE notifications SET is_read=1, read_at=? WHERE is_read=?";

            if ($userSysId) {
                $orClauses[] = "(recipient_type='user' AND user_sys_id=?)";
                $params[]    = $userSysId;
            }
            if ($deptSysId) {
                $orClauses[] = "(recipient_type='department' AND department_sys_id=?)";
                $params[]    = $deptSysId;
            }

            if (!empty($orClauses)) {
                $sql .= ' AND (' . implode(' OR ', $orClauses) . ')';
            }

            $pdo->prepare($sql)->execute($params);
            ob_clean();
            echo json_encode(['status' => 'success', 'message' => 'All marked as read']);
            break;

        default:
            throw new Exception("Unknown action: '{$action}'");
    }

} catch (Exception $e) {
    ob_clean();
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}