<?php
// SSRF-sicherer Fetcher: nur http/https auf Port 80/443, DNS selbst auflösen,
// private/reservierte IPs blocken, Auflösung per CURLOPT_RESOLVE pinnen
// (Rebinding-Schutz), Redirects manuell und pro Hop erneut validiert.

declare(strict_types=1);

define('UC_UA', 'UftrittWebsiteCheck/1.0 (+https://www.uftritt.ch/website-check)');
define('UC_MAX_BYTES', 1572864); // 1.5 MB pro Seite
define('UC_MAX_REDIRECTS', 2);

// ---- URL normalisieren und Basis-Validierung ----
// Rückgabe: normalisierte URL oder null.
function uc_normalize_url(string $raw): ?string
{
    $raw = trim($raw);
    if ($raw === '' || strlen($raw) > 2048) {
        return null;
    }
    if (!preg_match('#^https?://#i', $raw)) {
        $raw = 'https://' . $raw;
    }
    $p = parse_url($raw);
    if (!is_array($p) || empty($p['host'])) {
        return null;
    }
    $scheme = strtolower((string)($p['scheme'] ?? 'https'));
    if (!in_array($scheme, ['http', 'https'], true)) {
        return null;
    }
    if (isset($p['user']) || isset($p['pass'])) {
        return null; // Userinfo in URLs = Phishing-Verdacht
    }
    $host = strtolower(trim((string)$p['host'], '[]'));
    $isIp = filter_var($host, FILTER_VALIDATE_IP) !== false;
    if (!$isIp) {
        // Nur ASCII-Hostnamen mit mindestens einem Punkt und TLD >= 2 Zeichen
        if (!preg_match('/^[a-z0-9]([a-z0-9-]*[a-z0-9])?(\.[a-z0-9]([a-z0-9-]*[a-z0-9])?)+$/', $host)) {
            return null;
        }
        $tld = substr($host, (int)strrpos($host, '.') + 1);
        if (strlen($tld) < 2) {
            return null;
        }
    }
    $port = $p['port'] ?? ($scheme === 'https' ? 443 : 80);
    if (!in_array($port, [80, 443], true)) {
        return null; // nur Standard-Ports
    }
    $path = (string)($p['path'] ?? '/');
    if ($path === '') {
        $path = '/';
    }
    $query = isset($p['query']) ? '?' . $p['query'] : '';
    $hostOut = (filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) !== false) ? '[' . $host . ']' : $host;
    $isDefaultPort = ($scheme === 'http' && $port === 80) || ($scheme === 'https' && $port === 443);
    return $scheme . '://' . $hostOut . ($isDefaultPort ? '' : ':' . $port) . $path . $query;
}

// ---- Private/reservierte IPs erkennen ----
function uc_ip_is_public(string $ip): bool
{
    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) !== false) {
        $b = array_map('intval', explode('.', $ip));
        // 0.0.0.0/8, 10/8, 127/8, 169.254/16, 172.16/12, 192.168/16
        if ($b[0] === 0 || $b[0] === 10 || $b[0] === 127) {
            return false;
        }
        if ($b[0] === 169 && $b[1] === 254) {
            return false;
        }
        if ($b[0] === 172 && $b[1] >= 16 && $b[1] <= 31) {
            return false;
        }
        if ($b[0] === 192 && $b[1] === 168) {
            return false;
        }
        return true;
    }
    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) !== false) {
        $packed = @inet_pton($ip);
        if ($packed === false || strlen($packed) !== 16) {
            return false;
        }
        if ($packed === str_repeat("\0", 16)) {
            return false; // :: (unspecified)
        }
        if ($packed === str_repeat("\0", 15) . "\1") {
            return false; // ::1 (loopback)
        }
        $b0 = ord($packed[0]);
        if (($b0 & 0xfe) === 0xfc) {
            return false; // fc00::/7 (ULA)
        }
        if ($b0 === 0xfe && (ord($packed[1]) & 0xc0) === 0x80) {
            return false; // fe80::/10 (link-local)
        }
        // IPv4-mapped (::ffff:a.b.c.d) rekursiv prüfen
        if (substr($packed, 0, 12) === str_repeat("\0", 10) . "\xff\xff") {
            return uc_ip_is_public((string)inet_ntop(substr($packed, 12)));
        }
        return true;
    }
    return false;
}

// Zweite Hürde zusätzlich zur manuellen Liste oben: PHP-Filter gegen private/
// reservierte Ranges (deckt u. a. CGNAT 100.64.0.0/10 und NAT64 64:ff9b::/96)
function uc_ip_is_public_strict(string $ip): bool
{
    if (!uc_ip_is_public($ip)) {
        return false;
    }
    return filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false;
}

