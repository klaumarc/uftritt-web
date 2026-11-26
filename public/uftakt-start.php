<?php
// Uftakt-Startformular: Kunden liefern Stichworte und Bildmaterial für den Webseiten-Bau.
// Muster wie kontakt.php (Honeypot, AJAX-JSON-Zweig, lead_log), plus Datei-Anhänge
// als MIME-multipart-Mail. Astro kopiert public/*.php nach dist/ -> /uftakt-start.php.

// ---- Einstellungen ----
$empfaenger = 'info@uftritt.ch';
// Absender MUSS auf der eigenen Domain liegen, sonst blockt SPF/DMARC.
$absender   = 'no-reply@uftritt.ch';
$danke_url  = '/uftakt/start/?danke=1';
$fehler_url = '/uftakt/start/?fehler=1';

// ---- AJAX-Modus (fetch von der Seite): JSON statt Redirect ----
$is_ajax = ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest';
$json = static function (int $code, bool $ok, string $msg = ''): void {
    http_response_code($code);
    header('Content-Type: application/json');
    echo json_encode(['ok' => $ok, 'msg' => $msg]);
    exit;
};

// ---- Nur POST zulassen ----
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /uftakt/start/');
    exit;
}

// ---- Spam-Honeypot: für Menschen unsichtbar, Bots füllen es aus ----
if (!empty($_POST['bot-field'])) {
    if ($is_ajax) $json(200, true); // Bot still "erfolgreich", aber nichts senden
    header('Location: ' . $danke_url);
    exit;
}

// ---- Botguard: Zeit-Check + Linkfilter (still verwerfen), Turnstile (sichtbarer Fehler) ----
@include_once __DIR__ . '/inc/botguard.php';
if (function_exists('botguard_drop_reason')) {
    $bg_grund = botguard_drop_reason($_POST);
    if ($bg_grund !== null) {
        @include_once __DIR__ . '/inc/leadlog.php';
        if (function_exists('lead_log')) {
            lead_log('uftakt-start', [
                'geblockt' => $bg_grund,
                'firma'    => trim((string)($_POST['firma'] ?? '')),
                'email'    => trim((string)($_POST['email'] ?? '')),
                'mail_ok'  => false,
            ]);
        }
        if ($is_ajax) $json(200, true); // Bot still "erfolgreich", aber nichts senden
        header('Location: ' . $danke_url);
        exit;
    }
    if (!botguard_turnstile_ok(trim((string)($_POST['cf-turnstile-response'] ?? '')))) {
        if ($is_ajax) $json(403, false, 'Verifizierung fehlgeschlagen. Bitte Seite neu laden und nochmals versuchen.');
        header('Location: ' . $fehler_url);
        exit;
    }
}

// ---- Pflichtfelder ----
$firma     = trim($_POST['firma']     ?? '');
$email     = trim($_POST['email']     ?? '');
$impressum = trim($_POST['impressum'] ?? '');
$rechnungsadresse = trim($_POST['rechnungsadresse'] ?? '');

// Impressum ist nur Pflicht, wenn das Paket eine Webseite enthält.
$paket_post   = trim($_POST['paket'] ?? '');
$mit_webseite = strpos($paket_post, 'Webseite') !== false;

$email_ok = filter_var($email, FILTER_VALIDATE_EMAIL);
if ($firma === '' || !$email_ok || $rechnungsadresse === '' || ($mit_webseite && $impressum === '')) {
    if ($is_ajax) $json(400, false, 'Pflichtfelder fehlen');
    header('Location: ' . $fehler_url);
    exit;
}

// ---- Header-Injection verhindern ----
$clean = static function (string $v): string {
    return str_replace(["\r", "\n", "%0a", "%0d"], '', $v);
};
$firma_h = $clean($firma);
$email_h = $clean($email);

// ---- Alle Textfelder in Ausgabe-Reihenfolge ----
$felder = [
    'Firma'                       => $firma,
    'Kontaktperson'               => $_POST['kontaktperson'] ?? '',
    'E-Mail'                      => $email,
    'Telefon'                     => $_POST['telefon'] ?? '',
    'Rechnungsadresse'            => $_POST['rechnungsadresse'] ?? '',
    'Standort (falls anders)'     => $_POST['standort'] ?? '',
    'Paket'                       => $paket_post,
    'Abo-Laufzeit'                => $_POST['abo'] ?? '',
    'Angebot (was bietest du an)' => $_POST['angebot'] ?? '',
    'Zielgruppe (für wen)'        => $_POST['zielgruppe'] ?? '',
    'Region'                      => $_POST['region'] ?? '',
    'Wichtigste Leistungen'       => $_POST['leistungen'] ?? '',
    'Ziel der Seite'              => $_POST['ziel'] ?? '',
    'Stichworte Startseite'       => $_POST['stichworte_start'] ?? '',
    'Stichworte Über uns'         => $_POST['stichworte_ueber'] ?? '',
    'Stichworte Leistungen'       => $_POST['stichworte_leistungen'] ?? '',
    'Referenzen (nennbar)'        => $_POST['referenzen'] ?? '',
    'Öffnungszeiten'              => $_POST['oeffnungszeiten'] ?? '',
    'Domain-Status'               => $_POST['domain_status'] ?? '',
    'Domain'                      => $_POST['domain_name'] ?? '',
    'E-Mail-Wunschadresse'        => $_POST['email_wunsch'] ?? '',
    'Webseiten, die gefallen'     => $_POST['gefaellt'] ?? '',
    'Farben und Wünsche'          => $_POST['farben'] ?? '',
    'Logo vorhanden'              => $_POST['logo_vorhanden'] ?? '',
    'Firma in 3 Stichworten'      => $_POST['identitaet'] ?? '',
    'Firmenname im Logo'          => $_POST['firmenname_logo'] ?? '',
    'Logoform'                    => $_POST['logoform'] ?? '',
    'Claim zum Logo'              => $_POST['claim'] ?? '',
    'Impressum (Name, Adresse)'   => $impressum,
    'Handelsregister/UID'         => $_POST['uid_hr'] ?? '',
];

