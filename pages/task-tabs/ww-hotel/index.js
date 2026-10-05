/**
 * FILE PATH: /pages/task-tabs/ww-hotel/index.js
 * Entry point — called from show-works.php for hotel slug
 */

window.initWorkHotelTab = async function(config) {
    window._ht.cfg    = config;
    window._ht.data   = config.htData ?? null;

    const mount = document.getElementById('ww-hotel-mount');
    if (!mount) { console.error('[hotel] mount #ww-hotel-mount not found'); return; }

    mount.innerHTML = `
    <div id="ht-shell" style="display:flex;flex-direction:column;height:100%;min-height:0;">
        <!-- Tab bar -->
        <div style="flex-shrink:0;display:flex;gap:0;border-bottom:1px solid #f1f5f9;background:#fff;padding:0 16px;">
            ${[
                { key:'mindboard',    icon:'fa-sticky-note',   label:'Mind Board'    },
                { key:'quotation',    icon:'fa-file-invoice',  label:'Quotation'     },
                { key:'booking',      icon:'fa-bookmark',      label:'Booking'       },
                { key:'confirmation', icon:'fa-check-circle',  label:'Confirmation'  },
            ].map(t => `
                <button class="ht-tab" data-tab="${t.key}"
                    onclick="window._htSwitchTab('${t.key}',this)"
                    style="padding:10px 14px;font-size:12px;font-weight:600;color:#9ca3af;background:none;border:none;border-bottom:2px solid transparent;cursor:pointer;display:flex;align-items:center;gap:6px;transition:all .15s;white-space:nowrap;">
                    <i class="fas ${t.icon}" style="font-size:11px;"></i>${t.label}
                </button>`).join('')}
        </div>
        <!-- Panels -->
        <div id="ht-panels" style="flex:1;min-height:0;overflow-y:auto;padding:16px;">
            <div id="ht-panel-mindboard"    class="ht-panel"></div>
            <div id="ht-panel-quotation"    class="ht-panel hidden"></div>
            <div id="ht-panel-booking"      class="ht-panel hidden"></div>
            <div id="ht-panel-confirmation" class="ht-panel hidden"></div>
        </div>
    </div>`;

    // Init data
    if (!window._ht.data) {
        try {
            const res  = await fetch(`${config.api.hotelServices}?action=get&work_sys_id=${encodeURIComponent(config.workSysId)}`);
            const json = await res.json();
            if (json.status === 'success') {
                window._ht.data = json.data;
            } else {
                // init
                await window._htApi({ action: 'init', work_sys_id: config.workSysId });
                const r2   = await fetch(`${config.api.hotelServices}?action=get&work_sys_id=${encodeURIComponent(config.workSysId)}`);
                const j2   = await r2.json();
                window._ht.data = j2.data ?? null;
            }
        } catch(e) { console.error('[hotel] init error:', e); }
    }

    window._htSwitchTab('mindboard', mount.querySelector('.ht-tab[data-tab="mindboard"]'));
};

window._htSwitchTab = function(tab, btn) {
    window._ht.activeTab = tab;
    document.querySelectorAll('.ht-tab').forEach(b => {
        const active = b.dataset.tab === tab;
        b.style.color       = active ? '#6366f1' : '#9ca3af';
        b.style.borderColor = active ? '#6366f1' : 'transparent';
    });
    document.querySelectorAll('.ht-panel').forEach(p => {
        p.classList.toggle('hidden', !p.id.endsWith(tab));
    });
    // Render tab
    const renders = {
        mindboard:    window._renderHtMindboard,
        quotation:    window._renderHtQuotation,
        booking:      window._renderHtBooking,
        confirmation: window._renderHtConfirmation,
    };
    if (renders[tab]) renders[tab]();
};