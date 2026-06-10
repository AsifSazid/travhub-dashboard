/**
 * TravHub Shared Utilities — assets/js/th-utils.js
 * Include BEFORE any manager script.
 */

// ── Fetch wrapper ─────────────────────────────────────────────────────
async function thApi(url, method = 'GET', body = null) {
    const opts = { method, headers: { 'Content-Type': 'application/json' } };
    if (body) opts.body = JSON.stringify(body);
    const res = await fetch(url, opts);
    return res.json();
}

// ── Toast ─────────────────────────────────────────────────────────────
function thToast(msg, type = 'success') {
    const colours = { success: '#50BC81', error: '#EF4444', info: '#3B82F6', warning: '#F59E0B' };
    const icons   = { success: 'fa-check', error: 'fa-xmark', info: 'fa-info', warning: 'fa-triangle-exclamation' };
    const el = document.createElement('div');
    el.style.cssText = `position:fixed;bottom:24px;right:24px;z-index:9999;background:${colours[type]||colours.success};
        color:#fff;font-size:14px;font-weight:500;padding:12px 20px;border-radius:16px;
        box-shadow:0 8px 24px rgba(0,0,0,.18);display:flex;align-items:center;gap:10px;
        animation:thSlideUp .25s ease;pointer-events:none;max-width:380px;`;
    el.innerHTML = `<i class="fa-solid ${icons[type]||icons.success}"></i><span>${msg}</span>`;
    document.body.appendChild(el);
    setTimeout(() => { el.style.opacity = '0'; el.style.transform = 'translateY(8px)'; setTimeout(() => el.remove(), 300); }, 3200);
}
const style = document.createElement('style');
style.textContent = '@keyframes thSlideUp{from{opacity:0;transform:translateY(12px)}to{opacity:1;transform:translateY(0)}}';
document.head.appendChild(style);

// countriesCache থেকে currency_code বের করার helper
function getCurrencyByCountry(sys_id) {
    const c = countriesCache.find(x => x.sys_id === sys_id);
    return c?.currency_code || '';
}

// ── Modal ─────────────────────────────────────────────────────────────
function thOpenModal(id)  { document.getElementById(id)?.classList.remove('hidden'); }
function thCloseModal(id) { document.getElementById(id)?.classList.add('hidden'); }

// ── Form helpers ──────────────────────────────────────────────────────
function thVal(id)        { return (document.getElementById(id)?.value ?? '').trim(); }
function thSetVal(id, v)  { const el = document.getElementById(id); if (el) el.value = v ?? ''; }
function thChecked(id)    { return !!document.getElementById(id)?.checked; }
function thSetCheck(id,v) { const el = document.getElementById(id); if (el) el.checked = !!v; }

// ── Badges ────────────────────────────────────────────────────────────
function thStatusBadge(s) {
    const map = {
        active:       'bg-green-100 text-green-700',
        inactive:     'bg-yellow-100 text-yellow-700',
        deleted:      'bg-red-100 text-red-700',
        draft:        'bg-gray-100 text-gray-600',
        confirmed:    'bg-blue-100 text-blue-700',
        completed:    'bg-[#50BC81]/20 text-[#3da868]',
        cancelled:    'bg-red-100 text-red-700',
        pending:      'bg-yellow-100 text-yellow-700',
        requested:    'bg-blue-100 text-blue-700',
        paid:         'bg-green-100 text-green-700',
        voucher_issued: 'bg-purple-100 text-purple-700',
        sent:         'bg-indigo-100 text-indigo-700',
        accepted:     'bg-green-100 text-green-700',
        superseded:   'bg-gray-100 text-gray-500',
        expired:      'bg-red-100 text-red-500',
        issued:       'bg-[#50BC81]/20 text-[#3da868]',
    };
    const cls = map[s] || 'bg-gray-100 text-gray-500';
    const label = s ? s.replace(/_/g,' ').replace(/\b\w/g,c=>c.toUpperCase()) : '—';
    return `<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${cls}">${label}</span>`;
}

// ── Pagination ────────────────────────────────────────────────────────
function thPagination(containerId, pg, onPageFn) {
    const el = document.getElementById(containerId);
    if (!el) return;
    const { page, total_pages, total } = pg;
    let html = `<span class="text-xs text-gray-400">Showing ${page} of ${total_pages} | Total record(s): ${total} </span>`;
    if (total_pages > 1) {
        html += '<div class="flex gap-1">';
        const start = Math.max(1, page - 2), end = Math.min(total_pages, page + 2);
        if (start > 1) html += `<button class="px-3 py-1.5 rounded-lg text-xs bg-white border border-gray-200 text-gray-600 hover:bg-gray-50" onclick="${onPageFn}(1)">1</button>${start>2?'<span class="px-2 py-1.5 text-xs text-gray-400">…</span>':''}`;
        for (let i = start; i <= end; i++) {
            const a = i === page ? 'bg-[#1A2039] text-white' : 'bg-white border border-gray-200 text-gray-600 hover:bg-gray-50';
            html += `<button class="px-3 py-1.5 rounded-lg text-xs font-medium ${a}" onclick="${onPageFn}(${i})">${i}</button>`;
        }
        if (end < total_pages) html += `${end<total_pages-1?'<span class="px-2 py-1.5 text-xs text-gray-400">…</span>':''}<button class="px-3 py-1.5 rounded-lg text-xs bg-white border border-gray-200 text-gray-600 hover:bg-gray-50" onclick="${onPageFn}(${total_pages})">${total_pages}</button>`;
        html += '</div>';
    }
    el.innerHTML = html;
    el.className = 'flex items-center justify-between mt-2';
}

// ── Number format ─────────────────────────────────────────────────────
function thMoney(n, sym = '৳') { return sym + Number(n||0).toLocaleString('en-BD', {minimumFractionDigits:2,maximumFractionDigits:2}); }
function thNum(n)               { return Number(n||0).toLocaleString(); }

// ── Confirm dialog ────────────────────────────────────────────────────
function thConfirm(msg) { return window.confirm(msg); }

// ── Date format ───────────────────────────────────────────────────────
function thDate(d) { if (!d) return '—'; const dt = new Date(d); return dt.toLocaleDateString('en-GB',{day:'2-digit',month:'short',year:'numeric'}); }
