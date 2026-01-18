<?php
// print-invoice.php
require_once __DIR__ . '/vendor/autoload.php';
require __DIR__ . '../server/db_connection.php';

session_start();

// Get invoice ID from URL
$invoice_id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($invoice_id <= 0) {
    die("Invalid invoice ID");
}

try {
    // Fetch invoice using PDO (assuming db_connection.php sets up $pdo)
    $stmt = $pdo->prepare("SELECT * FROM invoices WHERE id = :id");
    $stmt->execute([':id' => $invoice_id]);
    $invoice = $stmt->fetch();

    if (!$invoice) {
        die("Invoice not found");
    }

    // Decode JSON fields
    $client_info = json_decode($invoice['client_info'], true);
    $work_items = json_decode($invoice['work_items'], true);
    $vendor_payment_methods = json_decode($invoice['vendor_payment_methods'], true);

    // Load vendor data from JSON file
    $vendor_json_path = __DIR__ . '/server/vendor.json';
    $vendor_data = [];
    if (file_exists($vendor_json_path)) {
        $vendor_json = file_get_contents($vendor_json_path);
        $vendor_data = json_decode($vendor_json, true);
    }

    // Function to convert number to words (same as in invoice-store.php)
    function numberToWords($number)
    {
        $ones = array(
            0 => 'Zero',
            1 => 'One',
            2 => 'Two',
            3 => 'Three',
            4 => 'Four',
            5 => 'Five',
            6 => 'Six',
            7 => 'Seven',
            8 => 'Eight',
            9 => 'Nine',
            10 => 'Ten',
            11 => 'Eleven',
            12 => 'Twelve',
            13 => 'Thirteen',
            14 => 'Fourteen',
            15 => 'Fifteen',
            16 => 'Sixteen',
            17 => 'Seventeen',
            18 => 'Eighteen',
            19 => 'Nineteen'
        );

        $tens = array(
            20 => 'Twenty',
            30 => 'Thirty',
            40 => 'Forty',
            50 => 'Fifty',
            60 => 'Sixty',
            70 => 'Seventy',
            80 => 'Eighty',
            90 => 'Ninety'
        );

        // Handle zero
        if ($number == 0) {
            return 'Zero Taka Only';
        }

        // Split into taka and poisha
        $parts = explode('.', number_format($number, 2, '.', ''));
        $taka = intval($parts[0]);
        $poisha = isset($parts[1]) ? intval($parts[1]) : 0;

        $words = '';

        // Process lakhs (Bangladeshi numbering: 1,00,000 = 1 lakh)
        if ($taka >= 100000) {
            $lakhs = floor($taka / 100000);
            $words .= numberToWordsSimple($lakhs, $ones, $tens) . ' Lakh';
            $taka %= 100000;

            if ($taka > 0) {
                $words .= ' ';
            }
        }

        // Process thousands
        if ($taka >= 1000) {
            $thousands = floor($taka / 1000);
            $words .= numberToWordsSimple($thousands, $ones, $tens) . ' Thousand';
            $taka %= 1000;

            if ($taka > 0) {
                $words .= ' ';
            }
        }

        // Process remaining amount
        if ($taka > 0) {
            $words .= numberToWordsSimple($taka, $ones, $tens);
        }

        // If no taka amount
        if (empty(trim($words))) {
            $words = 'Zero';
        }

        $words = trim($words) . ' Taka';

        // Add poisha if any
        if ($poisha > 0) {
            $words .= ' and ' . numberToWordsSimple($poisha, $ones, $tens) . ' Poisha';
        } else {
            $words .= ' Only';
        }

        return $words;
    }

    function numberToWordsSimple($num, $ones, $tens)
    {
        if ($num == 0) {
            return '';
        }

        $words = '';

        // Hundreds
        if ($num >= 100) {
            $hundreds = floor($num / 100);
            $words .= $ones[$hundreds] . ' Hundred';
            $num %= 100;

            if ($num > 0) {
                $words .= ' and ';
            }
        }

        // Tens and ones
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

    // ============ MERGE BANK AND MFS DATA ============
    // Merge bank information from both database and vendor.json
    $merged_bank_items = [];

    // Add bank info from database (if available)
    if (!empty($vendor_payment_methods['banks']) && is_array($vendor_payment_methods['banks'])) {
        foreach ($vendor_payment_methods['banks'] as $bank) {
            $merged_bank_items[] = [
                'title' => $bank['title'] ?? '',
                'account_no' => $bank['account_no'] ?? '',
                'branch' => $bank['branch'] ?? '',
                'routing_no' => $bank['routing_no'] ?? ''
            ];
        }
    }

    // Add bank info from vendor.json (if available)
    if (!empty($vendor_data['bank']) && is_array($vendor_data['bank'])) {
        foreach ($vendor_data['bank'] as $bank) {
            $merged_bank_items[] = [
                'title' => $bank['vendor_bank'] ?? '',
                'account_no' => $bank['vendor_bank_account'] ?? '',
                'branch' => $bank['vendor_bank_branch'] ?? '',
                'routing_no' => $bank['vendor_bank_routing'] ?? ''
            ];
        }
    }

    // Merge MFS information from both database and vendor.json
    $merged_mfs_items = [];

    // Add MFS info from database (if available)
    if (!empty($vendor_payment_methods['mfs']) && is_array($vendor_payment_methods['mfs'])) {
        foreach ($vendor_payment_methods['mfs'] as $mfs) {
            $merged_mfs_items[] = [
                'title' => $mfs['title'] ?? '',
                'mfs_type' => $mfs['mfs_type'] ?? '',
                'mfs_account' => $mfs['mfs_account'] ?? [],
                'note' => $mfs['note'] ?? ''
            ];
        }
    }

    // Add MFS info from vendor.json (if available)
    if (!empty($vendor_data['mfs']) && is_array($vendor_data['mfs'])) {
        foreach ($vendor_data['mfs'] as $mfs) {
            $merged_mfs_items[] = [
                'title' => $mfs['vendor_mfs_title'] ?? '',
                'mfs_type' => $mfs['vendor_mfs_type'] ?? '',
                'mfs_account' => $mfs['vendor_mfs_account'] ?? [],
                'note' => $mfs['vendor_amount_note'] ?? ''
            ];
        }
    }
    // ============ END MERGE ============

    // Prepare data for template
    $form_data = [
        'invoice_no' => $invoice['sys_id'] ?? 'N/A',
        'date' => isset($invoice['date']) ? date('d/m/Y', strtotime($invoice['date'])) : 'N/A',
        'total_amount' => $invoice['total_amount'] ?? 0,
        'paid_amount' => $invoice['paid_amount'] ?? 0,
        'due_amount' => $invoice['due_amount'] ?? 0,
        'total_amount_in_words' => $invoice['total_amount_in_words'] ?? '',

        // Vendor data from JSON
        'vendor_logo' => $vendor_data['logo'] ?? '',
        'vendor_title' => $vendor_data['company_name'] ?? 'TravHub Global Limited',
        'vendor_phone_no' => $vendor_data['phone'] ?? '+ 880 1611 482 773',
        'vendor_email' => $vendor_data['email'] ?? 'accounts@abc.com',
        'vendor_address_line_01' => $vendor_data['address']['line1'] ?? '',
        'vendor_address_line_02' => $vendor_data['address']['line2'] ?? '',
        'vendor_address_city' => $vendor_data['address']['city'] ?? '',
        'vendor_address_postal_code' => $vendor_data['address']['postcode'] ?? '',
        'vendor_address_country' => $vendor_data['address']['country'] ?? '',

        // Client data from database
        'client_title' => $client_info['title'] ?? '',
        'client_phone_no' => $client_info['phone_no'] ?? '',
        'client_cc' => $client_info['cc'] ?? '',

        // Work items from database
        'work_items' => $work_items ?? [],

        // Merged Bank/MFS data from both database AND vendor.json
        'bank_items' => $merged_bank_items,
        'mfs_items' => $merged_mfs_items
    ];

    // Generate amount in words if not already stored
    if (empty($form_data['total_amount_in_words'])) {
        $form_data['total_amount_in_words'] = numberToWords($invoice['total_amount']);
    }

} catch (Exception $e) {
    die("Database error: " . $e->getMessage());
}

// === HTML Content Generation ===
ob_start();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Invoice <?php echo htmlspecialchars($form_data['invoice_no']); ?></title>
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            color: #333;
            font-size: 12px;
        }

        @page {
            header: page-header;
            footer: page-footer;
            margin-top: 220px;
            margin-bottom: 80px;

            margin-header: 22mm;
        }

        table {
            border-collapse: collapse;
            width: 100%;
        }

        th,
        td {
            border: 1px solid #333;
            padding: 0px;
        }

        .title {
            font-size: 25px;
            font-weight: bold;
        }

        .sub-title {
            font-size: 15px;
            font-weight: bold;
        }

        .no-border td,
        .no-border th {
            border: none !important;
        }
    </style>
