/**
 * FILE PATH: /pages/task-tabs/ww-package/itinerary.js
 * Tab: Itinerary — Day-by-day builder
 *
 * Features:
 *  - Day list (Day 1…N), dates auto-synced from confirmed air ticket
 *  - Per-day items: type=activity (from master data + variant select)
 *                   type=plain (free text)
 *  - Activity search modal (pulls from api/masterdata/activities/list.php)
 *  - Add plain item inline
 *  - Drag-free reorder via ↑↓ buttons
 *  - Auto-save on every change (debounced 800ms)
 *  - "Set Trip Days" to init/resize day list
 *  - Date auto-populate from confirmed air ticket departure date
 *
 * State:
 *  window._pk.data.pk_itinerary  — array of day objects (see migration SQL)
 *  window._pk.data.trip_days     — INT
 *  window._pk.data.start_date    — DATE string | null
 */

// ── Local ID generator ────────────────────────────────────────
function _pkItnId() {
    return 'ITM-' + Math.random().toString(36).slice(2,8).toUpperCase();
}

// ── Debounce helper ───────────────────────────────────────────
let _pkItnSaveTimer = null;
function _pkItnDebounceSave() {
    clearTimeout(_pkItnSaveTimer);
    _pkItnSaveTimer = setTimeout(_pkItnSave, 800);
}

// ── Get itinerary from state ──────────────────────────────────
function _pkItnDays() {
    return window._pk.data?.pk_itinerary ?? [];
}

// ── Day label ─────────────────────────────────────────────────
function _pkDayLabel(day, dateStr) {
    const dayStr = `Day ${day.day}`;
    if (!dateStr) return dayStr;
    try {
        const d = new Date(dateStr + 'T00:00:00');
        const fmt = d.toLocaleDateString('en-BD', { weekday:'short', day:'2-digit', month:'short' });
        return `${dayStr} — ${fmt}`;
    } catch { return dayStr; }
}

