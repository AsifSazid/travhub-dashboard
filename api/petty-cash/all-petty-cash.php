<?php
// all.php - Petty Cash API for fetching all entries
session_start();

require '../../server/db_connection.php';          // $pdo

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

error_reporting(E_ALL);
ini_set('display_errors', 1);

/* =====================================================
   HELPER FUNCTIONS
===================================================== */
function extractMetaDataInfo($metaData) {
    $createdAt = null;
    $updatedAt = null;
    $parsedMeta = [];
    
    // If meta_data is already an array
    if (is_array($metaData)) {
        $parsedMeta = $metaData;
        
        // Extract created_at
        if (isset($metaData['created_by_date'])) {
            if (is_string($metaData['created_by_date'])) {
                $created_parts = explode('; ', $metaData['created_by_date']);
                $createdAt = count($created_parts) > 1 ? $created_parts[1] : $metaData['created_by_date'];
            } elseif (is_array($metaData['created_by_date'])) {
                // Handle if created_by_date is an array
                $createdAt = json_encode($metaData['created_by_date']);
            }
        }
        
        // Extract last_updated
        if (isset($metaData['updated_by_date']) && is_array($metaData['updated_by_date']) && !empty($metaData['updated_by_date'])) {
            $last_update = end($metaData['updated_by_date']);
            if (is_string($last_update)) {
                $update_parts = explode('; ', $last_update);
                $updatedAt = count($update_parts) > 1 ? $update_parts[1] : $last_update;
            } elseif (is_array($last_update)) {
                $updatedAt = json_encode($last_update);
            }
        }
    }
    // If meta_data is JSON string
    elseif (is_string($metaData) && !empty($metaData)) {
        $decoded = json_decode($metaData, true);
        if ($decoded !== null && is_array($decoded)) {
            return extractMetaDataInfo($decoded);
        }
    }
    
    return [
        'meta_data' => $parsedMeta,
        'created_at' => $createdAt,
        'updated_at' => $updatedAt
    ];
}

