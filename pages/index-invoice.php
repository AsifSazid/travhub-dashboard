<?php
// pages/index-invoices.php

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
        /* ===== BASE STYLES ===== */
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
            white-space: nowrap;
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
            font-size: 0.8rem;
            padding: 0.5rem 0.75rem;
        }

        .action-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        /* ===== MODAL OVERLAY ===== */
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
            padding: 1rem;
        }

        .modal-overlay.active {
            opacity: 1;
            visibility: visible;
        }

        .modal-content {
            background: white;
            border-radius: 0.75rem;
            width: 100%;
            max-width: 500px;
            max-height: 90vh;
            overflow-y: auto;
            transform: translateY(20px);
            transition: transform 0.3s ease;
        }

        .modal-overlay.active .modal-content {
            transform: translateY(0);
        }

        /* ===== MARK AS PAID MODAL ===== */
        #markAsPaidModal {
            position: fixed;
            inset: 0;
            background-color: rgba(0, 0, 0, 0.6);
            z-index: 9999;
            display: none;
            align-items: center;
            justify-content: center;
            padding: 1rem;
            overflow-y: auto;
        }

        #markAsPaidModal.flex {
            display: flex;
        }

        #markAsPaidModal .modal-box {
            background: white;
            border-radius: 1.25rem;
            width: 100%;
            max-width: 560px;
            max-height: 95vh;
            overflow-y: auto;
            box-shadow: 0 25px 60px rgba(0, 0, 0, 0.3);
            animation: slideUp 0.3s ease-out;
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px) scale(0.98);
            }
            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }

        /* ===== RESPONSIVE GRID FOR ACTION BUTTONS ===== */
        .action-grid {
            display: grid;
            gap: 0.5rem;
        }

        @media (min-width: 768px) {
            .action-grid {
                grid-template-columns: 1fr 1fr;
            }
            .action-grid .full-width {
                grid-column: 1 / -1;
            }
        }

        @media (max-width: 767px) {
            .action-grid {
                grid-template-columns: 1fr 1fr;
            }
        }

        @media (max-width: 480px) {
            .action-grid {
                grid-template-columns: 1fr 1fr;
                gap: 0.4rem;
            }
            .action-btn {
                font-size: 0.7rem;
                padding: 0.4rem 0.5rem;
            }
            .action-btn i {
                font-size: 0.75rem;
            }
        }

        /* ===== RESPONSIVE STATS CARDS ===== */
        .stats-grid {
            display: grid;
            gap: 1rem;
        }

        @media (min-width: 1024px) {
            .stats-grid {
                grid-template-columns: repeat(4, 1fr);
            }
        }
        @media (min-width: 768px) and (max-width: 1023px) {
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }
        @media (max-width: 767px) {
            .stats-grid {
                grid-template-columns: 1fr 1fr;
                gap: 0.75rem;
            }
        }
        @media (max-width: 480px) {
            .stats-grid {
                grid-template-columns: 1fr 1fr;
                gap: 0.5rem;
            }
            .stats-grid .stat-card {
                padding: 0.75rem !important;
            }
            .stats-grid .stat-card .stat-icon {
                width: 2.5rem !important;
                height: 2.5rem !important;
                font-size: 0.9rem !important;
            }
            .stats-grid .stat-card .stat-value {
                font-size: 1rem !important;
            }
            .stats-grid .stat-card .stat-label {
                font-size: 0.65rem !important;
            }
        }

        /* ===== FILTERS RESPONSIVE ===== */
        .filters-grid {
            display: grid;
            gap: 0.75rem;
        }

        @media (min-width: 1024px) {
            .filters-grid {
                grid-template-columns: repeat(4, 1fr);
            }
        }
        @media (min-width: 768px) and (max-width: 1023px) {
            .filters-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }
        @media (max-width: 767px) {
            .filters-grid {
                grid-template-columns: 1fr 1fr;
            }
        }
        @media (max-width: 480px) {
            .filters-grid {
                grid-template-columns: 1fr;
            }
        }

        /* ===== Skeleton Loader ===== */
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

        /* ===== Modal Responsive ===== */
        #markAsPaidModal .modal-box {
            padding: 1.5rem;
        }

        #markAsPaidModal .modal-box .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 1.25rem;
            gap: 1rem;
        }

        #markAsPaidModal .modal-box .modal-header h3 {
            font-size: 1.1rem;
        }

        @media (max-width: 480px) {
            #markAsPaidModal .modal-box {
                padding: 1rem;
                border-radius: 1rem;
                max-height: 98vh;
            }
            #markAsPaidModal .modal-box .modal-header h3 {
                font-size: 0.95rem;
            }
            #markAsPaidModal .modal-box .modal-header .close-btn {
                width: 2rem;
                height: 2rem;
                font-size: 0.8rem;
            }
        }

        /* ===== Payment Method Grid ===== */
        .payment-method-grid {
            display: grid;
            gap: 0.4rem;
        }

        @media (min-width: 640px) {
            .payment-method-grid {
                grid-template-columns: repeat(5, 1fr);
            }
        }
        @media (max-width: 639px) {
            .payment-method-grid {
                grid-template-columns: repeat(3, 1fr);
            }
        }
        @media (max-width: 400px) {
            .payment-method-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        .payment-method-grid .map-method-label {
            padding: 0.5rem 0.25rem;
            font-size: 0.7rem;
            text-align: center;
            border-radius: 0.5rem;
            border: 2px solid #e5e7eb;
            cursor: pointer;
            transition: all 0.2s;
        }

        .payment-method-grid .map-method-label i {
            font-size: 1rem;
        }
        .payment-method-grid .map-method-label span {
            font-size: 0.6rem;
            font-weight: 600;
        }

        /* ===== Invoice List Responsive ===== */
        .invoice-card .invoice-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
        }

        @media (max-width: 640px) {
            .invoice-card {
                padding: 1rem !important;
            }
            .invoice-card .invoice-header {
                flex-direction: column !important;
                align-items: flex-start !important;
            }
            .invoice-card .invoice-amount-section {
                width: 100%;
                flex-direction: row !important;
                justify-content: space-between !important;
                align-items: center !important;
            }
            .invoice-card .payment-summary {
                grid-template-columns: 1fr 1fr !important;
            }
            .invoice-card .invoice-actions {
                width: 100%;
                margin-top: 0.75rem;
            }
        }

        @media (max-width: 480px) {
            .invoice-card .payment-summary {
                grid-template-columns: 1fr !important;
            }
            .invoice-card .payment-summary .summary-item {
                padding: 0.4rem 0.6rem !important;
            }
            .invoice-card .payment-summary .summary-item .label {
                font-size: 0.6rem !important;
            }
            .invoice-card .payment-summary .summary-item .value {
                font-size: 0.8rem !important;
            }
        }

        /* ===== Chart Responsive ===== */
        .charts-grid {
            display: grid;
            gap: 1.5rem;
        }

        @media (min-width: 1024px) {
            .charts-grid {
                grid-template-columns: 1fr 1fr;
            }
        }
        @media (max-width: 1023px) {
            .charts-grid {
                grid-template-columns: 1fr;
            }
        }

        .chart-container {
            position: relative;
            height: 280px;
            width: 100%;
        }

        @media (max-width: 480px) {
            .chart-container {
                height: 220px;
            }
        }

        /* ===== Preview Modal ===== */
        .preview-modal {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.5);
            z-index: 9999;
            align-items: center;
            justify-content: center;
            padding: 1rem;
        }

        .preview-modal.active {
            display: flex;
        }

        .preview-modal .preview-content {
            background: white;
            border-radius: 0.75rem;
            width: 100%;
            max-width: 600px;
            max-height: 90vh;
            overflow-y: auto;
            padding: 1.5rem;
        }

        @media (max-width: 480px) {
            .preview-modal .preview-content {
                padding: 1rem;
                max-width: 100%;
                margin: 1rem;
                border-radius: 0.5rem;
            }
        }

        /* ===== Scrollbar Styling ===== */
        #markAsPaidModal .modal-box::-webkit-scrollbar {
            width: 4px;
        }
        #markAsPaidModal .modal-box::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 8px;
        }
        #markAsPaidModal .modal-box::-webkit-scrollbar-thumb {
            background: #c1c1c1;
            border-radius: 8px;
        }

        /* ===== Empty State ===== */
        .empty-state {
            text-align: center;
            padding: 3rem 1rem;
        }

        .empty-state-icon {
            font-size: 3.5rem;
            color: #d1d5db;
            margin-bottom: 1rem;
        }

        /* ===== Infinite Loader ===== */
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
    <main id="mainContent" class="pt-16 pl-0 lg:pl-64 lg:mt-16 transition-all duration-300 h-full">
        <div class="p-3 md:p-6 h-full">
            <!-- Header -->
            <div class="mb-4 md:mb-6">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                    <div>
                        <h1 class="text-xl md:text-3xl font-bold text-gray-800">Invoice Management</h1>
                        <p class="text-sm text-gray-600 mt-1">Manage and track all invoices in one place</p>
                    </div>
                    <div class="flex items-center gap-2 flex-wrap">
                        <button id="refresh-btn" class="bg-white hover:bg-gray-50 text-gray-700 font-medium py-2 px-3 md:px-4 rounded-lg transition duration-300 flex items-center border border-gray-300 text-sm">
                            <i class="fas fa-sync-alt mr-1 md:mr-2"></i> <span class="hidden sm:inline">Refresh</span>
                        </button>
                        <a href="create-invoice.php" class="bg-gradient-to-r from-green-600 to-emerald-700 hover:from-green-700 hover:to-emerald-800 text-white font-medium py-2 px-3 md:px-5 rounded-lg transition duration-300 flex items-center shadow text-sm">
                            <i class="fas fa-plus mr-1 md:mr-2"></i> <span class="hidden sm:inline">Create Invoice</span>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Charts Section -->
            <div id="chartsSection" class="mb-4 md:mb-6"></div>

            <!-- Stats Cards -->
            <div id="statsCards" class="stats-grid mb-4 md:mb-6"></div>

            <!-- Filters -->
            <div class="bg-white rounded-xl shadow-sm p-4 md:p-5 mb-4 md:mb-6 border border-gray-200">
                <div class="filters-grid">
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">Status</label>
                        <select id="filter-status" class="w-full bg-white border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent">
                            <option value="">All Status</option>
                            <option value="pending">Pending</option>
                            <option value="partial">Partial</option>
                            <option value="paid">Paid</option>
                            <option value="overdue">Overdue</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">Date Range</label>
                        <select id="filter-date" class="w-full bg-white border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent">
                            <option value="">All Time</option>
                            <option value="today">Today</option>
                            <option value="week">This Week</option>
                            <option value="month">This Month</option>
                            <option value="quarter">This Quarter</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">Client</label>
                        <input type="text" id="filter-client" placeholder="Search client..." class="w-full bg-white border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">Invoice No.</label>
                        <input type="text" id="filter-invoice-no" placeholder="Invoice number..." class="w-full bg-white border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent">
                    </div>
                </div>
            </div>

            <!-- Invoices List -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="p-4 md:p-5 border-b border-gray-200 bg-gray-50">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                        <h3 class="text-lg font-semibold text-gray-800">All Invoices</h3>
                        <div class="text-sm text-gray-600">
                            Showing <span id="showing-count">0</span> of <span id="total-count">0</span> invoices
                        </div>
                    </div>
                </div>

                <div id="invoices-container" class="p-3 md:p-4 min-h-[400px]">
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
                        <p class="text-gray-500 mb-6 max-w-md mx-auto text-sm">Start by creating your first invoice for visa applications or services.</p>
                        <a href="create-invoice.php" class="bg-gradient-to-r from-green-600 to-emerald-700 hover:from-green-700 hover:to-emerald-800 text-white font-medium py-2.5 px-6 rounded-lg transition duration-300 inline-flex items-center text-sm">
                            <i class="fas fa-plus mr-2"></i> Create First Invoice
                        </a>
                    </div>

                    <!-- Invoices will be loaded here -->
                </div>

                <!-- Infinite Scroll Loader -->
                <div id="infinite-loader" class="infinite-loader">
                    <div class="loader-spinner"></div>
                    <p class="text-gray-500 mt-2 text-sm">Loading more invoices...</p>
                </div>
            </div>
        </div>
    </main>

    <!-- Send Invoice Modal -->
    <div id="sendModal" class="modal-overlay">
        <div class="modal-content">
            <div class="p-4 md:p-6 border-b border-gray-200">
                <h3 class="text-lg md:text-xl font-bold text-gray-800">Send Invoice</h3>
            </div>
            <div class="p-4 md:p-6" id="sendModalContent">
                <!-- Content loaded dynamically -->
            </div>
            <div class="p-4 md:p-6 border-t border-gray-200 bg-gray-50 flex justify-end">
                <button onclick="closeSendModal()" class="bg-gray-200 hover:bg-gray-300 text-gray-800 font-medium py-2 px-4 md:px-6 rounded-lg transition duration-300 text-sm">
                    Cancel
                </button>
            </div>
        </div>
    </div>

    <?php include '../elements/floating-menus.php'; ?>

    <script src="../assets/js/script.js?time=<?php echo time(); ?>"></script>
    <script>
        // Configuration
        const IP_PATH = '<?php echo htmlspecialchars($base_ip_path); ?>';
        const API_URL = "<?php echo $allInvoice; ?>";
        const UPDATE_STATUS_API = `${IP_PATH}/api/invoices/update-invoice-status.php`;
        const ITEMS_PER_PAGE = 10;
        const INFINITE_SCROLL_THRESHOLD = 100;
        const DEBOUNCE_DELAY = 300;

        // State management
        let invoices = [];
        let filteredInvoices = [];
        let currentPage = 1;
        let isLoading = false;
        let hasMore = true;
        let searchTimeout;
        let isInfiniteScroll = true;

        // DOM Elements
        const elements = {
            container: document.getElementById('invoices-container'),
            skeleton: document.getElementById('skeleton-loaders'),
            emptyState: document.getElementById('empty-state'),
            infiniteLoader: document.getElementById('infinite-loader'),
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
            elements.filters.status.addEventListener('change', () => debouncedFilter());
            elements.filters.date.addEventListener('change', () => debouncedFilter());
            elements.filters.client.addEventListener('input', () => debouncedFilter());
            elements.filters.invoiceNo.addEventListener('input', () => debouncedFilter());

            document.getElementById('refresh-btn').addEventListener('click', async () => {
                await loadData();
            });
        }

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

        function showSkeleton() {
            elements.skeleton.style.display = 'block';
            elements.container.innerHTML = '';
            elements.container.appendChild(elements.skeleton);
            elements.emptyState.style.display = 'none';
        }

        function hideSkeleton() {
            elements.skeleton.style.display = 'none';
        }

        function filterInvoices() {
            const statusFilter = elements.filters.status.value;
            const dateFilter = elements.filters.date.value;
            const clientFilter = elements.filters.client.value.toLowerCase();
            const invoiceNoFilter = elements.filters.invoiceNo.value.toLowerCase();

            filteredInvoices = invoices.filter(invoice => {
                if (statusFilter && invoice.status !== statusFilter) return false;
                if (dateFilter) {
                    const invoiceDate = new Date(invoice.created_at);
                    const now = new Date();
                    const startDate = new Date();
                    switch (dateFilter) {
                        case 'today': startDate.setHours(0,0,0,0); break;
                        case 'week': startDate.setDate(now.getDate() - 7); break;
                        case 'month': startDate.setMonth(now.getMonth() - 1); break;
                        case 'quarter': startDate.setMonth(now.getMonth() - 3); break;
                    }
                    if (invoiceDate < startDate) return false;
                }
                if (clientFilter && !invoice.client_name.toLowerCase().includes(clientFilter)) return false;
                if (invoiceNoFilter && !invoice.invoice_no.toLowerCase().includes(invoiceNoFilter)) return false;
                return true;
            });
            updateStats();
        }

        function updateStats() {
            const totalInvoices = filteredInvoices.length;
            const totalRevenue = filteredInvoices.reduce((sum, inv) => sum + inv.total_amount, 0);
            const pendingAmount = filteredInvoices
                .filter(inv => ['pending', 'partial'].includes(inv.status))
                .reduce((sum, inv) => sum + inv.due_amount, 0);
            const overdueAmount = filteredInvoices
                .filter(inv => inv.status === 'overdue')
                .reduce((sum, inv) => sum + inv.due_amount, 0);
            const paidAmount = filteredInvoices.reduce((sum, inv) => sum + inv.paid_amount, 0);
            
            const formatCurrency = (amount) => {
                return `৳${amount.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
            };
        
            elements.statsCards.innerHTML = `
                <div class="stat-card bg-white rounded-xl shadow-sm p-4 md:p-5 border border-gray-200 hover:shadow-md transition-shadow">
                    <div class="flex items-center">
                        <div class="stat-icon w-10 h-10 md:w-12 md:h-12 bg-gradient-to-r from-blue-100 to-blue-200 rounded-lg flex items-center justify-center mr-3 md:mr-4">
                            <i class="fas fa-file-invoice text-blue-600 text-lg md:text-xl"></i>
                        </div>
                        <div>
                            <p class="stat-label text-xs text-gray-500 font-medium">Total Invoices</p>
                            <h3 class="stat-value text-xl md:text-2xl font-bold text-gray-800">${totalInvoices}</h3>
                        </div>
                    </div>
                    <div class="mt-2 text-xs md:text-sm text-gray-600">
                        <i class="fas fa-money-bill-wave mr-1"></i>
                        Total Value: ${formatCurrency(totalRevenue)}
                    </div>
                </div>
        
                <div class="stat-card bg-white rounded-xl shadow-sm p-4 md:p-5 border border-gray-200 hover:shadow-md transition-shadow">
                    <div class="flex items-center">
                        <div class="stat-icon w-10 h-10 md:w-12 md:h-12 bg-gradient-to-r from-green-100 to-green-200 rounded-lg flex items-center justify-center mr-3 md:mr-4">
                            <i class="fas fa-money-bill-wave text-green-600 text-lg md:text-xl"></i>
                        </div>
                        <div>
                            <p class="stat-label text-xs text-gray-500 font-medium">Total Revenue</p>
                            <h3 class="stat-value text-xl md:text-2xl font-bold text-gray-800">${formatCurrency(totalRevenue)}</h3>
                        </div>
                    </div>
                    <div class="mt-2 text-xs md:text-sm text-gray-600">
                        <i class="fas fa-check-circle mr-1"></i>
                        Paid: ${formatCurrency(paidAmount)}
                    </div>
                </div>
        
                <div class="stat-card bg-white rounded-xl shadow-sm p-4 md:p-5 border border-gray-200 hover:shadow-md transition-shadow">
                    <div class="flex items-center">
                        <div class="stat-icon w-10 h-10 md:w-12 md:h-12 bg-gradient-to-r from-amber-100 to-amber-200 rounded-lg flex items-center justify-center mr-3 md:mr-4">
                            <i class="fas fa-clock text-amber-600 text-lg md:text-xl"></i>
                        </div>
                        <div>
                            <p class="stat-label text-xs text-gray-500 font-medium">Pending Amount</p>
                            <h3 class="stat-value text-xl md:text-2xl font-bold text-gray-800">${formatCurrency(pendingAmount)}</h3>
                        </div>
                    </div>
                    <div class="mt-2">
                        <div class="flex justify-between text-xs md:text-sm">
                            <span class="text-gray-600">Pending:</span>
                            <span class="font-medium">${filteredInvoices.filter(inv => inv.status === 'pending').length}</span>
                        </div>
                        <div class="flex justify-between text-xs md:text-sm">
                            <span class="text-gray-600">Partial:</span>
                            <span class="font-medium">${filteredInvoices.filter(inv => inv.status === 'partial').length}</span>
                        </div>
                    </div>
                </div>
        
                <div class="stat-card bg-white rounded-xl shadow-sm p-4 md:p-5 border border-gray-200 hover:shadow-md transition-shadow">
                    <div class="flex items-center">
                        <div class="stat-icon w-10 h-10 md:w-12 md:h-12 bg-gradient-to-r from-red-100 to-red-200 rounded-lg flex items-center justify-center mr-3 md:mr-4">
                            <i class="fas fa-exclamation-triangle text-red-600 text-lg md:text-xl"></i>
                        </div>
                        <div>
                            <p class="stat-label text-xs text-gray-500 font-medium">Overdue Amount</p>
                            <h3 class="stat-value text-xl md:text-2xl font-bold text-gray-800">${formatCurrency(overdueAmount)}</h3>
                        </div>
                    </div>
                    <div class="mt-2">
                        <div class="flex justify-between text-xs md:text-sm">
                            <span class="text-gray-600">Overdue:</span>
                            <span class="font-medium">${filteredInvoices.filter(inv => inv.status === 'overdue').length}</span>
                        </div>
                        <div class="flex justify-between text-xs md:text-sm">
                            <span class="text-gray-600">Due Passed:</span>
                            <span class="font-medium">${filteredInvoices.filter(inv => {
                                const dueDate = new Date(inv.due_date);
                                const today = new Date();
                                return dueDate < today && inv.status !== 'paid';
                            }).length}</span>
                        </div>
                    </div>
                </div>
            `;
        
            elements.showingCount.textContent = Math.min(filteredInvoices.length, currentPage * ITEMS_PER_PAGE);
            elements.totalCount.textContent = filteredInvoices.length;
        }

        function renderCharts() {
            if (invoices.length === 0) return;
        
            const statusData = invoices.reduce((acc, invoice) => {
                acc[invoice.status] = (acc[invoice.status] || 0) + 1;
                return acc;
            }, {});
        
            const monthlyData = invoices.reduce((acc, invoice) => {
                const date = new Date(invoice.created_at);
                const monthYear = `${date.getFullYear()}-${(date.getMonth() + 1).toString().padStart(2, '0')}`;
                if (!acc[monthYear]) acc[monthYear] = { revenue: 0, count: 0 };
                acc[monthYear].revenue += invoice.total_amount;
                acc[monthYear].count += 1;
                return acc;
            }, {});
        
            const last6Months = [];
            const now = new Date();
            for (let i = 5; i >= 0; i--) {
                const date = new Date(now.getFullYear(), now.getMonth() - i, 1);
                last6Months.push(`${date.getFullYear()}-${(date.getMonth() + 1).toString().padStart(2, '0')}`);
            }
        
            elements.chartsSection.innerHTML = `
                <div class="bg-white rounded-xl shadow-sm p-4 md:p-5 border border-gray-200">
                    <h3 class="text-base md:text-lg font-semibold text-gray-800 mb-4 md:mb-6">Invoice Analytics</h3>
                    
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-3 md:gap-4 mb-4 md:mb-6">
                        <div class="bg-gradient-to-r from-amber-50 to-amber-100 p-3 md:p-4 rounded-lg border border-amber-200">
                            <div class="flex items-center">
                                <div class="w-8 h-8 md:w-10 md:h-10 bg-amber-100 rounded-full flex items-center justify-center mr-2 md:mr-3">
                                    <i class="fas fa-clock text-amber-600 text-sm md:text-base"></i>
                                </div>
                                <div>
                                    <p class="text-xs text-amber-800">Pending</p>
                                    <p class="text-lg md:text-xl font-bold text-amber-900">${statusData.pending || 0}</p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="bg-gradient-to-r from-red-50 to-red-100 p-3 md:p-4 rounded-lg border border-red-200">
                            <div class="flex items-center">
                                <div class="w-8 h-8 md:w-10 md:h-10 bg-red-100 rounded-full flex items-center justify-center mr-2 md:mr-3">
                                    <i class="fas fa-exclamation-triangle text-red-600 text-sm md:text-base"></i>
                                </div>
                                <div>
                                    <p class="text-xs text-red-800">Overdue</p>
                                    <p class="text-lg md:text-xl font-bold text-red-900">${statusData.overdue || 0}</p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="bg-gradient-to-r from-purple-50 to-purple-100 p-3 md:p-4 rounded-lg border border-purple-200">
                            <div class="flex items-center">
                                <div class="w-8 h-8 md:w-10 md:h-10 bg-purple-100 rounded-full flex items-center justify-center mr-2 md:mr-3">
                                    <i class="fas fa-chart-pie text-purple-600 text-sm md:text-base"></i>
                                </div>
                                <div>
                                    <p class="text-xs text-purple-800">Partial</p>
                                    <p class="text-lg md:text-xl font-bold text-purple-900">${statusData.partial || 0}</p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="bg-gradient-to-r from-green-50 to-green-100 p-3 md:p-4 rounded-lg border border-green-200">
                            <div class="flex items-center">
                                <div class="w-8 h-8 md:w-10 md:h-10 bg-green-100 rounded-full flex items-center justify-center mr-2 md:mr-3">
                                    <i class="fas fa-check-circle text-green-600 text-sm md:text-base"></i>
                                </div>
                                <div>
                                    <p class="text-xs text-green-800">Paid</p>
                                    <p class="text-lg md:text-xl font-bold text-green-900">${statusData.paid || 0}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="charts-grid">
                        <div class="bg-gray-50 rounded-lg p-3 md:p-4 border border-gray-200">
                            <div class="flex justify-between items-center mb-3 md:mb-4">
                                <h4 class="font-medium text-gray-700 text-sm md:text-base">Status Distribution</h4>
                                <span class="text-xs text-gray-500">Total: ${invoices.length}</span>
                            </div>
                            <div class="chart-container">
                                <canvas id="statusChart"></canvas>
                            </div>
                            <div class="mt-3 md:mt-4 grid grid-cols-2 gap-1 md:gap-2" id="statusLegend"></div>
                        </div>
                        
                        <div class="bg-gray-50 rounded-lg p-3 md:p-4 border border-gray-200">
                            <div class="flex justify-between items-center mb-3 md:mb-4">
                                <h4 class="font-medium text-gray-700 text-sm md:text-base">Monthly Revenue</h4>
                                <span class="text-xs text-gray-500">Last 6 months</span>
                            </div>
                            <div class="chart-container">
                                <canvas id="revenueChart"></canvas>
                            </div>
                            <div class="mt-2 text-center text-xs text-gray-500">
                                <i class="fas fa-info-circle mr-1"></i> Hover for details
                            </div>
                        </div>
                    </div>
                </div>
            `;
        
            setTimeout(() => {
                createActualStatusChart(statusData);
                createActualRevenueChart(monthlyData, last6Months);
            }, 100);
        }

        function createActualStatusChart(statusData) {
            const canvas = document.getElementById('statusChart');
            if (!canvas) return;
            
            const ctx = canvas.getContext('2d');
            const rect = canvas.parentElement.getBoundingClientRect();
            const width = canvas.width = rect.width || 300;
            const height = canvas.height = Math.min(rect.height || 250, 280);
            
            ctx.clearRect(0, 0, width, height);
            
            const colors = { pending: '#f59e0b', paid: '#10b981', overdue: '#ef4444', partial: '#8b5cf6' };
            const total = Object.values(statusData).reduce((a, b) => a + b, 0);
            if (total === 0) return;
            
            const centerX = width / 2;
            const centerY = height / 2;
            const radius = Math.min(width, height) / 2 - 20;
            
            let startAngle = 0;
            const entries = Object.entries(statusData);
            
            entries.forEach(([status, count]) => {
                const sliceAngle = (count / total) * 2 * Math.PI;
                ctx.beginPath();
                ctx.moveTo(centerX, centerY);
                ctx.arc(centerX, centerY, radius, startAngle, startAngle + sliceAngle);
                ctx.closePath();
                ctx.fillStyle = colors[status] || '#9ca3af';
                ctx.fill();
                ctx.strokeStyle = '#ffffff';
                ctx.lineWidth = 2;
                ctx.stroke();
                
                const midAngle = startAngle + sliceAngle / 2;
                const textRadius = radius * 0.5;
                const textX = centerX + Math.cos(midAngle) * textRadius;
                const textY = centerY + Math.sin(midAngle) * textRadius;
                
                if (sliceAngle > 0.3) {
                    ctx.fillStyle = '#ffffff';
                    ctx.font = 'bold 10px Arial';
                    ctx.textAlign = 'center';
                    ctx.textBaseline = 'middle';
                    ctx.fillText(`${((count / total) * 100).toFixed(0)}%`, textX, textY);
                }
                startAngle += sliceAngle;
            });
            
            ctx.beginPath();
            ctx.arc(centerX, centerY, radius * 0.25, 0, Math.PI * 2);
            ctx.fillStyle = '#f8fafc';
            ctx.fill();
            ctx.fillStyle = '#374151';
            ctx.font = 'bold 10px Arial';
            ctx.textAlign = 'center';
            ctx.textBaseline = 'middle';
            ctx.fillText('Total', centerX, centerY - 8);
            ctx.font = 'bold 16px Arial';
            ctx.fillText(total.toString(), centerX, centerY + 10);
            
            const legendContainer = document.getElementById('statusLegend');
            if (legendContainer) {
                let legendHTML = '';
                entries.forEach(([status, count]) => {
                    const percentage = ((count / total) * 100).toFixed(1);
                    legendHTML += `
                        <div class="flex items-center p-1 md:p-2 bg-white rounded border text-xs md:text-sm">
                            <div class="w-2 h-2 md:w-3 md:h-3 rounded-full mr-1 md:mr-2" style="background-color: ${colors[status] || '#9ca3af'}"></div>
                            <span class="font-medium text-gray-700 capitalize">${status}</span>
                            <span class="ml-auto text-gray-600">${count} (${percentage}%)</span>
                        </div>
                    `;
                });
                legendContainer.innerHTML = legendHTML;
            }
        }

        function createActualRevenueChart(monthlyData, months) {
            const canvas = document.getElementById('revenueChart');
            if (!canvas) return;
            
            const ctx = canvas.getContext('2d');
            const rect = canvas.parentElement.getBoundingClientRect();
            const width = canvas.width = rect.width || 300;
            const height = canvas.height = Math.min(rect.height || 250, 280);
            
            ctx.clearRect(0, 0, width, height);
            
            const padding = { top: 25, right: 15, bottom: 35, left: 40 };
            const chartWidth = width - padding.left - padding.right;
            const chartHeight = height - padding.top - padding.bottom;
            
            const data = months.map(month => monthlyData[month]?.revenue || 0);
            const maxRevenue = Math.max(...data, 1000);
            
            const monthLabels = months.map(month => {
                const [year, monthNum] = month.split('-');
                const monthNames = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
                return `${monthNames[parseInt(monthNum) - 1]}'${year.toString().slice(-2)}`;
            });
            
            ctx.strokeStyle = '#e5e7eb';
            ctx.lineWidth = 0.5;
            
            const gridLines = 4;
            for (let i = 0; i <= gridLines; i++) {
                const y = padding.top + chartHeight * (1 - i / gridLines);
                ctx.beginPath();
                ctx.moveTo(padding.left, y);
                ctx.lineTo(width - padding.right, y);
                ctx.stroke();
                
                ctx.fillStyle = '#6b7280';
                ctx.font = '8px Arial';
                ctx.textAlign = 'right';
                ctx.textBaseline = 'middle';
                const value = (maxRevenue * i / gridLines);
                let label = value >= 1000000 ? `৳${(value/1000000).toFixed(1)}M` : 
                           value >= 1000 ? `৳${(value/1000).toFixed(0)}K` : `৳${value.toFixed(0)}`;
                ctx.fillText(label, padding.left - 5, y);
            }
            
            const barWidth = chartWidth / months.length * 0.6;
            const barSpacing = chartWidth / months.length * 0.4;
            
            data.forEach((revenue, index) => {
                if (revenue <= 0) return;
                const x = padding.left + (index * (barWidth + barSpacing)) + barSpacing / 2;
                const barHeight = chartHeight * (revenue / maxRevenue);
                const y = padding.top + chartHeight - barHeight;
                
                const gradient = ctx.createLinearGradient(x, y, x, y + barHeight);
                gradient.addColorStop(0, '#10b981');
                gradient.addColorStop(1, '#059669');
                
                ctx.fillStyle = gradient;
                const rounded = Math.min(4, barWidth / 4);
                ctx.beginPath();
                ctx.moveTo(x, y + rounded);
                ctx.quadraticCurveTo(x, y, x + rounded, y);
                ctx.lineTo(x + barWidth - rounded, y);
                ctx.quadraticCurveTo(x + barWidth, y, x + barWidth, y + rounded);
                ctx.lineTo(x + barWidth, y + barHeight);
                ctx.lineTo(x, y + barHeight);
                ctx.closePath();
                ctx.fill();
                
                if (barHeight > 15) {
                    ctx.fillStyle = '#374151';
                    ctx.font = 'bold 7px Arial';
                    ctx.textAlign = 'center';
                    ctx.textBaseline = 'bottom';
                    let displayValue = revenue >= 1000000 ? `৳${(revenue/1000000).toFixed(1)}M` :
                                      revenue >= 1000 ? `৳${(revenue/1000).toFixed(0)}K` : `৳${revenue.toFixed(0)}`;
                    ctx.fillText(displayValue, x + barWidth / 2, y - 3);
                }
                
                const xLabel = padding.left + (index * (barWidth + barSpacing)) + barSpacing / 2 + barWidth / 2;
                const yLabel = height - padding.bottom + 10;
                ctx.fillStyle = '#6b7280';
                ctx.font = '8px Arial';
                ctx.textAlign = 'center';
                ctx.textBaseline = 'top';
                ctx.fillText(monthLabels[index], xLabel, yLabel);
            });
        }

        function renderInvoices() {
            const startIndex = (currentPage - 1) * ITEMS_PER_PAGE;
            const endIndex = startIndex + ITEMS_PER_PAGE;
            const pageInvoices = filteredInvoices.slice(startIndex, endIndex);
        
            if (pageInvoices.length === 0) {
                if (currentPage === 1) {
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
                const isOverdue = invoice.status === 'overdue';
                const updatedDate = invoice.updated_at ? new Date(invoice.updated_at) : null;
                
                html += `
                    <div class="invoice-card bg-white border border-gray-200 rounded-lg p-3 md:p-5 hover:border-green-300 fade-in mb-3 md:mb-4">
                        <div class="flex flex-col md:flex-row justify-between gap-3 md:gap-4">
                            <!-- Left Column -->
                            <div class="flex-1">
                                <div class="invoice-header flex flex-col sm:flex-row sm:items-start justify-between mb-3 md:mb-4">
                                    <div class="mb-2 sm:mb-0">
                                        <div class="flex items-center mb-2">
                                            <div class="w-8 h-8 md:w-10 md:h-10 bg-gradient-to-r from-green-100 to-green-200 rounded-lg flex items-center justify-center mr-2 md:mr-3 flex-shrink-0">
                                                <i class="fas fa-file-invoice text-green-600 text-sm md:text-base"></i>
                                            </div>
                                            <div>
                                                <h3 class="font-bold text-gray-800 text-sm md:text-lg truncate max-w-[180px] sm:max-w-[250px] md:max-w-full">
                                                    ${escapeHtml(invoice.client_name)}
                                                </h3>
                                                <div class="flex flex-wrap items-center mt-0.5 md:mt-1 gap-2 md:gap-4">
                                                    <span class="text-gray-600 text-xs md:text-sm">
                                                        <i class="far fa-calendar mr-1"></i> 
                                                        ${createdDate.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })}
                                                    </span>
                                                    <span class="text-gray-600 text-xs md:text-sm ${isOverdue ? 'text-red-600 font-medium' : ''}">
                                                        <i class="far fa-clock mr-1"></i> 
                                                        Due: ${dueDate.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })}
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="invoice-meta text-xs md:text-sm text-gray-600">
                                            <span class="flex items-center bg-gray-100 px-2 py-0.5 md:px-3 md:py-1 rounded-full">
                                                <i class="fas fa-hashtag mr-1 md:mr-2"></i> ${escapeHtml(invoice.invoice_no)}
                                            </span>
                                            ${invoice.phone ? `
                                                <span class="flex items-center bg-gray-100 px-2 py-0.5 md:px-3 md:py-1 rounded-full">
                                                    <i class="fas fa-phone mr-1 md:mr-2"></i> ${escapeHtml(invoice.phone)}
                                                </span>
                                            ` : ''}
                                        </div>
                                    </div>
                                    
                                    <div class="invoice-amount-section flex flex-row sm:flex-col items-center sm:items-end gap-2 sm:gap-1">
                                        <div class="amount-badge bg-gradient-to-r from-blue-500 to-blue-600 text-white text-xs md:text-sm px-3 py-1 md:px-4 md:py-1.5">
                                            ৳ ${invoice.total_amount.toFixed(2)}
                                        </div>
                                        <span class="status-badge status-${invoice.status} text-xs">
                                            ${invoice.status.charAt(0).toUpperCase() + invoice.status.slice(1)}
                                        </span>
                                    </div>
                                </div>
                                
                                <!-- Payment Summary -->
                                <div class="payment-summary grid grid-cols-3 gap-2 md:gap-3 mb-3 md:mb-4">
                                    <div class="summary-item bg-green-50 p-2 md:p-3 rounded-lg border border-green-100">
                                        <div class="label text-xs text-green-700">Total</div>
                                        <div class="value text-sm md:text-base font-bold text-green-800">৳ ${invoice.total_amount.toFixed(2)}</div>
                                    </div>
                                    <div class="summary-item bg-blue-50 p-2 md:p-3 rounded-lg border border-blue-100">
                                        <div class="label text-xs text-blue-700">Paid</div>
                                        <div class="value text-sm md:text-base font-bold text-blue-800">৳ ${invoice.paid_amount.toFixed(2)}</div>
                                    </div>
                                    <div class="summary-item bg-red-50 p-2 md:p-3 rounded-lg border border-red-100">
                                        <div class="label text-xs text-red-700">Due</div>
                                        <div class="value text-sm md:text-base font-bold text-red-800">৳ ${invoice.due_amount.toFixed(2)}</div>
                                    </div>
                                </div>
                                
                                <!-- Meta Information -->
                                <div class="mt-2 pt-2 border-t border-gray-100 text-xs text-gray-500">
                                    <div class="flex flex-wrap gap-x-4 gap-y-1">
                                        <span><i class="fas fa-user-plus mr-1"></i> ${escapeHtml(invoice.created_by || 'N/A')}</span>
                                        <span><i class="far fa-calendar-alt mr-1"></i> ${createdDate.toLocaleString()}</span>
                                        ${invoice.updated_by || invoice.updated_at ? `
                                            <span><i class="fas fa-user-edit mr-1"></i> ${escapeHtml(invoice.updated_by || 'N/A')}</span>
                                        ` : ''}
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Action Buttons -->
                            <div class="invoice-actions md:w-48">
                                <div class="action-grid">
                                    <button class="download-btn bg-gradient-to-r from-green-500 to-emerald-600 hover:from-green-600 hover:to-emerald-700 text-white font-medium py-1.5 md:py-2.5 px-2 md:px-4 rounded-lg transition duration-300 flex items-center justify-center action-btn text-xs md:text-sm"
                                            onclick="downloadInvoice('${invoice.invoice_no}')">
                                        <i class="fas fa-download mr-1 md:mr-2"></i>
                                        <span>DL</span>
                                    </button>
                                    ${invoice.status !== 'paid' ? `
                                        <button class="edit-btn bg-blue-50 hover:bg-blue-100 text-blue-700 border border-blue-200 font-medium py-1.5 md:py-2.5 px-2 md:px-4 rounded-lg transition duration-300 flex items-center justify-center action-btn text-xs md:text-sm"
                                                onclick="editInvoice('${invoice.invoice_no}')">
                                            <i class="fas fa-pencil mr-1 md:mr-2"></i>
                                            <span>Edit</span>
                                        </button>
                                    ` : ''}
                                    <button class="send-btn bg-purple-50 hover:bg-purple-100 text-purple-700 border border-purple-200 font-medium py-1.5 md:py-2.5 px-2 md:px-4 rounded-lg transition duration-300 flex items-center justify-center action-btn text-xs md:text-sm"
                                            onclick="sendInvoiceOptions('${invoice.invoice_no}', '${escapeHtml(invoice.client_email)}', '${escapeHtml(invoice.phone)}')">
                                        <i class="fas fa-paper-plane mr-1 md:mr-2"></i>
                                        <span>Send</span>
                                    </button>
                                    ${invoice.status !== 'paid' ? `
                                        <button class="mark-paid-btn bg-green-50 hover:bg-green-100 text-green-700 border border-green-200 font-medium py-1.5 md:py-2.5 px-2 md:px-4 rounded-lg transition duration-300 flex items-center justify-center action-btn text-xs md:text-sm"
                                                onclick="markAsPaid('${invoice.invoice_no}')">
                                            <i class="fas fa-check-circle mr-1 md:mr-2"></i>
                                            <span>Paid</span>
                                        </button>
                                    ` : ''}
                                    <button class="share-btn bg-indigo-50 hover:bg-indigo-100 text-indigo-700 border border-indigo-200 font-medium py-1.5 md:py-2.5 px-2 md:px-4 rounded-lg transition duration-300 flex items-center justify-center action-btn text-xs md:text-sm full-width"
                                            onclick="shareInvoicePdf('${invoice.invoice_no}')">
                                        <i class="fas fa-share-alt mr-1 md:mr-2"></i>
                                        <span>Share</span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
            });
            
            if (currentPage === 1) {
                elements.container.innerHTML = `<div class="space-y-3 md:space-y-4" id="invoices-list">${html}</div>`;
            } else {
                const invoicesList = document.getElementById('invoices-list');
                if (invoicesList) {
                    invoicesList.innerHTML += html;
                } else {
                    elements.container.innerHTML = `<div class="space-y-3 md:space-y-4" id="invoices-list">${html}</div>`;
                }
            }
            
            const showingCount = Math.min(filteredInvoices.length, currentPage * ITEMS_PER_PAGE);
            elements.showingCount.textContent = showingCount;
            
            if (isInfiniteScroll && showingCount < filteredInvoices.length) {
                elements.infiniteLoader.style.display = 'block';
            } else {
                elements.infiniteLoader.style.display = 'none';
            }
        }

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
            await new Promise(resolve => setTimeout(resolve, 300));
            currentPage++;
            renderInvoices();
            isLoading = false;
            elements.infiniteLoader.classList.remove('active');
        }

        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        function showError(message) {
            elements.container.innerHTML = `
                <div class="text-center py-8 md:py-12">
                    <div class="w-16 h-16 md:w-24 md:h-24 mx-auto mb-4 md:mb-6 bg-red-100 rounded-full flex items-center justify-center">
                        <i class="fas fa-exclamation-triangle text-red-600 text-2xl md:text-3xl"></i>
                    </div>
                    <h4 class="text-lg md:text-xl font-semibold text-gray-600 mb-2">Error Loading Data</h4>
                    <p class="text-gray-500 mb-4 md:mb-6 max-w-md mx-auto text-sm">${message}</p>
                    <button onclick="loadData()" class="bg-gradient-to-r from-green-600 to-emerald-700 hover:from-green-700 hover:to-emerald-800 text-white font-medium py-2 px-4 md:py-2.5 md:px-6 rounded-lg transition duration-300 inline-flex items-center text-sm">
                        <i class="fas fa-sync-alt mr-2"></i> Try Again
                    </button>
                </div>
            `;
        }

        // ===== INVOICE ACTIONS =====
        function downloadInvoice(invoiceId) {
            window.open(`print-invoice.php?id=${invoiceId}`, '_blank');
        }

        function editInvoice(invoiceId) {
            window.open(`edit-invoice.php?invoice=${invoiceId}`, '_blank');
        }

        function sendInvoiceOptions(invoiceId, email, phone) {
            const modal = document.getElementById('sendModal');
            const content = document.getElementById('sendModalContent');
            
            let html = `
                <div class="space-y-3 md:space-y-4">
                    ${email ? `
                        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between p-3 md:p-4 bg-gray-50 rounded-lg border border-gray-200 gap-2 sm:gap-0">
                            <div class="flex items-center w-full sm:w-auto">
                                <div class="w-8 h-8 md:w-10 md:h-10 bg-gradient-to-r from-purple-100 to-purple-200 rounded-lg flex items-center justify-center mr-3 flex-shrink-0">
                                    <i class="fas fa-envelope text-purple-600"></i>
                                </div>
                                <div>
                                    <div class="font-medium text-gray-800 text-sm">Email</div>
                                    <div class="text-xs text-gray-600 truncate max-w-[150px] sm:max-w-[200px]">${email}</div>
                                </div>
                            </div>
                            <button onclick="sendEmail('${invoiceId}', '${email}')" 
                                    class="bg-purple-600 hover:bg-purple-700 text-white py-1.5 px-4 rounded-lg transition duration-300 text-sm w-full sm:w-auto">
                                Send
                            </button>
                        </div>
                    ` : ''}
                    
                    ${phone ? `
                        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between p-3 md:p-4 bg-gray-50 rounded-lg border border-gray-200 gap-2 sm:gap-0">
                            <div class="flex items-center w-full sm:w-auto">
                                <div class="w-8 h-8 md:w-10 md:h-10 bg-gradient-to-r from-green-100 to-green-200 rounded-lg flex items-center justify-center mr-3 flex-shrink-0">
                                    <i class="fab fa-whatsapp text-green-600"></i>
                                </div>
                                <div>
                                    <div class="font-medium text-gray-800 text-sm">WhatsApp</div>
                                    <div class="text-xs text-gray-600">${phone}</div>
                                </div>
                            </div>
                            <button onclick="sendWhatsApp('${invoiceId}', '${phone}')" 
                                    class="bg-green-600 hover:bg-green-700 text-white py-1.5 px-4 rounded-lg transition duration-300 text-sm w-full sm:w-auto">
                                Send
                            </button>
                        </div>
                    ` : ''}
                    
                    ${!email && !phone ? `
                        <div class="text-center py-6 md:py-8">
                            <i class="fas fa-exclamation-circle text-gray-400 text-3xl md:text-4xl mb-3"></i>
                            <p class="text-gray-600 text-sm">No contact information available.</p>
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
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ invoice_id: invoiceId, email: email, method: 'email' })
                    });
                    const data = await response.json();
                    if (data.success) {
                        alert('Invoice sent via email successfully!');
                        closeSendModal();
                    } else {
                        alert('Error: ' + data.message);
                    }
                } catch (error) {
                    alert('Error: ' + error.message);
                }
            }
        }

        function sendWhatsApp(invoiceId, phone) {
            const invoice = invoices.find(inv => inv.id == invoiceId);
            if (!invoice) { alert('Invoice not found!'); return; }
            const cleanPhone = phone.replace(/[\s\+]/g, '');
            const message = `Hello! Invoice ${invoice.invoice_no} - Amount: ৳${invoice.total_amount.toFixed(2)}\nDownload: ${window.location.origin}/print-invoice.php?id=${invoiceId}`;
            const encodedMessage = encodeURIComponent(message);
            const whatsappUrl = `https://wa.me/${cleanPhone}?text=${encodedMessage}`;
            if (confirm(`Send via WhatsApp to ${phone}?`)) {
                window.open(whatsappUrl, '_blank');
                closeSendModal();
            }
        }

        // ===== MARK AS PAID =====
        let mapCurrentInvoiceId = null;
        let mapAdvanceBalance    = 0;
        let mapDueAmount         = 0;

        async function markAsPaid(invoiceId) {
            mapCurrentInvoiceId = invoiceId;
            const inv = invoices.find(i => i.invoice_no === invoiceId || i.sys_id === invoiceId);
            mapDueAmount = parseFloat(inv?.due_amount || 0);

            const modal = document.getElementById('markAsPaidModal');
            modal.classList.remove('hidden');
            modal.classList.add('flex');

            document.getElementById('mapInvoiceNo').textContent = 'Invoice #' + invoiceId;
            document.getElementById('mapDueAmount').textContent = '৳' + mapDueAmount.toFixed(2);
            document.getElementById('mapCashAmount').value = mapDueAmount.toFixed(2);
            document.getElementById('mapParticular').value = 'Invoice Payment: ' + invoiceId;

            document.getElementById('mapUseAdvance').checked = false;
            document.getElementById('mapAdvanceInputSection').classList.add('hidden');
            document.getElementById('mapAdvanceSection').classList.add('hidden');

            await loadMapAccounts();

            try {
                const r = await fetch(UPDATE_STATUS_API, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ invoice_id: invoiceId, action: 'check_advance' })
                });
                const data = await r.json();
                mapAdvanceBalance = parseFloat(data.advance_balance || 0);

                if (mapAdvanceBalance > 0) {
                    document.getElementById('mapAdvanceSection').classList.remove('hidden');
                    document.getElementById('mapAdvanceBalance').textContent = '৳' + mapAdvanceBalance.toFixed(2);
                    const maxUse = Math.min(mapAdvanceBalance, mapDueAmount);
                    document.getElementById('mapAdvanceMax').textContent = '৳' + maxUse.toFixed(2);
                }
            } catch(e) {
                console.error('Advance check failed:', e);
            }

            updateMapSummary();
        }

        async function loadMapAccounts() {
            const sel = document.getElementById('mapAccount');
            if (sel.options.length > 1) return;
            try {
                const r    = await fetch(`${IP_PATH}/api/accounts/all-accounts.php`);
                const data = await r.json();
                (data.accounts || []).forEach(acc => {
                    const opt = document.createElement('option');
                    opt.value       = acc.sys_id;
                    opt.dataset.name= acc.acc_name;
                    opt.textContent = acc.acc_name;
                    sel.appendChild(opt);
                });
            } catch(e) {}
        }

        function updateMapSummary() {
            const useAdv     = document.getElementById('mapUseAdvance').checked;
            const advInput   = parseFloat(document.getElementById('mapAdvanceAmount').value) || 0;
            const cashInput  = parseFloat(document.getElementById('mapCashAmount').value)    || 0;
            const maxAdvUse  = Math.min(mapAdvanceBalance, mapDueAmount);
            const advUsed    = useAdv ? Math.min(advInput || maxAdvUse, mapAdvanceBalance) : 0;
            const totalPaid  = advUsed + cashInput;
            const remaining  = Math.max(0, mapDueAmount - totalPaid);
            const baksheesh  = Math.max(0, totalPaid - mapDueAmount);

            document.getElementById('mapSumAdvance').textContent  = '৳' + advUsed.toFixed(2);
            document.getElementById('mapSumCash').textContent     = '৳' + cashInput.toFixed(2);
            document.getElementById('mapSumTotal').textContent    = '৳' + totalPaid.toFixed(2);
            document.getElementById('mapSumRemaining').textContent= '৳' + remaining.toFixed(2);

            const bkWarn = document.getElementById('mapBaksheeshWarning');
            const bkAmt  = document.getElementById('mapBaksheeshAmt');
            if (baksheesh > 0.01) {
                bkWarn.classList.remove('hidden');
                bkAmt.textContent = '৳' + baksheesh.toFixed(2);
            } else {
                bkWarn.classList.add('hidden');
            }

            const btn = document.getElementById('mapSubmitBtn');
            if (remaining > 0.01) {
                btn.innerHTML = '<i class="fas fa-check mr-1"></i> Partial Payment';
                btn.className = btn.className.replace('bg-green-600 hover:bg-green-700', 'bg-orange-500 hover:bg-orange-600');
            } else {
                btn.innerHTML = '<i class="fas fa-check mr-1"></i> Mark as Paid';
                btn.className = btn.className.replace('bg-orange-500 hover:bg-orange-600', 'bg-green-600 hover:bg-green-700');
            }
        }

        // Payment method toggle
        document.addEventListener('DOMContentLoaded', () => {
            document.querySelectorAll('.map-method-label').forEach(label => {
                label.addEventListener('click', () => {
                    document.querySelectorAll('.map-method-label').forEach(l => {
                        l.className = l.className.replace('border-green-500 bg-green-50 text-green-700','border-gray-200 text-gray-500');
                    });
                    label.className = label.className.replace('border-gray-200 text-gray-500','border-green-500 bg-green-50 text-green-700');
                    const method = label.dataset.val;
                    const isInstr = ['cheque','bftn-eft'].includes(method);
                    document.getElementById('mapAccountSection')?.classList.remove('hidden');
                    const accLabel = document.querySelector('#mapAccountSection label');
                    if (accLabel) {
                        accLabel.textContent = isInstr ? 'Deposit Account (clear হলে)' : 'Deposit Account';
                    }
                    document.getElementById('mapChequeFields')?.classList.toggle('hidden', method !== 'cheque');
                    document.getElementById('mapBftnFields')?.classList.toggle('hidden', method !== 'bftn-eft');
                });
            });
        });

        function getMapMethod() {
            return document.querySelector('input[name="mapPayMethod"]:checked')?.value || 'cash';
        }

        document.addEventListener('DOMContentLoaded', () => {
            document.getElementById('mapUseAdvance')?.addEventListener('change', function() {
                const sec = document.getElementById('mapAdvanceInputSection');
                sec.classList.toggle('hidden', !this.checked);
                if (this.checked) {
                    const maxUse = Math.min(mapAdvanceBalance, mapDueAmount);
                    document.getElementById('mapAdvanceAmount').value = maxUse.toFixed(2);
                    document.getElementById('mapCashAmount').value = Math.max(0, mapDueAmount - maxUse).toFixed(2);
                }
                updateMapSummary();
            });
            document.getElementById('mapAdvanceAmount')?.addEventListener('input', () => {
                const advVal = parseFloat(document.getElementById('mapAdvanceAmount').value) || 0;
                const maxAdv = Math.min(mapAdvanceBalance, mapDueAmount);
                const clamped = Math.min(advVal, maxAdv);
                const cashEl = document.getElementById('mapCashAmount');
                if (cashEl) cashEl.value = Math.max(0, mapDueAmount - clamped).toFixed(2);
                updateMapSummary();
            });
            document.getElementById('mapCashAmount')?.addEventListener('input', updateMapSummary);
        });

        function closeMarkAsPaidModal() {
            const modal = document.getElementById('markAsPaidModal');
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }

        async function submitMarkAsPaid() {
            const useAdv      = document.getElementById('mapUseAdvance').checked;
            const advInput    = parseFloat(document.getElementById('mapAdvanceAmount').value) || 0;
            const cashInput   = parseFloat(document.getElementById('mapCashAmount').value)    || 0;
            const accountSel  = document.getElementById('mapAccount');
            const accountId   = accountSel?.value || '';
            const accountName = accountSel?.options[accountSel.selectedIndex]?.dataset.name || '';
            const date        = document.getElementById('mapDate').value;
            const particular  = document.getElementById('mapParticular').value || ('Invoice Payment: ' + mapCurrentInvoiceId);
            const method      = getMapMethod();
            const isInstrument= ['cheque','bftn-eft'].includes(method);
            const withDiscount= document.getElementById('mapWithDiscount')?.checked || false;
            const discAmount  = parseFloat(document.getElementById('mapDiscountAmount')?.value) || 0;
            const discPart    = document.getElementById('mapDiscountParticular')?.value || '';

            const maxAdvUse = Math.min(mapAdvanceBalance, mapDueAmount);
            const advUsed   = useAdv ? Math.min(advInput || maxAdvUse, mapAdvanceBalance) : 0;
            const totalPaid = advUsed + cashInput + (withDiscount ? discAmount : 0);

            if (totalPaid <= 0) { alert('Amount দিন'); return; }
            if (!isInstrument && cashInput > 0 && !accountId) { alert('Deposit Account select করুন'); return; }
            if (isInstrument && !accountId) { alert('Deposit Account select করুন'); return; }

            let overpaymentAction = 'advance';
            if (cashInput + advUsed > mapDueAmount + 0.009) {
                const overpay = (cashInput + advUsed - mapDueAmount).toFixed(2);
                const choice  = await showMapOverpayModal(overpay);
                if (choice === null) return;
                overpaymentAction = choice;
            }

            const btn = document.getElementById('mapSubmitBtn');
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i> Processing...';

            try {
                const payload = {
                    invoice_id        : mapCurrentInvoiceId,
                    action            : 'pay',
                    cash_amount       : cashInput,
                    use_advance       : useAdv ? 1 : 0,
                    advance_amount    : advUsed,
                    account_id        : accountId,
                    account_name      : accountName,
                    date              : date + ' ' + new Date().toTimeString().slice(0,8),
                    particular        : particular,
                    transfer_method   : method,
                    overpayment_action: overpaymentAction,
                    with_discount     : withDiscount,
                    discount_amount   : withDiscount ? discAmount : 0,
                    discount_particular: withDiscount ? discPart : '',
                };

                if (method === 'cheque') {
                    payload.chequeNo          = document.getElementById('mapChequeNo')?.value;
                    payload.chequeDate        = document.getElementById('mapChequeDate')?.value;
                    payload.chequeAccountName = document.getElementById('mapChequeAccName')?.value;
                    payload.bankName          = document.getElementById('mapChequeBankName')?.value;
                }
                if (method === 'bftn-eft') {
                    payload.bftnNo          = document.getElementById('mapBftnNo')?.value;
                    payload.bftnDate        = document.getElementById('mapBftnDate')?.value;
                    payload.bftnAccountName = document.getElementById('mapBftnAccName')?.value;
                    payload.eftBankName     = document.getElementById('mapBftnBankName')?.value;
                }

                const res  = await fetch(UPDATE_STATUS_API, {
                    method:'POST', headers:{'Content-Type':'application/json'}, body:JSON.stringify(payload)
                });
                const data = await res.json();

                if (data.success) {
                    const files = document.getElementById('mapFiles')?.files;
                    if (files && files.length > 0) {
                        const fd = new FormData();
                        fd.append('entity_type', 'invoice');
                        fd.append('entity_id',   mapCurrentInvoiceId);
                        fd.append('entity_name', invoices.find(i=>i.invoice_no===mapCurrentInvoiceId)?.client_name || 'client');
                        for (const f of files) fd.append('files[]', f);
                        fetch(`${IP_PATH}/api/finance/upload-file.php`, { method:'POST', body:fd });
                    }
                    closeMarkAsPaidModal();
                    await loadData();
                    showToast(data.message || 'Payment recorded', 'success');
                } else {
                    alert('Error: ' + (data.message || 'Failed'));
                }
            } catch(e) {
                alert('Network error: ' + e.message);
            } finally {
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-check mr-1"></i> Mark as Paid';
            }
        }

        function showMapOverpayModal(overpayAmt) {
            return new Promise(resolve => {
                const existing = document.getElementById('mapOverpayModal');
                if (existing) existing.remove();
                const modal = document.createElement('div');
                modal.id = 'mapOverpayModal';
                modal.className = 'fixed inset-0 bg-black/60 z-[9999] flex items-center justify-center p-4';
                modal.innerHTML = `
                    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-sm p-5 md:p-6">
                        <h3 class="text-base font-bold text-gray-900 mb-2">
                            <i class="fas fa-exclamation-triangle text-orange-500 mr-2"></i> Overpayment
                        </h3>
                        <p class="text-sm text-gray-600 mb-4">৳${overpayAmt} বাড়তি। কী হিসেবে রাখবো?</p>
                        <div class="grid grid-cols-2 gap-3">
                            <button onclick="document.getElementById('mapOverpayModal').dataset.choice='advance'"
                                class="py-2.5 px-4 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold rounded-xl text-sm">
                                <i class="fas fa-piggy-bank mr-1"></i> Advance
                            </button>
                            <button onclick="document.getElementById('mapOverpayModal').dataset.choice='baksheesh'"
                                class="py-2.5 px-4 bg-pink-600 hover:bg-pink-700 text-white font-semibold rounded-xl text-sm">
                                <i class="fas fa-gift mr-1"></i> Baksheesh
                            </button>
                        </div>
                        <button onclick="document.getElementById('mapOverpayModal').dataset.choice='cancel'"
                            class="w-full mt-3 py-2 text-xs text-gray-400 hover:text-gray-600">Cancel</button>
                    </div>`;
                document.body.appendChild(modal);
                const observer = new MutationObserver(() => {
                    const choice = modal.dataset.choice;
                    if (choice) { observer.disconnect(); modal.remove(); resolve(choice==='cancel'?null:choice); }
                });
                observer.observe(modal, { attributes: true });
            });
        }

        async function shareInvoicePdf(invoiceNo) {
            const pdfUrl = `${IP_PATH}/pages/print-invoice.php?id=${invoiceNo}`;
            const isHttps = location.protocol === 'https:';
            if (!isHttps) {
                window.open(pdfUrl, '_blank');
                showToast('PDF opened in new tab', 'info');
                return;
            }

            const btn = document.querySelector(`button[onclick="shareInvoicePdf('${invoiceNo}')"]`);
            const originalHtml = btn?.innerHTML;
            if (btn) btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>';

            try {
                const response = await fetch(pdfUrl, { credentials: 'include' });
                const contentType = response.headers.get('Content-Type') || '';
                if (!response.ok || !contentType.includes('application/pdf')) {
                    window.open(pdfUrl, '_blank');
                    showToast('PDF opened in new tab', 'info');
                    return;
                }

                const blob = await response.blob();
                const file = new File([blob], `Invoice-${invoiceNo}.pdf`, { type: 'application/pdf' });

                if (navigator.canShare && navigator.canShare({ files: [file] })) {
                    await navigator.share({ title: `Invoice ${invoiceNo}`, files: [file] });
                } else {
                    const url = URL.createObjectURL(blob);
                    const a = document.createElement('a');
                    a.href = url;
                    a.download = `Invoice-${invoiceNo}.pdf`;
                    a.click();
                    URL.revokeObjectURL(url);
                    showToast('PDF downloaded', 'success');
                }
            } catch(e) {
                if (e.name !== 'AbortError') {
                    window.open(pdfUrl, '_blank');
                }
            } finally {
                if (btn && originalHtml) btn.innerHTML = originalHtml;
            }
        }

        function showToast(msg, type='success') {
            const t = document.createElement('div');
            t.className = `fixed bottom-4 right-4 md:bottom-6 md:right-6 z-50 px-4 py-2.5 md:px-5 md:py-3 rounded-xl shadow-lg text-white text-sm font-medium
                ${type === 'success' ? 'bg-green-600' : type === 'info' ? 'bg-blue-600' : 'bg-red-600'}`;
            t.innerHTML = `<i class="fas fa-${type === 'success' ? 'check-circle' : type === 'info' ? 'info-circle' : 'exclamation-circle'} mr-2"></i>${msg}`;
            document.body.appendChild(t);
            setTimeout(() => t.remove(), 4000);
        }

        document.getElementById('markAsPaidModal')?.addEventListener('click', function(e) {
            if (e.target === this) closeMarkAsPaidModal();
        });

        let resizeTimeout;
        window.addEventListener('resize', () => {
            clearTimeout(resizeTimeout);
            resizeTimeout = setTimeout(() => {
                if (invoices.length > 0) renderCharts();
            }, 250);
        });
    </script>

