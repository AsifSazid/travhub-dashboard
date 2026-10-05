<?php
/**
 * FILE PATH: /api/visa-services/generate-document-package.php
 * Generate PDF document package per traveler, or ZIP of all.
 *
 * GET params:
 *   work_sys_id  — required
 *   trav_sys_id  — optional: single traveler PDF
 *   all=1        — return ZIP of all travelers
 *
 * PDF contents per traveler:
 *   Page 1: Cover letter (if available)
 *   Following pages: uploaded docs (images/PDFs) in sequence order
 *
 * Compression: images resized to max 1200px wide, converted to JPEG at quality
 * adjusted to meet file_size_limit_kb if possible.
 */

ob_start();
include_once('../../authenticate.php');
include_once('../../server/connect.php');
ob_end_clean();

// ── Load TCPDF + Imagick availability ──────────────────────
$tcpdfPath = __DIR__ . '/../../vendor/tcpdf/tcpdf.php';
$hasTcpdf  = file_exists($tcpdfPath);
if ($hasTcpdf) require_once($tcpdfPath);
$hasImagick = extension_loaded('imagick');

// ── Fetch visa service row ──────────────────────────────────
function _vsFetchRow(PDO $pdo, string $workSysId): ?array {
    $st = $pdo->prepare('SELECT * FROM visa_services WHERE work_sys_id=? LIMIT 1');
    $st->execute([$workSysId]);
    $row = $st->fetch(PDO::FETCH_ASSOC);
    if (!$row) return null;
    $row['vs_travelers'] = json_decode($row['vs_travelers'] ?? '[]', true) ?? [];
    $row['meta_data']    = json_decode($row['meta_data']    ?? '{}', true) ?? [];
    return $row;
}

// ── Compress image to target KB ──────────────────────────────
function _compressImage(string $srcPath, int $limitKb): string {
    if (!extension_loaded('imagick')) return $srcPath;
    try {
        $img = new Imagick($srcPath);
        // Flatten for composited images (transparent background → white)
        $img->setImageBackgroundColor('white');
        $img = $img->flattenImages();
        // Resize if wider than 1200px
        $w = $img->getImageWidth();
        if ($w > 1200) $img->resizeImage(1200, 0, Imagick::FILTER_LANCZOS, 1);
        // Convert to JPEG for size reduction
        $img->setImageFormat('jpeg');
        // Binary search quality to meet limit
        $lo = 30; $hi = 85; $best = null;
        for ($i = 0; $i < 6; $i++) {
            $q = (int)(($lo + $hi) / 2);
            $img->setImageCompressionQuality($q);
            $blob = $img->getImageBlob();
            $kb   = strlen($blob) / 1024;
            if ($kb <= $limitKb) { $best = $blob; $lo = $q; } else { $hi = $q; }
        }
        if (!$best) { $img->setImageCompressionQuality(30); $best = $img->getImageBlob(); }
        $tmpPath = sys_get_temp_dir() . '/vs_img_' . uniqid() . '.jpg';
        file_put_contents($tmpPath, $best);
        $img->destroy();
        return $tmpPath;
    } catch (Exception $e) {
        return $srcPath;
    }
}

