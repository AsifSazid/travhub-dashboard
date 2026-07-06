<?php
include_once('./authenticate.php');
$ip_port = trim(@file_get_contents('../ippath.txt') ?: 'http://103.104.219.3:898', '/');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Make Payment — TravHub</title>
    <link rel="icon" type="image/png" href="../assets/images/logo/round-logo.png">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body class="bg-gray-50">
<?php include '../elements/header.php'; ?>
<?php include '../elements/aside.php'; ?>
<?php include '../elements/preview-model.php'; ?>

<main id="mainContent" class="pt-16 pl-64 transition-all duration-300">
<div class="p-6 max-w-5xl mx-auto">

    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900">
            <i class="fas fa-arrow-up text-red-500 mr-2"></i>Make Payment
        </h1>
        <p class="text-gray-500 text-sm mt-1">Vendor কে payment করুন</p>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        <!-- LEFT FORM -->
        <div class="lg:col-span-2 space-y-4">

            <!-- WAY SELECT -->
            <div class="bg-white rounded-xl border border-gray-200 p-4">
                <p class="text-xs font-semibold text-gray-500 mb-3">PAYMENT VIA</p>
                <div class="grid grid-cols-2 gap-3">
                    <button onclick="setWay('vendor')" id="way-vendor"
                        class="way-btn py-2.5 px-4 rounded-xl border-2 border-red-500 bg-red-50 text-red-700 text-sm font-semibold transition-colors">
                        <i class="fas fa-building mr-2"></i>Vendor Select
                    </button>
                    <button onclick="setWay('invoice')" id="way-invoice"
                        class="way-btn py-2.5 px-4 rounded-xl border-2 border-gray-200 text-gray-500 text-sm font-semibold hover:border-red-400 transition-colors">
                        <i class="fas fa-file-invoice mr-2"></i>Invoice Select
                    </button>
                </div>
            </div>

            <!-- VENDOR WAY -->
            <div id="panel-vendor" class="bg-white rounded-xl border border-gray-200 p-5">
                <h3 class="text-sm font-semibold text-gray-700 mb-3">
                    <i class="fas fa-building text-red-500 mr-1"></i> Vendor
                </h3>
                <div class="relative">
                    <input type="text" id="vendorSearch" placeholder="Vendor name বা ID search..."
                        class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-red-400 pr-10" autocomplete="off">
                    <button id="vendorResetBtn" onclick="resetVendor()" class="hidden absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-red-500">
                        <i class="fas fa-times-circle"></i>
                    </button>
                    <div id="vendorDropdown" class="absolute z-30 w-full bg-white border border-gray-200 rounded-lg shadow-lg hidden max-h-52 overflow-y-auto mt-1"></div>
                </div>
                <div id="vendorInfo" class="hidden mt-3 p-3 bg-red-50 rounded-lg text-sm flex items-center justify-between gap-2">
                    <div class="flex items-center gap-2 text-red-800">
                        <i class="fas fa-building text-red-400"></i>
                        <span id="vendorInfoText"></span>
                    </div>
                    <button onclick="resetVendor()" class="text-xs text-red-400 hover:text-red-600 flex-shrink-0">
                        <i class="fas fa-times mr-1"></i>Reset
                    </button>
                </div>
            </div>

            <!-- INVOICE WAY -->
            <div id="panel-invoice" class="hidden bg-white rounded-xl border border-gray-200 p-5">
                <h3 class="text-sm font-semibold text-gray-700 mb-3">
                    <i class="fas fa-file-invoice text-red-500 mr-1"></i> Invoice Search
                </h3>
                <div class="relative">
                    <input type="text" id="invoiceSearch" placeholder="Invoice no বা Vendor name search..."
                        class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-red-400 pr-10" autocomplete="off">
                    <button id="invoiceResetBtn" onclick="resetInvoice()" class="hidden absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-red-500">
                        <i class="fas fa-times-circle"></i>
                    </button>
                    <div id="invoiceDropdown" class="absolute z-30 w-full bg-white border border-gray-200 rounded-lg shadow-lg hidden max-h-52 overflow-y-auto mt-1"></div>
                </div>
                <div id="invoiceInfo" class="hidden mt-3 p-3 bg-red-50 rounded-lg text-sm flex items-center justify-between gap-2">
                    <div class="flex items-center gap-2 text-red-800">
                        <i class="fas fa-file-invoice text-red-400"></i>
                        <span id="invoiceInfoText"></span>
                    </div>
                    <button onclick="resetInvoice()" class="text-xs text-red-400 hover:text-red-600 flex-shrink-0">
                        <i class="fas fa-times mr-1"></i>Reset
                    </button>
                </div>
            </div>

            <!-- SELECTION MODE — vendor way -->
            <div id="selectionPanel" class="hidden bg-white rounded-xl border border-gray-200 p-5">
                <div class="flex gap-2 mb-4">
                    <button onclick="setMode('purchase')" id="mode-purchase"
                        class="mode-btn flex-1 py-1.5 px-2 rounded-lg text-xs font-semibold border-2 border-red-500 bg-red-50 text-red-700">
                        <i class="fas fa-list mr-1"></i> Purchase Entries
                    </button>
                    <button onclick="setMode('advance')" id="mode-advance"
                        class="mode-btn flex-1 py-1.5 px-2 rounded-lg text-xs font-semibold border-2 border-gray-200 text-gray-500">
                        <i class="fas fa-piggy-bank mr-1"></i> Vendor Advance
                    </button>
                </div>

                <!-- Purchase list -->
                <div id="purchase-panel">
                    <div class="flex justify-between items-center mb-2">
                        <span class="text-xs text-gray-500">Unpaid / Partial Purchases</span>
                        <button onclick="selectAllPurchases()" class="text-xs text-red-500 hover:underline">Select All</button>
                    </div>
                    <div id="purchaseList" class="space-y-2 max-h-56 overflow-y-auto">
                        <p class="text-xs text-gray-400 text-center py-4">Vendor select করুন</p>
                    </div>
                    <div class="flex justify-between text-xs font-semibold text-gray-600 border-t pt-2 mt-2">
                        <span>Selected Remaining:</span>
                        <span id="purchaseSelectedTotal" class="text-red-600">৳0.00</span>
                    </div>
                </div>

                <!-- Advance only -->
                <div id="advance-panel" class="hidden">
                    <div class="p-3 bg-indigo-50 border border-indigo-200 rounded-lg text-xs text-indigo-700">
                        <i class="fas fa-piggy-bank mr-1"></i>
                        Vendor কে <strong>Advance</strong> হিসেবে দেওয়া হবে। পরে purchase clear করতে ব্যবহার করা যাবে।
                    </div>
                </div>

                <!-- Vendor Advance use (purchase mode এ) -->
                <div id="vendorAdvanceSection" class="hidden mt-3 bg-indigo-50 border border-indigo-200 rounded-lg p-3">
                    <div class="flex items-center justify-between mb-2">
                        <div>
                            <p class="text-xs font-semibold text-indigo-700"><i class="fas fa-piggy-bank mr-1"></i> Vendor Advance Balance</p>
                            <p class="text-xs text-indigo-500">Available: <strong id="vendorAdvBalance">৳0.00</strong></p>
                        </div>
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" id="useVendorAdvance" class="w-4 h-4 text-indigo-600 rounded" onchange="toggleVendorAdvanceUse()">
                            <span class="text-xs font-semibold text-indigo-700">Use Advance</span>
                        </label>
                    </div>
                    <div id="vendorAdvanceInputWrap" class="hidden">
                        <label class="text-xs text-indigo-600">Use Amount (max: <span id="vendorAdvMax">৳0</span>)</label>
                        <input type="number" id="vendorAdvanceAmount" step="0.01" min="0" placeholder="0.00"
                            class="w-full px-3 py-2 border border-indigo-300 rounded-lg text-sm mt-1 focus:ring-2 focus:ring-indigo-400">
                    </div>
                </div>
            </div>

            <!-- Invoice linked purchases -->
            <div id="invoicePurchasePanel" class="hidden bg-white rounded-xl border border-gray-200 p-5">
                <div class="flex justify-between items-center mb-2">
                    <span class="text-xs font-semibold text-gray-600">Invoice Items</span>
                    <span id="invoiceDueBadge" class="text-xs bg-red-100 text-red-700 px-2 py-0.5 rounded-full font-semibold"></span>
                </div>
                <div id="invoicePurchaseList" class="space-y-2 max-h-52 overflow-y-auto">
                    <p class="text-xs text-gray-400 text-center py-3">Invoice select করুন</p>
                </div>
            </div>

            <!-- AMOUNT & PAYMENT -->
            <div class="bg-white rounded-xl border border-gray-200 p-5 space-y-4">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Payment Amount ৳</label>
                        <input type="number" id="paymentAmount" step="0.01" min="0" placeholder="0.00"
                            class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-red-400 font-semibold">
                        <p id="amountHint" class="text-xs text-orange-500 mt-1 hidden"></p>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Date</label>
                        <input type="date" id="paymentDate" value="<?php echo date('Y-m-d'); ?>"
                            class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-red-400">
                    </div>
                </div>

                <!-- Payment Method -->
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-2">Payment Method</label>
                    <div class="grid grid-cols-5 gap-1.5">
                        <label class="method-label flex flex-col items-center p-2 border-2 border-red-500 bg-red-50 text-red-700 rounded-lg cursor-pointer" data-val="cash">
                            <input type="radio" name="payMethod" value="cash" class="hidden" checked>
                            <i class="fas fa-money-bill-wave text-lg mb-0.5"></i>
                            <span class="text-xs font-semibold">Cash</span>
                        </label>
                        <label class="method-label flex flex-col items-center p-2 border-2 border-gray-200 text-gray-500 rounded-lg cursor-pointer" data-val="mfs">
                            <input type="radio" name="payMethod" value="mfs" class="hidden">
                            <i class="fas fa-mobile-alt text-lg mb-0.5"></i>
                            <span class="text-xs font-semibold">MFS</span>
                        </label>
                        <label class="method-label flex flex-col items-center p-2 border-2 border-gray-200 text-gray-500 rounded-lg cursor-pointer" data-val="npsb">
                            <input type="radio" name="payMethod" value="npsb" class="hidden">
                            <i class="fas fa-network-wired text-lg mb-0.5"></i>
                            <span class="text-xs font-semibold">NPSB</span>
                        </label>
                        <label class="method-label flex flex-col items-center p-2 border-2 border-gray-200 text-gray-500 rounded-lg cursor-pointer" data-val="cheque">
                            <input type="radio" name="payMethod" value="cheque" class="hidden">
                            <i class="fas fa-file-alt text-lg mb-0.5"></i>
                            <span class="text-xs font-semibold">Cheque</span>
                        </label>
                        <label class="method-label flex flex-col items-center p-2 border-2 border-gray-200 text-gray-500 rounded-lg cursor-pointer" data-val="bftn-eft">
                            <input type="radio" name="payMethod" value="bftn-eft" class="hidden">
                            <i class="fas fa-university text-lg mb-0.5"></i>
                            <span class="text-xs font-semibold">BFTN</span>
                        </label>
                    </div>
                </div>

                <div id="instrumentWarn" class="hidden text-xs text-yellow-700 bg-yellow-50 border border-yellow-200 rounded-lg p-2">
                    <i class="fas fa-clock mr-1"></i> Instrument pending — bank balance এখনই update হবে না
                </div>

                <!-- Withdraw Account -->
                <div id="accountSection">
                    <label class="block text-xs font-medium text-gray-600 mb-1">Withdraw From Account</label>
                    <select id="withdrawAccount" class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-red-400">
                        <option value="">-- Select Account --</option>
                    </select>
                </div>

                <!-- Cheque Fields -->
                <div id="chequeFields" class="hidden bg-yellow-50 border border-yellow-200 rounded-lg p-3 grid grid-cols-2 gap-3">
                    <div><label class="text-xs text-gray-600">Cheque No</label>
                        <input type="text" id="chequeNo" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm mt-1"></div>
                    <div><label class="text-xs text-gray-600">Cheque Date</label>
                        <input type="date" id="chequeDate" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm mt-1"></div>
                    <div><label class="text-xs text-gray-600">Account Name</label>
                        <input type="text" id="chequeAccountName" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm mt-1"></div>
                    <div><label class="text-xs text-gray-600">Bank Name</label>
                        <input type="text" id="chequeBankName" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm mt-1"></div>
                </div>

                <!-- BFTN Fields -->
                <div id="bftnFields" class="hidden bg-blue-50 border border-blue-200 rounded-lg p-3 grid grid-cols-2 gap-3">
                    <div><label class="text-xs text-gray-600">Reference No</label>
                        <input type="text" id="bftnNo" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm mt-1"></div>
                    <div><label class="text-xs text-gray-600">Date</label>
                        <input type="date" id="bftnDate" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm mt-1"></div>
                    <div><label class="text-xs text-gray-600">Account Name</label>
                        <input type="text" id="bftnAccountName" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm mt-1"></div>
                    <div><label class="text-xs text-gray-600">Bank Name</label>
                        <input type="text" id="bftnBankName" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm mt-1"></div>
                </div>

                <!-- Discount -->
                <div class="bg-orange-50 border border-orange-200 rounded-lg p-3">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" id="withDiscount" class="w-4 h-4 text-orange-500 rounded"
                            onchange="document.getElementById('discountFields').classList.toggle('hidden',!this.checked)">
                        <span class="text-xs font-semibold text-orange-700">Vendor Discount দিয়ে close করবো</span>
                    </label>
                    <div id="discountFields" class="hidden grid grid-cols-2 gap-3 mt-2">
                        <div><label class="text-xs text-gray-600">Discount Amount</label>
                            <input type="number" id="discountAmount" step="0.01" placeholder="0.00"
                                class="w-full px-3 py-2 border border-orange-300 rounded-lg text-sm mt-1" oninput="updateSummary()"></div>
                        <div><label class="text-xs text-gray-600">Reason</label>
                            <input type="text" id="discountParticular" placeholder="Reason"
                                class="w-full px-3 py-2 border border-orange-300 rounded-lg text-sm mt-1"></div>
                    </div>
                </div>

                <!-- File Upload -->
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">
                        <i class="fas fa-paperclip mr-1"></i> Attach Files (optional)
                    </label>
                    <input type="file" id="paymentFiles" multiple accept=".pdf,.jpg,.jpeg,.png,.doc,.docx"
                        class="w-full text-xs text-gray-600 file:mr-2 file:py-1 file:px-3 file:rounded file:border-0 file:text-xs file:bg-red-50 file:text-red-700 hover:file:bg-red-100">
                    <p class="text-xs text-gray-400 mt-1">Multiple files. Max 10MB each.</p>
                </div>

                <!-- Particular -->
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Particular</label>
                    <textarea id="particular" rows="2" placeholder="Payment description..."
                        class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-red-400"></textarea>
                </div>
            </div>

            <button onclick="submitPayment()" id="submitBtn"
                class="w-full py-3.5 bg-red-600 hover:bg-red-700 text-white font-bold rounded-xl transition-colors flex items-center justify-center gap-2 text-base">
                <i class="fas fa-paper-plane"></i> Save Payment
            </button>
        </div>

        <!-- RIGHT SUMMARY -->
        <div class="space-y-4">
            <div class="bg-white rounded-xl border border-gray-200 p-5">
                <h3 class="text-sm font-semibold text-gray-700 mb-4">
                    <i class="fas fa-receipt mr-1 text-red-500"></i> Summary
                </h3>
                <div class="space-y-3 text-sm">
                    <div class="flex justify-between">
                        <span class="text-gray-400">Via</span>
                        <span id="sumWay" class="font-medium">Vendor Select</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-400">Vendor</span>
                        <span id="sumVendor" class="font-medium text-right max-w-[150px] truncate">—</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-400">Mode</span>
                        <span id="sumMode" class="font-medium">Purchase Entries</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-400">Method</span>
                        <span id="sumMethod" class="font-medium">Cash</span>
                    </div>
                    <div class="border-t pt-3 flex justify-between">
                        <span class="font-semibold text-gray-700">Amount</span>
                        <span id="sumAmount" class="font-bold text-red-600 text-xl">৳0.00</span>
                    </div>
                    <div id="sumAdvanceUsedRow" class="hidden flex justify-between text-xs">
                        <span class="text-indigo-600">Vendor Advance Used</span>
                        <span id="sumAdvanceUsed" class="text-indigo-600 font-medium">৳0.00</span>
                    </div>
                    <div id="sumOverpayWrap" class="hidden p-2 bg-orange-50 rounded-lg">
                        <p class="text-xs text-orange-600 font-medium" id="sumOverpay"></p>
                    </div>
                    <div id="sumAdvanceWrap" class="hidden p-2 bg-indigo-50 rounded-lg">
                        <p class="text-xs text-indigo-600">
                            <i class="fas fa-piggy-bank mr-1"></i> Vendor Advance হিসেবে দেওয়া হবে
                        </p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-xl border border-gray-200 p-5">
                <h3 class="text-sm font-semibold text-gray-700 mb-3">
                    <i class="fas fa-history mr-1 text-gray-400"></i> Recent
                </h3>
                <div id="recentList" class="space-y-2">
                    <p class="text-xs text-gray-400 text-center py-2">No recent entries</p>
                </div>
            </div>
        </div>
    </div>
