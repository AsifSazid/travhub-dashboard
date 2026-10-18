<?php
/**
 * FILE PATH: /api/visa-services/generate-document-package.php
 * Generate PDF document package per traveler, or ZIP of all.
 * Uses mPDF (pages/vendor/autoload.php)
 *
 * GET params:
 *   work_sys_id  — required
 *   trav_sys_id  — optional: single traveler PDF
 *   format       — 'zip' (default) or 'pdf'
 *   all=1        — ZIP of all travelers
 *
 * PDF order per traveler:
 *   1. Cover letter
 *   2. Passport
 *   3. Photos
 *   4. Bank Statement
 *   5. Other docs (by seq order)
 */

ob_start();
include_once('../../authenticate.php');
ob_end_clean();
require_once '../../server/api_bootstrap.php';
require_once '../../server/db_connection.php';
require_once '../../server/smb_upload_handler.php';

// ── Load mPDF ───────────────────────────────────────────────
$autoload = __DIR__ . '/../../pages/vendor/autoload.php';
if (!file_exists($autoload)) {
    // Try alternate path
    $autoload = __DIR__ . '/../../../pages/vendor/autoload.php';
}
if (!file_exists($autoload)) {
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'mPDF not found (vendor/autoload.php missing)']);
    exit;
}
require_once $autoload;

$hasImagick = extension_loaded('imagick');

// ── Fetch visa service row ───────────────────────────────────
function _vsFetchRow(PDO $pdo, string $workSysId): ?array {
    $st = $pdo->prepare('SELECT * FROM visa_services WHERE work_sys_id = ? LIMIT 1');
    $st->execute([$workSysId]);
    $row = $st->fetch(PDO::FETCH_ASSOC);
    if (!$row) return null;
    $row['vs_travelers'] = json_decode($row['vs_travelers'] ?? '[]', true) ?? [];
    $row['meta_data']    = json_decode($row['meta_data']    ?? '{}', true) ?? [];
    return $row;
}