// ── Build PDF for one traveler ───────────────────────────────
function _buildTravelerPdf(array $trav, array $meta, string $ip_port, PDO $pdo): string {
    global $hasTcpdf, $hasImagick;

    $limitKb   = (int)($meta['file_size_limit_kb'] ?? 300);
    $visaTitle = $meta['master_visa_title'] ?? 'Visa Application';
    $name      = $trav['name']        ?? 'Traveler';
    $passport  = $trav['passport_no'] ?? '';
    $docs      = $trav['docs']        ?? [];

    // Sort docs by sequence
    usort($docs, fn($a,$b) => ($a['seq']??99) <=> ($b['seq']??99));

    if (!$hasTcpdf) {
        // Fallback: plain text file
        $tmp = sys_get_temp_dir() . '/vs_' . uniqid() . '.txt';
        $lines = ["Visa Document Package", "Applicant: {$name}", "Passport: {$passport}", "Visa: {$visaTitle}", "", "Cover Letter:", ""];
        $cl = $trav['cover_letter'] ?? [];
        $lines[] = $cl['edited'] ?? ($cl['ai_draft'] ?? '(No cover letter)');
        $lines[] = "";
        $lines[] = "Documents: " . count($docs) . " uploaded";
        file_put_contents($tmp, implode("\n", $lines));
        return $tmp;
    }

    // TCPDF
    $pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8');
    $pdf->setPrintHeader(false);
    $pdf->setPrintFooter(false);
    $pdf->SetMargins(20, 20, 20);
    $pdf->SetAutoPageBreak(true, 15);
    $pdf->SetFont('helvetica', '', 11);
    $pdf->AddPage();

    // ── Cover letter page ─────────────────────────────────
    $cl     = $trav['cover_letter'] ?? [];
    $clText = trim($cl['edited'] ?? ($cl['ai_draft'] ?? ''));
    if ($clText) {
        $pdf->SetFont('helvetica', 'B', 14);
        $pdf->Cell(0, 10, $visaTitle . ' — Cover Letter', 0, 1, 'C');
        $pdf->Ln(4);
        $pdf->SetFont('helvetica', '', 11);
        $pdf->MultiCell(0, 6, $clText, 0, 'L');
    } else {
        $pdf->SetFont('helvetica', 'B', 14);
        $pdf->Cell(0, 10, $visaTitle, 0, 1, 'C');
        $pdf->SetFont('helvetica', '', 10);
        $pdf->Cell(0, 7, "Applicant: {$name}  |  Passport: {$passport}", 0, 1, 'C');
    }

    // ── Document pages ────────────────────────────────────
    foreach ($docs as $doc) {
        if (($doc['status'] ?? '') !== 'uploaded') continue;
        $filePath = $doc['file_path'] ?? '';
        if (!$filePath) continue;

        // Resolve path (strip leading slash and ip_port prefix)
        $localPath = $_SERVER['DOCUMENT_ROOT'] . '/' . ltrim(parse_url($filePath, PHP_URL_PATH), '/');
        if (!file_exists($localPath)) {
            // Try relative from project root
            $localPath = __DIR__ . '/../../' . ltrim($filePath, '/');
        }
        if (!file_exists($localPath)) continue;

        $ext = strtolower(pathinfo($localPath, PATHINFO_EXTENSION));
        $pdf->AddPage();

        // Label
        $pdf->SetFont('helvetica', 'B', 9);
        $pdf->Cell(0, 6, ($doc['doc_name'] ?? 'Document') . ' — ' . $name, 0, 1, 'L');
        $pdf->Ln(2);

        if (in_array($ext, ['jpg','jpeg','png','webp','gif'])) {
            $imgPath = $hasImagick ? _compressImage($localPath, $limitKb) : $localPath;
            try {
                // Fit image to A4 content area (170mm × 240mm)
                $pdf->Image($imgPath, '', '', 170, 0, strtoupper($ext === 'jpg' ? 'JPEG' : $ext), '', 'T', true, 150, 'C', false, false, 0, true);
            } catch (Exception $e) {
                $pdf->SetFont('helvetica', 'I', 9);
                $pdf->Cell(0, 8, '[Image could not be embedded: ' . $e->getMessage() . ']', 0, 1);
            }
        } elseif ($ext === 'pdf') {
            // Embed PDF pages via TCPDF SetSourceFile
            try {
                $numPages = $pdf->setSourceFile($localPath);
                for ($p = 1; $p <= $numPages; $p++) {
                    if ($p > 1) $pdf->AddPage();
                    $tplIdx = $pdf->importPage($p);
                    $pdf->useTemplate($tplIdx, null, null, 0, 0, true);
                }
            } catch (Exception $e) {
                $pdf->SetFont('helvetica', 'I', 9);
                $pdf->Cell(0, 8, '[PDF could not be embedded]', 0, 1);
            }
        } else {
            $pdf->SetFont('helvetica', 'I', 9);
            $pdf->Cell(0, 8, '[Unsupported file type: ' . $ext . ']', 0, 1);
        }
    }

    $tmpPath = sys_get_temp_dir() . '/vs_pkg_' . uniqid() . '.pdf';
    $pdf->Output($tmpPath, 'F');
    return $tmpPath;
}

