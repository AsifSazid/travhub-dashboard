<?php
/**
 * FILE PATH: /api/ai/lead-extract.php
 *
 * Lead AI Extraction — POST endpoint
 *
 * Responsible for:
 *   - Defining the Lead extraction system prompt + JSON schema
 *   - Defining per-service role instructions
 *   - Calling prePrompt() to enhance the raw user input
 *   - Calling Gemini and returning structured lead data
 *
 * Body: {
 *   prompt:       string   — raw user prompt (typed / voice-transcribed)
 *   service_type: string   — pre-selected service slug (optional)
 *   countries:    [{sys_id, name}]
 *   services:     [{slug, name}]
 * }
 *
 * Returns: { status: 'success', data: { client, services, segment_type, common, service_data } }
 */

ob_start();
session_start();
date_default_timezone_set('Asia/Dhaka');
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');
ini_set('display_errors', 0);

require_once '../../server/ai-gemini.php';

$raw  = file_get_contents('php://input');
$data = json_decode($raw, true);

if (!$data || empty($data['prompt'])) {
    ob_clean();
    echo json_encode(['status' => 'error', 'message' => 'prompt is required']);
    exit;
}

$rawPrompt   = trim($data['prompt']);
$serviceType = trim($data['service_type'] ?? '');
$countries   = $data['countries'] ?? [];
$services    = $data['services']  ?? [];

// ── Step 1: use raw prompt directly ──────────────────────────────────
// Pre-prompter is skipped here intentionally.
// The raw prompt (typed text, voice transcript, pasted content) goes
// directly to the extraction call — the system prompt below is already
// detailed enough. Adding an enhance step in between loses information
// (especially client names, dates, and segment details).
$extractionPrompt = $rawPrompt;

// ── Step 2: build the system prompt (lead-specific, defined HERE) ─────
$countryList = implode(', ', array_map(fn($c) => "{$c['name']} (id:{$c['sys_id']})", $countries));
$serviceList = implode(', ', array_map(fn($s) => "{$s['slug']} ({$s['name']})", $services));

// Per-service role instruction — tells AI what to focus on for this service
$serviceRoles = [
    'air_ticket'   => "You are acting as an AIR TICKET specialist. Focus on: FLIGHT SEGMENTS (from, to, route, date, class, airline), segment type (one_way/round_trip/multi_city), and passenger counts. For round_trip: generate exactly 2 segments (outbound + return). For multi_city: extract all segments mentioned.",
    'visa'         => "You are acting as a VISA specialist. Focus on: DESTINATION COUNTRY, visa type, applicant count and names, travel dates, and any special conditions.",
    'hotel'        => "You are acting as a HOTEL specialist. Focus on: HOTEL NAME or preference, city/country, check-in date, check-out date, number of rooms, room type, star rating preference.",
    'package'      => "You are acting as a TOUR PACKAGE specialist. Focus on: DESTINATIONS, travel duration, package type (family/couple/group/honeymoon), inclusions, number of travelers, budget.",
    'tour_package' => "You are acting as a TOUR PACKAGE specialist. Focus on: DESTINATIONS, travel duration, package type (family/couple/group/honeymoon), inclusions, number of travelers, budget.",
    'umrah'        => "You are acting as an UMRAH specialist. Focus on: departure date, total nights (Makkah + Madina split), package type (flight/land/group), umrah type, group size.",
    'transport'    => "You are acting as a TRANSPORT specialist. Focus on: route (from/to), vehicle type, travel date/time, passenger count, special requirements (AC, luggage, etc.).",
];

$roleInstruction = isset($serviceRoles[$serviceType])
    ? $serviceRoles[$serviceType] . "\n\nPrimary service: {$serviceType}. Also detect other services if explicitly mentioned."
    : "Detect which service(s) are being requested from the available list and extract accordingly.";

$system = <<<SYSTEM
You are a lead data extraction assistant for TravHub, a Bangladeshi travel agency.
Extract structured data from the user's description and return ONLY valid JSON — no explanation, no markdown, no code blocks.

