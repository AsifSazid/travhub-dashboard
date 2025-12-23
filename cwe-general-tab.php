<div id="generalTab" class="tab-content">
    <h2 class="text-lg font-semibold text-gray-800 mb-1">
        General Information for Completed Work Entry
    </h2>
    <p class="text-sm text-gray-600 mb-4">Please fill up the form</p>

    <form action="">
        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 my-2">Search Client</label>
                <select
                    name="client"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500">
                    <option value="">Search For</option>
                    <option value="1">Asif M Sazid</option>
                    <option value="2">Shahanur Alam</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 my-2">Work Title</label>
                <input name="work_title" placeholder="Write a Work Title" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500">
            </div>
        </div>

        <button type="submit" class="flex-1 px-4 py-2 mt-4 bg-primary-600 hover:bg-primary-700 text-white font-medium rounded-lg shadow-md hover:shadow-lg transition-all duration-300 flex items-center justify-center">Submit</button>
    </form>

    <hr class="my-6">

    <div class="col-span-6 bg-white rounded-lg shadow p-4 flex flex-col">
        <h2 class="text-2xl font-semibold text-gray-800 mb-4">Transaction Ledger</h2>

        <div class="overflow-x-auto table-container">
            <table id="ledgerTable" class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Purpose</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Client (ID)</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Vendor (ID)</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Dir</th>
                        <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider text-green-600">Deposit (IN)</th>
                        <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider text-red-600">Withdraw (OUT)</th>
                    </tr>
                </thead>
                <tbody id="ledgerTableBody" class="bg-white divide-y divide-gray-200">
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">2025-11-29 02:06:58</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">Best Western Plus Pearl Creek Hotel</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">Client: Rony Maldives (13)</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">-</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">Dubai Hotel 7th to 10th August/BestWesternPlusPearlCreekHotel_Dubai_7Aug-10Aug_ShahidulIslam.pdf</td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-semibold text-green-600">
                            19200.00
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-semibold text-red-600">
                            0.00
                        </td>
                    </tr>
                </tbody>
                <tfoot id="ledgerTableFoot">
                    <tr class="bg-gray-100 font-bold">
                        <td colspan="4" class="px-6 py-4 text-right text-base text-gray-900">Total:</td>
                        <td class="px-6 py-4 text-right text-base text-gray-900"></td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-base text-green-700">
                            19200.00 </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-base text-red-700">
                            0.00 </td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>