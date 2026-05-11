<?php
// api/packages/generate-pdf.php
// GET ?sys_id=THR-PK-26-00K001&template=detailed|bullet

require_once('../../pages/vendor/autoload.php');
require_once('../../server/db_connection.php');

$sys_id   = trim($_GET['sys_id']   ?? '');
$template = trim($_GET['template'] ?? 'detailed');
if (!in_array($template, ['detailed','bullet'])) $template = 'detailed';
if (!$sys_id) { http_response_code(400); die('sys_id required'); }

$stmt = $pdo->prepare("SELECT * FROM packages WHERE sys_id = ? LIMIT 1");
$stmt->execute([$sys_id]);
$pkg = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$pkg) { http_response_code(404); die('Package not found'); }

foreach (['countries','cities','no_of_pax','hotels','pack_price',
          'pack_itenaries','pack_inclusions','pack_exclusions',
          'package_calculations_details'] as $f) {
    $pkg[$f] = json_decode($pkg[$f] ?? 'null', true) ?: [];
}

$calcData   = is_string($pkg['package_calculations_details'])
                ? json_decode($pkg['package_calculations_details'], true)
                : $pkg['package_calculations_details'];
$actRows    = $calcData['details']['activity'] ?? [];
$hotelRows  = $calcData['details']['hotel']    ?? [];
$grandTotal = $calcData['grand_total']         ?? 0;
$currency   = $pkg['currency_symbol']          ?? '৳';
$currCode   = $pkg['currency_code']            ?? 'BDT';

function esc(string $s): string { return htmlspecialchars($s, ENT_QUOTES|ENT_HTML5, 'UTF-8'); }
function money(float $n, string $sym='৳'): string { return $sym.number_format($n,2); }
function fmtTime(?string $t): string {
    if (!$t) return ''; try { return (new DateTime($t))->format('h:i A'); } catch(Exception $e){return $t;}
}
function fmtDate(?string $d): string {
    if (!$d) return ''; try { return (new DateTime($d))->format('d M Y'); } catch(Exception $e){return $d;}
}

$logoPath  = realpath(__DIR__.'/../../assets/images/pad-v2.png');
$navy      = '#1A2039';
$phone     = '+880 1611 482 773';
$email     = 'info@travhub.com.bd';
$address   = '5th Floor, House 1, Road 6, Sector 3, Uttara, Dhaka-1230';

$isBullet  = ($template === 'bullet');
$title     = esc($pkg['title'] ?? 'Travel Package');
$desc      = $pkg['full_description'] ?: ($pkg['description'] ?? '');
$countries = implode(', ', array_map(fn($c)=>esc($c['name']??''), $pkg['countries']));
$cities    = implode(', ', array_map(fn($c)=>esc($c['name']??''), $pkg['cities']));
$duration  = esc($pkg['duration'] ?? '');
$startDate = fmtDate($pkg['start_date']??'');
$endDate   = fmtDate($pkg['end_date']??'');
$pax       = $pkg['no_of_pax'];
$paxStr    = trim(($pax['adult']??0).' Adult'.(($pax['child']??0)?', '.$pax['child'].' Child':'').(($pax['infant']??0)?', '.$pax['infant'].' Infant':''));

