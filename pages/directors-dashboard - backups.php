<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<title>DirectorLedger — Accounting System</title>
<script src="https://cdn.tailwindcss.com"></script>
<link href="https://fonts.googleapis.com/css2?family=DM+Serif+Display:ital@0;1&family=DM+Mono:wght@400;500&family=DM+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet" />
<script>
  tailwind.config = {
    theme: {
      extend: {
        fontFamily: {
          display: ['DM Serif Display', 'serif'],
          sans: ['DM Sans', 'sans-serif'],
          mono: ['DM Mono', 'monospace'],
        },
        colors: {
          ink: { 50:'#f7f6f3', 100:'#eeeae2', 200:'#ddd6c6', 300:'#c8bca5', 400:'#ad9b82', 500:'#937e63', 600:'#7a6450', 700:'#624f3f', 800:'#4d3d31', 900:'#3c2f25', 950:'#201810' },
          jade: { 50:'#edfaf3', 100:'#d3f3e3', 200:'#aae7cb', 300:'#73d4ac', 400:'#3fba8a', 500:'#1fa06d', 600:'#158059', 700:'#126648', 800:'#115238', 900:'#0f4330' },
          crimson: { 50:'#fff1f2', 100:'#ffe4e6', 200:'#fecdd3', 300:'#fda4af', 400:'#fb7185', 500:'#f43f5e', 600:'#e11d48', 700:'#be123c', 800:'#9f1239', 900:'#881337' },
          amber: { 50:'#fffbeb', 100:'#fef3c7', 200:'#fde68a', 300:'#fcd34d', 400:'#fbbf24', 500:'#f59e0b', 600:'#d97706', 700:'#b45309', 800:'#92400e', 900:'#78350f' },
          slate: { 50:'#f8fafc', 100:'#f1f5f9', 200:'#e2e8f0', 300:'#cbd5e1', 400:'#94a3b8', 500:'#64748b', 600:'#475569', 700:'#334155', 800:'#1e293b', 900:'#0f172a', 950:'#020617' },
        },
      }
    }
  }
