<!--
    PATH: /vendors/sv-accounting.php
    
    Vendor Accounting — 4 transaction types:
    🟢 Purchase  (vendor+credit, related_type=2) — no bank touch
    🔴 Payment   (vendor+debit,  related_type=4) — bank withdraw
    🟣 Refund    (vendor+debit,  related_type=0) — conditional bank credit
    🟠 Discount  (vendor+debit,  related_type=5) — no bank touch
    
    Cross/Cancel button fix:
      - HTML inline onclick বাদ দেওয়া হয়েছে
      - JS থেকে addEventListener দিয়ে bind করা হয়েছে
      - IIFE wrap করা আছে
-->

<!-- ==================== SUMMARY CARDS ==================== -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4 m-8">
    <div class="group bg-gradient-to-br from-purple-50 to-purple-100 border border-purple-200 hover:border-purple-400 hover:from-purple-100 hover:to-purple-200 p-4 rounded-xl text-center transition-all duration-300 hover:shadow-lg hover:-translate-y-1">
        <p id="sv-total-trnx" class="font-semibold text-purple-800">--</p>
        <p class="text-xs text-purple-600 mt-1">Total Trnx</p>
    </div>
    <div class="group bg-gradient-to-br from-green-50 to-green-100 border border-green-200 hover:border-green-400 hover:from-green-100 hover:to-green-200 p-4 rounded-xl text-center transition-all duration-300 hover:shadow-lg hover:-translate-y-1">
        <p id="sv-total-credit" class="font-semibold text-green-800">--</p>
        <p class="text-xs text-green-600 mt-1">Total Credit (Purchase)</p>
    </div>
    <div class="group bg-gradient-to-br from-red-50 to-red-100 border border-red-200 hover:border-red-400 hover:from-red-100 hover:to-red-200 p-4 rounded-xl text-center transition-all duration-300 hover:shadow-lg hover:-translate-y-1">
        <p id="sv-total-debit" class="font-semibold text-red-800">--</p>
        <p class="text-xs text-red-600 mt-1">Total Debit (Payment)</p>
    </div>
    <div class="group bg-gradient-to-br from-yellow-50 to-yellow-100 border border-yellow-200 hover:border-yellow-400 hover:from-yellow-100 hover:to-yellow-200 p-4 rounded-xl text-center transition-all duration-300 hover:shadow-lg hover:-translate-y-1">
        <p id="sv-total-outstanding" class="font-semibold text-yellow-800">--</p>
        <p class="text-xs text-yellow-600 mt-1">Total Outstanding</p>
    </div>
    <div class="group bg-gradient-to-br from-blue-50 to-blue-100 border border-blue-200 hover:border-blue-400 hover:from-blue-100 hover:to-blue-200 p-4 rounded-xl text-center transition-all duration-300 hover:shadow-lg hover:-translate-y-1 cursor-pointer"
         id="sv-addTrnxCard">
        <p class="font-semibold text-blue-800">+ Add New Trnx</p>
    </div>
</div>

<!-- ==================== FLOATING ACTION BUTTONS ==================== -->
<div class="fixed bottom-4 right-4 flex flex-col items-end gap-2 z-40">
    <!-- Discount -->
    <div>
        <button id="sv-btn-discount" title="Give Discount from Vendor"
                class="bg-orange-500 text-white p-3 rounded-full shadow-lg hover:bg-orange-600 transition-colors">
            <i class="fas fa-tag text-xl"></i>
        </button>
    </div>
    <!-- Refund -->
    <div>
        <button id="sv-btn-refund" title="Receive Refund from Vendor"
                class="bg-purple-600 text-white p-3 rounded-full shadow-lg hover:bg-purple-700 transition-colors">
            <i class="fas fa-undo-alt text-xl"></i>
        </button>
    </div>
    <!-- Payment -->
    <div>
        <button id="sv-btn-payment" title="Make Payment to Vendor"
                class="bg-red-600 text-white p-3 rounded-full shadow-lg hover:bg-red-700 transition-colors">
            <i class="fas fa-minus-circle text-xl"></i>
        </button>
    </div>
    <!-- Purchase -->
    <div>
        <button id="sv-btn-purchase" title="Purchase / Receive Service from Vendor"
                class="bg-green-600 text-white p-3 rounded-full shadow-lg hover:bg-green-700 transition-colors">
            <i class="fas fa-plus-circle text-xl"></i>
        </button>
    </div>
</div>

<!-- ==================== TRANSACTION TABLE ==================== -->
<div class="bg-white rounded-lg shadow p-4 flex flex-col">
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-4 gap-3">
        <h2 class="text-2xl font-semibold text-gray-800">Vendor Transactions</h2>
        <div class="flex flex-col sm:flex-row gap-3 w-full md:w-auto">
            <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <i class="fas fa-search text-gray-400"></i>
                </div>
                <input type="text" id="sv-searchInput"
                    class="pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 w-full"
                    placeholder="Search vendor transactions...">
            </div>
            <div class="relative">
                <select id="sv-filterType"
                    class="appearance-none pl-4 pr-10 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 w-full">
                    <option value="all">All Types</option>
                    <option value="credit">Credit (Purchase)</option>
                    <option value="debit">Debit (Payment/Refund/Discount)</option>
                </select>
                <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                    <i class="fas fa-chevron-down text-gray-400"></i>
                </div>
            </div>
            <button id="sv-resetFilters"
                class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors whitespace-nowrap">
                <i class="fas fa-redo mr-2"></i>Reset
            </button>
        </div>
    </div>

    <div class="overflow-x-auto table-container">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-64">Purpose</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Work</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-green-600 uppercase tracking-wider">Credit (Purchase)</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-red-600 uppercase tracking-wider">Debit (Payment)</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Outstanding</th>
                </tr>
            </thead>
            <tbody id="sv-finTableBody" class="bg-white divide-y divide-gray-200 text-left"></tbody>
            <tfoot class="bg-gray-50 font-semibold">
                <tr>
                    <td colspan="5" class="px-6 py-4 text-right text-sm text-gray-700">Total:</td>
                    <td id="sv-total-credit" class="px-6 py-4 text-sm text-right text-green-700 font-bold">0.00</td>
                    <td id="sv-total-debit" class="px-6 py-4 text-sm text-right text-red-700 font-bold">0.00</td>
                    <td id="sv-final-outstanding" class="px-6 py-4 text-sm text-right text-orange-700 font-bold">0.00</td>
                </tr>
            </tfoot>
        </table>
        <div id="sv-noResultsMessage" class="hidden px-6 py-10 text-center text-gray-500">
            <div class="flex flex-col items-center gap-2">
                <i class="fas fa-search text-3xl text-gray-400"></i>
                <p class="text-sm">No vendor transactions match your criteria</p>
            </div>
        </div>
        <div id="sv-loadMoreContainer" class="hidden mt-4 text-center">
            <button id="sv-loadMoreBtn" class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                <i class="fas fa-spinner fa-spin hidden mr-2" id="sv-loadMoreSpinner"></i>
                Load More
            </button>
        </div>
    </div>
</div>


<!-- ==================== MODAL 1: PURCHASE ==================== -->
<!--
    Purchase / Receive Service (from Vendor)
    → financial_entries: user_type=vendor, type=credit, related_type=2
    → কোনো bank touch নাই
    API: /api/vendors/ve-ac-purchase-store.php
