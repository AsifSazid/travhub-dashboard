<?php
$ip_port = @file_get_contents('../ippath.txt');
if (empty($ip_port)) {
    $ip_port = "http://103.104.219.3:898";
}

$storeClientApi = $ip_port . "api/clients/store.php";

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Complete Work Entry</title>
    <link rel="icon" type="image/png" href="../assets/images/logo/round-logo.png" sizes="16x16">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://unpkg.com/sortablejs@1.14.0/Sortable.min.js"></script>
    <link rel="stylesheet" href="../assets/css/style.css">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: {
                            50: '#eff6ff',
                            100: '#dbeafe',
                            500: '#3b82f6',
                            600: '#2563eb',
                            700: '#1d4ed8',
                        }
                    }
                }
            }
        }
    </script>
</head>

<body class="bg-gray-50 font-sans">
    <!-- Top Navigation -->
    <?php include '../elements/header.php'; ?>

    <!-- Sidebar -->
    <?php include '../elements/aside.php'; ?>

    <!-- Preview Modal -->
    <div id="previewModal" class="preview-modal">
        <div class="preview-content">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-semibold text-gray-800" id="previewTitle">File Preview</h3>
                <button onclick="closePreview()" class="text-gray-500 hover:text-gray-700 text-2xl">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div id="modalPreviewContent" class="p-4">
                <!-- Preview content will be loaded here -->
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <main id="mainContent" class="pt-16 pl-64 transition-all duration-300">
        <div class="p-6">
            <div class="grid grid-cols-6 gap-4">
                <div class="col-span-12 bg-white rounded-lg shadow p-4">
                    <!-- Header -->
                    <div class="mb-6 border-b pb-4">
                        <h1 class="text-2xl font-bold text-gray-800">Add New Client</h1>
                        <p class="text-gray-600 mt-1">Fill in the client details below</p>
                    </div>

                    <!-- Success/Error Messages (Initially Hidden) -->
                    <div id="messageContainer" class="hidden mb-6">
                        <div id="successMessage" class="hidden bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative" role="alert">
                            <span class="block sm:inline" id="successText"></span>
                        </div>
                        <div id="errorMessage" class="hidden bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
                            <span class="block sm:inline" id="errorText"></span>
                        </div>
                    </div>

                    <!-- Client Form -->
                    <form id="clientForm" class="space-y-6">
                        <!-- Client Type -->
                        <div>
                            <label for="clientType" class="block text-sm font-medium text-gray-700 mb-2">
                                Client Type <span class="text-red-500">*</span>
                            </label>
                            <div class="flex space-x-4">
                                <label class="inline-flex items-center">
                                    <input type="radio" name="type" value="general" class="form-radio h-4 w-4 text-blue-600" checked>
                                    <span class="ml-2 text-gray-700">General</span>
                                </label>
                                <label class="inline-flex items-center">
                                    <input type="radio" name="type" value="local_agent" class="form-radio h-4 w-4 text-blue-600">
                                    <span class="ml-2 text-gray-700">Local Agent</span>
                                </label>
                            </div>
                        </div>

                        <div>
                            <label for="vendorType" class="block text-sm font-medium text-gray-700 mb-2">
                                Vendor Type <span class="text-red-500">*</span>
                            </label>
                            <div id="vendorType" class="flex space-x-4">
                                <label class="inline-flex items-center">
                                    <input type="radio" name="is_vendor" value="1" class="form-radio h-4 w-4 text-blue-600">
                                    <span class="ml-2 text-gray-700">Is Vendor</span>
                                </label>
                                <label class="inline-flex items-center">
                                    <input type="radio" name="is_vendor" value="0" class="form-radio h-4 w-4 text-blue-600" checked>
                                    <span class="ml-2 text-gray-700">Not a Vendor</span>
                                </label>
                            </div>
                        </div>

                        <!-- 3 Column Layout -->
                        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                            <!-- Column 1: Personal Information -->
                            <div class="space-y-4">
                                <h3 class="text-lg font-semibold text-gray-800 border-b pb-2">Personal Information</h3>

                                <div>
                                    <label for="givenName" class="block text-sm font-medium text-gray-700 mb-1">
                                        Given Name <span class="text-red-500">*</span>
                                    </label>
                                    <input type="text" id="givenName" name="given_name"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                        placeholder="John" required>
                                    <p class="text-xs text-gray-500 mt-1">First/Given name</p>
                                </div>

                                <div>
                                    <label for="surName" class="block text-sm font-medium text-gray-700 mb-1">
                                        Surname <span class="text-red-500">*</span>
                                    </label>
                                    <input type="text" id="surName" name="sur_name"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                        placeholder="Doe" required>
                                    <p class="text-xs text-gray-500 mt-1">Last/Family name</p>
                                </div>

                                <div>
                                    <label for="position" class="block text-sm font-medium text-gray-700 mb-1">
                                        Position
                                    </label>
                                    <input type="text" id="position" name="position"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                        placeholder="e.g., Manager, Director">
                                </div>

                                <div>
                                    <label for="companyName" class="block text-sm font-medium text-gray-700 mb-1">
                                        Company Name
                                    </label>
                                    <input type="text" id="companyName" name="company"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                        placeholder="Company Ltd.">
                                </div>
                            </div>

                            <!-- Column 2: Contact Information -->
                            <div class="space-y-4">
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

                        <!-- Form Actions -->
                        <div class="flex justify-end space-x-3 pt-6 border-t">
                            <button type="button" onclick="resetForm()"
                                class="px-6 py-2 border border-gray-300 rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500">
                                Reset
                            </button>
                            <button type="submit"
                                class="px-6 py-2 border border-transparent rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                Add Client
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- <div class="p-6">
            <div class="grid grid-cols-6 gap-4">
                <div class="col-span-12 bg-white rounded-lg shadow p-4">
                    <h2 class="text-2xl font-semibold text-gray-800 mb-4">Latest Client Lists</h2>

                    <div class="overflow-x-auto table-container">
                        <table id="clientTable" class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Sl No</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Client Name</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Phone No</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Is Vendor</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Action</th>
                                </tr>
                            </thead>
                            <tbody id="clientTableBody" class="bg-white divide-y divide-gray-200">
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div> -->
    </main>

    <script src="../assets/js/script.js"></script>

    <script>
        const API_URL_FOR_CLIENT_STORE = "<?php echo $storeClientApi; ?>";
        const vendorType = "<?php echo $vendorType; ?>";

        // Secondary Phone Management
        function addSecondaryPhone() {
            const container = document.getElementById('secondaryPhoneContainer');
            const div = document.createElement('div');
            div.className = 'flex gap-2 secondary-phone-input';
            div.innerHTML = `
                <select name="secondary_phone_type[]" class="w-1/3 px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="mobile">Mobile</option>
                    <option value="home">Home</option>
                    <option value="work">Work</option>
                    <option value="other">Other</option>
                </select>
                <input type="tel" name="secondary_phone_number[]"
                    class="flex-grow px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                    placeholder="+1 (555) 987-6543">
                <button type="button" onclick="removeSecondaryPhone(this)" class="px-3 py-2 text-red-600 hover:text-red-800">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            `;
            container.appendChild(div);
        }

        function removeSecondaryPhone(button) {
            button.closest('.secondary-phone-input').remove();
        }

        // Secondary Email Management
        function addSecondaryEmail() {
            const container = document.getElementById('secondaryEmailContainer');
            const div = document.createElement('div');
            div.className = 'flex gap-2 secondary-email-input';
            div.innerHTML = `
                <select name="secondary_email_type[]" class="w-1/3 px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="work">Work</option>
                    <option value="personal">Personal</option>
                    <option value="other">Other</option>
                </select>
                <input type="email" name="secondary_email_address[]"
                    class="flex-grow px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                    placeholder="john.secondary@example.com">
                <button type="button" onclick="removeSecondaryEmail(this)" class="px-3 py-2 text-red-600 hover:text-red-800">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            `;
            container.appendChild(div);
        }

        function removeSecondaryEmail(button) {
            button.closest('.secondary-email-input').remove();
        }

        // Form Reset
        function resetForm() {
            if (confirm('Are you sure you want to reset the form? All entered data will be lost.')) {
                document.getElementById('clientForm').reset();
                // Reset secondary phone and email inputs
                const secondaryPhoneContainer = document.getElementById('secondaryPhoneContainer');
                const secondaryEmailContainer = document.getElementById('secondaryEmailContainer');

                // Clear all secondary phone inputs
                secondaryPhoneContainer.innerHTML = '';

                // Clear all secondary email inputs
                secondaryEmailContainer.innerHTML = '';

                showMessage('Form has been reset', 'success');
            }
        }

        // Form Submission
        document.getElementById('clientForm').addEventListener('submit', async function(e) {
            e.preventDefault();

            // Show loading state
            const submitBtn = this.querySelector('button[type="submit"]');
            const originalText = submitBtn.textContent;
            submitBtn.textContent = 'Adding...';
            submitBtn.disabled = true;

            // Collect form data
            const formData = new FormData(this);
            const data = {
                type: formData.get('type'),
                given_name: formData.get('given_name'),
                sur_name: formData.get('sur_name'),
                position: formData.get('position'),
                company: formData.get('company'),
                status: 'active',
                created_by: 'current_user' // Replace with actual user from session
            };

            // Collect phone information with type
            const primaryPhoneType = formData.get('primary_phone_type');
            const primaryPhone = formData.get('primary_phone');

            const secondaryPhoneTypes = formData.getAll('secondary_phone_type[]');
            const secondaryPhoneNumbers = formData.getAll('secondary_phone_number[]');

            // Format phone data as per requirement
            data.phone = {
                primary_no: primaryPhone,
                secondary_no: secondaryPhoneTypes.map((type, index) => ({
                    type: type,
                    number: secondaryPhoneNumbers[index]
                }))
            };

            // Collect email information with type
            const primaryEmailType = formData.get('primary_email_type');
            const primaryEmail = formData.get('primary_email');

            const secondaryEmailTypes = formData.getAll('secondary_email_type[]');
            const secondaryEmailAddresses = formData.getAll('secondary_email_address[]');

            // Format email data as per requirement
            data.email = {
                primary: primaryEmail,
                secondary: secondaryEmailTypes.map((type, index) => ({
                    type: type,
                    address: secondaryEmailAddresses[index]
                }))
            };

            // Collect address
            data.address = {
                address_line_1: formData.get('address_line_1'),
                address_line_2: formData.get('address_line_2'),
                city: formData.get('city'),
                state: formData.get('state'),
                zip_code: formData.get('zip_code')
            };

            console.log('Data to send:', data); // For debugging

            // Send to server
            try {
                const response = await fetch(API_URL_FOR_CLIENT_STORE, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify(data)
                });

                const result = await response.json();

                if (result.success) {
                    showMessage('Client added successfully!', 'success');
                    // Reset form after successful submission
                    setTimeout(() => {
                        resetForm();
                    }, 1500);
                } else {
                    showMessage(result.message || 'Failed to add client', 'error');
                }
            } catch (error) {
                showMessage('Network error: ' + error.message, 'error');
            } finally {
                // Reset button state
                submitBtn.textContent = originalText;
                submitBtn.disabled = false;
            }
        });

        // Show Messages
        function showMessage(message, type) {
            const container = document.getElementById('messageContainer');
            const successDiv = document.getElementById('successMessage');
            const errorDiv = document.getElementById('errorMessage');

            container.classList.remove('hidden');

            if (type === 'success') {
                successDiv.classList.remove('hidden');
                errorDiv.classList.add('hidden');
                document.getElementById('successText').textContent = message;
            } else {
                errorDiv.classList.remove('hidden');
                successDiv.classList.add('hidden');
                document.getElementById('errorText').textContent = message;
            }

            // Auto-hide after 5 seconds
            setTimeout(() => {
                container.classList.add('hidden');
            }, 5000);
        }


        // Client
        const tableBody = document.getElementById('clientTableBody');

        let clientsData = [];
        fetch(API_URL_FOR_ALL_CLIENTS)
            .then(res => res.json())
            .then(data => {
                clientsData = data.clients;
                renderDropdown(clientsData);
            })
            .catch(err => console.error(err));

        function renderDropdown(list) {
            // আগের ডাটা মুছে ফেলা
            tableBody.innerHTML = '';

            list.forEach(client => {
                const tr = document.createElement('tr');
                tr.className = "hover:bg-gray-50";

                // টেবিল রো (Row) এর ভেতরে কলামগুলো তৈরি করা
                tr.innerHTML = `
                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">${client.id || 'N/A'}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">${client.given_name || 'No Title'} ${client.sur_name}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">${client || 'Unknown'}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${client || 'Folder'}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                    <a href="completed-task-entry.php?work_id=${client.id}" title="Tasks">
                        <i class="fas fa-tasks"></i>
                    </a>
                </td>
            `;

                tableBody.appendChild(tr);
            });
        }
    </script>
</body>

</html>