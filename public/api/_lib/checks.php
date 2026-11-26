<?php
// Deterministische Checks: Crawl-Daten -> Findings (kein LLM, keine Zufälle).
// Finding-Modell: {category, id, status: ok|warn|bad|info, title, detail, impact, effort}

declare(strict_types=1);

require_once __DIR__ . '/textqual.php';

// ---- Finding- und HTML-Helfer ----
function uc_f(string $category, string $id, string $status, string $title, string $detail, string $impact, string $effort): array
{
    return [
        'category' => $category,
        'id' => $id,
        'status' => $status,
        'title' => $title,
        'detail' => $detail,
        'impact' => $impact,
        'effort' => $effort,
    ];
}

// Ersten Wert eines Response-Headers holen
function uc_h(array $headers, string $name): ?string
{
    return $headers[strtolower($name)][0] ?? null;
}

// HTML parsen (fehlertolerant), Rückgabe ['dom' => ?DOMDocument, 'xpath' => ?DOMXPath]
function uc_dom(string $html): array
{
    if (trim($html) === '') {
        return ['dom' => null, 'xpath' => null];
    }
    $prev = libxml_use_internal_errors(true);
    $dom = new DOMDocument();
    $ok = $dom->loadHTML('<?xml encoding="utf-8" ?>' . $html, LIBXML_NOERROR | LIBXML_NOWARNING);
    libxml_clear_errors();
    libxml_use_internal_errors($prev);
    if (!$ok) {
        return ['dom' => null, 'xpath' => null];
    }
    return ['dom' => $dom, 'xpath' => new DOMXPath($dom)];
}

// XPath-Query als Array (nie false)
function uc_nodes(DOMXPath $xp, string $query): array
{
    $r = $xp->query($query);
    return $r === false ? [] : iterator_to_array($r, false);
}

function uc_count(DOMXPath $xp, string $query): int
{
    $r = $xp->query($query);
    return $r === false ? 0 : $r->length;
}

// Sichtbarer Text (ohne script/style/noscript)
function uc_visible_text(DOMXPath $xp): string
{
    $nodes = uc_nodes($xp, '//body//text()[not(ancestor::script) and not(ancestor::style) and not(ancestor::noscript)]');
    if ($nodes === []) {
        $nodes = uc_nodes($xp, '//text()[not(ancestor::script) and not(ancestor::style)]');
    }
    $parts = [];
    foreach ($nodes as $t) {
        $v = trim((string)$t->nodeValue);
        if ($v !== '') {
            $parts[] = $v;
        }
    }
    return implode(' ', $parts);
}

// =========================================================================
// Crawl: Home + bis zu 2 Kontakt-/Impressum-Unterseiten + robots/sitemap
// =========================================================================
function uc_crawl(string $url): array
{
    $home = uc_fetch_page($url, 8);
    if (empty($home['ok'])) {
        $code = (string)($home['error'] ?? 'unreachable');
        $msg = $code === 'ssrf_blocked'
            ? 'Diese Adresse dürfen wir nicht prüfen (internes oder reserviertes Netz).'
            : 'Unter dieser Adresse ist keine Website erreichbar (Timeout oder Verbindungsfehler).';
        return ['error' => $code, 'message' => $msg];
    }
    if ((int)($home['status'] ?? 0) >= 400) {
        return [
            'error' => 'fetch_failed',
            'message' => 'Die Website hat mit HTTP-Status ' . (int)$home['status'] . ' geantwortet – so können wir sie nicht prüfen.',
        ];
    }

    $doc = uc_dom((string)$home['body']);
    $xp = $doc['xpath'];
    $base = (string)$home['url']; // finale URL nach Redirects
    $baseHost = strtolower((string)parse_url($base, PHP_URL_HOST));
    $origin = (string)parse_url($base, PHP_URL_SCHEME) . '://' . $baseHost;

    // Kontakt-/Impressum-Links (intern, max. 2)
    $subUrls = [];
    if ($xp instanceof DOMXPath) {
        foreach (uc_nodes($xp, '//a[@href]') as $a) {
            $href = trim((string)$a->getAttribute('href'));
            if ($href === '' || !preg_match('/kontakt|contact|impressum|imprint/i', $href)) {
                continue;
            }
            $abs = uc_resolve_relative($base, $href);
            if ($abs === null || strtolower((string)parse_url($abs, PHP_URL_HOST)) !== $baseHost) {
                continue;
            }
            $subUrls[$abs] = true;
            if (count($subUrls) >= 2) {
                break;
            }
        }
    }

    // Parallel-Batch: Unterseiten, robots, sitemap, Brotli-Probe (HEAD), HTTP-Probe
    $batch = [
        'robots' => $origin . '/robots.txt',
        'sitemap' => $origin . '/sitemap.xml',
        'llms' => $origin . '/llms.txt',
        'brprobe' => $base,
        'http' => 'http://' . $baseHost . '/',
    ];
    $i = 0;
    foreach (array_keys($subUrls) as $su) {
        $batch['sub' . $i++] = $su;
    }
    $results = uc_fetch_multi($batch, 8, ['head_keys' => ['brprobe']]);

    // 3xx-Antworten validiert nachführen (multi folgt nicht selbst)
    foreach (['robots', 'sitemap', 'llms', 'http', 'sub0', 'sub1'] as $k) {
        if (!isset($results[$k]) || empty($results[$k]['ok']) || uc_deadline_close(10)) {
            continue;
        }
        $r = $results[$k];
        $location = $r['headers']['location'][0] ?? '';
        if (in_array((int)($r['status'] ?? 0), [301, 302, 303, 307, 308], true) && $location !== '') {
            $next = uc_resolve_relative($batch[$k], $location);
            if ($next !== null) {
                $followed = uc_fetch_page($next, 6);
                if (!empty($followed['ok'])) {
                    $results[$k] = $followed;
                }
            }
        }
    }

    // HTTP-Probe: Kette normalisieren (für den http→https-Check)
    $http = $results['http'] ?? null;
    if (is_array($http) && !empty($http['ok'])) {
        $http['chain'] = $http['chain'] ?? [['url' => $batch['http'], 'status' => (int)$http['status']]];
        $http['url'] = $http['url'] ?? $batch['http'];
    }

    // Unterseiten mit DOM
    $subs = [];
    $subDocs = [];
    foreach (['sub0', 'sub1'] as $k) {
        if (!empty($results[$k]['ok']) && (int)($results[$k]['status'] ?? 0) < 400) {
            $subs[$batch[$k]] = $results[$k];
            $subDocs[$batch[$k]] = uc_dom((string)($results[$k]['body'] ?? ''));
        }
    }

    // Sitemap ggf. aus robots.txt nachholen
    $robots = $results['robots'] ?? null;
    $sitemap = $results['sitemap'] ?? null;
    $sitemapOk = is_array($sitemap) && !empty($sitemap['ok']) && (int)($sitemap['status'] ?? 0) === 200;
    if (!$sitemapOk && is_array($robots) && !empty($robots['ok'])
        && (int)($robots['status'] ?? 0) === 200 && !uc_deadline_close(10)) {
        if (preg_match_all('/^sitemap:\s*(\S+)/im', (string)$robots['body'], $m) && !empty($m[1][0])) {
            $alt = uc_normalize_url($m[1][0]);
            if ($alt !== null && $alt !== $batch['sitemap']) {
                $r = uc_fetch_page($alt, 6);
                if (!empty($r['ok'])) {
                    $sitemap = $r;
                }
            }
        }
    }

    // Defekte interne Links: Stichprobe max. 5 (HEAD)
    $broken = ['checked' => 0, 'broken' => [], 'skipped' => true];
    if ($xp instanceof DOMXPath && !uc_deadline_close(12)) {
        $links = [];
        foreach (uc_nodes($xp, '//a[@href]') as $a) {
            $href = trim((string)$a->getAttribute('href'));
            if ($href === '' || str_starts_with($href, '#') || preg_match('/^(mailto|tel|javascript):/i', $href)) {
                continue;
            }
            $abs = uc_resolve_relative($base, $href);
            if ($abs === null || strtolower((string)parse_url($abs, PHP_URL_HOST)) !== $baseHost) {
                continue;
            }
            $links[$abs] = true;
            if (count($links) >= 12) {
                break; // Pool begrenzen, Stichprobe = erste 5 (deterministisch)
            }
        }
        $sample = array_slice(array_keys($links), 0, 5);
        if ($sample !== []) {
            // URL als Schlüssel, damit die Ergebnisse der URL zugeordnet bleiben
            $res2 = uc_fetch_multi(array_combine($sample, $sample), 6, ['head_all' => true]);
            $broken['skipped'] = false;
            $broken['checked'] = count($sample);
            foreach ($res2 as $u => $r) {
                if (empty($r['ok'])) {
                    $broken['broken'][] = $u;
                    continue;
                }
                $s = (int)($r['status'] ?? 0);
                if (in_array($s, [404, 410], true) || $s >= 500) {
                    $broken['broken'][] = $u;
                }
                // 200/3xx/405 gelten als erreichbar
            }
        }
    }

    $text = $xp instanceof DOMXPath ? uc_visible_text($xp) : '';

    return [
        'home' => $home,
        'home_doc' => $doc,
        'subpages' => $subs,
        'sub_docs' => $subDocs,
        'robots' => is_array($robots) ? $robots : null,
        'sitemap' => is_array($sitemap) ? $sitemap : null,
        'llms' => isset($results['llms']) && is_array($results['llms']) ? $results['llms'] : null,
        'http_probe' => is_array($http) ? $http : null,
        'br_probe' => $results['brprobe'] ?? null,
        'broken_links' => $broken,
        'text_excerpt' => mb_substr((string)preg_replace('/\s+/u', ' ', $text), 0, 1500),
        'pages_crawled' => 1 + count($subs),
    ];
}

