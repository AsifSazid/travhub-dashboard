<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 m-8">
    <div class="group bg-gradient-to-br from-purple-50 to-purple-100 border border-purple-200 hover:border-purple-400 hover:from-purple-100 hover:to-purple-200 p-4 rounded-xl text-center transition-all duration-300 hover:shadow-lg hover:-translate-y-1">
        <p id="total-trnx" class="font-semibold text-purple-800">1</p>
        <p class="text-xs text-purple-600 mt-1">Total Trnx</p>
    </div>
    <div class="group bg-gradient-to-br from-green-50 to-green-100 border border-green-200 hover:border-green-400 hover:from-green-100 hover:to-green-200 p-4 rounded-xl text-center transition-all duration-300 hover:shadow-lg hover:-translate-y-1">
        <p id="total-credit" class="font-semibold text-green-800">1</p>
        <p class="text-xs text-green-600 mt-1">Total Credit</p>
    </div>
    <div class="group bg-gradient-to-br from-red-50 to-red-100 border border-red-200 hover:border-red-400 hover:from-red-100 hover:to-red-200 p-4 rounded-xl text-center transition-all duration-300 hover:shadow-lg hover:-translate-y-1">
        <p id="total-debit" class="font-semibold text-red-800">1</p>
        <p class="text-xs text-red-600 mt-1">Total Debit</p>
        </div>
    <div class="group bg-gradient-to-br from-yellow-50 to-yellow-100 border border-yellow-200 hover:border-yellow-400 hover:from-yellow-100 hover:to-yellow-200 p-4 rounded-xl text-center transition-all duration-300 hover:shadow-lg hover:-translate-y-1">
        <p id="total-outstanding" class="font-semibold text-yellow-800">1</p>
        <p class="text-xs text-yellow-600 mt-1">Total Outstanding</p>
    </div>
</div>
<div class="bg-white rounded-lg shadow p-4 flex flex-col">
    <!-- Header with Search and Filters -->
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-4 gap-3">
        <h2 class="text-2xl font-semibold text-gray-800">Vendor Transactions</h2>
        
        <!-- Search and Filter Section -->
        <div class="flex flex-col sm:flex-row gap-3 w-full md:w-auto">
            <!-- Search Input -->
            <div class="relative flex-1 sm:flex-none">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <i class="fas fa-search text-gray-400"></i>
                </div>
                <input 
                    type="text" 
                    id="searchInput"
                    class="pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 w-full"
                    placeholder="Search vendor transactions..."
                >
            </div>
            
            <!-- Type Filter -->
            <div class="relative">
                <select 
                    id="filterType" 
                    class="appearance-none pl-4 pr-10 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 w-full"
                >
                    <option value="all">All Types</option>
                    <option value="credit">Credit</option>
                    <option value="debit">Debit</option>
                </select>
                <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                    <i class="fas fa-chevron-down text-gray-400"></i>
                </div>
            </div>
            
            <!-- Reset Button -->
            <button 
                id="resetFilters"
                class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors flex items-center justify-center whitespace-nowrap"
            >
                <i class="fas fa-redo mr-2"></i>Reset
            </button>
        </div>
    </div>

    <div class="table-container">
        <table id="finTable" class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50 sticky top-0 z-10">
                <tr>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-64">Purpose</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Work</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Running Outstanding</th>
                </tr>
            </thead>
            <tbody id="finTableBody" class="bg-white divide-y divide-gray-200 text-left">
            </tbody>
            <!-- Total Row -->
            <tfoot class="bg-gray-50 font-semibold">
                <tr>
                    <td colspan="5" class="px-6 py-4 text-right text-sm text-gray-700">Final Balance:</td>
                    <td id="final-outstanding" class="px-6 py-4 text-sm text-yellow-700 font-bold">0.00</td>
                </tr>
            </tfoot>
        </table>
        
        <!-- No Results Message -->
        <div id="noResultsMessage" class="hidden px-6 py-10 text-center text-gray-500">
            <div class="flex flex-col items-center gap-2">
                <i class="fas fa-search text-3xl text-gray-400"></i>
                <p class="text-sm">No vendor transactions match your search criteria</p>
            </div>
        </div>
        
        <!-- Load More Button -->
        <div id="loadMoreContainer" class="hidden mt-4 text-center">
            <button 
                id="loadMoreBtn"
                class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors"
            >
                <i class="fas fa-spinner fa-spin hidden mr-2" id="loadMoreSpinner"></i>
                Load More
            </button>
        </div>
    </div>
