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
<div class="p-6 max-w-4xl mx-auto">

    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900">
            <i class="fas fa-arrow-up text-red-500 mr-2"></i>Make Payment
        </h1>
        <p class="text-gray-500 text-sm mt-1">Vendor কে payment করুন</p>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        <!-- LEFT -->
        <div class="lg:col-span-2 space-y-4">

            <!-- Vendor Select -->
            <div class="bg-white rounded-xl border border-gray-200 p-5">
                <h3 class="text-sm font-semibold text-gray-700 mb-3">
                    <i class="fas fa-building mr-1 text-orange-500"></i> Vendor
                </h3>
                <div class="relative">
                    <input type="text" id="vendorSearch" placeholder="Vendor name বা ID দিয়ে search করুন..."
                        class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-orange-400 pr-10" autocomplete="off">
                    <button id="vendorResetBtn" onclick="resetVendor()" class="hidden absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-red-500 transition-colors">
                        <i class="fas fa-times-circle"></i>
                    </button>
                    <div id="vendorDropdown" class="absolute z-30 w-full bg-white border border-gray-200 rounded-lg shadow-lg hidden max-h-52 overflow-y-auto mt-1"></div>
                </div>
                <div id="vendorInfo" class="hidden mt-3 p-3 bg-orange-50 rounded-lg text-sm text-orange-800 flex items-center justify-between gap-2">
                    <div class="flex items-center gap-2">
                        <i class="fas fa-building text-orange-400"></i>
                        <span id="vendorInfoText"></span>
                    </div>
                    <button onclick="resetVendor()" class="text-xs text-orange-400 hover:text-red-500 flex-shrink-0">
                        <i class="fas fa-times mr-1"></i>Reset
                    </button>
                </div>
            </div>

            <!-- Mode Tabs -->
            <div class="bg-white rounded-xl border border-gray-200 p-5">
                <div class="flex gap-2 mb-4">
                    <button onclick="setMode('purchase')" id="tab-purchase"
                        class="mode-tab flex-1 py-2 px-3 rounded-lg text-sm font-semibold border-2 border-orange-500 bg-orange-50 text-orange-700">
                        <i class="fas fa-list mr-1"></i> Purchase Entries
                    </button>
                    <button onclick="setMode('general')" id="tab-general"
                        class="mode-tab flex-1 py-2 px-3 rounded-lg text-sm font-semibold border-2 border-gray-200 text-gray-500 hover:border-gray-300">
                        <i class="fas fa-coins mr-1"></i> General / Advance
                    </button>
                </div>

                <!-- Purchase Entries -->
                <div id="mode-purchase">
                    <div class="flex justify-between items-center mb-2">
                        <span class="text-xs font-medium text-gray-500">Unpaid / Partial Purchases</span>
                        <button onclick="selectAllPurchases()" class="text-xs text-orange-500 hover:underline">Select All</button>
                    </div>
                    <div id="purchaseList" class="space-y-2 mb-3 max-h-64 overflow-y-auto">
                        <p class="text-xs text-gray-400 text-center py-4">Vendor select করুন</p>
                    </div>
                    <div class="flex justify-between text-sm font-semibold text-gray-700 border-t pt-2">
                        <span>Selected Remaining Total:</span>
                        <span id="purchaseSelectedTotal" class="text-orange-600">৳0.00</span>
                    </div>
                </div>

                <!-- General Mode -->
                <div id="mode-general" class="hidden">
                    <div class="p-3 bg-indigo-50 border border-indigo-200 rounded-lg text-xs text-indigo-700">
                        <i class="fas fa-piggy-bank mr-1"></i>
                        কোনো purchase select না করে payment করলে Vendor কে <strong>Advance</strong> হিসেবে দেওয়া হবে।
                    </div>
                </div>
            </div>

            <!-- Amount & Date -->
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
                    <div class="grid grid-cols-4 gap-2">
                        <label class="method-label flex flex-col items-center p-2.5 border-2 border-red-500 bg-red-50 text-red-700 rounded-lg cursor-pointer" data-val="cash">
                            <input type="radio" name="payMethod" value="cash" class="hidden" checked>
                            <i class="fas fa-money-bill-wave text-xl mb-1"></i>
                            <span class="text-xs font-semibold">Cash</span>
                        </label>
                        <label class="method-label flex flex-col items-center p-2.5 border-2 border-gray-200 text-gray-500 rounded-lg cursor-pointer hover:border-gray-300" data-val="mfs">
                            <input type="radio" name="payMethod" value="mfs" class="hidden">
                            <i class="fas fa-mobile-alt text-xl mb-1"></i>
                            <span class="text-xs font-semibold">MFS</span>
                        </label>
                        <label class="method-label flex flex-col items-center p-2.5 border-2 border-gray-200 text-gray-500 rounded-lg cursor-pointer hover:border-gray-300" data-val="cheque">
                            <input type="radio" name="payMethod" value="cheque" class="hidden">
                            <i class="fas fa-file-alt text-xl mb-1"></i>
                            <span class="text-xs font-semibold">Cheque</span>
                        </label>
                        <label class="method-label flex flex-col items-center p-2.5 border-2 border-gray-200 text-gray-500 rounded-lg cursor-pointer hover:border-gray-300" data-val="bftn-eft">
                            <input type="radio" name="payMethod" value="bftn-eft" class="hidden">
                            <i class="fas fa-university text-xl mb-1"></i>
                            <span class="text-xs font-semibold">BFTN/EFT</span>
                        </label>
                    </div>
                </div>

                <!-- Withdraw Account -->
                <div id="accountSection">
                    <label class="block text-xs font-medium text-gray-600 mb-1">Withdraw From Account</label>
                    <select id="withdrawAccount" class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-red-400">
                        <option value="">-- Select Account --</option>
                    </select>
                </div>

                <!-- Cheque Fields -->
                <div id="chequeFields" class="hidden bg-yellow-50 border border-yellow-200 rounded-lg p-4 space-y-3">
                    <p class="text-xs font-semibold text-yellow-700">
                        <i class="fas fa-clock mr-1"></i> Cheque — Pending Clearance
                    </p>
                    <div class="grid grid-cols-2 gap-3">
                        <div><label class="block text-xs text-gray-600 mb-1">Cheque No</label>
                            <input type="text" id="chequeNo" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm"></div>
                        <div><label class="block text-xs text-gray-600 mb-1">Cheque Date</label>
                            <input type="date" id="chequeDate" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm"></div>
                        <div><label class="block text-xs text-gray-600 mb-1">Account Name</label>
                            <input type="text" id="chequeAccountName" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm"></div>
                        <div><label class="block text-xs text-gray-600 mb-1">Bank Name</label>
                            <input type="text" id="chequeBankName" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm"></div>
                    </div>
                </div>

                <!-- BFTN Fields -->
                <div id="bftnFields" class="hidden bg-blue-50 border border-blue-200 rounded-lg p-4 space-y-3">
                    <p class="text-xs font-semibold text-blue-700">
                        <i class="fas fa-clock mr-1"></i> BFTN/EFT — Pending Clearance
                    </p>
                    <div class="grid grid-cols-2 gap-3">
                        <div><label class="block text-xs text-gray-600 mb-1">Reference No</label>
                            <input type="text" id="bftnNo" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm"></div>
                        <div><label class="block text-xs text-gray-600 mb-1">Date</label>
                            <input type="date" id="bftnDate" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm"></div>
                        <div><label class="block text-xs text-gray-600 mb-1">Account Name</label>
                            <input type="text" id="bftnAccountName" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm"></div>
                        <div><label class="block text-xs text-gray-600 mb-1">Bank Name</label>
                            <input type="text" id="bftnBankName" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm"></div>
                    </div>
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

        <!-- RIGHT: Summary -->
        <div class="space-y-4">
            <div class="bg-white rounded-xl border border-gray-200 p-5">
                <h3 class="text-sm font-semibold text-gray-700 mb-4">
                    <i class="fas fa-receipt mr-1 text-red-500"></i> Summary
                </h3>
                <div class="space-y-3 text-sm">
                    <div class="flex justify-between">
                        <span class="text-gray-500">Vendor</span>
                        <span id="sumVendor" class="font-medium text-right max-w-[150px] truncate">—</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-500">Mode</span>
                        <span id="sumMode" class="font-medium">Purchase Entries</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-500">Method</span>
                        <span id="sumMethod" class="font-medium">Cash</span>
                    </div>
                    <div class="border-t pt-3 flex justify-between">
                        <span class="font-semibold text-gray-700">Amount</span>
                        <span id="sumAmount" class="font-bold text-red-600 text-xl">৳0.00</span>
                    </div>
                    <div id="sumOverpayWrap" class="hidden p-2 bg-orange-50 rounded-lg">
                        <div class="flex justify-between text-xs">
                            <span class="text-orange-600">Overpayment → Advance</span>
                            <span id="sumOverpay" class="text-orange-600 font-semibold">৳0.00</span>
                        </div>
                    </div>
                    <div id="sumAdvanceWrap" class="hidden p-2 bg-indigo-50 rounded-lg">
                        <p class="text-xs text-indigo-600"><i class="fas fa-piggy-bank mr-1"></i> Vendor Advance হিসেবে দেওয়া হবে</p>
                    </div>
                    <div id="sumInstrumentWrap" class="hidden p-2 bg-yellow-50 rounded-lg">
                        <p class="text-xs text-yellow-700"><i class="fas fa-clock mr-1"></i> Instrument pending — bank এখনই update হবে না</p>
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
const ACC_API     = `${IP}/api/accounts/all-accounts.php`;
const VENDOR_API  = `${IP}/api/vendors/all-vendors.php`;

