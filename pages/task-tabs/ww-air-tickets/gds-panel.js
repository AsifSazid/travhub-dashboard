/**
 * FILE PATH: /pages/task-tabs/ww-air-tickets/gds-panel.js
 * GDS Command Panel — inject, render stored/live commands, divider resize, regenerate
 */

// ── Copy helpers (global — onclick দিয়ে call হয়) ─────────────
window._gdsStoredCmds = [];
window._gdsCopySingle = function(idx) {
    const cmd = window._gdsStoredCmds[idx] ?? '';
    if (navigator.clipboard) navigator.clipboard.writeText(cmd);
    const el = document.getElementById('at-gds-toast');
    if (el) { el.textContent = cmd + ' copied'; el.style.opacity = '1'; setTimeout(() => el.style.opacity = '0', 1500); }
};
window._gdsCopyAll = function() {
    const lines = window._gdsStoredCmds.join('\n');
    if (navigator.clipboard) navigator.clipboard.writeText(lines);
    const el = document.getElementById('at-gds-toast');
    if (el) { el.textContent = window._gdsStoredCmds.length + ' lines copied'; el.style.opacity = '1'; setTimeout(() => el.style.opacity = '0', 1500); }
};

// ── GDS / Portal sub-tab state ────────────────────────────────
// stage -> 'gds' | 'portal'। GDS সবসময় ডিফল্টে খোলা থাকে; Portal ক্লিক
// করলে Air Ticket-এর portal credential (Master Data > Portal Links থেকে)
// দেখায়। GDS panel-এর ভেতরের বাকি কোনো লজিক (divider resize, regenerate,
// derived-facts) স্পর্শ করা হয়নি — শুধু header-এর নিচে একটা নতুন layer।
window._gdsSubTabState = window._gdsSubTabState || {};

function _gdsSubTabsHtml(stage) {
    const active = window._gdsSubTabState[stage] ?? 'gds';
    const tabBtn = (key, label) => `<button onclick="_gdsSwitchSubTab('${stage}','${key}')"
        style="flex:1;padding:7px 0;font-size:11px;font-weight:700;letter-spacing:.05em;text-transform:uppercase;
               border:none;cursor:pointer;background:${active===key?'#12172B':'transparent'};
               color:${active===key?'#50BC81':'rgba(255,255,255,.45)'};
               border-bottom:2px solid ${active===key?'#50BC81':'transparent'};">
        ${label}
    </button>`;
    return `<div id="at-gds-subtabs-${stage}" style="display:flex;background:#1C2340;border-bottom:1px solid rgba(255,255,255,.08);flex-shrink:0;">
        ${tabBtn('gds','GDS')}
        ${tabBtn('portal','Portal')}
    </div>`;
}

window._gdsSwitchSubTab = function(stage, key) {
    window._gdsSubTabState[stage] = key;

    // tab-bar visual state রিফ্রেশ
    const tabBar = document.getElementById(`at-gds-subtabs-${stage}`);
    if (tabBar) tabBar.outerHTML = _gdsSubTabsHtml(stage);

    const body = document.getElementById(`at-gds-body-${stage}`);
    if (!body) return;

    if (key === 'gds') {
        const stageMap = { '1':'mindboard', '2':'quotation', '3':'booking', '4':'confirmation' };
        const stageKey = stageMap[stage] ?? 'mindboard';
        const storedCmds = window._at.data?.commands?.[stageKey] ?? null;
        const facts = _gdsGetDerivedFacts();
        const cmdHtml = storedCmds ? _gdsRenderStoredCommands(storedCmds) : _gdsCommandsHtml(stage, facts);
        body.innerHTML = `<div style="padding:10px 10px 0;">${_gdsDerivedFactsHtml(facts)}</div>`;
        body.insertAdjacentHTML('beforeend', cmdHtml);
    } else {
        _gdsRenderPortalTab(body);
    }
};

// ── Portal tab — Air Ticket portal credentials (Master Data থেকে) ────
// Grid state: 3-column, শুধু portal name (+নিজের credential থাকলে preview)।
// Expanded state (Show More): সেই card full-width নেয়, নিচে সব VISIBLE
// credential (is_hide/access-check pass করা গুলোই) লিস্ট আকারে দেখায় —
// hidden credential-এর অস্তিত্বই বোঝানো হয় না, শুধু can_view=true গুলোই আসে।
window._gdsExpandedPortal = window._gdsExpandedPortal || null; // sys_id | null
window._gdsPortalsCache   = window._gdsPortalsCache   || [];

