<div id="taskTab" class="tab-content hidden">
    <h2 class="text-lg font-semibold text-gray-800 mb-1">
        Task Management
    </h2>
    <p class="text-sm text-gray-600 mb-4">
        Drag & drop files or paste content from clipboard
    </p>

    <form action="">
        <div class="grid grid-cols-2 gap-4">
            <!-- Left -->
            <div>
                <div class="mb-4">
                    <label for="taskCategory" class="block text-sm font-medium text-gray-700 my-2">Task Category</label>
                    <select id="taskCategory" name="task_category" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500">
                        <option value="">Search For</option>
                        <option value="1">Air Ticket Issue</option>
                        <option value="2">Hotel Booking</option>
                    </select>
                </div>

                <label class="block text-sm font-medium text-gray-700 my-2">Upload or Paste Your Documents</label>
                
                <div class="grid grid-cols-2 gap-4">
                    <div id="dragDropArea" class="rounded-lg border-2 border-dashed border-gray-300 p-6 mb-4 flex flex-col items-center justify-center hover:bg-gray-50">
                        <i class="fas fa-cloud-upload-alt text-4xl text-gray-400 mb-3"></i>
                        <input type="file" id="fileInput" multiple class="hidden">
                        <button
                            onclick="document.getElementById('fileInput').click()"
                            class="px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700">
                            <i class="fas fa-folder-open mr-1"></i> Browse Files
                        </button>
                    </div>

                    <textarea id="pasteArea"
                        placeholder="Paste content here"
                        class="w-full h-36 p-2 border-2 border-dashed border-gray-300 rounded"></textarea>

                    <div class="mt-4">
                        <div class="flex justify-between items-center mb-2">
                            <h4 class="text-sm font-medium">Dropped or Pasted Files</h4>
                            <span id="fileCount" class="text-xs bg-gray-200 px-2 py-1 rounded">0 files</span>
                        </div>
                    </div>
                </div>
                <div id="droppedFilesList" class="text-sm text-gray-500">
                    No files added yet
                </div>

            </div>

            <!-- Right -->
            <div>
                <label for="infoFileName" class="block text-sm font-medium text-gray-700 my-2">
                    Information File
                </label>
                <input type="text" id="infoFileName" name="infoFileName" class="w-full px-3 py-2 mb-4 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500">
                <label for="infoArea" class="block text-sm font-medium text-gray-700 mb-2">
                    Information
                </label>
                <textarea id="infoArea" rows="5" class="w-full p-2 border rounded-lg" placeholder="Write your information"></textarea>
            </div>
        </div>

        <button type="submit" class="flex-1 px-4 py-2 mt-4 bg-primary-600 hover:bg-primary-700 text-white font-medium rounded-lg shadow-md hover:shadow-lg transition-all duration-300 flex items-center justify-center">Submit</button>

    </form>

    <hr class="my-6">

    <div class="col-span-6 bg-white rounded-lg shadow p-4 flex flex-col">
        <h2 class="text-2xl font-semibold text-gray-800 mb-4">Task Lists</h2>

        <div class="overflow-x-auto table-container">
            <table id="ledgerTable" class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Task Title</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Category</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Files</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Action</th>
                    </tr>
                </thead>
                <tbody id="ledgerTableBody" class="bg-white divide-y divide-gray-200">
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">2025-11-29 02:06:58</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">Task One for Rony Maldives (13)</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">Air Ticket</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">Folder</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            <a href="cwe_tm-financial-trxn.php?work_id=123&task_id=234">
                                <i class="fa-solid fa-calculator"></i>
                            </a>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>