</div>
</main>

<script>
const IP          = '<?php echo $ip_port; ?>';
const PAYMENT_API = `${IP}/api/vendors/ve-ac-payment-store.php`;
const FIN_API     = `${IP}/api/financial_entries/fin-entries.php`;
const INV_API     = `${IP}/api/invoices/all-invoices.php`;
const ACC_API     = `${IP}/api/accounts/all-accounts.php`;
const VENDOR_API  = `${IP}/api/vendors/all-vendors.php`;
const UPLOAD_API  = `${IP}/api/finance/upload-file.php`;

let currentWay       = 'vendor';
let currentMode      = 'purchase';
let selectedVendor   = null;
let selectedInvoice  = null;
let selectedPurchaseIds = new Set();
let purchaseEntries  = [];
let vendors          = [];
let vendorAdvBalance = 0;

document.addEventListener('DOMContentLoaded', () => {
    loadAccounts();
    setupMethodToggle();
    setupVendorSearch();
    setupInvoiceSearch();
    document.getElementById('paymentAmount').addEventListener('input', updateSummary);
    setWay('vendor');
});

async function loadAccounts() {
    const r = await fetch(ACC_API);
    const d = await r.json();
    const sel = document.getElementById('withdrawAccount');
    (d.accounts||[]).forEach(a => {
        const o = document.createElement('option');
        o.value = a.sys_id; o.dataset.name = a.acc_name; o.textContent = a.acc_name;
        sel.appendChild(o);
    });
}