async function _gdsRenderPortalTab(container) {
    container.innerHTML = `<div style="padding:16px;color:#8A93A8;font-size:12px;"><i class="fas fa-spinner fa-spin mr-1"></i> Loading portals…</div>`;

    try {
        const res  = await fetch('/api/masterdata/portal-links.php?portal_type=air_ticket', { credentials: 'include' });
        const json = await res.json();

        if (!json.success) {
            container.innerHTML = `<div style="padding:16px;color:#E8817A;font-size:12px;">Error: ${_gdsEsc(json.message || 'Failed to load portals')}</div>`;
            return;
        }

        const portals = json.portals ?? [];
        window._gdsPortalsCache = portals;
        window._gdsExpandedPortal = null;

        if (!portals.length) {
            container.innerHTML = `<div style="padding:16px;color:#8A93A8;font-size:12px;">No Air Ticket portals added yet.<br><a href="../pages/portal-links.php" target="_blank" style="color:#50BC81;">Add one in Master Data →</a></div>`;
            return;
        }

        _gdsRenderPortalGrid(container);
    } catch (e) {
        container.innerHTML = `<div style="padding:16px;color:#E8817A;font-size:12px;">Network error loading portals.</div>`;
        console.error(e);
    }
}

function _gdsRenderPortalGrid(container) {
    const portals  = window._gdsPortalsCache;
    const expanded = window._gdsExpandedPortal;

    container.innerHTML = `<div style="padding:10px;display:grid;grid-template-columns:repeat(3,1fr);gap:8px;">
        ${portals.map(p => expanded === p.sys_id
            ? `<div style="grid-column:1/-1;">${_gdsPortalCardExpanded(p)}</div>`
            : _gdsPortalCardCollapsed(p)
        ).join('')}
    </div>`;
}

// ⚠️ Collapsed card-এ password কখনোই দেখানো হয় না — শুধু portal name, আর
// current user-এর নিজের credential থাকলে সেটার username (password না)
// preview হিসেবে। পূর্ণ credential list শুধু expand করলেই আসে।
function _gdsPortalCardCollapsed(p) {
    const creds  = p.credentials ?? [];
    const mine   = creds.find(c => c.is_mine);
    const url    = p.portal_url ? _gdsEsc(p.portal_url) : '';

    return `<div style="background:#1C2340;border:1px solid rgba(255,255,255,.08);border-radius:6px;padding:10px 12px;cursor:pointer;"
                 onclick="_gdsExpandPortal('${p.sys_id}')"
                 onmouseover="this.style.borderColor='rgba(80,188,129,.4)'"
                 onmouseout="this.style.borderColor='rgba(255,255,255,.08)'">
        <div style="display:flex;align-items:center;gap:6px;margin-bottom:${mine ? '6px' : '0'};">
            <span style="color:#fff;font-size:12px;font-weight:700;flex:1;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">${_gdsEsc(p.portal_name)}</span>
            ${url ? `<a href="${url}" target="_blank" onclick="event.stopPropagation()" style="color:#50BC81;font-size:10px;flex-shrink:0;"><i class="fas fa-external-link-alt"></i></a>` : ''}
        </div>
        ${mine ? `<div style="color:#5E6883;font-size:10px;font-family:monospace;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">${_gdsEsc(mine.user_name || '')}</div>` : ''}
    </div>`;
}

// Expanded card — full-width, সব visible credential দেখায়
function _gdsPortalCardExpanded(p) {
    const creds = p.credentials ?? [];
    const url   = p.portal_url ? _gdsEsc(p.portal_url) : '';

    // ⚠️ শুধু can_view=true credential-ই এখানে আসে (backend visibility-check
    // pass করে DB থেকেই এভাবে আসে) — hidden credential "Restricted" বলেও
    // দেখানো হয় না, সেই credential-এর অস্তিত্বই UI-তে reflect হয় না
    const visibleCreds = creds.filter(c => c.can_view);

    const credRows = visibleCreds.length ? visibleCreds.map(c => `
        <div style="display:flex;flex-direction:column;gap:2px;padding:8px 0;border-top:1px solid rgba(255,255,255,.06);">
            <div style="display:flex;justify-content:space-between;align-items:center;">
                <span style="color:#8A93A8;font-size:10px;text-transform:uppercase;letter-spacing:.05em;">User ID</span>
                <span style="color:#E8ECF5;font-size:12px;font-family:monospace;cursor:pointer;" onclick="event.stopPropagation();_gdsCopyText('${_gdsEsc(c.user_name||'')}')" title="Click to copy">${_gdsEsc(c.user_name || '—')}</span>
            </div>
            <div style="display:flex;justify-content:space-between;align-items:center;">
                <span style="color:#8A93A8;font-size:10px;text-transform:uppercase;letter-spacing:.05em;">Password</span>
                <span style="color:#E8ECF5;font-size:12px;font-family:monospace;cursor:pointer;" onclick="event.stopPropagation();_gdsCopyText('${_gdsEsc(c.password||'')}')" title="Click to copy">${_gdsEsc(c.password || '—')}</span>
            </div>
        </div>
    `).join('') : `<div style="padding:8px 0;color:#5E6883;font-size:11px;">No accessible credentials</div>`;

    return `<div style="background:#1C2340;border:1px solid rgba(80,188,129,.4);border-radius:6px;padding:12px 14px;">
        <div style="display:flex;align-items:center;gap:8px;margin-bottom:4px;">
            <span style="color:#fff;font-size:13px;font-weight:700;flex:1;">${_gdsEsc(p.portal_name)}</span>
            ${url ? `<a href="${url}" target="_blank" style="color:#50BC81;font-size:11px;"><i class="fas fa-external-link-alt"></i></a>` : ''}
            <button onclick="event.stopPropagation();_gdsCollapsePortal()" style="background:none;border:none;color:#5E6883;cursor:pointer;font-size:11px;padding:2px 6px;" title="Collapse">
                <i class="fas fa-compress-alt"></i>
            </button>
        </div>
        ${credRows}
    </div>`;
}

