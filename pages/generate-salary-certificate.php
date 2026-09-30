<?php
// PATH: /pages/generate-salary-certificate.php
//
// Generates a print-ready Salary Certificate for one employee. Same data
// source and conventions as generate-appointment-letter.php: HR document,
// login-only (no accounting permission), employees.company_related_info +
// basic_info/address for the fields.

include_once('./authenticate.php');
require_once __DIR__ . '/../server/db_connection.php';
require_once __DIR__ . '/../server/hrm_permissions.php';

$employeeId = $_GET['employee_id'] ?? '';
if (!$employeeId) {
    die('<div style="font-family:Arial,sans-serif;padding:60px;text-align:center;color:#666;"><h2>Missing employee</h2></div>');
}
requireHrmOrSelf($pdo, $employeeId, 'hr_docs_generate');

$stmt = $pdo->prepare("SELECT * FROM employees WHERE sys_id = ? LIMIT 1");
$stmt->execute([$employeeId]);
$emp = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$emp) {
    die('<div style="font-family:Arial,sans-serif;padding:60px;text-align:center;color:#666;"><h2>Employee not found</h2></div>');
}

$company = json_decode($emp['company_related_info'] ?? '{}', true) ?: [];
$address = json_decode($emp['address'] ?? '{}', true) ?: [];

