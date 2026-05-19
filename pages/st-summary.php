<?php
// st-summary.php — included by show-travelers.php
// $pdo and $traveler are already in scope (no bootstrap needed)

$summary = !empty($traveler['summary']) ? json_decode($traveler['summary'], true) : null;

$typeLabels = [];
foreach ($pdo->query("SELECT doc_type, display_name, smb_folder
                      FROM doc_type_registry
                      WHERE is_active = 1
                      ORDER BY display_order") as $r) {
    $typeLabels[$r['doc_type']] = ['name' => $r['display_name'], 'folder' => $r['smb_folder']];
}
?>

  <!-- ── Action bar ───────────────────────────────────────────────────── -->
  <div class="flex items-center justify-between flex-wrap gap-3">
    <h2 class="text-xl font-semibold text-gray-900">Documents & Summary</h2>
    <div class="flex gap-2">
      <button id="regenerateSummaryBtn"
              class="px-4 py-2 text-sm bg-blue-600 text-white rounded-lg hover:bg-blue-700 flex items-center gap-2">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
        </svg>
        <span>Regenerate Summary</span>
      </button>
      <a href="smart-upload.php?traveler=<?= urlencode($traveler['sys_id']) ?>"
         class="px-4 py-2 text-sm bg-emerald-600 text-white rounded-lg hover:bg-emerald-700 flex items-center gap-2">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M12 4v16m8-8H4"/>
        </svg>
        <span>Smart Upload</span>
      </a>
    </div>
  </div>

  <!-- ── Summary panel ────────────────────────────────────────────────── -->
  <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
    <div class="px-5 py-3 bg-gray-50 border-b border-gray-200 flex items-center justify-between">
      <div>
        <h3 class="font-semibold text-gray-900">Traveler Summary</h3>
        <?php if ($summary): ?>
          <p class="text-xs text-gray-500 mt-0.5">
            Version <?= (int)($summary['summary_count'] ?? 0) ?>
            · Updated <?= htmlspecialchars($summary['date'] ?? '—') ?>
            · <em><?= htmlspecialchars($summary['information_updated_for'] ?? '') ?></em>
          </p>
        <?php endif; ?>
      </div>
      <?php if (!empty($traveler['summary_dirty'])): ?>
        <span class="text-xs bg-amber-100 text-amber-800 px-2 py-1 rounded">
          ⏳ Pending: <?= htmlspecialchars($traveler['summary_pending_trigger'] ?? 'updates') ?>
        </span>
      <?php endif; ?>
    </div>

    <div class="p-5" id="summaryContent">
      <?php if ($summary && !empty($summary['summary_text'])): ?>
        <?php $st = $summary['summary_text']; ?>

        <!-- Headline -->
        <?php if (!empty($st['headline'])): ?>
          <p class="text-base font-medium text-gray-900 leading-relaxed mb-4">
            <?= htmlspecialchars($st['headline']) ?>
          </p>
        <?php endif; ?>

        <!-- Current status -->
        <?php if (!empty($st['current_status'])): ?>
          <div class="grid grid-cols-1 md:grid-cols-3 gap-3 mb-4 text-sm">
            <div class="bg-blue-50 rounded-lg p-3">
              <div class="text-xs text-blue-600 font-medium uppercase mb-1">Passport</div>
              <div class="text-blue-900"><?= htmlspecialchars($st['current_status']['passport'] ?? '—') ?></div>
            </div>
            <div class="bg-emerald-50 rounded-lg p-3">
              <div class="text-xs text-emerald-600 font-medium uppercase mb-1">Active Visas</div>
              <div class="text-emerald-900 text-xs leading-relaxed">
                <?php
                  $visas = $st['current_status']['active_visas'] ?? [];
                  echo $visas ? htmlspecialchars(implode(', ', $visas)) : '—';
                ?>
              </div>
            </div>
            <div class="bg-violet-50 rounded-lg p-3">
              <div class="text-xs text-violet-600 font-medium uppercase mb-1">Upcoming Trips</div>
              <div class="text-violet-900 text-xs leading-relaxed">
                <?php
                  $trips = $st['current_status']['upcoming_trips'] ?? [];
                  echo $trips ? htmlspecialchars(implode(', ', $trips)) : '—';
                ?>
              </div>
            </div>
          </div>
        <?php endif; ?>

        <!-- Highlights -->
        <?php if (!empty($st['highlights'])): ?>
          <div class="mb-4">
            <h4 class="text-xs font-medium text-gray-600 uppercase mb-2">Highlights</h4>
            <ul class="space-y-1 text-sm text-gray-700">
              <?php foreach ($st['highlights'] as $h): ?>
                <li class="flex gap-2"><span class="text-emerald-500">•</span><span><?= htmlspecialchars($h) ?></span></li>
              <?php endforeach; ?>
            </ul>
          </div>
        <?php endif; ?>

        <!-- Recent activity -->
        <?php if (!empty($st['recent_activity'])): ?>
          <div class="mb-4 text-xs text-gray-600 italic">
            <?= htmlspecialchars($st['recent_activity']) ?>
          </div>
        <?php endif; ?>

        <!-- Details (long-form) -->
        <?php if (!empty($st['details'])): ?>
          <details class="mt-4">
            <summary class="cursor-pointer text-sm font-medium text-blue-600 hover:text-blue-800">
              Read full details
            </summary>
            <div class="mt-3 p-4 bg-gray-50 rounded-lg text-sm text-gray-700 leading-relaxed whitespace-pre-line">
              <?= htmlspecialchars($st['details']) ?>
            </div>
          </details>
        <?php endif; ?>

      <?php else: ?>
        <div class="text-center py-8 text-gray-500">
          <p class="text-sm">No summary yet. Click "Regenerate Summary" or upload documents to generate one.</p>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <!-- ── Expiring-soon strip ──────────────────────────────────────────── -->
  <div id="expiringStrip" class="hidden bg-amber-50 border border-amber-200 rounded-xl p-4">
    <h3 class="font-semibold text-amber-900 mb-2 flex items-center gap-2">
      ⚠️ Expiring Soon / Expired
    </h3>
    <div id="expiringList" class="space-y-1 text-sm"></div>
  </div>

  <!-- ── Documents grid ──────────────────────────────────────────────── -->
  <div id="docsContainer">
    <div class="text-center py-12 text-gray-400">
      <span class="inline-block w-6 h-6 border-2 border-gray-300 border-t-blue-500 rounded-full animate-spin"></span>
      <p class="mt-3 text-sm">Loading documents…</p>
    </div>
  </div>

<script>
const SYS_ID = <?= json_encode($traveler['sys_id']) ?>;
const TYPE_LABELS = <?= json_encode($typeLabels) ?>;

// ============================================================================
// Load documents on page load
// ============================================================================
loadDocuments();

async function loadDocuments() {
  try {
    const res = await fetch(`/api/travelers/list-documents.php?traveler_sys_id=${encodeURIComponent(SYS_ID)}&group_by=doc_type&include_pages=1`);
    const data = await res.json();

    if (!data.success) {
      document.getElementById('docsContainer').innerHTML = `
        <div class="text-center py-8 text-red-600">${escapeHtml(data.error || 'Failed to load')}</div>`;
      return;
    }

    renderExpiringSoon(data.expiring_soon || []);
    renderGroups(data.groups || {}, data.total_docs);
  } catch (err) {
    document.getElementById('docsContainer').innerHTML = `
      <div class="text-center py-8 text-red-600">Network error: ${escapeHtml(err.message)}</div>`;
  }
}

function renderExpiringSoon(items) {
  if (!items.length) return;
  const strip = document.getElementById('expiringStrip');
  const list  = document.getElementById('expiringList');
  list.innerHTML = items.map(item => {
    const label = item.is_expired
      ? `<span class="text-red-700 font-medium">Expired ${Math.abs(item.days_left)} days ago</span>`
      : `<span class="text-amber-700 font-medium">${item.days_left} days left</span>`;
    return `<div class="flex justify-between">
      <span><strong>${escapeHtml((TYPE_LABELS[item.doc_type]?.name || item.doc_type))}</strong>
            ${item.doc_number ? `(${escapeHtml(item.doc_number)})` : ''}</span>
      <span>${label} — ${escapeHtml(item.expiry_date)}</span>
    </div>`;
  }).join('');
  strip.classList.remove('hidden');
}

function renderGroups(groups, totalDocs) {
  const container = document.getElementById('docsContainer');
  if (totalDocs === 0) {
    container.innerHTML = `
      <div class="text-center py-12 text-gray-500 bg-white rounded-xl border border-gray-200">
        <p class="text-sm">No documents uploaded yet.</p>
        <a href="smart-upload.php?traveler=${encodeURIComponent(SYS_ID)}"
           class="inline-block mt-3 text-sm text-blue-600 hover:underline">Upload first document</a>
      </div>`;
    return;
  }

  container.innerHTML = Object.entries(groups).map(([docType, docs]) => {
    const label = TYPE_LABELS[docType]?.name || docType;
    const folder = TYPE_LABELS[docType]?.folder || '';
    return `
      <div class="bg-white rounded-xl border border-gray-200 mb-4 overflow-hidden">
        <div class="px-5 py-3 bg-gray-50 border-b border-gray-200">
          <h3 class="font-semibold text-gray-900">
            ${escapeHtml(label)}
            <span class="text-xs font-normal text-gray-500">
              · ${docs.length} doc${docs.length !== 1 ? 's' : ''}
              · stored in <code>${folder}</code>
            </span>
          </h3>
        </div>
        <div class="p-4 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
          ${docs.map(renderDocCard).join('')}
        </div>
      </div>`;
  }).join('');
}

function renderDocCard(doc) {
  const thumb = doc.thumbnail_url
    ? `<img src="${doc.thumbnail_url}" alt="" class="w-full h-32 object-cover bg-gray-100">`
    : `<div class="w-full h-32 bg-gray-100 flex items-center justify-center text-gray-400">📄</div>`;

  const expiryBadge = doc.is_expired
    ? `<span class="text-[10px] bg-red-100 text-red-800 px-2 py-0.5 rounded">Expired</span>`
    : doc.is_expiring_soon
    ? `<span class="text-[10px] bg-amber-100 text-amber-800 px-2 py-0.5 rounded">${doc.days_until_expiry}d left</span>`
    : '';

  const verifiedBadge = doc.verification_status === 'verified'
    ? `<span class="text-[10px] bg-emerald-100 text-emerald-800 px-2 py-0.5 rounded">✓ Verified</span>`
    : '';

  const passportBadge = doc.passport_status
    ? `<span class="text-[10px] bg-blue-100 text-blue-800 px-2 py-0.5 rounded uppercase">${doc.passport_status}</span>`
    : '';

  return `
    <div class="border border-gray-200 rounded-lg overflow-hidden hover:shadow-md transition">
      ${thumb}
      <div class="p-3">
        <div class="flex items-start justify-between gap-2 mb-1">
          <div class="font-medium text-sm text-gray-900 truncate" title="${escapeHtml(doc.stored_basename)}">
            ${escapeHtml(doc.doc_number || doc.stored_basename)}
          </div>
          <span class="text-[10px] text-gray-500">${doc.page_count}p</span>
        </div>
        <div class="flex gap-1 flex-wrap mb-2">
          ${passportBadge} ${verifiedBadge} ${expiryBadge}
        </div>
        <div class="text-xs text-gray-600 line-clamp-2" title="${escapeHtml(doc.summary || '')}">
          ${escapeHtml(doc.summary || '—')}
        </div>
        ${doc.expiry_date ? `<div class="text-xs text-gray-500 mt-1">Exp: ${escapeHtml(doc.expiry_date)}</div>` : ''}
      </div>
    </div>`;
}

// ============================================================================
// Regenerate summary button
// ============================================================================
document.getElementById('regenerateSummaryBtn').addEventListener('click', async () => {
  const btn = document.getElementById('regenerateSummaryBtn');
  const originalHTML = btn.innerHTML;
  btn.disabled = true;
  btn.innerHTML = `<span class="inline-block w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin"></span><span>Regenerating...</span>`;

  try {
    const res = await fetch('/api/travelers/regenerate-summary.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        traveler_sys_id: SYS_ID,
        trigger: 'manual',
        information_updated_for: 'Manual regeneration by agent',
      }),
    });
    const data = await res.json();

    if (!data.success) {
      alert('Regeneration failed: ' + (data.error || 'unknown'));
      return;
    }

    alert(`Summary regenerated successfully. ${data.tokens_used} tokens used.`);
    location.reload();
  } catch (err) {
    alert('Network error: ' + err.message);
  } finally {
    btn.disabled = false;
    btn.innerHTML = originalHTML;
  }
});

function escapeHtml(s) {
  return String(s ?? '').replace(/[&<>"']/g, c => ({
    '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'
  }[c]));
}
</script>