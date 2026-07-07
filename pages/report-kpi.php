<?php
// pages/report-kpi.php

include_once('./authenticate.php');
$ip_port = trim(@file_get_contents('../ippath.txt') ?: 'http://103.104.219.3:898', '/');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employee KPI — TravHub</title>
    <link rel="icon" type="image/png" href="../assets/images/logo/round-logo.png">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.8.2/jspdf.plugin.autotable.min.js"></script>
    <style>
        .tab-btn.active {
            border-bottom-color: #4f46e5;
            color: #4f46e5;
        }
        .tab-btn {
            border-bottom: 2px solid transparent;
            padding: 0.75rem 1.25rem;
            font-size: 0.875rem;
            font-weight: 500;
            color: #6b7280;
            transition: all 0.2s;
        }
        .tab-btn:hover {
            color: #374151;
        }
        .kpi-table-container {
            max-height: 500px;
            overflow-y: auto;
        }
        .kpi-table-container thead {
            position: sticky;
            top: 0;
            z-index: 10;
        }
    </style>
</head>
<body class="bg-gray-50">
<?php include '../elements/header.php'; ?>
<?php include '../elements/aside.php'; ?>
<?php include '../elements/preview-model.php'; ?>

<main id="mainContent" class="pt-16 pl-64 transition-all duration-300">
<div class="p-6">

    <!-- Header -->
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-3">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">
                <i class="fas fa-chart-bar text-indigo-600 mr-2"></i>Employee KPI
            </h1>
            <p class="text-sm text-gray-500 mt-1">Three views: Created By | Owned By | Performed By</p>
        </div>
        <div class="relative" id="exportDropdownWrap">
            <button onclick="toggleExportMenu()"
                class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-sm font-medium flex items-center gap-2">
                <i class="fas fa-download"></i> Export <i class="fas fa-chevron-down ml-1 text-xs"></i>
            </button>
            <div id="exportMenu" class="hidden absolute right-0 mt-1 w-44 bg-white border border-gray-200 rounded-xl shadow-lg z-50">
                <button onclick="runExport('csv')"  class="w-full text-left px-4 py-2.5 text-sm hover:bg-gray-50 flex items-center gap-2">
                    <i class="fas fa-file-csv text-green-600"></i> CSV
                </button>
                <button onclick="runExport('excel')" class="w-full text-left px-4 py-2.5 text-sm hover:bg-gray-50 flex items-center gap-2">
                    <i class="fas fa-file-excel text-green-700"></i> Excel (.xlsx)
                </button>
                <button onclick="runExport('pdf')"  class="w-full text-left px-4 py-2.5 text-sm hover:bg-gray-50 flex items-center gap-2">
                    <i class="fas fa-file-pdf text-red-600"></i> PDF
                </button>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-xl border border-gray-200 p-4 mb-6">
        <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-3">
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">Date From</label>
                <input type="date" id="f-date-from" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">Date To</label>
                <input type="date" id="f-date-to" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">Quick Month</label>
                <input type="month" id="f-month" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">Employee</label>
                <select id="f-employee" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                    <option value="">All Employees</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">Work Status</label>
                <select id="f-work-status" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                    <option value="">All</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">Task Status</label>
                <select id="f-task-status" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                    <option value="">All</option>
                </select>
            </div>
        </div>
        <div class="flex gap-2 mt-3">
            <button onclick="applyFilters()" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-sm font-medium">
                <i class="fas fa-search mr-1"></i> Apply
            </button>
            <button onclick="clearFilters()" class="px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 rounded-lg text-sm font-medium">
                <i class="fas fa-redo mr-1"></i> Clear
            </button>
        </div>
        <!-- Active filter bar -->
        <div id="activeFilterBar" class="hidden mt-3 pt-3 border-t flex flex-wrap gap-2 items-center text-xs">
            <span class="text-gray-400">Active filters:</span>
            <div id="filterTags" class="flex flex-wrap gap-1.5"></div>
            <button onclick="clearFilters()" class="text-red-400 hover:text-red-600 ml-1">
                <i class="fas fa-times-circle mr-0.5"></i>Clear all
            </button>
        </div>
    </div>

    <!-- Tab Navigation -->
    <div class="bg-white rounded-t-xl border border-b-0 border-gray-200">
        <div class="flex overflow-x-auto">
            <button onclick="switchTab('created')" id="tab-created" class="tab-btn active">
                <i class="fas fa-user-plus mr-2"></i>Created By
            </button>
            <button onclick="switchTab('owned')" id="tab-owned" class="tab-btn">
                <i class="fas fa-user-tie mr-2"></i>Owned By
            </button>
            <button onclick="switchTab('performed')" id="tab-performed" class="tab-btn">
                <i class="fas fa-user-check mr-2"></i>Performed By
            </button>
        </div>
    </div>

    <!-- Tab Content -->
    <div class="bg-white rounded-b-xl border border-gray-200 mb-6">
        <!-- Created By Tab -->
        <div id="panel-created" class="tab-panel">
            <!-- Summary Cards -->
            <div class="grid grid-cols-2 md:grid-cols-5 gap-4 p-4 border-b">
                <div class="bg-gray-50 rounded-lg p-3">
                    <p class="text-xs text-gray-500">Employees</p>
                    <p id="c-s-emp" class="text-xl font-bold text-indigo-700">—</p>
                </div>
                <div class="bg-gray-50 rounded-lg p-3">
                    <p class="text-xs text-gray-500">Works Created</p>
                    <p id="c-s-works" class="text-xl font-bold text-blue-700">—</p>
                </div>
                <div class="bg-gray-50 rounded-lg p-3">
                    <p class="text-xs text-gray-500">Tasks Created</p>
                    <p id="c-s-tasks" class="text-xl font-bold text-purple-700">—</p>
                </div>
                <div class="bg-gray-50 rounded-lg p-3">
                    <p class="text-xs text-gray-500">Works Completed</p>
                    <p id="c-s-done-works" class="text-xl font-bold text-green-700">—</p>
                </div>
                <div class="bg-gray-50 rounded-lg p-3">
                    <p class="text-xs text-gray-500">Tasks Completed</p>
                    <p id="c-s-done-tasks" class="text-xl font-bold text-emerald-700">—</p>
                </div>
            </div>
            <!-- Table -->
            <div class="kpi-table-container">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 text-xs text-gray-500 uppercase">
                        <tr>
                            <th class="px-4 py-3 text-left">Employee</th>
                            <th class="px-4 py-3 text-left">ID</th>
                            <th class="px-4 py-3 text-center" colspan="4">Works Created</th>
                            <th class="px-4 py-3 text-center" colspan="4">Tasks Created</th>
                            <th class="px-4 py-3 text-center">Total</th>
                            <th class="px-4 py-3 text-center">Completion</th>
                            <th class="px-4 py-3 text-center">Details</th>
                        </tr>
                        <tr class="bg-gray-100 text-xs text-gray-400">
                            <th colspan="2"></th>
                            <th class="px-3 py-1.5 text-center">Total</th>
                            <th class="px-3 py-1.5 text-center text-green-600">Done</th>
                            <th class="px-3 py-1.5 text-center text-blue-600">In Progress</th>
                            <th class="px-3 py-1.5 text-center text-orange-500">Pending</th>
                            <th class="px-3 py-1.5 text-center">Total</th>
                            <th class="px-3 py-1.5 text-center text-green-600">Done</th>
                            <th class="px-3 py-1.5 text-center text-blue-600">In Progress</th>
                            <th class="px-3 py-1.5 text-center text-orange-500">Pending</th>
                            <th colspan="2"></th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody id="c-kpi-tbody" class="divide-y divide-gray-100">
                        <tr><td colspan="13" class="px-4 py-8 text-center text-gray-400">Loading...</td></tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Owned By Tab -->
        <div id="panel-owned" class="tab-panel hidden">
            <!-- Summary Cards -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 p-4 border-b">
                <div class="bg-gray-50 rounded-lg p-3">
                    <p class="text-xs text-gray-500">Employees</p>
                    <p id="o-s-emp" class="text-xl font-bold text-indigo-700">—</p>
                </div>
                <div class="bg-gray-50 rounded-lg p-3">
                    <p class="text-xs text-gray-500">Works Owned</p>
                    <p id="o-s-works" class="text-xl font-bold text-blue-700">—</p>
                </div>
                <div class="bg-gray-50 rounded-lg p-3">
                    <p class="text-xs text-gray-500">Works Completed</p>
                    <p id="o-s-done-works" class="text-xl font-bold text-green-700">—</p>
                </div>
                <div class="bg-gray-50 rounded-lg p-3">
                    <p class="text-xs text-gray-500">Works In Progress</p>
                    <p id="o-s-progress-works" class="text-xl font-bold text-blue-600">—</p>
                </div>
            </div>
            <!-- Table -->
            <div class="kpi-table-container">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 text-xs text-gray-500 uppercase">
                        <tr>
                            <th class="px-4 py-3 text-left">Employee</th>
                            <th class="px-4 py-3 text-left">ID</th>
                            <th class="px-4 py-3 text-center">Total Works</th>
                            <th class="px-4 py-3 text-center text-green-600">Completed</th>
                            <th class="px-4 py-3 text-center text-blue-600">In Progress</th>
                            <th class="px-4 py-3 text-center text-orange-500">Pending</th>
                            <th class="px-4 py-3 text-center">No Status</th>
                            <th class="px-4 py-3 text-center">Completion %</th>
                            <th class="px-4 py-3 text-center">Details</th>
                        </tr>
                    </thead>
                    <tbody id="o-kpi-tbody" class="divide-y divide-gray-100">
                        <tr><td colspan="9" class="px-4 py-8 text-center text-gray-400">Loading...</td></tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Performed By Tab -->
        <div id="panel-performed" class="tab-panel hidden">
            <!-- Summary Cards -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 p-4 border-b">
                <div class="bg-gray-50 rounded-lg p-3">
                    <p class="text-xs text-gray-500">Employees</p>
                    <p id="p-s-emp" class="text-xl font-bold text-indigo-700">—</p>
                </div>
                <div class="bg-gray-50 rounded-lg p-3">
                    <p class="text-xs text-gray-500">Tasks Performed</p>
                    <p id="p-s-tasks" class="text-xl font-bold text-purple-700">—</p>
                </div>
                <div class="bg-gray-50 rounded-lg p-3">
                    <p class="text-xs text-gray-500">Tasks Completed</p>
                    <p id="p-s-done-tasks" class="text-xl font-bold text-green-700">—</p>
                </div>
                <div class="bg-gray-50 rounded-lg p-3">
                    <p class="text-xs text-gray-500">Tasks In Progress</p>
                    <p id="p-s-progress-tasks" class="text-xl font-bold text-blue-600">—</p>
                </div>
            </div>
            <!-- Table -->
            <div class="kpi-table-container">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 text-xs text-gray-500 uppercase">
                        <tr>
                            <th class="px-4 py-3 text-left">Employee</th>
                            <th class="px-4 py-3 text-left">ID</th>
                            <th class="px-4 py-3 text-center">Total Tasks</th>
                            <th class="px-4 py-3 text-center text-green-600">Completed</th>
                            <th class="px-4 py-3 text-center text-blue-600">In Progress</th>
                            <th class="px-4 py-3 text-center text-orange-500">Pending</th>
                            <th class="px-4 py-3 text-center">No Status</th>
                            <th class="px-4 py-3 text-center">Completion %</th>
                            <th class="px-4 py-3 text-center">Details</th>
                        </tr>
                    </thead>
                    <tbody id="p-kpi-tbody" class="divide-y divide-gray-100">
                        <tr><td colspan="9" class="px-4 py-8 text-center text-gray-400">Loading...</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Detail Panel — per employee -->
    <div id="detailPanel" class="hidden">
        <div class="bg-white rounded-xl border border-gray-200">
            <div class="p-4 border-b flex justify-between items-center">
                <h3 class="font-semibold text-gray-800">
                    <i class="fas fa-user text-indigo-500 mr-2"></i>
                    Detail: <span id="detailEmpName" class="text-indigo-700"></span>
                </h3>
                <button onclick="closeDetail()" class="text-gray-400 hover:text-gray-600 text-sm">
                    <i class="fas fa-times mr-1"></i>Close
                </button>
            </div>
            <!-- Tabs -->
            <div class="flex border-b">
                <button onclick="setDetailTab('works')" id="dtab-works"
                    class="px-5 py-3 text-sm font-medium border-b-2 border-indigo-500 text-indigo-700">
                    <i class="fas fa-briefcase mr-1"></i> Works Created
                </button>
                <button onclick="setDetailTab('tasks')" id="dtab-tasks"
                    class="px-5 py-3 text-sm font-medium border-b-2 border-transparent text-gray-500 hover:text-gray-700">
                    <i class="fas fa-tasks mr-1"></i> Tasks Created
                </button>
            </div>

            <!-- Works tab -->
            <div id="dpanel-works" class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 text-xs text-gray-500 uppercase">
                        <tr>
                            <th class="px-4 py-3 text-left">Sys ID</th>
                            <th class="px-4 py-3 text-left">Title</th>
                            <th class="px-4 py-3 text-left">Client</th>
                            <th class="px-4 py-3 text-left">Status</th>
                            <th class="px-4 py-3 text-left">Created</th>
                            <th class="px-4 py-3 text-left">Owned By</th>
                        </tr>
                    </thead>
                    <tbody id="works-tbody" class="divide-y divide-gray-100"></tbody>
                </table>
            </div>

            <!-- Tasks tab -->
            <div id="dpanel-tasks" class="hidden overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 text-xs text-gray-500 uppercase">
                        <tr>
                            <th class="px-4 py-3 text-left">Sys ID</th>
                            <th class="px-4 py-3 text-left">Title</th>
                            <th class="px-4 py-3 text-left">Work</th>
                            <th class="px-4 py-3 text-left">Category</th>
                            <th class="px-4 py-3 text-left">Status</th>
                            <th class="px-4 py-3 text-left">Created</th>
                            <th class="px-4 py-3 text-left">Performed By</th>
                        </tr>
                    </thead>
                    <tbody id="tasks-tbody" class="divide-y divide-gray-100"></tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Loading -->
    <div id="loading" class="hidden fixed inset-0 bg-white/70 flex items-center justify-center z-50">
        <div class="text-center">
            <i class="fas fa-spinner fa-spin text-3xl text-indigo-600"></i>
            <p class="mt-2 text-gray-600 text-sm">Loading...</p>
        </div>
    </div>

