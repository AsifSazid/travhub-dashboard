<?php

function buildMetaData(
    ?string $existingMetaJson = null,
    string $userName = 'system',
    int $maxUpdates = 20
): string {
    date_default_timezone_set('Asia/Dhaka');
    $now = date('d-m-Y H:i');

    // যদি new record তৈরি হয়
    if ($existingMetaJson === null || $existingMetaJson === '') {
        $meta = [
            'created_by_date' => ['user' => $userName, 'date' => $now],
            'updated_by_date' => []
        ];
        return json_encode($meta, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    }

    // Existing JSON process
    try {
        $meta = json_decode($existingMetaJson, true, 512, JSON_THROW_ON_ERROR);
        
        // যদি decode fail হয় বা valid structure না থাকে
        if (!is_array($meta) || 
            !isset($meta['created_by_date']) || 
            !isset($meta['updated_by_date'])) {
            
            throw new Exception('Invalid meta data structure');
        }
    } catch (Exception $e) {
        // Fallback: create new structure
        $meta = [
            'created_by_date' => ['user' => $userName, 'date' => $now],
            'updated_by_date' => []
        ];
        return json_encode($meta, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    }

    // Ensure updated_by_date is array
    if (!is_array($meta['updated_by_date'])) {
        $meta['updated_by_date'] = [];
    }

    // Add new update entry
    $newUpdate = ['user' => $userName, 'date' => $now];
    
    // Remove oldest if max reached
    if (count($meta['updated_by_date']) >= $maxUpdates) {
        array_shift($meta['updated_by_date']);
    }
    
    $meta['updated_by_date'][] = $newUpdate;

    return json_encode($meta, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
}

// Usage example (create):
// $metaDataJson = buildMetaData(
//     null,
//     $_SESSION['user_name'] ?? 'demo_name'
// );

// Result format:
// {
//     "created_by_date": {"user": "demo_name", "date": "03-01-2026 16:40"},
//     "updated_by_date": [
//         {"user": "demo_name", "date": "03-01-2026 16:40"},
//         {"user": "admin", "date": "04-01-2026 11:05"}
//     ]
// }