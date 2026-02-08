<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Instrument Tracking System</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .category-tag {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 9999px;
            font-size: 12px;
            font-weight: 500;
        }

        .modal-scroll::-webkit-scrollbar {
            width: 8px;
        }

        .modal-scroll::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 4px;
        }

        .modal-scroll::-webkit-scrollbar-thumb {
            background: #888;
            border-radius: 4px;
        }

        .modal-scroll::-webkit-scrollbar-thumb:hover {
            background: #555;
        }

        .fixed {
            animation: fadeIn 0.3s ease-out;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
            }
            to {
                opacity: 1;
            }
        }

        @media (max-width: 768px) {
            #cardsContainer {
                grid-template-columns: 1fr;
            }
            
            .grid-cols-2 {
                grid-template-columns: 1fr;
                gap: 1rem;
            }
            
            #viewModal > div,
            #amountChangeModal > div,
            #transactionModal > div {
                width: 95%;
                margin: 0 10px;
            }
        }

        .bg-purple-100 {
            background-color: #f3e8ff;
        }

        .text-purple-700 {
            color: #7c3aed;
        }

        .history-item {
            border-left: 3px solid #3b82f6;
            padding-left: 1rem;
            margin-bottom: 1rem;
        }

        .history-item.receipt {
            border-left-color: #10b981;
        }

        .history-item.payment {
            border-left-color: #ef4444;
        }

        /* Readonly field styles */
        .readonly-field {
            background: linear-gradient(135deg, #f8fafc, #eef2f7);
            color: #475569;
            border: 1px solid #e2e8f0;
            cursor: default;
            box-shadow: inset 0 1px 2px rgba(0,0,0,0.04);
        }

        .editable-field {
            background-color: white;
            color: #374151;
        }

        .editable-field:focus {
            border-color: #3b82f6;
            ring-color: #3b82f6;
        }

        /* Disabled select styles */
        select:disabled {
            background-color: #f9fafb;
            color: #6b7280;
            cursor: not-allowed;
        }

        /* Transaction Details Modal */
        .transaction-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1rem;
            margin-top: 1rem;
        }

        .transaction-grid > div {
            background: #f8fafc;
            padding: 0.75rem;
            border-radius: 0.5rem;
            border: 1px solid #e2e8f0;
        }

        .transaction-grid label {
            font-size: 0.75rem;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            display: block;
            margin-bottom: 0.25rem;
        }

        .transaction-grid .value {
            font-weight: 600;
            color: #1e293b;
            font-size: 0.875rem;
        }

        .transaction-success {
            background: linear-gradient(135deg, #10b98115, #10b98105);
            border: 1px solid #10b98130;
            border-radius: 0.5rem;
            padding: 1rem;
            margin: 1rem 0;
        }

        .transaction-success .icon {
            background: #10b981;
            color: white;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 0.75rem;
        }

        .transaction-success h4 {
            color: #065f46;
            text-align: center;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }

        .transaction-success .details {
            background: white;
            padding: 1rem;
            border-radius: 0.375rem;
            margin-top: 0.75rem;
        }

        /* Status transition animation */
        .status-transition {
            animation: statusPulse 2s ease-in-out;
        }

        @keyframes statusPulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }

        /* Success badge */
        .success-badge {
            display: inline-flex;
            align-items: center;
            background: #10b981;
            color: white;
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 500;
            margin-left: 0.5rem;
        }

        /* Timeline for transaction process */
        .process-timeline {
            border-left: 2px solid #3b82f6;
            margin: 1rem 0 1rem 1rem;
            padding-left: 1.5rem;
        }

        .process-step {
            position: relative;
            margin-bottom: 1.5rem;
        }

        .process-step:before {
            content: '';
            position: absolute;
            left: -1.65rem;
            top: 0;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: #3b82f6;
            border: 2px solid white;
            box-shadow: 0 0 0 2px #3b82f6;
        }

        .process-step.completed:before {
            background: #10b981;
            box-shadow: 0 0 0 2px #10b981;
        }

        .process-step.failed:before {
            background: #ef4444;
            box-shadow: 0 0 0 2px #ef4444;
        }

        .process-step .step-title {
            font-weight: 600;
            color: #1e293b;
            font-size: 0.875rem;
        }

        .process-step .step-details {
            font-size: 0.75rem;
            color: #64748b;
            margin-top: 0.25rem;
        }

        /* Loader for processing */
        .processing-loader {
            text-align: center;
            padding: 2rem;
        }

        .processing-loader .spinner {
            border: 3px solid #f3f3f3;
            border-top: 3px solid #3b82f6;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
            margin: 0 auto 1rem;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        /* Toast Notification */
        .toast {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 1rem 1.5rem;
            border-radius: 0.5rem;
            display: flex;
            align-items: center;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            z-index: 1000;
            animation: slideIn 0.3s ease-out;
        }

        .toast.success {
            background: #10b981;
            color: white;
        }

        .toast.error {
            background: #ef4444;
            color: white;
        }

        .toast.warning {
            background: #f59e0b;
            color: white;
        }

        @keyframes slideIn {
            from {
                transform: translateX(100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }

        /* Loading overlay */
        .loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(255, 255, 255, 0.9);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            z-index: 2000;
        }

        .loading-overlay .spinner {
            border: 4px solid #f3f3f3;
            border-top: 4px solid #3b82f6;
            border-radius: 50%;
            width: 60px;
            height: 60px;
            animation: spin 1s linear infinite;
            margin-bottom: 1rem;
        }
    </style>
</head>
<body class="bg-gray-50 min-h-screen">
    <div class="container mx-auto px-4 py-8">
        <!-- Search & Filter Section -->
        <div class="flex justify-between items-center mb-6">
            <div>
                <h3 class="text-lg font-semibold text-gray-800">Instrument Tracking</h3>
                <p class="text-sm text-gray-600 mt-1">Track all instrument transactions</p>
            </div>
            <div class="flex space-x-3">
                <div class="relative">
                    <input type="text" id="searchInput" placeholder="Search instruments..." 
                           class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 w-64">
                    <i class="fas fa-search absolute right-3 top-3 text-gray-400"></i>
                </div>
                <button id="filterBtn" class="px-4 py-2 border border-gray-300 hover:bg-gray-50 text-gray-700 font-medium rounded-lg transition-colors flex items-center">
                    Filter <i class="fas fa-filter ml-2"></i>
                </button>
            </div>
        </div>

        <!-- Stats Cards -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
            <div class="bg-white rounded-lg border border-gray-200 p-4">
                <div class="flex items-center">
                    <div class="p-2 bg-blue-100 rounded-lg">
                        <i class="fas fa-receipt text-blue-600"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm text-gray-500">Total Instruments</p>
                        <h3 class="text-xl font-bold text-gray-800" id="totalInstruments">0</h3>
                    </div>
                </div>
            </div>
            <div class="bg-white rounded-lg border border-gray-200 p-4">
                <div class="flex items-center">
                    <div class="p-2 bg-green-100 rounded-lg">
                        <i class="fas fa-check-circle text-green-600"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm text-gray-500">Cleared</p>
                        <h3 class="text-xl font-bold text-gray-800" id="clearedInstruments">0</h3>
                    </div>
                </div>
            </div>
            <div class="bg-white rounded-lg border border-gray-200 p-4">
                <div class="flex items-center">
                    <div class="p-2 bg-yellow-100 rounded-lg">
                        <i class="fas fa-clock text-yellow-600"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm text-gray-500">Pending</p>
                        <h3 class="text-xl font-bold text-gray-800" id="pendingInstruments">0</h3>
                    </div>
                </div>
            </div>
            <div class="bg-white rounded-lg border border-gray-200 p-4">
                <div class="flex items-center">
                    <div class="p-2 bg-red-100 rounded-lg">
                        <i class="fas fa-times-circle text-red-600"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm text-gray-500">Failed/Bounced</p>
                        <h3 class="text-xl font-bold text-gray-800" id="failedInstruments">0</h3>
                    </div>
                </div>
            </div>
        </div>

        <!-- Loading Spinner -->
        <div id="loadingSpinner" class="hidden text-center py-8">
            <i class="fas fa-spinner fa-spin text-blue-500 text-2xl"></i>
            <p class="text-gray-600 mt-2">Loading instruments...</p>
        </div>

        <!-- Cards Container -->
        <div id="cardsContainer" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            <!-- Cards will be dynamically inserted here -->
        </div>

        <!-- Load More Button -->
        <div id="loadMoreContainer" class="mt-6 pt-6 border-t border-gray-200 text-center">
            <button id="loadMoreBtn" class="px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition-colors flex items-center mx-auto">
                Load More <i class="fas fa-arrow-down ml-2"></i>
            </button>
        </div>

        <!-- No Results Message -->
        <div id="noResults" class="hidden text-center py-12">
            <i class="fas fa-search text-gray-400 text-4xl mb-4"></i>
            <h3 class="text-lg font-semibold text-gray-700 mb-2">No instruments found</h3>
            <p class="text-gray-500">Try adjusting your search or filter criteria</p>
        </div>
    </div>

    <!-- View/Edit Modal -->
    <div id="viewModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-xl shadow-2xl w-full max-w-2xl max-h-[90vh] overflow-y-auto modal-scroll">
            <div class="p-6">
                <!-- Modal Header -->
                <div class="flex justify-between items-center mb-6 pb-4 border-b">
                    <h3 class="text-xl font-semibold text-gray-800">Instrument Details</h3>
                    <button id="closeModal" class="text-gray-400 hover:text-gray-600">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>
                
                <!-- Modal Content -->
                <div class="space-y-4">
                    <!-- Row 1 -->
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">System ID</label>
                            <input type="text" id="modalSysId" class="w-full px-3 py-2 border border-gray-300 rounded-lg readonly-field" readonly>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Instrument Type</label>
                            <select id="modalInstrumentType" class="w-full px-3 py-2 border border-gray-300 rounded-lg readonly-field" disabled>
                                <option value="CHEQUE">CHEQUE</option>
                                <option value="BFTN-EFT">BFTN-EFT</option>
                                <option value="RTGS">RTGS</option>
                                <option value="DD">DD</option>
                            </select>
                        </div>
                    </div>
                    
                    <!-- Row 2 -->
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Instrument No</label>
                            <input type="text" id="modalInstrumentNo" class="w-full px-3 py-2 border border-gray-300 rounded-lg readonly-field" readonly>
                        </div>
                        <div class="relative">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Amount</label>
                            <div class="flex items-center">
                                <input type="text" id="modalAmount" class="w-full px-3 py-2 border border-gray-300 rounded-lg readonly-field" readonly>
                                <button id="changeAmountBtn" class="ml-2 px-3 py-2 bg-blue-100 hover:bg-blue-200 text-blue-700 font-medium rounded-lg transition-colors text-sm">
                                    Change
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Row 3 -->
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Account Name</label>
                            <input type="text" id="modalAccountName" class="w-full px-3 py-2 border border-gray-300 rounded-lg readonly-field" readonly>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Bank Name</label>
                            <input type="text" id="modalBankName" class="w-full px-3 py-2 border border-gray-300 rounded-lg readonly-field" readonly>
                        </div>
                    </div>
                    
                    <!-- Row 4 -->
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Instrument Date</label>
                            <input type="date" id="modalInstrumentDate" class="w-full px-3 py-2 border border-gray-300 rounded-lg readonly-field" readonly>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Status <span class="text-red-500">*</span></label>
                            <select id="modalStatus" class="w-full px-3 py-2 border border-gray-300 rounded-lg editable-field">
                                <option value="pending">Pending</option>
                                <option value="cleared">Cleared</option>
                                <option value="bounced">Bounced</option>
                                <option value="failed">Failed</option>
                                <option value="cancelled">Cancelled</option>
                            </select>
                        </div>
                    </div>
                    
                    <!-- Row 5 -->
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Transaction Type</label>
                            <select id="modalTrnxType" class="w-full px-3 py-2 border border-gray-300 rounded-lg readonly-field" disabled>
                                <option value="debit">Debit</option>
                                <option value="credit">Credit</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Related Type</label>
                            <select id="modalRelatedType" class="w-full px-3 py-2 border border-gray-300 rounded-lg readonly-field" disabled>
                                <option value="a2a">A2A</option>
                                <option value="a2p">A2P</option>
                                <option value="received">RECEIVE</option>
                                <option value="payment">PAYMENT</option>
                            </select>
                        </div>
                    </div>
                    
                    <!-- Row 6 -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Remarks <span class="text-red-500">*</span></label>
                        <textarea id="modalRemarks" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-lg editable-field"></textarea>
                    </div>
                    
                    <!-- Related From/To -->
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Related From</label>
                            <input type="text" id="modalRelatedFrom" class="w-full px-3 py-2 border border-gray-300 rounded-lg readonly-field" readonly>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Related To</label>
                            <input type="text" id="modalRelatedTo" class="w-full px-3 py-2 border border-gray-300 rounded-lg readonly-field" readonly>
                        </div>
                    </div>
                    
                    <!-- Cleared Info (if cleared) -->
                    <div id="clearedInfoSection" class="hidden bg-green-50 border border-green-200 rounded-lg p-4 mt-4">
                        <h4 class="font-semibold text-green-800 mb-2 flex items-center">
                            <i class="fas fa-check-circle mr-2"></i> Cleared Information
                        </h4>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-medium text-green-700 mb-1">Cleared At</label>
                                <div id="clearedAt" class="text-sm text-green-800"></div>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-green-700 mb-1">Transaction ID</label>
                                <div id="clearedTransactionId" class="text-sm text-green-800"></div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Adjustment History -->
                    <div id="adjustmentHistorySection" class="hidden mt-6 pt-4 border-t">
                        <h4 class="text-lg font-semibold text-gray-800 mb-3">Amount Adjustment History</h4>
                        <div id="adjustmentHistoryList" class="space-y-3">
                            <!-- History items will be inserted here -->
                        </div>
                    </div>
                </div>
                
                <!-- Modal Footer -->
                <div class="mt-8 pt-6 border-t border-gray-200 flex justify-end space-x-3">
                    <button id="cancelBtn" class="px-5 py-2 border border-gray-300 hover:bg-gray-50 text-gray-700 font-medium rounded-lg transition-colors">
                        Cancel
                    </button>
                    <button id="saveBtn" class="px-5 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition-colors">
                        Save Changes
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Amount Change Modal -->
    <div id="amountChangeModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-xl shadow-2xl w-full max-w-md">
            <div class="p-6">
                <!-- Modal Header -->
                <div class="flex justify-between items-center mb-6 pb-4 border-b">
                    <h3 class="text-xl font-semibold text-gray-800">Update Amount</h3>
                    <button id="closeAmountModal" class="text-gray-400 hover:text-gray-600">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>
                
                <!-- Amount Change Form -->
                <div class="space-y-4">
                    <!-- Current Amount -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Current Amount</label>
                        <div class="flex items-center">
                            <span id="currentAmountDisplay" class="text-lg font-bold text-gray-800">৳ 0.00</span>
                            <span id="currentAmountType" class="ml-2 px-2 py-1 text-xs rounded-full"></span>
                        </div>
                    </div>
                    
                    <!-- Adjustment Type -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Adjustment Type</label>
                        <div class="grid grid-cols-2 gap-2">
                            <button type="button" id="gainBtn" class="px-4 py-2 bg-green-100 hover:bg-green-200 text-green-700 font-medium rounded-lg transition-colors border border-green-200">
                                <i class="fas fa-plus mr-2"></i> Gain/Add
                            </button>
                            <button type="button" id="paidBtn" class="px-4 py-2 bg-red-100 hover:bg-red-200 text-red-700 font-medium rounded-lg transition-colors border border-red-200">
                                <i class="fas fa-minus mr-2"></i> Paid/Deduct
                            </button>
                        </div>
                    </div>
                    
                    <!-- Adjustment Amount -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            <span id="adjustmentLabel">Adjustment Amount</span>
                        </label>
                        <div class="relative">
                            <span class="absolute left-3 top-3 text-gray-500">৳</span>
                            <input type="number" id="adjustmentAmount" 
                                   class="w-full pl-8 pr-4 py-2 border border-gray-300 rounded-lg editable-field focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                   placeholder="0.00"
                                   step="0.01"
                                   min="0">
                        </div>
                    </div>
                    
                    <!-- New Amount Preview -->
                    <div class="bg-gray-50 p-4 rounded-lg">
                        <div class="flex justify-between items-center">
                            <span class="text-sm font-medium text-gray-700">New Amount:</span>
                            <span id="newAmountPreview" class="text-lg font-bold text-blue-600">৳ 0.00</span>
                        </div>
                        <div id="amountChangeNote" class="text-xs text-gray-500 mt-1"></div>
                    </div>
                    
                    <!-- Reason for Change -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Reason for Change <span class="text-red-500">*</span></label>
                        <textarea id="amountChangeReason" rows="2" 
                                  class="w-full px-3 py-2 border border-gray-300 rounded-lg editable-field focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                  placeholder="Reason for amount adjustment..."></textarea>
                    </div>
                </div>
                
                <!-- Modal Footer -->
                <div class="mt-8 pt-6 border-t border-gray-200 flex justify-end space-x-3">
                    <button id="cancelAmountChange" class="px-5 py-2 border border-gray-300 hover:bg-gray-50 text-gray-700 font-medium rounded-lg transition-colors">
                        Cancel
                    </button>
                    <button id="saveAmountChange" class="px-5 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition-colors">
                        Update Amount
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Transaction Details Modal -->
    <div id="transactionModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-xl shadow-2xl w-full max-w-2xl max-h-[90vh] overflow-y-auto modal-scroll">
            <div class="p-6">
                <!-- Modal Header -->
                <div class="flex justify-between items-center mb-6 pb-4 border-b">
                    <h3 class="text-xl font-semibold text-gray-800">
                        <i class="fas fa-receipt mr-2 text-blue-600"></i>
                        Transaction Details
                    </h3>
                    <button id="closeTransactionModal" class="text-gray-400 hover:text-gray-600">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>
                
                <!-- Transaction Content -->
                <div id="transactionContent">
                    <!-- This will be populated dynamically -->
                </div>
                
                <!-- Modal Footer -->
                <div class="mt-8 pt-6 border-t border-gray-200 flex justify-end">
                    <button id="closeTransactionBtn" class="px-5 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition-colors">
                        Close
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Loading Overlay -->
    <div id="loadingOverlay" class="hidden loading-overlay">
        <div class="spinner"></div>
        <p class="text-gray-700 font-medium" id="loadingMessage">Processing...</p>
    </div>

    <!-- Toast Container -->
    <div id="toastContainer"></div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Global variables
        let currentPage = 1;
        let isLoading = false;
        let allData = [];
        let filteredData = [];
        const itemsPerPage = 6;
        
        // DOM Elements
        const cardsContainer = document.getElementById('cardsContainer');
        const loadingSpinner = document.getElementById('loadingSpinner');
        const loadMoreBtn = document.getElementById('loadMoreBtn');
        const searchInput = document.getElementById('searchInput');
        const noResults = document.getElementById('noResults');
        const loadingOverlay = document.getElementById('loadingOverlay');
        const loadingMessage = document.getElementById('loadingMessage');
        const toastContainer = document.getElementById('toastContainer');
        const filterBtn = document.getElementById('filterBtn');
        
        // Stats elements
        const totalInstruments = document.getElementById('totalInstruments');
        const clearedInstruments = document.getElementById('clearedInstruments');
        const pendingInstruments = document.getElementById('pendingInstruments');
        const failedInstruments = document.getElementById('failedInstruments');
        
        // Modals
        const viewModal = document.getElementById('viewModal');
        const amountChangeModal = document.getElementById('amountChangeModal');
        const transactionModal = document.getElementById('transactionModal');
        
        // View Modal Elements
        const closeModal = document.getElementById('closeModal');
        const cancelBtn = document.getElementById('cancelBtn');
        const saveBtn = document.getElementById('saveBtn');
        const changeAmountBtn = document.getElementById('changeAmountBtn');
        const adjustmentHistorySection = document.getElementById('adjustmentHistorySection');
        const adjustmentHistoryList = document.getElementById('adjustmentHistoryList');
        const clearedInfoSection = document.getElementById('clearedInfoSection');
        const clearedAt = document.getElementById('clearedAt');
        const clearedTransactionId = document.getElementById('clearedTransactionId');
        
        // Amount Change Modal Elements
        const closeAmountModal = document.getElementById('closeAmountModal');
        const cancelAmountChange = document.getElementById('cancelAmountChange');
        const saveAmountChange = document.getElementById('saveAmountChange');
        const gainBtn = document.getElementById('gainBtn');
        const paidBtn = document.getElementById('paidBtn');
        const adjustmentAmount = document.getElementById('adjustmentAmount');
        const currentAmountDisplay = document.getElementById('currentAmountDisplay');
        const newAmountPreview = document.getElementById('newAmountPreview');
        const amountChangeReason = document.getElementById('amountChangeReason');
        const amountChangeNote = document.getElementById('amountChangeNote');
        const adjustmentLabel = document.getElementById('adjustmentLabel');
        const currentAmountType = document.getElementById('currentAmountType');
        
        // Transaction Modal Elements
        const closeTransactionModal = document.getElementById('closeTransactionModal');
        const closeTransactionBtn = document.getElementById('closeTransactionBtn');
        const transactionContent = document.getElementById('transactionContent');
        
        // State variables for amount change
        let currentAmountChangeData = null;
        let adjustmentType = 'gain';
        
        // Initialize
        fetchData();
        
        // Event Listeners
        loadMoreBtn.addEventListener('click', loadMoreData);
        searchInput.addEventListener('input', handleSearch);
        filterBtn.addEventListener('click', showFilterOptions);
        
        // View Modal Events
        closeModal.addEventListener('click', () => closeModalFunc(viewModal));
        cancelBtn.addEventListener('click', () => closeModalFunc(viewModal));
        saveBtn.addEventListener('click', saveChanges);
        changeAmountBtn.addEventListener('click', openAmountChangeModal);
        
        // Amount Change Modal Events
        closeAmountModal.addEventListener('click', () => closeModalFunc(amountChangeModal));
        cancelAmountChange.addEventListener('click', () => closeModalFunc(amountChangeModal));
        saveAmountChange.addEventListener('click', saveAmountChangeHandler);
        gainBtn.addEventListener('click', () => setAdjustmentType('gain'));
        paidBtn.addEventListener('click', () => setAdjustmentType('paid'));
        adjustmentAmount.addEventListener('input', updateAmountPreview);
        
        // Transaction Modal Events
        closeTransactionModal.addEventListener('click', () => closeModalFunc(transactionModal));
        closeTransactionBtn.addEventListener('click', () => closeModalFunc(transactionModal));
        
        // Click outside modals to close
        viewModal.addEventListener('click', (e) => {
            if (e.target === viewModal) closeModalFunc(viewModal);
        });
        
        amountChangeModal.addEventListener('click', (e) => {
            if (e.target === amountChangeModal) closeModalFunc(amountChangeModal);
        });
        
        transactionModal.addEventListener('click', (e) => {
            if (e.target === transactionModal) closeModalFunc(transactionModal);
        });
        
        // Fetch data from API
        async function fetchData() {
            try {
                showLoading(true);
                const response = await fetch('https://travhub.com.bd/travhub-admin/api/acc-instrument-tracking/all-tracking.php');
                const data = await response.json();
                
                if (data.success && data.tracks) {
                    allData = data.tracks.map(item => ({
                        ...item,
                        original_amount: item.amount,
                        adjustment_history: item.history ? JSON.parse(item.history) : [],
                        cleared_at: item.cleared_at || null,
                        cleared_by: item.cleared_by || null,
                        cleared_transaction_id: item.cleared_transaction_id || null
                    }));
                    filteredData = [...allData];
                    updateStats();
                    renderCards();
                } else {
                    showToast('error', 'Failed to load instruments: ' + (data.message || 'Unknown error'));
                }
            } catch (error) {
                console.error('Error fetching data:', error);
                showToast('error', 'Network error. Please check your connection.');
                showError();
            } finally {
                showLoading(false);
            }
        }
        
        // Update statistics
        function updateStats() {
            const total = allData.length;
            const cleared = allData.filter(item => item.status === 'cleared').length;
            const pending = allData.filter(item => item.status === 'pending').length;
            const failed = allData.filter(item => item.status === 'bounced' || item.status === 'failed').length;
            
            totalInstruments.textContent = total;
            clearedInstruments.textContent = cleared;
            pendingInstruments.textContent = pending;
            failedInstruments.textContent = failed;
        }
        
        // Show filter options
        function showFilterOptions() {
            // Simple filter for now - can be extended with a modal
            const statuses = ['all', 'pending', 'cleared', 'bounced', 'failed', 'cancelled'];
            const status = prompt('Filter by status:\n\n' + statuses.map(s => s.charAt(0).toUpperCase() + s.slice(1)).join(', ') + '\n\nEnter status name:');
            
            if (status) {
                const normalizedStatus = status.toLowerCase();
                if (normalizedStatus === 'all') {
                    filteredData = [...allData];
                } else if (statuses.includes(normalizedStatus)) {
                    filteredData = allData.filter(item => item.status === normalizedStatus);
                } else {
                    showToast('warning', 'Invalid status filter');
                    return;
                }
                
                currentPage = 1;
                renderCards();
                showToast('success', `Filtered by: ${normalizedStatus}`);
            }
        }
        
        // Render cards
        function renderCards() {
            const startIndex = 0;
            const endIndex = currentPage * itemsPerPage;
            const dataToShow = filteredData.slice(startIndex, endIndex);
            
            if (dataToShow.length === 0) {
                cardsContainer.innerHTML = '';
                noResults.classList.remove('hidden');
                loadMoreBtn.style.display = 'none';
                return;
            }
            
            noResults.classList.add('hidden');
            cardsContainer.innerHTML = dataToShow.map(item => createCardHTML(item)).join('');
            
            if (endIndex >= filteredData.length) {
                loadMoreBtn.style.display = 'none';
            } else {
                loadMoreBtn.style.display = 'flex';
            }
        }
        
        // Create card HTML
        function createCardHTML(item) {
            const statusColor = getStatusColor(item.status);
            const amountColor = getAmountColor(item.trnx_type, item.amount);
            const amountSign = getAmountSign(item.trnx_type);
            const displayAmount = getDisplayAmount(item.trnx_type, item.amount);
            
            const hasAdjustment = item.adjustment_history && item.adjustment_history.length > 0;
            const adjustmentBadge = hasAdjustment ? 
                `<span class="ml-2 px-2 py-1 bg-purple-100 text-purple-700 text-xs rounded-full">Adjusted</span>` : '';
            
            const isCleared = item.status === 'cleared';
            const clearedBadge = isCleared ? 
                `<span class="ml-2 px-2 py-1 bg-green-100 text-green-700 text-xs rounded-full">Cleared</span>` : '';
            
            return `
                <div class="bg-white rounded-lg border border-gray-200 shadow-sm hover:shadow-md transition-shadow cursor-pointer view-btn-card" data-sys-id="${item.sys_id}">
                    <div class="p-4">
                        <!-- Card Header -->
                        <div class="flex justify-between items-start mb-3">
                            <div>
                                <h4 class="font-semibold text-gray-800">${item.sys_id}</h4>
                                <p class="text-sm text-gray-500">${item.instrument_type}</p>
                            </div>
                            <span class="px-3 py-1 ${statusColor.bg} ${statusColor.text} rounded-full text-xs font-medium">
                                ${item.status.charAt(0).toUpperCase() + item.status.slice(1)}
                            </span>
                        </div>
                        
                        <!-- Card Body -->
                        <div class="space-y-2 mb-4">
                            <div class="flex justify-between items-center">
                                <span class="text-gray-600">Amount:</span>
                                <div class="flex items-center">
                                    <span class="font-bold ${amountColor}">${displayAmount}</span>
                                    ${adjustmentBadge}
                                    ${clearedBadge}
                                </div>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600">Instrument No:</span>
                                <span class="font-medium">${item.instrument_no || 'N/A'}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600">Bank:</span>
                                <span class="font-medium">${item.bank_name}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600">Date:</span>
                                <span class="font-medium">${formatDate(item.instrument_date)}</span>
                            </div>
                        </div>
                        
                        <!-- Card Footer -->
                        <div class="flex justify-between items-center pt-3 border-t border-gray-100">
                            <div class="text-sm text-gray-500">
                                <i class="far fa-clock mr-1"></i>
                                Created: ${formatDateTime(item.created_at)}
                            </div>
                            <div class="flex space-x-2">
                                <button class="view-btn px-3 py-1 bg-blue-50 hover:bg-blue-100 text-blue-600 text-sm font-medium rounded-lg transition-colors" data-sys-id="${item.sys_id}">
                                    View
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            `;
        }
        
        // Get amount color based on transaction type
        function getAmountColor(trnxType, amount) {
            if (trnxType === 'debit') {
                return 'text-red-600';
            } else if (trnxType === 'credit') {
                return 'text-green-600';
            } else {
                const numAmount = parseFloat(amount);
                return numAmount >= 0 ? 'text-green-600' : 'text-red-600';
            }
        }
        
        // Get amount sign based on transaction type
        function getAmountSign(trnxType) {
            if (trnxType === 'debit') {
                return '-';
            } else if (trnxType === 'credit') {
                return '+';
            } else {
                return '';
            }
        }
        
        // Get display amount with sign
        function getDisplayAmount(trnxType, amount) {
            const sign = getAmountSign(trnxType);
            const numAmount = parseFloat(amount);
            return `${sign}৳ ${numAmount.toLocaleString()}`;
        }
        
        // Open view modal
        async function openViewModal(sysId) {
            const item = allData.find(item => item.sys_id == sysId);
            if (!item) {
                showToast('error', 'Instrument not found');
                return;
            }
            
            // Store current item sys_id in modal
            viewModal.dataset.currentSysId = sysId;
            
            // Fill modal with data - only 3 fields editable
            document.getElementById('modalSysId').value = item.sys_id;
            document.getElementById('modalInstrumentType').value = item.instrument_type;
            document.getElementById('modalInstrumentNo').value = item.instrument_no;
            document.getElementById('modalAmount').value = item.amount;
            document.getElementById('modalAccountName').value = item.account_name;
            document.getElementById('modalBankName').value = item.bank_name;
            document.getElementById('modalInstrumentDate').value = item.instrument_date;
            document.getElementById('modalStatus').value = item.status;
            document.getElementById('modalTrnxType').value = item.trnx_type || 'debit';
            document.getElementById('modalRelatedType').value = item.related_type;
            document.getElementById('modalRemarks').value = item.remarks || '';
            document.getElementById('modalRelatedFrom').value = item.related_from || 'N/A';
            document.getElementById('modalRelatedTo').value = item.related_to || 'N/A';
            
            // Show cleared info if cleared
            if (item.status === 'cleared' && item.cleared_at) {
                clearedInfoSection.classList.remove('hidden');
                clearedAt.textContent = formatDateTime(item.cleared_at);
                clearedTransactionId.textContent = item.cleared_transaction_id || 'N/A';
            } else {
                clearedInfoSection.classList.add('hidden');
            }
            
            // Load adjustment history
            await loadAdjustmentHistory(sysId);
            
            openModal(viewModal);
        }
        
        // Load adjustment history
        async function loadAdjustmentHistory(sysId) {
            try {
                const response = await fetch(`https://travhub.com.bd/travhub-admin/api/acc-instrument-tracking/get-history.php?sys_id=${sysId}`);
                const data = await response.json();
                
                if (data.success && data.history) {
                    const history = Array.isArray(data.history) ? data.history : JSON.parse(data.history);
                    
                    if (history.length > 0) {
                        adjustmentHistorySection.classList.remove('hidden');
                        adjustmentHistoryList.innerHTML = history.map(record => createHistoryHTML(record)).join('');
                    } else {
                        adjustmentHistorySection.classList.add('hidden');
                    }
                    
                    // Update local data
                    const index = allData.findIndex(item => item.sys_id == sysId);
                    if (index !== -1) {
                        allData[index].adjustment_history = history;
                    }
                } else {
                    adjustmentHistorySection.classList.add('hidden');
                }
            } catch (error) {
                console.error('Error loading history:', error);
                adjustmentHistorySection.classList.add('hidden');
            }
        }
        
        // Create history HTML
        function createHistoryHTML(record) {
            const isReceipt = record.type === 'gain';
            const typeClass = isReceipt ? 'history-item receipt' : 'history-item payment';
            const typeText = isReceipt ? 'Receipt' : 'Payment';
            const typeIcon = isReceipt ? 'fa-arrow-down' : 'fa-arrow-up';
            const typeColor = isReceipt ? 'text-green-600' : 'text-red-600';
            
            return `
                <div class="${typeClass}">
                    <div class="flex justify-between items-start">
                        <div>
                            <div class="flex items-center">
                                <i class="fas ${typeIcon} ${typeColor} mr-2"></i>
                                <span class="font-medium ${typeColor}">${typeText}</span>
                                <span class="ml-2 text-sm text-gray-600">${formatDateTime(record.date)}</span>
                            </div>
                            <p class="text-sm text-gray-700 mt-1">${record.reason}</p>
                            <p class="text-xs text-gray-500">By: ${record.adjusted_by || 'system'}</p>
                        </div>
                        <div class="text-right">
                            <div class="font-bold ${typeColor}">${getDisplayAmount(record.transaction_type, record.adjustment_amount)}</div>
                            <div class="text-xs text-gray-500">${record.effect || ''}</div>
                        </div>
                    </div>
                    <div class="text-xs text-gray-500 mt-1">
                        Previous: ৳${parseFloat(record.previous_amount).toLocaleString()} → 
                        New: ৳${parseFloat(record.new_amount).toLocaleString()}
                    </div>
                </div>
            `;
        }
        
        // Open amount change modal
        function openAmountChangeModal() {
            const sysId = viewModal.dataset.currentSysId;
            const item = allData.find(item => item.sys_id == sysId);
            if (!item) {
                showToast('error', 'Instrument not found');
                return;
            }
            
            currentAmountChangeData = {
                sysId: sysId,
                currentAmount: parseFloat(item.amount),
                originalAmount: parseFloat(item.original_amount),
                trnxType: item.trnx_type || 'debit'
            };
            
            const displayAmount = getDisplayAmount(item.trnx_type, item.amount);
            currentAmountDisplay.textContent = displayAmount;
            
            const typeText = item.trnx_type === 'debit' ? 'Debit (Money Out)' : 'Credit (Money In)';
            const typeColor = item.trnx_type === 'debit' ? 'bg-red-100 text-red-700' : 'bg-green-100 text-green-700';
            currentAmountType.textContent = typeText;
            currentAmountType.className = `ml-2 px-2 py-1 text-xs rounded-full ${typeColor}`;
            
            adjustmentAmount.value = '';
            amountChangeReason.value = '';
            setAdjustmentType('gain');
            updateUIForTransactionType(item.trnx_type);
            
            closeModalFunc(viewModal);
            openModal(amountChangeModal);
        }
        
        // Update UI labels based on transaction type
        function updateUIForTransactionType(trnxType) {
            if (trnxType === 'debit') {
                gainBtn.innerHTML = '<i class="fas fa-arrow-down mr-2"></i> Receive Back';
                paidBtn.innerHTML = '<i class="fas fa-arrow-up mr-2"></i> Pay More';
            } else {
                gainBtn.innerHTML = '<i class="fas fa-arrow-up mr-2"></i> Receive More';
                paidBtn.innerHTML = '<i class="fas fa-arrow-down mr-2"></i> Return Some';
            }
        }
        
        // Set adjustment type
        function setAdjustmentType(type) {
            adjustmentType = type;
            
            if (type === 'gain') {
                gainBtn.classList.add('bg-green-200', 'border-green-300');
                gainBtn.classList.remove('bg-green-100');
                paidBtn.classList.add('bg-red-100');
                paidBtn.classList.remove('bg-red-200', 'border-red-300');
                
                if (currentAmountChangeData.trnxType === 'debit') {
                    adjustmentLabel.textContent = 'Amount to Receive Back';
                } else {
                    adjustmentLabel.textContent = 'Additional Amount to Receive';
                }
            } else {
                paidBtn.classList.add('bg-red-200', 'border-red-300');
                paidBtn.classList.remove('bg-red-100');
                gainBtn.classList.add('bg-green-100');
                gainBtn.classList.remove('bg-green-200', 'border-green-300');
                
                if (currentAmountChangeData.trnxType === 'debit') {
                    adjustmentLabel.textContent = 'Additional Amount to Pay';
                } else {
                    adjustmentLabel.textContent = 'Amount to Return';
                }
            }
            
            updateAmountPreview();
        }
        
        // Update amount preview with correct logic
        function updateAmountPreview() {
            if (!currentAmountChangeData) return;
            
            const adjustment = parseFloat(adjustmentAmount.value) || 0;
            const trnxType = currentAmountChangeData.trnxType;
            let newAmount = currentAmountChangeData.currentAmount;
            let note = '';
            
            if (trnxType === 'debit') {
                if (adjustmentType === 'gain') {
                    newAmount -= adjustment;
                    note = `Receiving back ৳${adjustment.toLocaleString()} reduces the debit`;
                } else {
                    newAmount += adjustment;
                    note = `Paying additional ৳${adjustment.toLocaleString()} increases the debit`;
                }
            } else {
                if (adjustmentType === 'gain') {
                    newAmount += adjustment;
                    note = `Receiving additional ৳${adjustment.toLocaleString()} increases the credit`;
                } else {
                    newAmount -= adjustment;
                    note = `Returning ৳${adjustment.toLocaleString()} reduces the credit`;
                }
            }
            
            if (newAmount < 0 && trnxType === 'credit') {
                newAmount = 0;
                note += ' (Minimum credit is 0)';
            }
            
            const displayAmount = getDisplayAmount(trnxType, newAmount);
            newAmountPreview.textContent = displayAmount;
            amountChangeNote.textContent = note;
            
            const currentDisplay = getDisplayAmount(trnxType, currentAmountChangeData.currentAmount);
            if (trnxType === 'debit') {
                if (newAmount < currentAmountChangeData.currentAmount) {
                    newAmountPreview.classList.remove('text-red-600');
                    newAmountPreview.classList.add('text-green-600');
                } else if (newAmount > currentAmountChangeData.currentAmount) {
                    newAmountPreview.classList.remove('text-green-600');
                    newAmountPreview.classList.add('text-red-600');
                } else {
                    newAmountPreview.classList.remove('text-green-600', 'text-red-600');
                    newAmountPreview.classList.add('text-blue-600');
                }
            } else {
                if (newAmount > currentAmountChangeData.currentAmount) {
                    newAmountPreview.classList.remove('text-green-600');
                    newAmountPreview.classList.add('text-green-600');
                } else if (newAmount < currentAmountChangeData.currentAmount) {
                    newAmountPreview.classList.remove('text-green-600');
                    newAmountPreview.classList.add('text-red-600');
                } else {
                    newAmountPreview.classList.remove('text-green-600', 'text-red-600');
                    newAmountPreview.classList.add('text-blue-600');
                }
            }
        }
        
        // Save amount changes with proper logic
        async function saveAmountChangeHandler() {
            const adjustment = parseFloat(adjustmentAmount.value);
            const reason = amountChangeReason.value.trim();
            
            if (!adjustment || adjustment <= 0) {
                showToast('warning', 'Please enter a valid adjustment amount');
                return;
            }
            
            if (!reason) {
                showToast('warning', 'Please provide a reason for the amount change');
                return;
            }
            
            const sysId = currentAmountChangeData.sysId;
            const index = allData.findIndex(item => item.sys_id == sysId);
            
            if (index === -1) {
                showToast('error', 'Item not found');
                return;
            }
            
            showLoadingOverlay('Updating amount...');
            
            try {
                const trnxType = currentAmountChangeData.trnxType;
                let newAmount = currentAmountChangeData.currentAmount;
                
                if (trnxType === 'debit') {
                    if (adjustmentType === 'gain') {
                        newAmount -= adjustment;
                    } else {
                        newAmount += adjustment;
                    }
                } else {
                    if (adjustmentType === 'gain') {
                        newAmount += adjustment;
                    } else {
                        newAmount -= adjustment;
                        if (newAmount < 0) {
                            showToast('warning', 'Credit amount cannot be negative');
                            hideLoadingOverlay();
                            return;
                        }
                    }
                }
                
                const adjustmentRecord = {
                    date: new Date().toISOString(),
                    type: adjustmentType,
                    transaction_type: trnxType,
                    adjustment_amount: adjustment,
                    reason: reason,
                    previous_amount: currentAmountChangeData.currentAmount,
                    new_amount: newAmount,
                    effect: adjustmentType === 'gain' ? 
                        (trnxType === 'debit' ? 'Debit Reduced' : 'Credit Increased') :
                        (trnxType === 'debit' ? 'Debit Increased' : 'Credit Reduced')
                };
                
                // Send to server
                const adjustmentResponse = await fetch('https://travhub.com.bd/travhub-admin/api/acc-instrument-tracking/adjust-amount.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        sys_id: sysId,
                        new_amount: newAmount,
                        adjustment_type: adjustmentType,
                        trnx_type: trnxType,
                        adjustment_amount: adjustment,
                        reason: reason,
                        adjustment_record: adjustmentRecord
                    })
                });
                
                const adjustmentResult = await adjustmentResponse.json();
                
                if (!adjustmentResult.success) {
                    throw new Error(adjustmentResult.message || 'Failed to update amount');
                }
                
                // Update the item locally
                allData[index].amount = newAmount.toFixed(2);
                
                if (!allData[index].adjustment_history) {
                    allData[index].adjustment_history = [];
                }
                allData[index].adjustment_history.push(adjustmentRecord);
                
                // Update filtered data
                const filteredIndex = filteredData.findIndex(item => item.sys_id == sysId);
                if (filteredIndex !== -1) {
                    filteredData[filteredIndex] = { ...allData[index] };
                }
                
                const message = adjustmentType === 'gain' ? 
                    (trnxType === 'debit' ? 'Amount received back successfully!' : 'Additional amount received successfully!') :
                    (trnxType === 'debit' ? 'Additional payment recorded successfully!' : 'Amount returned successfully!');
                
                showToast('success', message);
                
                closeModalFunc(amountChangeModal);
                hideLoadingOverlay();
                renderCards();
                updateStats();
                
                setTimeout(() => openViewModal(sysId), 300);
                
            } catch (error) {
                console.error('Error updating amount:', error);
                showToast('error', 'Failed to update amount. Please try again.');
                hideLoadingOverlay();
            }
        }
        
        // Save changes (only status and remarks)
        async function saveChanges() {
            const sysId = viewModal.dataset.currentSysId;
            if (!sysId) return;
            
            const oldItem = allData.find(item => item.sys_id == sysId);
            const oldStatus = oldItem.status;
            const newStatus = document.getElementById('modalStatus').value;
            const remarks = document.getElementById('modalRemarks').value;
            
            // If status is changing to "cleared", directly go to process-cleared.php
            if (oldStatus !== 'cleared' && newStatus === 'cleared') {
                if (!confirm('Are you sure you want to mark this instrument as CLEARED?\n\n' +
                            'This will process financial transactions and:\n' +
                            '1. Check account balances\n' +
                            '2. Update bank statements\n' +
                            '3. Update financial entries\n\n' +
                            'Click OK to proceed or Cancel to abort.')) {
                    return;
                }
                
                // Directly process cleared status
                await processClearedStatus(sysId, oldItem, remarks);
                return;
            }
            
            // For other status changes (bounced, failed, cancelled, pending)
            await updateStatusAndRemarks(sysId, newStatus, remarks, oldItem);
        }
        
        // Process cleared status
        async function processClearedStatus(sysId, item, remarks) {
            // Show transaction processing
            showTransactionProcessing(item);
            
            try {
                // Directly call process-cleared.php
                const clearedResponse = await fetch('https://travhub.com.bd/travhub-admin/api/acc-instrument-tracking/process-cleared.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        sys_id: sysId,
                        amount: parseFloat(item.amount),
                        trnx_type: item.trnx_type,
                        related_type: item.related_type,
                        remarks: remarks,
                        instrument_type: item.instrument_type,
                        account_name: item.account_name,
                        bank_name: item.bank_name,
                        instrument_no: item.instrument_no
                    })
                });
                
                const clearedResult = await clearedResponse.json();
                
                if (!clearedResult.success) {
                    // Transaction failed, show error but don't revert (status remains pending)
                    showTransactionFailed(item, clearedResult.message);
                    
                    // Update remarks with failure reason
                    await updateStatusAndRemarks(
                        sysId, 
                        'pending', 
                        remarks + ' [Cleared failed: ' + clearedResult.message + ']',
                        item
                    );
                } else {
                    // Show transaction success with details
                    showTransactionSuccess(item, clearedResult);
                    
                    // Update local data
                    const index = allData.findIndex(item => item.sys_id == sysId);
                    if (index !== -1) {
                        allData[index].status = 'cleared';
                        allData[index].remarks = remarks;
                        allData[index].cleared_at = new Date().toISOString();
                        allData[index].cleared_by = 'system';
                        allData[index].cleared_transaction_id = clearedResult.transaction_id;
                        allData[index].cleared_details = clearedResult;
                        
                        const filteredIndex = filteredData.findIndex(item => item.sys_id == sysId);
                        if (filteredIndex !== -1) {
                            filteredData[filteredIndex] = { ...allData[index] };
                        }
                        
                        renderCards();
                        updateStats();
                        closeModalFunc(viewModal);
                    }
                }
                
            } catch (error) {
                console.error('Error processing cleared status:', error);
                showTransactionError(item, error.message);
            }
        }
        
        // Update status and remarks (for non-cleared status changes)
        async function updateStatusAndRemarks(sysId, status, remarks, oldItem) {
            showLoadingOverlay('Saving changes...');
            
            try {
                const updateResponse = await fetch('https://travhub.com.bd/travhub-admin/api/acc-instrument-tracking/update.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        sys_id: sysId,
                        status: status,
                        remarks: remarks
                    })
                });
                
                const updateResult = await updateResponse.json();
                
                if (!updateResult.success) {
                    throw new Error(updateResult.message || 'Failed to save changes');
                }
                
                showToast('success', 'Changes saved successfully!');
                
                // Update local data
                const index = allData.findIndex(item => item.sys_id == sysId);
                if (index !== -1) {
                    allData[index].status = status;
                    allData[index].remarks = remarks;
                    
                    // If changing from cleared to another status, clear cleared info
                    if (oldItem.status === 'cleared' && status !== 'cleared') {
                        allData[index].cleared_at = null;
                        allData[index].cleared_by = null;
                        allData[index].cleared_transaction_id = null;
                        allData[index].cleared_details = null;
                    }
                    
                    const filteredIndex = filteredData.findIndex(item => item.sys_id == sysId);
                    if (filteredIndex !== -1) {
                        filteredData[filteredIndex] = { ...allData[index] };
                    }
                    
                    renderCards();
                    updateStats();
                    closeModalFunc(viewModal);
                }
                
            } catch (error) {
                console.error('Error updating status:', error);
                showToast('error', 'Failed to save changes. Please try again.');
            } finally {
                hideLoadingOverlay();
            }
        }
        
        // Show transaction processing loader
        function showTransactionProcessing(item) {
            closeModalFunc(viewModal);
            
            transactionContent.innerHTML = `
                <div class="processing-loader">
                    <div class="spinner"></div>
                    <h3 class="text-lg font-semibold text-gray-800 mb-2">Processing Transaction</h3>
                    <p class="text-gray-600 mb-4">Please wait while we process your instrument clearance...</p>
                    
                    <div class="process-timeline">
                        <div class="process-step completed">
                            <div class="step-title">Step 1: Validation Check</div>
                            <div class="step-details">Checking instrument details and validity</div>
                        </div>
                        <div class="process-step">
                            <div class="step-title">Step 2: Balance Verification</div>
                            <div class="step-details">Verifying account balances</div>
                        </div>
                        <div class="process-step">
                            <div class="step-title">Step 3: Transaction Processing</div>
                            <div class="step-details">Processing financial entries</div>
                        </div>
                        <div class="process-step">
                            <div class="step-title">Step 4: Confirmation</div>
                            <div class="step-details">Finalizing transaction</div>
                        </div>
                    </div>
                    
                    <div class="mt-6 p-4 bg-blue-50 rounded-lg">
                        <div class="flex justify-between items-center">
                            <div>
                                <p class="font-medium text-gray-700">${item.sys_id}</p>
                                <p class="text-sm text-gray-600">${item.instrument_type} • ${item.bank_name}</p>
                            </div>
                            <div class="text-right">
                                <p class="font-bold ${getAmountColor(item.trnx_type, item.amount)}">
                                    ${getDisplayAmount(item.trnx_type, item.amount)}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            
            openModal(transactionModal);
        }
        
        // Show transaction success with details
        function showTransactionSuccess(item, transactionData) {
            const timestamp = new Date().toLocaleString('en-GB', {
                day: '2-digit',
                month: 'short',
                year: 'numeric',
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit'
            });
            
            let transactionDetails = '';
            
            if (transactionData.transaction_details) {
                const details = transactionData.transaction_details;
                transactionDetails = `
                    <div class="transaction-grid">
                        <div>
                            <label>Transaction ID</label>
                            <div class="value">${details.transaction_id || 'N/A'}</div>
                        </div>
                        <div>
                            <label>Reference No</label>
                            <div class="value">${details.reference_no || 'N/A'}</div>
                        </div>
                        <div>
                            <label>From Account</label>
                            <div class="value">${details.from_account || 'N/A'}</div>
                        </div>
                        <div>
                            <label>To Account</label>
                            <div class="value">${details.to_account || 'N/A'}</div>
                        </div>
                        <div>
                            <label>Debit Amount</label>
                            <div class="value">৳ ${parseFloat(details.debit_amount || 0).toLocaleString()}</div>
                        </div>
                        <div>
                            <label>Credit Amount</label>
                            <div class="value">৳ ${parseFloat(details.credit_amount || 0).toLocaleString()}</div>
                        </div>
                    </div>
                `;
            }
            
            transactionContent.innerHTML = `
                <div class="transaction-success">
                    <div class="icon">
                        <i class="fas fa-check text-xl"></i>
                    </div>
                    <h4>Instrument Cleared Successfully!</h4>
                    <p class="text-center text-gray-600 mb-4">Transaction completed at ${timestamp}</p>
                    
                    <div class="details">
                        <div class="flex justify-between items-start mb-4">
                            <div>
                                <h5 class="font-semibold text-gray-800">${item.sys_id}</h5>
                                <p class="text-sm text-gray-600">${item.instrument_type} • ${item.bank_name}</p>
                                <p class="text-sm text-gray-600 mt-1">${item.account_name}</p>
                            </div>
                            <div class="text-right">
                                <div class="font-bold text-lg ${getAmountColor(item.trnx_type, item.amount)}">
                                    ${getDisplayAmount(item.trnx_type, item.amount)}
                                </div>
                                <div class="success-badge">
                                    <i class="fas fa-check mr-1"></i> CLEARED
                                </div>
                            </div>
                        </div>
                        
                        ${transactionDetails}
                        
                        <div class="mt-4 pt-4 border-t border-gray-100">
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-600">Transaction ID:</span>
                                <span class="font-medium">${transactionData.transaction_id || 'N/A'}</span>
                            </div>
                            <div class="flex justify-between text-sm mt-2">
                                <span class="text-gray-600">Batch No:</span>
                                <span class="font-medium">${transactionData.batch_no || 'N/A'}</span>
                            </div>
                            <div class="flex justify-between text-sm mt-2">
                                <span class="text-gray-600">Status Message:</span>
                                <span class="font-medium text-green-600">${transactionData.message || 'Success'}</span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="mt-6">
                    <h5 class="font-semibold text-gray-800 mb-3">Transaction Summary</h5>
                    <div class="space-y-2">
                        <div class="flex justify-between">
                            <span class="text-gray-600">Instrument No:</span>
                            <span class="font-medium">${item.instrument_no || 'N/A'}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">Transaction Type:</span>
                            <span class="font-medium">${item.trnx_type.toUpperCase()}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">Related Type:</span>
                            <span class="font-medium">${item.related_type.toUpperCase()}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">Processed By:</span>
                            <span class="font-medium">System (Auto)</span>
                        </div>
                    </div>
                </div>
            `;
        }
        
        // Show transaction failure
        function showTransactionFailed(item, errorMessage) {
            transactionContent.innerHTML = `
                <div class="text-center py-8">
                    <div class="w-16 h-16 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-times text-2xl text-red-600"></i>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-800 mb-2">Transaction Failed</h3>
                    <p class="text-gray-600 mb-4">${errorMessage || 'Unable to process the transaction'}</p>
                    
                    <div class="bg-red-50 border border-red-200 rounded-lg p-4 mb-6">
                        <div class="flex justify-between items-start">
                            <div>
                                <h5 class="font-semibold text-gray-800">${item.sys_id}</h5>
                                <p class="text-sm text-gray-600">${item.instrument_type} • ${item.bank_name}</p>
                            </div>
                            <div class="text-right">
                                <div class="font-bold ${getAmountColor(item.trnx_type, item.amount)}">
                                    ${getDisplayAmount(item.trnx_type, item.amount)}
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <p class="text-sm text-gray-500">
                        <i class="fas fa-info-circle mr-1"></i>
                        Instrument status remains as <span class="font-medium">PENDING</span>. 
                        Please check the details and try again.
                    </p>
                </div>
            `;
        }
        
        // Show transaction error
        function showTransactionError(item, errorMessage) {
            transactionContent.innerHTML = `
                <div class="text-center py-8">
                    <div class="w-16 h-16 bg-yellow-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-exclamation-triangle text-2xl text-yellow-600"></i>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-800 mb-2">Processing Error</h3>
                    <p class="text-gray-600 mb-4">${errorMessage || 'An unexpected error occurred'}</p>
                    
                    <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                        <h5 class="font-medium text-yellow-800 mb-2">
                            <i class="fas fa-lightbulb mr-1"></i> Suggested Actions:
                        </h5>
                        <ul class="text-sm text-left text-gray-600 space-y-1">
                            <li>• Check your internet connection</li>
                            <li>• Verify the instrument details</li>
                            <li>• Contact support if the issue persists</li>
                            <li>• Try again in a few moments</li>
                        </ul>
                    </div>
                </div>
            `;
        }
        
        // Helper functions
        function openModal(modal) {
            modal.classList.remove('hidden');
            document.body.style.overflow = 'hidden';
        }
        
        function closeModalFunc(modal) {
            modal.classList.add('hidden');
            document.body.style.overflow = 'auto';
        }
        
        function showLoading(show) {
            if (show) {
                loadingSpinner.classList.remove('hidden');
                cardsContainer.innerHTML = '';
                noResults.classList.add('hidden');
                loadMoreBtn.style.display = 'none';
            } else {
                loadingSpinner.classList.add('hidden');
            }
            isLoading = show;
        }
        
        function showLoadingOverlay(message = 'Processing...') {
            loadingMessage.textContent = message;
            loadingOverlay.classList.remove('hidden');
        }
        
        function hideLoadingOverlay() {
            loadingOverlay.classList.add('hidden');
        }
        
        function showError() {
            cardsContainer.innerHTML = `
                <div class="col-span-full text-center py-8">
                    <i class="fas fa-exclamation-triangle text-red-500 text-3xl"></i>
                    <p class="text-gray-600 mt-2">Failed to load data. Please try again.</p>
                </div>
            `;
        }
        
        function showToast(type, message) {
            const toast = document.createElement('div');
            toast.className = `toast ${type}`;
            toast.innerHTML = `
                <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-circle' : 'exclamation-triangle'} mr-2"></i>
                <span>${message}</span>
            `;
            
            toastContainer.appendChild(toast);
            
            // Auto remove after 5 seconds
            setTimeout(() => {
                toast.style.animation = 'slideIn 0.3s ease-out reverse';
                setTimeout(() => {
                    toast.remove();
                }, 300);
            }, 5000);
        }
        
        function getStatusColor(status) {
            const colors = {
                'pending': { bg: 'bg-yellow-100', text: 'text-yellow-800' },
                'cleared': { bg: 'bg-green-100', text: 'text-green-800' },
                'bounced': { bg: 'bg-red-100', text: 'text-red-800' },
                'failed': { bg: 'bg-red-100', text: 'text-red-800' },
                'cancelled': { bg: 'bg-gray-100', text: 'text-gray-800' }
            };
            return colors[status] || colors.pending;
        }
        
        function formatDate(dateString) {
            if (!dateString) return 'N/A';
            const date = new Date(dateString);
            return date.toLocaleDateString('en-GB');
        }
        
        function formatDateTime(dateTimeString) {
            if (!dateTimeString) return 'N/A';
            const date = new Date(dateTimeString);
            return date.toLocaleDateString('en-GB') + ' ' + date.toLocaleTimeString('en-GB', { hour: '2-digit', minute: '2-digit' });
        }
        
        function loadMoreData() {
            currentPage++;
            renderCards();
        }
        
        function handleSearch() {
            const searchTerm = searchInput.value.toLowerCase();
            
            if (searchTerm === '') {
                filteredData = [...allData];
            } else {
                filteredData = allData.filter(item => 
                    item.sys_id.toLowerCase().includes(searchTerm) ||
                    (item.instrument_no && item.instrument_no.toLowerCase().includes(searchTerm)) ||
                    (item.account_name && item.account_name.toLowerCase().includes(searchTerm)) ||
                    (item.bank_name && item.bank_name.toLowerCase().includes(searchTerm)) ||
                    (item.remarks && item.remarks.toLowerCase().includes(searchTerm))
                );
            }
            
            currentPage = 1;
            renderCards();
        }
        
        // Event delegation for dynamic buttons
        cardsContainer.addEventListener('click', function(e) {
            // Handle click on the entire card
            if (e.target.closest('.view-btn-card')) {
                const card = e.target.closest('.view-btn-card');
                const sysId = card.dataset.sysId;
                openViewModal(sysId);
            }
            
            // Handle click on the view button
            if (e.target.classList.contains('view-btn') || e.target.closest('.view-btn')) {
                const btn = e.target.classList.contains('view-btn') ? e.target : e.target.closest('.view-btn');
                const sysId = btn.dataset.sysId;
                openViewModal(sysId);
            }
        });
    });
    </script>
</body>
</html>