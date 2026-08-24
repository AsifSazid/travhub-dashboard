<?php
/**
 * FILE PATH: api/travelers/commit-documents.php
 *
 * Phase 3 — Step 3: User confirmed → save to DB + move files to NAS
 *
 * smart-upload.php থেকে JSON body আসে:
 *   traveler_sys_id  — travelers.sys_id
 *   items[]          — প্রতিটা confirmed document এর data
 *     token                   — classify_tokens.token
 *     doc_type                — user confirmed/changed value
 *     doc_number              — user confirmed/changed
 *     suggested_filename_stem — user confirmed/changed
 *     summary                 — user confirmed/changed
 *     issue_date              — YYYY-MM-DD or null
 *     expiry_date             — YYYY-MM-DD or null
 *     classification_mode     — auto | manual | overridden
 *     passport_status         — current | previous | historical | null
 *     approve_bio_updates     — bool (passport renewal এ bio fields update করবে?)
 *     approved_bio_fields     — string[] (কোন fields update হবে)
 *     accept_merge            — bool (duplicate doc এ নতুন pages merge করবে?)
 *
 * OUTPUT (JSON):
 *   success, committed, total, results[], summary_dirty, pending_trigger
 */

require_once '../../server/api_bootstrap.php';
require_once '../../server/db_connection.php';
require_once '../../server/sys_id_generator_v2.php';
require_once '../../server/live_storage.php';  // OMV_SMB_Manager
require_once '../../server/make-smb-dir.php';  // makeSMBDir

ini_set('display_errors', 0);

ini_set('memory_limit', '512M');
set_time_limit(180);

// ── Input ────────────────────────────────────────────────────────────────────
$raw  = file_get_contents('php://input');
$body = json_decode($raw, true);

$travelerSysId = trim($body['traveler_sys_id'] ?? '');
$items         = $body['items'] ?? [];

if (!$travelerSysId || empty($items)) {
    jsonOut(['success' => false, 'message' => 'traveler_sys_id and items are required']);
}

// Traveler fetch
$stmt = $pdo->prepare("
    SELECT sys_id, name, passport_no, passport_info, nid_no, nid_info,
           server_path, smb_path
    FROM travelers WHERE sys_id = ? LIMIT 1
");
$stmt->execute([$travelerSysId]);
$traveler = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$traveler) {
    jsonOut(['success' => false, 'message' => 'Traveler not found']);
}

// doc_type_registry — smb_folder + updates_traveler_column fetch করো
$regStmt = $pdo->query("SELECT doc_type, smb_folder, updates_traveler_column, tracks_expiry, tracks_validity FROM doc_type_registry WHERE is_active = 1");
$registry = [];
foreach ($regStmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
    $registry[$row['doc_type']] = $row;
}

// ── Process each item ────────────────────────────────────────────────────────
$results      = [];
$committed    = 0;
$summaryDirty = false;

foreach ($items as $item) {
    $result = processItem($pdo, $traveler, $registry, $item);
    $results[] = $result;
    if ($result['success']) {
        $committed++;
        $summaryDirty = true;
    }
}

// ── Update traveler passport_no / nid_no quick-lookup columns ───────────────
// (commit এর পরে যদি passport বা nid save হয়ে থাকে)
syncTravelerDocNumbers($pdo, $travelerSysId);

jsonOut([
    'success'         => $committed > 0,
    'committed'       => $committed,
    'total'           => count($items),
    'results'         => $results,
    'summary_dirty'   => $summaryDirty,
    'pending_trigger' => $summaryDirty
        ? "{$committed} document" . ($committed > 1 ? 's' : '') . " uploaded"
        : null,
]);


