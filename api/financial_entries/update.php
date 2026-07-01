<?php
// PATH: /api/financial_entries/update.php
session_start();

require '../../server/db_connection.php';
require '../../server/generate_meta_data.php';
require '../../server/uuid_with_system_id_generator.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

function validateUpdateInput(array $data): array
{
    $errors = [];
    if (empty($data['id'])) {
        $errors[] = 'Transaction ID is required';
    }
    if (!isset($data['amount']) || !is_numeric($data['amount']) || $data['amount'] <= 0) {
        $errors[] = 'Valid positive amount is required';
    }
    if (empty(trim($data['purpose'] ?? ''))) {
        $errors[] = 'Purpose is required';
    }
    if (empty(trim($data['date'] ?? ''))) {
        $errors[] = 'Date is required';
    }
    return $errors;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

try {
    $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;

    $errors = validateUpdateInput($input);
    if ($errors) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Validation failed', 'errors' => $errors]);
        exit;
    }

    $transactionId = $input['id'];
    $amount        = (float)$input['amount'];
    $purpose       = trim($input['purpose']);
    $date          = $input['date'];
    $newVendorType = isset($input['vendor_type']) ? (int)$input['vendor_type'] : null;
    $inputVendorId = $input['vendor_id']  ?? null;
    $inputAccountId= $input['account_id'] ?? null;
    $qtyRate       = $input['qty_rate']   ?? null; // JSON string

    $stmt = $pdo->prepare("SELECT * FROM financial_entries WHERE id = ? OR sys_id = ?");
    $stmt->execute([$transactionId, $transactionId]);
    $orig = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$orig) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Transaction not found']);
        exit;
    }

    $origAmount     = (float)$orig['amount'];
    $origVendorType = (int)$orig['vendor_type'];
    $origUserSysId  = $orig['user_sys_id'];
    $origType       = $orig['type'];
    $origRelatedType= (int)$orig['related_type'];

    // Reverse original bank effect
    if ($origVendorType === 1 && $orig['user_type'] === 'account' && $origUserSysId) {

        $s = $pdo->prepare("SELECT balance FROM ac_banking WHERE sys_id = ?");
        $s->execute([$origUserSysId]);
        $origAccBalance = (float)$s->fetchColumn();

        if ($origType === 'credit') {
            $reversedBalance = $origAccBalance + $origAmount;
        } else {
            $reversedBalance = $origAccBalance - $origAmount;
        }

        $pdo->prepare("UPDATE ac_banking SET balance = ? WHERE sys_id = ?")
            ->execute([$reversedBalance, $origUserSysId]);

        $pdo->prepare("DELETE FROM ac_banking_stmts WHERE ref = ?")
            ->execute([$orig['sys_id']]);
    }

    // Determine new user info
    $newUserSysId = $origUserSysId;
    $newUserName  = $orig['user_name'];
    $newUserType  = $orig['user_type'];
    $finalVendorType = $newVendorType ?? $origVendorType;

    if ($newVendorType === 0 && $inputVendorId) {
        $s = $pdo->prepare("SELECT name FROM vendors WHERE sys_id = ?");
        $s->execute([$inputVendorId]);
        $vName = $s->fetchColumn();
        if ($vName) {
            $newUserSysId = $inputVendorId;
            $newUserName  = $vName;
            $newUserType  = 'vendor';
        }
    }

    $newAccountBalance = null;
    if ($newVendorType === 1 && $inputAccountId) {
        $s = $pdo->prepare("SELECT acc_name, balance FROM ac_banking WHERE sys_id = ?");
        $s->execute([$inputAccountId]);
        $accRow = $s->fetch(PDO::FETCH_ASSOC);

        if ($accRow) {
            $newUserSysId      = $inputAccountId;
            $newUserName       = $accRow['acc_name'];
            $newUserType       = 'account';
            $newAccountBalance = (float)$accRow['balance'];
        }
    }

    // Apply new bank effect
    if ($newVendorType === 1 && $inputAccountId && $newAccountBalance !== null) {
        if ($origType === 'credit') {
            $finalBalance = $newAccountBalance - $amount;
        } else {
            $finalBalance = $newAccountBalance + $amount;
        }

        $pdo->prepare("UPDATE ac_banking SET balance = ? WHERE sys_id = ?")
            ->execute([$finalBalance, $inputAccountId]);

        $newStmtIds  = generateIDs('ac_banking_stmts');
        $newStmtMeta = buildMetaData(null, $_SESSION['user_name'] ?? 'system');

        if ($origType === 'credit') {
            $pdo->prepare("
                INSERT INTO ac_banking_stmts
                (uuid, sys_id, ledger_db_id, name, date, particular,
                 withdraw, deposit, balance, related_type, meta_data, ref)
                VALUES (?, ?, ?, ?, ?, ?, ?, 0, ?, 2, ?, ?)
            ")->execute([
                $newStmtIds['uuid'], $newStmtIds['sys_id'],
                $inputAccountId, $newUserName,
                $date, $purpose, $amount, $finalBalance,
                $newStmtMeta, $orig['sys_id']
            ]);
        } else {
            $pdo->prepare("
                INSERT INTO ac_banking_stmts
                (uuid, sys_id, ledger_db_id, name, date, particular,
                 withdraw, deposit, balance, related_type, meta_data, ref)
                VALUES (?, ?, ?, ?, ?, ?, 0, ?, ?, 1, ?, ?)
            ")->execute([
                $newStmtIds['uuid'], $newStmtIds['sys_id'],
                $inputAccountId, $newUserName,
                $date, $purpose, $amount, $finalBalance,
                $newStmtMeta, $orig['sys_id']
            ]);
        }
    }

    // Recalculate related_type
    $newRelatedType = $origRelatedType;

    if ($newUserType === 'client') {
        $newRelatedType = ($origType === 'debit') ? 1 : 0;
    } elseif ($newUserType === 'vendor') {
        $newRelatedType = ($origType === 'credit') ? 2 : 0;
    } elseif ($newUserType === 'account') {
        $newRelatedType = ($origType === 'credit') ? 2 : 3;
    }

    // Update financial_entries with qty_rate
    $existingMeta = json_decode($orig['meta_data'], true) ?? [];
    $updatedMeta  = buildMetaData($existingMeta, $_SESSION['user_name'] ?? 'system');

    $pdo->prepare("
        UPDATE financial_entries
        SET purpose      = :purpose,
            amount       = :amount,
            qty_rate     = :qty_rate,
            date         = :date,
            user_sys_id  = :user_sys_id,
            user_name    = :user_name,
            user_type    = :user_type,
            vendor_type  = :vendor_type,
            related_type = :related_type,
            meta_data    = :meta_data
        WHERE id = :id OR sys_id = :sys_id
    ")->execute([
        ':purpose'      => $purpose,
        ':amount'       => $amount,
        ':qty_rate'     => $qtyRate,
        ':date'         => $date,
        ':user_sys_id'  => $newUserSysId,
        ':user_name'    => $newUserName,
        ':user_type'    => $newUserType,
        ':vendor_type'  => $finalVendorType,
        ':related_type' => $newRelatedType,
        ':meta_data'    => $updatedMeta,
        ':id'           => is_numeric($transactionId) ? (int)$transactionId : 0,
        ':sys_id'       => $transactionId
    ]);

    http_response_code(200);
    echo json_encode([
        'success'          => true,
        'message'          => 'Transaction updated successfully',
        'transaction_id'   => $transactionId,
        'related_type'     => $newRelatedType,
        'account_updated'  => ($newVendorType === 1 && $inputAccountId) ? true : false
    ]);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Server error',
        'error'   => $e->getMessage()
    ]);
}