</div>
</main>

<script>
const IP  = '<?php echo $ip_port; ?>';
const API = `${IP}/api/reports/kpi/endpoints.php`;

let kpiData = {
    created: { employees: [], totals: {} },
    owned: { employees: [], totals: {} },
    performed: { employees: [], totals: {} }
};
let currentTab = 'created';

const statusBadge = (s) => {
    if (!s) return '<span class="px-2 py-0.5 rounded text-xs bg-gray-100 text-gray-500">—</span>';
    const map = {
        completed:   'bg-green-100 text-green-700',
        in_progress: 'bg-blue-100 text-blue-700',
        inprogress:  'bg-blue-100 text-blue-700',
        pending:     'bg-orange-100 text-orange-700',
    };
    const cls = map[s.toLowerCase()] || 'bg-gray-100 text-gray-600';
    return `<span class="px-2 py-0.5 rounded text-xs font-medium ${cls}">${s}</span>`;
};

const progressBar = (pct) => {
    const cls = pct >= 80 ? 'bg-green-500' : pct >= 50 ? 'bg-blue-500' : 'bg-orange-400';
    return `<div class="flex items-center gap-2">
        <div class="flex-1 bg-gray-200 rounded-full h-1.5 min-w-[60px]">
            <div class="${cls} h-1.5 rounded-full" style="width:${pct}%"></div>
        </div>
        <span class="text-xs font-semibold text-gray-700 w-10 text-right">${pct}%</span>
    </div>`;
};

