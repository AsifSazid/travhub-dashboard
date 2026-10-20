/**
 * FILE PATH: /pages/task-tabs/ww-package/index.js
 * Package Service Module — entry point (IIFE)
 * Exposes: window.initWorkPackageTab(config)
 *
 * LFDP-22 changes:
 *  - Itinerary tab added (4th tab)
 *  - _pkSwitchTab handles 'itinerary' → _renderPkItinerary()
 *  - After mindboard render, calls _pkMbRenderSuggestions()
 *  - On init, loads confirmed services (AT + Hotel) for date auto-sync
 *  - pk_itinerary + trip_days + start_date decoded from API response
 */

(function() {
    'use strict';

    // ── Tab switch ────────────────────────────────────────────
    window._pkSwitchTab = function(tab, btnEl) {
        window._pk.activeTab = tab;

        document.querySelectorAll('.pk-tab-btn').forEach(b => {
            const active = b.dataset.tab === tab;
            b.style.color        = active ? '#6366f1' : '#64748b';
            b.style.borderBottom = active ? '2px solid #6366f1' : '2px solid transparent';
            b.style.fontWeight   = active ? '700' : '500';
        });

        document.querySelectorAll('.pk-tab-panel').forEach(p => {
            p.style.display = p.id === `pk-tab-${tab}` ? 'block' : 'none';
        });

        if      (tab === 'mindboard')    { window._renderPkMindboard?.();    setTimeout(() => window._pkMbRenderSuggestions?.(), 100); }
        else if (tab === 'itinerary')    { window._renderPkItinerary?.(); }
        else if (tab === 'quotation')    { window._renderPkQuotation?.(); }
        else if (tab === 'confirmation') { window._renderPkConfirmation?.(); }
    };

    // ── Main init ─────────────────────────────────────────────
    window.initWorkPackageTab = async function(config) {
        window._pk.cfg = config;

        const container = document.getElementById(config.containerId);
        if (!container) { console.error('[Package] Container not found:', config.containerId); return; }

        // Loading skeleton
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

        // Fetch or init data
        try {
            const url  = `${config.api.packageServices}?action=get&work_sys_id=${encodeURIComponent(config.workSysId)}`;
            const res  = await fetch(url);
            const json = await res.json();

            if (json.status === 'success' && json.data) {
                window._pk.data = json.data;
            } else {
                const initRes = await window._pkApi({ action: 'init' });
                if (initRes.status === 'success') {
                    await window._pkReload();
                } else {
                    throw new Error(initRes.message ?? 'Init failed');
                }
            }
        } catch(e) {
            console.error('[Package] Init error:', e);
            container.innerHTML = `<div style="padding:24px;text-align:center;color:#ef4444;">
                ⚠️ Failed to load Package module: ${e.message}</div>`;
            return;
        }

        // Load confirmed services (AT + Hotel) for date auto-sync — fire & forget
        _pkLoadConfirmedServices().catch(() => {});

        // Build shell
        container.innerHTML = `
        <div id="pk-root" style="font-family:inherit;">
            <div style="display:flex;align-items:center;border-bottom:1px solid #e2e8f0;
                        margin-bottom:0;gap:0;position:sticky;top:0;background:#fff;z-index:10;
                        padding:0 4px;overflow-x:auto;">
                <button class="pk-tab-btn" data-tab="mindboard"
                        style="padding:12px 16px;border:none;background:none;cursor:pointer;
                               font-size:13px;border-bottom:2px solid #6366f1;color:#6366f1;
                               font-weight:700;transition:all .15s;white-space:nowrap;">
                    🧠 Mindboard
                </button>
                <button class="pk-tab-btn" data-tab="itinerary"
                        style="padding:12px 16px;border:none;background:none;cursor:pointer;
                               font-size:13px;border-bottom:2px solid transparent;color:#64748b;
                               font-weight:500;transition:all .15s;white-space:nowrap;">
                    🗓️ Itinerary
                </button>
                <button class="pk-tab-btn" data-tab="quotation"
                        style="padding:12px 16px;border:none;background:none;cursor:pointer;
                               font-size:13px;border-bottom:2px solid transparent;color:#64748b;
                               font-weight:500;transition:all .15s;white-space:nowrap;">
                    📋 Quotation
                </button>
                <button class="pk-tab-btn" data-tab="confirmation"
                        style="padding:12px 16px;border:none;background:none;cursor:pointer;
                               font-size:13px;border-bottom:2px solid transparent;color:#64748b;
                               font-weight:500;transition:all .15s;white-space:nowrap;">
                    ✅ Confirmation
                </button>

                <div id="pk-conf-badge" style="margin-left:auto;display:none;padding-right:8px;">
                    <span style="background:#dcfce7;color:#15803d;font-size:11px;font-weight:700;
                                  padding:4px 10px;border-radius:20px;">● CONFIRMED</span>
                </div>
            </div>

            <div style="padding:0 4px;">
                <div id="pk-tab-mindboard"    class="pk-tab-panel" style="display:block;"></div>
                <div id="pk-tab-itinerary"    class="pk-tab-panel" style="display:none;"></div>
                <div id="pk-tab-quotation"    class="pk-tab-panel" style="display:none;"></div>
                <div id="pk-tab-confirmation" class="pk-tab-panel" style="display:none;"></div>
            </div>
        </div>`;

        // Tab click
        container.addEventListener('click', e => {
            const btn = e.target.closest('.pk-tab-btn');
            if (!btn) return;
            window._pkSwitchTab(btn.dataset.tab, btn);
        });

        // Confirmed badge
        if (window._pk.data?.pk_confirmation) {
            const badge = document.getElementById('pk-conf-badge');
            if (badge) badge.style.display = 'block';
        }

        // Render initial tab
        window._pkSwitchTab('mindboard');
    };

    // ── Load confirmed AT + Hotel services for date sync ──────
    async function _pkLoadConfirmedServices() {
        const url  = `${window._pk.cfg.api.packageServices}?action=get_confirmed_services&work_sys_id=${encodeURIComponent(window._pk.cfg.workSysId)}`;
        const json = await fetch(url).then(r => r.json());
        if (json.status === 'success') {
            window._pk.confirmedServices = json.data ?? {};

            // Auto-set start_date from AT departure if not already set
            const atDate = json.data?.air_ticket?.departure_date ?? null;
            if (atDate && window._pk.data && !window._pk.data.start_date) {
                window._pk.data.start_date = atDate;
                // Persist silently
                window._pkApi({
                    action:     'save_itinerary',
                    pk_itinerary: window._pk.data.pk_itinerary ?? [],
                    trip_days:  window._pk.data.trip_days ?? null,
                    start_date: atDate,
                }).catch(() => {});
            }
        }
    }

})();