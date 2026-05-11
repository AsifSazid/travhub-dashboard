<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes">
    <title>Traveler Grid | Complete Work Entry</title>
    <link rel="icon" type="image/png" href="../assets/images/logo/round-logo.png" sizes="16x16">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .grid-card {
            transition: all 0.2s ease;
        }
        .grid-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 20px 25px -12px rgba(0, 0, 0, 0.12), 0 4px 8px -4px rgba(0, 0, 0, 0.05);
            border-color: #cbd5e1;
        }
        .preview-modal {
            display: none;
            position: fixed;
            top: 0; left: 0;
            width: 100%; height: 100%;
            background: rgba(0,0,0,0.65);
            z-index: 1000;
            align-items: center;
            justify-content: center;
            backdrop-filter: blur(5px);
        }
        .preview-content {
            background: white;
            max-width: 800px;
            width: 90%;
            max-height: 85vh;
            border-radius: 1.5rem;
            overflow-y: auto;
            padding: 1.6rem;
            box-shadow: 0 25px 40px -12px black;
        }
        .traveler-5cols {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 1.5rem;
        }
        @media (min-width: 1400px) {
            .traveler-5cols {
                grid-template-columns: repeat(5, minmax(280px, 1fr));
            }
        }
        @media (min-width: 1024px) and (max-width: 1399px) {
            .traveler-5cols {
                grid-template-columns: repeat(4, minmax(280px, 1fr));
            }
        }
        /* Tooltip style for full name and sys_id */
        .truncate-text {
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .has-tooltip {
            position: relative;
            cursor: help;
        }
        .has-tooltip:hover::after {
            content: attr(data-tooltip);
            position: absolute;
            bottom: 100%;
            left: 0;
            background: #1f2937;
            color: white;
            font-size: 0.7rem;
            padding: 4px 8px;
            border-radius: 6px;
            white-space: nowrap;
            z-index: 50;
            box-shadow: 0 2px 8px rgba(0,0,0,0.2);
            pointer-events: none;
        }
        .sys-id-wrapper {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            background: #eef2ff;
            padding: 2px 8px;
            border-radius: 20px;
            font-size: 0.7rem;
            font-family: monospace;
            cursor: pointer;
        }
        .sys-id-wrapper:hover {
            background: #dbeafe;
        }
        .name-row {
            word-break: break-word;
            white-space: normal;
            line-height: 1.3;
        }
    </style>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: {
                            50: '#eff6ff', 100: '#dbeafe', 500: '#3b82f6', 600: '#2563eb', 700: '#1d4ed8',
                        }
                    }
                }
            }
        }
    </script>