/* WAY */
function setWay(way) {
    currentWay = way;
    ['vendor','invoice'].forEach(w => {
        const btn = document.getElementById(`way-${w}`);
        const panel = document.getElementById(`panel-${w}`);
        if (w === way) {
            btn?.classList.add('border-red-500','bg-red-50','text-red-700');
            btn?.classList.remove('border-gray-200','text-gray-500');
            panel?.classList.remove('hidden');
        } else {
            btn?.classList.remove('border-red-500','bg-red-50','text-red-700');
            btn?.classList.add('border-gray-200','text-gray-500');
            panel?.classList.add('hidden');
        }
    });
    document.getElementById('selectionPanel')?.classList.toggle('hidden', way!=='vendor');
    document.getElementById('invoicePurchasePanel')?.classList.toggle('hidden', way!=='invoice');
    document.getElementById('sumWay').textContent = way==='invoice' ? 'Invoice Select' : 'Vendor Select';
    if (way==='invoice') resetVendor();
    if (way==='vendor') resetInvoice();
    updateSummary();
}

/* MODE */
function setMode(mode) {
    currentMode = mode;
    ['purchase','advance'].forEach(m => {
        const btn = document.getElementById(`mode-${m}`);
        const panel = document.getElementById(`${m}-panel`);
        if (m === mode) {
            btn?.classList.add('border-red-500','bg-red-50','text-red-700');
            btn?.classList.remove('border-gray-200','text-gray-500');
            panel?.classList.remove('hidden');
        } else {
            btn?.classList.remove('border-red-500','bg-red-50','text-red-700');
            btn?.classList.add('border-gray-200','text-gray-500');
            panel?.classList.add('hidden');
        }
    });
    const modeNames = { purchase:'Purchase Entries', advance:'Vendor Advance' };
    document.getElementById('sumMode').textContent = modeNames[mode];
    document.getElementById('sumAdvanceWrap')?.classList.toggle('hidden', mode!=='advance');
    if (mode==='purchase' && selectedVendor) loadPurchaseEntries();
    updateSummary();
}

