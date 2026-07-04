<?php
include_once('./authenticate.php');
$ip_port = trim(@file_get_contents('../ippath.txt') ?: 'http://103.104.219.3:898', '/');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Receive Payment — TravHub</title>
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

    <!-- Page Header -->
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900">
            <i class="fas fa-arrow-down text-green-500 mr-2"></i>Receive Payment
        </h1>
        <p class="text-gray-500 text-sm mt-1">Client থেকে payment receive করুন</p>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        <!-- ===== LEFT: Form ===== -->
        <div class="lg:col-span-2 space-y-4">

            <!-- Client Select -->
            <div class="bg-white rounded-xl border border-gray-200 p-5">
                <h3 class="text-sm font-semibold text-gray-700 mb-3">
                    <i class="fas fa-user mr-1 text-blue-500"></i> Client
                </h3>
                <div class="relative">
                    <input type="text" id="clientSearch" placeholder="Client name বা ID দিয়ে search করুন..."
                        class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-400 pr-10" autocomplete="off">
                    <button id="clientResetBtn" onclick="resetClient()" class="hidden absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-red-500 transition-colors">
                        <i class="fas fa-times-circle"></i>
                    </button>
                    <div id="clientDropdown" class="absolute z-30 w-full bg-white border border-gray-200 rounded-lg shadow-lg hidden max-h-52 overflow-y-auto mt-1"></div>
                </div>
                <div id="clientInfo" class="hidden mt-3 p-3 bg-blue-50 rounded-lg text-sm text-blue-800 flex items-center justify-between gap-2">
                    <div class="flex items-center gap-2">
                        <i class="fas fa-user-circle text-blue-400"></i>
                        <span id="clientInfoText"></span>
                    </div>
                    <button onclick="resetClient()" class="text-xs text-blue-400 hover:text-red-500 flex-shrink-0">
                        <i class="fas fa-times mr-1"></i>Reset
                    </button>
                </div>
            </div>

            <!-- Mode Tabs -->
            <div class="bg-white rounded-xl border border-gray-200 p-5">
                <div class="flex gap-2 mb-4">
                    <button onclick="setMode('sale')" id="tab-sale"
                        class="mode-tab flex-1 py-2 px-3 rounded-lg text-sm font-semibold border-2 border-blue-500 bg-blue-50 text-blue-700">
                        <i class="fas fa-list mr-1"></i> Sale Entries
                    </button>
                    <button onclick="setMode('invoice')" id="tab-invoice"
                        class="mode-tab flex-1 py-2 px-3 rounded-lg text-sm font-semibold border-2 border-gray-200 text-gray-500 hover:border-gray-300">
                        <i class="fas fa-file-invoice mr-1"></i> Invoice
                    </button>
                    <button onclick="setMode('general')" id="tab-general"
                        class="mode-tab flex-1 py-2 px-3 rounded-lg text-sm font-semibold border-2 border-gray-200 text-gray-500 hover:border-gray-300">
                        <i class="fas fa-coins mr-1"></i> General / Advance
                    </button>
                </div>

                <!-- Sale Entries Mode -->
                <div id="mode-sale">
                    <div class="flex justify-between items-center mb-2">
                        <span class="text-xs font-medium text-gray-500">Unpaid / Partial Sales</span>
                        <button onclick="selectAllSales()" class="text-xs text-blue-500 hover:underline">Select All</button>
                    </div>
                    <div id="saleList" class="space-y-2 mb-3 max-h-64 overflow-y-auto">
                        <p class="text-xs text-gray-400 text-center py-4">Client select করুন</p>
                    </div>
                    <div class="flex justify-between text-sm font-semibold text-gray-700 border-t pt-2">
                        <span>Selected Remaining Total:</span>
                        <span id="saleSelectedTotal" class="text-blue-600">৳0.00</span>
                    </div>
                </div>

                <!-- Invoice Mode -->
                <div id="mode-invoice" class="hidden">
                    <p class="text-xs text-gray-500 mb-2">Unpaid / Partial Invoices</p>
                    <div id="invoiceList" class="space-y-2 max-h-64 overflow-y-auto">
                        <p class="text-xs text-gray-400 text-center py-4">Client select করুন</p>
                    </div>
                </div>

                <!-- General Mode -->
                <div id="mode-general" class="hidden">
                    <div class="p-3 bg-indigo-50 border border-indigo-200 rounded-lg text-xs text-indigo-700">
                        <i class="fas fa-piggy-bank mr-1"></i>
                        কোনো sale বা invoice select না করে receive করলে <strong>Advance</strong> হিসেবে save হবে।
                        পরে invoice বা sale clear করতে ব্যবহার করা যাবে।
                    </div>
                </div>
            </div>

            <!-- Amount & Date -->
            <div class="bg-white rounded-xl border border-gray-200 p-5 space-y-4">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Receive Amount ৳</label>
                        <input type="number" id="receiveAmount" step="0.01" min="0" placeholder="0.00"
                            class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-green-400 font-semibold">
                        <p id="amountHint" class="text-xs text-orange-500 mt-1 hidden"></p>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Date</label>
                        <input type="date" id="receiveDate" value="<?php echo date('Y-m-d'); ?>"
                            class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-green-400">
                    </div>
                </div>

                <!-- Payment Method -->
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-2">Payment Method</label>
                    <div class="grid grid-cols-4 gap-2" id="methodGrid">
                        <label class="method-label flex flex-col items-center p-2.5 border-2 border-green-500 bg-green-50 text-green-700 rounded-lg cursor-pointer" data-val="cash">
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

                <!-- Deposit Account — cash/mfs তে দেখাবে -->
                <div id="accountSection">
                    <label class="block text-xs font-medium text-gray-600 mb-1">Deposit To Account</label>
                    <select id="depositAccount" class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-green-400">
                        <option value="">-- Select Account --</option>
                    </select>
                </div>

                <!-- Cheque Fields -->
                <div id="chequeFields" class="hidden bg-yellow-50 border border-yellow-200 rounded-lg p-4 space-y-3">
                    <p class="text-xs font-semibold text-yellow-700">
                        <i class="fas fa-clock mr-1"></i> Cheque — Pending Clearance (bank balance এখনই update হবে না)
                    </p>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs text-gray-600 mb-1">Cheque No</label>
                            <input type="text" id="chequeNo" placeholder="Cheque number"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                        </div>
                        <div>
                            <label class="block text-xs text-gray-600 mb-1">Cheque Date</label>
                            <input type="date" id="chequeDate" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                        </div>
                        <div>
                            <label class="block text-xs text-gray-600 mb-1">Account Name</label>
                            <input type="text" id="chequeAccountName" placeholder="Account holder"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                        </div>
                        <div>
                            <label class="block text-xs text-gray-600 mb-1">Bank Name</label>
                            <input type="text" id="chequeBankName" placeholder="Bank name"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                        </div>
                    </div>
                </div>

                <!-- BFTN/EFT Fields -->
                <div id="bftnFields" class="hidden bg-blue-50 border border-blue-200 rounded-lg p-4 space-y-3">
                    <p class="text-xs font-semibold text-blue-700">
                        <i class="fas fa-clock mr-1"></i> BFTN/EFT — Pending Clearance (bank balance এখনই update হবে না)
                    </p>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs text-gray-600 mb-1">Reference No</label>
                            <input type="text" id="bftnNo" placeholder="Reference number"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                        </div>
                        <div>
                            <label class="block text-xs text-gray-600 mb-1">Date</label>
                            <input type="date" id="bftnDate" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                        </div>
                        <div>
                            <label class="block text-xs text-gray-600 mb-1">Account Name</label>
                            <input type="text" id="bftnAccountName" placeholder="Account holder"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                        </div>
                        <div>
                            <label class="block text-xs text-gray-600 mb-1">Bank Name</label>
                            <input type="text" id="bftnBankName" placeholder="Bank name"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                        </div>
                    </div>
                </div>

                <!-- Particular -->
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Particular</label>
                    <textarea id="particular" rows="2" placeholder="Payment description..."
                        class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-green-400"></textarea>
                </div>
            </div>

            <!-- Submit -->
            <button onclick="submitReceive()" id="submitBtn"
                class="w-full py-3.5 bg-green-600 hover:bg-green-700 text-white font-bold rounded-xl transition-colors flex items-center justify-center gap-2 text-base">
                <i class="fas fa-check-circle"></i> Save Receive
            </button>
        </div>

        <!-- ===== RIGHT: Summary ===== -->
        <div class="space-y-4">
            <div class="bg-white rounded-xl border border-gray-200 p-5">
                <h3 class="text-sm font-semibold text-gray-700 mb-4">
                    <i class="fas fa-receipt mr-1 text-green-500"></i> Summary
                </h3>
                <div class="space-y-3 text-sm">
                    <div class="flex justify-between">
                        <span class="text-gray-500">Client</span>
                        <span id="sumClient" class="font-medium text-gray-800 text-right max-w-[150px] truncate">—</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-500">Mode</span>
                        <span id="sumMode" class="font-medium text-gray-800">Sale Entries</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-500">Method</span>
                        <span id="sumMethod" class="font-medium text-gray-800">Cash</span>
                    </div>
                    <div class="border-t pt-3">
                        <div class="flex justify-between">
                            <span class="font-semibold text-gray-700">Amount</span>
                            <span id="sumAmount" class="font-bold text-green-600 text-xl">৳0.00</span>
                        </div>
                    </div>
                    <div id="sumOverpayWrap" class="hidden p-2 bg-orange-50 rounded-lg">
                        <div class="flex justify-between text-xs">
                            <span class="text-orange-600">Overpayment → Baksheesh</span>
                            <span id="sumOverpay" class="text-orange-600 font-semibold">৳0.00</span>
                        </div>
                    </div>
                    <div id="sumAdvanceWrap" class="hidden p-2 bg-indigo-50 rounded-lg">
                        <p class="text-xs text-indigo-600">
                            <i class="fas fa-piggy-bank mr-1"></i> Advance হিসেবে save হবে
                        </p>
                    </div>
                    <div id="sumInstrumentWrap" class="hidden p-2 bg-yellow-50 rounded-lg">
                        <p class="text-xs text-yellow-700">
                            <i class="fas fa-clock mr-1"></i> Instrument pending — bank balance এখনই update হবে না
                        </p>
                    </div>
                </div>
            </div>

            <!-- Recent -->
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
const IP         = '<?php echo $ip_port; ?>';
const RECEIVE_API= `${IP}/api/clients/cl-ac-receive-store.php`;
const FIN_API    = `${IP}/api/financial_entries/fin-entries.php`;
const INV_API    = `${IP}/api/invoices/all-invoices.php`;
const ACC_API    = `${IP}/api/accounts/all-accounts.php`;
const CLIENT_API = `${IP}/api/clients/all-clients.php`;

