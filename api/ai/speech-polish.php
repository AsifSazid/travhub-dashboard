<?php
/**
 * api/ai/speech-polish.php
 * POST { raw_text } — polishes a voice transcript into structured package description
 */
session_start();
require_once '../../server/api_bootstrap.php';
require_once '../../server/ai-gemini.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'POST only']); exit;
}

$raw_text = trim($_POST['raw_text'] ?? '');
if (!$raw_text) {
    echo json_encode(['success' => false, 'message' => 'No text received']); exit;
}

$system = 'You are an expert travel content writer. Rewrite raw voice transcripts into professional, structured travel package descriptions in English only. Output ONLY the structured text — no preamble, no explanation.';

$user = "The following is a raw voice transcript about a travel package (may be Bangla, English, or Banglish). Understand the meaning and rewrite it as a professional travel package description using EXACTLY this structure:
[Package Title: Only one sentence, it may keeps 5/6 words (if need you may add 2/3 words more).]

[A compelling 1-2 sentence hook that captures the essence of the package]

✦ Highlights
- [key selling point]
- [key selling point]
- [key selling point]
- (add more if relevant)

✦ What's Included
- [included item]
- [included item]
- (add more if relevant)

✦ Travel Details
Destination: [extracted from transcript]
Duration: [extracted from transcript]
Package Type: [e.g. Group Tour / FIT / Corporate / Umrah]
Number Pax: [adult, child, infant (if mentioned, else omit category)]
Departure: [if mentioned, else omit this line]
Arrival: [if mentioned, else omit this line]

✦ Day to Day Itinerary [important, You can hallucinate here (not max than 20%)]

✦ Important Notes
[Any conditions, visa info, exclusions, or special instructions mentioned. If nothing mentioned, write: Please contact us for further details.]

RULES:
- Keep the EXACT meaning from the transcript. Do not invent details.
- If a section has no relevant info, skip that section entirely.
- Language must be ENGLISH throughout.
- Bullet points concise — one line each.

Raw transcript:
{$raw_text}";

$r = geminiCall($system, $user, 4500, 0.4);

if (!$r['success']) {
    echo json_encode(['success' => false, 'message' => $r['error'] ?? 'AI call failed']); exit;
}

echo json_encode([
    'success'        => true,
    'corrected_text' => $r['text'],
], JSON_UNESCAPED_UNICODE);