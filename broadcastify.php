<?php
declare(strict_types=1);

const BROADCASTIFY_FEED_ID = 45853;
const BROADCASTIFY_LISTEN_URL = 'https://www.broadcastify.com/listen/feed/45853';
const BROADCASTIFY_ARCHIVES_URL = 'https://www.broadcastify.com/archives/feed/45853';
const BROADCASTIFY_OWNER_API = 'https://api.broadcastify.com/owner/';
const BROADCASTIFY_CACHE_TTL = 30;
const BROADCASTIFY_HTTP_TIMEOUT = 6;

function broadcastify_credentials(): array {
    $user = trim((string)getenv('BROADCASTIFY_USERNAME'));
    $pass = (string)getenv('BROADCASTIFY_PASSWORD');

    $paths = [
        '/etc/dstar-monitor/broadcastify.php',
        __DIR__.'/broadcastify.local.php',
    ];
    foreach ($paths as $path) {
        if (($user === '' || $pass === '') && is_file($path)) {
            $cfg = require $path;
            if (is_array($cfg)) {
                $user = $user !== '' ? $user : trim((string)($cfg['username'] ?? ''));
                $pass = $pass !== '' ? $pass : (string)($cfg['password'] ?? '');
            }
        }
    }
    return ['username'=>$user, 'password'=>$pass];
}

function broadcastify_owner_request(string $action, array $extra=[]): array {
    $creds = broadcastify_credentials();
    if ($creds['username'] === '' || $creds['password'] === '') {
        throw new RuntimeException('Broadcastify credentials are not configured.');
    }
    $query = array_merge([
        'a'=>$action,
        'type'=>'json',
        'feedId'=>(string)BROADCASTIFY_FEED_ID,
        'u'=>$creds['username'],
        'p'=>$creds['password'],
    ], $extra);
    $ch = curl_init(BROADCASTIFY_OWNER_API.'?'.http_build_query($query, '', '&', PHP_QUERY_RFC3986));
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER=>true,
        CURLOPT_FOLLOWLOCATION=>true,
        CURLOPT_CONNECTTIMEOUT=>3,
        CURLOPT_TIMEOUT=>BROADCASTIFY_HTTP_TIMEOUT,
        CURLOPT_USERAGENT=>'DSTAR-Reflector-Monitor/6.3.0',
        CURLOPT_HTTPHEADER=>['Accept: application/json'],
    ]);
    $body = curl_exec($ch);
    $err = curl_error($ch);
    $code = (int)curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    curl_close($ch);
    if ($body === false || $err !== '') throw new RuntimeException('Broadcastify request failed: '.$err);
    if ($code < 200 || $code >= 300) throw new RuntimeException('Broadcastify returned HTTP '.$code.'.');
    $json = json_decode($body, true);
    if (!is_array($json)) throw new RuntimeException('Broadcastify returned an invalid JSON response.');
    return $json;
}

function broadcastify_scalar_by_keys(array $data, array $keys): mixed {
    $wanted = array_map(fn($x)=>strtolower(preg_replace('/[^a-z0-9]/i','',(string)$x)), $keys);
    $walk = function($node) use (&$walk,$wanted) {
        if (!is_array($node)) return null;
        foreach ($node as $k=>$v) {
            $norm = strtolower(preg_replace('/[^a-z0-9]/i','',(string)$k));
            if (in_array($norm,$wanted,true) && (is_scalar($v) || $v === null)) return $v;
        }
        foreach ($node as $v) { if (is_array($v)) { $found=$walk($v); if ($found !== null) return $found; } }
        return null;
    };
    return $walk($data);
}

function broadcastify_normalize_feed(array $raw): array {
    $status = broadcastify_scalar_by_keys($raw,['status','feedStatus','online']);
    $listeners = broadcastify_scalar_by_keys($raw,['listeners','listenerCount','currentListeners','listenersCurrent']);
    $uptime = broadcastify_scalar_by_keys($raw,['uptime','feedUptime']);
    $online = null;
    if (is_bool($status)) $online=$status;
    elseif (is_numeric($status)) $online=((int)$status)>0;
    elseif (is_string($status)) {
        $s=strtolower(trim($status));
        if (in_array($s,['online','up','active','1','true','yes'],true)) $online=true;
        elseif (in_array($s,['offline','down','inactive','0','false','no'],true)) $online=false;
    }
    return [
        'online'=>$online,
        'status_text'=>$online===true?'ONLINE':($online===false?'OFFLINE':'UNKNOWN'),
        'listeners'=>is_numeric($listeners)?(int)$listeners:null,
        'uptime'=>is_scalar($uptime)?(string)$uptime:null,
    ];
}

function broadcastify_status(): array {
    $cacheDir=__DIR__.'/cache';
    $cacheFile=$cacheDir.'/broadcastify-45853.json';
    if (is_file($cacheFile) && (time()-(int)filemtime($cacheFile)) < BROADCASTIFY_CACHE_TTL) {
        $cached=json_decode((string)@file_get_contents($cacheFile),true);
        if (is_array($cached)) return $cached;
    }
    $base=[
        'ok'=>false,'configured'=>false,'feed_id'=>BROADCASTIFY_FEED_ID,
        'name'=>'REF049C Audio','bridge'=>'BrandMeister TG 312543',
        'listen_url'=>BROADCASTIFY_LISTEN_URL,'archives_url'=>BROADCASTIFY_ARCHIVES_URL,
        'online'=>null,'status_text'=>'UNAVAILABLE','listeners'=>null,'uptime'=>null,
        'checked_at'=>gmdate('c'),'error'=>null,
    ];
    $creds=broadcastify_credentials();
    if ($creds['username']==='' || $creds['password']==='') {
        $base['error']='Broadcastify credentials are not configured on this server.';
        return $base;
    }
    $base['configured']=true;
    try {
        $raw=broadcastify_owner_request('feed');
        $norm=broadcastify_normalize_feed($raw);
        $base=array_merge($base,$norm,['ok'=>true,'error'=>null,'checked_at'=>gmdate('c')]);
        if (!is_dir($cacheDir)) @mkdir($cacheDir,0775,true);
        @file_put_contents($cacheFile,json_encode($base,JSON_UNESCAPED_SLASHES),LOCK_EX);
        return $base;
    } catch (Throwable $e) {
        $base['error']=$e->getMessage();
        if (is_file($cacheFile)) {
            $stale=json_decode((string)@file_get_contents($cacheFile),true);
            if (is_array($stale)) {
                $stale['ok']=false; $stale['stale']=true; $stale['error']=$base['error'];
                return $stale;
            }
        }
        return $base;
    }
}
