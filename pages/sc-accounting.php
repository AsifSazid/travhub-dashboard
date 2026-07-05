<!--
    PATH: /clients/sc-accounting.php
    Changes from previous version:
    - Receive modal এ unpaid sale list যোগ হয়েছে (Option C)
    - Multiple sale selection support
    - Full / Partial / Discount-to-close তিন case handle
    - Modal close bug fix: class="cl-modal-close" + data-modal দিয়ে addEventListener
    - inline onclick সম্পূর্ণ বাদ
-->

<!-- ==================== SUMMARY CARDS ==================== -->
<div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4 m-8">
    <div onclick="filterTable('all')" id="card-all"
        class="stat-card group bg-gradient-to-br from-purple-50 to-purple-100 border-2 border-purple-300 p-4 rounded-xl text-center transition-all duration-300 hover:shadow-lg hover:-translate-y-1 cursor-pointer">
        <p id="cl-total-trnx" class="font-semibold text-purple-800">--</p>
        <p class="text-xs text-purple-600 mt-1">Total Trnx</p>
    </div>
    <div onclick="filterTable('credit')" id="card-credit"
        class="stat-card group bg-gradient-to-br from-green-50 to-green-100 border-2 border-green-200 p-4 rounded-xl text-center transition-all duration-300 hover:shadow-lg hover:-translate-y-1 cursor-pointer">
        <p id="cl-total-credit" class="font-semibold text-green-800">--</p>
        <p class="text-xs text-green-600 mt-1">Total Credit</p>
    </div>
    <div onclick="filterTable('debit')" id="card-debit"
        class="stat-card group bg-gradient-to-br from-red-50 to-red-100 border-2 border-red-200 p-4 rounded-xl text-center transition-all duration-300 hover:shadow-lg hover:-translate-y-1 cursor-pointer">
        <p id="cl-total-debit" class="font-semibold text-red-800">--</p>
        <p class="text-xs text-red-600 mt-1">Total Debit</p>
    </div>
    <div onclick="filterTable('outstanding')" id="card-outstanding"
        class="stat-card group bg-gradient-to-br from-yellow-50 to-yellow-100 border-2 border-yellow-200 p-4 rounded-xl text-center transition-all duration-300 hover:shadow-lg hover:-translate-y-1 cursor-pointer">
        <p id="cl-total-outstanding" class="font-semibold text-yellow-800">--</p>
        <p class="text-xs text-yellow-600 mt-1">Outstanding</p>
    </div>
    <div onclick="filterTable('advance')" id="card-advance"
        class="stat-card group bg-gradient-to-br from-indigo-50 to-indigo-100 border-2 border-indigo-200 p-4 rounded-xl text-center transition-all duration-300 hover:shadow-lg hover:-translate-y-1 cursor-pointer">
        <p id="cl-advance-balance" class="font-semibold text-indigo-800">--</p>
        <p class="text-xs text-indigo-600 mt-1">Advance Balance</p>
    </div>
    <div id="cl-addTrnxCard"
        class="stat-card group bg-gradient-to-br from-blue-50 to-blue-100 border-2 border-blue-200 p-4 rounded-xl text-center transition-all duration-300 hover:shadow-lg hover:-translate-y-1 cursor-pointer">
        <p class="font-semibold text-blue-800">+ Add New Trnx</p>
    </div>
</div>
<!-- Active filter label -->
<div id="activeFilterBar" class="hidden mx-8 -mt-4 mb-2 flex items-center gap-2">
    <span class="text-xs text-gray-500">Filtering:</span>
    <span id="activeFilterLabel" class="text-xs font-semibold px-2 py-0.5 rounded-full bg-gray-100 text-gray-700"></span>
    <button onclick="filterTable('all')" class="text-xs text-blue-500 hover:underline ml-1">Reset</button>
</div>

<!-- ==================== FLOATING BUTTONS ==================== -->
<div class="fixed bottom-4 right-4 flex flex-col items-end gap-2 z-40">
    <div>
        <button id="cl-btn-discount" title="Give Discount"
            class="bg-orange-500 text-white p-3 rounded-full shadow-lg hover:bg-orange-600 transition-colors">
            <i class="fas fa-tag text-xl"></i>
        </button>
    </div>
    <div>
        <button id="cl-btn-refund" title="Refund to Client"
            class="bg-purple-600 text-white p-3 rounded-full shadow-lg hover:bg-purple-700 transition-colors">
            <i class="fas fa-undo-alt text-xl"></i>
        </button>
    </div>
    <div>
        <button id="cl-btn-sale" title="Sale / Provide Service"
            class="bg-red-600 text-white p-3 rounded-full shadow-lg hover:bg-red-700 transition-colors">
            <i class="fas fa-minus-circle text-xl"></i>
        </button>
    </div>
    <div>
        <button id="cl-btn-receive" title="Receive Money"
            class="bg-green-600 text-white p-3 rounded-full shadow-lg hover:bg-green-700 transition-colors">
            <i class="fas fa-plus-circle text-xl"></i>
        </button>
    </div>
</div>

<!-- ==================== TABLE ==================== -->
<div class="bg-white rounded-lg shadow p-4">
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-4 gap-3">
        <h2 class="text-2xl font-semibold text-gray-800">Financial Transactions</h2>
        <div class="flex flex-col sm:flex-row gap-3 w-full md:w-auto">
            <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <i class="fas fa-search text-gray-400"></i>
                </div>
                <input type="text" id="cl-searchInput"
                    class="pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 w-full"
                    placeholder="Search transactions...">
            </div>
            <div class="relative">
                <select id="cl-filterType"
                    class="appearance-none pl-4 pr-10 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 w-full">
                    <option value="all">All Types</option>
                    <option value="debit">Debit (Sale)</option>
                    <option value="credit">Credit (Receive/Refund/Discount)</option>
                </select>
                <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                    <i class="fas fa-chevron-down text-gray-400"></i>
                </div>
            </div>
            <button id="cl-resetFilters"
                class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors whitespace-nowrap">
                <i class="fas fa-redo mr-2"></i>Reset
            </button>
        </div>
    </div>

    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase w-64">Purpose</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Work</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Type</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-green-600 uppercase">Credit</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-red-600 uppercase">Debit</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Outstanding</th>
                </tr>
            </thead>
            <tbody id="cl-finTableBody" class="bg-white divide-y divide-gray-200 text-left"></tbody>
            <tfoot class="bg-gray-50 font-semibold">
                <tr>
                    <td colspan="5" class="px-6 py-4 text-right text-sm text-gray-700">Total:</td>
                    <td id="cl-total-credit" class="px-6 py-4 text-sm text-right text-green-700 font-bold">0.00</td>
                    <td id="cl-total-debit" class="px-6 py-4 text-sm text-right text-red-700 font-bold">0.00</td>
                    <td id="cl-final-outstanding" class="px-6 py-4 text-sm text-right text-orange-700 font-bold">0.00</td>
                </tr>
            </tfoot>
        </table>
        <div id="cl-noResultsMessage" class="hidden px-6 py-10 text-center text-gray-500">
            <i class="fas fa-search text-3xl text-gray-400 block mb-2"></i>
            <p class="text-sm">No transactions found</p>
        </div>
        <div id="cl-loadMoreContainer" class="hidden mt-4 text-center">
            <button id="cl-loadMoreBtn" class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                <i class="fas fa-spinner fa-spin hidden mr-2" id="cl-loadMoreSpinner"></i>Load More
            </button>
        </div>
    </div>
</div>


