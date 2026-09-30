<?php
// api/eps/update-structure.php
//
// "Edit" an EPS structure by versioning it:
//   1. Mark the current record inactive (history preserved).
//   2. INSERT a brand-new record with the updated values + new effective_date.
//
// Accepts JSON POST:
//   { eps_sys_id, effective_date, basic_salary, house_rent,
//     medical_allowance, conveyance, pf_deduction, tax_deduction,
//     other_deduction, status }

declare(strict_types=1);

session_start();

require_once '../../server/db_connection.php';
require_once '../../server/hrm_permissions.php';
requireHrm($pdo, 'eps_manage');
require_once '../../server/uuid_with_system_id_generator.php';
require_once '../../server/generate_meta_data.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

function jsonResponse(bool $success, string $message, array $extra = []): void
{
    echo json_encode(array_merge(['success' => $success, 'message' => $message], $extra));
    exit;
}

$rawInput = file_get_contents('php://input');
$data = json_decode($rawInput, true);

if (json_last_error() !== JSON_ERROR_NONE || empty($data)) {
    jsonResponse(false, 'Invalid or empty JSON payload');
}

foreach (['eps_sys_id', 'effective_date', 'basic_salary'] as $f) {
    if (!isset($data[$f]) || $data[$f] === '') {
        jsonResponse(false, "Required field missing: $f");
    }
}

try {
    $pdo->beginTransaction();

    // 1. Fetch the existing record
    $stmt = $pdo->prepare("SELECT * FROM eps_structures WHERE sys_id = ? LIMIT 1");
    $stmt->execute([$data['eps_sys_id']]);
    $existing = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$existing) {
        throw new Exception('EPS record not found');
    }

    // 2. Guard: no OTHER record for same employee + new effective_date
    $stmt = $pdo->prepare(
        "SELECT id FROM eps_structures
         WHERE employee_id = ? AND effective_date = ? AND sys_id != ?"
    );
    $stmt->execute([$existing['employee_id'], $data['effective_date'], $data['eps_sys_id']]);
    if ($stmt->fetch()) {
        throw new Exception('An EPS record already exists for this employee on ' . $data['effective_date']);
    }

    // 3. Salary calculations
    $basic_salary      = (float)$data['basic_salary'];
    $house_rent        = (float)($data['house_rent'] ?? 0);
    $medical_allowance = (float)($data['medical_allowance'] ?? 0);
    $conveyance        = (float)($data['conveyance'] ?? 0);
    $allowance         = (float)($data['allowance'] ?? 0);
    $pf_deduction      = (float)($data['pf_deduction'] ?? 0);
    $tax_deduction     = (float)($data['tax_deduction'] ?? 0);
    $other_deduction   = (float)($data['other_deduction'] ?? 0);

    $gross_salary     = $basic_salary + $house_rent + $medical_allowance + $conveyance + $allowance;
    $total_deductions = $pf_deduction + $tax_deduction + $other_deduction;
    $net_salary       = $gross_salary - $total_deductions;

    $metaDataJson = buildMetaData(null, $_SESSION['user_name'] ?? 'system');

    // 4. Mark old record inactive — history preserved
    $stmt = $pdo->prepare("UPDATE eps_structures SET status = 'history' WHERE sys_id = ?");
    $stmt->execute([$data['eps_sys_id']]);

    // 5. Insert new versioned record
    $uuid = generateIDs('eps_structures');

    $stmt = $pdo->prepare(
        "INSERT INTO eps_structures (
            uuid, sys_id, employee_id, employee_name,
            effective_date, basic_salary, house_rent, medical_allowance, conveyance,
            pf_deduction, tax_deduction, other_deduction,
            gross_salary, total_deductions, net_salary,
            status, meta_data
        ) VALUES (
            :uuid, :sys_id, :employee_id, :employee_name,
            :effective_date, :basic_salary, :house_rent, :medical_allowance, :conveyance,
            :pf_deduction, :tax_deduction, :other_deduction,
            :gross_salary, :total_deductions, :net_salary,
            :status, :meta_data
        )"
    );
    $stmt->execute([
        ':uuid'              => $uuid['uuid'],
        ':sys_id'            => $uuid['sys_id'],
        ':employee_id'       => $existing['employee_id'],
        ':employee_name'     => $existing['employee_name'],
        ':effective_date'    => $data['effective_date'],
        ':basic_salary'      => $basic_salary,
        ':house_rent'        => $house_rent,
        ':medical_allowance' => $medical_allowance,
        ':conveyance'        => $conveyance,
        ':pf_deduction'      => $pf_deduction,
        ':tax_deduction'     => $tax_deduction,
        ':other_deduction'   => $other_deduction,
        ':gross_salary'      => $gross_salary,
        ':total_deductions'  => $total_deductions,
        ':net_salary'        => $net_salary,
        ':status'            => 'active',
        ':meta_data'         => $metaDataJson,
    ]);

    $pdo->commit();

    jsonResponse(true, 'EPS structure updated successfully (new version created)', [
        'new_sys_id'    => $uuid['sys_id'],
        'old_sys_id'    => $data['eps_sys_id'],
        'salary_summary' => [
            'gross_salary'     => $gross_salary,
            'total_deductions' => $total_deductions,
            'net_salary'       => $net_salary,
        ]
    ]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    jsonResponse(false, $e->getMessage());
}