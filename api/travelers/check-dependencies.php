<?php
/**
 * TravHub Smart Upload v3 — Dependency Check
 * ===========================================
 * Run this from /api/travelers/ to diagnose why classify-document.php returns 500.
 * 
 * Access: http://yoursite/api/travelers/check-dependencies.php
 */

header('Content-Type: text/plain; charset=utf-8');

echo "=== TravHub Smart Upload v3 Dependency Check ===\n\n";

$checks = [];

// 1. Check file structure
echo "[1] File structure\n";
$checks['cwd'] = getcwd();
echo "   Current directory: {$checks['cwd']}\n";

// 2. Check required files
echo "\n[2] Required files\n";
$requiredFiles = [
    '../../server/db_connection.php',
    '../../server/phash-helper.php',
    '../../server/doc-extraction-schemas.php',
    '../../server/gen_meta_for_summary.php',
    '../../server/summary-generator.php',
    '../../gemini-apikey.txt',
    '../../server-name.txt',
    '../../tmp/classify/',
];

foreach ($requiredFiles as $path) {
    $realPath = realpath($path);
    $exists = file_exists($path);
    $status = $exists ? '✓' : '✗';
    echo "   {$status} {$path}";
    if ($realPath) echo " → {$realPath}";
    echo "\n";
    $checks[$path] = $exists;
}

// 3. Check PHP extensions
echo "\n[3] PHP extensions\n";
$requiredExts = ['pdo', 'pdo_mysql', 'imagick', 'json', 'curl'];
foreach ($requiredExts as $ext) {
    $loaded = extension_loaded($ext);
    $status = $loaded ? '✓' : '✗';
    echo "   {$status} {$ext}\n";
    $checks["ext_{$ext}"] = $loaded;
}

// 4. Check PHP config (for 100MB support)
echo "\n[4] PHP configuration (for 100MB uploads)\n";
$configs = [
    'upload_max_filesize' => ini_get('upload_max_filesize'),
    'post_max_size'       => ini_get('post_max_size'),
    'max_execution_time'  => ini_get('max_execution_time'),
    'memory_limit'        => ini_get('memory_limit'),
];
foreach ($configs as $key => $val) {
    $ok = true;
    if ($key === 'upload_max_filesize' && parseBytes($val) < 100 * 1024 * 1024) $ok = false;
    if ($key === 'post_max_size'       && parseBytes($val) < 110 * 1024 * 1024) $ok = false;
    if ($key === 'max_execution_time'  && (int)$val < 300 && (int)$val !== 0) $ok = false;
    if ($key === 'memory_limit'        && parseBytes($val) < 512 * 1024 * 1024 && (int)$val !== -1) $ok = false;
    
    $status = $ok ? '✓' : '⚠';
    echo "   {$status} {$key} = {$val}\n";
}

// 5. Check database connection
echo "\n[5] Database connection\n";
if (file_exists('../../server/db_connection.php')) {
    try {
        require_once '../../server/db_connection.php';
        if (isset($pdo) && $pdo instanceof PDO) {
            echo "   ✓ PDO connection established\n";
            
            // Check doc_type_registry table exists
            try {
                $stmt = $pdo->query("SELECT COUNT(*) FROM doc_type_registry");
                $count = $stmt->fetchColumn();
                echo "   ✓ doc_type_registry table exists ({$count} rows)\n";
            } catch (Exception $e) {
                echo "   ✗ doc_type_registry table missing or migration not run\n";
                echo "      Error: {$e->getMessage()}\n";
            }
            
            // Check traveler_documents table exists
            try {
                $stmt = $pdo->query("SELECT COUNT(*) FROM traveler_documents");
                $count = $stmt->fetchColumn();
                echo "   ✓ traveler_documents table exists ({$count} rows)\n";
            } catch (Exception $e) {
                echo "   ✗ traveler_documents table missing or migration not run\n";
                echo "      Error: {$e->getMessage()}\n";
            }
            
            // Check summary columns
            try {
                $stmt = $pdo->query("SELECT summary, summary_dirty FROM travelers LIMIT 1");
                echo "   ✓ travelers.summary columns exist\n";
            } catch (Exception $e) {
                echo "   ✗ travelers summary columns missing (migration not run?)\n";
            }
            
        } else {
            echo "   ✗ db_connection.php doesn't create \$pdo\n";
        }
    } catch (Exception $e) {
        echo "   ✗ Error: {$e->getMessage()}\n";
    }
} else {
    echo "   ✗ db_connection.php not found\n";
}

// 6. Test Imagick (if loaded)
echo "\n[6] Imagick capabilities\n";
if (extension_loaded('imagick')) {
    try {
        $im = new Imagick();
        $formats = $im->queryFormats();
        echo "   ✓ Imagick loaded\n";
        echo "   ✓ Supports PDF: " . (in_array('PDF', $formats) ? 'yes' : 'NO') . "\n";
        echo "   ✓ Supports JPEG: " . (in_array('JPEG', $formats) ? 'yes' : 'NO') . "\n";
        
        // Check policy limits
        $policyFile = '/etc/ImageMagick-6/policy.xml';
        if (file_exists($policyFile)) {
            echo "   ✓ policy.xml found: {$policyFile}\n";
        } else {
            echo "   ⚠ policy.xml not at standard location\n";
        }
    } catch (Exception $e) {
        echo "   ✗ Imagick error: {$e->getMessage()}\n";
    }
} else {
    echo "   ✗ Imagick not loaded\n";
}

// 7. Check Gemini API key
echo "\n[7] Gemini API key\n";
if (file_exists('../../gemini-apikey.txt')) {
    $key = trim(file_get_contents('../../gemini-apikey.txt'));
    if (strlen($key) > 0) {
        echo "   ✓ gemini-apikey.txt exists (" . strlen($key) . " chars)\n";
    } else {
        echo "   ✗ gemini-apikey.txt is empty\n";
    }
} else {
    echo "   ✗ gemini-apikey.txt not found\n";
}

// Summary
echo "\n=== Summary ===\n";
$missing = array_filter($checks, fn($v) => !$v);
if (empty($missing)) {
    echo "✓ All checks passed!\n";
} else {
    echo "✗ " . count($missing) . " issue(s) found:\n";
    foreach ($missing as $item => $val) {
        echo "   - {$item}\n";
    }
}

echo "\nIf classify-document.php still fails, check PHP error logs:\n";
echo "   - /var/log/php-fpm/error.log (if using PHP-FPM)\n";
echo "   - /var/log/apache2/error.log (if using Apache)\n";
echo "   - Check your hosting panel's error logs\n";

// Helper
function parseBytes($str) {
    $str = trim($str);
    $last = strtolower($str[strlen($str)-1] ?? '');
    $val = (int)$str;
    switch($last) {
        case 'g': $val *= 1024;
        case 'm': $val *= 1024;
        case 'k': $val *= 1024;
    }
    return $val;
}