<?php
require '../../server/db_connection.php';
require '../../server/uuid_with_system_id_generator.php';
require '../../server/generate_meta_data.php';
require '../../server/make-dir.php';
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');



// get POST data (JSON)
$input = json_decode(file_get_contents('php://input'), true);


if (!$input) {
    echo json_encode(['success' => false, 'message' => 'No data received']);
    exit;
}

// extract values
$rawClient = $input['client'] ?? null;
$parts = explode('|', $rawClient);

// ID always first part
$clientSysID = trim($parts[0]);
$clientName = trim($parts[1]);
$workTitle = $input['work_title'] ?? null;

$cleanSysId = preg_replace('/\s+/u', '', $clientSysID);
$cleanFullName = preg_replace('/\s+/u', '', $clientName);
$clientFolderName = 'clients/' . $cleanSysId . '_' . $cleanFullName;

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
    $gettingClientPreviousWork = $pdo->prepare("SELECT work_name FROM clients WHERE sys_id = ?");
    $gettingClientPreviousWork->execute([$clientSysID]);
    $previousWorks = $gettingClientPreviousWork->fetch(PDO::FETCH_ASSOC);

    if ($previousWorks) {
        // 2. Combine the old and new data
        $oldWork = $previousWorks['work_name'];
        $updatedWorkName = empty($oldWork) ? $workTitle : $oldWork . ", " . $workTitle;

        // 3. UPDATE the database
        $updateStmt = $pdo->prepare("UPDATE clients SET work_name = ? WHERE sys_id = ?");
        $updateStmt->execute([$updatedWorkName, $clientSysID]);

        $uuid = generateIDs('works');

        $metaDataJson = buildMetaData(
            null,
            $_SESSION['user_name'] ?? 'system'
        );
        
        $sysId = preg_replace('/\s+/u', '', $uuid['sys_id']);
        // $workFolderName = $sysId . '+' . str_replace(' ', '_', $workTitle);

        makeDir($clientFolderName, $sysId);

        $workStoreSql = "INSERT INTO works (
                uuid,
                sys_id,
                file_name, 
                client_sys_id, 
                client_name, 
                title,
                meta_data
            ) VALUES (?, ?, ?, ?, ?, ?, ?)";

        $workStoreStmt = $pdo->prepare($workStoreSql);

        $workStoreStmt->execute([
            $uuid['uuid'],
            $uuid['sys_id'],
            isset($workFileName) ? $workFileName : null,
            isset($clientSysID) ? $clientSysID : null,
            isset($clientName) ? $clientName : null,
            isset($workTitle) ? $workTitle : null,
            isset($metaDataJson) ? $metaDataJson : null
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
