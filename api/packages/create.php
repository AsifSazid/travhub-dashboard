<?php
/**
 * api/packages/create.php (Gen-3)
 * POST { title, package_type, description?, start_date?, end_date?, duration?,
 *        adults?, children?, infants?, sell_currency_code?,
 *        client_sys_id?, client_name? }
 */
session_start();
require_once '../../server/api_bootstrap.php';
require_once '../../server/db_connection.php';
require_once '../../server/id_generator.php';
require_once '../../server/generate_meta_data.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success'=>false,'message'=>'POST only']); exit;
}

$in = json_decode(file_get_contents('php://input'), true) ?: [];

$title            = trim($in['title']           ?? '');
$package_type     = trim($in['package_type']    ?? 'custom');
$description      = trim($in['description']     ?? '');
$start_date       = trim($in['start_date']      ?? '');
$end_date         = trim($in['end_date']        ?? '');
$duration         = isset($in['duration'])       ? (int)$in['duration']   : null;
$adults           = max(1,  (int)($in['adults']   ?? 1));
$children         = max(0,  (int)($in['children'] ?? 0));
$infants          = max(0,  (int)($in['infants']  ?? 0));
$sell_currency_code = strtoupper(trim($in['sell_currency_code'] ?? 'BDT'));
$client_sys_id    = trim($in['client_sys_id']   ?? '');
$client_name      = trim($in['client_name']     ?? '');

$validTypes = ['group','fit','corporate','factory_tour','custom','fixed','umrah'];
if (!in_array($package_type, $validTypes)) $package_type = 'custom';

if (!$title) { echo json_encode(['success'=>false,'message'=>'title required']); exit; }

// Date / duration 3-way calc
if ($start_date && $end_date && !$duration) {
    $diff = (new DateTime($end_date))->diff(new DateTime($start_date));
    $duration = $diff->days;
} elseif ($start_date && $duration && !$end_date) {
    $end_date = (new DateTime($start_date))->modify("+{$duration} days")->format('Y-m-d');
} elseif ($end_date && $duration && !$start_date) {
    $start_date = (new DateTime($end_date))->modify("-{$duration} days")->format('Y-m-d');
}

try {
    $ids    = generateIDs($pdo, 'packages');
    $sys_id = $ids['sys_id'];
    $uuid   = $ids['uuid'];
    $meta   = buildMetaData(null, $_SESSION['user_name'] ?? 'system');

    $pdo->prepare("
        INSERT INTO packages
            (uuid, sys_id, title, package_type, description,
             start_date, end_date, duration,
             adults, children, infants,
             sell_currency_code, client_sys_id, client_name,
             progress_step, completion_status, status, meta_data)
        VALUES
            (:uuid,:sid,:title,:ptype,:desc,
             :sd,:ed,:dur,
             :adults,:children,:infants,
             :ccy,:csid,:cname,
             1,'draft','active',:meta)
    ")->execute([
        ':uuid'     => $uuid,          ':sid'     => $sys_id,
        ':title'    => $title,         ':ptype'   => $package_type,
        ':desc'     => $description ?: null,
        ':sd'       => $start_date  ?: null,
        ':ed'       => $end_date    ?: null,
        ':dur'      => $duration,
        ':adults'   => $adults,        ':children'=> $children,
        ':infants'  => $infants,
        ':ccy'      => $sell_currency_code,
        ':csid'     => $client_sys_id  ?: null,
        ':cname'    => $client_name    ?: null,
        ':meta'     => $meta,
    ]);

    echo json_encode([
        'success' => true,
        'action'  => 'created',
        'sys_id'  => $sys_id,
        'message' => "Package '{$title}' created.",
    ], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
