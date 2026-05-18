<?php
/**
 * TravHub Smart Upload v3 — Regenerate Traveler Summary API
 * =========================================================
 * Endpoint called in two scenarios:
 *   1. Frontend signals "batch-end" after commit-documents completes
 *      (trigger=document_upload)
 *   2. Agent clicks "Regenerate Summary" button on traveler profile
 *      (trigger=manual)
 *
 * Both scenarios run synchronously and return the new summary.
 * (No cron — Option B from spec.)
 *
 * Input (POST, JSON or form):
 *   traveler_sys_id:        TR-XXXXXX
 *   trigger:                'document_upload' | 'manual'
 *   information_updated_for: optional override; otherwise read from
 *                            travelers.summary_pending_trigger
 *
 * Output:
 *   { success, summary: {...}, tokens_used, error }
 */

session_start();
require_once '../../server/db_connection.php';
require_once '../../server/summary-generator.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
error_reporting(E_ALL);
ini_set('display_errors', 0);

// ============================================================================
// Parse input (support both JSON body and form post)
// ============================================================================
$travelerSysId = '';
$trigger       = '';
$infoUpdated   = '';

$raw = file_get_contents('php://input');
if (!empty($raw)) {
    $body = json_decode($raw, true);
    if (is_array($body)) {
        $travelerSysId = trim($body['traveler_sys_id'] ?? '');
        $trigger       = trim($body['trigger'] ?? '');
        $infoUpdated   = trim($body['information_updated_for'] ?? '');
    }
}
if ($travelerSysId === '') {
    $travelerSysId = trim($_POST['traveler_sys_id'] ?? $_GET['traveler_sys_id'] ?? '');
    $trigger       = trim($_POST['trigger']         ?? $_GET['trigger']         ?? '');
    $infoUpdated   = trim($_POST['information_updated_for'] ?? $_GET['information_updated_for'] ?? '');
}

if (empty($travelerSysId)) {
    echo json_encode(['success' => false, 'error' => 'traveler_sys_id required']);
    exit;
}

// Validate trigger
if (!in_array($trigger, ['document_upload', 'manual'], true)) {
    $trigger = 'manual';
}

// ============================================================================
// Fetch traveler
// ============================================================================
$stmt = $pdo->prepare("SELECT id, sys_id, name, summary_pending_trigger
                       FROM travelers WHERE sys_id = ? LIMIT 1");
$stmt->execute([$travelerSysId]);
$traveler = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$traveler) {
    echo json_encode(['success' => false, 'error' => 'Traveler not found']);
    exit;
}

// Resolve information_updated_for
if (empty($infoUpdated)) {
    $infoUpdated = !empty($traveler['summary_pending_trigger'])
        ? $traveler['summary_pending_trigger']
        : ($trigger === 'manual' ? 'Manual regeneration' : 'Document update');
}

$actor = $_SESSION['user_name'] ?? 'system';

// ============================================================================
// Generate
// ============================================================================
try {
    $result = regenerateTravelerSummary(
        $pdo,
        (int)$traveler['id'],
        $actor,
        $trigger,
        $infoUpdated
    );

    if (!$result['success']) {
        echo json_encode([
            'success' => false,
            'error'   => $result['error'],
        ]);
        exit;
    }

    // ----- Persist -----
    $upd = $pdo->prepare("
        UPDATE travelers
        SET summary               = ?,
            previous_summary      = ?,
            summary_dirty         = 0,
            summary_pending_trigger = NULL,
            summary_updated_at    = NOW()
        WHERE id = ?
    ");
    $upd->execute([
        $result['summary'],
        $result['previous_summary'],
        (int)$traveler['id'],
    ]);

    // ----- Respond with parsed summary so frontend can render immediately -----
    echo json_encode([
        'success'      => true,
        'summary'      => json_decode($result['summary'], true),
        'tokens_used'  => $result['tokens_used'],
        'trigger'      => $trigger,
        'info_updated_for' => $infoUpdated,
    ]);

} catch (Exception $e) {
    error_log('[regenerate-summary] ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error'   => 'Regeneration failed: ' . $e->getMessage(),
    ]);
}