/* VENDOR SEARCH */
function setupVendorSearch() {
    const input = document.getElementById('vendorSearch');
    const dd    = document.getElementById('vendorDropdown');
    fetch(VENDOR_API).then(r=>r.json()).then(d=>{ vendors = d.vendors||[]; });
    input.addEventListener('input', () => {
        const q = input.value.trim().toLowerCase();
        dd.innerHTML = '';
        if (!q) { dd.classList.add('hidden'); return; }
        const hits = vendors.filter(v =>
            (v.name||'').toLowerCase().includes(q)||(v.sys_id||'').toLowerCase().includes(q)
        ).slice(0,8);
        if (!hits.length) { dd.classList.add('hidden'); return; }
        hits.forEach(v => {
            const div = document.createElement('div');
            div.className = 'px-4 py-2.5 hover:bg-gray-50 cursor-pointer text-sm border-b last:border-0';
            div.innerHTML = `<div class="font-medium">${v.name}</div><div class="text-xs text-gray-400">${v.sys_id}</div>`;
            div.onclick = () => selectVendor(v);
            dd.appendChild(div);
        });
        dd.classList.remove('hidden');
    });
    document.addEventListener('click', e => {
        if (!input.contains(e.target)&&!dd.contains(e.target)) dd.classList.add('hidden');
    });
}

