<?php
/**
 * FILE PATH: pages/st-credentials.php
 * Credentials tab — show-travelers.php এ include হয়।
 * Visa portal, airline website এর login info।
 */
?>

<div class="flex flex-col overflow-hidden" style="height: calc(100vh - 260px); min-height: 400px;">

    <div class="flex items-center justify-between mb-3 flex-shrink-0">
        <h3 class="text-sm font-semibold text-gray-700">Credentials</h3>
        <button onclick="openAddCredential()"
                class="inline-flex items-center gap-1 px-3 py-1.5 bg-blue-600 text-white text-xs rounded-lg hover:bg-blue-700">
            <i class="fas fa-plus"></i> Add
        </button>
    </div>

    <!-- Loading -->
    <div id="credLoading" class="flex items-center gap-2 text-sm text-gray-500 py-4">
        <span class="w-4 h-4 border-2 border-gray-300 border-t-blue-500 rounded-full animate-spin"></span>
        Loading...
    </div>

    <!-- List -->
    <div id="credList" class="hidden flex-1 overflow-y-auto space-y-2"></div>

    <!-- Empty -->
    <div id="credEmpty" class="hidden flex-1 flex flex-col items-center justify-center text-center text-gray-400 py-12">
        <i class="fas fa-key text-4xl mb-3"></i>
        <p class="font-medium">No credentials saved</p>
        <p class="text-sm mt-1">Add portal logins for visa, airline, embassy websites</p>
        <button onclick="openAddCredential()"
                class="mt-4 px-4 py-2 bg-blue-600 text-white text-sm rounded-lg hover:bg-blue-700">
            Add Credential
        </button>
    </div>

</div>

<!-- Add/Edit Modal -->
<div id="credModal" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-md">
        <div class="flex items-center justify-between px-5 py-4 border-b border-gray-200">
            <h3 class="font-semibold text-gray-800" id="credModalTitle">Add Credential</h3>
            <button onclick="closeCredModal()" class="text-gray-400 hover:text-gray-600">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="p-5 space-y-3">
            <input type="hidden" id="credEditIndex" value="-1">
            <div>
                <label class="text-xs font-medium text-gray-600 mb-1 block">Portal / Website Name</label>
                <input type="text" id="credPortal" placeholder="e.g. Thailand e-Visa, Saudi Embassy"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <div>
                <label class="text-xs font-medium text-gray-600 mb-1 block">URL (optional)</label>
                <input type="url" id="credUrl" placeholder="https://..."
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <div>
                <label class="text-xs font-medium text-gray-600 mb-1 block">Username / Email</label>
                <input type="text" id="credUsername" placeholder="username or email"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <div>
                <label class="text-xs font-medium text-gray-600 mb-1 block">Password</label>
                <div class="relative">
                    <input type="password" id="credPassword" placeholder="password"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 pr-10">
                    <button type="button" onclick="toggleCredPassword()"
                            class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600">
                        <i id="credPasswordEye" class="fas fa-eye text-xs"></i>
                    </button>
                </div>
            </div>
            <div>
                <label class="text-xs font-medium text-gray-600 mb-1 block">Notes (optional)</label>
                <textarea id="credNotes" rows="2" placeholder="Application ID, security questions..."
                          class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 resize-none"></textarea>
            </div>
        </div>
        <div class="flex gap-2 px-5 pb-5">
            <button onclick="saveCredential()"
                    class="flex-1 bg-blue-600 text-white py-2 rounded-lg text-sm font-medium hover:bg-blue-700">
                Save
            </button>
            <button onclick="closeCredModal()"
                    class="px-4 py-2 border border-gray-300 text-gray-600 rounded-lg text-sm hover:bg-gray-50">
                Cancel
            </button>
        </div>
    </div>
</div>

