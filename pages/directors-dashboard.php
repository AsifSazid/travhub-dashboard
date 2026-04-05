<?php
include_once('./authenticate.php');
$ip_port = @file_get_contents('../ippath.txt');
$basePrice = @file_get_contents('../base-price.txt');
$cleanBasePrice = str_replace(',', '', $basePrice);
if (empty($ip_port)) {
    $ip_port = "http://103.104.219.3:898";
}
$base_ip_path = trim($ip_port, "/");
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
  <title>TravHub — Director Accounting System</title>

  <!-- Google Fonts -->
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

  <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <!-- TailwindCSS CDN -->
  <script src="https://cdn.tailwindcss.com"></script>

  <!-- Tailwind Config -->
  <script>
    tailwind.config = {
      theme: {
        extend: {
          fontFamily: {
            sans: ['Poppins', 'system-ui', 'sans-serif']
          },
          colors: {
            navy: {
              DEFAULT: '#1A2039',
              light: '#232d4e',
              lighter: '#2d3a5e',
            },
            brand: {
              DEFAULT: '#50BC81',
              dark: '#3da066',
              pale: 'rgba(80,188,129,0.12)',
            }
          }
        }
      }
    }
  </script>

  <!-- Custom CSS — শুধু যেটুকু Tailwind দিয়ে সম্ভব না -->
    <link rel="stylesheet" href="../assets/css/directors-dashboard.css">
</head>

<body class="bg-[#F4F4F4] font-sans text-navy flex min-h-screen">

<!-- Side overlay -->
<div id="sideOverlay" onclick="closeSidebar()"></div>

<!-- ════════ SIDEBAR ════════ -->
<aside id="sidebar" class="w-64 bg-navy flex flex-col flex-shrink-0 min-h-screen sticky top-0">

  <!-- Logo -->
  <div class="px-5 py-[22px] border-b border-white/[0.07]">
    <div class="flex items-center gap-3">
      <div class="w-9 h-9 rounded-[10px] bg-brand flex items-center justify-center flex-shrink-0">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2.5" stroke-linecap="round"><path d="M12 2L2 7l10 5 10-5-10-5z"/><path d="M2 17l10 5 10-5"/><path d="M2 12l10 5 10-5"/></svg>
      </div>
      <div>
        <div class="text-white font-bold text-base leading-tight">TravHub</div>
        <div class="text-white/35 text-[10px] font-medium tracking-wide">Director Portal</div>
      </div>
    </div>
  </div>

  <!-- Nav -->
  <nav class="flex-1 px-3 py-4 overflow-y-auto">
    <div class="text-[10px] font-semibold tracking-widest uppercase text-white/25 px-3 pt-2 pb-1.5">Main</div>

    <?php
    $navItems = [
      ['id'=>'dashboard',  'label'=>'Dashboard',         'icon'=>'<rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/>'],
      ['id'=>'settings',   'label'=>'Director Settings', 'icon'=>'<path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>'],
      ['id'=>'profiles',   'label'=>'Director Profiles', 'icon'=>'<circle cx="12" cy="8" r="4"/><path d="M4 20c0-4 3.6-7 8-7s8 3 8 7"/>'],
    ];
    foreach ($navItems as $n): ?>
    <div class="nav-link flex items-center gap-3 px-4 py-[11px] rounded-[10px] text-[13.5px] font-medium text-white/55 cursor-pointer transition-all hover:text-white hover:bg-white/[0.07] my-0.5 <?= $n['id']==='dashboard'?'active':'' ?>"
         onclick="nav('<?= $n['id'] ?>')" data-page="<?= $n['id'] ?>">
      <svg class="w-[17px] h-[17px] flex-shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <?= $n['icon'] ?>
      </svg>
      <?= $n['label'] ?>
    </div>
    <?php endforeach; ?>

    <div class="text-[10px] font-semibold tracking-widest uppercase text-white/25 px-3 pt-5 pb-1.5">Finance</div>

    <div class="nav-link flex items-center gap-3 px-4 py-[11px] rounded-[10px] text-[13.5px] font-medium text-white/55 cursor-pointer transition-all hover:text-white hover:bg-white/[0.07] my-0.5"
         onclick="nav('profitloss')" data-page="profitloss">
      <svg class="w-[17px] h-[17px] flex-shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <polyline points="22 7 13.5 15.5 8.5 10.5 2 17"/><polyline points="16 7 22 7 22 13"/>
      </svg>
      Profit / Loss
    </div>

    <div class="nav-link flex items-center gap-3 px-4 py-[11px] rounded-[10px] text-[13.5px] font-medium text-white/55 cursor-pointer transition-all hover:text-white hover:bg-white/[0.07] my-0.5"
         onclick="nav('dividend')" data-page="dividend">
      <svg class="w-[17px] h-[17px] flex-shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>
      </svg>
      Dividend
    </div>
  </nav>

  <!-- Bottom user strip -->
  <div class="border-t border-[#334155] p-4">
    <div class="flex items-center justify-between">
      <div class="flex items-center min-w-0 gap-2.5">
        <div class="w-[34px] h-[34px] rounded-[10px] bg-brand flex items-center justify-center text-[13px] font-bold text-navy flex-shrink-0"><?php echo $initialName ?></div>
        <div class="overflow-hidden">
          <p class="text-white font-medium truncate"><?php echo $_SESSION['user_name'] ?></p>
          <p class="text-[#9ca3af] text-xs truncate">Managing Director</p>
        </div>
      </div>
      <a href="../auth/logout.php" class="flex items-center justify-center w-9 h-9 rounded-lg hover:bg-white/[0.08] transition-colors flex-shrink-0" title="Logout">
        <i class="fa-solid fa-arrow-right-from-bracket text-white/60 text-sm"></i>
      </a>
    </div>
  </div>
</aside>

