<?php
// PATH: /pages/select-payment-method.php
//
// PUBLIC page (no authenticate.php, same token model as pay-invoice.php) --
// shown after the client clicks "Pay Now", BEFORE going to EPS. Lets them
// pick a payment method and see the surcharge for each option upfront,
// since we can't know which method they'll use (and therefore which
// charge applies) until they're on EPS's own page -- so we ask first, on
// our own page, and compute the final amount here instead.

require __DIR__ . '/../server/db_connection.php';

$invoiceId = $_GET['id'] ?? '';
$token     = $_GET['token'] ?? '';

if (!$invoiceId || !$token) {
    http_response_code(400);
    die('Invalid or missing payment link.');
}

$stmt = $pdo->prepare("SELECT * FROM invoices WHERE sys_id = :sys_id AND public_token = :token");
$stmt->execute([':sys_id' => $invoiceId, ':token' => $token]);
$invoice = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$invoice) {
    http_response_code(403);
    die('This payment link is invalid or has expired.');
}

$dueAmount = (float)($invoice['due_amount'] ?? 0);
if ($dueAmount <= 0) {
    die('This invoice has no outstanding due amount.');
}

$rates = $pdo->query("SELECT * FROM gateway_charge_rates WHERE is_active = 1 ORDER BY payment_method_type ASC, financial_entity_name ASC")->fetchAll(PDO::FETCH_ASSOC);

// Group into one representative option per payment_method_type, using its
// default rate (financial_entity_name IS NULL) as the headline figure --
// specific bank/wallet rates under the same type are shown as a note, since
// which exact bank/wallet the client has isn't known until EPS's own page.
$methodLabels = ['card' => 'Credit/Debit Card', 'mfs' => 'Mobile Banking (bKash/Nagad/Rocket)', 'bank' => 'Internet Banking', 'other' => 'Other'];
$byType = [];
foreach ($rates as $r) {
    $byType[$r['payment_method_type']][] = $r;
}

function safe($v) { return htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8'); }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Select Payment Method — TravHub</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-50 min-h-screen py-8 px-4">
<div class="max-w-lg mx-auto">
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="p-6 border-b border-gray-100">
            <h1 class="text-lg font-bold text-gray-900">Select Payment Method</h1>
            <p class="text-sm text-gray-500 mt-1">Invoice <?php echo safe($invoice['sys_id']); ?> — Due: ৳<?php echo number_format($dueAmount, 2); ?></p>
        </div>

        <div class="p-4 space-y-3" id="method-list">
            <?php foreach ($methodLabels as $type => $label):
                $defaultRate = null;
                $specificRates = [];
                foreach (($byType[$type] ?? []) as $r) {
                    if ($r['financial_entity_name'] === null) $defaultRate = $r;
                    else $specificRates[] = $r;
                }
                if (!$defaultRate && empty($specificRates)) continue; // no configured rate for this method at all -- don't show it
                $chargePercent = $defaultRate ? (float)$defaultRate['charge_percent'] : 0;
                $borneBy = $defaultRate ? $defaultRate['borne_by'] : 'client';
                $finalAmount = $borneBy === 'client' ? round($dueAmount * (1 + $chargePercent / 100), 2) : $dueAmount;
            ?>
            <label class="flex items-center justify-between p-4 border border-gray-200 rounded-xl cursor-pointer hover:border-indigo-400 transition has-[:checked]:border-indigo-500 has-[:checked]:bg-indigo-50">
                <div class="flex items-center gap-3">
                    <input type="radio" name="method" value="<?php echo safe($type); ?>"
                           data-charge-percent="<?php echo $chargePercent; ?>" data-borne-by="<?php echo safe($borneBy); ?>"
                           class="w-4 h-4 text-indigo-600" onchange="updateTotal()">
                    <div>
                        <p class="text-sm font-semibold text-gray-800"><?php echo safe($label); ?></p>
                        <?php if ($chargePercent > 0): ?>
                            <p class="text-xs text-gray-400"><?php echo $borneBy === 'client' ? "+{$chargePercent}% gateway charge" : 'No extra charge — covered by us'; ?></p>
                        <?php else: ?>
                            <p class="text-xs text-gray-400">No extra charge</p>
                        <?php endif; ?>
                    </div>
                </div>
                <span class="text-sm font-semibold text-gray-700">৳<?php echo number_format($finalAmount, 2); ?></span>
            </label>
            <?php endforeach; ?>
        </div>

        <div class="p-6 border-t border-gray-100">
            <div class="flex justify-between text-sm mb-4">
                <span class="text-gray-500">Total to pay</span>
                <span class="font-bold text-gray-900" id="total-display">৳<?php echo number_format($dueAmount, 2); ?></span>
            </div>
            <button onclick="proceedToPayment()" id="proceed-btn" disabled
                class="block w-full py-3.5 bg-gray-300 text-white text-center rounded-xl font-semibold transition cursor-not-allowed">
                Select a method to continue
            </button>
        </div>
    </div>
</div>

<script>
const DUE_AMOUNT = <?php echo $dueAmount; ?>;
const INVOICE_ID = <?php echo json_encode($invoiceId); ?>;
const TOKEN = <?php echo json_encode($token); ?>;

function updateTotal() {
    const selected = document.querySelector('input[name="method"]:checked');
    const btn = document.getElementById('proceed-btn');
    if (!selected) return;

    const chargePercent = parseFloat(selected.dataset.chargePercent) || 0;
    const borneBy = selected.dataset.borneBy;
    const total = borneBy === 'client' ? DUE_AMOUNT * (1 + chargePercent / 100) : DUE_AMOUNT;

    document.getElementById('total-display').textContent = '৳' + total.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
    btn.disabled = false;
    btn.textContent = 'Proceed to Payment';
    btn.className = 'block w-full py-3.5 bg-indigo-600 hover:bg-indigo-700 text-white text-center rounded-xl font-semibold transition';
}

function proceedToPayment() {
    const selected = document.querySelector('input[name="method"]:checked');
    if (!selected) return;
    const params = new URLSearchParams({
        invoice_id: INVOICE_ID, token: TOKEN, payment_method_type: selected.value,
    });
    window.location.href = '../api/epsgw/initiate.php?' + params.toString();
}
</script>
</body>
</html>