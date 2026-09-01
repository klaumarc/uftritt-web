<?php
// Gewinn-Endpoint für das Uftritt-Spiel «Abheben» (/agentur/, /durchstarten/).
// Ab GEWINN_MIN_SCORE Punkten kann ein Preis (Tasse oder Kugelschreiber) eingelöst
// werden. Regeln: ein Preis pro Person (Dedupe per E-Mail-Hash, Idempotenz bei
// Retry innert 24 h), Tages-Rate-Limit pro IP, Honeypot. Mail an info@uftritt.ch
// im selben Pattern wie kontakt.php (SPF-sauber via no-reply@uftritt.ch).
//
// Ehrlichkeitshinweis: Der Score kommt aus dem Client und ist fälschbar. Das ist
// bewusst so (Spass-Feature) — jede Einlösung wird von Hand geprüft (Mail).

declare(strict_types=1);
require __DIR__ . '/_lib/bootstrap.php';
require __DIR__ . '/_lib/store.php';

const GEWINN_MIN_SCORE    = 10000;
const GEWINN_IP_TAGESLIMIT = 8;
const GEWINN_PREISE = ['tasse' => 'Uftritt-Tasse', 'stift' => 'Uftritt-Kugelschreiber'];

$gewinn_empfaenger = 'info@uftritt.ch';
$gewinn_absender   = 'no-reply@uftritt.ch';

// ---- Nur POST ----
if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    uc_error('method', 'Nur POST erlaubt.', 405);
}

// ---- Honeypot: Bots still «erfolgreich» beantworten, nichts speichern/senden ----
if (uc_post('bot-field') !== '') {
    uc_json(['ok' => true]);
}

// ---- Eingaben ----
$gw_name  = uc_post('name');
$gw_email = strtolower(uc_post('email'));
$gw_preis = uc_post('preis');
$gw_score = (int)uc_post('score');

if ($gw_name === '' || mb_strlen($gw_name) > 100) {
    uc_error('name', 'Bitte gib deinen Namen an.');
}
if (!filter_var($gw_email, FILTER_VALIDATE_EMAIL)) {
    uc_error('email', 'Diese E-Mail-Adresse sieht nicht gültig aus.');
}
if (!isset(GEWINN_PREISE[$gw_preis])) {
    uc_error('preis', 'Bitte wähle einen Preis (Tasse oder Kugelschreiber).');
}
if ($gw_score < GEWINN_MIN_SCORE || $gw_score > 1000000) {
    uc_error('score', 'Der Score reicht für einen Preis leider nicht – weiter üben!');
}

// ---- Rate-Limit pro IP ----
if (uc_ratelimit_hit('gewinn', uc_ip_hash(), GEWINN_IP_TAGESLIMIT) !== null) {
    uc_error('limit', 'Das ging heute schon zu oft – bitte versuch es morgen wieder.', 429);
}

