    <div class="bg-white rounded-lg shadow p-4 flex flex-col">
        <h2 class="text-2xl font-semibold text-gray-800 mb-4">Financial Transactions</h2>

        <div class="overflow-x-auto table-container">
            <table id="finTable" class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Purpose</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Client/Vendor Name</th>
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


    <script>
        const GET_FINANCIAL_STATEMENT_BY_CLIENT_API = "<?php echo $getClientFinEntriesApi; ?>";

        function reloadFinancialTable() {
            fetch(GET_FINANCIAL_STATEMENT_BY_CLIENT_API)
                .then(res => res.json())
                .then(data => {
                    if (!data.success) return;
                    const finStmts = data.finStmts;

                    renderFinTable(finStmts);
                })
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
        
                tableBody.appendChild(tr);
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
                            <span class="px-3 py-1 rounded-full text-xs font-semibold bg-green-100 text-green-700">
                                CREDIT
                            </span>
                        `;
                } else if (type === 'credit') {
                    typeBadge = `
                            <span class="px-3 py-1 rounded-full text-xs font-semibold bg-red-100 text-red-700">
                                DEBIT
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

                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            ${finSingleEntry.client_name || finSingleEntry.vendor_name || 'Unknown'}
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