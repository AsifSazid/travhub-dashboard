    <!-- 3 Column Layout -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Column 1: Personal Information -->
        <div class="space-y-4">
            <h3 class="text-lg font-semibold text-gray-800 border-b pb-2">Personal Information</h3>

            <div>
                <label for="fullName" class="block text-sm font-medium text-gray-700 mb-1">
                    Name <span class="text-red-500">*</span>
                </label>
                <input type="text" id="fullName" name="full_name"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                    placeholder="John" required>
                <p class="text-xs text-gray-500 mt-1">Full Name</p>
            </div>

            <h3 class="text-lg font-semibold text-gray-800 border-b pb-2">Contact Information</h3>

            <!-- Primary Phone -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    Primary Phone <span class="text-red-500">*</span>
                </label>
                <div class="flex gap-2">
                    <input type="tel" id="primaryPhone" name="primary_phone"
                        class="flex-grow px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                        placeholder="+1 (555) 123-4567" required>
                </div>
                <p class="text-xs text-gray-500 mt-1">This will be saved as primary phone</p>
            </div>

            <!-- Secondary Phones -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    Secondary Phone Numbers
                </label>
                <div id="secondaryPhoneContainer" class="space-y-2">
                    <!-- Secondary phones will be added here -->
                </div>
                <button type="button" onclick="addSecondaryPhone()" class="mt-2 text-sm text-blue-600 hover:text-blue-800 flex items-center">
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    Add Secondary Phone
                </button>
            </div>

            <!-- Primary Email -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    Primary Email <span class="text-red-500">*</span>
                </label>
                <div class="flex gap-2">
                    <input type="email" id="primaryEmail" name="primary_email"
                        class="flex-grow px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                        placeholder="john@example.com" required>
                </div>
                <p class="text-xs text-gray-500 mt-1">This will be saved as primary email</p>
            </div>

            <!-- Secondary Emails -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    Secondary Email Addresses
                </label>
                <div id="secondaryEmailContainer" class="space-y-2">
                    <!-- Secondary emails will be added here -->
                </div>
                <button type="button" onclick="addSecondaryEmail()" class="mt-2 text-sm text-blue-600 hover:text-blue-800 flex items-center">
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    Add Secondary Email
                </button>
            </div>
        </div>

        <!-- Column 3: Address Information -->
        <div class="space-y-4 lg:col-span-1">
            <h3 class="text-lg font-semibold text-gray-800 border-b pb-2">Address Information</h3>

            <div>
                <label for="addressLine1" class="block text-sm font-medium text-gray-700 mb-1">
                    Address Line 1
                </label>
                <input type="text" id="addressLine1" name="address_line_1"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                    placeholder="Street address, P.O. box">
            </div>

            <div>
                <label for="addressLine2" class="block text-sm font-medium text-gray-700 mb-1">
                    Address Line 2
                </label>
                <input type="text" id="addressLine2" name="address_line_2"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                    placeholder="Apartment, suite, unit, building, floor">
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label for="city" class="block text-sm font-medium text-gray-700 mb-1">
                        City
                    </label>
                    <input type="text" id="city" name="city"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                        placeholder="City">
                </div>

                <div>
                    <label for="state" class="block text-sm font-medium text-gray-700 mb-1">
                        State/Province
                    </label>
                    <input type="text" id="state" name="state"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                        placeholder="State">
                </div>
            </div>

            <div>
                <label for="zipCode" class="block text-sm font-medium text-gray-700 mb-1">
                    ZIP/Postal Code
                </label>
                <input type="text" id="zipCode" name="zip_code"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                    placeholder="ZIP or Postal Code">
            </div>
        </div>
    </div>