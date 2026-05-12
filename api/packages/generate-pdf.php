<?php
/**
 * API Endpoint: Generate Package PDF
 * 
 * This script generates a printable PDF itinerary for a travel package.
 * 
 * Query Parameters:
 *   - sys_id (required): The package system ID (e.g., THR-PK-26-00K001)
 *   - template (optional): 'detailed' (default) or 'bullet' for compact view
 * 
 * Usage: /api/packages/generate-pdf.php?sys_id=THR-PK-26-00K001&template=detailed
 */

// ============================================================
// 1. SETUP & DEPENDENCIES
// ============================================================

require_once('../../pages/vendor/autoload.php');      // mPDF library
require_once('../../server/db_connection.php');       // Database connection

// ============================================================
// 2. VALIDATE INPUT PARAMETERS
// ============================================================

$sys_id   = trim($_GET['sys_id'] ?? '');               // Package identifier
$template = trim($_GET['template'] ?? 'detailed');     // PDF style template

// Validate template type - only allowed values
if (!in_array($template, ['detailed', 'bullet'])) {
    $template = 'detailed';  // Default to detailed if invalid
}

// Require sys_id parameter
if (!$sys_id) {
    http_response_code(400);
    die('Error: sys_id parameter is required');
}

// ============================================================
// 3. FETCH PACKAGE DATA FROM DATABASE
// ============================================================

$stmt = $pdo->prepare("SELECT * FROM packages WHERE sys_id = ? LIMIT 1");
$stmt->execute([$sys_id]);
$pkg = $stmt->fetch(PDO::FETCH_ASSOC);

// Handle package not found
if (!$pkg) {
    http_response_code(404);
    die('Error: Package not found');
}

// $packIti = json_decode($pkg['pack_itenaries']);

// var_dump($packIti[0]->flights);
// die;

// ============================================================
// 4. DECODE JSON FIELDS INTO PHP ARRAYS
// ============================================================

$json_fields = [
    'countries', 'cities', 'no_of_pax', 'hotels', 
    'pack_price', 'pack_itenaries', 'pack_inclusions', 
    'pack_exclusions', 'package_calculations_details'
];

foreach ($json_fields as $field) {
    $pkg[$field] = json_decode($pkg[$field] ?? 'null', true) ?: [];
}

// Extract pricing calculation data
$calcData   = $pkg['package_calculations_details'];
$actRows    = $calcData['details']['activity'] ?? [];      // Activity pricing
$hotelRows  = $calcData['details']['hotel'] ?? [];         // Hotel pricing
$grandTotal = $calcData['grand_total'] ?? 0;               // Total package price
$currency   = $pkg['currency_symbol'] ?? '৳';              // Currency symbol (Taka)
$currCode   = $pkg['currency_code'] ?? 'BDT';              // Currency code

// ============================================================
// 5. HELPER FUNCTIONS
// ============================================================

/**
 * Escape HTML special characters for safe output
 */
