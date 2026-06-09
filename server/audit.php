<?php
/** server/audit.php (Gen-3) — audit_log is in main TravHub system */
function auditLog(PDO $pdo, string $entityTable, string $entitySysId, string $action, mixed $before=null, mixed $after=null, ?string $userSysId=null, ?string $userName=null): bool {
    try {
        $pdo->prepare("INSERT INTO audit_log (user_sys_id,user_name,entity_table,entity_sys_id,action,before_json,after_json,ip_address) VALUES(?,?,?,?,?,?,?,?)")
            ->execute([$userSysId,$userName??($_SESSION['user_name']??'system'),$entityTable,$entitySysId,$action,$before?json_encode($before,JSON_UNESCAPED_UNICODE):null,$after?json_encode($after,JSON_UNESCAPED_UNICODE):null,$_SERVER['REMOTE_ADDR']??null]);
        return true;
    } catch(Throwable $e){ error_log("auditLog: {$e->getMessage()}"); return false; }
}
function auditCreate(PDO $pdo, string $t, string $sid, mixed $after): bool { return auditLog($pdo,$t,$sid,'create',null,$after); }
function auditDelete(PDO $pdo, string $t, string $sid): bool {
    try { $s=$pdo->prepare("SELECT * FROM `{$t}` WHERE sys_id=? LIMIT 1"); $s->execute([$sid]); return auditLog($pdo,$t,$sid,'delete',$s->fetch(),null); } catch(Throwable $e){ return false; }
}
