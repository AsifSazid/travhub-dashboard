    /* ══════════════════════════════════════════
       ANNUAL CALENDAR (Office Journal)
    ══════════════════════════════════════════ */
    let annualYear         = new Date().getFullYear();
    let annualHolidays     = []; // { holiday_date, title, type }
    let annualLeaveMap     = {}; // { "YYYY-MM-DD": [{leave_name, emp_name?}, ...] }
    let annualAtt          = {}; // { "YYYY-MM-DD": att_status }  (own only for employee)
    let annualNoteCache    = {}; // { "YYYY-MM-DD": [notes...] }
    let annualEventCache   = {}; // { "YYYY-MM-DD": [events...] }
    let annualSelectedDate = null;
    let annualAllEmps      = []; // employee list for event picker (admin only)

    const ANNUAL_MONTH_NAMES = ['January','February','March','April','May','June',
                                'July','August','September','October','November','December'];
    const ANNUAL_DAY_NAMES   = ['Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'];
    const ANNUAL_DAY_SHORT   = ['Sun','Mon','Tue','Wed','Thu','Fri','Sat'];
    const ANNUAL_DAY_LETTERS = ['S','M','T','W','T','F','S'];

    function annualDaysInMonth(y, m) { return new Date(y, m+1, 0).getDate(); }

    async function loadAnnualData() {
        const grid  = document.getElementById('annualGrid');
        const stats = document.getElementById('annualStats');
        grid.innerHTML  = '<div class="col-span-full text-center py-12 text-gray-400"><i class="fas fa-spinner fa-spin text-2xl mb-2 block"></i>Loading…</div>';
        stats.innerHTML = '';
        annualNoteCache  = {};
        annualLeaveMap   = {};
        annualAtt        = {};
        annualEventCache = {};

        try {
            // Holidays
            const hRes = await apiPost('/api/holidays/endpoints.php', {action:'list', year: annualYear});
            annualHolidays = hRes.data || [];

            // Leaves: admin gets all employees; employee gets own only
            try {
                if (IS_HR) {
                    const lRes = await apiPost('/api/leaves/endpoints.php', {action:'annual_all', year: annualYear});
                    (lRes.data || []).forEach(l => {
                        const from = new Date((l.date_from||'') + 'T00:00:00');
                        const to   = new Date((l.date_to||'')   + 'T00:00:00');
                        for (let d = new Date(from); d <= to; d.setDate(d.getDate()+1)) {
                            const ds = d.toISOString().slice(0,10);
                            if (!annualLeaveMap[ds]) annualLeaveMap[ds] = [];
                            annualLeaveMap[ds].push({
                                leave_name: l.leave_name || 'Leave',
                                emp_name:   l.emp_name   || '',
                                emp_id:     l.employee_sys_id || ''
                            });
                        }
                    });
                } else {
                    const lRes = await apiPost('/api/leaves/endpoints.php', {action:'list', year: annualYear});
                    (lRes.data || []).filter(l => l.status === 'approved').forEach(l => {
                        const from = new Date((l.date_from||'') + 'T00:00:00');
                        const to   = new Date((l.date_to||'')   + 'T00:00:00');
                        for (let d = new Date(from); d <= to; d.setDate(d.getDate()+1)) {
                            const ds = d.toISOString().slice(0,10);
                            if (!annualLeaveMap[ds]) annualLeaveMap[ds] = [];
                            annualLeaveMap[ds].push({leave_name: l.leave_name || 'Leave', emp_name: ''});
                        }
                    });
                }
            } catch(e) {}

            // Own attendance (all 12 months in parallel) — only meaningful for self
            await Promise.all(
                Array.from({length:12},(_,i)=>i+1).map(m =>
                    apiPost('/api/attendance/endpoints.php', {action:'my_month', year:annualYear, month:m})
                        .then(r => { (r.days||[]).forEach(d => { if(d.date) annualAtt[d.date] = d.att_status; }); })
                        .catch(()=>{})
                )
            );

            // Notes (all 12 months in parallel)
            await Promise.all(
                Array.from({length:12},(_,i)=>i+1).map(m =>
                    apiPost('/api/notes/endpoints.php', {action:'month', year:annualYear, month:m})
                        .then(r => {
                            const notes = r.data || [];
                            notes.forEach(n => {
                                const nd = n.note_date || '';
                                if (!nd) return;
                                if (!annualNoteCache[nd]) annualNoteCache[nd] = [];
                                annualNoteCache[nd].push(n);
                            });
                        })
                        .catch(()=>{})
                )
            );

            // Office events
            try {
                const eRes = await apiPost('/api/calendar-events/endpoints.php', {action:'list', year: annualYear});
                const evts = eRes.data || [];
                evts.forEach(ev => {
                    const d = ev.event_date || '';
                    if (!d) return;
                    if (!annualEventCache[d]) annualEventCache[d] = [];
                    annualEventCache[d].push(ev);
                });
            } catch(e) {}

        } catch(e) {
            grid.innerHTML = '<div class="col-span-full text-center py-8 text-red-400">Failed to load calendar data.</div>';
            return;
        }

        renderAnnualCalendar();
    }

    function annualDayCellClass(ds) {
        const today   = new Date().toISOString().slice(0,10);
        const isPast  = ds < today;
        const isFri   = new Date(ds+'T00:00:00').getDay() === 5;
        const hol     = annualHolidays.find(h => h.holiday_date === ds);
        const hasNote = !!(annualNoteCache[ds] && annualNoteCache[ds].length);
        const hasEvt  = !!(annualEventCache[ds] && annualEventCache[ds].length);
        const att     = annualAtt[ds] || null;
        const onLeave = !!(annualLeaveMap[ds] && annualLeaveMap[ds].length);

        if (ds === annualSelectedDate) return 'bg-blue-500 text-white border-blue-400 font-bold ring-2 ring-blue-300 z-10 scale-110';
        if (ds === today && ds !== annualSelectedDate) return 'bg-blue-100 text-blue-700 border-blue-300 font-bold ring-1 ring-blue-300';
        if (hol) {
            if (hol.type === 'public_holiday') return 'bg-red-100 text-red-700 border-red-200 font-semibold';
            if (hol.type === 'office_closed')  return 'bg-orange-100 text-orange-700 border-orange-200 font-semibold';
            return 'bg-yellow-50 text-yellow-700 border-yellow-200';
        }
        if (isFri)    return 'bg-slate-100 text-slate-500 border-slate-200';
        if (onLeave)  return 'bg-pink-100 text-pink-700 border-pink-200';
        if (att === 'present') return 'bg-green-100 text-green-700 border-green-200';
        if (att === 'absent' && isPast) return 'bg-rose-50 text-rose-500 border-rose-200';
        if (hasEvt)   return 'bg-indigo-50 text-indigo-700 border-indigo-200';
        if (hasNote)  return 'bg-violet-50 text-violet-700 border-violet-200';
        return 'bg-white text-gray-700 border-gray-100 hover:bg-gray-50';
    }

    function renderAnnualCalendar() {
        document.getElementById('annualYearLabel').textContent = annualYear;

        // Stats
        const leaveCount   = IS_HR
            ? Object.keys(annualLeaveMap).length
            : Object.values(annualLeaveMap).filter(v => v && v[0]?.emp_name === '').length;
        const presentCount = Object.values(annualAtt).filter(v=>v==='present').length;
        const noteCount    = Object.keys(annualNoteCache).length;
        const eventCount   = Object.keys(annualEventCache).length;

        document.getElementById('annualStats').innerHTML = [
            {icon:'fas fa-umbrella-beach',  color:'text-red-500 bg-red-50',     label:'Holidays',       val: annualHolidays.length},
            {icon:'fas fa-plane-departure', color:'text-pink-500 bg-pink-50',   label: IS_HR ? 'Leave Days (All)':'My Leave Days', val: Object.keys(annualLeaveMap).length},
            {icon:'fas fa-circle-check',    color:'text-green-500 bg-green-50', label:'Present Days',   val: presentCount},
            {icon:'fas fa-calendar-star',   color:'text-indigo-500 bg-indigo-50',label:'Event Days',    val: eventCount},
        ].map(s=>`
            <div class="flex items-center gap-3 rounded-xl border border-gray-100 p-3 cursor-default">
                <div class="w-9 h-9 rounded-xl ${s.color} flex items-center justify-center text-sm flex-shrink-0">
                    <i class="${s.icon}"></i>
                </div>
                <div>
                    <div class="text-xl font-bold text-gray-800 leading-tight">${s.val}</div>
                    <div class="text-xs text-gray-400">${s.label}</div>
                </div>
            </div>
        `).join('');

        // Render 12 months
        const grid = document.getElementById('annualGrid');
        let html = '';
        for (let m = 0; m < 12; m++) {
            const days  = annualDaysInMonth(annualYear, m);
            const first = new Date(annualYear, m, 1).getDay();
            html += `<div class="border border-gray-100 rounded-xl overflow-hidden shadow-sm">
                <div class="bg-gradient-to-r from-slate-700 to-slate-600 text-white text-xs font-bold px-3 py-2 flex justify-between items-center">
                    <span>${ANNUAL_MONTH_NAMES[m]}</span>
                    <span class="opacity-60 font-normal text-[10px]">${annualYear}</span>
                </div>
                <div class="p-1.5">
                    <div class="grid grid-cols-7 mb-0.5">
                        ${ANNUAL_DAY_LETTERS.map((l,i)=>`<div class="text-center text-[9px] font-semibold ${i===5?'text-slate-400':'text-gray-400'} py-0.5">${l}</div>`).join('')}
                    </div>
                    <div class="grid grid-cols-7 gap-px">`;

            for (let e = 0; e < first; e++) html += `<div></div>`;

            for (let day = 1; day <= days; day++) {
                const mm = String(m+1).padStart(2,'0');
                const dd = String(day).padStart(2,'0');
                const ds = `${annualYear}-${mm}-${dd}`;
                const cls     = annualDayCellClass(ds);
                const hasNote = !!(annualNoteCache[ds]  && annualNoteCache[ds].length);
                const hasEvt  = !!(annualEventCache[ds] && annualEventCache[ds].length);
                const leaveN  = annualLeaveMap[ds]?.length || 0;

                let dots = '';
                if (hasNote) dots += `<span class="absolute top-0 right-0 w-1 h-1 rounded-full bg-violet-500"></span>`;
                if (hasEvt)  dots += `<span class="absolute bottom-0 right-0 w-1 h-1 rounded-full bg-indigo-500"></span>`;
                // Admin leave badge
                let badge = '';
                if (IS_HR && leaveN > 1) {
                    badge = `<span class="absolute -top-0.5 -left-0.5 text-[7px] font-bold bg-pink-500 text-white rounded-full w-3 h-3 flex items-center justify-center leading-none">${leaveN}</span>`;
                }

                html += `<div class="aspect-square rounded text-[10px] flex items-center justify-center border ${cls} cursor-pointer transition-transform relative select-none"
                    data-anndate="${ds}" title="${ds}">${day}${dots}${badge}</div>`;
            }
            html += `</div></div></div>`;
        }
        grid.innerHTML = html;

        // Click handlers
        grid.querySelectorAll('[data-anndate]').forEach(el => {
            el.addEventListener('click', () => annualSelectDay(el.getAttribute('data-anndate')));
        });

        // Holiday list
        if (annualHolidays.length) {
            const listEl  = document.getElementById('annualHolidayList');
            const itemsEl = document.getElementById('annualHolidayItems');
            listEl.classList.remove('hidden');
            const typeLabel = {public_holiday:'Public Holiday', office_closed:'Office Closed', optional:'Optional'};
            const typeCls   = {public_holiday:'bg-red-100 text-red-700', office_closed:'bg-orange-100 text-orange-700', optional:'bg-yellow-100 text-yellow-700'};
            itemsEl.innerHTML = annualHolidays.map(h => {
                const dt      = new Date(h.holiday_date + 'T00:00:00');
                const dayName = ANNUAL_DAY_SHORT[dt.getDay()];
                const display = dt.toLocaleDateString('en-BD',{day:'numeric',month:'long',year:'numeric'});
                return `<div class="flex items-center justify-between px-4 py-2.5 hover:bg-gray-50 transition cursor-pointer"
                            onclick="annualSelectDay('${h.holiday_date}')">
                    <div>
                        <div class="font-medium text-gray-800 text-sm">${h.title}</div>
                        <div class="text-xs text-gray-400 mt-0.5">${dayName}, ${display}</div>
                    </div>
                    <span class="text-[10px] font-semibold px-2 py-0.5 rounded-full ${typeCls[h.type]||'bg-gray-100 text-gray-600'}">${typeLabel[h.type]||h.type}</span>
                </div>`;
            }).join('');
        }
    }

    // ── Day Planner Panel ─────────────────────────────────────
    window.annualSelectDay = function(dateStr) {
        annualSelectedDate = dateStr;
        // Re-apply cell classes
        document.querySelectorAll('#annualGrid [data-anndate]').forEach(el => {
            const ds = el.getAttribute('data-anndate');
            const cls = annualDayCellClass(ds);
            const hasNote = !!(annualNoteCache[ds]  && annualNoteCache[ds].length);
            const hasEvt  = !!(annualEventCache[ds] && annualEventCache[ds].length);
            const leaveN  = annualLeaveMap[ds]?.length || 0;
            let dots = '';
            if (hasNote) dots += `<span class="absolute top-0 right-0 w-1 h-1 rounded-full bg-violet-500"></span>`;
            if (hasEvt)  dots += `<span class="absolute bottom-0 right-0 w-1 h-1 rounded-full bg-indigo-500"></span>`;
            let badge = '';
            if (IS_HR && leaveN > 1) badge = `<span class="absolute -top-0.5 -left-0.5 text-[7px] font-bold bg-pink-500 text-white rounded-full w-3 h-3 flex items-center justify-center leading-none">${leaveN}</span>`;
            const dayNum = parseInt(ds.slice(8));
            el.className = `aspect-square rounded text-[10px] flex items-center justify-center border ${cls} cursor-pointer transition-transform relative select-none`;
            el.innerHTML = dayNum + dots + badge;
        });

        const panel = document.getElementById('annualDayPanel');
        panel.classList.remove('hidden');

        const dt      = new Date(dateStr + 'T00:00:00');
        const dayName = ANNUAL_DAY_NAMES[dt.getDay()];
        const display = dt.toLocaleDateString('en-BD',{day:'numeric',month:'long',year:'numeric'});
        document.getElementById('annualDayPanelDate').textContent = dayName;
        document.getElementById('annualDayPanelSub').textContent  = display;

        // Status chip(s)
        const chipEl  = document.getElementById('annualDayStatusChip');
        const hol     = annualHolidays.find(h => h.holiday_date === dateStr);
        const leaves  = annualLeaveMap[dateStr] || [];
        let chips = [];
        if (hol) {
            const typeLabel = {public_holiday:'🏖️ Public Holiday', office_closed:'🚪 Office Closed', optional:'🌟 Optional'};
            const typeCls   = {public_holiday:'bg-red-100 text-red-700', office_closed:'bg-orange-100 text-orange-700', optional:'bg-yellow-100 text-yellow-700'};
            chips.push(`<span class="inline-flex items-center gap-1 text-xs font-semibold px-2 py-0.5 rounded-full ${typeCls[hol.type]||'bg-gray-100 text-gray-600'}">${typeLabel[hol.type]||hol.type}: ${hol.title}</span>`);
        }
        if (dt.getDay() === 5) {
            chips.push(`<span class="inline-flex items-center gap-1 text-xs font-semibold px-2 py-0.5 rounded-full bg-slate-100 text-slate-600">📅 Weekend (Friday)</span>`);
        }
        if (!IS_HR && leaves.length) {
            // Own leave
            chips.push(`<span class="inline-flex items-center gap-1 text-xs font-semibold px-2 py-0.5 rounded-full bg-pink-100 text-pink-700">✈️ On Leave — ${leaves[0].leave_name}</span>`);
        }
        if (!hol && !leaves.length && dt.getDay() !== 5) {
            const att = annualAtt[dateStr];
            if (att === 'present')     chips.push(`<span class="inline-flex items-center gap-1 text-xs font-semibold px-2 py-0.5 rounded-full bg-green-100 text-green-700">✅ Present</span>`);
            else if (att === 'absent') chips.push(`<span class="inline-flex items-center gap-1 text-xs font-semibold px-2 py-0.5 rounded-full bg-rose-100 text-rose-700">❌ Absent</span>`);
            else                       chips.push(`<span class="inline-flex items-center gap-1 text-xs font-semibold px-2 py-0.5 rounded-full bg-gray-100 text-gray-500">— No record</span>`);
        }
        chipEl.innerHTML = chips.join('');

        // Who's away block
        const awayBlock = document.getElementById('annualDayAwayBlock');
        const awayList  = document.getElementById('annualDayAwayList');
        const awayCount = document.getElementById('annualDayAwayCount');
        awayBlock.classList.add('hidden');

        if (IS_HR) {
            // Admin: fetch live daily_all for comprehensive away list
            apiPost('/api/attendance/endpoints.php', {action:'daily_all', date: dateStr})
                .then(r => {
                    const rows = r.data || r.records || [];
                    const away = rows.filter(row => row.att_status === 'on_leave' || row.att_status === 'absent');
                    if (!away.length) return;
                    awayBlock.classList.remove('hidden');
                    awayCount.textContent = `${away.length} employee${away.length>1?'s':''}`;
                    awayList.innerHTML = away.map(row => {
                        const icon  = row.att_status === 'on_leave' ? '✈️' : '❌';
                        const label = row.att_status === 'on_leave' ? (row.leave_name||'On Leave') : 'Absent';
                        return `<div class="flex items-center justify-between py-0.5">
                            <span class="font-medium truncate max-w-[160px]">${icon} ${row.emp_name||'Employee'}</span>
                            <span class="text-gray-400 text-[10px] flex-shrink-0 ml-2">${label}</span>
                        </div>`;
                    }).join('');
                })
                .catch(()=>{});
        }

        // Events block
        const evtBlock = document.getElementById('annualDayEventsBlock');
        const evtList  = document.getElementById('annualDayEventsList');
        const events   = annualEventCache[dateStr] || [];
        evtBlock.classList.toggle('hidden', !events.length);
        if (events.length) {
            evtList.innerHTML = events.map(ev => `
                <div class="flex items-start justify-between gap-2 rounded-lg bg-indigo-50 border border-indigo-100 px-2.5 py-1.5">
                    <div class="flex-1 min-w-0">
                        <div class="font-semibold text-indigo-800 text-xs truncate">${ev.title}</div>
                        ${ev.description ? `<div class="text-indigo-600 text-[10px] mt-0.5">${ev.description}</div>` : ''}
                        <div class="text-indigo-400 text-[10px] mt-0.5">
                            ${ev.visibility === 'public' ? '🌐 Public' : '🔒 Private'}
                        </div>
                    </div>
                    ${IS_HR ? `<div class="flex gap-1 flex-shrink-0">
                        <button onclick="annualEditEvent('${ev.sys_id}')" class="text-indigo-400 hover:text-indigo-700 text-[10px]"><i class="fas fa-pen"></i></button>
                        <button onclick="annualDeleteEvent('${ev.sys_id}','${dateStr}')" class="text-red-400 hover:text-red-700 text-[10px]"><i class="fas fa-trash"></i></button>
                    </div>` : ''}
                </div>
            `).join('');
        }

        // Notes
        document.getElementById('annualNoteDate').value  = dateStr;
        document.getElementById('annualNoteSysId').value = '';
        document.getElementById('annualNoteTitle').value = '';
        document.getElementById('annualNoteBody').value  = '';
        document.getElementById('annualNoteRepeat').checked = false;
        document.getElementById('annualNoteCancel').classList.add('hidden');
        annualRenderNotesList(dateStr);
    };

    function annualRenderNotesList(dateStr) {
        const listEl = document.getElementById('annualNotesList');
        const notes  = annualNoteCache[dateStr] || [];
        if (!notes.length) {
            listEl.innerHTML = '<p class="text-xs text-gray-400 text-center py-3">No planning notes for this day.</p>';
            return;
        }
        const sorted = [...notes].sort((a,b) => (b.created_at||'') > (a.created_at||'') ? 1 : -1);
        listEl.innerHTML = sorted.map(n => {
            const isRecurring = n.is_recurring || n.repeat_yearly == 1;
            const titleEsc    = (n.title||'').replace(/\\/g,'\\\\').replace(/'/g,"\\'").replace(/"/g,'&quot;');
            const bodyEsc     = (n.note_text||'').replace(/\\/g,'\\\\').replace(/'/g,"\\'").replace(/"/g,'&quot;');
            const noteDateEsc = (n.note_date||dateStr);
            return `
            <div class="border ${isRecurring?'border-blue-200 bg-blue-50':'border-gray-100 bg-gray-50'} rounded-xl p-2.5 text-xs">
                <div class="flex items-start justify-between gap-1 mb-0.5">
                    <div class="font-semibold text-gray-700 break-words flex-1">${n.title||'<span class="text-gray-400 font-normal italic">No title</span>'}</div>
                    ${isRecurring ? `<span class="flex-shrink-0 text-[9px] font-semibold px-1.5 py-0.5 rounded-full bg-blue-100 text-blue-600 flex items-center gap-1 ml-1">
                        <i class="fas fa-rotate"></i>Yearly
                    </span>` : ''}
                </div>
                <div class="text-gray-600 whitespace-pre-wrap break-words">${n.note_text||''}</div>
                <div class="flex items-center justify-between mt-1.5 pt-1.5 border-t ${isRecurring?'border-blue-200':'border-gray-200'}">
                    <span class="text-gray-400 text-[10px]">${(n.created_at||'').slice(0,16)}${isRecurring&&n.note_date!==dateStr?' <span class="text-blue-400">(from '+n.note_date+')</span>':''}</span>
                    <div class="flex gap-2">
                        ${!isRecurring||n.note_date===dateStr ? `
                        <button onclick="annualEditNote('${n.sys_id}','${noteDateEsc}','${titleEsc}','${bodyEsc}',${n.repeat_yearly||0});return false;"
                            class="text-blue-500 hover:text-blue-700 text-[10px]"><i class="fas fa-pen"></i></button>
                        <button onclick="annualDeleteNote('${n.sys_id}','${dateStr}');return false;"
                            class="text-red-400 hover:text-red-600 text-[10px]"><i class="fas fa-trash"></i></button>` : ''}
                    </div>
                </div>
            </div>`;
        }).join('');
    }

    window.annualEditNote = function(sysId, dateStr, title, body, repeatYearly) {
        document.getElementById('annualNoteSysId').value = sysId;
        document.getElementById('annualNoteDate').value  = dateStr;
        document.getElementById('annualNoteTitle').value = title;
        document.getElementById('annualNoteBody').value  = body;
        document.getElementById('annualNoteRepeat').checked = !!repeatYearly;
        document.getElementById('annualNoteCancel').classList.remove('hidden');
        document.getElementById('annualNoteTitle').focus();
    };

    window.annualDeleteNote = async function(sysId, dateStr) {
        if (!confirm('Delete this note?')) return;
        try {
            await apiPost('/api/notes/endpoints.php', {action:'delete', sys_id: sysId});
            const r = await apiPost('/api/notes/endpoints.php', {action:'list_date', date: dateStr});
            annualNoteCache[dateStr] = r.data || [];
            annualRefreshCell(dateStr);
            annualRenderNotesList(dateStr);
        } catch(e) { alert('Delete failed.'); }
    };

    document.getElementById('annualNoteSave')?.addEventListener('click', async function() {
        const dateStr      = document.getElementById('annualNoteDate').value;
        const sysId        = document.getElementById('annualNoteSysId').value;
        const title        = document.getElementById('annualNoteTitle').value.trim();
        const body         = document.getElementById('annualNoteBody').value.trim();
        const repeatYearly = document.getElementById('annualNoteRepeat').checked ? 1 : 0;
        if (!title && !body) { alert('Please enter a title or note text.'); return; }
        try {
            if (sysId) {
                await apiPost('/api/notes/endpoints.php', {action:'update', sys_id:sysId, title, note_text:body, repeat_yearly:repeatYearly});
            } else {
                await apiPost('/api/notes/endpoints.php', {action:'add', date:dateStr, title, note_text:body, repeat_yearly:repeatYearly});
            }
            const r = await apiPost('/api/notes/endpoints.php', {action:'list_date', date: dateStr});
            annualNoteCache[dateStr] = r.data || [];
            document.getElementById('annualNoteTitle').value  = '';
            document.getElementById('annualNoteBody').value   = '';
            document.getElementById('annualNoteSysId').value  = '';
            document.getElementById('annualNoteRepeat').checked = false;
            document.getElementById('annualNoteCancel').classList.add('hidden');
            annualRefreshCell(dateStr);
            annualRenderNotesList(dateStr);
        } catch(e) { alert('Save failed: ' + e.message); }
    });

    document.getElementById('annualNoteCancel')?.addEventListener('click', () => {
        document.getElementById('annualNoteSysId').value  = '';
        document.getElementById('annualNoteTitle').value  = '';
        document.getElementById('annualNoteBody').value   = '';
        document.getElementById('annualNoteRepeat').checked = false;
        document.getElementById('annualNoteCancel').classList.add('hidden');
    });

    document.getElementById('annualDayPanelClose')?.addEventListener('click', () => {
        annualSelectedDate = null;
        document.getElementById('annualDayPanel').classList.add('hidden');
        document.querySelectorAll('#annualGrid [data-anndate]').forEach(el => {
            const ds    = el.getAttribute('data-anndate');
            const dayN  = parseInt(ds.slice(8));
            const cls   = annualDayCellClass(ds);
            const hasNote = !!(annualNoteCache[ds]  && annualNoteCache[ds].length);
            const hasEvt  = !!(annualEventCache[ds] && annualEventCache[ds].length);
            const leaveN  = annualLeaveMap[ds]?.length || 0;
            let dots = '';
            if (hasNote) dots += `<span class="absolute top-0 right-0 w-1 h-1 rounded-full bg-violet-500"></span>`;
            if (hasEvt)  dots += `<span class="absolute bottom-0 right-0 w-1 h-1 rounded-full bg-indigo-500"></span>`;
            let badge = '';
            if (IS_HR && leaveN > 1) badge = `<span class="absolute -top-0.5 -left-0.5 text-[7px] font-bold bg-pink-500 text-white rounded-full w-3 h-3 flex items-center justify-center leading-none">${leaveN}</span>`;
            el.className = `aspect-square rounded text-[10px] flex items-center justify-center border ${cls} cursor-pointer transition-transform relative select-none`;
            el.innerHTML = dayN + dots + badge;
        });
    });

    // Helper: refresh a single calendar cell without full re-render
    function annualRefreshCell(dateStr) {
        const cell = document.querySelector(`#annualGrid [data-anndate="${dateStr}"]`);
        if (!cell) return;
        const dayN    = parseInt(dateStr.slice(8));
        const cls     = annualDayCellClass(dateStr);
        const hasNote = !!(annualNoteCache[dateStr]  && annualNoteCache[dateStr].length);
        const hasEvt  = !!(annualEventCache[dateStr] && annualEventCache[dateStr].length);
        const leaveN  = annualLeaveMap[dateStr]?.length || 0;
        let dots = '';
        if (hasNote) dots += `<span class="absolute top-0 right-0 w-1 h-1 rounded-full bg-violet-500"></span>`;
        if (hasEvt)  dots += `<span class="absolute bottom-0 right-0 w-1 h-1 rounded-full bg-indigo-500"></span>`;
        let badge = '';
        if (IS_HR && leaveN > 1) badge = `<span class="absolute -top-0.5 -left-0.5 text-[7px] font-bold bg-pink-500 text-white rounded-full w-3 h-3 flex items-center justify-center leading-none">${leaveN}</span>`;
        cell.className = `aspect-square rounded text-[10px] flex items-center justify-center border ${cls} cursor-pointer transition-transform relative select-none`;
        cell.innerHTML = dayN + dots + badge;
    }

    async function initAnnualCalendar() {
        annualYear = new Date().getFullYear();
        document.getElementById('annualYearLabel').textContent = annualYear;
        await loadAnnualData();
    }

    document.getElementById('annualPrevYear')?.addEventListener('click', async () => {
        annualYear--; annualSelectedDate = null;
        document.getElementById('annualDayPanel').classList.add('hidden');
        await loadAnnualData();
    });
    document.getElementById('annualNextYear')?.addEventListener('click', async () => {
        annualYear++; annualSelectedDate = null;
        document.getElementById('annualDayPanel').classList.add('hidden');
        await loadAnnualData();
    });
    document.getElementById('annualTodayBtn')?.addEventListener('click', async () => {
        const todayStr  = new Date().toISOString().slice(0,10);
        const todayYear = new Date().getFullYear();
        const wasOtherYear = annualYear !== todayYear;
        if (wasOtherYear) {
            annualYear = todayYear;
            annualSelectedDate = null;
            document.getElementById('annualDayPanel').classList.add('hidden');
            await loadAnnualData();
        }
        setTimeout(() => {
            const todayCell = document.querySelector(`#annualGrid [data-anndate="${todayStr}"]`);
            if (todayCell) {
                todayCell.scrollIntoView({behavior:'smooth', block:'center'});
                annualSelectDay(todayStr);
            }
        }, wasOtherYear ? 600 : 50);
    });

    document.getElementById('annualPrintBtn')?.addEventListener('click', () => {
        const content = document.getElementById('mpsec-annual').innerHTML;
        const win = window.open('', '_blank');
        win.document.write(`<!DOCTYPE html><html><head>
            <title>Annual Calendar ${annualYear} — TravHub</title>
            <script src="https://cdn.tailwindcss.com"><\/script>
            <style>body{padding:24px;font-family:sans-serif} @media print{#annualDayPanel,button{display:none}}</style>
        </head><body><div class="max-w-5xl mx-auto">${content}</div></body></html>`);
        win.document.close();
        setTimeout(() => { win.focus(); win.print(); }, 800);
    });

