<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4 m-8">
    <div class="group bg-gradient-to-br from-purple-50 to-purple-100 border border-purple-200 hover:border-purple-400 hover:from-purple-100 hover:to-purple-200 p-4 rounded-xl text-center transition-all duration-300 hover:shadow-lg hover:-translate-y-1">
        <p id="total-trnx" class="font-semibold text-purple-800">1</p>
        <p class="text-xs text-purple-600 mt-1">Total Trnx</p>
    </div>
    <div class="group bg-gradient-to-br from-green-50 to-green-100 border border-green-200 hover:border-green-400 hover:from-green-100 hover:to-green-200 p-4 rounded-xl text-center transition-all duration-300 hover:shadow-lg hover:-translate-y-1">
        <p id="total-credit" class="font-semibold text-green-800">1</p>
        <p class="text-xs text-green-600 mt-1">Total Credit</p>
    </div>
    <div class="group bg-gradient-to-br from-red-50 to-red-100 border border-red-200 hover:border-red-400 hover:from-red-100 hover:to-red-200 p-4 rounded-xl text-center transition-all duration-300 hover:shadow-lg hover:-translate-y-1">
        <p id="total-debit" class="font-semibold text-red-800">1</p>
        <p class="text-xs text-red-600 mt-1">Total Debit</p>
        </div>
    <div class="group bg-gradient-to-br from-yellow-50 to-yellow-100 border border-yellow-200 hover:border-yellow-400 hover:from-yellow-100 hover:to-yellow-200 p-4 rounded-xl text-center transition-all duration-300 hover:shadow-lg hover:-translate-y-1">
        <p id="total-outstanding" class="font-semibold text-yellow-800">1</p>
        <p class="text-xs text-yellow-600 mt-1">Total Outstanding</p>
    </div>
    <button onclick="addTrnx()">
        <div class="group bg-gradient-to-br from-blue-50 to-blue-100 border border-blue-200 hover:border-blue-400 hover:from-blue-100 hover:to-blue-200 p-4 rounded-xl text-center transition-all duration-300 hover:shadow-lg hover:-translate-y-1">
            <p id="total-outstanding" class="font-semibold text-blue-800">+ Add Trnx</p>
        </div>
    </button>
</div>
<div class="bg-white rounded-lg shadow p-4 flex flex-col">
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-4 gap-3">
        <h2 class="text-2xl font-semibold text-gray-800">Financial Transactions</h2>
        
        <!-- Search and Filter Section -->
        <div class="flex flex-col sm:flex-row gap-3 w-full md:w-auto">
            <!-- Search Input -->
            <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <i class="fas fa-search text-gray-400"></i>
                </div>
                <input 
                    type="text" 
                    id="searchInput"
                    class="pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 w-full"
                    placeholder="Search transactions..."
                >
            </div>
            
            <!-- Filter Dropdown -->
            <div class="relative">
                <select 
                    id="filterType" 
                    class="appearance-none pl-4 pr-10 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 w-full"
                >
                    <option value="all">All Types</option>
                    <option value="debit">Credit</option>
                    <option value="credit">Debit</option>
                </select>
                <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                    <i class="fas fa-chevron-down text-gray-400"></i>
                </div>
            </div>
            
            <!-- Reset Button -->
            <button 
                id="resetFilters"
                class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors"
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
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-64">Purpose</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Work</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Running Outstanding</th>
                </tr>
            </thead>
            <tbody id="finTableBody" class="bg-white divide-y divide-gray-200 text-left">
            </tbody>
            <!-- Total Row -->
            <tfoot class="bg-gray-50 font-semibold">
                <tr>
                    <td colspan="4" class="px-6 py-4 text-right text-sm text-gray-700">Total:</td>
                    <td id="total-amount" class="px-6 py-4 text-sm text-gray-900">0.00</td>
                    <td id="final-outstanding" class="px-6 py-4 text-sm text-yellow-700 font-bold">0.00</td>
                </tr>
            </tfoot>
        </table>
        
        <!-- No Results Message -->
        <div id="noResultsMessage" class="hidden px-6 py-10 text-center text-gray-500">
            <div class="flex flex-col items-center gap-2">
                <i class="fas fa-search text-3xl text-gray-400"></i>
                <p class="text-sm">No transactions match your search criteria</p>
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

