/**
 * FILE PATH: /pages/task-tabs/ww-visa/visa-info.js
 * Visa Service Module — "Visa Info" tab
 *
 * Shows: selected master visa info, option to change it.
 * If no master selected: shows search UI to pick one.
 */

window._renderVsInfo = function() {
    const el = document.getElementById('vs-tab-info');
    if (!el) return;
    const data = window._vs.data;
    const meta = data?.meta_data;

    if (!meta?.master_visa_sys_id) {
        // No master selected — show picker
        el.innerHTML = _vsInfoPickerHtml();
        _vsInfoPickerInit();
        return;
    }

    // Show selected master info
    el.innerHTML = `
    <div style="padding:16px 0;">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;">
            <div>
                <div style="font-size:1rem;font-weight:700;color:#0F172A;">${_vsEsc(meta.master_visa_title)}</div>
                <div style="font-size:.8rem;color:#64748B;margin-top:3px;">
                    🌍 ${_vsEsc(meta.country_name)} &nbsp;·&nbsp;
                    📋 ${_vsEsc(meta.visa_category_name ?? '—')} &nbsp;·&nbsp;
                    🔑 ${_vsEsc(meta.visa_type_name ?? '—')} &nbsp;·&nbsp;
                    ⏱ ${_vsEsc(meta.duration_label || (meta.duration_days + ' days'))}
                </div>
            </div>
            <div style="display:flex;gap:8px;align-items:center;">
                ${_vsStatusChip(data.status)}
                <button onclick="_vsInfoChangeMaster()"
                        style="padding:6px 12px;border-radius:7px;border:1.5px solid #E2E8F0;
                               background:#fff;font-size:.78rem;font-weight:600;cursor:pointer;
                               color:#64748B;">
                    ↕ Change
                </button>
            </div>
        </div>

        <!-- Pricing -->
        <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:10px;margin-bottom:16px;">
            ${_vsInfoChip('Client Price (B2C)', _vsFmt(meta.b2c_price, meta.currency), '#EEF2FF', '#4F46E5')}
            ${_vsInfoChip('Purchase Price', _vsFmt(meta.purchase_price, meta.currency), '#FEF3C7', '#92400E')}
            ${_vsInfoChip('Max Doc Size', (meta.file_size_limit_kb ?? 300) + ' KB', '#F0FDF4', '#15803D')}
        </div>

        <!-- Required Documents preview per profession -->
        <div style="margin-bottom:16px;">
            <div style="font-size:.8rem;font-weight:700;color:#475569;margin-bottom:10px;text-transform:uppercase;letter-spacing:.04em;">
                Required Documents
            </div>
            ${_vsInfoDocsHtml(meta.required_documents)}
        </div>

        <!-- Description sections -->
        ${_vsInfoDescHtml(meta)}
    </div>`;
};

function _vsInfoChip(label, val, bg, color) {
    return `<div style="background:${bg};border-radius:10px;padding:12px 14px;">
        <div style="font-size:.7rem;font-weight:600;color:${color};opacity:.7;margin-bottom:3px;text-transform:uppercase;">${label}</div>
        <div style="font-size:.95rem;font-weight:700;color:${color};">${val}</div>
    </div>`;
}

