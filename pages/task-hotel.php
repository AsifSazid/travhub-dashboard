<?php
include_once('./authenticate.php');
$ip_port = @file_get_contents('../ippath.txt');
if (empty($ip_port)) {
    $ip_port = "http://103.104.219.3:898";
}
$data = null;
$taskId = $_GET['task_id']; 
$getTicket = $ip_port . "api/tasks/get-hotel.php?task_id=$taskId";
$updateJson = $ip_port . "api/tasks/update-hotel-json.php?task=$taskId";

// Fetch invoice data from API
$ch = curl_init($getTicket);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);
$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($http_code == 200 && !empty($response)) {
    $resData = json_decode($response, true);
    
    // Check if JSON decode was successful
    if (json_last_error() === JSON_ERROR_NONE && isset($resData['success']) && $resData['success'] === true) {
        if (isset($resData['hotel_json'])) {
        
            $raw_json = $resData["hotel_json"]['hotel_info'];
        
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
            
            // var_dump($data);
        }
    } else {
        die("Failed to decode API response. JSON error: " . json_last_error_msg());
    }
}

// Default values to prevent "Undefined array key" or "Null" errors
$defaults = [
    "booking_id" => "N/A",
    "pin" => "0000",
    "hotel_name" => "Hotel Name",
    "hotel_address" => "Address not provided",
    "hotel_phone" => "N/A",
    "check_in_date" => date('Y-m-d'),
    "check_out_date" => date('Y-m-d', strtotime('+1 day')),
    "guest_names" => ["Guest Name"],
    "room_type" => "Standard Room",
    "bedding" => "Bedding details not specified",
    "total_rooms" => 1,
    "adults" => 1,
    "meal_plan" => "Room Only",
    "cancellation_policy" => "Refer to terms and conditions.",
    "total_price" => "0.00",
    "currency" => "$",
    "hotel_email" => "N/P",
    "hotel_room_no" => "N/P",
    "sur_name" => "",
    "given_name" => "",
    "occupancy" => "",
    "room_info" => "",
    "booking_date" => "",
    "cancellation" => "",
    "terms_n_conditions" => "",
    "pcn" => "",
    "hcn" => "",
];

// Merge and ensure no values are actual nulls (converts null to default string)
$data = array_merge($defaults, array_filter($data ?? [], fn($v) => !is_null($v)));

// Ensure guest_names is always an array
if (!is_array($data['guest_names'])) {
    $data['guest_names'] = [$data['guest_names']];
}

// Helper to handle htmlspecialchars safely in PHP 8.1+
function safeHtml($str) {
    if (is_array($str)) {
        // If it's an array, convert to string representation or return empty string
        return htmlspecialchars(implode(', ', $str) ?? "");
    }
    return htmlspecialchars($str ?? "", ENT_QUOTES, 'UTF-8');
}

function formatDate($dateStr) {
    if (!$dateStr || $dateStr == 'N/A') return 'N/A';
    try {
        return date("M d, Y", strtotime($dateStr));
    } catch (Exception $e) {
        return "Invalid Date";
    }
}

// Calculate Nights
try {
    $checkIn = new DateTime($data['check_in_date']);
    $checkOut = new DateTime($data['check_out_date']);
    $nights = $checkIn->diff($checkOut)->format("%a");
} catch (Exception $e) {
    $nights = "0";
}

