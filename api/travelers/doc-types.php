<?php
/**
 * FILE PATH: /api/travelers/doc-types.php
 * Returns all active doc types from doc_type_registry table.
 * Used by masterdata-visa.php to populate doc_type dropdown.
 */
require_once '../../server/db_connection.php';
header('Content-Type: application/json');

try {
    $stmt = $pdo->query("
        SELECT doc_type, display_name, smb_folder
        FROM doc_type_registry
        WHERE is_active = 1
        ORDER BY display_order ASC, display_name ASC
    ");
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode(['status' => 'success', 'data' => $rows]);
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}