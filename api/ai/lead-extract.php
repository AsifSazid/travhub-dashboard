<?php
/**
 * FILE PATH: /api/ai/lead-extract.php
 * POST { prompt, countries: [{sys_id, name}], services: [{slug, name}] }
 * → Gemini extracts structured lead data → return JSON
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

$prompt    = trim($data['prompt']);
$countries = $data['countries'] ?? []; // [{sys_id, name}]
$services  = $data['services']  ?? []; // [{slug, name}]

// Build country list for system prompt
$countryList = implode(', ', array_map(fn($c) => "{$c['name']} (id:{$c['sys_id']})", $countries));

// Build service list for system prompt
$serviceList = implode(', ', array_map(fn($s) => "{$s['slug']} ({$s['name']})", $services));

$system = <<<PROMPT
You are a travel lead data extraction assistant for a Bangladeshi travel agency called TravHub.

Extract structured data from the user's prompt and return ONLY valid JSON — no explanation, no markdown, no code blocks.

Available services: {$serviceList}
Available countries: {$countryList}

Return this exact JSON structure:
{
  "client": {
    "name": "string or empty",
    "phone": "string or empty",
    "email": "string or empty"
  },
  "source": "facebook|website|phone_call|walk_in|referral|other or empty",
  "services": ["slug1", "slug2"],
  "common": {
    "title": "short descriptive title or empty",
    "countries": [{"sys_id": "from list above", "name": "country name"}],
    "pax_adult": 1,
    "pax_child": 0,
    "pax_infant": 0,
    "budget": "number as string or empty",
    "notes": "any extra info or special requests"
  },
  "service_data": {
    "air_ticket": {
      "segments": [{
        "route": "DAC-DXB",
        "from": "DAC",
        "to": "DXB",
        "airline": "",
        "class": "Economy|Business|First",
        "luggage": {"value": "", "unit": "Kg"},
        "departure_date": "",
        "arrival_date": "",
        "special_instruction": []
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
- Only include in "services" array what the user actually mentioned
- Only include in "service_data" the keys that match selected services
- Match country names to the provided country list — use exact sys_id from list
- If a country is not in the list, use empty sys_id but keep the name
- For air ticket: use IATA codes if possible (DAC=Dhaka, DXB=Dubai, BKK=Bangkok etc)
- Dates in YYYY-MM-DD format, datetimes in YYYY-MM-DDTHH:mm format
- Budget as a number string in BDT (convert if needed: 1 lakh = 100000)
- pax counts as integers
- If info is not mentioned, leave as empty string or empty array
- Do NOT include service_data keys for services not selected
PROMPT;

$result = geminiJSON($system, $prompt, 2000);

if (!$result['success']) {
    // Retry once
    $result = geminiJSON($system, $prompt, 2000);
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

// Sanitize — ensure required keys exist
if (!isset($extracted['client']))       $extracted['client']       = [];
if (!isset($extracted['services']))     $extracted['services']     = [];
if (!isset($extracted['common']))       $extracted['common']       = [];
if (!isset($extracted['service_data'])) $extracted['service_data'] = [];
if (!isset($extracted['source']))       $extracted['source']       = '';

// Ensure common sub-keys
$extracted['common'] = array_merge([
    'title'          => '',
    'countries'      => [],
    'pax_adult'      => 1,
    'pax_child'      => 0,
    'pax_infant'     => 0,
    'budget'         => '',
    'notes'          => '',
], $extracted['common']);

// Validate pax are integers
foreach (['pax_adult','pax_child','pax_infant'] as $f)
    $extracted['common'][$f] = (int)($extracted['common'][$f] ?? 0);

ob_clean();
echo json_encode(['status' => 'success', 'data' => $extracted]);