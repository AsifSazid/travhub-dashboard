<?php
/**
 * TravHub Smart Upload v3 — List Documents API
 * =============================================
 * Returns a traveler's documents grouped by doc_type or smb_folder.
 * Used by the documents tab on show-travelers.php.
 *
 * Input (GET):
 *   traveler_sys_id:  TR-XXXXXX (required)
 *   group_by:         'doc_type' | 'smb_folder' (default: doc_type)
 *   include_pages:    1 or 0 (default: 1)
 *   include_data:     1 or 0 (default: 0)
 *   status:           comma-separated list, default 'active'
 *
 * Output:
 *   {
 *     success: true,
 *     traveler: {sys_id, name},
 *     total_docs: int,
 *     groups: { "passport": [{...}], ... },
 *     expiring_soon: [{...}]
 *   }
 */

session_start();
require_once '../../server/db_connection.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
error_reporting(E_ALL);
ini_set('display_errors', 0);

$travelerSysId = trim($_GET['traveler_sys_id'] ?? '');
if ($travelerSysId === '') {
    echo json_encode(['success' => false, 'error' => 'traveler_sys_id required']);
    exit;
}

$groupBy      = $_GET['group_by']      ?? 'doc_type';
$includePages = (int)($_GET['include_pages'] ?? 1) === 1;
$includeData  = (int)($_GET['include_data']  ?? 0) === 1;
$statusList   = $_GET['status'] ?? 'active';

if (!in_array($groupBy, ['doc_type', 'smb_folder'], true)) {
    $groupBy = 'doc_type';
}

$statuses = array_filter(array_map('trim', explode(',', $statusList)));
if (empty($statuses)) $statuses = ['active'];
$validStatuses = ['pending', 'active', 'expired', 'archived', 'deleted'];
$statuses = array_values(array_intersect($statuses, $validStatuses));
if (empty($statuses)) $statuses = ['active'];

// ============================================================================
// Fetch traveler
// ============================================================================
$stmt = $pdo->prepare("SELECT sys_id, name FROM travelers WHERE sys_id = ? LIMIT 1");
$stmt->execute([$travelerSysId]);
$traveler = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$traveler) {
    echo json_encode(['success' => false, 'error' => 'Traveler not found']);
    exit;
}

// ============================================================================
// Build column list
// ============================================================================
$cols = [
    'd.sys_id', 'd.doc_type', 'd.doc_subtype', 'd.doc_number', 'd.suggested_filename_stem',
    'd.confidence', 'd.classification_mode', 'd.passport_status',
    'd.original_filename', 'd.file_size', 'd.mime_type',
    'd.page_count', 'd.smb_folder', 'd.server_path',
    'd.summary', 'd.language',
    'd.country', 'd.validity_from', 'd.validity_to',
    'd.issue_date', 'd.expiry_date',
    'd.verification_status', 'd.verified_at', 'd.verified_by',
    'd.is_primary', 'd.status', 'd.created_at', 'd.updated_at',
    // doc_type_registry থেকে validity rule columns
    'r.validity_mode', 'r.auto_validity_days', 'r.expired_label', 'r.validity_ui_label',
];
if ($includePages) $cols[] = 'd.pages';
if ($includeData)  { $cols[] = 'd.doc_data'; $cols[] = 'd.key_fields'; }

$colSql = implode(', ', $cols);

// ============================================================================
// Fetch docs — LEFT JOIN doc_type_registry
// ============================================================================
$placeholders = implode(',', array_fill(0, count($statuses), '?'));
$sql = "SELECT {$colSql}
        FROM traveler_documents d
        LEFT JOIN doc_type_registry r ON r.doc_type = d.doc_type
        WHERE d.traveler_id = ?
          AND d.status IN ({$placeholders})
        ORDER BY
          CASE d.doc_type
            WHEN 'passport' THEN 1
            WHEN 'nid'      THEN 2
            WHEN 'visa'     THEN 3
            WHEN 'air_ticket'     THEN 4
            WHEN 'hotel_voucher'  THEN 5
            ELSE 99
          END,
          d.created_at DESC";

$params = array_merge([$traveler['sys_id']], $statuses);
$stmt   = $pdo->prepare($sql);
$stmt->execute($params);
$docs = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ============================================================================
// Decorate + group
// ============================================================================
$groups       = [];
$expiringSoon = [];
$today        = strtotime(date('Y-m-d'));

