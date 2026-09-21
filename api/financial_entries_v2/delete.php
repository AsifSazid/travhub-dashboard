<?php
// (v2 -- separate namespace from legacy /api/financial_entries/, used only by the new Task View)
// PATH: /api/financial_entries_v2/delete.php
// POST { id: <financial_entries.id or sys_id> }
//
// Deletes an ENTIRE transaction group (every leg sharing the anchor row's
// transaction_group_id), not just the one row passed in. This is required
// for double-entry integrity -- deleting only a "purchase" leg while leaving
// its matching "accounts_payable" leg behind would silently corrupt the
// vendor's payable balance. Reverses every bank leg's ac_banking balance and
// removes its ac_banking_stmts row before deleting the financial_entries rows.

session_start();

require '../../server/db_connection.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

try {
    $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
    $transactionId = $input['id'] ?? '';

    if (empty($transactionId)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Transaction id is required']);
        exit;
    }

    $anchorStmt = $pdo->prepare("SELECT * FROM financial_entries WHERE id = ? OR sys_id = ?");
    $anchorStmt->execute([$transactionId, $transactionId]);
    $anchor = $anchorStmt->fetch(PDO::FETCH_ASSOC);

    if (!$anchor) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Transaction not found']);
        exit;
    }

    $groupId = $anchor['transaction_group_id'] ?: $anchor['sys_id'];
    $legsStmt = $pdo->prepare("SELECT * FROM financial_entries WHERE transaction_group_id = ?");
    $legsStmt->execute([$groupId]);
    $legs = $legsStmt->fetchAll(PDO::FETCH_ASSOC);
    if (!$legs) $legs = [$anchor]; // pre-migration row with no group match

    $deletedSysIds = [];

    foreach ($legs as $leg) {
        // Reverse bank effect for every bank leg in the group.
        if ($leg['account_head'] === 'bank_account' && $leg['user_sys_id']) {
            $s = $pdo->prepare("SELECT balance FROM ac_banking WHERE sys_id = ?");
            $s->execute([$leg['user_sys_id']]);
            $curBalance = $s->fetchColumn();

            if ($curBalance !== false) {
                $curBalance = (float)$curBalance;
                $amount = (float)$leg['amount'];
                // credit meant money left (withdraw) -> reverse by adding back
                // debit meant money entered (deposit) -> reverse by subtracting
                $reversedBalance = ($leg['type'] === 'credit')
                    ? $curBalance + $amount
                    : $curBalance - $amount;

                $pdo->prepare("UPDATE ac_banking SET balance = ? WHERE sys_id = ?")
                    ->execute([$reversedBalance, $leg['user_sys_id']]);
            }

            $pdo->prepare("DELETE FROM ac_banking_stmts WHERE ref = ?")
                ->execute([$leg['sys_id']]);
        }

        $deletedSysIds[] = $leg['sys_id'];
    }

    $pdo->prepare("DELETE FROM financial_entries WHERE transaction_group_id = ?")
        ->execute([$groupId]);
    // Also catch the pre-migration fallback case (group_id was the anchor's
    // own sys_id and no other row actually shared it).
    if (count($legs) === 1) {
        $pdo->prepare("DELETE FROM financial_entries WHERE sys_id = ?")
            ->execute([$anchor['sys_id']]);
    }

    http_response_code(200);
    echo json_encode([
        'success'              => true,
        'message'              => 'Transaction deleted successfully (' . count($deletedSysIds) . ' linked leg(s))',
        'transaction_group_id' => $groupId,
        'deleted_sys_ids'      => $deletedSysIds,
    ]);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Server error',
        'error'   => $e->getMessage()
    ]);
}