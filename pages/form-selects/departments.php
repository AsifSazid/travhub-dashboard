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

<script>
    let departmentData = [
        { id: 1, name: 'Management' },
        { id: 2, name: 'Visa' },
        { id: 3, name: 'Package' },
        { id: 4, name: 'Ticket' },
        { id: 5, name: 'IT' },
        { id: 6, name: 'Student' },
        { id: 7, name: 'Medical' },
        { id: 8, name: 'Account' }
    ];
    const departmentInput = document.getElementById('departmentInput');
    const departmentDropdown = document.getElementById('departmentDropdown');
    const departmentContainer = document.getElementById('departmentSearchContainer');
    
    /* Typing */
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
    
    /* Focus */
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
            li.className =
                "px-4 py-3 cursor-pointer hover:bg-purple-50 border-b last:border-b-0";
    
            li.innerHTML = `
                <div class="flex items-center">
                    <div class="w-8 h-8 bg-purple-600 rounded-full text-white flex items-center justify-center font-semibold">
                        ${department.name?.charAt(0).toUpperCase() ?? 'D'}
                    </div>
                    <div class="ml-3 flex-1">
                        <div class="font-medium">${department.name}</div>
                    </div>
                </div>
            `;
    
            li.onclick = () => {
                departmentInput.value = department.name;
                document.getElementById('selectedDepartmentId').value = department.id;
                departmentDropdown.classList.add('hidden');
            };
    
            departmentDropdown.appendChild(li);
        });
    }
    
    /* Outside click */
    document.addEventListener('click', e => {
        if (!departmentContainer.contains(e.target)) {
            departmentDropdown.classList.add('hidden');
        }
    });
</script>