<?php
require '../../server/db_connection.php';
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// get POST data (JSON)
$input = json_decode(file_get_contents('php://input'), true);

$rootPath = $_SERVER['DOCUMENT_ROOT'];


if (!$input) {
    echo json_encode(['success' => false, 'message' => 'No data received']);
    exit;
}

// extract values
$rawClient = $input['client'] ?? null;

$parts = explode('|', $rawClient);

// ID always first part
$clientId = trim($parts[0]);
$workTitle = $input['work_title'] ?? null;
$clientFolderName = trim(str_replace(' ', '',$parts[1])).trim(str_replace('+', '', $parts[2]));

$folderDirectory = $rootPath . '/storage/clients/'.$clientFolderName;
if (!is_dir($folderDirectory)) {
    mkdir($folderDirectory, 0755, true);
}

$workDirectory = $folderDirectory."/".$workTitle;

// validation
if (!$rawClient) {
    echo json_encode(['success' => false, 'message' => 'Client missing']);
    exit;
}
if (!$workTitle) {
    echo json_encode(['success' => false, 'message' => 'Work Title missing']);
    exit;
}

try {
    // 1. Get the existing info
    $gettingClientPreviousWork = $pdo->prepare("SELECT work_name FROM clients WHERE id = ?");
    $gettingClientPreviousWork->execute([$clientId]);
    $previousWorks = $gettingClientPreviousWork->fetch(PDO::FETCH_ASSOC);

    if ($previousWorks) {
        // 2. Combine the old and new data
        $oldWork = $previousWorks['work_name'];
        $updatedWorkName = empty($oldWork) ? $workTitle : $oldWork . ", " . $workTitle;

        // 3. UPDATE the database
        $updateStmt = $pdo->prepare("UPDATE clients SET work_name = ? WHERE id = ?");
        $updateStmt->execute([$updatedWorkName, $clientId]);

        mkdir($workDirectory, 0755, true);

        echo json_encode(['success' => true, 'message' => 'Work updated successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Client not found in DB!']);
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
