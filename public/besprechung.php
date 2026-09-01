<?php
// Besprechungs-Formular-Handler für Plesk/Kreativmedia (PHP mail()).
// Gleiches bewährtes Muster wie kontakt.php. Astro kopiert public/*.php nach dist/.

// ---- Einstellungen ----
$empfaenger = 'info@uftritt.ch';
// Absender MUSS auf der eigenen Domain liegen, sonst blockt SPF/DMARC.
$absender   = 'no-reply@uftritt.ch';
$danke_url  = '/danke/';
$fehler_url = '/besprechung/?fehler=1';

// ---- Nur POST zulassen ----
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /besprechung/');
    exit;
}

// ---- Spam-Honeypot: für Menschen unsichtbar, Bots füllen es aus ----
if (!empty($_POST['bot-field'])) {
    header('Location: ' . $danke_url); // Bot still "danken", aber nichts senden
    exit;
}

// ---- Eingaben einlesen ----
$name         = trim($_POST['name']         ?? '');
$email        = trim($_POST['email']        ?? '');
$telefon      = trim($_POST['telefon']      ?? '');
$wunschzeiten = trim($_POST['wunschzeiten'] ?? '');
$website      = trim($_POST['website']      ?? '');
$bemerkung    = trim($_POST['bemerkung']    ?? '');

// ---- Validierung ----
$email_ok = filter_var($email, FILTER_VALIDATE_EMAIL);
if ($name === '' || !$email_ok || $wunschzeiten === '') {
    header('Location: ' . $fehler_url);
    exit;
}

// ---- Header-Injection verhindern (Zeilenumbrüche aus Kopfzeilen entfernen) ----
$clean = static function (string $v): string {
    return str_replace(["\r", "\n", "%0a", "%0d"], '', $v);
};
$name_h  = $clean($name);
$email_h = $clean($email);

// ---- Mail zusammenbauen ----
$betreff = "Besprechung angefragt – von $name_h";

$body  = "Neue Anfrage für eine 20-Minuten-Besprechung (Website-Check)! 🎉\n\n";
$body .= "────────────────────────────────────────\n";
$body .= "  Von:      $name_h\n";
$body .= "  E-Mail:   $email_h\n";
$body .= "  Telefon:  " . ($telefon !== '' ? $clean($telefon) : '–') . "\n";
$body .= "  Website:  " . ($website !== '' ? $clean($website) : '–') . "\n";
$body .= "────────────────────────────────────────\n\n";
$body .= "Wunschzeiten:\n$wunschzeiten\n\n";
if ($bemerkung !== '') {
    $body .= "Bemerkung:\n$bemerkung\n\n";
}
$body .= "————\n";
$body .= "Tipp: Einfach direkt auf diese E-Mail antworten, um $name_h zu erreichen.\n";
$body .= "Automatische Benachrichtigung vom Besprechungs-Formular · uftritt.ch\n";

$headers  = "From: Uftritt Website <$absender>\r\n";
$headers .= "Reply-To: $name_h <$email_h>\r\n";
$headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
$headers .= "Content-Transfer-Encoding: quoted-printable\r\n";

$betreff_enc = '=?UTF-8?B?' . base64_encode($betreff) . '?=';

// ---- Senden ----
$gesendet = mail($empfaenger, $betreff_enc, quoted_printable_encode($body), $headers, '-f' . $absender);

header('Location: ' . ($gesendet ? $danke_url : $fehler_url));
exit;
