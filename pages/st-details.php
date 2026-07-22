<?php
/**
 * Show Traveler Details
 * Displays traveler information with inline editing capability
 */



$travelerId = $_GET['traveler_id'] ?? $_GET['id'] ?? null;

if (!$travelerId) {
    echo '<div class="text-red-500 p-4">No traveler ID provided</div>';
    exit;
}

// Fetch traveler data
$stmt = $pdo->prepare("SELECT * FROM travelers WHERE sys_id = ?");
$stmt->execute([$travelerId]);
$traveler = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$traveler) {
    echo '<div class="text-red-500 p-4">Traveler not found</div>';
    exit;
}

// Parse JSON fields with null check
$phoneData = json_decode($traveler['phone'] ?? '{}', true) ?: ['primary_no' => '', 'secondary_no' => []];
$emailData = json_decode($traveler['email'] ?? '{}', true) ?: ['primary' => '', 'secondary' => []];
$addressData = json_decode($traveler['address'] ?? '{}', true) ?: ['address_line_1' => '', 'address_line_2' => '', 'city' => '', 'state' => '', 'zip_code' => '', 'country' => ''];
$metaData = json_decode($traveler['meta_data'] ?? '{}', true) ?: [];
$passportInfo = json_decode($traveler['passport_info'] ?? '[]', true) ?: [];
$nidInfo = json_decode($traveler['nid_info'] ?? '[]', true) ?: [];
$summary = $traveler['summary'] ?? 'No Summary Provided Yet!';

// Get latest passport data
$latestPassport = !empty($passportInfo) ? $passportInfo[0] : null;
$latestNid = !empty($nidInfo) ? $nidInfo[0] : null;

// If address is empty but passport has it, use passport address
if (empty($addressData['address_line_1']) && empty($addressData['city']) && $latestPassport && isset($latestPassport['bio_info'])) {
    $bio = $latestPassport['bio_info'];
    
    // Map passport fields to address
    if (!empty($bio['permanent_address'])) {
        $addressData['address_line_1'] = $bio['permanent_address'];
    }
    if (!empty($bio['place_of_birth'])) {
        $addressData['city'] = $bio['place_of_birth'];
    }
    if (!empty($bio['country_code'])) {
        // Convert country code to name if needed
        $countryMap = [
            'BGD' => 'Bangladesh',
            'IND' => 'India',
            'USA' => 'United States',
            'GBR' => 'United Kingdom',
            'CAN' => 'Canada',
            'AUS' => 'Australia',
        ];
        $addressData['country'] = $countryMap[$bio['country_code']] ?? $bio['country_code'];
    }
    if (!empty($bio['nationality'])) {
        $addressData['state'] = $bio['nationality'];
    }
}

// Update API endpoint
$ip_port = @file_get_contents('../ippath.txt');
if (empty($ip_port)) {
    $ip_port = "http://103.104.219.3:898";
}
$updateApi = $ip_port . "api/travelers/update.php";
?>

<style>
    .editable-field {
        position: relative;
        transition: all 0.2s ease;
    }
    .editable-field:hover {
        background-color: #f9fafb;
    }
    .editable-field .action-icon {
        opacity: 0;
        transition: opacity 0.2s ease;
        cursor: pointer;
    }
    .editable-field:hover .action-icon {
        opacity: 1;
    }
    .editable-field.editing {
        background-color: #f0f7ff;
        border-color: #3b82f6 !important;
    }
    .editable-field.editing input, 
    .editable-field.editing textarea, 
    .editable-field.editing select {
        border-color: #3b82f6 !important;
        box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.1);
    }
    .info-card {
        transition: all 0.2s ease;
    }
    .info-card:hover {
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
    }
    .field-value {
        cursor: pointer;
        transition: background-color 0.2s ease;
        padding: 2px 6px;
        border-radius: 4px;
        display: inline-block;
    }
    .field-value:hover {
        background-color: #e5e7eb;
    }
    .copied {
        background-color: #d1fae5 !important;
        transition: background-color 0.3s ease;
    }
    
    .copied-flash {
        animation: copyFlash 0.6s ease;
    }
    @keyframes copyFlash {
        0% { background-color: rgba(255, 255, 255, 0.3); }
        50% { background-color: rgba(74, 222, 128, 0.5); }
        100% { background-color: rgba(255, 255, 255, 0.2); }
    }
    
    /* Tooltip for copy */
    .copy-tooltip {
        position: absolute;
        top: -30px;
        left: 50%;
        transform: translateX(-50%);
        background: #1f2937;
        color: white;
        font-size: 11px;
        padding: 4px 8px;
        border-radius: 4px;
        opacity: 0;
        transition: opacity 0.2s;
        pointer-events: none;
        white-space: nowrap;
    }
    .group:hover .copy-tooltip {
        opacity: 1;
    }
</style>

