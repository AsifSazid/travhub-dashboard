<?php
include_once('./authenticate.php');

$ip_port = @file_get_contents('../ippath.txt');
if (empty($ip_port)) {
    $ip_port = "http://103.104.219.3:898/";
}

if (!isset($_GET['eps_id']) || empty($_GET['eps_id'])) {
    die('EPS ID missing');
}

$eps_id = $_GET['eps_id'];

$singleEpsApi     = $ip_port . "api/eps/get-eps.php?eps_id=" . urlencode($eps_id);
$salaryHistoryApi = $ip_port . "api/eps/salary-history.php?eps_id=" . urlencode($eps_id);

/* Fetch EPS + Employee + Structure */
$epsData = json_decode(@file_get_contents($singleEpsApi), true);

/* Fetch Payment History */
$salaryHistory = json_decode(@file_get_contents($salaryHistoryApi), true);

if (!$epsData || empty($epsData['success'])) {
    die('Failed to load EPS data');
}

$emp = $epsData['epsDetails'];
$salaryRows = $salaryHistory['data'] ?? [];

$totalPaid = 0;
$totalRecords = count($salaryRows);
$preparedCount = 0;
$collectedCount = 0;
$authorizedCount = 0;

foreach ($salaryRows as $row) {
    $totalPaid += (float)($row['net_payable_salary'] ?? 0);

    $workflowStatus = strtolower($row['status'] ?? 'prepared');

    if ($workflowStatus === 'authorized') {
        $authorizedCount++;
    } elseif ($workflowStatus === 'collected') {
        $collectedCount++;
    } else {
        $preparedCount++;
    }
}

function safeText($value)
{
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}

function moneyFormat($value)
{
    return number_format((float)($value ?? 0), 2);
}

function paymentTypeLabel($type)
{
    return match ($type) {
        'salary' => 'Salary',
        'bonus' => 'Bonus Only',
        'overtime' => 'Overtime Only',
        'allowance' => 'Allowance Only',
        'adjustment' => 'Adjustment',
        'custom' => 'Custom Payment',
        default => 'Payment'
    };
}

function workflowBadgeClass($status)
{
    return match ($status) {
        'authorized' => 'bg-emerald-100 text-emerald-700 border-emerald-200',
        'collected' => 'bg-purple-100 text-purple-700 border-purple-200',
        default => 'bg-yellow-100 text-yellow-700 border-yellow-200'
    };
}

function statusBadgeClass($status)
{
    return match ($status) {
        'paid' => 'bg-green-100 text-green-700 border-green-200',
        'cancelled' => 'bg-red-100 text-red-700 border-red-200',
        'collected' => 'bg-purple-100 text-purple-700 border-purple-200',
        default => 'bg-yellow-100 text-yellow-700 border-yellow-200'
    };
}

$epsNetSalary = (float)($emp['net_salary'] ?? 0);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employee Payment - <?php echo safeText($emp['employee_name'] ?? 'Employee'); ?></title>

    <link rel="icon" type="image/png" href="../assets/images/logo/round-logo.png" sizes="16x16">

    <script src="https://cdn.tailwindcss.com"></script>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/remixicon@3.5.0/fonts/remixicon.css">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>

<body class="bg-gray-50">
<?php include '../elements/header.php'; ?>
<?php include '../elements/aside.php'; ?>

