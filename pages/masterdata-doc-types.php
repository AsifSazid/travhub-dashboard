<?php
// FILE PATH: /pages/masterdata-doc-types.php
include_once('./authenticate.php');
$ip_port    = @file_get_contents('../ippath.txt') ?: 'http://103.104.219.3:898';
$docTypeApi = $ip_port . 'api/masterdata/doc-types/endpoints.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Doc Type Registry — TravHub</title>
<link rel="icon" type="image/png" href="../assets/images/logo/round-logo.png">
<script src="https://cdn.tailwindcss.com"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="../assets/css/style.css">
<style>
:root{--accent:#4F46E5;--border:#E2E8F0;--surface:#F8FAFC;--text:#0F172A;--muted:#64748B;}
body{background:var(--surface);}
.dt-row:hover{background:#F8FAFC;}
.badge-active{background:#DCFCE7;color:#15803D;}
.badge-inactive{background:#F1F5F9;color:#64748B;}
</style>
</head>
<body>
<?php include '../elements/header.php'; ?>
<?php include '../elements/aside.php'; ?>

<main id="mainContent" class="transition-all duration-300 pl-64 mt-16" style="padding-top:64px;">
<div style="padding:28px 32px;max-width:1100px;">

    <!-- Header -->
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:24px;">
        <div>
            <h1 style="font-size:1.35rem;font-weight:800;color:var(--text);">Doc Type Registry</h1>
            <p style="font-size:.85rem;color:var(--muted);margin-top:2px;">
                Manage document types used across Smart Upload, Visa Checklist, and Traveler profiles.
            </p>
        </div>
        <button onclick="_dtOpenModal()"
                style="padding:9px 20px;background:#4F46E5;color:#fff;font-weight:700;
                       font-size:.875rem;border:none;border-radius:9px;cursor:pointer;">
            + Add Doc Type
        </button>
    </div>

    <!-- Table -->
    <div style="background:#fff;border:1px solid var(--border);border-radius:14px;overflow:hidden;">
        <table style="width:100%;border-collapse:collapse;font-size:.875rem;">
            <thead>
                <tr style="background:#F8FAFC;border-bottom:1px solid var(--border);">
                    <th style="padding:11px 16px;text-align:left;font-size:.72rem;font-weight:700;color:var(--muted);text-transform:uppercase;letter-spacing:.05em;">doc_type key</th>
                    <th style="padding:11px 16px;text-align:left;font-size:.72rem;font-weight:700;color:var(--muted);text-transform:uppercase;letter-spacing:.05em;">Display Name</th>
                    <th style="padding:11px 16px;text-align:left;font-size:.72rem;font-weight:700;color:var(--muted);text-transform:uppercase;letter-spacing:.05em;">SMB Folder</th>
                    <th style="padding:11px 16px;text-align:center;font-size:.72rem;font-weight:700;color:var(--muted);text-transform:uppercase;letter-spacing:.05em;">Tracks Expiry</th>
                    <th style="padding:11px 16px;text-align:center;font-size:.72rem;font-weight:700;color:var(--muted);text-transform:uppercase;letter-spacing:.05em;">Order</th>
                    <th style="padding:11px 16px;text-align:center;font-size:.72rem;font-weight:700;color:var(--muted);text-transform:uppercase;letter-spacing:.05em;">Status</th>
                    <th style="padding:11px 16px;text-align:right;font-size:.72rem;font-weight:700;color:var(--muted);text-transform:uppercase;letter-spacing:.05em;">Actions</th>
                </tr>
            </thead>
            <tbody id="dt-table-body">
                <tr><td colspan="7" style="padding:32px;text-align:center;color:var(--muted);">Loading…</td></tr>
            </tbody>
        </table>
    </div>

</div>
</main>

<!-- Modal -->
<div id="dt-modal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.45);z-index:300;
     display:none;align-items:center;justify-content:center;padding:20px;">
    <div style="background:#fff;border-radius:16px;width:100%;max-width:520px;
                box-shadow:0 20px 60px rgba(0,0,0,.2);overflow:hidden;">
        <div style="padding:18px 22px;border-bottom:1px solid var(--border);display:flex;justify-content:space-between;align-items:center;">
            <div id="dt-modal-title" style="font-weight:700;font-size:1rem;color:var(--text);">Add Doc Type</div>
            <button onclick="_dtCloseModal()" style="background:none;border:none;cursor:pointer;font-size:1.1rem;color:var(--muted);">✕</button>
        </div>
        <div style="padding:20px 22px;">
            <input type="hidden" id="dt-edit-id">

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-bottom:14px;">
                <div>
                    <label style="font-size:.75rem;font-weight:600;color:var(--muted);display:block;margin-bottom:5px;">doc_type key *</label>
                    <input id="dt-doc-type" placeholder="e.g. bank_solvency"
                           style="width:100%;padding:8px 11px;border:1.5px solid var(--border);border-radius:8px;
                                  font-size:.875rem;outline:none;font-family:monospace;box-sizing:border-box;" />
                    <div style="font-size:.7rem;color:var(--muted);margin-top:3px;">lowercase, underscores only</div>
                </div>
                <div>
                    <label style="font-size:.75rem;font-weight:600;color:var(--muted);display:block;margin-bottom:5px;">Display Name *</label>
                    <input id="dt-display-name" placeholder="e.g. Bank Solvency"
                           style="width:100%;padding:8px 11px;border:1.5px solid var(--border);border-radius:8px;
                                  font-size:.875rem;outline:none;box-sizing:border-box;" />
                </div>
            </div>

            <div style="margin-bottom:14px;">
                <label style="font-size:.75rem;font-weight:600;color:var(--muted);display:block;margin-bottom:5px;">SMB Folder *</label>
                <select id="dt-smb-folder"
                        style="width:100%;padding:8px 11px;border:1.5px solid var(--border);border-radius:8px;
                               font-size:.875rem;outline:none;background:#fff;">
                    <option value="">Select folder…</option>
                    <option value="passport_identity">passport_identity</option>
                    <option value="nid">nid</option>
                    <option value="countries_documents">countries_documents</option>
                    <option value="travel_documents">travel_documents</option>
                    <option value="financial_documents">financial_documents</option>
                    <option value="professional_documents">professional_documents</option>
                    <option value="personal_documents">personal_documents</option>
                    <option value="photos_signature">photos_signature</option>
                    <option value="travel_history">travel_history</option>
                    <option value="all_documents">all_documents</option>
                </select>
            </div>

            <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:14px;margin-bottom:18px;">
                <div>
                    <label style="font-size:.75rem;font-weight:600;color:var(--muted);display:block;margin-bottom:5px;">Display Order</label>
                    <input id="dt-display-order" type="number" value="99" min="1"
                           style="width:100%;padding:8px 11px;border:1.5px solid var(--border);border-radius:8px;
                                  font-size:.875rem;outline:none;box-sizing:border-box;" />
                </div>
                <div style="display:flex;flex-direction:column;justify-content:flex-end;padding-bottom:2px;">
                    <label style="display:flex;align-items:center;gap:8px;cursor:pointer;font-size:.85rem;font-weight:600;color:var(--text);">
                        <input id="dt-tracks-expiry" type="checkbox"
                               style="width:16px;height:16px;accent-color:#4F46E5;cursor:pointer;" />
                        Tracks Expiry
                    </label>
                </div>
                <div style="display:flex;flex-direction:column;justify-content:flex-end;padding-bottom:2px;">
                    <label style="display:flex;align-items:center;gap:8px;cursor:pointer;font-size:.85rem;font-weight:600;color:var(--text);">
                        <input id="dt-is-active" type="checkbox" checked
                               style="width:16px;height:16px;accent-color:#4F46E5;cursor:pointer;" />
                        Active
                    </label>
                </div>
            </div>

            <div style="display:flex;gap:10px;justify-content:flex-end;">
                <button onclick="_dtCloseModal()"
                        style="padding:8px 18px;border:1.5px solid var(--border);border-radius:8px;
                               background:#fff;font-size:.875rem;cursor:pointer;color:var(--muted);font-weight:600;">
                    Cancel
                </button>
                <button onclick="_dtSave()"
                        style="padding:8px 22px;background:#4F46E5;color:#fff;font-weight:700;
                               font-size:.875rem;border:none;border-radius:8px;cursor:pointer;">
                    Save
                </button>
            </div>
        </div>
    </div>
</div>

<script>
const API = '<?php echo $docTypeApi; ?>';

let _dtRows = [];

/* ── Load ───────────────────────────────────────────────── */
async function _dtLoad() {
    const j = await fetch(`${API}?action=list`).then(r => r.json());
    _dtRows = j.data ?? [];
    _dtRender();
}

function _dtRender() {
    const tbody = document.getElementById('dt-table-body');
    if (!_dtRows.length) {
        tbody.innerHTML = `<tr><td colspan="7" style="padding:32px;text-align:center;color:#94A3B8;">No doc types found.</td></tr>`;
        return;
    }
    tbody.innerHTML = _dtRows.map(r => `
        <tr class="dt-row" style="border-bottom:1px solid #F1F5F9;">
            <td style="padding:12px 16px;">
                <code style="background:#EEF2FF;color:#4F46E5;padding:3px 8px;border-radius:5px;font-size:.8rem;">
                    ${_esc(r.doc_type)}
                </code>
            </td>
            <td style="padding:12px 16px;font-weight:600;color:#0F172A;">${_esc(r.display_name)}</td>
            <td style="padding:12px 16px;color:#64748B;font-size:.82rem;">${_esc(r.smb_folder ?? '—')}</td>
            <td style="padding:12px 16px;text-align:center;">
                ${r.tracks_expiry == 1
                    ? '<span style="color:#15803D;font-weight:700;">✓</span>'
                    : '<span style="color:#CBD5E1;">—</span>'}
            </td>
            <td style="padding:12px 16px;text-align:center;color:#64748B;font-size:.82rem;">${r.display_order ?? '—'}</td>
            <td style="padding:12px 16px;text-align:center;">
                <span class="${r.is_active == 1 ? 'badge-active' : 'badge-inactive'}"
                      style="padding:3px 10px;border-radius:999px;font-size:.72rem;font-weight:700;">
                    ${r.is_active == 1 ? 'Active' : 'Inactive'}
                </span>
            </td>
            <td style="padding:12px 16px;text-align:right;">
                <div style="display:flex;gap:6px;justify-content:flex-end;">
                    <button onclick="_dtOpenModal(${JSON.stringify(r).replace(/"/g,'&quot;')})"
                            style="padding:4px 10px;border-radius:6px;border:1px solid var(--border);
                                   background:#fff;font-size:.75rem;cursor:pointer;color:#475569;font-weight:600;">
                        Edit
                    </button>
                    <button onclick="_dtToggle('${_esc(r.doc_type)}', ${r.is_active == 1 ? 0 : 1})"
                            style="padding:4px 10px;border-radius:6px;border:1px solid var(--border);
                                   background:#fff;font-size:.75rem;cursor:pointer;
                                   color:${r.is_active == 1 ? '#64748B' : '#15803D'};font-weight:600;">
                        ${r.is_active == 1 ? 'Disable' : 'Enable'}
                    </button>
                    <button onclick="_dtDelete('${_esc(r.doc_type)}', '${_esc(r.display_name)}')"
                            style="padding:4px 10px;border-radius:6px;border:1px solid #FCA5A5;
                                   background:#FFF1F2;font-size:.75rem;cursor:pointer;color:#EF4444;font-weight:600;">
                        Delete
                    </button>
                </div>
            </td>
        </tr>`).join('');
}

/* ── Modal ──────────────────────────────────────────────── */
function _dtOpenModal(row) {
    document.getElementById('dt-modal').style.display = 'flex';
    document.getElementById('dt-modal-title').textContent = row ? 'Edit Doc Type' : 'Add Doc Type';

    document.getElementById('dt-edit-id').value       = row?.doc_type ?? '';
    document.getElementById('dt-doc-type').value      = row?.doc_type ?? '';
    document.getElementById('dt-doc-type').readOnly   = !!row; // can't change key on edit
    document.getElementById('dt-doc-type').style.background = row ? '#F8FAFC' : '#fff';
    document.getElementById('dt-display-name').value  = row?.display_name ?? '';
    document.getElementById('dt-smb-folder').value    = row?.smb_folder ?? '';
    document.getElementById('dt-display-order').value = row?.display_order ?? 99;
    document.getElementById('dt-tracks-expiry').checked = row?.tracks_expiry == 1;
    document.getElementById('dt-is-active').checked    = row ? row.is_active == 1 : true;

    setTimeout(() => document.getElementById('dt-display-name').focus(), 100);
}

function _dtCloseModal() {
    document.getElementById('dt-modal').style.display = 'none';
}

/* ── Save ───────────────────────────────────────────────── */
async function _dtSave() {
    const docType    = document.getElementById('dt-doc-type').value.trim().toLowerCase().replace(/\s+/g,'_');
    const displayName= document.getElementById('dt-display-name').value.trim();
    const smbFolder  = document.getElementById('dt-smb-folder').value;
    const order      = parseInt(document.getElementById('dt-display-order').value) || 99;
    const tracksExp  = document.getElementById('dt-tracks-expiry').checked ? 1 : 0;
    const isActive   = document.getElementById('dt-is-active').checked ? 1 : 0;
    const editId     = document.getElementById('dt-edit-id').value;

    if (!docType)     return alert('doc_type key is required');
    if (!displayName) return alert('Display name is required');
    if (!smbFolder)   return alert('SMB folder is required');

    const j = await fetch(API, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            action:        editId ? 'update' : 'create',
            doc_type:      docType,
            display_name:  displayName,
            smb_folder:    smbFolder,
            display_order: order,
            tracks_expiry: tracksExp,
            is_active:     isActive,
        }),
    }).then(r => r.json());

    if (j.status !== 'success') return alert(j.message ?? 'Save failed');
    _dtCloseModal();
    _dtLoad();
}

/* ── Toggle ─────────────────────────────────────────────── */
async function _dtToggle(docType, newState) {
    const j = await fetch(API, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'toggle', doc_type: docType, is_active: newState }),
    }).then(r => r.json());
    if (j.status !== 'success') return alert(j.message);
    _dtLoad();
}

/* ── Delete ─────────────────────────────────────────────── */
async function _dtDelete(docType, name) {
    if (!confirm(`Delete "${name}"? This cannot be undone.`)) return;
    const j = await fetch(API, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'delete', doc_type: docType }),
    }).then(r => r.json());
    if (j.status !== 'success') return alert(j.message);
    _dtLoad();
}

function _esc(s) {
    return String(s ?? '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

// Close modal on backdrop click
document.getElementById('dt-modal').addEventListener('click', function(e) {
    if (e.target === this) _dtCloseModal();
});

_dtLoad();
</script>
</body>
</html>