// ---- Host auflösen (pro Request gecacht) und öffentliche IP liefern ----
// Rückgabe: ['ip' => …, 'ips' => […]] oder Fehlercode 'unreachable'|'ssrf_blocked'
function uc_resolve_host(string $host)
{
    static $cache = [];
    if (isset($cache[$host])) {
        return $cache[$host];
    }
    // IP-Literal direkt prüfen
    if (filter_var($host, FILTER_VALIDATE_IP) !== false) {
        return $cache[$host] = uc_ip_is_public_strict($host)
            ? ['ip' => $host, 'ips' => [$host]]
            : 'ssrf_blocked';
    }
    $ips = [];
    $v4 = @gethostbynamel($host);
    if (is_array($v4)) {
        $ips = array_merge($ips, $v4);
    }
    $v6 = @dns_get_record($host, DNS_AAAA);
    if (is_array($v6)) {
        foreach ($v6 as $r) {
            if (!empty($r['ipv6'])) {
                $ips[] = $r['ipv6'];
            }
        }
    }
    $ips = array_values(array_unique($ips));
    if ($ips === []) {
        return $cache[$host] = 'unreachable';
    }
    foreach ($ips as $ip) {
        if (!uc_ip_is_public_strict($ip)) {
            return $cache[$host] = 'ssrf_blocked';
        }
    }
    // IPv4 bevorzugen (Shared-Hosting läuft damit am stabilsten)
    usort($ips, static function (string $a, string $b): int {
        $aV6 = filter_var($a, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) !== false ? 1 : 0;
        $bV6 = filter_var($b, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) !== false ? 1 : 0;
        return $aV6 <=> $bV6;
    });
    return $cache[$host] = ['ip' => $ips[0], 'ips' => $ips];
}

// ---- cURL-Handle für einen Hop (GET oder HEAD), gepinnte Auflösung ----
// $state wird per Referenz befüllt: ['headers' => …, 'body' => …, 'overflow' => bool]
function uc_curl_init_hop(string $url, string $ip, int $timeout, int $maxBytes, array $extraHeaders, bool $headOnly, array &$state)
{
    $state = ['headers' => [], 'body' => '', 'overflow' => false];
    $p = parse_url($url);
    $host = strtolower(trim((string)($p['host'] ?? ''), '[]'));
    $scheme = strtolower((string)($p['scheme'] ?? 'https'));
    $port = $p['port'] ?? ($scheme === 'https' ? 443 : 80);

    $ch = curl_init();
    $options = [
        CURLOPT_URL => $url,
        CURLOPT_USERAGENT => UC_UA,
        CURLOPT_TIMEOUT => $timeout,
        CURLOPT_CONNECTTIMEOUT => min(5, $timeout),
        CURLOPT_FOLLOWLOCATION => false, // Redirects werden manuell + validiert gefolgt
        CURLOPT_PROTOCOLS => CURLPROTO_HTTP | CURLPROTO_HTTPS,
        CURLOPT_REDIR_PROTOCOLS => CURLPROTO_HTTP | CURLPROTO_HTTPS,
        // IPv6 braucht eckige Klammern im RESOLVE-Eintrag, sonst verwirft libcurl ihn still
        CURLOPT_RESOLVE => [$host . ':' . $port . ':' . (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) !== false ? '[' . $ip . ']' : $ip)],
        CURLOPT_NOBODY => $headOnly,
        CURLOPT_HTTPHEADER => $extraHeaders,
        CURLOPT_HEADERFUNCTION => static function ($ch, string $line) use (&$state): int {
            $len = strlen($line);
            $t = trim($line);
            if ($t !== '' && str_contains($t, ':')) {
                [$k, $v] = explode(':', $t, 2);
                $state['headers'][strtolower(trim($k))][] = trim($v);
            }
            return $len;
        },
        CURLOPT_WRITEFUNCTION => static function ($ch, string $chunk) use (&$state, $maxBytes): int {
            $state['body'] .= $chunk;
            if (strlen($state['body']) > $maxBytes) {
                $state['overflow'] = true;
                return 0; // Transfer abbrechen, Body bleibt gekappt
            }
            return strlen($chunk);
        },
    ];
    if (!$headOnly) {
        $options[CURLOPT_ENCODING] = 'gzip'; // curl sendet Accept-Encoding und dekodiert
    } else {
        // HEAD: kein Body zu dekodieren – darum br explizit anbieten (Brotli-Probe)
        $options[CURLOPT_HTTPHEADER] = array_merge($extraHeaders, ['Accept-Encoding: gzip, br']);
    }
    // Rückgabewert prüfen: schlägt das Setzen einer Option fehl (z. B. ungültiger
    // RESOLVE-Eintrag), darf der Request NICHT ungepinnt rausgehen (Rebinding-Fenster)
    if (!curl_setopt_array($ch, $options)) {
        curl_close($ch);
        return null;
    }
    return $ch;
}