<div id="transactionModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
    <div class="relative top-20 mx-auto p-5 border w-full max-w-4xl shadow-lg rounded-lg bg-white">
        
        <!-- Modal Header -->
        <div class="flex justify-between items-center pb-3 border-b">
            <h3 class="text-xl font-semibold text-gray-900">
                <i class="fas fa-exchange-alt mr-2 text-blue-600"></i>
                <span id="modalTitle">New Transaction</span>
            </h3>
            <button onclick="closeTransactionModal()" class="text-gray-400 hover:text-gray-600">
                <i class="fas fa-times text-2xl"></i>
            </button>
        </div>

        <!-- Modal Content -->
        <div class="mt-4">
            <!-- Transaction Type Selection -->
            <div class="bg-blue-50 p-4 rounded-lg mb-4">
                <div class="flex items-center space-x-6">
                    <label class="flex items-center space-x-2 cursor-pointer">
                        <input type="radio" name="transactionType" value="receive" checked 
                               onchange="toggleTransactionType('receive')" class="w-4 h-4 text-blue-600">
                        <span class="text-lg font-medium"><i class="fas fa-plus-circle text-green-600 mr-1"></i>Receive Money</span>
                    </label>
                    <label class="flex items-center space-x-2 cursor-pointer">
                        <input type="radio" name="transactionType" value="payment" 
                               onchange="toggleTransactionType('payment')" class="w-4 h-4 text-blue-600">
                        <span class="text-lg font-medium"><i class="fas fa-minus-circle text-red-600 mr-1"></i>Make Payment</span>
                    </label>
                </div>
            </div>

            <!-- Rules Notice -->
            <div class="bg-yellow-50 border-l-4 border-yellow-400 p-3 mb-4 text-sm text-yellow-700">
                <p><i class="fas fa-info-circle mr-2"></i> <strong>Rules:</strong></p>
                <ul class="list-disc list-inside ml-4 mt-1">
                    <li>Backdated entries can be made up to a maximum of 8 days.</li>
                    <li>Opening Balance এর আগের তারিখের entry শুধু সংরক্ষিত হবে (ব্যালেন্সে যোগ হবে না)</li>
                    <li>ব্যাকডেটেড entry করলে পরবর্তী সকল entry স্বয়ংক্রিয়ভাবে পুনরায় ক্যালকুলেট হবে</li>
                </ul>
            </div>

            <!-- Opening Date Info -->
            <div id="openingDateInfo" class="bg-purple-50 border-l-4 border-purple-400 p-2 mb-3 text-sm text-purple-700 hidden">
                <i class="fas fa-calendar-alt mr-2"></i>
                <span id="openingDateText"></span>
            </div>

            <!-- Date Warning -->
            <p id="dateWarning" class="text-xs text-red-500 mt-1 mb-2 hidden"></p>

            <form id="transactionForm" class="space-y-6">
                <input type="hidden" id="paymentType" name="paymentType" value="Deposit">
                <input type="hidden" id="accountId" name="accountId">
                <input type="hidden" id="accountName" name="accountName">
                <input type="hidden" id="currentAccountBalance" name="currentAccountBalance">

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    
                    <!-- Party Type (Client/Vendor) - Hidden now, will be auto-detected -->
                    <input type="hidden" id="select_type" value="client">
                    
                    <!-- Client (Auto-detected, shown for reference) -->
                    <div id="client-section" class="col-span-1">
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            <i class="fa-solid fa-user"></i> Client/Vendor
                        </label>
                        <div class="flex items-center space-x-2">
                            <div class="flex-1">
                                <?php include('form-selects/clients.php') ?>
                            </div>
                            <span class="text-xs text-gray-500">Auto-detected from context</span>
                        </div>
                    </div>

                    <!-- From/To Account (Based on transaction type) -->
                    <div class="col-span-1">
                        <label id="accountLabel" for="accountInput" class="block text-sm font-medium text-gray-700 mb-2">
                            <i class="fas fa-wallet mr-1"></i> From Account
                        </label>
                        <?php include('form-selects/accounts.php') ?>
                    </div>

                    <!-- Transfer Method -->
                    <div class="col-span-1">
                        <label for="transfer_method" class="block text-sm font-medium text-gray-700 mb-2">
                            <i class="fas fa-money-check-alt mr-1"></i> Payment Method
                        </label>
                        <select name="transfer_method" id="transfer_method" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500">
                            <option value="cash" selected>Cash</option>
                            <option value="cheque">Cheque</option>
                            <option value="npsb-rtgs">NPSB/RTGS</option>
                            <option value="bftn-eft">BFTN/EFT</option>
                        </select>
                    </div>

                    <!-- Date -->
                    <div class="col-span-1">
                        <label for="transactionDate" class="block text-sm font-medium text-gray-700 mb-2">
                            <i class="fas fa-calendar-alt mr-1"></i> Date
                        </label>
                        <input type="date"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                            id="transactionDate" name="transactionDate" required>
                    </div>

                    <!-- Amount -->
                    <div class="col-span-1">
                        <label for="balance" class="block text-sm font-medium text-gray-700 mb-2">
                            <i class="fas fa-dollar-sign mr-1"></i> Amount
                        </label>
                        <input type="number" step="0.01" min="0"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                            id="balance" name="balance" required placeholder="0.00">
                    </div>

                    <!-- Cheque Details Section -->
                    <div id="cheque-details-section" class="hidden md:col-span-2 lg:col-span-3 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        <div class="col-span-1">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div class="col-span-1">
                                    <label for="cheque_no" class="block text-sm font-medium text-gray-700 mb-2">
                                        <i class="fas fa-file-invoice mr-1"></i> Cheque No
                                    </label>
                                    <input type="text"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg"
                                        id="cheque_no" name="cheque_no" placeholder="Enter cheque number">
                                </div>
                                <div class="col-span-1">
                                    <label for="cheque_date" class="block text-sm font-medium text-gray-700 mb-2">
                                        <i class="fas fa-calendar-day mr-1"></i> Cheque Date
                                    </label>
                                    <input type="date"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg"
                                        id="cheque_date" name="cheque_date">
                                </div>
                            </div>
                        </div>
                        <div class="col-span-1">
                            <label for="cheque_account_name" class="block text-sm font-medium text-gray-700 mb-2">
                                <i class="fas fa-user-circle mr-1"></i> Account Name
                            </label>
                            <input type="text"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg"
                                id="cheque_account_name" name="cheque_account_name" placeholder="Enter account name">
                        </div>
                        <div class="col-span-1">
                            <label for="bank_name" class="block text-sm font-medium text-gray-700 mb-2">
                                <i class="fas fa-university mr-1"></i> Bank Name
                            </label>
                            <input type="text"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg"
                                id="bank_name" name="bank_name" placeholder="Enter bank name">
                        </div>
                    </div>

                    <!-- BFTN/EFT Details Section -->
                    <div id="bftn-details-section" class="hidden md:col-span-2 lg:col-span-3 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        <div class="col-span-1">
                            <label for="account_name" class="block text-sm font-medium text-gray-700 mb-2">
                                <i class="fas fa-user-circle mr-1"></i> Account Name
                            </label>
                            <input type="text"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg"
                                id="account_name" name="account_name" placeholder="Enter account name">
                        </div>
                        <div class="col-span-1">
                            <label for="eft_bank_name" class="block text-sm font-medium text-gray-700 mb-2">
                                <i class="fas fa-university mr-1"></i> Bank Name
                            </label>
                            <input type="text"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg"
                                id="eft_bank_name" name="eft_bank_name" placeholder="Enter bank name">
                        </div>
                        <div class="col-span-1">
                            <label for="bftn_date" class="block text-sm font-medium text-gray-700 mb-2">
                                <i class="fas fa-calendar-check mr-1"></i> Transaction Date
                            </label>
                            <input type="date"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg"
                                id="bftn_date" name="bftn_date">
                        </div>
                    </div>

                    <!-- Particular -->
                    <div class="md:col-span-2 lg:col-span-3">
                        <label for="particular" class="block text-sm font-medium text-gray-700 mb-2">
                            <i class="fas fa-file-alt mr-1"></i> Particular
                        </label>
                        <textarea
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" rows="3"
                            id="particular" name="particular" placeholder="Enter transaction description"></textarea>
                    </div>
                </div>

                <!-- Form Actions -->
                <div class="flex justify-end space-x-3 pt-4 border-t">
                    <button type="button" onclick="resetTransactionModal()"
                        class="px-6 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300">
                        <i class="fas fa-redo mr-2"></i> Reset
                    </button>
                    <button type="button" onclick="closeTransactionModal()"
                        class="px-6 py-2 bg-gray-500 text-white rounded-lg hover:bg-gray-600">
                        <i class="fas fa-times mr-2"></i> Cancel
                    </button>
                    <button type="button"
                        class="px-6 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 flex items-center"
                        id="saveTransactionBtn">
                        <span id="spinner" class="hidden spinner-border spinner-border-sm mr-2"></span>
                        <i class="fas fa-save mr-2"></i>
                        <span id="saveButtonText">Save Transaction</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
    /* Spinner animation */
    .spinner {
        border: 4px solid rgba(0, 0, 0, 0.1);
        width: 24px;
        height: 24px;
        border-radius: 50%;
        border-left-color: #3b82f6;
        animation: spin 1s linear infinite;
        display: inline-block;
    }
    .spinner-border-sm {
        width: 1rem;
        height: 1rem;
        border-width: 0.2em;
    }

    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }

    .hidden {
        display: none !important;
    }

    /* Modal animation */
    #transactionModal {
        transition: opacity 0.3s ease;
    }

    #transactionModal > div {
        transform: scale(0.9);
        transition: transform 0.3s ease;
    }

    #transactionModal.show > div {
        transform: scale(1);
    }
