<?php
require '../../server/db_connection.php';
require '../../server/uuid_generator.php';
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
$clientUuid = trim($parts[3]);
$clientName = trim($parts[1]);
$workTitle = $input['work_title'] ?? null;
$clientFolderName = trim(str_replace(' ', '', $parts[1])) . trim(str_replace('+', '', $parts[2]));

$folderDirectory = $rootPath . '/storage/clients/' . $clientFolderName;
if (!is_dir($folderDirectory)) {
    mkdir($folderDirectory, 0755, true);
}

$workFileName = str_replace(' ', '_', $workTitle);

$workDirectory = $folderDirectory . "/" . $workFileName;

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

        $uuid = generateUUID();

        $workStoreSql = "INSERT INTO works (
                uuid,
                file_name, 
                client_id, 
                client_uuid, 
                client_name, 
                title,
                work_dir_path 
            ) VALUES (?, ?, ?, ?, ?, ?, ?)";

        $workStoreStmt = $pdo->prepare($workStoreSql);

        $workStoreStmt->execute([
            $uuid,
            isset($workFileName) ? $workFileName : null,
            isset($clientId) ? $clientId : null,
            isset($clientUuid) ? $clientUuid : null,
            isset($clientName) ? $clientName : null,
            isset($workTitle) ? $workTitle : null,
            isset($workDirectory) ? $workDirectory : null
        ]);

        mkdir($workDirectory, 0755, true);
        ob_clean();

        echo json_encode(['success' => true, 'message' => 'Work updated successfully']);
    } else {
        ob_clean();
        echo json_encode(['success' => false, 'message' => 'Client not found in DB!']);
    }
} catch (PDOException $e) {
    ob_clean();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