window._gdsExpandPortal = function(sysId) {
    window._gdsExpandedPortal = sysId;
    const container = document.querySelector('[id^="at-gds-body-"]')?.closest('div')?.parentElement
        ?? document.querySelector('[id^="at-gds-body-"]');
    // সহজ উপায় — যেই container-এ grid render হয়েছিল সেটা re-render করাই যথেষ্ট;
    // active gds-body খুঁজে বের করে তার ভেতরের portal-grid wrapper রিফ্রেশ করি
    const activeBody = [...document.querySelectorAll('[id^="at-gds-body-"]')].find(el => el.querySelector('[style*="grid-template-columns"]'));
    if (activeBody) _gdsRenderPortalGrid(activeBody);
};

window._gdsCollapsePortal = function() {
    window._gdsExpandedPortal = null;
    const activeBody = [...document.querySelectorAll('[id^="at-gds-body-"]')].find(el => el.querySelector('[style*="grid-template-columns"]'));
    if (activeBody) _gdsRenderPortalGrid(activeBody);
};

window._gdsCopyText = function(text) {
    if (!text) return;
    if (navigator.clipboard) navigator.clipboard.writeText(text);
    const el = document.getElementById('at-gds-toast');
    if (el) { el.textContent = 'Copied!'; el.style.opacity = '1'; setTimeout(() => el.style.opacity = '0', 1200); }
};

function _gdsEsc(s) { return String(s ?? '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#39;'); }

// ── Inject GDS panel into a tab panel ────────────────────────
function _gdsInjectPanel(panel, stage) {
    const stageMap = { '1':'mindboard', '2':'quotation', '3':'booking', '4':'confirmation' };
    const stageKey = stageMap[stage] ?? 'mindboard';
    const storedCmds = window._at.data?.commands?.[stageKey] ?? null;
    const existingContent = panel.querySelector('.at-panel-content')?.innerHTML ?? panel.innerHTML;
    const saved = localStorage.getItem('at_gds_width');
    const gdsW  = saved ? saved + 'px' : '340px';
    const facts  = _gdsGetDerivedFacts();
    const cmdHtml = storedCmds
        ? _gdsRenderStoredCommands(storedCmds)
        : _gdsCommandsHtml(stage, facts);

    // Panel কে flex container বানাও — সব tabs এ GDS panel ঠিকমতো দেখাবে
    panel.style.cssText = 'display:flex;flex-direction:column;padding:0;overflow:hidden;';

    panel.innerHTML = `
    <div style="display:flex;gap:0;align-items:stretch;flex:1;min-height:400px;overflow:hidden;">
        <div style="flex:1;min-width:0;overflow-y:auto;padding:20px;">${existingContent}</div>
        <div style="display:flex;align-items:stretch;">
            <div id="at-gds-divider-${stage}" style="width:4px;background:#f1f5f9;cursor:col-resize;flex-shrink:0;transition:background .15s;"
                onmouseover="this.style.background='#6366f1'"
                onmouseout="this.style.background='#f1f5f9'"></div>
            <div id="at-gds-panel-${stage}" style="width:${gdsW};flex-shrink:0;background:#12172B;display:flex;flex-direction:column;overflow:hidden;">
                <div style="background:#1C2340;padding:11px 14px;border-bottom:1px solid rgba(255,255,255,.08);display:flex;align-items:center;gap:8px;flex-shrink:0;">
                    <div style="flex:1;">
                        <div style="color:#fff;font-size:12px;font-weight:700;">GDS Commands</div>
                        <div style="color:#50BC81;font-size:10px;font-weight:700;letter-spacing:.1em;text-transform:uppercase;">Stage — ${{'1':'Research','2':'Booking','3':'Confirmation','4':'Documents'}[stage] ?? stage}</div>
                    </div>
                    <button id="at-gds-notes-btn" onclick="_gdsToggleNotes()" style="font-size:10px;font-weight:600;padding:4px 8px;border-radius:2px;border:1px solid rgba(255,255,255,.2);background:transparent;color:rgba(255,255,255,.7);cursor:pointer;">Notes on</button>
                    ${(stage === '3' || stage === '4') ? `<button id="at-gds-regen-btn-${stage}" onclick="_gdsRegenerate('${stage}')" style="font-size:10px;font-weight:600;padding:4px 8px;border-radius:2px;border:none;background:#50BC81;color:#12172B;cursor:pointer;">🔄 Regenerate</button>` : ''}
                </div>
                ${_gdsSubTabsHtml(stage)}
                <div id="at-gds-body-${stage}" style="overflow-y:auto;flex:1;">
                    <div style="padding:10px 10px 0;">${_gdsDerivedFactsHtml(facts)}</div>
                </div>
            </div>
        </div>
    </div>`;

    // commands আলাদাভাবে inject — template literal এ বসালে onclick ভাঙে
    const gdsBodyEl = panel.querySelector(`#at-gds-body-${stage}`);
    if (gdsBodyEl) gdsBodyEl.insertAdjacentHTML('beforeend', cmdHtml);

    setTimeout(() => {
        const divider  = document.getElementById(`at-gds-divider-${stage}`);
        const gdsPanel = document.getElementById(`at-gds-panel-${stage}`);
        _gdsInitDivider(divider, gdsPanel);
    }, 0);
}

