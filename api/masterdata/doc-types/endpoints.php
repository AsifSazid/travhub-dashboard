<?php
/**
 * FILE PATH: api/masterdata/doc-types/endpoints.php
 * CRUD for doc_type_registry table
 *
 * GET  ?action=list              → all doc types ordered by display_order
 * POST action=create             → insert new doc type
 * POST action=update             → update existing
 * POST action=toggle             → flip is_active
 * POST action=delete             → hard delete (only if not in use)
 */

require_once '../../../server/db_connection.php';
header('Content-Type: application/json');

function dtJson(array $payload): never {
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
}
function dtErr(string $msg): never {
    dtJson(['status' => 'error', 'message' => $msg]);
}

try {
    $method = $_SERVER['REQUEST_METHOD'];
    $action = $_GET['action'] ?? '';
    $body   = [];

    if ($method === 'POST') {
        $raw  = file_get_contents('php://input');
        $body = json_decode($raw, true) ?? [];
        $action = $body['action'] ?? $action;
    }

    switch ($action) {

        /* ── LIST ──────────────────────────────────────────── */
        case 'list':
        default:
            $stmt = $pdo->query("
                SELECT id, doc_type, display_name, smb_folder,
                       tracks_expiry, has_structured_schema,
                       updates_traveler_column, display_order,
                       description, is_active
                FROM doc_type_registry
                ORDER BY display_order ASC, display_name ASC
            ");
            dtJson(['status' => 'success', 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);

        /* ── CREATE ────────────────────────────────────────── */
        case 'create':
            $docType     = trim(strtolower($body['doc_type'] ?? ''));
            $displayName = trim($body['display_name'] ?? '');
            $smbFolder   = trim($body['smb_folder'] ?? '');
            $order       = (int)($body['display_order'] ?? 99);
            $tracksExpiry= (int)($body['tracks_expiry'] ?? 0);
            $isActive    = (int)($body['is_active'] ?? 1);

            if (!$docType)     dtErr('doc_type is required');
            if (!$displayName) dtErr('display_name is required');
            if (!$smbFolder)   dtErr('smb_folder is required');

            // Validate key format
            if (!preg_match('/^[a-z][a-z0-9_]*$/', $docType)) {
                dtErr('doc_type must be lowercase letters, digits, underscores only');
            }

            // Check duplicate
            $chk = $pdo->prepare("SELECT id FROM doc_type_registry WHERE doc_type = ?");
            $chk->execute([$docType]);
            if ($chk->fetch()) dtErr("doc_type '{$docType}' already exists");

            $pdo->prepare("
                INSERT INTO doc_type_registry
                    (doc_type, display_name, smb_folder, tracks_expiry,
                     has_structured_schema, display_order, is_active)
                VALUES (?, ?, ?, ?, 0, ?, ?)
            ")->execute([$docType, $displayName, $smbFolder, $tracksExpiry, $order, $isActive]);

            dtJson(['status' => 'success', 'message' => 'Doc type created']);

        /* ── UPDATE ────────────────────────────────────────── */
        case 'update':
            $docType     = trim($body['doc_type'] ?? '');
            $displayName = trim($body['display_name'] ?? '');
            $smbFolder   = trim($body['smb_folder'] ?? '');
            $order       = (int)($body['display_order'] ?? 99);
            $tracksExpiry= (int)($body['tracks_expiry'] ?? 0);
            $isActive    = (int)($body['is_active'] ?? 1);

            if (!$docType)     dtErr('doc_type is required');
            if (!$displayName) dtErr('display_name is required');
            if (!$smbFolder)   dtErr('smb_folder is required');

            $pdo->prepare("
                UPDATE doc_type_registry
                SET display_name = ?, smb_folder = ?,
                    tracks_expiry = ?, display_order = ?, is_active = ?
                WHERE doc_type = ?
            ")->execute([$displayName, $smbFolder, $tracksExpiry, $order, $isActive, $docType]);

            dtJson(['status' => 'success', 'message' => 'Doc type updated']);

        /* ── TOGGLE ────────────────────────────────────────── */
        case 'toggle':
            $docType  = trim($body['doc_type'] ?? '');
            $isActive = (int)($body['is_active'] ?? 0);
            if (!$docType) dtErr('doc_type required');

            $pdo->prepare("UPDATE doc_type_registry SET is_active = ? WHERE doc_type = ?")
                ->execute([$isActive, $docType]);
            dtJson(['status' => 'success', 'message' => 'Status updated']);

        /* ── DELETE ────────────────────────────────────────── */
        case 'delete':
            $docType = trim($body['doc_type'] ?? '');
            if (!$docType) dtErr('doc_type required');

            // Check if in use in traveler_documents
            $chk = $pdo->prepare("SELECT COUNT(*) FROM traveler_documents WHERE doc_type = ?");
            $chk->execute([$docType]);
            $count = (int)$chk->fetchColumn();
            if ($count > 0) {
                dtErr("Cannot delete — {$count} traveler document(s) use this type. Disable it instead.");
            }

            $pdo->prepare("DELETE FROM doc_type_registry WHERE doc_type = ?")
                ->execute([$docType]);
            dtJson(['status' => 'success', 'message' => 'Doc type deleted']);
    }

} catch (Throwable $e) {
    dtErr($e->getMessage());
}