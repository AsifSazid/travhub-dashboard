<?php
/**
 * FILE PATH: /api/global-search.php
 * GET ?q=SEARCH_TERM&module=leads|works|tasks|clients|travelers|employees
 *
 * Returns: { rows: [...] }
 * Each module searches ALL its text/varchar/JSON columns.
 * Called once per module — fires concurrently from the search page.
 */

session_start();
date_default_timezone_set('Asia/Dhaka');
require_once '../server/db_connection.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$q      = trim($_GET['q']      ?? '');
$module = trim($_GET['module'] ?? '');

if (strlen($q) < 1 || !$module) {
    echo json_encode(['rows' => []]);
    exit;
}

$like = '%' . $q . '%';
$rows = [];

try {

    switch ($module) {

        /* ── Leads ──────────────────────────────────────────── */
        case 'leads':
            $s = $pdo->prepare("
                SELECT
                    sys_id, lead_status,
                    JSON_UNQUOTE(JSON_EXTRACT(client_info,'$.client_name')) AS client_name,
                    JSON_UNQUOTE(JSON_EXTRACT(lead_info,'$.source'))        AS source,
                    service_type, ai_prompt, voice_transcript, instruction, special_instruction
                FROM leads
                WHERE
                    sys_id                                                   LIKE ?
                 OR JSON_UNQUOTE(JSON_EXTRACT(client_info,'$.client_name')) LIKE ?
                 OR JSON_UNQUOTE(JSON_EXTRACT(client_info,'$.client_sys_id')) LIKE ?
                 OR JSON_UNQUOTE(JSON_EXTRACT(lead_info,'$.source'))         LIKE ?
                 OR lead_status                                               LIKE ?
                 OR service_type                                              LIKE ?
                 OR ai_prompt                                                 LIKE ?
                 OR voice_transcript                                          LIKE ?
                 OR instruction                                               LIKE ?
                 OR special_instruction                                       LIKE ?
                ORDER BY id DESC LIMIT 30
            ");
            $s->execute(array_fill(0, 10, $like));
            foreach ($s->fetchAll(PDO::FETCH_ASSOC) as $r) {
                $svcArr = json_decode($r['service_type'] ?? '[]', true);
                $r['service_type_label'] = is_array($svcArr) ? implode(', ', array_map(fn($v) => ucfirst(str_replace('_',' ',$v)), $svcArr)) : '';
                unset($r['service_type'], $r['ai_prompt'], $r['voice_transcript'], $r['instruction'], $r['special_instruction']);
                $rows[] = $r;
            }
            break;

        /* ── Works ──────────────────────────────────────────── */
        case 'works':
            $s = $pdo->prepare("
                SELECT
                    sys_id, lead_sys_id, work_status, assigned_to,
                    JSON_UNQUOTE(JSON_EXTRACT(client_info,'$.client_name')) AS client_name,
                    service_type, instruction, special_instruction
                FROM works
                WHERE
                    sys_id                                                   LIKE ?
                 OR lead_sys_id                                              LIKE ?
                 OR JSON_UNQUOTE(JSON_EXTRACT(client_info,'$.client_name')) LIKE ?
                 OR JSON_UNQUOTE(JSON_EXTRACT(client_info,'$.client_sys_id')) LIKE ?
                 OR work_status                                               LIKE ?
                 OR assigned_to                                               LIKE ?
                 OR service_type                                              LIKE ?
                 OR instruction                                               LIKE ?
                 OR special_instruction                                       LIKE ?
                ORDER BY id DESC LIMIT 30
            ");
            $s->execute(array_fill(0, 9, $like));
            foreach ($s->fetchAll(PDO::FETCH_ASSOC) as $r) {
                $svcArr = json_decode($r['service_type'] ?? '[]', true);
                $r['service_type_label'] = is_array($svcArr) ? implode(', ', array_map(fn($v) => ucfirst(str_replace('_',' ',$v)), $svcArr)) : '';
                unset($r['service_type'], $r['instruction'], $r['special_instruction']);
                $rows[] = $r;
            }
            break;
            
        /* ── Works ──────────────────────────────────────────── */
        case 'com_works':
            $s = $pdo->prepare("
                SELECT
                    sys_id,
                    title,
                    client_sys_id,
                    client_name,
                    owned_by
                FROM com_works
                WHERE
                    sys_id                                                   LIKE ?
                 OR client_sys_id                                            LIKE ?
                 OR client_name                                              LIKE ?
                 OR owned_by                                                 LIKE ?
                ORDER BY id DESC LIMIT 30
            ");
            $s->execute(array_fill(0, 4, $like));
            foreach ($s->fetchAll(PDO::FETCH_ASSOC) as $r) {
                $svcArr = json_decode($r['service_type'] ?? '[]', true);
                $r['service_type_label'] = is_array($svcArr) ? implode(', ', array_map(fn($v) => ucfirst(str_replace('_',' ',$v)), $svcArr)) : '';
                unset($r['service_type'], $r['instruction'], $r['special_instruction']);
                $rows[] = $r;
            }
            break;

        /* ── Tasks ──────────────────────────────────────────── */
        case 'tasks':
            $s = $pdo->prepare("
                SELECT
                    sys_id, work_sys_id, client_sys_id, client_name,
                    workname, assigned_to, status, overall_status,
                    notes, instruction, special_ins, plans
                FROM tasks
                WHERE
                    sys_id         LIKE ?
                 OR work_sys_id    LIKE ?
                 OR client_sys_id  LIKE ?
                 OR client_name    LIKE ?
                 OR workname       LIKE ?
                 OR assigned_to    LIKE ?
                 OR status         LIKE ?
                 OR overall_status LIKE ?
                 OR notes          LIKE ?
                 OR instruction    LIKE ?
                 OR special_ins    LIKE ?
                 OR plans          LIKE ?
                ORDER BY id DESC LIMIT 30
            ");
            $s->execute(array_fill(0, 12, $like));
            foreach ($s->fetchAll(PDO::FETCH_ASSOC) as $r) {
                unset($r['notes'], $r['instruction'], $r['special_ins'], $r['plans']);
                $rows[] = $r;
            }
            break;

        /* ── Clients ─────────────────────────────────────────── */
        case 'clients':
            $s = $pdo->prepare("
                SELECT sys_id, name, type, phone, email, address, rep_name, rep_phone, status
                FROM clients
                WHERE
                    sys_id    LIKE ?
                 OR name      LIKE ?
                 OR type      LIKE ?
                 OR phone     LIKE ?
                 OR email     LIKE ?
                 OR address   LIKE ?
                 OR rep_name  LIKE ?
                 OR rep_phone LIKE ?
                ORDER BY id DESC LIMIT 30
            ");
            $s->execute(array_fill(0, 8, $like));
            foreach ($s->fetchAll(PDO::FETCH_ASSOC) as $r) {
                // Flatten phone JSON → readable string
                $ph = json_decode($r['phone'] ?? '{}', true);
                $r['phone_flat'] = is_array($ph) ? implode(', ', array_filter(array_values($ph))) : ($r['phone'] ?? '');
                unset($r['phone'], $r['email'], $r['address']);
                $rows[] = $r;
            }
            break;

        case 'vendors':
            $s = $pdo->prepare("
                SELECT sys_id, name, type, phone, email, status
                FROM vendors
                WHERE
                    sys_id    LIKE ?
                 OR name      LIKE ?
                 OR type      LIKE ?
                 OR phone     LIKE ?
                 OR email     LIKE ?
                ORDER BY id DESC LIMIT 30
            ");
            $s->execute(array_fill(0, 5, $like));
            foreach ($s->fetchAll(PDO::FETCH_ASSOC) as $r) {
                // Flatten phone JSON → readable string
                $ph = json_decode($r['phone'] ?? '{}', true);
                $r['phone_flat'] = is_array($ph) ? implode(', ', array_filter(array_values($ph))) : ($r['phone'] ?? '');
                unset($r['phone'], $r['email']);
                $rows[] = $r;
            }
            break;

        /* ── Travelers ───────────────────────────────────────── */
        case 'travelers':
            $s = $pdo->prepare("
                SELECT sys_id, name, passport_no, nid_no, phone, status,
                       date_of_birth
                FROM travelers
                WHERE
                    sys_id       LIKE ?
                 OR name         LIKE ?
                 OR passport_no  LIKE ?
                 OR nid_no       LIKE ?
                 OR phone        LIKE ?
                ORDER BY id DESC LIMIT 30
            ");
            $s->execute(array_fill(0, 5, $like));
            foreach ($s->fetchAll(PDO::FETCH_ASSOC) as $r) {
                $ph = json_decode($r['phone'] ?? '{}', true);
                $r['phone_flat'] = is_array($ph) ? ($ph['primary_no'] ?? implode(', ', array_filter(array_values($ph)))) : ($r['phone'] ?? '');
                unset($r['phone']);
                $rows[] = $r;
            }
            break;

        /* ── Employees ───────────────────────────────────────── */
        case 'employees':
            $s = $pdo->prepare("
                SELECT sys_id, name, type, department_name, phone, email, status
                FROM employees
                WHERE
                    sys_id          LIKE ?
                 OR name            LIKE ?
                 OR type            LIKE ?
                 OR department_name LIKE ?
                 OR phone           LIKE ?
                 OR email           LIKE ?
                ORDER BY id DESC LIMIT 30
            ");
            $s->execute(array_fill(0, 6, $like));
            foreach ($s->fetchAll(PDO::FETCH_ASSOC) as $r) {
                $ph = json_decode($r['phone'] ?? '{}', true);
                $r['phone_flat'] = is_array($ph) ? implode(', ', array_filter(array_values($ph))) : ($r['phone'] ?? '');
                unset($r['phone'], $r['email']);
                $rows[] = $r;
            }
            break;

        default:
            break;
    }

    echo json_encode(['rows' => $rows]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['rows' => [], 'error' => $e->getMessage()]);
}