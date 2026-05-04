<?php
// api/packages/generate-pdf.php
// GET ?sys_id=THR-PK-26-00K001&template=detailed|bullet
// Streams PDF directly to browser — no file stored on server

// Adjust this path to match your server's vendor location
require_once('../../pages/vendor/autoload.php');
require_once('../../server/db_connection.php');

$sys_id   = trim($_GET['sys_id']   ?? '');
$template = trim($_GET['template'] ?? 'detailed');
if (!in_array($template, ['detailed','bullet'])) $template = 'detailed';

if (!$sys_id) {
    http_response_code(400);
    die('sys_id required');
}

// ── Fetch package ────────────────────────────────────────────────────
$stmt = $pdo->prepare("SELECT * FROM packages WHERE sys_id = ? LIMIT 1");
$stmt->execute([$sys_id]);
$pkg = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$pkg) {
    http_response_code(404);
    die('Package not found');
}

// Decode JSON fields
$jsonFields = ['countries','cities','no_of_pax','hotels','pack_price',
               'pack_itenaries','pack_inclusions','pack_exclusions',
               'package_calculations_details'];
foreach ($jsonFields as $f) {
    $pkg[$f] = json_decode($pkg[$f] ?? 'null', true) ?: [];
}

// ── Resolve pricing from calculation details ─────────────────────────
$calcDetails  = $pkg['package_calculations_details'];
$calcData     = is_string($calcDetails) ? json_decode($calcDetails, true) : $calcDetails;
$actRows      = $calcData['details']['activity'] ?? [];
$hotelRows    = $calcData['details']['hotel']    ?? [];
$grandTotal   = $calcData['grand_total']         ?? 0;
$currency     = $pkg['currency_symbol']          ?? '৳';
$currCode     = $pkg['currency_code']            ?? 'BDT';

// ── Helpers ───────────────────────────────────────────────────────────
function esc(string $s): string {
    return htmlspecialchars($s, ENT_QUOTES | ENT_HTML5, 'UTF-8');
}
function money(float $n, string $sym = '৳'): string {
    return $sym . number_format($n, 2);
}
function fmtTime(?string $t): string {
    if (!$t) return '—';
    try { return (new DateTime($t))->format('h:i A'); } catch(Exception $e) { return $t; }
}

// ── Brand constants ───────────────────────────────────────────────────
$logoPath    = realpath(__DIR__ . '/../../assets/images/pad-v2.png');
$logoSrc     = $logoPath ? 'var:logo' : '';
$brandNavy   = '#1A2039';
$brandGreen  = '#50BC81';
$brandLight  = '#F4F4F4';
$phone       = '+880 1611 482 773';
$email       = 'info@travhub.com.bd';
$address     = '5th Floor, House 1, Road 6, Sector 3, Uttara, Dhaka-1230';

// ── Build HTML ────────────────────────────────────────────────────────
$isBullet  = ($template === 'bullet');
$title     = esc($pkg['title'] ?? 'Travel Package');
$desc      = esc($pkg['description'] ?? '');
$countries = implode(', ', array_map(fn($c) => esc($c['name'] ?? ''), $pkg['countries'] ?? []));
$cities    = implode(', ', array_map(fn($c) => esc($c['name'] ?? ''), $pkg['cities']    ?? []));
$duration  = esc($pkg['duration'] ?? '');
$startDate = $pkg['start_date'] ? date('d M Y', strtotime($pkg['start_date'])) : '—';
$endDate   = $pkg['end_date']   ? date('d M Y', strtotime($pkg['end_date']))   : '—';
$pax       = $pkg['no_of_pax'];
$paxStr    = ($pax['adult'] ?? 0) . ' Adult' . (($pax['child'] ?? 0) ? ', ' . $pax['child'] . ' Child' : '') . (($pax['infant'] ?? 0) ? ', ' . $pax['infant'] . ' Infant' : '');