// =========================================================================
// Kategorie: technik
// =========================================================================
function uc_checks_technik(array $crawl): array
{
    $home = $crawl['home'];
    $h = (array)($home['headers'] ?? []);
    $isHttps = str_starts_with((string)$home['url'], 'https://');
    $out = [];

    $out[] = $isHttps
        ? uc_f('technik', 'https', 'ok', 'HTTPS aktiv', 'Die Seite wird verschlüsselt übertragen. So sind die Daten deiner Besucher geschützt, und Browser zeigen keine Warnung.', 'high', 'low')
        : uc_f('technik', 'https', 'bad', 'Kein HTTPS', 'Die Seite wird unverschlüsselt übertragen. Browser warnen Besucher dann aktiv vor der Seite, und Google stuft sie tiefer ein.', 'high', 'low');

    if ($isHttps) {
        $hsts = uc_h($h, 'strict-transport-security');
        $out[] = $hsts !== null
            ? uc_f('technik', 'hsts', 'ok', 'HTTPS-Zwang aktiv', 'Ein Schutz-Header (HSTS) sorgt dafür, dass Besucher immer automatisch auf der sicheren HTTPS-Version landen.', 'medium', 'low')
            : uc_f('technik', 'hsts', 'warn', 'Kein HTTPS-Zwang', 'Beim ersten Aufruf kann die Seite noch unverschlüsselt geladen werden. Ein fehlender Schutz-Header (HSTS) sorgt dafür, dass Besucher nicht automatisch auf der sicheren Version landen.', 'medium', 'low');
    }

    $probe = $crawl['http_probe'] ?? null;
    if (!is_array($probe) || empty($probe['ok'])) {
        $out[] = uc_f('technik', 'redirect_http', 'info', 'Umleitung nicht prüfbar', 'Die unverschlüsselte Adresse (http://) hat auf unsere Prüfung nicht geantwortet. Ob sie korrekt auf die sichere Version weiterleitet, konnten wir daher nicht feststellen.', 'medium', 'low');
    } else {
        $final = (string)($probe['url'] ?? '');
        $hops = max(0, count((array)$probe['chain']) - 1);
        $out[] = str_starts_with($final, 'https://')
            ? uc_f('technik', 'redirect_http', 'ok', 'HTTP leitet auf HTTPS um', 'Die unverschlüsselte Adresse (http://) leitet korrekt auf die sichere HTTPS-Version weiter' . ($hops > 0 ? ' (über ' . $hops . ' Umleitung' . ($hops > 1 ? 'en' : '') . ')' : '') . '.', 'medium', 'low')
            : uc_f('technik', 'redirect_http', 'warn', 'Keine Umleitung auf HTTPS', 'Wer die Adresse mit http:// aufruft, bleibt auf der unverschlüsselten Version. Leite diese Aufrufe immer automatisch auf die sichere HTTPS-Version um.', 'medium', 'low');
    }

    // Kompression: Brotli-Probe (HEAD) hat Vorrang, sonst Content-Encoding des GET
    $enc = strtolower((string)uc_h($h, 'content-encoding'));
    $br = $crawl['br_probe'] ?? null;
    $brEnc = (is_array($br) && !empty($br['ok'])) ? strtolower((string)uc_h((array)$br['headers'], 'content-encoding')) : '';
    $best = $brEnc !== '' ? $brEnc : $enc;
    if (str_contains($best, 'br')) {
        $out[] = uc_f('technik', 'compression', 'ok', 'Kompression aktiv', 'Die Seite wird mit Brotli komprimiert übertragen, einem besonders modernen Verfahren. Das spart Ladezeit, gerade auf dem Handy.', 'medium', 'low');
    } elseif (str_contains($best, 'gzip')) {
        $out[] = uc_f('technik', 'compression', 'ok', 'Kompression aktiv', 'Die Seite wird komprimiert übertragen. Das spart Ladezeit, gerade auf dem Handy.', 'medium', 'low');
    } else {
        $out[] = uc_f('technik', 'compression', 'warn', 'Keine Kompression', 'Die Seite wird unkomprimiert übertragen. Das bedeutet grössere Datenmengen und längere Ladezeiten, vor allem auf dem Handy.', 'medium', 'low');
    }

    $csp = uc_h($h, 'content-security-policy');
    $out[] = $csp !== null
        ? uc_f('technik', 'csp', 'ok', 'Sicherheitsrichtlinie aktiv', 'Die Sicherheitsrichtlinie (CSP) legt fest, welche Inhalte die Seite laden und ausführen darf. So wird es schwerer, schädliche Skripte einzuschleusen.', 'medium', 'medium')
        : uc_f('technik', 'csp', 'warn', 'Keine Sicherheitsrichtlinie', 'Der Seite fehlt eine Sicherheitsrichtlinie (CSP). Sie legt fest, welche Inhalte geladen und ausgeführt werden dürfen. Ohne sie ist die Seite anfälliger für eingeschleuste Schad-Skripte (XSS).', 'medium', 'medium');

    $frameOk = uc_h($h, 'x-frame-options') !== null || ($csp !== null && stripos($csp, 'frame-ancestors') !== false);
    $out[] = $frameOk
        ? uc_f('technik', 'xfo', 'ok', 'Schutz vor Einbettung aktiv', 'Die Seite kann nicht unbemerkt in fremde Websites eingebettet werden. So wird verhindert, dass Betrüger sie in einer getarnten Fassade missbrauchen (Clickjacking).', 'low', 'low')
        : uc_f('technik', 'xfo', 'warn', 'Kein Einbettungs-Schutz', 'Die Seite lässt sich in fremde Websites einbetten. Betrüger können sie so in einer getarnten Fassade missbrauchen (Clickjacking). Ein Schutz-Header (X-Frame-Options) fehlt.', 'low', 'low');

    $out[] = str_contains(strtolower((string)uc_h($h, 'x-content-type-options')), 'nosniff')
        ? uc_f('technik', 'xcto', 'ok', 'Dateitypen-Schutz aktiv', 'Der Browser führt Dateien nur in dem Typ aus, den der Server angibt (nosniff). So kann sich kein Schadcode als harmlose Datei tarnen.', 'low', 'low')
        : uc_f('technik', 'xcto', 'warn', 'Dateitypen-Schutz fehlt', 'Ohne den Schutz-Header (X-Content-Type-Options) rät der Browser Dateitypen mitunter selbst. Dabei kann eine scheinbar harmlose Datei als ausführbarer Code durchgehen.', 'low', 'low');

    if ($isHttps) {
        $xp = $crawl['home_doc']['xpath'];
        $mixed = 0;
        if ($xp instanceof DOMXPath) {
            foreach (['//img/@src', '//script/@src', '//link/@href', '//iframe/@src', '//source/@src', '//video/@src'] as $q) {
                foreach (uc_nodes($xp, $q) as $attr) {
                    if (stripos(trim((string)$attr->nodeValue), 'http://') === 0) {
                        $mixed++;
                    }
                }
            }
        }
        $out[] = $mixed === 0
            ? uc_f('technik', 'mixed_content', 'ok', 'Alle Inhalte verschlüsselt', 'Alle eingebundenen Bilder, Skripte und Dateien werden über die sichere HTTPS-Verbindung geladen.', 'medium', 'medium')
            : uc_f('technik', 'mixed_content', 'warn', 'Unsichere Inhalte gefunden', $mixed . ' Inhalt(e) werden noch unverschlüsselt über http:// geladen. Browser blockieren solche Inhalte oder zeigen eine Warnung, obwohl die Seite selbst verschlüsselt ist.', 'medium', 'medium');
    }

    $chain = (array)($home['chain'] ?? []);
    $hops = max(0, count($chain) - 1);
    $finalStatus = (int)($home['status'] ?? 0);
    if ($finalStatus !== 200) {
        $out[] = uc_f('technik', 'status', 'warn', 'Ungewöhnliche Server-Antwort', 'Die Seite antwortet mit dem Status-Code ' . $finalStatus . ' statt mit 200 (alles in Ordnung). Das kann Besucher und Suchmaschinen irritieren.', 'medium', 'low');
    } elseif ($hops >= 2) {
        $out[] = uc_f('technik', 'status', 'warn', 'Zu viele Umleitungen', $hops . ' Umleitungen führen nacheinander zur Zielseite. Jede kostet Ladezeit, und bei Google geht dabei etwas Linkkraft verloren.', 'medium', 'low');
    } else {
        $out[] = uc_f('technik', 'status', 'ok', $hops === 0 ? 'Direkt erreichbar' : 'Saubere Umleitung', 'Die Seite antwortet mit Status 200 (alles in Ordnung)' . ($hops === 0 ? ' und ganz ohne Umleitung.' : ' nach genau einer Umleitung, das ist sauber.'), 'medium', 'low');
    }

    $server = (string)uc_h($h, 'server');
    $powered = (string)uc_h($h, 'x-powered-by');
    $leak = [];
    if ($server !== '' && preg_match('/\d/', $server)) {
        $leak[] = 'Server: ' . $server;
    }
    if ($powered !== '') {
        $leak[] = 'X-Powered-By: ' . $powered;
    }
    $out[] = $leak === []
        ? uc_f('technik', 'server_header', 'ok', 'Server bleibt diskret', 'Der Server verrät in seinen Antworten keine Versionsdetails. So bekommen Angreifer keine unnötigen Hinweise.', 'low', 'low')
        : uc_f('technik', 'server_header', 'info', 'Server verrät Details', 'Sichtbar ist: ' . implode(' · ', $leak) . '. Solche Versionsangaben machen es Angreifern leichter, bekannte Schwachstellen gezielt auszunutzen.', 'low', 'low');

    return $out;
}

