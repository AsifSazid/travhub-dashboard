    ══════════════════════════════════════════ */
    const leaveModal      = document.getElementById('mpLeaveModal');
    const leaveModalClose = document.getElementById('mpLeaveModalClose');

    // View All Leaves modal
    const allLeavesModal  = document.getElementById('mpAllLeavesModal');
    document.getElementById('mpAllLeavesClose').addEventListener('click', () => { allLeavesModal.style.display='none'; });
    allLeavesModal.addEventListener('click', e => { if(e.target===allLeavesModal) allLeavesModal.style.display='none'; });
    document.getElementById('mpViewAllLeavesBtn').addEventListener('click', () => {
        const listEl = document.getElementById('mpAllLeavesList');
        if (!mpAllLeaves.length) {
            listEl.innerHTML = '<p class="text-sm text-gray-400 text-center py-8">No leave applications this year.</p>';
        } else {
            listEl.innerHTML = mpAllLeaves.map(la => renderLeaveCard(la)).join('');
        }
        allLeavesModal.style.display='flex';
    });
    const leaveFromInput  = document.getElementById('mpLeaveFrom');
    const leaveToInput    = document.getElementById('mpLeaveTo');
    const bridgeInfo      = document.getElementById('mpBridgeInfo');
    const bridgeText      = document.getElementById('mpBridgeText');

    document.getElementById('mpApplyLeaveBtn').addEventListener('click', () => {
        leaveModal.style.display = 'flex';
        document.getElementById('mpLeaveFormAlert').classList.add('hidden');
        bridgeInfo.classList.add('hidden');
        // Set min date to today
        const today = new Date().toISOString().split('T')[0];
        leaveFromInput.min = today;
        leaveToInput.min   = today;
    });
    leaveModalClose.addEventListener('click', () => { leaveModal.style.display='none'; });
    leaveModal.addEventListener('click', e => { if(e.target===leaveModal) leaveModal.style.display='none'; });

    /* Bridge preview calc (client-side: counts Fri+Sat only, no live holiday check) */
    function updateBridgePreview() {
        const f = leaveFromInput.value;
        const t = leaveToInput.value;
        if (!f || !t || f > t) { bridgeInfo.classList.add('hidden'); return; }
        let calDays = 0, workDays = 0;
        const cur = new Date(f);
        const end = new Date(t);
        while (cur <= end) {
            calDays++;
            const dow = cur.getDay(); // 0=Sun…6=Sat; Fri=5,Sat=6
            if (dow !== 5 && dow !== 6) workDays++;
            cur.setDate(cur.getDate() + 1);
        }
        const bridged = calDays - workDays;
        bridgeText.innerHTML = `<strong>${workDays} working day(s)</strong> selected.`
            + (bridged > 0 ? ` <strong>${bridged}</strong> weekend/holiday day(s) bridged (not deducted).` : '');
        bridgeInfo.classList.remove('hidden');
    }
    leaveFromInput.addEventListener('change', () => { if(leaveToInput.value && leaveToInput.value<leaveFromInput.value) leaveToInput.value=leaveFromInput.value; updateBridgePreview(); });
    leaveToInput.addEventListener('change', updateBridgePreview);

    document.getElementById('mpLeaveSubmitBtn').addEventListener('click', async function() {
        const alertEl = document.getElementById('mpLeaveFormAlert');
        const showFA  = (msg, ok) => {
            alertEl.textContent = msg;
            alertEl.className = 'mb-4 p-3 rounded-lg text-sm font-medium '
                + (ok ? 'bg-green-50 text-green-700 border border-green-200'
                      : 'bg-red-50 text-red-700 border border-red-200');
            alertEl.classList.remove('hidden');
        };

        const ltId   = document.getElementById('mpLeaveType').value;
        const from   = leaveFromInput.value;
        const to     = leaveToInput.value;
        const reason = document.getElementById('mpLeaveReason').value.trim();

        if (!ltId)    { showFA('Please select a leave type.',false); return; }
        if (!from||!to) { showFA('Please select both dates.',false); return; }
        if (from > to) { showFA('From date cannot be after To date.',false); return; }

        this.disabled = true;
        this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Submitting…';

        try {
            const data = await apiPost('/api/leaves/endpoints.php', {
                action:'apply', leave_type_sys_id:ltId, date_from:from, date_to:to, reason
            });
            if (data.success) {
                showFA('Leave application submitted successfully!', true);
                document.getElementById('mpLeaveType').value  = '';
                leaveFromInput.value = ''; leaveToInput.value = '';
                document.getElementById('mpLeaveReason').value = '';
                bridgeInfo.classList.add('hidden');
                setTimeout(() => { leaveModal.style.display='none'; }, 1800);
                await Promise.all([loadLeaveBalances(), loadLeaveList(), loadCalendar()]);
            } else {
                showFA(data.message || 'Failed to submit.', false);
            }
        } catch(_) { showFA('Network error. Please try again.', false); }
        finally {
            this.disabled = false;
            this.innerHTML = '<i class="fas fa-paper-plane"></i> Submit Application';
        }
    });

    /* ══════════════════════════════════════════
       7b. CLICKABLE CALENDAR DAYS
    ══════════════════════════════════════════ */
    let mpCalDayData = {}; // date → day object
    let mpNotesCache    = {}; // date → [note, note, ...] (multi-note per day)
    let mpSelectedDate  = null; // currently selected calendar date

    // Override loadCalendar to also make days clickable and store data
    const _origLoadCalendar = loadCalendar;

    async function loadCalendarWithNotes() {
        const grid  = document.getElementById('mpCalGrid');
        const title = document.getElementById('mpCalTitle');
        title.textContent = MONTH_NAMES[mpCalMonth-1] + ' ' + mpCalYear;
        grid.innerHTML = '<div class="col-span-7 text-center py-6 text-gray-400 text-sm"><i class="fas fa-spinner fa-spin mr-2"></i>Loading…</div>';

        try {
            const attData = await apiPost('/api/attendance/endpoints.php', {action:'my_month', year:mpCalYear, month:mpCalMonth});
            if (!attData.success) { grid.innerHTML='<p class="text-red-500 col-span-7 text-sm text-center py-4">'+attData.message+'</p>'; return; }

            // Fetch notes separately — graceful if table not ready
            let notesRaw = {};
            let notesHasNote = {};
            try {
                const notesData = await apiPost('/api/notes/endpoints.php', {action:'month', year:mpCalYear, month:mpCalMonth});
                if (notesData.success) { notesRaw = notesData.data || {}; notesHasNote = notesData.has_note || {}; }
            } catch(_) {}

            // Update summary
            const s = attData.summary || {};
            Object.keys(s).forEach(k => { const el=document.getElementById('mpS-'+k); if(el) el.textContent=s[k]; });

            // Store day data and notes
            mpCalDayData = {};
            attData.data.forEach(d => { mpCalDayData[d.date] = d; });
            mpNotesCache = notesRaw; // date → [note, note, ...]
            const mpHasNote = notesHasNote;

            // Update month label on notes sidebar
            const lbl = document.getElementById('mpNotesMonthLabel');
            if (lbl) lbl.textContent = MONTH_NAMES[mpCalMonth-1] + ' ' + mpCalYear;
            // Reset selected date if it's not in the new month
            if (mpSelectedDate && !attData.data.find(d => d.date === mpSelectedDate)) {
                mpSelectedDate = null;
                const hint = document.getElementById('mpNotesHint');
                if (hint) hint.style.display = '';
            }

            // Render notes sidebar
            const selD = mpSelectedDate ? mpCalDayData[mpSelectedDate] : null;
            renderNotesSidebar(selD || null);

            const firstDow = new Date(mpCalYear, mpCalMonth-1, 1).getDay();
            let html = '';
            for (let i=0; i<firstDow; i++) html += '<div class="mp-cal-day mp-d-empty"></div>';

            attData.data.forEach(d => {
                const st      = STATUS_MAP[d.att_status] || {cls:'mp-d-not_marked', code:'?'};
                const hasNote = !!(mpHasNote && mpHasNote[d.date]);
                html += `<div class="mp-cal-day ${st.cls} cursor-pointer" data-mpdate="${d.date}" title="${d.att_status} · ${d.date}">
                    <span class="mp-day-num">${d.day}</span>
                    ${st.code ? `<span class="mp-day-code">${st.code}</span>` : ''}
                    ${hasNote ? '<span style="position:absolute;top:2px;right:3px;font-size:.5rem;color:#6366f1">●</span>' : ''}
                </div>`;
            });

            grid.innerHTML = html;

            // Attach click handlers — select day, update notes panel inline (no modal)
            grid.querySelectorAll('.mp-cal-day[data-mpdate]').forEach(el => {
                const d = mpCalDayData[el.getAttribute('data-mpdate')];
                if (d) { el.addEventListener('click', () => selectCalendarDay(d)); }
            });
            // Re-highlight if a day was already selected this month
            if (mpSelectedDate && mpCalDayData[mpSelectedDate]) {
                grid.querySelectorAll('.mp-cal-day[data-mpdate]').forEach(el => {
                    el.classList.toggle('mp-d-selected', el.getAttribute('data-mpdate') === mpSelectedDate);
                });
            }
        } catch(_) {
            grid.innerHTML='<p class="text-red-500 col-span-7 text-sm text-center py-4">Failed to load calendar.</p>';
        }
    }

    // Bind calendar nav buttons ONCE (clone to strip any stale listeners)
    (function() {
        const prev = document.getElementById('mpCalPrev');
        const next = document.getElementById('mpCalNext');
        const np   = prev.cloneNode(true);
        const nn   = next.cloneNode(true);
        prev.parentNode.replaceChild(np, prev);
        next.parentNode.replaceChild(nn, next);
        np.addEventListener('click', () => {
            mpCalMonth--; if(mpCalMonth<1){mpCalMonth=12;mpCalYear--;} loadCalendarWithNotes();
        });
        nn.addEventListener('click', () => {
            mpCalMonth++; if(mpCalMonth>12){mpCalMonth=1;mpCalYear++;} loadCalendarWithNotes();
        });
    })();

    function escHtml(s) { return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;'); }

    /* ── Day selector (replaces modal popup) ── */
    function selectCalendarDay(d) {
        mpSelectedDate = d.date;
        // Highlight selected day in grid
        document.querySelectorAll('#mpCalGrid .mp-cal-day[data-mpdate]').forEach(el => {
            el.classList.toggle('mp-d-selected', el.getAttribute('data-mpdate') === d.date);
        });
        // Update notes panel header
        const lbl = document.getElementById('mpNotesMonthLabel');
        if (lbl) lbl.textContent = d.day_name + ', ' + d.date;
        const hint = document.getElementById('mpNotesHint');
        if (hint) hint.style.display = 'none';
        renderNotesSidebar(d);
    }

    /* ── Notes sidebar renderer ── */
    function renderNotesSidebar(selectedDay) {
        const sb = document.getElementById('mpNotesSidebar');
        if (!sb) return;

        // ── DAY MODE: a specific day is selected ──────────────
        if (selectedDay) {
            const d = selectedDay;
            const st = STATUS_MAP[d.att_status] || {cls:'mp-d-not_marked',code:'?'};
            const notes = Array.isArray(mpNotesCache[d.date]) ? mpNotesCache[d.date] : [];
            const todayStr = new Date().toISOString().slice(0,10);

            let html = '';

            // Attendance status chip
            html += `<div class="flex items-center gap-2 mb-3 pb-3 border-b border-gray-100">
                <span class="w-7 h-7 rounded-lg ${st.cls} flex items-center justify-center font-bold text-xs flex-shrink-0">${st.code||'—'}</span>
                <span class="text-xs font-semibold text-gray-600 capitalize">${d.att_status.replace(/_/g,' ')}</span>
                ${d.check_in  ? `<span class="ml-auto text-xs text-green-600"><i class="fas fa-sign-in-alt mr-1"></i>${d.check_in}</span>` : ''}
                ${d.check_out ? `<span class="text-xs text-red-500"><i class="fas fa-sign-out-alt mr-1"></i>${d.check_out}</span>` : ''}
            </div>`;

            if (d.leave_name) {
                html += `<div class="mb-3 px-2 py-1.5 rounded-lg bg-pink-50 text-xs text-pink-700"><i class="fas fa-umbrella-beach mr-1"></i>${escHtml(d.leave_name)}</div>`;
            }
            if (d.is_holiday && d.holiday_title) {
                html += `<div class="mb-3 px-2 py-1.5 rounded-lg bg-green-50 text-xs text-green-700"><i class="fas fa-star mr-1"></i>${escHtml(d.holiday_title)}</div>`;
            }

            // ── Add/Edit note form (always at top) ──
            html += `<div class="mb-3">
                <p class="text-xs font-semibold text-gray-500 mb-2" id="mpSbFormLabel">Add Note</p>
                <input id="mpSbTitle" type="text" maxlength="200" placeholder="Title (optional)"
                    class="w-full border border-gray-200 rounded-lg px-3 py-1.5 text-xs mb-1.5 focus:outline-none focus:ring-2 focus:ring-blue-300">
                <textarea id="mpSbBody" rows="3" placeholder="Write your note…"
                    class="w-full border border-gray-200 rounded-lg px-3 py-1.5 text-xs resize-none focus:outline-none focus:ring-2 focus:ring-blue-300"></textarea>
                <input type="hidden" id="mpSbSysId" value="">
                <div class="flex gap-2 mt-1.5">
                    <button id="mpSbSaveBtn" data-date="${d.date}"
                        class="flex-1 bg-blue-600 hover:bg-blue-700 text-white text-xs font-semibold rounded-lg px-3 py-1.5 transition flex items-center justify-center gap-1.5">
                        <i class="fas fa-save"></i> Save Note
                    </button>
                    <button id="mpSbCancelEdit" class="hidden border border-gray-200 text-gray-500 hover:bg-gray-50 text-xs font-semibold rounded-lg px-3 py-1.5 transition">
                        Cancel
                    </button>
                </div>
            </div>`;

            // ── Existing notes (newest first, below form) ──
            if (notes.length) {
                const sorted = [...notes].sort((a,b) => (b.created_at||'') > (a.created_at||'') ? 1 : -1);
                html += `<div class="border-t border-gray-100 pt-3 space-y-2">`;
                sorted.forEach(n => {
                    const ts  = n.created_at ? n.created_at.slice(0,16).replace('T',' ') : '';
                    const tid = 'mpns-' + n.sys_id;
                    html += `<div class="rounded-xl border border-yellow-100 bg-yellow-50 overflow-hidden text-xs">
                        <div class="flex items-center gap-1.5 px-3 py-2 cursor-pointer hover:bg-yellow-100 transition"
                             onclick="document.getElementById('${tid}').classList.toggle('hidden')">
                            <i class="fas fa-sticky-note text-yellow-500 flex-shrink-0"></i>
                            <div class="flex-1 min-w-0">
                                ${n.title ? `<span class="font-semibold text-gray-700 block truncate">${escHtml(n.title)}</span>` : ''}
                                ${!n.title && n.note_text ? `<span class="text-gray-500 truncate block">${escHtml((n.note_text||'').slice(0,40))}</span>` : ''}
                            </div>
                            <i class="fas fa-chevron-down text-gray-300 flex-shrink-0"></i>
                        </div>
                        <div id="${tid}" class="hidden px-3 pb-3 border-t border-yellow-100">
                            ${n.title ? `<p class="font-bold text-gray-700 mt-2">${escHtml(n.title)}</p>` : ''}
                            ${n.note_text ? `<p class="text-gray-600 mt-1 whitespace-pre-wrap">${escHtml(n.note_text)}</p>` : ''}
                            ${ts ? `<p class="text-gray-300 mt-1.5">${ts}</p>` : ''}
                            <div class="flex gap-3 mt-2">
                                <button class="text-blue-500 hover:underline mpInlineEdit"
                                    data-id="${n.sys_id}" data-title="${escHtml(n.title||'')}" data-body="${escHtml(n.note_text||'')}">
                                    <i class="fas fa-edit mr-0.5"></i>Edit
                                </button>
                                <button class="text-red-400 hover:underline mpInlineDel" data-id="${n.sys_id}" data-date="${d.date}">
                                    <i class="fas fa-trash mr-0.5"></i>Delete
                                </button>
                            </div>
                        </div>
                    </div>`;
                });
                html += `</div>`;
            }

            // Apply leave button
            if (!d.is_weekend && d.date >= todayStr && !['on_leave','holiday','weekend'].includes(d.att_status)) {
                html += `<button id="mpSbApplyLeave" data-date="${d.date}"
                    class="mt-2 w-full border border-blue-200 text-blue-600 hover:bg-blue-50 text-xs font-semibold rounded-lg px-3 py-1.5 transition flex items-center justify-center gap-1.5">
                    <i class="fas fa-calendar-plus"></i> Apply Leave from this date
                </button>`;
            }

            sb.innerHTML = html;

            // Wire up save button
            document.getElementById('mpSbSaveBtn').addEventListener('click', async function() {
                const date  = this.getAttribute('data-date');
                const title = (document.getElementById('mpSbTitle').value||'').trim();
                const text  = (document.getElementById('mpSbBody').value||'').trim();
                const sysId = document.getElementById('mpSbSysId').value;
                if (!title && !text) { alert('Please enter a title or note.'); return; }
                this.disabled = true; this.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
                try {
                    let res;
                    if (sysId) {
                        res = await apiPost('/api/notes/endpoints.php', {action:'update', sys_id:sysId, title, note_text:text});
                        if (res.success) {
                            const arr = mpNotesCache[date]||[];
                            const idx = arr.findIndex(n=>n.sys_id===sysId);
                            if (idx>=0) { arr[idx].title=title; arr[idx].note_text=text; }
                        }
                    } else {
                        res = await apiPost('/api/notes/endpoints.php', {action:'add', date, title, note_text:text});
                        if (res.success && res.note) {
                            if (!mpNotesCache[date]) mpNotesCache[date]=[];
                            mpNotesCache[date].push(res.note);
                        }
                    }
                    if (res.success) {
                        // Refresh calendar dot and re-render panel
                        loadCalendarWithNotes().then(() => {
                            const freshD = mpCalDayData[date];
                            if (freshD) selectCalendarDay(freshD);
                        });
                    } else { alert(res.message||'Failed.'); this.disabled=false; }
                } catch(_){ alert('Network error.'); this.disabled=false; }
            });

            // Cancel edit button
            const cancelEditBtn = document.getElementById('mpSbCancelEdit');
            if (cancelEditBtn) {
                cancelEditBtn.addEventListener('click', function() {
                    document.getElementById('mpSbTitle').value = '';
                    document.getElementById('mpSbBody').value  = '';
                    document.getElementById('mpSbSysId').value = '';
                    document.getElementById('mpSbFormLabel').textContent = 'Add Note';
                    document.getElementById('mpSbSaveBtn').innerHTML = '<i class="fas fa-save"></i> Save Note';
                    cancelEditBtn.classList.add('hidden');
                });
            }

            // Wire up inline edit buttons
            sb.querySelectorAll('.mpInlineEdit').forEach(btn => {
                btn.addEventListener('click', function() {
                    document.getElementById('mpSbTitle').value = this.getAttribute('data-title');
                    document.getElementById('mpSbBody').value  = this.getAttribute('data-body');
                    document.getElementById('mpSbSysId').value = this.getAttribute('data-id');
                    document.getElementById('mpSbFormLabel').textContent = 'Edit Note';
                    document.getElementById('mpSbSaveBtn').innerHTML = '<i class="fas fa-save"></i> Update Note';
                    const cb = document.getElementById('mpSbCancelEdit');
                    if (cb) cb.classList.remove('hidden');
                    document.getElementById('mpSbTitle').focus();
                });
            });

            // Wire up inline delete buttons
            sb.querySelectorAll('.mpInlineDel').forEach(btn => {
                btn.addEventListener('click', async function() {
                    if (!confirm('Delete this note?')) return;
                    const sysId = this.getAttribute('data-id');
                    const date2 = this.getAttribute('data-date');
                    try {
                        const r = await apiPost('/api/notes/endpoints.php', {action:'delete', sys_id:sysId});
                        if (r.success) {
                            if (mpNotesCache[date2]) {
                                mpNotesCache[date2] = mpNotesCache[date2].filter(n=>n.sys_id!==sysId);
                                if (!mpNotesCache[date2].length) delete mpNotesCache[date2];
                            }
                            loadCalendarWithNotes().then(() => {
                                const freshD = mpCalDayData[date2];
                                if (freshD) selectCalendarDay(freshD);
                            });
                        } else { alert(r.message||'Failed.'); }
                    } catch(_){ alert('Network error.'); }
                });
            });

            // Wire up Apply Leave
            const applyBtn = document.getElementById('mpSbApplyLeave');
            if (applyBtn) {
                applyBtn.addEventListener('click', function() {
                    const date = this.getAttribute('data-date');
                    document.getElementById('mpLeaveFrom').value = date;
                    document.getElementById('mpLeaveTo').value   = date;
                    leaveModal.style.display='flex';
                    document.getElementById('mpLeaveFormAlert').classList.add('hidden');
                    bridgeInfo.classList.add('hidden');
                    setTimeout(updateBridgePreview, 50);
                });
            }

            return;
        }

        // ── MONTH MODE: no day selected, show all month notes ────
        const allNotes = [];
        Object.entries(mpNotesCache).forEach(([date, notes]) => {
            if (Array.isArray(notes)) notes.forEach(n => allNotes.push({...n, _date: date}));
        });
        allNotes.sort((a,b) => (a._date+(a.created_at||'')) < (b._date+(b.created_at||'')) ? -1 : 1);

        if (!allNotes.length) {
            sb.innerHTML = '<p class="text-xs text-gray-400 text-center py-6">No notes this month. Select a day to add one.</p>';
            return;
        }

        sb.innerHTML = allNotes.map(n => {
            const d   = mpCalDayData[n._date] || {};
            const ts  = n.created_at ? n.created_at.slice(0,16).replace('T',' ') : '';
            const tid = 'mpnote-' + n.sys_id;
            return `<div class="rounded-xl border border-yellow-100 bg-yellow-50 overflow-hidden text-xs">
                <div class="flex items-center gap-1.5 px-3 py-2 cursor-pointer hover:bg-yellow-100 transition"
                     onclick="document.getElementById('${tid}').classList.toggle('hidden')">
                    <i class="fas fa-sticky-note text-yellow-500 flex-shrink-0"></i>
                    <div class="flex-1 min-w-0">
                        <span class="font-semibold text-gray-700">${n._date}${d.day_name?' ('+d.day_name+')':''}</span>
                        ${n.title ? `<span class="block text-gray-500 truncate">${escHtml(n.title)}</span>` : ''}
                        ${!n.title && n.note_text ? `<span class="block text-gray-400 truncate">${escHtml((n.note_text||'').slice(0,40))}</span>` : ''}
                    </div>
                    <i class="fas fa-chevron-down text-gray-300 flex-shrink-0"></i>
                </div>
                <div id="${tid}" class="hidden px-3 pb-3 border-t border-yellow-100">
                    ${n.title ? `<p class="font-bold text-gray-700 mt-2">${escHtml(n.title)}</p>` : ''}
                    ${n.note_text ? `<p class="text-gray-600 mt-1 whitespace-pre-wrap">${escHtml(n.note_text)}</p>` : ''}
                    ${ts ? `<p class="text-gray-300 mt-1.5">${ts}</p>` : ''}
                    <button class="text-blue-500 hover:underline mt-1.5"
                        onclick="const fd=mpCalDayData['${n._date}']; if(fd) selectCalendarDay(fd);">
                        <i class="fas fa-arrow-right mr-0.5"></i>Open day
                    </button>
                </div>
            </div>`;
        }).join('');
    }

    window.mpEditNoteSidebar = function(sysId, title, body, date) {
        const d = mpCalDayData[date];
        if (d) selectCalendarDay(d);
    };
    window.mpDeleteNote = async function(sysId, date) {
        if (!confirm('Delete this note?')) return;
        try {
            const r = await apiPost('/api/notes/endpoints.php', {action:'delete', sys_id:sysId});
            if (r.success) {
                if (mpNotesCache[date]) {
                    mpNotesCache[date] = mpNotesCache[date].filter(n => n.sys_id !== sysId);
                    if (!mpNotesCache[date].length) delete mpNotesCache[date];
                }
                renderNotesSidebar();
                loadCalendarWithNotes();
            } else { alert(r.message||'Failed.'); }
        } catch(_){ alert('Network error.'); }
    };
    window.mpOpenNoteDay = function(date) {
        const d = mpCalDayData[date];
        if (d) selectCalendarDay(d);
    };

    async function loadAttendanceFull() {
        await Promise.all([loadLeaveBalances(), loadCalendarWithNotes(), loadLeaveList(), loadLeaveTypes()]);
    }

    /* ── Day detail modal ── */
    const dayModal      = document.getElementById('mpDayModal');
    const dayModalClose = document.getElementById('mpDayModalClose');
    dayModalClose.addEventListener('click', () => { dayModal.style.display='none'; });
    dayModal.addEventListener('click', e => { if(e.target===dayModal) dayModal.style.display='none'; });

    // openDayModal kept as alias — UI now uses selectCalendarDay (no popup)
    function openDayModal(d, opts) {
        selectCalendarDay(d);
        return;
        // (legacy modal code below is unused but kept for reference)
        const titleEl = document.getElementById('mpDayModalTitle');
        const bodyEl  = document.getElementById('mpDayModalBody');
        const st      = STATUS_MAP[d.att_status] || {cls:'mp-d-not_marked',code:'?'};
        const existingNotes = Array.isArray(mpNotesCache[d.date]) ? mpNotesCache[d.date] : [];

        titleEl.textContent = d.date + ' · ' + d.day_name;

        // ── Attendance status row ──
        let html = `<div class="flex items-center gap-2 mb-4">
            <span class="w-8 h-8 rounded-lg ${st.cls} flex items-center justify-center font-bold text-sm">${st.code||'—'}</span>
            <span class="text-sm font-semibold text-gray-700">${d.att_status.replace(/_/g,' ')}</span>
        </div>`;

        if (d.check_in || d.check_out) {
            html += `<div class="grid grid-cols-2 gap-3 mb-4">
                <div class="bg-green-50 rounded-xl p-3 text-center">
                    <div class="text-xs text-gray-500">Check In</div>
                    <div class="text-base font-bold text-green-700">${d.check_in||'—'}</div>
                </div>
                <div class="bg-red-50 rounded-xl p-3 text-center">
                    <div class="text-xs text-gray-500">Check Out</div>
                    <div class="text-base font-bold text-red-700">${d.check_out||'—'}</div>
                </div>
            </div>`;
        }

        if (d.leave_name) {
            html += `<div class="mb-4 p-3 rounded-xl bg-pink-50 text-sm text-pink-700"><i class="fas fa-umbrella-beach mr-1.5"></i>${d.leave_name}${d.leave_code?' ('+d.leave_code+')':''}</div>`;
        }

        if (d.is_holiday && d.holiday_title) {
            html += `<div class="mb-4 p-3 rounded-xl bg-green-50 text-sm text-green-700"><i class="fas fa-star mr-1.5"></i>${d.holiday_title}</div>`;
        }

        // ── Existing notes list ──
        if (existingNotes.length > 0) {
            html += `<div class="mb-3">
                <div class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">Notes for this day</div>
                <div id="mpDayNotesList" class="flex flex-col gap-2">`;
            existingNotes.forEach(n => {
                const nTitle = (n.title||'').replace(/</g,'&lt;');
                const nBody  = (n.note_text||'').replace(/</g,'&lt;');
                const nTime  = n.created_at ? n.created_at.slice(0,16).replace('T',' ') : '';
                html += `<div class="border border-gray-200 rounded-xl p-3 bg-gray-50" data-note-id="${n.sys_id}">
                    ${nTitle ? `<div class="text-sm font-semibold text-gray-800 mb-1">${nTitle}</div>` : ''}
                    ${nBody  ? `<div class="text-sm text-gray-600 whitespace-pre-line">${nBody}</div>` : ''}
                    ${nTime  ? `<div class="text-xs text-gray-400 mt-1">${nTime}</div>` : ''}
                    <div class="flex gap-2 mt-2">
                        <button class="mpEditNoteBtn text-xs text-blue-600 hover:underline" data-id="${n.sys_id}" data-title="${(n.title||'').replace(/"/g,'&quot;')}" data-body="${(n.note_text||'').replace(/"/g,'&quot;')}"><i class="fas fa-edit mr-1"></i>Edit</button>
                        <button class="mpDelNoteBtn text-xs text-red-500 hover:underline" data-id="${n.sys_id}" data-date="${d.date}"><i class="fas fa-trash mr-1"></i>Delete</button>
                    </div>
                </div>`;
            });
            html += `</div></div>`;
        }

        // ── Add / Edit note form ──
        const isEdit   = !!(opts && opts.editSysId);
        const fTitle   = isEdit ? (opts.editTitle||'') : '';
        const fBody    = isEdit ? (opts.editBody||'')  : '';
        const fSysId   = isEdit ? opts.editSysId       : '';
        const btnLabel = isEdit ? 'Update Note' : 'Add Note';

        html += `<div class="mt-1" id="mpNoteFormWrap">
            <div class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">${isEdit ? 'Edit Note' : 'New Note'}</div>
            <input id="mpDayNoteTitle" type="text" maxlength="200"
                class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm mb-2 focus:outline-none focus:ring-2 focus:ring-blue-400"
                placeholder="Title (optional)" value="${fTitle.replace(/"/g,'&quot;')}">
            <textarea id="mpDayNoteText" rows="3"
                class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm resize-none focus:outline-none focus:ring-2 focus:ring-blue-400"
                placeholder="Write your note…">${fBody.replace(/</g,'&lt;')}</textarea>
            <input type="hidden" id="mpDayNoteSysId" value="${fSysId}">
            <button id="mpDayNoteSave" data-date="${d.date}"
                class="mt-2 w-full bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold rounded-lg px-4 py-2 transition flex items-center justify-center gap-2">
                <i class="fas fa-save"></i> ${btnLabel}
            </button>
        </div>`;

        // Apply leave only for today or future non-weekend, non-holiday days
        const todayStr = new Date().toISOString().slice(0,10);
        if (!d.is_weekend && d.date >= todayStr && !['on_leave','holiday','weekend'].includes(d.att_status)) {
            html += `<button id="mpDayApplyLeave" data-date="${d.date}"
                class="mt-3 w-full border border-blue-200 text-blue-600 hover:bg-blue-50 text-sm font-semibold rounded-lg px-4 py-2 transition flex items-center justify-center gap-2">
                <i class="fas fa-calendar-plus"></i> Apply Leave from this date
            </button>`;
        }

        bodyEl.innerHTML = html;

        // ── Save / Update ──
        document.getElementById('mpDayNoteSave').addEventListener('click', async function() {
            const date   = this.getAttribute('data-date');
            const title  = document.getElementById('mpDayNoteTitle').value.trim();
            const text   = document.getElementById('mpDayNoteText').value.trim();
            const sysId  = document.getElementById('mpDayNoteSysId').value;
            if (!title && !text) { alert('Please enter a title or note text.'); return; }
            this.disabled = true; this.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
            try {
                let res;
                if (sysId) {
                    res = await apiPost('/api/notes/endpoints.php', {action:'update', sys_id:sysId, title, note_text:text});
                    if (res.success) {
                        // Update cache
                        const arr = mpNotesCache[date] || [];
                        const idx = arr.findIndex(n=>n.sys_id===sysId);
                        if (idx>=0) { arr[idx].title=title; arr[idx].note_text=text; }
                    }
                } else {
                    res = await apiPost('/api/notes/endpoints.php', {action:'add', date, title, note_text:text});
                    if (res.success && res.note) {
                        if (!mpNotesCache[date]) mpNotesCache[date] = [];
                        mpNotesCache[date].push(res.note);
                    }
                }
                if (res.success) {
                    this.innerHTML = '<i class="fas fa-check"></i> Saved!';
                    renderNotesSidebar();
                    loadCalendarWithNotes();
                    setTimeout(() => { openDayModal(d); }, 600);
                } else { alert(res.message||'Failed to save.'); this.disabled=false; this.innerHTML='<i class="fas fa-save"></i> '+btnLabel; }
            } catch(_){ alert('Network error.'); this.disabled=false; this.innerHTML='<i class="fas fa-save"></i> '+btnLabel; }
        });

        // ── Per-note Edit ──
        bodyEl.querySelectorAll('.mpEditNoteBtn').forEach(btn => {
            btn.addEventListener('click', function() {
                const sysId = this.getAttribute('data-id');
                const title = this.getAttribute('data-title');
                const body2 = this.getAttribute('data-body');
                document.getElementById('mpDayNoteTitle').value = title;
                document.getElementById('mpDayNoteText').value  = body2;
                document.getElementById('mpDayNoteSysId').value = sysId;
                document.querySelector('#mpNoteFormWrap .text-xs.font-semibold').textContent = 'Edit Note';
                document.getElementById('mpDayNoteSave').innerHTML = '<i class="fas fa-save"></i> Update Note';
                document.getElementById('mpDayNoteSave').setAttribute('data-date', d.date);
                document.getElementById('mpDayNoteTitle').focus();
            });
        });

        // ── Per-note Delete ──
        bodyEl.querySelectorAll('.mpDelNoteBtn').forEach(btn => {
            btn.addEventListener('click', async function() {
                if (!confirm('Delete this note?')) return;
                const sysId = this.getAttribute('data-id');
                const date2 = this.getAttribute('data-date');
                try {
                    const res = await apiPost('/api/notes/endpoints.php', {action:'delete', sys_id:sysId});
                    if (res.success) {
                        if (mpNotesCache[date2]) {
                            mpNotesCache[date2] = mpNotesCache[date2].filter(n=>n.sys_id!==sysId);
                            if (!mpNotesCache[date2].length) delete mpNotesCache[date2];
                        }
                        renderNotesSidebar();
                        loadCalendarWithNotes();
                        openDayModal(d);
                    } else { alert(res.message||'Failed to delete.'); }
                } catch(_){ alert('Network error.'); }
            });
        });

        const applyBtn = document.getElementById('mpDayApplyLeave');
        if (applyBtn) {
            applyBtn.addEventListener('click', function() {
                dayModal.style.display='none';
                const date = this.getAttribute('data-date');
                document.getElementById('mpLeaveFrom').value = date;
                document.getElementById('mpLeaveTo').value   = date;
                leaveModal.style.display='flex';
                document.getElementById('mpLeaveFormAlert').classList.add('hidden');
                bridgeInfo.classList.add('hidden');
                setTimeout(updateBridgePreview, 50);
            });
        }

        dayModal.style.display = 'flex';
    }

    // Credentials lazy-load (attendance handled in main nav listener above)
    document.querySelectorAll('.mp-nav-btn[data-mpsec]').forEach(btn => {
        btn.addEventListener('click', function() {
            if (this.getAttribute('data-mpsec') === 'credentials' && !mpCredLoaded) {
                mpCredLoaded = true; loadCredentials();
            }
        });
    });