let selectedClient  = null;
let selectedSaleIds = new Set();
let selectedInvoice = null;
let currentMode     = 'sale';
let saleEntries     = [];

/* ===== Init ===== */
document.addEventListener('DOMContentLoaded', () => {
    loadAccounts();
    setupMethodToggle();
    document.getElementById('receiveAmount').addEventListener('input', updateSummary);
    setupClientSearch();
});

/* ===== Accounts ===== */
async function loadAccounts() {
    const r    = await fetch(ACC_API);
    const data = await r.json();
    const sel  = document.getElementById('depositAccount');
    (data.accounts || []).forEach(a => {
        const opt = document.createElement('option');
        opt.value        = a.sys_id;
        opt.dataset.name = a.acc_name;
        opt.textContent  = a.acc_name;
        sel.appendChild(opt);
    });
}

/* ===== Client Search ===== */
function setupClientSearch() {
    const input = document.getElementById('clientSearch');
    const dd    = document.getElementById('clientDropdown');
    let clients = [];
    fetch(CLIENT_API).then(r => r.json()).then(d => { clients = d.clients || []; });

    input.addEventListener('input', () => {
        const q = input.value.trim().toLowerCase();
        dd.innerHTML = '';
        if (!q) { dd.classList.add('hidden'); return; }
        const hits = clients.filter(c =>
            (c.name||'').toLowerCase().includes(q) || (c.sys_id||'').toLowerCase().includes(q)
        ).slice(0, 8);
        if (!hits.length) { dd.classList.add('hidden'); return; }
        hits.forEach(c => {
            const div = document.createElement('div');
            div.className = 'px-4 py-2.5 hover:bg-gray-50 cursor-pointer text-sm border-b last:border-0';
            div.innerHTML = `<div class="font-medium">${c.name}</div><div class="text-xs text-gray-400">${c.sys_id}</div>`;
            div.onclick = () => selectClient(c);
            dd.appendChild(div);
        });
        dd.classList.remove('hidden');
    });
    document.addEventListener('click', e => {
        if (!input.contains(e.target) && !dd.contains(e.target)) dd.classList.add('hidden');
    });
}