// ── Render stored commands (from DB) ──────────────────────────
function _gdsRenderStoredCommands(cmds) {
    if (!cmds || !cmds.length) return '<div style="padding:16px;color:#4A5372;font-size:12px;">No commands generated yet.</div>';

    const tok = s => String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');

    window._gdsStoredCmds = cmds.filter(c => !c.divider && !c.note_only && c.cmd).map(c => c.cmd || '');

    let n = 0, cmdIdx = 0;
    const html = cmds.map(c => {
        if (c.divider)   return `<div style="padding:10px 14px 4px;color:#4A5372;font-size:10px;font-weight:700;letter-spacing:.13em;text-transform:uppercase;">${tok(c.label ?? '')}</div>`;
        if (c.note_only) return `<div style="margin:8px 10px;padding:8px 12px;background:rgba(251,191,36,.08);border:1px solid rgba(251,191,36,.25);border-radius:6px;color:#FBD56A;font-size:11px;line-height:1.5;">${tok(c.note ?? '')}</div>`;
        n++;
        const idx  = cmdIdx++;
        const cmd  = tok(c.cmd ?? '');
        const note = tok(c.note ?? '');
        return `<div class="gds-cmd-row" style="display:flex;align-items:baseline;padding:3px 14px;border-left:2px solid transparent;cursor:pointer;"
            onclick="_gdsCopySingle(${idx})"
            onmouseover="this.style.background='rgba(255,255,255,.045)';this.style.borderLeftColor='rgba(80,188,129,.5)'"
            onmouseout="this.style.background='';this.style.borderLeftColor='transparent'">
            <span style="color:#4A5372;width:24px;flex-shrink:0;font-size:10px;font-family:monospace;">${String(n).padStart(2,'0')}</span>
            <span style="color:#E8ECF5;white-space:pre;letter-spacing:.02em;font-family:monospace;font-size:13px;">${cmd}</span>
            <span class="gds-note" style="color:#5E6883;font-size:11px;margin-left:auto;padding-left:20px;white-space:nowrap;flex-shrink:0;">${note}</span>
        </div>`;
    }).join('');

    return `
    <div style="background:rgba(0,0,0,.15);padding:8px 14px;display:flex;align-items:center;gap:8px;">
        <span style="color:#8A93A8;font-size:10px;font-weight:700;letter-spacing:.1em;text-transform:uppercase;flex:1;">${n} commands</span>
        <button onclick="_gdsCopyAll()" style="font-size:11px;font-weight:600;padding:5px 10px;border-radius:2px;border:none;background:#50BC81;color:#12172B;cursor:pointer;">
            Copy all
        </button>
    </div>
    <div style="font-family:monospace;padding:4px 0 12px;">${html}</div>
    <div id="at-gds-toast" style="position:fixed;bottom:20px;left:50%;transform:translateX(-50%);background:#12172B;color:#fff;padding:9px 18px;border-radius:3px;font-size:12px;font-weight:600;border-left:3px solid #50BC81;opacity:0;transition:opacity .2s;pointer-events:none;z-index:9999;"></div>`;
}

