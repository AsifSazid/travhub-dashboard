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
    <main id="mainContent" class="pt-16 pb-16 pl-64 md:pb-0 md:pl-16 lg:pl-64 transition-all duration-300">
        <div class="p-3 md:p-6">
            <!-- Kanban Board -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 gap-4">
                <!-- Column 1: Leads -->
                <div class="bg-gray-100 rounded-lg p-3 md:p-4 flex flex-col h-[400px] md:h-[calc(100vh-8rem)]">
                    <div class="flex justify-between items-center mb-3 md:mb-4">
                        <h2 class="font-semibold text-gray-800 text-sm md:text-base">Leads</h2>
                        <a href="./generate-leads.php" class="kanban-btn-add text-xs md:text-base" target="_blank">
                            <i class="fas fa-plus"></i>
                        </a>
                    </div>
                    <div id="leadsColumn" class="column-scroll overflow-y-auto flex-grow space-y-2 md:space-y-3">
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
                <div class="bg-gray-100 rounded-lg p-3 md:p-4 flex flex-col h-[400px] md:h-[calc(100vh-8rem)]">
                    <div class="flex justify-between items-center mb-3 md:mb-4">
                        <h2 class="font-semibold text-gray-800 text-sm md:text-base">New Work</h2>
                        <button class="kanban-btn-add text-xs md:text-base" data-modal="workModal">
                            <i class="fas fa-plus"></i>
                        </button>
                    </div>
                    <div id="newWorkColumn" class="column-scroll overflow-y-auto flex-grow space-y-2 md:space-y-3">
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
                <div class="col-span-1 lg:col-span-2 bg-gray-100 rounded-lg p-3 md:p-4 flex flex-col h-[400px] md:h-[calc(100vh-8rem)]">
                    <!-- In Progress Section -->
                    <div class="mb-4 md:mb-6">
                        <div class="flex justify-between items-center mb-3 md:mb-4">
                            <h2 class="font-semibold text-gray-800">In Progress</h2>
                            <button class="kanban-btn-load-more text-xs bg-white px-3 py-1 rounded border border-gray-300 text-gray-600 hover:bg-gray-50" data-column="inProgress">
                                Load More
                            </button>
                        </div>
                        <div id="inProgressColumn" class="space-y-2 md:space-y-3">
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
                <div class="bg-gray-100 rounded-lg p-3 md:p-4 flex flex-col h-[400px] md:h-[calc(100vh-8rem)]">
                    <!-- Decided Today Section -->
                    <div class="mb-4 md:mb-6">
                        <div class="flex justify-between items-center mb-3 md:mb-4">
                            <h2 class="font-semibold text-gray-800 text-sm md:text-base">Decided Today</h2>
                            <button class="kanban-btn-add text-xs md:text-base" data-modal="decidedModal">
                                <i class="fas fa-plus"></i>
                            </button>
                        </div>
                        <div id="decidedTodayColumn" class="space-y-2 md:space-y-3">
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
                <div class="bg-gray-100 rounded-lg p-3 md:p-4 flex flex-col h-[400px] md:h-[calc(100vh-8rem)]">
                    <!-- Documents Collection Section -->
                    <div class="mb-4 md:mb-6">
                        <div class="flex justify-between items-center mb-3 md:mb-4">
                            <h2 class="font-semibold text-gray-800 text-sm md:text-base">Doc Collection</h2>
                            <button class="kanban-btn-add text-xs md:text-base" data-modal="collectionModal">
                                <i class="fas fa-plus"></i>
                            </button>
                        </div>
                        <div id="docCollectionColumn" class="space-y-2 md:space-y-3">
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

</body>

</html>