<div class="grid grid-cols-3 gap-6 h-full min-h-0 items-stretch overflow-hidden">
    
    <!-- ---------------------------------------------------- -->
    <!-- (Traveler Profile - Independent Scroll) -->
    <!-- ---------------------------------------------------- -->
    <div class="col-span-2 h-full overflow-y-auto pr-2 custom-scrollbar space-y-4 text-left min-h-0">
        
        <!-- Traveler Header Info -->
        <div class="bg-gradient-to-r from-blue-700 to-purple-700 rounded-lg p-5 text-white shadow-lg">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-2xl font-bold tracking-wide"><?php echo htmlspecialchars($traveler['name']); ?></h3>
                    <div class="flex items-center gap-4 mt-3 text-sm flex-wrap">
                        <div class="bg-white/20 backdrop-blur-sm rounded-lg px-3 py-2 hover:bg-white/30 transition-all cursor-pointer group relative" 
                             onclick="copyToClipboard(this, '<?php echo htmlspecialchars($traveler['passport_no'] ?? ''); ?>')"
                             title="Click to copy passport number">
                            <i class="fas fa-passport mr-1.5 text-blue-200"></i>
                            <span class="font-medium">
                                <?php echo !empty($traveler['passport_no']) ? htmlspecialchars($traveler['passport_no']) : '<span class="text-white/60 italic">Passport: -</span>'; ?>
                            </span>
                            <i class="fas fa-copy text-xs opacity-0 group-hover:opacity-100 ml-1.5 transition-opacity"></i>
                            <span class="absolute -top-8 left-1/2 -translate-x-1/2 bg-gray-900 text-white text-xs px-2 py-1 rounded opacity-0 group-hover:opacity-100 transition-opacity whitespace-nowrap pointer-events-none">
                                Click to copy
                            </span>
                        </div>
                        <div class="bg-white/20 backdrop-blur-sm rounded-lg px-3 py-2 hover:bg-white/30 transition-all cursor-pointer group relative"
                             onclick="copyToClipboard(this, '<?php echo htmlspecialchars($traveler['nid_no'] ?? ''); ?>')"
                             title="Click to copy NID number">
                            <i class="fas fa-id-card mr-1.5 text-blue-200"></i>
                            <span class="font-medium">
                                <?php echo !empty($traveler['nid_no']) ? htmlspecialchars($traveler['nid_no']) : '<span class="text-white/60 italic">NID: -</span>'; ?>
                            </span>
                            <i class="fas fa-copy text-xs opacity-0 group-hover:opacity-100 ml-1.5 transition-opacity"></i>
                            <span class="absolute -top-8 left-1/2 -translate-x-1/2 bg-gray-900 text-white text-xs px-2 py-1 rounded opacity-0 group-hover:opacity-100 transition-opacity whitespace-nowrap pointer-events-none">
                                Click to copy
                            </span>
                        </div>
                        <div class="bg-white/20 backdrop-blur-sm rounded-lg px-3 py-2 hover:bg-white/30 transition-all cursor-pointer group relative"
                             onclick="copyToClipboard(this, '<?php echo htmlspecialchars($traveler['date_of_birth'] ?? ''); ?>')"
                             title="Click to copy date of birth">
                            <i class="fas fa-calendar mr-1.5 text-blue-200"></i>
                            <span class="font-medium">
                                <?php echo !empty($traveler['date_of_birth']) ? htmlspecialchars($traveler['date_of_birth']) : '<span class="text-white/60 italic">DOB: -</span>'; ?>
                            </span>
                            <i class="fas fa-copy text-xs opacity-0 group-hover:opacity-100 ml-1.5 transition-opacity"></i>
                            <span class="absolute -top-8 left-1/2 -translate-x-1/2 bg-gray-900 text-white text-xs px-2 py-1 rounded opacity-0 group-hover:opacity-100 transition-opacity whitespace-nowrap pointer-events-none">
                                Click to copy
                            </span>
                        </div>
                    </div>
                </div>
                <div class="flex flex-col items-end gap-2">
                    <span class="px-4 py-1.5 rounded-full text-xs font-bold uppercase tracking-wider <?php echo $traveler['status'] === 'active' ? 'bg-green-400 text-green-900' : 'bg-gray-400 text-gray-900'; ?>">
                        <?php echo strtoupper($traveler['status'] ?? 'active'); ?>
                    </span>
                </div>
            </div>
            <div class="mt-4 pt-3 border-t border-white/20 text-sm text-blue-100 flex items-center gap-4">
                <div class="bg-white/10 rounded-lg px-3 py-1.5 cursor-pointer hover:bg-white/20 transition-all group relative"
                     onclick="copyToClipboard(this, '<?php echo htmlspecialchars($traveler['sys_id']); ?>')"
                     title="Click to copy Sys ID">
                    <i class="fas fa-fingerprint mr-1.5 text-blue-300"></i>
                    <span class="font-mono text-xs font-medium"><?php echo htmlspecialchars($traveler['sys_id']); ?></span>
                    <i class="fas fa-copy text-xs opacity-0 group-hover:opacity-100 ml-1 transition-opacity"></i>
                </div>
                <div class="flex items-center gap-2 text-xs">
                    <i class="fas fa-clock text-blue-300"></i>
                    <span>Created: <strong><?php echo !empty($metaData['created_by_date']['date']) ? htmlspecialchars($metaData['created_by_date']['date']) : '-'; ?></strong></span>
                    <span class="text-blue-300">by</span>
                    <span><strong><?php echo !empty($metaData['created_by_date']['user']) ? htmlspecialchars($metaData['created_by_date']['user']) : 'system'; ?></strong></span>
                </div>
            </div>
        </div>
    
        <!-- Basic Information Card -->
        <div class="info-card bg-white rounded-lg shadow p-5">
            <div class="flex items-center justify-between mb-4">
                <h4 class="text-lg font-semibold text-gray-800 flex items-center">
                    <i class="fas fa-info-circle mr-2 text-blue-600"></i>Basic Information
                </h4>
            </div>
            
            <div class="space-y-3">
                <!-- Phone & Email Side by Side -->
                <div class="grid grid-cols-2 gap-3">
                    <!-- Phone -->
                    <div class="editable-field border rounded-lg p-3">
                        <div class="flex items-center justify-between mb-1">
                            <label class="text-xs font-semibold text-gray-500 uppercase">
                                <i class="fas fa-phone mr-1"></i>Primary Phone
                            </label>
                            <button onclick="editField(this)" class="action-icon text-gray-400 hover:text-blue-600">
                                <i class="fas fa-pen text-xs"></i>
                            </button>
                        </div>
                        <div class="field-display">
                            <span class="text-sm text-gray-800 field-value" onclick="copyToClipboard(this, '<?php echo htmlspecialchars($phoneData['primary_no'] ?? ''); ?>')">
                                <?php echo !empty($phoneData['primary_no']) ? htmlspecialchars($phoneData['primary_no']) : '<span class="text-gray-400 italic">Click to add phone</span>'; ?>
                            </span>
                        </div>
                        <div class="field-input hidden mt-2">
                            <input type="text" data-phone="primary_no"
                                class="w-full px-3 py-2 border border-blue-300 rounded-md text-sm mb-2"
                                value="<?php echo htmlspecialchars($phoneData['primary_no'] ?? ''); ?>"
                                placeholder="+1 (555) 123-4567">
                            <div class="secondary-phones-container space-y-1 mb-2">
                                <?php foreach (($phoneData['secondary_no'] ?? [['type' => 'mobile', 'number' => '']]) as $idx => $secPhone): ?>
                                    <div class="flex items-center gap-2">
                                        <select class="px-2 py-1 border border-gray-300 rounded text-xs">
                                            <option value="mobile" <?php echo ($secPhone['type'] ?? '') === 'mobile' ? 'selected' : ''; ?>>Mobile</option>
                                            <option value="home" <?php echo ($secPhone['type'] ?? '') === 'home' ? 'selected' : ''; ?>>Home</option>
                                            <option value="work" <?php echo ($secPhone['type'] ?? '') === 'work' ? 'selected' : ''; ?>>Work</option>
                                            <option value="other" <?php echo ($secPhone['type'] ?? '') === 'other' ? 'selected' : ''; ?>>Other</option>
                                        </select>
                                        <input type="text" class="flex-1 px-2 py-1 border border-gray-300 rounded text-xs" value="<?php echo htmlspecialchars($secPhone['number'] ?? ''); ?>" placeholder="Phone number">
                                        <button type="button" onclick="this.parentElement.remove()" class="text-red-500 hover:text-red-700 text-xs"><i class="fas fa-times"></i></button>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <div class="flex gap-2">
                                <button type="button" onclick="addSecondaryPhone(this)" class="text-xs text-blue-600 hover:text-blue-800">
                                    <i class="fas fa-plus mr-1"></i>Add Phone
                                </button>
                                <div class="flex-1"></div>
                                <button onclick="saveField(this, 'phone')" class="px-3 py-1 bg-green-600 text-white rounded-md text-sm hover:bg-green-700">
                                    <i class="fas fa-check"></i> Save
                                </button>
                                <button onclick="cancelEdit(this)" class="px-3 py-1 bg-gray-300 text-gray-700 rounded-md text-sm hover:bg-gray-400">
                                    <i class="fas fa-times"></i> Cancel
                                </button>
                            </div>
                        </div>
                    </div>
    
                    <!-- Email -->
                    <div class="editable-field border rounded-lg p-3">
                        <div class="flex items-center justify-between mb-1">
                            <label class="text-xs font-semibold text-gray-500 uppercase">
                                <i class="fas fa-envelope mr-1"></i>Primary Email
                            </label>
                            <button onclick="editField(this)" class="action-icon text-gray-400 hover:text-blue-600">
                                <i class="fas fa-pen text-xs"></i>
                            </button>
                        </div>
                        <div class="field-display">
                            <span class="text-sm text-gray-800 field-value" onclick="copyToClipboard(this, '<?php echo htmlspecialchars($emailData['primary'] ?? ''); ?>')">
                                <?php echo !empty($emailData['primary']) ? htmlspecialchars($emailData['primary']) : '<span class="text-gray-400 italic">Click to add email</span>'; ?>
                            </span>
                        </div>
                        <div class="field-input hidden mt-2">
                            <input type="email" data-email="primary"
                                class="w-full px-3 py-2 border border-blue-300 rounded-md text-sm mb-2"
                                value="<?php echo htmlspecialchars($emailData['primary'] ?? ''); ?>"
                                placeholder="john@example.com">
                            <div class="secondary-emails-container space-y-1 mb-2">
                                <?php foreach (($emailData['secondary'] ?? [['type' => 'personal', 'address' => '']]) as $secEmail): ?>
                                    <div class="flex items-center gap-2">
                                        <select class="px-2 py-1 border border-gray-300 rounded text-xs">
                                            <option value="work" <?php echo ($secEmail['type'] ?? '') === 'work' ? 'selected' : ''; ?>>Work</option>
                                            <option value="personal" <?php echo ($secEmail['type'] ?? '') === 'personal' ? 'selected' : ''; ?>>Personal</option>
                                            <option value="other" <?php echo ($secEmail['type'] ?? '') === 'other' ? 'selected' : ''; ?>>Other</option>
                                        </select>
                                        <input type="email" class="flex-1 px-2 py-1 border border-gray-300 rounded text-xs" value="<?php echo htmlspecialchars($secEmail['address'] ?? ''); ?>" placeholder="Email">
                                        <button type="button" onclick="this.parentElement.remove()" class="text-red-500 hover:text-red-700 text-xs"><i class="fas fa-times"></i></button>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <div class="flex gap-2">
                                <button type="button" onclick="addSecondaryEmail(this)" class="text-xs text-blue-600 hover:text-blue-800">
                                    <i class="fas fa-plus mr-1"></i>Add Email
                                </button>
                                <div class="flex-1"></div>
                                <button onclick="saveField(this, 'email')" class="px-3 py-1 bg-green-600 text-white rounded-md text-sm hover:bg-green-700">
                                    <i class="fas fa-check"></i> Save
                                </button>
                                <button onclick="cancelEdit(this)" class="px-3 py-1 bg-gray-300 text-gray-700 rounded-md text-sm hover:bg-gray-400">
                                    <i class="fas fa-times"></i> Cancel
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
    
                <!-- Address -->
                <div class="editable-field border rounded-lg p-3">
                    <div class="flex items-center justify-between mb-2">
                        <label class="text-xs font-semibold text-gray-500 uppercase">
                            <i class="fas fa-map-marker-alt mr-1"></i>Address
                        </label>
                        <button onclick="editField(this)" class="action-icon text-gray-400 hover:text-blue-600">
                            <i class="fas fa-pen text-xs"></i>
                        </button>
                    </div>
                    
                    <!-- Display Mode - Shows all fields stacked -->
                    <div class="field-display space-y-1.5">
                        <div class="text-sm text-gray-800">
                            <div class="flex items-start gap-2">
                                <span class="text-xs text-gray-400 w-20 flex-shrink-0">Line 1:</span>
                                <span class="field-value flex-1" onclick="copyToClipboard(this, '<?php echo htmlspecialchars($addressData['address_line_1'] ?? ''); ?>')">
                                    <?php echo !empty($addressData['address_line_1']) ? htmlspecialchars($addressData['address_line_1']) : '<span class="text-gray-400 italic">-</span>'; ?>
                                </span>
                            </div>
                            
                            <div class="flex items-start gap-2">
                                <span class="text-xs text-gray-400 w-20 flex-shrink-0">Line 2:</span>
                                <span class="field-value flex-1" onclick="copyToClipboard(this, '<?php echo htmlspecialchars($addressData['address_line_2'] ?? ''); ?>')">
                                    <?php echo !empty($addressData['address_line_2']) ? htmlspecialchars($addressData['address_line_2']) : '<span class="text-gray-400 italic">-</span>'; ?>
                                </span>
                            </div>
                            
                            <div class="flex items-start gap-2">
                                <span class="text-xs text-gray-400 w-20 flex-shrink-0">City:</span>
                                <span class="field-value flex-1" onclick="copyToClipboard(this, '<?php echo htmlspecialchars($addressData['city'] ?? ''); ?>')">
                                    <?php echo !empty($addressData['city']) ? htmlspecialchars($addressData['city']) : '<span class="text-gray-400 italic">-</span>'; ?>
                                </span>
                            </div>
                            
                            <div class="flex items-start gap-2">
                                <span class="text-xs text-gray-400 w-20 flex-shrink-0">State:</span>
                                <span class="field-value flex-1" onclick="copyToClipboard(this, '<?php echo htmlspecialchars($addressData['state'] ?? ''); ?>')">
                                    <?php echo !empty($addressData['state']) ? htmlspecialchars($addressData['state']) : '<span class="text-gray-400 italic">-</span>'; ?>
                                </span>
                            </div>
                            
                            <div class="flex items-start gap-2">
                                <span class="text-xs text-gray-400 w-20 flex-shrink-0">ZIP:</span>
                                <span class="field-value flex-1" onclick="copyToClipboard(this, '<?php echo htmlspecialchars($addressData['zip_code'] ?? ''); ?>')">
                                    <?php echo !empty($addressData['zip_code']) ? htmlspecialchars($addressData['zip_code']) : '<span class="text-gray-400 italic">-</span>'; ?>
                                </span>
                            </div>
                            
                            <div class="flex items-start gap-2">
                                <span class="text-xs text-gray-400 w-20 flex-shrink-0">Country:</span>
                                <span class="field-value flex-1" onclick="copyToClipboard(this, '<?php echo htmlspecialchars($addressData['country'] ?? ''); ?>')">
                                    <?php echo !empty($addressData['country']) ? htmlspecialchars($addressData['country']) : '<span class="text-gray-400 italic">-</span>'; ?>
                                </span>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Edit Mode -->
                    <div class="field-input hidden mt-2 space-y-2">
                        <div class="grid grid-cols-2 gap-2">
                            <div>
                                <label class="text-xs text-gray-500 mb-1 block">Address Line 1</label>
                                <input type="text" data-addr="address_line_1" 
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500" 
                                    value="<?php echo htmlspecialchars($addressData['address_line_1'] ?? ''); ?>" 
                                    placeholder="Street address, P.O. box">
                            </div>
                            <div>
                                <label class="text-xs text-gray-500 mb-1 block">Address Line 2</label>
                                <input type="text" data-addr="address_line_2" 
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500" 
                                    value="<?php echo htmlspecialchars($addressData['address_line_2'] ?? ''); ?>" 
                                    placeholder="Apartment, suite, unit, building, floor">
                            </div>
                        </div>
                        <div class="grid grid-cols-3 gap-2">
                            <div>
                                <label class="text-xs text-gray-500 mb-1 block">City</label>
                                <input type="text" data-addr="city" 
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500" 
                                    value="<?php echo htmlspecialchars($addressData['city'] ?? ''); ?>" 
                                    placeholder="City">
                            </div>
                            <div>
                                <label class="text-xs text-gray-500 mb-1 block">State/Province</label>
                                <input type="text" data-addr="state" 
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500" 
                                    value="<?php echo htmlspecialchars($addressData['state'] ?? ''); ?>" 
                                    placeholder="State">
                            </div>
                            <div>
                                <label class="text-xs text-gray-500 mb-1 block">ZIP/Postal Code</label>
                                <input type="text" data-addr="zip_code" 
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500" 
                                    value="<?php echo htmlspecialchars($addressData['zip_code'] ?? ''); ?>" 
                                    placeholder="ZIP Code">
                            </div>
                        </div>
                        <div>
                            <label class="text-xs text-gray-500 mb-1 block">Country</label>
                            <input type="text" data-addr="country" 
                                class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500" 
                                value="<?php echo htmlspecialchars($addressData['country'] ?? ''); ?>" 
                                placeholder="Country">
                        </div>
                        <div class="flex justify-end gap-2 pt-2">
                            <button onclick="saveField(this, 'address')" class="px-4 py-2 bg-green-600 text-white rounded-md text-sm hover:bg-green-700 transition-colors">
                                <i class="fas fa-check mr-1"></i> Save
                            </button>
                            <button onclick="cancelEdit(this)" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-md text-sm hover:bg-gray-300 transition-colors">
                                <i class="fas fa-times mr-1"></i> Cancel
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    
        <!-- Passport Information Card -->
        <?php
            $bio = $latestPassport['bio_info'] ?? [];
        ?>
        <div class="info-card bg-white rounded-lg shadow p-5">
            <div class="flex items-center justify-between mb-4">
                <h4 class="text-lg font-semibold text-gray-800 flex items-center">
                    <i class="fas fa-passport mr-2 text-green-600"></i>Passport Information
                </h4>
                <span class="text-xs text-gray-500">
                    Source: <?php echo htmlspecialchars(basename($latestPassport['_metadata']['source_file'] ?? 'N/A')); ?>
                </span>
            </div>
            
            <div class="grid grid-cols-2 md:grid-cols-3 gap-3">
                <?php 
                $passportFields = [
                    'passport_number' => 'Passport No',
                    'country_code' => 'Country Code',
                    'full_name' => 'Full Name',
                    'surname' => 'Surname',
                    'given_names' => 'Given Names',
                    'nationality' => 'Nationality',
                    'date_of_birth' => 'Date of Birth',
                    'date_of_issue' => 'Issue Date',
                    'date_of_expiry' => 'Expiry Date',
                    'sex' => 'Sex',
                    'place_of_birth' => 'Place of Birth',
                    'issuing_authority' => 'Issuing Authority',
                    'father_name' => "Father",
                    'mother_name' => "Mother",
                    'spouse_name' => "Spouse",
                    'permanent_address' => 'Address',
                ];
                
                foreach ($passportFields as $key => $label): 
                    $value = $bio[$key] ?? '';
                ?>
                    <div class="editable-field border rounded-lg p-2" data-pp-field="<?php echo $key; ?>">
                        <div class="flex items-center justify-between mb-1">
                            <label class="text-xs font-semibold text-gray-500"><?php echo $label; ?></label>
                            <button onclick="editField(this)" class="action-icon text-gray-400 hover:text-blue-600">
                                <i class="fas fa-pen text-xs"></i>
                            </button>
                        </div>
                        <div class="field-display">
                            <span class="text-sm text-gray-800 field-value" onclick="copyToClipboard(this, '<?php echo htmlspecialchars($value); ?>')">
                                <?php echo !empty($value) ? htmlspecialchars($value) : '<span class="text-gray-400 italic">-</span>'; ?>
                            </span>
                        </div>
                        <div class="field-input hidden mt-1">
                            <?php if ($key === 'permanent_address'): ?>
                                <textarea class="w-full px-3 py-2 border border-blue-300 rounded-md text-sm" rows="2" data-pp-value="<?php echo $key; ?>"><?php echo htmlspecialchars($value); ?></textarea>
                            <?php else: ?>
                                <input type="text" class="w-full px-3 py-2 border border-blue-300 rounded-md text-sm" data-pp-value="<?php echo $key; ?>" value="<?php echo htmlspecialchars($value); ?>">
                            <?php endif; ?>
                            <div class="flex justify-end gap-2 mt-1">
                                <button onclick="savePassportField(this)" class="px-3 py-1 bg-green-600 text-white rounded-md text-xs hover:bg-green-700">
                                    <i class="fas fa-check"></i>
                                </button>
                                <button onclick="cancelEdit(this)" class="px-3 py-1 bg-gray-300 text-gray-700 rounded-md text-xs hover:bg-gray-400">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
    
            <!-- Emergency Contact Section -->
            <?php
                $emergency = $bio['emergency_contact'] ?? [];
            ?>
            <div class="mt-4 border-t pt-4">
                <h5 class="text-sm font-semibold text-gray-700 mb-3 flex items-center">
                    <i class="fas fa-phone-alt mr-2 text-red-500"></i>Emergency Contact
                </h5>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                    <?php 
                    $emergencyFields = ['name' => 'Name', 'relationship' => 'Relationship', 'telephone' => 'Telephone', 'address' => 'Address'];
                    foreach ($emergencyFields as $ekey => $elabel): 
                        $evalue = $emergency[$ekey] ?? '';
                    ?>
                        <div class="editable-field border rounded-lg p-2" data-emergency-field="<?php echo $ekey; ?>">
                            <div class="flex items-center justify-between mb-1">
                                <label class="text-xs font-semibold text-gray-500"><?php echo $elabel; ?></label>
                                <button onclick="editField(this)" class="action-icon text-gray-400 hover:text-blue-600">
                                    <i class="fas fa-pen text-xs"></i>
                                </button>
                            </div>
                            <div class="field-display">
                                <span class="text-sm text-gray-800 field-value" onclick="copyToClipboard(this, '<?php echo htmlspecialchars($evalue); ?>')">
                                    <?php echo !empty($evalue) ? htmlspecialchars($evalue) : '<span class="text-gray-400 italic">-</span>'; ?>
                                </span>
                            </div>
                            <div class="field-input hidden mt-1">
                                <input type="text" class="w-full px-3 py-2 border border-blue-300 rounded-md text-sm" data-emergency-value="<?php echo $ekey; ?>" value="<?php echo htmlspecialchars($evalue); ?>">
                                <div class="flex justify-end gap-2 mt-1">
                                    <button onclick="saveEmergencyField(this)" class="px-3 py-1 bg-green-600 text-white rounded-md text-xs hover:bg-green-700">
                                        <i class="fas fa-check"></i>
                                    </button>
                                    <button onclick="cancelEdit(this)" class="px-3 py-1 bg-gray-300 text-gray-700 rounded-md text-xs hover:bg-gray-400">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
    
            <!-- MRZ Section -->
            <div class="mt-4 border-t pt-4">
                <h5 class="text-sm font-semibold text-gray-700 mb-3 flex items-center">
                    <i class="fas fa-barcode mr-2 text-purple-500"></i>Machine Readable Zone (MRZ)
                </h5>
                <div class="editable-field border rounded-lg p-3 bg-gray-50">
                    <div class="flex items-center justify-between mb-2">
                        <label class="text-xs font-semibold text-gray-500 uppercase">MRZ Lines</label>
                        <button onclick="editField(this)" class="action-icon text-gray-400 hover:text-blue-600">
                            <i class="fas fa-pen text-xs"></i>
                        </button>
                    </div>
                    <div class="field-display space-y-1">
                        <div class="text-sm font-mono bg-gray-100 p-2 rounded field-value" onclick="copyToClipboard(this, '<?php echo htmlspecialchars($bio['mrz_line_1'] ?? ''); ?>')">
                            <?php echo !empty($bio['mrz_line_1']) ? htmlspecialchars($bio['mrz_line_1']) : '<span class="text-gray-400 italic">MRZ Line 1</span>'; ?>
                        </div>
                        <div class="text-sm font-mono bg-gray-100 p-2 rounded field-value" onclick="copyToClipboard(this, '<?php echo htmlspecialchars($bio['mrz_line_2'] ?? ''); ?>')">
                            <?php echo !empty($bio['mrz_line_2']) ? htmlspecialchars($bio['mrz_line_2']) : '<span class="text-gray-400 italic">MRZ Line 2</span>'; ?>
                        </div>
                    </div>
                    <div class="field-input hidden mt-2 space-y-2">
                        <input type="text" class="w-full px-3 py-2 border border-blue-300 rounded-md text-sm font-mono" data-mrz="mrz_line_1" value="<?php echo htmlspecialchars($bio['mrz_line_1'] ?? ''); ?>" placeholder="MRZ Line 1">
                        <input type="text" class="w-full px-3 py-2 border border-blue-300 rounded-md text-sm font-mono" data-mrz="mrz_line_2" value="<?php echo htmlspecialchars($bio['mrz_line_2'] ?? ''); ?>" placeholder="MRZ Line 2">
                        <div class="flex justify-end gap-2">
                            <button onclick="saveMRZ(this)" class="px-3 py-1 bg-green-600 text-white rounded-md text-sm hover:bg-green-700">
                                <i class="fas fa-check"></i> Save
                            </button>
                            <button onclick="cancelEdit(this)" class="px-3 py-1 bg-gray-300 text-gray-700 rounded-md text-sm hover:bg-gray-400">
                                <i class="fas fa-times"></i> Cancel
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    
        <!-- NID Information Card -->
        <?php
            $nidData = $latestNid['nid_info'] ?? [];
        ?>
        <div class="info-card bg-white rounded-lg shadow p-5">
            <div class="flex items-center justify-between mb-4">
                <h4 class="text-lg font-semibold text-gray-800 flex items-center">
                    <i class="fas fa-id-card mr-2 text-orange-600"></i>NID Information
                </h4>
                <span class="text-xs text-gray-500">
                    Type: <?php echo htmlspecialchars($latestNid['page_type'] ?? 'N/A'); ?>
                </span>
            </div>
            
            <div class="grid grid-cols-2 md:grid-cols-3 gap-3">
                <?php 
                $nidFields = [
                    'national_id' => 'National ID',
                    'pin' => 'PIN',
                    'name_bn' => 'Name Bangla',
                    'name_en' => 'Name English',
                    'father_name' => 'Father Name',
                    'mother_name' => 'Mother Name',
                    'date_of_birth' => 'Date of Birth',
                    'blood_group' => 'Blood Group',
                    'address' => 'Address',
                    'issue_date' => 'Issue Date',
                ];
                foreach ($nidFields as $key => $label): 
                    $value = $nidData[$key] ?? '';
                ?>
                    <div class="editable-field border rounded-lg p-2" data-nid-field="<?php echo $key; ?>">
                        <div class="flex items-center justify-between mb-1">
                            <label class="text-xs font-semibold text-gray-500"><?php echo $label; ?></label>
                            <button onclick="editField(this)" class="action-icon text-gray-400 hover:text-blue-600">
                                <i class="fas fa-pen text-xs"></i>
                            </button>
                        </div>
                        <div class="field-display">
                            <span class="text-sm text-gray-800 field-value" onclick="copyToClipboard(this, '<?php echo htmlspecialchars($value); ?>')">
                                <?php echo !empty($value) ? htmlspecialchars($value) : '<span class="text-gray-400 italic">-</span>'; ?>
                            </span>
                        </div>
                        <div class="field-input hidden mt-1">
                            <input type="text" class="w-full px-3 py-2 border border-blue-300 rounded-md text-sm" data-nid-value="<?php echo $key; ?>" value="<?php echo htmlspecialchars($value); ?>">
                            <div class="flex justify-end gap-2 mt-1">
                                <button onclick="saveNidField(this)" class="px-3 py-1 bg-green-600 text-white rounded-md text-xs hover:bg-green-700">
                                    <i class="fas fa-check"></i>
                                </button>
                                <button onclick="cancelEdit(this)" class="px-3 py-1 bg-gray-300 text-gray-700 rounded-md text-xs hover:bg-gray-400">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    
    </div>
    
    
    <!-- RIGHT PANEL: Traveler Summary Card (Independent Scroll) -->
    <div class="border-l border-gray-300 pl-6 h-full max-h-full overflow-y-auto custom-scrollbar">
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 flex flex-col h-full min-h-0">
            <!-- Fixed Header -->
            <div class="text-center pb-4 mb-3 border-b border-gray-100 flex-shrink-0">
                <i class="fas fa-user-circle text-5xl text-purple-600 mb-2 block"></i>
                <h3 class="text-xl font-bold text-gray-800">Traveler Summary</h3>
            </div>
            <!-- Scrollable Content Segment -->
            <div class="overflow-y-auto flex-1 pr-1 custom-scrollbar">
                <p class="text-justify text-gray-600 leading-relaxed text-sm whitespace-pre-line">
                    <?= $summary ?>
                </p>
            </div>
        </div>
    </div>
