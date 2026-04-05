
<!--Financial Transactions & Notification-->
<div class="grid grid-cols-6 md:grid-cols-4 gap-4">
    <!-- ================= ACTION BUTTONS ================= -->
    <div class="col-span-4 md:col-span-3 px-4 py-6">

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
    
            <!-- Conveyance -->
            <button class="group bg-gradient-to-br from-purple-50 to-purple-100 border border-purple-200 hover:border-purple-400 p-5 rounded-xl text-center transition-all hover:shadow-lg hover:-translate-y-1">
                <div class="bg-purple-100 group-hover:bg-purple-200 w-12 h-12 rounded-lg flex items-center justify-center mx-auto mb-3">
                    <i class="fa-solid fa-money-bill-transfer text-purple-600 text-xl"></i>
                </div>
                <p class="font-semibold text-purple-800">Conveyance</p>
                <p class="text-xs text-purple-600 mt-1">Conveyance Bill</p>
            </button>
    
            <!-- Loan -->
            <a href="index-petty.php?type=loan"
               class="group bg-gradient-to-br from-yellow-50 to-yellow-100 border border-yellow-200 hover:border-yellow-400 p-5 rounded-xl text-center transition-all hover:shadow-lg hover:-translate-y-1">
                <div class="bg-yellow-100 group-hover:bg-yellow-200 w-12 h-12 rounded-lg flex items-center justify-center mx-auto mb-3">
                    <i class="fa-solid fa-hand-holding-dollar text-yellow-600 text-xl"></i>
                </div>
                <p class="font-semibold text-yellow-800">Loan</p>
                <p class="text-xs text-yellow-600 mt-1">Request for Loan</p>
            </a>
    
            <!-- Petty Cash -->
            <button class="group bg-gradient-to-br from-indigo-50 to-indigo-100 border border-indigo-200 hover:border-indigo-400 p-5 rounded-xl text-center transition-all hover:shadow-lg hover:-translate-y-1">
                <div class="bg-indigo-100 group-hover:bg-indigo-200 w-12 h-12 rounded-lg flex items-center justify-center mx-auto mb-3">
                    <i class="fas fa-credit-card text-indigo-600 text-xl"></i>
                </div>
                <p class="font-semibold text-indigo-800">Petty Cash</p>
                <p class="text-xs text-indigo-600 mt-1">Make Payment</p>
            </button>
    
        </div>
    </div>
    
    <div class="bg-white rounded-lg shadow p-4 flex flex-col mt-4">
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-4 gap-3">
            <h2 class="text-2xl font-semibold text-gray-800">Summary</h2>
        </div>
        <!-- ================= SUMMARY CARDS ================= -->
        <div class="grid grid-cols-2 md:grid-cols-2 gap-4">
            <div class="group bg-gradient-to-br from-purple-50 to-purple-100 border border-purple-200 hover:border-purple-400 p-4 rounded-xl text-center transition-all hover:shadow-lg hover:-translate-y-1">
                <p id="total-trnx" class="text-2xl font-bold text-purple-800">1</p>
                <p class="text-xs text-purple-600 mt-1 uppercase tracking-wide">Total Trnx</p>
            </div>
    
            <div class="group bg-gradient-to-br from-green-50 to-green-100 border border-green-200 hover:border-green-400 p-4 rounded-xl text-center transition-all hover:shadow-lg hover:-translate-y-1">
                <p id="total-credit" class="text-2xl font-bold text-green-800">1</p>
                <p class="text-xs text-green-600 mt-1 uppercase tracking-wide">Total Credit</p>
            </div>
    
            <div class="group bg-gradient-to-br from-red-50 to-red-100 border border-red-200 hover:border-red-400 p-4 rounded-xl text-center transition-all hover:shadow-lg hover:-translate-y-1">
                <p id="total-debit" class="text-2xl font-bold text-red-800">1</p>
                <p class="text-xs text-red-600 mt-1 uppercase tracking-wide">Total Debit</p>
            </div>
    
            <div class="group bg-gradient-to-br from-blue-50 to-blue-100 border border-blue-200 hover:border-blue-400 p-4 rounded-xl text-center transition-all hover:shadow-lg hover:-translate-y-1">
                <p id="total-balance" class="text-2xl font-bold text-blue-800">1</p>
                <p class="text-xs text-blue-600 mt-1 uppercase tracking-wide">Net Balance</p>
            </div>
        </div>
    </div>
    
    <!--Financial Transactions-->
    <div class="col-span-4 md:col-span-3 bg-white rounded-lg shadow p-4 flex flex-col mt-4">
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-4 gap-3">
            <h2 class="text-2xl font-semibold text-gray-800">Employee Financial Transactions</h2>
            
            <!-- Search and Filter Section -->
            <div class="flex flex-col sm:flex-row gap-3 w-full md:w-auto">
                <!-- Search Input -->
                <div class="relative flex-1 sm:flex-none">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <i class="fas fa-search text-gray-400"></i>
                    </div>
                    <input 
                        type="text" 
                        id="searchInput"
                        class="pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 w-full"
                        placeholder="Search employee transactions..."
                    >
                </div>
                
                <!-- Type Filter -->
                <div class="relative">
                    <select 
                        id="filterType" 
                        class="appearance-none pl-4 pr-10 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 w-full"
                    >
                        <option value="all">All Types</option>
                        <option value="credit">Credit</option>
                        <option value="debit">Debit</option>
                    </select>
                    <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                        <i class="fas fa-chevron-down text-gray-400"></i>
                    </div>
                </div>
                
                <!-- Reset Button -->
                <button 
                    id="resetFilters"
                    class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors flex items-center justify-center whitespace-nowrap"
                >
                    <i class="fas fa-redo mr-2"></i>Reset
                </button>
            </div>
        </div>
    
        <div class="overflow-x-auto table-container">
            <table id="finTable" class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Purpose</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Running Balance</th>
                    </tr>
                </thead>
                <tbody id="finTableBody" class="bg-white divide-y divide-gray-200 text-left">
                </tbody>
                <!-- Total Row -->
                <tfoot class="bg-gray-50 font-semibold">
                    <tr>
                        <td colspan="4" class="px-6 py-4 text-right text-sm text-gray-700">Final Balance:</td>
                        <td id="final-balance" class="px-6 py-4 text-sm text-blue-700 font-bold">0.00</td>
                    </tr>
                </tfoot>
            </table>
            
            <!-- No Results Message -->
            <div id="noResultsMessage" class="hidden px-6 py-10 text-center text-gray-500">
                <div class="flex flex-col items-center gap-2">
                    <i class="fa-solid fa-bangladeshi-taka-sign text-3xl text-gray-400"></i>
                    <p class="text-sm">No employee transactions match your search criteria</p>
                </div>
            </div>
            
            <!-- Load More Button -->
            <div id="loadMoreContainer" class="hidden mt-4 text-center">
                <button 
                    id="loadMoreBtn"
                    class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors"
                >
                    <i class="fas fa-spinner fa-spin hidden mr-2" id="loadMoreSpinner"></i>
                    Load More
                </button>
            </div>
        </div>
    </div>
    
    <!--Notificartions-->
    <div class="bg-white rounded-lg shadow p-4 flex flex-col mt-4">
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-4 gap-3">
            <h2 class="text-2xl font-semibold text-gray-800">Notifications</h2>
        </div>
    
        <div class="overflow-x-auto table-container">
            <table id="notificationTable" class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">EMP ID</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Purpose</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody id="notificationTableBody" class="bg-white divide-y divide-gray-200 text-left">
                </tbody>
            </table>
            
            <!-- No Results Message -->
            <div id="noResultsMessage" class="hidden px-6 py-10 text-center text-gray-500">
                <div class="flex flex-col items-center gap-2">
                    <i class="fa-solid fa-bangladeshi-taka-sign text-3xl text-gray-400"></i>
                    <p class="text-sm">No employee transactions match your search criteria</p>
                </div>
            </div>
            
            <!-- Load More Button -->
            <div id="loadMoreContainer" class="hidden mt-4 text-center">
                <button 
                    id="loadMoreBtn"
                    class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors"
                >
                    <i class="fas fa-spinner fa-spin hidden mr-2" id="loadMoreSpinner"></i>
                    Load More
                </button>
            </div>
        </div>
    </div>

