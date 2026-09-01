<?php
// Mails exakt nach dem kontakt.php-Muster: mail(), From no-reply@uftritt.ch,
// -f-Envelope, Header-Injection-Guard, UTF-8-Base64-Betreff, quoted-printable Body.
// HINWEIS (Lesson 2026-08-14): Die Server-IP muss per ip4: im SPF von uftritt.ch
// stehen, sonst junken/verwerfen Empfänger diese Mails (DMARC aspf=s).

declare(strict_types=1);

define('UC_MAIL_FROM', 'no-reply@uftritt.ch');
define('UC_MAIL_LEAD_TO', 'info@uftritt.ch');

// Header-Injection verhindern (Zeilenumbrüche aus Kopfzeilen entfernen)
function uc_header_clean(string $v): string
{
    return str_replace(["\r", "\n", "%0a", "%0d", "%0A", "%0D"], '', $v);
}

// Generischer Versand: Text oder multipart/alternative (Text + HTML)
function uc_mail_send(string $to, string $subject, string $textBody, ?string $htmlBody = null, ?string $replyTo = null): bool
{
    $to = uc_header_clean($to);
    $subjectEnc = '=?UTF-8?B?' . base64_encode(uc_header_clean($subject)) . '?=';

    $headers = 'From: Uftritt Website-Check <' . UC_MAIL_FROM . ">\r\n";
    if ($replyTo !== null) {
        $headers .= 'Reply-To: ' . uc_header_clean($replyTo) . "\r\n";
    }
    $headers .= "MIME-Version: 1.0\r\n";

    if ($htmlBody === null) {
        $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
        $headers .= "Content-Transfer-Encoding: quoted-printable\r\n";
        $body = quoted_printable_encode($textBody);
    } else {
        $boundary = '=_uc_' . bin2hex(random_bytes(12));
        $headers .= 'Content-Type: multipart/alternative; boundary="' . $boundary . '"' . "\r\n";
        $body = '--' . $boundary . "\r\n"
            . "Content-Type: text/plain; charset=UTF-8\r\n"
            . "Content-Transfer-Encoding: quoted-printable\r\n\r\n"
            . quoted_printable_encode($textBody) . "\r\n"
            . '--' . $boundary . "\r\n"
            . "Content-Type: text/html; charset=UTF-8\r\n"
            . "Content-Transfer-Encoding: quoted-printable\r\n\r\n"
            . quoted_printable_encode($htmlBody) . "\r\n"
            . '--' . $boundary . '--';
    }
    return mail($to, $subjectEnc, $body, $headers, '-f' . UC_MAIL_FROM);
}

// Zahl mit Komma formatieren (7,1)
function uc_fmt(float $n): string
{
    return number_format($n, 1, ',', '');
}

