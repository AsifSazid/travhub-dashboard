/**
 * FILE PATH: /pages/task-tabs/ww-visa/index.js
 * Visa Service Module — main entry point (IIFE)
 *
 * Exposes:
 *   window.initWorkVisaTab(config)
 *   window._vsSwitchTab(tab, btnEl)
 */

(function() {

    // ── Tab switch ────────────────────────────────────────
    window._vsSwitchTab = function(tab, btnEl) {
        window._vs.activeTab = tab;

        // Button styles
        document.querySelectorAll('.vs-tab-btn').forEach(b => {
            const active = b === btnEl || b.dataset.tab === tab;
            b.style.borderBottomColor = active ? '#4F46E5' : 'transparent';
            b.style.color             = active ? '#4F46E5' : '#64748B';
            b.style.fontWeight        = active ? '700'     : '500';
        });

        // Panel visibility
        ['info', 'travelers', 'status'].forEach(t => {
            const p = document.getElementById('vs-tab-' + t);
            if (p) p.style.display = (t === tab) ? 'block' : 'none';
        });

        // Render active tab
        if (tab === 'info')      window._renderVsInfo?.();
        if (tab === 'travelers') window._renderVsTravelers?.();
        if (tab === 'status')    window._renderVsStatus?.();
    };

    // ── Init ──────────────────────────────────────────────
    window.initWorkVisaTab = async function(config) {
        window._vs.cfg = config;

        const mount = document.getElementById(config.containerId ?? 'ww-visa-mount');
        if (!mount) return;

        // Skeleton
        mount.innerHTML = `
        <div style="padding:20px;text-align:center;color:#94A3B8;font-size:.875rem;">
            <div style="display:inline-block;width:28px;height:28px;border:3px solid #E2E8F0;
                        border-top-color:#6366f1;border-radius:50%;animation:vs-spin .7s linear infinite;
                        margin-bottom:10px;"></div>
            <div>Loading visa service…</div>
        </div>
        <style>
            @keyframes vs-spin { to { transform: rotate(360deg); } }
        </style>`;

        // Fetch or init
        let data = null;
        try {
            const j = await fetch(
                `${config.api.visaServices}?action=get&work_sys_id=${encodeURIComponent(config.workSysId)}`
            ).then(r => r.json());

            if (j.status === 'success' && j.data) {
                data = j.data;
            } else {
                // No row yet — init
                const ji = await fetch(config.api.visaServices, {
                    method:  'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body:    JSON.stringify({ action: 'init', work_sys_id: config.workSysId }),
                }).then(r => r.json());
                if (ji.status === 'success') data = ji.data;
            }
        } catch(e) {
            mount.innerHTML = `<div style="padding:32px;text-align:center;color:#EF4444;font-size:.875rem;">
                ⚠️ Failed to load visa service. Please refresh.
            </div>`;
            return;
        }

        window._vs.data = data;

        const isSubmitted = ['submitted', 'completed'].includes(data?.status);

        // ── Shell ─────────────────────────────────────────
        mount.innerHTML = `
        <!-- Tab bar -->
        <div style="display:flex;align-items:center;gap:0;border-bottom:2px solid #E2E8F0;margin-bottom:0;padding:0 4px;">
            <button class="vs-tab-btn" data-tab="info"
                    onclick="window._vsSwitchTab('info', this)"
                    style="padding:11px 18px;font-size:.875rem;background:none;border:none;
                           border-bottom:2px solid transparent;margin-bottom:-2px;cursor:pointer;
                           color:#64748B;font-weight:500;transition:color .15s;">
                🌍 Visa Info
            </button>
            <button class="vs-tab-btn" data-tab="travelers"
                    onclick="window._vsSwitchTab('travelers', this)"
                    style="padding:11px 18px;font-size:.875rem;background:none;border:none;
                           border-bottom:2px solid transparent;margin-bottom:-2px;cursor:pointer;
                           color:#64748B;font-weight:500;transition:color .15s;">
                👤 Travelers &amp; Docs
            </button>
            <button class="vs-tab-btn" data-tab="status"
                    onclick="window._vsSwitchTab('status', this)"
                    style="padding:11px 18px;font-size:.875rem;background:none;border:none;
                           border-bottom:2px solid transparent;margin-bottom:-2px;cursor:pointer;
                           color:#64748B;font-weight:500;transition:color .15s;">
                📋 Application Status
            </button>

            <!-- Submitted badge -->
            <div id="vs-submitted-badge"
                 style="margin-left:auto;display:${isSubmitted ? 'flex' : 'none'};
                        align-items:center;gap:6px;padding:4px 10px;
                        background:#DCFCE7;border-radius:999px;
                        font-size:.72rem;font-weight:700;color:#15803D;">
                ✓ Submitted
            </div>
        </div>

        <!-- Tab panels -->
        <div style="padding:0 4px;">
            <div id="vs-tab-info"      style="display:none;"></div>
            <div id="vs-tab-travelers" style="display:none;"></div>
            <div id="vs-tab-status"    style="display:none;"></div>
        </div>`;

        // Show initial tab
        window._vsSwitchTab('info',
            mount.querySelector('.vs-tab-btn[data-tab="info"]'));
    };

})();