</style>

<script>
    const GET_FINANCIAL_STATEMENT_BY_CLIENT_API = "<?php echo $getClientFinEntriesApi; ?>";
    let originalFinStmts = []; // Store original data for filtering
    let displayedFinStmts = []; // Store currently displayed data
    let currentOffset = 0; // Start from newest
    const incrementAmount = 5; // How many to load each time
    let isFiltering = false; // Track if we're in filtering mode

    function reloadFinancialTable() {
        fetch(GET_FINANCIAL_STATEMENT_BY_CLIENT_API)
            .then(res => res.json())
            .then(data => {
                if (!data.success) return;
                
                // API থেকে ডেটা নিই এবং নতুন থেকে পুরানো সাজাই (নতুন তারিখ আগে)
                originalFinStmts = data.finStmts.sort((a, b) => {
                    // প্রথমে তারিখ নতুন থেকে পুরানো
                    const dateA = new Date(a.date);
                    const dateB = new Date(b.date);
                    if (dateA.getTime() !== dateB.getTime()) {
                        return dateB - dateA; // নতুন তারিখ আগে
                    }
                    // তারিখ একই হলে id বড় থেকে ছোট (নতুন id আগে)
                    return b.id - a.id;
                });

                // প্রাথমিকভাবে নতুন ৫টি ট্রানজেকশন দেখাবো (0 থেকে 5)
                currentOffset = 0;
                displayedFinStmts = originalFinStmts.slice(currentOffset, currentOffset + incrementAmount);
                renderFinTable(displayedFinStmts, originalFinStmts); // পুরো ডেটা ক্যালকুলেশনের জন্য পাঠাই
                updateSummary(originalFinStmts); // সামারি সবসময় পুরো ডেটার উপর ভিত্তি করে
                
                // Load More বাটন দেখানোর সিদ্ধান্ত
                toggleLoadMoreButton();
            })
    }
    
    function updateSummary(list) {
        const totalTrnx = document.getElementById('total-trnx'); 
        const totalCredit = document.getElementById('total-credit'); 
        const totalDebit = document.getElementById('total-debit'); 
        const totalOutstanding = document.getElementById('total-outstanding');
        const totalAmount = document.getElementById('total-amount');
        
        let totalTrnxCount = list.length;
        let totalCreditAmount = 0;
        let totalDebitAmount = 0;
        let totalAmountSum = 0;

        list.forEach(finSingleEntry => {
            const type = (finSingleEntry.type || '').toLowerCase();
            const amount = Number(finSingleEntry.amount) || 0;
            
            totalAmountSum += amount;
            
            if (type === 'debit') {
                totalCreditAmount += amount;
            }
            if (type === 'credit') {
                totalDebitAmount += amount;
            }
        });
        
        totalOutstandingAmount = totalCreditAmount - totalDebitAmount;
        
        totalTrnx.textContent = totalTrnxCount;
        totalCredit.textContent = totalCreditAmount.toFixed(2);
        totalDebit.textContent = totalDebitAmount.toFixed(2);
        totalOutstanding.textContent = totalOutstandingAmount.toFixed(2);
        totalAmount.textContent = totalAmountSum.toFixed(2);
    }

    const finTableBody = document.getElementById('finTableBody');
    const searchInput = document.getElementById('searchInput');
    const filterType = document.getElementById('filterType');
    const resetFilters = document.getElementById('resetFilters');
    const noResultsMessage = document.getElementById('noResultsMessage');
    const finalOutstanding = document.getElementById('final-outstanding');
    const loadMoreContainer = document.getElementById('loadMoreContainer');
    const loadMoreBtn = document.getElementById('loadMoreBtn');
    const loadMoreSpinner = document.getElementById('loadMoreSpinner');

    function filterTransactions() {
        const searchTerm = searchInput.value.toLowerCase();
        const selectedType = filterType.value;
        
        isFiltering = searchTerm !== '' || selectedType !== 'all';
        
        if (isFiltering) {
            // সার্চ বা ফিল্টার করা হলে পুরো ডেটা থেকে ফলাফল দেখাবে
            let filteredList = originalFinStmts.filter(entry => {
                // Search filter
                const matchesSearch = searchTerm === '' || 
                    (entry.purpose && entry.purpose.toLowerCase().includes(searchTerm)) ||
                    (entry.work_title && entry.work_title.toLowerCase().includes(searchTerm)) ||
                    (entry.date && entry.date.toLowerCase().includes(searchTerm));
                
                // Type filter
                const matchesType = selectedType === 'all' || 
                    (entry.type && entry.type.toLowerCase() === selectedType);
                
                return matchesSearch && matchesType;
            });
            
            // ফিল্টার মোডে সব দেখাবে, নতুন থেকে পুরানো সাজিয়ে
            displayedFinStmts = filteredList.sort((a, b) => {
                const dateA = new Date(a.date);
                const dateB = new Date(b.date);
                if (dateA.getTime() !== dateB.getTime()) {
                    return dateB - dateA; // নতুন তারিখ আগে
                }
                return b.id - a.id; // নতুন id আগে
            });
            
            renderFinTable(displayedFinStmts, filteredList);
            updateSummary(filteredList);
            
            // Show/hide no results message
            if (filteredList.length === 0 && originalFinStmts.length > 0) {
                noResultsMessage.classList.remove('hidden');
                finTableBody.innerHTML = '';
                finalOutstanding.textContent = '0.00';
                loadMoreContainer.classList.add('hidden');
            } else {
                noResultsMessage.classList.add('hidden');
                loadMoreContainer.classList.add('hidden'); // ফিল্টার মোডে Load More দেখাবে না
            }
        } else {
            // ফিল্টার না করা হলে নতুন থেকে পুরানো সাজিয়ে নির্দিষ্ট পরিমাণ দেখাবে
            isFiltering = false;
            displayedFinStmts = originalFinStmts.slice(currentOffset, currentOffset + incrementAmount);
            renderFinTable(displayedFinStmts, originalFinStmts);
            updateSummary(originalFinStmts);
            noResultsMessage.classList.add('hidden');
            toggleLoadMoreButton();
        }
    }

    function renderFinTable(displayList, calculationList) {
        finTableBody.innerHTML = '';
        
        if (!displayList || displayList.length === 0) {
            const tr = document.createElement('tr');
    
            tr.innerHTML = `
                <td colspan="6" class="px-6 py-10 text-center text-gray-500">
                    <div class="flex flex-col items-center gap-2">
                        <i class="fas fa-users-slash text-3xl text-gray-400"></i>
                        <p class="text-sm">No Transaction Found!</p>
                    </div>
                </td>
            `;
    
            finTableBody.appendChild(tr);
            finalOutstanding.textContent = '0.00';
            return;
        }
        
        // STEP 1: ক্যালকুলেশনের জন্য পুরানো থেকে নতুন সাজাই
        // প্রথমে তারিখ পুরানো থেকে নতুন
        const sortedForCalculation = [...calculationList].sort((a, b) => {
            const dateA = new Date(a.date);
            const dateB = new Date(b.date);
            if (dateA.getTime() !== dateB.getTime()) {
                return dateA - dateB; // পুরানো তারিখ আগে
            }
            return a.id - b.id; // একই তারিখে ছোট id আগে
        });
        
        // STEP 2: প্রতিটি transaction এর জন্য running balance calculate করি
        let cumulativeBalance = 0;
        const runningBalances = new Map(); // Map হিসেবে store করবো id অনুযায়ী
        
        sortedForCalculation.forEach((entry) => {
            const type = (entry.type || '').toLowerCase();
            const amount = Number(entry.amount) || 0;
            
            // এখন transaction apply করি
            if (type === 'debit') {
                // CREDIT: Balance বাড়ে
                cumulativeBalance += amount;
            } else if (type === 'credit') {
                // DEBIT: Balance কমে
                cumulativeBalance -= amount;
            }
            
            // এই transaction এর পরে running balance store করি
            runningBalances.set(entry.id, cumulativeBalance);
        });
        
        // Final balance store করি
        const finalBalance = cumulativeBalance;
        
        // STEP 3: ডিসপ্লের জন্য displayList কে নতুন থেকে পুরানো সাজাই (ইতিমধ্যে সাজানো)
        // displayList ইতিমধ্যে নতুন থেকে পুরানো সাজানো আছে
        
        // STEP 4: নতুন থেকে পুরানো তারিখে show করি
        displayList.forEach(finSingleEntry => {
            const tr = document.createElement('tr');
            tr.className = "hover:bg-gray-50";

            const type = (finSingleEntry.type || '').toLowerCase();
            const amount = Number(finSingleEntry.amount) || 0;
            
            // এই transaction এর running balance Map থেকে নিই
            const runningOutstanding = runningBalances.get(finSingleEntry.id) || 0;

            let typeBadge = `
                    <span class="px-3 py-1 rounded-full text-xs font-semibold bg-gray-100 text-gray-600">
                        UNKNOWN
                    </span>
                `;

            if (type === 'debit') {
                typeBadge = `
                        <span class="px-3 py-1 rounded-full text-xs font-semibold bg-green-100 text-green-700">
                            CREDIT
                        </span>
                    `;
            } else if (type === 'credit') {
                typeBadge = `
                        <span class="px-3 py-1 rounded-full text-xs font-semibold bg-red-100 text-red-700">
                            DEBIT
                        </span>
                    `;
            }

            // Format running outstanding with color based on value
            let outstandingClass = "text-gray-700";
            if (runningOutstanding > 0) {
                outstandingClass = "text-green-700 font-semibold";
            } else if (runningOutstanding < 0) {
                outstandingClass = "text-red-700 font-semibold";
            }

            tr.innerHTML = `
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                        ${finSingleEntry.date || 'N/A'}
                    </td>

                    <td class="px-6 py-2 text-sm text-gray-700 max-w-xs break-words whitespace-normal">
                        ${finSingleEntry.purpose || 'No Data Found'}
                    </td>
                    
                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                    ${finSingleEntry.work_title || 'No Data Found'}
                    </td>

                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                    ${typeBadge}
                    </td>

                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium ${type === 'debit' ? 'text-green-600' : 'text-red-600'}">
                        ${amount.toFixed(2)}
                    </td>

                    <td class="px-6 py-4 whitespace-nowrap text-sm ${outstandingClass}">
                        ${runningOutstanding.toFixed(2)}
                    </td>
                `;

            finTableBody.appendChild(tr);
        });

        // Update final outstanding in footer
        finalOutstanding.textContent = finalBalance.toFixed(2);
        
        // Color code final outstanding
        if (finalBalance > 0) {
            finalOutstanding.className = "px-6 py-4 text-sm text-green-700 font-bold";
        } else if (finalBalance < 0) {
            finalOutstanding.className = "px-6 py-4 text-sm text-red-700 font-bold";
        } else {
            finalOutstanding.className = "px-6 py-4 text-sm text-yellow-700 font-bold";
        }
    }

    function toggleLoadMoreButton() {
        // শুধুমাত্র ফিল্টার মোডে না থাকলে এবং আরো ডেটা থাকলে Load More বাটন দেখাবে
        if (!isFiltering && (currentOffset + incrementAmount) < originalFinStmts.length) {
            loadMoreContainer.classList.remove('hidden');
        } else {
            loadMoreContainer.classList.add('hidden');
        }
    }

    function loadMoreTransactions() {
        loadMoreSpinner.classList.remove('hidden');
        loadMoreBtn.disabled = true;
        
        // নতুন ডেটা লোড করি (পুরানো ডেটার দিকে যাবো)
        currentOffset += incrementAmount;
        
        // পরবর্তী ৫টি ট্রানজেকশন যোগ করি
        const nextBatch = originalFinStmts.slice(currentOffset, currentOffset + incrementAmount);
        displayedFinStmts = [...displayedFinStmts, ...nextBatch];
        
        // কিছুক্ষণ পরে UI আপডেট করি (লোডিং ইফেক্টের জন্য)
        setTimeout(() => {
            renderFinTable(displayedFinStmts, originalFinStmts);
            updateSummary(originalFinStmts);
            toggleLoadMoreButton();
            
            loadMoreSpinner.classList.add('hidden');
            loadMoreBtn.disabled = false;
        }, 300);
    }
    
    function addTrnx() {
        alert('Add Trnx!')
    }

    // Event Listeners
    searchInput.addEventListener('input', filterTransactions);
    filterType.addEventListener('change', filterTransactions);
    resetFilters.addEventListener('click', () => {
        searchInput.value = '';
        filterType.value = 'all';
        currentOffset = 0; // রিসেট করলে আবার নতুন ডেটা থেকে শুরু হবে
        isFiltering = false;
        filterTransactions();
    });
    loadMoreBtn.addEventListener('click', loadMoreTransactions);

    // Initialize
    reloadFinancialTable();
</script>