$css = "
@page { margin-top:38mm; margin-bottom:18mm; margin-left:18mm; margin-right:18mm; }
body { font-family:'dejavusans',sans-serif; font-size:9pt; color:#1a1a1a; line-height:1.55; }
h1 { font-size:19pt; font-weight:700; color:{$navy}; margin:0 0 3pt; }
h2 { font-size:11pt; font-weight:700; color:{$navy}; margin:12pt 0 5pt; padding-bottom:3pt; border-bottom:1pt solid #1a1a1a; }
h3 { font-size:9.5pt; font-weight:700; color:#1a1a1a; margin:6pt 0 3pt; }
p  { margin:1.5pt 0; }
table { width:100%; border-collapse:collapse; }
th { background:{$navy}; color:#fff; padding:4pt 6pt; font-size:8pt; font-weight:700; text-align:left; letter-spacing:0.3pt; }
td { padding:3.5pt 6pt; font-size:8.5pt; border-bottom:0.3pt solid #ddd; vertical-align:top; }
tr:last-child td { border-bottom:none; }
tr:nth-child(even) td { background:#f7f7f7; }
.info-label { font-size:7pt; font-weight:700; text-transform:uppercase; letter-spacing:0.5pt; color:#888; }
.info-value { font-size:9pt; font-weight:600; color:#1a1a1a; }
.day-block  { margin-bottom:10pt; page-break-inside:avoid; }
.day-num    { font-size:7pt; font-weight:700; text-transform:uppercase; letter-spacing:1pt; color:#888; }
.day-title  { font-size:10.5pt; font-weight:700; color:{$navy}; margin:1pt 0 4pt; }
.day-meta   { font-size:8pt; color:#555; margin-bottom:4pt; }
.act-entry  { margin-bottom:4pt; padding-left:8pt; border-left:1.5pt solid #ddd; }
.act-name   { font-weight:700; font-size:9pt; color:#1a1a1a; }
.act-meta   { font-size:7.5pt; color:#666; }
.tr-entry   { margin-bottom:3pt; padding-left:8pt; border-left:1.5pt solid #bbb; font-size:8.5pt; }
.fl-entry   { margin-bottom:5pt; padding-left:8pt; border-left:1.5pt solid #bbb; }
.fl-num     { font-weight:700; font-size:9pt; }
.price-total{ font-size:13pt; font-weight:700; color:{$navy}; }
.price-label{ font-size:8pt; color:#666; }
.inc-item   { font-size:8.5pt; color:#1a1a1a; margin-bottom:1.5pt; }
.exc-item   { font-size:8.5pt; color:#555; margin-bottom:1.5pt; }
.sub-item   { font-size:7.5pt; color:#777; padding-left:10pt; }
.cover      { border-bottom:1pt solid #ddd; padding-bottom:8pt; margin-bottom:10pt; }
.cover-sub  { font-size:9pt; color:#555; margin-top:3pt; }
.bl-day     { margin-bottom:7pt; }
.bl-hdr     { background:{$navy}; color:#fff; padding:3pt 7pt; font-weight:700; font-size:8.5pt; margin-bottom:3pt; }
.bl-item    { font-size:8.5pt; padding:1pt 0 1pt 10pt; color:#1a1a1a; border-bottom:0.3pt solid #eee; }
.bl-badge   { font-size:7pt; color:#888; }
";

$headerHtml = "
<table width='100%' style='border-collapse:collapse;'>
  <tr>
    <td width='20%' style='vertical-align:middle;padding-right:10pt;'>"
  .($logoPath ? "<img src='var:logo' style='height:36pt;width:auto;'/>"
               : "<span style='font-size:13pt;font-weight:700;color:{$navy};'>TravHub</span>").
  "</td>
    <td style='border-left:0.5pt solid #ccc;padding-left:10pt;vertical-align:middle;'>
      <p style='margin:1pt 0;font-size:7.5pt;color:#555;'>&#9742; {$phone}</p>
      <p style='margin:1pt 0;font-size:7.5pt;color:#555;'>&#9993; {$email}</p>
      <p style='margin:1pt 0;font-size:7.5pt;color:#555;'>&#128205; {$address}</p>
    </td>
  </tr>
</table>
<hr style='border:0;border-top:0.5pt solid #ddd;margin:4pt 0 0;'/>
";

$footerHtml = "
<hr style='border:0;border-top:0.5pt solid #ddd;margin-bottom:3pt;'/>
<table width='100%'>
  <tr>
    <td style='font-size:7pt;color:#aaa;width:40%;'>{$title}</td>
    <td style='font-size:7pt;color:#aaa;text-align:center;'>{$address}</td>
    <td style='font-size:7pt;color:#aaa;text-align:right;'>Page {PAGENO} / {nbpg}</td>
  </tr>
</table>
";

ob_start();
if ($isBullet):
?>
<div class="cover">
    <h1><?= $title ?></h1>
    <?php if ($desc): ?><p class="cover-sub"><?= esc(substr(strip_tags($desc),0,200)) ?>…</p><?php endif; ?>
</div>
<table style="margin-bottom:10pt;">
  <tr>
    <td width="25%"><p class="info-label">Destination</p><p class="info-value"><?= $cities?:$countries ?></p></td>
    <td width="20%"><p class="info-label">Duration</p><p class="info-value"><?= $duration?:'—' ?></p></td>
    <td width="25%"><p class="info-label">Dates</p><p class="info-value"><?= $startDate ?> – <?= $endDate ?></p></td>
    <td width="15%"><p class="info-label">Pax</p><p class="info-value"><?= esc($paxStr) ?></p></td>
    <td width="15%"><p class="info-label">Days</p><p class="info-value"><?= count($pkg['pack_itenaries']) ?></p></td>
  </tr>
</table>

<h2>Itinerary</h2>
<?php foreach ($pkg['pack_itenaries'] as $day): ?>
<div class="bl-day">
  <div class="bl-hdr">Day <?= (int)$day['day_number'] ?><?= $day['title']?' &mdash; '.esc($day['title']):'' ?><?= $day['date']?' &bull; '.fmtDate($day['date']):'' ?></div>
  <?php foreach (($day['flights']??[]) as $fl): ?>
  <div class="bl-item">&#9992; <strong><?= esc($fl['flight_number']?:'Flight') ?></strong>
    <span class="bl-badge"> <?= esc($fl['dep_airport']??'') ?><?= $fl['dep_time']?' '.fmtTime($fl['dep_time']):'' ?> &rarr; <?= esc($fl['arr_airport']??'') ?><?= $fl['arr_time']?' '.fmtTime($fl['arr_time']):'' ?></span>
    <?php foreach (($fl['transits']??[]) as $ti=>$tr): ?>
    <br><span style="padding-left:12pt;font-size:7.5pt;color:#777;">&#8627; Transit <?= $ti+1 ?>: <?= esc($tr['dep_airport']??'') ?> &rarr; <?= esc($tr['arr_airport']??'') ?></span>
    <?php endforeach; ?>
  </div>
  <?php endforeach; ?>
  <?php foreach (($day['activities']??[]) as $act): ?>
  <div class="bl-item">&#9679; <strong><?= esc($act['name']) ?></strong>
    <span class="bl-badge"><?= $act['location']?' &bull; '.esc($act['location']):'' ?><?= $act['start_time']?' &bull; '.fmtTime($act['start_time']).($act['end_time']?'&ndash;'.fmtTime($act['end_time']):''):'' ?></span>
    <?php if ($act['note']): ?><br><span style="padding-left:10pt;font-size:7.5pt;color:#777;font-style:italic;"><?= esc($act['note']) ?></span><?php endif; ?>
  </div>
  <?php endforeach; ?>
  <?php foreach (($day['transfers']??[]) as $tr): ?>
  <div class="bl-item">&#8644; <?= esc($tr['title']?:'Transfer') ?> <span class="bl-badge">[<?= strtoupper($tr['type']??'SIC') ?>]<?= ($tr['start_time']&&$tr['end_time'])?' '.fmtTime($tr['start_time']).'&ndash;'.fmtTime($tr['end_time']):'' ?></span></div>
  <?php endforeach; ?>
  <?php if ($day['overnight_stay']): ?><div class="bl-item">&#127963; Overnight: <?= esc($day['overnight_stay']) ?></div><?php endif; ?>
  <?php if (!empty($day['meals'])): ?><div class="bl-item">&#127860; Meals: <?= esc(implode(', ',$day['meals'])) ?></div><?php endif; ?>
</div>
<?php endforeach; ?>

<?php if (!empty($pkg['hotels'])): ?>
<h2>Hotels</h2>
<?php foreach ($pkg['hotels'] as $h): ?>
<div class="bl-item">&#9744; <?= esc($h['hotel_title']??'—') ?> <span class="bl-badge">&bull; <?= esc($h['city_name']??'') ?><?= $h['check_in']?' &bull; '.fmtDate($h['check_in']).'&ndash;'.fmtDate($h['check_out']):'' ?></span></div>
<?php endforeach; ?>
<?php endif; ?>

<?php if (!empty($pkg['pack_inclusions'])||!empty($pkg['pack_exclusions'])): ?>
<table style="margin-top:8pt;">
  <tr>
    <td width="50%" style="vertical-align:top;padding-right:6pt;">
      <h2 style="color:#1a7a3c;">Inclusions</h2>
      <?php foreach ($pkg['pack_inclusions'] as $it): ?>
      <div class="bl-item">&#10003; <?= esc($it['title']??$it) ?></div>
      <?php if (!empty($it['sub_titles'])): foreach($it['sub_titles'] as $s): ?><div class="sub-item">&#8627; <?= esc($s) ?></div><?php endforeach; endif; ?>
      <?php endforeach; ?>
    </td>
    <td width="50%" style="vertical-align:top;padding-left:6pt;border-left:0.5pt solid #ddd;">
      <h2 style="color:#7a1a1a;">Exclusions</h2>
      <?php foreach ($pkg['pack_exclusions'] as $it): ?>
      <div class="bl-item">&#10007; <?= esc(is_array($it)?($it['title']??''):$it) ?></div>
      <?php endforeach; ?>
    </td>
  </tr>
</table>
<?php endif; ?>

<?php if ($actRows||$hotelRows||$grandTotal): ?>
<h2>Pricing Summary</h2>
<?php if ($actRows): ?>
<table style="margin-bottom:6pt;">
  <tr><th>Activity</th><th width="12%">Pax</th><th width="22%" style="text-align:right;">Sale/Person</th></tr>
  <?php foreach ($actRows as $r): ?>
  <tr><td><?= esc($r['particular']??'—') ?></td><td style="text-align:center;"><?= (int)($r['total_pax']??1) ?></td><td style="text-align:right;"><?= money((float)($r['sale_price_per_person']??0),$currency) ?></td></tr>
  <?php endforeach; ?>
</table>
<?php endif; ?>
<?php if ($hotelRows): ?>
<table style="margin-bottom:6pt;">
  <tr><th>Hotel</th><th width="18%">Nights</th><th width="22%" style="text-align:right;">Sale/Night</th></tr>
  <?php foreach ($hotelRows as $r): ?>
  <tr><td><?= esc($r['hotel_name']??'—') ?></td><td style="text-align:center;"><?= (int)($r['no_of_nights']??0) ?></td><td style="text-align:right;"><?= money((float)($r['sale_price_per_night_bdt']??0),$currency) ?></td></tr>
  <?php endforeach; ?>
</table>
<?php endif; ?>
<p style="text-align:right;"><span class="price-label">Grand Total</span><br><span class="price-total"><?= money((float)$grandTotal,$currency) ?> <?= esc($currCode) ?></span></p>
<?php endif; ?>

<?php else: // DETAILED ?>

<div class="cover">
  <h1><?= $title ?></h1>
  <?php if ($pkg['sys_id']): ?><p style="font-size:7.5pt;color:#aaa;margin:2pt 0;">Ref: <?= esc($pkg['sys_id']) ?></p><?php endif; ?>
  <?php if ($desc): ?><p class="cover-sub"><?= nl2br(esc($desc)) ?></p><?php endif; ?>
</div>

<table style="margin-bottom:12pt;">
  <tr>
    <td width="25%"><p class="info-label">Destinations</p><p class="info-value"><?= $cities?:$countries ?></p></td>
    <td width="20%"><p class="info-label">Duration</p><p class="info-value"><?= $duration?:'—' ?></p></td>
    <td width="25%"><p class="info-label">Travel Dates</p><p class="info-value"><?= $startDate ?> &ndash; <?= $endDate ?></p></td>
    <td width="15%"><p class="info-label">Passengers</p><p class="info-value"><?= esc($paxStr) ?></p></td>
    <td width="15%"><p class="info-label">Total Days</p><p class="info-value"><?= count($pkg['pack_itenaries']) ?> day(s)</p></td>
  </tr>
</table>

<h2>Day-by-Day Itinerary</h2>
<?php foreach ($pkg['pack_itenaries'] as $day): ?>
<div class="day-block">
  <p class="day-num">Day <?= (int)$day['day_number'] ?><?= $day['date']?' &bull; '.fmtDate($day['date']):'' ?></p>
  <p class="day-title"><?= $day['title']?esc($day['title']):'Day '.(int)$day['day_number'] ?></p>
  <?php
  $mp=[];
  if ($day['day_start_time']) $mp[]='Starts '.fmtTime($day['day_start_time']);
  if ($day['overnight_stay']) $mp[]='Overnight: '.esc($day['overnight_stay']);
  if (!empty($day['meals']))  $mp[]='Meals: '.esc(implode(', ',$day['meals']));
  if ($mp): ?><p class="day-meta"><?= implode(' &bull; ',$mp) ?></p><?php endif; ?>

  <?php foreach (($day['flights']??[]) as $fl): ?>
  <div class="fl-entry">
    <p class="fl-num">&#9992; <?= esc($fl['flight_number']?:'Flight') ?></p>
    <table width="100%" style="margin:2pt 0;">
      <tr><td width="10%" style="font-size:7.5pt;font-weight:700;color:#555;">DEP</td><td><?= esc($fl['dep_airport']??'—') ?></td><td width="22%"><?= fmtDate($fl['dep_date']??'') ?></td><td width="14%" style="text-align:right;font-weight:600;"><?= fmtTime($fl['dep_time']??'') ?></td></tr>
      <?php foreach (($fl['transits']??[]) as $ti=>$tr): ?>
      <tr style="background:#f9f9f9;"><td style="font-size:7pt;color:#888;">T<?= $ti+1 ?></td><td colspan="2" style="font-size:8pt;color:#555;"><?= esc($tr['dep_airport']??'') ?> &rarr; <?= esc($tr['arr_airport']??'') ?></td><td style="font-size:8pt;color:#555;text-align:right;"><?= fmtTime($tr['dep_time']??'') ?></td></tr>
      <?php endforeach; ?>
      <tr><td style="font-size:7.5pt;font-weight:700;color:#555;">ARR</td><td><?= esc($fl['arr_airport']??'—') ?></td><td><?= fmtDate($fl['arr_date']??'') ?></td><td style="text-align:right;font-weight:600;"><?= fmtTime($fl['arr_time']??'') ?></td></tr>
    </table>
  </div>
  <?php endforeach; ?>

  <?php foreach (($day['activities']??[]) as $act): ?>
  <div class="act-entry">
    <p class="act-name"><?= esc($act['name']) ?> <span style="font-size:7.5pt;font-weight:400;color:#888;"><?= $act['type']?'['.esc(ucfirst($act['type'])).']':'' ?></span></p>
    <p class="act-meta"><?php
      $am=[];
      if ($act['location'])       $am[]='&#128205; '.esc($act['location']);
      if ($act['start_time'])     $am[]='&#9200; '.fmtTime($act['start_time']).($act['end_time']?'&ndash;'.fmtTime($act['end_time']):'');
      if ($act['duration_hours']) $am[]=esc($act['duration_hours']).'h';
      echo implode(' &bull; ',$am);
    ?></p>
    <?php if ($act['note']): ?><p style="font-size:7.5pt;color:#777;font-style:italic;margin:1pt 0;"><?= esc($act['note']) ?></p><?php endif; ?>
    <?php foreach (($act['transfers']??[]) as $tr):
      if (empty($tr['title'])&&empty($tr['pricing'])) continue; ?>
    <div class="tr-entry" style="margin-top:3pt;">
      <p style="font-size:8.5pt;font-weight:700;margin:0;">&#8644; <?= esc($tr['title']?:'Transfer') ?> <span style="font-weight:400;color:#777;font-size:7.5pt;">[<?= strtoupper($tr['type']??'SIC') ?>]</span></p>
      <?php if (!empty($tr['pricing'])): ?>
      <table style="margin:2pt 0;font-size:8pt;">
        <tr><th style="font-size:7pt;">Car</th><th style="font-size:7pt;">Type</th><th style="font-size:7pt;text-align:right;">Adult</th><th style="font-size:7pt;text-align:right;">Child</th><th style="font-size:7pt;text-align:right;">Full</th></tr>
        <?php foreach ($tr['pricing'] as $p): ?>
        <tr><td><?= esc($p['car_name']??'—') ?></td><td><?= esc($p['car_type']??'—') ?></td>
        <td style="text-align:right;"><?= isset($p['price_adult'])&&$p['price_adult']!==null?money((float)$p['price_adult'],$currency):'—' ?></td>
        <td style="text-align:right;"><?= isset($p['price_child'])&&$p['price_child']!==null?money((float)$p['price_child'],$currency):'—' ?></td>
        <td style="text-align:right;"><?= isset($p['price_full']) &&$p['price_full'] !==null?money((float)$p['price_full'], $currency):'—' ?></td></tr>
        <?php endforeach; ?>
      </table>
      <?php endif; ?>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endforeach; ?>

  <?php foreach (($day['transfers']??[]) as $tr): ?>
  <div class="tr-entry">
    <p style="font-size:9pt;font-weight:700;margin:0 0 1pt;">&#8644; <?= esc($tr['title']?:'Transfer') ?> <span style="font-weight:400;color:#777;font-size:7.5pt;">[<?= strtoupper($tr['type']??'SIC') ?>]<?= ($tr['start_time']&&$tr['end_time'])?' '.fmtTime($tr['start_time']).'&ndash;'.fmtTime($tr['end_time']):'' ?></span></p>
    <?php if ($tr['notes']): ?><p style="font-size:7.5pt;color:#777;font-style:italic;margin:1pt 0;"><?= esc($tr['notes']) ?></p><?php endif; ?>
    <?php if (!empty($tr['pricing'])): ?>
    <table style="margin:2pt 0;font-size:8pt;">
      <tr><th style="font-size:7pt;">Car</th><th style="font-size:7pt;">Type</th><th style="font-size:7pt;text-align:right;">Adult</th><th style="font-size:7pt;text-align:right;">Child</th><th style="font-size:7pt;text-align:right;">Full</th></tr>
      <?php foreach ($tr['pricing'] as $p): ?>
      <tr><td><?= esc($p['car_name']??'—') ?></td><td><?= esc($p['car_type']??'—') ?></td>
      <td style="text-align:right;"><?= isset($p['price_adult'])&&$p['price_adult']!==null?money((float)$p['price_adult'],$currency):'—' ?></td>
      <td style="text-align:right;"><?= isset($p['price_child'])&&$p['price_child']!==null?money((float)$p['price_child'],$currency):'—' ?></td>
      <td style="text-align:right;"><?= isset($p['price_full']) &&$p['price_full'] !==null?money((float)$p['price_full'], $currency):'—' ?></td></tr>
      <?php endforeach; ?>
    </table>
    <?php endif; ?>
  </div>
  <?php endforeach; ?>
</div>
<?php endforeach; ?>

<?php if (!empty($pkg['hotels'])): ?>
<h2>Accommodation</h2>
<table>
  <tr><th>Hotel</th><th width="18%">City</th><th width="18%">Check-in</th><th width="18%">Check-out</th></tr>
  <?php foreach ($pkg['hotels'] as $h): ?>
  <tr><td><strong><?= esc($h['hotel_title']??'—') ?></strong></td><td><?= esc($h['city_name']??'—') ?></td><td><?= fmtDate($h['check_in']??'')?:'—' ?></td><td><?= fmtDate($h['check_out']??'')?:'—' ?></td></tr>
  <?php endforeach; ?>
</table>
<?php endif; ?>

<?php if (!empty($pkg['pack_inclusions'])||!empty($pkg['pack_exclusions'])): ?>
<h2>Inclusions &amp; Exclusions</h2>
<table>
  <tr>
    <td width="50%" style="vertical-align:top;padding-right:8pt;">
      <h3 style="color:#1a7a3c;border-bottom:0.5pt solid #ddd;padding-bottom:2pt;">&#10003; Inclusions</h3>
      <?php foreach ($pkg['pack_inclusions'] as $it): ?>
      <p class="inc-item">&#10003;&nbsp; <?= esc($it['title']??$it) ?></p>
      <?php if (!empty($it['sub_titles'])): foreach($it['sub_titles'] as $s): ?><p class="sub-item">&#8627; <?= esc($s) ?></p><?php endforeach; endif; ?>
      <?php endforeach; ?>
    </td>
    <td width="50%" style="vertical-align:top;padding-left:8pt;border-left:0.5pt solid #ddd;">
      <h3 style="color:#7a1a1a;border-bottom:0.5pt solid #ddd;padding-bottom:2pt;">&#10007; Exclusions</h3>
      <?php foreach ($pkg['pack_exclusions'] as $it): ?>
      <p class="exc-item">&#10007;&nbsp; <?= esc(is_array($it)?($it['title']??''):$it) ?></p>
      <?php endforeach; ?>
    </td>
  </tr>
</table>
<?php endif; ?>

<?php if ($actRows||$hotelRows): ?>
<h2>Pricing Breakdown</h2>
<?php if ($actRows): ?>
<p style="font-size:8.5pt;font-weight:700;color:#555;margin-bottom:3pt;">Activities</p>
<table style="margin-bottom:8pt;">
  <tr><th>Activity / Particular</th><th width="14%">Date</th><th width="10%">Pax</th><th width="20%" style="text-align:right;">Sale/Person</th></tr>
  <?php foreach ($actRows as $r): ?>
  <tr><td><?= esc($r['particular']??'—') ?></td><td><?= fmtDate($r['date']??'')?:'—' ?></td><td style="text-align:center;"><?= (int)($r['total_pax']??1) ?></td><td style="text-align:right;font-weight:600;"><?= money((float)($r['sale_price_per_person']??0),$currency) ?></td></tr>
  <?php endforeach; ?>
</table>
<?php endif; ?>
<?php if ($hotelRows): ?>
<p style="font-size:8.5pt;font-weight:700;color:#555;margin-bottom:3pt;">Hotels</p>
<table style="margin-bottom:8pt;">
  <tr><th>Hotel</th><th width="14%">Check-in</th><th width="14%">Check-out</th><th width="10%">Nights</th><th width="10%">Rooms</th><th width="18%" style="text-align:right;">Sale/Night</th></tr>
  <?php foreach ($hotelRows as $r): ?>
  <tr><td><?= esc($r['hotel_name']??'—') ?> <span style="font-size:7.5pt;color:#888;"><?= esc($r['room_type']??'') ?></span></td><td><?= fmtDate($r['check_in']??'')?:'—' ?></td><td><?= fmtDate($r['check_out']??'')?:'—' ?></td><td style="text-align:center;"><?= (int)($r['no_of_nights']??0) ?></td><td style="text-align:center;"><?= (int)($r['no_of_rooms']??1) ?></td><td style="text-align:right;font-weight:600;"><?= money((float)($r['sale_price_per_night_bdt']??0),$currency) ?></td></tr>
  <?php endforeach; ?>
</table>
<?php endif; ?>
<table><tr><td style="text-align:right;padding-top:4pt;"><span class="price-label">Grand Total</span><br><span class="price-total"><?= money((float)$grandTotal,$currency) ?> <?= esc($currCode) ?></span></td></tr></table>
<?php endif; ?>

<?php if (!empty($pkg['pack_price'])): ?>
<h2>Package Options</h2>
<table>
  <tr><th>Option</th><th width="28%" style="text-align:right;">Price</th></tr>
  <?php foreach ($pkg['pack_price'] as $opt): ?>
  <tr><td><?= esc($opt['title']??'—') ?></td><td style="text-align:right;font-weight:600;"><?= money((float)($opt['price']??0),$currency) ?></td></tr>
  <?php endforeach; ?>
</table>
<?php endif; ?>

<?php endif;

$bodyHtml = ob_get_clean();

$mpdf = new \Mpdf\Mpdf([
    'mode'          => 'utf-8',
    'format'        => 'A4',
    'margin_top'    => 38,
    'margin_bottom' => 18,
    'margin_left'   => 18,
    'margin_right'  => 18,
    'default_font'  => 'dejavusans',
]);

if ($logoPath) $mpdf->imageVars['logo'] = file_get_contents($logoPath);

$mpdf->SetHTMLHeader($headerHtml);
$mpdf->SetHTMLFooter($footerHtml);
$mpdf->WriteHTML('<style>'.$css.'</style>');
$mpdf->WriteHTML($bodyHtml);

$safeTitle = preg_replace('/[^a-zA-Z0-9\-_]/','_',$pkg['title']??'package');
$mpdf->Output('TravHub_'.$safeTitle.'_'.date('Ymd').'.pdf', \Mpdf\Output\Destination::INLINE);