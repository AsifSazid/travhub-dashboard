<?php
// api/ticket-calculation/extract-gds.php

header("Content-Type: application/json");

$data = json_decode(file_get_contents("php://input"), true);
$rawGds = trim($data["raw_gds"] ?? "");

if ($rawGds === "") {
    echo json_encode([
        "success" => false,
        "message" => "Raw GDS text is required"
    ]);
    exit;
}

$apiKeyFile = '../../gemini-apikey.txt';

if (!file_exists($apiKeyFile)) {
    echo json_encode([
        'success' => false,
        'message' => 'Gemini API key file not found'
    ]);
    exit;
}

$apiKey = trim(file_get_contents($apiKeyFile));

$systemInstruction = "
You are an expert GDS data parser. Your task is to extract structured flight segments and fare details from raw GDS text combined with human-readable pricing notes, and output a single, tightly validated JSON object.

Extract flight segments and fares from GDS text. Ignore ARNK. Return JSON only.

## Segment Rules:
- Format: [Line] [Flight] [Class] [Date] [Day] [Route] [Dep] [Arr] [Tag]
- Tags: D1/D2=Departure, R1/R2=Return
- Remove status codes
- Route: add dash (DACHKG → DAC-HKG)
- Keep date format: 04SEP
- Time format: keep as is (0210, 0810)

## Fare Rules:
- Parse passenger types: ADT=Adult, CNN=Child, INF=Infant
- Extract base fare, taxes, gross fare from fare lines
- If total fare given, use that

## Output JSON:
{
  \"airline\": \"Cathay Pacific\",
  \"segments\": [
    {\"flight\":\"CX662\",\"class\":\"Q\",\"date\":\"04SEP\",\"route\":\"DAC-HKG\",\"departure\":\"0210\",\"arrival\":\"0810\",\"tag\":\"D1\"}
  ],
  \"fares\": [
    {\"type\":\"ADT\",\"pax\":1,\"base_fare\":99646,\"taxes\":0,\"gross_fare\":99646},
    {\"type\":\"CNN\",\"pax\":1,\"base_fare\":73110,\"taxes\":0,\"gross_fare\":73110}
  ],
  \"total_fare\": 272402
}
";

$payload = [
    "system_instruction" => [
        "parts" => [
            ["text" => $systemInstruction]
        ]
    ],
    "contents" => [
        [
            "role" => "user",
            "parts" => [
                ["text" => $rawGds]
            ]
        ]
    ],
    "generationConfig" => [
        "temperature" => 0.1,
        "responseMimeType" => "application/json"
    ]
];

$url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key=" . $apiKey;

$ch = curl_init($url);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_HTTPHEADER => [
        "Content-Type: application/json"
    ],
    CURLOPT_POSTFIELDS => json_encode($payload)
]);

$response = curl_exec($ch);
$error = curl_error($ch);
curl_close($ch);

if ($error) {
    echo json_encode([
        "success" => false,
        "message" => $error
    ]);
    exit;
}

$result = json_decode($response, true);
$text = $result["candidates"][0]["content"]["parts"][0]["text"] ?? "";

$parsed = json_decode($text, true);

if (!$parsed) {
    echo json_encode([
        "success" => false,
        "message" => "AI response parsing failed",
        "raw_response" => $text
    ]);
    exit;
}

echo json_encode([
    "success" => true,
    "data" => $parsed
]);