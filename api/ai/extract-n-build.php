<?php
/**
 * api/ai/extract-n-build.php  (v3 — flight-aware, per-day country)
 * POST { prompt }
 * Pipeline:
 *   Step 1 → Gemini extracts structured fields + raw day data + detects flight content
 *   Step 2 → If GDS detected: route to extract-gds.php; else: Gemini extracts flight fields
 *   Step 3 → DB search per day (per-day country) + Gemini enriches activity/transport suggestions
 */
session_start();
require_once '../../server/api_bootstrap.php';
require_once '../../server/db_connection.php';
require_once '../../server/ai-gemini.php';

header('Content-Type: application/json');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { echo json_encode(['success'=>false,'message'=>'POST only']); exit; }

$in   = json_decode(file_get_contents('php://input'), true) ?: [];
$text = trim($in['prompt'] ?? '');
if (!$text) { echo json_encode(['success'=>false,'message'=>'prompt required']); exit; }

// ── JSON-mode Gemini ─────────────────────────────────────────────────────
function geminiJsonMode(string $sys, string $usr, int $tok=5000): array {
    $key = trim(@file_get_contents(__DIR__.'/../../gemini-apikey.txt') ?: '');
    if (!$key) return ['success'=>false,'error'=>'API key missing'];
    $url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key={$key}";
    $body = json_encode(['system_instruction'=>['parts'=>[['text'=>$sys]]],'contents'=>[['role'=>'user','parts'=>[['text'=>$usr]]]],'generationConfig'=>['maxOutputTokens'=>$tok,'temperature'=>0.2,'response_mime_type'=>'application/json']],JSON_UNESCAPED_UNICODE);
    $ch=curl_init($url);curl_setopt_array($ch,[CURLOPT_POST=>true,CURLOPT_POSTFIELDS=>$body,CURLOPT_RETURNTRANSFER=>true,CURLOPT_TIMEOUT=>60,CURLOPT_HTTPHEADER=>['Content-Type: application/json']]);
    $raw=curl_exec($ch);$code=curl_getinfo($ch,CURLINFO_HTTP_CODE);$err=curl_error($ch);curl_close($ch);
    if($err) return['success'=>false,'error'=>$err];
    if($code!==200){$b=json_decode($raw,true);return['success'=>false,'error'=>$b['error']['message']??"HTTP {$code}"];}
    $out=json_decode($raw,true)['candidates'][0]['content']['parts'][0]['text']??'';
    if(!trim($out)) return['success'=>false,'error'=>'Empty response'];
    return ['success'=>true,'text'=>trim($out)];
}

// Detect GDS pattern in text
function looksLikeGds(string $t): bool {
    return (bool)preg_match('/\b[A-Z]{2}\s*\d{3,4}\s+[A-Z]\s+\d{2}[A-Z]{3}\b/',$t);
}

// ══════════════════════════════════════════════════════════════════════════
// STEP 1 — Extract structured fields from full_description
// ══════════════════════════════════════════════════════════════════════════
$sys1 = 'You are a travel data extractor. Extract all structured fields from travel package descriptions. Return ONLY valid JSON.';
$usr1 = <<<PROMPT
Extract from this travel package description and return a JSON object:

{
  "title": "short title 5-8 words",
  "package_type": "group|fit|corporate|factory_tour|custom|fixed|umrah",
  "start_date": "YYYY-MM-DD or empty",
  "end_date": "YYYY-MM-DD or empty",
  "duration": 0,
  "adults": 2, "children": 0, "infants": 0,
  "sell_currency_code": "BDT",
  "countries": ["Country Name"],
  "inclusions": ["item"], "exclusions": ["item"], "highlights": ["point"],
  "days": [
    {
      "day_number": 1,
      "title": "Day title",
      "city_name": "",
      "country_name": "",
      "meal_breakfast": false, "meal_lunch": false, "meal_dinner": false,
      "has_flight": false,
      "flight_text": "",
      "raw_items": [
        {"type": "activity|transport", "name": "name", "from_city": "", "to_city": "", "duration_hours": 0}
      ]
    }
  ]
}

