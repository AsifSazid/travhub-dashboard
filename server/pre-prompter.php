<?php
/**
 * FILE PATH: /server/pre-prompter.php
 *
 * Pre-Prompter — Generic Prompt Enhancement Engine
 *
 * Takes a raw prompt + context data, calls Gemini in prompt-engineer role,
 * returns an enhanced, structured prompt string.
 *
 * The caller is responsible for:
 *   - Defining its own system prompt & JSON schema
 *   - Defining role instructions
 *   - Using the returned enhanced prompt in its own AI call
 *
 * Usage:
 *   require_once 'server/pre-prompter.php';
 *
 *   $enhanced = prePrompt($rawPrompt, $contextData);
 *   $result   = geminiJSON($mySystemPrompt, $enhanced);
 *
 *   // or for file extraction:
 *   $enhanced = prePrompt($rawPrompt, $contextData);
 *   $result   = geminiCallWithFile($filePath, $mimeType, $enhanced);
 */

if (!function_exists('geminiCall')) {
    require_once __DIR__ . '/ai-gemini.php';
}

/**
 * prePrompt
 *
 * @param  string $rawPrompt  Raw user input / instruction
 * @param  array  $context    Any context the caller wants to inject
 *                            (service_type, document_type, segment_type, etc.)
 * @return string             Enhanced prompt string — falls back to raw on failure
 */
function prePrompt(string $rawPrompt, array $context = []): string
{
    if (empty(trim($rawPrompt))) return $rawPrompt;

    $system = "You are an expert prompt engineer. Transform the given raw prompt into a highly detailed, strict, structured prompt for perfect JSON output. Use the provided context to make it specific and accurate. Return ONLY the enhanced prompt — nothing else.";

    $user = "Context: " . json_encode($context, JSON_UNESCAPED_UNICODE)
          . "\n\nRaw Prompt: " . $rawPrompt;

    $result = geminiCall($system, $user, 2000, 0.3);

    return $result['text'] ?? $rawPrompt;
}