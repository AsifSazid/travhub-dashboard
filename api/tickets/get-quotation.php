<?php
session_start();

require '../../server/db_connection.php';

header('Content-Type: application/json');

/**
 * Fetch a single air ticket quotation document by sys_id or uuid
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
    
    $sql = "SELECT * FROM air_ticket_quotations WHERE $type = :identifier LIMIT 1";
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
        $includeDeleted = filter_var($_GET['include_deleted'] ?? false, FILTER_VALIDATE_BOOLEAN);
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

/**
 * Get a specific quotation by its quotation_id
 * 
 * @param array $document The full document
 * @param string $quotationId The quotation ID to find
 * @return array|null The specific quotation or null
 */
function getSpecificQuotation($document, $quotationId) {
    $quotations = $document['quotations'] ?? [];
    foreach ($quotations as $index => $quotation) {
        if (($quotation['quot_id'] ?? '') === $quotationId) {
            return [
                'quotation' => $quotation,
                'information' => $document['informations'][$index] ?? null,
                'form_data' => $document['form_data'][$index] ?? null,
                'index' => $index
            ];
        }
    }
    return null;
}

// Get parameters
$sys_id = $_GET['sys_id'] ?? null;
$uuid = $_GET['uuid'] ?? null;
$quotation_id = $_GET['quotation_id'] ?? null;
$includeDeleted = filter_var($_GET['include_deleted'] ?? false, FILTER_VALIDATE_BOOLEAN);

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
            'message' => 'Air ticket quotation not found'
        ]);
        exit;
    }
    
    // If quotation_id is provided, return only that specific quotation
    if ($quotation_id) {
        $specificQuotation = getSpecificQuotation($document, $quotation_id);
        
        if (!$specificQuotation) {
            echo json_encode([
                'success' => false,
                'message' => 'Quotation not found with the provided quotation_id'
            ]);
            exit;
        }
        
        echo json_encode([
            'success' => true,
            'data' => [
                'document_id' => $document['sys_id'],
                'document_uuid' => $document['uuid'],
                'client_sys_id' => $document['client_sys_id'],
                'title' => $document['title'],
                'quotation' => $specificQuotation['quotation'],
                'information' => $specificQuotation['information'],
                'form_data' => $specificQuotation['form_data'],
                'percentage' => $document['percentage'],
                've_fixed_price' => $document['ve_fixed_price']
            ],
            'meta' => [
                'quotation_id' => $quotation_id,
                'identifier_type' => $identifierType,
                'identifier' => $identifier
            ]
        ]);
        exit;
    }
    
    // Return the full document with all quotations
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
    error_log("Air Ticket Quotation Fetch Error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred: ' . $e->getMessage()
    ]);
}