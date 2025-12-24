    <!-- Tab Header -->
    <header class="text-center mb-12">
        <div class="flex items-center justify-center mb-4">
            <div class="w-12 h-12 bg-blue-600 rounded-full flex items-center justify-center mr-3">
                <i class="fas fa-users text-white text-xl"></i>
            </div>
            <h1 class="text-3xl font-bold text-gray-800">Linked Travellers</h1>
        </div>
    </header>

    <!-- Tab Main Content Container -->
    <div id="travellerList" class="grid grid-cols-6 gap-6 h-full mb-4">
        <div class="flex items-center col-span-1 justify-center h-full">
            <div class="bg-white rounded-xl shadow-lg">
                <div class="flex justify-between items-start">
                    <div class="p-4">
                        <a href="#">
                            <div class="flex justify-between items-start">
                                <h3 class="text-lg text-gray-800"><strong>Asif M Sazid</strong></h3>
                                <i class="fa-solid fa-arrow-up-right-from-square"></i>
                            </div>

                            <p class="text-sm text-gray-600 mt-1">
                                <Strong>Passport No: </Strong> A54187481
                            </p>

                            <p class="text-sm text-gray-600 mt-1">
                                <Strong>Relation: </Strong> Siblings
                            </p>

                            <p class="text-sm text-gray-600 mt-1">
                                <Strong>Phone No: </Strong> 01751906710
                            </p>

                            <p class="text-sm text-gray-600 mt-1">
                                <Strong>Note: </Strong> Not Applicable
                            </p>

                            <p class="text-sm text-gray-600 mt-1">
                                <Strong>Travel Togather In: </Strong> Thailand, Malaysia, Singapur
                            </p>
                        </a>
                    </div>
                </div>
            </div>
        </div>
        <div class="flex items-center col-span-1 justify-center h-full">
            <div class="bg-white rounded-xl shadow-lg">
                <div class="flex justify-between items-start">
                    <div class="p-4">
                        <a href="#">
                            <div class="flex justify-between items-start">
                                <h3 class="text-lg text-gray-800"><strong>Shahanur Alam</strong></h3>
                                <i class="fa-solid fa-arrow-up-right-from-square"></i>
                            </div>

                            <p class="text-sm text-gray-600 mt-1">
                                <Strong>Passport No: </Strong> A48412978
                            </p>

                            <p class="text-sm text-gray-600 mt-1">
                                <Strong>Relation: </Strong> Siblings
                            </p>

                            <p class="text-sm text-gray-600 mt-1">
                                <Strong>Phone No: </Strong> 01684576384
                            </p>

                            <p class="text-sm text-gray-600 mt-1">
                                <Strong>Note: </Strong> Not Applicable
                            </p>

                            <p class="text-sm text-gray-600 mt-1">
                                <Strong>Travel Togather In: </Strong> Thailand, Malaysia, Singapur
                            </p>
                        </a>
                    </div>
                </div>
            </div>
        </div>
        <div class="flex items-center col-span-1 justify-center h-full" id="addMoreWrapper">
            <div class="bg-slate-800 text-white rounded-xl shadow-lg w-28 h-28 flex items-center justify-center cursor-pointer"
                id="openModalBtn">
                <div class="flex justify-between items-start">
                    <span class="text-center text-xl font-semibold">
                        Add<br>More +
                    </span>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal for Add More -->
    <div id="travellerModal"
        class="fixed inset-0 bg-black/50 hidden items-center justify-center z-50">

        <!-- Modal Box -->
        <div class="bg-white w-full max-w-lg rounded-xl shadow-xl p-6 relative">

            <!-- Close Button -->
            <button id="closeModalBtn"
                class="absolute top-3 right-3 text-gray-400 hover:text-gray-600">
                ✕
            </button>

            <h2 class="text-xl font-bold text-gray-800 mb-4">
                Add Linked Traveller
            </h2>

            <!-- Passport Search -->
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    Passport No
                </label>
                <input type="text" id="passportNo" placeholder="Enter passport number" class="w-full border rounded-lg px-3 py-2">

            </div>

            <!-- Relation -->
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    Relation
                </label>
                <select id="relation" class="w-full border rounded-lg px-3 py-2">
                    <option value="">Select relation</option>
                    <option>Sibling</option>
                    <option>Spouse</option>
                    <option>Parent</option>
                    <option>Child</option>
                    <option>Friend</option>
                </select>
            </div>

            <!-- Travel Countries -->
            <div class="mb-4 relative">
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    Travel Together In
                </label>

                <!-- Dropdown Button -->
                <button type="button"
                    id="countryDropdownBtn"
                    class="w-full border rounded-lg px-3 py-2 text-left bg-white">
                    Select countries
                </button>

                <!-- Dropdown -->
                <div id="countryDropdown"
                    class="absolute z-10 mt-1 w-full bg-white border rounded-lg shadow-lg hidden">

                    <label class="flex items-center px-3 py-2 hover:bg-gray-50 cursor-pointer">
                        <input type="checkbox" value="Thailand" class="mr-2">
                        Thailand
                    </label>

                    <label class="flex items-center px-3 py-2 hover:bg-gray-50 cursor-pointer">
                        <input type="checkbox" value="Malaysia" class="mr-2">
                        Malaysia
                    </label>

                    <label class="flex items-center px-3 py-2 hover:bg-gray-50 cursor-pointer">
                        <input type="checkbox" value="Singapore" class="mr-2">
                        Singapore
                    </label>

                    <label class="flex items-center px-3 py-2 hover:bg-gray-50 cursor-pointer">
                        <input type="checkbox" value="Indonesia" class="mr-2">
                        Indonesia
                    </label>

                </div>
            </div>


            <!-- Note -->
            <div class="mb-6">
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    Note
                </label>
                <textarea id="note" rows="3" placeholder="Write note..." class="w-full border rounded-lg px-3 py-2"></textarea>
            </div>

            <!-- Actions -->
            <div class="flex justify-end gap-3">
                <button id="cancelModal"
                    class="px-4 py-2 rounded-lg border">
                    Cancel
                </button>
                <button id="saveTravellerBtn"
                    class="px-4 py-2 rounded-lg bg-blue-600 text-white hover:bg-blue-700">
                    Save Traveller
                </button>
            </div>
        </div>
    </div>


    <script>
        /* ================= ELEMENTS ================= */
        const openBtn = document.getElementById('openModalBtn');
        const modal = document.getElementById('travellerModal');
        const closeBtn = document.getElementById('closeModalBtn');
        const cancelBtn = document.getElementById('cancelModal');
        const saveBtn = document.getElementById('saveTravellerBtn');
        const travellerList = document.getElementById('travellerList');

        const dropdownBtn = document.getElementById('countryDropdownBtn');
        const dropdown = document.getElementById('countryDropdown');

        const passportInput = document.getElementById('passportNo');
        const relationSelect = document.getElementById('relation');
        const noteInput = document.getElementById('note');

        /* ================= MODAL ================= */
        function openModal() {
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }

        function closeModal() {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }

        openBtn?.addEventListener('click', openModal);
        closeBtn?.addEventListener('click', closeModal);
        cancelBtn?.addEventListener('click', closeModal);

        modal?.addEventListener('click', (e) => {
            if (e.target === modal) closeModal();
        });

        /* ================= COUNTRY CHECKBOX SELECT ================= */
        let selectedCountries = [];

        if (dropdownBtn && dropdown) {
            const checkboxes = dropdown.querySelectorAll('input[type="checkbox"]');

            dropdownBtn.addEventListener('click', (e) => {
                e.stopPropagation();
                dropdown.classList.toggle('hidden');
            });

            checkboxes.forEach(cb => {
                cb.addEventListener('change', () => {
                    selectedCountries = Array.from(checkboxes)
                        .filter(c => c.checked)
                        .map(c => c.value);

                    dropdownBtn.innerText = selectedCountries.length ?
                        selectedCountries.join(', ') :
                        'Select countries';
                });
            });

            document.addEventListener('click', (e) => {
                if (!dropdownBtn.contains(e.target) && !dropdown.contains(e.target)) {
                    dropdown.classList.add('hidden');
                }
            });
        }

        /* ================= SAVE TRAVELLER ================= */
        saveBtn?.addEventListener('click', () => {
            const passport = passportInput.value.trim();
            const relation = relationSelect.value;
            const note = noteInput.value || 'Not Applicable';

            if (!passport || !relation) {
                alert('Passport No and Relation are required');
                return;
            }

            const cardHTML = `
            <div class="flex items-center col-span-1 justify-center h-full">
                <div class="bg-white rounded-xl shadow-lg">
                    <div class="p-4">
                        <a href="">
                            <div class="flex justify-between items-start">
                                <h3 class="text-lg text-gray-800"><strong>New Traveller</strong></h3>
                                <i class="fa-solid fa-arrow-up-right-from-square"></i>
                            </div>

                            <p class="text-sm text-gray-600 mt-1">
                                <strong>Passport No:</strong> ${passport}
                            </p>
                            <p class="text-sm text-gray-600 mt-1">
                                <strong>Relation:</strong> ${relation}
                            </p>
                            <p class="text-sm text-gray-600 mt-1">
                                <strong>Phone No:</strong> N/A
                            </p>
                            <p class="text-sm text-gray-600 mt-1">
                                <strong>Note:</strong> ${note}
                            </p>
                            <p class="text-sm text-gray-600 mt-1">
                                <strong>Travel Together In:</strong> ${selectedCountries.join(', ') || 'N/A'}
                            </p>
                        </a>
                    </div>
                </div>
            </div>
            `;

            const addMoreEl = document.getElementById('addMoreWrapper');
            travellerList.insertBefore(
                document.createRange().createContextualFragment(cardHTML),
                addMoreEl
            );

            /* Reset */
            passportInput.value = '';
            relationSelect.value = '';
            noteInput.value = '';
            selectedCountries = [];
            dropdownBtn.innerText = 'Select countries';
            dropdown.querySelectorAll('input[type="checkbox"]').forEach(c => c.checked = false);

            closeModal();
        });
    </script>