// ── Live commands (no DB data yet) ────────────────────────────
function _gdsCommandsHtml(stage, facts) {
    const { adtCount, chdCount, infCount, seatCount, airlineCode, segs, routes, agent, pnr } = facts;
    const q = window._at.data?.at_quotations?.[0] ?? {};
    const totalFare = q.gross_fare ? Number(q.gross_fare).toLocaleString('en-BD') : '???';

    const STAGES = {
        '1': {
            title: 'Research',
            warning: { type:'note', text:'Fares are a snapshot — prices can move before booking.' },
            lines: [
                ['d', 'Schedules'],
                ...segs.map((s,i) => {
                    const from = s.from || s.origin || 'DAC';
                    const to   = s.to   || s.destination || '???';
                    const date = s.date || s.departure_date || '???';
                    const carr = s.airline || s.carrier || airlineCode;
                    return [`TT${date}${from}${to}/${carr}`, `Timetable ${from}-${to}`];
                }),
                ['d', 'Live seat availability'],
                ...segs.map((s,i) => {
                    const from = s.from || s.origin || 'DAC';
                    const to   = s.to   || s.destination || '???';
                    const date = s.date || s.departure_date || '???';
                    const carr = s.airline || s.carrier || airlineCode;
                    return [`A${date}${from}${to}*${carr}`, `Sector ${i+1}`];
                }),
                ['d', 'Priced shopping'],
                ...segs.map((s,i) => {
                    const from = s.from || s.origin || 'DAC';
                    const to   = s.to   || s.destination || '???';
                    const date = s.date || s.departure_date || '???';
                    const adultNums = Array.from({length: adtCount}, (_,i) => i+1).join('.');
                    const childNums = chdCount > 0 ? Array.from({length: chdCount}, (_,i) => adtCount+i+1).join('.') : '';
                    const paxStr = '+P' + adultNums + (chdCount > 0 ? '*CNN.' + childNums + '*CNN' : '');
                    return [`FS${seatCount}${from}${date}${to}${paxStr}`, `${from}-${to} ${seatCount} seats`];
                }),
                ['d', 'Fare conditions'],
                [`FQPQ${routes.map(r=>r.split('-')[0]).slice(0,1).join('')}${routes.map(r=>r.split('-')[1]).join('')}`, 'Through-fare on carrier'],
                ['FQL', 'Fare and tax'],
                ['FN*1/ALL', 'Baggage, changes, refunds'],
            ]
        },
        '2': {
            title: 'Quotation',
            warning: { type:'block', text:'Do not create a booking yet — wait for written client confirmation.' },
            nocmd: {
                h: 'Nothing to paste at this stage',
                p: 'The quotation is a client document. Below is what to include in the PDF.',
                items: [
                    ['ROUTE', routes.join(' · ')],
                    ['PASSENGERS', [adtCount&&`${adtCount} Adults`, chdCount&&`${chdCount} Child`, infCount&&`${infCount} Infant`].filter(Boolean).join(', ')],
                    ['FARE', `BDT ${totalFare} total, taxes included`],
                    ['VALIDITY', '48 hours from quotation date'],
                    ['MUST SAY', 'Fare subject to availability until ticket issued'],
                ]
            }
        },
        '3': {
            title: 'Booking',
            warning: { type:'note', text:`Compare the fare before saving — quoted BDT ${totalFare}.` },
            lines: [
                ['d', 'Sell the itinerary'],
                ...segs.map((s,i) => {
                    const from = s.from || s.origin || 'DAC';
                    const to   = s.to   || s.destination || '???';
                    const date = s.date || s.departure_date || '???';
                    const carr = s.airline || s.carrier || airlineCode;
                    return [`A${date}${from}${to}*${carr}`, `Sector ${i+1}`];
                }),
                [`N${seatCount}M1`, `Sell ${seatCount} seats`],
                ['d', 'Names (adults, children, infants)'],
                ...(adtCount > 0 ? [['N.SURNAME/FIRSTNAME MR', 'Adult — edit name']] : []),
                ...(chdCount > 0 ? [['N.SURNAME/FIRSTNAME MSTR*P-C0X', 'Child — edit name + age']] : []),
                ...(infCount > 0 ? [['N.I/SURNAME/FIRSTNAME MISS*DDMMMYY', 'Infant — DOB required']] : []),
                ['d', 'Agency, time limit, save'],
                ['P.T*TRAVHUB', 'Agency phone line'],
                ['T.T*', 'Ticketing time limit'],
                [`ER`, 'Saved — PNR created'],
                ['d', 'Price'],
                [`FQC${airlineCode}/ET`, 'Quote on carrier'],
                ['FQL', 'Fare — read back'],
                [`ER`, 'Fare saved'],
            ]
        },
        '4': {
            title: 'Documents',
            warning: { type:'block', text:'Every DOCS line uses YY so all carriers receive the data.' },
            lines: [
                ['d', 'Passports — one per passenger'],
                ...Array.from({length: adtCount}, (_,i) =>
                    [`SI.P${i+1}/SSRDOCSYYHK1/P/BD/PASSPORT/BD/DOB/M-F/EXPIRY/SURNAME/GIVEN`, `Adult ${i+1}`]),
                ...Array.from({length: chdCount}, (_,i) =>
                    [`SI.P${adtCount+i+1}/SSRDOCSYYHK1/P/BD/PASSPORT/BD/DOB/M-F/EXPIRY/SURNAME/GIVEN`, `Child ${i+1}`]),
                ...Array.from({length: infCount}, (_,i) =>
                    [`SI.P${adtCount+chdCount+i+1}/SSRDOCSYYHK1/P/BD/PASSPORT/BD/DOB/FI/EXPIRY/SURNAME/GIVEN`, `Infant ${i+1} — use FI`]),
                ['d', 'Emergency contact'],
                ['SI.SSRPCTCYYHK1/CTC NAME TEL NUMBER', 'No symbols in number'],
                ['d', 'Meals'],
                ...Array.from({length: adtCount}, (_,i) => [`SI.P${i+1}/MOML`, `Muslim meal — adult ${i+1}`]),
                ...Array.from({length: chdCount}, (_,i) => [`SI.P${adtCount+i+1}/CHML`, `Child meal`]),
                ...(infCount > 0 ? [['SI.P{mother}/BSCT*INFANT', 'Bassinet — against mother']] : []),
                ['d', 'Save and verify'],
                [`ER`, 'Saved'],
                ['*SI', 'All lines must read HK'],
            ]
        }
    };

    const st = STAGES[stage];
    if (!st) return '';

    const tok = s => s
        .replace(/<t>/g, '<span style="color:#50BC81;font-weight:700;background:rgba(80,188,129,.1);border-radius:2px;padding:0 2px;">')
        .replace(/<\/t>/g, '</span>')
        .replace(/<s>/g, '<span style="color:#7C86A3;">')
        .replace(/<\/s>/g, '</span>');

    const warnColor  = st.warning?.type === 'block' ? '#FDEDE8' : '#FCF4E4';
    const warnBorder = st.warning?.type === 'block' ? 'rgba(228,87,46,.35)' : 'rgba(232,163,61,.4)';
    const warnText   = st.warning?.type === 'block' ? '#8A2D12' : '#7A5410';
    const warnIcon   = st.warning?.type === 'block' ? '!!' : '→';
    const warningHtml = st.warning ? `
        <div style="display:flex;gap:10px;padding:10px 12px;background:${warnColor};border:1px solid ${warnBorder};color:${warnText};font-size:12px;border-radius:3px;margin-bottom:10px;">
            <span style="font-family:monospace;font-weight:700;flex-shrink:0;">${warnIcon}</span>
            <span>${st.warning.text}</span>
        </div>` : '';

    if (st.nocmd) {
        return warningHtml + `
        <div style="padding:4px 0;">
            <h3 style="color:#fff;font-size:14px;font-weight:700;margin-bottom:6px;">${st.nocmd.h}</h3>
            <p style="color:#8A93A8;font-size:12px;margin-bottom:14px;">${st.nocmd.p}</p>
            <div style="display:grid;gap:8px;">
                ${st.nocmd.items.map(it=>`
                <div style="display:flex;gap:10px;font-size:12px;color:#D5DAE6;padding:8px 10px;background:rgba(255,255,255,.04);border-left:2px solid rgba(80,188,129,.5);border-radius:2px;">
                    <span style="font-family:monospace;font-size:10px;color:#50BC81;flex-shrink:0;width:80px;letter-spacing:.06em;">${it[0]}</span>
                    <span>${it[1]}</span>
                </div>`).join('')}
            </div>
        </div>`;
    }

    let n = 0;
    const cmdHtml = st.lines.map(l => {
        if (l[0] === 'd') return `<div style="padding:10px 14px 4px;color:#4A5372;font-size:10px;font-weight:700;letter-spacing:.13em;text-transform:uppercase;">${l[1]}</div>`;
        n++;
        return `<div class="gds-cmd" style="display:flex;align-items:baseline;padding:3px 14px;border-left:2px solid transparent;cursor:pointer;"
            onclick="(function(el){var t=el.querySelector('.gds-text').textContent;if(navigator.clipboard)navigator.clipboard.writeText(t);var toast=document.getElementById('at-gds-toast');if(toast){toast.textContent=t+' copied';toast.style.opacity='1';setTimeout(()=>toast.style.opacity='0',1500);}})(this)"
            onmouseover="this.style.background='rgba(255,255,255,.045)';this.style.borderLeftColor='rgba(80,188,129,.5)'"
            onmouseout="this.style.background='';this.style.borderLeftColor='transparent'">
            <span style="color:#4A5372;width:24px;flex-shrink:0;font-size:10px;font-family:monospace;">${String(n).padStart(2,'0')}</span>
            <span class="gds-text" style="color:#E8ECF5;white-space:pre;letter-spacing:.02em;font-family:monospace;font-size:13px;">${tok(l[0])}</span>
            <span class="gds-note" style="color:#5E6883;font-size:11px;margin-left:auto;padding-left:20px;white-space:nowrap;flex-shrink:0;">${l[1]}</span>
        </div>`;
    }).join('');

    const allLines = st.lines.filter(l=>l[0]!=='d').map(l=>l[0].replace(/<[^>]+>/g,''));
    window._gdsStoredCmds = allLines;

    return warningHtml + `
    <div style="background:rgba(0,0,0,.15);padding:8px 14px 2px;display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
        <span style="color:#8A93A8;font-size:10px;font-weight:700;letter-spacing:.1em;text-transform:uppercase;flex:1;">${n} commands</span>
        <button onclick="_gdsCopyAll()" style="font-size:11px;font-weight:600;padding:5px 10px;border-radius:2px;border:1px solid rgba(255,255,255,.2);background:rgba(80,188,129,1);color:#12172B;cursor:pointer;">
            Copy all
        </button>
    </div>
    <div style="font-family:monospace;padding:6px 0 12px;overflow-x:auto;">${cmdHtml}</div>
    <div id="at-gds-toast" style="position:fixed;bottom:20px;left:50%;transform:translateX(-50%);background:#12172B;color:#fff;padding:9px 18px;border-radius:3px;font-size:12px;font-weight:600;border-left:3px solid #50BC81;opacity:0;transition:opacity .2s;pointer-events:none;z-index:9999;"></div>`;
}

