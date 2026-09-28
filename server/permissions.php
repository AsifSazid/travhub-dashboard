<?php
// PATH: /server/permissions.php
//
// Granular, admin-controlled accounting permissions. Per the user's explicit
// decisions:
//   - every permission is a real per-employee on/off switch stored in
//     employee_permissions; department/designation are NEVER used to infer
//     access (they only help sort the admin UI).
//   - login.role = '0' (super-admin) always passes -- existing convention
//     already used elsewhere in this codebase.
//   - 'full_accounting_access' is a MASTER switch: holding it implies every
//     granular permission below, so anyone already granted it keeps working
//     exactly as before.
//
// A page and the API it calls share ONE permission key (not one per action),
// so a user who can open a page is never blocked by its own backend.

/**
 * The single source of truth for what permissions exist. The admin UI and
 * every check read from here, so adding a permission means adding one line
 * -- nothing else needs to know the list.
 *
 * group => [ key => human label ]
 */
function accountingPermissionCatalog(): array
{
    return [
        'Reports' => [
            'report_payable'    => 'A/C Payable report',
            'report_receivable' => 'A/C Receivable report',
            'report_purchase'   => 'Purchase report',
            'report_sale'       => 'Sale report',
            'report_profit'     => 'Profit report',
            'report_cashflow'   => 'Cashflow report',
            'report_payment'    => 'Payment report',
            'report_receive'    => 'Receive report',
            'report_expense'    => 'Expense report',
            'report_asset'      => 'Fixed Asset report',
        ],
        'Entry (create / change money)' => [
            'entry_expense'  => 'Record Expense',
            'entry_asset'    => 'Record Asset Purchase',
            'entry_payment'  => 'Pay a vendor (Pay Now / General Payment)',
            'entry_receive'  => 'Receive from a client (Receive Now / General Receive)',
            'entry_refund'   => 'Declare & settle refunds',
            'entry_advance'  => 'Record Advance',
            'entry_discount' => 'Record Discount',
            'entry_gratuity' => 'Record Gratuity',
        ],
        'Ledgers' => [
            'ledger_vendor' => 'Vendor Ledger',
            'ledger_client' => 'Client Ledger',
        ],
        'Loans' => [
            'loans_view'   => 'View loans',
            'loans_create' => 'Create loans',
            'loans_repay'  => 'Record loan repayments',
        ],
        'Gateway & Payroll' => [
            'gateway_settle'   => 'Settle EPS gateway payments',
            'payroll_disburse' => 'Disburse payroll',
        ],
    ];
}

/** Flat list of every valid granular permission key. */
function allAccountingPermissionKeys(): array
{
    $keys = [];
    foreach (accountingPermissionCatalog() as $group) {
        foreach (array_keys($group) as $k) $keys[] = $k;
    }
    return $keys;
}

/**
 * Raw check: is there an active explicit grant row for this exact key?
 * No master-switch or role logic here -- callers wanting the full rule
 * should use canAccess().
 */
function hasPermission(PDO $pdo, string $employeeSysId, string $permissionKey): bool
{
    if (!$employeeSysId) return false;

    $stmt = $pdo->prepare("
        SELECT 1 FROM employee_permissions
        WHERE employee_sys_id = ? AND permission_key = ? AND revoked_at IS NULL
        LIMIT 1
    ");
    $stmt->execute([$employeeSysId, $permissionKey]);
    return (bool)$stmt->fetchColumn();
}

/**
 * The full rule, in the order the user specified:
 *   1. super-admin (role '0') -> always yes
 *   2. holds the master 'full_accounting_access' -> yes to everything
 *   3. holds this specific permission -> yes
 *   4. otherwise -> no
 *
 * Results are cached per request so a page that checks several keys (e.g.
 * to decide which buttons to show) hits the database once, not once per key.
 */
function canAccess(PDO $pdo, string $permissionKey, ?string $employeeSysId = null): bool
{
    // (string) cast: mysqli can return role as int 0 or string '0' depending on
    // server config; a strict === '0' would silently fail on the int form.
    if ((string)($_SESSION['role'] ?? '') === '0') {
        return true;
    }

    $employeeSysId = $employeeSysId ?? ($_SESSION['user_id'] ?? '');
    if (!$employeeSysId) return false;

    static $granted = []; // employeeSysId => set of active keys
    if (!isset($granted[$employeeSysId])) {
        $stmt = $pdo->prepare("SELECT permission_key FROM employee_permissions WHERE employee_sys_id = ? AND revoked_at IS NULL");
        $stmt->execute([$employeeSysId]);
        $granted[$employeeSysId] = array_flip($stmt->fetchAll(PDO::FETCH_COLUMN));
    }

    return isset($granted[$employeeSysId]['full_accounting_access'])
        || isset($granted[$employeeSysId][$permissionKey]);
}

/**
 * Back-compat wrapper: "may this person use accounting at all". Kept so any
 * code still calling the old name keeps working; it now means "holds the
 * master switch or is super-admin".
 */
function hasFullAccountingAccess(PDO $pdo, ?string $employeeSysId = null): bool
{
    return canAccess($pdo, 'full_accounting_access', $employeeSysId);
}

/**
 * Put this at the top of a page or API file. Ends the request immediately
 * if the current session lacks the given permission.
 *
 * $isApi: true  => JSON + HTTP 403 (for api/*.php)
 *         false => a plain "Access Restricted" page (for pages/*.php)
 */
function requirePermission(PDO $pdo, string $permissionKey, bool $isApi = true): void
{
    if (canAccess($pdo, $permissionKey)) return;

    http_response_code(403);
    if ($isApi) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'You do not have permission to do this.']);
    } else {
        echo '<div style="font-family:Arial,sans-serif;padding:60px;text-align:center;color:#666;">
                <h2 style="color:#dc2626;">Access Restricted</h2>
                <p>You do not have permission to view this page. Contact your administrator if you believe this is a mistake.</p>
            </div>';
    }
    exit;
}