</script>
<style>
  *, *::before, *::after { box-sizing: border-box; }
  body { font-family: 'Poppins', sans-serif; background: #f7f6f3; color: #3c2f25; }
  
  /* Sidebar */
  #sidebar { transition: transform 0.3s cubic-bezier(.4,0,.2,1); }
  .nav-item { transition: all 0.18s ease; }
  .nav-item:hover { background: rgba(60,47,37,0.07); }
  .nav-item.active { background: #3c2f25; color: #f7f6f3; }
  .nav-item.active svg { stroke: #f7f6f3; }

  /* Cards */
  .card { background: #fff; border: 1px solid #eeeae2; border-radius: 16px; box-shadow: 0 2px 12px rgba(60,47,37,0.07); }
  .card-hover { transition: transform 0.18s, box-shadow 0.18s; cursor:pointer; }
  .card-hover:hover { transform: translateY(-3px); box-shadow: 0 8px 32px rgba(60,47,37,0.13); }

  /* Buttons */
  .btn-primary { background: #3c2f25; color: #f7f6f3; border-radius:10px; font-weight:600; font-size:0.875rem; padding:0.5rem 1.25rem; transition:all 0.18s; }
  .btn-primary:hover { background: #201810; transform:translateY(-1px); box-shadow:0 4px 16px rgba(32,24,16,0.18); }
  .btn-outline { background: transparent; color: #3c2f25; border:1.5px solid #ddd6c6; border-radius:10px; font-weight:600; font-size:0.875rem; padding:0.5rem 1.25rem; transition:all 0.18s; }
  .btn-outline:hover { border-color:#3c2f25; background:#f7f6f3; }
  .btn-sm { font-size:0.75rem; padding:0.3rem 0.75rem; border-radius:8px; font-weight:600; transition:all 0.15s; }
  .btn-jade { background:#1fa06d; color:#fff; }
  .btn-jade:hover { background:#158059; }
  .btn-crimson { background:#e11d48; color:#fff; }
  .btn-crimson:hover { background:#be123c; }
  .btn-amber { background:#d97706; color:#fff; }
  .btn-amber:hover { background:#b45309; }
  .btn-slate { background:#475569; color:#fff; }
  .btn-slate:hover { background:#334155; }

  /* Table */
  table { border-collapse: collapse; width:100%; }
  th { font-size:0.72rem; font-weight:600; letter-spacing:0.08em; text-transform:uppercase; color:#937e63; padding:0.7rem 1rem; background:#f7f6f3; }
  td { padding:0.85rem 1rem; border-top:1px solid #eeeae2; font-size:0.875rem; }
  tr:hover td { background:#fdfcfb; }

  /* Progress bar */
  .progress-bar { height:6px; border-radius:999px; background:#eeeae2; overflow:hidden; }
  .progress-fill { height:100%; border-radius:999px; transition:width 0.5s cubic-bezier(.4,0,.2,1); background: linear-gradient(90deg,#1fa06d,#3fba8a); }

  /* Modal */
  .modal-overlay { position:fixed; inset:0; background:rgba(32,24,16,0.45); z-index:200; display:flex; align-items:center; justify-content:center; opacity:0; pointer-events:none; transition:opacity 0.22s; }
  .modal-overlay.open { opacity:1; pointer-events:all; }
  .modal-box { background:#fff; border-radius:20px; padding:2rem; max-width:480px; width:92vw; box-shadow:0 24px 80px rgba(32,24,16,0.22); transform:scale(0.95) translateY(16px); transition:transform 0.22s cubic-bezier(.4,0,.2,1); max-height:90vh; overflow-y:auto; }
  .modal-overlay.open .modal-box { transform:scale(1) translateY(0); }

  /* Input */
  .inp { border:1.5px solid #ddd6c6; border-radius:10px; padding:0.55rem 0.9rem; font-size:0.9rem; font-family:'DM Sans',sans-serif; width:100%; outline:none; transition:border-color 0.15s; background:#fff; }
  .inp:focus { border-color:#3c2f25; }
  label { font-size:0.8rem; font-weight:600; color:#624f3f; margin-bottom:0.3rem; display:block; }

  /* Chart canvas */
  #plChart { width:100% !important; max-height:240px; }

  /* Stat cards */
  .stat-card { background:#fff; border:1px solid #eeeae2; border-radius:14px; padding:1.25rem 1.5rem; }
  
  /* Badge */
  .badge { display:inline-flex; align-items:center; font-size:0.72rem; font-weight:600; border-radius:999px; padding:0.18rem 0.7rem; }
  .badge-jade { background:#d3f3e3; color:#158059; }
  .badge-crimson { background:#ffe4e6; color:#be123c; }
  .badge-amber { background:#fef3c7; color:#b45309; }

  /* Scrollbar */
  ::-webkit-scrollbar { width:6px; height:6px; }
  ::-webkit-scrollbar-track { background:transparent; }
  ::-webkit-scrollbar-thumb { background:#c8bca5; border-radius:999px; }

  /* Page transitions */
  .page { display:none; animation:fadeIn 0.22s ease; }
  .page.active { display:block; }
  @keyframes fadeIn { from{opacity:0;transform:translateY(8px);} to{opacity:1;transform:translateY(0);} }

  /* Skeleton */
  .skeleton { background:linear-gradient(90deg,#eeeae2 25%,#f7f6f3 50%,#eeeae2 75%); background-size:200% 100%; animation:shimmer 1.3s infinite; border-radius:8px; }
  @keyframes shimmer { 0%{background-position:200% 0;} 100%{background-position:-200% 0;} }

  /* Toggle */
  .toggle-btn { padding:0.35rem 0.9rem; border-radius:8px; font-size:0.8rem; font-weight:600; cursor:pointer; border:1.5px solid #ddd6c6; background:transparent; color:#624f3f; transition:all 0.15s; }
  .toggle-btn.selected { background:#3c2f25; color:#fff; border-color:#3c2f25; }

  /* Notification */
  #notif { position:fixed; top:1.5rem; right:1.5rem; z-index:999; transform:translateX(200%); transition:transform 0.28s cubic-bezier(.4,0,.2,1); }
  #notif.show { transform:translateX(0); }

  /* Mobile sidebar overlay */
  #sideOverlay { display:none; position:fixed; inset:0; background:rgba(32,24,16,0.35); z-index:98; }
  @media(max-width:768px){
    #sidebar { position:fixed; left:0; top:0; bottom:0; z-index:99; transform:translateX(-100%); }
    #sidebar.open { transform:translateX(0); }
  }
</style>
</head>
<body class="min-h-screen flex">

<!-- Sidebar Overlay (mobile) -->
<div id="sideOverlay" onclick="closeSidebar()"></div>

<!-- SIDEBAR -->
<aside id="sidebar" class="w-64 bg-white border-r border-[#eeeae2] flex flex-col h-screen sticky top-0 shrink-0">
  <div class="p-5 border-b border-[#eeeae2] flex items-center gap-3">
    <div class="w-9 h-9 rounded-xl bg-[#3c2f25] flex items-center justify-center">
      <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#f7f6f3" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="3" width="20" height="14" rx="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/></svg>
    </div>
    <div>
      <div class="font-display text-[#3c2f25] text-base leading-tight">DirectorLedger</div>
      <div class="text-[10px] text-[#937e63] font-mono uppercase tracking-wider">Accounting System</div>
    </div>
  </div>

  <nav class="flex-1 p-3 space-y-1 overflow-y-auto">
    <div class="text-[10px] font-mono uppercase tracking-widest text-[#ad9b82] px-3 pt-2 pb-1">Main</div>
    <button onclick="navigate('dashboard')" class="nav-item active w-full flex items-center gap-3 px-3 py-2.5 rounded-xl text-left text-sm font-medium" data-page="dashboard">
      <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
      Dashboard
    </button>
    <button onclick="navigate('settings')" class="nav-item w-full flex items-center gap-3 px-3 py-2.5 rounded-xl text-left text-sm font-medium" data-page="settings">
      <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"/><path d="M19.07 4.93a1 1 0 0 0-1.41 0l-.71.71a7 7 0 0 1 1.41 1.41l.71-.71a1 1 0 0 0 0-1.41zM4.93 4.93a1 1 0 0 0 0 1.41l.71.71a7 7 0 0 1 1.41-1.41l-.71-.71a1 1 0 0 0-1.41 0zM12 2a1 1 0 0 0-1 1v1a7 7 0 0 1 2 0V3a1 1 0 0 0-1-1zm0 18a1 1 0 0 0 1-1v-1a7 7 0 0 1-2 0v1a1 1 0 0 0 1 1zm7-7h1a1 1 0 0 0 0-2h-1a7 7 0 0 1 0 2zm-16 0a1 1 0 0 0 1 1h1a7 7 0 0 1 0-2H4a1 1 0 0 0-1 1zM17.66 17.66l.71.71a1 1 0 0 0 1.41-1.41l-.71-.71a7 7 0 0 1-1.41 1.41zm-12.73 0a7 7 0 0 1-1.41-1.41l-.71.71a1 1 0 0 0 1.41 1.41l.71-.71z"/></svg>
      Settings
    </button>
    <button onclick="navigate('profiles')" class="nav-item w-full flex items-center gap-3 px-3 py-2.5 rounded-xl text-left text-sm font-medium" data-page="profiles">
      <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
      Director Profiles
    </button>
    <button onclick="navigate('profitloss')" class="nav-item w-full flex items-center gap-3 px-3 py-2.5 rounded-xl text-left text-sm font-medium" data-page="profitloss">
      <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="22 7 13.5 15.5 8.5 10.5 2 17"/><polyline points="16 7 22 7 22 13"/></svg>
      Profit / Loss
    </button>
    <button onclick="navigate('dividend')" class="nav-item w-full flex items-center gap-3 px-3 py-2.5 rounded-xl text-left text-sm font-medium" data-page="dividend">
      <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
      Dividend
    </button>
  </nav>

  <div class="p-4 border-t border-[#eeeae2]">
    <div class="flex items-center gap-2.5 px-2">
      <div class="w-8 h-8 rounded-full bg-[#ddd6c6] flex items-center justify-center text-sm font-bold text-[#4d3d31]">A</div>
      <div>
        <div class="text-sm font-semibold text-[#3c2f25]">Admin</div>
        <div class="text-[11px] text-[#937e63]">Super Director</div>
      </div>
    </div>
  </div>
</aside>

<!-- MAIN -->
<div class="flex-1 flex flex-col min-h-screen overflow-hidden">

  <!-- Top Navbar -->
  <header class="sticky top-0 z-50 bg-white/90 backdrop-blur-sm border-b border-[#eeeae2] flex items-center justify-between px-5 py-3">
    <div class="flex items-center gap-3">
      <button class="md:hidden p-2 rounded-lg hover:bg-[#f7f6f3]" onclick="openSidebar()">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
      </button>
      <div>
        <h1 id="pageTitle" class="font-display text-xl text-[#3c2f25]">Dashboard</h1>
        <div id="pageSubtitle" class="text-xs text-[#937e63]">Overview of all director accounts</div>
      </div>
    </div>
    <div class="flex items-center gap-3">
      <div class="hidden sm:flex items-center gap-2 bg-[#f7f6f3] rounded-xl px-3 py-1.5 border border-[#eeeae2]">
        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="#937e63" stroke-width="2" stroke-linecap="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
        <input type="text" placeholder="Search..." class="bg-transparent text-sm outline-none text-[#3c2f25] w-32 placeholder:text-[#ad9b82]" />
      </div>
      <div class="w-8 h-8 rounded-full bg-[#3c2f25] flex items-center justify-center text-[#f7f6f3] text-xs font-bold">AD</div>
    </div>
  </header>

  <!-- Pages -->
  <main class="flex-1 overflow-y-auto p-5 lg:p-7">

    <!-- ===== DASHBOARD ===== -->
    <div id="page-dashboard" class="page active">
      <!-- Stats Row -->
      <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <div class="stat-card">
          <div class="text-xs text-[#937e63] font-semibold mb-1">Total Directors</div>
          <div id="dash-total-dirs" class="font-display text-3xl text-[#3c2f25]">0</div>
          <div class="text-xs text-[#ad9b82] mt-1">Active members</div>
        </div>
        <div class="stat-card">
          <div class="text-xs text-[#937e63] font-semibold mb-1">Total Investment</div>
          <div id="dash-total-inv" class="font-display text-3xl text-[#3c2f25]">—</div>
          <div class="text-xs text-[#ad9b82] mt-1">All directors combined</div>
        </div>
        <div class="stat-card">
          <div class="text-xs text-[#937e63] font-semibold mb-1">Total Ownership</div>
          <div id="dash-total-own" class="font-display text-3xl text-[#3c2f25]">—</div>
          <div class="text-xs text-[#ad9b82] mt-1">Unit-based calculation</div>
        </div>
        <div class="stat-card">
          <div class="text-xs text-[#937e63] font-semibold mb-1">Base Unit</div>
          <div class="font-display text-3xl text-[#937e63]">12,000</div>
          <div class="text-xs text-[#ad9b82] mt-1">= 12.5% ownership</div>
        </div>
      </div>

      <!-- Directors Quick View -->
      <div class="card p-5 mb-6">
        <div class="flex items-center justify-between mb-4">
          <h2 class="font-display text-lg text-[#3c2f25]">Director Ownership Snapshot</h2>
          <button onclick="navigate('profiles')" class="btn-outline text-xs px-3 py-1.5">View All →</button>
        </div>
        <div id="dash-directors-list" class="space-y-3"></div>
      </div>

      <!-- Recent Activity -->
      <div class="card p-5">
        <h2 class="font-display text-lg text-[#3c2f25] mb-4">Recent Activity</h2>
        <div id="dash-activity" class="space-y-2"></div>
      </div>
    </div>

    <!-- ===== SETTINGS ===== -->
    <div id="page-settings" class="page">
      <!-- Base Unit Notice -->
      <div class="bg-[#fef3c7] border border-[#fde68a] rounded-xl px-4 py-3 mb-5 flex items-start gap-3">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#d97706" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mt-0.5 shrink-0"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
        <div class="text-sm text-[#92400e]"><strong>Unit-Based Ownership Model:</strong> Base Unit = 12,000 → 12.5% ownership. Each director's ownership = (Investment ÷ 12,000) × 12.5%</div>
      </div>

      <div class="grid lg:grid-cols-3 gap-6">
        <!-- Add/Edit Form -->
        <div class="lg:col-span-1">
          <div class="card p-5">
            <h2 id="settings-form-title" class="font-display text-lg text-[#3c2f25] mb-4">Add Director</h2>
            <div class="space-y-3">
              <input type="hidden" id="edit-id" value="" />
              <div>
                <label>Director Name</label>
                <input type="text" id="dir-name" class="inp" placeholder="e.g. Arif Rahman" />
              </div>
              <div>
                <label>Investment Amount (BDT)</label>
                <input type="number" id="dir-amount" class="inp" placeholder="e.g. 12000" min="0" />
              </div>
              <div class="bg-[#f7f6f3] rounded-xl p-3 text-sm">
                <div class="flex justify-between text-xs text-[#937e63] mb-1">
                  <span>Calculated Ownership</span>
                  <span class="font-mono font-semibold text-[#3c2f25]" id="calc-preview">—</span>
                </div>
                <div class="progress-bar"><div class="progress-fill" id="calc-bar" style="width:0%"></div></div>
              </div>
              <div class="flex gap-2 pt-1">
                <button onclick="saveDirector()" class="btn-primary flex-1">
                  <span id="save-btn-txt">Add Director</span>
                </button>
                <button onclick="cancelEdit()" id="cancel-btn" class="btn-outline" style="display:none">Cancel</button>
              </div>
            </div>
          </div>
        </div>

        <!-- Directors Table -->
        <div class="lg:col-span-2">
          <div class="card overflow-hidden">
            <div class="px-5 py-4 border-b border-[#eeeae2] flex items-center justify-between">
              <h2 class="font-display text-lg text-[#3c2f25]">Directors</h2>
              <div class="text-xs font-mono text-[#937e63]" id="settings-summary">Total: —</div>
            </div>
            <div class="overflow-x-auto">
              <table>
                <thead>
                  <tr>
                    <th class="text-left">Name</th>
                    <th class="text-right">Investment</th>
                    <th class="text-right">Ownership %</th>
                    <th class="text-center">Actions</th>
                  </tr>
                </thead>
                <tbody id="settings-table-body"></tbody>
              </table>
            </div>
            <div id="settings-empty" class="p-10 text-center text-[#ad9b82] hidden">
              <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="#c8bca5" stroke-width="1.5" class="mx-auto mb-3"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
              <p class="text-sm">No directors added yet.</p>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- ===== PROFILES ===== -->
    <div id="page-profiles" class="page">
      <!-- Summary -->
      <div class="grid grid-cols-2 md:grid-cols-3 gap-4 mb-6">
        <div class="stat-card md:col-span-1">
          <div class="text-xs text-[#937e63] font-semibold mb-1">Total Investment</div>
          <div id="prof-total-inv" class="font-display text-2xl text-[#3c2f25]">—</div>
        </div>
        <div class="stat-card">
          <div class="text-xs text-[#937e63] font-semibold mb-1">Total Ownership</div>
          <div id="prof-total-own" class="font-display text-2xl text-[#3c2f25]">—</div>
        </div>
        <div class="stat-card">
          <div class="text-xs text-[#937e63] font-semibold mb-1">Directors</div>
          <div id="prof-count" class="font-display text-2xl text-[#3c2f25]">—</div>
        </div>
      </div>

      <div id="profiles-grid" class="grid sm:grid-cols-2 lg:grid-cols-3 gap-5"></div>
      <div id="profiles-empty" class="card p-10 text-center text-[#ad9b82] hidden">
        <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="#c8bca5" stroke-width="1.5" class="mx-auto mb-3"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>
        <p class="text-sm">No director profiles yet. Add some in Settings.</p>
      </div>
    </div>

    <!-- ===== PROFIT/LOSS ===== -->
    <div id="page-profitloss" class="page">
      <!-- Toggle Period -->
      <div class="flex items-center gap-2 mb-5">
        <button class="toggle-btn selected" onclick="setPLPeriod('daily',this)">Daily</button>
        <button class="toggle-btn" onclick="setPLPeriod('weekly',this)">Weekly</button>
        <button class="toggle-btn" onclick="setPLPeriod('monthly',this)">Monthly</button>
        <button class="toggle-btn" onclick="setPLPeriod('yearly',this)">Yearly</button>
      </div>

      <!-- Stats Row -->
      <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <div class="stat-card">
          <div class="text-xs text-[#937e63] font-semibold mb-1">Daily Average</div>
          <div id="pl-daily" class="font-display text-2xl">—</div>
        </div>
        <div class="stat-card">
          <div class="text-xs text-[#937e63] font-semibold mb-1">Weekly Average</div>
          <div id="pl-weekly" class="font-display text-2xl">—</div>
        </div>
        <div class="stat-card">
          <div class="text-xs text-[#937e63] font-semibold mb-1">Monthly Total</div>
          <div id="pl-monthly" class="font-display text-2xl">—</div>
        </div>
        <div class="stat-card">
          <div class="text-xs text-[#937e63] font-semibold mb-1">Yearly Total</div>
          <div id="pl-yearly" class="font-display text-2xl">—</div>
        </div>
      </div>

      <!-- Chart -->
      <div class="card p-5 mb-6">
        <div class="flex items-center justify-between mb-4">
          <h2 id="pl-chart-title" class="font-display text-lg text-[#3c2f25]">Daily Profit / Loss Trend</h2>
        </div>
        <canvas id="plChart"></canvas>
      </div>

      <!-- Data Table -->
      <div class="card overflow-hidden">
        <div class="px-5 py-4 border-b border-[#eeeae2]">
          <h2 class="font-display text-lg text-[#3c2f25]">Transaction Records</h2>
        </div>
        <div class="overflow-x-auto">
          <table>
            <thead>
              <tr>
                <th class="text-left">Period</th>
                <th class="text-right">Amount (BDT)</th>
                <th class="text-center">Type</th>
                <th class="text-left">Notes</th>
              </tr>
            </thead>
            <tbody id="pl-table"></tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- ===== DIVIDEND ===== -->
    <div id="page-dividend" class="page">
      <div class="card p-5 mb-6">
        <h2 class="font-display text-lg text-[#3c2f25] mb-4">Disbursement Calculator</h2>
        <div class="flex flex-wrap gap-3 items-end">
          <div class="flex-1 min-w-48">
            <label>Total Profit Amount (BDT)</label>
            <input type="number" id="div-profit" class="inp" placeholder="e.g. 240000" oninput="calcDividends()" />
          </div>
          <button onclick="disburseModal()" class="btn-primary">Disburse Dividend</button>
        </div>
        <div class="mt-3 text-xs text-[#937e63]">Formula: Dividend = (Ownership% ÷ 100) × Total Profit</div>
      </div>

      <div class="card overflow-hidden">
        <div class="px-5 py-4 border-b border-[#eeeae2] flex items-center justify-between">
          <h2 class="font-display text-lg text-[#3c2f25]">Dividend Breakdown</h2>
          <div id="div-total-badge" class="text-xs font-mono text-[#937e63]"></div>
        </div>
        <div class="overflow-x-auto">
          <table>
            <thead>
              <tr>
                <th class="text-left">Director</th>
                <th class="text-right">Investment</th>
                <th class="text-right">Ownership %</th>
                <th class="text-right">Dividend (BDT)</th>
              </tr>
            </thead>
            <tbody id="div-table"></tbody>
          </table>
        </div>
        <div id="div-empty" class="p-10 text-center text-[#ad9b82]">
          <svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="#c8bca5" stroke-width="1.5" class="mx-auto mb-3"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
          <p class="text-sm">Enter a profit amount above to see dividend calculations.</p>
        </div>
      </div>
    </div>

  </main>
</div>

<!-- ==================== MODALS ==================== -->

<!-- Add Investment Modal -->
<div id="modal-invest" class="modal-overlay" onclick="if(event.target===this)closeModal('modal-invest')">
  <div class="modal-box">
    <h2 class="font-display text-xl text-[#3c2f25] mb-1">Add Investment</h2>
    <p id="modal-invest-name" class="text-sm text-[#937e63] mb-4"></p>
    <div class="bg-[#f7f6f3] rounded-xl p-3 mb-4 text-sm space-y-1">
      <div class="flex justify-between"><span class="text-[#937e63]">Current Investment:</span><span class="font-mono font-semibold" id="mi-current"></span></div>
      <div class="flex justify-between"><span class="text-[#937e63]">Current Ownership:</span><span class="font-mono font-semibold" id="mi-own"></span></div>
    </div>
    <div class="space-y-3 mb-5">
      <div>
        <label>Amount to Add (BDT)</label>
        <input type="number" id="mi-amount" class="inp" placeholder="Enter amount" min="1" oninput="previewInvest()" />
      </div>
      <div class="bg-[#edfaf3] border border-[#aae7cb] rounded-xl p-3 text-sm hidden" id="mi-preview">
        <div class="flex justify-between"><span class="text-[#158059]">New Investment:</span><span class="font-mono font-semibold text-[#158059]" id="mi-new-inv"></span></div>
        <div class="flex justify-between"><span class="text-[#158059]">New Ownership:</span><span class="font-mono font-semibold text-[#158059]" id="mi-new-own"></span></div>
      </div>
    </div>
    <div class="flex gap-3">
      <button onclick="confirmAddInvest()" class="btn-primary flex-1">Confirm Add</button>
      <button onclick="closeModal('modal-invest')" class="btn-outline">Cancel</button>
    </div>
  </div>
</div>

<!-- Withdraw Modal -->
<div id="modal-withdraw" class="modal-overlay" onclick="if(event.target===this)closeModal('modal-withdraw')">
  <div class="modal-box">
    <h2 class="font-display text-xl text-[#3c2f25] mb-1">Withdraw Investment</h2>
    <p id="modal-withdraw-name" class="text-sm text-[#937e63] mb-4"></p>
    <div class="bg-[#f7f6f3] rounded-xl p-3 mb-4 text-sm space-y-1">
      <div class="flex justify-between"><span class="text-[#937e63]">Current Investment:</span><span class="font-mono font-semibold" id="mw-current"></span></div>
      <div class="flex justify-between"><span class="text-[#937e63]">Current Ownership:</span><span class="font-mono font-semibold" id="mw-own"></span></div>
    </div>
    <div class="space-y-3 mb-5">
      <div>
        <label>Amount to Withdraw (BDT)</label>
        <input type="number" id="mw-amount" class="inp" placeholder="Enter amount" min="1" oninput="previewWithdraw()" />
      </div>
      <div id="mw-preview" class="hidden">
        <div class="bg-[#fff1f2] border border-[#fecdd3] rounded-xl p-3 text-sm">
          <div class="flex justify-between"><span class="text-[#be123c]">New Investment:</span><span class="font-mono font-semibold text-[#be123c]" id="mw-new-inv"></span></div>
          <div class="flex justify-between"><span class="text-[#be123c]">New Ownership:</span><span class="font-mono font-semibold text-[#be123c]" id="mw-new-own"></span></div>
        </div>
      </div>
      <div id="mw-error" class="text-xs text-[#e11d48] hidden">Amount exceeds current investment.</div>
    </div>
    <div class="flex gap-3">
      <button onclick="confirmWithdraw()" class="btn-primary flex-1" style="background:#e11d48">Confirm Withdraw</button>
      <button onclick="closeModal('modal-withdraw')" class="btn-outline">Cancel</button>
    </div>
  </div>
</div>

<!-- Profile Modal -->
<div id="modal-profile" class="modal-overlay" onclick="if(event.target===this)closeModal('modal-profile')">
  <div class="modal-box">
    <div class="flex items-center gap-4 mb-5">
      <div id="prof-modal-avatar" class="w-14 h-14 rounded-2xl flex items-center justify-center text-2xl font-display text-white"></div>
      <div>
        <h2 id="prof-modal-name" class="font-display text-2xl text-[#3c2f25]"></h2>
        <div id="prof-modal-own" class="text-sm text-[#937e63]"></div>
      </div>
    </div>
    <div class="grid grid-cols-2 gap-3 mb-5">
      <div class="bg-[#f7f6f3] rounded-xl p-3 text-center">
        <div class="text-xs text-[#937e63] mb-1">Investment</div>
        <div id="prof-modal-inv" class="font-mono font-semibold text-[#3c2f25]"></div>
      </div>
      <div class="bg-[#f7f6f3] rounded-xl p-3 text-center">
        <div class="text-xs text-[#937e63] mb-1">Ownership %</div>
        <div id="prof-modal-pct" class="font-mono font-semibold text-[#3c2f25]"></div>
      </div>
    </div>
    <h3 class="font-semibold text-sm text-[#624f3f] mb-3 uppercase tracking-wider text-xs font-mono">Transaction History</h3>
    <div id="prof-modal-history" class="space-y-2"></div>
    <button onclick="closeModal('modal-profile')" class="btn-outline w-full mt-5">Close</button>
  </div>
</div>

<!-- Disburse Confirmation Modal -->
<div id="modal-disburse" class="modal-overlay" onclick="if(event.target===this)closeModal('modal-disburse')">
  <div class="modal-box">
    <div class="text-center mb-5">
      <div class="w-14 h-14 bg-[#d3f3e3] rounded-2xl flex items-center justify-center mx-auto mb-3">
        <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#158059" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
      </div>
      <h2 class="font-display text-2xl text-[#3c2f25] mb-1">Confirm Disbursement</h2>
      <p class="text-sm text-[#937e63]">This action will distribute dividends to all directors.</p>
    </div>
    <div class="bg-[#f7f6f3] rounded-xl p-4 mb-5 space-y-2" id="disburse-summary"></div>
    <div class="flex gap-3">
      <button onclick="confirmDisburse()" class="btn-primary flex-1" style="background:#1fa06d">Confirm &amp; Disburse</button>
      <button onclick="closeModal('modal-disburse')" class="btn-outline">Cancel</button>
    </div>
  </div>
</div>

<!-- Delete Confirm Modal -->
<div id="modal-delete" class="modal-overlay" onclick="if(event.target===this)closeModal('modal-delete')">
  <div class="modal-box text-center">
    <div class="w-14 h-14 bg-[#ffe4e6] rounded-2xl flex items-center justify-center mx-auto mb-3">
      <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#be123c" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/><path d="M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"/></svg>
    </div>
    <h2 class="font-display text-2xl text-[#3c2f25] mb-1">Delete Director</h2>
    <p id="delete-modal-name" class="text-sm text-[#937e63] mb-5"></p>
    <div class="flex gap-3">
      <button onclick="confirmDelete()" class="btn-primary flex-1" style="background:#e11d48">Delete</button>
      <button onclick="closeModal('modal-delete')" class="btn-outline">Cancel</button>
    </div>
  </div>
</div>

<!-- Notification -->
<div id="notif" class="card px-4 py-3 flex items-center gap-3 min-w-64">
  <div id="notif-icon"></div>
  <div id="notif-msg" class="text-sm font-medium text-[#3c2f25]"></div>
</div>

<script>
// ==================== STATE & CONSTANTS ====================
const BASE_UNIT = 12000;
const BASE_PCT = 12.5;

const COLORS = ['#1fa06d','#3c2f25','#d97706','#e11d48','#7c3aed','#0284c7','#be123c','#0891b2'];

let state = {
  directors: [
    { id:1, name:'Arif Rahman',   investment:15000, history:[] },
    { id:2, name:'Bashir Ahmed',  investment:12000, history:[] },
    { id:3, name:'Champa Begum',  investment:12000, history:[] },
    { id:4, name:'Delwar Hossain',investment:12000, history:[] },
    { id:5, name:'Erina Sultana', investment:9000,  history:[] },
  ],
  nextId: 6,
  activity: [],
};

// Seed history
state.directors.forEach(d => {
  d.history = [
    { date:'2025-01-15', type:'invest',   amount:d.investment, note:'Initial investment' },
  ];
});
state.activity = [
  { msg:'Arif Rahman added ৳3,000 to investment', time:'2 days ago', type:'invest' },
  { msg:'Erina Sultana withdrew ৳3,000', time:'5 days ago', type:'withdraw' },
  { msg:'New director Erina Sultana joined', time:'2 weeks ago', type:'join' },
];

let currentPage = 'dashboard';
let modalTargetId = null;
let plPeriod = 'daily';
let plChart = null;

// ==================== CALCULATIONS ====================
function calcOwnership(investment) {
  return (investment / BASE_UNIT) * BASE_PCT;
}
function totalInvestment() {
  return state.directors.reduce((s, d) => s + d.investment, 0);
}
function totalOwnership() {
  return state.directors.reduce((s, d) => s + calcOwnership(d.investment), 0);
}
function fmt(n) {
  return '৳' + n.toLocaleString('en-BD');
}
function pct(n) {
  return n.toFixed(4).replace(/\.?0+$/, '') + '%';
}

// ==================== NAVIGATION ====================
function navigate(page) {
  document.querySelectorAll('.page').forEach(p => p.classList.remove('active'));
  document.querySelectorAll('.nav-item').forEach(n => n.classList.remove('active'));
  document.getElementById('page-' + page).classList.add('active');
  document.querySelector(`[data-page="${page}"]`).classList.add('active');
  currentPage = page;

  const titles = {
    dashboard: ['Dashboard', 'Overview of all director accounts'],
    settings: ['Director Settings', 'Manage directors and investments'],
    profiles: ['Director Profiles', 'View and interact with director cards'],
    profitloss: ['Profit / Loss', 'Track financial performance trends'],
    dividend: ['Dividend Disbursement', 'Calculate and distribute profits'],
  };
  document.getElementById('pageTitle').textContent = titles[page][0];
  document.getElementById('pageSubtitle').textContent = titles[page][1];

  renderPage(page);
  closeSidebar();
}

function openSidebar() {
  document.getElementById('sidebar').classList.add('open');
  document.getElementById('sideOverlay').style.display = 'block';
}
function closeSidebar() {
  document.getElementById('sidebar').classList.remove('open');
  document.getElementById('sideOverlay').style.display = 'none';
}

// ==================== RENDER PAGES ====================
function renderPage(page) {
  if (page === 'dashboard') renderDashboard();
  if (page === 'settings') renderSettings();
  if (page === 'profiles') renderProfiles();
  if (page === 'profitloss') renderPL();
  if (page === 'dividend') renderDividend();
}

function renderDashboard() {
  const dirs = state.directors;
  document.getElementById('dash-total-dirs').textContent = dirs.length;
  document.getElementById('dash-total-inv').textContent = fmt(totalInvestment());

  const to = totalOwnership();
  const toEl = document.getElementById('dash-total-own');
  toEl.textContent = pct(to);
  toEl.style.color = to > 100 ? '#1fa06d' : to < 100 ? '#d97706' : '#3c2f25';

  // Director list
  const list = document.getElementById('dash-directors-list');
  list.innerHTML = dirs.map((d, i) => {
    const own = calcOwnership(d.investment);
    return `
    <div class="flex items-center gap-3">
      <div class="w-8 h-8 rounded-lg shrink-0 flex items-center justify-center text-white text-xs font-bold" style="background:${COLORS[i%COLORS.length]}">${d.name[0]}</div>
      <div class="flex-1 min-w-0">
        <div class="flex justify-between text-sm font-medium text-[#3c2f25] mb-1">
          <span class="truncate">${d.name}</span>
          <span class="font-mono shrink-0 ml-2">${pct(own)}</span>
        </div>
        <div class="progress-bar"><div class="progress-fill" style="width:${Math.min(own,100)}%;background:${COLORS[i%COLORS.length]}"></div></div>
      </div>
      <div class="text-xs font-mono text-[#937e63] shrink-0 w-24 text-right">${fmt(d.investment)}</div>
    </div>`;
  }).join('');

  // Activity
  const act = document.getElementById('dash-activity');
  const icons = {
    invest: `<div class="w-7 h-7 rounded-full bg-[#d3f3e3] flex items-center justify-center"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="#158059" stroke-width="2.5"><polyline points="18 15 12 9 6 15"/></svg></div>`,
    withdraw: `<div class="w-7 h-7 rounded-full bg-[#ffe4e6] flex items-center justify-center"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="#be123c" stroke-width="2.5"><polyline points="6 9 12 15 18 9"/></svg></div>`,
    join: `<div class="w-7 h-7 rounded-full bg-[#fef3c7] flex items-center justify-center"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="#b45309" stroke-width="2.5"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="8.5" cy="7" r="4"/><line x1="20" y1="8" x2="20" y2="14"/><line x1="23" y1="11" x2="17" y2="11"/></svg></div>`,
  };
  act.innerHTML = state.activity.slice(0,6).map(a => `
    <div class="flex items-center gap-3 py-2">
      ${icons[a.type] || icons.join}
      <div class="flex-1 text-sm text-[#3c2f25]">${a.msg}</div>
      <div class="text-xs text-[#ad9b82] shrink-0">${a.time}</div>
    </div>`).join('');
}

function renderSettings() {
  const dirs = state.directors;
  const tbody = document.getElementById('settings-table-body');
  const empty = document.getElementById('settings-empty');
  const summary = document.getElementById('settings-summary');

  summary.textContent = `Total: ${fmt(totalInvestment())} | ${pct(totalOwnership())}`;

  if (dirs.length === 0) {
    tbody.innerHTML = '';
    empty.classList.remove('hidden');
    return;
  }
  empty.classList.add('hidden');
  tbody.innerHTML = dirs.map((d, i) => {
    const own = calcOwnership(d.investment);
    return `
    <tr>
      <td>
        <div class="flex items-center gap-2">
          <div class="w-7 h-7 rounded-lg flex items-center justify-center text-white text-xs font-bold shrink-0" style="background:${COLORS[i%COLORS.length]}">${d.name[0]}</div>
          <span class="font-medium">${d.name}</span>
        </div>
      </td>
      <td class="text-right font-mono">${fmt(d.investment)}</td>
      <td class="text-right">
        <span class="font-mono font-semibold" style="color:${COLORS[i%COLORS.length]}">${pct(own)}</span>
      </td>
      <td>
        <div class="flex items-center justify-center gap-1 flex-wrap">
          <button onclick="editDirector(${d.id})" class="btn-sm btn-slate">Edit</button>
          <button onclick="openInvest(${d.id})" class="btn-sm btn-jade">+ Add</button>
          <button onclick="openWithdraw(${d.id})" class="btn-sm btn-amber">− Withdraw</button>
          <button onclick="openDelete(${d.id})" class="btn-sm btn-crimson">Delete</button>
        </div>
      </td>
    </tr>`;
  }).join('');
}

function renderProfiles() {
  const dirs = state.directors;
  const grid = document.getElementById('profiles-grid');
  const empty = document.getElementById('profiles-empty');

  document.getElementById('prof-total-inv').textContent = fmt(totalInvestment());
  document.getElementById('prof-total-own').textContent = pct(totalOwnership());
  document.getElementById('prof-count').textContent = dirs.length;

  if (dirs.length === 0) {
    grid.innerHTML = '';
    empty.classList.remove('hidden');
    return;
  }
  empty.classList.add('hidden');
  grid.innerHTML = dirs.map((d, i) => {
    const own = calcOwnership(d.investment);
    const barW = Math.min(own, 100);
    return `
    <div class="card card-hover p-5" onclick="openProfile(${d.id})">
      <div class="flex items-start justify-between mb-4">
        <div class="w-12 h-12 rounded-2xl flex items-center justify-center text-xl font-display text-white shrink-0" style="background:${COLORS[i%COLORS.length]}">${d.name[0]}</div>
        <span class="badge ${own >= 12.5 ? 'badge-jade' : 'badge-amber'}">${own >= 12.5 ? 'Full Unit+' : 'Sub Unit'}</span>
      </div>
      <h3 class="font-semibold text-[#3c2f25] text-base mb-0.5">${d.name}</h3>
      <div class="font-mono text-sm text-[#937e63] mb-3">${fmt(d.investment)}</div>
      <div class="flex justify-between text-xs text-[#937e63] mb-1">
        <span>Ownership</span>
        <span class="font-mono font-semibold text-[#3c2f25]">${pct(own)}</span>
      </div>
      <div class="progress-bar"><div class="progress-fill" style="width:${barW}%;background:${COLORS[i%COLORS.length]}"></div></div>
      <div class="mt-3 text-xs text-[#ad9b82]">Click to view history →</div>
    </div>`;
  }).join('');
}

// ==================== PROFIT/LOSS ====================
const plData = {
  daily: [
    { period:'Apr 01', amount:12400, note:'Online sales' },
    { period:'Apr 02', amount:-3200, note:'Operations cost' },
    { period:'Apr 03', amount:18700, note:'Bulk order' },
    { period:'Apr 04', amount:9200, note:'Retail' },
    { period:'Apr 05', amount:-1500, note:'Maintenance' },
    { period:'Apr 06', amount:22100, note:'Event revenue' },
    { period:'Apr 07', amount:15300, note:'Online sales' },
  ],
  weekly: [
    { period:'Week 1', amount:85000, note:'January W1' },
    { period:'Week 2', amount:-12000, note:'January W2' },
    { period:'Week 3', amount:110000, note:'February W1' },
    { period:'Week 4', amount:73000, note:'February W2' },
    { period:'Week 5', amount:-8500, note:'March W1' },
    { period:'Week 6', amount:145000, note:'March W2' },
  ],
  monthly: [
    { period:'Jan', amount:240000, note:'Strong month' },
    { period:'Feb', amount:-45000, note:'Off-season' },
    { period:'Mar', amount:310000, note:'Peak season' },
    { period:'Apr', amount:180000, note:'Ongoing' },
    { period:'May', amount:270000, note:'—' },
    { period:'Jun', amount:-30000, note:'Slow period' },
    { period:'Jul', amount:390000, note:'Festival boost' },
  ],
  yearly: [
    { period:'2021', amount:1800000, note:'Growth year' },
    { period:'2022', amount:2400000, note:'Expansion' },
    { period:'2023', amount:-200000, note:'Market downturn' },
    { period:'2024', amount:3100000, note:'Recovery' },
    { period:'2025', amount:2800000, note:'Stable' },
  ],
};

function setPLPeriod(p, btn) {
  plPeriod = p;
  document.querySelectorAll('.toggle-btn').forEach(b => b.classList.remove('selected'));
  btn.classList.add('selected');
  renderPL();
}

function renderPL() {
  const data = plData[plPeriod];
  const profits = data.filter(d => d.amount > 0);
  const losses = data.filter(d => d.amount < 0);
  const daily = data.reduce((s,d) => s+d.amount,0) / data.length;
  const weekly = daily * 7;
  const monthly = daily * 30;
  const yearly = daily * 365;

  function colorFmt(n) {
    const c = n >= 0 ? '#1fa06d' : '#e11d48';
    const el = `<span style="color:${c}">${fmt(Math.abs(n))}</span>`;
    return el;
  }
  document.getElementById('pl-daily').innerHTML = colorFmt(daily);
  document.getElementById('pl-weekly').innerHTML = colorFmt(weekly);
  document.getElementById('pl-monthly').innerHTML = colorFmt(monthly);
  document.getElementById('pl-yearly').innerHTML = colorFmt(yearly);

  document.getElementById('pl-chart-title').textContent =
    { daily:'Daily', weekly:'Weekly', monthly:'Monthly', yearly:'Yearly' }[plPeriod] + ' Profit / Loss Trend';

  // Chart
  drawChart(data);

  // Table
  const tbody = document.getElementById('pl-table');
  tbody.innerHTML = data.map(d => `
    <tr>
      <td class="font-medium">${d.period}</td>
      <td class="text-right font-mono ${d.amount>=0?'text-[#158059]':'text-[#e11d48]'}">${d.amount>=0?'+':'−'}${fmt(Math.abs(d.amount))}</td>
      <td class="text-center"><span class="badge ${d.amount>=0?'badge-jade':'badge-crimson'}">${d.amount>=0?'Profit':'Loss'}</span></td>
      <td class="text-[#937e63]">${d.note}</td>
    </tr>`).join('');
}

function drawChart(data) {
  const canvas = document.getElementById('plChart');
  const ctx = canvas.getContext('2d');
  const W = canvas.parentElement.clientWidth - 40;
  const H = 220;
  canvas.width = W * window.devicePixelRatio;
  canvas.height = H * window.devicePixelRatio;
  canvas.style.width = W + 'px';
  canvas.style.height = H + 'px';
  ctx.scale(window.devicePixelRatio, window.devicePixelRatio);
  ctx.clearRect(0, 0, W, H);

  const amounts = data.map(d => d.amount);
  const max = Math.max(...amounts.map(Math.abs)) * 1.15;
  const mid = H / 2;
  const pad = { l:10, r:10, t:10 };
  const bw = (W - pad.l - pad.r) / data.length;

  // Grid
  ctx.strokeStyle = '#eeeae2';
  ctx.lineWidth = 1;
  [0.25, 0.5, 0.75, 1].forEach(f => {
    const y = pad.t + f * (H - pad.t - 20);
    ctx.beginPath(); ctx.moveTo(pad.l, y); ctx.lineTo(W-pad.r, y); ctx.stroke();
  });

  // Zero line
  ctx.strokeStyle = '#c8bca5';
  ctx.lineWidth = 1.5;
  ctx.setLineDash([4,3]);
  ctx.beginPath(); ctx.moveTo(pad.l, mid); ctx.lineTo(W-pad.r, mid); ctx.stroke();
  ctx.setLineDash([]);

  // Bars
  data.forEach((d, i) => {
    const x = pad.l + i * bw + bw * 0.15;
    const bWidth = bw * 0.7;
    const h = Math.abs(d.amount) / max * (mid - pad.t);
    const y = d.amount >= 0 ? mid - h : mid;

    const grad = ctx.createLinearGradient(0, y, 0, y + (d.amount >= 0 ? h : -h));
    if (d.amount >= 0) {
      grad.addColorStop(0, '#1fa06d');
      grad.addColorStop(1, '#aae7cb');
    } else {
      grad.addColorStop(0, '#fecdd3');
      grad.addColorStop(1, '#e11d48');
    }
    ctx.fillStyle = grad;
    ctx.beginPath();
    ctx.roundRect(x, d.amount >= 0 ? mid - h : mid, bWidth, h, 4);
    ctx.fill();

    // Label
    ctx.fillStyle = '#937e63';
    ctx.font = '10px DM Sans, sans-serif';
    ctx.textAlign = 'center';
    ctx.fillText(d.period, x + bWidth/2, H - 4);
  });
}

// ==================== DIVIDEND ====================
function renderDividend() {
  calcDividends();
}

function calcDividends() {
  const profit = parseFloat(document.getElementById('div-profit').value) || 0;
  const dirs = state.directors;
  const tbody = document.getElementById('div-table');
  const empty = document.getElementById('div-empty');
  const badge = document.getElementById('div-total-badge');

  if (dirs.length === 0) {
    tbody.innerHTML = '';
    empty.style.display = 'block';
    return;
  }
  empty.style.display = 'none';

  const total = dirs.reduce((s, d) => s + (calcOwnership(d.investment)/100)*profit, 0);
  badge.textContent = profit ? `Total Disbursed: ${fmt(total)}` : '';

  tbody.innerHTML = dirs.map((d, i) => {
    const own = calcOwnership(d.investment);
    const div = (own / 100) * profit;
    return `
    <tr>
      <td>
        <div class="flex items-center gap-2">
          <div class="w-6 h-6 rounded-lg flex items-center justify-center text-white text-xs font-bold" style="background:${COLORS[i%COLORS.length]}">${d.name[0]}</div>
          ${d.name}
        </div>
      </td>
      <td class="text-right font-mono">${fmt(d.investment)}</td>
      <td class="text-right font-mono font-semibold" style="color:${COLORS[i%COLORS.length]}">${pct(own)}</td>
      <td class="text-right font-mono font-semibold text-[#158059]">${profit ? fmt(Math.round(div)) : '—'}</td>
    </tr>`;
  }).join('');
}

function disburseModal() {
  const profit = parseFloat(document.getElementById('div-profit').value) || 0;
  if (!profit) { showNotif('Enter a profit amount first.', 'warn'); return; }
  const summary = document.getElementById('disburse-summary');
  summary.innerHTML = `
    <div class="flex justify-between text-sm"><span class="text-[#937e63]">Total Profit:</span><span class="font-mono font-semibold">${fmt(profit)}</span></div>
    <div class="flex justify-between text-sm"><span class="text-[#937e63]">Directors:</span><span class="font-mono font-semibold">${state.directors.length}</span></div>
    <div class="flex justify-between text-sm"><span class="text-[#937e63]">Total Ownership:</span><span class="font-mono font-semibold">${pct(totalOwnership())}</span></div>
  ` + state.directors.map((d,i) => {
    const div = (calcOwnership(d.investment)/100)*profit;
    return `<div class="flex justify-between text-xs py-1 border-t border-[#eeeae2]"><span>${d.name}</span><span class="font-mono text-[#158059]">${fmt(Math.round(div))}</span></div>`;
  }).join('');
  openModal('modal-disburse');
}

function confirmDisburse() {
  closeModal('modal-disburse');
  showNotif('Dividends successfully disbursed!', 'success');
  state.activity.unshift({ msg:`Dividend of ${fmt(parseFloat(document.getElementById('div-profit').value)||0)} disbursed`, time:'Just now', type:'invest' });
}

// ==================== SETTINGS ACTIONS ====================
document.getElementById('dir-amount').addEventListener('input', function() {
  const amt = parseFloat(this.value) || 0;
  const own = calcOwnership(amt);
  document.getElementById('calc-preview').textContent = pct(own);
  document.getElementById('calc-bar').style.width = Math.min(own, 100) + '%';
});

function saveDirector() {
  const name = document.getElementById('dir-name').value.trim();
  const amount = parseFloat(document.getElementById('dir-amount').value) || 0;
  const editId = document.getElementById('edit-id').value;

  if (!name) { showNotif('Please enter a name.', 'warn'); return; }
  if (amount <= 0) { showNotif('Investment must be > 0.', 'warn'); return; }

  if (editId) {
    const d = state.directors.find(d => d.id == editId);
    if (d) {
      d.name = name;
      d.investment = amount;
      d.history.push({ date: today(), type:'edit', amount, note:'Investment updated' });
      state.activity.unshift({ msg:`${name} investment updated to ${fmt(amount)}`, time:'Just now', type:'invest' });
      showNotif(`${name} updated.`, 'success');
    }
    cancelEdit();
  } else {
    if (state.directors.find(d => d.name.toLowerCase() === name.toLowerCase())) {
      showNotif('Director with this name already exists.', 'warn'); return;
    }
    state.directors.push({ id: state.nextId++, name, investment: amount, history:[{ date:today(), type:'invest', amount, note:'Initial investment' }] });
    state.activity.unshift({ msg:`New director ${name} added`, time:'Just now', type:'join' });
    showNotif(`${name} added successfully.`, 'success');
  }

  document.getElementById('dir-name').value = '';
  document.getElementById('dir-amount').value = '';
  document.getElementById('calc-preview').textContent = '—';
  document.getElementById('calc-bar').style.width = '0%';
  renderSettings();
}

function editDirector(id) {
  const d = state.directors.find(d => d.id === id);
  if (!d) return;
  document.getElementById('edit-id').value = id;
  document.getElementById('dir-name').value = d.name;
  document.getElementById('dir-amount').value = d.investment;
  document.getElementById('settings-form-title').textContent = 'Edit Director';
  document.getElementById('save-btn-txt').textContent = 'Save Changes';
  document.getElementById('cancel-btn').style.display = 'block';

  const own = calcOwnership(d.investment);
  document.getElementById('calc-preview').textContent = pct(own);
  document.getElementById('calc-bar').style.width = Math.min(own,100) + '%';
  document.getElementById('dir-name').focus();
}

function cancelEdit() {
  document.getElementById('edit-id').value = '';
  document.getElementById('dir-name').value = '';
  document.getElementById('dir-amount').value = '';
  document.getElementById('settings-form-title').textContent = 'Add Director';
  document.getElementById('save-btn-txt').textContent = 'Add Director';
  document.getElementById('cancel-btn').style.display = 'none';
  document.getElementById('calc-preview').textContent = '—';
  document.getElementById('calc-bar').style.width = '0%';
}

// ==================== INVEST / WITHDRAW ====================
function openInvest(id) {
  modalTargetId = id;
  const d = state.directors.find(d => d.id === id);
  document.getElementById('modal-invest-name').textContent = d.name;
  document.getElementById('mi-current').textContent = fmt(d.investment);
  document.getElementById('mi-own').textContent = pct(calcOwnership(d.investment));
  document.getElementById('mi-amount').value = '';
  document.getElementById('mi-preview').classList.add('hidden');
  openModal('modal-invest');
}

function previewInvest() {
  const d = state.directors.find(d => d.id === modalTargetId);
  const add = parseFloat(document.getElementById('mi-amount').value) || 0;
  const prev = document.getElementById('mi-preview');
  if (add > 0) {
    const newInv = d.investment + add;
    document.getElementById('mi-new-inv').textContent = fmt(newInv);
    document.getElementById('mi-new-own').textContent = pct(calcOwnership(newInv));
    prev.classList.remove('hidden');
  } else { prev.classList.add('hidden'); }
}

function confirmAddInvest() {
  const d = state.directors.find(d => d.id === modalTargetId);
  const add = parseFloat(document.getElementById('mi-amount').value) || 0;
  if (add <= 0) { showNotif('Enter a valid amount.', 'warn'); return; }
  d.investment += add;
  d.history.push({ date:today(), type:'invest', amount:add, note:'Additional investment' });
  state.activity.unshift({ msg:`${d.name} added ${fmt(add)} to investment`, time:'Just now', type:'invest' });
  showNotif(`${fmt(add)} added to ${d.name}.`, 'success');
  closeModal('modal-invest');
  renderSettings();
}

function openWithdraw(id) {
  modalTargetId = id;
  const d = state.directors.find(d => d.id === id);
  document.getElementById('modal-withdraw-name').textContent = d.name;
  document.getElementById('mw-current').textContent = fmt(d.investment);
  document.getElementById('mw-own').textContent = pct(calcOwnership(d.investment));
  document.getElementById('mw-amount').value = '';
  document.getElementById('mw-preview').classList.add('hidden');
  document.getElementById('mw-error').classList.add('hidden');
  openModal('modal-withdraw');
}

function previewWithdraw() {
  const d = state.directors.find(d => d.id === modalTargetId);
  const amt = parseFloat(document.getElementById('mw-amount').value) || 0;
  const prev = document.getElementById('mw-preview');
  const err = document.getElementById('mw-error');
  if (amt > d.investment) { prev.classList.add('hidden'); err.classList.remove('hidden'); return; }
  err.classList.add('hidden');
  if (amt > 0) {
    const newInv = d.investment - amt;
    document.getElementById('mw-new-inv').textContent = fmt(newInv);
    document.getElementById('mw-new-own').textContent = pct(calcOwnership(newInv));
    prev.classList.remove('hidden');
  } else { prev.classList.add('hidden'); }
}

function confirmWithdraw() {
  const d = state.directors.find(d => d.id === modalTargetId);
  const amt = parseFloat(document.getElementById('mw-amount').value) || 0;
  if (amt <= 0) { showNotif('Enter a valid amount.', 'warn'); return; }
  if (amt > d.investment) { showNotif('Exceeds current investment.', 'warn'); return; }
  d.investment -= amt;
  d.history.push({ date:today(), type:'withdraw', amount:amt, note:'Withdrawal' });
  state.activity.unshift({ msg:`${d.name} withdrew ${fmt(amt)}`, time:'Just now', type:'withdraw' });
  showNotif(`${fmt(amt)} withdrawn from ${d.name}.`, 'success');
  closeModal('modal-withdraw');
  renderSettings();
}

// ==================== DELETE ====================
function openDelete(id) {
  modalTargetId = id;
  const d = state.directors.find(d => d.id === id);
  document.getElementById('delete-modal-name').textContent = `Are you sure you want to delete "${d.name}"?`;
  openModal('modal-delete');
}

function confirmDelete() {
  const d = state.directors.find(d => d.id === modalTargetId);
  if (!d) return;
  state.directors = state.directors.filter(x => x.id !== modalTargetId);
  state.activity.unshift({ msg:`${d.name} was removed`, time:'Just now', type:'join' });
  showNotif(`${d.name} removed.`, 'success');
  closeModal('modal-delete');
  renderSettings();
}

// ==================== PROFILE MODAL ====================
function openProfile(id) {
  const d = state.directors.find(d => d.id === id);
  const i = state.directors.indexOf(d);
  const own = calcOwnership(d.investment);
  document.getElementById('prof-modal-avatar').textContent = d.name[0];
  document.getElementById('prof-modal-avatar').style.background = COLORS[i % COLORS.length];
  document.getElementById('prof-modal-name').textContent = d.name;
  document.getElementById('prof-modal-own').textContent = `${pct(own)} ownership`;
  document.getElementById('prof-modal-inv').textContent = fmt(d.investment);
  document.getElementById('prof-modal-pct').textContent = pct(own);

  const histHTML = (d.history || []).map(h => {
    const icon = h.type === 'invest' ? '↑' : '↓';
    const c = h.type === 'invest' ? 'text-[#158059]' : 'text-[#e11d48]';
    return `
    <div class="flex items-center gap-3 bg-[#f7f6f3] rounded-xl px-3 py-2">
      <div class="w-7 h-7 rounded-full flex items-center justify-center font-bold text-sm ${c} bg-white border border-[#eeeae2]">${icon}</div>
      <div class="flex-1 min-w-0">
        <div class="text-sm font-medium text-[#3c2f25]">${h.note}</div>
        <div class="text-xs text-[#937e63]">${h.date}</div>
      </div>
      <div class="font-mono text-sm font-semibold ${c}">${fmt(h.amount)}</div>
    </div>`;
  }).join('') || `<div class="text-sm text-[#ad9b82] text-center py-4">No history yet.</div>`;

  document.getElementById('prof-modal-history').innerHTML = histHTML;
  openModal('modal-profile');
}

// ==================== MODAL HELPERS ====================
function openModal(id) {
  document.getElementById(id).classList.add('open');
}
function closeModal(id) {
  document.getElementById(id).classList.remove('open');
}

// ==================== NOTIFICATION ====================
let notifTimer;
function showNotif(msg, type='success') {
  const el = document.getElementById('notif');
  const icons = {
    success: `<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#158059" stroke-width="2.5" stroke-linecap="round"><polyline points="20 6 9 17 4 12"/></svg>`,
    warn: `<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#d97706" stroke-width="2.5" stroke-linecap="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>`,
  };
  document.getElementById('notif-icon').innerHTML = icons[type] || icons.success;
  document.getElementById('notif-msg').textContent = msg;
  el.classList.add('show');
  clearTimeout(notifTimer);
  notifTimer = setTimeout(() => el.classList.remove('show'), 3200);
}

// ==================== UTILS ====================
function today() {
  return new Date().toISOString().slice(0, 10);
}

// ==================== INIT ====================
renderPage('dashboard');
window.addEventListener('resize', () => { if (currentPage === 'profitloss') renderPL(); });
</script>
</body>
</html>