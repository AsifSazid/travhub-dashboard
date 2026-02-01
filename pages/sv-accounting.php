    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mt-2">
        <div class="col-span-3">    
            <div class="bg-white rounded-lg shadow p-4 flex flex-col">
                <div class="overflow-x-auto overflow-y-auto max-h-[70vh] table-container">
                    <table id="finTable" class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
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
                </div>
            </div>
        </div>
        <div class="col-span-1 border-l border-gray-400 sticky self-start h-fit">
            <h3 class="text-xl font-semibold mb-2">Summary</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-2 gap-4 m-8">
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
        </div>
    </div>


    <script>
        const GET_FINANCIAL_STATEMENT_BY_VENDOR_API = "<?php echo $getVendorFinEntriesApi; ?>";

        function reloadFinancialTable() {
            fetch(GET_FINANCIAL_STATEMENT_BY_VENDOR_API)
                .then(res => res.json())
                .then(data => {
                    if (!data.success) return;

                    const finStmts = data.finStmts;
                    renderFinTable(finStmts);
                    updateSummary(finStmts);
                })
        }
        
        function updateSummary(list) {
            // initializing
            const totalTrnx = document.getElementById('total-trnx'); 
            const totalCredit = document.getElementById('total-credit'); 
            const totalDebit = document.getElementById('total-debit'); 
            const totalOutstanding = document.getElementById('total-outstanding'); 
            
            // getting value
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

        function renderFinTable(list) {
            // আগের ডাটা মুছে ফেলা
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

                // type normalize
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

        reloadFinancialTable();
    </script>