<?php
/**
 * Batch Create API  —  TravHub v2 Document Intelligence Pipeline (Step 2)
 *
 * Creates an empty `batches` shell row for an upload session.
 * documents / summary / summary_info are NULL at creation; they are filled
 * in later by batch_update.php after the per-file Gemini loop completes.
 *
 * Input  (JSON body):  { "traveler_id": "THR-TR-26-00K001" }
 * Output (JSON):        { success, message, batch: { uuid, sys_id, traveler_id } }
 */

session_start();
require '../../server/db_connection.php';
require '../../server/uuid_with_system_id_generator.php';
require '../../server/generate_meta_data.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
error_reporting(E_ALL);
ini_set('display_errors', 1);

$data = json_decode(file_get_contents('php://input'), true);

if (!$data) {
    echo json_encode(['success' => false, 'message' => 'No data received']);
    exit;
}

$travelerId = $data['traveler_id'] ?? null;

if (!$travelerId) {
    echo json_encode(['success' => false, 'message' => 'traveler_id is required']);
    exit;
}

try {
    // Confirm the traveler exists before opening a batch against it
    $stmt = $pdo->prepare("SELECT sys_id FROM travelers WHERE sys_id = ?");
    $stmt->execute([$travelerId]);
    if (!$stmt->fetchColumn()) {
        echo json_encode(['success' => false, 'message' => 'Traveler not found']);
        exit;
    }

    $ids          = generateIDs('batches'); // ['uuid' => ..., 'sys_id' => 'THR-BT-...']
    $metaDataJson = buildMetaData(null, $_SESSION['user_name'] ?? 'system');

    $stmt = $pdo->prepare("
        INSERT INTO batches (uuid, sys_id, traveler_id, documents, summary, summary_info, meta_data)
        VALUES (:uuid, :sys_id, :traveler_id, NULL, NULL, NULL, :meta_data)
    ");
    $stmt->execute([
        ':uuid'        => $ids['uuid'],
        ':sys_id'      => $ids['sys_id'],
        ':traveler_id' => $travelerId,
        ':meta_data'   => $metaDataJson,
    ]);

    echo json_encode([
        'success' => true,
        'message' => 'Batch created',
        'batch'   => [
            'uuid'        => $ids['uuid'],
            'sys_id'      => $ids['sys_id'],
            'traveler_id' => $travelerId,
        ],
    ]);

} catch (Exception $e) {
    error_log('batch_create error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}