/** Old name, kept so nothing breaks while files are migrated to requirePermission(). */
function requireFullAccountingAccess(PDO $pdo, bool $isApi = true): void
{
    requirePermission($pdo, 'full_accounting_access', $isApi);
}

/**
 * Task-level access to the Financial tab. Per the user's rule:
 *
 *   1. Anyone holding the accounting permission (or role '0') may work in
 *      EVERY task's Financial tab.
 *   2. Everyone else is judged by the task's service (service_works row):
 *        - the service has an assignee (assigned_to)  -> ONLY that person
 *        - the service has no assignee                -> anyone in the
 *          service's department
 *
 * $permissionKey is the granular permission that unlocks step 1 for the
 * action being attempted (e.g. 'entry_payment'). Pass 'full_accounting_access'
 * for a plain "can they touch this task's finances at all" check.
 */
function canWorkOnTaskFinance(PDO $pdo, string $taskSysId, string $permissionKey = 'full_accounting_access'): bool
{
    // Step 1: accounting permission => every task.
    if (canAccess($pdo, $permissionKey)) return true;
    // Step 2: otherwise the task's own rule.
    return taskRuleAllows($pdo, $taskSysId);
}

/**
 * The task rule on its own, with no permission override: is the current user
 * the assignee of this task's service, or -- when nobody is assigned -- in the
 * service's department?
 */
function taskRuleAllows(PDO $pdo, string $taskSysId): bool
{
    $userId = $_SESSION['user_id'] ?? '';
    if (!$userId || !$taskSysId) return false;

    $stmt = $pdo->prepare("
        SELECT sw.assigned_to, sw.department_sys_id
        FROM tasks t
        LEFT JOIN service_works sw ON sw.sys_id = t.service_work_sys_id
        WHERE t.sys_id = ?
        LIMIT 1
    ");
    $stmt->execute([$taskSysId]);
    $svc = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$svc) return false; // unknown task => deny rather than guess

    // An assignee exists => only that person.
    if (!empty($svc['assigned_to'])) {
        return $svc['assigned_to'] === $userId;
    }

    // Nobody assigned => anyone in the service's department.
    if (empty($svc['department_sys_id'])) return false; // no department to compare => deny
    $emp = $pdo->prepare("SELECT department_sys_id FROM employees WHERE sys_id = ? LIMIT 1");
    $emp->execute([$userId]);
    $myDept = $emp->fetchColumn();
    return $myDept && $myDept === $svc['department_sys_id'];
}

/**
 * Same rule as canWorkOnTaskFinance(), but ends the request with a 403 if
 * the current user may not work on this task's finances. For task-scoped
 * API endpoints (the ones the Financial tab calls).
 */
function requireTaskFinanceAccess(PDO $pdo, string $taskSysId, string $permissionKey = 'full_accounting_access', bool $isApi = true): void
{
    if (canWorkOnTaskFinance($pdo, $taskSysId, $permissionKey)) return;

    http_response_code(403);
    if ($isApi) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'You are not assigned to this task, so you cannot work on its finances.']);
    } else {
        echo '<div style="font-family:Arial,sans-serif;padding:60px;text-align:center;color:#666;"><h2 style="color:#dc2626;">Access Restricted</h2><p>You are not assigned to this task.</p></div>';
    }
    exit;
}

/**
 * For endpoints that are reached from TWO places: a task's Financial tab
 * (which sends a task_id) and a Vendor/Client Ledger page (which doesn't,
 * since a ledger spans every task).
 *
 *   task id present -> the task-level rule (assignee / department, or the
 *                      accounting permission)
 *   task id absent  -> the accounting permission only (ledgers are an
 *                      accounting screen, so department membership alone
 *                      does not open them)
 */
function requireTaskOrPermission(PDO $pdo, ?string $taskSysId, string $permissionKey, bool $isApi = true): void
{
    if ($taskSysId) {
        requireTaskFinanceAccess($pdo, $taskSysId, $permissionKey, $isApi);
    } else {
        requirePermission($pdo, $permissionKey, $isApi);
    }
}

