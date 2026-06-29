<?php
// delete-task.php
require '../../server/db_connection.php'; // Your PDO connection file

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Get POST data
$data = json_decode(file_get_contents('php://input'), true);
$task_id = $data['task_id'] ?? null;

// Validate task_id
if (!$task_id) {
    echo json_encode(['success' => false, 'message' => 'Task ID is required']);
    exit;
}

try {
    // First, get task details to find associated files (optional - if you want to delete files)
    $stmt = $pdo->prepare("SELECT all_file_name, work_sys_id, sys_id FROM old_tasks WHERE sys_id = ?");
    $stmt->execute([$task_id]);
    $task = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($task) {
        $stmt = $pdo->prepare("SELECT sys_id FROM financial_entries WHERE task_sys_id = ?");
        $stmt->execute([$task_id]);
        $finEntities = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if($finEntities){
            echo json_encode(['success' => false, 'message' => 'Can\'t Delete! There are financial Transaction in this Task...']);
            exit;
        }
        
        
        // Delete associated files if needed (optional)
        $files = json_decode($task['all_file_name'], true);
        if (is_array($files) && !empty($files)) {
            foreach ($files as $file) {
                // Construct full file path based on your structure
                $workSysId = $task['work_sys_id'];
                $taskSysId = $task['sys_id'];
                
                // Adjust this path based on your folder structure
                $filePath = "../../clients/*/{$workSysId}/tasks/{$taskSysId}/{$file}";
                // You might need to glob or find exact path
                
                if (file_exists($filePath)) {
                    unlink($filePath); // Delete file
                }
            }
            
            // Also delete the task directory if empty
            $taskDir = "../../clients/*/{$workSysId}/tasks/{$taskSysId}";
            if (is_dir($taskDir)) {
                rmdir($taskDir); // This will only work if directory is empty
            }
        }
    }
    
    // Delete the task from database
    $stmt = $pdo->prepare("DELETE FROM old_tasks WHERE sys_id = ?");
    $stmt->execute([$task_id]);
    
    if ($stmt->rowCount() > 0) {
        echo json_encode([
            'success' => true, 
            'message' => 'Task deleted successfully'
        ]);
    } else {
        echo json_encode([
            'success' => false, 
            'message' => 'Task not found or already deleted'
        ]);
    }
    
} catch (PDOException $e) {
    echo json_encode([
        'success' => false, 
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?>