// ── Fetch file from SMB via OMV_SMB_Manager → save to temp ──
// Same logic as serve.php _serveDoc() — reused here without HTTP overhead
function _resolvePageFile(PDO $pdo, string $docSysId, string $pageFilename): ?string {
    if (!$docSysId || !$pageFilename) return null;

    try {
        $stmt = $pdo->prepare("
            SELECT d.doc_type, d.smb_folder,
                   t.sys_id AS traveler_sys_id, t.name AS traveler_name
            FROM traveler_documents d
            JOIN travelers t ON t.sys_id = d.traveler_id
            WHERE d.sys_id = ? LIMIT 1
        ");
        $stmt->execute([$docSysId]);
        $doc = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (Throwable $e) {
        error_log('[gen-doc-pkg] DB error: ' . $e->getMessage());
        return null;
    }

    if (!$doc) return null;

    // Build SMB path — same as serve.php _serveDoc()
    $serverPrefix  = trim(@file_get_contents(__DIR__ . '/../../server-name.txt') ?? '');
    $cleanSysId    = preg_replace('/\s+/u', '', $doc['traveler_sys_id']);
    $cleanName     = preg_replace('/\s+/u', '', $doc['traveler_name']);
    $travelerFolder= "{$cleanSysId}_{$cleanName}";
    $smbFolder     = $doc['smb_folder'] ?: ($doc['doc_type'] ?: 'all_documents');
    $smbPath       = "{$serverPrefix}_travelers/{$travelerFolder}/{$smbFolder}/" . basename($pageFilename);

    $ext     = strtolower(pathinfo($pageFilename, PATHINFO_EXTENSION));
    $tmpPath = sys_get_temp_dir() . '/vs_doc_' . uniqid() . '.' . $ext;

    try {
        $omv = new OMV_SMB_Manager();
        $ok  = $omv->get_file($smbPath, $tmpPath);
        if (!$ok || !file_exists($tmpPath) || filesize($tmpPath) === 0) {
            @unlink($tmpPath);
            error_log('[gen-doc-pkg] SMB get_file failed: ' . $smbPath);
            return null;
        }
        return $tmpPath;
    } catch (Throwable $e) {
        @unlink($tmpPath);
        error_log('[gen-doc-pkg] SMB error: ' . $e->getMessage());
        return null;
    }
}

// ── Resize + compress image via Imagick ──────────────────────
// Target: max 700px, JPEG 55 quality — readable but compact
function _compressImage(string $srcPath, int $limitKb): string {
    if (!extension_loaded('imagick')) return $srcPath;
    try {
        $img = new Imagick($srcPath);
        $img->setImageBackgroundColor('white');
        $img = $img->flattenImages();
        if ($img->getImageWidth() > 500) {
            $img->resizeImage(500, 0, Imagick::FILTER_BOX, 1);
        }
        $img->setImageFormat('jpeg');
        $img->setImageCompressionQuality(40);
        $img->stripImage(); // Remove EXIF/metadata — saves space
        $blob = $img->getImageBlob();
        $tmp  = sys_get_temp_dir() . '/vs_img_' . uniqid() . '.jpg';
        file_put_contents($tmp, $blob);
        $img->destroy();
        return $tmp;
    } catch (Exception $e) { return $srcPath; }
}

// ── Strip HTML tags from cover letter ───────────────────────
function _clToPlainText(string $html): string {
    $text = strip_tags(str_replace(['<br>','<br/>','<br />','</p>','</div>'], "\n", $html));
    return html_entity_decode(trim($text), ENT_QUOTES, 'UTF-8');
}

// ── Build PDF for one traveler using mPDF ───────────────────
function _buildTravelerPdf(array $trav, array $meta, PDO $pdo): string {
    $limitKb   = (int)($meta['file_size_limit_kb'] ?? 300);
    $visaTitle = $meta['master_visa_title'] ?? 'Visa Application';
    $country   = $meta['country_name']      ?? '';
    $name      = $trav['name']              ?? 'Traveler';
    $passport  = $trav['passport_no']       ?? '';
    $docs      = $trav['docs']              ?? [];
    $cl        = $trav['cover_letter']      ?? [];
    $clContent = trim($cl['edited'] ?? ($cl['ai_draft'] ?? ''));

    // Sort docs by seq
    usort($docs, fn($a, $b) => (float)($a['seq'] ?? 99) <=> (float)($b['seq'] ?? 99));

    // Deduplicate by doc_sys_id — same doc should not appear twice
    $seenDocIds = [];
    $docs = array_filter($docs, function($doc) use (&$seenDocIds) {
        $id = $doc['doc_sys_id'] ?? null;
        if (!$id) return true; // missing → keep (will be skipped later)
        if (isset($seenDocIds[$id])) return false;
        $seenDocIds[$id] = true;
        return true;
    });

    // Init mPDF
    $tmpDir = sys_get_temp_dir() . '/mpdf_' . uniqid();
    if (!is_dir($tmpDir)) mkdir($tmpDir, 0775, true);
    $mpdf = new \Mpdf\Mpdf([
        'margin_left'        => 15,
        'margin_right'       => 15,
        'margin_top'         => 15,
        'margin_bottom'      => 15,
        'default_font'       => 'dejavusans',
        'tempDir'            => $tmpDir,
        'img_dpi'            => 50,
        'useSubstitutions'   => false,
        'useKerning'         => false,
    ]);
    $mpdf->SetTitle($visaTitle . ' — ' . $name);

    // ── Page 1: Cover Letter ────────────────────────────────
    $mpdf->AddPage();

    if ($clContent) {
        // If HTML, use WriteHTML; if plain text, convert to HTML
        if (str_contains($clContent, '<')) {
            $html = '<h2 style="text-align:center;font-size:14pt;">' . htmlspecialchars($visaTitle) . ' — Cover Letter</h2>'
                  . '<p style="text-align:center;color:#555;">' . htmlspecialchars($name) . ' | Passport: ' . htmlspecialchars($passport) . '</p>'
                  . '<hr>'
                  . $clContent;
        } else {
            $plain = htmlspecialchars($clContent);
            $plain = nl2br($plain);
            $html  = '<h2 style="text-align:center;font-size:14pt;">' . htmlspecialchars($visaTitle) . ' — Cover Letter</h2>'
                   . '<hr><div style="line-height:1.8;font-size:11pt;">' . $plain . '</div>';
        }
        $mpdf->WriteHTML($html);
    } else {
        $mpdf->WriteHTML(
            '<h2 style="text-align:center;">' . htmlspecialchars($visaTitle) . '</h2>'
            . '<p style="text-align:center;">Applicant: ' . htmlspecialchars($name)
            . ' | Passport: ' . htmlspecialchars($passport) . '</p>'
            . '<p style="text-align:center;color:#888;">(No cover letter generated)</p>'
        );
    }

    // ── Following pages: documents ──────────────────────────
    foreach ($docs as $doc) {
        if (($doc['status'] ?? '') !== 'uploaded') continue;

        // Get pages array
        $pages = $doc['pages'] ?? [];
        if (empty($pages) && !empty($doc['file_name'])) {
            $pages = [['filename' => $doc['file_name'], 'url' => null]];
        }
        // pages may be stored as JSON string
        if (is_string($pages)) {
            $pages = json_decode($pages, true) ?? [];
        }

        // Sort pages by page_no ascending to fix reversed page order
        usort($pages, fn($a, $b) => ($a['page_no'] ?? $a['page_no'] ?? 0) <=> ($b['page_no'] ?? 0));

        $totalPages = count($pages);

        foreach ($pages as $pi => $page) {
            $filename  = $page['filename'] ?? '';
            if (!$filename) continue;

            $docSysId  = $doc['doc_sys_id'] ?? '';
            $localPath = _resolvePageFile($pdo, $docSysId, $filename);
            if (!$localPath) continue;

            // For docs with 2 pages (e.g. ID card front/back): put both on one page
            if ($totalPages === 2 && $pi === 0) {
                $nextPage = $pages[1] ?? null;
                $nextFilename = $nextPage['filename'] ?? '';
                $nextPath = $nextFilename ? _resolvePageFile($pdo, $docSysId, $nextFilename) : null;

                if ($nextPath) {
                    $mpdf->AddPage();
                    $docLabel = $doc['doc'] ?? 'Document';
                    $mpdf->WriteHTML('<p style="font-size:8pt;color:#888;margin-bottom:4px;">'
                        . htmlspecialchars($docLabel . ' — ' . $name) . '</p>');
                    $ext1 = strtoupper(pathinfo($localPath, PATHINFO_EXTENSION));
                    $ext2 = strtoupper(pathinfo($nextPath, PATHINFO_EXTENSION));
                    if ($ext1 === 'JPEG') $ext1 = 'JPG';
                    if ($ext2 === 'JPEG') $ext2 = 'JPG';
                    // Front on left, back on right — each 85mm wide
                    $mpdf->Image($localPath, 10, 25, 85, 0, $ext1, '', 'N', false, 120, 'L', false, false, 0, true);
                    $mpdf->Image($nextPath,  105, 25, 85, 0, $ext2, '', 'N', false, 120, 'R', false, false, 0, true);
                    @unlink($nextPath);
                    continue 2; // skip next page (already handled)
                }
            }

            // Skip if this is page index 1 and totalPages=2 (already handled above)
            if ($totalPages === 2 && $pi === 1) {
                @unlink($localPath);
                continue;
            }

            $mpdf->AddPage();
            $docLabel = ($doc['doc'] ?? 'Document') . ($totalPages > 2 && $pi > 0 ? ' (p' . ($pi+1) . ')' : '');


            $ext = strtolower(pathinfo($localPath, PATHINFO_EXTENSION));

            // Page label already added above
            $mpdf->WriteHTML(
                '<p style="font-size:8pt;color:#888;margin-bottom:4px;">'
                . htmlspecialchars($docLabel . ' — ' . $name) . '</p>'
            );

            if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp'])) {
                $imgPath = $hasImagick ? _compressImage($localPath, $limitKb) : $localPath;
                try {
                    // Use mPDF Image() directly — avoids pcre.backtrack_limit issue
                    // Max 150mm wide, auto height, centered
                    $mpdf->Image(
                        $imgPath,
                        20, 22,        // x, y
                        170, 0,        // max width 170mm, auto height
                        strtoupper($ext === 'jpeg' ? 'jpg' : $ext),
                        '', 'N',
                        false, 150,    // dpi 150 for good quality but smaller size
                        'C',
                        false, false, 0, true
                    );
                } catch (Exception $e) {
                    $mpdf->WriteHTML('<p style="color:red;">[Image error: ' . htmlspecialchars($e->getMessage()) . ']</p>');
                }
            } elseif ($ext === 'pdf') {
                if (!$hasImagick) {
                    $mpdf->WriteHTML('<p style="color:#888;">[PDF: Imagick not available]</p>');
                } else {
                    try {
                        $imagick = new Imagick();
                        $imagick->setResolution(50, 50); // 50dpi — smaller file size
                        $imagick->readImage($localPath);
                        $pageCount = $imagick->getNumberImages();
                        for ($p = 0; $p < $pageCount; $p++) {
                            $imagick->setIteratorIndex($p);
                            $imagick->setImageFormat('jpeg');
                            $imagick->setImageBackgroundColor('white');
                            $flat = $imagick->flattenImages();
                            // Resize to max 700px wide
                            if ($flat->getImageWidth() > 500) {
                                $flat->resizeImage(500, 0, Imagick::FILTER_BOX, 1);
                            }
                            $flat->setImageCompressionQuality(40);
                            $blob   = $flat->getImageBlob();
                            $tmpImg = sys_get_temp_dir() . '/vs_pdf_pg_' . uniqid() . '.jpg';
                            file_put_contents($tmpImg, $blob);

                            if ($p > 0) {
                                $mpdf->AddPage();
                                $mpdf->WriteHTML('<p style="font-size:7pt;color:#aaa;margin-bottom:2px;">'
                                    . htmlspecialchars($docLabel . ' (p' . ($p+1) . ') — ' . $name) . '</p>');
                            }
                            $mpdf->Image($tmpImg, 15, ($p===0?20:14), 178, 0, 'JPG', '', 'N', false, 72, 'C', false, false, 0, true);
                            @unlink($tmpImg);
                            $flat->destroy();
                        }
                        $imagick->clear();
                        $imagick->destroy();
                    } catch (Exception $e) {
                        $mpdf->WriteHTML('<p style="color:red;">[PDF error: ' . htmlspecialchars($e->getMessage()) . ']</p>');
                    }
                }
            } else {
                $mpdf->WriteHTML('<p style="color:#888;">[Unsupported: ' . htmlspecialchars($ext) . ']</p>');
            }
        }
    }

    $tmpPath = sys_get_temp_dir() . '/vs_pkg_' . uniqid() . '.pdf';
    $mpdf->Output($tmpPath, \Mpdf\Output\Destination::FILE);
    return $tmpPath;
}

// ════════════════════════════════════════════════════════════
// MAIN
// ════════════════════════════════════════════════════════════
$workSysId = trim($_GET['work_sys_id'] ?? '');
$travSysId = trim($_GET['trav_sys_id'] ?? '');
$format    = trim($_GET['format'] ?? 'zip');
$all       = ($_GET['all'] ?? '') === '1' || $format === 'zip';

if (!$workSysId) {
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'work_sys_id required']);
    exit;
}