<main id="mainContent" class="pt-16 pl-0 lg:pl-64 lg:my-16 transition-all duration-300">
    <div class="p-4 md:p-6">

        <!-- Page Header -->
        <div class="mb-6 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-gray-800">Employee Payment Management</h1>
                <p class="text-gray-600">
                    Generate salary, bonus, allowance or adjustment payment for <?php echo safeText($emp['employee_name'] ?? 'Employee'); ?>
                </p>
            </div>

            <a href="eps.php"
               class="inline-flex items-center gap-2 px-4 py-2 bg-white border border-gray-200 rounded-lg text-gray-700 hover:bg-gray-100 transition">
                <i class="fas fa-arrow-left"></i>
                Back
            </a>
        </div>

        <div class="space-y-6">

            <!-- Employee Salary Information Card -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <div class="mb-6 pb-4 border-b">
                    <h2 class="text-xl font-semibold text-gray-800">
                        Payment Information of <?php echo safeText($emp['employee_name'] ?? ''); ?>
                    </h2>
                    <p class="text-sm text-gray-500 mt-1">
                        EPS ID: <?php echo safeText($eps_id); ?>
                    </p>
                </div>

                <div class="grid grid-cols-1 xl:grid-cols-2 gap-6">

                    <!-- Salary Structure -->
                    <div>
                        <p class="text-md font-semibold text-gray-800 mb-4">EPS Salary Structure</p>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div class="bg-gray-50 p-4 rounded-lg border border-gray-100">
                                <div class="text-sm text-gray-500">Basic Salary</div>
                                <div class="text-lg font-semibold text-gray-800">
                                    <?php echo moneyFormat($emp['basic_salary'] ?? 0); ?>
                                </div>
                            </div>

                            <div class="bg-gray-50 p-4 rounded-lg border border-gray-100">
                                <div class="text-sm text-gray-500">House Rent</div>
                                <div class="text-lg font-semibold text-gray-800">
                                    <?php echo moneyFormat($emp['house_rent'] ?? 0); ?>
                                </div>
                            </div>

                            <div class="bg-gray-50 p-4 rounded-lg border border-gray-100">
                                <div class="text-sm text-gray-500">Medical Allowance</div>
                                <div class="text-lg font-semibold text-gray-800">
                                    <?php echo moneyFormat($emp['medical_allowance'] ?? 0); ?>
                                </div>
                            </div>

                            <div class="bg-gray-50 p-4 rounded-lg border border-gray-100">
                                <div class="text-sm text-gray-500">Conveyance</div>
                                <div class="text-lg font-semibold text-gray-800">
                                    <?php echo moneyFormat($emp['conveyance'] ?? 0); ?>
                                </div>
                            </div>

                            <div class="bg-blue-50 p-4 rounded-lg border border-blue-100">
                                <div class="text-sm text-blue-600">Gross Salary</div>
                                <div class="text-lg font-semibold text-blue-900">
                                    <?php echo moneyFormat($emp['gross_salary'] ?? 0); ?>
                                </div>
                            </div>

                            <div class="bg-green-50 p-4 rounded-lg border border-green-100">
                                <div class="text-sm text-green-600">EPS Net Salary</div>
                                <div class="text-lg font-semibold text-green-900" id="epsNetSalaryText">
                                    <?php echo moneyFormat($epsNetSalary); ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Payment Generate Inputs -->
                    <div>
                        <div class="mb-5">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Payment Type</label>
                            <select id="payment_type"
                                    onchange="handlePaymentTypeChange()"
                                    class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition">
                                <option value="salary">Salary</option>
                                <option value="bonus">Bonus Only</option>
                                <option value="overtime">Overtime Only</option>
                                <option value="allowance">Allowance Only</option>
                                <option value="adjustment">Adjustment</option>
                                <option value="custom">Custom Payment</option>
                            </select>
                        </div>

                        <div class="mb-5 p-4 bg-indigo-50 border border-indigo-100 rounded-xl">
                            <label class="flex items-center gap-3 cursor-pointer">
                                <input type="checkbox"
                                       id="include_base_salary"
                                       checked
                                       onchange="calculatePaymentPreview()"
                                       class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                                <span>
                                    <span class="block text-sm font-semibold text-gray-800">Include EPS Net Salary</span>
                                    <span class="block text-xs text-gray-500">
                                        Turn off this option for bonus-only, overtime-only or custom payments.
                                    </span>
                                </span>
                            </label>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

                            <!-- Additions Section -->
                            <div>
                                <h3 class="text-md font-semibold text-gray-800 mb-4">Payment Components</h3>

                                <div class="space-y-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Bonus</label>
                                        <input type="number" id="bonus" placeholder="Enter bonus amount"
                                               oninput="calculatePaymentPreview()"
                                               class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition">
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Overtime</label>
                                        <input type="number" id="overtime" placeholder="Enter overtime amount"
                                               oninput="calculatePaymentPreview()"
                                               class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition">
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Other Allowances / Adjustment</label>
                                        <input type="number" id="other_allowances" placeholder="Enter allowance or adjustment amount"
                                               oninput="calculatePaymentPreview()"
                                               class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition">
                                    </div>
                                </div>
                            </div>

                            <!-- Deductions Section -->
                            <div>
                                <h3 class="text-md font-semibold text-gray-800 mb-4">Deductions</h3>

                                <div class="space-y-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Provident Fund</label>
                                        <input type="number" id="pf" placeholder="Enter PF amount"
                                               oninput="calculatePaymentPreview()"
                                               class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition">
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Loan</label>
                                        <input type="number" id="loan" placeholder="Enter loan amount"
                                               oninput="calculatePaymentPreview()"
                                               class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition">
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Tax</label>
                                        <input type="number" id="tax" placeholder="Enter tax amount"
                                               oninput="calculatePaymentPreview()"
                                               class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Payment Preview -->
                        <div class="mt-6 grid grid-cols-1 md:grid-cols-3 gap-3">
                            <div class="p-4 rounded-xl bg-blue-50 border border-blue-100">
                                <p class="text-sm text-blue-600">Base Included</p>
                                <h3 class="text-lg font-bold text-blue-900" id="previewBaseSalary">0.00</h3>
                            </div>

                            <div class="p-4 rounded-xl bg-green-50 border border-green-100">
                                <p class="text-sm text-green-600">Total Additions</p>
                                <h3 class="text-lg font-bold text-green-900" id="previewAdditions">0.00</h3>
                            </div>

                            <div class="p-4 rounded-xl bg-red-50 border border-red-100">
                                <p class="text-sm text-red-600">Total Deductions</p>
                                <h3 class="text-lg font-bold text-red-900" id="previewDeductions">0.00</h3>
                            </div>

                            <div class="md:col-span-3 p-5 rounded-xl bg-gray-900 border border-gray-800">
                                <p class="text-sm text-gray-300">Net Payable Amount</p>
                                <h3 class="text-3xl font-bold text-white" id="previewNetPayable">0.00</h3>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Payment Information -->
                <div class="mt-8 pt-6 border-t">
                    <h3 class="text-md font-semibold text-gray-800 mb-4">Payment Account & Note</h3>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <div class="mb-4">
                                <label for="accountInput" class="block text-sm font-medium text-gray-700 mb-1">Accounts</label>
                                <?php include('./form-selects/accounts.php'); ?>
                                <input type="hidden" id="selected_account_id" value="">
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Payment Month / Reference Month</label>
                                <input type="text" id="month_of_salary" placeholder="Example: January 2026"
                                       class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition">
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Note</label>
                            <textarea id="note" placeholder="Add notes here" rows="5"
                                      class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition">No Notes</textarea>
                        </div>
                    </div>
                </div>

                <div class="mt-8 flex justify-end">
                    <button onclick="generatePayment()"
                            class="px-8 py-3 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg shadow-sm hover:shadow-md transition duration-200">
                        <i class="fas fa-money-check-alt mr-2"></i>
                        Generate Payment
                    </button>
                </div>
            </div>

            <!-- Payment History Section -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6 pb-3 border-b">
                    <div>
                        <h2 class="text-lg font-semibold text-gray-800">Payment History</h2>
                        <p class="text-sm text-gray-500">Search, filter and view salary, bonus or other payment records.</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                    <div class="p-4 rounded-xl bg-blue-50 border border-blue-100">
                        <p class="text-sm text-blue-600">Total Records</p>
                        <h3 class="text-2xl font-bold text-blue-900"><?php echo $totalRecords; ?></h3>
                    </div>

                    <div class="p-4 rounded-xl bg-green-50 border border-green-100">
                        <p class="text-sm text-green-600">Total Paid/Payable</p>
                        <h3 class="text-2xl font-bold text-green-900"><?php echo moneyFormat($totalPaid); ?></h3>
                    </div>

                    <div class="p-4 rounded-xl bg-yellow-50 border border-yellow-100">
                        <p class="text-sm text-yellow-600">Prepared</p>
                        <h3 class="text-2xl font-bold text-yellow-900"><?php echo $preparedCount; ?></h3>
                    </div>

                    <div class="p-4 rounded-xl bg-emerald-50 border border-emerald-100">
                        <p class="text-sm text-emerald-600">Authorized</p>
                        <h3 class="text-2xl font-bold text-emerald-900"><?php echo $authorizedCount; ?></h3>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-5 gap-4 mb-6">
                    <input type="text"
                           id="salarySearchInput"
                           placeholder="Search slip, type, month, amount..."
                           class="md:col-span-2 w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none">

                    <select id="paymentTypeFilter"
                            class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none">
                        <option value="">All Types</option>
                        <option value="salary">Salary</option>
                        <option value="bonus">Bonus</option>
                        <option value="overtime">Overtime</option>
                        <option value="allowance">Allowance</option>
                        <option value="adjustment">Adjustment</option>
                        <option value="custom">Custom</option>
                    </select>

                    <select id="salaryStatusFilter"
                            class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none">
                        <option value="">All Flow Status</option>
                        <option value="prepared">Prepared</option>
                        <option value="collected">Collected</option>
                        <option value="authorized">Authorized</option>
                    </select>

                    <select id="salarySortFilter"
                            class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none">
                        <option value="newest">Newest First</option>
                        <option value="oldest">Oldest First</option>
                        <option value="high">Highest Amount</option>
                        <option value="low">Lowest Amount</option>
                    </select>
                </div>

                <?php if (!empty($salaryRows)): ?>
                    <div id="salaryGrid" class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-5">
                        <?php foreach ($salaryRows as $row): ?>
                            <?php
                                $status = strtolower($row['status'] ?? 'pending');
                                $workflowStatus = strtolower($row['status'] ?? 'prepared');

                                $paymentType = strtolower($row['payment_type'] ?? 'salary');

                                $statusClass = statusBadgeClass($status);
                                $workflowClass = workflowBadgeClass($workflowStatus);

                                $netSalary = (float)($row['net_payable_salary'] ?? 0);
                                $deduct = (float)($row['total_deduction'] ?? 0);
                                $bonus = (float)($row['bonus'] ?? 0);
                                $overtime = (float)($row['overtime'] ?? 0);
                                $allowances = (float)($row['allowances'] ?? 0);
                                $paymentDate = $row['payment_date'] ?? '';
                                $salaryMonth = $row['salary_month'] ?? ($row['month'] ?? '');
                                $slipId = $row['slip_id'] ?? ($row['sys_id'] ?? '');

                                $preparedInfo = json_decode($row['prepared_info'] ?? '{}', true);
                                $collectedInfo = json_decode($row['collected_info'] ?? '{}', true);
                                $authorizedInfo = json_decode($row['authorized_info'] ?? '{}', true);

                                $preparedBy = $preparedInfo['user_name'] ?? '';
                                $collectedBy = $collectedInfo['user_name'] ?? '';
                                $authorizedBy = $authorizedInfo['user_name'] ?? '';
                            ?>

                            <div class="salary-card rounded-2xl border border-gray-200 bg-white shadow-sm hover:shadow-md transition overflow-hidden"
                                 data-search="<?php echo strtolower(safeText($slipId . ' ' . $salaryMonth . ' ' . $netSalary . ' ' . $status . ' ' . $workflowStatus . ' ' . $paymentType)); ?>"
                                 data-status="<?php echo safeText($workflowStatus); ?>"
                                 data-payment-type="<?php echo safeText($paymentType); ?>"
                                 data-date="<?php echo safeText($paymentDate); ?>"
                                 data-amount="<?php echo safeText($netSalary); ?>">

                                <div class="p-5">
                                    <div class="flex items-start justify-between gap-3 mb-4">
                                        <div>
                                            <p class="text-xs text-gray-500">Slip ID</p>
                                            <h3 class="font-bold text-gray-900"><?php echo safeText($slipId); ?></h3>
                                        </div>
                                        
                                        <div>
                                            <span class="px-3 py-1 text-xs font-semibold rounded-full bg-blue-50 text-blue-700 border border-blue-100 me-2">
                                                <?php echo safeText(paymentTypeLabel($paymentType)); ?>
                                            </span>
                                            
                                            <?php if($workflowStatus == 'collected') { ?>
                                            <span class="px-3 py-1 text-xs font-semibold rounded-full border <?php echo $statusClass; ?>">
                                                Payment: <?php echo strtoupper(safeText($workflowStatus)); ?>
                                            </span>
                                            <?php }else{ ?>
                                            <span class="px-3 py-1 text-xs font-semibold rounded-full border <?php echo $workflowClass; ?>">
                                                <?php echo strtoupper(safeText($workflowStatus)); ?>
                                            </span>
                                            <?php } ?>
                                        </div>

                                    </div>

                                    <div class="mb-4">
                                        <p class="text-sm text-gray-500">Net Payable Amount</p>
                                        <h2 class="text-3xl font-bold text-gray-900">
                                            <?php echo moneyFormat($netSalary); ?>
                                        </h2>
                                    </div>

                                    <div class="grid grid-cols-2 gap-3 text-sm mb-4">
                                        <div class="bg-gray-50 p-3 rounded-lg">
                                            <p class="text-gray-500">Month</p>
                                            <p class="font-semibold text-gray-800"><?php echo safeText($salaryMonth); ?></p>
                                        </div>

                                        <div class="bg-gray-50 p-3 rounded-lg">
                                            <p class="text-gray-500">Payment Date</p>
                                            <p class="font-semibold text-gray-800"><?php echo safeText($paymentDate); ?></p>
                                        </div>

                                        <div class="bg-green-50 p-3 rounded-lg">
                                            <p class="text-green-600">Additions</p>
                                            <p class="font-semibold text-green-800">
                                                <?php echo moneyFormat($bonus + $overtime + $allowances); ?>
                                            </p>
                                        </div>

                                        <div class="bg-red-50 p-3 rounded-lg">
                                            <p class="text-red-600">Deduction</p>
                                            <p class="font-semibold text-red-800">
                                                <?php echo moneyFormat($deduct); ?>
                                            </p>
                                        </div>
                                    </div>

                                    <div class="mb-4 p-3 bg-gray-50 rounded-xl text-xs text-gray-600 space-y-1">
                                        <div>
                                            <strong>Prepared:</strong>
                                            <?php echo $preparedBy ? safeText($preparedBy) : 'Pending'; ?>
                                        </div>
                                        <div>
                                            <strong>Collected:</strong>
                                            <?php echo $collectedBy ? safeText($collectedBy) : 'Pending'; ?>
                                        </div>
                                        <div>
                                            <strong>Authorized:</strong>
                                            <?php echo $authorizedBy ? safeText($authorizedBy) : 'Pending'; ?>
                                        </div>
                                    </div>

                                    <?php if (!empty($row['note'])): ?>
                                        <div class="mb-4 p-3 bg-blue-50 rounded-lg text-sm text-blue-800">
                                            <i class="fas fa-note-sticky mr-1"></i>
                                            <?php echo safeText($row['note']); ?>
                                        </div>
                                    <?php endif; ?>

                                    <div class="flex flex-wrap items-center gap-2 pt-4 border-t">
                                        <a href="view-salary-slip.php?slip_id=<?php echo urlencode($slipId); ?>"
                                           class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm rounded-lg transition">
                                            <i class="fas fa-eye"></i>
                                            View Slip
                                        </a>

                                        <?php if ($workflowStatus === 'prepared'): ?>
                                            <button onclick="updateSlipFlow('<?php echo safeText($slipId); ?>', 'collect')"
                                                    class="inline-flex items-center gap-2 px-4 py-2 bg-green-600 hover:bg-green-700 text-white text-sm rounded-lg transition">
                                                <i class="fas fa-hand-holding-dollar"></i>
                                                Collected
                                            </button>
                                        <?php elseif ($workflowStatus === 'collected'): ?>
                                            <button onclick="updateSlipFlow('<?php echo safeText($slipId); ?>', 'authorize')"
                                                    class="inline-flex items-center gap-2 px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white text-sm rounded-lg transition">
                                                <i class="fas fa-check-circle"></i>
                                                Authorize
                                            </button>
                                        <?php elseif ($workflowStatus === 'authorized'): ?>
                                            <span class="px-4 py-2 bg-emerald-100 text-emerald-700 text-sm rounded-lg font-semibold">
                                                <i class="fas fa-circle-check"></i>
                                                Authorized
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div id="salaryEmptyState" class="hidden text-center py-12">
                        <i class="fas fa-search text-4xl text-gray-300 mb-4"></i>
                        <h3 class="text-lg font-semibold text-gray-800">No matching records</h3>
                        <p class="text-gray-500">Try changing your search or filter.</p>
                    </div>
                <?php else: ?>
                    <div class="text-center py-12">
                        <i class="fas fa-file-invoice text-4xl text-gray-300 mb-4"></i>
                        <h3 class="text-lg font-medium text-gray-900 mb-2">No Payment Records</h3>
                        <p class="text-gray-600">No payment has been generated for this employee yet.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</main>

