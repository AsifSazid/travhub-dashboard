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

    <div class="overflow-x-auto table-container">
        <table id="finTable" class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50 sticky top-0 z-10">
                <tr>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Purpose</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Work</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                </tr>
            </thead>
            <tbody id="finTableBody" class="bg-white divide-y divide-gray-200 text-left">
            </tbody>
        </table>
        
        <!-- No Results Message -->
        <div id="noResultsMessage" class="hidden px-6 py-10 text-center text-gray-500">
            <div class="flex flex-col items-center gap-2">
                <i class="fas fa-search text-3xl text-gray-400"></i>
                <p class="text-sm">No vendor transactions match your search criteria</p>
            </div>
        </div>
    </div>
</div>

<script>
    const GET_FINANCIAL_STATEMENT_BY_VENDOR_API = "<?php echo $getVendorFinEntriesApi; ?>";
    let originalFinStmts = []; // Store original vendor data for filtering

    function reloadFinancialTable() {
        fetch(GET_FINANCIAL_STATEMENT_BY_VENDOR_API)
            .then(res => res.json())
            .then(data => {
                if (!data.success) return;

                originalFinStmts = data.finStmts; // Store original vendor data
                renderFinTable(originalFinStmts);
                updateSummary(originalFinStmts);
            })
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

    function filterTransactions() {
        const searchTerm = searchInput.value.toLowerCase();
        const selectedType = filterType.value;
        
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
        
        renderFinTable(filteredList);
        updateSummary(filteredList);
        
        // Show/hide no results message
        if (filteredList.length === 0 && originalFinStmts.length > 0) {
            noResultsMessage.classList.remove('hidden');
            finTableBody.innerHTML = '';
        } else {
            noResultsMessage.classList.add('hidden');
        }
    }

    function renderFinTable(list) {
        finTableBody.innerHTML = '';
        
        if (!list || list.length === 0) {
            const tr = document.createElement('tr');
    
            tr.innerHTML = `
                <td colspan="8" class="px-6 py-10 text-center text-gray-500">
                    <div class="flex flex-col items-center gap-2">
                        <i class="fas fa-users-slash text-3xl text-gray-400"></i>
                        <p class="text-sm">No Transaction Found!</p>
                    </div>
                </td>
            `;
    
            finTableBody.appendChild(tr);
            return;
        }

        list.forEach(finSingleEntry => {
            const tr = document.createElement('tr');
            tr.className = "hover:bg-gray-50";

            const type = (finSingleEntry.type || '').toLowerCase();

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

            tr.innerHTML = `
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                        ${finSingleEntry.date || 'N/A'}
                    </td>

                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">
                        ${finSingleEntry.purpose || 'No Data Found'}
                    </td>
                    
                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                    ${finSingleEntry.work_title || 'No Data Found'}
                    </td>

                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                    ${typeBadge}
                    </td>

                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                        ${finSingleEntry.amount || '-'}
                    </td>
                `;

            finTableBody.appendChild(tr);
        });
    }

    // Event Listeners for filtering
    searchInput.addEventListener('input', filterTransactions);
    filterType.addEventListener('change', filterTransactions);
    resetFilters.addEventListener('click', () => {
        searchInput.value = '';
        filterType.value = 'all';
        filterTransactions();
    });

    // Debounce search for better performance
    let debounceTimer;
    searchInput.addEventListener('input', () => {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(filterTransactions, 300);
    });

    // Initialize
    reloadFinancialTable();
</script>