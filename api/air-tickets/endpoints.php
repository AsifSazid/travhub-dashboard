<?php
/**
 * FILE PATH: /api/air-tickets/endpoints.php
 *
 * Air Ticket Module — Single endpoint file, action-based routing
 *
 * GET  actions:
 *   ?action=get&task_sys_id=THR-A26-TK-0001   → full air_tickets record for task
 *
 * POST actions (JSON body):
 *   action=init               → task এ প্রথমবার air_tickets row তৈরি করে
 *   action=save_quotation     → at_quotations array তে add/update
 *   action=update_quotation   → existing quotation update (sys_id দিয়ে)
 *   action=delete_quotation   → at_quotations থেকে remove
 *   action=move_to_booking    → quotation → booking এ copy করে
 *   action=save_booking       → at_bookings array তে add/update
 *   action=update_booking     → existing booking update
 *   action=delete_booking     → at_bookings থেকে remove
 *   action=set_confirmation   → at_confirmation set/update (booking থেকে)
 *   action=update_quotation_status → quotation status change
 *   action=update_booking_status   → booking status change
 *   action=update_confirmation_status → confirmation status change
 */

ob_start();
session_start();
date_default_timezone_set('Asia/Dhaka');

// PHP fatal errors কে JSON এ convert করো
ini_set('display_errors', 0);
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    ob_clean();
    http_response_code(500);
    echo json_encode(['status'=>'error','message'=>"PHP Error: $errstr in $errfile:$errline"]);
    exit;
});
register_shutdown_function(function() {
    $e = error_get_last();
    if ($e && in_array($e['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        ob_clean();
        http_response_code(500);
        echo json_encode(['status'=>'error','message'=>"Fatal: {$e['message']} in {$e['file']}:{$e['line']}"]);
    }
});

require_once '../../server/api_bootstrap.php';
require_once '../../server/db_connection.php';
require_once '../../server/sys_id_generator_v2.php';
require_once '../../server/generate_meta_data.php';
require_once __DIR__ . '/../../server/ai-gemini.php';

$method   = $_SERVER['REQUEST_METHOD'];
$action   = $_GET['action'] ?? '';
$userName = $_SESSION['user_name'] ?? 'system';

// POST body parse
$body = [];
if ($method === 'POST') {
    $raw  = file_get_contents('php://input');
    $body = json_decode($raw, true) ?? [];
    $action = $body['action'] ?? $_POST['action'] ?? $action;
}

// ── Helper: fetch air_tickets row by task_sys_id ─────────────
function _fetchRow(PDO $pdo, string $taskSysId): ?array
{
    $s = $pdo->prepare("SELECT * FROM air_tickets WHERE task_sys_id = ? LIMIT 1");
    $s->execute([$taskSysId]);
    $row = $s->fetch(PDO::FETCH_ASSOC);
    if (!$row) return null;
    return _decodeRow($row);
}

// ── Helper: fetch by task or work — whichever is available ───
function _fetchByContext(PDO $pdo, string $taskSysId, string $workSysId): ?array {
    if ($workSysId) return _fetchRowByWork($pdo, $workSysId);
    if ($taskSysId) return _fetchRow($pdo, $taskSysId);
    return null;
}
function _fetchRowByWork(PDO $pdo, string $workSysId): ?array
{
    $s = $pdo->prepare("SELECT * FROM air_tickets WHERE work_sys_id = ? LIMIT 1");
    $s->execute([$workSysId]);
    $row = $s->fetch(PDO::FETCH_ASSOC);
    if (!$row) return null;
    return _decodeRow($row);
}

function _decodeRow(array $row): array
{
    foreach (['at_quotations', 'at_bookings', 'at_confirmations'] as $col) {
        $row[$col] = (isset($row[$col]) && $row[$col]) ? json_decode($row[$col], true) : [];
    }
    // Decode commands
    $row['commands'] = (isset($row['commands']) && $row['commands']) ? json_decode($row['commands'], true) : null;
    // Legacy at_confirmation (single object) → migrate to array
    if (isset($row['at_confirmation']) && $row['at_confirmation']) {
        $single = json_decode($row['at_confirmation'], true);
        if (is_array($single) && !empty($single) && empty($row['at_confirmations'])) {
            $row['at_confirmations'] = [array_merge(['sys_id'=>'C-001','added_at'=>'legacy'], $single)];
        }
    }
    $row['meta_data'] = (isset($row['meta_data']) && $row['meta_data']) ? json_decode($row['meta_data'], true) : [];
    return $row;
}

// Save all JSON columns
function _saveRow(PDO $pdo, string $id, array $quotations, array $bookings, $confirmation, string $existingMeta, string $userName, bool $byWork = false): void
{
    $meta  = buildMetaData($existingMeta, $userName);
    $field = $byWork ? 'work_sys_id' : 'task_sys_id';
    $pdo->prepare("
        UPDATE air_tickets
        SET at_quotations=?, at_bookings=?, at_confirmation=?, meta_data=?
        WHERE {$field}=?
    ")->execute([
        json_encode($quotations, JSON_UNESCAPED_UNICODE),
        json_encode($bookings,   JSON_UNESCAPED_UNICODE),
        $confirmation !== null ? json_encode($confirmation, JSON_UNESCAPED_UNICODE) : null,
        $meta,
        $id,
    ]);
}

// Save including at_confirmations array — supports both task_sys_id and work_sys_id
function _saveRowFull(PDO $pdo, string $id, array $quotations, array $bookings, array $confirmations, string $existingMeta, string $userName, bool $byWork = false): void
{
    $meta  = buildMetaData($existingMeta, $userName);
    $field = $byWork ? 'work_sys_id' : 'task_sys_id';
    $pdo->prepare("
        UPDATE air_tickets
        SET at_quotations=?, at_bookings=?, at_confirmations=?, meta_data=?
        WHERE {$field}=?
    ")->execute([
        json_encode($quotations,    JSON_UNESCAPED_UNICODE),
        json_encode($bookings,      JSON_UNESCAPED_UNICODE),
        json_encode($confirmations, JSON_UNESCAPED_UNICODE),
        $meta,
        $id,
    ]);
}

// ── Phase 6: Auto-create task when confirmation → confirmed ───
function _autoCreateTaskOnConfirmed(PDO $pdo, array $conf, string $workSysId, string $userName): ?string
{
    try {
        // confirmation_sys_id + work_sys_id দুটো দিয়েই check — C-001 সব work এ থাকে
        $checkStmt = $pdo->prepare("SELECT sys_id FROM tasks WHERE confirmation_sys_id = ? AND work_sys_id = ? LIMIT 1");
        $checkStmt->execute([$conf['sys_id'], $workSysId]);
        $existing = $checkStmt->fetchColumn();
        if ($existing) return $existing; // existing task sys_id return করো, null না

        $ws = $pdo->prepare("SELECT client_info FROM works WHERE sys_id = ? LIMIT 1");
        $ws->execute([$workSysId]);
        $work = $ws->fetch(PDO::FETCH_ASSOC);
        if (!$work) return 'NO_WORK';

        $ci          = json_decode($work['client_info'], true) ?? [];
        $clientName  = $ci['name']   ?? 'Unknown';
        $clientSysId = $ci['sys_id'] ?? null;
        $ticketNos   = implode(', ', $conf['ticket_nos'] ?? []);
        $taskName    = 'Air Ticket' . ($ticketNos ? ' — ' . $ticketNos : ' — ' . $conf['sys_id']);

        $sw = $pdo->prepare("SELECT sys_id FROM service_works WHERE work_sys_id = ? AND service_slug = 'air_ticket' LIMIT 1");
        $sw->execute([$workSysId]);
        $swSysId = $sw->fetchColumn() ?: null;

        require_once __DIR__ . '/../../server/sys_id_generator_v2.php';
        require_once __DIR__ . '/../../server/generate_meta_data.php';

        $taskIds  = generateV2IDs($pdo, 'tasks');
        $taskMeta = buildMetaData(null, $userName);

        $pdo->prepare("
            INSERT INTO tasks (uuid, sys_id, service_work_sys_id, work_sys_id, client_sys_id, workname, client_name, status, service_slug, confirmation_sys_id, meta_data)
            VALUES (?, ?, ?, ?, ?, ?, ?, 'open', 'air_ticket', ?, ?)
        ")->execute([
            $taskIds['uuid'], $taskIds['sys_id'],
            $swSysId, $workSysId, $clientSysId,
            $taskName, $clientName,
            $conf['sys_id'], $taskMeta,
        ]);

        return $taskIds['sys_id'];

    } catch (Throwable $e) {
        return 'ERR:' . $e->getMessage();
    }
}

