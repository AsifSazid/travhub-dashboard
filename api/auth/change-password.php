<?php
// PATH: /api/auth/change-password.php
//
// Allows a logged-in user to change their own password.
// Verifies old password against the `login` table, then hashes and stores the new one.
//
// POST JSON: { current_password, new_password, confirm_password }
// Session must be active — uses $_SESSION['user_id'] to find the login record.

session_start();
require_once '../../server/db_connection.php';
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

if (!isset($_SESSION['user_email'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

try {
    $data = json_decode(file_get_contents('php://input'), true);
    if (!$data) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'No data received']);
        exit;
    }

    $currentPassword = $data['current_password'] ?? '';
    $newPassword     = $data['new_password'] ?? '';
    $confirmPassword = $data['confirm_password'] ?? '';

    if (!$currentPassword || !$newPassword || !$confirmPassword) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'All fields are required']);
        exit;
    }

    if ($newPassword !== $confirmPassword) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'New password and confirmation do not match']);
        exit;
    }

    if (strlen($newPassword) < 6) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'New password must be at least 6 characters']);
        exit;
    }

    // Fetch current login record
    $myUserId = $_SESSION['user_id'] ?? '';
    $stmt = $pdo->prepare("SELECT id, password FROM login WHERE user_id = ? LIMIT 1");
    $stmt->execute([$myUserId]);
    $loginRow = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$loginRow) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Login record not found']);
        exit;
    }

    // Verify current password
    if (!password_verify($currentPassword, $loginRow['password'])) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Current password is incorrect']);
        exit;
    }

    // Hash and update new password
    $newHashed = password_hash($newPassword, PASSWORD_DEFAULT);
    $pdo->prepare("UPDATE login SET password = ? WHERE id = ?")
        ->execute([$newHashed, $loginRow['id']]);

    echo json_encode(['success' => true, 'message' => 'Password changed successfully']);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error', 'error' => $e->getMessage()]);
}