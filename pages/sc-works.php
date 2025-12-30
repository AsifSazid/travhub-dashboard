    <div class="bg-white rounded-lg shadow p-4 flex flex-col">
        <h2 class="text-2xl font-semibold text-gray-800 mb-4">Work Lists</h2>

        <div class="overflow-x-auto table-container">
            <table id="workTable" class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Work Title</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Work For (Client)</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Files</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Action</th>
                    </tr>
                </thead>
                <tbody id="workTableBody" class="bg-white divide-y divide-gray-200 text-left">
                </tbody>
            </table>
        </div>
    </div>


    <script>
        const API_URL_FOR_CLIENTS_WORKS = "<?php echo $getClientsWorksApi; ?>";
        alert(API_URL_FOR_CLIENTS_WORKS);
        function loadWorks() {
            fetch(API_URL_FOR_CLIENTS_WORKS)
                .then(res => res.json())
                .then(data => {
                    if (!data.success) return;
                    const worksData = data.works;
                    renderTable(worksData);
                })

        }

        const workTableBody = document.getElementById('workTableBody');

        function renderTable(list) {
            // আগের ডাটা মুছে ফেলা
            workTableBody.innerHTML = '';

            list.forEach(work => {
                const tr = document.createElement('tr');
                tr.className = "hover:bg-gray-50";

                // টেবিল রো (Row) এর ভেতরে কলামগুলো তৈরি করা
                tr.innerHTML = `
                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">${work.created_at || 'N/A'}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">${work.title || 'No Title'}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">${work.client_name || 'Unknown'}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${work.file_info || 'Folder'}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                    <a href="completed-task-entry.php?work_id=${work.id}" title="Tasks">
                        <i class="fas fa-tasks"></i>
                    </a>
                </td>
            `;

                workTableBody.appendChild(tr);
            });
        }

        loadWorks();
    </script>