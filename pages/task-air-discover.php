<?php
include_once('./authenticate.php');
$ip_port = @file_get_contents('../ippath.txt');
if (empty($ip_port)) {
    $ip_port = "http://103.104.219.3:898";
}
$data = null;
$taskId = $_GET['task_id'] ?? '';
$getTicket = $ip_port . "api/tasks/get-ticket.php?task_id=$taskId";

// Fetch invoice data from API
$ch = curl_init($getTicket);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);
$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($http_code == 200 && !empty($response)) {
    $resData = json_decode($response, true);

    if (json_last_error() === JSON_ERROR_NONE && isset($resData['success']) && $resData['success'] === true) {
        if (isset($resData['ticket_json'])) {

            $raw_json = $resData['ticket_json']["air_ticket_info"];

            // 1st decode
            if (is_string($raw_json)) {
                $raw_json = json_decode($raw_json, true);
            }

            // 2nd decode
            if (isset($raw_json[0]) && is_string($raw_json[0])) {
                $raw_json = json_decode($raw_json[0], true);
            }

            // 3rd decode (final safety)
            if (isset($raw_json[0]) && is_string($raw_json[0])) {
                $raw_json = json_decode($raw_json[0], true);
            }

            // final format
            $data = (isset($raw_json[0])) ? $raw_json[0] : $raw_json;
        }
    } else {
        die("Failed to decode API response. JSON error: " . json_last_error_msg());
    }
}

function val($path, $default = "N/A") {
    global $data;
    if (!$data) return htmlspecialchars((string)$default);
    $keys = explode('.', $path);
    $temp = $data;
    foreach ($keys as $key) {
        if (isset($temp[$key])) {
            $temp = $temp[$key];
        } else {
            return htmlspecialchars((string)$default);
        }
    }
    return htmlspecialchars((string)($temp ?? $default));
}

