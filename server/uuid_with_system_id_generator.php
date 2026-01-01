<?php
require 'db_connection.php'; // PDO connection

function generateIDs(string $tag): array
{
    $sysData = generateUUID($tag); // returns ['uuid' => ..., 'sys_id' => ...]

    return [
        'uuid'   => uuidV4(), // ✅ pure UUID v4
        'sys_id' => $sysData // ✅ business readable ID
    ];
}

function generateUUID(string $tag): string
{
    require 'db_connection.php'; // $pdo

    $map = [
        'clients'   => ['table' => 'clients',   'column' => 'client_sys_id',   'short' => 'C'],
        'travelers' => ['table' => 'travelers', 'column' => 'traveler_sys_id', 'short' => 'T'],
        'vendors'   => ['table' => 'vendors',   'column' => 'vendor_sys_id',   'short' => 'V'],
    ];

    if (!isset($map[$tag])) {
        throw new Exception('Invalid tag');
    }

    $table  = $map[$tag]['table'];
    $column = $map[$tag]['column'];
    $short  = $map[$tag]['short'];

    $company = 'TH';
    $static  = 'NR';
    $year    = date('y'); // 26

    // 🔹 Last sys_id of CURRENT YEAR only
    $stmt = $pdo->prepare("
        SELECT {$column}
        FROM {$table}
        WHERE {$column} LIKE :yearPattern
        ORDER BY id DESC
        LIMIT 1
    ");
    $stmt->execute([
        ':yearPattern' => "%-{$year}-%"
    ]);

    $lastSysId = $stmt->fetchColumn();

    if ($lastSysId) {
        // THC-NR-26-00K-9999
        $parts  = explode('-', $lastSysId);
        $block  = $parts[3];          // 00K
        $serial = (int) $parts[4];    // 9999

        if ($serial >= 999) {
            // 🔥 block increase, serial reset
            $blockNumber = (int) str_replace('K', '', $block);
            $block = str_pad($blockNumber + 1, 2, '0', STR_PAD_LEFT) . 'K';
            $serial = '001';
        } else {
            $serial = str_pad($serial + 1, 3, '0', STR_PAD_LEFT);
        }
    } else {
        // 🔹 New year or empty table
        $block  = '00K';
        $serial = '001';
    }

    $sys_id = "{$company}-{$static}-{$year}-{$block}-{$serial}";

    return $sys_id;
}

function uuidV4(): string
{
    $data = random_bytes(16);

    // set version to 0100
    $data[6] = chr((ord($data[6]) & 0x0f) | 0x40);
    // set variant to 10xx
    $data[8] = chr((ord($data[8]) & 0x3f) | 0x80);

    return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
}





// THC-NR-26-00K-001