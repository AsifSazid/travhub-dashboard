<?php
session_start();
require '../../server/db_connection.php';
require '../../server/uuid_with_system_id_generator.php';
require '../../server/generate_meta_data.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success'=>false,'message'=>'Method Not Allowed']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
if (!$data) {
    http_response_code(400);
    echo json_encode(['success'=>false,'message'=>'Invalid JSON']);
    exit;
}

try {
    $pdo->beginTransaction();

    /* ── Input ── */
    $main_type       = trim($data['accountType']      ?? '');
    $category        = trim($data['accountCategory']  ?? '');
    $name            = trim($data['accountName']      ?? '');
    $transactionable = $data['isTransactionable']     ?? 'no';
    $description     = trim($data['description']      ?? '');
    $openingAmount   = (float)($data['openingAmount'] ?? 0);

    if (!$main_type || !$name) throw new Exception('Account type and name are required');

    /* ── Auto-generate account_code ── */
    $prefix   = _acCodePrefix($main_type, $category);
    $lastStmt = $pdo->prepare("SELECT account_code FROM ac_banking WHERE account_code LIKE ? ORDER BY CAST(account_code AS UNSIGNED) DESC LIMIT 1");
    $lastStmt->execute([$prefix . '%']);
    $last         = $lastStmt->fetchColumn();
    $account_code = $last ? (string)((int)$last + 1) : $prefix . '001';

    /* ── Insert account ── */
    $ids  = generateIDs('ac_banking');
    $meta = buildMetaData(null, $_SESSION['user_name'] ?? 'system');

    $pdo->prepare("
        INSERT INTO ac_banking
            (uuid, sys_id, main_type, category, account_code, acc_name, is_transactionable, description, balance, meta_data)
        VALUES
            (:uuid, :sys_id, :main_type, :category, :account_code, :acc_name, :is_transactionable, :description, :balance, :meta_data)
    ")->execute([
        ':uuid'               => $ids['uuid'],
        ':sys_id'             => $ids['sys_id'],
        ':main_type'          => $main_type,
        ':category'           => $category,
        ':account_code'       => $account_code,
        ':acc_name'           => $name,
        ':is_transactionable' => $transactionable,
        ':description'        => $description,
        ':balance'            => $openingAmount,
        ':meta_data'          => $meta,
    ]);

    /* ── Verify insert ── */
    $acc = $pdo->prepare("SELECT sys_id, acc_name, balance FROM ac_banking WHERE sys_id = ? LIMIT 1");
    $acc->execute([$ids['sys_id']]);
    $account = $acc->fetch(PDO::FETCH_ASSOC);
    if (!$account) throw new Exception('Account insert verification failed');

    /* ── Opening balance statement ── */
    $stmtIds  = generateIDs('ac_banking_stmts');
    $stmtMeta = buildMetaData(null, $_SESSION['user_name'] ?? 'system');
    $today    = (new DateTime('now', new DateTimeZone('Asia/Dhaka')))->format('Y-m-d H:i:s');

    $pdo->prepare("
        INSERT INTO ac_banking_stmts
            (uuid, sys_id, ledger_db_id, name, date, particular, withdraw, deposit, balance, reconsilation, meta_data, is_historical)
        VALUES
            (:uuid, :sys_id, :ledger_db_id, :name, :date, 'Opening Balance', 0, :deposit, :balance, 0, :meta_data, 0)
    ")->execute([
        ':uuid'         => $stmtIds['uuid'],
        ':sys_id'       => $stmtIds['sys_id'],
        ':ledger_db_id' => $account['sys_id'],
        ':name'         => $account['acc_name'],
        ':date'         => $today,
        ':deposit'      => $account['balance'],
        ':balance'      => $account['balance'],
        ':meta_data'    => $stmtMeta,
    ]);

    $pdo->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Account created with opening balance',
        'data'    => [
            'account_sys_id' => $account['sys_id'],
            'account_code'   => $account_code,
            'name'           => $account['acc_name'],
            'balance'        => $account['balance'],
        ],
    ]);

} catch (PDOException $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    if ($e->getCode() == 23000) {
        http_response_code(409);
        echo json_encode(['success'=>false,'message'=>'Same account name already exists under this account type']);
        exit;
    }
    http_response_code(500);
    echo json_encode(['success'=>false,'message'=>'Database error','error'=>$e->getMessage()]);

} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['success'=>false,'message'=>'Transaction failed','error'=>$e->getMessage()]);
}

/* ── account_code prefix by type ── */
function _acCodePrefix(string $mainType, string $category): string {
    if (str_contains($mainType, 'Assets'))      return '1';
    if (str_contains($mainType, 'Liabilities')) return '2';
    if (str_contains($mainType, 'Equity'))      return '3';
    if (str_contains($mainType, 'Income'))      return '4';
    if ($category === 'Cost of Services')       return '5';
    if (str_contains($mainType, 'Expense'))     return '6';
    return '7';
}
?>