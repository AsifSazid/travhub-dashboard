<?php
// POST /api/transactions/invest.php
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

// var_dump($input);
// die;

if (!$input) {
    sendError('Invalid JSON body');
}

// Validation
$director_sys_id = trim($input['director_id'] ?? '');
$amount          = (float)($input['amount'] ?? 0);
$note            = trim($input['note'] ?? 'Investment');

if (empty($director_sys_id)) sendError('director_sys_id is required');
if ($amount <= 0) sendError('Amount must be greater than 0');

$db = $pdo;

try {
    // 1. Verify director exists and get current status
    $stmt = $db->prepare("SELECT sys_id FROM directors WHERE sys_id = ? AND status = 'active' LIMIT 1");
    $stmt->execute([$director_sys_id]);
    $director = $stmt->fetch();

    if (!$director) {
        sendError('Active director not found', 404);
    }

    $db->beginTransaction();

    // 2. Generate Metadata and IDs
    $meta = buildMetaData(null, $_SESSION['user_name'] ?? 'system');
    $tranxIdData = generateIDs('director_transactions');

    // 3. Insert into director_transactions
    $insTranx = $db->prepare("
        INSERT INTO director_transactions (uuid, sys_id, director_sys_id, type, amount, note, meta_data) 
        VALUES (?, ?, ?, 'invest', ?, ?, ?)
    ");
    $insTranx->execute([
        $tranxIdData['uuid'], 
        $tranxIdData['sys_id'], 
        $director_sys_id, 
        $amount, 
        $note, 
        json_encode($meta)
    ]);

    // 4. Calculate New Balance and Percentage
    // Prothome current total investment ber kora (transactions table theke)
    $stmtBal = $db->prepare("
        SELECT SUM(CASE WHEN type = 'invest' THEN amount ELSE -amount END) as total 
        FROM director_transactions 
        WHERE director_sys_id = ?
    ");
    $stmtBal->execute([$director_sys_id]);
    $newTotalInvestment = (float) $stmtBal->fetchColumn();

    // Ownership percentage calculate kora (external function theke)
    $newPercentage = calcOwnership($newTotalInvestment);

    // 5. Update director_balances table
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
        'message'            => 'Investment recorded successfully'
    ], 201);

} catch (Exception $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    sendError('Transaction failed: ' . $e->getMessage(), 500);
}