function _vsInfoDocsHtml(rd) {
    if (!rd || typeof rd !== 'object') return '<div style="color:#64748B;font-size:.85rem;">No document list configured.</div>';
    const profs = ['general','student','employment','businessman','non_employment'];
    const labels = { general:'General', student:'Student', employment:'Employment',
                     businessman:'Businessman', non_employment:'Non-Employment' };
    let html = '<div style="display:flex;gap:6px;flex-wrap:wrap;margin-bottom:8px;">';
    const tabs = profs.filter(p => Array.isArray(rd[p]) && rd[p].length);
    if (!tabs.length) return '<div style="color:#64748B;font-size:.85rem;">No documents defined.</div>';

    tabs.forEach((p, i) => {
        html += `<button onclick="_vsInfoDocTab('${p}')"
                         id="vs-info-dtab-${p}"
                         class="vs-info-dtab"
                         style="padding:4px 12px;border-radius:6px;border:1px solid ${i===0?'#6366f1':'#E2E8F0'};
                                background:${i===0?'#EEF2FF':'#fff'};font-size:.78rem;font-weight:600;cursor:pointer;
                                color:${i===0?'#4F46E5':'#64748B'};">
                    ${labels[p]}
                 </button>`;
    });
    html += '</div>';

    tabs.forEach((p, i) => {
        const docs = rd[p] ?? [];
        html += `<div id="vs-info-dpanel-${p}" style="display:${i===0?'block':'none'};">
            <ol style="margin:0;padding-left:22px;color:#334155;font-size:.875rem;line-height:1.8;">
                ${docs.map(d => `<li>${_vsEsc(d.doc)}${d.note ? `<span style="color:#94A3B8;font-size:.78rem;"> — ${_vsEsc(d.note)}</span>` : ''}</li>`).join('')}
            </ol>
        </div>`;
    });

    return html;
}

window._vsInfoDocTab = function(prof) {
    document.querySelectorAll('.vs-info-dtab').forEach(b => {
        const active = b.id === 'vs-info-dtab-' + prof;
        b.style.background   = active ? '#EEF2FF' : '#fff';
        b.style.color        = active ? '#4F46E5' : '#64748B';
        b.style.borderColor  = active ? '#6366f1' : '#E2E8F0';
    });
    document.querySelectorAll('[id^="vs-info-dpanel-"]').forEach(p => {
        p.style.display = p.id === 'vs-info-dpanel-' + prof ? 'block' : 'none';
    });
};

function _vsInfoDescHtml(meta) {
    // Description pulled from master on set_master — load it live for freshness
    // We stored required_documents in meta but not description — need to load description from master
    // Trigger async load
    setTimeout(() => _vsInfoLoadDesc(meta.master_visa_sys_id), 0);
    return `<div id="vs-info-desc-area"></div>`;
}

async function _vsInfoLoadDesc(masterSysId) {
    if (!masterSysId) return;
    const el = document.getElementById('vs-info-desc-area');
    if (!el) return;
    try {
        const res = await fetch(
            `${window._vs.cfg.api.visaServices}?action=get_master&sys_id=${encodeURIComponent(masterSysId)}`
        );
        const j = await res.json();
        const desc = j.data?.description ?? [];
        if (!desc.length) { el.innerHTML = ''; return; }
        el.innerHTML = `
        <div style="margin-top:8px;">
            <div style="font-size:.8rem;font-weight:700;color:#475569;margin-bottom:10px;text-transform:uppercase;letter-spacing:.04em;">
                Notes & Info
            </div>
            ${desc.map(s => `
                <div style="margin-bottom:10px;padding:12px 14px;background:#FAFBFF;border-left:3px solid #6366f1;border-radius:0 8px 8px 0;">
                    ${s.title ? `<div style="font-size:.83rem;font-weight:700;color:#4F46E5;margin-bottom:4px;">${_vsEsc(s.title)}</div>` : ''}
                    <div style="font-size:.875rem;color:#334155;line-height:1.7;">${s.body ?? ''}</div>
                </div>
            `).join('')}
        </div>`;
    } catch(e) { /* silent */ }
}