// ── Helper: short local ID generator (Q-001, B-001 etc) ──────
function _localId(array $existing, string $prefix): string
{
    $max = 0;
    foreach ($existing as $item) {
        $id  = $item['sys_id'] ?? '';
        if (strpos($id, $prefix . '-') === 0) {
            $num = (int) substr($id, strlen($prefix) + 1);
            if ($num > $max) $max = $num;
        }
    }
    return $prefix . '-' . str_pad($max + 1, 3, '0', STR_PAD_LEFT);
}

// ── Helper: Mind Board note-এর meta_data-তে "এই quotation-এ ব্যবহৃত হয়েছে"
// মার্ক করা — note bubble-এ badge দেখানোর জন্য (mindboard.js এই ফিল্ড
// পড়ে "Used in Q-00X" badge বসায়)। note-এ কোনো নতুন column যোগ করিনি,
// existing meta_data JSON-এই একটা key যোগ করা হচ্ছে যাতে schema migration
// লাগে না।
function _markNotesUsedInQuotation(PDO $pdo, array $noteIds, string $qSysId): void
{
    if (empty($noteIds)) return;
    $placeholders = implode(',', array_fill(0, count($noteIds), '?'));
    $stmt = $pdo->prepare("SELECT sys_id, meta_data FROM task_notes WHERE sys_id IN ($placeholders)");
    $stmt->execute($noteIds);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $upd = $pdo->prepare("UPDATE task_notes SET meta_data = ? WHERE sys_id = ?");
    foreach ($rows as $r) {
        $meta = $r['meta_data'] ? (json_decode($r['meta_data'], true) ?: []) : [];
        $used = $meta['used_in_quotations'] ?? [];
        if (!in_array($qSysId, $used, true)) {
            $used[] = $qSysId;
            $meta['used_in_quotations'] = $used;
            $upd->execute([json_encode($meta, JSON_UNESCAPED_UNICODE), $r['sys_id']]);
        }
    }
}

// ─────────────────────────────────────────────────────────────
// ── Task-lock helpers ──────────────────────────────────────────
// Once "Confirm & Create Task" fires for ONE confirmation, only that
// confirmation + its source booking + that booking's source quotation
// are locked — other quotations/bookings/confirmations in the same
// work stay fully editable.

// Does a task already exist for this specific confirmation?
function _atConfirmationLocked(PDO $pdo, string $workSysId, string $confSysId): bool
{
    if (!$workSysId || !$confSysId) return false;
    $s = $pdo->prepare("SELECT sys_id FROM tasks WHERE work_sys_id = ? AND confirmation_sys_id = ? LIMIT 1");
    $s->execute([$workSysId, $confSysId]);
    return (bool)$s->fetchColumn();
}

// Is this quotation locked? — true if ANY confirmation that traces back to
// this quotation (via booking.quotation_sys_id) already has a task.
function _atQuotationLocked(PDO $pdo, array $row, string $workSysId, string $qSysId): bool
{
    $bookings      = is_array($row['at_bookings'] ?? null) ? $row['at_bookings'] : [];
    $confirmations = is_array($row['at_confirmations'] ?? null) ? $row['at_confirmations'] : [];
    $bookingIds    = array_column(array_filter($bookings, fn($b) => ($b['quotation_sys_id'] ?? null) === $qSysId), 'sys_id');
    if (!$bookingIds) return false;
    foreach ($confirmations as $c) {
        if (in_array($c['booking_sys_id'] ?? null, $bookingIds, true) && _atConfirmationLocked($pdo, $workSysId, $c['sys_id'])) {
            return true;
        }
    }
    return false;
}

// Is this booking locked? — true if ANY confirmation built from this booking already has a task.
function _atBookingLocked(PDO $pdo, array $row, string $workSysId, string $bSysId): bool
{
    $confirmations = is_array($row['at_confirmations'] ?? null) ? $row['at_confirmations'] : [];
    foreach ($confirmations as $c) {
        if (($c['booking_sys_id'] ?? null) === $bSysId && _atConfirmationLocked($pdo, $workSysId, $c['sys_id'])) {
            return true;
        }
    }
    return false;
}

function _atLockedResponse(): void
{
    ob_clean();
    http_response_code(403);
    echo json_encode([
        'status'  => 'error',
        'message' => 'A task has already been created from this — it is now locked and cannot be edited or deleted.',
        'locked'  => true,
    ]);
    exit;
}

