/**
 * FILE PATH: /pages/task-tabs/ww-umrah/index.js
 */
window.initWorkUmrahTab = async function(config) {
    window._um.cfg = config;

    const mount = document.getElementById('ww-umrah-mount');
    if (!mount) { console.error('[umrah] #ww-umrah-mount not found'); return; }

    mount.innerHTML = `
    <div id="um-shell" style="display:flex;flex-direction:column;height:100%;min-height:0;">
        <div style="flex-shrink:0;display:flex;gap:0;border-bottom:1px solid #f1f5f9;background:#fff;padding:0 16px;">
            ${[
                { key:'travelers',  icon:'fa-users',         label:'Travelers & Group' },
                { key:'documents',  icon:'fa-file-shield',   label:'Documents'         },
                { key:'summary',    icon:'fa-list-check',    label:'Trip Summary'      },
                { key:'idcard',     icon:'fa-id-card',       label:'ID Cards'          },
            ].map(t => `
                <button class="um-tab" data-tab="${t.key}"
                    onclick="window._umSwitchTab('${t.key}',this)"
                    style="padding:10px 14px;font-size:12px;font-weight:600;color:#9ca3af;background:none;border:none;border-bottom:2px solid transparent;cursor:pointer;display:flex;align-items:center;gap:6px;transition:all .15s;white-space:nowrap;">
                    <i class="fas ${t.icon}" style="font-size:11px;"></i>${t.label}
                </button>`).join('')}
        </div>
        <div id="um-panels" style="flex:1;min-height:0;overflow-y:auto;padding:16px;">
            <div id="um-panel-travelers"  class="um-panel"><div class="text-center py-8 text-gray-300"><i class="fas fa-spinner fa-spin"></i></div></div>
            <div id="um-panel-documents"  class="um-panel hidden"></div>
            <div id="um-panel-summary"    class="um-panel hidden"></div>
            <div id="um-panel-idcard"     class="um-panel hidden"></div>
        </div>
    </div>`;

    // Load data
    await window._umReload();
    window._umSwitchTab('travelers', mount.querySelector('.um-tab[data-tab="travelers"]'));
};

window._umSwitchTab = function(tab, btn) {
    window._um.activeTab = tab;
    document.querySelectorAll('.um-tab').forEach(b => {
        const active = b.dataset.tab === tab;
        b.style.color       = active ? '#7c3aed' : '#9ca3af';
        b.style.borderColor = active ? '#7c3aed' : 'transparent';
    });
    document.querySelectorAll('.um-panel').forEach(p => {
        p.classList.toggle('hidden', !p.id.endsWith(tab));
    });
    const renders = {
        travelers: window._renderUmTravelers,
        documents: window._renderUmDocuments,
        summary:   window._renderUmSummary,
        idcard:    window._renderUmIdCard,
    };
    if (renders[tab]) renders[tab]();
};