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
            <p id="total-outstanding" class="font-semibold text-blue-800">+ Add New Trnx</p>
        </div>
    </button>
</div>

<div class="fixed bottom-4 right-4 space-x-2 z-40">
    <button onclick="openTransactionModal('receive')" 
            class="bg-green-600 text-white p-3 rounded-full shadow-lg hover:bg-green-700">
        <i class="fas fa-plus-circle text-xl"></i>
    </button>
    <button onclick="openTransactionModal('payment')"
            class="bg-red-600 text-white p-3 rounded-full shadow-lg hover:bg-red-700">
        <i class="fas fa-minus-circle text-xl"></i>
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
                        <span class="text-lg font-medium"><i class="fas fa-plus-circle text-green-600 mr-1"></i>Receive Money (from Client)</span>
                    </label>
                    <label class="flex items-center space-x-2 cursor-pointer">
                        <input type="radio" name="transactionType" value="payment" 
                               onchange="toggleTransactionType('payment')" class="w-4 h-4 text-blue-600">
                        <span class="text-lg font-medium"><i class="fas fa-minus-circle text-red-600 mr-1"></i>Sale/Provide Service (to Client)</span>
                    </label>
                </div>
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
                            <i class="fa-solid fa-user"></i> Client
                        </label>
                        <span id="clientName" class="block text-sm font-medium text-center text-gray-700 mb-2"></span>
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
                    
                    <!-- Payment Section - Only shown for Receive transactions -->
                    <div id="paymentSection" class="hidden w-full md:col-span-2 lg:col-span-3">
                        
                        <!-- Rules Notice -->
                        <!--<div class="bg-yellow-50 border-l-4 border-yellow-400 p-3 mb-4 text-sm text-yellow-700">-->
                        <!--    <p><i class="fas fa-info-circle mr-2"></i> <strong>Rules:</strong></p>-->
                        <!--    <ul class="list-disc list-inside ml-4 mt-1">-->
                        <!--        <li>Backdated entries can be made up to a maximum of 10 days.</li>-->
                        <!--        <li>Opening Balance এর আগের তারিখের entry শুধু সংরক্ষিত হবে (ব্যালেন্সে যোগ হবে না)</li>-->
                        <!--        <li>ব্যাকডেটেড entry করলে পরবর্তী সকল entry স্বয়ংক্রিয়ভাবে পুনরায় ক্যালকুলেট হবে</li>-->
                        <!--    </ul>-->
                        <!--</div>-->
            
                        <div class="bg-gray-50 p-4 rounded-lg border border-gray-200">
                            <h4 class="text-sm font-semibold text-gray-700 mb-3 flex items-center">
                                <i class="fas fa-credit-card mr-2 text-blue-600"></i>Payment Details
                            </h4>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <!-- From Account (Payment) -->
                                <div class="col-span-1">
                                    <label id="accountLabel" for="accountInput" class="block text-sm font-medium text-gray-700 mb-2">
                                        <i class="fas fa-wallet mr-1"></i> From Account
                                    </label>
                                    <?php include('form-selects/accounts.php') ?>
                                </div>

                                <!-- Transfer Method (Payment) -->
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
                            </div>
                        </div>
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
                    
                    <input type="hidden" id="vendorPaymentType" name="vendorPaymentType" value="Withdraw">
                    <div id="vendorSection" class="hidden md:col-span-2 lg:col-span-3 text-left">
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            <i class="fa-solid fa-user"></i> Vendor
                        </label>
                        <?php include('form-selects/vendors.php') ?>
                    </div>
                    
                    <!-- Particular -->
                    <div id="vendorParticularSection" class="hidden md:col-span-2 lg:col-span-3 text-left">
                        <label for="particular" class="block text-sm font-medium text-gray-700 mb-2">
                            <i class="fas fa-file-alt mr-1"></i> Particular for Vendor
                        </label>
                        <textarea
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" rows="3"
                            id="vendorParticularInput" name="vendorParticularInput" placeholder="Enter transaction description"></textarea>
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
    
    /* Add to your existing styles */
    mark {
        background-color: #fef3c7;
        padding: 0 2px;
        border-radius: 2px;
    }
    
    #searchInput:focus {
        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
    }
    
    .table-container {
        scroll-behavior: smooth;
    }
    
    /* Loading animation */
    @keyframes pulse {
        0%, 100% { opacity: 1; }
        50% { opacity: 0.5; }
    }
    
    .animate-pulse {
        animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
    }
    
    /* Transition for filter changes */
    #finTableBody tr {
        transition: background-color 0.2s ease;
    }
