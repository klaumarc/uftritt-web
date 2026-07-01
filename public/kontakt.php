<?php
// Kontaktformular-Handler für Plesk/Kreativmedia (PHP mail()).
// Astro kopiert public/*.php nach dist/ -> auf dem Server unter /kontakt.php erreichbar.

// ---- Einstellungen ----
$empfaenger = 'info@uftritt.ch';
// Absender MUSS auf der eigenen Domain liegen, sonst blockt SPF/DMARC.
// Beim Domainwechsel ggf. auf no-reply@uftritt.ch anpassen.
$absender   = 'no-reply@uftritt.ch';
$danke_url  = '/danke/';
$fehler_url = '/kontakt/?fehler=1';

// ---- Nur POST zulassen ----
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /kontakt/');
    exit;
}

// ---- Spam-Honeypot: für Menschen unsichtbar, Bots füllen es aus ----
if (!empty($_POST['bot-field'])) {
    header('Location: ' . $danke_url); // Bot still "danken", aber nichts senden
    exit;
}

// ---- Eingaben einlesen ----
$name      = trim($_POST['name']      ?? '');
$email     = trim($_POST['email']     ?? '');
$nachricht = trim($_POST['nachricht'] ?? '');

// ---- Validierung ----
$email_ok = filter_var($email, FILTER_VALIDATE_EMAIL);
if ($name === '' || !$email_ok || $nachricht === '') {
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
$betreff = "Neue Anfrage über uftritt.ch – von $name_h";

$body  = "Juhui – es hat sich jemand über das Kontaktformular auf uftritt.ch gemeldet! 🎉\n\n";
$body .= "────────────────────────────────────────\n";
$body .= "  Von:      $name_h\n";
$body .= "  E-Mail:   $email_h\n";
$body .= "────────────────────────────────────────\n\n";
$body .= "Nachricht:\n\n";
$body .= "$nachricht\n\n";
$body .= "————\n";
$body .= "Tipp: Einfach direkt auf diese E-Mail antworten, um $name_h zu erreichen.\n";
$body .= "Automatische Benachrichtigung vom Kontaktformular · uftritt.ch\n";

$headers  = "From: Uftritt Website <$absender>\r\n";
$headers .= "Reply-To: $name_h <$email_h>\r\n";
$headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
$headers .= "X-Mailer: PHP/" . phpversion();

$betreff_enc = '=?UTF-8?B?' . base64_encode($betreff) . '?=';

// ---- Senden ----
$gesendet = mail($empfaenger, $betreff_enc, $body, $headers, '-f' . $absender);

header('Location: ' . ($gesendet ? $danke_url : $fehler_url));
exit;