RULES:
- For each day, set has_flight=true and flight_text=the raw flight text if ANY flight/airline info exists for that day
- flight_text should include the raw GDS or human-readable flight info verbatim
- raw_items: only non-flight activities and transport
- country_name: which country is this day in (detect from context/routes)
- duration = number of nights

Description:
{$text}
PROMPT;

$r1 = geminiJsonMode($sys1, $usr1, 12000);
if (!$r1['success']) { echo json_encode(['success'=>false,'message'=>$r1['error']??'Step 1 failed']); exit; }

$raw1 = trim(preg_replace(['/^```(json)?\s*/m','/```\s*$/m'],'',$r1['text']));
$p    = json_decode($raw1, true);
if (!is_array($p)) { echo json_encode(['success'=>false,'message'=>'Unparseable JSON','raw'=>substr($raw1,0,400)]); exit; }

// ── Match countries to DB ────────────────────────────────────────────────
$ctexts = is_array($p['countries']??null) ? $p['countries'] : [];
$matched_countries = [];
$countryMap = []; // name → DB row
if (!empty($ctexts)) {
    try {
        $ph = implode(',',array_fill(0,count($ctexts),'?'));
        $s  = $pdo->prepare("SELECT sys_id,name,currency_code,default_rate FROM countries WHERE status='active' AND name IN ({$ph}) LIMIT 20");
        $s->execute($ctexts);
        $matched_countries = $s->fetchAll(PDO::FETCH_ASSOC);
        foreach ($matched_countries as $mc) $countryMap[strtolower($mc['name'])] = $mc;
    } catch(Throwable $e){}
}

// ══════════════════════════════════════════════════════════════════════════
// STEP 2 + 3 — Per-day: extract flights + DB search + AI enrichment
// ══════════════════════════════════════════════════════════════════════════
$rawDays = $p['days'] ?? [];
$days    = [];

