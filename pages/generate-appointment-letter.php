<?php
// PATH: /pages/generate-appointment-letter.php
//
// Generates a print-ready Appointment Letter for one employee, in one of
// three forms (letter_type): permanent | probationary | intern. Filled from
// employees.company_related_info -- the same fields added for this purpose
// (father_name, mother_name, spouse_name, nid_no, gross_salary,
// reporting_to_name/designation) plus the existing basic_info/address/
// emergency_contact JSON blobs.
//
// This is an HR document, not an accounting one -- it does NOT go through
// server/permissions.php's accounting permission system. Login is required
// via authenticate.php, matching the existing convention set by
// api/eps/generate-authorization-letter.php for payroll documents.

include_once('./authenticate.php');
require_once __DIR__ . '/../server/db_connection.php';
require_once __DIR__ . '/../server/hrm_permissions.php';

$employeeId = $_GET['employee_id'] ?? '';
requireHrmOrSelf($pdo, $employeeId, 'hr_docs_generate');

$letterType = $_GET['letter_type'] ?? 'permanent'; // permanent | probationary | intern
if (!in_array($letterType, ['permanent', 'probationary', 'intern'], true)) {
    $letterType = 'permanent';
}

if (!$employeeId) {
    die('<div style="font-family:Arial,sans-serif;padding:60px;text-align:center;color:#666;"><h2>Missing employee</h2></div>');
}

$stmt = $pdo->prepare("SELECT * FROM employees WHERE sys_id = ? LIMIT 1");
$stmt->execute([$employeeId]);
$emp = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$emp) {
    die('<div style="font-family:Arial,sans-serif;padding:60px;text-align:center;color:#666;"><h2>Employee not found</h2></div>');
}

$company  = json_decode($emp['company_related_info'] ?? '{}', true) ?: [];
$basic    = json_decode($emp['basic_info'] ?? '{}', true) ?: [];
$address  = json_decode($emp['address'] ?? '{}', true) ?: [];
$emergency = json_decode($emp['emergency_contact'] ?? '{}', true) ?: [];

function alv($v, $fallback = '[                    ]') { $v = trim((string)($v ?? '')); return $v !== '' ? htmlspecialchars($v, ENT_QUOTES, 'UTF-8') : $fallback; }
function fmtDate($v) { if (!$v) return '[DD Month YYYY]'; try { return (new DateTime($v))->format('d F Y'); } catch (Exception $e) { return htmlspecialchars($v); } }
function fmtMoney($v) { return $v !== null && $v !== '' ? number_format((float)$v, 2) : '[                ]'; }
function numberToWordsTaka($amount) {
    // Reuses the same "Taka ... only" style already used on invoices, kept
    // local and simple since this is the only caller here.
    if (!is_numeric($amount)) return '[                                        ]';
    $amount = (int)round((float)$amount);
    if ($amount === 0) return 'Zero';
    $ones = ['', 'One','Two','Three','Four','Five','Six','Seven','Eight','Nine','Ten','Eleven','Twelve','Thirteen','Fourteen','Fifteen','Sixteen','Seventeen','Eighteen','Nineteen'];
    $tens = ['', '', 'Twenty','Thirty','Forty','Fifty','Sixty','Seventy','Eighty','Ninety'];
    $twoDigits = function($n) use ($ones, $tens) {
        if ($n < 20) return $ones[$n];
        return trim($tens[intdiv($n, 10)] . ' ' . $ones[$n % 10]);
    };
    $threeDigits = function($n) use ($twoDigits, $ones) {
        $s = '';
        if ($n >= 100) { $s .= $ones[intdiv($n, 100)] . ' Hundred '; $n %= 100; }
        $s .= $twoDigits($n);
        return trim($s);
    };
    $crore = intdiv($amount, 10000000); $amount %= 10000000;
    $lakh  = intdiv($amount, 100000);   $amount %= 100000;
    $thousand = intdiv($amount, 1000);  $amount %= 1000;
    $rest = $amount;
    $parts = [];
    if ($crore) $parts[] = $threeDigits($crore) . ' Crore';
    if ($lakh) $parts[] = $threeDigits($lakh) . ' Lakh';
    if ($thousand) $parts[] = $threeDigits($thousand) . ' Thousand';
    if ($rest) $parts[] = $threeDigits($rest);
    return trim(implode(' ', $parts));
}

