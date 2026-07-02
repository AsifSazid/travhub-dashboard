<?php
// PATH: /api/financial_entries/fin-entries.php
// Changes from original:
//   - Server-side filtering: type, related_type, is_paid, is_partial
//   - প্রতিটা sale entry তে received_amount, discounted_amount, remaining_amount যোগ
//   - শুধু requested user এর data (security)

require '../../server/db_connection.php';
header('Content-Type: application/json');

$userId = $_GET['id'] ?? '';

if (empty($userId)) {
    echo json_encode(['success' => false, 'message' => 'User ID is required']);
    exit;
}

/* ================= FILTERS ================= */
$filterType        = $_GET['type']         ?? null;
$filterRelatedType = isset($_GET['related_type']) ? (int)$_GET['related_type'] : null;
$filterIsPaid      = isset($_GET['is_paid'])      ? (int)$_GET['is_paid']      : null;
$filterIsPartial   = isset($_GET['is_partial'])   ? (int)$_GET['is_partial']   : null;
$filterIsInvoiced  = isset($_GET['is_invoiced'])  ? (int)$_GET['is_invoiced']  : null;
$filterTaskId      = $_GET['task_id']      ?? null; // task_sys_id filter

try {
    /* ================= BUILD QUERY ================= */
    $conditions = ["user_sys_id = :user_id"];
    $params     = [':user_id' => $userId];

    if ($filterType !== null && in_array($filterType, ['credit', 'debit'], true)) {
        $conditions[] = "type = :type";
        $params[':type'] = $filterType;
    }

    if ($filterRelatedType !== null) {
        $conditions[] = "related_type = :related_type";
        $params[':related_type'] = $filterRelatedType;
    }

    if ($filterIsPaid !== null) {
        $conditions[] = "is_paid = :is_paid";
        $params[':is_paid'] = $filterIsPaid;
    }

    if ($filterIsPartial !== null) {
        $conditions[] = "is_partial = :is_partial";
        $params[':is_partial'] = $filterIsPartial;
    }

    if ($filterIsInvoiced !== null) {
        $conditions[] = "is_invoiced = :is_invoiced";
        $params[':is_invoiced'] = $filterIsInvoiced;
    }

    if (!empty($filterTaskId)) {
        $conditions[] = "task_sys_id = :task_sys_id";
        $params[':task_sys_id'] = $filterTaskId;
    }

    $where = implode(' AND ', $conditions);

    $stmt = $pdo->prepare("
        SELECT * FROM financial_entries
        WHERE {$where}
        ORDER BY date DESC, id DESC
    ");
    $stmt->execute($params);
    $finStmts = $stmt->fetchAll(PDO::FETCH_ASSOC);

    /* ================= AMOUNT CALCULATION ================= */
    // ref column এ JSON array — linked entry sys_ids
    //
    // Client sale (debit, related_type=1):
    //   received_amount   = linked receive entries (credit, rt=3) এর sum
    //   discounted_amount = linked discount entries (credit, rt=5) এর sum
    //   remaining_amount  = amount - received - discounted
    //
    // Vendor purchase (credit, related_type=2):
    //   paid_amount       = linked payment entries (debit, rt=4) এর sum
    //   discounted_amount = linked vendor discount entries (debit, rt=5) এর sum
    //   remaining_amount  = amount - paid - discounted
    foreach ($finStmts as &$entry) {
        $entry['received_amount']   = 0.0;
        $entry['paid_amount']       = 0.0;
        $entry['discounted_amount'] = 0.0;
        $entry['remaining_amount']  = (float)$entry['amount'];

        $refIds = !empty($entry['ref']) ? json_decode($entry['ref'], true) : null;
        $hasRefs = is_array($refIds) && !empty($refIds);

        // ===== Client Sale (debit, related_type=1) =====
        if (
            strtolower($entry['type']) === 'debit' &&
            (int)$entry['related_type'] === 1 &&
            $hasRefs
        ) {
            $ph = implode(',', array_fill(0, count($refIds), '?'));

            $r = $pdo->prepare("
                SELECT COALESCE(SUM(amount), 0) FROM financial_entries
                WHERE sys_id IN ($ph) AND type = 'credit' AND related_type = 3
            ");
            $r->execute($refIds);
            $entry['received_amount'] = (float)$r->fetchColumn();

            $d = $pdo->prepare("
                SELECT COALESCE(SUM(amount), 0) FROM financial_entries
                WHERE sys_id IN ($ph) AND type = 'credit' AND related_type = 5
            ");
            $d->execute($refIds);
            $entry['discounted_amount'] = (float)$d->fetchColumn();

            $entry['remaining_amount'] = max(
                0,
                (float)$entry['amount'] - $entry['received_amount'] - $entry['discounted_amount']
            );
        }

        // ===== Vendor Purchase (credit, related_type=2) =====
        if (
            strtolower($entry['type']) === 'credit' &&
            (int)$entry['related_type'] === 2 &&
            $hasRefs
        ) {
            $ph = implode(',', array_fill(0, count($refIds), '?'));

            // Payment entries (debit, related_type=4)
            $p = $pdo->prepare("
                SELECT COALESCE(SUM(amount), 0) FROM financial_entries
                WHERE sys_id IN ($ph) AND type = 'debit' AND related_type = 4
            ");
            $p->execute($refIds);
            $entry['paid_amount'] = (float)$p->fetchColumn();

            // Vendor discount entries (debit, related_type=5)
            $d = $pdo->prepare("
                SELECT COALESCE(SUM(amount), 0) FROM financial_entries
                WHERE sys_id IN ($ph) AND type = 'debit' AND related_type = 5
            ");
            $d->execute($refIds);
            $entry['discounted_amount'] = (float)$d->fetchColumn();

            $entry['remaining_amount'] = max(
                0,
                (float)$entry['amount'] - $entry['paid_amount'] - $entry['discounted_amount']
            );
        }
    }
    unset($entry);

    echo json_encode([
        'finStmts' => $finStmts,
        'success'  => true,
        'count'    => count($finStmts)
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    exit;
}