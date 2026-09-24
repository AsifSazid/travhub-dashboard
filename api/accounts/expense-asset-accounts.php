<?php
// PATH: /api/accounts/expense-asset-accounts.php
//
// Lists ac_banking rows in the Expense or Fixed Asset categories -- these
// are classification accounts (e.g. "Office Rent", "Utility Bill", "Office
// Laptop"), NOT payment-source accounts. They are intentionally
// is_transactionable='no' (see all-trxnable-accounts.php for the payment
// side), so this endpoint ignores that flag and filters by category
// instead.
//
// GET ?type=expense   -> category IN ('Expenses','Cost of sales','Other Expense')
// GET ?type=asset      -> category = 'Fixed Assets'
// GET (no type)         -> both

require '../../server/db_connection.php';

header('Content-Type: application/json');

$type = $_GET['type'] ?? '';

$expenseCategories = ['Expenses', 'Cost of sales', 'Other Expense'];
$assetCategories   = ['Fixed Assets'];

try {
    if ($type === 'expense') {
        $categories = $expenseCategories;
    } elseif ($type === 'asset') {
        $categories = $assetCategories;
    } else {
        $categories = array_merge($expenseCategories, $assetCategories);
    }

    $placeholders = implode(',', array_fill(0, count($categories), '?'));
    $stmt = $pdo->prepare("
        SELECT sys_id, acc_name, category, main_type, description
        FROM ac_banking
        WHERE category IN ($placeholders)
        ORDER BY category ASC, acc_name ASC
    ");
    $stmt->execute($categories);
    $accounts = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['accounts' => $accounts, 'success' => true]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
    exit;
}