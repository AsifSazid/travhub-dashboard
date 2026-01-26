<?php
require '../../server/db_connection.php';
require '../../server/uuid_with_system_id_generator.php';
require '../../server/generate_meta_data.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Handle preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Get JSON input
$data = json_decode(file_get_contents('php://input'), true);

if (!$data) {
    echo json_encode(['success' => false, 'message' => 'No data received']);
    exit;
}

/* =========================
   BASIC VALIDATION
========================= */

if (empty($data['traveler_name'])) {
    echo json_encode(['success' => false, 'message' => 'Traveler name is required']);
    exit;
}

if (empty($data['hotel_details']) || !is_array($data['hotel_details'])) {
    echo json_encode(['success' => false, 'message' => 'Hotel details are required']);
    exit;
}

if (empty($data['staying_details']) || !is_array($data['staying_details'])) {
    echo json_encode(['success' => false, 'message' => 'Staying details are required']);
    exit;
}

try {
    /* =========================
       GENERATE IDS
    ========================= */

    $uuid = generateIDs('hotel_bookings'); // booking_ref source
    $bookingRef = $uuid['sys_id'];
    
    // var_dump($uuid['sys_id']);
    // die;

    /* =========================
       PREPARE DATA
    ========================= */

    $hotelDetailsJson  = json_encode($data['hotel_details'], JSON_UNESCAPED_UNICODE);
    $guestDetailsJson  = !empty($data['guest_details']) 
                            ? json_encode($data['guest_details'], JSON_UNESCAPED_UNICODE)
                            : null;

    $stayingDetailsJson = json_encode($data['staying_details'], JSON_UNESCAPED_UNICODE);

    /* =========================
       INSERT QUERY
    ========================= */

    $stmt = $pdo->prepare("
        INSERT INTO hotel_bookings (
            booking_ref,
            uuid,
            sys_id,
            hotel_details,
            guest_details,
            traveler_sys_id,
            traveler_name,
            staying_details,
            pcn,
            hcn,
            notes
        ) VALUES (
            :booking_ref,
            :uuid,
            :sys_id,
            :hotel_details,
            :guest_details,
            :traveler_sys_id,
            :traveler_name,
            :staying_details,
            :pcn,
            :hcn,
            :notes
        )
    ");

    $stmt->execute([
        ':booking_ref'      => $bookingRef,
        ':uuid'             => $uuid['uuid'],
        ':sys_id'           => $uuid['sys_id'],
        ':hotel_details'    => $hotelDetailsJson,
        ':guest_details'    => $guestDetailsJson,
        ':traveler_sys_id'  => $data['traveler_sys_id'] ?? null,
        ':traveler_name'    => $data['traveler_name'],
        ':staying_details'  => $stayingDetailsJson,
        ':pcn'              => $data['pcn'] ?? null,
        ':hcn'              => $data['hcn'] ?? null,
        ':notes'            => $data['notes'] ?? null
    ]);

    $bookingId = $pdo->lastInsertId();

    echo json_encode([
        'success' => true,
        'message' => 'Hotel booking saved successfully',
        'booking_id' => $bookingId,
        'booking_ref' => $bookingRef
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database error',
        'error' => $e->getMessage()
    ]);
}