<?php include '../elements/floating-menus.php'; ?>

<script src="../assets/js/script.js?time=<?php echo time(); ?>"></script>

<script>
const EPS_NET_SALARY = <?php echo json_encode($epsNetSalary); ?>;

document.addEventListener('DOMContentLoaded', function () {
    initAccountSelectorOverride();
    initSalaryHistoryFilters();
    handlePaymentTypeChange();
    calculatePaymentPreview();
});

function initAccountSelectorOverride() {
    if (typeof window.renderAccountDropdown !== 'function') {
        return;
    }

    window.renderAccountDropdown = function (list) {
        const accountDropdown = document.getElementById('accountDropdown');

        if (!accountDropdown) {
            return;
        }

        accountDropdown.innerHTML = '';

        if (!list.length) {
            accountDropdown.innerHTML =
                `<li class="px-4 py-3 text-center text-gray-500">No accounts found</li>`;
            return;
        }

        list.forEach(acc => {
            const li = document.createElement('li');
            li.className = "px-4 py-3 cursor-pointer hover:bg-purple-50 border-b last:border-b-0";

            li.innerHTML = `
                <div class="flex items-center">
                    <div class="w-8 h-8 bg-purple-600 rounded-full text-white flex items-center justify-center font-semibold">
                        ${acc.acc_name?.charAt(0).toUpperCase() ?? 'A'}
                    </div>
                    <div class="ml-3">
                        <div class="font-medium">${acc.acc_name ?? ''}</div>
                        <div class="text-xs text-gray-500">ID: ${acc.sys_id ?? ''}</div>
                    </div>
                </div>
            `;

            li.onclick = () => {
                const accountInput = document.getElementById('accountInput');
                const selectedAccountId = document.getElementById('selected_account_id');

                if (accountInput) {
                    accountInput.value = `${acc.sys_id} | ${acc.acc_name}`;
                }

                if (selectedAccountId) {
                    selectedAccountId.value = acc.sys_id;
                }

                accountDropdown.classList.add('hidden');
            };

            accountDropdown.appendChild(li);
        });
    };
}