-->
<div id="sv-purchaseModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
    <div class="relative top-10 mx-auto p-5 border w-full max-w-2xl shadow-lg rounded-lg bg-white">
        <div class="flex justify-between items-center pb-3 border-b">
            <h3 class="text-xl font-semibold text-gray-900">
                <i class="fas fa-plus-circle text-green-600 mr-2"></i>
                Purchase / Receive Service — <span id="sv-pur-vendorName" class="text-green-700"></span>
            </h3>
            <button class="sv-modal-close text-gray-400 hover:text-gray-600" data-modal="sv-purchaseModal">
                <i class="fas fa-times text-2xl"></i>
            </button>
        </div>
        <div class="mt-4 space-y-4">
            <div class="bg-green-50 border-l-4 border-green-400 p-2 text-sm text-green-700">
                <i class="fas fa-info-circle mr-1"></i> Vendor থেকে service/product কেনা হচ্ছে। কোনো bank transaction হবে না।
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1"><i class="fas fa-calendar-alt mr-1"></i> Date</label>
                    <input type="date" id="sv-pur-date"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1"><i class="fas fa-taka-sign mr-1"></i> Purchase Amount</label>
                    <input type="number" step="0.01" min="0" id="sv-pur-amount" placeholder="0.00"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500">
                </div>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1"><i class="fas fa-file-alt mr-1"></i> Particular</label>
                <textarea id="sv-pur-particular" rows="3" placeholder="Service/product description"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500"></textarea>
            </div>
            <div class="flex justify-end gap-3 pt-3 border-t">
                <button class="sv-modal-close px-5 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300" data-modal="sv-purchaseModal">
                    <i class="fas fa-times mr-1"></i> Cancel
                </button>
                <button id="sv-pur-saveBtn"
                    class="px-5 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 flex items-center gap-2">
                    <i class="fas fa-save"></i> Save Purchase
                </button>
            </div>
        </div>
    </div>
</div>


<!-- ==================== MODAL 2: PAYMENT ==================== -->
<!--
    Make Payment (to Vendor)
    → financial_entries: user_type=vendor, type=debit, related_type=4
    → ac_banking_stmts: withdraw (bank balance কমে)
    → Selected unpaid purchases link হবে (is_paid, is_partial, is_discounted update)
    API: /api/vendors/ve-ac-payment-store.php
-->
<div id="sv-paymentModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
    <div class="relative top-6 mx-auto p-5 border w-full max-w-3xl shadow-lg rounded-lg bg-white mb-6">
        <div class="flex justify-between items-center pb-3 border-b">
            <h3 class="text-xl font-semibold text-gray-900">
                <i class="fas fa-minus-circle text-red-600 mr-2"></i>
                Make Payment — <span id="sv-pay-vendorName" class="text-red-700"></span>
            </h3>
            <button class="sv-modal-close text-gray-400 hover:text-gray-600" data-modal="sv-paymentModal">
                <i class="fas fa-times text-2xl"></i>
            </button>
        </div>
        <div class="mt-4 space-y-4">

            <!-- Unpaid Purchase List -->
            <div class="bg-gray-50 border border-gray-200 rounded-lg p-3">
                <div class="flex justify-between items-center mb-2">
                    <h4 class="text-sm font-semibold text-gray-700">
                        <i class="fas fa-list-check mr-1 text-red-600"></i>
                        Unpaid / Partial Purchases
                        <span class="text-xs text-gray-400 font-normal ml-1">(কোন purchase এর payment দিচ্ছো select করো)</span>
                    </h4>
                    <button id="sv-pay-selectAll" class="text-xs text-red-600 hover:underline">Select All</button>
                </div>
                <div id="sv-pay-purchaseList" class="space-y-2 max-h-48 overflow-y-auto">
                    <p class="text-xs text-gray-400 text-center py-4">Loading unpaid purchases...</p>
                </div>
                <div class="mt-3 pt-3 border-t flex justify-between items-center">
                    <span class="text-xs text-gray-500">Selected Total:</span>
                    <span id="sv-pay-selectedTotal" class="text-sm font-bold text-red-700">0.00</span>
                </div>
            </div>

            <div id="sv-pay-openingDateInfo" class="bg-purple-50 border-l-4 border-purple-400 p-2 text-sm text-purple-700 hidden">
                <i class="fas fa-calendar-alt mr-2"></i><span id="sv-pay-openingDateText"></span>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1"><i class="fas fa-calendar-alt mr-1"></i> Date</label>
                    <input type="date" id="sv-pay-date"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        <i class="fas fa-taka-sign mr-1"></i> Payment Amount
                        <span id="sv-pay-amountHint" class="text-xs text-gray-400"></span>
                    </label>
                    <input type="number" step="0.01" min="0" id="sv-pay-amount" placeholder="0.00"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1"><i class="fas fa-wallet mr-1"></i> Withdraw From Account</label>
                    <select id="sv-pay-accountSelect"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500">
                        <option value="">-- Select Account --</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1"><i class="fas fa-money-check-alt mr-1"></i> Payment Method</label>
                    <select id="sv-pay-method"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500">
                        <option value="cash">Cash</option>
                        <option value="npsb-rtgs">NPSB/RTGS</option>
                        <option value="cheque">Cheque</option>
                        <option value="bftn-eft">BFTN/EFT</option>
                    </select>
                </div>
            </div>

            <!-- Cheque Details -->
            <div id="sv-pay-cheque" class="hidden grid grid-cols-1 md:grid-cols-3 gap-4 bg-gray-50 p-3 rounded-lg">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Cheque No</label>
                    <input type="text" id="sv-pay-cheque-no" placeholder="Cheque number"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Cheque Date</label>
                    <input type="date" id="sv-pay-cheque-date" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Account Name</label>
                    <input type="text" id="sv-pay-cheque-acc" placeholder="Account name"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                </div>
                <div class="md:col-span-3">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Bank Name</label>
                    <input type="text" id="sv-pay-cheque-bank" placeholder="Bank name"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                </div>
            </div>

            <!-- BFTN/EFT Details -->
            <div id="sv-pay-bftn" class="hidden grid grid-cols-1 md:grid-cols-3 gap-4 bg-gray-50 p-3 rounded-lg">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Account Name</label>
                    <input type="text" id="sv-pay-bftn-acc" placeholder="Account name"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Bank Name</label>
                    <input type="text" id="sv-pay-bftn-bank" placeholder="Bank name"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Transaction Date</label>
                    <input type="date" id="sv-pay-bftn-date" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                </div>
            </div>

            <!-- Payment status indicator -->
            <div id="sv-pay-statusBanner" class="hidden rounded-lg p-3 text-sm font-medium"></div>

            <!-- Discount-to-close section (vendor discount দিলে বাকিটা close করা) -->
            <div id="sv-pay-discountSection" class="hidden bg-orange-50 border border-orange-200 rounded-lg p-3 space-y-3">
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" id="sv-pay-withDiscount" class="w-4 h-4 text-orange-500 rounded">
                    <span class="text-sm font-medium text-orange-800">
                        বাকি টাকা Vendor Discount দিয়ে Close করবো
                        <span id="sv-pay-discountHint" class="text-xs text-orange-600 ml-1"></span>
                    </span>
                </label>
                <div id="sv-pay-discountFields" class="hidden grid grid-cols-1 md:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">Discount Amount</label>
                        <input type="number" step="0.01" min="0" id="sv-pay-discountAmount" placeholder="0.00"
                            class="w-full px-3 py-2 border border-orange-300 rounded-lg focus:ring-2 focus:ring-orange-400 text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">Discount Reason</label>
                        <input type="text" id="sv-pay-discountParticular" placeholder="Reason for discount"
                            class="w-full px-3 py-2 border border-orange-300 rounded-lg focus:ring-2 focus:ring-orange-400 text-sm">
                    </div>
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1"><i class="fas fa-file-alt mr-1"></i> Particular</label>
                <textarea id="sv-pay-particular" rows="2" placeholder="Payment description"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500"></textarea>
            </div>

            <div class="flex justify-end gap-3 pt-3 border-t">
                <button class="sv-modal-close px-5 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300" data-modal="sv-paymentModal">
                    <i class="fas fa-times mr-1"></i> Cancel
                </button>
                <button id="sv-pay-saveBtn"
                    class="px-5 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 flex items-center gap-2">
                    <i class="fas fa-save"></i> Save Payment
                </button>
            </div>
        </div>
    </div>
