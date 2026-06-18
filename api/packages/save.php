<?php
/**
 * api/packages/save.php (Gen-3)
 * POST — update existing package fields
 */
session_start();
require_once '../../server/api_bootstrap.php';
require_once '../../server/db_connection.php';
require_once '../../server/generate_meta_data.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success'=>false,'message'=>'POST only']); exit;
}

$in     = json_decode(file_get_contents('php://input'), true) ?: [];
$sys_id = trim($in['sys_id'] ?? '');
if (!$sys_id) { echo json_encode(['success'=>false,'message'=>'sys_id required']); exit; }

try {
    $row = $pdo->prepare("SELECT * FROM packages WHERE sys_id=? AND status!='deleted' LIMIT 1");
    $row->execute([$sys_id]);
    $pkg = $row->fetch();
    if (!$pkg) { echo json_encode(['success'=>false,'message'=>'Package not found']); exit; }

    $meta = buildMetaData($pkg['meta_data'], $_SESSION['user_name'] ?? 'system');

    // ── Columns that exist in the packages table ──────────────────────
    $allowed = [
        'title','package_type','description','full_description',
        'start_date','end_date','duration',
        'adults','children','infants',
        'sell_currency_code','sell_currency_title','sell_currency_symbol',
        'client_sys_id','client_name',
        'countries','cities','hotels',
        'pack_itenaries','pack_price',
        'pack_inclusions','pack_exclusions',
        'pack_components',   // ← added via migration
        'pricing_config',    // ← added via migration
        'highlights',        // ← changed to JSON via migration
        'no_of_pax',
        'image','cover_image',
        'progress_step','completion_status','active_quote_sys_id',
        'rating','notes','booking_ref','version',
        'overall_price','air_ticket_details','assigned_to_sys_id',
    ];

    $jsonFields = [
        'countries','cities','hotels','pack_itenaries','pack_price',
        'pack_inclusions','pack_exclusions','pack_components',
        'pricing_config','highlights','no_of_pax',
    ];

    $sets   = [];
    $params = [':sid' => $sys_id, ':meta' => $meta];

    foreach ($allowed as $field) {
        if (!array_key_exists($field, $in)) continue;
        $val = $in[$field];
        if (in_array($field, $jsonFields) && (is_array($val) || is_object($val))) {
            $val = json_encode($val, JSON_UNESCAPED_UNICODE);
        }
        $sets[]              = "`{$field}` = :{$field}";
        $params[":{$field}"] = $val;
    }

    // date 3-way recalc
    if (array_key_exists('start_date',$in)||array_key_exists('end_date',$in)||array_key_exists('duration',$in)) {
        $sd  = $params[':start_date'] ?? $pkg['start_date'];
        $ed  = $params[':end_date']   ?? $pkg['end_date'];
        $dur = $params[':duration']   ?? $pkg['duration'];
        if ($sd && $ed && !$dur) {
            $dur = (new DateTime($ed))->diff(new DateTime($sd))->days;
            $sets[] = '`duration`=:duration'; $params[':duration']=$dur;
        } elseif ($sd && $dur && !$ed) {
            $ed = (new DateTime($sd))->modify("+{$dur} days")->format('Y-m-d');
            $sets[] = '`end_date`=:end_date'; $params[':end_date']=$ed;
        } elseif ($ed && $dur && !$sd) {
            $sd = (new DateTime($ed))->modify("-{$dur} days")->format('Y-m-d');
            $sets[] = '`start_date`=:start_date'; $params[':start_date']=$sd;
        }
    }

    if (empty($sets)) { echo json_encode(['success'=>false,'message'=>'No fields to update']); exit; }

    $sets[] = '`meta_data`=:meta';
    $sql    = "UPDATE packages SET ".implode(', ',$sets)." WHERE sys_id=:sid";
    $pdo->prepare($sql)->execute($params);

    echo json_encode(['success'=>true,'sys_id'=>$sys_id,'message'=>'Package updated.'], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}