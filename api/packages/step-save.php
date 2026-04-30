<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// include_once('../../authenticate.php');
require_once('../../server/db_connection.php');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
if (!$input) $input = $_POST;

$uuid        = trim($input['uuid'] ?? '');
$step_number = intval($input['step_number'] ?? 0);
$step_data   = $input['step_data'] ?? [];

if (empty($uuid) || $step_number < 1 || $step_number > 8) {
    echo json_encode(['success' => false, 'message' => 'Invalid parameters']);
    exit;
}

try {
    // $pdo = getDBConnection();

    // Verify package exists
    $check = $pdo->prepare("SELECT id, progress_step FROM packages WHERE uuid = :uuid AND status != 'deleted'");
    $check->execute([':uuid' => $uuid]);
    $pkg = $check->fetch(PDO::FETCH_ASSOC);

    if (!$pkg) {
        echo json_encode(['success' => false, 'message' => 'Package not found']);
        exit;
    }

    $updateFields = [];
    $params = [':uuid' => $uuid];

    switch ($step_number) {
        case 1:
            if (isset($step_data['title'])) {
                $updateFields[] = 'title = :title';
                $params[':title'] = $step_data['title'];
            }
            if (isset($step_data['description'])) {
                $updateFields[] = 'description = :description';
                $params[':description'] = $step_data['description'];
            }
            if (isset($step_data['rating'])) {
                $updateFields[] = 'rating = :rating';
                $params[':rating'] = intval($step_data['rating']);
            }
            if (isset($step_data['image'])) {
                $updateFields[] = 'image = :image';
                $params[':image'] = $step_data['image'];
            }
            break;

        case 2:
            if (isset($step_data['countries'])) {
                $updateFields[] = 'countries = :countries';
                $params[':countries'] = json_encode($step_data['countries']);
            }
            if (isset($step_data['cities'])) {
                $updateFields[] = 'cities = :cities';
                $params[':cities'] = json_encode($step_data['cities']);
            }
            if (isset($step_data['activities'])) {
                $updateFields[] = 'activities = :activities';
                $params[':activities'] = json_encode($step_data['activities']);
            }
            break;

        case 3:
            if (isset($step_data['duration'])) {
                $updateFields[] = 'duration = :duration';
                $params[':duration'] = $step_data['duration'];
            }
            if (isset($step_data['start_date'])) {
                $updateFields[] = 'start_date = :start_date';
                $params[':start_date'] = $step_data['start_date'];
            }
            if (isset($step_data['end_date'])) {
                $updateFields[] = 'end_date = :end_date';
                $params[':end_date'] = $step_data['end_date'];
            }
            if (isset($step_data['no_of_pax'])) {
                $updateFields[] = 'no_of_pax = :no_of_pax';
                $params[':no_of_pax'] = json_encode($step_data['no_of_pax']);
            }
            break;

        case 4:
            if (isset($step_data['hotels'])) {
                $updateFields[] = 'hotels = :hotels';
                $params[':hotels'] = json_encode($step_data['hotels']);
            }
            break;

        case 5:
            // Step 5 = Itinerary
            if (isset($step_data['pack_itenaries'])) {
                $updateFields[] = 'pack_itenaries = :pack_itenaries';
                $params[':pack_itenaries'] = json_encode($step_data['pack_itenaries']);
            }
            break;

        case 6:
            // Step 6 = Pricing
            if (isset($step_data['currency_title'])) {
                $updateFields[] = 'currency_title = :currency_title';
                $params[':currency_title'] = $step_data['currency_title'];
            }
            if (isset($step_data['currency_code'])) {
                $updateFields[] = 'currency_code = :currency_code';
                $params[':currency_code'] = $step_data['currency_code'];
            }
            if (isset($step_data['currency_symbol'])) {
                $updateFields[] = 'currency_symbol = :currency_symbol';
                $params[':currency_symbol'] = $step_data['currency_symbol'];
            }
            if (isset($step_data['overall_price'])) {
                $updateFields[] = 'overall_price = :overall_price';
                $params[':overall_price'] = floatval($step_data['overall_price']);
            }
            if (isset($step_data['air_ticket_details'])) {
                $updateFields[] = 'air_ticket_details = :air_ticket_details';
                $params[':air_ticket_details'] = $step_data['air_ticket_details'];
            }
            if (isset($step_data['pack_price'])) {
                $updateFields[] = 'pack_price = :pack_price';
                $params[':pack_price'] = json_encode($step_data['pack_price']);
            }
            break;

        case 7:
            if (isset($step_data['pack_inclusions'])) {
                $updateFields[] = 'pack_inclusions = :pack_inclusions';
                $params[':pack_inclusions'] = json_encode($step_data['pack_inclusions']);
            }
            if (isset($step_data['pack_exclusions'])) {
                $updateFields[] = 'pack_exclusions = :pack_exclusions';
                $params[':pack_exclusions'] = json_encode($step_data['pack_exclusions']);
            }
            break;

        case 8:
            // Step 8 = Review — just mark as saved
            $updateFields[] = "completion_status = 'saved'";
            break;
    }

    // Update progress step if moving forward
    if ($step_number > $pkg['progress_step']) {
        $updateFields[] = 'progress_step = :progress_step';
        $params[':progress_step'] = $step_number;
    }

    if (empty($updateFields)) {
        echo json_encode(['success' => true, 'message' => 'Nothing to update']);
        exit;
    }

    $sql = 'UPDATE packages SET ' . implode(', ', $updateFields) . ' WHERE uuid = :uuid';
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    echo json_encode(['success' => true, 'message' => 'Step saved successfully', 'step' => $step_number]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}