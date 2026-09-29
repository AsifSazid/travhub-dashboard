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

$ip_port = @file_get_contents('../ippath.txt');
if (empty($ip_port)) { $ip_port = "http://103.104.219.3:898"; }

$employeeId = $_GET['employee_id'] ?? '';
if (!$employeeId) {
    die('<div style="font-family:Arial,sans-serif;padding:60px;text-align:center;color:#666;"><h2>Missing employee</h2></div>');
}

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

$photoUrl = !empty($emp['profile_photo']) ? $ip_port . 'storage/' . $emp['profile_photo'] : '';
// A generic silhouette, inlined as a data URI, when no profile photo has been uploaded yet.
$photoFallback = "data:image/svg+xml;charset=UTF-8,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 100 100%22%3E%3Ccircle cx=%2250%22 cy=%2250%22 r=%2250%22 fill=%22%231e293b%22/%3E%3Ccircle cx=%2250%22 cy=%2238%22 r=%2216%22 fill=%22%2364748b%22/%3E%3Cellipse cx=%2250%22 cy=%2285%22 rx=%2230%22 ry=%2222%22 fill=%22%2364748b%22/%3E%3C/svg%3E";

// employee.php already exists on the live server as a public, no-login
// verification page -- the QR encodes a link straight to it, not just the
// bare sys_id, per the user's confirmation.
$qrValue = 'https://travhub.com.bd/employee.php?employee_id=' . urlencode($emp['sys_id']);
// ecc=H (high error correction) so the logo overlay below doesn't break scanning.
$qrCodeUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=240x240&margin=8&ecc=H&data=' . urlencode($qrValue);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>ID Card — <?php echo alv($fullName, ''); ?></title>
<style>
    * { box-sizing: border-box; }
    /* Card aspect ratio 5:7, matching the Canva design (180x252pt pages) --
       sized up for on-screen/print clarity while keeping that exact ratio. */
    :root { --card-w: 380px; --card-h: 532px; }
    @page { size: auto; margin: 12mm; }
    body { font-family: 'Poppins', Arial, sans-serif; background: #eee; margin: 0; padding: 24px; display: flex; flex-direction: row; flex-wrap: wrap; align-items: flex-start; justify-content: center; gap: 30px; }

    .page { width: var(--card-w); height: var(--card-h); border-radius: 28px; background: #1b2540; color: #fff; position: relative; overflow: hidden; box-shadow: 0 6px 20px rgba(0,0,0,.28); display: flex; flex-direction: column; }
    .notch { width: 130px; height: 18px; background: #d9d9d9; border-radius: 10px; margin: 22px auto 0; flex-shrink: 0; }

    /* ── FRONT ───────────────────────────────────────────────── */
    .logo-circle { width: 150px; height: 150px; border-radius: 50%; overflow: hidden; margin: 34px auto 0; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
    .logo-circle img { width: 100%; height: 100%; object-fit: cover; }
    .photo-wrap { width: 168px; height: 168px; border-radius: 50%; border: 6px solid #3ecf8e; margin: 54px auto 0; overflow: hidden; background: #fff; flex-shrink: 0; }
    .photo-wrap img { width: 100%; height: 100%; object-fit: cover; }
    .name-block { text-align: center; margin-top: 34px; padding: 0 20px; }
    .name-block .name { color: #3ecf8e; font-weight: 800; font-size: 24px; letter-spacing: .3px; line-height: 1.15; }
    .name-block .desig { font-size: 15px; color: #e4e8f0; margin-top: 8px; line-height: 1.4; }
    .footer-block { margin-top: auto; text-align: center; font-size: 13.5px; color: #cbd2dd; padding: 20px 16px 28px; line-height: 1.7; }

    /* ── BACK ────────────────────────────────────────────────── */
    .back-body { padding: 0 26px; display: flex; flex-direction: column; flex: 1; }
    .back h1 { color: #3ecf8e; text-align: center; font-size: 21px; margin: 40px 0 26px; letter-spacing: .3px; }
    .back .info-row { display: grid; grid-template-columns: 128px 12px 1fr; font-size: 14px; margin-bottom: 11px; color: #eef1f6; }
    .back .info-row b { color: #fff; font-weight: 700; }
    .back .qr-wrap { text-align: center; margin: 22px 0; position: relative; display: flex; justify-content: center; }
    .back .qr-wrap img.qr-img { width: 150px; height: 150px; border-radius: 8px; background: #fff; padding: 6px; display: block; }
    .back .qr-wrap .qr-logo { position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); width: 40px; height: 40px; border-radius: 50%; background: #0f1830; display: flex; align-items: center; justify-content: center; border: 3px solid #fff; }
    .back .qr-wrap .qr-logo img { width: 24px; height: 24px; object-fit: contain; display: block; }
    .back .notice { background: #3ecf8e; color: #0f1830; font-weight: 700; font-size: 13px; text-align: center; padding: 14px 16px; line-height: 1.5; margin-top: auto; }
    .back .return-note { font-size: 11px; color: #c7cedb; text-align: center; padding: 16px 20px 22px; line-height: 1.55; }

    .no-print { text-align: center; }
    @media print { .no-print { display: none; } body { background: #fff; gap: 24px; padding: 12px; } .page { box-shadow: none; } }
</style>
</head>
<body>

<div class="no-print">
    <button onclick="window.print()" style="padding:10px 24px;background:#1b2540;color:#3ecf8e;border:none;border-radius:8px;font-size:14px;cursor:pointer;">Print / Save as PDF</button>
</div>

<!-- FRONT -->
<div class="page front">
    <div class="notch"></div>
    <div class="logo-circle">
        <img src="../assets/images/logo/round-logo.png" alt="TravHub"
             onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
        <div class="logo-fallback" style="display:none; width:100%; height:100%; align-items:center; justify-content:center; background:#0f1830; font-weight:800; font-size:26px; text-align:center; line-height:1.1;"><span style="color:#3ecf8e; display:block;">trav</span>hub</div>
    </div>
    <div class="photo-wrap">
        <img src="<?php echo $photoUrl ? alv($photoUrl, '') : $photoFallback; ?>" onerror="this.src='<?php echo $photoFallback; ?>'" alt="">
    </div>
    <div class="name-block">
        <div class="name"><?php echo alv($fullName, '[EMPLOYEE NAME]'); ?></div>
        <div class="desig"><?php echo nl2br(alv(str_replace(',', ",
", $designation ?: ''), '[Designation]')); ?></div>
    </div>
    <div class="footer-block">
        <?php echo alv($emp['sys_id'], ''); ?><br>
        Contact: <?php echo alv($email['primary'] ?? null, '—'); ?>
    </div>
</div>

<!-- BACK -->
<div class="page back">
    <div class="notch"></div>
    <div class="back-body">
        <h1>TRAVHUB GLOBAL LIMITED</h1>

        <div class="info-row"><span>Date of Joining</span><span>:</span><b><?php echo fmtDateShort($joinDate); ?></b></div>
        <div class="info-row"><span>Date of Issue</span><span>:</span><b><?php echo fmtDateShort(date('Y-m-d')); ?></b></div>
        <div class="info-row"><span>Date of Expiration</span><span>:</span><b><?php echo fmtDateShort($expiryDate); ?></b></div>
        <div class="info-row"><span>Blood Group</span><span>:</span><b><?php echo alv($basic['blood_group'] ?? null); ?></b></div>
        <div class="info-row"><span>Phone No</span><span>:</span><b><?php echo alv($phone['primary_no'] ?? null); ?></b></div>

        <div class="qr-wrap">
            <img class="qr-img" src="<?php echo $qrCodeUrl; ?>" alt="QR">
            <div class="qr-logo">
                <img src="../assets/images/logo/round-logo.png" alt=""
                     onerror="this.src='data:image/svg+xml;charset=UTF-8,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 40 40%22%3E%3Ctext x=%2220%22 y=%2227%22 font-family=%22Arial%22 font-size=%2220%22 font-weight=%22800%22 fill=%22%233ecf8e%22 text-anchor=%22middle%22%3ET%3C/text%3E%3C/svg%3E'; this.onerror=null;">
            </div>
        </div>

        <div class="notice">This Card Is The Property of TravHub Global Limited.<br>Returnable when leaving the Company</div>
        <div class="return-note">If found please return to <b>TravHub Global Limited</b>. House-01, Road-6, Sector-3, Uttara, Dhaka-1230<br>Mobile: 01611482773 · Mail: info@travhub.com.bd</div>
    </div>
</div>

</body>
</html>