// ---- Report-Mail an den Lead (Text + HTML, Reply-To info@uftritt.ch) ----
function uc_mail_report(string $email, array $report, string $reportUrlAbs): bool
{
    $domain = (string)$report['domain'];
    $grade = (string)$report['overall']['grade'];
    $score = uc_fmt((float)$report['overall']['score']);
    $subject = 'Dein Website-Check: ' . $domain . ' (' . $grade . ', ' . $score . '/10)';

    $labels = uc_category_labels();
    $scoreLine = [];
    foreach (uc_category_order() as $cat) {
        $scoreLine[] = $labels[$cat] . ' ' . uc_fmt((float)$report['scores'][$cat]);
    }
    $scoreText = implode(' · ', $scoreLine);

    $text = "Hallo\n\n"
        . "dein Website-Check für {$domain} ist fertig: Note {$grade} ({$score}/10).\n\n"
        . "Kategorien: {$scoreText}\n\n"
        . "Den vollständigen Report mit allen Befunden und dem Aktionsplan findest du hier:\n{$reportUrlAbs}\n\n"
        . "Der Link ist 90 Tage gültig. Fragen zum Ergebnis? Antworte einfach auf dieses E-Mail – wir besprechen es gerne in 20 Minuten, gratis und unverbindlich.\n\n"
        . "Freundliche Grüsse\n"
        . "dein Uftritt-Team\n\n"
        . "————\n"
        . "Uftritt GmbH · Frauwisstrasse 9 · 8493 Saland · info@uftritt.ch · www.uftritt.ch\n"
        . "Diese Analyse wurde teilweise KI-erstellt (redaktionell geprüfte Methodik).";

    $e = static function (string $s): string {
        return htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    };
    $html = '<div style="font-family:Arial,Helvetica,sans-serif;color:#014080;max-width:560px;margin:0 auto">'
        . '<div style="background:#ffd23c;padding:14px 18px;font-weight:bold;font-size:15px">Website-Check · uftritt.ch</div>'
        . '<div style="padding:22px 18px;background:#ffffff;border:1px solid #e6ebf2">'
        . '<p style="margin:0 0 12px">Hallo</p>'
        . '<p style="margin:0 0 12px">dein Website-Check für <strong>' . $e($domain) . '</strong> ist fertig:</p>'
        . '<p style="margin:0 0 16px;font-size:22px;font-weight:bold">Note ' . $e($grade) . ' <span style="font-weight:normal;font-size:15px">(' . $e($score) . '/10)</span></p>'
        . '<p style="margin:0 0 16px;font-size:13px;color:#5b7290">' . $e($scoreText) . '</p>'
        . '<p style="margin:0 0 18px"><a href="' . $e($reportUrlAbs) . '" style="background:#da2c67;color:#ffffff;padding:11px 20px;text-decoration:none;font-weight:bold;display:inline-block">Report ansehen</a></p>'
        . '<p style="margin:0;font-size:13px;color:#5b7290">Der Link ist 90 Tage gültig. Fragen zum Ergebnis? Antworte einfach auf dieses E-Mail – wir besprechen es gerne in 20 Minuten, gratis und unverbindlich.</p>'
        . '</div>'
        . '<p style="font-size:12px;color:#5b7290;padding:12px 4px">Uftritt GmbH · Frauwisstrasse 9 · 8493 Saland · info@uftritt.ch · www.uftritt.ch · Diese Analyse wurde teilweise KI-erstellt (redaktionell geprüfte Methodik).</p>'
        . '</div>';

    return uc_mail_send($email, $subject, $text, $html, 'info@uftritt.ch');
}

// ---- Verkaufs-Mail intern an info@uftritt.ch (Text, Reply-To = Käufer) ----
function uc_mail_sale(array $lead, string $reportUrlAbs): bool
{
    $domain = (string)parse_url((string)$lead['url'], PHP_URL_HOST);
    $betrag = number_format(((int)($lead['amount_rappen'] ?? 0)) / 100, 2, ',', '');
    $subject = 'Neuer Report-Verkauf (CHF ' . $betrag . '): ' . $domain;

    $labels = uc_category_labels();
    $scoreLine = [];
    foreach (uc_category_order() as $cat) {
        $scoreLine[] = $labels[$cat] . ' ' . uc_fmt((float)$lead['scores'][$cat]);
    }

    $body = "Neuer Verkauf über den Website-Check 🎉\n\n"
        . "────────────────────────────────────────\n"
        . "  URL:      " . $lead['url'] . "\n"
        . "  E-Mail:   " . ($lead['email'] !== '' ? $lead['email'] : '–') . "\n"
        . "  Betrag:   CHF " . $betrag . " (" . (string)($lead['currency'] ?? 'chf') . ")\n"
        . "  Gesamt:   " . $lead['overall']['grade'] . ' (' . uc_fmt((float)$lead['overall']['score']) . "/10)\n"
        . "  Details:  " . implode(' · ', $scoreLine) . "\n"
        . "  Report:   {$reportUrlAbs}\n"
        . "  Stripe:   " . (string)($lead['stripe_session'] ?? '–') . "\n"
        . "────────────────────────────────────────\n\n"
        . "Env: " . $lead['env'] . "\n\n"
        . "Tipp: Einfach direkt auf diese E-Mail antworten, um die Kundin oder den Kunden zu erreichen.\n"
        . "Automatische Benachrichtigung vom Website-Check · uftritt.ch\n";

    $replyTo = ($lead['email'] ?? '') !== '' ? (string)$lead['email'] : null;
    return uc_mail_send(UC_MAIL_LEAD_TO, $subject, $body, null, $replyTo);
}