<?php if ($isHR): ?>
    // ── Office Event Management (admin only) ─────────────────
    let annualEditingEventId = null;

    // Load employee list for picker
    async function annualLoadEmpList() {
        if (annualAllEmps.length) return; // already loaded
        try {
            const r = await apiPost('/api/employees/endpoints.php', {action:'list_active'});
            annualAllEmps = r.data || r.employees || [];
        } catch(e) {
            // fallback: try attendance daily to get any employee list
            annualAllEmps = [];
        }
    }

    function annualRenderEmpPicker(selectedIds) {
        const listEl = document.getElementById('annualEventEmpList');
        if (!annualAllEmps.length) {
            listEl.innerHTML = '<div class="text-xs text-gray-400 text-center py-2">No employees found.<br>Type sys_ids manually below.</div>';
            return;
        }
        listEl.innerHTML = annualAllEmps.map(emp => {
            const checked = selectedIds.includes(emp.sys_id) ? 'checked' : '';
            return `<label class="flex items-center gap-2 cursor-pointer hover:bg-gray-50 rounded px-1 py-0.5">
                <input type="checkbox" value="${emp.sys_id}" ${checked} class="annualEmpChk rounded">
                <span>${emp.emp_name||emp.name||emp.sys_id}</span>
            </label>`;
        }).join('');
    }

    document.getElementById('annualEventVisibility')?.addEventListener('change', function() {
        const picker = document.getElementById('annualEventEmpPicker');
        if (this.value === 'private') {
            picker.classList.remove('hidden');
            annualLoadEmpList().then(() => annualRenderEmpPicker([]));
        } else {
            picker.classList.add('hidden');
        }
    });

    document.getElementById('annualAddEventBtn')?.addEventListener('click', () => {
        annualEditingEventId = null;
        document.getElementById('annualEventModalTitle').textContent = 'Add Office Event';
        document.getElementById('annualEventSysId').value = '';
        document.getElementById('annualEventTitle').value = '';
        document.getElementById('annualEventDesc').value  = '';
        document.getElementById('annualEventDate').value  = annualSelectedDate || new Date().toISOString().slice(0,10);
        document.getElementById('annualEventVisibility').value = 'public';
        document.getElementById('annualEventEmpPicker').classList.add('hidden');
        document.getElementById('annualEventModal').classList.remove('hidden');
    });

    window.annualEditEvent = function(sysId) {
        // Find event in cache
        let ev = null;
        for (const evts of Object.values(annualEventCache)) {
            ev = evts.find(e => e.sys_id === sysId);
            if (ev) break;
        }
        if (!ev) return;
        annualEditingEventId = sysId;
        document.getElementById('annualEventModalTitle').textContent = 'Edit Office Event';
        document.getElementById('annualEventSysId').value     = sysId;
        document.getElementById('annualEventTitle').value     = ev.title || '';
        document.getElementById('annualEventDesc').value      = ev.description || '';
        document.getElementById('annualEventDate').value      = ev.event_date || '';
        document.getElementById('annualEventVisibility').value = ev.visibility || 'public';
        const picker = document.getElementById('annualEventEmpPicker');
        if (ev.visibility === 'private') {
            picker.classList.remove('hidden');
            const selectedIds = Array.isArray(ev.visible_to) ? ev.visible_to : [];
            annualLoadEmpList().then(() => annualRenderEmpPicker(selectedIds));
        } else {
            picker.classList.add('hidden');
        }
        document.getElementById('annualEventModal').classList.remove('hidden');
    };

    document.getElementById('annualEventForm')?.addEventListener('submit', async function(e) {
        e.preventDefault();
        const sysId      = document.getElementById('annualEventSysId').value;
        const date       = document.getElementById('annualEventDate').value;
        const title      = document.getElementById('annualEventTitle').value.trim();
        const desc       = document.getElementById('annualEventDesc').value.trim();
        const visibility = document.getElementById('annualEventVisibility').value;
        // Collect selected employee ids
        const visibleTo = visibility === 'private'
            ? [...document.querySelectorAll('.annualEmpChk:checked')].map(c => c.value)
            : [];

        const btn = document.getElementById('annualEventSaveBtn');
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i>Saving…';

        try {
            if (sysId) {
                await apiPost('/api/calendar-events/endpoints.php', {action:'update', sys_id:sysId, date, title, description:desc, visibility, visible_to: visibleTo});
            } else {
                await apiPost('/api/calendar-events/endpoints.php', {action:'add', date, title, description:desc, visibility, visible_to: visibleTo});
            }
            // Refresh event cache for this date
            const eRes = await apiPost('/api/calendar-events/endpoints.php', {action:'list', year: annualYear});
            annualEventCache = {};
            (eRes.data || []).forEach(ev => {
                if (!annualEventCache[ev.event_date]) annualEventCache[ev.event_date] = [];
                annualEventCache[ev.event_date].push(ev);
            });
            document.getElementById('annualEventModal').classList.add('hidden');
            annualRefreshCell(date);
            // If day panel is open on this date, refresh it
            if (annualSelectedDate === date) annualSelectDay(date);
        } catch(er) {
            alert('Save failed: ' + er.message);
        } finally {
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-save mr-1"></i>Save Event';
        }
    });

    window.annualDeleteEvent = async function(sysId, dateStr) {
        if (!confirm('Delete this event?')) return;
        try {
            await apiPost('/api/calendar-events/endpoints.php', {action:'delete', sys_id: sysId});
            // Remove from cache
            if (annualEventCache[dateStr]) {
                annualEventCache[dateStr] = annualEventCache[dateStr].filter(e => e.sys_id !== sysId);
                if (!annualEventCache[dateStr].length) delete annualEventCache[dateStr];
            }
            annualRefreshCell(dateStr);
            if (annualSelectedDate === dateStr) annualSelectDay(dateStr);
        } catch(e) { alert('Delete failed.'); }
    };
<?php endif; ?>
