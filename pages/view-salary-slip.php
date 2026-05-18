<?php
include_once('./authenticate.php');

require_once __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/../server/db_connection.php';

session_start();

$slip_id = $_GET['slip_id'] ?? '';

if (empty($slip_id)) {
    die('Slip ID missing');
}

function safe($value) {
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}

function money($value) {
    return number_format((float)($value ?? 0), 2);
}

function paymentTypeLabel($type) {
    return match ($type) {
        'salary' => 'Salary',
        'bonus' => 'Bonus',
        'overtime' => 'Overtime',
        'allowance' => 'Allowance',
        'adjustment' => 'Adjustment',
        'custom' => 'Custom Payment',
        default => 'Payment'
    };
}

function numberToWordsSimple($num, $ones, $tens)
{
    if ($num == 0) return '';

    $words = '';

    if ($num >= 100) {
        $hundreds = floor($num / 100);
        $words .= $ones[$hundreds] . ' Hundred';
        $num %= 100;

        if ($num > 0) {
            $words .= ' and ';
        }
    }

    if ($num >= 20) {
        $tensPart = floor($num / 10) * 10;
        $words .= $tens[$tensPart];
        $num %= 10;

        if ($num > 0) {
            $words .= '-' . $ones[$num];
        }
    } elseif ($num > 0) {
        $words .= $ones[$num];
    }

    return $words;
}

function numberToWords($number)
{
    $ones = [
        0 => 'Zero', 1 => 'One', 2 => 'Two', 3 => 'Three', 4 => 'Four',
        5 => 'Five', 6 => 'Six', 7 => 'Seven', 8 => 'Eight', 9 => 'Nine',
        10 => 'Ten', 11 => 'Eleven', 12 => 'Twelve', 13 => 'Thirteen',
        14 => 'Fourteen', 15 => 'Fifteen', 16 => 'Sixteen',
        17 => 'Seventeen', 18 => 'Eighteen', 19 => 'Nineteen'
    ];

    $tens = [
        20 => 'Twenty', 30 => 'Thirty', 40 => 'Forty', 50 => 'Fifty',
        60 => 'Sixty', 70 => 'Seventy', 80 => 'Eighty', 90 => 'Ninety'
    ];

    if ($number == 0) {
        return 'Zero Taka Only';
    }

    $parts = explode('.', number_format($number, 2, '.', ''));
    $taka = intval($parts[0]);
    $poisha = intval($parts[1] ?? 0);

    $words = '';

    if ($taka >= 100000) {
        $lakhs = floor($taka / 100000);
        $words .= numberToWordsSimple($lakhs, $ones, $tens) . ' Lakh';
        $taka %= 100000;
        if ($taka > 0) $words .= ' ';
    }

    if ($taka >= 1000) {
        $thousands = floor($taka / 1000);
        $words .= numberToWordsSimple($thousands, $ones, $tens) . ' Thousand';
        $taka %= 1000;
        if ($taka > 0) $words .= ' ';
    }

    if ($taka > 0) {
        $words .= numberToWordsSimple($taka, $ones, $tens);
    }

    $words = trim($words) ?: 'Zero';
    $words .= ' Taka';

    if ($poisha > 0) {
        $words .= ' and ' . numberToWordsSimple($poisha, $ones, $tens) . ' Poisha';
    } else {
        $words .= ' Only';
    }

    return $words;
}

