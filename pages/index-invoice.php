<?php
session_start();
require '../server/db_connection.php';

// Load invoices from database
try {
    $stmt = $pdo->query("
        SELECT * FROM invoices
        ORDER BY created_at DESC
    ");

    $invoices = [];

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $client_info = json_decode($row['client_info'], true);
        $work_items = json_decode($row['work_items'], true) ?: [];

        // Calculate status
        $status = 'pending';
        if ($row['due_amount'] == 0) {
            $status = 'paid';
        } elseif ($row['paid_amount'] > 0 && $row['due_amount'] > 0) {
            $status = 'partial';
        }

        // Check overdue
        $created_date = new DateTime($row['created_at']);
        $due_date = $created_date->modify('+30 days');
        $now = new DateTime();
        
        if ($status !== 'paid' && $now > $due_date) {
            $status = 'overdue';
        }

        $invoices[] = [
            "id" => $row['id'],
            "invoice_no" => $row['invoice_no'],
            "client_name" => $client_info['title'] ?? 'Unknown Client',
            "client_email" => $client_info['cc'] ?? '',
            "phone" => $client_info['phone_no'] ?? '',
            "total_amount" => floatval($row['total_amount']),
            "paid_amount" => floatval($row['paid_amount']),
            "due_amount" => floatval($row['due_amount']),
            "created_at" => $row['created_at'],
            "updated_at" => $row['updated_at'],
            "invoice_date" => $row['date'],
            "due_date" => date('Y-m-d', strtotime($row['date'] . ' +30 days')),
            "status" => $status,
            "currency" => "BDT",
            "description" => "Visa Application Services",
            "items" => array_map(function ($item) {
                return [
                    'description' => $item['title'] ?? 'Service',
                    'quantity' => intval($item['qty'] ?? 1),
                    'unit_price' => floatval($item['rate'] ?? 0),
                    'total' => floatval($item['amount'] ?? 0)
                ];
            }, $work_items)
        ];
    }
} catch (Exception $e) {
    $invoices = [];
}