<!-- ==================== MODAL 1: RECEIVE ==================== -->
<div id="cl-receiveModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
    <div class="relative top-6 mx-auto p-5 border w-full max-w-3xl shadow-lg rounded-lg bg-white mb-6">
        <div class="flex justify-between items-center pb-3 border-b">
            <h3 class="text-xl font-semibold text-gray-900">
                <i class="fas fa-plus-circle text-green-600 mr-2"></i>
                Receive Money — <span id="cl-rcv-clientName" class="text-green-700"></span>
            </h3>
            <button class="cl-modal-close text-gray-400 hover:text-gray-600" data-modal="cl-receiveModal">
                <i class="fas fa-times text-2xl"></i>
            </button>
        </div>
        <div class="mt-4 space-y-4">

            <!-- Step 1: Unpaid Sales List -->
            <div class="bg-gray-50 border border-gray-200 rounded-lg p-3">
                <div class="flex justify-between items-center mb-2">
                    <h4 class="text-sm font-semibold text-gray-700">
                        <i class="fas fa-list-check mr-1 text-blue-600"></i>
                        Unpaid / Partial Sales
                        <span class="text-xs text-gray-400 font-normal ml-1">(কোন কাজের payment আসছে select করো)</span>
                    </h4>
                    <button id="cl-rcv-selectAll" class="text-xs text-blue-600 hover:underline">Select All</button>
                </div>
                <div id="cl-rcv-saleList" class="space-y-2 max-h-48 overflow-y-auto">
                    <p class="text-xs text-gray-400 text-center py-4">Loading unpaid sales...</p>
                </div>
                <!-- Selected total -->
                <div class="mt-3 pt-3 border-t flex justify-between items-center">
                    <span class="text-xs text-gray-500">Selected Total:</span>
                    <span id="cl-rcv-selectedTotal" class="text-sm font-bold text-blue-700">0.00</span>
                </div>
            </div>

            <div id="cl-rcv-openingDateInfo" class="bg-purple-50 border-l-4 border-purple-400 p-2 text-sm text-purple-700 hidden">
                <i class="fas fa-calendar-alt mr-2"></i><span id="cl-rcv-openingDateText"></span>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        <i class="fas fa-calendar-alt mr-1"></i> Date
                    </label>
                    <input type="date" id="cl-rcv-date"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        <i class="fas fa-taka-sign mr-1"></i> Receive Amount
                        <span id="cl-rcv-amountHint" class="text-xs text-gray-400"></span>
                    </label>
                    <input type="number" step="0.01" min="0" id="cl-rcv-amount" placeholder="0.00"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        <i class="fas fa-wallet mr-1"></i> Deposit To Account
                    </label>
                    <select id="cl-rcv-accountSelect"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500">
                        <option value="">-- Select Account --</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        <i class="fas fa-money-check-alt mr-1"></i> Payment Method
                    </label>
                    <select id="cl-rcv-method"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500">
                        <option value="cash">Cash</option>
                        <option value="npsb-rtgs">NPSB/RTGS</option>
                        <option value="cheque">Cheque</option>
                        <option value="bftn-eft">BFTN/EFT</option>
                    </select>
                </div>
            </div>

            <!-- Cheque Details -->
            <div id="cl-rcv-cheque" class="hidden grid grid-cols-1 md:grid-cols-3 gap-4 bg-gray-50 p-3 rounded-lg">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Cheque No</label>
                    <input type="text" id="cl-rcv-cheque-no" class="w-full px-3 py-2 border border-gray-300 rounded-lg" placeholder="Cheque number">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Cheque Date</label>
                    <input type="date" id="cl-rcv-cheque-date" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Account Name</label>
                    <input type="text" id="cl-rcv-cheque-acc" class="w-full px-3 py-2 border border-gray-300 rounded-lg" placeholder="Account name">
                </div>
                <div class="md:col-span-3">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Bank Name</label>
                    <input type="text" id="cl-rcv-cheque-bank" class="w-full px-3 py-2 border border-gray-300 rounded-lg" placeholder="Bank name">
                </div>
            </div>

            <!-- BFTN Details -->
            <div id="cl-rcv-bftn" class="hidden grid grid-cols-1 md:grid-cols-3 gap-4 bg-gray-50 p-3 rounded-lg">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Account Name</label>
                    <input type="text" id="cl-rcv-bftn-acc" class="w-full px-3 py-2 border border-gray-300 rounded-lg" placeholder="Account name">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Bank Name</label>
                    <input type="text" id="cl-rcv-bftn-bank" class="w-full px-3 py-2 border border-gray-300 rounded-lg" placeholder="Bank name">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Transaction Date</label>
                    <input type="date" id="cl-rcv-bftn-date" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                </div>
            </div>

            <!-- Payment status indicator -->
            <div id="cl-rcv-statusBanner" class="hidden rounded-lg p-3 text-sm font-medium"></div>

            <!-- Discount-to-close section -->
            <div id="cl-rcv-discountSection" class="hidden bg-orange-50 border border-orange-200 rounded-lg p-3 space-y-3">
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" id="cl-rcv-withDiscount" class="w-4 h-4 text-orange-500 rounded">
                    <span class="text-sm font-medium text-orange-800">
                        বাকি টাকা Discount দিয়ে Close করবো
                        <span id="cl-rcv-discountHint" class="text-xs text-orange-600 ml-1"></span>
                    </span>
                </label>
                <div id="cl-rcv-discountFields" class="hidden grid grid-cols-1 md:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">Discount Amount</label>
                        <input type="number" step="0.01" min="0" id="cl-rcv-discountAmount" placeholder="0.00"
                            class="w-full px-3 py-2 border border-orange-300 rounded-lg focus:ring-2 focus:ring-orange-400 text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">Discount Reason</label>
                        <input type="text" id="cl-rcv-discountParticular" placeholder="Reason for discount"
                            class="w-full px-3 py-2 border border-orange-300 rounded-lg focus:ring-2 focus:ring-orange-400 text-sm">
                    </div>
                </div>
            </div>

            <!-- Particular -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    <i class="fas fa-file-alt mr-1"></i> Particular
                </label>
                <textarea id="cl-rcv-particular" rows="2" placeholder="Transaction description"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500"></textarea>
            </div>

            <div class="flex justify-end gap-3 pt-3 border-t">
                <button class="cl-modal-close px-5 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300" data-modal="cl-receiveModal">
                    <i class="fas fa-times mr-1"></i> Cancel
                </button>
                <button id="cl-rcv-saveBtn"
                    class="px-5 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 flex items-center gap-2">
                    <i class="fas fa-save"></i> Save
                </button>
            </div>
        </div>
    </div>
</div>