// =========================================================================
// Kategorie: seo
// =========================================================================
function uc_checks_seo(array $crawl): array
{
    $xp = $crawl['home_doc']['xpath'];
    if (!$xp instanceof DOMXPath) {
        return [uc_f('seo', 'parse', 'info', 'Seite nicht auswertbar', 'Der Quellcode der Seite konnte nicht zuverlässig gelesen werden. Die SEO-Prüfung ist daher nur eingeschränkt möglich.', 'medium', 'low')];
    }
    $out = [];

    $title = '';
    foreach (uc_nodes($xp, '//title[1]') as $t) {
        $title = trim((string)$t->nodeValue);
    }
    $tl = mb_strlen($title);
    if ($title === '') {
        $out[] = uc_f('seo', 'title', 'bad', 'Seitentitel fehlt', 'Der Seitentitel (Title-Tag) ist die Zeile, die in den Google-Resultaten und im Browser-Tab erscheint. Ohne ihn fehlt der wichtigste Anker für Suchmaschinen und Besucher.', 'high', 'low');
    } elseif ($tl >= 30 && $tl <= 60) {
        $out[] = uc_f('seo', 'title', 'ok', 'Seitentitel passt', 'Der Titel hat ' . $tl . ' Zeichen (ideal sind 30–60). Er erscheint so vollständig in den Google-Resultaten.', 'high', 'low');
    } else {
        $out[] = uc_f('seo', 'title', 'warn', 'Seitentitel ' . ($tl < 30 ? 'zu kurz' : 'zu lang'), 'Der Titel hat ' . $tl . ' Zeichen, ideal sind 30–60. So bleibt er in den Google-Resultaten vollständig sichtbar und macht Lust auf den Klick.', 'high', 'low');
    }

    $desc = '';
    foreach (uc_nodes($xp, '//meta[@name]') as $m) {
        if (strtolower($m->getAttribute('name')) === 'description') {
            $desc = trim($m->getAttribute('content'));
            break;
        }
    }
    $dl = mb_strlen($desc);
    if ($desc === '') {
        $out[] = uc_f('seo', 'meta_description', 'bad', 'Beschreibung fehlt', 'Die Meta-Description ist der kurze Beschrieb unter dem Titel in den Google-Resultaten. Ohne sie zeigt Google oft willkürliche Textfetzen.', 'high', 'low');
    } elseif ($dl >= 120 && $dl <= 160) {
        $out[] = uc_f('seo', 'meta_description', 'ok', 'Beschreibung passt', 'Die Beschreibung (Meta-Description) hat ' . $dl . ' Zeichen (ideal sind 120–160). Sie wird in den Google-Resultaten vollständig angezeigt.', 'high', 'low');
    } else {
        $out[] = uc_f('seo', 'meta_description', 'warn', 'Beschreibung ' . ($dl < 120 ? 'zu kurz' : 'zu lang'), 'Die Beschreibung (Meta-Description) hat ' . $dl . ' Zeichen, ideal sind 120–160. Nur so wird sie in den Google-Resultaten vollständig angezeigt.', 'high', 'low');
    }

    $canon = uc_count($xp, "//link[translate(@rel,'ABCDEFGHIJKLMNOPQRSTUVWXYZ','abcdefghijklmnopqrstuvwxyz')='canonical']/@href");
    $out[] = $canon > 0
        ? uc_f('seo', 'canonical', 'ok', 'Dubletten-Schutz aktiv', 'Die Seite nennt per Canonical-Tag ihre eigene Hauptadresse. So weiss Google, welche Version zählt, und wertet keine Dubletten.', 'medium', 'low')
        : uc_f('seo', 'canonical', 'warn', 'Kein Dubletten-Schutz', 'Ohne Canonical-Tag kann Google die Seite unter mehreren Adressen finden (mit und ohne www oder mit Zusätzen in der Adresse). Solche Dubletten konkurrieren sich gegenseitig und schwächen das Ranking.', 'medium', 'low');

    $h1c = uc_count($xp, '//h1');
    if ($h1c === 1) {
        $out[] = uc_f('seo', 'h1', 'ok', 'Genau eine Hauptüberschrift', 'Die Seite hat genau eine H1, also eine klare Hauptüberschrift. Das gibt Suchmaschinen und Besuchern eine eindeutige Struktur.', 'medium', 'low');
    } elseif ($h1c === 0) {
        $out[] = uc_f('seo', 'h1', 'bad', 'Keine Hauptüberschrift', 'Der Seite fehlt die H1, also die wichtigste Überschrift. Sie ist das Erste, an dem sich Google und Besucher orientieren.', 'medium', 'low');
    } else {
        $out[] = uc_f('seo', 'h1', 'warn', $h1c . ' Hauptüberschriften', 'Die Seite hat mehrere H1-Überschriften. Das verwässert, worum es geht. Besser ist genau eine Hauptüberschrift pro Seite.', 'medium', 'low');
    }

    $ogMissing = [];
    foreach (['og:title', 'og:description', 'og:image'] as $p) {
        $found = false;
        foreach (uc_nodes($xp, "//meta[@property='" . $p . "']/@content") as $c) {
            if (trim((string)$c->nodeValue) !== '') {
                $found = true;
            }
        }
        if (!$found) {
            $ogMissing[] = $p;
        }
    }
    if ($ogMissing === []) {
        $out[] = uc_f('seo', 'og', 'ok', 'Teilen-Vorschau komplett', 'Titel, Beschreibung und Bild für die Vorschau sind hinterlegt (Open Graph). Geteilte Links sehen in Social Media und Chats ansprechend aus.', 'medium', 'low');
    } elseif (count($ogMissing) === 3) {
        $out[] = uc_f('seo', 'og', 'warn', 'Keine Teilen-Vorschau', 'Beim Teilen in Social Media und Chats erscheint keine ansprechende Vorschau mit Bild und Beschrieb. Die nötigen Angaben (Open Graph) fehlen komplett.', 'medium', 'low');
    } else {
        $out[] = uc_f('seo', 'og', 'warn', 'Teilen-Vorschau unvollständig', 'Für die Vorschau beim Teilen fehlen: ' . implode(', ', $ogMissing) . '. Der geteilte Link wirkt in Social Media und Chats dadurch lückenhaft.', 'medium', 'low');
    }

    $ld = uc_count($xp, "//script[@type='application/ld+json']");
    $out[] = $ld > 0
        ? uc_f('seo', 'jsonld', 'ok', 'Strukturierte Daten vorhanden', $ld . ' Block' . ($ld > 1 ? 'öcke' : '') . ' mit strukturierten Daten (JSON-LD) gefunden. Sie sind die Voraussetzung dafür, dass Google Sonderinfos wie Sterne oder Öffnungszeiten direkt im Resultat zeigt.', 'medium', 'medium')
        : uc_f('seo', 'jsonld', 'warn', 'Keine strukturierten Daten', 'Ohne strukturierte Daten (JSON-LD) kann Google keine Zusatzinfos wie Sterne, Öffnungszeiten oder FAQ direkt im Suchresultat anzeigen. Diese auffälligeren Resultate gehen dir verloren.', 'medium', 'medium');

    $robots = $crawl['robots'] ?? null;
    $robotsOk = is_array($robots) && !empty($robots['ok']) && (int)($robots['status'] ?? 0) === 200;
    $out[] = $robotsOk
        ? uc_f('seo', 'robots', 'ok', 'robots.txt vorhanden', 'Die Datei robots.txt ist erreichbar. Sie steuert, welche Bereiche Suchmaschinen durchsuchen dürfen.', 'low', 'low')
        : uc_f('seo', 'robots', 'warn', 'robots.txt fehlt', 'Ohne die Datei robots.txt fehlt die Steuerung für die Suchmaschinen, zum Beispiel der Verweis auf deine Sitemap. Meist ist sie schnell nachgerüstet.', 'low', 'low');

    $sm = $crawl['sitemap'] ?? null;
    $smOk = is_array($sm) && !empty($sm['ok']) && (int)($sm['status'] ?? 0) === 200;
    $out[] = $smOk
        ? uc_f('seo', 'sitemap', 'ok', 'Sitemap vorhanden', 'Die Sitemap (das Inhaltsverzeichnis für Suchmaschinen) ist erreichbar unter ' . (string)($sm['url'] ?? '/sitemap.xml') . '. So findet Google alle Unterseiten zuverlässig.', 'medium', 'low')
        : uc_f('seo', 'sitemap', 'warn', 'Keine Sitemap gefunden', 'Weder in der robots.txt noch unter /sitemap.xml liegt ein Inhaltsverzeichnis für Suchmaschinen bereit. Google findet deine Unterseiten so langsamer und weniger vollständig.', 'medium', 'low');

    $imgs = uc_nodes($xp, '//img');
    $n = count($imgs);
    if ($n < 3) {
        $out[] = uc_f('seo', 'img_alt', 'info', 'Wenige Bilder', 'Nur ' . $n . ' Bild(er) auf der Seite. Eine Prüfung der Bildbeschreibungen (Alt-Texte) ist hier kaum aussagekräftig.', 'medium', 'low');
    } else {
        $withAlt = 0;
        foreach ($imgs as $img) {
            if (trim($img->getAttribute('alt')) !== '') {
                $withAlt++;
            }
        }
        $q = $withAlt / $n;
        $detail = $withAlt . ' von ' . $n . ' Bildern haben eine Beschreibung (Alt-Text), das sind ' . round($q * 100) . ' %.';
        if ($q >= 0.9) {
            $out[] = uc_f('seo', 'img_alt', 'ok', 'Bildbeschreibungen gepflegt', $detail, 'medium', 'low');
        } elseif ($q >= 0.5) {
            $out[] = uc_f('seo', 'img_alt', 'warn', 'Bildbeschreibungen lückenhaft', $detail . ' Alt-Texte helfen Google, die Bilder einzuordnen, und blinden Besuchern, sie zu verstehen.', 'medium', 'low');
        } else {
            $out[] = uc_f('seo', 'img_alt', 'bad', 'Bildbeschreibungen fehlen grösstenteils', $detail . ' Hier gehen Sichtbarkeit in der Google-Bildersuche und Barrierefreiheit verloren.', 'medium', 'low');
        }
    }

    $lang = '';
    foreach (uc_nodes($xp, '//html/@lang') as $l) {
        $lang = trim((string)$l->nodeValue);
    }
    $out[] = $lang !== ''
        ? uc_f('seo', 'lang', 'ok', 'Sprache deklariert', 'Die Seite nennt ihre Sprache («' . $lang . '»). So müssen Suchmaschinen und Vorlese-Programme nicht raten.', 'low', 'low')
        : uc_f('seo', 'lang', 'warn', 'Sprache nicht deklariert', 'Der Seite fehlt die Sprachangabe (lang-Attribut). Suchmaschinen und Vorlese-Programme für Blinde müssen die Sprache sonst raten.', 'low', 'low');

    // hreflang: fuer mehrsprachige Sites (in der Schweiz oft relevant)
    $hl = [];
    foreach (uc_nodes($xp, '//link[@hreflang]/@hreflang') as $l) {
        $v = trim((string)$l->nodeValue);
        if ($v !== '') {
            $hl[$v] = true;
        }
    }
    $out[] = $hl !== []
        ? uc_f('seo', 'hreflang', 'ok', 'Sprachversionen verknüpft', 'Die Seite verweist per hreflang auf ihre Sprachversionen (' . implode(', ', array_keys($hl)) . '). Google zeigt so jeder Besucherin die richtige Sprache.', 'medium', 'medium')
        : uc_f('seo', 'hreflang', 'info', 'Keine Sprachversionen verknüpft', 'hreflang-Angaben fehlen. Das ist nur relevant, wenn die Website mehrsprachig ist: Ohne sie kann Google die Sprachversionen durcheinanderbringen.', 'medium', 'medium');

    // KI-Sichtbarkeit: blockiert robots.txt bekannte KI-Crawler? Gibt es llms.txt?
    // robots.txt gruppenweise auswerten (User-agent-Bloecke), nicht per Einzelregex
    $aiBlocked = false;
    $robotsR = $crawl['robots'] ?? null;
    if (is_array($robotsR) && !empty($robotsR['ok']) && (int)($robotsR['status'] ?? 0) === 200) {
        $agents = [];
        foreach (preg_split('/\r?\n/', (string)($robotsR['body'] ?? '')) as $line) {
            $line = trim((string)preg_replace('/#.*$/', '', $line));
            if ($line === '') {
                $agents = []; // Leerzeile trennt robots.txt-Gruppen
                continue;
            }
            if (preg_match('/^user-agent:\s*(.+)$/i', $line, $m)) {
                $agents[] = mb_strtolower(trim($m[1]));
                continue;
            }
            if (preg_match('/^disallow:\s*(\S*)$/i', $line, $m) && trim($m[1]) === '/') {
                foreach ($agents as $a) {
                    if (preg_match('/gptbot|chatgpt-user|claudebot|anthropic|perplexitybot|ccbot|google-extended/i', $a)) {
                        $aiBlocked = true;
                        break 2;
                    }
                }
            } elseif (preg_match('/^disallow:/i', $line) === 0 && !preg_match('/^(allow|sitemap|crawl-delay|user-agent):/i', $line)) {
                $agents = []; // Fremdzeile beendet die Gruppe
            }
        }
    }
    $llmsR = $crawl['llms'] ?? null;
    $llmsOk = is_array($llmsR) && !empty($llmsR['ok']) && (int)($llmsR['status'] ?? 0) === 200 && trim((string)($llmsR['body'] ?? '')) !== '';
    if ($aiBlocked) {
        $out[] = uc_f('seo', 'ai_visibility', 'warn', 'KI-Assistenten scheinbar blockiert', 'Die robots.txt sperrt bekannte KI-Crawler (z. B. von ChatGPT oder Perplexity). Immer mehr Kunden fragen KI-Assistenten statt Google: Wer blockiert, wird dort unsichtbar.', 'medium', 'low');
    } elseif ($llmsOk) {
        $out[] = uc_f('seo', 'ai_visibility', 'ok', 'Für KI-Assistenten vorbereitet', 'Die Seite hat eine llms.txt und blockiert keine KI-Crawler. KI-Assistenten können die Inhalte lesen und dich nennen.', 'medium', 'low');
    } else {
        $out[] = uc_f('seo', 'ai_visibility', 'info', 'KI-Sichtbarkeit ungeplant', 'KI-Assistenten dürfen die Seite lesen, aber eine llms.txt (Steuerdatei, die KI gezielt auf deine wichtigsten Inhalte zeigt) fehlt. Neues Thema, kein Muss, aber ein Vorsprung.', 'low', 'low');
    }

    return $out;
}

