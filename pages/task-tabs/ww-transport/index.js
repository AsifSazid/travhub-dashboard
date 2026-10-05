/**
 * FILE PATH: /pages/task-tabs/ww-transport/index.js
 */
window.initWorkTransportTab = async function(config) {
    window._ts.cfg  = config;
    window._ts.data = config.tsData ?? null;

    const mount = document.getElementById('ww-transport-mount');
    if (!mount) { console.error('[transport] #ww-transport-mount not found'); return; }

    mount.innerHTML = `
    <div id="ts-shell" style="display:flex;flex-direction:column;height:100%;min-height:0;">
        <div style="flex-shrink:0;display:flex;gap:0;border-bottom:1px solid #f1f5f9;background:#fff;padding:0 16px;">
            ${[
                { key:'mindboard',    icon:'fa-sticky-note',   label:'Mind Board'   },
                { key:'quotation',    icon:'fa-file-invoice',  label:'Quotation'    },
                { key:'confirmation', icon:'fa-check-circle',  label:'Confirmation' },
            ].map(t => `
                <button class="ts-tab" data-tab="${t.key}"
                    onclick="window._tsSwitchTab('${t.key}',this)"
                    style="padding:10px 14px;font-size:12px;font-weight:600;color:#9ca3af;background:none;border:none;border-bottom:2px solid transparent;cursor:pointer;display:flex;align-items:center;gap:6px;transition:all .15s;white-space:nowrap;">
                    <i class="fas ${t.icon}" style="font-size:11px;"></i>${t.label}
                </button>`).join('')}
        </div>
        <div id="ts-panels" style="flex:1;min-height:0;overflow-y:auto;padding:16px;">
            <div id="ts-panel-mindboard"    class="ts-panel"></div>
            <div id="ts-panel-quotation"    class="ts-panel hidden"></div>
            <div id="ts-panel-confirmation" class="ts-panel hidden"></div>
        </div>
    </div>`;

    if (!window._ts.data) {
        try {
            const res  = await fetch(`${config.api.transportServices}?action=get&work_sys_id=${encodeURIComponent(config.workSysId)}`);
            const json = await res.json();
            if (json.status === 'success') {
                window._ts.data = json.data;
            } else {
                await window._tsApi({ action:'init' });
                const r2 = await fetch(`${config.api.transportServices}?action=get&work_sys_id=${encodeURIComponent(config.workSysId)}`);
                window._ts.data = (await r2.json()).data ?? null;
            }
        } catch(e) { console.error('[transport] init:', e); }
    }

    window._tsSwitchTab('mindboard', mount.querySelector('.ts-tab[data-tab="mindboard"]'));
};

window._tsSwitchTab = function(tab, btn) {
    window._ts.activeTab = tab;
    document.querySelectorAll('.ts-tab').forEach(b => {
        const active = b.dataset.tab === tab;
        b.style.color       = active ? '#0284c7' : '#9ca3af';
        b.style.borderColor = active ? '#0284c7' : 'transparent';
    });
    document.querySelectorAll('.ts-panel').forEach(p => {
        p.classList.toggle('hidden', !p.id.endsWith(tab));
    });
    const renders = {
        mindboard:    window._renderTsMindboard,
        quotation:    window._renderTsQuotation,
        confirmation: window._renderTsConfirmation,
    };
    if (renders[tab]) renders[tab]();
};