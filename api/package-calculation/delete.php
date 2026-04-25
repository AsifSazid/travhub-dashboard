<?php
header('Content-Type: application/json');
include_once('../../authenticate.php');
require_once('../../server/db_connection.php');

$input = json_decode(file_get_contents('php://input'), true) ?: [];
if ($_SERVER['REQUEST_METHOD'] === 'GET') $input = $_GET;
$uuid = trim($input['uuid'] ?? '');

if (empty($uuid)) { echo json_encode(['success' => false, 'message' => 'UUID required']); exit; }

try {
    $pdo = getDBConnection();
    $pdo->prepare("DELETE FROM package_calculations WHERE package_uuid = :uuid")->execute([':uuid' => $uuid]);
    echo json_encode(['success' => true, 'message' => 'Calculation deleted']);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}