<!-- ════════ MAIN ════════ -->
<div class="flex-1 flex flex-col min-h-screen overflow-hidden">

  <!-- Topbar -->
  <header class="bg-white border-b border-[#e8eaf0] px-5 py-[13px] flex items-center justify-between sticky top-0 z-50">
    <div class="flex items-center gap-3">
      <button onclick="openSidebar()" id="burger" class="hidden bg-transparent border-none cursor-pointer p-1 rounded-lg hover:bg-[#f4f4f4] transition-colors">
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#1A2039" stroke-width="2" stroke-linecap="round">
          <line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/>
        </svg>
      </button>
      <div>
        <div id="pgTitle" class="text-[20px] font-bold text-navy leading-tight">Director's Dashboard</div>
        <div id="pgSub" class="text-xs text-navy/45 font-normal">Overview of all director accounts</div>
      </div>
    </div>
    <div class="flex items-center gap-2">
      <div class="hidden sm:flex items-center gap-2 bg-[#F4F4F4] border border-[#e8eaf0] rounded-[10px] px-3 py-2">
        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="rgba(26,32,57,0.4)" stroke-width="2" stroke-linecap="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
        <input id="global-search" class="bg-transparent border-none outline-none font-sans text-[13px] text-navy w-28" placeholder="Search..." oninput="globalSearch(this.value)" />
      </div>
      <div class="w-9 h-9 rounded-[10px] bg-navy flex items-center justify-center text-xs font-bold text-brand"><?php echo $initialName ?></div>
    </div>
  </header>

  <!-- Pages container -->
  <main class="flex-1 overflow-y-auto p-5 lg:p-6">

    <!-- ══ DASHBOARD ══ -->
    <div id="page-dashboard" class="page active">

      <!-- KPI cards -->
      <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-5">

        <div class="bg-navy rounded-2xl p-5 shadow-sm col-span-2 lg:col-span-1">
          <div class="text-white/55 text-[11px] font-semibold mb-1 uppercase tracking-wide">Total Investment</div>
          <div id="d-tot-inv" class="text-white text-[22px] font-bold mb-0.5">—</div>
          <div class="text-white/40 text-xs mb-3">All directors combined</div>
          <div class="flex items-center gap-1.5">
            <div class="w-[20px] h-[20px] rounded-md bg-brand/20 flex items-center justify-center">
              <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="#50BC81" stroke-width="2.5" stroke-linecap="round"><polyline points="18 15 12 9 6 15"/></svg>
            </div>
            <span class="text-[11px] text-white/50 font-medium">Base unit: <?php echo $basePrice; ?> = 12.5%</span>
          </div>
        </div>

        <div class="bg-white rounded-2xl border border-[#e8eaf0] p-5 shadow-sm">
          <div class="text-navy/45 text-[11px] font-semibold mb-1 uppercase tracking-wide">Total Ownership</div>
          <div id="d-tot-own" class="text-[22px] font-bold text-navy mb-0.5">—</div>
          <div class="text-navy/40 text-xs mb-3">Unit-based calculation</div>
          <div class="prog-track"><div class="prog-fill" id="d-own-bar" style="width:0%"></div></div>
        </div>

        <div class="bg-white rounded-2xl border border-[#e8eaf0] p-5 shadow-sm">
          <div class="text-navy/45 text-[11px] font-semibold mb-1 uppercase tracking-wide">Total Directors</div>
          <div id="d-tot-dir" class="text-[22px] font-bold text-navy mb-0.5">—</div>
          <div class="text-navy/40 text-xs">Active members</div>
        </div>

        <div class="bg-white rounded-2xl border border-[#e8eaf0] p-5 shadow-sm">
          <div class="text-navy/45 text-[11px] font-semibold mb-1 uppercase tracking-wide">Base Unit</div>
          <div class="text-[22px] font-bold text-navy mb-0.5"><?php echo $basePrice; ?></div>
          <div class="text-navy/40 text-xs">= 12.5% ownership</div>
        </div>
      </div>

      <!-- Mid row -->
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
        <!-- Ownership snapshot -->
        <div class="bg-white rounded-2xl border border-[#e8eaf0] shadow-sm p-5">
          <div class="flex items-center justify-between mb-4">
            <div class="text-[15px] font-bold text-navy">Ownership Snapshot</div>
            <button onclick="nav('profiles')" class="text-[11.5px] font-semibold text-navy border border-[#d4d7e3] rounded-lg px-3 py-1.5 hover:border-navy transition-colors bg-transparent cursor-pointer">View All →</button>
          </div>
          <div id="d-snap"></div>
        </div>

        <!-- Recent Activity -->
        <div class="bg-white rounded-2xl border border-[#e8eaf0] shadow-sm p-5">
          <div class="text-[15px] font-bold text-navy mb-4">Recent Transactions</div>
          <div id="d-act"></div>
        </div>
      </div>
    </div>

    <!-- ══ SETTINGS ══ -->
    <div id="page-settings" class="page">

      <!-- Info banner -->
      <div class="flex items-start gap-3 bg-brand/10 border border-brand/25 rounded-xl px-4 py-3 mb-5">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#3da066" stroke-width="2" stroke-linecap="round" class="mt-0.5 flex-shrink-0"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
        <div class="text-[12.5px] text-navy/70">
          <strong class="text-navy">Unit-Based Ownership:</strong> Base Unit = <?php echo $basePrice; ?> → 12.5% &nbsp;|&nbsp; Formula: (Investment ÷ <?php echo $basePrice; ?>) × 12.5%
        </div>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-[320px_1fr] gap-5">

          <!-- Add / Edit Form -->
          <div class="bg-white rounded-2xl border border-[#e8eaf0] shadow-sm p-6">
            <div id="form-title" class="text-[16px] font-bold text-navy mb-5">Add Director</div>
            <input type="hidden" id="edit-id" value="" />
        
            <div class="form-group">
              <label class="inp-label">Full Name *</label>
              <input type="text" id="f-name" class="inp" placeholder="e.g. Arif Rahman" />
            </div>
            <div class="form-group">
              <label class="inp-label">Email *</label>
              <input type="email" id="f-email" class="inp" placeholder="e.g. arif@example.com" />
            </div>
            <div class="form-group">
              <label class="inp-label">Phone *</label>
              <input type="text" id="f-phone" class="inp" placeholder="e.g. 01700000000" />
            </div>
            <div class="form-group">
              <label class="inp-label">Invest Amount *</label>
              <input type="text" id="f-invest-amount" class="inp" placeholder="e.g. 1200000" />
            </div>
            
            <!-- Address Fields -->
            <div class="form-group">
              <label class="inp-label">Address Line 1</label>
              <input type="text" id="f-address-01" class="inp" placeholder="e.g. House #12, Road #5" />
            </div>
            <div class="form-group">
              <label class="inp-label">Address Line 2</label>
              <input type="text" id="f-address-02" class="inp" placeholder="e.g. Sector #10" />
            </div>
            <div class="form-group">
              <label class="inp-label">City</label>
              <input type="text" id="f-address-city" class="inp" placeholder="e.g. Dhaka" />
            </div>
            <div class="form-group">
              <label class="inp-label">State</label>
              <input type="text" id="f-address-state" class="inp" placeholder="e.g. Dhaka Division" />
            </div>
            <div class="form-group">
              <label class="inp-label">ZIP Code</label>
              <input type="text" id="f-address-zip" class="inp" placeholder="e.g. 1212" />
            </div>
            <div class="form-group">
              <label class="inp-label">Country</label>
              <input type="text" id="f-address-country" class="inp" placeholder="e.g. Bangladesh" />
            </div>
            
            <div class="form-group">
              <label class="inp-label">Status</label>
              <select id="f-status" class="inp">
                <option value="active">Active</option>
                <option value="inactive">Inactive</option>
                <option value="suspended">Suspended</option>
              </select>
            </div>
        
            <!-- Ownership preview (only visible on edit with investment) -->
            <div id="form-own-preview" class="bg-[#F4F4F4] rounded-xl p-4 mb-5" style="display:none">
              <div class="text-[11.5px] font-semibold text-navy/50 mb-2">Current Investment</div>
              <div class="flex justify-between items-center">
                <span id="form-own-inv" class="text-[15px] font-bold text-navy">—</span>
                <span id="form-own-pct" class="text-[13px] font-bold text-brand">—</span>
              </div>
              <div class="prog-track mt-2"><div class="prog-fill" id="form-own-bar" style="width:0%"></div></div>
            </div>
        
            <button onclick="saveDir()" id="save-btn" class="w-full bg-brand text-white rounded-[10px] font-semibold text-[13px] py-2.5 border-none cursor-pointer hover:bg-brand-dark transition-colors flex items-center justify-center gap-2">
              <span id="save-lbl">Add Director</span>
            </button>
            <button onclick="cancelEdit()" id="cancel-btn" class="mt-2 w-full bg-transparent text-navy border border-[#d4d7e3] rounded-[10px] font-semibold text-[13px] py-2.5 cursor-pointer hover:border-navy transition-colors" style="display:none">Cancel</button>
          </div>
        
          <!-- Directors Table -->
          <div class="bg-white rounded-2xl border border-[#e8eaf0] shadow-sm overflow-hidden">
            <div class="flex items-center justify-between px-5 py-4 border-b border-[#f0f2f7]">
              <div class="text-[16px] font-bold text-navy">Directors</div>
              <div id="s-summary" class="text-[12px] font-medium text-navy/45"></div>
            </div>
            <div class="table-scroll">
              <table>
                <thead>
                  <tr>
                    <th class="th-left">Name</th>
                    <th class="th-right">Investment (BDT)</th>
                    <th class="th-right">Ownership %</th>
                    <th class="th-center">Status</th>
                    <th class="th-center">Actions</th>
                  </tr>
                </thead>
                <tbody id="s-tbody">
                  <tr><td colspan="5" class="text-center py-10 text-navy/35"><div class="section-loader"><div class="spin"></div></div></td></tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>
    </div>

    <!-- ══ PROFILES ══ -->
    <div id="page-profiles" class="page">
      <div class="grid grid-cols-3 gap-4 mb-5">
        <div class="bg-white rounded-2xl border border-[#e8eaf0] shadow-sm p-5">
          <div class="text-[11px] font-semibold text-navy/45 mb-1 uppercase tracking-wide">Total Investment</div>
          <div id="p-inv" class="text-2xl font-bold text-navy">—</div>
        </div>
        <div class="bg-white rounded-2xl border border-[#e8eaf0] shadow-sm p-5">
          <div class="text-[11px] font-semibold text-navy/45 mb-1 uppercase tracking-wide">Total Ownership</div>
          <div id="p-own" class="text-2xl font-bold text-brand">—</div>
        </div>
        <div class="bg-white rounded-2xl border border-[#e8eaf0] shadow-sm p-5">
          <div class="text-[11px] font-semibold text-navy/45 mb-1 uppercase tracking-wide">Directors</div>
          <div id="p-cnt" class="text-2xl font-bold text-navy">—</div>
        </div>
      </div>
      <div id="prof-grid" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
        <div class="section-loader col-span-4"><div class="spin"></div></div>
      </div>
    </div>

    <!-- ══ PROFIT / LOSS ══ -->
    <div id="page-profitloss" class="page">

      <!-- Add P&L Entry -->
      <div class="bg-white rounded-2xl border border-[#e8eaf0] shadow-sm p-6 mb-5">
        <div class="text-[16px] font-bold text-navy mb-4">Record Profit / Loss</div>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 items-end">
          <div>
            <label class="inp-label">Type</label>
            <select id="pl-type" class="inp">
              <option value="profit">Profit</option>
              <option value="loss">Loss</option>
            </select>
          </div>
          <div>
            <label class="inp-label">Amount (BDT)</label>
            <input type="number" id="pl-amount" class="inp" placeholder="e.g. 50000" min="1" />
          </div>
          <div>
            <label class="inp-label">Date</label>
            <input type="date" id="pl-date" class="inp" />
          </div>
          <div>
            <label class="inp-label">Note</label>
            <input type="text" id="pl-note" class="inp" placeholder="Optional note" />
          </div>
        </div>
        <button onclick="savePL()" class="mt-4 bg-brand text-white rounded-[10px] font-semibold text-[13px] px-6 py-2.5 border-none cursor-pointer hover:bg-brand-dark transition-colors">Add Entry</button>
      </div>

      <!-- KPI row -->
      <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-5">
        <div class="bg-white rounded-2xl border border-[#e8eaf0] shadow-sm p-5">
          <div class="text-[11px] font-semibold text-navy/45 mb-1 uppercase tracking-wide">Total Profit</div>
          <div id="pl-tp" class="text-xl font-bold text-[#16a34a]">—</div>
        </div>
        <div class="bg-white rounded-2xl border border-[#e8eaf0] shadow-sm p-5">
          <div class="text-[11px] font-semibold text-navy/45 mb-1 uppercase tracking-wide">Total Loss</div>
          <div id="pl-tl" class="text-xl font-bold text-red-500">—</div>
        </div>
        <div class="bg-white rounded-2xl border border-[#e8eaf0] shadow-sm p-5">
          <div class="text-[11px] font-semibold text-navy/45 mb-1 uppercase tracking-wide">Net</div>
          <div id="pl-net" class="text-xl font-bold text-navy">—</div>
        </div>
        <div class="bg-white rounded-2xl border border-[#e8eaf0] shadow-sm p-5">
          <div class="text-[11px] font-semibold text-navy/45 mb-1 uppercase tracking-wide">Entries</div>
          <div id="pl-cnt" class="text-xl font-bold text-navy">—</div>
        </div>
      </div>

      <!-- Chart -->
      <div class="bg-white rounded-2xl border border-[#e8eaf0] shadow-sm p-6 mb-5">
        <div class="flex items-center justify-between mb-4 flex-wrap gap-2">
          <div id="pl-chart-label" class="text-[16px] font-bold text-navy">Monthly Overview</div>
          <div class="flex items-center gap-2">
            <?php foreach(['daily','weekly','monthly','yearly'] as $p): ?>
            <button onclick="loadPL('<?=$p?>')" data-period="<?=$p?>" class="pl-period-btn text-[11.5px] font-semibold px-3 py-1 rounded-lg border border-[#e8eaf0] text-navy/55 hover:border-brand hover:text-brand transition-colors bg-transparent cursor-pointer <?=$p==='monthly'?'border-brand text-brand':''?>">
              <?=ucfirst($p)?>
            </button>
            <?php endforeach; ?>
          </div>
        </div>
        <canvas id="plChart" height="200"></canvas>
      </div>

      <!-- Records table -->
      <div class="bg-white rounded-2xl border border-[#e8eaf0] shadow-sm overflow-hidden">
        <div class="px-5 py-4 border-b border-[#f0f2f7] text-[16px] font-bold text-navy">Transaction Records</div>
        <div class="table-scroll">
          <table>
            <thead>
              <tr>
                <th class="th-left">Date</th>
                <th class="th-right">Amount (BDT)</th>
                <th class="th-center">Type</th>
                <th class="th-left">Note</th>
              </tr>
            </thead>
            <tbody id="pl-tbody">
              <tr><td colspan="4" class="text-center py-8 text-navy/35"><div class="section-loader"><div class="spin"></div></div></td></tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- ══ DIVIDEND ══ -->
    <div id="page-dividend" class="page">

      <!-- Calculator -->
      <div class="bg-white rounded-2xl border border-[#e8eaf0] shadow-sm p-6 mb-5">
        <div class="text-[16px] font-bold text-navy mb-4">Dividend Calculator</div>
        <div class="flex flex-wrap gap-3 items-end">
          <div class="flex-1 min-w-[220px]">
            <label class="inp-label">Total Profit Amount (BDT)</label>
            <input type="number" id="div-profit" class="inp" placeholder="e.g. 240000" min="1" oninput="calcDiv()" />
          </div>
          <div class="flex-1 min-w-[220px]">
            <label class="inp-label">Note (optional)</label>
            <input type="text" id="div-note" class="inp" placeholder="e.g. Q1 2025 dividend" />
          </div>
          <button onclick="disburseDiv()" class="bg-brand text-white rounded-[10px] font-semibold text-[13px] px-5 py-2.5 border-none cursor-pointer hover:bg-brand-dark transition-colors flex items-center gap-2">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><polyline points="20 6 9 17 4 12"/></svg>
            Disburse & Save
          </button>
        </div>
        <div class="text-[11.5px] text-navy/45 mt-2.5">Formula: Dividend = (Ownership% ÷ 100) × Total Profit</div>
      </div>

      <!-- Breakdown table -->
      <div class="bg-white rounded-2xl border border-[#e8eaf0] shadow-sm overflow-hidden mb-5">
        <div class="flex items-center justify-between px-5 py-4 border-b border-[#f0f2f7]">
          <div class="text-[16px] font-bold text-navy">Dividend Breakdown</div>
          <div id="div-totbadge" class="text-[12px] font-semibold text-brand"></div>
        </div>
        <div class="table-scroll">
          <table>
            <thead>
              <tr>
                <th class="th-left">Director</th>
                <th class="th-right">Investment (BDT)</th>
                <th class="th-right">Ownership %</th>
                <th class="th-right">Dividend (BDT)</th>
              </tr>
            </thead>
            <tbody id="div-tbody"></tbody>
          </table>
          <div id="div-empty" class="flex flex-col items-center justify-center py-10 text-navy/35">
            <svg width="34" height="34" viewBox="0 0 24 24" fill="none" stroke="#d4d7e3" stroke-width="1.5" class="mb-2"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
            <div class="text-[13px] font-medium">Enter a profit amount above to preview.</div>
          </div>
        </div>
      </div>

      <!-- Dividend History -->
      <div class="bg-white rounded-2xl border border-[#e8eaf0] shadow-sm overflow-hidden">
        <div class="px-5 py-4 border-b border-[#f0f2f7] text-[16px] font-bold text-navy">Disbursement History</div>
        <div class="table-scroll">
          <table>
            <thead>
              <tr>
                <th class="th-left">Date</th>
                <th class="th-right">Total Profit</th>
                <th class="th-right">Distributed</th>
                <th class="th-center">Directors</th>
                <th class="th-left">Note</th>
              </tr>
            </thead>
            <tbody id="div-hist-tbody">
              <tr><td colspan="5" class="text-center py-8 text-navy/35"><div class="section-loader"><div class="spin"></div></div></td></tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

  </main><!-- /main -->