function selectClient(c) {
    selectedClient = c;
    document.getElementById('clientSearch').value = c.name;
    document.getElementById('clientDropdown').classList.add('hidden');
    // Phone parse — JSON string হতে পারে
    let phone = '';
    try {
        const ph = typeof c.phone === 'string' ? JSON.parse(c.phone) : c.phone;
        phone = ph?.primary_no || ph?.primary || '';
    } catch(e) { phone = c.phone || ''; }
    document.getElementById('clientInfoText').innerHTML =
        `<span class="font-semibold">${c.name}</span>
         <span class="text-blue-500 mx-1">·</span>
         <span class="text-blue-600">${c.sys_id}</span>
         ${phone ? `<span class="text-blue-500 mx-1">·</span><span>${phone}</span>` : ''}`;
    document.getElementById('clientInfo').classList.remove('hidden');
    document.getElementById('clientResetBtn').classList.remove('hidden');
    document.getElementById('sumClient').textContent = c.name;
    loadModeData();
}

function resetClient() {
    selectedClient = null;
    selectedSaleIds.clear();
    selectedInvoice = null;
    document.getElementById('clientSearch').value = '';
    document.getElementById('clientInfo').classList.add('hidden');
    document.getElementById('clientResetBtn').classList.add('hidden');
    document.getElementById('sumClient').textContent = '—';
    document.getElementById('saleList').innerHTML = '<p class="text-xs text-gray-400 text-center py-4">Client select করুন</p>';
    document.getElementById('invoiceList').innerHTML = '<p class="text-xs text-gray-400 text-center py-4">Client select করুন</p>';
    document.getElementById('saleSelectedTotal').textContent = '৳0.00';
    document.getElementById('receiveAmount').value = '';
    updateSummary();
}

