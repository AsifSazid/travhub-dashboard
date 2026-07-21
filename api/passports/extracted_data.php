<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

set_time_limit(120);

$response = [
    'success' => false,
    'data' => [],
    'error' => null
];

try {

    /* ================= FILE COLLECTION ================= */
    $files = [];

    if (!empty($_FILES['files'])) {
        // multiple files
        foreach ($_FILES['files']['tmp_name'] as $i => $tmp) {
            if ($_FILES['files']['error'][$i] === UPLOAD_ERR_OK) {
                $files[] = [
                    'tmp_name' => $tmp,
                    'type' => $_FILES['files']['type'][$i]
                ];
            }
        }
    } elseif (!empty($_FILES['file']) && $_FILES['file']['error'] === UPLOAD_ERR_OK) {
        // single file
        $files[] = $_FILES['file'];
    }

    if (empty($files)) {
        throw new Exception('No valid file uploaded');
    }

    /* ================= PROCESS EACH FILE ================= */
    foreach ($files as $file) {

        $content = file_get_contents($file['tmp_name']);
        if (!$content) continue;

        $prompt = passportPrompt();
        $geminiText = callGemini($content, $file['type'], $prompt);
        $data = parseGeminiJSON($geminiText);
        $data = normalizePassportData($data);

        // 🔥 required doc_code format
        $data['doc_code'] = generate3DOCSA($data);
        $data['doc_code_short'] = 'P';

        $response['data'][] = $data;
    }

    $response['success'] = true;

} catch (Exception $e) {
    $response['error'] = $e->getMessage();
}

echo json_encode($response, JSON_PRETTY_PRINT);
exit;


/* =======================================================
   PROMPT
======================================================= */
function passportPrompt() {
    return <<<PROMPT
Extract passport information including MRZ.
Return ONLY valid JSON:

{
 "salutation": "",
 "passport_no": "",
 "given_name": "",
 "sur_name": "",
 "nationality": "",
 "country_code": "",
 "date_of_birth": "YYYY-MM-DD",
 "date_of_expiry": "YYYY-MM-DD",
 "gender": "M/F",
 "relationship": "",  // e.g., "Married" / "Unmarried"
}
PROMPT;
}

/* =======================================================
   GEMINI CALL
======================================================= */
function callGemini($content, $type, $prompt) {

    $apiKey = trim(file_get_contents('../../gemini-apikey.txt'));
    if (!$apiKey) throw new Exception('API key missing');

    $model = 'gemini-2.5-flash';
    $url = "https://generativelanguage.googleapis.com/v1beta/models/$model:generateContent?key=$apiKey";

    if (str_starts_with($type, 'image/')) {
        $payload = [
            'contents' => [[
                'parts' => [
                    ['text' => $prompt],
                    ['inline_data' => [
                        'mime_type' => $type,
                        'data' => base64_encode($content)
                    ]]
                ]
            ]]
        ];
    } else {
        $payload = [
            'contents' => [[
                'parts' => [['text' => $prompt]]
            ]]
        ];
    }

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
        CURLOPT_POSTFIELDS => json_encode($payload),
        CURLOPT_TIMEOUT => 90
    ]);

    $res = curl_exec($ch);
    if (!$res) throw new Exception(curl_error($ch));
    curl_close($ch);

    $json = json_decode($res, true);
    return $json['candidates'][0]['content']['parts'][0]['text'] ?? '';
}

/* =======================================================
   PARSE GEMINI JSON
======================================================= */
function parseGeminiJSON($text) {
    $s = strpos($text, '{');
    $e = strrpos($text, '}');
    if ($s === false || $e === false) throw new Exception('Invalid response');
    return json_decode(substr($text, $s, $e - $s + 1), true);
}

/* =======================================================
   NORMALIZE DATA + SALUTATION
======================================================= */
function normalizePassportData($d) {

    $gender = strtoupper($d['gender'] ?? 'M');
    $relationship = strtolower($d['relationship'] ?? '');

    // Future-proof salutation logic
    $salutation = '';
    if ($gender === 'M') {
        $salutation = 'Mr';
    } elseif ($gender === 'F') {
        if (strpos($relationship, 'married') !== false) {
            $salutation = 'Mrs';
        } else {
            $salutation = 'Ms';
        }
    }

    return [
        'salutation' => $salutation,
        'passport_no' => strtoupper($d['passport_no'] ?? ''),
        'given_name' => strtoupper(trim($d['given_name'] ?? '')),
        'sur_name' => strtoupper(trim($d['sur_name'] ?? '')),
        'nationality' => strtoupper($d['nationality'] ?? ''),
        'country_code' => strtoupper($d['country_code'] ?? ''),
        'date_of_birth' => $d['date_of_birth'] ?? '',
        'date_of_expiry' => $d['date_of_expiry'] ?? '',
        'gender' => $gender,
        'relationship' => $relationship
    ];
}

/* =======================================================
   🔥 DOC CODE GENERATOR (YOUR FORMAT)
======================================================= */
function generate3DOCSA($d) {
    return implode('/', [
        '3DOCSA',
        'P',
        $d['country_code'],
        $d['passport_no'],
        $d['country_code'],
        toDDMMMYY($d['date_of_birth']),
        $d['gender'],
        toDDMMMYY($d['date_of_expiry']),
        $d['sur_name'],
        $d['given_name']
    ]) . '-1.1';
}

function toDDMMMYY($date) {
    $dt = DateTime::createFromFormat('Y-m-d', $date);
    return $dt ? strtoupper($dt->format('dMY')) : '';
}