</div><!-- /flex-1 -->

<!-- ════════════════ MODALS ════════════════ -->

<!-- ── Add Investment ── -->
<div id="m-invest" class="modal-bg" onclick="if(event.target===this)closeModal('m-invest')">
  <div class="modal-box">
    <div class="text-[20px] font-bold text-navy mb-1">Add Investment</div>
    <div id="mi-name" class="text-[13px] text-navy/50 mb-4 font-medium"></div>
    <div class="bg-[#F4F4F4] rounded-xl p-4 mb-4">
      <div class="flex justify-between text-[13px] mb-2">
        <span class="text-navy/50 font-medium">Current Investment</span>
        <span id="mi-cur" class="font-semibold text-navy"></span>
      </div>
      <div class="flex justify-between text-[13px]">
        <span class="text-navy/50 font-medium">Current Ownership</span>
        <span id="mi-own" class="font-semibold text-brand"></span>
      </div>
    </div>
    <div class="form-group">
      <label class="inp-label">Amount to Add (BDT)</label>
      <input type="number" id="mi-amt" class="inp" placeholder="Enter amount" min="1" oninput="prevInvest()" />
    </div>
    <div class="form-group">
      <label class="inp-label">Note</label>
      <input type="text" id="mi-note" class="inp" placeholder="e.g. Initial capital" value="Investment" />
    </div>
    <div id="mi-prev" class="bg-brand/10 border border-brand/25 rounded-xl p-4 mb-4" style="display:none">
      <div class="flex justify-between text-[13px] mb-2">
        <span class="text-navy/50 font-medium">New Investment</span>
        <span id="mi-ni" class="font-bold text-navy"></span>
      </div>
      <div class="flex justify-between text-[13px]">
        <span class="text-navy/50 font-medium">New Ownership</span>
        <span id="mi-no" class="font-bold text-brand"></span>
      </div>
    </div>
    <div class="flex flex-col gap-2 mt-2">
      <button onclick="confirmInvest()" class="w-full bg-brand text-white rounded-[10px] font-semibold text-[13px] py-2.5 border-none cursor-pointer hover:bg-brand-dark transition-colors">Confirm Investment</button>
      <button onclick="closeModal('m-invest')" class="w-full bg-transparent text-navy border border-[#d4d7e3] rounded-[10px] font-semibold text-[13px] py-2.5 cursor-pointer hover:border-navy transition-colors">Cancel</button>
    </div>
  </div>