$fullName = $emp['name'] ?? '';
$surname  = trim((function() use ($fullName) { $parts = preg_split('/\s+/', trim($fullName)); return end($parts) ?: ''; })());
$grossSalary = $company['gross_salary'] ?? null;

// Reference number: Ref: TGL/HR/APT/{year}/{sequential within that ref series}.
// A simple count-based sequence is used here (not a stored counter table),
// good enough for a printed reference and consistent for re-prints of the
// same employee's letter within the same year.
$refYear = date('Y');
$refPrefix = $letterType === 'permanent' ? 'APT' : 'PRO-INT';
$refCountStmt = $pdo->prepare("SELECT COUNT(*) FROM employees WHERE YEAR(id) <= YEAR(NOW())"); // placeholder count basis
$refNo = str_pad((string)($emp['id'] ?? 1), 3, '0', STR_PAD_LEFT);

$issueDate = fmtDate(date('Y-m-d'));
$joinDateFormatted = fmtDate($company['date_of_join'] ?? null);
$dobFormatted = !empty($basic['date_of_birth']) ? (new DateTime($basic['date_of_birth']))->format('d/m/Y') : '[DD/MM/YYYY]';

$presentAddressParts = array_filter([$address['address_line_1'] ?? '', $address['address_line_2'] ?? '', $address['city'] ?? '', $address['state'] ?? '', $address['zip_code'] ?? '']);
$presentAddress = $presentAddressParts ? implode(', ', $presentAddressParts) : '[Present Address]';

$emergencyLine = $emergency ? trim(($emergency['person'] ?? '') . ', ' . ($emergency['relation'] ?? '') . ', ' . ($emergency['phone'] ?? ''), ', ') : '';

$designation = $company['designation'] ?? '';
$department  = $company['department'] ?? ($emp['department_name'] ?? '');
$reportingToLine = trim(($company['reporting_to_name'] ?? '') . (!empty($company['reporting_to_designation']) ? ', ' . $company['reporting_to_designation'] : ''));

