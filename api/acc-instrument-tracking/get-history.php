<?php
session_start();
require '../../server/db_connection.php';          // $pdo
header('Content-Type: application/json');

try {
    if (!isset($_GET['sys_id'])) {
        throw new Exception('Instrument sys_id is required');
    }
    
    $stmt = $pdo->prepare("SELECT history FROM ac_instrument_tracking WHERE sys_id = ?");
    $stmt->execute([$_GET['sys_id']]);
    $data = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$data) {
        throw new Exception('Instrument not found');
    }
    
    $history = [];
    if ($data['history']) {
        $history = json_decode($data['history'], true);
        if (!is_array($history)) {
            $history = [];
        }
    }
    
    echo json_encode([
        'success' => true,
        'history' => $history
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>