// ── Derived facts (pax, carrier, route) ───────────────────────
function _gdsGetDerivedFacts() {
    const atData = window._at.data ?? {};
    const segs   = atData.segments_json ?? (atData.at_quotations?.[0]?.segments_json ?? []);
    const cfg    = window._at.cfg ?? {};

    // Primary source: config (from segment_data.common via show-works.php)
    let adtCount = +(cfg.paxAdult  ?? 0);
    let chdCount = +(cfg.paxChild  ?? 0);
    let infCount = +(cfg.paxInfant ?? 0);

    // Fallback: quotation pricing_json (only if config has no pax data)
    if (!adtCount && !chdCount) {
        const pricingJson = atData.at_quotations?.[0]?.pricing_json ?? [];
        pricingJson.forEach(p => {
            if (p.type === 'ADT') adtCount = p.pax ?? 1;
            if (p.type === 'CHD') chdCount = p.pax ?? 0;
            if (p.type === 'INF') infCount = p.pax ?? 0;
        });
    }

    const totalPax  = adtCount + chdCount + infCount;
    const seatCount = adtCount + chdCount;

    const firstSeg    = segs[0] ?? {};
    const airline     = atData.at_quotations?.[0]?.airline ?? firstSeg.airline ?? firstSeg.carrier ?? '??';
    const airlineCode = airline.length > 2 ? airline.substring(0,2).toUpperCase() : airline.toUpperCase();

    const routes  = segs.map(s => (s.from || s.origin || '') + '-' + (s.to || s.destination || '')).filter(Boolean);
    const agent   = (window.CURRENT_USER ?? 'AGENT').split(' ').map(w=>w[0]).join('').toUpperCase() || 'AGENT';
    const booking = atData.at_bookings?.[0] ?? {};
    const pnr     = booking.pnr ?? booking.locator ?? '';
    const dates   = segs.map(s => s.date || s.departure_date || '').filter(Boolean);

    return { adtCount, chdCount, infCount, totalPax, seatCount, airlineCode, airline, routes, segs, agent, pnr, dates };
}

