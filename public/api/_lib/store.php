<?php
// Datei-basiertes Storage: URL-Cache (24 h), Roast-Ablage (1 h), Rate-Limits (flock),
// Leads (JSONL), Reports, 90-Tage-Cleanup mit 2 %-Wahrscheinlichkeit pro Request.

declare(strict_types=1);

define('UC_CACHE_TTL', 24 * 3600);
define('UC_ROAST_TTL', 3600);

function uc_path(string $sub, string $file): string
{
    return $GLOBALS['UC']['storage'] . '/' . $sub . '/' . $file;
}

// ---- generisches JSON lesen/schreiben mit flock ----
function uc_json_read(string $path): ?array
{
    if (!is_file($path)) {
        return null;
    }
    $fh = @fopen($path, 'rb');
    if (!$fh) {
        return null;
    }
    flock($fh, LOCK_SH);
    $raw = stream_get_contents($fh);
    flock($fh, LOCK_UN);
    fclose($fh);
    if (!is_string($raw) || $raw === '') {
        return null;
    }
    $j = json_decode($raw, true);
    return is_array($j) ? $j : null;
}

function uc_json_write(string $path, array $data): bool
{
    $fh = @fopen($path, 'cb');
    if (!$fh) {
        return false;
    }
    flock($fh, LOCK_EX);
    ftruncate($fh, 0);
    rewind($fh);
    $ok = fwrite($fh, json_encode($data, JSON_UNESCAPED_UNICODE)) !== false;
    fflush($fh);
    flock($fh, LOCK_UN);
    fclose($fh);
    @chmod($path, 0640);
    return $ok;
}

// ---- Analyse-Cache: sha256(normalisierte URL), 24 h gültig ----
function uc_cache_key(string $normalizedUrl): string
{
    return hash('sha256', $normalizedUrl);
}

function uc_cache_get(string $normalizedUrl): ?array
{
    $d = uc_json_read(uc_path('cache', 'a_' . uc_cache_key($normalizedUrl) . '.json'));
    if ($d === null || empty($d['ts']) || (time() - (int)$d['ts']) > UC_CACHE_TTL) {
        return null;
    }
    return $d;
}

function uc_cache_set(string $normalizedUrl, array $analysis): void
{
    uc_json_write(uc_path('cache', 'a_' . uc_cache_key($normalizedUrl) . '.json'), [
        'ts' => time(),
        'url' => $normalizedUrl,
        'analysis' => $analysis,
    ]);
}

// ---- Roast-Ablage: frische ID pro Antwort, für report-request 1 h gültig ----
function uc_roast_create(string $normalizedUrl, array $analysis): string
{
    $id = bin2hex(random_bytes(32)); // hex64
    uc_json_write(uc_path('cache', 'roast_' . $id . '.json'), [
        'ts' => time(),
        'url' => $normalizedUrl,
        'analysis' => $analysis,
    ]);
    return $id;
}

function uc_roast_get(string $id): ?array
{
    if (!preg_match('/^[a-f0-9]{64}$/', $id)) {
        return null;
    }
    $d = uc_json_read(uc_path('cache', 'roast_' . $id . '.json'));
    // TTL hier durchsetzen, sonst bleibt eine ergatterte ID ~90 Tage einlösbar (Cleanup-Intervall)
    if ($d === null || empty($d['ts']) || (time() - (int)$d['ts']) > UC_ROAST_TTL) {
        return null;
    }
    return $d;
}

// Meldet einen Roast als „Report bereits versendet" (max. 1 Report-Versand pro Roast,
// damit eine roast_id nicht als Mail-Relay für beliebig viele Empfänger dient)
function uc_roast_mark_used(string $id): void
{
    if (!preg_match('/^[a-f0-9]{64}$/', $id)) {
        return;
    }
    $path = uc_path('cache', 'roast_' . $id . '.json');
    $d = uc_json_read($path);
    if (is_array($d)) {
        $d['report_sent'] = true;
        uc_json_write($path, $d);
    }
}

