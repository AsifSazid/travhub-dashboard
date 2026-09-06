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
        'air_tickets'         => ['table' => 'air_tickets',         'short' => 'AT'],
        'traveler_documents'  => ['table' => 'traveler_documents',  'short' => 'DC'],
        'travelers'           => ['table' => 'travelers',           'short' => 'TR'],
        'batches'             => ['table' => 'batches',             'short' => 'BT'],
        'traveler_links'      => ['table' => 'traveler_links',      'short' => 'LK'],
        'traveler_groups'         => ['table' => 'traveler_groups',         'short' => 'GR'],
        'traveler_group_members'  => ['table' => 'traveler_group_members',  'short' => 'GM'],
        'portal_links'        => ['table' => 'portal_links',        'short' => 'PL'],
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
        $parts  = explode('-', $last);
        $blockYY = $parts[1];
        $block   = $blockYY[0];
        $serial  = $parts[3];

        $serialInt = fromB36($serial);

        if ($serialInt >= _b36Max()) {
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
        $block     = 'A';
        $serialInt = 0;
    }

    $serial = toB36($serialInt, 4);

    return "THR-{$block}{$yy}-{$short}-{$serial}";
}

// ─── Custom UUID Builder ─────────────────────────────────────

function buildCustomUUID(string $block, string $yy, string $serial): string
{
    $seg1 = strtolower($block) . $yy . strtolower($serial);
    $seg1 = str_pad($seg1, 8, '0', STR_PAD_RIGHT);

    $rand = random_bytes(10);
    $hex  = bin2hex($rand);

    $s2 = substr($hex, 0, 4);
    $s3 = '4' . substr($hex, 4, 3);
    $s4 = dechex(0x80 | (hexdec(substr($hex, 7, 2)) & 0x3f)) . substr($hex, 9, 2);
    $s5 = substr($hex, 11, 12);

    return "{$seg1}-{$s2}-{$s3}-{$s4}-{$s5}";
}

// ─── Public API ──────────────────────────────────────────────

function generateV2IDs(PDO $pdo, string $tag): array
{
    $sysId = generateV2SysId($pdo, $tag);

    $parts  = explode('-', $sysId);
    $blockYY = $parts[1];
    $block   = $blockYY[0];
    $yy      = substr($blockYY, 1);
    $serial  = $parts[3];

    $uuid = buildCustomUUID($block, $yy, $serial);

    return [
        'uuid'   => $uuid,
        'sys_id' => $sysId,
    ];
}