document.addEventListener('DOMContentLoaded', () => {
    loadFilters();
    loadData();

    // Month quick select
    document.getElementById('f-month').addEventListener('change', function() {
        if (!this.value) return;
        const [y, m] = this.value.split('-');
        const last = new Date(y, m, 0).getDate();
        document.getElementById('f-date-from').value = `${y}-${m}-01`;
        document.getElementById('f-date-to').value   = `${y}-${m}-${String(last).padStart(2,'0')}`;
    });

    // Export dropdown close
    document.addEventListener('click', e => {
        if (!document.getElementById('exportDropdownWrap')?.contains(e.target))
            document.getElementById('exportMenu')?.classList.add('hidden');
    });
});

async function loadFilters() {
    const r = await fetch(`${API}?action=filters`);
    const d = await r.json();
    if (!d.success) return;

    const empSel = document.getElementById('f-employee');
    (d.employees||[]).forEach(e => {
        const o = document.createElement('option');
        o.value = e.id; o.textContent = `${e.name} (${e.id})`;
        empSel.appendChild(o);
    });

    const wsSel = document.getElementById('f-work-status');
    (d.work_statuses||[]).forEach(s => {
        const o = document.createElement('option');
        o.value = s; o.textContent = s; wsSel.appendChild(o);
    });

    const tsSel = document.getElementById('f-task-status');
    (d.task_statuses||[]).forEach(s => {
        const o = document.createElement('option');
        o.value = s; o.textContent = s; tsSel.appendChild(o);
    });
}

