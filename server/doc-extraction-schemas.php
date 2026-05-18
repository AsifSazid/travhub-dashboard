<?php
/**
 * TravHub Smart Upload v3 — Document Extraction Schemas
 * =====================================================
 * Defines what structured fields Gemini should extract for each doc_type.
 *
 * Used by:
 *   - api/travelers/classify-document.php  (builds the Gemini prompt)
 *   - api/travelers/commit-documents.php   (pulls hot fields from doc_data)
 *
 * How to add a new structured schema:
 *   1. Add an entry to $DOC_EXTRACTION_SCHEMAS below
 *   2. Update doc_type_registry SET has_structured_schema=1 for that doc_type
 *   3. (Optional) If it should mirror to travelers table, set updates_traveler_column
 *
 * Doc types NOT in this file fall back to free-form `key_fields` extraction.
 * That's intentional — we only define structured schemas for the 6 core types.
 */

// ============================================================================
// Hot field mapping: which path inside doc_data populates which indexed column
// on traveler_documents. Used by commit-documents.php after extraction.
// ============================================================================
$DOC_HOT_FIELD_MAP = [
    'passport' => [
        // Hot fields aren't pulled from doc_data for passport because passport
        // bio is mirrored to travelers.passport_info. Expiry comes from
        // doc_data.bio_info.date_of_expiry parsed at commit time.
        'expiry_date'  => 'bio_info.date_of_expiry',
        'issue_date'   => 'bio_info.date_of_issue',
    ],
    'visa' => [
        'country'                => 'visa_info.country',
        'validity_from'          => 'visa_info.validity_from',
        'validity_to'            => 'visa_info.validity_to',
        'expiry_date'            => 'visa_info.validity_to',
        'linked_passport_number' => 'visa_info.holder_passport_number',
    ],
    'air_ticket' => [
        // Country isn't a single value for tickets (multi-segment trips),
        // but linked_passport ties ticket to passport holder.
        'linked_passport_number' => 'ticket_info.passenger_passport_number',
    ],
    'hotel_voucher' => [
        'country'      => 'hotel_info.country',
        'validity_from'=> 'hotel_info.check_in',
        'validity_to'  => 'hotel_info.check_out',
    ],
    'bank_statement' => [
        'validity_from'=> 'bank_info.statement_period_from',
        'validity_to'  => 'bank_info.statement_period_to',
    ],
];


