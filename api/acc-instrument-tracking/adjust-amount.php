<?php
session_start();
require '../../server/db_connection.php';          // $pdo
require '../../server/generate_meta_data.php';
header('Content-Type: application/json');

try {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['sys_id']) || !isset($data['new_amount'])) {
        throw new Exception('Missing required fields');
    }
    
    $pdo->beginTransaction();
    
    // Get existing data using sys_id
    $getStmt = $pdo->prepare("SELECT amount, revised_amount, history, meta_data FROM ac_instrument_tracking WHERE sys_id = ?");
    $getStmt->execute([$data['sys_id']]);
    $currentData = $getStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$currentData) {
        throw new Exception('Instrument not found');
    }
    
    $currentHistory = [];
    if ($currentData['history']) {
        $currentHistory = json_decode($currentData['history'], true);
        if (!is_array($currentHistory)) {
            $currentHistory = [];
        }
    }
    
    // Add new adjustment to history
    $adjustmentRecord = $data['adjustment_record'];
    $adjustmentRecord['adjusted_by'] = $_SESSION['user_name'] ?? 'system';
    
    // Update meta data
    $newMetaData = buildMetaData(
        $currentData['meta_data'],
        $_SESSION['user_name'] ?? 'system'
    );
    
    $currentHistory[] = $adjustmentRecord;
    
    // Update instrument with new amount, history and meta_data
    $stmt = $pdo->prepare("
        UPDATE ac_instrument_tracking 
        SET 
            amount = :amount, 
            revised_amount = :revised_amount,
            history = :history,
            meta_data = :meta_data,
            updated_at = NOW()
        WHERE sys_id = :sys_id
    ");
    
    $stmt->execute([
        ':amount' => $data['new_amount'],
        ':revised_amount' => $data['new_amount'],
        ':history' => json_encode($currentHistory),
        ':meta_data' => $newMetaData,
        ':sys_id' => $data['sys_id']
    ]);
    
    $pdo->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Amount adjusted successfully',
        'new_amount' => $data['new_amount'],
        'history' => $currentHistory
    ]);
    
} catch (Exception $e) {
    $pdo->rollBack();
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>