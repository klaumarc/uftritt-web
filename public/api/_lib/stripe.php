<?php
// Stripe-API-Client (Checkout Sessions) via cURL, keine SDK-Abhängigkeit.
// Keys kommen aus uftritt-check-config.php (ausserhalb Docroot, NICHT im Repo):
// stripe_secret_key_test (Staging) und stripe_secret_key_live (Prod).

declare(strict_types=1);

// Report-Preis: CHF 9.00 einmalig (inkl. MwSt.)
define('UC_PRICE_CHF_RAPPEN', 900);

function uc_stripe_key(): string
{
    return trim((string)($GLOBALS['UC']['env'] === 'staging'
        ? uc_config('stripe_secret_key_test')
        : uc_config('stripe_secret_key_live')));
}

// Roher API-Call. Rückgabe: ['status' => int, 'json' => array|null]
function uc_stripe_request(string $method, string $path, ?array $params = null): array
{
    $key = uc_stripe_key();
    if ($key === '') {
        return ['status' => 0, 'json' => null];
    }
    $ch = curl_init('https://api.stripe.com' . $path);
    $opts = [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 15,
        CURLOPT_CONNECTTIMEOUT => 6,
        CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $key],
    ];
    if (strtoupper($method) === 'POST') {
        // Stripe erwartet application/x-www-form-urlencoded (verschachtelt via http_build_query)
        $opts[CURLOPT_POST] = true;
        $opts[CURLOPT_POSTFIELDS] = http_build_query($params ?? []);
    }
    curl_setopt_array($ch, $opts);
    $resp = curl_exec($ch);
    $status = (int)curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    curl_close($ch);
    $json = is_string($resp) ? json_decode($resp, true) : null;
    return ['status' => $status, 'json' => is_array($json) ? $json : null];
}

// Checkout-Session erstellen. Rückgabe: ['id' => …, 'url' => …] oder null.
// TWINT wird versucht; falls das Konto es nicht aktiviert hat, Fallback auf Karte.
function uc_stripe_checkout_create(string $domain, string $roastId, string $url): ?array
{
    $base = $GLOBALS['UC']['base_url'];
    $params = [
        'mode' => 'payment',
        'line_items[0][price_data][currency]' => 'chf',
        'line_items[0][price_data][unit_amount]' => UC_PRICE_CHF_RAPPEN,
        'line_items[0][price_data][product_data][name]' => 'Website-Check Report für ' . $domain,
        'line_items[0][quantity]' => 1,
        'success_url' => $base . '/api/report-unlock.php?session_id={CHECKOUT_SESSION_ID}',
        'cancel_url' => $base . '/website-check/',
        'metadata[roast_id]' => $roastId,
        'metadata[url]' => $url,
        'invoice_creation[enabled]' => 'true',
    ];
    foreach ([['card', 'twint'], ['card']] as $i => $methods) {
        foreach ($methods as $m => $type) {
            $params['payment_method_types[' . $m . ']'] = $type;
        }
        $r = uc_stripe_request('POST', '/v1/checkout/sessions', $params);
        $j = $r['json'];
        if ($r['status'] === 200 && !empty($j['id']) && !empty($j['url'])) {
            return ['id' => (string)$j['id'], 'url' => (string)$j['url']];
        }
        // Erster Durchlauf mit TWINT gescheitert -> ohne TWINT erneut versuchen
        if ($i > 0 || $r['status'] === 0) {
            break;
        }
    }
    return null;
}

// Session abrufen (für die Zahlungs-Verifikation nach dem Redirect)
function uc_stripe_session_get(string $sessionId): ?array
{
    if (!preg_match('/^cs_(test|live)_[A-Za-z0-9]+$/', $sessionId)) {
        return null;
    }
    // Test-Sessions gehören auf Staging, Live auf Prod – Cross-Zugriff abwehren
    $isTest = str_starts_with($sessionId, 'cs_test_');
    if ($isTest !== ($GLOBALS['UC']['env'] === 'staging')) {
        return null;
    }
    $r = uc_stripe_request('GET', '/v1/checkout/sessions/' . rawurlencode($sessionId));
    return ($r['status'] === 200 && is_array($r['json'])) ? $r['json'] : null;
}
