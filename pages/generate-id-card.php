<?php
// PATH: /pages/generate-id-card.php
//
// Generates a print-ready front+back ID Card for one employee, matching the
// provided design (dark navy card, TravHub logo circle, circular photo,
// name/designation, employee id/contact on the front; company name,
// join/issue/expiry dates, blood group, phone, QR code, and the
// return-if-found notice on the back).
//
// Expiration = date_of_join + 2 years (fixed duration, per the user's
// decision) -- there is no separate "expiry date" field to store.
//
// QR code currently encodes only the employee's sys_id (via the same
// QRServer API used for the invoice payment QR) as a neutral placeholder --
// the user said they'd specify what scanning it should do later; swapping
// the encoded value to a verification URL at that point needs no other
// change here.

include_once('./authenticate.php');
require_once __DIR__ . '/../server/db_connection.php';
require_once __DIR__ . '/../server/hrm_permissions.php';

$ip_port = @file_get_contents('../ippath.txt');
if (empty($ip_port)) { $ip_port = "http://103.104.219.3:898"; }

$employeeId = $_GET['employee_id'] ?? '';
if (!$employeeId) {
    die('<div style="font-family:Arial,sans-serif;padding:60px;text-align:center;color:#666;"><h2>Missing employee</h2></div>');
}
requireHrmOrSelf($pdo, $employeeId, 'hr_id_card');

$stmt = $pdo->prepare("SELECT * FROM employees WHERE sys_id = ? LIMIT 1");
$stmt->execute([$employeeId]);
$emp = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$emp) {
    die('<div style="font-family:Arial,sans-serif;padding:60px;text-align:center;color:#666;"><h2>Employee not found</h2></div>');
}

$company = json_decode($emp['company_related_info'] ?? '{}', true) ?: [];
$basic   = json_decode($emp['basic_info'] ?? '{}', true) ?: [];
$phone   = json_decode($emp['phone'] ?? '{}', true) ?: [];
$email   = json_decode($emp['email'] ?? '{}', true) ?: [];

function alv($v, $fallback = '—') { $v = trim((string)($v ?? '')); return $v !== '' ? htmlspecialchars($v, ENT_QUOTES, 'UTF-8') : $fallback; }
function fmtDateShort($v) { if (!$v) return '—'; try { return strtoupper((new DateTime($v))->format('d-M-Y')); } catch (Exception $e) { return htmlspecialchars($v); } }

$fullName = strtoupper($emp['name'] ?? '');
$designation = $company['designation'] ?? '';
$joinDate = $company['date_of_join'] ?? null;

// Fixed 2-year validity from date of join -- no separate expiry field exists.
$expiryDate = null;
if ($joinDate) {
    try { $expiryDate = (clone (new DateTime($joinDate)))->modify('+2 years')->format('Y-m-d'); } catch (Exception $e) {}
}

$images = json_decode($emp['image_name'] ?? '', true);

if (!empty($images)) {
    $filePath   = $images[0]['file_path'] ?? '';
    $storedName = $images[0]['stored_name'];
}

$photoUrl = !empty($filePath) ? $ip_port . 'uploads/' . $filePath . '?t=' . time() : '';

// A generic silhouette, inlined as a data URI, when no profile photo has been uploaded yet.
$photoFallback = "data:image/svg+xml;charset=UTF-8,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 100 100%22%3E%3Ccircle cx=%2250%22 cy=%2250%22 r=%2250%22 fill=%22%231e293b%22/%3E%3Ccircle cx=%2250%22 cy=%2238%22 r=%2216%22 fill=%22%2364748b%22/%3E%3Cellipse cx=%2250%22 cy=%2285%22 rx=%2230%22 ry=%2222%22 fill=%22%2364748b%22/%3E%3C/svg%3E";

