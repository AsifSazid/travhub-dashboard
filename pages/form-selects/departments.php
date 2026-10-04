<?php
// Fetch departments from DB for the dropdown
// This file is included inside pages/ context, so db_connection is relative to pages/
if (!isset($pdo)) {
    require_once __DIR__ . '/../../server/db_connection.php';
}
$_deptRows = [];
try {
    $s = $pdo->query("SELECT id, sys_id, name FROM departments WHERE is_active=1 ORDER BY sort_order ASC, name ASC");
    $_deptRows = $s->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $_e) {
    error_log('[departments.php] ' . $_e->getMessage());
}
$_deptJson = json_encode($_deptRows, JSON_UNESCAPED_UNICODE);
?>
<div>
    <label for="departmentInput" class="form-label mb-1">
        Department <span class="required-star">*</span>
    </label>
    <div id="departmentSearchContainer" class="relative w-full">
        <input
            type="text"
            id="departmentInput"
            placeholder="Search for a department..."
            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:outline-none"
            autocomplete="off">

        <ul id="departmentDropdown"
            class="absolute w-full bg-white border border-gray-300 rounded-lg mt-1 max-h-60 overflow-auto shadow-lg hidden z-50">
        </ul>
    </div>
</div>

<!-- Two hidden fields: integer id (legacy) + sys_id (new) -->
<input type="hidden" id="selectedDepartmentId"    name="department_id">
<input type="hidden" id="selectedDepartmentSysId" name="department_sys_id">

<script>
(function () {
    const departmentData = <?= $_deptJson ?>;

    const departmentInput     = document.getElementById('departmentInput');
    const departmentDropdown  = document.getElementById('departmentDropdown');
    const departmentContainer = document.getElementById('departmentSearchContainer');

    let departmentTypingTimer;
    departmentInput.addEventListener('input', () => {
        clearTimeout(departmentTypingTimer);
        departmentTypingTimer = setTimeout(() => {
            const value = departmentInput.value.toLowerCase().trim();
            const filtered = value === ''
                ? departmentData
                : departmentData.filter(d =>
                    d.name?.toLowerCase().includes(value) ||
                    d.sys_id?.toLowerCase().includes(value)
                );
            renderDepartmentDropdown(filtered);
            departmentDropdown.classList.remove('hidden');
        }, 300);
    });

    departmentInput.addEventListener('focus', () => {
        renderDepartmentDropdown(departmentData);
        departmentDropdown.classList.remove('hidden');
    });

    function renderDepartmentDropdown(list) {
        departmentDropdown.innerHTML = '';
        if (!list.length) {
            departmentDropdown.innerHTML =
                `<li class="px-4 py-3 text-center text-gray-500">No department found</li>`;
            return;
        }
        list.forEach(department => {
            const li = document.createElement('li');
            li.className = "px-4 py-3 cursor-pointer hover:bg-purple-50 border-b last:border-b-0";
            li.innerHTML = `
                <div class="flex items-center">
                    <div class="w-8 h-8 bg-purple-600 rounded-full text-white flex items-center justify-content-center font-semibold text-center leading-8">
                        ${(department.name?.charAt(0) ?? 'D').toUpperCase()}
                    </div>
                    <div class="ml-3 flex-1">
                        <div class="font-medium">${department.name}</div>
                        <div class="text-xs text-gray-400">${department.sys_id ?? ''}</div>
                    </div>
                </div>
            `;
            li.onclick = () => {
                departmentInput.value = department.name;
                document.getElementById('selectedDepartmentId').value    = department.id;
                document.getElementById('selectedDepartmentSysId').value = department.sys_id ?? '';
                departmentDropdown.classList.add('hidden');
            };
            departmentDropdown.appendChild(li);
        });
    }

    document.addEventListener('click', e => {
        if (!departmentContainer.contains(e.target)) {
            departmentDropdown.classList.add('hidden');
        }
    });
})();
</script>