<?php
// PATH: /pages/generate-noc.php
//
// Generates a print-ready No Objection Certificate (NOC) for one employee.
// HR document — login required, no accounting permission needed.
//
// GET params:
//   employee_id  — employees.sys_id
//   purpose      — visa | bank | education | travel | govt | resigned | other
//   other_reason — free text when purpose=other
//   date_from    — optional (YYYY-MM-DD), shown as "from ... to ..."
//   date_to      — optional (YYYY-MM-DD)
//   (date_from/to are ignored when purpose=resigned)

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

function alv($v, $fallback = '[                    ]') { $v = trim((string)($v ?? '')); return $v !== '' ? htmlspecialchars($v, ENT_QUOTES, 'UTF-8') : $fallback; }
function fmtDate($v) { if (!$v) return null; try { return (new DateTime($v))->format('d F Y'); } catch (Exception $e) { return null; } }

$purpose    = $_GET['purpose']      ?? '';
$otherText  = trim($_GET['other_reason'] ?? '');
$dateFrom   = $_GET['date_from']    ?? '';
$dateTo     = $_GET['date_to']      ?? '';
$isResigned = ($purpose === 'resigned');

$fullName    = $emp['name'] ?? '';
$designation = $company['designation'] ?? '';
$department  = $company['department'] ?? ($emp['department_name'] ?? '');
$joinDate    = fmtDate($company['date_of_join'] ?? null);
$issueDate   = fmtDate(date('Y-m-d'));

// Reference number
$refNo   = 'TH/NOC/' . date('Y') . '/' . str_pad((string)($emp['id'] ?? 1), 3, '0', STR_PAD_LEFT);

// Purpose sentence map
$purposeMap = [
    'visa'       => 'obtain a visa from the concerned Embassy/Consulate',
    'bank'       => 'submit to the concerned bank or financial institution',
    'education'  => 'apply for higher education or academic programme abroad',
    'travel'     => 'travel abroad for personal or business purposes',
    'govt'       => 'submit to the concerned government office or embassy',
    'resigned'   => '', // handled separately in body
    'other'      => $otherText ?: 'the stated purpose',
];
$purposeText = $purposeMap[$purpose] ?? 'the stated purpose';

// Date range clause (non-resigned only)
$dateClause = '';
if (!$isResigned && $dateFrom && $dateTo) {
    $f = fmtDate($dateFrom); $t = fmtDate($dateTo);
    if ($f && $t) $dateClause = " for the period from <b>{$f}</b> to <b>{$t}</b>";
} elseif (!$isResigned && $dateFrom) {
    $f = fmtDate($dateFrom);
    if ($f) $dateClause = " from <b>{$f}</b>";
}