// =========================================================================
// Kategorie: performance
// =========================================================================
function uc_checks_performance(array $crawl): array
{
    $home = $crawl['home'];
    $h = (array)($home['headers'] ?? []);
    $out = [];

    $ttfb = (float)($home['ttfb'] ?? 0);
    $ms = (int)round($ttfb * 1000);
    if ($ttfb <= 0.6) {
        $out[] = uc_f('performance', 'ttfb', 'ok', 'Schnelle Serverantwort', 'Der Server antwortet in ' . $ms . ' ms (gemessen bis zum ersten Datenbyte). Das ist flott.', 'high', 'medium');
    } elseif ($ttfb <= 1.2) {
        $out[] = uc_f('performance', 'ttfb', 'warn', 'Serverantwort mittelprächtig', 'Der Server braucht ' . $ms . ' ms für die erste Antwort. Über 600 ms wird es spürbar zäh, das Ziel sind unter 600 ms.', 'high', 'medium');
    } else {
        $out[] = uc_f('performance', 'ttfb', 'bad', 'Langsame Serverantwort', 'Der Server braucht ' . $ms . ' ms für die erste Antwort, das ist zu lange. Häufige Gründe sind ein fehlender Zwischenspeicher (Server-Cache) oder ein schwaches Hosting.', 'high', 'medium');
    }

    $kb = (int)round(((int)($home['bytes'] ?? 0)) / 1024);
    if (!empty($home['truncated'])) {
        $out[] = uc_f('performance', 'html_weight', 'bad', 'Sehr schwerer Seiten-Code', 'Über 1,5 MB Quellcode (HTML) für eine einzige Seite. Das bremst den Aufbau spürbar, besonders auf dem Handy.', 'medium', 'medium');
    } elseif ($kb <= 150) {
        $out[] = uc_f('performance', 'html_weight', 'ok', 'Schlanker Seiten-Code', 'Die Seite bringt ' . $kb . ' KB Quellcode (HTML) auf die Waage. Das ist schlank und lädt schnell.', 'medium', 'medium');
    } elseif ($kb <= 400) {
        $out[] = uc_f('performance', 'html_weight', 'warn', 'Seiten-Code etwas schwer', $kb . ' KB Quellcode (HTML) für eine Seite. Pagebuilder und eingebettete Inhalte treiben dieses Gewicht oft unnötig hoch.', 'medium', 'medium');
    } else {
        $out[] = uc_f('performance', 'html_weight', 'bad', 'Schwerer Seiten-Code', $kb . ' KB Quellcode (HTML) für eine Seite. So viel Code bremst den Aufbau, besonders auf dem Handy.', 'medium', 'medium');
    }

    $xp = $crawl['home_doc']['xpath'];
    if (!$xp instanceof DOMXPath) {
        $out[] = uc_f('performance', 'parse', 'info', 'Datei-Prüfung nicht möglich', 'Der Quellcode der Seite konnte nicht zuverlässig gelesen werden. Die Prüfung der eingebundenen Dateien entfällt daher.', 'medium', 'low');
        return $out;
    }

    $imgN = uc_count($xp, '//img');
    $scriptN = uc_count($xp, '//script[@src]');
    $cssN = uc_count($xp, "//link[translate(@rel,'ABCDEFGHIJKLMNOPQRSTUVWXYZ','abcdefghijklmnopqrstuvwxyz')='stylesheet']");
    $assets = $imgN + $scriptN + $cssN;
    $detail = $assets . ' eingebundene Dateien (' . $imgN . ' Bilder, ' . $scriptN . ' Skripte, ' . $cssN . ' Design-Dateien).';
    if ($assets <= 40) {
        $out[] = uc_f('performance', 'asset_count', 'ok', 'Überschaubare Zahl an Dateien', $detail, 'medium', 'medium');
    } elseif ($assets <= 80) {
        $out[] = uc_f('performance', 'asset_count', 'warn', 'Viele eingebundene Dateien', $detail . ' Jede einzelne Datei kostet eine Anfrage und damit Ladezeit.', 'medium', 'medium');
    } else {
        $out[] = uc_f('performance', 'asset_count', 'bad', 'Sehr viele eingebundene Dateien', $detail . ' Hier lohnt es sich, Dateien zusammenzufassen und konsequent auszumisten.', 'medium', 'medium');
    }

    $headCss = uc_count($xp, "//head/link[translate(@rel,'ABCDEFGHIJKLMNOPQRSTUVWXYZ','abcdefghijklmnopqrstuvwxyz')='stylesheet']");
    $headSyncJs = 0;
    foreach (uc_nodes($xp, '//head/script[@src]') as $s) {
        if (!$s->hasAttribute('async') && !$s->hasAttribute('defer') && strtolower($s->getAttribute('type')) !== 'module') {
            $headSyncJs++;
        }
    }
    $blocking = $headCss + $headSyncJs;
    $bDetail = 'Im Kopf der Seite laden ' . $headSyncJs . ' Skripte und ' . $headCss . ' Design-Dateien, die der Browser zuerst abarbeiten muss.';
    if ($headSyncJs === 0 && $headCss <= 2) {
        $out[] = uc_f('performance', 'render_blocking', 'ok', 'Erster Aufbau kaum gebremst', $bDetail, 'high', 'medium');
    } elseif ($blocking <= 4) {
        $out[] = uc_f('performance', 'render_blocking', 'warn', 'Dateien bremsen den ersten Aufbau', $bDetail, 'high', 'medium');
    } else {
        $out[] = uc_f('performance', 'render_blocking', 'bad', 'Erster Aufbau stark gebremst', $bDetail . ' Solange bleibt die Seite weiss, und der erste Eindruck wirkt träge.', 'high', 'medium');
    }

    if ($imgN >= 5) {
        $lazy = 0;
        foreach (uc_nodes($xp, '//img') as $img) {
            // natives lazy loading oder gängige JS-LazyLoader (z. B. WP Rocket data-lazy-src)
            if (strtolower($img->getAttribute('loading')) === 'lazy' || $img->hasAttribute('data-lazy-src') || $img->hasAttribute('data-src')) {
                $lazy++;
            }
        }
        $q = $lazy / $imgN;
        $lDetail = $lazy . ' von ' . $imgN . ' Bildern laden verzögert, also erst wenn sie sichtbar werden.';
        $out[] = $q >= 0.3
            ? uc_f('performance', 'lazy', 'ok', 'Bilder laden bei Bedarf', $lDetail, 'medium', 'low')
            : uc_f('performance', 'lazy', 'warn', 'Bilder laden meist sofort', $lDetail . ' Bilder, die man erst beim Scrollen sieht, sollten auch erst dann geladen werden. Das macht den Start der Seite schneller.', 'medium', 'low');
    } else {
        $out[] = uc_f('performance', 'lazy', 'info', 'Wenige Bilder', 'Nur ' . $imgN . ' Bild(er) auf der Seite. Verzögertes Laden (Lazy Loading) ist hier kaum relevant.', 'medium', 'low');
    }

    $raster = 0;
    $modern = 0;
    foreach (uc_nodes($xp, '//img') as $img) {
        $src = strtolower($img->getAttribute('src') . ' ' . $img->getAttribute('srcset') . ' ' . $img->getAttribute('data-src'));
        // <picture>-Varianten desselben Bildes mitbewerten (WebP/AVIF via <source>).
        // Achtung: libxml parst <source> nicht als Void-Element, das <img> landet als
        // Kind von <source>, darum die Vorfahren-Kette nach <picture> durchsuchen.
        $node = $img->parentNode;
        while ($node instanceof DOMElement) {
            if (strtolower($node->nodeName) === 'picture') {
                foreach ($node->getElementsByTagName('source') as $source) {
                    $src .= ' ' . strtolower($source->getAttribute('type') . ' ' . $source->getAttribute('srcset'));
                }
                break;
            }
            $node = $node->parentNode;
        }
        // modern gewinnt bei gemischtem Markup (jpg-Fallback + WebP-Source zählt als modern)
        if (preg_match('/\.(webp|avif)(\?| |$)|image\/(webp|avif)/', $src)) {
            $modern++;
        } elseif (preg_match('/\.(jpe?g|png|gif)(\?| |$)/', $src)) {
            $raster++;
        }
    }
    $total = $raster + $modern;
    if ($total < 3) {
        $out[] = uc_f('performance', 'modern_images', 'info', 'Wenige Bilder', 'Zu wenige Bilder für eine aussagekräftige Prüfung der Bildformate.', 'medium', 'medium');
    } else {
        $q = $modern / $total;
        $mDetail = $modern . ' von ' . $total . ' Bildern nutzen moderne Formate (WebP oder AVIF).';
        if ($q >= 0.5) {
            $out[] = uc_f('performance', 'modern_images', 'ok', 'Moderne Bildformate', $mDetail, 'medium', 'medium');
        } elseif ($modern > 0) {
            $out[] = uc_f('performance', 'modern_images', 'warn', 'Teils moderne Bildformate', $mDetail . ' Der Rest liegt noch als JPEG oder PNG vor. WebP oder AVIF wären bei gleicher Qualität deutlich kleiner und schneller geladen.', 'medium', 'medium');
        } else {
            $out[] = uc_f('performance', 'modern_images', 'warn', 'Keine modernen Bildformate', 'Alle ' . $total . ' Bilder liegen als JPEG, PNG oder GIF vor. WebP oder AVIF wären bei gleicher Qualität deutlich kleiner und würden die Seite spürbar schneller machen.', 'medium', 'medium');
        }
    }

    $cc = strtolower((string)uc_h($h, 'cache-control'));
    $hasCache = $cc !== '' && !str_contains($cc, 'no-store') && !str_contains($cc, 'no-cache') && preg_match('/max-age=([1-9]\d*)/', $cc) === 1;
    $out[] = $hasCache
        ? uc_f('performance', 'cache_headers', 'ok', 'Zwischenspeicher aktiv', 'Die Seite darf im Browser zwischengespeichert werden (Cache-Control: ' . $cc . '). Wiederkehrende Besucher laden so deutlich schneller.', 'low', 'low')
        : uc_f('performance', 'cache_headers', 'warn', 'Kein Zwischenspeicher für die Seite', 'Ohne Cache-Control lädt der Browser die Seite bei jedem Besuch komplett neu. Ein Zwischenspeicher würde wiederkehrenden Besuchern Ladezeit sparen.', 'low', 'low');

    // Bildgewicht: Stichprobe bis 4 Bilder per HEAD (Deadline beachten)
    if ($xp instanceof DOMXPath && !uc_deadline_close(6)) {
        $imgUrls = [];
        $base = (string)$home['url'];
        foreach (uc_nodes($xp, '//img') as $img) {
            $src = trim((string)$img->getAttribute('src'));
            if ($src === '') {
                $srcset = trim((string)$img->getAttribute('srcset'));
                if ($srcset !== '') {
                    $src = trim((string)preg_split('/,/', $srcset)[0]);
                    $src = trim((string)preg_split('/\s+/', $src)[0]);
                }
            }
            if ($src === '' || str_starts_with($src, 'data:')) {
                continue;
            }
            $abs = uc_resolve_relative($base, $src);
            if ($abs !== null) {
                $imgUrls[$abs] = true;
            }
            if (count($imgUrls) >= 4) {
                break;
            }
        }
        if ($imgUrls !== []) {
            $res = uc_fetch_multi(array_combine(array_keys($imgUrls), array_keys($imgUrls)), 6, ['head_all' => true]);
            $known = 0;
            $worst = 0;
            $worstKb = 0;
            foreach ($res as $u => $r) {
                if (empty($r['ok'])) {
                    continue;
                }
                $len = (int)(uc_h((array)$r['headers'], 'content-length') ?? 0);
                if ($len <= 0) {
                    continue;
                }
                $known++;
                if ($len > $worst) {
                    $worst = $len;
                    $worstKb = (int)round($len / 1024);
                }
            }
            if ($known === 0) {
                $out[] = uc_f('performance', 'image_weight', 'info', 'Bildgewicht nicht messbar', 'Die Server-Antworten verraten nicht, wie schwer die Bilder sind. Die Prüfung des Bildgewichts entfällt daher.', 'high', 'low');
            } elseif ($worst >= 1500000) {
                $out[] = uc_f('performance', 'image_weight', 'bad', 'Überschweres Bild', 'Das schwerste getestete Bild wiegt ' . number_format($worstKb / 1024, 1, ',', '') . ' MB (Stichprobe von ' . $known . ' Bildern). Solche Brocken bremsen die Seite massiv, gerade auf dem Handy unterwegs. Verkleinern und als WebP speichern wirkt sofort.', 'high', 'low');
            } elseif ($worst >= 500000) {
                $out[] = uc_f('performance', 'image_weight', 'warn', 'Schweres Bild gefunden', 'Das schwerste getestete Bild wiegt ' . $worstKb . ' KB (Stichprobe von ' . $known . ' Bildern). Als Faustregel gilt: kaum ein Bild braucht mehr als 300 KB. Verkleinern spart spürbar Ladezeit.', 'high', 'low');
            } else {
                $out[] = uc_f('performance', 'image_weight', 'ok', 'Bilder schlank', 'Das schwerste getestete Bild wiegt ' . $worstKb . ' KB (Stichprobe von ' . $known . ' Bildern). Das ist vorbildlich.', 'high', 'low');
            }
        }
    }

    // Echte Ladezeit-Daten (Chrome UX Report), wenn ein Google-Key konfiguriert ist
    if (!uc_deadline_close(4)) {
        $cwv = uc_crux_fetch((string)$home['url']);
        if (is_array($cwv) && ($cwv['lcp_ms'] !== null || $cwv['inp_ms'] !== null)) {
            $parts = [];
            $worstStatus = 'ok';
            if ($cwv['lcp_ms'] !== null) {
                $lcpS = number_format($cwv['lcp_ms'] / 1000, 1, ',', '');
                $parts[] = 'Hauptinhalt sichtbar nach ' . $lcpS . ' s';
                if ($cwv['lcp_ms'] > 4000) {
                    $worstStatus = 'bad';
                } elseif ($cwv['lcp_ms'] > 2500) {
                    $worstStatus = 'warn';
                }
            }
            if ($cwv['inp_ms'] !== null) {
                $parts[] = 'Reaktion auf Klicks nach ' . (int)round($cwv['inp_ms']) . ' ms';
                if ($cwv['inp_ms'] > 500) {
                    $worstStatus = 'bad';
                } elseif ($cwv['inp_ms'] > 200 && $worstStatus === 'ok') {
                    $worstStatus = 'warn';
                }
            }
            if ($cwv['cls'] !== null) {
                $parts[] = 'Layout-Sprünge ' . number_format($cwv['cls'], 2, ',', '');
                if ($cwv['cls'] > 0.25) {
                    $worstStatus = 'bad';
                } elseif ($cwv['cls'] > 0.1 && $worstStatus === 'ok') {
                    $worstStatus = 'warn';
                }
            }
            $detail = 'Gemessen bei echten Besuchern (Google Chrome-Daten, 28 Tage): ' . implode(', ', $parts) . '.';
            $out[] = match ($worstStatus) {
                'bad' => uc_f('performance', 'cwv_field', 'bad', 'Echte Besucher warten zu lange', $detail . ' Diese Werte kommen nicht aus dem Labor, sondern aus dem echten Leben: Google stuft sie als zu schwach ein.', 'high', 'medium'),
                'warn' => uc_f('performance', 'cwv_field', 'warn', 'Echte Besucher spüren Wartezeit', $detail . ' Diese Werte kommen von echten Besuchern, nicht aus dem Labor. Da ist noch spürbar Luft nach oben.', 'high', 'medium'),
                default => uc_f('performance', 'cwv_field', 'ok', 'Echte Besucher laden schnell', $detail . ' Gemessen an echten Besuchern, nicht im Labor: Das ist ein stärkeres Qualitätszeichen als jeder synthetische Test.', 'high', 'medium'),
            };
        }
    }

    return $out;
}

