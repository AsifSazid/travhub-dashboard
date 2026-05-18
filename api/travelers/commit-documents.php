<?php
/**
 * TravHub Smart Upload v3 — Commit Documents API
 * ==============================================
 * STAGE 2 of upload pipeline. Reads sidecars from tmp/{token}/ and finalizes.
 *
 * Two commit modes:
 *   A) NEW DOCUMENT (no merge_analysis): generate sys_id, INSERT row,
 *      move all JPGs to SMB folder
 *   B) MERGE GROWTH (has merge_analysis): UPDATE existing row, only move
 *      the genuinely new JPGs (numbered continuing from existing page count)
 *
 * For passports:
 *   - Update travelers.passport_info only with user-approved bio fields
 *   - Handle current/previous transitions (demote old current on renewal)
 *
 * Always:
 *   - Set travelers.summary_dirty = 1 + summary_pending_trigger
 *   - Append to travelers.meta_data audit (using existing helper)
 *   - Delete tmp/{token}/ on success
 *
 * Input JSON:
 *   { traveler_sys_id, items: [...] }
 *
 * Each item:
 *   {
 *     token, doc_type, doc_number, suggested_filename_stem, summary,
 *     issue_date, expiry_date, classification_mode,
 *     passport_status: 'current'|'previous',
 *     approve_bio_updates: bool,
 *     approved_bio_fields: [...] | null,
 *     accept_merge: bool       // confirms merge_analysis if present
 *   }
 */

session_start();
require_once '../../server/db_connection.php';
require_once '../../server/uuid_with_system_id_generator.php';
require_once '../../server/generate_meta_data.php';   // EXISTING helper - untouched
require_once '../../server/make-smb-dir.php';
require_once '../../server/make-dir.php';
require_once '../../server/doc-extraction-schemas.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
error_reporting(E_ALL);
ini_set('display_errors', 0);

$TMP_BASE         = '../../tmp/classify/';
$SERVER_CUS_PATH  = trim(@file_get_contents('../../server-name.txt'));

