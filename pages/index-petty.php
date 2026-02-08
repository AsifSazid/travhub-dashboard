<?php
include_once('./authenticate.php');
// Get IP path
$ip_port = @file_get_contents('../ippath.txt');
if (empty($ip_port)) {
    $ip_port = "http://103.104.219.3:898";
}
$base_ip_path = trim($ip_port, "/");

$allPettyCash = $ip_port . "/api/petty-cash/all-petty-cash.php";

$type = $_GET['type'] ?? '';

if (!empty($type)) {
    $formattedType = str_replace('_', ' ', $type);
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo ($type ? strtoupper($formattedType) : 'PETTY CASH') ; ?> - Petty Cash Management</title>
    <link rel="icon" type="image/png" href="../assets/images/logo/round-logo.png" sizes="16x16">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .petty-card {
            transition: all 0.3s ease;
            border-left: 4px solid transparent;
            cursor: pointer;
        }

        .petty-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
        }

        .type-badge {
            font-size: 0.75rem;
            padding: 0.35rem 0.85rem;
            border-radius: 9999px;
            font-weight: 600;
            letter-spacing: 0.3px;
        }

        .type-conveyance {
            background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
            color: white;
            box-shadow: 0 2px 5px rgba(59, 130, 246, 0.2);
        }

        .type-other {
            background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%);
            color: white;
            box-shadow: 0 2px 5px rgba(139, 92, 246, 0.2);
        }

        .type-loan {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
            box-shadow: 0 2px 5px rgba(16, 185, 129, 0.2);
        }

        .type-petty {
            background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
            color: white;
            box-shadow: 0 2px 5px rgba(245, 158, 11, 0.2);
        }

        .amount-badge {
            font-size: 0.9rem;
            padding: 0.4rem 0.9rem;
            border-radius: 8px;
            font-weight: 600;
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

        /* Quick Type Filter */
        .type-filter-btn {
            padding: 0.5rem 1rem;
            border-radius: 0.5rem;
            font-weight: 500;
            transition: all 0.2s;
        }
        
        .type-filter-btn.active {
            transform: scale(1.05);
        }
        
        /* Status Dot */
        .status-dot {
            display: inline-block;
            width: 8px;
            height: 8px;
            border-radius: 50%;
            margin-right: 5px;
        }
    </style>
</head>

<body class="bg-gray-50 font-sans">
    <!-- Top Navigation -->
    <?php include '../elements/header.php'; ?>

    <!-- Sidebar -->
    <?php include '../elements/aside.php'; ?>

    <!-- Main Content -->
    <main id="mainContent" class="pt-16 pl-0 lg:pl-64 lg:my-16 transition-all duration-300">
        <div class="p-4 md:p-6 h-full">
            <!-- Header -->
            <div class="mb-6">
                <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                    <div>
                        <h1 class="text-2xl md:text-3xl font-bold text-gray-800">
                            <?php echo ($type ? ucfirst($formattedType) : 'Petty Cash') ; ?> Management
                        </h1>
                        <p class="text-gray-600 mt-2">Manage and track all petty cash transactions in one place</p>
                    </div>
                    <div class="flex items-center gap-3">
                        <button id="refresh-btn" class="bg-white hover:bg-gray-50 text-gray-700 font-medium py-2.5 px-4 rounded-lg transition duration-300 flex items-center border border-gray-300">
                            <i class="fas fa-sync-alt mr-2"></i> Refresh
                        </button>
                        <a href="create-petty.php<?php if($type) echo '?type=' . $type ?>" class="bg-gradient-to-r from-green-600 to-emerald-700 hover:from-green-700 hover:to-emerald-800 text-white font-medium py-2.5 px-5 rounded-lg transition duration-300 flex items-center shadow">
                            <i class="fas fa-plus mr-2"></i> Create New <?php echo ($type ? ucfirst($formattedType) : 'Entry') ; ?>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Quick Type Filters -->
            <div class="mb-6 bg-white rounded-xl shadow-sm p-4 border border-gray-200">
                <div class="flex flex-wrap gap-2">
                    <button class="type-filter-btn bg-gradient-to-r from-blue-500 to-blue-600 text-white" data-type="all">
                        <i class="fas fa-layer-group mr-2"></i> All Entries
                    </button>
                    <button class="type-filter-btn bg-gradient-to-r from-blue-400 to-blue-500 text-white" data-type="conveyance_bill">
                        <i class="fas fa-car mr-2"></i> Conveyance
                    </button>
                    <button class="type-filter-btn bg-gradient-to-r from-purple-400 to-purple-500 text-white" data-type="other_bill">
                        <i class="fas fa-receipt mr-2"></i> Other Bills
                    </button>
                    <button class="type-filter-btn bg-gradient-to-r from-green-500 to-green-600 text-white" data-type="loan">
                        <i class="fas fa-hand-holding-usd mr-2"></i> Loans
                    </button>
                    <button class="type-filter-btn bg-gradient-to-r from-amber-500 to-amber-600 text-white" data-type="petty_cash">
                        <i class="fas fa-money-bill-wave mr-2"></i> Petty Cash
                    </button>
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
                        <label class="block text-sm font-medium text-gray-700 mb-2">Type</label>
                        <select id="filter-type" class="w-full bg-white border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            <option value="">All Types</option>
                            <option value="conveyance_bill">Conveyance Bill</option>
                            <option value="other_bill">Other Bill</option>
                            <option value="loan">Loan</option>
                            <option value="petty_cash">Petty Cash</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Date Range</label>
                        <select id="filter-date" class="w-full bg-white border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            <option value="">All Time</option>
                            <option value="today">Today</option>
                            <option value="week">This Week</option>
                            <option value="month">This Month</option>
                            <option value="quarter">This Quarter</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">User</label>
                        <input type="text" id="filter-user" placeholder="Search user..." class="w-full bg-white border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Amount Range</label>
                        <div class="flex gap-2">
                            <input type="number" id="filter-amount-min" placeholder="Min" class="w-1/2 bg-white border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            <input type="number" id="filter-amount-max" placeholder="Max" class="w-1/2 bg-white border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Petty Cash Entries List -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="p-5 border-b border-gray-200 bg-gray-50">
                    <h3 class="text-lg font-semibold text-gray-800">All Entries</h3>
                    <div class="text-sm text-gray-600 mt-1">
                        Showing <span id="showing-count">0</span> of <span id="total-count">0</span> entries
                    </div>
                </div>

                <div id="entries-container" class="p-4 min-h-[400px]">
                    <!-- Skeleton Loaders -->
                    <div id="skeleton-loaders" class="space-y-4">
                        <?php for($i = 0; $i < 5; $i++): ?>
                        <div class="skeleton-card skeleton-loader"></div>
                        <?php endfor; ?>
                    </div>

                    <!-- Empty State -->
                    <div id="empty-state" class="empty-state" style="display: none;">
                        <div class="empty-state-icon">
                            <i class="fas fa-money-bill-wave"></i>
                        </div>
                        <h4 class="text-xl font-semibold text-gray-600 mb-2">No entries found</h4>
                        <p class="text-gray-500 mb-6 max-w-md mx-auto">Start by creating your first petty cash entry for conveyance, bills, loans or cash transactions.</p>
                        <a href="create-petty.php" class="bg-gradient-to-r from-green-600 to-emerald-700 hover:from-green-700 hover:to-emerald-800 text-white font-medium py-2.5 px-6 rounded-lg transition duration-300 inline-flex items-center">
                            <i class="fas fa-plus mr-2"></i> Create First Entry
                        </a>
                    </div>

                    <!-- Entries will be loaded here -->
                </div>

                <!-- Infinite Scroll Loader -->
                <div id="infinite-loader" class="infinite-loader">
                    <div class="loader-spinner"></div>
                    <p class="text-gray-500 mt-2">Loading more entries...</p>
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

    <!-- Entry Details Modal -->
    <div id="detailsModal" class="modal-overlay">
        <div class="modal-content">
            <div class="p-6 border-b border-gray-200">
                <h3 class="text-xl font-bold text-gray-800">Entry Details</h3>
            </div>
            <div class="p-6" id="detailsModalContent">
                <!-- Content loaded dynamically -->
            </div>
            <div class="p-6 border-t border-gray-200 bg-gray-50 flex justify-end">
                <button onclick="closeDetailsModal()" class="bg-gray-200 hover:bg-gray-300 text-gray-800 font-medium py-2.5 px-6 rounded-lg transition duration-300">
                    Close
                </button>
            </div>
        </div>
    </div>

    <?php include '../elements/floating-menus.php'; ?>

    <script src="../assets/js/script.js?time=<?php echo time(); ?>"></script>
    <script>
        // Configuration
        const IP_PATH = '<?php echo htmlspecialchars($base_ip_path); ?>';
        const API_URL = "<?php echo $allPettyCash; ?>";
        const DELETE_API = `${IP_PATH}/api/petty_cash/delete.php`;
        const ITEMS_PER_PAGE = 10;
        const INFINITE_SCROLL_THRESHOLD = 100; // pixels from bottom
        const DEBOUNCE_DELAY = 300; // milliseconds

        // State management
        let entries = [];
        let filteredEntries = [];
        let currentPage = 1;
        let isLoading = false;
        let hasMore = true;
        let searchTimeout;
        let isInfiniteScroll = true;
        let currentFilterType = 'all';

        // DOM Elements
        const elements = {
            container: document.getElementById('entries-container'),
            skeleton: document.getElementById('skeleton-loaders'),
            emptyState: document.getElementById('empty-state'),
            infiniteLoader: document.getElementById('infinite-loader'),
            pagination: document.getElementById('pagination'),
            showingCount: document.getElementById('showing-count'),
            totalCount: document.getElementById('total-count'),
            chartsSection: document.getElementById('chartsSection'),
            statsCards: document.getElementById('statsCards'),
            filters: {
                type: document.getElementById('filter-type'),
                date: document.getElementById('filter-date'),
                user: document.getElementById('filter-user'),
                amountMin: document.getElementById('filter-amount-min'),
                amountMax: document.getElementById('filter-amount-max')
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
            // Quick type filter buttons
            document.querySelectorAll('.type-filter-btn').forEach(btn => {
                btn.addEventListener('click', function() {
                    const type = this.dataset.type;
                    currentFilterType = type;
                    
                    // Update active button
                    document.querySelectorAll('.type-filter-btn').forEach(b => {
                        b.classList.remove('active');
                    });
                    this.classList.add('active');
                    
                    // Update type filter dropdown
                    elements.filters.type.value = type === 'all' ? '' : type;
                    
                    // Apply filter
                    debouncedFilter();
                });
            });

            // Filter listeners with debouncing
            elements.filters.type.addEventListener('change', () => debouncedFilter());
            elements.filters.date.addEventListener('change', () => debouncedFilter());
            elements.filters.user.addEventListener('input', () => debouncedFilter());
            elements.filters.amountMin.addEventListener('input', () => debouncedFilter());
            elements.filters.amountMax.addEventListener('input', () => debouncedFilter());

            // Refresh button
            document.getElementById('refresh-btn').addEventListener('click', async () => {
                await loadData();
            });

            // Type parameter from URL
            const urlParams = new URLSearchParams(window.location.search);
            const typeParam = urlParams.get('type');
            if (typeParam) {
                elements.filters.type.value = typeParam;
                const filterBtn = document.querySelector(`[data-type="${typeParam}"]`);
                if (filterBtn) {
                    filterBtn.click();
                }
            }
        }

        // Debounce function for search/filter
        function debouncedFilter() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                currentPage = 1;
                filterEntries();
                renderEntries();
            }, DEBOUNCE_DELAY);
        }

        // Load data from API
        async function loadData() {
            try {
                showSkeleton();
                const response = await fetch(API_URL);
                const data = await response.json();
                
                entries = data.entries || data.petty_cashes || data.data?.entries || [];
                filteredEntries = [...entries];
                
                console.log(data.data?.entries);
                
                updateStats();
                renderCharts();
                renderEntries();
                hideSkeleton();
            } catch (error) {
                console.error('Error loading entries:', error);
                showError('Failed to load entries. Please try again.');
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

        // Filter entries based on criteria
        function filterEntries() {
            const typeFilter = elements.filters.type.value;
            const dateFilter = elements.filters.date.value;
            const userFilter = elements.filters.user.value.toLowerCase();
            const amountMin = parseFloat(elements.filters.amountMin.value) || 0;
            const amountMax = parseFloat(elements.filters.amountMax.value) || Infinity;

            filteredEntries = entries.filter(entry => {
                // Type filter
                if (typeFilter && entry.type !== typeFilter) return false;

                // Date filter
                if (dateFilter) {
                    const entryDate = new Date(entry.date);
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

                    if (entryDate < startDate) return false;
                }

                // User filter
                if (userFilter && 
                    !entry.user_name?.toLowerCase().includes(userFilter) && 
                    !entry.to_user_name?.toLowerCase().includes(userFilter)) {
                    return false;
                }

                // Amount range filter
                const amount = parseFloat(entry.amount);
                if (amount < amountMin || amount > amountMax) return false;

                return true;
            });

            updateStats();
        }

        // Update statistics
        function updateStats() {
            const totalEntries = filteredEntries.length;
            const totalAmount = filteredEntries.reduce((sum, entry) => sum + parseFloat(entry.amount), 0);
            
            // Calculate amounts by type
            const conveyanceAmount = filteredEntries
                .filter(entry => entry.type === 'conveyance_bill')
                .reduce((sum, entry) => sum + parseFloat(entry.amount), 0);
            
            const otherBillAmount = filteredEntries
                .filter(entry => entry.type === 'other_bill')
                .reduce((sum, entry) => sum + parseFloat(entry.amount), 0);
            
            const loanAmount = filteredEntries
                .filter(entry => entry.type === 'loan')
                .reduce((sum, entry) => sum + parseFloat(entry.amount), 0);
            
            const pettyAmount = filteredEntries
                .filter(entry => entry.type === 'petty_cash')
                .reduce((sum, entry) => sum + parseFloat(entry.amount), 0);
            
            // Format currency function
            const formatCurrency = (amount) => {
                return `৳${amount.toLocaleString('en-IN', { 
                    minimumFractionDigits: 2, 
                    maximumFractionDigits: 2 
                })}`;
            };

            // Count by type
            const conveyanceCount = filteredEntries.filter(entry => entry.type === 'conveyance_bill').length;
            const otherCount = filteredEntries.filter(entry => entry.type === 'other_bill').length;
            const loanCount = filteredEntries.filter(entry => entry.type === 'loan').length;
            const pettyCount = filteredEntries.filter(entry => entry.type === 'petty_cash').length;
        
            elements.statsCards.innerHTML = `
                <div class="bg-white rounded-xl shadow-sm p-5 border border-gray-200 hover:shadow-md transition-shadow">
                    <div class="flex items-center">
                        <div class="w-12 h-12 bg-gradient-to-r from-blue-100 to-blue-200 rounded-lg flex items-center justify-center mr-4">
                            <i class="fas fa-money-bill-wave text-blue-600 text-xl"></i>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500 font-medium">Total Entries</p>
                            <h3 class="text-2xl font-bold text-gray-800">${totalEntries}</h3>
                        </div>
                    </div>
                    <div class="mt-3 text-sm text-gray-600">
                        <i class="fas fa-coins mr-1"></i>
                        Total Amount: ${formatCurrency(totalAmount)}
                    </div>
                </div>
        
                <div class="bg-white rounded-xl shadow-sm p-5 border border-gray-200 hover:shadow-md transition-shadow">
                    <div class="flex items-center">
                        <div class="w-12 h-12 bg-gradient-to-r from-green-100 to-green-200 rounded-lg flex items-center justify-center mr-4">
                            <i class="fas fa-car text-green-600 text-xl"></i>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500 font-medium">Conveyance</p>
                            <h3 class="text-2xl font-bold text-gray-800">
                                ${formatCurrency(conveyanceAmount)}
                            </h3>
                        </div>
                    </div>
                    <div class="mt-3 text-sm text-gray-600">
                        <i class="fas fa-list-ol mr-1"></i>
                        ${conveyanceCount} entries
                    </div>
                </div>
        
                <div class="bg-white rounded-xl shadow-sm p-5 border border-gray-200 hover:shadow-md transition-shadow">
                    <div class="flex items-center">
                        <div class="w-12 h-12 bg-gradient-to-r from-purple-100 to-purple-200 rounded-lg flex items-center justify-center mr-4">
                            <i class="fas fa-hand-holding-usd text-purple-600 text-xl"></i>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500 font-medium">Loans</p>
                            <h3 class="text-2xl font-bold text-gray-800">
                                ${formatCurrency(loanAmount)}
                            </h3>
                        </div>
                    </div>
                    <div class="mt-3 text-sm text-gray-600">
                        <i class="fas fa-users mr-1"></i>
                        ${loanCount} loans given
                    </div>
                </div>
        
                <div class="bg-white rounded-xl shadow-sm p-5 border border-gray-200 hover:shadow-md transition-shadow">
                    <div class="flex items-center">
                        <div class="w-12 h-12 bg-gradient-to-r from-amber-100 to-amber-200 rounded-lg flex items-center justify-center mr-4">
                            <i class="fas fa-cash-register text-amber-600 text-xl"></i>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500 font-medium">Petty Cash</p>
                            <h3 class="text-2xl font-bold text-gray-800">
                                ${formatCurrency(pettyAmount)}
                            </h3>
                        </div>
                    </div>
                    <div class="mt-3 text-sm text-gray-600">
                        <i class="fas fa-receipt mr-1"></i>
                        ${pettyCount} cash entries
                    </div>
                </div>
            `;
        
            elements.showingCount.textContent = Math.min(totalEntries, currentPage * ITEMS_PER_PAGE);
            elements.totalCount.textContent = totalEntries;
        }

        // Render charts for petty cash entries
        function renderCharts() {
            if (entries.length === 0) return;
        
            // Calculate data by type
            const typeData = entries.reduce((acc, entry) => {
                acc[entry.type] = (acc[entry.type] || 0) + parseFloat(entry.amount);
                return acc;
            }, {});
        
            const countByType = entries.reduce((acc, entry) => {
                acc[entry.type] = (acc[entry.type] || 0) + 1;
                return acc;
            }, {});
        
            // Monthly data
            const monthlyData = entries.reduce((acc, entry) => {
                const date = new Date(entry.date);
                const monthYear = `${date.getFullYear()}-${(date.getMonth() + 1).toString().padStart(2, '0')}`;
                if (!acc[monthYear]) {
                    acc[monthYear] = { amount: 0, count: 0 };
                }
                acc[monthYear].amount += parseFloat(entry.amount);
                acc[monthYear].count += 1;
                return acc;
            }, {});
        
            // Get last 6 months
            const last6Months = [];
            const now = new Date();
            for (let i = 5; i >= 0; i--) {
                const date = new Date(now.getFullYear(), now.getMonth() - i, 1);
                last6Months.push(`${date.getFullYear()}-${(date.getMonth() + 1).toString().padStart(2, '0')}`);
            }
        
            // Create chart containers
            elements.chartsSection.innerHTML = `
                <div class="bg-white rounded-xl shadow-sm p-5 border border-gray-200 mb-4">
                    <h3 class="text-lg font-semibold text-gray-800 mb-6">Petty Cash Analytics</h3>
                    
                    <!-- Quick stats -->
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
                        <div class="bg-gradient-to-r from-blue-50 to-blue-100 p-4 rounded-lg border border-blue-200">
                            <div class="flex items-center">
                                <div class="w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center mr-3">
                                    <i class="fas fa-car text-blue-600"></i>
                                </div>
                                <div>
                                    <p class="text-sm text-blue-800">Conveyance</p>
                                    <p class="text-xl font-bold text-blue-900">${formatCurrency(typeData.conveyance_bill || 0)}</p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="bg-gradient-to-r from-purple-50 to-purple-100 p-4 rounded-lg border border-purple-200">
                            <div class="flex items-center">
                                <div class="w-10 h-10 bg-purple-100 rounded-full flex items-center justify-center mr-3">
                                    <i class="fas fa-receipt text-purple-600"></i>
                                </div>
                                <div>
                                    <p class="text-sm text-purple-800">Other Bills</p>
                                    <p class="text-xl font-bold text-purple-900">${formatCurrency(typeData.other_bill || 0)}</p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="bg-gradient-to-r from-green-50 to-green-100 p-4 rounded-lg border border-green-200">
                            <div class="flex items-center">
                                <div class="w-10 h-10 bg-green-100 rounded-full flex items-center justify-center mr-3">
                                    <i class="fas fa-hand-holding-usd text-green-600"></i>
                                </div>
                                <div>
                                    <p class="text-sm text-green-800">Loans</p>
                                    <p class="text-xl font-bold text-green-900">${formatCurrency(typeData.loan || 0)}</p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="bg-gradient-to-r from-amber-50 to-amber-100 p-4 rounded-lg border border-amber-200">
                            <div class="flex items-center">
                                <div class="w-10 h-10 bg-amber-100 rounded-full flex items-center justify-center mr-3">
                                    <i class="fas fa-money-bill-wave text-amber-600"></i>
                                </div>
                                <div>
                                    <p class="text-sm text-amber-800">Petty Cash</p>
                                    <p class="text-xl font-bold text-amber-900">${formatCurrency(typeData.petty_cash || 0)}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Charts row -->
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        <!-- Type Distribution Chart -->
                        <div class="bg-gray-50 rounded-lg p-4 border border-gray-200">
                            <div class="flex justify-between items-center mb-4">
                                <h4 class="font-medium text-gray-700">Amount by Type</h4>
                                <span class="text-sm text-gray-500">Total: ${formatCurrency(Object.values(typeData).reduce((a, b) => a + b, 0))}</span>
                            </div>
                            <div class="relative h-64">
                                <canvas id="typeChart"></canvas>
                            </div>
                            <div class="mt-4 grid grid-cols-2 gap-2" id="typeLegend"></div>
                        </div>
                        
                        <!-- Monthly Trend Chart -->
                        <div class="bg-gray-50 rounded-lg p-4 border border-gray-200">
                            <div class="flex justify-between items-center mb-4">
                                <h4 class="font-medium text-gray-700">Monthly Trend</h4>
                                <span class="text-sm text-gray-500">Last 6 months</span>
                            </div>
                            <div class="relative h-64">
                                <canvas id="trendChart"></canvas>
                            </div>
                            <div class="mt-4 text-center text-sm text-gray-500">
                                <i class="fas fa-chart-line mr-1"></i> Monthly spending trend
                            </div>
                        </div>
                    </div>
                </div>
            `;
        
            // Create actual charts
            setTimeout(() => {
                createTypeChart(typeData, countByType);
                createTrendChart(monthlyData, last6Months);
            }, 100);
        }

        // Helper function to format currency
        function formatCurrency(amount) {
            return `৳${parseFloat(amount).toLocaleString('en-IN', { 
                minimumFractionDigits: 2, 
                maximumFractionDigits: 2 
            })}`;
        }

        // Create type distribution chart
        function createTypeChart(typeData, countByType) {
            const canvas = document.getElementById('typeChart');
            if (!canvas) return;
            
            const ctx = canvas.getContext('2d');
            const width = canvas.width = canvas.offsetWidth;
            const height = canvas.height = canvas.offsetHeight;
            
            // Clear canvas
            ctx.clearRect(0, 0, width, height);
            
            // Colors for each type
            const colors = {
                conveyance_bill: '#3b82f6',
                other_bill: '#8b5cf6',
                loan: '#10b981',
                petty_cash: '#f59e0b'
            };
            
            const typeLabels = {
                conveyance_bill: 'Conveyance',
                other_bill: 'Other Bills',
                loan: 'Loans',
                petty_cash: 'Petty Cash'
            };
            
            // Filter out zero amounts
            const entries = Object.entries(typeData).filter(([_, amount]) => amount > 0);
            const total = entries.reduce((sum, [_, amount]) => sum + amount, 0);
            
            if (total === 0) {
                // Show empty chart message
                ctx.fillStyle = '#6b7280';
                ctx.font = '14px Arial';
                ctx.textAlign = 'center';
                ctx.textBaseline = 'middle';
                ctx.fillText('No data available', width / 2, height / 2);
                return;
            }
            
            const centerX = width / 2;
            const centerY = height / 2;
            const radius = Math.min(width, height) / 2 - 20;
            
            let startAngle = 0;
            
            // Draw pie slices
            entries.forEach(([type, amount], index) => {
                const sliceAngle = (amount / total) * 2 * Math.PI;
                
                // Draw slice
                ctx.beginPath();
                ctx.moveTo(centerX, centerY);
                ctx.arc(centerX, centerY, radius, startAngle, startAngle + sliceAngle);
                ctx.closePath();
                ctx.fillStyle = colors[type];
                ctx.fill();
                
                // Add border
                ctx.strokeStyle = '#ffffff';
                ctx.lineWidth = 2;
                ctx.stroke();
                
                // Draw percentage
                const midAngle = startAngle + sliceAngle / 2;
                const textRadius = radius * 0.5;
                const textX = centerX + Math.cos(midAngle) * textRadius;
                const textY = centerY + Math.sin(midAngle) * textRadius;
                
                ctx.fillStyle = '#ffffff';
                ctx.font = 'bold 12px Arial';
                ctx.textAlign = 'center';
                ctx.textBaseline = 'middle';
                ctx.fillText(`${((amount / total) * 100).toFixed(0)}%`, textX, textY);
                
                startAngle += sliceAngle;
            });
            
            // Draw center circle
            ctx.beginPath();
            ctx.arc(centerX, centerY, radius * 0.3, 0, Math.PI * 2);
            ctx.fillStyle = '#f8fafc';
            ctx.fill();
            
            ctx.fillStyle = '#374151';
            ctx.font = 'bold 14px Arial';
            ctx.textAlign = 'center';
            ctx.textBaseline = 'middle';
            ctx.fillText('Total', centerX, centerY - 10);
            ctx.font = 'bold 16px Arial';
            ctx.fillText(formatCurrency(total), centerX, centerY + 10);
            
            // Update legend
            const legendContainer = document.getElementById('typeLegend');
            if (legendContainer) {
                let legendHTML = '';
                entries.forEach(([type, amount]) => {
                    const percentage = ((amount / total) * 100).toFixed(1);
                    const count = countByType[type] || 0;
                    legendHTML += `
                        <div class="flex items-center p-2 bg-white rounded border">
                            <div class="w-3 h-3 rounded-full mr-2" style="background-color: ${colors[type]}"></div>
                            <span class="text-sm font-medium text-gray-700">${typeLabels[type]}</span>
                            <span class="ml-auto text-sm text-gray-600">${count} (${percentage}%)</span>
                        </div>
                    `;
                });
                legendContainer.innerHTML = legendHTML;
            }
        }

        // Create trend chart
        function createTrendChart(monthlyData, months) {
            const canvas = document.getElementById('trendChart');
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
            const data = months.map(month => monthlyData[month]?.amount || 0);
            const maxAmount = Math.max(...data, 1000); // Min 1000 for scale
            
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
                
                const value = (maxAmount * i / gridLines);
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
            
            // Calculate point positions for line chart
            const pointSpacing = chartWidth / (months.length - 1);
            const points = [];
            
            data.forEach((amount, index) => {
                const x = padding.left + (index * pointSpacing);
                const y = amount > 0 ? padding.top + chartHeight * (1 - (amount / maxAmount)) : padding.top + chartHeight;
                points.push({ x, y, amount });
            });
            
            // Draw line
            ctx.beginPath();
            ctx.moveTo(points[0].x, points[0].y);
            points.forEach(point => {
                ctx.lineTo(point.x, point.y);
            });
            
            ctx.strokeStyle = '#3b82f6';
            ctx.lineWidth = 3;
            ctx.stroke();
            
            // Draw points
            points.forEach(point => {
                if (point.amount > 0) {
                    ctx.beginPath();
                    ctx.arc(point.x, point.y, 5, 0, Math.PI * 2);
                    ctx.fillStyle = '#1d4ed8';
                    ctx.fill();
                    ctx.strokeStyle = '#ffffff';
                    ctx.lineWidth = 2;
                    ctx.stroke();
                    
                    // Draw value above point
                    if (point.y > 20) {
                        ctx.fillStyle = '#374151';
                        ctx.font = 'bold 10px Arial';
                        ctx.textAlign = 'center';
                        ctx.textBaseline = 'bottom';
                        
                        let displayValue;
                        if (point.amount >= 1000000) {
                            displayValue = `৳${(point.amount / 1000000).toFixed(1)}M`;
                        } else if (point.amount >= 1000) {
                            displayValue = `৳${(point.amount / 1000).toFixed(0)}K`;
                        } else {
                            displayValue = `৳${point.amount.toFixed(0)}`;
                        }
                        
                        ctx.fillText(displayValue, point.x, point.y - 10);
                    }
                }
            });
            
            // Draw month labels
            months.forEach((month, index) => {
                const x = padding.left + (index * pointSpacing);
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
            ctx.fillText('Monthly Spending Trend', width / 2, 10);
        }

        // Render entries with proper infinite scroll
        function renderEntries() {
            const startIndex = (currentPage - 1) * ITEMS_PER_PAGE;
            const endIndex = startIndex + ITEMS_PER_PAGE;
            const pageEntries = filteredEntries.slice(startIndex, endIndex);
        
            if (pageEntries.length === 0) {
                if (currentPage === 1) { // Only show empty state on first page
                    elements.emptyState.style.display = 'block';
                    elements.container.innerHTML = '';
                    elements.container.appendChild(elements.emptyState);
                }
                return;
            }
        
            elements.emptyState.style.display = 'none';
        
            let html = '';
            
            pageEntries.forEach(entry => {
                const entryDate = new Date(entry.date);
                const typeClass = `type-${entry.type.replace('_', '-')}`;
                const typeLabel = entry.type.replace('_', ' ').toUpperCase();
                
                html += `
                    <div class="petty-card bg-white border border-gray-200 rounded-lg p-5 hover:border-blue-300 fade-in mb-4" 
                         onclick="showEntryDetails('${escapeHtml(JSON.stringify(entry))}')">
                        <div class="flex flex-col md:flex-row justify-between gap-4">
                            <!-- Left Column -->
                            <div class="flex-1">
                                <div class="flex flex-col md:flex-row md:items-start justify-between mb-4">
                                    <div class="mb-3 md:mb-0">
                                        <div class="flex items-center mb-3">
                                            <div class="w-10 h-10 bg-gradient-to-r from-blue-100 to-blue-200 rounded-lg flex items-center justify-center mr-3">
                                                <i class="${getTypeIcon(entry.type)} text-blue-600"></i>
                                            </div>
                                            <div>
                                                <h3 class="font-bold text-gray-800 text-lg">
                                                    ${escapeHtml(entry.user_name || 'N/A')}
                                                    ${entry.user_sys_id ? ` || ${escapeHtml(entry.user_sys_id)}` : ''}
                                                </h3>
                                                <div class="flex items-center mt-1 space-x-4">
                                                    <span class="text-gray-600 text-sm">
                                                        <i class="far fa-calendar mr-1"></i> 
                                                        ${entryDate.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })}
                                                    </span>
                                                    ${entry.to_user_name ? `
                                                        <span class="text-gray-600 text-sm">
                                                            <i class="fas fa-user-check mr-1"></i> 
                                                            To: ${escapeHtml(entry.to_user_name)}
                                                        </span>
                                                    ` : ''}
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="flex flex-wrap items-center gap-2 text-sm text-gray-600">
                                            <span class="type-badge ${typeClass}">
                                                ${typeLabel}
                                            </span>
                                            <span class="flex items-center bg-gray-100 px-3 py-1 rounded-full">
                                                <i class="fas fa-hashtag mr-2"></i> ${escapeHtml(entry.sys_id || 'N/A')}
                                            </span>
                                            ${entry.ref ? `
                                                <span class="flex items-center bg-gray-100 px-3 py-1 rounded-full">
                                                    <i class="fas fa-link mr-2"></i> ${escapeHtml(entry.ref.substring(0, 30))}${entry.ref.length > 30 ? '...' : ''}
                                                </span>
                                            ` : ''}
                                        </div>
                                    </div>
                                    
                                    <div class="flex flex-col items-end">
                                        <div class="amount-badge bg-gradient-to-r from-blue-500 to-blue-600 text-white mb-2">
                                            ${formatCurrency(entry.amount)}
                                        </div>
                                        <div class="text-sm text-gray-500">
                                            <i class="fas fa-id-card mr-1"></i> ${escapeHtml(entry.sys_id || 'N/A')}
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Details Summary -->
                                <div class="mb-4">
                                    <div class="text-sm text-gray-700 mb-2">
                                        <strong>Purpose:</strong> ${escapeHtml(entry.purpose || 'N/A')}
                                    </div>
                                    <div class="text-sm text-gray-700">
                                        <strong>Details:</strong> ${escapeHtml(entry.details.substring(0, 150))}${entry.details.length > 150 ? '...' : ''}
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Action Buttons -->
                            <div class="md:w-48 flex md:flex-col gap-2">
                                <button class="download-btn bg-gradient-to-r from-blue-500 to-blue-600 hover:from-blue-600 hover:to-blue-700 text-white font-medium py-2.5 px-4 rounded-lg transition duration-300 flex items-center justify-center action-btn w-full"
                                        onclick="event.stopPropagation(); downloadEntry('${entry.uuid}')">
                                    <i class="fas fa-download mr-2"></i>
                                    <span>Download</span>
                                </button>
                                <button class="edit-btn bg-green-50 hover:bg-green-100 text-green-700 border border-green-200 font-medium py-2.5 px-4 rounded-lg transition duration-300 flex items-center justify-center action-btn w-full"
                                        onclick="event.stopPropagation(); editEntry('${entry.uuid}')">
                                    <i class="fas fa-pencil mr-2"></i>
                                    <span>Edit</span>
                                </button>
                                <button class="delete-btn bg-red-50 hover:bg-red-100 text-red-700 border border-red-200 font-medium py-2.5 px-4 rounded-lg transition duration-300 flex items-center justify-center action-btn w-full"
                                        onclick="event.stopPropagation(); deleteEntry('${entry.uuid}', '${escapeHtml(entry.sys_id)}')">
                                    <i class="fas fa-trash mr-2"></i>
                                    <span>Delete</span>
                                </button>
                            </div>
                        </div>
                    </div>
                `;
            });
            
            // Append new entries instead of replacing
            if (currentPage === 1) {
                elements.container.innerHTML = `<div class="space-y-4" id="entries-list">${html}</div>`;
            } else {
                const entriesList = document.getElementById('entries-list');
                if (entriesList) {
                    entriesList.innerHTML += html;
                } else {
                    elements.container.innerHTML = `<div class="space-y-4" id="entries-list">${html}</div>`;
                }
            }
            
            // Show/hide infinite scroll loader
            const showingCount = Math.min(filteredEntries.length, currentPage * ITEMS_PER_PAGE);
            elements.showingCount.textContent = showingCount;
            
            if (isInfiniteScroll && showingCount < filteredEntries.length) {
                elements.infiniteLoader.style.display = 'block';
            } else {
                elements.infiniteLoader.style.display = 'none';
            }
        }

        // Get icon based on type
        function getTypeIcon(type) {
            switch(type) {
                case 'conveyance_bill': return 'fas fa-car';
                case 'other_bill': return 'fas fa-receipt';
                case 'loan': return 'fas fa-hand-holding-usd';
                case 'petty_cash': return 'fas fa-money-bill-wave';
                default: return 'fas fa-file-invoice';
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
                    loadMoreEntries();
                }
            });
        }

        // Load more entries for infinite scroll
        async function loadMoreEntries() {
            const showingCount = currentPage * ITEMS_PER_PAGE;
            
            if (showingCount >= filteredEntries.length) {
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
            renderEntries();
        
            isLoading = false;
            elements.infiniteLoader.classList.remove('active');
        }   

        // Escape HTML to prevent XSS
        function escapeHtml(text) {
            if (!text) return '';
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

        // Show entry details modal
        function showEntryDetails(entryJson) {
            try {
                const entry = JSON.parse(entryJson);
                const modal = document.getElementById('detailsModal');
                const content = document.getElementById('detailsModalContent');
                const entryDate = new Date(entry.date);
                
                let html = `
                    <div class="space-y-4">
                        <!-- Header -->
                        <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
                            <div class="flex items-center">
                                <div class="w-12 h-12 bg-gradient-to-r from-blue-100 to-blue-200 rounded-lg flex items-center justify-center mr-4">
                                    <i class="${getTypeIcon(entry.type)} text-blue-600 text-xl"></i>
                                </div>
                                <div>
                                    <div class="font-bold text-lg text-gray-800">${escapeHtml(entry.user_name || 'N/A')}</div>
                                    <div class="text-sm text-gray-600">${entryDate.toLocaleDateString('en-US', { 
                                        weekday: 'long', 
                                        year: 'numeric', 
                                        month: 'long', 
                                        day: 'numeric' 
                                    })}</div>
                                </div>
                            </div>
                            <div class="text-right">
                                <div class="text-2xl font-bold text-blue-600">${formatCurrency(entry.amount)}</div>
                                <span class="type-badge type-${entry.type.replace('_', '-')}">
                                    ${entry.type.replace('_', ' ').toUpperCase()}
                                </span>
                            </div>
                        </div>
                        
                        <!-- Details -->
                        <div class="grid grid-cols-2 gap-4">
                            <div class="bg-white p-4 rounded-lg border border-gray-200">
                                <div class="text-sm text-gray-500 mb-1">System ID</div>
                                <div class="font-medium">${escapeHtml(entry.sys_id || 'N/A')}</div>
                            </div>
                            <div class="bg-white p-4 rounded-lg border border-gray-200">
                                <div class="text-sm text-gray-500 mb-1">UUID</div>
                                <div class="font-medium text-sm">${escapeHtml(entry.uuid || 'N/A')}</div>
                            </div>
                        </div>
                        
                        ${entry.to_user_name ? `
                            <div class="bg-white p-4 rounded-lg border border-gray-200">
                                <div class="text-sm text-gray-500 mb-1">To Employee</div>
                                <div class="font-medium">${escapeHtml(entry.to_user_name)}</div>
                                ${entry.to_user_sys_id ? `
                                    <div class="text-xs text-gray-500 mt-1">ID: ${escapeHtml(entry.to_user_sys_id)}</div>
                                ` : ''}
                            </div>
                        ` : ''}
                        
                        ${entry.purpose ? `
                            <div class="bg-white p-4 rounded-lg border border-gray-200">
                                <div class="text-sm text-gray-500 mb-1">Purpose</div>
                                <div class="font-medium">${escapeHtml(entry.purpose)}</div>
                            </div>
                        ` : ''}
                        
                        <div class="bg-white p-4 rounded-lg border border-gray-200">
                            <div class="text-sm text-gray-500 mb-1">Details</div>
                            <div class="font-medium whitespace-pre-line">${escapeHtml(entry.details)}</div>
                        </div>
                        
                        ${entry.ref ? `
                            <div class="bg-white p-4 rounded-lg border border-gray-200">
                                <div class="text-sm text-gray-500 mb-1">Reference</div>
                                <div class="font-medium">${escapeHtml(entry.ref)}</div>
                            </div>
                        ` : ''}
                        
                        <!-- Meta Data -->
                        <div class="bg-gray-50 p-4 rounded-lg border border-gray-200">
                            <div class="text-sm text-gray-500 mb-2">Created By</div>
                            <div class="text-sm">${escapeHtml(entry.meta_data?.created_by_date || 'N/A')}</div>
                        </div>
                    </div>
                `;
                
                content.innerHTML = html;
                modal.classList.add('active');
            } catch (error) {
                console.error('Error showing details:', error);
                alert('Error loading entry details');
            }
        }

        function closeDetailsModal() {
            document.getElementById('detailsModal').classList.remove('active');
        }

        function downloadEntry(uuid) {
            // Implement download functionality
            window.open(`print-petty.php?uuid=${uuid}`, '_blank');
        }

        function editEntry(uuid) {
            window.open(`edit-petty.php?uuid=${uuid}`, '_blank');
        }

        async function deleteEntry(uuid, sysId) {
            if (confirm(`Are you sure you want to delete entry ${sysId}? This action cannot be undone.`)) {
                try {
                    const response = await fetch(DELETE_API, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
                            uuid: uuid,
                            sys_id: sysId
                        })
                    });
                    
                    const data = await response.json();
                    
                    if (data.success) {
                        alert('Entry deleted successfully!');
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
                if (entries.length > 0) {
                    renderCharts();
                }
            }, 250);
        });
    </script>
</body>
</html>