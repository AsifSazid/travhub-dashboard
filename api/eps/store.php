<?php
// api/eps/store.php

declare(strict_types=1);

session_start();

require_once '../../server/db_connection.php';
require_once '../../server/uuid_with_system_id_generator.php';
require_once '../../server/generate_meta_data.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

error_reporting(E_ALL);
ini_set('display_errors', '1');

/**
 * Helper: JSON response
 */
function jsonResponse(bool $success, string $message, array $extra = []): void
{
    echo json_encode(array_merge([
        'success' => $success,
        'message' => $message
    ], $extra));
    exit;
}

/**
 * Read JSON input safely
 */
$rawInput = file_get_contents('php://input');
$data = json_decode($rawInput, true);

if (json_last_error() !== JSON_ERROR_NONE || empty($data)) {
    jsonResponse(false, 'Invalid or empty JSON payload');
}

/**
 * Validate required fields
 */
$requiredFields = ['employee_id', 'effective_date', 'basic_salary'];
$missing = [];

foreach ($requiredFields as $field) {
    if (!isset($data[$field]) || $data[$field] === '') {
        $missing[] = $field;
    }
}

if (!empty($missing)) {
    jsonResponse(
        false,
        'Required fields are missing: ' . implode(', ', $missing)
    );
}

try {
    $pdo->beginTransaction();

    /**
     * Generate UUID & Meta
     */
    $uuid = generateIDs('eps_structures');
    $metaDataJson = buildMetaData(
        null,
        $_SESSION['user_name'] ?? 'system'
    );

    /**
     * Validate employee
     */
    $stmt = $pdo->prepare(
        "SELECT sys_id 
         FROM employees 
         WHERE sys_id = :employee_id 
           AND status = 'active'"
    );
    $stmt->execute([
        ':employee_id' => $data['employee_id']
    ]);

    if (!$stmt->fetch()) {
        throw new Exception('Employee not found or inactive');
    }

    /**
     * Prevent duplicate EPS
     */
    $stmt = $pdo->prepare(
        "SELECT id 
         FROM eps_structures 
         WHERE employee_id = :employee_id 
           AND effective_date = :effective_date"
    );
    $stmt->execute([
        ':employee_id'   => $data['employee_id'],
        ':effective_date'=> $data['effective_date']
    ]);

    if ($stmt->fetch()) {
        throw new Exception(
            'EPS structure already exists for this employee on this date'
        );
    }

    /**
     * Salary calculations
     */
    $basic_salary       = (float) $data['basic_salary'];
    $house_rent         = (float) ($data['house_rent'] ?? 0);
    $medical_allowance  = (float) ($data['medical_allowance'] ?? 0);
    $conveyance         = (float) ($data['conveyance'] ?? 0);
    $allowance         = (float) ($data['allowance'] ?? 0);

    $pf_deduction       = (float) ($data['pf_deduction'] ?? 0);
    $tax_deduction      = (float) ($data['tax_deduction'] ?? 0);
    $other_deduction    = (float) ($data['other_deduction'] ?? 0);

    $gross_salary       = $basic_salary + $house_rent + $medical_allowance + $conveyance + $allowance;
    $total_deductions   = $pf_deduction + $tax_deduction + $other_deduction;
    $net_salary         = $gross_salary - $total_deductions;

    /**
     * Insert EPS
     */
    $stmt = $pdo->prepare(
        "INSERT INTO eps_structures (
            uuid,
            sys_id,
            employee_id,
            employee_name,
            effective_date,
            basic_salary,
            house_rent,
            medical_allowance,
            conveyance,
            pf_deduction,
            tax_deduction,
            other_deduction,
            gross_salary,
            total_deductions,
            net_salary,
            status,
            meta_data
        ) VALUES (
            :uuid,
            :sys_id,
            :employee_id,
            :employee_name,
            :effective_date,
            :basic_salary,
            :house_rent,
            :medical_allowance,
            :conveyance,
            :pf_deduction,
            :tax_deduction,
            :other_deduction,
            :gross_salary,
            :total_deductions,
            :net_salary,
            :status,
            :meta_data
        )"
    );

    $stmt->execute([
        ':uuid'              => $uuid['uuid'],
        ':sys_id'            => $uuid['sys_id'],
        ':employee_id'       => $data['employee_id'],
        ':employee_id'       => $data['employee_name'],
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
        ':status'            => $data['status'] ?? 'active',
        ':meta_data'         => $metaDataJson
    ]);

    $pdo->commit();

    jsonResponse(true, 'EPS structure created successfully', [
        'id' => $pdo->lastInsertId(),
        'eps_uuid' => $uuid,
        'salary_summary' => [
            'gross_salary'     => $gross_salary,
            'total_deductions' => $total_deductions,
            'net_salary'       => $net_salary
        ]
    ]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    jsonResponse(false, $e->getMessage());
}
