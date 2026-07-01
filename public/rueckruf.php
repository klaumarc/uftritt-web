<?php
// Handler für das Rückruf-Popup (RecallPopup.astro). Nimmt Telefon ODER E-Mail
// entgegen und schickt eine kurze Benachrichtigung an info@uftritt.ch.
header("Content-Type: text/plain; charset=UTF-8");

$empfaenger = "info@uftritt.ch";
$absender   = "no-reply@uftritt.ch";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);
    echo "method";
    exit;
}

// Spam-Honeypot
if (!empty($_POST["website"])) {
    echo "ok";
    exit;
}

$kontakt = trim($_POST["kontakt"] ?? "");
if ($kontakt === "") {
    http_response_code(400);
    echo "leer";
    exit;
}

// Header-Injection verhindern
$clean = static function (string $v): string {
    return str_replace(["\r", "\n", "%0a", "%0d"], "", $v);
};
$k = $clean($kontakt);
$istEmail = filter_var($k, FILTER_VALIDATE_EMAIL);

$betreff = "=?UTF-8?B?" . base64_encode("Rückrufwunsch über uftritt.ch") . "?=";

$body  = "Jemand möchte kontaktiert werden (Popup auf uftritt.ch):\n\n";
$body .= "Kontakt: $k\n";
if (!empty($_POST["seite"])) {
    $body .= "Seite:   " . $clean($_POST["seite"]) . "\n";
}

$headers  = "From: Uftritt Website <$absender>\r\n";
if ($istEmail) {
    $headers .= "Reply-To: $k\r\n";
}
$headers .= "Content-Type: text/plain; charset=UTF-8\r\n";

$ok = mail($empfaenger, $betreff, $body, $headers, "-f" . $absender);
if ($ok) {
    echo "ok";
} else {
    http_response_code(500);
    echo "fehler";
}
