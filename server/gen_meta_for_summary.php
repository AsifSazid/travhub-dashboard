<?php
/**
 * TravHub Smart Upload v3 — Summary Meta Helpers
 * ==============================================
 * Specialized meta_data builders for the `travelers.summary` JSON field.
 *
 * IMPORTANT: This is a separate file from `generate_meta_data.php` to avoid
 * touching the existing helper which is used by many other APIs (invoicing,
 * package management, accounting, etc.).
 *
 * Used by:
 *   - server/summary-generator.php
 *   - api/travelers/regenerate-summary.php
 *
 * Produces the meta_data shape locked in spec:
 *   {
 *     "created_by_date": "demo_name; 12-10-2025 10:11",
 *     "trigger_type":    "document_upload",
 *     "tokens_used":     1234,
 *     "updated_by_date": [
 *       { "1": "demo_name; 12-10-2025 10:11", "trigger_type": "document_upload", "tokens_used": 265 },
 *       { "2": "demo_name; 13-10-2025 14:22", "trigger_type": "manual",          "tokens_used": 412 },
 *       ...
 *     ]
 *   }
 */


/**
 * Build a fresh meta_data object for FIRST-TIME summary creation.
 * Use this only when travelers.summary is currently NULL (no summary exists yet).
 *
 * @param  string  $actor        User name / "system"
 * @param  string  $triggerType  document_upload | manual
 * @param  int     $tokensUsed   Gemini tokens consumed for this generation
 * @return string  JSON-encoded meta_data
 */
function buildSummaryMeta($actor, $triggerType, $tokensUsed) {
    $actor      = trim($actor ?: 'system');
    $triggerType= validateTriggerType($triggerType);
    $tokensUsed = max(0, (int)$tokensUsed);

    $meta = [
        'created_by_date' => $actor . '; ' . date('d-m-Y H:i'),
        'trigger_type'    => $triggerType,
        'tokens_used'     => $tokensUsed,
        'updated_by_date' => [],
    ];

    return json_encode($meta, JSON_UNESCAPED_UNICODE);
}


/**
 * Append a new entry to an existing meta_data's updated_by_date[] array.
 * Use this when regenerating an EXISTING summary (the wrapper that's already
 * in travelers.summary).
 *
 * The numbered key inside each entry auto-increments based on array length.
 *
 * @param  string|null  $existingMetaJson  Current meta_data JSON, or null
 * @param  string       $actor
 * @param  string       $triggerType
 * @param  int          $tokensUsed
 * @return string                          Updated JSON
 */
function appendSummaryMeta($existingMetaJson, $actor, $triggerType, $tokensUsed) {
    $actor      = trim($actor ?: 'system');
    $triggerType= validateTriggerType($triggerType);
    $tokensUsed = max(0, (int)$tokensUsed);

    $meta = $existingMetaJson ? json_decode($existingMetaJson, true) : null;
    if (!is_array($meta)) {
        // Fallback: treat as first-time if existing meta is corrupted
        return buildSummaryMeta($actor, $triggerType, $tokensUsed);
    }

    if (!isset($meta['updated_by_date']) || !is_array($meta['updated_by_date'])) {
        $meta['updated_by_date'] = [];
    }

    $nextNumber = count($meta['updated_by_date']) + 1;

    $meta['updated_by_date'][] = [
        (string)$nextNumber => $actor . '; ' . date('d-m-Y H:i'),
        'trigger_type'      => $triggerType,
        'tokens_used'       => $tokensUsed,
    ];

    return json_encode($meta, JSON_UNESCAPED_UNICODE);
}


/**
 * Validate trigger type against known values. Falls back to 'manual' for
 * anything unrecognized (defensive — better to attribute to manual than to
 * silently store garbage).
 */
function validateTriggerType($triggerType) {
    $allowed = ['document_upload', 'manual'];
    $triggerType = strtolower(trim((string)$triggerType));
    return in_array($triggerType, $allowed, true) ? $triggerType : 'manual';
}