</div>

<!-- ── Withdraw ── -->
<div id="m-withdraw" class="modal-bg" onclick="if(event.target===this)closeModal('m-withdraw')">
  <div class="modal-box">
    <div class="text-[20px] font-bold text-navy mb-1">Withdraw Investment</div>
    <div id="mw-name" class="text-[13px] text-navy/50 mb-4 font-medium"></div>
    <div class="bg-[#F4F4F4] rounded-xl p-4 mb-4">
      <div class="flex justify-between text-[13px] mb-2">
        <span class="text-navy/50 font-medium">Current Investment</span>
        <span id="mw-cur" class="font-semibold text-navy"></span>
      </div>
      <div class="flex justify-between text-[13px]">
        <span class="text-navy/50 font-medium">Current Ownership</span>
        <span id="mw-own" class="font-semibold text-brand"></span>
      </div>
    </div>
    <div class="form-group">
      <label class="inp-label">Amount to Withdraw (BDT)</label>
      <input type="number" id="mw-amt" class="inp" placeholder="Enter amount" min="1" oninput="prevWithdraw()" />
    </div>
    <div class="form-group">
      <label class="inp-label">Note</label>
      <input type="text" id="mw-note" class="inp" placeholder="Reason for withdrawal" value="Withdrawal" />
    </div>
    <div id="mw-prev" class="bg-red-50 border border-red-100 rounded-xl p-4 mb-3" style="display:none">
      <div class="flex justify-between text-[13px] mb-2">
        <span class="text-navy/50 font-medium">New Investment</span>
        <span id="mw-ni" class="font-bold text-navy"></span>
      </div>
      <div class="flex justify-between text-[13px]">
        <span class="text-navy/50 font-medium">New Ownership</span>
        <span id="mw-no" class="font-bold text-red-500"></span>
      </div>
    </div>
    <div id="mw-err" class="text-red-500 text-[12.5px] font-medium bg-red-50 border border-red-100 rounded-lg px-3 py-2 mb-3" style="display:none">Amount exceeds current investment.</div>
    <div class="flex flex-col gap-2 mt-2">
      <button onclick="confirmWithdraw()" class="w-full bg-red-500 text-white rounded-[10px] font-semibold text-[13px] py-2.5 border-none cursor-pointer hover:bg-red-600 transition-colors">Confirm Withdraw</button>
      <button onclick="closeModal('m-withdraw')" class="w-full bg-transparent text-navy border border-[#d4d7e3] rounded-[10px] font-semibold text-[13px] py-2.5 cursor-pointer hover:border-navy transition-colors">Cancel</button>
    </div>
  </div>
</div>

<!-- ── Profile / Transaction History ── -->
<div id="m-profile" class="modal-bg" onclick="if(event.target===this)closeModal('m-profile')">
  <div class="modal-box modal-box-lg">
    <div class="flex items-center gap-4 mb-5">
      <div id="pm-av" class="w-[52px] h-[52px] rounded-[14px] flex items-center justify-center text-[22px] font-bold text-white flex-shrink-0"></div>
      <div class="flex-1 min-w-0">
        <div id="pm-name" class="text-[18px] font-bold text-navy truncate"></div>
        <div id="pm-own" class="text-[12.5px] text-navy/50 font-medium"></div>
      </div>
      <button onclick="closeModal('m-profile')" class="w-8 h-8 rounded-lg bg-[#F4F4F4] flex items-center justify-center cursor-pointer border-none hover:bg-[#e8eaf0] transition-colors flex-shrink-0">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#1A2039" stroke-width="2.5" stroke-linecap="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
      </button>
    </div>
    <!-- Stats row -->
    <div class="grid grid-cols-2 gap-3 mb-5">
      <div class="bg-[#F4F4F4] rounded-xl p-4">
        <div class="text-[11px] font-semibold text-navy/45 mb-1 uppercase tracking-wide">Investment</div>
        <div id="pm-inv" class="text-[16px] font-bold text-navy"></div>
      </div>
      <div class="bg-[#F4F4F4] rounded-xl p-4">
        <div class="text-[11px] font-semibold text-navy/45 mb-1 uppercase tracking-wide">Ownership</div>
        <div id="pm-pct" class="text-[16px] font-bold text-brand"></div>
      </div>
    </div>
    <!-- Transaction history -->
    <div class="text-[13.5px] font-bold text-navy mb-3">Transaction History</div>
    <div id="pm-hist" class="flex flex-col gap-2 max-h-[320px] overflow-y-auto"></div>
  </div>
</div>

<!-- ── Delete confirmation ── -->
<div id="m-delete" class="modal-bg" onclick="if(event.target===this)closeModal('m-delete')">
  <div class="modal-box">
    <div class="w-12 h-12 rounded-[14px] bg-red-50 flex items-center justify-center mb-4">
      <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#ef4444" stroke-width="2" stroke-linecap="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/><path d="M9 6V4h6v2"/></svg>
    </div>
    <div class="text-[18px] font-bold text-navy mb-2">Deactivate Director?</div>
    <div id="del-msg" class="text-[13px] text-navy/55 mb-5"></div>
    <div class="flex flex-col gap-2">
      <button onclick="confirmDelete()" class="w-full bg-red-500 text-white rounded-[10px] font-semibold text-[13px] py-2.5 border-none cursor-pointer hover:bg-red-600 transition-colors">Yes, Deactivate</button>
      <button onclick="closeModal('m-delete')" class="w-full bg-transparent text-navy border border-[#d4d7e3] rounded-[10px] font-semibold text-[13px] py-2.5 cursor-pointer hover:border-navy transition-colors">Cancel</button>
    </div>
  </div>
</div>

<!-- ── Toast ── -->
<div id="notif">
  <span id="notif-icon"></span>
  <span id="notif-msg"></span>
</div>

<!-- ════════════════════════════════════════
     JAVASCRIPT
════════════════════════════════════════ -->
<script>
// ── Config ───────────────────────────────────────────────────────────────────
const API = {
  directors: {
    getAll:  '../api/directors/get-all.php',
    create:  '../api/directors/store-from-dashboard.php',
    update:  '../api/directors/update.php',
    delete:  '../api/directors/delete.php',
    summary: '../api/directors/summary.php',
    details: id => `../api/directors/details.php?id=${id}`,
  },
  tx: {
    get:      id => `../api/director_transactions/get.php?director_id=${id}`,
    invest:   '../api/director_transactions/invest.php',
    withdraw: '../api/director_transactions/withdraw.php',
  },
  pl: {
    get:    p => `../api/profit-loss/get.php?period=${p}`,
    create: '../api/profit-loss/create.php',
  },
  div: {
    calculate: '../api/dividends/calculate.php',
    disburse:  '../api/dividends/disburse.php',
    history:   '../api/dividends/history.php',
  }
};

