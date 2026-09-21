<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';

function blank_status(array $r): array {
    return [
        'name' => $r['name'], 'type' => $r['type'], 'host' => $r['host'],
        'url' => $r['urls'][0], 'online' => false, 'http_code' => 0,
        'response_ms' => null, 'checked_at' => gmdate('c'),
        'uptime' => null, 'version' => null, 'xlx_version' => null, 'dashboard_version' => null, 'description' => null,
        'users' => [], 'modules' => [], 'peers' => [], 'last_heard' => [],
        'error' => null,
    ];
}

function clean_text(string $s): string {
    $s = html_entity_decode($s, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    return trim(preg_replace('/\s+/u', ' ', $s) ?? '');
}

function fetch_url(string $url): array {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_MAXREDIRS => 4,
        CURLOPT_CONNECTTIMEOUT => 4,
        CURLOPT_TIMEOUT => HTTP_TIMEOUT,
        CURLOPT_USERAGENT => 'DSTAR-Reflector-Monitor/2.0',
        CURLOPT_ENCODING => '',
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_SSL_VERIFYHOST => 2,
    ]);
    $t = microtime(true);
    $body = curl_exec($ch);
    $info = curl_getinfo($ch);
    $err = curl_error($ch);
    curl_close($ch);
    return [
        'ok' => $body !== false && ($info['http_code'] ?? 0) >= 200 && ($info['http_code'] ?? 0) < 400,
        'body' => is_string($body) ? $body : '',
        'http_code' => (int)($info['http_code'] ?? 0),
        'response_ms' => (int)round((microtime(true)-$t)*1000),
        'error' => $err,
    ];
}

function fetch_any(array $urls): array {
    $last = null;
    foreach ($urls as $url) {
        $r = fetch_url($url);
        if ($r['ok']) return array_merge($r, ['url'=>$url]);
        $last = array_merge($r, ['url'=>$url]);
    }
    return $last ?? ['ok'=>false,'body'=>'','http_code'=>0,'response_ms'=>null,'error'=>'No URL configured','url'=>$urls[0]];
}

function dom_from_html(string $html): ?DOMDocument {
    if ($html === '') return null;
    libxml_use_internal_errors(true);
    $dom = new DOMDocument();
    @$dom->loadHTML('<?xml encoding="UTF-8">' . $html, LIBXML_NOWARNING|LIBXML_NOERROR);
    libxml_clear_errors();
    return $dom;
}

function tables_from_dom(?DOMDocument $dom): array {
    if (!$dom) return [];
    $xp = new DOMXPath($dom);
    $out = [];
    foreach ($xp->query('//table') as $table) {
        $rows = [];
        foreach ($xp->query('.//tr', $table) as $tr) {
            $cells = [];
            foreach ($xp->query('./th|./td', $tr) as $cell) {
                $cells[] = clean_text($cell->textContent);
            }
            if ($cells) $rows[] = $cells;
        }
        if ($rows) $out[] = $rows;
    }
    return $out;
}

function header_text(array $row): string {
    return strtolower(implode(' | ', array_map('strtolower', $row)));
}

function table_has(array $table, array $terms): bool {
    if (!$table) return false;
    $h = header_text($table[0]);
    foreach ($terms as $term) if (str_contains($h, strtolower($term))) return true;
    return false;
}

function find_tables(array $tables, array $terms): array {
    return array_values(array_filter($tables, fn($t) => table_has($t, $terms)));
}

function module_letters(string $s): array {
    preg_match_all('/(?<![A-Z0-9])([A-I])(?![A-Z0-9])/i', $s, $m);
    return array_values(array_unique(array_map('strtoupper', $m[1] ?? [])));
}

function extract_uptime(string $text): ?string {
    if (preg_match('/Service\s+uptime\s*:\s*(.*?)(?=\s+(?:Users|Modules|Repeaters|Peers)\b|$)/i', $text, $m)) {
        $v = clean_text($m[1]);
        return $v !== '' ? $v : null;
    }
    return null;
}

function extract_version(string $text): ?array {
    if (preg_match('/(XLX\d+)\s+v([\d.]+)\s*-\s*Dashboard\s+v([\d.]+)/i', $text, $m)) {
        return [
            'xlx' => $m[1].' v'.$m[2],
            'dashboard' => 'Dashboard v'.$m[3],
        ];
    }
    return null;
}