function buildParams() {
    const p = new URLSearchParams();
    const from = document.getElementById('f-date-from').value;
    const to   = document.getElementById('f-date-to').value;
    const emp  = document.getElementById('f-employee').value;
    const ws   = document.getElementById('f-work-status').value;
    const ts   = document.getElementById('f-task-status').value;
    if (from) p.set('date_from', from);
    if (to)   p.set('date_to', to);
    if (emp)  p.set('employee_id', emp);
    if (ws)   p.set('work_status', ws);
    if (ts)   p.set('task_status', ts);
    return p.toString();
}

function setLoading(v) { document.getElementById('loading').classList.toggle('hidden', !v); }

async function loadData() {
    setLoading(true);
    try {
        const r = await fetch(`${API}?action=summary&${buildParams()}`);
        const d = await r.json();
        if (!d.success) return;
        
        kpiData.created = d.created_by || { employees: [], totals: {} };
        kpiData.owned = d.owned_by || { employees: [], totals: {} };
        kpiData.performed = d.performed_by || { employees: [], totals: {} };
        
        renderAllTabs();
        updateFilterBar();
    } finally {
        setLoading(false);
    }
}

function renderAllTabs() {
    renderCreatedTab();
    renderOwnedTab();
    renderPerformedTab();
}

function renderCreatedTab() {
    const data = kpiData.created;
    const totals = data.totals || {};
    
    document.getElementById('c-s-emp').textContent = totals.total_employees || 0;
    document.getElementById('c-s-works').textContent = totals.total_works || 0;
    document.getElementById('c-s-tasks').textContent = totals.total_tasks || 0;
    document.getElementById('c-s-done-works').textContent = totals.completed_works || 0;
    document.getElementById('c-s-done-tasks').textContent = totals.completed_tasks || 0;

    const tb = document.getElementById('c-kpi-tbody');
    if (!data.employees || !data.employees.length) {
        tb.innerHTML = '<tr><td colspan="13" class="px-4 py-8 text-center text-gray-400">No data found</td></tr>';
        return;
    }

    tb.innerHTML = data.employees.map(e => `
        <tr class="hover:bg-gray-50 transition-colors">
            <td class="px-4 py-3 font-semibold text-gray-800">${e.emp_name}</td>
            <td class="px-4 py-3 text-xs text-gray-400 font-mono">${e.emp_id}</td>
            <!-- Works -->
            <td class="px-3 py-3 text-center font-bold text-blue-700">${e.total_works}</td>
            <td class="px-3 py-3 text-center text-green-700">${e.completed_works}</td>
            <td class="px-3 py-3 text-center text-blue-600">${e.inprogress_works}</td>
            <td class="px-3 py-3 text-center text-orange-500">${e.pending_works}</td>
            <!-- Tasks -->
            <td class="px-3 py-3 text-center font-bold text-purple-700">${e.total_tasks}</td>
            <td class="px-3 py-3 text-center text-green-700">${e.completed_tasks}</td>
            <td class="px-3 py-3 text-center text-blue-600">${e.inprogress_tasks}</td>
            <td class="px-3 py-3 text-center text-orange-500">${e.pending_tasks}</td>
            <!-- Total + completion -->
            <td class="px-3 py-3 text-center font-bold text-gray-800">${e.total_items}</td>
            <td class="px-4 py-3 min-w-[140px]">${progressBar(e.completion_rate)}</td>
            <td class="px-4 py-3 text-center">
                <button onclick="loadDetail('${e.emp_id}','${e.emp_name.replace(/'/g,"\\\'")}')"
                    class="px-3 py-1 text-xs bg-indigo-50 text-indigo-700 border border-indigo-200 rounded-lg hover:bg-indigo-100">
                    <i class="fas fa-eye mr-1"></i>View
                </button>
            </td>
        </tr>`).join('');
}