foreach ($rawDays as $d) {
    $dayNum    = (int)($d['day_number']??1);
    $dayTitle  = trim($d['title']??'Day '.$dayNum);
    $cityName  = trim($d['city_name']??'');
    $cntryName = trim($d['country_name']??'');
    $rawItems  = $d['raw_items']??[];
    $hasFlight = (bool)($d['has_flight']??false);
    $flightTxt = trim($d['flight_text']??'');

    // ── Find country_sys_id for this day ─────────────────────────────
    $dayCntryId = null;
    if ($cntryName && isset($countryMap[strtolower($cntryName)])) {
        $dayCntryId = $countryMap[strtolower($cntryName)]['sys_id'];
    } elseif (!empty($matched_countries)) {
        $dayCntryId = $matched_countries[0]['sys_id']; // fallback to first
    }

    // ── Flight extraction ─────────────────────────────────────────────
    $flights = [];
    if ($hasFlight && $flightTxt) {
        if (looksLikeGds($flightTxt)) {
            // Route to existing extract-gds.php
            $gdsKey = trim(@file_get_contents(__DIR__.'/../../gemini-apikey.txt') ?: '');
            $gdsPayload = json_encode(['system_instruction'=>['parts'=>[['text'=>"Extract flight segments and fare details from raw GDS text. Convert HK to HS. Return ONLY valid JSON: {\"airline\":\"\",\"segments\":[{\"line\":1,\"flight\":\"\",\"class\":\"\",\"date\":\"\",\"route\":\"\",\"status\":\"\",\"departure\":\"\",\"arrival\":\"\"}],\"fares\":[{\"type\":\"ADT\",\"pax\":1,\"base_fare\":0,\"taxes\":0,\"gross_fare\":0,\"iata_charge\":0}]}"]]],'contents'=>[['role'=>'user','parts'=>[['text'=>$flightTxt]]]],'generationConfig'=>['temperature'=>0.1,'responseMimeType'=>'application/json']]);
            $ch=curl_init("https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key={$gdsKey}");
            curl_setopt_array($ch,[CURLOPT_POST=>true,CURLOPT_POSTFIELDS=>$gdsPayload,CURLOPT_RETURNTRANSFER=>true,CURLOPT_TIMEOUT=>30,CURLOPT_HTTPHEADER=>['Content-Type: application/json']]);
            $gdsRaw=curl_exec($ch);curl_close($ch);
            $gdsBody=json_decode($gdsRaw,true);
            $gdsTxt=$gdsBody['candidates'][0]['content']['parts'][0]['text']??'';
            $gdsData=json_decode($gdsTxt,true);
            if (is_array($gdsData) && !empty($gdsData['segments'])) {
                $segs=array_map(fn($s)=>['flight'=>$s['flight']??'','class'=>$s['class']??'','date'=>$s['date']??'','route'=>$s['route']??'','dep'=>$s['departure']??'','arr'=>$s['arrival']??'','arr_offset'=>0],$gdsData['segments']);
                $fares=array_map(fn($f)=>['type'=>$f['type']??'ADT','pax'=>(int)($f['pax']??1),'base_fare'=>(float)($f['base_fare']??0),'taxes'=>(float)($f['taxes']??0),'gross_fare'=>(float)($f['gross_fare']??0),'iata_charge'=>(float)($f['iata_charge']??0),'net_fare'=>0,'payable'=>0,'payable_edited'=>false],$gdsData['fares']??[]);
                $airline=$gdsData['airline']??'';
                $firstSeg=$segs[0]??[];$lastSeg=$segs[count($segs)-1]??[];
                $routeParts=explode('-',$firstSeg['route']??'');
                $flights[]=['type'=>'flight','source'=>'gds','name'=>"{$airline} ".($firstSeg['route']??''),'airline'=>$airline,'segments'=>$segs,'fares'=>$fares,'raw_gds'=>$flightTxt,'from_city'=>$routeParts[0]??'','to_city'=>$routeParts[1]??'','overnight'=>false,'arrive_day_offset'=>0,'start_time'=>$firstSeg['dep']??'','end_time'=>$lastSeg['arr']??'','currency_code'=>'BDT','total_net'=>0,'total_sell'=>0,'description_points'=>[]];
            }
        } else {
            // Human-readable flight — Gemini extracts
            $fr = geminiJsonMode('Extract flight info from human-readable text. Return ONLY JSON: {"airline":"","segments":[{"flight":"","class":"","date":"","route":"DAC-BKK","dep":"","arr":"","arr_offset":0}],"fares":[{"type":"ADT","pax":1,"gross_fare":0,"iata_charge":0,"payable":0}],"overnight":false,"arrive_day_offset":0}', $flightTxt, 1500);
            if ($fr['success']) {
                $fd2=json_decode(trim(preg_replace(['/^```(json)?\s*/m','/```\s*$/m'],'',$fr['text'])),true);
                if (is_array($fd2) && !empty($fd2['segments'])) {
                    $segs=array_map(fn($s)=>['flight'=>$s['flight']??'','class'=>$s['class']??'','date'=>$s['date']??'','route'=>$s['route']??'','dep'=>$s['dep']??'','arr'=>$s['arr']??'','arr_offset'=>(int)($s['arr_offset']??0)],$fd2['segments']);
                    $fares=array_map(fn($f)=>['type'=>$f['type']??'ADT','pax'=>(int)($f['pax']??1),'base_fare'=>0,'taxes'=>0,'gross_fare'=>(float)($f['gross_fare']??0),'iata_charge'=>(float)($f['iata_charge']??0),'net_fare'=>0,'payable'=>(float)($f['payable']??0),'payable_edited'=>false],$fd2['fares']??[]);
                    $al=$fd2['airline']??'';
                    $firstSeg=$segs[0]??[];$routeParts=explode('-',$firstSeg['route']??'');
                    $ovn=(bool)($fd2['overnight']??false);$off=(int)($fd2['arrive_day_offset']??0);
                    $flights[]=['type'=>'flight','source'=>'ai','name'=>"{$al} ".($firstSeg['route']??''),'airline'=>$al,'segments'=>$segs,'fares'=>$fares,'raw_gds'=>'','from_city'=>$routeParts[0]??'','to_city'=>$routeParts[1]??'','overnight'=>$ovn,'arrive_day_offset'=>$off,'start_time'=>$firstSeg['dep']??'','end_time'=>(!$ovn?($segs[count($segs)-1]['arr']??''):''),'currency_code'=>'BDT','total_net'=>0,'total_sell'=>0,'description_points'=>[]];
                }
            }
        }
    }

    // ── DB search for activities/transport (per-day country) ──────────
    $suggestions = [];
    if ($dayCntryId && !empty($rawItems)) {
        try {
            $dbA=[]; $dbT=[];
            $s=$pdo->prepare("SELECT sys_id,name,category,city_name,duration_hours FROM activities WHERE status='active' AND country_sys_id=? ORDER BY popularity DESC,usage_count DESC LIMIT 30");
            $s->execute([$dayCntryId]); $dbA=$s->fetchAll(PDO::FETCH_ASSOC);

            $s=$pdo->prepare("SELECT sys_id,name,type,from_city_name,to_city_name,duration_typical FROM transport_services WHERE status='active' AND country_sys_id=? ORDER BY usage_count DESC LIMIT 15");
            $s->execute([$dayCntryId]); $dbT=$s->fetchAll(PDO::FETCH_ASSOC);

            $actVar=[];$transVar=[];
            if (!empty($dbA)){$ids=array_column($dbA,'sys_id');$vph=implode(',',array_fill(0,count($ids),'?'));$vs=$pdo->prepare("SELECT activity_sys_id,sys_id as v_id,currency_code,net_cost,sell_price,price_basis FROM activity_variants WHERE activity_sys_id IN ({$vph}) AND status='active' ORDER BY sell_price ASC");$vs->execute($ids);foreach($vs->fetchAll(PDO::FETCH_ASSOC) as $v){if(!isset($actVar[$v['activity_sys_id']]))$actVar[$v['activity_sys_id']]=$v;}}
            if (!empty($dbT)){$ids=array_column($dbT,'sys_id');$vph=implode(',',array_fill(0,count($ids),'?'));$vs=$pdo->prepare("SELECT service_sys_id,sys_id as v_id,vehicle_class,currency_code,net_cost,sell_price,price_basis FROM transport_variants WHERE service_sys_id IN ({$vph}) AND status='active' ORDER BY sell_price ASC");$vs->execute($ids);foreach($vs->fetchAll(PDO::FETCH_ASSOC) as $v){if(!isset($transVar[$v['service_sys_id']]))$transVar[$v['service_sys_id']]=$v;}}

            $aList=[];foreach($dbA as $a){$v=$actVar[$a['sys_id']]??null;$aList[]="{$a['sys_id']}|{$a['name']}|{$a['city_name']}|".($v?"{$v['currency_code']} {$v['net_cost']}":"TBD");}
            $tList=[];foreach($dbT as $t){$v=$transVar[$t['sys_id']]??null;$tList[]="{$t['sys_id']}|{$t['name']}|{$t['from_city_name']}→{$t['to_city_name']}|".($v?"{$v['currency_code']} {$v['net_cost']}":"TBD");}

            $rawNames=implode(', ',array_column($rawItems,'name'));
            $sys3='Suggest travel day items matching the mentioned activities. Use DB IDs when possible. Return ONLY a raw JSON array. Each: {"type":"activity|transport","source":"masterdata|ai","source_sys_id":"","name":"","reason":"","category":"","duration_hours":0,"transport_type":"intercity","vehicle_class":"van","from_city":"","to_city":"","direction":"one_way","duration_typical":"","currency_code":"BDT","net_cost":0,"sell_price":0,"price_basis":"per_pax","description_points":[]}';
            $usr3="Day {$dayNum}: {$dayTitle} in {$cntryName}\nMentioned: {$rawNames}\n\nACTIVITIES:\n".implode("\n",$aList)."\n\nTRANSPORT:\n".implode("\n",$tList)."\n\nMatch mentioned items to DB or mark source='ai'. Return JSON array.";

            $r3=geminiCall($sys3,$usr3,2000,0.2);
            if($r3['success']){
                $txt3=trim(preg_replace(['/^```(json)?\s*/m','/```\s*$/m'],'',$r3['text']));
                $p3=json_decode($txt3,true);
                if(is_array($p3)){
                    $suggestions=array_values(array_filter($p3,fn($s)=>!empty($s['name'])));
                    // Enrich from DB
                    foreach($suggestions as &$sg){
                        $sid=$sg['source_sys_id']??'';
                        if($sg['type']==='activity'&&$sid&&isset($actVar[$sid])&&empty($sg['net_cost'])){$v=$actVar[$sid];$sg['currency_code']=$v['currency_code'];$sg['net_cost']=(float)$v['net_cost'];$sg['sell_price']=(float)$v['sell_price'];$sg['price_basis']=$v['price_basis'];$sg['variant_sys_id']=$v['v_id'];}
                        if($sg['type']==='transport'&&$sid&&isset($transVar[$sid])&&empty($sg['net_cost'])){$v=$transVar[$sid];$sg['currency_code']=$v['currency_code'];$sg['net_cost']=(float)$v['net_cost'];$sg['sell_price']=(float)$v['sell_price'];$sg['price_basis']=$v['price_basis'];$sg['variant_sys_id']=$v['v_id'];$sg['vehicle_class']=$v['vehicle_class'];}
                        if(empty($sg['source']))$sg['source']=($sg['source_sys_id']??'')?'masterdata':'ai';
                        if(!isset($sg['description_points']))$sg['description_points']=[];
                    }
                    unset($sg);
                }
            }
        } catch(Throwable $e){}
    }

    // Fallback: raw_items as AI suggestions if DB empty
    if (empty($suggestions) && !empty($rawItems)) {
        foreach ($rawItems as $ri) {
            if (empty($ri['name'])) continue;
            $type=in_array($ri['type']??'',['activity','transport'])?$ri['type']:'activity';
            $suggestions[]=['type'=>$type,'source'=>'ai','source_sys_id'=>'','name'=>trim($ri['name']),'reason'=>'Extracted from description','category'=>'','duration_hours'=>(float)($ri['duration_hours']??0),'transport_type'=>'intercity','vehicle_class'=>'van','from_city'=>$ri['from_city']??'','to_city'=>$ri['to_city']??'','direction'=>'one_way','duration_typical'=>'','currency_code'=>'BDT','net_cost'=>0,'sell_price'=>0,'price_basis'=>'per_pax','description_points'=>[]];
        }
    }

    $days[] = [
        'day_number'     => $dayNum,
        'title'          => $dayTitle,
        'city_name'      => $cityName,
        'country_name'   => $cntryName,
        'meal_breakfast' => (bool)($d['meal_breakfast']??false),
        'meal_lunch'     => (bool)($d['meal_lunch']??false),
        'meal_dinner'    => (bool)($d['meal_dinner']??false),
        'flights'        => $flights,        // ← flight items (direct inject)
        'suggestions'    => array_values($suggestions), // ← activity/transport suggestions
        'raw_text'       => '',
        'hotel_ref'      => null,
    ];
}

echo json_encode([
    'success' => true,
    'fields'  => [
        'title'              => trim($p['title']??''),
        'package_type'       => trim($p['package_type']??'custom'),
        'start_date'         => trim($p['start_date']??''),
        'end_date'           => trim($p['end_date']??''),
        'duration'           => (int)($p['duration']??0),
        'adults'             => max(1,(int)($p['adults']??2)),
        'children'           => max(0,(int)($p['children']??0)),
        'infants'            => max(0,(int)($p['infants']??0)),
        'sell_currency_code' => strtoupper(trim($p['sell_currency_code']??'BDT')),
        'inclusions'         => array_values(array_filter((array)($p['inclusions']??[]))),
        'exclusions'         => array_values(array_filter((array)($p['exclusions']??[]))),
        'highlights'         => array_values(array_filter((array)($p['highlights']??[]))),
        'countries_text'     => $ctexts,
        'matched_countries'  => $matched_countries,
    ],
    'days' => $days,
], JSON_UNESCAPED_UNICODE);