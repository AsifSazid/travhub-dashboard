<?php
/**
 * ═══════════════════════════════════════════════════════════════════════
 * TravHub Global Limited — MODULE 02: sys_id Generator
 * ═══════════════════════════════════════════════════════════════════════
 * File   : server/id_generator.php
 * Requires: PDO connection available as $pdo
 *
 * Two ID families exist in TravHub:
 *
 * FAMILY A — FLAT IDs  (top-level entities, no parent)
 *   Format : THR-{SHORT}-{YY}-{BLOCK}K{SERIAL}
 *   Example: THR-PK-26-00K001
 *   Used by: packages, quotes, travelers, suppliers, currencies, fx_rates,
 *            components, hotels, transport_services, users, vouchers,
 *            client_payments, supplier_payments
 *
 * FAMILY B — HIERARCHICAL IDs  (child entities, scoped to a parent)
 *   Format : {PARENT_SYS_ID}-{SHORT}-{BASE36_SEQ}
 *   Example: THR-26-CNT-01-ACT-01  (activity inside country THR-26-CNT-01)
 *   Used by: countries, cities (JSON), cars, activities, activity_variants,
 *            activity_tags, room_types, room_rates, transport_variants,
 *            component_variants, package_days, package_items,
 *            package_travelers, quote_lines
 *
 * PUBLIC API
 * ──────────
 *   generateFlatID($pdo, string $tag)                     → string sys_id
 *   generateHierarchicalID($pdo, string $tag, string $parentSysId) → string sys_id
 *   generateIDs($pdo, string $tag)                        → ['uuid'=>..., 'sys_id'=>...]
 *   generateChildIDs($pdo, string $tag, string $parentSysId) → ['uuid'=>..., 'sys_id'=>...]
 *   uuidV4()                                              → string UUID
 *
 * USAGE EXAMPLES  (at bottom of file)
 * ═══════════════════════════════════════════════════════════════════════
 */

// ────────────────────────────────────────────────────────────────────────
// UTILITY HELPERS
// ────────────────────────────────────────────────────────────────────────

/**
 * Generate a UUID v4.
 */
function uuidV4(): string
{
    $data    = random_bytes(16);
    $data[6] = chr((ord($data[6]) & 0x0f) | 0x40); // version 4
    $data[8] = chr((ord($data[8]) & 0x3f) | 0x80); // variant RFC 4122
    return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
}

/**
 * Convert integer to Base-36 string with fixed width padding.
 * e.g. toBase36(10, 2) → "0A",  toBase36(35, 2) → "0Z"
 */
function toBase36(int $number, int $width = 2): string
{
    return str_pad(strtoupper(base_convert($number, 10, 36)), $width, '0', STR_PAD_LEFT);
}

/**
 * Decode a Base-36 string back to integer.
 */
function fromBase36(string $b36): int
{
    return (int) base_convert(strtolower($b36), 36, 10);
}


// ────────────────────────────────────────────────────────────────────────
// FAMILY A — FLAT ID GENERATOR
// Format: THR-{SHORT}-{YY}-{BLOCK}K{SERIAL}
// e.g.   THR-PK-26-00K001
// Block rolls over after 999 serial → 01K001, 02K001 …
// ────────────────────────────────────────────────────────────────────────

/**
 * Registry of all flat-ID entities.
 * 'short'  → 2-letter code embedded in the sys_id
 * 'table'  → MySQL table name
 * 'column' → sys_id column name (always 'sys_id')
 */