</div>


<script>
/* ===============================
   API URLS
=================================*/
const GET_FINANCIAL_STMT_BY_EMPLOYEE_API = "<?php echo $getEmployeeFinEntriesApi; ?>";
const GET_FINANCIAL_NOTIFICATION = "<?php echo $getNotificationApi; ?>";

/* ===============================
   FINANCIAL VARIABLES
=================================*/
let originalFinStmts = [];
let displayedFinStmts = [];
let currentOffset = 0;
const incrementAmount = 5;
let isFiltering = false;
let runningBalances = new Map();
let finalBalance = 0;

/* ===============================
   NOTIFICATION VARIABLES
=================================*/
let originalNotifications = [];
let displayedNotifications = [];

/* ===============================
   DOM ELEMENTS
=================================*/
const finTableBody = document.getElementById('finTableBody');
const notificationTableBody = document.getElementById('notificationTableBody');
const searchInput = document.getElementById('searchInput');
const filterType = document.getElementById('filterType');
const resetFilters = document.getElementById('resetFilters');
const loadMoreContainer = document.getElementById('loadMoreContainer');
const loadMoreBtn = document.getElementById('loadMoreBtn');
const loadMoreSpinner = document.getElementById('loadMoreSpinner');
const finalBalanceEl = document.getElementById('final-balance');

