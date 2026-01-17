<?php
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

    <script src="../assets/js/script.js"></script>
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
            const pendingInvoices = filteredInvoices.filter(inv => ['pending', 'partial'].includes(inv.status)).length;
            const overdueInvoices = filteredInvoices.filter(inv => inv.status === 'overdue').length;

            elements.statsCards.innerHTML = `
                <div class="bg-white rounded-xl shadow-sm p-5 border border-gray-200">
                    <div class="flex items-center">
                        <div class="w-12 h-12 bg-gradient-to-r from-blue-100 to-blue-200 rounded-lg flex items-center justify-center mr-4">
                            <i class="fas fa-file-invoice text-blue-600 text-xl"></i>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500 font-medium">Total Invoices</p>
                            <h3 class="text-2xl font-bold text-gray-800">${totalInvoices}</h3>
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
                            <h3 class="text-2xl font-bold text-gray-800">
                                ৳ ${totalRevenue.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}
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
                            <h3 class="text-2xl font-bold text-gray-800">${pendingInvoices}</h3>
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
                            <h3 class="text-2xl font-bold text-gray-800">${overdueInvoices}</h3>
                        </div>
                    </div>
                </div>
            `;

            elements.showingCount.textContent = Math.min(totalInvoices, currentPage * ITEMS_PER_PAGE);
            elements.totalCount.textContent = totalInvoices;
        }

        // Render charts
        function renderCharts() {
            if (invoices.length === 0) return;

            // Calculate data for charts
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

            const last6Months = Array.from({ length: 6 }, (_, i) => {
                const date = new Date();
                date.setMonth(date.getMonth() - i);
                return `${date.getFullYear()}-${(date.getMonth() + 1).toString().padStart(2, '0')}`;
            }).reverse();

            elements.chartsSection.innerHTML = `
                <div class="bg-white rounded-xl shadow-sm p-5 border border-gray-200 mb-4">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">Invoice Analytics</h3>
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        <div>
                            <h4 class="text-sm font-medium text-gray-700 mb-3">Status Distribution</h4>
                            <div class="chart-container">
                                <canvas id="statusChart"></canvas>
                            </div>
                        </div>
                        <div>
                            <h4 class="text-sm font-medium text-gray-700 mb-3">Monthly Revenue (Last 6 Months)</h4>
                            <div class="chart-container">
                                <canvas id="revenueChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
            `;

            // Create charts using canvas (no external libraries)
            createStatusChart(statusData);
            createRevenueChart(monthlyData, last6Months);
        }

        // Create status chart
        function createStatusChart(statusData) {
            const canvas = document.getElementById('statusChart');
            if (!canvas) return;

            const ctx = canvas.getContext('2d');
            const width = canvas.width = canvas.offsetWidth;
            const height = canvas.height = canvas.offsetHeight;
            const centerX = width / 2;
            const centerY = height / 2;
            const radius = Math.min(width, height) * 0.3;

            // Clear canvas
            ctx.clearRect(0, 0, width, height);

            // Colors for each status
            const colors = {
                pending: '#f59e0b',
                paid: '#10b981',
                overdue: '#ef4444',
                partial: '#8b5cf6'
            };

            // Calculate total and draw pie chart
            const total = Object.values(statusData).reduce((a, b) => a + b, 0);
            let startAngle = 0;

            for (const [status, count] of Object.entries(statusData)) {
                const sliceAngle = (count / total) * 2 * Math.PI;
                
                // Draw slice
                ctx.beginPath();
                ctx.moveTo(centerX, centerY);
                ctx.arc(centerX, centerY, radius, startAngle, startAngle + sliceAngle);
                ctx.closePath();
                ctx.fillStyle = colors[status] || '#9ca3af';
                ctx.fill();

                // Draw label
                const labelAngle = startAngle + sliceAngle / 2;
                const labelRadius = radius * 0.7;
                const labelX = centerX + Math.cos(labelAngle) * labelRadius;
                const labelY = centerY + Math.sin(labelAngle) * labelRadius;
                
                ctx.fillStyle = '#fff';
                ctx.font = 'bold 12px Arial';
                ctx.textAlign = 'center';
                ctx.textBaseline = 'middle';
                ctx.fillText(`${((count / total) * 100).toFixed(1)}%`, labelX, labelY);

                startAngle += sliceAngle;
            }

            // Draw legend
            const legendX = 20;
            let legendY = 20;
            const legendSize = 12;
            
            for (const [status, count] of Object.entries(statusData)) {
                ctx.fillStyle = colors[status] || '#9ca3af';
                ctx.fillRect(legendX, legendY, legendSize, legendSize);
                
                ctx.fillStyle = '#374151';
                ctx.font = '12px Arial';
                ctx.textAlign = 'left';
                ctx.textBaseline = 'middle';
                ctx.fillText(`${status.charAt(0).toUpperCase() + status.slice(1)}: ${count} (${((count / total) * 100).toFixed(1)}%)`, 
                           legendX + legendSize + 8, legendY + legendSize / 2);
                
                legendY += legendSize + 8;
            }
        }

        // Create revenue chart
        function createRevenueChart(monthlyData, months) {
            const canvas = document.getElementById('revenueChart');
            if (!canvas) return;

            const ctx = canvas.getContext('2d');
            const width = canvas.width = canvas.offsetWidth;
            const height = canvas.height = canvas.offsetHeight;
            const padding = { top: 40, right: 40, bottom: 60, left: 60 };

            // Clear canvas
            ctx.clearRect(0, 0, width, height);

            // Prepare data
            const data = months.map(month => monthlyData[month]?.revenue || 0);
            const maxRevenue = Math.max(...data, 1);
            const barWidth = (width - padding.left - padding.right) / months.length * 0.6;

            // Draw grid lines
            ctx.strokeStyle = '#e5e7eb';
            ctx.lineWidth = 1;
            
            // Horizontal grid lines
            for (let i = 0; i <= 5; i++) {
                const y = padding.top + (height - padding.top - padding.bottom) * (1 - i / 5);
                ctx.beginPath();
                ctx.moveTo(padding.left, y);
                ctx.lineTo(width - padding.right, y);
                ctx.stroke();
                
                // Y-axis labels
                ctx.fillStyle = '#6b7280';
                ctx.font = '10px Arial';
                ctx.textAlign = 'right';
                ctx.textBaseline = 'middle';
                ctx.fillText(`৳${(maxRevenue * i / 5).toLocaleString('en-IN', { maximumFractionDigits: 0 })}`, 
                           padding.left - 10, y);
            }

            // Draw bars
            data.forEach((revenue, index) => {
                const x = padding.left + (width - padding.left - padding.right) * (index / months.length);
                const barHeight = (height - padding.top - padding.bottom) * (revenue / maxRevenue);
                const barY = height - padding.bottom - barHeight;

                // Gradient fill
                const gradient = ctx.createLinearGradient(x, barY, x, height - padding.bottom);
                gradient.addColorStop(0, '#10b981');
                gradient.addColorStop(1, '#059669');

                ctx.fillStyle = gradient;
                ctx.fillRect(x + barWidth * 0.2, barY, barWidth * 0.6, barHeight);

                // Value label
                if (revenue > 0) {
                    ctx.fillStyle = '#374151';
                    ctx.font = 'bold 10px Arial';
                    ctx.textAlign = 'center';
                    ctx.textBaseline = 'bottom';
                    ctx.fillText(`৳${revenue.toLocaleString('en-IN', { maximumFractionDigits: 0 })}`, 
                               x + barWidth / 2, barY - 5);
                }

                // Month label
                ctx.fillStyle = '#6b7280';
                ctx.font = '10px Arial';
                ctx.textAlign = 'center';
                ctx.textBaseline = 'top';
                ctx.fillText(months[index].slice(-2), x + barWidth / 2, height - padding.bottom + 10);
            });

            // Y-axis label
            ctx.save();
            ctx.fillStyle = '#374151';
            ctx.font = 'bold 12px Arial';
            ctx.textAlign = 'center';
            ctx.textBaseline = 'middle';
            ctx.translate(20, height / 2);
            ctx.rotate(-Math.PI / 2);
            ctx.fillText('Revenue (BDT)', 0, 0);
            ctx.restore();
        }

        // Render invoices
        function renderInvoices() {
            const startIndex = (currentPage - 1) * ITEMS_PER_PAGE;
            const endIndex = startIndex + ITEMS_PER_PAGE;
            const pageInvoices = filteredInvoices.slice(startIndex, endIndex);

            if (pageInvoices.length === 0) {
                elements.emptyState.style.display = 'block';
                elements.container.innerHTML = '';
                elements.container.appendChild(elements.emptyState);
                return;
            }

            elements.emptyState.style.display = 'none';

            let html = '<div class="space-y-4">';
            
            pageInvoices.forEach(invoice => {
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
                                                    ${escapeHtml(invoice.client_name)}
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
                                            <span class="invoice-type-badge type-service">
                                                Visa Service
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
                                        onclick="sendInvoiceOptions('${invoice.id}', '${escapeHtml(invoice.client_email)}', '${escapeHtml(invoice.phone)}')">
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
            elements.container.innerHTML = html;
            
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

            isLoading = true;
            elements.infiniteLoader.classList.add('active');

            // Simulate loading delay
            await new Promise(resolve => setTimeout(resolve, 500));

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