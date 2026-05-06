<?php
/**
 * smb_save_editor.php - Save edited images back to SMB storage
 */
require_once 'live_storage.php';

// Enable error logging but don't display
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Clear output buffers
while (ob_get_level()) ob_end_clean();

header('Content-Type: application/json');

// Log the request
error_log("=== SMB Save Editor Called ===");
error_log("POST data keys: " . implode(', ', array_keys($_POST)));

try {
    // Check if required data is present
    if (!isset($_POST['image']) || !isset($_POST['path'])) {
        throw new Exception('Missing image data or path. Received: ' . json_encode(array_keys($_POST)));
    }

    $imageData = $_POST['image'];
    $remotePath = $_POST['path'];

    error_log("Remote path: " . $remotePath);
    error_log("Image data length: " . strlen($imageData));

    // Remove data URL prefix (e.g., "data:image/jpeg;base64,")
    if (strpos($imageData, 'data:image') === 0) {
        $imageData = substr($imageData, strpos($imageData, ',') + 1);
        error_log("Removed data URL prefix");
    }

    // Decode base64 image
    $imageBinary = base64_decode($imageData);
    
    if ($imageBinary === false) {
        throw new Exception('Base64 decode failed');
    }
    
    if (empty($imageBinary)) {
        throw new Exception('Decoded image is empty');
    }

    error_log("Decoded image size: " . strlen($imageBinary) . " bytes");

    // Create a temporary file with proper extension
    $tempFile = tempnam(sys_get_temp_dir(), 'img_') . '.jpg';
    $bytesWritten = file_put_contents($tempFile, $imageBinary);
    
    if ($bytesWritten === false) {
        throw new Exception('Failed to write temp file');
    }

    error_log("Temp file created: " . $tempFile . " (" . $bytesWritten . " bytes)");
    error_log("Temp file exists: " . (file_exists($tempFile) ? 'Yes' : 'No'));

    // Initialize SMB manager
    $omv = new OMV_SMB_Manager();

    // Upload the file back to SMB
    $result = $omv->paste_file($tempFile, $remotePath);

    // Clean up temp file
    if (file_exists($tempFile)) {
        unlink($tempFile);
        error_log("Temp file deleted");
    }

    if ($result === true) {
        error_log("✅ Save successful for: " . $remotePath);
        echo json_encode(['success' => true, 'message' => 'Image saved successfully']);
    } else {
        error_log("❌ Save failed: " . print_r($result, true));
        throw new Exception(is_string($result) ? $result : 'Failed to save file');
    }

} catch (Exception $e) {
    error_log("❌ Exception in smb_save_editor: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    
    echo json_encode([
        'success' => false, 
        'message' => $e->getMessage()
    ]);
}
?>