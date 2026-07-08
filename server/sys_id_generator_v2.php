<?php
/**
 * ═══════════════════════════════════════════════════════════
 * TravHub Global Limited — sys_id Generator v2
 * ═══════════════════════════════════════════════════════════
 * File: server/sys_id_generator_v2.php
 *
 * FORMAT:  THR-{BLOCK}{YY}-{SHORT}-{SERIAL}
 * EXAMPLE: THR-A26-LD-0000
 *
 * Parts:
 *   THR    → Company code (fixed)
 *   A      → Odometer block (A-Z), increments when serial maxes out
 *   26     → Year (2-digit, resets yearly)
 *   LD     → Table short code
 *   0000   → Base-36 serial (0000–ZZZZ = 0–1,679,615 per block per year)
 *
 * UUID format: {block}{yy}{serial}-xxxx-xxxx-xxxx-xxxxxxxxxxxx
 * Example:     a260000-xxxx-... (first 8 chars from sys_id data)
 *
 * PUBLIC API:
 *   generateV2IDs(PDO $pdo, string $tag): array  → ['uuid'=>..., 'sys_id'=>...]
 *   generateV2SysId(PDO $pdo, string $tag): string
 *   buildCustomUUID(string $block, string $yy, string $serial): string
 *
 * REGISTRY (add new tables here):
 *   'leads'    → LD
 *   'works'    → WK
 *   'services' → SV
 * ═══════════════════════════════════════════════════════════
 */


// ─── Registry ────────────────────────────────────────────────
/**
 * Add new entity tags here.
 * 'short'  → 2-char code used in sys_id
 * 'table'  → MySQL table name
 */
function _v2Registry(): array
{
    return [
        'leads'    => ['table' => 'leads',    'short' => 'LD'],
        'works'    => ['table' => 'works',    'short' => 'WK'],
        'services' => ['table' => 'services', 'short' => 'SV'],
        'departments'   => ['table' => 'departments',   'short' => 'DP'],
        'service_works' => ['table' => 'service_works', 'short' => 'SW'],
        'tasks'         => ['table' => 'tasks',         'short' => 'TK'],
        'notifications' => ['table' => 'notifications', 'short' => 'NT'],
        'task_notes'    => ['table' => 'task_notes',    'short' => 'TN'],
        'sm_posts'       => ['table' => 'sm_posts',       'short' => 'SM'],
        'air_tickets'       => ['table' => 'air_tickets',       'short' => 'AT'],
    ];
}


// ─── Base-36 Helpers ─────────────────────────────────────────

/**
 * Convert integer to Base-36 string with fixed width (uppercase).
 * e.g. toB36(0, 4) → "0000", toB36(35, 4) → "000Z", toB36(1679615, 4) → "ZZZZ"
 */
function toB36(int $n, int $width = 4): string
{
    return str_pad(strtoupper(base_convert((string)$n, 10, 36)), $width, '0', STR_PAD_LEFT);
}

/**
 * Convert Base-36 string back to integer.
 */
function fromB36(string $b36): int
{
    return (int) base_convert(strtolower($b36), 36, 10);
}

/**
 * Max value for a 4-char Base-36 serial: ZZZZ = 1,679,615
 */
function _b36Max(): int
{
    return fromB36('ZZZZ'); // 1,679,615
}


// ─── Core Generator ──────────────────────────────────────────

/**
 * Generate next sys_id for a given tag.
 *
 * @param  PDO    $pdo
 * @param  string $tag  Registry key e.g. 'leads', 'works'
 * @return string       e.g. "THR-A26-LD-0001"
 * @throws Exception    If tag unknown or odometer overflows (Z + ZZZZ)
 */
