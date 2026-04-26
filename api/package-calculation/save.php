<!--* api/package-calculation/save.php-->

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

$package_sys_id      = trim($input['package_sys_id'] ?? '');
$country_id          = intval($input['country_id'] ?? 0);
$country_name        = trim($input['country_name'] ?? '');
$local_currency      = trim($input['local_currency'] ?? '');
$exchange_rate       = floatval($input['exchange_rate'] ?? 1);
$activity_profit_pct = floatval($input['activity_profit_pct'] ?? $input['profit_percentage'] ?? 15);
$hotel_profit_pct    = floatval($input['hotel_profit_pct'] ?? 12);
$profit_percentage   = floatval($input['profit_percentage'] ?? 15);
$mode                = in_array($input['mode'] ?? '', ['activity', 'hotel']) ? $input['mode'] : 'activity';
$calculation_data    = $input['calculation_data'] ?? [];
$total_subtotal      = floatval($input['total_subtotal'] ?? 0);
$total_profit        = floatval($input['total_profit'] ?? 0);
$grand_total         = floatval($input['grand_total'] ?? 0);

if (empty($package_sys_id)) {
    echo json_encode(['success' => false, 'message' => 'Package System ID required']);
    exit;
}

try {
    // Transaction shuru korchi
    $pdo->beginTransaction();

    // Check if record already exists
    $check = $pdo->prepare("SELECT id, sys_id, meta_data FROM package_calculations WHERE package_sys_id = :package_sys_id LIMIT 1");
    $check->execute([':package_sys_id' => $package_sys_id]);
    $existing = $check->fetch(PDO::FETCH_ASSOC);

    $calcJson = json_encode($calculation_data);

    if ($existing) {
        $metaData = buildMetaData($existing['meta_data'], $_SESSION['user_name'] ?? 'system');
        
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
                meta_data         = :meta_data,
                status            = 'saved'
            WHERE package_sys_id = :package_sys_id
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
            ':meta_data'         => $metaData,
            ':package_sys_id'    => $package_sys_id
        ]);
        $calc_id = $existing['sys_id'];
    } else {
        $ids = generateIDs('package-calculator');
        $sys_id = $ids['sys_id'];
        $metaData = buildMetaData(null, $_SESSION['user_name'] ?? 'system');
            
        $stmt = $pdo->prepare("
            INSERT INTO package_calculations 
                (sys_id, package_sys_id, country_id, country_name, exchange_rate, profit_percentage, activity_profit_pct, hotel_profit_pct, mode, calculation_data, total_subtotal, total_profit, grand_total, meta_data, status) 
            VALUES 
                (:sys_id, :package_sys_id, :country_id, :country_name, :exchange_rate, :profit_percentage, :activity_profit_pct, :hotel_profit_pct, :mode, :calculation_data, :total_subtotal, :total_profit, :grand_total, :meta_data, 'saved')
        ");
        $stmt->execute([
            ':sys_id'              => $sys_id,
            ':package_sys_id'      => $package_sys_id,
            ':country_id'          => $country_id,
            ':country_name'        => $country_name,
            ':exchange_rate'       => $exchange_rate,
            ':profit_percentage'   => $profit_percentage,
            ':activity_profit_pct' => $activity_profit_pct,
            ':hotel_profit_pct'    => $hotel_profit_pct,
            ':mode'                => $mode,
            ':calculation_data'    => $calcJson,
            ':total_subtotal'      => $total_subtotal,
            ':total_profit'        => $total_profit,
            ':grand_total'         => $grand_total,
            ':meta_data'           => $metaData
        ]);
        $calc_id = $sys_id;
    }

    // Update the packages table
    $packPriceRef = json_encode([
        'has_calculation'  => true,
        'calculation_id'   => $calc_id,
        'grand_total'      => $grand_total,
        'local_currency'   => $local_currency,
        'selling_currency' => 'BDT',
        'details'          => $calculation_data
    ]);

    $updatePack = $pdo->prepare("UPDATE packages SET package_calculations_details = :pack_price_details WHERE sys_id = :package_sys_id");
    $updatePack->execute([
        ':pack_price_details' => $packPriceRef, 
        ':package_sys_id'     => $package_sys_id
    ]);

    // Sob thik thak thakle commit korbo
    $pdo->commit();

    echo json_encode([
        'success'        => true,
        'message'        => 'Calculation saved successfully',
        'calculation_id' => $calc_id,
    ]);

} catch (Exception $e) {
    // Error hole rollback korbe
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}