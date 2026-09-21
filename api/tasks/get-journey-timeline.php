<?php
/**
 * FILE PATH: /api/tasks/get-journey-timeline.php
 * GET ?task_sys_id=THR-A26-TK-0001
 *
 * Traces the full journey that produced a task, backward from its
 * confirmation_sys_id:
 *   Confirmation → (booking_sys_id) Booking → (quotation_sys_id) Quotation
 *   → (source_note_ids) Note(s)
 *
 * Returns a flat list of events, each tagged with a stage and timestamp,
 * so the frontend can render them as a single newest-first timeline.
 * Only currently supports the air_ticket service (same as the rest of
 * the air-tickets module) — other services return an empty events list.
 */

ob_start();
session_start();
date_default_timezone_set('Asia/Dhaka');
header('Content-Type: application/json');
ini_set('display_errors', 0);

require '../../server/db_connection.php';

$taskSysId = $_GET['task_sys_id'] ?? '';
if (!$taskSysId) {
    ob_clean(); http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'task_sys_id is required']);
    exit;
}

// Parse 'd-m-Y H:i' (used everywhere in this codebase) into a sortable
// timestamp; unparsable/missing dates sort last (treated as very old).
function _tlParseDate(?string $s): int
{
    if (!$s) return 0;
    $dt = DateTime::createFromFormat('d-m-Y H:i', $s);
    return $dt ? $dt->getTimestamp() : 0;
}

