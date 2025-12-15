<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/style.css">
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
    <?php include 'elements/header.php'; ?>

    <!-- Sidebar -->
    <?php include 'elements/aside.php'; ?>

    <!-- Main Content -->
    <main id="mainContent" class="pt-16 pl-64 transition-all duration-300">
        <div class="p-6">
            <!-- Lead Creation Form -->
            <div class="max-w-6xl mx-auto">
                <!-- Header -->
                <div class="mb-8">
                    <h1 class="text-3xl font-bold text-gray-800">Create New Lead</h1>
                    <p class="text-gray-600 mt-2">Fill out the form below to create a new lead in the system.</p>
                </div>

                <!-- Form Container -->
                <div class="bg-white rounded-xl shadow-lg p-6 mb-8">
                    <form id="leadForm">
                        <!-- Step 1: Is our Client? -->
                        <div class="mb-10">
                            <h2 class="text-xl font-semibold text-gray-800 mb-4 pb-2 border-b">1. Is our Client?</h2>
                            <div class="flex space-x-6">
                                <div class="flex items-center">
                                    <input type="radio" id="clientYes" name="isClient" value="yes" class="h-5 w-5 text-primary-600 focus:ring-primary-500" checked>
                                    <label for="clientYes" class="ml-3 block text-lg font-medium text-gray-700">
                                        <span class="flex items-center">
                                            <span class="h-8 w-8 rounded-full bg-green-100 flex items-center justify-center mr-3">
                                                <i class="fas fa-check text-green-600 text-sm"></i>
                                            </span>
                                            Client
                                        </span>
                                    </label>
                                </div>
                                <div class="flex items-center">
                                    <input type="radio" id="clientNo" name="isClient" value="no" class="h-5 w-5 text-primary-600 focus:ring-primary-500">
                                    <label for="clientNo" class="ml-3 block text-lg font-medium text-gray-700">
                                        <span class="flex items-center">
                                            <span class="h-8 w-8 rounded-full bg-red-100 flex items-center justify-center mr-3">
                                                <i class="fas fa-times text-red-600 text-sm"></i>
                                            </span>
                                            Not a Client
                                        </span>
                                    </label>
                                </div>
                            </div>
                        </div>

                        <!-- Client Section (Visible when Client = YES) -->
                        <div id="clientSection" class="mb-10">
                            <!-- Conversation Note -->
                            <div class="mb-8">
                                <label for="conversationNote" class="block text-lg font-medium text-gray-700 mb-3">Conversation Note</label>
                                <textarea id="conversationNote" name="conversationNote" rows="4" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent" placeholder="Enter notes from your conversation..."></textarea>
                            </div>

                            <!-- Work Priority -->
                            <div>
                                <h3 class="text-lg font-medium text-gray-700 mb-4">Work Priority</h3>
                                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                                    <div class="relative">
                                        <input type="radio" id="urgent" name="workPriority" value="urgent" class="hidden peer">
                                        <label for="urgent" class="flex flex-col items-center justify-center p-5 border-2 border-gray-200 rounded-xl cursor-pointer hover:border-primary-400 peer-checked:border-primary-500 peer-checked:bg-primary-50 transition-all duration-200">
                                            <span class="h-12 w-12 rounded-full bg-red-100 flex items-center justify-center mb-3">
                                                <i class="fas fa-exclamation-triangle text-red-600 text-lg"></i>
                                            </span>
                                            <span class="font-medium text-gray-800">Urgent</span>
                                            <span class="text-sm text-gray-500 mt-1">Immediate attention</span>
                                        </label>
                                    </div>
                                    <div class="relative">
                                        <input type="radio" id="today" name="workPriority" value="today" class="hidden peer">
                                        <label for="today" class="flex flex-col items-center justify-center p-5 border-2 border-gray-200 rounded-xl cursor-pointer hover:border-primary-400 peer-checked:border-primary-500 peer-checked:bg-primary-50 transition-all duration-200">
                                            <span class="h-12 w-12 rounded-full bg-orange-100 flex items-center justify-center mb-3">
                                                <i class="fas fa-calendar-day text-orange-600 text-lg"></i>
                                            </span>
                                            <span class="font-medium text-gray-800">Must within Today</span>
                                            <span class="text-sm text-gray-500 mt-1">Complete by EOD</span>
                                        </label>
                                    </div>
                                    <div class="relative">
                                        <input type="radio" id="easy" name="workPriority" value="easy" class="hidden peer" checked>
                                        <label for="easy" class="flex flex-col items-center justify-center p-5 border-2 border-gray-200 rounded-xl cursor-pointer hover:border-primary-400 peer-checked:border-primary-500 peer-checked:bg-primary-50 transition-all duration-200">
                                            <span class="h-12 w-12 rounded-full bg-green-100 flex items-center justify-center mb-3">
                                                <i class="fas fa-check-circle text-green-600 text-lg"></i>
                                            </span>
                                            <span class="font-medium text-gray-800">Easy</span>
                                            <span class="text-sm text-gray-500 mt-1">Low effort required</span>
                                        </label>
                                    </div>
                                    <div class="relative">
                                        <input type="radio" id="research" name="workPriority" value="research" class="hidden peer">
                                        <label for="research" class="flex flex-col items-center justify-center p-5 border-2 border-gray-200 rounded-xl cursor-pointer hover:border-primary-400 peer-checked:border-primary-500 peer-checked:bg-primary-50 transition-all duration-200">
                                            <span class="h-12 w-12 rounded-full bg-blue-100 flex items-center justify-center mb-3">
                                                <i class="fas fa-search text-blue-600 text-lg"></i>
                                            </span>
                                            <span class="font-medium text-gray-800">Research First</span>
                                            <span class="text-sm text-gray-500 mt-1">Need more information</span>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Non-Client Section (Hidden by default) -->
                        <div id="nonClientSection" class="hidden mb-10">
                            <h2 class="text-xl font-semibold text-gray-800 mb-6 pb-2 border-b">Client Details</h2>

                            <!-- Name -->
                            <div class="mb-6">
                                <label for="clientName" class="block text-lg font-medium text-gray-700 mb-3">Name</label>
                                <input type="text" id="clientName" name="clientName" class="w-full md:w-1/2 px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent" placeholder="Enter full name">
                            </div>

                            <!-- Phone Numbers (Repeatable) -->
                            <div class="mb-6">
                                <div class="flex justify-between items-center mb-3">
                                    <label class="block text-lg font-medium text-gray-700">Phone Numbers</label>
                                    <button type="button" id="addPhoneBtn" class="text-primary-600 hover:text-primary-800 font-medium flex items-center">
                                        <i class="fas fa-plus-circle mr-2"></i> Add More Numbers
                                    </button>
                                </div>
                                
                                <div id="phoneFieldsContainer">
                                    <div class="phone-field flex flex-col md:flex-row gap-4 mb-4">
                                        <div class="flex-1">
                                            <select name="phoneType[]" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent">
                                                <option value="whatsapp">WhatsApp</option>
                                                <option value="primary">Primary</option>
                                                <option value="secondary">Secondary</option>
                                                <option value="others">Others</option>
                                            </select>
                                        </div>
                                        <div class="flex-1">
                                            <input type="tel" name="phoneNumber[]" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent" placeholder="Enter phone number">
                                        </div>
                                        <div class="md:w-24 flex items-center">
                                            <button type="button" class="remove-phone-btn text-red-600 hover:text-red-800 p-2 rounded-lg hover:bg-red-50 hidden">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Emails (Repeatable) -->
                            <div class="mb-6">
                                <div class="flex justify-between items-center mb-3">
                                    <label class="block text-lg font-medium text-gray-700">Email Addresses</label>
                                    <button type="button" id="addEmailBtn" class="text-primary-600 hover:text-primary-800 font-medium flex items-center">
                                        <i class="fas fa-plus-circle mr-2"></i> Add More Emails
                                    </button>
                                </div>
                                
                                <div id="emailFieldsContainer">
                                    <div class="email-field flex mb-4">
                                        <div class="flex-1">
                                            <input type="email" name="email[]" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent" placeholder="Enter email address">
                                        </div>
                                        <div class="w-24 flex items-center">
                                            <button type="button" class="remove-email-btn text-red-600 hover:text-red-800 p-2 rounded-lg hover:bg-red-50 hidden">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Position -->
                            <div class="mb-6">
                                <label for="position" class="block text-lg font-medium text-gray-700 mb-3">Position</label>
                                <input type="text" id="position" name="position" class="w-full md:w-1/2 px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent" placeholder="Enter job position">
                            </div>

                            <!-- Address Section -->
                            <div class="mb-8">
                                <h3 class="text-lg font-medium text-gray-700 mb-4">Address</h3>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div>
                                        <label for="address1" class="block text-gray-700 mb-2">Address Line 1</label>
                                        <input type="text" id="address1" name="address1" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent">
                                    </div>
                                    <div>
                                        <label for="address2" class="block text-gray-700 mb-2">Address Line 2</label>
                                        <input type="text" id="address2" name="address2" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent">
                                    </div>
                                    <div>
                                        <label for="city" class="block text-gray-700 mb-2">City</label>
                                        <input type="text" id="city" name="city" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent">
                                    </div>
                                    <div>
                                        <label for="state" class="block text-gray-700 mb-2">State</label>
                                        <input type="text" id="state" name="state" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent">
                                    </div>
                                    <div>
                                        <label for="zip" class="block text-gray-700 mb-2">ZIP Code</label>
                                        <input type="text" id="zip" name="zip" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent">
                                    </div>
                                    <div>
                                        <label for="country" class="block text-gray-700 mb-2">Country</label>
                                        <select id="country" name="country" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent">
                                            <option value="us">United States</option>
                                            <option value="uk">United Kingdom</option>
                                            <option value="ca">Canada</option>
                                            <option value="au">Australia</option>
                                            <option value="in">India</option>
                                            <option value="de">Germany</option>
                                            <option value="fr">France</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <!-- Company Name -->
                            <div class="mb-6">
                                <label for="companyName" class="block text-lg font-medium text-gray-700 mb-3">Company Name</label>
                                <input type="text" id="companyName" name="companyName" class="w-full md:w-1/2 px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent" placeholder="Enter company name">
                            </div>

                            <!-- Is Vendor (Toggle) -->
                            <div class="mb-8">
                                <div class="flex items-center">
                                    <span class="text-lg font-medium text-gray-700 mr-4">Is Vendor</span>
                                    <label for="isVendor" class="inline-flex relative items-center cursor-pointer">
                                        <input type="checkbox" id="isVendor" name="isVendor" class="sr-only peer">
                                        <div class="w-14 h-7 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-0.5 after:left-[4px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-6 after:w-6 after:transition-all peer-checked:bg-primary-600"></div>
                                    </label>
                                    <span id="vendorStatus" class="ml-3 font-medium text-gray-600">No</span>
                                </div>
                            </div>

                            <!-- Social Media Handles (Repeatable) -->
                            <div class="mb-10">
                                <div class="flex justify-between items-center mb-3">
                                    <h3 class="text-lg font-medium text-gray-700">Social Media Handles</h3>
                                    <button type="button" id="addSocialBtn" class="text-primary-600 hover:text-primary-800 font-medium flex items-center">
                                        <i class="fas fa-plus-circle mr-2"></i> Add More
                                    </button>
                                </div>
                                
                                <div id="socialFieldsContainer">
                                    <div class="social-field flex flex-col md:flex-row gap-4 mb-4">
                                        <div class="flex-1">
                                            <select name="socialPlatform[]" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent">
                                                <option value="facebook">Facebook</option>
                                                <option value="twitter">Twitter</option>
                                                <option value="linkedin">LinkedIn</option>
                                                <option value="instagram">Instagram</option>
                                                <option value="youtube">YouTube</option>
                                                <option value="tiktok">TikTok</option>
                                            </select>
                                        </div>
                                        <div class="flex-1">
                                            <input type="text" name="socialUsername[]" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent" placeholder="Username or URL">
                                        </div>
                                        <div class="md:w-24 flex items-center">
                                            <button type="button" class="remove-social-btn text-red-600 hover:text-red-800 p-2 rounded-lg hover:bg-red-50 hidden">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Non-Client Conversation Note -->
                            <div class="mb-8">
                                <label for="nonClientConversationNote" class="block text-lg font-medium text-gray-700 mb-3">Conversation Note</label>
                                <textarea id="nonClientConversationNote" name="nonClientConversationNote" rows="4" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent" placeholder="Enter notes from your conversation..."></textarea>
                            </div>

                            <!-- Non-Client Work Priority -->
                            <div class="mb-8">
                                <h3 class="text-lg font-medium text-gray-700 mb-4">Work Priority</h3>
                                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                                    <div class="relative">
                                        <input type="radio" id="nonClientUrgent" name="nonClientWorkPriority" value="urgent" class="hidden peer">
                                        <label for="nonClientUrgent" class="flex flex-col items-center justify-center p-5 border-2 border-gray-200 rounded-xl cursor-pointer hover:border-primary-400 peer-checked:border-primary-500 peer-checked:bg-primary-50 transition-all duration-200">
                                            <span class="h-12 w-12 rounded-full bg-red-100 flex items-center justify-center mb-3">
                                                <i class="fas fa-exclamation-triangle text-red-600 text-lg"></i>
                                            </span>
                                            <span class="font-medium text-gray-800">Urgent</span>
                                            <span class="text-sm text-gray-500 mt-1">Immediate attention</span>
                                        </label>
                                    </div>
                                    <div class="relative">
                                        <input type="radio" id="nonClientToday" name="nonClientWorkPriority" value="today" class="hidden peer">
                                        <label for="nonClientToday" class="flex flex-col items-center justify-center p-5 border-2 border-gray-200 rounded-xl cursor-pointer hover:border-primary-400 peer-checked:border-primary-500 peer-checked:bg-primary-50 transition-all duration-200">
                                            <span class="h-12 w-12 rounded-full bg-orange-100 flex items-center justify-center mb-3">
                                                <i class="fas fa-calendar-day text-orange-600 text-lg"></i>
                                            </span>
                                            <span class="font-medium text-gray-800">Must within Today</span>
                                            <span class="text-sm text-gray-500 mt-1">Complete by EOD</span>
                                        </label>
                                    </div>
                                    <div class="relative">
                                        <input type="radio" id="nonClientEasy" name="nonClientWorkPriority" value="easy" class="hidden peer" checked>
                                        <label for="nonClientEasy" class="flex flex-col items-center justify-center p-5 border-2 border-gray-200 rounded-xl cursor-pointer hover:border-primary-400 peer-checked:border-primary-500 peer-checked:bg-primary-50 transition-all duration-200">
                                            <span class="h-12 w-12 rounded-full bg-green-100 flex items-center justify-center mb-3">
                                                <i class="fas fa-check-circle text-green-600 text-lg"></i>
                                            </span>
                                            <span class="font-medium text-gray-800">Easy</span>
                                            <span class="text-sm text-gray-500 mt-1">Low effort required</span>
                                        </label>
                                    </div>
                                    <div class="relative">
                                        <input type="radio" id="nonClientResearch" name="nonClientWorkPriority" value="research" class="hidden peer">
                                        <label for="nonClientResearch" class="flex flex-col items-center justify-center p-5 border-2 border-gray-200 rounded-xl cursor-pointer hover:border-primary-400 peer-checked:border-primary-500 peer-checked:bg-primary-50 transition-all duration-200">
                                            <span class="h-12 w-12 rounded-full bg-blue-100 flex items-center justify-center mb-3">
                                                <i class="fas fa-search text-blue-600 text-lg"></i>
                                            </span>
                                            <span class="font-medium text-gray-800">Research First</span>
                                            <span class="text-sm text-gray-500 mt-1">Need more information</span>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Submit Button -->
                        <div class="pt-6 border-t flex justify-end">
                            <button type="submit" class="px-8 py-3 bg-primary-600 hover:bg-primary-700 text-white font-medium rounded-lg shadow-md hover:shadow-lg transition-all duration-300 flex items-center">
                                <i class="fas fa-plus-circle mr-3"></i> Create Lead
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </main>

    <!-- Floating Quick Access Tab -->
    <?php include 'elements/floating-menus.php'; ?>

    <!-- Custom JavaScript Library -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Toggle between Client and Non-Client sections
            const clientYes = document.getElementById('clientYes');
            const clientNo = document.getElementById('clientNo');
            const clientSection = document.getElementById('clientSection');
            const nonClientSection = document.getElementById('nonClientSection');
            
            function toggleClientSections() {
                if (clientYes.checked) {
                    clientSection.classList.remove('hidden');
                    nonClientSection.classList.add('hidden');
                } else {
                    clientSection.classList.add('hidden');
                    nonClientSection.classList.remove('hidden');
                }
            }
            
            clientYes.addEventListener('change', toggleClientSections);
            clientNo.addEventListener('change', toggleClientSections);
            
            // Initialize
            toggleClientSections();
            
            // Toggle switch for Is Vendor
            const isVendorToggle = document.getElementById('isVendor');
            const vendorStatus = document.getElementById('vendorStatus');
            
            isVendorToggle.addEventListener('change', function() {
                vendorStatus.textContent = this.checked ? 'Yes' : 'No';
            });
            
            // Phone number fields - Add more
            const addPhoneBtn = document.getElementById('addPhoneBtn');
            const phoneFieldsContainer = document.getElementById('phoneFieldsContainer');
            let phoneFieldCount = 1;
            
            addPhoneBtn.addEventListener('click', function() {
                phoneFieldCount++;
                const newPhoneField = document.createElement('div');
                newPhoneField.className = 'phone-field flex flex-col md:flex-row gap-4 mb-4';
                newPhoneField.innerHTML = `
                    <div class="flex-1">
                        <select name="phoneType[]" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent">
                            <option value="whatsapp">WhatsApp</option>
                            <option value="secondary">Secondary</option>
                            <option value="others">Others</option>
                        </select>
                    </div>
                    <div class="flex-1">
                        <input type="tel" name="phoneNumber[]" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent" placeholder="Enter phone number">
                    </div>
                    <div class="md:w-24 flex items-center">
                        <button type="button" class="remove-phone-btn text-red-600 hover:text-red-800 p-2 rounded-lg hover:bg-red-50">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                `;
                
                phoneFieldsContainer.appendChild(newPhoneField);
                
                // Show remove buttons on all phone fields except the first one
                updatePhoneRemoveButtons();
            });
            
            // Email fields - Add more
            const addEmailBtn = document.getElementById('addEmailBtn');
            const emailFieldsContainer = document.getElementById('emailFieldsContainer');
            let emailFieldCount = 1;
            
            addEmailBtn.addEventListener('click', function() {
                emailFieldCount++;
                const newEmailField = document.createElement('div');
                newEmailField.className = 'email-field flex mb-4';
                newEmailField.innerHTML = `
                    <div class="flex-1">
                        <input type="email" name="email[]" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent" placeholder="Enter email address">
                    </div>
                    <div class="w-24 flex items-center">
                        <button type="button" class="remove-email-btn text-red-600 hover:text-red-800 p-2 rounded-lg hover:bg-red-50">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                `;
                
                emailFieldsContainer.appendChild(newEmailField);
                
                // Show remove buttons on all email fields except the first one
                updateEmailRemoveButtons();
            });
            
            // Social media fields - Add more
            const addSocialBtn = document.getElementById('addSocialBtn');
            const socialFieldsContainer = document.getElementById('socialFieldsContainer');
            let socialFieldCount = 1;
            
            addSocialBtn.addEventListener('click', function() {
                socialFieldCount++;
                const newSocialField = document.createElement('div');
                newSocialField.className = 'social-field flex flex-col md:flex-row gap-4 mb-4';
                newSocialField.innerHTML = `
                    <div class="flex-1">
                        <select name="socialPlatform[]" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent">
                            <option value="facebook">Facebook</option>
                            <option value="twitter">Twitter</option>
                            <option value="linkedin">LinkedIn</option>
                            <option value="instagram">Instagram</option>
                            <option value="youtube">YouTube</option>
                            <option value="tiktok">TikTok</option>
                        </select>
                    </div>
                    <div class="flex-1">
                        <input type="text" name="socialUsername[]" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent" placeholder="Username or URL">
                    </div>
                    <div class="md:w-24 flex items-center">
                        <button type="button" class="remove-social-btn text-red-600 hover:text-red-800 p-2 rounded-lg hover:bg-red-50">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                `;
                
                socialFieldsContainer.appendChild(newSocialField);
                
                // Show remove buttons on all social fields except the first one
                updateSocialRemoveButtons();
            });
            
            // Update remove buttons visibility for phone fields
            function updatePhoneRemoveButtons() {
                const phoneFields = document.querySelectorAll('.phone-field');
                phoneFields.forEach((field, index) => {
                    const removeBtn = field.querySelector('.remove-phone-btn');
                    if (phoneFields.length > 1) {
                        removeBtn.classList.remove('hidden');
                    } else {
                        removeBtn.classList.add('hidden');
                    }
                    
                    // Remove the field when remove button is clicked
                    removeBtn.addEventListener('click', function() {
                        if (phoneFields.length > 1) {
                            field.remove();
                            updatePhoneRemoveButtons();
                        }
                    });
                });
            }
            
            // Update remove buttons visibility for email fields
            function updateEmailRemoveButtons() {
                const emailFields = document.querySelectorAll('.email-field');
                emailFields.forEach((field, index) => {
                    const removeBtn = field.querySelector('.remove-email-btn');
                    if (emailFields.length > 1) {
                        removeBtn.classList.remove('hidden');
                    } else {
                        removeBtn.classList.add('hidden');
                    }
                    
                    // Remove the field when remove button is clicked
                    removeBtn.addEventListener('click', function() {
                        if (emailFields.length > 1) {
                            field.remove();
                            updateEmailRemoveButtons();
                        }
                    });
                });
            }
            
            // Update remove buttons visibility for social fields
            function updateSocialRemoveButtons() {
                const socialFields = document.querySelectorAll('.social-field');
                socialFields.forEach((field, index) => {
                    const removeBtn = field.querySelector('.remove-social-btn');
                    if (socialFields.length > 1) {
                        removeBtn.classList.remove('hidden');
                    } else {
                        removeBtn.classList.add('hidden');
                    }
                    
                    // Remove the field when remove button is clicked
                    removeBtn.addEventListener('click', function() {
                        if (socialFields.length > 1) {
                            field.remove();
                            updateSocialRemoveButtons();
                        }
                    });
                });
            }
            
            // Initialize remove buttons
            updatePhoneRemoveButtons();
            updateEmailRemoveButtons();
            updateSocialRemoveButtons();
            
            // Form submission
            const leadForm = document.getElementById('leadForm');
            
            leadForm.addEventListener('submit', function(e) {
                e.preventDefault();
                
                // Simple validation
                let isValid = true;
                const requiredFields = [];
                
                if (clientYes.checked) {
                    // Client validation
                    const conversationNote = document.getElementById('conversationNote');
                    if (!conversationNote.value.trim()) {
                        isValid = false;
                        conversationNote.classList.add('border-red-500');
                    } else {
                        conversationNote.classList.remove('border-red-500');
                    }
                } else {
                    // Non-client validation
                    const clientName = document.getElementById('clientName');
                    if (!clientName.value.trim()) {
                        isValid = false;
                        clientName.classList.add('border-red-500');
                    } else {
                        clientName.classList.remove('border-red-500');
                    }
                }
                
                if (isValid) {
                    // Form is valid, show success message
                    alert('Lead created successfully!');
                    // In a real application, you would submit the form data to a server here
                    // leadForm.submit();
                } else {
                    alert('Please fill in all required fields.');
                }
            });
            
            // Remove red border when user starts typing in a field
            const allInputs = document.querySelectorAll('input, textarea, select');
            allInputs.forEach(input => {
                input.addEventListener('input', function() {
                    this.classList.remove('border-red-500');
                });
            });
        });
    </script>
</body>
</html>