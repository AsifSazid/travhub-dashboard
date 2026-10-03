            <!-- ═══════════════════════════════════════════════
                 SECTION: Documents
            ════════════════════════════════════════════════ -->
            <div id="mpsec-documents" class="mp-section" style="display:none">
                <div class="bg-white rounded-2xl shadow p-5">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="font-semibold text-gray-800 flex items-center gap-2">
                            <i class="fas fa-folder-open text-orange-500"></i> My Documents
                        </h3>
                        <label class="cursor-pointer bg-orange-500 hover:bg-orange-600 text-white text-sm font-semibold px-4 py-2 rounded-lg flex items-center gap-2 transition">
                            <i class="fas fa-upload"></i> Upload
                            <input type="file" id="mpDocUploadInput" class="hidden" multiple accept=".pdf,.doc,.docx,.jpg,.jpeg,.png,.xls,.xlsx">
                        </label>
                    </div>
                    <div id="mpDocUploadProgress" class="hidden mb-4 p-3 rounded-lg bg-blue-50 text-sm text-blue-700">
                        <i class="fas fa-spinner fa-spin mr-2"></i>Uploading…
                    </div>
                    <div id="mpDocsList" class="space-y-2">
                        <div class="text-center py-10 text-gray-400 text-sm">
                            <i class="fas fa-spinner fa-spin text-2xl mb-3 block text-gray-300"></i>Loading…
                        </div>
                    </div>
                </div>
            </div>

