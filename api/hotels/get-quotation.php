<?php
session_start();

require '../../server/db_connection.php';

header('Content-Type: application/json');

/**
 * Fetch a single hotel quotation document by sys_id or uuid
 * 
 * @param PDO $pdo Database connection
 * @param string $identifier System ID or UUID
 * @param string $type Type of identifier ('sys_id' or 'uuid')
 * @return array|null Document data or null if not found
 */
function fetchQuotation($pdo, $identifier, $type = 'sys_id') {
    $allowedTypes = ['sys_id', 'uuid'];
    if (!in_array($type, $allowedTypes)) {
        return null;
    }
    
    $sql = "SELECT * FROM hotel_quotations WHERE $type = :identifier LIMIT 1";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':identifier' => $identifier]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result) {
        // Decode JSON fields
        $result['informations'] = json_decode($result['informations'], true) ?? [];
        $result['quotations'] = json_decode($result['quotations'], true) ?? [];
        $result['form_data'] = json_decode($result['form_data'], true) ?? [];
        $result['meta_data'] = json_decode($result['meta_data'], true) ?? [];
        
        // Filter out deleted quotations if requested
        $includeDeleted = $_GET['include_deleted'] ?? false;
        if (!$includeDeleted) {
            $result['informations'] = array_filter($result['informations'], function($item) {
                return !($item['deleted'] ?? false);
            });
            $result['quotations'] = array_filter($result['quotations'], function($item) {
                return !($item['deleted'] ?? false);
            });
            $result['form_data'] = array_filter($result['form_data'], function($item) {
                return !($item['deleted'] ?? false);
            });
            
            // Re-index arrays
            $result['informations'] = array_values($result['informations']);
            $result['quotations'] = array_values($result['quotations']);
            $result['form_data'] = array_values($result['form_data']);
        }
    }
    
    return $result;
}

// Get parameters
$sys_id = $_GET['sys_id'] ?? null;
$uuid = $_GET['uuid'] ?? null;

if (!$sys_id && !$uuid) {
    echo json_encode([
        'success' => false,
        'message' => 'Either sys_id or uuid parameter is required'
    ]);
    exit;
}

try {
    $document = null;
    $identifierType = null;
    $identifier = null;
    
    if ($sys_id) {
        $document = fetchQuotation($pdo, $sys_id, 'sys_id');
        $identifierType = 'sys_id';
        $identifier = $sys_id;
    } else if ($uuid) {
        $document = fetchQuotation($pdo, $uuid, 'uuid');
        $identifierType = 'uuid';
        $identifier = $uuid;
    }
    
    if (!$document) {
        echo json_encode([
            'success' => false,
            'message' => 'Hotel quotation not found'
        ]);
        exit;
    }
    
    // Return the document with all its quotations
    echo json_encode([
        'success' => true,
        'data' => $document,
        'meta' => [
            'total_quotations' => count($document['quotations']),
            'active_quotations' => count(array_filter($document['quotations'], function($q) {
                return !($q['deleted'] ?? false);
            })),
            'identifier_type' => $identifierType,
            'identifier' => $identifier
        ]
    ]);
    
} catch (Exception $e) {
    error_log("Hotel Quotation Fetch Error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred: ' . $e->getMessage()
    ]);
}