// ============================================================================
// Per-doc-type extraction schemas
// ============================================================================
$DOC_EXTRACTION_SCHEMAS = [

    // ─────────────────────────────────────────────────────────────────────
    // PASSPORT
    // ─────────────────────────────────────────────────────────────────────
    'passport' => [
        'description' => 'Extract bio page fields + per-page metadata for visa stamps / entry-exit pages.',
        'schema' => [
            'bio_info' => [
                'passport_number'    => 'string — primary passport number',
                'country_code'       => 'string — ISO 3-letter code (BGD, IND, USA)',
                'surname'            => 'string',
                'given_names'        => 'string',
                'full_name'          => 'string — concatenated full name',
                'nationality'        => 'string',
                'date_of_birth'      => 'string — DD-MM-YYYY',
                'sex'                => 'string — M/F',
                'place_of_birth'     => 'string',
                'date_of_issue'      => 'string — DD-MM-YYYY',
                'date_of_expiry'     => 'string — DD-MM-YYYY',
                'issuing_authority'  => 'string',
                'father_name'        => 'string',
                'mother_name'        => 'string',
                'spouse_name'        => 'string',
                'permanent_address'  => 'string',
                'mrz_line_1'         => 'string — Machine Readable Zone line 1',
                'mrz_line_2'         => 'string — Machine Readable Zone line 2',
            ],
        ],
    ],

    // ─────────────────────────────────────────────────────────────────────
    // NID (Bangladeshi National ID)
    // ─────────────────────────────────────────────────────────────────────
    'nid' => [
        'description' => 'Extract NID front + back details (Bangladeshi NID; bilingual en/bn).',
        'schema' => [
            'nid_info' => [
                'nid_number'           => 'string — 10/13/17-digit NID number',
                'full_name_en'         => 'string — name in English',
                'full_name_bn'         => 'string — name in Bengali',
                'father_name_en'       => 'string',
                'father_name_bn'       => 'string',
                'mother_name_en'       => 'string',
                'mother_name_bn'       => 'string',
                'date_of_birth'        => 'string — DD-MM-YYYY',
                'sex'                  => 'string — M/F',
                'blood_group'          => 'string — A+, B-, etc. or empty',
                'permanent_address_en' => 'string',
                'permanent_address_bn' => 'string',
                'present_address_en'   => 'string',
                'issue_date'           => 'string — DD-MM-YYYY (if printed)',
            ],
        ],
    ],

    // ─────────────────────────────────────────────────────────────────────
    // VISA
    // ─────────────────────────────────────────────────────────────────────
    'visa' => [
        'description' => 'Extract visa label / sticker / e-visa details.',
        'schema' => [
            'visa_info' => [
                'visa_number'              => 'string — unique visa number',
                'country'                  => 'string — issuing country (full name, e.g. "United Arab Emirates")',
                'visa_type'                => 'string — tourist / business / work / student / transit / family / other',
                'visa_category'            => 'string — sub-category if shown (e.g. "Type C", "Schengen", "B1/B2")',
                'validity_from'            => 'string — YYYY-MM-DD (visa valid from)',
                'validity_to'              => 'string — YYYY-MM-DD (visa valid until)',
                'entries_allowed'          => 'string — single / multiple / double',
                'duration_of_stay'         => 'string — e.g. "30 days", "90 days"',
                'port_of_entry'            => 'string — designated port if specified, else empty',
                'issuing_authority'        => 'string — embassy/consulate name',
                'place_of_issue'           => 'string — city where issued',
                'date_of_issue'            => 'string — YYYY-MM-DD',
                'holder_name'              => 'string — visa holder full name',
                'holder_passport_number'   => 'string — passport this visa is attached to',
                'holder_nationality'       => 'string',
                'holder_date_of_birth'     => 'string — DD-MM-YYYY',
                'control_number'           => 'string — control/reference number if printed',
            ],
        ],
    ],

    // ─────────────────────────────────────────────────────────────────────
    // AIR TICKET
    // ─────────────────────────────────────────────────────────────────────
    'air_ticket' => [
        'description' => 'Extract e-ticket / airline ticket details, including all flight segments.',
        'schema' => [
            'ticket_info' => [
                'pnr'                          => 'string — booking reference / PNR (6 chars typically)',
                'ticket_number'                => 'string — 13-digit airline ticket number',
                'airline_code'                 => 'string — 2-letter IATA code',
                'airline_name'                 => 'string — full airline name',
                'passenger_name'               => 'string',
                'passenger_passport_number'    => 'string — if shown on ticket',
                'booking_reference'            => 'string — agency or GDS reference if different from PNR',
                'issue_date'                   => 'string — YYYY-MM-DD',
                'total_fare'                   => 'number — total amount paid',
                'currency'                     => 'string — 3-letter currency code (USD, BDT, AED)',
                'trip_type'                    => 'string — one-way / round-trip / multi-city',
                'flight_segments'              => [
                    'description' => 'Array, one entry per flight leg',
                    'item_schema' => [
                        'segment_no'         => 'integer',
                        'flight_number'      => 'string — e.g. EK 583',
                        'airline_code'       => 'string',
                        'from_airport'       => 'string — IATA code (DAC)',
                        'from_city'          => 'string',
                        'to_airport'         => 'string — IATA code (DXB)',
                        'to_city'            => 'string',
                        'depart_datetime'    => 'string — YYYY-MM-DD HH:MM (local time)',
                        'arrive_datetime'    => 'string — YYYY-MM-DD HH:MM (local time)',
                        'duration'           => 'string — e.g. "4h 30m"',
                        'class'              => 'string — economy / business / first',
                        'cabin_code'         => 'string — Y, J, F, etc.',
                        'baggage_allowance'  => 'string',
                    ],
                ],
            ],
        ],
    ],

    // ─────────────────────────────────────────────────────────────────────
    // HOTEL VOUCHER
    // ─────────────────────────────────────────────────────────────────────
    'hotel_voucher' => [
        'description' => 'Extract hotel booking confirmation / voucher details.',
        'schema' => [
            'hotel_info' => [
                'booking_reference'   => 'string — confirmation number',
                'hotel_name'          => 'string',
                'hotel_chain'         => 'string — if part of a chain (Marriott, Hilton, etc.)',
                'city'                => 'string',
                'country'             => 'string — full country name',
                'address'             => 'string — full hotel address',
                'phone'               => 'string',
                'check_in'            => 'string — YYYY-MM-DD',
                'check_out'           => 'string — YYYY-MM-DD',
                'nights'              => 'integer',
                'guest_name'          => 'string — primary guest',
                'number_of_guests'    => 'integer',
                'room_type'           => 'string — e.g. "Deluxe King", "Twin"',
                'number_of_rooms'     => 'integer',
                'meal_plan'           => 'string — room only / breakfast / half board / all inclusive',
                'total_amount'        => 'number',
                'currency'            => 'string — 3-letter currency code',
                'cancellation_policy' => 'string — brief description',
                'booked_through'      => 'string — direct, Booking.com, Agoda, etc.',
            ],
        ],
    ],

    // ─────────────────────────────────────────────────────────────────────
    // BANK STATEMENT
    // ─────────────────────────────────────────────────────────────────────
    'bank_statement' => [
        'description' => 'Extract bank statement summary (NOT individual transactions).',
        'schema' => [
            'bank_info' => [
                'bank_name'             => 'string',
                'branch_name'           => 'string',
                'account_holder'        => 'string',
                'account_number_masked' => 'string — show only last 4 digits, mask the rest (e.g. "****1234")',
                'account_type'          => 'string — savings / current / fixed deposit',
                'currency'              => 'string — 3-letter code',
                'statement_period_from' => 'string — YYYY-MM-DD',
                'statement_period_to'   => 'string — YYYY-MM-DD',
                'opening_balance'       => 'number',
                'closing_balance'       => 'number',
                'average_balance'       => 'number — if shown, else null',
                'total_credits'         => 'number — sum of deposits if shown',
                'total_debits'          => 'number — sum of withdrawals if shown',
                'transaction_count'     => 'integer — if shown',
            ],
        ],
    ],
];