<!-- ==================== MARK AS PAID MODAL ==================== -->
<div id="markAsPaidModal">
    <div class="modal-box">
        <!-- Header -->
        <div class="modal-header">
            <div>
                <h3 class="text-base md:text-lg font-bold text-gray-900">
                    <i class="fas fa-check-circle text-green-500 mr-2"></i> Mark as Paid
                </h3>
                <p class="text-xs text-gray-500 mt-0.5" id="mapInvoiceNo">Invoice #</p>
            </div>
            <button onclick="closeMarkAsPaidModal()" class="close-btn w-8 h-8 md:w-10 md:h-10 flex items-center justify-center rounded-full bg-gray-100 hover:bg-gray-200 text-gray-500 flex-shrink-0">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <div class="space-y-3 md:space-y-4">
            <!-- Due Info -->
            <div class="bg-orange-50 border border-orange-200 rounded-xl p-3 md:p-4 flex items-center justify-between">
                <div>
                    <p class="text-xs text-orange-600">Invoice Due</p>
                    <p id="mapDueAmount" class="text-xl md:text-2xl font-bold text-orange-700">৳0.00</p>
                </div>
                <i class="fas fa-file-invoice-dollar text-orange-300 text-2xl md:text-3xl"></i>
            </div>

            <!-- Advance Section -->
            <div id="mapAdvanceSection" class="bg-indigo-50 border border-indigo-200 rounded-xl p-3 md:p-4">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 mb-3">
                    <div>
                        <p class="text-xs text-indigo-600">Client Advance Balance</p>
                        <p id="mapAdvanceBalance" class="text-lg md:text-xl font-bold text-indigo-700">৳0.00</p>
                    </div>
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" id="mapUseAdvance" class="w-4 h-4 text-indigo-600 rounded">
                        <span class="text-sm font-semibold text-indigo-700">Use Advance</span>
                    </label>
                </div>
                <div id="mapAdvanceInputSection" class="hidden">
                    <label class="block text-xs text-indigo-600 mb-1">
                        Use Amount <span class="text-indigo-400">(max: <span id="mapAdvanceMax">৳0</span>)</span>
                    </label>
                    <input type="number" id="mapAdvanceAmount" step="0.01" min="0" placeholder="0.00"
                        class="w-full px-3 py-2 border border-indigo-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-400 bg-white font-semibold">
                </div>
            </div>

            <!-- Payment Method -->
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1.5">Payment Method</label>
                <div class="payment-method-grid">
                    <label class="map-method-label" data-val="cash">
                        <input type="radio" name="mapPayMethod" value="cash" class="hidden" checked>
                        <i class="fas fa-money-bill-wave text-sm md:text-base"></i>
                        <span class="block text-[10px] md:text-xs">Cash</span>
                    </label>
                    <label class="map-method-label" data-val="mfs">
                        <input type="radio" name="mapPayMethod" value="mfs" class="hidden">
                        <i class="fas fa-mobile-alt text-sm md:text-base"></i>
                        <span class="block text-[10px] md:text-xs">MFS</span>
                    </label>
                    <label class="map-method-label" data-val="npsb">
                        <input type="radio" name="mapPayMethod" value="npsb" class="hidden">
                        <i class="fas fa-network-wired text-sm md:text-base"></i>
                        <span class="block text-[10px] md:text-xs">NPSB</span>
                    </label>
                    <label class="map-method-label" data-val="cheque">
                        <input type="radio" name="mapPayMethod" value="cheque" class="hidden">
                        <i class="fas fa-file-alt text-sm md:text-base"></i>
                        <span class="block text-[10px] md:text-xs">Cheque</span>
                    </label>
                    <label class="map-method-label" data-val="bftn-eft">
                        <input type="radio" name="mapPayMethod" value="bftn-eft" class="hidden">
                        <i class="fas fa-university text-sm md:text-base"></i>
                        <span class="block text-[10px] md:text-xs">BFTN</span>
                    </label>
                </div>
            </div>

            <!-- Cheque fields -->
            <div id="mapChequeFields" class="hidden bg-yellow-50 border border-yellow-200 rounded-lg p-3 grid grid-cols-2 gap-2 text-xs">
                <div><label class="text-gray-600">Cheque No</label><input type="text" id="mapChequeNo" class="w-full px-2 py-1.5 border rounded mt-1"></div>
                <div><label class="text-gray-600">Date</label><input type="date" id="mapChequeDate" class="w-full px-2 py-1.5 border rounded mt-1"></div>
                <div class="col-span-2"><label class="text-gray-600">Account Name</label><input type="text" id="mapChequeAccName" class="w-full px-2 py-1.5 border rounded mt-1"></div>
                <div class="col-span-2"><label class="text-gray-600">Bank</label><input type="text" id="mapChequeBankName" class="w-full px-2 py-1.5 border rounded mt-1"></div>
            </div>

            <!-- BFTN fields -->
            <div id="mapBftnFields" class="hidden bg-blue-50 border border-blue-200 rounded-lg p-3 grid grid-cols-2 gap-2 text-xs">
                <div class="col-span-2"><label class="text-gray-600">Ref No</label><input type="text" id="mapBftnNo" class="w-full px-2 py-1.5 border rounded mt-1"></div>
                <div><label class="text-gray-600">Date</label><input type="date" id="mapBftnDate" class="w-full px-2 py-1.5 border rounded mt-1"></div>
                <div class="col-span-2"><label class="text-gray-600">Account Name</label><input type="text" id="mapBftnAccName" class="w-full px-2 py-1.5 border rounded mt-1"></div>
                <div class="col-span-2"><label class="text-gray-600">Bank</label><input type="text" id="mapBftnBankName" class="w-full px-2 py-1.5 border rounded mt-1"></div>
            </div>

            <!-- Amount / Date / Account -->
            <div class="space-y-3">
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Amount</label>
                        <input type="number" id="mapCashAmount" step="0.01" min="0" placeholder="0.00"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-green-400">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Date</label>
                        <input type="date" id="mapDate" value="<?php echo date('Y-m-d'); ?>"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-green-400">
                    </div>
                </div>
                <div id="mapAccountSection">
                    <label class="block text-xs font-medium text-gray-600 mb-1">Deposit Account</label>
                    <select id="mapAccount"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-green-400">
                        <option value="">-- Select Account --</option>
                    </select>
                </div>

                <!-- Discount -->
                <div class="bg-orange-50 border border-orange-200 rounded-lg p-2 md:p-3">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" id="mapWithDiscount" class="w-3.5 h-3.5 text-orange-500 rounded" onchange="document.getElementById('mapDiscountFields').classList.toggle('hidden',!this.checked);updateMapSummary()">
                        <span class="text-xs font-medium text-orange-700">Discount দিয়ে close করবো</span>
                    </label>
                    <div id="mapDiscountFields" class="hidden grid grid-cols-2 gap-2 mt-2">
                        <div><label class="text-xs text-gray-600">Amount</label>
                            <input type="number" id="mapDiscountAmount" step="0.01" placeholder="0.00" oninput="updateMapSummary()" class="w-full px-2 py-1.5 border border-orange-300 rounded text-sm mt-1"></div>
                        <div><label class="text-xs text-gray-600">Reason</label>
                            <input type="text" id="mapDiscountParticular" placeholder="Reason" class="w-full px-2 py-1.5 border border-orange-300 rounded text-sm mt-1"></div>
                    </div>
                </div>

                <!-- File Upload -->
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">
                        <i class="fas fa-paperclip mr-1"></i> Attach Files
                    </label>
                    <input type="file" id="mapFiles" multiple accept=".pdf,.jpg,.jpeg,.png,.doc,.docx"
                        class="w-full text-xs text-gray-600 file:mr-2 file:py-1 file:px-2 file:rounded file:border-0 file:text-xs file:bg-green-50 file:text-green-700">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Particular</label>
                    <input type="text" id="mapParticular" placeholder="Payment note..."
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-green-400">
                </div>
            </div>

            <!-- Summary -->
            <div id="mapSummary" class="bg-gray-50 border border-gray-200 rounded-xl p-3 md:p-4 space-y-2 text-sm">
                <div class="flex justify-between">
                    <span class="text-gray-500 text-xs md:text-sm">Advance Used:</span>
                    <span id="mapSumAdvance" class="font-medium text-indigo-600 text-xs md:text-sm">৳0.00</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-500 text-xs md:text-sm">Cash/Cheque:</span>
                    <span id="mapSumCash" class="font-medium text-green-600 text-xs md:text-sm">৳0.00</span>
                </div>
                <div class="flex justify-between border-t border-gray-200 pt-2">
                    <span class="font-semibold text-gray-700 text-xs md:text-sm">Total Payment:</span>
                    <span id="mapSumTotal" class="font-bold text-gray-900 text-xs md:text-sm">৳0.00</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-500 text-xs md:text-sm">Remaining Due:</span>
                    <span id="mapSumRemaining" class="font-bold text-orange-600 text-xs md:text-sm">৳0.00</span>
                </div>
                <div id="mapBaksheeshWarning" class="hidden text-xs text-pink-600 bg-pink-50 rounded p-2 mt-1">
                    <i class="fas fa-info-circle mr-1"></i>
                    <span id="mapBaksheeshAmt"></span> বাড়তি — Baksheesh হিসেবে record হবে
                </div>
            </div>
        </div>

        <!-- Footer -->
        <div class="flex flex-col sm:flex-row justify-end gap-2 mt-4 md:mt-6 pt-3 md:pt-4 border-t border-gray-100">
            <button onclick="closeMarkAsPaidModal()"
                class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm rounded-lg order-2 sm:order-1">
                Cancel
            </button>
            <button onclick="submitMarkAsPaid()" id="mapSubmitBtn"
                class="px-5 py-2 bg-green-600 hover:bg-green-700 text-white text-sm font-semibold rounded-lg flex items-center justify-center gap-2 order-1 sm:order-2">
                <i class="fas fa-check"></i> Mark as Paid
            </button>
        </div>
    </div>
</div>

</body>
</html>