// =========================================================================
// Kategorie: inhalt
// =========================================================================
function uc_checks_inhalt(array $crawl): array
{
    $xp = $crawl['home_doc']['xpath'];
    $out = [];
    $text = $xp instanceof DOMXPath ? uc_visible_text($xp) : '';

    if (!$xp instanceof DOMXPath) {
        return [uc_f('inhalt', 'parse', 'info', 'Seite nicht auswertbar', 'Der Quellcode der Seite konnte nicht zuverlässig gelesen werden.', 'medium', 'low')];
    }

    $words = preg_match_all('/[\p{L}\p{N}]+/u', $text);
    if ($words >= 600) {
        $out[] = uc_f('inhalt', 'word_count', 'ok', 'Solide Textmenge', $words . ' sichtbare Wörter auf der Startseite. Das ist genug Substanz für Besucher und für Google.', 'high', 'medium');
    } elseif ($words >= 300) {
        $out[] = uc_f('inhalt', 'word_count', 'warn', 'Textmenge dünn', $words . ' sichtbare Wörter auf der Startseite. Für gute Platzierungen bei Google und echte Überzeugungsarbeit empfehlen wir mindestens 600.', 'high', 'medium');
    } else {
        $out[] = uc_f('inhalt', 'word_count', 'bad', 'Sehr wenig Text', $words . ' sichtbare Wörter auf der Startseite. Mit so wenig Substanz kann Google die Seite kaum einordnen, und Besucher finden zu wenig, das sie überzeugt.', 'high', 'medium');
    }

    // Kontakt-Auffindbarkeit über ALLE gecrawlten Seiten
    $found = [];
    $pages = ['__home' => $crawl['home_doc']];
    foreach ((array)$crawl['sub_docs'] as $su => $sd) {
        $pages[$su] = $sd;
    }
    foreach ($pages as $pk => $d) {
        $px = $d['xpath'] ?? null;
        if (!$px instanceof DOMXPath) {
            continue;
        }
        $label = $pk === '__home' ? 'Startseite' : (string)parse_url($pk, PHP_URL_PATH);
        if (uc_count($px, '//form') > 0) {
            $found[] = 'Formular (' . $label . ')';
        }
        if (uc_count($px, "//a[starts-with(@href,'tel:')]") > 0) {
            $found[] = 'Telefon-Link (' . $label . ')';
        }
        if (uc_count($px, "//a[starts-with(@href,'mailto:')]") > 0) {
            $found[] = 'E-Mail-Link (' . $label . ')';
        }
    }
    $found = array_values(array_unique($found));
    $out[] = $found !== []
        ? uc_f('inhalt', 'contact', 'ok', 'Kontakt leicht auffindbar', 'Gefunden: ' . implode(' · ', $found) . '. Wer anfragen will, findet sofort einen Weg zu dir.', 'high', 'low')
        : uc_f('inhalt', 'contact', 'bad', 'Kein Kontakt auffindbar', 'Auf keiner der ' . (int)$crawl['pages_crawled'] . ' geprüften Seiten fanden wir ein Formular, einen Telefon- oder einen E-Mail-Link. So gehen Anfragen verloren, weil Interessenten nicht wissen, wie sie dich erreichen.', 'high', 'low');

    // CTA-Erkennung auf der Startseite
    $ctas = [];
    foreach (uc_nodes($xp, '//a | //button') as $el) {
        $t = trim((string)preg_replace('/\s+/u', ' ', (string)$el->textContent));
        if ($t !== '' && mb_strlen($t) <= 40 && preg_match('/kontakt|angebot|anfragen|beratung|termin|offerte/iu', $t)) {
            $ctas[] = $t;
        }
        if (count($ctas) >= 5) {
            break;
        }
    }
    $ctas = array_values(array_unique($ctas));
    $out[] = $ctas !== []
        ? uc_f('inhalt', 'cta', 'ok', 'Handlungsaufforderung vorhanden', 'Gefunden: «' . implode('», «', array_slice($ctas, 0, 3)) . '». Klare Buttons führen Besucher zum nächsten Schritt.', 'medium', 'low')
        : uc_f('inhalt', 'cta', 'warn', 'Keine klare Handlungsaufforderung', 'Wir finden keinen Link oder Button mit einer klaren Aufforderung wie «Kontakt», «Angebot anfragen» oder «Termin buchen». Besucher wissen dann nicht, was der nächste Schritt ist, und springen eher ab.', 'medium', 'low');

    // Defekte interne Links (Stichprobe)
    $bl = (array)($crawl['broken_links'] ?? ['skipped' => true]);
    if (!empty($bl['skipped'])) {
        $out[] = uc_f('inhalt', 'broken_links', 'info', 'Link-Prüfung übersprungen', 'Die Stichprobe der internen Links wurde aus Zeitgründen übersprungen.', 'medium', 'medium');
    } else {
        $n = count((array)$bl['broken']);
        if ($n === 0) {
            $out[] = uc_f('inhalt', 'broken_links', 'ok', 'Interne Links intakt', (int)$bl['checked'] . ' interne Links stichprobenartig geprüft, alle erreichbar.', 'medium', 'medium');
        } else {
            $examples = array_slice((array)$bl['broken'], 0, 3);
            $out[] = $n === 1
                ? uc_f('inhalt', 'broken_links', 'warn', '1 defekter Link', 'Dieser interne Link ist nicht erreichbar: ' . implode(', ', $examples) . '. Defekte Links frustrieren Besucher und kosten Vertrauen.', 'medium', 'medium')
                : uc_f('inhalt', 'broken_links', 'bad', $n . ' defekte Links', 'Nicht erreichbar (Stichprobe): ' . implode(', ', $examples) . '. Defekte Links frustrieren Besucher und kosten Vertrauen.', 'medium', 'medium');
        }
    }

    // Frische-Signale: Jahreszahlen im Text
    if (preg_match_all('/\b(20[12]\d)\b/', $text, $m) && !empty($m[1])) {
        $max = max(array_map('intval', $m[1]));
        $y = (int)date('Y');
        $out[] = $max >= $y - 1
            ? uc_f('inhalt', 'freshness', 'ok', 'Inhalt wirkt gepflegt', 'Aktuelle Jahreszahl (' . $max . ') im Text gefunden. Die Seite wirkt aktiv gepflegt.', 'medium', 'low')
            : uc_f('inhalt', 'freshness', 'warn', 'Inhalt könnte veraltet wirken', 'Neueste Jahreszahl im Text: ' . $max . '. Alte Daten signalisieren Besuchern und Google, dass sich hier niemand mehr kümmert.', 'medium', 'low');
    } else {
        $out[] = uc_f('inhalt', 'freshness', 'info', 'Keine Jahreszahlen gefunden', 'Keine Datumsangaben im Text. Das ist weder positiv noch negativ auffällig.', 'medium', 'low');
    }

    // ---- Textqualitaet (V2): Lesbarkeit, Keyword-Stuffing, Floskeln ----
    $textParts = [];
    foreach (uc_nodes($xp, '//body//text()[not(ancestor::script) and not(ancestor::style) and not(ancestor::noscript)]') as $tn) {
        $tv = trim((string)$tn->nodeValue);
        if ($tv !== '') {
            $textParts[] = $tv;
        }
    }
    $ts = uc_text_stats($text, $textParts);
    if ($ts['words'] < 80) {
        $out[] = uc_f('inhalt', 'readability', 'info', 'Lesbarkeit kaum messbar', 'Zu wenig Fliesstext für eine verlässliche Lesbarkeits-Messung.', 'high', 'medium');
    } else {
        $fre = $ts['fre'];
        $aslTxt = number_format($ts['asl'], 1, ',', '');
        // Ehrliche Begruendung: Treiber nennen (Satzlänge vs. Wortlänge)
        $driver = [];
        if ($ts['asl'] > 16) {
            $driver[] = 'lange Schachtelsätze (Ø ' . $aslTxt . ' Wörter pro Satz)';
        }
        if ($ts['asw'] > 1.85) {
            $driver[] = 'viele lange Wörter (Ø ' . number_format($ts['asw'], 1, ',', '') . ' Silben pro Wort)';
        }
        if ($driver === []) {
            $driver[] = 'verschachtelte Formulierungen';
        }
        $driverTxt = implode(' und ', $driver);
        if ($fre >= 60) {
            $out[] = uc_f('inhalt', 'readability', 'ok', 'Gut lesbare Texte', 'Die Texte sind angenehm lesbar (Ø ' . $aslTxt . ' Wörter pro Satz, Lesbarkeits-Index ' . (int)round($fre) . ' nach der Amstad-Formel). So liest man bis zum Schluss.', 'high', 'medium');
        } elseif ($fre >= 45) {
            $out[] = uc_f('inhalt', 'readability', 'warn', 'Texte sind zäh zu lesen', 'Auffällig sind ' . $driverTxt . ' (Lesbarkeits-Index ' . (int)round($fre) . ', ideal ab 60). Kürzere Sätze und einfachere Wörter bringen mehr Leser bis zum Kontakt-Button.', 'high', 'medium');
        } else {
            $out[] = uc_f('inhalt', 'readability', 'bad', 'Texte sind schwer lesbar', 'Die Texte verlangen viel: ' . $driverTxt . ' (Lesbarkeits-Index ' . (int)round($fre) . ', ideal ab 60). Die meisten Besucher brechen solche Texte ab, bevor sie anfragen.', 'high', 'medium');
        }

        // Keyword-Stuffing: ein Begriff dominiert unnatuerlich, oder Text wirkt repetitiv
        $stuffReasons = [];
        if ($ts['top_density'] >= 0.06) {
            $stuffReasons[] = 'bad';
        } elseif ($ts['top_density'] >= 0.035) {
            $stuffReasons[] = 'warn';
        }
        if ($ts['lexdiv'] < 0.30 && $ts['words'] >= 250) {
            $stuffReasons[] = 'warn';
        }
        if ($stuffReasons === []) {
            $out[] = uc_f('inhalt', 'keyword_balance', 'ok', 'Natürliche Sprache', 'Kein Begriff wird unnatürlich oft wiederholt, und der Wortschatz ist abwechslungsreich. Der Text liest sich wie für Menschen geschrieben.', 'high', 'medium');
        } else {
            $topPct = number_format($ts['top_density'] * 100, 1, ',', '');
            $bad = in_array('bad', $stuffReasons, true);
            $out[] = uc_f('inhalt', 'keyword_balance', $bad ? 'bad' : 'warn', $bad ? 'Für Suchmaschinen statt für Menschen geschrieben' : 'Sprache wirkt für Suchmaschinen optimiert',
                'Das Wort «' . $ts['top_term'] . '» macht ' . $topPct . ' % des Textes aus' . ($ts['lexdiv'] < 0.30 && $ts['words'] >= 250 ? ', und der Text wiederholt sich insgesamt viel' : '') . '. Texte, die vor allem Google füttern sollen, lesen Kunden nicht mehr. Google straft das inzwischen sogar ab.',
                'high', 'medium');
        }

        // Floskeln vs. Konkretheit
        $fh = $ts['floskel_hits'];
        if (count($fh) >= 6) {
            $out[] = uc_f('inhalt', 'floskeln', 'bad', 'Viele Floskeln, wenig Substanz', 'Gefunden: «' . implode('», «', array_slice($fh, 0, 4)) . '» und weitere. Solche Allerweltsphrasen sagen nichts über deinen Betrieb aus. Zahlen, echte Beispiele und konkrete Angebote überzeugen stattdessen.', 'medium', 'medium');
        } elseif (count($fh) >= 3) {
            $out[] = uc_f('inhalt', 'floskeln', 'warn', 'Floskeln im Text', 'Gefunden: «' . implode('», «', array_slice($fh, 0, 3)) . '». Solche Phrasen stehen auf tausend Websites. Konkrete Zahlen und Beispiele aus deinem Betrieb wären überzeugender.', 'medium', 'medium');
        } elseif (count($fh) === 0 && $ts['concrete'] >= 3) {
            $out[] = uc_f('inhalt', 'floskeln', 'ok', 'Konkrete, eigene Sprache', 'Keine leeren Werbephrasen gefunden, dafür echte Zahlen und Fakten. Genau so wirkt ein KMU-Text glaubwürdig.', 'medium', 'low');
        } else {
            $out[] = uc_f('inhalt', 'floskeln', 'ok', 'Wenig Phrasen', 'Kaum Werbefloskeln gefunden. Der Text klingt nach dir, nicht nach Werbetexter.', 'medium', 'low');
        }
    }

    // ---- Vertrauen & Recht (CH): Impressum/UWG, Datenschutz (nDSG) ----
    $subDocs = (array)($crawl['sub_docs'] ?? []);
    $hasImprintPage = false;
    foreach (array_keys($subDocs) as $su) {
        if (preg_match('/impressum|imprint/i', (string)$su)) {
            $hasImprintPage = true;
        }
    }
    // Alle gecrawlten Seiten nach Adresse + Kontaktmoeglichkeit durchsuchen
    $addrFound = false;
    $mailOrTel = false;
    $allDocs = array_merge(['__home' => $crawl['home_doc']], $subDocs);
    foreach ($allDocs as $d) {
        $px = $d['xpath'] ?? null;
        if (!$px instanceof DOMXPath) {
            continue;
        }
        if (uc_count($px, "//a[starts-with(@href,'mailto:')]") > 0 || uc_count($px, "//a[starts-with(@href,'tel:')]") > 0) {
            $mailOrTel = true;
        }
        $t = uc_visible_text($px);
        if (preg_match('/\b[1-9]\d{3}\s+[A-ZÄÖÜ][\p{L}äöüéàè\-]+/u', $t)) {
            $addrFound = true;
        }
    }
    if ($hasImprintPage && $addrFound && $mailOrTel) {
        $out[] = uc_f('inhalt', 'impressum', 'ok', 'Impressum mit vollständigen Angaben', 'Ein Impressum mit Adresse und direkter Kontaktmöglichkeit ist vorhanden. Das erwartet das Gesetz für kommerzielle Angebote (UWG), und es schafft Vertrauen.', 'high', 'low');
    } elseif ($addrFound && $mailOrTel) {
        $out[] = uc_f('inhalt', 'impressum', 'warn', 'Kein eigenes Impressum', 'Adresse und Kontaktmöglichkeit sind auffindbar, aber ein eigenes Impressum fehlt. Für kommerzielle Angebote erwartet das Gesetz (UWG) klare Angaben zu Identität und Kontakt, und Besucher suchen genau danach.', 'high', 'low');
    } else {
        $out[] = uc_f('inhalt', 'impressum', 'warn', 'Adresse und Kontakt schwer auffindbar', 'Wir haben weder eine vollständige Adresse noch eine direkte Kontaktmöglichkeit (E-Mail oder Telefon) gefunden. Das wirkt auf Besucher misstrauenserregend, und auch das Gesetz (UWG) erwartet: klare Angaben zu Identität und Kontakt gehören gut sichtbar auf die Seite.', 'high', 'low');
    }

    $dsFound = false;
    foreach ($allDocs as $d) {
        $px = $d['xpath'] ?? null;
        if (!$px instanceof DOMXPath) {
            continue;
        }
        foreach (uc_nodes($px, '//a[@href]') as $a) {
            $h = mb_strtolower((string)$a->getAttribute('href') . ' ' . (string)$a->textContent);
            if (preg_match('/datenschutz|privacy|confidentialit/i', $h)) {
                $dsFound = true;
                break 2;
            }
        }
    }
    $out[] = $dsFound
        ? uc_f('inhalt', 'datenschutz', 'ok', 'Datenschutzerklärung verlinkt', 'Eine Datenschutzerklärung ist verlinkt. Das schafft die Transparenz, die das Schweizer Datenschutzgesetz (nDSG) erwartet.', 'medium', 'low')
        : uc_f('inhalt', 'datenschutz', 'warn', 'Keine Datenschutzerklärung gefunden', 'Wir haben keinen Link zu einer Datenschutzerklärung gefunden. Das nDSG erwartet Transparenz über die Datenbearbeitung, und viele Besucher schauen gezielt danach. Meist eine Sache von einer halben Stunde.', 'medium', 'low');

    // ---- Stockfotos: authentisches Bildmaterial zaehlt fuer KMU-Vertrauen ----
    $stockHosts = 'istockphoto|shutterstock|stock\.adobe|adobestock|fotolia|dreamstime|gettyimages|123rf|depositphotos|unsplash|pixabay|pexels';
    $stockN = 0;
    $imgTotal = 0;
    foreach (uc_nodes($xp, '//img') as $img) {
        $imgTotal++;
        $srcs = mb_strtolower((string)$img->getAttribute('src') . ' ' . (string)$img->getAttribute('srcset') . ' ' . (string)$img->getAttribute('data-src'));
        if (preg_match('/' . $stockHosts . '/', $srcs)) {
            $stockN++;
        }
    }
    if ($stockN > 0) {
        $out[] = uc_f('inhalt', 'stock_photos', 'warn', 'Stockfotos erkannt', $stockN . ' Bild(er) stammen von Bilddatenbanken. Gestellte Stockbilder erkennen Besucher sofort: Echte Fotos aus deinem Betrieb schaffen mehr Vertrauen und unterscheiden dich von der Konkurrenz.', 'medium', 'medium');
    } elseif ($imgTotal >= 3) {
        $out[] = uc_f('inhalt', 'stock_photos', 'ok', 'Eigenes Bildmaterial', 'Keine Stockfotos von Bilddatenbanken gefunden. Eigenes Material wirkt echt und baut Vertrauen auf.', 'medium', 'medium');
    }

    return $out;
}

