<?php
// PATH: /api/epsgw/charge-rates.php
// (epsgw = EPS Gateway)
//
// Manages gateway_charge_rates -- a configurable table of per-payment-method
// surcharge percentages, so the user can adjust rates (card vs MFS vs a
// specific bank/wallet) from a UI without touching code. Read by
// select-payment-method.php (to show the client what each option costs)
// and by initiate.php (to compute the actual amount sent to EPS).
//
// Actions (?action=...):
//   list   -> all configured rates (default, GET)
//   toggle -> activate/deactivate a rate (POST, super-admin only)
//   store  -> create or update a rate (POST, super-admin only)
//   delete -> remove a rate (POST, super-admin only)

session_start();
require_once __DIR__ . '/../../server/db_connection.php';
require_once __DIR__ . '/../../server/sys_id_generator_v2.php';
require_once __DIR__ . '/../../server/generate_meta_data.php';
header('Content-Type: application/json');

$action = $_REQUEST['action'] ?? 'list';

function _requireSuperAdmin(): void
{
    if (($_SESSION['role'] ?? null) !== '0') {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Only a super-admin can manage charge rates']);
        exit;
    }
}

try {
    switch ($action) {
        case 'toggle': _requireSuperAdmin(); handleToggle($pdo); break;
        case 'store':  _requireSuperAdmin(); handleStore($pdo);  break;
        case 'delete': _requireSuperAdmin(); handleDelete($pdo); break;
        case 'list':
        default:       handleList($pdo); break;
    }
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

// GET is intentionally allowed for anyone logged-in (select-payment-method.php
// needs this to show clients their charge options) -- only mutation actions
// (toggle/store/delete) require super-admin.
function handleList(PDO $pdo): void
{
    $activeOnly = isset($_GET['active_only']) && $_GET['active_only'] !== '0';
    $sql = "SELECT * FROM gateway_charge_rates" . ($activeOnly ? " WHERE is_active = 1" : "") . " ORDER BY payment_method_type ASC, financial_entity_name ASC";
    $rows = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode(['success' => true, 'rates' => $rows]);
}

function handleStore(PDO $pdo): void
{
    $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;

    $sysId              = trim($input['sys_id'] ?? ''); // present = update, absent = create
    $paymentMethodType  = trim($input['payment_method_type'] ?? ''); // 'card' | 'mfs' | 'bank' | 'other'
    $financialEntityName = trim($input['financial_entity_name'] ?? '') ?: null; // NULL = default rate for this method type
    $chargePercent      = (float)($input['charge_percent'] ?? 0);
    $borneBy            = trim($input['borne_by'] ?? 'client'); // 'client' | 'company'
    $isActive           = !empty($input['is_active']) ? 1 : 0;

    $errors = [];
    if (!in_array($paymentMethodType, ['card', 'mfs', 'bank', 'other'], true)) $errors[] = "payment_method_type must be one of: card, mfs, bank, other";
    if ($chargePercent < 0) $errors[] = 'charge_percent cannot be negative';
    if (!in_array($borneBy, ['client', 'company'], true)) $errors[] = "borne_by must be 'client' or 'company'";
    if ($errors) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Validation failed', 'errors' => $errors]);
        return;
    }

    $userName = $_SESSION['user_name'] ?? 'system';

    if ($sysId) {
        $pdo->prepare("
            UPDATE gateway_charge_rates
            SET payment_method_type = ?, financial_entity_name = ?, charge_percent = ?, borne_by = ?, is_active = ?, updated_by = ?, updated_at = NOW()
            WHERE sys_id = ?
        ")->execute([$paymentMethodType, $financialEntityName, $chargePercent, $borneBy, $isActive, $userName, $sysId]);
        echo json_encode(['success' => true, 'message' => 'Rate updated', 'sys_id' => $sysId]);
    } else {
        $ids = generateV2IDs($pdo, 'gateway_charge_rates');
        $meta = buildMetaData(null, $userName);
        $pdo->prepare("
            INSERT INTO gateway_charge_rates
            (uuid, sys_id, payment_method_type, financial_entity_name, charge_percent, borne_by, is_active, updated_by, updated_at, meta_data)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), ?)
        ")->execute([$ids['uuid'], $ids['sys_id'], $paymentMethodType, $financialEntityName, $chargePercent, $borneBy, $isActive, $userName, $meta]);
        echo json_encode(['success' => true, 'message' => 'Rate created', 'sys_id' => $ids['sys_id']]);
    }
}

function handleToggle(PDO $pdo): void
{
    $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
    $sysId = trim($input['sys_id'] ?? '');
    $isActive = !empty($input['is_active']) ? 1 : 0;
    if (!$sysId) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'sys_id is required']);
        return;
    }
    $pdo->prepare("UPDATE gateway_charge_rates SET is_active = ?, updated_by = ?, updated_at = NOW() WHERE sys_id = ?")
        ->execute([$isActive, $_SESSION['user_name'] ?? 'system', $sysId]);
    echo json_encode(['success' => true, 'message' => $isActive ? 'Rate activated' : 'Rate deactivated']);
}

function handleDelete(PDO $pdo): void
{
    $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
    $sysId = trim($input['sys_id'] ?? '');
    if (!$sysId) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'sys_id is required']);
        return;
    }
    $pdo->prepare("DELETE FROM gateway_charge_rates WHERE sys_id = ?")->execute([$sysId]);
    echo json_encode(['success' => true, 'message' => 'Rate deleted']);
}