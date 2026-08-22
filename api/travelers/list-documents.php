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
 *   include_data:     1 or 0 (default: 0 - excludes doc_data/ocr to keep response small)
 *   status:           comma-separated list, default 'active'
 *
 * Output:
 *   {
 *     success: true,
 *     traveler: {sys_id, name},
 *     total_docs: int,
 *     groups: {
 *       "passport": [{sys_id, doc_type, doc_number, pages, ...}, ...],
 *       "visa":     [...],
 *       ...
 *     },
 *     expiring_soon: [{doc_type, expiry_date, days_left, sys_id}, ...]
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

// Parse status into array
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
// Build column list (skip heavy columns unless requested)
// ============================================================================
$cols = [
    'sys_id', 'doc_type', 'doc_subtype', 'doc_number', 'suggested_filename_stem',
    'confidence', 'classification_mode', 'passport_status',
    'original_filename', 'file_size', 'mime_type',
    'page_count', 'smb_folder', 'server_path',
    'summary', 'language',
    'country', 'validity_from', 'validity_to',
    'issue_date', 'expiry_date',
    'verification_status', 'verified_at', 'verified_by',
    'is_primary', 'status', 'created_at', 'updated_at',
];
if ($includePages) {
    $cols[] = 'pages';
}
if ($includeData) {
    $cols[] = 'doc_data';
    $cols[] = 'key_fields';
}

$colSql = implode(', ', $cols);

// ============================================================================
// Fetch docs
// ============================================================================
$placeholders = implode(',', array_fill(0, count($statuses), '?'));
$sql = "SELECT {$colSql}
        FROM traveler_documents
        WHERE traveler_id = ?
          AND status IN ({$placeholders})
        ORDER BY
          CASE doc_type
            WHEN 'passport' THEN 1
            WHEN 'nid' THEN 2
            WHEN 'visa' THEN 3
            WHEN 'air_ticket' THEN 4
            WHEN 'hotel_voucher' THEN 5
            ELSE 99
          END,
          created_at DESC";

$params = array_merge([$traveler['sys_id']], $statuses);
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$docs = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ============================================================================
// Decorate + group
// ============================================================================
$groups = [];
$expiringSoon = [];
$today = strtotime(date('Y-m-d'));

foreach ($docs as &$doc) {
    // Parse pages JSON
    if (isset($doc['pages']) && $doc['pages']) {
        $pages = json_decode($doc['pages'], true) ?: [];
        // Build URLs via serve.php?doc_id=SYS_ID&file=FILENAME
        if (!empty($pages) && !empty($doc['sys_id'])) {
            foreach ($pages as &$p) {
                $p['url'] = buildFileUrl($doc['sys_id'], $p['filename'] ?? '');
            }
            unset($p);
        }
        $doc['pages'] = $pages;
    }

    // Parse doc_data/key_fields if requested
    if ($includeData) {
        if (!empty($doc['doc_data']))   $doc['doc_data']   = json_decode($doc['doc_data'],   true);
        if (!empty($doc['key_fields'])) $doc['key_fields'] = json_decode($doc['key_fields'], true);
    }

    // Compute days_until_expiry for hot UI display
    $doc['days_until_expiry'] = null;
    $doc['is_expiring_soon']  = false;
    $doc['is_expired']        = false;
    if (!empty($doc['expiry_date'])) {
        $exp = strtotime($doc['expiry_date']);
        if ($exp) {
            $days = (int)floor(($exp - $today) / 86400);
            $doc['days_until_expiry'] = $days;
            $doc['is_expired']        = $days < 0;
            $doc['is_expiring_soon']  = $days >= 0 && $days <= 30;

            if ($doc['is_expiring_soon'] || $doc['is_expired']) {
                $expiringSoon[] = [
                    'sys_id'       => $doc['sys_id'],
                    'doc_type'     => $doc['doc_type'],
                    'doc_number'   => $doc['doc_number'],
                    'expiry_date'  => $doc['expiry_date'],
                    'days_left'    => $days,
                    'is_expired'   => $doc['is_expired'],
                ];
            }
        }
    }

    // Group
    $groupKey = $doc[$groupBy];
    if (!isset($groups[$groupKey])) $groups[$groupKey] = [];
    $groups[$groupKey][] = $doc;
}
unset($doc);

// Sort expiring_soon by urgency (negative days first = expired)
usort($expiringSoon, fn($a, $b) => $a['days_left'] <=> $b['days_left']);

// ============================================================================
// Response
// ============================================================================
echo json_encode([
    'success'      => true,
    'traveler'     => [
        'sys_id' => $traveler['sys_id'],
        'name'   => $traveler['name'],
    ],
    'total_docs'   => count($docs),
    'group_by'     => $groupBy,
    'groups'       => $groups,
    'expiring_soon'=> $expiringSoon,
]);

// ============================================================================
// Helpers
// ============================================================================

/**
 * SMB file URL — serve.php?doc_id=SYS_ID&file=FILENAME
 * _serveDoc() handler use করে traveler_documents থেকে path resolve করে
 */
function buildFileUrl($docSysId, $filename) {
    if (!$docSysId || !$filename) return null;
    return '/api/file/serve.php?doc_id=' . urlencode($docSysId) . '&file=' . urlencode($filename);
}