function parse_xlxd(array $r, string $html, int $ms, string $url): array {
    $s = blank_status($r);
    $s['online'] = true; $s['http_code'] = 200; $s['response_ms'] = $ms; $s['url'] = $url;
    $dom = dom_from_html($html);
    $tables = tables_from_dom($dom);
    $plain = clean_text($dom?->textContent ?? strip_tags($html));
    $s['uptime'] = extract_uptime($plain);
    $versions = extract_version($plain);
    if (is_array($versions)) {
        $s['xlx_version'] = $versions['xlx'] ?? null;
        $s['dashboard_version'] = $versions['dashboard'] ?? null;
        // Preserve the combined value for API/backward compatibility.
        $s['version'] = ($versions['xlx'] ?? '').' · '.($versions['dashboard'] ?? '');
    }

    // XLXD "Users / Modules" table: Module, Name, Users, DPlus, DExtra, ...
    foreach (find_tables($tables, ['Module','Users','DPlus']) as $t) {
        foreach (array_slice($t, 1) as $row) {
            if (!isset($row[0])) continue;
            $mod = strtoupper(trim($row[0]));
            if (preg_match('/^[A-I]$/', $mod)) {
                $s['modules'][] = [
                    'module'=>$mod,
                    'name'=>$row[1] ?? '',
                    'users'=>isset($row[2]) && is_numeric($row[2]) ? (int)$row[2] : null,
                    'links'=>array_values(array_filter(array_slice($row, 3), fn($v)=>$v!==''))
                ];
            }
        }
    }

    // Generic XLXD user/live tables.
    foreach ($tables as $t) {
        if (!$t) continue;
        $h = strtolower(implode(' ', $t[0]));
        if (str_contains($h,'callsign') && (str_contains($h,'last heard') || str_contains($h,'last tx') || str_contains($h,'listening on'))) {
            foreach (array_slice($t,1,MAX_USERS) as $row) {
                if (!isset($row[0]) || $row[0]==='') continue;
                $s['users'][] = [
                    'callsign'=>$row[1] ?? $row[0],
                    'suffix'=>$row[2] ?? '',
                    'via'=>$row[3] ?? '',
                    'last_heard'=>$row[4] ?? ($row[5] ?? ''),
                    'module'=>end($row) ?: ''
                ];
            }
        }
    }

    // Peers / nodes.
    foreach (find_tables($tables, ['Peer','Protocol']) as $t) {
        foreach (array_slice($t,1,MAX_PEERS) as $row) {
            if (!isset($row[0]) || $row[0]==='') continue;
            $s['peers'][] = [
                'peer'=>$row[0],
                'details'=>implode(' | ', array_slice($row,1))
            ];
        }
    }

    // D-Star live / last-heard style tables.
    foreach ($tables as $t) {
        if (!$t) continue;
        $h = strtolower(implode(' ', $t[0]));
        if (str_contains($h,'mycall') || str_contains($h,'last heard')) {
            foreach (array_slice($t,1,MAX_LAST_HEARD) as $row) {
                if (!isset($row[0]) || $row[0]==='') continue;
                $s['last_heard'][] = [
                    'callsign'=>$row[0],
                    'time'=>$row[1] ?? '',
                    'module'=>$row[2] ?? '',
                    'details'=>implode(' | ', array_slice($row,3))
                ];
            }
        }
    }

    // XLXD pages expose module assignments even if there are no current users.
    if (!$s['modules']) {
        foreach (module_letters($plain) as $m) $s['modules'][]=['module'=>$m,'name'=>'','users'=>null,'links'=>[]];
    }
    return $s;
}