// ── Master picker ─────────────────────────────────────────
function _vsInfoPickerHtml() {
    return `
    <div style="padding:24px 0;">
        <div style="font-size:.9rem;font-weight:700;color:#0F172A;margin-bottom:4px;">Select Visa Service</div>
        <div style="font-size:.8rem;color:#64748B;margin-bottom:16px;">Choose the visa product from master data</div>

        <div style="display:flex;gap:10px;flex-wrap:wrap;margin-bottom:14px;">
            <input id="vs-search-q" placeholder="Search by country / title…"
                   style="flex:1;min-width:180px;padding:8px 12px;font-size:.875rem;
                          border:1.5px solid #E2E8F0;border-radius:8px;outline:none;" />
            <button onclick="_vsPickerSearch()"
                    style="padding:8px 18px;background:#4F46E5;color:#fff;font-weight:700;
                           font-size:.875rem;border:none;border-radius:8px;cursor:pointer;">
                Search
            </button>
        </div>
        <div id="vs-picker-results"></div>
    </div>`;
}

function _vsInfoPickerInit() {
    // Auto-search by client country if available
    const country = window._vs.cfg?.clientCountrySysId;
    _vsPickerSearch(country);

    const inp = document.getElementById('vs-search-q');
    if (inp) inp.addEventListener('keydown', e => { if (e.key === 'Enter') _vsPickerSearch(); });
}

window._vsPickerSearch = async function(preCountry) {
    const el = document.getElementById('vs-picker-results');
    if (!el) return;
    el.innerHTML = '<div style="padding:16px;text-align:center;color:#64748B;font-size:.85rem;">Searching…</div>';

    const q = document.getElementById('vs-search-q')?.value?.trim() ?? '';
    let url = `${window._vs.cfg.api.visaServices}?action=search_master`;
    if (preCountry) url += `&country_sys_id=${encodeURIComponent(preCountry)}`;
    if (q) url += `&q=${encodeURIComponent(q)}`;

    const res = await fetch(url);
    const j   = await res.json();
    const rows = j.data ?? [];

    if (!rows.length) {
        el.innerHTML = '<div style="padding:24px;text-align:center;color:#64748B;font-size:.85rem;">No services found</div>';
        return;
    }

    el.innerHTML = rows.map(r => `
        <div onclick="_vsSelectMaster('${_vsEsc(r.sys_id)}')"
             style="display:flex;justify-content:space-between;align-items:center;
                    padding:12px 14px;border:1.5px solid #E2E8F0;border-radius:10px;
                    margin-bottom:8px;cursor:pointer;background:#fff;transition:border-color .15s;"
             onmouseover="this.style.borderColor='#6366f1'"
             onmouseout="this.style.borderColor='#E2E8F0'">
            <div>
                <div style="font-size:.9rem;font-weight:700;color:#0F172A;">${_vsEsc(r.title)}</div>
                <div style="font-size:.75rem;color:#64748B;margin-top:2px;">
                    🌍 ${_vsEsc(r.country_name)} · ${_vsEsc(r.visa_category_name ?? '')} · ${_vsEsc(r.visa_type_name ?? '')}
                    · ${_vsEsc(r.duration_label || r.duration_days + ' days')}
                </div>
            </div>
            <div style="text-align:right;white-space:nowrap;">
                <div style="font-size:.9rem;font-weight:700;color:#4F46E5;">
                    ${Number(r.b2c_price).toLocaleString()} ${r.currency}
                </div>
                <div style="font-size:.72rem;color:#94A3B8;">Cost: ${Number(r.purchase_price).toLocaleString()}</div>
            </div>
        </div>
    `).join('');
};

window._vsSelectMaster = async function(masterSysId) {
    const btn = event?.target?.closest('div[onclick]');
    if (btn) btn.style.opacity = '.5';
    const j = await window._vsApi({ action: 'set_master', master_visa_sys_id: masterSysId });
    if (j.status !== 'success') { alert(j.message); return; }
    window._vs.data = j.data;
    window._renderVsInfo();
    // If travelers tab is active, re-render it
    if (window._vs.activeTab === 'travelers') window._renderVsTravelers?.();
};

window._vsInfoChangeMaster = function() {
    const el = document.getElementById('vs-tab-info');
    if (!el) return;
    el.innerHTML = _vsInfoPickerHtml();
    _vsInfoPickerInit();
};