function esc(string $s): string 
{
    return htmlspecialchars($s, ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

/**
 * Format number as currency with symbol
 */
function money(float $n, string $sym = '৳'): string 
{
    return $sym . number_format($n, 2);
}

/**
 * Format time string to human-readable format (h:i A)
 */
function fmtTime(?string $t): string 
{
    if (!$t) return '';
    try {
        return (new DateTime($t))->format('h:i A');
    } catch (Exception $e) {
        return $t;
    }
}

/**
 * Format date string to human-readable format (d M Y)
 */
function fmtDate(?string $d): string 
{
    if (!$d) return '';
    try {
        return (new DateTime($d))->format('d M Y');
    } catch (Exception $e) {
        return $d;
    }
}

// ============================================================
// 6. COMPANY INFORMATION (Customize this section)
// ============================================================

$logoPath  = realpath(__DIR__ . '/../../assets/images/logo/logo.png');
$navy      = '#1A2039';
$phone     = '+880 1611 482 773';
$email     = 'info@travhub.com.bd';
$address   = '5th Floor, House 1, Road 6, Sector 3, Uttara, Dhaka-1230';
$website   = 'www.travhub.com.bd';

// ============================================================
// 7. PREPARE PACKAGE DATA
// ============================================================

$isBullet   = ($template === 'bullet');
$title      = esc($pkg['title'] ?? 'Travel Package');
$desc       = $pkg['full_description'] ?: ($pkg['description'] ?? '');
$countries  = implode(' · ', array_map(fn($c) => esc($c['name'] ?? ''), $pkg['countries']));
$cities     = implode(', ', array_map(fn($c) => esc($c['name'] ?? ''), $pkg['cities']));
$duration   = esc($pkg['duration'] ?? '');
$startDate  = fmtDate($pkg['start_date'] ?? '');
$endDate    = fmtDate($pkg['end_date'] ?? '');

// Format passenger count
$pax        = $pkg['no_of_pax'];
$paxStr     = trim(($pax['adult'] ?? 0) . ' Adult' . 
              (($pax['child'] ?? 0) ? ', ' . $pax['child'] . ' Child' : '') . 
              (($pax['infant'] ?? 0) ? ', ' . $pax['infant'] . ' Infant' : ''));

// ============================================================
// 8. PDF STYLESHEET (CSS)
// ============================================================

// @page { 
//     margin-top: 38mm; 
//     margin-bottom: 18mm; 
//     margin-left: 18mm; 
//     margin-right: 18mm; 
// }
$css = "

body { 
    font-family: 'dejavusans', sans-serif; 
    font-size: 9pt; 
    color: #1a1a1a; 
    line-height: 1.55; 
}

h1 { 
    font-size: 19pt; 
    font-weight: 700; 
    color: {$navy}; 
    margin: 0 0 3pt; 
}

h2 { 
    font-size: 11pt; 
    font-weight: 700; 
    color: {$navy}; 
    margin: 12pt 0 5pt; 
    padding-bottom: 3pt; 
    border-bottom: 1pt solid #50BC81; 
}

h3 { 
    font-size: 9.5pt; 
    font-weight: 700; 
    color: #1a1a1a; 
    margin: 6pt 0 3pt; 
}

p { 
    margin: 1.5pt 0; 
}

table { 
    width: 100%; 
    border-collapse: collapse; 
}

th { 
    background: {$navy}; 
    color: #fff; 
    padding: 4pt 6pt; 
    font-size: 8pt; 
    font-weight: 700; 
    text-align: left; 
    letter-spacing: 0.3pt; 
}

td { 
    padding: 3.5pt 6pt; 
    font-size: 8.5pt; 
    border-bottom: 0.3pt solid #ddd; 
    vertical-align: top; 
}

tr:last-child td { 
    border-bottom: none; 
}

// tr:nth-child(even) td { 
//     background: #f7f7f7; 
// }

.info-label { 
    font-size: 7pt; 
    font-weight: 700; 
    text-transform: uppercase; 
    letter-spacing: 0.5pt; 
    color: #888; 
}

.info-value { 
    font-size: 9pt; 
    font-weight: 600; 
    color: #1a1a1a; 
}

.day-block { 
    margin-bottom: 10pt; 
    page-break-inside: avoid; 
}

.day-num { 
    font-size: 7pt; 
    font-weight: 700; 
    text-transform: uppercase; 
    letter-spacing: 1pt; 
    color: #888; 
}

.day-title { 
    font-size: 10.5pt; 
    font-weight: 700; 
    color: {$navy}; 
    margin: 1pt 0 4pt; 
}

.day-meta { 
    font-size: 8pt; 
    color: #555; 
    margin-bottom: 4pt; 
}

.act-entry { 
    margin-bottom: 4pt; 
    padding-left: 8pt; 
    border-left: 1.5pt solid #ddd; 
}

.act-name { 
    font-weight: 700; 
    font-size: 9pt; 
    color: #1a1a1a; 
}

.act-meta { 
    font-size: 7.5pt; 
    color: #666; 
}

.tr-entry { 
    margin-bottom: 3pt; 
    padding-left: 8pt; 
    border-left: 1.5pt solid #bbb; 
    font-size: 8.5pt; 
}

.fl-entry { 
    margin-bottom: 5pt; 
    padding-left: 8pt; 
    border-left: 1.5pt solid #bbb; 
}

.fl-num { 
    font-weight: 700; 
    font-size: 9pt; 
}

.price-total { 
    font-size: 13pt; 
    font-weight: 700; 
    color: {$navy}; 
}

.price-label { 
    font-size: 8pt; 
    color: #666; 
}

.inc-item { 
    font-size: 8.5pt; 
    color: #1a1a1a; 
    margin-bottom: 1.5pt; 
}

.exc-item { 
    font-size: 8.5pt; 
    color: #555; 
    margin-bottom: 1.5pt; 
}

.sub-item { 
    font-size: 7.5pt; 
    color: #777; 
    padding-left: 10pt; 
}

.cover { 
    border-bottom: 1pt solid #ddd; 
    padding-bottom: 8pt; 
    margin-bottom: 10pt; 
    text-align: center;
}

.cover h1 {
    font-weight: bold;
}

.cover-sub { 
    font-size: 9pt; 
    color: #555; 
    margin-top: 3pt; 
}

.bl-day { 
    margin-bottom: 7pt; 
}

.bl-hdr { 
    background: {$navy}; 
    color: #fff; 
    padding: 3pt 7pt; 
    font-weight: 700; 
    font-size: 8.5pt; 
    margin-bottom: 3pt; 
}

.bl-item { 
    font-size: 8.5pt; 
    padding: 1pt 0 1pt 10pt; 
    color: #1a1a1a; 
    border-bottom: 0.3pt solid #eee; 
}

.bl-badge { 
    font-size: 7pt; 
    color: #888; 
}

/* Container styling */
.icon-container {
  display: flex;
  gap: 30px;
  justify-content: center;
  padding: 50px;
  background-color: #f0f0f0;
}

.uppercase{
    text-transform: uppercase;
}

.bg-dark {
    background-color: #1A2039;
}

.bg-ash{
    background-color : #EEEEEE;
}

.text-white{
    color : #FFFFFF;
}

.text-dark{
    color : #1A2039;
}

.bold{
    font-weight: bold;
}

.twenty-per{
    width: 20%;
}

.twenty-five-per{
    width: 25%;
}

.white-border{
    border-collapse: collapse; 
    border: 2px solid #FFF;
}

";

// ============================================================
// 9. PDF HEADER (Same for all pages)
// ============================================================

$firstPageHeader = '
<table width="100%">
    <tr>
        <td width="25%" style="vertical-align: middle; border-bottom: 0px;">
            ' . ($logoPath && file_exists($logoPath) ? 
                '<img src="var:logo" style="height: 90px; width: auto;" />' : 
                '<div style="font-size: 14pt; font-weight: bold; color: ' . $navy . ';">TRAVHUB</div>') . '
        </td>
        <td width="75%" style="vertical-align: middle; text-align: right; font-size: 12px; color: #555; border-bottom: 2px solid #ddd; ">
            <div>' . $phone . '</div>
            <div>' . $website . '</div>
            <div>' . $email . '</div>
            <div>' . $address . '</div>
        </td>
    </tr>
</table>

<!-- Add some spacing after header -->
<div style="margin-top: 15px; border: none; border-top: 2px solid #50BC81; !important"></div>
<div style="height: 5mm;"></div>
';

// ============================================================
// INTERIOR PAGES HEADER (Minimal header)
// ============================================================

$minimalHeader = '
<div class="minimal-header">
    <table width="100%">
        <tr>
            <td style="text-align: left;">' . $title . '</td>
            <td style="text-align: right;">Page {PAGENO}</td>
        </tr>
    </table>
</div>
';

// ============================================================
// FOOTER (Same for all pages)
// ============================================================

$footer = '
<div class="footer">
    <table width="100%" style="border-bottom: 0px;">
        <tr>
            <td collapse="2"></td>
        </tr>
        <tr>
            <td style="text-align: left; padding-top: 3pt; font-size: 6.5pt; color: #bbb; border-bottom: 0px;">
                © ' . date('Y') . ' TravHub. All rights reserved. | Terms & Conditions Apply |' . esc(substr($pkg['sys_id'], 7)) .
            '</td>
            <td style="text-align: right; border-top: 0.3pt solid #ddd; border-left: 0.5pt solid #ddd; border-bottom: 0px;">Page {PAGENO}</td>
        </tr>
    </table>
</div>
';

// ============================================================
// MAIN CONTENT (Your existing itinerary HTML)
// ============================================================


// ============================================================
// 11. GENERATE PDF CONTENT (HTML)
// ============================================================

ob_start();

// ---- BULLET STYLE (Compact) ----
if ($isBullet):

?>
<div class="cover">
    <h1><?= $title ?></h1>
    <div style="border: none; border-top: 2px solid #50BC81; !important"></div>
    <?php if ($desc): ?>
        <p class="cover-sub"><?= esc(substr(strip_tags($desc), 0, 200)) ?>…</p>
    <?php endif; ?>
</div>

<!-- Package Summary Table -->
<table style="margin-bottom:10pt;">
  <tr>
    <td width="25%"><p class="info-label">Destination</p><p class="info-value"><?= $cities ?: $countries ?></p></td>
    <td width="20%"><p class="info-label">Duration</p><p class="info-value"><?= $duration ?: '—' ?></p></td>
    <td width="25%"><p class="info-label">Dates</p><p class="info-value"><?= $startDate ?> – <?= $endDate ?></p></td>
    <td width="15%"><p class="info-label">Pax</p><p class="info-value"><?= esc($paxStr) ?></p></td>
    <td width="15%"><p class="info-label">Days</p><p class="info-value"><?= count($pkg['pack_itenaries']) ?></p></td>
  </tr>
</table>

<!-- Itinerary (Bullet Style) -->
<h2>Itinerary</h2>
<?php foreach ($pkg['pack_itenaries'] as $day): ?>
<div class="bl-day">
    <div class="bl-hdr">
        Day <?= (int)$day['day_number'] ?>
        <?= $day['title'] ? ' &mdash; ' . esc($day['title']) : '' ?>
        <?= $day['date'] ? ' &bull; ' . fmtDate($day['date']) : '' ?>
    </div>

    <!-- Flights -->
    <?php foreach (($day['flights'] ?? []) as $fl): ?>
    <div class="bl-item">
        &#9992; <strong><?= esc($fl['flight_number'] ?: 'Flight') ?></strong>
        <span class="bl-badge">
            <?= esc($fl['dep_airport'] ?? '') ?>
            <?= $fl['dep_time'] ? ' ' . fmtTime($fl['dep_time']) : '' ?> 
            &rarr; 
            <?= esc($fl['arr_airport'] ?? '') ?>
            <?= $fl['arr_time'] ? ' ' . fmtTime($fl['arr_time']) : '' ?>
        </span>
        <?php foreach (($fl['transits'] ?? []) as $ti => $tr): ?>
        <br><span style="padding-left:12pt;font-size:7.5pt;color:#777;">
            &#8627; Transit <?= $ti + 1 ?>: <?= esc($tr['dep_airport'] ?? '') ?> 
            &rarr; <?= esc($tr['arr_airport'] ?? '') ?>
        </span>
        <?php endforeach; ?>
    </div>
    <?php endforeach; ?>

    <!-- Activities -->
    <?php foreach (($day['activities'] ?? []) as $act):
        $isCustomAct = ($act['entry_type'] ?? '') === 'custom';
    ?>
    <div class="bl-item">
        &#9679; <strong><?= esc($act['name']) ?></strong>
        <span class="bl-badge">
            <?= $act['location'] ? ' &bull; ' . esc($act['location']) : '' ?>
            <?= $act['start_time'] ? ' &bull; ' . fmtTime($act['start_time']) . 
                ($act['end_time'] ? '&ndash;' . fmtTime($act['end_time']) : '') : '' ?>
        </span>
        <?php if ($isCustomAct && !empty($act['description'])): ?>
        <br><span style="padding-left:10pt;font-size:7.5pt;color:#555;">
            <?= strip_tags($act['description']) ?>
        </span>
        <?php elseif (!empty($act['note'])): ?>
        <br><span style="padding-left:10pt;font-size:7.5pt;color:#777;font-style:italic;">
            <?= esc($act['note']) ?>
        </span>
        <?php endif; ?>
    </div>
    <?php endforeach; ?>

    <!-- Transfers -->
    <?php foreach (($day['transfers'] ?? []) as $tr): ?>
    <div class="bl-item">
        &#8644; <?= esc($tr['title'] ?: 'Transfer') ?> 
        <span class="bl-badge">
            [<?= strtoupper($tr['type'] ?? 'SIC') ?>]
            <?= ($tr['start_time'] && $tr['end_time']) ? 
                ' ' . fmtTime($tr['start_time']) . '&ndash;' . fmtTime($tr['end_time']) : '' ?>
        </span>
    </div>
    <?php endforeach; ?>

    <!-- Overnight Stay -->
    <?php if ($day['overnight_stay']): ?>
    <div class="bl-item">&#127963; Overnight: <?= esc($day['overnight_stay']) ?></div>
    <?php endif; ?>

    <!-- Meals -->
    <?php if (!empty($day['meals'])): ?>
    <div class="bl-item">&#127860; Meals: <?= esc(implode(', ', $day['meals'])) ?></div>
    <?php endif; ?>
</div>
<?php endforeach; ?>

<!-- Hotels Section -->
<?php if (!empty($pkg['hotels'])): ?>
<h2>Hotels</h2>
<?php foreach ($pkg['hotels'] as $h): ?>
<div class="bl-item">
    &#9744; <?= esc($h['hotel_title'] ?? '—') ?> 
    <span class="bl-badge">
        &bull; <?= esc($h['city_name'] ?? '') ?>
        <?= $h['check_in'] ? ' &bull; ' . fmtDate($h['check_in']) . '&ndash;' . fmtDate($h['check_out']) : '' ?>
    </span>
</div>
<?php endforeach; ?>
<?php endif; ?>

<!-- Inclusions & Exclusions -->
<?php if (!empty($pkg['pack_inclusions']) || !empty($pkg['pack_exclusions'])): ?>
<table style="margin-top:8pt;">
  <tr>
    <td width="50%" style="vertical-align:top;padding-right:6pt;">
        <h2 style="color:#1a7a3c;">Inclusions</h2>
        <?php foreach ($pkg['pack_inclusions'] as $it): ?>
        <div class="bl-item">&#10003; <?= esc($it['title'] ?? $it) ?></div>
        <?php if (!empty($it['sub_titles'])): ?>
            <?php foreach ($it['sub_titles'] as $s): ?>
            <div class="sub-item">&#8627; <?= esc($s) ?></div>
            <?php endforeach; ?>
        <?php endif; ?>
        <?php endforeach; ?>
    </td>
    <td width="50%" style="vertical-align:top;padding-left:6pt;border-left:0.5pt solid #ddd;">
        <h2 style="color:#7a1a1a;">Exclusions</h2>
        <?php foreach ($pkg['pack_exclusions'] as $it): ?>
        <div class="bl-item">&#10007; <?= esc(is_array($it) ? ($it['title'] ?? '') : $it) ?></div>
        <?php endforeach; ?>
    </td>
  </tr>
</table>
<?php endif; ?>

<!-- Pricing Summary (Bullet) -->
<?php if ($actRows || $hotelRows || $grandTotal): ?>
<h2>Pricing Summary</h2>

<?php if ($actRows): ?>
<table style="margin-bottom:6pt;">
  <tr><th>Activity</th><th width="12%">Pax</th><th width="22%" style="text-align:right;">Sale/Person</th></tr>
    <?php foreach ($actRows as $r): ?>
    <tr>
        <td><?= esc($r['particular'] ?? '—') ?></td>
        <td style="text-align:center;"><?= (int)($r['total_pax'] ?? 1) ?></td>
        <td style="text-align:right;"><?= money((float)($r['sale_price_per_person'] ?? 0), $currency) ?></td>
    </tr>
    <?php endforeach; ?>
</table>
<?php endif; ?>

<?php if ($hotelRows): ?>
<table style="margin-bottom:6pt;">
  <tr><th>Hotel</th><th width="18%">Nights</th><th width="22%" style="text-align:right;">Sale/Night</th></tr>
    <?php foreach ($hotelRows as $r): ?>
    <tr>
        <td><?= esc($r['hotel_name'] ?? '—') ?></td>
        <td style="text-align:center;"><?= (int)($r['no_of_nights'] ?? 0) ?></td>
        <td style="text-align:right;"><?= money((float)($r['sale_price_per_night_bdt'] ?? 0), $currency) ?></td>
    </tr>
    <?php endforeach; ?>
</table>
<?php endif; ?>

<p style="text-align:right;">
    <span class="price-label">Grand Total</span><br>
    <span class="price-total"><?= money((float)$grandTotal, $currency) ?> <?= esc($currCode) ?></span>
</p>
<?php endif; ?>

<?php 
// ---- DETAILED STYLE (Full details) ----
else:
?>

<div class="cover">
    <h1><?= $title ?></h1>
    <p>
        <?= $startDate ?> &ndash; <?= $endDate ?> | <?= esc($paxStr) ?> | <?= $countries; ?>
    </p>
    <div style="border: none; border-top: 2px solid #50BC81; !important"></div>
    <?php if ($desc): ?>
        <p class="cover-sub"><?= nl2br(esc($desc)) ?></p>
    <?php endif; ?>
</div>

<!-- trip Overview Table -->
<h2 class="uppercase">
    Trip Overview
</h2>

<table style="margin-bottom: 12pt; width: 100%; border-collapse: collapse; border: 2px solid #FFF;">
    <tr class="bg-dark">
       <td class="text-white bold white-border">Details</td> 
       <td class="text-white bold white-border">Info</td>
       <td class="text-white bold white-border">Details</td> 
       <td class="text-white bold white-border">Info</td>
    </tr>
    <tr>
       <td class="bg-ash text-dark twenty-per white-border">Departure</td> 
       <td><?= $startDate ?></td> 
       <td class="bg-ash text-dark twenty-per white-border">Return</td> 
       <td><?= $endDate ?></td> 
    </tr>
    <tr>
       <td class="bg-ash text-dark twenty-per white-border">Duration</td> 
       <td><?= $duration ?: '—' ?></td> 
       <td class="bg-ash text-dark twenty-per white-border">Pax</td> 
       <td><?= esc($paxStr) ?></td> 
    </tr>
    <tr>
       <td class="bg-ash text-dark twenty-per white-border">Airline</td> 
       <td></td> 
       <td class="bg-ash text-dark twenty-per white-border">Buggage</td> 
       <td></td> 
    </tr>
    <tr>
       <td class="bg-ash text-dark twenty-per white-border">Countries</td> 
       <td colspan="3"><?= $countries; ?></td>
    </tr>
    <tr>
       <td class="bg-ash text-dark twenty-per white-border">Ground Transport </td> 
       <td colspan="3"></td>
    </tr>
    <tr>
       <td class="bg-ash text-dark twenty-per white-border">Hotel Check In</td> 
       <td colspan="3"></td>
    </tr>
</table>

<!--Flight Schedule Table-->
<h2 class="uppercase">
    Flight Schedule
</h2>

<table style="margin-bottom: 12pt; width: 100%; border-collapse: collapse; border: 2px solid #FFF;">
    <tr class="bg-dark">
        <td class="text-white bold white-border">#</td> 
        <td class="text-white bold white-border">Flight</td>
        <td class="text-white bold white-border">From</td> 
        <td class="text-white bold white-border">To</td>
        <td class="text-white bold white-border">Date</td>
        <td class="text-white bold white-border">Dep</td> 
        <td class="text-white bold white-border">Arr</td>
    </tr>
</table>





























<table style="margin-bottom:12pt;">
  <tr>
    <td width="25%"><p class="info-label">Destinations</p><p class="info-value"><?= $cities ?: $countries ?></p></td>
    <td width="20%"><p class="info-label">Duration</p><p class="info-value"><?= $duration ?: '—' ?></p></td>
    <td width="25%"><p class="info-label">Travel Dates</p><p class="info-value"><?= $startDate ?> &ndash; <?= $endDate ?></p></td>
    <td width="15%"><p class="info-label">Passengers</p><p class="info-value"><?= esc($paxStr) ?></p></td>
    <td width="15%"><p class="info-label">Total Days</p><p class="info-value"><?= count($pkg['pack_itenaries']) ?> day(s)</p></td>
  </tr>
</table>

<!-- Day-by-Day Itinerary (Detailed) -->
<h2>Day-by-Day Itinerary</h2>

<?php foreach ($pkg['pack_itenaries'] as $day): ?>
<div class="day-block">
    <p class="day-num">
        Day <?= (int)$day['day_number'] ?>
        <?= $day['date'] ? ' &bull; ' . fmtDate($day['date']) : '' ?>
    </p>
    <p class="day-title">
        <?= $day['title'] ? esc($day['title']) : 'Day ' . (int)$day['day_number'] ?>
    </p>

    <!-- Day Meta Information -->
    <?php
    $dayMode = $day['day_mode'] ?? 'library';
    $meta = [];
    
    if ($dayMode !== 'custom' && !empty($day['day_start_time'])) {
        $meta[] = 'Starts ' . fmtTime($day['day_start_time']);
    }
    if ($day['overnight_stay']) {
        $meta[] = 'Overnight: ' . esc($day['overnight_stay']);
    }
    if (!empty($day['meals'])) {
        $meta[] = 'Meals: ' . esc(implode(', ', $day['meals']));
    }
    if ($meta): ?>
    <p class="day-meta"><?= implode(' &bull; ', $meta) ?></p>
    <?php endif; ?>

    <!-- Flights Section -->
    <?php foreach (($day['flights'] ?? []) as $fl): ?>
    <div class="fl-entry">
        <p class="fl-num">&#9992; <?= esc($fl['flight_number'] ?: 'Flight') ?></p>
        <table width="100%" style="margin:2pt 0;">
            <tr>
                <td width="10%" style="font-size:7.5pt;font-weight:700;color:#555;">DEP</td>
                <td><?= esc($fl['dep_airport'] ?? '—') ?></td>
                <td width="22%"><?= fmtDate($fl['dep_date'] ?? '') ?></td>
                <td width="14%" style="text-align:right;font-weight:600;"><?= fmtTime($fl['dep_time'] ?? '') ?></td>
            </tr>
            
            <!-- Transits -->
            <?php foreach (($fl['transits'] ?? []) as $ti => $tr): ?>
            <tr style="background:#f9f9f9;">
                <td style="font-size:7pt;color:#888;">T<?= $ti + 1 ?></td>
                <td colspan="2" style="font-size:8pt;color:#555;">
                    <?= esc($tr['dep_airport'] ?? '') ?> &rarr; <?= esc($tr['arr_airport'] ?? '') ?>
                </td>
                <td style="font-size:8pt;color:#555;text-align:right;"><?= fmtTime($tr['dep_time'] ?? '') ?></td>
            </tr>
            <?php endforeach; ?>
            
            <tr>
                <td style="font-size:7.5pt;font-weight:700;color:#555;">ARR</td>
                <td><?= esc($fl['arr_airport'] ?? '—') ?></td>
                <td><?= fmtDate($fl['arr_date'] ?? '') ?></td>
                <td style="text-align:right;font-weight:600;"><?= fmtTime($fl['arr_time'] ?? '') ?></td>
            </tr>
        </table>
    </div>
    <?php endforeach; ?>

    <!-- Activities Section -->
    <?php foreach (($day['activities'] ?? []) as $act):
        $isCustomAct = ($act['entry_type'] ?? '') === 'custom';
    ?>
    <div class="act-entry">
        <p class="act-name">
            <?= esc($act['name']) ?>
            <?php if (!$isCustomAct && $act['type']): ?>
            <span style="font-size:7.5pt;font-weight:400;color:#888;">
                [<?= esc(ucfirst($act['type'])) ?>]
            </span>
            <?php endif; ?>
        </p>
        <p class="act-meta">
            <?php
            $details = [];
            if ($act['location']) $details[] = '&#128205; ' . esc($act['location']);
            if ($act['start_time']) {
                $details[] = '&#9200; ' . fmtTime($act['start_time']) . 
                            ($act['end_time'] ? '&ndash;' . fmtTime($act['end_time']) : '');
            }
            if (!$isCustomAct && $act['duration_hours']) $details[] = esc($act['duration_hours']) . 'h';
            echo implode(' &bull; ', $details);
            ?>
        </p>

        <?php if ($isCustomAct): ?>
            <!-- Custom activity: show rich text description -->
            <?php if (!empty($act['description'])): ?>
            <div style="font-size:8.5pt;color:#333;margin:3pt 0;line-height:1.55;padding-left:2pt;">
                <?= $act['description'] ?>
            </div>
            <?php endif; ?>

        <?php else: ?>
            <!-- Library activity: show note + child transfers -->
            <?php if ($act['note']): ?>
            <p style="font-size:7.5pt;color:#777;font-style:italic;margin:1pt 0;">
                <?= esc($act['note']) ?>
            </p>
            <?php endif; ?>
            
            <?php foreach (($act['transfers'] ?? []) as $tr):
                if (empty($tr['title']) && empty($tr['pricing'])) continue;
            ?>
            <div class="tr-entry" style="margin-top:3pt;">
                <p style="font-size:8.5pt;font-weight:700;margin:0;">
                    &#8644; <?= esc($tr['title'] ?: 'Transfer') ?>
                    <span style="font-weight:400;color:#777;font-size:7.5pt;">
                        [<?= strtoupper($tr['type'] ?? 'SIC') ?>]
                    </span>
                </p>
                
                <?php if (!empty($tr['pricing'])): ?>
                <table style="margin:2pt 0;font-size:8pt;">
                    <tr>
                        <th style="font-size:7pt;">Car</th>
                        <th style="font-size:7pt;">Type</th>
                        <th style="font-size:7pt;text-align:right;">Adult</th>
                        <th style="font-size:7pt;text-align:right;">Child</th>
                        <th style="font-size:7pt;text-align:right;">Full</th>
                    </tr>
                    <?php foreach ($tr['pricing'] as $p): ?>
                    <tr>
                        <td><?= esc($p['car_name'] ?? '—') ?></td>
                        <td><?= esc($p['car_type'] ?? '—') ?></td>
                        <td style="text-align:right;">
                            <?= isset($p['price_adult']) && $p['price_adult'] !== null 
                                ? money((float)$p['price_adult'], $currency) : '—' ?>
                        </td>
                        <td style="text-align:right;">
                            <?= isset($p['price_child']) && $p['price_child'] !== null 
                                ? money((float)$p['price_child'], $currency) : '—' ?>
                        </td>
                        <td style="text-align:right;">
                            <?= isset($p['price_full']) && $p['price_full'] !== null 
                                ? money((float)$p['price_full'], $currency) : '—' ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </table>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
    <?php endforeach; ?>

    <!-- Standalone Transfers -->
    <?php foreach (($day['transfers'] ?? []) as $tr): ?>
    <div class="tr-entry">
        <p style="font-size:9pt;font-weight:700;margin:0 0 1pt;">
            &#8644; <?= esc($tr['title'] ?: 'Transfer') ?>
            <span style="font-weight:400;color:#777;font-size:7.5pt;">
                [<?= strtoupper($tr['type'] ?? 'SIC') ?>]
                <?= ($tr['start_time'] && $tr['end_time']) ? 
                    ' ' . fmtTime($tr['start_time']) . '&ndash;' . fmtTime($tr['end_time']) : '' ?>
            </span>
        </p>
        <?php if ($tr['notes']): ?>
        <p style="font-size:7.5pt;color:#777;font-style:italic;margin:1pt 0;">
            <?= esc($tr['notes']) ?>
        </p>
        <?php endif; ?>
        
        <?php if (!empty($tr['pricing'])): ?>
        <table style="margin:2pt 0;font-size:8pt;">
            <tr>
                <th style="font-size:7pt;">Car</th>
                <th style="font-size:7pt;">Type</th>
                <th style="font-size:7pt;text-align:right;">Adult</th>
                <th style="font-size:7pt;text-align:right;">Child</th>
                <th style="font-size:7pt;text-align:right;">Full</th>
            </tr>
            <?php foreach ($tr['pricing'] as $p): ?>
            <tr>
                <td><?= esc($p['car_name'] ?? '—') ?></td>
                <td><?= esc($p['car_type'] ?? '—') ?></td>
                <td style="text-align:right;">
                    <?= isset($p['price_adult']) && $p['price_adult'] !== null 
                        ? money((float)$p['price_adult'], $currency) : '—' ?>
                </td>
                <td style="text-align:right;">
                    <?= isset($p['price_child']) && $p['price_child'] !== null 
                        ? money((float)$p['price_child'], $currency) : '—' ?>
                </td>
                <td style="text-align:right;">
                    <?= isset($p['price_full']) && $p['price_full'] !== null 
                        ? money((float)$p['price_full'], $currency) : '—' ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </table>
        <?php endif; ?>
    </div>
    <?php endforeach; ?>
</div>
<?php endforeach; ?>

<!-- Hotels Section -->
<?php if (!empty($pkg['hotels'])): ?>
<h2>Accommodation</h2>
<table>
    <tr>
        <th>Hotel</th>
        <th width="18%">City</th>
        <th width="18%">Check-in</th>
        <th width="18%">Check-out</th>
    </tr>
    <?php foreach ($pkg['hotels'] as $h): ?>
    <tr>
        <td><strong><?= esc($h['hotel_title'] ?? '—') ?></strong></td>
        <td><?= esc($h['city_name'] ?? '—') ?></td>
        <td><?= fmtDate($h['check_in'] ?? '') ?: '—' ?></td>
        <td><?= fmtDate($h['check_out'] ?? '') ?: '—' ?></td>
    </tr>
    <?php endforeach; ?>
</table>
<?php endif; ?>

<!-- Inclusions & Exclusions Section -->
<?php if (!empty($pkg['pack_inclusions']) || !empty($pkg['pack_exclusions'])): ?>
<h2>Inclusions &amp; Exclusions</h2>
<table>
  <tr>
    <td width="50%" style="vertical-align:top;padding-right:8pt;">
        <h3 style="color:#1a7a3c;border-bottom:0.5pt solid #ddd;padding-bottom:2pt;">
            &#10003; Inclusions
        </h3>
        <?php foreach ($pkg['pack_inclusions'] as $it): ?>
        <p class="inc-item">&#10003;&nbsp; <?= esc($it['title'] ?? $it) ?></p>
        <?php if (!empty($it['sub_titles'])): ?>
            <?php foreach ($it['sub_titles'] as $s): ?>
            <p class="sub-item">&#8627; <?= esc($s) ?></p>
            <?php endforeach; ?>
        <?php endif; ?>
        <?php endforeach; ?>
    </td>
    <td width="50%" style="vertical-align:top;padding-left:8pt;border-left:0.5pt solid #ddd;">
        <h3 style="color:#7a1a1a;border-bottom:0.5pt solid #ddd;padding-bottom:2pt;">
            &#10007; Exclusions
        </h3>
        <?php foreach ($pkg['pack_exclusions'] as $it): ?>
        <p class="exc-item">&#10007;&nbsp; <?= esc(is_array($it) ? ($it['title'] ?? '') : $it) ?></p>
        <?php endforeach; ?>
    </td>
  </tr>
</table>
<?php endif; ?>

<!-- Pricing Breakdown Section -->
<?php if ($actRows || $hotelRows): ?>
<h2>Pricing Breakdown</h2>

<?php if ($actRows): ?>
<p style="font-size:8.5pt;font-weight:700;color:#555;margin-bottom:3pt;">Activities</p>
<table style="margin-bottom:8pt;">
  <tr>
        <th>Activity / Particular</th>
        <th width="14%">Date</th>
        <th width="10%">Pax</th>
        <th width="20%" style="text-align:right;">Sale/Person</th>
    </tr>
    <?php foreach ($actRows as $r): ?>
    <tr>
        <td><?= esc($r['particular'] ?? '—') ?></td>
        <td><?= fmtDate($r['date'] ?? '') ?: '—' ?></td>
        <td style="text-align:center;"><?= (int)($r['total_pax'] ?? 1) ?></td>
        <td style="text-align:right;font-weight:600;">
            <?= money((float)($r['sale_price_per_person'] ?? 0), $currency) ?>
        </td>
    </tr>
    <?php endforeach; ?>
</table>
<?php endif; ?>

<?php if ($hotelRows): ?>
<p style="font-size:8.5pt;font-weight:700;color:#555;margin-bottom:3pt;">Hotels</p>
<table style="margin-bottom:8pt;">
  <tr>
        <th>Hotel</th>
        <th width="14%">Check-in</th>
        <th width="14%">Check-out</th>
        <th width="10%">Nights</th>
        <th width="10%">Rooms</th>
        <th width="18%" style="text-align:right;">Sale/Night</th>
    </tr>
    <?php foreach ($hotelRows as $r): ?>
    <tr>
        <td>
            <?= esc($r['hotel_name'] ?? '—') ?>
            <span style="font-size:7.5pt;color:#888;"><?= esc($r['room_type'] ?? '') ?></span>
        </td>
        <td><?= fmtDate($r['check_in'] ?? '') ?: '—' ?></td>
        <td><?= fmtDate($r['check_out'] ?? '') ?: '—' ?></td>
        <td style="text-align:center;"><?= (int)($r['no_of_nights'] ?? 0) ?></td>
        <td style="text-align:center;"><?= (int)($r['no_of_rooms'] ?? 1) ?></td>
        <td style="text-align:right;font-weight:600;">
            <?= money((float)($r['sale_price_per_night_bdt'] ?? 0), $currency) ?>
        </td>
    </tr>
    <?php endforeach; ?>
<table>
<?php endif; ?>

<table>
    <tr>
        <td style="text-align:right;padding-top:4pt;">
            <span class="price-label">Grand Total</span><br>
            <span class="price-total">
                <?= money((float)$grandTotal, $currency) ?> <?= esc($currCode) ?>
            </span>
        </td>
    </tr>
</table>
<?php endif; ?>

<!-- Package Options Section -->
<?php if (!empty($pkg['pack_price'])): ?>
<h2>Package Options</h2>
<table>
    <tr>
        <th>Option</th>
        <th width="28%" style="text-align:right;">Price</th>
    </tr>
    <?php foreach ($pkg['pack_price'] as $opt): ?>
    <tr>
        <td><?= esc($opt['title'] ?? '—') ?></td>
        <td style="text-align:right;font-weight:600;">
            <?= money((float)($opt['price'] ?? 0), $currency) ?>
        </td>
    </tr>
    <?php endforeach; ?>
</table>
<?php endif; ?>

<?php endif; // End detailed style

$content = ob_get_clean();

// ============================================================
// 12. GENERATE AND OUTPUT PDF
// ============================================================

// Initialize mPDF
$mpdf = new \Mpdf\Mpdf([
    'mode'           => 'utf-8',
    'format'         => 'A4',
    'margin_top'     => 45,     // হেডার এর জন্য জায়গা
    'margin_bottom'  => 25,     // ফুটার এর জন্য পর্যাপ্ত জায়গা (২০ থেকে বাড়িয়ে ২৫ করা হয়েছে)
    'margin_left'    => 15,
    'margin_right'   => 15,
    'margin_header'  => 10,
    'margin_footer'  => 10,
    'default_font'   => 'dejavusans',
]);

// লোগো সেট করা
if ($logoPath && file_exists($logoPath)) {
    $mpdf->imageVars['logo'] = file_get_contents($logoPath);
}

// ১. হেডার এবং ফুটার ডিফাইন করা (নাম দিয়ে)
$mpdf->DefHTMLHeaderByName('firstpage', $firstPageHeader);
$mpdf->DefHTMLHeaderByName('otherpages', $minimalHeader);
$mpdf->DefHTMLFooterByName('allpages', $footer); // ফুটারকেও নাম দিন

// ২. প্রথম পেজের জন্য হেডার ও ফুটার সেট করা
$mpdf->SetHTMLHeaderByName('firstpage');
$mpdf->SetHTMLFooterByName('allpages');

// ৩. সিএসএস এবং মেইন কন্টেন্ট রাইট করা
$mpdf->WriteHTML('<style>' . $css . '</style>');

/**
 * এখানে একটি ট্রিক আছে: 
 * কন্টেন্টের একদম শুরুতে আমরা mPDF কে বলে দিচ্ছি যেন পরের পেজ থেকে হেডার পরিবর্তন হয়।
 */
$htmlContent = '
<sethtmlheader name="firstpage" value="on" show-this-page="1" />
<sethtmlheader name="otherpages" value="on" />
<sethtmlfooter name="allpages" value="on" />
' . $content;

$mpdf->WriteHTML($htmlContent);

// আউটপুট
$safeTitle = preg_replace('/[^a-zA-Z0-9\-_]/', '_', $pkg['title'] ?? 'package');
$filename = 'TravHub_' . $safeTitle . '_' . date('Ymd') . '.pdf';
$mpdf->Output($filename, \Mpdf\Output\Destination::INLINE);