<?php
/**
 * FILE PATH: api/travelers/get_info.php
 *
 * Information tab এর জন্য traveler এর সব data একসাথে return করো।
 * দুটো source থেকে data আসে:
 *   1. travelers table — manually filled columns
 *   2. traveler_documents table — AI extracted doc_data
 *
 * INPUT (GET):
 *   traveler_id — travelers.sys_id
 *
 * OUTPUT (JSON):
 *   success, traveler_id, name,
 *   personal_info, family_info, employment_info,
 *   educational_info, work_info, others_info,
 *   passport_info, nid_info,
 *   documents_data { passport, nid, visa[], bank_statement[], employment_letter[], ... }
 */

require_once '../../server/api_bootstrap.php';
require_once '../../server/db_connection.php';

$travelerSysId = trim($_GET['traveler_id'] ?? '');

if (!$travelerSysId) {
    jsonOut(['success' => false, 'message' => 'traveler_id is required']);
}

// ── 1. travelers table ────────────────────────────────────────────────────────
$stmt = $pdo->prepare("
    SELECT
        sys_id, name,
        personal_info, family_info, employment_info,
        educational_info, work_info, others_info,
        passport_info, nid_info
    FROM travelers
    WHERE sys_id = ?
    LIMIT 1
");
$stmt->execute([$travelerSysId]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$row) {
    jsonOut(['success' => false, 'message' => 'Traveler not found']);
}

$jsonCols = [
    'personal_info', 'family_info', 'employment_info',
    'educational_info', 'work_info', 'others_info',
    'passport_info', 'nid_info',
];
foreach ($jsonCols as $col) {
    $row[$col] = json_decode($row[$col] ?? 'null', true) ?? [];
}

// ── 2. traveler_documents table — AI extracted data ───────────────────────────
$docStmt = $pdo->prepare("
    SELECT doc_type, doc_number, issue_date, expiry_date,
           doc_data, summary, passport_status, country,
           validity_from, validity_to, is_primary, status
    FROM traveler_documents
    WHERE traveler_id = ? AND status != 'deleted'
    ORDER BY is_primary DESC, created_at DESC
");
$docStmt->execute([$travelerSysId]);
$docs = $docStmt->fetchAll(PDO::FETCH_ASSOC);

// doc_data decode + doc_type অনুযায়ী group করো
$documentsData = buildDocumentsData($docs);

// ── 3. Merge — travelers columns empty হলে documents data দিয়ে fill করো ──────
$merged = mergeWithDocuments($row, $documentsData);

jsonOut([
    'success'          => true,
    'traveler_id'      => $row['sys_id'],
    'name'             => $row['name'],
    'personal_info'    => $merged['personal_info'],
    'family_info'      => $merged['family_info'],
    'employment_info'  => $merged['employment_info'],
    'educational_info' => $merged['educational_info'],
    'work_info'        => $merged['work_info'],
    'others_info'      => $merged['others_info'],
    'passport_info'    => $merged['passport_info'],
    'nid_info'         => $merged['nid_info'],
    'documents_data'   => $documentsData,
]);


// ════════════════════════════════════════════════════════════════════════════
// buildDocumentsData — doc_type অনুযায়ী group করো
// ════════════════════════════════════════════════════════════════════════════
function buildDocumentsData(array $docs): array
{
    $out = [
        'passport'            => null,
        'previous_passports'  => [],
        'nid'                 => null,
        'visa'                => [],
        'visa_stamp'          => [],
        'air_ticket'          => [],
        'hotel_voucher'       => [],
        'bank_statement'      => [],
        'sponsor_letter'      => [],
        'employment_letter'   => [],
        'education_certificate' => [],
        'medical_report'      => [],
        'vaccination_card'    => [],
        'marriage_certificate'=> [],
        'birth_certificate'   => [],
        'other'               => [],
    ];

    foreach ($docs as $doc) {
        $docData = json_decode($doc['doc_data'] ?? 'null', true) ?? [];

        $entry = [
            'doc_number'     => $doc['doc_number'],
            'issue_date'     => $doc['issue_date'],
            'expiry_date'    => $doc['expiry_date'],
            'validity_from'  => $doc['validity_from'],
            'validity_to'    => $doc['validity_to'],
            'country'        => $doc['country'],
            'summary'        => $doc['summary'],
            'passport_status'=> $doc['passport_status'],
            'is_primary'     => (bool)$doc['is_primary'],
            'doc_data'       => $docData,
        ];

        $type = $doc['doc_type'];

        switch ($type) {
            case 'passport':
                if ($doc['passport_status'] === 'current' || $out['passport'] === null) {
                    if ($out['passport'] !== null) {
                        // পুরানো current টা previous এ নামাও
                        $out['previous_passports'][] = $out['passport'];
                    }
                    $out['passport'] = $entry;
                } else {
                    $out['previous_passports'][] = $entry;
                }
                break;

            case 'nid':
                if ($out['nid'] === null) {
                    $out['nid'] = $entry;
                }
                break;

            case 'visa':
            case 'visa_stamp':
            case 'air_ticket':
            case 'hotel_voucher':
            case 'bank_statement':
            case 'sponsor_letter':
            case 'employment_letter':
            case 'education_certificate':
            case 'medical_report':
            case 'vaccination_card':
            case 'marriage_certificate':
            case 'birth_certificate':
                $out[$type][] = $entry;
                break;

            default:
                $out['other'][] = $entry;
        }
    }

    return $out;
}


// ════════════════════════════════════════════════════════════════════════════
// mergeWithDocuments — travelers columns empty হলে documents data দিয়ে fill
// ════════════════════════════════════════════════════════════════════════════
function mergeWithDocuments(array $row, array $docs): array
{
    // ── passport_info ─────────────────────────────────────────────────────────
    // travelers.passport_info empty হলে documents এর passport doc_data দিয়ে fill
    if (empty($row['passport_info']) && !empty($docs['passport'])) {
        $bio = $docs['passport']['doc_data'] ?? [];
        if ($bio) {
            $row['passport_info'] = [[
                'page_type' => 'current',
                'doc_number'=> $docs['passport']['doc_number'] ?? '',
                'bio_info'  => $bio,
                '_metadata' => ['source' => 'traveler_documents'],
            ]];
        }
    }

    // ── nid_info ──────────────────────────────────────────────────────────────
    if (empty($row['nid_info']) && !empty($docs['nid'])) {
        $nidData = $docs['nid']['doc_data'] ?? [];
        if ($nidData) {
            $row['nid_info'] = [[
                'page_type' => 'primary',
                'doc_number'=> $docs['nid']['doc_number'] ?? '',
                'bio_info'  => $nidData,
                '_metadata' => ['source' => 'traveler_documents'],
            ]];
        }
    }

    // ── personal_info ─────────────────────────────────────────────────────────
    // passport doc_data থেকে personal fields নাও
    if (empty($row['personal_info']) && !empty($docs['passport'])) {
        $bio = $docs['passport']['doc_data'] ?? [];
        $row['personal_info'] = array_filter([
            'pp_given_name'       => $bio['given_names']       ?? '',
            'pp_family_name'      => $bio['surname']           ?? '',
            'pp_gender'           => isset($bio['gender'])
                ? ($bio['gender'] === 'M' ? 'male' : ($bio['gender'] === 'F' ? 'female' : $bio['gender']))
                : '',
            'pp_dob'              => formatToInputDate($bio['date_of_birth'] ?? ''),
            'pp_pob'              => $bio['place_of_birth']    ?? '',
            'pp_number'           => $bio['passport_number']   ?? ($docs['passport']['doc_number'] ?? ''),
            'pp_issue_date'       => formatToInputDate($docs['passport']['issue_date'] ?? ''),
            'pp_expiry_date'      => formatToInputDate($docs['passport']['expiry_date'] ?? ''),
            'pp_issuing_authority'=> $bio['issuing_authority'] ?? '',
        ]);
    }

    // ── employment_info ───────────────────────────────────────────────────────
    // employment_letter doc_data থেকে নাও
    if (empty($row['employment_info']) && !empty($docs['employment_letter'])) {
        $emp = $docs['employment_letter'][0]['doc_data'] ?? [];
        if ($emp) {
            $row['employment_info'] = array_filter([
                'employmentStatus' => 'employed',
                'jobTitle'         => $emp['designation']    ?? '',
                'employer'         => $emp['employer_name']  ?? '',
                'issue_date'       => formatToInputDate($emp['issue_date'] ?? ''),
            ]);
        }
    }

    // ── educational_info ──────────────────────────────────────────────────────
    if (empty($row['educational_info']) && !empty($docs['education_certificate'])) {
        $row['educational_info'] = array_map(function($cert) {
            $d = $cert['doc_data'] ?? [];
            return array_filter([
                'name'           => $d['institution_name'] ?? '',
                'course'         => $d['course']           ?? '',
                'attendanceFrom' => formatToInputDate($d['from_date'] ?? ''),
                'attendanceTo'   => formatToInputDate($d['to_date']   ?? ''),
            ]);
        }, $docs['education_certificate']);
    }

    return $row;
}


// ════════════════════════════════════════════════════════════════════════════
// HELPERS
// ════════════════════════════════════════════════════════════════════════════

// DD-MM-YYYY বা YYYY-MM-DD → YYYY-MM-DD (HTML date input format)
function formatToInputDate(string $raw): string
{
    $raw = trim($raw);
    if (!$raw) return '';
    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $raw)) return $raw;
    if (preg_match('/^(\d{2})-(\d{2})-(\d{4})$/', $raw, $m)) {
        return "{$m[3]}-{$m[2]}-{$m[1]}";
    }
    return '';
}

function jsonOut(array $data): never
{
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}