<div>
    <label for="bloodGroupInput" class="form-label mb-1">
        Blood Group <span class="required-star">*</span>
    </label>
    <div id="bloodGroupSearchContainer" class="relative w-full">
        <input
            type="text"
            id="bloodGroupInput"
            placeholder="Search for a blood group..."
            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:outline-none"
            autocomplete="off">
    
        <ul id="bloodGroupDropdown"
            class="absolute w-full bg-white border border-gray-300 rounded-lg mt-1 max-h-60 overflow-auto shadow-lg hidden z-50">
        </ul>
    </div>
</div>

<script>
    let bloodGroupData = [
        { id: 'a+', name: 'a+' },
        { id: 'a-', name: 'a-' },
        { id: 'ab+', name: 'ab+' },
        { id: 'ab-', name: 'ab-' },
        { id: 'b+', name: 'b+' },
        { id: 'b-', name: 'b-' },
        { id: 'o+', name: 'o+' },
        { id: 'o-', name: 'o-' }
    ];
    const bloodGroupInput = document.getElementById('bloodGroupInput');
    const bloodGroupDropdown = document.getElementById('bloodGroupDropdown');
    const bloodGroupContainer = document.getElementById('bloodGroupSearchContainer');
    
    /* Typing */
    let bloodGroupTypingTimer;
    bloodGroupInput.addEventListener('input', () => {
        clearTimeout(bloodGroupTypingTimer);
        bloodGroupTypingTimer = setTimeout(() => {
            const value = bloodGroupInput.value.toLowerCase().trim();
    
            const filtered = value === ''
                ? bloodGroupData
                : bloodGroupData.filter(d =>
                    d.name?.toLowerCase().includes(value) ||
                    d.sys_id?.toLowerCase().includes(value)
                );
    
            renderBloodGroupDropdown(filtered);
            bloodGroupDropdown.classList.remove('hidden');
        }, 300);
    });
    
    /* Focus */
    bloodGroupInput.addEventListener('focus', () => {
        renderBloodGroupDropdown(bloodGroupData);
        bloodGroupDropdown.classList.remove('hidden');
    });
    
    function renderBloodGroupDropdown(list) {
        bloodGroupDropdown.innerHTML = '';
    
        if (!list.length) {
            bloodGroupDropdown.innerHTML =
                `<li class="px-4 py-3 text-center text-gray-500">No blood group found</li>`;
            return;
        }
    
        list.forEach(bloodGroup => {
            const li = document.createElement('li');
            li.className =
                "px-4 py-3 cursor-pointer hover:bg-purple-50 border-b last:border-b-0";
    
            li.innerHTML = `
                <div class="flex items-center">
                    <div class="w-8 h-8 bg-purple-600 rounded-full text-white flex items-center justify-center font-semibold uppercase">
                        ${bloodGroup.name.charAt(0)}
                    </div>
                    <div class="ml-3 flex-1">
                        <div class="font-medium uppercase">${bloodGroup.name}</div>
                    </div>
                </div>
            `;
    
            li.onclick = () => {
                bloodGroupInput.value = bloodGroup.name;
                document.getElementById('selectedBloodGroupValue').value = bloodGroup.id;
                bloodGroupDropdown.classList.add('hidden');
            };
    
            bloodGroupDropdown.appendChild(li);
        });
    }
    
    /* Outside click */
    document.addEventListener('click', e => {
        if (!bloodGroupContainer.contains(e.target)) {
            bloodGroupDropdown.classList.add('hidden');
        }
    });
</script>