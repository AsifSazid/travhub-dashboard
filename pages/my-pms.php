<?php
include_once('./authenticate.php');

$ip_port = @file_get_contents('../ippath.txt');

if (empty($ip_port)) {
    $ip_port = "http://103.104.219.3:898/";
}

function safeText($value)
{
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My PMS</title>

    <link rel="icon" type="image/png" href="../assets/images/logo/round-logo.png" sizes="16x16">

    <script src="https://cdn.tailwindcss.com"></script>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>

<body class="bg-gray-50">

<?php include '../elements/header.php'; ?>
<?php include '../elements/aside.php'; ?>

<main id="mainContent" class="pt-16 pl-0 lg:pl-64 lg:my-16 transition-all duration-300">

    <div class="p-4 md:p-6">

        <!-- Header -->
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-gray-800">
                My Payment History
            </h1>

            <p class="text-gray-600">
                View your salary, bonus, overtime and allowance history.
            </p>
        </div>

        <!-- Summary -->
        <div id="summaryCards"
             class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">

            <div class="bg-white border border-gray-200 rounded-xl p-5 shadow-sm">
                <p class="text-sm text-gray-500">Total Records</p>
                <h3 id="totalRecords"
                    class="text-2xl font-bold text-gray-900 mt-1">0</h3>
            </div>

            <div class="bg-white border border-gray-200 rounded-xl p-5 shadow-sm">
                <p class="text-sm text-gray-500">Total Amount</p>
                <h3 id="totalAmount"
                    class="text-2xl font-bold text-green-700 mt-1">0.00</h3>
            </div>

            <div class="bg-white border border-gray-200 rounded-xl p-5 shadow-sm">
                <p class="text-sm text-gray-500">Prepared</p>
                <h3 id="preparedCount"
                    class="text-2xl font-bold text-yellow-700 mt-1">0</h3>
            </div>

            <div class="bg-white border border-gray-200 rounded-xl p-5 shadow-sm">
                <p class="text-sm text-gray-500">Authorized</p>
                <h3 id="authorizedCount"
                    class="text-2xl font-bold text-emerald-700 mt-1">0</h3>
            </div>

        </div>

        <!-- Filter -->
        <div class="bg-white border border-gray-200 rounded-xl p-5 shadow-sm mb-6">

            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">

                <input type="text"
                       id="searchInput"
                       placeholder="Search payment..."
                       class="md:col-span-2 px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none">

                <select id="statusFilter"
                        class="px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none">
                    <option value="">All Status</option>
                    <option value="prepared">Prepared</option>
                    <option value="collected">Collected</option>
                    <option value="authorized">Authorized</option>
                </select>

                <select id="sortFilter"
                        class="px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none">
                    <option value="newest">Newest First</option>
                    <option value="oldest">Oldest First</option>
                    <option value="high">Highest Amount</option>
                    <option value="low">Lowest Amount</option>
                </select>

            </div>
        </div>

        <!-- Loader -->
        <div id="loader"
             class="bg-white border border-gray-200 rounded-xl p-10 text-center shadow-sm">

            <i class="fas fa-spinner fa-spin text-3xl text-blue-600 mb-3"></i>

            <p class="text-gray-600">
                Loading payment history...
            </p>
        </div>

        <!-- Empty -->
        <div id="emptyState"
             class="hidden bg-white border border-gray-200 rounded-xl p-10 text-center shadow-sm">

            <i class="fas fa-file-invoice text-4xl text-gray-300 mb-4"></i>

            <h3 class="text-lg font-semibold text-gray-800">
                No Payment Records Found
            </h3>

            <p class="text-gray-500 mt-1">
                No salary or payment record available.
            </p>
        </div>

        <!-- Grid -->
        <div id="paymentGrid"
             class="hidden grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-5">
        </div>

    </div>

</main>

<?php include '../elements/floating-menus.php'; ?>

<script src="../assets/js/script.js?time=<?php echo time(); ?>"></script>

<script>
const API_URL = "<?php echo safeText($ip_port); ?>api/eps/my-salary-history.php";

let ALL_PAYMENTS = [];

document.addEventListener('DOMContentLoaded', () => {
    loadMyPayments();

    document.getElementById('searchInput')
        .addEventListener('input', renderFilteredPayments);

    document.getElementById('statusFilter')
        .addEventListener('change', renderFilteredPayments);

    document.getElementById('sortFilter')
        .addEventListener('change', renderFilteredPayments);
});

function money(value) {
    return Number(value || 0).toLocaleString('en-US', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    });
}

