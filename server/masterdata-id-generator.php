<?php


/**
 * Converts an integer to a fixed-length Base-36 string (0-9, A-Z).
 * Example: 10 -> "0A", 35 -> "0Z", 36 -> "10"
 */
function toBase36(int $number, int $width = 2): string
{
    $base36 = strtoupper(base_convert($number, 10, 36));
    return str_pad($base36, $width, '0', STR_PAD_LEFT);
}

/**
 * Decodes a Base-36 string back to an integer.
 */
function fromBase36(string $base36): int
{
    return (int)base_convert($base36, 36, 10);
}

/**
 * Generates a standard UUID v4.
 */
function uuidV4(): string
{
    $data = random_bytes(16);
    $data[6] = chr((ord($data[6]) & 0x0f) | 0x40); // version 4
    $data[8] = chr((ord($data[8]) & 0x3f) | 0x80); // variant RFC 4122
    return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
}

/**
 * Main logic to generate Hierarchical IDs
 */
function generateHierarchyIDs(PDO $pdo, string $tag, ?string $parentId = null): array
{
    $company = 'THR';
    $year    = date('y'); // 26
    $newSysId = '';

    switch ($tag) {
        case 'countries':
            // Format: THR-26-CNT-01
            $prefix = "{$company}-{$year}-CNT";
            
            $stmt = $pdo->prepare("SELECT sys_id FROM countries WHERE sys_id LIKE :pattern ORDER BY id DESC LIMIT 1");
            $stmt->execute([':pattern' => "{$prefix}-%"]);
            $lastId = $stmt->fetchColumn();

            $nextVal = $lastId ? fromBase36(substr($lastId, -2)) + 1 : 1;
            $newSysId = $prefix . "-" . toBase36($nextVal);
            break;

        case 'cities':
            // parentId is the Country sys_id (e.g., THR-26-CNT-01)
            if (!$parentId) throw new Exception("Country sys_id is required to generate a City ID.");

            // Cities are stored in the 'cities' JSON column of the countries table
            $stmt = $pdo->prepare("SELECT cities FROM countries WHERE sys_id = ?");
            $stmt->execute([$parentId]);
            $json = $stmt->fetchColumn();

            $citiesArray = $json ? json_decode($json, true) : [];
            
            // Logic: Count existing elements in the JSON array and increment
            $nextVal = count($citiesArray) + 1;
            $newSysId = "{$parentId}-CTS-" . toBase36($nextVal);
            break;

        case 'activities':
            // parentId is now the Country sys_id (e.g., THR-26-CNT-01)
            // Format: THR-26-CNT-01-ACT-01
            if (!$parentId) throw new Exception("Country sys_id is required to generate an Activity ID.");

            $prefix = "{$parentId}-ACT";

            $stmt = $pdo->prepare("SELECT sys_id FROM activities WHERE sys_id LIKE :pattern ORDER BY id DESC LIMIT 1");
            $stmt->execute([':pattern' => "{$prefix}-%"]);
            $lastId = $stmt->fetchColumn();

            $nextVal = $lastId ? fromBase36(substr($lastId, -2)) + 1 : 1;
            $newSysId = $prefix . "-" . toBase36($nextVal);
            break;

        case 'cars':
            // parentId is the Country sys_id (e.g., THR-26-CNT-01)
            // Format: THR-26-CNT-01-CAR-01
            if (!$parentId) throw new Exception("Country sys_id is required to generate a Car ID.");

            $prefix = "{$parentId}-CAR";

            $stmt = $pdo->prepare("SELECT sys_id FROM cars WHERE sys_id LIKE :pattern ORDER BY id DESC LIMIT 1");
            $stmt->execute([':pattern' => "{$prefix}-%"]);
            $lastId = $stmt->fetchColumn();

            $nextVal = $lastId ? fromBase36(substr($lastId, -2)) + 1 : 1;
            $newSysId = $prefix . "-" . toBase36($nextVal);
            break;

        default:
            throw new Exception("Invalid tag provided.");
    }

    return [
        'uuid'   => uuidV4(),
        'sys_id' => $newSysId
    ];
}

// --- USAGE EXAMPLES ---

// 1. For a new Country
// $countryData = generateHierarchyIDs($pdo, 'countries');
// Result: ['uuid' => '...', 'sys_id' => 'THR-26-CNT-01']

// 2. For a new City inside Country 'THR-26-CNT-01'
// $cityData = generateHierarchyIDs($pdo, 'cities', 'THR-26-CNT-01');
// Result: ['uuid' => '...', 'sys_id' => 'THR-26-CNT-01-CTS-01']

// 3. For a new Activity inside Country 'THR-26-CNT-01'
// $activityData = generateHierarchyIDs($pdo, 'activities', 'THR-26-CNT-01');
// Result: ['uuid' => '...', 'sys_id' => 'THR-26-CNT-01-ACT-01']

// 4. For a new Car inside Country 'THR-26-CNT-01'
// $carData = generateHierarchyIDs($pdo, 'cars', 'THR-26-CNT-01');
// Result: ['uuid' => '...', 'sys_id' => 'THR-26-CNT-01-CAR-01']