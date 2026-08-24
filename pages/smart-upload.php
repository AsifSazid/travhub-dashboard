<?php
/**
 * TravHub Smart Upload v3 — Bulk Upload UI (UCPT-compliant)
 * ==========================================================
 * UCPT zone with all four input methods visible:
 *   - Upload (click to browse)
 *   - Drag-Drop
 *   - Paste Image (Ctrl+V — zone-focused, single image per paste, multi-paste OK)
 *   - Text input (visible per UCPT rule; submission shows "Unsupported — working on it!")
 *
 * 100MB hard limit per file (frontend validates BEFORE upload).
 *
 * ⚠️ PHP CONFIG REQUIRED for 100MB to actually work end-to-end:
 *   upload_max_filesize = 100M
 *   post_max_size       = 110M
 *   max_execution_time  = 300
 *   memory_limit        = 512M
 *
 * Imagick policy.xml may also need:
 *   <policy domain="resource" name="memory" value="512MiB"/>
 *   <policy domain="resource" name="disk"   value="2GiB"/>
 *
 * URL: /pages/smart-upload.php?traveler=TR-XXXXXX
 */

session_start();
require_once __DIR__ . '/../auth/db.php';
require_once __DIR__ . '/../server/db_connection.php';

$travelerSysId = $_GET['traveler'] ?? '';
$stmt = $pdo->prepare("SELECT sys_id, name, passport_no, nid_no
                       FROM travelers WHERE sys_id = ?");
$stmt->execute([$travelerSysId]);
$traveler = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$traveler) {
    http_response_code(404);
    die('Traveler not found');
}