// =========================================================================
// Kategorie: ux
// =========================================================================
function uc_checks_ux(array $crawl): array
{
    $xp = $crawl['home_doc']['xpath'];
    if (!$xp instanceof DOMXPath) {
        return [uc_f('ux', 'parse', 'info', 'Seite nicht auswertbar', 'Der Quellcode der Seite konnte nicht zuverlässig gelesen werden.', 'medium', 'low')];
    }
    $out = [];

    $vp = 0;
    foreach (uc_nodes($xp, '//meta[@name]') as $m) {
        if (strtolower($m->getAttribute('name')) === 'viewport') {
            $vp++;
        }
    }
    $out[] = $vp > 0
        ? uc_f('ux', 'viewport', 'ok', 'Für Handys eingerichtet', 'Die Seite ist für Mobilgeräte konfiguriert (Viewport-Angabe vorhanden).', 'high', 'low')
        : uc_f('ux', 'viewport', 'bad', 'Nicht fürs Handy eingerichtet', 'Ohne Viewport-Angabe stellt der Browser die Seite auf dem Handy winzig skaliert dar. Sie ist dort faktisch unbenutzbar, und die meisten Besucher kommen heute übers Handy.', 'high', 'low');

    $imgs = uc_nodes($xp, '//img');
    $n = count($imgs);
    if ($n >= 3) {
        $sr = 0;
        foreach ($imgs as $img) {
            if ($img->hasAttribute('srcset')) {
                $sr++;
                continue;
            }
            // <picture><source srcset> gilt ebenfalls als geräteabhängige Grösse
            // (Vorfahren-Suche: libxml parst das <img> als Kind von <source>)
            $node = $img->parentNode;
            while ($node instanceof DOMElement) {
                if (strtolower($node->nodeName) === 'picture') {
                    foreach ($node->getElementsByTagName('source') as $source) {
                        if (trim((string)$source->getAttribute('srcset')) !== '') {
                            $sr++;
                            break;
                        }
                    }
                    break;
                }
                $node = $node->parentNode;
            }
        }
        $q = $sr / $n;
        $sDetail = $sr . ' von ' . $n . ' Bildern laden je nach Gerät in passender Grösse.';
        if ($q >= 0.5) {
            $out[] = uc_f('ux', 'srcset', 'ok', 'Bilder in passender Grösse', $sDetail, 'medium', 'medium');
        } elseif ($sr > 0) {
            $out[] = uc_f('ux', 'srcset', 'warn', 'Bilder nur teils angepasst', $sDetail . ' Für die übrigen Bilder lohnt sich das Nachrüsten.', 'medium', 'medium');
        } else {
            $out[] = uc_f('ux', 'srcset', 'warn', 'Bilder nur in einer Grösse', 'Alle ' . $n . ' Bilder liegen nur in einer einzigen Grösse vor. Handys laden dann unnötig grosse Dateien, und das kostet Ladezeit.', 'medium', 'medium');
        }
    } else {
        $out[] = uc_f('ux', 'srcset', 'info', 'Wenige Bilder', 'Nur ' . $n . ' Bild(er) auf der Seite. Die Prüfung der Bildgrössen ist hier kaum aussagekräftig.', 'medium', 'medium');
    }

    // Formular-Labels über Home + Unterseiten
    $pages = ['__home' => $crawl['home_doc']];
    foreach ((array)$crawl['sub_docs'] as $su => $sd) {
        $pages[$su] = $sd;
    }
    $inputs = 0;
    $unlabeled = 0;
    foreach ($pages as $d) {
        $px = $d['xpath'] ?? null;
        if (!$px instanceof DOMXPath) {
            continue;
        }
        $labelFor = [];
        foreach (uc_nodes($px, '//label[@for]') as $l) {
            $labelFor[$l->getAttribute('for')] = true;
        }
        foreach (uc_nodes($px, '//input | //textarea | //select') as $in) {
            $type = strtolower($in->getAttribute('type') !== '' ? $in->getAttribute('type') : 'text');
            if ($in->nodeName === 'input' && in_array($type, ['hidden', 'submit', 'button', 'checkbox', 'radio', 'file', 'image', 'reset', 'range'], true)) {
                continue;
            }
            $inputs++;
            $id = $in->getAttribute('id');
            $hasLabel = ($id !== '' && isset($labelFor[$id]))
                || trim($in->getAttribute('aria-label')) !== ''
                || trim($in->getAttribute('aria-labelledby')) !== ''
                || trim($in->getAttribute('title')) !== '';
            if (!$hasLabel) {
                // in ein <label> eingebettet?
                $anc = $in->parentNode;
                while ($anc instanceof DOMElement) {
                    if ($anc->nodeName === 'label') {
                        $hasLabel = true;
                        break;
                    }
                    $anc = $anc->parentNode;
                }
            }
            if (!$hasLabel) {
                $unlabeled++;
            }
        }
    }
    if ($inputs === 0) {
        $out[] = uc_f('ux', 'form_labels', 'info', 'Keine Formulare gefunden', 'Auf den geprüften Seiten haben wir keine Formularfelder gefunden.', 'medium', 'low');
    } elseif ($unlabeled === 0) {
        $out[] = uc_f('ux', 'form_labels', 'ok', 'Formularfelder beschriftet', 'Alle ' . $inputs . ' Formularfeld(er) sind beschriftet. Das hilft beim Ausfüllen, bei der automatischen Vervollständigung und bei Vorlese-Programmen.', 'medium', 'low');
    } else {
        $out[] = uc_f('ux', 'form_labels', 'warn', $unlabeled . ' Formularfeld(er) ohne Beschriftung', 'Von ' . $inputs . ' Feldern fehlt bei ' . $unlabeled . ' die Beschriftung. Das erschwert das Ausfüllen, die automatische Vervollständigung und die Nutzung mit Vorlese-Programmen.', 'medium', 'low');
    }

    // Generische Linktexte
    $generic = ['hier klicken', 'mehr', 'click here', 'hier'];
    $gen = 0;
    foreach (uc_nodes($xp, '//a') as $a) {
        $t = mb_strtolower(trim((string)preg_replace('/\s+/u', ' ', (string)$a->textContent)));
        if (in_array($t, $generic, true)) {
            $gen++;
        }
    }
    $out[] = $gen === 0
        ? uc_f('ux', 'linktexts', 'ok', 'Sprechende Linktexte', 'Keine nichtssagenden Linktexte wie «hier klicken» gefunden.', 'low', 'low')
        : uc_f('ux', 'linktexts', 'warn', $gen . ' nichtssagende(r) Linktext(e)', 'Links wie «hier klicken» oder «mehr» verraten weder Besuchern noch Google, was sie nach dem Klick erwartet.', 'low', 'low');

    $fav = uc_count($xp, "//link[contains(translate(@rel,'ABCDEFGHIJKLMNOPQRSTUVWXYZ','abcdefghijklmnopqrstuvwxyz'),'icon')]");
    $out[] = $fav > 0
        ? uc_f('ux', 'favicon', 'ok', 'Favicon vorhanden', 'Das kleine Symbol (Favicon) erscheint in Browser-Tabs, Lesezeichen und Google-Resultaten. Deine Seite bleibt so wiedererkennbar.', 'low', 'low')
        : uc_f('ux', 'favicon', 'warn', 'Kein Favicon', 'Ohne Favicon fehlt das kleine Wiedererkennungs-Symbol in Browser-Tabs, Lesezeichen und Google-Resultaten.', 'low', 'low');

    return $out;
}