/* ===== Mode ===== */
function setMode(mode) {
    currentMode = mode;
    ['sale','invoice','general'].forEach(m => {
        document.getElementById(`mode-${m}`).classList.toggle('hidden', m !== mode);
        const tab = document.getElementById(`tab-${m}`);
        tab.className = m === mode
            ? 'mode-tab flex-1 py-2 px-3 rounded-lg text-sm font-semibold border-2 border-blue-500 bg-blue-50 text-blue-700'
            : 'mode-tab flex-1 py-2 px-3 rounded-lg text-sm font-semibold border-2 border-gray-200 text-gray-500 hover:border-gray-300';
    });
    const modeNames = { sale: 'Sale Entries', invoice: 'Invoice', general: 'General / Advance' };
    document.getElementById('sumMode').textContent = modeNames[mode];
    document.getElementById('sumAdvanceWrap').classList.toggle('hidden', mode !== 'general');
    selectedSaleIds.clear();
    selectedInvoice = null;
    loadModeData();
}

async function loadModeData() {
    if (!selectedClient) return;
    if (currentMode === 'sale')    await loadSaleEntries();
    if (currentMode === 'invoice') await loadInvoices();
}

/* ===== Sale Entries ===== */
async function loadSaleEntries() {
    const r    = await fetch(`${FIN_API}?id=${selectedClient.sys_id}&type=debit&related_type=1&is_paid=0`);
    const data = await r.json();
    saleEntries = (data.finStmts || []).filter(e =>
        (e.type||'').toLowerCase() === 'debit' &&
        parseInt(e.related_type) === 1 &&
        parseInt(e.is_paid||0) === 0
    );
    selectedSaleIds.clear();
    renderSaleList();
}

