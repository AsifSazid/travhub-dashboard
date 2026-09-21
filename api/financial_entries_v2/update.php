<?php
// PATH: /api/financial_entries_v2/update.php
// (v2 -- separate namespace from legacy /api/financial_entries/, used only by the new Task View)
//
// ============= GROUP-AWARE EDIT WITH MANDATORY AUDIT TRAIL =============
// Editing ANY field on ANY entry now requires a reason (text) and an
// evidence file (already-uploaded, referenced by its file token/name --
// this endpoint does not itself handle multipart upload; the frontend
// uploads the evidence first via upload-file.php, then passes the result
// here as evidence_file).
//
// Vendor/account/client identity is fixed per entry (no re-linking here --
// that was intentionally removed). Only these shared fields are editable:
// purpose, amount, qty_rate, date, ref (note). Editing amount propagates to
// EVERY leg in the transaction_group_id (all legs of a real-time vendor
// payment share the same amount, per store.php), keeping the group balanced.
// Bank legs (account_head='bank_account') also get their ac_banking balance
// and ac_banking_stmts row corrected to reflect the new amount.
//
// Every edited row gets an entry appended to its edit_history column (a
// rolling window of the last 20 edits): {old_data, new_data, reason,
// evidence_file, edited_by, edited_at}.
session_start();

require '../../server/db_connection.php';
require '../../server/generate_meta_data.php';
require '../../server/uuid_with_system_id_generator.php';
require_once '../../server/sys_id_generator_v2.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

const EDIT_HISTORY_MAX = 20;

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
    if (empty(trim($data['reason'] ?? ''))) {
        $errors[] = 'A reason for this edit is required';
    }
    if (empty(trim($data['evidence_file'] ?? ''))) {
        $errors[] = 'Evidence (an uploaded file reference) is required for this edit';
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
    $newAmount     = (float)$input['amount'];
    $newPurpose    = trim($input['purpose']);
    $newDate       = $input['date'];
    $newQtyRate    = $input['qty_rate'] ?? null;
    $newRef        = $input['ref'] ?? null;
    $reason        = trim($input['reason']);
    $evidenceFile  = trim($input['evidence_file']);
    $userName      = $_SESSION['user_name'] ?? 'system';
    $editedAt      = date('Y-m-d H:i:s');

    // Find the anchor row, then every leg sharing its transaction_group_id.
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

    $updatedSysIds = [];

    foreach ($legs as $leg) {
        $origAmount = (float)$leg['amount'];
        $amountChanged = abs($origAmount - $newAmount) > 0.0001;

        // ---- Reverse and reapply the bank effect if this leg is a bank leg ----
        if ($leg['account_head'] === 'bank_account' && $amountChanged) {
            $s = $pdo->prepare("SELECT balance FROM ac_banking WHERE sys_id = ?");
            $s->execute([$leg['user_sys_id']]);
            $curBalance = (float)$s->fetchColumn();

            // Reverse the old amount's effect, then apply the new amount's effect.
            // credit = money left the account; debit = money entered it.
            $balanceAfterReversal = ($leg['type'] === 'credit')
                ? $curBalance + $origAmount
                : $curBalance - $origAmount;
            $finalBalance = ($leg['type'] === 'credit')
                ? $balanceAfterReversal - $newAmount
                : $balanceAfterReversal + $newAmount;

            $pdo->prepare("UPDATE ac_banking SET balance = ? WHERE sys_id = ?")
                ->execute([$finalBalance, $leg['user_sys_id']]);

            // Update the matching ac_banking_stmts row (created with ref = this leg's sys_id)
            $stmtField = ($leg['type'] === 'credit') ? 'withdraw' : 'deposit';
            $pdo->prepare("UPDATE ac_banking_stmts SET {$stmtField} = ?, balance = ?, date = ?, particular = ? WHERE ref = ?")
                ->execute([$newAmount, $finalBalance, $newDate, $newPurpose, $leg['sys_id']]);
        }

        // ---- Build this leg's edit_history entry ----
        $history = json_decode($leg['edit_history'] ?? '[]', true);
        if (!is_array($history)) $history = [];
        $history[] = [
            'old_data' => [
                'purpose'  => $leg['purpose'],
                'amount'   => $origAmount,
                'qty_rate' => $leg['qty_rate'],
                'date'     => $leg['date'],
                'ref'      => $leg['ref'],
            ],
            'new_data' => [
                'purpose'  => $newPurpose,
                'amount'   => $newAmount,
                'qty_rate' => $newQtyRate,
                'date'     => $newDate,
                'ref'      => $newRef,
            ],
            'reason'        => $reason,
            'evidence_file' => $evidenceFile,
            'edited_by'     => $userName,
            'edited_at'     => $editedAt,
        ];
        // Rolling window -- keep only the most recent EDIT_HISTORY_MAX entries.
        if (count($history) > EDIT_HISTORY_MAX) {
            $history = array_slice($history, -EDIT_HISTORY_MAX);
        }

        $existingMeta = json_decode($leg['meta_data'], true) ?? [];
        $updatedMeta  = buildMetaData($existingMeta, $userName);

        $pdo->prepare("
            UPDATE financial_entries
            SET purpose      = :purpose,
                amount       = :amount,
                qty_rate     = :qty_rate,
                date         = :date,
                ref          = :ref,
                edit_history = :edit_history,
                meta_data    = :meta_data
            WHERE sys_id = :sys_id
        ")->execute([
            ':purpose'      => $newPurpose,
            ':amount'       => $newAmount,
            ':qty_rate'     => $newQtyRate,
            ':date'         => $newDate,
            ':ref'          => $newRef,
            ':edit_history' => json_encode($history, JSON_UNESCAPED_UNICODE),
            ':meta_data'    => $updatedMeta,
            ':sys_id'       => $leg['sys_id'],
        ]);

        $updatedSysIds[] = $leg['sys_id'];
    }

    http_response_code(200);
    echo json_encode([
        'success'              => true,
        'message'              => 'Transaction updated successfully (' . count($updatedSysIds) . ' linked leg(s))',
        'transaction_group_id' => $groupId,
        'updated_sys_ids'      => $updatedSysIds,
    ]);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Server error',
        'error'   => $e->getMessage()
    ]);
}