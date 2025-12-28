<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Complete Work Entry</title>
    <link rel="icon" type="image/png" href="./assets/images/logo/round-logo.png" sizes="16x16">
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
    <style>
        .search-result-item {
            transition: all 0.2s ease;
        }

        .search-result-item:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        .drag-drop-area {
            border: 2px dashed #d1d5db;
            transition: all 0.3s ease;
        }

        .drag-drop-area.dragover {
            border-color: #3b82f6;
            background-color: rgba(59, 130, 246, 0.05);
        }

        .file-item {
            animation: slideIn 0.3s ease-out;
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .draggable-item {
            cursor: move;
            user-select: none;
        }

        .draggable-item.dragging {
            opacity: 0.5;
        }

        /* Scrollbar styling */
        .custom-scrollbar::-webkit-scrollbar {
            width: 6px;
        }

        .custom-scrollbar::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 10px;
        }

        .custom-scrollbar::-webkit-scrollbar-thumb {
            background: #888;
            border-radius: 10px;
        }

        .custom-scrollbar::-webkit-scrollbar-thumb:hover {
            background: #555;
        }

        /* Preview Modal */
        .preview-modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.8);
            animation: fadeIn 0.3s ease;
        }

        .preview-content {
            background-color: white;
            margin: 5% auto;
            padding: 20px;
            border-radius: 10px;
            width: 90%;
            max-width: 800px;
            max-height: 80vh;
            overflow-y: auto;
            animation: slideUp 0.3s ease;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
            }

            to {
                opacity: 1;
            }
        }

        @keyframes slideUp {
            from {
                transform: translateY(50px);
                opacity: 0;
            }

            to {
                transform: translateY(0);
                opacity: 1;
            }
        }
    </style>
    <!-- Add to the <head> section for FilePond -->
    <link href="https://unpkg.com/filepond@^4/dist/filepond.css" rel="stylesheet">
    <link href="https://unpkg.com/filepond-plugin-image-preview/dist/filepond-plugin-image-preview.css" rel="stylesheet">
    <script src="https://unpkg.com/filepond@^4/dist/filepond.js"></script>
    <script src="https://unpkg.com/filepond-plugin-file-validate-type/dist/filepond-plugin-file-validate-type.js"></script>
    <script src="https://unpkg.com/filepond-plugin-file-validate-size/dist/filepond-plugin-file-validate-size.js"></script>
    <script src="https://unpkg.com/filepond-plugin-image-preview/dist/filepond-plugin-image-preview.js"></script>
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

    <main id="mainContent" class="pt-16 pl-64 transition-all duration-300">
        <div class="p-6">
            <div class="col-span-6 bg-white rounded-lg shadow p-4 flex flex-col">
                <h3 class="text-2xl font-bold text-gray-900 text-center border-b pb-4 mb-4">
                    Financial Transaction Management
                </h3>

                <div class="grid grid-cols-6 gap-4">
                    <!-- Left Column: Client Search -->
                    <div class="col-span-3 bg-white rounded-lg shadow p-4 flex flex-col">
                        <!-- Header -->
                        <div class="mb-4">
                            <h2 class="text-lg font-semibold text-gray-800 flex items-center">
                                <i class="fas fa-user-friends mr-2 text-primary-600"></i>
                                Client/traveller Search
                            </h2>
                            <p class="text-sm text-gray-600">Search clients by various criteria</p>
                        </div>

                        <!-- Search Form -->
                        <div class="mb-4">
                            <div class="flex space-x-2">
                                <select id="serachFor" class="w-1/3 px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent">
                                    <option value="">Search For</option>
                                    <option value="client">Client</option>
                                    <option value="traveller">Traveller</option>
                                </select>
                                <select id="clientSearchType" class="w-1/3 px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent">
                                    <option value="name">Name</option>
                                    <option value="phone">Phone</option>
                                    <option value="email">Email</option>
                                    <option value="company">Company</option>
                                    <option value="id">ID</option>
                                    <option value="position">Position</option>
                                    <option value="work_name">Work Name</option>
                                    <option value="vendor_status">Vendor Status</option>
                                    <option value="phone2">Phone 2</option>
                                </select>
                                <input type="text" id="clientSearchInput" placeholder="Enter search term..." class="flex-1 px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent">
                                <button id="clientSearchBtn" class="px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition duration-200">
                                    <i class="fas fa-search"></i>
                                </button>

                            </div>

                            <div class="flex space-x-2 my-4">
                                <div class="w-2/3">
                                    <label for="client_purpose" class="block text-sm font-medium text-gray-700">Purpose</label>
                                    <input type="text" id="client_purpose" name="client_purpose" required="" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500 p-2" placeholder="e.g., Initial Payment">
                                </div>

                                <div class="w-1/3">
                                    <label for="client_amount" class="block text-sm font-medium text-gray-700">Amount (Deposit)</label>
                                    <input type="number" step="0.01" min="0.01" id="client_amount" name="client_amount" required="" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500 p-2" placeholder="0.00">
                                </div>
                                <div>
                                    <button id="newPurposeForClient" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition duration-200">
                                        <i class="fas fa-plus"></i>
                                    </button>
                                </div>
                            </div>

                            <button type="submit" class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 transition duration-150 ease-in-out">
                                Record Client / Traveler Deposit
                            </button>
                        </div>
                    </div>

                    <!-- Right Column: Vendor Search -->
                    <div class="col-span-3 bg-white rounded-lg shadow p-4 flex flex-col">
                        <!-- Header -->
                        <div class="mb-4">
                            <h2 class="text-lg font-semibold text-gray-800 flex items-center">
                                <i class="fas fa-building mr-2 text-green-600"></i>
                                Vendor Search
                            </h2>
                            <p class="text-sm text-gray-600">Search vendors by various criteria</p>
                        </div>

                        <!-- Search Form -->
                        <div class="mb-4">
                            <div class="flex space-x-2">
                                <select id="vendorSearchType" class="w-1/3 px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent">
                                    <option value="name">Name</option>
                                    <option value="phone">Phone</option>
                                    <option value="email">Email</option>
                                    <option value="company">Company</option>
                                    <option value="id">ID</option>
                                    <option value="position">Position</option>
                                    <option value="work_name">Work Name</option>
                                    <option value="vendor_status">Vendor Status</option>
                                    <option value="phone2">Phone 2</option>
                                </select>
                                <input type="text" id="vendorSearchInput" placeholder="Enter search term..." class="flex-1 px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent">
                                <button id="vendorSearchBtn" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition duration-200">
                                    <i class="fas fa-search"></i>
                                </button>

                            </div>

                            <div class="flex space-x-2 my-4">
                                <div class="w-2/3">
                                    <label for="vendor_purpose" class="block text-sm font-medium text-gray-700">Purpose</label>
                                    <input type="text" id="vendor_purpose" name="vendor_purpose" required="" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-red-500 focus:ring-red-500 p-2" placeholder="e.g., Service Fee">
                                </div>

                                <div class="w-1/3">
                                    <label for="vendor_amount" class="block text-sm font-medium text-gray-700">Amount (Withdrawal)</label>
                                    <input type="number" step="0.01" min="0.01" id="vendor_amount" name="vendor_amount" required="" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-red-500 focus:ring-red-500 p-2" placeholder="0.00">
                                </div>

                                <div>
                                    <button id="newPurposeForVendor" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition duration-200">
                                        <i class="fas fa-plus"></i>
                                    </button>
                                </div>
                            </div>

                            <button type="submit" class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 transition duration-150 ease-in-out">
                                Record Vendor Withdrawal
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <?php include '../elements/floating-menus.php'; ?>

    <script src="../assets/script.js"></script>
</body>

</html>