/* ===============================
   LOAD FINANCIAL DATA
=================================*/
function reloadFinancialTable() {
    fetch(GET_FINANCIAL_STMT_BY_EMPLOYEE_API)
        .then(res => res.json())
        .then(data => {
            if (!data.success) return;

            originalFinStmts = data.finStmts.sort((a, b) => {
                const dateA = new Date(a.date);
                const dateB = new Date(b.date);
                if (dateA.getTime() !== dateB.getTime()) {
                    return dateB - dateA;
                }
                return b.id - a.id;
            });

            calculateRunningBalances(originalFinStmts);

            currentOffset = 0;
            displayedFinStmts = originalFinStmts.slice(0, incrementAmount);

            renderFinTable(displayedFinStmts);
            updateSummary(originalFinStmts);
            toggleLoadMoreButton();
        });
}

/* ===============================
   LOAD NOTIFICATIONS
=================================*/
function reloadFinNotificationTable() {
    fetch(GET_FINANCIAL_NOTIFICATION)
        .then(res => res.json())
        .then(data => {
            if (!data.success) return;

            originalNotifications = data.finStmts.sort((a, b) => {
                const dateA = new Date(a.date);
                const dateB = new Date(b.date);
                if (dateA.getTime() !== dateB.getTime()) {
                    return dateB - dateA;
                }
                return b.id - a.id;
            });

            displayedNotifications = originalNotifications.slice(0, 5);
            renderNotificationTable(displayedNotifications);
        });
}

/* ===============================
   RUNNING BALANCE CALCULATION
=================================*/
function calculateRunningBalances(list) {

    const sorted = [...list].sort((a, b) => {
        const dateA = new Date(a.date);
        const dateB = new Date(b.date);
        if (dateA.getTime() !== dateB.getTime()) {
            return dateA - dateB;
        }
        return a.id - b.id;
    });

    let balance = 0;
    runningBalances.clear();

    sorted.forEach(entry => {
        const type = (entry.type || '').toLowerCase();
        const amount = Number(entry.amount) || 0;

        if (type === 'credit') balance += amount;
        if (type === 'debit') balance -= amount;

        runningBalances.set(entry.id, balance);
    });

    finalBalance = balance;
}