/**
 * update.php / delete.php only receive a financial_entries row id, not a
 * task id. This finds the task that row belongs to (NULL if it has none,
 * e.g. an expense or a loan-adjacent entry), so the same task-level rule can
 * be applied.
 */
function taskIdForFinancialEntry(PDO $pdo, string $entrySysId): ?string
{
    $stmt = $pdo->prepare("SELECT task_sys_id FROM financial_entries WHERE sys_id = ? LIMIT 1");
    $stmt->execute([$entrySysId]);
    $task = $stmt->fetchColumn();
    return $task ?: null;
}


/** Shared "no" response so every check words its refusal the same way. */
function denyAccess(bool $isApi, string $apiMessage, string $pageMessage = 'You do not have permission to view this page.'): void
{
    http_response_code(403);
    if ($isApi) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => $apiMessage]);
    } else {
        echo '<div style="font-family:Arial,sans-serif;padding:60px;text-align:center;color:#666;"><h2 style="color:#dc2626;">Access Restricted</h2><p>' . htmlspecialchars($pageMessage) . '</p></div>';
    }
    exit;
}

/**
 * Passes if the user holds ANY one of the given permissions. For endpoints
 * that serve several pages -- e.g. the cashflow API backs the Cashflow,
 * Payment and Receive report pages, so any of those three is enough.
 */
function requireAnyPermission(PDO $pdo, array $permissionKeys, bool $isApi = true): void
{
    foreach ($permissionKeys as $k) {
        if (canAccess($pdo, $k)) return;
    }
    denyAccess($isApi, 'You do not have permission to do this.');
}

/**
 * Moving money (paying, receiving, refunding, or editing/deleting an entry
 * that already moved money). Per the user's rule this needs BOTH:
 *   - the granular permission for the action ($moneyKey), AND
 *   - being allowed on the task: the task rule when a task id is given, or,
 *     when no task id is given (the call came from a ledger page), the
 *     matching ledger permission ($ledgerKey).
 * The ledger requirement stops someone skipping the task check just by
 * leaving task_id out of the request. Accounting staff (master switch or
 * role '0') bypass both.
 */
function canMoveMoney(PDO $pdo, ?string $taskSysId, string $moneyKey, string $ledgerKey): bool
{
    if (canAccess($pdo, 'full_accounting_access')) return true;
    if (!canAccess($pdo, $moneyKey)) return false;
    return $taskSysId ? taskRuleAllows($pdo, $taskSysId) : canAccess($pdo, $ledgerKey);
}

function requireMoneyAccess(PDO $pdo, ?string $taskSysId, string $moneyKey, string $ledgerKey, bool $isApi = true): void
{
    if (canMoveMoney($pdo, $taskSysId, $moneyKey, $ledgerKey)) return;
    denyAccess($isApi, 'You do not have permission to move money on this.');
}

/**
 * update.php / delete.php: does this entry's group touch a bank account?
 * If so, editing or deleting it changes a bank balance, so it counts as
 * moving money. Returns the permissions that apply, or NULL when the group
 * has no bank leg (a plain payable/receivable adjustment).
 *
 *   bank leg is a credit  -> money left the bank  -> entry_payment
 *   bank leg is a debit   -> money came in        -> entry_receive
 * The ledger key follows the party on the other side (vendor / client).
 *
 * Refunds share this rule: a refund payout to a client is a credit, so it
 * asks for entry_payment here rather than entry_refund. entry_refund is
 * enforced when the refund is declared or settled, not when a settled row
 * is later edited.
 */
function moneyPermissionsForEntry(PDO $pdo, string $entrySysId): ?array
{
    $g = $pdo->prepare("SELECT transaction_group_id FROM financial_entries WHERE sys_id = ? LIMIT 1");
    $g->execute([$entrySysId]);
    $groupId = $g->fetchColumn();
    if ($groupId === false) return null;          // unknown entry: let the endpoint's own 404 handle it
    $groupId = $groupId ?: $entrySysId;           // legacy row with no group id

    $legs = $pdo->prepare("SELECT account_head, type, user_type FROM financial_entries WHERE transaction_group_id = ? OR sys_id = ?");
    $legs->execute([$groupId, $groupId]);
    $rows = $legs->fetchAll(PDO::FETCH_ASSOC);

    $bankType = null;
    $partyType = null;
    foreach ($rows as $r) {
        if ($r['account_head'] === 'bank_account') $bankType = $bankType ?? $r['type'];
        elseif (in_array($r['user_type'], ['vendor', 'client'], true)) $partyType = $partyType ?? $r['user_type'];
    }
    if ($bankType === null) return null; // no bank leg => not money movement

    return [
        'money'  => $bankType === 'credit' ? 'entry_payment' : 'entry_receive',
        'ledger' => $partyType === 'vendor' ? 'ledger_vendor' : 'ledger_client',
    ];
}