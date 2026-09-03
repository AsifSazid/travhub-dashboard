<?php
/**
 * FILE PATH: pages/tp-linked-travellers.php
 * Linked Travellers tab — show-travelers.php এ include হয়।
 * Family relations (spouse/parent/sibling) + named travel groups।
 */
?>

<div class="flex flex-col overflow-hidden" style="height: calc(100vh - 260px); min-height: 400px;">

    <!-- Family Links section -->
    <div class="flex items-center justify-between mb-3 flex-shrink-0">
        <h3 class="text-sm font-semibold text-gray-700"><i class="fas fa-users mr-1"></i> Family Links</h3>
        <button onclick="openAddLinkModal()"
                class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-blue-600 text-white text-xs rounded-lg hover:bg-blue-700">
            <i class="fas fa-plus"></i> Add Link
        </button>
    </div>

    <div id="linksLoading" class="flex items-center gap-2 text-sm text-gray-500 py-3">
        <span class="w-4 h-4 border-2 border-gray-300 border-t-blue-500 rounded-full animate-spin"></span>
        Loading...
    </div>
    <div id="linksList" class="hidden space-y-2 mb-2"></div>
    <div id="linksEmpty" class="hidden text-sm text-gray-400 py-3">No family links added yet.</div>

    <!-- Travel Groups section -->
    <div class="flex items-center justify-between mb-3 mt-6 flex-shrink-0 pt-4 border-t border-gray-100">
        <h3 class="text-sm font-semibold text-gray-700"><i class="fas fa-suitcase-rolling mr-1"></i> Travel Groups</h3>
        <button onclick="openGroupModal()"
                class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-emerald-600 text-white text-xs rounded-lg hover:bg-emerald-700">
            <i class="fas fa-plus"></i> New / Join Group
        </button>
    </div>

    <div id="groupsLoading" class="flex items-center gap-2 text-sm text-gray-500 py-3">
        <span class="w-4 h-4 border-2 border-gray-300 border-t-emerald-500 rounded-full animate-spin"></span>
        Loading...
    </div>
    <div id="groupsList" class="hidden flex-1 overflow-y-auto space-y-2"></div>
    <div id="groupsEmpty" class="hidden text-sm text-gray-400 py-3">Not a member of any travel group.</div>

</div>

<!-- Add Family Link Modal -->
<div id="linkModal" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-sm">
        <div class="flex items-center justify-between px-5 py-4 border-b border-gray-200">
            <h3 class="font-semibold text-gray-800">Add Family Link</h3>
            <button onclick="closeAddLinkModal()" class="text-gray-400 hover:text-gray-600"><i class="fas fa-times"></i></button>
        </div>
        <div class="p-5 space-y-3">
            <div>
                <label class="text-xs font-medium text-gray-600 mb-1 block">Search by name</label>
                <input type="text" id="linkSearchInput" placeholder="Type traveler's name..."
                       oninput="onLinkSearchInput()"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                <div id="linkSearchResults" class="hidden mt-1 border border-gray-200 rounded-lg max-h-40 overflow-y-auto"></div>
            </div>
            <div id="linkSelectedTraveler" class="hidden bg-blue-50 border border-blue-200 rounded-lg px-3 py-2 text-sm text-blue-800 flex items-center justify-between">
                <span id="linkSelectedName"></span>
                <button onclick="clearLinkSelection()" class="text-blue-400 hover:text-blue-700"><i class="fas fa-times text-xs"></i></button>
            </div>
            <div>
                <label class="text-xs font-medium text-gray-600 mb-1 block">Relation</label>
                <select id="linkRelationType" onchange="onRelationChange()"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="spouse">Spouse</option>
                    <option value="parent">Parent / Child</option>
                    <option value="sibling">Sibling</option>
                    <option value="other">Other</option>
                </select>
            </div>
            <div id="linkParentDirectionWrap" class="hidden">
                <label class="text-xs font-medium text-gray-600 mb-1 block">Who is the Parent?</label>
                <select id="linkParentDirection" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                    <option value="a">This traveler (current profile) — Parent</option>
                    <option value="b">The selected traveler — Parent</option>
                </select>
            </div>
        </div>
        <div class="flex gap-2 px-5 pb-5">
            <button onclick="saveLink()" class="flex-1 bg-blue-600 text-white py-2 rounded-lg text-sm font-medium hover:bg-blue-700">Save</button>
            <button onclick="closeAddLinkModal()" class="px-4 py-2 border border-gray-300 text-gray-600 rounded-lg text-sm hover:bg-gray-50">Cancel</button>
        </div>
    </div>
