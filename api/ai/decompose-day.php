<?php
/**
 * api/ai/decompose-day.php (Gen-3)
 * POST { day_text, day_number?, context? }
 * Uses Gemini to decompose free-text day into typed JSON components.
 * Returns structured JSON per §7.2 contract.
 */
session_start();
require_once '../../../server/api_bootstrap.php';
require_once '../../../server/db_connection.php';
require_once '../../../server/ai-gemini.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success'=>false,'message'=>'POST only']); exit;
}

$in         = json_decode(file_get_contents('php://input'), true) ?: [];
$day_text   = trim($in['day_text']   ?? '');
$day_number = (int)($in['day_number'] ?? 1);
$context    = trim($in['context']    ?? '');   // e.g. "Thailand, 4 adults, Day 2 was Bangkok"

if (!$day_text) {
    echo json_encode(['success'=>false,'message'=>'day_text required']); exit;
}

$systemPrompt = <<<PROMPT
You convert one day of a travel itinerary, written in plain language by a travel operator, into a structured JSON object.

RULES (strict):
- Extract ONLY what the operator explicitly stated.
- Do NOT invent hotel names, prices, times, suppliers, or any fact.
- If you infer anything not stated, set "assumed": true on that element.
- Break the day into typed components. Types allowed: transport, hotel, activity, meal, flight, note.
- Normalise obvious phrasings (e.g. "by van" → transport_mode "van", "breakfast included" → meal type "breakfast").
- For hotel: extract name if given, city if given.
- For transport: extract from/to if given, mode if given.
- For activity: extract name, any attributes (meal, guide, etc).
- Output JSON ONLY. No prose, no markdown fences.

JSON shape to return:
{
  "day_number": <int>,
  "title": "<short day title>",
  "components": [
    {
      "type": "activity|transport|hotel|flight|meal|note",
      "label": "<extracted label>",
      "start_time": "<HH:MM or null>",
      "attributes": {},
      "purpose": "leisure|logistics|business",
      "assumed": false
    }
  ],
  "meals": { "breakfast": false, "lunch": false, "dinner": false },
  "unresolved": []
}
PROMPT;

$userMsg = "Day number: {$day_number}\n";
if ($context) $userMsg .= "Context: {$context}\n";
$userMsg .= "Operator input:\n{$day_text}";

$result = geminiJSON($systemPrompt, $userMsg, 1500);

if (!$result['success']) {
    echo json_encode(['success'=>false,'message'=>$result['error'] ?? 'AI failed', 'raw'=>$result['raw']??null]);
    exit;
}

$data = $result['data'];

// Basic validation
if (!isset($data['components']) || !is_array($data['components'])) {
    echo json_encode(['success'=>false,'message'=>'AI returned unexpected structure','raw'=>$data]);
    exit;
}

echo json_encode(['success'=>true,'data'=>$data], JSON_UNESCAPED_UNICODE);
