<?php
/**
 * api/ai/flight-to-gds.php
 * POST { airline, segments:[{flight,class,date,route,dep,arr,arr_offset}],
 *        fares:[{type,pax,gross_fare,iata_charge,payable}], currency_code }
 * Returns { success, gds_text }
 */
session_start();
require_once '../../server/api_bootstrap.php';
require_once '../../server/ai-gemini.php';

header('Content-Type: application/json');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { echo json_encode(['success'=>false,'message'=>'POST only']); exit; }

$in       = json_decode(file_get_contents('php://input'), true) ?: [];
$airline  = trim($in['airline']       ?? '');
$segments = is_array($in['segments']  ?? null) ? $in['segments']  : [];
$fares    = is_array($in['fares']     ?? null) ? $in['fares']     : [];
$ccy      = strtoupper(trim($in['currency_code'] ?? 'BDT'));

if (empty($segments)) { echo json_encode(['success'=>false,'message'=>'segments required']); exit; }

$segLines = [];
foreach ($segments as $i => $s) {
    $flight = strtoupper(trim($s['flight'] ?? ''));
    $cls    = strtoupper(trim($s['class']  ?? 'M'));
    $date   = strtoupper(trim($s['date']   ?? ''));
    $route  = strtoupper(str_replace('-','',trim($s['route'] ?? '')));
    $dep    = trim($s['dep'] ?? '');
    $arr    = trim($s['arr'] ?? '');
    $offset = (int)($s['arr_offset'] ?? 0);
    $arrStr = $arr.($offset>0?'+'.str_repeat('+',$offset):'');
    $n      = $i+1;
    $segLines[] = "{$n}. {$flight} {$cls} {$date} {$route} HS {$dep} {$arrStr}";
}

$fareLines = [];
foreach ($fares as $f) {
    $type  = strtoupper(trim($f['type'] ?? 'ADT'));
    $pax   = (int)($f['pax']        ?? 1);
    $gross = (float)($f['gross_fare']  ?? 0);
    $iata  = (float)($f['iata_charge'] ?? 0);
    $pay   = (float)($f['payable']     ?? 0);
    $fareLines[] = "{$type} {$pax} {$ccy} ".number_format($gross,0,'.','')." IATA+".number_format($iata,0,'.','')." PAY ".number_format($pay,0,'.','');
}

$sys = 'You are a GDS text formatter for airline ticketing. Format provided flight data into standard Amadeus/Sabre-style GDS text. Return ONLY the raw GDS text, no explanation.';
$usr = "Airline: {$airline}\n\nSegments:\n".implode("\n",$segLines)."\n\nFares:\n".implode("\n",$fareLines)."\n\nFormat as standard GDS output text.";

$r = geminiCall($sys, $usr, 800, 0.1);
if (!$r['success']) { echo json_encode(['success'=>false,'message'=>$r['error']??'AI failed']); exit; }

echo json_encode(['success'=>true,'gds_text'=>trim($r['text'])], JSON_UNESCAPED_UNICODE);