function num(id) {
    return parseFloat(document.getElementById(id)?.value || 0) || 0;
}

function formatMoney(value) {
    return Number(value || 0).toLocaleString('en-US', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    });
}

function handlePaymentTypeChange() {
    const type = document.getElementById('payment_type')?.value || 'salary';
    const includeBase = document.getElementById('include_base_salary');

    const bonus = document.getElementById('bonus');
    const overtime = document.getElementById('overtime');
    const otherAllowances = document.getElementById('other_allowances');

    if (!includeBase) return;

    if (type === 'salary') {
        includeBase.checked = true;
    }

    if (type === 'bonus' || type === 'overtime' || type === 'allowance' || type === 'adjustment') {
        includeBase.checked = false;
    }

    if (bonus) bonus.placeholder = type === 'bonus' ? 'Enter bonus amount' : 'Enter bonus amount';
    if (overtime) overtime.placeholder = type === 'overtime' ? 'Enter overtime amount' : 'Enter overtime amount';
    if (otherAllowances) {
        otherAllowances.placeholder = type === 'adjustment'
            ? 'Enter adjustment amount'
            : 'Enter allowance amount';
    }

    calculatePaymentPreview();
}

function calculatePaymentPreview() {
    const includeBase = document.getElementById('include_base_salary')?.checked || false;

    const baseSalary = includeBase ? EPS_NET_SALARY : 0;
    const bonus = num('bonus');
    const overtime = num('overtime');
    const otherAllowances = num('other_allowances');

    const pf = num('pf');
    const loan = num('loan');
    const tax = num('tax');

    const additions = bonus + overtime + otherAllowances;
    const deductions = pf + loan + tax;
    const netPayable = baseSalary + additions - deductions;

    const previewBaseSalary = document.getElementById('previewBaseSalary');
    const previewAdditions = document.getElementById('previewAdditions');
    const previewDeductions = document.getElementById('previewDeductions');
    const previewNetPayable = document.getElementById('previewNetPayable');

    if (previewBaseSalary) previewBaseSalary.textContent = formatMoney(baseSalary);
    if (previewAdditions) previewAdditions.textContent = formatMoney(additions);
    if (previewDeductions) previewDeductions.textContent = formatMoney(deductions);
    if (previewNetPayable) previewNetPayable.textContent = formatMoney(netPayable);
}