// ── Derived facts HTML grid ───────────────────────────────────
function _gdsDerivedFactsHtml(facts) {
    const { adtCount, chdCount, infCount, totalPax, seatCount, airlineCode, routes, segs, agent, pnr } = facts;
    const paxBreak = [adtCount&&`${adtCount} Adult`, chdCount&&`${chdCount} Child`, infCount&&`${infCount} Infant`].filter(Boolean).join(' · ');
    const paxValue = paxBreak || totalPax;

    const factsData = [
        { label:'Passengers',    value: paxValue,    sub: `Total: ${totalPax}` },
        { label:'Seats to sell', value: seatCount,   sub: 'infant takes no seat', flag: true },
        { label:'Carrier',       value: airlineCode, sub: 'plating carrier', flag: true },
        { label:'Segments',      value: segs.length, sub: routes.join(' · ') },
        { label:'Agent',         value: agent,       sub: `saves as R.${agent}` },
        pnr ? { label:'PNR', value: pnr, sub: 'record locator' } : null,
    ].filter(Boolean);

    return `<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(110px,1fr));gap:1px;background:#E2E6EE;border:1px solid #E2E6EE;margin-bottom:12px;">
        ${factsData.map(f=>`
        <div style="background:#fff;padding:10px 12px;">
            <div style="font-size:10px;font-weight:600;letter-spacing:.09em;text-transform:uppercase;color:#8A93A8;margin-bottom:3px;">${f.label}</div>
            <div style="font-family:'JetBrains Mono',monospace;font-size:${String(f.value||'').length>8?'11px':'16px'};font-weight:700;color:${f.flag?'#2E8F5B':'#1A2039'};line-height:1.3;">${f.value||'—'}</div>
            ${f.sub?`<div style="font-size:10px;color:#8A93A8;margin-top:2px;">${f.sub}</div>`:''}
        </div>`).join('')}
    </div>`;
}

