<?php
session_start();
require '../../server/db_connection.php';          // $pdo
require '../../server/generate_meta_data.php';
header('Content-Type: application/json');

try {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['id'])) {
        throw new Exception('Instrument ID is required');
    }
    
    // First get existing instrument data
    $getStmt = $pdo->prepare("SELECT sys_id, meta_data FROM ac_instrument_tracking WHERE sys_id = ?");
    $getStmt->execute([$data['sys_id']]);
    $existing = $getStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$existing) {
        throw new Exception('Instrument not found');
    }
    
    // Build new meta data with existing
    $newMetaData = buildMetaData(
        $existing['meta_data'],
        $_SESSION['user_name'] ?? 'system'
    );
    
    // Only update status, remarks, and meta_data
    $stmt = $pdo->prepare("
        UPDATE ac_instrument_tracking 
        SET 
            status = :status,
            remarks = :remarks,
            meta_data = :meta_data,
            updated_at = NOW()
        WHERE sys_id = :sys_id
    ");
    
    $stmt->execute([
        ':status' => $data['status'],
        ':remarks' => $data['remarks'],
        ':meta_data' => $newMetaData,
        ':sys_id' => $data['sys_id']
    ]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Instrument updated successfully'
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>