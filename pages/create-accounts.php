<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Accounting Setup | Professional Dashboard</title>
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Modal animations */
        @keyframes fadeIn {
            from {
                opacity: 0;
            }

            to {
                opacity: 1;
            }
        }

        @keyframes slideIn {
            from {
                transform: translateY(-20px);
                opacity: 0;
            }

            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        .modal {
            animation: fadeIn 0.3s ease-out;
        }

        .modal-content {
            animation: slideIn 0.3s ease-out;
        }

        /* Custom scrollbar */
        ::-webkit-scrollbar {
            width: 8px;
        }

        ::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 4px;
        }

        ::-webkit-scrollbar-thumb {
            background: #888;
            border-radius: 4px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: #555;
        }

        /* Custom colors */
        .bg-gradient-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }

        .shadow-soft {
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.05), 0 10px 10px -5px rgba(0, 0, 0, 0.02);
        }

        /* Card hover effect */
        .card-hover:hover {
            transform: translateY(-5px);
            transition: transform 0.3s ease;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        }

        /* Adjust for header and sidebar */
        #mainContent {
            height: calc(100vh - 4rem);
            overflow-y: auto;
        }

        @media (max-width: 768px) {
            #mainContent {
                padding-left: 0 !important;
                height: calc(100vh - 4rem);
            }
        }
    </style>
</head>