<!-- ==================== MODAL 2: SALE ==================== -->
<div id="cl-saleModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
    <div class="relative top-10 mx-auto p-5 border w-full max-w-3xl shadow-lg rounded-lg bg-white">
        <div class="flex justify-between items-center pb-3 border-b">
            <h3 class="text-xl font-semibold text-gray-900">
                <i class="fas fa-minus-circle text-red-600 mr-2"></i>
                Sale / Provide Service — <span id="cl-sale-clientName" class="text-red-700"></span>
            </h3>
            <button class="cl-modal-close text-gray-400 hover:text-gray-600" data-modal="cl-saleModal">
                <i class="fas fa-times text-2xl"></i>
            </button>
        </div>
        <div class="mt-4 space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1"><i class="fas fa-calendar-alt mr-1"></i> Date</label>
                    <input type="date" id="cl-sale-date"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        <i class="fas fa-taka-sign mr-1 text-red-600"></i> Selling Price
                        <span class="text-xs text-gray-400">(Client charge)</span>
                    </label>
                    <input type="number" step="0.01" min="0" id="cl-sale-sellingPrice" placeholder="0.00"
                        class="w-full px-3 py-2 border border-red-300 rounded-lg focus:ring-2 focus:ring-red-500">
                </div>
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        <i class="fas fa-user-tie mr-1"></i> Vendor
                        <span class="text-xs text-gray-400">(Optional)</span>
                    </label>
                    <?php include('form-selects/vendors.php') ?>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        <i class="fas fa-taka-sign mr-1 text-blue-600"></i> Purchase Price
                        <span class="text-xs text-gray-400">(Vendor cost)</span>
                    </label>
                    <input type="number" step="0.01" min="0" id="cl-sale-purchasePrice" placeholder="0.00"
                        class="w-full px-3 py-2 border border-blue-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                </div>
                <div class="flex items-end pb-1">
                    <div class="bg-green-50 border border-green-200 rounded-lg p-3 w-full text-center">
                        <p class="text-xs text-gray-500">Profit</p>
                        <p id="cl-sale-profit" class="text-lg font-bold text-green-700">0.00</p>
                    </div>
                </div>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    <i class="fas fa-file-alt mr-1"></i> Particular <span class="text-xs text-gray-400">(Client entry)</span>
                </label>
                <textarea id="cl-sale-particular" rows="2" placeholder="Service description for client"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500"></textarea>
            </div>
            <div id="cl-sale-vendorParticularWrapper" class="hidden">
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    <i class="fas fa-file-alt mr-1"></i> Particular for Vendor
                </label>
                <textarea id="cl-sale-vendorParticular" rows="2" placeholder="Purchase description for vendor"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"></textarea>
            </div>
            <div class="flex justify-end gap-3 pt-3 border-t">
                <button class="cl-modal-close px-5 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300" data-modal="cl-saleModal">
                    <i class="fas fa-times mr-1"></i> Cancel
                </button>
                <button id="cl-sale-saveBtn"
                    class="px-5 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 flex items-center gap-2">
                    <i class="fas fa-save"></i> Save Sale
                </button>
            </div>
        </div>
    </div>
</div>


<!-- ==================== MODAL 3: REFUND ==================== -->
<div id="cl-refundModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
    <div class="relative top-10 mx-auto p-5 border w-full max-w-2xl shadow-lg rounded-lg bg-white">
        <div class="flex justify-between items-center pb-3 border-b">
            <h3 class="text-xl font-semibold text-gray-900">
                <i class="fas fa-undo-alt text-purple-600 mr-2"></i>
                Refund — <span id="cl-ref-clientName" class="text-purple-700"></span>
            </h3>
            <button class="cl-modal-close text-gray-400 hover:text-gray-600" data-modal="cl-refundModal">
                <i class="fas fa-times text-2xl"></i>
            </button>
        </div>
        <div class="mt-4 space-y-4">
            <div class="bg-purple-50 border border-purple-200 rounded-lg p-3">
                <label class="flex items-center gap-3 cursor-pointer">
                    <input type="checkbox" id="cl-ref-isPhysical" class="w-4 h-4 text-purple-600 rounded">
                    <div>
                        <p class="text-sm font-medium text-purple-800">টাকা ইতিমধ্যে Receive করা হয়েছে (Physical Refund)</p>
                        <p class="text-xs text-purple-600">Check করলে Bank account থেকে টাকা বের হবে</p>
                    </div>
                </label>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1"><i class="fas fa-calendar-alt mr-1"></i> Date</label>
                    <input type="date" id="cl-ref-date"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1"><i class="fas fa-taka-sign mr-1"></i> Refund Amount</label>
                    <input type="number" step="0.01" min="0" id="cl-ref-amount" placeholder="0.00"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500">
                </div>
            </div>
            <div id="cl-ref-physicalSection" class="hidden space-y-3">
                <div class="bg-yellow-50 border-l-4 border-yellow-400 p-2 text-sm text-yellow-700">
                    <i class="fas fa-info-circle mr-1"></i> নিচের Account থেকে টাকা বের করে client কে দেয়া হবে।
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1"><i class="fas fa-wallet mr-1"></i> Withdraw From Account</label>
                        <select id="cl-ref-accountSelect"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500">
                            <option value="">-- Select Account --</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1"><i class="fas fa-money-check-alt mr-1"></i> Method</label>
                        <select id="cl-ref-method"
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
                <textarea id="cl-ref-particular" rows="2" placeholder="Reason for refund"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500"></textarea>
            </div>
            <div class="flex justify-end gap-3 pt-3 border-t">
                <button class="cl-modal-close px-5 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300" data-modal="cl-refundModal">
                    <i class="fas fa-times mr-1"></i> Cancel
                </button>
                <button id="cl-ref-saveBtn"
                    class="px-5 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 flex items-center gap-2">
                    <i class="fas fa-save"></i> Save Refund
                </button>
            </div>
        </div>
    </div>
</div>


<!-- ==================== MODAL 4: DISCOUNT ==================== -->
<div id="cl-discountModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
    <div class="relative top-10 mx-auto p-5 border w-full max-w-xl shadow-lg rounded-lg bg-white">
        <div class="flex justify-between items-center pb-3 border-b">
            <h3 class="text-xl font-semibold text-gray-900">
                <i class="fas fa-tag text-orange-500 mr-2"></i>
                Discount — <span id="cl-disc-clientName" class="text-orange-600"></span>
            </h3>
            <button class="cl-modal-close text-gray-400 hover:text-gray-600" data-modal="cl-discountModal">
                <i class="fas fa-times text-2xl"></i>
            </button>
        </div>
        <div class="mt-4 space-y-4">
            <div class="bg-orange-50 border-l-4 border-orange-400 p-2 text-sm text-orange-700">
                <i class="fas fa-info-circle mr-1"></i> Discount এ কোনো bank transaction হয় না।
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1"><i class="fas fa-calendar-alt mr-1"></i> Date</label>
                    <input type="date" id="cl-disc-date"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1"><i class="fas fa-taka-sign mr-1"></i> Discount Amount</label>
                    <input type="number" step="0.01" min="0" id="cl-disc-amount" placeholder="0.00"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500">
                </div>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1"><i class="fas fa-file-alt mr-1"></i> Particular / Reason</label>
                <textarea id="cl-disc-particular" rows="2" placeholder="Reason for discount"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500"></textarea>
            </div>
            <div class="flex justify-end gap-3 pt-3 border-t">
                <button class="cl-modal-close px-5 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300" data-modal="cl-discountModal">
                    <i class="fas fa-times mr-1"></i> Cancel
                </button>
                <button id="cl-disc-saveBtn"
                    class="px-5 py-2 bg-orange-500 text-white rounded-lg hover:bg-orange-600 flex items-center gap-2">
                    <i class="fas fa-save"></i> Save Discount
                </button>
            </div>
        </div>
    </div>
</div>


