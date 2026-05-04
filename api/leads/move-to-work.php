<?php
// FILE PATH: /api/leads/move-to-work.php
ob_start();
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require '../../server/db_connection.php';
require '../../server/uuid_with_system_id_generator.php';
require '../../server/generate_meta_data.php';
require '../../server/make-dir.php';
require '../../server/make-smb-dir.php';

date_default_timezone_set('Asia/Dhaka');

$input  = json_decode(file_get_contents('php://input'), true);
$sys_id = $input['sys_id'] ?? null;

if (!$sys_id) {
    ob_clean();
    echo json_encode(['success' => false, 'message' => 'sys_id is required.']);
    exit;
}

try {
    // 1. Fetch the lead
    $stmt = $pdo->prepare("SELECT * FROM leads WHERE sys_id = ?");
    $stmt->execute([$sys_id]);
    $lead = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$lead) {
        ob_clean();
        echo json_encode(['success' => false, 'message' => 'Lead not found.']);
        exit;
    }

    if ($lead['lead_status'] === 'converted') {
        ob_clean();
        echo json_encode(['success' => false, 'message' => 'Lead is already converted.']);
        exit;
    }

    $clientInfo  = json_decode($lead['client_info'], true) ?? [];
    $serviceType = json_decode($lead['service_type'], true) ?? [];
    $serviceData = json_decode($lead['service_data'], true) ?? [];

    $clientName  = $clientInfo['name']   ?? 'Unknown';
    $clientSysId = $clientInfo['sys_id'] ?? null;
    $services    = is_array($serviceType) ? $serviceType : [$serviceType];

    // 2. Create a work entry per service type
    $metaDataJson = buildMetaData(null, $_SESSION['user_name'] ?? 'system');
    $SERVER_CUS_PATH = trim(@file_get_contents('../../server-name.txt') ?? '');

    $createdWorks = [];

    foreach ($services as $svc) {
        $svcData   = $serviceData[$svc] ?? [];
        $workTitle = $clientName . ' - ' . ucfirst($svc);

        $workUuid = generateIDs('works');
        $sysId    = preg_replace('/\s+/u', '', $workUuid['sys_id']);
        $workFolderName = $sysId . '+' . str_replace(' ', '_', $workTitle);

        // Build client folder name if we have sys_id
        if ($clientSysId) {
            $cleanSysId    = preg_replace('/\s+/u', '', $clientSysId);
            $cleanName     = preg_replace('/\s+/u', '', $clientName);
            $clientFolder  = 'clients/' . $cleanSysId . '_' . $cleanName;
            $cloudPath     = $SERVER_CUS_PATH . '_' . $clientFolder;

            makeDir($clientFolder, $workFolderName);
            makeSMBDir($cloudPath, $workFolderName);
        }

        $pdo->prepare("INSERT INTO works (
            uuid, sys_id, client_sys_id, client_name, title, owned_by, work_type, lead_sys_id, meta_data
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)")->execute([
            $workUuid['uuid'],
            $workUuid['sys_id'],
            $clientSysId,
            $clientName,
            $workTitle,
            $_SESSION['user_name'] ?? 'system',
            $svc,
            $sys_id,
            $metaDataJson,
        ]);

        $createdWorks[] = $workUuid['sys_id'];
    }

    // 3. Update lead status to converted
    $pdo->prepare("UPDATE leads SET lead_status = 'converted' WHERE sys_id = ?")
        ->execute([$sys_id]);

    ob_clean();
    echo json_encode([
        'success'       => true,
        'status'        => 'success',
        'message'       => 'Lead successfully moved to workboard.',
        'created_works' => $createdWorks,
    ]);

} catch (PDOException $e) {
    ob_clean();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}