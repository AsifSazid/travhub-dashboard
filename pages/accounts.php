<?php
$ip_port = @file_get_contents('../ippath.txt');
if (empty($ip_port)) {
    $ip_port = "http://103.104.219.3:898";
}

$base_ip_path = trim($ip_port, "/");
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Accounting - Account Laser Records</title>
    <link rel="icon" type="image/png" href="../assets/images/logo/round-logo.png" sizes="16x16">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .account-card {
            transition: all 0.3s ease;
            border-left: 4px solid #3b82f6;
            cursor: pointer;
        }

        .account-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
            border-left-color: #2563eb;
        }

        .account-card.deposit {
            border-left-color: #10b981;
        }

        .account-card.withdraw {
            border-left-color: #ef4444;
        }

        .account-card.neutral {
            border-left-color: #6b7280;
        }

        .balance-badge {
            font-size: 1.1rem;
            font-weight: 600;
            padding: 0.4rem 1rem;
        }

        .category-tag {
            font-size: 0.75rem;
            padding: 0.2rem 0.6rem;
            background: #e0f2fe;
            color: #0369a1;
            border-radius: 20px;
        }

        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: rgba(0, 0, 0, 0.5);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 1000;
            opacity: 0;
            visibility: hidden;
            transition: opacity 0.3s ease, visibility 0.3s ease;
        }

        .modal-overlay.active {
            opacity: 1;
            visibility: visible;
        }

        .modal-content {
            background: white;
            border-radius: 0.5rem;
            width: 90%;
            max-width: 1200px;
            max-height: 90vh;
            overflow-y: auto;
            transform: translateY(20px);
            transition: transform 0.3s ease;
        }

        .modal-overlay.active .modal-content {
            transform: translateY(0);
        }

        .spinner {
            width: 3rem;
            height: 3rem;
            border: 3px solid #e5e7eb;
            border-top-color: #3b82f6;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            to {
                transform: rotate(360deg);
            }
        }
    </style>
</head>