// ---- Rate-Limits (Datei-basiert, flock) ----
// null = erlaubt (Zähler wurde erhöht); ['retry_after_h' => n] = blockiert
function uc_ratelimit_hit(string $bucket, string $key, int $limit): ?array
{
    $day = date('Y-m-d');
    $bucket = preg_replace('/[^a-z_]/', '', $bucket);
    $file = uc_path('ratelimit', $bucket . '_' . hash('sha256', $key) . '.json');
    $fh = @fopen($file, 'c+b');
    if (!$fh) {
        return null; // Storage-Problem: im Zweifel nicht blockieren
    }
    flock($fh, LOCK_EX);
    rewind($fh);
    $raw = stream_get_contents($fh);
    $state = is_string($raw) && $raw !== '' ? json_decode($raw, true) : null;
    if (!is_array($state) || ($state['day'] ?? '') !== $day) {
        $state = ['day' => $day, 'count' => 0];
    }
    if ((int)$state['count'] >= $limit) {
        flock($fh, LOCK_UN);
        fclose($fh);
        return ['retry_after_h' => uc_retry_after_h()];
    }
    $state['count'] = (int)$state['count'] + 1;
    rewind($fh);
    ftruncate($fh, 0);
    fwrite($fh, json_encode($state));
    fflush($fh);
    flock($fh, LOCK_UN);
    fclose($fh);
    return null;
}

// Stunden bis Mitternacht (für retry_after_h)
function uc_retry_after_h(): int
{
    return max(1, (int)ceil((strtotime('tomorrow') - time()) / 3600));
}

// Verbleibende Aufrufe heute, OHNE zu erhöhen (Anzeige „noch x Gratis-Checks")
function uc_ratelimit_remaining(string $bucket, string $key, int $limit): int
{
    $day = date('Y-m-d');
    $bucket = preg_replace('/[^a-z_]/', '', $bucket);
    $state = uc_json_read(uc_path('ratelimit', $bucket . '_' . hash('sha256', $key) . '.json'));
    if (!is_array($state) || ($state['day'] ?? '') !== $day) {
        return $limit;
    }
    return max(0, $limit - (int)($state['count'] ?? 0));
}

// ---- Leads: eine Zeile JSON pro Lead ----
function uc_lead_append(array $lead): void
{
    $fh = @fopen($GLOBALS['UC']['storage'] . '/leads.jsonl', 'ab');
    if (!$fh) {
        return;
    }
    flock($fh, LOCK_EX);
    fwrite($fh, json_encode($lead, JSON_UNESCAPED_UNICODE) . "\n");
    fflush($fh);
    flock($fh, LOCK_UN);
    fclose($fh);
    @chmod($GLOBALS['UC']['storage'] . '/leads.jsonl', 0640);
}

// ---- Reports speichern/laden ----
function uc_report_save(string $id, array $report): bool
{
    return uc_json_write(uc_path('reports', $id . '.json'), $report);
}

function uc_report_load(string $id): ?array
{
    if (!preg_match('/^[a-f0-9]{32}$/', $id)) {
        return null;
    }
    return uc_json_read(uc_path('reports', $id . '.json'));
}

// ---- Cleanup: mit 2 %-Wahrscheinlichkeit pro Request alte Dateien löschen ----
function uc_cleanup_maybe(): void
{
    if (mt_rand(1, 50) !== 1) {
        return;
    }
    $now = time();
    $wipe = static function (string $dir, int $maxAge) use ($now): void {
        foreach ((array)@glob($dir . '/*.json') as $f) {
            if (is_file($f) && ($now - (int)@filemtime($f)) > $maxAge) {
                @unlink($f);
            }
        }
    };
    $wipe($GLOBALS['UC']['storage'] . '/cache', 90 * 86400);     // inkl. roast_*.json
    $wipe($GLOBALS['UC']['storage'] . '/reports', 90 * 86400);   // Report-Löschung nach 90 Tagen
    $wipe($GLOBALS['UC']['storage'] . '/ratelimit', 2 * 86400);  // Tages-Zähler
}
