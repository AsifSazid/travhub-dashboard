<?php
require '../../server/db_connection.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

error_reporting(E_ALL);
ini_set('display_errors', 1);

$taskId = $_GET['task'] ?? null;

if (!$taskId) {
    echo json_encode([
        'success' => false,
        'message' => 'Task ID is required'
    ]);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    echo json_encode([
        'success' => false,
        'message' => 'No data received'
    ]);
    exit;
}

try {
    // Get existing task
    $stmt = $pdo->prepare("SELECT * FROM old_tasks WHERE sys_id = ? OR uuid = ?");
    $stmt->execute([$taskId, $taskId]);
    $task = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$task) {
        echo json_encode([
            'success' => false,
            'message' => 'Task not found'
        ]);
        exit;
    }

    // Decode existing hotel_info (should be an array like ["{ json string }"])
    $hotelInfo = [];
    if (!empty($task['hotel_info'])) {
        $hotelInfo = json_decode($task['hotel_info'], true);
    }

    // Index 0 তে যে JSON string আছে সেটা parse করো
    $innerData = [];
    if (!empty($hotelInfo[0])) {
        $innerData = json_decode($hotelInfo[0], true) ?? [];
    }

    // Inner data তে নতুন input merge করো
    foreach ($input as $key => $value) {
        $innerData[$key] = $value;
    }

    // আবার JSON string বানাও এবং index 0 তে রাখো
    $hotelInfo[0] = json_encode($innerData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

    // Save to database
    $updateStmt = $pdo->prepare("
        UPDATE old_tasks 
        SET hotel_info = ?
        WHERE sys_id = ? OR uuid = ?
    ");

    $success = $updateStmt->execute([
        json_encode($hotelInfo, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
        $taskId,
        $taskId
    ]);

    if ($success) {
        echo json_encode([
            'success' => true,
            'message' => 'Hotel information updated successfully',
            'hotel_json' => $hotelInfo
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Failed to update task'
        ]);
    }

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?>