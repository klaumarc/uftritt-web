<?php
// POST /api/roast.php – Free-Check: URL prüfen, 5 Kategorie-Scores + Teaser.
// Felder: url (string), bot-field (Honeypot, muss leer sein)

declare(strict_types=1);

require __DIR__ . '/_lib/bootstrap.php';
require __DIR__ . '/_lib/store.php';
require __DIR__ . '/_lib/fetch.php';
require __DIR__ . '/_lib/checks.php';
require __DIR__ . '/_lib/score.php';
require __DIR__ . '/_lib/llm.php';

uc_cleanup_maybe();

try {
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
        uc_error('invalid_url', 'Ungültige Anfrage.', 400);
    }

    // Honeypot: Bots füllen das unsichtbare Feld aus
    if (uc_post('bot-field') !== '') {
        uc_error('bot_detected', 'Die Anfrage konnte nicht verarbeitet werden. Bitte versuche es erneut.', 403);
    }

    $url = uc_normalize_url(uc_post('url'));
    if ($url === null) {
        uc_error('invalid_url', 'Das sieht nicht nach einer gültigen Website-Adresse aus. Bitte prüfe die Eingabe (z. B. «example.ch»).', 400);
    }

    // Rate-Limit zuerst: 2 Gratis-Checks pro IP/Tag – auch DNS-Auflösungen kosten Ressourcen
    $block = uc_ratelimit_hit('roast_ip', uc_ip_hash(), 2);
    if ($block !== null) {
        uc_error('rate_limited', 'Du hast heute schon 2 Gratis-Checks gemacht – danke für dein Interesse! Morgen geht es weiter, oder du meldest dich direkt bei uns.', 429, ['retry_after_h' => $block['retry_after_h']]);
    }

    // SSRF-Vorprüfung (DNS + öffentliche IP), bevor Cache oder Analyse greifen
    $host = strtolower(trim((string)parse_url($url, PHP_URL_HOST), '[]'));
    $res = uc_resolve_host($host);
    if ($res === 'ssrf_blocked') {
        uc_error('ssrf_blocked', 'Diese Adresse dürfen wir nicht prüfen (internes oder reserviertes Netz).', 403);
    }
    if ($res === 'unreachable') {
        uc_error('unreachable', 'Unter dieser Adresse ist keine Website erreichbar. Vielleicht ein Tippfehler?', 502);
    }

    // Cache: 24 h pro URL (Roast-ID wird trotzdem frisch gemünzt, damit sie 1 h gilt)
    $cached = false;
    $cache = uc_cache_get($url);
    if ($cache !== null && !empty($cache['analysis'])) {
        $analysis = $cache['analysis'];
        $cached = true;
    } else {
        // Circuit-Breaker: global max. 100 frische Analysen/Tag
        $block = uc_ratelimit_hit('global', 'analysen', 100);
        if ($block !== null) {
            uc_error('global_cap', 'Heute sind ungewöhnlich viele Checks gelaufen – unser Tageslimit ist erreicht. Morgen sind wir wieder für dich da.', 429, ['retry_after_h' => $block['retry_after_h']]);
        }
        $analysis = uc_analyze($url);
        if (isset($analysis['error'])) {
            $code = (string)$analysis['error'];
            $msg = (string)($analysis['message'] ?? 'Die Website konnte nicht geprüft werden.');
            uc_error($code, $msg, $code === 'ssrf_blocked' ? 403 : 502);
        }
        uc_cache_set($url, $analysis);
    }

    $roastId = uc_roast_create($url, $analysis);

    // Antwort exakt nach API-Vertrag, plus Befundliste pro Kategorie
    // (Gratis-Version zeigt für Technik/SEO bis zu 5 Befunde offen)
    $labels = uc_category_labels();
    $categories = [];
    foreach (uc_category_order() as $cat) {
        $fList = [];
        foreach ((array)($analysis['findings'][$cat] ?? []) as $f) {
            $fList[] = [
                'status' => $f['status'],
                'title' => $f['title'],
                'detail' => $f['detail'],
            ];
        }
        $categories[] = [
            'key' => $cat,
            'label' => $labels[$cat],
            'score' => $analysis['scores'][$cat],
            'top_finding' => uc_top_finding($analysis['findings'][$cat] ?? []),
            'findings' => $fList,
        ];
    }

    uc_json([
        'ok' => true,
        'cached' => $cached,
        'roast_id' => $roastId,
        'url' => $analysis['url'],
        'domain' => $analysis['domain'],
        'overall' => $analysis['overall'],
        'categories' => $categories,
        'teaser' => $analysis['llm']['teaser'] ?? '',
        'llm' => (bool)($analysis['llm_used'] ?? false),
        'checks_left' => uc_ratelimit_remaining('roast_ip', uc_ip_hash(), 2),
        'duration_ms' => (int)round((microtime(true) - $GLOBALS['UC']['started']) * 1000),
    ]);
} catch (Throwable $e) {
    error_log('website-check roast: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
    uc_error('internal', 'Upsi – da ist auf unserer Seite etwas schiefgelaufen. Bitte versuche es gleich nochmals.', 502);
}
