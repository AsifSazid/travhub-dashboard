<?php
include_once('./authenticate.php');
$ip_port = @file_get_contents('../ippath.txt');
if (empty($ip_port)) {
    $ip_port = "http://103.104.219.3:898";
}

$base_ip_path = trim($ip_port, "/");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Receivable Report | TravHub Global Limited</title>
    <link rel="icon" type="image/png" href="../assets/images/logo/round-logo.png" sizes="16x16">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <script>
      tailwind.config = {
        theme: {
          extend: {
            colors: { navy: '#1A2039', green: '#50BC81' },
            fontFamily: { sans: ['Poppins', 'sans-serif'] }
          }
        }
      }
    </script>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.8.2/jspdf.plugin.autotable.min.js"></script>

    <style>body{font-family:'Poppins',sans-serif;}</style>
</head>
<body class="bg-gray-50 text-navy">

    <?php include '../elements/header.php'; ?>
    <?php include '../elements/aside.php'; ?>

    <main id="mainContent" class="pt-16 pl-0 lg:pl-64 lg:my-16 transition-all duration-300 h-full">
        <div class="max-w-7xl mx-auto p-4 md:p-6">

          <div class="flex flex-wrap items-center justify-between gap-3 mb-6">
            <div>
              <h1 class="text-xl md:text-2xl font-semibold text-navy">Receivable Report</h1>
              <p class="text-sm text-gray-500">Client-wise: Sale − Receive − Discount/Refund</p>
            </div>

            <div class="relative">
              <button id="exportBtn" class="flex items-center gap-2 bg-navy text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-navy/90">
                Download
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
              </button>
              <div id="exportMenu" class="hidden absolute right-0 mt-2 w-64 bg-white rounded-lg shadow-lg border border-gray-100 z-20 overflow-hidden">
                <div class="px-3 py-2 text-xs font-semibold text-gray-400 uppercase">With current filter</div>
                <button data-type="csv"  data-scope="filtered" class="export-action w-full text-left px-4 py-2 text-sm hover:bg-gray-50">CSV</button>
                <button data-type="xlsx" data-scope="filtered" class="export-action w-full text-left px-4 py-2 text-sm hover:bg-gray-50">Excel (.xlsx)</button>
                <button data-type="pdf"  data-scope="filtered" class="export-action w-full text-left px-4 py-2 text-sm hover:bg-gray-50">PDF</button>
                <div class="border-t border-gray-100"></div>
                <div class="px-3 py-2 text-xs font-semibold text-gray-400 uppercase">Without filter (all)</div>
                <button data-type="csv"  data-scope="all" class="export-action w-full text-left px-4 py-2 text-sm hover:bg-gray-50">CSV</button>
                <button data-type="xlsx" data-scope="all" class="export-action w-full text-left px-4 py-2 text-sm hover:bg-gray-50">Excel (.xlsx)</button>
                <button data-type="pdf"  data-scope="all" class="export-action w-full text-left px-4 py-2 text-sm hover:bg-gray-50">PDF</button>
              </div>
            </div>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-6">
            <div class="bg-white rounded-xl border border-gray-100 p-4">
              <p class="text-xs text-gray-400 mb-1">Clients with Dues</p>
              <p id="sumCount" class="text-2xl font-semibold text-navy">0</p>
            </div>
            <div class="bg-white rounded-xl border border-gray-100 p-4">
              <p class="text-xs text-gray-400 mb-1">Total Receivable</p>
              <p id="sumAmount" class="text-2xl font-semibold text-green">৳ 0.00</p>
            </div>
          </div>

          <div class="bg-white rounded-xl border border-gray-100 p-4 mb-3">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
              <div>
                <label class="text-xs text-gray-500">From Date</label>
                <input type="date" id="f_date_from" class="w-full mt-1 border border-gray-200 rounded-lg px-3 py-2 text-sm">
              </div>
              <div>
                <label class="text-xs text-gray-500">To Date</label>
                <input type="date" id="f_date_to" class="w-full mt-1 border border-gray-200 rounded-lg px-3 py-2 text-sm">
              </div>
              <div>
                <label class="text-xs text-gray-500">Search Client</label>
                <input type="text" id="f_search" placeholder="Client name / sys_id" class="w-full mt-1 border border-gray-200 rounded-lg px-3 py-2 text-sm">
              </div>
              <div class="flex items-end gap-2">
                <button id="applyFilter" class="flex-1 bg-green text-white rounded-lg px-3 py-2 text-sm font-medium hover:bg-green/90">Apply</button>
                <button id="clearFilter" class="flex-1 border border-gray-300 text-gray-500 rounded-lg px-3 py-2 text-sm hover:bg-gray-50">Clear</button>
              </div>
            </div>
          </div>

          <div class="bg-white rounded-xl border border-gray-100 overflow-x-auto">
            <table class="w-full text-sm">
              <thead class="bg-navy text-white">
                <tr>
                  <th class="px-3 py-2 text-left">Client</th>
                  <th class="px-3 py-2 text-right">Total Sale</th>
                  <th class="px-3 py-2 text-right">Total Receive</th>
                  <th class="px-3 py-2 text-right">Discount/Refund</th>
                  <th class="px-3 py-2 text-right">Receivable</th>
                  <th class="px-3 py-2 text-left">Last Activity</th>
                  <th class="px-3 py-2 text-center">Details</th>
                </tr>
              </thead>
              <tbody id="tableBody">
                <tr><td colspan="7" class="text-center py-6 text-gray-400">Loading...</td></tr>
              </tbody>
            </table>
          </div>

          <div class="flex items-center justify-between mt-4">
            <p id="pageInfo" class="text-xs text-gray-500">-</p>
            <div class="flex gap-2">
              <button id="prevPage" class="px-3 py-1.5 text-sm border border-gray-200 rounded-lg hover:bg-gray-50">Prev</button>
              <button id="nextPage" class="px-3 py-1.5 text-sm border border-gray-200 rounded-lg hover:bg-gray-50">Next</button>
            </div>
          </div>

        </div>
    </main>

    <!-- Drilldown modal -->
    <div id="detailModal" class="hidden fixed inset-0 bg-black/40 z-30 flex items-center justify-center p-4">
      <div class="bg-white rounded-xl max-w-3xl w-full max-h-[80vh] overflow-y-auto">
        <div class="flex items-center justify-between p-4 border-b border-gray-100 sticky top-0 bg-white">
          <h3 id="detailTitle" class="font-semibold text-navy">Client Ledger</h3>
          <button id="closeDetail" class="text-gray-400 hover:text-gray-600">&times;</button>
        </div>
        <div class="p-4">
          <table class="w-full text-xs">
            <thead class="text-gray-500">
              <tr>
                <th class="text-left py-1">Date</th>
                <th class="text-left py-1">Purpose</th>
                <th class="text-left py-1">Type</th>
                <th class="text-right py-1">Amount</th>
              </tr>
            </thead>
            <tbody id="detailBody"></tbody>
          </table>
        </div>
      </div>
    </div>

    <script src="../assets/js/script.js?time=<?php echo time(); ?>"></script>

    <script>
    (function () {
      'use strict';

      const API = '/api/reports/receivable/endpoints.php';
      const TYPE_LABEL = { 1: 'Sale', 3: 'Receive', 5: 'Discount/Refund' };

      let state = { page: 1, per_page: 25, pages: 1 };

      document.addEventListener('DOMContentLoaded', () => {
        fetchData();
        bindEvents();
      });

      function bindEvents() {
        document.getElementById('applyFilter').addEventListener('click', () => { state.page = 1; fetchData(); });
        document.getElementById('clearFilter').addEventListener('click', () => {
          document.getElementById('f_date_from').value = '';
          document.getElementById('f_date_to').value = '';
          document.getElementById('f_search').value = '';
          state.page = 1;
          fetchData();
        });
        document.getElementById('prevPage').addEventListener('click', () => { if (state.page > 1) { state.page--; fetchData(); } });
        document.getElementById('nextPage').addEventListener('click', () => { if (state.page < state.pages) { state.page++; fetchData(); } });

        document.getElementById('exportBtn').addEventListener('click', (e) => {
          e.stopPropagation();
          document.getElementById('exportMenu').classList.toggle('hidden');
        });
        document.addEventListener('click', () => document.getElementById('exportMenu').classList.add('hidden'));

        document.querySelectorAll('.export-action').forEach(btn => {
          btn.addEventListener('click', (e) => {
            e.stopPropagation();
            runExport(btn.dataset.type, btn.dataset.scope);
          });
        });

        document.getElementById('closeDetail').addEventListener('click', () => {
          document.getElementById('detailModal').classList.add('hidden');
        });
      }

      function buildParams(action, scopeAll) {
        const p = new URLSearchParams();
        p.append('action', action);

        if (!scopeAll) {
          const dateFrom = document.getElementById('f_date_from').value;
          const dateTo   = document.getElementById('f_date_to').value;
          const search   = document.getElementById('f_search').value;
          if (dateFrom) p.append('date_from', dateFrom);
          if (dateTo) p.append('date_to', dateTo);
          if (search) p.append('search', search);
        }

        if (action === 'list') {
          p.append('page', state.page);
          p.append('per_page', state.per_page);
        }
        return p;
      }

      async function fetchData() {
        const params = buildParams('list', false);
        document.getElementById('tableBody').innerHTML = `<tr><td colspan="7" class="text-center py-6 text-gray-400">Loading...</td></tr>`;

        try {
          const res = await fetch(`${API}?${params.toString()}`);
          const data = await res.json();
          if (!data.success) {
            document.getElementById('tableBody').innerHTML = `<tr><td colspan="7" class="text-center py-6 text-red-400">Failed to load data.</td></tr>`;
            return;
          }
          renderTable(data.rows);
          renderSummary(data.summary);
          state.pages = data.pages || 1;
          document.getElementById('pageInfo').textContent = `Page ${data.page} of ${data.pages} — ${data.total} clients`;
        } catch (err) {
          console.error(err);
          document.getElementById('tableBody').innerHTML = `<tr><td colspan="7" class="text-center py-6 text-red-400">Error loading report.</td></tr>`;
        }
      }

      function renderSummary(summary) {
        document.getElementById('sumCount').textContent = summary.total_clients;
        document.getElementById('sumAmount').textContent = '৳ ' + Number(summary.total_receivable).toLocaleString('en-BD', { minimumFractionDigits: 2 });
      }

      function renderTable(rows) {
        const tbody = document.getElementById('tableBody');
        if (!rows.length) {
          tbody.innerHTML = `<tr><td colspan="7" class="text-center py-6 text-gray-400">No outstanding receivables.</td></tr>`;
          return;
        }
        tbody.innerHTML = rows.map(r => `
          <tr class="border-t border-gray-100 hover:bg-gray-50">
            <td class="px-3 py-2 font-medium">${r.user_name || '-'}</td>
            <td class="px-3 py-2 text-right">${fmt(r.total_sale)}</td>
            <td class="px-3 py-2 text-right">${fmt(r.total_receive)}</td>
            <td class="px-3 py-2 text-right">${fmt(r.total_discount)}</td>
            <td class="px-3 py-2 text-right font-semibold ${r.receivable_balance > 0 ? 'text-red-500' : 'text-green'}">${fmt(r.receivable_balance)}</td>
            <td class="px-3 py-2 text-xs text-gray-500">${formatDate(r.last_activity_date)}</td>
            <td class="px-3 py-2 text-center">
              <button class="view-detail text-navy hover:underline text-xs" data-id="${r.user_sys_id}" data-name="${r.user_name}">View</button>
            </td>
          </tr>
        `).join('');

        document.querySelectorAll('.view-detail').forEach(btn => {
          btn.addEventListener('click', () => openDetail(btn.dataset.id, btn.dataset.name));
        });
      }

      async function openDetail(userSysId, name) {
        document.getElementById('detailTitle').textContent = `${name} — Ledger`;
        document.getElementById('detailBody').innerHTML = `<tr><td colspan="4" class="text-center py-4 text-gray-400">Loading...</td></tr>`;
        document.getElementById('detailModal').classList.remove('hidden');

        try {
          const res = await fetch(`${API}?action=detail&user_sys_id=${encodeURIComponent(userSysId)}`);
          const data = await res.json();
          if (!data.success || !data.rows.length) {
            document.getElementById('detailBody').innerHTML = `<tr><td colspan="4" class="text-center py-4 text-gray-400">No entries.</td></tr>`;
            return;
          }
          document.getElementById('detailBody').innerHTML = data.rows.map(r => `
            <tr class="border-t border-gray-50">
              <td class="py-1">${formatDate(r.date)}</td>
              <td class="py-1">${r.purpose || '-'}</td>
              <td class="py-1">${TYPE_LABEL[r.related_type] || r.related_type}</td>
              <td class="py-1 text-right">${fmt(r.amount)}</td>
            </tr>
          `).join('');
        } catch (err) {
          document.getElementById('detailBody').innerHTML = `<tr><td colspan="4" class="text-center py-4 text-red-400">Error loading detail.</td></tr>`;
        }
      }

      function fmt(n) { return Number(n).toLocaleString('en-BD', { minimumFractionDigits: 2 }); }
      function formatDate(d) {
        if (!d) return '-';
        const dt = new Date(d);
        return isNaN(dt) ? d : dt.toLocaleDateString('en-GB');
      }

      async function runExport(type, scope) {
        const params = buildParams('export', scope === 'all');
        document.getElementById('exportMenu').classList.add('hidden');

        try {
          const res = await fetch(`${API}?${params.toString()}`);
          const data = await res.json();
          if (!data.success) { alert('Export failed.'); return; }

          const rows = data.rows.map(r => ({
            Client: r.user_name,
            'Total Sale': Number(r.total_sale).toFixed(2),
            'Total Receive': Number(r.total_receive).toFixed(2),
            'Discount/Refund': Number(r.total_discount).toFixed(2),
            Receivable: Number(r.receivable_balance).toFixed(2),
            'Last Activity': formatDate(r.last_activity_date),
          }));

          const filenameBase = `receivable-report-${scope}-${new Date().toISOString().slice(0,10)}`;
          if (type === 'csv') exportCSV(rows, filenameBase);
          else if (type === 'xlsx') exportXLSX(rows, filenameBase);
          else if (type === 'pdf') exportPDF(rows, filenameBase, data.summary);
        } catch (err) {
          console.error(err);
          alert('Export failed.');
        }
      }

      function exportCSV(rows, filenameBase) {
        const ws = XLSX.utils.json_to_sheet(rows);
        const csv = XLSX.utils.sheet_to_csv(ws);
        downloadBlob(new Blob([csv], { type: 'text/csv;charset=utf-8;' }), `${filenameBase}.csv`);
      }
      function exportXLSX(rows, filenameBase) {
        const ws = XLSX.utils.json_to_sheet(rows);
        const wb = XLSX.utils.book_new();
        XLSX.utils.book_append_sheet(wb, ws, 'Receivable');
        XLSX.writeFile(wb, `${filenameBase}.xlsx`);
      }
      function exportPDF(rows, filenameBase, summary) {
        const { jsPDF } = window.jspdf;
        const doc = new jsPDF({ orientation: 'landscape' });
        doc.setFontSize(14);
        doc.text('Receivable Report', 14, 15);
        doc.setFontSize(9);
        doc.text(`Clients: ${summary.total_clients}   Total Receivable: ${Number(summary.total_receivable).toFixed(2)}`, 14, 21);
        doc.autoTable({
          startY: 26,
          head: [['Client', 'Total Sale', 'Total Receive', 'Discount/Refund', 'Receivable', 'Last Activity']],
          body: rows.map(r => [r.Client, r['Total Sale'], r['Total Receive'], r['Discount/Refund'], r.Receivable, r['Last Activity']]),
          styles: { fontSize: 8 },
          headStyles: { fillColor: [26, 32, 57] },
        });
        doc.save(`${filenameBase}.pdf`);
      }
      function downloadBlob(blob, filename) {
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url; a.download = filename;
        document.body.appendChild(a); a.click(); a.remove();
        URL.revokeObjectURL(url);
      }

    })();
    </script>

</body>
</html>