// Ergebnis eines Hops aus Handle + State lesen
function uc_hop_result($ch, array $state, string $url): array
{
    $errno = curl_errno($ch);
    $errstr = curl_error($ch);
    if ($errno !== 0 && !$state['overflow']) {
        return [
            'ok' => false,
            'error' => $errstr !== '' ? $errstr : 'curl-Fehler ' . $errno,
            'url' => $url,
        ];
    }
    return [
        'ok' => true,
        'status' => (int)curl_getinfo($ch, CURLINFO_RESPONSE_CODE),
        'headers' => $state['headers'],
        'body' => $state['body'],
        'ttfb' => (float)curl_getinfo($ch, CURLINFO_STARTTRANSFER_TIME),
        'total' => (float)curl_getinfo($ch, CURLINFO_TOTAL_TIME),
        'bytes' => strlen($state['body']),
        'truncated' => $state['overflow'],
        'url' => $url,
    ];
}

// ---- Seite holen: validierte Redirect-Kette (max. UC_MAX_REDIRECTS) ----
function uc_fetch_page(string $url, int $timeout = 8, array $opts = []): array
{
    $chain = [];
    $current = $url;
    for ($hop = 0; $hop <= UC_MAX_REDIRECTS; $hop++) {
        $p = parse_url($current);
        $host = strtolower(trim((string)($p['host'] ?? ''), '[]'));
        $scheme = strtolower((string)($p['scheme'] ?? 'https'));
        $port = $p['port'] ?? ($scheme === 'https' ? 443 : 80);
        if ($host === '' || !in_array($scheme, ['http', 'https'], true) || !in_array($port, [80, 443], true)) {
            return ['ok' => false, 'error' => 'ssrf_blocked', 'chain' => $chain, 'url' => $current];
        }
        $res = uc_resolve_host($host);
        if (is_string($res)) {
            return ['ok' => false, 'error' => $res, 'chain' => $chain, 'url' => $current];
        }
        $state = [];
        $ch = uc_curl_init_hop(
            $current,
            $res['ip'],
            $timeout,
            (int)($opts['max_bytes'] ?? UC_MAX_BYTES),
            (array)($opts['headers'] ?? []),
            (bool)($opts['head'] ?? false),
            $state
        );
        if ($ch === null) {
            return ['ok' => false, 'error' => 'unreachable', 'chain' => $chain, 'url' => $current];
        }
        curl_exec($ch);
        $r = uc_hop_result($ch, $state, $current);
        curl_close($ch);
        if (!$r['ok']) {
            return ['ok' => false, 'error' => 'unreachable', 'detail' => $r['error'] ?? '', 'chain' => $chain, 'url' => $current];
        }
        $chain[] = ['url' => $current, 'status' => (int)$r['status']];
        $location = $r['headers']['location'][0] ?? '';
        if (in_array((int)$r['status'], [301, 302, 303, 307, 308], true) && $location !== '') {
            $next = uc_resolve_relative($current, $location);
            if ($next === null) {
                return ['ok' => false, 'error' => 'fetch_failed', 'detail' => 'Ungültige Weiterleitung.', 'chain' => $chain, 'url' => $current];
            }
            $current = $next; // nächster Hop wird oben erneut validiert (Schema, Port, DNS, IP)
            continue;
        }
        $r['chain'] = $chain;
        return $r;
    }
    return ['ok' => false, 'error' => 'fetch_failed', 'detail' => 'Zu viele Weiterleitungen.', 'chain' => $chain, 'url' => $current];
}