async function selectVendor(v) {
    selectedVendor = v;
    document.getElementById('vendorSearch').value = v.name;
    document.getElementById('vendorDropdown').classList.add('hidden');
    let phone = '';
    try {
        const ph = typeof v.phone==='string' ? JSON.parse(v.phone) : v.phone;
        phone = ph?.primary_no || ph?.primary || '';
    } catch(e) { phone = v.phone||''; }
    document.getElementById('vendorInfoText').innerHTML =
        `<span class="font-semibold">${v.name}</span> · <span class="text-red-600">${v.sys_id}</span>`
        + (phone ? ` · <span>${phone}</span>` : '');
    document.getElementById('vendorInfo').classList.remove('hidden');
    document.getElementById('vendorResetBtn').classList.remove('hidden');
    document.getElementById('selectionPanel').classList.remove('hidden');
    document.getElementById('sumVendor').textContent = v.name;
    if (currentMode==='purchase') loadPurchaseEntries();
    await loadVendorAdvance();
    updateSummary();
}

function resetVendor() {
    selectedVendor = null;
    selectedPurchaseIds.clear();
    purchaseEntries = [];
    vendorAdvBalance = 0;
    document.getElementById('vendorSearch').value = '';
    document.getElementById('vendorInfo').classList.add('hidden');
    document.getElementById('vendorResetBtn').classList.add('hidden');
    document.getElementById('selectionPanel').classList.add('hidden');
    document.getElementById('vendorAdvanceSection')?.classList.add('hidden');
    document.getElementById('purchaseList').innerHTML = '<p class="text-xs text-gray-400 text-center py-4">Vendor select করুন</p>';
    document.getElementById('purchaseSelectedTotal').textContent = '৳0.00';
    document.getElementById('sumVendor').textContent = '—';
    document.getElementById('paymentAmount').value = '';
    updateSummary();
}

/* INVOICE SEARCH */
function setupInvoiceSearch() {
    const input = document.getElementById('invoiceSearch');
    const dd    = document.getElementById('invoiceDropdown');
    let invoices = [];
    input.addEventListener('focus', async () => {
        if (!invoices.length) {
            const r = await fetch(`${INV_API}?unpaid_only=1`);
            const d = await r.json();
            invoices = d.invoices||[];
        }
    });
    input.addEventListener('input', () => {
        const q = input.value.trim().toLowerCase();
        dd.innerHTML = '';
        if (!q) { dd.classList.add('hidden'); return; }
        const hits = invoices.filter(i =>
            (i.invoice_no||'').toLowerCase().includes(q)||(i.client_name||'').toLowerCase().includes(q)
        ).slice(0,8);
        if (!hits.length) { dd.classList.add('hidden'); return; }
        hits.forEach(inv => {
            const div = document.createElement('div');
            div.className = 'px-4 py-2.5 hover:bg-gray-50 cursor-pointer text-sm border-b last:border-0';
            div.innerHTML = `
                <div class="flex justify-between">
                    <span class="font-medium">${inv.invoice_no}</span>
                    <span class="text-red-600 font-semibold">৳${parseFloat(inv.due_amount).toFixed(2)}</span>
                </div>
                <div class="text-xs text-gray-400">${inv.client_name} · ${inv.invoice_date}</div>`;
            div.onclick = () => selectInvoice(inv);
            dd.appendChild(div);
        });
        dd.classList.remove('hidden');
    });
    document.addEventListener('click', e => {
        if (!input.contains(e.target)&&!dd.contains(e.target)) dd.classList.add('hidden');
    });
}

function selectInvoice(inv) {
    selectedInvoice = inv;
    document.getElementById('invoiceSearch').value = inv.invoice_no;
    document.getElementById('invoiceDropdown').classList.add('hidden');
    document.getElementById('invoiceInfoText').textContent =
        `${inv.invoice_no} · ${inv.client_name} · Due ৳${parseFloat(inv.due_amount).toFixed(2)}`;
    document.getElementById('invoiceInfo').classList.remove('hidden');
    document.getElementById('invoiceResetBtn').classList.remove('hidden');
    document.getElementById('sumVendor').textContent = inv.client_name;
    document.getElementById('invoiceDueBadge').textContent = `Due ৳${parseFloat(inv.due_amount).toFixed(2)}`;
    document.getElementById('paymentAmount').value = parseFloat(inv.due_amount).toFixed(2);
    // Show items
    const listEl = document.getElementById('invoicePurchaseList');
    const items  = inv.items||[];
    listEl.innerHTML = items.length
        ? items.map(e=>`<div class="flex justify-between items-center p-2 bg-gray-50 border border-gray-200 rounded-lg">
            <div class="text-xs"><div class="font-medium text-gray-800">${e.description||'Service'}</div>
            <div class="text-gray-400">Qty: ${e.quantity} · Rate: ৳${e.unit_price}</div></div>
            <div class="text-sm font-bold text-gray-700">৳${e.total.toFixed(2)}</div>
        </div>`).join('')
        : '<p class="text-xs text-gray-400 text-center py-2">No items</p>';
    updateSummary();
}

function resetInvoice() {
    selectedInvoice = null;
    document.getElementById('invoiceSearch').value = '';
    document.getElementById('invoiceInfo').classList.add('hidden');
    document.getElementById('invoiceResetBtn').classList.add('hidden');
    document.getElementById('invoicePurchaseList').innerHTML = '<p class="text-xs text-gray-400 text-center py-3">Invoice select করুন</p>';
    document.getElementById('invoiceDueBadge').textContent = '';
    document.getElementById('paymentAmount').value = '';
    document.getElementById('sumVendor').textContent = '—';
    updateSummary();
}

