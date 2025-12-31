<div class="bg-white rounded-lg shadow p-4 flex flex-col text-left">

    <div class="grid grid-cols-3 gap-4">
        <!-- Left -->
        <div class="col-span-2">
            <div class="mb-4">
                <h3 class="text-xl font-semibold mb-2">Files Are Shown here-</h3>
            </div>
        </div>


        <!-- Right -->
        <form id="docForms">
            <div class="col-span-1">
                <div class="mb-4">
                    <label class="block mb-2 text-sm font-medium text-gray-700">
                        Select Document
                    </label>

                    <select
                        class="w-full rounded-lg border border-gray-300 bg-white px-4 py-2 text-gray-700 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 focus:outline-none">
                        <option value="" selected disabled>-- Select --</option>
                        <option value="trade_license">Trade License</option>
                        <option value="trade_license_translated">Trade License (Translated and Notarized)</option>
                        <option value="company_letterhead">Company Letterhead</option>
                        <option value="common">Just Common</option>
                        <option value="moa">MOA</option>
                        <option value="form_xii">Form XII</option>
                        <option value="tin">TIN</option>
                        <option value="tax_return">Tax Return</option>
                        <option value="schedule_x">Schedule-X</option>
                    </select>
                </div>

                <label class="block text-sm font-medium text-gray-700 my-2">Upload or Paste Your Documents</label>

                <div class="grid grid-cols-2 gap-4">
                    <div id="dragDropArea" class="rounded-lg border-2 border-dashed border-gray-300 p-6 mb-4 flex flex-col items-center justify-center hover:bg-gray-50">
                        <i class="fas fa-cloud-upload-alt text-4xl text-gray-400 mb-3"></i>
                        <input type="file" id="fileInput" multiple class="hidden">
                        <button
                            type="button"
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
            <button type="submit" class="flex-1 px-4 py-2 mt-4 bg-primary-600 hover:bg-primary-700 text-white font-medium rounded-lg shadow-md hover:shadow-lg transition-all duration-300 flex items-center justify-center">Submit</button>

        </form>
    </div>

</div>


<script src="../assets/js/functional/file-dropper.js"></script>