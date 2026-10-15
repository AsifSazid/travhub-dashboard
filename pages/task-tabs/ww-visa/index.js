/**
 * FILE PATH: /pages/task-tabs/ww-visa/index.js
 * Visa Service Module — 4-tab nav + Layer 2/3 shell
 *
 * Layer 1: [Mind Board][Visa Info][Traveler Docs][Application Status]
 * Layer 2: Traveler pills (inside Traveler Docs)
 * Layer 3: [Documents][Applicant Info][Cover Letter][Export][Status]
 */
(function () {

    /* ── Layer 1 switch ───────────────────────────────────── */
    window._vsSwitchTab = function (tab, btnEl) {
        window._vs.activeTab = tab;
        document.querySelectorAll('.vs-tab-btn').forEach(b => {
            const on = b === btnEl || b.dataset.tab === tab;
            b.style.borderBottomColor = on ? '#4F46E5' : 'transparent';
            b.style.color             = on ? '#4F46E5' : '#64748B';
            b.style.fontWeight        = on ? '700'     : '500';
        });
        ['mindboard','info','travelers','status'].forEach(t => {
            const p = document.getElementById('vs-tab-' + t);
            if (p) p.style.display = (t === tab) ? 'block' : 'none';
        });
        if (tab === 'mindboard') window._renderVsMindboard?.();
        if (tab === 'info')      window._renderVsInfo?.();
        if (tab === 'travelers') window._renderVsTravelers?.();
        if (tab === 'status')    window._renderVsStatus?.();
    };

    /* ── Layer 2 switch ───────────────────────────────────── */
    window._vsSwitchTraveler = function (travSysId, pillEl) {
        window._vs.activeTraveler = travSysId;
        document.querySelectorAll('.vs-trav-pill').forEach(p => {
            const on = p === pillEl || p.dataset.travId === travSysId;
            p.style.background  = on ? '#4F46E5' : '#F1F5F9';
            p.style.color       = on ? '#fff'    : '#475569';
            p.style.borderColor = on ? '#4F46E5' : '#E2E8F0';
            p.style.fontWeight  = on ? '700'     : '500';
        });
        _vsRenderSubNav(travSysId);
        window._vsSwitchTravSub(window._vs.activeTravSub, null, travSysId);
    };

    /* ── Layer 3 switch ───────────────────────────────────── */
    window._vsSwitchTravSub = function (sub, btnEl, travSysId) {
        travSysId = travSysId ?? window._vs.activeTraveler;
        if (!travSysId) return;
        window._vs.activeTravSub = sub;
        document.querySelectorAll('.vs-sub-btn').forEach(b => {
            const on = b === btnEl || b.dataset.sub === sub;
            b.style.background  = on ? '#EEF2FF'     : 'transparent';
            b.style.color       = on ? '#4F46E5'     : '#64748B';
            b.style.fontWeight  = on ? '700'         : '500';
            b.style.borderColor = on ? '#C7D2FE'     : 'transparent';
        });
        ['docs','appinfo','coverletter','export','travstatus'].forEach(s => {
            const p = document.getElementById('vs-sub-panel-' + s);
            if (p) p.style.display = (s === sub) ? 'block' : 'none';
        });
        if (sub === 'docs')        window._renderVsDocs?.(travSysId);
        if (sub === 'appinfo')     window._renderVsAppInfo?.(travSysId);
        if (sub === 'coverletter') window._renderVsCoverLetter?.(travSysId);
        if (sub === 'export')      window._renderVsExport?.(travSysId);
        if (sub === 'travstatus')  window._renderVsTravStatus?.(travSysId);
    };

    /* ── Traveler sub-nav ─────────────────────────────────── */
    function _vsRenderSubNav(travSysId) {
        const navEl   = document.getElementById('vs-trav-subnav');
        const panelEl = document.getElementById('vs-trav-panels');
        if (!navEl || !panelEl) return;
        navEl.style.display   = 'block';
        panelEl.style.display = 'block';

        const wTravs = window._vs.cfg?._workTravelersLive ?? [];
        const trav   = wTravs.find(t => t.sys_id === travSysId) ?? {};
        const cur    = window._vs.activeTravSub;

        const subs = [
            { key:'docs',        icon:'📄', label:'Documents' },
            { key:'appinfo',     icon:'📝', label:'Applicant Info' },
            { key:'coverletter', icon:'✉️',  label:'Cover Letter' },
            { key:'export',      icon:'📦', label:'Export' },
            { key:'travstatus',  icon:'📋', label:'Status' },
        ];

        navEl.innerHTML = `
        <div style="display:flex;align-items:center;gap:4px;flex-wrap:wrap;">
            ${subs.map(s => `
                <button class="vs-sub-btn" data-sub="${s.key}"
                        onclick="window._vsSwitchTravSub('${s.key}',this)"
                        style="padding:6px 13px;font-size:.8rem;
                               background:${cur===s.key?'#EEF2FF':'transparent'};
                               color:${cur===s.key?'#4F46E5':'#64748B'};
                               font-weight:${cur===s.key?'700':'500'};
                               border:1.5px solid ${cur===s.key?'#C7D2FE':'transparent'};
                               border-radius:7px;cursor:pointer;
                               display:flex;align-items:center;gap:5px;">
                    ${s.label}
                </button>`).join('')}
            <span style="margin-left:auto;font-size:.78rem;color:#94A3B8;">
                ${_vsEsc(trav.name ?? '')}
            </span>
        </div>`;
    }

    /* ── Travelers shell (Layer 2) ────────────────────────── */
    window._buildTravelersShell = function () {
        const el = document.getElementById('vs-tab-travelers');
        if (!el) return;

        const data    = window._vs.data;
        const vsMap   = {};
        (data?.vs_travelers ?? []).forEach(t => { vsMap[t.sys_id] = t; });
        const wTravs  = window._vs.cfg?._workTravelersLive ?? [];

        if (!data?.meta_data?.master_visa_sys_id) {
            el.innerHTML = `<div style="padding:32px;text-align:center;color:#64748B;font-size:.875rem;">
                ⚠️ Please select a visa service first (Visa Info tab).
            </div>`;
            return;
        }

        const readyCount = wTravs.filter(t => window._vsTravelerReady(vsMap[t.sys_id])).length;
        const totalCount = wTravs.length;

        el.innerHTML = `
        <!-- Layer 2: Traveler pills -->
        <div style="display:flex;align-items:center;gap:8px;padding:14px 0 10px;
                    flex-wrap:wrap;border-bottom:1px solid #F1F5F9;">
            ${wTravs.map(t => {
                const vsT  = vsMap[t.sys_id] ?? {};
                const ready = window._vsTravelerReady(vsT);
                const icon = (!ready && vsT.docs?.length && !window._vsTravelerReady(vsT)) ? '⚠️' : '';
                return `<div style="display:flex;align-items:center;border-radius:20px;
                                   border:1.5px solid #E2E8F0;background:#F1F5F9;overflow:hidden;">
                            <button class="vs-trav-pill" data-trav-id="${_vsEsc(t.sys_id)}"
                                onclick="window._vsSwitchTraveler('${_vsEsc(t.sys_id)}',this)"
                                style="padding:6px 12px;border:none;background:transparent;
                                       color:#475569;font-size:.82rem;font-weight:500;
                                       cursor:pointer;display:flex;align-items:center;gap:5px;">
                                ${icon ? `<span>${icon}</span>` : ''}
                                ${_vsEsc(t.name ?? t.sys_id)}
                            </button>
                            <button onclick="window._vsUnlinkTraveler('${_vsEsc(t.sys_id)}')"
                                title="Remove from visa"
                                style="padding:4px 8px;border:none;border-left:1px solid #E2E8F0;
                                       background:transparent;cursor:pointer;color:#94A3B8;font-size:.8rem;
                                       line-height:1;">✕</button>
                        </div>`;
            }).join('')}

            <button onclick="window._vsOpenAddTraveler()"
                    style="padding:6px 12px;border-radius:20px;border:1.5px dashed #C7D2FE;
                           background:#EEF2FF;color:#4F46E5;font-size:.82rem;font-weight:600;
                           cursor:pointer;">+ Add</button>

            ${wTravs.length ? `
                <button onclick="window._vsSyncDocs()"
                        style="margin-left:auto;padding:5px 12px;border-radius:7px;
                               border:1.5px solid #E2E8F0;background:#fff;font-size:.75rem;
                               font-weight:600;cursor:pointer;color:#64748B;">
                    🔄 Sync Docs
                </button>` : ''}
        </div>

        <!-- Layer 3 panels -->
        <div id="vs-trav-content">
            <div id="vs-trav-subnav"  style="display:none;border-bottom:1px solid #F1F5F9;padding:10px 0 0;"></div>
            <div id="vs-trav-panels" style="display:none;padding-top:14px;">
                <div id="vs-sub-panel-docs"        style="display:none;"></div>
                <div id="vs-sub-panel-appinfo"     style="display:none;"></div>
                <div id="vs-sub-panel-coverletter" style="display:none;"></div>
                <div id="vs-sub-panel-export"      style="display:none;"></div>
                <div id="vs-sub-panel-travstatus"  style="display:none;"></div>
            </div>
        </div>

        ${wTravs.length === 0 ? `
        <div style="padding:40px;text-align:center;color:#94A3B8;font-size:.875rem;">
            No travelers linked yet.<br>
            Add from the right sidebar or click <strong>+ Add</strong> above.
        </div>` : ''}`;

        // Auto-select first
        if (wTravs.length) {
            const first = el.querySelector('.vs-trav-pill');
            if (first) window._vsSwitchTraveler(wTravs[0].sys_id, first);
        }
    };

    /* ── Add traveler modal ───────────────────────────────── */
    window._vsOpenAddTraveler = function () {
        document.getElementById('vs-add-trav-modal')?.remove();
        const modal = document.createElement('div');
        modal.id = 'vs-add-trav-modal';
        modal.style.cssText = 'position:fixed;inset:0;background:rgba(0,0,0,.45);z-index:300;display:flex;align-items:center;justify-content:center;padding:20px;';
        modal.innerHTML = `
        <div style="background:#fff;border-radius:16px;width:100%;max-width:520px;max-height:85vh;
                    overflow:hidden;display:flex;flex-direction:column;box-shadow:0 20px 60px rgba(0,0,0,.2);">
            <div style="padding:16px 20px;border-bottom:1px solid #E2E8F0;display:flex;justify-content:space-between;align-items:center;">
                <div style="font-weight:700;font-size:.95rem;">Add Traveler to Visa</div>
                <button onclick="document.getElementById('vs-add-trav-modal').remove()"
                        style="background:none;border:none;cursor:pointer;font-size:1.1rem;color:#64748B;">✕</button>
            </div>
            <div style="padding:16px 20px;flex:1;overflow-y:auto;">
                <div style="display:flex;gap:8px;margin-bottom:14px;">
                    <input id="vs-trav-search-q" placeholder="Search by name or passport no…"
                           style="flex:1;padding:8px 12px;font-size:.875rem;border:1.5px solid #E2E8F0;border-radius:8px;outline:none;" />
                    <button onclick="window._vsSearchTravelers()"
                            style="padding:8px 14px;background:#4F46E5;color:#fff;font-size:.82rem;
                                   font-weight:700;border:none;border-radius:8px;cursor:pointer;">Search</button>
                </div>
                <div id="vs-trav-search-results"></div>
            </div>
        </div>`;
        document.body.appendChild(modal);
        modal.querySelector('#vs-trav-search-q')
             ?.addEventListener('keydown', e => { if (e.key==='Enter') window._vsSearchTravelers(); });
        modal.querySelector('#vs-trav-search-q')?.focus();
        window._vsSearchTravelers('');
    };

    window._vsSearchTravelers = async function (q) {
        const el = document.getElementById('vs-trav-search-results');
        if (!el) return;
        q = q ?? document.getElementById('vs-trav-search-q')?.value?.trim() ?? '';
        el.innerHTML = '<div style="padding:12px;text-align:center;color:#64748B;font-size:.85rem;">Searching…</div>';

        try {
            // Use allTravelers to search across ALL travelers in the system
            const j    = await fetch(window._vs.cfg.api.allTravelers).then(r => r.json());
            const all  = j.travelers ?? j.data ?? [];
            const rows = q
                ? all.filter(t => (t.name??'').toLowerCase().includes(q.toLowerCase())
                               || (t.passport_no??'').toLowerCase().includes(q.toLowerCase()))
                : all.slice(0, 20);

            if (!rows.length) {
                el.innerHTML = '<div style="padding:20px;text-align:center;color:#94A3B8;font-size:.85rem;">No travelers found</div>';
                return;
            }

            const added = new Set((window._vs.data?.vs_travelers ?? []).map(t => t.sys_id));
            el.innerHTML = rows.map(t => {
                const isAdded = added.has(t.sys_id);
                return `<div style="display:flex;align-items:center;justify-content:space-between;
                                    padding:10px 12px;border:1px solid #E2E8F0;border-radius:9px;margin-bottom:7px;">
                    <div>
                        <div style="font-size:.88rem;font-weight:600;color:#0F172A;">${_vsEsc(t.name??'')}</div>
                        <div style="font-size:.75rem;color:#94A3B8;">${_vsEsc(t.passport_no??'—')}</div>
                    </div>
                    ${isAdded
                        ? `<span style="font-size:.75rem;color:#15803D;font-weight:600;">✓ Added</span>`
                        : `<button onclick="window._vsAddTraveler('${_vsEsc(t.sys_id)}','${_vsEsc((t.name??'').replace(/'/g,"\\'"))}','${_vsEsc(t.passport_no??'')}')"
                                   style="padding:5px 12px;background:#4F46E5;color:#fff;font-size:.78rem;
                                          font-weight:600;border:none;border-radius:7px;cursor:pointer;">+ Add</button>`
                    }
                </div>`;
            }).join('');
        } catch (e) {
            el.innerHTML = `<div style="padding:12px;text-align:center;color:#EF4444;font-size:.85rem;">Error: ${e.message}</div>`;
        }
    };

    window._vsAddTraveler = async function (travSysId, name, passportNo) {
        // Step 1: Link traveler to work (works.traveler_sys_ids)
        try {
            const linkRes = await fetch(window._vs.cfg.api.workTravelers, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    action:          'link',
                    work_sys_id:     window._vs.cfg.workSysId,
                    traveler_sys_id: travSysId,
                }),
            }).then(r => r.json());
            if (linkRes.status !== 'success') {
                alert('Failed to link traveler: ' + (linkRes.message ?? 'Unknown error'));
                return;
            }
        } catch (e) {
            alert('Network error linking traveler: ' + e.message);
            return;
        }

        // Step 2: Add to visa_services.vs_travelers JSON
        const travelers = JSON.parse(JSON.stringify(window._vs.data?.vs_travelers ?? []));
        if (!travelers.find(t => t.sys_id === travSysId)) {
            travelers.push({
                sys_id: travSysId, name, passport_no: passportNo,
                profession_type: 'general', docs: [],
                cover_letter: null, app_status: 'pending',
            });
            const j = await window._vsApi({ action: 'update_travelers', vs_travelers: travelers });
            if (j.status !== 'success') { alert(j.message); return; }
            window._vs.data = j.data;
        }

        // Step 3: Refresh work travelers cache + rebuild UI
        window._vs.cfg._workTravelersLive = null;

        // Fetch fresh — inline so no dependency on travelers.js load order
        try {
            const fresh = await fetch(
                `${window._vs.cfg.api.workTravelers}?action=list&work_sys_id=${encodeURIComponent(window._vs.cfg.workSysId)}`
            ).then(r => r.json());
            window._vs.cfg._workTravelersLive = fresh.data ?? [];
        } catch (e) {
            window._vs.cfg._workTravelersLive = [];
        }

        document.getElementById('vs-add-trav-modal')?.remove();
        window._buildTravelersShell();
        window._vsUpdateTravelerBadge?.();
    };

    /* ── Badge update ─────────────────────────────────────── */
    window._vsUpdateTravelerBadge = function () {
        const wTravs     = window._vs.cfg?._workTravelersLive ?? [];
        const vsMap      = {};
        (window._vs.data?.vs_travelers ?? []).forEach(t => { vsMap[t.sys_id] = t; });
        const readyCount = wTravs.filter(t => window._vsTravelerReady(vsMap[t.sys_id])).length;
        const totalCount = wTravs.length;
        const badge      = document.querySelector('.vs-tab-btn[data-tab="travelers"] .vs-trav-badge');
        if (badge && totalCount > 0) {
            badge.textContent = `${readyCount}/${totalCount}`;
            badge.style.background = readyCount===totalCount ? '#DCFCE7' : '#EEF2FF';
            badge.style.color      = readyCount===totalCount ? '#15803D' : '#4F46E5';
        }
    };

    window._vsTravelerReady = function (vsEntry) {
        if (!vsEntry?.docs?.length) return false;
        return vsEntry.docs.every(d => d.status === 'uploaded');
    };

    /* ── Shell builder ────────────────────────────────────── */
    function _buildShell(mount, data) {
        const isSubmitted = ['submitted','completed'].includes(data?.status);
        mount.innerHTML = `
        <style>
            @keyframes vs-spin { to { transform:rotate(360deg); } }
            .vs-tab-btn,.vs-trav-pill,.vs-sub-btn { transition:all .15s; }
            .vs-sub-btn { border-radius:7px; }
        </style>
        <!-- Layer 1 tab bar -->
        <div style="display:flex;align-items:center;border-bottom:2px solid #E2E8F0;padding:0 4px;">
            ${[
                {tab:'mindboard', icon:'', label:'Mind Board'},
                {tab:'info',      icon:'', label:'Visa Info'},
                {tab:'travelers', icon:'', label:'Traveler Docs', badge:true},
                {tab:'status',    icon:'', label:'Application Status'},
            ].map(({tab,icon,label,badge}) => `
                <button class="vs-tab-btn" data-tab="${tab}"
                        onclick="window._vsSwitchTab('${tab}',this)"
                        style="padding:11px 16px;font-size:.875rem;background:none;border:none;
                               border-bottom:2px solid transparent;margin-bottom:-2px;cursor:pointer;
                               color:#64748B;font-weight:500;display:flex;align-items:center;gap:6px;">
                    ${label}
                    ${badge ? `<span class="vs-trav-badge" style="padding:2px 7px;border-radius:999px;
                                font-size:.7rem;font-weight:700;background:#EEF2FF;color:#4F46E5;
                                display:none;"></span>` : ''}
                </button>`).join('')}

            <div id="vs-submitted-badge"
                 style="margin-left:auto;display:${isSubmitted?'flex':'none'};align-items:center;
                        gap:6px;padding:4px 10px;background:#DCFCE7;border-radius:999px;
                        font-size:.72rem;font-weight:700;color:#15803D;">
                ✓ Submitted
            </div>
        </div>
        <!-- Tab panels -->
        <div style="padding:0 4px;">
            <div id="vs-tab-mindboard" style="display:none;"></div>
            <div id="vs-tab-info"      style="display:none;"></div>
            <div id="vs-tab-travelers" style="display:none;"></div>
            <div id="vs-tab-status"    style="display:none;"></div>
        </div>`;
    }

    /* ── Init ─────────────────────────────────────────────── */
    window.initWorkVisaTab = async function (config) {
        window._vs.cfg = config;
        window._vs.cfg._workTravelersLive = null; // will be loaded fresh

        const mount = document.getElementById(config.containerId ?? 'ww-visa-mount');
        if (!mount) return;

        mount.innerHTML = `
        <div style="padding:20px;text-align:center;color:#94A3B8;font-size:.875rem;">
            <div style="display:inline-block;width:28px;height:28px;border:3px solid #E2E8F0;
                        border-top-color:#6366f1;border-radius:50%;animation:vs-spin .7s linear infinite;
                        margin-bottom:10px;"></div>
            <div>Loading visa service…</div>
        </div>
        <style>@keyframes vs-spin{to{transform:rotate(360deg);}}</style>`;

        // Load visa_services row
        let data = null;
        try {
            const j = await fetch(
                `${config.api.visaServices}?action=get&work_sys_id=${encodeURIComponent(config.workSysId)}`
            ).then(r => r.json());
            if (j.status === 'success' && j.data) {
                data = j.data;
            } else {
                const ji = await fetch(config.api.visaServices, {
                    method:'POST', headers:{'Content-Type':'application/json'},
                    body: JSON.stringify({ action:'init', work_sys_id: config.workSysId }),
                }).then(r => r.json());
                if (ji.status === 'success') data = ji.data;
            }
        } catch (e) {
            mount.innerHTML = `<div style="padding:32px;text-align:center;color:#EF4444;font-size:.875rem;">
                ⚠️ Failed to load visa service. Please refresh.</div>`;
            return;
        }
        window._vs.data = data;

        // Load work travelers fresh
        try {
            const j = await fetch(
                `${config.api.workTravelers}?action=list&work_sys_id=${encodeURIComponent(config.workSysId)}`
            ).then(r => r.json());
            window._vs.cfg._workTravelersLive = j.data ?? [];
        } catch (e) {
            window._vs.cfg._workTravelersLive = [];
        }

        _buildShell(mount, data);
        window._vsSwitchTab('mindboard',
            mount.querySelector('.vs-tab-btn[data-tab="mindboard"]'));
    };

})();