function _flatIDRegistry(): array
{
    return [
        // ── Core operations ──────────────────────────────────────────
        'packages'           => ['table' => 'packages',           'short' => 'PK'],
        'quotes'             => ['table' => 'quotes',             'short' => 'QT'],
        'travelers'          => ['table' => 'travelers',          'short' => 'TR'],
        'client_payments'    => ['table' => 'client_payments',    'short' => 'CP'],
        'supplier_payments'  => ['table' => 'supplier_payments',  'short' => 'SP'],
        'vouchers'           => ['table' => 'vouchers',           'short' => 'VCH'],

        // ── Catalog (master data) ─────────────────────────────────────
        'suppliers'          => ['table' => 'suppliers',          'short' => 'SUP'],
        'currencies'         => ['table' => 'currencies',         'short' => 'CCY'],
        'fx_rates'           => ['table' => 'fx_rates',           'short' => 'FXR'],
        'hotels'             => ['table' => 'hotels',             'short' => 'HTL'],
        'transport_services' => ['table' => 'transport_services', 'short' => 'TRS'],
        'components'         => ['table' => 'components',         'short' => 'CMP'],

        // ── System ────────────────────────────────────────────────────
        'users'              => ['table' => 'users',              'short' => 'USR'],

        // ── Legacy aliases (backward compatibility) ───────────────────
        'package-calculator' => ['table' => 'package_calculations', 'short' => 'PC'],
        'clients'            => ['table' => 'clients',            'short' => 'CL'],
        'vendors'            => ['table' => 'vendors',            'short' => 'VR'],
    ];
}

/**
 * Generate a FLAT sys_id for top-level entities.
 *
 * @param  PDO    $pdo  Active PDO connection
 * @param  string $tag  Registry key (e.g. 'packages', 'quotes')
 * @return string       e.g. "THR-PK-26-00K001"
 * @throws Exception    If tag not found in registry
 */
