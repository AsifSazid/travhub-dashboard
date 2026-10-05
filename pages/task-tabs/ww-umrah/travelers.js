/**
 * FILE PATH: /pages/task-tabs/ww-umrah/travelers.js
 * Tab: Travelers & Group
 */

window._renderUmTravelers = function() {
    const panel   = document.getElementById('um-panel-travelers');
    const summary = window._um.summary;
    const group   = window._um.group;
    const gData   = group?.group;
    const members = group?.members ?? [];

    // ── No group yet ──────────────────────────────────────────
    if (!gData) {
        panel.innerHTML = `
        <div class="max-w-md mx-auto mt-8">
            <div class="text-center mb-6">
                <i class="fas fa-users text-4xl text-purple-200 mb-3 block"></i>
                <h3 class="font-bold text-gray-700 text-sm">No Umrah Group Yet</h3>
                <p class="text-xs text-gray-400 mt-1">Create a group to manage travelers for this Umrah trip.</p>
            </div>
            <div class="space-y-3">
                <div>
                    <label class="text-xs font-bold text-gray-500 uppercase block mb-1">Group Name</label>
                    <input id="um-new-group-name" placeholder="e.g. Umrah Group — Nov 2026"
                        class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:border-purple-400">
                </div>
                <div>
                    <label class="text-xs font-bold text-gray-500 uppercase block mb-1">Description (optional)</label>
                    <input id="um-new-group-desc" placeholder="Notes about this group"
                        class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:border-purple-400">
                </div>
                <button onclick="umCreateGroup()"
                    class="w-full py-2.5 bg-purple-600 hover:bg-purple-700 text-white rounded-lg text-sm font-semibold transition">
                    <i class="fas fa-plus mr-1.5"></i>Create Group
                </button>
            </div>
        </div>`;
        return;
    }

    // ── Group exists ──────────────────────────────────────────
    const leaders  = members.filter(m => m.is_leader);
    const travelers= members.filter(m => !m.is_leader);

    panel.innerHTML = `
    <!-- Group header -->
    <div class="flex items-center justify-between mb-4 p-3 bg-purple-50 rounded-xl border border-purple-100">
        <div>
            <div class="text-sm font-bold text-purple-800">${_ume(gData.group_name)}</div>
            <div class="text-[11px] text-purple-500 mt-0.5 font-mono">${_ume(gData.sys_id)}</div>
        </div>
        <div class="flex items-center gap-2">
            <span class="text-xs bg-purple-100 text-purple-700 px-2 py-0.5 rounded-full font-semibold">${members.length} travelers</span>
            <button onclick="umOpenSetLeaders()" class="px-3 py-1.5 bg-amber-500 hover:bg-amber-600 text-white text-xs font-semibold rounded-lg transition">
                <i class="fas fa-star mr-1"></i>Leaders
            </button>
            <button onclick="umOpenAddTraveler()" class="px-3 py-1.5 bg-purple-600 hover:bg-purple-700 text-white text-xs font-semibold rounded-lg transition">
                <i class="fas fa-plus mr-1"></i>Add Traveler
            </button>
        </div>
    </div>

    <!-- Leaders row -->
    ${leaders.length ? `
    <div class="mb-4">
        <div class="text-xs font-bold text-amber-600 uppercase mb-2"><i class="fas fa-star mr-1"></i>Group Leaders</div>
        <div class="flex gap-3 flex-wrap">
            ${leaders.map(l => _umTravelerChip(l, true)).join('')}
        </div>
    </div>` : ''}

    <!-- Travelers -->
    <div>
        <div class="text-xs font-bold text-gray-500 uppercase mb-2">Travelers (${travelers.length})</div>
        ${travelers.length ? `
        <div class="space-y-2" id="um-traveler-list">
            ${travelers.map(t => _umTravelerRow(t)).join('')}
        </div>` : '<p class="text-xs text-gray-300 text-center py-4">No travelers yet. Add some above.</p>'}
    </div>

    <!-- Add Traveler Modal -->
    <div id="um-add-traveler-modal" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4" style="background:rgba(0,0,0,.5);">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg max-h-[85vh] overflow-y-auto">
            <div class="flex items-center justify-between p-4 border-b border-gray-100">
                <h3 class="font-semibold text-gray-800 text-sm"><i class="fas fa-user-plus mr-2 text-purple-500"></i>Add Traveler</h3>
                <button onclick="document.getElementById('um-add-traveler-modal').classList.add('hidden')" class="text-gray-400 hover:text-gray-600"><i class="fas fa-times"></i></button>
            </div>
            <div class="p-4">
                <!-- Search existing -->
                <label class="text-xs font-bold text-gray-500 uppercase block mb-1">Search Existing Traveler</label>
                <div class="relative mb-3">
                    <input id="um-traveler-search" placeholder="Name or passport number…" autocomplete="off"
                        class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:border-purple-400"
                        oninput="umSearchTravelers(this.value)">
                    <div id="um-traveler-results" class="absolute w-full bg-white border border-gray-200 rounded-lg mt-1 shadow-xl hidden z-50 max-h-48 overflow-auto"></div>
                </div>
                <div class="flex items-center gap-2 mb-3">
                    <div class="flex-1 h-px bg-gray-200"></div>
                    <span class="text-[10px] text-gray-400 uppercase">or scan passport</span>
                    <div class="flex-1 h-px bg-gray-200"></div>
                </div>
                <label class="flex items-center justify-center gap-2 w-full py-2.5 border-2 border-dashed border-purple-200 rounded-xl cursor-pointer hover:border-purple-400 hover:bg-purple-50 transition text-xs font-semibold text-purple-600">
                    <i class="fas fa-camera"></i>Upload Passport Scan (AI Extract)
                    <input type="file" class="hidden" accept="image/*" onchange="umPassportScan(this)">
                </label>
                <div id="um-passport-scan-prog" class="hidden mt-2 text-xs text-purple-600 text-center"><i class="fas fa-spinner fa-spin mr-1"></i>Extracting passport data…</div>
            </div>
        </div>
    </div>

    <!-- Set Leaders Modal -->
    <div id="um-leaders-modal" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4" style="background:rgba(0,0,0,.5);">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md">
            <div class="flex items-center justify-between p-4 border-b border-gray-100">
                <h3 class="font-semibold text-gray-800 text-sm"><i class="fas fa-star mr-2 text-amber-500"></i>Set Group Leaders</h3>
                <button onclick="document.getElementById('um-leaders-modal').classList.add('hidden')" class="text-gray-400 hover:text-gray-600"><i class="fas fa-times"></i></button>
            </div>
            <div class="p-4">
                <p class="text-xs text-gray-400 mb-3">Select up to 3 leaders:</p>
                <div class="space-y-2 max-h-48 overflow-y-auto mb-4">
                    ${members.map(m => `
                    <label class="flex items-center gap-3 p-2 rounded-lg hover:bg-gray-50 cursor-pointer">
                        <input type="checkbox" value="${_ume(m.traveler_id)}" ${m.is_leader?'checked':''}
                            class="um-leader-cb w-4 h-4 accent-amber-500">
                        <span class="text-sm text-gray-700">${_ume(m.name||'Unknown')}</span>
                        <span class="text-xs text-gray-400 ml-auto">${_ume(m.passport_no||'—')}</span>
                    </label>`).join('')}
                </div>
                <button onclick="umSaveLeaders()" class="w-full py-2.5 bg-amber-500 hover:bg-amber-600 text-white rounded-lg text-sm font-semibold transition">
                    <i class="fas fa-save mr-1.5"></i>Save Leaders
                </button>
            </div>
        </div>
    </div>`;
};