</style>

<script>
    // Financial Transactions Search and Filter
    const GET_FINANCIAL_STATEMENT_BY_CLIENT_API = "<?php echo $getClientFinEntriesApi; ?>";
    let originalFinStmts = []; // Store original data for filtering
    let displayedFinStmts = []; // Store currently displayed data
    let currentOffset = 0; // Start from newest
    const incrementAmount = 5; // How many to load each time
    let isFiltering = false; // Track if we're in filtering mode
    let searchTimeout = null; // For debouncing search input
    let currentTransactionType = 'receive';
    const clientId = "<?php echo isset($clientId) ? $clientId : ''; ?>";

    // DOM Elements
    const finTableBody = document.getElementById('finTableBody');
    const searchInput = document.getElementById('searchInput');
    const filterType = document.getElementById('filterType');
    const resetFilters = document.getElementById('resetFilters');
    const noResultsMessage = document.getElementById('noResultsMessage');
    const finalOutstanding = document.getElementById('final-outstanding');
    const loadMoreContainer = document.getElementById('loadMoreContainer');
    const loadMoreBtn = document.getElementById('loadMoreBtn');
    const loadMoreSpinner = document.getElementById('loadMoreSpinner');
    const vendorSection = document.getElementById('vendorSection');
    const vendorParticularSection = document.getElementById('vendorParticularSection');

    // Main function to load data
    function reloadFinancialTable() {
        // Show skeleton loaders while fetching
        showSkeletonLoaders();
        
        fetch(GET_FINANCIAL_STATEMENT_BY_CLIENT_API)
            .then(res => res.json())
            .then(data => {
                if (!data.success) {
                    console.error('API returned unsuccessful:', data);
                    return;
                }
                
                // Store all transactions in original array
                originalFinStmts = data.finStmts || [];
                
                // Sort by date descending (newest first)
                originalFinStmts.sort((a, b) => {
                    const dateA = new Date(a.date);
                    const dateB = new Date(b.date);
                    if (dateA.getTime() !== dateB.getTime()) {
                        return dateB - dateA;
                    }
                    return (b.id || 0) - (a.id || 0);
                });

                // Initially display first 5 transactions
                currentOffset = 0;
                displayedFinStmts = originalFinStmts.slice(currentOffset, currentOffset + incrementAmount);
                renderFinTable(displayedFinStmts, originalFinStmts);
                updateSummary(originalFinStmts);
                
                // Toggle Load More button
                toggleLoadMoreButton();
            })
            .catch(error => {
                console.error('Error fetching transactions:', error);
                showErrorState();
            });
    }

    // Show skeleton loading state
    function showSkeletonLoaders() {
        if (!finTableBody) return;
        
        finTableBody.innerHTML = '';
        for (let i = 0; i < 5; i++) {
            const tr = document.createElement('tr');
            tr.className = "animate-pulse";
            tr.innerHTML = `
                <td class="px-6 py-4"><div class="h-4 bg-gray-200 rounded w-24"></div></td>
                <td class="px-6 py-4"><div class="h-4 bg-gray-200 rounded w-48"></div></td>
                <td class="px-6 py-4"><div class="h-4 bg-gray-200 rounded w-32"></div></td>
                <td class="px-6 py-4"><div class="h-6 bg-gray-200 rounded-full w-16"></div></td>
                <td class="px-6 py-4"><div class="h-4 bg-gray-200 rounded w-20"></div></td>
                <td class="px-6 py-4"><div class="h-4 bg-gray-200 rounded w-20"></div></td>
            `;
            finTableBody.appendChild(tr);
        }
    }

    // Show error state
    function showErrorState() {
        if (!finTableBody) return;
        
        finTableBody.innerHTML = `
            <tr>
                <td colspan="6" class="px-6 py-10 text-center text-gray-500">
                    <div class="flex flex-col items-center gap-2">
                        <i class="fas fa-exclamation-triangle text-3xl text-red-400"></i>
                        <p class="text-sm">Error loading transactions. Please try again.</p>
                    </div>
                </td>
            </tr>
        `;
    }

    // Update summary cards
    function updateSummary(list) {
        const totalTrnx = document.getElementById('total-trnx'); 
        const totalCredit = document.getElementById('total-credit'); 
        const totalDebit = document.getElementById('total-debit'); 
        const totalOutstanding = document.getElementById('total-outstanding');
        const totalAmount = document.getElementById('total-amount');
        
        if (!totalTrnx || !totalCredit || !totalDebit || !totalOutstanding || !totalAmount) return;
        
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
        
        const totalOutstandingAmount = totalCreditAmount - totalDebitAmount;
        
        totalTrnx.textContent = totalTrnxCount;
        totalCredit.textContent = totalCreditAmount.toFixed(2);
        totalDebit.textContent = totalDebitAmount.toFixed(2);
        totalOutstanding.textContent = totalOutstandingAmount.toFixed(2);
        totalAmount.textContent = totalAmountSum.toFixed(2);
    }

    // Filter transactions based on search and type
    function filterTransactions() {
        const searchTerm = searchInput ? searchInput.value.toLowerCase().trim() : '';
        const selectedType = filterType ? filterType.value : 'all';
        
        // Clear any pending timeout
        if (searchTimeout) {
            clearTimeout(searchTimeout);
        }
        
        // Debounce search for better performance
        searchTimeout = setTimeout(() => {
            isFiltering = searchTerm !== '' || selectedType !== 'all';
            
            if (isFiltering) {
                // Filter locally from stored array
                let filteredList = originalFinStmts.filter(entry => {
                    // Search filter - check multiple fields
                    const matchesSearch = searchTerm === '' || 
                        (entry.purpose && entry.purpose.toLowerCase().includes(searchTerm)) ||
                        (entry.work_title && entry.work_title.toLowerCase().includes(searchTerm)) ||
                        (entry.date && entry.date.toLowerCase().includes(searchTerm)) ||
                        (entry.amount && entry.amount.toString().includes(searchTerm)) ||
                        (entry.client_name && entry.client_name.toLowerCase().includes(searchTerm)) ||
                        (entry.vendor_name && entry.vendor_name.toLowerCase().includes(searchTerm));
                    
                    // Type filter
                    const matchesType = selectedType === 'all' || 
                        (entry.type && entry.type.toLowerCase() === selectedType);
                    
                    return matchesSearch && matchesType;
                });
                
                // Show all filtered results (no pagination in filter mode)
                displayedFinStmts = filteredList;
                
                renderFinTable(displayedFinStmts, filteredList);
                updateSummary(filteredList);
                
                // Show/hide no results message
                if (noResultsMessage) {
                    if (filteredList.length === 0) {
                        noResultsMessage.classList.remove('hidden');
                        if (finTableBody) finTableBody.innerHTML = '';
                        if (finalOutstanding) finalOutstanding.textContent = '0.00';
                        if (loadMoreContainer) loadMoreContainer.classList.add('hidden');
                    } else {
                        noResultsMessage.classList.add('hidden');
                        if (loadMoreContainer) loadMoreContainer.classList.add('hidden');
                    }
                }
            } else {
                // No filters - show paginated results
                currentOffset = 0;
                displayedFinStmts = originalFinStmts.slice(currentOffset, currentOffset + incrementAmount);
                renderFinTable(displayedFinStmts, originalFinStmts);
                updateSummary(originalFinStmts);
                if (noResultsMessage) noResultsMessage.classList.add('hidden');
                toggleLoadMoreButton();
            }
        }, 300); // 300ms debounce
    }

    // Render the table with data
    function renderFinTable(displayList, calculationList) {
        if (!finTableBody) return;
        
        finTableBody.innerHTML = '';
        
        if (!displayList || displayList.length === 0) {
            finTableBody.innerHTML = `
                <tr>
                    <td colspan="6" class="px-6 py-10 text-center text-gray-500">
                        <div class="flex flex-col items-center gap-2">
                            <i class="fas fa-search text-3xl text-gray-400"></i>
                            <p class="text-sm">No transactions found</p>
                        </div>
                    </td>
                </tr>
            `;
            if (finalOutstanding) finalOutstanding.textContent = '0.00';
            return;
        }
        
        // Calculate running balances using calculationList (sorted oldest to newest)
        const sortedForCalculation = [...calculationList].sort((a, b) => {
            const dateA = new Date(a.date);
            const dateB = new Date(b.date);
            if (dateA.getTime() !== dateB.getTime()) {
                return dateA - dateB; // Oldest first
            }
            return (a.id || 0) - (b.id || 0);
        });
        
        // Calculate running balances
        let cumulativeBalance = 0;
        const runningBalances = new Map();
        
        sortedForCalculation.forEach((entry) => {
            const type = (entry.type || '').toLowerCase();
            const amount = Number(entry.amount) || 0;
            
            if (type === 'debit') { // Credit/Receive increases balance
                cumulativeBalance += amount;
            } else if (type === 'credit') { // Debit/Payment decreases balance
                cumulativeBalance -= amount;
            }
            
            runningBalances.set(entry.id, cumulativeBalance);
        });
        
        const finalBalance = cumulativeBalance;
        
        // Render displayList (already sorted newest to oldest)
        displayList.forEach(finSingleEntry => {
            const tr = document.createElement('tr');
            tr.className = "hover:bg-gray-50 transition-colors";
    
            const type = (finSingleEntry.type || '').toLowerCase();
            const amount = Number(finSingleEntry.amount) || 0;
            const runningOutstanding = runningBalances.get(finSingleEntry.id) || 0;
    
            // Determine party name (client or vendor)
            const partyName = finSingleEntry.user_name || 'N/A';
            
            // Style based on transaction type
            let typeBadge = '';
            if (type === 'debit') {
                typeBadge = `
                    <span class="px-3 py-1 rounded-full text-xs font-semibold bg-green-100 text-green-700">
                        Credit
                    </span>
                `;
            } else if (type === 'credit') {
                typeBadge = `
                    <span class="px-3 py-1 rounded-full text-xs font-semibold bg-red-100 text-red-700">
                        Debit
                    </span>
                `;
            } else {
                typeBadge = `
                    <span class="px-3 py-1 rounded-full text-xs font-semibold bg-gray-100 text-gray-600">
                        UNKNOWN
                    </span>
                `;
            }
    
            const purpose = finSingleEntry.purpose || 'No Data Found';
            const workTitle = finSingleEntry.work_title || 'No Data Found';
    
            tr.innerHTML = `
                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                    ${finSingleEntry.date || 'N/A'}
                </td>
                <td class="px-6 py-2 text-sm text-gray-700 max-w-xs break-words whitespace-normal">
                    <div class="font-medium">${purpose}</div>
                    <div class="text-xs text-gray-500 mt-1">${partyName}</div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                    ${workTitle}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm">
                    ${typeBadge}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium ${type === 'debit' ? 'text-green-600' : 'text-red-600'}">
                    ${amount.toFixed(2)}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm ${runningOutstanding >= 0 ? 'text-green-700' : 'text-red-700'} font-semibold">
                    ${runningOutstanding.toFixed(2)}
                </td>
            `;
    
            finTableBody.appendChild(tr);
        });
    
        // Update final outstanding
        if (finalOutstanding) {
            finalOutstanding.textContent = finalBalance.toFixed(2);
            finalOutstanding.className = finalBalance >= 0 
                ? "px-6 py-4 text-sm text-green-700 font-bold" 
                : "px-6 py-4 text-sm text-red-700 font-bold";
        }
    }

    // Toggle Load More button visibility
    function toggleLoadMoreButton() {
        if (!loadMoreContainer || !loadMoreBtn) return;
        
        if (!isFiltering && originalFinStmts && (currentOffset + incrementAmount) < originalFinStmts.length) {
            loadMoreContainer.classList.remove('hidden');
        } else {
            loadMoreContainer.classList.add('hidden');
        }
    }

    // Load more transactions
    function loadMoreTransactions() {
        if (isFiltering || !loadMoreSpinner || !loadMoreBtn) return;
        
        loadMoreSpinner.classList.remove('hidden');
        loadMoreBtn.disabled = true;
        
        // Simulate loading delay for better UX
        setTimeout(() => {
            currentOffset += incrementAmount;
            const nextBatch = originalFinStmts.slice(currentOffset, currentOffset + incrementAmount);
            displayedFinStmts = [...displayedFinStmts, ...nextBatch];
            
            renderFinTable(displayedFinStmts, originalFinStmts);
            updateSummary(originalFinStmts);
            toggleLoadMoreButton();
            
            loadMoreSpinner.classList.add('hidden');
            loadMoreBtn.disabled = false;
        }, 300);
    }
    
    // Reset all filters
    function resetFiltersAndSearch() {
        if (searchInput) searchInput.value = '';
        if (filterType) filterType.value = 'all';
        currentOffset = 0;
        isFiltering = false;
        
        // Trigger filter
        filterTransactions();
        
        // Scroll to top of table
        const container = document.querySelector('.table-container');
        if (container) container.scrollTop = 0;
    }
    
    // NEW Transactions Here
    function addTrnx() {
        openTransactionModal('receive');
    }
    
    // Transaction Modal Functionality
    function openTransactionModal(type = 'receive') {
        const modal = document.getElementById('transactionModal');
        if (!modal) return;
        
        currentTransactionType = type;
        
        // Set modal title and labels based on type
        updateTransactionUI(type);
        
        // Set initial visibility of payment section - only show for Receive
        const paymentSection = document.getElementById('paymentSection');
        const accountInput = document.getElementById('accountInput');
        const transferMethod = document.getElementById('transfer_method');
        
        if (paymentSection) {
            if (type === 'receive') {
                paymentSection.classList.remove('hidden');
                if (accountInput) accountInput.setAttribute('required', 'required');
                if (transferMethod) transferMethod.setAttribute('required', 'required');
            } else {
                paymentSection.classList.add('hidden');
                if (accountInput) accountInput.removeAttribute('required');
                if (transferMethod) transferMethod.removeAttribute('required');
            }
        }
        
        // Show modal
        modal.classList.remove('hidden');
        setTimeout(() => modal.classList.add('show'), 10);
        
        const clientNameSpan = document.getElementById('clientName');
        if (clientNameSpan) clientNameSpan.innerHTML = clientName;
        
        // Set default date
        const transactionDate = document.getElementById('transactionDate');
        if (transactionDate) {
            const today = new Date().toISOString().split('T')[0];
            transactionDate.value = today;
            transactionDate.max = today;
        }
    }
    
    function closeTransactionModal() {
        const modal = document.getElementById('transactionModal');
        if (!modal) return;
        
        modal.classList.remove('show');
        setTimeout(() => modal.classList.add('hidden'), 300);
        resetTransactionModal();
    }
    
    function updateTransactionUI(type) {
        const modalTitle = document.getElementById('modalTitle');
        const accountLabel = document.getElementById('accountLabel');
        const paymentType = document.getElementById('paymentType');
        
        if (!modalTitle || !accountLabel || !paymentType) return;
        
        if (type === 'receive') {
            modalTitle.innerHTML = `<i class="fas fa-plus-circle text-green-600 mr-2"></i>Receive Money for ${clientName}`;
            accountLabel.innerHTML = '<i class="fas fa-sign-in-alt mr-1"></i> Deposit To Account';
            paymentType.value = 'Deposit';
        } else {
            modalTitle.innerHTML = `<i class="fas fa-minus-circle text-red-600 mr-2"></i>Make Payment for ${clientName}`;
            accountLabel.innerHTML = '<i class="fas fa-sign-out-alt mr-1"></i> Withdraw From Account';
            paymentType.value = 'Withdraw';
        }
    }
    
    function toggleTransactionType(type) {
        currentTransactionType = type;
        updateTransactionUI(type);
        
        // Show/hide payment section - show for Receive, hide for Payment
        const paymentSection = document.getElementById('paymentSection');
        const accountInput = document.getElementById('accountInput');
        const transferMethod = document.getElementById('transfer_method');
        
        if (paymentSection) {
            if (type === 'receive') {
                paymentSection.classList.remove('hidden');
                vendorSection.classList.add('hidden');
                vendorParticularSection.classList.add('hidden');
                if (accountInput) accountInput.setAttribute('required', 'required');
                if (transferMethod) transferMethod.setAttribute('required', 'required');
            } else {
                paymentSection.classList.add('hidden');
                vendorSection.classList.remove('hidden');
                vendorParticularSection.classList.remove('hidden');
                if (accountInput) accountInput.removeAttribute('required');
                if (transferMethod) transferMethod.removeAttribute('required');
            }
        }
    }
    
    function resetTransactionModal() {
        const form = document.getElementById('transactionForm');
        if (form) form.reset();
        
        // Reset date
        const transactionDate = document.getElementById('transactionDate');
        if (transactionDate) {
            const today = new Date().toISOString().split('T')[0];
            transactionDate.value = today;
        }
        
        // Reset warnings
        const dateWarning = document.getElementById('dateWarning');
        if (dateWarning) dateWarning.classList.add('hidden');
        
        const openingDateInfo = document.getElementById('openingDateInfo');
        if (openingDateInfo) openingDateInfo.classList.add('hidden');
        
        // Reset payment method sections
        const chequeSection = document.getElementById('cheque-details-section');
        const bftnSection = document.getElementById('bftn-details-section');
        
        if (chequeSection) chequeSection.classList.add('hidden');
        if (bftnSection) bftnSection.classList.add('hidden');
        
        // Reset payment section based on current type
        const paymentSection = document.getElementById('paymentSection');
        const accountInput = document.getElementById('accountInput');
        const transferMethod = document.getElementById('transfer_method');
        
        if (paymentSection) {
            if (currentTransactionType === 'receive') {
                paymentSection.classList.remove('hidden');
                if (accountInput) accountInput.setAttribute('required', 'required');
                if (transferMethod) transferMethod.setAttribute('required', 'required');
            } else {
                paymentSection.classList.add('hidden');
                if (accountInput) accountInput.removeAttribute('required');
                if (transferMethod) transferMethod.removeAttribute('required');
            }
        }
    }
    
    // Setup transaction form
    function setupTransactionForm() {
        const API_RECEIVE = `${IP_PATH}/api/clients/cl-ac-receive-store.php`;
        const API_SALE = `${IP_PATH}/api/clients/cl-ac-sale-store.php`;
        const API_PURCHASE = `${IP_PATH}/api/vendors/ve-ac-purchase-store.php`;
        const FETCH_STATEMENT = `${IP_PATH}/api/accounts/fetch_account_statement_api.php`;
        
        const accountInput = document.getElementById('accountInput');
        const transactionDate = document.getElementById('transactionDate');
        const transferMethod = document.getElementById('transfer_method');
        const saveBtn = document.getElementById('saveTransactionBtn');
        const spinner = document.getElementById('spinner');
        const saveButtonText = document.getElementById('saveButtonText');
        
        if (!saveBtn) return;
        
        let openingDate = null;
        
        // Extract IDs from combined field
        function extractIds(value) {
            if (!value) return null;
            const parts = value.split('|').map(v => v.trim());
            return {
                sys_id: parts[0] || null,
                name: parts[1] || null,
            };
        }
        
        function secondExtractIds(value) {
            if (!value || typeof value !== 'string') return null;
            
            // Split by pipe and trim
            const parts = value.split('|').map(v => v.trim());
            
            // Ensure we have at least 2 parts (ID and Name)
            if (parts.length < 2) {
                // Try to extract ID from the beginning if format is different
                const idMatch = value.match(/^(\d+)/);
                if (idMatch) {
                    return {
                        sys_id: idMatch[1],
                        name: value.substring(idMatch[1].length).trim()
                    };
                }
                return null;
            }
            
            return {
                sys_id: parts[0] || null,
                name: parts[1] || null,
            };
        }
        
        // Fetch opening date for account
        async function fetchOpeningDate(accountId) {
            if (!accountId) {
                openingDate = null;
                const info = document.getElementById('openingDateInfo');
                if (info) info.classList.add('hidden');
                return;
            }
            
            try {
                const response = await fetch(`${FETCH_STATEMENT}?ledger_db_id=${accountId}&opening_only=1`);
                const result = await response.json();
                
                if (result.success && result.data && result.data.length > 0) {
                    openingDate = result.data[0].date;
                    
                    const dateObj = new Date(openingDate);
                    const formattedDate = dateObj.toLocaleDateString('bn-BD', {
                        year: 'numeric',
                        month: 'long',
                        day: 'numeric'
                    });
                    
                    const text = document.getElementById('openingDateText');
                    const info = document.getElementById('openingDateInfo');
                    
                    if (text) {
                        text.innerHTML = `এই অ্যাকাউন্টের Opening Balance: <strong>${formattedDate}</strong>। এর আগের তারিখে entry করলে তা শুধু সংরক্ষিত হবে, ব্যালেন্স ক্যালকুলেশনে যোগ হবে না।`;
                    }
                    if (info) info.classList.remove('hidden');
                    
                    // Allow dates before opening balance
                    if (transactionDate) transactionDate.removeAttribute('min');
                }
            } catch (error) {
                console.error('Error fetching opening date:', error);
            }
        }
        
        // Validate date
        function validateTransactionDate(selectedDate) {
            const selected = new Date(selectedDate);
            const today = new Date();
            
            selected.setHours(0, 0, 0, 0);
            today.setHours(0, 0, 0, 0);
            
            const diffDays = (today - selected) / (1000 * 60 * 60 * 24);
            
            // Check if before opening balance
            if (openingDate && selectedDate < openingDate) {
                return {
                    valid: true,
                    warning: 'আপনি Opening Balance এর আগের তারিখে entry করছেন। এই entry শুধু সংরক্ষিত হবে, ব্যালেন্স ক্যালকুলেশনে যোগ হবে না।',
                    isHistorical: true
                };
            }
            
            // Check max backdated (10 days)
            if (diffDays > 10) {
                return {
                    valid: false,
                    message: 'You can make backdated entries up to a maximum of 10 days.'
                };
            }
            
            return { valid: true, isHistorical: false };
        }
        
        // Toggle payment method sections
        function togglePaymentDetails() {
            const method = transferMethod ? transferMethod.value : 'cash';
            const chequeSection = document.getElementById('cheque-details-section');
            const bftnSection = document.getElementById('bftn-details-section');
            
            if (chequeSection) chequeSection.classList.add('hidden');
            if (bftnSection) bftnSection.classList.add('hidden');
            
            if (method === 'cheque' && chequeSection) {
                chequeSection.classList.remove('hidden');
            } else if (method === 'bftn-eft' && bftnSection) {
                bftnSection.classList.remove('hidden');
            }
        }
        
        if (transferMethod) {
            transferMethod.addEventListener('change', togglePaymentDetails);
        }
        
        // Account change handler
        if (accountInput) {
            accountInput.addEventListener('change', function() {
                const account = extractIds(accountInput.value);
                if (account && account.sys_id) {
                    const idField = document.getElementById('accountId');
                    const nameField = document.getElementById('accountName');
                    
                    if (idField) idField.value = account.sys_id;
                    if (nameField) nameField.value = account.name;
                    
                    fetchOpeningDate(account.sys_id);
                }
            });
        }
        
        // Date validation
        if (transactionDate) {
            transactionDate.addEventListener('change', function() {
                const warning = document.getElementById('dateWarning');
                if (!warning) return;
                
                const validation = validateTransactionDate(this.value);
                
                if (!validation.valid) {
                    warning.textContent = validation.message;
                    warning.classList.remove('hidden');
                    warning.classList.add('text-red-500');
                    if (saveBtn) saveBtn.disabled = true;
                } else if (validation.warning) {
                    warning.textContent = validation.warning;
                    warning.classList.remove('hidden');
                    warning.classList.remove('text-red-500');
                    warning.classList.add('text-yellow-600');
                    if (saveBtn) saveBtn.disabled = false;
                } else {
                    warning.classList.add('hidden');
                    if (saveBtn) saveBtn.disabled = false;
                }
            });
        }
        
        // Form validation
        function validateForm() {
            // Only validate account and payment method for Receive transactions
            if (currentTransactionType === 'receive') {
                const account = extractIds(accountInput?.value);
                if (!account || !account.sys_id) {
                    alert('Please select an account');
                    return false;
                }
                
                if (!transferMethod?.value) {
                    alert('Please select a payment method');
                    return false;
                }
            }
            
            if (!transactionDate?.value) {
                alert('Please select a date');
                return false;
            }
            
            const dateValidation = validateTransactionDate(transactionDate.value);
            if (!dateValidation.valid) {
                alert(dateValidation.message);
                return false;
            }
            
            const amount = document.getElementById('balance')?.value;
            if (!amount || parseFloat(amount) <= 0) {
                alert('Please enter a valid amount');
                return false;
            }
            
            const particular = document.getElementById('particular')?.value;
            if (!particular || !particular.trim()) {
                alert('Please enter particulars');
                return false;
            }
            
            return true;
        }
        
        // Build datetime
        function buildDateTime(dateOnly) {
            const now = new Date();
            const time = now.toTimeString().split(' ')[0];
            return `${dateOnly} ${time}`;
        }
        
        // Submit form
        saveBtn.addEventListener('click', async function() {
            if (!validateForm()) return;
            
            const type = currentTransactionType;
            const method = transferMethod?.value || 'cash';
            const account = extractIds(accountInput?.value);
            const dateValidation = validateTransactionDate(transactionDate?.value);
            
            // 1. Prepare Primary Data
            const data = {
                clientId: clientId,
                clientName: clientName,
                amount: document.getElementById('balance')?.value,
                particular: document.getElementById('particular')?.value.trim(),
                transactionDate: buildDateTime(transactionDate?.value),
                isHistorical: dateValidation.isHistorical ? 1 : 0
            };
            
            if (type === 'receive') {
                data.accountId = account?.sys_id;
                data.accountName = account?.name;
                data.transferMethod = method;
            }
        
            // 2. Prepare Vendor Data (Declare outside so it's scoped for later)
            let vendorData = null;
            if (type === 'payment') {
                // const vendorValue = document.getElementById('vendorInput').value;
                let vendor = null;
                if(vendorInput){
                    vendorInfo = secondExtractIds(vendorInput?.value);
                }    
                if(vendorInfo){
                    vendorData = {
                        type: 'receive',
                        vendorId: vendorInfo.sys_id,
                        vendorName: vendorInfo.name,
                        amount: document.getElementById('balance')?.value,
                        particular: document.getElementById('vendorParticularInput')?.value.trim(),
                        transactionDate: buildDateTime(transactionDate?.value),
                    };
                }
            }
            
            // console.log(vendorData);
            
            // Add method-specific fields to 'data'
            if (method === 'cheque') {
                data.chequeNo = document.getElementById('cheque_no')?.value;
                data.chequeDate = document.getElementById('cheque_date')?.value;
                data.chequeAccountName = document.getElementById('cheque_account_name')?.value;
                data.bankName = document.getElementById('bank_name')?.value;
            } else if (method === 'bftn-eft') {
                data.bftnAccountName = document.getElementById('account_name')?.value;
                data.eftBankName = document.getElementById('eft_bank_name')?.value;
                data.bftnDate = document.getElementById('bftn_date')?.value;
            }
        
            // UI Updates
            saveBtn.disabled = true;
            if (spinner) spinner.classList.remove('hidden');
            if (saveButtonText) saveButtonText.textContent = 'Processing...';
            
            try {
                // First API Call
                const apiUrl = type === 'receive' ? API_RECEIVE : API_SALE;
                const response = await fetch(apiUrl, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(data)
                });
                
                const result = await response.json();
                
                if (response.ok && result.success) {

                    const messages = {
                        receive: 'Payment received successfully!',
                        default: 'Service provided successfully!',
                        historical: 'Historic Data Stored Successfully'
                    };
                    
                    let message = result.is_historical 
                        ? messages.historical 
                        : messages[type] || messages.default;
                    
                    alert(message);
        
                    // Second API Call (Only for payments)
                    if (type === 'payment' && vendorData) {
                        const vResponse = await fetch(API_PURCHASE, {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify(vendorData)
                        });
                        const vResult = await vResponse.json();
                        
                        if (vResponse.ok && vResult.success) {
                            alert('Vendor transaction completed!');
                        } else {
                            alert('Client saved, but Vendor failed: ' + (vResult.message || 'Unknown error'));
                        }
                    }
        
                    closeTransactionModal();
                    location.reload(); // Uncomment if needed
                } else {
                    alert(result.error || result.message || 'Transaction failed.');
                }
            } catch (error) {
                console.error('Transaction error:', error);
                alert('Network error. Please check your connection.');
            } finally {
                saveBtn.disabled = false;
                if (spinner) spinner.classList.add('hidden');
                if (saveButtonText) saveButtonText.textContent = 'Save Transaction';
            }
        });
    }

    // Initialize everything when DOM is loaded
    document.addEventListener('DOMContentLoaded', function() {
        // Load initial data
        reloadFinancialTable();
        
        // Setup event listeners
        if (searchInput) {
            searchInput.addEventListener('input', filterTransactions);
            searchInput.addEventListener('keyup', (e) => {
                if (e.key === 'Escape') {
                    resetFiltersAndSearch();
                }
            });
        }
        
        if (filterType) {
            filterType.addEventListener('change', filterTransactions);
        }
        
        if (resetFilters) {
            resetFilters.addEventListener('click', resetFiltersAndSearch);
        }
        
        if (loadMoreBtn) {
            loadMoreBtn.addEventListener('click', loadMoreTransactions);
        }
        
        // Setup transaction form
        setupTransactionForm();
        
        // Add keyboard shortcut for search (Ctrl+F)
        document.addEventListener('keydown', (e) => {
            if (e.ctrlKey && e.key === 'f') {
                e.preventDefault();
                if (searchInput) {
                    searchInput.focus();
                    searchInput.select();
                }
            }
        });
    });
</script>