// ── State ────────────────────────────────────────────────────────────────────
const BASE_PRICE = <?php echo (int)$cleanBasePrice; ?>;
// alert(BASE_PRICE)
let S = { dirs: [], summary: {}, plChart: null };
let modalTarget = null;
const AV = ['#50BC81','#3b82f6','#f59e0b','#ef4444','#8b5cf6','#06b6d4','#ec4899','#14b8a6'];

// ── Helpers ───────────────────────────────────────────────────────────────────
const fmt   = v => 'BDT ' + Number(v).toLocaleString('en-BD', {minimumFractionDigits:2, maximumFractionDigits:2});
const pct   = v => (Number(v)||0).toFixed(4) + '%';
const own   = inv => ((inv/BASE_PRICE)*12.5);
const avCol = i  => AV[i % AV.length];
const avLtr = n  => (n||'?')[0].toUpperCase();
const dateStr = d => new Date(d).toLocaleDateString('en-GB',{day:'2-digit',month:'short',year:'numeric'});

async function api(url, method='GET', body=null) {
  const opts = { method, headers:{ 'Content-Type':'application/json' } };
  if (body) opts.body = JSON.stringify(body);
  const res = await fetch(url, opts);
  const json = await res.json();
  if (json.status !== 'success') throw new Error(json.message || 'API error');
  return json.data;
}

// ── Navigation ───────────────────────────────────────────────────────────────
const pageTitles = {
  dashboard: ['Director\'s Dashboard','Overview of all director accounts'],
  settings:  ['Director Settings','Add or manage director investment accounts'],
  profiles:  ['Director Profiles','Detailed view of each director'],
  profitloss:['Profit / Loss','Track financial performance over time'],
  dividend:  ['Dividend','Calculate & disburse director dividends'],
};

function nav(page) {
    document.querySelectorAll('.page').forEach(p => p.classList.remove('active'));
    document.querySelectorAll('.nav-link').forEach(l => l.classList.remove('active'));
    document.getElementById('page-' + page).classList.add('active');
    document.querySelector(`.nav-link[data-page="${page}"]`).classList.add('active');
    const [title, sub] = pageTitles[page] || ['',''];
    document.getElementById('pgTitle').textContent = title;
    document.getElementById('pgSub').textContent   = sub;
    closeSidebar();
    // Load data per page
    if (page === 'dashboard')   renderDashboard();
    if (page === 'settings')    renderSettings();
    if (page === 'profiles')    renderProfiles();
    if (page === 'profitloss')  loadPL('monthly');
    if (page === 'dividend')    { renderDivTable(); loadDivHistory(); }
}

// ── Sidebar (mobile) ─────────────────────────────────────────────────────────
function openSidebar()  { document.getElementById('sidebar').classList.add('open'); document.getElementById('sideOverlay').classList.add('open'); }
function closeSidebar() { document.getElementById('sidebar').classList.remove('open'); document.getElementById('sideOverlay').classList.remove('open'); }
window.addEventListener('resize', () => {
  if (window.innerWidth > 768) closeSidebar();
  if (window.innerWidth <= 768) document.getElementById('burger').style.display = 'block';
  else document.getElementById('burger').style.display = 'none';
});

// ── Global search (client-side) ───────────────────────────────────────────────
function globalSearch(q) {
  const lq = q.toLowerCase();
  document.querySelectorAll('#s-tbody tr[data-dir-id]').forEach(tr => {
    tr.style.display = tr.dataset.name.toLowerCase().includes(lq) ? '' : 'none';
  });
  document.querySelectorAll('#prof-grid .prof-card').forEach(c => {
    c.style.display = (c.dataset.name||'').toLowerCase().includes(lq) ? '' : 'none';
  });
}

// ── Toast ─────────────────────────────────────────────────────────────────────
let toastTimer;
function toast(msg, type='success') {
  const icons = {
    success: `<svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="#50BC81" stroke-width="2.5" stroke-linecap="round"><polyline points="20 6 9 17 4 12"/></svg>`,
    warn:    `<svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="#d97706" stroke-width="2.5" stroke-linecap="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>`,
    error:   `<svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="#ef4444" stroke-width="2.5" stroke-linecap="round"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>`,
  };
  document.getElementById('notif-icon').innerHTML = icons[type] || icons.success;
  document.getElementById('notif-msg').textContent = msg;
  const el = document.getElementById('notif');
  el.classList.add('show');
  clearTimeout(toastTimer);
  toastTimer = setTimeout(() => el.classList.remove('show'), 3500);
}

// ── Modal helpers ─────────────────────────────────────────────────────────────
function openModal(id)  { document.getElementById(id).classList.add('open'); }
function closeModal(id) { document.getElementById(id).classList.remove('open'); }

// ═══════════════════════════════════════════════════
//  DASHBOARD
// ═══════════════════════════════════════════════════
async function renderDashboard() {
  try {
    const [sum, dirs] = await Promise.all([
      api(API.directors.summary),
      api(API.directors.getAll)
    ]);
    S.dirs = dirs;

    document.getElementById('d-tot-inv').textContent = fmt(sum.total_investment);
    document.getElementById('d-tot-own').textContent = pct(sum.total_ownership);
    document.getElementById('d-tot-dir').textContent = sum.total_directors;
    const ownPct = Math.min((sum.total_ownership / 100) * 100, 100);
    document.getElementById('d-own-bar').style.width = ownPct + '%';

    // Ownership snapshot bars
    const snap = document.getElementById('d-snap');
    if (!dirs.length) { snap.innerHTML = '<div class="text-center py-6 text-navy/35 text-sm">No directors yet.</div>'; return; }
    snap.innerHTML = dirs.slice(0,6).map((d,i) => {
      const o = d.ownership_percent;
      const barW = Math.min((o / 100) * 100, 100);
      return `<div class="own-row">
        <div class="w-6 h-6 rounded-md flex items-center justify-center text-[10px] font-bold text-white flex-shrink-0" style="background:${avCol(i)}">${avLtr(d.name)}</div>
        <div class="own-name">${d.name}</div>
        <div class="flex-1 min-w-0"><div class="prog-track"><div class="prog-fill" style="width:${barW}%"></div></div></div>
        <div class="own-pct">${pct(o)}</div>
      </div>`;
    }).join('');

    // Recent activity — get latest transactions across all directors
    await renderRecentActivity();
  } catch(e) { toast('Failed to load dashboard: ' + e.message, 'error'); }
}

async function renderRecentActivity() {
  const actEl = document.getElementById('d-act');
  actEl.innerHTML = '<div class="section-loader"><div class="spin"></div></div>';
  try {
    // Fetch last transactions for the first few directors
    const topDirs = S.dirs.slice(0, 4);
    const txArrays = await Promise.all(topDirs.map(d => api(API.tx.get(d.id)).catch(()=>[])));
    let all = [];
    txArrays.forEach((txs, i) => {
      txs.slice(0,3).forEach(tx => all.push({ ...tx, dir_name: topDirs[i].name }));
    });
    all.sort((a,b) => new Date(b.created_at) - new Date(a.created_at));
    all = all.slice(0, 8);

    if (!all.length) { actEl.innerHTML = '<div class="text-center py-6 text-navy/35 text-sm">No transactions yet.</div>'; return; }

    actEl.innerHTML = all.map(tx => {
      const isInv = tx.type === 'invest';
      const col   = isInv ? '#50BC81' : '#ef4444';
      const icon  = isInv ? '↑' : '↓';
      return `<div class="flex items-center gap-3 py-2.5 border-b border-[#f7f8fb] last:border-0">
        <div class="w-7 h-7 rounded-lg flex items-center justify-center text-[12px] font-bold border border-[#e8eaf0]" style="color:${col};">${icon}</div>
        <div class="flex-1 min-w-0">
          <div class="text-[12.5px] font-semibold text-navy truncate">${tx.dir_name}</div>
          <div class="text-[11px] text-navy/40">${dateStr(tx.created_at)}</div>
        </div>
        <div class="text-[12.5px] font-bold flex-shrink-0" style="color:${col}">${isInv?'+':'-'}${fmt(tx.amount)}</div>
      </div>`;
    }).join('');
  } catch(e) { actEl.innerHTML = ''; }
}

