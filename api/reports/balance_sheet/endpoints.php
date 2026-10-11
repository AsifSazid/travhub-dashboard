<?php
/**
 * FILE PATH: /api/reports/balance_sheet/endpoints.php
 *
 * Balance Sheet — as at a given date
 *
 * GET ?action=balance_sheet&as_at=YYYY-MM-DD
 *
 * Structure:
 *   Current Assets:
 *     - Cash at bank/hand  → ac_banking_stmts (balance column per account)
 *     - AIT Recoverable    → financial_entries (ait_recoverable entries)
 *     - Accounts Receivable→ financial_entries (unpaid sales — AR not cleared)
 *   Current Liabilities:
 *     - Accounts Payable   → financial_entries (unpaid purchases — AP not cleared)
 *     - VAT Payable        → financial_entries (account_head='vat_payable')
 *     - Payroll Liabilities→ financial_entries (account_head='payroll_liability')
 *   Net Current Assets    = Current Assets - Current Liabilities
 *   Capital & Reserves:
 *     - Owner's Capital    → ac_banking (Equity accounts balance)
 *     - Retained Earnings  → sum of all prior year net profits
 *     - Current Year Earnings → this year's net profit from financial_entries
 *   Net Assets = Total Assets - Total Liabilities
 *   Net Assets must equal Capital & Reserves
 */

declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../../../server/db_connection.php';
session_start();
require_once __DIR__ . '/../../../server/permissions.php';
requireFullAccountingAccess($pdo, true);

$action = $_GET['action'] ?? 'balance_sheet';
$asAt   = $_GET['as_at'] ?? date('Y-m-d');

try {
    handleBalanceSheet($pdo, $asAt);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success'=>false,'error'=>$e->getMessage()]);
}

