<?php
// FILE PATH: /pages/index-smpost.php
include_once('./authenticate.php');
$ip_port   = @file_get_contents('../ippath.txt') ?: 'http://103.104.219.3:898';
$smpostApi = $ip_port . 'api/social/endpoints.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Social Media Posts – TravHub</title>
<link rel="icon" type="image/png" href="../assets/images/logo/round-logo.png">
<script src="https://cdn.tailwindcss.com"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="../assets/css/style.css">
<style>
:root { --accent:#6c47d9; --accent-lt:#ede9fe; --border:#e8e4f3; }

.badge { display:inline-flex;align-items:center;padding:2px 10px;border-radius:999px;font-size:.7rem;font-weight:700; }
.badge-draft     { background:#f3f4f6;color:#6b7280; }
.badge-published { background:#dcfce7;color:#15803d; }
.badge-archived  { background:#fef3c7;color:#b45309; }

.plat-icon { display:inline-flex;align-items:center;justify-content:center;width:28px;height:28px;border-radius:8px;font-size:.75rem;flex-shrink:0; }
.pi-facebook  { background:#e8f0fe;color:#1877f2; }
.pi-instagram { background:#fce4ec;color:#e1306c; }
.pi-linkedin  { background:#e3f0fb;color:#0a66c2; }
.pi-twitter   { background:#f2f2f2;color:#111; }
.pi-tiktok    { background:#fde8ec;color:#fe2c55; }

.post-card { background:#fff;border:1.5px solid var(--border);border-radius:16px;transition:all .2s; }
.post-card:hover { border-color:#c4b5fd;box-shadow:0 6px 20px rgba(109,71,217,.1); }

.f-chip { padding:5px 13px;border-radius:999px;font-size:.72rem;font-weight:600;border:1.5px solid var(--border);color:#8b85a0;cursor:pointer;transition:all .12s;background:#fff;white-space:nowrap; }
.f-chip.active { border-color:var(--accent);background:var(--accent-lt);color:var(--accent); }

.modal-bg { background:rgba(0,0,0,.55);backdrop-filter:blur(4px); }
.modal-box { max-height:90vh;display:flex;flex-direction:column; }
.modal-body { overflow-y:auto;flex:1; }

.ht-chip { display:inline-flex;align-items:center;padding:2px 10px;border-radius:999px;font-size:.7rem;font-weight:600;background:var(--accent-lt);color:var(--accent); }
.kw-chip { display:inline-flex;align-items:center;padding:2px 10px;border-radius:999px;font-size:.7rem;font-weight:600;background:#ecfdf5;color:#059669; }

#toast { position:fixed;bottom:24px;right:24px;z-index:9999;transform:translateY(60px);opacity:0;transition:all .3s; }
#toast.show { transform:translateY(0);opacity:1; }
</style>
</head>
<body class="bg-[#f8f7ff] font-sans">
<?php include '../elements/header.php'; ?>
<?php include '../elements/aside.php'; ?>
<?php include '../elements/preview-model.php'; ?>

<main id="mainContent" class="pt-16 pl-64 transition-all duration-300">
<div class="p-6 max-w-6xl mx-auto">

<!-- Header -->
<div class="flex flex-wrap items-center justify-between gap-3 mb-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-800"><i class="fas fa-layer-group mr-2 text-violet-500"></i>Social Media Posts</h1>
        <p class="text-sm text-gray-500 mt-0.5">Manage all your AI-generated social content</p>
    </div>
    <a href="create-smpost.php" class="flex items-center gap-2 px-5 py-2.5 bg-gradient-to-r from-violet-600 to-indigo-600 hover:from-violet-700 hover:to-indigo-700 text-white rounded-xl font-semibold text-sm transition shadow-md">
        <i class="fas fa-plus"></i>Create Post
    </a>
</div>

<!-- Stats -->
<div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-5">
    <div class="bg-white rounded-xl p-4 border border-[var(--border)] shadow-sm"><div class="text-2xl font-bold text-violet-600" id="statTotal">—</div><div class="text-xs text-gray-500 mt-0.5">Total Posts</div></div>
    <div class="bg-white rounded-xl p-4 border border-[var(--border)] shadow-sm"><div class="text-2xl font-bold text-green-600" id="statPublished">—</div><div class="text-xs text-gray-500 mt-0.5">Published</div></div>
    <div class="bg-white rounded-xl p-4 border border-[var(--border)] shadow-sm"><div class="text-2xl font-bold text-gray-500" id="statDraft">—</div><div class="text-xs text-gray-500 mt-0.5">Drafts</div></div>
    <div class="bg-white rounded-xl p-4 border border-[var(--border)] shadow-sm"><div class="text-2xl font-bold text-amber-500" id="statImages">—</div><div class="text-xs text-gray-500 mt-0.5">With Images</div></div>
</div>

<!-- Filters -->
<div class="bg-white rounded-2xl p-4 border border-[var(--border)] shadow-sm mb-5">
    <div class="flex flex-wrap items-center gap-2">
        <div class="relative flex-1 min-w-[180px]">
            <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm"></i>
            <input type="text" id="searchInput" placeholder="Search posts…"
                class="pl-9 pr-4 py-2.5 border border-gray-200 rounded-lg text-sm w-full focus:ring-2 focus:ring-violet-300 focus:outline-none"
                oninput="applyFilters()">
        </div>
        <div class="flex flex-wrap gap-1.5">
            <button class="f-chip active" onclick="setFilter('platform','',this)">All</button>
            <button class="f-chip" onclick="setFilter('platform','facebook',this)"><i class="fab fa-facebook-f mr-1 text-[#1877f2]"></i>FB</button>
            <button class="f-chip" onclick="setFilter('platform','instagram',this)"><i class="fab fa-instagram mr-1 text-[#e1306c]"></i>IG</button>
            <button class="f-chip" onclick="setFilter('platform','linkedin',this)"><i class="fab fa-linkedin-in mr-1 text-[#0a66c2]"></i>LI</button>
            <button class="f-chip" onclick="setFilter('platform','twitter',this)"><i class="fab fa-x-twitter mr-1"></i>X</button>
            <button class="f-chip" onclick="setFilter('platform','tiktok',this)"><i class="fab fa-tiktok mr-1 text-[#fe2c55]"></i>TT</button>
        </div>
        <div class="flex flex-wrap gap-1.5">
            <button class="f-chip active" onclick="setFilter('status','',this)">All Status</button>
            <button class="f-chip" onclick="setFilter('status','draft',this)">Draft</button>
            <button class="f-chip" onclick="setFilter('status','published',this)">Published</button>
            <button class="f-chip" onclick="setFilter('status','archived',this)">Archived</button>
        </div>
        <button onclick="resetFilters()" class="px-3 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-500 rounded-lg text-sm transition"><i class="fas fa-rotate-right"></i></button>
    </div>
</div>

<!-- Grid -->
<div id="postsGrid" class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
    <div class="col-span-3 text-center py-16 text-gray-400">
        <i class="fas fa-spinner fa-spin text-3xl mb-3 block text-violet-300"></i>Loading…
    </div>
</div>

<!-- Pagination -->
<div class="flex items-center justify-between mt-6 pt-4 border-t border-[var(--border)]">
    <div class="text-sm text-gray-400" id="pageInfo">—</div>
    <div class="flex gap-2" id="pageBtns"></div>
</div>
</div>
</main>

<!-- ═══════════ PREVIEW MODAL ═══════════ -->
<div id="previewModal" class="fixed inset-0 z-50 hidden modal-bg flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-2xl modal-box">
        <!-- Header -->
        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100 flex-shrink-0">
            <div class="flex items-center gap-2" id="modalPlatHeader"></div>
            <button onclick="closePreview()" class="w-8 h-8 flex items-center justify-center rounded-full hover:bg-gray-100 text-gray-400 text-lg transition">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <!-- Body -->
        <div class="modal-body p-6 space-y-4" id="previewContent"></div>
        <!-- Footer -->
        <div class="flex flex-wrap items-center gap-2 px-6 py-4 border-t border-gray-100 flex-shrink-0" id="previewActions"></div>
    </div>
</div>

<!-- ═══════════ DELETE MODAL ═══════════ -->
<div id="deleteModal" class="fixed inset-0 z-50 hidden modal-bg flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-sm p-6 text-center">
        <div class="w-12 h-12 bg-red-50 rounded-2xl flex items-center justify-center mb-4 mx-auto">
            <i class="fas fa-trash-alt text-red-500 text-xl"></i>
        </div>
        <h3 class="font-bold text-gray-800 mb-1">Delete Post?</h3>
        <p class="text-sm text-gray-400 mb-5">This cannot be undone.</p>
        <input type="hidden" id="deleteSysId">
        <div class="flex gap-3">
            <button onclick="document.getElementById('deleteModal').classList.add('hidden')"
                class="flex-1 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-xl font-semibold text-sm">Cancel</button>
            <button onclick="confirmDelete()"
                class="flex-1 py-2.5 bg-red-500 hover:bg-red-600 text-white rounded-xl font-semibold text-sm">Delete</button>
        </div>
    </div>
</div>

<!-- Toast -->
<div id="toast">
    <div id="toastInner" class="flex items-center gap-3 px-5 py-3 rounded-xl shadow-lg text-white text-sm font-medium">
        <i id="toastIcon" class="fas fa-check-circle text-lg"></i><span id="toastMsg"></span>
    </div>
</div>

<?php include '../elements/floating-menus.php'; ?>
<script src="../assets/js/script.js?t=<?php echo time(); ?>"></script>
<script>
const SMPOST_API = '<?php echo $smpostApi; ?>';

const PLAT_INFO = {
    facebook:  { icon:'fab fa-facebook-f',  cls:'pi-facebook',  name:'Facebook'  },
    instagram: { icon:'fab fa-instagram',   cls:'pi-instagram', name:'Instagram' },
    linkedin:  { icon:'fab fa-linkedin-in', cls:'pi-linkedin',  name:'LinkedIn'  },
    twitter:   { icon:'fab fa-x-twitter',   cls:'pi-twitter',   name:'X/Twitter' },
    tiktok:    { icon:'fab fa-tiktok',      cls:'pi-tiktok',    name:'TikTok'    },
};

const PER_PAGE = 12;
let allPosts = [], filteredPosts = [], currentPage = 1;
let filters  = { platform:'', status:'' };
let _previewPost = null;

/* ── Helpers ── */
function sp(v) { if (!v) return null; if (typeof v === 'object') return v; try { return JSON.parse(v); } catch { return null; } }
function esc(s) { return String(s ?? '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;'); }
function extractDate(meta) {
    try { const m = sp(meta) ?? {}; return m.created_by_date?.date ?? m.created_by_date ?? '—'; } catch { return '—'; }
}
function showToast(type, msg) {
    const t=document.getElementById('toast'),i=document.getElementById('toastInner');
    document.getElementById('toastMsg').textContent = msg;
    document.getElementById('toastIcon').className = 'fas ' + (type==='success'?'fa-check-circle':'fa-exclamation-circle') + ' text-lg';
    i.className = 'flex items-center gap-3 px-5 py-3 rounded-xl shadow-lg text-white text-sm font-medium ' + (type==='success'?'bg-green-600':'bg-red-500');
    t.classList.add('show');
    setTimeout(() => t.classList.remove('show'), 3000);
}

/* ── Load ── */
async function loadPosts() {
    try {
        const res  = await fetch(SMPOST_API + '?action=list&limit=200');
        const json = await res.json();
        // Parse JSON columns for each post
        allPosts = (json.data ?? []).map(p => ({
            ...p,
            hashtags: sp(p.hashtags) ?? [],
            keywords: sp(p.keywords) ?? [],
            tips:     sp(p.tips)     ?? [],
        }));
        updateStats();
        applyFilters();
    } catch(e) {
        document.getElementById('postsGrid').innerHTML =
            '<div class="col-span-3 text-center py-12 text-red-400"><i class="fas fa-exclamation-circle mr-2"></i>Failed to load posts</div>';
    }
}

/* ── Stats ── */
function updateStats() {
    document.getElementById('statTotal').textContent     = allPosts.length;
    document.getElementById('statPublished').textContent = allPosts.filter(p => p.status === 'published').length;
    document.getElementById('statDraft').textContent     = allPosts.filter(p => p.status === 'draft').length;
    document.getElementById('statImages').textContent    = allPosts.filter(p => p.has_image == 1).length;
}

/* ── Filters ── */
function setFilter(key, val, btn) {
    filters[key] = val;
    const groupMap = { platform: 0, status: 1 };
    // reset active in same group
    btn.closest('.flex').querySelectorAll('.f-chip').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    applyFilters();
}

function resetFilters() {
    filters = { platform:'', status:'' };
    document.getElementById('searchInput').value = '';
    document.querySelectorAll('.f-chip').forEach(b => b.classList.remove('active'));
    // re-activate first in each group
    document.querySelectorAll('.flex .f-chip:first-child').forEach(b => b.classList.add('active'));
    applyFilters();
}

function applyFilters() {
    const search = document.getElementById('searchInput').value.toLowerCase().trim();
    filteredPosts = allPosts.filter(p => {
        const matchPlat   = !filters.platform || p.platform === filters.platform;
        const matchStatus = !filters.status   || p.status   === filters.status;
        const matchSearch = !search || (p.post_text??'').toLowerCase().includes(search) || (p.raw_input??'').toLowerCase().includes(search);
        return matchPlat && matchStatus && matchSearch;
    });
    currentPage = 1;
    renderGrid();
    renderPagination();
}

/* ── Grid ── */
function renderGrid() {
    const grid = document.getElementById('postsGrid');
    const s    = (currentPage - 1) * PER_PAGE;
    const page = filteredPosts.slice(s, s + PER_PAGE);

    if (!page.length) {
        grid.innerHTML = '<div class="col-span-3 text-center py-16 text-gray-400"><i class="fas fa-layer-group text-4xl mb-3 block opacity-20"></i><p class="font-semibold">No posts found</p><a href="create-smpost.php" class="mt-2 inline-block text-sm text-violet-500 hover:text-violet-700 font-semibold">Create your first post →</a></div>';
        return;
    }

    grid.innerHTML = page.map(p => {
        const pi      = PLAT_INFO[p.platform] ?? { icon:'fa-circle', cls:'', name: p.platform };
        const preview = (p.post_text ?? '').slice(0, 130) + ((p.post_text ?? '').length > 130 ? '…' : '');
        const tags    = (p.hashtags ?? []).slice(0, 3).map(h => '<span class="text-[10px] text-violet-500 font-semibold">#' + esc(h) + '</span>').join(' ');
        const moreTags= (p.hashtags ?? []).length > 3 ? '<span class="text-[10px] text-gray-400">+' + ((p.hashtags??[]).length - 3) + '</span>' : '';

        return '<div class="post-card p-5 flex flex-col gap-3">'
            + '<div class="flex items-center gap-2">'
                + '<div class="plat-icon ' + pi.cls + '"><i class="' + pi.icon + '"></i></div>'
                + '<span class="text-xs font-bold text-gray-600">' + esc(pi.name) + '</span>'
                + '<span class="badge badge-' + esc(p.status) + ' ml-auto">' + esc(p.status) + '</span>'
            + '</div>'
            + (p.has_image && p.image_url
                ? '<div class="rounded-xl overflow-hidden bg-gray-50" style="height:110px"><img src="' + esc(p.image_url) + '" class="w-full h-full object-cover" loading="lazy" onerror="this.parentElement.style.display=\'none\'"></div>'
                : '')
            + '<p class="text-sm text-gray-700 leading-relaxed flex-1">' + esc(preview) + '</p>'
            + (tags ? '<div class="flex flex-wrap gap-1 items-center">' + tags + moreTags + '</div>' : '')
            + '<div class="flex items-center justify-between pt-2 border-t border-gray-50">'
                + '<span class="text-[11px] text-gray-400">' + esc(extractDate(p.meta_data)) + '</span>'
                + '<div class="flex gap-1">'
                    + '<button data-id="' + esc(p.sys_id) + '" data-action="preview" class="sm-action w-7 h-7 flex items-center justify-center rounded-lg bg-gray-100 hover:bg-violet-50 text-gray-400 hover:text-violet-500 transition text-xs" title="Preview"><i class="fas fa-eye"></i></button>'
                    + '<a href="create-smpost.php?id=' + esc(p.sys_id) + '" class="w-7 h-7 flex items-center justify-center rounded-lg bg-gray-100 hover:bg-amber-50 text-gray-400 hover:text-amber-500 transition text-xs" title="Edit"><i class="fas fa-pencil"></i></a>'
                    + '<button data-id="' + esc(p.sys_id) + '" data-action="delete" class="sm-action w-7 h-7 flex items-center justify-center rounded-lg bg-gray-100 hover:bg-red-50 text-gray-400 hover:text-red-500 transition text-xs" title="Delete"><i class="fas fa-trash-alt"></i></button>'
                + '</div>'
            + '</div>'
        + '</div>';
    }).join('');
}

/* ── Pagination ── */
function renderPagination() {
    const total = filteredPosts.length;
    const pages = Math.ceil(total / PER_PAGE);
    const s = total ? (currentPage-1)*PER_PAGE+1 : 0;
    const e = Math.min(currentPage*PER_PAGE, total);
    document.getElementById('pageInfo').textContent = total ? 'Showing ' + s + '–' + e + ' of ' + total : 'No results';

    const btns = document.getElementById('pageBtns');
    btns.innerHTML = '';
    if (pages <= 1) return;

    const mk = (label, page, disabled, active) => {
        const b = document.createElement('button');
        b.innerHTML = label;
        b.className = 'px-3 py-1.5 rounded-lg text-sm border font-medium transition ' +
            (active ? 'bg-violet-600 text-white border-violet-600' : disabled ? 'bg-gray-50 text-gray-300 border-gray-200 cursor-not-allowed' : 'bg-white text-gray-600 border-gray-200 hover:bg-gray-50');
        if (!disabled && !active) b.onclick = () => { currentPage = page; renderGrid(); renderPagination(); };
        btns.appendChild(b);
    };
    mk('<i class="fas fa-chevron-left text-xs"></i>', currentPage-1, currentPage===1);
    for (let i=1; i<=pages; i++) mk(i, i, false, i===currentPage);
    mk('<i class="fas fa-chevron-right text-xs"></i>', currentPage+1, currentPage===pages);
}

/* ══════════════════════════════════════
   PREVIEW MODAL
══════════════════════════════════════ */
function openPreview(sysId) {
    const p = allPosts.find(x => x.sys_id === sysId);
    if (!p) { showToast('error', 'Post not found'); return; }
    _previewPost = p;

    const pi = PLAT_INFO[p.platform] ?? { icon:'fa-circle', cls:'', name: p.platform };
    const hashtags = Array.isArray(p.hashtags) ? p.hashtags : (sp(p.hashtags) ?? []);
    const keywords = Array.isArray(p.keywords) ? p.keywords : (sp(p.keywords) ?? []);

    // Platform header
    document.getElementById('modalPlatHeader').innerHTML =
        '<div class="plat-icon ' + pi.cls + '"><i class="' + pi.icon + '"></i></div>'
        + '<span class="font-bold text-gray-700">' + esc(pi.name) + '</span>'
        + '<span class="badge badge-' + esc(p.status) + ' ml-2">' + esc(p.status) + '</span>';

    // Build body HTML
    let bodyHtml = '';

    // Image
    if (p.has_image && p.image_url) {
        bodyHtml += '<div class="relative">'
            + '<img src="' + esc(p.image_url) + '" alt="" class="w-full rounded-xl border border-gray-100 object-cover" style="max-height:260px" onerror="this.parentElement.style.display=\'none\'">'
            + '<a href="' + esc(p.image_url) + '" download class="absolute top-2 right-2 flex items-center gap-1.5 px-3 py-1.5 bg-black/60 hover:bg-black/80 text-white rounded-lg text-xs font-semibold transition backdrop-blur-sm">'
            + '<i class="fas fa-download"></i>Download</a>'
            + '</div>';
    }

    // Post text
    bodyHtml += '<div class="bg-gray-50 rounded-xl p-4 text-sm text-gray-800 whitespace-pre-wrap leading-relaxed border border-gray-100">'
        + esc(p.post_text ?? '—')
        + '</div>';

    // Hook + CTA
    if (p.hook || p.cta) {
        bodyHtml += '<div class="grid grid-cols-2 gap-3">';
        if (p.hook) bodyHtml += '<div class="bg-amber-50 rounded-lg p-3"><p class="text-[10px] font-bold text-amber-500 uppercase mb-1">⚡ Hook</p><p class="text-xs text-gray-700">' + esc(p.hook) + '</p></div>';
        if (p.cta)  bodyHtml += '<div class="bg-green-50 rounded-lg p-3"><p class="text-[10px] font-bold text-green-500 uppercase mb-1">👆 CTA</p><p class="text-xs text-gray-700">' + esc(p.cta) + '</p></div>';
        bodyHtml += '</div>';
    }

    // Hashtags
    if (hashtags.length) {
        bodyHtml += '<div>'
            + '<p class="text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-2">Hashtags</p>'
            + '<div class="flex flex-wrap gap-1.5">'
            + hashtags.map(h => '<span class="ht-chip">#' + esc(h) + '</span>').join('')
            + '</div></div>';
    }

    // Keywords
    if (keywords.length) {
        bodyHtml += '<div>'
            + '<p class="text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-2">Keywords</p>'
            + '<div class="flex flex-wrap gap-1.5">'
            + keywords.map(k => '<span class="kw-chip">' + esc(k) + '</span>').join('')
            + '</div></div>';
    }

    document.getElementById('previewContent').innerHTML = bodyHtml;

    // Footer actions — use data-status to avoid single-quote in onclick
    const statusBtns = {
        draft:     '<button data-status="published" class="qs-btn flex items-center gap-2 px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-xl text-sm font-semibold transition ml-auto"><i class="fas fa-check"></i>Publish</button>',
        published: '<button data-status="archived"  class="qs-btn flex items-center gap-2 px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-600 rounded-xl text-sm font-semibold transition"><i class="fas fa-archive"></i>Archive</button>'
                 + '<button data-status="draft"     class="qs-btn flex items-center gap-2 px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-600 rounded-xl text-sm font-semibold transition ml-auto"><i class="fas fa-rotate-left"></i>Unpublish</button>',
        archived:  '<button data-status="published" class="qs-btn flex items-center gap-2 px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-xl text-sm font-semibold transition ml-auto"><i class="fas fa-check"></i>Re-publish</button>',
    };

    let actionsHtml = '<button onclick="copyPreviewPost()" class="flex items-center gap-2 px-4 py-2 bg-violet-600 hover:bg-violet-700 text-white rounded-xl text-sm font-semibold transition"><i class="fas fa-copy"></i>Copy Post</button>';
    actionsHtml += '<a href="create-smpost.php?id=' + esc(p.sys_id) + '" class="flex items-center gap-2 px-4 py-2 bg-amber-50 hover:bg-amber-100 text-amber-700 rounded-xl text-sm font-semibold transition"><i class="fas fa-pencil"></i>Edit</a>';
    actionsHtml += (statusBtns[p.status] ?? '');

    document.getElementById('previewActions').innerHTML = actionsHtml;
    document.getElementById('previewModal').classList.remove('hidden');
}

function closePreview() {
    document.getElementById('previewModal').classList.add('hidden');
    _previewPost = null;
}

// Close on backdrop click
document.addEventListener('DOMContentLoaded', function() {
    document.getElementById('previewModal').addEventListener('click', function(e) {
        if (e.target === this) closePreview();
    });

    // Status buttons in preview modal footer
    document.getElementById('previewActions').addEventListener('click', function(e) {
        const btn = e.target.closest('.qs-btn');
        if (!btn) return;
        quickStatus(btn.dataset.status);
    });
});

function copyPreviewPost() {
    if (!_previewPost) return;
    navigator.clipboard.writeText(_previewPost.post_text ?? '').then(
        () => showToast('success', 'Copied ✓'),
        () => showToast('error', 'Copy failed')
    );
}

async function quickStatus(status) {
    if (!_previewPost) return;
    const sysId = _previewPost.sys_id;

    try {
        const res  = await fetch(SMPOST_API, {
            method: 'POST', headers: {'Content-Type':'application/json'},
            body: JSON.stringify({ action:'status', sys_id: sysId, status }),
        });
        const json = await res.json();
        if (json.status !== 'success') throw new Error(json.message);

        // Update local data
        const post = allPosts.find(p => p.sys_id === sysId);
        if (post) post.status = status;
        _previewPost.status = status;

        updateStats();
        applyFilters();
        closePreview();
        showToast('success', 'Status → ' + status + ' ✓');
    } catch(e) {
        showToast('error', e.message ?? 'Failed');
    }
}

/* ── Delete ── */
function openDeleteModal(sysId) {
    document.getElementById('deleteSysId').value = sysId;
    document.getElementById('deleteModal').classList.remove('hidden');
}

async function confirmDelete() {
    const sysId = document.getElementById('deleteSysId').value;
    document.getElementById('deleteModal').classList.add('hidden');
    try {
        const res  = await fetch(SMPOST_API, {
            method:'POST', headers:{'Content-Type':'application/json'},
            body: JSON.stringify({ action:'delete', sys_id: sysId }),
        });
        const json = await res.json();
        if (json.status !== 'success') throw new Error(json.message);
        allPosts = allPosts.filter(p => p.sys_id !== sysId);
        updateStats();
        applyFilters();
        showToast('success', 'Post deleted');
    } catch(e) {
        showToast('error', e.message ?? 'Delete failed');
    }
}

/* ── Event delegation for grid action buttons ── */
document.getElementById('postsGrid').addEventListener('click', function(e) {
    const btn = e.target.closest('.sm-action');
    if (!btn) return;
    const id     = btn.dataset.id;
    const action = btn.dataset.action;
    if (!id) return;
    if (action === 'preview') openPreview(id);
    if (action === 'delete')  openDeleteModal(id);
});

/* ── Init ── */
loadPosts();
</script>
</body>
</html>