// ============================================================================
// Input
// ============================================================================
$raw   = file_get_contents('php://input');
$input = json_decode($raw, true);
if (!$input || empty($input['items']) || empty($input['traveler_sys_id'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid payload']);
    exit;
}

$travelerSysId = $input['traveler_sys_id'];
$items         = $input['items'];
$actor         = $_SESSION['user_name'] ?? 'system';

// Fetch traveler
$stmt = $pdo->prepare("SELECT id, sys_id, name, smb_path, server_path,
                              passport_info, nid_info, meta_data
                       FROM travelers WHERE sys_id = ? LIMIT 1");
$stmt->execute([$travelerSysId]);
$traveler = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$traveler) {
    echo json_encode(['success' => false, 'message' => 'Traveler not found']);
    exit;
}

// Load doc type registry
$registry = [];
foreach ($pdo->query("SELECT doc_type, smb_folder, has_structured_schema,
                             updates_traveler_column
                      FROM doc_type_registry WHERE is_active=1") as $r) {
    $registry[$r['doc_type']] = $r;
}

// ============================================================================
// Process each item independently (file moves can't roll back)
// ============================================================================
$results = [];
foreach ($items as $item) {
    try {
        $results[] = commitOne($pdo, $TMP_BASE, $traveler, $item, $registry, $actor, $SERVER_CUS_PATH);
        // Refresh traveler after each commit (passport_info may have changed)
        $stmt->execute([$travelerSysId]);
        $traveler = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log('[commit-documents] ' . $e->getMessage());
        $results[] = [
            'token'   => $item['token'] ?? null,
            'success' => false,
            'error'   => $e->getMessage(),
        ];
    }
}

$okCount = count(array_filter($results, fn($r) => !empty($r['success'])));

// Build trigger description for summary regeneration
$triggerParts = array_filter(array_map(function($r) {
    return $r['success'] ? "{$r['doc_type']}" : null;
}, $results));
$pendingTrigger = $okCount > 0
    ? 'Uploaded ' . implode(', ', array_unique($triggerParts))
    : null;

// Mark summary dirty if anything succeeded
if ($okCount > 0) {
    $upd = $pdo->prepare("UPDATE travelers
                          SET summary_dirty = 1, summary_pending_trigger = ?
                          WHERE id = ?");
    $upd->execute([$pendingTrigger, $traveler['id']]);
}

echo json_encode([
    'success'         => $okCount > 0,
    'committed'       => $okCount,
    'total'           => count($items),
    'summary_dirty'   => $okCount > 0,
    'pending_trigger' => $pendingTrigger,
    'results'         => $results,
]);

// ============================================================================
// Per-item commit
// ============================================================================

function commitOne($pdo, $tmpBase, $traveler, $item, $registry, $actor, $SERVER_CUS_PATH) {
    $token = $item['token'] ?? '';
    if (!preg_match('/^[a-f0-9]{32}$/', $token)) throw new Exception('Bad token');

    $workDir = $tmpBase . $token . '/';
    $sidecarPath = $workDir . 'sidecar.json';
    if (!file_exists($sidecarPath)) throw new Exception('Token expired');

    $sidecar = json_decode(file_get_contents($sidecarPath), true);
    if (!$sidecar) throw new Exception('Sidecar corrupted');

    // ---- Apply user overrides ----
    $docType    = $item['doc_type'] ?? $sidecar['classification']['doc_type'];
    $docNumber  = trim($item['doc_number'] ?? ($sidecar['classification']['doc_number'] ?? ''));
    $summary    = $item['summary']     ?? $sidecar['classification']['summary'];
    $issueDate  = $item['issue_date']  ?? ($sidecar['classification']['issue_date']  ?? null);
    $expiryDate = $item['expiry_date'] ?? ($sidecar['classification']['expiry_date'] ?? null);
    $mode       = $item['classification_mode'] ?? 'auto';
    $stem       = trim($item['suggested_filename_stem'] ?? $sidecar['suggested_stem']);

    if (!isset($registry[$docType])) $docType = 'other';
    $smbFolder = $registry[$docType]['smb_folder'];

    // ---- Decide: merge mode or new doc mode? ----
    $mergeMode = !empty($sidecar['merge_analysis']) && !empty($item['accept_merge']);

    if ($mergeMode) {
        return commitAsMerge($pdo, $workDir, $sidecar, $item, $traveler, $docType, $smbFolder, $registry, $actor, $SERVER_CUS_PATH);
    }
    return commitAsNew($pdo, $workDir, $sidecar, $item, $traveler, $docType, $docNumber,
                       $summary, $issueDate, $expiryDate, $mode, $stem, $smbFolder, $registry,
                       $actor, $SERVER_CUS_PATH);
}

// ============================================================================
// MODE A: New document
// ============================================================================

function commitAsNew($pdo, $workDir, $sidecar, $item, $traveler, $docType, $docNumber,
                     $summary, $issueDate, $expiryDate, $mode, $stem, $smbFolder, $registry,
                     $actor, $SERVER_CUS_PATH) {

    [$localTargetDir, $smbTargetDir] = resolveTargetDirs($traveler, $smbFolder, $SERVER_CUS_PATH);

    // Sanitize stem
    $stem = sanitizeStem($stem);
    $stem = uniqueStem($localTargetDir, $stem);

    // Move pages page_001.jpg -> {stem}_p1.jpg
    $pageEntries = [];
    $movedFiles  = [];
    $pageCount   = (int)$sidecar['page_count'];
    $pagesMeta   = $sidecar['classification']['pages'] ?? [];
    $pageHashes  = $sidecar['page_hashes'] ?? [];

    for ($i = 1; $i <= $pageCount; $i++) {
        $srcFile = $workDir . sprintf('page_%03d.jpg', $i);
        if (!file_exists($srcFile)) {
            cleanupMoved($movedFiles);
            throw new Exception("Page {$i} JPG missing");
        }

        $destFilename = "{$stem}_p{$i}.jpg";
        $localDest = $localTargetDir . '/' . $destFilename;
        $smbDest   = $smbTargetDir   . '/' . $destFilename;

        if (!rename($srcFile, $localDest)) {
            cleanupMoved($movedFiles);
            throw new Exception("Move failed for page {$i}");
        }
        $movedFiles[] = $localDest;

        pushToSmb($localDest, $smbDest);

        $pm = $pagesMeta[$i - 1] ?? ['page_no' => $i, 'page_type' => 'content', 'country' => null];
        $pageEntries[] = [
            'page_no'   => $i,
            'filename'  => $destFilename,
            'phash'     => $pageHashes[$i - 1] ?? null,
            'page_type' => $pm['page_type'] ?? 'content',
            'country'   => $pm['country']   ?? null,
            'summary'   => $pm['summary_short'] ?? null,
        ];
    }

    // Delete original source
    foreach (glob($workDir . 'source.*') as $g) @unlink($g);

    // Race-protect dedup
    $dup = $pdo->prepare("SELECT id FROM traveler_documents
                          WHERE traveler_id = ? AND file_hash = ? AND status != 'deleted' LIMIT 1");
    $dup->execute([$traveler['id'], $sidecar['file_hash']]);
    if ($dup->fetch()) {
        cleanupMoved($movedFiles);
        throw new Exception('Duplicate detected at commit');
    }

    // Generate IDs
    $ids = generateIDs('traveler_documents');
    $uuid  = $ids['uuid']   ?? generateUUIDFallback();
    $sysId = $ids['sys_id'] ?? generateSysIdFallback('TD');

    // Doc-data + key_fields
    $docData = !empty($sidecar['classification']['doc_data'])
        ? json_encode($sidecar['classification']['doc_data'], JSON_UNESCAPED_UNICODE)
        : null;
    $keyFields = !empty($sidecar['classification']['key_fields'])
        ? json_encode($sidecar['classification']['key_fields'], JSON_UNESCAPED_UNICODE)
        : null;

    // Hot fields
    $hot = !empty($sidecar['classification']['doc_data'])
        ? extractHotFields($docType, $sidecar['classification']['doc_data'])
        : [];

    // Passport status
    $passportStatus = null;
    if ($docType === 'passport') {
        $analysis = $sidecar['passport_analysis'] ?? null;
        $passportStatus = $item['passport_status'] ?? ($analysis['resolved_status'] ?? 'current');
    }

    // Build meta_data via EXISTING helper (untouched)
    $metaJson = buildMetaData(null, $actor);

    // INSERT
    $stmt = $pdo->prepare("
        INSERT INTO traveler_documents (
            uuid, sys_id, traveler_id, traveler_sys_id,
            doc_type, doc_subtype, doc_number, confidence, classification_mode,
            passport_status,
            original_filename, original_ext, file_hash, file_size, mime_type,
            pages, page_count, smb_folder, smb_path, server_path,
            suggested_name, stored_basename, summary, ocr_text, language,
            doc_data, key_fields,
            country, validity_from, validity_to, linked_passport_number,
            issue_date, expiry_date,
            status, meta_data
        ) VALUES (
            ?, ?, ?, ?,
            ?, ?, ?, ?, ?,
            ?,
            ?, ?, ?, ?, ?,
            ?, ?, ?, ?, ?,
            ?, ?, ?, ?, ?,
            ?, ?,
            ?, ?, ?, ?,
            ?, ?,
            'active', ?
        )
    ");

    $stmt->execute([
        $uuid, $sysId, $traveler['id'], $traveler['sys_id'],
        $docType,
        $sidecar['classification']['doc_subtype'] ?? null,
        $docNumber !== '' ? $docNumber : null,
        $sidecar['classification']['confidence'],
        $mode,
        $passportStatus,
        $sidecar['original_filename'], $sidecar['original_ext'],
        $sidecar['file_hash'], $sidecar['file_size'], $sidecar['mime_type'],
        json_encode($pageEntries, JSON_UNESCAPED_UNICODE),
        count($pageEntries),
        $smbFolder, $smbTargetDir, $localTargetDir,
        $sidecar['suggested_stem'], $stem,
        $summary,
        $sidecar['classification']['ocr_text'] ?? '',
        $sidecar['classification']['language'] ?? 'en',
        $docData, $keyFields,
        $hot['country']                ?? null,
        $hot['validity_from']          ?? null,
        $hot['validity_to']            ?? null,
        $hot['linked_passport_number'] ?? null,
        $issueDate ?: null,
        $expiryDate ?: null,
        $metaJson,
    ]);

    // Update travelers.passport_info (if applicable)
    $passportUpdated = false;
    if ($docType === 'passport') {
        $analysis = $sidecar['passport_analysis'] ?? null;
        $passportUpdated = updateTravelerPassportInfo(
            $pdo, $traveler['id'], $sidecar, $analysis,
            $passportStatus,
            !empty($item['approve_bio_updates']),
            $item['approved_bio_fields'] ?? null,
            $actor
        );
    }

    // Update travelers.nid_info (if NID)
    if ($docType === 'nid') {
        updateTravelerNidInfo($pdo, $traveler['id'], $sidecar, $actor);
    }

    // Append to travelers.meta_data
    $newTravelerMeta = appendUpdateMeta(
        $traveler['meta_data'],
        $actor,
        "Uploaded {$docType}" . ($passportStatus ? " ({$passportStatus})" : '') . ": {$stem}"
    );
    $upd = $pdo->prepare("UPDATE travelers SET meta_data = ? WHERE id = ?");
    $upd->execute([$newTravelerMeta, $traveler['id']]);

    rmdirRecursive($workDir);

    return [
        'token'           => $sidecar['token'],
        'success'         => true,
        'mode'            => 'new',
        'sys_id'          => $sysId,
        'doc_type'        => $docType,
        'stored_basename' => $stem,
        'page_count'      => count($pageEntries),
        'smb_folder'      => $smbFolder,
        'passport_status' => $passportStatus,
        'passport_updated'=> $passportUpdated,
    ];
}

// ============================================================================
// MODE B: Merge growth into existing document
// ============================================================================

function commitAsMerge($pdo, $workDir, $sidecar, $item, $traveler, $docType, $smbFolder, $registry, $actor, $SERVER_CUS_PATH) {
    $merge = $sidecar['merge_analysis'];
    $newIndices = $merge['new_indices'];

    if (empty($newIndices)) {
        rmdirRecursive($workDir);
        throw new Exception('No new pages to merge');
    }

    // Load existing document
    $stmt = $pdo->prepare("SELECT * FROM traveler_documents WHERE id = ? LIMIT 1");
    $stmt->execute([$merge['existing_doc_id']]);
    $existing = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$existing) throw new Exception('Existing doc disappeared');

    $existingPages = json_decode($existing['pages'], true) ?: [];
    $stem          = $existing['stored_basename'];
    [$localTargetDir, $smbTargetDir] = resolveTargetDirs($traveler, $smbFolder, $SERVER_CUS_PATH);

    $pagesMeta  = $sidecar['classification']['pages'] ?? [];
    $pageHashes = $sidecar['page_hashes'] ?? [];

    // Numbering continues from existing page count
    $startNum = (int)$existing['page_count'];
    $appended = [];
    $moved    = [];

    foreach ($newIndices as $offset => $newIdx) {
        $newPageNo = $startNum + $offset + 1;
        $srcFile = $workDir . sprintf('page_%03d.jpg', $newIdx + 1);
        if (!file_exists($srcFile)) {
            cleanupMoved($moved);
            throw new Exception("Source page " . ($newIdx + 1) . " missing");
        }

        $destFilename = "{$stem}_p{$newPageNo}.jpg";
        $localDest = $localTargetDir . '/' . $destFilename;
        $smbDest   = $smbTargetDir   . '/' . $destFilename;

        if (!rename($srcFile, $localDest)) {
            cleanupMoved($moved);
            throw new Exception("Merge move failed");
        }
        $moved[] = $localDest;
        pushToSmb($localDest, $smbDest);

        $pm = $pagesMeta[$newIdx] ?? ['page_no' => $newPageNo, 'page_type' => 'content', 'country' => null];
        $appended[] = [
            'page_no'   => $newPageNo,
            'filename'  => $destFilename,
            'phash'     => $pageHashes[$newIdx] ?? null,
            'page_type' => $pm['page_type'] ?? 'content',
            'country'   => $pm['country']   ?? null,
            'summary'   => $pm['summary_short'] ?? null,
        ];
    }

    // Delete source PDF/image
    foreach (glob($workDir . 'source.*') as $g) @unlink($g);

    // Merge pages JSON
    $allPages = array_merge($existingPages, $appended);

    // Merge doc_data (new values overwrite, but never null-out existing)
    $existingDocData = json_decode($existing['doc_data'] ?? '{}', true) ?: [];
    $newDocData      = $sidecar['classification']['doc_data'] ?? [];
    $mergedDocData   = deepMergeDocData($existingDocData, $newDocData);

    // Update meta_data
    $mergeNote = sprintf("Merged %d new page(s) into existing doc %s",
        count($appended), $existing['sys_id']);
    $newDocMeta = appendUpdateMeta($existing['meta_data'], $actor, $mergeNote);

    // Recalculate hot fields from merged doc_data
    $hot = extractHotFields($docType, $mergedDocData);

    $upd = $pdo->prepare("
        UPDATE traveler_documents
        SET pages = ?, page_count = ?, doc_data = ?,
            country = COALESCE(?, country),
            validity_from = COALESCE(?, validity_from),
            validity_to   = COALESCE(?, validity_to),
            linked_passport_number = COALESCE(?, linked_passport_number),
            meta_data = ?,
            updated_at = NOW()
        WHERE id = ?
    ");
    $upd->execute([
        json_encode($allPages, JSON_UNESCAPED_UNICODE),
        count($allPages),
        json_encode($mergedDocData, JSON_UNESCAPED_UNICODE),
        $hot['country']                ?? null,
        $hot['validity_from']          ?? null,
        $hot['validity_to']            ?? null,
        $hot['linked_passport_number'] ?? null,
        $newDocMeta,
        $existing['id'],
    ]);

    // If passport bio diff approved, update travelers.passport_info
    $passportUpdated = false;
    if ($docType === 'passport') {
        $analysis = $sidecar['passport_analysis'] ?? null;
        $passportStatus = $item['passport_status'] ?? ($analysis['resolved_status'] ?? 'current');
        $passportUpdated = updateTravelerPassportInfo(
            $pdo, $traveler['id'], $sidecar, $analysis,
            $passportStatus,
            !empty($item['approve_bio_updates']),
            $item['approved_bio_fields'] ?? null,
            $actor
        );
    }

    // Audit travelers
    $newTravelerMeta = appendUpdateMeta(
        $traveler['meta_data'], $actor,
        "Added " . count($appended) . " page(s) to {$docType} {$existing['stored_basename']}"
    );
    $upd2 = $pdo->prepare("UPDATE travelers SET meta_data=? WHERE id=?");
    $upd2->execute([$newTravelerMeta, $traveler['id']]);

    rmdirRecursive($workDir);

    return [
        'token'             => $sidecar['token'],
        'success'           => true,
        'mode'              => 'merge',
        'sys_id'            => $existing['sys_id'],
        'doc_type'          => $docType,
        'stored_basename'   => $stem,
        'pages_added'       => count($appended),
        'total_pages'       => count($allPages),
        'smb_folder'        => $smbFolder,
        'passport_updated'  => $passportUpdated,
    ];
}

// ============================================================================
// travelers.passport_info / nid_info updates
// ============================================================================

function updateTravelerPassportInfo($pdo, $travelerId, $sidecar, $analysis, $resolvedStatus, $approveBioUpdates, $approvedFields, $actor) {
    if (!$analysis) return false;
    $newBio = $analysis['new_bio'] ?? null;
    if (!$newBio) return false;

    $stmt = $pdo->prepare("SELECT passport_info FROM travelers WHERE id=? LIMIT 1");
    $stmt->execute([$travelerId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $existing = !empty($row['passport_info']) ? json_decode($row['passport_info'], true) : [];
    if (!is_array($existing)) $existing = [];

    switch ($analysis['scenario']) {
        case 'first_time':
            $existing[] = buildPassportInfoEntry($newBio, $resolvedStatus, $sidecar, $actor);
            break;

        case 'matches_existing_current':
            if ($approveBioUpdates && !empty($analysis['bio_diff'])) {
                foreach ($existing as &$entry) {
                    $en = $entry['bio_info']['passport_number'] ?? null;
                    $nn = $newBio['passport_number'] ?? '';
                    if ($en && $nn && strcasecmp($en, $nn) === 0) {
                        foreach ($analysis['bio_diff'] as $f => $vals) {
                            if ($approvedFields === null || in_array($f, $approvedFields, true)) {
                                $entry['bio_info'][$f] = $vals['new'];
                            }
                        }
                        $entry['_metadata']['last_updated_at'] = date('d-m-Y H:i:s');
                        $entry['_metadata']['last_updated_by'] = $actor;
                        break;
                    }
                }
                unset($entry);
            }
            break;

        case 'renewal_demote_old':
            foreach ($existing as &$entry) {
                $st = $entry['_metadata']['passport_status'] ?? 'current';
                if ($st === 'current') {
                    $entry['_metadata']['passport_status'] = 'previous';
                    $entry['_metadata']['demoted_at']      = date('d-m-Y H:i:s');
                    $entry['_metadata']['demoted_by']      = $actor;
                }
            }
            unset($entry);
            $existing[] = buildPassportInfoEntry($newBio, 'current', $sidecar, $actor);

            $upd = $pdo->prepare("UPDATE traveler_documents
                                  SET passport_status='previous'
                                  WHERE traveler_id=? AND doc_type='passport'
                                    AND passport_status='current'");
            $upd->execute([$travelerId]);
            break;

        case 'historical_upload':
            $existing[] = buildPassportInfoEntry($newBio, 'previous', $sidecar, $actor);
            break;
    }

    $upd = $pdo->prepare("UPDATE travelers
                          SET passport_info=?,
                              passport_no = COALESCE(NULLIF(?, ''), passport_no)
                          WHERE id=?");
    $upd->execute([
        json_encode($existing, JSON_UNESCAPED_UNICODE),
        $resolvedStatus === 'current' ? ($newBio['passport_number'] ?? '') : '',
        $travelerId,
    ]);
    return true;
}

function buildPassportInfoEntry($bio, $status, $sidecar, $actor) {
    return [
        'page_type' => 'bio_page',
        'bio_info'  => $bio,
        '_metadata' => [
            'saved_at'        => date('d-m-Y H:i:s'),
            'passport_status' => $status,
            'source_doc_hash' => $sidecar['file_hash'],
            'source_filename' => $sidecar['original_filename'],
            'page_count'      => $sidecar['page_count'],
            'created_by'      => $actor,
        ],
    ];
}

function updateTravelerNidInfo($pdo, $travelerId, $sidecar, $actor) {
    $newNid = $sidecar['classification']['doc_data']['nid_info'] ?? null;
    if (!$newNid) return;

    $stmt = $pdo->prepare("SELECT nid_info FROM travelers WHERE id=? LIMIT 1");
    $stmt->execute([$travelerId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $existing = !empty($row['nid_info']) ? json_decode($row['nid_info'], true) : [];
    if (!is_array($existing)) $existing = [];

    $entry = [
        'nid_info'  => $newNid,
        '_metadata' => [
            'saved_at'        => date('d-m-Y H:i:s'),
            'source_doc_hash' => $sidecar['file_hash'],
            'created_by'      => $actor,
        ],
    ];
    $existing[] = $entry;

    $upd = $pdo->prepare("UPDATE travelers
                          SET nid_info=?,
                              nid_no = COALESCE(NULLIF(?, ''), nid_no)
                          WHERE id=?");
    $upd->execute([
        json_encode($existing, JSON_UNESCAPED_UNICODE),
        $newNid['nid_number'] ?? '',
        $travelerId,
    ]);
}

// ============================================================================
// Helpers
// ============================================================================

function resolveTargetDirs($traveler, $smbFolder, $SERVER_CUS_PATH) {
    $localRoot = rtrim($traveler['server_path'] ?: '../../travelers/' . $traveler['sys_id'], '/');
    $smbRoot   = rtrim($traveler['smb_path']    ?: "{$SERVER_CUS_PATH}_travelers/" . $traveler['sys_id'], '/');
    $localDir  = $localRoot . '/' . $smbFolder;
    $smbDir    = $smbRoot   . '/' . $smbFolder;
    if (!is_dir($localDir)) mkdir($localDir, 0777, true);
    @makeSMBDir($smbRoot, $smbFolder);
    return [$localDir, $smbDir];
}

function pushToSmb($localPath, $smbPath) {
    if (class_exists('OMV_SMB_Manager')) {
        try {
            $omv = new OMV_SMB_Manager();
            $omv->paste_file($localPath, $smbPath);
        } catch (Exception $e) {
            error_log("[smb push] " . $e->getMessage());
        }
    } else {
        @copy($localPath, $smbPath);
    }
}

function sanitizeStem($stem) {
    $stem = preg_replace('/[^A-Za-z0-9._\-]/', '_', $stem);
    $stem = preg_replace('/_+/', '_', $stem);
    return $stem !== '' ? $stem : 'document_' . date('Ymd-His');
}

function uniqueStem($dir, $stem) {
    if (!file_exists($dir . '/' . $stem . '_p1.jpg')) return $stem;
    $i = 2;
    while (file_exists($dir . '/' . $stem . '_v' . $i . '_p1.jpg')) $i++;
    return $stem . '_v' . $i;
}

function deepMergeDocData($old, $new) {
    if (!is_array($old)) return $new;
    if (!is_array($new)) return $old;
    foreach ($new as $k => $v) {
        if (is_array($v) && isset($old[$k]) && is_array($old[$k])) {
            $old[$k] = deepMergeDocData($old[$k], $v);
        } elseif ($v !== null && $v !== '') {
            $old[$k] = $v;
        }
    }
    return $old;
}

function cleanupMoved($files) {
    foreach ($files as $f) @unlink($f);
}

function rmdirRecursive($dir) {
    if (!is_dir($dir)) return;
    foreach (scandir($dir) as $f) {
        if ($f === '.' || $f === '..') continue;
        $p = $dir . '/' . $f;
        is_dir($p) ? rmdirRecursive($p) : @unlink($p);
    }
    @rmdir($dir);
}

function generateUUIDFallback() {
    $data = random_bytes(16);
    $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
    $data[8] = chr(ord($data[8]) & 0x3f | 0x80);
    return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
}

function generateSysIdFallback($prefix) {
    return $prefix . '-' . strtoupper(substr(bin2hex(random_bytes(4)), 0, 6));
}