// =========================================================================
// Orchestrierung: Crawl -> Checks -> Scores -> LLM-Texte
// =========================================================================
function uc_analyze(string $url, bool $withPsi = false, string $llmMode = 'teaser'): array
{
    $crawl = uc_crawl($url);
    if (isset($crawl['error'])) {
        return $crawl; // ['error' => code, 'message' => …]
    }

    $findings = [
        'technik' => uc_checks_technik($crawl),
        'seo' => uc_checks_seo($crawl),
        'performance' => uc_checks_performance($crawl),
        'inhalt' => uc_checks_inhalt($crawl),
        'ux' => uc_checks_ux($crawl),
    ];
    $scores = uc_score_categories($findings);
    $overall = uc_score_overall($scores);

    $domain = (string)(parse_url((string)$crawl['home']['url'], PHP_URL_HOST) ?: parse_url($url, PHP_URL_HOST));
    $domain = preg_replace('/^www\./', '', strtolower($domain));

    // Kontext fuer Texte und Report: Branche, Marke/Ort, Farbpalette (V2)
    $industry = uc_detect_industry($crawl);
    $brand = uc_detect_brand($crawl);
    $palette = uc_palette_from_crawl($crawl);
    $ctx = ['industry' => $industry, 'brand' => $brand, 'palette' => $palette];

    // LLM-Texte je nach Phase: 'teaser' (Free-Check, kurzer Output = schnell),
    // 'full' (Report: Teaser + Volltext), 'none' (nur Befunde, Text kommt später).
    // Fehlgeschlagener LLM-Call -> Fallback-Templates (Determinismus bleibt).
    $llm = ['teaser' => null, 'full' => null, 'leseprobe' => null, 'farbeindruck' => null];
    $llmUsed = false;
    if ($llmMode === 'full') {
        $res = uc_llm_full_analysis($domain, $scores, $overall, $findings, (string)$crawl['text_excerpt'], $ctx);
        if ($res !== null) {
            $llm = [
                'teaser' => $res['teaser'],
                'full' => $res['full'],
                'leseprobe' => $res['leseprobe'] ?? null,
                'farbeindruck' => $res['farbeindruck'] ?? null,
            ];
            $llmUsed = true;
        }
    } elseif ($llmMode === 'teaser') {
        $t = uc_llm_teaser($domain, $scores, $overall, $findings, (string)$crawl['text_excerpt'], $ctx);
        if ($t !== null) {
            $llm['teaser'] = $t;
            $llmUsed = true;
        }
    }
    if (($llmMode === 'teaser' && $llm['teaser'] === null)
        || ($llmMode === 'full' && ($llm['teaser'] === null || $llm['full'] === null))) {
        $fb = uc_llm_fallback($domain, $scores, $overall, $findings);
        $llm['teaser'] = $llm['teaser'] ?? $fb['teaser'];
        if ($llmMode === 'full') {
            $llm['full'] = $llm['full'] ?? $fb['full'];
        }
    }

    $analysis = [
        'url' => (string)$crawl['home']['url'],
        'domain' => $domain,
        'scores' => $scores,
        'overall' => $overall,
        'findings' => $findings,
        'llm' => $llm,
        'llm_used' => $llmUsed,
        'pages_crawled' => (int)$crawl['pages_crawled'],
        'text_excerpt' => (string)$crawl['text_excerpt'],
        'industry' => $industry,
        'brand' => $brand,
        'palette' => $palette,
        'analyzed_at' => date('c'),
    ];

    if ($withPsi && !uc_deadline_close(22)) {
        $psi = uc_psi_fetch((string)$crawl['home']['url'], true);
        if ($psi !== null) {
            $analysis['psi'] = $psi; // Labormessung (Lighthouse): separater Block im Report, nicht in der Note
        }
    }

    return $analysis;
}