// ── Toggle notes visibility ───────────────────────────────────
function _gdsToggleNotes() {
    window._at.notesVisible = !window._at.notesVisible;
    const btn = document.getElementById('at-gds-notes-btn');
    if (btn) btn.textContent = window._at.notesVisible ? 'Notes on' : 'Notes off';
    document.querySelectorAll('.gds-note').forEach(el => {
        el.style.display = window._at.notesVisible ? '' : 'none';
    });
}

// ── Divider resize ────────────────────────────────────────────
function _gdsInitDivider(divider, gdsPanel) {
    if (!divider || !gdsPanel) return;
    let startX, startW;
    divider.addEventListener('mousedown', e => {
        startX = e.clientX; startW = gdsPanel.offsetWidth;
        document.body.style.userSelect = 'none';
        document.body.style.cursor = 'col-resize';
        function onMove(e) {
            const newW = Math.min(600, Math.max(180, startW - (e.clientX - startX)));
            gdsPanel.style.width = newW + 'px';
        }
        function onUp() {
            localStorage.setItem('at_gds_width', gdsPanel.offsetWidth);
            document.body.style.userSelect = '';
            document.body.style.cursor = '';
            document.removeEventListener('mousemove', onMove);
            document.removeEventListener('mouseup', onUp);
        }
        document.addEventListener('mousemove', onMove);
        document.addEventListener('mouseup', onUp);
        e.preventDefault();
    });
}

// ── Regenerate (Stage 3/4 — traveler data দিয়ে) ──────────────
async function _gdsRegenerate(stage) {
    const btn = document.getElementById(`at-gds-regen-btn-${stage}`);
    if (btn) { btn.textContent = '⏳ Generating...'; btn.disabled = true; }
    try {
        const cfg = window._at.cfg;
        const regenUrl = (cfg.api.airTickets ?? '').replace('api/air-tickets/endpoints.php', 'api/air-tickets/regenerate-gds.php');
        const res  = await fetch(regenUrl, {
            method: 'POST', headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ work_sys_id: cfg.workSysId, stages: [parseInt(stage)] }),
        });
        const json = await res.json();
        if (json.status !== 'success') throw new Error(json.message ?? 'Regenerate failed');

        if (!window._at.data) window._at.data = {};
        if (!window._at.data.commands) window._at.data.commands = {};
        const stageMap = { '3': 'booking', '4': 'confirmation' };
        const key = stageMap[stage];
        if (key && json.commands?.[key]) window._at.data.commands[key] = json.commands[key];

        const gdsPanelEl = document.getElementById(`at-gds-panel-${stage}`);
        if (gdsPanelEl) {
            const facts   = _gdsGetDerivedFacts();
            const newCmds = window._at.data.commands[key] ?? [];
            const innerScroll = gdsPanelEl.querySelector(`#at-gds-body-${stage}`);
            if (innerScroll) {
                innerScroll.innerHTML = `<div style="padding:10px 10px 0;">${_gdsDerivedFactsHtml(facts)}</div>`;
                innerScroll.insertAdjacentHTML('beforeend', _gdsRenderStoredCommands(newCmds));
            }
        }
        const count = json.traveler_count ?? 0;
        atT('success', `Stage ${stage} regenerated` + (count ? ` with ${count} traveler(s)` : ''));
    } catch(err) {
        console.error('[gdsRegenerate]', err);
        atT('error', err.message ?? 'Regenerate failed');
    } finally {
        if (btn) { btn.textContent = '🔄 Regenerate'; btn.disabled = false; }
    }
}