function _umTravelerChip(m, isLeader) {
    const name = m.name || 'Unknown';
    return `<div class="flex items-center gap-2 bg-amber-50 border border-amber-200 rounded-xl px-3 py-2">
        <div class="w-7 h-7 rounded-full bg-amber-200 flex items-center justify-center text-amber-800 font-bold text-xs flex-shrink-0">${_ume(name[0]||'?')}</div>
        <div>
            <div class="text-xs font-semibold text-gray-800">${_ume(name)}</div>
            <div class="text-[10px] text-gray-400">${_ume(m.passport_no||'—')}</div>
        </div>
    </div>`;
}

function _umTravelerRow(m) {
    const gSysId = window._um.summary?.group?.sys_id ?? '';
    const name   = m.name || 'Unknown';
    return `<div class="flex items-center gap-3 p-3 bg-white border border-gray-100 rounded-xl hover:border-purple-200 transition">
        <div class="w-9 h-9 rounded-full bg-purple-100 flex items-center justify-center text-purple-700 font-bold text-sm flex-shrink-0">${_ume(name[0]||'?')}</div>
        <div class="flex-1 min-w-0">
            <div class="text-sm font-semibold text-gray-800">${_ume(name)}</div>
            <div class="text-[11px] text-gray-400">${_ume(m.passport_no||'No passport')} · ${_ume(m.expiry||'—')}</div>
        </div>
        <div class="flex items-center gap-2">
            <!-- Roaming phone inline edit -->
            <div class="flex items-center gap-1">
                <span class="text-[10px] text-gray-400"><i class="fas fa-sim-card text-[9px]"></i></span>
                <span id="um-rphone-display-${_ume(m.traveler_id)}" class="text-xs text-gray-500 cursor-pointer hover:text-purple-600"
                    onclick="umEditRoamingPhone('${_ume(m.traveler_id)}')">${_ume(m.roaming_phone||'KSA phone')}</span>
                <input id="um-rphone-input-${_ume(m.traveler_id)}" class="hidden text-xs border border-purple-300 rounded px-1 py-0.5 w-28 focus:outline-none"
                    value="${_ume(m.roaming_phone||'')}" placeholder="KSA phone"
                    onblur="umSaveRoamingPhone('${_ume(m.traveler_id)}')"
                    onkeydown="if(event.key==='Enter')umSaveRoamingPhone('${_ume(m.traveler_id)}')">
            </div>
            <button onclick="umRemoveTraveler('${_ume(m.traveler_id)}')" class="text-red-300 hover:text-red-500 text-xs transition" title="Remove">
                <i class="fas fa-times"></i>
            </button>
        </div>
    </div>`;
}