// ═══════════════════════════════════════════════════
//  SETTINGS
// ═══════════════════════════════════════════════════
async function renderSettings() {
  try {
    S.dirs = await api(API.directors.getAll);
    const tbody = document.getElementById('s-tbody');
    const totalInv = S.dirs.reduce((a,d) => a + d.total_investment, 0);
    document.getElementById('s-summary').textContent =
      `${S.dirs.length} director${S.dirs.length!==1?'s':''} · ${fmt(totalInv)} total`;

    if (!S.dirs.length) {
      tbody.innerHTML = '<tr><td colspan="5" class="text-center py-10 text-navy/35 text-sm">No directors yet. Add one!</td></tr>';
      return;
    }

    tbody.innerHTML = S.dirs.map((d,i) => `
      <tr data-dir-id="${d.id}" data-name="${d.name}">
        <td>
          <div class="flex items-center gap-2.5">
            <div class="w-7 h-7 rounded-lg flex items-center justify-center text-[11px] font-bold text-white flex-shrink-0" style="background:${avCol(i)}">${avLtr(d.name)}</div>
            <div>
              <div class="font-semibold text-[13px]">${d.name}</div>
              <div class="text-[11px] text-navy/40">${d.sys_id}</div>
            </div>
          </div>
        </td>
        <td class="right font-semibold">${fmt(d.total_investment)}</td>
        <td class="right font-bold text-brand">${pct(d.ownership_percent)}</td>
        <td class="center"><span class="badge-${d.status}">${d.status}</span></td>
        <td class="center">
          <div class="flex items-center justify-center gap-1.5">
            <button onclick="openInvest(${d.id})" title="Add Investment" class="w-7 h-7 rounded-lg bg-brand/10 text-brand flex items-center justify-center cursor-pointer border-none hover:bg-brand/20 transition-colors text-[11px] font-bold">+</button>
            <button onclick="openWithdraw(${d.id})" title="Withdraw" class="w-7 h-7 rounded-lg bg-red-50 text-red-500 flex items-center justify-center cursor-pointer border-none hover:bg-red-100 transition-colors text-[11px] font-bold">−</button>
            <button onclick="editDir(${d.id})" title="Edit" class="w-7 h-7 rounded-lg bg-[#F4F4F4] text-navy/60 flex items-center justify-center cursor-pointer border-none hover:bg-[#e8eaf0] transition-colors">
              <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
            </button>
            <button onclick="openDelete(${d.id})" title="Deactivate" class="w-7 h-7 rounded-lg bg-red-50 text-red-400 flex items-center justify-center cursor-pointer border-none hover:bg-red-100 transition-colors">
              <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/></svg>
            </button>
          </div>
        </td>
      </tr>
    `).join('');
  } catch(e) { toast('Failed to load directors: ' + e.message, 'error'); }
}

// Add / Edit director
async function saveDir() {
  const id      = document.getElementById('edit-id').value;
  const name    = document.getElementById('f-name').value.trim();
  const email   = document.getElementById('f-email').value.trim();
  const phone   = document.getElementById('f-phone').value.trim();
  const investAmount   = document.getElementById('f-invest-amount').value.trim();
  const address_01 = document.getElementById('f-address-01').value.trim();
  const address_02 = document.getElementById('f-address-02').value.trim();
  const address_city = document.getElementById('f-address-city').value.trim();
  const address_zip = document.getElementById('f-address-zip').value.trim();
  const address_state = document.getElementById('f-address-state').value.trim();
  const address_country = document.getElementById('f-address-country').value.trim();
  const status  = document.getElementById('f-status').value;

  if (!name)  { toast('Name is required', 'warn'); return; }
  if (!email) { toast('Email is required', 'warn'); return; }
  if (!phone) { toast('Phone is required', 'warn'); return; }
  if (!investAmount) { toast('Invest Amount is required', 'warn'); return; }

  const btn = document.getElementById('save-btn');
  btn.disabled = true;
  btn.innerHTML = '<div class="spin"></div>';

  try {
    if (id) {
      await api(API.directors.update, 'POST', { 
        id: parseInt(id), 
        name, 
        email, 
        phone, 
        investAmount, 
        address_01,
        address_02,
        address_city,
        address_zip,
        address_state,
        address_country,
        status 
      });
      toast(`${name} updated!`, 'success');
    } else {
      await api(API.directors.create, 'POST', { 
        name, 
        email, 
        phone, 
        investAmount,
        address_01,
        address_02,
        address_city,
        address_zip,
        address_state,
        address_country,
        status 
      });
      toast(`${name} added!`, 'success');
    }
    cancelEdit();
    renderSettings();
    if (document.getElementById('page-dashboard').classList.contains('active')) renderDashboard();
  } catch(e) {
    toast(e.message, 'error');
  } finally {
    btn.disabled = false;
    document.getElementById('save-lbl').textContent = id ? 'Save Changes' : 'Add Director';
    btn.innerHTML = `<span id="save-lbl">${id ? 'Save Changes' : 'Add Director'}</span>`;
  }
}

function editDir(id) {
  const d = S.dirs.find(d => d.id == id);
  if (!d) return;
  document.getElementById('edit-id').value     = d.id;
  document.getElementById('f-name').value      = d.name;
  document.getElementById('f-email').value     = d.email;
  document.getElementById('f-phone').value     = d.phone;
  document.getElementById('f-address').value   = d.address || '';
  document.getElementById('f-status').value    = d.status;
  document.getElementById('form-title').textContent = 'Edit Director';
  document.getElementById('save-lbl').textContent   = 'Save Changes';
  document.getElementById('cancel-btn').style.display = 'block';

  // Show ownership preview
  const ownPreview = document.getElementById('form-own-preview');
  ownPreview.style.display = 'block';
  document.getElementById('form-own-inv').textContent = fmt(d.total_investment);
  document.getElementById('form-own-pct').textContent = pct(d.ownership_percent);
  document.getElementById('form-own-bar').style.width = Math.min((d.ownership_percent / 100) * 100, 100) + '%';

  document.getElementById('f-name').focus();
  window.scrollTo({ top: 0, behavior:'smooth' });
}

function cancelEdit() {
  document.getElementById('edit-id').value  = '';
  document.getElementById('f-name').value   = '';
  document.getElementById('f-email').value  = '';
  document.getElementById('f-phone').value  = '';
  document.getElementById('f-address').value= '';
  document.getElementById('f-status').value = 'active';
  document.getElementById('form-title').textContent = 'Add Director';
  document.getElementById('save-lbl').textContent   = 'Add Director';
  document.getElementById('cancel-btn').style.display = 'none';
  document.getElementById('form-own-preview').style.display = 'none';
}

