<?php
include_once('./authenticate.php');
$ip_port = @file_get_contents('../ippath.txt');
if (empty($ip_port)) {
    $ip_port = "http://103.104.219.3:898";
}

// $getAllWorksApi = $ip_port . "api/works/all-works.php";

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Hotel Quotation</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
      <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://unpkg.com/sortablejs@1.14.0/Sortable.min.js"></script>
    <link rel="stylesheet" href="../assets/css/style.css">
  <style>
    body { font-family: 'Inter', system-ui, sans-serif; }
    .input {
      width: 100%;
      padding: 0.625rem 0.875rem;
      border: 1px solid #e5e7eb;
      border-radius: 0.5rem;
      font-size: 0.9rem;
      color: #0f172a;
      background: #fff;
      transition: all 0.15s ease;
    }
    .input:focus {
      outline: none;
      border-color: #0f172a;
      box-shadow: 0 0 0 3px rgba(15, 23, 42, 0.08);
    }
    .input[readonly] { background: #f9fafb; color: #6b7280; }
    .label {
      display: block;
      font-size: 0.8rem;
      font-weight: 600;
      color: #374151;
      margin-bottom: 0.35rem;
    }
    .card {
      background: #fff;
      border: 1px solid #e5e7eb;
      border-radius: 0.875rem;
      padding: 1.5rem;
      margin-bottom: 1.25rem;
    }
    .section-title {
      font-size: 1.05rem;
      font-weight: 600;
      color: #0f172a;
      margin-bottom: 1rem;
      display: flex;
      align-items: center;
      gap: 0.625rem;
    }
    .section-title .num {
      background: #0f172a;
      color: #fff;
      width: 1.5rem;
      height: 1.5rem;
      border-radius: 9999px;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      font-size: 0.75rem;
      font-weight: 600;
    }
    .btn-primary {
      background: #0f172a; color: #fff; padding: 0.7rem 1.4rem;
      border-radius: 0.5rem; font-weight: 600; font-size: 0.9rem;
      transition: all 0.15s ease; cursor: pointer; border: none;
    }
    .btn-primary:hover { background: #1e293b; }
    .btn-success {
      background: #16a34a; color: #fff; padding: 0.7rem 1.4rem;
      border-radius: 0.5rem; font-weight: 600; font-size: 0.9rem;
      transition: all 0.15s ease; cursor: pointer; border: none;
    }
    .btn-success:hover { background: #15803d; }
    .btn-copy {
      background: #fff; color: #0f172a; padding: 0.7rem 1.4rem;
      border: 1px solid #e5e7eb; border-radius: 0.5rem;
      font-weight: 600; font-size: 0.9rem; transition: all 0.15s ease; cursor: pointer;
    }
    .btn-copy:hover:not(:disabled) { background: #f9fafb; border-color: #cbd5e1; }
    .btn-copy:disabled { opacity: 0.5; cursor: not-allowed; }
    .btn-secondary {
      background: #fff; color: #0f172a; padding: 0.5rem 0.95rem;
      border: 1px solid #e5e7eb; border-radius: 0.45rem;
      font-weight: 500; font-size: 0.85rem; transition: all 0.15s ease; cursor: pointer;
    }
    .btn-secondary:hover { background: #f9fafb; border-color: #cbd5e1; }
    .btn-danger-sm {
      background: #fef2f2; color: #dc2626; padding: 0.35rem 0.7rem;
      border: 1px solid #fecaca; border-radius: 0.4rem;
      font-weight: 500; font-size: 0.78rem; transition: all 0.15s ease; cursor: pointer;
    }
    .btn-danger-sm:hover { background: #fee2e2; }
    .room-card {
      border: 1px solid #e5e7eb; border-radius: 0.625rem;
      padding: 1rem; margin-bottom: 0.875rem; background: #f9fafb;
    }
    .preview {
      width: 100%; height: 28rem; padding: 1rem;
      border: 1px solid #e5e7eb; border-radius: 0.5rem;
      font-family: 'Courier New', monospace; font-size: 0.82rem;
      line-height: 1.55; resize: vertical;
    }
    .preview-business { background: #f0fdf4; border-color: #bbf7d0; }
    .toast {
      position: fixed; top: 1.5rem; right: 1.5rem;
      padding: 0.85rem 1.15rem; border-radius: 0.5rem; color: #fff;
      font-weight: 500; font-size: 0.88rem;
      box-shadow: 0 8px 24px -8px rgba(0,0,0,0.2);
      z-index: 50; transform: translateX(120%); transition: transform 0.3s ease;
    }
    .toast.show { transform: translateX(0); }
    .toast.success { background: #16a34a; }
    .toast.error { background: #dc2626; }
    .save-status {
      display: inline-flex; align-items: center; gap: 0.4rem;
      padding: 0.35rem 0.75rem; border-radius: 9999px;
      font-size: 0.78rem; font-weight: 500;
    }
    .save-status.unsaved { background: #fef3c7; color: #92400e; }
    .save-status.saved { background: #d1fae5; color: #065f46; }
    .dot { width: 0.5rem; height: 0.5rem; border-radius: 9999px; }
    .save-status.unsaved .dot { background: #f59e0b; }
    .save-status.saved .dot { background: #10b981; }
  </style>
</head>
<body class="bg-gray-50 font-sans">
    <!-- Top Navigation -->
    <?php include '../elements/header.php'; ?>

    <!-- Sidebar -->
    <?php include '../elements/aside.php'; ?>
    
    <!-- Preview Modal -->
    <?php include '../elements/preview-model.php'; ?>
    
    <main id="mainContent" class="pt-16 pb-16 pl-64 md:pb-0 md:pl-16 lg:pl-64 transition-all duration-300">

        <div class="max-w-6xl mx-auto px-4 py-8">
        
          <div class="mb-6 flex items-center justify-between">
            <div>
              <h1 class="text-2xl font-bold text-slate-900">Hotel Quotation</h1>
              <p class="text-sm text-slate-500 mt-1">Create hotel quotations with rooms, pricing, and business markup.</p>
            </div>
            <div id="saveStatus" class="save-status unsaved">
              <span class="dot"></span>
              <span class="status-text">Unsaved</span>
            </div>
          </div>
        
          <!-- Screenshot Upload -->
          <div class="card">
            <div class="section-title"><span class="num">OP</span> Upload Screenshot (If Any Image of Hotel Booking)</div>
            <div class="flex flex-wrap items-center gap-3">
              <input type="file" id="screenshot" accept="image/*" class="text-sm flex-1 min-w-[200px]">
              <button onclick="extractScreenshot()" class="btn-success">Extract From Screenshot</button>
            </div>
          </div>

            <!--Basic Information-->
          <div class="card">
            <div class="section-title"><span class="num">1</span> Basic Information</div>
            <div class="grid grid-cols-2 gap-4 mb-5">
                <div>
                  <label class="label">Title</label>
                  <input id="quoteTitle" type="text" class="input">
                </div>
                <div>
                    <label class="label">Select a Client</label>
                    <?php include('form-selects/clients.php') ?>
                </div>
            </div>
          </div>
        
          <!-- Hotel Info -->
          <div class="card">
            <div class="section-title"><span class="num">2</span> Hotel Information</div>
        
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
              <div>
                <label class="label">Hotel Name</label>
                <input id="hotel_name" class="input" placeholder="e.g. Grand Hyatt Singapore">
              </div>
              <div>
                <label class="label">Address</label>
                <input id="address" class="input" placeholder="Full address">
              </div>
            </div>
        
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
              <div>
                <label class="label">Check In</label>
                <input id="check_in" type="date" class="input" onchange="calculateNights(); markUnsaved();">
              </div>
              <div>
                <label class="label">Check Out</label>
                <input id="check_out" type="date" class="input" onchange="calculateNights(); markUnsaved();">
              </div>
              <div>
                <label class="label">No of Nights</label>
                <input id="nights" readonly class="input">
              </div>
            </div>
          </div>
        
          <!-- Rooms -->
          <div class="card">
            <div class="flex items-center justify-between mb-4">
              <div class="section-title mb-0"><span class="num">3</span> Room Information</div>
              <button onclick="addRoom(); markUnsaved();" class="btn-secondary">+ Add Room</button>
            </div>
            <div id="rooms"></div>
          </div>
        
          <!-- Notes -->
          <div class="card">
            <div class="section-title"><span class="num">4</span> Notes</div>
        
            <div class="space-y-2 mb-4">
              <label class="flex items-center gap-2 cursor-pointer">
                <input type="checkbox" class="default_note w-4 h-4" value="City taxes payable at hotel" onchange="markUnsaved()">
                <span class="text-sm text-slate-700">City taxes payable at hotel</span>
              </label>
              <label class="flex items-center gap-2 cursor-pointer">
                <input type="checkbox" class="default_note w-4 h-4" value="Refundable security deposit applicable" onchange="markUnsaved()">
                <span class="text-sm text-slate-700">Refundable security deposit applicable</span>
              </label>
            </div>
        
            <div id="notes" class="mb-3"></div>
        
            <button onclick="addNote(); markUnsaved();" class="btn-secondary">+ Add Note</button>
          </div>
        
          <!-- Markup -->
          <div class="card">
            <div class="section-title"><span class="num">5</span> Business Markup</div>
            <div>
              <label class="label">Business Percentage (%)</label>
              <input id="percentage" type="number" min="0" step="0.01" value="0" class="input max-w-xs" oninput="generateQuotation(); markUnsaved();">
            </div>
          </div>
        
          <!-- Actions -->
          <div class="flex flex-wrap gap-3 items-center justify-end mb-6">
            <button onclick="generateQuotation()" class="btn-primary">Generate</button>
            <button onclick="saveQuotation()" class="btn-success">Save</button>
            <button id="copyBtn" onclick="copyBusinessQuotation()" class="btn-copy" disabled>Copy</button>
          </div>
        
          <!-- Previews -->
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label class="label flex items-center gap-2">
                Raw Formatted Quotation
                <span class="text-xs font-normal text-slate-500">(original prices)</span>
              </label>
              <textarea id="raw_preview" class="preview" readonly></textarea>
            </div>
            <div>
              <label class="label flex items-center gap-2">
                Business Quotation
                <span class="text-xs font-normal text-emerald-700">(with markup — to send)</span>
              </label>
              <textarea id="business_preview" class="preview preview-business" readonly></textarea>
            </div>
          </div>
        
        </div>
    
    </main>    
    
    <div id="toast" class="toast"></div>
    

    <script>
    let currentQuotationId = null;
    let currentQuotationUuid = null;
    let isSaved = false;
    
    function setSaveStatus(saved) {
      isSaved = saved;
      const el = document.getElementById('saveStatus');
      const txt = el.querySelector('.status-text');
      if (saved) {
        el.classList.remove('unsaved');
        el.classList.add('saved');
        txt.textContent = 'Saved';
        document.getElementById('copyBtn').disabled = false;
      } else {
        el.classList.remove('saved');
        el.classList.add('unsaved');
        txt.textContent = 'Unsaved';
        document.getElementById('copyBtn').disabled = true;
      }
    }
    
    function markUnsaved() {
      setSaveStatus(false);
    }
    
    document.addEventListener('input', markUnsaved);
    
    function showToast(msg, type = 'success') {
      const t = document.getElementById('toast');
      t.textContent = msg;
      t.className = `toast ${type} show`;
      setTimeout(() => t.classList.remove('show'), 3000);
    }
    
    function addRoom(data = {}) {
      const div = document.createElement('div');
      div.className = 'room-card';
    
      div.innerHTML = `
        <div class="flex items-center justify-between mb-3">
          <h3 class="font-semibold text-slate-800">Room Details</h3>
          <button type="button" class="btn-danger-sm">Remove</button>
        </div>
    
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-3">
          <div>
            <label class="label">Room Type</label>
            <input class="input room_type" value="${data.room_type || ''}" placeholder="e.g. Deluxe King">
          </div>
          <div>
            <label class="label">Room Size</label>
            <input class="input room_size" value="${data.room_size || ''}" placeholder="e.g. 32 sqm">
          </div>
        </div>
    
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-3">
          <div>
            <label class="label">No of Rooms</label>
            <input type="number" min="0" class="input no_rooms" value="${data.no_rooms || ''}">
          </div>
          <div>
            <label class="label">Adults</label>
            <input type="number" min="0" class="input adults" value="${data.adults || ''}">
          </div>
          <div>
            <label class="label">Child Count</label>
            <input type="number" min="0" class="input child_count" value="${data.child_count || ''}">
          </div>
          <div>
            <label class="label">Child Ages</label>
            <input class="input child_ages" placeholder="e.g. 4Y, 7Y" value="${data.child_ages || ''}">
          </div>
        </div>
    
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label class="label">Room Only Price (BDT)</label>
            <div class="flex gap-2">
              <input type="number" min="0" class="input room_only flex-1" value="${data.room_only || ''}">
              <select class="input room_only_type max-w-[140px]">
                <option value="Total">Total</option>
                <option value="PRPN">Per Room/Night</option>
              </select>
            </div>
          </div>
          <div>
            <label class="label">With Breakfast Price (BDT)</label>
            <div class="flex gap-2">
              <input type="number" min="0" class="input breakfast flex-1" value="${data.breakfast || ''}">
              <select class="input breakfast_type max-w-[140px]">
                <option value="Total">Total</option>
                <option value="PRPN">Per Room/Night</option>
              </select>
            </div>
          </div>
        </div>
      `;
    
      div.querySelector('button.btn-danger-sm').addEventListener('click', () => {
        div.remove();
        markUnsaved();
      });
    
      document.getElementById('rooms').appendChild(div);
    
      if (data.room_only_type) div.querySelector('.room_only_type').value = data.room_only_type;
      if (data.breakfast_type) div.querySelector('.breakfast_type').value = data.breakfast_type;
    }
    
    function addNote(value = '') {
      const div = document.createElement('div');
      div.className = 'flex gap-2 mb-2';
      div.innerHTML = `
        <input class="input note" placeholder="Note" value="${value}">
        <button type="button" class="btn-danger-sm">Remove</button>
      `;
      div.querySelector('button').addEventListener('click', () => {
        div.remove();
        markUnsaved();
      });
      document.getElementById('notes').appendChild(div);
    }
    
    function calculateNights() {
      const checkIn = new Date(document.getElementById('check_in').value);
      const checkOut = new Date(document.getElementById('check_out').value);
      if (!isNaN(checkIn) && !isNaN(checkOut)) {
        const diff = (checkOut - checkIn) / (1000 * 60 * 60 * 24);
        document.getElementById('nights').value = diff > 0 ? diff : 0;
      }
    }
    
    function formatDate(dateValue) {
      if (!dateValue) return '';
      const d = new Date(dateValue);
      if (isNaN(d)) return dateValue;
      return d.toLocaleDateString('en-GB', { day: '2-digit', month: 'short' });
    }
    
    function formatPrice(p) {
      return Number(p).toLocaleString();
    }
    
    function addPercentage(price, percentage) {
      const base = Number(price);
      const percent = Number(percentage || 0);
      if (!base || base <= 0) return '';
      return Math.round(base + ((base * percent) / 100));
    }
    
    function collectData() {
      const rooms = [...document.querySelectorAll('.room-card')].map(room => ({
        room_type: room.querySelector('.room_type').value,
        room_size: room.querySelector('.room_size').value,
        no_rooms: room.querySelector('.no_rooms').value,
        adults: room.querySelector('.adults').value,
        child_count: room.querySelector('.child_count').value,
        child_ages: room.querySelector('.child_ages').value,
        room_only: room.querySelector('.room_only').value,
        room_only_type: room.querySelector('.room_only_type').value,
        breakfast: room.querySelector('.breakfast').value,
        breakfast_type: room.querySelector('.breakfast_type').value
      }));
    
      const manualNotes = [...document.querySelectorAll('.note')].map(n => n.value).filter(Boolean);
      const defaultNotes = [...document.querySelectorAll('.default_note:checked')].map(n => n.value);
    
      return {
        hotel_name: document.getElementById('hotel_name').value,
        address: document.getElementById('address').value,
        check_in: document.getElementById('check_in').value,
        check_out: document.getElementById('check_out').value,
        nights: document.getElementById('nights').value,
        percentage: document.getElementById('percentage').value || 0,
        rooms,
        notes: [...defaultNotes, ...manualNotes]
      };
    }
    
    function makeQuotationText(data, useBusinessPrice = false) {
      let text = `*${data.hotel_name}*\n`;
      text += `Address: ${data.address}\n\n`;
      text += `C/In: ${formatDate(data.check_in)} | C/Out: ${formatDate(data.check_out)} | ${data.nights} Nights\n`;
    
      data.rooms.forEach(room => {
        text += `${room.no_rooms} Room | ${room.adults} Adults`;
        if (Number(room.child_count) > 0) {
          text += ` + ${room.child_count} Child (${room.child_ages})`;
        }
        text += `\n\n${room.room_type}\n`;
        if (room.room_size) text += `Room Size: ${room.room_size}\n`;
        text += `Breakfast Included | Non-Refundable\n\n`;
    
        let hasPrice = false;
    
        const roomOnlyPrice = useBusinessPrice ? addPercentage(room.room_only, data.percentage) : room.room_only;
        const breakfastPrice = useBusinessPrice ? addPercentage(room.breakfast, data.percentage) : room.breakfast;
    
        if (roomOnlyPrice && Number(roomOnlyPrice) > 0) {
          if (!hasPrice) { text += `*Price:*\n`; hasPrice = true; }
          text += `- Room Only: BDT ${formatPrice(roomOnlyPrice)} (${room.room_only_type})\n`;
        }
    
        if (breakfastPrice && Number(breakfastPrice) > 0) {
          if (!hasPrice) { text += `*Price:*\n`; hasPrice = true; }
          text += `- With breakfast: BDT ${formatPrice(breakfastPrice)} (${room.breakfast_type})\n`;
        }
    
        text += `\n`;
      });
    
      if (data.notes.length) {
        text += `*Note:*\n`;
        data.notes.forEach(n => { text += `• ${n}\n`; });
      }
    
      return text;
    }
    
    function generateQuotation() {
      const data = collectData();
      document.getElementById('raw_preview').value = makeQuotationText(data, false);
      document.getElementById('business_preview').value = makeQuotationText(data, true);
      markUnsaved();
    }
    
    async function saveQuotation() {
      generateQuotation();
    
      const data = collectData();
      const clientValue = document.getElementById('clientInput').value;
      const quoteTitle = document.getElementById('quoteTitle').value;
      const rawQuotation = document.getElementById('raw_preview').value;
      const businessQuotation = document.getElementById('business_preview').value;
    
      try {
        const response = await fetch('../api/hotels/store-quotation.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({
            id: currentQuotationId,
            uuid: currentQuotationUuid,
            client: clientValue,
            title: quoteTitle,
            informations: rawQuotation,
            quotations: businessQuotation,
            percentage: data.percentage,
            form_data: data
          })
        });
    
        const result = await response.json();
    
        if (result.success) {
          currentQuotationId = result.id;
          currentQuotationUuid = result.uuid;
          setSaveStatus(true);
          showToast(result.message);
        } else {
          showToast(result.message || 'Something went wrong', 'error');
        }
      } catch (err) {
        showToast('Network error: ' + err.message, 'error');
      }
    }
    
    async function copyBusinessQuotation() {
      if (!isSaved) {
        showToast('Please save first before copying.', 'error');
        return;
      }
    
      const businessQuotation = document.getElementById('business_preview').value;
    
      if (!businessQuotation.trim()) {
        showToast('Nothing to copy', 'error');
        return;
      }
    
      try {
        await navigator.clipboard.writeText(businessQuotation);
        showToast('Business quotation copied');
      } catch (err) {
        showToast('Copy failed', 'error');
      }
    }
    
    async function extractScreenshot() {
      const file = document.getElementById('screenshot').files[0];
      if (!file) { showToast('Please upload a screenshot', 'error'); return; }
    
      const fd = new FormData();
      fd.append('screenshot', file);
    
      showToast('Extracting…');
    
      try {
        const response = await fetch('../api/hotels/ss-extraction.php', { method: 'POST', body: fd });
        const result = await response.json();
    
        if (!result.success) {
          showToast(result.message || 'Extraction failed', 'error');
          return;
        }
    
        fillForm(result.data);
        showToast('Extraction complete');
      } catch (err) {
        showToast('Network error: ' + err.message, 'error');
      }
    }
    
    function fillForm(data) {
      document.getElementById('hotel_name').value = data.hotel_name || '';
      document.getElementById('address').value = data.address || '';
      document.getElementById('check_in').value = data.check_in || '';
      document.getElementById('check_out').value = data.check_out || '';
    
      calculateNights();
    
      document.getElementById('rooms').innerHTML = '';
    
      if (data.rooms && data.rooms.length) {
        data.rooms.forEach(room => addRoom(room));
      } else {
        addRoom();
      }
    
      document.getElementById('notes').innerHTML = '';
      if (data.notes) data.notes.forEach(n => addNote(n));
    
      generateQuotation();
      markUnsaved();
    }
    
    // Init
    addRoom();
    setSaveStatus(false);
    </script>
    
</body>
</html>
