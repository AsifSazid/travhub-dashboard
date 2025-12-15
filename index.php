<?php
require 'server/db_connection.php';

try {
    $stmt = $pdo->prepare("
            SELECT * FROM leads 
            WHERE lead_status = ?
            ORDER BY created_at DESC
        ");
    $stmt->execute(['pending']);
    $leads = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    echo '<p class="text-red-500">' . $e->getMessage() . '</p>';
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <link rel="icon" type="image/png" href="./assets/images/logo/round-logo.png" sizes="16x16">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/style.css">
    <style>
        /* Additional styles for lead cards and modal */
        .lead-card {
            transition: all 0.2s ease;
            cursor: pointer;
        }

        .lead-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.1);
            border-color: #3b82f6;
        }

        .modal-slide-in {
            animation: modalSlideIn 0.3s ease-out;
        }

        @keyframes modalSlideIn {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .column-scroll {
            scrollbar-width: thin;
            scrollbar-color: #cbd5e0 #f7fafc;
        }

        .column-scroll::-webkit-scrollbar {
            width: 6px;
        }

        .column-scroll::-webkit-scrollbar-track {
            background: #f7fafc;
            border-radius: 3px;
        }

        .column-scroll::-webkit-scrollbar-thumb {
            background-color: #cbd5e0;
            border-radius: 3px;
        }

        .kanban-btn-move,
        .kanban-btn-edit {
            transition: all 0.2s ease;
        }

        .kanban-btn-move:hover,
        .kanban-btn-edit:hover {
            transform: scale(1.1);
        }
    </style>
</head>

<body class="bg-gray-50 font-sans">
    <!-- Top Navigation -->
    <?php include 'elements/header.php'; ?>

    <!-- Sidebar -->
    <?php include 'elements/aside.php'; ?>

    <!-- Main Content -->
    <main id="mainContent" class="pt-16 pl-64 transition-all duration-300">
        <div class="p-6">
            <!-- Kanban Board -->
            <div class="grid grid-cols-6 gap-4">
                <!-- Column 1: Leads -->
                <div class="bg-gray-100 rounded-lg p-4 flex flex-col h-[calc(100vh-8rem)]">
                    <div class="flex justify-between items-center mb-4">
                        <h2 class="font-semibold text-gray-800">Leads</h2>
                        <a href="./generate-leads.php" class="kanban-btn-add" target="_blank">
                            <i class="fas fa-plus"></i>
                        </a>
                    </div>
                    <div id="leadsColumn" class="column-scroll overflow-y-auto flex-grow space-y-3">
                        <?php
                        if (!empty($leads)) {

                            $index = 0;
                            $total = count($leads);

                            while ($index < $total) {
                                $lead = $leads[$index];

                                $clientInfo  = json_decode($lead['client_info'], true);
                                $serviceData = json_decode($lead['service_data'], true);

                                $name = $clientInfo['name'] ?? 'Unknown';
                                $email = $clientInfo['email'] ?? 'N/A';
                                $phone = $clientInfo['phone'] ?? 'N/A';

                                // Service type display
                                $displayService = 'N/A';
                                if (!empty($lead['service_type'])) {
                                    $decodedType = json_decode($lead['service_type'], true);
                                    if (json_last_error() === JSON_ERROR_NONE && is_array($decodedType)) {
                                        $displayService = implode(', ', array_map('ucfirst', $decodedType));
                                    } else {
                                        $displayService = $lead['service_type'];
                                    }
                                }

                                // Country
                                $country = 'N/A';
                                if (is_array($serviceData)) {
                                    foreach ($serviceData as $service) {
                                        if (!empty($service['country'])) {
                                            $country = strtoupper($service['country']);
                                            break;
                                        }
                                    }
                                }

                                $submittedAt = !empty($lead['created_at'])
                                    ? date("j M, Y", strtotime($lead['created_at']))
                                    : 'Unknown';
                        ?>
                                <div class="kanban-card bg-white p-4 rounded-lg shadow-sm border border-gray-200 lead-card"
                                    data-lead-id="<?= $lead['id']; ?>"
                                    data-lead-info='<?= htmlspecialchars(json_encode($lead), ENT_QUOTES, "UTF-8"); ?>'>

                                    <div class="flex justify-between items-start">
                                        <div>
                                            <h3 class="font-medium text-gray-800"><?= htmlspecialchars($name); ?></h3>

                                            <p class="text-sm text-gray-600 mt-1">
                                                <?= htmlspecialchars($displayService); ?>
                                            </p>

                                            <div class="flex items-center mt-2 text-xs text-gray-500">
                                                <i class="fas fa-clock mr-1"></i>
                                                <span><?= $submittedAt; ?></span>
                                            </div>

                                            <div class="flex items-center mt-1 text-xs text-gray-500">
                                                <i class="fas fa-map-marker-alt mr-1"></i>
                                                <span><?= htmlspecialchars($country); ?></span>
                                            </div>
                                        </div>

                                        <button class="kanban-btn-move text-gray-400 hover:text-blue-500"
                                            data-from="leads"
                                            data-to="newWork">
                                            <i class="fas fa-arrow-right"></i>
                                        </button>
                                    </div>
                                </div>
                        <?php
                                $index++;
                            }
                        } else {
                            echo '<p class="text-gray-500 text-sm">No pending leads found.</p>';
                        }
                        ?>

                        <button class="kanban-btn-load-more w-full py-2 bg-white border border-dashed border-gray-300 rounded-lg text-gray-500 hover:bg-gray-50 hover:text-gray-700"
                            data-column="leads">
                            Load More
                        </button>
                    </div>
                </div>

                <!-- Column 2: New Work -->
                <div class="bg-gray-100 rounded-lg p-4 flex flex-col h-[calc(100vh-8rem)]">
                    <div class="flex justify-between items-center mb-4">
                        <h2 class="font-semibold text-gray-800">New Work</h2>
                        <button class="kanban-btn-add" data-modal="workModal">
                            <i class="fas fa-plus"></i>
                        </button>
                    </div>
                    <div id="newWorkColumn" class="column-scroll overflow-y-auto flex-grow space-y-3">
                        <!-- Work Cards -->
                        <div class="kanban-card bg-white p-4 rounded-lg shadow-sm border border-gray-200 card-hover">
                            <div class="flex justify-between items-start">
                                <div>
                                    <h3 class="font-medium text-gray-800">Emma Wilson</h3>
                                    <p class="text-sm text-gray-600 mt-1">Permanent Residency</p>
                                    <div class="flex items-center mt-2 text-xs text-gray-500">
                                        <i class="fas fa-clock mr-1"></i>
                                        <span>5 hours ago</span>
                                    </div>
                                </div>
                                <button class="kanban-btn-move text-gray-400 hover:text-blue-500" data-from="newWork" data-to="inProgress">
                                    <i class="fas fa-arrow-right"></i>
                                </button>
                            </div>
                        </div>

                        <!-- Load More Button -->
                        <button class="kanban-btn-load-more w-full py-2 bg-white border border-dashed border-gray-300 rounded-lg text-gray-500 hover:bg-gray-50 hover:text-gray-700" data-column="newWork">
                            Load More
                        </button>
                    </div>
                </div>

                <!-- Combined Columns 3 & 4 -->
                <div class="col-span-2 bg-gray-100 rounded-lg p-4 flex flex-col h-[calc(100vh-8rem)]">
                    <!-- In Progress Section -->
                    <div class="mb-6">
                        <div class="flex justify-between items-center mb-4">
                            <h2 class="font-semibold text-gray-800">In Progress</h2>
                            <button class="kanban-btn-load-more text-xs bg-white px-3 py-1 rounded border border-gray-300 text-gray-600 hover:bg-gray-50" data-column="inProgress">
                                Load More
                            </button>
                        </div>
                        <div id="inProgressColumn" class="space-y-3">
                            <!-- In Progress Card -->
                            <div class="kanban-card bg-white p-4 rounded-lg shadow-sm border border-gray-200 card-hover">
                                <div class="flex justify-between items-start mb-3">
                                    <h3 class="font-medium text-gray-800">Work #WN-2023-045</h3>
                                    <button class="kanban-btn-edit text-gray-400 hover:text-blue-500" data-card-type="inProgress">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                </div>
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <div class="flex items-center justify-between">
                                            <span class="text-sm font-medium text-gray-500">Client:</span>
                                            <span class="text-sm font-semibold">Robert Taylor</span>
                                        </div>
                                        <div class="mt-2">
                                            <p class="text-xs text-gray-500">Required From Client:</p>
                                            <p class="text-sm">Passport Copy, Financial Statements</p>
                                        </div>
                                    </div>
                                    <div>
                                        <div class="flex items-center justify-between">
                                            <span class="text-sm font-medium text-gray-500">Collected:</span>
                                            <span class="text-xs bg-green-100 text-green-800 px-2 py-1 rounded">5/8</span>
                                        </div>
                                        <div class="mt-2">
                                            <p class="text-xs text-gray-500">Required Documents:</p>
                                            <p class="text-sm">8 documents</p>
                                        </div>
                                        <div class="mt-1">
                                            <span class="inline-block px-2 py-1 bg-blue-100 text-blue-800 text-xs rounded">In Review</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Submitted Work Section -->
                    <div class="flex-grow">
                        <div class="flex justify-between items-center mb-4">
                            <h2 class="font-semibold text-gray-800">Submitted Work / Amendment Handle</h2>
                            <button class="kanban-btn-load-more text-xs bg-white px-3 py-1 rounded border border-gray-300 text-gray-600 hover:bg-gray-50" data-column="submittedWork">
                                Load More
                            </button>
                        </div>
                        <div id="submittedWorkColumn" class="space-y-3">
                            <!-- Submitted Work Card -->
                            <div class="kanban-card bg-white p-4 rounded-lg shadow-sm border border-gray-200 card-hover">
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <div class="flex items-center justify-between">
                                            <span class="text-sm font-medium text-gray-500">Work No:</span>
                                            <span class="text-sm font-semibold">WN-2023-038</span>
                                        </div>
                                        <div class="mt-2">
                                            <p class="text-sm font-medium">Lisa Anderson</p>
                                            <p class="text-xs text-gray-500 mt-1">Amendment From Vendor: Additional financial proof</p>
                                        </div>
                                    </div>
                                    <div>
                                        <div class="flex items-center justify-between">
                                            <span class="text-sm font-medium text-gray-500">Submitted To:</span>
                                            <span class="text-xs font-medium">Visa Services Inc.</span>
                                        </div>
                                        <div class="mt-2">
                                            <p class="text-xs text-gray-500">Amendment List: 2 items</p>
                                            <div class="mt-1">
                                                <span class="inline-block px-2 py-1 bg-yellow-100 text-yellow-800 text-xs rounded">Pending</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Column 5 -->
                <div class="bg-gray-100 rounded-lg p-4 flex flex-col h-[calc(100vh-8rem)]">
                    <!-- Decided Today Section -->
                    <div class="mb-6">
                        <div class="flex justify-between items-center mb-4">
                            <h2 class="font-semibold text-gray-800">Decided Today</h2>
                            <button class="kanban-btn-add" data-modal="decidedModal">
                                <i class="fas fa-plus"></i>
                            </button>
                        </div>
                        <div id="decidedTodayColumn" class="space-y-3">
                            <!-- Decided Today Card -->
                            <div class="kanban-card bg-white p-4 rounded-lg shadow-sm border border-gray-200 card-hover">
                                <h3 class="font-medium text-gray-800">Application Approved</h3>
                                <p class="text-sm text-gray-600 mt-1">Thomas Brown - Work Visa</p>
                                <div class="flex items-center mt-2 text-xs text-gray-500">
                                    <i class="fas fa-check-circle text-green-500 mr-1"></i>
                                    <span>Completed today</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Information to Provide Section -->
                    <div class="flex-grow">
                        <div class="flex justify-between items-center mb-4">
                            <h2 class="font-semibold text-gray-800">Information to Provide</h2>
                            <button class="kanban-btn-add" data-modal="infoModal">
                                <i class="fas fa-plus"></i>
                            </button>
                        </div>
                        <div id="infoToProvideColumn" class="space-y-3">
                            <!-- Information Card -->
                            <div class="kanban-card bg-white p-4 rounded-lg shadow-sm border border-gray-200 card-hover">
                                <h3 class="font-medium text-gray-800">Tax Documentation</h3>
                                <p class="text-sm text-gray-600 mt-1">Required for residency application</p>
                                <div class="flex items-center mt-2 text-xs text-gray-500">
                                    <i class="fas fa-exclamation-triangle text-yellow-500 mr-1"></i>
                                    <span>Urgent</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Column 6 -->
                <div class="bg-gray-100 rounded-lg p-4 flex flex-col h-[calc(100vh-8rem)]">
                    <!-- Documents Collection Section -->
                    <div class="mb-6">
                        <div class="flex justify-between items-center mb-4">
                            <h2 class="font-semibold text-gray-800">Documents Collection</h2>
                            <button class="kanban-btn-add" data-modal="collectionModal">
                                <i class="fas fa-plus"></i>
                            </button>
                        </div>
                        <div id="docCollectionColumn" class="space-y-3">
                            <!-- Collection Card -->
                            <div class="kanban-card bg-white p-4 rounded-lg shadow-sm border border-gray-200 card-hover">
                                <div class="space-y-2">
                                    <div class="flex justify-between">
                                        <span class="text-sm font-medium">From: James Wilson</span>
                                        <span class="text-xs text-gray-500">New York, US</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-sm">Concern: Immigration Dept.</span>
                                        <span class="text-xs text-gray-500">+1 (555) 123-4567</span>
                                    </div>
                                    <div>
                                        <span class="text-sm">Document Type: Passport & Financials</span>
                                    </div>
                                    <div class="flex justify-between text-xs text-gray-500">
                                        <span>Date: 15 Nov 2023</span>
                                        <span>Due: 22 Nov 2023</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Documents Delivery Section -->
                    <div class="flex-grow">
                        <div class="flex justify-between items-center mb-4">
                            <h2 class="font-semibold text-gray-800">Documents Delivery</h2>
                            <button class="kanban-btn-add" data-modal="deliveryModal">
                                <i class="fas fa-plus"></i>
                            </button>
                        </div>
                        <div id="docDeliveryColumn" class="space-y-3">
                            <!-- Delivery Card -->
                            <div class="kanban-card bg-white p-4 rounded-lg shadow-sm border border-gray-200 card-hover">
                                <div class="space-y-2">
                                    <div class="flex justify-between">
                                        <span class="text-sm font-medium">To: Embassy of Canada</span>
                                        <span class="text-xs text-gray-500">Ottawa, CA</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-sm">Concern: Visa Section</span>
                                        <span class="text-xs text-gray-500">+1 (555) 987-6543</span>
                                    </div>
                                    <div>
                                        <span class="text-sm">Document Type: Application Package</span>
                                    </div>
                                    <div class="flex justify-between text-xs text-gray-500">
                                        <span>Date: 18 Nov 2023</span>
                                        <span>Expected: 25 Nov 2023</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Floating Quick Access Tab -->
    <?php include 'elements/floating-menus.php'; ?>

    <!-- Lead Details Modal Template -->
    <template id="leadDetailsModalTemplate">
        <div class="space-y-4">
            <!-- Basic Information -->
            <div>
                <h4 class="text-sm font-medium text-gray-700 mb-2">Basic Information</h4>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <p class="text-xs text-gray-500">Client Name</p>
                        <p class="text-sm font-semibold" id="leadModalName"></p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500">Service Type</p>
                        <p class="text-sm font-semibold" id="leadModalService"></p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500">Submitted Date</p>
                        <p class="text-sm font-semibold" id="leadModalDate"></p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500">Status</p>
                        <p class="text-sm font-semibold"><span class="px-2 py-1 bg-blue-100 text-blue-800 text-xs rounded">Lead</span></p>
                    </div>
                </div>
            </div>

            <!-- Client Information -->
            <div>
                <h4 class="text-sm font-medium text-gray-700 mb-2">Client Details</h4>
                <div class="bg-gray-50 p-3 rounded-lg">
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <p class="text-xs text-gray-500">Email</p>
                            <p class="text-sm" id="leadModalEmail"></p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500">Phone</p>
                            <p class="text-sm" id="leadModalPhone"></p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500">Country</p>
                            <p class="text-sm" id="leadModalCountry"></p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500">Visa Type</p>
                            <p class="text-sm" id="leadModalVisaType"></p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Additional Information -->
            <div>
                <h4 class="text-sm font-medium text-gray-700 mb-2">Additional Information</h4>
                <div id="leadModalAdditionalInfo" class="bg-gray-50 p-3 rounded-lg">
                    <!-- Dynamic content will be inserted here -->
                </div>
            </div>

            <!-- Notes -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Notes</label>
                <textarea id="leadModalNotes" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" rows="3" placeholder="Add notes about this lead..."></textarea>
            </div>

            <!-- Actions -->
            <div class="flex justify-between items-center pt-4 border-t">
                <!-- Right: Convert & Save buttons -->
                <div class="flex space-x-3">
                    <button type="button" class="kanban-modal-btn-convert px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition-colors flex items-center">
                        <i class="fas fa-exchange-alt mr-2"></i>Convert to Work
                    </button>
                </div>
            </div>

        </div>
    </template>

    <!-- Modal Template -->
    <div id="modalOverlay" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-40 hidden modal-overlay">
        <div class="bg-white rounded-xl shadow-2xl max-w-md w-full mx-4 modal-slide-in">
            <div class="p-6">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-semibold text-gray-800" id="modalTitle">Add New Item</h3>
                    <button id="modalClose" class="text-gray-400 hover:text-gray-600">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div class="mb-6" id="modalContent">
                    <!-- Modal content will be inserted here -->
                    <p>Modal content goes here.</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Custom JavaScript Library -->
    <script src="./assets/script.js"></script>
    <script>
        // Kanban Dashboard Library
        const KanbanDashboard = (function() {
            // Private variables
            let activeModal = null;
            let currentLeadId = null;

            // DOM Elements
            const sidebar = document.getElementById('sidebar');
            const sidebarToggle = document.getElementById('sidebarToggle');
            const modalOverlay = document.getElementById('modalOverlay');
            const modalClose = document.getElementById('modalClose');
            const modalTitle = document.getElementById('modalTitle');
            const modalContent = document.getElementById('modalContent');

            // Column references
            const columns = {
                leads: document.getElementById('leadsColumn'),
                newWork: document.getElementById('newWorkColumn'),
                inProgress: document.getElementById('inProgressColumn'),
                submittedWork: document.getElementById('submittedWorkColumn'),
                decidedToday: document.getElementById('decidedTodayColumn'),
                infoToProvide: document.getElementById('infoToProvideColumn'),
                docCollection: document.getElementById('docCollectionColumn'),
                docDelivery: document.getElementById('docDeliveryColumn')
            };

            // Modal templates
            const modalTemplates = {
                leadModal: {
                    title: 'Add New Lead',
                    content: `
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Client Name</label>
                                <input type="text" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Service Type</label>
                                <select class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                                    <option>Business Visa</option>
                                    <option>Work Permit</option>
                                    <option>Permanent Residency</option>
                                    <option>Citizenship</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Notes</label>
                                <textarea class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" rows="3"></textarea>
                            </div>
                        </div>
                    `
                },
                workModal: {
                    title: 'Add New Work',
                    content: `
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Work Title</label>
                                <input type="text" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Client</label>
                                <select class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                                    <option>Select a client</option>
                                    <option>Sarah Johnson</option>
                                    <option>Michael Chen</option>
                                    <option>Emma Wilson</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Priority</label>
                                <div class="flex space-x-4">
                                    <label class="inline-flex items-center">
                                        <input type="radio" name="priority" class="text-blue-500" checked>
                                        <span class="ml-2">Normal</span>
                                    </label>
                                    <label class="inline-flex items-center">
                                        <input type="radio" name="priority" class="text-blue-500">
                                        <span class="ml-2">High</span>
                                    </label>
                                    <label class="inline-flex items-center">
                                        <input type="radio" name="priority" class="text-blue-500">
                                        <span class="ml-2">Urgent</span>
                                    </label>
                                </div>
                            </div>
                        </div>
                    `
                },
                decidedModal: {
                    title: 'Add Decided Item',
                    content: `
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Decision</label>
                                <select class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                                    <option>Approved</option>
                                    <option>Rejected</option>
                                    <option>Pending</option>
                                    <option>Need More Info</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Client Name</label>
                                <input type="text" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Service Type</label>
                                <input type="text" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                            </div>
                        </div>
                    `
                },
                infoModal: {
                    title: 'Add Information to Provide',
                    content: `
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Information Type</label>
                                <input type="text" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="e.g., Tax Documentation">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                                <textarea class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" rows="3" placeholder="Describe the information needed"></textarea>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Priority</label>
                                <select class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                                    <option>Normal</option>
                                    <option>High</option>
                                    <option>Urgent</option>
                                </select>
                            </div>
                        </div>
                    `
                },
                collectionModal: {
                    title: 'Add Document Collection',
                    content: `
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">From/To Name</label>
                                <input type="text" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Location</label>
                                <input type="text" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Concern Name</label>
                                <input type="text" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Phone</label>
                                <input type="text" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Document Type</label>
                                <input type="text" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Date</label>
                                <input type="date" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                            </div>
                        </div>
                    `
                },
                deliveryModal: {
                    title: 'Add Document Delivery',
                    content: `
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">From/To Name</label>
                                <input type="text" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Location</label>
                                <input type="text" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Concern Name</label>
                                <input type="text" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Phone</label>
                                <input type="text" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Document Type</label>
                                <input type="text" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Date</label>
                                <input type="date" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                            </div>
                        </div>
                    `
                },
                editProgressModal: {
                    title: 'Edit In Progress Work',
                    content: `
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Work Number</label>
                                <input type="text" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" value="WN-2023-045">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Client Name</label>
                                <input type="text" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" value="Robert Taylor">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Required From Client</label>
                                <textarea class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" rows="2">Passport Copy, Financial Statements</textarea>
                            </div>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Collected Documents</label>
                                    <input type="number" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" value="5">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Required Documents</label>
                                    <input type="number" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" value="8">
                                </div>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Work Status</label>
                                <select class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                                    <option>Not Started</option>
                                    <option selected>In Review</option>
                                    <option>Pending Client</option>
                                    <option>Completed</option>
                                </select>
                            </div>
                        </div>
                    `
                },
                leadDetailsModal: {
                    title: 'Lead Details',
                    content: document.getElementById('leadDetailsModalTemplate').innerHTML
                }
            };

            // Private methods            
            function openModal(modalId, leadData = null) {
                if (modalId === 'leadDetailsModal' && leadData) {
                    openLeadDetailsModal(leadData);
                } else {
                    const template = modalTemplates[modalId];
                    if (template) {
                        modalTitle.textContent = template.title;
                        modalContent.innerHTML = template.content;
                        modalOverlay.classList.remove('hidden');
                        activeModal = modalId;
                    }
                }
            }

            function openLeadDetailsModal(leadData) {
                const template = modalTemplates.leadDetailsModal;
                modalTitle.textContent = template.title;
                modalContent.innerHTML = template.content;

                // Fill modal with lead data
                const clientInfo = JSON.parse(leadData.client_info || '{}');

                // Set basic information
                document.getElementById('leadModalName').textContent = clientInfo.name || 'N/A';
                let serviceText = 'N/A';

                if (leadData.service_type) {
                    try {
                        const services = JSON.parse(leadData.service_type); // ["visa"]
                        serviceText = Array.isArray(services) ?
                            services.map(s => s.charAt(0).toUpperCase() + s.slice(1)).join(', ') :
                            services;
                    } catch (e) {
                        serviceText = leadData.service_type; // fallback
                    }
                }

                document.getElementById('leadModalService').textContent = serviceText;
                document.getElementById('leadModalDate').textContent =
                    new Date(leadData.created_at).toLocaleDateString('en-US', {
                        day: 'numeric',
                        month: 'short',
                        year: 'numeric'
                    });

                // Set client details
                document.getElementById('leadModalEmail').textContent = clientInfo.emails.length > 0 ? clientInfo.emails[0] : 'N/A';
                document.getElementById('leadModalPhone').textContent = clientInfo.phones.length > 0 ? clientInfo.phones[0] : 'N/A';
                let country = 'N/A';
                let visa_type = 'N/A';

                if (leadData.service_data) {
                    try {
                        const serviceData = JSON.parse(leadData.service_data);

                        // service_data is an object: { visa: { country: "usa", ... } }
                        for (const key in serviceData) {
                            
                            if (serviceData[key]?.country || serviceData[key]?.visaCategory) {
                                country = serviceData[key].country.toUpperCase();
                                visa_type = serviceData[key].visaCategory.toUpperCase() + ' ' + serviceText;
                                break; // first service
                            }
                        }
                    } catch (e) {
                        console.error('Invalid service_data JSON', e);
                    }
                }

                document.getElementById('leadModalCountry').textContent = country;
                document.getElementById('leadModalVisaType').textContent = visa_type;

                // Set additional information
                const additionalInfoDiv = document.getElementById('leadModalAdditionalInfo');
                if (leadData.additional_info && leadData.additional_info !== 'null') {
                    try {
                        const additionalInfo = JSON.parse(leadData.additional_info);
                        let html = '';
                        for (const [key, value] of Object.entries(additionalInfo)) {
                            if (value) {
                                html += `<div class="mb-2">
                                    <p class="text-xs text-gray-500">${key.replace('_', ' ').toUpperCase()}</p>
                                    <p class="text-sm">${value}</p>
                                </div>`;
                            }
                        }
                        if (html === '') {
                            html = '<p class="text-gray-500 text-sm">No additional information available.</p>';
                        }
                        additionalInfoDiv.innerHTML = html;
                    } catch (e) {
                        additionalInfoDiv.innerHTML = `<p class="text-sm">${leadData.additional_info}</p>`;
                    }
                } else {
                    additionalInfoDiv.innerHTML = '<p class="text-gray-500 text-sm">No additional information available.</p>';
                }

                // Set notes if available
                if (leadData.notes) {
                    document.getElementById('leadModalNotes').value = leadData.notes;
                }

                // Store lead ID for later use
                modalContent.dataset.leadId = leadData.id;
                currentLeadId = leadData.id;

                // Show modal
                modalOverlay.classList.remove('hidden');
                activeModal = 'leadDetailsModal';
            }

            function closeModal() {
                modalOverlay.classList.add('hidden');
                activeModal = null;
                currentLeadId = null;
            }

            function moveCard(card, fromColumn, toColumn) {
                // Animate card moving
                card.style.transform = 'translateX(20px)';
                card.style.opacity = '0.7';

                setTimeout(() => {
                    // Remove from current column
                    card.remove();

                    // Add to new column
                    const newCard = card.cloneNode(true);
                    newCard.style.transform = '';
                    newCard.style.opacity = '';

                    // Update the move button data attributes
                    const moveBtn = newCard.querySelector('.kanban-btn-move');
                    if (moveBtn) {
                        if (toColumn === 'newWork') {
                            moveBtn.setAttribute('data-from', 'newWork');
                            moveBtn.setAttribute('data-to', 'inProgress');
                        } else if (toColumn === 'inProgress') {
                            // Change button to edit button for In Progress
                            moveBtn.innerHTML = '<i class="fas fa-edit"></i>';
                            moveBtn.className = 'kanban-btn-edit text-gray-400 hover:text-blue-500';
                            moveBtn.setAttribute('data-card-type', 'inProgress');
                        }
                    }

                    // Insert before the load more button in the target column
                    const loadMoreBtn = columns[toColumn].querySelector('.kanban-btn-load-more');
                    if (loadMoreBtn) {
                        columns[toColumn].insertBefore(newCard, loadMoreBtn);
                    } else {
                        columns[toColumn].appendChild(newCard);
                    }

                    // Re-attach event listeners
                    if (toColumn === 'inProgress') {
                        newCard.querySelector('.kanban-btn-edit').addEventListener('click', function() {
                            openModal('editProgressModal');
                        });
                    } else {
                        newCard.querySelector('.kanban-btn-move').addEventListener('click', function() {
                            const from = this.getAttribute('data-from');
                            const to = this.getAttribute('data-to');
                            moveCard(newCard, from, to);
                        });
                    }

                    // Re-attach click event for lead details
                    if (newCard.classList.contains('lead-card')) {
                        newCard.addEventListener('click', function(e) {
                            if (!e.target.closest('.kanban-btn-move')) {
                                const leadData = JSON.parse(newCard.dataset.leadInfo);
                                openLeadDetailsModal(leadData);
                            }
                        });
                    }
                }, 300);
            }

            function loadMoreItems(columnId) {
                // Sample data for different columns
                const sampleData = {
                    leads: {
                        title: 'David Miller',
                        description: 'Student Visa Application',
                        time: 'Just now',
                        country: 'Australia'
                    },
                    newWork: {
                        title: 'Jennifer Lopez',
                        description: 'Tourist Visa Extension',
                        time: '30 minutes ago'
                    },
                    inProgress: {
                        workNo: 'WN-2023-051',
                        client: 'Alex Johnson',
                        required: 'Birth Certificate, Marriage Certificate',
                        collected: '3',
                        requiredDocs: '5',
                        status: 'Pending Client'
                    },
                    submittedWork: {
                        workNo: 'WN-2023-042',
                        client: 'Maria Garcia',
                        amendment: 'Additional identity proof',
                        submittedTo: 'Global Visa Center',
                        amendmentCount: '1',
                        status: 'Under Review'
                    },
                    decidedToday: {
                        title: 'Application Rejected',
                        description: 'Samuel Lee - Business Visa',
                        time: 'Today'
                    },
                    infoToProvide: {
                        title: 'Employment Verification',
                        description: 'Required for work permit application',
                        time: 'Today',
                        priority: 'High'
                    },
                    docCollection: {
                        fromTo: 'From: Robert Brown',
                        location: 'London, UK',
                        concern: 'Home Office',
                        phone: '+44 20 7946 0958',
                        docType: 'Biometric Residence Permit',
                        date: '20 Nov 2023'
                    },
                    docDelivery: {
                        fromTo: 'To: US Embassy',
                        location: 'London, UK',
                        concern: 'Consular Section',
                        phone: '+44 20 7499 9000',
                        docType: 'Visa Application',
                        date: '22 Nov 2023'
                    }
                };

                const data = sampleData[columnId];
                const column = columns[columnId];
                const loadMoreBtn = column.querySelector('.kanban-btn-load-more');

                let newCard;

                switch (columnId) {
                    case 'leads':
                        newCard = document.createElement('div');
                        newCard.className = 'kanban-card bg-white p-4 rounded-lg shadow-sm border border-gray-200 lead-card';
                        newCard.innerHTML = `
                            <div class="flex justify-between items-start">
                                <div>
                                    <h3 class="font-medium text-gray-800">${data.title}</h3>
                                    <p class="text-sm text-gray-600 mt-1">${data.description}</p>
                                    <div class="flex items-center mt-2 text-xs text-gray-500">
                                        <i class="fas fa-clock mr-1"></i>
                                        <span>${data.time}</span>
                                    </div>
                                    <div class="flex items-center mt-1 text-xs text-gray-500">
                                        <i class="fas fa-map-marker-alt mr-1"></i>
                                        <span>${data.country}</span>
                                    </div>
                                </div>
                                <button class="kanban-btn-move text-gray-400 hover:text-blue-500" data-from="${columnId}" data-to="newWork">
                                    <i class="fas fa-arrow-right"></i>
                                </button>
                            </div>
                        `;
                        // Add sample lead data
                        newCard.dataset.leadId = 'sample-' + Date.now();
                        newCard.dataset.leadInfo = JSON.stringify({
                            id: newCard.dataset.leadId,
                            client_info: JSON.stringify({
                                name: data.title,
                                email: 'sample@example.com',
                                phone: '+1234567890',
                                country: data.country,
                                visa_type: 'Student'
                            }),
                            service_type: data.description,
                            created_at: new Date().toISOString(),
                            notes: ''
                        });
                        break;

                    case 'newWork':
                        newCard = document.createElement('div');
                        newCard.className = 'kanban-card bg-white p-4 rounded-lg shadow-sm border border-gray-200 card-hover';
                        newCard.innerHTML = `
                            <div class="flex justify-between items-start">
                                <div>
                                    <h3 class="font-medium text-gray-800">${data.title}</h3>
                                    <p class="text-sm text-gray-600 mt-1">${data.description}</p>
                                    <div class="flex items-center mt-2 text-xs text-gray-500">
                                        <i class="fas fa-clock mr-1"></i>
                                        <span>${data.time}</span>
                                    </div>
                                </div>
                                <button class="kanban-btn-move text-gray-400 hover:text-blue-500" data-from="${columnId}" data-to="inProgress">
                                    <i class="fas fa-arrow-right"></i>
                                </button>
                            </div>
                        `;
                        break;

                    case 'inProgress':
                        newCard = document.createElement('div');
                        newCard.className = 'kanban-card bg-white p-4 rounded-lg shadow-sm border border-gray-200 card-hover';
                        newCard.innerHTML = `
                            <div class="flex justify-between items-start mb-3">
                                <h3 class="font-medium text-gray-800">Work #${data.workNo}</h3>
                                <button class="kanban-btn-edit text-gray-400 hover:text-blue-500" data-card-type="inProgress">
                                    <i class="fas fa-edit"></i>
                                </button>
                            </div>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <div class="flex items-center justify-between">
                                        <span class="text-sm font-medium text-gray-500">Client:</span>
                                        <span class="text-sm font-semibold">${data.client}</span>
                                    </div>
                                    <div class="mt-2">
                                        <p class="text-xs text-gray-500">Required From Client:</p>
                                        <p class="text-sm">${data.required}</p>
                                    </div>
                                </div>
                                <div>
                                    <div class="flex items-center justify-between">
                                        <span class="text-sm font-medium text-gray-500">Collected:</span>
                                        <span class="text-xs bg-green-100 text-green-800 px-2 py-1 rounded">${data.collected}/${data.requiredDocs}</span>
                                    </div>
                                    <div class="mt-2">
                                        <p class="text-xs text-gray-500">Required Documents:</p>
                                        <p class="text-sm">${data.requiredDocs} documents</p>
                                    </div>
                                    <div class="mt-1">
                                        <span class="inline-block px-2 py-1 bg-blue-100 text-blue-800 text-xs rounded">${data.status}</span>
                                    </div>
                                </div>
                            </div>
                        `;
                        break;

                    default:
                        // For other columns, create a simple card
                        newCard = document.createElement('div');
                        newCard.className = 'kanban-card bg-white p-4 rounded-lg shadow-sm border border-gray-200 card-hover';
                        newCard.innerHTML = `
                            <h3 class="font-medium text-gray-800">New ${columnId} Item</h3>
                            <p class="text-sm text-gray-600 mt-1">Additional content loaded for ${columnId}</p>
                            <div class="flex items-center mt-2 text-xs text-gray-500">
                                <i class="fas fa-clock mr-1"></i>
                                <span>Just now</span>
                            </div>
                        `;
                }

                // Insert before the load more button
                column.insertBefore(newCard, loadMoreBtn);

                // Re-attach event listeners based on column type
                if (columnId === 'leads') {
                    newCard.addEventListener('click', function(e) {
                        if (!e.target.closest('.kanban-btn-move')) {
                            const leadData = JSON.parse(newCard.dataset.leadInfo);
                            openLeadDetailsModal(leadData);
                        }
                    });
                    newCard.querySelector('.kanban-btn-move').addEventListener('click', function() {
                        const from = this.getAttribute('data-from');
                        const to = this.getAttribute('data-to');
                        moveCard(newCard, from, to);
                    });
                } else if (columnId === 'newWork') {
                    newCard.querySelector('.kanban-btn-move').addEventListener('click', function() {
                        const from = this.getAttribute('data-from');
                        const to = this.getAttribute('data-to');
                        moveCard(newCard, from, to);
                    });
                } else if (columnId === 'inProgress') {
                    newCard.querySelector('.kanban-btn-edit').addEventListener('click', function() {
                        openModal('editProgressModal');
                    });
                }
            }

            function saveLeadChanges() {
                const leadId = currentLeadId;
                const notes = document.getElementById('leadModalNotes').value;

                // Show loading state
                const saveBtn = modalContent.querySelector('.kanban-modal-btn-save');
                const originalText = saveBtn.innerHTML;
                saveBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Saving...';
                saveBtn.disabled = true;

                // Simulate API call (replace with actual fetch)
                setTimeout(() => {
                    // Here you would make an actual fetch request to your server
                    console.log('Saving lead:', {
                        leadId,
                        notes
                    });

                    // Show success message
                    saveBtn.innerHTML = '<i class="fas fa-check mr-2"></i>Saved!';
                    setTimeout(() => {
                        saveBtn.innerHTML = originalText;
                        saveBtn.disabled = false;

                        // Update the lead card in the DOM
                        const leadCard = document.querySelector(`.kanban-card[data-lead-id="${leadId}"]`);
                        if (leadCard) {
                            // Update the lead data in the card
                            const leadData = JSON.parse(leadCard.dataset.leadInfo);
                            leadData.notes = notes;
                            leadCard.dataset.leadInfo = JSON.stringify(leadData);
                        }

                        closeModal();
                    }, 1000);
                }, 1500);
            }

            function convertLeadToWork() {
                const leadId = currentLeadId;

                if (confirm('Convert this lead to a new work item?')) {
                    // Show loading state
                    const convertBtn = modalContent.querySelector('.kanban-modal-btn-convert');
                    const originalText = convertBtn.innerHTML;
                    convertBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Converting...';
                    convertBtn.disabled = true;

                    // Simulate API call (replace with actual fetch)
                    setTimeout(() => {
                        // Here you would make an actual fetch request to your server
                        console.log('Converting lead to work:', {
                            leadId
                        });

                        // Show success message
                        convertBtn.innerHTML = '<i class="fas fa-check mr-2"></i>Converted!';

                        // Remove lead card from leads column
                        const leadCard = document.querySelector(`.kanban-card[data-lead-id="${leadId}"]`);
                        if (leadCard) {
                            // Move card to new work column
                            moveCard(leadCard, 'leads', 'newWork');
                        }

                        setTimeout(() => {
                            convertBtn.innerHTML = originalText;
                            convertBtn.disabled = false;
                            closeModal();
                        }, 1000);
                    }, 1500);
                }
            }

            function deleteLead() {
                const leadId = currentLeadId;

                if (confirm('Are you sure you want to delete this lead? This action cannot be undone.')) {
                    // Show loading state
                    const deleteBtn = modalContent.querySelector('.kanban-modal-btn-delete');
                    const originalText = deleteBtn.innerHTML;
                    deleteBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Deleting...';
                    deleteBtn.disabled = true;

                    // Simulate API call (replace with actual fetch)
                    setTimeout(() => {
                        // Here you would make an actual fetch request to your server
                        console.log('Deleting lead:', {
                            leadId
                        });

                        // Remove lead card from leads column
                        const leadCard = document.querySelector(`.kanban-card[data-lead-id="${leadId}"]`);
                        if (leadCard) {
                            leadCard.style.opacity = '0.5';
                            leadCard.style.transform = 'scale(0.95)';

                            setTimeout(() => {
                                leadCard.remove();
                                closeModal();
                            }, 300);
                        } else {
                            closeModal();
                        }
                    }, 1500);
                }
            }

            function initEventListeners() {
                // Modal close
                modalClose.addEventListener('click', closeModal);
                modalOverlay.addEventListener('click', function(e) {
                    if (e.target === modalOverlay) {
                        closeModal();
                    }
                });

                // Add buttons
                document.querySelectorAll('.kanban-btn-add').forEach(button => {
                    button.addEventListener('click', function() {
                        const modalId = this.getAttribute('data-modal');
                        if (modalId) {
                            openModal(modalId);
                        }
                    });
                });

                // Move buttons
                document.querySelectorAll('.kanban-btn-move').forEach(button => {
                    button.addEventListener('click', function() {
                        const card = this.closest('.kanban-card');
                        const from = this.getAttribute('data-from');
                        const to = this.getAttribute('data-to');
                        moveCard(card, from, to);
                    });
                });

                // Edit buttons for In Progress
                document.querySelectorAll('.kanban-btn-edit').forEach(button => {
                    button.addEventListener('click', function() {
                        openModal('editProgressModal');
                    });
                });

                // Load more buttons
                document.querySelectorAll('.kanban-btn-load-more').forEach(button => {
                    button.addEventListener('click', function() {
                        const columnId = this.getAttribute('data-column');
                        loadMoreItems(columnId);
                    });
                });

                // Lead card click events
                document.addEventListener('click', function(e) {
                    const leadCard = e.target.closest('.kanban-card[data-lead-id]');
                    if (leadCard && !e.target.closest('.kanban-btn-move')) {
                        const leadData = JSON.parse(leadCard.dataset.leadInfo);
                        openLeadDetailsModal(leadData);
                    }
                });

                // Modal action buttons (delegated)
                modalOverlay.addEventListener('click', function(e) {
                    const target = e.target;
                    const btn = target.closest('button');

                    if (!btn) return;

                    // Save changes button
                    if (btn.classList.contains('kanban-modal-btn-save')) {
                        saveLeadChanges();
                    }

                    // Convert to work button
                    if (btn.classList.contains('kanban-modal-btn-convert')) {
                        convertLeadToWork();
                    }

                    // Delete button
                    if (btn.classList.contains('kanban-modal-btn-delete')) {
                        deleteLead();
                    }
                });

                // Quick access buttons
                document.querySelectorAll('.quick-access-btn').forEach(button => {
                    button.addEventListener('click', function() {
                        console.log('Quick access button clicked');
                    });
                });
            }

            // Public API
            return {
                init: function() {
                    initEventListeners();
                },

                // Public methods that can be called from outside
                openModal: openModal,
                closeModal: closeModal,

                // Utility to open lead details
                openLeadDetails: function(leadId) {
                    // Find lead data and open modal
                    const leadCard = document.querySelector(`.kanban-card[data-lead-id="${leadId}"]`);
                    if (leadCard) {
                        const leadData = JSON.parse(leadCard.dataset.leadInfo);
                        openLeadDetailsModal(leadData);
                    }
                },

                // Utility to add a new card to a column
                addCard: function(columnId, cardData) {
                    // Implementation for adding cards programmatically
                },

                // Utility to move a card between columns
                moveCard: function(cardId, fromColumn, toColumn) {
                    // Implementation for moving cards between columns
                }
            };
        })();

        // Initialize the dashboard when DOM is loaded
        document.addEventListener('DOMContentLoaded', function() {
            KanbanDashboard.init();
        });
    </script>
</body>

</html>