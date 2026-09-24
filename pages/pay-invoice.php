<?php
// PATH: /pages/pay-invoice.php
//
// PUBLIC invoice + payment page — deliberately does NOT include
// authenticate.php. Access is controlled entirely by the invoice_id +
// public_token pair in the URL (the same model as a password-reset link):
// unguessable, single-invoice-scoped, no login required. This is what the
// QR code on the printed invoice PDF points to.

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

$clientInfo = json_decode($invoice['client_info'] ?? '{}', true);
$workItems  = json_decode($invoice['work_items'] ?? '[]', true);

$totalAmount = (float)$invoice['total_amount'];
$paidAmount  = (float)$invoice['paid_amount'];
$dueAmount   = (float)$invoice['due_amount'];
$statusLabel = $dueAmount <= 0.009 ? 'Paid' : ($paidAmount > 0 ? 'Partially Paid' : 'Unpaid');
$statusColor = $dueAmount <= 0.009 ? '#059669' : ($paidAmount > 0 ? '#d97706' : '#dc2626');

function safe($v) { return htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8'); }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice <?php echo safe($invoice['sys_id']); ?> — TravHub</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-50 min-h-screen py-8 px-4">
<div class="max-w-2xl mx-auto">

    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="p-6 border-b border-gray-100 flex items-start justify-between">
            <div>
                <h1 class="text-xl font-bold text-gray-900">Invoice <?php echo safe($invoice['sys_id']); ?></h1>
                <p class="text-sm text-gray-500 mt-1"><?php echo safe($clientInfo['name'] ?? 'Valued Customer'); ?></p>
            </div>
            <span class="px-3 py-1.5 rounded-full text-xs font-semibold text-white" style="background:<?php echo $statusColor; ?>;"><?php echo $statusLabel; ?></span>
        </div>

        <?php if (!empty($workItems) && is_array($workItems)): ?>
        <div class="p-6 border-b border-gray-100">
            <h2 class="text-xs font-semibold text-gray-500 uppercase mb-3">Items</h2>
            <div class="space-y-2">
                <?php foreach ($workItems as $item): ?>
                <div class="flex justify-between text-sm">
                    <span class="text-gray-700"><?php echo safe($item['title'] ?? $item['description'] ?? 'Item'); ?></span>
                    <span class="font-medium text-gray-800">৳<?php echo number_format((float)($item['amount'] ?? 0), 2); ?></span>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <div class="p-6 space-y-2 border-b border-gray-100">
            <div class="flex justify-between text-sm"><span class="text-gray-500">Total Amount</span><span class="font-medium text-gray-800">৳<?php echo number_format($totalAmount, 2); ?></span></div>
            <div class="flex justify-between text-sm"><span class="text-gray-500">Paid</span><span class="font-medium text-emerald-600">৳<?php echo number_format($paidAmount, 2); ?></span></div>
            <div class="flex justify-between text-base font-bold pt-2 border-t border-gray-100"><span class="text-gray-800">Due</span><span class="text-rose-600">৳<?php echo number_format($dueAmount, 2); ?></span></div>
        </div>

        <div class="p-6">
            <?php if ($dueAmount > 0.009): ?>
                <a href="../api/epsgw/initiate.php?invoice_id=<?php echo urlencode($invoiceId); ?>&token=<?php echo urlencode($token); ?>"
                   class="block w-full py-3.5 bg-indigo-600 hover:bg-indigo-700 text-white text-center rounded-xl font-semibold transition">
                    <i class="fas fa-lock mr-2"></i>Pay ৳<?php echo number_format($dueAmount, 2); ?> Now
                </a>
                <p class="text-center text-xs text-gray-400 mt-3">Secured by EPS Payment Gateway</p>
            <?php else: ?>
                <div class="text-center py-4">
                    <i class="fas fa-circle-check text-4xl text-emerald-500 mb-3"></i>
                    <p class="text-gray-700 font-medium">This invoice has been fully paid. Thank you!</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <p class="text-center text-xs text-gray-400 mt-6">If you believe this link is incorrect, please contact us directly.</p>
</div>
</body>
</html>