function generateV2SysId(PDO $pdo, string $tag): string
{
    $registry = _v2Registry();

    if (!isset($registry[$tag])) {
        throw new Exception("sys_id_generator_v2: unknown tag '{$tag}'");
    }

    $table = $registry[$tag]['table'];
    $short = $registry[$tag]['short'];
    $yy    = date('y'); // e.g. "26"

    // Find last sys_id for this tag in the current year
    // Pattern: THR-_26-LD-%  (block is any single char)
    $stmt = $pdo->prepare("
        SELECT sys_id
        FROM `{$table}`
        WHERE sys_id LIKE :pattern
        ORDER BY id DESC
        LIMIT 1
    ");
    $stmt->execute([':pattern' => "THR-_{$yy}-{$short}-%"]);
    $last = $stmt->fetchColumn();

    if ($last) {
        // Parse: THR-A26-LD-000Z
        // Split by '-' → ['THR', 'A26', 'LD', '000Z']
        $parts  = explode('-', $last);
        $blockYY = $parts[1];             // e.g. "A26"
        $block   = $blockYY[0];           // e.g. "A"
        $serial  = $parts[3];             // e.g. "000Z"

        $serialInt = fromB36($serial);

        if ($serialInt >= _b36Max()) {
            // Roll odometer block A→B, B→C ...
            $nextBlock = chr(ord(strtoupper($block)) + 1);
            if ($nextBlock > 'Z') {
                throw new Exception("sys_id_generator_v2: odometer overflow for tag '{$tag}' in year {$yy}");
            }
            $block     = $nextBlock;
            $serialInt = 0;
        } else {
            $serialInt++;
        }
    } else {
        // New year or empty table
        $block     = 'A';
        $serialInt = 0;
    }

    $serial = toB36($serialInt, 4);

    return "THR-{$block}{$yy}-{$short}-{$serial}";
}


// ─── Custom UUID Builder ─────────────────────────────────────

/**
 * Build a UUID where the first 8 chars encode sys_id data.
 *
 * Structure: {block}{yy}{serial}-xxxx-4xxx-yxxx-xxxxxxxxxxxx
 * Example:   a260001-2e81-42e8-bef8-f0bc257026c8
 *
 * @param  string $block   Single char e.g. "A"
 * @param  string $yy      2-digit year e.g. "26"
 * @param  string $serial  4-char base-36 serial e.g. "0001"
 * @return string          Full UUID string
 */
function buildCustomUUID(string $block, string $yy, string $serial): string
{
    // First segment: block(1) + yy(2) + serial(4) = 7 chars... pad to 8
    // We store as: lowercase block + yy + lowercase serial + one pad char '0'
    $seg1 = strtolower($block) . $yy . strtolower($serial); // 7 chars
    // UUID first segment must be exactly 8 hex chars — pad with 0
    $seg1 = str_pad($seg1, 8, '0', STR_PAD_RIGHT);

    // Remaining 3 segments: pure random bytes
    $rand = random_bytes(10);
    $hex  = bin2hex($rand); // 20 hex chars

    // UUID v4 format: 8-4-4-4-12
    // seg1(8) - rand(4) - 4xxx(4) - yxxx(4) - rand(12)
    $s2 = substr($hex, 0, 4);
    $s3 = '4' . substr($hex, 4, 3);                          // version 4
    $s4 = dechex(0x80 | (hexdec(substr($hex, 7, 2)) & 0x3f)) . substr($hex, 9, 2); // variant
    $s5 = substr($hex, 11, 12);

    return "{$seg1}-{$s2}-{$s3}-{$s4}-{$s5}";
}


// ─── Public API ──────────────────────────────────────────────

/**
 * Generate both uuid and sys_id for a given entity tag.
 *
 * @param  PDO    $pdo
 * @param  string $tag  e.g. 'leads', 'works', 'services'
 * @return array        ['uuid' => string, 'sys_id' => string]
 *
 * Usage:
 *   require_once __DIR__ . '/sys_id_generator_v2.php';
 *   $ids = generateV2IDs($pdo, 'leads');
 *   // $ids['sys_id'] → "THR-A26-LD-0001"
 *   // $ids['uuid']   → "a260001-xxxx-4xxx-yxxx-xxxxxxxxxxxx"
 */
function generateV2IDs(PDO $pdo, string $tag): array
{
    $sysId = generateV2SysId($pdo, $tag);

    // Parse block, yy, serial from generated sys_id for UUID
    $parts  = explode('-', $sysId);
    $blockYY = $parts[1];              // e.g. "A26"
    $block   = $blockYY[0];           // "A"
    $yy      = substr($blockYY, 1);   // "26"
    $serial  = $parts[3];             // "0001"

    $uuid = buildCustomUUID($block, $yy, $serial);

    return [
        'uuid'   => $uuid,
        'sys_id' => $sysId,
    ];
}