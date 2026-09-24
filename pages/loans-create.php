<?php
include_once('./authenticate.php');
require_once '../server/db_connection.php';
require_once '../server/permissions.php';
requireFullAccountingAccess($pdo, false);
$ip_port = trim(@file_get_contents('../ippath.txt') ?: 'http://103.104.219.3:898', '/');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Loan — TravHub</title>
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
<div class="p-6 max-w-2xl mx-auto">

    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900"><i class="fas fa-hand-holding-dollar text-teal-600 mr-2"></i>Create Loan</h1>
        <p class="text-sm text-gray-500 mt-1">Company, employee, vendor, or client — any lender/borrower combination</p>
    </div>

    <div class="bg-white rounded-xl border border-gray-200 p-6 space-y-5">

        <!-- Lender / Borrower -->
        <div class="grid grid-cols-2 gap-4">
            <div class="p-4 bg-gray-50 rounded-lg">
                <h3 class="text-xs font-semibold text-gray-500 uppercase mb-2">Lender (who gives)</h3>
                <label class="block text-xs font-medium text-gray-700 mb-1">Type</label>
                <select id="f-lender-type" onchange="onPartyTypeChange('lender')" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm mb-2">
                    <option value="company">Company</option>
                    <option value="employee">Employee</option>
                    <option value="vendor">Vendor</option>
                    <option value="client">Client</option>
                </select>
                <div id="f-lender-party-wrap" class="hidden">
                    <label class="block text-xs font-medium text-gray-700 mb-1">Select</label>
                    <div class="relative">
                        <input id="f-lender-search" placeholder="Search…" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm" autocomplete="off" oninput="filterParty('lender', this.value)" onfocus="filterParty('lender', this.value)">
                        <ul id="f-lender-drop" class="absolute w-full bg-white border border-gray-200 rounded-lg mt-1 max-h-40 overflow-auto shadow-xl hidden z-50"></ul>
                    </div>
                    <input type="hidden" id="f-lender-id">
                    <input type="hidden" id="f-lender-name">
                </div>
            </div>
            <div class="p-4 bg-gray-50 rounded-lg">
                <h3 class="text-xs font-semibold text-gray-500 uppercase mb-2">Borrower (who takes)</h3>
                <label class="block text-xs font-medium text-gray-700 mb-1">Type</label>
                <select id="f-borrower-type" onchange="onPartyTypeChange('borrower')" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm mb-2">
                    <option value="employee">Employee</option>
                    <option value="company">Company</option>
                    <option value="vendor">Vendor</option>
                    <option value="client">Client</option>
                </select>
                <div id="f-borrower-party-wrap" class="hidden">
                    <label class="block text-xs font-medium text-gray-700 mb-1">Select</label>
                    <div class="relative">
                        <input id="f-borrower-search" placeholder="Search…" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm" autocomplete="off" oninput="filterParty('borrower', this.value)" onfocus="filterParty('borrower', this.value)">
                        <ul id="f-borrower-drop" class="absolute w-full bg-white border border-gray-200 rounded-lg mt-1 max-h-40 overflow-auto shadow-xl hidden z-50"></ul>
                    </div>
                    <input type="hidden" id="f-borrower-id">
                    <input type="hidden" id="f-borrower-name">
                </div>
            </div>
        </div>

        <!-- Principal / Interest -->
        <div class="grid grid-cols-2 gap-3">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">Principal Amount ৳ <span class="text-red-500">*</span></label>
                <input type="number" step="0.01" min="0.01" id="f-principal" class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">Interest Rate % <span class="text-gray-400 font-normal">(optional)</span></label>
                <input type="number" step="0.01" min="0" id="f-interest-rate" placeholder="0 = interest-free" class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm" oninput="toggleInterestMethod()">
            </div>
        </div>
        <div id="f-interest-method-wrap" class="hidden">
            <label class="block text-sm font-medium text-gray-700 mb-1.5">Interest Method</label>
            <select id="f-interest-method" class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm">
                <option value="reducing_balance">Reducing Balance</option>
                <option value="flat">Flat Rate</option>
            </select>
        </div>

        <!-- Repayment Type -->
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1.5">Repayment Type</label>
            <div class="flex gap-2">
                <button type="button" id="f-repay-emi-btn" onclick="setRepaymentType('emi')" class="flex-1 py-2 rounded-lg text-sm font-medium bg-teal-600 text-white">Fixed EMI</button>
                <button type="button" id="f-repay-free-btn" onclick="setRepaymentType('free_form')" class="flex-1 py-2 rounded-lg text-sm font-medium bg-gray-200 text-gray-700">Free-form</button>
            </div>
        </div>

        <div id="f-emi-fields" class="space-y-3 p-4 bg-teal-50 rounded-lg">
            <div>
                <label class="block text-xs font-medium text-gray-700 mb-1">EMI Calculation</label>
                <div class="flex gap-2">
                    <button type="button" id="f-emi-auto-btn" onclick="setEmiMode('auto')" class="flex-1 py-1.5 rounded-lg text-xs font-medium bg-teal-600 text-white">Auto (rate + tenure)</button>
                    <button type="button" id="f-emi-manual-btn" onclick="setEmiMode('manual')" class="flex-1 py-1.5 rounded-lg text-xs font-medium bg-gray-200 text-gray-700">Manual amount</button>
                </div>
            </div>
            <div id="f-emi-auto-fields">
                <label class="block text-xs font-medium text-gray-700 mb-1">Tenure (months)</label>
                <input type="number" min="1" id="f-tenure" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
            </div>
            <div id="f-emi-manual-fields" class="hidden">
                <label class="block text-xs font-medium text-gray-700 mb-1">EMI Amount ৳ (per installment)</label>
                <input type="number" step="0.01" min="0.01" id="f-emi-amount" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                <label class="block text-xs font-medium text-gray-700 mb-1 mt-2">Number of Installments</label>
                <input type="number" min="1" id="f-manual-tenure" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
            </div>
        </div>

        <!-- Disbursement -->
        <div class="p-4 bg-gray-50 rounded-lg space-y-3">
            <label class="flex items-center gap-2 text-sm font-medium text-gray-700">
                <input type="checkbox" id="f-disburse-now" checked onchange="toggleDisburseFields()"> Disburse now (money moves immediately)
            </label>
            <div id="f-disburse-fields" class="space-y-3">
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1">Own Account</label>
                    <div class="relative">
                        <input id="f-account-search" placeholder="Search for an account…" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm" autocomplete="off" oninput="filterAccount(this.value)" onfocus="filterAccount(this.value)">
                        <ul id="f-account-drop" class="absolute w-full bg-white border border-gray-200 rounded-lg mt-1 max-h-40 overflow-auto shadow-xl hidden z-50"></ul>
                    </div>
                    <input type="hidden" id="f-account-id">
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">Payment Method</label>
                        <select id="f-payment-method" onchange="toggleInstrumentField()" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                            <option value="cash">Cash</option><option value="npsb">NPSB</option><option value="rtgs">RTGS</option>
                            <option value="bftn">BFTN</option><option value="eft">EFT</option><option value="cheque">Cheque</option>
                        </select>
                    </div>
                    <div id="f-instrument-wrap" class="hidden">
                        <label class="block text-xs font-medium text-gray-700 mb-1">Cheque/Instrument No.</label>
                        <input type="text" id="f-instrument-no" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1">Disbursement Date</label>
                    <input type="date" id="f-disbursement-date" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                </div>
            </div>
        </div>

        <button onclick="submitLoan()" id="submitBtn" class="w-full py-3 bg-teal-600 hover:bg-teal-700 text-white rounded-lg text-sm font-semibold transition">
            <i class="fas fa-check mr-1.5"></i>Create Loan
        </button>
    </div>

