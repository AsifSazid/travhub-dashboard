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
    <title>Receive Report | TravHub Global Limited</title>
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

    <!-- Export libs (CDN, no Composer/npm needed) -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.8.2/jspdf.plugin.autotable.min.js"></script>

    <style>body{font-family:'Poppins',sans-serif;}</style>
</head>
<body class="bg-gray-50 text-navy">

    <!-- Top Navigation -->
    <?php include '../elements/header.php'; ?>

    <!-- Sidebar -->
    <?php include '../elements/aside.php'; ?>

    <main id="mainContent" class="pt-16 pl-0 lg:pl-64 lg:my-16 transition-all duration-300 h-full">
        <div class="max-w-7xl mx-auto p-4 md:p-6">

          <!-- Header -->
          <div class="flex flex-wrap items-center justify-between gap-3 mb-6">
            <div>
              <h1 class="text-xl md:text-2xl font-semibold text-navy">Receive Report</h1>
              <p class="text-sm text-gray-500">Source: ac_banking_stmts &middot; related_type = receive</p>
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

          <!-- Summary cards -->
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-6">
            <div class="bg-white rounded-xl border border-gray-100 p-4">
              <p class="text-xs text-gray-400 mb-1">Total Receives (filtered)</p>
              <p id="sumCount" class="text-2xl font-semibold text-navy">0</p>
            </div>
            <div class="bg-white rounded-xl border border-gray-100 p-4">
              <p class="text-xs text-gray-400 mb-1">Total Amount (filtered)</p>
              <p id="sumAmount" class="text-2xl font-semibold text-green">৳ 0.00</p>
            </div>
          </div>

          <!-- Basic filters -->
          <div class="bg-white rounded-xl border border-gray-100 p-4 mb-3">
            <div class="grid grid-cols-1 md:grid-cols-5 gap-3">
              <div>
                <label class="text-xs text-gray-500">From Date</label>
                <input type="date" id="f_date_from" class="w-full mt-1 border border-gray-200 rounded-lg px-3 py-2 text-sm">
              </div>
              <div>
                <label class="text-xs text-gray-500">To Date</label>
                <input type="date" id="f_date_to" class="w-full mt-1 border border-gray-200 rounded-lg px-3 py-2 text-sm">
              </div>
              <div class="md:col-span-2">
                <label class="text-xs text-gray-500">Search (particular / ref / sys_id)</label>
                <input type="text" id="f_search" placeholder="Search..." class="w-full mt-1 border border-gray-200 rounded-lg px-3 py-2 text-sm">
              </div>
              <div class="flex items-end gap-2">
                <button id="applyFilter" class="flex-1 bg-green text-white rounded-lg px-3 py-2 text-sm font-medium hover:bg-green/90">Apply</button>
                <button id="toggleAdvanced" class="flex-1 border border-navy text-navy rounded-lg px-3 py-2 text-sm font-medium hover:bg-navy/5">Advanced</button>
              </div>
            </div>

            <!-- Advanced filters -->
            <div id="advancedPanel" class="hidden mt-4 pt-4 border-t border-gray-100 grid grid-cols-1 md:grid-cols-4 gap-3">
              <div>
                <label class="text-xs text-gray-500">Bank Account</label>
                <select id="f_bank" multiple class="w-full mt-1 border border-gray-200 rounded-lg px-3 py-2 text-sm h-24"></select>
              </div>
              <div>
                <label class="text-xs text-gray-500">Payment Method</label>
                <select id="f_method" multiple class="w-full mt-1 border border-gray-200 rounded-lg px-3 py-2 text-sm h-24"></select>
              </div>
              <div>
                <label class="text-xs text-gray-500">Amount Min</label>
                <input type="number" id="f_amount_min" placeholder="0" class="w-full mt-1 border border-gray-200 rounded-lg px-3 py-2 text-sm">
                <label class="text-xs text-gray-500 mt-2 block">Amount Max</label>
                <input type="number" id="f_amount_max" placeholder="0" class="w-full mt-1 border border-gray-200 rounded-lg px-3 py-2 text-sm">
              </div>
              <div>
                <label class="text-xs text-gray-500">Reconciliation Status</label>
                <select id="f_reconsilation" class="w-full mt-1 border border-gray-200 rounded-lg px-3 py-2 text-sm">
                  <option value="">All</option>
                  <option value="1">Reconciled</option>
                  <option value="0">Not Reconciled</option>
                </select>
                <button id="clearFilter" class="w-full mt-3 border border-gray-300 text-gray-500 rounded-lg px-3 py-2 text-sm hover:bg-gray-50">Clear All Filters</button>
              </div>
            </div>
          </div>

          <!-- Table -->
          <div class="bg-white rounded-xl border border-gray-100 overflow-x-auto">
            <table class="w-full text-sm">
              <thead class="bg-navy text-white">
                <tr>
                  <th class="px-3 py-2 text-left">Date</th>
                  <th class="px-3 py-2 text-left">Bank</th>
                  <th class="px-3 py-2 text-left">Method</th>
                  <th class="px-3 py-2 text-left">Particular</th>
                  <th class="px-3 py-2 text-left">Ref</th>
                  <th class="px-3 py-2 text-right">Amount</th>
                  <th class="px-3 py-2 text-center">Reconciled</th>
                </tr>
              </thead>
              <tbody id="tableBody">
                <tr><td colspan="8" class="text-center py-6 text-gray-400">Loading...</td></tr>
              </tbody>
            </table>
          </div>

          <!-- Pagination -->
          <div class="flex items-center justify-between mt-4">
            <p id="pageInfo" class="text-xs text-gray-500">-</p>
            <div class="flex gap-2">
              <button id="prevPage" class="px-3 py-1.5 text-sm border border-gray-200 rounded-lg hover:bg-gray-50">Prev</button>
              <button id="nextPage" class="px-3 py-1.5 text-sm border border-gray-200 rounded-lg hover:bg-gray-50">Next</button>
            </div>
          </div>

        </div>
    </main>
    
    <script src="../assets/js/script.js?time=<?php echo time(); ?>"></script>

    <script>
    (function () {
      'use strict';

      const API = '/api/reports/receive/endpoints.php';

      let state = { page: 1, per_page: 25, pages: 1 };

      document.addEventListener('DOMContentLoaded', () => {
        loadFilterOptions();
        fetchData();
        bindEvents();
      });

      function bindEvents() {
        document.getElementById('applyFilter').addEventListener('click', () => {
          state.page = 1;
          fetchData();
        });

        document.getElementById('clearFilter').addEventListener('click', () => {
          document.querySelectorAll('#advancedPanel input, #advancedPanel select').forEach(el => {
            if (el.tagName === 'SELECT') {
              [...el.options].forEach(o => o.selected = false);
            } else {
              el.value = '';
            }
          });
          document.getElementById('f_date_from').value = '';
          document.getElementById('f_date_to').value = '';
          document.getElementById('f_search').value = '';
          state.page = 1;
          fetchData();
        });

        document.getElementById('toggleAdvanced').addEventListener('click', () => {
          document.getElementById('advancedPanel').classList.toggle('hidden');
        });

        document.getElementById('prevPage').addEventListener('click', () => {
          if (state.page > 1) { state.page--; fetchData(); }
        });
        document.getElementById('nextPage').addEventListener('click', () => {
          if (state.page < state.pages) { state.page++; fetchData(); }
        });

        document.getElementById('exportBtn').addEventListener('click', (e) => {
          e.stopPropagation();
          document.getElementById('exportMenu').classList.toggle('hidden');
        });
        document.addEventListener('click', () => {
          document.getElementById('exportMenu').classList.add('hidden');
        });

        document.querySelectorAll('.export-action').forEach(btn => {
          btn.addEventListener('click', (e) => {
            e.stopPropagation();
            runExport(btn.dataset.type, btn.dataset.scope);
          });
        });
      }

      async function loadFilterOptions() {
        try {
          const res = await fetch(`${API}?action=filters`);
          const data = await res.json();
          if (!data.success) return;

          document.getElementById('f_bank').innerHTML = data.banks.map(b =>
            `<option value="${b.sys_id}">${b.acc_name}</option>`
          ).join('');

          document.getElementById('f_method').innerHTML = data.methods.map(m =>
            `<option value="${m.value}">${m.label}</option>`
          ).join('');
        } catch (err) {
          console.error('Failed to load filter options', err);
        }
      }

      function buildParams(action, scopeAll) {
        const p = new URLSearchParams();
        p.append('action', action);

        if (!scopeAll) {
          const dateFrom = document.getElementById('f_date_from').value;
          const dateTo   = document.getElementById('f_date_to').value;
          const search   = document.getElementById('f_search').value;
          const amountMin = document.getElementById('f_amount_min').value;
          const amountMax = document.getElementById('f_amount_max').value;
          const reconsilation = document.getElementById('f_reconsilation').value;

          if (dateFrom) p.append('date_from', dateFrom);
          if (dateTo) p.append('date_to', dateTo);
          if (search) p.append('search', search);
          if (amountMin) p.append('amount_min', amountMin);
          if (amountMax) p.append('amount_max', amountMax);
          if (reconsilation !== '') p.append('reconsilation', reconsilation);

          [...document.getElementById('f_bank').selectedOptions].forEach(o => p.append('bank[]', o.value));
          [...document.getElementById('f_method').selectedOptions].forEach(o => p.append('method[]', o.value));
        }

        if (action === 'list') {
          p.append('page', state.page);
          p.append('per_page', state.per_page);
        }

        return p;
      }

      async function fetchData() {
        const params = buildParams('list', false);
        document.getElementById('tableBody').innerHTML =
          `<tr><td colspan="8" class="text-center py-6 text-gray-400">Loading...</td></tr>`;

        try {
          const res = await fetch(`${API}?${params.toString()}`);
          const data = await res.json();

          if (!data.success) {
            document.getElementById('tableBody').innerHTML =
              `<tr><td colspan="8" class="text-center py-6 text-red-400">Failed to load data.</td></tr>`;
            return;
          }

          renderTable(data.rows);
          renderSummary(data.summary);

          state.pages = data.pages || 1;
          document.getElementById('pageInfo').textContent =
            `Page ${data.page} of ${data.pages} — ${data.total} total records`;
        } catch (err) {
          console.error(err);
          document.getElementById('tableBody').innerHTML =
            `<tr><td colspan="8" class="text-center py-6 text-red-400">Error loading report.</td></tr>`;
        }
      }

      function renderSummary(summary) {
        document.getElementById('sumCount').textContent = summary.total_count;
        document.getElementById('sumAmount').textContent =
          '৳ ' + Number(summary.total_amount).toLocaleString('en-BD', { minimumFractionDigits: 2 });
      }

      function renderTable(rows) {
        const tbody = document.getElementById('tableBody');
        if (!rows.length) {
          tbody.innerHTML = `<tr><td colspan="8" class="text-center py-6 text-gray-400">No receives found.</td></tr>`;
          return;
        }
        tbody.innerHTML = rows.map(r => `
          <tr class="border-t border-gray-100 hover:bg-gray-50">
            <td class="px-3 py-2">${formatDate(r.date)}</td>
            <td class="px-3 py-2">${r.bank_name || '-'}</td>
            <td class="px-3 py-2 capitalize">${r.transfer_method || '-'}</td>
            <td class="px-3 py-2">${r.particular || '-'}</td>
            <td class="px-3 py-2 text-xs text-gray-500">${r.ref || '-'}</td>
            <td class="px-3 py-2 text-right font-medium">${Number(r.amount).toLocaleString('en-BD', {minimumFractionDigits:2})}</td>
            <td class="px-3 py-2 text-center">
              ${r.reconsilation ? '<span class="text-green text-xs font-semibold">YES</span>' : '<span class="text-gray-300 text-xs">NO</span>'}
            </td>
          </tr>
        `).join('');
      }

      function formatDate(d) {
        if (!d) return '-';
        const dt = new Date(d);
        if (isNaN(dt)) return d;
        return dt.toLocaleDateString('en-GB');
      }

      async function runExport(type, scope) {
        const params = buildParams('export', scope === 'all');
        document.getElementById('exportMenu').classList.add('hidden');

        try {
          const res = await fetch(`${API}?${params.toString()}`);
          const data = await res.json();
          if (!data.success) { alert('Export failed: could not fetch data.'); return; }

          const rows = data.rows.map(r => ({
            Date: formatDate(r.date),
            'Sys ID': r.sys_id,
            Bank: r.bank_name || '',
            Method: r.transfer_method || '',
            Particular: r.particular || '',
            Ref: r.ref || '',
            Amount: Number(r.amount).toFixed(2),
            Reconciled: r.reconsilation ? 'Yes' : 'No',
          }));

          const filenameBase = `receive-report-${scope}-${new Date().toISOString().slice(0,10)}`;

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
        const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
        downloadBlob(blob, `${filenameBase}.csv`);
      }

      function exportXLSX(rows, filenameBase) {
        const ws = XLSX.utils.json_to_sheet(rows);
        const wb = XLSX.utils.book_new();
        XLSX.utils.book_append_sheet(wb, ws, 'Receive');
        XLSX.writeFile(wb, `${filenameBase}.xlsx`);
      }

      function exportPDF(rows, filenameBase, summary) {
        const { jsPDF } = window.jspdf;
        const doc = new jsPDF({ orientation: 'landscape' });

        doc.setFontSize(14);
        doc.text('Receive Report', 14, 15);
        doc.setFontSize(9);
        doc.text(`Total Records: ${summary.total_count}   Total Amount: ${Number(summary.total_amount).toFixed(2)}`, 14, 21);

        doc.autoTable({
          startY: 26,
          head: [['Date', 'Sys ID', 'Bank', 'Method', 'Particular', 'Ref', 'Amount', 'Reconciled']],
          body: rows.map(r => [r.Date, r['Sys ID'], r.Bank, r.Method, r.Particular, r.Ref, r.Amount, r.Reconciled]),
          styles: { fontSize: 7 },
          headStyles: { fillColor: [26, 32, 57] },
        });

        doc.save(`${filenameBase}.pdf`);
      }

      function downloadBlob(blob, filename) {
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = filename;
        document.body.appendChild(a);
        a.click();
        a.remove();
        URL.revokeObjectURL(url);
      }

    })();
    </script>

</body>
</html>