function renderOwnedTab() {
    const data = kpiData.owned;
    const totals = data.totals || {};
    
    document.getElementById('o-s-emp').textContent = totals.total_employees || 0;
    document.getElementById('o-s-works').textContent = totals.total_works || 0;
    document.getElementById('o-s-done-works').textContent = totals.completed_works || 0;
    document.getElementById('o-s-progress-works').textContent = (totals.total_works || 0) - (totals.completed_works || 0);

    const tb = document.getElementById('o-kpi-tbody');
    if (!data.employees || !data.employees.length) {
        tb.innerHTML = '<tr><td colspan="9" class="px-4 py-8 text-center text-gray-400">No data found</td></tr>';
        return;
    }

    tb.innerHTML = data.employees.map(e => {
        const total = e.total_works || 0;
        const done = e.completed_works || 0;
        const pct = total > 0 ? Math.round((done / total) * 100) : 0;
        return `
        <tr class="hover:bg-gray-50 transition-colors">
            <td class="px-4 py-3 font-semibold text-gray-800">${e.emp_name}</td>
            <td class="px-4 py-3 text-xs text-gray-400 font-mono">${e.emp_id}</td>
            <td class="px-3 py-3 text-center font-bold text-blue-700">${total}</td>
            <td class="px-3 py-3 text-center text-green-700">${e.completed_works}</td>
            <td class="px-3 py-3 text-center text-blue-600">${e.inprogress_works}</td>
            <td class="px-3 py-3 text-center text-orange-500">${e.pending_works}</td>
            <td class="px-3 py-3 text-center text-gray-400">${e.no_status_works}</td>
            <td class="px-4 py-3 min-w-[140px]">${progressBar(pct)}</td>
            <td class="px-4 py-3 text-center">
                <button onclick="loadDetail('${e.emp_id}','${e.emp_name.replace(/'/g,"\\\'")}')"
                    class="px-3 py-1 text-xs bg-indigo-50 text-indigo-700 border border-indigo-200 rounded-lg hover:bg-indigo-100">
                    <i class="fas fa-eye mr-1"></i>View
                </button>
            </td>
        </tr>`}).join('');
}

