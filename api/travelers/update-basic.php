<?php
/**
 * FILE PATH: api/travelers/update-basic.php
 * Update basic traveler info fields (address, phone, email, personal_info, family_info, employment_info)
 * Called from ww-visa Applicant Info "Save Info" — with audit note.
 *
 * POST JSON:
 *   sys_id, address, phone, email,
 *   personal_info, family_info, employment_info,
 *   audit_note (string — recorded in meta_data.audit_log)
 */

require_once '../../server/api_bootstrap.php';
require_once '../../server/db_connection.php';
header('Content-Type: application/json');

function ubErr(string $msg): never {
    echo json_encode(['status' => 'error', 'message' => $msg]);
    exit;
}

try {
    $body   = json_decode(file_get_contents('php://input'), true) ?? [];
    $action = $body['action'] ?? '';

    if ($action !== 'update_basic') ubErr('Invalid action');

    $sysId = trim($body['sys_id'] ?? '');
    if (!$sysId) ubErr('sys_id required');

    // Fetch existing
    $stmt = $pdo->prepare("SELECT sys_id, meta_data FROM travelers WHERE sys_id = ? LIMIT 1");
    $stmt->execute([$sysId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row) ubErr('Traveler not found');

    $meta = json_decode($row['meta_data'] ?? '{}', true) ?: [];

    // Append audit log
    $auditNote = trim($body['audit_note'] ?? '');
    if ($auditNote) {
        if (!isset($meta['audit_log'])) $meta['audit_log'] = [];
        $meta['audit_log'][] = [
            'note' => $auditNote,
            'at'   => date('Y-m-d H:i:s'),
        ];
        // Keep last 20 entries
        if (count($meta['audit_log']) > 20) {
            $meta['audit_log'] = array_slice($meta['audit_log'], -20);
        }
    }

    // Build update fields
    $updates = ['meta_data = ?'];
    $params  = [json_encode($meta, JSON_UNESCAPED_UNICODE)];

    if (!empty($body['address'])) {
        $updates[] = 'address = ?';
        $params[]  = $body['address'];
    }
    if (!empty($body['phone'])) {
        $updates[] = 'phone = ?';
        $params[]  = $body['phone'];
    }
    if (!empty($body['email'])) {
        $updates[] = 'email = ?';
        $params[]  = $body['email'];
    }
    if (!empty($body['personal_info'])) {
        // Merge with existing
        $existing = json_decode(
            $pdo->query("SELECT personal_info FROM travelers WHERE sys_id = " . $pdo->quote($sysId))->fetchColumn() ?? '{}',
            true
        ) ?: [];
        $merged = array_merge($existing, json_decode($body['personal_info'], true) ?: []);
        $updates[] = 'personal_info = ?';
        $params[]  = json_encode($merged, JSON_UNESCAPED_UNICODE);
    }
    if (!empty($body['family_info'])) {
        $existing = json_decode(
            $pdo->query("SELECT family_info FROM travelers WHERE sys_id = " . $pdo->quote($sysId))->fetchColumn() ?? '{}',
            true
        ) ?: [];
        $merged = array_merge($existing, json_decode($body['family_info'], true) ?: []);
        $updates[] = 'family_info = ?';
        $params[]  = json_encode($merged, JSON_UNESCAPED_UNICODE);
    }
    if (!empty($body['employment_info'])) {
        $existing = json_decode(
            $pdo->query("SELECT employment_info FROM travelers WHERE sys_id = " . $pdo->quote($sysId))->fetchColumn() ?? '{}',
            true
        ) ?: [];
        $merged = array_merge($existing, json_decode($body['employment_info'], true) ?: []);
        $updates[] = 'employment_info = ?';
        $params[]  = json_encode($merged, JSON_UNESCAPED_UNICODE);
    }

    $params[] = $sysId;
    $sql = "UPDATE travelers SET " . implode(', ', $updates) . " WHERE sys_id = ?";
    $pdo->prepare($sql)->execute($params);

    echo json_encode(['status' => 'success', 'message' => 'Traveler profile updated']);

} catch (Throwable $e) {
    ubErr($e->getMessage());
}