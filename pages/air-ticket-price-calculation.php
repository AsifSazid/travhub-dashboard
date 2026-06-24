<?php
// pages/air-ticket-price-calculation.php
include_once('./authenticate.php');

$ip_port = @file_get_contents('../ippath.txt');
if (empty($ip_port)) {
    $ip_port = "http://103.104.219.3:898";
}

$uuid = isset($_GET['id']) ? $_GET['id'] : '';
$mode = !empty($uuid) ? 'edit' : 'create';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>GDS Air Ticket Quotation Tool - <?php echo ucfirst($mode); ?></title>

    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://unpkg.com/sortablejs@1.14.0/Sortable.min.js"></script>
    <link rel="stylesheet" href="../assets/css/style.css">

    <style>
        body {
            font-family: 'Inter', sans-serif;
        }

        .thin-scrollbar::-webkit-scrollbar {
            width: 6px;
            height: 6px;
        }

        .thin-scrollbar::-webkit-scrollbar-track {
            background: #f1f5f9;
            border-radius: 999px;
        }

        .thin-scrollbar::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 999px;
        }

        .quotation-preview {
            white-space: pre-wrap;
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace;
        }

        .loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(255, 255, 255, 0.9);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 9999;
        }

        .loading-overlay.hidden {
            display: none;
        }
    </style>
</head>