// Last working date for resigned (use today as fallback if not in DB)
$lastWorkingDate = fmtDate($company['last_working_date'] ?? date('Y-m-d')) ?? $issueDate;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>NOC — <?php echo alv($fullName, ''); ?></title>
<style>
    * { box-sizing: border-box; margin: 0; padding: 0; }
    @page { size: A4; margin: 20mm 22mm; }
    body { font-family: 'Times New Roman', Times, serif; font-size: 13px; line-height: 1.75; color: #111; background: #fff; max-width: 780px; margin: 0 auto; padding: 30px 20px; }

    .letterhead { text-align: center; border-bottom: 3px double #1b2540; padding-bottom: 14px; margin-bottom: 20px; }
    .letterhead .company-name { font-size: 22px; font-weight: 800; color: #1b2540; letter-spacing: .5px; }
    .letterhead .company-sub { font-size: 11.5px; color: #444; margin-top: 4px; }

    .ref-row { display: flex; justify-content: space-between; font-size: 11.5px; color: #333; margin-bottom: 24px; }

    h2.title { text-align: center; text-decoration: underline; font-size: 15px; font-weight: 800; margin-bottom: 22px; text-transform: uppercase; letter-spacing: .5px; }

    p { margin-bottom: 14px; text-align: justify; }
    b { font-weight: 700; }

    table.details { width: 100%; border-collapse: collapse; margin: 18px 0; font-size: 12.5px; }
    table.details td { padding: 6px 10px; vertical-align: top; }
    table.details td:first-child { width: 180px; font-weight: 700; color: #1b2540; }
    table.details td:nth-child(2) { width: 14px; }
    table.details tr:nth-child(odd) td { background: #f7f8fb; }

    .sign-block { margin-top: 60px; display: flex; justify-content: space-between; font-size: 12.5px; }
    .sign-box { text-align: center; width: 200px; }
    .sign-box .line { border-top: 1px solid #333; margin-bottom: 6px; }
    .sign-box .label { font-weight: 700; font-size: 12px; }
    .sign-box .sub { font-size: 11px; color: #555; }

    .footer-band { margin-top: 40px; border-top: 1px solid #ccc; padding-top: 10px; text-align: center; font-size: 10.5px; color: #666; }

    .no-print { text-align: center; margin-bottom: 20px; }
    @media print { .no-print { display: none; } body { padding: 0; } }
</style>
</head>
<body>

<div class="no-print">
    <button onclick="window.print()" style="padding:9px 24px;background:#1b2540;color:#3ecf8e;border:none;border-radius:8px;font-size:13px;font-weight:600;cursor:pointer;">🖨 Print / Save as PDF</button>
</div>

<!-- Letterhead -->
<div class="letterhead">
    <div class="company-name">TravHub Global Limited</div>
    <div class="company-sub">House-01, Road-6, Sector-3, Uttara, Dhaka-1230 &nbsp;|&nbsp; Mobile: 01611482773 &nbsp;|&nbsp; info@travhub.com.bd</div>
    <div class="company-sub" style="margin-top:3px;">Reg. No: C-196691/2024</div>
</div>

<div class="ref-row">
    <span>Ref: <b><?php echo alv($refNo); ?></b></span>
    <span>Date: <b><?php echo alv($issueDate, date('d F Y')); ?></b></span>
</div>

<h2 class="title">No Objection Certificate</h2>

<p>To Whom It May Concern,</p>

<?php if ($isResigned): ?>
<p>
    This is to certify that <b><?php echo alv($fullName); ?></b> was employed at <b>TravHub Global Limited</b>
    as <b><?php echo alv($designation, 'Staff'); ?></b><?php echo $department ? ', ' . alv($department) . ' Department' : ''; ?>,
    with effect from <b><?php echo alv($joinDate, '[Date of Joining]'); ?></b>.
</p>
<p>
    The above-mentioned employee has since resigned from the organization and their last working date was
    <b><?php echo alv($lastWorkingDate); ?></b>.
    TravHub Global Limited has <b>no objection</b> to their seeking employment elsewhere or pursuing any
    activity of their choice after the cessation of their employment with us.
</p>
<p>
    During their tenure with us, they carried out their duties diligently and we wish them the very best
    in their future endeavours.
</p>
<?php else: ?>
<p>
    This is to certify that <b><?php echo alv($fullName); ?></b> is currently employed at
    <b>TravHub Global Limited</b> as <b><?php echo alv($designation, 'Staff'); ?></b><?php echo $department ? ', ' . alv($department) . ' Department' : ''; ?>,
    with effect from <b><?php echo alv($joinDate, '[Date of Joining]'); ?></b>.
</p>
<p>
    The Company has <b>no objection</b> to <?php echo alv($fullName, 'the above employee'); ?> proceeding
    to <?php echo $purposeText; ?><?php echo $dateClause; ?>.
    This certificate is being issued at the request of the employee for the purpose stated above and is
    not to be used for any other reason.
</p>
<?php endif; ?>

<p>We wish them all the best.</p>

<!-- Employee details table -->
<table class="details">
    <tr><td>Employee Name</td><td>:</td><td><b><?php echo alv($fullName); ?></b></td></tr>
    <tr><td>Employee ID</td><td>:</td><td><?php echo alv($emp['sys_id']); ?></td></tr>
    <tr><td>Designation</td><td>:</td><td><?php echo alv($designation); ?></td></tr>
    <?php if ($department): ?>
    <tr><td>Department</td><td>:</td><td><?php echo alv($department); ?></td></tr>
    <?php endif; ?>
    <tr><td>Date of Joining</td><td>:</td><td><?php echo alv($joinDate, '—'); ?></td></tr>
    <?php if ($isResigned): ?>
    <tr><td>Last Working Date</td><td>:</td><td><?php echo alv($lastWorkingDate, '—'); ?></td></tr>
    <?php endif; ?>
</table>

<!-- Signature block -->
<div class="sign-block">
    <div class="sign-box">
        <div class="line"></div>
        <div class="label">Authorized Signatory</div>
        <div class="sub">TravHub Global Limited</div>
    </div>
    <div class="sign-box">
        <div class="line"></div>
        <div class="label">Managing Director</div>
        <div class="sub">TravHub Global Limited</div>
    </div>
</div>

<div class="footer-band">
    This certificate is computer-generated and valid without a handwritten signature unless required by the receiving authority.
</div>

</body>
</html>