    /* ══════════════════════════════════════════
       1. LEFT SIDEBAR NAVIGATION
    ══════════════════════════════════════════ */
    const navBtns = document.querySelectorAll('.mp-nav-btn[data-mpsec]');
    navBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            if (this.disabled) return;
            const sec = this.getAttribute('data-mpsec');
            navBtns.forEach(b => b.classList.remove('mp-active'));
            this.classList.add('mp-active');
            document.querySelectorAll('.mp-section').forEach(s => { s.style.display = 'none'; });
            const el = document.getElementById('mpsec-' + sec);
            if (el) el.style.display = 'block';
            if (sec === 'attendance'    && !mpAttLoaded)    { mpAttLoaded    = true; loadAttendanceFull(); }
            if (sec === 'documents'     && !mpDocsLoaded)  { mpDocsLoaded   = true; loadDocs(); }
            if (sec === 'notifications' && !mpNotifLoaded) { mpNotifLoaded  = true; loadNotifications(); }
            if (sec === 'payroll'       && !mpPayrollLoaded){ mpPayrollLoaded= true; loadPayroll(); }
            if (sec === 'annual'        && !mpAnnualLoaded){ mpAnnualLoaded  = true; initAnnualCalendar(); }
        });
    });

    /* ══════════════════════════════════════════
       2. INNER TABS (My Info section)
    ══════════════════════════════════════════ */
    const iTabBtns  = document.querySelectorAll('#mpITabBar .mp-itab-btn');
    const iTabPanes = document.querySelectorAll('#mpsec-myinfo .mp-tab-pane');

    // Init: first pane visible
    iTabPanes.forEach((p, i) => { p.style.display = i === 0 ? 'block' : 'none'; });
    if (iTabBtns[0]) iTabBtns[0].classList.add('mp-active');

    function switchInnerTab(tabId) {
        iTabBtns.forEach(b => b.classList.remove('mp-active'));
        iTabPanes.forEach(p => { p.style.display = 'none'; });
        const btn  = document.querySelector('#mpITabBar [data-mptab="' + tabId + '"]');
        const pane = document.getElementById('mptab-' + tabId);
        if (btn)  btn.classList.add('mp-active');
        if (pane) pane.style.display = 'block';
    }
    iTabBtns.forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault(); e.stopPropagation();
            switchInnerTab(this.getAttribute('data-mptab'));
        });
    });

    /* ══════════════════════════════════════════
       3. PASSWORD EYE + STRENGTH
    ══════════════════════════════════════════ */
    document.querySelectorAll('.mp-eye-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const inp = document.getElementById(this.getAttribute('data-mpfield'));
            if (!inp) return;
            const toText = inp.type === 'password';
            inp.type = toText ? 'text' : 'password';
            this.querySelector('i').className = toText ? 'fas fa-eye-slash text-sm' : 'fas fa-eye text-sm';
        });
    });

    const newPwInput = document.getElementById('mpNewPw');
    if (newPwInput) {
        newPwInput.addEventListener('input', function() {
            const v = this.value;
            let score = 0;
            if (v.length >= 6)          score++;
            if (v.length >= 10)         score++;
            if (/[A-Z]/.test(v))        score++;
            if (/[0-9]/.test(v))        score++;
            if (/[^A-Za-z0-9]/.test(v)) score++;
            const levels = [
                {w:'0%',  cls:'bg-gray-300',  txt:''},
                {w:'25%', cls:'bg-red-400',    txt:'Weak'},
                {w:'50%', cls:'bg-orange-400', txt:'Fair'},
                {w:'75%', cls:'bg-yellow-400', txt:'Good'},
                {w:'90%', cls:'bg-blue-400',   txt:'Strong'},
                {w:'100%',cls:'bg-green-500',  txt:'Very Strong'},
            ];
            const lvl = v.length ? levels[Math.min(score,5)] : levels[0];
            const bar = document.getElementById('mpStrBar');
            const lbl = document.getElementById('mpStrLbl');
            bar.style.width = lvl.w;
            bar.className   = 'mp-pw-bar h-full ' + lvl.cls;
            lbl.textContent = lvl.txt;
            lbl.className   = 'text-xs mt-0.5 ' + lvl.cls.replace('bg-','text-');
        });
    }

    /* ══════════════════════════════════════════
       4. CHANGE PASSWORD
    ══════════════════════════════════════════ */
    const pwBtn = document.getElementById('mpPwBtn');
    if (pwBtn) {
        pwBtn.addEventListener('click', async function() {
            const alertEl = document.getElementById('mpPwAlert');
            const curPw   = document.getElementById('mpCurPw').value.trim();
            const newPw   = document.getElementById('mpNewPw').value;
            const conPw   = document.getElementById('mpConPw').value;

            const showAlert = (msg, ok) => {
                alertEl.textContent = msg;
                alertEl.className = 'mb-4 p-3 rounded-lg text-sm font-medium '
                    + (ok ? 'bg-green-50 text-green-700 border border-green-200'
                          : 'bg-red-50 text-red-700 border border-red-200');
                alertEl.classList.remove('hidden');
            };

            if (!curPw||!newPw||!conPw) { showAlert('Please fill all fields.',false); return; }
            if (newPw !== conPw)        { showAlert('New passwords do not match.',false); return; }
            if (newPw.length < 6)       { showAlert('Password must be at least 6 characters.',false); return; }

            this.disabled = true;
            this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Updating…';
            try {
                const res  = await fetch(API_BASE + '/api/auth/change-password.php', {
                    method:'POST', headers:{'Content-Type':'application/json'},
                    body: JSON.stringify({current_password:curPw, new_password:newPw, confirm_password:conPw})
                });
                const data = await res.json();
                showAlert(data.message, data.success);
                if (data.success) {
                    ['mpCurPw','mpNewPw','mpConPw'].forEach(id => document.getElementById(id).value='');
                    const bar = document.getElementById('mpStrBar');
                    if (bar) { bar.style.width='0%'; bar.className='mp-pw-bar h-full bg-gray-300'; }
                    document.getElementById('mpStrLbl').textContent='';
                }
            } catch(_) { showAlert('Network error. Please try again.',false); }
            finally {
                this.disabled = false;
                this.innerHTML = '<i class="fas fa-lock-open"></i> <span>Update Password</span>';
            }
        });
    }

    /* ══════════════════════════════════════════
       5. PROFILE PHOTO UPLOAD
    ══════════════════════════════════════════ */
    const photoRing  = document.getElementById('mpPhotoRing');
    const photoInput = document.getElementById('mpPhotoInput');
    const photoMsg   = document.getElementById('mpPhotoMsg');

    if (photoRing && photoInput) {
        photoRing.addEventListener('click', () => photoInput.click());
        photoInput.addEventListener('change', async function() {
            if (!this.files[0]) return;
            photoMsg.textContent = 'Uploading…'; photoMsg.classList.remove('hidden');
            const fd = new FormData();
            fd.append('sys_id', MY_SYS_ID);
            fd.append('photo',  this.files[0]);
            try {
                const res  = await fetch(API_BASE + '/api/employees/upload-photo.php', {method:'POST', body:fd});
                const data = await res.json();
                if (data.success) {
                    document.getElementById('mpProfileImg').src = API_BASE+'/uploads/'+data.path+'?t='+Date.now();
                    photoMsg.textContent = '✓ Photo updated!';
                    setTimeout(() => photoMsg.classList.add('hidden'), 2500);
                } else {
                    photoMsg.textContent = '✗ ' + (data.message || 'Upload failed');
                }
            } catch(_) { photoMsg.textContent = '✗ Network error'; }
            this.value = '';
        });
    }

    /* ══════════════════════════════════════════
       6. ATTENDANCE & LEAVE MODULE
    ══════════════════════════════════════════ */
    let mpAttLoaded    = false;
    let mpAnnualLoaded = false;
    let mpCalYear    = new Date().getFullYear();
    let mpCalMonth   = new Date().getMonth() + 1; // 1-based
    let mpLeaveTypes = [];

    const STATUS_MAP = {
        present:   {cls:'mp-d-present',  code:'P'},
        absent:    {cls:'mp-d-absent',   code:'A'},
        late:      {cls:'mp-d-late',     code:'L'},
        half_day:  {cls:'mp-d-half_day', code:'H'},
        on_leave:  {cls:'mp-d-on_leave', code:'OL'},
        weekend:   {cls:'mp-d-weekend',  code:''},
        holiday:   {cls:'mp-d-holiday',  code:'H'},
        future:    {cls:'mp-d-future',   code:''},
        not_marked:{cls:'mp-d-not_marked',code:'?'},
    };

    async function apiPost(endpoint, body) {
        const res = await fetch(API_BASE + endpoint, {
            method:'POST', headers:{'Content-Type':'application/json'},
            body: JSON.stringify(body)
        });
        return res.json();
    }

    async function loadAttendance() {
        await Promise.all([loadLeaveBalances(), loadCalendar(), loadLeaveList(), loadLeaveTypes()]);
    }

    /* ── Leave balances ── */
    async function loadLeaveBalances() {
        const el = document.getElementById('mpLeaveBalances');
        const yr = document.getElementById('mpLbYear');
        try {
            const data = await apiPost('/api/leaves/endpoints.php', {action:'balances', year: mpCalYear});
            if (yr) yr.textContent = '(' + mpCalYear + ')';
            if (!data.success) { el.innerHTML='<p class="text-sm text-red-500 col-span-full">'+data.message+'</p>'; return; }
            if (!data.data.length) { el.innerHTML='<p class="text-sm text-gray-400 col-span-full text-center py-4">No leave types configured.</p>'; return; }
            el.innerHTML = data.data.map(b => `
                <div class="rounded-xl p-3 border border-gray-100 hover:shadow-sm transition">
                    <div class="flex items-center gap-2 mb-2">
                        <span class="w-3 h-3 rounded-full flex-shrink-0" style="background:${b.color}"></span>
                        <span class="text-xs font-semibold text-gray-700 truncate">${b.name}</span>
                    </div>
                    <div class="flex items-end justify-between">
                        <div>
                            <span class="text-2xl font-bold text-gray-800">${b.remaining}</span>
                            <span class="text-xs text-gray-400">/${b.allocated}</span>
                        </div>
                        <span class="text-xs text-gray-400">Used: ${b.used}${b.pending>0 ? ' · Pend: '+b.pending : ''}</span>
                    </div>
                    <div class="mt-2 h-1.5 rounded-full bg-gray-100 overflow-hidden">
                        <div class="h-full rounded-full transition-all" style="width:${b.allocated>0?Math.min(100,Math.round((b.used/b.allocated)*100)):0}%; background:${b.color}"></div>
                    </div>
                </div>
            `).join('');
        } catch(_) {
            el.innerHTML='<p class="text-sm text-red-500 col-span-full">Failed to load balances.</p>';
        }
    }

    /* ── Calendar ── */
    async function loadCalendar() {
        const grid  = document.getElementById('mpCalGrid');
        const title = document.getElementById('mpCalTitle');
        title.textContent = MONTH_NAMES[mpCalMonth-1] + ' ' + mpCalYear;
        grid.innerHTML = '<div class="col-span-7 text-center py-6 text-gray-400 text-sm"><i class="fas fa-spinner fa-spin mr-2"></i>Loading…</div>';

        try {
            const data = await apiPost('/api/attendance/endpoints.php', {action:'my_month', year:mpCalYear, month:mpCalMonth});
            if (!data.success) { grid.innerHTML='<p class="text-red-500 col-span-7 text-sm text-center py-4">'+data.message+'</p>'; return; }

            // Update summary
            const s = data.summary || {};
            Object.keys(s).forEach(k => {
                const el = document.getElementById('mpS-'+k);
                if (el) el.textContent = s[k];
            });

            // Build calendar grid
            // Find what weekday the first day is (JS: 0=Sun, so we need offset)
            const firstDow = new Date(mpCalYear, mpCalMonth-1, 1).getDay(); // 0=Sun
            let html = '';
            // Empty cells before first day
            for (let i=0; i<firstDow; i++) {
                html += '<div class="mp-cal-day mp-d-empty"></div>';
            }

            data.data.forEach(d => {
                const st  = STATUS_MAP[d.att_status] || {cls:'mp-d-not_marked', code:'?'};
                const tip = d.is_holiday ? (d.holiday_title||'Holiday') :
                            d.att_status === 'on_leave' ? (d.leave_name||'On Leave') :
                            d.att_status;
                html += `<div class="mp-cal-day ${st.cls}" title="${tip} · ${d.date}">
                    <span class="mp-day-num">${d.day}</span>
                    ${st.code ? `<span class="mp-day-code">${st.code}</span>` : ''}
                </div>`;
            });

            grid.innerHTML = html;
        } catch(_) {
            grid.innerHTML='<p class="text-red-500 col-span-7 text-sm text-center py-4">Failed to load calendar.</p>';
        }
    }

    // NOTE: Calendar nav listeners attached in loadCalendarWithNotes block below
    // to avoid double-fire. Do NOT add listeners here.

    /* ── Leave list ── */
    const STATUS_BADGE = {
        pending:   'bg-yellow-100 text-yellow-700',
        approved:  'bg-green-100 text-green-700',
        rejected:  'bg-red-100 text-red-700',
        cancelled: 'bg-gray-100 text-gray-500',
    };

    let mpAllLeaves = []; // full list for view-all modal

    async function loadLeaveList() {
        const el = document.getElementById('mpLeaveList');
        try {
            const data = await apiPost('/api/leaves/endpoints.php', {action:'list', year: new Date().getFullYear()});
            if (!data.success) { el.innerHTML='<p class="text-sm text-red-500 col-span-full">'+data.message+'</p>'; return; }
            mpAllLeaves = data.data || [];

            if (!mpAllLeaves.length) {
                el.innerHTML='<p class="text-sm text-gray-400 text-center py-8 col-span-full">No leave applications this year.</p>';
                return;
            }

            const today   = new Date().toISOString().slice(0,10);
            const upcoming = mpAllLeaves.filter(la => la.date_from >= today || la.status === 'pending').slice(0, 3);
            const toShow   = upcoming.length ? upcoming : mpAllLeaves.slice(0,3);

            el.innerHTML = toShow.map(la => renderLeaveCard(la)).join('');
        } catch(_) {
            el.innerHTML='<p class="text-sm text-red-500 text-center py-4 col-span-full">Failed to load.</p>';
        }
    }

    function renderLeaveCard(la) {
        const badge = STATUS_BADGE[la.status] || 'bg-gray-100 text-gray-500';
        return `<div class="p-3 rounded-xl border border-gray-100 hover:bg-gray-50 transition">
            <div class="flex items-start justify-between gap-2 mb-1">
                <div class="flex items-center gap-2 min-w-0">
                    <span class="w-2.5 h-2.5 rounded-full flex-shrink-0 mt-0.5" style="background:${la.color||'#94a3b8'}"></span>
                    <span class="text-sm font-semibold text-gray-700 truncate">${la.leave_name}</span>
                </div>
                <span class="text-xs font-semibold px-2 py-0.5 rounded-full flex-shrink-0 ${badge}">${la.status}</span>
            </div>
            <div class="text-xs text-gray-500 ml-4">
                ${la.date_from} → ${la.date_to} · <strong>${la.total_days}d</strong>
                ${la.bridged_days > 0 ? '<span class="text-blue-500 ml-1">+'+la.bridged_days+' bridged</span>' : ''}
            </div>
            ${la.status==='pending' ? `<button class="mt-2 ml-4 text-xs text-red-500 hover:text-red-700 font-semibold" onclick="mpCancelLeave('${la.sys_id}')">Cancel</button>` : ''}
        </div>`;
    }

    window.mpCancelLeave = async function(sysId) {
        if (!confirm('Cancel this leave application?')) return;
        try {
            const data = await apiPost('/api/leaves/endpoints.php', {action:'cancel', sys_id:sysId});
            if (data.success) {
                await Promise.all([loadLeaveList(), loadLeaveBalances(), loadCalendarWithNotes()]);
            } else {
                alert(data.message || 'Failed to cancel.');
            }
        } catch(_) { alert('Network error.'); }
    };

    /* ── Leave types (for apply modal) ── */
    async function loadLeaveTypes() {
        try {
            const data = await apiPost('/api/leaves/endpoints.php', {action:'types'});
            if (data.success) {
                mpLeaveTypes = data.data;
                const sel = document.getElementById('mpLeaveType');
                sel.innerHTML = '<option value="">— Select leave type —</option>'
                    + data.data.map(t => `<option value="${t.sys_id}">${t.name} (${t.code})</option>`).join('');
            }
        } catch(_) {}
    }

    /* ══════════════════════════════════════════
       7. APPLY LEAVE MODAL
