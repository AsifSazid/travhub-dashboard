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
    <title>Record Asset Purchase — TravHub</title>
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
        <h1 class="text-2xl font-bold text-gray-900"><i class="fas fa-desktop text-blue-600 mr-2"></i>Record Fixed Asset Purchase</h1>
        <p class="text-sm text-gray-500 mt-1">Laptop, furniture, machinery — anything the office owns long-term</p>
    </div>

    <div class="bg-white rounded-xl border border-gray-200 p-6 space-y-4">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1.5">Asset Category <span class="text-red-500">*</span></label>
            <select id="f-asset-account" class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-400">
                <option value="">Loading categories…</option>
            </select>
            <p class="text-xs text-gray-400 mt-1">Doesn't exist yet? <a href="create-accounts.php" class="text-blue-600 hover:underline">Create a new Fixed Assets-category account</a> first.</p>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1.5">Paid From <span class="text-red-500">*</span></label>
            <select id="f-payment-account" class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-400">
                <option value="">Loading accounts…</option>
            </select>
            <p class="text-xs text-gray-400 mt-1">Which Bank/Cash/Petty Cash account the money is actually leaving.</p>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1.5">Purpose <span class="text-red-500">*</span></label>
            <textarea id="f-purpose" rows="2" placeholder="e.g., MacBook for Design team" class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-400"></textarea>
        </div>

        <div class="grid grid-cols-2 gap-3">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">Amount ৳ <span class="text-red-500">*</span></label>
                <input type="number" step="0.01" min="0.01" id="f-amount" placeholder="0.00" class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-400">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">Date</label>
                <input type="date" id="f-date" class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-400">
            </div>
        </div>

<div class="grid grid-cols-2 gap-3">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">Payment Method</label>
                <select id="f-payment-method" onchange="toggleInstrumentField()" class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-400">
                    <option value="cash">Cash</option>
                    <option value="npsb">NPSB</option>
                    <option value="rtgs">RTGS</option>
                    <option value="bftn">BFTN</option>
                    <option value="eft">EFT</option>
                    <option value="cheque">Cheque</option>
                </select>
            </div>
            <div id="f-instrument-wrap" class="hidden">
                <label class="block text-sm font-medium text-gray-700 mb-1.5">Cheque/Instrument No.</label>
                <input type="text" id="f-instrument-no" placeholder="Cheque number" class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-400">
            </div>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1.5">Note <span class="text-gray-400 font-normal">(optional)</span></label>
            <input type="text" id="f-note" placeholder="Any additional note…" class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-400">
        </div>

        <button onclick="submitAsset()" id="submitBtn" class="w-full py-3 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-sm font-semibold transition">
            <i class="fas fa-check mr-1.5"></i>Record Asset Purchase
        </button>
    </div>

</div>
</main>

<script>
const API_ASSET_ACCOUNTS = "<?php echo $ip_port; ?>/api/accounts/expense-asset-accounts.php?type=asset";
const API_PAYMENT_ACCOUNTS = "<?php echo $ip_port; ?>/api/accounts/all-trxnable-accounts.php";
const API_STORE_ASSET    = "<?php echo $ip_port; ?>/api/financial_entries_v2/store-asset.php";

document.getElementById('f-date').value = new Date().toISOString().slice(0,10);

async function loadOptions() {
    try {
        const [expRes, payRes] = await Promise.all([fetch(API_ASSET_ACCOUNTS), fetch(API_PAYMENT_ACCOUNTS)]);
        const astJson = await expRes.json();
        const payJson = await payRes.json();

        const astSel = document.getElementById('f-asset-account');
        astSel.innerHTML = '<option value="">Select a category…</option>' +
            (astJson.accounts||[]).map(a => `<option value="${a.sys_id}">${a.acc_name} (${a.category})</option>`).join('');

        const paySel = document.getElementById('f-payment-account');
        paySel.innerHTML = '<option value="">Select an account…</option>' +
            (payJson.accounts||[]).map(a => `<option value="${a.sys_id}">${a.acc_name}</option>`).join('');
    } catch(e) {
        alert('Failed to load account options');
    }
}

function toggleInstrumentField() {
    const method = document.getElementById('f-payment-method').value;
    document.getElementById('f-instrument-wrap').classList.toggle('hidden', method !== 'cheque');
}

async function submitAsset() {
    const assetAccountId = document.getElementById('f-asset-account').value;
    const paymentAccountId = document.getElementById('f-payment-account').value;
    const purpose = document.getElementById('f-purpose').value.trim();
    const amount = parseFloat(document.getElementById('f-amount').value);
    const date = document.getElementById('f-date').value;
    const note = document.getElementById('f-note').value.trim();
    const paymentMethod = document.getElementById('f-payment-method').value;
    const instrumentNo = document.getElementById('f-instrument-no').value.trim();

    if (!assetAccountId) { alert('Select an Asset Category'); return; }
    if (!paymentAccountId) { alert('Select where the money is paid from'); return; }
    if (!purpose) { alert('Purpose is required'); return; }
    if (!amount || amount <= 0) { alert('Enter a valid amount'); return; }
    if (paymentMethod === 'cheque' && !instrumentNo) { alert('Enter the cheque number'); return; }

    const btn = document.getElementById('submitBtn');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-1.5"></i>Saving…';

    try {
        const res = await fetch(API_STORE_ASSET, {
            method: 'POST', headers: {'Content-Type':'application/json'},
            body: JSON.stringify({ asset_account_id: assetAccountId, payment_account_id: paymentAccountId, purpose, amount, date, ref: note || undefined, payment_method: paymentMethod, instrument_no: instrumentNo || undefined }),
        });
        const json = await res.json();
        if (json.success) {
            alert('Asset purchase recorded successfully');
            document.getElementById('f-purpose').value = '';
            document.getElementById('f-amount').value = '';
            document.getElementById('f-note').value = '';
        } else {
            alert(json.message || 'Failed to record asset purchase');
        }
    } catch(e) {
        alert('Network error');
    } finally {
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-check mr-1.5"></i>Record Asset Purchase';
    }
}

document.addEventListener('DOMContentLoaded', loadOptions);
</script>
<script src="../assets/js/script.js?time=<?php echo time(); ?>"></script>
</body>
</html>