// ── Main render ───────────────────────────────────────────────
window._renderPkItinerary = function() {
    const panel = document.getElementById('pk-panel-itinerary');
    if (!panel) return;

    const days      = _pkItnDays();
    const tripDays  = window._pk.data?.trip_days  ?? 0;
    const startDate = window._pk.data?.start_date ?? null;
    const confData  = window._pk.data?.pk_confirmation ?? null;

    // Try to resolve start_date from confirmed air ticket
    const resolvedStart = startDate || _pkResolveStartDate();

    panel.innerHTML = `
    <div style="padding:14px 0;">

        <!-- Header controls -->
        <div style="display:flex;align-items:center;justify-content:space-between;
                    flex-wrap:wrap;gap:10px;margin-bottom:16px;">
            <div style="font-size:.9rem;font-weight:800;color:#0F172A;">
                🗓️ Itinerary
                ${tripDays ? `<span style="font-size:.75rem;font-weight:600;color:#6366f1;
                    margin-left:8px;">${tripDays} day${tripDays>1?'s':''}</span>` : ''}
                ${resolvedStart ? `<span style="font-size:.73rem;color:#64748B;margin-left:6px;">
                    from ${resolvedStart}</span>` : ''}
            </div>
            <div style="display:flex;gap:8px;align-items:center;">
                ${resolvedStart && !startDate ? `
                <button onclick="window._pkItnSetStartDate('${_pke(resolvedStart)}')"
                        style="padding:6px 12px;background:#EEF2FF;color:#4F46E5;border:1px solid #C7D2FE;
                               border-radius:8px;font-size:.75rem;font-weight:700;cursor:pointer;">
                    📅 Use AT Date
                </button>` : ''}
                <button onclick="window._pkItnSetDaysModal()"
                        style="padding:7px 14px;background:#F1F5F9;color:#475569;border:none;
                               border-radius:8px;font-size:.78rem;font-weight:700;cursor:pointer;">
                    ⚙️ Set Days
                </button>
                <button onclick="window._pkItnAddDay()"
                        style="padding:7px 14px;background:#4F46E5;color:#fff;border:none;
                               border-radius:8px;font-size:.78rem;font-weight:700;cursor:pointer;">
                    + Add Day
                </button>
            </div>
        </div>

        ${days.length === 0 ? `
        <div style="padding:48px 24px;text-align:center;background:#F8FAFC;
                    border:2px dashed #E2E8F0;border-radius:14px;">
            <div style="font-size:2rem;margin-bottom:10px;">🗺️</div>
            <div style="font-size:.9rem;font-weight:700;color:#0F172A;margin-bottom:6px;">
                Itinerary empty
            </div>
            <div style="font-size:.8rem;color:#94A3B8;margin-bottom:16px;">
                Set trip days to get started, or add days one by one.
            </div>
            <button onclick="window._pkItnSetDaysModal()"
                    style="padding:10px 24px;background:#4F46E5;color:#fff;border:none;
                           border-radius:10px;font-size:.85rem;font-weight:700;cursor:pointer;">
                ⚙️ Set Trip Days
            </button>
        </div>` : `
        <div id="pk-itn-days" style="display:flex;flex-direction:column;gap:12px;">
            ${days.map((day, di) => _pkDayCard(day, di, days.length, resolvedStart)).join('')}
        </div>`}

    </div>`;
};

// ── Day card ──────────────────────────────────────────────────
function _pkDayCard(day, di, totalDays, startDate) {
    // Compute this day's date
    let dayDate = day.date ?? null;
    if (!dayDate && startDate) {
        try {
            const d = new Date(startDate + 'T00:00:00');
            d.setDate(d.getDate() + (day.day - 1));
            dayDate = d.toISOString().slice(0,10);
        } catch {}
    }

    const label  = _pkDayLabel(day, dayDate);
    const items  = day.items ?? [];

    return `
    <div class="pk-day-card" data-day="${day.day}"
         style="background:#fff;border:1.5px solid #E2E8F0;border-radius:14px;overflow:hidden;">

        <!-- Day header -->
        <div style="display:flex;align-items:center;justify-content:space-between;
                    padding:11px 14px;background:#F8FAFC;border-bottom:1px solid #E2E8F0;">
            <div style="display:flex;align-items:center;gap:10px;">
                <span style="background:#6366f1;color:#fff;font-size:.72rem;font-weight:800;
                             padding:3px 9px;border-radius:6px;">Day ${day.day}</span>
                ${dayDate
                    ? `<span style="font-size:.78rem;color:#475569;font-weight:600;">${_pkFmtDate(dayDate)}</span>`
                    : `<span style="font-size:.75rem;color:#94A3B8;">Date TBD</span>`}
                <input id="pk-day-label-${day.day}" type="text"
                       value="${_pke(day.label ?? '')}"
                       placeholder="Custom label (optional)"
                       onchange="window._pkItnUpdateDayLabel(${day.day}, this.value)"
                       style="border:none;outline:none;font-size:.78rem;color:#64748B;
                              background:transparent;width:160px;">
            </div>
            <div style="display:flex;align-items:center;gap:6px;">
                ${di > 0 ? `<button onclick="window._pkItnMoveDay(${day.day},'up')"
                    title="Move up"
                    style="width:26px;height:26px;background:#F1F5F9;border:none;border-radius:6px;
                           cursor:pointer;font-size:.75rem;color:#64748B;">↑</button>` : ''}
                ${di < totalDays-1 ? `<button onclick="window._pkItnMoveDay(${day.day},'down')"
                    title="Move down"
                    style="width:26px;height:26px;background:#F1F5F9;border:none;border-radius:6px;
                           cursor:pointer;font-size:.75rem;color:#64748B;">↓</button>` : ''}
                <button onclick="window._pkItnDeleteDay(${day.day})"
                    title="Remove day"
                    style="width:26px;height:26px;background:#FEE2E2;border:none;border-radius:6px;
                           cursor:pointer;font-size:.75rem;color:#B91C1C;">✕</button>
            </div>
        </div>

        <!-- Items list -->
        <div style="padding:10px 14px;display:flex;flex-direction:column;gap:8px;">
            ${items.length === 0
                ? `<div style="font-size:.78rem;color:#CBD5E1;text-align:center;padding:12px 0;">
                       No items yet — add activity or plain note below
                   </div>`
                : items.map((item, ii) => _pkItemRow(item, day.day, ii, items.length)).join('')}

            <!-- Add item row -->
            <div style="display:flex;gap:8px;margin-top:6px;">
                <button onclick="window._pkItnOpenActivityModal(${day.day})"
                        style="flex:1;padding:8px;background:#EEF2FF;color:#4F46E5;border:1px solid #C7D2FE;
                               border-radius:9px;font-size:.78rem;font-weight:700;cursor:pointer;">
                    🎯 Add Activity
                </button>
                <button onclick="window._pkItnAddPlainItem(${day.day})"
                        style="flex:1;padding:8px;background:#F8FAFC;color:#475569;border:1px solid #E2E8F0;
                               border-radius:9px;font-size:.78rem;font-weight:700;cursor:pointer;">
                    📝 Add Note
                </button>
            </div>
        </div>
    </div>`;
}

// ── Item row ──────────────────────────────────────────────────
function _pkItemRow(item, dayNum, ii, total) {
    const isActivity = item.type === 'activity';

    const badge = isActivity
        ? `<span style="background:#FEF3C7;color:#92400E;font-size:.65rem;font-weight:800;
                        padding:2px 6px;border-radius:4px;">ACTIVITY</span>`
        : `<span style="background:#F1F5F9;color:#64748B;font-size:.65rem;font-weight:800;
                        padding:2px 6px;border-radius:4px;">NOTE</span>`;

    const title = isActivity
        ? `<span style="font-size:.85rem;font-weight:700;color:#0F172A;">${_pke(item.activity_name ?? '—')}</span>
           ${item.variant_name ? `<span style="font-size:.73rem;color:#6366f1;margin-left:6px;">
               [${_pke(item.variant_name)}]</span>` : ''}`
        : `<input type="text" value="${_pke(item.title ?? '')}"
                  placeholder="Item title…"
                  onchange="window._pkItnUpdateItemTitle('${_pke(item.id)}',${dayNum},this.value)"
                  style="border:none;outline:none;font-size:.85rem;font-weight:700;
                         color:#0F172A;background:transparent;width:100%;">`;

    return `
    <div class="pk-item-row" data-item-id="${_pke(item.id)}"
         style="display:flex;align-items:flex-start;gap:8px;padding:9px 10px;
                background:${isActivity?'#FAFAFE':'#FAFAFA'};border-radius:10px;
                border:1px solid ${isActivity?'#E0E7FF':'#F1F5F9'};">
        <div style="flex:1;min-width:0;">
            <div style="display:flex;align-items:center;gap:6px;flex-wrap:wrap;margin-bottom:4px;">
                ${badge}
                ${item.time ? `<span style="font-size:.72rem;color:#6366f1;font-weight:700;">
                    🕐 ${_pke(item.time)}</span>` : ''}
                ${item.duration ? `<span style="font-size:.72rem;color:#94A3B8;">${_pke(item.duration)}</span>` : ''}
            </div>
            <div style="display:flex;align-items:center;gap:6px;">
                ${title}
            </div>
            ${item.notes ? `<div style="font-size:.73rem;color:#64748B;margin-top:3px;
                                         font-style:italic;">${_pke(item.notes)}</div>` : ''}
        </div>
        <div style="display:flex;gap:4px;flex-shrink:0;">
            ${ii > 0 ? `<button onclick="window._pkItnMoveItem('${_pke(item.id)}',${dayNum},'up')"
                style="width:22px;height:22px;background:#F1F5F9;border:none;border-radius:5px;
                       cursor:pointer;font-size:.7rem;color:#64748B;">↑</button>` : ''}
            ${ii < total-1 ? `<button onclick="window._pkItnMoveItem('${_pke(item.id)}',${dayNum},'down')"
                style="width:22px;height:22px;background:#F1F5F9;border:none;border-radius:5px;
                       cursor:pointer;font-size:.7rem;color:#64748B;">↓</button>` : ''}
            <button onclick="window._pkItnEditItemTime('${_pke(item.id)}',${dayNum})"
                title="Set time"
                style="width:22px;height:22px;background:#EEF2FF;border:none;border-radius:5px;
                       cursor:pointer;font-size:.7rem;color:#6366f1;">🕐</button>
            <button onclick="window._pkItnDeleteItem('${_pke(item.id)}',${dayNum})"
                style="width:22px;height:22px;background:#FEE2E2;border:none;border-radius:5px;
                       cursor:pointer;font-size:.7rem;color:#B91C1C;">✕</button>
        </div>
    </div>`;
}

// ── Date formatter ────────────────────────────────────────────
function _pkFmtDate(d) {
    if (!d) return '';
    try {
        return new Date(d + 'T00:00:00').toLocaleDateString('en-BD',
            { weekday:'short', day:'2-digit', month:'short', year:'numeric' });
    } catch { return d; }
}

// ── Resolve start date from confirmed air ticket ──────────────
function _pkResolveStartDate() {
    // Try pk_confirmation linked_at_confirmation
    const conf = window._pk.data?.pk_confirmation;
    if (conf?.at_departure_date) return conf.at_departure_date;

    // Try confirmed_services from the work (loaded via get_confirmed_services)
    const cs = window._pk.confirmedServices;
    if (cs?.air_ticket?.departure_date) return cs.air_ticket.departure_date;

    return null;
}

// ── Set start date from AT ────────────────────────────────────
window._pkItnSetStartDate = async function(date) {
    if (!window._pk.data) return;
    window._pk.data.start_date = date;
    await _pkItnSave();
    _renderPkItinerary();
    pkT('success', `Start date set: ${_pkFmtDate(date)}`);
};

// ── Set days modal ────────────────────────────────────────────
window._pkItnSetDaysModal = function() {
    const current = window._pk.data?.trip_days ?? '';
    document.getElementById('pkSetDaysModal')?.remove();

    const modal = document.createElement('div');
    modal.id = 'pkSetDaysModal';
    modal.style.cssText = 'position:fixed;inset:0;z-index:9999;display:flex;align-items:center;justify-content:center;padding:16px;background:rgba(0,0,0,.45);';
    modal.innerHTML = `
    <div style="background:#fff;border-radius:16px;box-shadow:0 20px 60px rgba(0,0,0,.2);
                width:100%;max-width:320px;padding:20px;">
        <div style="font-size:.95rem;font-weight:800;color:#0F172A;margin-bottom:16px;">⚙️ Set Trip Days</div>
        <div style="margin-bottom:14px;">
            <label style="font-size:.78rem;font-weight:700;color:#374151;display:block;margin-bottom:6px;">
                Number of Days
            </label>
            <input id="pkSetDaysInput" type="number" min="1" max="30" value="${current || ''}"
                   placeholder="e.g. 5"
                   style="width:100%;padding:10px 12px;border:1.5px solid #E2E8F0;border-radius:9px;
                          font-size:1rem;outline:none;box-sizing:border-box;">
        </div>
        <div style="font-size:.73rem;color:#94A3B8;margin-bottom:16px;">
            ⚠️ Reducing days will remove extra day data. Increasing adds empty days.
        </div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;">
            <button onclick="document.getElementById('pkSetDaysModal').remove()"
                    style="padding:10px;background:#F1F5F9;border:none;border-radius:9px;
                           cursor:pointer;font-size:.85rem;font-weight:700;color:#475569;">Cancel</button>
            <button onclick="window._pkItnApplyDays()"
                    style="padding:10px;background:#4F46E5;border:none;border-radius:9px;
                           cursor:pointer;font-size:.85rem;font-weight:800;color:#fff;">Apply</button>
        </div>
    </div>`;
    document.body.appendChild(modal);
    modal.addEventListener('click', e => { if (e.target === modal) modal.remove(); });
    setTimeout(() => document.getElementById('pkSetDaysInput')?.focus(), 50);
};

window._pkItnApplyDays = async function() {
    const n = parseInt(document.getElementById('pkSetDaysInput')?.value);
    if (!n || n < 1 || n > 30) { pkT('error', '1–30 days দিন'); return; }
    document.getElementById('pkSetDaysModal')?.remove();

    const current = _pkItnDays();
    let newDays = [];

    for (let i = 1; i <= n; i++) {
        const existing = current.find(d => d.day === i);
        newDays.push(existing ?? { day: i, date: null, label: '', items: [] });
    }

    window._pk.data.pk_itinerary = newDays;
    window._pk.data.trip_days    = n;

    await _pkItnSave();
    _renderPkItinerary();
    pkT('success', `${n} days set`);
};

// ── Add single day ────────────────────────────────────────────
window._pkItnAddDay = async function() {
    const days = _pkItnDays();
    const next = (days[days.length - 1]?.day ?? 0) + 1;
    days.push({ day: next, date: null, label: '', items: [] });
    window._pk.data.pk_itinerary = days;
    window._pk.data.trip_days    = days.length;
    await _pkItnSave();
    _renderPkItinerary();
};

// ── Delete day ────────────────────────────────────────────────
window._pkItnDeleteDay = async function(dayNum) {
    if (!confirm(`Day ${dayNum} মুছবেন? এর সব items মুছে যাবে।`)) return;
    let days = _pkItnDays().filter(d => d.day !== dayNum);
    // Re-number
    days = days.map((d, i) => ({ ...d, day: i + 1 }));
    window._pk.data.pk_itinerary = days;
    window._pk.data.trip_days    = days.length;
    await _pkItnSave();
    _renderPkItinerary();
};

// ── Move day ──────────────────────────────────────────────────
window._pkItnMoveDay = async function(dayNum, dir) {
    const days = _pkItnDays();
    const idx  = days.findIndex(d => d.day === dayNum);
    if (idx < 0) return;
    const swapIdx = dir === 'up' ? idx - 1 : idx + 1;
    if (swapIdx < 0 || swapIdx >= days.length) return;
    [days[idx], days[swapIdx]] = [days[swapIdx], days[idx]];
    // Re-number
    days.forEach((d, i) => { d.day = i + 1; });
    window._pk.data.pk_itinerary = days;
    await _pkItnSave();
    _renderPkItinerary();
};

// ── Update day label ──────────────────────────────────────────
window._pkItnUpdateDayLabel = function(dayNum, label) {
    const day = _pkItnDays().find(d => d.day === dayNum);
    if (day) day.label = label;
    _pkItnDebounceSave();
};

// ── Add plain item ────────────────────────────────────────────
window._pkItnAddPlainItem = async function(dayNum) {
    const day = _pkItnDays().find(d => d.day === dayNum);
    if (!day) return;
    day.items = day.items ?? [];
    day.items.push({ id: _pkItnId(), type: 'plain', title: '', time: '', notes: '' });
    window._pk.data.pk_itinerary = _pkItnDays();
    await _pkItnSave();
    _renderPkItinerary();
};

// ── Update plain item title ───────────────────────────────────
window._pkItnUpdateItemTitle = function(itemId, dayNum, title) {
    const day = _pkItnDays().find(d => d.day === dayNum);
    const item = day?.items?.find(i => i.id === itemId);
    if (item) item.title = title;
    _pkItnDebounceSave();
};

// ── Edit item time ────────────────────────────────────────────
window._pkItnEditItemTime = function(itemId, dayNum) {
    const day  = _pkItnDays().find(d => d.day === dayNum);
    const item = day?.items?.find(i => i.id === itemId);
    if (!item) return;

    document.getElementById('pkItemTimeModal')?.remove();
    const modal = document.createElement('div');
    modal.id = 'pkItemTimeModal';
    modal.style.cssText = 'position:fixed;inset:0;z-index:9999;display:flex;align-items:center;justify-content:center;padding:16px;background:rgba(0,0,0,.4);';
    modal.innerHTML = `
    <div style="background:#fff;border-radius:14px;box-shadow:0 16px 48px rgba(0,0,0,.2);
                width:100%;max-width:300px;padding:18px;">
        <div style="font-size:.9rem;font-weight:800;color:#0F172A;margin-bottom:14px;">🕐 Set Time</div>
        <div style="margin-bottom:10px;">
            <label style="font-size:.75rem;font-weight:700;color:#374151;display:block;margin-bottom:4px;">Time</label>
            <input id="pkItemTimeInput" type="text" value="${_pke(item.time ?? '')}" placeholder="e.g. 09:00 AM"
                   style="width:100%;padding:8px 12px;border:1.5px solid #E2E8F0;border-radius:8px;
                          font-size:.85rem;outline:none;box-sizing:border-box;">
        </div>
        <div style="margin-bottom:14px;">
            <label style="font-size:.75rem;font-weight:700;color:#374151;display:block;margin-bottom:4px;">Notes</label>
            <textarea id="pkItemNotesInput" rows="2" placeholder="Short note…"
                      style="width:100%;padding:8px 12px;border:1.5px solid #E2E8F0;border-radius:8px;
                             font-size:.82rem;outline:none;resize:none;box-sizing:border-box;">${_pke(item.notes ?? '')}</textarea>
        </div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;">
            <button onclick="document.getElementById('pkItemTimeModal').remove()"
                    style="padding:9px;background:#F1F5F9;border:none;border-radius:8px;
                           cursor:pointer;font-size:.82rem;font-weight:700;color:#475569;">Cancel</button>
            <button onclick="window._pkSaveItemTime('${_pke(itemId)}',${dayNum})"
                    style="padding:9px;background:#4F46E5;border:none;border-radius:8px;
                           cursor:pointer;font-size:.82rem;font-weight:800;color:#fff;">Save</button>
        </div>
    </div>`;
    document.body.appendChild(modal);
    modal.addEventListener('click', e => { if (e.target === modal) modal.remove(); });
};

window._pkSaveItemTime = async function(itemId, dayNum) {
    const time  = document.getElementById('pkItemTimeInput')?.value.trim();
    const notes = document.getElementById('pkItemNotesInput')?.value.trim();
    document.getElementById('pkItemTimeModal')?.remove();

    const day  = _pkItnDays().find(d => d.day === dayNum);
    const item = day?.items?.find(i => i.id === itemId);
    if (item) { item.time = time; item.notes = notes; }
    await _pkItnSave();
    _renderPkItinerary();
};

// ── Delete item ───────────────────────────────────────────────
window._pkItnDeleteItem = async function(itemId, dayNum) {
    const day = _pkItnDays().find(d => d.day === dayNum);
    if (!day) return;
    day.items = (day.items ?? []).filter(i => i.id !== itemId);
    await _pkItnSave();
    _renderPkItinerary();
};

// ── Move item ─────────────────────────────────────────────────
window._pkItnMoveItem = async function(itemId, dayNum, dir) {
    const day   = _pkItnDays().find(d => d.day === dayNum);
    if (!day) return;
    const items = day.items ?? [];
    const idx   = items.findIndex(i => i.id === itemId);
    const swap  = dir === 'up' ? idx - 1 : idx + 1;
    if (swap < 0 || swap >= items.length) return;
    [items[idx], items[swap]] = [items[swap], items[idx]];
    day.items = items;
    await _pkItnSave();
    _renderPkItinerary();
};

/* ── Activity Search Modal ───────────────────────────────────── */
window._pkItnOpenActivityModal = function(dayNum) {
    window._pkItnTargetDay = dayNum;
    document.getElementById('pkActivityModal')?.remove();

    const modal = document.createElement('div');
    modal.id = 'pkActivityModal';
    modal.style.cssText = 'position:fixed;inset:0;z-index:9999;display:flex;align-items:center;justify-content:center;padding:16px;background:rgba(0,0,0,.45);';
    modal.innerHTML = `
    <div style="background:#fff;border-radius:20px;box-shadow:0 20px 60px rgba(0,0,0,.25);
                width:100%;max-width:520px;max-height:88vh;display:flex;flex-direction:column;">

        <div style="display:flex;align-items:center;justify-content:space-between;
                    padding:14px 18px;border-bottom:1px solid #F1F5F9;">
            <div style="font-size:.95rem;font-weight:800;color:#0F172A;">🎯 Add Activity — Day ${dayNum}</div>
            <button onclick="document.getElementById('pkActivityModal').remove()"
                    style="width:28px;height:28px;background:#F1F5F9;border:none;border-radius:8px;
                           cursor:pointer;font-size:.9rem;color:#64748B;">✕</button>
        </div>

        <!-- Search -->
        <div style="padding:12px 16px;border-bottom:1px solid #F1F5F9;">
            <input id="pkActSearch" type="text" placeholder="Search activities…"
                   oninput="window._pkActSearch(this.value)"
                   style="width:100%;padding:9px 12px;border:1.5px solid #E2E8F0;border-radius:9px;
                          font-size:.85rem;outline:none;box-sizing:border-box;">
        </div>

        <!-- Results -->
        <div id="pkActResults" style="flex:1;overflow-y:auto;padding:10px 14px;">
            <div style="text-align:center;padding:24px;color:#94A3B8;font-size:.85rem;">
                Type to search activities…
            </div>
        </div>

        <!-- Variant select (hidden until activity picked) -->
        <div id="pkActVariantSection" style="display:none;padding:12px 16px;
             border-top:1px solid #F1F5F9;background:#FAFAFE;">
            <div style="font-size:.78rem;font-weight:700;color:#374151;margin-bottom:8px;">
                Select Variant (optional)
            </div>
            <div id="pkActVariantList" style="display:flex;flex-direction:column;gap:6px;"></div>
            <button onclick="window._pkItnAddActivityItem(null)"
                    style="margin-top:8px;width:100%;padding:9px;background:#F1F5F9;border:none;
                           border-radius:8px;cursor:pointer;font-size:.8rem;font-weight:700;color:#475569;">
                Skip variant → Add anyway
            </button>
        </div>
    </div>`;
    document.body.appendChild(modal);
    modal.addEventListener('click', e => { if (e.target === modal) modal.remove(); });
    // Load initial list
    window._pkActSearch('');
};

let _pkActSearchTimer = null;
window._pkActSearch = function(q) {
    clearTimeout(_pkActSearchTimer);
    _pkActSearchTimer = setTimeout(() => _pkActDoSearch(q), 300);
};

async function _pkActDoSearch(q) {
    const el = document.getElementById('pkActResults');
    if (!el) return;
    el.innerHTML = `<div style="text-align:center;padding:24px;color:#94A3B8;font-size:.82rem;">Searching…</div>`;

    try {
        const base = window._pk.cfg.api.packageServices.replace('api/package-services/endpoints.php','');
        const url  = `${base}api/masterdata/activities/list.php?limit=20&status=active&search=${encodeURIComponent(q)}`;
        const json = await fetch(url).then(r => r.json());
        const rows = json.data ?? [];

        if (!rows.length) {
            el.innerHTML = `<div style="text-align:center;padding:24px;color:#94A3B8;font-size:.82rem;">
                No activities found${q ? ` for "${_pke(q)}"` : ''}.</div>`;
            return;
        }

        el.innerHTML = rows.map(a => `
        <div onclick="window._pkActSelectActivity('${_pke(a.sys_id)}','${_pke(a.name)}')"
             style="display:flex;align-items:center;gap:10px;padding:9px 10px;
                    border-radius:10px;cursor:pointer;border:1px solid #F1F5F9;margin-bottom:6px;
                    transition:background .1s;"
             onmouseover="this.style.background='#EEF2FF'" onmouseout="this.style.background=''">
            ${a.thumb ? `<img src="${_pke(a.thumb)}" style="width:44px;height:44px;object-fit:cover;
                              border-radius:8px;flex-shrink:0;">` :
                `<div style="width:44px;height:44px;background:#EEF2FF;border-radius:8px;
                              flex-shrink:0;display:flex;align-items:center;justify-content:center;
                              font-size:1.2rem;">🎯</div>`}
            <div style="flex:1;min-width:0;">
                <div style="font-size:.85rem;font-weight:700;color:#0F172A;">${_pke(a.name)}</div>
                <div style="font-size:.73rem;color:#64748B;">
                    ${_pke(a.city_name ?? '')}${a.city_name && a.country_name ? ' · ' : ''}${_pke(a.country_name ?? '')}
                    ${a.duration_hours ? ` · ${a.duration_hours}h` : ''}
                </div>
            </div>
            <span style="font-size:.7rem;background:#F1F5F9;color:#64748B;padding:2px 7px;
                         border-radius:5px;flex-shrink:0;">${_pke(a.type ?? '')}</span>
        </div>`).join('');
    } catch (e) {
        el.innerHTML = `<div style="text-align:center;padding:24px;color:#ef4444;font-size:.82rem;">Load error</div>`;
    }
}

window._pkActSelectActivity = async function(sysId, name) {
    window._pkItnSelectedActivity = { sys_id: sysId, name };
    window._pkItnSelectedVariant  = null;

    // Load variants
    const varSection = document.getElementById('pkActVariantSection');
    const varList    = document.getElementById('pkActVariantList');
    if (!varSection || !varList) return;

    varSection.style.display = 'block';
    varList.innerHTML = `<div style="font-size:.78rem;color:#94A3B8;">Loading variants…</div>`;

    try {
        const base = window._pk.cfg.api.packageServices.replace('api/package-services/endpoints.php','');
        const url  = `${base}api/masterdata/activities/variant-list.php?activity_sys_id=${encodeURIComponent(sysId)}`;
        const json = await fetch(url).then(r => r.json());
        const vars = json.data ?? [];

        if (!vars.length) {
            // No variants — add directly
            varSection.style.display = 'none';
            window._pkItnAddActivityItem(null);
            return;
        }

        varList.innerHTML = vars.map(v => `
        <div onclick="window._pkItnAddActivityItem('${_pke(v.sys_id)}','${_pke(v.variant_name)}')"
             style="display:flex;align-items:center;justify-content:space-between;
                    padding:8px 10px;border:1px solid #E2E8F0;border-radius:8px;cursor:pointer;
                    transition:background .1s;"
             onmouseover="this.style.background='#EEF2FF';this.style.borderColor='#C7D2FE'"
             onmouseout="this.style.background='';this.style.borderColor='#E2E8F0'">
            <div>
                <div style="font-size:.82rem;font-weight:700;color:#0F172A;">${_pke(v.variant_name)}</div>
                <div style="font-size:.72rem;color:#64748B;">
                    ${v.currency_code} ${parseFloat(v.sell_price||0).toLocaleString()} · ${_pke(v.price_basis||'per_pax')}
                    ${v.transport_mode && v.transport_mode!=='none' ? ' · 🚌 '+_pke(v.transport_mode) : ''}
                    ${v.meal_lunch ? ' · 🍽️ Lunch' : ''}
                </div>
            </div>
            <span style="font-size:.72rem;color:#6366f1;font-weight:700;">Select →</span>
        </div>`).join('');
    } catch {
        varList.innerHTML = `<div style="font-size:.75rem;color:#ef4444;">Variant load failed</div>`;
    }
};

window._pkItnAddActivityItem = async function(variantSysId, variantName) {
    document.getElementById('pkActivityModal')?.remove();

    const act     = window._pkItnSelectedActivity;
    const dayNum  = window._pkItnTargetDay;
    if (!act || !dayNum) return;

    const day = _pkItnDays().find(d => d.day === dayNum);
    if (!day) return;
    day.items = day.items ?? [];
    day.items.push({
        id:              _pkItnId(),
        type:            'activity',
        activity_sys_id: act.sys_id,
        activity_name:   act.name,
        variant_sys_id:  variantSysId  ?? null,
        variant_name:    variantName   ?? null,
        time:            '',
        duration:        '',
        notes:           '',
    });

    await _pkItnSave();
    _renderPkItinerary();
    pkT('success', `Added: ${act.name}`);
};

/* ── Add activity from mindboard note card ───────────────────── */
window._pkItnAddFromNote = function(actSysId, actName, dayNum) {
    window._pkItnTargetDay         = dayNum;
    window._pkItnSelectedActivity  = { sys_id: actSysId, name: actName };
    window._pkItnSelectedVariant   = null;
    window._pkItnAddActivityItem(null);
};

/* ── Save to server ──────────────────────────────────────────── */
async function _pkItnSave() {
    try {
        const days      = window._pk.data?.pk_itinerary ?? [];
        const tripDays  = window._pk.data?.trip_days    ?? days.length;
        const startDate = window._pk.data?.start_date   ?? null;

        const json = await window._pkApi({
            action:        'save_itinerary',
            pk_itinerary:  days,
            trip_days:     tripDays,
            start_date:    startDate,
        });
        if (json.status !== 'success') pkT('error', json.message ?? 'Save failed');
    } catch (e) {
        pkT('error', 'Network error saving itinerary');
        console.error('[itinerary] save:', e);
    }
}