function renderSaleList() {
    const el = document.getElementById('saleList');
    if (!saleEntries.length) {
        el.innerHTML = '<p class="text-xs text-gray-400 text-center py-4">কোনো unpaid sale নেই</p>';
        updateSaleTotal();
        return;
    }
    el.innerHTML = saleEntries.map(e => {
        const amt       = parseFloat(e.amount) || 0;
        const remaining = parseFloat(e.remaining_amount ?? amt);
        const received  = parseFloat(e.received_amount || 0);
        const pct       = amt > 0 ? Math.min((received/amt)*100,100) : 0;
        const checked   = selectedSaleIds.has(e.sys_id);
        const partialBadge = e.is_partial == 1
            ? `<span class="text-xs bg-blue-100 text-blue-700 px-1 py-0.5 rounded ml-1">Partial</span>` : '';
        const progressBar = received > 0 ? `
            <div class="w-full bg-gray-200 rounded-full h-1 mt-1">
                <div class="bg-green-500 h-1 rounded-full" style="width:${pct}%"></div>
            </div>
            <p class="text-xs text-gray-400 mt-0.5">Received: ${received.toFixed(2)} · Remaining: <b class="text-orange-600">${remaining.toFixed(2)}</b></p>` : '';

        return `<div class="flex items-start gap-2 p-2 border-2 rounded-lg cursor-pointer transition-colors
                ${checked ? 'border-green-400 bg-green-50' : 'border-gray-200 hover:border-gray-300'}"
                onclick="toggleSale('${e.sys_id}')">
            <input type="checkbox" class="mt-0.5 flex-shrink-0 w-4 h-4" ${checked?'checked':''} onclick="event.stopPropagation();toggleSale('${e.sys_id}')">
            <div class="flex-1 min-w-0">
                <div class="text-xs font-medium text-gray-800 truncate">${e.purpose||'N/A'}${partialBadge}</div>
                <div class="text-xs text-gray-400">${(e.date||'').substring(0,10)}${e.work_title?' · '+e.work_title:''}</div>
                ${progressBar}
            </div>
            <div class="text-sm font-bold text-red-600 flex-shrink-0">৳${remaining.toFixed(2)}</div>
        </div>`;
    }).join('');
    updateSaleTotal();
}

function toggleSale(id) {
    if (selectedSaleIds.has(id)) selectedSaleIds.delete(id);
    else selectedSaleIds.add(id);
    renderSaleList();
}

function selectAllSales() {
    if (selectedSaleIds.size === saleEntries.length) selectedSaleIds.clear();
    else saleEntries.forEach(e => selectedSaleIds.add(e.sys_id));
    renderSaleList();
}

function updateSaleTotal() {
    let total = 0;
    saleEntries.forEach(e => {
        if (selectedSaleIds.has(e.sys_id))
            total += parseFloat(e.remaining_amount ?? e.amount) || 0;
    });
    document.getElementById('saleSelectedTotal').textContent = '৳' + total.toFixed(2);
    if (total > 0 && !document.getElementById('receiveAmount').value) {
        document.getElementById('receiveAmount').value = total.toFixed(2);
    }
    updateSummary();
}

