<?php
// api/package-calculation/get.php
session_start();
header('Content-Type: application/json');
require_once('../../server/db_connection.php');

// Accept ?packageId={packages.sys_id}
$package_sys_id = trim($_GET['packageId'] ?? $_GET['package_sys_id'] ?? $_GET['uuid'] ?? '');

if (empty($package_sys_id)) {
    echo json_encode(['success' => false, 'exists' => false, 'message' => 'packageId required']);
    exit;
}

try {
    // First, look in package_calculations table by package_sys_id
    $stmt = $pdo->prepare("
        SELECT pc.*
        FROM package_calculations pc
        WHERE pc.package_sys_id = :package_sys_id
        LIMIT 1
    ");
    $stmt->execute([':package_sys_id' => $package_sys_id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($row) {
        $row['calculation_data'] = json_decode($row['calculation_data'] ?? '{}', true);
        $row['meta_data']        = json_decode($row['meta_data']        ?? '{}', true);
        // Also fetch package hotels for pre-fill
        $hStmt = $pdo->prepare("SELECT hotels, pack_itenaries FROM packages WHERE sys_id = :sid LIMIT 1");
        $hStmt->execute([':sid' => $package_sys_id]);
        $pkgHRow = $hStmt->fetch(PDO::FETCH_ASSOC);
        $row['package_hotels']    = json_decode($pkgHRow['hotels']         ?? '[]', true) ?: [];
        $row['package_itenaries'] = json_decode($pkgHRow['pack_itenaries'] ?? '[]', true) ?: [];

        echo json_encode([
            'success' => true,
            'exists'  => true,
            'source'  => 'package_calculations',
            'data'    => $row,
        ]);
        exit;
    }

    // Fallback: check packages.package_calculations_details
    // (written by save.php under the key 'details')
    $pkgStmt = $pdo->prepare("
        SELECT package_calculations_details
        FROM packages
        WHERE sys_id = :sys_id
        LIMIT 1
    ");
    $pkgStmt->execute([':sys_id' => $package_sys_id]);
    $pkg = $pkgStmt->fetch(PDO::FETCH_ASSOC);

    if ($pkg && !empty($pkg['package_calculations_details'])) {
        $details = json_decode($pkg['package_calculations_details'], true);
        // details format: { has_calculation, calculation_id, grand_total, local_currency, selling_currency, details:{activity,hotel} }
        if (!empty($details['has_calculation'])) {
            echo json_encode([
                'success' => true,
                'exists'  => true,
                'source'  => 'packages_column',
                'data'    => [
                    'sys_id'           => $details['calculation_id'] ?? null,
                    'grand_total'      => $details['grand_total']    ?? 0,
                    'local_currency'   => $details['local_currency'] ?? '',
                    'calculation_data' => $details['details']        ?? [],
                    // Other fields unknown from this fallback — caller should re-fetch from package_calculations
                ],
            ]);
            exit;
        }
    }

    // Nothing found — but still return package hotels so calculator can pre-fill
    $hotelsStmt = $pdo->prepare("SELECT hotels, pack_itenaries, title FROM packages WHERE sys_id = :sid LIMIT 1");
    $hotelsStmt->execute([':sid' => $package_sys_id]);
    $pkgRow = $hotelsStmt->fetch(PDO::FETCH_ASSOC);
    $hotels     = json_decode($pkgRow['hotels']         ?? '[]', true) ?: [];
    $itenaries  = json_decode($pkgRow['pack_itenaries'] ?? '[]', true) ?: [];

    echo json_encode([
        'success'           => true,
        'exists'            => false,
        'package_hotels'    => $hotels,
        'package_itenaries' => $itenaries,
        'package_title'     => $pkgRow['title'] ?? '',
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'exists' => false, 'message' => $e->getMessage()]);
}