try {

    // ════════════════════════════════════════════════════════
    // GET — fetch full record
    // ════════════════════════════════════════════════════════
    if ($method === 'GET' && $action === 'get') {
        $taskSysId = $_GET['task_sys_id'] ?? '';
        $workSysId = $_GET['work_sys_id'] ?? '';

        $row = null;
        if ($workSysId) {
            $row = _fetchRowByWork($pdo, $workSysId);
        } elseif ($taskSysId) {
            $row = _fetchByContext($pdo, $taskSysId, $workSysId);
        } else {
            throw new Exception('task_sys_id or work_sys_id is required');
        }

        ob_clean();
        echo json_encode([
            'status' => 'success',
            'data'   => $row,
        ]);
        exit;
    }

    // ════════════════════════════════════════════════════════
    // POST — all write actions
    // ════════════════════════════════════════════════════════
    if ($method !== 'POST') throw new Exception('Method not allowed');

    $taskSysId = $body['task_sys_id'] ?? $_POST['task_sys_id'] ?? '';
    $workSysId = $body['work_sys_id'] ?? $_POST['work_sys_id'] ?? '';
    $byWork    = !$taskSysId && $workSysId; // new flow: work_sys_id only

    if (!$taskSysId && !$workSysId) throw new Exception('task_sys_id or work_sys_id is required');

    switch ($action) {

        // ── INIT ─────────────────────────────────────────────
        case 'init': {
            $leadSysId = $body['lead_sys_id'] ?? null;
            if (!$workSysId) throw new Exception('work_sys_id is required');

            // Already exists by work?
            $existing = _fetchRowByWork($pdo, $workSysId);
            if (!$existing && $taskSysId) $existing = _fetchByContext($pdo, $taskSysId, $workSysId);
            if ($existing) {
                ob_clean();
                echo json_encode(['status' => 'success', 'message' => 'Already initialized', 'data' => $existing]);
                exit;
            }

            $ids  = generateV2IDs($pdo, 'air_tickets');
            $meta = buildMetaData(null, $userName);

            $pdo->prepare("
                INSERT INTO air_tickets (uuid, sys_id, lead_sys_id, work_sys_id, task_sys_id, at_quotations, at_bookings, at_confirmation, meta_data)
                VALUES (?, ?, ?, ?, ?, '[]', '[]', NULL, ?)
            ")->execute([$ids['uuid'], $ids['sys_id'], $leadSysId, $workSysId, $taskSysId ?: null, $meta]);

            ob_clean();
            echo json_encode([
                'status'  => 'success',
                'message' => 'Air ticket record initialized',
                'sys_id'  => $ids['sys_id'],
            ]);
            break;
        }

        // ── SAVE QUOTATION ────────────────────────────────────
        // at_quotations array তে নতুন quotation push করে
        case 'save_quotation': {
            $row = _fetchByContext($pdo, $taskSysId, $workSysId);
            if (!$row) throw new Exception('Air ticket record not found. Call init first.');

            $quotations = is_array($row['at_quotations']) ? $row['at_quotations'] : [];

            // ⚠️ Mind Board "Generate Quotation" ফিচার থেকে আসলে source note
            // গুলোর sys_id list পাঠানো হয় — কোন note থেকে এই quotation
            // তৈরি হয়েছে সেটা ট্র্যাক রাখার জন্য (Mind Board-এ badge + এখানে
            // পরে দেখার জন্য)। সাধারণ manual quotation-এ এটা খালি array থাকে।
            $sourceNoteIds = is_array($body['source_note_ids'] ?? null) ? $body['source_note_ids'] : [];

            // ⚠️ SOTO quotation-এর সব field (route, trip_option, baggage/price
            // options, refundable status ইত্যাদি) form_data object-এর ভেতরে
            // থাকে (quotation.js-এর atSaveQ() দেখুন) — আগে এই key-টা এখানে
            // গ্রহণই করা হতো না, ফলে SOTO quotation সেভ হতো ঠিকই (title/
            // airline/gross_fare বেঁচে থাকত, তাই card list-এ price দেখা
            // যেত) কিন্তু ফর্ম আবার খুললে পুরো form_data হারিয়ে যেত —
            // route/pax/baggage সব ফাঁকা দেখাত।
            $newQ = [
                'sys_id'          => _localId($quotations, 'Q'),
                'type'            => $body['type']          ?? 'gds',    // gds | soto
                'title'           => $body['title']         ?? '',
                'airline'         => $body['airline']       ?? '',
                'segments_json'   => $body['segments_json'] ?? [],
                'pax'             => $body['pax']           ?? [],
                'pricing_json'    => $body['pricing_json']  ?? [],
                'raw_input'       => $body['raw_input']     ?? '',
                'copy_text'       => $body['copy_text']     ?? '',
                'gross_fare'      => (float)($body['gross_fare']    ?? 0),
                'net_fare'        => (float)($body['net_fare']      ?? 0),
                'total_payable'   => (float)($body['total_payable'] ?? 0),
                'form_data'       => $body['form_data']     ?? null,
                'source_note_ids' => $sourceNoteIds,
                'status'          => 'draft',
                'created_at'      => date('d-m-Y H:i'),
                'created_by'      => $userName,
            ];

            $quotations[] = $newQ;

            $existingMeta = json_encode($row['meta_data'], JSON_UNESCAPED_UNICODE);
            _saveRow($pdo, $byWork ? $workSysId : $taskSysId, $quotations, is_array($row['at_bookings']) ? $row['at_bookings'] : [], $row['at_confirmation'] ?: null, $existingMeta, $userName, $byWork);

            // Source note গুলোতে "Used in Q-00X" মার্ক করা — quotation save
            // ব্যর্থ হলে এই ধাপ চলবে না (উপরের _saveRow() exception ছুঁড়লে
            // থেমে যাবে), তাই marking সবসময় সফল save-এর পরেই হয়
            if (!empty($sourceNoteIds)) {
                _markNotesUsedInQuotation($pdo, $sourceNoteIds, $newQ['sys_id']);
            }

            ob_clean();
            echo json_encode(['status' => 'success', 'message' => 'Quotation saved', 'quotation_sys_id' => $newQ['sys_id']]);
            break;
        }

        // ── UPDATE QUOTATION ──────────────────────────────────
        // Existing quotation update — sys_id দিয়ে match করে
        case 'update_quotation': {
            $qSysId = $body['quotation_sys_id'] ?? '';
            if (!$qSysId) throw new Exception('quotation_sys_id is required');

            $row = _fetchByContext($pdo, $taskSysId, $workSysId);
            if (!$row) throw new Exception('Air ticket record not found');

            $wSysIdForLock = $row['work_sys_id'] ?? $workSysId;
            if (_atQuotationLocked($pdo, $row, $wSysIdForLock, $qSysId)) _atLockedResponse();

            $quotations = is_array($row['at_quotations']) ? $row['at_quotations'] : [];
            $found      = false;

            foreach ($quotations as &$q) {
                if ($q['sys_id'] === $qSysId) {
                    // Update allowed fields (status ও preserved)
                    if (isset($body['type']))          $q['type']          = $body['type'];
                    if (isset($body['title']))         $q['title']         = $body['title'];
                    if (isset($body['airline']))       $q['airline']       = $body['airline'];
                    if (isset($body['segments_json'])) $q['segments_json'] = $body['segments_json'];
                    if (isset($body['pax']))           $q['pax']           = $body['pax'];
                    if (isset($body['pricing_json']))  $q['pricing_json']  = $body['pricing_json'];
                    if (isset($body['raw_input']))     $q['raw_input']     = $body['raw_input'];
                    if (isset($body['copy_text']))     $q['copy_text']     = $body['copy_text'];
                    if (isset($body['gross_fare']))    $q['gross_fare']    = (float)$body['gross_fare'];
                    if (isset($body['net_fare']))      $q['net_fare']      = (float)$body['net_fare'];
                    if (isset($body['total_payable'])) $q['total_payable'] = (float)$body['total_payable'];
                    if (isset($body['form_data']))     $q['form_data']     = $body['form_data']; // ⚠️ SOTO-এর সব field এখানেই থাকে — save_quotation-এর মতোই আগে মিসিং ছিল
                    $q['updated_at'] = date('d-m-Y H:i');
                    $q['updated_by'] = $userName;
                    $found = true;
                    break;
                }
            }
            unset($q);

            if (!$found) throw new Exception("Quotation '{$qSysId}' not found");

            $existingMeta = json_encode($row['meta_data'], JSON_UNESCAPED_UNICODE);
            _saveRow($pdo, $byWork ? $workSysId : $taskSysId, $quotations, is_array($row['at_bookings']) ? $row['at_bookings'] : [], $row['at_confirmation'] ?: null, $existingMeta, $userName, $byWork);

            ob_clean();
            echo json_encode(['status' => 'success', 'message' => 'Quotation updated']);
            break;
        }

        // ── DELETE QUOTATION ──────────────────────────────────
        case 'delete_quotation': {
            $qSysId = $body['quotation_sys_id'] ?? '';
            if (!$qSysId) throw new Exception('quotation_sys_id is required');

            $row = _fetchByContext($pdo, $taskSysId, $workSysId);
            if (!$row) throw new Exception('Air ticket record not found');

            $wSysIdForLock = $row['work_sys_id'] ?? $workSysId;
            if (_atQuotationLocked($pdo, $row, $wSysIdForLock, $qSysId)) _atLockedResponse();

            $existingBookings = is_array($row['at_bookings']) ? $row['at_bookings'] : [];
            if (array_filter($existingBookings, fn($b) => ($b['quotation_sys_id'] ?? null) === $qSysId)) {
                ob_clean();
                http_response_code(409);
                echo json_encode(['status' => 'error', 'message' => 'একটা Booking এই Quotation থেকে তৈরি হয়েছে — আগে সেই Booking delete করুন।']);
                exit;
            }

            $quotations = array_values(array_filter(
                is_array($row['at_quotations']) ? $row['at_quotations'] : [],
                fn($q) => $q['sys_id'] !== $qSysId
            ));

            $existingMeta = json_encode($row['meta_data'], JSON_UNESCAPED_UNICODE);
            _saveRow($pdo, $byWork ? $workSysId : $taskSysId, $quotations, is_array($row['at_bookings']) ? $row['at_bookings'] : [], $row['at_confirmation'] ?: null, $existingMeta, $userName, $byWork);

            ob_clean();
            echo json_encode(['status' => 'success', 'message' => 'Quotation deleted']);
            break;
        }

        // ── UPDATE QUOTATION STATUS ───────────────────────────
        // draft | sent | moved_to_booking | cancelled
        case 'update_quotation_status': {
            $qSysId    = $body['quotation_sys_id'] ?? '';
            $newStatus = $body['status']           ?? '';
            $allowed   = ['draft', 'sent', 'moved_to_booking', 'cancelled'];

            if (!$qSysId)                        throw new Exception('quotation_sys_id is required');
            if (!in_array($newStatus, $allowed))  throw new Exception('Invalid quotation status');

            $row = _fetchByContext($pdo, $taskSysId, $workSysId);
            if (!$row) throw new Exception('Air ticket record not found');

            $quotations = is_array($row['at_quotations']) ? $row['at_quotations'] : [];
            foreach ($quotations as &$q) {
                if ($q['sys_id'] === $qSysId) {
                    $q['status']     = $newStatus;
                    $q['updated_at'] = date('d-m-Y H:i');
                    $q['updated_by'] = $userName;
                    break;
                }
            }
            unset($q);

            $existingMeta = json_encode($row['meta_data'], JSON_UNESCAPED_UNICODE);
            _saveRow($pdo, $byWork ? $workSysId : $taskSysId, $quotations, is_array($row['at_bookings']) ? $row['at_bookings'] : [], $row['at_confirmation'] ?: null, $existingMeta, $userName, $byWork);

            ob_clean();
            echo json_encode(['status' => 'success', 'message' => 'Quotation status updated']);
            break;
        }

        // ── MOVE TO BOOKING ───────────────────────────────────
        // Quotation → Booking এ copy করে
        // Original quotation status → 'moved_to_booking'
        // Booking এ নতুন entry, quotation_sys_id reference সহ
        case 'move_to_booking': {
            $qSysId = $body['quotation_sys_id'] ?? '';
            if (!$qSysId) throw new Exception('quotation_sys_id is required');

            $row = _fetchByContext($pdo, $taskSysId, $workSysId);
            if (!$row) throw new Exception('Air ticket record not found');

            $quotations = is_array($row['at_quotations']) ? $row['at_quotations'] : [];
            $bookings   = is_array($row['at_bookings'])   ? $row['at_bookings']   : [];

            // Find source quotation
            $sourceQ = null;
            foreach ($quotations as &$q) {
                if ($q['sys_id'] === $qSysId) {
                    $sourceQ = $q;
                    $q['status']     = 'moved_to_booking';
                    $q['updated_at'] = date('d-m-Y H:i');
                    $q['updated_by'] = $userName;
                    break;
                }
            }
            unset($q);

            if (!$sourceQ) throw new Exception("Quotation '{$qSysId}' not found");

            // Build new booking from quotation
            // ⚠️ 'form_data' না কপি করলে SOTO booking-এর currency/prices/route
            // সব হারিয়ে যায় (ঠিক save_quotation-এ যেই bug ছিল) — এখানেও
            // একই ভুল ছিল, এখন ঠিক করা হলো
            $newB = [
                'sys_id'           => _localId($bookings, 'B'),
                'quotation_sys_id' => $qSysId,
                'type'             => $sourceQ['type']          ?? 'gds',
                'title'            => $sourceQ['title']         ?? '',
                'airline'          => $sourceQ['airline']       ?? '',
                'pnr'              => '',                        // booking এ fill করবে
                'ticket_nos'       => [],
                'segments_json'    => $sourceQ['segments_json'] ?? [],
                'pax'              => $sourceQ['pax']           ?? [],
                'pricing_json'     => $sourceQ['pricing_json']  ?? [],
                'raw_input'        => $sourceQ['raw_input']     ?? '',
                'copy_text'        => $sourceQ['copy_text']     ?? '',
                'gross_fare'       => $sourceQ['gross_fare']    ?? 0,
                'net_fare'         => $sourceQ['net_fare']      ?? 0,
                'total_payable'    => $sourceQ['total_payable'] ?? 0,
                'form_data'        => $sourceQ['form_data']     ?? null,
                'status'           => 'tentative',
                'created_at'       => date('d-m-Y H:i'),
                'created_by'       => $userName,
            ];

            $bookings[] = $newB;

            $existingMeta = json_encode($row['meta_data'], JSON_UNESCAPED_UNICODE);
            _saveRow($pdo, $byWork ? $workSysId : $taskSysId, $quotations, $bookings, $row['at_confirmation'] ?: null, $existingMeta, $userName, $byWork);

            ob_clean();
            echo json_encode([
                'status'          => 'success',
                'message'         => 'Moved to booking',
                'booking_sys_id'  => $newB['sys_id'],
            ]);
            break;
        }

        // ── SAVE BOOKING ──────────────────────────────────────
        // at_bookings array তে নতুন booking push (quotation ছাড়া directly)
        case 'save_booking': {
            $row = _fetchByContext($pdo, $taskSysId, $workSysId);
            if (!$row) throw new Exception('Air ticket record not found. Call init first.');

            $bookings = is_array($row['at_bookings']) ? $row['at_bookings'] : [];

            $newB = [
                'sys_id'           => _localId($bookings, 'B'),
                'quotation_sys_id' => $body['quotation_sys_id'] ?? null,
                'type'             => $body['type']             ?? 'gds',
                'title'            => $body['title']            ?? '',
                'airline'          => $body['airline']          ?? '',
                'pnr'              => $body['pnr']              ?? '',
                'ticket_nos'       => $body['ticket_nos']       ?? [],
                'segments_json'    => $body['segments_json']    ?? [],
                'pax'              => $body['pax']              ?? [],
                'pricing_json'     => $body['pricing_json']     ?? [],
                'raw_input'        => $body['raw_input']        ?? '',
                'copy_text'        => $body['copy_text']        ?? '',
                'gross_fare'       => (float)($body['gross_fare']    ?? 0),
                'net_fare'         => (float)($body['net_fare']      ?? 0),
                'total_payable'    => (float)($body['total_payable'] ?? 0),
                'form_data'        => $body['form_data']        ?? null,
                'status'           => 'tentative',
                'created_at'       => date('d-m-Y H:i'),
                'created_by'       => $userName,
            ];

            // Booking থেকে সরাসরি করলে quotation তেও draft entry রাখো
            // (quotation_sys_id null মানে directly created)
            $quotations = is_array($row['at_quotations']) ? $row['at_quotations'] : [];
            if (empty($newB['quotation_sys_id'])) {
                $mirrorQ = array_merge($newB, [
                    'sys_id' => _localId($quotations, 'Q'),
                    'status' => 'moved_to_booking',
                    'note'   => 'Auto-created from booking tab',
                ]);
                $quotations[]          = $mirrorQ;
                $newB['quotation_sys_id'] = $mirrorQ['sys_id'];
            }

            $bookings[] = $newB;

            $existingMeta = json_encode($row['meta_data'], JSON_UNESCAPED_UNICODE);
            _saveRow($pdo, $byWork ? $workSysId : $taskSysId, $quotations, $bookings, $row['at_confirmation'] ?: null, $existingMeta, $userName, $byWork);

            ob_clean();
            echo json_encode([
                'status'         => 'success',
                'message'        => 'Booking saved',
                'booking_sys_id' => $newB['sys_id'],
            ]);
            break;
        }

        // ── UPDATE BOOKING ────────────────────────────────────
        // Booking update করলে:
        //   1. booking update হয়
        //   2. quotation-এ নতুন snapshot entry তৈরি হয় (Fix 4)
        //   3. original quotation touch হয় না
        case 'update_booking': {
            $bSysId = $body['booking_sys_id'] ?? '';
            if (!$bSysId) throw new Exception('booking_sys_id is required');

            $row = _fetchByContext($pdo, $taskSysId, $workSysId);
            if (!$row) throw new Exception('Air ticket record not found');

            $wSysIdForLock = $row['work_sys_id'] ?? $workSysId;
            if (_atBookingLocked($pdo, $row, $wSysIdForLock, $bSysId)) _atLockedResponse();

            $bookings   = is_array($row['at_bookings'])   ? $row['at_bookings']   : [];
            $quotations = is_array($row['at_quotations']) ? $row['at_quotations'] : [];
            $found      = false;
            $sourceQSysId = null;

            foreach ($bookings as &$b) {
                if ($b['sys_id'] === $bSysId) {
                    $sourceQSysId = $b['quotation_sys_id'] ?? null;
                    if (isset($body['pnr']))           $b['pnr']           = $body['pnr'];
                    if (isset($body['ticket_nos']))    $b['ticket_nos']    = $body['ticket_nos'];
                    if (isset($body['airline']))       $b['airline']       = $body['airline'];
                    if (isset($body['segments_json'])) $b['segments_json'] = $body['segments_json'];
                    if (isset($body['pax']))           $b['pax']           = $body['pax'];
                    if (isset($body['pricing_json']))  $b['pricing_json']  = $body['pricing_json'];
                    if (isset($body['raw_input']))     $b['raw_input']     = $body['raw_input'];
                    if (isset($body['copy_text']))     $b['copy_text']     = $body['copy_text'];
                    if (isset($body['gross_fare']))    $b['gross_fare']    = (float)($body['gross_fare'] ?? 0);
                    if (isset($body['net_fare']))      $b['net_fare']      = (float)($body['net_fare']   ?? 0);
                    if (isset($body['total_payable'])) $b['total_payable'] = (float)($body['total_payable'] ?? 0);
                    if (isset($body['form_data']))     $b['form_data']     = $body['form_data'];
                    if (isset($body['status']))        $b['status']        = $body['status'];
                    $b['updated_at'] = date('d-m-Y H:i');
                    $b['updated_by'] = $userName;
                    $found = true;
                    break;
                }
            }
            unset($b);

            if (!$found) throw new Exception("Booking '{$bSysId}' not found");

            // ⚠️ আগে এখানে booking update করলে Quotation list-এ একটা
            // 'Auto-revision' quotation তৈরি হতো (source_booking সেট করে) —
            // এটা UI-তে বিভ্রান্তিকর ছিল (booking-এর update Quotation
            // list-এও দেখা যেত)। এখন সরিয়ে দেওয়া হলো — booking update
            // করলে শুধু at_bookings-ই আপডেট হবে, at_quotations অপরিবর্তিত
            // থাকবে। Confirmation tab booking-এর data থেকেই সরাসরি পড়ে
            // (window._atReload() করলে), তাই আলাদা sync করার দরকার নেই।
            $existingMeta = json_encode($row['meta_data'], JSON_UNESCAPED_UNICODE);
            _saveRow($pdo, $byWork ? $workSysId : $taskSysId, $quotations, $bookings, $row['at_confirmation'] ?: null, $existingMeta, $userName, $byWork);

            ob_clean();
            echo json_encode([
                'status'               => 'success',
                'message'              => 'Booking updated',
                'new_quotation_sys_id' => null,
            ]);
            break;
        }

        // ── DELETE BOOKING ────────────────────────────────────
        case 'delete_booking': {
            $bSysId = $body['booking_sys_id'] ?? '';
            if (!$bSysId) throw new Exception('booking_sys_id is required');

            $row = _fetchByContext($pdo, $taskSysId, $workSysId);
            if (!$row) throw new Exception('Air ticket record not found');

            $wSysIdForLock = $row['work_sys_id'] ?? $workSysId;
            if (_atBookingLocked($pdo, $row, $wSysIdForLock, $bSysId)) _atLockedResponse();

            $bookings = array_values(array_filter(
                is_array($row['at_bookings']) ? $row['at_bookings'] : [],
                fn($b) => $b['sys_id'] !== $bSysId
            ));

            // Confirmation এ এই booking ছিল কিনা check
            $confirmation = $row['at_confirmation'];
            if (is_array($confirmation) && ($confirmation['booking_sys_id'] ?? '') === $bSysId) {
                $confirmation = null; // Confirmation ও clear হবে
            }

            // ⚠️ যেই quotation থেকে এই booking তৈরি হয়েছিল, তার status
            // 'moved_to_booking'-এ আটকে থাকত booking delete করার পরেও —
            // ফলে সেই quotation UI-তে "already moved" দেখাত এবং আবার নতুন
            // করে Move to Booking করা যেত না, যদিও booking-টা আর নেই।
            // এখানে সেই quotation-এর status ফিরিয়ে 'draft'-এ আনা হচ্ছে,
            // যাতে আবার move করা যায়। booking থেকে তৈরি হওয়া revision-type
            // quotation (source_booking সেট আছে) touch করা হয় না — সেগুলো
            // historical snapshot হিসেবেই থেকে যায়।
            $quotations = is_array($row['at_quotations']) ? $row['at_quotations'] : [];

            // Delete হওয়া booking-টার quotation_sys_id বের করি (filter করার
            // আগের original array থেকে, যেহেতু এখন $bookings থেকে বাদ পড়ে গেছে)
            $deletedBooking = null;
            foreach ((is_array($row['at_bookings']) ? $row['at_bookings'] : []) as $b) {
                if ($b['sys_id'] === $bSysId) { $deletedBooking = $b; break; }
            }
            if ($deletedBooking && !empty($deletedBooking['quotation_sys_id'])) {
                $qSysIdToRevert = $deletedBooking['quotation_sys_id'];
                foreach ($quotations as &$q) {
                    if ($q['sys_id'] === $qSysIdToRevert && ($q['status'] ?? '') === 'moved_to_booking') {
                        $q['status']     = 'draft';
                        $q['updated_at'] = date('d-m-Y H:i');
                        $q['updated_by'] = $userName;
                        break;
                    }
                }
                unset($q);
            }

            $existingMeta = json_encode($row['meta_data'], JSON_UNESCAPED_UNICODE);
            _saveRow($pdo, $byWork ? $workSysId : $taskSysId, $quotations, $bookings, $confirmation, $existingMeta, $userName, $byWork);

            ob_clean();
            echo json_encode(['status' => 'success', 'message' => 'Booking deleted']);
            break;
        }

        // ── UPDATE BOOKING STATUS ─────────────────────────────
        // tentative | confirmed | failed | cancelled
        case 'update_booking_status': {
            $bSysId    = $body['booking_sys_id'] ?? '';
            $newStatus = $body['status']         ?? '';
            $allowed   = ['tentative', 'confirmed', 'failed', 'cancelled'];

            if (!$bSysId)                        throw new Exception('booking_sys_id is required');
            if (!in_array($newStatus, $allowed))  throw new Exception('Invalid booking status');

            $row = _fetchByContext($pdo, $taskSysId, $workSysId);
            if (!$row) throw new Exception('Air ticket record not found');

            $bookings = is_array($row['at_bookings']) ? $row['at_bookings'] : [];
            foreach ($bookings as &$b) {
                if ($b['sys_id'] === $bSysId) {
                    $b['status']     = $newStatus;
                    $b['updated_at'] = date('d-m-Y H:i');
                    $b['updated_by'] = $userName;
                    break;
                }
            }
            unset($b);

            $quotations   = is_array($row['at_quotations']) ? $row['at_quotations'] : [];
            $existingMeta = json_encode($row['meta_data'], JSON_UNESCAPED_UNICODE);
            _saveRow($pdo, $byWork ? $workSysId : $taskSysId, $quotations, $bookings, $row['at_confirmation'] ?: null, $existingMeta, $userName, $byWork);

            ob_clean();
            echo json_encode(['status' => 'success', 'message' => 'Booking status updated']);
            break;
        }

        // ── SET CONFIRMATION ──────────────────────────────────
        // Booking থেকে একটা select করে confirmation এ set করে
        // at_confirmation = single JSON object
        // ── ADD TO CONFIRMATION ───────────────────────────────
        // at_confirmations array এ একটা নতুন entry যোগ করে
        // একটা booking একবারই active confirmation এ থাকতে পারবে
        case 'add_to_confirmation': {
            $bSysId = $body['booking_sys_id'] ?? '';
            if (!$bSysId) throw new Exception('booking_sys_id is required');

            $row = _fetchByContext($pdo, $taskSysId, $workSysId);
            if (!$row) throw new Exception('Air ticket record not found');

            $bookings      = is_array($row['at_bookings'])      ? $row['at_bookings']      : [];
            $confirmations = is_array($row['at_confirmations'])  ? $row['at_confirmations'] : [];

            // Source booking check
            $sourceB = null;
            foreach ($bookings as $b) { if ($b['sys_id'] === $bSysId) { $sourceB = $b; break; } }
            if (!$sourceB) throw new Exception("Booking '{$bSysId}' not found");

            // Check not already in active confirmation
            foreach ($confirmations as $c) {
                if ($c['booking_sys_id'] === $bSysId && !in_array($c['status'] ?? 'pending', ['failed', 'cancelled'])) {
                    throw new Exception("Booking '{$bSysId}' is already in active confirmation");
                }
            }

            $newC = [
                'sys_id'         => _localId($confirmations, 'C'),
                'booking_sys_id' => $bSysId,
                'ticket_nos'     => $sourceB['ticket_nos'] ?? [],
                'files_json'     => [],
                'note'           => '',
                'status'         => 'pending',
                'added_at'       => date('d-m-Y H:i'),
                'added_by'       => $userName,
            ];

            $confirmations[] = $newC;

            $row['at_confirmations'] = $confirmations;
            $existingMeta = json_encode($row['meta_data'], JSON_UNESCAPED_UNICODE);
            $quotations   = is_array($row['at_quotations']) ? $row['at_quotations'] : [];
            _saveRowFull($pdo, $byWork ? $workSysId : $taskSysId, $quotations, $bookings, $confirmations, $existingMeta, $userName, $byWork);

            ob_clean();
            echo json_encode(['status' => 'success', 'message' => 'Added to confirmation', 'conf_sys_id' => $newC['sys_id']]);
            break;
        }

        // ── UPDATE CONFIRMATION ───────────────────────────────
        // ticket_nos, note, files_json update
        case 'update_confirmation': {
            $confSysId = $body['conf_sys_id'] ?? '';
            if (!$confSysId) throw new Exception('conf_sys_id is required');

            $row = _fetchByContext($pdo, $taskSysId, $workSysId);
            if (!$row) throw new Exception('Air ticket record not found');

            $wSysIdForLock = $row['work_sys_id'] ?? $workSysId;
            if (_atConfirmationLocked($pdo, $wSysIdForLock, $confSysId)) _atLockedResponse();

            $confirmations = is_array($row['at_confirmations']) ? $row['at_confirmations'] : [];
            $found = false;
            foreach ($confirmations as &$c) {
                if ($c['sys_id'] === $confSysId) {
                    if (isset($body['ticket_nos']))  $c['ticket_nos']  = $body['ticket_nos'];
                    if (isset($body['note']))        $c['note']        = $body['note'];
                    if (isset($body['files_json']))  $c['files_json']  = $body['files_json'];
                    $c['updated_at'] = date('d-m-Y H:i');
                    $c['updated_by'] = $userName;
                    $found = true;
                    break;
                }
            }
            unset($c);
            if (!$found) throw new Exception("Confirmation '{$confSysId}' not found");

            $quotations   = is_array($row['at_quotations']) ? $row['at_quotations'] : [];
            $bookings     = is_array($row['at_bookings'])   ? $row['at_bookings']   : [];
            $existingMeta = json_encode($row['meta_data'], JSON_UNESCAPED_UNICODE);
            _saveRowFull($pdo, $byWork ? $workSysId : $taskSysId, $quotations, $bookings, $confirmations, $existingMeta, $userName, $byWork);

            ob_clean();
            echo json_encode(['status' => 'success', 'message' => 'Confirmation updated']);
            break;
        }

        // ── UPDATE CONFIRMATION STATUS ────────────────────────
        // pending | confirmed | failed | cancelled
        case 'update_confirmation_status': {
            $confSysId = $body['conf_sys_id'] ?? '';
            $newStatus = $body['status']      ?? '';
            $allowed   = ['pending', 'confirmed', 'failed', 'cancelled'];
            if (!in_array($newStatus, $allowed)) throw new Exception('Invalid confirmation status');

            $row = $byWork ? _fetchRowByWork($pdo, $workSysId) : _fetchByContext($pdo, $taskSysId, $workSysId);
            if (!$row) throw new Exception('Air ticket record not found');

            $confirmations = is_array($row['at_confirmations']) ? $row['at_confirmations'] : [];
            $found = false;
            $confirmedConf = null;
            foreach ($confirmations as &$c) {
                if ($c['sys_id'] === $confSysId) {
                    $c['status']     = $newStatus;
                    $c['updated_at'] = date('d-m-Y H:i');
                    $c['updated_by'] = $userName;
                    $found = true;
                    if ($newStatus === 'confirmed') $confirmedConf = $c;
                    break;
                }
            }
            unset($c);
            if (!$found) throw new Exception("Confirmation '{$confSysId}' not found");

            $quotations   = is_array($row['at_quotations']) ? $row['at_quotations'] : [];
            $bookings     = is_array($row['at_bookings'])   ? $row['at_bookings']   : [];
            $existingMeta = json_encode($row['meta_data'], JSON_UNESCAPED_UNICODE);

            $id = $byWork ? $workSysId : $taskSysId;
            _saveRowFull($pdo, $id, $quotations, $bookings, $confirmations, $existingMeta, $userName, $byWork);

            ob_clean();
            echo json_encode(['status' => 'success', 'message' => 'Confirmation status updated']);
            break;
        }

        // ── CONFIRM AND CREATE TASK ───────────────────────────
        case 'confirm_and_create_task': {
            $confSysId = $body['conf_sys_id'] ?? '';
            if (!$confSysId) throw new Exception('conf_sys_id required');

            $row = _fetchByContext($pdo, $taskSysId, $workSysId);
            if (!$row) throw new Exception('Air ticket record not found');

            $confirmations = is_array($row['at_confirmations']) ? $row['at_confirmations'] : [];
            $found = false;
            $confirmedConf = null;
            foreach ($confirmations as &$c) {
                if ($c['sys_id'] === $confSysId) {
                    $c['status']     = 'confirmed';
                    $c['updated_at'] = date('d-m-Y H:i');
                    $c['updated_by'] = $userName;
                    $found = true;
                    $confirmedConf = $c;
                    break;
                }
            }
            unset($c);
            if (!$found) throw new Exception("Confirmation '{$confSysId}' not found");

            $quotations   = is_array($row['at_quotations']) ? $row['at_quotations'] : [];
            $bookings     = is_array($row['at_bookings'])   ? $row['at_bookings']   : [];
            $existingMeta = json_encode($row['meta_data'], JSON_UNESCAPED_UNICODE);

            $id = $byWork ? $workSysId : $taskSysId;
            _saveRowFull($pdo, $id, $quotations, $bookings, $confirmations, $existingMeta, $userName, $byWork);

            $wSysId = $row['work_sys_id'] ?? $workSysId;
            $autoTaskResult = null;
            if ($wSysId) {
                $autoTaskResult = _autoCreateTaskOnConfirmed($pdo, $confirmedConf, $wSysId, $userName);
            }

            // ERR: বা NO_WORK string মানে failure — null বা valid sys_id মানে success
            $taskOk    = $autoTaskResult !== null
                      && !str_starts_with((string)$autoTaskResult, 'ERR:')
                      && $autoTaskResult !== 'NO_WORK';

            // Source booking (for auto-filling a vendor payment amount on the frontend)
            $srcBooking = null;
            foreach ($bookings as $b) { if ($b['sys_id'] === ($confirmedConf['booking_sys_id'] ?? null)) { $srcBooking = $b; break; } }

            ob_clean();
            echo json_encode([
                'status'       => 'success',
                'message'      => 'Confirmed' . ($taskOk ? ' and task created' : ' (task creation failed)'),
                'task_created' => $taskOk,
                'auto_task_id' => $taskOk    ? $autoTaskResult : null,
                'task_error'   => !$taskOk   ? $autoTaskResult : null,
                'work_sys_id'  => $wSysId,
                'booking'      => $srcBooking, // { sys_id, airline, total_payable, ... } or null
            ]);
            break;
        }

        // ── REMOVE CONFIRMATION ───────────────────────────────
        // Failed/cancelled confirmation remove করা
        case 'remove_confirmation': {
            $confSysId = $body['conf_sys_id'] ?? '';
            if (!$confSysId) throw new Exception('conf_sys_id is required');

            $row = _fetchByContext($pdo, $taskSysId, $workSysId);
            if (!$row) throw new Exception('Air ticket record not found');

            $confirmations = is_array($row['at_confirmations']) ? $row['at_confirmations'] : [];

            // ⚠️ আগে শুধু failed/cancelled status-এই remove করা যেত — এখন
            // pending-ও allow করা হচ্ছে (ভুল করে confirmation-এ পাঠানো
            // entry মুছে ফেলার জন্য)। শুধু 'confirmed' + task already
            // তৈরি হয়ে গেলে block করা হয়, কারণ সেই task-এর সাথে link
            // ছিন্ন করা data-integrity ভাঙতে পারে।
            foreach ($confirmations as $c) {
                if ($c['sys_id'] !== $confSysId) continue;
                if (($c['status'] ?? 'pending') === 'confirmed') {
                    $taskCheck = $pdo->prepare("SELECT sys_id FROM tasks WHERE confirmation_sys_id = ? LIMIT 1");
                    $taskCheck->execute([$confSysId]);
                    if ($taskCheck->fetchColumn()) {
                        throw new Exception('Task already created from this confirmation — cannot remove');
                    }
                }
            }
            $confirmations = array_values(array_filter($confirmations, fn($c) => $c['sys_id'] !== $confSysId));

            $quotations   = is_array($row['at_quotations']) ? $row['at_quotations'] : [];
            $bookings     = is_array($row['at_bookings'])   ? $row['at_bookings']   : [];
            $existingMeta = json_encode($row['meta_data'], JSON_UNESCAPED_UNICODE);
            _saveRowFull($pdo, $byWork ? $workSysId : $taskSysId, $quotations, $bookings, $confirmations, $existingMeta, $userName, $byWork);

            ob_clean();
            echo json_encode(['status' => 'success', 'message' => 'Confirmation removed']);
            break;
        }

        // ── LEGACY: set_confirmation (backward compat) ────────
        case 'set_confirmation': {
            // Redirect to add_to_confirmation
            $body['action'] = 'add_to_confirmation';
            // fall-through not possible in PHP switch, so duplicate minimal logic
            $bSysId = $body['booking_sys_id'] ?? '';
            if (!$bSysId) throw new Exception('booking_sys_id is required');
            $row = _fetchByContext($pdo, $taskSysId, $workSysId);
            if (!$row) throw new Exception('Air ticket record not found');
            $bookings      = is_array($row['at_bookings'])     ? $row['at_bookings']     : [];
            $confirmations = is_array($row['at_confirmations']) ? $row['at_confirmations'] : [];
            $sourceB = null;
            foreach ($bookings as $b) { if ($b['sys_id'] === $bSysId) { $sourceB = $b; break; } }
            if (!$sourceB) throw new Exception("Booking '{$bSysId}' not found");
            // Remove any existing entry for this booking first
            $confirmations = array_values(array_filter($confirmations, fn($c) => $c['booking_sys_id'] !== $bSysId));
            $newC = [
                'sys_id'         => _localId($confirmations, 'C'),
                'booking_sys_id' => $bSysId,
                'ticket_nos'     => $body['ticket_nos']  ?? $sourceB['ticket_nos'] ?? [],
                'files_json'     => $body['files_json']  ?? [],
                'note'           => $body['note']        ?? '',
                'status'         => 'pending',
                'added_at'       => date('d-m-Y H:i'),
                'added_by'       => $userName,
            ];
            $confirmations[] = $newC;
            $quotations      = is_array($row['at_quotations']) ? $row['at_quotations'] : [];
            $existingMeta    = json_encode($row['meta_data'], JSON_UNESCAPED_UNICODE);
            _saveRowFull($pdo, $byWork ? $workSysId : $taskSysId, $quotations, $bookings, $confirmations, $existingMeta, $userName, $byWork);
            ob_clean();
            echo json_encode(['status' => 'success', 'message' => 'Confirmation set', 'conf_sys_id' => $newC['sys_id']]);
            break;
        }

        case 'upload_conf_file': {
            // multipart/form-data — $_POST থেকে নিতে হবে, $body থেকে না
            $confSysId = $_POST['conf_sys_id'] ?? '';
            $taskSysId = $_POST['task_sys_id'] ?? $taskSysId; // fallback to already-parsed
            if (!$confSysId) throw new Exception('conf_sys_id required');
            if (empty($_FILES['file'])) throw new Exception('No file uploaded');

            // ── fetch air_tickets row ─────────────────────────
            $row = _fetchByContext($pdo, $taskSysId, $workSysId);
            if (!$row) throw new Exception('Air ticket record not found');

            // ── find conf ─────────────────────────────────────
            $confirmations = is_array($row['at_confirmations']) ? $row['at_confirmations'] : [];
            $confIdx = null;
            foreach ($confirmations as $i => $c) {
                if (($c['sys_id'] ?? '') === $confSysId) { $confIdx = $i; break; }
            }
            if ($confIdx === null) throw new Exception("Confirmation '{$confSysId}' not found");

            // ── Shared upload logic (client lookup, SMB path, file save) ──
            // See server/confirmation_file_upload.php for why this is a shared
            // helper — confirmation uploads always happen BEFORE a task exists.
            require_once __DIR__ . '/../../server/confirmation_file_upload.php';

            $existingFiles = $confirmations[$confIdx]['files_json'] ?? [];
            $saved = uploadConfirmationFile($pdo, [
                'work_sys_id'    => $row['work_sys_id'] ?? $workSysId,
                'service_slug'   => 'air_ticket',
                'conf_sys_id'    => $confSysId,
                'existing_count' => count($existingFiles),
                'uploaded_by'    => $userName,
                'php_file'       => $_FILES['file'],
            ]);
            $tempLocal = $saved['_temp_local'];
            $mimeType  = $saved['mime_type'];

            // ── AI Extraction (air_ticket-specific — temp file delete এর আগে) ──
            $extractedData = null;
            try {
                $isImage = str_starts_with($mimeType, 'image/');
                $isPdf   = $mimeType === 'application/pdf';
                if ($isImage || $isPdf) {
                    $prompt = "Extract flight itinerary details from this travel document (PDF/image) and return ONLY valid JSON matching this exact schema. Extract ALL flight segments in chronological order.

                    SCHEMA:
                    {
                      \"purpose\": [
                        {
                          \"route\": \"\", 
                          \"route_type\": \"One-way/Return/Multi-city\",
                          \"passengers\": [],
                          \"travel_date\": \"Departure Date | Arrival Date\",
                          \"others\": []
                        }
                      ],
                      \"booking_details\": {
                        \"booking_reference_pnr\": \"\",
                        \"booking_platform\": \"\",
                        \"booking_number\": \"\",
                        \"date_of_issue\": \"YYYY-MM-DD\"
                      },
                      \"airline_details\": {
                        \"primary_airline\": \"\",
                        \"airline_pnr\": \"\",
                        \"galileo_pnr\": \"\"
                      },
                      \"passengers\": [
                        {
                          \"name\": {\"first\": \"\", \"last\": \"\"},
                          \"full_name\": \"\",
                          \"type\": \"Adult/Child/Infant\",
                          \"ticket_number\": \"\",
                          \"passport_number\": \"\",
                          \"frequent_flyer_number\": \"\",
                          \"seat_assignment\": \"\"
                        }
                      ],
                      \"journey\": {
                        \"type\": \"One-way/Return/Multi-city\",
                        \"total_passengers\": 0,
                        \"flights\": [
                          {
                            \"segment_id\": 1,
                            \"flight_number\": \"\",
                            \"operating_airline\": \"\",
                            \"marketing_airline\": \"\",
                            \"departure\": {
                              \"city\": \"\",
                              \"airport\": \"\",
                              \"airport_code\": \"\",
                              \"terminal\": \"\",
                              \"date\": \"YYYY-MM-DD\",
                              \"time\": \"HH:MM\",
                              \"full_datetime\": \"\"
                            },
                            \"arrival\": {
                              \"city\": \"\",
                              \"airport\": \"\",
                              \"airport_code\": \"\",
                              \"terminal\": \"\",
                              \"date\": \"YYYY-MM-DD\",
                              \"time\": \"HH:MM\",
                              \"full_datetime\": \"\"
                            },
                            \"duration\": \"\",
                            \"class\": \"\",
                            \"status\": \"\",
                            \"aircraft\": \"\",
                            \"meal\": \"\",
                            \"stops\": 0,
                            \"stopover_info\": [],
                            \"baggage_info\": {
                              \"checked\": \"\",
                              \"cabin\": \"\",
                              \"personal_item\": \"\",
                              \"details\": \"\"
                            },
                            \"special_services\": \"\"
                          }
                        ],
                        \"transfers\": [
                          {
                            \"from_flight\": 1,
                            \"to_flight\": 2,
                            \"transfer_location\": \"\",
                            \"transfer_duration\": \"\",
                            \"transfer_notes\": \"\",
                            \"baggage_checked_through\": true
                          }
                        ]
                      },
                      \"baggage_allowance\": {
                        \"summary\": \"\",
                        \"per_passenger\": [
                          {
                            \"passenger_name\": \"\",
                            \"checked_baggage\": \"\",
                            \"cabin_baggage\": \"\",
                            \"personal_item\": \"\",
                            \"total_weight_allowance\": \"\",
                            \"restrictions\": \"\"
                          }
                        ]
                      },
                      \"fare_details\": {
                        \"base_fare\": {\"amount\": 0, \"currency\": \"\"},
                        \"taxes\": {\"amount\": 0, \"breakdown\": []},
                        \"total_fare\": {\"amount\": 0, \"currency\": \"\"},
                        \"fare_rules\": {
                          \"refundable\": true,
                          \"changeable\": true,
                          \"cancellation_penalty\": \"\",
                          \"validity\": \"\"
                        }
                      },
                      \"important_notes\": [
                        {
                          \"type\": \"check-in/visa/baggage/other\",
                          \"message\": \"\"
                        }
                      ],
                      \"raw_extracted_text\": \"\"
                    }
                    
                    EXTRACTION RULES:
                    
                    PNR IDENTIFICATION (TWO DISTINCT TYPES):
                    1. galileo_pnr: GDS/agency code (labels: RESERVATION CODE, BOOKING REF, PNR, GDS PNR, REC LOC, REFERENCE)
                    2. airline_pnr: Airline-issued code (labels: AIRLINE RES CODE, AIRLINE BOOKING CODE, AIRLINE PNR, CARRIER PNR, CONFIRMATION CODE)
                    → Identify by LABELS & CONTEXT. Populate only available fields.
                    
                    PURPOSE FIELD CONSTRUCTION:
                    • route: Join segments chronologically with hyphen (-) | Use IATA code if present, otherwise full city name | Examples: DAC-SIN, DAC-SIN-DAC, DAC-SIN-DPS
                    • route_type: One-way (no return) | Return (returns to origin) | Multi-city (multiple segments, no return)
                    • passengers: Array of STRINGS | Parse \"LAST/FIRST TITLE\" → \"Salutation FirstName LastName\" | Convert: MR→Mr., MRS→Mrs., MS→Ms., MISS→Miss, DR→Dr.
                    • travel_date: \"DD MMM | DD MMM\" (first departure | final arrival)
                    • others: Array in order [\"GDS: {galileo_pnr}\", \"Airline PNR: {airline_pnr}\", \"Ticket: {ticket1}, {ticket2}\"] | Use \"Not Found\" for missing items
                    
                    FIELD EXTRACTION:
                    • airport_code: Use explicit IATA code; if absent, infer from airport/city name; otherwise null
                    • fare_rules: NON REF/NONEND/NON REFUNDABLE → refundable:false | NON CHANGE/NON ENDORSABLE → changeable:false
                    • passenger_type: Default \"Adult\" unless explicitly \"Child\" or \"Infant\"
                    • transfers: Create only for connecting flights with stopovers; empty array for direct flights
                    • multiple bookings: Create separate purpose objects
                    
                    DEFAULT VALUES: strings=\"\", numbers=0, arrays=[], objects=null";
                    
                    $extractedData = geminiCallWithFile($tempLocal, $mimeType, $prompt, 4092);
                }
            } catch (Exception $aiErr) {
                error_log('[conf upload AI] ' . $aiErr->getMessage());
            }

            // temp file delete করো
            if (file_exists($tempLocal)) unlink($tempLocal);

            // ── files_json update ─────────────────────────────
            $fileEntry = array_merge($saved, ['extracted_data' => $extractedData]);
            unset($fileEntry['_temp_local']);

            $confirmations[$confIdx]['files_json'][] = $fileEntry;

            $quotations   = is_array($row['at_quotations']) ? $row['at_quotations'] : [];
            $bookings     = is_array($row['at_bookings'])   ? $row['at_bookings']   : [];
            $existingMeta = json_encode($row['meta_data'], JSON_UNESCAPED_UNICODE);
            _saveRowFull($pdo, $byWork ? $workSysId : $taskSysId, $quotations, $bookings, $confirmations, $existingMeta, $userName, $byWork);

            ob_clean();
            echo json_encode([
                'status'    => 'success',
                'file_name' => $saved['file_name'],
                'smb_token' => $saved['smb_token'],
                'extracted' => $extractedData !== null,
            ]);
            break;
        }

        case 'delete_conf_file': {
            $confSysId = $body['conf_sys_id'] ?? '';
            $fileIndex = $body['file_index'] ?? null;
            if (!$confSysId) throw new Exception('conf_sys_id required');
            if ($fileIndex === null) throw new Exception('file_index required');

            $row = _fetchByContext($pdo, $taskSysId, $workSysId);
            if (!$row) throw new Exception('Record not found');

            $wSysIdForLock = $row['work_sys_id'] ?? $workSysId;
            if (_atConfirmationLocked($pdo, $wSysIdForLock, $confSysId)) _atLockedResponse();

            $confirmations = is_array($row['at_confirmations']) ? $row['at_confirmations'] : [];
            $confIdx = null;
            foreach ($confirmations as $i => $c) {
                if (($c['sys_id'] ?? '') === $confSysId) { $confIdx = $i; break; }
            }
            if ($confIdx === null) throw new Exception('Confirmation not found');

            $files = $confirmations[$confIdx]['files_json'] ?? [];
            $fileIndex = (int)$fileIndex;
            if (!isset($files[$fileIndex])) throw new Exception('File not found');

            // SMB থেকে delete
            require_once __DIR__ . '/../../server/smb_upload_handler.php';
            require_once __DIR__ . '/../../server/safe_folder_name.php';
            require_once __DIR__ . '/../../server/live_storage.php';
            try {
                $fileName = $files[$fileIndex]['file_name'] ?? '';
                if ($fileName) {
                    $tStmt = $pdo->prepare("
                        SELECT t.work_sys_id,
                               JSON_UNQUOTE(JSON_EXTRACT(w.client_info, '$.sys_id')) AS client_sys_id,
                               JSON_UNQUOTE(JSON_EXTRACT(w.client_info, '$.name'))   AS client_name
                        FROM tasks t JOIN works w ON w.sys_id = t.work_sys_id
                        WHERE t.sys_id = ? LIMIT 1
                    ");
                    $tStmt->execute([$taskSysId]);
                    $tRow = $tStmt->fetch(PDO::FETCH_ASSOC);
                    if ($tRow) {
                        $ctx = [
                            'client_sys_id' => $tRow['client_sys_id'],
                            'client_name'   => $tRow['client_name'],
                            'work_sys_id'   => $tRow['work_sys_id'],
                            'task_sys_id'   => $taskSysId,
                            'module'        => 'files',
                        ];
                        $smbPath = smbBuildPath($ctx) . '/' . $fileName;
                        $omv = new OMV_SMB_Manager();
                        $omv->delete_file($smbPath);
                    }
                }
            } catch (Exception $delErr) {
                error_log('[delete_conf_file SMB] ' . $delErr->getMessage());
            }

            // files_json থেকে remove
            array_splice($files, $fileIndex, 1);
            $confirmations[$confIdx]['files_json'] = array_values($files);

            $quotations   = is_array($row['at_quotations']) ? $row['at_quotations'] : [];
            $bookings     = is_array($row['at_bookings'])   ? $row['at_bookings']   : [];
            $existingMeta = json_encode($row['meta_data'], JSON_UNESCAPED_UNICODE);
            _saveRowFull($pdo, $byWork ? $workSysId : $taskSysId, $quotations, $bookings, $confirmations, $existingMeta, $userName, $byWork);

            ob_clean();
            echo json_encode(['status' => 'success']);
            break;
        }

        default:
            throw new Exception("Unknown action: '{$action}'");
    }

} catch (Exception $e) {
    ob_clean();
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}