foreach ($docs as &$doc) {

    // ── Pages JSON parse ──────────────────────────────────────────────────
    if (isset($doc['pages']) && $doc['pages']) {
        $pages = json_decode($doc['pages'], true) ?: [];
        foreach ($pages as &$p) {
            $p['url'] = buildFileUrl($doc['sys_id'], $p['filename'] ?? '');
        }
        unset($p);
        $doc['pages'] = $pages;
    }

    // ── doc_data / key_fields ─────────────────────────────────────────────
    if ($includeData) {
        if (!empty($doc['doc_data']))   $doc['doc_data']   = json_decode($doc['doc_data'],   true);
        if (!empty($doc['key_fields'])) $doc['key_fields'] = json_decode($doc['key_fields'], true);
    }

    // ── Effective expiry — registry rule দিয়ে compute ────────────────────
    $mode     = $doc['validity_mode']     ?? 'none';
    $autoDays = (int)($doc['auto_validity_days'] ?? 0);
    $anchor   = null;

    switch ($mode) {
        case 'expiry_date':
            $anchor = !empty($doc['expiry_date']) ? $doc['expiry_date'] : null;
            break;

        case 'validity_range':
            $anchor = !empty($doc['validity_to']) ? $doc['validity_to'] : null;
            break;

        case 'auto_from_issue':
            if (!empty($doc['issue_date']) && $autoDays > 0) {
                $anchor = date('Y-m-d', strtotime($doc['issue_date'] . " +{$autoDays} days"));
            }
            break;

        case 'auto_from_upload':
            if ($autoDays > 0 && !empty($doc['created_at'])) {
                $anchor = date('Y-m-d', strtotime($doc['created_at'] . " +{$autoDays} days"));
            }
            break;

        case 'entry_exit':
            // expiry নেই — entry/exit date + stay days হিসাব
            $doc['stay_days'] = null;
            if (!empty($doc['validity_from']) && !empty($doc['validity_to'])) {
                $doc['stay_days'] = (int)floor(
                    (strtotime($doc['validity_to']) - strtotime($doc['validity_from'])) / 86400
                );
            }
            break;

        case 'none':
        default:
            $anchor = null;
            break;
    }

    $doc['effective_expiry'] = $anchor;

    // ── Days until expiry + flags ─────────────────────────────────────────
    $doc['days_until_expiry'] = null;
    $doc['is_expiring_soon']  = false;
    $doc['is_expired']        = false;

    if ($anchor) {
        $days = (int)floor((strtotime($anchor) - $today) / 86400);
        $doc['days_until_expiry'] = $days;
        $doc['is_expired']        = $days < 0;
        $doc['is_expiring_soon']  = $days >= 0 && $days <= 30;

        if ($doc['is_expiring_soon'] || $doc['is_expired']) {
            $expiringSoon[] = [
                'sys_id'        => $doc['sys_id'],
                'doc_type'      => $doc['doc_type'],
                'doc_number'    => $doc['doc_number'],
                'expiry_date'   => $anchor,
                'days_left'     => $days,
                'is_expired'    => $doc['is_expired'],
                'expired_label' => $doc['expired_label'] ?? 'Expired', // ← নতুন line
            ];
        }
    }

    // ── Group ─────────────────────────────────────────────────────────────
    $groupKey = $doc[$groupBy];
    if (!isset($groups[$groupKey])) $groups[$groupKey] = [];
    $groups[$groupKey][] = $doc;
}
unset($doc);

usort($expiringSoon, fn($a, $b) => $a['days_left'] <=> $b['days_left']);

// ============================================================================
// Response
// ============================================================================
echo json_encode([
    'success'       => true,
    'traveler'      => ['sys_id' => $traveler['sys_id'], 'name' => $traveler['name']],
    'total_docs'    => count($docs),
    'group_by'      => $groupBy,
    'groups'        => $groups,
    'expiring_soon' => $expiringSoon,
]);

// ============================================================================
// Helpers
// ============================================================================
function buildFileUrl($docSysId, $filename) {
    if (!$docSysId || !$filename) return null;
    return '/api/file/serve.php?doc_id=' . urlencode($docSysId) . '&file=' . urlencode($filename);
}