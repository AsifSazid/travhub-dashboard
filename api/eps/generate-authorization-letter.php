<?php
// PATH: /api/eps/generate-authorization-letter.php
//
// Generates a print-ready HTML authorization letter for a salary
// disbursement, addressed to a bank, for a Managing Director/Accounts
// signatory to sign and send.
//
// No employee bank-account fields exist yet anywhere in this codebase
// (eps_structures / payroll_finals hold no bank_account_no column), so
// this endpoint takes the bank/account details as direct input rather than
// looking them up -- the same details can differ per payment anyway if an
// employee changes banks.
//
// GET or POST:
//   slip_id            (required) -- payroll_finals.sys_id, the salary payment this letter is for
//   bank_name          (required) -- e.g. "Dutch Bangla Bank Ltd."
//   bank_branch         (optional)
//   employee_bank_account (required) -- the employee's account number the salary should be credited to
//   employee_designation (optional) -- shown on the letter
//   company_name        (optional, defaults to the configured company name)
//   company_address     (optional)
//   signatory_name       (optional) -- left blank for a wet signature if omitted
//   signatory_title      (optional, default "Managing Director")
//
// Returns a full, self-contained, print-ready HTML page (window.print()
// fires automatically), the same rendering approach used by the report
// pages' exportPDF() helper.

session_start();
require '../../server/db_connection.php';

$slipId = $_REQUEST['slip_id'] ?? '';
if (!$slipId) {
    http_response_code(400);
    echo 'slip_id is required';
    exit;
}

$bankName             = trim($_REQUEST['bank_name'] ?? '');
$bankBranch           = trim($_REQUEST['bank_branch'] ?? '');
$employeeBankAccount  = trim($_REQUEST['employee_bank_account'] ?? '');
$employeeDesignation  = trim($_REQUEST['employee_designation'] ?? '');
$companyName          = trim($_REQUEST['company_name'] ?? 'TravHub Global Limited');
$companyAddress       = trim($_REQUEST['company_address'] ?? '');
$signatoryName        = trim($_REQUEST['signatory_name'] ?? '');
$signatoryTitle       = trim($_REQUEST['signatory_title'] ?? 'Managing Director');

if (!$bankName || !$employeeBankAccount) {
    http_response_code(400);
    echo 'bank_name and employee_bank_account are required';
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT * FROM payroll_finals WHERE sys_id = ?");
    $stmt->execute([$slipId]);
    $slip = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$slip) {
        http_response_code(404);
        echo 'Salary slip not found';
        exit;
    }

    $employeeName  = htmlspecialchars($slip['employee_name'] ?? '');
    $netPayable    = number_format((float)($slip['net_payable_salary'] ?? 0), 2);
    $monthOfSalary = htmlspecialchars($slip['month'] ?? '');
    $paymentDate   = $slip['payment_date'] ?? date('Y-m-d');
    $letterDate    = (new DateTime())->format('d F, Y');
    $refNo         = 'AUTH-' . strtoupper(substr(md5($slipId . time()), 0, 8));

} catch (Throwable $e) {
    http_response_code(500);
    echo 'Server error: ' . htmlspecialchars($e->getMessage());
    exit;
}

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Authorization Letter — <?php echo $employeeName; ?></title>
<style>
    @page { size: A4; margin: 25mm 20mm; }
    body { font-family: 'Times New Roman', Times, serif; font-size: 13px; color: #111; line-height: 1.6; max-width: 750px; margin: 0 auto; padding: 30px; }
    .letterhead { text-align: center; border-bottom: 2px solid #111; padding-bottom: 12px; margin-bottom: 30px; }
    .letterhead h1 { font-size: 20px; margin: 0; letter-spacing: 0.5px; }
    .letterhead p { font-size: 11px; color: #444; margin: 4px 0 0; }
    .meta-row { display: flex; justify-content: space-between; margin-bottom: 20px; font-size: 12px; }
    .to-block { margin-bottom: 20px; }
    .subject { font-weight: bold; text-decoration: underline; margin: 20px 0; }
    .body-text { text-align: justify; margin-bottom: 16px; }
    table.details { width: 100%; border-collapse: collapse; margin: 20px 0; }
    table.details th, table.details td { border: 1px solid #999; padding: 8px 10px; font-size: 12px; text-align: left; }
    table.details th { background: #f2f2f2; width: 40%; }
    .signature-block { margin-top: 60px; }
    .signature-line { border-top: 1px solid #111; width: 220px; margin-top: 50px; padding-top: 6px; }
    .no-print { text-align: center; margin-top: 30px; }
    @media print { .no-print { display: none; } }
</style>
</head>
<body>

    <div class="letterhead">
        <h1><?php echo htmlspecialchars($companyName); ?></h1>
        <?php if ($companyAddress): ?><p><?php echo htmlspecialchars($companyAddress); ?></p><?php endif; ?>
    </div>

    <div class="meta-row">
        <span>Ref: <?php echo $refNo; ?></span>
        <span>Date: <?php echo $letterDate; ?></span>
    </div>

    <div class="to-block">
        The Manager<br>
        <?php echo htmlspecialchars($bankName); ?><br>
        <?php if ($bankBranch): ?><?php echo htmlspecialchars($bankBranch); ?> Branch<br><?php endif; ?>
    </div>

    <div class="subject">Subject: Authorization for Salary Disbursement — <?php echo $employeeName; ?></div>

    <p class="body-text">Dear Sir/Madam,</p>
    <p class="body-text">
        We hereby authorize you to credit the salary payment detailed below to the account of our employee,
        <strong><?php echo $employeeName; ?></strong><?php echo $employeeDesignation ? ' (' . htmlspecialchars($employeeDesignation) . ')' : ''; ?>,
        for the month of <strong><?php echo $monthOfSalary; ?></strong>.
    </p>

    <table class="details">
        <tr><th>Employee Name</th><td><?php echo $employeeName; ?></td></tr>
        <?php if ($employeeDesignation): ?><tr><th>Designation</th><td><?php echo htmlspecialchars($employeeDesignation); ?></td></tr><?php endif; ?>
        <tr><th>Beneficiary Account No.</th><td><?php echo htmlspecialchars($employeeBankAccount); ?></td></tr>
        <tr><th>Salary Month</th><td><?php echo $monthOfSalary; ?></td></tr>
        <tr><th>Payment Date</th><td><?php echo htmlspecialchars(substr($paymentDate, 0, 10)); ?></td></tr>
        <tr><th>Amount Payable</th><td>৳ <?php echo $netPayable; ?></td></tr>
    </table>

    <p class="body-text">
        Kindly process the above transfer and debit the equivalent amount from our company account maintained with your branch.
        This letter is issued for the sole purpose of authorizing the above-mentioned salary disbursement.
    </p>

    <p class="body-text">Thank you for your continued support and cooperation.</p>

    <div class="signature-block">
        <p>Sincerely,</p>
        <div class="signature-line">
            <?php echo $signatoryName ? htmlspecialchars($signatoryName) : '&nbsp;'; ?><br>
            <?php echo htmlspecialchars($signatoryTitle); ?><br>
            <?php echo htmlspecialchars($companyName); ?>
        </div>
    </div>

    <div class="no-print">
        <button onclick="window.print()" style="padding:10px 24px;background:#1f2937;color:#fff;border:none;border-radius:6px;cursor:pointer;font-size:14px;">Print This Letter</button>
    </div>

</body>
</html>