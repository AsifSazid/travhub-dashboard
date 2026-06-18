<?php
/** server/ai-claude.php — Claude API wrapper */
function _claudeApiKey(): string {
    static $key = null;
    if ($key !== null) return $key;
    foreach ([__DIR__.'/../claude-apikey.txt',__DIR__.'/../../claude-apikey.txt'] as $p) {
        if (file_exists($p)) { $key = trim(file_get_contents($p)); return $key; }
    }
    return '';
}
function claudeCall(string $system, string $user, int $maxTokens = 1000, float $temperature = 0.5): array {
    $apiKey = _claudeApiKey();
    if (!$apiKey) return ['success'=>false,'error'=>'Claude API key not configured'];
    $payload = json_encode(['model'=>'claude-sonnet-4-20250514','max_tokens'=>$maxTokens,'temperature'=>$temperature,'system'=>$system,'messages'=>[['role'=>'user','content'=>$user]]]);
    $ch = curl_init('https://api.anthropic.com/v1/messages');
    curl_setopt_array($ch,[CURLOPT_POST=>true,CURLOPT_POSTFIELDS=>$payload,CURLOPT_RETURNTRANSFER=>true,CURLOPT_TIMEOUT=>45,CURLOPT_HTTPHEADER=>['Content-Type: application/json','x-api-key: '.$apiKey,'anthropic-version: 2023-06-01']]);
    $raw=curl_exec($ch); $code=curl_getinfo($ch,CURLINFO_HTTP_CODE); $err=curl_error($ch); curl_close($ch);
    if ($err) return ['success'=>false,'error'=>'cURL: '.$err];
    if ($code!==200) { $b=json_decode($raw,true); return ['success'=>false,'error'=>$b['error']['message']??"HTTP {$code}"]; }
    return ['success'=>true,'text'=>trim(json_decode($raw,true)['content'][0]['text']??'')];
}
function claudeJSON(string $system, string $user, int $maxTokens = 1200): array {
    $r = claudeCall($system, $user, $maxTokens, 0.2);
    if (!$r['success']) return $r;
    $text = trim(preg_replace(['/^```(json)?\s*/m','/```\s*$/m'],'',$r['text']));
    $data = json_decode($text, true);
    if (json_last_error()!==JSON_ERROR_NONE) return ['success'=>false,'error'=>'JSON parse: '.json_last_error_msg(),'raw'=>$text];
    return ['success'=>true,'data'=>$data];
}