/* ===== Invoices ===== */
async function loadInvoices() {
    const r    = await fetch(`${INV_API}?client_id=${selectedClient.sys_id}&unpaid_only=1`);
    const data = await r.json();
    const invs = data.invoices || [];
    const el   = document.getElementById('invoiceList');
    selectedInvoice = null;

    if (!invs.length) {
        el.innerHTML = '<p class="text-xs text-gray-400 text-center py-4">কোনো unpaid invoice নেই</p>';
        return;
    }
    el.innerHTML = invs.map(inv => {
        const statusCls = inv.status === 'partial'
            ? 'bg-orange-100 text-orange-700' : 'bg-yellow-100 text-yellow-700';
        return `<div class="flex items-center gap-2 p-2.5 border-2 border-gray-200 hover:border-blue-400 rounded-lg cursor-pointer invoice-row transition-colors"
                    onclick="selectInvoice(this,'${inv.invoice_no}',${inv.due_amount})">
            <input type="radio" name="invSel" class="flex-shrink-0">
            <div class="flex-1 min-w-0">
                <div class="text-xs font-semibold text-gray-800">${inv.invoice_no}</div>
                <div class="text-xs text-gray-400">${inv.invoice_date} · Total ৳${inv.total_amount.toFixed(2)}</div>
            </div>
            <div class="text-right flex-shrink-0">
                <div class="text-sm font-bold text-red-600">৳${inv.due_amount.toFixed(2)}</div>
                <span class="text-xs px-1.5 py-0.5 rounded ${statusCls}">${inv.status}</span>
            </div>
        </div>`;
    }).join('');
}

function selectInvoice(el, invNo, due) {
    document.querySelectorAll('.invoice-row').forEach(d => {
        d.classList.remove('border-blue-400','bg-blue-50');
        d.classList.add('border-gray-200');
    });
    el.classList.remove('border-gray-200');
    el.classList.add('border-blue-400','bg-blue-50');
    el.querySelector('input[type=radio]').checked = true;
    selectedInvoice = { invoice_no: invNo, due_amount: due };
    document.getElementById('receiveAmount').value = parseFloat(due).toFixed(2);
    updateSummary();
}

/* ===== Payment Method ===== */
function setupMethodToggle() {
    document.querySelectorAll('.method-label').forEach(label => {
        label.addEventListener('click', () => {
            document.querySelectorAll('.method-label').forEach(l => {
                l.className = l.className
                    .replace('border-green-500 bg-green-50 text-green-700','border-gray-200 text-gray-500');
            });
            label.className = label.className
                .replace('border-gray-200 text-gray-500','border-green-500 bg-green-50 text-green-700');

            const method = label.dataset.val;
            const isInstrument = ['cheque','bftn-eft'].includes(method);

            document.getElementById('accountSection').classList.toggle('hidden', isInstrument);
            document.getElementById('chequeFields').classList.toggle('hidden', method !== 'cheque');
            document.getElementById('bftnFields').classList.toggle('hidden', method !== 'bftn-eft');
            document.getElementById('sumMethod').textContent = label.querySelector('span').textContent;
            document.getElementById('sumInstrumentWrap').classList.toggle('hidden', !isInstrument);

            updateSummary();
        });
    });
}

function getSelectedMethod() {
    return document.querySelector('input[name="payMethod"]:checked')?.value || 'cash';
}

/* ===== Summary ===== */
function updateSummary() {
    const amt = parseFloat(document.getElementById('receiveAmount').value) || 0;
    document.getElementById('sumAmount').textContent = '৳' + amt.toFixed(2);

    if (currentMode === 'sale' && selectedSaleIds.size > 0) {
        let selTotal = 0;
        saleEntries.forEach(e => {
            if (selectedSaleIds.has(e.sys_id))
                selTotal += parseFloat(e.remaining_amount ?? e.amount) || 0;
        });
        const overpay = Math.max(0, amt - selTotal);
        document.getElementById('sumOverpayWrap').classList.toggle('hidden', overpay < 0.01);
        document.getElementById('sumOverpay').textContent = '৳' + overpay.toFixed(2);
        const hint = document.getElementById('amountHint');
        hint.textContent = overpay > 0.01 ? `৳${overpay.toFixed(2)} বাড়তি — Baksheesh হবে` : '';
        hint.classList.toggle('hidden', overpay < 0.01);
    } else {
        document.getElementById('sumOverpayWrap').classList.add('hidden');
        document.getElementById('amountHint').classList.add('hidden');
    }
}

