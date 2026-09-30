<?php
// PATH: /pages/edit-eps.php
// Edit an existing EPS (Employee Payroll Structure) record.
// Requires ?eps_id=<sys_id> in the query string.

include_once('./authenticate.php');
require_once __DIR__ . '/../server/db_connection.php';
require_once __DIR__ . '/../server/hrm_permissions.php';
requireHrm($pdo, 'eps_manage', false);

$ip_port = @file_get_contents('../ippath.txt');
if (empty($ip_port)) {
    $ip_port = "http://103.104.219.3:898";
}

$eps_id = $_GET['eps_id'] ?? '';
if (!$eps_id) {
    die('<div style="padding:60px;text-align:center;font-family:Arial,sans-serif;color:#666"><h2>Missing EPS ID</h2><a href="index-eps.php">← Back to EPS list</a></div>');
}

$getEpsApi       = $ip_port . "api/eps/get-eps.php?eps_id=" . urlencode($eps_id);
$updateEpsApi    = $ip_port . "api/eps/update-structure.php";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit EPS Structure</title>
    <link rel="icon" type="image/png" href="../assets/images/logo/round-logo.png" sizes="16x16">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        @keyframes slideIn { from { opacity:0; transform:translateY(-10px); } to { opacity:1; transform:translateY(0); } }
        .animate-slide-in { animation: slideIn 0.3s ease-out; }
        ::-webkit-scrollbar { width:6px; }
        ::-webkit-scrollbar-track { background:#f1f1f1; border-radius:3px; }
        ::-webkit-scrollbar-thumb { background:#c1c1c1; border-radius:3px; }
        .form-label { display:block; font-size:0.875rem; font-weight:500; color:#374151; margin-bottom:0.5rem; }
        .required-star { color:#ef4444; margin-left:0.25rem; }
        .form-input { width:100%; padding:0.625rem 1rem; border:1px solid #d1d5db; border-radius:0.5rem; transition:all 0.2s; }
        .form-input:focus { outline:none; border-color:#3b82f6; box-shadow:0 0 0 2px rgba(59,130,246,0.1); }
        .form-card { background:white; border-radius:0.5rem; border:1px solid #e5e7eb; padding:1.25rem; }
        .section-title { font-size:1.125rem; font-weight:600; color:#1f2937; margin-bottom:1rem; padding-bottom:0.75rem; border-bottom:1px solid #e5e7eb; }
        @media (max-width:768px) { main#mainContent { padding-left:0!important; padding-top:4rem!important; } }
    </style>
</head>
<body class="bg-gray-50 font-sans">
    <?php include '../elements/header.php'; ?>
    <?php include '../elements/aside.php'; ?>

    <main id="mainContent" class="pt-16 pl-0 lg:pl-64 lg:my-16 transition-all duration-300 h-full">
        <div class="p-4 md:p-6">
            <div class="bg-white rounded-lg shadow p-4 md:p-6">

                <!-- Header -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 md:p-6 mb-6">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <div class="w-10 h-10 bg-amber-100 rounded-lg flex items-center justify-center mr-3">
                                <i class="fas fa-edit text-amber-600 text-lg"></i>
                            </div>
                            <div>
                                <h1 class="text-xl md:text-2xl font-bold text-gray-800">Edit EPS Structure</h1>
                                <p class="text-gray-600 text-sm mt-1" id="empNameHeader">Loading employee data…</p>
                            </div>
                        </div>
                        <a href="javascript:history.back()" class="text-sm text-gray-500 hover:text-gray-700 flex items-center gap-1">
                            <i class="fas fa-arrow-left"></i> Back
                        </a>
                    </div>
                </div>

                <!-- Loading indicator -->
                <div id="loadingBlock" class="text-center py-12 text-gray-400">
                    <i class="fas fa-spinner fa-spin text-3xl mb-3"></i>
                    <p>Loading EPS data…</p>
                </div>

                <!-- Messages -->
                <div id="messageContainer" class="hidden mb-6 animate-slide-in">
                    <div id="successMessage" class="hidden bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg flex items-center gap-2">
                        <i class="fas fa-check-circle text-green-500"></i><span id="successText"></span>
                    </div>
                    <div id="errorMessage" class="hidden bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg flex items-center gap-2">
                        <i class="fas fa-times-circle text-red-500"></i><span id="errorText"></span>
                    </div>
                </div>

                <!-- Form (hidden until data loads) -->
                <form id="epsForm" class="space-y-6 hidden">
                    <input type="hidden" id="epsSysId" name="eps_sys_id">

                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        <div class="space-y-6">

                            <!-- Employee info (read-only) -->
                            <div class="form-card bg-gray-50">
                                <h3 class="section-title"><i class="fas fa-id-badge mr-2 text-blue-600"></i>Employee</h3>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label class="form-label">Employee Name</label>
                                        <input type="text" id="employeeNameDisplay" class="form-input bg-gray-100 cursor-not-allowed" readonly>
                                    </div>
                                    <div>
                                        <label class="form-label">New Effective Date <span class="required-star">*</span></label>
                                        <input type="date" name="effective_date" id="effectiveDate" class="form-input" required>
                                        <p class="text-xs text-amber-600 mt-1"><i class="fas fa-info-circle mr-1"></i>এই তারিখ থেকে নতুন salary কার্যকর হবে। পুরনো structure history তে সংরক্ষিত থাকবে।</p>
                                    </div>
                                </div>
                            </div>

                            <!-- Monthly Earnings -->
                            <div class="form-card border-l-4 border-l-green-500">
                                <h3 class="section-title text-green-700"><i class="fas fa-plus-circle mr-2"></i>Monthly Earnings</h3>

                                <!-- Gross quick-fill -->
                                <div class="mb-4 p-3 bg-green-50 border border-green-200 rounded-lg">
                                    <label class="form-label text-green-800 mb-1">
                                        <i class="fas fa-magic mr-1"></i> Enter Gross Salary to Auto-Distribute
                                        <span class="text-xs text-green-600 font-normal ml-1">(50% basic · 30% house rent · 10% medical · 10% conveyance)</span>
                                    </label>
                                    <div class="flex gap-2">
                                        <div class="relative flex-1">
                                            <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-green-600 font-bold">৳</span>
                                            <input type="number" id="grossInput" class="form-input pl-8 border-green-300 focus:border-green-500" placeholder="e.g. 27000" min="0" step="1" oninput="distributeFromGross()">
                                        </div>
                                        <button type="button" onclick="clearGrossDistribute()" class="px-3 py-2 text-xs text-gray-500 border border-gray-300 rounded-lg hover:bg-gray-50">Clear</button>
                                    </div>
                                </div>

                                <div class="space-y-4">
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <div>
                                            <label class="form-label">Basic Salary <span class="required-star">*</span></label>
                                            <div class="relative">
                                                <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-500">৳</span>
                                                <input type="number" name="basic_salary" class="form-input pl-8" placeholder="0.00" required min="0" step="0.01" oninput="calculateSalary(); clearGrossOnManual()">
                                            </div>
                                        </div>
                                        <div>
                                            <label class="form-label">House Rent</label>
                                            <div class="relative">
                                                <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-500">৳</span>
                                                <input type="number" name="house_rent" class="form-input pl-8" placeholder="0.00" min="0" step="0.01" oninput="calculateSalary(); clearGrossOnManual()">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <div>
                                            <label class="form-label">Medical Allowance</label>
                                            <div class="relative">
                                                <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-500">৳</span>
                                                <input type="number" name="medical_allowance" class="form-input pl-8" placeholder="0.00" min="0" step="0.01" oninput="calculateSalary(); clearGrossOnManual()">
                                            </div>
                                        </div>
                                        <div>
                                            <label class="form-label">Conveyance</label>
                                            <div class="relative">
                                                <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-500">৳</span>
                                                <input type="number" name="conveyance" class="form-input pl-8" placeholder="0.00" min="0" step="0.01" oninput="calculateSalary(); clearGrossOnManual()">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="space-y-6">
                            <!-- Deductions -->
                            <div class="form-card border-l-4 border-l-red-500">
                                <h3 class="section-title text-red-700"><i class="fas fa-minus-circle mr-2"></i>Monthly Deductions</h3>
                                <div class="space-y-4">
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <div>
                                            <label class="form-label">Provident Fund (PF)</label>
                                            <div class="relative">
                                                <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-500">৳</span>
                                                <input type="number" name="pf_deduction" class="form-input pl-8" placeholder="0.00" min="0" step="0.01" oninput="calculateSalary()">
                                            </div>
                                        </div>
                                        <div>
                                            <label class="form-label">Professional Tax</label>
                                            <div class="relative">
                                                <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-500">৳</span>
                                                <input type="number" name="tax_deduction" class="form-input pl-8" placeholder="0.00" min="0" step="0.01" oninput="calculateSalary()">
                                            </div>
                                        </div>
                                    </div>
                                    <div>
                                        <label class="form-label">Other Deductions (Insurance/Loan)</label>
                                        <div class="relative">
                                            <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-500">৳</span>
                                            <input type="number" name="other_deduction" class="form-input pl-8" placeholder="0.00" min="0" step="0.01" oninput="calculateSalary()">
                                        </div>
                                    </div>
                                    <div>
                                        <label class="form-label">Status</label>
                                        <select name="status" class="form-input">
                                            <option value="active">Active</option>
                                            <option value="inactive">Inactive</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <!-- Summary -->
                            <div class="bg-blue-900 rounded-xl p-6 text-white shadow-lg">
                                <h3 class="text-lg font-semibold mb-4 flex items-center">
                                    <i class="fas fa-calculator mr-2"></i> Salary Summary Preview
                                </h3>
                                <div class="space-y-3">
                                    <div class="flex justify-between text-blue-100">
                                        <span>Gross Earnings:</span>
                                        <span id="gross_display">৳ 0.00</span>
                                    </div>
                                    <div class="flex justify-between text-red-300 border-b border-blue-800 pb-2">
                                        <span>Total Deductions:</span>
                                        <span id="deduction_display">৳ 0.00</span>
                                    </div>
                                    <div class="flex justify-between text-xl font-bold pt-2">
                                        <span>Net Take-Home:</span>
                                        <span id="net_display" class="text-green-400">৳ 0.00</span>
                                    </div>
                                </div>
                                <p class="mt-4 text-xs text-blue-200 italic">* Real-time calculation based on your inputs above.</p>
                            </div>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="pt-6 border-t flex flex-col sm:flex-row justify-between items-center gap-4">
                        <p class="text-sm text-gray-500"><i class="fas fa-info-circle mr-1"></i>Fields marked with <span class="required-star">*</span> are required</p>
                        <div class="flex flex-wrap gap-3">
                            <a href="javascript:history.back()" class="px-6 py-2 border border-gray-300 rounded-md text-gray-700 bg-white hover:bg-gray-50 transition-colors">
                                <i class="fas fa-times mr-2"></i>Cancel
                            </a>
                            <button type="submit" class="px-6 py-2 border border-transparent rounded-md shadow-sm text-white bg-amber-600 hover:bg-amber-700 transition-colors">
                                <i class="fas fa-save mr-2"></i>Update Structure
                            </button>
                        </div>
                    </div>
                </form>

            </div>
        </div>
    </main>

    <script src="../assets/js/script.js?time=<?php echo time(); ?>"></script>
    <script>
    (function () {
        const EPS_ID         = "<?php echo htmlspecialchars($eps_id, ENT_QUOTES, 'UTF-8'); ?>";
        const getEpsApi      = "<?php echo $getEpsApi; ?>";
        const updateEpsApi   = "<?php echo $updateEpsApi; ?>";

        /* ===== LOAD DATA ===== */
        async function loadEps() {
            try {
                const res  = await fetch(getEpsApi);
                const json = await res.json();
                if (!json.success || !json.epsDetails) {
                    document.getElementById('loadingBlock').innerHTML =
                        '<p class="text-red-500">EPS record not found.</p>';
                    return;
                }
                const d = json.epsDetails;
                document.getElementById('epsSysId').value               = d.sys_id;
                document.getElementById('employeeNameDisplay').value    = d.employee_name || d.employee_id;
                document.getElementById('effectiveDate').value          = d.effective_date || '';
                document.querySelector('[name="basic_salary"]').value   = d.basic_salary   || '';
                document.querySelector('[name="house_rent"]').value     = d.house_rent      || '';
                document.querySelector('[name="medical_allowance"]').value = d.medical_allowance || '';
                document.querySelector('[name="conveyance"]').value     = d.conveyance      || '';
                document.querySelector('[name="pf_deduction"]').value   = d.pf_deduction    || '';
                document.querySelector('[name="tax_deduction"]').value  = d.tax_deduction   || '';
                document.querySelector('[name="other_deduction"]').value= d.other_deduction || '';
                document.querySelector('[name="status"]').value         = d.status          || 'active';
                document.getElementById('empNameHeader').textContent    = `Editing: ${d.employee_name || d.employee_id}`;
                calculateSalary();
                document.getElementById('loadingBlock').classList.add('hidden');
                document.getElementById('epsForm').classList.remove('hidden');
            } catch (err) {
                document.getElementById('loadingBlock').innerHTML =
                    `<p class="text-red-500">Failed to load EPS: ${err.message}</p>`;
            }
        }
        loadEps();

        /* ===== GROSS AUTO-DISTRIBUTE ===== */
        function distributeFromGross() {
            const gross = parseFloat(document.getElementById('grossInput').value) || 0;
            if (!gross) return;
            const set = (name, val) => { const el = document.querySelector(`[name="${name}"]`); if (el) el.value = val.toFixed(2); };
            set('basic_salary',      Math.round(gross * 0.50 * 100) / 100);
            set('house_rent',        Math.round(gross * 0.30 * 100) / 100);
            set('medical_allowance', Math.round(gross * 0.10 * 100) / 100);
            set('conveyance',        Math.round(gross * 0.10 * 100) / 100);
            calculateSalary();
        }
        function clearGrossOnManual() {
            document.getElementById('grossInput').value = '';
        }
        function clearGrossDistribute() {
            document.getElementById('grossInput').value = '';
            ['basic_salary','house_rent','medical_allowance','conveyance'].forEach(n => {
                const el = document.querySelector(`[name="${n}"]`); if (el) el.value = '';
            });
            calculateSalary();
        }
        // Expose for inline oninput handlers
        window.distributeFromGross  = distributeFromGross;
        window.clearGrossOnManual   = clearGrossOnManual;
        window.clearGrossDistribute = clearGrossDistribute;

        /* ===== SALARY CALC ===== */
        function calculateSalary() {
            const get = n => parseFloat(document.querySelector(`[name="${n}"]`)?.value) || 0;
            const gross = get('basic_salary') + get('house_rent') + get('medical_allowance') + get('conveyance');
            const deduction = get('pf_deduction') + get('tax_deduction') + get('other_deduction');
            document.getElementById('gross_display').textContent     = `৳ ${gross.toFixed(2)}`;
            document.getElementById('deduction_display').textContent = `৳ ${deduction.toFixed(2)}`;
            document.getElementById('net_display').textContent       = `৳ ${(gross - deduction).toFixed(2)}`;
        }
        window.calculateSalary = calculateSalary;

        /* ===== MESSAGES ===== */
        function showMessage(type, msg) {
            document.getElementById('messageContainer').classList.remove('hidden');
            const el = document.getElementById(type === 'success' ? 'successMessage' : 'errorMessage');
            el.classList.remove('hidden');
            document.getElementById(type === 'success' ? 'successText' : 'errorText').textContent = msg;
        }
        function hideMessages() {
            document.getElementById('messageContainer').classList.add('hidden');
            document.getElementById('successMessage').classList.add('hidden');
            document.getElementById('errorMessage').classList.add('hidden');
        }

        /* ===== SUBMIT ===== */
        document.getElementById('epsForm').addEventListener('submit', async e => {
            e.preventDefault();
            hideMessages();

            const data = {
                eps_sys_id:        document.getElementById('epsSysId').value,
                effective_date:    document.getElementById('effectiveDate').value,
                basic_salary:      +document.querySelector('[name="basic_salary"]').value,
                house_rent:        +document.querySelector('[name="house_rent"]').value    || 0,
                medical_allowance: +document.querySelector('[name="medical_allowance"]').value || 0,
                conveyance:        +document.querySelector('[name="conveyance"]').value    || 0,
                pf_deduction:      +document.querySelector('[name="pf_deduction"]').value  || 0,
                tax_deduction:     +document.querySelector('[name="tax_deduction"]').value || 0,
                other_deduction:   +document.querySelector('[name="other_deduction"]').value || 0,
                status:            document.querySelector('[name="status"]').value,
            };

            try {
                const res  = await fetch(updateEpsApi, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(data)
                });
                const json = await res.json();
                if (json.success) {
                    showMessage('success', json.message || 'EPS updated successfully');
                } else {
                    showMessage('error', json.message || 'Update failed');
                }
            } catch {
                showMessage('error', 'Network error — please try again');
            }
        });
    })();
    </script>
</body>
</html>