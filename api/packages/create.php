<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// include_once('../../authenticate.php');
require_once('../../server/db_connection.php');
require_once('../../server/uuid_with_system_id_generator.php');
require_once('../../server/generate_meta_data.php');
require_once('../../server/make-dir.php');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
if (!$input) $input = $_POST;

$title = trim($input['title'] ?? '');
if (empty($title)) {
    echo json_encode(['success' => false, 'message' => 'Title is required']);
    exit;
}

$description = trim($input['description'] ?? '');
$rating = intval($input['rating'] ?? 0);

try {
    $ids = generateIDs('packages');
    $uuid  = $ids['uuid'];
    $sys_id = $ids['sys_id'];

    $metaData = buildMetaData(null, $_SESSION['user_name'] ?? 'system');

    makeDir('packages', $sys_id);

    // $pdo = getDBConnection();
    $stmt = $pdo->prepare("
        INSERT INTO packages (uuid, sys_id, title, description, rating, progress_step, completion_status, status, meta_data)
        VALUES (:uuid, :sys_id, :title, :description, :rating, 1, 'draft', 'active', :meta_data)
    ");
    $stmt->execute([
        ':uuid'        => $uuid,
        ':sys_id'      => $sys_id,
        ':title'       => $title,
        ':description' => $description,
        ':rating'      => $rating,
        ':meta_data'   => json_encode($metaData),
    ]);

    echo json_encode([
        'success' => true,
        'uuid'    => $uuid,
        'sys_id'  => $sys_id,
        'message' => 'Package created successfully',
    ]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}