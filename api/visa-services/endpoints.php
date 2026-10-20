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
 *   action=update_mb_notes               → patch mb_notes in meta_data (Mind Board)
 *   action=submit_application            → status → submitted, record submitted_at
 *   action=reopen                        → status → in_progress (undo submit)
 *   action=update_meta                   → patch arbitrary meta_data fields
 *   action=create_visa_task  (LFDP-21)  → create task row, set service_works.status=confirmed
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
require_once '../../server/generate_meta_data.php';

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

// ── Auto-create task for visa service (LFDP-21) ──────────
function _vsAutoCreateTask(PDO $pdo, string $workSysId, string $userName): string|false
{
    // Idempotent: return existing task if already created
    $chk = $pdo->prepare("
        SELECT t.sys_id FROM tasks t
        JOIN service_works sw ON sw.sys_id = t.service_work_sys_id
        WHERE sw.work_sys_id = ? AND sw.service_slug = 'visa'
        LIMIT 1
    ");
    $chk->execute([$workSysId]);
    if ($existing = $chk->fetchColumn()) return $existing;

    // Fetch work + client info
    $ws = $pdo->prepare("SELECT client_info, work_name FROM works WHERE sys_id = ? LIMIT 1");
    $ws->execute([$workSysId]);
    $work = $ws->fetch(PDO::FETCH_ASSOC);
    if (!$work) return false;

    $ci          = json_decode($work['client_info'], true) ?? [];
    $clientName  = $ci['name']   ?? 'Unknown';
    $clientSysId = $ci['sys_id'] ?? null;

    // Get service_works row for 'visa'
    $sw = $pdo->prepare("SELECT sys_id FROM service_works WHERE work_sys_id = ? AND service_slug = 'visa' LIMIT 1");
    $sw->execute([$workSysId]);
    $swSysId = $sw->fetchColumn() ?: null;

    // Fetch visa meta for task name
    $vs = $pdo->prepare("SELECT meta_data FROM visa_services WHERE work_sys_id = ? LIMIT 1");
    $vs->execute([$workSysId]);
    $vsRow  = $vs->fetch(PDO::FETCH_ASSOC);
    $vsMeta = $vsRow ? (json_decode($vsRow['meta_data'], true) ?? []) : [];

    $countryName = $vsMeta['country_name']       ?? '';
    $visaTitle   = $vsMeta['master_visa_title']  ?? ($countryName ? "$countryName Visa" : 'Visa Submission');
    $travCount   = 0;
    if ($vsRow) {
        $trav = $pdo->prepare("SELECT JSON_LENGTH(vs_travelers) FROM visa_services WHERE work_sys_id = ?");
        $trav->execute([$workSysId]);
        $travCount = (int)($trav->fetchColumn() ?? 0);
    }

    $taskName = $visaTitle . ($travCount > 0 ? " ({$travCount} traveler" . ($travCount > 1 ? 's' : '') . ')' : '');

    $ids  = generateV2IDs($pdo, 'tasks');
    $meta = buildMetaData(null, $userName);

    $pdo->prepare("
        INSERT INTO tasks
            (uuid, sys_id, service_work_sys_id, work_sys_id, client_sys_id, client_name, workname,
             status, overall_status, service_slug, meta_data)
        VALUES
            (?, ?, ?, ?, ?, ?, ?, 'open', 'pending', 'visa', ?)
    ")->execute([
        $ids['uuid'], $ids['sys_id'],
        $swSysId, $workSysId, $clientSysId,
        $clientName, $taskName, $meta,
    ]);

    // Update service_works status → confirmed
    if ($swSysId) {
        $swMeta = buildMetaData(null, $userName);
        $pdo->prepare("UPDATE service_works SET status = 'confirmed', meta_data = ? WHERE sys_id = ?")
            ->execute([$swMeta, $swSysId]);
    }

    // Store task_sys_id back into visa_services.meta_data for UI reference
    $existingMeta = $vsMeta;
    $existingMeta['task_sys_id'] = $ids['sys_id'];
    $pdo->prepare("UPDATE visa_services SET meta_data = ? WHERE work_sys_id = ?")
        ->execute([json_encode($existingMeta, JSON_UNESCAPED_UNICODE), $workSysId]);

    return $ids['sys_id'];
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

            // country name search (used by create-leads dropdown)
            if (!empty($_GET['country']) || !empty($_GET['countryName'])) {
                $cn = trim($_GET['country'] ?? $_GET['countryName'] ?? '');
                if ($cn) {
                    $where[]  = 'mvs.country_name LIKE ?';
                    $params[] = '%' . $cn . '%';
                }
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
            $workSysId     = $body['work_sys_id']        ?? '';
            $masterSysId   = $body['master_visa_sys_id'] ?? '';
            if (!$workSysId)   throw new Exception('work_sys_id required');
            if (!$masterSysId) throw new Exception('master_visa_sys_id required');

            $master = _fetchMaster($pdo, $masterSysId);
            if (!$master) throw new Exception('Master visa service not found');

            // Preserve existing meta fields (mb_notes, task_sys_id etc.)
            $existing = _vsFetch($pdo, $workSysId);
            $preserve = [];
            if ($existing && is_array($existing['meta_data'])) {
                foreach (['mb_notes', 'task_sys_id'] as $k) {
                    if (isset($existing['meta_data'][$k])) $preserve[$k] = $existing['meta_data'][$k];
                }
            }

            $meta = json_encode(array_merge($preserve, [
                'master_visa_sys_id'   => $master['sys_id'],
                'master_visa_title'    => $master['title'],
                'country_sys_id'       => $master['country_sys_id'],
                'country_name'         => $master['country_name'],
                'visa_type_sys_id'     => $master['visa_type_sys_id'],
                'visa_type_name'       => $master['visa_type_name'],
                'visa_category_sys_id' => $master['visa_category_sys_id'],
                'visa_category_name'   => $master['visa_category_name'],
                'duration_days'        => $master['duration_days'],
                'duration_label'       => $master['duration_label'],
                'b2c_price'            => $master['b2c_price'],
                'purchase_price'       => $master['purchase_price'],
                'currency'             => $master['currency'],
                'file_size_limit_kb'   => $master['file_size_limit_kb'],
                'required_documents'   => $master['required_documents'],
            ]), JSON_UNESCAPED_UNICODE);

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

        /* ── UPDATE_META: patch meta_data fields ──────────────── */
        case 'update_meta':
            $wid  = $body['work_sys_id'] ?? '';
            if (!$wid) throw new Exception('work_sys_id required');
            $row  = _vsFetch($pdo, $wid);
            if (!$row) throw new Exception('Visa service not found');
            $meta = array_merge($row['meta_data'] ?? [], $body['meta_data'] ?? []);
            $pdo->prepare("UPDATE visa_services SET meta_data = ? WHERE work_sys_id = ?")
                ->execute([json_encode($meta, JSON_UNESCAPED_UNICODE), $wid]);
            vsJson(['status' => 'success', 'message' => 'Meta updated', 'data' => _vsFetch($pdo, $wid)]);
            break;

        /* ── UPDATE_MB_NOTES: save Mind Board notes ─────────── */
        case 'update_mb_notes':
            $wid     = $body['work_sys_id'] ?? '';
            $mbNotes = $body['mb_notes']    ?? '';
            if (!$wid) throw new Exception('work_sys_id required');
            $row  = _vsFetch($pdo, $wid);
            if (!$row) throw new Exception('Visa service not found');
            $meta = array_merge($row['meta_data'] ?? [], ['mb_notes' => $mbNotes]);
            $pdo->prepare("UPDATE visa_services SET meta_data = ? WHERE work_sys_id = ?")
                ->execute([json_encode($meta, JSON_UNESCAPED_UNICODE), $wid]);
            vsJson(['status' => 'success', 'message' => 'MB notes saved']);
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

        /* ── UPDATE_COVER_LETTER ─────────────────────────────── */
        case 'update_cover_letter':
            $workSysId   = $body['work_sys_id']     ?? '';
            $travSysId   = $body['traveler_sys_id'] ?? '';
            $coverLetter = $body['cover_letter']    ?? null;
            if (!$workSysId) throw new Exception('work_sys_id required');
            if (!$travSysId) throw new Exception('traveler_sys_id required');

            $row = _vsFetch($pdo, $workSysId);
            if (!$row) throw new Exception('Visa service not found');

            $travelers = $row['vs_travelers'] ?? [];
            $found = false;
            foreach ($travelers as &$t) {
                if ($t['sys_id'] === $travSysId) {
                    $t['cover_letter'] = $coverLetter;
                    $found = true; break;
                }
            }
            unset($t);
            if (!$found) throw new Exception('Traveler not found in vs_travelers');

            $pdo->prepare("UPDATE visa_services SET vs_travelers = ? WHERE work_sys_id = ?")
                ->execute([json_encode($travelers, JSON_UNESCAPED_UNICODE), $workSysId]);

            vsJson(['status' => 'success', 'message' => 'Cover letter updated']);
            break;

        /* ── SUBMIT_APPLICATION ──────────────────────────────── */
        case 'submit_application':
            $workSysId = $body['work_sys_id'] ?? '';
            if (!$workSysId) throw new Exception('work_sys_id required');

            $row = _vsFetch($pdo, $workSysId);
            if (!$row) throw new Exception('Visa service not found');
            if ($row['status'] === 'submitted') throw new Exception('Already submitted');

            $pdo->prepare("
                UPDATE visa_services
                SET status = 'submitted', submitted_at = NOW()
                WHERE work_sys_id = ?
            ")->execute([$workSysId]);

            $row = _vsFetch($pdo, $workSysId);
            vsJson([
                'status'         => 'success',
                'message'        => 'Application submitted',
                'data'           => $row,
                'meta'           => $row['meta_data'],
                'traveler_count' => count($row['vs_travelers'] ?? []),
            ]);
            break;

        /* ── REOPEN ──────────────────────────────────────────── */
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

        /* ── CREATE_VISA_TASK (LFDP-21) ──────────────────────
         *
         * Creates a tasks row linked to the visa service_works row.
         * Sets service_works.status = 'confirmed'.
         * Stores task_sys_id back into visa_services.meta_data.
         * Idempotent — returns existing task_sys_id if already created.
         * Financial entries are posted by the JS layer after this call.
         */
        case 'create_visa_task':
            $workSysId = $body['work_sys_id'] ?? '';
            if (!$workSysId) throw new Exception('work_sys_id required');

            $row = _vsFetch($pdo, $workSysId);
            if (!$row) throw new Exception('Visa service not found');

            // Check travelers are actually ready
            $travelers   = $row['vs_travelers'] ?? [];
            $readyCount  = 0;
            foreach ($travelers as $t) {
                $s = $t['app_status'] ?? 'pending';
                if (in_array($s, ['ready', 'submitted', 'approved'], true)) $readyCount++;
            }
            if (count($travelers) === 0)   throw new Exception('কোনো traveler নেই');
            if ($readyCount < count($travelers)) {
                throw new Exception("সব travelers ready নয় ({$readyCount}/" . count($travelers) . ")");
            }

            $taskSysId = _vsAutoCreateTask($pdo, $workSysId, $userName);
            if (!$taskSysId) throw new Exception('Task creation failed — work not found');

            vsJson([
                'status'     => 'success',
                'task_sys_id'=> $taskSysId,
                'work_sys_id'=> $workSysId,
                'message'    => 'Task created successfully',
            ]);
            break;

        default:
            throw new Exception("Unknown action: '{$action}'");
    }

} catch (Exception $e) {
    ob_clean();
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}