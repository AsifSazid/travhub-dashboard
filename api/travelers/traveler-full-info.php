<?php
/**
 * FILE PATH: api/travelers/traveler-full-info.php
 *
 * Work-এর ভেতরকার tab (ww-air-ticket.js ইত্যাদি) থেকে কল করা হয় — কোনো
 * linked traveler-এর **live** passport তথ্য (works.traveler_sys_ids-এর
 * সীমিত snapshot না) + actual passport page image URL(s) ফেরত দেয়।
 *
 * শুধু doc_type='passport' AND passport_status='current' AND status='active'
 * document খোঁজে — renewal হয়ে থাকলেও সবসময় সর্বশেষ current passport-ই আসবে।
 *
 * INPUT (GET): traveler_sys_id
 * OUTPUT: { success, traveler: { sys_id, name, bio_info: {...সব field...},
 *           images: [{page_no, url}], doc_number, expiry_date, issue_date } }
 */

require '../../server/db_connection.php';
header('Content-Type: application/json');
ini_set('display_errors', 0);

$travelerSysId = trim($_GET['traveler_sys_id'] ?? '');
if (!$travelerSysId) {
    jsonOut(['success' => false, 'message' => 'traveler_sys_id প্রয়োজন']);
}

try {
    $tStmt = $pdo->prepare("SELECT sys_id, name FROM travelers WHERE sys_id = ? LIMIT 1");
    $tStmt->execute([$travelerSysId]);
    $traveler = $tStmt->fetch(PDO::FETCH_ASSOC);
    if (!$traveler) jsonOut(['success' => false, 'message' => 'Traveler পাওয়া যায়নি']);

    $dStmt = $pdo->prepare("
        SELECT sys_id, doc_number, issue_date, expiry_date, doc_data, pages
        FROM traveler_documents
        WHERE traveler_id = ? AND doc_type = 'passport'
          AND passport_status = 'current' AND status = 'active'
        ORDER BY created_at DESC LIMIT 1
    ");
    $dStmt->execute([$travelerSysId]);
    $doc = $dStmt->fetch(PDO::FETCH_ASSOC);

    if (!$doc) {
        // কোনো current passport document নেই — traveler নাম-টুকুই ফেরত,
        // ww-air-ticket.js তখন "No passport document" দেখাতে পারবে
        jsonOut(['success' => true, 'traveler' => [
            'sys_id' => $traveler['sys_id'], 'name' => $traveler['name'],
            'bio_info' => null, 'images' => [], 'doc_number' => null,
        ]]);
    }

    // doc_data-তে Gemini-এর raw bio_info থাকে (commit-documents.php এর
    // updateTravelerColumn() যেভাবে সেভ করে তার সাথে সঙ্গতিপূর্ণ — direct
    // Gemini output, "bio_info" wrapper ছাড়াই সরাসরি flat structure)
    $docData = json_decode($doc['doc_data'] ?? '{}', true) ?: [];
    $bioInfo = $docData['bio_info'] ?? $docData; // কিছু commit path bio_info ছাড়াই flat রাখে

    $pages  = json_decode($doc['pages'] ?? '[]', true) ?: [];
    $images = [];
    foreach ($pages as $p) {
        $filename = $p['filename'] ?? '';
        if (!$filename) continue;
        $images[] = [
            'page_no' => $p['page_no'] ?? 1,
            'url'     => '/api/file/serve.php?doc_id=' . urlencode($doc['sys_id']) . '&file=' . urlencode($filename),
        ];
    }

    jsonOut(['success' => true, 'traveler' => [
        'sys_id'      => $traveler['sys_id'],
        'name'        => $traveler['name'],
        'bio_info'    => $bioInfo,
        'images'      => $images,
        'doc_number'  => $doc['doc_number'],
        'issue_date'  => $doc['issue_date'],
        'expiry_date' => $doc['expiry_date'],
    ]]);

} catch (Throwable $e) {
    error_log('[traveler-full-info] ' . $e->getMessage());
    jsonOut(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}

function jsonOut(array $d): never
{
    echo json_encode($d, JSON_UNESCAPED_UNICODE);
    exit;
}