</div>
</main>

<script>
const API_LOAN_STORE = "<?php echo $ip_port; ?>/api/loans/store.php";
const API_ALL_ACCOUNTS = "<?php echo $ip_port; ?>/api/accounts/all-trxnable-accounts.php";
const API_ALL_EMPLOYEES = "<?php echo $ip_port; ?>/api/employees/all-employees.php"; // adjust if the actual endpoint differs
const API_ALL_VENDORS = "<?php echo $ip_port; ?>/api/vendors/all-vendors.php"; // adjust if the actual endpoint differs
const API_ALL_CLIENTS = "<?php echo $ip_port; ?>/api/clients/all-clients.php"; // adjust if the actual endpoint differs

document.getElementById('f-disbursement-date').value = new Date().toISOString().slice(0,10);

let repaymentType = 'emi';
let emiMode = 'auto';
let _accounts = [];
let _partyLists = { employee: [], vendor: [], client: [] };

function onPartyTypeChange(side) {
    const type = document.getElementById(`f-${side}-type`).value;
    const wrap = document.getElementById(`f-${side}-party-wrap`);
    wrap.classList.toggle('hidden', type === 'company');
    document.getElementById(`f-${side}-id`).value = type === 'company' ? 'COMPANY' : '';
    document.getElementById(`f-${side}-name`).value = type === 'company' ? 'TravHub Global Limited' : '';
    if (type !== 'company') document.getElementById(`f-${side}-search`).value = '';
}