$row = _vsFetchRow($pdo, $workSysId);
if (!$row) {
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'Visa service not found']);
    exit;
}

$travelers = $row['vs_travelers'];
$meta      = $row['meta_data'];
$visaTitle = $meta['master_visa_title'] ?? 'Visa';

// ── Single traveler PDF ──────────────────────────────────────
if ($travSysId && !$all) {
    $trav = null;
    foreach ($travelers as $t) {
        if (($t['sys_id'] ?? '') === $travSysId) { $trav = $t; break; }
    }
    if (!$trav) {
        header('Content-Type: application/json');
        echo json_encode(['status' => 'error', 'message' => 'Traveler not found']);
        exit;
    }

    try {
        $pdfPath = _buildTravelerPdf($trav, $meta, $pdo);
        $fname   = preg_replace('/[^a-zA-Z0-9_-]/', '_', $trav['name'] ?? 'traveler') . '_visa_docs.pdf';
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . $fname . '"');
        header('Content-Length: ' . filesize($pdfPath));
        readfile($pdfPath);
        @unlink($pdfPath);
    } catch (Throwable $e) {
        header('Content-Type: application/json');
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
    exit;
}

// ── All travelers → ZIP ──────────────────────────────────────
if ($all || !$travSysId) {
    if (!class_exists('ZipArchive')) {
        header('Content-Type: application/json');
        echo json_encode(['status' => 'error', 'message' => 'ZipArchive not available']);
        exit;
    }

    $zipPath = sys_get_temp_dir() . '/vs_all_' . uniqid() . '.zip';
    $zip     = new ZipArchive();
    if ($zip->open($zipPath, ZipArchive::CREATE) !== true) {
        header('Content-Type: application/json');
        echo json_encode(['status' => 'error', 'message' => 'Could not create ZIP']);
        exit;
    }

    $tmpFiles = [];
    foreach ($travelers as $trav) {
        try {
            $pdfPath    = _buildTravelerPdf($trav, $meta);
            $fname      = preg_replace('/[^a-zA-Z0-9_-]/', '_', $trav['name'] ?? 'traveler') . '_visa_docs.pdf';
            $zip->addFile($pdfPath, $fname);
            $tmpFiles[] = $pdfPath;
        } catch (Throwable $e) {
            // Skip failed traveler, continue others
            error_log('[generate-document-package] ' . ($trav['name'] ?? '?') . ': ' . $e->getMessage());
        }
    }
    $zip->close();

    $zipName = preg_replace('/[^a-zA-Z0-9_-]/', '_', $visaTitle) . '_docs.zip';
    header('Content-Type: application/zip');
    header('Content-Disposition: attachment; filename="' . $zipName . '"');
    header('Content-Length: ' . filesize($zipPath));
    readfile($zipPath);
    @unlink($zipPath);
    foreach ($tmpFiles as $f) @unlink($f);
    exit;
}

header('Content-Type: application/json');
echo json_encode(['status' => 'error', 'message' => 'Specify trav_sys_id or format=zip']);