// ════════════════════════════════════════════════════════════
// MAIN
// ════════════════════════════════════════════════════════════
$workSysId  = trim($_GET['work_sys_id'] ?? '');
$travSysId  = trim($_GET['trav_sys_id'] ?? '');
$all        = ($_GET['all'] ?? '') === '1';

if (!$workSysId) {
    header('Content-Type: application/json');
    echo json_encode(['status'=>'error','message'=>'work_sys_id required']);
    exit;
}

$ip_port = @file_get_contents(__DIR__ . '/../../ippath.txt');
if (empty($ip_port)) $ip_port = 'http://103.104.219.3:898/';

$row = _vsFetchRow($pdo, $workSysId);
if (!$row) {
    header('Content-Type: application/json');
    echo json_encode(['status'=>'error','message'=>'Visa service not found']);
    exit;
}

$travelers = $row['vs_travelers'];
$meta      = $row['meta_data'];
$visaTitle = $meta['master_visa_title'] ?? 'Visa';

// ── Single traveler ──────────────────────────────────────────
if ($travSysId && !$all) {
    $trav = null;
    foreach ($travelers as $t) { if (($t['sys_id'] ?? '') === $travSysId) { $trav = $t; break; } }
    if (!$trav) {
        header('Content-Type: application/json');
        echo json_encode(['status'=>'error','message'=>'Traveler not found']);
        exit;
    }
    $pdfPath = _buildTravelerPdf($trav, $meta, $ip_port, $pdo);
    $fname   = preg_replace('/[^a-zA-Z0-9_-]/', '_', ($trav['name'] ?? 'traveler')) . '_visa_docs.pdf';
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="' . $fname . '"');
    header('Content-Length: ' . filesize($pdfPath));
    readfile($pdfPath);
    @unlink($pdfPath);
    exit;
}

// ── All travelers → ZIP ──────────────────────────────────────
if ($all) {
    if (!class_exists('ZipArchive')) {
        header('Content-Type: application/json');
        echo json_encode(['status'=>'error','message'=>'ZipArchive not available on server']);
        exit;
    }

    $zipPath = sys_get_temp_dir() . '/vs_all_' . uniqid() . '.zip';
    $zip     = new ZipArchive();
    if ($zip->open($zipPath, ZipArchive::CREATE) !== true) {
        header('Content-Type: application/json');
        echo json_encode(['status'=>'error','message'=>'Could not create ZIP']);
        exit;
    }

    $tmpFiles = [];
    foreach ($travelers as $trav) {
        $pdfPath = _buildTravelerPdf($trav, $meta, $ip_port, $pdo);
        $fname   = preg_replace('/[^a-zA-Z0-9_-]/', '_', ($trav['name'] ?? 'traveler')) . '_visa_docs.pdf';
        $zip->addFile($pdfPath, $fname);
        $tmpFiles[] = $pdfPath;
    }
    $zip->close();

    $zipName = preg_replace('/[^a-zA-Z0-9_-]/', '_', $visaTitle) . '_document_packages.zip';
    header('Content-Type: application/zip');
    header('Content-Disposition: attachment; filename="' . $zipName . '"');
    header('Content-Length: ' . filesize($zipPath));
    readfile($zipPath);
    @unlink($zipPath);
    foreach ($tmpFiles as $f) @unlink($f);
    exit;
}

header('Content-Type: application/json');
echo json_encode(['status'=>'error','message'=>'Specify trav_sys_id or all=1']);