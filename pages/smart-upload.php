<?php
/**
 * TravHub Smart Upload v3 — Bulk Upload UI
 * ========================================
 * Drop-zone → parallel classification → review cards → commit → summary regen.
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
<style>
  .spinner { border: 2px solid transparent; border-top-color: currentColor; border-radius: 9999px; animation: spin 0.6s linear infinite; }
  @keyframes spin { to { transform: rotate(360deg); } }
</style>
</head>
<body class="bg-gray-50 min-h-screen">

<?php @include __DIR__ . '/../elements/aside.php'; ?>

<main class="md:ml-64 p-4 md:p-8">

  <!-- Header -->
  <header class="mb-6 flex items-center justify-between flex-wrap gap-4">
    <div>
      <a href="show-travelers.php?sys_id=<?= htmlspecialchars($traveler['sys_id']) ?>"
         class="text-sm text-gray-500 hover:text-gray-700">← Back to profile</a>
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
      If uploading a passport, treat it as:
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

  <!-- Dropzone -->
  <div id="dropzone"
       class="border-2 border-dashed border-gray-300 rounded-2xl p-12 text-center bg-white hover:bg-gray-50 transition cursor-pointer">
    <svg class="w-12 h-12 mx-auto mb-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
            d="M7 16a4 4 0 01-.88-7.9 5 5 0 019.9-1A5.5 5.5 0 0118 17H7zm5-9v6m0 0l-2-2m2 2l2-2"/>
    </svg>
    <p class="font-medium text-gray-700">Drop documents here, or click to browse</p>
    <p class="text-xs mt-1 text-gray-500">
      PDFs auto-convert to JPG pages · multi-page docs stay together · max 20 MB each
    </p>
    <input type="file" id="fileInput" multiple
           accept=".pdf,.jpg,.jpeg,.png,.webp" class="hidden">
  </div>

  <!-- Review section -->
  <div id="reviewSection" class="hidden mt-8">
    <div class="flex items-center justify-between mb-3 flex-wrap gap-2">
      <h2 class="text-lg font-semibold text-gray-900">Review & Confirm</h2>
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

  <!-- Status overlay (commit + summary regen) -->
  <div id="statusOverlay" class="hidden fixed inset-0 bg-black/40 flex items-center justify-center z-40">
    <div class="bg-white rounded-xl shadow-xl p-6 max-w-md w-full mx-4 text-center">
      <div class="w-12 h-12 spinner text-blue-600 mx-auto mb-4"></div>
      <h3 class="font-semibold text-gray-900 mb-1" id="statusTitle">Committing...</h3>
      <p class="text-sm text-gray-600" id="statusMsg">Storing files and writing to database</p>
    </div>
  </div>
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
// State
// ============================================================================
const TRAVELER_SYS_ID = <?= json_encode($traveler['sys_id']) ?>;
const DOC_TYPES = <?= json_encode($docTypes) ?>;

const $dropzone     = document.getElementById('dropzone');
const $fileInput    = document.getElementById('fileInput');
const $reviewSection= document.getElementById('reviewSection');
const $reviewCards  = document.getElementById('reviewCards');
const $commitBtn    = document.getElementById('commitBtn');
const $cancelBtn    = document.getElementById('cancelBtn');
const $overlay      = document.getElementById('statusOverlay');
const $statusTitle  = document.getElementById('statusTitle');
const $statusMsg    = document.getElementById('statusMsg');

const pending = new Map();      // token -> classify response (with user overrides applied at commit time)
let currentDiffToken = null;
let rowCounter = 0;

// ============================================================================
// Drag & drop wiring
// ============================================================================
$dropzone.addEventListener('click', () => $fileInput.click());
$dropzone.addEventListener('dragover', e => {
  e.preventDefault();
  $dropzone.classList.add('bg-blue-50');
});
$dropzone.addEventListener('dragleave', () => $dropzone.classList.remove('bg-blue-50'));
$dropzone.addEventListener('drop', e => {
  e.preventDefault();
  $dropzone.classList.remove('bg-blue-50');
  handleFiles(e.dataTransfer.files);
});
$fileInput.addEventListener('change', e => handleFiles(e.target.files));

async function handleFiles(fileList) {
  if (!fileList.length) return;
  $reviewSection.classList.remove('hidden');

  const files = Array.from(fileList);
  const indices = files.map(f => insertSkeleton(f));

  // Concurrency limit 3 — PDF rasterization is heavy on the server
  await runParallel(files.map((f, idx) => () => classifyOne(f, indices[idx])), 3);
}

async function classifyOne(file, rowIdx) {
  const passportStatus = document.querySelector('input[name="passportStatus"]:checked')?.value || 'auto';

  const fd = new FormData();
  fd.append('file', file);
  fd.append('traveler_sys_id', TRAVELER_SYS_ID);
  fd.append('passport_status', passportStatus);

  try {
    const res = await fetch('/api/travelers/classify-document.php', { method: 'POST', body: fd });
    const data = await res.json();

    if (!data.success) {
      renderError(rowIdx, data.message || 'Failed', data.duplicate, data.layer);
      return;
    }

    pending.set(data.token, { ...data, _userPassportStatus: passportStatus });
    renderCard(rowIdx, data);
  } catch (err) {
    renderError(rowIdx, err.message);
  }
}

function runParallel(tasks, concurrency) {
  return new Promise(resolve => {
    let i = 0, active = 0, done = 0;
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
// Skeleton + error rendering
// ============================================================================
function insertSkeleton(file) {
  const idx = ++rowCounter;
  const card = document.createElement('div');
  card.id = `card-${idx}`;
  card.className = 'bg-white rounded-xl shadow-sm p-5 border border-gray-200';
  card.innerHTML = `
    <div class="flex items-center gap-3">
      <span class="w-4 h-4 spinner text-blue-600"></span>
      <div class="flex-1 min-w-0">
        <div class="font-medium text-gray-900 truncate">${escapeHtml(file.name)}</div>
        <div class="text-xs text-gray-500 mt-1">Rasterizing & classifying with Gemini…</div>
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

// ============================================================================
// Review card
// ============================================================================
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
    const scenarioLabels = {
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
            <span class="font-medium text-blue-900">${scenarioLabels[a.scenario] || a.scenario}</span>
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

  // Merge banner (growth detection: e.g. "5 of 10 pages already exist")
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

  // Page badges
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
              class="text-gray-400 hover:text-red-600 text-xl leading-none">&times;</button>
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
};

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
// Commit + summary regeneration (the batch-end trigger)
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
    alert('Nothing to commit');
    return;
  }

  // ----- Stage 1: commit documents -----
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
    alert('Network error during commit: ' + err.message);
    $commitBtn.disabled = false;
    return;
  }

  if (!commitData.success && commitData.committed === 0) {
    hideStatus();
    alert('Commit failed: ' + (commitData.message || 'unknown'));
    $commitBtn.disabled = false;
    return;
  }

  // ----- Stage 2: batch-end summary regeneration (if anything was committed) -----
  if (commitData.summary_dirty) {
    showStatus('Regenerating traveler summary...',
               `Calling Gemini with full traveler context (${commitData.committed} document${commitData.committed !== 1 ? 's' : ''} added)`);
    try {
      const summaryRes = await fetch('/api/travelers/regenerate-summary.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          traveler_sys_id: TRAVELER_SYS_ID,
          trigger: 'document_upload',
          information_updated_for: commitData.pending_trigger || 'Document upload batch',
        }),
      });
      const summaryData = await summaryRes.json();
      if (!summaryData.success) {
        console.warn('Summary regen failed:', summaryData.error);
        // Don't block — documents were committed successfully
      }
    } catch (err) {
      console.warn('Summary regen network error:', err);
    }
  }

  hideStatus();

  // ----- Done -----
  const failed = commitData.results.filter(r => !r.success).length;
  let msg = `Committed ${commitData.committed} of ${commitData.total} documents.`;
  if (failed > 0) {
    msg += `\n\n${failed} failed:\n` +
           commitData.results.filter(r => !r.success).map(r => `• ${r.error}`).join('\n');
  }
  alert(msg);

  // Redirect to profile
  setTimeout(() => {
    location.href = `show-travelers.php?sys_id=${TRAVELER_SYS_ID}`;
  }, 500);
});

$cancelBtn.addEventListener('click', () => {
  if (confirm('Discard all pending uploads?')) location.reload();
});

// ============================================================================
// Status overlay helpers
// ============================================================================
function showStatus(title, msg) {
  $statusTitle.textContent = title;
  $statusMsg.textContent = msg;
  $overlay.classList.remove('hidden');
}
function hideStatus() {
  $overlay.classList.add('hidden');
}

// ============================================================================
// Utility
// ============================================================================
function escapeHtml(s) {
  return String(s ?? '').replace(/[&<>"']/g, c => ({
    '&':'&amp;', '<':'&lt;', '>':'&gt;', '"':'&quot;', "'":'&#39;'
  }[c]));
}
</script>

</body>
</html>