function generatePayment() {
    const selectedAccountId = document.getElementById('selected_account_id')?.value || '';
    const includeBaseSalary = document.getElementById('include_base_salary')?.checked ? 1 : 0;

    const data = {
        eps_id: "<?php echo safeText($eps_id); ?>",
        payment_type: document.getElementById('payment_type')?.value || 'salary',
        include_base_salary: includeBaseSalary,
        bonus: document.getElementById('bonus')?.value || 0,
        overtime: document.getElementById('overtime')?.value || 0,
        other_allowances: document.getElementById('other_allowances')?.value || 0,
        pf: document.getElementById('pf')?.value || 0,
        loan: document.getElementById('loan')?.value || 0,
        tax: document.getElementById('tax')?.value || 0,
        from_account: selectedAccountId,
        month_of_salary: document.getElementById('month_of_salary')?.value || '',
        note: document.getElementById('note')?.value || '',
    };

    const baseSalary = data.include_base_salary ? EPS_NET_SALARY : 0;
    const additions =
        parseFloat(data.bonus || 0) +
        parseFloat(data.overtime || 0) +
        parseFloat(data.other_allowances || 0);

    const deductions =
        parseFloat(data.pf || 0) +
        parseFloat(data.loan || 0) +
        parseFloat(data.tax || 0);

    const netPayable = baseSalary + additions - deductions;

    if (!data.from_account) {
        alert('Please select a payment account');
        return;
    }

    if (!data.month_of_salary.trim()) {
        alert('Please enter payment month or reference month');
        return;
    }

    if (netPayable <= 0) {
        alert('Net payable amount must be greater than 0');
        return;
    }

    const button = document.querySelector('button[onclick="generatePayment()"]');
    const originalText = button.innerHTML;

    button.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Generating...';
    button.disabled = true;

    fetch("<?php echo safeText($ip_port); ?>api/eps/generate-salary.php", {
        method: "POST",
        headers: {
            "Content-Type": "application/json"
        },
        body: JSON.stringify(data)
    })
        .then(res => res.json())
        .then(res => {
            if (res.success) {
                alert("✓ Payment generated successfully");
                location.reload();
            } else {
                alert("✗ " + (res.message || "Failed to generate payment"));
            }
        })
        .catch(err => {
            console.error('Fetch error:', err);
            alert("✗ Server error occurred");
        })
        .finally(() => {
            button.innerHTML = originalText;
            button.disabled = false;
        });
}

