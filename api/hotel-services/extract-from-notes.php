<?php
/**
 * FILE PATH: /api/hotel-services/extract-from-notes.php
 *
 * Mindboard থেকে select করা notes (text + image) → Gemini → Hotel info extract।
 * Summary অথবা Hotel Quotation generate করে।
 *
 * POST (JSON):
 *   {
 *     work_sys_id:   string,
 *     note_sys_ids:  string[],
 *     action:        'summary' | 'quotation',
 *   }
 *
 * OUTPUT (quotation):
 *   {
 *     success: true,
 *     quotation: {
 *       hotel_name, city, country, star_rating,
 *       check_in, check_out, nights,
 *       room_type, bed_config, size_sqm, max_occupancy,
 *       meal_plan, rooms, currency,
 *       net_rate, sell_rate,
 *       notes: [],
 *       masterdata_suggestion: {     ← review modal-এর জন্য
 *         hotel: { name, city, country, star_rating, address, phone, email },
 *         room_type: { room_name, bed_config, max_adults, max_children, size_sqm },
 *         room_rate: { meal_plan, net_cost, sell_price, valid_from, valid_to, currency_code },
 *         matched_hotel_sys_id: null | string,
 *         matched_room_type_sys_id: null | string,
 *       }
 *     }
 *   }
 */

session_start();
date_default_timezone_set('Asia/Dhaka');
ini_set('display_errors', 0);

require_once '../../server/api_bootstrap.php';
require_once '../../server/db_connection.php';
require_once '../../server/live_storage.php';
require_once '../../server/smb_upload_handler.php';
require_once '../../server/safe_folder_name.php';
require_once __DIR__ . '/../../server/ai-gemini.php';

header('Content-Type: application/json');