<style>
    .hidden { display: none !important; }
    @keyframes pulse { 0%, 100% { opacity: 1; } 50% { opacity: .5; } }
    .animate-pulse { animation: pulse 2s cubic-bezier(.4,0,.6,1) infinite; }
    .sale-item-selected { background-color: #f0fdf4; border-color: #16a34a !important; }
</style>


<script>
(function () {

    const IP_PATH      = `<?php echo $ip_port; ?>`;
    const CLIENT_ID    = "<?php echo isset($clientId) ? $clientId : ''; ?>";
    const FIN_API      = `<?php echo $getClientFinEntriesApi; ?>`;
    const FETCH_STMT   = `${IP_PATH}/api/accounts/fetch_account_statement_api.php`;
    const FIN_ENTRIES  = `${IP_PATH}/api/financial_entries/fin-entries.php`;
    const API_RECEIVE  = `${IP_PATH}/api/clients/cl-ac-receive-store.php`;
    const API_SALE     = `${IP_PATH}/api/clients/cl-ac-sale-store.php`;
    const API_PURCHASE = `${IP_PATH}/api/vendors/ve-ac-purchase-store.php`;
    const API_REFUND   = `${IP_PATH}/api/clients/cl-ac-refund-store.php`;
    const API_DISCOUNT = `${IP_PATH}/api/clients/cl-ac-discount-store.php`;

    let originalFinStmts  = [];
    let displayedFinStmts = [];
    let currentOffset     = 0;
    const PAGE_SIZE       = 5;
    let isFiltering       = false;
    let debounceTimer     = null;

    // Unpaid sales cache
    let unpaidSales       = [];
    let selectedSaleIds   = new Set();

    const RT_BADGE = {
        0: { label: 'Refund',   cls: 'bg-purple-100 text-purple-700' },
        1: { label: 'Sale',     cls: 'bg-red-100 text-red-700' },
        2: { label: 'Purchase', cls: 'bg-blue-100 text-blue-700' },
        3: { label: 'Receive',  cls: 'bg-green-100 text-green-700' },
        4: { label: 'Payment',  cls: 'bg-orange-100 text-orange-700' },
        5: { label: 'Discount', cls: 'bg-yellow-100 text-yellow-700' },
        6: { label: 'Advance',  cls: 'bg-indigo-100 text-indigo-700' },
        7: { label: 'Baksheesh', cls: 'bg-pink-100 text-pink-700' },
    };

    // ==================== UTILITIES ====================
    function setEl(id, val) { const el = document.getElementById(id); if (el) el.textContent = val; }
    function getEl(id) { return document.getElementById(id); }

    function setToday(inputId) {
        const el = getEl(inputId);
        if (el) { const t = new Date().toISOString().split('T')[0]; el.value = t; el.max = t; }
    }

    function setBtnLoading(btnId, loading) {
        const btn = getEl(btnId);
        if (!btn) return;
        btn.disabled = loading;
        const icon = btn.querySelector('i');
        if (icon) icon.className = loading ? 'fas fa-spinner fa-spin' : 'fas fa-save';
    }

    function buildDateTime(dateOnly) {
        return `${dateOnly} ${new Date().toTimeString().split(' ')[0]}`;
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

    function closeModal(id) { getEl(id)?.classList.add('hidden'); }
    function openModal(id)  { getEl(id)?.classList.remove('hidden'); }

    // Account options — API থেকে load করে সব select এ populate করে
    // একবার fetch করে cache করে রাখে, বারবার API call হয় না
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
            // ac_banking table এ acc_name column আছে, name না
            const label = acc.acc_name || acc.name || acc.sys_id;
            html += `<option value="${acc.sys_id}|${label}">${label}</option>`;
        });
        return html;
    }

    async function populateAccountSelect(selectId) {
        const el = getEl(selectId);
        if (!el) return;
        const accounts = await loadAllAccounts();
        el.innerHTML   = buildAccountOptions(accounts);
    }

    // একসাথে সব account select populate করে
    async function syncAccountsTo(...selectIds) {
        const accounts = await loadAllAccounts();
        const html     = buildAccountOptions(accounts);
        selectIds.forEach(id => {
            const el = getEl(id);
            if (el) el.innerHTML = html;
        });
    }

    // ==================== TABLE ====================
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
        const tbody = getEl('cl-finTableBody');
        if (!tbody) return;
        tbody.innerHTML = '';
        for (let i = 0; i < 5; i++) {
            const tr = document.createElement('tr');
            tr.className = 'animate-pulse';
            tr.innerHTML = `
                <td class="px-6 py-4"><div class="h-4 bg-gray-200 rounded w-24"></div></td>
                <td class="px-6 py-4"><div class="h-4 bg-gray-200 rounded w-48"></div></td>
                <td class="px-6 py-4"><div class="h-4 bg-gray-200 rounded w-32"></div></td>
                <td class="px-6 py-4"><div class="h-6 bg-gray-200 rounded-full w-16"></div></td>
                <td class="px-6 py-4"><div class="h-4 bg-gray-200 rounded w-16"></div></td>
                <td class="px-6 py-4"><div class="h-4 bg-gray-200 rounded w-20"></div></td>
                <td class="px-6 py-4"><div class="h-4 bg-gray-200 rounded w-20"></div></td>`;
            tbody.appendChild(tr);
        }
    }

    function showErrorState() {
        const tbody = getEl('cl-finTableBody');
        if (tbody) tbody.innerHTML = `
            <tr><td colspan="7" class="px-6 py-10 text-center text-gray-500">
                <i class="fas fa-exclamation-triangle text-3xl text-red-400 block mb-2"></i>
                <p class="text-sm">Error loading. Please try again.</p>
            </td></tr>`;
    }

    function updateSummary(list) {
        let credit = 0, debit = 0;
        // সব entries count
        list.forEach(e => {
            const amt = Number(e.amount) || 0;
            const t   = (e.type || '').toLowerCase();
            if (t === 'debit') debit += amt;
            else credit += amt;
        });
        setEl('cl-total-trnx', list.length);
        setEl('cl-total-credit', credit.toFixed(2));
        setEl('cl-total-debit', debit.toFixed(2));
        setEl('cl-total-outstanding', (debit - credit).toFixed(2));

        // Advance balance — আলাদা API call
        loadAdvanceBalance();
    }

    /* ===== Advance Balance ===== */
    async function loadAdvanceBalance() {
        try {
            // Balance = SUM(credit rt=6) - SUM(debit rt=6)
            const [crRes, drRes] = await Promise.all([
                fetch(`${FIN_API}&type=credit&related_type=6`),
                fetch(`${FIN_API}&type=debit&related_type=6`)
            ]);
            const crData = await crRes.json();
            const drData = await drRes.json();

            const totalIn  = (crData.finStmts || []).reduce((s,e) => s + (parseFloat(e.amount)||0), 0);
            const totalOut = (drData.finStmts || []).reduce((s,e) => s + (parseFloat(e.amount)||0), 0);
            const balance  = totalIn - totalOut;

            setEl('cl-advance-balance', balance.toFixed(2));
        } catch(e) {
            setEl('cl-advance-balance', '0.00');
        }
    }

    /* ===== Card Filter — same table এ data change ===== */
    let currentFilter = 'all';

    // onclick attribute থেকে call করতে global expose করি
    window.filterTable = function filterTable(filterType) {
        currentFilter = filterType;

        // Card highlight
        const cardMap = {
            'all'        : 'card-all',
            'credit'     : 'card-credit',
            'debit'      : 'card-debit',
            'outstanding': 'card-outstanding',
            'advance'    : 'card-advance',
        };
        Object.values(cardMap).forEach(id => {
            const el = getEl(id);
            if (el) el.style.boxShadow = '';
        });
        const activeCard = getEl(cardMap[filterType]);
        if (activeCard) activeCard.style.boxShadow = '0 0 0 3px rgba(99,102,241,0.5)';

        // Filter label
        const labelMap = {
            'all'        : 'সব Transactions',
            'credit'     : 'Credit only',
            'debit'      : 'Debit only',
            'outstanding': 'Outstanding (Sale + Receive)',
            'advance'    : 'Advance Statement',
        };
        const filterBar = getEl('activeFilterBar');
        const filterLabel = getEl('activeFilterLabel');
        if (filterType === 'all') {
            filterBar?.classList.add('hidden');
        } else {
            filterBar?.classList.remove('hidden');
            if (filterLabel) filterLabel.textContent = labelMap[filterType] || filterType;
        }

        // Filter originalFinStmts (IIFE এর ভেতরের variable)
        let filtered;
        switch(filterType) {
            case 'credit':
                filtered = originalFinStmts.filter(e => (e.type||'').toLowerCase() === 'credit');
                break;
            case 'debit':
                filtered = originalFinStmts.filter(e => (e.type||'').toLowerCase() === 'debit');
                break;
            case 'outstanding':
                filtered = originalFinStmts; // সব entries
                break;
            case 'advance':
                filtered = originalFinStmts.filter(e => parseInt(e.related_type ?? -1) === 6);
                break;
            default:
                filtered = originalFinStmts;
        }

        renderFinTable(filtered.slice(0, PAGE_SIZE), filtered);
    }

    function renderFinTable(displayList, calcList) {
        const tbody = getEl('cl-finTableBody');
        const finalEl = getEl('cl-final-outstanding');
        if (!tbody) return;
        tbody.innerHTML = '';

        if (!displayList?.length) {
            tbody.innerHTML = `<tr><td colspan="8" class="px-6 py-10 text-center text-gray-500">
                <i class="fas fa-inbox text-3xl text-gray-400 block mb-2"></i>
                <p class="text-sm">No transactions found</p></td></tr>`;
            if (finalEl) finalEl.textContent = '0.00';
            return;
        }

        // Running balance
        const sorted = [...calcList].sort((a, b) => {
            const da = new Date(a.date), db = new Date(b.date);
            return da - db !== 0 ? da - db : (a.id || 0) - (b.id || 0);
        });
        let cum = 0;
        const runMap = new Map();
        sorted.forEach(e => {
            const amt = Number(e.amount) || 0;
            const t   = (e.type || '').toLowerCase();
            const rt  = parseInt(e.related_type ?? -1);

            // সব entries — debit বাড়ে, credit কমে
            cum += t === 'debit' ? amt : -amt;
            runMap.set(e.id, cum);
        });

        displayList.forEach(e => {
            const tr = document.createElement('tr');
            tr.className = 'hover:bg-gray-50 transition-colors';
            const type   = (e.type || '').toLowerCase();
            const amt    = Number(e.amount) || 0;
            const rt     = e.related_type !== undefined ? parseInt(e.related_type) : null;
            const runBal = runMap.get(e.id) || 0;

            const typeBadge = type === 'debit'
                ? `<span class="px-2 py-1 rounded-full text-xs font-semibold bg-red-100 text-red-700">Debit</span>`
                : `<span class="px-2 py-1 rounded-full text-xs font-semibold bg-green-100 text-green-700">Credit</span>`;

            let rtBadge = '';
            if (rt !== null && RT_BADGE[rt]) {
                const cfg = RT_BADGE[rt];
                rtBadge = `<span class="ml-1 px-2 py-0.5 rounded text-xs font-medium ${cfg.cls}">${cfg.label}</span>`;
            }

            // Status badges
            let statusBadges = '';
            if (e.is_paid == 1)       statusBadges += `<span class="px-1.5 py-0.5 rounded text-xs bg-green-100 text-green-700 mr-1">Paid</span>`;
            if (e.is_partial == 1)    statusBadges += `<span class="px-1.5 py-0.5 rounded text-xs bg-blue-100 text-blue-700 mr-1">Partial</span>`;
            if (e.is_discounted == 1) statusBadges += `<span class="px-1.5 py-0.5 rounded text-xs bg-orange-100 text-orange-700 mr-1">Disc.</span>`;
            if (!statusBadges && type === 'debit') statusBadges = `<span class="px-1.5 py-0.5 rounded text-xs bg-yellow-100 text-yellow-700">Unpaid</span>`;

            // Credit/Debit আলাদা column
            const creditAmt = type === 'credit' ? amt : null;
            const debitAmt  = type === 'debit'  ? amt : null;

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
                    ${creditAmt !== null ? creditAmt.toFixed(2) : '<span class="text-gray-300">—</span>'}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-right text-red-600">
                    ${debitAmt !== null ? debitAmt.toFixed(2) : '<span class="text-gray-300">—</span>'}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-right ${runBal > 0 ? 'text-orange-600' : runBal < 0 ? 'text-green-600' : 'text-gray-500'}">
                    ${runBal.toFixed(2)}</td>`;
            tbody.appendChild(tr);
        });

        // সব entries count
        let totalCredit = 0, totalDebit = 0;
        calcList.forEach(e => {
            const a = Number(e.amount) || 0;
            const t = (e.type || '').toLowerCase();
            if (t === 'credit') totalCredit += a;
            else totalDebit += a;
        });

        const creditEl = getEl('cl-total-credit');
        const debitEl  = getEl('cl-total-debit');
        if (creditEl) creditEl.textContent = totalCredit.toFixed(2);
        if (debitEl)  debitEl.textContent  = totalDebit.toFixed(2);

        if (finalEl) {
            finalEl.textContent = cum.toFixed(2);
            finalEl.className = `px-6 py-4 text-sm font-bold text-right ${cum > 0 ? 'text-orange-700' : cum < 0 ? 'text-green-700' : 'text-gray-700'}`;
        }
    }

    function filterTransactions() {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(() => {
            const term = getEl('cl-searchInput')?.value.toLowerCase().trim() || '';
            const type = getEl('cl-filterType')?.value || 'all';
            isFiltering = term !== '' || type !== 'all';
            if (isFiltering) {
                const filtered = originalFinStmts.filter(e => {
                    const ms = !term ||
                        (e.purpose || '').toLowerCase().includes(term) ||
                        (e.work_title || '').toLowerCase().includes(term) ||
                        (e.date || '').includes(term) ||
                        String(e.amount || '').includes(term) ||
                        (e.user_name || '').toLowerCase().includes(term);
                    const mt = type === 'all' || (e.type || '').toLowerCase() === type;
                    return ms && mt;
                });
                displayedFinStmts = filtered;
                renderFinTable(filtered, filtered);
                updateSummary(filtered);
                getEl('cl-noResultsMessage')?.classList.toggle('hidden', filtered.length > 0);
                getEl('cl-loadMoreContainer')?.classList.add('hidden');
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
        getEl('cl-noResultsMessage')?.classList.add('hidden');
        toggleLoadMoreButton();
    }

    function toggleLoadMoreButton() {
        const show = !isFiltering && (currentOffset + PAGE_SIZE) < originalFinStmts.length;
        getEl('cl-loadMoreContainer')?.classList.toggle('hidden', !show);
    }

    function loadMoreTransactions() {
        const spinner = getEl('cl-loadMoreSpinner');
        const btn     = getEl('cl-loadMoreBtn');
        if (!btn) return;
        spinner?.classList.remove('hidden');
        btn.disabled = true;
        setTimeout(() => {
            currentOffset += PAGE_SIZE;
            const next = originalFinStmts.slice(currentOffset, currentOffset + PAGE_SIZE);
            displayedFinStmts = [...displayedFinStmts, ...next];
            renderFinTable(displayedFinStmts, originalFinStmts);
            updateSummary(originalFinStmts);
            toggleLoadMoreButton();
            spinner?.classList.add('hidden');
            btn.disabled = false;
        }, 300);
    }


    // ==================== RECEIVE MODAL ====================
    async function loadUnpaidSales() {
        const listEl = getEl('cl-rcv-saleList');
        if (!listEl) return;
        listEl.innerHTML = '<p class="text-xs text-gray-400 text-center py-4"><i class="fas fa-spinner fa-spin mr-1"></i>Loading...</p>';

        try {
            // Server-side filter: debit + related_type=1 (sale) + is_paid=0
            // API এখন received_amount, discounted_amount, remaining_amount পাঠাচ্ছে
            const r    = await fetch(`${FIN_ENTRIES}?id=${CLIENT_ID}&type=debit&related_type=1&is_paid=0`);
            const data = await r.json();
            unpaidSales = data.finStmts || [];

            if (!unpaidSales.length) {
                listEl.innerHTML = '<p class="text-xs text-gray-500 text-center py-4">কোনো unpaid sale নেই। General receive হিসেবে record হবে।</p>';
                return;
            }

            listEl.innerHTML = '';
            unpaidSales.forEach(sale => {
                const div = document.createElement('div');
                div.className = 'flex items-center gap-3 p-2 border border-gray-200 rounded-lg cursor-pointer hover:border-green-400 transition-colors';
                div.dataset.sysId      = sale.sys_id;
                div.dataset.amount     = sale.amount;
                div.dataset.remaining  = sale.remaining_amount ?? sale.amount;

                const saleAmt      = Number(sale.amount);
                const receivedAmt  = Number(sale.received_amount  || 0);
                const remainingAmt = Number(sale.remaining_amount ?? saleAmt);

                const partialBadge = sale.is_partial == 1
                    ? `<span class="px-1.5 py-0.5 rounded text-xs bg-blue-100 text-blue-700 ml-1">Partial</span>` : '';

                // Received progress bar
                const receivedPct = saleAmt > 0 ? Math.min((receivedAmt / saleAmt) * 100, 100) : 0;
                const progressBar = receivedAmt > 0 ? `
                    <div class="w-full bg-gray-200 rounded-full h-1 mt-1">
                        <div class="bg-green-500 h-1 rounded-full" style="width: ${receivedPct}%"></div>
                    </div>
                    <p class="text-xs text-gray-400 mt-0.5">Received: ${receivedAmt.toFixed(2)} · Remaining: <span class="text-orange-600 font-medium">${remainingAmt.toFixed(2)}</span></p>
                ` : '';

                div.innerHTML = `
                    <input type="checkbox" class="sale-checkbox w-4 h-4 text-green-600 rounded flex-shrink-0"
                        data-sys-id="${sale.sys_id}" data-amount="${sale.amount}" data-remaining="${remainingAmt}">
                    <div class="flex-1 min-w-0">
                        <p class="text-xs font-medium text-gray-800 truncate">${sale.purpose || 'N/A'}</p>
                        <p class="text-xs text-gray-400">${sale.date || ''} ${sale.work_title ? '· ' + sale.work_title : ''}</p>
                        ${progressBar}
                    </div>
                    <div class="text-right flex-shrink-0">
                        <p class="text-sm font-bold text-red-600">${saleAmt.toFixed(2)}</p>
                        ${partialBadge}
                    </div>`;

                // Click anywhere on row = toggle checkbox
                div.addEventListener('click', (e) => {
                    if (e.target.type === 'checkbox') return; // checkbox নিজেই handle করবে
                    const cb = div.querySelector('.sale-checkbox');
                    cb.checked = !cb.checked;
                    cb.dispatchEvent(new Event('change'));
                });

                div.querySelector('.sale-checkbox').addEventListener('change', (e) => {
                    const sysId = e.target.dataset.sysId;
                    if (e.target.checked) {
                        selectedSaleIds.add(sysId);
                        div.classList.add('sale-item-selected');
                    } else {
                        selectedSaleIds.delete(sysId);
                        div.classList.remove('sale-item-selected');
                    }
                    updateReceiveUI();
                });

                listEl.appendChild(div);
            });
        } catch(e) {
            listEl.innerHTML = '<p class="text-xs text-red-500 text-center py-4">Error loading sales.</p>';
        }
    }

    function getSelectedTotal() {
        let total = 0;
        unpaidSales.forEach(s => {
            if (selectedSaleIds.has(s.sys_id)) {
                // remaining_amount ব্যবহার করি — আগে কিছু receive হলে সেটা বাদ
                const remaining = Number(s.remaining_amount ?? s.amount);
                total += remaining;
            }
        });
        return total;
    }

    function updateReceiveUI() {
        const selectedTotal = getSelectedTotal();
        const receiveAmt    = parseFloat(getEl('cl-rcv-amount')?.value) || 0;

        setEl('cl-rcv-selectedTotal', selectedTotal.toFixed(2));

        const hint = getEl('cl-rcv-amountHint');
        if (selectedSaleIds.size > 0 && hint) {
            hint.textContent = `(Selected total: ${selectedTotal.toFixed(2)})`;
        } else if (hint) {
            hint.textContent = '';
        }

        // Status banner
        const banner      = getEl('cl-rcv-statusBanner');
        const discSection = getEl('cl-rcv-discountSection');
        const discHint    = getEl('cl-rcv-discountHint');

        if (!banner || !discSection) return;

        if (selectedSaleIds.size === 0) {
            // Sale select না করলে → Advance hint
            if (receiveAmt > 0) {
                banner.classList.remove('hidden');
                banner.className = 'rounded-lg p-3 text-sm font-medium bg-indigo-50 border border-indigo-300 text-indigo-800';
                banner.innerHTML = `<i class="fas fa-piggy-bank mr-1"></i> কোনো Sale select করা হয়নি — এই <strong>৳${receiveAmt.toFixed(2)}</strong> <strong>Advance</strong> হিসেবে save হবে`;
            } else {
                banner.classList.add('hidden');
            }
            discSection.classList.add('hidden');
            return;
        }

        banner.classList.remove('hidden');
        const diff = selectedTotal - receiveAmt;

        if (Math.abs(diff) < 0.01) {
            // Full payment
            banner.className = 'rounded-lg p-3 text-sm font-medium bg-green-50 border border-green-300 text-green-800';
            banner.innerHTML = `<i class="fas fa-check-circle mr-1"></i> Full Payment — সব selected sale paid হয়ে যাবে`;
            discSection.classList.add('hidden');
        } else if (receiveAmt > 0 && receiveAmt < selectedTotal) {
            // Partial or discount-to-close
            banner.className = 'rounded-lg p-3 text-sm font-medium bg-blue-50 border border-blue-300 text-blue-800';
            banner.innerHTML = `<i class="fas fa-info-circle mr-1"></i> Partial Payment — বাকি আছে <strong>${diff.toFixed(2)}</strong> টাকা`;
            discSection.classList.remove('hidden');
            if (discHint) discHint.textContent = `(বাকি: ${diff.toFixed(2)})`;
        } else if (receiveAmt > selectedTotal) {
            banner.className = 'rounded-lg p-3 text-sm font-medium bg-yellow-50 border border-yellow-300 text-yellow-800';
            banner.innerHTML = `<i class="fas fa-exclamation-triangle mr-1"></i> Receive amount (${receiveAmt.toFixed(2)}) > Selected total (${selectedTotal.toFixed(2)})`;
            discSection.classList.add('hidden');
        }
    }

    async function openReceiveModal() {
        setEl('cl-rcv-clientName', clientName);
        setToday('cl-rcv-date');
        getEl('cl-rcv-amount').value = '';
        getEl('cl-rcv-particular').value = '';
        getEl('cl-rcv-method').value = 'cash';
        getEl('cl-rcv-cheque')?.classList.add('hidden');
        getEl('cl-rcv-bftn')?.classList.add('hidden');
        getEl('cl-rcv-openingDateInfo')?.classList.add('hidden');
        getEl('cl-rcv-statusBanner')?.classList.add('hidden');
        getEl('cl-rcv-discountSection')?.classList.add('hidden');
        getEl('cl-rcv-discountFields')?.classList.add('hidden');
        getEl('cl-rcv-withDiscount') && (getEl('cl-rcv-withDiscount').checked = false);
        selectedSaleIds.clear();
        setEl('cl-rcv-selectedTotal', '0.00');

        // Account options load — await করা জরুরি, না হলে modal open এর আগে load হবে না
        await loadAccountsFromAPI();

        openModal('cl-receiveModal');
        loadUnpaidSales();
    }

    async function loadAccountsFromAPI() {
        // Receive modal + Refund modal দুইটাতেই একসাথে populate করি
        await syncAccountsTo('cl-rcv-accountSelect', 'cl-ref-accountSelect');
    }

    async function submitReceive() {
        const date       = getEl('cl-rcv-date')?.value;
        const amount     = getEl('cl-rcv-amount')?.value;
        const method     = getEl('cl-rcv-method')?.value;
        const particular = getEl('cl-rcv-particular')?.value.trim();
        const accEl      = getEl('cl-rcv-accountSelect');
        const accParts   = accEl?.value?.split('|').map(v => v.trim()) || [];
        const accountId  = accParts[0] || '';
        const accountName= accParts[1] || '';
        const withDiscount  = getEl('cl-rcv-withDiscount')?.checked || false;
        const discountAmount= getEl('cl-rcv-discountAmount')?.value || 0;
        const discountParticular = getEl('cl-rcv-discountParticular')?.value.trim() || '';

        if (!date)                              return alert('Please select a date');
        if (!validateAmount(amount, 'amount'))  return;
        if (!accountId)                         return alert('Please select an account');
        if (!particular)                        return alert('Please enter particular');
        if (withDiscount && (!discountAmount || parseFloat(discountAmount) <= 0))
            return alert('Please enter discount amount');

        const payload = {
            clientId:           CLIENT_ID,
            clientName:         clientName,
            amount,
            particular,
            transactionDate:    buildDateTime(date),
            accountId,
            accountName,
            transferMethod:     method,
            isHistorical:       0,
            selectedSaleIds:    [...selectedSaleIds],
            withDiscount,
            discountAmount:     withDiscount ? discountAmount : 0,
            discountParticular: withDiscount ? discountParticular : ''
        };

        if (method === 'cheque') {
            payload.chequeNo          = getEl('cl-rcv-cheque-no')?.value;
            payload.chequeDate        = getEl('cl-rcv-cheque-date')?.value;
            payload.chequeAccountName = getEl('cl-rcv-cheque-acc')?.value;
            payload.bankName          = getEl('cl-rcv-cheque-bank')?.value;
        } else if (method === 'bftn-eft') {
            payload.bftnAccountName   = getEl('cl-rcv-bftn-acc')?.value;
            payload.eftBankName       = getEl('cl-rcv-bftn-bank')?.value;
            payload.bftnDate          = getEl('cl-rcv-bftn-date')?.value;
        }

        setBtnLoading('cl-rcv-saveBtn', true);
        try {
            const res = await postJSON(API_RECEIVE, payload);
            if (res.success) {
                const msg = res.is_historical   ? 'ঐতিহাসিক entry সংরক্ষিত হয়েছে'
                          : res.is_partial      ? 'Partial payment recorded!'
                          : res.is_discounted   ? 'Payment + Discount recorded!'
                          : 'Payment received successfully!';
                alert(msg);
                closeModal('cl-receiveModal');
                location.reload();
            } else {
                alert(res.message || 'Failed');
            }
        } catch(e) { alert('Network error.'); }
        finally { setBtnLoading('cl-rcv-saveBtn', false); }
    }


    // ==================== SALE MODAL ====================
    function openSaleModal() {
        setEl('cl-sale-clientName', clientName);
        setToday('cl-sale-date');
        getEl('cl-sale-sellingPrice').value = '';
        getEl('cl-sale-purchasePrice').value = '';
        setEl('cl-sale-profit', '0.00');
        getEl('cl-sale-particular').value = '';
        getEl('cl-sale-vendorParticular').value = '';
        getEl('cl-sale-vendorParticularWrapper')?.classList.add('hidden');
        if (typeof vendorInput !== 'undefined' && vendorInput) vendorInput.value = '';
        openModal('cl-saleModal');
    }

    function updateProfitPreview() {
        const sell   = parseFloat(getEl('cl-sale-sellingPrice')?.value) || 0;
        const buy    = parseFloat(getEl('cl-sale-purchasePrice')?.value) || 0;
        const profit = sell - buy;
        const el     = getEl('cl-sale-profit');
        if (el) {
            el.textContent = profit.toFixed(2);
            el.className   = `text-lg font-bold ${profit >= 0 ? 'text-green-700' : 'text-red-700'}`;
        }
    }

    async function submitSale() {
        const date          = getEl('cl-sale-date')?.value;
        const sellingPrice  = getEl('cl-sale-sellingPrice')?.value;
        const purchasePrice = getEl('cl-sale-purchasePrice')?.value;
        const particular    = getEl('cl-sale-particular')?.value.trim();
        const vendorParticular = getEl('cl-sale-vendorParticular')?.value.trim();
        const vendor = (typeof vendorInput !== 'undefined' && vendorInput?.value)
            ? (() => { const p = vendorInput.value.split('|').map(v=>v.trim()); return {sys_id:p[0],name:p[1]}; })()
            : null;

        if (!date)                                     return alert('Please select a date');
        if (!validateAmount(sellingPrice, 'Selling Price')) return;
        if (!particular)                               return alert('Please enter particular for client');

        setBtnLoading('cl-sale-saveBtn', true);
        try {
            const r1 = await postJSON(API_SALE, {
                clientId: CLIENT_ID, clientName, sellingPrice, particular,
                transactionDate: buildDateTime(date)
            });
            if (!r1.success) { alert(r1.message || 'Client sale failed'); return; }

            if (vendor?.sys_id && purchasePrice && parseFloat(purchasePrice) > 0) {
                const r2 = await postJSON(API_PURCHASE, {
                    vendorId: vendor.sys_id, vendorName: vendor.name,
                    purchasePrice,
                    particular: vendorParticular || particular,
                    transactionDate: buildDateTime(date)
                });
                if (!r2.success) {
                    alert(`Client sale saved, কিন্তু Vendor purchase failed: ${r2.message || 'Unknown'}`);
                    closeModal('cl-saleModal'); location.reload(); return;
                }
            }

            alert('Sale recorded successfully!');
            closeModal('cl-saleModal');
            location.reload();
        } catch(e) { alert('Network error.'); }
        finally { setBtnLoading('cl-sale-saveBtn', false); }
    }


    // ==================== REFUND MODAL ====================
    function openRefundModal() {
        setEl('cl-ref-clientName', clientName);
        setToday('cl-ref-date');
        getEl('cl-ref-amount').value = '';
        getEl('cl-ref-particular').value = '';
        getEl('cl-ref-isPhysical').checked = false;
        getEl('cl-ref-physicalSection')?.classList.add('hidden');
        syncAccountsTo('cl-ref-accountSelect');
        openModal('cl-refundModal');
    }

    async function submitRefund() {
        const date       = getEl('cl-ref-date')?.value;
        const amount     = getEl('cl-ref-amount')?.value;
        const particular = getEl('cl-ref-particular')?.value.trim();
        const isPhysical = getEl('cl-ref-isPhysical')?.checked;

        if (!date)                                   return alert('Please select a date');
        if (!validateAmount(amount, 'Refund Amount')) return;
        if (!particular)                             return alert('Please enter reason for refund');

        const payload = { clientId: CLIENT_ID, clientName, amount, particular,
            transactionDate: buildDateTime(date), isPhysical: isPhysical ? 1 : 0 };

        if (isPhysical) {
            const accParts = getEl('cl-ref-accountSelect')?.value?.split('|').map(v=>v.trim()) || [];
            if (!accParts[0]) return alert('Please select an account');
            payload.accountId      = accParts[0];
            payload.accountName    = accParts[1] || '';
            payload.transferMethod = getEl('cl-ref-method')?.value || 'cash';
        }

        setBtnLoading('cl-ref-saveBtn', true);
        try {
            const res = await postJSON(API_REFUND, payload);
            if (res.success) { alert('Refund recorded!'); closeModal('cl-refundModal'); location.reload(); }
            else alert(res.message || 'Failed');
        } catch(e) { alert('Network error.'); }
        finally { setBtnLoading('cl-ref-saveBtn', false); }
    }


    // ==================== DISCOUNT MODAL ====================
    function openDiscountModal() {
        setEl('cl-disc-clientName', clientName);
        setToday('cl-disc-date');
        getEl('cl-disc-amount').value = '';
        getEl('cl-disc-particular').value = '';
        openModal('cl-discountModal');
    }

    async function submitDiscount() {
        const date       = getEl('cl-disc-date')?.value;
        const amount     = getEl('cl-disc-amount')?.value;
        const particular = getEl('cl-disc-particular')?.value.trim();

        if (!date)                                     return alert('Please select a date');
        if (!validateAmount(amount, 'Discount Amount')) return;
        if (!particular)                               return alert('Please enter reason for discount');

        setBtnLoading('cl-disc-saveBtn', true);
        try {
            const res = await postJSON(API_DISCOUNT, {
                clientId: CLIENT_ID, clientName, amount, particular,
                transactionDate: buildDateTime(date)
            });
            if (res.success) { alert('Discount recorded!'); closeModal('cl-discountModal'); location.reload(); }
            else alert(res.message || 'Failed');
        } catch(e) { alert('Network error.'); }
        finally { setBtnLoading('cl-disc-saveBtn', false); }
    }


    // ==================== INIT ====================
    document.addEventListener('DOMContentLoaded', () => {

        // Modal close — addEventListener pattern (inline onclick bug fix)
        document.querySelectorAll('.cl-modal-close').forEach(btn => {
            btn.addEventListener('click', () => closeModal(btn.getAttribute('data-modal')));
        });

        // Floating buttons
        getEl('cl-btn-receive')?.addEventListener('click',  openReceiveModal);
        getEl('cl-btn-sale')?.addEventListener('click',     openSaleModal);
        getEl('cl-btn-refund')?.addEventListener('click',   openRefundModal);
        getEl('cl-btn-discount')?.addEventListener('click', openDiscountModal);
        getEl('cl-addTrnxCard')?.addEventListener('click',  openReceiveModal);

        // Receive modal: amount input → update UI
        getEl('cl-rcv-amount')?.addEventListener('input', updateReceiveUI);

        // Receive: Select All
        getEl('cl-rcv-selectAll')?.addEventListener('click', () => {
            document.querySelectorAll('.sale-checkbox').forEach(cb => {
                if (!cb.checked) {
                    cb.checked = true;
                    cb.dispatchEvent(new Event('change'));
                }
            });
        });

        // Receive: method toggle
        getEl('cl-rcv-method')?.addEventListener('change', function() {
            getEl('cl-rcv-cheque')?.classList.toggle('hidden', this.value !== 'cheque');
            getEl('cl-rcv-bftn')?.classList.toggle('hidden',   this.value !== 'bftn-eft');
        });

        // Receive: account → opening date
        getEl('cl-rcv-accountSelect')?.addEventListener('change', async function() {
            const parts = this.value.split('|').map(v => v.trim());
            if (!parts[0]) return;
            try {
                const r   = await fetch(`${FETCH_STMT}?ledger_db_id=${parts[0]}&opening_only=1`);
                const res = await r.json();
                if (res.success && res.data?.length > 0) {
                    setEl('cl-rcv-openingDateText', `Opening Balance: ${res.data[0].date?.split(' ')[0]}`);
                    getEl('cl-rcv-openingDateInfo')?.classList.remove('hidden');
                }
            } catch(e) {}
        });

        // Discount-to-close checkbox
        getEl('cl-rcv-withDiscount')?.addEventListener('change', function() {
            getEl('cl-rcv-discountFields')?.classList.toggle('hidden', !this.checked);
            if (this.checked) {
                // Auto-fill discount amount = remaining
                const selectedTotal = getSelectedTotal();
                const receiveAmt    = parseFloat(getEl('cl-rcv-amount')?.value) || 0;
                const remaining     = selectedTotal - receiveAmt;
                if (remaining > 0) getEl('cl-rcv-discountAmount').value = remaining.toFixed(2);
            }
        });

        // Refund: physical toggle
        getEl('cl-ref-isPhysical')?.addEventListener('change', function() {
            getEl('cl-ref-physicalSection')?.classList.toggle('hidden', !this.checked);
        });

        // Sale: profit preview
        getEl('cl-sale-sellingPrice')?.addEventListener('input',  updateProfitPreview);
        getEl('cl-sale-purchasePrice')?.addEventListener('input', updateProfitPreview);

        // Sale: vendor particular show/hide
        if (typeof vendorInput !== 'undefined' && vendorInput) {
            vendorInput.addEventListener('change', () => {
                getEl('cl-sale-vendorParticularWrapper')?.classList.toggle('hidden', !vendorInput.value);
            });
        }

        // Save buttons
        getEl('cl-rcv-saveBtn')?.addEventListener('click',  submitReceive);
        getEl('cl-sale-saveBtn')?.addEventListener('click', submitSale);
        getEl('cl-ref-saveBtn')?.addEventListener('click',  submitRefund);
        getEl('cl-disc-saveBtn')?.addEventListener('click', submitDiscount);

        // Table controls
        getEl('cl-searchInput')?.addEventListener('input', filterTransactions);
        getEl('cl-searchInput')?.addEventListener('keyup', e => { if (e.key === 'Escape') resetPagination(); });
        getEl('cl-filterType')?.addEventListener('change', filterTransactions);
        getEl('cl-resetFilters')?.addEventListener('click', () => {
            getEl('cl-searchInput') && (getEl('cl-searchInput').value = '');
            getEl('cl-filterType') && (getEl('cl-filterType').value = 'all');
            resetPagination();
        });
        getEl('cl-loadMoreBtn')?.addEventListener('click', loadMoreTransactions);

        document.addEventListener('keydown', e => {
            if (e.ctrlKey && e.key === 'f') { e.preventDefault(); getEl('cl-searchInput')?.focus(); }
        });

        reloadFinancialTable();
    });

})();
</script>