</div>


<!-- ==================== MODAL 3: REFUND ==================== -->
<!--
    Receive Refund (from Vendor)
    → financial_entries: user_type=vendor, type=debit, related_type=0
    Case A — Non-physical: শুধু financial_entries
    Case B — Physical (vendor টাকা ফেরত দিচ্ছে bank এ):
        ac_banking_stmts deposit + financial_entries
    API: /api/vendors/ve-ac-refund-store.php (NEW)
-->
<div id="sv-refundModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
    <div class="relative top-10 mx-auto p-5 border w-full max-w-2xl shadow-lg rounded-lg bg-white">
        <div class="flex justify-between items-center pb-3 border-b">
            <h3 class="text-xl font-semibold text-gray-900">
                <i class="fas fa-undo-alt text-purple-600 mr-2"></i>
                Receive Refund — <span id="sv-ref-vendorName" class="text-purple-700"></span>
            </h3>
            <button class="sv-modal-close text-gray-400 hover:text-gray-600" data-modal="sv-refundModal">
                <i class="fas fa-times text-2xl"></i>
            </button>
        </div>
        <div class="mt-4 space-y-4">

            <!-- Physical toggle -->
            <div class="bg-purple-50 border border-purple-200 rounded-lg p-3">
                <label class="flex items-center gap-3 cursor-pointer">
                    <input type="checkbox" id="sv-ref-isPhysical"
                        class="w-4 h-4 text-purple-600 rounded">
                    <div>
                        <p class="text-sm font-medium text-purple-800">Vendor সত্যিই টাকা ফেরত দিচ্ছে (Physical Refund)</p>
                        <p class="text-xs text-purple-600">Check করলে Bank account এ টাকা জমা হবে এবং Bank Statement এ entry হবে</p>
                    </div>
                </label>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1"><i class="fas fa-calendar-alt mr-1"></i> Date</label>
                    <input type="date" id="sv-ref-date"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1"><i class="fas fa-taka-sign mr-1"></i> Refund Amount</label>
                    <input type="number" step="0.01" min="0" id="sv-ref-amount" placeholder="0.00"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500">
                </div>
            </div>

            <!-- Physical section -->
            <div id="sv-ref-physicalSection" class="hidden space-y-4">
                <div class="bg-green-50 border-l-4 border-green-400 p-2 text-sm text-green-700">
                    <i class="fas fa-info-circle mr-1"></i> Vendor এর কাছ থেকে টাকা ফেরত আসছে, নিচের Account এ জমা হবে।
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1"><i class="fas fa-wallet mr-1"></i> Deposit To Account</label>
                        <select id="sv-ref-accountSelect"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500">
                            <option value="">-- Select Account --</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1"><i class="fas fa-money-check-alt mr-1"></i> Payment Method</label>
                        <select id="sv-ref-method"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500">
                            <option value="cash">Cash</option>
                            <option value="npsb-rtgs">NPSB/RTGS</option>
                            <option value="cheque">Cheque</option>
                            <option value="bftn-eft">BFTN/EFT</option>
                        </select>
                    </div>
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1"><i class="fas fa-file-alt mr-1"></i> Particular / Reason</label>
                <textarea id="sv-ref-particular" rows="3" placeholder="Reason for refund"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500"></textarea>
            </div>

            <div class="flex justify-end gap-3 pt-3 border-t">
                <button class="sv-modal-close px-5 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300" data-modal="sv-refundModal">
                    <i class="fas fa-times mr-1"></i> Cancel
                </button>
                <button id="sv-ref-saveBtn"
                    class="px-5 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 flex items-center gap-2">
                    <i class="fas fa-save"></i> Save Refund
                </button>
            </div>
        </div>
    </div>
</div>


<!-- ==================== MODAL 4: DISCOUNT ==================== -->
<!--
    Receive Discount (from Vendor)
    → financial_entries: user_type=vendor, type=debit, related_type=5, is_discounted=1
    → কোনো bank touch নাই
    API: /api/vendors/ve-ac-discount-store.php (NEW)
-->
<div id="sv-discountModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
    <div class="relative top-10 mx-auto p-5 border w-full max-w-xl shadow-lg rounded-lg bg-white">
        <div class="flex justify-between items-center pb-3 border-b">
            <h3 class="text-xl font-semibold text-gray-900">
                <i class="fas fa-tag text-orange-500 mr-2"></i>
                Vendor Discount — <span id="sv-disc-vendorName" class="text-orange-600"></span>
            </h3>
            <button class="sv-modal-close text-gray-400 hover:text-gray-600" data-modal="sv-discountModal">
                <i class="fas fa-times text-2xl"></i>
            </button>
        </div>
        <div class="mt-4 space-y-4">
            <div class="bg-orange-50 border-l-4 border-orange-400 p-2 text-sm text-orange-700">
                <i class="fas fa-info-circle mr-1"></i> Vendor discount দিচ্ছে। কোনো bank transaction হবে না — শুধু vendor এর outstanding কমবে।
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1"><i class="fas fa-calendar-alt mr-1"></i> Date</label>
                    <input type="date" id="sv-disc-date"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1"><i class="fas fa-taka-sign mr-1"></i> Discount Amount</label>
                    <input type="number" step="0.01" min="0" id="sv-disc-amount" placeholder="0.00"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500">
                </div>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1"><i class="fas fa-file-alt mr-1"></i> Particular / Reason</label>
                <textarea id="sv-disc-particular" rows="3" placeholder="Reason for discount"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500"></textarea>
            </div>
            <div class="flex justify-end gap-3 pt-3 border-t">
                <button class="sv-modal-close px-5 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300" data-modal="sv-discountModal">
                    <i class="fas fa-times mr-1"></i> Cancel
                </button>
                <button id="sv-disc-saveBtn"
                    class="px-5 py-2 bg-orange-500 text-white rounded-lg hover:bg-orange-600 flex items-center gap-2">
                    <i class="fas fa-save"></i> Save Discount
                </button>
            </div>
        </div>
    </div>
</div>


<!-- ==================== STYLES ==================== -->
<style>
    .hidden { display: none !important; }
    @keyframes pulse { 0%, 100% { opacity: 1; } 50% { opacity: .5; } }
    .animate-pulse { animation: pulse 2s cubic-bezier(.4,0,.6,1) infinite; }
    .table-container { scroll-behavior: smooth; overflow-x: auto; }
    #sv-finTableBody tr { transition: background-color .2s ease; }