<body class="bg-gray-50 font-sans">
    <!-- Top Navigation -->
    <?php include '../elements/header.php'; ?>

    <!-- Sidebar -->
    <?php include '../elements/aside.php'; ?>

    <main id="mainContent" class="pt-16 pl-0 lg:pl-64 transition-all duration-300 h-full">
        <div class="p-4 md:p-6 h-full">
            <div class="bg-white rounded-xl shadow-lg p-4 md:p-6 h-full overflow-y-auto">
                <!-- Header -->
                <div class="mb-6 md:mb-8">
                    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                        <div>
                            <h1 class="text-2xl md:text-3xl font-bold text-gray-800">Create Accounts</h1>
                            <p class="text-gray-600 mt-1 md:mt-2 text-sm md:text-base">Configure your chart of accounts and manage financial settings</p>
                        </div>
                        <div class="flex items-center space-x-3">
                            <div class="hidden md:block">
                                <div class="flex items-center bg-white rounded-lg px-4 py-2 shadow-sm border">
                                    <i class="fas fa-database text-purple-600 mr-2"></i>
                                    <span class="text-sm font-medium text-gray-700">Accounts: 24</span>
                                </div>
                            </div>
                            <button onclick="showQuickGuide()" class="flex items-center bg-white text-gray-700 hover:bg-gray-50 font-medium py-2 px-3 md:px-4 rounded-lg border shadow-sm transition duration-200 text-sm">
                                <i class="fas fa-question-circle mr-2"></i>
                                <span>Help</span>
                            </button>
                        </div>
                    </div>
                    <div class="w-full h-1 bg-gradient-to-r from-purple-500 to-blue-500 mt-3 md:mt-4 rounded-full"></div>
                </div>

                <!-- Main Content -->
                <div class="flex flex-col lg:flex-row gap-6">
                    <!-- Left Panel - Setup Form -->
                    <div class="lg:w-2/3">
                        <div class="bg-white rounded-xl md:rounded-2xl shadow-lg p-4 md:p-6">
                            <!-- Progress Indicator -->
                            <div class="mb-6">
                                <div class="flex items-center justify-between mb-2">
                                    <span class="text-sm font-medium text-gray-700">Setup Progress</span>
                                    <span class="text-sm font-bold text-purple-600">Step 1 of 2</span>
                                </div>
                                <div class="w-full bg-gray-200 rounded-full h-2">
                                    <div class="bg-gradient-to-r from-purple-500 to-blue-500 h-2 rounded-full w-1/2"></div>
                                </div>
                                <div class="flex justify-between mt-1">
                                    <span class="text-xs text-gray-500">Select Account Type</span>
                                    <span class="text-xs text-gray-500">Configure Account</span>
                                </div>
                            </div>

                            <!-- Account Type Selection -->
                            <div class="mb-6">
                                <div class="flex items-center mb-4">
                                    <div class="w-8 h-8 md:w-10 md:h-10 rounded-full bg-purple-100 flex items-center justify-center mr-3">
                                        <i class="fas fa-chart-pie text-purple-600 text-sm md:text-base"></i>
                                    </div>
                                    <div>
                                        <h2 class="text-lg md:text-xl font-bold text-gray-800">Select Account Type</h2>
                                        <p class="text-gray-600 text-xs md:text-sm">Choose the appropriate account classification</p>
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3 md:gap-4 mb-6">
                                    <div class="account-type-card border border-gray-200 rounded-xl p-3 md:p-4 hover:border-purple-300 cursor-pointer transition duration-200 card-hover" data-value="Assets (DR)>B/L">
                                        <div class="flex items-start">
                                            <div class="w-10 h-10 md:w-12 md:h-12 rounded-lg bg-blue-50 flex items-center justify-center mr-3">
                                                <i class="fas fa-landmark text-blue-600 text-lg md:text-xl"></i>
                                            </div>
                                            <div>
                                                <h3 class="font-bold text-gray-800 text-sm md:text-base">Assets</h3>
                                                <p class="text-xs text-gray-500 mt-1">Balance Sheet (DR)</p>
                                                <div class="mt-2">
                                                    <span class="inline-block bg-blue-100 text-blue-800 text-xs px-2 py-1 rounded">Debit Balance</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="account-type-card border border-gray-200 rounded-xl p-3 md:p-4 hover:border-purple-300 cursor-pointer transition duration-200 card-hover" data-value="Liabilities(CR)>B/L">
                                        <div class="flex items-start">
                                            <div class="w-10 h-10 md:w-12 md:h-12 rounded-lg bg-red-50 flex items-center justify-center mr-3">
                                                <i class="fas fa-hand-holding-usd text-red-600 text-lg md:text-xl"></i>
                                            </div>
                                            <div>
                                                <h3 class="font-bold text-gray-800 text-sm md:text-base">Liabilities</h3>
                                                <p class="text-xs text-gray-500 mt-1">Balance Sheet (CR)</p>
                                                <div class="mt-2">
                                                    <span class="inline-block bg-red-100 text-red-800 text-xs px-2 py-1 rounded">Credit Balance</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="account-type-card border border-gray-200 rounded-xl p-3 md:p-4 hover:border-purple-300 cursor-pointer transition duration-200 card-hover" data-value="Equity(CR)>B/L">
                                        <div class="flex items-start">
                                            <div class="w-10 h-10 md:w-12 md:h-12 rounded-lg bg-green-50 flex items-center justify-center mr-3">
                                                <i class="fas fa-users text-green-600 text-lg md:text-xl"></i>
                                            </div>
                                            <div>
                                                <h3 class="font-bold text-gray-800 text-sm md:text-base">Equity</h3>
                                                <p class="text-xs text-gray-500 mt-1">Balance Sheet (CR)</p>
                                                <div class="mt-2">
                                                    <span class="inline-block bg-green-100 text-green-800 text-xs px-2 py-1 rounded">Credit Balance</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="account-type-card border border-gray-200 rounded-xl p-3 md:p-4 hover:border-purple-300 cursor-pointer transition duration-200 card-hover" data-value="Income(CR)>P/L">
                                        <div class="flex items-start">
                                            <div class="w-10 h-10 md:w-12 md:h-12 rounded-lg bg-yellow-50 flex items-center justify-center mr-3">
                                                <i class="fas fa-money-check-alt text-yellow-600 text-lg md:text-xl"></i>
                                            </div>
                                            <div>
                                                <h3 class="font-bold text-gray-800 text-sm md:text-base">Income</h3>
                                                <p class="text-xs text-gray-500 mt-1">Profit & Loss (CR)</p>
                                                <div class="mt-2">
                                                    <span class="inline-block bg-yellow-100 text-yellow-800 text-xs px-2 py-1 rounded">Credit Balance</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="account-type-card border border-gray-200 rounded-xl p-3 md:p-4 hover:border-purple-300 cursor-pointer transition duration-200 card-hover" data-value="Expense(DR)>P/L">
                                        <div class="flex items-start">
                                            <div class="w-10 h-10 md:w-12 md:h-12 rounded-lg bg-purple-50 flex items-center justify-center mr-3">
                                                <i class="fas fa-file-invoice-dollar text-purple-600 text-lg md:text-xl"></i>
                                            </div>
                                            <div>
                                                <h3 class="font-bold text-gray-800 text-sm md:text-base">Expense</h3>
                                                <p class="text-xs text-gray-500 mt-1">Profit & Loss (DR)</p>
                                                <div class="mt-2">
                                                    <span class="inline-block bg-purple-100 text-purple-800 text-xs px-2 py-1 rounded">Debit Balance</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="border border-gray-200 rounded-xl p-3 md:p-4 bg-gray-50">
                                        <div class="flex items-start">
                                            <div class="w-10 h-10 md:w-12 md:h-12 rounded-lg bg-gray-100 flex items-center justify-center mr-3">
                                                <i class="fas fa-info-circle text-gray-500 text-lg md:text-xl"></i>
                                            </div>
                                            <div>
                                                <h3 class="font-bold text-gray-800 text-sm md:text-base">Need Help?</h3>
                                                <p class="text-xs text-gray-500 mt-1">Understanding account types</p>
                                                <button onclick="showQuickGuide()" class="mt-2 text-xs md:text-sm text-purple-600 font-medium hover:text-purple-800">View Guide →</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Traditional dropdown as fallback -->
                                <div class="mt-6">
                                    <label for="accountType" class="block text-sm font-medium text-gray-700 mb-2">
                                        <i class="fas fa-caret-down mr-1"></i> Or select from dropdown:
                                    </label>
                                    <select id="accountType" onchange="updateAccountCategory()"
                                        class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500 transition duration-150 ease-in-out cursor-pointer text-gray-700 shadow-sm text-sm md:text-base">
                                        <option value="" disabled selected>-- Choose Account Type --</option>
                                        <option value="Assets (DR)>B/L">Assets (DR) > Balance Sheet</option>
                                        <option value="Liabilities(CR)>B/L">Liabilities (CR) > Balance Sheet</option>
                                        <option value="Equity(CR)>B/L">Equity (CR) > Balance Sheet</option>
                                        <option value="Income(CR)>P/L">Income (CR) > Profit & Loss</option>
                                        <option value="Expense(DR)>P/L">Expense (DR) > Profit & Loss</option>
                                    </select>
                                </div>
                            </div>

                            <!-- Dynamic Account Category Selection -->
                            <div id="accountCategoryContainer" class="mb-6" style="display: none;">
                                <div class="flex items-center mb-4">
                                    <div class="w-8 h-8 md:w-10 md:h-10 rounded-full bg-blue-100 flex items-center justify-center mr-3">
                                        <i class="fas fa-folder-open text-blue-600 text-sm md:text-base"></i>
                                    </div>
                                    <div>
                                        <h2 class="text-lg md:text-xl font-bold text-gray-800">Select Account Category</h2>
                                        <p class="text-gray-600 text-xs md:text-sm">Choose a specific category for your account</p>
                                    </div>
                                </div>

                                <div class="bg-gray-50 p-4 md:p-5 rounded-xl mb-6">
                                    <div class="flex items-center mb-4">
                                        <div class="w-8 h-8 rounded-full bg-purple-100 flex items-center justify-center mr-3">
                                            <span id="selectedTypeIcon" class="text-purple-600">
                                                <i class="fas fa-chart-pie"></i>
                                            </span>
                                        </div>
                                        <div>
                                            <h3 class="font-bold text-gray-800 text-sm md:text-base">Selected Type: <span id="selectedTypeText" class="text-purple-600">None</span></h3>
                                            <p id="selectedTypeDesc" class="text-gray-600 text-xs md:text-sm">Please select an account type first</p>
                                        </div>
                                    </div>

                                    <label for="accountCategory" class="block text-sm font-medium text-gray-700 mb-2">
                                        Available Categories
                                    </label>
                                    <select id="accountCategory" onchange="showAccountForm()"
                                        class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-150 ease-in-out cursor-pointer text-gray-700 shadow-sm text-sm md:text-base">
                                        <!-- Options populated by JavaScript -->
                                    </select>

                                    <div class="mt-4 flex justify-end">
                                        <button onclick="resetSelection()" class="text-gray-600 hover:text-gray-800 font-medium text-xs md:text-sm flex items-center">
                                            <i class="fas fa-redo mr-1"></i> Reset Selection
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <!-- Status Message -->
                            <div id="statusMessage" class="hidden mt-4 p-3 md:p-4 rounded-lg text-center font-semibold text-sm md:text-base" role="alert"></div>
                        </div>
                    </div>

                    <!-- Right Panel - Information & Preview -->
                    <div class="lg:w-1/3 mt-6 lg:mt-0">
                        <!-- Account Summary Card -->
                        <div class="bg-white rounded-xl shadow-lg p-4 md:p-6 mb-6 border border-gray-100">
                            <h3 class="text-lg font-bold text-gray-800 mb-4 flex items-center">
                                <i class="fas fa-chart-line text-purple-600 mr-2"></i> Account Summary
                            </h3>
                            <div class="space-y-3 md:space-y-4">
                                <div class="flex justify-between items-center">
                                    <span class="text-gray-600 text-sm md:text-base">Total Accounts</span>
                                    <span class="font-bold text-gray-800 text-sm md:text-base">24</span>
                                </div>
                                <div class="flex justify-between items-center">
                                    <span class="text-gray-600 text-sm md:text-base">Active Accounts</span>
                                    <span class="font-bold text-green-600 text-sm md:text-base">22</span>
                                </div>
                                <div class="flex justify-between items-center">
                                    <span class="text-gray-600 text-sm md:text-base">Transactionable</span>
                                    <span class="font-bold text-blue-600 text-sm md:text-base">18</span>
                                </div>
                                <div class="flex justify-between items-center">
                                    <span class="text-gray-600 text-sm md:text-base">Balance Sheet Accounts</span>
                                    <span class="font-bold text-purple-600 text-sm md:text-base">15</span>
                                </div>
                                <div class="flex justify-between items-center">
                                    <span class="text-gray-600 text-sm md:text-base">P&L Accounts</span>
                                    <span class="font-bold text-yellow-600 text-sm md:text-base">9</span>
                                </div>
                            </div>
                            <div class="mt-6 pt-4 border-t border-gray-200">
                                <a href="accounts.php" class="w-full bg-gray-100 hover:bg-gray-200 text-gray-800 font-medium py-2 px-4 rounded-lg transition duration-200 flex items-center justify-center text-sm md:text-base">
                                    <i class="fas fa-list mr-2"></i> View All Accounts
                                </a>
                            </div>
                        </div>

                        <!-- Quick Guide Card -->
                        <div class="bg-gradient-primary text-white rounded-xl shadow-lg p-4 md:p-6">
                            <h3 class="text-lg font-bold mb-4 flex items-center">
                                <i class="fas fa-lightbulb mr-2"></i> Quick Guide
                            </h3>
                            <ul class="space-y-2 md:space-y-3 text-xs md:text-sm">
                                <li class="flex items-start">
                                    <i class="fas fa-check-circle mr-2 mt-0.5"></i>
                                    <span>Assets & Expenses normally have debit balances</span>
                                </li>
                                <li class="flex items-start">
                                    <i class="fas fa-check-circle mr-2 mt-0.5"></i>
                                    <span>Liabilities, Equity & Income normally have credit balances</span>
                                </li>
                                <li class="flex items-start">
                                    <i class="fas fa-check-circle mr-2 mt-0.5"></i>
                                    <span>Balance Sheet accounts track financial position</span>
                                </li>
                                <li class="flex items-start">
                                    <i class="fas fa-check-circle mr-2 mt-0.5"></i>
                                    <span>Profit & Loss accounts track performance</span>
                                </li>
                            </ul>
                            <div class="mt-6">
                                <button onclick="showDetailedGuide()" class="w-full bg-white text-purple-700 hover:bg-gray-100 font-medium py-2 px-4 rounded-lg transition duration-200 text-sm md:text-base">
                                    <i class="fas fa-book-open mr-2"></i> Read Full Documentation
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Modal Form -->
    <div id="accountFormModal" class="modal fixed inset-0 bg-black bg-opacity-50 flex justify-center items-center p-4 z-50" style="display: none;">
        <div class="modal-content bg-white w-full max-w-md rounded-xl md:rounded-2xl p-4 md:p-6 shadow-2xl relative max-h-[90vh] overflow-y-auto">
            <button class="close absolute top-3 right-3 md:top-4 md:right-4 text-gray-400 hover:text-gray-800 text-xl md:text-2xl font-bold leading-none z-10"
                onclick="closeModal()">&times;</button>

            <!-- Modal Header -->
            <div class="mb-4 md:mb-6">
                <div class="w-10 h-10 md:w-12 md:h-12 rounded-lg md:rounded-xl bg-gradient-primary flex items-center justify-center mb-3">
                    <i class="fas fa-plus text-white text-lg md:text-xl"></i>
                </div>
                <h2 class="text-xl md:text-2xl font-bold text-gray-800">Create New Account</h2>
                <p class="text-gray-600 text-xs md:text-sm mt-1">Fill in the details for the new account</p>
            </div>

            <!-- Selected Account Info -->
            <div class="bg-gray-50 p-3 md:p-4 rounded-lg mb-4 md:mb-6">
                <div class="flex items-center justify-between">
                    <div>
                        <h4 class="font-medium text-gray-700 text-xs md:text-sm">Account Type</h4>
                        <p id="modalAccountType" class="text-gray-800 font-bold text-sm md:text-base">-</p>
                    </div>
                    <div>
                        <h4 class="font-medium text-gray-700 text-xs md:text-sm">Category</h4>
                        <p id="modalAccountCategory" class="text-gray-800 font-bold text-sm md:text-base">-</p>
                    </div>
                </div>
            </div>

            <!-- Form -->
            <form id="newAccountForm">
                <div class="space-y-4">
                    <div>
                        <label for="accountName" class="block text-sm font-medium text-gray-700 mb-1 flex items-center">
                            <i class="fas fa-tag mr-2 text-gray-500"></i> Account Name
                        </label>
                        <input type="text" id="accountName" name="accountName" required placeholder="e.g., Bank of America Checking"
                            class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 shadow-sm transition duration-200 text-sm md:text-base">
                    </div>

                    <div>
                        <label for="openingAmount" class="block text-sm font-medium text-gray-700 mb-1 flex items-center">
                            <i class="fas fa-dollar-sign mr-2 text-gray-500"></i> Opening Amount
                        </label>
                        <div class="relative">
                            <span class="absolute left-3 top-3 text-gray-500">$</span>
                            <input type="number" id="openingAmount" name="openingAmount" step="0.01" value="0.00" required
                                class="w-full p-3 pl-8 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 shadow-sm transition duration-200 text-sm md:text-base">
                        </div>
                        <p class="text-xs text-gray-500 mt-1">Enter the initial balance for this account</p>
                    </div>

                    <div>
                        <label for="isTransactionable" class="block text-sm font-medium text-gray-700 mb-1 flex items-center">
                            <i class="fas fa-exchange-alt mr-2 text-gray-500"></i> Is Transactionable?
                        </label>
                        <div class="grid grid-cols-2 gap-2 md:gap-3">
                            <div class="transaction-option border border-gray-300 rounded-lg p-3 cursor-pointer text-center" data-value="yes">
                                <i class="fas fa-check-circle text-lg md:text-xl mb-2 text-gray-400"></i>
                                <p class="font-medium text-sm md:text-base">Yes</p>
                                <p class="text-xs text-gray-500">Can record transactions</p>
                            </div>
                            <div class="transaction-option border border-gray-300 rounded-lg p-3 cursor-pointer text-center" data-value="no">
                                <i class="fas fa-times-circle text-lg md:text-xl mb-2 text-gray-400"></i>
                                <p class="font-medium text-sm md:text-base">No</p>
                                <p class="text-xs text-gray-500">Informational only</p>
                            </div>
                        </div>
                        <select id="isTransactionable" name="isTransactionable" required class="hidden">
                            <option value="yes">Yes</option>
                            <option value="no">No</option>
                        </select>
                    </div>

                    <div>
                        <label for="description" class="block text-sm font-medium text-gray-700 mb-1 flex items-center">
                            <i class="fas fa-align-left mr-2 text-gray-500"></i> Description (Optional)
                        </label>
                        <textarea id="description" name="description" rows="3" placeholder="Add any notes or details about this account..."
                            class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 shadow-sm transition duration-200 resize-none text-sm md:text-base"></textarea>
                    </div>
                </div>

                <div class="flex space-x-2 md:space-x-3 mt-6 md:mt-8">
                    <button type="button" onclick="closeModal()" class="flex-1 p-3 bg-gray-100 hover:bg-gray-200 text-gray-800 font-bold rounded-lg transition duration-300 shadow-sm text-sm md:text-base">
                        Cancel
                    </button>
                    <button type="submit" class="flex-1 p-3 bg-gradient-primary text-white font-bold rounded-lg hover:opacity-90 transition duration-300 shadow-md flex items-center justify-center text-sm md:text-base">
                        <i class="fas fa-save mr-2"></i> Save Account
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Quick Guide Modal -->
    <div id="quickGuideModal" class="modal fixed inset-0 bg-black bg-opacity-50 flex justify-center items-center p-4 z-50" style="display: none;">
        <div class="modal-content bg-white w-full max-w-2xl rounded-xl md:rounded-2xl p-4 md:p-6 shadow-2xl relative max-h-[90vh] overflow-y-auto">
            <button class="close absolute top-3 right-3 md:top-4 md:right-4 text-gray-400 hover:text-gray-800 text-xl md:text-2xl font-bold leading-none"
                onclick="closeGuideModal()">&times;</button>

            <div class="mb-4 md:mb-6">
                <h2 class="text-xl md:text-2xl font-bold text-gray-800 flex items-center">
                    <i class="fas fa-question-circle text-purple-600 mr-2 md:mr-3"></i> Accounting Setup Guide
                </h2>
                <p class="text-gray-600 text-xs md:text-sm mt-1">Understanding account types and their usage</p>
            </div>

            <div class="space-y-4 md:space-y-6">
                <div class="bg-blue-50 p-3 md:p-4 rounded-lg">
                    <h3 class="font-bold text-blue-800 text-base md:text-lg mb-2">Assets (DR) > Balance Sheet</h3>
                    <p class="text-blue-700 text-sm md:text-base">Resources owned by the business that have economic value. Examples: Cash, Inventory, Equipment, Accounts Receivable.</p>
                </div>

                <div class="bg-red-50 p-3 md:p-4 rounded-lg">
                    <h3 class="font-bold text-red-800 text-base md:text-lg mb-2">Liabilities (CR) > Balance Sheet</h3>
                    <p class="text-red-700 text-sm md:text-base">Obligations or debts owed by the business. Examples: Loans, Accounts Payable, Mortgages.</p>
                </div>

                <div class="bg-green-50 p-3 md:p-4 rounded-lg">
                    <h3 class="font-bold text-green-800 text-base md:text-lg mb-2">Equity (CR) > Balance Sheet</h3>
                    <p class="text-green-700 text-sm md:text-base">Owner's interest in the business. Examples: Owner's Capital, Retained Earnings, Common Stock.</p>
                </div>

                <div class="bg-yellow-50 p-3 md:p-4 rounded-lg">
                    <h3 class="font-bold text-yellow-800 text-base md:text-lg mb-2">Income (CR) > Profit & Loss</h3>
                    <p class="text-yellow-700 text-sm md:text-base">Revenue generated from business operations. Examples: Sales Revenue, Service Income, Interest Income.</p>
                </div>

                <div class="bg-purple-50 p-3 md:p-4 rounded-lg">
                    <h3 class="font-bold text-purple-800 text-base md:text-lg mb-2">Expense (DR) > Profit & Loss</h3>
                    <p class="text-purple-700 text-sm md:text-base">Costs incurred to generate revenue. Examples: Rent, Salaries, Utilities, Advertising.</p>
                </div>
            </div>

            <div class="mt-6 md:mt-8 pt-4 md:pt-6 border-t border-gray-200">
                <button onclick="closeGuideModal()" class="w-full p-3 bg-gradient-primary text-white font-bold rounded-lg hover:opacity-90 transition duration-300 shadow-md text-sm md:text-base">
                    Got it, Close Guide
                </button>
            </div>
        </div>
    </div>

    <?php include '../elements/floating-menus.php'; ?>

    <script src="../assets/js/script.js"></script>

    <script>
        // Data structure for the dynamic categories
        const accountCategories = {
            'Assets (DR)>B/L': [
                'Accounts Receivable/Debtors',
                'Current Assets',
                'Cash at bank and in hand',
                'Fixed Assets',
                'Non-current assets'
            ],
            'Liabilities(CR)>B/L': [
                'Creditors',
                'Credit Card',
                'Current liabilities',
                'Non-current liabilities'
            ],
            'Equity(CR)>B/L': [
                'Equity'
            ],
            'Income(CR)>P/L': [
                'Income',
                'Other Income'
            ],
            'Expense(DR)>P/L': [
                'Cost of sales',
                'Expenses',
                'Other Expense'
            ]
        };

        // Account type descriptions
        const accountTypeDescriptions = {
            'Assets (DR)>B/L': 'Resources owned by the business that have economic value',
            'Liabilities(CR)>B/L': 'Obligations or debts owed by the business',
            'Equity(CR)>B/L': "Owner's interest in the business",
            'Income(CR)>P/L': 'Revenue generated from business operations',
            'Expense(DR)>P/L': 'Costs incurred to generate revenue'
        };

        // Account type icons
        const accountTypeIcons = {
            'Assets (DR)>B/L': 'fas fa-landmark',
            'Liabilities(CR)>B/L': 'fas fa-hand-holding-usd',
            'Equity(CR)>B/L': 'fas fa-users',
            'Income(CR)>P/L': 'fas fa-money-check-alt',
            'Expense(DR)>P/L': 'fas fa-file-invoice-dollar'
        };

        const accountTypeSelect = document.getElementById('accountType');
        const categoryContainer = document.getElementById('accountCategoryContainer');
        const categorySelect = document.getElementById('accountCategory');
        const modal = document.getElementById('accountFormModal');
        const form = document.getElementById('newAccountForm');
        const statusMessage = document.getElementById('statusMessage');
        const selectedTypeText = document.getElementById('selectedTypeText');
        const selectedTypeDesc = document.getElementById('selectedTypeDesc');
        const selectedTypeIcon = document.getElementById('selectedTypeIcon');
        const modalAccountType = document.getElementById('modalAccountType');
        const modalAccountCategory = document.getElementById('modalAccountCategory');
        const quickGuideModal = document.getElementById('quickGuideModal');

        // API URL - directly set to your API endpoint
        const apiUrl = '../api/accounts/store.php';

        // Add click events to account type cards
        document.addEventListener('DOMContentLoaded', function() {
            const accountTypeCards = document.querySelectorAll('.account-type-card');
            accountTypeCards.forEach(card => {
                card.addEventListener('click', function() {
                    const value = this.getAttribute('data-value');
                    accountTypeSelect.value = value;
                    updateAccountCategory();

                    // Add visual feedback
                    accountTypeCards.forEach(c => c.classList.remove('border-purple-500', 'bg-purple-50'));
                    this.classList.add('border-purple-500', 'bg-purple-50');
                });
            });

            // Add click events to transaction options
            const transactionOptions = document.querySelectorAll('.transaction-option');
            transactionOptions.forEach(option => {
                option.addEventListener('click', function() {
                    const value = this.getAttribute('data-value');
                    document.getElementById('isTransactionable').value = value;

                    // Update UI
                    transactionOptions.forEach(opt => {
                        opt.classList.remove('border-blue-500', 'bg-blue-50');
                        opt.querySelector('i').classList.remove('text-blue-500');
                        opt.querySelector('i').classList.add('text-gray-400');
                    });

                    this.classList.add('border-blue-500', 'bg-blue-50');
                    this.querySelector('i').classList.remove('text-gray-400');
                    this.querySelector('i').classList.add('text-blue-500');
                });
            });

            // Set default transaction option
            document.querySelector('.transaction-option[data-value="yes"]').click();

            // Attach form submission listener
            form.addEventListener('submit', handleFormSubmit);
        });

        /**
         * Shows a temporary status message in the main container.
         */
        function showStatus(message, isError = false) {
            statusMessage.textContent = message;
            statusMessage.className = `mt-4 p-3 md:p-4 rounded-lg text-center font-semibold ${isError ? 'bg-red-100 text-red-700 border border-red-200' : 'bg-green-100 text-green-700 border border-green-200'}`;
            statusMessage.style.display = 'block';
            statusMessage.scrollIntoView({
                behavior: 'smooth',
                block: 'nearest'
            });

            // Hide after 5 seconds
            setTimeout(() => {
                statusMessage.style.display = 'none';
            }, 5000);
        }

        /**
         * Updates the 'Select account category' dropdown based on the selected 'Account Type'.
         */
        function updateAccountCategory() {
            const selectedType = accountTypeSelect.value;
            categorySelect.innerHTML = ''; // Clear previous options

            if (selectedType && accountCategories[selectedType]) {
                // Update selected type display
                selectedTypeText.textContent = selectedType.split('>')[0];
                selectedTypeDesc.textContent = accountTypeDescriptions[selectedType] || '';

                // Update icon
                const iconClass = accountTypeIcons[selectedType] || 'fas fa-chart-pie';
                selectedTypeIcon.className = iconClass;

                // Add a disabled/selected placeholder option
                const placeholderOption = document.createElement('option');
                placeholderOption.value = '';
                placeholderOption.textContent = '-- Select a category --';
                placeholderOption.disabled = true;
                placeholderOption.selected = true;
                categorySelect.appendChild(placeholderOption);

                // Populate with new options
                accountCategories[selectedType].forEach(category => {
                    const option = document.createElement('option');
                    option.value = category;
                    option.textContent = category;
                    categorySelect.appendChild(option);
                });

                categoryContainer.style.display = 'block'; // Show the category dropdown

                // Scroll to category section
                setTimeout(() => {
                    categoryContainer.scrollIntoView({
                        behavior: 'smooth',
                        block: 'nearest'
                    });
                }, 300);
            } else {
                categoryContainer.style.display = 'none'; // Hide if no valid type is selected
            }
        }

        /**
         * Shows the modal form when an account category is selected.
         */
        function showAccountForm() {
            if (categorySelect.value) {
                // Update modal with selected values
                modalAccountType.textContent = accountTypeSelect.value.split('>')[0];
                modalAccountCategory.textContent = categorySelect.value;

                modal.style.display = 'flex';
                document.body.style.overflow = 'hidden'; // Prevent background scrolling
            }
        }

        /**
         * Hides the modal form and resets the form.
         */
        function closeModal() {
            modal.style.display = 'none';
            document.body.style.overflow = 'auto'; // Re-enable scrolling
        }

        /**
         * Shows the quick guide modal.
         */
        function showQuickGuide() {
            quickGuideModal.style.display = 'flex';
            document.body.style.overflow = 'hidden';
        }

        /**
         * Hides the quick guide modal.
         */
        function closeGuideModal() {
            quickGuideModal.style.display = 'none';
            document.body.style.overflow = 'auto';
        }

        /**
         * Resets the account type and category selection.
         */
        function resetSelection() {
            accountTypeSelect.value = '';
            categorySelect.innerHTML = '';
            categoryContainer.style.display = 'none';
            selectedTypeText.textContent = 'None';
            selectedTypeDesc.textContent = 'Please select an account type first';
            selectedTypeIcon.className = 'fas fa-chart-pie';

            // Reset card selections
            document.querySelectorAll('.account-type-card').forEach(card => {
                card.classList.remove('border-purple-500', 'bg-purple-50');
            });

            showStatus('Selection has been reset. Please choose an account type again.', false);
        }

        /**
         * Demo function for showing detailed guide.
         */
        function showDetailedGuide() {
            showQuickGuide();
        }

        // Close the modal if the user clicks anywhere outside of it
        window.onclick = function(event) {
            if (event.target == modal) {
                closeModal();
            }
            if (event.target == quickGuideModal) {
                closeGuideModal();
            }
        }

        /**
         * Handles the form submission (Save button click) and sends data to the PHP API.
         */
        async function handleFormSubmit(event) {
            event.preventDefault();

            const data = {
                accountType: accountTypeSelect.value,
                accountCategory: categorySelect.value,
                accountName: document.getElementById('accountName').value,
                openingAmount: document.getElementById('openingAmount').value,
                isTransactionable: document.getElementById('isTransactionable').value,
                description: document.getElementById('description').value,
            };

            // Show loading state
            const submitBtn = form.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Saving...';
            submitBtn.disabled = true;

            try {
                const response = await fetch(apiUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify(data),
                });

                const result = await response.json();

                // Reset button state
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;

                if (result.success) {
                    closeModal();
                    showStatus(`Account "${data.accountName}" created successfully! Balance: $${result.data.balance}`, false);

                    // Reset form and selections
                    form.reset();
                    resetSelection();

                    // Reset transaction option
                    document.querySelector('.transaction-option[data-value="yes"]').click();
                } else {
                    showStatus(`Error: ${result.message || 'Failed to create account'}`, true);
                }

            } catch (error) {
                console.error('Error saving account:', error);

                // Reset button state
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;

                showStatus('Failed to save account. Please check your connection and try again.', true);
            }
        }
    </script>
</body>

</html>