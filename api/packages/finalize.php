<?php
session_start();
// api/packages/finalize.php
// POST { "uuid": "...", "pdf_template": "detailed|bullet" }
// 1. Sets completion_status = 'completed'
// 2. Inserts activity override rows into activities table
//    (one row per modified activity per day, linked to package_sys_id)
header('Content-Type: application/json');
require_once('../../server/db_connection.php');
require_once('../../server/masterdata-id-generator.php');
require_once('../../server/generate_meta_data.php');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$input        = json_decode(file_get_contents('php://input'), true) ?: [];
$uuid         = trim($input['uuid']         ?? '');
$pdf_template = trim($input['pdf_template'] ?? 'detailed');

if (!$uuid) {
    echo json_encode(['success' => false, 'message' => 'uuid required']);
    exit;
}

try {
    // ── Fetch the package ─────────────────────────────────────────
    $pkgStmt = $pdo->prepare("
        SELECT id, sys_id, completion_status, pack_itenaries
        FROM packages
        WHERE uuid = ? AND status != 'deleted'
        LIMIT 1
    ");
    $pkgStmt->execute([$uuid]);
    $pkg = $pkgStmt->fetch(PDO::FETCH_ASSOC);

    if (!$pkg) {
        echo json_encode(['success' => false, 'message' => 'Package not found']);
        exit;
    }

    if ($pkg['completion_status'] === 'completed') {
        echo json_encode(['success' => false, 'message' => 'Package is already finalized and cannot be edited.']);
        exit;
    }

    $packageSysId = $pkg['sys_id'];
    $itineraries  = json_decode($pkg['pack_itenaries'] ?? '[]', true) ?: [];

    // ── Insert activity overrides ──────────────────────────────────
    // For each activity in each day that has override data (modified fields),
    // insert a new row in activities table with is_package_override = 1
    $overrideCount = 0;

    foreach ($itineraries as $day) {
        $dayActivities = $day['activities'] ?? [];
        foreach ($dayActivities as $act) {
            // Only insert override if this activity came from masterdata (has original_sys_id)
            $originalSysId = $act['original_sys_id'] ?? $act['sys_id'] ?? null;
            if (!$originalSysId) continue;

            $countrySysId = $act['country_sys_id'] ?? '';
            if (!$countrySysId) continue;

            // Check if override already exists for this package + original activity
            $chk = $pdo->prepare("
                SELECT id FROM activities
                WHERE package_sys_id = ? AND meta_data->'$.original_sys_id' = ?
                LIMIT 1
            ");
            $chk->execute([$packageSysId, $originalSysId]);
            if ($chk->fetchColumn()) continue; // already inserted

            // Generate new sys_id for the override row
            $ids      = generateHierarchyIDs($pdo, 'activities', $countrySysId);
            $metaJson = buildMetaData(null, $_SESSION['user_name'] ?? 'system');

            // Merge original_sys_id into meta so we can trace it back
            $meta                    = json_decode($metaJson, true) ?: [];
            $meta['original_sys_id'] = $originalSysId;
            $meta['package_sys_id']  = $packageSysId;
            $meta['day_number']      = $day['day_number'] ?? null;
            $metaJson                = json_encode($meta, JSON_UNESCAPED_UNICODE);

            $pdo->prepare("
                INSERT INTO activities
                    (uuid, sys_id, country_sys_id, name, type, location,
                     start_time, end_time, duration_hours, popularity,
                     pickup_from_city, dropoff_city, itineraries,
                     inclusions, exclusions, transfers,
                     package_sys_id, is_package_override,
                     status, meta_data)
                VALUES
                    (:uuid, :sys_id, :country_sys_id, :name, :type, :location,
                     :start_time, :end_time, :duration_hours, :popularity,
                     :pickup_from_city, :dropoff_city, :itineraries,
                     :inclusions, :exclusions, :transfers,
                     :package_sys_id, 1,
                     'active', :meta_data)
            ")->execute([
                ':uuid'             => $ids['uuid'],
                ':sys_id'           => $ids['sys_id'],
                ':country_sys_id'   => $countrySysId,
                ':name'             => $act['name']           ?? '',
                ':type'             => $act['type']           ?? 'tour',
                ':location'         => $act['location']       ?? null,
                ':start_time'       => $act['start_time']     ?? null,
                ':end_time'         => $act['end_time']       ?? null,
                ':duration_hours'   => (float)($act['duration_hours'] ?? 0),
                ':popularity'       => (int)($act['popularity']       ?? 3),
                ':pickup_from_city' => json_encode($act['pickup_from_city'] ?? [], JSON_UNESCAPED_UNICODE),
                ':dropoff_city'     => json_encode($act['dropoff_city']     ?? [], JSON_UNESCAPED_UNICODE),
                ':itineraries'      => json_encode($act['itineraries']      ?? [], JSON_UNESCAPED_UNICODE),
                ':inclusions'       => json_encode($act['inclusions']       ?? [], JSON_UNESCAPED_UNICODE),
                ':exclusions'       => json_encode($act['exclusions']       ?? [], JSON_UNESCAPED_UNICODE),
                ':transfers'        => json_encode($act['transfers']        ?? [], JSON_UNESCAPED_UNICODE),
                ':package_sys_id'   => $packageSysId,
                ':meta_data'        => $metaJson,
            ]);
            $overrideCount++;
        }
    }

    // ── Mark package as completed ─────────────────────────────────
    $pdo->prepare("
        UPDATE packages SET
            completion_status = 'completed',
            pdf_template      = :pdf_template
        WHERE uuid = ?
    ")->execute([$pdf_template, $uuid]);

    // Note: pdf_template column added via ALTER below — add to packages table if missing
    // ALTER TABLE packages ADD COLUMN pdf_template VARCHAR(20) NULL DEFAULT 'detailed';

    echo json_encode([
        'success'        => true,
        'message'        => 'Package finalized successfully.',
        'override_count' => $overrideCount,
        'pdf_template'   => $pdf_template,
        'package_sys_id' => $packageSysId,
    ]);

} catch (Throwable $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}