// ═══════════════════════════════════════════════════
//  PROFILES
// ═══════════════════════════════════════════════════
async function renderProfiles() {
    const grid = document.getElementById('prof-grid');
    grid.innerHTML = '<div class="section-loader col-span-4"><div class="spin"></div></div>';
    
    try {
        S.dirs = await api(API.directors.getAll);
        const totalInv = S.dirs.reduce((a,d) => a + d.total_investment, 0);
        const totalOwn = S.dirs.reduce((a,d) => a + d.ownership_percent, 0);
        
        document.getElementById('p-inv').textContent = fmt(totalInv);
        document.getElementById('p-own').textContent = pct(totalOwn);
        document.getElementById('p-cnt').textContent = S.dirs.length;
        
        if (!S.dirs.length) {
          grid.innerHTML = '<div class="col-span-4 text-center py-12 text-navy/35 text-sm">No directors found.</div>';
          return;
    }
    
    grid.innerHTML = S.dirs.map((d,i) => `
      <div class="prof-card bg-white rounded-2xl border border-[#e8eaf0] shadow-sm p-5 cursor-pointer hover:shadow-md transition-all hover:-translate-y-0.5"
           data-name="${d.name}" onclick="openProfile(${d.id})">
        <div class="flex items-center gap-3 mb-4">
          <div class="w-11 h-11 rounded-[12px] flex items-center justify-center text-[18px] font-bold text-white flex-shrink-0" style="background:${avCol(i)}">${avLtr(d.name)}</div>
          <div class="flex-1 min-w-0">
            <div class="font-bold text-[14px] text-navy truncate">${d.name}</div>
            <div class="text-[11px] text-navy/40 truncate">${d.sys_id}</div>
          </div>
          <span class="badge-${d.status}">${d.status}</span>
        </div>
        <div class="grid grid-cols-2 gap-3 mb-4">
          <div class="bg-[#F4F4F4] rounded-xl p-3">
            <div class="text-[10px] font-semibold text-navy/40 mb-0.5 uppercase tracking-wide">Investment</div>
            <div class="text-[13px] font-bold text-navy">${fmt(d.total_investment)}</div>
          </div>
          <div class="bg-brand/10 rounded-xl p-3">
            <div class="text-[10px] font-semibold text-navy/40 mb-0.5 uppercase tracking-wide">Ownership</div>
            <div class="text-[13px] font-bold text-brand">${pct(d.ownership_percent)}</div>
          </div>
        </div>
        <div class="prog-track"><div class="prog-fill" style="width:${Math.min((d.ownership_percent/100)*100,100)}%"></div></div>
        <div class="flex gap-2 mt-3">
          <button onclick="event.stopPropagation();openInvest(${d.id})" class="flex-1 bg-brand/10 text-brand border-none rounded-lg py-1.5 text-[11.5px] font-semibold cursor-pointer hover:bg-brand/20 transition-colors">Invest</button>
          <button onclick="event.stopPropagation();openWithdraw(${d.id})" class="flex-1 bg-red-50 text-red-500 border-none rounded-lg py-1.5 text-[11.5px] font-semibold cursor-pointer hover:bg-red-100 transition-colors">Withdraw</button>
        </div>
      </div>
    `).join('');
    } catch(e) { grid.innerHTML = '<div class="col-span-4 text-center py-8 text-red-400">Failed to load profiles.</div>'; }
}

// ── Profile modal ─────────────────────────────────────────────────────────────
async function openProfile(id) {
  const d = S.dirs.find(d => d.id == id);
  const i = S.dirs.indexOf(d);
  document.getElementById('pm-av').textContent   = avLtr(d.name);
  document.getElementById('pm-av').style.background = avCol(i);
  document.getElementById('pm-name').textContent = d.name;
  document.getElementById('pm-own').textContent  = `${pct(d.ownership_percent)} ownership · ${d.email}`;
  document.getElementById('pm-inv').textContent  = fmt(d.total_investment);
  document.getElementById('pm-pct').textContent  = pct(d.ownership_percent);
  document.getElementById('pm-hist').innerHTML   = '<div class="section-loader"><div class="spin"></div></div>';
  openModal('m-profile');

  try {
    const txs = await api(API.tx.get(id));
    if (!txs.length) {
      document.getElementById('pm-hist').innerHTML = '<div class="text-center py-6 text-navy/35 text-sm">No transactions yet.</div>';
      return;
    }
    document.getElementById('pm-hist').innerHTML = txs.map(tx => {
      const isInv = tx.type === 'invest';
      const col   = isInv ? '#50BC81' : '#ef4444';
      return `<div class="flex items-center gap-3 bg-[#F4F4F4] rounded-[10px] px-3 py-2.5">
        <div class="w-7 h-7 rounded-lg flex items-center justify-center text-[12px] font-bold bg-white border border-[#e8eaf0] flex-shrink-0" style="color:${col}">${isInv?'↑':'↓'}</div>
        <div class="flex-1 min-w-0">
          <div class="text-[12.5px] font-semibold text-navy">${tx.note || tx.type}</div>
          <div class="text-[11px] text-navy/40">${dateStr(tx.created_at)}</div>
        </div>
        <div class="text-[12.5px] font-bold flex-shrink-0" style="color:${col}">${isInv?'+':'-'}${fmt(tx.amount)}</div>
      </div>`;
    }).join('');
  } catch(e) { document.getElementById('pm-hist').innerHTML = '<div class="text-center py-4 text-red-400 text-sm">Failed to load transactions.</div>'; }
}

// ═══════════════════════════════════════════════════
//  INVEST / WITHDRAW MODALS
// ═══════════════════════════════════════════════════
function openInvest(id) {
  modalTarget = id;
  const d = S.dirs.find(d => d.id == id);
  document.getElementById('mi-name').textContent = d.name;
  document.getElementById('mi-cur').textContent  = fmt(d.total_investment);
  document.getElementById('mi-own').textContent  = pct(d.ownership_percent);
  document.getElementById('mi-amt').value  = '';
  document.getElementById('mi-note').value = 'Investment';
  document.getElementById('mi-prev').style.display = 'none';
  openModal('m-invest');
  setTimeout(() => document.getElementById('mi-amt').focus(), 200);
}

function prevInvest() {
  const d   = S.dirs.find(d => d.id == modalTarget);
  const add = parseFloat(document.getElementById('mi-amt').value) || 0;
  const prev = document.getElementById('mi-prev');
  if (add > 0) {
    document.getElementById('mi-ni').textContent = fmt(d.total_investment + add);
    document.getElementById('mi-no').textContent = pct(own(d.total_investment + add));
    prev.style.display = 'block';
  } else prev.style.display = 'none';
}

async function confirmInvest() {
  const add  = parseFloat(document.getElementById('mi-amt').value) || 0;
  const note = document.getElementById('mi-note').value.trim() || 'Investment';
  if (add <= 0) { toast('Enter a valid amount', 'warn'); return; }
  try {
    const res = await api(API.tx.invest, 'POST', { director_id: modalTarget, amount: add, note });
    // Update local state
    const d = S.dirs.find(d => d.id == modalTarget);
    if (d) { d.total_investment = res.total_investment; d.ownership_percent = res.ownership_percent; }
    toast(`${fmt(add)} invested for ${d?.name}`, 'success');
    closeModal('m-invest');
    refreshCurrentPage();
  } catch(e) { toast(e.message, 'error'); }
}

function openWithdraw(id) {
  modalTarget = id;
  const d = S.dirs.find(d => d.id == id);
  document.getElementById('mw-name').textContent = d.name;
  document.getElementById('mw-cur').textContent  = fmt(d.total_investment);
  document.getElementById('mw-own').textContent  = pct(d.ownership_percent);
  document.getElementById('mw-amt').value  = '';
  document.getElementById('mw-note').value = 'Withdrawal';
  document.getElementById('mw-prev').style.display = 'none';
  document.getElementById('mw-err').style.display  = 'none';
  openModal('m-withdraw');
  setTimeout(() => document.getElementById('mw-amt').focus(), 200);
}

function prevWithdraw() {
  const d   = S.dirs.find(d => d.id == modalTarget);
  const amt = parseFloat(document.getElementById('mw-amt').value) || 0;
  const prev = document.getElementById('mw-prev');
  const err  = document.getElementById('mw-err');
  if (amt > d.total_investment) { prev.style.display='none'; err.style.display='block'; return; }
  err.style.display = 'none';
  if (amt > 0) {
    document.getElementById('mw-ni').textContent = fmt(d.total_investment - amt);
    document.getElementById('mw-no').textContent = pct(own(d.total_investment - amt));
    prev.style.display = 'block';
  } else prev.style.display = 'none';
}

async function confirmWithdraw() {
  const amt  = parseFloat(document.getElementById('mw-amt').value) || 0;
  const note = document.getElementById('mw-note').value.trim() || 'Withdrawal';
  if (amt <= 0) { toast('Enter a valid amount', 'warn'); return; }
  const d = S.dirs.find(d => d.id == modalTarget);
  if (amt > (d?.total_investment || 0)) { toast('Exceeds current investment', 'warn'); return; }
  try {
    const res = await api(API.tx.withdraw, 'POST', { director_id: modalTarget, amount: amt, note });
    if (d) { d.total_investment = res.total_investment; d.ownership_percent = res.ownership_percent; }
    toast(`${fmt(amt)} withdrawn from ${d?.name}`, 'success');
    closeModal('m-withdraw');
    refreshCurrentPage();
  } catch(e) { toast(e.message, 'error'); }
}