Available services: {$serviceList}
Available countries: {$countryList}

{$roleInstruction}

CRITICAL EXTRACTION RULES:
- Client name: extract ANY person's name mentioned — even in Bangla (e.g. "মেজর মেহেদী হাসান" → "Major Mehedi Hasan"), transliterate to English
- Client phone/email: extract if mentioned anywhere in the text
- For air_ticket: ALWAYS populate ALL segment fields — route, from, to, airline, class, luggage, departure_date, arrival_date, date_flexibility, special_instruction
- departure_date = outbound travel date, arrival_date = return/landing date at destination
- If text says "September 9" or "৯ সেপ্টেম্বর" → "2026-09-09" (use current year if no year given)
- Segment route: always "FROM-TO" format e.g. "DAC-BKK"
- If round_trip: segment[0] = outbound (DAC→BKK), segment[1] = return (BKK→DAC)
- Extract airline preference even if vague (e.g. "Biman", "Emirates", "any airline" → keep as-is)
- Extract class even if implied (e.g. "economy class", "business" → "Economy" / "Business")
- special_instruction: any specific requests, notes, conditions mentioned

Return this exact JSON structure:
{
  "client": {
    "name": "Full name in English (transliterate from Bangla if needed) or empty",
    "phone": "string or empty",
    "email": "string or empty"
  },
  "source": "facebook|website|phone_call|walk_in|referral|other or empty",
  "services": ["slug1", "slug2"],
  "segment_type": "one_way|round_trip|multi_city or empty",
  "common": {
    "title": "short descriptive title including travel date if mentioned (e.g. 'Major Mehedi — DAC-BKK Round Trip — Sep 2026') or empty",
    "countries": [{"sys_id": "from list above", "name": "country name"}],
    "pax_adult": 1,
    "pax_child": 0,
    "pax_infant": 0,
    "tentative_start_date": "tentative starting date"
    "tentative_end_date": "tentative end date"
    "budget": "number as string or empty",
    "notes": "any extra info or special requests"
  },
  "service_data": {
    "air_ticket": {
      "segment_type": "one_way|round_trip|multi_city",
      "segments": [{
        "route": "DAC-BKK",
        "from": "DAC",
        "to": "BKK",
        "airline": "preference or empty",
        "class": "Economy|Premium|Business|First or empty",
        "luggage": {"value": "20 or empty", "unit": "Kg"},
        "departure_date": "YYYY-MM-DD or empty",
        "arrival_date": "YYYY-MM-DD or empty",
        "date_flexibility": "Fixed|±3 days|±7 days|Flexible|Specific month or empty",
        "special_instruction": ["any notes"]
      }]
    },
    "hotel": {
      "segments": [{
        "country_sys_id": "",
        "country_name": "",
        "city_sys_id": "",
        "city_name": "",
        "hotel_name": "",
        "hotel_sys_id": "",
        "check_in": "",
        "check_out": "",
        "special_instruction": []
      }],
      "booking_flexibility": ""
    },
    "visa": {
      "segments": [{
        "country_sys_id": "",
        "country_name": "",
        "invitation_status": "No",
        "applicants": [{"name": "", "profession": "", "is_main": true}],
        "cost_bearer": ["self"],
        "self_bearers": [0],
        "special_instruction": []
      }]
    },
    "tour_package": {
      "title": "",
      "type": "Family|Couple|Group|Solo|Honeymoon",
      "currency": "BDT",
      "description": "",
      "destinations": [{"country_id": "", "country_name": "", "city_ids": [], "city_names": []}],
      "accommodations": [],
      "special_instruction": []
    },
    "umrah": {
      "umrah_type": "umrah_visa",
      "package_type": "flight",
      "flight_date_type": "fixed",
      "departure_date": "",
      "total_nights": 14,
      "makkah_nights": 9,
      "madina_nights": 5,
      "has_transport": "no",
      "description": "",
      "special_instruction": []
    },
    "transport": {
      "segments": [{
        "type": "Microbus|Bus|Car|Train|Ferry",
        "route": "",
        "from": "",
        "to": "",
        "start_datetime": "",
        "end_datetime": "",
        "luggage": {"value": "", "unit": "Pieces"},
        "special_instruction": []
      }]
    }
  }
}