</style>


<!-- ==================== JAVASCRIPT ==================== -->
<script>
(function () { // IIFE

    // ==================== CONFIG ====================
    const IP_PATH      = `<?php echo $ip_port; ?>`;
    const VENDOR_ID    = "<?php echo isset($vendorId) ? $vendorId : ''; ?>";
    const FIN_API      = `${IP_PATH}/api/financial_entries/fin-entries.php?id=${VENDOR_ID}`;
    const FETCH_STMT   = `${IP_PATH}/api/accounts/fetch_account_statement_api.php`;
    const API_PURCHASE = `${IP_PATH}/api/vendors/ve-ac-purchase-store.php`;
    const API_PAYMENT  = `${IP_PATH}/api/vendors/ve-ac-payment-store.php`;
    const API_REFUND   = `${IP_PATH}/api/vendors/ve-ac-refund-store.php`;
    const API_DISCOUNT = `${IP_PATH}/api/vendors/ve-ac-discount-store.php`;

    // ==================== STATE ====================
    let originalFinStmts  = [];
    let displayedFinStmts = [];
    let currentOffset     = 0;
    const PAGE_SIZE       = 5;
    let isFiltering       = false;
    let debounceTimer     = null;

    // Unpaid purchases (Payment modal এর জন্য)
    let unpaidPurchases      = [];
    let selectedPurchaseIds  = new Set();

    // ==================== DOM REFS ====================
    const finTableBody      = document.getElementById('sv-finTableBody');
    const searchInput       = document.getElementById('sv-searchInput');
    const filterType        = document.getElementById('sv-filterType');
    const resetFiltersBtn   = document.getElementById('sv-resetFilters');
    const noResultsMsg      = document.getElementById('sv-noResultsMessage');
    const finalOutstanding  = document.getElementById('sv-final-outstanding');
    const loadMoreContainer = document.getElementById('sv-loadMoreContainer');
    const loadMoreBtn       = document.getElementById('sv-loadMoreBtn');
    const loadMoreSpinner   = document.getElementById('sv-loadMoreSpinner');

    // ==================== MODAL CLOSE (fix) ====================
    // inline onclick বাদ — সব close button addEventListener দিয়ে bind করা হচ্ছে
    // HTML এ class="sv-modal-close" + data-modal="modal-id" দেওয়া আছে
    function initModalCloseButtons() {
        document.querySelectorAll('.sv-modal-close').forEach(btn => {
            btn.addEventListener('click', () => {
                const modalId = btn.getAttribute('data-modal');
                if (modalId) closeModal(modalId);
            });
        });
    }

    function closeModal(id) {
        const m = document.getElementById(id);
        if (m) m.classList.add('hidden');
    }

    function openModal(id) {
        const m = document.getElementById(id);
        if (m) m.classList.remove('hidden');
    }

    // ==================== TABLE LOAD ====================
    function reloadFinancialTable() {
        showSkeletonLoaders();
        fetch(FIN_API)
            .then(r => r.json())
            .then(data => {
                if (!data.success) { showErrorState(); return; }
                originalFinStmts = (data.finStmts || []).sort((a, b) => {
                    const da = new Date(a.date), db = new Date(b.date);
                    return da - db !== 0 ? db - da : (b.id || 0) - (a.id || 0);
                });
                currentOffset     = 0;
                displayedFinStmts = originalFinStmts.slice(0, PAGE_SIZE);
                renderFinTable(displayedFinStmts, originalFinStmts);
                updateSummary(originalFinStmts);
                toggleLoadMoreButton();
            })
            .catch(() => showErrorState());
    }

    function showSkeletonLoaders() {
        if (!finTableBody) return;
        finTableBody.innerHTML = '';
        for (let i = 0; i < 5; i++) {
            const tr = document.createElement('tr');
            tr.className = 'animate-pulse';
            tr.innerHTML = `
                <td class="px-6 py-4"><div class="h-4 bg-gray-200 rounded w-24"></div></td>
                <td class="px-6 py-4"><div class="h-4 bg-gray-200 rounded w-48"></div></td>
                <td class="px-6 py-4"><div class="h-4 bg-gray-200 rounded w-32"></div></td>
                <td class="px-6 py-4"><div class="h-6 bg-gray-200 rounded-full w-16"></div></td>
                <td class="px-6 py-4"><div class="h-4 bg-gray-200 rounded w-20"></div></td>
                <td class="px-6 py-4"><div class="h-4 bg-gray-200 rounded w-20"></div></td>`;
            finTableBody.appendChild(tr);
        }
    }

    function showErrorState() {
        if (!finTableBody) return;
        finTableBody.innerHTML = `
            <tr><td colspan="8" class="px-6 py-10 text-center text-gray-500">
                <i class="fas fa-exclamation-triangle text-3xl text-red-400 block mb-2"></i>
                <p class="text-sm">Error loading transactions. Please try again.</p>
            </td></tr>`;
    }

    function updateSummary(list) {
        let credit = 0, debit = 0;
        list.forEach(e => {
            const amt = Number(e.amount) || 0;
            if ((e.type || '').toLowerCase() === 'credit') credit += amt;
            else debit += amt;
        });
        setEl('sv-total-trnx', list.length);
        setEl('sv-total-credit', credit.toFixed(2));
        setEl('sv-total-debit', debit.toFixed(2));
        setEl('sv-total-outstanding', (credit - debit).toFixed(2));
    }

    const RT_BADGE = {
        0: { label: 'Refund',   cls: 'bg-purple-100 text-purple-700' },
        1: { label: 'Sale',     cls: 'bg-red-100 text-red-700' },
        2: { label: 'Purchase', cls: 'bg-green-100 text-green-700' },
        3: { label: 'Receive',  cls: 'bg-blue-100 text-blue-700' },
        4: { label: 'Payment',  cls: 'bg-orange-100 text-orange-700' },
        5: { label: 'Discount', cls: 'bg-yellow-100 text-yellow-700' },
        6: { label: 'Advance',  cls: 'bg-indigo-100 text-indigo-700' },
    };

    function renderFinTable(displayList, calcList) {
        if (!finTableBody) return;
        finTableBody.innerHTML = '';

        if (!displayList || displayList.length === 0) {
            finTableBody.innerHTML = `
                <tr><td colspan="8" class="px-6 py-10 text-center text-gray-500">
                    <i class="fas fa-inbox text-3xl text-gray-400 block mb-2"></i>
                    <p class="text-sm">No transactions found</p>
                </td></tr>`;
            if (finalOutstanding) finalOutstanding.textContent = '0.00';
            return;
        }

        // Running balance — oldest first, vendor perspective
        // credit (purchase) = vendor কে দিতে হবে = outstanding বাড়ে
        // debit  (payment/refund/discount) = দিয়ে দিলাম = outstanding কমে
        const sorted = [...calcList].sort((a, b) => {
            const da = new Date(a.date), db = new Date(b.date);
            return da - db !== 0 ? da - db : (a.id || 0) - (b.id || 0);
        });
        let cum = 0;
        const runMap = new Map();
        sorted.forEach(e => {
            const amt = Number(e.amount) || 0;
            const t   = (e.type || '').toLowerCase();
            if (t === 'credit') cum += amt;
            else cum -= amt;
            runMap.set(e.id, cum);
        });
        const finalBal = cum;

        displayList.forEach(e => {
            const tr = document.createElement('tr');
            tr.className = 'hover:bg-gray-50 transition-colors';

            const type   = (e.type || '').toLowerCase();
            const amt    = Number(e.amount) || 0;
            const rt     = e.related_type !== undefined ? parseInt(e.related_type) : null;
            const runBal = runMap.get(e.id) || 0;

            // Credit column = purchase (vendor কে দিতে হবে)
            // Debit column  = payment/refund/discount (vendor কে দিলাম)
            const creditAmt = type === 'credit' ? amt : null;
            const debitAmt  = type === 'debit'  ? amt : null;

            const typeBadge = type === 'credit'
                ? `<span class="px-2 py-1 rounded-full text-xs font-semibold bg-green-100 text-green-700">Credit</span>`
                : `<span class="px-2 py-1 rounded-full text-xs font-semibold bg-red-100 text-red-700">Debit</span>`;

            let rtBadge = '';
            if (rt !== null && RT_BADGE[rt]) {
                const cfg = RT_BADGE[rt];
                rtBadge = `<span class="ml-1 px-2 py-0.5 rounded text-xs font-medium ${cfg.cls}">${cfg.label}</span>`;
            }

            let statusBadges = '';
            if (e.is_paid == 1)       statusBadges += `<span class="px-1.5 py-0.5 rounded text-xs bg-green-100 text-green-700 mr-1">Paid</span>`;
            if (e.is_partial == 1)    statusBadges += `<span class="px-1.5 py-0.5 rounded text-xs bg-blue-100 text-blue-700 mr-1">Partial</span>`;
            if (e.is_discounted == 1) statusBadges += `<span class="px-1.5 py-0.5 rounded text-xs bg-orange-100 text-orange-700 mr-1">Disc.</span>`;
            if (!statusBadges && type === 'credit' && rt === 2) statusBadges = `<span class="px-1.5 py-0.5 rounded text-xs bg-yellow-100 text-yellow-700">Unpaid</span>`;

            tr.innerHTML = `
                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">${e.date || 'N/A'}</td>
                <td class="px-6 py-2 text-sm text-gray-700 max-w-xs break-words whitespace-normal">
                    <div class="font-medium">${e.purpose || 'N/A'}</div>
                    <div class="text-xs text-gray-400 mt-0.5">${e.user_name || ''}</div>
                </td>
                <td class="px-6 py-4 text-sm text-gray-600">${e.work_title || '—'}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm">${typeBadge}${rtBadge}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm">${statusBadges}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-right text-green-600">
                    ${creditAmt !== null ? creditAmt.toFixed(2) : '<span class="text-gray-300">—</span>'}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-right text-red-600">
                    ${debitAmt !== null ? debitAmt.toFixed(2) : '<span class="text-gray-300">—</span>'}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-right ${runBal > 0 ? 'text-orange-600' : runBal < 0 ? 'text-green-600' : 'text-gray-500'}">
                    ${runBal.toFixed(2)}
                </td>`;
            finTableBody.appendChild(tr);
        });

        // Tfoot: Credit, Debit, Outstanding আলাদা
        let totalCredit = 0, totalDebit = 0;
        calcList.forEach(e => {
            const a = Number(e.amount) || 0;
            if ((e.type || '').toLowerCase() === 'credit') totalCredit += a;
            else totalDebit += a;
        });
        const creditEl = document.getElementById('sv-total-credit');
        const debitEl  = document.getElementById('sv-total-debit');
        if (creditEl) creditEl.textContent = totalCredit.toFixed(2);
        if (debitEl)  debitEl.textContent  = totalDebit.toFixed(2);

        if (finalOutstanding) {
            finalOutstanding.textContent = finalBal.toFixed(2);
            finalOutstanding.className = `px-6 py-4 text-sm font-bold text-right ${finalBal > 0 ? 'text-orange-700' : finalBal < 0 ? 'text-green-700' : 'text-gray-700'}`;
        }
    }

    function filterTransactions() {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(() => {
            const term = searchInput?.value.toLowerCase().trim() || '';
            const type = filterType?.value || 'all';
            isFiltering = term !== '' || type !== 'all';

            if (isFiltering) {
                const filtered = originalFinStmts.filter(e => {
                    const matchSearch = !term ||
                        (e.purpose || '').toLowerCase().includes(term) ||
                        (e.work_title || '').toLowerCase().includes(term) ||
                        (e.date || '').toLowerCase().includes(term) ||
                        String(e.amount || '').includes(term) ||
                        (e.user_name || '').toLowerCase().includes(term);
                    const matchType = type === 'all' || (e.type || '').toLowerCase() === type;
                    return matchSearch && matchType;
                });
                displayedFinStmts = filtered;
                renderFinTable(filtered, filtered);
                updateSummary(filtered);
                if (noResultsMsg) noResultsMsg.classList.toggle('hidden', filtered.length > 0);
                if (loadMoreContainer) loadMoreContainer.classList.add('hidden');
            } else {
                resetPagination();
            }
        }, 300);
    }

    function resetPagination() {
        currentOffset     = 0;
        isFiltering       = false;
        displayedFinStmts = originalFinStmts.slice(0, PAGE_SIZE);
        renderFinTable(displayedFinStmts, originalFinStmts);
        updateSummary(originalFinStmts);
        if (noResultsMsg) noResultsMsg.classList.add('hidden');
        toggleLoadMoreButton();
    }

    function toggleLoadMoreButton() {
        if (!loadMoreContainer) return;
        loadMoreContainer.classList.toggle('hidden',
            isFiltering || (currentOffset + PAGE_SIZE) >= originalFinStmts.length);
    }

    function loadMoreTransactions() {
        if (!loadMoreSpinner || !loadMoreBtn) return;
        loadMoreSpinner.classList.remove('hidden');
        loadMoreBtn.disabled = true;
        setTimeout(() => {
            currentOffset += PAGE_SIZE;
            const next = originalFinStmts.slice(currentOffset, currentOffset + PAGE_SIZE);
            displayedFinStmts = [...displayedFinStmts, ...next];
            renderFinTable(displayedFinStmts, originalFinStmts);
            updateSummary(originalFinStmts);
            toggleLoadMoreButton();
            loadMoreSpinner.classList.add('hidden');
            loadMoreBtn.disabled = false;
        }, 300);
    }


    // ==================== UTILITIES ====================
    function setEl(id, val) {
        const el = document.getElementById(id);
        if (el) el.textContent = val;
    }

    function setToday(inputId) {
        const el = document.getElementById(inputId);
        if (el) {
            const today = new Date().toISOString().split('T')[0];
            el.value = today;
            el.max   = today;
        }
    }

    function setBtnLoading(btnId, loading) {
        const btn = document.getElementById(btnId);
        if (!btn) return;
        btn.disabled = loading;
        const icon = btn.querySelector('i');
        if (icon) icon.className = loading ? 'fas fa-spinner fa-spin' : 'fas fa-save';
    }

    function buildDateTime(dateOnly) {
        const now = new Date();
        return `${dateOnly} ${now.toTimeString().split(' ')[0]}`;
    }

    function validateAmount(val, label) {
        if (!val || parseFloat(val) <= 0) { alert(`Please enter a valid ${label}`); return false; }
        return true;
    }

    async function postJSON(url, data) {
        const r = await fetch(url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        });
        return r.json();
    }

    // Account options load করা — accounts.php include একটাই থাকবে page এ
    // তাই অন্য select গুলোতে options copy করি
    // Account options — API থেকে load করে cache রাখে
    let accountsCache = null;

    async function loadAllAccounts() {
        if (accountsCache) return accountsCache;
        try {
            const r    = await fetch(`${IP_PATH}/api/accounts/all-accounts.php`);
            const data = await r.json();
            accountsCache = data.success ? (data.accounts || []) : [];
        } catch(e) {
            accountsCache = [];
        }
        return accountsCache;
    }

    function buildAccountOptions(accounts) {
        let html = '<option value="">-- Select Account --</option>';
        accounts.forEach(acc => {
            const label = acc.acc_name || acc.name || acc.sys_id;
            html += `<option value="${acc.sys_id}|${label}">${label}</option>`;
        });
        return html;
    }

    async function syncAccountOptions(...selectIds) {
        const accounts = await loadAllAccounts();
        const html     = buildAccountOptions(accounts);
        selectIds.forEach(id => {
            const el = document.getElementById(id);
            if (el) el.innerHTML = html;
        });
    }

    async function fetchOpeningDate(accountSysId, infoId, textId) {
        if (!accountSysId) return;
        try {
            const r   = await fetch(`${FETCH_STMT}?ledger_db_id=${accountSysId}&opening_only=1`);
            const res = await r.json();
            if (res.success && res.data?.length > 0) {
                const info = document.getElementById(infoId);
                const txt  = document.getElementById(textId);
                if (txt)  txt.innerHTML = `Opening Balance date: <strong>${res.data[0].date?.split(' ')[0]}</strong>`;
                if (info) info.classList.remove('hidden');
            }
        } catch(e) {}
    }

    function getAccountValue(selectId) {
        const el = document.getElementById(selectId);
        if (!el || !el.value) return null;
        const parts = el.value.split('|').map(v => v.trim());
        return { sys_id: parts[0] || null, name: parts[1] || null };
    }

    function toggleMethodDetails(method, chequeId, bftnId) {
        document.getElementById(chequeId)?.classList.toggle('hidden', method !== 'cheque');
        document.getElementById(bftnId)?.classList.toggle('hidden',   method !== 'bftn-eft');
    }


    // ==================== PURCHASE MODAL ====================
    function openPurchaseModal() {
        setEl('sv-pur-vendorName', vendorName);
        setToday('sv-pur-date');
        document.getElementById('sv-pur-amount').value = '';
        document.getElementById('sv-pur-particular').value = '';
        openModal('sv-purchaseModal');
    }

    async function submitPurchase() {
        const date       = document.getElementById('sv-pur-date')?.value;
        const amount     = document.getElementById('sv-pur-amount')?.value;
        const particular = document.getElementById('sv-pur-particular')?.value.trim();

        if (!date)                                    return alert('Please select a date');
        if (!validateAmount(amount, 'Purchase Amount')) return;
        if (!particular)                              return alert('Please enter particular');

        setBtnLoading('sv-pur-saveBtn', true);
        try {
            const res = await postJSON(API_PURCHASE, {
                vendorId:        VENDOR_ID,
                vendorName:      vendorName,
                purchasePrice:   amount,
                particular,
                transactionDate: buildDateTime(date)
            });
            if (res.success) {
                alert('Purchase recorded successfully!');
                closeModal('sv-purchaseModal');
                location.reload();
            } else { alert(res.message || 'Failed'); }
        } catch(e) { alert('Network error.'); }
        finally { setBtnLoading('sv-pur-saveBtn', false); }
    }


    // ==================== PAYMENT MODAL ====================
    // ==================== PAYMENT MODAL ====================
    async function loadUnpaidPurchases() {
        const listEl = document.getElementById('sv-pay-purchaseList');
        if (!listEl) return;
        listEl.innerHTML = '<p class="text-xs text-gray-400 text-center py-4"><i class="fas fa-spinner fa-spin mr-1"></i>Loading...</p>';

        try {
            // vendor এর unpaid purchase entries (credit, related_type=2, is_paid=0)
            const r    = await fetch(`${IP_PATH}/api/financial_entries/fin-entries.php?id=${VENDOR_ID}&type=credit&related_type=2&is_paid=0`);
            const data = await r.json();
            unpaidPurchases = data.finStmts || [];

            if (!unpaidPurchases.length) {
                listEl.innerHTML = '<p class="text-xs text-gray-500 text-center py-4">কোনো unpaid purchase নেই। General payment হিসেবে record হবে।</p>';
                return;
            }

            listEl.innerHTML = '';
            unpaidPurchases.forEach(purchase => {
                const div = document.createElement('div');
                div.className = 'flex items-center gap-3 p-2 border border-gray-200 rounded-lg cursor-pointer hover:border-red-400 transition-colors';
                div.dataset.sysId     = purchase.sys_id;
                div.dataset.amount    = purchase.amount;
                div.dataset.remaining = purchase.remaining_amount ?? purchase.amount;

                const purchaseAmt  = Number(purchase.amount);
                const paidAmt      = Number(purchase.paid_amount || 0);
                const remainingAmt = Number(purchase.remaining_amount ?? purchaseAmt);

                const partialBadge = purchase.is_partial == 1
                    ? `<span class="px-1.5 py-0.5 rounded text-xs bg-blue-100 text-blue-700 ml-1">Partial</span>` : '';

                // Paid progress bar
                const paidPct = purchaseAmt > 0 ? Math.min((paidAmt / purchaseAmt) * 100, 100) : 0;
                const progressBar = paidAmt > 0 ? `
                    <div class="w-full bg-gray-200 rounded-full h-1 mt-1">
                        <div class="bg-red-500 h-1 rounded-full" style="width: ${paidPct}%"></div>
                    </div>
                    <p class="text-xs text-gray-400 mt-0.5">Paid: ${paidAmt.toFixed(2)} · Remaining: <span class="text-orange-600 font-medium">${remainingAmt.toFixed(2)}</span></p>
                ` : '';

                div.innerHTML = `
                    <input type="checkbox" class="purch-checkbox w-4 h-4 text-red-600 rounded flex-shrink-0"
                        data-sys-id="${purchase.sys_id}" data-amount="${purchase.amount}" data-remaining="${remainingAmt}">
                    <div class="flex-1 min-w-0">
                        <p class="text-xs font-medium text-gray-800 truncate">${purchase.purpose || 'N/A'}</p>
                        <p class="text-xs text-gray-400">${purchase.date || ''} ${purchase.work_title ? '· ' + purchase.work_title : ''}</p>
                        ${progressBar}
                    </div>
                    <div class="text-right flex-shrink-0">
                        <p class="text-sm font-bold text-green-600">${purchaseAmt.toFixed(2)}</p>
                        ${partialBadge}
                    </div>`;

                div.addEventListener('click', (e) => {
                    if (e.target.type === 'checkbox') return;
                    const cb = div.querySelector('.purch-checkbox');
                    cb.checked = !cb.checked;
                    cb.dispatchEvent(new Event('change'));
                });

                div.querySelector('.purch-checkbox').addEventListener('change', (e) => {
                    const sysId = e.target.dataset.sysId;
                    if (e.target.checked) {
                        selectedPurchaseIds.add(sysId);
                        div.classList.add('bg-red-50', 'border-red-400');
                    } else {
                        selectedPurchaseIds.delete(sysId);
                        div.classList.remove('bg-red-50', 'border-red-400');
                    }
                    updatePaymentUI();
                });

                listEl.appendChild(div);
            });
        } catch(e) {
            listEl.innerHTML = '<p class="text-xs text-red-500 text-center py-4">Error loading purchases.</p>';
        }
    }

    function getSelectedPurchaseTotal() {
        let total = 0;
        unpaidPurchases.forEach(p => {
            if (selectedPurchaseIds.has(p.sys_id)) {
                // remaining_amount ব্যবহার করি — আগে কিছু pay হলে সেটা বাদ
                const remaining = Number(p.remaining_amount ?? p.amount);
                total += remaining;
            }
        });
        return total;
    }

    function updatePaymentUI() {
        const selectedTotal = getSelectedPurchaseTotal();
        const paymentAmt    = parseFloat(document.getElementById('sv-pay-amount')?.value) || 0;

        if (document.getElementById('sv-pay-selectedTotal'))
            document.getElementById('sv-pay-selectedTotal').textContent = selectedTotal.toFixed(2);

        const hint = document.getElementById('sv-pay-amountHint');
        if (hint) hint.textContent = selectedPurchaseIds.size > 0 ? `(Selected: ${selectedTotal.toFixed(2)})` : '';

        const banner      = document.getElementById('sv-pay-statusBanner');
        const discSection = document.getElementById('sv-pay-discountSection');
        const discHint    = document.getElementById('sv-pay-discountHint');

        if (!banner || !discSection) return;

        if (selectedPurchaseIds.size === 0) {
            // Purchase select না করলে → Vendor Advance hint
            if (paymentAmt > 0) {
                banner.classList.remove('hidden');
                banner.className = 'rounded-lg p-3 text-sm font-medium bg-indigo-50 border border-indigo-300 text-indigo-800';
                banner.innerHTML = `<i class="fas fa-piggy-bank mr-1"></i> কোনো Purchase select করা হয়নি — এই <strong>৳${paymentAmt.toFixed(2)}</strong> Vendor কে <strong>Advance</strong> হিসেবে দেওয়া হবে`;
            } else {
                banner.classList.add('hidden');
            }
            discSection.classList.add('hidden');
            return;
        }

        banner.classList.remove('hidden');
        const diff = selectedTotal - paymentAmt;

        if (Math.abs(diff) < 0.01) {
            banner.className = 'rounded-lg p-3 text-sm font-medium bg-green-50 border border-green-300 text-green-800';
            banner.innerHTML = `<i class="fas fa-check-circle mr-1"></i> Full Payment — সব selected purchase paid হয়ে যাবে`;
            discSection.classList.add('hidden');
        } else if (paymentAmt > 0 && paymentAmt < selectedTotal) {
            banner.className = 'rounded-lg p-3 text-sm font-medium bg-blue-50 border border-blue-300 text-blue-800';
            banner.innerHTML = `<i class="fas fa-info-circle mr-1"></i> Partial Payment — বাকি আছে <strong>${diff.toFixed(2)}</strong> টাকা`;
            discSection.classList.remove('hidden');
            if (discHint) discHint.textContent = `(বাকি: ${diff.toFixed(2)})`;
        } else if (paymentAmt > selectedTotal) {
            banner.className = 'rounded-lg p-3 text-sm font-medium bg-yellow-50 border border-yellow-300 text-yellow-800';
            banner.innerHTML = `<i class="fas fa-exclamation-triangle mr-1"></i> Payment (${paymentAmt.toFixed(2)}) > Selected total (${selectedTotal.toFixed(2)})`;
            discSection.classList.add('hidden');
        }
    }

    async function openPaymentModal() {
        setEl('sv-pay-vendorName', vendorName);
        setToday('sv-pay-date');
        document.getElementById('sv-pay-amount').value = '';
        document.getElementById('sv-pay-particular').value = '';
        document.getElementById('sv-pay-method').value = 'cash';
        document.getElementById('sv-pay-cheque').classList.add('hidden');
        document.getElementById('sv-pay-bftn').classList.add('hidden');
        document.getElementById('sv-pay-openingDateInfo').classList.add('hidden');
        document.getElementById('sv-pay-statusBanner')?.classList.add('hidden');
        document.getElementById('sv-pay-discountSection')?.classList.add('hidden');
        document.getElementById('sv-pay-discountFields')?.classList.add('hidden');
        const discCb = document.getElementById('sv-pay-withDiscount');
        if (discCb) discCb.checked = false;
        selectedPurchaseIds.clear();
        if (document.getElementById('sv-pay-selectedTotal'))
            document.getElementById('sv-pay-selectedTotal').textContent = '0.00';

        await syncAccountOptions('sv-pay-accountSelect', 'sv-ref-accountSelect');
        openModal('sv-paymentModal');
        loadUnpaidPurchases();
    }

    async function submitPayment() {
        const date          = document.getElementById('sv-pay-date')?.value;
        const amount        = document.getElementById('sv-pay-amount')?.value;
        const particular    = document.getElementById('sv-pay-particular')?.value.trim();
        const method        = document.getElementById('sv-pay-method')?.value;
        const acc           = getAccountValue('sv-pay-accountSelect');
        const withDiscount  = document.getElementById('sv-pay-withDiscount')?.checked || false;
        const discountAmount= document.getElementById('sv-pay-discountAmount')?.value || 0;
        const discountParticular = document.getElementById('sv-pay-discountParticular')?.value.trim() || '';

        if (!date)                                    return alert('Please select a date');
        if (!validateAmount(amount, 'Payment Amount')) return;
        if (!acc?.sys_id)                             return alert('Please select an account');
        if (!particular)                              return alert('Please enter particular');
        if (withDiscount && (!discountAmount || parseFloat(discountAmount) <= 0))
            return alert('Please enter discount amount');

        const payload = {
            vendorId:           VENDOR_ID,
            vendorName:         vendorName,
            amount,
            particular,
            transactionDate:    buildDateTime(date),
            accountId:          acc.sys_id,
            accountName:        acc.name,
            transferMethod:     method,
            isHistorical:       0,
            selectedPurchaseIds: [...selectedPurchaseIds],
            withDiscount,
            discountAmount:     withDiscount ? discountAmount : 0,
            discountParticular: withDiscount ? discountParticular : ''
        };

        if (method === 'cheque') {
            payload.chequeNo          = document.getElementById('sv-pay-cheque-no')?.value;
            payload.chequeDate        = document.getElementById('sv-pay-cheque-date')?.value;
            payload.chequeAccountName = document.getElementById('sv-pay-cheque-acc')?.value;
            payload.bankName          = document.getElementById('sv-pay-cheque-bank')?.value;
        } else if (method === 'bftn-eft') {
            payload.bftnAccountName   = document.getElementById('sv-pay-bftn-acc')?.value;
            payload.eftBankName       = document.getElementById('sv-pay-bftn-bank')?.value;
            payload.bftnDate          = document.getElementById('sv-pay-bftn-date')?.value;
        }

        setBtnLoading('sv-pay-saveBtn', true);
        try {
            const res = await postJSON(API_PAYMENT, payload);
            if (res.success) {
                const msg = res.is_historical  ? 'ঐতিহাসিক entry সংরক্ষিত হয়েছে'
                          : res.is_partial     ? 'Partial payment recorded!'
                          : res.is_discounted  ? 'Payment + Discount recorded!'
                          : 'Payment recorded successfully!';
                alert(msg);
                closeModal('sv-paymentModal');
                location.reload();
            } else { alert(res.message || 'Failed'); }
        } catch(e) { alert('Network error.'); }
        finally { setBtnLoading('sv-pay-saveBtn', false); }
    }


    // ==================== REFUND MODAL ====================
    function openRefundModal() {
        setEl('sv-ref-vendorName', vendorName);
        setToday('sv-ref-date');
        document.getElementById('sv-ref-amount').value = '';
        document.getElementById('sv-ref-particular').value = '';
        document.getElementById('sv-ref-isPhysical').checked = false;
        document.getElementById('sv-ref-physicalSection').classList.add('hidden');
        syncAccountOptions('sv-ref-accountSelect');
        openModal('sv-refundModal');
    }

    async function submitRefund() {
        const date       = document.getElementById('sv-ref-date')?.value;
        const amount     = document.getElementById('sv-ref-amount')?.value;
        const particular = document.getElementById('sv-ref-particular')?.value.trim();
        const isPhysical = document.getElementById('sv-ref-isPhysical')?.checked;

        if (!date)                                   return alert('Please select a date');
        if (!validateAmount(amount, 'Refund Amount')) return;
        if (!particular)                             return alert('Please enter reason for refund');

        const payload = {
            vendorId:        VENDOR_ID,
            vendorName:      vendorName,
            amount,
            particular,
            transactionDate: buildDateTime(date),
            isPhysical:      isPhysical ? 1 : 0
        };

        if (isPhysical) {
            const acc = getAccountValue('sv-ref-accountSelect');
            if (!acc?.sys_id) return alert('Please select an account for physical refund');
            payload.accountId      = acc.sys_id;
            payload.accountName    = acc.name;
            payload.transferMethod = document.getElementById('sv-ref-method')?.value || 'cash';
        }

        setBtnLoading('sv-ref-saveBtn', true);
        try {
            const res = await postJSON(API_REFUND, payload);
            if (res.success) {
                alert('Refund recorded successfully!');
                closeModal('sv-refundModal');
                location.reload();
            } else { alert(res.message || 'Failed'); }
        } catch(e) { alert('Network error.'); }
        finally { setBtnLoading('sv-ref-saveBtn', false); }
    }


    // ==================== DISCOUNT MODAL ====================
    function openDiscountModal() {
        setEl('sv-disc-vendorName', vendorName);
        setToday('sv-disc-date');
        document.getElementById('sv-disc-amount').value = '';
        document.getElementById('sv-disc-particular').value = '';
        openModal('sv-discountModal');
    }

    async function submitDiscount() {
        const date       = document.getElementById('sv-disc-date')?.value;
        const amount     = document.getElementById('sv-disc-amount')?.value;
        const particular = document.getElementById('sv-disc-particular')?.value.trim();

        if (!date)                                     return alert('Please select a date');
        if (!validateAmount(amount, 'Discount Amount')) return;
        if (!particular)                               return alert('Please enter reason for discount');

        setBtnLoading('sv-disc-saveBtn', true);
        try {
            const res = await postJSON(API_DISCOUNT, {
                vendorId:        VENDOR_ID,
                vendorName:      vendorName,
                amount,
                particular,
                transactionDate: buildDateTime(date)
            });
            if (res.success) {
                alert('Discount recorded successfully!');
                closeModal('sv-discountModal');
                location.reload();
            } else { alert(res.message || 'Failed'); }
        } catch(e) { alert('Network error.'); }
        finally { setBtnLoading('sv-disc-saveBtn', false); }
    }


    // ==================== INIT ====================
    document.addEventListener('DOMContentLoaded', () => {

        // Modal close buttons — addEventListener (inline onclick bug fix)
        initModalCloseButtons();

        // Floating buttons
        document.getElementById('sv-btn-purchase')?.addEventListener('click', openPurchaseModal);
        document.getElementById('sv-btn-payment')?.addEventListener('click',  openPaymentModal);
        document.getElementById('sv-btn-refund')?.addEventListener('click',   openRefundModal);
        document.getElementById('sv-btn-discount')?.addEventListener('click', openDiscountModal);
        document.getElementById('sv-addTrnxCard')?.addEventListener('click',  openPurchaseModal);

        // Payment: amount input → update UI
        document.getElementById('sv-pay-amount')?.addEventListener('input', updatePaymentUI);

        // Payment: select all
        document.getElementById('sv-pay-selectAll')?.addEventListener('click', () => {
            document.querySelectorAll('.purch-checkbox').forEach(cb => {
                if (!cb.checked) { cb.checked = true; cb.dispatchEvent(new Event('change')); }
            });
        });

        // Payment: method toggle
        document.getElementById('sv-pay-method')?.addEventListener('change', function() {
            toggleMethodDetails(this.value, 'sv-pay-cheque', 'sv-pay-bftn');
        });

        // Payment: account → fetch opening date
        document.getElementById('sv-pay-accountSelect')?.addEventListener('change', function() {
            const parts = this.value.split('|').map(v => v.trim());
            if (parts[0]) fetchOpeningDate(parts[0], 'sv-pay-openingDateInfo', 'sv-pay-openingDateText');
        });

        // Payment: discount-to-close checkbox
        document.getElementById('sv-pay-withDiscount')?.addEventListener('change', function() {
            document.getElementById('sv-pay-discountFields')?.classList.toggle('hidden', !this.checked);
            if (this.checked) {
                const selectedTotal = getSelectedPurchaseTotal();
                const payAmt = parseFloat(document.getElementById('sv-pay-amount')?.value) || 0;
                const remaining = selectedTotal - payAmt;
                if (remaining > 0) document.getElementById('sv-pay-discountAmount').value = remaining.toFixed(2);
            }
        });

        // Refund physical toggle
        document.getElementById('sv-ref-isPhysical')?.addEventListener('change', function() {
            document.getElementById('sv-ref-physicalSection').classList.toggle('hidden', !this.checked);
        });

        // Save buttons
        document.getElementById('sv-pur-saveBtn')?.addEventListener('click',  submitPurchase);
        document.getElementById('sv-pay-saveBtn')?.addEventListener('click',  submitPayment);
        document.getElementById('sv-ref-saveBtn')?.addEventListener('click',  submitRefund);
        document.getElementById('sv-disc-saveBtn')?.addEventListener('click', submitDiscount);

        // Table filters
        searchInput?.addEventListener('input', filterTransactions);
        searchInput?.addEventListener('keyup', e => { if (e.key === 'Escape') resetPagination(); });
        filterType?.addEventListener('change', filterTransactions);
        resetFiltersBtn?.addEventListener('click', () => {
            if (searchInput) searchInput.value = '';
            if (filterType)  filterType.value  = 'all';
            resetPagination();
        });
        loadMoreBtn?.addEventListener('click', loadMoreTransactions);

        document.addEventListener('keydown', e => {
            if (e.ctrlKey && e.key === 'f') { e.preventDefault(); searchInput?.focus(); searchInput?.select(); }
        });

        // Load data
        reloadFinancialTable();
    });

})(); // end IIFE
</script>