function paymentTypeLabel(type) {

    switch (type) {

        case 'salary':
            return 'Salary';

        case 'bonus':
            return 'Bonus';

        case 'overtime':
            return 'Overtime';

        case 'allowance':
            return 'Allowance';

        case 'adjustment':
            return 'Adjustment';

        case 'custom':
            return 'Custom';

        default:
            return 'Payment';
    }
}

function workflowClass(status) {

    switch (status) {

        case 'authorized':
            return 'bg-emerald-100 text-emerald-700 border-emerald-200';

        case 'collected':
            return 'bg-purple-100 text-purple-700 border-purple-200';

        default:
            return 'bg-yellow-100 text-yellow-700 border-yellow-200';
    }
}

async function loadMyPayments() {

    try {

        const response = await fetch(API_URL);
        const result = await response.json();

        document.getElementById('loader').classList.add('hidden');

        if (!result.success) {

            document.getElementById('emptyState').classList.remove('hidden');

            return;
        }

        ALL_PAYMENTS = result.data || [];

        updateSummaryCards(ALL_PAYMENTS);

        if (!ALL_PAYMENTS.length) {

            document.getElementById('emptyState').classList.remove('hidden');

            return;
        }

        document.getElementById('paymentGrid').classList.remove('hidden');

        renderFilteredPayments();

    } catch (error) {

        console.error(error);

        document.getElementById('loader').classList.add('hidden');

        document.getElementById('emptyState').classList.remove('hidden');
    }
}

function updateSummaryCards(data) {

    let totalAmount = 0;
    let prepared = 0;
    let authorized = 0;

    data.forEach(row => {

        totalAmount += parseFloat(row.net_payable_salary || 0);

        const flow = (row.workflow_status || 'prepared').toLowerCase();

        if (flow === 'authorized') {
            authorized++;
        } else {
            prepared++;
        }
    });

    document.getElementById('totalRecords').innerText = data.length;
    document.getElementById('totalAmount').innerText = money(totalAmount);
    document.getElementById('preparedCount').innerText = prepared;
    document.getElementById('authorizedCount').innerText = authorized;
}

function renderFilteredPayments() {

    const search = document.getElementById('searchInput')
        .value
        .toLowerCase()
        .trim();

    const status = document.getElementById('statusFilter').value;

    const sort = document.getElementById('sortFilter').value;

    let filtered = [...ALL_PAYMENTS];

    filtered = filtered.filter(item => {

        const searchable = `
            ${item.slip_id || ''}
            ${item.salary_month || ''}
            ${item.payment_type || ''}
            ${item.workflow_status || ''}
        `.toLowerCase();

        const matchSearch = !search || searchable.includes(search);

        const matchStatus =
            !status ||
            (item.workflow_status || '').toLowerCase() === status;

        return matchSearch && matchStatus;
    });

    filtered.sort((a, b) => {

        const amountA = parseFloat(a.net_payable_salary || 0);
        const amountB = parseFloat(b.net_payable_salary || 0);

        const dateA = new Date(a.payment_date || 0);
        const dateB = new Date(b.payment_date || 0);

        if (sort === 'oldest') {
            return dateA - dateB;
        }

        if (sort === 'high') {
            return amountB - amountA;
        }

        if (sort === 'low') {
            return amountA - amountB;
        }

        return dateB - dateA;
    });

    renderPaymentCards(filtered);
}