</head>
<body class="bg-gray-50 font-sans antialiased">

    <?php include_once('./authenticate.php'); ?>
    <?php 
        $ip_port = @file_get_contents('../ippath.txt');
        if (empty($ip_port)) {
            $ip_port = "http://103.104.219.3:898";
        }
        $storeAllTravelerApi = $ip_port . "api/travelers/all-travelers.php";
    ?>
    <?php include '../elements/header.php'; ?>
    <?php include '../elements/aside.php'; ?>

    <!-- Preview Modal -->
    <div id="previewModal" class="preview-modal">
        <div class="preview-content">
            <div class="flex justify-between items-center border-b pb-3 mb-4">
                <h3 class="text-xl font-bold text-gray-800 flex items-center gap-2" id="previewTitle">
                    <i class="fas fa-address-card text-primary-500"></i> Full Details
                </h3>
                <button onclick="closePreview()" class="text-gray-500 hover:text-gray-800 text-2xl leading-5">&times;</button>
            </div>
            <div id="modalPreviewContent" class="text-gray-700 space-y-3 text-sm"></div>
        </div>
    </div>

    <!-- Main Content -->
    <main id="mainContent" class="pt-16 pl-64 transition-all duration-300">
        <div class="p-6">
            <!-- Header -->
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">
                <div>
                    <h2 class="text-2xl font-bold text-gray-800 flex items-center gap-2">
                        <i class="fas fa-id-card text-primary-500"></i> Traveler Directory
                    </h2>
                    <p class="text-sm text-gray-500 mt-1">Grid view | sys_id (TR-**** format) | Full address formatted</p>
                </div>
                <a href="create-traveler.php" class="inline-flex items-center gap-2 px-5 py-2.5 bg-primary-600 hover:bg-primary-700 text-white rounded-xl shadow-md hover:shadow-lg transition font-medium">
                    <i class="fas fa-plus-circle"></i> Add New Traveler
                </a>
            </div>

            <!-- Filters -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 mb-8">
                <div class="flex flex-col md:flex-row gap-4 items-end flex-wrap">
                    <div class="flex-1 min-w-[180px]">
                        <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1"><i class="fas fa-search"></i> Search</label>
                        <input type="text" id="searchInput" placeholder="Name, Email, Phone, Passport, sys_id..." class="w-full px-4 py-2.5 border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary-500 outline-none bg-gray-50">
                    </div>
                    <div class="w-full md:w-56">
                        <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1"><i class="fas fa-user-check"></i> Created By</label>
                        <select id="filterCreatedBy" class="w-full px-4 py-2.5 border border-gray-200 rounded-xl bg-gray-50">
                            <option value="">All creators</option>
                        </select>
                    </div>
                    <div class="w-full md:w-56">
                        <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1"><i class="fas fa-user-edit"></i> Updated By</label>
                        <select id="filterUpdatedBy" class="w-full px-4 py-2.5 border border-gray-200 rounded-xl bg-gray-50">
                            <option value="">All updaters</option>
                        </select>
                    </div>
                    <button id="resetFiltersBtn" class="px-5 py-2.5 bg-gray-200 hover:bg-gray-300 text-gray-800 rounded-xl font-medium transition flex items-center gap-2">
                        <i class="fas fa-undo-alt"></i> Reset
                    </button>
                </div>
                <div class="flex justify-between items-center mt-4 text-xs text-gray-400 border-t pt-3">
                    <div><i class="fas fa-th-large mr-1"></i> 5-Column Responsive | sys_id (first 4 chars hidden) | Full address formatted</div>
                    <div id="resultCount" class="font-medium text-gray-600">0 travelers</div>
                </div>
            </div>

            <!-- Grid Container -->
            <div id="travelerGridContainer" class="traveler-5cols">
                <div class="col-span-full text-center py-12 text-gray-400 bg-white rounded-xl"><i class="fas fa-spinner fa-spin mr-2"></i> Loading travelers...</div>
            </div>
        </div>
    </main>

    <?php include '../elements/floating-menus.php'; ?>
    <script src="../assets/js/script.js?time=<?php echo time(); ?>"></script>
    
    <script>
        const API_URL_FOR_ALL_TRAVELERS = "<?php echo $storeAllTravelerApi; ?>";
        let masterTravelers = [];
        let filteredTravelers = [];

        const gridContainer = document.getElementById('travelerGridContainer');
        const searchInput = document.getElementById('searchInput');
        const filterCreatedBy = document.getElementById('filterCreatedBy');
        const filterUpdatedBy = document.getElementById('filterUpdatedBy');
        const resetBtn = document.getElementById('resetFiltersBtn');
        const resultCountSpan = document.getElementById('resultCount');

        // Helper: format sys_id -> hide first 4 chars, show from TR-26 style
        // Example: "TR-26-12345" becomes "****-12345" or if starts with TR-26 then show "TR-26..."? 
        // Requirement: "first 4 character will be hide, then I can view the whole sys_id"
        // So we mask first 4 chars with **** but on click/tooltip show full
        function formatSysId(sysId) {
            if (!sysId || sysId === 'N/A') return 'N/A';
            const str = String(sysId);
            if (str.length <= 4) return '****';
            const hiddenPart = '****';
            const visiblePart = str.substring(4);
            return hiddenPart + visiblePart;
        }
        
        // Get full sys_id for tooltip
        function getFullSysId(sysId) {
            return sysId || 'N/A';
        }

        // Parse phone
        function getPrimaryPhone(phoneField) {
            if (!phoneField) return '—';
            try {
                let obj = typeof phoneField === 'string' ? JSON.parse(phoneField) : phoneField;
                return obj?.primary_no || obj?.primary || obj?.phone || '—';
            } catch(e) { return String(phoneField); }
        }

        function getPrimaryEmail(emailField) {
            if (!emailField) return '—';
            try {
                let obj = typeof emailField === 'string' ? JSON.parse(emailField) : emailField;
                return obj?.primary || obj?.email_address || obj?.email || '—';
            } catch(e) { return String(emailField); }
        }

        function getPassportNumber(passportData) {
            if (!passportData) return '—';
            if (typeof passportData === 'string') {
                try {
                    let parsed = JSON.parse(passportData);
                    return parsed?.passport_no || parsed?.number || parsed?.passportNumber || '—';
                } catch(e) {
                    return passportData.length > 30 ? passportData.substring(0,27)+'…' : passportData;
                }
            }
            return passportData?.passport_no || passportData?.number || '—';
        }

        // ========== FORMAT ADDRESS PROPERLY ==========
        // Input JSON: {"address_line_1":"HOUSE 05, ROAD 14","address_line_2":"GULSHAN","city":"Dhaka","state":"Dhaka","zip_code":"1212","country":"Bangladesh"}
        // Output: "House 05, Road 14, Gulshan, Dhaka-1212, Dhaka, Bangladesh"
        function formatAddressFull(addressData) {
            if (!addressData) return 'Not provided';
            
            let addrObj = null;
            if (typeof addressData === 'string') {
                try {
                    addrObj = JSON.parse(addressData);
                } catch(e) {
                    return addressData;
                }
            } else if (typeof addressData === 'object') {
                addrObj = addressData;
            }
            
            if (!addrObj || typeof addrObj !== 'object') return String(addressData);
            
            // Build components based on common fields (case-insensitive handling)
            const line1 = addrObj.address_line_1 || addrObj.addressLine1 || addrObj.street || addrObj.line1 || '';
            const line2 = addrObj.address_line_2 || addrObj.addressLine2 || addrObj.area || addrObj.line2 || '';
            const city = addrObj.city || addrObj.town || addrObj.district || '';
            const state = addrObj.state || addrObj.province || addrObj.region || '';
            const zip = addrObj.zip_code || addrObj.zip || addrObj.postal_code || addrObj.pincode || '';
            const country = addrObj.country || addrObj.nation || '';
            
            // Format line1 with proper case (optional: capitalize first letter of each word but preserve)
            const formattedLine1 = line1 ? line1.replace(/\b\w/g, l => l.toUpperCase()) : '';
            const formattedLine2 = line2 ? line2.replace(/\b\w/g, l => l.toUpperCase()) : '';
            
            const addressParts = [];
            if (formattedLine1) addressParts.push(formattedLine1);
            if (formattedLine2) addressParts.push(formattedLine2);
            
            // City with zip: "Dhaka-1212"
            let cityWithZip = city;
            if (city && zip) {
                cityWithZip = `${city}-${zip}`;
            } else if (zip && !city) {
                cityWithZip = zip;
            } else if (city && !zip) {
                cityWithZip = city;
            }
            if (cityWithZip) addressParts.push(cityWithZip);
            
            if (state && state !== city) addressParts.push(state);
            if (country) addressParts.push(country);
            
            if (addressParts.length > 0) {
                return addressParts.join(', ');
            }
            
            // fallback: try full_address field
            if (addrObj.full_address) return addrObj.full_address;
            
            // last resort: stringify
            try {
                return JSON.stringify(addrObj);
            } catch(e) {
                return String(addressData);
            }
        }

        // Fetch travelers
        async function fetchTravelers() {
            try {
                const res = await fetch(API_URL_FOR_ALL_TRAVELERS);
                const json = await res.json();
                let travelers = json.travelers || json.data || [];
                if (!Array.isArray(travelers)) travelers = [];
                
                masterTravelers = travelers.map(t => {
                    const rawAddress = t.address || t.full_address || t.residential_address || t.permanent_address || t.addresses;
                    const formattedAddress = formatAddressFull(rawAddress);
                    
                    return {
                        ...t,
                        fullName: t.name || t.full_name || 'Unnamed Traveler',
                        primaryPhone: getPrimaryPhone(t.phone),
                        primaryEmail: getPrimaryEmail(t.email),
                        passportNo: getPassportNumber(t.passport_number || t.passport_no || t.passport),
                        createdBy: t.created_by || t.createdBy || t.creator_name || 'System',
                        updatedBy: t.updated_by || t.updatedBy || t.last_modified_by || '—',
                        sys_id: t.sys_id || t.id || t.traveler_id || t.uid || 'N/A',
                        addressFull: formattedAddress,
                        rawAddressObj: rawAddress,
                        travelerId: t.id || t.sys_id
                    };
                });
                populateFilterDropdowns();
                applyFiltersAndRender();
            } catch(err) {
                console.error("API fetch error:", err);
                gridContainer.innerHTML = `<div class="col-span-full text-center py-12 text-red-500 bg-white rounded-xl shadow-sm"><i class="fas fa-exclamation-triangle mr-2"></i>Failed to load. Check network or API endpoint.</div>`;
            }
        }

        function populateFilterDropdowns() {
            const createdBySet = new Set();
            const updatedBySet = new Set();
            masterTravelers.forEach(t => {
                if (t.createdBy && t.createdBy.trim() !== '') createdBySet.add(t.createdBy);
                if (t.updatedBy && t.updatedBy.trim() !== '' && t.updatedBy !== '—') updatedBySet.add(t.updatedBy);
            });
            filterCreatedBy.innerHTML = '<option value="">All creators</option>';
            Array.from(createdBySet).sort().forEach(creator => {
                filterCreatedBy.innerHTML += `<option value="${escapeHtml(creator)}">${escapeHtml(creator)}</option>`;
            });
            filterUpdatedBy.innerHTML = '<option value="">All updaters</option>';
            Array.from(updatedBySet).sort().forEach(updater => {
                filterUpdatedBy.innerHTML += `<option value="${escapeHtml(updater)}">${escapeHtml(updater)}</option>`;
            });
        }

        function applyFiltersAndRender() {
            const searchTerm = searchInput.value.trim().toLowerCase();
            const createdByVal = filterCreatedBy.value;
            const updatedByVal = filterUpdatedBy.value;

            filteredTravelers = masterTravelers.filter(traveler => {
                let matchSearch = true;
                if (searchTerm) {
                    matchSearch = 
                        (traveler.fullName?.toLowerCase().includes(searchTerm)) ||
                        (traveler.primaryEmail?.toLowerCase().includes(searchTerm)) ||
                        (traveler.primaryPhone?.toLowerCase().includes(searchTerm)) ||
                        (traveler.passportNo?.toLowerCase().includes(searchTerm)) ||
                        (String(traveler.sys_id).toLowerCase().includes(searchTerm)) ||
                        (traveler.addressFull?.toLowerCase().includes(searchTerm));
                }
                let matchCreated = createdByVal ? traveler.createdBy === createdByVal : true;
                let matchUpdated = updatedByVal ? traveler.updatedBy === updatedByVal : true;
                return matchSearch && matchCreated && matchUpdated;
            });
            renderGridView();
        }

        function renderGridView() {
            if (!gridContainer) return;
            if (filteredTravelers.length === 0) {
                gridContainer.innerHTML = `<div class="col-span-full flex flex-col items-center justify-center py-16 bg-white rounded-2xl shadow-sm border border-dashed border-gray-300">
                    <i class="fas fa-user-slash text-5xl text-gray-300 mb-3"></i>
                    <p class="text-gray-500 font-medium">No travelers match the filters</p>
                    <button onclick="document.getElementById('resetFiltersBtn').click();" class="mt-3 text-primary-600 text-sm underline">Clear filters</button>
                </div>`;
                resultCountSpan.innerText = `0 travelers`;
                return;
            }

            resultCountSpan.innerText = `${filteredTravelers.length} traveler${filteredTravelers.length !== 1 ? 's' : ''}`;
            
            let cardsHtml = '';
            filteredTravelers.forEach((traveler, idx) => {
                const detailLink = `show-travelers.php?traveler_id=${encodeURIComponent(traveler.sys_id)}`;
                const maskedSysId = formatSysId(traveler.sys_id);
                const fullSysId = getFullSysId(traveler.sys_id);
                const shortAddress = traveler.addressFull.length > 100 ? traveler.addressFull.substring(0, 97) + '...' : traveler.addressFull;
                
                cardsHtml += `
                    <div class="bg-white rounded-2xl border border-gray-200 shadow-sm hover:shadow-lg transition-all duration-200 overflow-hidden flex flex-col h-full">
                        <div class="p-4 flex-1">
                            <!-- sys_id row (masked with click to view full) -->
                            <div class="flex justify-end mb-2">
                                <div class="sys-id-wrapper has-tooltip" data-tooltip="Full ID: ${escapeHtml(fullSysId)}" onclick="showFullSysIdModal('${escapeHtml(traveler.fullName)}', '${escapeHtml(fullSysId)}')">
                                    <i class="fas fa-fingerprint text-xs"></i>
                                    <span class="font-mono text-xs">${escapeHtml(maskedSysId)}</span>
                                    <i class="fas fa-eye text-[10px] ml-1 opacity-60"></i>
                                </div>
                            </div>
                            
                            <!-- Traveler Name - full row, with ellipsis if long, tooltip on hover -->
                            <div class="mb-3 pb-2 border-b border-gray-100">
                                <div class="has-tooltip name-row" data-tooltip="${escapeHtml(traveler.fullName)}">
                                    <a href="${detailLink}" class="text-md font-bold text-gray-800 hover:text-primary-600 transition flex items-start gap-2">
                                        <i class="fas fa-user-circle text-primary-500 mt-0.5"></i>
                                        <span class="flex-1 break-words leading-tight">${escapeHtml(traveler.fullName.length > 45 ? traveler.fullName.substring(0,42)+'...' : traveler.fullName)}</span>
                                    </a>
                                </div>
                            </div>
                            
                            <!-- Passport + Phone -->
                            <div class="space-y-2 text-sm mb-3">
                                <div class="flex items-start gap-2">
                                    <i class="fas fa-passport w-4 mt-0.5 text-indigo-400 text-xs"></i>
                                    <div class="flex-1 flex justify-between">
                                        <span class="text-gray-500 text-xs font-medium">Passport No</span>
                                        <span class="font-mono text-gray-800 text-right">${escapeHtml(traveler.passportNo)}</span>
                                    </div>
                                </div>
                                <div class="flex items-start gap-2">
                                    <i class="fas fa-phone-alt w-4 mt-0.5 text-green-500 text-xs"></i>
                                    <div class="flex-1 flex justify-between">
                                        <span class="text-gray-500 text-xs font-medium">Primary Phone</span>
                                        <span class="text-gray-800">${escapeHtml(traveler.primaryPhone)}</span>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Email -->
                            <div class="mb-3 p-2 bg-gray-50 rounded-lg">
                                <div class="flex items-start gap-2">
                                    <i class="fas fa-envelope text-blue-400 w-4 mt-0.5 text-xs"></i>
                                    <div class="flex-1 overflow-hidden">
                                        <span class="text-gray-500 text-xs block">Primary Email</span>
                                        <span class="text-gray-800 text-sm break-all has-tooltip" data-tooltip="${escapeHtml(traveler.primaryEmail)}">${escapeHtml(traveler.primaryEmail.length > 35 ? traveler.primaryEmail.substring(0,32)+'...' : traveler.primaryEmail)}</span>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Created By / Updated By -->
                            <div class="grid grid-cols-2 gap-2 text-xs bg-white rounded-md mb-3">
                                <div class="border-r border-gray-100 pr-1">
                                    <span class="text-gray-500 block"><i class="fas fa-user-plus mr-1"></i> Created By</span>
                                    <span class="font-medium text-gray-700 truncate block has-tooltip" data-tooltip="${escapeHtml(traveler.createdBy)}">${escapeHtml(traveler.createdBy.length > 20 ? traveler.createdBy.substring(0,18)+'..' : traveler.createdBy)}</span>
                                </div>
                                <div class="pl-1">
                                    <span class="text-gray-500 block"><i class="fas fa-user-edit mr-1"></i> Updated By</span>
                                    <span class="font-medium text-gray-700 truncate block has-tooltip" data-tooltip="${escapeHtml(traveler.updatedBy)}">${escapeHtml(traveler.updatedBy.length > 20 ? traveler.updatedBy.substring(0,18)+'..' : traveler.updatedBy)}</span>
                                </div>
                            </div>
                            
                            <!-- Full Address (formatted) -->
                            <div class="mt-2 pt-2 border-t border-gray-100">
                                <div class="flex items-start gap-1.5">
                                    <i class="fas fa-map-pin text-amber-500 w-4 mt-0.5 text-xs"></i>
                                    <div class="flex-1">
                                        <span class="text-gray-500 text-[11px] font-semibold uppercase tracking-wide">Full Address</span>
                                        <p class="text-gray-700 text-xs leading-relaxed mt-1 has-tooltip" data-tooltip="${escapeHtml(traveler.addressFull)}">${escapeHtml(shortAddress)}</p>
                                        ${traveler.addressFull.length > 100 ? `<button onclick="showFullAddressModal('${escapeHtml(traveler.fullName)}', \`${escapeHtml(traveler.addressFull.replace(/`/g, '\\`'))}\`)" class="text-primary-500 text-[10px] mt-1 underline">show full</button>` : ''}
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="bg-gray-50 px-4 py-2 flex justify-between items-center text-xs border-t border-gray-100">
                            <a href="${detailLink}" class="text-primary-600 font-medium hover:underline"><i class="far fa-eye mr-1"></i> Details</a>
                            <button onclick="quickViewFullTraveler('${traveler.sys_id}')" class="text-gray-500 hover:text-gray-700 bg-white px-2 py-1 rounded-full shadow-sm"><i class="fas fa-info-circle mr-1"></i> Quick JSON</button>
                        </div>
                    </div>
                `;
            });
            gridContainer.innerHTML = cardsHtml;
        }

        // Show full sys_id in modal
        window.showFullSysIdModal = (name, fullSysId) => {
            const modalContent = `
                <div class="space-y-3">
                    <div class="bg-blue-50 p-3 rounded-lg">
                        <p class="font-bold text-gray-800"><i class="fas fa-user"></i> ${escapeHtml(name)}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 uppercase tracking-wide">Complete System ID (sys_id)</p>
                        <div class="bg-gray-100 p-3 rounded-lg mt-1 font-mono text-sm break-all">${escapeHtml(fullSysId)}</div>
                    </div>
                    <p class="text-xs text-gray-400">* First 4 characters are hidden in grid view, full ID shown here</p>
                </div>
            `;
            document.getElementById('modalPreviewContent').innerHTML = modalContent;
            document.getElementById('previewTitle').innerHTML = `<i class="fas fa-fingerprint"></i> System ID: ${escapeHtml(name)}`;
            document.getElementById('previewModal').style.display = 'flex';
        };

        window.showFullAddressModal = (name, fullAddr) => {
            const modalContent = `
                <div class="space-y-3">
                    <div class="bg-blue-50 p-3 rounded-lg">
                        <p class="font-bold text-gray-800"><i class="fas fa-user"></i> ${escapeHtml(name)}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 uppercase tracking-wide">Complete Address (Formatted)</p>
                        <div class="bg-gray-100 p-3 rounded-lg mt-1 whitespace-pre-wrap text-sm text-gray-800">${escapeHtml(fullAddr)}</div>
                    </div>
                </div>
            `;
            document.getElementById('modalPreviewContent').innerHTML = modalContent;
            document.getElementById('previewTitle').innerHTML = `<i class="fas fa-map-marked-alt"></i> Full Address: ${escapeHtml(name)}`;
            document.getElementById('previewModal').style.display = 'flex';
        };
        
        window.quickViewFullTraveler = (sysId) => {
            const traveler = masterTravelers.find(t => t.sys_id == sysId);
            if (!traveler) {
                alert("Traveler data missing");
                return;
            }
            let rawAddressJsonPreview = '';
            if (traveler.rawAddressObj) {
                try {
                    let raw = typeof traveler.rawAddressObj === 'string' ? traveler.rawAddressObj : JSON.stringify(traveler.rawAddressObj, null, 2);
                    rawAddressJsonPreview = `<div class="mt-2"><span class="font-semibold text-gray-700">Raw Address (JSON stored in DB):</span><pre class="bg-gray-100 p-2 rounded text-xs overflow-auto max-h-32">${escapeHtml(raw)}</pre></div>`;
                } catch(e) { rawAddressJsonPreview = `<div>Raw address: ${escapeHtml(String(traveler.rawAddressObj))}</div>`; }
            }
            const modalHtml = `
                <div class="grid grid-cols-2 gap-3 text-sm">
                    <div class="font-semibold">System ID (sys_id):</div><div class="font-mono break-all">${escapeHtml(traveler.sys_id)}</div>
                    <div class="font-semibold">Traveler Name:</div><div>${escapeHtml(traveler.fullName)}</div>
                    <div class="font-semibold">Passport No:</div><div>${escapeHtml(traveler.passportNo)}</div>
                    <div class="font-semibold">Primary Phone:</div><div>${escapeHtml(traveler.primaryPhone)}</div>
                    <div class="font-semibold">Primary Email:</div><div class="break-all">${escapeHtml(traveler.primaryEmail)}</div>
                    <div class="font-semibold">Created By:</div><div>${escapeHtml(traveler.createdBy)}</div>
                    <div class="font-semibold">Updated By:</div><div>${escapeHtml(traveler.updatedBy)}</div>
                </div>
                <div class="mt-4 border-t pt-2">
                    <p class="font-semibold text-gray-800"><i class="fas fa-map-marked-alt"></i> Full Address (Formatted)</p>
                    <p class="bg-gray-50 p-2 rounded text-sm">${escapeHtml(traveler.addressFull)}</p>
                    ${rawAddressJsonPreview}
                </div>
            `;
            document.getElementById('modalPreviewContent').innerHTML = modalHtml;
            document.getElementById('previewTitle').innerHTML = `<i class="fas fa-id-card"></i> Traveler Snapshot: ${escapeHtml(traveler.fullName)}`;
            document.getElementById('previewModal').style.display = 'flex';
        };
        
        window.closePreview = function() {
            document.getElementById('previewModal').style.display = 'none';
        };
        
        function escapeHtml(str) {
            if (!str) return '';
            return String(str).replace(/[&<>]/g, function(m) {
                if (m === '&') return '&amp;';
                if (m === '<') return '&lt;';
                if (m === '>') return '&gt;';
                return m;
            });
        }
        
        function debounce(func, delay) {
            let timer;
            return function(...args) {
                clearTimeout(timer);
                timer = setTimeout(() => func.apply(this, args), delay);
            };
        }
        
        const debouncedFilter = debounce(() => applyFiltersAndRender(), 250);
        searchInput.addEventListener('input', debouncedFilter);
        filterCreatedBy.addEventListener('change', () => applyFiltersAndRender());
        filterUpdatedBy.addEventListener('change', () => applyFiltersAndRender());
        resetBtn.addEventListener('click', () => {
            searchInput.value = '';
            filterCreatedBy.value = '';
            filterUpdatedBy.value = '';
            applyFiltersAndRender();
        });
        
        fetchTravelers();
        
        window.onclick = function(e) {
            const modal = document.getElementById('previewModal');
            if (e.target === modal) closePreview();
        };
        
        (function() {
            const mainContent = document.getElementById('mainContent');
            const updatePadding = () => {
                const aside = document.querySelector('aside');
                if (aside && window.getComputedStyle(aside).display === 'none') {
                    mainContent.classList.remove('pl-64');
                    mainContent.classList.add('pl-0');
                } else if (aside && aside.offsetWidth > 0) {
                    mainContent.classList.remove('pl-0');
                    mainContent.classList.add('pl-64');
                }
            };
            setTimeout(updatePadding, 150);
            window.addEventListener('resize', updatePadding);
        })();
    </script>
</body>
</html>