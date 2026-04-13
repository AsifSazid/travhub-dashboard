<?php
/**
 * smb_proxy.php - Proxy for serving images from SMB storage
 */
require_once '../../server/live_storage.php';

// Disable error reporting to prevent HTML output
error_reporting(0);
ini_set('display_errors', 0);

// Clear any output buffers
while (ob_get_level()) ob_end_clean();

try {
    $path = isset($_GET['path']) ? $_GET['path'] : '';
    
    if (empty($path)) {
        throw new Exception('No path specified');
    }

    // Decode the path
    $path = urldecode($path);
    
    $omv = new OMV_SMB_Manager();
    
    // Get file contents from SMB
    $imageData = $omv->get_file_contents($path);
    
    if ($imageData === false || empty($imageData)) {
        throw new Exception('File not found or empty');
    }

    // Get file extension
    $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
    
    // Set correct content type based on file extension
    $contentType = 'image/jpeg';
    if ($ext == 'png') $contentType = 'image/png';
    if ($ext == 'gif') $contentType = 'image/gif';
    if ($ext == 'bmp') $contentType = 'image/bmp';
    if ($ext == 'webp') $contentType = 'image/webp';
    
    // Set proper headers for image
    header('Content-Type: ' . $contentType);
    header('Content-Length: ' . strlen($imageData));
    header('Cache-Control: public, max-age=86400');
    header('Expires: ' . gmdate('D, d M Y H:i:s', time() + 86400) . ' GMT');
    header('Pragma: cache');
    
    echo $imageData;
    exit;
    
} catch (Exception $e) {
    // Log error for debugging
    error_log('SMB Proxy Error: ' . $e->getMessage());
    
    // Return a 404 image or error message
    header('HTTP/1.0 404 Not Found');
    header('Content-Type: text/plain');
    echo 'Image not found: ' . $e->getMessage();
    exit;
}
?>