// ════════════════════════════════════════════════════════════════════════════
// processItem — একটা item process করো
// ════════════════════════════════════════════════════════════════════════════
function processItem(PDO $pdo, array $traveler, array $registry, array $item): array
{
    $token = trim($item['token'] ?? '');
    if (!$token) {
        return ['success' => false, 'token' => '', 'message' => 'Missing token'];
    }

    // classify_tokens থেকে data নাও
    $stmt = $pdo->prepare("
        SELECT * FROM classify_tokens
        WHERE token = ? AND traveler_id = ? AND expires_at > NOW()
        LIMIT 1
    ");
    $stmt->execute([$token, $traveler['sys_id']]);
    $ct = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$ct) {
        return ['success' => false, 'token' => $token, 'message' => 'Token not found or expired'];
    }

    $classifyResult = json_decode($ct['classify_result'] ?? '{}', true) ?: [];
    $passportAnalysis = $ct['passport_analysis'] ? json_decode($ct['passport_analysis'], true) : null;

    // User confirmed values (override Gemini values)
    $docType          = trim($item['doc_type']   ?? $classifyResult['doc_type'] ?? 'other');
    $docNumber        = trim($item['doc_number'] ?? $classifyResult['doc_number'] ?? '');
    $filenameStem     = sanitizeStem($item['suggested_filename_stem'] ?? $classifyResult['suggested_filename_stem'] ?? '');
    $summary          = trim($item['summary']    ?? $classifyResult['summary'] ?? '');
    $issueDate        = sanitizeDate($item['issue_date']  ?? $classifyResult['issue_date']  ?? '');
    $expiryDate       = sanitizeDate($item['expiry_date'] ?? $classifyResult['expiry_date'] ?? '');
    $classMode        = $item['classification_mode'] ?? 'auto';
    $passportStatus   = $item['passport_status']   ?? null;
    $approveBioUpdate = !empty($item['approve_bio_updates']);
    $approvedBioFields= $item['approved_bio_fields'] ?? [];
    $acceptMerge      = !empty($item['accept_merge']);

    $confidence  = (float)($classifyResult['confidence'] ?? 0.5);
    $needsReview = !empty($classifyResult['needs_review']);
    $language    = $classifyResult['language']  ?? '';
    $pageCount   = (int)($ct['page_count']      ?? 1);
    $pages       = $classifyResult['pages']     ?? [];
    $docData     = $classifyResult['doc_data']  ?? [];

    // Registry থেকে smb_folder নাও
    $reg       = $registry[$docType] ?? $registry['other'];
    $smbFolder = $reg['smb_folder'] ?? 'all_documents';

    // Filename stem fallback
    if (!$filenameStem) {
        $filenameStem = sanitizeStem(
            strtolower($traveler['name']) . '_' . $docType .
            ($docNumber ? '_' . $docNumber : '')
        );
    }

    // Passport-এর filename সবসময় systematic — Gemini-suggested নাম উপেক্ষা করে
    // 'current_passport_bio_page' ব্যবহার হবে (Traveler Create flow-এর সাথে সামঞ্জস্যপূর্ণ)।
    // renewal হলে demoteOldPassport() পরে পুরনোটাকে 'previous_passport_bio_page_p{n}' এ
    // rename করে দেবে, তাই এখানে সবসময় 'current' ধরেই এগোনো নিরাপদ।
    if ($docType === 'passport') {
        $filenameStem = 'current_passport_bio_page';
    }

    // ── File: tmp → NAS ──────────────────────────────────────────────────────
    $tmpPath = $ct['tmp_path'];
    if (!file_exists($tmpPath)) {
        return ['success' => false, 'token' => $token, 'message' => 'Staged file not found (expired?)'];
    }

    $ext         = strtolower(pathinfo($ct['original_filename'] ?? '', PATHINFO_EXTENSION)) ?: 'jpg';
    $storedPages = storeFilesToNas($tmpPath, $ext, $traveler, $smbFolder, $filenameStem, $pages);

    if (empty($storedPages)) {
        return ['success' => false, 'token' => $token, 'message' => 'Failed to store files to NAS'];
    }

    // ── Duplicate / merge check ──────────────────────────────────────────────
    $existingDocSysId = null;
    if ($docNumber && $acceptMerge) {
        $dupStmt = $pdo->prepare("
            SELECT sys_id, page_count, pages FROM traveler_documents
            WHERE traveler_id = ? AND doc_type = ? AND doc_number = ? AND status != 'deleted'
            LIMIT 1
        ");
        $dupStmt->execute([$traveler['sys_id'], $docType, $docNumber]);
        $existing = $dupStmt->fetch(PDO::FETCH_ASSOC);

        if ($existing) {
            // Existing document এ নতুন pages merge করো
            $existingDocSysId = $existing['sys_id'];
            mergeIntoExisting($pdo, $existing, $storedPages, $pageCount, $summary, $expiryDate, $issueDate);
            deleteToken($pdo, $token, $tmpPath);
            return [
                'success'          => true,
                'token'            => $token,
                'action'           => 'merged',
                'merged_into'      => $existingDocSysId,
                'pages_added'      => count($storedPages),
            ];
        }
    }

    // ── traveler_documents INSERT ────────────────────────────────────────────
    $v2    = generateV2IDs($pdo, 'traveler_documents');
    $uuid  = $v2['uuid'];
    $sysId = $v2['sys_id'];
    

    $serverPath = $traveler['server_path']
        ? rtrim($traveler['server_path'], '/') . '/' . $smbFolder
        : null;

    $now = date('Y-m-d H:i:s');
    $metaData = json_encode([
        'created_by_date' => ['by' => $_SESSION['employee_id'] ?? 'system', 'date' => $now],
        'updated_by_date' => [],
    ]);

    $stmt = $pdo->prepare("
        INSERT INTO traveler_documents (
            uuid, sys_id, traveler_id, batch_id,
            doc_type, doc_number, language,
            passport_status, issue_date, expiry_date,
            summary, doc_data, key_fields,
            confidence, needs_review, classification_mode,
            original_filename, suggested_filename_stem,
            smb_folder, server_path, file_size, mime_type,
            page_count, pages,
            is_primary, status, meta_data
        ) VALUES (
            ?, ?, ?, ?,
            ?, ?, ?,
            ?, ?, ?,
            ?, ?, ?,
            ?, ?, ?,
            ?, ?,
            ?, ?, ?, ?,
            ?, ?,
            ?, 'active', ?
        )
    ");

    $stmt->execute([
        $uuid, $sysId, $traveler['sys_id'], null,
        $docType, $docNumber ?: null, $language ?: null,
        $passportStatus ?: null, $issueDate ?: null, $expiryDate ?: null,
        $summary ?: null,
        $docData ? json_encode($docData, JSON_UNESCAPED_UNICODE) : null,
        null, // key_fields — future use
        $confidence, $needsReview ? 1 : 0, $classMode,
        $ct['original_filename'], $filenameStem,
        $smbFolder, $serverPath, $ct['file_size'], $ct['mime_type'],
        $pageCount,
        json_encode($storedPages, JSON_UNESCAPED_UNICODE),
        0, // is_primary — set below if needed
        $metaData,
    ]);

    // ── travelers table mirror update ────────────────────────────────────────
    // doc_type_registry.updates_traveler_column অনুযায়ী
    $travelerCol = $reg['updates_traveler_column'] ?? null;
    if ($travelerCol === 'passport_info' || $travelerCol === 'nid_info') {
        // Passport/NID — complex current/previous demote logic
        updateTravelerColumn($pdo, $traveler, $travelerCol, $docType, $docData, $docNumber,
                             $passportStatus, $approveBioUpdate, $approvedBioFields);
    } elseif ($travelerCol) {
        // employment_info, educational_info ইত্যাদি — সহজ append (multi-entry history)
        appendTravelerInfoEntry($pdo, $traveler, $travelerCol, $docType, $docData, $docNumber, $issueDate, $expiryDate);
    }

    // ── Passport renewal: পুরানো passport demote করো ────────────────────────
    if ($docType === 'passport' && $passportStatus === 'current' && $passportAnalysis) {
        $scenario = $passportAnalysis['scenario'] ?? '';
        if ($scenario === 'renewal_demote_old') {
            demoteOldPassport($pdo, $traveler, $sysId);
        }
    }

    // ── Token cleanup ────────────────────────────────────────────────────────
    deleteToken($pdo, $token, $tmpPath);

    return [
        'success'  => true,
        'token'    => $token,
        'action'   => 'created',
        'sys_id'   => $sysId,
        'doc_type' => $docType,
        'pages'    => count($storedPages),
    ];
}


// ════════════════════════════════════════════════════════════════════════════
// storeFilesToNas — tmp file → সরাসরি SMB (local এ কোনো permanent copy নেই)
// PDF হলে Imagick দিয়ে page by page PNG বানিয়ে প্রতিটা SMB তে upload
// Image হলে সরাসরি SMB তে upload
// Returns: stored pages [{page_no, filename, page_type, country}]
// ════════════════════════════════════════════════════════════════════════════
function storeFilesToNas(
    string $tmpPath,
    string $ext,
    array  $traveler,
    string $smbFolder,
    string $filenameStem,
    array  $geminiPages
): array {
    // SMB path: {SERVER_CUS_PATH}_travelers/{sysId}_{Name}/{smb_folder}/
    $SERVER_CUS_PATH = trim(@file_get_contents(__DIR__ . '/../../server-name.txt') ?? 'dev');
    $cleanSysId      = preg_replace('/\s+/u', '', $traveler['sys_id']);
    $cleanName       = preg_replace('/\s+/u', '', $traveler['name']);
    $smbBase         = "{$SERVER_CUS_PATH}_travelers/{$cleanSysId}_{$cleanName}/{$smbFolder}";

    // SMB folder exist না করলে বানাও
    if (class_exists('OMV_SMB_Manager')) {
        try {
            $omv = new OMV_SMB_Manager();
            $omv->create_folder($smbBase);
        } catch (Throwable $e) {
            error_log("[commit-documents] SMB mkdir failed: {$smbBase}");
        }
    }

    $stored = [];

    if ($ext === 'pdf') {
        // PDF → page by page PNG via Imagick → প্রতিটা tmp তে save → SMB তে upload
        if (!extension_loaded('imagick')) {
            error_log("[commit-documents] Imagick not available");
            return [];
        }
        try {
            $imagick = new Imagick();
            $imagick->setResolution(200, 200);
            $imagick->readImage($tmpPath);
            $totalPages = $imagick->getNumberImages();

            for ($i = 0; $i < $totalPages; $i++) {
                $imagick->setIteratorIndex($i);
                $imagick->setImageFormat('png');

                $pageNo    = $i + 1;
                $filename  = "{$filenameStem}_p{$pageNo}.png";
                $smbPath   = "{$smbBase}/{$filename}";

                // Page টা tmp তে save করো
                $pageTmp = sys_get_temp_dir() . '/th_page_' . uniqid() . '.png';
                file_put_contents($pageTmp, $imagick->getImageBlob());

                // SMB তে upload
                $uploaded = _uploadToSmb($pageTmp, $smbPath);
                @unlink($pageTmp); // tmp page delete

                if ($uploaded) {
                    $gemPage  = $geminiPages[$i] ?? [];
                    $stored[] = [
                        'page_no'   => $pageNo,
                        'filename'  => $filename,
                        'page_type' => $gemPage['page_type'] ?? 'unknown',
                        'country'   => $gemPage['country']   ?? null,
                    ];
                }
            }
            $imagick->clear();
            $imagick->destroy();
        } catch (Throwable $e) {
            error_log("[commit-documents] Imagick error: " . $e->getMessage());
            @unlink($tmpPath);
            return [];
        }
    } else {
        // Image — সরাসরি SMB তে upload
        $filename = "{$filenameStem}_p1.{$ext}";
        $smbPath  = "{$smbBase}/{$filename}";

        $uploaded = _uploadToSmb($tmpPath, $smbPath);

        if ($uploaded) {
            $gemPage  = $geminiPages[0] ?? [];
            $stored[] = [
                'page_no'   => 1,
                'filename'  => $filename,
                'page_type' => $gemPage['page_type'] ?? 'unknown',
                'country'   => $gemPage['country']   ?? null,
            ];
        }
    }

    // Tmp file delete — শুধু কিছু store হলে
    if (!empty($stored)) {
        @unlink($tmpPath);
    }

    return $stored;
}

// ── SMB upload helper ─────────────────────────────────────────────────────────
function _uploadToSmb(string $localPath, string $smbPath): bool
{
    if (!class_exists('OMV_SMB_Manager')) {
        error_log("[commit-documents] OMV_SMB_Manager not available");
        return false;
    }
    try {
        $omv    = new OMV_SMB_Manager();
        $result = $omv->paste_file($localPath, $smbPath);
        if ($result === true) {
            error_log("[commit-documents] SMB upload OK: {$smbPath}");
            return true;
        }
        error_log("[commit-documents] SMB upload failed: {$smbPath} — {$result}");
        return false;
    } catch (Throwable $e) {
        error_log("[commit-documents] SMB exception: " . $e->getMessage());
        return false;
    }
}

// ── Local path helper (আর লাগে না, remove করা হয়েছে) ──────────────────────────

// ════════════════════════════════════════════════════════════════════════════
// updateTravelerColumn — doc_type_registry.updates_traveler_column অনুযায়ী
// travelers table এর passport_info বা nid_info update করো
// ════════════════════════════════════════════════════════════════════════════
function updateTravelerColumn(
    PDO    $pdo,
    array  $traveler,
    string $column,        // 'passport_info' or 'nid_info'
    string $docType,
    array  $docData,
    string $docNumber,
    ?string $passportStatus,
    bool   $approveBioUpdate,
    array  $approvedBioFields
): void {
    if (!$docData) return;

    $existing = json_decode($traveler[$column] ?? '[]', true) ?: [];

    // New entry তৈরি করো
    $newEntry = [
        'page_type'  => $docType === 'passport' ? ($passportStatus ?? 'current') : 'primary',
        'doc_number' => $docNumber,
        'bio_info'   => $docData,
        '_metadata'  => [
            'uploaded_at' => date('Y-m-d H:i:s'),
            'source'      => 'smart_upload',
        ],
    ];

    if ($docType === 'passport') {
        if ($passportStatus === 'current') {
            // আগের current গুলো previous এ নামাও
            foreach ($existing as &$e) {
                if (($e['page_type'] ?? '') === 'current') {
                    $e['page_type'] = 'previous';
                }
            }
            unset($e);
            array_unshift($existing, $newEntry); // নতুনটা সামনে

            // Bio fields update (approve হলে)
            if ($approveBioUpdate && !empty($approvedBioFields)) {
                $bioUpdate = [];
                foreach ($approvedBioFields as $field) {
                    if (isset($docData[$field])) {
                        $bioUpdate[$field] = $docData[$field];
                    }
                }
                if ($bioUpdate) {
                    // travelers table এর basic columns update
                    // name, date_of_birth etc. — only if explicitly approved
                    $allowed = ['full_name' => 'name', 'date_of_birth' => 'date_of_birth'];
                    $sets = []; $vals = [];
                    foreach ($allowed as $bioField => $col) {
                        if (in_array($bioField, $approvedBioFields) && isset($docData[$bioField])) {
                            $sets[] = "`{$col}` = ?";
                            $vals[] = $docData[$bioField];
                        }
                    }
                    if ($sets) {
                        $vals[] = $traveler['sys_id'];
                        $pdo->prepare("UPDATE travelers SET " . implode(', ', $sets) . " WHERE sys_id = ?")
                            ->execute($vals);
                    }
                }
            }
        } else {
            // previous / historical — শেষে যোগ করো
            $existing[] = $newEntry;
        }
    } else {
        // NID — replace করো
        $existing = [$newEntry];
    }

    $pdo->prepare("UPDATE travelers SET `{$column}` = ? WHERE sys_id = ?")
        ->execute([json_encode($existing, JSON_UNESCAPED_UNICODE), $traveler['sys_id']]);
}

// ════════════════════════════════════════════════════════════════════════════
// appendTravelerInfoEntry — employment_letter, education_certificate ইত্যাদি
// non-passport/nid document দিয়ে travelers.{column} আপডেট করা।
//
// get_info.php-এর ইতিমধ্যে-থাকা fallback logic (যেটা traveler_documents থেকে
// সরাসরি পড়ে employment_info/educational_info বানায়) যেই exact structure
// আশা করে, এখানেও ঠিক সেই structure ব্যবহার করা হচ্ছে — যাতে tp-combine-form.php
// (Information tab-এর form) দুই পথ থেকেই একই shape পায়, ভেঙে না যায়।
// ════════════════════════════════════════════════════════════════════════════
function appendTravelerInfoEntry(
    PDO    $pdo,
    array  $traveler,
    string $column,       // 'employment_info' | 'educational_info'
    string $docType,
    array  $docData,
    string $docNumber,
    string $issueDate,
    string $expiryDate
): void {
    if (!$docData) return; // Gemini structured data না পেলে update করার কিছু নেই

    if ($column === 'employment_info') {
        // get_info.php fallback structure: single object, {employmentStatus, jobTitle, employer, issue_date}
        $newValue = array_filter([
            'employmentStatus' => 'employed',
            'jobTitle'         => $docData['designation']   ?? '',
            'employer'         => $docData['employer_name'] ?? '',
            'issue_date'       => $issueDate ?: ($docData['issue_date'] ?? ''),
        ]);
        if (!$newValue) return;

        $pdo->prepare("UPDATE travelers SET `employment_info` = ? WHERE sys_id = ?")
            ->execute([json_encode($newValue, JSON_UNESCAPED_UNICODE), $traveler['sys_id']]);

    } elseif ($column === 'educational_info') {
        // get_info.php fallback structure: array of {name, course, attendanceFrom, attendanceTo}
        $newEntry = array_filter([
            'name'           => $docData['institution_name'] ?? '',
            'course'         => $docData['course']            ?? '',
            'attendanceFrom' => $docData['from_date']         ?? '',
            'attendanceTo'   => $docData['to_date']           ?? '',
        ]);
        if (!$newEntry) return;

        $existing = json_decode($traveler[$column] ?? '[]', true) ?: [];
        $existing[] = $newEntry;

        $pdo->prepare("UPDATE travelers SET `educational_info` = ? WHERE sys_id = ?")
            ->execute([json_encode($existing, JSON_UNESCAPED_UNICODE), $traveler['sys_id']]);
    }
}


// ════════════════════════════════════════════════════════════════════════════
// demoteOldPassport — renewal এ পুরানো current passport কে previous করো
// traveler_documents table এ + SMB তে actual ফাইলও rename করো
// (current_passport_bio_page_p{n}.ext → previous_passport_bio_page_p{n}.ext)
// ════════════════════════════════════════════════════════════════════════════
function demoteOldPassport(PDO $pdo, array $traveler, string $newDocSysId): void
{
    $travelerSysId = $traveler['sys_id'];

    // ইতিমধ্যে কতগুলো 'previous' passport আছে গুনে নাও — নতুন demote হওয়া
    // পাসপোর্টের rename-index (n) ঠিক করার জন্য
    $countStmt = $pdo->prepare("
        SELECT COUNT(*) FROM traveler_documents
        WHERE traveler_id = ? AND doc_type = 'passport' AND passport_status = 'previous'
    ");
    $countStmt->execute([$travelerSysId]);
    $previousIndex = (int)$countStmt->fetchColumn() + 1;

    // পুরানো current passport row(গুলো) বের করো — filename rename করার জন্য
    $oldStmt = $pdo->prepare("
        SELECT sys_id, smb_folder, pages FROM traveler_documents
        WHERE traveler_id = ? AND doc_type = 'passport'
          AND passport_status = 'current' AND sys_id != ?
    ");
    $oldStmt->execute([$travelerSysId, $newDocSysId]);
    $oldDocs = $oldStmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($oldDocs as $oldDoc) {
        $updatedPages = renamePassportFilesOnSmb(
            $traveler, $oldDoc['smb_folder'], $oldDoc['pages'], $previousIndex
        );

        // SMB rename সফল হলে নতুন filenames DB তে sync করো, যাতে Documents tab
        // ও actual SMB ফাইল mismatch না হয়
        if ($updatedPages !== null) {
            $pdo->prepare("
                UPDATE traveler_documents
                SET passport_status = 'previous', is_primary = 0,
                    suggested_filename_stem = ?, pages = ?
                WHERE sys_id = ?
            ")->execute([
                "previous_passport_bio_page",
                json_encode($updatedPages, JSON_UNESCAPED_UNICODE),
                $oldDoc['sys_id'],
            ]);
        } else {
            // SMB rename fail করলে DB তে পুরনো filename রেখেই status বদলাও —
            // নাহলে DB বলবে 'previous' কিন্তু SMB তে এখনো 'current_...' নামে
            // ফাইল থাকবে, যেটা আরও বড় mismatch তৈরি করবে
            error_log("[demoteOldPassport] SMB rename failed for {$oldDoc['sys_id']}, keeping old filename in DB");
            $pdo->prepare("
                UPDATE traveler_documents
                SET passport_status = 'previous', is_primary = 0
                WHERE sys_id = ?
            ")->execute([$oldDoc['sys_id']]);
        }
    }

    // নতুনটা primary করো
    $pdo->prepare("
        UPDATE traveler_documents SET is_primary = 1, passport_status = 'current'
        WHERE sys_id = ?
    ")->execute([$newDocSysId]);
}

// ════════════════════════════════════════════════════════════════════════════
// renamePassportFilesOnSmb — SMB তে actual passport file(গুলো) rename করে
// current_passport_bio_page_p{n}.ext → previous_passport_bio_page_p{n}.ext
// সব page সফলভাবে rename হলে updated pages array রিটার্ন করে, নাহলে null
// (partial rename এড়াতে — হয় সব নয়তো কিছুই না, DB এর সাথে mismatch এড়াতে)
// ════════════════════════════════════════════════════════════════════════════
function renamePassportFilesOnSmb(array $traveler, string $smbFolder, ?string $pagesJson, int $previousIndex): ?array
{
    if (!class_exists('OMV_SMB_Manager')) return null;

    $pages = json_decode($pagesJson ?? '[]', true) ?: [];
    if (!$pages) return null;

    $SERVER_CUS_PATH = trim(@file_get_contents(__DIR__ . '/../../server-name.txt') ?? 'dev');
    $cleanSysId = preg_replace('/\s+/u', '', $traveler['sys_id']);
    $cleanName  = preg_replace('/\s+/u', '', $traveler['name']);
    $smbBase    = "{$SERVER_CUS_PATH}_travelers/{$cleanSysId}_{$cleanName}/{$smbFolder}";

    try {
        $omv = new OMV_SMB_Manager();
        $updatedPages = [];

        foreach ($pages as $page) {
            $oldFilename = $page['filename'] ?? '';
            if (!$oldFilename) return null;

            $ext = strtolower(pathinfo($oldFilename, PATHINFO_EXTENSION)) ?: 'jpg';
            $pageNo = $page['page_no'] ?? 1;
            $newFilename = "previous_passport_bio_page_p{$previousIndex}" .
                           (count($pages) > 1 ? "_page{$pageNo}" : '') . ".{$ext}";

            $oldPath = "{$smbBase}/{$oldFilename}";
            $newPath = "{$smbBase}/{$newFilename}";

            $renamed = $omv->rename_item($oldPath, $newPath);
            if (!$renamed) return null; // fail হলে পুরো batch বাতিল — partial rename এড়াতে

            $page['filename'] = $newFilename;
            $updatedPages[] = $page;
        }

        return $updatedPages;
    } catch (Throwable $e) {
        error_log("[renamePassportFilesOnSmb] Error: " . $e->getMessage());
        return null;
    }
}


// ════════════════════════════════════════════════════════════════════════════
// mergeIntoExisting — duplicate doc এ নতুন pages merge করো
// ════════════════════════════════════════════════════════════════════════════
function mergeIntoExisting(
    PDO    $pdo,
    array  $existing,
    array  $newPages,
    int    $newPageCount,
    string $newSummary,
    string $newExpiry,
    string $newIssue
): void {
    $existingPages = json_decode($existing['pages'] ?? '[]', true) ?: [];

    // Page number offset করো
    $maxPageNo = !empty($existingPages)
        ? max(array_column($existingPages, 'page_no'))
        : 0;

    foreach ($newPages as &$p) {
        $p['page_no'] += $maxPageNo;
    }
    unset($p);

    $mergedPages = array_merge($existingPages, $newPages);
    $totalPages  = $maxPageNo + $newPageCount;

    $sets  = ["pages = ?", "page_count = ?", "updated_at = NOW()"];
    $vals  = [json_encode($mergedPages, JSON_UNESCAPED_UNICODE), $totalPages];

    if ($newSummary) { $sets[] = "summary = ?"; $vals[] = $newSummary; }
    if ($newExpiry)  { $sets[] = "expiry_date = ?"; $vals[] = $newExpiry; }
    if ($newIssue)   { $sets[] = "issue_date = ?";  $vals[] = $newIssue; }

    $vals[] = $existing['sys_id'];
    $pdo->prepare("UPDATE traveler_documents SET " . implode(', ', $sets) . " WHERE sys_id = ?")
        ->execute($vals);
}


// ════════════════════════════════════════════════════════════════════════════
// syncTravelerDocNumbers — commit শেষে travelers.passport_no / nid_no sync
// ════════════════════════════════════════════════════════════════════════════
function syncTravelerDocNumbers(PDO $pdo, string $travelerSysId): void
{
    // Current passport number
    $stmt = $pdo->prepare("
        SELECT doc_number FROM traveler_documents
        WHERE traveler_id = ? AND doc_type = 'passport'
          AND passport_status = 'current' AND status = 'active'
        ORDER BY created_at DESC LIMIT 1
    ");
    $stmt->execute([$travelerSysId]);
    $passportNo = $stmt->fetchColumn();

    // NID number
    $stmt = $pdo->prepare("
        SELECT doc_number FROM traveler_documents
        WHERE traveler_id = ? AND doc_type = 'nid' AND status = 'active'
        ORDER BY created_at DESC LIMIT 1
    ");
    $stmt->execute([$travelerSysId]);
    $nidNo = $stmt->fetchColumn();

    $sets = []; $vals = [];
    if ($passportNo) { $sets[] = "passport_no = ?"; $vals[] = $passportNo; }
    if ($nidNo)      { $sets[] = "nid_no = ?";      $vals[] = $nidNo; }

    if ($sets) {
        $vals[] = $travelerSysId;
        $pdo->prepare("UPDATE travelers SET " . implode(', ', $sets) . " WHERE sys_id = ?")
            ->execute($vals);
    }
}


// ════════════════════════════════════════════════════════════════════════════
// HELPERS
// ════════════════════════════════════════════════════════════════════════════

function deleteToken(PDO $pdo, string $token, string $tmpPath): void
{
    try {
        if ($tmpPath && file_exists($tmpPath)) @unlink($tmpPath);
        $pdo->prepare("DELETE FROM classify_tokens WHERE token = ?")->execute([$token]);
    } catch (Throwable $e) {
        error_log("[commit-documents] Token cleanup error: " . $e->getMessage());
    }
}

function sanitizeDate(string $raw): string
{
    $raw = trim($raw);
    if (!$raw) return '';
    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $raw)) return $raw;
    if (preg_match('/^(\d{2})-(\d{2})-(\d{4})$/', $raw, $m)) return "{$m[3]}-{$m[2]}-{$m[1]}";
    return '';
}

function sanitizeStem(string $name): string
{
    $name = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '_', $name));
    return substr(trim($name, '_'), 0, 80);
}

function generateUUID(): string
{
    return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
        mt_rand(0, 0xffff), mt_rand(0, 0xffff),
        mt_rand(0, 0xffff),
        mt_rand(0, 0x0fff) | 0x4000,
        mt_rand(0, 0x3fff) | 0x8000,
        mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
    );
}

function jsonOut(array $data): never
{
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}