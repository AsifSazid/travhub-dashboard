<?php
// PATH: /api/eps/toggle-eps-status.php
//
// Flips an eps_structures record between 'active' and 'inactive'.
//
// POST JSON:
//   eps_sys_id  (required) — the sys_id of the eps_structures row to toggle
//
// Response JSON:
//   { success: true,  new_status: "active"|"inactive", message: "..." }
//   { success: false, message: "..." }

session_start();
require_once __DIR__ . '/../../server/db_connection.php';
require_once __DIR__ . '/../../server/permissions.php';

header('Content-Type: application/json; charset=utf-8');

requirePermission($pdo, 'eps_toggle', true);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$body = json_decode(file_get_contents('php://input'), true);
$epsId = trim($body['eps_sys_id'] ?? '');

if (!$epsId) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'eps_sys_id is required']);
    exit;
}

try {
    // Fetch current status
    $stmt = $pdo->prepare("SELECT sys_id, status FROM eps_structures WHERE sys_id = ? LIMIT 1");
    $stmt->execute([$epsId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'EPS structure not found']);
        exit;
    }

    $newStatus = ($row['status'] === 'active') ? 'inactive' : 'active';

    // Update
    $upd = $pdo->prepare("UPDATE eps_structures SET status = ? WHERE sys_id = ?");
    $upd->execute([$newStatus, $epsId]);

    echo json_encode([
        'success'    => true,
        'new_status' => $newStatus,
        'message'    => 'Status updated to ' . $newStatus
    ]);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}