async function loadPartyList(type) {
    if (_partyLists[type] && _partyLists[type].length) return;
    const url = type === 'employee' ? API_ALL_EMPLOYEES : type === 'vendor' ? API_ALL_VENDORS : API_ALL_CLIENTS;
    try {
        const r = await fetch(url);
        const j = await r.json();
        _partyLists[type] = j.employees ?? j.vendors ?? j.clients ?? j.data ?? [];
    } catch(e) { _partyLists[type] = []; }
}

async function filterParty(side, q) {
    const type = document.getElementById(`f-${side}-type`).value;
    if (type === 'company') return;
    await loadPartyList(type);
    const dd = document.getElementById(`f-${side}-drop`);
    const v = q.toLowerCase().trim();
    const list = _partyLists[type].filter(p => (p.name||p.title||'').toLowerCase().includes(v)).slice(0, 15);
    if (!list.length) { dd.innerHTML = `<li class="px-3 py-2 text-center text-gray-400 text-xs">কিছু পাওয়া যায়নি</li>`; dd.classList.remove('hidden'); return; }
    dd.innerHTML = list.map(p => `<li class="px-3 py-2 cursor-pointer hover:bg-teal-50 border-b last:border-b-0 text-xs" data-id="${p.sys_id||p.id}" data-name="${(p.name||p.title||'').replace(/"/g,'&quot;')}">${p.name||p.title}</li>`).join('');
    dd.classList.remove('hidden');
    dd.querySelectorAll('li[data-id]').forEach(li => li.addEventListener('click', () => {
        document.getElementById(`f-${side}-search`).value = li.dataset.name;
        document.getElementById(`f-${side}-id`).value = li.dataset.id;
        document.getElementById(`f-${side}-name`).value = li.dataset.name;
        dd.classList.add('hidden');
    }));
}

function toggleInterestMethod() {
    const rate = parseFloat(document.getElementById('f-interest-rate').value) || 0;
    document.getElementById('f-interest-method-wrap').classList.toggle('hidden', rate <= 0);
}

function setRepaymentType(type) {
    repaymentType = type;
    document.getElementById('f-repay-emi-btn').className = `flex-1 py-2 rounded-lg text-sm font-medium ${type==='emi'?'bg-teal-600 text-white':'bg-gray-200 text-gray-700'}`;
    document.getElementById('f-repay-free-btn').className = `flex-1 py-2 rounded-lg text-sm font-medium ${type==='free_form'?'bg-teal-600 text-white':'bg-gray-200 text-gray-700'}`;
    document.getElementById('f-emi-fields').classList.toggle('hidden', type !== 'emi');
}

function setEmiMode(mode) {
    emiMode = mode;
    document.getElementById('f-emi-auto-btn').className = `flex-1 py-1.5 rounded-lg text-xs font-medium ${mode==='auto'?'bg-teal-600 text-white':'bg-gray-200 text-gray-700'}`;
    document.getElementById('f-emi-manual-btn').className = `flex-1 py-1.5 rounded-lg text-xs font-medium ${mode==='manual'?'bg-teal-600 text-white':'bg-gray-200 text-gray-700'}`;
    document.getElementById('f-emi-auto-fields').classList.toggle('hidden', mode !== 'auto');
    document.getElementById('f-emi-manual-fields').classList.toggle('hidden', mode !== 'manual');
}

function toggleDisburseFields() {
    document.getElementById('f-disburse-fields').classList.toggle('hidden', !document.getElementById('f-disburse-now').checked);
}
function toggleInstrumentField() {
    document.getElementById('f-instrument-wrap').classList.toggle('hidden', document.getElementById('f-payment-method').value !== 'cheque');
}

