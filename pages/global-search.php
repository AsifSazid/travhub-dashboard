<?php
// FILE PATH: /pages/global-search.php
include_once('./authenticate.php');
$ip_port = @file_get_contents('../ippath.txt') ?: 'http://103.104.219.3:898';
$q = trim($_GET['q'] ?? '');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $q ? htmlspecialchars($q) . ' — ' : ''; ?>Search · TravHub</title>
    <link rel="icon" type="image/png" href="../assets/images/logo/round-logo.png">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        body { background: #f8f9fc; }

        /* ── Skeleton shimmer ─────────────────────────── */
        @keyframes shimmer {
            0%   { background-position: -600px 0; }
            100% { background-position:  600px 0; }
        }
        .skeleton {
            background: linear-gradient(90deg, #e5e7eb 25%, #f3f4f6 50%, #e5e7eb 75%);
            background-size: 600px 100%;
            animation: shimmer 1.4s infinite linear;
            border-radius: 6px;
        }

        /* ── Spinner ring ─────────────────────────────── */
        @keyframes spin { to { transform: rotate(360deg); } }
        .spinner {
            width: 18px; height: 18px;
            border: 2.5px solid #c7d2fe;
            border-top-color: #4f46e5;
            border-radius: 50%;
            animation: spin .7s linear infinite;
            display: inline-block;
        }

        /* ── Card slide-in ────────────────────────────── */
        @keyframes fadeUp {
            from { opacity: 0; transform: translateY(10px); }
            to   { opacity: 1; transform: translateY(0);    }
        }
        .result-section { animation: fadeUp .25s ease both; }

        /* ── Highlight ────────────────────────────────── */
        mark {
            background: #fef08a;
            color: #713f12;
            border-radius: 3px;
            padding: 0 2px;
            font-style: normal;
        }

        /* ── Status badges ────────────────────────────── */
        .st { display:inline-flex;align-items:center;padding:1px 8px;border-radius:999px;font-size:.68rem;font-weight:600; }
        .st-pending     { background:#fef9c3;color:#854d0e; }
        .st-active      { background:#dcfce7;color:#166534; }
        .st-converted   { background:#dbeafe;color:#1e40af; }
        .st-closed,.st-cancelled { background:#fee2e2;color:#991b1b; }
        .st-hold,.st-on_hold    { background:#f3e8ff;color:#6b21a8; }
        .st-open        { background:#e0f2fe;color:#075985; }
        .st-in_progress { background:#fff7ed;color:#9a3412; }
        .st-done        { background:#dcfce7;color:#166534; }
    </style>
</head>
<body>
<?php include_once('../elements/header.php'); ?>
<?php include_once('../elements/aside.php'); ?>

<main class="ml-0 lg:ml-64 pt-16 min-h-screen">
<div class="max-w-5xl mx-auto px-4 py-8">

    <!-- Search bar (big, on page) -->
    <form action="global-search.php" method="GET" autocomplete="off" class="mb-8">
        <div class="relative">
            <i class="fas fa-search absolute left-4 top-1/2 -translate-y-1/2 text-gray-400"></i>
            <input name="q" id="pageSearchInput" type="text"
                value="<?php echo htmlspecialchars($q); ?>"
                placeholder="Search leads, works, tasks, clients, travelers, employees…"
                spellcheck="false"
                class="w-full pl-11 pr-24 py-3.5 text-base bg-white border border-gray-200 rounded-xl
                       shadow-sm focus:border-indigo-400 focus:ring-2 focus:ring-indigo-100 focus:outline-none transition">
            <button type="submit"
                class="absolute right-2 top-1/2 -translate-y-1/2 bg-indigo-600 hover:bg-indigo-700
                       text-white text-sm font-semibold px-4 py-2 rounded-lg transition">
                Search
            </button>
        </div>
    </form>

    <?php if (!$q): ?>
    <!-- Empty state -->
    <div class="text-center py-20 text-gray-400">
        <i class="fas fa-search text-5xl mb-4 opacity-20"></i>
        <p class="text-lg font-medium">Type something to search</p>
        <p class="text-sm mt-1">Searches across leads, works, tasks, clients, travelers & employees</p>
    </div>

    <?php else: ?>

    <!-- Query info + overall status bar -->
    <div class="flex items-center gap-3 mb-6">
        <p class="text-sm text-gray-500 flex-1">
            Results for <strong class="text-gray-800">"<?php echo htmlspecialchars($q); ?>"</strong>
        </p>
        <div id="overallStatus" class="flex items-center gap-2 text-sm text-indigo-600 font-medium">
            <span class="spinner"></span>
            <span id="overallStatusText">Searching…</span>
        </div>
    </div>

    <!-- Result sections will be injected here progressively -->
    <div id="resultContainer" class="space-y-6"></div>

    <!-- All-done empty state (shown if zero results total) -->
    <div id="noResultBox" class="hidden text-center py-20 text-gray-400">
        <i class="fas fa-search-minus text-5xl mb-4 opacity-20"></i>
        <p class="text-lg font-medium">No results found</p>
        <p class="text-sm mt-1">Try a different keyword or check for typos</p>
    </div>

    <?php endif; ?>

</div>
</main>

<?php if ($q): ?>
<script>
(function () {

const Q            = <?php echo json_encode($q); ?>;
const IP           = <?php echo json_encode(rtrim($ip_port,'/')); ?>;
const container    = document.getElementById('resultContainer');
const noResultBox  = document.getElementById('noResultBox');
const statusIcon   = document.querySelector('#overallStatus .spinner');
const statusText   = document.getElementById('overallStatusText');

/* ──────────────────────────────────────────────────────────────
   Module definitions — one API call per module
   Each module searches its own table via individual endpoints
   that already exist, OR we use the unified api below.
   We'll use one consolidated endpoint with ?module= param
────────────────────────────────────────────────────────────── */
const MODULES = [
    {
        key:   'leads',
        label: 'Leads',
        icon:  'fa-funnel-dollar',
        color: 'text-purple-500',
        badge: 'bg-purple-100 text-purple-700',
        link:  (r) => `edit-lead.php?id=${r.sys_id}`,
        title: (r) => r.client_name || r.sys_id,
        meta:  (r) => [
            r.sys_id,
            r.lead_status,
            r.service_type_label,
            r.source,
        ].filter(Boolean),
        status: (r) => r.lead_status,
    },
    {
        key:   'works',
        label: 'Works',
        icon:  'fa-briefcase',
        color: 'text-blue-500',
        badge: 'bg-blue-100 text-blue-700',
        link:  (r) => `show-works.php?id=${r.sys_id}`,
        title: (r) => r.client_name || r.sys_id,
        meta:  (r) => [r.sys_id, r.work_status, r.service_type_label, r.lead_sys_id].filter(Boolean),
        status: (r) => r.work_status,
    },
    {
        key:   'com_works',
        label: 'Compeleted Works',
        icon:  'fa-briefcase',
        color: 'text-blue-500',
        badge: 'bg-blue-100 text-blue-700',
        link:  (r) => `task-entry.php?work_id=${r.sys_id}`,
        title: (r) => r.client_name || r.sys_id,
        meta:  (r) => [r.sys_id, r.work_status, r.service_type_label, r.lead_sys_id].filter(Boolean),
        status: (r) => r.work_status,
    },
    {
        key:   'tasks',
        label: 'Tasks',
        icon:  'fa-tasks',
        color: 'text-green-500',
        badge: 'bg-green-100 text-green-700',
        link:  (r) => `show-tasks.php?id=${r.sys_id}`,
        title: (r) => r.workname || r.sys_id,
        meta:  (r) => [r.sys_id, r.client_name, r.work_sys_id, r.status].filter(Boolean),
        status: (r) => r.status,
    },
    {
        key:   'clients',
        label: 'Clients',
        icon:  'fa-user-tie',
        color: 'text-yellow-500',
        badge: 'bg-yellow-100 text-yellow-700',
        link:  (r) => `show-clients.php?id=${r.sys_id}`,
        title: (r) => r.name || r.sys_id,
        meta:  (r) => [r.sys_id, r.type, r.phone_flat, r.rep_name].filter(Boolean),
        status: (r) => r.status,
    },
    {
        key:   'vendors',
        label: 'Vendors',
        icon:  'fa-user-tie',
        color: 'text-yellow-500',
        badge: 'bg-yellow-100 text-yellow-700',
        link:  (r) => `show-vendors.php?id=${r.sys_id}`,
        title: (r) => r.name || r.sys_id,
        meta:  (r) => [r.sys_id].filter(Boolean),
        status: (r) => r.status,
    },
    {
        key:   'travelers',
        label: 'Travelers',
        icon:  'fa-passport',
        color: 'text-orange-500',
        badge: 'bg-orange-100 text-orange-700',
        link:  (r) => `show-travelers.php?id=${r.sys_id}`,
        title: (r) => r.name || r.sys_id,
        meta:  (r) => [r.sys_id, r.passport_no ? 'PP: '+r.passport_no : null, r.nid_no ? 'NID: '+r.nid_no : null, r.phone_flat].filter(Boolean),
        status: (r) => r.status,
    },
    {
        key:   'employees',
        label: 'Employees',
        icon:  'fa-id-badge',
        color: 'text-indigo-500',
        badge: 'bg-indigo-100 text-indigo-700',
        link:  (r) => `show-employees.php?id=${r.sys_id}`,
        title: (r) => r.name || r.sys_id,
        meta:  (r) => [r.sys_id, r.department_name, r.type, r.phone_flat].filter(Boolean),
        status: (r) => r.status,
    },
];

/* ── Highlight matching text ──────────────────────────────── */
function hi(str) {
    if (!str) return '';
    const safe = String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
    const escaped = Q.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
    return safe.replace(new RegExp('(' + escaped + ')', 'gi'),
        '<mark>$1</mark>');
}
function esc(s) { return String(s??'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;'); }

/* ── Status badge ─────────────────────────────────────────── */
function statusBadge(s) {
    if (!s) return '';
    const cls = 'st st-' + s.replace(/\s+/g,'_');
    return `<span class="${cls}">${esc(s.replace(/_/g,' '))}</span>`;
}

/* ── Render one module section ────────────────────────────── */
function renderSection(mod, rows) {
    const sec = document.createElement('div');
    sec.className = 'result-section bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden';
    sec.id = 'sec-' + mod.key;

    let cards = rows.map(r => {
        const titleHtml = hi(mod.title(r));
        const metaHtml  = mod.meta(r).map(m => `<span class="text-gray-400">·</span><span>${hi(m)}</span>`).join(' ');
        const st        = mod.status(r);
        return `
        <a href="${esc(mod.link(r))}"
           class="flex items-start gap-4 px-5 py-3.5 hover:bg-gray-50 transition border-b border-gray-50 last:border-0 group">
            <i class="fas ${esc(mod.icon)} ${esc(mod.color)} text-sm mt-1 w-4 flex-shrink-0"></i>
            <div class="flex-1 min-w-0">
                <p class="text-sm font-semibold text-gray-800 leading-snug truncate">${titleHtml}</p>
                <p class="text-xs text-gray-400 mt-0.5 flex flex-wrap items-center gap-1 truncate">
                    ${metaHtml}
                </p>
            </div>
            <div class="flex items-center gap-2 flex-shrink-0">
                ${st ? statusBadge(st) : ''}
                <i class="fas fa-chevron-right text-gray-300 text-xs group-hover:text-indigo-400 transition"></i>
            </div>
        </a>`;
    }).join('');

    sec.innerHTML = `
        <!-- Section header -->
        <div class="flex items-center justify-between px-5 py-3 border-b border-gray-100 bg-gray-50">
            <div class="flex items-center gap-2">
                <i class="fas ${esc(mod.icon)} ${esc(mod.color)} text-sm"></i>
                <span class="font-semibold text-gray-700 text-sm">${esc(mod.label)}</span>
                <span class="inline-flex items-center justify-center px-2 py-0.5 rounded-full text-xs font-bold ${esc(mod.badge)}">
                    ${rows.length}
                </span>
            </div>
        </div>
        <!-- Cards -->
        <div>${cards}</div>`;

    container.appendChild(sec);
}

/* ── Skeleton placeholder ─────────────────────────────────── */
function renderSkeleton(mod) {
    const sec = document.createElement('div');
    sec.id = 'sk-' + mod.key;
    sec.className = 'result-section bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden';
    sec.innerHTML = `
        <div class="flex items-center gap-2 px-5 py-3 border-b border-gray-100 bg-gray-50">
            <i class="fas ${esc(mod.icon)} ${esc(mod.color)} text-sm"></i>
            <span class="font-semibold text-gray-700 text-sm">${esc(mod.label)}</span>
            <span class="spinner" style="width:12px;height:12px;border-width:2px;"></span>
        </div>
        <div class="px-5 py-4 space-y-3">
            ${[1,2,3].map(()=>`
            <div class="flex gap-4 items-start">
                <div class="skeleton w-4 h-4 mt-1 flex-shrink-0"></div>
                <div class="flex-1 space-y-2">
                    <div class="skeleton h-3.5 w-3/5"></div>
                    <div class="skeleton h-2.5 w-4/5"></div>
                </div>
                <div class="skeleton h-4 w-14 rounded-full flex-shrink-0"></div>
            </div>`).join('')}
        </div>`;
    container.appendChild(sec);
    return sec;
}

/* ── Main: fire all module searches concurrently ────────────
   Each resolves independently → renders as soon as it's done
────────────────────────────────────────────────────────────── */
let totalFound = 0;
let doneCount  = 0;
const total    = MODULES.length;

// Show skeletons immediately (order preserved visually)
const skeletons = {};
MODULES.forEach(mod => { skeletons[mod.key] = renderSkeleton(mod); });

// Fire all fetches concurrently
MODULES.forEach(mod => {
    const url = `${IP}/api/global-search.php?q=${encodeURIComponent(Q)}&module=${mod.key}`;

    fetch(url, { cache: 'no-store' })
        .then(r => r.json())
        .then(json => {
            const rows = json.rows ?? [];

            // Remove skeleton
            skeletons[mod.key]?.remove();

            if (rows.length > 0) {
                totalFound += rows.length;
                renderSection(mod, rows);
            }
        })
        .catch(() => {
            skeletons[mod.key]?.remove();
        })
        .finally(() => {
            doneCount++;
            updateStatus();
        });
});

function updateStatus() {
    const remaining = total - doneCount;
    if (remaining > 0) {
        statusText.textContent = `Searching… (${doneCount}/${total} done)`;
    } else {
        // All done
        statusIcon.style.display = 'none';
        if (totalFound === 0) {
            statusText.textContent = 'No results found';
            statusText.className   = 'text-gray-400';
            noResultBox.classList.remove('hidden');
        } else {
            statusText.textContent = `${totalFound} result${totalFound > 1 ? 's' : ''} found`;
            statusText.className   = 'text-green-600';
        }
    }
}

/* ── Enter key on header search submits ───────────────────── */
document.getElementById('pageSearchInput')?.focus();

})();
</script>
<?php endif; ?>

</body>
</html>