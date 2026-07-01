<?php
// Kontaktformular-Handler für Plesk/Kreativmedia (PHP mail()).
// Astro kopiert public/*.php nach dist/ -> auf dem Server unter /kontakt.php erreichbar.

// ---- Einstellungen ----
$empfaenger = 'info@uftritt.ch';
// Absender MUSS auf der eigenen Domain liegen, sonst blockt SPF/DMARC.
// Beim Domainwechsel ggf. auf no-reply@uftritt.ch anpassen.
$absender   = 'no-reply@uftritt.ch';
$betreff    = 'Neue Nachricht über das Kontaktformular';
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
$body  = "Neue Nachricht über das Kontaktformular von uftritt.ch\n\n";
$body .= "Name:    $name_h\n";
$body .= "E-Mail:  $email_h\n\n";
$body .= "Nachricht:\n$nachricht\n";

$headers  = "From: Uftritt Website <$absender>\r\n";
$headers .= "Reply-To: $name_h <$email_h>\r\n";
$headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
$headers .= "X-Mailer: PHP/" . phpversion();

$betreff_enc = '=?UTF-8?B?' . base64_encode($betreff) . '?=';

// ---- Senden ----
$gesendet = mail($empfaenger, $betreff_enc, $body, $headers, '-f' . $absender);

header('Location: ' . ($gesendet ? $danke_url : $fehler_url));
exit;
