<?php
session_start();
header('Content-Type: application/json');
require_once('../../server/db_connection.php');
require_once('../../server/uuid_with_system_id_generator.php');
require_once('../../server/generate_meta_data.php');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
if (!$input) $input = $_POST;

$package_sys_id    = trim($input['package_sys_id'] ?? '');
$country_id        = intval($input['country_id'] ?? 0);
$country_name      = trim($input['country_name'] ?? '');
$exchange_rate     = floatval($input['exchange_rate'] ?? 1);
$profit_percentage = floatval($input['profit_percentage'] ?? 15);
$mode              = in_array($input['mode'] ?? '', ['activity', 'hotel']) ? $input['mode'] : 'activity';
$calculation_data  = $input['calculation_data'] ?? [];
$total_subtotal    = floatval($input['total_subtotal'] ?? 0);
$total_profit      = floatval($input['total_profit'] ?? 0);
$grand_total       = floatval($input['grand_total'] ?? 0);

if (empty($package_sys_id)) {
    echo json_encode(['success' => false, 'message' => 'Package System ID required']);
    exit;
}

try {
    // $pdo = getDBConnection();

    // Check if record already exists for this package
    $check = $pdo->prepare("SELECT id FROM package_calculations WHERE package_sys_id = :package_sys_id LIMIT 1");
    $check->execute([':package_sys_id' => $package_sys_id]);
    $existing = $check->fetch(PDO::FETCH_ASSOC);
    
    $ids = generateIDs('package-calculator');
    $uuid  = $ids['uuid'];
    $sys_id = $ids['sys_id'];

    $metaData = buildMetaData(null, $_SESSION['user_name'] ?? 'system');

    var_dump($ids, $metaData);
    die;

    $calcJson = json_encode($calculation_data);

    if ($existing) {
        $stmt = $pdo->prepare("
            UPDATE package_calculations SET
                country_id        = :country_id,
                country_name      = :country_name,
                exchange_rate     = :exchange_rate,
                profit_percentage = :profit_percentage,
                mode              = :mode,
                calculation_data  = :calculation_data,
                total_subtotal    = :total_subtotal,
                total_profit      = :total_profit,
                grand_total       = :grand_total,
                status            = 'saved'
            WHERE package_sys_id = :uuid
        ");
        $stmt->execute([
            ':country_id'        => $country_id,
            ':country_name'      => $country_name,
            ':exchange_rate'     => $exchange_rate,
            ':profit_percentage' => $profit_percentage,
            ':mode'              => $mode,
            ':calculation_data'  => $calcJson,
            ':total_subtotal'    => $total_subtotal,
            ':total_profit'      => $total_profit,
            ':grand_total'       => $grand_total,
            ':package_sys_id'    => $package_sys_id,
        ]);
        $calc_id = $existing['id'];
    } else {
        $stmt = $pdo->prepare("
            INSERT INTO package_calculations
                (sys_id, package_sys_id, country_id, country_name, exchange_rate, profit_percentage, activity_profit_pct, hotel_profit_pct, mode, calculation_data, total_subtotal, total_profit, grand_total, status)
            VALUES
                (:sys_id, :package_sys_id, :country_id, :country_name, :exchange_rate, :profit_percentage, :activity_profit_pct, :hotel_profit_pct, :mode, :calculation_data, :total_subtotal, :total_profit, :grand_total, 'saved')
        ");
        $stmt->execute([
            ':sys_id'            => $sys_id,
            ':package_sys_id'    => $package_sys_id,
            ':country_id'        => $country_id,
            ':country_name'      => $country_name,
            ':exchange_rate'     => $exchange_rate,
            ':profit_percentage' => $profit_percentage,
            ':mode'              => $mode,
            ':calculation_data'  => $calcJson,
            ':total_subtotal'    => $total_subtotal,
            ':total_profit'      => $total_profit,
            ':grand_total'       => $grand_total,
        ]);
        $calc_id = $pdo->lastInsertId();
    }

    // Update the packages.pack_price to reference this calculation
    $packPriceRef = json_encode([
        'has_calculation'  => true,
        'calculation_id'   => intval($calc_id),
        'calculation_uuid' => $package_uuid,
        'grand_total'      => $grand_total,
        'currency'         => 'BDT',
    ]);
    $pdo->prepare("UPDATE packages SET pack_price = :pack_price WHERE uuid = :uuid")
        ->execute([':pack_price' => $packPriceRef, ':uuid' => $package_uuid]);

    echo json_encode([
        'success'        => true,
        'message'        => 'Calculation saved successfully',
        'calculation_id' => intval($calc_id),
    ]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}