// Pricing calculation safety
$raw_price = is_numeric($data['total_price']) ? floatval($data['total_price']) : 0.00;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Voucher · <?php echo safeHtml($data['hotel_name']); ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --primary: #0f172a;
            --accent: #2563eb;
            --bg-page: #f1f5f9;
            --bg-card: #ffffff;
            --text-main: #1e293b;
            --text-muted: #64748b;
            --border: #e2e8f0;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; -webkit-print-color-adjust: exact; }
        body { background-color: var(--bg-page); font-family: 'Poppins', sans-serif; color: var(--text-main); line-height: 1.3; }

        .container {
            width: 210mm;
            height: 296mm;
            margin: 0 auto;
            background: var(--bg-card);
            display: flex;
            flex-direction: column;
            position: relative;
            overflow: hidden;
        }

        .toolbar { width: 210mm; margin: 10px auto; display: flex; justify-content: space-between; align-items: center; padding: 12px 20px; background: #1e293b; color: white; border-radius: 8px; }
        .btn-print { background: #2563eb; color: white; padding: 8px 24px; border-radius: 6px; cursor: pointer; border: none; font-weight: 700; }

        [contenteditable="true"] { outline: none; transition: all 0.2s; border-radius: 2px; }
        [contenteditable="true"]:hover { background: rgba(37, 99, 235, 0.08); }
        [contenteditable="true"]:focus { background: #fff; box-shadow: 0 0 0 2px #2563eb; color: #000; }

        header { 
            padding: 25px 40px; 
            background: #e2e8f0;
            color: var(--primary);
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
        }
        .status-pill { background: #059669; color: white; padding: 4px 12px; border-radius: 6px; font-size: 11px; font-weight: 700; text-transform: uppercase; }

        .section { padding: 18px 40px; border-bottom: 1px solid var(--border); }
        .section-title { font-size: 13px; font-weight: 700; margin-bottom: 8px; color: var(--primary); text-transform: uppercase; letter-spacing: 1px; }

        .booking-hero { display: flex; gap: 15px; padding: 20px 40px; background: #f8fafc; border-bottom: 1px solid var(--border); }
        .date-card { flex: 1; background: white; padding: 12px; border-radius: 8px; border: 1px solid var(--border); }
        .date-label { font-size: 9px; font-weight: 700; color: var(--accent); text-transform: uppercase; }
        .date-value { font-size: 16px; font-weight: 700; color: var(--primary); }

        .data-table { width: 100%; border-collapse: collapse; }
        .data-table th { text-align: left; padding: 8px 0; font-size: 10px; text-transform: uppercase; color: var(--text-muted); border-bottom: 2px solid var(--border); }
        .data-table td { padding: 10px 0; border-bottom: 1px solid var(--border); font-size: 12px; }

        .pill { background: #f1f5f9; padding: 4px 10px; border-radius: 4px; font-size: 10px; margin-right: 6px; display: inline-block; margin-bottom: 5px; }

        .pricing-table { width: 100%; background: #f1f5f9; border-radius: 8px; padding: 15px; }
        .price-row { display: flex; justify-content: space-between; padding: 4px 0; font-size: 13px; }
        .price-total { border-top: 2px solid #cbd5e1; margin-top: 8px; padding-top: 8px; font-weight: 800; font-size: 18px; color: var(--accent); }

        footer { padding: 25px 40px; font-size: 11px; color: var(--text-muted); display: flex; justify-content: space-between; background: #f8fafc; margin-top: auto; border-top: 1px solid var(--border); }

        @media print {
            @page { size: A4; margin: 0; }
            body { background: white; padding: 0; margin: 0; }
            .container { width: 100%; height: 297mm; margin: 0; border: none; box-shadow: none; border-radius: 0; }
            .toolbar { display: none !important; }
        }
        .no-print { margin-bottom: 10px; }
        .toggle-label {
            display: flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
            font-size: 11px;
            font-weight: bold;
            color: var(--text-muted);
        }
        @media print {
            .no-print { display: none !important; }
        }
    </style>
</head>
<body>

    <div class="toolbar">
        <div style="font-size: 13px;"><strong>Editor Mode:</strong> Values auto-corrected for nulls.</div>
        <button onclick="saveAndPrint()" class="btn-print">Print & Save Voucher</button>
    </div>
    <div class="container">
        <header>
            <div class="brand" style="display: flex; align-items: center; gap: 15px;">
                <img src="../assets/images/logo/logo.png" alt="Logo" style="height: 50px; width: auto; object-fit: contain;">
                
                <div>
                    <h1 contenteditable="true" id="company-name" style="font-size: 24px;">TravHub Global Limited</h1>
                    <p style="font-size: 12px; opacity: 0.8;">
                        Confirmation: <strong contenteditable="true" id="booking-id"><?php echo safeHtml($data['booking_id'] ? $data['booking_id'] : $data['pcn']); ?></strong> · 
                        PIN: <span contenteditable="true" id="pin-number"><?php echo safeHtml($data['pin']); ?></span>
                    </p>
                </div>
            </div>
            <div class="status-pill" contenteditable="true" id="status-pill">✓ Paid In Full</div>
        </header>
    
        <div class="section">
            <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                <div>
                    <h2 class="section-title" contenteditable="true" id="hotel-name"><?php echo safeHtml($data['hotel_name']); ?></h2>
                    <?php
                        // Full address in one line
                        $fullAddress = [];
                        
                        if (isset($data['hotel_address'])) {
                            if (is_array($data['hotel_address'])) {
                                if (isset($data['hotel_address'][0]) && is_array($data['hotel_address'][0])) {
                                    // Format: address_line_1, address_line_2, city, state, zip
                                    $addr = $data['hotel_address'][0];
                                    if (!empty($addr['address_line_1'])) $fullAddress[] = $addr['address_line_1'];
                                    if (!empty($addr['address_line_2'])) $fullAddress[] = $addr['address_line_2'];
                                    if (!empty($addr['address_city'])) $fullAddress[] = $addr['address_city'];
                                    if (!empty($addr['address_state'])) $fullAddress[] = $addr['address_state'];
                                    if (!empty($addr['address_zip_code'])) $fullAddress[] = $addr['address_zip_code'];
                                } else {
                                    if (!empty($data['hotel_address']['address_line_1'])) $fullAddress[] = $data['hotel_address']['address_line_1'];
                                    if (!empty($data['hotel_address']['address_line_2'])) $fullAddress[] = $data['hotel_address']['address_line_2'];
                                    if (!empty($data['hotel_address']['address_city'])) $fullAddress[] = $data['hotel_address']['address_city'];
                                    if (!empty($data['hotel_address']['address_state'])) $fullAddress[] = $data['hotel_address']['address_state'];
                                    if (!empty($data['hotel_address']['address_zip_code'])) $fullAddress[] = $data['hotel_address']['address_zip_code'];
                                }
                            }
                        }
                        
                        if (!empty($fullAddress)) {
                            echo '<p style="color: var(--text-muted); font-size: 12px; max-width: 500px;" contenteditable="true" id="hotel-address">' . safeHtml(implode(', ', $fullAddress)) . '</p>';
                        } else {
                            echo '<p style="color: var(--text-muted); font-size: 12px; max-width: 500px;" contenteditable="true" id="hotel-address">' . safeHtml($data['hotel_address'] ?? 'Address not available') . '</p>';
                        }
                    ?>
                    <p style="margin-top: 5px; font-size: 12px;">
                        <strong>Phone:</strong> 
                        <span contenteditable="true" id="hotel-phone"><?php echo safeHtml($data['hotel_phone']); ?></span>
                    </p>
                </div>
                <div style="border: 1px dashed #cbd5e1; padding: 10px; font-size: 10px; color: #94a3b8; text-align: center; border-radius: 4px;">OFFICIAL<br>VOUCHER</div>
            </div>
        </div>
    
        <div class="booking-hero">
            <div class="date-card">
                <span class="date-label">Check-In</span>
                <div class="date-value" contenteditable="true" id="check-in-date"><?php echo formatDate($data['check_in_date']); ?></div>
                <div style="font-size: 10px; color: var(--text-muted);" contenteditable="true" id="check-in-time">After 2:00 PM</div>
            </div>
            <div class="date-card">
                <span class="date-label">Check-Out</span>
                <div class="date-value" contenteditable="true" id="check-out-date"><?php echo formatDate($data['check_out_date']); ?></div>
                <div style="font-size: 10px; color: var(--text-muted);" contenteditable="true" id="check-out-time">Before 12:00 PM</div>
            </div>
            <div class="date-card">
                <span class="date-label">Stay Details</span>
                <div class="date-value"><span contenteditable="true" id="nights-count"><?php echo $nights; ?></span> Nights</div>
                <div style="font-size: 10px; color: var(--text-muted); font-weight: 600;" id="room-guest-count">
                    <span contenteditable="true" id="total-rooms"><?php echo safeHtml($data['total_rooms']); ?></span> Room(s) | 
                    <span contenteditable="true" id="total-guests"><?php echo count($data['guest_names']); ?></span> Guest(s)
                </div>
            </div>
        </div>
    
        <div class="section">
            <h2 class="section-title">Guest Details</h2>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Lead Guest</th>
                        <th>Additional Guests</th>
                        <th>Inventory</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><strong style="font-size: 13px;" contenteditable="true" id="lead-guest"><?php echo safeHtml($data['guest_names'][0] ?? 'N/A'); ?></strong></td>
                        
                        <td id="guest-list" contenteditable="true">
                            <?php 
                            $others = array_slice($data['guest_names'], 1);
                            echo !empty($others) ? safeHtml(implode(", ", $others)) : "No additional guests";
                            ?>
                        </td>
                        <td><strong contenteditable="true" id="inventory-rooms"><?php echo safeHtml($data['total_rooms']); ?> Room(s)</strong></td>
                    </tr>
                </tbody>
            </table>
        </div>
    
        <div class="section">
            <h2 class="section-title">Room & Inclusions</h2>
            <p style="font-weight: 700; font-size: 15px; color: var(--accent);" contenteditable="true" id="room-type"><?php echo safeHtml($data['room_type']); ?></p>
            <p style="font-size: 12px; color: var(--text-muted); margin-bottom: 10px;" contenteditable="true" id="meal-plan-info">Plan: <strong id="meal-plan"><?php echo safeHtml($data['meal_plan']); ?></strong></p>
            <div>
                <span class="pill" contenteditable="true" id="amenity-1">Free High-Speed WiFi</span>
                <span class="pill" contenteditable="true" id="amenity-2">Air Conditioning</span>
                <span class="pill" contenteditable="true" id="amenity-3">Private Bathroom</span>
            </div>
        </div>
    
        <div class="section">
            <h2 class="section-title">Policies</h2>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                <div>
                    <h4 style="font-size: 10px; text-transform: uppercase; color: var(--primary);">Cancellation</h4>
                    <p style="font-size: 11px; color: var(--text-muted);" contenteditable="true" id="cancellation-policy"><?php echo safeHtml($data['cancellation_policy']); ?></p>
                </div>
                <div>
                    <h4 style="font-size: 10px; text-transform: uppercase; color: var(--primary);">Important</h4>
                    <p style="font-size: 11px; color: var(--text-muted);" contenteditable="true" id="important-notes">Valid passport and visa required for check-in.</p>
                </div>
            </div>
        </div>
    
        <!-- 1. Add the Checkbox just before the Payment Summary Section -->
        <div class="section no-print" style="border: none; padding-bottom: 0;">
            <label class="toggle-label">
                <input type="checkbox" id="togglePricing" checked onchange="toggleFareSummary()" style="width: 16px; height: 16px;">
                SHOW PRICING SUMMARY
            </label>
        </div>
        
        <!-- 2. Add id="pricing-area" to the section you want to hide/show -->
        <div class="section" id="pricing-area" style="border: none;">
            <h2 class="section-title">Payment Summary</h2>
            <div class="pricing-table">
                <div class="price-row">
                    <span>Room Base Rate (<?php echo $nights; ?> Nights)</span>
                    <span>
                        <span class="save-currency" contenteditable="true" id="currency-symbol"><?php echo safeHtml($data['currency']); ?></span> 
                        <span id="display-base-rate"><?php echo number_format($raw_price * 0.83, 2); ?></span>
                    </span>
                </div>
                <div class="price-row">
                    <span>Service Charge & Taxes</span>
                    <span>
                        <span class="save-currency" contenteditable="true"><?php echo safeHtml($data['currency']); ?></span> 
                        <span id="display-tax"><?php echo number_format($raw_price * 0.17, 2); ?></span>
                    </span>
                </div>
                <div class="price-row price-total">
                    <span>Total Amount Paid</span>
                    <span>
                        <span id="save-currency" contenteditable="true"><?php echo safeHtml($data['currency']); ?></span> 
                        <span id="save-total-price" contenteditable="true" oninput="updateCalculations(this)"><?php echo number_format($raw_price, 2); ?></span>
                    </span>
                </div>
            </div>
        </div>
    
        <footer>
            <div>
                <strong contenteditable="true" id="company-support">TravHub Global Limited (Support)</strong><br>
                <span contenteditable="true" id="support-helpline">24/7 Helpline: +880 1611 482 773</span>
            </div>
            <div style="text-align: right;">
                <span contenteditable="true" id="voucher-id">Digital Voucher ID: <?php echo strtoupper(substr(md5($data['booking_id']), 0, 12)); ?></span><br>
                <em contenteditable="true" id="website">travhub.com.bd</em>
            </div>
        </footer>
    </div>

    <script>
        const UPDATE_JSON = `<?php echo $updateJson ?>`;

        async function saveAndPrint() {
            // Collect all data using IDs
            const updatedData = {
                // Basic Information
                booking_id: document.getElementById('booking-id')?.innerText.trim() || '',
                pin: document.getElementById('pin-number')?.innerText.trim() || '',
                hotel_name: document.getElementById('hotel-name')?.innerText.trim() || '',
                hotel_address: document.getElementById('hotel-address')?.innerText.trim() || '',
                hotel_phone: document.getElementById('hotel-phone')?.innerText.trim() || '',
                
                // Guest Information
                guest_names: (() => {
                    // Lead guest is the first element
                    const leadGuest = document.getElementById('lead-guest')?.innerText.trim() || '';
                    
                    // Get additional guests from guest-list
                    const guestListText = document.getElementById('guest-list')?.innerText.trim() || '';
                    let additionalGuests = [];
                    
                    if (guestListText && guestListText !== 'No additional guests') {
                        additionalGuests = guestListText.split(',').map(name => name.trim());
                    }
                    
                    // Combine: lead guest first, then additional guests
                    return [leadGuest, ...additionalGuests].filter(name => name !== '');
                })(),
                
                lead_guest: document.getElementById('lead-guest')?.innerText.trim() || '',
                
                // Room Information
                room_type: document.getElementById('room-type')?.innerText.trim() || '',
                meal_plan: document.getElementById('meal-plan')?.innerText.trim() || '',
                total_rooms: document.getElementById('total-rooms')?.innerText.trim() || '1',
                
                // Date Information
                check_in_date: document.getElementById('check-in-date')?.innerText.trim() || '',
                check_out_date: document.getElementById('check-out-date')?.innerText.trim() || '',
                nights: document.getElementById('nights-count')?.innerText.trim() || '0',
                
                // Pricing Information
                total_price: document.getElementById('save-total-price')?.innerText.replace(/,/g, '').trim() || '0',
                currency: document.getElementById('save-currency')?.innerText.trim() || '$',
                
                // Policy Information
                cancellation_policy: document.getElementById('cancellation-policy')?.innerText.trim() || '',
                
                // Additional Information
                occupancy: document.getElementById('room-guest-count')?.innerText.trim() || '',
                room_info: `Plan: ${document.getElementById('meal-plan')?.innerText.trim() || ''}`,
                
                // Split lead guest name for sur_name and given_name
                lead_guest_name: document.getElementById('lead-guest')?.innerText.trim() || ''
            };
            
            // Split lead guest into sur_name and given_name
            const nameParts = updatedData.lead_guest_name.split(' ');
            updatedData.sur_name = nameParts.pop() || ''; // Last name
            updatedData.given_name = nameParts.join(' ') || ''; // First + middle names
            
            // Parse guest count from occupancy
            const guestCountMatch = updatedData.occupancy.match(/(\d+)\s*Guest\(s\)/);
            const totalGuests = guestCountMatch ? parseInt(guestCountMatch[1]) : updatedData.guest_names.length;
            
            updatedData.no_of_pax = [
                { type: "Adult", count: totalGuests.toString() },
                { type: "Child", count: "0" },
                { type: "Infant", count: "0" }
            ];

            // Format hotel address as array for API
            if (updatedData.hotel_address) {
                updatedData.hotel_address = [{
                    address_line_1: updatedData.hotel_address,
                    address_line_2: "",
                    address_city: "",
                    address_state: "",
                    address_zip_code: ""
                }];
            }

            // Add PCN and HCN (using PIN as HCN if not available)
            updatedData.pcn = document.getElementById('voucher-id')?.innerText.split(':')[1]?.trim() || '';
            updatedData.hcn = updatedData.pin;

            // Booking date
            updatedData.booking_date = new Date().toISOString().split('T')[0];
            updatedData.cancellation = updatedData.cancellation_policy;

            console.log("Sending data:", updatedData);

            // Send to update-hotel-json.php
            try {
                const response = await fetch(UPDATE_JSON, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(updatedData)
                });

                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }

                const result = await response.json();
                
                if (result.success) {
                    console.log("✅ Hotel data updated successfully:", result);
                } else {
                    console.error("❌ Update failed:", result.message);
                    alert("Warning: Could not update hotel data: " + (result.message || "Unknown error"));
                }
            } catch (error) {
                console.error("❌ Error updating hotel data:", error);
                alert("Warning: Could not connect to server. Data not saved. Error: " + error.message);
            }

            // Trigger Print
            window.print();
        }

        // Toggle fare summary function
        function toggleFareSummary() {
            const pricingArea = document.getElementById('pricing-area');
            const isChecked = document.getElementById('togglePricing').checked;
            if (pricingArea) {
                pricingArea.style.display = isChecked ? 'block' : 'none';
            }
        }

        // Update calculations function
        function updateCalculations(element) {
            let totalPrice = parseFloat(element.innerText.replace(/,/g, ''));
            
            if (!isNaN(totalPrice)) {
                let baseRate = (totalPrice * 0.83).toFixed(2);
                let tax = (totalPrice * 0.17).toFixed(2);

                const displayBaseRate = document.getElementById('display-base-rate');
                const displayTax = document.getElementById('display-tax');
                
                if (displayBaseRate) displayBaseRate.innerText = baseRate;
                if (displayTax) displayTax.innerText = tax;
            }
        }

        // Sync currency symbols
        document.addEventListener('DOMContentLoaded', function() {
            const currencyElement = document.getElementById('save-currency');
            if (currencyElement) {
                currencyElement.addEventListener('input', function() {
                    const newCurrency = this.innerText;
                    document.querySelectorAll('.save-currency').forEach(el => {
                        el.innerText = newCurrency;
                    });
                });
            }
        });
    </script>

</body>
</html>