/* =====================================================
   FETCH ALL PETTY CASH ENTRIES
===================================================== */
try {
    // Optional query parameters for filtering
    $type = $_GET['type'] ?? null;
    $user_id = $_GET['user_id'] ?? null;
    $date_from = $_GET['date_from'] ?? null;
    $date_to = $_GET['date_to'] ?? null;
    $limit = $_GET['limit'] ?? 1000; // Default limit
    $offset = $_GET['offset'] ?? 0;
    $search = $_GET['search'] ?? null;
    $status = $_GET['status'] ?? null;
    
    // Base query
    $sql = "SELECT * FROM petty_cashes WHERE 1=1";
    $params = [];
    
    // Add filters
    if ($type) {
        $sql .= " AND type = :type";
        $params[':type'] = $type;
    }
    
    if ($user_id) {
        $sql .= " AND (user_sys_id = :user_id OR to_user_sys_id = :user_id)";
        $params[':user_id'] = $user_id;
    }
    
    if ($date_from) {
        $sql .= " AND date >= :date_from";
        $params[':date_from'] = $date_from;
    }
    
    if ($date_to) {
        $sql .= " AND date <= :date_to";
        $params[':date_to'] = $date_to;
    }
    
    if ($status) {
        $sql .= " AND status = :status";
        $params[':status'] = $status;
    }
    
    // Search functionality
    if ($search) {
        $sql .= " AND (
            user_name LIKE :search OR 
            to_user_name LIKE :search OR 
            sys_id LIKE :search OR 
            purpose LIKE :search OR 
            details LIKE :search OR
            ref LIKE :search
        )";
        $params[':search'] = "%{$search}%";
    }
    
    // Order by date (newest first) - using meta_data for creation date
    $sql .= " ORDER BY date DESC, id DESC";
    
    // Add limit and offset
    $sql .= " LIMIT :limit OFFSET :offset";
    $params[':limit'] = (int)$limit;
    $params[':offset'] = (int)$offset;
    
    // Prepare and execute
    $stmt = $pdo->prepare($sql);
    
    // Bind parameters
    foreach ($params as $key => $value) {
        if ($key === ':limit' || $key === ':offset') {
            $stmt->bindValue($key, $value, PDO::PARAM_INT);
        } else {
            $stmt->bindValue($key, $value);
        }
    }
    
    $stmt->execute();
    
    // Fetch all entries
    $entries = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Process each entry
    foreach ($entries as &$entry) {
        // Parse meta_data JSON and extract created_at, updated_at
        $metaInfo = extractMetaDataInfo($entry['meta_data'] ?? '');
        
        $entry['meta_data'] = $metaInfo['meta_data'];
        $entry['created_at'] = $metaInfo['created_at'];
        $entry['updated_at'] = $metaInfo['updated_at'];
        
        // Convert amount to float for consistency
        if (isset($entry['amount'])) {
            $entry['amount'] = (float)$entry['amount'];
        }
        
        // Ensure status field exists
        if (!isset($entry['status'])) {
            $entry['status'] = 'pending';
        }
        
        // Format type for display
        $entry['type_display'] = ucwords(str_replace('_', ' ', $entry['type']));
        
        // Calculate days ago
        if ($entry['created_at']) {
            $createdDate = DateTime::createFromFormat('d-m-Y H:i', $entry['created_at']);
            if ($createdDate) {
                $now = new DateTime();
                $interval = $now->diff($createdDate);
                $entry['days_ago'] = $interval->days;
                $entry['created_at_formatted'] = $createdDate->format('M d, Y h:i A');
            } else {
                $entry['days_ago'] = null;
                $entry['created_at_formatted'] = $entry['created_at'];
            }
        } else {
            $entry['days_ago'] = null;
            $entry['created_at_formatted'] = null;
        }
    }
    
    // Get total count for pagination
    $count_sql = "SELECT COUNT(*) as total FROM petty_cashes WHERE 1=1";
    $count_params = [];
    
    if ($type) {
        $count_sql .= " AND type = :type";
        $count_params[':type'] = $type;
    }
    
    if ($user_id) {
        $count_sql .= " AND (user_sys_id = :user_id OR to_user_sys_id = :user_id)";
        $count_params[':user_id'] = $user_id;
    }
    
    if ($date_from) {
        $count_sql .= " AND date >= :date_from";
        $count_params[':date_from'] = $date_from;
    }
    
    if ($date_to) {
        $count_sql .= " AND date <= :date_to";
        $count_params[':date_to'] = $date_to;
    }
    
    if ($status) {
        $count_sql .= " AND status = :status";
        $count_params[':status'] = $status;
    }
    
    if ($search) {
        $count_sql .= " AND (
            user_name LIKE :search OR 
            to_user_name LIKE :search OR 
            sys_id LIKE :search OR 
            purpose LIKE :search OR 
            details LIKE :search OR
            ref LIKE :search
        )";
        $count_params[':search'] = "%{$search}%";
    }
    
    $count_stmt = $pdo->prepare($count_sql);
    foreach ($count_params as $key => $value) {
        $count_stmt->bindValue($key, $value);
    }
    $count_stmt->execute();
    $total_count = $count_stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Calculate summary statistics
    $summary_sql = "SELECT 
        COUNT(*) as total_entries,
        SUM(amount) as total_amount,
        SUM(CASE WHEN type = 'conveyance_bill' THEN amount ELSE 0 END) as conveyance_total,
        SUM(CASE WHEN type = 'other_bill' THEN amount ELSE 0 END) as other_bill_total,
        SUM(CASE WHEN type = 'loan' THEN amount ELSE 0 END) as loan_total,
        SUM(CASE WHEN type = 'petty_cash' THEN amount ELSE 0 END) as petty_cash_total,
        COUNT(CASE WHEN type = 'conveyance_bill' THEN 1 END) as conveyance_count,
        COUNT(CASE WHEN type = 'other_bill' THEN 1 END) as other_bill_count,
        COUNT(CASE WHEN type = 'loan' THEN 1 END) as loan_count,
        COUNT(CASE WHEN type = 'petty_cash' THEN 1 END) as petty_cash_count,
        SUM(CASE WHEN status = 'pending' THEN amount ELSE 0 END) as pending_amount,
        SUM(CASE WHEN status = 'approved' THEN amount ELSE 0 END) as approved_amount,
        SUM(CASE WHEN status = 'rejected' THEN amount ELSE 0 END) as rejected_amount,
        SUM(CASE WHEN status = 'repaid' THEN amount ELSE 0 END) as repaid_amount,
        COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending_count,
        COUNT(CASE WHEN status = 'approved' THEN 1 END) as approved_count,
        COUNT(CASE WHEN status = 'rejected' THEN 1 END) as rejected_count,
        COUNT(CASE WHEN status = 'repaid' THEN 1 END) as repaid_count
    FROM petty_cashes WHERE 1=1";
    
    $summary_params = [];
    
    if ($type) {
        $summary_sql .= " AND type = :type";
        $summary_params[':type'] = $type;
    }
    
    if ($user_id) {
        $summary_sql .= " AND (user_sys_id = :user_id OR to_user_sys_id = :user_id)";
        $summary_params[':user_id'] = $user_id;
    }
    
    if ($date_from) {
        $summary_sql .= " AND date >= :date_from";
        $summary_params[':date_from'] = $date_from;
    }
    
    if ($date_to) {
        $summary_sql .= " AND date <= :date_to";
        $summary_params[':date_to'] = $date_to;
    }
    
    if ($status) {
        $summary_sql .= " AND status = :status";
        $summary_params[':status'] = $status;
    }
    
    if ($search) {
        $summary_sql .= " AND (
            user_name LIKE :search OR 
            to_user_name LIKE :search OR 
            sys_id LIKE :search OR 
            purpose LIKE :search OR 
            details LIKE :search OR
            ref LIKE :search
        )";
        $summary_params[':search'] = "%{$search}%";
    }
    
    $summary_stmt = $pdo->prepare($summary_sql);
    foreach ($summary_params as $key => $value) {
        $summary_stmt->bindValue($key, $value);
    }
    $summary_stmt->execute();
    $summary = $summary_stmt->fetch(PDO::FETCH_ASSOC);
    
    // Get recent entries (last 7 days based on date field)
    $recent_sql = "SELECT * FROM petty_cashes 
                   WHERE date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
                   ORDER BY date DESC, id DESC 
                   LIMIT 10";
    $recent_stmt = $pdo->query($recent_sql);
    $recent_entries = $recent_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Process recent entries
    foreach ($recent_entries as &$entry) {
        $metaInfo = extractMetaDataInfo($entry['meta_data'] ?? '');
        $entry['meta_data'] = $metaInfo['meta_data'];
        $entry['created_at'] = $metaInfo['created_at'];
        $entry['updated_at'] = $metaInfo['updated_at'];
        
        if (isset($entry['amount'])) {
            $entry['amount'] = (float)$entry['amount'];
        }
    }
    
    // Get top users by amount
    $top_users_sql = "SELECT 
        user_name,
        user_sys_id,
        COUNT(*) as entry_count,
        SUM(amount) as total_amount
    FROM petty_cashes 
    WHERE user_name IS NOT NULL AND user_name != ''
    GROUP BY user_name, user_sys_id 
    ORDER BY total_amount DESC 
    LIMIT 5";
    $top_users_stmt = $pdo->query($top_users_sql);
    $top_users = $top_users_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Monthly summary for chart (last 6 months)
    $monthly_sql = "SELECT 
        DATE_FORMAT(date, '%Y-%m') as month,
        COUNT(*) as count,
        SUM(amount) as total_amount
    FROM petty_cashes 
    WHERE date >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
    GROUP BY DATE_FORMAT(date, '%Y-%m')
    ORDER BY month";
    $monthly_stmt = $pdo->query($monthly_sql);
    $monthly_data = $monthly_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Status distribution
    $status_sql = "SELECT 
        status,
        COUNT(*) as count,
        SUM(amount) as total_amount
    FROM petty_cashes 
    GROUP BY status";
    $status_stmt = $pdo->query($status_sql);
    $status_data = $status_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Type distribution
    $type_distribution_sql = "SELECT 
        type,
        COUNT(*) as count,
        SUM(amount) as total_amount
    FROM petty_cashes 
    GROUP BY type
    ORDER BY total_amount DESC";
    $type_distribution_stmt = $pdo->query($type_distribution_sql);
    $type_distribution = $type_distribution_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Prepare response
    $response = [
        'success' => true,
        'message' => 'Petty cash entries retrieved successfully',
        'data' => [
            'entries' => $entries,
            'summary' => [
                'total_entries' => (int)$summary['total_entries'],
                'total_amount' => (float)($summary['total_amount'] ?? 0),
                'by_type' => [
                    'conveyance_bill' => [
                        'total' => (float)($summary['conveyance_total'] ?? 0),
                        'count' => (int)($summary['conveyance_count'] ?? 0)
                    ],
                    'other_bill' => [
                        'total' => (float)($summary['other_bill_total'] ?? 0),
                        'count' => (int)($summary['other_bill_count'] ?? 0)
                    ],
                    'loan' => [
                        'total' => (float)($summary['loan_total'] ?? 0),
                        'count' => (int)($summary['loan_count'] ?? 0)
                    ],
                    'petty_cash' => [
                        'total' => (float)($summary['petty_cash_total'] ?? 0),
                        'count' => (int)($summary['petty_cash_count'] ?? 0)
                    ]
                ],
                'by_status' => [
                    'pending' => [
                        'total' => (float)($summary['pending_amount'] ?? 0),
                        'count' => (int)($summary['pending_count'] ?? 0)
                    ],
                    'approved' => [
                        'total' => (float)($summary['approved_amount'] ?? 0),
                        'count' => (int)($summary['approved_count'] ?? 0)
                    ],
                    'rejected' => [
                        'total' => (float)($summary['rejected_amount'] ?? 0),
                        'count' => (int)($summary['rejected_count'] ?? 0)
                    ],
                    'repaid' => [
                        'total' => (float)($summary['repaid_amount'] ?? 0),
                        'count' => (int)($summary['repaid_count'] ?? 0)
                    ]
                ],
                'type_distribution' => $type_distribution,
                'status_distribution' => $status_data,
                'pagination' => [
                    'total' => (int)$total_count,
                    'limit' => (int)$limit,
                    'offset' => (int)$offset,
                    'has_more' => ($offset + count($entries)) < $total_count
                ],
                'recent_entries' => $recent_entries,
                'top_users' => $top_users,
                'monthly_data' => $monthly_data
            ]
        ]
    ];
    
    echo json_encode($response, JSON_PRETTY_PRINT);
    
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage(),
        'error_details' => $e->getMessage()
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage(),
        'error_details' => $e->getMessage()
    ]);
}