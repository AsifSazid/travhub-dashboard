<?php
/**
 * api/tools/cv-extractor.php
 *
 * Accepts an uploaded CV (PDF or image) and uses Gemini to extract
 * structured resume data (name, contact, experience, education, skills...).
 *
 * Follows the same conventions used elsewhere in TravHub:
 *  - geminiCallWithFile() helper from ai-gemini.php for PDF/image -> JSON extraction
 *  - top-level error handler to catch PHP fatals and always return JSON
 *  - gemini-apikey.txt at project root for the API key (same convention as
 *    the encryption key file used for traveler credentials)
 *
 * Response shape:
 *   { "success": true,  "data": { ...extracted fields... } }
 *   { "success": false, "message": "..." }
 */

header('Content-Type: application/json; charset=utf-8');

// ---- Catch fatals so the client always gets JSON, never a raw PHP error page ----
set_error_handler(function ($severity, $message, $file, $line) {
    throw new ErrorException($message, 0, $severity, $file, $line);
});
register_shutdown_function(function () {
    $err = error_get_last();
    if ($err && in_array($err['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
        if (!headers_sent()) {
            header('Content-Type: application/json; charset=utf-8');
        }
        echo json_encode([
            'success' => false,
            'message' => 'Server error during CV extraction.',
        ]);
    }
});

require_once __DIR__ . '/../../server/ai-gemini.php'; // provides geminiCallWithFile()

const MAX_FILE_BYTES = 12 * 1024 * 1024; // 12MB
const ALLOWED_MIME = [
    'application/pdf',
    'image/jpeg',
    'image/png',
    'image/webp',
];

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new RuntimeException('Only POST is allowed.', 405);
    }

    if (empty($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
        throw new RuntimeException('No file uploaded, or upload failed.', 400);
    }

    $file = $_FILES['file'];

    if ($file['size'] > MAX_FILE_BYTES) {
        throw new RuntimeException('File too large (max 12MB).', 400);
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($file['tmp_name']);

    if (!in_array($mime, ALLOWED_MIME, true)) {
        throw new RuntimeException('Unsupported file type. Upload a PDF or image (jpg/png/webp).', 400);
    }

    $fileBytes = file_get_contents($file['tmp_name']);
    if ($fileBytes === false) {
        throw new RuntimeException('Could not read uploaded file.', 500);
    }

    // ---- Prompt: ask Gemini for a strict JSON resume schema ----
    $prompt = <<<PROMPT
You are a resume/CV data extraction engine. Read the attached document (a person's CV/resume)
and extract the information into STRICT JSON only — no markdown, no commentary, no code fences.

Return exactly this shape (omit a field entirely if it is not present in the document; do not invent data):

{
  "name": "string",
  "title": "string (current or most recent job title / desired role)",
  "email": "string",
  "phone": "string",
  "location": "string",
  "website": "string",
  "linkedin": "string",
  "github": "string",
  "facebook": "string",
  "summary": "string (2-4 sentence professional summary, written from the CV content if not already present)",
  "experiences": [
    { "role": "string", "company": "string", "date": "string (e.g. Jan 2023 - Present)", "desc": "string" }
  ],
  "education": [
    { "degree": "string", "inst": "string", "year": "string (year or result/GPA)" }
  ],
  "skills": ["string", "..."],
  "languages": [
    { "lang": "string", "level": "string (e.g. Native, Fluent, Intermediate)" }
  ],
  "certifications": ["string", "..."]
}

Rules:
- Output must be valid JSON and nothing else.
- Do not fabricate contact details, dates, or employers that are not in the document.
- Keep "desc" fields concise (1-3 sentences), rewritten in clear professional English if needed.
- "skills" should be individual skill names, not comma-joined sentences.
PROMPT;

    // geminiCallWithFile(bytes, mimeType, prompt) -> returns raw text response from Gemini
    // (mirrors usage in TravHub's file-upload + AI extract flows, e.g. Air Ticket module)
    $rawResponse = geminiCallWithFile($fileBytes, $mime, $prompt);

    if (!$rawResponse) {
        throw new RuntimeException('AI extraction returned no response.', 502);
    }

    // Strip possible ```json fences defensively even though the prompt forbids them
    $clean = trim($rawResponse);
    $clean = preg_replace('/^```json\s*/i', '', $clean);
    $clean = preg_replace('/```$/', '', $clean);
    $clean = trim($clean);

    $extracted = json_decode($clean, true);

    if (json_last_error() !== JSON_ERROR_NONE || !is_array($extracted)) {
        throw new RuntimeException('AI response was not valid JSON.', 502);
    }

    // Whitelist/sanitize the fields we hand back to the client
    $safe = [
        'name'           => (string)($extracted['name'] ?? ''),
        'title'          => (string)($extracted['title'] ?? ''),
        'email'          => (string)($extracted['email'] ?? ''),
        'phone'          => (string)($extracted['phone'] ?? ''),
        'location'       => (string)($extracted['location'] ?? ''),
        'website'        => (string)($extracted['website'] ?? ''),
        'linkedin'       => (string)($extracted['linkedin'] ?? ''),
        'github'         => (string)($extracted['github'] ?? ''),
        'facebook'       => (string)($extracted['facebook'] ?? ''),
        'summary'        => (string)($extracted['summary'] ?? ''),
        'experiences'    => [],
        'education'      => [],
        'skills'         => [],
        'languages'      => [],
        'certifications' => [],
    ];

    if (!empty($extracted['experiences']) && is_array($extracted['experiences'])) {
        foreach ($extracted['experiences'] as $e) {
            if (!is_array($e)) continue;
            $safe['experiences'][] = [
                'role'    => (string)($e['role'] ?? ''),
                'company' => (string)($e['company'] ?? ''),
                'date'    => (string)($e['date'] ?? ''),
                'desc'    => (string)($e['desc'] ?? ''),
            ];
        }
    }

    if (!empty($extracted['education']) && is_array($extracted['education'])) {
        foreach ($extracted['education'] as $e) {
            if (!is_array($e)) continue;
            $safe['education'][] = [
                'degree' => (string)($e['degree'] ?? ''),
                'inst'   => (string)($e['inst'] ?? ''),
                'year'   => (string)($e['year'] ?? ''),
            ];
        }
    }

    if (!empty($extracted['skills']) && is_array($extracted['skills'])) {
        foreach ($extracted['skills'] as $s) {
            $s = trim((string)$s);
            if ($s !== '') $safe['skills'][] = $s;
        }
    }

    if (!empty($extracted['languages']) && is_array($extracted['languages'])) {
        foreach ($extracted['languages'] as $l) {
            if (!is_array($l)) continue;
            $safe['languages'][] = [
                'lang'  => (string)($l['lang'] ?? ''),
                'level' => (string)($l['level'] ?? ''),
            ];
        }
    }

    if (!empty($extracted['certifications']) && is_array($extracted['certifications'])) {
        foreach ($extracted['certifications'] as $c) {
            $c = trim((string)$c);
            if ($c !== '') $safe['certifications'][] = $c;
        }
    }

    echo json_encode([
        'success' => true,
        'data'    => $safe,
    ]);

} catch (Throwable $e) {
    $code = ($e instanceof RuntimeException && $e->getCode() >= 400 && $e->getCode() < 600)
        ? $e->getCode()
        : 500;
    http_response_code($code);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
    ]);
}