</div>

<!-- New/Join Group Modal -->
<div id="groupModal" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-sm">
        <div class="flex items-center justify-between px-5 py-4 border-b border-gray-200">
            <h3 class="font-semibold text-gray-800">Travel Group</h3>
            <button onclick="closeGroupModal()" class="text-gray-400 hover:text-gray-600"><i class="fas fa-times"></i></button>
        </div>
        <div class="p-5 space-y-3">
            <div class="flex gap-2 mb-2">
                <button id="groupModeNewBtn" onclick="setGroupMode('new')"
                        class="flex-1 py-1.5 text-xs rounded-lg border border-blue-500 bg-blue-50 text-blue-700 font-medium">New Group</button>
                <button id="groupModeJoinBtn" onclick="setGroupMode('join')"
                        class="flex-1 py-1.5 text-xs rounded-lg border border-gray-300 text-gray-600">Join Existing</button>
            </div>

            <div id="groupNewFields">
                <label class="text-xs font-medium text-gray-600 mb-1 block">Group Name</label>
                <input type="text" id="groupNameInput" placeholder="e.g. Dubai Trip 2026"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm mb-2 focus:outline-none focus:ring-2 focus:ring-emerald-500">
                <label class="text-xs font-medium text-gray-600 mb-1 block">Description (optional)</label>
                <textarea id="groupDescInput" rows="2"
                          class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm resize-none focus:outline-none focus:ring-2 focus:ring-emerald-500"></textarea>
            </div>

            <div id="groupJoinFields" class="hidden">
                <label class="text-xs font-medium text-gray-600 mb-1 block">Search by group name or sys_id</label>
                <input type="text" id="groupSearchInput" placeholder="Type group name or sys_id..."
                       oninput="onGroupSearchInput()"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500">
                <div id="groupSearchResults" class="hidden mt-1 border border-gray-200 rounded-lg max-h-40 overflow-y-auto"></div>
                <div id="groupSelectedWrap" class="hidden mt-2 bg-emerald-50 border border-emerald-200 rounded-lg px-3 py-2 text-sm text-emerald-800 flex items-center justify-between">
                    <span id="groupSelectedName"></span>
                    <button type="button" onclick="clearGroupSelection()" class="text-emerald-400 hover:text-emerald-700"><i class="fas fa-times text-xs"></i></button>
                </div>
            </div>
        </div>
        <div class="flex gap-2 px-5 pb-5">
            <button onclick="submitGroupModal()" class="flex-1 bg-emerald-600 text-white py-2 rounded-lg text-sm font-medium hover:bg-emerald-700">Save</button>
            <button onclick="closeGroupModal()" class="px-4 py-2 border border-gray-300 text-gray-600 rounded-lg text-sm hover:bg-gray-50">Cancel</button>
        </div>
    </div>
</div>

<!-- Group Members Modal -->
<div id="groupMembersModal" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-sm">
        <div class="flex items-center justify-between px-5 py-4 border-b border-gray-200">
            <h3 class="font-semibold text-gray-800" id="groupMembersTitle">Group Members</h3>
            <button onclick="closeGroupMembersModal()" class="text-gray-400 hover:text-gray-600"><i class="fas fa-times"></i></button>
        </div>
        <div class="p-5">
            <p class="text-xs text-gray-400 mb-2">Group sys_id or name: <span id="groupMembersId" class="font-mono select-all"></span></p>
            <div id="groupMembersListEl" class="space-y-1.5 mb-3 max-h-48 overflow-y-auto"></div>

            <div class="border-t border-gray-100 pt-3 mb-3">
                <label class="text-xs font-medium text-gray-600 mb-1 block">Add a member</label>
                <input type="text" id="addMemberSearchInput" placeholder="Type traveler's name..."
                       oninput="onAddMemberSearchInput()"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500">
                <div id="addMemberSearchResults" class="hidden mt-1 border border-gray-200 rounded-lg max-h-32 overflow-y-auto"></div>
            </div>

            <div class="flex gap-2">
                <button onclick="removeMeFromGroup()" class="flex-1 border border-red-300 text-red-600 py-1.5 rounded-lg text-xs hover:bg-red-50">
                    Leave this Group
                </button>
            </div>
        </div>
    </div>
