<?php
include_once('./authenticate.php');
// Get IP path
$ip_port = @file_get_contents('../ippath.txt');
if (empty($ip_port)) {
    $ip_port = "http://103.104.219.3:898";
}
$base_ip_path = trim($ip_port, "/");

$allInvoice = $ip_port . "api/invoices/all-invoices.php";
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

        /* Skeleton Loader Styles */
        .skeleton-loader {
            background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
            background-size: 200% 100%;
            animation: loading 1.5s infinite;
            border-radius: 0.5rem;
        }

        @keyframes loading {
            0% { background-position: 200% 0; }
            100% { background-position: -200% 0; }
        }

        .skeleton-card {
            height: 180px;
            margin-bottom: 1rem;
        }

        .skeleton-text {
            height: 1rem;
            margin-bottom: 0.75rem;
            border-radius: 0.25rem;
        }

        .skeleton-button {
            height: 2.5rem;
            width: 100%;
            border-radius: 0.5rem;
        }

        /* Chart Container */
        .chart-container {
            position: relative;
            height: 300px;
            width: 100%;
        }

        /* Infinite Scroll Loader */
        .infinite-loader {
            text-align: center;
            padding: 2rem;
            display: none;
        }

        .infinite-loader.active {
            display: block;
        }

        .loader-spinner {
            display: inline-block;
            width: 40px;
            height: 40px;
            border: 3px solid #e5e7eb;
            border-radius: 50%;
            border-top-color: #10b981;
            animation: spin 1s ease-in-out infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 4rem 1rem;
        }

        .empty-state-icon {
            font-size: 4rem;
            color: #d1d5db;
            margin-bottom: 1rem;
        }
        
        .relative.h-64 canvas {
            display: block;
            width: 100%;
            height: 100%;
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

            <!-- Charts Section -->
            <div id="chartsSection" class="mb-6">
                <!-- Charts will be loaded here -->
            </div>

            <!-- Stats Cards -->
            <div id="statsCards" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
                <!-- Stats will be loaded here -->
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
                    <div class="text-sm text-gray-600 mt-1">
                        Showing <span id="showing-count">0</span> of <span id="total-count">0</span> invoices
                    </div>
                </div>

                <div id="invoices-container" class="p-4 min-h-[400px]">
                    <!-- Skeleton Loaders -->
                    <div id="skeleton-loaders" class="space-y-4">
                        <?php for($i = 0; $i < 5; $i++): ?>
                        <div class="skeleton-card skeleton-loader"></div>
                        <?php endfor; ?>
                    </div>

                    <!-- Empty State -->
                    <div id="empty-state" class="empty-state" style="display: none;">
                        <div class="empty-state-icon">
                            <i class="fas fa-file-invoice"></i>
                        </div>
                        <h4 class="text-xl font-semibold text-gray-600 mb-2">No invoices found</h4>
                        <p class="text-gray-500 mb-6 max-w-md mx-auto">Start by creating your first invoice for visa applications or services.</p>
                        <a href="create-invoice.php" class="bg-gradient-to-r from-green-600 to-emerald-700 hover:from-green-700 hover:to-emerald-800 text-white font-medium py-2.5 px-6 rounded-lg transition duration-300 inline-flex items-center">
                            <i class="fas fa-plus mr-2"></i> Create First Invoice
                        </a>
                    </div>

                    <!-- Invoices will be loaded here -->
                </div>

                <!-- Infinite Scroll Loader -->
                <div id="infinite-loader" class="infinite-loader">
                    <div class="loader-spinner"></div>
                    <p class="text-gray-500 mt-2">Loading more invoices...</p>
                </div>
            </div>

            <!-- Pagination (Alternative to Infinite Scroll) -->
            <div id="pagination" class="mt-6 hidden">
                <div class="flex items-center justify-between">
                    <div class="text-sm text-gray-600">
                        Page <span id="current-page">1</span> of <span id="total-pages">1</span>
                    </div>
                    <div class="flex gap-2">
                        <button id="prev-page" class="px-4 py-2 bg-white border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed">
                            Previous
                        </button>
                        <div id="page-numbers" class="flex gap-1"></div>
                        <button id="next-page" class="px-4 py-2 bg-white border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed">
                            Next
                        </button>
                    </div>
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

    <script src="../assets/js/script.js?time=<?php echo time(); ?>"></script>
    <script>
        // Configuration
        const API_URL = "<?php echo $allInvoice; ?>";
        const ITEMS_PER_PAGE = 10;
        const INFINITE_SCROLL_THRESHOLD = 100; // pixels from bottom
        const DEBOUNCE_DELAY = 300; // milliseconds

        // State management
        let invoices = [];
        let filteredInvoices = [];
        let currentPage = 1;
        let isLoading = false;
        let hasMore = true;
        let searchTimeout;
        let isInfiniteScroll = true; // Toggle between pagination and infinite scroll

        // DOM Elements
        const elements = {
            container: document.getElementById('invoices-container'),
            skeleton: document.getElementById('skeleton-loaders'),
            emptyState: document.getElementById('empty-state'),
            infiniteLoader: document.getElementById('infinite-loader'),
            pagination: document.getElementById('pagination'),
            showingCount: document.getElementById('showing-count'),
            totalCount: document.getElementById('total-count'),
            chartsSection: document.getElementById('chartsSection'),
            statsCards: document.getElementById('statsCards'),
            filters: {
                status: document.getElementById('filter-status'),
                date: document.getElementById('filter-date'),
                client: document.getElementById('filter-client'),
                invoiceNo: document.getElementById('filter-invoice-no')
            }
        };

        // Initialize application
        document.addEventListener('DOMContentLoaded', async function() {
            initializeEventListeners();
            await loadData();
            initializeInfiniteScroll();
        });

        // Setup event listeners
        function initializeEventListeners() {
            // Filter listeners with debouncing
            elements.filters.status.addEventListener('change', () => debouncedFilter());
            elements.filters.date.addEventListener('change', () => debouncedFilter());
            elements.filters.client.addEventListener('input', () => debouncedFilter());
            elements.filters.invoiceNo.addEventListener('input', () => debouncedFilter());

            // Refresh button
            document.getElementById('refresh-btn').addEventListener('click', async () => {
                await loadData();
            });

            // Toggle between infinite scroll and pagination (optional)
            document.addEventListener('keydown', (e) => {
                if (e.ctrlKey && e.key === 'p') {
                    isInfiniteScroll = !isInfiniteScroll;
                    alert(`Switched to ${isInfiniteScroll ? 'Infinite Scroll' : 'Pagination'}`);
                    renderInvoices();
                }
            });
        }

        // Debounce function for search/filter
        function debouncedFilter() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                currentPage = 1;
                filterInvoices();
                renderInvoices();
            }, DEBOUNCE_DELAY);
        }

        // Load data from API
        async function loadData() {
            try {
                showSkeleton();
                const response = await fetch(API_URL);
                const data = await response.json();
                
                invoices = data.invoices || [];
                filteredInvoices = [...invoices];
                
                updateStats();
                renderCharts();
                renderInvoices();
                hideSkeleton();
            } catch (error) {
                console.error('Error loading invoices:', error);
                showError('Failed to load invoices. Please try again.');
            }
        }

        // Show skeleton loaders
        function showSkeleton() {
            elements.skeleton.style.display = 'block';
            elements.container.innerHTML = '';
            elements.container.appendChild(elements.skeleton);
            elements.emptyState.style.display = 'none';
        }

        // Hide skeleton loaders
        function hideSkeleton() {
            elements.skeleton.style.display = 'none';
        }

        // Filter invoices based on criteria
        function filterInvoices() {
            const statusFilter = elements.filters.status.value;
            const dateFilter = elements.filters.date.value;
            const clientFilter = elements.filters.client.value.toLowerCase();
            const invoiceNoFilter = elements.filters.invoiceNo.value.toLowerCase();

            filteredInvoices = invoices.filter(invoice => {
                // Status filter
                if (statusFilter && invoice.status !== statusFilter) return false;

                // Date filter
                if (dateFilter) {
                    const invoiceDate = new Date(invoice.created_at);
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

                    if (invoiceDate < startDate) return false;
                }

                // Client filter
                if (clientFilter && !invoice.client_name.toLowerCase().includes(clientFilter)) {
                    return false;
                }

                // Invoice number filter
                if (invoiceNoFilter && !invoice.invoice_no.toLowerCase().includes(invoiceNoFilter)) {
                    return false;
                }

                return true;
            });

            updateStats();
        }

        // Update statistics
        function updateStats() {
            const totalInvoices = filteredInvoices.length;
            const totalRevenue = filteredInvoices.reduce((sum, inv) => sum + inv.total_amount, 0);
            
            // Calculate PENDING AMOUNT (sum of due_amount for pending and partial invoices)
            const pendingAmount = filteredInvoices
                .filter(inv => ['pending', 'partial'].includes(inv.status))
                .reduce((sum, inv) => sum + inv.due_amount, 0);
            
            // Calculate OVERDUE AMOUNT (sum of due_amount for overdue invoices)
            const overdueAmount = filteredInvoices
                .filter(inv => inv.status === 'overdue')
                .reduce((sum, inv) => sum + inv.due_amount, 0);
            
            // Calculate PAID AMOUNT
            const paidAmount = filteredInvoices.reduce((sum, inv) => sum + inv.paid_amount, 0);
            
            // Format currency function
            const formatCurrency = (amount) => {
                return `৳${amount.toLocaleString('en-IN', { 
                    minimumFractionDigits: 2, 
                    maximumFractionDigits: 2 
                })}`;
            };
        
            elements.statsCards.innerHTML = `
                <div class="bg-white rounded-xl shadow-sm p-5 border border-gray-200 hover:shadow-md transition-shadow">
                    <div class="flex items-center">
                        <div class="w-12 h-12 bg-gradient-to-r from-blue-100 to-blue-200 rounded-lg flex items-center justify-center mr-4">
                            <i class="fas fa-file-invoice text-blue-600 text-xl"></i>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500 font-medium">Total Invoices</p>
                            <h3 class="text-2xl font-bold text-gray-800">${totalInvoices}</h3>
                        </div>
                    </div>
                    <div class="mt-3 text-sm text-gray-600">
                        <i class="fas fa-money-bill-wave mr-1"></i>
                        Total Value: ${formatCurrency(totalRevenue)}
                    </div>
                </div>
        
                <div class="bg-white rounded-xl shadow-sm p-5 border border-gray-200 hover:shadow-md transition-shadow">
                    <div class="flex items-center">
                        <div class="w-12 h-12 bg-gradient-to-r from-green-100 to-green-200 rounded-lg flex items-center justify-center mr-4">
                            <i class="fas fa-money-bill-wave text-green-600 text-xl"></i>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500 font-medium">Total Revenue</p>
                            <h3 class="text-2xl font-bold text-gray-800">
                                ${formatCurrency(totalRevenue)}
                            </h3>
                        </div>
                    </div>
                    <div class="mt-3 text-sm text-gray-600">
                        <i class="fas fa-check-circle mr-1"></i>
                        Paid: ${formatCurrency(paidAmount)}
                    </div>
                </div>
        
                <div class="bg-white rounded-xl shadow-sm p-5 border border-gray-200 hover:shadow-md transition-shadow">
                    <div class="flex items-center">
                        <div class="w-12 h-12 bg-gradient-to-r from-amber-100 to-amber-200 rounded-lg flex items-center justify-center mr-4">
                            <i class="fas fa-clock text-amber-600 text-xl"></i>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500 font-medium">Pending Amount</p>
                            <h3 class="text-2xl font-bold text-gray-800">
                                ${formatCurrency(pendingAmount)}
                            </h3>
                        </div>
                    </div>
                    <div class="mt-3">
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-600">Pending Invoices:</span>
                            <span class="font-medium">${filteredInvoices.filter(inv => inv.status === 'pending').length}</span>
                        </div>
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-600">Partial Invoices:</span>
                            <span class="font-medium">${filteredInvoices.filter(inv => inv.status === 'partial').length}</span>
                        </div>
                    </div>
                </div>
        
                <div class="bg-white rounded-xl shadow-sm p-5 border border-gray-200 hover:shadow-md transition-shadow">
                    <div class="flex items-center">
                        <div class="w-12 h-12 bg-gradient-to-r from-red-100 to-red-200 rounded-lg flex items-center justify-center mr-4">
                            <i class="fas fa-exclamation-triangle text-red-600 text-xl"></i>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500 font-medium">Overdue Amount</p>
                            <h3 class="text-2xl font-bold text-gray-800">
                                ${formatCurrency(overdueAmount)}
                            </h3>
                        </div>
                    </div>
                    <div class="mt-3">
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-600">Overdue Invoices:</span>
                            <span class="font-medium">${filteredInvoices.filter(inv => inv.status === 'overdue').length}</span>
                        </div>
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-600">Due Date Passed:</span>
                            <span class="font-medium">${filteredInvoices.filter(inv => {
                                const dueDate = new Date(inv.due_date);
                                const today = new Date();
                                return dueDate < today && inv.status !== 'paid';
                            }).length}</span>
                        </div>
                    </div>
                </div>
            `;
        
            elements.showingCount.textContent = Math.min(totalInvoices, currentPage * ITEMS_PER_PAGE);
            elements.totalCount.textContent = totalInvoices;
        }

        // Render charts
        // Improved renderCharts function with actual visual charts
        function renderCharts() {
            if (invoices.length === 0) return;
        
            // Calculate data properly
            const statusData = invoices.reduce((acc, invoice) => {
                acc[invoice.status] = (acc[invoice.status] || 0) + 1;
                return acc;
            }, {});
        
            const monthlyData = invoices.reduce((acc, invoice) => {
                const date = new Date(invoice.created_at);
                const monthYear = `${date.getFullYear()}-${(date.getMonth() + 1).toString().padStart(2, '0')}`;
                if (!acc[monthYear]) {
                    acc[monthYear] = { revenue: 0, count: 0 };
                }
                acc[monthYear].revenue += invoice.total_amount;
                acc[monthYear].count += 1;
                return acc;
            }, {});
        
            // Get last 6 months correctly
            const last6Months = [];
            const now = new Date();
            for (let i = 5; i >= 0; i--) {
                const date = new Date(now.getFullYear(), now.getMonth() - i, 1);
                last6Months.push(`${date.getFullYear()}-${(date.getMonth() + 1).toString().padStart(2, '0')}`);
            }
        
            // Create actual chart containers
            elements.chartsSection.innerHTML = `
                <div class="bg-white rounded-xl shadow-sm p-5 border border-gray-200 mb-4">
                    <h3 class="text-lg font-semibold text-gray-800 mb-6">Invoice Analytics</h3>
                    
                    <!-- Stats summary row -->
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
                        <div class="bg-gradient-to-r from-amber-50 to-amber-100 p-4 rounded-lg border border-amber-200">
                            <div class="flex items-center">
                                <div class="w-10 h-10 bg-amber-100 rounded-full flex items-center justify-center mr-3">
                                    <i class="fas fa-clock text-amber-600"></i>
                                </div>
                                <div>
                                    <p class="text-sm text-amber-800">Pending</p>
                                    <p class="text-xl font-bold text-amber-900">${statusData.pending || 0}</p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="bg-gradient-to-r from-red-50 to-red-100 p-4 rounded-lg border border-red-200">
                            <div class="flex items-center">
                                <div class="w-10 h-10 bg-red-100 rounded-full flex items-center justify-center mr-3">
                                    <i class="fas fa-exclamation-triangle text-red-600"></i>
                                </div>
                                <div>
                                    <p class="text-sm text-red-800">Overdue</p>
                                    <p class="text-xl font-bold text-red-900">${statusData.overdue || 0}</p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="bg-gradient-to-r from-purple-50 to-purple-100 p-4 rounded-lg border border-purple-200">
                            <div class="flex items-center">
                                <div class="w-10 h-10 bg-purple-100 rounded-full flex items-center justify-center mr-3">
                                    <i class="fas fa-chart-pie text-purple-600"></i>
                                </div>
                                <div>
                                    <p class="text-sm text-purple-800">Partial</p>
                                    <p class="text-xl font-bold text-purple-900">${statusData.partial || 0}</p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="bg-gradient-to-r from-green-50 to-green-100 p-4 rounded-lg border border-green-200">
                            <div class="flex items-center">
                                <div class="w-10 h-10 bg-green-100 rounded-full flex items-center justify-center mr-3">
                                    <i class="fas fa-check-circle text-green-600"></i>
                                </div>
                                <div>
                                    <p class="text-sm text-green-800">Paid</p>
                                    <p class="text-xl font-bold text-green-900">${statusData.paid || 0}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Charts row -->
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        <!-- Pie Chart Container -->
                        <div class="bg-gray-50 rounded-lg p-4 border border-gray-200">
                            <div class="flex justify-between items-center mb-4">
                                <h4 class="font-medium text-gray-700">Status Distribution</h4>
                                <span class="text-sm text-gray-500">Total: ${invoices.length}</span>
                            </div>
                            <div class="relative h-64">
                                <canvas id="statusChart"></canvas>
                            </div>
                            <div class="mt-4 grid grid-cols-2 gap-2" id="statusLegend"></div>
                        </div>
                        
                        <!-- Bar Chart Container -->
                        <div class="bg-gray-50 rounded-lg p-4 border border-gray-200">
                            <div class="flex justify-between items-center mb-4">
                                <h4 class="font-medium text-gray-700">Monthly Revenue</h4>
                                <span class="text-sm text-gray-500">Last 6 months</span>
                            </div>
                            <div class="relative h-64">
                                <canvas id="revenueChart"></canvas>
                            </div>
                            <div class="mt-4 text-center text-sm text-gray-500">
                                <i class="fas fa-info-circle mr-1"></i> Hover over bars for details
                            </div>
                        </div>
                    </div>
                </div>
            `;
        
            // Create actual charts after DOM is updated
            setTimeout(() => {
                createActualStatusChart(statusData);
                createActualRevenueChart(monthlyData, last6Months);
            }, 100);
        }
        
        // Create actual pie chart
        function createActualStatusChart(statusData) {
            const canvas = document.getElementById('statusChart');
            
            console.log(canvas);
            if (!canvas) return;
            
            const ctx = canvas.getContext('2d');
            const width = canvas.width = canvas.offsetWidth;
            const height = canvas.height = canvas.offsetHeight;
            
            // Clear canvas
            ctx.clearRect(0, 0, width, height);
            
            // Colors with proper contrast
            const colors = {
                pending: '#f59e0b', // Orange
                paid: '#10b981',    // Green
                overdue: '#ef4444', // Red
                partial: '#8b5cf6'  // Purple
            };
            
            const total = Object.values(statusData).reduce((a, b) => a + b, 0);
            const centerX = width / 2;
            const centerY = height / 2;
            const radius = Math.min(width, height) / 2 - 20;
            
            let startAngle = 0;
            const entries = Object.entries(statusData);
            
            // Draw pie slices
            entries.forEach(([status, count], index) => {
                const sliceAngle = (count / total) * 2 * Math.PI;
                
                // Draw slice
                ctx.beginPath();
                ctx.moveTo(centerX, centerY);
                ctx.arc(centerX, centerY, radius, startAngle, startAngle + sliceAngle);
                ctx.closePath();
                ctx.fillStyle = colors[status];
                ctx.fill();
                
                // Add border
                ctx.strokeStyle = '#ffffff';
                ctx.lineWidth = 2;
                ctx.stroke();
                
                // Draw percentage in the slice
                const midAngle = startAngle + sliceAngle / 2;
                const textRadius = radius * 0.5;
                const textX = centerX + Math.cos(midAngle) * textRadius;
                const textY = centerY + Math.sin(midAngle) * textRadius;
                
                ctx.fillStyle = '#ffffff';
                ctx.font = 'bold 12px Arial';
                ctx.textAlign = 'center';
                ctx.textBaseline = 'middle';
                ctx.fillText(`${((count / total) * 100).toFixed(0)}%`, textX, textY);
                
                startAngle += sliceAngle;
            });
            
            // Draw center circle with total
            ctx.beginPath();
            ctx.arc(centerX, centerY, radius * 0.3, 0, Math.PI * 2);
            ctx.fillStyle = '#f8fafc';
            ctx.fill();
            
            ctx.fillStyle = '#374151';
            ctx.font = 'bold 14px Arial';
            ctx.textAlign = 'center';
            ctx.textBaseline = 'middle';
            ctx.fillText('Total', centerX, centerY - 10);
            ctx.font = 'bold 20px Arial';
            ctx.fillText(total.toString(), centerX, centerY + 10);
            
            // Update legend
            const legendContainer = document.getElementById('statusLegend');
            if (legendContainer) {
                let legendHTML = '';
                entries.forEach(([status, count]) => {
                    const percentage = ((count / total) * 100).toFixed(1);
                    legendHTML += `
                        <div class="flex items-center p-2 bg-white rounded border">
                            <div class="w-3 h-3 rounded-full mr-2" style="background-color: ${colors[status]}"></div>
                            <span class="text-sm font-medium text-gray-700 capitalize">${status}</span>
                            <span class="ml-auto text-sm text-gray-600">${count} (${percentage}%)</span>
                        </div>
                    `;
                });
                legendContainer.innerHTML = legendHTML;
            }
        }
        
        // Create actual bar chart
        function createActualRevenueChart(monthlyData, months) {
            const canvas = document.getElementById('revenueChart');
            if (!canvas) return;
            
            const ctx = canvas.getContext('2d');
            const width = canvas.width = canvas.offsetWidth;
            const height = canvas.height = canvas.offsetHeight;
            
            // Clear canvas
            ctx.clearRect(0, 0, width, height);
            
            // Setup chart area
            const padding = { top: 30, right: 20, bottom: 40, left: 50 };
            const chartWidth = width - padding.left - padding.right;
            const chartHeight = height - padding.top - padding.bottom;
            
            // Prepare data
            const data = months.map(month => monthlyData[month]?.revenue || 0);
            const maxRevenue = Math.max(...data, 1000); // Min 1000 for better scale
            
            // Format month labels
            const monthLabels = months.map(month => {
                const [year, monthNum] = month.split('-');
                const monthNames = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 
                                   'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
                return `${monthNames[parseInt(monthNum) - 1]} '${year.toString().slice(-2)}`;
            });
            
            // Draw grid
            ctx.strokeStyle = '#e5e7eb';
            ctx.lineWidth = 1;
            
            // Horizontal grid lines
            const gridLines = 5;
            for (let i = 0; i <= gridLines; i++) {
                const y = padding.top + chartHeight * (1 - i / gridLines);
                ctx.beginPath();
                ctx.moveTo(padding.left, y);
                ctx.lineTo(width - padding.right, y);
                ctx.stroke();
                
                // Y-axis labels
                ctx.fillStyle = '#6b7280';
                ctx.font = '10px Arial';
                ctx.textAlign = 'right';
                ctx.textBaseline = 'middle';
                
                const value = (maxRevenue * i / gridLines);
                let label;
                if (value >= 1000000) {
                    label = `৳${(value / 1000000).toFixed(1)}M`;
                } else if (value >= 1000) {
                    label = `৳${(value / 1000).toFixed(0)}K`;
                } else {
                    label = `৳${value.toFixed(0)}`;
                }
                
                ctx.fillText(label, padding.left - 10, y);
            }
            
            // Calculate bar positions
            const barWidth = chartWidth / months.length * 0.7;
            const barSpacing = chartWidth / months.length * 0.3;
            
            // Draw bars
            data.forEach((revenue, index) => {
                if (revenue <= 0) return;
                
                const x = padding.left + (index * (barWidth + barSpacing)) + barSpacing / 2;
                const barHeight = chartHeight * (revenue / maxRevenue);
                const y = padding.top + chartHeight - barHeight;
                
                // Create gradient for bar
                const gradient = ctx.createLinearGradient(x, y, x, y + barHeight);
                gradient.addColorStop(0, '#10b981');
                gradient.addColorStop(1, '#059669');
                
                // Draw bar
                ctx.fillStyle = gradient;
                ctx.fillRect(x, y, barWidth, barHeight);
                
                // Draw rounded top
                ctx.beginPath();
                ctx.moveTo(x, y);
                ctx.lineTo(x + barWidth, y);
                ctx.lineTo(x + barWidth, y + barHeight);
                ctx.lineTo(x, y + barHeight);
                ctx.closePath();
                ctx.fillStyle = gradient;
                ctx.fill();
                
                // Draw value on top if enough space
                if (barHeight > 20) {
                    ctx.fillStyle = '#374151';
                    ctx.font = 'bold 10px Arial';
                    ctx.textAlign = 'center';
                    ctx.textBaseline = 'bottom';
                    
                    let displayValue;
                    if (revenue >= 1000000) {
                        displayValue = `৳${(revenue / 1000000).toFixed(1)}M`;
                    } else if (revenue >= 1000) {
                        displayValue = `৳${(revenue / 1000).toFixed(0)}K`;
                    } else {
                        displayValue = `৳${revenue.toFixed(0)}`;
                    }
                    
                    ctx.fillText(displayValue, x + barWidth / 2, y - 5);
                }
            });
            
            // Draw month labels
            data.forEach((revenue, index) => {
                const x = padding.left + (index * (barWidth + barSpacing)) + barSpacing / 2 + barWidth / 2;
                const y = height - padding.bottom + 15;
                
                ctx.fillStyle = '#6b7280';
                ctx.font = '11px Arial';
                ctx.textAlign = 'center';
                ctx.textBaseline = 'top';
                ctx.fillText(monthLabels[index], x, y);
            });
            
            // Draw chart title
            ctx.fillStyle = '#374151';
            ctx.font = 'bold 12px Arial';
            ctx.textAlign = 'center';
            ctx.textBaseline = 'top';
            ctx.fillText('Revenue Overview (BDT)', width / 2, 10);
        }

        // Render invoices with proper infinite scroll
        function renderInvoices() {
            const startIndex = (currentPage - 1) * ITEMS_PER_PAGE;
            const endIndex = startIndex + ITEMS_PER_PAGE;
            const pageInvoices = filteredInvoices.slice(startIndex, endIndex);
        
            if (pageInvoices.length === 0) {
                if (currentPage === 1) { // Only show empty state on first page
                    elements.emptyState.style.display = 'block';
                    elements.container.innerHTML = '';
                    elements.container.appendChild(elements.emptyState);
                }
                return;
            }
        
            elements.emptyState.style.display = 'none';
        
            let html = '';
            
            pageInvoices.forEach(invoice => {
                const createdDate = new Date(invoice.created_at);
                const dueDate = new Date(invoice.due_date);
                const now = new Date();
                const isOverdue = invoice.status === 'overdue';
                
                html += `
                    <div class="invoice-card bg-white border border-gray-200 rounded-lg p-5 hover:border-green-300 fade-in mb-4">
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
                                                    ${escapeHtml(invoice.client_name)}
                                                    ${invoice.client_sys_id ? ` || ${escapeHtml(invoice.client_sys_id)}` : ''}
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
                                                <i class="fas fa-hashtag mr-2"></i> ${escapeHtml(invoice.invoice_no)}
                                            </span>
                                            ${invoice.phone ? `
                                                <span class="flex items-center bg-gray-100 px-3 py-1 rounded-full">
                                                    <i class="fas fa-phone mr-2"></i> ${escapeHtml(invoice.phone)}
                                                </span>
                                            ` : ''}
                                        </div>
                                    </div>
                                    
                                    <div class="flex flex-col items-end">
                                        <div class="amount-badge bg-gradient-to-r from-blue-500 to-blue-600 text-white mb-2">
                                            BDT ${invoice.total_amount.toFixed(2)}
                                        </div>
                                        <span class="status-badge status-${invoice.status}">
                                            ${invoice.status.charAt(0).toUpperCase() + invoice.status.slice(1)}
                                        </span>
                                    </div>
                                </div>
                                
                                <!-- Payment Summary -->
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-3 mb-4">
                                    <div class="bg-green-50 p-3 rounded-lg border border-green-100">
                                        <div class="text-sm text-green-700 mb-1">Total Amount</div>
                                        <div class="text-base font-bold text-green-800">
                                            ৳ ${invoice.total_amount.toFixed(2)}
                                        </div>
                                    </div>
                                    <div class="bg-blue-50 p-3 rounded-lg border border-blue-100">
                                        <div class="text-sm text-blue-700 mb-1">Paid Amount</div>
                                        <div class="text-base font-bold text-blue-800">
                                            ৳ ${invoice.paid_amount.toFixed(2)}
                                        </div>
                                    </div>
                                    <div class="bg-red-50 p-3 rounded-lg border border-red-100">
                                        <div class="text-sm text-red-700 mb-1">Due Amount</div>
                                        <div class="text-base font-bold text-red-800">
                                            ৳ ${invoice.due_amount.toFixed(2)}
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Action Buttons -->
                            <div class="md:w-48 flex md:flex-col gap-2">
                                <button class="download-btn bg-gradient-to-r from-green-500 to-emerald-600 hover:from-green-600 hover:to-emerald-700 text-white font-medium py-2.5 px-4 rounded-lg transition duration-300 flex items-center justify-center action-btn w-full"
                                        onclick="downloadInvoice('${invoice.invoice_no}')">
                                    <i class="fas fa-download mr-2"></i>
                                    <span>Download</span>
                                </button>
                                <button class="edit-btn bg-blue-50 hover:bg-blue-100 text-blue-700 border border-blue-200 font-medium py-2.5 px-4 rounded-lg transition duration-300 flex items-center justify-center action-btn w-full"
                                        onclick="editInvoice('${invoice.invoice_no}')">
                                    <i class="fas fa-pencil mr-2"></i>
                                    <span>Edit</span>
                                </button>
                                <button class="send-btn bg-purple-50 hover:bg-purple-100 text-purple-700 border border-purple-200 font-medium py-2.5 px-4 rounded-lg transition duration-300 flex items-center justify-center action-btn w-full"
                                        onclick="sendInvoiceOptions('${invoice.invoice_no}', '${escapeHtml(invoice.client_email)}', '${escapeHtml(invoice.phone)}')">
                                    <i class="fas fa-paper-plane mr-2"></i>
                                    <span>Send</span>
                                </button>
                                ${invoice.status !== 'paid' ? `
                                    <button class="mark-paid-btn bg-green-50 hover:bg-green-100 text-green-700 border border-green-200 font-medium py-2.5 px-4 rounded-lg transition duration-300 flex items-center justify-center action-btn w-full"
                                            onclick="markAsPaid('${invoice.invoice_no}')">
                                        <i class="fas fa-check-circle mr-2"></i>
                                        <span>Mark Paid</span>
                                    </button>
                                ` : ''}
                            </div>
                        </div>
                    </div>
                `;
            });
            
            // Append new invoices instead of replacing
            if (currentPage === 1) {
                elements.container.innerHTML = `<div class="space-y-4" id="invoices-list">${html}</div>`;
            } else {
                const invoicesList = document.getElementById('invoices-list');
                if (invoicesList) {
                    invoicesList.innerHTML += html;
                } else {
                    elements.container.innerHTML = `<div class="space-y-4" id="invoices-list">${html}</div>`;
                }
            }
            
            // Show/hide infinite scroll loader
            const showingCount = Math.min(filteredInvoices.length, currentPage * ITEMS_PER_PAGE);
            elements.showingCount.textContent = showingCount;
            
            if (isInfiniteScroll && showingCount < filteredInvoices.length) {
                elements.infiniteLoader.style.display = 'block';
            } else {
                elements.infiniteLoader.style.display = 'none';
            }
        }

        // Initialize infinite scroll
        function initializeInfiniteScroll() {
            if (!isInfiniteScroll) return;

            window.addEventListener('scroll', () => {
                if (isLoading || !hasMore) return;

                const scrollPosition = window.innerHeight + window.scrollY;
                const threshold = document.body.offsetHeight - INFINITE_SCROLL_THRESHOLD;

                if (scrollPosition >= threshold) {
                    loadMoreInvoices();
                }
            });
        }

        // Load more invoices for infinite scroll
        async function loadMoreInvoices() {
            const showingCount = currentPage * ITEMS_PER_PAGE;
            
            if (showingCount >= filteredInvoices.length) {
                hasMore = false;
                elements.infiniteLoader.style.display = 'none';
                return;
            }
        
            if (isLoading) return;
            
            isLoading = true;
            elements.infiniteLoader.style.display = 'block';
            elements.infiniteLoader.classList.add('active');
        
            // Add a small delay for better UX
            await new Promise(resolve => setTimeout(resolve, 300));
        
            currentPage++;
            renderInvoices();
        
            isLoading = false;
            elements.infiniteLoader.classList.remove('active');
        }   

        // Escape HTML to prevent XSS
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        // Show error message
        function showError(message) {
            elements.container.innerHTML = `
                <div class="text-center py-12">
                    <div class="w-24 h-24 mx-auto mb-6 bg-red-100 rounded-full flex items-center justify-center">
                        <i class="fas fa-exclamation-triangle text-red-600 text-3xl"></i>
                    </div>
                    <h4 class="text-xl font-semibold text-gray-600 mb-2">Error Loading Data</h4>
                    <p class="text-gray-500 mb-6 max-w-md mx-auto">${message}</p>
                    <button onclick="loadData()" class="bg-gradient-to-r from-green-600 to-emerald-700 hover:from-green-700 hover:to-emerald-800 text-white font-medium py-2.5 px-6 rounded-lg transition duration-300 inline-flex items-center">
                        <i class="fas fa-sync-alt mr-2"></i> Try Again
                    </button>
                </div>
            `;
        }

        // Existing functions (downloadInvoice, editInvoice, etc.)
        function downloadInvoice(invoiceId) {
            window.open(`print-invoice.php?id=${invoiceId}`, '_blank');
        }

        function editInvoice(invoiceId) {
            window.open(`edit-invoice.php?id=${invoiceId}`, '_blank');
        }

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

        function closeSendModal() {
            document.getElementById('sendModal').classList.remove('active');
        }

        async function sendEmail(invoiceId, email) {
            if (confirm(`Send invoice to ${email}?`)) {
                try {
                    const response = await fetch('send_invoice.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
                            invoice_id: invoiceId,
                            email: email,
                            method: 'email'
                        })
                    });
                    
                    const data = await response.json();
                    
                    if (data.success) {
                        alert('Invoice sent via email successfully!');
                        closeSendModal();
                    } else {
                        alert('Error sending invoice: ' + data.message);
                    }
                } catch (error) {
                    alert('Error sending invoice: ' + error.message);
                }
            }
        }

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

        async function markAsPaid(invoiceId) {
            if (confirm('Mark this invoice as paid?')) {
                try {
                    const response = await fetch('update-invoice-status.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
                            id: invoiceId,
                            status: 'paid',
                            paid_amount: 'full'
                        })
                    });
                    
                    const data = await response.json();
                    
                    if (data.success) {
                        alert('Invoice marked as paid!');
                        await loadData(); // Reload data
                    } else {
                        alert('Error: ' + data.message);
                    }
                } catch (error) {
                    alert('Error: ' + error.message);
                }
            }
        }

        // Handle window resize for responsive charts
        let resizeTimeout;
        window.addEventListener('resize', () => {
            clearTimeout(resizeTimeout);
            resizeTimeout = setTimeout(() => {
                if (invoices.length > 0) {
                    renderCharts();
                }
            }, 250);
        });
    </script>
</body>
</html>