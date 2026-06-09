<?php
include_once('./authenticate.php');
$ip_port = @file_get_contents('../ippath.txt');
if (empty($ip_port)) {
    $ip_port = "http://103.104.219.3:899";
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Complete Hotel Booking System - Multi-Segment Data Entry</title>
    <link rel="icon" type="image/png" href="../assets/images/logo/round-logo.png" sizes="16x16">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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
    <style>
        .hotel-form {
            position: relative;
            transition: all 0.3s ease;
        }
        .remove-hotel-btn {
            position: absolute;
            top: 15px;
            right: 15px;
            opacity: 0.7;
            transition: opacity 0.2s;
        }
        .remove-hotel-btn:hover {
            opacity: 1;
        }
        .segment-counter {
            min-width: 24px;
            height: 24px;
        }
    </style>
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
            <!-- Success Toast -->
            <div id="successToast" class="hidden fixed top-24 right-6 z-50 bg-green-500 text-white px-6 py-3 rounded-lg shadow-lg transition-all duration-300">
                <div class="flex items-center">
                    <i class="fas fa-check-circle mr-2"></i>
                    <span id="toastMessage"></span>
                </div>
            </div>

            <!-- Error Toast -->
            <div id="errorToast" class="hidden fixed top-24 right-6 z-50 bg-red-500 text-white px-6 py-3 rounded-lg shadow-lg transition-all duration-300">
                <div class="flex items-center">
                    <i class="fas fa-exclamation-circle mr-2"></i>
                    <span id="errorToastMessage"></span>
                </div>
            </div>

            <!-- Loading Overlay -->
            <div id="loadingOverlay" class="hidden fixed inset-0 bg-black bg-opacity-50 z-40 flex items-center justify-center">
                <div class="bg-white p-8 rounded-lg shadow-xl text-center">
                    <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-500 mx-auto mb-4"></div>
                    <p id="loadingText" class="text-gray-700 font-medium">Processing...</p>
                </div>
            </div>

            <div class="grid grid-cols-3 gap-6">
                <!-- Left: Hotel Forms Section -->
                <div class="col-span-2">
                    <div class="bg-white rounded-lg shadow p-6 mb-6">
                        <div class="flex justify-between items-center mb-6">
                            <div>
                                <h2 class="text-xl font-bold text-gray-800">Hotel Booking Data Entry</h2>
                                <p class="text-sm text-gray-600 mt-1">Add multiple hotels for the same tour/booking</p>
                            </div>
                            <div class="flex items-center space-x-4">
                                <div id="segmentCounter" class="bg-blue-100 text-blue-800 text-sm font-semibold px-3 py-1 rounded-full flex items-center">
                                    <i class="fas fa-hotel mr-1"></i>
                                    <span id="segmentCount">1</span> Hotel(s)
                                </div>
                                <button onclick="addNewHotelForm()" 
                                        class="bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 rounded-lg transition duration-300 flex items-center">
                                    <i class="fas fa-plus mr-2"></i> Add Another Hotel
                                </button>
                            </div>
                        </div>

                        <!-- Manual Data Entry Form -->
                        <div id="manualEntryForm" class="mb-8 p-6 border-2 border-dashed border-blue-200 rounded-lg bg-blue-50">
                            <h3 class="text-lg font-semibold text-gray-800 mb-4">
                                <i class="fas fa-keyboard text-blue-500 mr-2"></i> Manual Data Entry
                            </h3>
                            
                            <div class="grid grid-cols-2 gap-4 mb-6">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Booking Reference *</label>
                                    <input type="text" id="bookingRef" 
                                           class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                           placeholder="e.g., TOUR-2024-001">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Tour/Group Name</label>
                                    <input type="text" id="tourName" 
                                           class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                           placeholder="e.g., Europe Summer Tour 2024">
                                </div>
                            </div>
                            
                            <div class="flex items-center justify-between">
                                <div class="text-sm text-gray-600">
                                    <i class="fas fa-info-circle mr-1"></i>
                                    Add hotel details below using forms or extract from files
                                </div>
                                <button type="button" onclick="addNewHotelForm()"
                                        class="bg-green-600 hover:bg-green-700 text-white font-medium py-2 px-4 rounded-lg transition duration-300 flex items-center">
                                    <i class="fas fa-hotel mr-2"></i> Add First Hotel
                                </button>
                            </div>
                        </div>

                        <!-- Hotel Forms Container -->
                        <div id="hotelFormsContainer">
                            <!-- Hotel forms will be dynamically added here -->
                            <div id="noFormsMessage" class="text-center py-12 text-gray-500">
                                <i class="fas fa-hotel text-4xl mb-4 text-gray-300"></i>
                                <p class="text-lg">No hotel forms added yet</p>
                                <p class="text-sm mt-2">Click "Add First Hotel" button above or upload files to extract data</p>
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div id="actionButtons" class="hidden mt-8 pt-6 border-t border-gray-200">
                            <div class="flex space-x-4">
                                <button id="saveAllBtn" onclick="saveAllBookings()" 
                                    class="flex-1 bg-green-600 hover:bg-green-700 text-white font-semibold py-3 px-4 rounded-lg shadow transition duration-300 flex items-center justify-center">
                                    <i class="fas fa-save mr-2"></i> Save All Hotels
                                </button>
                                <button onclick="clearAllForms()" 
                                    class="flex-1 bg-gray-200 hover:bg-gray-300 text-gray-800 font-medium py-3 px-4 rounded-lg transition duration-300 flex items-center justify-center">
                                    <i class="fas fa-trash-alt mr-2"></i> Clear All Forms
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Saved Bookings Section -->
                    <div id="savedBookingsSection" class="bg-white rounded-lg shadow p-6">
                        <div class="flex justify-between items-center mb-6">
                            <h3 class="text-lg font-bold text-gray-800">Saved Bookings</h3>
                            <button onclick="loadSavedBookings()" 
                                    class="bg-blue-100 hover:bg-blue-200 text-blue-800 text-sm font-medium py-2 px-4 rounded-lg transition duration-300 flex items-center">
                                <i class="fas fa-sync-alt mr-2"></i> Refresh
                            </button>
                        </div>
                        
                        <div id="savedBookingsContainer">
                            <div class="text-center py-8 text-gray-500">
                                <i class="fas fa-hotel text-3xl mb-3 text-gray-300"></i>
                                <p>No saved bookings yet</p>
                                <p class="text-sm mt-1">Saved bookings will appear here</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right: File Upload & Extraction Section -->
                <div class="col-span-1">
                    <div class="bg-white rounded-lg shadow p-6 sticky top-24">
                        <h2 class="text-lg font-bold text-gray-800 mb-1">
                            <i class="fas fa-file-import text-blue-500 mr-2"></i>
                            Extract from Files
                        </h2>
                        <p class="text-sm text-gray-600 mb-6">
                            Upload files or paste text to automatically extract hotel data
                        </p>

                        <form id="extractionForm">
                            <div class="mb-6">
                                <?php include('./form-elements/file-uploader.php') ?>
                            </div>

                            <!-- Submit for Extraction -->
                            <button type="submit" id="extractBtn"
                                class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-3 px-4 rounded-lg shadow transition duration-300 flex items-center justify-center">
                                <i class="fas fa-magic mr-2"></i> Extract Hotel Data
                            </button>

                            <!-- Clear Files Button -->
                            <button type="button" onclick="clearAllFiles()" 
                                class="w-full mt-3 bg-gray-200 hover:bg-gray-300 text-gray-800 font-medium py-2.5 px-4 rounded-lg transition duration-300 flex items-center justify-center">
                                <i class="fas fa-trash-alt mr-2"></i> Clear All Files
                            </button>
                        </form>

                        <!-- Quick Actions -->
                        <div class="mt-8 pt-6 border-t border-gray-200">
                            <h4 class="text-sm font-semibold text-gray-700 mb-3">
                                <i class="fas fa-bolt text-yellow-500 mr-2"></i> Quick Actions
                            </h4>
                            <div class="grid grid-cols-1 gap-2">
                                <button onclick="addSampleData()" 
                                        class="text-left p-3 bg-purple-50 hover:bg-purple-100 text-purple-700 rounded-lg transition duration-300">
                                    <i class="fas fa-vial mr-2"></i> Add Sample Hotel Data
                                </button>
                                <button onclick="exportAllForms()" 
                                        class="text-left p-3 bg-green-50 hover:bg-green-100 text-green-700 rounded-lg transition duration-300">
                                    <i class="fas fa-download mr-2"></i> Export All Forms (JSON)
                                </button>
                            </div>
                        </div>

                        <!-- Instructions -->
                        <div class="mt-8 pt-6 border-t border-gray-200">
                            <h4 class="text-sm font-semibold text-gray-700 mb-2">
                                <i class="fas fa-info-circle mr-2 text-blue-500"></i> How to use:
                            </h4>
                            <ul class="text-xs text-gray-600 space-y-2">
                                <li class="flex items-start">
                                    <span class="text-blue-500 mr-2 mt-1">•</span>
                                    <span><strong>Manual Entry:</strong> Add booking reference and hotel details manually</span>
                                </li>
                                <li class="flex items-start">
                                    <span class="text-blue-500 mr-2 mt-1">•</span>
                                    <span><strong>Multiple Hotels:</strong> Click "Add Another Hotel" for multi-city tours</span>
                                </li>
                                <li class="flex items-start">
                                    <span class="text-blue-500 mr-2 mt-1">•</span>
                                    <span><strong>File Extraction:</strong> Upload booking confirmations to auto-fill forms</span>
                                </li>
                                <li class="flex items-start">
                                    <span class="text-blue-500 mr-2 mt-1">•</span>
                                    <span><strong>Remove:</strong> Click ❌ on any hotel form to remove it</span>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script src="../assets/js/script.js?time=<?php echo time(); ?>"></script>

    <script>
        // Global Variables
        const EXTRACTION_API = "<?php echo $ip_port; ?>" + `api/hotels/extracted_data.php`;
        const STORE_API = "<?php echo $ip_port; ?>" + `api/hotels/store.php`; // Add your save API endpoint here
        const ALL_HOTEL_BOOKINGS_API = "<?php echo $ip_port; ?>" + `api/hotels/all-bookings.php`; // Add your save API endpoint here
        
        let extractedData = [];
        let savedBookings = [];
        let hotelFormsCount = 0;
        let currentForms = [];

        // Initialize on page load
        document.addEventListener('DOMContentLoaded', function() {
            initDragDrop();
            setupEventListeners();
            updateUI();
            loadSavedBookings();
            
            // Add first empty form by default
            setTimeout(() => {
                addNewHotelForm();
            }, 500);
        });

        // Setup event listeners
        function setupEventListeners() {
            const extractionForm = document.getElementById('extractionForm');
            const pasteArea = document.getElementById('pasteArea');

            extractionForm.addEventListener('submit', async (e) => {
                e.preventDefault();
                await extractData();
            });

            pasteArea.addEventListener('paste', (e) => {
                setTimeout(() => {
                    if (pasteArea.value.trim()) {
                        // Optional: Auto-extract from pasted text
                        // extractFromText(pasteArea.value);
                    }
                }, 100);
            });
        }

        // Show loading state
        function showLoading(text = 'Processing...') {
            document.getElementById('loadingText').textContent = text;
            document.getElementById('loadingOverlay').classList.remove('hidden');
        }

        // Hide loading
        function hideLoading() {
            document.getElementById('loadingOverlay').classList.add('hidden');
        }

        // Show toast message
        function showToast(message, type = 'success') {
            const successToast = document.getElementById('successToast');
            const errorToast = document.getElementById('errorToast');
            
            if (type === 'success') {
                document.getElementById('toastMessage').textContent = message;
                successToast.classList.remove('hidden');
                
                setTimeout(() => {
                    successToast.classList.add('hidden');
                }, 3000);
            } else {
                document.getElementById('errorToastMessage').textContent = message;
                errorToast.classList.remove('hidden');
                
                setTimeout(() => {
                    errorToast.classList.add('hidden');
                }, 3000);
            }
        }

        // Add new hotel form
        function addNewHotelForm(hotelData = null) {
            hotelFormsCount++;
            const formId = `hotelForm_${hotelFormsCount}`;
            
            // Hide no forms message
            const noFormsMsg = document.getElementById('noFormsMessage');
            if (noFormsMsg) noFormsMsg.remove();
            
            // Show action buttons
            document.getElementById('actionButtons').classList.remove('hidden');
            
            // Create form element
            const container = document.getElementById('hotelFormsContainer');
            const formElement = document.createElement('div');
            formElement.id = formId;
            formElement.className = 'hotel-form mb-6 border border-gray-200 rounded-lg p-6 bg-white shadow-sm relative';
            
            // Generate form HTML
            formElement.innerHTML = createHotelFormHTML(hotelFormsCount, hotelData);
            
            container.appendChild(formElement);
            
            // Store form reference
            currentForms.push({
                id: formId,
                data: hotelData || {}
            });
            
            // Update counters
            updateSegmentCounter();
            updateFormNumbers();
            
            return formId;
        }

        // Create hotel form HTML
        function createHotelFormHTML(formNumber, hotelData = null) {
            // If hotelData is provided from API extraction, use it
            const data = hotelData || {};
            const result = data.result || {};
            const hotelAddress = result.hotel_address && result.hotel_address[0] || {};
            const noOfPax = result.no_of_pax || [];
            
            // Calculate pax counts
            let adultCount = 0, childCount = 0, infantCount = 0;
            if (Array.isArray(noOfPax)) {
                noOfPax.forEach(pax => {
                    const age = pax.age || 0;
                    if (age >= 18) adultCount++;
                    else if (age >= 2) childCount++;
                    else infantCount++;
                });
            }
            
            return `
                <!-- Remove Button -->
                <button onclick="removeHotelForm('hotelForm_${formNumber}')" 
                        class="remove-hotel-btn bg-red-100 hover:bg-red-200 text-red-600 w-8 h-8 rounded-full flex items-center justify-center">
                    <i class="fas fa-times text-sm"></i>
                </button>
                
                <!-- Form Header -->
                <div class="flex justify-between items-center mb-6 pb-4 border-b">
                    <div class="flex items-center">
                        <div class="segment-counter bg-blue-500 text-white rounded-full flex items-center justify-center mr-3">
                            ${formNumber}
                        </div>
                        <h3 class="text-lg font-bold text-gray-800">
                            <i class="fas fa-hotel text-blue-500 mr-2"></i>
                            Hotel ${formNumber}
                        </h3>
                    </div>
                    <span class="text-xs bg-gray-100 text-gray-800 px-3 py-1 rounded-full">
                        Segment ${formNumber}
                    </span>
                </div>
        
                <div class="space-y-6">
                    <!-- Hotel Details -->
                    <div>
                        <h4 class="text-md font-semibold text-gray-700 mb-3">
                            <i class="fas fa-building mr-2"></i> Hotel Details
                        </h4>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Hotel Name *</label>
                                <input type="text" name="hotel_name" value="${result.hotel_name || ''}" 
                                    class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                    placeholder="Enter hotel name" required>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Phone Number</label>
                                <input type="text" name="hotel_phone_no" value="${result.hotel_phone_no || ''}" 
                                    class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                    placeholder="Hotel phone number">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                                <input type="email" name="hotel_email" value="${result.hotel_email || ''}" 
                                    class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                    placeholder="hotel@example.com">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Address</label>
                                <input type="text" name="address_line_1" value="${hotelAddress.address_line_1 || ''}" 
                                    class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                    placeholder="Street address">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">City</label>
                                <input type="text" name="address_city" value="${hotelAddress.address_city || ''}" 
                                    class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                    placeholder="City name">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Zip Code</label>
                                <input type="text" name="address_zip_code" value="${hotelAddress.address_zip_code || ''}" 
                                    class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                    placeholder="Postal code">
                            </div>
                        </div>
                    </div>
        
                    <!-- Guest Details -->
                    <div>
                        <h4 class="text-md font-semibold text-gray-700 mb-3">
                            <i class="fas fa-users mr-2"></i> Guest Details
                        </h4>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Given Name</label>
                                <input type="text" name="given_name" value="${result.given_name || ''}" 
                                    class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                    placeholder="First name">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Surname</label>
                                <input type="text" name="sur_name" value="${result.sur_name || ''}" 
                                    class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                    placeholder="Last name">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Total Occupancy</label>
                                <input type="number" name="occupancy" value="${result.occupancy || ''}" 
                                    class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                    placeholder="Total guests" min="1">
                            </div>
                            <div class="grid grid-cols-3 gap-2">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Adult</label>
                                    <input type="number" name="pax_adult" value="${adultCount || ''}" 
                                        class="w-full border border-gray-300 rounded-lg px-2 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                        placeholder="18+">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Child</label>
                                    <input type="number" name="pax_child" value="${childCount || ''}" 
                                        class="w-full border border-gray-300 rounded-lg px-2 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                        placeholder="2-17">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Infant</label>
                                    <input type="number" name="pax_infant" value="${infantCount || ''}" 
                                        class="w-full border border-gray-300 rounded-lg px-2 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                        placeholder="0-2">
                                </div>
                            </div>
                        </div>
                    </div>
        
                    <!-- Stay Details -->
                    <div>
                        <h4 class="text-md font-semibold text-gray-700 mb-3">
                            <i class="fas fa-calendar-alt mr-2"></i> Stay Details
                        </h4>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Check-in Date</label>
                                <input type="date" name="check_in" value="${result.check_in || ''}" 
                                    class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Check-out Date</label>
                                <input type="date" name="check_out" value="${result.check_out || ''}" 
                                    class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Room Type</label>
                                <input type="text" name="hotel_room_type" value="${result.hotel_room_type || ''}" 
                                    class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                    placeholder="e.g., Deluxe Room">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Meal Plan</label>
                                <input type="text" name="meal_plan" value="${result.meal_plan || ''}" 
                                    class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                    placeholder="e.g., Breakfast Included">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Room Info</label>
                                <input type="text" name="room_info" value="${result.room_info || ''}" 
                                    class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                    placeholder="Additional room details">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Cancellation Policy</label>
                                <input type="text" name="cancellation" value="${result.cancellation || ''}" 
                                    class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                    placeholder="Cancellation details">
                            </div>
                        </div>
                    </div>
        
                    <!-- Confirmations -->
                    <div>
                        <h4 class="text-md font-semibold text-gray-700 mb-3">
                            <i class="fas fa-check-circle mr-2"></i> Confirmations
                        </h4>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">PCN</label>
                                <input type="text" name="pcn" value="${result.pcn || ''}" 
                                    class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                    placeholder="Property Confirmation Number">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">HCN</label>
                                <input type="text" name="hcn" value="${result.hcn || ''}" 
                                    class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                    placeholder="Hotel Confirmation Number">
                            </div>
                        </div>
                    </div>
                    
                    <!-- Notes -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Additional Notes</label>
                        <textarea name="notes" rows="2" 
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                            placeholder="Any special requests or notes...">${result.notes || ''}</textarea>
                    </div>
                </div>
            `;
        }

        // Remove hotel form
        function removeHotelForm(formId) {
            const formElement = document.getElementById(formId);
            if (formElement) {
                formElement.remove();
                
                // Remove from currentForms array
                currentForms = currentForms.filter(form => form.id !== formId);
                
                // Update counters
                updateSegmentCounter();
                updateFormNumbers();
                
                // If no forms left, show message
                if (document.querySelectorAll('.hotel-form').length === 0) {
                    const container = document.getElementById('hotelFormsContainer');
                    container.innerHTML = `
                        <div id="noFormsMessage" class="text-center py-12 text-gray-500">
                            <i class="fas fa-hotel text-4xl mb-4 text-gray-300"></i>
                            <p class="text-lg">No hotel forms added yet</p>
                            <p class="text-sm mt-2">Click "Add Another Hotel" button or upload files to extract data</p>
                        </div>
                    `;
                    
                    // Hide action buttons
                    document.getElementById('actionButtons').classList.add('hidden');
                }
                
                showToast('Hotel form removed', 'success');
            }
        }

        // Update segment counter
        function updateSegmentCounter() {
            const forms = document.querySelectorAll('.hotel-form').length;
            document.getElementById('segmentCount').textContent = forms;
            document.getElementById('segmentCounter').className = 
                forms > 0 
                ? 'bg-green-100 text-green-800 text-sm font-semibold px-3 py-1 rounded-full flex items-center'
                : 'bg-blue-100 text-blue-800 text-sm font-semibold px-3 py-1 rounded-full flex items-center';
        }

        // Update form numbers after removal
        function updateFormNumbers() {
            const forms = document.querySelectorAll('.hotel-form');
            forms.forEach((form, index) => {
                const formNumber = index + 1;
                const segmentCounter = form.querySelector('.segment-counter');
                const formTitle = form.querySelector('h3');
                const segmentBadge = form.querySelector('.text-xs.bg-gray-100');
                
                if (segmentCounter) {
                    segmentCounter.textContent = formNumber;
                }
                if (formTitle) {
                    formTitle.innerHTML = `<i class="fas fa-hotel text-blue-500 mr-2"></i>Hotel ${formNumber}`;
                }
                if (segmentBadge) {
                    segmentBadge.textContent = `Segment ${formNumber}`;
                }
                
                // Update form ID and remove button
                const newFormId = `hotelForm_${formNumber}`;
                form.id = newFormId;
                
                const removeBtn = form.querySelector('.remove-hotel-btn');
                if (removeBtn) {
                    removeBtn.setAttribute('onclick', `removeHotelForm('${newFormId}')`);
                }
            });
        }

        // Extract data from files
        async function extractData() {
            if (droppedFiles.length === 0 && !document.getElementById('pasteArea').value.trim()) {
                showToast('Please upload files or paste content first!', 'error');
                return;
            }
        
            showLoading('Extracting hotel data...');
        
            const formData = new FormData();
            
            // Add files
            droppedFiles.forEach(file => {
                formData.append('files[]', file);
            });
            
            // Add pasted text
            const pastedText = document.getElementById('pasteArea').value.trim();
            if (pastedText) {
                formData.append('pasted_text', pastedText);
            }
        
            try {
                const response = await fetch(EXTRACTION_API, {
                    method: 'POST',
                    body: formData
                });
        
                if (!response.ok) {
                    throw new Error('Extraction failed');
                }
        
                const data = await response.json();
                
                console.log('API Response:', data);
                
                if (data.success) {
                    extractedData = data.data || [];
                    
                    if (extractedData.length > 0) {
                        // Clear existing forms first
                        clearAllForms();
                        
                        // Add a form for each extracted hotel
                        extractedData.forEach((hotel, index) => {
                            addNewHotelForm(hotel);
                        });
                        
                        showToast(`Successfully extracted ${extractedData.length} hotel booking(s)`);
                    } else {
                        showToast('No hotel data found in the files', 'error');
                        // Add one empty form if no data found
                        if (document.querySelectorAll('.hotel-form').length === 0) {
                            addNewHotelForm();
                        }
                    }
                } else {
                    showToast(data.message || 'Extraction failed', 'error');
                    // Add one empty form on error
                    if (document.querySelectorAll('.hotel-form').length === 0) {
                        addNewHotelForm();
                    }
                }
            } catch (error) {
                console.error('Extraction error:', error);
                showToast('Failed to extract data. Please try again.', 'error');
                // Add one empty form on error
                if (document.querySelectorAll('.hotel-form').length === 0) {
                    addNewHotelForm();
                }
            } finally {
                hideLoading();
            }
        }

        // Save all bookings
        async function saveAllBookings() {
            const forms = document.querySelectorAll('.hotel-form');
            
            if (forms.length === 0) {
                showToast('No hotel bookings to save!', 'error');
                return;
            }
        
            // Validate required fields
            let isValid = true;
            const requiredFields = ['hotel_name', 'given_name', 'sur_name', 'check_in', 'check_out'];
            
            forms.forEach(form => {
                requiredFields.forEach(fieldName => {
                    const field = form.querySelector(`[name="${fieldName}"]`);
                    if (field && !field.value.trim()) {
                        field.classList.add('border-red-500');
                        isValid = false;
                    }
                });
            });
        
            if (!isValid) {
                showToast('Please fill in all required fields', 'error');
                return;
            }
        
            showLoading('Saving all hotel bookings...');
        
            try {
                // For each form, create and send booking
                const savePromises = [];
                const bookingRef = document.getElementById('bookingRef').value.trim() || `BOOKING-${Date.now()}`;
                const tourName = document.getElementById('tourName').value.trim() || 'No Tour Name';
                
                forms.forEach((form, index) => {
                    // Prepare hotel details
                    const hotelDetails = {
                        hotel_name: form.querySelector('[name="hotel_name"]').value,
                        hotel_phone_no: form.querySelector('[name="hotel_phone_no"]').value,
                        hotel_email: form.querySelector('[name="hotel_email"]').value,
                        hotel_address: form.querySelector('[name="address_line_1"]').value,
                        hotel_city: form.querySelector('[name="address_city"]').value,
                        hotel_zip_code: form.querySelector('[name="address_zip_code"]').value
                    };
        
                    // Prepare guest details
                    const guestDetails = {
                        first_name: form.querySelector('[name="given_name"]').value,
                        last_name: form.querySelector('[name="sur_name"]').value,
                        traveler_sys_id: `TRV-${Math.floor(1000 + Math.random() * 9000)}`, // Generate or get from system
                        total_pax: {
                            adult: parseInt(form.querySelector('[name="pax_adult"]').value) || 0,
                            child: parseInt(form.querySelector('[name="pax_child"]').value) || 0,
                            infant: parseInt(form.querySelector('[name="pax_infant"]').value) || 0
                        }
                    };
        
                    // Prepare staying details
                    const stayingDetails = {
                        check_in: form.querySelector('[name="check_in"]').value,
                        check_out: form.querySelector('[name="check_out"]').value,
                        room_type: form.querySelector('[name="hotel_room_type"]').value,
                        meal_type: form.querySelector('[name="meal_plan"]').value,
                        room_info: form.querySelector('[name="room_info"]').value,
                        cancellation_policy: form.querySelector('[name="cancellation"]').value
                    };
        
                    // Prepare the complete booking data
                    const bookingData = {
                        traveler_name: `${guestDetails.first_name} ${guestDetails.last_name}`,
                        traveler_sys_id: guestDetails.traveler_sys_id,
                        hotel_details: hotelDetails,
                        guest_details: guestDetails,
                        staying_details: stayingDetails,
                        pcn: form.querySelector('[name="pcn"]').value,
                        hcn: form.querySelector('[name="hcn"]').value,
                        notes: form.querySelector('[name="notes"]').value || `${tourName} - Segment ${index + 1}`
                    };
        
                    // Send each booking individually
                    savePromises.push(sendBookingToAPI(bookingData));
                });
        
                // Wait for all saves to complete
                const results = await Promise.allSettled(savePromises);
                
                // Check results
                const successfulSaves = results.filter(r => r.status === 'fulfilled' && r.value.success);
                const failedSaves = results.filter(r => r.status === 'rejected' || !r.value?.success);
                
                hideLoading();
                
                if (failedSaves.length === 0) {
                    showToast(`Successfully saved ${successfulSaves.length} hotel booking(s)`);
                    clearAllForms();
                    addNewHotelForm();
                    loadSavedBookings();
                } else {
                    showToast(`Saved ${successfulSaves.length} out of ${forms.length} bookings. ${failedSaves.length} failed.`, 'error');
                }
                
            } catch (error) {
                console.error('Save error:', error);
                hideLoading();
                showToast('Failed to save bookings. Please try again.', 'error');
            }
        }
        
        // Helper function to send booking to API
        async function sendBookingToAPI(bookingData) {
            try {
                const response = await fetch(STORE_API, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify(bookingData)
                });
        
                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}`);
                }
        
                const data = await response.json();
                return data;
                
            } catch (error) {
                console.error('API call failed:', error);
                return { success: false, message: error.message };
            }
        }

        // Clear all forms
        function clearAllForms() {
            const forms = document.querySelectorAll('.hotel-form');
            forms.forEach(form => form.remove());
            
            currentForms = [];
            hotelFormsCount = 0;
            
            // Show no forms message
            const container = document.getElementById('hotelFormsContainer');
            container.innerHTML = `
                <div id="noFormsMessage" class="text-center py-12 text-gray-500">
                    <i class="fas fa-hotel text-4xl mb-4 text-gray-300"></i>
                    <p class="text-lg">No hotel forms added yet</p>
                    <p class="text-sm mt-2">Click "Add Another Hotel" button or upload files to extract data</p>
                </div>
            `;
            
            // Hide action buttons
            document.getElementById('actionButtons').classList.add('hidden');
            
            // Reset counter
            updateSegmentCounter();
            
            showToast('All forms cleared', 'success');
        }

        // Render saved bookings
        function renderSavedBookings() {
            const container = document.getElementById('savedBookingsContainer');
            
            if (savedBookings.length === 0) {
                container.innerHTML = `
                    <div class="text-center py-8 text-gray-500">
                        <i class="fas fa-hotel text-3xl mb-3 text-gray-300"></i>
                        <p>No saved bookings yet</p>
                        <p class="text-sm mt-1">Saved bookings will appear here</p>
                    </div>
                `;
                return;
            }

            let html = '<div class="space-y-4">';
            
            savedBookings.forEach((booking, index) => {

                const guest = booking.guest_details
                    ? JSON.parse(booking.guest_details)
                    : {};
            
                const hotel = booking.hotel_details
                    ? JSON.parse(booking.hotel_details)
                    : {};
            
                const stay = booking.staying_details
                    ? JSON.parse(booking.staying_details)
                    : {};
            
                html += `
                <div class="border border-gray-200 rounded-lg p-4 bg-gray-50 hover:bg-white transition duration-300">
                    
                    <!-- Header -->
                    <div class="flex justify-between items-start mb-3">
                        <div>
                            <h4 class="font-bold text-gray-800">
                                <i class="fas fa-hotel text-blue-500 mr-2"></i>
                                ${booking.booking_ref}
                            </h4>
                            <p class="text-sm text-gray-600 mt-1">
                                ${booking.notes || 'No Tour Name'}
                            </p>
                        </div>
            
                        <div class="text-right">
                            <span class="text-xs bg-blue-100 text-blue-800 px-3 py-1 rounded-full">
                                Hotel Booking
                            </span>
                            <p class="text-xs text-gray-500 mt-1">
                                ${stay.check_in || 'N/A'} → ${stay.check_out || 'N/A'}
                            </p>
                        </div>
                    </div>
            
                    <!-- Main Info -->
                    <div class="grid grid-cols-2 gap-3 text-sm mb-3">
                        <div>
                            <p class="text-gray-500">Traveler Name</p>
                            <p class="font-medium">${booking.traveler_name}</p>
                        </div>
            
                        <div>
                            <p class="text-gray-500">Traveler ID</p>
                            <p class="font-medium">${booking.traveler_sys_id}</p>
                        </div>
            
                        <div>
                            <p class="text-gray-500">Hotel Name</p>
                            <p class="font-medium">${hotel.hotel_name || 'N/A'}</p>
                        </div>
            
                        <div>
                            <p class="text-gray-500">City</p>
                            <p class="font-medium">${hotel.hotel_city || 'N/A'}</p>
                        </div>
            
                        <div>
                            <p class="text-gray-500">Room Type</p>
                            <p class="font-medium">${stay.room_type || 'N/A'}</p>
                        </div>
            
                        <div>
                            <p class="text-gray-500">Meal Plan</p>
                            <p class="font-medium">${stay.meal_type || 'N/A'}</p>
                        </div>
                    </div>
            
                    <!-- Footer -->
                    <div class="text-xs text-gray-500 border-t pt-2">
                        <div class="flex justify-between items-center">
                            <span>
                                Pax: 
                                Adult ${guest.total_pax?.adult || 0}, 
                                Child ${guest.total_pax?.child || 0}, 
                                Infant ${guest.total_pax?.infant || 0}
                            </span>
            
                            <div class="space-x-3">
                                <button onclick="viewBooking('${booking.uuid}')"
                                    class="text-blue-600 hover:text-blue-800">
                                    <i class="fas fa-eye mr-1"></i> View
                                </button>
            
                                <button onclick="deleteBooking('${booking.uuid}')"
                                    class="text-red-600 hover:text-red-800">
                                    <i class="fas fa-trash mr-1"></i> Delete
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                `;
            });
            
            html += '</div>';
            container.innerHTML = html;
        }

        // View booking details
        function viewBookingDetails(index) {
            const booking = savedBookings[index];
            if (!booking) return;
            
            let details = `
                <div class="bg-white p-4 rounded-lg">
                    <h4 class="font-bold text-lg text-gray-800 mb-4">
                        Booking Details: ${booking.booking_reference}
                    </h4>
                    
                    <div class="mb-4">
                        <p class="text-sm text-gray-600">Tour Name:</p>
                        <p class="font-medium">${booking.tour_name || 'N/A'}</p>
                    </div>
                    
                    <div class="mb-4">
                        <p class="text-sm text-gray-600 mb-2">Hotels (${booking.hotels.length}):</p>
                        <div class="space-y-3">
            `;
            
            booking.hotels.forEach((hotel, hotelIndex) => {
                details += `
                    <div class="border-l-4 border-blue-300 pl-3 py-2 bg-blue-50">
                        <p class="font-medium">${hotelIndex + 1}. ${hotel.hotel_name || 'Unnamed Hotel'}</p>
                        <p class="text-sm text-gray-600">
                            ${hotel.check_in || ''} to ${hotel.check_out || ''} | 
                            Guests: ${hotel.occupancy || '0'}
                        </p>
                    </div>
                `;
            });
            
            details += `
                        </div>
                    </div>
                    
                    <div class="text-right">
                        <button onclick="closeModal()" 
                                class="bg-gray-200 hover:bg-gray-300 text-gray-800 px-4 py-2 rounded-lg">
                            Close
                        </button>
                    </div>
                </div>
            `;
            
            // Show in modal (you need to implement modal or use alert)
            alert(`Booking: ${booking.booking_reference}\nTour: ${booking.tour_name}\nHotels: ${booking.hotels.length}`);
        }

        // Delete booking
        function deleteBooking(index) {
            if (confirm('Are you sure you want to delete this booking?')) {
                savedBookings.splice(index, 1);
                renderSavedBookings();
                showToast('Booking deleted', 'success');
            }
        }

        // Load saved bookings
        function loadSavedBookings() {
            try {
                showLoading('Loading saved bookings...');
        
                fetch(ALL_HOTEL_BOOKINGS_API)
                    .then(response => response.json())
                    .then(responseData => { // Changed parameter name from 'data' to 'responseData'
                        savedBookings = responseData.bookings || [];
                        renderSavedBookings();
                        showToast('Bookings loaded successfully');
                    })
                    .catch(err => {
                        console.error('Error fetching data:', err);
                        showToast('Failed to load bookings', 'error');
                    });
            } catch (error) {
                console.error('Load error:', error);
                showToast('Failed to load bookings', 'error');
            } finally {
                hideLoading();
            }
        }

        // Add sample data
        function addSampleData() {
            clearAllForms();
            
            const sampleHotels = [
                {
                    result: {
                        hotel_name: "Grand Plaza Hotel",
                        hotel_phone_no: "+1 234-567-8900",
                        hotel_email: "info@grandplaza.com",
                        hotel_address: [{ address_line_1: "123 Main Street", address_city: "New York", address_zip_code: "10001" }],
                        given_name: "John",
                        sur_name: "Doe",
                        occupancy: "2",
                        check_in: "2024-06-15",
                        check_out: "2024-06-20",
                        hotel_room_type: "Deluxe Suite",
                        meal_plan: "Breakfast Included",
                        pcn: "PCN12345",
                        hcn: "HCN67890"
                    }
                },
                {
                    result: {
                        hotel_name: "Seaside Resort",
                        hotel_phone_no: "+1 345-678-9012",
                        hotel_email: "reservations@seasideresort.com",
                        hotel_address: [{ address_line_1: "456 Beach Road", address_city: "Miami", address_zip_code: "33139" }],
                        given_name: "John",
                        sur_name: "Doe",
                        occupancy: "4",
                        check_in: "2024-06-20",
                        check_out: "2024-06-25",
                        hotel_room_type: "Ocean View Room",
                        meal_plan: "All Inclusive",
                        pcn: "PCN54321",
                        hcn: "HCN09876"
                    }
                }
            ];
            
            sampleHotels.forEach((hotel, index) => {
                addNewHotelForm(hotel);
            });
            
            document.getElementById('bookingRef').value = "TOUR-SAMPLE-001";
            document.getElementById('tourName').value = "Sample USA Tour 2024";
            
            showToast('Sample data loaded with 2 hotels', 'success');
        }

        // Export all forms as JSON
        function exportAllForms() {
            const forms = document.querySelectorAll('.hotel-form');
            if (forms.length === 0) {
                showToast('No forms to export', 'error');
                return;
            }
            
            const exportData = {
                booking_reference: document.getElementById('bookingRef').value.trim() || `BOOKING-${Date.now()}`,
                tour_name: document.getElementById('tourName').value.trim() || 'No Tour Name',
                exported_at: new Date().toISOString(),
                hotels: []
            };
            
            forms.forEach((form, index) => {
                const inputs = form.querySelectorAll('input, textarea, select');
                const hotelData = {
                    segment_number: index + 1
                };
                
                inputs.forEach(input => {
                    if (input.name) {
                        hotelData[input.name] = input.value;
                    }
                });
                
                exportData.hotels.push(hotelData);
            });
            
            const dataStr = JSON.stringify(exportData, null, 2);
            const dataUri = 'data:application/json;charset=utf-8,'+ encodeURIComponent(dataStr);
            
            const exportFileDefaultName = `hotel-booking-${exportData.booking_reference}.json`;
            
            const linkElement = document.createElement('a');
            linkElement.setAttribute('href', dataUri);
            linkElement.setAttribute('download', exportFileDefaultName);
            linkElement.click();
            
            showToast('Data exported as JSON file', 'success');
        }

        // Update UI
        function updateUI() {
            const forms = document.querySelectorAll('.hotel-form').length;
            const savedSection = document.getElementById('savedBookingsSection');
        }

        // Clear all files (from file uploader)
        function clearAllFiles() {
            // Assuming this function exists in your file-uploader.php
            if (typeof window.clearFileUploader === 'function') {
                window.clearFileUploader();
            }
            
            const pasteArea = document.getElementById('pasteArea');
            if (pasteArea) {
                pasteArea.value = '';
            }
            
            showToast('All files cleared', 'success');
        }
    </script>

</body>

</html>