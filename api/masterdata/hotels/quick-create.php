<?php
/**
 * FILE PATH: /api/masterdata/hotels/quick-create.php
 *
 * Lightweight hotel create — called from create-leads.php "Add New Hotel" modal.
 *
 * POST (JSON):
 *   name            string  required
 *   country_sys_id  string  required
 *   country_name    string
 *   city_sys_id     string
 *   city_name       string
 *   star_rating     int     1–5
 *   address         string
 *   phone           string
 *   email           string
 *
 * Response:
 *   { status: 'ok', hotel: { sys_id, name, city_name, country_name, star_rating } }
 *   { status: 'error', message: '...' }
 */

session_start();
require_once '../../../server/api_bootstrap.php';
require_once '../../../server/db_connection.php';
require_once '../../../server/id_generator.php';
require_once '../../../server/generate_meta_data.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'POST only']);
    exit;
}

$in = json_decode(file_get_contents('php://input'), true) ?? [];

$name           = trim($in['name']           ?? '');
$country_sys_id = trim($in['country_sys_id'] ?? '');
$country_name   = trim($in['country_name']   ?? '');
$city_sys_id    = trim($in['city_sys_id']    ?? '');
$city_name      = trim($in['city_name']      ?? '');
$star_rating    = isset($in['star_rating']) ? max(1, min(5, (int)$in['star_rating'])) : null;
$address        = trim($in['address']        ?? '');
$phone          = trim($in['phone']          ?? '');
$email          = trim($in['email']          ?? '');

if (!$name) {
    echo json_encode(['status' => 'error', 'message' => 'Hotel name required']);
    exit;
}
if (!$country_sys_id) {
    echo json_encode(['status' => 'error', 'message' => 'country_sys_id required']);
    exit;
}

try {
    $ids  = generateIDs($pdo, 'hotels');
    $meta = buildMetaData(null, $_SESSION['user_name'] ?? 'system');

    $pdo->prepare("
        INSERT INTO hotels
            (uuid, sys_id, country_sys_id, country_name, city_sys_id, city_name,
             name, star_rating, address, phone, email,
             amenities, images, for_umrah, status, meta_data)
        VALUES
            (?, ?, ?, ?, ?, ?,
             ?, ?, ?, ?, ?,
             '[]', '[]', 0, 'active', ?)
    ")->execute([
        $ids['uuid'], $ids['sys_id'],
        $country_sys_id, $country_name ?: null,
        $city_sys_id    ?: null, $city_name ?: null,
        $name, $star_rating,
        $address ?: null, $phone ?: null, $email ?: null,
        $meta,
    ]);

    // Sync hotels JSON cache if helper exists
    if (function_exists('syncHotelsJson')) {
        syncHotelsJson($pdo);
    } else {
        @include_once '../../../server/json-sync-helper.php';
        if (function_exists('syncHotelsJson')) syncHotelsJson($pdo);
    }

    echo json_encode([
        'status' => 'ok',
        'hotel'  => [
            'sys_id'       => $ids['sys_id'],
            'name'         => $name,
            'city_name'    => $city_name    ?: null,
            'country_name' => $country_name ?: null,
            'star_rating'  => $star_rating,
        ],
    ], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}