let selectedVendor     = null;
let selectedPurchaseIds= new Set();
let currentMode        = 'purchase';
let purchaseEntries    = [];

document.addEventListener('DOMContentLoaded', () => {
    loadAccounts();
    setupMethodToggle();
    document.getElementById('paymentAmount').addEventListener('input', updateSummary);
    setupVendorSearch();
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

function setupVendorSearch() {
    const input = document.getElementById('vendorSearch');
    const dd    = document.getElementById('vendorDropdown');
    let vendors = [];
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

function selectVendor(v) {
    selectedVendor = v;
    document.getElementById('vendorSearch').value = v.name;
    document.getElementById('vendorDropdown').classList.add('hidden');
    // Phone parse
    let phone = '';
    try {
        const ph = typeof v.phone === 'string' ? JSON.parse(v.phone) : v.phone;
        phone = ph?.primary_no || ph?.primary || '';
    } catch(e) { phone = v.phone || ''; }
    document.getElementById('vendorInfoText').innerHTML =
        `<span class="font-semibold">${v.name}</span>
         <span class="text-orange-500 mx-1">·</span>
         <span class="text-orange-600">${v.sys_id}</span>
         ${phone ? `<span class="text-orange-500 mx-1">·</span><span>${phone}</span>` : ''}`;
    document.getElementById('vendorInfo').classList.remove('hidden');
    document.getElementById('vendorResetBtn').classList.remove('hidden');
    document.getElementById('sumVendor').textContent = v.name;
    loadModeData();
}

function resetVendor() {
    selectedVendor = null;
    selectedPurchaseIds.clear();
    document.getElementById('vendorSearch').value = '';
    document.getElementById('vendorInfo').classList.add('hidden');
    document.getElementById('vendorResetBtn').classList.add('hidden');
    document.getElementById('sumVendor').textContent = '—';
    document.getElementById('purchaseList').innerHTML = '<p class="text-xs text-gray-400 text-center py-4">Vendor select করুন</p>';
    document.getElementById('purchaseSelectedTotal').textContent = '৳0.00';
    document.getElementById('paymentAmount').value = '';
    updateSummary();
}

function setMode(mode) {
    currentMode = mode;
    ['purchase','general'].forEach(m => {
        document.getElementById(`mode-${m}`).classList.toggle('hidden', m!==mode);
        const t = document.getElementById(`tab-${m}`);
        t.className = m===mode
            ? 'mode-tab flex-1 py-2 px-3 rounded-lg text-sm font-semibold border-2 border-orange-500 bg-orange-50 text-orange-700'
            : 'mode-tab flex-1 py-2 px-3 rounded-lg text-sm font-semibold border-2 border-gray-200 text-gray-500 hover:border-gray-300';
    });
    const mn = { purchase:'Purchase Entries', general:'General / Advance' };
    document.getElementById('sumMode').textContent = mn[mode];
    document.getElementById('sumAdvanceWrap').classList.toggle('hidden', mode!=='general');
    selectedPurchaseIds.clear();
    loadModeData();
}

async function loadModeData() {
    if (!selectedVendor) return;
    if (currentMode==='purchase') await loadPurchaseEntries();
}

async function loadPurchaseEntries() {
    const r = await fetch(`${FIN_API}?id=${selectedVendor.sys_id}&type=credit&related_type=2&is_paid=0`);
    const d = await r.json();
    purchaseEntries = (d.finStmts||[]).filter(e =>
        (e.type||'').toLowerCase()==='credit' &&
        parseInt(e.related_type)===2 &&
        parseInt(e.is_paid||0)===0
    );
    selectedPurchaseIds.clear();
    renderPurchaseList();
}

function renderPurchaseList() {
    const el = document.getElementById('purchaseList');
    if (!purchaseEntries.length) {
        el.innerHTML = '<p class="text-xs text-gray-400 text-center py-4">কোনো unpaid purchase নেই</p>';
        updatePurchaseTotal();
        return;
    }
    el.innerHTML = purchaseEntries.map(e => {
        const amt       = parseFloat(e.amount)||0;
        const remaining = parseFloat(e.remaining_amount??amt);
        const paid      = parseFloat(e.paid_amount||0);
        const pct       = amt>0?Math.min((paid/amt)*100,100):0;
        const checked   = selectedPurchaseIds.has(e.sys_id);
        const pb        = e.is_partial==1?`<span class="text-xs bg-blue-100 text-blue-700 px-1 py-0.5 rounded ml-1">Partial</span>`:'';
        const bar       = paid>0?`
            <div class="w-full bg-gray-200 rounded-full h-1 mt-1">
                <div class="bg-orange-500 h-1 rounded-full" style="width:${pct}%"></div>
            </div>
            <p class="text-xs text-gray-400 mt-0.5">Paid: ${paid.toFixed(2)} · Remaining: <b class="text-orange-600">${remaining.toFixed(2)}</b></p>`:''
        ;
        return `<div class="flex items-start gap-2 p-2 border-2 rounded-lg cursor-pointer transition-colors
                ${checked?'border-orange-400 bg-orange-50':'border-gray-200 hover:border-gray-300'}"
                onclick="togglePurchase('${e.sys_id}')">
            <input type="checkbox" class="mt-0.5 flex-shrink-0 w-4 h-4" ${checked?'checked':''} onclick="event.stopPropagation();togglePurchase('${e.sys_id}')">
            <div class="flex-1 min-w-0">
                <div class="text-xs font-medium text-gray-800 truncate">${e.purpose||'N/A'}${pb}</div>
                <div class="text-xs text-gray-400">${(e.date||'').substring(0,10)}${e.work_title?' · '+e.work_title:''}</div>
                ${bar}
            </div>
            <div class="text-sm font-bold text-green-600 flex-shrink-0">৳${remaining.toFixed(2)}</div>
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
    purchaseEntries.forEach(e => {
        if (selectedPurchaseIds.has(e.sys_id))
            total += parseFloat(e.remaining_amount??e.amount)||0;
    });
    document.getElementById('purchaseSelectedTotal').textContent = '৳'+total.toFixed(2);
    if (total>0&&!document.getElementById('paymentAmount').value)
        document.getElementById('paymentAmount').value = total.toFixed(2);
    updateSummary();
}

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
            document.getElementById('sumMethod').textContent = label.querySelector('span').textContent;
            document.getElementById('sumInstrumentWrap').classList.toggle('hidden', !isInstr);
            updateSummary();
        });
    });
}

function getMethod() {
    return document.querySelector('input[name="payMethod"]:checked')?.value||'cash';
}

function updateSummary() {
    const amt = parseFloat(document.getElementById('paymentAmount').value)||0;
    document.getElementById('sumAmount').textContent = '৳'+amt.toFixed(2);

    if (currentMode==='purchase'&&selectedPurchaseIds.size>0) {
        let selTotal = 0;
        purchaseEntries.forEach(e => {
            if (selectedPurchaseIds.has(e.sys_id))
                selTotal += parseFloat(e.remaining_amount??e.amount)||0;
        });
        const overpay = Math.max(0, amt-selTotal);
        document.getElementById('sumOverpayWrap').classList.toggle('hidden', overpay<0.01);
        document.getElementById('sumOverpay').textContent = '৳'+overpay.toFixed(2);
        const hint = document.getElementById('amountHint');
        hint.textContent = overpay>0.01?`৳${overpay.toFixed(2)} বাড়তি — Vendor Advance হবে`:'';
        hint.classList.toggle('hidden', overpay<0.01);
    } else {
        document.getElementById('sumOverpayWrap').classList.add('hidden');
        document.getElementById('amountHint').classList.add('hidden');
    }
}

async function submitPayment() {
    if (!selectedVendor) { showToast('Vendor select করুন','error'); return; }
    const amount    = parseFloat(document.getElementById('paymentAmount').value);
    const date      = document.getElementById('paymentDate').value;
    const method    = getMethod();
    const particular= document.getElementById('particular').value||'Payment Made';
    const accSel    = document.getElementById('withdrawAccount');
    const accountId = accSel.value;
    const accountName = accSel.options[accSel.selectedIndex]?.dataset.name||'';

    if (!amount||amount<=0) { showToast('Amount দিন','error'); return; }
    if (!['cheque','bftn-eft'].includes(method)&&!accountId) {
        showToast('Withdraw account select করুন','error'); return;
    }

    const payload = {
        vendorId           : selectedVendor.sys_id,
        vendorName         : selectedVendor.name,
        amount             : amount,
        transactionDate    : date+' '+new Date().toTimeString().slice(0,8),
        particular         : particular,
        transferMethod     : method,
        accountId          : accountId,
        accountName        : accountName,
        isHistorical       : 0,
        selectedPurchaseIds: currentMode==='purchase'?[...selectedPurchaseIds]:[],
        withDiscount       : false,
        discountAmount     : 0,
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
        const r = await fetch(PAYMENT_API, {
            method:'POST', headers:{'Content-Type':'application/json'}, body:JSON.stringify(payload)
        });
        const data = await r.json();
        if (data.success) {
            showToast(data.message||'Payment recorded!','success');
            addRecent(selectedVendor.name, amount, method);
            selectedPurchaseIds.clear();
            document.getElementById('paymentAmount').value = '';
            document.getElementById('particular').value    = '';
            updateSummary();
            if (currentMode==='purchase') await loadPurchaseEntries();
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
    const p  = el.querySelector('p');
    if (p) p.remove();
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
    setTimeout(() => t.remove(), 4000);
}
</script>
</body>
</html>