function generateFlatID(PDO $pdo, string $tag): string
{
    $registry = _flatIDRegistry();

    if (!isset($registry[$tag])) {
        throw new Exception("generateFlatID: unknown tag '{$tag}'");
    }

    $table  = $registry[$tag]['table'];
    $short  = $registry[$tag]['short'];
    $year   = date('y');   // 2-digit year, e.g. "26"
    $prefix = "THR-{$short}-{$year}";

    // Find last sys_id for this entity in the current year
    $stmt = $pdo->prepare("
        SELECT sys_id
        FROM `{$table}`
        WHERE sys_id LIKE :pattern
        ORDER BY id DESC
        LIMIT 1
    ");
    $stmt->execute([':pattern' => "{$prefix}-%"]);
    $lastSysId = $stmt->fetchColumn();

    if ($lastSysId) {
        // Pattern: THR-PK-26-00K001  →  last segment is  00K001
        $parts  = explode('-', $lastSysId);
        $last   = end($parts);                // "00K001"
        [$block, $serial] = explode('K', $last);

        $blockInt  = (int) $block;
        $serialInt = (int) $serial;

        if ($serialInt >= 999) {
            $blockInt++;
            $serialInt = 1;
        } else {
            $serialInt++;
        }

        $newSegment = str_pad($blockInt, 2, '0', STR_PAD_LEFT)
                    . 'K'
                    . str_pad($serialInt, 3, '0', STR_PAD_LEFT);
    } else {
        $newSegment = '00K001';
    }

    return "{$prefix}-{$newSegment}";
}


// ────────────────────────────────────────────────────────────────────────
// FAMILY B — HIERARCHICAL ID GENERATOR
// Format: {PARENT_SYS_ID}-{SHORT}-{BASE36_SEQ}
// e.g.   THR-26-CNT-01-ACT-01
//        THR-26-HTL-01-RMT-01-RMR-01   (2 levels deep)
// ────────────────────────────────────────────────────────────────────────

/**
 * Registry for hierarchical ID entities.
 *
 * 'short'        → segment appended to parent
 * 'table'        → table to query for last ID  (null = JSON parent, see special cases)
 * 'parent_field' → column in the table to filter by parent
 * 'width'        → zero-pad width for the Base-36 sequence number
 */
function _hierarchicalRegistry(): array
{
    return [
        // ── Countries (root hierarchical entity) ─────────────────────
        // Format: THR-{YY}-CNT-{BASE36}
        // Special: no parent, but uses year prefix like masterdata-id-generator
        'countries' => [
            'table'        => 'countries',
            'parent_field' => null,     // no parent — uses year prefix
            'short'        => 'CNT',
            'width'        => 2,
        ],

        // ── Cities (stored in countries.cities JSON — no table) ───────
        // parent = country sys_id, count from JSON array length
        'cities' => [
            'table'        => null,     // special: JSON count
            'parent_field' => null,
            'short'        => 'CTS',
            'width'        => 2,
        ],

        // ── Cars (scoped to country) ──────────────────────────────────
        'cars' => [
            'table'        => 'cars',
            'parent_field' => 'country_sys_id',
            'short'        => 'CAR',
            'width'        => 2,
        ],

        // ── Activities (scoped to country) ────────────────────────────
        'activities' => [
            'table'        => 'activities',
            'parent_field' => 'country_sys_id',
            'short'        => 'ACT',
            'width'        => 2,
        ],

        // ── Activity Variants (scoped to activity) ────────────────────
        'activity_variants' => [
            'table'        => 'activity_variants',
            'parent_field' => 'activity_sys_id',
            'short'        => 'AV',
            'width'        => 2,
        ],

        // ── Hotels (scoped to country — flat IDs, see generateFlatID)
        // room_types and room_rates ARE hierarchical under hotels
        'room_types' => [
            'table'        => 'room_types',
            'parent_field' => 'hotel_sys_id',
            'short'        => 'RMT',
            'width'        => 2,
        ],

        'room_rates' => [
            'table'        => 'room_rates',
            'parent_field' => 'room_type_sys_id',
            'short'        => 'RMR',
            'width'        => 2,
        ],

        // ── Transport Variants (scoped to transport service) ──────────
        'transport_variants' => [
            'table'        => 'transport_variants',
            'parent_field' => 'service_sys_id',
            'short'        => 'TRV',
            'width'        => 2,
        ],

        // ── Component Variants (scoped to component) ──────────────────
        'component_variants' => [
            'table'        => 'component_variants',
            'parent_field' => 'component_sys_id',
            'short'        => 'CMV',
            'width'        => 2,
        ],

        // ── Package Days (scoped to package) ─────────────────────────
        'package_days' => [
            'table'        => 'package_days',
            'parent_field' => 'package_sys_id',
            'short'        => 'PD',
            'width'        => 2,
        ],

        // ── Package Items (scoped to package) ────────────────────────
        'package_items' => [
            'table'        => 'package_items',
            'parent_field' => 'package_sys_id',
            'short'        => 'PI',
            'width'        => 3,   // 3-width: PI-001, PI-002 ...
        ],

        // ── Package Travelers (scoped to package) ─────────────────────
        'package_travelers' => [
            'table'        => 'package_travelers',
            'parent_field' => 'package_sys_id',
            'short'        => 'PT',
            'width'        => 2,
        ],

        // ── Quote Lines (scoped to quote) ─────────────────────────────
        'quote_lines' => [
            'table'        => 'quote_lines',
            'parent_field' => 'quote_sys_id',
            'short'        => 'QL',
            'width'        => 2,
        ],

        // ── Transport Services (scoped to country) ────────────────────
        'transport_services' => [
            'table'        => 'transport_services',
            'parent_field' => 'country_sys_id',
            'short'        => 'TRS',
            'width'        => 2,
        ],
    ];
}

/**
 * Generate a HIERARCHICAL sys_id scoped under a parent entity.
 *
 * @param  PDO    $pdo          Active PDO connection
 * @param  string $tag          Registry key (e.g. 'activities', 'room_types')
 * @param  string $parentSysId  The parent's sys_id value
 * @return string               e.g. "THR-26-CNT-01-ACT-01"
 * @throws Exception
 */
function generateHierarchicalID(PDO $pdo, string $tag, string $parentSysId = ''): string
{
    $registry = _hierarchicalRegistry();

    // ── Special case: countries (root, uses year prefix) ──────────────
    if ($tag === 'countries') {
        $company = 'THR';
        $year    = date('y');
        $prefix  = "{$company}-{$year}-CNT";

        $stmt = $pdo->prepare("
            SELECT sys_id FROM countries
            WHERE sys_id LIKE :pattern
            ORDER BY id DESC LIMIT 1
        ");
        $stmt->execute([':pattern' => "{$prefix}-%"]);
        $last = $stmt->fetchColumn();

        $next = $last ? fromBase36(substr($last, strrpos($last, '-') + 1)) + 1 : 1;
        return $prefix . '-' . toBase36($next, 2);
    }

    // ── Special case: cities (JSON column, count-based) ───────────────
    if ($tag === 'cities') {
        if (empty($parentSysId)) {
            throw new Exception("generateHierarchicalID: 'cities' requires a country sys_id");
        }
        $stmt = $pdo->prepare("SELECT cities FROM countries WHERE sys_id = ?");
        $stmt->execute([$parentSysId]);
        $json  = $stmt->fetchColumn();
        $arr   = $json ? json_decode($json, true) : [];
        $next  = count($arr) + 1;
        return "{$parentSysId}-CTS-" . toBase36($next, 2);
    }

    // ── All other hierarchical entities ───────────────────────────────
    if (!isset($registry[$tag])) {
        throw new Exception("generateHierarchicalID: unknown tag '{$tag}'");
    }

    if (empty($parentSysId)) {
        throw new Exception("generateHierarchicalID: '{$tag}' requires a parent sys_id");
    }

    $cfg   = $registry[$tag];
    $table = $cfg['table'];
    $short = $cfg['short'];
    $width = $cfg['width'];
    $field = $cfg['parent_field'];
    $prefix = "{$parentSysId}-{$short}";

    $stmt = $pdo->prepare("
        SELECT sys_id FROM `{$table}`
        WHERE `{$field}` = :parent AND sys_id LIKE :pattern
        ORDER BY id DESC LIMIT 1
    ");
    $stmt->execute([
        ':parent'  => $parentSysId,
        ':pattern' => "{$prefix}-%",
    ]);
    $last = $stmt->fetchColumn();

    $next = $last ? fromBase36(substr($last, strrpos($last, '-') + 1)) + 1 : 1;
    return $prefix . '-' . toBase36($next, $width);
}


// ────────────────────────────────────────────────────────────────────────
// CONVENIENCE WRAPPERS  (drop-in for existing code)
// ────────────────────────────────────────────────────────────────────────

/**
 * Generate UUID + flat sys_id for a top-level entity.
 * Use for: packages, quotes, travelers, suppliers, hotels, etc.
 *
 * @return array ['uuid' => string, 'sys_id' => string]
 */
function generateIDs(PDO $pdo, string $tag): array
{
    return [
        'uuid'   => uuidV4(),
        'sys_id' => generateFlatID($pdo, $tag),
    ];
}

/**
 * Generate UUID + hierarchical sys_id for a child entity.
 * Use for: activities, room_types, package_days, etc.
 *
 * @return array ['uuid' => string, 'sys_id' => string]
 */
function generateChildIDs(PDO $pdo, string $tag, string $parentSysId): array
{
    return [
        'uuid'   => uuidV4(),
        'sys_id' => generateHierarchicalID($pdo, $tag, $parentSysId),
    ];
}

/**
 * Backward-compatible wrapper: generateHierarchyIDs() used in old code.
 * Maps old masterdata-id-generator.php calls to new functions.
 *
 * @return array ['uuid' => string, 'sys_id' => string]
 */
function generateHierarchyIDs(PDO $pdo, string $tag, ?string $parentId = null): array
{
    $hierarchicalTags = [
        'countries', 'cities', 'cars', 'activities',
        'activity_variants', 'room_types', 'room_rates',
        'transport_services', 'transport_variants',
        'component_variants', 'package_days', 'package_items',
        'package_travelers', 'quote_lines',
    ];

    if (in_array($tag, $hierarchicalTags, true)) {
        return generateChildIDs($pdo, $tag, $parentId ?? '');
    }
    return generateIDs($pdo, $tag);
}


// ════════════════════════════════════════════════════════════════════════
// USAGE EXAMPLES
// ════════════════════════════════════════════════════════════════════════
/*

require_once __DIR__ . '/id_generator.php';

// ── FLAT IDs ──────────────────────────────────────────────────────────

// New package
$ids = generateIDs($pdo, 'packages');
// → ['uuid' => 'xxxxxxxx-...', 'sys_id' => 'THR-PK-26-00K001']

// New quote
$ids = generateIDs($pdo, 'quotes');
// → ['uuid' => '...', 'sys_id' => 'THR-QT-26-00K001']

// New supplier
$ids = generateIDs($pdo, 'suppliers');
// → ['uuid' => '...', 'sys_id' => 'THR-SUP-26-00K001']

// New hotel
$ids = generateIDs($pdo, 'hotels');
// → ['uuid' => '...', 'sys_id' => 'THR-HTL-26-00K001']

// New transport service
$ids = generateIDs($pdo, 'transport_services');
// → ['uuid' => '...', 'sys_id' => 'THR-TRS-26-00K001']

// New component
$ids = generateIDs($pdo, 'components');
// → ['uuid' => '...', 'sys_id' => 'THR-CMP-26-00K001']

// New traveler
$ids = generateIDs($pdo, 'travelers');
// → ['uuid' => '...', 'sys_id' => 'THR-TV-26-00K001']

// New currency
$ids = generateIDs($pdo, 'currencies');
// → ['uuid' => '...', 'sys_id' => 'THR-CCY-26-00K001']

// New voucher
$ids = generateIDs($pdo, 'vouchers');
// → ['uuid' => '...', 'sys_id' => 'THR-VCH-26-00K001']


// ── HIERARCHICAL IDs ──────────────────────────────────────────────────

// New country
$ids = generateChildIDs($pdo, 'countries', '');
// → ['uuid' => '...', 'sys_id' => 'THR-26-CNT-01']

// New city inside country THR-26-CNT-01
$ids = generateChildIDs($pdo, 'cities', 'THR-26-CNT-01');
// → ['uuid' => '...', 'sys_id' => 'THR-26-CNT-01-CTS-01']

// New car inside country THR-26-CNT-01
$ids = generateChildIDs($pdo, 'cars', 'THR-26-CNT-01');
// → ['uuid' => '...', 'sys_id' => 'THR-26-CNT-01-CAR-01']

// New activity inside country THR-26-CNT-01
$ids = generateChildIDs($pdo, 'activities', 'THR-26-CNT-01');
// → ['uuid' => '...', 'sys_id' => 'THR-26-CNT-01-ACT-01']

// New activity variant inside activity THR-26-CNT-01-ACT-01
$ids = generateChildIDs($pdo, 'activity_variants', 'THR-26-CNT-01-ACT-01');
// → ['uuid' => '...', 'sys_id' => 'THR-26-CNT-01-ACT-01-AV-01']

// New room type inside hotel THR-HTL-26-00K001
$ids = generateChildIDs($pdo, 'room_types', 'THR-HTL-26-00K001');
// → ['uuid' => '...', 'sys_id' => 'THR-HTL-26-00K001-RMT-01']

// New room rate inside room type THR-HTL-26-00K001-RMT-01
$ids = generateChildIDs($pdo, 'room_rates', 'THR-HTL-26-00K001-RMT-01');
// → ['uuid' => '...', 'sys_id' => 'THR-HTL-26-00K001-RMT-01-RMR-01']

// New transport variant inside service THR-TRS-26-00K001
$ids = generateChildIDs($pdo, 'transport_variants', 'THR-TRS-26-00K001');
// → ['uuid' => '...', 'sys_id' => 'THR-TRS-26-00K001-TRV-01']

// New component variant inside component THR-CMP-26-00K001
$ids = generateChildIDs($pdo, 'component_variants', 'THR-CMP-26-00K001');
// → ['uuid' => '...', 'sys_id' => 'THR-CMP-26-00K001-CMV-01']

// New package day inside package THR-PK-26-00K001
$ids = generateChildIDs($pdo, 'package_days', 'THR-PK-26-00K001');
// → ['uuid' => '...', 'sys_id' => 'THR-PK-26-00K001-PD-01']

// New package item inside package THR-PK-26-00K001
$ids = generateChildIDs($pdo, 'package_items', 'THR-PK-26-00K001');
// → ['uuid' => '...', 'sys_id' => 'THR-PK-26-00K001-PI-001']

// New package traveler inside package THR-PK-26-00K001
$ids = generateChildIDs($pdo, 'package_travelers', 'THR-PK-26-00K001');
// → ['uuid' => '...', 'sys_id' => 'THR-PK-26-00K001-PT-01']

// New quote line inside quote THR-QT-26-00K001
$ids = generateChildIDs($pdo, 'quote_lines', 'THR-QT-26-00K001');
// → ['uuid' => '...', 'sys_id' => 'THR-QT-26-00K001-QL-01']

// New transport service inside country THR-26-CNT-01
$ids = generateChildIDs($pdo, 'transport_services', 'THR-26-CNT-01');
// → ['uuid' => '...', 'sys_id' => 'THR-26-CNT-01-TRS-01']

*/