<body class="bg-gray-50 font-sans">

    <!-- Loading Overlay -->
    <div id="loadingOverlay" class="loading-overlay hidden">
        <div class="bg-white rounded-2xl shadow-xl p-8 text-center">
            <div class="w-16 h-16 border-4 border-blue-600 border-t-transparent rounded-full animate-spin mx-auto mb-4"></div>
            <p class="text-slate-700 font-semibold">Loading quotation data...</p>
        </div>
    </div>

    <!-- Top Navigation -->
    <?php include '../elements/header.php'; ?>

    <!-- Sidebar -->
    <?php include '../elements/aside.php'; ?>

    <!-- Preview Modal -->
    <?php include '../elements/preview-model.php'; ?>

    <main id="mainContent" class="pt-16 pb-16 pl-64 md:pb-0 md:pl-16 lg:pl-64 transition-all duration-300">

        <div class="w-full p-4">

            <!-- Header -->
            <div class="bg-white border border-slate-200 rounded-2xl shadow-sm px-5 py-4 mb-4">
                <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3">
                    <div>
                        <h1 class="text-2xl font-extrabold text-slate-800">
                            GDS Air Ticket Quotation Tool
                            <span class="text-sm font-medium text-slate-500 ml-2">
                                (<?php echo ucfirst($mode); ?> Mode)
                            </span>
                        </h1>
                        <p class="text-sm text-slate-500 mt-1">
                            <?php if ($mode === 'edit'): ?>
                                Edit existing quotation or modify calculations
                            <?php else: ?>
                                Paste raw GDS, extract flight and fare data, edit calculation, save, then copy final quotation.
                            <?php endif; ?>
                        </p>
                    </div>

                    <div class="flex items-center gap-3">
                        <div id="savedBadge" class="hidden bg-green-50 border border-green-200 text-green-700 px-4 py-2 rounded-xl text-sm font-semibold">
                            <i class="fa-solid fa-check-circle mr-1"></i>
                            Saved
                        </div>
                        <?php if ($mode === 'edit'): ?>
                            <div class="bg-blue-50 border border-blue-200 text-blue-700 px-4 py-2 rounded-xl text-sm font-semibold">
                                <i class="fa-regular fa-pen-to-square mr-1"></i>
                                Editing: <?php echo htmlspecialchars(substr($uuid, 0, 8)); ?>...
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Main Layout -->
            <div class="grid grid-cols-1 xl:grid-cols-3 gap-4 min-h-[calc(100vh-150px)]">

                <!-- Left 1/3: Raw GDS -->
                <section class="xl:col-span-1 bg-white border border-slate-200 rounded-2xl shadow-sm p-4 flex flex-col min-h-[calc(100vh-150px)]">

                    <div class="flex items-center justify-between mb-3">
                        <div>
                            <h2 class="text-lg font-bold text-slate-800">
                                Raw GDS Input
                            </h2>
                            <p class="text-xs text-slate-500 mt-1">
                                Paste Amadeus/Sabre/Galileo style text here.
                            </p>
                        </div>

                        <button type="button" onclick="clearAll()" class="text-xs px-3 py-2 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-600 font-semibold">
                            <i class="fa-solid fa-rotate-left mr-1"></i>
                            Reset
                        </button>
                    </div>

                    <textarea
                        id="rawGds"
                        class="w-full flex-1 min-h-[240px] border border-slate-300 rounded-xl p-4 text-sm focus:outline-none focus:ring-2 focus:ring-blue-600 focus:border-blue-600 thin-scrollbar"
                        placeholder="Paste raw GDS text here..."
                    ></textarea>

                    <button
                        type="button"
                        onclick="processGds()"
                        id="processBtn"
                        class="mt-4 w-full bg-blue-700 text-white py-3 rounded-xl font-bold hover:bg-blue-800 transition"
                    >
                        <i class="fa-solid fa-wand-magic-sparkles mr-2"></i>
                        Process GDS
                    </button>

                    <div id="processError" class="hidden mt-3 bg-red-50 border border-red-200 text-red-700 rounded-xl px-4 py-3 text-sm"></div>
                </section>

                <!-- Right 2/3: Preview + Editable Output -->
                <section class="xl:col-span-2 bg-white border border-slate-200 rounded-2xl shadow-sm p-4 min-h-[calc(100vh-150px)] flex flex-col">

                    <div id="emptyState" class="flex-1 flex items-center justify-center text-center">
                        <div>
                            <div class="w-20 h-20 rounded-2xl bg-slate-100 flex items-center justify-center mx-auto mb-4">
                                <i class="fa-solid fa-plane-departure text-3xl text-slate-400"></i>
                            </div>
                            <h3 class="text-xl font-bold text-slate-700">
                                <?php if ($mode === 'edit'): ?>
                                    Loading quotation...
                                <?php else: ?>
                                    No quotation processed yet
                                <?php endif; ?>
                            </h3>
                            <p class="text-slate-500 mt-2 max-w-md">
                                <?php if ($mode === 'edit'): ?>
                                    Please wait while we load your saved quotation data.
                                <?php else: ?>
                                    Paste raw GDS text on the left and click Process GDS. The live quotation preview will appear here.
                                <?php endif; ?>
                            </p>
                        </div>
                    </div>

                    <div id="outputArea" class="hidden flex-1 flex flex-col gap-4">

                        <!-- Top Calculation Controls -->
                        <div class="grid grid-cols-1 lg:grid-cols-4 gap-3">

                            <div class="lg:col-span-1">
                                <label class="text-xs font-bold text-slate-600 uppercase">
                                    Airline
                                </label>
                                <input
                                    id="airline"
                                    type="text"
                                    class="w-full mt-1 border border-slate-300 rounded-xl px-3 py-2.5 text-sm font-semibold focus:outline-none focus:ring-2 focus:ring-blue-600"
                                    oninput="renderMarkdownPreview(); markUnsaved();"
                                >
                            </div>

                            <div>
                                <label class="text-xs font-bold text-slate-600 uppercase">
                                    Commission %
                                </label>
                                <input
                                    id="commissionRate"
                                    type="text" inputmode="numeric" pattern="[0-9]*"
                                    step="0.01"
                                    value="7"
                                    class="w-full mt-1 border border-slate-300 rounded-xl px-3 py-2.5 text-sm font-semibold focus:outline-none focus:ring-2 focus:ring-blue-600"
                                    oninput="calculateAllFares(true)"
                                >
                            </div>

                            <div>
                                <label class="text-xs font-bold text-slate-600 uppercase">
                                    Govt Tax %
                                </label>
                                <input
                                    id="govtTaxRate"
                                    type="text" inputmode="numeric" pattern="[0-9]*"
                                    step="0.01"
                                    value="0.3"
                                    class="w-full mt-1 border border-slate-300 rounded-xl px-3 py-2.5 text-sm font-semibold focus:outline-none focus:ring-2 focus:ring-blue-600"
                                    oninput="calculateAllFares(true)"
                                >
                            </div>

                            <div>
                                <label class="text-xs font-bold text-slate-600 uppercase">
                                    Global IATA Charge
                                </label>
                                <input
                                    id="globalIataCharge"
                                    type="text" inputmode="numeric" pattern="[0-9]*"
                                    value="0"
                                    class="w-full mt-1 border border-slate-300 rounded-xl px-3 py-2.5 text-sm font-semibold focus:outline-none focus:ring-2 focus:ring-blue-600"
                                    oninput="applyGlobalIataCharge()"
                                >
                            </div>
                        </div>

                        <!-- Markdown Preview -->
                        <div class="border border-slate-200 rounded-2xl overflow-hidden">
                            <div class="bg-slate-800 px-4 py-3 flex items-center justify-between">
                                <div>
                                    <h2 class="text-white font-bold">
                                        Client Copy Preview
                                    </h2>
                                    <p class="text-xs text-slate-300">
                                        Any edit below will update this quotation instantly.
                                    </p>
                                </div>

                                <div class="text-xs text-slate-300">
                                    <span id="previewStatus">Unsaved</span>
                                </div>
                            </div>

                            <pre
                                id="markdownPreview"
                                class="quotation-preview bg-slate-950 text-slate-100 p-4 min-h-[220px] max-h-[320px] overflow-auto thin-scrollbar text-sm leading-7"
                            ></pre>

                            <div class="bg-white border-t border-slate-200 p-3 flex flex-col sm:flex-row gap-3 justify-end">

                                <button
                                    type="button"
                                    onclick="saveQuotation()"
                                    id="saveBtn"
                                    class="bg-green-600 hover:bg-green-700 text-white px-5 py-3 rounded-xl font-bold transition"
                                >
                                    <i class="fa-solid fa-floppy-disk mr-2"></i>
                                    <?php echo ($mode === 'edit') ? 'Update' : 'Save'; ?> to System
                                </button>

                                <button
                                    type="button"
                                    onclick="copyQuotation()"
                                    id="copyBtn"
                                    class="hidden bg-slate-900 hover:bg-black text-white px-5 py-3 rounded-xl font-bold transition"
                                >
                                    <i class="fa-solid fa-copy mr-2"></i>
                                    Copy Quotation
                                </button>

                                <?php if ($mode === 'edit'): ?>
                                    <button
                                        type="button"
                                        onclick="window.location.href='air-ticket-price-calculation.php'"
                                        class="bg-slate-500 hover:bg-slate-600 text-white px-5 py-3 rounded-xl font-bold transition"
                                    >
                                        <i class="fa-solid fa-plus mr-2"></i>
                                        New Quotation
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Editable Fields -->
                        <div class="grid grid-cols-1 gap-4">

                            <!-- Segments -->
                            <div class="border border-slate-200 rounded-2xl overflow-hidden">
                                <div class="bg-slate-50 border-b border-slate-200 px-4 py-3 flex items-center justify-between">
                                    <div>
                                        <h3 class="font-extrabold text-slate-800">
                                            Flight Segments
                                        </h3>
                                        <p class="text-xs text-slate-500">
                                            Each segment stays in one row.
                                        </p>
                                    </div>

                                    <button
                                        type="button"
                                        onclick="addSegment()"
                                        class="bg-blue-50 hover:bg-blue-100 text-blue-700 px-3 py-2 rounded-lg text-sm font-bold"
                                    >
                                        <i class="fa-solid fa-plus mr-1"></i>
                                        Add Segment
                                    </button>
                                </div>

                                <div class="overflow-x-auto thin-scrollbar">
                                    <div class="min-w-[1000px]">

                                        <div class="grid grid-cols-[50px_120px_80px_110px_150px_100px_100px_70px] gap-2 px-4 py-2 bg-slate-100 text-xs font-bold uppercase text-slate-500">
                                            <div>Line</div>
                                            <div>Flight</div>
                                            <div>Class</div>
                                            <div>Date</div>
                                            <div>Route</div>
                                            <div>Dep</div>
                                            <div>Arr</div>
                                            <div>Action</div>
                                        </div>

                                        <div id="segmentsArea" class="divide-y divide-slate-100"></div>

                                    </div>
                                </div>
                            </div>

                            <!-- Fares -->
                            <div class="border border-slate-200 rounded-2xl overflow-hidden">
                                <div class="bg-slate-50 border-b border-slate-200 px-4 py-3 flex items-center justify-between">
                                    <div>
                                        <h3 class="font-extrabold text-slate-800">
                                            Passenger Fare Calculation
                                        </h3>
                                        <p class="text-xs text-slate-500">
                                            Supports ADT, CHD, INF or multiple fare rows.
                                        </p>
                                    </div>

                                    <button
                                        type="button"
                                        onclick="addFareRow()"
                                        class="bg-blue-50 hover:bg-blue-100 text-blue-700 px-3 py-2 rounded-lg text-sm font-bold"
                                    >
                                        <i class="fa-solid fa-plus mr-1"></i>
                                        Add Fare
                                    </button>
                                </div>

                                <div class="overflow-x-auto thin-scrollbar">
                                    <div class="min-w-[1450px]">

                                        <div class="grid grid-cols-[90px_70px_130px_120px_130px_120px_120px_120px_130px_140px_150px_70px] gap-2 px-4 py-2 bg-slate-100 text-xs font-bold uppercase text-slate-500">
                                            <div>Type</div>
                                            <div>Pax</div>
                                            <div>Base</div>
                                            <div>Taxes</div>
                                            <div>Gross</div>
                                            <div>A</div>
                                            <div>B</div>
                                            <div>IATA</div>
                                            <div>Net</div>
                                            <div>Payable</div>
                                            <div>Total</div>
                                            <div>Action</div>
                                        </div>

                                        <div id="faresArea" class="divide-y divide-slate-100"></div>

                                    </div>
                                </div>
                            </div>

                        </div>

                    </div>
                </section>

            </div>
        </div>
    </main>

    <script src="../assets/js/script.js?time=<?php echo time(); ?>"></script>
    <script>
        // ============================================================
        // PASTE THIS ENTIRE BLOCK INSIDE <script> TAG
        // (replace everything after the PHP variable declarations)
        // ============================================================
        
        let currentSegments = [];
        let currentFares = [];
        let rawGdsText = "";
        let savedUuid = "<?php echo $uuid; ?>";
        let isSaved = false;
        let isEditMode = <?php echo ($mode === 'edit') ? 'true' : 'false'; ?>;
        
        const EXTRACT_API = "../api/ticket-calculation/extract-gds.php";
        const SAVE_API    = "../api/ticket-calculation/save-calculation.php";
        const LOAD_API    = "../api/ticket-calculation/load-calculation.php";
        
        // ─── INIT ────────────────────────────────────────────────────
        document.addEventListener("DOMContentLoaded", function () {
            if (isEditMode && savedUuid) {
                loadQuotationData(savedUuid);
            }
        });
        
        // ─── LOAD ────────────────────────────────────────────────────
        async function loadQuotationData(uuid) {
            showLoading();
            try {
                const response = await fetch(`${LOAD_API}?uuid=${encodeURIComponent(uuid)}`);
                const result   = await response.json();
        
                if (!result.success) {
                    showError(result.message || "Failed to load quotation data.");
                    hideLoading();
                    return;
                }
        
                const data = result.data;
                document.getElementById("airline").value  = data.airline  || "";
                document.getElementById("rawGds").value   = data.raw_gds  || "";
        
                let segments = data.segments_json || [];
                let fares    = data.pricing_json  || [];
        
                if (segments.length === 0) {
                    showError("No segments found in saved data.");
                    hideLoading();
                    return;
                }
        
                if (fares.length === 0 && data.pax) {
                    const paxData   = typeof data.pax === 'string' ? JSON.parse(data.pax) : data.pax;
                    const totalPax  = paxData.total || 1;
                    fares = [{
                        type          : "ADT",
                        pax           : totalPax,
                        base_fare     : Math.round(data.base_fare  / totalPax),
                        taxes         : Math.round(data.taxes      / totalPax),
                        gross_fare    : Math.round(data.gross_fare / totalPax),
                        commission_a  : Math.round(data.commission_a / totalPax),
                        govt_tax_b    : Math.round(data.govt_tax_b   / totalPax),
                        iata_charge   : Math.round(data.iata_charge  / totalPax),
                        net_fare      : Math.round(data.net_fare     / totalPax),
                        payable       : Math.round(data.payable      / totalPax),
                        payable_edited: true,
                        total_payable : data.payable
                    }];
                }
        
                currentSegments = normalizeSegments(segments);
                currentFares    = normalizeFares(fares);
                rawGdsText      = data.raw_gds || "";
        
                document.getElementById("emptyState").classList.add("hidden");
                document.getElementById("outputArea").classList.remove("hidden");
        
                if (currentFares.length > 0 && currentFares[0].iata_charge) {
                    document.getElementById("globalIataCharge").value = currentFares[0].iata_charge;
                }
        
                renderSegments();
                renderFares();
                calculateAllFares(false);   // recalc after render so readonly fields populated
                renderMarkdownPreview();
        
                isSaved = true;
                document.getElementById("copyBtn").classList.remove("hidden");
                document.getElementById("savedBadge").classList.remove("hidden");
                document.getElementById("previewStatus").innerText = "Saved";
        
                hideLoading();
            } catch (error) {
                console.error("Load error:", error);
                showError("Failed to load quotation data. Please try again.");
                hideLoading();
            }
        }
        
        // ─── NORMALIZE ───────────────────────────────────────────────
        function normalizeSegments(segments) {
            if (!Array.isArray(segments)) return [];
            return segments
                .filter(seg => String(seg.flight || "").toUpperCase() !== "ARNK")
                .map((seg, i) => ({
                    line       : seg.line        || i + 1,
                    flight     : seg.flight      || "",
                    class      : seg.class       || "",
                    date       : seg.date        || "",
                    route      : normalizeRoute(seg.route || ""),
                    departure  : seg.departure   || "",
                    arrival    : seg.arrival     || "",
                    tag        : seg.tag         || `D${i + 1}`,
                    airline_name: seg.airline_name || ""
                }));
        }
        
        function normalizeFares(fares) {
            if (!Array.isArray(fares) || fares.length === 0) {
                return [{
                    type: "ADT", pax: 1, base_fare: 0, taxes: 0,
                    gross_fare: 0, commission_a: 0, govt_tax_b: 0,
                    iata_charge: 0, net_fare: 0, payable: 0,
                    payable_edited: false, total_payable: 0
                }];
            }
            return fares.map(f => ({
                type          : f.type           || "ADT",
                pax           : Number(f.pax     || 1),
                base_fare     : Number(f.base_fare     || 0),
                taxes         : Number(f.taxes         || 0),
                gross_fare    : Number(f.gross_fare    || 0),
                commission_a  : Number(f.commission_a  || 0),
                govt_tax_b    : Number(f.govt_tax_b    || 0),
                iata_charge   : Number(f.iata_charge   || 0),
                net_fare      : Number(f.net_fare      || 0),
                payable       : Number(f.payable       || 0),
                payable_edited: Boolean(f.payable_edited || false),
                total_payable : Number(f.total_payable || 0)
            }));
        }
        
        function normalizeRoute(route) {
            let v = String(route || "").trim().toUpperCase();
            if (v.includes("-")) return v;
            if (v.length === 6) return v.substring(0, 3) + "-" + v.substring(3);
            return v;
        }
        
        // ─── PROCESS GDS ─────────────────────────────────────────────
        async function processGds() {
            rawGdsText = document.getElementById("rawGds").value.trim();
            hideError();
        
            if (!rawGdsText) { showError("Please paste raw GDS text first."); return; }
        
            const btn = document.getElementById("processBtn");
            btn.disabled = true;
            btn.innerHTML = `<i class="fa-solid fa-spinner fa-spin mr-2"></i>Processing...`;
        
            try {
                const response = await fetch(EXTRACT_API, {
                    method : "POST",
                    headers: { "Content-Type": "application/json" },
                    body   : JSON.stringify({ raw_gds: rawGdsText })
                });
                const result = await response.json();
        
                if (!result.success) { showError(result.message || "Extraction failed."); return; }
                if (!result.data)    { showError("No data extracted from GDS.");           return; }
        
                renderOutput(result.data);
                markUnsaved();
            } catch (e) {
                showError("Something went wrong while processing GDS.");
                console.error(e);
            } finally {
                btn.disabled = false;
                btn.innerHTML = `<i class="fa-solid fa-wand-magic-sparkles mr-2"></i>Process GDS`;
            }
        }
        
        function renderOutput(data) {
            document.getElementById("emptyState").classList.add("hidden");
            document.getElementById("outputArea").classList.remove("hidden");
        
            document.getElementById("airline").value = data.airline || "";
        
            currentSegments = normalizeSegments(data.segments || []);
        
            if (Array.isArray(data.fares) && data.fares.length > 0) {
                currentFares = normalizeFares(data.fares);
            } else {
                currentFares = normalizeFares([{
                    type      : "ADT",
                    pax       : Number(data.pax       || 1),
                    base_fare : Number(data.base_fare  || 0),
                    taxes     : Number(data.taxes      || 0),
                    gross_fare: Number(data.gross_fare || 0),
                    iata_charge: 0
                }]);
            }
        
            calculateAllFares(false);
            renderSegments();
            renderFares();
            renderMarkdownPreview();
        }
        
        // ─── RENDER SEGMENTS (full re-render — only called on add/remove) ──
        function renderSegments() {
            const area = document.getElementById("segmentsArea");
            area.innerHTML = "";
        
            if (!currentSegments || currentSegments.length === 0) {
                area.innerHTML = `<div class="text-center py-8 text-slate-500">No segments available. Add a segment or process GDS.</div>`;
                return;
            }
        
            currentSegments.forEach((seg, index) => {
                const div = document.createElement("div");
                div.className = "grid grid-cols-[50px_120px_80px_110px_150px_100px_100px_70px] gap-2 px-4 py-3 items-center";
                div.dataset.index = index;
                div.innerHTML = `
                    <div class="text-sm font-bold text-slate-500">${escapeHtml(seg.tag || String(index + 1))}</div>
        
                    <input type="text" inputmode="text"
                        value="${escapeHtml(seg.flight)}"
                        data-index="${index}" data-key="flight"
                        class="seg-input border border-slate-300 rounded-lg px-2 py-2 text-sm font-semibold"
                        placeholder="TK713">
        
                    <input type="text" inputmode="text"
                        value="${escapeHtml(seg.class)}"
                        data-index="${index}" data-key="class"
                        class="seg-input border border-slate-300 rounded-lg px-2 py-2 text-sm"
                        placeholder="M">
        
                    <input type="text" inputmode="text"
                        value="${escapeHtml(seg.date)}"
                        data-index="${index}" data-key="date"
                        class="seg-input border border-slate-300 rounded-lg px-2 py-2 text-sm"
                        placeholder="19MAY">
        
                    <input type="text" inputmode="text"
                        value="${escapeHtml(seg.route)}"
                        data-index="${index}" data-key="route"
                        class="seg-input border border-slate-300 rounded-lg px-2 py-2 text-sm font-semibold"
                        placeholder="DAC-IST">
        
                    <input type="text" inputmode="numeric" pattern="[0-9]*"
                        value="${escapeHtml(seg.departure)}"
                        data-index="${index}" data-key="departure"
                        class="seg-input border border-slate-300 rounded-lg px-2 py-2 text-sm"
                        placeholder="0650">
        
                    <input type="text" inputmode="numeric" pattern="[0-9]*"
                        value="${escapeHtml(seg.arrival)}"
                        data-index="${index}" data-key="arrival"
                        class="seg-input border border-slate-300 rounded-lg px-2 py-2 text-sm"
                        placeholder="1245">
        
                    <button type="button" data-remove-seg="${index}"
                        class="bg-red-50 hover:bg-red-100 text-red-600 rounded-lg px-2 py-2">
                        <i class="fa-solid fa-trash"></i>
                    </button>
                `;
                area.appendChild(div);
            });
        }
        
        // ─── RENDER FARES (full re-render — only called on add/remove) ──
        function renderFares() {
            const area = document.getElementById("faresArea");
            area.innerHTML = "";
        
            if (!currentFares || currentFares.length === 0) {
                area.innerHTML = `<div class="text-center py-8 text-slate-500">No fare rows available. Add a fare or process GDS.</div>`;
                return;
            }
        
            currentFares.forEach((fare, index) => {
                fare.total_payable = Number(fare.payable || 0) * Number(fare.pax || 1);
        
                const div = document.createElement("div");
                div.className = "grid grid-cols-[90px_70px_130px_120px_130px_120px_120px_120px_130px_140px_150px_70px] gap-2 px-4 py-3 items-center";
                div.dataset.fareIndex = index;
                div.innerHTML = `
                    <input type="text" inputmode="text"
                        value="${escapeHtml(fare.type)}"
                        data-fare-index="${index}" data-fare-key="type"
                        class="fare-input border border-slate-300 rounded-lg px-2 py-2 text-sm font-bold"
                        placeholder="ADT">
        
                    <input type="text" inputmode="numeric" pattern="[0-9]*"
                        value="${fare.pax}"
                        data-fare-index="${index}" data-fare-key="pax" data-numeric="1"
                        class="fare-input border border-slate-300 rounded-lg px-2 py-2 text-sm">
        
                    <input type="text" inputmode="numeric" pattern="[0-9]*"
                        value="${fare.base_fare}"
                        data-fare-index="${index}" data-fare-key="base_fare" data-numeric="1"
                        class="fare-input border border-slate-300 rounded-lg px-2 py-2 text-sm">
        
                    <input type="text" inputmode="numeric" pattern="[0-9]*"
                        value="${fare.taxes}"
                        data-fare-index="${index}" data-fare-key="taxes" data-numeric="1"
                        class="fare-input border border-slate-300 rounded-lg px-2 py-2 text-sm">
        
                    <input type="text" inputmode="numeric" pattern="[0-9]*"
                        value="${fare.gross_fare}"
                        data-fare-index="${index}" data-fare-key="gross_fare" data-numeric="1"
                        class="fare-input border border-slate-300 rounded-lg px-2 py-2 text-sm font-semibold">
        
                    <input type="text" inputmode="numeric"
                        value="${fare.commission_a}"
                        readonly
                        data-fare-readonly="commission_a" data-fare-index="${index}"
                        class="bg-slate-100 border border-slate-300 rounded-lg px-2 py-2 text-sm">
        
                    <input type="text" inputmode="numeric"
                        value="${fare.govt_tax_b}"
                        readonly
                        data-fare-readonly="govt_tax_b" data-fare-index="${index}"
                        class="bg-slate-100 border border-slate-300 rounded-lg px-2 py-2 text-sm">
        
                    <input type="text" inputmode="numeric" pattern="[0-9]*"
                        value="${fare.iata_charge}"
                        data-fare-index="${index}" data-fare-key="iata_charge" data-numeric="1"
                        class="fare-input border border-slate-300 rounded-lg px-2 py-2 text-sm">
        
                    <input type="text" inputmode="numeric"
                        value="${fare.net_fare}"
                        readonly
                        data-fare-readonly="net_fare" data-fare-index="${index}"
                        class="bg-slate-100 border border-slate-300 rounded-lg px-2 py-2 text-sm font-semibold">
        
                    <input type="text" inputmode="numeric" pattern="[0-9]*"
                        value="${fare.payable}"
                        data-fare-index="${index}" data-fare-key="payable" data-payable="1"
                        class="fare-payable border-2 border-green-500 rounded-lg px-2 py-2 text-sm font-extrabold text-green-700">
        
                    <input type="text"
                        value="BDT ${formatMoney(fare.total_payable)}/-"
                        readonly
                        data-fare-readonly="total_payable" data-fare-index="${index}"
                        class="bg-green-50 border border-green-200 rounded-lg px-2 py-2 text-sm font-bold text-green-700">
        
                    <button type="button" data-remove-fare="${index}"
                        class="bg-red-50 hover:bg-red-100 text-red-600 rounded-lg px-2 py-2">
                        <i class="fa-solid fa-trash"></i>
                    </button>
                `;
                area.appendChild(div);
            });
        }
        
        // ─── TARGETED READONLY UPDATE (no re-render, no focus loss) ──
        function updateFareReadonlyFields(index) {
            const fare = currentFares[index];
            if (!fare) return;
        
            // commission_a
            const ca = document.querySelector(`[data-fare-readonly="commission_a"][data-fare-index="${index}"]`);
            if (ca) ca.value = fare.commission_a;
        
            // govt_tax_b
            const gt = document.querySelector(`[data-fare-readonly="govt_tax_b"][data-fare-index="${index}"]`);
            if (gt) gt.value = fare.govt_tax_b;
        
            // net_fare
            const nf = document.querySelector(`[data-fare-readonly="net_fare"][data-fare-index="${index}"]`);
            if (nf) nf.value = fare.net_fare;
        
            // total_payable
            const tp = document.querySelector(`[data-fare-readonly="total_payable"][data-fare-index="${index}"]`);
            if (tp) tp.value = `BDT ${formatMoney(fare.total_payable)}/-`;
        }
        
        // ─── EVENT DELEGATION (replaces inline oninput handlers) ─────
        document.addEventListener("input", function (e) {
            // Segment inputs
            if (e.target.classList.contains("seg-input")) {
                const index = Number(e.target.dataset.index);
                const key   = e.target.dataset.key;
                const value = e.target.value;
        
                currentSegments[index][key] = key === "route" ? normalizeRoute(value) : value;
                renderMarkdownPreview();
                markUnsaved();
                return;
            }
        
            // Fare payable input
            if (e.target.dataset.payable === "1") {
                const index = Number(e.target.dataset.fareIndex);
                currentFares[index].payable        = Number(e.target.value || 0);
                currentFares[index].payable_edited = true;
                currentFares[index].total_payable  = currentFares[index].payable * currentFares[index].pax;
                updateFareReadonlyFields(index);
                renderMarkdownPreview();
                markUnsaved();
                return;
            }
        
            // General fare inputs
            if (e.target.classList.contains("fare-input") && e.target.dataset.fareKey) {
                const index    = Number(e.target.dataset.fareIndex);
                const key      = e.target.dataset.fareKey;
                const isNumber = e.target.dataset.numeric === "1";
                const value    = isNumber ? Number(e.target.value || 0) : e.target.value;
        
                currentFares[index][key] = value;
        
                if (["base_fare", "gross_fare", "iata_charge", "pax"].includes(key)) {
                    if (key !== "pax") currentFares[index].payable_edited = false;
                    calculateFare(index, false);
                    updateFareReadonlyFields(index);
        
                    // If payable was auto-recalculated, update payable input too (without triggering event)
                    if (!currentFares[index].payable_edited) {
                        const payableInput = document.querySelector(`[data-fare-index="${index}"][data-payable="1"]`);
                        if (payableInput && document.activeElement !== payableInput) {
                            payableInput.value = currentFares[index].payable;
                        }
                    }
                }
        
                renderMarkdownPreview();
                markUnsaved();
                return;
            }
        });
        
        // ─── EVENT DELEGATION for remove buttons ─────────────────────
        document.addEventListener("click", function (e) {
            const removeSegBtn  = e.target.closest("[data-remove-seg]");
            const removeFareBtn = e.target.closest("[data-remove-fare]");
        
            if (removeSegBtn) {
                removeSegment(Number(removeSegBtn.dataset.removeSeg));
                return;
            }
            if (removeFareBtn) {
                removeFare(Number(removeFareBtn.dataset.removeFare));
                return;
            }
        });
        
        // ─── SEGMENT ADD / REMOVE ────────────────────────────────────
        function addSegment() {
            currentSegments.push({
                line: currentSegments.length + 1,
                flight: "", class: "", date: "",
                route: "", departure: "", arrival: "",
                tag: `D${currentSegments.length + 1}`
            });
            renderSegments();
            renderMarkdownPreview();
            markUnsaved();
        }
        
        function removeSegment(index) {
            currentSegments.splice(index, 1);
            renderSegments();
            renderMarkdownPreview();
            markUnsaved();
        }
        
        // ─── FARE ADD / REMOVE ───────────────────────────────────────
        function addFareRow() {
            currentFares.push({
                type: "ADT", pax: 1, base_fare: 0, taxes: 0,
                gross_fare: 0, commission_a: 0, govt_tax_b: 0,
                iata_charge: Number(document.getElementById("globalIataCharge").value || 0),
                net_fare: 0, payable: 0, payable_edited: false, total_payable: 0
            });
            calculateFare(currentFares.length - 1, false);
            renderFares();
            renderMarkdownPreview();
            markUnsaved();
        }
        
        function removeFare(index) {
            currentFares.splice(index, 1);
            renderFares();
            renderMarkdownPreview();
            markUnsaved();
        }
        
        // ─── FARE CALCULATION ────────────────────────────────────────
        function calculateAllFares(forceResetPayable = false) {
            currentFares.forEach((_, i) => calculateFare(i, forceResetPayable));
            renderFares();         // full re-render here is fine (called from rate inputs, not fare inputs)
            renderMarkdownPreview();
            markUnsaved();
        }
        
        function calculateFare(index, forceResetPayable = false) {
            const fare           = currentFares[index];
            const commissionRate = Number(document.getElementById("commissionRate").value || 7)   / 100;
            const govtTaxRate    = Number(document.getElementById("govtTaxRate").value    || 0.3) / 100;
        
            const base  = Number(fare.base_fare  || 0);
            const gross = Number(fare.gross_fare || 0);
            const iata  = Number(fare.iata_charge || 0);
        
            fare.commission_a = Math.round(base  * commissionRate);
            fare.govt_tax_b   = Math.round(gross * govtTaxRate);
            fare.net_fare     = Math.max(0, Math.round(gross - fare.commission_a + fare.govt_tax_b + iata));
        
            if (forceResetPayable || !fare.payable_edited || Number(fare.payable || 0) === 0) {
                fare.payable        = Math.max(0, Math.round((gross + fare.net_fare) / 2));
                fare.payable_edited = false;
            }
        
            fare.total_payable = Number(fare.payable || 0) * Number(fare.pax || 1);
        }
        
        function applyGlobalIataCharge() {
            const globalIata = Number(document.getElementById("globalIataCharge").value || 0);
            currentFares.forEach((fare, i) => {
                fare.iata_charge   = globalIata;
                fare.payable_edited = false;
                calculateFare(i, false);
            });
            renderFares();
            renderMarkdownPreview();
            markUnsaved();
        }
        
        // ─── PREVIEW GENERATION ──────────────────────────────────────
        function generateCopyText() {
            const segments = currentSegments;
            const fares    = currentFares;
        
            if (segments.length === 0) return "No flight segments available.";
        
            // Group by tag
            const groupedSegments = {};
            segments.forEach(seg => {
                const tag = seg.tag || "D1";
                if (!groupedSegments[tag]) groupedSegments[tag] = [];
                groupedSegments[tag].push(seg);
            });
        
            // Route summary
            const sortedTagsForRoute = Object.keys(groupedSegments).sort();
            const routeSummaryParts  = [];
            sortedTagsForRoute.forEach(tag => {
                const group = groupedSegments[tag];
                if (group.length > 0) {
                    const first = group[0].route ? group[0].route.split('-')[0].trim() : '';
                    const last  = group[group.length - 1].route ? group[group.length - 1].route.split('-')[1].trim() : '';
                    if (first && last) routeSummaryParts.push(`${first} -> ${last}`);
                }
            });
            const routeSummary = routeSummaryParts.join(' | ');
        
            // Trip type
            let tripType = "One Way";
            if (segments.length >= 2) {
                const firstOrigin = segments[0].route ? segments[0].route.split('-')[0].trim() : '';
                const lastDest    = segments[segments.length - 1].route ? segments[segments.length - 1].route.split('-')[1].trim() : '';
                if (firstOrigin && lastDest) {
                    tripType = firstOrigin === lastDest ? "Round Trip" : "Multi-City";
                }
            }
        
            // Pax count
            let totalPax = 0;
            fares.forEach(f => totalPax += (f.pax || 1));
            const passengerLabel = totalPax > 1 ? 'Passengers' : 'Passenger';
        
            let output = `*${tripType} Ticket*\n`;
            output += `Route: ${routeSummary}\n`;
            output += `${totalPax} ${passengerLabel}\n\n`;
            output += `*Flight Info:*\n`;
        
            const sortedTags = Object.keys(groupedSegments).sort();
        
            sortedTags.forEach((tag, idx) => {
                const segs     = groupedSegments[tag];
                const isReturn = tag.startsWith('R');
                const airlineName = segs[0].airline_name
                    || document.getElementById("airline").value.trim()
                    || "AIRLINE";
        
                output += `*${isReturn ? 'Return' : 'Depart'} | ${airlineName.toUpperCase()}*\n`;
        
                segs.forEach((seg, i) => {
                    const depFormatted = formatTimeDisplay(seg.departure);
                    const arrFormatted = formatTimeDisplay(seg.arrival);
                    const airports     = seg.route ? seg.route.split('-') : ['', ''];
                    const originAP     = airports[0] ? airports[0].trim() : '';
                    const destAP       = airports[1] ? airports[1].trim() : '';
        
                    // Transit line
                    if (i > 0) {
                        const transitDuration = calculateTransitTime(segs[i - 1], seg);
                        const durationLabel   = transitDuration ? ` (${transitDuration})` : '';
                        output += `*TRANSIT at ${originAP}${durationLabel}* \n`;
                    }
        
                    // Overnight note
                    const overnight     = isOvernightFlight(seg);
                    const overnightNote = overnight ? `-${getNextDayLabel(seg.date)}` : '';
        
                    output += `*${seg.date || ''}* | ${seg.flight || ''}\n`;
                    output += `${originAP} ${seg.departure || ''} (${depFormatted}) - ${destAP} ${seg.arrival || ''} (${arrFormatted})${overnightNote}\n`;
                });
        
                if (idx < sortedTags.length - 1) output += `\n`;
            });
        
            // Pricing
            output += `\n*Price:*\n`;
            let grandTotal = 0;
        
            fares.forEach(fare => {
                const gross   = Number(fare.gross_fare || 0);
                const payable = Number(fare.payable    || 0);
                const pax     = Number(fare.pax        || 1);
                grandTotal   += payable * pax;
        
                const typeLabel =
                    fare.type === 'ADT' ? 'Adult'  :
                    fare.type === 'CNN' ? 'Child'  :
                    fare.type === 'CHD' ? 'Child'  :
                    fare.type === 'INF' ? 'Infant' : fare.type;
        
                output += `- ${typeLabel}: Gross BDT ${formatMoney(gross)} per person\n`;
                output += `- *Payable: BDT ${formatMoney(payable)}/- per person.*\n`;
            });
        
            output += `*Total payable: ${formatMoney(grandTotal)}/-*`;
        
            return output;
        }
        
        function renderMarkdownPreview() {
            const preview = document.getElementById("markdownPreview");
            if (preview) preview.innerText = generateCopyText();
        }
        
        // ─── OVERNIGHT HELPERS ───────────────────────────────────────
        function isOvernightFlight(seg) {
            if (!seg.departure || !seg.arrival) return false;
            return parseInt(seg.arrival, 10) < parseInt(seg.departure, 10);
        }
        
        function getNextDayLabel(dateStr) {
            const currentYear = new Date().getFullYear();
            const parsed = new Date(Date.parse(`${dateStr.replace(/(\d+)([A-Z]+)/, '$1 $2')} ${currentYear}`));
            if (isNaN(parsed)) return '';
            parsed.setDate(parsed.getDate() + 1);
            const day   = String(parsed.getDate()).padStart(2, '0');
            const month = parsed.toLocaleString('en-US', { month: 'short' }).toUpperCase();
            return `${day}${month}`;
        }
        
        // ─── TRANSIT TIME ────────────────────────────────────────────
        function calculateTransitTime(prevSeg, currentSeg) {
            if (!prevSeg || !currentSeg) return '';
        
            const currentYear = new Date().getFullYear();
        
            const parseDateTime = (dateStr, timeStr) => {
                if (!dateStr || !timeStr) return null;
                const hh = timeStr.substring(0, 2);
                const mm = timeStr.substring(2, 4);
                return new Date(Date.parse(`${dateStr.replace(/(\d+)([A-Z]+)/, '$1 $2')} ${currentYear} ${hh}:${mm}`));
            };
        
            const depDate    = parseDateTime(prevSeg.date, prevSeg.departure);
            let   arrDate    = parseDateTime(prevSeg.date, prevSeg.arrival);
            const nextDepDate = parseDateTime(currentSeg.date, currentSeg.departure);
        
            if (!depDate || !arrDate || !nextDepDate) return '';
        
            // Overnight: arrival HHMM < departure HHMM → +1 day
            if (arrDate <= depDate) {
                arrDate = new Date(arrDate.getTime() + 24 * 60 * 60 * 1000);
            }
        
            let diffMs = nextDepDate - arrDate;
            if (diffMs < 0) diffMs += 24 * 60 * 60 * 1000; // safety net
        
            const totalMinutes = Math.floor(diffMs / (1000 * 60));
            const hours        = Math.floor(totalMinutes / 60);
            const minutes      = totalMinutes % 60;
        
            if (hours === 0) return `${minutes} mins`;
            return `${String(hours).padStart(2, '0')} ${hours > 1 ? 'hrs' : 'hr'} ${minutes} ${minutes > 1 ? 'mins' : 'min'}`;
        }
        
        // ─── TIME FORMAT ─────────────────────────────────────────────
        function formatTimeDisplay(time) {
            if (!time) return '';
            const str   = String(time).padStart(4, '0');
            const hours = parseInt(str.substring(0, 2));
            const mins  = str.substring(2, 4);
            const ampm  = hours >= 12 ? 'PM' : 'AM';
            const h     = hours > 12 ? hours - 12 : hours === 0 ? 12 : hours;
            return `${String(h).padStart(2, '0')}:${mins} ${ampm}`;
        }
        
        // ─── SAVE / COPY / CLEAR ─────────────────────────────────────
        async function copyQuotation() {
            if (!isSaved) { alert("Please save the quotation first."); return; }
            try {
                await navigator.clipboard.writeText(generateCopyText());
                alert("Quotation copied.");
            } catch {
                alert("Copy failed. Please copy manually from preview.");
            }
        }
        
        async function saveQuotation() {
            hideError();
            if (!currentSegments.length || !currentFares.length) {
                alert("No quotation data to save."); return;
            }
        
            const saveBtn = document.getElementById("saveBtn");
            saveBtn.disabled = true;
            saveBtn.innerHTML = `<i class="fa-solid fa-spinner fa-spin mr-2"></i>${isEditMode ? 'Updating...' : 'Saving...'}`;
        
            const payload = {
                uuid     : savedUuid,
                airline  : document.getElementById("airline").value.trim(),
                raw_gds  : rawGdsText,
                segments : currentSegments,
                fares    : currentFares,
                pricing_summary: getPricingSummary(),
                copy_text: generateCopyText(),
                mode     : isEditMode ? 'update' : 'create'
            };
        
            try {
                const response = await fetch(SAVE_API, {
                    method : "POST",
                    headers: { "Content-Type": "application/json" },
                    body   : JSON.stringify(payload)
                });
                const result = await response.json();
        
                if (result.success) {
                    savedUuid = result.uuid;
                    isSaved   = true;
        
                    document.getElementById("copyBtn").classList.remove("hidden");
                    document.getElementById("savedBadge").classList.remove("hidden");
                    document.getElementById("previewStatus").innerText = "Saved";
        
                    if (!isEditMode) {
                        window.history.pushState({}, "", `air-ticket-price-calculation.php?uuid=${encodeURIComponent(savedUuid)}`);
                        isEditMode = true;
                    }
        
                    alert(result.message || "Saved successfully.");
                } else {
                    alert(result.message || "Save failed.");
                }
            } catch (e) {
                console.error(e);
                alert("Something went wrong while saving.");
            } finally {
                saveBtn.disabled = false;
                saveBtn.innerHTML = `<i class="fa-solid fa-floppy-disk mr-2"></i>${isEditMode ? 'Update' : 'Save'} to System`;
            }
        }
        
        function getPricingSummary() {
            let totalBase = 0, totalTaxes = 0, totalGross = 0,
                totalCommissionA = 0, totalGovtTaxB = 0,
                totalIata = 0, totalNet = 0, totalPayable = 0, totalPax = 0;
        
            currentFares.forEach(fare => {
                const pax = Number(fare.pax || 1);
                totalPax        += pax;
                totalBase       += Number(fare.base_fare    || 0) * pax;
                totalTaxes      += Number(fare.taxes        || 0) * pax;
                totalGross      += Number(fare.gross_fare   || 0) * pax;
                totalCommissionA += Number(fare.commission_a || 0) * pax;
                totalGovtTaxB   += Number(fare.govt_tax_b   || 0) * pax;
                totalIata       += Number(fare.iata_charge  || 0) * pax;
                totalNet        += Number(fare.net_fare     || 0) * pax;
                totalPayable    += Number(fare.payable      || 0) * pax;
            });
        
            return {
                total_pax         : totalPax,
                total_base_fare   : totalBase,
                total_taxes       : totalTaxes,
                total_gross_fare  : totalGross,
                total_commission_a: totalCommissionA,
                total_govt_tax_b  : totalGovtTaxB,
                total_iata_charge : totalIata,
                total_net_fare    : totalNet,
                total_payable     : totalPayable
            };
        }
        
        function clearAll() {
            if (isEditMode && isSaved) {
                if (!confirm("This will reset the form. Continue?")) return;
            }
        
            document.getElementById("rawGds").value         = "";
            document.getElementById("airline").value         = "";
            document.getElementById("markdownPreview").innerText = "";
        
            currentSegments = []; currentFares = [];
            rawGdsText = ""; savedUuid = null;
            isSaved = false; isEditMode = false;
        
            document.getElementById("outputArea").classList.add("hidden");
            document.getElementById("emptyState").classList.remove("hidden");
            document.getElementById("copyBtn").classList.add("hidden");
            document.getElementById("savedBadge").classList.add("hidden");
            hideError();
        
            window.history.pushState({}, "", "air-ticket-price-calculation.php");
        }
        
        function markUnsaved() {
            isSaved = false;
            document.getElementById("copyBtn").classList.add("hidden");
            document.getElementById("savedBadge").classList.add("hidden");
            const ps = document.getElementById("previewStatus");
            if (ps) ps.innerText = "Unsaved";
        }
        
        // ─── HELPERS ─────────────────────────────────────────────────
        function showError(msg) {
            const box = document.getElementById("processError");
            box.innerText = msg;
            box.classList.remove("hidden");
        }
        function hideError() {
            const box = document.getElementById("processError");
            box.innerText = "";
            box.classList.add("hidden");
        }
        function showLoading() { document.getElementById("loadingOverlay").classList.remove("hidden"); }
        function hideLoading()  { document.getElementById("loadingOverlay").classList.add("hidden");    }
        function formatMoney(amount) {
            return Number(amount || 0).toLocaleString("en-BD", { maximumFractionDigits: 0 });
        }
        function escapeHtml(value) {
            return String(value ?? "")
                .replaceAll("&", "&amp;")
                .replaceAll("<", "&lt;")
                .replaceAll(">", "&gt;")
                .replaceAll('"', "&quot;")
                .replaceAll("'", "&#039;");
        }
        
        // ─── RATE INPUTS (commission, govtTax) — these DO full re-render ──
        // Wire up via inline oninput in HTML as before:
        //   oninput="calculateAllFares(true)"  ← commission & govtTax inputs
        //   oninput="applyGlobalIataCharge()"  ← globalIataCharge input
        //   oninput="renderMarkdownPreview(); markUnsaved();"  ← airline input
        // No changes needed in HTML for these 4 inputs.
    </script>

</body>
</html>