function renderPerformedTab() {
    const data = kpiData.performed;
    const totals = data.totals || {};
    
    document.getElementById('p-s-emp').textContent = totals.total_employees || 0;
    document.getElementById('p-s-tasks').textContent = totals.total_tasks || 0;
    document.getElementById('p-s-done-tasks').textContent = totals.completed_tasks || 0;
    document.getElementById('p-s-progress-tasks').textContent = (totals.total_tasks || 0) - (totals.completed_tasks || 0);

    const tb = document.getElementById('p-kpi-tbody');
    if (!data.employees || !data.employees.length) {
        tb.innerHTML = '<tr><td colspan="9" class="px-4 py-8 text-center text-gray-400">No data found</td></tr>';
        return;
    }

    tb.innerHTML = data.employees.map(e => {
        const total = e.total_tasks || 0;
        const done = e.completed_tasks || 0;
        const pct = total > 0 ? Math.round((done / total) * 100) : 0;
        return `
        <tr class="hover:bg-gray-50 transition-colors">
            <td class="px-4 py-3 font-semibold text-gray-800">${e.emp_name}</td>
            <td class="px-4 py-3 text-xs text-gray-400 font-mono">${e.emp_id}</td>
            <td class="px-3 py-3 text-center font-bold text-purple-700">${total}</td>
            <td class="px-3 py-3 text-center text-green-700">${e.completed_tasks}</td>
            <td class="px-3 py-3 text-center text-blue-600">${e.inprogress_tasks}</td>
            <td class="px-3 py-3 text-center text-orange-500">${e.pending_tasks}</td>
            <td class="px-3 py-3 text-center text-gray-400">${e.no_status_tasks}</td>
            <td class="px-4 py-3 min-w-[140px]">${progressBar(pct)}</td>
            <td class="px-4 py-3 text-center">
                <button onclick="loadDetail('${e.emp_id}','${e.emp_name.replace(/'/g,"\\\'")}')"
                    class="px-3 py-1 text-xs bg-indigo-50 text-indigo-700 border border-indigo-200 rounded-lg hover:bg-indigo-100">
                    <i class="fas fa-eye mr-1"></i>View
                </button>
            </td>
        </tr>`}).join('');
}

