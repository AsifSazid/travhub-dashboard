<?php
$ip_port = @file_get_contents('../ippath.txt');
if (empty($ip_port)) {
    $ip_port = "http://103.104.219.3:898";
}

$vendorId = $_GET['vendor_id'];


$getVendorFinEntriesApi = $ip_port . "api/financial_entries/vendor-fin-entries.php?vendor_id=$vendorId";

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
    <style>
        /* Tab Styles */
        .tab-button {
            padding: 12px 20px;
            font-weight: 500;
            border-bottom: 3px solid transparent;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
            white-space: nowrap;
        }

        .tab-button:hover {
            background-color: #f8fafc;
            border-color: #e2e8f0;
        }

        .tab-button.active {
            color: #3b82f6;
            border-color: #3b82f6;
            background-color: #eff6ff;
        }

        .tab-button.active::after {
            content: '';
            position: absolute;
            bottom: -3px;
            left: 0;
            width: 100%;
            height: 3px;
            background-color: #3b82f6;
        }

        .tab-content {
            display: none;
            animation: fadeIn 0.3s ease;
        }

        .tab-content.active {
            display: block;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
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
    <main id="mainContent" class=" pl-64 transition-all duration-300">
        <div class="p-6">
            <div class="bg-white rounded-lg shadow p-4 flex flex-col h-[400px] md:h-[calc(100vh-8rem)]">
                <!-- Header -->
                <div class="mb-6">
                    <h2 class="text-lg font-semibold text-gray-800 flex items-center">
                        <i class="fas fa-user-circle mr-2 text-purple-600"></i>
                        Vendor's Profile
                    </h2>
                    <p class="text-sm text-gray-600">Manage traveler information, documents, and related data</p>
                </div>

                <!-- Tabs Navigation -->
                <div class="border-b border-gray-200 mb-6">
                    <div class="flex space-x-1 overflow-x-auto custom-scrollbar">
                        <button class="tab-button flex items-center active" data-tab="documents">
                            <i class="fas fa-folder mr-2"></i>
                            Documents
                        </button>
                        <button class="tab-button flex items-center" data-tab="information">
                            <i class="fas fa-info-circle mr-2"></i>
                            Information
                        </button>
                        <button class="tab-button flex items-center" data-tab="work-board">
                            <i class="fas fa-clipboard-list mr-2"></i>
                            Work Board
                        </button>
                        <button class="tab-button flex items-center" data-tab="accounting">
                            <i class="fas fa-calculator mr-2"></i>
                            Accounting
                        </button>
                        <button class="tab-button flex items-center" data-tab="credentials">
                            <i class="fas fa-key mr-2"></i>
                            Credentials
                        </button>
                    </div>
                </div>

                <!-- Tab Content Area -->
                <div class="flex-1 overflow-y-auto">
                    <!-- Documents Tab -->
                    <div id="documents" class="tab-content active">
                        <div class="grid grid-cols-2 gap-6 h-full">
                            <div class="flex items-center col-span-2 justify-center h-full">
                                <div class="text-center">
                                    <i class="fas fa-folder text-4xl text-blue-500 mb-4"></i>
                                    <h3 class="text-xl font-semibold mb-2">Documents Content</h3>
                                    <p class="text-gray-600">Document management will be displayed here</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Work Board Tab -->
                    <div id="work-board" class="tab-content">
                        <div class="grid grid-cols-2 gap-6 h-full">
                            <div class="flex items-center justify-center h-full">
                                <div class="text-center">
                                    <i class="fas fa-clipboard-list text-4xl text-purple-500 mb-4"></i>
                                    <h3 class="text-xl font-semibold mb-2">Work Board Content</h3>
                                    <p class="text-gray-600">Work board tasks will be displayed here</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Accounting Tab -->
                    <div id="accounting" class="tab-content">
                        <div class="grid grid-cols-2 gap-6 h-full">
                            <div class="col-span-2 justify-center h-full w-full">
                                <div class="text-center">
                                    <i class="fas fa-calculator text-4xl text-orange-500 mb-4"></i>
                                    <h3 class="text-xl font-semibold mb-2">Accounting Content</h3>
                                    <p class="text-gray-600">Financial information will be displayed here</p>

                                    <?php include('sv-accounting.php') ?> <!-- sc means show client -->
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Linked Travellers Tab -->
                    <div id="linked-travellers" class="tab-content">
                        <div class="h-full w-full">
                            <?php include('tp-linked-travellers.php') ?> <!-- tp means Traveller Profile -->
                        </div>
                    </div>

                    <!-- Credentials Tab -->
                    <div id="credentials" class="tab-content">
                        <div class="grid grid-cols-2 gap-6 h-full">
                            <div class="flex items-center justify-center h-full">
                                <div class="text-center">
                                    <i class="fas fa-key text-4xl text-indigo-500 mb-4"></i>
                                    <h3 class="text-xl font-semibold mb-2">Credentials Content</h3>
                                    <p class="text-gray-600">Login credentials will be displayed here</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Floating Quick Access Tab -->
    <?php include '../elements/floating-menus.php'; ?>

    <script src="../assets/js/script.js"></script>

    <script>
        // Tab switching functionality
        document.addEventListener('DOMContentLoaded', function() {
            const tabButtons = document.querySelectorAll('.tab-button');
            const tabContents = document.querySelectorAll('.tab-content');

            // Function to switch tabs
            function switchTab(tabId) {
                // Remove active class from all tabs
                tabButtons.forEach(button => {
                    button.classList.remove('active');
                });

                tabContents.forEach(content => {
                    content.classList.remove('active');
                });

                // Add active class to clicked tab
                const activeButton = document.querySelector(`[data-tab="${tabId}"]`);
                const activeContent = document.getElementById(tabId);

                if (activeButton && activeContent) {
                    activeButton.classList.add('active');
                    activeContent.classList.add('active');
                }
            }

            // Add click event to tab buttons
            tabButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const tabId = this.getAttribute('data-tab');
                    switchTab(tabId);
                });
            });

            // Initialize first tab as active
            if (tabButtons.length > 0) {
                const firstTabId = tabButtons[0].getAttribute('data-tab');
                switchTab(firstTabId);
            }
        });
    </script>
</body>

</html>