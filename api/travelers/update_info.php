<?php
/**
 * Update Traveler Information (v2)  —  Information tab save endpoint
 * ==================================================================
 * Saves the new structured-info sections to their dedicated travelers columns.
 * Mirrors the contract of api/travelers/update.php:
 *   POST JSON: { traveler_id, category, data }
 *
 * Supported categories (each maps to one travelers column):
 *   personal_info | family_info | employment_info | educational_info
 *   work_info | others_info
 *
 * Passport / NID / Travel History sections are NOT handled here — the
 * Information tab edits those through the EXISTING update.php
 * (passport_info / nid_info) and the existing travel_history flow, so we
 * don't duplicate or fight that logic.
 *
 * Save model: SECTION SAVE. The form sends the whole section object for a
 * category; we merge it onto whatever is stored (so partial sends are safe),
 * then append an audit entry to meta_data.
 */

session_start();
require '../../server/db_connection.php';
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
$category   = $data['category'] ?? null;
$updateData = $data['data'] ?? null;

if (!$travelerId || !$category || $updateData === null) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields (traveler_id, category, data)']);
    exit;
}

// category -> column whitelist (prevents arbitrary column writes)
$columnMap = [
    'personal_info'    => 'personal_info',
    'family_info'      => 'family_info',
    'employment_info'  => 'employment_info',
    'educational_info' => 'educational_info',
    'work_info'        => 'work_info',
    'others_info'      => 'others_info',
];

if (!isset($columnMap[$category])) {
    echo json_encode(['success' => false, 'message' => 'Invalid category']);
    exit;
}

$column = $columnMap[$category];

try {
    // Confirm traveler + read current value of this column AND meta_data
    $stmt = $pdo->prepare("SELECT `{$column}` AS col, meta_data FROM travelers WHERE sys_id = ?");
    $stmt->execute([$travelerId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row) {
        echo json_encode(['success' => false, 'message' => 'Traveler not found']);
        exit;
    }

    // Merge incoming section data over existing (section-save, partial-safe)
    $existing = json_decode($row['col'] ?? '{}', true);
    if (!is_array($existing)) {
        $existing = [];
    }

    // If the client sends a list (e.g. work_info / educational_info as arrays),
    // replace wholesale; if it sends an object, merge key-by-key.
    if (array_is_list($updateData)) {
        $merged = $updateData;
    } else {
        $merged = array_merge($existing, $updateData);
    }

    // Append audit entry
    $metaDataJson = buildMetaData($row['meta_data'] ?? null, $_SESSION['user_name'] ?? 'system');

    $stmt = $pdo->prepare("UPDATE travelers SET `{$column}` = :val, meta_data = :meta WHERE sys_id = :sid");
    $stmt->execute([
        ':val'  => json_encode($merged, JSON_UNESCAPED_UNICODE),
        ':meta' => $metaDataJson,
        ':sid'  => $travelerId,
    ]);

    echo json_encode([
        'success'  => true,
        'message'  => ucfirst(str_replace('_', ' ', $category)) . ' saved',
        'category' => $category,
        'data'     => $merged,
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    error_log('update_info error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}