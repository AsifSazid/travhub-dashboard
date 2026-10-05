/**
 * FILE PATH: /pages/task-tabs/ww-package/index.js
 * Package Service Module — entry point (IIFE)
 * Exposes: window.initWorkPackageTab(config)
 */

(function() {
    'use strict';

    // ── Tab switch ────────────────────────────────────────────
    window._pkSwitchTab = function(tab, btnEl) {
        window._pk.activeTab = tab;

        // Update tab buttons
        document.querySelectorAll('.pk-tab-btn').forEach(b => {
            const active = b.dataset.tab === tab;
            b.style.color       = active ? '#6366f1' : '#64748b';
            b.style.borderBottom = active ? '2px solid #6366f1' : '2px solid transparent';
            b.style.fontWeight  = active ? '700' : '500';
        });

        // Show/hide panels
        document.querySelectorAll('.pk-tab-panel').forEach(p => {
            p.style.display = p.id === `pk-tab-${tab}` ? 'block' : 'none';
        });

        // Render the active tab
        if (tab === 'mindboard') {
            window._renderPkMindboard?.();
        } else if (tab === 'quotation') {
            window._renderPkQuotation?.();
        } else if (tab === 'confirmation') {
            window._renderPkConfirmation?.();
        }
    };

    // ── Main init ─────────────────────────────────────────────
    window.initWorkPackageTab = async function(config) {
        // Store config on shared state
        window._pk.cfg = config;

        const container = document.getElementById(config.containerId);
        if (!container) {
            console.error('[Package] Container not found:', config.containerId);
            return;
        }

        // ── Loading skeleton ──
        container.innerHTML = `
        <div id="pk-loading" style="display:flex;align-items:center;justify-content:center;
                                     min-height:280px;gap:12px;color:#64748b;">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#6366f1"
                 stroke-width="2" style="animation:spin 1s linear infinite;">
                <path d="M12 2v4M12 18v4M4.93 4.93l2.83 2.83M16.24 16.24l2.83 2.83
                         M2 12h4M18 12h4M4.93 19.07l2.83-2.83M16.24 7.76l2.83-2.83"
                      stroke-linecap="round"/>
            </svg>
            <span style="font-size:14px;">Loading Package Module…</span>
        </div>
        <style>@keyframes spin{to{transform:rotate(360deg)}}</style>`;

        // ── Fetch or init data ──
        try {
            const url = `${config.api.packageServices}?action=get&work_sys_id=${encodeURIComponent(config.workSysId)}`;
            const res = await fetch(url);
            const json = await res.json();

            if (json.status === 'success' && json.data) {
                window._pk.data = json.data;
            } else {
                // Auto-init row
                const initRes = await window._pkApi({ action: 'init' });
                if (initRes.status === 'success') {
                    await window._pkReload();
                } else {
                    throw new Error(initRes.message ?? 'Init failed');
                }
            }
        } catch(e) {
            console.error('[Package] Init error:', e);
            container.innerHTML = `
            <div style="padding:24px;text-align:center;color:#ef4444;">
                ⚠️ Failed to load Package module: ${e.message}
            </div>`;
            return;
        }

        // ── Build shell ──
        container.innerHTML = `
        <div id="pk-root" style="font-family:inherit;">

            <!-- Tab bar -->
            <div style="display:flex;align-items:center;border-bottom:1px solid #e2e8f0;
                        margin-bottom:0;gap:0;position:sticky;top:0;background:#fff;z-index:10;
                        padding:0 4px;">
                <button class="pk-tab-btn" data-tab="mindboard"
                        style="padding:12px 20px;border:none;background:none;cursor:pointer;
                               font-size:13px;border-bottom:2px solid #6366f1;color:#6366f1;
                               font-weight:700;transition:all .15s;">
                    🧠 Mindboard
                </button>
                <button class="pk-tab-btn" data-tab="quotation"
                        style="padding:12px 20px;border:none;background:none;cursor:pointer;
                               font-size:13px;border-bottom:2px solid transparent;color:#64748b;
                               font-weight:500;transition:all .15s;">
                    📋 Quotation
                </button>
                <button class="pk-tab-btn" data-tab="confirmation"
                        style="padding:12px 20px;border:none;background:none;cursor:pointer;
                               font-size:13px;border-bottom:2px solid transparent;color:#64748b;
                               font-weight:500;transition:all .15s;">
                    ✅ Confirmation
                </button>

                <!-- Confirmed badge (if confirmed) -->
                <div id="pk-conf-badge" style="margin-left:auto;display:none;">
                    <span style="background:#dcfce7;color:#15803d;font-size:11px;font-weight:700;
                                  padding:4px 10px;border-radius:20px;">● CONFIRMED</span>
                </div>
            </div>

            <!-- Tab panels -->
            <div style="padding:0 4px;">
                <div id="pk-tab-mindboard"    class="pk-tab-panel" style="display:block;"></div>
                <div id="pk-tab-quotation"    class="pk-tab-panel" style="display:none;"></div>
                <div id="pk-tab-confirmation" class="pk-tab-panel" style="display:none;"></div>
            </div>
        </div>`;

        // Tab button click delegation
        container.addEventListener('click', e => {
            const btn = e.target.closest('.pk-tab-btn');
            if (!btn) return;
            window._pkSwitchTab(btn.dataset.tab, btn);
        });

        // Show confirmed badge if confirmed
        if (window._pk.data?.pk_confirmation) {
            const badge = document.getElementById('pk-conf-badge');
            if (badge) badge.style.display = 'block';
        }

        // Render initial tab (mindboard)
        window._pkSwitchTab('mindboard');
    };

})();