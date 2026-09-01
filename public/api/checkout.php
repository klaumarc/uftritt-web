<?php
// POST /api/checkout.php – Erstellt eine Stripe-Checkout-Session für den vollen
// Report (CHF 9) und liefert die URL, zu der das Frontend weiterleitet.
// Felder: roast_id, url, bot-field (Honeypot, muss leer sein)

declare(strict_types=1);

require __DIR__ . '/_lib/bootstrap.php';
require __DIR__ . '/_lib/store.php';
require __DIR__ . '/_lib/fetch.php';
require __DIR__ . '/_lib/stripe.php';

uc_cleanup_maybe();

try {
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
        uc_error('invalid_url', 'Ungültige Anfrage.', 400);
    }

    if (uc_post('bot-field') !== '') {
        uc_error('bot_detected', 'Die Anfrage konnte nicht verarbeitet werden. Bitte versuche es erneut.', 403);
    }

    // Missbrauchsbremse: max. 10 Checkout-Erstellungen pro IP/Tag
    $block = uc_ratelimit_hit('report_ip', uc_ip_hash(), 10);
    if ($block !== null) {
        uc_error('rate_limited', 'Von deinem Anschluss wurden heute schon viele Reports angefordert. Morgen geht es weiter – oder melde dich direkt bei uns.', 429, ['retry_after_h' => $block['retry_after_h']]);
    }

    // Domain für den Produktnamen: aus dem Roast, sonst aus der URL
    $roastId = uc_post('roast_id');
    $url = uc_post('url');
    $domain = '';
    if ($roastId !== '') {
        $roast = uc_roast_get($roastId);
        if (is_array($roast) && !empty($roast['analysis']['domain'])) {
            $domain = (string)$roast['analysis']['domain'];
            // Die Roast-URL hat Vorrang vor der mitgeschickten (Vertrauensgrenze)
            $url = (string)($roast['url'] ?? '') !== '' ? (string)$roast['url'] : $url;
        }
    }
    if ($domain === '') {
        $norm = uc_normalize_url($url);
        if ($norm === null) {
            uc_error('roast_expired', 'Dein Check ist abgelaufen – bitte starte ihn kurz neu, dann geht es direkt weiter.', 400);
        }
        $host = strtolower(trim((string)parse_url($norm, PHP_URL_HOST), '[]'));
        $res = uc_resolve_host($host);
        if (is_string($res)) {
            uc_error($res === 'ssrf_blocked' ? 'ssrf_blocked' : 'unreachable', 'Diese Adresse können wir nicht prüfen.', $res === 'ssrf_blocked' ? 403 : 502);
        }
        $url = $norm;
        $domain = preg_replace('/^www\./', '', $host);
    }

    if (uc_stripe_key() === '') {
        // Keys noch nicht hinterlegt (z. B. vor dem Livegang): ehrlich bleiben
        uc_error('checkout_unavailable', 'Die Zahlung wird gerade eingerichtet. Versuche es bitte in Kürze erneut – oder schreib uns an info@uftritt.ch.', 503);
    }

    $session = uc_stripe_checkout_create((string)$domain, $roastId, (string)$url);
    if ($session === null) {
        error_log('website-check checkout: Stripe-Session fehlgeschlagen (domain=' . $domain . ')');
        uc_error('internal', 'Die Zahlung konnte gerade nicht vorbereitet werden. Bitte versuche es gleich noch einmal.', 502);
    }

    uc_json(['ok' => true, 'checkout_url' => $session['url']]);
} catch (Throwable $e) {
    error_log('website-check checkout: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
    uc_error('internal', 'Upsi – da ist auf unserer Seite etwas schiefgelaufen. Bitte versuche es gleich nochmals.', 502);
}