function switchTab(tab) {
    currentTab = tab;
    document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
    document.getElementById(`tab-${tab}`).classList.add('active');
    document.querySelectorAll('.tab-panel').forEach(p => p.classList.add('hidden'));
    document.getElementById(`panel-${tab}`).classList.remove('hidden');
}

async function loadDetail(empId, empName) {
    document.getElementById('detailPanel').classList.remove('hidden');
    document.getElementById('detailEmpName').textContent = empName;
    document.getElementById('detailPanel').scrollIntoView({ behavior: 'smooth', block: 'start' });

    const r = await fetch(`${API}?action=detail&employee_id=${encodeURIComponent(empId)}`);
    const d = await r.json();
    if (!d.success) return;

    // Works
    const wtb = document.getElementById('works-tbody');
    wtb.innerHTML = (d.works||[]).map(w => `
        <tr class="hover:bg-gray-50">
            <td class="px-4 py-3 text-xs font-mono text-gray-500">${w.sys_id}</td>
            <td class="px-4 py-3 font-medium text-gray-800">${w.title||'—'}</td>
            <td class="px-4 py-3 text-gray-600">${w.client_name||'—'}</td>
            <td class="px-4 py-3">${statusBadge(w.status)}</td>
            <td class="px-4 py-3 text-xs text-gray-400">${w.created_date||'—'}</td>
            <td class="px-4 py-3 text-xs text-gray-600">${w.owned_by||'—'}</td>
        </tr>`).join('') || '<tr><td colspan="6" class="px-4 py-6 text-center text-gray-400">No works created</td></tr>';

    // Tasks
    const ttb = document.getElementById('tasks-tbody');
    ttb.innerHTML = (d.tasks||[]).map(t => `
        <tr class="hover:bg-gray-50">
            <td class="px-4 py-3 text-xs font-mono text-gray-500">${t.sys_id}</td>
            <td class="px-4 py-3 font-medium text-gray-800">${t.title||'—'}</td>
            <td class="px-4 py-3 text-gray-600">${t.client_name||'—'}</td>
            <td class="px-4 py-3 text-xs text-gray-500">${t.category||'—'}</td>
            <td class="px-4 py-3">${statusBadge(t.status)}</td>
            <td class="px-4 py-3 text-xs text-gray-400">${t.created_date||'—'}</td>
            <td class="px-4 py-3 text-xs text-gray-600">${t.performed_by||'—'}</td>
        </tr>`).join('') || '<tr><td colspan="7" class="px-4 py-6 text-center text-gray-400">No tasks created</td></tr>';
}

function closeDetail() {
    document.getElementById('detailPanel').classList.add('hidden');
}

function setDetailTab(tab) {
    ['works','tasks'].forEach(t => {
        document.getElementById(`dpanel-${t}`).classList.toggle('hidden', t !== tab);
        const btn = document.getElementById(`dtab-${t}`);
        btn.className = t === tab
            ? 'px-5 py-3 text-sm font-medium border-b-2 border-indigo-500 text-indigo-700'
            : 'px-5 py-3 text-sm font-medium border-b-2 border-transparent text-gray-500 hover:text-gray-700';
    });
}

function getActiveFilters() {
    const filters = [];
    const from = document.getElementById('f-date-from')?.value;
    const to   = document.getElementById('f-date-to')?.value;
    const month= document.getElementById('f-month')?.value;
    const emp  = document.getElementById('f-employee');
    const ws   = document.getElementById('f-work-status');
    const ts   = document.getElementById('f-task-status');
    if (from) filters.push({ label: 'From', value: from });
    if (to)   filters.push({ label: 'To', value: to });
    if (month)filters.push({ label: 'Month', value: month });
    if (emp?.value)  filters.push({ label: 'Employee', value: emp.options[emp.selectedIndex]?.text });
    if (ws?.value)   filters.push({ label: 'Work Status', value: ws.value });
    if (ts?.value)   filters.push({ label: 'Task Status', value: ts.value });
    return filters;
}

function updateFilterBar() {
    const filters = getActiveFilters();
    const bar  = document.getElementById('activeFilterBar');
    const tags = document.getElementById('filterTags');
    if (!filters.length) { bar.classList.add('hidden'); tags.innerHTML = ''; return; }
    bar.classList.remove('hidden');
    tags.innerHTML = filters.map(f =>
        `<span class="px-2 py-0.5 bg-indigo-50 text-indigo-700 border border-indigo-200 rounded-full">
            <span class="font-medium">${f.label}:</span> ${f.value}
        </span>`
    ).join('');
}