/* PURCHASE ENTRIES */
async function loadPurchaseEntries() {
    if (!selectedVendor) return;
    const listEl = document.getElementById('purchaseList');
    listEl.innerHTML = '<p class="text-xs text-gray-400 text-center py-3">Loading...</p>';
    const r = await fetch(`${FIN_API}?id=${selectedVendor.sys_id}&type=credit&related_type=2&is_paid=0`);
    const d = await r.json();
    purchaseEntries = (d.finStmts||[]).filter(e =>
        (e.type||'').toLowerCase()==='credit' && parseInt(e.related_type)===2 && parseInt(e.is_paid||0)===0
    );
    selectedPurchaseIds.clear();
    renderPurchaseList();
}

function renderPurchaseList() {
    const el = document.getElementById('purchaseList');
    if (!purchaseEntries.length) {
        el.innerHTML = '<p class="text-xs text-gray-400 text-center py-4">কোনো unpaid purchase নেই</p>';
        updatePurchaseTotal(); return;
    }
    el.innerHTML = purchaseEntries.map(e => {
        const amt = parseFloat(e.amount)||0;
        const rem = parseFloat(e.remaining_amount??amt);
        const pd  = parseFloat(e.paid_amount||0);
        const pct = amt>0?Math.min((pd/amt)*100,100):0;
        const chk = selectedPurchaseIds.has(e.sys_id);
        const pb  = e.is_partial==1?`<span class="text-xs bg-blue-100 text-blue-700 px-1 py-0.5 rounded ml-1">Partial</span>`:'';
        const bar = pd>0?`<div class="w-full bg-gray-200 rounded-full h-1 mt-1"><div class="bg-orange-500 h-1 rounded-full" style="width:${pct}%"></div></div>
            <p class="text-xs text-gray-400 mt-0.5">Paid: ${pd.toFixed(2)} · Rem: <b class="text-orange-600">${rem.toFixed(2)}</b></p>`:''
        ;
        return `<div class="flex items-start gap-2 p-2 border-2 rounded-lg cursor-pointer transition-colors
            ${chk?'border-red-400 bg-red-50':'border-gray-200 hover:border-gray-300'}"
            onclick="togglePurchase('${e.sys_id}')">
            <input type="checkbox" class="mt-0.5 flex-shrink-0 w-4 h-4" ${chk?'checked':''} onclick="event.stopPropagation();togglePurchase('${e.sys_id}')">
            <div class="flex-1 min-w-0">
                <div class="text-xs font-medium text-gray-800 truncate">${e.purpose||'N/A'}${pb}</div>
                <div class="text-xs text-gray-400">${(e.date||'').substring(0,10)}${e.work_title?' · '+e.work_title:''}</div>
                ${bar}
            </div>
            <div class="text-sm font-bold text-green-600 flex-shrink-0">৳${rem.toFixed(2)}</div>
        </div>`;
    }).join('');
    updatePurchaseTotal();
}

function togglePurchase(id) {
    if (selectedPurchaseIds.has(id)) selectedPurchaseIds.delete(id);
    else selectedPurchaseIds.add(id);
    renderPurchaseList();
}

function selectAllPurchases() {
    if (selectedPurchaseIds.size===purchaseEntries.length) selectedPurchaseIds.clear();
    else purchaseEntries.forEach(e=>selectedPurchaseIds.add(e.sys_id));
    renderPurchaseList();
}

function updatePurchaseTotal() {
    let total = 0;
    purchaseEntries.forEach(e=>{ if(selectedPurchaseIds.has(e.sys_id)) total+=parseFloat(e.remaining_amount??e.amount)||0; });
    document.getElementById('purchaseSelectedTotal').textContent = '৳'+total.toFixed(2);
    const amtEl = document.getElementById('paymentAmount');
    if (total>0 && !amtEl.value) amtEl.value = total.toFixed(2);
    updateSummary();
}

/* VENDOR ADVANCE */
async function loadVendorAdvance() {
    if (!selectedVendor) return;
    try {
        const [drRes, crRes] = await Promise.all([
            fetch(`${FIN_API}?id=${selectedVendor.sys_id}&type=debit&related_type=6`),
            fetch(`${FIN_API}?id=${selectedVendor.sys_id}&type=credit&related_type=6`)
        ]);
        const drData = await drRes.json();
        const crData = await crRes.json();
        const totalOut  = (drData.finStmts||[]).reduce((s,e)=>s+parseFloat(e.amount||0),0);
        const totalBack = (crData.finStmts||[]).reduce((s,e)=>s+parseFloat(e.amount||0),0);
        vendorAdvBalance = Math.max(0, totalOut - totalBack);
        if (vendorAdvBalance > 0.01) {
            document.getElementById('vendorAdvanceSection')?.classList.remove('hidden');
            document.getElementById('vendorAdvBalance').textContent = '৳'+vendorAdvBalance.toFixed(2);
        }
    } catch(e) {}
}

function toggleVendorAdvanceUse() {
    const cb   = document.getElementById('useVendorAdvance');
    const wrap = document.getElementById('vendorAdvanceInputWrap');
    wrap?.classList.toggle('hidden', !cb?.checked);
    if (cb?.checked) {
        const payAmt = parseFloat(document.getElementById('paymentAmount').value)||0;
        const maxUse = Math.min(vendorAdvBalance, payAmt||vendorAdvBalance);
        document.getElementById('vendorAdvMax').textContent = '৳'+maxUse.toFixed(2);
        const inp = document.getElementById('vendorAdvanceAmount');
        if (inp && !inp.value) inp.value = maxUse.toFixed(2);
    }
    updateSummary();
}

