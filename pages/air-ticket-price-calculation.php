<?php
// pages/air-ticket-price-calculation.php
include_once('./authenticate.php');

$ip_port = @file_get_contents('../ippath.txt');
if (empty($ip_port)) {
    $ip_port = "http://103.104.219.3:898";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>GDS Air Ticket Quotation Tool</title>

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

        <div class="w-full p-4">

            <!-- Header -->
            <div class="bg-white border border-slate-200 rounded-2xl shadow-sm px-5 py-4 mb-4">
                <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3">
                    <div>
                        <h1 class="text-2xl font-extrabold text-slate-800">
                            GDS Air Ticket Quotation Tool
                        </h1>
                        <p class="text-sm text-slate-500 mt-1">
                            Paste raw GDS, extract flight and fare data, edit calculation, save, then copy final quotation.
                        </p>
                    </div>

                    <div id="savedBadge" class="hidden bg-green-50 border border-green-200 text-green-700 px-4 py-2 rounded-xl text-sm font-semibold">
                        <i class="fa-solid fa-check-circle mr-1"></i>
                        Saved
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
                            Clear
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
                                No quotation processed yet
                            </h3>
                            <p class="text-slate-500 mt-2 max-w-md">
                                Paste raw GDS text on the left and click Process GDS. The live quotation preview will appear here.
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
                                    type="number"
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
                                    type="number"
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
                                    type="number"
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
                                    Save to System
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
                                    <div class="min-w-[1100px]">

                                        <div class="grid grid-cols-[50px_120px_80px_110px_150px_100px_100px_100px_70px] gap-2 px-4 py-2 bg-slate-100 text-xs font-bold uppercase text-slate-500">
                                            <div>Line</div>
                                            <div>Flight</div>
                                            <div>Class</div>
                                            <div>Date</div>
                                            <div>Route</div>
                                            <div>Status</div>
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
let currentSegments = [];
let currentFares = [];
let rawGdsText = "";
let savedUuid = null;
let isSaved = false;

const EXTRACT_API = "../api/ticket-calculation/extract-gds.php";
const SAVE_API = "../api/ticket-calculation/save-calculation.php";

document.addEventListener("DOMContentLoaded", function () {
    const urlParams = new URLSearchParams(window.location.search);
    const uuid = urlParams.get("uuid");

    if (uuid) {
        savedUuid = uuid;
        isSaved = true;
        document.getElementById("savedBadge").classList.remove("hidden");
        document.getElementById("previewStatus").innerText = "Saved";
    }
});

async function processGds() {
    rawGdsText = document.getElementById("rawGds").value.trim();

    hideError();

    if (!rawGdsText) {
        showError("Please paste raw GDS text first.");
        return;
    }

    const processBtn = document.getElementById("processBtn");
    processBtn.disabled = true;
    processBtn.innerHTML = `<i class="fa-solid fa-spinner fa-spin mr-2"></i>Processing...`;

    try {
        const response = await fetch(EXTRACT_API, {
            method: "POST",
            headers: {
                "Content-Type": "application/json"
            },
            body: JSON.stringify({
                raw_gds: rawGdsText
            })
        });

        const result = await response.json();

        if (!result.success) {
            showError(result.message || "Extraction failed.");
            return;
        }

        renderOutput(result.data);
        markUnsaved();

    } catch (error) {
        showError("Something went wrong while processing GDS.");
        console.error(error);
    } finally {
        processBtn.disabled = false;
        processBtn.innerHTML = `<i class="fa-solid fa-wand-magic-sparkles mr-2"></i>Process GDS`;
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
        currentFares = normalizeFares([
            {
                type: "ADT",
                pax: Number(data.pax || 1),
                base_fare: Number(data.base_fare || 0),
                taxes: Number(data.taxes || 0),
                gross_fare: Number(data.gross_fare || 0),
                iata_charge: 0
            }
        ]);
    }

    calculateAllFares(false);
    renderSegments();
    renderFares();
    renderMarkdownPreview();
}

function normalizeSegments(segments) {
    return segments
        .filter(seg => {
            const flight = String(seg.flight || "").toUpperCase();
            return flight !== "ARNK";
        })
        .map((seg, index) => {
            return {
                line: seg.line || index + 1,
                flight: seg.flight || "",
                class: seg.class || "",
                date: seg.date || "",
                route: normalizeRoute(seg.route || ""),
                status: normalizeStatus(seg.status || ""),
                departure: seg.departure || "",
                arrival: seg.arrival || ""
            };
        });
}

function normalizeFares(fares) {
    return fares.map(fare => {
        return {
            type: fare.type || "ADT",
            pax: Number(fare.pax || 1),
            base_fare: Number(fare.base_fare || 0),
            taxes: Number(fare.taxes || 0),
            gross_fare: Number(fare.gross_fare || 0),
            commission_a: 0,
            govt_tax_b: 0,
            iata_charge: Number(fare.iata_charge || 0),
            net_fare: 0,
            payable: Number(fare.payable || 0),
            payable_edited: Boolean(fare.payable_edited || false),
            total_payable: 0
        };
    });
}

function normalizeStatus(status) {
    let value = String(status || "").trim().toUpperCase();

    if (!value) return "HS";

    value = value.replace("HK", "HS");

    return value;
}

function normalizeRoute(route) {
    let value = String(route || "").trim().toUpperCase();

    if (value.includes("-")) {
        return value;
    }

    if (value.length === 6) {
        return value.substring(0, 3) + "-" + value.substring(3);
    }

    return value;
}

function renderSegments() {
    const area = document.getElementById("segmentsArea");
    area.innerHTML = "";

    currentSegments.forEach((seg, index) => {
        area.innerHTML += `
            <div class="grid grid-cols-[50px_120px_80px_110px_150px_100px_100px_100px_70px] gap-2 px-4 py-3 items-center">
                <div class="text-sm font-bold text-slate-500">${index + 1}</div>

                <input
                    value="${escapeHtml(seg.flight)}"
                    oninput="updateSegment(${index}, 'flight', this.value)"
                    class="border border-slate-300 rounded-lg px-2 py-2 text-sm font-semibold"
                    placeholder="TK713"
                >

                <input
                    value="${escapeHtml(seg.class)}"
                    oninput="updateSegment(${index}, 'class', this.value)"
                    class="border border-slate-300 rounded-lg px-2 py-2 text-sm"
                    placeholder="M"
                >

                <input
                    value="${escapeHtml(seg.date)}"
                    oninput="updateSegment(${index}, 'date', this.value)"
                    class="border border-slate-300 rounded-lg px-2 py-2 text-sm"
                    placeholder="19MAY"
                >

                <input
                    value="${escapeHtml(seg.route)}"
                    oninput="updateSegment(${index}, 'route', this.value)"
                    class="border border-slate-300 rounded-lg px-2 py-2 text-sm font-semibold"
                    placeholder="DAC-IST"
                >

                <input
                    value="${escapeHtml(seg.status)}"
                    oninput="updateSegment(${index}, 'status', this.value)"
                    class="border border-slate-300 rounded-lg px-2 py-2 text-sm"
                    placeholder="HS3"
                >

                <input
                    value="${escapeHtml(seg.departure)}"
                    oninput="updateSegment(${index}, 'departure', this.value)"
                    class="border border-slate-300 rounded-lg px-2 py-2 text-sm"
                    placeholder="0650"
                >

                <input
                    value="${escapeHtml(seg.arrival)}"
                    oninput="updateSegment(${index}, 'arrival', this.value)"
                    class="border border-slate-300 rounded-lg px-2 py-2 text-sm"
                    placeholder="1245"
                >

                <button
                    type="button"
                    onclick="removeSegment(${index})"
                    class="bg-red-50 hover:bg-red-100 text-red-600 rounded-lg px-2 py-2"
                    title="Remove"
                >
                    <i class="fa-solid fa-trash"></i>
                </button>
            </div>
        `;
    });
}

function renderFares() {
    const area = document.getElementById("faresArea");
    area.innerHTML = "";

    currentFares.forEach((fare, index) => {
        area.innerHTML += `
            <div class="grid grid-cols-[90px_70px_130px_120px_130px_120px_120px_120px_130px_140px_150px_70px] gap-2 px-4 py-3 items-center">

                <input
                    value="${escapeHtml(fare.type)}"
                    oninput="updateFare(${index}, 'type', this.value, false)"
                    class="border border-slate-300 rounded-lg px-2 py-2 text-sm font-bold"
                    placeholder="ADT"
                >

                <input
                    type="number"
                    value="${fare.pax}"
                    min="1"
                    oninput="updateFare(${index}, 'pax', this.value, true)"
                    class="border border-slate-300 rounded-lg px-2 py-2 text-sm"
                >

                <input
                    type="number"
                    value="${fare.base_fare}"
                    oninput="updateFare(${index}, 'base_fare', this.value, true)"
                    class="border border-slate-300 rounded-lg px-2 py-2 text-sm"
                >

                <input
                    type="number"
                    value="${fare.taxes}"
                    oninput="updateFare(${index}, 'taxes', this.value, true)"
                    class="border border-slate-300 rounded-lg px-2 py-2 text-sm"
                >

                <input
                    type="number"
                    value="${fare.gross_fare}"
                    oninput="updateFare(${index}, 'gross_fare', this.value, true)"
                    class="border border-slate-300 rounded-lg px-2 py-2 text-sm font-semibold"
                >

                <input
                    type="number"
                    value="${fare.commission_a}"
                    readonly
                    class="bg-slate-100 border border-slate-300 rounded-lg px-2 py-2 text-sm"
                >

                <input
                    type="number"
                    value="${fare.govt_tax_b}"
                    readonly
                    class="bg-slate-100 border border-slate-300 rounded-lg px-2 py-2 text-sm"
                >

                <input
                    type="number"
                    value="${fare.iata_charge}"
                    oninput="updateFare(${index}, 'iata_charge', this.value, true)"
                    class="border border-slate-300 rounded-lg px-2 py-2 text-sm"
                >

                <input
                    type="number"
                    value="${fare.net_fare}"
                    readonly
                    class="bg-slate-100 border border-slate-300 rounded-lg px-2 py-2 text-sm font-semibold"
                >

                <input
                    type="number"
                    value="${fare.payable}"
                    oninput="updatePayable(${index}, this.value)"
                    class="border-2 border-green-500 rounded-lg px-2 py-2 text-sm font-extrabold text-green-700"
                >

                <input
                    type="text"
                    value="BDT ${formatMoney(fare.total_payable)}/-"
                    readonly
                    class="bg-green-50 border border-green-200 rounded-lg px-2 py-2 text-sm font-bold text-green-700"
                >

                <button
                    type="button"
                    onclick="removeFare(${index})"
                    class="bg-red-50 hover:bg-red-100 text-red-600 rounded-lg px-2 py-2"
                    title="Remove"
                >
                    <i class="fa-solid fa-trash"></i>
                </button>
            </div>
        `;
    });
}

function updateSegment(index, key, value) {
    currentSegments[index][key] = value;

    if (key === "status") {
        currentSegments[index][key] = normalizeStatus(value);
    }

    if (key === "route") {
        currentSegments[index][key] = normalizeRoute(value);
    }

    renderMarkdownPreview();
    markUnsaved();
}

function updateFare(index, key, value, isNumber) {
    if (isNumber) {
        currentFares[index][key] = Number(value || 0);
    } else {
        currentFares[index][key] = value;
    }

    if (["base_fare", "gross_fare", "iata_charge", "pax"].includes(key)) {
        if (key !== "pax") {
            currentFares[index].payable_edited = false;
        }
        calculateFare(index, false);
    }

    renderFares();
    renderMarkdownPreview();
    markUnsaved();
}

function updatePayable(index, value) {
    currentFares[index].payable = Number(value || 0);
    currentFares[index].payable_edited = true;
    currentFares[index].total_payable = currentFares[index].payable * currentFares[index].pax;

    renderFares();
    renderMarkdownPreview();
    markUnsaved();
}

function calculateAllFares(forceResetPayable = false) {
    currentFares.forEach((fare, index) => {
        calculateFare(index, forceResetPayable);
    });

    renderFares();
    renderMarkdownPreview();
    markUnsaved();
}

function calculateFare(index, forceResetPayable = false) {
    const fare = currentFares[index];

    const commissionRate = Number(document.getElementById("commissionRate").value || 7) / 100;
    const govtTaxRate = Number(document.getElementById("govtTaxRate").value || 0.3) / 100;

    const base = Number(fare.base_fare || 0);
    const gross = Number(fare.gross_fare || 0);
    const iata = Number(fare.iata_charge || 0);

    fare.commission_a = Math.round(base * commissionRate);
    fare.govt_tax_b = Math.round(gross * govtTaxRate);
    fare.net_fare = Math.round(gross - fare.commission_a + fare.govt_tax_b + iata);

    if (forceResetPayable || !fare.payable_edited || Number(fare.payable || 0) === 0) {
        fare.payable = Math.round((gross + fare.net_fare) / 2);
        fare.payable_edited = false;
    }

    fare.total_payable = Number(fare.payable || 0) * Number(fare.pax || 1);
}

function applyGlobalIataCharge() {
    const globalIata = Number(document.getElementById("globalIataCharge").value || 0);

    currentFares.forEach((fare, index) => {
        fare.iata_charge = globalIata;
        fare.payable_edited = false;
        calculateFare(index, false);
    });

    renderFares();
    renderMarkdownPreview();
    markUnsaved();
}

function addSegment() {
    currentSegments.push({
        line: currentSegments.length + 1,
        flight: "",
        class: "",
        date: "",
        route: "",
        status: "HS",
        departure: "",
        arrival: ""
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

function addFareRow() {
    currentFares.push({
        type: "ADT",
        pax: 1,
        base_fare: 0,
        taxes: 0,
        gross_fare: 0,
        commission_a: 0,
        govt_tax_b: 0,
        iata_charge: Number(document.getElementById("globalIataCharge").value || 0),
        net_fare: 0,
        payable: 0,
        payable_edited: false,
        total_payable: 0
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

function generateCopyText() {
    const airline = document.getElementById("airline").value.trim() || "Airline";

    let text = `${airline.toUpperCase()}:\n`;

    currentSegments.forEach((seg, index) => {
        text += `${index + 1} . ${formatFlight(seg.flight)} ${seg.class || ""} ${seg.date || ""} ${removeDash(seg.route || "")} ${seg.status || "HS"} ${seg.departure || ""} ${formatArrival(seg.arrival || "")}\n`;
    });

    text += `\n*Price:*\n`;

    let grandTotal = 0;

    currentFares.forEach(fare => {
        const pax = Number(fare.pax || 1);
        const payable = Number(fare.payable || 0);
        const total = payable * pax;

        grandTotal += total;

        text += `- Gross: BDT ${formatMoney(fare.gross_fare)} per person \n`;
        text += `- *Payable: BDT ${formatMoney(payable)}/- per person.*\n`;
    });

    text += `*Total payable: ${formatMoney(grandTotal)}/-*`;

    return text.trim();
}

function formatFlight(flight) {
    const value = String(flight || "").trim().toUpperCase();

    const match = value.match(/^([A-Z]{2})(\d+)$/);

    if (!match) return value;

    const code = match[1];
    const number = match[2].padStart(4, " ");

    return `${code}${number}`;
}

function formatArrival(arrival) {
    const value = String(arrival || "").trim();

    if (!value) return "";

    return value;
}

function removeDash(route) {
    return String(route || "").replaceAll("-", "");
}

function renderMarkdownPreview() {
    const preview = document.getElementById("markdownPreview");

    if (!preview) return;

    preview.innerText = generateCopyText();
}

async function copyQuotation() {
    if (!isSaved) {
        alert("Please save the quotation first.");
        return;
    }

    const text = generateCopyText();

    try {
        await navigator.clipboard.writeText(text);
        alert("Quotation copied.");
    } catch (error) {
        alert("Copy failed. Please copy manually from preview.");
    }
}

async function saveQuotation() {
    hideError();

    if (!currentSegments.length || !currentFares.length) {
        alert("No quotation data to save.");
        return;
    }

    const saveBtn = document.getElementById("saveBtn");
    saveBtn.disabled = true;
    saveBtn.innerHTML = `<i class="fa-solid fa-spinner fa-spin mr-2"></i>Saving...`;

    const payload = {
        uuid: savedUuid,
        airline: document.getElementById("airline").value.trim(),
        raw_gds: rawGdsText,
        segments: currentSegments,
        fares: currentFares,
        pricing_summary: getPricingSummary(),
        copy_text: generateCopyText()
    };

    try {
        const response = await fetch(SAVE_API, {
            method: "POST",
            headers: {
                "Content-Type": "application/json"
            },
            body: JSON.stringify(payload)
        });

        const result = await response.json();

        if (result.success) {
            savedUuid = result.uuid;
            isSaved = true;

            document.getElementById("copyBtn").classList.remove("hidden");
            document.getElementById("savedBadge").classList.remove("hidden");
            document.getElementById("previewStatus").innerText = "Saved";

            const newUrl = `index-at-calculation.php?uuid=${encodeURIComponent(savedUuid)}`;
            window.history.pushState({}, "", newUrl);

            alert("Saved successfully.");
        } else {
            alert(result.message || "Save failed.");
        }

    } catch (error) {
        console.error(error);
        alert("Something went wrong while saving.");
    } finally {
        saveBtn.disabled = false;
        saveBtn.innerHTML = `<i class="fa-solid fa-floppy-disk mr-2"></i>Save to System`;
    }
}

function getPricingSummary() {
    let totalBase = 0;
    let totalTaxes = 0;
    let totalGross = 0;
    let totalCommissionA = 0;
    let totalGovtTaxB = 0;
    let totalIata = 0;
    let totalNet = 0;
    let totalPayable = 0;
    let totalPax = 0;

    currentFares.forEach(fare => {
        const pax = Number(fare.pax || 1);

        totalPax += pax;
        totalBase += Number(fare.base_fare || 0) * pax;
        totalTaxes += Number(fare.taxes || 0) * pax;
        totalGross += Number(fare.gross_fare || 0) * pax;
        totalCommissionA += Number(fare.commission_a || 0) * pax;
        totalGovtTaxB += Number(fare.govt_tax_b || 0) * pax;
        totalIata += Number(fare.iata_charge || 0) * pax;
        totalNet += Number(fare.net_fare || 0) * pax;
        totalPayable += Number(fare.payable || 0) * pax;
    });

    return {
        total_pax: totalPax,
        total_base_fare: totalBase,
        total_taxes: totalTaxes,
        total_gross_fare: totalGross,
        total_commission_a: totalCommissionA,
        total_govt_tax_b: totalGovtTaxB,
        total_iata_charge: totalIata,
        total_net_fare: totalNet,
        total_payable: totalPayable
    };
}

function clearAll() {
    document.getElementById("rawGds").value = "";
    document.getElementById("airline").value = "";
    document.getElementById("markdownPreview").innerText = "";

    currentSegments = [];
    currentFares = [];
    rawGdsText = "";
    savedUuid = null;
    isSaved = false;

    document.getElementById("outputArea").classList.add("hidden");
    document.getElementById("emptyState").classList.remove("hidden");
    document.getElementById("copyBtn").classList.add("hidden");
    document.getElementById("savedBadge").classList.add("hidden");

    hideError();

    window.history.pushState({}, "", "index-at-calculation.php");
}

function markUnsaved() {
    isSaved = false;
    document.getElementById("copyBtn").classList.add("hidden");
    document.getElementById("savedBadge").classList.add("hidden");

    const previewStatus = document.getElementById("previewStatus");
    if (previewStatus) {
        previewStatus.innerText = "Unsaved";
    }
}

function showError(message) {
    const errorBox = document.getElementById("processError");
    errorBox.innerText = message;
    errorBox.classList.remove("hidden");
}

function hideError() {
    const errorBox = document.getElementById("processError");
    errorBox.innerText = "";
    errorBox.classList.add("hidden");
}

function formatMoney(amount) {
    return Number(amount || 0).toLocaleString("en-BD", {
        maximumFractionDigits: 0
    });
}

function escapeHtml(value) {
    return String(value ?? "")
        .replaceAll("&", "&amp;")
        .replaceAll("<", "&lt;")
        .replaceAll(">", "&gt;")
        .replaceAll('"', "&quot;")
        .replaceAll("'", "&#039;");
}
</script>

</body>
</html>