async function loadAccounts() {
    if (_accounts.length) return;
    try { const r = await fetch(API_ALL_ACCOUNTS); const j = await r.json(); _accounts = j.accounts ?? []; } catch(e) {}
}
async function filterAccount(q) {
    await loadAccounts();
    const dd = document.getElementById('f-account-drop');
    const v = q.toLowerCase().trim();
    const list = v ? _accounts.filter(a => (a.acc_name||'').toLowerCase().includes(v)) : _accounts.slice(0, 15);
    if (!list.length) { dd.innerHTML = `<li class="px-3 py-2 text-center text-gray-400 text-xs">কিছু পাওয়া যায়নি</li>`; dd.classList.remove('hidden'); return; }
    dd.innerHTML = list.map(a => `<li class="px-3 py-2 cursor-pointer hover:bg-teal-50 border-b last:border-b-0 text-xs" data-id="${a.sys_id}" data-name="${(a.acc_name||'').replace(/"/g,'&quot;')}">${a.acc_name}</li>`).join('');
    dd.classList.remove('hidden');
    dd.querySelectorAll('li[data-id]').forEach(li => li.addEventListener('click', () => {
        document.getElementById('f-account-search').value = li.dataset.name;
        document.getElementById('f-account-id').value = li.dataset.id;
        dd.classList.add('hidden');
    }));
}

async function submitLoan() {
    const lenderType = document.getElementById('f-lender-type').value;
    const lenderId = document.getElementById('f-lender-id').value;
    const lenderName = document.getElementById('f-lender-name').value;
    const borrowerType = document.getElementById('f-borrower-type').value;
    const borrowerId = document.getElementById('f-borrower-id').value;
    const borrowerName = document.getElementById('f-borrower-name').value;
    const principal = parseFloat(document.getElementById('f-principal').value);
    const interestRate = parseFloat(document.getElementById('f-interest-rate').value) || 0;
    const interestMethod = document.getElementById('f-interest-method').value;
    const disburseNow = document.getElementById('f-disburse-now').checked;
    const accountId = document.getElementById('f-account-id').value;
    const paymentMethod = document.getElementById('f-payment-method').value;
    const instrumentNo = document.getElementById('f-instrument-no').value.trim();
    const disbursementDate = document.getElementById('f-disbursement-date').value;

    if (!lenderId) { alert('Lender সিলেক্ট করুন'); return; }
    if (!borrowerId) { alert('Borrower সিলেক্ট করুন'); return; }
    if (!principal || principal <= 0) { alert('সঠিক Principal Amount দিন'); return; }
    const isCompanyInvolved = lenderType === 'company' || borrowerType === 'company';
    if (disburseNow && isCompanyInvolved && !accountId) { alert('Account সিলেক্ট করুন'); return; }

    const payload = {
        lender_type: lenderType, lender_id: lenderId, lender_name: lenderName,
        borrower_type: borrowerType, borrower_id: borrowerId, borrower_name: borrowerName,
        principal_amount: principal,
        interest_rate: interestRate || undefined,
        interest_method: interestRate > 0 ? interestMethod : undefined,
        repayment_type: repaymentType,
        disbursement_date: disbursementDate,
        disburse_now: disburseNow,
        account_id: accountId || undefined,
        payment_method: paymentMethod,
        instrument_no: instrumentNo || undefined,
    };

    if (repaymentType === 'emi') {
        payload.emi_calculation_mode = emiMode;
        if (emiMode === 'auto') {
            payload.tenure_months = parseInt(document.getElementById('f-tenure').value);
            if (!payload.tenure_months) { alert('Tenure (months) দিন'); return; }
        } else {
            payload.emi_amount = parseFloat(document.getElementById('f-emi-amount').value);
            payload.tenure_months = parseInt(document.getElementById('f-manual-tenure').value) || undefined;
            if (!payload.emi_amount) { alert('EMI Amount দিন'); return; }
        }
    }

    const btn = document.getElementById('submitBtn');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-1.5"></i>Creating…';

    try {
        const res = await fetch(API_LOAN_STORE, { method: 'POST', headers: {'Content-Type':'application/json'}, body: JSON.stringify(payload) });
        const json = await res.json();
        if (json.success) {
            alert(json.message + (json.emi_amount ? ` (EMI: ৳${json.emi_amount})` : ''));
            window.location.href = 'loans-list.php';
        } else {
            alert(json.message || (json.errors ? json.errors.join('\n') : 'Failed to create loan'));
        }
    } catch(e) {
        alert('Network error');
    } finally {
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-check mr-1.5"></i>Create Loan';
    }
}

document.addEventListener('DOMContentLoaded', () => {
    onPartyTypeChange('lender');
    onPartyTypeChange('borrower');
});
</script>
</body>
</html>