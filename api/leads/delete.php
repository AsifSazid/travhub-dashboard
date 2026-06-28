<?php
// FILE PATH: /api/leads/delete.php
// POST { action: 'soft',      sys_id: '...' }          → soft delete (move to trash)
// POST { action: 'restore',   sys_id: '...' }          → restore from trash
// POST { action: 'permanent', sys_id: '...' }          → permanent delete (single)
// POST { action: 'bulk_permanent', sys_ids: [...] }    → bulk permanent delete

session_start();
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require '../../server/db_connection.php';

$input  = json_decode(file_get_contents('php://input'), true) ?? [];
$action = $input['action'] ?? 'soft';
$sysId  = $input['sys_id']  ?? null;
$sysIds = $input['sys_ids'] ?? [];
$userName = $_SESSION['user_name'] ?? 'system';
$now    = date('Y-m-d H:i:s');

try {
    switch ($action) {

        // ── Soft delete (to trash) ───────────────────────────────────
        case 'soft':
            if (!$sysId) throw new Exception('sys_id is required.');
            $stmt = $pdo->prepare(
                "UPDATE leads SET deleted_at = ?, deleted_by = ? WHERE sys_id = ? AND deleted_at IS NULL"
            );
            $stmt->execute([$now, $userName, $sysId]);
            if ($stmt->rowCount() === 0) throw new Exception('Lead not found or already deleted.');
            echo json_encode(['success' => true, 'message' => 'Lead moved to trash.']);
            break;

        // ── Restore from trash ───────────────────────────────────────
        case 'restore':
            if (!$sysId) throw new Exception('sys_id is required.');
            $stmt = $pdo->prepare(
                "UPDATE leads SET deleted_at = NULL, deleted_by = NULL WHERE sys_id = ? AND deleted_at IS NOT NULL"
            );
            $stmt->execute([$sysId]);
            if ($stmt->rowCount() === 0) throw new Exception('Lead not found in trash.');
            echo json_encode(['success' => true, 'message' => 'Lead restored successfully.']);
            break;

        // ── Bulk restore ─────────────────────────────────────────────
        case 'bulk_restore':
            if (empty($sysIds)) throw new Exception('No leads selected.');
            $ph = implode(',', array_fill(0, count($sysIds), '?'));
            $pdo->prepare("UPDATE leads SET deleted_at=NULL, deleted_by=NULL WHERE sys_id IN ({$ph})")
                ->execute($sysIds);
            echo json_encode(['success' => true, 'message' => count($sysIds) . ' lead(s) restored.']);
            break;

        // ── Permanent delete (single) ────────────────────────────────
        case 'permanent':
            if (!$sysId) throw new Exception('sys_id is required.');
            $stmt = $pdo->prepare("DELETE FROM leads WHERE sys_id = ? AND deleted_at IS NOT NULL");
            $stmt->execute([$sysId]);
            if ($stmt->rowCount() === 0) throw new Exception('Lead not found in trash.');
            echo json_encode(['success' => true, 'message' => 'Lead permanently deleted.']);
            break;

        // ── Bulk permanent delete ────────────────────────────────────
        case 'bulk_permanent':
            if (empty($sysIds)) throw new Exception('No leads selected.');
            $ph = implode(',', array_fill(0, count($sysIds), '?'));
            // Only delete leads that are already in trash (deleted_at IS NOT NULL)
            $params = array_merge($sysIds);
            $pdo->prepare("DELETE FROM leads WHERE sys_id IN ({$ph}) AND deleted_at IS NOT NULL")
                ->execute($params);
            echo json_encode(['success' => true, 'message' => count($sysIds) . ' lead(s) permanently deleted.']);
            break;

        default:
            throw new Exception('Invalid action.');
    }
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}