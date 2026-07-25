<?php
/**
 * FILE PATH: /api/ai/lead-speech-polish.php
 *
 * Lead Speech Polish — Service-Aware
 *
 * POST { raw_text, service_type? }
 *
 * Polishes a voice transcript or rough text into a clean lead inquiry
 * description tailored to the selected service. Output goes into the
 * promptArea for Extract & Build — NOT a package description.
 *
 * Difference from speech-polish.php:
 *   speech-polish.php  → formats text as a travel package description (for package builder)
 *   lead-speech-polish → formats text as a lead inquiry summary (for lead extraction)
 */

session_start();
require_once '../../server/api_bootstrap.php';
require_once '../../server/ai-gemini.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'POST only']); exit;
}

$raw_text    = trim($_POST['raw_text']    ?? '');
$serviceType = trim($_POST['service_type'] ?? '');

if (!$raw_text) {
    echo json_encode(['success' => false, 'message' => 'No text received']); exit;
}

// ── Service-specific lead inquiry formats ─────────────────────────────
$formats = [

    'air_ticket' => "Rewrite as a clear air ticket lead inquiry. Include:
- Client name (if mentioned)
- Route: From → To (use IATA codes if possible: DAC=Dhaka, DXB=Dubai, BKK=Bangkok, KUL=KL)
- Journey type: One Way / Round Trip / Multi City
- Departure date, Return date (if round trip)
- Class: Economy / Business / First (if mentioned)
- Number of passengers (adult/child/infant)
- Airline preference (if mentioned)
- Luggage requirement (if mentioned)
- Any special instructions",

    'visa' => "Rewrite as a clear visa lead inquiry. Include:
- Client name (if mentioned)
- Destination country
- Visa type (Tourist / Business / Student / etc. if mentioned)
- Number of applicants and names (if mentioned)
- Travel dates (if mentioned)
- Any special conditions or requirements",

    'hotel' => "Rewrite as a clear hotel booking lead inquiry. Include:
- Client name (if mentioned)
- Destination city and country
- Hotel name or preference (e.g. 5-star, specific hotel)
- Check-in date, Check-out date
- Number of rooms and room type (if mentioned)
- Any special requests",

    'tour_package' => "Rewrite as a clear tour package lead inquiry. Include:
- Client name (if mentioned)
- Destination(s)
- Travel duration (days/nights)
- Package type (Family / Couple / Group / Honeymoon / Solo)
- Number of travelers (adult/child)
- Budget (if mentioned)
- Any specific inclusions or requirements",

    'umrah' => "Rewrite as a clear Umrah lead inquiry. Include:
- Client name (if mentioned)
- Package type: Flight / Land / Group
- Preferred departure date
- Total nights, Makkah nights, Madina nights (if mentioned)
- Number of travelers
- Any special requirements",

    'transport' => "Rewrite as a clear transport lead inquiry. Include:
- Client name (if mentioned)
- Vehicle type (Car / Microbus / Bus / etc.)
- Route: From → To
- Travel date and time (if mentioned)
- Number of passengers
- Any special requirements (AC, luggage, etc.)",

];

$formats['package'] = $formats['tour_package'];

// ── Build prompt ──────────────────────────────────────────────────────
$formatInstruction = isset($formats[$serviceType])
    ? $formats[$serviceType]
    : "Rewrite as a clear travel lead inquiry summary covering: client name, service needed, destination, dates, number of travelers, and any special requirements.";

$system = 'You are a travel agency assistant. Convert raw voice transcripts or rough notes (Bangla, English, or Banglish) into clean, structured lead inquiry descriptions in English. Output ONLY the polished text — no preamble, no explanation, no labels like "Polished:" or "Output:".';

$user = "{$formatInstruction}

RULES:
- Keep the EXACT meaning — do not invent or assume details not mentioned
- Transliterate Bangla names to English (e.g. মেজর মেহেদী হাসান → Major Mehedi Hasan)
- Output in plain English prose or short bullet points — NOT a package description format
- If information is missing, simply omit it — do not write \"Not specified\"
- Keep it concise — this will be used as input for AI extraction

Raw input:
{$raw_text}";

$r = geminiCall($system, $user, 1500, 0.3);

if (!$r['success']) {
    echo json_encode(['success' => false, 'message' => $r['error'] ?? 'AI call failed']); exit;
}

echo json_encode([
    'success'        => true,
    'corrected_text' => $r['text'],
], JSON_UNESCAPED_UNICODE);