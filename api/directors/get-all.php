<?php
// GET /api/directors/get-all.php
// Returns all directors with computed investment & ownership

require_once '../../server/db_connection.php';
require_once '../../server/director-calculation.php';
jsonHeaders();

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    sendError('Method not allowed', 405);
}

try {
    // $pdo variable-ti db_connection.php theke ashche
    $sql = "
        SELECT
            d.id, d.uuid, d.sys_id, d.name, d.email, d.phone, d.address,
            d.basic_info, d.emergency_contact, d.status, d.profile_photo,
            COALESCE(
                SUM(CASE WHEN t.type = 'invest' THEN t.amount ELSE 0 END) -
                SUM(CASE WHEN t.type = 'withdraw' THEN t.amount ELSE 0 END),
                0
            ) AS total_investment
        FROM directors d
        LEFT JOIN director_transactions t ON t.director_sys_id = d.sys_id COLLATE utf8mb4_unicode_ci
        GROUP BY d.id
        ORDER BY d.name ASC
    ";

    // Prepare ebong execute
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    
    // Fetch all results
    $directors = $stmt->fetchAll();
    
    // var_dump($directors);
    // die;

    // Data processing (Loop)
    foreach ($directors as &$dir) {
        $dir['total_investment'] = (float) $dir['total_investment'];
        
        // calcOwnership function-ti dhore nichchi available ache
        $dir['ownership_percent'] = calcOwnership($dir['total_investment']);
        
        // Decode JSON fields (Short-hand condition)
        $dir['basic_info'] = !empty($dir['basic_info']) ? json_decode($dir['basic_info'], true) : null;
        $dir['emergency_contact'] = !empty($dir['emergency_contact']) ? json_decode($dir['emergency_contact'], true) : null;
    }

    // Success response using your helper
    sendSuccess($directors);

} catch (Exception $e) {
    // Error response using your helper
    sendError("Database Error: " . $e->getMessage(), 500);
}