function nested_val($array, $key, $default = "N/A") {
    $keys = explode('.', $key);
    $temp = $array;
    foreach ($keys as $k) {
        if (isset($temp[$k])) {
            $temp = $temp[$k];
        } else {
            return htmlspecialchars((string)$default);
        }
    }
    return htmlspecialchars((string)($temp ?? $default));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Flight Itinerary - <?php echo val('booking_details.booking_reference_pnr'); ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700;800&display=swap');
        body { font-family: 'Inter', sans-serif; background-color: #f8fafc; color: #1e293b; margin: 0; padding: 0; }

        [contenteditable="true"] {
            border: 1px dashed transparent;
            padding: 1px 2px;
            transition: all 0.2s;
            cursor: text;
            display: inline-block;
        }
        [contenteditable="true"]:hover { border-color: #3b82f6; background-color: #eff6ff; }
        [contenteditable="true"]:focus { outline: none; border: 1px solid #3b82f6; background-color: #fff; }

        .main-container { position: relative; min-height: 297mm; background: white; }

        .journey-header-row {
            background-color: #334155;
            color: white;
            font-size: 11px;
            font-weight: 800;
            text-transform: uppercase;
        }

        .connection-row {
            background-color: #f1f5f9;
            border-top: 1px solid #e2e8f0;
            border-bottom: 1px solid #e2e8f0;
        }

        #save-status {
            display: none;
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 10px 20px;
            border-radius: 8px;
            font-weight: bold;
            font-size: 13px;
            z-index: 9999;
            transition: opacity 0.4s;
        }
        #save-status.success { background: #22c55e; color: white; display: block; }
        #save-status.error   { background: #ef4444; color: white; display: block; }

        @media print {
            @page { size: A4; margin: 0.2cm; }
            body { background-color: white !important; -webkit-print-color-adjust: exact; margin: 0; }
            .no-print { display: none !important; }
            .main-container {
                box-shadow: none !important;
                border: none !important;
                width: 100% !important;
                height: 293mm;
                padding: 0.2cm !important;
                margin: 0 !important;
            }
            .footer-section { position: absolute; bottom: 0.2cm; left: 0.2cm; right: 0.2cm; }
            [contenteditable="true"] { border: none !important; background: transparent !important; padding: 0 !important; }
        }
        .main-container { padding: 40px; }
    </style>
</head>
<body class="md:p-4">

    <!-- Save Status Toast -->
    <div id="save-status"></div>

    <div class="max-w-5xl mx-auto shadow-2xl main-container overflow-hidden">
        <div class="h-1.5 bg-blue-600 w-full mb-6"></div>

        <!-- ===== HEADER ===== -->
        <div class="flex justify-between items-start mb-8 px-1">
            <div class="flex gap-4">
                <img src="../assets/images/logo/discover-logo.png" alt="Logo" class="h-14 w-auto" onerror="this.style.display='none'">
                <div>
                    <h1 class="text-2xl font-extrabold tracking-tight text-slate-900 leading-none mb-1">
                        <span contenteditable="true">Discover Holdidays</span>
                    </h1>
                    <div class="text-[10px] text-slate-500 leading-tight">
                        <p class="font-bold uppercase text-blue-600">
                            <span contenteditable="true">Siddique Tower, Level 5, 49 Bir Uttam A.K. Khandakar Road, Mohakhali C/A, Dhaka-1212, Dhaka, Bangladesh</span>
                        </p>
                        <p><span contenteditable="true">ticket@discoverholidaysbd.com | +880 1730 716616</span></p>
                    </div>
                </div>
            </div>
            <div class="text-right">
                <div class="border-l-4 border-blue-600 pl-3">
                    <p class="text-[9px] font-bold text-slate-400 uppercase tracking-[0.2em]">
                        <span contenteditable="true">Airlines (PNR)</span>
                    </p>
                    <p class="text-3xl font-black text-slate-900 font-mono italic uppercase">
                        <!-- ✅ FIX: Named ID for reliable selector -->
                        <span id="pnr-value" contenteditable="true"><?php echo val('booking_details.booking_reference_pnr'); ?></span>
                    </p>
                </div>
            </div>
        </div>

        <!-- ===== PASSENGERS ===== -->
        <div class="mb-8 px-1">
            <h3 class="text-[10px] font-bold text-blue-600 uppercase tracking-[0.2em] mb-2 border-b border-slate-100 pb-1">
                <span contenteditable="true">Passenger Information</span>
            </h3>
            <table class="w-full text-left">
                <thead>
                    <tr class="text-[9px] text-slate-400 uppercase border-b border-slate-50">
                        <th class="py-1 w-10">No.</th>
                        <th class="py-1">Passenger Name</th>
                        <th class="py-1">Ticket / ID</th>
                        <th class="py-1">Type</th>
                        <th class="py-1 text-right">Status</th>
                    </tr>
                </thead>
                <!-- ✅ FIX: Named ID so JS can find passengers reliably -->
                <tbody id="passenger-table-body" class="text-[11px]">
                    <?php
                    $passengers = $data['passengers'] ?? [];
                    foreach ($passengers as $index => $p):
                    ?>
                    <tr class="border-b border-slate-50">
                        <td class="py-2.5 text-slate-400 font-bold"><?php echo $index + 1; ?></td>
                        <td class="py-2.5 font-bold text-slate-800 uppercase italic">
                            <span class="pax-name" contenteditable="true"><?php echo nested_val($p, 'full_name'); ?></span>
                        </td>
                        <td class="py-2.5 font-mono text-slate-500">
                            <span class="pax-ticket" contenteditable="true"><?php echo nested_val($p, 'ticket_number'); ?></span>
                        </td>
                        <td class="py-2.5 text-slate-500 italic">
                            <span class="pax-type" contenteditable="true"><?php echo nested_val($p, 'type'); ?></span>
                        </td>
                        <td class="py-2.5 text-right">
                            <span class="bg-green-50 text-green-700 px-2 py-0.5 rounded text-[9px] font-bold uppercase italic" contenteditable="true">Confirmed</span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- ===== FLIGHTS ===== -->
        <div class="mb-8">
            <h3 class="text-[10px] font-bold text-blue-600 uppercase tracking-[0.2em] mb-2 px-1 border-b border-slate-100 pb-1">
                <span contenteditable="true">Flight Itinerary</span>
            </h3>

            <div class="no-print bg-slate-100 p-4 mb-4 rounded flex gap-3 items-center border border-slate-200">
                <input type="text" id="journey_type" placeholder="e.g. OUTBOUND JOURNEY" class="flex-1 px-3 py-2 text-sm border rounded uppercase font-bold">
                <button onclick="buildJourney()" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded text-sm font-bold uppercase transition-colors">
                    Build Journey Block
                </button>
            </div>

            <table class="w-full border-collapse" id="flight-table">
                <thead id="main-table-header">
                    <tr class="bg-slate-900 text-white text-[9px] uppercase tracking-wider">
                        <th class="p-2.5 text-left pl-3 w-8 no-print">Sel</th>
                        <th class="p-2.5 text-left">Flight / Airline</th>
                        <th class="p-2.5 text-left">Departure</th>
                        <th class="p-2.5 text-center"></th>
                        <th class="p-2.5 text-left">Arrival</th>
                        <th class="p-2.5 text-right pr-3">Details</th>
                    </tr>
                </thead>
                <tbody id="master-flight-body">
                    <?php
                    $flights = $data['journey']['flights'] ?? [];
                    foreach ($flights as $f):
                    ?>
                    <tr class="border-b border-slate-100 flight-row">
                        <td class="p-3 text-center no-print">
                            <input type="checkbox" class="row-checkbox w-4 h-4 cursor-pointer">
                        </td>
                        <td class="p-3 align-top">
                            <div class="font-black text-blue-700 text-[13px] italic leading-none">
                                <span contenteditable="true"><?php echo nested_val($f, 'flight_number'); ?></span>
                            </div>
                            <div class="text-[9px] text-slate-500 font-bold uppercase mt-1">
                                <span contenteditable="true"><?php echo nested_val($f, 'operating_airline'); ?></span>
                            </div>
                        </td>
                        <td class="p-3 align-top departure-cell">
                            <div class="text-base font-black text-slate-900 leading-none">
                                <span class="time-val" contenteditable="true"><?php echo nested_val($f, 'departure.time'); ?></span>
                            </div>
                            <div class="text-[10px] font-bold text-slate-800 mt-1 uppercase">
                                <span class="city-val" contenteditable="true"><?php echo nested_val($f, 'departure.city'); ?></span>
                            </div>
                            <div class="text-[9px] text-slate-500">
                                <span class="date-val" contenteditable="true"><?php echo nested_val($f, 'departure.date'); ?></span>
                            </div>
                        </td>
                        <td class="p-3 text-center align-top opacity-30">
                            <span class="text-[10px]">✈</span>
                            <div class="w-8 border-t border-slate-400 mx-auto my-0.5"></div>
                            <div class="text-[7px] font-bold uppercase">
                                <span contenteditable="true"><?php echo nested_val($f, 'duration'); ?></span>
                            </div>
                        </td>
                        <td class="p-3 align-top arrival-cell">
                            <div class="text-base font-black text-slate-900 leading-none">
                                <span class="time-val" contenteditable="true"><?php echo nested_val($f, 'arrival.time'); ?></span>
                            </div>
                            <div class="text-[10px] font-bold text-slate-800 mt-1 uppercase">
                                <span class="city-val" contenteditable="true"><?php echo nested_val($f, 'arrival.city'); ?></span>
                            </div>
                            <div class="text-[9px] text-slate-500">
                                <span class="date-val" contenteditable="true"><?php echo nested_val($f, 'arrival.date'); ?></span>
                            </div>
                        </td>
                        <td class="p-3 align-top text-right pr-3">
                            <div class="text-[10px] font-bold text-slate-700 italic">
                                <span contenteditable="true"><?php echo nested_val($f, 'class'); ?></span>
                            </div>
                            <div class="text-[10px] text-blue-600 font-black mt-1 uppercase">
                                <span contenteditable="true">Bag: <?php echo nested_val($f, 'baggage_info.checked', '20KG'); ?></span>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- ===== FOOTER ===== -->
        <div class="footer-section">
            <div class="no-print px-1 mb-3">
                <label class="flex items-center gap-2 text-xs font-bold text-slate-500 uppercase cursor-pointer">
                    <input type="checkbox" id="togglePricing" checked onchange="toggleFareSummary()" class="w-4 h-4">
                    Show Pricing Summary
                </label>
            </div>

            <div class="flex justify-between items-end bg-slate-50 p-5 rounded-sm border border-slate-100">
                <div class="max-w-md">
                    <p class="text-[9px] font-bold text-blue-600 uppercase tracking-widest mb-2">Important Instructions</p>
                    <div class="space-y-0.5">
                        <p class="text-[9px] text-slate-500 italic leading-tight">
                            <span contenteditable="true"><?php echo "GDS PNR: " . val('airline_details.galileo_pnr'); ?></span>
                        </p>
                        <?php foreach (($data['important_notes'] ?? []) as $note): ?>
                        <p class="text-[9px] text-slate-500 italic leading-tight">
                            <span contenteditable="true"><?php echo $note['message']; ?></span>
                        </p>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div id="pricing-area" class="text-right min-w-[220px]">
                    <div class="flex justify-between text-[11px] font-bold text-slate-500 mb-1 gap-4">
                        <span>Base Fare</span>
                        <span>
                            <!-- ✅ FIX: Named IDs for reliable fare scraping -->
                            <span id="save-base-fare" contenteditable="true"><?php echo number_format(val('fare_details.base_fare.amount', 0)); ?></span>
                            <span id="fare-currency"><?php echo val('fare_details.total_fare.currency', 'BDT'); ?></span>
                        </span>
                    </div>
                    <div class="flex justify-between text-[11px] font-bold text-slate-500 mb-2 gap-4">
                        <span>Taxes &amp; Fees</span>
                        <span>
                            <span id="save-taxes" contenteditable="true"><?php echo number_format(val('fare_details.taxes.amount', 0)); ?></span>
                            <?php echo val('fare_details.total_fare.currency', 'BDT'); ?>
                        </span>
                    </div>

                    <div class="border-t border-slate-400 mb-2"></div>

                    <div class="flex justify-between items-baseline gap-4">
                        <p class="text-lg font-black text-slate-900 uppercase tracking-tight">Total Paid</p>
                        <p class="text-2xl font-black text-slate-900">
                            <span id="save-total-fare" contenteditable="true"><?php echo number_format(val('fare_details.total_fare.amount', 0)); ?></span>
                            <span class="text-lg"><?php echo val('fare_details.total_fare.currency', 'BDT'); ?></span>
                        </p>
                    </div>
                </div>
            </div>

            <p class="text-center mt-6 text-[8px] text-slate-300 font-bold uppercase tracking-[0.4em]">
                Electronically Generated • Powered by Travhub Global Limited
            </p>
        </div>
    </div>

    <!-- ===== ACTION BUTTONS ===== -->
    <div class="max-w-5xl mx-auto mt-6 flex justify-center gap-4 no-print pb-10">
        <button onclick="saveAndPrint()" class="bg-blue-600 hover:bg-blue-700 text-white px-10 py-3 rounded-full font-bold uppercase tracking-widest shadow-xl transition-colors">
            💾 Save &amp; Print
        </button>
        <button onclick="saveOnly()" class="bg-slate-700 hover:bg-slate-800 text-white px-10 py-3 rounded-full font-bold uppercase tracking-widest shadow-xl transition-colors">
            Save Only
        </button>
        <button onclick="location.reload()" class="bg-white text-slate-500 px-10 py-3 rounded-full font-bold border border-slate-200 uppercase tracking-widest hover:bg-slate-50 transition-colors">
            Reset
        </button>
    </div>

    <script>
    // ─── HELPERS ────────────────────────────────────────────────────

    /**
     * Reads a contenteditable span by ID, strips commas, returns float.
     */
    function getNum(id) {
        const el = document.getElementById(id);
        if (!el) return 0;
        return parseFloat(el.innerText.replace(/,/g, '').trim()) || 0;
    }

    /**
     * Reads a contenteditable span by ID, returns trimmed string.
     */
    function getText(id) {
        const el = document.getElementById(id);
        return el ? el.innerText.trim() : '';
    }

    /**
     * Collects all passenger data from the passenger table body.
     * Uses #passenger-table-body so it never accidentally picks up flight rows.
     */
    function collectPassengers() {
        const rows = document.querySelectorAll('#passenger-table-body tr');
        return Array.from(rows).map(tr => ({
            full_name:     tr.querySelector('.pax-name')?.innerText.trim()   || '',
            ticket_number: tr.querySelector('.pax-ticket')?.innerText.trim() || '',
            type:          tr.querySelector('.pax-type')?.innerText.trim()   || ''
        }));
    }

    /**
     * Builds the full payload to POST to the save endpoint.
     */
    function buildPayload() {
        const currency = document.getElementById('fare-currency')?.innerText.trim() || 'BDT';

        return {
            task_id: <?php echo json_encode($taskId); ?>,   // ✅ Pass task_id so API knows what to update
            booking_details: {
                booking_reference_pnr: getText('pnr-value')
            },
            passengers: collectPassengers(),
            fare_details: {
                base_fare:  { amount: getNum('save-base-fare'), currency },
                taxes:      { amount: getNum('save-taxes'),     currency },
                total_fare: { amount: getNum('save-total-fare'), currency }
            }
        };
    }

    /**
     * Shows a toast notification.
     */
    function showToast(message, type = 'success') {
        const toast = document.getElementById('save-status');
        toast.textContent = message;
        toast.className = type;
        setTimeout(() => { toast.style.opacity = '0'; }, 2500);
        setTimeout(() => { toast.className = ''; toast.style.opacity = '1'; }, 3000);
    }

    /**
     * Sends data to save_ticket.php (which proxies to the real API).
     * Returns true on success, false on failure.
     */
    async function doSave() {
        const payload = buildPayload();
        
        const updateApi = `../api/tasks/update-ticket-json.php?task_id=<?php echo $taskId;?>`;
        
        alert(updateApi);

        try {
            const response = await fetch(updateApi, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            });

            const result = await response.json();

            if (!response.ok || result.success === false) {
                throw new Error(result.message || 'Server returned an error');
            }

            // showToast('✅ Saved successfully!', 'success');
            return true;

        } catch (error) {
            showToast('❌ Save failed: ' + error.message, 'error');
            console.error('Save error:', error);
            return false;
        }
    }

    // ─── PUBLIC ACTIONS ─────────────────────────────────────────────

    async function saveOnly() {
        await doSave();
    }

    async function saveAndPrint() {
        const saved = await doSave();
        if (saved) {
            // Small delay so toast is visible before print dialog opens
            setTimeout(() => window.print(), 600);
        }
    }

    // ─── UI HELPERS ──────────────────────────────────────────────────

    function toggleFareSummary() {
        const pricingArea = document.getElementById('pricing-area');
        pricingArea.style.display = document.getElementById('togglePricing').checked ? 'block' : 'none';
    }

    function calculateLayover(arrivalRow, departureRow) {
        try {
            const arrivalDate    = arrivalRow.querySelector('.arrival-cell .date-val').innerText;
            const arrivalTime    = arrivalRow.querySelector('.arrival-cell .time-val').innerText;
            const departureDate  = departureRow.querySelector('.departure-cell .date-val').innerText;
            const departureTime  = departureRow.querySelector('.departure-cell .time-val').innerText;
            const city           = arrivalRow.querySelector('.arrival-cell .city-val').innerText;

            const arrival   = new Date(`${arrivalDate} ${arrivalTime}`);
            const departure = new Date(`${departureDate} ${departureTime}`);
            const diffMs    = departure - arrival;

            if (isNaN(diffMs) || diffMs < 0) return `Connection in ${city} — Layover: N/A`;

            const hrs  = Math.floor(diffMs / 3600000);
            const mins = Math.round((diffMs % 3600000) / 60000);
            return `Connection in ${city} — Layover: ${hrs}h ${mins}m`;
        } catch (e) {
            return "Connection — Layover: Manual Entry Required";
        }
    }

    function buildJourney() {
        const journeyTitle = document.getElementById('journey_type').value.trim() || "JOURNEY";
        const table        = document.getElementById('flight-table');
        const checkboxes   = Array.from(document.querySelectorAll('.row-checkbox:checked'));
        const masterBody   = document.getElementById('master-flight-body');

        if (checkboxes.length === 0) {
            alert("Please select at least one flight first!");
            return;
        }

        const newTbody  = document.createElement('tbody');
        const headerRow = document.createElement('tr');
        headerRow.className = "journey-header-row";
        headerRow.innerHTML = `<td colspan="6" class="p-2.5 pl-3 font-extrabold tracking-widest"><span contenteditable="true">${journeyTitle}</span></td>`;
        newTbody.appendChild(headerRow);

        checkboxes.forEach((cb, index) => {
            const row = cb.closest('tr');
            cb.checked = false;
            newTbody.appendChild(row);

            if (index < checkboxes.length - 1) {
                const nextRow   = checkboxes[index + 1].closest('tr');
                const layover   = calculateLayover(row, nextRow);
                const connRow   = document.createElement('tr');
                connRow.className = "connection-row text-[10px]";
                connRow.innerHTML = `
                    <td class="no-print"></td>
                    <td colspan="5" class="p-2 pl-3 italic text-slate-600 font-semibold">
                        <span contenteditable="true">${layover}</span>
                    </td>`;
                newTbody.appendChild(connRow);
            }
        });

        table.appendChild(newTbody);
        document.getElementById('journey_type').value = "";

        if (masterBody.querySelectorAll('.flight-row').length === 0) {
            document.getElementById('main-table-header').style.display = 'none';
        }
    }
    </script>
</body>
</html>