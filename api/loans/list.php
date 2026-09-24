<?php
// PATH: /api/loans/list.php
//
// Lists loans, either globally (for a Loan Management dashboard) or scoped
// to one party (for embedding in a Vendor/Client/Employee ledger, per the
// user's instruction that loan balances must show in a SEPARATE section
// there, never mixed with purchase/sale payable-receivable).
//
// Actions (?action=...):
//   list    -> paginated loans, optionally filtered (default)
//   detail  -> one loan's full detail: installments + repayment history
//   party   -> all loans where the given party is lender OR borrower,
//              with a summary (used by Vendor/Client Ledger pages)

declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../../server/db_connection.php';
session_start();
require_once __DIR__ . '/../../server/permissions.php';
requireFullAccountingAccess($pdo, true);

$action = $_GET['action'] ?? 'list';

try {
    switch ($action) {
        case 'detail': handleDetail($pdo); break;
        case 'party':  handleParty($pdo);  break;
        case 'list':
        default:       handleList($pdo);   break;
    }
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

function handleList(PDO $pdo): void
{
    $where  = [];
    $params = [];

    if (!empty($_GET['status'])) {
        $where[] = 'status = :status';
        $params[':status'] = $_GET['status'];
    }
    if (!empty($_GET['party_type'])) {
        $where[] = '(lender_type = :pt OR borrower_type = :pt)';
        $params[':pt'] = $_GET['party_type'];
    }
    $whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

    $page    = max(1, (int)($_GET['page'] ?? 1));
    $perPage = min(200, max(1, (int)($_GET['per_page'] ?? 25)));
    $offset  = ($page - 1) * $perPage;

    $cntStmt = $pdo->prepare("SELECT COUNT(*) FROM loans {$whereSql}");
    $cntStmt->execute($params);
    $total = (int)$cntStmt->fetchColumn();

    $params[':limit']  = $perPage;
    $params[':offset']  = $offset;
    $stmt = $pdo->prepare("
        SELECT * FROM loans {$whereSql}
        ORDER BY id DESC
        LIMIT :limit OFFSET :offset
    ");
    foreach ($params as $k => $v) {
        $stmt->bindValue($k, $v, in_array($k, [':limit', ':offset'], true) ? PDO::PARAM_INT : PDO::PARAM_STR);
    }
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $summaryStmt = $pdo->query("
        SELECT
            COUNT(*) AS total_loans,
            SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) AS active_loans,
            SUM(CASE WHEN status = 'active' THEN outstanding_balance ELSE 0 END) AS total_outstanding
        FROM loans
    ");
    $summary = $summaryStmt->fetch(PDO::FETCH_ASSOC);

    echo json_encode([
        'success'  => true,
        'summary'  => $summary,
        'rows'     => $rows,
        'page'     => $page,
        'per_page' => $perPage,
        'total'    => $total,
        'pages'    => (int)ceil($total / $perPage),
    ]);
}

function handleDetail(PDO $pdo): void
{
    $loanSysId = $_GET['loan_sys_id'] ?? '';
    if (!$loanSysId) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'loan_sys_id is required']);
        return;
    }

    $loanStmt = $pdo->prepare("SELECT * FROM loans WHERE sys_id = ?");
    $loanStmt->execute([$loanSysId]);
    $loan = $loanStmt->fetch(PDO::FETCH_ASSOC);
    if (!$loan) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Loan not found']);
        return;
    }

    $instStmt = $pdo->prepare("SELECT * FROM loan_installments WHERE loan_sys_id = ? ORDER BY installment_no ASC");
    $instStmt->execute([$loanSysId]);
    $installments = $instStmt->fetchAll(PDO::FETCH_ASSOC);

    $repayStmt = $pdo->prepare("SELECT * FROM loan_repayments WHERE loan_sys_id = ? ORDER BY date ASC");
    $repayStmt->execute([$loanSysId]);
    $repayments = $repayStmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success'      => true,
        'loan'         => $loan,
        'installments' => $installments,
        'repayments'   => $repayments,
    ]);
}

function handleParty(PDO $pdo): void
{
    $partyType = $_GET['party_type'] ?? '';
    $partyId   = $_GET['party_id'] ?? '';

    if (!$partyType || !$partyId) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'party_type and party_id are required']);
        return;
    }

    $stmt = $pdo->prepare("
        SELECT * FROM loans
        WHERE (lender_type = :pt AND lender_id = :pid) OR (borrower_type = :pt2 AND borrower_id = :pid2)
        ORDER BY id DESC
    ");
    $stmt->execute([':pt' => $partyType, ':pid' => $partyId, ':pt2' => $partyType, ':pid2' => $partyId]);
    $loans = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Split into two directions for clear ledger display: money this party
    // owes the company/others (they are the borrower) vs money owed TO this
    // party (they are the lender) -- kept as two separate totals rather
    // than netted, since the user wants loan balances shown as their own
    // distinct section, not blended with purchase/sale payable-receivable.
    $asLender = array_values(array_filter($loans, fn($l) => $l['lender_type'] === $partyType && $l['lender_id'] === $partyId));
    $asBorrower = array_values(array_filter($loans, fn($l) => $l['borrower_type'] === $partyType && $l['borrower_id'] === $partyId));

    $totalOwedToParty = array_sum(array_map(fn($l) => $l['status'] === 'active' ? (float)$l['outstanding_balance'] : 0, $asLender));
    $totalOwedByParty = array_sum(array_map(fn($l) => $l['status'] === 'active' ? (float)$l['outstanding_balance'] : 0, $asBorrower));

    echo json_encode([
        'success' => true,
        'summary' => [
            'total_owed_to_party'  => round($totalOwedToParty, 2), // party lent money out, is owed this back
            'total_owed_by_party'  => round($totalOwedByParty, 2), // party borrowed, owes this
            'loan_count'           => count($loans),
        ],
        'as_lender'   => $asLender,
        'as_borrower' => $asBorrower,
    ]);
}