$docTypes = $pdo->query("
    SELECT doc_type, display_name, smb_folder, has_structured_schema
    FROM doc_type_registry
    WHERE is_active = 1
    ORDER BY display_order
")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Smart Upload — <?= htmlspecialchars($traveler['name']) ?></title>
<script src="https://cdn.tailwindcss.com"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
  .spinner { border: 2px solid transparent; border-top-color: currentColor; border-radius: 9999px; animation: spin 0.6s linear infinite; }
  @keyframes spin { to { transform: rotate(360deg); } }
  .ucpt-zone { transition: all 0.2s ease; }
  .ucpt-zone:focus-within { outline: 2px solid #3b82f6; outline-offset: 2px; }
  .ucpt-zone.dragover { background-color: #eff6ff; border-color: #3b82f6; }
  .ucpt-zone.paste-flash { background-color: #ecfdf5; border-color: #10b981; }
</style>
</head>
<body class="bg-gray-50 min-h-screen">

<?php @include __DIR__ . '/../elements/aside.php'; ?>
<?php @include __DIR__ . '/../elements/header.php'; ?>

<main class="md:ml-64 p-4 md:p-8">

  <!-- Header -->
  <header class="mb-6 flex items-center justify-between flex-wrap gap-4">
    <div>
      <a href="show-travelers.php?traveler_id=<?= htmlspecialchars($traveler['sys_id']) ?>"
         class="text-sm text-gray-500 hover:text-gray-700">
        <i class="fas fa-arrow-left mr-1"></i> Back to profile
      </a>
      <h1 class="text-2xl font-semibold text-gray-900 mt-1">Smart Document Upload</h1>
      <p class="text-sm text-gray-600 mt-1">
        Traveler: <strong><?= htmlspecialchars($traveler['name']) ?></strong>
        <span class="text-gray-400">·</span>
        <code class="text-xs"><?= htmlspecialchars($traveler['sys_id']) ?></code>
      </p>
    </div>
  </header>

  <!-- Passport status selector -->
  <div class="mb-4 bg-white border border-gray-200 rounded-xl p-4">
    <div class="text-sm font-medium text-gray-700 mb-2">
      <i class="fas fa-passport text-gray-500 mr-1"></i> If uploading a passport, treat it as:
    </div>
    <div class="flex gap-6 text-sm flex-wrap">
      <label class="flex items-center gap-2 cursor-pointer">
        <input type="radio" name="passportStatus" value="auto" checked>
        <span>Auto-detect</span>
        <span class="text-xs text-gray-500">(by expiry date)</span>
      </label>
      <label class="flex items-center gap-2 cursor-pointer">
        <input type="radio" name="passportStatus" value="current">
        <span>Current passport</span>
      </label>
      <label class="flex items-center gap-2 cursor-pointer">
        <input type="radio" name="passportStatus" value="previous">
        <span>Previous passport</span>
      </label>
    </div>
  </div>

  <!-- ═══════════════════════════════════════════════════════════════════ -->
  <!-- UCPT ZONE                                                            -->
  <!-- Upload + Drag-Drop + Paste Image + Text — all visible at once       -->
  <!-- ═══════════════════════════════════════════════════════════════════ -->
  <div id="ucptZone"
       tabindex="0"
       class="ucpt-zone border-2 border-dashed border-gray-300 rounded-2xl bg-white p-6 md:p-8 focus:outline-none">

    <!-- Click-to-upload + drag-drop hint -->
    <div class="text-center mb-6">
      <i class="fas fa-cloud-upload-alt text-4xl text-gray-400 mb-3"></i>
      <p class="font-medium text-gray-700">
        <button type="button" onclick="document.getElementById('fileInput').click()"
                class="text-blue-600 hover:underline">Click to browse</button>,
        drag &amp; drop files here, or
        <span class="text-emerald-600 font-medium">click inside this zone</span> and paste an image (Ctrl+V)
      </p>
      <p class="text-xs mt-2 text-gray-500">
        PDF, JPG, PNG, WEBP · multi-page docs stay together · max <strong>100 MB</strong> per file
      </p>
      <p class="text-[11px] mt-1 text-gray-400">
        <i class="fas fa-keyboard"></i> Paste is zone-focused. Click anywhere on this zone first, then Ctrl+V.
      </p>
      <input type="file" id="fileInput" multiple
             accept=".pdf,.jpg,.jpeg,.png,.webp" class="hidden">
    </div>

    <!-- Divider -->
    <div class="relative my-4">
      <div class="absolute inset-0 flex items-center"><div class="w-full border-t border-gray-200"></div></div>
      <div class="relative flex justify-center text-xs">
        <span class="bg-white px-3 text-gray-400 uppercase tracking-wider">Or paste text</span>
      </div>
    </div>

    <!-- Text input (UCPT rule: visible but currently disabled) -->
    <div class="grid grid-cols-1 gap-2">
      <textarea id="textInput"
                rows="4"
                placeholder="Paste or type document text here (OCR output, statement contents, letter body, etc.)"
                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm font-mono resize-none focus:border-blue-400 focus:ring-2 focus:ring-blue-100 focus:outline-none"></textarea>
      <div class="flex items-center justify-between gap-3">
        <span class="text-[11px] text-gray-400">
          <i class="fas fa-flask text-amber-500"></i> Text classification is in development
        </span>
        <button type="button"
                id="processTextBtn"
                class="px-4 py-1.5 text-sm bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300">
          Process Text
        </button>
      </div>
    </div>

    <!-- "Unsupported" banner appears here when text is submitted -->
    <div id="textUnsupportedBanner" class="hidden mt-3 bg-amber-50 border border-amber-200 rounded-lg p-3 text-sm text-amber-800">
      <i class="fas fa-tools text-amber-600 mr-1"></i>
      <strong>Unsupported — we're working on it!</strong>
      <p class="text-xs mt-1 text-amber-700">For now, please upload, drop, or paste an image of the document instead.</p>
    </div>
  </div>

  <!-- Review section -->
  <div id="reviewSection" class="hidden mt-8">
    <div class="flex items-center justify-between mb-3 flex-wrap gap-2">
      <h2 class="text-lg font-semibold text-gray-900">
        Review &amp; Confirm
        <span id="reviewCount" class="ml-2 text-sm font-normal text-gray-500"></span>
      </h2>
      <div class="flex gap-2">
        <button id="cancelBtn"
                class="px-4 py-2 text-sm text-gray-600 hover:text-gray-900">
          Cancel All
        </button>
        <button id="commitBtn"
                class="px-5 py-2 text-sm bg-emerald-600 text-white rounded-lg hover:bg-emerald-700 disabled:opacity-50">
          Commit All
        </button>
      </div>
    </div>

    <div id="reviewCards" class="space-y-4"></div>
  </div>

  <!-- Status overlay -->
  <div id="statusOverlay" class="hidden fixed inset-0 bg-black/40 flex items-center justify-center z-40">
    <div class="bg-white rounded-xl shadow-xl p-6 max-w-md w-full mx-4 text-center">
      <div class="w-12 h-12 spinner text-blue-600 mx-auto mb-4"></div>
      <h3 class="font-semibold text-gray-900 mb-1" id="statusTitle">Committing...</h3>
      <p class="text-sm text-gray-600" id="statusMsg">Storing files and writing to database</p>
    </div>
  </div>

  <!-- Toast -->
  <div id="toast" class="hidden fixed bottom-6 right-6 bg-gray-900 text-white px-4 py-3 rounded-lg shadow-xl text-sm z-50 max-w-sm"></div>
</main>

<!-- Bio diff modal -->
<div id="diffModal" class="fixed inset-0 bg-black/50 hidden items-center justify-center z-50">
  <div class="bg-white rounded-xl max-w-2xl w-full mx-4 max-h-[80vh] overflow-hidden flex flex-col">
    <div class="p-5 border-b border-gray-200 flex items-center justify-between">
      <h3 class="text-lg font-semibold">Bio Page Updates Detected</h3>
      <button onclick="closeDiff()"
              class="text-gray-500 hover:text-gray-900 text-2xl leading-none">&times;</button>
    </div>
    <div class="p-5 overflow-y-auto flex-1" id="diffBody"></div>
    <div class="p-4 border-t border-gray-200 flex justify-end gap-2 bg-gray-50">
      <button onclick="closeDiff()"
              class="px-4 py-2 text-sm text-gray-600 hover:text-gray-900">Skip Updates</button>
      <button onclick="approveDiff()"
              class="px-5 py-2 text-sm bg-blue-600 text-white rounded-lg hover:bg-blue-700">
        Apply Selected
      </button>
    </div>
  </div>
</div>

<script>
// ============================================================================
// Config + State
// ============================================================================
const TRAVELER_SYS_ID = <?= json_encode($traveler['sys_id']) ?>;
const DOC_TYPES       = <?= json_encode($docTypes) ?>;
// const MAX_BYTES       = 100 * 1024 * 1024; // 100 MB hard limit (frontend)
const MAX_BYTES       = 50 * 1024 * 1024; // 100 MB hard limit (frontend)
const ALLOWED_EXTS    = ['pdf', 'jpg', 'jpeg', 'png', 'webp'];

const $zone          = document.getElementById('ucptZone');
const $fileInput     = document.getElementById('fileInput');
const $textInput     = document.getElementById('textInput');
const $processTextBtn= document.getElementById('processTextBtn');
const $textBanner    = document.getElementById('textUnsupportedBanner');
const $reviewSection = document.getElementById('reviewSection');
const $reviewCards   = document.getElementById('reviewCards');
const $reviewCount   = document.getElementById('reviewCount');
const $commitBtn     = document.getElementById('commitBtn');
const $cancelBtn     = document.getElementById('cancelBtn');
const $overlay       = document.getElementById('statusOverlay');
const $statusTitle   = document.getElementById('statusTitle');
const $statusMsg     = document.getElementById('statusMsg');
const $toast         = document.getElementById('toast');

let zoneFocused = false;            // true while UCPT zone holds keyboard focus
const pending = new Map();           // token -> classification response
let currentDiffToken = null;
let rowCounter = 0;
let pastedCounter = 0;

// ============================================================================
// UCPT Channel 1+2: Click-to-browse + Drag-drop
// ============================================================================
$fileInput.addEventListener('change', e => handleFiles(e.target.files));

$zone.addEventListener('dragover', e => {
  e.preventDefault();
  $zone.classList.add('dragover');
});
$zone.addEventListener('dragleave', e => {
  // Only clear when leaving the zone itself, not bubbling from a child
  if (e.target === $zone) $zone.classList.remove('dragover');
});
$zone.addEventListener('drop', e => {
  e.preventDefault();
  $zone.classList.remove('dragover');
  handleFiles(e.dataTransfer.files);
});

// ============================================================================
// UCPT Channel 3: Paste Image (zone-focused, sequential multi-paste)
// ============================================================================

// Track whether the UCPT zone is focused (zone-focused paste rule from spec)
$zone.addEventListener('focus', () => { zoneFocused = true; });
$zone.addEventListener('blur',  () => { zoneFocused = false; });
$zone.addEventListener('click', () => $zone.focus());

// Listen globally but only act when zone is focused (and not inside textarea)
document.addEventListener('paste', e => {
  if (!zoneFocused) return;

  // Don't hijack paste when typing inside the textarea
  if (document.activeElement === $textInput) return;

  const items = (e.clipboardData || window.clipboardData)?.items;
  if (!items) return;

  // Find FIRST image in clipboard (per spec: single image per paste,
  // user can paste again for additional images)
  for (const item of items) {
    if (item.type && item.type.startsWith('image/')) {
      const blob = item.getAsFile();
      if (!blob) continue;

      // Synthesize a File with a friendly name
      pastedCounter++;
      const ext = (item.type.split('/')[1] || 'png').toLowerCase();
      const safeExt = ext === 'jpeg' ? 'jpg' : ext;
      const filename = `pasted-image-${pastedCounter}.${safeExt}`;
      const file = new File([blob], filename, { type: item.type });

      // Visual feedback
      $zone.classList.add('paste-flash');
      setTimeout(() => $zone.classList.remove('paste-flash'), 400);

      e.preventDefault();
      handleFiles([file]);
      return; // exit after first image
    }
  }
});

// ============================================================================
// UCPT Channel 4: Text input — placeholder (unsupported notice)
// ============================================================================
$processTextBtn.addEventListener('click', () => {
  const text = $textInput.value.trim();
  if (text.length === 0) {
    toast('Type or paste some text first');
    return;
  }
  $textBanner.classList.remove('hidden');
  $textBanner.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
});

$textInput.addEventListener('input', () => {
  // Hide banner once user starts editing again
  if ($textBanner && !$textBanner.classList.contains('hidden')) {
    $textBanner.classList.add('hidden');
  }
});

// ============================================================================
// File pipeline (shared by all 3 working channels)
// ============================================================================
async function handleFiles(fileList) {
  if (!fileList || !fileList.length) return;
  $reviewSection.classList.remove('hidden');

  const files = Array.from(fileList);
  const accepted = [];
  for (const f of files) {
    const reason = validateFile(f);
    if (reason) {
      toast(`${f.name}: ${reason}`, 'error');
      continue;
    }
    accepted.push(f);
  }

  const indices = accepted.map(f => insertSkeleton(f));
  await runParallel(accepted.map((f, idx) => () => classifyOne(f, indices[idx])), 3);
  updateReviewCount();
}

function validateFile(file) {
  if (file.size > MAX_BYTES) {
    return `Too large (${(file.size / 1024 / 1024).toFixed(1)} MB, max 100 MB)`;
  }
  const ext = (file.name.split('.').pop() || '').toLowerCase();
  if (!ALLOWED_EXTS.includes(ext)) {
    return `Unsupported type (.${ext})`;
  }
  return null;
}

async function classifyOne(file, rowIdx) {
  const passportStatus = document.querySelector('input[name="passportStatus"]:checked')?.value || 'auto';
  const ext = (file.name.split('.').pop() || '').toLowerCase();

  if (ext === 'pdf') {
    // PDF: Step 1 — split into pages
    const fd = new FormData();
    fd.append('action', 'split');
    fd.append('file', file);

    let splitData;
    try {
      const res = await fetch('/api/travelers/classify-document.php', { method: 'POST', body: fd });
      splitData = await res.json();
    } catch (err) {
      renderError(rowIdx, 'Split failed: ' + err.message);
      return;
    }

    if (!splitData.success) {
      renderError(rowIdx, splitData.message || 'Split failed');
      return;
    }

    const pageTokens = splitData.page_tokens || [];
    if (!pageTokens.length) {
      renderError(rowIdx, 'No pages found in PDF');
      return;
    }

    // Step 2 — classify each page in parallel
    const classifyPromises = pageTokens.map(async ([pageToken, pagePath, pageNo], i) => {
      const targetIdx = i === 0 ? rowIdx : insertSkeleton({ name: file.name + ' p' + pageNo, size: file.size });

      const fd2 = new FormData();
      fd2.append('action', 'classify');
      fd2.append('traveler_sys_id', TRAVELER_SYS_ID);
      fd2.append('passport_status', passportStatus);
      fd2.append('page_path', pagePath);
      fd2.append('original_filename', file.name);
      fd2.append('file_size', file.size);
      fd2.append('page_no', pageNo);

      try {
        const res2 = await fetch('/api/travelers/classify-document.php', { method: 'POST', body: fd2 });
        const doc  = await res2.json();
        if (!doc.success) { renderError(targetIdx, doc.message || 'Classify failed'); return; }
        pending.set(doc.token, { ...doc, _userPassportStatus: passportStatus });
        renderCard(targetIdx, doc);
      } catch (err) {
        renderError(targetIdx, 'Page error: ' + err.message);
      }
    });

    await Promise.all(classifyPromises);

  } else {
    // Image: single classify
    const fd = new FormData();
    fd.append('action', 'classify');
    fd.append('file', file);
    fd.append('traveler_sys_id', TRAVELER_SYS_ID);
    fd.append('passport_status', passportStatus);

    try {
      const res  = await fetch('/api/travelers/classify-document.php', { method: 'POST', body: fd });
      const data = await res.json();
      if (!data.success) { renderError(rowIdx, data.message || 'Failed', data.duplicate, data.layer); return; }
      pending.set(data.token, { ...data, _userPassportStatus: passportStatus });
      renderCard(rowIdx, data);
    } catch (err) {
      renderError(rowIdx, err.message);
    }
  }
}

function runParallel(tasks, concurrency) {
  return new Promise(resolve => {
    let i = 0, active = 0, done = 0;
    if (tasks.length === 0) return resolve();
    const next = () => {
      while (active < concurrency && i < tasks.length) {
        active++;
        tasks[i++]().finally(() => {
          active--; done++;
          if (done === tasks.length) resolve();
          else next();
        });
      }
    };
    next();
  });
}

// ============================================================================
// Review-card rendering
// ============================================================================
function insertSkeleton(file) {
  const idx = ++rowCounter;
  const card = document.createElement('div');
  card.id = `card-${idx}`;
  card.className = 'bg-white rounded-xl shadow-sm p-5 border border-gray-200';
  const sizeMb = (file.size / 1024 / 1024).toFixed(1);
  card.innerHTML = `
    <div class="flex items-center gap-3">
      <span class="w-4 h-4 spinner text-blue-600"></span>
      <div class="flex-1 min-w-0">
        <div class="font-medium text-gray-900 truncate">${escapeHtml(file.name)}</div>
        <div class="text-xs text-gray-500 mt-1">${sizeMb} MB · Rasterizing & classifying with Gemini…</div>
      </div>
    </div>`;
  $reviewCards.appendChild(card);
  return idx;
}

function renderError(idx, msg, isDup, layer) {
  const card = document.getElementById(`card-${idx}`);
  if (!card) return;
  const tone = isDup ? 'amber' : 'red';
  const icon = isDup ? '⚠️' : '❌';
  const badge = isDup && layer
    ? `<span class="text-[10px] bg-${tone}-200 text-${tone}-900 px-2 py-0.5 rounded ml-2">Layer ${layer}</span>`
    : '';
  card.className = `bg-${tone}-50 rounded-xl p-5 border border-${tone}-200`;
  card.innerHTML = `<div class="text-${tone}-800 font-medium">${icon} ${escapeHtml(msg)} ${badge}</div>`;
}

function renderCard(idx, data) {
  const card = document.getElementById(`card-${idx}`);
  if (!card) return;
  card.dataset.token = data.token;
  card.className = 'bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden';

  const typeOptions = DOC_TYPES.map(t => `
    <option value="${t.doc_type}" ${t.doc_type === data.doc_type ? 'selected' : ''}>
      ${escapeHtml(t.display_name)} → ${t.smb_folder}
    </option>`).join('');

  const confPct = Math.round(data.confidence * 100);
  const confColor = data.confidence >= 0.7 ? 'emerald' : data.confidence >= 0.4 ? 'amber' : 'red';
  const reviewBadge = data.needs_review
    ? `<span class="ml-2 px-2 py-0.5 text-[10px] font-medium bg-amber-100 text-amber-800 rounded uppercase">Review</span>` : '';

  // Passport banner
  let passportBanner = '';
  if (data.doc_type === 'passport' && data.passport_analysis) {
    const a = data.passport_analysis;
    const labels = {
      'first_time': '🆕 First passport for this traveler',
      'matches_existing_current': '🔄 Same passport already on file — only new visa pages will be added',
      'renewal_demote_old': '🆙 Passport renewal — old passport will be marked as Previous',
      'historical_upload': '📜 Historical passport upload (marked as Previous)',
    };
    const diffCount = Object.keys(a.bio_diff || {}).length;
    passportBanner = `
      <div class="bg-blue-50 border-b border-blue-100 px-5 py-3 text-sm">
        <div class="flex items-center justify-between flex-wrap gap-2">
          <div>
            <span class="font-medium text-blue-900">${labels[a.scenario] || a.scenario}</span>
            <span class="ml-2 text-xs text-blue-700">Status: <strong>${a.resolved_status}</strong></span>
          </div>
          ${diffCount > 0 ? `
            <button onclick="openDiff('${data.token}')"
                    class="px-3 py-1 text-xs bg-blue-600 text-white rounded hover:bg-blue-700">
              Review ${diffCount} bio update${diffCount > 1 ? 's' : ''}
            </button>` : ''}
        </div>
      </div>`;
  }

  // Merge banner
  let mergeBanner = '';
  if (data.merge_analysis) {
    const m = data.merge_analysis;
    const isMerge = m.new_pages_to_add > 0;
    mergeBanner = `
      <div class="bg-${isMerge ? 'amber' : 'red'}-50 border-b border-${isMerge ? 'amber' : 'red'}-100 px-5 py-3 text-sm">
        <div class="flex items-center justify-between flex-wrap gap-2">
          <div>
            <span class="font-medium text-${isMerge ? 'amber' : 'red'}-900">
              🔁 Matches existing document <code class="text-xs">${m.existing_sys_id}</code>
            </span>
            <span class="ml-2 text-xs text-${isMerge ? 'amber' : 'red'}-700">
              ${m.duplicate_pages} duplicate, ${m.new_pages_to_add} new page${m.new_pages_to_add !== 1 ? 's' : ''} to append
            </span>
          </div>
          ${isMerge ? `
            <label class="flex items-center gap-2 text-xs text-amber-900">
              <input type="checkbox" data-field="accept_merge" data-token="${data.token}" checked>
              Merge new pages into existing document
            </label>` : ''}
        </div>
      </div>`;
  }

  const pageBadges = (data.pages || []).map(p => {
    const tag = p.country ? `${p.page_type} · ${p.country}` : p.page_type;
    return `<span class="inline-block px-2 py-0.5 text-[10px] bg-gray-100 text-gray-700 rounded mr-1 mb-1">
              p${p.page_no}: ${escapeHtml(tag)}
            </span>`;
  }).join('');

  card.innerHTML = `
    <div class="px-5 py-4 flex items-start justify-between gap-3 border-b border-gray-100">
      <div class="flex-1 min-w-0">
        <div class="font-medium text-gray-900 truncate">${escapeHtml(data.original_filename)}</div>
        <div class="text-xs text-gray-500 mt-1">
          ${data.page_count} page${data.page_count > 1 ? 's' : ''} · ${data.language}
          · <span class="text-${confColor}-700 font-semibold">${confPct}% confidence</span>
          ${reviewBadge}
        </div>
      </div>
      <button onclick="removeCard('${data.token}', ${idx})"
              class="text-gray-400 hover:text-red-600 text-xl leading-none"
              title="Remove">&times;</button>
    </div>
    ${passportBanner}
    ${mergeBanner}
    <div class="p-5 grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
      <div>
        <label class="block text-xs font-medium text-gray-600 mb-1">Document Type</label>
        <select class="w-full border border-gray-300 rounded px-2 py-1.5"
                data-field="doc_type" data-token="${data.token}">${typeOptions}</select>
      </div>
      <div>
        <label class="block text-xs font-medium text-gray-600 mb-1">Document Number</label>
        <input type="text" class="w-full border border-gray-300 rounded px-2 py-1.5"
               value="${escapeHtml(data.doc_number || '')}"
               data-field="doc_number" data-token="${data.token}">
      </div>
      <div class="md:col-span-2">
        <label class="block text-xs font-medium text-gray-600 mb-1">
          Filename stem <span class="text-gray-400 font-normal">(pages become {stem}_p1.jpg, _p2.jpg...)</span>
        </label>
        <input type="text" class="w-full border border-gray-300 rounded px-2 py-1.5 font-mono text-xs"
               value="${escapeHtml(data.suggested_filename_stem)}"
               data-field="suggested_filename_stem" data-token="${data.token}">
      </div>
      <div>
        <label class="block text-xs font-medium text-gray-600 mb-1">Issue Date</label>
        <input type="date" class="w-full border border-gray-300 rounded px-2 py-1.5"
               value="${data.issue_date || ''}"
               data-field="issue_date" data-token="${data.token}">
      </div>
      <div>
        <label class="block text-xs font-medium text-gray-600 mb-1">Expiry Date</label>
        <input type="date" class="w-full border border-gray-300 rounded px-2 py-1.5"
               value="${data.expiry_date || ''}"
               data-field="expiry_date" data-token="${data.token}">
      </div>
      <div class="md:col-span-2">
        <label class="block text-xs font-medium text-gray-600 mb-1">Document Summary</label>
        <textarea class="w-full border border-gray-300 rounded px-2 py-1.5 text-xs"
                  rows="2" data-field="summary" data-token="${data.token}">${escapeHtml(data.summary)}</textarea>
      </div>
      ${pageBadges ? `
        <div class="md:col-span-2">
          <div class="text-xs font-medium text-gray-600 mb-1">Page breakdown</div>
          ${pageBadges}
        </div>` : ''}
    </div>`;
}

window.removeCard = (token, idx) => {
  pending.delete(token);
  document.getElementById(`card-${idx}`)?.remove();
  updateReviewCount();
};

function updateReviewCount() {
  const n = pending.size;
  $reviewCount.textContent = n > 0 ? `(${n} document${n !== 1 ? 's' : ''})` : '';
}

// ============================================================================
// Bio diff modal
// ============================================================================
window.openDiff = (token) => {
  const data = pending.get(token);
  if (!data || !data.passport_analysis) return;
  const diff = data.passport_analysis.bio_diff || {};
  currentDiffToken = token;

  const body = document.getElementById('diffBody');
  body.innerHTML = `
    <p class="text-sm text-gray-600 mb-4">
      Your existing passport data has these fields. The new upload extracted different values.
      Tick fields you want to <strong>update</strong>:
    </p>
    <table class="w-full text-sm">
      <thead class="bg-gray-50 text-xs text-gray-600">
        <tr>
          <th class="text-left px-2 py-1.5 w-8"><input type="checkbox" id="diffAll" checked></th>
          <th class="text-left px-2 py-1.5">Field</th>
          <th class="text-left px-2 py-1.5">Old</th>
          <th class="text-left px-2 py-1.5">New</th>
        </tr>
      </thead>
      <tbody>
        ${Object.entries(diff).map(([field, vals]) => `
          <tr class="border-t border-gray-100">
            <td class="px-2 py-2">
              <input type="checkbox" class="diff-check" data-field="${field}" checked>
            </td>
            <td class="px-2 py-2 font-medium text-gray-700">${field}</td>
            <td class="px-2 py-2 text-gray-500 line-through">${escapeHtml(vals.old || '(empty)')}</td>
            <td class="px-2 py-2 text-emerald-700 font-medium">${escapeHtml(vals.new)}</td>
          </tr>`).join('')}
      </tbody>
    </table>`;

  document.getElementById('diffAll').addEventListener('change', e => {
    document.querySelectorAll('.diff-check').forEach(cb => cb.checked = e.target.checked);
  });
  const m = document.getElementById('diffModal');
  m.classList.remove('hidden');
  m.classList.add('flex');
};

window.closeDiff = () => {
  const m = document.getElementById('diffModal');
  m.classList.add('hidden');
  m.classList.remove('flex');
  currentDiffToken = null;
};

window.approveDiff = () => {
  if (!currentDiffToken) return;
  const approved = [];
  document.querySelectorAll('.diff-check:checked').forEach(cb => approved.push(cb.dataset.field));
  const data = pending.get(currentDiffToken);
  data._approveBioUpdates = approved.length > 0;
  data._approvedBioFields = approved;
  pending.set(currentDiffToken, data);
  closeDiff();

  const card = document.querySelector(`[data-token="${currentDiffToken}"]`);
  const btn = card?.querySelector('button[onclick^="openDiff"]');
  if (btn) {
    btn.textContent = `✓ ${approved.length} update${approved.length !== 1 ? 's' : ''} approved`;
    btn.className = 'px-3 py-1 text-xs bg-emerald-600 text-white rounded';
  }
};

// ============================================================================
// Commit + batch-end summary regeneration
// ============================================================================
$commitBtn.addEventListener('click', async () => {
  const items = [];
  document.querySelectorAll('#reviewCards [data-token]').forEach(card => {
    const token = card.dataset.token;
    const original = pending.get(token);
    if (!original) return;
    const get = (field) => card.querySelector(`[data-field="${field}"]`)?.value ?? '';
    const checked = (field) => card.querySelector(`input[data-field="${field}"]`)?.checked ?? false;

    const docType = get('doc_type');
    items.push({
      token,
      doc_type: docType,
      doc_number: get('doc_number'),
      suggested_filename_stem: get('suggested_filename_stem'),
      summary: get('summary'),
      issue_date: get('issue_date') || null,
      expiry_date: get('expiry_date') || null,
      classification_mode: docType !== original.doc_type
        ? 'overridden'
        : (original.needs_review ? 'manual' : 'auto'),
      passport_status: original.passport_analysis?.resolved_status || null,
      approve_bio_updates: !!original._approveBioUpdates,
      approved_bio_fields: original._approvedBioFields || null,
      accept_merge: original.merge_analysis ? checked('accept_merge') : false,
    });
  });

  if (!items.length) {
    toast('Nothing to commit');
    return;
  }

  showStatus('Committing documents...', 'Storing files and writing to database');
  $commitBtn.disabled = true;

  let commitData;
  try {
    const res = await fetch('/api/travelers/commit-documents.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ traveler_sys_id: TRAVELER_SYS_ID, items }),
    });
    commitData = await res.json();
  } catch (err) {
    hideStatus();
    toast('Network error during commit: ' + err.message, 'error');
    $commitBtn.disabled = false;
    return;
  }

  if (!commitData.success && commitData.committed === 0) {
    hideStatus();
    toast('Commit failed: ' + (commitData.message || 'unknown'), 'error');
    $commitBtn.disabled = false;
    return;
  }

  // Batch-end summary regeneration
  if (commitData.summary_dirty) {
    showStatus('Regenerating traveler summary...',
               `Calling Gemini with full traveler context (${commitData.committed} document${commitData.committed !== 1 ? 's' : ''} added)`);
    try {
      const summaryRes  = await fetch('/api/travelers/regenerate-summary.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          traveler_sys_id: TRAVELER_SYS_ID,
          trigger: 'document_upload',
          information_updated_for: commitData.pending_trigger || 'Document upload batch',
        }),
      });
      const summaryData = await summaryRes.json();

      // আগে এই response কখনো check হতো না — Gemini call fail করলেও (timeout,
      // API key issue, ইত্যাদি) ইউজার কিছু জানতে পারতো না, summary silently
      // পুরনোই থেকে যেত। এখন fail হলে সরাসরি জানিয়ে দেওয়া হচ্ছে।
      if (!summaryData.success) {
        toast('Summary update ব্যর্থ হয়েছে: ' + (summaryData.message || 'অজানা কারণ'), 'error');
      }
    } catch (err) {
      console.warn('Summary regen network error:', err);
      toast('Summary update এ network সমস্যা: ' + err.message, 'error');
    }
  }

  hideStatus();

  const failed = commitData.results.filter(r => !r.success).length;
  let msg = `Committed ${commitData.committed} of ${commitData.total} documents.`;
  if (failed > 0) {
    msg += `\n\n${failed} failed:\n` +
           commitData.results.filter(r => !r.success).map(r => `• ${r.message || r.error || 'Unknown error'}`).join('\n');
  }
  alert(msg);

  setTimeout(() => {
    location.href = `show-travelers.php?traveler_id=${TRAVELER_SYS_ID}`;
  }, 500);
});

$cancelBtn.addEventListener('click', () => {
  if (confirm('Discard all pending uploads?')) location.reload();
});

// ============================================================================
// Toast + status helpers
// ============================================================================
function toast(msg, tone = 'info') {
  $toast.textContent = msg;
  $toast.className = `fixed bottom-6 right-6 px-4 py-3 rounded-lg shadow-xl text-sm z-50 max-w-sm ${
    tone === 'error' ? 'bg-red-600 text-white' : 'bg-gray-900 text-white'
  }`;
  $toast.classList.remove('hidden');
  clearTimeout(toast._t);
  toast._t = setTimeout(() => $toast.classList.add('hidden'), 3500);
}
function showStatus(title, msg) {
  $statusTitle.textContent = title;
  $statusMsg.textContent = msg;
  $overlay.classList.remove('hidden');
}
function hideStatus() {
  $overlay.classList.add('hidden');
}
function escapeHtml(s) {
  return String(s ?? '').replace(/[&<>"']/g, c => ({
    '&':'&amp;', '<':'&lt;', '>':'&gt;', '"':'&quot;', "'":'&#39;'
  }[c]));
}
</script>

</body>
</html>