</div>

<script>
    const GET_FINANCIAL_STATEMENT_BY_VENDOR_API = "<?php echo $getVendorFinEntriesApi; ?>";
    let originalFinStmts = []; // Store original vendor data for filtering
    let displayedFinStmts = []; // Store currently displayed data
    let currentOffset = 0; // Start from newest
    const incrementAmount = 5; // How many to load each time
    let isFiltering = false; // Track if we're in filtering mode
    let runningBalances = new Map(); // Store running balances for all transactions
    let finalBalance = 0; // Store final balance

    function reloadFinancialTable() {
        fetch(GET_FINANCIAL_STATEMENT_BY_VENDOR_API)
            .then(res => res.json())
            .then(data => {
                if (!data.success) return;

                // API থেকে ডেটা নিই এবং নতুন থেকে পুরানো সাজাই (নতুন তারিখ আগে)
                originalFinStmts = data.finStmts.sort((a, b) => {
                    // প্রথমে তারিখ নতুন থেকে পুরানো
                    const dateA = new Date(a.date);
                    const dateB = new Date(b.date);
                    if (dateA.getTime() !== dateB.getTime()) {
                        return dateB - dateA; // নতুন তারিখ আগে
                    }
                    // তারিখ একই হলে id বড় থেকে ছোট (নতুন id আগে)
                    return b.id - a.id;
                });

                // Calculate running balances for all transactions
                calculateRunningBalances(originalFinStmts);

                // প্রাথমিকভাবে নতুন ৫টি ট্রানজেকশন দেখাবো (0 থেকে 5)
                currentOffset = 0;
                displayedFinStmts = originalFinStmts.slice(currentOffset, currentOffset + incrementAmount);
                renderFinTable(displayedFinStmts, originalFinStmts); // পুরো ডেটা ক্যালকুলেশনের জন্য পাঠাই
                updateSummary(originalFinStmts); // সামারি সবসময় পুরো ডেটার উপর ভিত্তি করে
                
                // Load More বাটন দেখানোর সিদ্ধান্ত
                toggleLoadMoreButton();
            })
    }
    
    function calculateRunningBalances(list) {
        // STEP 1: ক্যালকুলেশনের জন্য পুরানো থেকে নতুন সাজাই
        const sortedForCalculation = [...list].sort((a, b) => {
            const dateA = new Date(a.date);
            const dateB = new Date(b.date);
            if (dateA.getTime() !== dateB.getTime()) {
                return dateA - dateB; // পুরানো তারিখ আগে
            }
            return a.id - b.id; // একই তারিখে ছোট id আগে
        });
        
        // STEP 2: প্রতিটি transaction এর জন্য running balance calculate করি
        let cumulativeBalance = 0;
        runningBalances.clear();
        
        sortedForCalculation.forEach((entry) => {
            const type = (entry.type || '').toLowerCase();
            const amount = Number(entry.amount) || 0;
            
            // এখন transaction apply করি
            if (type === 'credit') {
                // CREDIT: Balance বাড়ে (Company vendor কে দিলে)
                cumulativeBalance += amount;
            } else if (type === 'debit') {
                // DEBIT: Balance কমে (Vendor company কে দিলে)
                cumulativeBalance -= amount;
            }
            
            // এই transaction এর পরে running balance store করি
            runningBalances.set(entry.id, cumulativeBalance);
        });
        
        // Final balance store করি
        finalBalance = cumulativeBalance;
    }
    
    function updateSummary(list) {
        const totalTrnx = document.getElementById('total-trnx'); 
        const totalCredit = document.getElementById('total-credit'); 
        const totalDebit = document.getElementById('total-debit'); 
        const totalOutstanding = document.getElementById('total-outstanding'); 
        
        let totalTrnxCount = list.length;
        let totalCreditAmount = 0;
        let totalDebitAmount = 0;
        let totalOutstandingAmount = 0;

        list.forEach(finSingleEntry => {
            const type = (finSingleEntry.type || '').toLowerCase();
            const amount = Number(finSingleEntry.amount) || 0;
            
            if (type === 'credit') {
                totalCreditAmount += amount;
            }
            if (type === 'debit') {
                totalDebitAmount += amount;
            }
        });
        
        totalOutstandingAmount = totalCreditAmount - totalDebitAmount;
        
        totalTrnx.textContent = totalTrnxCount;
        totalCredit.textContent = totalCreditAmount.toFixed(2);
        totalDebit.textContent = totalDebitAmount.toFixed(2);
        totalOutstanding.textContent = totalOutstandingAmount.toFixed(2);
    }

    const finTableBody = document.getElementById('finTableBody');
    const searchInput = document.getElementById('searchInput');
    const filterType = document.getElementById('filterType');
    const resetFilters = document.getElementById('resetFilters');
    const noResultsMessage = document.getElementById('noResultsMessage');
    const loadMoreContainer = document.getElementById('loadMoreContainer');
    const loadMoreBtn = document.getElementById('loadMoreBtn');
    const loadMoreSpinner = document.getElementById('loadMoreSpinner');
    const finalOutstanding = document.getElementById('final-outstanding');

    function filterTransactions() {
        const searchTerm = searchInput.value.toLowerCase();
        const selectedType = filterType.value;
        
        isFiltering = searchTerm !== '' || selectedType !== 'all';
        
        if (isFiltering) {
            // সার্চ বা ফিল্টার করা হলে পুরো ডেটা থেকে ফলাফল দেখাবে
            let filteredList = originalFinStmts.filter(entry => {
                // Search filter - vendor specific fields
                const matchesSearch = searchTerm === '' || 
                    (entry.purpose && entry.purpose.toLowerCase().includes(searchTerm)) ||
                    (entry.work_title && entry.work_title.toLowerCase().includes(searchTerm)) ||
                    (entry.date && entry.date.toLowerCase().includes(searchTerm)) ||
                    (entry.amount && entry.amount.toString().includes(searchTerm));
                
                // Type filter
                const matchesType = selectedType === 'all' || 
                    (entry.type && entry.type.toLowerCase() === selectedType);
                
                return matchesSearch && matchesType;
            });
            
            // Calculate running balances for filtered list
            calculateRunningBalances(filteredList);
            
            // ফিল্টার মোডে সব দেখাবে, নতুন থেকে পুরানো সাজিয়ে
            displayedFinStmts = filteredList.sort((a, b) => {
                const dateA = new Date(a.date);
                const dateB = new Date(b.date);
                if (dateA.getTime() !== dateB.getTime()) {
                    return dateB - dateA; // নতুন তারিখ আগে
                }
                return b.id - a.id; // নতুন id আগে
            });
            
            renderFinTable(displayedFinStmts, filteredList);
            updateSummary(filteredList);
            
            // Show/hide no results message
            if (filteredList.length === 0 && originalFinStmts.length > 0) {
                noResultsMessage.classList.remove('hidden');
                finTableBody.innerHTML = '';
                finalOutstanding.textContent = '0.00';
                loadMoreContainer.classList.add('hidden');
            } else {
                noResultsMessage.classList.add('hidden');
                loadMoreContainer.classList.add('hidden'); // ফিল্টার মোডে Load More দেখাবে না
            }
        } else {
            // ফিল্টার না করা হলে নতুন থেকে পুরানো সাজিয়ে নির্দিষ্ট পরিমাণ দেখাবে
            isFiltering = false;
            displayedFinStmts = originalFinStmts.slice(currentOffset, currentOffset + incrementAmount);
            renderFinTable(displayedFinStmts, originalFinStmts);
            updateSummary(originalFinStmts);
            noResultsMessage.classList.add('hidden');
            toggleLoadMoreButton();
        }
    }

    function renderFinTable(displayList, calculationList) {
        finTableBody.innerHTML = '';
        
        if (!displayList || displayList.length === 0) {
            const tr = document.createElement('tr');
    
            tr.innerHTML = `
                <td colspan="6" class="px-6 py-10 text-center text-gray-500">
                    <div class="flex flex-col items-center gap-2">
                        <i class="fas fa-users-slash text-3xl text-gray-400"></i>
                        <p class="text-sm">No Transaction Found!</p>
                    </div>
                </td>
            `;
    
            finTableBody.appendChild(tr);
            finalOutstanding.textContent = '0.00';
            return;
        }

        displayList.forEach(finSingleEntry => {
            const tr = document.createElement('tr');
            tr.className = "hover:bg-gray-50";

            const type = (finSingleEntry.type || '').toLowerCase();
            const amount = Number(finSingleEntry.amount) || 0;
            
            // এই transaction এর running balance Map থেকে নিই
            const runningOutstanding = runningBalances.get(finSingleEntry.id) || 0;

            let typeBadge = `
                    <span class="px-3 py-1 rounded-full text-xs font-semibold bg-gray-100 text-gray-600">
                        UNKNOWN
                    </span>
                `;

            if (type === 'debit') {
                typeBadge = `
                        <span class="px-3 py-1 rounded-full text-xs font-semibold bg-red-100 text-red-700">
                            DEBIT
                        </span>
                    `;
            } else if (type === 'credit') {
                typeBadge = `
                        <span class="px-3 py-1 rounded-full text-xs font-semibold bg-green-100 text-green-700">
                            CREDIT
                        </span>
                    `;
            }
            
            // Format running outstanding with color based on value
            let outstandingClass = "text-gray-700";
            if (runningOutstanding > 0) {
                outstandingClass = "text-green-700 font-semibold";
            } else if (runningOutstanding < 0) {
                outstandingClass = "text-red-700 font-semibold";
            }

            tr.innerHTML = `
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                        ${finSingleEntry.date || 'N/A'}
                    </td>

                    <td class="px-6 py-2 text-sm text-gray-700 max-w-xs break-words whitespace-normal">
                        ${finSingleEntry.purpose || 'No Data Found'}
                    </td>
                    
                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                    ${finSingleEntry.work_title || 'No Data Found'}
                    </td>

                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                    ${typeBadge}
                    </td>

                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium ${type === 'credit' ? 'text-green-600' : 'text-red-600'}">
                        ${amount.toFixed(2)}
                    </td>
                    
                    <td class="px-6 py-4 whitespace-nowrap text-sm ${outstandingClass}">
                        ${runningOutstanding.toFixed(2)}
                    </td>
                `;

            finTableBody.appendChild(tr);
        });
        
        // Update final outstanding in footer
        finalOutstanding.textContent = finalBalance.toFixed(2);
        
        // Color code final outstanding
        if (finalBalance > 0) {
            finalOutstanding.className = "px-6 py-4 text-sm text-green-700 font-bold";
        } else if (finalBalance < 0) {
            finalOutstanding.className = "px-6 py-4 text-sm text-red-700 font-bold";
        } else {
            finalOutstanding.className = "px-6 py-4 text-sm text-yellow-700 font-bold";
        }
    }

    function toggleLoadMoreButton() {
        // শুধুমাত্র ফিল্টার মোডে না থাকলে এবং আরো ডেটা থাকলে Load More বাটন দেখাবে
        if (!isFiltering && (currentOffset + incrementAmount) < originalFinStmts.length) {
            loadMoreContainer.classList.remove('hidden');
        } else {
            loadMoreContainer.classList.add('hidden');
        }
    }

    function loadMoreTransactions() {
        loadMoreSpinner.classList.remove('hidden');
        loadMoreBtn.disabled = true;
        
        // নতুন ডেটা লোড করি (পুরানো ডেটার দিকে যাবো)
        currentOffset += incrementAmount;
        
        // পরবর্তী ৫টি ট্রানজেকশন যোগ করি
        const nextBatch = originalFinStmts.slice(currentOffset, currentOffset + incrementAmount);
        displayedFinStmts = [...displayedFinStmts, ...nextBatch];
        
        // কিছুক্ষণ পরে UI আপডেট করি (লোডিং ইফেক্টের জন্য)
        setTimeout(() => {
            renderFinTable(displayedFinStmts, originalFinStmts);
            updateSummary(originalFinStmts);
            toggleLoadMoreButton();
            
            loadMoreSpinner.classList.add('hidden');
            loadMoreBtn.disabled = false;
        }, 300);
    }

    // Event Listeners
    searchInput.addEventListener('input', () => {
        clearTimeout(window.debounceTimer);
        window.debounceTimer = setTimeout(filterTransactions, 300);
    });
    filterType.addEventListener('change', filterTransactions);
    resetFilters.addEventListener('click', () => {
        searchInput.value = '';
        filterType.value = 'all';
        currentOffset = 0; // রিসেট করলে আবার নতুন ডেটা থেকে শুরু হবে
        isFiltering = false;
        // Recalculate running balances for original data
        calculateRunningBalances(originalFinStmts);
        filterTransactions();
    });
    loadMoreBtn.addEventListener('click', loadMoreTransactions);

    // Initialize
    reloadFinancialTable();
</script>