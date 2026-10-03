    /* ══════════════════════════════════════════
       8. WIFI CHECK-IN / CHECK-OUT
    ══════════════════════════════════════════ */
    let mpTodayStatus = null;

    async function loadTodayStatus(autoCheckIn = false) {
        try {
            const data = await apiPost('/api/attendance/endpoints.php', {action:'today_status'});
            mpTodayStatus = data;
            renderCheckinWidget(data);

            // Auto check-in: only if not weekend, not holiday, not already checked in
            if (autoCheckIn && !data.is_weekend && !data.is_holiday
                && data.att_status === 'not_marked' && !data.check_in) {
                const ci = await apiPost('/api/attendance/endpoints.php', {action:'checkin'});
                if (ci.success) {
                    const data2 = await apiPost('/api/attendance/endpoints.php', {action:'today_status'});
                    mpTodayStatus = data2;
                    renderCheckinWidget(data2);
                }
            }
        } catch(_) {}
    }

    function fmtDuration(secs) {
        const h = Math.floor(secs/3600);
        const m = Math.floor((secs%3600)/60);
        return h > 0 ? `${h}h ${m}m` : `${m}m`;
    }

    function renderCheckinWidget(d) {
        const statusEl  = document.getElementById('mpCheckinStatus');
        const timeEl    = document.getElementById('mpCheckinTime');
        const signOutBtn= document.getElementById('mpSignOutBtn');
        const ciManual  = document.getElementById('mpCheckInManualBtn');

        if (!d || d.is_weekend || d.is_holiday) {
            statusEl.textContent = d?.is_holiday ? '🎉 ' + (d.holiday_title||'Holiday') : '🌴 Weekend';
            timeEl.textContent   = '';
            signOutBtn.classList.add('hidden');
            ciManual.classList.add('hidden');
            return;
        }

        if (d.check_out) {
            statusEl.textContent = '✅ Checked Out';
            timeEl.textContent   = d.check_in + ' → ' + d.check_out;
            signOutBtn.classList.add('hidden');
            ciManual.classList.add('hidden');
        } else if (d.check_in) {
            statusEl.textContent = '🟢 Checked In';
            timeEl.textContent   = 'Since ' + d.check_in;
            signOutBtn.classList.remove('hidden');
            ciManual.classList.add('hidden');
        } else if (d.att_status === 'on_leave') {
            statusEl.textContent = '🏖️ On Leave';
            timeEl.textContent   = d.leave_name || '';
            signOutBtn.classList.add('hidden');
            ciManual.classList.add('hidden');
        } else {
            statusEl.textContent = '⚪ Not Checked In';
            timeEl.textContent   = '';
            signOutBtn.classList.add('hidden');
            ciManual.classList.remove('hidden');
        }
    }

    // Manual check-in button
    document.getElementById('mpCheckInManualBtn').addEventListener('click', async function() {
        this.disabled = true; this.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
        try {
            const ci = await apiPost('/api/attendance/endpoints.php', {action:'checkin'});
            if (ci.success) {
                await loadTodayStatus(false);
            } else { alert(ci.message||'Check-in failed.'); }
        } catch(_){ alert('Network error.'); }
        this.disabled = false; this.innerHTML = '<i class="fas fa-fingerprint mr-1"></i> Check In';
    });

    // Sign out button → show confirm modal
    document.getElementById('mpSignOutBtn').addEventListener('click', function() {
        const dur = document.getElementById('mpSignOutDuration');
        if (mpTodayStatus?.check_in) {
            const now  = new Date();
            const ci   = mpTodayStatus.check_in.split(':');
            const then = new Date();
            then.setHours(parseInt(ci[0]), parseInt(ci[1]), parseInt(ci[2]||0));
            const secs = Math.max(0, Math.floor((now - then) / 1000));
            dur.textContent = `আজকে ${fmtDuration(secs)} কাজ করেছ — বের হচ্ছ?`;
        } else { dur.textContent = 'Confirm sign out for today?'; }
        document.getElementById('mpSignOutModal').style.display = 'flex';
    });

    document.getElementById('mpSignOutCancel').addEventListener('click', () => {
        document.getElementById('mpSignOutModal').style.display = 'none';
    });

    document.getElementById('mpSignOutConfirm').addEventListener('click', async function() {
        this.disabled = true; this.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
        try {
            const co = await apiPost('/api/attendance/endpoints.php', {action:'checkout'});
            document.getElementById('mpSignOutModal').style.display = 'none';
            if (co.success) {
                await loadTodayStatus(false);
                const worked = co.worked_seconds ? fmtDuration(co.worked_seconds) : '';
                if (worked) alert('✅ Signed out! আজকে ' + worked + ' কাজ করেছ।');
            } else { alert(co.message||'Check-out failed.'); }
        } catch(_){ alert('Network error.'); }
        this.disabled = false; this.innerHTML = '<i class="fas fa-check mr-1.5"></i> Confirm';
    });

    // Load today status + auto check-in on page load
    loadTodayStatus(true);

    /* ══════════════════════════════════════════
       9. CREDENTIALS MODULE
    ══════════════════════════════════════════ */
    let mpCredLoaded = false;

    async function loadCredentials() {
        const el = document.getElementById('mpCredList');
        try {
            const data = await apiPost('/api/credentials/endpoints.php', {action:'list'});
            if (!data.success) { el.innerHTML='<p class="text-sm text-red-500">'+data.message+'</p>'; return; }
            if (!data.data.length) {
                el.innerHTML=`<div class="text-center py-10 text-gray-400 text-sm">
                    <i class="fas fa-key text-4xl mb-3 block text-gray-200"></i>
                    No credentials saved yet. Click <strong>Add</strong> to save your first one.
                </div>`;
                return;
            }
            el.innerHTML = data.data.map(c => `
                <div class="flex items-center gap-3 p-3 rounded-xl border border-gray-100 hover:bg-gray-50 transition">
                    <div class="w-10 h-10 rounded-xl bg-indigo-50 flex items-center justify-center flex-shrink-0">
                        <i class="fas fa-globe text-indigo-400 text-sm"></i>
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="font-semibold text-sm text-gray-800 truncate">${escHtml(c.title)}</div>
                        <div class="text-xs text-gray-400 truncate">${c.username ? escHtml(c.username) : (c.url ? escHtml(c.url) : 'No username')}</div>
                    </div>
                    <div class="flex gap-2 flex-shrink-0">
                        <button class="w-8 h-8 rounded-lg hover:bg-indigo-50 text-gray-400 hover:text-indigo-600 transition flex items-center justify-center text-sm"
                                onclick="mpEditCred('${c.sys_id}')" title="View / Edit">
                            <i class="fas fa-pen"></i>
                        </button>
                        <button class="w-8 h-8 rounded-lg hover:bg-red-50 text-gray-400 hover:text-red-500 transition flex items-center justify-center text-sm"
                                onclick="mpDeleteCred('${c.sys_id}','${escHtml(c.title)}')" title="Delete">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>
            `).join('');
        } catch(_) { el.innerHTML='<p class="text-sm text-red-500">Failed to load.</p>'; }
    }

    function escHtml(s){ return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;'); }

    const credModal      = document.getElementById('mpCredModal');
    const credModalClose = document.getElementById('mpCredModalClose');
    credModalClose.addEventListener('click', () => { credModal.style.display='none'; });
    credModal.addEventListener('click', e => { if(e.target===credModal) credModal.style.display='none'; });

    document.getElementById('mpCredAddBtn').addEventListener('click', () => {
        document.getElementById('mpCredModalTitle').innerHTML = '<i class="fas fa-key text-indigo-500"></i> Add Credential';
        document.getElementById('mpCredSysId').value = '';
        ['mpCredTitle','mpCredUrl','mpCredUsername','mpCredPassword','mpCredNotes'].forEach(id => document.getElementById(id).value='');
        document.getElementById('mpCredFormAlert').classList.add('hidden');
        credModal.style.display = 'flex';
    });

    document.getElementById('mpCredPwEye').addEventListener('click', function() {
        const inp = document.getElementById('mpCredPassword');
        const toText = inp.type === 'password';
        inp.type = toText ? 'text' : 'password';
        this.querySelector('i').className = toText ? 'fas fa-eye-slash text-sm' : 'fas fa-eye text-sm';
    });

    window.mpEditCred = async function(sysId) {
        try {
            const data = await apiPost('/api/credentials/endpoints.php', {action:'get', sys_id:sysId});
            if (!data.success) { alert(data.message||'Failed.'); return; }
            const c = data.data;
            document.getElementById('mpCredModalTitle').innerHTML = '<i class="fas fa-pen text-indigo-500"></i> Edit Credential';
            document.getElementById('mpCredSysId').value    = c.sys_id;
            document.getElementById('mpCredTitle').value    = c.title    || '';
            document.getElementById('mpCredUrl').value      = c.url      || '';
            document.getElementById('mpCredUsername').value = c.username || '';
            document.getElementById('mpCredPassword').value = c.password || '';
            document.getElementById('mpCredNotes').value    = c.notes    || '';
            document.getElementById('mpCredFormAlert').classList.add('hidden');
            credModal.style.display = 'flex';
        } catch(_){ alert('Network error.'); }
    };

    window.mpDeleteCred = async function(sysId, title) {
        if (!confirm('Delete credential "' + title + '"?')) return;
        try {
            const data = await apiPost('/api/credentials/endpoints.php', {action:'delete', sys_id:sysId});
            if (data.success) { await loadCredentials(); }
            else { alert(data.message||'Failed.'); }
        } catch(_){ alert('Network error.'); }
    };

    document.getElementById('mpCredSaveBtn').addEventListener('click', async function() {
        const alertEl = document.getElementById('mpCredFormAlert');
        const showA   = (msg, ok) => {
            alertEl.textContent = msg;
            alertEl.className = 'mb-4 p-3 rounded-lg text-sm font-medium '+(ok?'bg-green-50 text-green-700 border border-green-200':'bg-red-50 text-red-700 border border-red-200');
            alertEl.classList.remove('hidden');
        };

        const sysId    = document.getElementById('mpCredSysId').value.trim();
        const title    = document.getElementById('mpCredTitle').value.trim();
        const url      = document.getElementById('mpCredUrl').value.trim();
        const username = document.getElementById('mpCredUsername').value.trim();
        const password = document.getElementById('mpCredPassword').value;
        const notes    = document.getElementById('mpCredNotes').value.trim();

        if (!title) { showA('Title is required.', false); return; }

        this.disabled = true; this.innerHTML='<i class="fas fa-spinner fa-spin"></i> Saving…';
        try {
            const payload = sysId
                ? {action:'update', sys_id:sysId, title, url, username, password, notes}
                : {action:'add', title, url, username, password, notes};
            const data = await apiPost('/api/credentials/endpoints.php', payload);
            if (data.success) {
                showA('Saved!', true);
                await loadCredentials();
                setTimeout(() => { credModal.style.display='none'; }, 1200);
            } else { showA(data.message||'Failed.', false); }
        } catch(_){ showA('Network error.', false); }
        this.disabled = false; this.innerHTML='<i class="fas fa-save"></i> Save';
    });

    /* ══════════════════════════════════════════
       10. DOCUMENTS MODULE
    ══════════════════════════════════════════ */
    let mpDocsLoaded = false;

    async function loadDocs() {
        const el = document.getElementById('mpDocsList');
        try {
            const data = await apiPost('/api/employee-documents/endpoints.php', {action:'list'});
            if (!data.success) { el.innerHTML = `<p class="text-sm text-red-500">${escHtml(data.message)}</p>`; return; }
            if (!data.data.length) {
                el.innerHTML = `<div class="text-center py-10 text-gray-400 text-sm">
                    <i class="fas fa-folder-open text-4xl mb-3 block text-gray-200"></i>
                    No documents yet. Upload your first document above.
                </div>`;
                return;
            }
            const iconMap = {
                'application/pdf': {icon:'fa-file-pdf', cls:'text-red-400 bg-red-50'},
                'application/msword': {icon:'fa-file-word', cls:'text-blue-400 bg-blue-50'},
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document': {icon:'fa-file-word', cls:'text-blue-400 bg-blue-50'},
                'image/jpeg': {icon:'fa-file-image', cls:'text-purple-400 bg-purple-50'},
                'image/png':  {icon:'fa-file-image', cls:'text-purple-400 bg-purple-50'},
                'application/vnd.ms-excel': {icon:'fa-file-excel', cls:'text-green-500 bg-green-50'},
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet': {icon:'fa-file-excel', cls:'text-green-500 bg-green-50'},
            };
            el.innerHTML = data.data.map(doc => {
                const ic = iconMap[doc.file_type] || {icon:'fa-file', cls:'text-gray-400 bg-gray-100'};
                const hrBadge = doc.is_hr_issued == '1'
                    ? `<span class="ml-2 px-2 py-0.5 rounded-full text-xs bg-indigo-50 text-indigo-600 font-semibold">HR Issued</span>` : '';
                const size = doc.file_size > 0 ? (doc.file_size > 1048576
                    ? (doc.file_size/1048576).toFixed(1)+' MB'
                    : (doc.file_size/1024).toFixed(0)+' KB') : '';
                const date = doc.created_at ? doc.created_at.slice(0,10) : '';
                return `<div class="flex items-center gap-3 p-3 rounded-xl border border-gray-100 hover:bg-gray-50 transition">
                    <div class="w-10 h-10 rounded-xl flex items-center justify-center flex-shrink-0 ${ic.cls}">
                        <i class="fas ${ic.icon} text-sm"></i>
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="font-semibold text-sm text-gray-800 truncate">${escHtml(doc.title)}${hrBadge}</div>
                        <div class="text-xs text-gray-400 truncate">${escHtml(doc.file_name)} ${size ? '· '+size : ''} ${date ? '· '+date : ''}</div>
                    </div>
                    <div class="flex gap-2 flex-shrink-0">
                        <button class="w-8 h-8 rounded-lg hover:bg-red-50 text-gray-400 hover:text-red-500 transition flex items-center justify-center text-sm"
                                onclick="mpDeleteDoc('${escHtml(doc.sys_id)}','${escHtml(doc.title)}')" title="Delete">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>`;
            }).join('');
        } catch(_) { el.innerHTML='<p class="text-sm text-red-500">Failed to load documents.</p>'; }
    }

    window.mpDeleteDoc = async function(sysId, title) {
        if (!confirm('Delete "' + title + '"? This cannot be undone.')) return;
        try {
            const data = await apiPost('/api/employee-documents/endpoints.php', {action:'delete', sys_id:sysId});
            if (data.success) { await loadDocs(); }
            else { alert(data.message || 'Delete failed.'); }
        } catch(_){ alert('Network error.'); }
    };

    document.getElementById('mpDocUploadInput').addEventListener('change', async function() {
        const files = Array.from(this.files);
        if (!files.length) return;
        const progress = document.getElementById('mpDocUploadProgress');
        progress.classList.remove('hidden');
        let done = 0, failed = 0;
        for (const file of files) {
            const fd = new FormData();
            fd.append('action', 'upload');
            fd.append('file', file);
            fd.append('title', file.name.replace(/\.[^.]+$/, ''));
            progress.innerHTML = `<i class="fas fa-spinner fa-spin mr-2"></i>Uploading ${escHtml(file.name)}… (${done+1}/${files.length})`;
            try {
                const resp = await fetch('/api/employee-documents/endpoints.php', {method:'POST', body:fd});
                const json = await resp.json();
                if (json.success) done++; else { failed++; alert('Upload failed: ' + (json.message||'Unknown error')); }
            } catch(_){ failed++; alert('Network error uploading ' + file.name); }
        }
        progress.classList.add('hidden');
        this.value = '';
        await loadDocs();
        if (done > 0 && failed === 0) {
            const alert2 = document.createElement('div');
            alert2.className = 'mb-3 p-3 rounded-lg bg-green-50 text-green-700 text-sm font-medium';
            alert2.textContent = `✅ ${done} document${done>1?'s':''} uploaded successfully.`;
            document.getElementById('mpDocsList').before(alert2);
            setTimeout(() => alert2.remove(), 3000);
        }
    });

    /* ══════════════════════════════════════════
       11. NOTIFICATIONS MODULE
    ══════════════════════════════════════════ */
    let mpNotifLoaded = false;

    async function loadNotifications() {
        const el = document.getElementById('mpNotifList');
        try {
            const data = await apiPost('/api/notifications/endpoints.php', {action:'list'});
            if (data.status !== 'success') { el.innerHTML = `<p class="text-sm text-red-500">${escHtml(data.message||'Failed to load notifications.')}</p>`; return; }
            const items = data.data || [];
            updateNotifBadge(items.filter(n => !n.is_read).length);
            if (!items.length) {
                el.innerHTML = `<div class="text-center py-10 text-gray-400 text-sm">
                    <i class="fas fa-bell text-4xl mb-3 block text-gray-200"></i>No notifications yet.</div>`;
                return;
            }
            const typeIcon = {leave:'fa-calendar-check text-blue-500', attendance:'fa-clock text-orange-500', hr:'fa-building text-indigo-500', info:'fa-info-circle text-gray-400'};
            el.innerHTML = `<div class="flex justify-end mb-2">
                <button onclick="mpMarkAllRead()" class="text-xs text-blue-600 hover:underline">Mark all as read</button>
            </div>` + items.map(n => {
                const ic = typeIcon[n.type] || typeIcon.info;
                const unread = !n.is_read ? 'bg-blue-50 border-blue-100' : 'bg-white border-gray-100';
                const dot = !n.is_read ? '<span class="w-2 h-2 rounded-full bg-blue-500 flex-shrink-0 mt-1.5"></span>' : '<span class="w-2 h-2 flex-shrink-0"></span>';
                const dt = n.created_at ? n.created_at.slice(0,16).replace('T',' ') : '';
                return `<div class="flex items-start gap-3 p-3 rounded-xl border ${unread} transition" id="mpn-${escHtml(n.sys_id)}">
                    ${dot}
                    <div class="w-9 h-9 rounded-xl flex items-center justify-center flex-shrink-0 bg-gray-100">
                        <i class="fas ${ic} text-sm"></i>
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="text-sm font-semibold text-gray-800">${escHtml(n.title)}</div>
                        ${n.body ? `<div class="text-xs text-gray-500 mt-0.5">${escHtml(n.body)}</div>` : ''}
                        <div class="text-xs text-gray-400 mt-1">${dt}</div>
                    </div>
                    ${!n.is_read ? `<button onclick="mpMarkRead('${escHtml(n.sys_id)}')" class="text-xs text-blue-500 hover:underline flex-shrink-0">Read</button>` : ''}
                </div>`;
            }).join('');
        } catch(_) { el.innerHTML='<p class="text-sm text-red-500">Failed to load notifications.</p>'; }
    }

    function updateNotifBadge(count) {
        const badge = document.getElementById('mpNotifBadge');
        if (!badge) return;
        if (count > 0) {
            badge.textContent = count > 99 ? '99+' : count;
            badge.classList.remove('hidden');
        } else {
            badge.classList.add('hidden');
        }
    }

    window.mpMarkRead = async function(sysId) {
        try {
            await apiPost('/api/notifications/endpoints.php', {action:'mark_read', sys_id:sysId});
            await loadNotifications();
        } catch(_){}
    };

    window.mpMarkAllRead = async function() {
        try {
            await apiPost('/api/notifications/endpoints.php', {action:'mark_all'});
            await loadNotifications();
        } catch(_){}
    };

    // Load notification badge count on page load (without opening the panel)
    (async function() {
        try {
            const data = await apiPost('/api/notifications/endpoints.php', {action:'unread_count'});
            if (data.status === 'success') updateNotifBadge(data.count || 0);
        } catch(_){}
    })();

    /* ══════════════════════════════════════════
       12. PAYROLL MODULE
    ══════════════════════════════════════════ */
    let mpPayrollLoaded = false;
    let mpPayrollYears  = [];

    async function loadPayroll(year) {
        const el = document.getElementById('mpPayrollList');
        el.innerHTML = '<div class="text-center py-10 text-gray-300 text-sm"><i class="fas fa-spinner fa-spin text-2xl block mb-2"></i>Loading…</div>';
        try {
            const data = await apiPost('/api/payroll/endpoints.php', {action:'list', year: year || new Date().getFullYear()});
            if (!data.success) { el.innerHTML = `<p class="text-sm text-red-500 text-center py-6">${escHtml(data.message||'Failed to load payroll.')}</p>`; return; }

            // Populate year selector
            if (data.years && data.years.length) {
                mpPayrollYears = data.years;
                const sel = document.getElementById('mpPayrollYear');
                sel.innerHTML = mpPayrollYears.map(y => `<option value="${y}"${y==data.year?' selected':''}>${y}</option>`).join('');
            }

            const rows = data.data || [];
            if (!rows.length) {
                el.innerHTML = '<div class="text-center py-12 text-gray-400 text-sm"><i class="fas fa-file-invoice-dollar text-4xl block mb-3 text-gray-200"></i>No payroll records found for this year.</div>';
                return;
            }

            const STATUS_COLOR = {
                prepared:   'bg-yellow-100 text-yellow-700',
                collected:  'bg-blue-100 text-blue-700',
                authorized: 'bg-green-100 text-green-700',
                cancelled:  'bg-red-100 text-red-700',
            };

            el.innerHTML = rows.map(r => {
                const cls   = STATUS_COLOR[r.status] || 'bg-gray-100 text-gray-600';
                const month = r.month ? new Date(r.month+'-01').toLocaleDateString('en-BD',{month:'long',year:'numeric'}) : r.month;
                const net   = Number(r.net_payable_salary||0).toLocaleString('en-BD');
                return `<div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-4 flex items-center gap-4 cursor-pointer hover:shadow-md transition" onclick="mpViewSlip('${escHtml(r.sys_id)}')">
                    <div class="w-10 h-10 rounded-xl bg-green-50 flex items-center justify-center flex-shrink-0">
                        <i class="fas fa-file-invoice-dollar text-green-500"></i>
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="font-semibold text-sm text-gray-800">${month}</div>
                        <div class="text-xs text-gray-400 mt-0.5">${r.payment_type||'Salary'} · ${r.payment_date||'—'}</div>
                    </div>
                    <div class="text-right flex-shrink-0">
                        <div class="font-bold text-green-700 text-sm">৳ ${net}</div>
                        <span class="mt-1 inline-block text-xs font-semibold px-2 py-0.5 rounded-full ${cls}">${r.status||'—'}</span>
                    </div>
                </div>`;
            }).join('');
        } catch(e) {
            el.innerHTML = `<p class="text-sm text-red-500 text-center py-6">Network error: ${escHtml(e.message)}</p>`;
        }
    }

    window.mpViewSlip = async function(sysId) {
        const modal  = document.getElementById('mpPayrollModal');
        const mBody  = document.getElementById('mpPayrollModalBody');
        const mMonth = document.getElementById('mpPayrollModalMonth');
        const mStat  = document.getElementById('mpPayrollModalStatus');
        modal.style.display = 'flex';
        mBody.innerHTML = '<div class="text-center py-8 text-gray-300"><i class="fas fa-spinner fa-spin text-2xl"></i></div>';
        try {
            const data = await apiPost('/api/payroll/endpoints.php', {action:'get', sys_id:sysId});
            if (!data.success) { mBody.innerHTML = `<p class="text-red-500 text-sm text-center py-6">${escHtml(data.message)}</p>`; return; }
            const r = data.data;
            const month = r.month ? new Date(r.month+'-01').toLocaleDateString('en-BD',{month:'long',year:'numeric'}) : r.month;
            mMonth.textContent = month;
            mStat.textContent  = (r.status||'').toUpperCase() + (r.payment_date ? ' · ' + r.payment_date : '');

            const fmt = v => Number(v||0).toLocaleString('en-BD');
            const row = (label, val, cls='') => val
                ? `<div class="flex justify-between items-center py-1.5 border-b border-gray-50 last:border-0">
                       <span class="text-sm text-gray-500">${label}</span>
                       <span class="text-sm font-semibold ${cls}">৳ ${fmt(val)}</span>
                   </div>` : '';

            // Build allowances/deductions display
            let allowHtml = '', dedHtml = '';
            if (r.allowances && typeof r.allowances === 'object') {
                Object.entries(r.allowances).forEach(([k,v]) => { if(v) allowHtml += row(k, v, 'text-green-700'); });
            }
            if (r.deduction && typeof r.deduction === 'object') {
                Object.entries(r.deduction).forEach(([k,v]) => { if(v) dedHtml += row(k, v, 'text-red-600'); });
            }

            mBody.innerHTML = `
                <div class="space-y-1 mb-4">
                    ${row('Basic Salary', r.eps_salary?.basic_salary || r.net_payable_salary)}
                    ${row('Bonus',     r.bonus)}
                    ${row('Overtime',  r.overtime)}
                    ${allowHtml}
                </div>
                ${dedHtml ? `<div class="bg-red-50 rounded-xl p-3 mb-4 space-y-1">
                    <div class="text-xs font-bold text-red-500 mb-1 uppercase tracking-wide">Deductions</div>
                    ${dedHtml}
                </div>` : ''}
                <div class="bg-green-50 rounded-xl p-4 flex items-center justify-between">
                    <span class="font-bold text-gray-700">Net Payable</span>
                    <span class="text-xl font-extrabold text-green-700">৳ ${fmt(r.net_payable_salary)}</span>
                </div>
                ${r.note ? `<p class="mt-3 text-xs text-gray-400 text-center">${escHtml(r.note)}</p>` : ''}
            `;
        } catch(e) {
            mBody.innerHTML = `<p class="text-red-500 text-sm text-center py-6">Network error.</p>`;
        }
    };

    document.getElementById('mpPayrollModalClose')?.addEventListener('click', () => {
        document.getElementById('mpPayrollModal').style.display = 'none';
    });
    document.getElementById('mpPayrollModal')?.addEventListener('click', e => {
        if (e.target === document.getElementById('mpPayrollModal'))
            document.getElementById('mpPayrollModal').style.display = 'none';
    });
    document.getElementById('mpPayrollYear')?.addEventListener('change', function() {
        loadPayroll(this.value);
    });

    /* ══════════════════════════════════════════
       13. QUICK ACTIONS
    ══════════════════════════════════════════ */
    document.getElementById('qaAppoint')?.addEventListener('click', () => {
        alert('Appointment Letter — HR এর সাথে যোগাযোগ করুন। (Document generation coming soon)');
    });
    document.getElementById('qaNoc')?.addEventListener('click', () => {
        alert('NOC Letter — HR এর সাথে যোগাযোগ করুন।');
    });
    document.getElementById('qaSalary')?.addEventListener('click', () => {
        document.querySelector('[data-mpsec="payroll"]')?.click();
    });
    document.getElementById('qaIdCard')?.addEventListener('click', () => {
        window.open('my-id-card.php', '_blank');
    });