function handleBalanceSheet(PDO $pdo, string $asAt): void
{
    // ── CURRENT ASSETS ────────────────────────────────────────

    // 1. Cash at bank and in hand — from ac_banking_stmts balance
    // balance column = running balance after each transaction
    // We take the latest balance per account as at the given date
    $cashStmt = $pdo->prepare("
        SELECT
            ab.sys_id,
            ab.acc_name AS account_name,
            ab.category,
            ab.account_code,
            COALESCE((
                SELECT bs.balance FROM ac_banking_stmts bs
                WHERE bs.ledger_db_id = ab.sys_id
                  AND bs.date <= :as_at1
                  AND bs.is_historical = 0
                ORDER BY bs.date DESC, bs.id DESC
                LIMIT 1
            ), 0) AS balance
        FROM ac_banking ab
        WHERE ab.main_type LIKE 'Assets%'
          AND ab.category IN ('Bank Account','Cash in Hand','MFS')
          AND ab.is_transactionable = 'yes'
        ORDER BY ab.account_code ASC
    ");
    $cashStmt->execute([':as_at1' => $asAt . ' 23:59:59']);
    $cashAccounts = $cashStmt->fetchAll(PDO::FETCH_ASSOC);
    $totalCash = array_sum(array_column($cashAccounts, 'balance'));

    // 2. Accounts Receivable — unpaid sales (credit sales - received amounts)
    $arStmt = $pdo->prepare("
        SELECT
            COALESCE(SUM(CASE WHEN fe.account_head='accounts_receivable' AND fe.type='debit'  THEN fe.amount ELSE 0 END), 0)
          - COALESCE(SUM(CASE WHEN fe.account_head='accounts_receivable' AND fe.type='credit' THEN fe.amount ELSE 0 END), 0)
            AS ar_balance
        FROM financial_entries fe
        WHERE fe.account_head = 'accounts_receivable'
          AND fe.date <= :as_at2
    ");
    $arStmt->execute([':as_at2' => $asAt]);
    $accountsReceivable = (float)$arStmt->fetchColumn();

    // 3. AIT Recoverable — debit entries tagged to ait_recoverable
    $aitStmt = $pdo->prepare("
        SELECT COALESCE(SUM(fe.amount), 0)
        FROM financial_entries fe
        WHERE fe.account_head = 'ait_recoverable'
          AND fe.type = 'debit'
          AND fe.date <= :as_at3
    ");
    $aitStmt->execute([':as_at3' => $asAt]);
    $aitRecoverable = (float)$aitStmt->fetchColumn();

    // 4. Other Current Assets from ac_banking (Other Current Asset category)
    $otherAssetStmt = $pdo->prepare("
        SELECT ab.acc_name, ab.account_code,
            COALESCE((
                SELECT bs.balance FROM ac_banking_stmts bs
                WHERE bs.ledger_db_id = ab.sys_id
                  AND bs.date <= :as_at4
                ORDER BY bs.date DESC, bs.id DESC LIMIT 1
            ), 0) AS balance
        FROM ac_banking ab
        WHERE ab.main_type LIKE 'Assets%'
          AND ab.category = 'Other Current Asset'
          AND ab.is_transactionable = 'yes'
        ORDER BY ab.account_code ASC
    ");
    $otherAssetStmt->execute([':as_at4' => $asAt . ' 23:59:59']);
    $otherAssets = $otherAssetStmt->fetchAll(PDO::FETCH_ASSOC);
    $totalOtherAssets = array_sum(array_column($otherAssets, 'balance'));

    $totalCurrentAssets = $totalCash + $accountsReceivable + $aitRecoverable + $totalOtherAssets;

    // ── FIXED ASSETS ──────────────────────────────────────────
    $fixedStmt = $pdo->prepare("
        SELECT ab.acc_name, ab.account_code,
            COALESCE((
                SELECT bs.balance FROM ac_banking_stmts bs
                WHERE bs.ledger_db_id = ab.sys_id
                  AND bs.date <= :as_at5
                ORDER BY bs.date DESC, bs.id DESC LIMIT 1
            ), 0) AS balance
        FROM ac_banking ab
        WHERE ab.main_type LIKE 'Assets%'
          AND ab.category IN ('Fixed Assets','Non-Current Asset')
        ORDER BY ab.account_code ASC
    ");
    $fixedStmt->execute([':as_at5' => $asAt . ' 23:59:59']);
    $fixedAssets = $fixedStmt->fetchAll(PDO::FETCH_ASSOC);
    $totalFixedAssets = array_sum(array_column($fixedAssets, 'balance'));

    $totalAssets = $totalCurrentAssets + $totalFixedAssets;

    // ── CURRENT LIABILITIES ───────────────────────────────────

    // 1. Accounts Payable — unpaid purchases
    $apStmt = $pdo->prepare("
        SELECT
            COALESCE(SUM(CASE WHEN fe.account_head='accounts_payable' AND fe.type='credit' THEN fe.amount ELSE 0 END), 0)
          - COALESCE(SUM(CASE WHEN fe.account_head='accounts_payable' AND fe.type='debit'  THEN fe.amount ELSE 0 END), 0)
            AS ap_balance
        FROM financial_entries fe
        WHERE fe.account_head = 'accounts_payable'
          AND fe.date <= :as_at6
    ");
    $apStmt->execute([':as_at6' => $asAt]);
    $accountsPayable = (float)$apStmt->fetchColumn();

    // 2. VAT Payable
    $vatStmt = $pdo->prepare("
        SELECT COALESCE(SUM(fe.amount), 0)
        FROM financial_entries fe
        WHERE fe.account_head IN ('vat_payable','tax_payable')
          AND fe.type = 'credit'
          AND fe.date <= :as_at7
    ");
    $vatStmt->execute([':as_at7' => $asAt]);
    $vatPayable = (float)$vatStmt->fetchColumn();

    // 3. Payroll Liabilities
    $payrollLiabStmt = $pdo->prepare("
        SELECT COALESCE(SUM(fe.amount), 0)
        FROM financial_entries fe
        WHERE fe.account_head = 'payroll_liability'
          AND fe.type = 'credit'
          AND fe.date <= :as_at8
    ");
    $payrollLiabStmt->execute([':as_at8' => $asAt]);
    $payrollLiability = (float)$payrollLiabStmt->fetchColumn();

    // 4. Other Liabilities from ac_banking
    $otherLiabStmt = $pdo->prepare("
        SELECT ab.acc_name, ab.account_code,
            COALESCE((
                SELECT bs.balance FROM ac_banking_stmts bs
                WHERE bs.ledger_db_id = ab.sys_id
                  AND bs.date <= :as_at9
                ORDER BY bs.date DESC, bs.id DESC LIMIT 1
            ), 0) AS balance
        FROM ac_banking ab
        WHERE ab.main_type LIKE 'Liabilities%'
          AND ab.is_transactionable = 'yes'
        ORDER BY ab.account_code ASC
    ");
    $otherLiabStmt->execute([':as_at9' => $asAt . ' 23:59:59']);
    $otherLiabilities = $otherLiabStmt->fetchAll(PDO::FETCH_ASSOC);
    $totalOtherLiab = array_sum(array_column($otherLiabilities, 'balance'));

    $totalCurrentLiabilities = $accountsPayable + $vatPayable + $payrollLiability + $totalOtherLiab;
    $netCurrentAssets = $totalCurrentAssets - $totalCurrentLiabilities;
    $totalAssetsLessCurrentLiab = $totalAssets - $totalCurrentLiabilities;

    // ── CAPITAL & RESERVES ────────────────────────────────────
    // Owner's Capital from ac_banking Equity accounts
    $capitalStmt = $pdo->prepare("
        SELECT ab.acc_name, ab.account_code,
            COALESCE((
                SELECT bs.balance FROM ac_banking_stmts bs
                WHERE bs.ledger_db_id = ab.sys_id
                  AND bs.date <= :as_at10
                ORDER BY bs.date DESC, bs.id DESC LIMIT 1
            ), 0) AS balance
        FROM ac_banking ab
        WHERE ab.main_type LIKE 'Equity%'
        ORDER BY ab.account_code ASC
    ");
    $capitalStmt->execute([':as_at10' => $asAt . ' 23:59:59']);
    $capitalAccounts = $capitalStmt->fetchAll(PDO::FETCH_ASSOC);
    $totalCapital = array_sum(array_column($capitalAccounts, 'balance'));

    // Current Year Earnings — net profit from financial_entries this year
    $yearStart = date('Y') . '-01-01';
    $yearEnd   = $asAt;
    $cyeStmt = $pdo->prepare("
        SELECT
            COALESCE(SUM(CASE WHEN fe.account_head='sales'    AND fe.type='credit' THEN fe.amount
                              WHEN fe.account_head='sales'    AND fe.type='debit'  THEN -fe.amount ELSE 0 END), 0)
          - COALESCE(SUM(CASE WHEN fe.account_head='purchase' AND fe.type='debit'  THEN fe.amount
                              WHEN fe.account_head='purchase' AND fe.type='credit' THEN -fe.amount ELSE 0 END), 0)
          - COALESCE(SUM(CASE WHEN fe.account_head IN ('expense','payroll_expense') THEN fe.amount ELSE 0 END), 0)
            AS current_year_earnings
        FROM financial_entries fe
        WHERE fe.date BETWEEN :yr_start AND :yr_end
          AND fe.account_head IN ('sales','purchase','expense','payroll_expense')
    ");
    $cyeStmt->execute([':yr_start' => $yearStart, ':yr_end' => $yearEnd]);
    $currentYearEarnings = (float)$cyeStmt->fetchColumn();

    // Retained Earnings — net profit before current year
    $reStmt = $pdo->prepare("
        SELECT
            COALESCE(SUM(CASE WHEN fe.account_head='sales'    AND fe.type='credit' THEN fe.amount
                              WHEN fe.account_head='sales'    AND fe.type='debit'  THEN -fe.amount ELSE 0 END), 0)
          - COALESCE(SUM(CASE WHEN fe.account_head='purchase' AND fe.type='debit'  THEN fe.amount
                              WHEN fe.account_head='purchase' AND fe.type='credit' THEN -fe.amount ELSE 0 END), 0)
          - COALESCE(SUM(CASE WHEN fe.account_head IN ('expense','payroll_expense') THEN fe.amount ELSE 0 END), 0)
            AS retained_earnings
        FROM financial_entries fe
        WHERE fe.date < :yr_start2
          AND fe.account_head IN ('sales','purchase','expense','payroll_expense')
    ");
    $reStmt->execute([':yr_start2' => $yearStart]);
    $retainedEarnings = (float)$reStmt->fetchColumn();

    $totalCapitalReserves = $totalCapital + $retainedEarnings + $currentYearEarnings;
    $netAssets = $totalAssetsLessCurrentLiab;

    echo json_encode([
        'success' => true,
        'as_at'   => $asAt,
        'current_assets' => [
            'cash_accounts'      => $cashAccounts,
            'total_cash'         => $totalCash,
            'accounts_receivable'=> $accountsReceivable,
            'ait_recoverable'    => $aitRecoverable,
            'other_assets'       => $otherAssets,
            'total_other'        => $totalOtherAssets,
            'total'              => $totalCurrentAssets,
        ],
        'fixed_assets' => [
            'items' => $fixedAssets,
            'total' => $totalFixedAssets,
        ],
        'total_assets' => $totalAssets,
        'current_liabilities' => [
            'accounts_payable'  => $accountsPayable,
            'vat_payable'       => $vatPayable,
            'payroll_liability' => $payrollLiability,
            'other'             => $otherLiabilities,
            'total_other'       => $totalOtherLiab,
            'total'             => $totalCurrentLiabilities,
        ],
        'net_current_assets'           => $netCurrentAssets,
        'total_assets_less_current_liab'=> $totalAssetsLessCurrentLiab,
        'capital_reserves' => [
            'capital_accounts'     => $capitalAccounts,
            'total_capital'        => $totalCapital,
            'retained_earnings'    => $retainedEarnings,
            'current_year_earnings'=> $currentYearEarnings,
            'total'                => $totalCapitalReserves,
        ],
        'net_assets' => $netAssets,
        'balanced'   => round($netAssets, 2) === round($totalCapitalReserves, 2),
    ]);
}