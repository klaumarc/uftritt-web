<?php
// Botguard: zusätzlicher Formular-Schutz (Zeit-Check, Linkfilter, Cloudflare
// Turnstile). Ergänzt den Honeypot in kontakt.php / besprechung.php /
// uftakt-start.php. Konfiguration über uftritt-forms-config.php im
// Subscription-Root (eine Ebene über dem Docroot, NICHT im Repo):
//   <?php return ['turnstile_secret' => '...'];

declare(strict_types=1);

function botguard_config(): array
{
    static $cfg = null;
    if ($cfg === null) {
        $file = dirname((string)($_SERVER['DOCUMENT_ROOT'] ?? '')) . '/uftritt-forms-config.php';
        $cfg = is_file($file) ? include $file : [];
        if (!is_array($cfg)) {
            $cfg = [];
        }
    }
    return $cfg;
}

// Liefert einen Drop-Grund (Bot: still verwerfen wie der Honeypot) oder null.
// Der Zeit-Check greift nur, wenn das per JS gesetzte Feld f_ts mitkommt,
// damit Besucher ohne JavaScript weiterhin durchkommen.
function botguard_drop_reason(array $post): ?string
{
    $ts = (int)($post['f_ts'] ?? 0);
    if ($ts > 0) {
        $alter_ms = (int)(microtime(true) * 1000) - $ts;
        // Unter ~2,5 s ausgefüllt oder Zeitstempel aus der Zukunft: Bot
        if ($alter_ms < 2500) {
            return 'zu_schnell';
        }
    }

    // Freitext-Felder auf Spam-Muster prüfen. «gefaellt» bleibt absichtlich
    // draussen: Im Uftakt-Formular sollen dort Links hin.
    $freitext = '';
    foreach (['nachricht', 'bemerkung', 'wunschzeiten', 'angebot', 'zielgruppe',
              'leistungen', 'stichworte_start', 'stichworte_ueber',
              'stichworte_leistungen', 'referenzen', 'farben'] as $f) {
        if (!empty($post[$f]) && is_string($post[$f])) {
            $freitext .= $post[$f] . "\n";
        }
    }
    if (preg_match_all('~https?://|www\.~i', $freitext) >= 3) {
        return 'link_spam';
    }
    if ($freitext !== '' && preg_match('/\p{Cyrillic}/u', $freitext)) {
        return 'kyrillisch';
    }
    return null;
}

// Turnstile-Verifizierung, aktiv sobald turnstile_secret konfiguriert ist.
// Fehlendes oder ungültiges Token => false. Ist Cloudflare nicht erreichbar,
// gilt fail-open (geloggt), damit echte Anfragen nicht verloren gehen.
function botguard_turnstile_ok(string $token): bool
{
    $secret = (string)(botguard_config()['turnstile_secret'] ?? '');
    if ($secret === '') {
        return true;
    }
    if ($token === '') {
        return false;
    }
    $ctx = stream_context_create(['http' => [
        'method'  => 'POST',
        'header'  => "Content-Type: application/x-www-form-urlencoded\r\n",
        'content' => http_build_query([
            'secret'   => $secret,
            'response' => $token,
            'remoteip' => (string)($_SERVER['REMOTE_ADDR'] ?? ''),
        ]),
        'timeout' => 4,
    ]]);
    $raw = @file_get_contents('https://challenges.cloudflare.com/turnstile/v0/siteverify', false, $ctx);
    if ($raw === false) {
        error_log('botguard: siteverify nicht erreichbar (fail-open)');
        return true;
    }
    $res = json_decode((string)$raw, true);
    return (bool)($res['success'] ?? false);
}