/* METHOD */
function setupMethodToggle() {
    document.querySelectorAll('.method-label').forEach(label => {
        label.addEventListener('click', () => {
            document.querySelectorAll('.method-label').forEach(l => {
                l.className = l.className.replace('border-red-500 bg-red-50 text-red-700','border-gray-200 text-gray-500');
            });
            label.className = label.className.replace('border-gray-200 text-gray-500','border-red-500 bg-red-50 text-red-700');
            const method = label.dataset.val;
            const isInstr = ['cheque','bftn-eft'].includes(method);
            document.getElementById('accountSection').classList.toggle('hidden', isInstr);
            document.getElementById('chequeFields').classList.toggle('hidden', method!=='cheque');
            document.getElementById('bftnFields').classList.toggle('hidden', method!=='bftn-eft');
            document.getElementById('instrumentWarn').classList.toggle('hidden', !isInstr);
            document.getElementById('sumMethod').textContent = label.querySelector('span').textContent;
            updateSummary();
        });
    });
}

function getMethod() { return document.querySelector('input[name="payMethod"]:checked')?.value||'cash'; }

/* SUMMARY */
function updateSummary() {
    const amt = parseFloat(document.getElementById('paymentAmount').value)||0;
    document.getElementById('sumAmount').textContent = '৳'+amt.toFixed(2);

    // Vendor advance used
    const useAdv = document.getElementById('useVendorAdvance')?.checked||false;
    const advAmt = useAdv ? (parseFloat(document.getElementById('vendorAdvanceAmount')?.value)||0) : 0;
    document.getElementById('sumAdvanceUsedRow')?.classList.toggle('hidden', !useAdv||advAmt<=0);
    if (useAdv && advAmt>0) document.getElementById('sumAdvanceUsed').textContent = '৳'+advAmt.toFixed(2);

    // Overpayment
    const overpayWrap = document.getElementById('sumOverpayWrap');
    if (currentWay==='vendor' && currentMode==='purchase' && selectedPurchaseIds.size>0) {
        let selTotal = 0;
        purchaseEntries.forEach(e=>{ if(selectedPurchaseIds.has(e.sys_id)) selTotal+=parseFloat(e.remaining_amount??e.amount)||0; });
        const disc     = document.getElementById('withDiscount')?.checked ? (parseFloat(document.getElementById('discountAmount')?.value)||0) : 0;
        const totalCov = amt + advAmt - disc;
        const overpay  = Math.max(0, totalCov - selTotal);
        overpayWrap.classList.toggle('hidden', overpay<0.01);
        document.getElementById('sumOverpay').textContent = overpay>0.01 ? `⚠ ৳${overpay.toFixed(2)} বাড়তি — Advance বা Baksheesh choose করতে হবে` : '';
    } else {
        overpayWrap.classList.add('hidden');
    }

    // Mode
    document.getElementById('sumAdvanceWrap')?.classList.toggle('hidden', currentMode!=='advance');
}

/* OVERPAYMENT MODAL */
function showOverpayModal(overpayAmt) {
    return new Promise(resolve => {
        const existing = document.getElementById('overpayModal');
        if (existing) existing.remove();
        const modal = document.createElement('div');
        modal.id = 'overpayModal';
        modal.className = 'fixed inset-0 bg-black/50 z-[9999] flex items-center justify-center p-4';
        modal.innerHTML = `
            <div class="bg-white rounded-2xl shadow-2xl w-full max-w-sm p-6">
                <h3 class="text-base font-bold text-gray-900 mb-2">
                    <i class="fas fa-exclamation-triangle text-orange-500 mr-2"></i> Overpayment
                </h3>
                <p class="text-sm text-gray-600 mb-4">৳${overpayAmt} বাড়তি। এটা কী হিসেবে রাখবো?</p>
                <div class="grid grid-cols-2 gap-3">
                    <button onclick="document.getElementById('overpayModal').dataset.choice='advance'"
                        class="py-2.5 px-4 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold rounded-xl text-sm">
                        <i class="fas fa-piggy-bank mr-1"></i> Vendor Advance
                    </button>
                    <button onclick="document.getElementById('overpayModal').dataset.choice='baksheesh'"
                        class="py-2.5 px-4 bg-pink-600 hover:bg-pink-700 text-white font-semibold rounded-xl text-sm">
                        <i class="fas fa-gift mr-1"></i> Baksheesh
                    </button>
                </div>
                <button onclick="document.getElementById('overpayModal').dataset.choice='cancel'"
                    class="w-full mt-3 py-2 text-xs text-gray-400 hover:text-gray-600">Cancel</button>
            </div>`;
        document.body.appendChild(modal);
        const obs = new MutationObserver(() => {
            const choice = modal.dataset.choice;
            if (choice) { obs.disconnect(); modal.remove(); resolve(choice==='cancel'?null:choice); }
        });
        obs.observe(modal, { attributes: true });
    });
}

