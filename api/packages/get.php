<?php
header('Content-Type: application/json');
// include_once('../../authenticate.php');
require_once('../../server/db_connection.php');

$uuid = trim($_GET['packageId'] ?? '');
if (empty($uuid)) {
    echo json_encode(['success' => false, 'message' => 'Package ID required']);
    exit;
}

try {
    // $pdo = getDBConnection();
    $stmt = $pdo->prepare("SELECT * FROM packages WHERE sys_id = :uuid LIMIT 1");
    $stmt->execute([':uuid' => $uuid]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row) {
        echo json_encode(['success' => false, 'message' => 'Package not found']);
        exit;
    }

    $jsonFields = ['countries','cities','activities','no_of_pax','hotels','pack_price','pack_itenaries','pack_inclusions','pack_exclusions','meta_data', 'package_calculations_details'];
    foreach ($jsonFields as $field) {
        $row[$field] = json_decode($row[$field] ?? 'null', true);
    }

    echo json_encode(['success' => true, 'data' => $row]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}