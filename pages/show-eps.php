<?php
include_once('./authenticate.php');

$ip_port = @file_get_contents('../ippath.txt');
if (empty($ip_port)) {
    $ip_port = "http://103.104.219.3:898/";
}

if (!isset($_GET['eps_id'])) {
    die('EPS ID missing');
}

$eps_id = $_GET['eps_id'];

$singleEpsApi     = $ip_port . "api/eps/get-eps.php?eps_id=" . urlencode($eps_id);
$salaryHistoryApi = $ip_port . "api/eps/salary-history.php?eps_id=" . urlencode($eps_id);

/* Fetch EPS + Employee + Structure */
$epsData = json_decode(@file_get_contents($singleEpsApi), true);

/* Fetch Salary History */
$salaryHistory = json_decode(@file_get_contents($salaryHistoryApi), true);

if (!$epsData || !$epsData['success']) {
    die('Failed to load EPS data');
}

$emp = $epsData['epsDetails'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employee EPS - <?php echo htmlspecialchars($employeeId ?? 'Employee'); ?></title>
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
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-gray-800">Salary Management</h1>
            <p class="text-gray-600">Generate salary and view history for <?php echo htmlspecialchars($emp['employee_name']); ?></p>
        </div>

        <div class="space-y-6">
            <!-- Employee Information Card -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <h2 class="text-lg font-semibold text-gray-800 mb-4 pb-3 border-b">Employee Information</h2>
                
                <div class="mb-4">
                    <h3 class="text-xl font-medium text-gray-900"><?php echo htmlspecialchars($emp['employee_name']); ?></h3>
                    <p class="text-gray-600">Salary Structure Details</p>
                </div>
                
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    <div class="bg-gray-50 p-4 rounded-lg">
                        <div class="text-sm text-gray-500">Basic Salary</div>
                        <div class="text-lg font-semibold text-gray-800"><?php echo $emp['basic_salary']; ?></div>
                    </div>
                    <div class="bg-gray-50 p-4 rounded-lg">
                        <div class="text-sm text-gray-500">House Rent</div>
                        <div class="text-lg font-semibold text-gray-800"><?php echo $emp['house_rent']; ?></div>
                    </div>
                    <div class="bg-gray-50 p-4 rounded-lg">
                        <div class="text-sm text-gray-500">Medical Allowance</div>
                        <div class="text-lg font-semibold text-gray-800"><?php echo $emp['medical_allowance']; ?></div>
                    </div>
                    <div class="bg-gray-50 p-4 rounded-lg">
                        <div class="text-sm text-gray-500">Conveyance</div>
                        <div class="text-lg font-semibold text-gray-800"><?php echo $emp['conveyance']; ?></div>
                    </div>
                </div>
            </div>

            <!-- Salary Generation Section -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <h2 class="text-lg font-semibold text-gray-800 mb-6 pb-3 border-b">Salary Generation</h2>
                
                <div class="grid md:grid-cols-2 gap-8">
                    <!-- Additions Section -->
                    <div>
                        <h3 class="text-md font-medium text-gray-800 mb-4">Additions</h3>
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Bonus</label>
                                <input type="number" id="bonus" placeholder="Enter bonus amount" 
                                       class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Overtime</label>
                                <input type="number" id="overtime" placeholder="Enter overtime amount" 
                                       class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition">
                            </div>
                        </div>
                    </div>
                    
                    <!-- Deductions Section -->
                    <div>
                        <h3 class="text-md font-medium text-gray-800 mb-4">Deductions</h3>
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Provident Fund (PF)</label>
                                <input type="number" id="pf" placeholder="Enter PF amount" 
                                       class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Loan</label>
                                <input type="number" id="loan" placeholder="Enter loan amount" 
                                       class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Tax</label>
                                <input type="number" id="tax" placeholder="Enter tax amount" 
                                       class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Payment Account Selection -->
                <div class="mt-8 pt-6 border-t">
                    <h3 class="text-md font-medium text-gray-800 mb-4">Payment Information</h3>
                    <div class="grid md:grid-cols-2 gap-8">
                        <div class="col-span1">
                            <div class="mb-4">
                                <label for="accountInput" class="block text-sm font-medium text-gray-700 mb-1">Accounts</label>
                                <?php include('./form-selects/accounts.php'); ?>
                                <!-- Hidden field to store the selected account ID -->
                                <input type="hidden" id="selected_account_id" value="">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Month of Salary</label>
                                <input type="text" id="month" placeholder="Enter Month Name and Year" 
                                       class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition">
                            </div>
                        </div>
                        <div>
                            
                        </div>
                    </div>
                </div>

                <!-- Generate Button -->
                <div class="mt-8 flex justify-end">
                    <button onclick="generateSalary()"
                            class="px-8 py-3 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg shadow-sm hover:shadow-md transition duration-200">
                        Generate Salary
                    </button>
                </div>
            </div>

            <!-- Salary History Section -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <h2 class="text-lg font-semibold text-gray-800 mb-6 pb-3 border-b">Salary History</h2>
                
                <?php if (!empty($salaryHistory['data'])): ?>
                    <div class="overflow-x-auto rounded-lg border border-gray-200">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Slip ID</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Net Salary</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Deduction</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Payment Date</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Month</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Action</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php foreach ($salaryHistory['data'] as $row): ?>
                                <tr class="hover:bg-gray-50 transition">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900"><?php echo $row['slip_id']; ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?php echo $row['net_salary']; ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?php echo $row['total_deduction']; ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600"><?php echo $row['payment_date']; ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600"><?php echo $row['salary_month']; ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <?php 
                                        $statusColor = match(strtolower($row['status'])) {
                                            'paid' => 'bg-green-100 text-green-800',
                                            'pending' => 'bg-yellow-100 text-yellow-800',
                                            'cancelled' => 'bg-red-100 text-red-800',
                                            default => 'bg-gray-100 text-gray-800'
                                        };
                                        ?>
                                        <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo $statusColor; ?>">
                                            <?php echo strtoupper($row['status']); ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                                        <a href="view-slip.php?slip_id=<?php echo $row['slip_id']; ?>"
                                           class="text-blue-600 hover:text-blue-900 font-medium hover:underline">
                                            View Slip
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="text-center py-12">
                        <div class="mx-auto w-16 h-16 mb-4 text-gray-400">
                            <i class="fas fa-file-invoice text-4xl"></i>
                        </div>
                        <h3 class="text-lg font-medium text-gray-900 mb-2">No Salary Records</h3>
                        <p class="text-gray-600">No salary has been generated for this employee yet.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</main>

<?php include '../elements/floating-menus.php'; ?>

<script src="../assets/js/script.js?time=<?php echo time(); ?>"></script>

<script>
// Override the account selection logic from accounts.php
document.addEventListener('DOMContentLoaded', function() {
    // Store the original onclick handler
    const originalOnclickHandler = window.renderAccountDropdown;
    
    // Override the renderAccountDropdown function
    if (typeof window.renderAccountDropdown === 'function') {
        const originalRender = window.renderAccountDropdown;
        
        window.renderAccountDropdown = function(list) {
            // Call the original function
            const accountDropdown = document.getElementById('accountDropdown');
            accountDropdown.innerHTML = '';
            
            if (!list.length) {
                accountDropdown.innerHTML =
                    `<li class="px-4 py-3 text-center text-gray-500">No accounts found</li>`;
                return;
            }

            list.forEach(acc => {
                const li = document.createElement('li');
                li.className =
                    "px-4 py-3 cursor-pointer hover:bg-purple-50 border-b last:border-b-0";

                li.innerHTML = `
                    <div class="flex items-center">
                        <div class="w-8 h-8 bg-purple-600 rounded-full text-white flex items-center justify-center font-semibold">
                            ${acc.acc_name?.charAt(0).toUpperCase() ?? 'A'}
                        </div>
                        <div class="ml-3">
                            <div class="font-medium">${acc.acc_name}</div>
                            <div class="text-xs text-gray-500">ID: ${acc.sys_id}</div>
                        </div>
                    </div>
                `;

                li.onclick = () => {
                    const accountInput = document.getElementById('accountInput');
                    accountInput.value = `${acc.sys_id} | ${acc.acc_name}`;
                    // Store the selected account ID in the hidden field
                    document.getElementById('selected_account_id').value = acc.sys_id;
                    accountDropdown.classList.add('hidden');
                };

                accountDropdown.appendChild(li);
            });
        };
    }
});

function generateSalary() {
    // Get the selected account ID from the hidden field
    const selectedAccountId = document.getElementById('selected_account_id').value;
    
    const data = {
        eps_id: "<?php echo $eps_id; ?>",
        bonus: document.getElementById('bonus').value || 0,
        overtime: document.getElementById('overtime').value || 0,
        pf: document.getElementById('pf').value || 0,
        loan: document.getElementById('loan').value || 0,
        tax: document.getElementById('tax').value || 0,
        from_account: selectedAccountId
    };

    console.log('Sending data:', data);

    // Validate required fields
    if (!data.from_account) {
        alert('Please select a payment account');
        return;
    }

    // Show loading state
    const button = document.querySelector('button[onclick="generateSalary()"]');
    const originalText = button.textContent;
    button.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Generating...';
    button.disabled = true;

    fetch("<?php echo $ip_port; ?>api/eps/generate-salary.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify(data)
    })
    .then(res => res.json())
    .then(res => {
        console.log('Response:', res);
        if (res.success) {
            alert("✓ Salary generated successfully");
            location.reload();
        } else {
            alert("✗ " + (res.message || "Failed to generate salary"));
        }
    })
    .catch(err => {
        console.error('Fetch error:', err);
        alert("✗ Server error occurred");
    })
    .finally(() => {
        // Reset button state
        button.textContent = originalText;
        button.disabled = false;
    });
}
</script>

</body>
</html>