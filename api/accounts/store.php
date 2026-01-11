<?php
require '../../server/db_connection.php'; // $pdo
require '../../server/uuid_with_system_id_generator.php';
require '../../server/generate_meta_data.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method Not Allowed']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
if (!$data) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid JSON']);
    exit;
}

try {
    /* ================= START TRANSACTION ================= */
    $pdo->beginTransaction();

    /* ---------------- Input Mapping ---------------- */
    $main_type       = $data['accountType'] ?? '';
    $category        = $data['accountCategory'] ?? '';
    $name            = $data['accountName'] ?? '';
    $transactionable = $data['isTransactionable'] ?? 'no';
    $description     = $data['description'] ?? '';
    $openingAmount   = (float)($data['openingAmount'] ?? 0);

    /* ---------------- UUID & Meta ---------------- */
    $accountIds = generateIDs('ac_banking');
    $accountMeta = buildMetaData(null, $_SESSION['user_name'] ?? 'system');

    /* ---------------- Insert Account ---------------- */
    $insertAccountSql = "
        INSERT INTO ac_banking
        (uuid, sys_id, `Main Type`, Category, Name, Transactionable, Description, balance, meta_data)
        VALUES
        (:uuid, :sys_id, :main_type, :category, :name, :transactionable, :description, :balance, :meta_data)
    ";

    $stmt = $pdo->prepare($insertAccountSql);
    $stmt->execute([
        ':uuid'            => $accountIds['uuid'],
        ':sys_id'          => $accountIds['sys_id'],
        ':main_type'       => $main_type,
        ':category'        => $category,
        ':name'            => $name,
        ':transactionable' => $transactionable,
        ':description'     => $description,
        ':balance'         => $openingAmount,
        ':meta_data'       => $accountMeta
    ]);

    /* ---------------- Fetch Inserted Account ---------------- */
    $fetchAccountStmt = $pdo->prepare("
        SELECT sys_id, Name, balance
        FROM ac_banking
        WHERE sys_id = :sys_id
        LIMIT 1
    ");
    $fetchAccountStmt->execute([':sys_id' => $accountIds['sys_id']]);
    $account = $fetchAccountStmt->fetch(PDO::FETCH_ASSOC);

    if (!$account) {
        throw new Exception('Account insert verification failed');
    }

    /* ---------------- Opening Statement ---------------- */
    $tz = new DateTimeZone('Asia/Dhaka');
    $today = (new DateTime('now', $tz))->format('Y-m-d H:i:s');

    $stmtIds = generateIDs('ac_banking_stmts');
    $stmtMeta = buildMetaData(null, $_SESSION['user_name'] ?? 'system');

    $insertStmtSql = "
        INSERT INTO ac_banking_stmts
        (uuid, sys_id, laser_db_id, name, date, particular, withdraw, deposit, balance, reconsilation, meta_data)
        VALUES
        (:uuid, :sys_id, :laser_db_id, :name, :date, :particular, :withdraw, :deposit, :balance, :reconsilation, :meta_data)
    ";

    $stmt = $pdo->prepare($insertStmtSql);
    $stmt->execute([
        ':uuid'          => $stmtIds['uuid'],
        ':sys_id'        => $stmtIds['sys_id'],
        ':laser_db_id'   => $account['sys_id'],
        ':name'          => $account['Name'],
        ':date'          => $today,
        ':particular'    => 'Opening Balance',
        ':withdraw'      => 0,
        ':deposit'       => $account['balance'],
        ':balance'       => $account['balance'],
        ':reconsilation' => 0,
        ':meta_data'     => $stmtMeta
    ]);

    /* ================= COMMIT ================= */
    $pdo->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Account created with opening balance',
        'data' => [
            'account_sys_id' => $account['sys_id'],
            'name'           => $account['Name'],
            'balance'        => $account['balance']
        ]
    ]);

} catch (Throwable $e) {

    /* ================= ROLLBACK ================= */
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Transaction failed',
        'error'   => $e->getMessage()
    ]);
}
