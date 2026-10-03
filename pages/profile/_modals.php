<!-- ═══════════════════════════════════════
     APPLY LEAVE MODAL
════════════════════════════════════════ -->
<div id="mpLeaveModal" class="mp-modal-bg" style="display:none">
    <div class="mp-modal">
        <div class="flex items-center justify-between mb-5">
            <h3 class="text-base font-bold text-gray-800 flex items-center gap-2">
                <i class="fas fa-calendar-plus text-blue-500"></i> Apply for Leave
            </h3>
            <button id="mpLeaveModalClose" class="text-gray-400 hover:text-gray-600 text-xl leading-none">&times;</button>
        </div>

        <div id="mpLeaveFormAlert" class="hidden mb-4 p-3 rounded-lg text-sm font-medium"></div>

        <div class="space-y-4">
            <div>
                <label class="mp-lbl block mb-1">Leave Type</label>
                <select id="mpLeaveType" class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400">
                    <option value="">Loading types…</option>
                </select>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="mp-lbl block mb-1">From Date</label>
                    <input type="date" id="mpLeaveFrom" class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400">
                </div>
                <div>
                    <label class="mp-lbl block mb-1">To Date</label>
                    <input type="date" id="mpLeaveTo" class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400">
                </div>
            </div>

            <!-- Bridge calculation preview -->
            <div id="mpBridgeInfo" class="hidden p-3 bg-blue-50 rounded-lg text-sm text-blue-700">
                <i class="fas fa-info-circle mr-1"></i>
                <span id="mpBridgeText"></span>
            </div>

            <div>
                <label class="mp-lbl block mb-1">Reason</label>
                <textarea id="mpLeaveReason" rows="3"
                    class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400 resize-none"
                    placeholder="Optional reason for leave"></textarea>
            </div>

            <button id="mpLeaveSubmitBtn"
                class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-lg px-4 py-2.5 text-sm transition flex items-center justify-center gap-2">
                <i class="fas fa-paper-plane"></i> Submit Application
            </button>
        </div>
    </div>
</div>


<!-- ═══════════════════════════════════════
     ALL LEAVES MODAL
════════════════════════════════════════ -->
<div id="mpAllLeavesModal" class="mp-modal-bg" style="display:none">
    <div class="mp-modal" style="max-width:640px">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-base font-bold text-gray-800 flex items-center gap-2">
                <i class="fas fa-list-check text-indigo-500"></i> All Leave Applications
            </h3>
            <button id="mpAllLeavesClose" class="text-gray-400 hover:text-gray-600 text-xl leading-none">&times;</button>
        </div>
        <div id="mpAllLeavesList" class="space-y-2 max-h-[60vh] overflow-y-auto"></div>
    </div>
</div>

<!-- ═══════════════════════════════════════
     DAY DETAIL MODAL
════════════════════════════════════════ -->
<div id="mpDayModal" class="mp-modal-bg" style="display:none">
    <div class="mp-modal max-w-sm">
        <div class="flex items-center justify-between mb-4">
            <h3 id="mpDayModalTitle" class="text-base font-bold text-gray-800"></h3>
            <button id="mpDayModalClose" class="text-gray-400 hover:text-gray-600 text-xl leading-none">&times;</button>
        </div>
        <div id="mpDayModalBody"></div>
    </div>
</div>

<!-- ═══════════════════════════════════════
     CREDENTIAL MODAL
════════════════════════════════════════ -->
<div id="mpCredModal" class="mp-modal-bg" style="display:none">
    <div class="mp-modal">
        <div class="flex items-center justify-between mb-5">
            <h3 id="mpCredModalTitle" class="text-base font-bold text-gray-800 flex items-center gap-2">
                <i class="fas fa-key text-indigo-500"></i> Add Credential
            </h3>
            <button id="mpCredModalClose" class="text-gray-400 hover:text-gray-600 text-xl leading-none">&times;</button>
        </div>
        <div id="mpCredFormAlert" class="hidden mb-4 p-3 rounded-lg text-sm font-medium"></div>
        <input type="hidden" id="mpCredSysId">
        <div class="space-y-3">
            <div>
                <label class="mp-lbl block mb-1">Title *</label>
                <input type="text" id="mpCredTitle" placeholder="e.g. Company Email" class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400">
            </div>
            <div>
                <label class="mp-lbl block mb-1">URL</label>
                <input type="url" id="mpCredUrl" placeholder="https://" class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400">
            </div>
            <div>
                <label class="mp-lbl block mb-1">Username / Email</label>
                <input type="text" id="mpCredUsername" class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400">
            </div>
            <div>
                <label class="mp-lbl block mb-1">Password</label>
                <div class="relative">
                    <input type="password" id="mpCredPassword" placeholder="Leave blank to keep unchanged" class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400 pr-10">
                    <button type="button" id="mpCredPwEye" class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600">
                        <i class="fas fa-eye text-sm"></i>
                    </button>
                </div>
            </div>
            <div>
                <label class="mp-lbl block mb-1">Notes</label>
                <textarea id="mpCredNotes" rows="2" class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400 resize-none"></textarea>
            </div>
            <button id="mpCredSaveBtn" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-semibold rounded-lg px-4 py-2.5 text-sm transition flex items-center justify-center gap-2">
                <i class="fas fa-save"></i> Save
            </button>
        </div>
    </div>
</div>

<!-- ═══════════════════════════════════════
     SIGN OUT CONFIRM MODAL
════════════════════════════════════════ -->
<div id="mpSignOutModal" class="mp-modal-bg" style="display:none">
    <div class="mp-modal max-w-sm text-center">
        <div class="w-16 h-16 rounded-full bg-red-50 flex items-center justify-center mx-auto mb-4">
            <i class="fas fa-sign-out-alt text-2xl text-red-500"></i>
        </div>
        <h3 class="text-base font-bold text-gray-800 mb-1">Sign Out for Today?</h3>
        <p id="mpSignOutDuration" class="text-sm text-gray-500 mb-5"></p>
        <div class="flex gap-3">
            <button id="mpSignOutCancel" class="flex-1 border border-gray-200 text-gray-600 font-semibold rounded-lg px-4 py-2.5 text-sm hover:bg-gray-50 transition">Cancel</button>
            <button id="mpSignOutConfirm" class="flex-1 bg-red-600 hover:bg-red-700 text-white font-semibold rounded-lg px-4 py-2.5 text-sm transition">
                <i class="fas fa-check mr-1.5"></i> Confirm
            </button>
        </div>
    </div>
</div>

<?php include '../elements/floating-menus.php'; ?>
