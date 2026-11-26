<?php
// Website-Check: gemeinsamer Bootstrap (Config, Env, Pfade, JSON-Helper, Deadline).
// Wird von roast.php, report-request.php und report.php eingebunden.

declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');
date_default_timezone_set('Europe/Zurich');
@set_time_limit(50); // Server-Limit ist 60 s, wir planen mit 45 s Budget

// Hartes Gesamtbudget pro Request
define('UC_TIME_BUDGET', 45.0);

$GLOBALS['UC'] = [
    'started' => microtime(true),
    'config'  => [],
    'env'     => 'prod',
    'storage' => '',
    'base_url' => '',
];

// ---- Config: liegt ausserhalb des Docroot (per FTP abgelegt, NICHT im Repo) ----
// Erwartetes Format von uftritt-check-config.php: <?php return ['moonshot_api_key' => '…', …];
$uc_defaults = [
    'moonshot_api_key'     => '',
    'moonshot_model'       => 'kimi-k3',
    'moonshot_temperature' => 1,     // kimi-k2.6/k3 erlauben NUR 1 (API-Fehler 400 sonst)
    'moonshot_reasoning'   => 'low', // Reasoning-Modelle: ohne 'low' denken sie lang (Timeout)
    'moonshot_max_tokens'  => 4000,  // Reasoning-Tokens zählen mit
    'psi_api_key'          => '',   // leer = PageSpeed Insights deaktiviert
    'llm_enabled'          => true,
    'ip_salt'              => '',   // Salt für den IP-Hash (Rate-Limits, Lead-Log)
    'base_url'             => '',   // optionaler Override, sonst Host-Allowlist unten
    'stripe_secret_key_test' => '', // Stripe Secret Key (Test-Modus) – für Staging
    'stripe_secret_key_live' => '', // Stripe Secret Key (Live) – für Prod
];
$uc_config_file = dirname((string)($_SERVER['DOCUMENT_ROOT'] ?? '')) . '/uftritt-check-config.php';
$uc_user_config = is_file($uc_config_file) ? include $uc_config_file : [];
if (!is_array($uc_user_config)) {
    $uc_user_config = [];
}
$GLOBALS['UC']['config'] = array_merge($uc_defaults, $uc_user_config);
// Ohne API-Key ist das LLM faktisch deaktiviert
if (trim((string)$GLOBALS['UC']['config']['moonshot_api_key']) === '') {
    $GLOBALS['UC']['config']['llm_enabled'] = false;
}

// ---- Environment + Basis-URL: nur bekannte Hosts akzeptieren ----
// Der Host-Header ist Client-Input und fälschbar – er darf nie in Mail-Links
// oder in die Env-/Storage-Wahl einfliessen (Security-Gate 2026-08-18).
$uc_host = strtolower((string)($_SERVER['HTTP_HOST'] ?? ''));
$uc_hosts = [
    'www.uftritt.ch' => ['prod', 'https://www.uftritt.ch'],
    'uftritt.ch' => ['prod', 'https://www.uftritt.ch'],
    'uftritt.mklkreativ.ch' => ['staging', 'https://uftritt.mklkreativ.ch'],
];
[$GLOBALS['UC']['env'], $GLOBALS['UC']['base_url']] = $uc_hosts[$uc_host] ?? ['prod', 'https://www.uftritt.ch'];
if (trim((string)$GLOBALS['UC']['config']['base_url']) !== '') {
    $GLOBALS['UC']['base_url'] = (string)$GLOBALS['UC']['config']['base_url'];
}

// ---- Storage: ausserhalb des Docroot, open_basedir-konform ----
$GLOBALS['UC']['storage'] = dirname((string)($_SERVER['DOCUMENT_ROOT'] ?? ''))
    . '/uftritt-check-storage/' . $GLOBALS['UC']['env'];
foreach (['', '/cache', '/reports', '/ratelimit', '/unlocks'] as $uc_sub) {
    $uc_dir = $GLOBALS['UC']['storage'] . $uc_sub;
    if (!is_dir($uc_dir)) {
        @mkdir($uc_dir, 0750, true);
    }
}

// Defense-in-depth: Storage zusätzlich per .htaccess sperren (falls der Pfad
// durch eine künftige vhost-Fehlkonfiguration doch web-erreichbar würde)
$uc_storage_root = dirname($GLOBALS['UC']['storage']);
if (is_dir($uc_storage_root) && !is_file($uc_storage_root . '/.htaccess')) {
    @file_put_contents($uc_storage_root . '/.htaccess', "Require all denied\n");
}

unset($uc_defaults, $uc_config_file, $uc_user_config, $uc_host, $uc_hosts, $uc_sub, $uc_dir, $uc_storage_root);

// ---- Deadline-Tracker ----
// budget_extra: Endpunkte mit schwerer Nachbearbeitung (z. B. report-unlock mit
// PSI + Volltext) koennen das Budget einmalig erhoehen, sobald set_time_limit
// entsprechend angehoben wurde (FPM-Terminate-Timeout des Hosters beachten).
function uc_time_left(): float
{
    $budget = UC_TIME_BUDGET + (float)($GLOBALS['UC']['budget_extra'] ?? 0.0);
    return $budget - (microtime(true) - $GLOBALS['UC']['started']);
}

// true, wenn weniger als $needed Sekunden übrig sind
function uc_deadline_close(float $needed = 0.0): bool
{
    return uc_time_left() < $needed;
}

// ---- JSON-Responses (alle API-Endpunkte: noindex, no-store) ----
function uc_json(array $data, int $status = 200): void
{
    if (!headers_sent()) {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        header('X-Robots-Tag: noindex, nofollow');
        header('X-Content-Type-Options: nosniff');
        header('Cache-Control: no-store');
    }
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function uc_error(string $code, string $message, int $status = 400, array $extra = []): void
{
    uc_json(array_merge(['ok' => false, 'error' => $code, 'message' => $message], $extra), $status);
}

// ---- POST-Feld sicher als String holen ----
function uc_post(string $key): string
{
    $v = $_POST[$key] ?? '';
    return is_string($v) ? trim($v) : '';
}

// ---- Client-IP nie im Klartext, immer als gesalzener Hash ----
// (ungesalzen ist sha256 über ~4 Mrd. IPv4-Adressen trivial brute-forcebar)
function uc_ip_hash(): string
{
    $salt = (string)($GLOBALS['UC']['config']['ip_salt'] ?? '');
    return hash_hmac('sha256', (string)($_SERVER['REMOTE_ADDR'] ?? 'cli'), $salt !== '' ? $salt : 'uftritt-website-check');
}

function uc_config(string $key)
{
    return $GLOBALS['UC']['config'][$key] ?? null;
}