</head>

<body>

    <!-- === FIXED HEADER === -->
    <htmlpageheader name="page-header">
        <table class="no-border">
            <tr>
                <td colspan="3" style="text-align: right; border:none;">
                    <h1 style="margin: 0px;" class="title">INVOICE</h1>
                </td>
            </tr>
            <tr>
                <td rowspan="3" style="width: 12%; border:none;">
                    <img src="./assets/img/travhub.png" width="65" alt="Company Logo">
                </td>
                <td style="width: 30%; border:none;">
                    <div style="font-weight:bold;" class="sub-title">
                        <?php echo htmlspecialchars($form_data['vendor_title'] ?? 'N/A'); ?>
                    </div>
                    <div>
                        <?php
                        // Address Line 1
                        if (!empty($form_data['vendor_address_line_01'])) {
                            echo '<div style="display:block;">' . htmlspecialchars($form_data['vendor_address_line_01']) . '</div>';
                        }

                        // Address Line 2 (with comma only if next value exists)
                        if (!empty($form_data['vendor_address_line_02'])) {
                            echo '<span>';
                            echo htmlspecialchars($form_data['vendor_address_line_02']);
                            // Add comma only if city or postal code exists
                            if (!empty($form_data['vendor_address_city']) || !empty($form_data['vendor_address_postal_code'])) {
                                echo ', ';
                            }
                            echo '</span>';
                        }

                        // City & Postal Code (with hyphen only if postal code exists)
                        if (!empty($form_data['vendor_address_city']) || !empty($form_data['vendor_address_postal_code'])) {
                            echo '<span>';
                            if (!empty($form_data['vendor_address_city'])) {
                                echo htmlspecialchars($form_data['vendor_address_city']);
                            }
                            if (!empty($form_data['vendor_address_city']) && !empty($form_data['vendor_address_postal_code'])) {
                                echo '-';
                            }
                            if (!empty($form_data['vendor_address_postal_code'])) {
                                echo htmlspecialchars($form_data['vendor_address_postal_code']);
                            }
                            echo '</span>';
                        }
                        ?>
                    </div>
                    <div>Phone: <?php echo htmlspecialchars($form_data['vendor_phone_no'] ?? 'N/A'); ?></div>
                </td>
                <td style="width: 58%; text-align: right; vertical-align: top; border:none; padding-top: 10px;">
                    <div style="display: block;"><?php echo htmlspecialchars($form_data['invoice_no'] ?? 'N/A'); ?></div>
                    <div style="display: block;"><strong>Date:</strong> <?php echo htmlspecialchars($form_data['date'] ?? 'N/A'); ?></div>
                </td>
            </tr>
        </table>
    </htmlpageheader>

    <!-- === FIXED FOOTER === -->
    <htmlpagefooter name="page-footer">
        <table class="no-border" style="font-size:10px; text-align:center;">
            <tr>
                <td style="border:none;">---This is a software-generated invoice. No need for a sign and seal.---</td>
            </tr>
            <tr>
                <td style="text-align:right; border:none;">
                    Page {PAGENO} of {nbpg}
                </td>
            </tr>
        </table>
    </htmlpagefooter>

    <!-- === MAIN CONTENT === -->
    <div class="content-body">

        <!-- Bill To Section -->
        <table class="no-border mb-10">
            <tr>
                <td style="width: 45%">
                    <h3 style="margin: 5px 0 10px 0;">Bill To:</h3>
                    <div class="text-bold"><?php echo htmlspecialchars($form_data['client_title']); ?></div>
                    <?php if (!empty($form_data['client_cc'])): ?>
                        <div>CC: <?php echo htmlspecialchars($form_data['client_cc']); ?></div>
                    <?php endif; ?>
                    <?php if (!empty($form_data['client_phone_no'])): ?>
                        <div>Phone: <?php echo htmlspecialchars($form_data['client_phone_no']); ?></div>
                    <?php endif; ?>
                </td>
            </tr>
        </table>

        <!-- Work Items Table -->
        <table class="no-border" style="margin-top: 15px;">
            <thead>
                <tr>
                    <th style="background-color: #ccccccff; width: 45%; text-align: left; padding: 5px;">Item Description</th>
                    <th style="background-color: #ccccccff; width: 10%; text-align: center;">Qty</th>
                    <th style="background-color: #ccccccff; width: 20%; text-align: right;">Rate (BDT)</th>
                    <th style="background-color: #ccccccff; width: 25%; text-align: right;">Amount (BDT)</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $total_amount = 0;
                if (!empty($form_data['work_items'])):
                    foreach ($form_data['work_items'] as $item):
                        $item_amount = ($item['qty'] ?? 0) * ($item['rate'] ?? 0);
                        $total_amount += $item_amount;
                ?>
                        <tr>
                            <td style="padding: 10px 5px; border-bottom: 1px solid #d4d4d4;">
                                <div class="text-bold"><?php echo htmlspecialchars($item['title'] ?? ''); ?></div>
                                <?php if (!empty($item['particular'])): ?>
                                    <div style="font-size: 11px; margin-top: 3px;">
                                        <?php echo nl2br(htmlspecialchars($item['particular'] ?? '')); ?>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td style="text-align: center; border-bottom: 1px solid #d4d4d4;">
                                <?php echo htmlspecialchars($item['qty'] ?? 0); ?>
                            </td>
                            <td style="text-align: right; border-bottom: 1px solid #d4d4d4;">
                                <?php echo number_format($item['rate'] ?? 0, 2); ?>
                            </td>
                            <td style="text-align: right; border-bottom: 1px solid #d4d4d4;">
                                <?php echo number_format($item_amount, 2); ?>
                            </td>
                        </tr>
                    <?php
                    endforeach;
                else:
                    ?>
                    <tr>
                        <td colspan="4" style="text-align: center; padding: 20px;">
                            No work items found
                        </td>
                    </tr>
                <?php endif; ?>

                <!-- Total Amount Row -->
                <tr>
                    <td colspan="2" style="border: none;"></td>
                    <th style="text-align: right; padding: 5px 0px 5px 5px;">Total Amount:</th>
                    <td style="padding-left: 5px; text-align: right;">
                        BDT <?php echo number_format($form_data['total_amount'], 2); ?>
                    </td>
                </tr>

                <!-- Paid Amount Row -->
                <tr>
                    <td colspan="2" style="border: none;"></td>
                    <th style="text-align: right; padding: 5px 0px 5px 5px;">Paid Amount:</th>
                    <td style="padding-left: 5px; text-align: right;">
                        BDT <?php echo number_format($form_data['paid_amount'], 2); ?>
                    </td>
                </tr>

                <!-- Due Amount Row -->
                <tr>
                    <td colspan="2" style="border: none;"></td>
                    <th style="background-color: #f5f5f5; text-align: right; padding: 5px 0px 5px 5px;" class="sub-title">Balance Due:</th>
                    <td style="background-color: #f5f5f5; padding-left: 8px; text-align: right;" class="sub-title">
                        BDT <?php echo number_format($form_data['due_amount'], 2); ?>
                    </td>
                </tr>
            </tbody>
        </table>

        <!-- Amount in Words -->
        <table class="no-border" style="font-size: 12px; margin-top: 10px;">
            <tr>
                <td>In Word: <?php echo htmlspecialchars($form_data['total_amount_in_words']); ?></td>
            </tr>
        </table>

        <!-- Payment Information -->
        <?php
        $has_bank_info = !empty($form_data['bank_items']) && is_array($form_data['bank_items']);
        $has_mfs_info = !empty($form_data['mfs_items']) && is_array($form_data['mfs_items']);

        if ($has_bank_info || $has_mfs_info):
        ?>
        <table class="no-border" style="font-size: 12px; margin-top: 15px;">
            <tr>
                <td style="padding: 5px 0px;"><strong>Bank Info:</strong></td>
            </tr>
            <?php if ($has_bank_info): ?>
                <!-- Bank Information -->
                <?php foreach ($form_data['bank_items'] as $bank):
                    if (!empty($bank['title']) || !empty($bank['account_no'])): ?>
                        <tr>
                            <td style="border:none;">
                                <?php
                                $bank_info = [];
                                if (!empty($bank['title'])) $bank_info[] = htmlspecialchars($bank['title']);
                                if (!empty($bank['account_no'])) $bank_info[] = "A/C: " . htmlspecialchars($bank['account_no']);
                                if (!empty($bank['branch'])) $bank_info[] = "Branch: " . htmlspecialchars($bank['branch']);
                                if (!empty($bank['routing_no'])) $bank_info[] = "Routing: " . htmlspecialchars($bank['routing_no']);
                                echo implode(' | ', $bank_info);
                                ?>
                            </td>
                        </tr>
                    <?php endif; ?>
                <?php endforeach; ?>
            <?php endif; ?>

            <?php if ($has_mfs_info): ?>
                <!-- MFS Information -->
                <?php foreach ($form_data['mfs_items'] as $mfs):
                    if (!empty($mfs['title']) || !empty($mfs['mfs_account'])): ?>
                        <tr>
                            <td style="border:none;">
                            <?php
                            $mfs_info = [];
                            if (!empty($mfs['title'])) $mfs_info[] = htmlspecialchars($mfs['title']);
                            if (!empty($mfs['mfs_type'])) $mfs_info[] = htmlspecialchars($mfs['mfs_type']);
                            if (!empty($mfs['mfs_account']) && is_array($mfs['mfs_account'])) {
                                $mfs_info[] = "Accounts: " . implode(', ', array_map('htmlspecialchars', $mfs['mfs_account']));
                            }
                            echo implode(' | ', $mfs_info);
                            ?>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <div style="margin-top: 3px; font-style: italic;">
                                    <?php
                                        if($form_data['paid_amount'] === 0 )
                                            $mfsAmount = $form_data['total_amount'] + $form_data['total_amount'] * (1.85/100);
                                        else{
                                            $mfsAmount = $form_data['due_amount'] + $form_data['due_amount'] * (1.85/100);
                                        }
                                    ?>
                                    Note: Please pay BDT <?php echo  number_format($mfsAmount, 2); ?> in case of MFS payment
                                </div>
                            </td>
                        </tr>
                    <?php endif; ?>
                <?php endforeach; ?>
            <?php endif; ?>
        </table>
        <?php endif; ?>

        <!-- Thank You Note -->
        <div style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #ddd; text-align: center;">

        </div>

    </div>
</body>

</html>

<?php
// Capture HTML content
$html = ob_get_clean();

// Configure mPDF
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


// Set PDF metadata
$mpdf->SetTitle('Invoice ' . $form_data['invoice_no']);
$mpdf->SetAuthor($form_data['vendor_title']);
$mpdf->SetCreator('TravHub Invoice System');

// Write HTML to PDF
$mpdf->WriteHTML($html);

// Output PDF
$mpdf->Output('Invoice_' . $form_data['invoice_no'] . '.pdf', 'I');
exit;
?>