function alv($v, $fallback = '[                    ]') { $v = trim((string)($v ?? '')); return $v !== '' ? htmlspecialchars($v, ENT_QUOTES, 'UTF-8') : $fallback; }
function fmtDate($v) { if (!$v) return '[DD Month YYYY]'; try { return (new DateTime($v))->format('d F Y'); } catch (Exception $e) { return htmlspecialchars($v); } }
function fmtMoney($v) { return $v !== null && $v !== '' ? number_format((float)$v, 2) : '[                ]'; }
function numberToWordsTaka($amount) {
    if (!is_numeric($amount)) return '[                                        ]';
    $amount = (int)round((float)$amount);
    if ($amount === 0) return 'Zero';
    $ones = ['', 'One','Two','Three','Four','Five','Six','Seven','Eight','Nine','Ten','Eleven','Twelve','Thirteen','Fourteen','Fifteen','Sixteen','Seventeen','Eighteen','Nineteen'];
    $tens = ['', '', 'Twenty','Thirty','Forty','Fifty','Sixty','Seventy','Eighty','Ninety'];
    $twoDigits = function($n) use ($ones, $tens) { if ($n < 20) return $ones[$n]; return trim($tens[intdiv($n, 10)] . ' ' . $ones[$n % 10]); };
    $threeDigits = function($n) use ($twoDigits, $ones) { $s = ''; if ($n >= 100) { $s .= $ones[intdiv($n, 100)] . ' Hundred '; $n %= 100; } $s .= $twoDigits($n); return trim($s); };
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
$designation = $company['designation'] ?? '';
$department  = $company['department'] ?? ($emp['department_name'] ?? '');
$grossSalary = $company['gross_salary'] ?? null;
$joinDateFormatted = fmtDate($company['date_of_join'] ?? null);
$issueDate = fmtDate(date('Y-m-d'));
$refNo = str_pad((string)($emp['id'] ?? 1), 3, '0', STR_PAD_LEFT);
$refYear = date('Y');

// Optional period filter (YYYY-MM from <input type="month">)
$periodFrom = $_GET['date_from'] ?? '';
$periodTo   = $_GET['date_to']   ?? '';
function fmtMonth($ym) {
    if (!$ym) return null;
    try { return (new DateTime($ym . '-01'))->format('F Y'); } catch (Exception $e) { return null; }
}
$periodFromLabel = fmtMonth($periodFrom);
$periodToLabel   = fmtMonth($periodTo);
$periodLabel = '';
if ($periodFromLabel && $periodToLabel)   $periodLabel = $periodFromLabel . ' – ' . $periodToLabel;
elseif ($periodFromLabel)                 $periodLabel = 'from ' . $periodFromLabel;
elseif ($periodToLabel)                   $periodLabel = 'up to ' . $periodToLabel;

$presentAddressParts = array_filter([$address['address_line_1'] ?? '', $address['address_line_2'] ?? '', $address['city'] ?? '', $address['state'] ?? '', $address['zip_code'] ?? '']);
$presentAddress = $presentAddressParts ? implode(', ', $presentAddressParts) : '[Present Address]';

// Salary structure: if a date range was given AND multiple eps_structures
// records exist within that range (salary revised mid-period), we collect
// all of them and show Option B — one breakdown table per sub-period.
// Otherwise we fall back to the single most-recent active record.
$salaryPeriods = []; // array of sub-period rows for Option B

if ($periodFrom && $periodTo) {
    // Fetch every active structure whose effective_date falls ON OR BEFORE
    // periodTo and is the most recent record before each month's start.
    // Simpler approach: fetch all structures in the date window + the one
    // immediately before, then deduplicate into sub-period bands.
    $fromDate = $periodFrom . '-01';
    $toDate   = $periodTo   . '-01';

    // All active records effective on or before the end of the requested range
    $payStmt = $pdo->prepare("
        SELECT basic_salary, house_rent, medical_allowance, conveyance, gross_salary, effective_date
        FROM eps_structures
        WHERE employee_id = ? AND effective_date <= ?
        ORDER BY effective_date ASC
    ");
    $payStmt->execute([$emp['sys_id'], $toDate]);
    $allStructures = $payStmt->fetchAll(PDO::FETCH_ASSOC);

    // Keep only the last one that was effective before/at the start of the
    // period plus all those that fell strictly inside the period.
    $inPeriod = [];
    $beforePeriod = null;
    foreach ($allStructures as $s) {
        if ($s['effective_date'] < $fromDate) {
            $beforePeriod = $s; // keeps rolling; last one wins
        } else {
            $inPeriod[] = $s;
        }
    }
    // Build the full ordered set that was ever "active" within the period
    $orderedSet = [];
    if ($beforePeriod) $orderedSet[] = $beforePeriod;
    foreach ($inPeriod as $s) $orderedSet[] = $s;

    if (count($orderedSet) > 1) {
        // Build sub-period bands: each entry is valid from its effective_date
        // (or period start, whichever is later) until the day before the next.
        function fmtPeriodMonth($ym) {
            try { return (new DateTime($ym . '-01'))->format('F Y'); } catch (Exception $e) { return $ym; }
        }
        for ($i = 0; $i < count($orderedSet); $i++) {
            $s = $orderedSet[$i];
            // Sub-period start: max(effective_date, fromDate)
            $subFrom = $s['effective_date'] < $fromDate ? $fromDate : $s['effective_date'];
            // Sub-period end: day before next record's effective_date, or toDate
            if (isset($orderedSet[$i + 1])) {
                $nextEff   = new DateTime($orderedSet[$i + 1]['effective_date']);
                $subToDate = clone $nextEff;
                $subToDate->modify('-1 month');
                // Format as YYYY-MM-01 then reformat to YYYY-MM
                $subToLabel = $subToDate->format('F Y');
            } else {
                $subToLabel = fmtPeriodMonth(substr($toDate, 0, 7));
            }
            $subFromLabel = fmtPeriodMonth(substr($subFrom, 0, 7));
            $salaryPeriods[] = [
                'from_label' => $subFromLabel,
                'to_label'   => $subToLabel,
                'basic'      => (float)$s['basic_salary'],
                'house_rent' => (float)$s['house_rent'],
                'medical'    => (float)$s['medical_allowance'],
                'conveyance' => (float)$s['conveyance'],
                'gross'      => (float)$s['gross_salary'],
            ];
        }
        // Use the final (most recent) structure as the primary values for the
        // letter body text (fallback for other parts of the template)
        $last = end($orderedSet);
        $basicPay    = (float)$last['basic_salary'];
        $houseRent   = (float)$last['house_rent'];
        $medical     = (float)$last['medical_allowance'];
        $conveyance  = (float)$last['conveyance'];
        $grossSalary = (float)$last['gross_salary'];
    } else {
        // Only one structure covers the whole period — treat as single
        $single = $orderedSet[0] ?? null;
        if ($single) {
            $basicPay    = (float)$single['basic_salary'];
            $houseRent   = (float)$single['house_rent'];
            $medical     = (float)$single['medical_allowance'];
            $conveyance  = (float)$single['conveyance'];
            $grossSalary = (float)$single['gross_salary'];
        } else {
            // no eps record at all — fallback to company_related_info ratios
            $basicPay   = $grossSalary !== null && $grossSalary !== '' ? round($grossSalary * 0.50, 2) : null;
            $houseRent  = $grossSalary !== null && $grossSalary !== '' ? round($grossSalary * 0.30, 2) : null;
            $medical    = $grossSalary !== null && $grossSalary !== '' ? round($grossSalary * 0.10, 2) : null;
            $conveyance = $grossSalary !== null && $grossSalary !== '' ? round($grossSalary * 0.10, 2) : null;
        }
    }
} else {
    // No date range — just show the most recent active structure
    $payStmt = $pdo->prepare("
        SELECT basic_salary, house_rent, medical_allowance, conveyance, gross_salary
        FROM eps_structures
        WHERE employee_id = ? AND status = 'active'
        ORDER BY effective_date DESC LIMIT 1
    ");
    $payStmt->execute([$emp['sys_id']]);
    $payStructure = $payStmt->fetch(PDO::FETCH_ASSOC);

    if ($payStructure) {
        $basicPay    = (float)$payStructure['basic_salary'];
        $houseRent   = (float)$payStructure['house_rent'];
        $medical     = (float)$payStructure['medical_allowance'];
        $conveyance  = (float)$payStructure['conveyance'];
        $grossSalary = (float)$payStructure['gross_salary'];
    } else {
        $basicPay   = $grossSalary !== null && $grossSalary !== '' ? round($grossSalary * 0.50, 2) : null;
        $houseRent  = $grossSalary !== null && $grossSalary !== '' ? round($grossSalary * 0.30, 2) : null;
        $medical    = $grossSalary !== null && $grossSalary !== '' ? round($grossSalary * 0.10, 2) : null;
        $conveyance = $grossSalary !== null && $grossSalary !== '' ? round($grossSalary * 0.10, 2) : null;
    }
}

// Employment status line: permanent workers get a plain "employed with us
// since", probationary/interns get a status note -- read from the same
// employment_type value store.php already saves.
$employmentType = $company['employment_type'] ?? 'permanent';
$statusLine = $employmentType === 'permanent'
    ? 'is a permanent employee of this organization'
    : ($employmentType === 'intern' ? 'is currently engaged as an Intern with this organization' : 'is currently a Probationary Employee of this organization');
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Salary Certificate — <?php echo alv($fullName, ''); ?></title>
<style>
    @page { size: A4; margin: 25mm 20mm; }
    body { font-family: 'Times New Roman', Times, serif; font-size: 12.5px; line-height: 1.7; color: #111; max-width: 780px; margin: 0 auto; padding: 20px; }
    .letterhead { text-align: center; border-bottom: 3px double #1b2540; padding-bottom: 14px; margin-bottom: 20px; }
    .letterhead .company-name { font-size: 22px; font-weight: 800; color: #1b2540; letter-spacing: .5px; }
    .letterhead .company-sub { font-size: 11.5px; color: #444; margin-top: 4px; }
    h2.title { text-align: center; text-decoration: underline; font-size: 15px; margin-bottom: 24px; text-transform: uppercase; }
    .ref-row { display: flex; justify-content: space-between; font-size: 11px; margin-bottom: 18px; }
    p { text-align: justify; margin: 10px 0; }
    table.salary { width: 60%; margin: 14px auto; border-collapse: collapse; }
    table.salary td { border: 1px solid #999; padding: 6px 12px; font-size: 12px; }
    table.salary td:first-child { font-weight: bold; background: #f7f7f7; }
    table.salary tr:last-child td { font-weight: bold; background: #f0f0f0; }
    .sign-block { margin-top: 60px; }
    .no-print { text-align: center; margin: 20px 0; }
    @media print { .no-print { display: none; } }
</style>
</head>
<body>

<div class="no-print">
    <button onclick="window.print()" style="padding:10px 24px;background:#0d9488;color:#fff;border:none;border-radius:8px;font-size:14px;cursor:pointer;">Print / Save as PDF</button>
</div>

<div class="letterhead">
    <div class="company-name">TRAVHUB GLOBAL LIMITED</div>
    <div class="company-sub">House-01, Road-6, Sector-3, Uttara, Dhaka-1230 &nbsp;|&nbsp; Mobile: 01611482773 &nbsp;|&nbsp; info@travhub.com.bd</div>
    <div class="company-sub" style="margin-top:3px;">Reg. No: C-196691/2024</div>
</div>

<h2 class="title">Salary Certificate<?php echo $periodLabel ? ' (' . htmlspecialchars($periodLabel, ENT_QUOTES, 'UTF-8') . ')' : ''; ?></h2>
<div class="ref-row"><span>Ref: TGL/HR/SAL-CERT/<?php echo $refYear; ?>/<?php echo $refNo; ?></span><span>Date: <?php echo $issueDate; ?></span></div>

<p><strong>TO WHOM IT MAY CONCERN</strong></p>

<p>This is to certify that <strong><?php echo alv($fullName, '[Employee Full Name]'); ?></strong>, holding the position of <strong><?php echo alv($designation); ?></strong> in the <?php echo alv($department); ?> department, <?php echo $statusLine; ?>, having joined on <?php echo $joinDateFormatted; ?>.</p>

<p>As per our records, <?php echo alv($fullName, 'the above-named employee'); ?>'s gross monthly salary<?php echo $periodLabel ? ' for the period <strong>' . htmlspecialchars($periodLabel, ENT_QUOTES, 'UTF-8') . '</strong>' : ''; ?> is as follows:</p>

<?php if (count($salaryPeriods) > 1): ?>
    <?php foreach ($salaryPeriods as $sp): ?>
    <p style="margin:16px 0 4px; font-weight:bold; font-size:12px; color:#333;">
        <?php echo htmlspecialchars($sp['from_label'], ENT_QUOTES, 'UTF-8');
              if ($sp['from_label'] !== $sp['to_label']) echo ' – ' . htmlspecialchars($sp['to_label'], ENT_QUOTES, 'UTF-8'); ?>
    </p>
    <table class="salary">
        <tr><td>Basic Salary</td><td><?php echo fmtMoney($sp['basic']); ?></td></tr>
        <tr><td>House Rent Allowance</td><td><?php echo fmtMoney($sp['house_rent']); ?></td></tr>
        <tr><td>Medical Allowance</td><td><?php echo fmtMoney($sp['medical']); ?></td></tr>
        <tr><td>Conveyance Allowance</td><td><?php echo fmtMoney($sp['conveyance']); ?></td></tr>
        <tr><td>Gross Monthly Salary</td><td>BDT <?php echo fmtMoney($sp['gross']); ?></td></tr>
    </table>
    <p style="text-align:center; font-size:11px; color:#555;">In words: Taka <?php echo numberToWordsTaka($sp['gross']); ?> only, per month.</p>
    <?php endforeach; ?>
<?php else: ?>
<table class="salary">
    <tr><td>Basic Salary</td><td><?php echo fmtMoney($basicPay); ?></td></tr>
    <tr><td>House Rent Allowance</td><td><?php echo fmtMoney($houseRent); ?></td></tr>
    <tr><td>Medical Allowance</td><td><?php echo fmtMoney($medical); ?></td></tr>
    <tr><td>Conveyance Allowance</td><td><?php echo fmtMoney($conveyance); ?></td></tr>
    <tr><td>Gross Monthly Salary</td><td>BDT <?php echo fmtMoney($grossSalary); ?></td></tr>
</table>
<p style="text-align:center; font-size:11.5px; color:#444;">In words: Taka <?php echo numberToWordsTaka($grossSalary); ?> only, per month.</p>
<?php endif; ?>

<p>This certificate is issued at the request of the concerned employee for whatever purpose it may serve, and is valid as of the date of issue mentioned above.</p>

<p>We wish <?php echo alv($fullName, 'the above-named employee'); ?> continued success.</p>

<div class="sign-block">
    <p>Yours sincerely,</p>
    <br><br>
    <p>______________________________<br>
    [Name]<br>
    Head of Human Resources<br>
    TravHub Global Limited</p>
</div>

</body>
</html>