// Get IP path
$ip_port = @file_get_contents('../ippath.txt');
if (empty($ip_port)) {
    $ip_port = "http://103.104.219.3:898";
}
$base_ip_path = trim($ip_port, "/");
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Accounting - Invoice Management</title>
    <link rel="icon" type="image/png" href="../assets/images/logo/round-logo.png" sizes="16x16">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .invoice-card {
            transition: all 0.3s ease;
            border-left: 4px solid transparent;
            cursor: pointer;
        }

        .invoice-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
        }

        .status-badge {
            font-size: 0.75rem;
            padding: 0.35rem 0.85rem;
            border-radius: 9999px;
            font-weight: 600;
            letter-spacing: 0.3px;
        }

        .status-pending {
            background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
            color: white;
            box-shadow: 0 2px 5px rgba(245, 158, 11, 0.2);
        }

        .status-paid {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
            box-shadow: 0 2px 5px rgba(16, 185, 129, 0.2);
        }

        .status-overdue {
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
            color: white;
            box-shadow: 0 2px 5px rgba(239, 68, 68, 0.2);
        }

        .status-partial {
            background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%);
            color: white;
            box-shadow: 0 2px 5px rgba(139, 92, 246, 0.2);
        }

        .amount-badge {
            font-size: 0.9rem;
            padding: 0.4rem 0.9rem;
            border-radius: 8px;
            font-weight: 600;
        }

        .invoice-type-badge {
            font-size: 0.7rem;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-weight: 600;
        }

        .type-visa {
            background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%);
            color: #1e40af;
            border: 1px solid #93c5fd;
        }

        .type-ticket {
            background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);
            color: #0c4a6e;
            border: 1px solid #7dd3fc;
        }

        .type-service {
            background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%);
            color: #166534;
            border: 1px solid #86efac;
        }

        .fade-in {
            animation: fadeIn 0.5s ease-in-out;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(15px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .action-btn {
            transition: all 0.2s ease;
            border: 1px solid transparent;
        }

        .action-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: rgba(0, 0, 0, 0.5);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 1000;
            opacity: 0;
            visibility: hidden;
            transition: opacity 0.3s ease, visibility 0.3s ease;
        }

        .modal-overlay.active {
            opacity: 1;
            visibility: visible;
        }

        .modal-content {
            background: white;
            border-radius: 0.75rem;
            width: 90%;
            max-width: 500px;
            max-height: 90vh;
            overflow-y: auto;
            transform: translateY(20px);
            transition: transform 0.3s ease;
        }

        .modal-overlay.active .modal-content {
            transform: translateY(0);
        }
    </style>
</head>

<body class="bg-gray-50 font-sans">
    <!-- Top Navigation -->
    <?php include '../elements/header.php'; ?>

    <!-- Sidebar -->
    <?php include '../elements/aside.php'; ?>
    
    <!-- Preview Modal -->
    <div id="previewModal" class="preview-modal">
        <div class="preview-content">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-semibold text-gray-800" id="previewTitle">File Preview</h3>
                <button onclick="closePreview()" class="text-gray-500 hover:text-gray-700 text-2xl">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div id="modalPreviewContent" class="p-4">
                <!-- Preview content will be loaded here -->
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <main id="mainContent" class="pt-16 pl-0 lg:pl-64 transition-all duration-300 h-full">
        <div class="p-4 md:p-6 h-full">
            <!-- Header -->
            <div class="mb-6">
                <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                    <div>
                        <h1 class="text-2xl md:text-3xl font-bold text-gray-800">Invoice Management</h1>
                        <p class="text-gray-600 mt-2">Manage and track all invoices in one place</p>
                    </div>
                    <div class="flex items-center gap-3">
                        <button id="refresh-btn" class="bg-white hover:bg-gray-50 text-gray-700 font-medium py-2.5 px-4 rounded-lg transition duration-300 flex items-center border border-gray-300">
                            <i class="fas fa-sync-alt mr-2"></i> Refresh
                        </button>
                        <a href="create-invoice.php" class="bg-gradient-to-r from-green-600 to-emerald-700 hover:from-green-700 hover:to-emerald-800 text-white font-medium py-2.5 px-5 rounded-lg transition duration-300 flex items-center shadow">
                            <i class="fas fa-plus mr-2"></i> Create Invoice
                        </a>
                    </div>
                </div>
            </div>

            <!-- Stats Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
                <div class="bg-white rounded-xl shadow-sm p-5 border border-gray-200">
                    <div class="flex items-center">
                        <div class="w-12 h-12 bg-gradient-to-r from-blue-100 to-blue-200 rounded-lg flex items-center justify-center mr-4">
                            <i class="fas fa-file-invoice text-blue-600 text-xl"></i>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500 font-medium">Total Invoices</p>
                            <h3 id="total-invoices" class="text-2xl font-bold text-gray-800"><?php echo count($invoices); ?></h3>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-xl shadow-sm p-5 border border-gray-200">
                    <div class="flex items-center">
                        <div class="w-12 h-12 bg-gradient-to-r from-green-100 to-green-200 rounded-lg flex items-center justify-center mr-4">
                            <i class="fas fa-money-bill-wave text-green-600 text-xl"></i>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500 font-medium">Total Revenue</p>
                            <h3 id="total-revenue" class="text-2xl font-bold text-gray-800">
                                ৳ <?php 
                                    $total = 0;
                                    foreach($invoices as $invoice) {
                                        $total += $invoice['total_amount'];
                                    }
                                    echo number_format($total, 2);
                                ?>
                            </h3>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-xl shadow-sm p-5 border border-gray-200">
                    <div class="flex items-center">
                        <div class="w-12 h-12 bg-gradient-to-r from-amber-100 to-amber-200 rounded-lg flex items-center justify-center mr-4">
                            <i class="fas fa-clock text-amber-600 text-xl"></i>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500 font-medium">Pending</p>
                            <h3 id="pending-invoices" class="text-2xl font-bold text-gray-800">
                                <?php 
                                    $pending = 0;
                                    foreach($invoices as $invoice) {
                                        if(in_array($invoice['status'], ['pending', 'partial'])) {
                                            $pending++;
                                        }
                                    }
                                    echo $pending;
                                ?>
                            </h3>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-xl shadow-sm p-5 border border-gray-200">
                    <div class="flex items-center">
                        <div class="w-12 h-12 bg-gradient-to-r from-red-100 to-red-200 rounded-lg flex items-center justify-center mr-4">
                            <i class="fas fa-exclamation-triangle text-red-600 text-xl"></i>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500 font-medium">Overdue</p>
                            <h3 id="overdue-invoices" class="text-2xl font-bold text-gray-800">
                                <?php 
                                    $overdue = 0;
                                    foreach($invoices as $invoice) {
                                        if($invoice['status'] == 'overdue') {
                                            $overdue++;
                                        }
                                    }
                                    echo $overdue;
                                ?>
                            </h3>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filters -->
            <div class="bg-white rounded-xl shadow-sm p-5 mb-6 border border-gray-200">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                        <select id="filter-status" class="w-full bg-white border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent">
                            <option value="">All Status</option>
                            <option value="pending">Pending</option>
                            <option value="partial">Partial</option>
                            <option value="paid">Paid</option>
                            <option value="overdue">Overdue</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Date Range</label>
                        <select id="filter-date" class="w-full bg-white border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent">
                            <option value="">All Time</option>
                            <option value="today">Today</option>
                            <option value="week">This Week</option>
                            <option value="month">This Month</option>
                            <option value="quarter">This Quarter</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Client</label>
                        <input type="text" id="filter-client" placeholder="Search client..." class="w-full bg-white border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Invoice No.</label>
                        <input type="text" id="filter-invoice-no" placeholder="Invoice number..." class="w-full bg-white border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent">
                    </div>
                </div>
            </div>

            <!-- Invoices List -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="p-5 border-b border-gray-200 bg-gray-50">
                    <h3 class="text-lg font-semibold text-gray-800">All Invoices</h3>
                </div>

                <div id="invoices-container" class="p-4">
                    <?php if(empty($invoices)): ?>
                        <div class="text-center py-12">
                            <div class="w-24 h-24 mx-auto mb-6 bg-gray-100 rounded-full flex items-center justify-center">
                                <i class="fas fa-file-invoice text-gray-400 text-3xl"></i>
                            </div>
                            <h4 class="text-xl font-semibold text-gray-600 mb-2">No invoices found</h4>
                            <p class="text-gray-500 mb-6 max-w-md mx-auto">Start by creating your first invoice for visa applications or services.</p>
                            <a href="create-invoice.php" class="bg-gradient-to-r from-green-600 to-emerald-700 hover:from-green-700 hover:to-emerald-800 text-white font-medium py-2.5 px-6 rounded-lg transition duration-300 inline-flex items-center">
                                <i class="fas fa-plus mr-2"></i> Create First Invoice
                            </a>
                        </div>
                    <?php else: ?>
                        <div class="space-y-4">
                            <?php foreach($invoices as $invoice): 
                                $createdDate = new DateTime($invoice['created_at']);
                                $dueDate = new DateTime($invoice['due_date']);
                                $now = new DateTime();
                                $isOverdue = $invoice['status'] == 'overdue';
                            ?>
                                <div class="invoice-card bg-white border border-gray-200 rounded-lg p-5 hover:border-green-300 fade-in">
                                    <div class="flex flex-col md:flex-row justify-between gap-4">
                                        <!-- Left Column -->
                                        <div class="flex-1">
                                            <div class="flex flex-col md:flex-row md:items-start justify-between mb-4">
                                                <div class="mb-3 md:mb-0">
                                                    <div class="flex items-center mb-3">
                                                        <div class="w-10 h-10 bg-gradient-to-r from-green-100 to-green-200 rounded-lg flex items-center justify-center mr-3">
                                                            <i class="fas fa-file-invoice text-green-600"></i>
                                                        </div>
                                                        <div>
                                                            <h3 class="font-bold text-gray-800 text-lg">
                                                                <?php echo htmlspecialchars($invoice['client_name']); ?>
                                                            </h3>
                                                            <div class="flex items-center mt-1 space-x-4">
                                                                <span class="text-gray-600 text-sm">
                                                                    <i class="far fa-calendar mr-1"></i> 
                                                                    <?php echo $createdDate->format('M d, Y'); ?>
                                                                </span>
                                                                <span class="text-gray-600 text-sm <?php echo $isOverdue ? 'text-red-600 font-medium' : ''; ?>">
                                                                    <i class="far fa-clock mr-1"></i> 
                                                                    Due: <?php echo $dueDate->format('M d, Y'); ?>
                                                                </span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    
                                                    <div class="flex flex-wrap items-center gap-2 text-sm text-gray-600">
                                                        <span class="flex items-center bg-gray-100 px-3 py-1 rounded-full">
                                                            <i class="fas fa-hashtag mr-2"></i> <?php echo $invoice['invoice_no']; ?>
                                                        </span>
                                                        <span class="invoice-type-badge type-service">
                                                            Visa Service
                                                        </span>
                                                        <?php if($invoice['phone']): ?>
                                                            <span class="flex items-center bg-gray-100 px-3 py-1 rounded-full">
                                                                <i class="fas fa-phone mr-2"></i> <?php echo $invoice['phone']; ?>
                                                            </span>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                                
                                                <div class="flex flex-col items-end">
                                                    <div class="amount-badge bg-gradient-to-r from-blue-500 to-blue-600 text-white mb-2">
                                                        BDT <?php echo number_format($invoice['total_amount'], 2); ?>
                                                    </div>
                                                    <?php 
                                                        $statusClass = 'status-' . $invoice['status'];
                                                        $statusText = ucfirst($invoice['status']);
                                                    ?>
                                                    <span class="status-badge <?php echo $statusClass; ?>"><?php echo $statusText; ?></span>
                                                </div>
                                            </div>
                                            
                                            <!-- Payment Summary -->
                                            <div class="grid grid-cols-1 md:grid-cols-3 gap-3 mb-4">
                                                <div class="bg-green-50 p-3 rounded-lg border border-green-100">
                                                    <div class="text-sm text-green-700 mb-1">Total Amount</div>
                                                    <div class="text-base font-bold text-green-800">৳ <?php echo number_format($invoice['total_amount'], 2); ?></div>
                                                </div>
                                                <div class="bg-blue-50 p-3 rounded-lg border border-blue-100">
                                                    <div class="text-sm text-blue-700 mb-1">Paid Amount</div>
                                                    <div class="text-base font-bold text-blue-800">৳ <?php echo number_format($invoice['paid_amount'], 2); ?></div>
                                                </div>
                                                <div class="bg-red-50 p-3 rounded-lg border border-red-100">
                                                    <div class="text-sm text-red-700 mb-1">Due Amount</div>
                                                    <div class="text-base font-bold text-red-800">৳ <?php echo number_format($invoice['due_amount'], 2); ?></div>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <!-- Action Buttons -->
                                        <div class="md:w-48 flex md:flex-col gap-2">
                                            <button class="download-btn bg-gradient-to-r from-green-500 to-emerald-600 hover:from-green-600 hover:to-emerald-700 text-white font-medium py-2.5 px-4 rounded-lg transition duration-300 flex items-center justify-center action-btn w-full"
                                                    onclick="downloadInvoice('<?php echo $invoice['id']; ?>')">
                                                <i class="fas fa-download mr-2"></i>
                                                <span>Download</span>
                                            </button>
                                            <button class="edit-btn bg-blue-50 hover:bg-blue-100 text-blue-700 border border-blue-200 font-medium py-2.5 px-4 rounded-lg transition duration-300 flex items-center justify-center action-btn w-full"
                                                    onclick="editInvoice('<?php echo $invoice['id']; ?>')">
                                                <i class="fas fa-pencil mr-2"></i>
                                                <span>Edit</span>
                                            </button>
                                            <button class="send-btn bg-purple-50 hover:bg-purple-100 text-purple-700 border border-purple-200 font-medium py-2.5 px-4 rounded-lg transition duration-300 flex items-center justify-center action-btn w-full"
                                                    onclick="sendInvoiceOptions('<?php echo $invoice['id']; ?>', '<?php echo addslashes($invoice['client_email']); ?>', '<?php echo addslashes($invoice['phone']); ?>')">
                                                <i class="fas fa-paper-plane mr-2"></i>
                                                <span>Send</span>
                                            </button>
                                            <?php if($invoice['status'] !== 'paid'): ?>
                                                <button class="mark-paid-btn bg-green-50 hover:bg-green-100 text-green-700 border border-green-200 font-medium py-2.5 px-4 rounded-lg transition duration-300 flex items-center justify-center action-btn w-full"
                                                        onclick="markAsPaid('<?php echo $invoice['id']; ?>')">
                                                    <i class="fas fa-check-circle mr-2"></i>
                                                    <span>Mark Paid</span>
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>

    <!-- Send Invoice Modal -->
    <div id="sendModal" class="modal-overlay">
        <div class="modal-content">
            <div class="p-6 border-b border-gray-200">
                <h3 class="text-xl font-bold text-gray-800">Send Invoice</h3>
            </div>
            <div class="p-6" id="sendModalContent">
                <!-- Content loaded dynamically -->
            </div>
            <div class="p-6 border-t border-gray-200 bg-gray-50 flex justify-end">
                <button onclick="closeSendModal()" class="bg-gray-200 hover:bg-gray-300 text-gray-800 font-medium py-2.5 px-6 rounded-lg transition duration-300">
                    Cancel
                </button>
            </div>
        </div>
    </div>

    <?php include '../elements/floating-menus.php'; ?>

    <script src="../assets/js/script.js"></script>
    <script>
        let invoices = <?php echo json_encode($invoices); ?>;

        // Setup event listeners
        document.addEventListener('DOMContentLoaded', function() {
            // Filter listeners
            ['filter-status', 'filter-date', 'filter-client', 'filter-invoice-no'].forEach(id => {
                document.getElementById(id).addEventListener('change', filterInvoices);
                document.getElementById(id).addEventListener('input', filterInvoices);
            });

            // Refresh button
            document.getElementById('refresh-btn').addEventListener('click', function() {
                location.reload();
            });
        });

        // Filter invoices
        function filterInvoices() {
            const statusFilter = document.getElementById('filter-status').value;
            const dateFilter = document.getElementById('filter-date').value;
            const clientFilter = document.getElementById('filter-client').value.toLowerCase();
            const invoiceNoFilter = document.getElementById('filter-invoice-no').value.toLowerCase();

            let filteredInvoices = invoices;

            // Status filter
            if (statusFilter) {
                filteredInvoices = filteredInvoices.filter(inv => inv.status === statusFilter);
            }

            // Date filter
            if (dateFilter) {
                const now = new Date();
                const startDate = new Date();

                switch (dateFilter) {
                    case 'today':
                        startDate.setHours(0, 0, 0, 0);
                        break;
                    case 'week':
                        startDate.setDate(now.getDate() - 7);
                        break;
                    case 'month':
                        startDate.setMonth(now.getMonth() - 1);
                        break;
                    case 'quarter':
                        startDate.setMonth(now.getMonth() - 3);
                        break;
                }

                filteredInvoices = filteredInvoices.filter(inv => {
                    const invDate = new Date(inv.created_at);
                    return invDate >= startDate;
                });
            }

            // Client filter
            if (clientFilter) {
                filteredInvoices = filteredInvoices.filter(inv => 
                    inv.client_name.toLowerCase().includes(clientFilter)
                );
            }

            // Invoice number filter
            if (invoiceNoFilter) {
                filteredInvoices = filteredInvoices.filter(inv => 
                    inv.invoice_no.toLowerCase().includes(invoiceNoFilter)
                );
            }

            renderFilteredInvoices(filteredInvoices);
        }

        // Render filtered invoices
        function renderFilteredInvoices(filteredInvoices) {
            const container = document.getElementById('invoices-container');
            
            if (filteredInvoices.length === 0) {
                container.innerHTML = `
                    <div class="text-center py-12">
                        <div class="w-24 h-24 mx-auto mb-6 bg-gray-100 rounded-full flex items-center justify-center">
                            <i class="fas fa-search text-gray-400 text-3xl"></i>
                        </div>
                        <h4 class="text-xl font-semibold text-gray-600 mb-2">No invoices found</h4>
                        <p class="text-gray-500">Try adjusting your filters or search terms.</p>
                    </div>
                `;
                return;
            }

            let html = '<div class="space-y-4">';
            
            filteredInvoices.forEach(invoice => {
                const createdDate = new Date(invoice.created_at);
                const dueDate = new Date(invoice.due_date);
                const now = new Date();
                const isOverdue = invoice.status === 'overdue';
                
                html += `
                    <div class="invoice-card bg-white border border-gray-200 rounded-lg p-5 hover:border-green-300 fade-in">
                        <div class="flex flex-col md:flex-row justify-between gap-4">
                            <!-- Left Column -->
                            <div class="flex-1">
                                <div class="flex flex-col md:flex-row md:items-start justify-between mb-4">
                                    <div class="mb-3 md:mb-0">
                                        <div class="flex items-center mb-3">
                                            <div class="w-10 h-10 bg-gradient-to-r from-green-100 to-green-200 rounded-lg flex items-center justify-center mr-3">
                                                <i class="fas fa-file-invoice text-green-600"></i>
                                            </div>
                                            <div>
                                                <h3 class="font-bold text-gray-800 text-lg">
                                                    ${invoice.client_name}
                                                </h3>
                                                <div class="flex items-center mt-1 space-x-4">
                                                    <span class="text-gray-600 text-sm">
                                                        <i class="far fa-calendar mr-1"></i> 
                                                        ${createdDate.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })}
                                                    </span>
                                                    <span class="text-gray-600 text-sm ${isOverdue ? 'text-red-600 font-medium' : ''}">
                                                        <i class="far fa-clock mr-1"></i> 
                                                        Due: ${dueDate.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })}
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="flex flex-wrap items-center gap-2 text-sm text-gray-600">
                                            <span class="flex items-center bg-gray-100 px-3 py-1 rounded-full">
                                                <i class="fas fa-hashtag mr-2"></i> ${invoice.invoice_no}
                                            </span>
                                            <span class="invoice-type-badge type-service">
                                                Visa Service
                                            </span>
                                            ${invoice.phone ? `
                                                <span class="flex items-center bg-gray-100 px-3 py-1 rounded-full">
                                                    <i class="fas fa-phone mr-2"></i> ${invoice.phone}
                                                </span>
                                            ` : ''}
                                        </div>
                                    </div>
                                    
                                    <div class="flex flex-col items-end">
                                        <div class="amount-badge bg-gradient-to-r from-blue-500 to-blue-600 text-white mb-2">
                                            BDT ${invoice.total_amount.toFixed(2)}
                                        </div>
                                        <span class="status-badge status-${invoice.status}">${invoice.status.charAt(0).toUpperCase() + invoice.status.slice(1)}</span>
                                    </div>
                                </div>
                                
                                <!-- Payment Summary -->
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-3 mb-4">
                                    <div class="bg-green-50 p-3 rounded-lg border border-green-100">
                                        <div class="text-sm text-green-700 mb-1">Total Amount</div>
                                        <div class="text-base font-bold text-green-800">৳ ${invoice.total_amount.toFixed(2)}</div>
                                    </div>
                                    <div class="bg-blue-50 p-3 rounded-lg border border-blue-100">
                                        <div class="text-sm text-blue-700 mb-1">Paid Amount</div>
                                        <div class="text-base font-bold text-blue-800">৳ ${invoice.paid_amount.toFixed(2)}</div>
                                    </div>
                                    <div class="bg-red-50 p-3 rounded-lg border border-red-100">
                                        <div class="text-sm text-red-700 mb-1">Due Amount</div>
                                        <div class="text-base font-bold text-red-800">৳ ${invoice.due_amount.toFixed(2)}</div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Action Buttons -->
                            <div class="md:w-48 flex md:flex-col gap-2">
                                <button class="download-btn bg-gradient-to-r from-green-500 to-emerald-600 hover:from-green-600 hover:to-emerald-700 text-white font-medium py-2.5 px-4 rounded-lg transition duration-300 flex items-center justify-center action-btn w-full"
                                        onclick="downloadInvoice('${invoice.id}')">
                                    <i class="fas fa-download mr-2"></i>
                                    <span>Download</span>
                                </button>
                                <button class="edit-btn bg-blue-50 hover:bg-blue-100 text-blue-700 border border-blue-200 font-medium py-2.5 px-4 rounded-lg transition duration-300 flex items-center justify-center action-btn w-full"
                                        onclick="editInvoice('${invoice.id}')">
                                    <i class="fas fa-pencil mr-2"></i>
                                    <span>Edit</span>
                                </button>
                                <button class="send-btn bg-purple-50 hover:bg-purple-100 text-purple-700 border border-purple-200 font-medium py-2.5 px-4 rounded-lg transition duration-300 flex items-center justify-center action-btn w-full"
                                        onclick="sendInvoiceOptions('${invoice.id}', '${invoice.client_email}', '${invoice.phone}')">
                                    <i class="fas fa-paper-plane mr-2"></i>
                                    <span>Send</span>
                                </button>
                                ${invoice.status !== 'paid' ? `
                                    <button class="mark-paid-btn bg-green-50 hover:bg-green-100 text-green-700 border border-green-200 font-medium py-2.5 px-4 rounded-lg transition duration-300 flex items-center justify-center action-btn w-full"
                                            onclick="markAsPaid('${invoice.id}')">
                                        <i class="fas fa-check-circle mr-2"></i>
                                        <span>Mark Paid</span>
                                    </button>
                                ` : ''}
                            </div>
                        </div>
                    </div>
                `;
            });
            
            html += '</div>';
            container.innerHTML = html;
        }

        // Download invoice
        function downloadInvoice(invoiceId) {
            window.open(`print-invoice.php?id=${invoiceId}`, '_blank');
        }

        // Edit invoice
        function editInvoice(invoiceId) {
            window.open(`edit-invoice.php?id=${invoiceId}`, '_blank');
        }

        // Send invoice options
        function sendInvoiceOptions(invoiceId, email, phone) {
            const modal = document.getElementById('sendModal');
            const content = document.getElementById('sendModalContent');
            
            let html = `
                <div class="space-y-4">
                    ${email ? `
                        <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg border border-gray-200">
                            <div class="flex items-center">
                                <div class="w-10 h-10 bg-gradient-to-r from-purple-100 to-purple-200 rounded-lg flex items-center justify-center mr-4">
                                    <i class="fas fa-envelope text-purple-600"></i>
                                </div>
                                <div>
                                    <div class="font-medium text-gray-800">Email</div>
                                    <div class="text-sm text-gray-600">${email}</div>
                                </div>
                            </div>
                            <button onclick="sendEmail('${invoiceId}', '${email}')" 
                                    class="bg-purple-600 hover:bg-purple-700 text-white py-2 px-4 rounded-lg transition duration-300">
                                Send
                            </button>
                        </div>
                    ` : ''}
                    
                    ${phone ? `
                        <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg border border-gray-200">
                            <div class="flex items-center">
                                <div class="w-10 h-10 bg-gradient-to-r from-green-100 to-green-200 rounded-lg flex items-center justify-center mr-4">
                                    <i class="fab fa-whatsapp text-green-600"></i>
                                </div>
                                <div>
                                    <div class="font-medium text-gray-800">WhatsApp</div>
                                    <div class="text-sm text-gray-600">${phone}</div>
                                </div>
                            </div>
                            <button onclick="sendWhatsApp('${invoiceId}', '${phone}')" 
                                    class="bg-green-600 hover:bg-green-700 text-white py-2 px-4 rounded-lg transition duration-300">
                                Send
                            </button>
                        </div>
                    ` : ''}
                    
                    ${!email && !phone ? `
                        <div class="text-center py-8">
                            <i class="fas fa-exclamation-circle text-gray-400 text-4xl mb-4"></i>
                            <p class="text-gray-600">No contact information available for this client.</p>
                        </div>
                    ` : ''}
                </div>
            `;
            
            content.innerHTML = html;
            modal.classList.add('active');
        }

        // Close send modal
        function closeSendModal() {
            document.getElementById('sendModal').classList.remove('active');
        }

        // Send email
        function sendEmail(invoiceId, email) {
            if (confirm(`Send invoice to ${email}?`)) {
                fetch('send_invoice.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        invoice_id: invoiceId,
                        email: email,
                        method: 'email'
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Invoice sent via email successfully!');
                        closeSendModal();
                    } else {
                        alert('Error sending invoice: ' + data.message);
                    }
                })
                .catch(error => {
                    alert('Error sending invoice: ' + error.message);
                });
            }
        }

        // Send WhatsApp
        function sendWhatsApp(invoiceId, phone) {
            const invoice = invoices.find(inv => inv.id == invoiceId);
            if (!invoice) {
                alert('Invoice not found!');
                return;
            }

            const cleanPhone = phone.replace(/[\s\+]/g, '');
            const message = `Hello! Here is your invoice ${invoice.invoice_no}.\n` +
                `Amount: ${invoice.currency || 'BDT'} ${invoice.total_amount.toFixed(2)}\n` +
                `You can download it here: ${window.location.origin}/print-invoice.php?id=${invoiceId}\n` +
                `Thank you!`;

            const encodedMessage = encodeURIComponent(message);
            const whatsappUrl = `https://wa.me/${cleanPhone}?text=${encodedMessage}`;

            if (confirm(`Send invoice via WhatsApp to ${phone}?`)) {
                window.open(whatsappUrl, '_blank');
                closeSendModal();
            }
        }

        // Mark as paid
        function markAsPaid(invoiceId) {
            if (confirm('Mark this invoice as paid?')) {
                fetch('update-invoice-status.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        id: invoiceId,
                        status: 'paid',
                        paid_amount: 'full'
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Invoice marked as paid!');
                        location.reload();
                    } else {
                        alert('Error: ' + data.message);
                    }
                })
                .catch(error => {
                    alert('Error: ' + error.message);
                });
            }
        }
    </script>
</body>
</html>