function parse_dplus(array $r, string $html, int $ms, string $url): array {
    $s = blank_status($r);
    $s['online'] = true; $s['http_code'] = 200; $s['response_ms'] = $ms; $s['url'] = $url;
    $dom = dom_from_html($html);
    $tables = tables_from_dom($dom);
    $plain = clean_text($dom?->textContent ?? strip_tags($html));

    // DPLUS dashboards commonly publish "Linked Gateways / Reflectors".
    foreach (find_tables($tables, ['Module','Linked to']) as $t) {
        foreach (array_slice($t,1) as $row) {
            if (count($row)<2) continue;
            $mod = strtoupper(trim($row[0]));
            if (preg_match('/^[A-I]$/',$mod)) {
                $s['modules'][]=['module'=>$mod,'name'=>'','users'=>null,'links'=>[$row[1]]];
            }
        }
    }

    // Remote Users: Callsign / User / Message / Last TX on / Type
    foreach (find_tables($tables, ['Remote Users','Callsign','Last TX on']) as $t) {
        foreach (array_slice($t,1,MAX_USERS) as $row) {
            if (!isset($row[0]) || !preg_match('/^[A-Z0-9\/\-]{3,8}$/i',$row[0])) continue;
            $s['users'][]=[
                'callsign'=>$row[0],
                'user'=>$row[1] ?? '',
                'message'=>$row[2] ?? '',
                'last_heard'=>$row[3] ?? '',
                'module'=>$row[4] ?? ''
            ];
        }
    }

    // Last Heard table.
    foreach (find_tables($tables, ['Last Heard','Callsign','Time']) as $t) {
        foreach (array_slice($t,1,MAX_LAST_HEARD) as $row) {
            if (!isset($row[0]) || $row[0]==='') continue;
            $s['last_heard'][]=[
                'callsign'=>$row[0],
                'time'=>$row[1] ?? '',
                'module'=>$row[2] ?? '',
                'details'=>implode(' | ',array_slice($row,3))
            ];
        }
    }

    // Fallback: scan visible text for linked module rows such as "A unlinked".
    if (!$s['modules']) {
        foreach (preg_split('/\s{2,}|\n/',$plain) as $line) {
            if (preg_match('/\b([A-I])\s+(unlinked|not linked|REF\d+[A-Z]?|XRF\d+[A-Z]?|DCS\d+[A-Z]?|XLX\d+[A-Z]?)/i',$line,$m)) {
                $s['modules'][]=['module'=>strtoupper($m[1]),'name'=>'','users'=>null,'links'=>[$m[2]]];
            }
        }
    }
    return $s;
}

function load_cache(string $key): ?array {
    $file = __DIR__.'/cache/'.preg_replace('/[^A-Za-z0-9_-]/','_',$key).'.json';
    if (!is_file($file) || (time()-filemtime($file)) > CACHE_TTL) return null;
    $data = json_decode((string)file_get_contents($file), true);
    return is_array($data) ? $data : null;
}

function save_cache(string $key, array $data): void {
    $file = __DIR__.'/cache/'.preg_replace('/[^A-Za-z0-9_-]/','_',$key).'.json';
    @file_put_contents($file, json_encode($data, JSON_UNESCAPED_SLASHES), LOCK_EX);
}

function get_reflector(array $r): array {
    $cached = load_cache($r['name']);
    if ($cached) { $cached['cached']=true; return $cached; }

    $raw = fetch_any($r['urls']);
    if (!$raw['ok']) {
        $s = blank_status($r);
        $s['response_ms'] = $raw['response_ms'];
        $s['error'] = $raw['error'] ?: ('HTTP '.$raw['http_code']);
        $s['http_code'] = $raw['http_code'];
        save_cache($r['name'], $s);
        return $s;
    }

    $s = strtoupper($r['type']) === 'XLXD'
        ? parse_xlxd($r,$raw['body'],$raw['response_ms'],$raw['url'])
        : parse_dplus($r,$raw['body'],$raw['response_ms'],$raw['url']);

    $s['checked_at'] = gmdate('c');
    $s['cached'] = false;
    save_cache($r['name'],$s);
    return $s;
}

function all_reflectors(array $reflectors): array {
    $out=[];
    foreach ($reflectors as $r) $out[] = get_reflector($r);
    return $out;
}

function network_last_heard(array $statuses): array {
    $rows=[];
    foreach ($statuses as $s) {
        foreach ($s['last_heard'] as $x) {
            $x['reflector']=$s['name'];
            $rows[]=$x;
        }
        foreach ($s['users'] as $x) {
            if (!isset($x['callsign']) || !$x['callsign']) continue;
            $rows[]=[
                'reflector'=>$s['name'],
                'callsign'=>$x['callsign'],
                'time'=>$x['last_heard'] ?? '',
                'module'=>$x['module'] ?? '',
                'details'=>$x['via'] ?? ($x['message'] ?? '')
            ];
        }
    }
    return array_slice($rows,0,150);
}
