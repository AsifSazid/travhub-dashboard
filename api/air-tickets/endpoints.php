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

require_once '../../server/api_bootstrap.php';
require_once '../../server/db_connection.php';
require_once '../../server/sys_id_generator_v2.php';
require_once '../../server/generate_meta_data.php';

$method   = $_SERVER['REQUEST_METHOD'];
$action   = $_GET['action'] ?? '';
$userName = $_SESSION['user_name'] ?? 'system';

// POST body parse
$body = [];
if ($method === 'POST') {
    $raw  = file_get_contents('php://input');
    $body = json_decode($raw, true) ?? [];
    $action = $body['action'] ?? $action;
}

// ── Helper: fetch air_tickets row by task_sys_id ─────────────
function _fetchRow(PDO $pdo, string $taskSysId): ?array
{
    $s = $pdo->prepare("SELECT * FROM air_tickets WHERE task_sys_id = ? LIMIT 1");
    $s->execute([$taskSysId]);
    $row = $s->fetch(PDO::FETCH_ASSOC);
    if (!$row) return null;

    foreach (['at_quotations', 'at_bookings', 'at_confirmations'] as $col) {
        $row[$col] = (isset($row[$col]) && $row[$col]) ? json_decode($row[$col], true) : [];
    }
    // Legacy at_confirmation (single object) — migrate to array if needed
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
function _saveRow(PDO $pdo, string $taskSysId, array $quotations, array $bookings, $confirmation, string $existingMeta, string $userName): void
{
    $meta = buildMetaData($existingMeta, $userName);
    $pdo->prepare("
        UPDATE air_tickets
        SET at_quotations=?, at_bookings=?, at_confirmation=?, meta_data=?
        WHERE task_sys_id=?
    ")->execute([
        json_encode($quotations, JSON_UNESCAPED_UNICODE),
        json_encode($bookings,   JSON_UNESCAPED_UNICODE),
        $confirmation !== null ? json_encode($confirmation, JSON_UNESCAPED_UNICODE) : null,
        $meta,
        $taskSysId,
    ]);
}

// Save including at_confirmations array
function _saveRowFull(PDO $pdo, string $taskSysId, array $quotations, array $bookings, array $confirmations, string $existingMeta, string $userName): void
{
    $meta = buildMetaData($existingMeta, $userName);
    $pdo->prepare("
        UPDATE air_tickets
        SET at_quotations=?, at_bookings=?, at_confirmations=?, meta_data=?
        WHERE task_sys_id=?
    ")->execute([
        json_encode($quotations,   JSON_UNESCAPED_UNICODE),
        json_encode($bookings,     JSON_UNESCAPED_UNICODE),
        json_encode($confirmations, JSON_UNESCAPED_UNICODE),
        $meta,
        $taskSysId,
    ]);
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