// ============================================================================
// Helper functions
// ============================================================================

/**
 * Get the extraction schema for a given doc_type.
 * Returns null if no structured schema is defined (caller should fall back to key_fields).
 */
function getExtractionSchema($docType) {
    global $DOC_EXTRACTION_SCHEMAS;
    return $DOC_EXTRACTION_SCHEMAS[$docType] ?? null;
}

/**
 * Convert a schema array into a JSON-shaped instruction string for Gemini.
 * Used inside the classification prompt.
 *
 * Example output:
 *   "visa_info": {
 *       "visa_number": "string — unique visa number",
 *       "country": "string — issuing country (...)",
 *       ...
 *   }
 */
function formatSchemaForPrompt($docType) {
    $schemaDef = getExtractionSchema($docType);
    if (!$schemaDef) return '';

    $lines = [];
    foreach ($schemaDef['schema'] as $groupKey => $fields) {
        $lines[] = '"' . $groupKey . '": {';
        foreach ($fields as $fieldName => $fieldDesc) {
            if (is_array($fieldDesc) && isset($fieldDesc['item_schema'])) {
                // Nested array of objects (e.g. flight_segments)
                $lines[] = '    "' . $fieldName . '": [  // ' . $fieldDesc['description'];
                $lines[] = '        {';
                foreach ($fieldDesc['item_schema'] as $subName => $subDesc) {
                    $lines[] = '            "' . $subName . '": "' . $subDesc . '",';
                }
                $lines[] = '        }';
                $lines[] = '    ],';
            } else {
                $lines[] = '    "' . $fieldName . '": "' . $fieldDesc . '",';
            }
        }
        $lines[] = '}';
    }
    return implode("\n", $lines);
}

/**
 * Resolve a hot field value from doc_data using dot-notation path.
 * Example: resolveHotField($docData, 'visa_info.validity_to')
 */
function resolveHotField($docData, $path) {
    if (empty($docData) || empty($path)) return null;
    $parts = explode('.', $path);
    $cursor = $docData;
    foreach ($parts as $p) {
        if (!is_array($cursor) || !isset($cursor[$p])) return null;
        $cursor = $cursor[$p];
    }
    return $cursor;
}

/**
 * Extract hot fields (country, validity_from, validity_to, etc.) from doc_data
 * based on $DOC_HOT_FIELD_MAP. Returns an associative array suitable for the
 * traveler_documents UPDATE/INSERT.
 */
function extractHotFields($docType, $docData) {
    global $DOC_HOT_FIELD_MAP;
    $map = $DOC_HOT_FIELD_MAP[$docType] ?? [];
    $hot = [];
    foreach ($map as $column => $path) {
        $value = resolveHotField($docData, $path);
        if ($value !== null && $value !== '') {
            $hot[$column] = $value;
        }
    }
    return $hot;
}