function htJsonOut(array $data): never {
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

$body        = json_decode(file_get_contents('php://input'), true) ?: [];
$workSysId   = trim($body['work_sys_id']  ?? '');
$noteSysIds  = $body['note_sys_ids']      ?? [];
$action      = trim($body['action']       ?? 'quotation');

if (!$workSysId)       htJsonOut(['success'=>false,'message'=>'work_sys_id required']);
if (empty($noteSysIds)) htJsonOut(['success'=>false,'message'=>'note_sys_ids required']);

try {
    // ── Step 1: fetch notes ───────────────────────────────────
    $ph   = implode(',', array_fill(0, count($noteSysIds), '?'));
    $stmt = $pdo->prepare("
        SELECT sys_id, note_type, content, file_name, work_sys_id, service_slug, board_name
        FROM task_notes
        WHERE sys_id IN ($ph) AND work_sys_id = ?
        ORDER BY sort_order ASC, id ASC
    ");
    $stmt->execute([...$noteSysIds, $workSysId]);
    $notes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if (!$notes) htJsonOut(['success'=>false,'message'=>'Notes not found']);

    // ── Step 2: build text + image parts ─────────────────────
    $omv        = null;
    $textParts  = [];
    $imageParts = [];

    foreach ($notes as $n) {
        if ($n['note_type'] === 'text') {
            $textParts[] = trim($n['content'] ?? '');
            continue;
        }
        if ($n['note_type'] === 'image' && $n['file_name']) {
            try {
                $ctx  = _genNotesGetCtxByWork($pdo, $n['work_sys_id'], $n['service_slug'] ?? 'hotel', $n['board_name'] ?? 'mindboard');
                $base = smbBuildPath($ctx);
                if (!$omv) $omv = new OMV_SMB_Manager();
                $tmp  = sys_get_temp_dir() . '/htextract_' . uniqid() . '_' . $n['file_name'];
                $ok   = $omv->downloadFile($base . '/' . $n['file_name'], $tmp);
                if ($ok && file_exists($tmp)) {
                    $ext  = strtolower(pathinfo($n['file_name'], PATHINFO_EXTENSION));
                    $mime = in_array($ext, ['jpg','jpeg']) ? 'image/jpeg' : ($ext === 'png' ? 'image/png' : ($ext === 'webp' ? 'image/webp' : 'image/jpeg'));
                    $imageParts[] = ['mime_type' => $mime, 'data' => base64_encode(file_get_contents($tmp))];
                    @unlink($tmp);
                }
            } catch (Throwable $e) { /* skip failed images */ }
        }
    }

    $combinedText = implode("\n\n", array_filter($textParts));

    // ── Step 3: Summary ───────────────────────────────────────
    if ($action === 'summary') {
        $system = "You are helping a Bangladeshi travel agency. Summarize these hotel-related notes into a concise, structured summary in Bengali or English (match the language of the notes). Include hotel name, location, dates, room details, pricing if present.";
        $userTxt = $combinedText ?: 'See attached image(s).';
        $res = callGeminiMultimodal($system, $userTxt, $imageParts, 1024);
        if (!$res['success']) htJsonOut(['success'=>false,'message'=>$res['error'] ?? 'AI failed']);
        htJsonOut(['success'=>true,'summary_text'=>trim($res['text'])]);
    }

    // ── Step 4: Hotel Quotation extraction ───────────────────
    $system = <<<SYS
You are helping a Bangladeshi travel agency extract hotel quotation data from notes and/or screenshots.

Return ONLY a valid JSON object with this exact shape (no markdown, no explanation):
{
  "hotel_name": "",
  "city": "",
  "country": "",
  "star_rating": 0,
  "address": "",
  "phone": "",
  "email": "",
  "check_in": "",
  "check_out": "",
  "nights": 0,
  "room_type": "",
  "bed_config": "",
  "size_sqm": null,
  "max_adults": 2,
  "max_children": 0,
  "meal_plan": "bb",
  "rooms": 1,
  "currency": "BDT",
  "net_rate": 0,
  "sell_rate": 0,
  "cancellation_policy": "",
  "notes": []
}

Field rules:
- star_rating: integer 1-5, 0 if unknown
- check_in / check_out: YYYY-MM-DD format if date present, else empty string
- nights: integer, calculate from check_in/check_out if both present
- meal_plan: one of "room_only" | "bb" (bed+breakfast) | "hb" (half board) | "fb" (full board) | "ai" (all inclusive). Default "bb"
- bed_config: e.g. "King Bed", "Twin Beds", "Queen Bed", "Double Bed"
- size_sqm: integer sqm or null if unknown
- max_adults: integer per room
- max_children: integer per room
- currency: 3-letter code (BDT default)
- net_rate: vendor net cost per room per night
- sell_rate: selling price per room per night (with markup), 0 if unknown
- notes: array of strings for extra info (cancellation, breakfast inclusion, etc)
- Only extract data actually present — do not invent prices or dates
SYS;

    $userTxt = $combinedText ?: 'See attached image(s).';
    $res = callGeminiMultimodal($system, $userTxt, $imageParts, 2048);
    if (!$res['success']) htJsonOut(['success'=>false,'message'=>$res['error'] ?? 'AI failed']);

    // Clean and parse JSON
    $raw = $res['text'];
    $raw = preg_replace('/```json|```/', '', $raw);
    $raw = trim($raw);
    $extracted = json_decode($raw, true);
    if (!is_array($extracted)) htJsonOut(['success'=>false,'message'=>'AI returned invalid JSON']);

    // ── Step 5: Masterdata match check ───────────────────────
    $masterSuggestion = null;
    $hotelName  = trim($extracted['hotel_name'] ?? '');
    $cityName   = trim($extracted['city']       ?? '');

    if ($hotelName) {
        // Try to match existing hotel
        $mh = $pdo->prepare("
            SELECT sys_id, name, city_name, country_name, star_rating, address, phone, email
            FROM hotels
            WHERE (LOWER(name) LIKE LOWER(?) OR LOWER(search_terms) LIKE LOWER(?))
              AND status = 'active'
            LIMIT 1
        ");
        $likeVal = '%' . $hotelName . '%';
        $mh->execute([$likeVal, $likeVal]);
        $matchedHotel = $mh->fetch(PDO::FETCH_ASSOC);

        $matchedRoomType = null;
        $matchedRoomRate = null;
        if ($matchedHotel && $extracted['room_type']) {
            $mr = $pdo->prepare("
                SELECT sys_id, room_name, bed_config, max_adults, max_children, size_sqm
                FROM room_types
                WHERE hotel_sys_id = ? AND LOWER(room_name) LIKE LOWER(?) AND status='active'
                LIMIT 1
            ");
            $mr->execute([$matchedHotel['sys_id'], '%' . $extracted['room_type'] . '%']);
            $matchedRoomType = $mr->fetch(PDO::FETCH_ASSOC) ?: null;

            if ($matchedRoomType && $extracted['check_in']) {
                $rr = $pdo->prepare("
                    SELECT sys_id, meal_plan, net_cost, sell_price, valid_from, valid_to, currency_code
                    FROM room_rates
                    WHERE room_type_sys_id = ?
                      AND meal_plan = ?
                      AND valid_from <= ? AND valid_to >= ?
                      AND status = 'active'
                    ORDER BY valid_from DESC LIMIT 1
                ");
                $rr->execute([
                    $matchedRoomType['sys_id'],
                    $extracted['meal_plan'] ?? 'bb',
                    $extracted['check_in'],
                    $extracted['check_in'],
                ]);
                $matchedRoomRate = $rr->fetch(PDO::FETCH_ASSOC) ?: null;
            }
        }

        $masterSuggestion = [
            'hotel' => [
                'name'        => $hotelName,
                'city'        => $cityName,
                'country'     => $extracted['country']     ?? '',
                'star_rating' => $extracted['star_rating'] ?? 0,
                'address'     => $extracted['address']     ?? '',
                'phone'       => $extracted['phone']       ?? '',
                'email'       => $extracted['email']       ?? '',
            ],
            'room_type' => [
                'room_name'          => $extracted['room_type']  ?? '',
                'bed_config'         => $extracted['bed_config'] ?? '',
                'max_adults'         => $extracted['max_adults'] ?? 2,
                'max_children'       => $extracted['max_children'] ?? 0,
                'size_sqm'           => $extracted['size_sqm']  ?? null,
            ],
            'room_rate' => [
                'meal_plan'           => $extracted['meal_plan']   ?? 'bb',
                'net_cost'            => $extracted['net_rate']    ?? 0,
                'sell_price'          => $extracted['sell_rate']   ?? 0,
                'currency_code'       => $extracted['currency']    ?? 'BDT',
                'valid_from'          => $extracted['check_in']    ?? '',
                'valid_to'            => $extracted['check_out']   ?? '',
                'cancellation_policy' => $extracted['cancellation_policy'] ?? '',
            ],
            'matched_hotel_sys_id'      => $matchedHotel    ? $matchedHotel['sys_id']    : null,
            'matched_hotel_data'        => $matchedHotel    ?: null,
            'matched_room_type_sys_id'  => $matchedRoomType ? $matchedRoomType['sys_id'] : null,
            'matched_room_type_data'    => $matchedRoomType ?: null,
            'matched_room_rate_data'    => $matchedRoomRate ?: null,
            'has_new_data'              => true, // frontend shows review modal
        ];
    }

    htJsonOut([
        'success'   => true,
        'quotation' => array_merge($extracted, [
            'masterdata_suggestion' => $masterSuggestion,
        ]),
    ]);

} catch (Throwable $e) {
    htJsonOut(['success'=>false,'message'=>$e->getMessage()]);
}

// ── Helper: get SMB context for a work's notes ────────────────
function _genNotesGetCtxByWork(PDO $pdo, string $workSysId, string $serviceSlug, string $board): array
{
    $s = $pdo->prepare("SELECT client_info FROM works WHERE sys_id=? LIMIT 1");
    $s->execute([$workSysId]);
    $w = $s->fetch(PDO::FETCH_ASSOC);
    $ci = json_decode($w['client_info'] ?? '{}', true) ?? [];
    return ['client_sys_id'=>$ci['sys_id'] ?? '', 'work_sys_id'=>$workSysId, 'service_slug'=>$serviceSlug, 'board'=>$board];
}