<?php if ($canManagePerms): ?>
// ── §14  PERMISSIONS PANEL ────────────────────────────────────────────────────
(function () {
    const IS_SUPER     = <?= $myRole === '0' ? 'true' : 'false' ?>;
    const LIST_URL     = API_BASE + '/api/permissions/list.php';
    const TOGGLE_URL   = API_BASE + '/api/permissions/toggle.php';

    let permCatalog   = {};   // { group: { key: label } }
    let permEmployees = [];   // raw array from API
    let permFiltered  = [];   // after search/dept filter
    let permSelected  = null; // current employee object (mutable, we patch .permissions)
    let permDepts     = [];   // unique dept names

    // ── Load data ────────────────────────────────────────────────────────────
    async function permLoad() {
        document.getElementById('permEmpLoading').style.display = '';
        document.getElementById('permEmpList').innerHTML = '';
        try {
            const r = await fetch(LIST_URL, { credentials: 'same-origin' });
            const j = await r.json();
            if (!j.success) throw new Error(j.message || 'Failed to load');

            permCatalog   = j.catalog || {};
            permEmployees = j.employees || [];
            permFiltered  = permEmployees.slice();

            // Stats
            document.getElementById('permStatTotal').textContent  = j.total_count || 0;
            document.getElementById('permStatMaster').textContent = j.master_count || 0;
            const anyCount = permEmployees.filter(e => e.permissions && e.permissions.length > 0).length;
            document.getElementById('permStatAny').textContent    = anyCount;

            // Department dropdown
            permDepts = [...new Set(permEmployees.map(e => e.department_name || 'No Department'))].sort();
            const deptSel = document.getElementById('permDeptFilter');
            deptSel.innerHTML = '<option value="">All Departments</option>' +
                permDepts.map(d => `<option value="${esc(d)}">${esc(d)}</option>`).join('');

            document.getElementById('permEmpLoading').style.display = 'none';
            permRenderList();

            // Auto-select first employee so the grid is immediately visible
            if (permFiltered.length > 0) {
                permSelectEmployee(permFiltered[0].sys_id);
            }
        } catch (err) {
            document.getElementById('permEmpLoading').innerHTML =
                `<i class="fas fa-exclamation-circle text-red-400 mr-1"></i><span class="text-red-500">${esc(err.message)}</span>`;
        }
    }

    // ── Filter + render employee list ────────────────────────────────────────
    function permFilterEmployees() {
        const q    = (document.getElementById('permEmpSearch').value || '').toLowerCase();
        const dept = document.getElementById('permDeptFilter').value;
        permFiltered = permEmployees.filter(e => {
            const nameMatch = (e.name || '').toLowerCase().includes(q);
            const deptMatch = !dept || (e.department_name || 'No Department') === dept;
            return nameMatch && deptMatch;
        });
        permRenderList();
    }
    window.permFilterEmployees = permFilterEmployees;

    function permRenderList() {
        const el = document.getElementById('permEmpList');
        if (!permFiltered.length) {
            el.innerHTML = '<p class="text-xs text-gray-400 text-center py-4">No employees found</p>';
            return;
        }

        // Group by department
        const grouped = {};
        permFiltered.forEach(e => {
            const d = e.department_name || 'No Department';
            (grouped[d] = grouped[d] || []).push(e);
        });

        let html = '';
        Object.entries(grouped).forEach(([dept, emps]) => {
            html += `<p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest px-1 mt-2 mb-1">${esc(dept)}</p>`;
            emps.forEach(e => {
                const isSel     = permSelected && permSelected.sys_id === e.sys_id;
                const hasMaster = (e.permissions || []).includes('full_accounting_access');
                const count     = (e.permissions || []).filter(k => k !== 'full_accounting_access').length;
                const initials  = (e.name || '?').split(' ').map(w => w[0]).slice(0, 2).join('').toUpperCase();

                html += `
                <button onclick="permSelectEmployee('${esc(e.sys_id)}')"
                    class="w-full flex items-center gap-2 px-2 py-2 rounded-lg text-left transition-colors ${isSel ? 'bg-purple-50 border border-purple-200' : 'hover:bg-gray-50'}">
                    <div class="w-7 h-7 rounded-full flex-shrink-0 flex items-center justify-center text-white text-[10px] font-bold
                        ${hasMaster ? 'bg-gradient-to-br from-purple-500 to-indigo-600' : 'bg-gradient-to-br from-gray-400 to-gray-500'}">
                        ${esc(initials)}
                    </div>
                    <div class="min-w-0 flex-1">
                        <div class="text-xs font-medium text-gray-800 truncate">${esc(e.name || '—')}</div>
                        <div class="text-[10px] text-gray-400 truncate">${hasMaster
                            ? '<span class="text-purple-600 font-semibold">Full Access</span>'
                            : (count > 0 ? `${count} perm${count !== 1 ? 's' : ''}` : '<span class="text-gray-300">No permissions</span>')
                        }</div>
                    </div>
                    ${isSel ? '<i class="fas fa-chevron-right text-purple-400 text-[10px]"></i>' : ''}
                </button>`;
            });
        });
        el.innerHTML = html;
    }
    window.permRenderList = permRenderList;

    // ── Select employee ───────────────────────────────────────────────────────
    window.permSelectEmployee = function (sysId) {
        permSelected = permEmployees.find(e => e.sys_id === sysId) || null;
        if (!permSelected) return;

        // Refresh list to show selection
        permRenderList();

        // Show grid
        document.getElementById('permNoSelection').classList.add('hidden');
        document.getElementById('permGrid').classList.remove('hidden');

        // Avatar + name
        const initials = (permSelected.name || '?').split(' ').map(w => w[0]).slice(0, 2).join('').toUpperCase();
        document.getElementById('permSelAvatar').textContent = initials;
        document.getElementById('permSelName').textContent   = permSelected.name || '—';
        document.getElementById('permSelDept').textContent   = permSelected.department_name || 'No Department';

        // Master toggle (super admin only)
        if (IS_SUPER) {
            const hasMaster = (permSelected.permissions || []).includes('full_accounting_access');
            const mt = document.getElementById('permMasterToggle');
            if (mt) mt.checked = hasMaster;
        }

        permRenderGrid();
        permUpdateBadge();
        document.getElementById('permSaveNotice').classList.add('hidden');
    };

    // ── Render permission group checkboxes ───────────────────────────────────
    function permRenderGrid() {
        const container = document.getElementById('permGroupsContainer');
        const granted   = new Set(permSelected.permissions || []);
        const hasMaster = granted.has('full_accounting_access');

        let html = '';
        Object.entries(permCatalog).forEach(([group, perms]) => {
            const keys       = Object.keys(perms);
            const groupCount = keys.filter(k => granted.has(k)).length;

            html += `
            <div class="bg-white rounded-2xl shadow p-4">
                <div class="flex items-center justify-between mb-3">
                    <h3 class="text-xs font-bold text-gray-700 uppercase tracking-wide">${esc(group)}</h3>
                    <span class="text-[10px] text-gray-400">${groupCount}/${keys.length}</span>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">`;

            keys.forEach(key => {
                const label   = perms[key];
                const checked = granted.has(key) || hasMaster;
                const disabled = hasMaster; // if master switch on, all implied — disable individual

                html += `
                <label class="flex items-start gap-2 cursor-pointer group ${disabled ? 'opacity-60' : ''}">
                    <input type="checkbox" data-pkey="${esc(key)}"
                        class="perm-chk mt-0.5 w-4 h-4 rounded text-purple-500 border-gray-300 focus:ring-purple-400 cursor-pointer flex-shrink-0"
                        ${checked ? 'checked' : ''} ${disabled ? 'disabled' : ''}
                        onchange="permToggleOne(this, '${esc(key)}')">
                    <span class="text-xs text-gray-700 group-hover:text-gray-900 leading-snug">${esc(label)}</span>
                </label>`;
            });

            html += `</div></div>`;
        });

        container.innerHTML = html;
    }

    // ── Toggle single permission ─────────────────────────────────────────────
    window.permToggleOne = async function (chk, key) {
        if (!permSelected) return;
        chk.disabled = true;

        const grant = chk.checked;
        const ok    = await permCallToggle([key], grant);
        if (!ok) {
            chk.checked = !grant; // revert
        } else {
            // Patch local state
            const perms = permSelected.permissions || [];
            if (grant) {
                if (!perms.includes(key)) perms.push(key);
            } else {
                const idx = perms.indexOf(key);
                if (idx !== -1) perms.splice(idx, 1);
            }
            permSelected.permissions = perms;
            // Sync in master array
            const emp = permEmployees.find(e => e.sys_id === permSelected.sys_id);
            if (emp) emp.permissions = perms;
            permUpdateBadge();
            permShowSaved();
        }
        chk.disabled = false;
    };

    // ── Toggle master switch (super admin only) ──────────────────────────────
    window.permToggleMaster = async function (grant) {
        if (!permSelected || !IS_SUPER) return;
        const mt = document.getElementById('permMasterToggle');
        if (mt) mt.disabled = true;

        const ok = await permCallToggle(['full_accounting_access'], grant);
        if (!ok) {
            if (mt) { mt.checked = !grant; mt.disabled = false; }
            return;
        }
        // Patch local state
        const perms = permSelected.permissions || [];
        if (grant) {
            if (!perms.includes('full_accounting_access')) perms.push('full_accounting_access');
        } else {
            const idx = perms.indexOf('full_accounting_access');
            if (idx !== -1) perms.splice(idx, 1);
        }
        permSelected.permissions = perms;
        const emp = permEmployees.find(e => e.sys_id === permSelected.sys_id);
        if (emp) { emp.permissions = perms; emp.has_master = grant; }

        // Update stats
        const masterCount = permEmployees.filter(e => (e.permissions || []).includes('full_accounting_access')).length;
        document.getElementById('permStatMaster').textContent = masterCount;

        permRenderGrid(); // re-render to grey-out / restore individual checkboxes
        permUpdateBadge();
        permShowSaved();
        if (mt) mt.disabled = false;
    };

    // ── Grant all / Revoke all individual permissions ────────────────────────
    window.permGrantAll = async function () {
        if (!permSelected) return;
        const allKeys = [];
        Object.values(permCatalog).forEach(grp => allKeys.push(...Object.keys(grp)));
        const ok = await permCallToggle(allKeys, true);
        if (!ok) return;
        const perms = [...new Set([...(permSelected.permissions || []), ...allKeys])];
        permSelected.permissions = perms;
        const emp = permEmployees.find(e => e.sys_id === permSelected.sys_id);
        if (emp) emp.permissions = perms;
        permRenderGrid();
        permUpdateBadge();
        permShowSaved();
    };

    window.permRevokeAll = async function () {
        if (!permSelected) return;
        const allKeys = [];
        Object.values(permCatalog).forEach(grp => allKeys.push(...Object.keys(grp)));
        const ok = await permCallToggle(allKeys, false);
        if (!ok) return;
        // Keep full_accounting_access if present (can only be removed via master toggle by super admin)
        const remaining = (permSelected.permissions || []).filter(k => k === 'full_accounting_access');
        permSelected.permissions = remaining;
        const emp = permEmployees.find(e => e.sys_id === permSelected.sys_id);
        if (emp) emp.permissions = remaining;
        permRenderGrid();
        permUpdateBadge();
        permShowSaved();
    };

    // ── API call helper ───────────────────────────────────────────────────────
    async function permCallToggle(keys, grant) {
        try {
            const r = await fetch(TOGGLE_URL, {
                method: 'POST',
                credentials: 'same-origin',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    employee_sys_id: permSelected.sys_id,
                    permission_keys: keys,
                    grant: grant
                })
            });
            const j = await r.json();
            if (!j.success) {
                alert(j.message || 'Failed to update permission');
                return false;
            }
            return true;
        } catch {
            alert('Network error — please try again');
            return false;
        }
    }

    // ── Badge showing granted count ───────────────────────────────────────────
    function permUpdateBadge() {
        const badge  = document.getElementById('permSelGrantedBadge');
        const perms  = permSelected ? (permSelected.permissions || []) : [];
        const hasMaster = perms.includes('full_accounting_access');
        const count  = perms.filter(k => k !== 'full_accounting_access').length;

        if (hasMaster) {
            badge.textContent = 'Full Access';
            badge.className   = 'text-[11px] font-semibold px-2 py-0.5 rounded-full bg-purple-50 text-purple-600 border border-purple-200';
            badge.classList.remove('hidden');
        } else if (count > 0) {
            badge.textContent = `${count} permission${count !== 1 ? 's' : ''}`;
            badge.className   = 'text-[11px] font-semibold px-2 py-0.5 rounded-full bg-blue-50 text-blue-600 border border-blue-200';
            badge.classList.remove('hidden');
        } else {
            badge.classList.add('hidden');
        }

        // Update list item
        permRenderList();
    }

    function permShowSaved() {
        const n = document.getElementById('permSaveNotice');
        n.classList.remove('hidden');
        clearTimeout(n._t);
        n._t = setTimeout(() => n.classList.add('hidden'), 3000);
    }

    // ── Auto-load when section becomes visible ───────────────────────────────
    let permLoaded = false;
    document.addEventListener('click', function (e) {
        const btn = e.target.closest('[data-mpsec="permissions"]');
        if (btn && !permLoaded) {
            permLoaded = true;
            permLoad();
        }
    });

    // Helper (shared from outer scope)
    function esc(s) {
        return String(s ?? '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
    }
})();
<?php endif; ?>