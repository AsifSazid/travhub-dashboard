<?php
include_once('./authenticate.php');
require_once '../server/db_connection.php';
require_once '../server/permissions.php';
requireFullAccountingAccess($pdo, false);
$ip_port = trim(@file_get_contents('../ippath.txt') ?: 'http://103.104.219.3:898', '/');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Balance Sheet — TravHub</title>
    <link rel="icon" type="image/png" href="../assets/images/logo/round-logo.png">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
    <style>
        .bs-section-header { font-weight:700; font-size:.75rem; text-transform:uppercase; letter-spacing:.05em; padding:10px 16px; border-bottom:1px solid #e5e7eb; }
        .bs-row { display:flex; justify-content:space-between; padding:7px 16px; border-bottom:1px solid #f9fafb; font-size:.85rem; }
        .bs-row:hover { background:#fafafa; }
        .bs-row.indent { padding-left:32px; color:#6b7280; }
        .bs-row.subtotal { font-weight:600; background:#f9fafb; }
        .bs-row.total { font-weight:700; border-top:2px solid #e5e7eb; background:#f3f4f6; }
        .bs-row.grand-total { font-weight:700; font-size:.95rem; background:#4f46e5; color:#fff; padding:12px 16px; }
        .bs-row.grand-total:hover { background:#4338ca; }
        .amount { font-variant-numeric:tabular-nums; }
        .negative { color:#dc2626; }
    </style>
</head>
<body class="bg-gray-50">
<?php include '../elements/header.php'; ?>
<?php include '../elements/aside.php'; ?>
<?php include '../elements/preview-model.php'; ?>

<main id="mainContent" class="pt-16 pl-64 transition-all duration-300">
<div class="p-6 max-w-4xl mx-auto">

    <!-- Header -->
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-3">
        <div>
            <h1 class="text-2xl font-bold text-gray-900"><i class="fas fa-scale-balanced text-indigo-600 mr-2"></i>Balance Sheet</h1>
            <p class="text-sm text-gray-500 mt-1" id="subtitle">As at today</p>
        </div>
        <div class="flex gap-2">
            <div>
                <label class="block text-xs text-gray-500 mb-1">As at Date</label>
                <input type="date" id="as-at-date"
                    class="px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-400">
            </div>
            <div class="flex items-end gap-2">
                <button onclick="loadBalanceSheet()" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-sm font-medium">
                    <i class="fas fa-sync mr-1"></i> Load
                </button>
                <div class="relative" id="exportWrap">
                    <button onclick="document.getElementById('exportMenu').classList.toggle('hidden')"
                        class="px-3 py-2 bg-white border border-gray-300 hover:bg-gray-50 text-gray-700 rounded-lg text-sm">
                        <i class="fas fa-download mr-1"></i> Export
                    </button>
                    <div id="exportMenu" class="hidden absolute right-0 mt-1 w-36 bg-white border border-gray-200 rounded-xl shadow-lg z-50">
                        <button onclick="exportBS('pdf')"  class="w-full text-left px-4 py-2.5 text-sm hover:bg-gray-50 flex items-center gap-2"><i class="fas fa-file-pdf text-red-500"></i> PDF</button>
                        <button onclick="exportBS('excel')" class="w-full text-left px-4 py-2.5 text-sm hover:bg-gray-50 flex items-center gap-2"><i class="fas fa-file-excel text-green-600"></i> Excel</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div id="loadingBar" class="hidden text-center py-12"><i class="fas fa-spinner fa-spin text-2xl text-indigo-500"></i></div>
    <div id="balanceSheet" class="hidden space-y-4"></div>

    <!-- Balance check badge -->
    <div id="balanceCheck" class="hidden mt-4 text-center">
        <span id="balanceCheckBadge" class="inline-flex items-center gap-2 px-4 py-2 rounded-full text-sm font-semibold"></span>
    </div>

</div>
</main>

<script>
const API = "<?php echo $ip_port; ?>/api/reports/balance_sheet/endpoints.php";
let _bsData = null;

function fmt(n) {
    const v = parseFloat(n) || 0;
    return (v < 0 ? '(' : '') + '৳' + Math.abs(v).toLocaleString('en-US',{minimumFractionDigits:2,maximumFractionDigits:2}) + (v < 0 ? ')' : '');
}
function amtClass(n) { return parseFloat(n) < 0 ? 'amount negative' : 'amount'; }

function row(label, amount, cls='') {
    return `<div class="bs-row ${cls}"><span>${label}</span><span class="${amtClass(amount)}">${fmt(amount)}</span></div>`;
}
function sectionHeader(label, icon='', color='text-gray-700') {
    return `<div class="bs-section-header ${color}"><i class="${icon} mr-2"></i>${label}</div>`;
}

async function loadBalanceSheet() {
    const asAt = document.getElementById('as-at-date').value || new Date().toISOString().split('T')[0];
    document.getElementById('subtitle').textContent = `As at ${asAt}`;
    document.getElementById('loadingBar').classList.remove('hidden');
    document.getElementById('balanceSheet').classList.add('hidden');
    document.getElementById('balanceCheck').classList.add('hidden');

    try {
        const r = await fetch(`${API}?action=balance_sheet&as_at=${asAt}`);
        const d = await r.json();
        if (!d.success) { alert('Error: ' + d.error); return; }
        _bsData = d;
        renderBalanceSheet(d);
    } catch(e) {
        alert('Network error: ' + e.message);
    } finally {
        document.getElementById('loadingBar').classList.add('hidden');
    }
}

function renderBalanceSheet(d) {
    const ca = d.current_assets;
    const cl = d.current_liabilities;
    const fa = d.fixed_assets;
    const cr = d.capital_reserves;

    let html = '';

    // ── CURRENT ASSETS ────────────────────────────────────────
    html += `<div class="bg-white rounded-xl border border-gray-200 overflow-hidden">`;
    html += sectionHeader('Current Assets', 'fas fa-coins', 'text-emerald-700');

    html += `<div class="bs-section-header text-xs text-gray-500 py-2" style="font-size:.7rem;padding:6px 32px;background:#f9fafb;">Cash at bank and in hand</div>`;
    (ca.cash_accounts||[]).forEach(a => {
        html += row(a.account_name + (a.account_code ? ` <span class="text-gray-400 text-xs">${a.account_code}</span>` : ''), a.balance, 'indent');
    });
    html += row('Total Cash at bank and in hand', ca.total_cash, 'subtotal');

    if ((ca.accounts_receivable||0) !== 0)
        html += row('Accounts Receivable', ca.accounts_receivable, 'indent');
    if ((ca.ait_recoverable||0) !== 0)
        html += row('AIT Recoverable', ca.ait_recoverable, 'indent');

    (ca.other_assets||[]).forEach(a => {
        if (parseFloat(a.balance) !== 0)
            html += row(a.account_name, a.balance, 'indent');
    });

    html += row('Total Current Assets', ca.total, 'total');
    html += `</div>`;

    // ── FIXED ASSETS ──────────────────────────────────────────
    if ((fa.total||0) !== 0) {
        html += `<div class="bg-white rounded-xl border border-gray-200 overflow-hidden">`;
        html += sectionHeader('Fixed Assets', 'fas fa-building', 'text-blue-700');
        (fa.items||[]).forEach(a => {
            if (parseFloat(a.balance) !== 0)
                html += row(a.account_name, a.balance, 'indent');
        });
        html += row('Total Fixed Assets', fa.total, 'total');
        html += `</div>`;
    }

    // ── CURRENT LIABILITIES ───────────────────────────────────
    html += `<div class="bg-white rounded-xl border border-gray-200 overflow-hidden">`;
    html += sectionHeader('Creditors: amounts falling due within one year', 'fas fa-file-invoice-dollar', 'text-rose-700');

    if ((cl.accounts_payable||0) !== 0)
        html += row('Accounts Payable', cl.accounts_payable, 'indent');
    if ((cl.vat_payable||0) !== 0)
        html += row('VAT / Tax Payable', cl.vat_payable, 'indent');
    if ((cl.payroll_liability||0) !== 0)
        html += row('Payroll Liabilities', cl.payroll_liability, 'indent');
    (cl.other||[]).forEach(a => {
        if (parseFloat(a.balance) !== 0)
            html += row(a.account_name, a.balance, 'indent');
    });

    html += row('Total Current Liabilities', cl.total, 'total');
    html += `</div>`;

    // ── NET CURRENT ASSETS ────────────────────────────────────
    html += `<div class="bg-white rounded-xl border border-gray-200 overflow-hidden">`;
    html += row('Net Current Assets (Liabilities)', d.net_current_assets, 'subtotal text-indigo-700');
    html += row('Total Assets less Current Liabilities', d.total_assets_less_current_liab, 'total');
    html += `</div>`;

    // ── CAPITAL & RESERVES ────────────────────────────────────
    html += `<div class="bg-white rounded-xl border border-gray-200 overflow-hidden">`;
    html += sectionHeader('Capital and Reserves', 'fas fa-landmark', 'text-indigo-700');

    (cr.capital_accounts||[]).forEach(a => {
        if (parseFloat(a.balance) !== 0)
            html += row(a.account_name, a.balance, 'indent');
    });
    if ((cr.retained_earnings||0) !== 0)
        html += row('Retained Earnings (Prior Years)', cr.retained_earnings, 'indent');
    html += row('Current Year Earnings', cr.current_year_earnings, 'indent');
    html += row('Total Capital and Reserves', cr.total, 'total');
    html += `</div>`;

    // ── NET ASSETS ────────────────────────────────────────────
    html += `<div class="bg-white rounded-xl border border-gray-200 overflow-hidden">`;
    html += `<div class="bs-row grand-total"><span>Net Assets</span><span class="amount">${fmt(d.net_assets)}</span></div>`;
    html += `</div>`;

    document.getElementById('balanceSheet').innerHTML = html;
    document.getElementById('balanceSheet').classList.remove('hidden');

    // Balance check
    const badge = document.getElementById('balanceCheckBadge');
    if (d.balanced) {
        badge.className = 'inline-flex items-center gap-2 px-4 py-2 rounded-full text-sm font-semibold bg-emerald-100 text-emerald-700';
        badge.innerHTML = '<i class="fas fa-check-circle"></i> Balanced — Net Assets = Capital & Reserves';
    } else {
        badge.className = 'inline-flex items-center gap-2 px-4 py-2 rounded-full text-sm font-semibold bg-rose-100 text-rose-700';
        badge.innerHTML = `<i class="fas fa-exclamation-circle"></i> Not balanced — difference: ${fmt(d.net_assets - d.capital_reserves?.total)}`;
    }
    document.getElementById('balanceCheck').classList.remove('hidden');
}

function exportBS(type) {
    document.getElementById('exportMenu').classList.add('hidden');
    if (!_bsData) { alert('Load balance sheet first'); return; }
    const d   = _bsData;
    const ca  = d.current_assets;
    const cl  = d.current_liabilities;
    const cr  = d.capital_reserves;
    const asAt = document.getElementById('as-at-date').value || 'today';

    const rows = [
        [`Balance Sheet — TravHub`, '', `As at ${asAt}`],
        [],
        ['CURRENT ASSETS', '', ''],
        ['Cash at bank and in hand', '', ''],
        ...(ca.cash_accounts||[]).map(a => [`  ${a.account_name}`, '', parseFloat(a.balance)]),
        ['  Total Cash', '', parseFloat(ca.total_cash)],
        ['  Accounts Receivable', '', parseFloat(ca.accounts_receivable)],
        ['  AIT Recoverable', '', parseFloat(ca.ait_recoverable)],
        ['Total Current Assets', '', parseFloat(ca.total)],
        [],
        ['CURRENT LIABILITIES', '', ''],
        ['  Accounts Payable', '', parseFloat(cl.accounts_payable)],
        ['  VAT Payable', '', parseFloat(cl.vat_payable)],
        ['  Payroll Liabilities', '', parseFloat(cl.payroll_liability)],
        ['Total Current Liabilities', '', parseFloat(cl.total)],
        [],
        ['Net Current Assets', '', parseFloat(d.net_current_assets)],
        ['Total Assets less Current Liabilities', '', parseFloat(d.total_assets_less_current_liab)],
        [],
        ['CAPITAL & RESERVES', '', ''],
        ...(cr.capital_accounts||[]).map(a => [`  ${a.account_name}`, '', parseFloat(a.balance)]),
        ['  Retained Earnings', '', parseFloat(cr.retained_earnings)],
        ['  Current Year Earnings', '', parseFloat(cr.current_year_earnings)],
        ['Total Capital & Reserves', '', parseFloat(cr.total)],
        [],
        ['Net Assets', '', parseFloat(d.net_assets)],
    ];

    if (type === 'excel') {
        const ws = XLSX.utils.aoa_to_sheet(rows);
        ws['!cols'] = [{wch:40},{wch:5},{wch:18}];
        const wb = XLSX.utils.book_new();
        XLSX.utils.book_append_sheet(wb, ws, 'Balance Sheet');
        XLSX.writeFile(wb, `balance-sheet-${asAt}.xlsx`);
    } else {
        const win = window.open('', '_blank');
        win.document.write(`<html><head><title>Balance Sheet</title><style>
            body{font-family:Arial,sans-serif;padding:24px;max-width:700px;margin:0 auto;}
            h1{font-size:20px;margin-bottom:4px;}h2{font-size:14px;color:#6b7280;margin-bottom:20px;}
            table{width:100%;border-collapse:collapse;}
            td{padding:5px 8px;font-size:13px;}
            .header{font-weight:700;font-size:11px;text-transform:uppercase;letter-spacing:.05em;padding:8px;background:#f3f4f6;border-top:2px solid #e5e7eb;}
            .indent{padding-left:24px;color:#374151;}
            .subtotal{font-weight:600;border-top:1px solid #e5e7eb;}
            .total{font-weight:700;border-top:2px solid #374151;background:#f9fafb;}
            .grand{font-weight:700;font-size:14px;border-top:2px solid #111;background:#f0f0f0;}
            .r{text-align:right;}
        </style></head><body>
        <h1>Balance Sheet</h1>
        <h2>As at ${asAt}</h2>
        <table>
        ${rows.map(r => {
            if (!r.length || (r[0]==='' && r[1]==='' && r[2]==='')) return '<tr><td colspan="2" style="padding:4px"></td></tr>';
            const label = r[0], amt = r[2];
            const cls = label.startsWith('  ') ? 'indent' : label.includes('Total') ? 'total' : label === 'Net Assets' ? 'grand' : '';
            const amtHtml = amt !== '' ? `<td class="r ${parseFloat(amt)<0?'style="color:#dc2626"':''}">${fmt(amt)}</td>` : '<td></td>';
            return `<tr><td class="${cls}">${label}</td>${amtHtml}</tr>`;
        }).join('')}
        </table>
        <script>window.onload=()=>window.print()<\/script>
        </body></html>`);
        win.document.close();
    }
}

document.addEventListener('click', e => {
    if (!e.target.closest('#exportWrap')) document.getElementById('exportMenu')?.classList.add('hidden');
});

document.addEventListener('DOMContentLoaded', () => {
    const today = new Date().toISOString().split('T')[0];
    document.getElementById('as-at-date').value = today;
    loadBalanceSheet();
});
</script>
<script src="../assets/js/script.js?time=<?php echo time(); ?>"></script>
</body>
</html>