// employee.php already exists on the live server as a public, no-login
// verification page -- the QR encodes a link straight to it, not just the
// bare sys_id, per the user's confirmation.
$qrValue = 'https://travhub.com.bd/employee.php?employee_id=' . urlencode($emp['sys_id']);
// ecc=H (high error correction) so the logo overlay below doesn't break scanning.
$qrCodeUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=240x240&margin=8&ecc=H&color=1b2a4a&data=' . urlencode($qrValue);?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>ID Card — <?php echo alv($fullName, ''); ?></title>
<style>
    * { box-sizing: border-box; }
    :root { --card-w: 340px; --card-h: 540px; --navy: #1b2a4a; --green: #3ecf8e; --dark: #0f1c35; }
    @page { size: auto; margin: 10mm; }
    body {
        font-family: 'Segoe UI', Arial, sans-serif;
        background: #c8cdd8;
        margin: 0; padding: 32px;
        display: flex; flex-direction: row; flex-wrap: wrap;
        align-items: flex-start; justify-content: center; gap: 36px;
    }

    /* ── card shell ── */
    .page {
        width: var(--card-w); height: var(--card-h);
        border-radius: 22px;
        background: var(--navy);
        color: #fff;
        position: relative; overflow: hidden;
        box-shadow: 0 10px 34px rgba(0,0,0,.45);
        display: flex; flex-direction: column;
        align-items: center;
    }

    /* airplane SVG pattern overlay */
    /*.page::before {*/
    /*    content: '';*/
    /*    position: absolute; inset: 0;*/
    /*    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='100' height='100' viewBox='0 0 100 100'%3E%3Cg fill='none' stroke='%23ffffff' stroke-width='1' opacity='0.07'%3E%3Cpath d='M15 50 L65 22 L55 50 L65 78 Z'/%3E%3C/g%3E%3C/svg%3E");*/
    /*    background-size: 100px 100px;*/
    /*    pointer-events: none; z-index: 0;*/
    /*}*/
    .page > * { position: relative; z-index: 1; }

    /* lanyard notch */
    .notch {
        width: 52px; height: 14px;
        background: #b0b8cc; border-radius: 10px;
        margin: 10px auto; flex-shrink: 0;
        box-shadow: inset 0 2px 4px rgba(0,0,0,.2);
    }

    /* ── FRONT ── */
    .logo-circle {
        width: 90px; height: 90px; border-radius: 50%;
        background: var(--dark);
        margin: 18px auto 0;
        display: flex; align-items: center; justify-content: center;
        flex-shrink: 0; overflow: hidden;
        border: 2px solid rgba(255,255,255,.1);
    }
    .logo-circle img { width: 70px; height: 70px; object-fit: contain; display: block; }

    .photo-wrap {
        width: 160px; height: 160px; border-radius: 50%;
        border: 5px solid var(--green);
        margin: 16px auto 0;
        overflow: hidden; background: #2a3a5c;
        flex-shrink: 0;
        box-shadow: 0 0 0 3px rgba(62,207,142,.2);
    }
    .photo-wrap img { width: 100%; height: 100%; object-fit: cover; object-position: top; display: block; }

    .name-block { text-align: center; margin-top: 18px; padding: 20px 20px; width: 100%; }
    .name-block .name {
        color: #ffffff; font-weight: 800;
        font-size: 20px; letter-spacing: .8px; line-height: 1.2;
        text-shadow: 0 1px 4px rgba(0,0,0,.3);
    }
    .name-block .desig {
        font-size: 13px; color: #b8cce0;
        margin-top: 7px; line-height: 1.5;
    }

    .id-contact {
        text-align: center; margin-top: auto; width: 100%;
        padding: 14px 20px 22px;
        font-size: 12.5px; color: #8fa5c4; line-height: 1.85;
        border-top: 1px solid rgba(255,255,255,.1);
    }
    .id-contact strong { color: #c8daf0; font-weight: 600; }

    /* ── BACK ── */
    .back-body {
        padding: 0 24px;
        display: flex; flex-direction: column; flex: 1; width: 100%;
    }
    .back-title {
        color: var(--green); text-align: center;
        font-size: 15px; font-weight: 800; letter-spacing: .6px;
        margin: 22px 0 20px;
    }
    .text-ember{
        color: var(--green) !important;
    }
    .info-row {
        display: grid; grid-template-columns: 118px 14px 1fr;
        font-size: 12.5px; margin-bottom: 11px; color: #b0c0d8;
        align-items: baseline;
    }
    .info-row .lbl { font-weight: 400; }
    .info-row .sep { color: #6a7c98; }
    .info-row .val { color: #fff; font-weight: 700; }

    .qr-wrap {
        margin: 14px auto 0;
        position: relative; display: flex; justify-content: center;
    }
    .qr-wrap img.qr-img {
        width: 140px; height: 140px;
        border-radius: 10px; background: #fff;
        padding: 6px; display: block;
    }
    .qr-wrap .qr-logo {
        position: absolute; top: 50%; left: 50%;
        transform: translate(-50%, -50%);
        width: 36px; height: 36px; border-radius: 50%;
        background: #fff;
        display: flex; align-items: center; justify-content: center;
        overflow: hidden;
    }
    .qr-wrap .qr-logo img { width: 28px; height: 28px; object-fit: contain; display: block; }

    .notice {
        width: 100%;
        background: var(--green); color: #0a1628;
        font-weight: 700; font-size: 11px;
        text-align: center; padding: 8px 0px; line-height: 1.55;
        margin: 20px 0px;
    }
    .return-note {
        font-size: 10px; color: #7a8eaa;
        text-align: center; line-height: 1.85;
        padding: 12px 0px;
        text-align: center; margin-top: auto; width: 100%;
        border-top: 1px solid rgba(255,255,255,.1);
    }

    .no-print { text-align: center; margin-bottom: 16px; }
    @media print {
        .no-print { display: none; }
        body { background: #fff; gap: 20px; padding: 8px; }
        .page { box-shadow: none; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
    }
</style>

</head>
<body>

<div class="no-print">
    <button onclick="window.print()" style="padding:10px 28px;background:#1b2540;color:#3ecf8e;border:none;border-radius:8px;font-size:14px;font-weight:600;cursor:pointer;letter-spacing:.3px;">🖨 Print / Save as PDF</button>
</div>

<!-- FRONT -->
<div class="page front">
    <div class="notch"></div>

    <div class="logo-circle">
        <img src="../assets/images/logo/round-logo.png" alt="TravHub"
             onerror="this.style.display='none'">
    </div>

    <div class="photo-wrap">
        <img src="<?php echo $photoUrl ? alv($photoUrl, '') : $photoFallback; ?>"
             onerror="this.src='<?php echo $photoFallback; ?>'" alt="">
    </div>

    <div class="name-block">
        <div class="name text-ember"><?php echo alv($fullName, '[EMPLOYEE NAME]'); ?></div>
        <div class="desig"><?php
            $desigFormatted = str_replace(',', ",\n", $designation ?: '[Designation]');
            echo nl2br(alv($desigFormatted));
        ?></div>
    </div>

    <div class="id-contact">
        <strong>EMP-ID-<?php
            $rawId = $emp['sys_id'] ?? '';
            echo alv(preg_replace('/^EMP-/i', '', $rawId) ?: $rawId);
        ?></strong><br>
        Contact: <?php echo alv($email['primary'] ?? null, '—'); ?>
    </div>
</div>

<!-- BACK -->
<div class="page back">
    <div class="notch"></div>
    <div class="back-body">
        <div class="back-title">TRAVHUB GLOBAL LIMITED</div>

        <div class="info-row">
            <span class="lbl">Date of Joining</span>
            <span class="sep">:</span>
            <span class="val"><?php echo fmtDateShort($joinDate); ?></span>
        </div>
        <div class="info-row">
            <span class="lbl">Date of Issue</span>
            <span class="sep">:</span>
            <span class="val"><?php echo fmtDateShort(date('Y-m-d')); ?></span>
        </div>
        <div class="info-row">
            <span class="lbl">Date of Expiration</span>
            <span class="sep">:</span>
            <span class="val"><?php echo fmtDateShort($expiryDate); ?></span>
        </div>
        <div class="info-row">
            <span class="lbl">Blood Group</span>
            <span class="sep">:</span>
            <span class="val"><?php echo strtoupper(alv($basic['blood_group'] ?? null)); ?></span>
        </div>
        <div class="info-row">
            <span class="lbl">Phone No</span>
            <span class="sep">:</span>
            <span class="val"><?php echo alv($phone['primary_no'] ?? null); ?></span>
        </div>

        <div class="qr-wrap">
            <img class="qr-img" src="<?php echo $qrCodeUrl; ?>" alt="QR">
            <div class="qr-logo">
                <img src="../assets/images/logo/round-logo.png" alt="TH"
                     onerror="this.src='data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 40 40%22%3E%3Ctext x=%2220%22 y=%2228%22 font-family=%22Arial%22 font-size=%2218%22 font-weight=%22800%22 fill=%22%233ecf8e%22 text-anchor=%22middle%22%3ET%3C/text%3E%3C/svg%3E'; this.onerror=null;">
            </div>
        </div>

    </div>
        <div class="notice">
            This Card Is The Property of TravHub Global Limited.<br>
            Returnable when leaving the Company
        </div>
        
        <div class="return-note">
            If found please return to <b>TravHub Global Limited</b>. <br>House-01, Road-6, Sector-3, Uttara, Dhaka-1230
            Mobile: 01611482773 &middot; Mail: info@travhub.com.bd
        </div>
</div>

</body>
</html>