// ─────────────────────────────────────────────────────────────────────
// CSS shared
// ─────────────────────────────────────────────────────────────────────
$css = "
@page {
    margin-top: 35mm;
    margin-bottom: 20mm;
    margin-left: 15mm;
    margin-right: 15mm;
}
body {
    font-family: 'Poppins', sans-serif;
    font-size: 9pt;
    color: #333;
    line-height: 1.5;
}
.navy  { color: {$brandNavy}; }
.green { color: {$brandGreen}; }
h1 { font-size: 20pt; font-weight: 700; color: {$brandNavy}; margin: 0 0 4pt; }
h2 { font-size: 13pt; font-weight: 700; color: {$brandNavy}; margin: 10pt 0 4pt; border-bottom: 1.5pt solid {$brandGreen}; padding-bottom: 3pt; }
h3 { font-size: 10pt; font-weight: 600; color: {$brandNavy}; margin: 8pt 0 3pt; }
h4 { font-size: 9pt;  font-weight: 600; color: #444; margin: 5pt 0 2pt; }
p  { margin: 2pt 0; }
table { width: 100%; border-collapse: collapse; margin-bottom: 8pt; }
th { background: {$brandNavy}; color: #fff; padding: 5pt 6pt; font-size: 8pt; text-align: left; }
td { padding: 4pt 6pt; font-size: 8.5pt; border-bottom: 0.5pt solid #eee; vertical-align: top; }
tr:nth-child(even) td { background: #f9f9f9; }
.badge { display: inline-block; background: {$brandGreen}; color: #fff; border-radius: 3pt; padding: 1pt 5pt; font-size: 7.5pt; font-weight: 600; }
.badge-navy { background: {$brandNavy}; }
.info-box { background: {$brandLight}; border-left: 3pt solid {$brandGreen}; padding: 6pt 8pt; margin-bottom: 8pt; border-radius: 2pt; }
.day-header { background: {$brandNavy}; color: #fff; padding: 5pt 8pt; border-radius: 3pt; margin: 8pt 0 4pt; }
.day-num { font-size: 8pt; opacity: 0.7; }
.day-title { font-size: 11pt; font-weight: 700; }
.act-card { border: 0.5pt solid #ddd; border-radius: 3pt; padding: 5pt 7pt; margin-bottom: 5pt; }
.act-name { font-weight: 700; font-size: 9.5pt; color: {$brandNavy}; }
.act-meta { color: #666; font-size: 7.5pt; }
.tr-card { border: 0.5pt solid {$brandGreen}; border-radius: 3pt; padding: 4pt 7pt; margin-bottom: 4pt; background: #f0faf5; }
.inc { color: #166534; }
.exc { color: #991b1b; }
.section-label { font-size: 7pt; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5pt; color: #999; margin-bottom: 2pt; }
.cover-strip { background: {$brandNavy}; color: #fff; padding: 10pt 12pt; border-radius: 4pt; margin-bottom: 10pt; }
.price-total { font-size: 14pt; font-weight: 700; color: {$brandNavy}; }
.bullet-day { margin-bottom: 8pt; }
.bullet-day-hdr { background: {$brandNavy}; color: #fff; padding: 3pt 7pt; border-radius: 3pt; font-weight: 700; font-size: 9pt; }
.bullet li { margin-bottom: 2pt; font-size: 8.5pt; }
.footer-line { border-top: 1pt solid {$brandLight}; padding-top: 4pt; font-size: 7.5pt; color: #999; text-align: center; }
";

// ─────────────────────────────────────────────────────────────────────
// HEADER HTML (used as mPDF header on every page)
// ─────────────────────────────────────────────────────────────────────
$headerHtml = "
<table width='100%' style='border-collapse:collapse;'>
    <tr>
        <td width='22%' style='vertical-align:middle;'>
            " . ($logoPath ? "<img src='var:logo' style='height:40pt; width:auto;' />" : "<span style='font-size:14pt;font-weight:700;color:{$brandNavy};'>TravHub</span>") . "
        </td>
        <td width='4%' style='border-left:1.5pt solid {$brandGreen}; height:44pt;'>&nbsp;</td>
        <td style='vertical-align:middle; padding-left:8pt;'>
            <p style='margin:1pt 0; font-size:8pt; color:#555;'>&#9742; {$phone}</p>
            <p style='margin:1pt 0; font-size:8pt; color:#555;'>&#9993; {$email}</p>
            <p style='margin:1pt 0; font-size:8pt; color:#555;'>&#9679; {$address}</p>
        </td>
    </tr>
</table>
<hr style='border:0; border-top:1pt solid #ddd; margin:4pt 0 0;' />
";

// ─────────────────────────────────────────────────────────────────────
// FOOTER HTML
// ─────────────────────────────────────────────────────────────────────
$footerHtml = "
<hr style='border:0; border-top:0.5pt solid #ddd; margin-bottom:3pt;' />
<table width='100%'>
    <tr>
        <td style='font-size:7pt; color:#999; width:30%;'>
            <span style='display:inline-block; width:20pt; border-top:2pt solid {$brandNavy};'></span>
            <span style='display:inline-block; width:8pt; border-top:2pt solid {$brandGreen};'></span>
        </td>
        <td style='font-size:7pt; color:#999; text-align:center;'>TravHub — {$address}</td>
        <td style='font-size:7pt; color:#999; text-align:right;'>Page {PAGENO} of {nbpg}</td>
    </tr>
</table>
";

// ─────────────────────────────────────────────────────────────────────
// BODY — switch by template
// ─────────────────────────────────────────────────────────────────────
ob_start();
if ($isBullet) {
    // ═══════════════ BULLET TEMPLATE ═══════════════
    ?>
    <!-- Cover strip -->
    <div class="cover-strip">
        <p style="font-size:8pt;opacity:.7;margin:0;">TRAVEL PACKAGE</p>
        <h1 style="color:#fff;margin:2pt 0;"><?= $title ?></h1>
        <p style="margin:0;font-size:9pt;opacity:.85;"><?= $countries ?: $cities ?></p>
    </div>

    <!-- Key info -->
    <table>
        <tr>
            <td width="25%"><span class="section-label">Duration</span><br><?= esc($duration) ?: '—' ?></td>
            <td width="25%"><span class="section-label">Dates</span><br><?= $startDate ?> – <?= $endDate ?></td>
            <td width="25%"><span class="section-label">Pax</span><br><?= esc($paxStr) ?></td>
            <td width="25%"><span class="section-label">Destinations</span><br><?= $cities ?: $countries ?></td>
        </tr>
    </table>

    <!-- Itinerary bullets -->
    <h2>Itinerary</h2>
    <?php foreach (($pkg['pack_itenaries'] ?? []) as $day): ?>
    <div class="bullet-day">
        <div class="bullet-day-hdr">
            Day <?= (int)$day['day_number'] ?>
            <?= $day['title'] ? ' — ' . esc($day['title']) : '' ?>
            <?= $day['date'] ? ' &nbsp;|&nbsp; ' . date('d M Y', strtotime($day['date'])) : '' ?>
        </div>
        <ul class="bullet" style="margin:3pt 0 0 12pt; padding:0;">
            <?php foreach (($day['activities'] ?? []) as $act): ?>
            <li>
                <strong><?= esc($act['name']) ?></strong>
                <?= $act['start_time'] ? ' (' . fmtTime($act['start_time']) . ($act['end_time'] ? '–' . fmtTime($act['end_time']) : '') . ')' : '' ?>
                <?= $act['location'] ? ' — ' . esc($act['location']) : '' ?>
                <?php if (!empty($act['transfers'])): foreach ($act['transfers'] as $tr): if (empty($tr['title'])) continue; ?>
                <br>&nbsp;&nbsp;&nbsp;&#8618; <em><?= esc($tr['title']) ?></em> [<?= strtoupper($tr['type'] ?? 'SIC') ?>]
                <?php endforeach; endif; ?>
            </li>
            <?php endforeach; ?>
            <?php foreach (($day['flights'] ?? []) as $fl): ?>
            <li>&#9992; <strong><?= esc($fl['flight_number'] ?: 'Flight') ?></strong>
                <?= $fl['dep_airport'] ? ' ' . esc($fl['dep_airport']) : '' ?>
                <?= $fl['dep_time'] ? fmtTime($fl['dep_time']) : '' ?>
                <?= ($fl['dep_date']) ? date('d M', strtotime($fl['dep_date'])) : '' ?>
                &#8594; <?= $fl['arr_airport'] ? esc($fl['arr_airport']) : '?' ?>
                <?= $fl['arr_time'] ? fmtTime($fl['arr_time']) : '' ?>
            </li>
            <?php endforeach; ?>
            <?php foreach (($day['transfers'] ?? []) as $tr): ?>
            <li>&#8618; <strong><?= esc($tr['title'] ?: 'Transfer') ?></strong> [<?= strtoupper($tr['type'] ?? 'SIC') ?>]
                <?= $tr['start_time'] ? ' ' . fmtTime($tr['start_time']) . '–' . fmtTime($tr['end_time']) : '' ?>
            </li>
            <?php endforeach; ?>
            <?php if ($day['overnight_stay']): ?>
            <li>&#127963; Overnight: <strong><?= esc($day['overnight_stay']) ?></strong></li>
            <?php endif; ?>
            <?php if (!empty($day['meals'])): ?>
            <li>Meals: <?= esc(implode(', ', $day['meals'])) ?></li>
            <?php endif; ?>
        </ul>
    </div>
    <?php endforeach; ?>

    <!-- Inclusions / Exclusions -->
    <?php if (!empty($pkg['pack_inclusions']) || !empty($pkg['pack_exclusions'])): ?>
    <table>
        <tr>
            <td width="50%" style="vertical-align:top;">
                <h3 class="inc">&#10003; Inclusions</h3>
                <ul class="bullet inc" style="margin:0 0 0 12pt;padding:0;">
                    <?php foreach ($pkg['pack_inclusions'] as $it): ?>
                    <li><?= esc($it['title'] ?? $it) ?></li>
                    <?php endforeach; ?>
                </ul>
            </td>
            <td width="50%" style="vertical-align:top;">
                <h3 class="exc">&#10007; Exclusions</h3>
                <ul class="bullet exc" style="margin:0 0 0 12pt;padding:0;">
                    <?php foreach ($pkg['pack_exclusions'] as $it): ?>
                    <li><?= esc($it['title'] ?? $it) ?></li>
                    <?php endforeach; ?>
                </ul>
            </td>
        </tr>
    </table>
    <?php endif; ?>

    <!-- Pricing -->
    <?php if ($actRows || $hotelRows): ?>
    <h2>Pricing Summary</h2>
    <?php if ($actRows): ?>
    <table>
        <tr><th>Activity</th><th width="15%">Pax</th><th width="20%">Sale Price/Person</th></tr>
        <?php foreach ($actRows as $r): ?>
        <tr>
            <td><?= esc($r['particular'] ?? '—') ?></td>
            <td><?= (int)($r['total_pax'] ?? 1) ?></td>
            <td><?= money((float)($r['sale_price_per_person'] ?? 0), $currency) ?></td>
        </tr>
        <?php endforeach; ?>
    </table>
    <?php endif; ?>
    <?php if ($hotelRows): ?>
    <table>
        <tr><th>Hotel</th><th width="12%">Nights</th><th width="15%">Rooms</th><th width="20%">Sale/Night</th></tr>
        <?php foreach ($hotelRows as $r): ?>
        <tr>
            <td><?= esc($r['hotel_name'] ?? '—') ?></td>
            <td><?= (int)($r['no_of_nights'] ?? 0) ?></td>
            <td><?= (int)($r['no_of_rooms'] ?? 1) ?> <?= esc($r['room_type'] ?? '') ?></td>
            <td><?= money((float)($r['sale_price_per_night_bdt'] ?? 0), $currency) ?></td>
        </tr>
        <?php endforeach; ?>
    </table>
    <?php endif; ?>
    <p style="text-align:right;" class="price-total">Grand Total: <?= money((float)$grandTotal, $currency) ?> <?= esc($currCode) ?></p>
    <?php endif; ?>

<?php } else {
    // ═══════════════ DETAILED TEMPLATE ═══════════════
    ?>
    <!-- Cover -->
    <div class="cover-strip">
        <p style="font-size:8pt;opacity:.7;margin:0 0 2pt;">TRAVEL PACKAGE PROPOSAL</p>
        <h1 style="color:#fff;margin:0 0 4pt;"><?= $title ?></h1>
        <?php if ($desc): ?><p style="margin:0;font-size:9pt;opacity:.85;"><?= $desc ?></p><?php endif; ?>
    </div>

    <!-- Key info box -->
    <div class="info-box">
        <table style="margin:0;">
            <tr>
                <td width="20%"><span class="section-label">Destinations</span><br><strong><?= $cities ?: $countries ?></strong></td>
                <td width="20%"><span class="section-label">Duration</span><br><strong><?= esc($duration) ?: '—' ?></strong></td>
                <td width="25%"><span class="section-label">Travel Dates</span><br><strong><?= $startDate ?> – <?= $endDate ?></strong></td>
                <td width="20%"><span class="section-label">Pax</span><br><strong><?= esc($paxStr) ?></strong></td>
                <td width="15%"><span class="section-label">Days</span><br><strong><?= count($pkg['pack_itenaries'] ?? []) ?> days</strong></td>
            </tr>
        </table>
    </div>

    <!-- Day-by-day itinerary -->
    <h2>Day-by-Day Itinerary</h2>
    <?php foreach (($pkg['pack_itenaries'] ?? []) as $day):
        $dayMeals = implode(', ', $day['meals'] ?? []);
    ?>
    <div class="day-header">
        <span class="day-num">DAY <?= (int)$day['day_number'] ?><?= $day['date'] ? '  |  ' . date('D, d M Y', strtotime($day['date'])) : '' ?></span><br>
        <span class="day-title"><?= $day['title'] ? esc($day['title']) : 'Day ' . (int)$day['day_number'] ?></span>
        <?php if ($day['overnight_stay']): ?>
        &nbsp;&nbsp;<span style="font-size:8pt;opacity:.7;">&#127963; <?= esc($day['overnight_stay']) ?></span>
        <?php endif; ?>
    </div>

    <?php if ($day['day_start_time'] || $dayMeals): ?>
    <p class="act-meta" style="margin-bottom:4pt;">
        <?= $day['day_start_time'] ? '&#9200; Day starts: ' . fmtTime($day['day_start_time']) : '' ?>
        <?= $dayMeals ? '&nbsp;&nbsp;&#127860; Meals: ' . esc($dayMeals) : '' ?>
    </p>
    <?php endif; ?>

    <!-- Activities -->
    <?php foreach (($day['activities'] ?? []) as $act): ?>
    <div class="act-card">
        <table style="margin:0;" width="100%">
            <tr>
                <td>
                    <span class="act-name"><?= esc($act['name']) ?></span>
                    &nbsp;<span class="badge <?= $act['type'] === 'transfer' ? 'badge-navy' : '' ?>"><?= esc(ucfirst($act['type'] ?? 'tour')) ?></span>
                </td>
                <td style="text-align:right;" width="30%">
                    <?php if ($act['start_time']): ?>
                    <span class="act-meta">&#9200; <?= fmtTime($act['start_time']) ?><?= $act['end_time'] ? ' – ' . fmtTime($act['end_time']) : '' ?></span>
                    <?php endif; ?>
                    <?php if ($act['duration_hours']): ?>
                    &nbsp;<span class="act-meta">(<?= (float)$act['duration_hours'] ?>h)</span>
                    <?php endif; ?>
                </td>
            </tr>
        </table>
        <?php if ($act['location']): ?>
        <p class="act-meta">&#128205; <?= esc($act['location']) ?></p>
        <?php endif; ?>
        <?php if ($act['note']): ?>
        <p style="font-size:8pt;color:#555;margin-top:2pt;font-style:italic;"><?= esc($act['note']) ?></p>
        <?php endif; ?>

        <!-- Activity transfers (child) -->
        <?php foreach (($act['transfers'] ?? []) as $tr):
            if (empty($tr['title']) && empty($tr['pricing'])) continue; ?>
        <div class="tr-card" style="margin-top:4pt;">
            <table style="margin:0;" width="100%">
                <tr>
                    <td><strong><?= esc($tr['title'] ?: 'Transfer') ?></strong> &nbsp;<span class="badge badge-navy"><?= strtoupper($tr['type'] ?? 'SIC') ?></span></td>
                </tr>
            </table>
            <?php if (!empty($tr['pricing'])): ?>
            <table style="margin:3pt 0 0;font-size:8pt;">
                <tr style="background:<?= $brandNavy ?>;color:#fff;">
                    <th>Car</th><th width="12%">Type</th><th width="15%">Adult</th><th width="15%">Child</th><th width="15%">Full</th>
                </tr>
                <?php foreach ($tr['pricing'] as $p): ?>
                <tr>
                    <td><?= esc($p['car_name'] ?? '—') ?></td>
                    <td><?= esc($p['car_type'] ?? '—') ?></td>
                    <td><?= isset($p['price_adult']) && $p['price_adult'] !== null ? money((float)$p['price_adult'], $currency) : '—' ?></td>
                    <td><?= isset($p['price_child']) && $p['price_child'] !== null ? money((float)$p['price_child'], $currency) : '—' ?></td>
                    <td><?= isset($p['price_full'])  && $p['price_full']  !== null ? money((float)$p['price_full'],  $currency) : '—' ?></td>
                </tr>
                <?php endforeach; ?>
            </table>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endforeach; ?>

    <!-- Standalone transfers for the day -->
    <?php foreach (($day['transfers'] ?? []) as $tr): ?>
    <div class="tr-card">
        <table style="margin:0;" width="100%">
            <tr>
                <td>
                    <strong>&#8618; <?= esc($tr['title'] ?: 'Transfer') ?></strong>
                    &nbsp;<span class="badge badge-navy"><?= strtoupper($tr['type'] ?? 'SIC') ?></span>
                </td>
                <td style="text-align:right;" width="30%">
                    <?php if ($tr['start_time']): ?>
                    <span class="act-meta">&#9200; <?= fmtTime($tr['start_time']) ?> – <?= fmtTime($tr['end_time']) ?></span>
                    <?php endif; ?>
                </td>
            </tr>
        </table>
        <?php if ($tr['notes']): ?>
        <p style="font-size:8pt;color:#555;font-style:italic;margin:2pt 0 0;"><?= esc($tr['notes']) ?></p>
        <?php endif; ?>
        <?php if (!empty($tr['pricing'])): ?>
        <table style="margin:3pt 0 0;font-size:8pt;">
            <tr style="background:<?= $brandNavy ?>;color:#fff;">
                <th>Car</th><th width="12%">Type</th><th width="15%">Adult</th><th width="15%">Child</th><th width="15%">Full</th>
            </tr>
            <?php foreach ($tr['pricing'] as $p): ?>
            <tr>
                <td><?= esc($p['car_name'] ?? '—') ?></td>
                <td><?= esc($p['car_type'] ?? '—') ?></td>
                <td><?= isset($p['price_adult']) && $p['price_adult'] !== null ? money((float)$p['price_adult'], $currency) : '—' ?></td>
                <td><?= isset($p['price_child']) && $p['price_child'] !== null ? money((float)$p['price_child'], $currency) : '—' ?></td>
                <td><?= isset($p['price_full'])  && $p['price_full']  !== null ? money((float)$p['price_full'],  $currency) : '—' ?></td>
            </tr>
            <?php endforeach; ?>
        </table>
        <?php endif; ?>
    </div>
    <?php endforeach; ?>

    <!-- Flights for this day (detailed) -->
    <?php if (!empty($day['flights'])): ?>
    <div style="margin-top:4pt;">
    <?php foreach ($day['flights'] as $fl): ?>
    <div style="border:0.5pt solid #7dd3fc;border-radius:3pt;padding:5pt 7pt;margin-bottom:4pt;background:#f0f9ff;">
        <p style="margin:0 0 3pt;font-weight:700;font-size:9pt;color:#0c4a6e;">
            &#9992; <?= esc($fl['flight_number'] ?? '—') ?>
        </p>
        <table style="margin:0;font-size:8pt;" width="100%">
            <tr>
                <td width="12%" style="color:#0369a1;font-weight:700;font-size:7pt;">DEP</td>
                <td><?= esc($fl['dep_airport'] ?? '—') ?></td>
                <td width="26%"><?= $fl['dep_date'] ? date('d M Y', strtotime($fl['dep_date'])) : '—' ?></td>
                <td width="14%" style="text-align:right;"><?= fmtTime($fl['dep_time'] ?? null) ?></td>
            </tr>
            <?php foreach (($fl['transits'] ?? []) as $ti => $tr): ?>
            <tr style="background:#fffbeb;">
                <td style="color:#b45309;font-weight:700;font-size:7pt;">T<?= $ti+1 ?></td>
                <td style="color:#92400e;"><?= esc($tr['dep_airport']??'—') ?> → <?= esc($tr['arr_airport']??'—') ?></td>
                <td style="color:#92400e;"><?= $tr['dep_date'] ? date('d M Y', strtotime($tr['dep_date'])) : '—' ?></td>
                <td style="text-align:right;color:#92400e;"><?= fmtTime($tr['dep_time']??null) ?></td>
            </tr>
            <?php endforeach; ?>
            <tr>
                <td style="color:#0369a1;font-weight:700;font-size:7pt;">ARR</td>
                <td><?= esc($fl['arr_airport'] ?? '—') ?></td>
                <td><?= $fl['arr_date'] ? date('d M Y', strtotime($fl['arr_date'])) : '—' ?></td>
                <td style="text-align:right;"><?= fmtTime($fl['arr_time'] ?? null) ?></td>
            </tr>
        </table>
    </div>
    <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <?php endforeach; // end days ?>

    <!-- Hotel accommodation -->
    <?php if (!empty($pkg['hotels'])): ?>
    <h2>Accommodation</h2>
    <?php foreach ($pkg['hotels'] as $h): ?>
    <div class="act-card">
        <strong><?= esc($h['name'] ?? $h['hotel_name'] ?? '—') ?></strong>
        <?php if ($h['city'] ?? $h['city_name'] ?? null): ?>
        &nbsp;<span class="act-meta">— <?= esc($h['city'] ?? $h['city_name']) ?></span>
        <?php endif; ?>
        <?php if ($h['check_in'] ?? null): ?>
        <p class="act-meta">Check-in: <?= date('d M Y', strtotime($h['check_in'])) ?>
        <?= ($h['check_out'] ?? null) ? ' &nbsp;|&nbsp; Check-out: ' . date('d M Y', strtotime($h['check_out'])) : '' ?></p>
        <?php endif; ?>
    </div>
    <?php endforeach; ?>
    <?php endif; ?>

    <!-- Inclusions / Exclusions -->
    <?php if (!empty($pkg['pack_inclusions']) || !empty($pkg['pack_exclusions'])): ?>
    <h2>Inclusions &amp; Exclusions</h2>
    <table>
        <tr>
            <td width="50%" style="vertical-align:top; padding-right:8pt;">
                <h3 class="inc">&#10003; Inclusions</h3>
                <?php foreach ($pkg['pack_inclusions'] as $it): ?>
                <p class="inc">&#10003;&nbsp; <?= esc($it['title'] ?? $it) ?>
                    <?php if (!empty($it['description'])): ?><br><span style="color:#555;font-size:8pt;font-style:italic;"><?= esc($it['description']) ?></span><?php endif; ?>
                </p>
                <?php endforeach; ?>
            </td>
            <td width="50%" style="vertical-align:top; border-left:0.5pt solid #eee; padding-left:8pt;">
                <h3 class="exc">&#10007; Exclusions</h3>
                <?php foreach ($pkg['pack_exclusions'] as $it): ?>
                <p class="exc">&#10007;&nbsp; <?= esc($it['title'] ?? $it) ?>
                    <?php if (!empty($it['description'])): ?><br><span style="color:#555;font-size:8pt;font-style:italic;"><?= esc($it['description']) ?></span><?php endif; ?>
                </p>
                <?php endforeach; ?>
            </td>
        </tr>
    </table>
    <?php endif; ?>

    <!-- Pricing from advanced calculator -->
    <?php if ($actRows || $hotelRows): ?>
    <h2>Pricing Breakdown</h2>
    <?php if ($actRows): ?>
    <h3>Activities</h3>
    <table>
        <tr><th>Activity / Particular</th><th width="12%">Days</th><th width="10%">Pax</th><th width="20%">Sale Price / Person</th></tr>
        <?php foreach ($actRows as $r): ?>
        <tr>
            <td><?= esc($r['particular'] ?? '—') ?><?= $r['date'] ? '<br><span style="font-size:7.5pt;color:#888;">' . date('d M Y', strtotime($r['date'])) . '</span>' : '' ?></td>
            <td><?= (int)($r['days_count'] ?? 1) ?></td>
            <td><?= (int)($r['total_pax'] ?? 1) ?></td>
            <td><strong><?= money((float)($r['sale_price_per_person'] ?? 0), $currency) ?></strong></td>
        </tr>
        <?php endforeach; ?>
    </table>
    <?php endif; ?>
    <?php if ($hotelRows): ?>
    <h3>Hotels</h3>
    <table>
        <tr><th>Hotel</th><th width="15%">Check-in</th><th width="15%">Check-out</th><th width="10%">Nights</th><th width="12%">Rooms</th><th width="18%">Sale / Night</th></tr>
        <?php foreach ($hotelRows as $r): ?>
        <tr>
            <td><?= esc($r['hotel_name'] ?? '—') ?> <span style="font-size:7.5pt;color:#888;"><?= esc($r['room_type'] ?? '') ?></span></td>
            <td><?= $r['check_in']  ? date('d M Y', strtotime($r['check_in']))  : '—' ?></td>
            <td><?= $r['check_out'] ? date('d M Y', strtotime($r['check_out'])) : '—' ?></td>
            <td><?= (int)($r['no_of_nights'] ?? 0) ?></td>
            <td><?= (int)($r['no_of_rooms'] ?? 1) ?></td>
            <td><strong><?= money((float)($r['sale_price_per_night_bdt'] ?? 0), $currency) ?></strong></td>
        </tr>
        <?php endforeach; ?>
    </table>
    <?php endif; ?>
    <div class="info-box" style="text-align:right;">
        <span class="section-label">Grand Total</span><br>
        <span class="price-total"><?= money((float)$grandTotal, $currency) ?> <?= esc($currCode) ?></span>
    </div>
    <?php endif; ?>

    <!-- Price options -->
    <?php if (!empty($pkg['pack_price'])): ?>
    <h2>Package Options</h2>
    <table>
        <tr><th>Option</th><th width="25%">Price</th></tr>
        <?php foreach ($pkg['pack_price'] as $opt): ?>
        <tr>
            <td><?= esc($opt['title'] ?? '—') ?></td>
            <td><strong><?= money((float)($opt['price'] ?? 0), $currency) ?></strong></td>
        </tr>
        <?php endforeach; ?>
    </table>
    <?php endif; ?>

<?php } // end template switch

$bodyHtml = ob_get_clean();

// ── Generate with mPDF ────────────────────────────────────────────────
$mpdf = new \Mpdf\Mpdf([
    'mode'          => 'utf-8',
    'format'        => 'A4',
    'margin_top'    => 35,
    'margin_bottom' => 20,
    'margin_left'   => 15,
    'margin_right'  => 15,
    'default_font'  => 'dejavusans',
]);

// Set logo as variable image (mPDF 8+ — no SetImportUse needed)
if ($logoPath) {
    $mpdf->imageVars['logo'] = file_get_contents($logoPath);
}

$mpdf->SetHTMLHeader($headerHtml);
$mpdf->SetHTMLFooter($footerHtml);
$mpdf->WriteHTML('<style>' . $css . '</style>');
$mpdf->WriteHTML($bodyHtml);

$safeTitle = preg_replace('/[^a-zA-Z0-9\-_]/', '_', $pkg['title'] ?? 'package');
$filename  = 'TravHub_' . $safeTitle . '_' . date('Ymd') . '.pdf';

$mpdf->Output($filename, \Mpdf\Output\Destination::INLINE);