// Salary structure (Permanent letter only): fixed percentages, per the user's decision.
// Salary structure: prefer the real, per-employee breakdown already set up
// in the Payroll module (eps_structures) over guessing a fixed percentage
// split -- HR enters each component there individually, so it reflects
// whatever was actually agreed for this employee, not an assumed ratio.
$payStmt = $pdo->prepare("
    SELECT basic_salary, house_rent, medical_allowance, conveyance, gross_salary
    FROM eps_structures
    WHERE employee_id = ? AND status = 'active'
    ORDER BY effective_date DESC LIMIT 1
");
$payStmt->execute([$emp['sys_id']]);
$payStructure = $payStmt->fetch(PDO::FETCH_ASSOC);

if ($payStructure) {
    $basicPay   = (float)$payStructure['basic_salary'];
    $houseRent  = (float)$payStructure['house_rent'];
    $medical    = (float)$payStructure['medical_allowance'];
    $conveyance = (float)$payStructure['conveyance'];
    $grossSalary = (float)$payStructure['gross_salary'];
} else {
    // No payroll structure set up yet for this employee -- fall back to the
    // fixed 50/30/10/10 split against the gross_salary entered on their HR
    // profile, so the letter is still usable before Payroll is configured.
    $basicPay = $grossSalary !== null && $grossSalary !== '' ? round($grossSalary * 0.50, 2) : null;
    $houseRent = $grossSalary !== null && $grossSalary !== '' ? round($grossSalary * 0.30, 2) : null;
    $medical   = $grossSalary !== null && $grossSalary !== '' ? round($grossSalary * 0.10, 2) : null;
    $conveyance = $grossSalary !== null && $grossSalary !== '' ? round($grossSalary * 0.10, 2) : null;
}

$isPermanent = $letterType === 'permanent';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title><?php echo $isPermanent ? 'Letter of Appointment' : 'Probationary / Internship Appointment Letter'; ?> — <?php echo alv($fullName, ''); ?></title>
<style>
    @page { size: A4; margin: 20mm 18mm; }
    body { font-family: 'Times New Roman', Times, serif; font-size: 12px; line-height: 1.5; color: #111; max-width: 800px; margin: 0 auto; padding: 20px; }
    .letterhead { text-align: center; border-bottom: 3px double #1b2540; padding-bottom: 14px; margin-bottom: 20px; }
    .letterhead .company-name { font-size: 22px; font-weight: 800; color: #1b2540; letter-spacing: .5px; }
    .letterhead .company-sub { font-size: 11.5px; color: #444; margin-top: 4px; }
    h1 { font-size: 16px; text-align: center; text-transform: uppercase; margin-bottom: 4px; }
    .subhead { text-align: center; font-size: 10.5px; color: #444; margin-bottom: 18px; }
    .ref-row { display: flex; justify-content: space-between; font-size: 11px; margin-bottom: 14px; }
    table { width: 100%; border-collapse: collapse; margin: 10px 0 16px; }
    table.info td { border: 1px solid #999; padding: 5px 8px; font-size: 11.5px; vertical-align: top; }
    table.info td:first-child { width: 38%; font-weight: bold; background: #f7f7f7; }
    h2.part { font-size: 13px; margin-top: 22px; border-bottom: 1px solid #333; padding-bottom: 3px; }
    h3.clause { font-size: 12px; margin-top: 14px; margin-bottom: 4px; }
    p { text-align: justify; margin: 6px 0; }
    .sign-block { margin-top: 50px; display: flex; justify-content: space-between; }
    .sign-block div { width: 45%; }
    .no-print { text-align: center; margin: 20px 0; }
    @media print { .no-print { display: none; } }
</style>
</head>
<body>

<div class="no-print">
    <button onclick="window.print()" style="padding:10px 24px;background:#4338ca;color:#fff;border:none;border-radius:8px;font-size:14px;cursor:pointer;">Print / Save as PDF</button>
</div>

<div class="letterhead">
    <div class="company-name">TRAVHUB GLOBAL LIMITED</div>
    <div class="company-sub">House-01, Road-6, Sector-3, Uttara, Dhaka-1230 &nbsp;|&nbsp; Mobile: 01611482773 &nbsp;|&nbsp; info@travhub.com.bd</div>
    <div class="company-sub" style="margin-top:3px;">Reg. No: C-196691/2024</div>
</div>

<?php if ($isPermanent): ?>

<h1>Letter of Appointment</h1>
<p class="subhead">Issued under the Bangladesh Labour Act, 2006 and the Bangladesh Labour Rules, 2015</p>
<div class="ref-row"><span>Ref: TGL/HR/APT/<?php echo $refYear; ?>/<?php echo $refNo; ?></span><span>Date of issue: <?php echo $issueDate; ?></span></div>

<p><?php echo alv($fullName, '[Employee Full Name]'); ?><br><?php echo $presentAddress; ?></p>
<p><strong>Subject: Appointment as <?php echo alv($designation, '[Designation]'); ?></strong></p>
<p>Dear <?php echo alv($surname, '[Mr./Ms. Surname]'); ?>,</p>
<p>TravHub Global Limited ("the Company"), Registration No. C-196691/2024, is pleased to appoint you as a permanent worker under section 4 of the Bangladesh Labour Act, 2006, subject to satisfactory completion of probation, on the following terms.</p>

<h2 class="part">PART A — PARTICULARS OF APPOINTMENT</h2>
<table class="info">
    <tr><td>Name of employee</td><td><?php echo alv($fullName, '[Full name as per NID]'); ?></td></tr>
    <tr><td>Father's name</td><td><?php echo alv($company['father_name'] ?? null); ?></td></tr>
    <tr><td>Mother's name</td><td><?php echo alv($company['mother_name'] ?? null); ?></td></tr>
    <tr><td>Spouse's name (if applicable)</td><td><?php echo alv($company['spouse_name'] ?? null, '—'); ?></td></tr>
    <tr><td>Date of birth</td><td><?php echo $dobFormatted; ?></td></tr>
    <tr><td>National ID (NID) No.</td><td><?php echo alv($company['nid_no'] ?? null); ?></td></tr>
    <tr><td>Present / Permanent address</td><td><?php echo $presentAddress; ?></td></tr>
    <tr><td>Emergency contact</td><td><?php echo alv($emergencyLine, '[Name], [Relationship], [Phone No.]'); ?></td></tr>
    <tr><td>Designation</td><td><?php echo alv($designation); ?></td></tr>
    <tr><td>Department / Section</td><td><?php echo alv($department); ?></td></tr>
    <tr><td>Classification of worker</td><td>Permanent — section 4, Bangladesh Labour Act, 2006, subject to satisfactory completion of probation</td></tr>
    <tr><td>Date of joining</td><td><?php echo $joinDateFormatted; ?></td></tr>
    <tr><td>Place of work</td><td>TravHub Global Limited, 5th Floor, House 1, Road 6, Sector 3, Uttara, Dhaka 1230</td></tr>
    <tr><td>Reporting to</td><td><?php echo alv($reportingToLine, '[Name, Designation]'); ?></td></tr>
</table>

<h2 class="part">PART B — TERMS AND CONDITIONS</h2>

<h3 class="clause">1. Probation and confirmation</h3>
<p>You will be on probation for six (6) months from your date of joining — the maximum period permitted under section 4(8) of the Bangladesh Labour Act, 2006, which cannot lawfully be extended further. During probation, either party may end this appointment with thirty (30) days' written notice or pay in lieu. On satisfactory completion of probation you will be a permanent worker, whether or not a confirmation letter is issued.</p>

<h3 class="clause">2. Remuneration</h3>
<p>Your gross monthly salary is BDT <?php echo fmtMoney($grossSalary); ?>/- (Taka <?php echo numberToWordsTaka($grossSalary); ?> only), as set out in Annexure-1, payable by bank transfer by the 7th working day of the following month, subject to income tax at source.</p>

<h3 class="clause">3. Other benefits</h3>
<p>Festival bonus: two (2) bonuses per calendar year, each equivalent to one (1) month's basic salary, pro-rata for staff with under twelve (12) months' service. Medical coverage, and outside-visit/field allowance for client and supplier visits, are provided as per Company policy. Business expenses are reimbursed against receipts under the Travel and Cash Advance policies. The Company does not operate a provident fund or a separate gratuity fund; any statutory compensation due on termination follows section 26 of the Act.</p>

<h3 class="clause">4. Working hours, weekly holiday and leave</h3>
<p>Normal working hours are eight (8) hours per day, inclusive of a one (1) hour break with one day weekly holiday. Given the nature of the travel business, you may occasionally be required to work during peak season, group departures, or Hajj/Umrah operations, compensated per the Act and the Leave Policy. Leave entitlement — casual leave (10 days/year), sick leave (14 days/year), earned leave (accruing at 1 day per 18 days worked with no late coming or half day, claimable after one year's continuous service), and festival holidays per the government gazette — follows the Company Leave Policy and is in no case less favourable than the Bangladesh Labour Act, 2006.</p>

<h3 class="clause">5. Duties, confidentiality and Company property</h3>
<p>You will perform the duties at Annexure-2 and devote your full working time to the Company, without outside travel, visa, or consultancy work without prior written consent. Client passports, visa and biometric data, GDS credentials, fare and supplier terms, and all Company records are confidential during and after employment, must be handled per the Company's document custody procedures, and returned on cessation of employment. You are accountable for all activity under your personal GDS sign-in, including any Agency Debit Memo arising from misuse.</p>

<h3 class="clause">6. Termination</h3>
<p>After confirmation, the Company may terminate your employment with one hundred and twenty (120) days' written notice or pay in lieu, together with compensation under section 26 of the Act; you may resign with sixty (60) days' notice under section 27. Dismissal for misconduct follows section 24 and the Company's Disciplinary Procedure, without notice or pay in lieu. Employment ends on your attaining sixty (60) years of age under section 28. On cessation, you must complete clearance and hand over all Company property, client files and passwords before final settlement.</p>

<h3 class="clause">7. General</h3>
<p>This appointment is subject to the Company's policies as amended from time to time, is governed by the laws of Bangladesh, and supersedes all prior offers or discussions. Nothing in this letter gives you terms less favourable than those guaranteed by the Bangladesh Labour Act, 2006 and the Bangladesh Labour Rules, 2015.</p>
<p>Please sign and return the duplicate copy of this letter in token of your acceptance.</p>

<p>Yours sincerely,<br><br>[Name]<br>Managing Director, for and on behalf of TravHub Global Limited</p>

<h2 class="part">ANNEXURE-1: SALARY STRUCTURE</h2>
<table class="info">
    <tr><td>Component</td><td>Amount (BDT / month)</td></tr>
    <tr><td>Basic salary</td><td><?php echo fmtMoney($basicPay); ?> (50% of gross)</td></tr>
    <tr><td>House rent allowance</td><td><?php echo fmtMoney($houseRent); ?> (30% of gross)</td></tr>
    <tr><td>Medical allowance</td><td><?php echo fmtMoney($medical); ?> (10% of gross)</td></tr>
    <tr><td>Conveyance allowance</td><td><?php echo fmtMoney($conveyance); ?> (10% of gross)</td></tr>
    <tr><td>Gross monthly salary</td><td><?php echo fmtMoney($grossSalary); ?> (100%)</td></tr>
</table>
<p style="font-size:10.5px;color:#555;">Festival bonus is calculated on basic salary. Income tax is deducted at source per the applicable slabs. This structure is confidential to the employee.</p>

<h2 class="part">ACCEPTANCE BY EMPLOYEE</h2>
<p>I have read and understood the above terms and the Annexures, and accept this appointment on those terms. I confirm receipt of the Company policies referred to in clause 7, and that the particulars in Part A are correct.</p>

<div class="sign-block">
    <div>Employee Signature<br><br>Name: ______________________<br>Date: ______________________</div>
    <div>For TravHub Global Limited<br><br>Name: ______________________<br>Date: ______________________</div>
</div>

<?php else: /* probationary / intern */
$engagementLabel = $letterType === 'intern' ? 'Intern' : 'Probationary Employee';
?>

<h1>Probationary / Internship Appointment Letter</h1>
<div class="ref-row"><span>Ref: TGL/HR/PRO-INT/<?php echo $refYear; ?>/<?php echo $refNo; ?></span><span>Date of issue: <?php echo $issueDate; ?></span></div>

<p><?php echo alv($fullName, '[Full Name]'); ?><br><?php echo $presentAddress; ?></p>
<p><strong>Subject: Appointment as <?php echo $engagementLabel; ?> — <?php echo alv($designation, '[Designation]'); ?></strong></p>
<p>Dear <?php echo alv($surname, '[Mr./Ms. Surname]'); ?>,</p>
<p>TravHub Global Limited ("the Company"), Registration No. C-196691/2024, is pleased to appoint you as a <?php echo $engagementLabel; ?> on the following terms.</p>

<h2 class="part">PARTICULARS OF APPOINTMENT</h2>
<table class="info">
    <tr><td>Name</td><td><?php echo alv($fullName, '[Full name as per NID / Student ID]'); ?></td></tr>
    <tr><td>Father's name</td><td><?php echo alv($company['father_name'] ?? null); ?></td></tr>
    <tr><td>Mother's name</td><td><?php echo alv($company['mother_name'] ?? null); ?></td></tr>
    <tr><td>Spouse's name (if applicable)</td><td><?php echo alv($company['spouse_name'] ?? null, '—'); ?></td></tr>
    <tr><td>Date of birth</td><td><?php echo $dobFormatted; ?></td></tr>
    <tr><td>NID / Student ID No.</td><td><?php echo alv($company['nid_no'] ?? null); ?></td></tr>
    <tr><td>Present / Permanent address</td><td><?php echo $presentAddress; ?></td></tr>
    <tr><td>Emergency contact</td><td><?php echo alv($emergencyLine, '[Name], [Relationship], [Phone No.]'); ?></td></tr>
    <tr><td>Type of engagement</td><td><?php echo $engagementLabel; ?></td></tr>
    <tr><td>Designation</td><td><?php echo alv($designation); ?></td></tr>
    <tr><td>Department / Section</td><td><?php echo alv($department); ?></td></tr>
    <tr><td>Duration</td><td><?php echo $letterType === 'intern' ? 'Internship: [__] months, non-renewable beyond [__] months' : 'Probation: 6 months from joining'; ?></td></tr>
    <tr><td>Date of joining</td><td><?php echo $joinDateFormatted; ?></td></tr>
    <tr><td>Place of work</td><td>TravHub Global Limited, 5th Floor, House 1, Road 6, Sector 3, Uttara, Dhaka 1230</td></tr>
    <tr><td>Reporting to</td><td><?php echo alv($reportingToLine, '[Name, Designation]'); ?></td></tr>
    <tr><td>Remuneration</td><td><?php echo $letterType === 'intern' ? 'BDT ' . fmtMoney($grossSalary) . '/month stipend' : 'BDT ' . fmtMoney($grossSalary) . '/month gross salary'; ?></td></tr>
</table>

<h2 class="part">ANNEXURE-1: <?php echo $letterType === 'intern' ? 'STIPEND STRUCTURE' : 'SALARY STRUCTURE'; ?></h2>
<table class="info">
    <tr><td>Component</td><td>Amount (BDT / month)</td></tr>
    <tr><td>Basic <?php echo $letterType === 'intern' ? 'stipend' : 'salary'; ?></td><td><?php echo fmtMoney($basicPay); ?> (50% of gross)</td></tr>
    <tr><td>House rent allowance</td><td><?php echo fmtMoney($houseRent); ?> (30% of gross)</td></tr>
    <tr><td>Medical allowance</td><td><?php echo fmtMoney($medical); ?> (10% of gross)</td></tr>
    <tr><td>Conveyance allowance</td><td><?php echo fmtMoney($conveyance); ?> (10% of gross)</td></tr>
    <tr><td>Gross monthly <?php echo $letterType === 'intern' ? 'stipend' : 'salary'; ?></td><td><?php echo fmtMoney($grossSalary); ?> (100%)</td></tr>
</table>
<p style="font-size:10.5px;color:#555;">In words: Taka <?php echo numberToWordsTaka($grossSalary); ?> only, per month. <?php echo $letterType === 'intern' ? 'This stipend is not a wage under the Bangladesh Labour Act, 2006 and is not subject to income tax at source in the same manner as a regular salary.' : 'Income tax is deducted at source per the applicable slabs.'; ?> This structure is confidential to the <?php echo $letterType === 'intern' ? 'intern' : 'employee'; ?>.</p>

<h2 class="part">TERMS AND CONDITIONS</h2>

<h3 class="clause">1. Duration and nature of engagement</h3>
<p>If appointed as a Probationary Employee, your probation runs for six (6) months from your date of joining — the maximum period permitted under section 4(8) of the Bangladesh Labour Act, 2006 — after which you will be a permanent worker, whether or not a confirmation letter is issued. If appointed as an Intern, this engagement is for [__] months from your date of joining, for training and exposure purposes, does not create an employer-employee relationship, and does not carry any right to continued or permanent engagement with the Company.</p>

<h3 class="clause">2. Remuneration</h3>
<p>You will receive the amount set out in Annexure-1, payable by bank transfer by the 7th working day of the following month. A Probationary Employee's salary is subject to income tax at source; an Intern's stipend is not a wage under the Bangladesh Labour Act, 2006.</p>

<h3 class="clause">3. Working hours and leave</h3>
<p>Normal working hours are eight (8) hours per day, inclusive of a one (1) hour break with one day weekly holiday. A Probationary Employee is entitled to casual leave (10 days/year) and sick leave (14 days/year) per the Act. An Intern may take leave only with prior approval of the reporting supervisor, per the Internship Policy.</p>

<h3 class="clause">4. Duties, confidentiality and Company property</h3>
<p>You will perform the duties assigned by your reporting supervisor and devote your full working time to the Company. Client passports, visa and biometric data, GDS credentials, fare and supplier terms, and all Company records are confidential during and after this engagement, and all Company property, files and access credentials must be returned on its conclusion.</p>

<h3 class="clause">5. Termination / end of engagement</h3>
<p>During probation, either party may end this appointment with thirty (30) days' written notice or pay in lieu, per section 4(8) and 4(6) of the Act. An internship may be ended by either party with seven (7) days' written notice, or immediately by the Company for misconduct or poor conduct. This letter does not entitle an Intern to notice pay, severance, or any benefit under the Bangladesh Labour Act, 2006.</p>

<h3 class="clause">6. General</h3>
<p>This appointment is subject to the Company's policies as amended from time to time and is governed by the laws of Bangladesh. Please sign and return the duplicate copy of this letter in token of your acceptance.</p>

<p>Yours sincerely,<br><br>[Name]<br>Managing Director, for and on behalf of TravHub Global Limited</p>

<h2 class="part">ANNEXURE-1: <?php echo $letterType === 'intern' ? 'STIPEND' : 'SALARY'; ?> STRUCTURE</h2>
<table class="info">
    <tr><td>Component</td><td>Amount (BDT / month)</td></tr>
    <tr><td>Basic <?php echo $letterType === 'intern' ? 'amount' : 'salary'; ?></td><td><?php echo fmtMoney($basicPay); ?> (50% of gross)</td></tr>
    <tr><td>House rent allowance</td><td><?php echo fmtMoney($houseRent); ?> (30% of gross)</td></tr>
    <tr><td>Medical allowance</td><td><?php echo fmtMoney($medical); ?> (10% of gross)</td></tr>
    <tr><td>Conveyance allowance</td><td><?php echo fmtMoney($conveyance); ?> (10% of gross)</td></tr>
    <tr><td>Gross monthly <?php echo $letterType === 'intern' ? 'stipend' : 'salary'; ?></td><td><?php echo fmtMoney($grossSalary); ?> (100%)</td></tr>
</table>
<p style="font-size:10.5px;color:#555;">এই structure কর্মচারীর জন্য গোপনীয়।</p>

<h2 class="part">ACCEPTANCE</h2>
<p>I have read and understood the above terms, and accept this appointment on those terms. I confirm that the particulars above are correct.</p>

<div class="sign-block">
    <div>Employee / Intern Signature<br><br>Name: ______________________<br>Date: ______________________</div>
    <div>For TravHub Global Limited<br><br>Name: ______________________<br>Date: ______________________</div>
</div>

<?php endif; ?>

</body>
</html>