/* SUBMIT */
async function submitPayment() {
    const amount     = parseFloat(document.getElementById('paymentAmount').value)||0;
    const date       = document.getElementById('paymentDate').value;
    const method     = getMethod();
    const particular = document.getElementById('particular').value||'Payment Made';
    const accSel     = document.getElementById('withdrawAccount');
    const accountId  = accSel.value;
    const accountName= accSel.options[accSel.selectedIndex]?.dataset.name||'';
    const isInstr    = ['cheque','bftn-eft'].includes(method);
    const withDiscount = document.getElementById('withDiscount')?.checked||false;
    const discountAmount = parseFloat(document.getElementById('discountAmount')?.value)||0;
    const discountParticular = document.getElementById('discountParticular')?.value||'';
    const useAdv     = document.getElementById('useVendorAdvance')?.checked||false;
    const advAmt     = useAdv ? (parseFloat(document.getElementById('vendorAdvanceAmount')?.value)||0) : 0;

    if (amount<=0) { showToast('Amount দিন','error'); return; }
    if (!isInstr && !accountId) { showToast('Withdraw account select করুন','error'); return; }
    if (currentWay==='invoice' && !selectedInvoice) { showToast('Invoice select করুন','error'); return; }
    if (currentWay==='vendor' && !selectedVendor) { showToast('Vendor select করুন','error'); return; }

    // Overpayment check
    let overpaymentAction = 'advance';
    if (currentWay==='vendor' && currentMode==='purchase' && selectedPurchaseIds.size>0) {
        let selTotal = 0;
        purchaseEntries.forEach(e=>{ if(selectedPurchaseIds.has(e.sys_id)) selTotal+=parseFloat(e.remaining_amount??e.amount)||0; });
        const disc     = withDiscount ? discountAmount : 0;
        const totalCov = amount + advAmt - disc;
        const overpay  = totalCov - selTotal;
        if (overpay > 0.009) {
            const choice = await showOverpayModal(overpay.toFixed(2));
            if (choice===null) return;
            overpaymentAction = choice;
        }
    }

    const payload = {
        vendorId              : selectedVendor?.sys_id || (selectedInvoice ? selectedInvoice.client_sys_id : ''),
        vendorName            : selectedVendor?.name   || (selectedInvoice ? selectedInvoice.client_name  : ''),
        amount,
        transactionDate       : date+' '+new Date().toTimeString().slice(0,8),
        particular,
        transferMethod        : method,
        accountId,
        accountName,
        isHistorical          : 0,
        selectedPurchaseIds   : (currentWay==='vendor' && currentMode==='purchase') ? [...selectedPurchaseIds] : [],
        overpayment_action    : overpaymentAction,
        use_vendor_advance    : useAdv,
        vendor_advance_amount : useAdv ? advAmt : 0,
        withDiscount,
        discountAmount        : withDiscount ? discountAmount : 0,
        discountParticular    : withDiscount ? discountParticular : '',
    };

    if (method==='cheque') {
        payload.chequeNo          = document.getElementById('chequeNo').value;
        payload.chequeDate        = document.getElementById('chequeDate').value;
        payload.chequeAccountName = document.getElementById('chequeAccountName').value;
        payload.bankName          = document.getElementById('chequeBankName').value;
    }
    if (method==='bftn-eft') {
        payload.bftnNo          = document.getElementById('bftnNo').value;
        payload.bftnDate        = document.getElementById('bftnDate').value;
        payload.bftnAccountName = document.getElementById('bftnAccountName').value;
        payload.eftBankName     = document.getElementById('bftnBankName').value;
    }

    const btn = document.getElementById('submitBtn');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Saving...';

    try {
        const r    = await fetch(PAYMENT_API, {
            method:'POST', headers:{'Content-Type':'application/json'}, body:JSON.stringify(payload)
        });
        const data = await r.json();
        if (data.success) {
            // File upload
            const files = document.getElementById('paymentFiles')?.files;
            if (files && files.length>0 && data.data?.payment_entry_ids?.length>0) {
                const fd = new FormData();
                fd.append('entity_type', 'payment');
                fd.append('entity_id',   data.data.payment_entry_ids[0]);
                fd.append('entity_name', selectedVendor?.name||'vendor');
                for (const f of files) fd.append('files[]', f);
                fetch(UPLOAD_API, { method:'POST', body:fd });
            }
            showToast(data.message||'Payment recorded!','success');
            addRecent(selectedVendor?.name||'', amount, method);
            document.getElementById('paymentAmount').value = '';
            document.getElementById('particular').value    = '';
            document.getElementById('withDiscount').checked = false;
            document.getElementById('discountFields').classList.add('hidden');
            document.getElementById('useVendorAdvance') && (document.getElementById('useVendorAdvance').checked = false);
            document.getElementById('vendorAdvanceInputWrap')?.classList.add('hidden');
            selectedPurchaseIds.clear();
            if (currentWay==='vendor' && currentMode==='purchase' && selectedVendor) await loadPurchaseEntries();
            updateSummary();
        } else {
            showToast('Error: '+(data.message||'Failed'),'error');
        }
    } catch(e) {
        showToast('Network error: '+e.message,'error');
    } finally {
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-paper-plane mr-2"></i> Save Payment';
    }
}

function addRecent(vendor, amount, method) {
    const el = document.getElementById('recentList');
    const p  = el.querySelector('p'); if (p) p.remove();
    const div = document.createElement('div');
    div.className = 'flex justify-between items-center p-2 bg-red-50 rounded-lg border border-red-100';
    div.innerHTML = `
        <div>
            <div class="text-xs font-semibold text-gray-800">${vendor}</div>
            <div class="text-xs text-gray-400">${method} · ${new Date().toLocaleTimeString([],{hour:'2-digit',minute:'2-digit'})}</div>
        </div>
        <div class="text-sm font-bold text-red-600">৳${parseFloat(amount).toFixed(2)}</div>`;
    el.insertBefore(div, el.firstChild);
    while (el.children.length>5) el.removeChild(el.lastChild);
}

function showToast(msg, type='success') {
    const t = document.createElement('div');
    t.className = `fixed bottom-6 right-6 z-50 px-5 py-3 rounded-xl shadow-lg text-white text-sm font-medium flex items-center gap-2
        ${type==='success'?'bg-green-600':'bg-red-600'}`;
    t.innerHTML = `<i class="fas fa-${type==='success'?'check-circle':'exclamation-circle'}"></i>${msg}`;
    document.body.appendChild(t);
    setTimeout(()=>t.remove(), 4000);
}
</script>
</body>
</html>