<script>
(function() {
    const TRAVELER_ID = <?= json_encode($travelerId) ?>;
    let credentials = [];

    // ── Load ──────────────────────────────────────────────────────────────────
    async function loadCredentials() {
        try {
            const res  = await fetch(`/api/travelers/credentials.php?traveler_id=${TRAVELER_ID}`, { credentials: 'include' });
            const data = await res.json();
            document.getElementById('credLoading').classList.add('hidden');

            if (!data.success) { showCredError(data.message || 'Load failed'); return; }

            credentials = data.credentials || []; // password এখানে ইতিমধ্যে decrypt করা আসে
            renderCredentials();
        } catch (err) {
            document.getElementById('credLoading').classList.add('hidden');
            showCredError(err.message);
        }
    }

    function showCredError(msg) {
        const l = document.getElementById('credLoading');
        l.innerHTML = `<span class="text-red-500 text-sm">Error: ${escHtml(msg)}</span>`;
        l.classList.remove('hidden');
    }

    function renderCredentials() {
        if (!credentials.length) {
            document.getElementById('credEmpty').classList.remove('hidden');
            return;
        }

        const list = document.getElementById('credList');
        list.classList.remove('hidden');
        list.innerHTML = credentials.map((cred, idx) => `
            <div class="bg-white border border-gray-200 rounded-xl px-4 py-3">
                <div class="flex items-start justify-between gap-2">
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2 mb-1">
                            <i class="fas fa-globe text-gray-400 text-xs"></i>
                            <span class="font-medium text-sm text-gray-800">${escHtml(cred.portal)}</span>
                        </div>
                        ${cred.url ? `<a href="${escHtml(cred.url)}" target="_blank" class="text-xs text-blue-500 hover:underline truncate block">${escHtml(cred.url)}</a>` : ''}
                        <div class="mt-2 space-y-1">
                            <div class="flex items-center gap-2">
                                <span class="text-xs text-gray-400 w-16">Username</span>
                                <span class="text-xs font-mono text-gray-700">${escHtml(cred.username)}</span>
                                <button onclick="copyCred(${idx}, 'username', this)" title="Copy username"
                                        class="text-gray-300 hover:text-gray-600">
                                    <i class="fas fa-copy text-xs"></i>
                                </button>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="text-xs text-gray-400 w-16">Password</span>
                                <span class="text-xs font-mono text-gray-700 select-all" id="credPass_${idx}">••••••••</span>
                                <button onclick="toggleShow(${idx})" title="Show / hide"
                                        class="text-gray-300 hover:text-gray-600">
                                    <i class="fas fa-eye text-xs" id="credEye_${idx}"></i>
                                </button>
                                <button onclick="copyCred(${idx}, 'password', this)" title="Copy password"
                                        class="text-gray-300 hover:text-gray-600">
                                    <i class="fas fa-copy text-xs"></i>
                                </button>
                            </div>
                            ${cred.notes ? `<p class="text-xs text-gray-400 mt-1">${escHtml(cred.notes)}</p>` : ''}
                            ${cred.updated_at ? `<p class="text-[10px] text-gray-300 mt-1">Updated ${escHtml(cred.updated_at)}</p>` : ''}
                        </div>
                    </div>
                    <div class="flex flex-col gap-1">
                        <button onclick="editCredential(${idx})" class="text-gray-300 hover:text-blue-500">
                            <i class="fas fa-pen text-xs"></i>
                        </button>
                        <button onclick="deleteCredential(${idx})" class="text-gray-300 hover:text-red-500">
                            <i class="fas fa-trash text-xs"></i>
                        </button>
                    </div>
                </div>
            </div>
        `).join('');
    }

    // ── Save ──────────────────────────────────────────────────────────────────
    async function saveCredential() {
        const portal   = document.getElementById('credPortal').value.trim();
        const url      = document.getElementById('credUrl').value.trim();
        const username = document.getElementById('credUsername').value.trim();
        const password = document.getElementById('credPassword').value.trim();
        const notes    = document.getElementById('credNotes').value.trim();
        const editIdx  = parseInt(document.getElementById('credEditIndex').value);

        if (!portal || !username) {
            alert('Portal name and username are required');
            return;
        }

        const entry = { portal, url, username, password, notes, updated_at: new Date().toLocaleString('en-GB') };

        if (editIdx >= 0) {
            credentials[editIdx] = entry;
        } else {
            credentials.push(entry);
        }

        const ok = await persistCredentials();
        if (!ok) { alert('Save failed — server did not confirm'); return; }
        closeCredModal();
        renderCredentials();
        document.getElementById('credEmpty').classList.add('hidden');
        document.getElementById('credList').classList.remove('hidden');
    }

    async function deleteCredential(idx) {
        if (!confirm('Delete this credential?')) return;
        credentials.splice(idx, 1);
        await persistCredentials();
        renderCredentials();
        if (!credentials.length) {
            document.getElementById('credList').classList.add('hidden');
            document.getElementById('credEmpty').classList.remove('hidden');
        }
    }

    async function persistCredentials() {
        try {
            const res = await fetch('/api/travelers/credentials.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                credentials: 'include',
                body: JSON.stringify({
                    traveler_id: TRAVELER_ID,
                    credentials: credentials, // password এখানে plaintext — server-side এ encrypt হবে
                }),
            });
            const d = await res.json();
            if (!d.success) console.error('[credentials] save failed:', d.message);
            return !!d.success;
        } catch (e) {
            console.error('[credentials] save error:', e);
            return false;
        }
    }

    // ── Modal ─────────────────────────────────────────────────────────────────
    window.openAddCredential = function() {
        document.getElementById('credModalTitle').textContent = 'Add Credential';
        document.getElementById('credEditIndex').value = -1;
        document.getElementById('credPortal').value   = '';
        document.getElementById('credUrl').value      = '';
        document.getElementById('credUsername').value = '';
        document.getElementById('credPassword').value = '';
        document.getElementById('credNotes').value    = '';
        document.getElementById('credModal').classList.remove('hidden');
    };

    window.editCredential = function(idx) {
        const c = credentials[idx];
        document.getElementById('credModalTitle').textContent = 'Edit Credential';
        document.getElementById('credEditIndex').value = idx;
        document.getElementById('credPortal').value   = c.portal   || '';
        document.getElementById('credUrl').value      = c.url      || '';
        document.getElementById('credUsername').value = c.username || '';
        document.getElementById('credPassword').value = c.password || '';
        document.getElementById('credNotes').value    = c.notes    || '';
        document.getElementById('credModal').classList.remove('hidden');
    };

    window.saveCredential    = saveCredential;
    window.deleteCredential  = deleteCredential;
    window.closeCredModal    = () => document.getElementById('credModal').classList.add('hidden');

    window.toggleCredPassword = function() {
        const input = document.getElementById('credPassword');
        const eye   = document.getElementById('credPasswordEye');
        if (input.type === 'password') {
            input.type = 'text';
            eye.className = 'fas fa-eye-slash text-xs';
        } else {
            input.type = 'password';
            eye.className = 'fas fa-eye text-xs';
        }
    };

    // password DOM attribute-এ না রেখে সরাসরি credentials[] থেকে পড়া হয় —
    // quote/backslash ভাঙে না, Inspect করলেও plaintext দেখা যায় না
    window.toggleShow = function(idx) {
        const el  = document.getElementById(`credPass_${idx}`);
        const eye = document.getElementById(`credEye_${idx}`);
        const hidden = el.textContent === '••••••••';
        el.textContent = hidden ? (credentials[idx]?.password || '') : '••••••••';
        eye.className  = hidden ? 'fas fa-eye-slash text-xs' : 'fas fa-eye text-xs';
    };

    window.copyCred = async function(idx, field, btn) {
        const text = credentials[idx]?.[field] || '';
        if (!text) return;
        try {
            window.focus();
            await new Promise(r => setTimeout(r, 100)); // clipboard user-gesture quirk
            await navigator.clipboard.writeText(text);
            const orig = btn.innerHTML;
            btn.innerHTML = '<i class="fas fa-check text-xs text-green-500"></i>';
            setTimeout(() => btn.innerHTML = orig, 1200);
        } catch (e) {
            alert('Copy failed: ' + e.message);
        }
    };

    function escHtml(s) {
        return String(s ?? '').replace(/[&<>"']/g, c => ({
            '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'
        }[c]));
    }

    loadCredentials();
})();
</script>