function initSalaryHistoryFilters() {
    const searchInput = document.getElementById('salarySearchInput');
    const statusFilter = document.getElementById('salaryStatusFilter');
    const typeFilter = document.getElementById('paymentTypeFilter');
    const sortFilter = document.getElementById('salarySortFilter');
    const grid = document.getElementById('salaryGrid');
    const emptyState = document.getElementById('salaryEmptyState');

    if (!grid) {
        return;
    }

    const cards = Array.from(grid.querySelectorAll('.salary-card'));

    function applyFilters() {
        const search = (searchInput?.value || '').toLowerCase().trim();
        const status = statusFilter?.value || '';
        const paymentType = typeFilter?.value || '';
        const sort = sortFilter?.value || 'newest';

        let visibleCards = cards.filter(card => {
            const cardSearch = card.dataset.search || '';
            const cardStatus = card.dataset.status || '';
            const cardPaymentType = card.dataset.paymentType || '';

            const matchSearch = !search || cardSearch.includes(search);
            const matchStatus = !status || cardStatus === status;
            const matchType = !paymentType || cardPaymentType === paymentType;

            return matchSearch && matchStatus && matchType;
        });

        visibleCards.sort((a, b) => {
            const amountA = parseFloat(a.dataset.amount || 0);
            const amountB = parseFloat(b.dataset.amount || 0);

            const dateA = new Date(a.dataset.date || 0);
            const dateB = new Date(b.dataset.date || 0);

            if (sort === 'oldest') return dateA - dateB;
            if (sort === 'high') return amountB - amountA;
            if (sort === 'low') return amountA - amountB;

            return dateB - dateA;
        });

        cards.forEach(card => card.classList.add('hidden'));

        visibleCards.forEach(card => {
            card.classList.remove('hidden');
            grid.appendChild(card);
        });

        if (emptyState) {
            emptyState.classList.toggle('hidden', visibleCards.length > 0);
        }
    }

    searchInput?.addEventListener('input', applyFilters);
    statusFilter?.addEventListener('change', applyFilters);
    typeFilter?.addEventListener('change', applyFilters);
    sortFilter?.addEventListener('change', applyFilters);
}

function updateSlipFlow(slipId, action) {
    let message = action === 'collect'
        ? 'Confirm employee collected this payment?'
        : 'Confirm authorization for this payment?';

    if (!confirm(message)) {
        return;
    }

    fetch("<?php echo safeText($ip_port); ?>api/eps/update-slip-flow.php", {
        method: "POST",
        headers: {
            "Content-Type": "application/json"
        },
        body: JSON.stringify({
            slip_id: slipId,
            action: action
        })
    })
    .then(res => res.json())
    .then(res => {
        if (res.success) {
            alert('✓ ' + res.message);
            location.reload();
        } else {
            alert('✗ ' + res.message);
        }
    })
    .catch(err => {
        console.error(err);
        alert('Server error occurred');
    });
}
</script>

</body>
</html>