// ── Group create ──────────────────────────────────────────────
window.umCreateGroup = async function() {
    const name = document.getElementById('um-new-group-name')?.value.trim();
    const desc = document.getElementById('um-new-group-desc')?.value.trim();
    if (!name) { umT('error','Group name দিন'); return; }
    try {
        const json = await window._umApi({ action:'create_group', group_name:name, description:desc });
        if (json.status === 'success') { umT('success','Group created!'); await window._umReload(); _renderUmTravelers(); }
        else umT('error', json.message);
    } catch(e) { umT('error','Network error'); }
};

// ── Add Traveler modal ────────────────────────────────────────
window.umOpenAddTraveler = function() {
    document.getElementById('um-add-traveler-modal')?.classList.remove('hidden');
    document.getElementById('um-traveler-search')?.focus();
};

window.umSearchTravelers = async function(q) {
    const dd = document.getElementById('um-traveler-results');
    if (!q || q.length < 2) { dd?.classList.add('hidden'); return; }
    try {
        const res  = await fetch(`${window._um.cfg.api.allTravelers}?search=${encodeURIComponent(q)}&limit=10`);
        const json = await res.json();
        const list = json.data ?? json.travelers ?? [];
        if (!list.length) { dd.innerHTML='<div class="px-4 py-3 text-xs text-gray-400 text-center">Not found</div>'; dd.classList.remove('hidden'); return; }
        dd.innerHTML = list.map(t => `
            <div class="px-3 py-2 cursor-pointer hover:bg-purple-50 border-b last:border-0 text-sm"
                onclick="umAddExistingTraveler('${_ume(t.sys_id)}','${_ume(t.name||'')}')">
                <div class="font-medium text-gray-800">${_ume(t.name||'—')}</div>
                <div class="text-[11px] text-gray-400">${_ume(t.passport_no||'No passport')}</div>
            </div>`).join('');
        dd.classList.remove('hidden');
    } catch(e) {}
};

window.umAddExistingTraveler = async function(tSysId, name) {
    document.getElementById('um-traveler-results')?.classList.add('hidden');
    document.getElementById('um-traveler-search').value = '';
    const gSysId = window._um.summary?.group?.sys_id;
    if (!gSysId) return;
    try {
        const json = await window._umApi({ action:'add_traveler', group_sys_id:gSysId, traveler_sys_id:tSysId });
        if (json.status === 'success') {
            umT('success', `${name} added!`);
            document.getElementById('um-add-traveler-modal')?.classList.add('hidden');
            await window._umReload(); _renderUmTravelers();
        } else umT('error', json.message);
    } catch(e) { umT('error','Network error'); }
};