Rules:
- Only include in "services" what is actually mentioned
- Only include in "service_data" keys for detected/selected services
- air_ticket round_trip: EXACTLY 2 segments (outbound + return) — both fully populated
- air_ticket multi_city: all segments mentioned
- Match country names to the provided list — use exact sys_id
- If country not in list: empty sys_id, keep name
- IATA: DAC=Dhaka, DXB=Dubai, BKK=Bangkok, KUL=KL, CGP=Chittagong, ZYL=Sylhet, CXB=Cox's Bazar
- Dates: YYYY-MM-DD. Datetimes: YYYY-MM-DDTHH:mm. Current year: 2026
- Budget as number string in BDT (1 lakh = 100000)
- pax as integers (minimum 1 adult)
- Missing info: empty string or empty array — never omit a field from the schema
SYSTEM;

// ── Step 3: call Gemini ───────────────────────────────────────────────
$result = geminiJSON($system, $extractionPrompt, 8192);

if (!$result['success']) {
    $result = geminiJSON($system, $extractionPrompt, 8192); // single retry
}

if (!$result['success']) {
    ob_clean();
    echo json_encode([
        'status'  => 'error',
        'message' => $result['error'] ?? 'AI extraction failed',
        'raw'     => $result['raw'] ?? null,
    ]);
    exit;
}

$extracted = $result['data'];

// ── Step 4: sanitise output ───────────────────────────────────────────
if (!isset($extracted['client']))       $extracted['client']       = [];
if (!isset($extracted['services']))     $extracted['services']     = [];
if (!isset($extracted['common']))       $extracted['common']       = [];
if (!isset($extracted['service_data'])) $extracted['service_data'] = [];
if (!isset($extracted['source']))       $extracted['source']       = '';
if (!isset($extracted['segment_type'])) $extracted['segment_type'] = '';

// Normalise segment_type
$validSegTypes = ['one_way', 'round_trip', 'multi_city'];
if (!in_array($extracted['segment_type'], $validSegTypes, true)) {
    $atSegType = $extracted['service_data']['air_ticket']['segment_type'] ?? '';
    $extracted['segment_type'] = in_array($atSegType, $validSegTypes, true) ? $atSegType : '';
}

// Ensure common sub-keys
$extracted['common'] = array_merge([
    'title'     => '',
    'countries' => [],
    'pax_adult'  => 1,
    'pax_child'  => 0,
    'pax_infant' => 0,
    'budget'    => '',
    'notes'     => '',
], $extracted['common']);

foreach (['pax_adult', 'pax_child', 'pax_infant'] as $f) {
    $extracted['common'][$f] = (int)($extracted['common'][$f] ?? 0);
}

// round_trip safety: ensure exactly 2 segments
if (
    $extracted['segment_type'] === 'round_trip' &&
    isset($extracted['service_data']['air_ticket']['segments'])
) {
    $segs = $extracted['service_data']['air_ticket']['segments'];
    if (count($segs) === 1) {
        $outbound = $segs[0];
        $extracted['service_data']['air_ticket']['segments'] = [$outbound, [
            'route'               => ($outbound['to'] ?? '') . '-' . ($outbound['from'] ?? ''),
            'from'                => $outbound['to']           ?? '',
            'to'                  => $outbound['from']         ?? '',
            'airline'             => $outbound['airline']      ?? '',
            'class'               => $outbound['class']        ?? '',
            'luggage'             => $outbound['luggage']      ?? ['value' => '', 'unit' => 'Kg'],
            'departure_date'      => $outbound['arrival_date'] ?? '',
            'arrival_date'        => '',
            'date_flexibility'    => $outbound['date_flexibility'] ?? '',
            'special_instruction' => [],
        ]];
    }
}

ob_clean();
echo json_encode(['status' => 'success', 'data' => $extracted]);