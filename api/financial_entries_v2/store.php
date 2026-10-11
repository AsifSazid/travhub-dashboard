<?php
// PATH: /api/financial_entries_v2/store.php
session_start();

require '../../server/db_connection.php';
require_once '../../server/permissions.php';
require '../../server/uuid_with_system_id_generator.php';
require_once '../../server/sys_id_generator_v2.php';
require '../../server/generate_meta_data.php';
require_once '../../server/finance_helpers.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

function validateInput(array $data): array
{
    $errors = [];
    if (!isset($data['type']) || !in_array($data['type'], ['credit', 'debit'], true))
        $errors[] = 'Valid type (credit/debit) is required';
    if (!isset($data['amount']) || !is_numeric($data['amount']) || $data['amount'] <= 0)
        $errors[] = 'Valid positive amount is required';
    if (empty(trim($data['purpose'] ?? '')))
        $errors[] = 'Purpose is required';
    if (empty($data['client_id']) && empty($data['vendor_id']))
        $errors[] = 'Either client_id or vendor_id is required';
    if (!empty($data['vendor_id'])) {
        $mode = $data['transaction_mode'] ?? null;
        if (!in_array($mode, ['realtime', 'non_realtime'], true))
            $errors[] = 'transaction_mode (realtime/non_realtime) is required for a vendor payment';
        if ($mode === 'realtime' && empty($data['account_id']))
            $errors[] = 'account_id is required for a real-time vendor payment';
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

    $errors = validateInput($input);
    if ($errors) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Validation failed', 'errors' => $errors]);
        exit;
    }

    /* ── Input ── */
    $type                = $input['type'];
    $amount              = (float)$input['amount'];
    $purpose             = trim($input['purpose']);
    $date                = $input['date'] ?? date('Y-m-d');
    $clientId            = $input['client_id']       ?? null;
    $vendorId            = $input['vendor_id']       ?? null;
    $accountId           = $input['account_id']      ?? null;
    $txnMode             = $input['transaction_mode'] ?? null;
    $workId              = $input['work_id']          ?? null;
    $taskId              = $input['task_id']          ?? null;
    $ref                 = $input['ref']              ?? null;
    $qtyRate             = $input['qty_rate']         ?? null;
    $paymentMethod       = strtolower($input['payment_method'] ?? 'cash');
    $instrumentNo        = $input['instrument_no']   ?? null;
    $accountHeadOverride = $input['account_head']    ?? null; // e.g. 'ait_recoverable'

    $movesMoney = !empty($accountId) && (!empty($vendorId) || !empty($clientId));
    if ($movesMoney) {
        requireMoneyAccess($pdo, $taskId, !empty($vendorId) ? 'entry_payment' : 'entry_receive', !empty($vendorId) ? 'ledger_vendor' : 'ledger_client');
    } else {
        requireTaskOrPermission($pdo, $taskId, 'full_accounting_access');
    }

    $userName = $_SESSION['user_name'] ?? 'system';
    $clientName = $vendorName = $accountName = $taskTitle = $workTitle = null;

    /* ── Lookups ── */
    if ($workId) {
        $s = $pdo->prepare("SELECT client_info FROM works WHERE sys_id = ?");
        $s->execute([$workId]);
        $ci = json_decode($s->fetchColumn() ?: '{}', true) ?? [];
        $workTitle = $ci['name'] ?? null;
    }
    if ($taskId) {
        $s = $pdo->prepare("SELECT workname FROM tasks WHERE sys_id = ?");
        $s->execute([$taskId]);
        $taskTitle = $s->fetchColumn();
    }
    if ($clientId) {
        $s = $pdo->prepare("SELECT name FROM clients WHERE sys_id = ?");
        $s->execute([$clientId]);
        $clientName = $s->fetchColumn();
        if (!$clientName) throw new Exception('Client not found');
    }
    if ($vendorId) {
        $s = $pdo->prepare("SELECT name FROM vendors WHERE sys_id = ?");
        $s->execute([$vendorId]);
        $vendorName = $s->fetchColumn();
        if (!$vendorName) throw new Exception('Vendor not found');
    }

    $oldBalance = null;
    if ($accountId) {
        $s = $pdo->prepare("SELECT acc_name, balance FROM ac_banking WHERE sys_id = ?");
        $s->execute([$accountId]);
        $acInfo = $s->fetch(PDO::FETCH_ASSOC);
        if (!$acInfo) throw new Exception('Account not found');
        $accountName = $acInfo['acc_name'];
        $oldBalance  = (float)$acInfo['balance'];
    }

    /* ── Date format ── */
    if ($date) {
        $tz  = new DateTimeZone('Asia/Dhaka');
        $dt  = new DateTime($date, $tz);
        $now = (new DateTime('now', $tz))->format('H:i:s');
        $dt->setTime(...explode(':', $now));
        $date = $dt->format('Y-m-d H:i:s');
    }

    /* ── Insert one row helper ── */
    function _insertFE(PDO $pdo, array $f): string
    {
        $ids  = generateV2IDs($pdo, 'financial_entries');
        $meta = buildMetaData(null, $f['actor']);
        $pdo->prepare("
            INSERT INTO financial_entries (
                uuid, sys_id, transaction_group_id,
                user_sys_id, user_name, user_type, account_head, vendor_type,
                task_sys_id, task_title, work_sys_id, work_title,
                date, purpose, type, related_type,
                is_paid, is_partial, is_discounted,
                amount, qty_rate, ref, meta_data
            ) VALUES (
                :uuid, :sys_id, :gid,
                :user_sys_id, :user_name, :user_type, :account_head, :vendor_type,
                :task_sys_id, :task_title, :work_sys_id, :work_title,
                :date, :purpose, :type, :rt,
                0, 0, 0,
                :amount, :qty_rate, :ref, :meta
            )
        ")->execute([
            ':uuid'         => $ids['uuid'],        ':sys_id'      => $ids['sys_id'],
            ':gid'          => $f['gid'],            ':user_sys_id' => $f['user_sys_id'],
            ':user_name'    => $f['user_name'],      ':user_type'   => $f['user_type'],
            ':account_head' => $f['account_head'],   ':vendor_type' => $f['vendor_type'],
            ':task_sys_id'  => $f['task_sys_id'],    ':task_title'  => $f['task_title'],
            ':work_sys_id'  => $f['work_sys_id'],    ':work_title'  => $f['work_title'],
            ':date'         => $f['date'],           ':purpose'     => $f['purpose'],
            ':type'         => $f['type'],           ':rt'          => $f['rt'],
            ':amount'       => $f['amount'],         ':qty_rate'    => $f['qty_rate'],
            ':ref'          => $f['ref'],            ':meta'        => $meta,
        ]);
        return $ids['sys_id'];
    }

    $gid  = generateV2SysId($pdo, 'financial_entries');
    $eids = [];

    $base = [
        'gid'          => $gid,
        'task_sys_id'  => $taskId,   'task_title'  => $taskTitle,
        'work_sys_id'  => $workId,   'work_title'  => $workTitle,
        'date'         => $date,     'purpose'     => $purpose,
        'qty_rate'     => $qtyRate,  'ref'         => $ref,
        'actor'        => $userName,
    ];

    /* ════════════════ VENDOR SIDE ════════════════ */
    if ($vendorId) {

        if ($type === 'credit') {

            if ($accountHeadOverride) {
                // Special single-debit entry (e.g. AIT Recoverable)
                // No AP row — just record the asset/recoverable
                $eids['override_entry_sys_id'] = _insertFE($pdo, $base + [
                    'user_sys_id'  => $vendorId, 'user_name'    => $vendorName,
                    'user_type'    => 'vendor',  'account_head' => $accountHeadOverride,
                    'vendor_type'  => 0,         'type'         => 'debit',
                    'rt'           => 2,         'amount'       => $amount,
                ]);
                // override entries are always non-realtime — no bank movement

            } else {
                // Standard purchase: COGS Dr + AP Cr
                $eids['purchase_entry_sys_id'] = _insertFE($pdo, $base + [
                    'user_sys_id'  => $vendorId, 'user_name'    => $vendorName,
                    'user_type'    => 'vendor',  'account_head' => 'purchase',
                    'vendor_type'  => 0,         'type'         => 'debit',
                    'rt'           => 2,         'amount'       => $amount,
                ]);
                $eids['payable_entry_sys_id'] = _insertFE($pdo, $base + [
                    'user_sys_id'  => $vendorId, 'user_name'    => $vendorName,
                    'user_type'    => 'vendor',  'account_head' => 'accounts_payable',
                    'vendor_type'  => 0,         'type'         => 'credit',
                    'rt'           => 2,         'amount'       => $amount,
                ]);

                if ($txnMode === 'realtime') {
                    // AP cleared Dr + Bank Cr
                    $eids['payable_cleared_entry_sys_id'] = _insertFE($pdo, $base + [
                        'user_sys_id'  => $vendorId, 'user_name'    => $vendorName,
                        'user_type'    => 'vendor',  'account_head' => 'accounts_payable',
                        'vendor_type'  => 0,         'type'         => 'debit',
                        'rt'           => 2,         'amount'       => $amount,
                    ]);
                    $bankSysId = _insertFE($pdo, $base + [
                        'user_sys_id'  => $accountId, 'user_name'    => $accountName,
                        'user_type'    => 'account',  'account_head' => 'bank_account',
                        'vendor_type'  => 1,          'type'         => 'credit',
                        'rt'           => 2,           'amount'       => $amount,
                    ]);
                    $eids['bank_entry_sys_id'] = $bankSysId;
                    postBankLegV2($pdo, $accountId, $accountName, $oldBalance, $amount, 'out',
                        $date, "Payment to {$vendorName} — {$purpose}", $bankSysId,
                        $userName, $paymentMethod, $vendorId, $vendorName, 'vendor', $instrumentNo);
                }
            }

        } else {
            // Vendor refund: AP Dr (we owe less)
            $eids['payable_entry_sys_id'] = _insertFE($pdo, $base + [
                'user_sys_id'  => $vendorId, 'user_name'    => $vendorName,
                'user_type'    => 'vendor',  'account_head' => 'accounts_payable',
                'vendor_type'  => 0,         'type'         => 'debit',
                'rt'           => 0,         'amount'       => $amount,
            ]);
            if ($txnMode === 'realtime') {
                $bankSysId = _insertFE($pdo, $base + [
                    'user_sys_id'  => $accountId, 'user_name'    => $accountName,
                    'user_type'    => 'account',  'account_head' => 'bank_account',
                    'vendor_type'  => 1,          'type'         => 'debit',
                    'rt'           => 3,           'amount'       => $amount,
                ]);
                $eids['bank_entry_sys_id'] = $bankSysId;
                postBankLegV2($pdo, $accountId, $accountName, $oldBalance, $amount, 'in',
                    $date, "Refund from {$vendorName} — {$purpose}", $bankSysId,
                    $userName, $paymentMethod, $vendorId, $vendorName, 'vendor', $instrumentNo);
            }
        }

    /* ════════════════ CLIENT SIDE ════════════════ */
    } else {
        if ($type !== 'debit') throw new Exception('Client-side store.php only handles sales (type=debit). Use receive-outstanding.php to record a receive.');

        // Sale: Revenue Cr + AR Dr
        $eids['sales_entry_sys_id'] = _insertFE($pdo, $base + [
            'user_sys_id'  => $clientId, 'user_name'    => $clientName,
            'user_type'    => 'client',  'account_head' => 'sales',
            'vendor_type'  => null,      'type'         => 'credit',
            'rt'           => 1,         'amount'       => $amount,
        ]);
        $eids['receivable_entry_sys_id'] = _insertFE($pdo, $base + [
            'user_sys_id'  => $clientId, 'user_name'    => $clientName,
            'user_type'    => 'client',  'account_head' => 'accounts_receivable',
            'vendor_type'  => null,      'type'         => 'debit',
            'rt'           => 1,         'amount'       => $amount,
        ]);
    }

    http_response_code(201);
    echo json_encode(array_merge([
        'success'              => true,
        'message'              => ucfirst($type) . ' transaction recorded successfully',
        'transaction_group_id' => $gid,
        'sys_id'               => $eids['purchase_entry_sys_id']
                               ?? $eids['sales_entry_sys_id']
                               ?? $eids['override_entry_sys_id']
                               ?? $eids['receivable_entry_sys_id']
                               ?? null,
    ], $eids));

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error', 'error' => $e->getMessage()]);
}