function getFilterSummaryText() {
    const f = getActiveFilters();
    return f.length ? 'Filters: ' + f.map(x => `${x.label}: ${x.value}`).join(' | ') : 'No filters applied';
}

function applyFilters() { loadData(); }

function clearFilters() {
    ['f-date-from','f-date-to','f-month'].forEach(id => document.getElementById(id).value = '');
    ['f-employee','f-work-status','f-task-status'].forEach(id => document.getElementById(id).value = '');
    loadData();
}

function toggleExportMenu() {
    document.getElementById('exportMenu').classList.toggle('hidden');
}

function runExport(type) {
    document.getElementById('exportMenu').classList.add('hidden');
    const filterText = getFilterSummaryText();
    const data = kpiData[currentTab];
    const employees = data.employees || [];
    const isCreated = currentTab === 'created';
    
    let headers, rows;
    if (isCreated) {
        headers = ['Employee', 'Emp ID', 'Total Works', 'Done Works', 'In Progress Works', 'Pending Works',
                   'Total Tasks', 'Done Tasks', 'In Progress Tasks', 'Pending Tasks',
                   'Total Items', 'Completion %'];
        rows = employees.map(e => [
            e.emp_name, e.emp_id,
            e.total_works, e.completed_works, e.inprogress_works, e.pending_works,
            e.total_tasks, e.completed_tasks, e.inprogress_tasks, e.pending_tasks,
            e.total_items, e.completion_rate + '%'
        ]);
    } else if (currentTab === 'owned') {
        headers = ['Employee', 'Emp ID', 'Total Works', 'Completed', 'In Progress', 'Pending', 'No Status', 'Completion %'];
        rows = employees.map(e => {
            const total = e.total_works || 0;
            const done = e.completed_works || 0;
            const pct = total > 0 ? Math.round((done / total) * 100) + '%' : '0%';
            return [e.emp_name, e.emp_id, total, e.completed_works, e.inprogress_works, e.pending_works, e.no_status_works, pct];
        });
    } else { // performed
        headers = ['Employee', 'Emp ID', 'Total Tasks', 'Completed', 'In Progress', 'Pending', 'No Status', 'Completion %'];
        rows = employees.map(e => {
            const total = e.total_tasks || 0;
            const done = e.completed_tasks || 0;
            const pct = total > 0 ? Math.round((done / total) * 100) + '%' : '0%';
            return [e.emp_name, e.emp_id, total, e.completed_tasks, e.inprogress_tasks, e.pending_tasks, e.no_status_tasks, pct];
        });
    }

    const filename = `employee-kpi-${currentTab}-${new Date().toISOString().split('T')[0]}`;

    if (type === 'csv') {
        const csv = [filterText, '', headers.join(','),
            ...rows.map(r => r.map(c => `"${c}"`).join(','))
        ].join('\n');
        dlFile('data:text/csv;charset=utf-8,' + encodeURIComponent(csv), filename + '.csv');

    } else if (type === 'excel') {
        const ws = XLSX.utils.aoa_to_sheet([[filterText], [''], headers, ...rows]);
        ws['!cols'] = headers.map(() => ({wch: 20}));
        const wb = XLSX.utils.book_new();
        XLSX.utils.book_append_sheet(wb, ws, 'KPI');
        XLSX.writeFile(wb, filename + '.xlsx');

    } else if (type === 'pdf') {
        const { jsPDF } = window.jspdf;
        const doc = new jsPDF({ orientation: 'landscape' });
        doc.setFontSize(14);
        doc.text(`Employee KPI Report - ${currentTab.toUpperCase()}`, 14, 15);
        doc.setFontSize(8);
        doc.setTextColor(107, 114, 128);
        doc.text(filterText, 14, 21);
        doc.setTextColor(0, 0, 0);
        doc.autoTable({
            startY: 26,
            head: [headers],
            body: rows,
            styles: { fontSize: 7 },
            headStyles: { fillColor: [99, 102, 241] }
        });
        doc.save(filename + '.pdf');
    }
}

function dlFile(href, filename) {
    const a = document.createElement('a');
    a.href = href; a.download = filename; a.click();
}
</script>
</body>
</html>