// ---- Dateien einsammeln (logo + bilder[]) ----
$max_bytes  = 15 * 1024 * 1024;  // 15 MB pro Datei
$max_total  = 40 * 1024 * 1024;  // 40 MB gesamt (Mail-Grösse)
$max_files  = 12;
$erlaubte_ext = ['jpg','jpeg','png','webp','gif','heic','heif','pdf','svg','zip','ai','eps'];

$anhaenge       = [];
$fehler_upload  = [];

$add_file = static function (array $f) use (&$anhaenge, &$fehler_upload, $max_bytes, $max_files, $erlaubte_ext): void {
    if (($f['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) return;
    if ($f['error'] !== UPLOAD_ERR_OK) { $fehler_upload[] = $f['name'] . ' (Upload-Fehler ' . $f['error'] . ')'; return; }
    if (count($anhaenge) >= $max_files) { $fehler_upload[] = $f['name'] . ' (max. ' . $max_files . ' Dateien)'; return; }
    if ($f['size'] > $max_bytes) { $fehler_upload[] = $f['name'] . ' (zu gross, max. 15 MB)'; return; }
    $ext = strtolower(pathinfo((string)$f['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, $erlaubte_ext, true)) { $fehler_upload[] = $f['name'] . ' (Dateityp nicht erlaubt)'; return; }
    $anhaenge[] = ['name' => (string)$f['name'], 'tmp' => $f['tmp_name'], 'size' => (int)$f['size']];
};

if (!empty($_FILES['logo'])) $add_file($_FILES['logo']);
if (!empty($_FILES['bilder']) && is_array($_FILES['bilder']['name'])) {
    foreach ($_FILES['bilder']['name'] as $i => $n) {
        $add_file([
            'name'     => $n,
            'tmp_name' => $_FILES['bilder']['tmp_name'][$i],
            'error'    => $_FILES['bilder']['error'][$i],
            'size'     => $_FILES['bilder']['size'][$i],
        ]);
    }
}

// ---- Dateien als base64-JSON (WAF killt echte Multipart-Uploads auf diesem
// Server, darum liest das Formular die Dateien clientseitig als base64) ----
$dateien_json = (string)($_POST['dateien_json'] ?? '');
if ($dateien_json !== '') {
    $liste = json_decode($dateien_json, true);
    if (is_array($liste)) {
        foreach ($liste as $e) {
            $name = (string)($e['name'] ?? '');
            $b64  = (string)($e['b64'] ?? '');
            if ($name === '' || $b64 === '') continue;
            $daten = base64_decode($b64, true);
            if ($daten === false) { $fehler_upload[] = $name . ' (defekte Daten)'; continue; }
            if (count($anhaenge) >= $max_files) { $fehler_upload[] = $name . ' (max. ' . $max_files . ' Dateien)'; continue; }
            if (strlen($daten) > $max_bytes) { $fehler_upload[] = $name . ' (zu gross, max. 15 MB)'; continue; }
            $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
            if (!in_array($ext, $erlaubte_ext, true)) { $fehler_upload[] = $name . ' (Dateityp nicht erlaubt)'; continue; }
            $tmp = tempnam(sys_get_temp_dir(), 'ufb64_');
            if ($tmp === false || file_put_contents($tmp, $daten) === false) { $fehler_upload[] = $name . ' (Server-Fehler)'; continue; }
            $anhaenge[] = ['name' => $name, 'tmp' => $tmp, 'size' => strlen($daten)];
        }
    }
}

$total = array_sum(array_column($anhaenge, 'size'));
if ($total > $max_total) {
    if ($is_ajax) $json(400, false, 'Dateien zusammen zu gross (max. 40 MB). Bitte per Mail an mk@uftritt.ch schicken.');
    header('Location: ' . $fehler_url);
    exit;
}

// ---- Mail-Text ----
$betreff = "Uftakt-Start: $firma_h hat Inhalte geliefert";

$body  = "Neue Lieferung über das Uftakt-Startformular.\n\n";
foreach ($felder as $label => $wert) {
    $wert = trim((string)$wert);
    if ($wert !== '') {
        $body .= strtoupper($label) . ":\n" . $wert . "\n\n";
    }
}
if ($anhaenge) {
    $body .= "ANHÄNGE (" . count($anhaenge) . "):\n";
    foreach ($anhaenge as $a) {
        $body .= "- {$a['name']} (" . round($a['size'] / 1024) . " KB)\n";
    }
    $body .= "\n";
}
if ($fehler_upload) {
    $body .= "ABGELEHNTE DATEIEN (beim Kunden nachfragen):\n- " . implode("\n- ", $fehler_upload) . "\n\n";
}
$body .= "————\n";
$body .= "Automatische Benachrichtigung vom Uftakt-Startformular · uftritt.ch/uftakt/\n";

// ---- MIME multipart bauen (nur wenn Anhänge) ----
$headers  = "From: Uftritt Website <$absender>\r\n";
$headers .= "Reply-To: $firma_h <$email_h>\r\n";
$headers .= "X-Mailer: PHP/" . phpversion() . "\r\n";

if (!$anhaenge) {
    $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
    $headers .= "Content-Transfer-Encoding: quoted-printable\r\n";
    $mailbody = quoted_printable_encode($body);
} else {
    $boundary = '=_uftakt_' . md5(uniqid((string)mt_rand(), true));
    $headers .= "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: multipart/mixed; boundary=\"$boundary\"\r\n";

    $mailbody  = "--$boundary\r\n";
    $mailbody .= "Content-Type: text/plain; charset=UTF-8\r\n";
    $mailbody .= "Content-Transfer-Encoding: quoted-printable\r\n\r\n";
    $mailbody .= quoted_printable_encode($body) . "\r\n";

    foreach ($anhaenge as $a) {
        $daten = @file_get_contents($a['tmp']);
        if ($daten === false) continue;
        $name_safe = preg_replace('/[^A-Za-z0-9._-]/', '_', $a['name']);
        $mailbody .= "--$boundary\r\n";
        $mailbody .= "Content-Type: application/octet-stream; name=\"$name_safe\"\r\n";
        $mailbody .= "Content-Transfer-Encoding: base64\r\n";
        $mailbody .= "Content-Disposition: attachment; filename=\"$name_safe\"\r\n\r\n";
        $mailbody .= chunk_split(base64_encode($daten)) . "\r\n";
    }
    $mailbody .= "--$boundary--\r\n";
}

$betreff_enc = '=?UTF-8?B?' . base64_encode($betreff) . '?=';

// ---- Senden ----
$gesendet = mail($empfaenger, $betreff_enc, $mailbody, $headers, '-f' . $absender);

// ---- Bestätigungs-Mail an den Absender ----
$kp      = trim($_POST['kontaktperson'] ?? '');
$vorname = $clean($kp !== '' ? preg_split('/\s+/', $kp)[0] : '');
$anrede  = $vorname !== '' ? "Hoi $vorname" : 'Guten Tag';

$best_betreff = 'Deine Uftakt-Angaben sind angekommen';
$best_body  = "$anrede\n\n";
$best_body .= "Besten Dank, deine Angaben sind bei uns angekommen. Wir melden uns, wenn etwas fehlt. Ansonsten hörst du von uns, sobald dein Projekt fertig ist.\n\n";
$best_body .= "Willst du uns noch etwas ergänzen? Antworte einfach auf dieses Mail.\n\n";
$best_body .= "Freundliche Grüsse\nUftritt GmbH\n";

$best_headers  = "From: Uftritt Website <$absender>\r\n";
$best_headers .= "Reply-To: Uftritt GmbH <info@uftritt.ch>\r\n";
$best_headers .= "X-Mailer: PHP/" . phpversion() . "\r\n";
$best_headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
$best_headers .= "Content-Transfer-Encoding: quoted-printable\r\n";

$best_ok = false;
if ($gesendet) {
    $best_ok = mail(
        $email_h,
        '=?UTF-8?B?' . base64_encode($best_betreff) . '?=',
        quoted_printable_encode($best_body),
        $best_headers,
        '-f' . $absender
    );
}

// ---- Lead-Log ----
@include_once __DIR__ . '/inc/leadlog.php';
if (function_exists('lead_log')) {
    lead_log('uftakt-start', [
        'firma'         => $firma_h,
        'email'         => $email_h,
        'anhaenge'      => count($anhaenge),
        'upload_fehler' => $fehler_upload,
        'mail_ok'       => (bool) $gesendet,
        'bestaetigung'  => (bool) $best_ok,
    ]);
}

if ($is_ajax) $json($gesendet ? 200 : 500, (bool) $gesendet);
header('Location: ' . ($gesendet ? $danke_url : $fehler_url));
exit;
