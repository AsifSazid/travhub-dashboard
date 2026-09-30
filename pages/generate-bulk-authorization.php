<?php
// PATH: /pages/generate-bulk-authorization.php
//
// Bulk salary disbursement authorization letter.
// Lists ALL employees whose eps_structures record is currently 'active',
// with their net salary, for the MD / Accounts signatory to print and sign.
//
// GET params:
//   month   YYYY-MM  (optional, defaults to current month)

include_once('./authenticate.php');
require_once __DIR__ . '/../server/db_connection.php';
require_once __DIR__ . '/../server/hrm_permissions.php';
requireHrm($pdo, 'payroll_disburse', false);

$monthParam = $_GET['month'] ?? date('Y-m');
try {
    $monthDt    = new DateTime($monthParam . '-01');
    $monthLabel = $monthDt->format('F Y');
} catch (Exception $e) {
    $monthLabel = date('F Y');
}

$issueDate = (new DateTime())->format('d F Y');
$refNo     = 'TGL/FIN/DISBURSE/' . date('Y') . '/' . str_pad(date('n'), 2, '0', STR_PAD_LEFT);

// Fetch the one active EPS record per employee (latest effective_date wins).
// eps_structures.employee_name is denormalised; join employees for designation.
$stmt = $pdo->query("
    SELECT
        e.sys_id          AS emp_sys_id,
        e.name            AS emp_name,
        JSON_UNQUOTE(JSON_EXTRACT(e.company_related_info, '$.designation')) AS designation,
        eps.net_salary,
        eps.gross_salary
    FROM eps_structures eps
    INNER JOIN employees e ON e.sys_id COLLATE utf8mb4_unicode_ci = eps.employee_id COLLATE utf8mb4_unicode_ci
    WHERE eps.status = 'active'
    ORDER BY e.name ASC
");
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

$totalNet   = array_sum(array_column($rows, 'net_salary'));
$totalGross = array_sum(array_column($rows, 'gross_salary'));

function fmtBDT($v) { return '৳ ' . number_format((float)$v, 2); }
function alv($v, $fb = '—') { $v = trim((string)($v ?? '')); return $v !== '' ? htmlspecialchars($v, ENT_QUOTES, 'UTF-8') : $fb; }
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Salary Disbursement Authorization — <?php echo htmlspecialchars($monthLabel); ?></title>
<style>
    @page { size: A4; margin: 20mm 18mm; }
    * { box-sizing: border-box; }
    body {
        font-family: 'Times New Roman', Times, serif;
        font-size: 12.5px; line-height: 1.7; color: #111;
        max-width: 800px; margin: 0 auto; padding: 24px;
    }

    /* ── Letterhead (same as NOC / salary cert) ── */
    .letterhead { text-align: center; border-bottom: 3px double #1b2540; padding-bottom: 14px; margin-bottom: 20px; }
    .letterhead .company-name { font-size: 22px; font-weight: 800; color: #1b2540; letter-spacing: .5px; }
    .letterhead .company-sub  { font-size: 11.5px; color: #444; margin-top: 4px; }

    .ref-row { display: flex; justify-content: space-between; font-size: 11.5px; margin-bottom: 18px; }

    h2.title {
        text-align: center; text-decoration: underline;
        font-size: 15px; font-weight: 800; text-transform: uppercase;
        letter-spacing: .5px; margin-bottom: 20px;
    }

    p { text-align: justify; margin-bottom: 12px; }

    /* ── Disbursement table ── */
    table.disburse {
        width: 100%; border-collapse: collapse; margin: 18px 0;
        font-size: 12px;
    }
    table.disburse th {
        background: #1b2540; color: #fff;
        padding: 8px 10px; text-align: left; font-weight: 700;
    }
    table.disburse td { border: 1px solid #ccc; padding: 7px 10px; vertical-align: middle; }
    table.disburse tr:nth-child(even) td { background: #f7f8fb; }
    table.disburse td.num { text-align: right; font-variant-numeric: tabular-nums; }
    table.disburse tfoot td {
        font-weight: 800; font-size: 13px;
        background: #eef1f8; border-top: 2px solid #1b2540;
        padding: 9px 10px;
    }

    /* ── Signature block ── */
    .sign-block {
        margin-top: 56px;
        display: flex; justify-content: space-between;
    }
    .sign-box { width: 220px; }
    .sign-box .line { border-top: 1px solid #333; margin-bottom: 5px; }
    .sign-box .label { font-weight: 700; font-size: 12px; }
    .sign-box .sub   { font-size: 11px; color: #555; }

    .no-print { text-align: center; margin-bottom: 20px; }
    @media print {
        .no-print { display: none; }
        body { padding: 0; }
    }
</style>
</head>
<body>

<div class="no-print">
    <button onclick="window.print()"
        style="padding:9px 26px;background:#1b2540;color:#3ecf8e;border:none;border-radius:8px;font-size:13px;font-weight:600;cursor:pointer;">
        🖨 Print / Save as PDF
    </button>
</div>

<!-- Letterhead -->
<div class="letterhead">
    <div class="company-name">TRAVHUB GLOBAL LIMITED</div>
    <div class="company-sub">House-01, Road-6, Sector-3, Uttara, Dhaka-1230 &nbsp;|&nbsp; Mobile: 01611482773 &nbsp;|&nbsp; info@travhub.com.bd</div>
    <div class="company-sub" style="margin-top:3px;">Reg. No: C-196691/2024</div>
</div>

<div class="ref-row">
    <span>Ref: <b><?php echo $refNo; ?></b></span>
    <span>Date: <b><?php echo $issueDate; ?></b></span>
</div>

<h2 class="title">Salary Disbursement Authorization — <?php echo htmlspecialchars($monthLabel); ?></h2>

<p>This letter authorizes the Finance / Accounts department to process and disburse the monthly salary
for the period of <strong><?php echo htmlspecialchars($monthLabel); ?></strong> to all eligible employees
as listed below, in accordance with their respective approved payroll structures.</p>

<p>Only employees with an <strong>active salary structure</strong> are included in this disbursement.
Total disbursement amount: <strong><?php echo fmtBDT($totalNet); ?></strong>.</p>

<table class="disburse">
    <thead>
        <tr>
            <th style="width:36px;">Sl</th>
            <th>Employee ID</th>
            <th>Employee Name</th>
            <th>Designation</th>
            <th style="text-align:right;">Gross Salary</th>
            <th style="text-align:right;">Net Payable</th>
        </tr>
    </thead>
    <tbody>
        <?php if (empty($rows)): ?>
        <tr>
            <td colspan="6" style="text-align:center;padding:20px;color:#888;">
                No active salary structures found.
            </td>
        </tr>
        <?php else: ?>
        <?php foreach ($rows as $i => $r): ?>
        <tr>
            <td><?php echo $i + 1; ?></td>
            <td><?php echo alv($r['emp_sys_id']); ?></td>
            <td><?php echo alv($r['emp_name']); ?></td>
            <td><?php echo alv($r['designation']); ?></td>
            <td class="num"><?php echo fmtBDT($r['gross_salary']); ?></td>
            <td class="num"><?php echo fmtBDT($r['net_salary']); ?></td>
        </tr>
        <?php endforeach; ?>
        <?php endif; ?>
    </tbody>
    <tfoot>
        <tr>
            <td colspan="4" style="text-align:right;">Total</td>
            <td class="num"><?php echo fmtBDT($totalGross); ?></td>
            <td class="num"><?php echo fmtBDT($totalNet); ?></td>
        </tr>
    </tfoot>
</table>

<p>The disbursement should be completed by the <strong>last working day of <?php echo htmlspecialchars($monthLabel); ?></strong>.
All payments must be processed through the respective employees' registered accounts or as per HR records.</p>

<p>This authorization is issued by the undersigned and is valid for the salary month mentioned above only.</p>

<!-- Signature block -->
<div class="sign-block">
    <div class="sign-box">
        <div class="line"></div>
        <div class="label">Prepared by</div>
        <div class="sub">HR Department<br>TravHub Global Limited</div>
    </div>
    <div class="sign-box">
        <div class="line"></div>
        <div class="label">Authorized by</div>
        <div class="sub">Managing Director<br>TravHub Global Limited</div>
    </div>
</div>

</body>
</html>