</div>

<script>
(function() {
    const TRAVELER_ID = <?= json_encode($travelerId) ?>;
    let selectedLinkTraveler = null;
    let selectedJoinGroup = null; // { sys_id, group_name }
    let searchDebounce = null;
    let groupSearchDebounce = null;

    async function loadLinks() {
        try {
            const res  = await fetch(`/api/travelers/traveler-links.php?traveler_id=${TRAVELER_ID}`, { credentials: 'include' });
            const data = await res.json();
            document.getElementById('linksLoading').classList.add('hidden');

            if (!data.success) { document.getElementById('linksEmpty').textContent = 'Error: ' + (data.message || ''); document.getElementById('linksEmpty').classList.remove('hidden'); return; }

            if (!data.links.length) {
                document.getElementById('linksEmpty').classList.remove('hidden');
                return;
            }
            const list = document.getElementById('linksList');
            list.classList.remove('hidden');
            list.innerHTML = data.links.map(l => `
                <div class="flex items-center justify-between bg-white border border-gray-200 rounded-xl px-4 py-2.5">
                    <a href="show-travelers.php?traveler_id=${encodeURIComponent(l.other_traveler.sys_id)}" class="flex items-center gap-2 text-sm text-gray-800 hover:text-blue-600">
                        <i class="fas fa-user-circle text-gray-300"></i>
                        <span class="font-medium">${escHtml(l.other_traveler.name)}</span>
                        <span class="text-xs bg-gray-100 text-gray-600 px-2 py-0.5 rounded-full">${escHtml(l.relation_label)}</span>
                    </a>
                    <button onclick="unlinkTraveler('${l.link_sys_id}')" class="text-gray-300 hover:text-red-500">
                        <i class="fas fa-unlink text-xs"></i>
                    </button>
                </div>
            `).join('');
        } catch (err) {
            document.getElementById('linksLoading').classList.add('hidden');
        }
    }

    window.unlinkTraveler = function(linkSysId) {
        if (!confirm('Delete this link?')) return;
        fetch(`/api/travelers/traveler-links.php?sys_id=${encodeURIComponent(linkSysId)}`, { method: 'DELETE', credentials: 'include' })
            .then(r => r.json())
            .then(res => { if (res.success) loadLinks(); else alert('Failed: ' + (res.message || '')); });
    };

    window.openAddLinkModal = function() {
        clearLinkSelection();
        document.getElementById('linkSearchInput').value = '';
        document.getElementById('linkSearchResults').classList.add('hidden');
        document.getElementById('linkRelationType').value = 'spouse';
        onRelationChange();
        document.getElementById('linkModal').classList.remove('hidden');
    };
    window.closeAddLinkModal = () => document.getElementById('linkModal').classList.add('hidden');

    window.onRelationChange = function() {
        document.getElementById('linkParentDirectionWrap').classList.toggle(
            'hidden', document.getElementById('linkRelationType').value !== 'parent'
        );
    };

    window.onLinkSearchInput = function() {
        clearTimeout(searchDebounce);
        const q = document.getElementById('linkSearchInput').value.trim();
        const box = document.getElementById('linkSearchResults');
        if (q.length < 2) { box.classList.add('hidden'); return; }

        searchDebounce = setTimeout(async () => {
            const res  = await fetch(`/api/travelers/search-travelers.php?q=${encodeURIComponent(q)}&exclude=${TRAVELER_ID}`);
            const data = await res.json();
            if (!data.success || !data.travelers.length) {
                box.innerHTML = `<div class="px-3 py-2 text-xs text-gray-400">No one found</div>`;
                box.classList.remove('hidden');
                return;
            }
            box.innerHTML = data.travelers.map(t => `
                <div class="px-3 py-2 text-sm hover:bg-gray-50 cursor-pointer" onclick='selectLinkTraveler(${JSON.stringify(t)})'>
                    ${escHtml(t.name)} <span class="text-[10px] text-gray-400">${escHtml(t.sys_id)}</span>
                </div>
            `).join('');
            box.classList.remove('hidden');
        }, 300);
    };

    window.selectLinkTraveler = function(t) {
        selectedLinkTraveler = t;
        document.getElementById('linkSelectedName').textContent = t.name;
        document.getElementById('linkSelectedTraveler').classList.remove('hidden');
        document.getElementById('linkSearchResults').classList.add('hidden');
        document.getElementById('linkSearchInput').value = '';
    };
    window.clearLinkSelection = function() {
        selectedLinkTraveler = null;
        document.getElementById('linkSelectedTraveler').classList.add('hidden');
    };

    window.saveLink = async function() {
        if (!selectedLinkTraveler) { alert('Please select a traveler first'); return; }
        const relation = document.getElementById('linkRelationType').value;
        const body = { traveler_a: TRAVELER_ID, traveler_b: selectedLinkTraveler.sys_id, relation_type: relation };

        if (relation === 'parent') {
            const dir = document.getElementById('linkParentDirection').value;
            body.parent_is_a = (dir === 'a');
        }

        const res = await fetch('/api/travelers/traveler-links.php', {
            method: 'POST', headers: { 'Content-Type': 'application/json' }, credentials: 'include',
            body: JSON.stringify(body)
        });
        const data = await res.json();
        if (!data.success) { alert('Failed: ' + (data.message || '')); return; }
        closeAddLinkModal();
        loadLinks();
    };

    async function loadGroups() {
        try {
            const res  = await fetch(`/api/travelers/traveler-groups.php?traveler_id=${TRAVELER_ID}`, { credentials: 'include' });
            const data = await res.json();
            document.getElementById('groupsLoading').classList.add('hidden');

            if (!data.success) return;

            if (!data.groups.length) {
                document.getElementById('groupsEmpty').classList.remove('hidden');
                return;
            }
            const list = document.getElementById('groupsList');
            list.classList.remove('hidden');
            list.innerHTML = data.groups.map(g => `
                <div class="bg-white border border-gray-200 rounded-xl px-4 py-3 cursor-pointer hover:border-emerald-300"
                     onclick="openGroupMembers('${g.sys_id}', '${escHtml(g.group_name)}')">
                    <div class="flex items-center justify-between">
                        <span class="font-medium text-sm text-gray-800">
                            ${escHtml(g.group_name)}
                            ${g.linked_work_id ? '<span class="text-[9px] bg-blue-50 text-blue-600 px-1.5 py-0.5 rounded-full ml-1">Work</span>' : ''}
                        </span>
                        <span class="text-xs bg-emerald-50 text-emerald-700 px-2 py-0.5 rounded-full">${g.member_count} members</span>
                    </div>
                    ${g.description ? `<p class="text-xs text-gray-400 mt-1">${escHtml(g.description)}</p>` : ''}
                    <p class="text-[10px] text-gray-300 mt-1 font-mono">${escHtml(g.sys_id)}</p>
                </div>
            `).join('');
        } catch (err) {
            document.getElementById('groupsLoading').classList.add('hidden');
        }
    }

    let groupMode = 'new';
    window.openGroupModal  = () => {
        setGroupMode('new');
        clearGroupSelection();
        document.getElementById('groupSearchInput').value = '';
        document.getElementById('groupSearchResults').classList.add('hidden');
        document.getElementById('groupNameInput').value = '';
        document.getElementById('groupDescInput').value = '';
        document.getElementById('groupModal').classList.remove('hidden');
    };
    window.closeGroupModal = () => document.getElementById('groupModal').classList.add('hidden');

    window.setGroupMode = function(mode) {
        groupMode = mode;
        document.getElementById('groupNewFields').classList.toggle('hidden', mode !== 'new');
        document.getElementById('groupJoinFields').classList.toggle('hidden', mode !== 'join');
        document.getElementById('groupModeNewBtn').className = mode === 'new'
            ? 'flex-1 py-1.5 text-xs rounded-lg border border-blue-500 bg-blue-50 text-blue-700 font-medium'
            : 'flex-1 py-1.5 text-xs rounded-lg border border-gray-300 text-gray-600';
        document.getElementById('groupModeJoinBtn').className = mode === 'join'
            ? 'flex-1 py-1.5 text-xs rounded-lg border border-emerald-500 bg-emerald-50 text-emerald-700 font-medium'
            : 'flex-1 py-1.5 text-xs rounded-lg border border-gray-300 text-gray-600';
        if (mode === 'new') clearGroupSelection();
    };

    window.onGroupSearchInput = function() {
        clearTimeout(groupSearchDebounce);
        const q = document.getElementById('groupSearchInput').value.trim();
        const box = document.getElementById('groupSearchResults');
        if (q.length < 2) { box.classList.add('hidden'); return; }

        groupSearchDebounce = setTimeout(async () => {
            const res  = await fetch(`/api/travelers/search-groups.php?q=${encodeURIComponent(q)}`, { credentials: 'include' });
            const data = await res.json();
            if (!data.success) {
                box.innerHTML = `<div class="px-3 py-2 text-xs text-red-500">Error: ${escHtml(data.message || 'Search failed')}</div>`;
                box.classList.remove('hidden');
                return;
            }
            if (!data.groups.length) {
                box.innerHTML = `<div class="px-3 py-2 text-xs text-gray-400">No groups found</div>`;
                box.classList.remove('hidden');
                return;
            }
            box.innerHTML = data.groups.map(g => `
                <div class="px-3 py-2 text-sm hover:bg-gray-50 cursor-pointer" onclick='selectJoinGroup(${JSON.stringify(g)})'>
                    ${escHtml(g.group_name)}
                    ${g.linked_work_id ? '<span class="text-[9px] bg-blue-50 text-blue-600 px-1.5 py-0.5 rounded-full ml-1">Work</span>' : ''}
                    <span class="text-[10px] text-gray-400">${escHtml(g.sys_id)} · ${g.member_count} members</span>
                </div>
            `).join('');
            box.classList.remove('hidden');
        }, 300);
    };

    window.selectJoinGroup = function(g) {
        selectedJoinGroup = g;
        document.getElementById('groupSelectedName').textContent = `${g.group_name} (${g.sys_id})`;
        document.getElementById('groupSelectedWrap').classList.remove('hidden');
        document.getElementById('groupSearchResults').classList.add('hidden');
        document.getElementById('groupSearchInput').value = '';
    };
    window.clearGroupSelection = function() {
        selectedJoinGroup = null;
        document.getElementById('groupSelectedWrap').classList.add('hidden');
    };

    window.submitGroupModal = async function() {
        if (groupMode === 'new') {
            const name = document.getElementById('groupNameInput').value.trim();
            const desc = document.getElementById('groupDescInput').value.trim();
            if (!name) { alert('Please enter a group name'); return; }

            const res = await fetch('/api/travelers/traveler-groups.php', {
                method: 'POST', headers: { 'Content-Type': 'application/json' }, credentials: 'include',
                body: JSON.stringify({ action: 'create_group', group_name: name, description: desc, traveler_id: TRAVELER_ID })
            });
            const data = await res.json();
            if (!data.success) { alert('Failed: ' + (data.message || '')); return; }
        } else {
            if (!selectedJoinGroup) { alert('Please select a group'); return; }

            const res = await fetch('/api/travelers/traveler-groups.php', {
                method: 'POST', headers: { 'Content-Type': 'application/json' }, credentials: 'include',
                body: JSON.stringify({ action: 'add_member', group_id: selectedJoinGroup.sys_id, traveler_id: TRAVELER_ID })
            });
            const data = await res.json();
            if (!data.success) { alert('Failed: ' + (data.message || '')); return; }
        }
        closeGroupModal();
        loadGroups();
    };

    let currentGroupMembersId = null;
    let addMemberSearchDebounce = null;

    window.openGroupMembers = async function(groupId, groupName) {
        currentGroupMembersId = groupId;
        document.getElementById('groupMembersTitle').textContent = groupName;
        document.getElementById('groupMembersId').textContent = groupId;
        document.getElementById('addMemberSearchInput').value = '';
        document.getElementById('addMemberSearchResults').classList.add('hidden');
        document.getElementById('groupMembersModal').classList.remove('hidden');
        await refreshGroupMembersList();
    };

    // Modal খোলা রেখেই list রিফ্রেশ করার জন্য আলাদা ফাংশন — add member করার
    // পরও এটাই কল হবে, পুরো modal আবার খুলতে হবে না
    async function refreshGroupMembersList() {
        document.getElementById('groupMembersListEl').innerHTML = '<span class="text-xs text-gray-400">Loading...</span>';

        const res  = await fetch(`/api/travelers/traveler-groups.php?action=members&group_id=${encodeURIComponent(currentGroupMembersId)}`, { credentials: 'include' });
        const data = await res.json();
        if (!data.success) { document.getElementById('groupMembersListEl').innerHTML = 'Error'; return; }

        document.getElementById('groupMembersListEl').innerHTML = data.members.map(m => `
            <div class="flex items-center justify-between px-2 py-1 rounded hover:bg-gray-50 group">
                <a href="show-travelers.php?traveler_id=${encodeURIComponent(m.sys_id)}"
                   class="flex-1 text-sm text-gray-700 hover:text-blue-600">
                    <i class="fas fa-user-circle text-gray-300 mr-1"></i> ${escHtml(m.name)}
                </a>
                <button onclick="removeMemberFromGroup('${m.sys_id}')" title="Remove from group"
                        class="text-gray-300 hover:text-red-500 opacity-0 group-hover:opacity-100 transition-opacity">
                    <i class="fas fa-times text-xs"></i>
                </button>
            </div>
        `).join('') || '<span class="text-xs text-gray-400">No members yet</span>';
    }

    window.closeGroupMembersModal = () => document.getElementById('groupMembersModal').classList.add('hidden');

    // ── Remove any member from group (not just self) ────────────────────────
    window.removeMemberFromGroup = async function(travelerSysId) {
        if (!confirm('Remove this traveler from the group?')) return;

        const res = await fetch('/api/travelers/traveler-groups.php', {
            method: 'POST', headers: { 'Content-Type': 'application/json' }, credentials: 'include',
            body: JSON.stringify({ action: 'remove_member', group_id: currentGroupMembersId, traveler_id: travelerSysId })
        });
        const data = await res.json();
        if (!data.success) { alert('Failed: ' + (data.message || '')); return; }

        await refreshGroupMembersList();
        loadGroups();
    };

    // ── Add member (নাম দিয়ে খুঁজে সরাসরি group-এ add) ─────────────────────
    window.onAddMemberSearchInput = function() {
        clearTimeout(addMemberSearchDebounce);
        const q = document.getElementById('addMemberSearchInput').value.trim();
        const box = document.getElementById('addMemberSearchResults');
        if (q.length < 2) { box.classList.add('hidden'); return; }

        addMemberSearchDebounce = setTimeout(async () => {
            // ইতিমধ্যে group-এর সদস্য না — তাই এই group-এর জন্য exclude নেই,
            // কিন্তু নিজেকে exclude করা দরকার
            const res  = await fetch(`/api/travelers/search-travelers.php?q=${encodeURIComponent(q)}&exclude=${TRAVELER_ID}`);
            const data = await res.json();
            if (!data.success || !data.travelers.length) {
                box.innerHTML = `<div class="px-3 py-2 text-xs text-gray-400">No one found</div>`;
                box.classList.remove('hidden');
                return;
            }
            box.innerHTML = data.travelers.map(t => `
                <div class="px-3 py-2 text-sm hover:bg-gray-50 cursor-pointer" onclick='addMemberToGroup(${JSON.stringify(t)})'>
                    ${escHtml(t.name)} <span class="text-[10px] text-gray-400">${escHtml(t.sys_id)}</span>
                </div>
            `).join('');
            box.classList.remove('hidden');
        }, 300);
    };

    window.addMemberToGroup = async function(t) {
        document.getElementById('addMemberSearchResults').classList.add('hidden');
        document.getElementById('addMemberSearchInput').value = '';

        const res = await fetch('/api/travelers/traveler-groups.php', {
            method: 'POST', headers: { 'Content-Type': 'application/json' }, credentials: 'include',
            body: JSON.stringify({ action: 'add_member', group_id: currentGroupMembersId, traveler_id: t.sys_id })
        });
        const data = await res.json();
        if (!data.success) { alert('Failed: ' + (data.message || '')); return; }

        await refreshGroupMembersList();
        loadGroups(); // member count আপডেট হওয়ার জন্য group card গুলো রিফ্রেশ
    };

    window.removeMeFromGroup = async function() {
        if (!currentGroupMembersId) return;
        if (!confirm('Are you sure you want to leave this group?')) return;

        const res = await fetch('/api/travelers/traveler-groups.php', {
            method: 'POST', headers: { 'Content-Type': 'application/json' }, credentials: 'include',
            body: JSON.stringify({ action: 'remove_member', group_id: currentGroupMembersId, traveler_id: TRAVELER_ID })
        });
        const data = await res.json();
        if (!data.success) { alert('Failed: ' + (data.message || '')); return; }
        closeGroupMembersModal();
        loadGroups();
    };

    function escHtml(s) {
        return String(s ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
    }

    loadLinks();
    loadGroups();
})();
</script>