try {
    $stmt = $pdo->prepare("
        SELECT *
        FROM payroll_finals
        WHERE sys_id = :sys_id
        LIMIT 1
    ");
    $stmt->execute([':sys_id' => $slip_id]);
    $salary = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$salary) {
        die('Payment slip not found');
    }

    $epsSalary = json_decode($salary['eps_salary'] ?? '{}', true);
    $deduction = json_decode($salary['deduction'] ?? '{}', true);
    $paymentComponents = json_decode($salary['payment_components'] ?? '{}', true);

    $preparedInfo = json_decode($salary['prepared_info'] ?? '{}', true);
    $collectedInfo = json_decode($salary['collected_info'] ?? '{}', true);
    $authorizedInfo = json_decode($salary['authorized_info'] ?? '{}', true);

    $paymentType = $salary['payment_type'] ?? ($paymentComponents['payment_type'] ?? 'salary');
    $includeBaseSalary = !empty($paymentComponents['include_base_salary']);

    $baseSalary = (float)($paymentComponents['base_salary'] ?? 0);
    $bonus = (float)($paymentComponents['bonus'] ?? $salary['bonus'] ?? 0);
    $overtime = (float)($paymentComponents['overtime'] ?? $salary['overtime'] ?? 0);
    $otherAllowances = (float)($paymentComponents['other_allowances'] ?? $salary['allowances'] ?? 0);

    $pf = (float)($paymentComponents['pf'] ?? $deduction['provident'] ?? 0);
    $loan = (float)($paymentComponents['loan'] ?? $deduction['office_loan'] ?? 0);
    $tax = (float)($paymentComponents['tax'] ?? $deduction['tax'] ?? 0);

    $totalAdditions = $baseSalary + $bonus + $overtime + $otherAllowances;
    $totalDeductions = $pf + $loan + $tax;
    $netPayable = (float)($salary['net_payable_salary'] ?? 0);

    $bankStmt = $pdo->prepare("
        SELECT acc_name, sys_id
        FROM ac_banking
        WHERE sys_id = :sys_id
        LIMIT 1
    ");
    $bankStmt->execute([':sys_id' => $salary['from_account']]);
    $bankInfo = $bankStmt->fetch(PDO::FETCH_ASSOC);

    $form_data = [
        'slip_id' => $salary['sys_id'],
        'date' => !empty($salary['payment_date']) ? date('d/m/Y', strtotime($salary['payment_date'])) : 'N/A',
        'month' => $salary['month'] ?? '',
        'status' => strtoupper($salary['status'] ?? 'PREPARED'),
        'workflow_status' => strtoupper($salary['workflow_status'] ?? 'PREPARED'),

        'company_name' => 'TravHub Global Limited',
        'company_phone' => '+880 1611 482 773',
        'company_email' => 'accounts@travhub.com.bd',
        'company_address' => 'Dhaka, Bangladesh',

        'employee_name' => $salary['employee_name'] ?? '',
        'employee_id' => $salary['employee_id'] ?? '',
        'eps_id' => $salary['eps_id'] ?? '',

        'basic_salary' => $epsSalary['basic_salary'] ?? 0,
        'house_rent' => $epsSalary['house_rent'] ?? 0,
        'medical_allowance' => $epsSalary['medical_allowance'] ?? 0,
        'conveyance' => $epsSalary['conveyance'] ?? 0,
        'allowance' => $epsSalary['allowance'] ?? 0,
        'eps_net_salary' => $epsSalary['net_salary'] ?? 0,

        'payment_type' => $paymentType,
        'payment_type_label' => paymentTypeLabel($paymentType),

        'base_salary' => $baseSalary,
        'bonus' => $bonus,
        'overtime' => $overtime,
        'other_allowances' => $otherAllowances,

        'pf' => $pf,
        'loan' => $loan,
        'tax' => $tax,

        'total_additions' => $totalAdditions,
        'total_deductions' => $totalDeductions,
        'net_payable' => $netPayable,
        'amount_in_words' => numberToWords($netPayable),

        'note' => $salary['note'] ?? '',
        'payment_account' => ($bankInfo['acc_name'] ?? 'N/A') . ' (' . ($bankInfo['sys_id'] ?? $salary['from_account']) . ')',

        'prepared_by' => $preparedInfo['user_name'] ?? '',
        'prepared_designation' => $preparedInfo['designation'] ?? '',
        'prepared_date' => $preparedInfo['date'] ?? '',

        'collected_by' => $collectedInfo['user_name'] ?? '',
        'collected_designation' => $collectedInfo['designation'] ?? '',
        'collected_date' => $collectedInfo['date'] ?? '',

        'authorized_by' => $authorizedInfo['user_name'] ?? '',
        'authorized_designation' => $authorizedInfo['designation'] ?? '',
        'authorized_date' => $authorizedInfo['date'] ?? '',
    ];

} catch (Throwable $e) {
    die('Database error: ' . $e->getMessage());
}

ob_start();
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title><?php echo safe($form_data['payment_type_label']); ?> Slip <?php echo safe($form_data['slip_id']); ?></title>

    <style>
        body {
            font-family: 'Poppins', sans-serif;
            color: #333;
            font-size: 12px;
        }

        @page {
            header: page-header;
            footer: page-footer;
            margin-top: 200px;
            margin-bottom: 70px;
            margin-header: 18mm;
        }

        table {
            border-collapse: collapse;
            width: 100%;
        }

        th, td {
            border: 1px solid #333;
            padding: 6px;
        }

        .no-border td,
        .no-border th {
            border: none !important;
        }

        .title {
            font-size: 25px;
            font-weight: bold;
            color: #1A2039;
        }

        .sub-title {
            font-size: 15px;
            font-weight: bold;
            color: #1A2039;
        }

        .section-title {
            background: #1A2039;
            color: #ffffff;
            font-weight: bold;
            padding: 7px;
            font-size: 13px;
        }

        .right {
            text-align: right;
        }

        .center {
            text-align: center;
        }

        .amount-box {
            background: #f5f5f5;
            font-weight: bold;
            font-size: 13px;
        }

        .green {
            color: #3aab71;
            font-weight: bold;
        }

        .red {
            color: #b91c1c;
            font-weight: bold;
        }

        .muted {
            color: #666;
            font-size: 11px;
        }
    </style>
</head>

<body>

<htmlpageheader name="page-header">
    <table class="no-border">
        <tr>
            <td colspan="3" style="text-align:right;">
                <h1 class="title" style="margin:0;">
                    <?php echo strtoupper(safe($form_data['payment_type_label'])); ?> SLIP
                </h1>
            </td>
        </tr>

        <tr>
            <td rowspan="3" style="width:12%;">
                <img src="../assets/images/logo/round-logo.png" width="65">
            </td>

            <td style="width:45%;">
                <div class="sub-title"><?php echo safe($form_data['company_name']); ?></div>
                <div><?php echo safe($form_data['company_address']); ?></div>
                <div>Phone: <?php echo safe($form_data['company_phone']); ?></div>
                <div>Email: <?php echo safe($form_data['company_email']); ?></div>
            </td>

            <td style="width:43%; text-align:right; vertical-align:top;">
                <div><strong>Slip ID:</strong> <?php echo safe($form_data['slip_id']); ?></div>
                <div><strong>Date:</strong> <?php echo safe($form_data['date']); ?></div>
                <div><strong>Month:</strong> <?php echo safe($form_data['month']); ?></div>
                <div><strong>Status:</strong> <?php echo safe($form_data['workflow_status']); ?></div>
            </td>
        </tr>
    </table>
</htmlpageheader>

<htmlpagefooter name="page-footer">
    <table class="no-border" style="font-size:10px; text-align:center;">
        <tr>
            <td>---This is a software-generated payment slip. No need for a sign and seal. If need, you may sign and seal for external use, .---</td>
        </tr>
        <tr>
            <td style="text-align:right;">Page {PAGENO} of {nbpg}</td>
        </tr>
    </table>
</htmlpagefooter>

<div>

    <table class="no-border" style="margin-bottom:15px;">
        <tr>
            <td style="width:50%;">
                <h3 style="margin:0 0 8px 0;">Employee Information</h3>
                <div><strong>Name:</strong> <?php echo safe($form_data['employee_name']); ?></div>
                <div><strong>Employee ID:</strong> <?php echo safe($form_data['employee_id']); ?></div>
                <div><strong>EPS ID:</strong> <?php echo safe($form_data['eps_id']); ?></div>
            </td>

            <td style="width:50%; text-align:right;">
                <h3 style="margin:0 0 8px 0;">Payment Information</h3>
                <div><strong>Payment Type:</strong> <?php echo safe($form_data['payment_type_label']); ?></div>
                <div><strong>Payment Account:</strong></div>
                <div><?php echo safe($form_data['payment_account']); ?></div>
            </td>
        </tr>
    </table>

    <?php if ($includeBaseSalary && $baseSalary > 0): ?>
        <table>
            <tr>
                <td colspan="2" class="section-title">Salary Structure</td>
            </tr>

            <tr>
                <td>Basic Salary</td>
                <td class="right">BDT <?php echo money($form_data['basic_salary']); ?></td>
            </tr>

            <tr>
                <td>House Rent</td>
                <td class="right">BDT <?php echo money($form_data['house_rent']); ?></td>
            </tr>

            <tr>
                <td>Medical Allowance</td>
                <td class="right">BDT <?php echo money($form_data['medical_allowance']); ?></td>
            </tr>

            <tr>
                <td>Conveyance</td>
                <td class="right">BDT <?php echo money($form_data['conveyance']); ?></td>
            </tr>

            <tr>
                <td>Allowance</td>
                <td class="right">BDT <?php echo money($form_data['allowance']); ?></td>
            </tr>

            <tr class="amount-box">
                <td>Base Salary Included</td>
                <td class="right">BDT <?php echo money($baseSalary); ?></td>
            </tr>
        </table>

        <br>
    <?php endif; ?>

    <table>
        <tr>
            <td colspan="4" class="section-title">Payment Details</td>
        </tr>

        <?php if ($baseSalary > 0): ?>
            <tr>
                <td colspan="3">Base Salary</td>
                <td class="right green">BDT <?php echo money($baseSalary); ?></td>
            </tr>
        <?php endif; ?>

        <?php if ($bonus > 0): ?>
            <tr>
                <td colspan="3">Bonus</td>
                <td class="right green">BDT <?php echo money($bonus); ?></td>
            </tr>
        <?php endif; ?>

        <?php if ($overtime > 0): ?>
            <tr>
                <td colspan="3">Overtime</td>
                <td class="right green">BDT <?php echo money($overtime); ?></td>
            </tr>
        <?php endif; ?>

        <?php if ($otherAllowances > 0): ?>
            <tr>
                <td colspan="3">
                    <?php echo $paymentType === 'adjustment' ? 'Adjustment' : 'Other Allowance'; ?>
                </td>
                <td class="right green">BDT <?php echo money($otherAllowances); ?></td>
            </tr>
        <?php endif; ?>

        <?php if ($pf > 0): ?>
            <tr>
                <td colspan="3">Provident Fund</td>
                <td class="right red">BDT <?php echo money($pf); ?></td>
            </tr>
        <?php endif; ?>

        <?php if ($loan > 0): ?>
            <tr>
                <td colspan="3">Office Loan / Office Due</td>
                <td class="right red">BDT <?php echo money($loan); ?></td>
            </tr>
        <?php endif; ?>

        <?php if ($tax > 0): ?>
            <tr>
                <td colspan="3">Tax</td>
                <td class="right red">BDT <?php echo money($tax); ?></td>
            </tr>
        <?php endif; ?>

        <tr class="amount-box">
            <td colspan="3">Total Addition</td>
            <td class="right">BDT <?php echo money($totalAdditions); ?></td>
        </tr>

        <tr class="amount-box">
            <td colspan="3">Total Deduction</td>
            <td class="right">BDT <?php echo money($totalDeductions); ?></td>
        </tr>
    </table>

    <br>

    <table>
        <tr>
            <td style="width:70%;" class="amount-box">Net Payable Amount</td>
            <td style="width:30%;" class="right amount-box">
                BDT <?php echo money($form_data['net_payable']); ?>
            </td>
        </tr>

        <tr>
            <td colspan="2">
                <strong>In Word:</strong> <?php echo safe($form_data['amount_in_words']); ?>
            </td>
        </tr>
    </table>

    <?php if (!empty($form_data['note'])): ?>
        <br>
        <table>
            <tr>
                <td class="section-title">Note</td>
            </tr>
            <tr>
                <td><?php echo nl2br(safe($form_data['note'])); ?></td>
            </tr>
        </table>
    <?php endif; ?>

    <br>

    <table>
        <!--<tr>-->
        <!--    <td colspan="3" class="section-title">Workflow Information</td>-->
        <!--</tr>-->

        <tr>
            <th style="width:33%;">Prepared By</th>
            <th style="width:33%;">Collected By</th>
            <th style="width:34%;">Authorized By</th>
        </tr>

        <tr>
            <td>
                <?php echo safe($form_data['prepared_by'] ?: 'Pending'); ?><br>
                <span class="muted"><?php echo safe($form_data['prepared_designation']); ?></span><br>
                <span class="muted"><?php echo safe($form_data['prepared_date']); ?></span>
            </td>

            <td>
                <?php echo safe($form_data['collected_by'] ?: 'Pending'); ?><br>
                <span class="muted"><?php echo safe($form_data['collected_designation']); ?></span><br>
                <span class="muted"><?php echo safe($form_data['collected_date']); ?></span>
            </td>

            <td>
                <?php echo safe($form_data['authorized_by'] ?: 'Pending'); ?><br>
                <span class="muted"><?php echo safe($form_data['authorized_designation']); ?></span><br>
                <span class="muted"><?php echo safe($form_data['authorized_date']); ?></span>
            </td>
        </tr>
    </table>

    <br><br>

    <table class="no-border">
        <tr>
            <td style="width:33%; text-align:center;">
                <br><br>
                ___________________________<br>
                Prepared By
            </td>

            <td style="width:33%; text-align:center;">
                <br><br>
                ___________________________<br>
                Employee Collection
            </td>

            <td style="width:34%; text-align:center;">
                <br><br>
                ___________________________<br>
                Authorized By
            </td>
        </tr>
    </table>

</div>

</body>
</html>

<?php
$html = ob_get_clean();

$mpdf = new \Mpdf\Mpdf([
    'mode' => 'utf-8',
    'format' => 'A4',
    'tempDir' => __DIR__ . '/tmp',
    'fontDir' => array_merge((new Mpdf\Config\ConfigVariables())->getDefaults()['fontDir'], [
        __DIR__ . '/fonts',
    ]),
    'fontdata' => array_merge((new Mpdf\Config\FontVariables())->getDefaults()['fontdata'], [
        'poppins' => [
            'R' => 'Poppins-Regular.ttf',
            'M' => 'Poppins-Medium.ttf',
            'B' => 'Poppins-SemiBold.ttf',
        ]
    ]),
    'default_font' => 'poppins',
]);

$mpdf->SetTitle($form_data['payment_type_label'] . ' Slip ' . $form_data['slip_id']);
$mpdf->SetAuthor($form_data['company_name']);
$mpdf->SetCreator('TravHub Payroll System');

$mpdf->WriteHTML($html);

$fileName = str_replace(' ', '_', $form_data['payment_type_label']) . '_Slip_' . $form_data['slip_id'] . '.pdf';

$mpdf->Output($fileName, 'I');
exit;
?>