/* ===== Submit ===== */
async function submitReceive() {
    if (!selectedClient) { showToast('Client select করুন', 'error'); return; }

    const amount     = parseFloat(document.getElementById('receiveAmount').value);
    const date       = document.getElementById('receiveDate').value;
    const method     = getSelectedMethod();
    const particular = document.getElementById('particular').value || 'Payment Received';
    const accSel     = document.getElementById('depositAccount');
    const accountId  = accSel.value;
    const accountName= accSel.options[accSel.selectedIndex]?.dataset.name || '';

    if (!amount || amount <= 0) { showToast('Amount দিন', 'error'); return; }
    if (!['cheque','bftn-eft'].includes(method) && !accountId) {
        showToast('Deposit account select করুন', 'error'); return;
    }

    const payload = {
        clientId       : selectedClient.sys_id,
        clientName     : selectedClient.name,
        amount         : amount,
        transactionDate: date + ' ' + new Date().toTimeString().slice(0,8),
        particular     : particular,
        transferMethod : method,
        accountId      : accountId,
        accountName    : accountName,
        isHistorical   : 0,
        selectedSaleIds: currentMode === 'sale' ? [...selectedSaleIds] : [],
        withDiscount   : false,
        discountAmount : 0,
    };

    if (currentMode === 'invoice' && selectedInvoice) {
        payload.invoice_id  = selectedInvoice.invoice_no;
        payload.selectedSaleIds = []; // invoice mode এ sale select নেই
    }

    if (method === 'cheque') {
        payload.chequeNo          = document.getElementById('chequeNo').value;
        payload.chequeDate        = document.getElementById('chequeDate').value;
        payload.chequeAccountName = document.getElementById('chequeAccountName').value;
        payload.bankName          = document.getElementById('chequeBankName').value;
    }
    if (method === 'bftn-eft') {
        payload.bftnNo          = document.getElementById('bftnNo').value;
        payload.bftnDate        = document.getElementById('bftnDate').value;
        payload.bftnAccountName = document.getElementById('bftnAccountName').value;
        payload.eftBankName     = document.getElementById('bftnBankName').value;
    }

    const btn = document.getElementById('submitBtn');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Saving...';

    try {
        const r    = await fetch(RECEIVE_API, {
            method : 'POST',
            headers: { 'Content-Type': 'application/json' },
            body   : JSON.stringify(payload)
        });
        const data = await r.json();

        if (data.success) {
            showToast(data.message || 'Receive recorded!', 'success');
            addRecent(selectedClient.name, amount, method);
            // Reset form
            selectedSaleIds.clear();
            selectedInvoice = null;
            document.getElementById('receiveAmount').value = '';
            document.getElementById('particular').value    = '';
            updateSummary();
            if (currentMode === 'sale') await loadSaleEntries();
            if (currentMode === 'invoice') await loadInvoices();
        } else {
            showToast('Error: ' + (data.message || 'Failed'), 'error');
        }
    } catch(e) {
        showToast('Network error: ' + e.message, 'error');
    } finally {
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-check-circle mr-2"></i> Save Receive';
    }
}

function addRecent(client, amount, method) {
    const el   = document.getElementById('recentList');
    const p    = el.querySelector('p');
    if (p) p.remove();
    const div  = document.createElement('div');
    div.className = 'flex justify-between items-center p-2 bg-green-50 rounded-lg border border-green-100';
    div.innerHTML = `
        <div>
            <div class="text-xs font-semibold text-gray-800">${client}</div>
            <div class="text-xs text-gray-400">${method} · ${new Date().toLocaleTimeString([],{hour:'2-digit',minute:'2-digit'})}</div>
        </div>
        <div class="text-sm font-bold text-green-600">৳${parseFloat(amount).toFixed(2)}</div>`;
    el.insertBefore(div, el.firstChild);
    // Max 5
    while (el.children.length > 5) el.removeChild(el.lastChild);
}

function showToast(msg, type='success') {
    const t = document.createElement('div');
    t.className = `fixed bottom-6 right-6 z-50 px-5 py-3 rounded-xl shadow-lg text-white text-sm font-medium flex items-center gap-2
        ${type==='success' ? 'bg-green-600' : 'bg-red-600'}`;
    t.innerHTML = `<i class="fas fa-${type==='success'?'check-circle':'exclamation-circle'}"></i>${msg}`;
    document.body.appendChild(t);
    setTimeout(() => t.remove(), 4000);
}
</script>
</body>
</html>