// ── Passport scan ─────────────────────────────────────────────
window.umPassportScan = async function(input) {
    if (!input.files[0]) return;
    const prog = document.getElementById('um-passport-scan-prog');
    prog?.classList.remove('hidden');
    const gSysId = window._um.summary?.group?.sys_id;

    const fd = new FormData();
    fd.append('file', input.files[0]);
    fd.append('group_sys_id', gSysId || '');
    fd.append('work_sys_id', window._um.cfg.workSysId);

    try {
        const res  = await fetch(window._um.cfg.api.extractDocument, { method:'POST', body:fd });
        const json = await res.json();
        prog?.classList.add('hidden');
        if (json.success && json.traveler_sys_id) {
            const tName = json.traveler_name || 'Traveler';
            umT('success', `${tName} extracted & saved!`);
            // Auto-add to group if group exists
            if (gSysId) {
                await window._umApi({ action:'add_traveler', group_sys_id:gSysId, traveler_sys_id:json.traveler_sys_id });
            }
            document.getElementById('um-add-traveler-modal')?.classList.add('hidden');
            await window._umReload(); _renderUmTravelers();
        } else {
            umT('error', json.message || 'Extraction failed');
        }
    } catch(e) { prog?.classList.add('hidden'); umT('error','Network error'); }
};

// ── Leaders ───────────────────────────────────────────────────
window.umOpenSetLeaders = function() {
    document.getElementById('um-leaders-modal')?.classList.remove('hidden');
};

window.umSaveLeaders = async function() {
    const selected = [...document.querySelectorAll('.um-leader-cb:checked')].map(cb => cb.value);
    if (selected.length > 3) { umT('error','সর্বোচ্চ 3 জন leader হতে পারে'); return; }
    const gSysId = window._um.summary?.group?.sys_id;
    try {
        const json = await window._umApi({ action:'set_leaders', group_sys_id:gSysId, traveler_sys_ids:selected });
        if (json.status === 'success') {
            umT('success','Leaders updated!');
            document.getElementById('um-leaders-modal')?.classList.add('hidden');
            await window._umReload(); _renderUmTravelers();
        } else umT('error', json.message);
    } catch(e) { umT('error','Network error'); }
};

// ── Remove traveler ───────────────────────────────────────────
window.umRemoveTraveler = async function(tSysId) {
    if (!confirm('Remove from group?')) return;
    const gSysId = window._um.summary?.group?.sys_id;
    try {
        const json = await window._umApi({ action:'remove_traveler', group_sys_id:gSysId, traveler_sys_id:tSysId });
        if (json.status === 'success') { umT('success','Removed'); await window._umReload(); _renderUmTravelers(); }
        else umT('error', json.message);
    } catch(e) { umT('error','Network error'); }
};

// ── Roaming phone ─────────────────────────────────────────────
window.umEditRoamingPhone = function(tSysId) {
    document.getElementById(`um-rphone-display-${tSysId}`)?.classList.add('hidden');
    const inp = document.getElementById(`um-rphone-input-${tSysId}`);
    inp?.classList.remove('hidden'); inp?.focus();
};

window.umSaveRoamingPhone = async function(tSysId) {
    const inp   = document.getElementById(`um-rphone-input-${tSysId}`);
    const phone = inp?.value.trim() || '';
    const gSysId= window._um.summary?.group?.sys_id;
    const disp  = document.getElementById(`um-rphone-display-${tSysId}`);
    if (disp) disp.textContent = phone || 'KSA phone';
    inp?.classList.add('hidden'); disp?.classList.remove('hidden');
    try {
        await window._umApi({ action:'update_roaming_phone', group_sys_id:gSysId, traveler_sys_id:tSysId, roaming_phone:phone });
    } catch(e) {}
};

// Close search on click outside
document.addEventListener('click', e => {
    const wrap = document.getElementById('um-traveler-search')?.parentElement;
    if (wrap && !wrap.contains(e.target)) document.getElementById('um-traveler-results')?.classList.add('hidden');
});