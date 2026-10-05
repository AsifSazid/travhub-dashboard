<?php
/**
 * FILE PATH: /api/visa-services/endpoints.php
 *
 * Visa Service Module — work-level, action-based routing
 *
 * GET  actions:
 *   ?action=get&work_sys_id=...          → full visa_services record
 *   ?action=get_master&sys_id=...        → master_visa_services detail (for display)
 *   ?action=search_master                → search master_visa_services
 *     [&country_sys_id=&visa_type_sys_id=&visa_category_sys_id=&q=]
 *
 * POST actions (JSON body):
 *   action=init                          → create visa_services row
 *   action=set_master                    → set/change master_visa_sys_id + update meta_data
 *   action=update_travelers              → replace full vs_travelers JSON
 *   action=update_cover_letter           → update one traveler's cover_letter field
 *   action=submit_application            → status → submitted, record submitted_at
 *   action=reopen                        → status → in_progress (undo submit)
 */

ob_start();
session_start();
date_default_timezone_set('Asia/Dhaka');

ini_set('display_errors', 0);
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    ob_clean(); http_response_code(500);
    echo json_encode(['status'=>'error','message'=>"PHP Error: $errstr in $errfile:$errline"]);
    exit;
});
register_shutdown_function(function() {
    $e = error_get_last();
    if ($e && in_array($e['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        ob_clean(); http_response_code(500);
        echo json_encode(['status'=>'error','message'=>"Fatal: {$e['message']} in {$e['file']}:{$e['line']}"]);
    }
});

require_once '../../server/api_bootstrap.php';
require_once '../../server/db_connection.php';
require_once '../../server/sys_id_generator_v2.php';

$method   = $_SERVER['REQUEST_METHOD'];
$action   = $_GET['action'] ?? '';
$userName = $_SESSION['user_name'] ?? 'system';

$body = [];
if ($method === 'POST') {
    $raw  = file_get_contents('php://input');
    $body = json_decode($raw, true) ?? [];
    $action = $body['action'] ?? $action;
}

function vsJson(array $data, int $code = 200): void {
    ob_clean();
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
}

// ── Fetch one visa_services row ────────────────────────────
function _vsFetch(PDO $pdo, string $workSysId): ?array
{
    $s = $pdo->prepare("SELECT * FROM visa_services WHERE work_sys_id = ? LIMIT 1");
    $s->execute([$workSysId]);
    $row = $s->fetch(PDO::FETCH_ASSOC);
    if (!$row) return null;
    $row['vs_travelers'] = $row['vs_travelers'] ? json_decode($row['vs_travelers'], true) : [];
    $row['meta_data']    = $row['meta_data']    ? json_decode($row['meta_data'],    true) : null;
    return $row;
}

// ── Fetch master visa service ──────────────────────────────
function _fetchMaster(PDO $pdo, string $sysId): ?array
{
    $s = $pdo->prepare("
        SELECT mvs.*,
               vt.name AS visa_type_name,
               vc.name AS visa_category_name
        FROM master_visa_services mvs
        LEFT JOIN visa_types vt ON vt.sys_id = mvs.visa_type_sys_id
        LEFT JOIN visa_categories vc ON vc.sys_id = mvs.visa_category_sys_id
        WHERE mvs.sys_id = ?
        LIMIT 1
    ");
    $s->execute([$sysId]);
    $row = $s->fetch(PDO::FETCH_ASSOC);
    if (!$row) return null;
    $row['required_documents'] = $row['required_documents'] ? json_decode($row['required_documents'], true) : [];
    $row['description']        = $row['description']        ? json_decode($row['description'],        true) : [];
    return $row;
}

try {

    switch ($action) {

        /* ── GET full row ──────────────────────────────────── */
        case 'get':
            $workSysId = $_GET['work_sys_id'] ?? '';
            if (!$workSysId) throw new Exception('work_sys_id required');
            $row = _vsFetch($pdo, $workSysId);
            vsJson(['status' => 'success', 'data' => $row]);
            break;

        /* ── GET master service detail ─────────────────────── */
        case 'get_master':
            $sysId = $_GET['sys_id'] ?? '';
            if (!$sysId) throw new Exception('sys_id required');
            $row = _fetchMaster($pdo, $sysId);
            if (!$row) throw new Exception('Master visa service not found');
            vsJson(['status' => 'success', 'data' => $row]);
            break;

        /* ── SEARCH master visa services ───────────────────── */
        case 'search_master':
            $where   = ['mvs.status = 1'];
            $params  = [];

            if (!empty($_GET['country_sys_id'])) {
                $where[]  = 'mvs.country_sys_id = ?';
                $params[] = $_GET['country_sys_id'];
            }
            if (!empty($_GET['visa_type_sys_id'])) {
                $where[]  = 'mvs.visa_type_sys_id = ?';
                $params[] = $_GET['visa_type_sys_id'];
            }
            if (!empty($_GET['visa_category_sys_id'])) {
                $where[]  = 'mvs.visa_category_sys_id = ?';
                $params[] = $_GET['visa_category_sys_id'];
            }
            if (!empty($_GET['q'])) {
                $where[]  = '(mvs.title LIKE ? OR mvs.country_name LIKE ?)';
                $like     = '%' . $_GET['q'] . '%';
                $params[] = $like;
                $params[] = $like;
            }

            $whereStr = implode(' AND ', $where);
            $stmt = $pdo->prepare("
                SELECT mvs.sys_id, mvs.title, mvs.country_sys_id, mvs.country_name,
                       mvs.visa_type_sys_id, mvs.visa_category_sys_id,
                       vt.name AS visa_type_name,
                       vc.name AS visa_category_name,
                       mvs.duration_days, mvs.duration_label,
                       mvs.b2c_price, mvs.purchase_price, mvs.currency,
                       mvs.file_size_limit_kb
                FROM master_visa_services mvs
                LEFT JOIN visa_types vt ON vt.sys_id = mvs.visa_type_sys_id
                LEFT JOIN visa_categories vc ON vc.sys_id = mvs.visa_category_sys_id
                WHERE {$whereStr}
                ORDER BY mvs.country_name ASC, mvs.title ASC
                LIMIT 50
            ");
            $stmt->execute($params);
            vsJson(['status' => 'success', 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
            break;

        /* ── INIT: create visa_services row ────────────────── */
        case 'init':
            $workSysId = $body['work_sys_id'] ?? '';
            if (!$workSysId) throw new Exception('work_sys_id required');

            // Check duplicate
            $chk = $pdo->prepare("SELECT id FROM visa_services WHERE work_sys_id = ? LIMIT 1");
            $chk->execute([$workSysId]);
            if ($chk->fetchColumn()) {
                $row = _vsFetch($pdo, $workSysId);
                vsJson(['status' => 'success', 'message' => 'Already exists', 'data' => $row]);
                break;
            }

            $ids = generateV2IDs($pdo, 'visa_services');
            $pdo->prepare("
                INSERT INTO visa_services (uuid, sys_id, work_sys_id, vs_travelers, meta_data, status)
                VALUES (?, ?, ?, '[]', NULL, 'pending')
            ")->execute([$ids['uuid'], $ids['sys_id'], $workSysId]);

            $row = _vsFetch($pdo, $workSysId);
            vsJson(['status' => 'success', 'message' => 'Initialized', 'data' => $row]);
            break;

        /* ── SET_MASTER: select/change master visa + snapshot meta ── */
        case 'set_master':
            $workSysId     = $body['work_sys_id']      ?? '';
            $masterSysId   = $body['master_visa_sys_id'] ?? '';
            if (!$workSysId)   throw new Exception('work_sys_id required');
            if (!$masterSysId) throw new Exception('master_visa_sys_id required');

            $master = _fetchMaster($pdo, $masterSysId);
            if (!$master) throw new Exception('Master visa service not found');

            $meta = json_encode([
                'master_visa_sys_id'  => $master['sys_id'],
                'master_visa_title'   => $master['title'],
                'country_sys_id'      => $master['country_sys_id'],
                'country_name'        => $master['country_name'],
                'visa_type_sys_id'    => $master['visa_type_sys_id'],
                'visa_type_name'      => $master['visa_type_name'],
                'visa_category_sys_id'=> $master['visa_category_sys_id'],
                'visa_category_name'  => $master['visa_category_name'],
                'duration_days'       => $master['duration_days'],
                'duration_label'      => $master['duration_label'],
                'b2c_price'           => $master['b2c_price'],
                'purchase_price'      => $master['purchase_price'],
                'currency'            => $master['currency'],
                'file_size_limit_kb'  => $master['file_size_limit_kb'],
                'required_documents'  => $master['required_documents'],
            ], JSON_UNESCAPED_UNICODE);

            // Ensure row exists
            $chk = $pdo->prepare("SELECT id FROM visa_services WHERE work_sys_id = ? LIMIT 1");
            $chk->execute([$workSysId]);
            if (!$chk->fetchColumn()) {
                $ids = generateV2IDs($pdo, 'visa_services');
                $pdo->prepare("
                    INSERT INTO visa_services (uuid, sys_id, work_sys_id, vs_travelers, meta_data, status)
                    VALUES (?, ?, ?, '[]', ?, 'pending')
                ")->execute([$ids['uuid'], $ids['sys_id'], $workSysId, $meta]);
            } else {
                $pdo->prepare("UPDATE visa_services SET meta_data = ? WHERE work_sys_id = ?")
                    ->execute([$meta, $workSysId]);
            }

            $row = _vsFetch($pdo, $workSysId);
            vsJson(['status' => 'success', 'message' => 'Master set', 'data' => $row]);
            break;

        /* ── UPDATE_TRAVELERS: full replace of vs_travelers JSON ─── */
        case 'update_travelers':
            $workSysId  = $body['work_sys_id']  ?? '';
            $travelers  = $body['vs_travelers'] ?? [];
            if (!$workSysId) throw new Exception('work_sys_id required');
            if (!is_array($travelers)) throw new Exception('vs_travelers must be array');

            $travJson = json_encode($travelers, JSON_UNESCAPED_UNICODE);
            $pdo->prepare("UPDATE visa_services SET vs_travelers = ? WHERE work_sys_id = ?")
                ->execute([$travJson, $workSysId]);

            $row = _vsFetch($pdo, $workSysId);
            vsJson(['status' => 'success', 'message' => 'Travelers updated', 'data' => $row]);
            break;

        /* ── UPDATE_COVER_LETTER: update one traveler's cover letter ─ */
        case 'update_cover_letter':
            $workSysId   = $body['work_sys_id']   ?? '';
            $travSysId   = $body['traveler_sys_id'] ?? '';
            $coverLetter = $body['cover_letter']   ?? null;
            if (!$workSysId)  throw new Exception('work_sys_id required');
            if (!$travSysId)  throw new Exception('traveler_sys_id required');

            $row = _vsFetch($pdo, $workSysId);
            if (!$row) throw new Exception('Visa service not found');

            $travelers = $row['vs_travelers'] ?? [];
            $found = false;
            foreach ($travelers as &$t) {
                if ($t['sys_id'] === $travSysId) {
                    $t['cover_letter'] = $coverLetter;
                    $found = true;
                    break;
                }
            }
            unset($t);
            if (!$found) throw new Exception('Traveler not found in vs_travelers');

            $pdo->prepare("UPDATE visa_services SET vs_travelers = ? WHERE work_sys_id = ?")
                ->execute([json_encode($travelers, JSON_UNESCAPED_UNICODE), $workSysId]);

            vsJson(['status' => 'success', 'message' => 'Cover letter updated']);
            break;

        /* ── SUBMIT_APPLICATION: mark submitted ─────────────── */
        case 'submit_application':
            $workSysId = $body['work_sys_id'] ?? '';
            if (!$workSysId) throw new Exception('work_sys_id required');

            $row = _vsFetch($pdo, $workSysId);
            if (!$row) throw new Exception('Visa service not found');
            if ($row['status'] === 'submitted')
                throw new Exception('Already submitted');

            $pdo->prepare("
                UPDATE visa_services
                SET status = 'submitted', submitted_at = NOW()
                WHERE work_sys_id = ?
            ")->execute([$workSysId]);

            $row = _vsFetch($pdo, $workSysId);
            vsJson([
                'status'  => 'success',
                'message' => 'Application submitted',
                'data'    => $row,
                // meta for JS to trigger financial entries
                'meta'    => $row['meta_data'],
                'traveler_count' => count($row['vs_travelers'] ?? []),
            ]);
            break;

        /* ── REOPEN: un-submit ───────────────────────────────── */
        case 'reopen':
            $workSysId = $body['work_sys_id'] ?? '';
            if (!$workSysId) throw new Exception('work_sys_id required');
            $pdo->prepare("
                UPDATE visa_services
                SET status = 'in_progress', submitted_at = NULL
                WHERE work_sys_id = ?
            ")->execute([$workSysId]);
            vsJson(['status' => 'success', 'message' => 'Reopened']);
            break;

        default:
            throw new Exception("Unknown action: '{$action}'");
    }

} catch (Exception $e) {
    ob_clean();
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}