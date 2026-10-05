<?php
/**
 * FILE PATH: /api/package-services/extract-from-notes.php
 *
 * Mindboard notes → Gemini → Package itinerary / summary generate।
 *
 * POST (JSON):
 *   {
 *     work_sys_id:   string,
 *     note_sys_ids:  string[],
 *     action:        'itinerary' | 'summary',
 *   }
 *
 * OUTPUT (itinerary):
 *   {
 *     success: true,
 *     package: {
 *       title:          string,
 *       itinerary:      string,   // Day-by-day full text
 *       destinations:   [{ country, city }],
 *       duration_days:  number,
 *       pax:            number,
 *       services: {
 *         air_ticket:  { description, cost },
 *         hotel:       { description, cost },
 *         transport:   { description, cost },
 *         visa:        { description, cost },
 *         others:      [{ label, cost }],
 *       },
 *       pricing: {
 *         at_cost, hotel_cost, transport_cost, visa_cost, others_cost,
 *         base_cost, markup_pct, markup_amount, gross_total, per_pax, currency
 *       },
 *     }
 *   }
 *
 * OUTPUT (summary):
 *   { success: true, summary_text: string }
 */

session_start();
date_default_timezone_set('Asia/Dhaka');
ini_set('display_errors', 0);

require_once '../../server/api_bootstrap.php';
require_once '../../server/db_connection.php';
require_once '../../server/live_storage.php';
require_once '../../server/smb_upload_handler.php';
require_once '../../server/safe_folder_name.php';
require_once __DIR__ . '/../../server/ai-gemini.php';

header('Content-Type: application/json');