// ── Delete ────────────────────────────────────────────────────────────────────
function openDelete(id) {
  modalTarget = id;
  const d = S.dirs.find(d => d.id == id);
  document.getElementById('del-msg').textContent = `This will deactivate "${d?.name}". Their transaction history will be preserved.`;
  openModal('m-delete');
}

async function confirmDelete() {
  try {
    await api(API.directors.delete, 'POST', { id: modalTarget });
    toast('Director deactivated.', 'success');
    closeModal('m-delete');
    renderSettings();
    renderDashboard();
  } catch(e) { toast(e.message, 'error'); }
}

// Refresh whichever page is currently active
function refreshCurrentPage() {
  if (document.getElementById('page-dashboard').classList.contains('active'))  renderDashboard();
  if (document.getElementById('page-settings').classList.contains('active'))   renderSettings();
  if (document.getElementById('page-profiles').classList.contains('active'))   renderProfiles();
  if (document.getElementById('page-dividend').classList.contains('active'))   renderDivTable();
}

// ═══════════════════════════════════════════════════
//  PROFIT / LOSS
// ═══════════════════════════════════════════════════
let plChartInst = null;

async function loadPL(period = 'monthly') {
  // Period button active state
  document.querySelectorAll('.pl-period-btn').forEach(b => {
    const active = b.dataset.period === period;
    b.classList.toggle('border-brand', active);
    b.classList.toggle('text-brand', active);
    b.classList.toggle('text-navy/55', !active);
  });
  document.getElementById('pl-chart-label').textContent = `${period.charAt(0).toUpperCase()+period.slice(1)} Overview`;

  try {
    const data = await api(API.pl.get(period));
    const { grouped, records } = data;

    // KPIs
    const totP = records.filter(r=>r.type==='profit').reduce((a,r)=>a+r.amount,0);
    const totL = records.filter(r=>r.type==='loss').reduce((a,r)=>a+r.amount,0);
    document.getElementById('pl-tp').textContent  = fmt(totP);
    document.getElementById('pl-tl').textContent  = fmt(totL);
    document.getElementById('pl-net').textContent = fmt(totP - totL);
    document.getElementById('pl-cnt').textContent = records.length;

    // Chart
    const labels  = grouped.map(r => r.period_key);
    const profits = grouped.map(r => r.total_profit);
    const losses  = grouped.map(r => r.total_loss);

    if (plChartInst) plChartInst.destroy();
    const ctx = document.getElementById('plChart').getContext('2d');
    plChartInst = new Chart(ctx, {
      type: 'bar',
      data: {
        labels,
        datasets: [
          { label:'Profit', data:profits, backgroundColor:'rgba(80,188,129,0.75)', borderRadius:6 },
          { label:'Loss',   data:losses,  backgroundColor:'rgba(239,68,68,0.65)',  borderRadius:6 }
        ]
      },
      options: {
        responsive:true, maintainAspectRatio:true,
        plugins:{ legend:{ position:'top', labels:{ font:{ family:'Poppins', size:12 } } } },
        scales:{
          x:{ grid:{ display:false }, ticks:{ font:{ family:'Poppins', size:11 } } },
          y:{ grid:{ color:'#f0f2f7' }, ticks:{ font:{ family:'Poppins', size:11 }, callback: v => 'BDT ' + v.toLocaleString() } }
        }
      }
    });

    // Records table
    const tbody = document.getElementById('pl-tbody');
    if (!records.length) {
      tbody.innerHTML = '<tr><td colspan="4" class="text-center py-8 text-navy/35 text-sm">No records yet.</td></tr>';
      return;
    }
    tbody.innerHTML = records.map(r => `
      <tr>
        <td>${dateStr(r.date)}</td>
        <td class="right font-semibold">${fmt(r.amount)}</td>
        <td class="center"><span class="badge-${r.type}">${r.type}</span></td>
        <td class="text-navy/55">${r.note || '—'}</td>
      </tr>
    `).join('');
  } catch(e) { toast('Failed to load P&L data: ' + e.message, 'error'); }
}

async function savePL() {
  const type   = document.getElementById('pl-type').value;
  const amount = parseFloat(document.getElementById('pl-amount').value) || 0;
  const date   = document.getElementById('pl-date').value;
  const note   = document.getElementById('pl-note').value.trim();

  if (amount <= 0) { toast('Enter a valid amount', 'warn'); return; }
  if (!date)       { toast('Select a date', 'warn'); return; }

  try {
    await api(API.pl.create, 'POST', { type, amount, date, note });
    toast(`${type} of ${fmt(amount)} recorded!`, 'success');
    document.getElementById('pl-amount').value = '';
    document.getElementById('pl-note').value   = '';
    const period = document.querySelector('.pl-period-btn.border-brand')?.dataset.period || 'monthly';
    loadPL(period);
  } catch(e) { toast(e.message, 'error'); }
}

// ═══════════════════════════════════════════════════
//  DIVIDEND
// ═══════════════════════════════════════════════════
async function calcDiv() {
  const profit = parseFloat(document.getElementById('div-profit').value) || 0;
  const empty  = document.getElementById('div-empty');
  const tbody  = document.getElementById('div-tbody');
  const badge  = document.getElementById('div-totbadge');

  if (profit <= 0) {
    tbody.innerHTML = '';
    empty.style.display = 'flex';
    badge.textContent   = '';
    return;
  }

  try {
    const data = await api(API.div.calculate, 'POST', { total_profit: profit });
    empty.style.display = 'none';
    badge.textContent = `Total: ${fmt(data.total_dividend)}`;
    tbody.innerHTML = data.breakdown.map(b => `
      <tr>
        <td class="font-semibold">${b.director_name}</td>
        <td class="right">${fmt(b.investment)}</td>
        <td class="right font-bold text-brand">${pct(b.ownership_percent)}</td>
        <td class="right font-bold">${fmt(b.dividend_amount)}</td>
      </tr>
    `).join('');
  } catch(e) { toast(e.message, 'error'); }
}

async function renderDivTable() {
  // Re-render with current input value
  const profit = parseFloat(document.getElementById('div-profit').value) || 0;
  if (profit > 0) calcDiv();
}

async function disburseDiv() {
  const profit = parseFloat(document.getElementById('div-profit').value) || 0;
  const note   = document.getElementById('div-note').value.trim();
  if (profit <= 0) { toast('Enter a profit amount first', 'warn'); return; }

  try {
    const data = await api(API.div.disburse, 'POST', { total_profit: profit, note });
    toast(`Dividend disbursed! Total: ${fmt(data.breakdown.reduce((a,b)=>a+b.dividend_amount,0))}`, 'success');
    document.getElementById('div-profit').value = '';
    document.getElementById('div-note').value   = '';
    document.getElementById('div-tbody').innerHTML = '';
    document.getElementById('div-empty').style.display = 'flex';
    document.getElementById('div-totbadge').textContent = '';
    loadDivHistory();
  } catch(e) { toast(e.message, 'error'); }
}

async function loadDivHistory() {
  const tbody = document.getElementById('div-hist-tbody');
  tbody.innerHTML = '<tr><td colspan="5" class="text-center py-6"><div class="section-loader"><div class="spin"></div></div></td></tr>';
  try {
    const history = await api(API.div.history);
    if (!history.length) {
      tbody.innerHTML = '<tr><td colspan="5" class="text-center py-8 text-navy/35 text-sm">No disbursements yet.</td></tr>';
      return;
    }
    tbody.innerHTML = history.map(h => `
      <tr>
        <td>${dateStr(h.created_at)}</td>
        <td class="right font-semibold">${fmt(h.total_profit)}</td>
        <td class="right font-bold text-brand">${fmt(h.total_distributed)}</td>
        <td class="center">${h.director_count}</td>
        <td class="text-navy/55">${h.note || '—'}</td>
      </tr>
    `).join('');
  } catch(e) { tbody.innerHTML = '<tr><td colspan="5" class="text-center py-4 text-red-400">Failed to load history.</td></tr>'; }
}

// ═══════════════════════════════════════════════════
//  INIT
// ═══════════════════════════════════════════════════
// Set today's date as default for P&L form
document.getElementById('pl-date').value = new Date().toISOString().split('T')[0];

// Mobile burger button
if (window.innerWidth <= 768) document.getElementById('burger').style.display = 'block';

// Boot dashboard
renderDashboard();
</script>
</body>
</html>