<body class="bg-gray-50 font-sans">
    <!-- Top Navigation -->
    <?php include '../elements/header.php'; ?>

    <!-- Sidebar -->
    <?php include '../elements/aside.php'; ?>

    <!-- Preview Modal -->
    <div id="previewModal" class="preview-modal">
        <div class="preview-content">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-semibold text-gray-800" id="previewTitle">File Preview</h3>
                <button onclick="closePreview()" class="text-gray-500 hover:text-gray-700 text-2xl">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div id="modalPreviewContent" class="p-4">
                <!-- Preview content will be loaded here -->
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <main id="mainContent" class="pt-16 pl-0 lg:pl-64 transition-all duration-300 h-full">
        <div class="p-4 md:p-6 h-full">
            <!-- Header Section -->
            <div class="mb-6">
                <h1 class="text-2xl md:text-3xl font-bold text-gray-800">Accounting Dashboard</h1>
                <p class="text-gray-600 mt-2">Monitor all your financial activities in one place</p>
            </div>
    
            <!-- Quick Actions - Top Section -->
            <div class="mb-8">
                <div class="flex justify-between items-center mb-4">
                    <h2 class="text-lg font-semibold text-gray-800">Quick Actions</h2>
                    <button class="text-sm text-blue-600 hover:text-blue-800 font-medium flex items-center">
                        <i class="fas fa-history mr-1"></i> Recent Actions
                    </button>
                </div>
                <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-7 gap-4">
                    <!-- Add Income -->
                    <a href="accounts-receive.php" class="group bg-gradient-to-br from-green-50 to-green-100 border border-green-200 hover:border-green-400 hover:from-green-100 hover:to-green-200 p-4 rounded-xl text-center transition-all duration-300 hover:shadow-lg hover:-translate-y-1">
                        <div class="bg-green-100 group-hover:bg-green-200 w-12 h-12 rounded-lg flex items-center justify-center mx-auto mb-3 transition-colors">
                            <i class="fas fa-plus-circle text-green-600 text-xl"></i>
                        </div>
                        <p class="font-semibold text-green-800">Receive</p>
                        <p class="text-xs text-green-600 mt-1">Record new received info</p>
                    </a>
    
                    <!-- Add Expense -->
                    <a href="accounts-payment.php" class="group bg-gradient-to-br from-red-50 to-red-100 border border-red-200 hover:border-red-400 hover:from-red-100 hover:to-red-200 p-4 rounded-xl text-center transition-all duration-300 hover:shadow-lg hover:-translate-y-1">
                        <div class="bg-red-100 group-hover:bg-red-200 w-12 h-12 rounded-lg flex items-center justify-center mx-auto mb-3 transition-colors">
                            <i class="fas fa-minus-circle text-red-600 text-xl"></i>
                        </div>
                        <p class="font-semibold text-red-800">Payment</p>
                        <p class="text-xs text-red-600 mt-1">Record new payment info</p>
                    </a>
    
                    <!-- Create Invoice -->
                    <a href="index-invoice.php" class="group bg-gradient-to-br from-blue-50 to-blue-100 border border-blue-200 hover:border-blue-400 hover:from-blue-100 hover:to-blue-200 p-4 rounded-xl text-center transition-all duration-300 hover:shadow-lg hover:-translate-y-1" target="_blank">
                        <div class="bg-blue-100 group-hover:bg-blue-200 w-12 h-12 rounded-lg flex items-center justify-center mx-auto mb-3 transition-colors">
                            <i class="fas fa-file-invoice text-blue-600 text-xl"></i>
                        </div>
                        <p class="font-semibold text-blue-800">Invoice Lists</p>
                        <p class="text-xs text-blue-600 mt-1">Generate new invoice</p>
                    </a>
    
                    <!-- Generate Report -->
                    <button class="group bg-gradient-to-br from-purple-50 to-purple-100 border border-purple-200 hover:border-purple-400 hover:from-purple-100 hover:to-purple-200 p-4 rounded-xl text-center transition-all duration-300 hover:shadow-lg hover:-translate-y-1">
                        <div class="bg-purple-100 group-hover:bg-purple-200 w-12 h-12 rounded-lg flex items-center justify-center mx-auto mb-3 transition-colors">
                            <i class="fas fa-chart-bar text-purple-600 text-xl"></i>
                        </div>
                        <p class="font-semibold text-purple-800">Generate Report</p>
                        <p class="text-xs text-purple-600 mt-1">Financial reports</p>
                    </button>
    
                    <!-- Transfer Funds -->
                    <button class="group bg-gradient-to-br from-yellow-50 to-yellow-100 border border-yellow-200 hover:border-yellow-400 hover:from-yellow-100 hover:to-yellow-200 p-4 rounded-xl text-center transition-all duration-300 hover:shadow-lg hover:-translate-y-1">
                        <div class="bg-yellow-100 group-hover:bg-yellow-200 w-12 h-12 rounded-lg flex items-center justify-center mx-auto mb-3 transition-colors">
                            <i class="fas fa-exchange-alt text-yellow-600 text-xl"></i>
                        </div>
                        <p class="font-semibold text-yellow-800">Transfer</p>
                        <p class="text-xs text-yellow-600 mt-1">Funds transfer</p>
                    </button>
    
                    <!-- Quick Payment -->
                    <button class="group bg-gradient-to-br from-indigo-50 to-indigo-100 border border-indigo-200 hover:border-indigo-400 hover:from-indigo-100 hover:to-indigo-200 p-4 rounded-xl text-center transition-all duration-300 hover:shadow-lg hover:-translate-y-1">
                        <div class="bg-indigo-100 group-hover:bg-indigo-200 w-12 h-12 rounded-lg flex items-center justify-center mx-auto mb-3 transition-colors">
                            <i class="fas fa-credit-card text-indigo-600 text-xl"></i>
                        </div>
                        <p class="font-semibold text-indigo-800">PITTY Cash</p>
                        <p class="text-xs text-indigo-600 mt-1">Make payment</p>
                    </button>
                    
                    <!-- Ledger List -->
                    <a href="accounts-ledger.php" class="group bg-gradient-to-br from-teal-50 to-teal-100 border border-teal-200 hover:border-teal-400 hover:from-teal-100 hover:to-teal-200 p-4 rounded-xl text-center transition-all duration-300 hover:shadow-lg hover:-translate-y-1" target="_blank">
                        <div class="bg-teal-100 group-hover:bg-teal-200 w-12 h-12 rounded-lg flex items-center justify-center mx-auto mb-3 transition-colors">
                            <i class="fas fa-book-open text-teal-600 text-xl"></i>
                        </div>
                        <p class="font-semibold text-teal-800">Ledger Lists</p>
                        <p class="text-xs text-teal-600 mt-1">View your all Ledgers here</p>
                    </a>
                </div>
            </div>
    
            <!-- Stats Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                <!-- Total Balance -->
                <div class="account-card bg-white rounded-xl shadow-lg p-6 border-l-4 border-blue-500 hover:shadow-xl transition-all duration-300">
                    <div class="flex justify-between items-start">
                        <div>
                            <p class="text-gray-600 text-sm font-medium">Total Balance</p>
                            <h2 class="text-3xl font-bold mt-2 text-gray-800">৳ 1,25,850</h2>
                            <div class="flex items-center mt-2">
                                <span class="text-green-600 text-sm font-medium flex items-center">
                                    <i class="fas fa-arrow-up mr-1 text-xs"></i> 12.5%
                                </span>
                                <span class="text-gray-500 text-sm ml-2">from last month</span>
                            </div>
                        </div>
                        <div class="bg-blue-50 p-3 rounded-lg">
                            <i class="fas fa-wallet text-blue-600 text-2xl"></i>
                        </div>
                    </div>
                    <div class="mt-4 pt-4 border-t border-gray-100">
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-600">Cash: ৳ 25,850</span>
                            <span class="text-gray-600">Bank: ৳ 1,00,000</span>
                        </div>
                    </div>
                </div>
    
                <!-- Monthly Income -->
                <div class="account-card bg-white rounded-xl shadow-lg p-6 border-l-4 border-green-500 hover:shadow-xl transition-all duration-300">
                    <div class="flex justify-between items-start">
                        <div>
                            <p class="text-gray-600 text-sm font-medium">Monthly Income</p>
                            <h2 class="text-3xl font-bold mt-2 text-gray-800">৳ 45,200</h2>
                            <div class="flex items-center mt-2">
                                <span class="text-green-600 text-sm font-medium flex items-center">
                                    <i class="fas fa-arrow-up mr-1 text-xs"></i> 8.2%
                                </span>
                                <span class="text-gray-500 text-sm ml-2">from last month</span>
                            </div>
                        </div>
                        <div class="bg-green-50 p-3 rounded-lg">
                            <i class="fas fa-arrow-down text-green-600 text-2xl"></i>
                        </div>
                    </div>
                    <div class="mt-4 pt-4 border-t border-gray-100">
                        <div class="text-sm text-gray-600">
                            <span class="font-medium">Today:</span> ৳ 3,500
                        </div>
                    </div>
                </div>
    
                <!-- Monthly Expense -->
                <div class="account-card bg-white rounded-xl shadow-lg p-6 border-l-4 border-red-500 hover:shadow-xl transition-all duration-300">
                    <div class="flex justify-between items-start">
                        <div>
                            <p class="text-gray-600 text-sm font-medium">Monthly Expense</p>
                            <h2 class="text-3xl font-bold mt-2 text-gray-800">৳ 28,750</h2>
                            <div class="flex items-center mt-2">
                                <span class="text-red-600 text-sm font-medium flex items-center">
                                    <i class="fas fa-arrow-down mr-1 text-xs"></i> 3.1%
                                </span>
                                <span class="text-gray-500 text-sm ml-2">from last month</span>
                            </div>
                        </div>
                        <div class="bg-red-50 p-3 rounded-lg">
                            <i class="fas fa-arrow-up text-red-600 text-2xl"></i>
                        </div>
                    </div>
                    <div class="mt-4 pt-4 border-t border-gray-100">
                        <div class="text-sm text-gray-600">
                            <span class="font-medium">Today:</span> ৳ 1,200
                        </div>
                    </div>
                </div>
    
                <!-- Pending Invoices -->
                <div class="account-card bg-white rounded-xl shadow-lg p-6 border-l-4 border-purple-500 hover:shadow-xl transition-all duration-300">
                    <div class="flex justify-between items-start">
                        <div>
                            <p class="text-gray-600 text-sm font-medium">Pending Invoices</p>
                            <h2 class="text-3xl font-bold mt-2 text-gray-800">৳ 15,300</h2>
                            <div class="flex items-center mt-2">
                                <span class="text-red-600 text-sm font-medium">5 invoices</span>
                                <span class="text-gray-500 text-sm ml-2">overdue</span>
                            </div>
                        </div>
                        <div class="bg-purple-50 p-3 rounded-lg">
                            <i class="fas fa-file-invoice text-purple-600 text-2xl"></i>
                        </div>
                    </div>
                    <div class="mt-4 pt-4 border-t border-gray-100">
                        <div class="text-sm text-gray-600">
                            <span class="font-medium">Due Today:</span> ৳ 2,800
                        </div>
                    </div>
                </div>
            </div>
    
            <!-- Charts and Recent Transactions -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
                <!-- Income vs Expense Chart -->
                <div class="lg:col-span-2">
                    <div class="bg-white rounded-xl shadow p-6 h-full">
                        <div class="flex justify-between items-center mb-6">
                            <h3 class="text-lg font-semibold text-gray-800">Income vs Expense Trend</h3>
                            <div class="flex space-x-2">
                                <button class="px-3 py-1 text-sm bg-blue-100 text-blue-700 rounded-lg font-medium">Month</button>
                                <button class="px-3 py-1 text-sm text-gray-600 hover:bg-gray-100 rounded-lg font-medium">Quarter</button>
                                <button class="px-3 py-1 text-sm text-gray-600 hover:bg-gray-100 rounded-lg font-medium">Year</button>
                            </div>
                        </div>
                        
                        <!-- Simplified Chart -->
                        <div class="h-64 flex items-end space-x-4">
                            <!-- January -->
                            <div class="flex-1">
                                <div class="text-center mb-2">
                                    <span class="text-sm text-gray-600">Jan</span>
                                </div>
                                <div class="relative h-48">
                                    <div class="absolute bottom-0 left-1/4 w-1/2 bg-green-400 rounded-t-lg" style="height: 60%">
                                        <div class="absolute -top-6 left-1/2 transform -translate-x-1/2 text-xs font-medium text-green-700">৳45K</div>
                                    </div>
                                    <div class="absolute bottom-0 left-1/4 w-1/2 bg-red-400 rounded-t-lg" style="height: 40%">
                                        <div class="absolute -top-6 left-1/2 transform -translate-x-1/2 text-xs font-medium text-red-700">৳30K</div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- February -->
                            <div class="flex-1">
                                <div class="text-center mb-2">
                                    <span class="text-sm text-gray-600">Feb</span>
                                </div>
                                <div class="relative h-48">
                                    <div class="absolute bottom-0 left-1/4 w-1/2 bg-green-400 rounded-t-lg" style="height: 70%">
                                        <div class="absolute -top-6 left-1/2 transform -translate-x-1/2 text-xs font-medium text-green-700">৳52K</div>
                                    </div>
                                    <div class="absolute bottom-0 left-1/4 w-1/2 bg-red-400 rounded-t-lg" style="height: 50%">
                                        <div class="absolute -top-6 left-1/2 transform -translate-x-1/2 text-xs font-medium text-red-700">৳38K</div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- March -->
                            <div class="flex-1">
                                <div class="text-center mb-2">
                                    <span class="text-sm text-gray-600">Mar</span>
                                </div>
                                <div class="relative h-48">
                                    <div class="absolute bottom-0 left-1/4 w-1/2 bg-green-400 rounded-t-lg" style="height: 80%">
                                        <div class="absolute -top-6 left-1/2 transform -translate-x-1/2 text-xs font-medium text-green-700">৳60K</div>
                                    </div>
                                    <div class="absolute bottom-0 left-1/4 w-1/2 bg-red-400 rounded-t-lg" style="height: 45%">
                                        <div class="absolute -top-6 left-1/2 transform -translate-x-1/2 text-xs font-medium text-red-700">৳34K</div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- April -->
                            <div class="flex-1">
                                <div class="text-center mb-2">
                                    <span class="text-sm text-gray-600">Apr</span>
                                </div>
                                <div class="relative h-48">
                                    <div class="absolute bottom-0 left-1/4 w-1/2 bg-green-400 rounded-t-lg" style="height: 90%">
                                        <div class="absolute -top-6 left-1/2 transform -translate-x-1/2 text-xs font-medium text-green-700">৳68K</div>
                                    </div>
                                    <div class="absolute bottom-0 left-1/4 w-1/2 bg-red-400 rounded-t-lg" style="height: 55%">
                                        <div class="absolute -top-6 left-1/2 transform -translate-x-1/2 text-xs font-medium text-red-700">৳42K</div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- May -->
                            <div class="flex-1">
                                <div class="text-center mb-2">
                                    <span class="text-sm text-gray-600">May</span>
                                </div>
                                <div class="relative h-48">
                                    <div class="absolute bottom-0 left-1/4 w-1/2 bg-green-400 rounded-t-lg" style="height: 85%">
                                        <div class="absolute -top-6 left-1/2 transform -translate-x-1/2 text-xs font-medium text-green-700">৳64K</div>
                                    </div>
                                    <div class="absolute bottom-0 left-1/4 w-1/2 bg-red-400 rounded-t-lg" style="height: 60%">
                                        <div class="absolute -top-6 left-1/2 transform -translate-x-1/2 text-xs font-medium text-red-700">৳48K</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="flex justify-center space-x-8 mt-6">
                            <div class="flex items-center">
                                <div class="w-3 h-3 bg-green-400 rounded-full mr-2"></div>
                                <span class="text-sm text-gray-700">Income</span>
                            </div>
                            <div class="flex items-center">
                                <div class="w-3 h-3 bg-red-400 rounded-full mr-2"></div>
                                <span class="text-sm text-gray-700">Expense</span>
                            </div>
                            <div class="flex items-center">
                                <div class="w-3 h-3 bg-blue-400 rounded-full mr-2"></div>
                                <span class="text-sm text-gray-700">Net Profit</span>
                            </div>
                        </div>
                    </div>
                </div>
    
                <!-- Upcoming Bills -->
                <div class="bg-white rounded-xl shadow p-6">
                    <div class="flex justify-between items-center mb-6">
                        <h3 class="text-lg font-semibold text-gray-800">Upcoming Bills</h3>
                        <button class="text-blue-600 hover:text-blue-800 text-sm font-medium">
                            View All <i class="fas fa-arrow-right ml-1"></i>
                        </button>
                    </div>
                    
                    <div class="space-y-4">
                        <!-- Bill 1 -->
                        <div class="flex items-center justify-between p-4 bg-gradient-to-r from-yellow-50 to-white border-l-4 border-yellow-500 rounded-lg">
                            <div class="flex items-center">
                                <div class="bg-yellow-100 p-3 rounded-lg mr-4">
                                    <i class="fas fa-bolt text-yellow-600"></i>
                                </div>
                                <div>
                                    <p class="font-semibold text-gray-800">Electricity Bill</p>
                                    <p class="text-sm text-gray-600">Due in 3 days</p>
                                </div>
                            </div>
                            <div class="text-right">
                                <p class="font-bold text-red-600">৳ 2,800</p>
                                <p class="text-xs text-gray-500">15 Jun 2024</p>
                            </div>
                        </div>
                        
                        <!-- Bill 2 -->
                        <div class="flex items-center justify-between p-4 bg-gradient-to-r from-red-50 to-white border-l-4 border-red-500 rounded-lg">
                            <div class="flex items-center">
                                <div class="bg-red-100 p-3 rounded-lg mr-4">
                                    <i class="fas fa-home text-red-600"></i>
                                </div>
                                <div>
                                    <p class="font-semibold text-gray-800">Rent Payment</p>
                                    <p class="text-sm text-gray-600">Due in 8 days</p>
                                </div>
                            </div>
                            <div class="text-right">
                                <p class="font-bold text-red-600">৳ 15,000</p>
                                <p class="text-xs text-gray-500">20 Jun 2024</p>
                            </div>
                        </div>
                        
                        <!-- Bill 3 -->
                        <div class="flex items-center justify-between p-4 bg-gradient-to-r from-green-50 to-white border-l-4 border-green-500 rounded-lg">
                            <div class="flex items-center">
                                <div class="bg-green-100 p-3 rounded-lg mr-4">
                                    <i class="fas fa-users text-green-600"></i>
                                </div>
                                <div>
                                    <p class="font-semibold text-gray-800">Salary Payment</p>
                                    <p class="text-sm text-gray-600">Due in 13 days</p>
                                </div>
                            </div>
                            <div class="text-right">
                                <p class="font-bold text-green-600">+৳ 45,000</p>
                                <p class="text-xs text-gray-500">25 Jun 2024</p>
                            </div>
                        </div>
                        
                        <!-- Bill 4 -->
                        <div class="flex items-center justify-between p-4 bg-gradient-to-r from-blue-50 to-white border-l-4 border-blue-500 rounded-lg">
                            <div class="flex items-center">
                                <div class="bg-blue-100 p-3 rounded-lg mr-4">
                                    <i class="fas fa-wifi text-blue-600"></i>
                                </div>
                                <div>
                                    <p class="font-semibold text-gray-800">Internet Bill</p>
                                    <p class="text-sm text-gray-600">Due today</p>
                                </div>
                            </div>
                            <div class="text-right">
                                <p class="font-bold text-red-600">৳ 1,200</p>
                                <p class="text-xs text-gray-500">12 Jun 2024</p>
                            </div>
                        </div>
                    </div>
                    
                    <button class="w-full mt-6 py-3 bg-gray-50 hover:bg-gray-100 text-gray-700 font-medium rounded-lg border-2 border-dashed border-gray-300 transition-colors flex items-center justify-center">
                        <i class="fas fa-plus mr-2"></i> Add New Bill Reminder
                    </button>
                </div>
            </div>
    
            <!-- Recent Transactions -->
            <div class="bg-white rounded-xl shadow p-6 mb-8">
                <div class="flex justify-between items-center mb-6">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-800">Recent Transactions</h3>
                        <p class="text-sm text-gray-600 mt-1">Latest 10 transactions from all accounts</p>
                    </div>
                    <div class="flex space-x-3">
                        <button class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition-colors">
                            <i class="fas fa-download mr-2"></i> Export
                        </button>
                        <button class="px-4 py-2 border border-gray-300 hover:bg-gray-50 text-gray-700 font-medium rounded-lg transition-colors">
                            Filter <i class="fas fa-filter ml-2"></i>
                        </button>
                    </div>
                </div>
                
                <div class="overflow-x-auto">
                    <table class="w-full min-w-full">
                        <thead>
                            <tr class="border-b border-gray-200">
                                <th class="text-left py-3 px-4 text-gray-700 font-semibold">Date & Time</th>
                                <th class="text-left py-3 px-4 text-gray-700 font-semibold">Transaction</th>
                                <th class="text-left py-3 px-4 text-gray-700 font-semibold">Category</th>
                                <th class="text-left py-3 px-4 text-gray-700 font-semibold">Account</th>
                                <th class="text-left py-3 px-4 text-gray-700 font-semibold">Amount</th>
                                <th class="text-left py-3 px-4 text-gray-700 font-semibold">Status</th>
                                <th class="text-left py-3 px-4 text-gray-700 font-semibold">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Transaction 1 -->
                            <tr class="border-b border-gray-100 hover:bg-gray-50 transition-colors">
                                <td class="py-3 px-4">
                                    <div class="font-medium text-gray-800">12 Jun 2024</div>
                                    <div class="text-sm text-gray-500">10:30 AM</div>
                                </td>
                                <td class="py-3 px-4">
                                    <div class="flex items-center">
                                        <div class="bg-blue-100 p-2 rounded-lg mr-3">
                                            <i class="fas fa-shopping-cart text-blue-600"></i>
                                        </div>
                                        <div>
                                            <p class="font-medium text-gray-800">Office Supplies</p>
                                            <p class="text-sm text-gray-500">ABC Store - Invoice #INV-0012</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="py-3 px-4">
                                    <span class="category-tag">Office Expense</span>
                                </td>
                                <td class="py-3 px-4">
                                    <div class="flex items-center">
                                        <div class="w-2 h-2 bg-blue-500 rounded-full mr-2"></div>
                                        <span class="text-gray-700">City Bank</span>
                                    </div>
                                </td>
                                <td class="py-3 px-4">
                                    <span class="font-bold text-red-600">-৳ 2,500</span>
                                </td>
                                <td class="py-3 px-4">
                                    <span class="px-3 py-1 bg-red-100 text-red-800 rounded-full text-xs font-medium">Completed</span>
                                </td>
                                <td class="py-3 px-4">
                                    <button class="text-gray-500 hover:text-blue-600 p-1">
                                        <i class="fas fa-ellipsis-h"></i>
                                    </button>
                                </td>
                            </tr>
                            
                            <!-- Transaction 2 -->
                            <tr class="border-b border-gray-100 hover:bg-gray-50 transition-colors">
                                <td class="py-3 px-4">
                                    <div class="font-medium text-gray-800">10 Jun 2024</div>
                                    <div class="text-sm text-gray-500">02:15 PM</div>
                                </td>
                                <td class="py-3 px-4">
                                    <div class="flex items-center">
                                        <div class="bg-green-100 p-2 rounded-lg mr-3">
                                            <i class="fas fa-file-invoice-dollar text-green-600"></i>
                                        </div>
                                        <div>
                                            <p class="font-medium text-gray-800">Client Payment</p>
                                            <p class="text-sm text-gray-500">XYZ Corp - Project #PRJ-045</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="py-3 px-4">
                                    <span class="category-tag" style="background: #dcfce7; color: #166534;">Revenue</span>
                                </td>
                                <td class="py-3 px-4">
                                    <div class="flex items-center">
                                        <div class="w-2 h-2 bg-green-500 rounded-full mr-2"></div>
                                        <span class="text-gray-700">Cash</span>
                                    </div>
                                </td>
                                <td class="py-3 px-4">
                                    <span class="font-bold text-green-600">+৳ 25,000</span>
                                </td>
                                <td class="py-3 px-4">
                                    <span class="px-3 py-1 bg-green-100 text-green-800 rounded-full text-xs font-medium">Completed</span>
                                </td>
                                <td class="py-3 px-4">
                                    <button class="text-gray-500 hover:text-blue-600 p-1">
                                        <i class="fas fa-ellipsis-h"></i>
                                    </button>
                                </td>
                            </tr>
                            
                            <!-- Transaction 3 -->
                            <tr class="border-b border-gray-100 hover:bg-gray-50 transition-colors">
                                <td class="py-3 px-4">
                                    <div class="font-medium text-gray-800">08 Jun 2024</div>
                                    <div class="text-sm text-gray-500">11:45 AM</div>
                                </td>
                                <td class="py-3 px-4">
                                    <div class="flex items-center">
                                        <div class="bg-purple-100 p-2 rounded-lg mr-3">
                                            <i class="fas fa-wifi text-purple-600"></i>
                                        </div>
                                        <div>
                                            <p class="font-medium text-gray-800">Internet Bill</p>
                                            <p class="text-sm text-gray-500">Monthly subscription - Jun</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="py-3 px-4">
                                    <span class="category-tag" style="background: #f3e8ff; color: #7c3aed;">Utilities</span>
                                </td>
                                <td class="py-3 px-4">
                                    <div class="flex items-center">
                                        <div class="w-2 h-2 bg-red-500 rounded-full mr-2"></div>
                                        <span class="text-gray-700">DBBL</span>
                                    </div>
                                </td>
                                <td class="py-3 px-4">
                                    <span class="font-bold text-red-600">-৳ 1,200</span>
                                </td>
                                <td class="py-3 px-4">
                                    <span class="px-3 py-1 bg-yellow-100 text-yellow-800 rounded-full text-xs font-medium">Pending</span>
                                </td>
                                <td class="py-3 px-4">
                                    <button class="text-gray-500 hover:text-blue-600 p-1">
                                        <i class="fas fa-ellipsis-h"></i>
                                    </button>
                                </td>
                            </tr>
                            
                            <!-- Transaction 4 -->
                            <tr class="hover:bg-gray-50 transition-colors">
                                <td class="py-3 px-4">
                                    <div class="font-medium text-gray-800">05 Jun 2024</div>
                                    <div class="text-sm text-gray-500">09:20 AM</div>
                                </td>
                                <td class="py-3 px-4">
                                    <div class="flex items-center">
                                        <div class="bg-yellow-100 p-2 rounded-lg mr-3">
                                            <i class="fas fa-tools text-yellow-600"></i>
                                        </div>
                                        <div>
                                            <p class="font-medium text-gray-800">Equipment Maintenance</p>
                                            <p class="text-sm text-gray-500">Printer servicing - Tech Solutions</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="py-3 px-4">
                                    <span class="category-tag" style="background: #fef3c7; color: #92400e;">Maintenance</span>
                                </td>
                                <td class="py-3 px-4">
                                    <div class="flex items-center">
                                        <div class="w-2 h-2 bg-yellow-500 rounded-full mr-2"></div>
                                        <span class="text-gray-700">Bkash</span>
                                    </div>
                                </td>
                                <td class="py-3 px-4">
                                    <span class="font-bold text-red-600">-৳ 3,500</span>
                                </td>
                                <td class="py-3 px-4">
                                    <span class="px-3 py-1 bg-green-100 text-green-800 rounded-full text-xs font-medium">Completed</span>
                                </td>
                                <td class="py-3 px-4">
                                    <button class="text-gray-500 hover:text-blue-600 p-1">
                                        <i class="fas fa-ellipsis-h"></i>
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                
                <div class="mt-6 pt-6 border-t border-gray-200 flex justify-between items-center">
                    <p class="text-sm text-gray-600">Showing 4 of 128 transactions</p>
                    <button class="px-4 py-2 border border-gray-300 hover:bg-gray-50 text-gray-700 font-medium rounded-lg transition-colors flex items-center">
                        Load More <i class="fas fa-arrow-down ml-2"></i>
                    </button>
                </div>
            </div>
    
            <!-- Quick Summary Footer -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="bg-gradient-to-r from-gray-50 to-white border border-gray-200 rounded-xl p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-600 font-medium">Total Accounts</p>
                            <h4 class="text-2xl font-bold mt-1 text-gray-800">8</h4>
                            <p class="text-sm text-gray-500 mt-1">3 Bank, 3 Digital, 2 Cash</p>
                        </div>
                        <div class="text-blue-600 text-4xl">
                            <i class="fas fa-landmark"></i>
                        </div>
                    </div>
                </div>
                
                <div class="bg-gradient-to-r from-gray-50 to-white border border-gray-200 rounded-xl p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-600 font-medium">This Month Transactions</p>
                            <h4 class="text-2xl font-bold mt-1 text-gray-800">42</h4>
                            <p class="text-sm text-gray-500 mt-1">32 Income, 10 Expense</p>
                        </div>
                        <div class="text-green-600 text-4xl">
                            <i class="fas fa-exchange-alt"></i>
                        </div>
                    </div>
                </div>
                
                <div class="bg-gradient-to-r from-gray-50 to-white border border-gray-200 rounded-xl p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-600 font-medium">Active Invoices</p>
                            <h4 class="text-2xl font-bold mt-1 text-gray-800">12</h4>
                            <p class="text-sm text-gray-500 mt-1">5 Paid, 7 Pending</p>
                        </div>
                        <div class="text-purple-600 text-4xl">
                            <i class="fas fa-file-alt"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <?php include '../elements/floating-menus.php'; ?>

    <script src="../assets/js/script.js"></script>

</body>

</html>