// ---- Relative Location gegen Basis-URL auflösen ----
function uc_resolve_relative(string $base, string $location): ?string
{
    $location = trim($location);
    if ($location === '') {
        return null;
    }
    if (preg_match('#^https?://#i', $location)) {
        return uc_normalize_url($location);
    }
    $b = parse_url($base);
    if (!is_array($b) || empty($b['host'])) {
        return null;
    }
    $scheme = strtolower((string)($b['scheme'] ?? 'https'));
    $host = strtolower((string)$b['host']);
    $port = $b['port'] ?? ($scheme === 'https' ? 443 : 80);
    if (str_starts_with($location, '//')) {
        return uc_normalize_url($scheme . ':' . $location);
    }
    if (str_starts_with($location, '/')) {
        $newPath = $location;
    } else {
        $dir = preg_replace('#[^/]*$#', '', (string)($b['path'] ?? '/'));
        $newPath = $dir . $location;
    }
    // . und .. auflösen
    $parts = [];
    foreach (explode('/', $newPath) as $seg) {
        if ($seg === '' || $seg === '.') {
            continue;
        }
        if ($seg === '..') {
            array_pop($parts);
            continue;
        }
        $parts[] = $seg;
    }
    $isDefaultPort = ($scheme === 'http' && $port === 80) || ($scheme === 'https' && $port === 443);
    return uc_normalize_url($scheme . '://' . $host . ($isDefaultPort ? '' : ':' . $port) . '/' . implode('/', $parts));
}

// ---- Paralleles Holen mehrerer URLs (curl_multi), ohne Redirect-Follow ----
// $urls: ['schluessel' => url, …]; Rückgabe: ['schluessel' => Ergebnis wie uc_hop_result]
// Optionen: max_bytes, headers, head_keys (Liste von Schlüsseln als HEAD), head_all
function uc_fetch_multi(array $urls, int $timeout = 8, array $opts = []): array
{
    $out = [];
    $handles = [];
    $mh = curl_multi_init();
    $maxBytes = (int)($opts['max_bytes'] ?? UC_MAX_BYTES);
    $headKeys = (array)($opts['head_keys'] ?? []);
    $headAll = !empty($opts['head_all']);

    foreach ($urls as $key => $url) {
        $p = parse_url($url);
        $host = strtolower(trim((string)($p['host'] ?? ''), '[]'));
        $scheme = strtolower((string)($p['scheme'] ?? 'https'));
        $port = $p['port'] ?? ($scheme === 'https' ? 443 : 80);
        if ($host === '' || !in_array($scheme, ['http', 'https'], true) || !in_array($port, [80, 443], true)) {
            $out[$key] = ['ok' => false, 'error' => 'ssrf_blocked', 'url' => $url];
            continue;
        }
        $res = uc_resolve_host($host);
        if (is_string($res)) {
            $out[$key] = ['ok' => false, 'error' => $res, 'url' => $url];
            continue;
        }
        $head = $headAll || in_array($key, $headKeys, true);
        $handles[$key] = ['state' => [], 'url' => $url];
        $ch = uc_curl_init_hop($url, $res['ip'], $timeout, $maxBytes, (array)($opts['headers'] ?? []), $head, $handles[$key]['state']);
        if ($ch === null) {
            unset($handles[$key]);
            $out[$key] = ['ok' => false, 'error' => 'unreachable', 'url' => $url];
            continue;
        }
        $handles[$key]['ch'] = $ch;
        curl_multi_add_handle($mh, $ch);
    }

    $running = 0;
    do {
        $mrc = curl_multi_exec($mh, $running);
        if ($running > 0 && curl_multi_select($mh, 1.0) === -1) {
            usleep(50000); // select ohne FDS: kurz warten
        }
    } while ($running > 0 && $mrc === CURLM_OK);

    foreach ($handles as $key => $h) {
        $out[$key] = uc_hop_result($h['ch'], $h['state'], $h['url']);
        curl_multi_remove_handle($mh, $h['ch']);
        curl_close($h['ch']);
    }
    curl_multi_close($mh);
    return $out;
}

// ---- PageSpeed Insights (optional; leerer Key = deaktiviert) ----
function uc_psi_fetch(string $url): ?array
{
    $key = trim((string)uc_config('psi_api_key'));
    if ($key === '') {
        return null;
    }
    $api = 'https://www.googleapis.com/pagespeedonline/v5/runPagespeed'
        . '?url=' . urlencode($url) . '&strategy=mobile&category=performance&key=' . urlencode($key);
    $r = uc_fetch_page($api, 15, ['max_bytes' => 6291456]);
    if (empty($r['ok']) || ($r['status'] ?? 0) !== 200) {
        return null;
    }
    $j = json_decode((string)$r['body'], true);
    if (!is_array($j) || !isset($j['lighthouseResult']['categories']['performance']['score'])) {
        return null;
    }
    $audits = $j['lighthouseResult']['audits'] ?? [];
    return [
        'score' => (int)round(((float)$j['lighthouseResult']['categories']['performance']['score']) * 100),
        'lcp' => (string)($audits['largest-contentful-paint']['displayValue'] ?? '–'),
        'cls' => (string)($audits['cumulative-layout-shift']['displayValue'] ?? '–'),
    ];
}