function renderPaymentCards(data) {

    const grid = document.getElementById('paymentGrid');

    grid.innerHTML = '';

    if (!data.length) {

        grid.classList.add('hidden');

        document.getElementById('emptyState')
            .classList.remove('hidden');

        return;
    }

    document.getElementById('emptyState')
        .classList.add('hidden');

    grid.classList.remove('hidden');

    data.forEach(row => {

        const workflow = (row.status || 'prepared').toLowerCase();

        const workflowBadge = workflowClass(workflow);

        const amount = parseFloat(row.net_payable_salary || 0);

        const additions =
            parseFloat(row.bonus || 0) +
            parseFloat(row.overtime || 0) +
            parseFloat(row.allowances || 0);

        const deduction =
            parseFloat(row.total_deduction || 0);

        const paymentType =
            paymentTypeLabel(row.payment_type || 'salary');

        const card = document.createElement('div');

        card.className =
            'rounded-2xl border border-gray-200 bg-white shadow-sm hover:shadow-md transition overflow-hidden';

        card.innerHTML = `
            <div class="p-5">

                <div class="flex items-start justify-between mb-4">

                    <div>
                        <p class="text-xs text-gray-500">
                            Slip ID
                        </p>

                        <h3 class="font-bold text-gray-900">
                            ${row.slip_id || ''}
                        </h3>
                    </div>

                    <div>
                        <span class="inline-block px-3 py-1 text-xs font-semibold rounded-full bg-blue-50 text-blue-700 border border-blue-100 mb-2 me-2">
                            ${paymentType}
                        </span>
    
                        <span class="px-3 py-1 text-xs font-semibold rounded-full border ${workflowBadge}">
                            ${workflow.toUpperCase()}
                        </span>
                    </div>

                </div>

                <div class="mb-4">

                    <p class="text-sm text-gray-500">
                        Net Payable Amount
                    </p>

                    <h2 class="text-3xl font-bold text-gray-900">
                        ${money(amount)}
                    </h2>

                </div>

                <div class="grid grid-cols-2 gap-3 text-sm mb-4">

                    <div class="bg-gray-50 p-3 rounded-lg">
                        <p class="text-gray-500">Month</p>

                        <p class="font-semibold text-gray-800">
                            ${row.salary_month || ''}
                        </p>
                    </div>

                    <div class="bg-gray-50 p-3 rounded-lg">
                        <p class="text-gray-500">Date</p>

                        <p class="font-semibold text-gray-800">
                            ${row.payment_date || ''}
                        </p>
                    </div>

                    <div class="bg-green-50 p-3 rounded-lg">
                        <p class="text-green-600">Additions</p>

                        <p class="font-semibold text-green-800">
                            ${money(additions)}
                        </p>
                    </div>

                    <div class="bg-red-50 p-3 rounded-lg">
                        <p class="text-red-600">Deduction</p>

                        <p class="font-semibold text-red-800">
                            ${money(deduction)}
                        </p>
                    </div>

                </div>

                ${
                    row.note
                    ?
                    `
                    <div class="mb-4 p-3 bg-blue-50 rounded-lg text-sm text-blue-800">
                        <i class="fas fa-note-sticky mr-1"></i>
                        ${row.note}
                    </div>
                    `
                    :
                    ''
                }

                <div class="flex flex-wrap items-center gap-2 pt-4 border-t">

                    <a href="view-salary-slip.php?slip_id=${encodeURIComponent(row.slip_id || '')}"
                       class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm rounded-lg transition">

                        <i class="fas fa-eye"></i>
                        View Slip

                    </a>

                    ${
                        workflow === 'prepared'
                        ?
                        `
                        <button onclick="collectPayment('${row.slip_id}')"
                                class="inline-flex items-center gap-2 px-4 py-2 bg-green-600 hover:bg-green-700 text-white text-sm rounded-lg transition">

                            <i class="fas fa-hand-holding-dollar"></i>
                            Mark Collected

                        </button>
                        `
                        :
                        workflow === 'collected'
                        ?
                        `
                        <span class="px-4 py-2 bg-purple-100 text-purple-700 text-sm rounded-lg font-semibold">
                            Waiting Authorization
                        </span>
                        `
                        :
                        `
                        <span class="px-4 py-2 bg-emerald-100 text-emerald-700 text-sm rounded-lg font-semibold">
                            Authorized
                        </span>
                        `
                    }

                </div>

            </div>
        `;

        grid.appendChild(card);
    });
}

async function collectPayment(slipId) {

    const confirmed = confirm(
        'Confirm that you collected this payment?'
    );

    if (!confirmed) {
        return;
    }

    try {

        const response = await fetch(
            "<?php echo safeText($ip_port); ?>api/eps/update-slip-flow.php",
            {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    slip_id: slipId,
                    action: 'collect'
                })
            }
        );

        const result = await response.json();

        if (result.success) {

            alert('✓ Payment collected successfully');

            loadMyPayments();

        } else {

            alert('✗ ' + (result.message || 'Failed'));
        }

    } catch (error) {

        console.error(error);

        alert('Server error occurred');
    }
}
</script>

</body>
</html>