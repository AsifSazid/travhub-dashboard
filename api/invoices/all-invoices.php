<?php
// api/invoices/all-invoices.php
// Changes:
//   - client_id filter (GET param)
//   - status filter: pending, paid, partial, overdue
//   - unpaid_only filter (status != paid)

session_start();
require '../../server/db_connection.php';
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: Content-Type');

$filterClientId = $_GET['client_id'] ?? null;
$filterStatus   = $_GET['status']    ?? null; // paid|partial|pending|overdue|unpaid
$filterUnpaid   = isset($_GET['unpaid_only']) ? (bool)$_GET['unpaid_only'] : false;

try {
    $conditions = [];
    $params     = [];

    if (!empty($filterClientId)) {
        $conditions[] = "client_sys_id = :client_id";
        $params[':client_id'] = $filterClientId;
    }

    // status=unpaid → paid_amount >= 0 AND due_amount > 0
    if ($filterUnpaid || $filterStatus === 'unpaid') {
        $conditions[] = "due_amount > 0";
    } elseif (!empty($filterStatus) && $filterStatus !== 'all') {
        // status column এ numeric: 0=pending, 1=paid, 2=partial, 4=overdue
        $statusMap = ['pending' => 0, 'paid' => 1, 'partial' => 2, 'overdue' => 4];
        if (isset($statusMap[$filterStatus])) {
            $conditions[] = "status = :status";
            $params[':status'] = $statusMap[$filterStatus];
        }
    }

    $where = !empty($conditions) ? "WHERE " . implode(" AND ", $conditions) : "";

    $stmt = $pdo->prepare("
        SELECT * FROM invoices
        {$where}
        ORDER BY created_at DESC
    ");
    $stmt->execute($params);

    $invoices = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $client_info = json_decode($row['client_info'] ?? '{}', true) ?: [];
        $work_items  = json_decode($row['work_items']  ?? '[]', true) ?: [];
        $meta_data   = json_decode($row['meta_data']   ?? '{}', true) ?: [];

        // Status string — DB status column থেকে
        $dbStatus = (int)($row['status'] ?? 0);
        $statusStr = match($dbStatus) {
            1 => 'paid',
            2 => 'partial',
            4 => 'overdue',
            default => 'pending'
        };

        // Overdue check — 30 days পার হলে
        if ($statusStr !== 'paid') {
            $dueDate = new DateTime($row['date']);
            $dueDate->modify('+30 days');
            if (new DateTime() > $dueDate) {
                $statusStr = 'overdue';
            }
        }

        $dueDate = new DateTime($row['date']);
        $dueDate->modify('+30 days');

        $createdBy = $meta_data['created_by_date']['user'] ?? 'System';
        $updatedBy = 'Not updated';
        if (!empty($meta_data['updated_by_date']) && is_array($meta_data['updated_by_date'])) {
            $last = end($meta_data['updated_by_date']);
            $updatedBy = $last['user'] ?? 'System';
        }

        $invoices[] = [
            "id"              => (int)$row['id'],
            "invoice_no"      => $row['sys_id'],
            "sys_id"          => $row['sys_id'],
            "client_sys_id"   => $row['client_sys_id'] ?? '',
            "client_name"     => $row['client_name'] ?? $client_info['title'] ?? 'Unknown',
            "client_email"    => $client_info['cc'] ?? '',
            "phone"           => $client_info['phone_no'] ?? '',
            "total_amount"    => (float)$row['total_amount'],
            "paid_amount"     => (float)$row['paid_amount'],
            "due_amount"      => (float)$row['due_amount'],
            "status"          => $statusStr,
            "status_code"     => $dbStatus,
            "invoice_date"    => $row['date'],
            "due_date"        => $dueDate->format('Y-m-d'),
            "created_at"      => $row['created_at'],
            "updated_at"      => $row['updated_at'],
            "created_by"      => $createdBy,
            "updated_by"      => $updatedBy,
            "currency"        => "BDT",
            "financial_entry_ids" => json_decode($row['financial_entry_ids'] ?? '[]', true) ?: [],
            "items" => array_map(fn($item) => [
                'description' => $item['title']   ?? 'Service',
                'quantity'    => (int)($item['qty']  ?? 1),
                'unit_price'  => (float)($item['rate'] ?? 0),
                'total'       => (float)($item['amount'] ?? 0),
                'fe_sys_id'   => $item['fe_sys_id'] ?? null,
            ], $work_items)
        ];
    }

    echo json_encode([
        'success'   => true,
        'invoices'  => $invoices,
        'total'     => count($invoices),
        'timestamp' => date('Y-m-d H:i:s')
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success'  => false,
        'message'  => 'Database error: ' . $e->getMessage(),
        'invoices' => []
    ]);
}