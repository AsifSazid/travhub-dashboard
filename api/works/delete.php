<?php
// delete-work.php
require '../../server/db_connection.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Get POST data
$data = json_decode(file_get_contents('php://input'), true);
$work_id = $data['work_id'] ?? null;

// Validate work_id
if (!$work_id) {
    echo json_encode(['success' => false, 'message' => 'Work ID is required']);
    exit;
}

try {
    $pdo->beginTransaction();
    
    // First, get work details to find associated files
    $stmt = $pdo->prepare("SELECT sys_id, title FROM works WHERE sys_id = ?");
    $stmt->execute([$work_id]);
    $work = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($work) {
        // Get all tasks under this work to delete their files
        $stmt = $pdo->prepare("SELECT sys_id, all_file_name FROM tasks WHERE work_sys_id = ?");
        $stmt->execute([$work_id]);
        $tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Delete task files
        foreach ($tasks as $task) {
            $files = json_decode($task['all_file_name'], true);
            if (is_array($files)) {
                foreach ($files as $file) {
                    // Adjust path based on your structure
                    $filePath = "../../clients/*/{$work_id}/tasks/{$task['sys_id']}/{$file}";
                    if (file_exists($filePath)) {
                        unlink($filePath);
                    }
                }
                // Remove task directory
                $taskDir = "../../clients/*/{$work_id}/tasks/{$task['sys_id']}";
                if (is_dir($taskDir)) {
                    rmdir($taskDir);
                }
            }
        }
        
        // Delete all tasks under this work
        $stmt = $pdo->prepare("DELETE FROM tasks WHERE work_sys_id = ?");
        $stmt->execute([$work_id]);
        
        // Delete the work
        $stmt = $pdo->prepare("DELETE FROM works WHERE sys_id = ?");
        $stmt->execute([$work_id]);
        
        // Remove work directory
        $workDir = "../../clients/*/{$work_id}";
        if (is_dir($workDir)) {
            // Delete work files if any
            array_map('unlink', glob("$workDir/*"));
            rmdir($workDir);
        }
        
        $pdo->commit();
        
        echo json_encode([
            'success' => true,
            'message' => 'Work and all associated tasks deleted successfully',
            'deleted_tasks' => count($tasks)
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Work not found'
        ]);
    }
    
} catch (PDOException $e) {
    $pdo->rollBack();
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?>