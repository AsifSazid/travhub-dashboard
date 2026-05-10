<?php
/**
 * Check for duplicate travelers before creation
 */
require '../../server/db_connection.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$data = json_decode(file_get_contents('php://input'), true);

if (!$data) {
    echo json_encode(['has_duplicates' => false, 'message' => 'No data received']);
    exit;
}

$fullName = trim($data['full_name'] ?? '');
$documentNumber = trim($data['document_number'] ?? '');
$documentType = $data['document_type'] ?? 'passport';
$dateOfBirth = trim($data['date_of_birth'] ?? '');

$duplicates = [];

// Match Type 1: Name + Document Number match (exact match - BLOCK)
if (!empty($fullName) && !empty($documentNumber)) {
    if ($documentType === 'passport') {
        $stmt = $pdo->prepare("
            SELECT sys_id, name, passport_no, nid_no, date_of_birth, status, meta_data,
                   'passport_no' as column_name
            FROM travelers 
            WHERE name = ? AND passport_no = ?
        ");
    } else {
        $stmt = $pdo->prepare("
            SELECT sys_id, name, passport_no, nid_no, date_of_birth, status, meta_data,
                   'nid_no' as column_name
            FROM travelers 
            WHERE name = ? AND nid_no = ?
        ");
    }
    $stmt->execute([$fullName, $documentNumber]);
    $exactMatches = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($exactMatches as $match) {
        $metaData = parseMetaData($match['meta_data']);
        
        $duplicates[] = [
            'sys_id' => $match['sys_id'],
            'name' => $match['name'],
            'document_number' => $match['passport_no'] ?? $match['nid_no'],
            'date_of_birth' => $match['date_of_birth'],
            'status' => $match['status'],
            'created_at' => $metaData['created_at'] ?? 'N/A',
            'created_by' => $metaData['created_by'] ?? 'system',
            'column' => $match['column_name'],
            'match_type' => 'exact',
            'match_reason' => 'Name and document number match'
        ];
    }
}

// Match Type 2: Name + Document Number + DOB match (exact match - BLOCK)
if (!empty($fullName) && !empty($documentNumber) && !empty($dateOfBirth)) {
    if ($documentType === 'passport') {
        $stmt = $pdo->prepare("
            SELECT sys_id, name, passport_no, nid_no, date_of_birth, status, meta_data,
                   'passport_no' as column_name
            FROM travelers 
            WHERE name = ? AND passport_no = ? AND date_of_birth = ?
        ");
    } else {
        $stmt = $pdo->prepare("
            SELECT sys_id, name, passport_no, nid_no, date_of_birth, status, meta_data,
                   'nid_no' as column_name
            FROM travelers 
            WHERE name = ? AND nid_no = ? AND date_of_birth = ?
        ");
    }
    $stmt->execute([$fullName, $documentNumber, $dateOfBirth]);
    $exactDobMatches = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($exactDobMatches as $match) {
        // Check if already added
        $alreadyExists = false;
        foreach ($duplicates as $dup) {
            if ($dup['sys_id'] === $match['sys_id']) {
                $alreadyExists = true;
                break;
            }
        }
        if (!$alreadyExists) {
            $metaData = parseMetaData($match['meta_data']);
            
            $duplicates[] = [
                'sys_id' => $match['sys_id'],
                'name' => $match['name'],
                'document_number' => $match['passport_no'] ?? $match['nid_no'],
                'date_of_birth' => $match['date_of_birth'],
                'status' => $match['status'],
                'created_at' => $metaData['created_at'] ?? 'N/A',
                'created_by' => $metaData['created_by'] ?? 'system',
                'column' => $match['column_name'],
                'match_type' => 'exact',
                'match_reason' => 'Name, document number and date of birth match'
            ];
        }
    }
}

// Match Type 3: Name + DOB match (partial match - SHOW WARNING)
if (!empty($fullName) && !empty($dateOfBirth)) {
    $stmt = $pdo->prepare("
        SELECT sys_id, name, passport_no, nid_no, date_of_birth, status, meta_data
        FROM travelers 
        WHERE name = ? AND date_of_birth = ?
    ");
    $stmt->execute([$fullName, $dateOfBirth]);
    $nameDobMatches = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($nameDobMatches as $match) {
        $alreadyExists = false;
        foreach ($duplicates as $dup) {
            if ($dup['sys_id'] === $match['sys_id']) {
                $alreadyExists = true;
                break;
            }
        }
        if (!$alreadyExists) {
            $metaData = parseMetaData($match['meta_data']);
            
            $duplicates[] = [
                'sys_id' => $match['sys_id'],
                'name' => $match['name'],
                'document_number' => $match['passport_no'] ?? $match['nid_no'],
                'date_of_birth' => $match['date_of_birth'],
                'status' => $match['status'],
                'created_at' => $metaData['created_at'] ?? 'N/A',
                'created_by' => $metaData['created_by'] ?? 'system',
                'column' => $match['passport_no'] ? 'passport_no' : 'nid_no',
                'match_type' => 'partial',
                'match_reason' => 'Name and date of birth match (different document)'
            ];
        }
    }
}

echo json_encode([
    'has_duplicates' => !empty($duplicates),
    'duplicates' => $duplicates,
    'count' => count($duplicates),
    'matched_criteria' => [
        'full_name' => $fullName,
        'document_number' => $documentNumber,
        'document_type' => $documentType,
        'date_of_birth' => $dateOfBirth
    ]
]);

/**
 * Parse meta_data JSON to extract created_by and created_at
 */
function parseMetaData($metaDataJson) {
    if (empty($metaDataJson)) {
        return [
            'created_by' => 'system',
            'created_at' => 'N/A'
        ];
    }
    
    $metaData = json_decode($metaDataJson, true);
    
    if (!is_array($metaData)) {
        return [
            'created_by' => 'system',
            'created_at' => 'N/A'
        ];
    }
    
    // Extract from created_by_date structure
    $createdBy = 'system';
    $createdAt = 'N/A';
    
    if (isset($metaData['created_by_date'])) {
        $createdBy = $metaData['created_by_date']['user'] ?? 'system';
        $createdAt = $metaData['created_by_date']['date'] ?? 'N/A';
    }
    
    // Fallback to other possible structures
    if (isset($metaData['created_by']) && $createdBy === 'system') {
        $createdBy = $metaData['created_by'];
    }
    if (isset($metaData['created_at']) && $createdAt === 'N/A') {
        $createdAt = $metaData['created_at'];
    }
    
    return [
        'created_by' => $createdBy,
        'created_at' => $createdAt
    ];
}
?>