/* ===============================
   RENDER FINANCIAL TABLE
=================================*/
function renderFinTable(list) {

    finTableBody.innerHTML = '';

    if (!list.length) {
        finTableBody.innerHTML = `
            <tr>
                <td colspan="5" class="text-center py-6 text-gray-500">
                    No Transaction Found!
                </td>
            </tr>`;
        finalBalanceEl.textContent = "0.00";
        return;
    }

    list.forEach(entry => {

        const type = (entry.type || '').toLowerCase();
        const amount = Number(entry.amount) || 0;
        const runningBalance = runningBalances.get(entry.id) || 0;

        let badge = `
            <span class="px-3 py-1 rounded-full text-xs font-semibold bg-gray-100 text-gray-600">
                UNKNOWN
            </span>`;

        if (type === 'credit') {
            badge = `<span class="px-3 py-1 rounded-full text-xs font-semibold bg-green-100 text-green-700">CREDIT</span>`;
        }

        if (type === 'debit') {
            badge = `<span class="px-3 py-1 rounded-full text-xs font-semibold bg-red-100 text-red-700">DEBIT</span>`;
        }

        const tr = document.createElement('tr');
        tr.className = "hover:bg-gray-50";

        tr.innerHTML = `
            <td class="px-6 py-4">${entry.date || 'N/A'}</td>
            <td class="px-6 py-4">${entry.purpose || '-'}</td>
            <td class="px-6 py-4">${badge}</td>
            <td class="px-6 py-4 ${type === 'credit' ? 'text-green-600' : 'text-red-600'}">
                ${amount.toFixed(2)}
            </td>
            <td class="px-6 py-4 font-semibold ${runningBalance >= 0 ? 'text-green-700' : 'text-red-700'}">
                ${runningBalance.toFixed(2)}
            </td>
        `;

        finTableBody.appendChild(tr);
    });

    finalBalanceEl.textContent = finalBalance.toFixed(2);
}

/* ===============================
   RENDER NOTIFICATION TABLE
=================================*/
function renderNotificationTable(list) {

    notificationTableBody.innerHTML = '';

    if (!list.length) {
        notificationTableBody.innerHTML = `
            <tr>
                <td colspan="6" class="text-center py-6 text-gray-500">
                    No Notification Found!
                </td>
            </tr>`;
        return;
    }

    list.forEach(entry => {

        const type = (entry.type || '').toLowerCase();
        const amount = Number(entry.amount) || 0;

        let badge = `
            <span class="px-3 py-1 rounded-full text-xs font-semibold bg-gray-100 text-gray-600">
                UNKNOWN
            </span>`;

        if (type === 'credit') {
            badge = `<span class="px-3 py-1 rounded-full text-xs font-semibold bg-green-100 text-green-700">CREDIT</span>`;
        }

        if (type === 'debit') {
            badge = `<span class="px-3 py-1 rounded-full text-xs font-semibold bg-red-100 text-red-700">DEBIT</span>`;
        }

        const tr = document.createElement('tr');
        tr.className = "hover:bg-gray-50";

        tr.innerHTML = `
            <td class="px-6 py-4">${entry.date || 'N/A'}</td>
            <td class="px-6 py-4">${entry.employee_id || '-'}</td>
            <td class="px-6 py-4">${entry.purpose || '-'}</td>
            <td class="px-6 py-4">${badge}</td>
            <td class="px-6 py-4 ${type === 'credit' ? 'text-green-600' : 'text-red-600'}">
                ${amount.toFixed(2)}
            </td>
            <td class="px-6 py-4">
                <button class="px-3 py-1 bg-blue-600 text-white rounded text-xs">
                    View
                </button>
            </td>
        `;

        notificationTableBody.appendChild(tr);
    });
}

/* ===============================
   LOAD MORE (FINANCIAL ONLY)
=================================*/
function toggleLoadMoreButton() {
    if (!isFiltering && (currentOffset + incrementAmount) < originalFinStmts.length) {
        loadMoreContainer.classList.remove('hidden');
    } else {
        loadMoreContainer.classList.add('hidden');
    }
}

function loadMoreTransactions() {

    loadMoreSpinner.classList.remove('hidden');
    loadMoreBtn.disabled = true;

    currentOffset += incrementAmount;

    const nextBatch = originalFinStmts.slice(currentOffset, currentOffset + incrementAmount);
    displayedFinStmts = [...displayedFinStmts, ...nextBatch];

    setTimeout(() => {
        renderFinTable(displayedFinStmts);
        toggleLoadMoreButton();
        loadMoreSpinner.classList.add('hidden');
        loadMoreBtn.disabled = false;
    }, 300);
}

/* ===============================
   EVENT LISTENERS
=================================*/
loadMoreBtn.addEventListener('click', loadMoreTransactions);

/* ===============================
   INITIAL LOAD
=================================*/
reloadFinancialTable();
reloadFinNotificationTable();

</script>