function pkJsonOut(array $data): never {
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

$body       = json_decode(file_get_contents('php://input'), true) ?: [];
$workSysId  = trim($body['work_sys_id']  ?? '');
$noteSysIds = $body['note_sys_ids']      ?? [];
$action     = trim($body['action']       ?? 'itinerary');

if (!$workSysId)        pkJsonOut(['success'=>false,'message'=>'work_sys_id required']);
if (empty($noteSysIds)) pkJsonOut(['success'=>false,'message'=>'note_sys_ids required']);

try {
    // ── Step 1: fetch notes ───────────────────────────────────
    $ph   = implode(',', array_fill(0, count($noteSysIds), '?'));
    $stmt = $pdo->prepare("
        SELECT sys_id, note_type, content, file_name, serve_url, work_sys_id, service_slug
        FROM task_notes
        WHERE sys_id IN ($ph) AND work_sys_id = ?
        ORDER BY id ASC
    ");
    $stmt->execute([...$noteSysIds, $workSysId]);
    $notes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if (!$notes) pkJsonOut(['success'=>false,'message'=>'Notes not found']);

    // ── Step 2: build Gemini parts ────────────────────────────
    $textParts  = [];
    $imageParts = [];
    $omv        = null;

    foreach ($notes as $n) {
        if ($n['note_type'] === 'text' && !empty($n['content'])) {
            $textParts[] = trim($n['content']);
        } elseif ($n['note_type'] === 'image' && !empty($n['serve_url'])) {
            // Attempt to fetch image as base64
            $url = $n['serve_url'];
            if (str_starts_with($url, 'smb://') || str_starts_with($url, '/')) {
                // local file
                $path = preg_replace('#^smb://[^/]+#', '', $url);
                if (file_exists($path)) {
                    $data  = base64_encode(file_get_contents($path));
                    $mime  = mime_content_type($path) ?: 'image/jpeg';
                    $imageParts[] = ['inline_data'=>['mime_type'=>$mime,'data'=>$data]];
                }
            } else {
                try {
                    $ctx  = stream_context_create(['http'=>['timeout'=>8]]);
                    $data = @file_get_contents($url, false, $ctx);
                    if ($data !== false) {
                        $ext  = strtolower(pathinfo($url, PATHINFO_EXTENSION));
                        $mime = match($ext) { 'png'=>'image/png','gif'=>'image/gif','webp'=>'image/webp', default=>'image/jpeg' };
                        $imageParts[] = ['inline_data'=>['mime_type'=>$mime,'data'=>base64_encode($data)]];
                    }
                } catch(Throwable $e) {}
            }
        } elseif ($n['note_type'] === 'audio' && !empty($n['serve_url'])) {
            $textParts[] = '[Voice note — audio file, content described: listen to what\'s said]';
        }
    }

    $textBlock = implode("\n\n---\n\n", $textParts);

    // ── Step 3: build prompt ──────────────────────────────────
    if ($action === 'summary') {
        $prompt = <<<PROMPT
You are an expert travel consultant's AI assistant for a Bangladeshi travel agency (TravHub).

Below are notes from the mindboard about a client's tour package inquiry. Create a concise professional summary in Bangla or English (match the note language) that captures:
- Client's travel intent
- Destinations interested in
- Budget or pricing discussed
- Service requirements (flights, hotel, transport, visa)
- Special requests or notes

Keep it professional and under 200 words. Omit vague or irrelevant parts.

=== NOTES ===
$textBlock
PROMPT;

        $parts   = array_merge([['text'=>$prompt]], $imageParts);
        $gemini  = callGemini($parts);
        $summary = trim($gemini['text'] ?? $gemini['candidates'][0]['content']['parts'][0]['text'] ?? '');
        if (!$summary) pkJsonOut(['success'=>false,'message'=>'Gemini returned empty response']);
        pkJsonOut(['success'=>true,'summary_text'=>$summary]);
    }

    // ── action = itinerary ─────────────────────────────────────
    $todayYear = date('Y');
    $prompt = <<<PROMPT
You are an expert travel consultant's AI assistant for TravHub, a Bangladeshi travel agency.

Based on the following mindboard notes about a client's tour package, extract and structure the information as a JSON object. The output must be valid JSON only — no markdown, no explanation.

Required JSON structure:
{
  "title": "Short package name, e.g. Dubai 5N6D — Mar {$todayYear}",
  "destinations": [{"country": "UAE", "city": "Dubai"}],
  "duration_days": 6,
  "pax": 2,
  "itinerary": "Full day-by-day itinerary text. Use format:\\nDay 1: Arrival at Dubai...\\nDay 2: ...\\n(Write in the same language as the notes — Bangla or English)",
  "services": {
    "air_ticket": {"description": "DAC-DXB return, Economy", "cost": 0},
    "hotel": {"description": "Hilton Dubai 5 nights BB", "cost": 0},
    "transport": {"description": "Airport transfer + city tour", "cost": 5000},
    "visa": {"description": "UAE Tourist Visa 30 days", "cost": 6000},
    "others": [{"label": "Travel Insurance", "cost": 2000}]
  },
  "pricing": {
    "at_cost": 0,
    "hotel_cost": 0,
    "transport_cost": 5000,
    "visa_cost": 6000,
    "others_cost": 2000,
    "base_cost": 13000,
    "markup_pct": 10,
    "markup_amount": 1300,
    "gross_total": 14300,
    "per_pax": 7150,
    "currency": "BDT"
  }
}

Rules:
- If a cost is not mentioned in notes, set it to 0
- If itinerary is not in notes, generate a sensible placeholder based on destinations and duration
- pax defaults to 1 if not mentioned
- currency defaults to "BDT"
- others array can be empty []
- Costs in "pricing" must match "services" costs and be consistent
- Return ONLY valid JSON — no comments, no markdown fences

=== CLIENT NOTES ===
$textBlock
PROMPT;

    $parts  = array_merge([['text'=>$prompt]], $imageParts);
    $gemini = callGemini($parts);
    $raw    = trim($gemini['text'] ?? $gemini['candidates'][0]['content']['parts'][0]['text'] ?? '');
    if (!$raw) pkJsonOut(['success'=>false,'message'=>'Gemini returned empty response']);

    // Strip markdown fences if present
    $raw = preg_replace('/^```(?:json)?\s*/i', '', $raw);
    $raw = preg_replace('/\s*```$/', '', $raw);
    $raw = trim($raw);

    $pkg = json_decode($raw, true);
    if (!$pkg) pkJsonOut(['success'=>false,'message'=>'Gemini JSON parse failed','raw'=>$raw]);

    // Ensure required keys
    $pkg['title']         ??= 'Package Quotation';
    $pkg['destinations']  ??= [];
    $pkg['duration_days'] ??= 0;
    $pkg['pax']           ??= 1;
    $pkg['itinerary']     ??= '';
    $pkg['services']      ??= [];
    $pkg['pricing']       ??= [
        'at_cost'=>0,'hotel_cost'=>0,'transport_cost'=>0,'visa_cost'=>0,'others_cost'=>0,
        'base_cost'=>0,'markup_pct'=>10,'markup_amount'=>0,'gross_total'=>0,'per_pax'=>0,'currency'=>'BDT'
    ];

    pkJsonOut(['success'=>true,'package'=>$pkg]);

} catch (Throwable $e) {
    pkJsonOut(['success'=>false,'message'=>$e->getMessage()]);
}