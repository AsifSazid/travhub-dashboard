<?php
// POST /api/transactions/withdraw.php
session_start();

require_once '../../server/db_connection.php';
require '../../server/uuid_with_system_id_generator.php';
require '../../server/generate_meta_data.php';
require_once '../../server/director-calculation.php';

jsonHeaders();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendError('Method not allowed', 405);
}

$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    sendError('Invalid JSON body');
}

// Validation
$director_sys_id = trim($input['director_id'] ?? '');
$amount          = (float)($input['amount'] ?? 0);
$note            = trim($input['note'] ?? 'Withdrawal');

if (empty($director_sys_id)) sendError('director_sys_id is required');
if ($amount <= 0) sendError('Amount must be greater than 0');

$db = $pdo;

try {
    // 1. Verify director exists and get current balance
    $stmt = $db->prepare("
        SELECT b.total_investment, d.status 
        FROM directors d
        JOIN director_balances b ON d.sys_id = b.director_sys_id COLLATE utf8mb4_unicode_ci
        WHERE d.sys_id = ? LIMIT 1
    ");
    $stmt->execute([$director_sys_id]);
    $directorData = $stmt->fetch();

    if (!$directorData) {
        sendError('Director not found or no balance record exists', 404);
    }

    if ($directorData['status'] !== 'active') {
        sendError('Director account is not active');
    }

    // Optional: Check if withdrawal amount is greater than current balance
    if ($amount > (float)$directorData['total_investment']) {
        sendError('Insufficient balance. Max withdrawal: ' . $directorData['total_investment']);
    }

    $db->beginTransaction();

    // 2. Generate Metadata and IDs
    $meta = buildMetaData(null, $_SESSION['user_name'] ?? 'system');
    $tranxIdData = generateIDs('director_transactions');

    // 3. Insert into director_transactions (Type: withdraw)
    $insTranx = $db->prepare("
        INSERT INTO director_transactions (uuid, sys_id, director_sys_id, type, amount, note, meta_data) 
        VALUES (?, ?, ?, 'withdraw', ?, ?, ?)
    ");
    $insTranx->execute([
        $tranxIdData['uuid'], 
        $tranxIdData['sys_id'], 
        $director_sys_id, 
        $amount, 
        $note, 
        json_encode($meta)
    ]);

    // 4. Calculate New Total Investment
    // Invest (+) hobe, Withdraw (-) hobe
    $stmtBal = $db->prepare("
        SELECT SUM(CASE WHEN type = 'invest' THEN amount ELSE -amount END) as total 
        FROM director_transactions 
        WHERE director_sys_id = ?
    ");
    $stmtBal->execute([$director_sys_id]);
    $newTotalInvestment = (float) $stmtBal->fetchColumn();

    // 5. Update ownership percentage
    $newPercentage = calcOwnership($newTotalInvestment);

    // 6. Update director_balances table
    $updBalance = $db->prepare("
        UPDATE director_balances 
        SET total_investment = ?, 
            total_percentage = ?, 
            meta_data = ? 
        WHERE director_sys_id = ?
    ");
    $updBalance->execute([
        $newTotalInvestment, 
        $newPercentage, 
        json_encode($meta), 
        $director_sys_id
    ]);

    $db->commit();

    sendSuccess([
        'transaction_sys_id' => $tranxIdData['sys_id'],
        'director_sys_id'    => $director_sys_id,
        'amount'             => $amount,
        'total_investment'   => $newTotalInvestment,
        'ownership_percent'  => $newPercentage,
        'message'            => 'Withdrawal recorded successfully'
    ], 201);

} catch (Exception $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    sendError('Transaction failed: ' . $e->getMessage(), 500);
}