try {
    $t = $pdo->prepare("SELECT sys_id, work_sys_id, service_slug, confirmation_sys_id, workname, status, meta_data FROM tasks WHERE sys_id = ? LIMIT 1");
    $t->execute([$taskSysId]);
    $task = $t->fetch(PDO::FETCH_ASSOC);

    if (!$task) {
        ob_clean(); http_response_code(404);
        echo json_encode(['status' => 'error', 'message' => 'Task not found']);
        exit;
    }

    $events = [];

    if ($task['service_slug'] === 'air_ticket' && $task['work_sys_id'] && $task['confirmation_sys_id']) {
        $atStmt = $pdo->prepare("SELECT at_quotations, at_bookings, at_confirmations FROM air_tickets WHERE work_sys_id = ? LIMIT 1");
        $atStmt->execute([$task['work_sys_id']]);
        $at = $atStmt->fetch(PDO::FETCH_ASSOC);

        if ($at) {
            $quotations    = json_decode($at['at_quotations']    ?? '[]', true) ?: [];
            $bookings      = json_decode($at['at_bookings']      ?? '[]', true) ?: [];
            $confirmations = json_decode($at['at_confirmations'] ?? '[]', true) ?: [];

            $confirmation = null;
            foreach ($confirmations as $c) { if ($c['sys_id'] === $task['confirmation_sys_id']) { $confirmation = $c; break; } }

            $booking = null;
            if ($confirmation) {
                foreach ($bookings as $b) { if ($b['sys_id'] === ($confirmation['booking_sys_id'] ?? null)) { $booking = $b; break; } }
            }

            $quotation = null;
            if ($booking) {
                foreach ($quotations as $q) { if ($q['sys_id'] === ($booking['quotation_sys_id'] ?? null)) { $quotation = $q; break; } }
            }

            // ── Notes (earliest in the chain) ──
            if ($quotation && !empty($quotation['source_note_ids'])) {
                $ids = $quotation['source_note_ids'];
                $placeholders = implode(',', array_fill(0, count($ids), '?'));
                $nStmt = $pdo->prepare("SELECT sys_id, note_type, content, meta_data FROM task_notes WHERE sys_id IN ($placeholders)");
                $nStmt->execute($ids);
                foreach ($nStmt->fetchAll(PDO::FETCH_ASSOC) as $n) {
                    $meta = $n['meta_data'] ? (json_decode($n['meta_data'], true) ?: []) : [];
                    $created = $meta['created_by_date']['date'] ?? null;
                    $events[] = [
                        'stage'     => 'note',
                        'sys_id'    => $n['sys_id'],
                        'title'     => 'Mind Board Note',
                        'summary'   => $n['note_type'] === 'text' ? mb_strimwidth(strip_tags($n['content'] ?? ''), 0, 140, '…') : ucfirst($n['note_type'] ?? 'media') . ' note',
                        'by'        => $meta['created_by_date']['user'] ?? null,
                        'at'        => $created,
                        'raw'       => ['note_type' => $n['note_type'], 'content' => $n['content']],
                        '_ts'       => _tlParseDate($created),
                    ];
                }
            }

            // ── Quotation ──
            if ($quotation) {
                $events[] = [
                    'stage'   => 'quotation',
                    'sys_id'  => $quotation['sys_id'],
                    'title'   => 'Quotation ' . $quotation['sys_id'] . ($quotation['title'] ? ' — ' . $quotation['title'] : ''),
                    'summary' => trim(($quotation['airline'] ?? '') . ' · ৳' . number_format((float)($quotation['total_payable'] ?? 0), 2)),
                    'by'      => $quotation['created_by'] ?? null,
                    'at'      => $quotation['created_at'] ?? null,
                    'raw'     => $quotation,
                    '_ts'     => _tlParseDate($quotation['created_at'] ?? null),
                ];
            }

            // ── Booking ──
            if ($booking) {
                $events[] = [
                    'stage'   => 'booking',
                    'sys_id'  => $booking['sys_id'],
                    'title'   => 'Booking ' . $booking['sys_id'] . ($booking['pnr'] ? ' — PNR ' . $booking['pnr'] : ''),
                    'summary' => trim(($booking['airline'] ?? '') . ' · ৳' . number_format((float)($booking['total_payable'] ?? 0), 2)),
                    'by'      => $booking['created_by'] ?? null,
                    'at'      => $booking['created_at'] ?? null,
                    'raw'     => $booking,
                    '_ts'     => _tlParseDate($booking['created_at'] ?? null),
                ];
            }

            // ── Confirmation ──
            if ($confirmation) {
                $ticketNos = implode(', ', $confirmation['ticket_nos'] ?? []);
                $events[] = [
                    'stage'   => 'confirmation',
                    'sys_id'  => $confirmation['sys_id'],
                    'title'   => 'Confirmation ' . $confirmation['sys_id'] . ($ticketNos ? ' — ' . $ticketNos : ''),
                    'summary' => 'Status: ' . ($confirmation['status'] ?? 'pending') . ($confirmation['note'] ? ' · ' . $confirmation['note'] : ''),
                    'by'      => $confirmation['added_by'] ?? null,
                    'at'      => $confirmation['added_at'] ?? null,
                    'raw'     => $confirmation,
                    '_ts'     => _tlParseDate($confirmation['added_at'] ?? null),
                ];
            }

            // ── Task creation (end of chain) ──
            $taskMeta = $task['meta_data'] ? (json_decode($task['meta_data'], true) ?: []) : [];
            $taskCreated = $taskMeta['created_by_date']['date'] ?? null;
            $events[] = [
                'stage'   => 'task',
                'sys_id'  => $task['sys_id'],
                'title'   => 'Task Created — ' . ($task['workname'] ?? $task['sys_id']),
                'summary' => 'Status: ' . ($task['status'] ?? 'open'),
                'by'      => $taskMeta['created_by_date']['user'] ?? null,
                'at'      => $taskCreated,
                'raw'     => $task,
                '_ts'     => $taskCreated ? _tlParseDate($taskCreated) : PHP_INT_MAX,
            ];
        }
    }

    // Newest first
    usort($events, fn($a, $b) => $b['_ts'] <=> $a['_ts']);
    foreach ($events as &$e) unset($e['_ts']);
    unset($e);

    ob_clean();
    echo json_encode(['status' => 'success', 'events' => $events]);

} catch (Throwable $e) {
    ob_clean(); http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}