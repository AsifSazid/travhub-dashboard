<?php
include_once('./authenticate.php');
$ip_port = @file_get_contents('../ippath.txt');
if (empty($ip_port)) {
    $ip_port = "http://103.104.219.3:898";
}

$storeAllVendorApi = $ip_port . "api/vendors/all-vendors.php";
$toggleVendorClientApi = $ip_port . "api/vendors/vendor-store.php";

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vendor Lists</title>
    <link rel="icon" type="image/png" href="../assets/images/logo/round-logo.png" sizes="16x16">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://unpkg.com/sortablejs@1.14.0/Sortable.min.js"></script>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .animate-fade-in {
            animation: fadeIn 0.3s ease-in-out;
        }
        
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .toast-success {
            background: linear-gradient(135deg, #10b981, #059669);
        }
        
        .toast-error {
            background: linear-gradient(135deg, #ef4444, #dc2626);
        }
        
        .toast-warning {
            background: linear-gradient(135deg, #f59e0b, #d97706);
        }
        
        .switch-loading {
            opacity: 0.5;
            cursor: not-allowed;
        }
        
        .table-row-hover:hover {
            background-color: #f9fafb;
            transition: background-color 0.2s ease;
        }
    </style>
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
                    <div class="flex items-start gap-4 flex-wrap mb-4">
                        <div class="flex-1 min-w-0">
                            <h2 class="text-2xl font-semibold text-gray-800 mb-4">Vendor Lists</h2>
                        </div>
                        <a href="create-vendor.php" class="hidden md:flex w-48 px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-800 text-md rounded-lg shadow-md hover:shadow-lg transition-all duration-300 items-center justify-center">
                            <i class="fas fa-plus-circle mr-3"></i>Add New Vendor
                        </a>
                    </div>

                    <div class="overflow-x-auto table-container">
                        <table id="vendorTable" class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Sl No</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Vendor ID</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Vendor Name</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Phone</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Is Client</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Action</th>
                                </tr>
                            </thead>
                            <tbody id="vendorTableBody" class="bg-white divide-y divide-gray-200">
                                <tr>
                                    <td colspan="8" class="px-6 py-10 text-center text-gray-500">
                                        <div class="flex flex-col items-center gap-2">
                                            <i class="fas fa-spinner fa-spin text-3xl text-gray-400"></i>
                                            <p class="text-sm">Loading vendors...</p>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Floating Quick Access Tab -->
    <?php include '../elements/floating-menus.php'; ?>

    <script src="../assets/js/script.js?time=<?php echo time(); ?>"></script>

    <script>
        const API_URL_FOR_ALL_VENDORS = "<?php echo $storeAllVendorApi; ?>";
        const API_URL_FOR_TOGGLE_CLIENT = "<?php echo $toggleVendorClientApi; ?>";

        const tableBody = document.getElementById('vendorTableBody');
        let vendorsData = [];

        function loadVendors() {
            // Show loading state
            tableBody.innerHTML = `
                <tr>
                    <td colspan="8" class="px-6 py-10 text-center text-gray-500">
                        <div class="flex flex-col items-center gap-2">
                            <i class="fas fa-spinner fa-spin text-3xl text-gray-400"></i>
                            <p class="text-sm">Loading vendors...</p>
                        </div>
                    </td>
                </tr>
            `;
            
            fetch(API_URL_FOR_ALL_VENDORS)
                .then(res => res.json())
                .then(data => {
                    if (data.success && data.vendors) {
                        vendorsData = data.vendors;
                        renderTable(vendorsData);
                    } else {
                        renderTable([]);
                        if (data.message) {
                            showToast(data.message, 'warning');
                        }
                    }
                })
                .catch(err => {
                    console.error('Error loading vendors:', err);
                    renderTable([]);
                    showToast('Failed to load vendors. Please check your connection.', 'error');
                });
        }

        function renderTable(list) {
            tableBody.innerHTML = '';
            
            if (!list || list.length === 0) {
                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td colspan="8" class="px-6 py-10 text-center text-gray-500">
                        <div class="flex flex-col items-center gap-2">
                            <i class="fas fa-store-slash text-3xl text-gray-400"></i>
                            <p class="text-sm">No Vendors Found!</p>
                            <p class="text-xs text-gray-400 mt-1">Click "Add New Vendor" to create one</p>
                        </div>
                    </td>
                `;
                tableBody.appendChild(tr);
                return;
            }

            list.forEach((vendor, index) => {
                try {
                    const phoneObj = JSON.parse(vendor.phone || '{}');
                    const primaryPhone = phoneObj.primary_no || 'Unknown';

                    const emailObj = JSON.parse(vendor.email || '{}');
                    const primaryEmail = emailObj.primary || 'Unknown';

                    const tr = document.createElement('tr');
                    tr.className = "table-row-hover transition-colors duration-150";
                    tr.setAttribute('data-vendor-id', vendor.id);

                    const vendorSysId = vendor.vendor_sys_id || vendor.sys_id || 'No ID';
                    const vendorName = vendor.name || 'No Name';
                    
                    tr.innerHTML = `
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">${index + 1}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">
                            <a href="show-vendors.php?vendor_id=${vendorSysId}" class="text-blue-600 hover:text-blue-800 hover:underline" title="Details">
                                ${escapeHtml(vendorSysId)}
                            </a>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">
                            <a href="show-vendors.php?vendor_id=${vendorSysId}" class="text-blue-600 hover:text-blue-800 hover:underline" title="Details">
                                ${escapeHtml(vendorName)}
                            </a>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">${escapeHtml(primaryPhone)}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${escapeHtml(primaryEmail)}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 uppercase">
                            <span class="px-2 py-1 rounded-full text-xs font-medium ${getTypeColor(vendor.type)}">
                                ${vendor.type || 'Unknown'}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            <label class="inline-flex items-center cursor-pointer">
                                <input 
                                    type="checkbox"
                                    class="sr-only peer client-toggle"
                                    data-vendor-id="${vendor.id}"
                                    ${vendor.is_client == 1 ? 'checked' : ''}
                                    onchange="toggleClientStatus(${vendor.id}, this)"
                                >
                                <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer 
                                    peer-checked:bg-green-600 
                                    after:content-[''] after:absolute after:top-[2px] after:left-[2px]
                                    after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all
                                    peer-checked:after:translate-x-full relative">
                                </div>
                            </label>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            <a href="show-vendors.php?vendor_id=${vendorSysId}" class="text-blue-600 hover:text-blue-800 transition-colors" title="Details">
                                <i class="fas fa-eye"></i>
                            </a>
                        </td>
                    `;

                    tableBody.appendChild(tr);
                } catch (error) {
                    console.error('Error rendering vendor:', vendor, error);
                }
            });
        }

        function getTypeColor(type) {
            const colors = {
                'individual': 'bg-blue-100 text-blue-800',
                'company': 'bg-purple-100 text-purple-800',
                'retailer': 'bg-green-100 text-green-800',
                'wholesaler': 'bg-orange-100 text-orange-800',
                'distributor': 'bg-red-100 text-red-800'
            };
            return colors[type?.toLowerCase()] || 'bg-gray-100 text-gray-800';
        }

        function toggleClientStatus(vendorId, checkbox) {
            const action = checkbox.checked ? 'enable' : 'disable';
            
            // Disable checkbox while processing
            checkbox.disabled = true;
            const parentLabel = checkbox.closest('label');
            if (parentLabel) {
                parentLabel.classList.add('opacity-50', 'cursor-not-allowed');
            }
            
            fetch(API_URL_FOR_TOGGLE_CLIENT, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    vendor_id: vendorId,
                    action: action
                })
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    const message = checkbox.checked ? 
                        '✓ Vendor converted to client successfully!' : 
                        '✓ Client relation removed successfully!';
                    showToast(message, 'success');
                    
                    // Update the is_client status in local data
                    const vendorIndex = vendorsData.findIndex(v => v.id == vendorId);
                    if (vendorIndex !== -1) {
                        vendorsData[vendorIndex].is_client = checkbox.checked ? 1 : 0;
                    }
                } else {
                    // Rollback checkbox state
                    checkbox.checked = !checkbox.checked;
                    showToast(data.message || '✗ Failed to toggle client status', 'error');
                }
            })
            .catch(err => {
                console.error('Error:', err);
                checkbox.checked = !checkbox.checked;
                showToast('✗ Network error! Please check your connection and try again.', 'error');
            })
            .finally(() => {
                checkbox.disabled = false;
                const parentLabel = checkbox.closest('label');
                if (parentLabel) {
                    parentLabel.classList.remove('opacity-50', 'cursor-not-allowed');
                }
            });
        }

        function showToast(message, type = 'success') {
            // Remove existing toasts
            const existingToasts = document.querySelectorAll('.custom-toast');
            existingToasts.forEach(toast => toast.remove());
            
            // Create toast element
            const toast = document.createElement('div');
            const toastClass = type === 'success' ? 'toast-success' : (type === 'warning' ? 'toast-warning' : 'toast-error');
            toast.className = `custom-toast fixed bottom-4 right-4 px-6 py-3 rounded-lg shadow-lg text-white z-50 animate-fade-in ${toastClass}`;
            
            const icon = type === 'success' ? 'fa-check-circle' : (type === 'warning' ? 'fa-exclamation-triangle' : 'fa-exclamation-circle');
            
            toast.innerHTML = `
                <div class="flex items-center gap-3">
                    <i class="fas ${icon} text-lg"></i>
                    <span class="text-sm font-medium">${escapeHtml(message)}</span>
                </div>
            `;
            
            document.body.appendChild(toast);
            
            // Remove after 3 seconds
            setTimeout(() => {
                toast.style.opacity = '0';
                toast.style.transform = 'translateY(20px)';
                toast.style.transition = 'all 0.3s ease-out';
                setTimeout(() => {
                    if (toast.parentNode) toast.remove();
                }, 300);
            }, 3000);
        }

        function escapeHtml(str) {
            if (!str) return '';
            return String(str)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#39;');
        }

        // Load vendors when page loads
        document.addEventListener('DOMContentLoaded', function() {
            loadVendors();
        });
        
        // Auto-refresh every 30 seconds (optional)
        let refreshInterval;
        document.addEventListener('visibilitychange', function() {
            if (document.hidden) {
                if (refreshInterval) clearInterval(refreshInterval);
            } else {
                loadVendors();
                refreshInterval = setInterval(loadVendors, 30000);
            }
        });
        
        // Start auto-refresh
        refreshInterval = setInterval(loadVendors, 30000);
    </script>
</body>

</html>