// ---- Dedupe: ein Preis pro Person (E-Mail-Hash) ----
$gw_file = $GLOBALS['UC']['storage'] . '/gewinne.jsonl';
$gw_eh   = hash('sha256', $gw_email);
$gw_retry_row = null;
if (is_file($gw_file)) {
    $gw_fh = @fopen($gw_file, 'rb');
    if ($gw_fh) {
        flock($gw_fh, LOCK_SH);
        while (($gw_line = fgets($gw_fh)) !== false) {
            $gw_row = json_decode($gw_line, true);
            if (is_array($gw_row) && ($gw_row['eh'] ?? '') === $gw_eh) {
                $gw_retry_row = $gw_row;
                break;
            }
        }
        flock($gw_fh, LOCK_UN);
        fclose($gw_fh);
    }
}
if ($gw_retry_row !== null) {
    // Retry innert 24 h (z. B. nach Mail-Fehler): gespeicherte Daten verwenden und
    // die Mail ERNEUT senden – danach erst «duplicate». Kein zweiter Claim-Eintrag.
    if (time() - (int)$gw_retry_row['ts'] < 86400) {
        $gw_name  = (string)$gw_retry_row['name'];
        $gw_email = (string)$gw_retry_row['email'];
        $gw_preis = (string)$gw_retry_row['preis'];
        $gw_score = (int)$gw_retry_row['score'];
        if (!isset(GEWINN_PREISE[$gw_preis])) {
            uc_error('duplicate', 'Mit dieser E-Mail wurde schon ein Preis eingelöst – nur einer pro Person.', 409);
        }
    } else {
        uc_error('duplicate', 'Mit dieser E-Mail wurde schon ein Preis eingelöst – nur einer pro Person.', 409);
    }
} else {
    // ---- Claim speichern (JSONL, ausserhalb Docroot) ----
    $gw_fh = @fopen($gw_file, 'ab');
    if ($gw_fh) {
        flock($gw_fh, LOCK_EX);
        fwrite($gw_fh, json_encode([
            'ts'    => time(),
            'eh'    => $gw_eh,
            'name'  => $gw_name,
            'email' => $gw_email,
            'preis' => $gw_preis,
            'score' => $gw_score,
            'ip'    => uc_ip_hash(),
        ], JSON_UNESCAPED_UNICODE) . "\n");
        fflush($gw_fh);
        flock($gw_fh, LOCK_UN);
        fclose($gw_fh);
        @chmod($gw_file, 0640);
    }
}

// ---- Mail an Uftritt (Pattern wie kontakt.php) ----
$gw_clean = static function (string $v): string {
    return str_replace(["\r", "\n", "%0a", "%0d"], '', $v);
};
$gw_name_h  = $gw_clean($gw_name);
$gw_email_h = $gw_clean($gw_email);

$gw_betreff = 'Gewinn im Uftritt-Spiel – ' . GEWINN_PREISE[$gw_preis] . ' für ' . $gw_name_h;

$gw_body  = "Juhui – jemand hat das Uftritt-Spiel «Abheben» geknackt! 🎉\n\n";
$gw_body .= "────────────────────────────────────────\n";
$gw_body .= '  Gewinner:in: ' . $gw_name_h . "\n";
$gw_body .= '  E-Mail:      ' . $gw_email_h . "\n";
$gw_body .= '  Preis:       ' . GEWINN_PREISE[$gw_preis] . "\n";
$gw_body .= '  Score:       ' . number_format($gw_score, 0, '.', "'") . "\n";
$gw_body .= '  Zeitpunkt:   ' . date('d.m.Y, H:i') . "\n";
$gw_body .= "────────────────────────────────────────\n\n";
$gw_body .= "Übergabe wie besprochen: Abholung in Saland, bei einem Kaffee\n";
$gw_body .= "oder beim nächsten Besuch – einfach per Mail abstimmen.\n\n";
$gw_body .= "Tipp: Direkt auf diese E-Mail antworten, um $gw_name_h zu erreichen.\n";
$gw_body .= "Automatische Benachrichtigung vom Uftritt-Spiel · uftritt.ch\n";

$gw_headers  = "From: Uftritt Spiel <$gewinn_absender>\r\n";
$gw_headers .= "Reply-To: $gw_name_h <$gw_email_h>\r\n";
$gw_headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
$gw_headers .= "Content-Transfer-Encoding: quoted-printable\r\n";
$gw_headers .= "X-Mailer: PHP/" . phpversion();

$gw_betreff_enc = '=?UTF-8?B?' . base64_encode($gw_betreff) . '?=';
$gw_gesendet = mail($gewinn_empfaenger, $gw_betreff_enc, quoted_printable_encode($gw_body), $gw_headers, '-f' . $gewinn_absender);

if (!$gw_gesendet) {
    // Claim bleibt gespeichert (Retry innert 24 h möglich, siehe Dedupe)
    uc_error('mail', 'Der Gewinn konnte gerade nicht gemeldet werden – bitte gleich nochmal versuchen.', 500);
}

uc_json(['ok' => true]);
