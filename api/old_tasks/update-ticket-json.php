<?php
// save_json.php
require '../../server/db_connection.php';
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
error_reporting(E_ALL);
ini_set('display_errors', 1);

// ── 1. Get task_id from GET param ────────────────────────────────────
$taskId = $_GET['task_id'] ?? null;
if (!$taskId) {
    echo json_encode(['success' => false, 'message' => 'Task ID is required']);
    exit;
}

// ── 2. Read & validate POST body ─────────────────────────────────────
$input = json_decode(file_get_contents('php://input'), true);
if (!$input || json_last_error() !== JSON_ERROR_NONE) {
    echo json_encode(['success' => false, 'message' => 'Invalid or empty JSON body']);
    exit;
}

try {
    // ── 3. Find the task ─────────────────────────────────────────────
    $stmt = $pdo->prepare("SELECT * FROM old_tasks WHERE sys_id = ? OR uuid = ? LIMIT 1");
    $stmt->execute([$taskId, $taskId]);
    $task = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$task) {
        echo json_encode(['success' => false, 'message' => 'Task not found']);
        exit;
    }

    // ── 4. Decode the full air_ticket_info from DB ───────────────────────
    //
    // DB structure:
    // {
    //   "0": "{ ...full flight data as JSON string... }",
    //   "air_ticket_info": "[{ booking_details, fare_details }]"  ← JSON string of array
    // }
    //
    $ticketJson = json_decode($task['air_ticket_info'], true);
    if (json_last_error() !== JSON_ERROR_NONE || !is_array($ticketJson)) {
        $ticketJson = [];
    }

    // ── 5. Decode "0" key → full flight data object ──────────────────
    $fullData = [];
    if (isset($ticketJson['0'])) {
        $fullData = is_string($ticketJson['0'])
            ? json_decode($ticketJson['0'], true)
            : $ticketJson['0'];
        if (json_last_error() !== JSON_ERROR_NONE) $fullData = [];
    }

    // ── 6. Decode "air_ticket_info" key → simplified summary object ──
    $airInfo = [];
    if (isset($ticketJson['air_ticket_info'])) {
        $raw = $ticketJson['air_ticket_info'];
        if (is_string($raw)) {
            $raw = json_decode($raw, true); // "[{...}]" → array
        }
        // It's an array with one element: [{...}]
        $airInfo = (isset($raw[0]) && is_array($raw[0])) ? $raw[0] : [];
    }

    // ── 7. Apply incoming edits ──────────────────────────────────────

    // 7a. Booking PNR
    if (!empty($input['booking_details']['booking_reference_pnr'])) {
        $pnr = strip_tags(trim($input['booking_details']['booking_reference_pnr']));
        $fullData['booking_details']['booking_reference_pnr'] = $pnr;
        $airInfo['booking_details']['booking_reference_pnr']  = $pnr;
    }

    // 7b. Passengers (only in "0" — air_ticket_info doesn't store passengers)
    if (!empty($input['passengers']) && is_array($input['passengers'])) {
        foreach ($input['passengers'] as $i => $pax) {
            if (!isset($fullData['passengers'][$i])) continue;

            if (!empty($pax['full_name'])) {
                $name = strip_tags(trim($pax['full_name']));
                $fullData['passengers'][$i]['full_name'] = $name;
                // Keep name breakdown in sync
                $parts = explode(' ', $name, 2);
                $fullData['passengers'][$i]['name']['last']  = $parts[0];
                $fullData['passengers'][$i]['name']['first'] = $parts[1] ?? '';
            }
            if (!empty($pax['ticket_number'])) {
                $fullData['passengers'][$i]['ticket_number'] = strip_tags(trim($pax['ticket_number']));
            }
            if (!empty($pax['type'])) {
                $fullData['passengers'][$i]['type'] = strip_tags(trim($pax['type']));
            }
        }
    }

    // 7c. Fare details — update in BOTH "0" and "air_ticket_info"
    if (!empty($input['fare_details']) && is_array($input['fare_details'])) {
        foreach (['base_fare', 'taxes', 'total_fare'] as $key) {
            if (!isset($input['fare_details'][$key]['amount'])) continue;

            $amount = (float) $input['fare_details'][$key]['amount'];
            if ($amount <= 0) continue; // Never wipe with zero

            $currency = strip_tags(trim($input['fare_details'][$key]['currency'] ?? 'BDT'));

            // Update full data ("0")
            $fullData['fare_details'][$key]['amount']   = $amount;
            $fullData['fare_details'][$key]['currency'] = $currency;

            // Update air_ticket_info summary
            $airInfo['fare_details'][$key]['amount']   = $amount;
            $airInfo['fare_details'][$key]['currency'] = $currency;
        }
    }

    // ── 8. Re-pack into original DB structure ────────────────────────

    // "0" → JSON string of the full object
    $ticketJson['0'] = json_encode(
        $fullData,
        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT
    );

    // "air_ticket_info" → JSON string of array with one object: [{...}]
    $ticketJson['air_ticket_info'] = json_encode(
        [$airInfo],
        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
    );

    // Outer wrapper
    $updatedTicketJson = json_encode(
        $ticketJson,
        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
    );

    // ── 9. Save to DB ────────────────────────────────────────────────
    $update = $pdo->prepare("
        UPDATE old_tasks
        SET    air_ticket_info = ?
        WHERE  sys_id = ? OR uuid = ?
        LIMIT  1
    ");
    $update->execute([$updatedTicketJson, $taskId, $taskId]);

    echo json_encode([
        'success' => true,
        'message' => $update->rowCount() > 0
            ? 'Ticket saved successfully'
            : 'No changes detected (data may be identical)',
        'task_id' => $taskId,
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage(),
    ]);
}