<?php
include_once('./authenticate.php');

$ip_port = @file_get_contents('../ippath.txt');
if (empty($ip_port)) $ip_port = "http://103.104.219.3:898/";
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Air Ticket Calculation</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="../assets/css/style.css">

  <style>
    body { font-family: 'Inter', system-ui, sans-serif; }

    .index-card {
      background:#fff;
      border:1px solid #e5e7eb;
      border-radius:1rem;
      padding:1.25rem;
      cursor:pointer;
      transition:all .18s ease;
      display:flex;
      flex-direction:column;
      gap:.5rem;
    }

    .index-card:hover {
      border-color:#0f172a;
      box-shadow:0 8px 24px -8px rgba(15,23,42,.18);
      transform:translateY(-2px);
    }

    .card-badge {
      display:inline-flex;
      align-items:center;
      gap:.35rem;
      padding:.25rem .6rem;
      border-radius:999px;
      font-size:.72rem;
      font-weight:700;
    }

    .badge-air { background:#eff6ff; color:#1d4ed8; }
    .badge-pax { background:#f0fdf4; color:#15803d; }
    .badge-money { background:#fff7ed; color:#c2410c; }

    .search-wrap { position:relative; flex:1; min-width:240px; }

    .search-wrap i {
      position:absolute;
      left:.875rem;
      top:50%;
      transform:translateY(-50%);
      color:#94a3b8;
      font-size:.8rem;
    }

    .input-filter {
      width:100%;
      padding:.65rem .875rem .65rem 2.5rem;
      border:1px solid #e5e7eb;
      border-radius:.6rem;
      font-size:.9rem;
      background:#fff;
    }

    .input-filter.no-icon { padding-left:.875rem; }

    .input-filter:focus {
      outline:none;
      border-color:#0f172a;
      box-shadow:0 0 0 3px rgba(15,23,42,.08);
    }

    .btn-add {
      background:#0f172a;
      color:#fff;
      padding:.65rem 1.25rem;
      border-radius:.6rem;
      font-weight:700;
      font-size:.9rem;
      display:inline-flex;
      align-items:center;
      gap:.5rem;
      text-decoration:none;
      white-space:nowrap;
    }

    .btn-add:hover { background:#1e293b; }

    .page-btn {
      padding:.45rem .85rem;
      border:1px solid #e5e7eb;
      border-radius:.45rem;
      font-size:.85rem;
      font-weight:600;
      background:#fff;
      cursor:pointer;
    }

    .page-btn.active {
      background:#0f172a;
      color:#fff;
      border-color:#0f172a;
    }

    .page-btn:disabled {
      opacity:.4;
      cursor:not-allowed;
    }

    .skeleton {
      background:linear-gradient(90deg,#f1f5f9 25%,#e2e8f0 50%,#f1f5f9 75%);
      background-size:200% 100%;
      animation:shimmer 1.4s infinite;
      border-radius:.45rem;
    }

    @keyframes shimmer {
      0% { background-position:200% 0; }
      100% { background-position:-200% 0; }
    }

    .empty-state {
      text-align:center;
      padding:4rem 2rem;
      color:#94a3b8;
    }

    .empty-state i {
      font-size:3rem;
      margin-bottom:1rem;
      display:block;
    }
  </style>
</head>

<body class="bg-gray-50 font-sans">

<?php include '../elements/header.php'; ?>
<?php include '../elements/aside.php'; ?>
<?php include '../elements/preview-model.php'; ?>
<?php include '../elements/floating-menus.php'; ?>

<main id="mainContent" class="pt-16 pb-16 pl-64 md:pb-0 md:pl-16 lg:pl-64 transition-all duration-300">
  <div class="max-w-screen-2xl mx-auto px-4 py-8">

    <!-- Header -->
    <div class="flex flex-wrap items-center justify-between gap-4 mb-6">
      <div>
        <h1 class="text-2xl font-extrabold text-slate-900">Air Ticket Calculation</h1>
        <p class="text-sm text-slate-500 mt-0.5">All GDS air ticket calculation records</p>
      </div>

      <a href="air-ticket-price-calculation.php" class="btn-add">
        <i class="fas fa-plus text-xs"></i>
        Add New Calculation
      </a>
    </div>

    <!-- Filters -->
    <div class="flex flex-wrap gap-3 mb-4">
      <div class="search-wrap">
        <i class="fas fa-search"></i>
        <input
          type="text"
          id="searchInput"
          class="input-filter"
          placeholder="Search airline, sys_id, uuid, route..."
        >
      </div>

      <select id="sortSelect" class="input-filter no-icon" style="width:auto;flex:none;">
        <option value="created_at DESC">Newest First</option>
        <option value="created_at ASC">Oldest First</option>
        <option value="airline ASC">Airline A–Z</option>
        <option value="airline DESC">Airline Z–A</option>
        <option value="total_payable DESC">Highest Payable</option>
        <option value="total_payable ASC">Lowest Payable</option>
      </select>

      <select id="limitSelect" class="input-filter no-icon" style="width:auto;flex:none;">
        <option value="12">12 per page</option>
        <option value="24">24 per page</option>
        <option value="48">48 per page</option>
      </select>
    </div>

    <!-- Stats -->
    <div class="flex items-center justify-between mb-4">
      <span id="statsText" class="text-sm text-slate-400">Loading...</span>
    </div>

    <!-- Grid -->
    <div id="calculationGrid" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5 gap-4 mb-6"></div>

    <!-- Pagination -->
    <div id="paginationRow" class="flex flex-wrap items-center justify-between gap-3"></div>

  </div>
</main>

<script src="../assets/js/script.js?time=<?php echo time(); ?>"></script>

<script>
const API_BASE = '<?php echo rtrim($ip_port, "/"); ?>/';
const GET_API  = API_BASE + 'api/ticket-calculation/get-calculations.php';

let currentPage = 1;
let totalPages = 1;
let debounceTimer;

async function fetchCalculations() {
  const search = document.getElementById('searchInput').value.trim();
  const sort = document.getElementById('sortSelect').value;
  const limit = parseInt(document.getElementById('limitSelect').value);

  const url = `${GET_API}?search=${encodeURIComponent(search)}&page=${currentPage}&limit=${limit}&sort=${encodeURIComponent(sort)}`;

  showSkeletons(limit);

  try {
    const res = await fetch(url);
    const r = await res.json();

    if (!r.success) {
      showEmpty(r.message || 'Failed to load calculations.');
      return;
    }

    totalPages = Math.ceil(r.total / limit) || 1;

    renderGrid(r.data);
    renderStats(r.total, r.page, limit);
    renderPagination();

  } catch (e) {
    showEmpty('Network error: ' + e.message);
  }
}

function renderGrid(data) {
  const grid = document.getElementById('calculationGrid');
  grid.innerHTML = '';

  if (!data?.length) {
    grid.innerHTML = `
      <div class="col-span-full empty-state">
        <i class="fas fa-plane-slash text-slate-300"></i>
        <p class="text-lg font-semibold text-slate-600">No calculations found</p>
        <p class="text-sm mt-1 text-slate-400">
          Try a different search or
          <a href="air-ticket-price-calculation.php" class="text-blue-600 underline">create one</a>
        </p>
      </div>
    `;
    return;
  }

  data.forEach(q => {
    const card = document.createElement('div');
    card.className = 'index-card';
    card.onclick = () => {
      window.location.href = `air-ticket-price-calculation.php?id=${encodeURIComponent(q.uuid)}`;
    };

    const date = q.created_at
      ? new Date(q.created_at).toLocaleDateString('en-GB', {
          day: '2-digit',
          month: 'short',
          year: 'numeric'
        })
      : '—';

    const pax = parsePax(q.pax);
    const airline = q.airline || 'Unknown Airline';
    const payable = formatMoney(q.total_payable || 0);
    const gross = formatMoney(q.gross_fare || 0);

    card.innerHTML = `
      <div class="flex items-center justify-between flex-wrap gap-1">
        <span class="card-badge badge-air">
          <i class="fas fa-hashtag text-xs"></i> ${escapeHtml(q.sys_id || '—')}
        </span>

        <span class="card-badge badge-pax">
          <i class="fas fa-users text-xs"></i> ${pax.total} pax
        </span>
      </div>

      <h3 class="font-bold text-slate-900 leading-tight">
        ${escapeHtml(airline)}
      </h3>

      <div class="flex flex-wrap gap-1">
        <span class="card-badge badge-money">
          Gross: BDT ${gross}
        </span>
      </div>

      <p class="text-sm font-extrabold text-green-700">
        Payable: BDT ${payable}/-
      </p>

      <p class="text-xs text-slate-500 flex items-center gap-1.5">
        <i class="fas fa-user w-3 text-slate-400"></i>
        ADT ${pax.info.adult}, CHD ${pax.info.child}, INF ${pax.info.infant}
      </p>

      <p class="text-xs text-slate-400 flex items-center gap-1.5">
        <i class="fas fa-calendar-alt w-3 text-slate-300"></i>
        ${date}
      </p>
    `;

    grid.appendChild(card);
  });
}

function parsePax(paxRaw) {
  let fallback = {
    total: 0,
    info: {
      adult: 0,
      child: 0,
      infant: 0
    }
  };

  if (!paxRaw) return fallback;

  if (typeof paxRaw === 'object') return paxRaw;

  try {
    const parsed = JSON.parse(paxRaw);

    return {
      total: Number(parsed.total || 0),
      info: {
        adult: Number(parsed.info?.adult || 0),
        child: Number(parsed.info?.child || 0),
        infant: Number(parsed.info?.infant || 0)
      }
    };
  } catch (e) {
    return {
      total: Number(paxRaw || 0),
      info: {
        adult: Number(paxRaw || 0),
        child: 0,
        infant: 0
      }
    };
  }
}

function renderStats(total, page, limit) {
  const from = total ? (page - 1) * limit + 1 : 0;
  const to = Math.min(page * limit, total);

  document.getElementById('statsText').textContent =
    total > 0
      ? `Showing ${from}–${to} of ${total} calculation record${total !== 1 ? 's' : ''}`
      : 'No calculations found';
}

function renderPagination() {
  const row = document.getElementById('paginationRow');
  row.innerHTML = '';

  if (totalPages <= 1) return;

  const mkBtn = (label, pg, active = false, disabled = false) => {
    const b = document.createElement('button');
    b.className = 'page-btn' + (active ? ' active' : '');
    b.disabled = disabled;
    b.innerHTML = label;
    b.onclick = () => {
      currentPage = pg;
      fetchCalculations();
    };
    return b;
  };

  const wrap = document.createElement('div');
  wrap.className = 'flex items-center gap-1 flex-wrap';

  wrap.appendChild(
    mkBtn('<i class="fas fa-chevron-left text-xs"></i>', currentPage - 1, false, currentPage === 1)
  );

  let pages = [];

  if (totalPages <= 7) {
    pages = Array.from({ length: totalPages }, (_, i) => i + 1);
  } else {
    pages = [1];

    if (currentPage > 3) pages.push('…');

    for (
      let p = Math.max(2, currentPage - 1);
      p <= Math.min(totalPages - 1, currentPage + 1);
      p++
    ) {
      pages.push(p);
    }

    if (currentPage < totalPages - 2) pages.push('…');

    pages.push(totalPages);
  }

  pages.forEach(p => {
    if (p === '…') {
      const s = document.createElement('span');
      s.className = 'px-1 text-slate-400 text-sm';
      s.textContent = '…';
      wrap.appendChild(s);
    } else {
      wrap.appendChild(mkBtn(p, p, p === currentPage));
    }
  });

  wrap.appendChild(
    mkBtn('<i class="fas fa-chevron-right text-xs"></i>', currentPage + 1, false, currentPage === totalPages)
  );

  const info = document.createElement('span');
  info.className = 'text-sm text-slate-400';
  info.textContent = `Page ${currentPage} of ${totalPages}`;

  row.appendChild(wrap);
  row.appendChild(info);
}

function showSkeletons(n) {
  const grid = document.getElementById('calculationGrid');

  grid.innerHTML = Array.from({ length: n }).map(() => `
    <div class="index-card" style="cursor:default">
      <div class="flex gap-2 mb-1">
        <div class="skeleton h-5 w-28"></div>
        <div class="skeleton h-5 w-16"></div>
      </div>
      <div class="skeleton h-5 w-full"></div>
      <div class="skeleton h-4 w-3/4"></div>
      <div class="skeleton h-4 w-1/2"></div>
    </div>
  `).join('');
}

function showEmpty(msg) {
  document.getElementById('calculationGrid').innerHTML = `
    <div class="col-span-full empty-state">
      <i class="fas fa-exclamation-triangle text-amber-400"></i>
      <p class="text-slate-600 font-medium">${escapeHtml(msg)}</p>
    </div>
  `;

  document.getElementById('statsText').textContent = '';
}

function formatMoney(amount) {
  return Number(amount || 0).toLocaleString('en-BD', {
    maximumFractionDigits: 0
  });
}

function escapeHtml(value) {
  return String(value ?? '')
    .replaceAll('&', '&amp;')
    .replaceAll('<', '&lt;')
    .replaceAll('>', '&gt;')
    .replaceAll('"', '&quot;')
    .replaceAll("'", '&#039;');
}

document.getElementById('searchInput').addEventListener('input', () => {
  clearTimeout(debounceTimer);
  debounceTimer = setTimeout(() => {
    currentPage = 1;
    fetchCalculations();
  }, 400);
});

document.getElementById('sortSelect').addEventListener('change', () => {
  currentPage = 1;
  fetchCalculations();
});

document.getElementById('limitSelect').addEventListener('change', () => {
  currentPage = 1;
  fetchCalculations();
});

fetchCalculations();
</script>

</body>
</html>