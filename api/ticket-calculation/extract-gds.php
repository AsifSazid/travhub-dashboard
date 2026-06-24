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
You are an expert GDS data parser. Your task is to extract structured flight segments and fare details from raw GDS text, and output a single, tightly validated JSON object.

Extract flight segments and fares from GDS text. Ignore ARNK. Return JSON only.

## Segment Rules:
- Format: Extract Flight, Class, Date, Route, Departure, Arrival.
- Identify Departure segments vs Return segments logically. Assign tags: 'D1', 'D2', 'R1', etc.
- Identify the operating Airline full name for EACH segment line based on the code prefix (e.g., 'TK' -> 'Turkish Airlines', 'CX' -> 'Cathay Pacific') and include it inside the segment dataset as \"airline_name\".
- Remove status codes completely.
- Route: add dash (DACIST → DAC-IST).
- Keep date format: 19MAY.

## Fare Rules:
- Parse passenger types: ADT=Adult, CNN/CHD/PSG=Child, INF=Infant.
- Map and extract exact numerical values for: base_fare (FARE), taxes (TAXES), and gross_fare (TOTAL / TOTAL PSG DES).
- If FARE/TAXES columns contain single values but are mapped to multiple passengers, replicate them properly under each node.

## Output JSON Example:
{
  \"airline\": \"Turkish Airlines\",
  \"segments\": [
    {\"flight\":\"TK713\",\"class\":\"M\",\"date\":\"19MAY\",\"route\":\"DAC-IST\",\"departure\":\"0650\",\"arrival\":\"1245\",\"tag\":\"D1\",\"airline_name\":\"Turkish Airlines\"}
  ],
  \"fares\": [
    {\"type\":\"ADT\",\"pax\":1,\"base_fare\":242758,\"taxes\":77047,\"gross_fare\":319805,\"iata_charge\": 0},
    {\"type\":\"CHD\",\"pax\":1,\"base_fare\":242758,\"taxes\":77047,\"gross_fare\":319805,\"iata_charge\": 0}
  ]
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