</div>

<style>
    .custom-scrollbar::-webkit-scrollbar { width: 5px; }
    .custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
    .custom-scrollbar::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 4px; }
    .custom-scrollbar::-webkit-scrollbar-thumb:hover { background: #94a3b8; }
</style>

<script>
    const UPDATE_API = "<?php echo $updateApi; ?>";
    const TRAVELER_ID = "<?php echo $travelerId; ?>";

    // Copy to clipboard - click on field value with better feedback
    function copyToClipboard(element, text) {
        if (!text || text.trim() === '') return;
        
        navigator.clipboard.writeText(text).then(() => {
            // Flash effect
            element.classList.add('copied-flash');
            setTimeout(() => element.classList.remove('copied-flash'), 600);
            
            // Change the tooltip text temporarily
            const tooltip = element.querySelector('.copy-tooltip, span[class*="top-8"]');
            if (tooltip) {
                const originalText = tooltip.textContent;
                tooltip.textContent = '✓ Copied!';
                tooltip.style.opacity = '1';
                setTimeout(() => {
                    tooltip.textContent = originalText;
                    tooltip.style.opacity = '0';
                }, 1500);
            }
            
            // Show toast
            showToast('Copied to clipboard!', 'success');
        }).catch(() => {
            // Fallback
            const textarea = document.createElement('textarea');
            textarea.value = text;
            textarea.style.position = 'fixed';
            textarea.style.opacity = '0';
            document.body.appendChild(textarea);
            textarea.select();
            document.execCommand('copy');
            document.body.removeChild(textarea);
            
            element.classList.add('copied-flash');
            setTimeout(() => element.classList.remove('copied-flash'), 600);
        });
    }
    
    // Edit field - show input
    function editField(btn) {
        const field = btn.closest('.editable-field');
        field.classList.add('editing');
        field.querySelector('.field-display').classList.add('hidden');
        field.querySelector('.field-input').classList.remove('hidden');
        
        const firstInput = field.querySelector('input, textarea');
        if (firstInput) firstInput.focus();
    }

    // Cancel edit
    function cancelEdit(btn) {
        const field = btn.closest('.editable-field');
        field.classList.remove('editing');
        field.querySelector('.field-display').classList.remove('hidden');
        field.querySelector('.field-input').classList.add('hidden');
    }

    // Save basic field (phone, email, address)
    async function saveField(btn, type) {
        const field = btn.closest('.editable-field');
        let data = {};
        
        if (type === 'phone') {
            data.primary_no = field.querySelector('[data-phone="primary_no"]').value;
            data.secondary_no = [];
            field.querySelectorAll('.secondary-phones-container .flex.items-center').forEach(item => {
                const sel = item.querySelector('select');
                const inp = item.querySelector('input');
                if (sel && inp && inp.value.trim()) {
                    data.secondary_no.push({ type: sel.value, number: inp.value.trim() });
                }
            });
        } else if (type === 'email') {
            data.primary = field.querySelector('[data-email="primary"]').value;
            data.secondary = [];
            field.querySelectorAll('.secondary-emails-container .flex.items-center').forEach(item => {
                const sel = item.querySelector('select');
                const inp = item.querySelector('input');
                if (sel && inp && inp.value.trim()) {
                    data.secondary.push({ type: sel.value, address: inp.value.trim() });
                }
            });
        } else if (type === 'address') {
            field.querySelectorAll('[data-addr]').forEach(inp => {
                data[inp.getAttribute('data-addr')] = inp.value;
            });
        }
        
        await doUpdate(type, data, field);
    }

    // Save passport field
    async function savePassportField(btn) {
        const field = btn.closest('.editable-field');
        const fieldKey = field.getAttribute('data-pp-field');
        const input = field.querySelector('[data-pp-value]');
        const data = { [fieldKey]: input.value };
        
        await doUpdate('passport_info', data, field);
    }

    // Save emergency contact field
    async function saveEmergencyField(btn) {
        const field = btn.closest('.editable-field');
        const fieldKey = field.getAttribute('data-emergency-field');
        const input = field.querySelector('[data-emergency-value]');
        const data = { emergency_contact: { [fieldKey]: input.value } };
        
        await doUpdate('passport_info', data, field);
    }

    // Save MRZ
    async function saveMRZ(btn) {
        const field = btn.closest('.editable-field');
        const data = {
            mrz_line_1: field.querySelector('[data-mrz="mrz_line_1"]').value,
            mrz_line_2: field.querySelector('[data-mrz="mrz_line_2"]').value
        };
        
        await doUpdate('passport_info', data, field);
    }

    // Save NID field
    async function saveNidField(btn) {
        const field = btn.closest('.editable-field');
        const fieldKey = field.getAttribute('data-nid-field');
        const input = field.querySelector('[data-nid-value]');
        const data = { [fieldKey]: input.value };
        
        await doUpdate('nid_info', data, field);
    }

    // Generic update function
    async function doUpdate(category, data, field) {
        try {
            const response = await fetch(UPDATE_API, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    traveler_id: TRAVELER_ID,
                    category: category,
                    data: data
                })
            });
            
            const result = await response.json();
            
            if (result.success) {
                // Hide edit mode, show display mode
                field.classList.remove('editing');
                const display = field.querySelector('.field-display');
                const input = field.querySelector('.field-input');
                if (display) display.classList.remove('hidden');
                if (input) input.classList.add('hidden');
                
                // Update display based on category
                if (category === 'address') {
                    const displayContainer = field.querySelector('.field-display .text-sm');
                    if (displayContainer) {
                        let html = '';
                        if (data.address_line_1) {
                            html += `<div class="flex items-start gap-2">
                                <span class="text-xs text-gray-400 w-20 flex-shrink-0">Line 1:</span>
                                <span class="field-value flex-1" onclick="copyToClipboard(this, '${escapeHtml(data.address_line_1)}')">${escapeHtml(data.address_line_1)}</span>
                            </div>`;
                        }
                        if (data.address_line_2) {
                            html += `<div class="flex items-start gap-2">
                                <span class="text-xs text-gray-400 w-20 flex-shrink-0">Line 2:</span>
                                <span class="field-value flex-1" onclick="copyToClipboard(this, '${escapeHtml(data.address_line_2)}')">${escapeHtml(data.address_line_2)}</span>
                            </div>`;
                        }
                        html += `<div class="flex items-start gap-2">
                            <span class="text-xs text-gray-400 w-20 flex-shrink-0">City:</span>
                            <span class="field-value flex-1" onclick="copyToClipboard(this, '${escapeHtml(data.city || '')}')">${data.city || '<span class="text-gray-400 italic">-</span>'}</span>
                        </div>`;
                        html += `<div class="flex items-start gap-2">
                            <span class="text-xs text-gray-400 w-20 flex-shrink-0">State:</span>
                            <span class="field-value flex-1" onclick="copyToClipboard(this, '${escapeHtml(data.state || '')}')">${data.state || '<span class="text-gray-400 italic">-</span>'}</span>
                        </div>`;
                        html += `<div class="flex items-start gap-2">
                            <span class="text-xs text-gray-400 w-20 flex-shrink-0">ZIP:</span>
                            <span class="field-value flex-1" onclick="copyToClipboard(this, '${escapeHtml(data.zip_code || '')}')">${data.zip_code || '<span class="text-gray-400 italic">-</span>'}</span>
                        </div>`;
                        html += `<div class="flex items-start gap-2">
                            <span class="text-xs text-gray-400 w-20 flex-shrink-0">Country:</span>
                            <span class="field-value flex-1" onclick="copyToClipboard(this, '${escapeHtml(data.country || '')}')">${data.country || '<span class="text-gray-400 italic">-</span>'}</span>
                        </div>`;
                        
                        if (!data.address_line_1 && !data.city && !data.state && !data.zip_code && !data.country) {
                            html = '<span class="text-gray-400 italic text-sm">No address provided. Click edit icon to add.</span>';
                        }
                        
                        displayContainer.innerHTML = html;
                    }
                } else if (category === 'phone') {
                    const displaySpan = field.querySelector('.field-display .field-value');
                    if (displaySpan) {
                        const newText = data.primary_no || 'Click to add phone';
                        displaySpan.innerHTML = newText || '<span class="text-gray-400 italic">Click to add phone</span>';
                        displaySpan.setAttribute('onclick', `copyToClipboard(this, '${escapeHtml(data.primary_no || '')}')`);
                    }
                } else if (category === 'email') {
                    const displaySpan = field.querySelector('.field-display .field-value');
                    if (displaySpan) {
                        const newText = data.primary || 'Click to add email';
                        displaySpan.innerHTML = newText || '<span class="text-gray-400 italic">Click to add email</span>';
                        displaySpan.setAttribute('onclick', `copyToClipboard(this, '${escapeHtml(data.primary || '')}')`);
                    }
                } else {
                    const displaySpan = field.querySelector('.field-display .field-value');
                    if (displaySpan) {
                        const values = Object.values(data).filter(v => typeof v === 'string');
                        const newText = values[0] || '-';
                        displaySpan.textContent = newText || '-';
                        displaySpan.setAttribute('onclick', `copyToClipboard(this, '${escapeHtml(newText || '')}')`);
                    }
                }
                
                showToast('Saved successfully!', 'success');
            } else {
                showToast('Failed: ' + (result.message || 'Unknown error'), 'error');
            }
        } catch (error) {
            showToast('Network error: ' + error.message, 'error');
        }
    }
    
    // helper function
    function escapeHtml(text) {
        if (!text) return '';
        return String(text)
            .replace(/'/g, "\\'")
            .replace(/"/g, '&quot;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;');
    }

    // Add secondary phone
    function addSecondaryPhone(btn) {
        const container = btn.parentElement.previousElementSibling;
        const div = document.createElement('div');
        div.className = 'flex items-center gap-2';
        div.innerHTML = `
            <select class="px-2 py-1 border border-gray-300 rounded text-xs">
                <option value="mobile">Mobile</option>
                <option value="home">Home</option>
                <option value="work">Work</option>
                <option value="other">Other</option>
            </select>
            <input type="text" class="flex-1 px-2 py-1 border border-gray-300 rounded text-xs" placeholder="Phone number">
            <button type="button" onclick="this.parentElement.remove()" class="text-red-500 hover:text-red-700 text-xs"><i class="fas fa-times"></i></button>
        `;
        container.appendChild(div);
    }

    // Add secondary email
    function addSecondaryEmail(btn) {
        const container = btn.parentElement.previousElementSibling;
        const div = document.createElement('div');
        div.className = 'flex items-center gap-2';
        div.innerHTML = `
            <select class="px-2 py-1 border border-gray-300 rounded text-xs">
                <option value="work">Work</option>
                <option value="personal">Personal</option>
                <option value="other">Other</option>
            </select>
            <input type="email" class="flex-1 px-2 py-1 border border-gray-300 rounded text-xs" placeholder="Email address">
            <button type="button" onclick="this.parentElement.remove()" class="text-red-500 hover:text-red-700 text-xs"><i class="fas fa-times"></i></button>
        `;
        container.appendChild(div);
    }

    // Toast notification
    function showToast(message, type) {
        const existing = document.querySelector('.toast-notification');
        if (existing) existing.remove();
        
        const toast = document.createElement('div');
        toast.className = `toast-notification fixed bottom-4 right-4 px-4 py-2 rounded-lg text-white text-sm z-50 shadow-lg ${
            type === 'success' ? 'bg-green-600' : 'bg-red-600'
        }`;
        toast.textContent = message;
        document.body.appendChild(toast);
        
        setTimeout(() => {
            toast.style.opacity = '0';
            toast.style.transition = 'opacity 0.3s';
            setTimeout(() => toast.remove(), 300);
        }, 2500);
    }
</script>