<?php
// PATH: /api/reports/diagnostics/legacy-chain-check.php
// GET ?owned_by=<optional employee name/id to filter by>
//
// Diagnostic report: how much of the legacy com_works -> old_tasks ->
// financial_entries chain still exists, and whether any of that legacy
// financial_entries data could leak into the new v2 (account_head-based)
// reports.
//
// Chain:
//   com_works.sys_id       (owned_by = employee who created the Work)
//     <- old_tasks.work_sys_id
//   old_tasks.sys_id
//     <- financial_entries.task_sys_id
//
// A financial_entries row is "legacy" if its task_sys_id points to an
// old_tasks row (not the new tasks table) -- these rows predate the
// account_head/transaction_group_id migration and will have NULL
// account_head (or a backfilled guess), which the v2 reports should either
// exclude or clearly flag rather than silently mixing in.

require '../../../server/db_connection.php';
header('Content-Type: application/json');

$ownedBy = $_GET['owned_by'] ?? null;

try {
    // ---- 1. com_works count (optionally filtered by owned_by) ----
    if ($ownedBy) {
        $cw = $pdo->prepare("SELECT sys_id, client_name, title, owned_by FROM com_works WHERE owned_by = ?");
        $cw->execute([$ownedBy]);
    } else {
        $cw = $pdo->query("SELECT sys_id, client_name, title, owned_by FROM com_works");
    }
    $comWorks = $cw->fetchAll(PDO::FETCH_ASSOC);
    $comWorkIds = array_column($comWorks, 'sys_id');

    // ---- 2. old_tasks linked to those com_works ----
    $oldTasks = [];
    if ($comWorkIds) {
        $placeholders = implode(',', array_fill(0, count($comWorkIds), '?'));
        $ot = $pdo->prepare("SELECT sys_id, work_sys_id, title, status FROM old_tasks WHERE work_sys_id IN ($placeholders)");
        $ot->execute($comWorkIds);
        $oldTasks = $ot->fetchAll(PDO::FETCH_ASSOC);
    }
    $oldTaskIds = array_column($oldTasks, 'sys_id');

    // ---- 3. financial_entries rows referencing those old_tasks ----
    $legacyEntries = [];
    if ($oldTaskIds) {
        $placeholders = implode(',', array_fill(0, count($oldTaskIds), '?'));
        $fe = $pdo->prepare("
            SELECT id, sys_id, task_sys_id, transaction_group_id, account_head, user_type, type, amount, date
            FROM financial_entries WHERE task_sys_id IN ($placeholders)
        ");
        $fe->execute($oldTaskIds);
        $legacyEntries = $fe->fetchAll(PDO::FETCH_ASSOC);
    }

    // ---- 4. How many of those legacy entries have account_head set (from
    //         the migration backfill) vs NULL (never touched) ----
    $withHead = 0; $withoutHead = 0; $withGroupId = 0;
    $headBreakdown = [];
    foreach ($legacyEntries as $e) {
        if ($e['account_head']) {
            $withHead++;
            $headBreakdown[$e['account_head']] = ($headBreakdown[$e['account_head']] ?? 0) + 1;
        } else {
            $withoutHead++;
        }
        if ($e['transaction_group_id']) $withGroupId++;
    }

    // ---- 5. Cross-check: does task_sys_id ever ALSO match the new `tasks`
    //         table? (would mean a sys_id collision between old_tasks and
    //         tasks -- worth flagging if it ever happens) ----
    $collisions = [];
    if ($oldTaskIds) {
        $placeholders = implode(',', array_fill(0, count($oldTaskIds), '?'));
        $col = $pdo->prepare("SELECT sys_id FROM tasks WHERE sys_id IN ($placeholders)");
        $col->execute($oldTaskIds);
        $collisions = array_column($col->fetchAll(PDO::FETCH_ASSOC), 'sys_id');
    }

    echo json_encode([
        'success' => true,
        'filter' => ['owned_by' => $ownedBy],
        'summary' => [
            'com_works_count'                 => count($comWorks),
            'old_tasks_count'                  => count($oldTasks),
            'legacy_financial_entries_count'   => count($legacyEntries),
            'legacy_entries_with_account_head' => $withHead,
            'legacy_entries_without_account_head' => $withoutHead,
            'legacy_entries_with_group_id'     => $withGroupId,
            'account_head_breakdown'           => $headBreakdown,
            'sys_id_collisions_with_new_tasks_table' => $collisions, // should always be empty
        ],
        'detail' => [
            'com_works'       => $comWorks,
            'old_tasks'       => $oldTasks,
            'legacy_entries'  => $legacyEntries,
        ],
    ]);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}