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
  <title>Air Ticket Quotation</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://unpkg.com/sortablejs@1.14.0/Sortable.min.js"></script>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        body {
          font-family: 'Inter', system-ui, sans-serif;
        }
    
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
    
        .input[readonly] {
          background: #f9fafb;
          color: #6b7280;
        }
    
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
    
        .pill-group {
          display: flex;
          flex-wrap: wrap;
          gap: 0.5rem;
        }
    
        .pill-group input {
          display: none;
        }
    
        .pill-group label {
          cursor: pointer;
          padding: 0.55rem 1rem;
          border: 1px solid #e5e7eb;
          border-radius: 9999px;
          font-size: 0.85rem;
          font-weight: 500;
          color: #374151;
          transition: all 0.15s ease;
          user-select: none;
          background: #fff;
        }
    
        .pill-group input:checked+label {
          background: #0f172a;
          color: #fff;
          border-color: #0f172a;
        }
    
        .btn-primary {
          background: #0f172a;
          color: #fff;
          padding: 0.7rem 1.4rem;
          border-radius: 0.5rem;
          font-weight: 600;
          font-size: 0.9rem;
          transition: all 0.15s ease;
          cursor: pointer;
          border: none;
        }
    
        .btn-primary:hover {
          background: #1e293b;
        }
    
        .btn-success {
          background: #16a34a;
          color: #fff;
          padding: 0.7rem 1.4rem;
          border-radius: 0.5rem;
          font-weight: 600;
          font-size: 0.9rem;
          transition: all 0.15s ease;
          cursor: pointer;
          border: none;
        }
    
        .btn-success:hover {
          background: #15803d;
        }
    
        .btn-copy {
          background: #fff;
          color: #0f172a;
          padding: 0.7rem 1.4rem;
          border: 1px solid #e5e7eb;
          border-radius: 0.5rem;
          font-weight: 600;
          font-size: 0.9rem;
          transition: all 0.15s ease;
          cursor: pointer;
        }
    
        .btn-copy:hover:not(:disabled) {
          background: #f9fafb;
          border-color: #cbd5e1;
        }
    
        .btn-copy:disabled {
          opacity: 0.5;
          cursor: not-allowed;
        }
    
        .btn-secondary {
          background: #fff;
          color: #0f172a;
          padding: 0.5rem 0.95rem;
          border: 1px solid #e5e7eb;
          border-radius: 0.45rem;
          font-weight: 500;
          font-size: 0.85rem;
          transition: all 0.15s ease;
          cursor: pointer;
        }
    
        .btn-secondary:hover {
          background: #f9fafb;
          border-color: #cbd5e1;
        }
    
        .btn-danger-sm {
          background: #fef2f2;
          color: #dc2626;
          padding: 0.35rem 0.7rem;
          border: 1px solid #fecaca;
          border-radius: 0.4rem;
          font-weight: 500;
          font-size: 0.78rem;
          transition: all 0.15s ease;
          cursor: pointer;
        }
    
        .btn-danger-sm:hover {
          background: #fee2e2;
        }
    
        .segment-card {
          border: 1px solid #e5e7eb;
          border-radius: 0.625rem;
          padding: 1rem;
          margin-bottom: 0.875rem;
          background: #f9fafb;
        }
    
        .connection-card {
          border: 1px dashed #cbd5e1;
          border-radius: 0.5rem;
          padding: 0.875rem;
          margin-bottom: 0.625rem;
          background: #fff;
        }
    
        .transit-row {
          background: #fef3c7;
          border: 1px solid #fde68a;
          padding: 0.75rem 1rem;
          border-radius: 0.5rem;
          margin: 0.75rem 0;
        }
    
        .price-card {
          border: 1px solid #e5e7eb;
          border-radius: 0.625rem;
          padding: 1rem;
          margin-bottom: 0.875rem;
          background: #f9fafb;
        }
    
        .preview {
          width: 100%;
          height: 28rem;
          padding: 1rem;
          border: 1px solid #e5e7eb;
          border-radius: 0.5rem;
          font-family: 'Courier New', monospace;
          font-size: 0.82rem;
          line-height: 1.55;
          resize: vertical;
        }
    
        .preview-business {
          background: #f0fdf4;
          border-color: #bbf7d0;
        }
    
        .toast {
          position: fixed;
          top: 1.5rem;
          right: 1.5rem;
          padding: 0.85rem 1.15rem;
          border-radius: 0.5rem;
          color: #fff;
          font-weight: 500;
          font-size: 0.88rem;
          box-shadow: 0 8px 24px -8px rgba(0, 0, 0, 0.2);
          z-index: 50;
          transform: translateX(120%);
          transition: transform 0.3s ease;
        }
    
        .toast.show {
          transform: translateX(0);
        }
    
        .toast.success {
          background: #16a34a;
        }
    
        .toast.error {
          background: #dc2626;
        }
    
        .save-status {
          display: inline-flex;
          align-items: center;
          gap: 0.4rem;
          padding: 0.35rem 0.75rem;
          border-radius: 9999px;
          font-size: 0.78rem;
          font-weight: 500;
        }
    
        .save-status.unsaved {
          background: #fef3c7;
          color: #92400e;
        }
    
        .save-status.saved {
          background: #d1fae5;
          color: #065f46;
        }
    
        .dot {
          width: 0.5rem;
          height: 0.5rem;
          border-radius: 9999px;
        }
    
        .save-status.unsaved .dot {
          background: #f59e0b;
        }
    
        .save-status.saved .dot {
          background: #10b981;
        }
    
        .switch {
          position: relative;
          display: inline-block;
          width: 40px;
          height: 22px;
        }
    
        .switch input {
          opacity: 0;
          width: 0;
          height: 0;
        }
    
        .slider {
          position: absolute;
          cursor: pointer;
          inset: 0;
          background: #cbd5e1;
          border-radius: 9999px;
          transition: 0.2s;
        }
    
        .slider::before {
          content: "";
          position: absolute;
          height: 16px;
          width: 16px;
          left: 3px;
          top: 3px;
          background: #fff;
          border-radius: 50%;
          transition: 0.2s;
        }
    
        .switch input:checked+.slider {
          background: #0f172a;
        }
    
        .switch input:checked+.slider::before {
          transform: translateX(18px);
        }
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
                <h1 class="text-2xl font-bold text-slate-900">Air Ticket Quotation</h1>
                <p class="text-sm text-slate-500 mt-1">Create flight quotations for One Way, Round Trip, and Multi City routes.</p>
              </div>
              <div id="saveStatus" class="save-status unsaved">
                <span class="dot"></span>
                <span class="status-text">Unsaved</span>
              </div>
            </div>
        
            <!-- Screenshot Upload -->
            <div class="card">
              <div class="section-title"><span class="num">1</span> Upload Screenshot (optional)</div>
              <div class="flex flex-wrap items-center gap-3">
                <input type="file" id="screenshot" accept="image/*" class="text-sm flex-1 min-w-[200px]">
                <button onclick="extractScreenshot()" class="btn-success">Extract From Screenshot</button>
              </div>
            </div>
        
            <!-- General Info -->
            <div class="card">
              <div class="section-title"><span class="num">2</span> General Information</div>
        
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
        
              <div class="mb-5">
                <label class="label">Trip Type</label>
                <div class="pill-group">
                  <input type="radio" name="trip_option" id="trip_oneway" value="One Way" checked>
                  <label for="trip_oneway">One Way Ticket</label>
                  <input type="radio" name="trip_option" id="trip_round" value="Round Trip">
                  <label for="trip_round">Round Trip Ticket</label>
                  <input type="radio" name="trip_option" id="trip_multi" value="Multi City">
                  <label for="trip_multi">Multi City Ticket</label>
                </div>
              </div>
        
              <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-5">
                <div>
                  <label class="label">Route</label>
                  <input id="route" class="input" placeholder="e.g. DAC - MNL">
                </div>
                <div>
                  <label class="label">Class</label>
                  <div class="pill-group">
                    <input type="radio" name="class" id="cls_eco" value="Economy" checked>
                    <label for="cls_eco">Economy</label>
                    <input type="radio" name="class" id="cls_biz" value="Business">
                    <label for="cls_biz">Business</label>
                  </div>
                </div>
              </div>
        
              <div class="grid grid-cols-3 gap-4">
                <div>
                  <label class="label">Adult</label>
                  <input id="pax_adult" type="number" min="0" value="1" class="input">
                </div>
                <div>
                  <label class="label">Child</label>
                  <input id="pax_child" type="number" min="0" value="0" class="input">
                </div>
                <div>
                  <label class="label">Infant</label>
                  <input id="pax_infant" type="number" min="0" value="0" class="input">
                </div>
              </div>
            </div>
        
            <!-- Flight Segments -->
            <div class="card">
              <div class="flex items-center justify-between mb-4">
                <div class="section-title mb-0"><span class="num">3</span> Flight Segments</div>
                <button id="add_segment_btn" onclick="addMultiCitySegment(); markUnsaved();" class="btn-secondary hidden">+ Add Segment</button>
              </div>
              <div id="segments"></div>
            </div>
        
            <!-- Pricing -->
            <div class="card">
              <div class="section-title"><span class="num">4</span> Pricing</div>
        
              <div class="price-card">
                <div class="mb-3">
                  <label class="label">Baggage Option 1 Description</label>
                  <input id="baggage_1_desc" class="input" value="Without Baggage">
                </div>
                <div class="grid grid-cols-3 gap-4">
                  <div>
                    <label class="label">Adult Price (BDT)</label>
                    <input id="price_1_adult" type="number" min="0" class="input">
                  </div>
                  <div>
                    <label class="label">Child Price (BDT)</label>
                    <input id="price_1_child" type="number" min="0" class="input">
                  </div>
                  <div>
                    <label class="label">Infant Price (BDT)</label>
                    <input id="price_1_infant" type="number" min="0" class="input">
                  </div>
                </div>
              </div>
        
              <div class="price-card">
                <div class="mb-3">
                  <label class="label">Baggage Option 2 Description</label>
                  <input id="baggage_2_desc" class="input" value="With 30 Kg Check-IN + 7 Kg Cabin Baggage">
                </div>
                <div class="grid grid-cols-3 gap-4">
                  <div>
                    <label class="label">Adult Price (BDT)</label>
                    <input id="price_2_adult" type="number" min="0" class="input">
                  </div>
                  <div>
                    <label class="label">Child Price (BDT)</label>
                    <input id="price_2_child" type="number" min="0" class="input">
                  </div>
                  <div>
                    <label class="label">Infant Price (BDT)</label>
                    <input id="price_2_infant" type="number" min="0" class="input">
                  </div>
                </div>
              </div>
            </div>
        
            <!-- Conditions & Notes -->
            <div class="card">
              <div class="section-title"><span class="num">5</span> Conditions & Notes</div>
        
              <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-5">
                <div>
                  <label class="label">Refundable Status</label>
                  <div class="pill-group">
                    <input type="radio" name="refundable_status" id="ref_yes" value="Refundable" checked>
                    <label for="ref_yes">Refundable</label>
                    <input type="radio" name="refundable_status" id="ref_no" value="Non Refundable">
                    <label for="ref_no">Non Refundable</label>
                  </div>
                </div>
                <div>
                  <label class="label">Changeable Status</label>
                  <div class="pill-group">
                    <input type="radio" name="changeable_status" id="chg_yes" value="Changeable" checked>
                    <label for="chg_yes">Changeable</label>
                    <input type="radio" name="changeable_status" id="chg_no" value="Not Changeable">
                    <label for="chg_no">Not Changeable</label>
                  </div>
                </div>
              </div>
        
              <div class="space-y-2 mb-4">
                <label class="flex items-center gap-2 cursor-pointer">
                  <input type="checkbox" class="default_note w-4 h-4" value="Subject to availability at the time of booking" onchange="markUnsaved()">
                  <span class="text-sm text-slate-700">Subject to availability at the time of booking</span>
                </label>
                <label class="flex items-center gap-2 cursor-pointer">
                  <input type="checkbox" class="default_note w-4 h-4" value="Fare may change without prior notice" onchange="markUnsaved()">
                  <span class="text-sm text-slate-700">Fare may change without prior notice</span>
                </label>
              </div>
        
              <div id="notes" class="mb-3"></div>
        
              <button onclick="addNote(); markUnsaved();" class="btn-secondary">+ Add Note</button>
            </div>
        
            <!-- Markup -->
            <div class="card">
              <div class="section-title"><span class="num">6</span> Business Markup</div>
              <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                  <label class="label">Business Percentage (%)</label>
                  <input id="percentage" type="number" min="0" step="0.01" value="0" class="input" oninput="generateQuotation(); markUnsaved();">
                </div>
                <div>
                  <label class="label">Vendor Fixed Price (BDT)</label>
                  <input id="ve_fixed_price" type="number" min="0" step="0.01" value="0" class="input" oninput="generateQuotation(); markUnsaved();">
                </div>
              </div>
              <p class="text-xs text-slate-500 mt-3">
                Formula: <span class="font-mono">final = (base × (1 + %/100)) + fixed</span>
              </p>
            </div>
        
            <!-- Actions -->
            <div class="flex flex-wrap gap-3 items-center justify-end mb-6">
              <button onclick="generateQuotation()" class="btn-primary">Generate</button>
              <button onclick="saveQuotation()" class="btn-success">Save</button>
              <button id="copyBtn" onclick="copyBusinessQuotation()" class="btn-copy" disabled>Copy</button>
            </div>
        
            <!-- Preview Boxes -->
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
        
        <div id="toast" class="toast"></div>
    
    </main>  
    
    <!-- Floating Quick Access Tab -->
    <?php include '../elements/floating-menus.php'; ?>

    <script src="../assets/js/script.js?time=<?php echo time(); ?>"></script>

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
    document.addEventListener('change', (e) => {
      if (e.target.matches('input[type="radio"], input[type="checkbox"]')) markUnsaved();
    });

    function showToast(msg, type = 'success') {
      const t = document.getElementById('toast');
      t.textContent = msg;
      t.className = `toast ${type} show`;
      setTimeout(() => t.classList.remove('show'), 3000);
    }

    // ─── Connection block (sub-flight after a transit) ──────────────

    function buildConnectionHTML(data = {}) {
      const div = document.createElement('div');
      div.className = 'connection-card';

      div.innerHTML = `
        <div class="flex items-center justify-between mb-2">
        <span class="text-xs font-semibold text-slate-600 uppercase tracking-wide">Connection Flight</span>
        <button type="button" class="btn-danger-sm remove-conn">Remove</button>
        </div>
        
        <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-3">
        <div>
        <label class="label">Dep Airport</label>
        <input class="input conn_dep_airport" placeholder="SIN" value="${data.dep_airport || ''}">
        </div>
        <div>
        <label class="label">Dep Time</label>
        <input type="time" class="input conn_dep_time" value="${data.dep_time || ''}">
        </div>
        <div>
        <label class="label">Arr Airport</label>
        <input class="input conn_arr_airport" placeholder="MNL" value="${data.arr_airport || ''}">
        </div>
        <div>
        <label class="label">Arr Time</label>
        <input type="time" class="input conn_arr_time" value="${data.arr_time || ''}">
        </div>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
        <div class="flex items-end pb-1">
        <label class="flex items-center gap-2 cursor-pointer">
        <input type="checkbox" class="conn_arr_day_indicator w-4 h-4" ${data.arr_day_indicator ? 'checked' : ''}>
        <span class="text-sm text-slate-700">Next day (+1)</span>
        </label>
        </div>
        <div class="md:col-span-2">
        <label class="label">Flight No (optional)</label>
        <input class="input conn_flight_no" placeholder="e.g. SQ44" value="${data.flight_no || ''}">
        </div>
        </div>
    `;

      div.querySelector('.remove-conn').addEventListener('click', () => {
        div.remove();
        markUnsaved();
      });

      return div;
    }

    // ─── Segment block (primary flight + optional transit + connections) ──

    function buildSegmentHTML(data, heading, removable) {
      const div = document.createElement('div');
      div.className = 'segment-card';

      const hasTransit = data.has_transit === true || data.has_transit === 'true';

      div.innerHTML = `
        <div class="flex items-center justify-between mb-3">
        <h3 class="font-semibold text-slate-800 seg-heading">${heading}</h3>
        ${removable ? '<button type="button" class="btn-danger-sm remove-seg">Remove</button>' : ''}
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-3">
        <div>
        <label class="label">Segment Title</label>
        <input class="input segment_title" placeholder="e.g. DAC-MNL" value="${data.segment_title || ''}">
        </div>
        <div>
        <label class="label">Date</label>
        <input type="date" class="input segment_date" value="${data.date || ''}">
        </div>
        </div>
        
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-3">
        <div>
        <label class="label">Airline</label>
        <input class="input airline" placeholder="e.g. SQ" value="${data.airline || ''}">
        </div>
        <div>
        <label class="label">Flight No</label>
        <input class="input flight_no" placeholder="e.g. SQ988" value="${data.flight_no || ''}">
        </div>
        <div class="flex items-end pb-1">
        <label class="flex items-center gap-2 cursor-pointer">
        <input type="checkbox" class="arr_day_indicator w-4 h-4" ${data.arr_day_indicator ? 'checked' : ''}>
        <span class="text-sm text-slate-700">Arr Next day (+1)</span>
        </label>
        </div>
        <div></div>
        </div>
        
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-2">
        <div>
        <label class="label">Dep Airport</label>
        <input class="input dep_airport" placeholder="DAC" value="${data.dep_airport || ''}">
        </div>
        <div>
        <label class="label">Dep Time</label>
        <input type="time" class="input dep_time" value="${data.dep_time || ''}">
        </div>
        <div>
        <label class="label">Arr Airport</label>
        <input class="input arr_airport" placeholder="SIN" value="${data.arr_airport || ''}">
        </div>
        <div>
        <label class="label">Arr Time</label>
        <input type="time" class="input arr_time" value="${data.arr_time || ''}">
        </div>
        </div>
        
        <!-- Has Transit toggle -->
        <div class="flex items-center justify-between mt-4 pt-3 border-t border-slate-200">
        <div class="flex items-center gap-3">
        <span class="text-sm font-medium text-slate-700">Has Transit?</span>
        <label class="switch">
        <input type="checkbox" class="has_transit" ${hasTransit ? 'checked' : ''}>
        <span class="slider"></span>
        </label>
        <span class="transit-state-label text-xs font-medium ${hasTransit ? 'text-emerald-700' : 'text-slate-500'}">${hasTransit ? 'Yes' : 'No'}</span>
        </div>
        <button type="button" class="btn-secondary add-conn-btn ${hasTransit ? '' : 'hidden'}">+ Add Connection Flight</button>
        </div>
        
        <!-- Transit wrapper (shown only when toggle is ON) -->
        <div class="transit-wrapper ${hasTransit ? '' : 'hidden'}">
        <div class="transit-row mt-3">
        <label class="label">Transit Duration</label>
        <input class="input transit_time" placeholder="e.g. Tr 3 Hours" value="${data.transit_time || ''}">
        </div>
        <div class="connections-container mt-3"></div>
        </div>
    `;

      // wire up segment remove
      if (removable) {
        div.querySelector('.remove-seg').addEventListener('click', () => {
          div.remove();
          renumberMultiCity();
          markUnsaved();
        });
      }

      // wire up Has Transit toggle
      const toggle = div.querySelector('.has_transit');
      const transitWrapper = div.querySelector('.transit-wrapper');
      const addConnBtn = div.querySelector('.add-conn-btn');
      const stateLabel = div.querySelector('.transit-state-label');

      toggle.addEventListener('change', () => {
        const on = toggle.checked;
        transitWrapper.classList.toggle('hidden', !on);
        addConnBtn.classList.toggle('hidden', !on);
        stateLabel.textContent = on ? 'Yes' : 'No';
        stateLabel.classList.toggle('text-emerald-700', on);
        stateLabel.classList.toggle('text-slate-500', !on);

        // If toggled OFF, clear connections (so leftover data doesn't get saved)
        if (!on) {
          div.querySelector('.connections-container').innerHTML = '';
          div.querySelector('.transit_time').value = '';
        }
        markUnsaved();
      });

      // wire up add-connection
      const connContainer = div.querySelector('.connections-container');
      addConnBtn.addEventListener('click', () => {
        connContainer.appendChild(buildConnectionHTML({}));
        markUnsaved();
      });

      // restore connections if data has them and transit is on
      if (hasTransit && Array.isArray(data.connections)) {
        data.connections.forEach(conn => {
          connContainer.appendChild(buildConnectionHTML(conn));
        });
      }

      return div;
    }

    function renumberMultiCity() {
      const tripType = document.querySelector('input[name="trip_option"]:checked').value;
      if (tripType !== 'Multi City') return;
      [...document.querySelectorAll('.segment-card')].forEach((seg, i) => {
        seg.querySelector('.seg-heading').textContent = `Route ${i + 1}`;
      });
    }

    function addMultiCitySegment(data = {}) {
      const container = document.getElementById('segments');
      const nextNum = container.children.length + 1;
      const removable = nextNum > 1;
      const node = buildSegmentHTML(data, `Route ${nextNum}`, removable);
      container.appendChild(node);
      return node;
    }

    function renderSegments() {
      const tripType = document.querySelector('input[name="trip_option"]:checked').value;
      const container = document.getElementById('segments');
      container.innerHTML = '';

      const addBtn = document.getElementById('add_segment_btn');

      if (tripType === 'One Way') {
        container.appendChild(buildSegmentHTML({}, 'Flight', false));
        addBtn.classList.add('hidden');
      } else if (tripType === 'Round Trip') {
        container.appendChild(buildSegmentHTML({}, 'Outbound Flight', false));
        container.appendChild(buildSegmentHTML({}, 'Inbound Flight', false));
        addBtn.classList.add('hidden');
      } else {
        container.appendChild(buildSegmentHTML({}, 'Route 1', false));
        container.appendChild(buildSegmentHTML({}, 'Route 2', true));
        addBtn.classList.remove('hidden');
      }
    }

    document.querySelectorAll('input[name="trip_option"]').forEach(r => {
      r.addEventListener('change', () => {
        renderSegments();
        markUnsaved();
      });
    });

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

    // ─── Formatting Helpers ─────────────────────────────────────────

    function formatDate(dateValue) {
      if (!dateValue) return '';
      const d = new Date(dateValue);
      if (isNaN(d)) return dateValue;
      return d.toLocaleDateString('en-GB', {
        day: '2-digit',
        month: 'short'
      });
    }

    function formatTime(timeValue) {
      if (!timeValue) return '';
      const [h, m] = timeValue.split(':').map(Number);
      if (isNaN(h) || isNaN(m)) return timeValue;
      const period = h >= 12 ? 'PM' : 'AM';
      const h12 = h % 12 || 12;
      const mm = String(m).padStart(2, '0');
      return `${String(h).padStart(2,'0')}:${mm} (${String(h12).padStart(2,'0')}:${mm}${period})`;
    }

    function formatPrice(p) {
      return Number(p).toLocaleString();
    }

    function applyMarkup(price, percentage, fixed) {
      const base = Number(price);
      if (!base || base <= 0) return '';
      const pct = Number(percentage || 0);
      const fix = Number(fixed || 0);
      return Math.round(base + ((base * pct) / 100) + fix);
    }

    // ─── Data Collection ────────────────────────────────────────────

    function collectData() {
      const segments = [...document.querySelectorAll('.segment-card')].map(seg => {
        const hasTransit = seg.querySelector('.has_transit').checked;

        const connections = hasTransit ?
          [...seg.querySelectorAll('.connection-card')].map(c => ({
            dep_airport: c.querySelector('.conn_dep_airport').value,
            dep_time: c.querySelector('.conn_dep_time').value,
            arr_airport: c.querySelector('.conn_arr_airport').value,
            arr_time: c.querySelector('.conn_arr_time').value,
            arr_day_indicator: c.querySelector('.conn_arr_day_indicator').checked,
            flight_no: c.querySelector('.conn_flight_no').value
          })) :
          [];

        return {
          heading: seg.querySelector('.seg-heading').textContent,
          segment_title: seg.querySelector('.segment_title').value,
          date: seg.querySelector('.segment_date').value,
          airline: seg.querySelector('.airline').value,
          flight_no: seg.querySelector('.flight_no').value,
          dep_airport: seg.querySelector('.dep_airport').value,
          dep_time: seg.querySelector('.dep_time').value,
          arr_airport: seg.querySelector('.arr_airport').value,
          arr_time: seg.querySelector('.arr_time').value,
          arr_day_indicator: seg.querySelector('.arr_day_indicator').checked,
          has_transit: hasTransit,
          transit_time: hasTransit ? seg.querySelector('.transit_time').value : '',
          connections
        };
      });

      const manualNotes = [...document.querySelectorAll('.note')].map(n => n.value).filter(Boolean);
      const defaultNotes = [...document.querySelectorAll('.default_note:checked')].map(n => n.value);

      return {
        trip_option: document.querySelector('input[name="trip_option"]:checked').value,
        route: document.getElementById('route').value,
        class: document.querySelector('input[name="class"]:checked').value,
        pax_adult: document.getElementById('pax_adult').value,
        pax_child: document.getElementById('pax_child').value,
        pax_infant: document.getElementById('pax_infant').value,
        segments,
        baggage_1_desc: document.getElementById('baggage_1_desc').value,
        price_1_adult: document.getElementById('price_1_adult').value,
        price_1_child: document.getElementById('price_1_child').value,
        price_1_infant: document.getElementById('price_1_infant').value,
        baggage_2_desc: document.getElementById('baggage_2_desc').value,
        price_2_adult: document.getElementById('price_2_adult').value,
        price_2_child: document.getElementById('price_2_child').value,
        price_2_infant: document.getElementById('price_2_infant').value,
        refundable_status: document.querySelector('input[name="refundable_status"]:checked').value,
        changeable_status: document.querySelector('input[name="changeable_status"]:checked').value,
        percentage: document.getElementById('percentage').value || 0,
        ve_fixed_price: document.getElementById('ve_fixed_price').value || 0,
        notes: [...defaultNotes, ...manualNotes]
      };
    }

    function makeQuotationText(data, useBusinessPrice = false) {
      const tripLabel = data.trip_option === 'One Way' ? 'One Way Ticket' :
        data.trip_option === 'Round Trip' ? 'Round Ticket' :
        'Multi City Ticket';

      let text = `*${tripLabel}*\n`;

      const paxParts = [];
      if (Number(data.pax_adult) > 0) paxParts.push(`${data.pax_adult} Adult`);
      if (Number(data.pax_child) > 0) paxParts.push(`${data.pax_child} Child`);
      if (Number(data.pax_infant) > 0) paxParts.push(`${data.pax_infant} Infant`);

      text += `Route: ${data.route} ${paxParts.join(', ')}\n`;
      text += `${data.class}\n\n`;

      data.segments.forEach(seg => {
        text += `*${seg.heading}:*\n`;
        if (seg.segment_title) text += `${seg.segment_title}\n`;
        text += `${formatDate(seg.date)} | ${seg.flight_no || seg.airline}\n`;
        const nextDay = seg.arr_day_indicator ? ' (+1)' : '';
        text += `${seg.dep_airport} ${formatTime(seg.dep_time)} -> ${seg.arr_airport} ${formatTime(seg.arr_time)}${nextDay}\n`;

        // Transit + connection flights
        if (seg.has_transit) {
          if (seg.transit_time) text += `${seg.transit_time}\n`;

          (seg.connections || []).forEach(conn => {
            const connNextDay = conn.arr_day_indicator ? ' (+1)' : '';
            const tag = conn.flight_no ? `${conn.flight_no} | ` : '';
            text += `${tag}${conn.dep_airport} ${formatTime(conn.dep_time)} -> ${conn.arr_airport} ${formatTime(conn.arr_time)}${connNextDay}\n`;
          });
        }

        text += `\n`;
      });

      text += `*Price:*\n`;

      const p1a = useBusinessPrice ? applyMarkup(data.price_1_adult, data.percentage, data.ve_fixed_price) : data.price_1_adult;
      const p1c = useBusinessPrice ? applyMarkup(data.price_1_child, data.percentage, data.ve_fixed_price) : data.price_1_child;
      const p1i = useBusinessPrice ? applyMarkup(data.price_1_infant, data.percentage, data.ve_fixed_price) : data.price_1_infant;

      if (Number(p1a) > 0 || Number(p1c) > 0 || Number(p1i) > 0) {
        text += `• ${data.baggage_1_desc}: BDT ->`;
        const parts = [];
        if (Number(p1a) > 0) parts.push(`Adult ${formatPrice(p1a)}`);
        if (Number(p1c) > 0) parts.push(`Child ${formatPrice(p1c)}`);
        if (Number(p1i) > 0) parts.push(`Infant ${formatPrice(p1i)}`);
        text += ` ${parts.join(' | ')}\n`;
      }

      const p2a = useBusinessPrice ? applyMarkup(data.price_2_adult, data.percentage, data.ve_fixed_price) : data.price_2_adult;
      const p2c = useBusinessPrice ? applyMarkup(data.price_2_child, data.percentage, data.ve_fixed_price) : data.price_2_child;
      const p2i = useBusinessPrice ? applyMarkup(data.price_2_infant, data.percentage, data.ve_fixed_price) : data.price_2_infant;

      if (Number(p2a) > 0 || Number(p2c) > 0 || Number(p2i) > 0) {
        text += `• ${data.baggage_2_desc}: BDT ->`;
        const parts = [];
        if (Number(p2a) > 0) parts.push(`Adult ${formatPrice(p2a)}`);
        if (Number(p2c) > 0) parts.push(`Child ${formatPrice(p2c)}`);
        if (Number(p2i) > 0) parts.push(`Infant ${formatPrice(p2i)}`);
        text += ` ${parts.join(' | ')}\n`;
      }

      text += `\n${data.refundable_status} | ${data.changeable_status}\n`;

      if (data.notes.length) {
        text += `\n*Note:*\n`;
        data.notes.forEach(n => {
          text += `• ${n}\n`;
        });
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

      const clientValue = document.getElementById('clientInput').value;
      const quoteTitle = document.getElementById('quoteTitle').value;
      const data = collectData();
      const rawQuotation = document.getElementById('raw_preview').value;
      const businessQuotation = document.getElementById('business_preview').value;

      try {
        const response = await fetch('../api/tickets/store-quotation.php', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json'
          },
          body: JSON.stringify({
            id: currentQuotationId,
            uuid: currentQuotationUuid,
            client: clientValue,
            title: quoteTitle,
            informations: rawQuotation,
            quotations: businessQuotation,
            percentage: data.percentage,
            ve_fixed_price: data.ve_fixed_price,
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
      if (!file) {
        showToast('Please upload a screenshot', 'error');
        return;
      }

      const fd = new FormData();
      fd.append('screenshot', file);

      showToast('Extracting…');

      try {
        const response = await fetch('../api/tickets/ss-extraction.php', {
          method: 'POST',
          body: fd
        });
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
      if (data.trip_option) {
        const tripRadio = document.querySelector(`input[name="trip_option"][value="${data.trip_option}"]`);
        if (tripRadio) tripRadio.checked = true;
      }

      document.getElementById('route').value = data.route || '';

      if (data.class) {
        const clsRadio = document.querySelector(`input[name="class"][value="${data.class}"]`);
        if (clsRadio) clsRadio.checked = true;
      }

      document.getElementById('pax_adult').value = data.pax_adult || 0;
      document.getElementById('pax_child').value = data.pax_child || 0;
      document.getElementById('pax_infant').value = data.pax_infant || 0;

      document.getElementById('segments').innerHTML = '';
      const tripType = document.querySelector('input[name="trip_option"]:checked').value;

      if (data.segments && data.segments.length) {
        data.segments.forEach((seg, i) => {
          let heading = 'Flight';
          let removable = false;
          if (tripType === 'Round Trip') {
            heading = i === 0 ? 'Outbound Flight' : 'Inbound Flight';
          } else if (tripType === 'Multi City') {
            heading = `Route ${i + 1}`;
            removable = i > 0;
          }
          const node = buildSegmentHTML(seg, heading, removable);
          document.getElementById('segments').appendChild(node);
        });
        if (tripType === 'Multi City') {
          document.getElementById('add_segment_btn').classList.remove('hidden');
        } else {
          document.getElementById('add_segment_btn').classList.add('hidden');
        }
      } else {
        renderSegments();
      }

      document.getElementById('baggage_1_desc').value = data.baggage_1_desc || 'Without Baggage';
      document.getElementById('price_1_adult').value = data.price_1_adult || '';
      document.getElementById('price_1_child').value = data.price_1_child || '';
      document.getElementById('price_1_infant').value = data.price_1_infant || '';
      document.getElementById('baggage_2_desc').value = data.baggage_2_desc || 'With 30 Kg Check-IN + 7 Kg Cabin Baggage';
      document.getElementById('price_2_adult').value = data.price_2_adult || '';
      document.getElementById('price_2_child').value = data.price_2_child || '';
      document.getElementById('price_2_infant').value = data.price_2_infant || '';

      if (data.refundable_status) {
        const r = document.querySelector(`input[name="refundable_status"][value="${data.refundable_status}"]`);
        if (r) r.checked = true;
      }
      if (data.changeable_status) {
        const c = document.querySelector(`input[name="changeable_status"][value="${data.changeable_status}"]`);
        if (c) c.checked = true;
      }

      document.getElementById('notes').innerHTML = '';
      if (data.notes) data.notes.forEach(n => addNote(n));

      generateQuotation();
      markUnsaved();
    }

    renderSegments();
    setSaveStatus(false);
  </script>

</body>

</html>