// ─────────────────────────────────────────────────────────────
try {

    // ════════════════════════════════════════════════════════
    // GET — fetch full record
    // ════════════════════════════════════════════════════════
    if ($method === 'GET' && $action === 'get') {
        $taskSysId = $_GET['task_sys_id'] ?? '';
        if (!$taskSysId) throw new Exception('task_sys_id is required');

        $row = _fetchRow($pdo, $taskSysId);

        ob_clean();
        echo json_encode([
            'status' => 'success',
            'data'   => $row, // null হলে frontend init করবে
        ]);
        exit;
    }

    // ════════════════════════════════════════════════════════
    // POST — all write actions
    // ════════════════════════════════════════════════════════
    if ($method !== 'POST') throw new Exception('Method not allowed');

    $taskSysId = $body['task_sys_id'] ?? '';
    if (!$taskSysId) throw new Exception('task_sys_id is required');

    switch ($action) {

        // ── INIT ─────────────────────────────────────────────
        // Task এ প্রথমবার air_tickets row তৈরি করে
        // Frontend: task load হলে get করে, null হলে init call করে
        case 'init': {
            $workSysId = $body['work_sys_id'] ?? '';
            $leadSysId = $body['lead_sys_id'] ?? null;
            if (!$workSysId) throw new Exception('work_sys_id is required');

            // Already exists?
            $existing = _fetchRow($pdo, $taskSysId);
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
            ")->execute([$ids['uuid'], $ids['sys_id'], $leadSysId, $workSysId, $taskSysId, $meta]);

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
            $row = _fetchRow($pdo, $taskSysId);
            if (!$row) throw new Exception('Air ticket record not found. Call init first.');

            $quotations = is_array($row['at_quotations']) ? $row['at_quotations'] : [];

            $newQ = [
                'sys_id'        => _localId($quotations, 'Q'),
                'type'          => $body['type']          ?? 'gds',    // gds | soto
                'title'         => $body['title']         ?? '',
                'airline'       => $body['airline']       ?? '',
                'segments_json' => $body['segments_json'] ?? [],
                'pax'           => $body['pax']           ?? [],
                'pricing_json'  => $body['pricing_json']  ?? [],
                'raw_input'     => $body['raw_input']     ?? '',
                'copy_text'     => $body['copy_text']     ?? '',
                'gross_fare'    => (float)($body['gross_fare']    ?? 0),
                'net_fare'      => (float)($body['net_fare']      ?? 0),
                'total_payable' => (float)($body['total_payable'] ?? 0),
                'status'        => 'draft',
                'created_at'    => date('d-m-Y H:i'),
                'created_by'    => $userName,
            ];

            $quotations[] = $newQ;

            $existingMeta = json_encode($row['meta_data'], JSON_UNESCAPED_UNICODE);
            _saveRow($pdo, $taskSysId, $quotations, is_array($row['at_bookings']) ? $row['at_bookings'] : [], $row['at_confirmation'] ?: null, $existingMeta, $userName);

            ob_clean();
            echo json_encode(['status' => 'success', 'message' => 'Quotation saved', 'quotation_sys_id' => $newQ['sys_id']]);
            break;
        }

        // ── UPDATE QUOTATION ──────────────────────────────────
        // Existing quotation update — sys_id দিয়ে match করে
        case 'update_quotation': {
            $qSysId = $body['quotation_sys_id'] ?? '';
            if (!$qSysId) throw new Exception('quotation_sys_id is required');

            $row = _fetchRow($pdo, $taskSysId);
            if (!$row) throw new Exception('Air ticket record not found');

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
                    $q['updated_at'] = date('d-m-Y H:i');
                    $q['updated_by'] = $userName;
                    $found = true;
                    break;
                }
            }
            unset($q);

            if (!$found) throw new Exception("Quotation '{$qSysId}' not found");

            $existingMeta = json_encode($row['meta_data'], JSON_UNESCAPED_UNICODE);
            _saveRow($pdo, $taskSysId, $quotations, is_array($row['at_bookings']) ? $row['at_bookings'] : [], $row['at_confirmation'] ?: null, $existingMeta, $userName);

            ob_clean();
            echo json_encode(['status' => 'success', 'message' => 'Quotation updated']);
            break;
        }

        // ── DELETE QUOTATION ──────────────────────────────────
        case 'delete_quotation': {
            $qSysId = $body['quotation_sys_id'] ?? '';
            if (!$qSysId) throw new Exception('quotation_sys_id is required');

            $row = _fetchRow($pdo, $taskSysId);
            if (!$row) throw new Exception('Air ticket record not found');

            $quotations = array_values(array_filter(
                is_array($row['at_quotations']) ? $row['at_quotations'] : [],
                fn($q) => $q['sys_id'] !== $qSysId
            ));

            $existingMeta = json_encode($row['meta_data'], JSON_UNESCAPED_UNICODE);
            _saveRow($pdo, $taskSysId, $quotations, is_array($row['at_bookings']) ? $row['at_bookings'] : [], $row['at_confirmation'] ?: null, $existingMeta, $userName);

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

            $row = _fetchRow($pdo, $taskSysId);
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
            _saveRow($pdo, $taskSysId, $quotations, is_array($row['at_bookings']) ? $row['at_bookings'] : [], $row['at_confirmation'] ?: null, $existingMeta, $userName);

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

            $row = _fetchRow($pdo, $taskSysId);
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
                'status'           => 'tentative',
                'created_at'       => date('d-m-Y H:i'),
                'created_by'       => $userName,
            ];

            $bookings[] = $newB;

            $existingMeta = json_encode($row['meta_data'], JSON_UNESCAPED_UNICODE);
            _saveRow($pdo, $taskSysId, $quotations, $bookings, $row['at_confirmation'] ?: null, $existingMeta, $userName);

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
            $row = _fetchRow($pdo, $taskSysId);
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
            _saveRow($pdo, $taskSysId, $quotations, $bookings, $row['at_confirmation'] ?: null, $existingMeta, $userName);

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

            $row = _fetchRow($pdo, $taskSysId);
            if (!$row) throw new Exception('Air ticket record not found');

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
                    if (isset($body['status']))        $b['status']        = $body['status'];
                    $b['updated_at'] = date('d-m-Y H:i');
                    $b['updated_by'] = $userName;
                    $found = true;
                    break;
                }
            }
            unset($b);

            if (!$found) throw new Exception("Booking '{$bSysId}' not found");

            // ── Quotation revision ────────────────────────────
            // Updated booking data from $bookings array (already updated above)
            $updatedB = null;
            foreach ($bookings as $b) {
                if ($b['sys_id'] === $bSysId) { $updatedB = $b; break; }
            }

            $newQ = null;
            if ($updatedB) {
                $newQ = [
                    'sys_id'         => _localId($quotations, 'Q'),
                    'source_booking' => $bSysId,
                    'ref_quotation'  => $sourceQSysId,
                    'type'           => $updatedB['type']          ?? 'gds',
                    'title'          => ($updatedB['title'] ?? ($updatedB['airline'] ?? '')) . ' [B-' . substr($bSysId, -3) . ' Revision]',
                    'airline'        => $updatedB['airline']       ?? '',
                    'segments_json'  => $updatedB['segments_json'] ?? [],
                    'pricing_json'   => $updatedB['pricing_json']  ?? [],
                    'raw_input'      => $updatedB['raw_input']     ?? '',
                    'copy_text'      => $updatedB['copy_text']     ?? '',
                    'gross_fare'     => (float)($updatedB['gross_fare']    ?? 0),
                    'net_fare'       => (float)($updatedB['net_fare']      ?? 0),
                    'total_payable'  => (float)($updatedB['total_payable'] ?? 0),
                    'status'         => 'draft',
                    'note'           => "Auto-revision from booking update ({$bSysId})",
                    'created_at'     => date('d-m-Y H:i'),
                    'created_by'     => $userName,
                ];
                $quotations[] = $newQ;
            }

            $existingMeta = json_encode($row['meta_data'], JSON_UNESCAPED_UNICODE);
            _saveRow($pdo, $taskSysId, $quotations, $bookings, $row['at_confirmation'] ?: null, $existingMeta, $userName);

            ob_clean();
            echo json_encode([
                'status'               => 'success',
                'message'              => 'Booking updated' . ($updatedB ? ' & quotation revision created' : ''),
                'new_quotation_sys_id' => $updatedB ? $newQ['sys_id'] : null,
            ]);
            break;
        }

        // ── DELETE BOOKING ────────────────────────────────────
        case 'delete_booking': {
            $bSysId = $body['booking_sys_id'] ?? '';
            if (!$bSysId) throw new Exception('booking_sys_id is required');

            $row = _fetchRow($pdo, $taskSysId);
            if (!$row) throw new Exception('Air ticket record not found');

            $bookings = array_values(array_filter(
                is_array($row['at_bookings']) ? $row['at_bookings'] : [],
                fn($b) => $b['sys_id'] !== $bSysId
            ));

            // Confirmation এ এই booking ছিল কিনা check
            $confirmation = $row['at_confirmation'];
            if (is_array($confirmation) && ($confirmation['booking_sys_id'] ?? '') === $bSysId) {
                $confirmation = null; // Confirmation ও clear হবে
            }

            $quotations   = is_array($row['at_quotations']) ? $row['at_quotations'] : [];
            $existingMeta = json_encode($row['meta_data'], JSON_UNESCAPED_UNICODE);
            _saveRow($pdo, $taskSysId, $quotations, $bookings, $confirmation, $existingMeta, $userName);

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

            $row = _fetchRow($pdo, $taskSysId);
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
            _saveRow($pdo, $taskSysId, $quotations, $bookings, $row['at_confirmation'] ?: null, $existingMeta, $userName);

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

            $row = _fetchRow($pdo, $taskSysId);
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
            _saveRowFull($pdo, $taskSysId, $quotations, $bookings, $confirmations, $existingMeta, $userName);

            ob_clean();
            echo json_encode(['status' => 'success', 'message' => 'Added to confirmation', 'conf_sys_id' => $newC['sys_id']]);
            break;
        }

        // ── UPDATE CONFIRMATION ───────────────────────────────
        // ticket_nos, note, files_json update
        case 'update_confirmation': {
            $confSysId = $body['conf_sys_id'] ?? '';
            if (!$confSysId) throw new Exception('conf_sys_id is required');

            $row = _fetchRow($pdo, $taskSysId);
            if (!$row) throw new Exception('Air ticket record not found');

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
            _saveRowFull($pdo, $taskSysId, $quotations, $bookings, $confirmations, $existingMeta, $userName);

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

            $row = _fetchRow($pdo, $taskSysId);
            if (!$row) throw new Exception('Air ticket record not found');

            $confirmations = is_array($row['at_confirmations']) ? $row['at_confirmations'] : [];
            $found = false;
            foreach ($confirmations as &$c) {
                if ($c['sys_id'] === $confSysId) {
                    $c['status']     = $newStatus;
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
            _saveRowFull($pdo, $taskSysId, $quotations, $bookings, $confirmations, $existingMeta, $userName);

            ob_clean();
            echo json_encode(['status' => 'success', 'message' => 'Confirmation status updated']);
            break;
        }

        // ── REMOVE CONFIRMATION ───────────────────────────────
        // Failed/cancelled confirmation remove করা
        case 'remove_confirmation': {
            $confSysId = $body['conf_sys_id'] ?? '';
            if (!$confSysId) throw new Exception('conf_sys_id is required');

            $row = _fetchRow($pdo, $taskSysId);
            if (!$row) throw new Exception('Air ticket record not found');

            $confirmations = is_array($row['at_confirmations']) ? $row['at_confirmations'] : [];
            // Only allow removing failed/cancelled ones
            foreach ($confirmations as $c) {
                if ($c['sys_id'] === $confSysId && !in_array($c['status']??'pending', ['failed','cancelled'])) {
                    throw new Exception('Only failed or cancelled confirmations can be removed');
                }
            }
            $confirmations = array_values(array_filter($confirmations, fn($c) => $c['sys_id'] !== $confSysId));

            $quotations   = is_array($row['at_quotations']) ? $row['at_quotations'] : [];
            $bookings     = is_array($row['at_bookings'])   ? $row['at_bookings']   : [];
            $existingMeta = json_encode($row['meta_data'], JSON_UNESCAPED_UNICODE);
            _saveRowFull($pdo, $taskSysId, $quotations, $bookings, $confirmations, $existingMeta, $userName);

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
            $row = _fetchRow($pdo, $taskSysId);
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
            _saveRowFull($pdo, $taskSysId, $quotations, $bookings, $confirmations, $existingMeta, $userName);
            ob_clean();
            echo json_encode(['status' => 'success', 'message' => 'Confirmation set', 'conf_sys_id' => $newC['sys_id']]);
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