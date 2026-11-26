<?php
// Textqualitaet, Branchen-/Marken-Erkennung, Farbpalette (V2, 2026-11-11).
// Reine Hilfsfunktionen ohne Seiteneffekte; wird von checks.php eingebunden.

declare(strict_types=1);

// ---- Sichtbarer Text -> Statistiken (Lesbarkeit, Stuffing, Floskeln) ----
// Rueckgabe: words, sentences, asl (Wörter/Satz), asw (Silben/Wort),
// fre (Amstad/Flesch-DE, hoeher = leichter), top_term, top_density,
// lexdiv (lexikalische Vielfalt), floskel_hits (Liste), concrete (Zahl konkreter Signale)
function uc_text_stats(string $text, array $parts = []): array
{
    $text = trim((string)preg_replace('/\s+/u', ' ', $text));
    $out = [
        'words' => 0, 'sentences' => 0, 'asl' => 0.0, 'asw' => 0.0, 'fre' => 0.0,
        'top_term' => '', 'top_density' => 0.0, 'lexdiv' => 1.0,
        'floskel_hits' => [], 'concrete' => 0,
    ];
    if ($text === '') {
        return $out;
    }
    preg_match_all('/[\p{L}\p{N}]+/u', $text, $wm);
    $words = $wm[0];
    $n = count($words);
    if ($n === 0) {
        return $out;
    }
    // Saetze chunk-weise zaehlen: UI-Fragmente (Buttons, Karten, Headlines) ohne
    // Schlusszeichen zaehlen als eigene kurze Einheit, sonst wuerden sie per
    // Leerschlag zu Pseudo-Langsaetzen verschmelzen (Verzerrung 2026-11-11).
    if ($parts !== []) {
        $sentences = 0;
        foreach ($parts as $chunk) {
            $chunk = trim((string)preg_replace('/\s+/u', ' ', (string)$chunk));
            if ($chunk === '') {
                continue;
            }
            $sc = (int)preg_match_all('/[.!?…]+(?:\s|$)/u', $chunk);
            $sentences += max(1, $sc);
        }
        $sentences = max(1, $sentences);
    } else {
        $sentences = max(1, (int)preg_match_all('/[.!?…]+(?:\s|$)/u', $text));
    }
    $syll = 0;
    foreach ($words as $w) {
        // Silben-Schaetzung fuer Deutsch: Vokalgruppen zaehlen
        $syll += max(1, (int)preg_match_all('/[aeiouyäöü]+/iu', $w));
    }
    $asl = $n / $sentences;
    $asw = $syll / $n;
    $fre = 180 - $asl - (58.5 * $asw); // Amstad-Adaption des Flesch-Index fuer Deutsch

    $stop = array_flip([
        'der', 'die', 'das', 'den', 'dem', 'des', 'und', 'oder', 'aber', 'mit', 'ohne',
        'für', 'auf', 'aus', 'ist', 'sind', 'war', 'waren', 'wird', 'werden', 'wurde',
        'ein', 'eine', 'einer', 'eines', 'einen', 'einem', 'zu', 'zum', 'zur', 'von', 'vom',
        'im', 'in', 'am', 'an', 'als', 'auch', 'bei', 'beim', 'nach', 'wie', 'wir', 'sie',
        'es', 'ich', 'du', 'er', 'man', 'nicht', 'kein', 'keine', 'keinen', 'sich', 'uns',
        'ihre', 'ihr', 'ihren', 'ihn', 'ihm', 'ihnen', 'dies', 'diese', 'dieser', 'dieses',
        'diesem', 'diesen', 'dass', 'wenn', 'hat', 'haben', 'kann', 'können', 'mehr',
        'sehr', 'so', 'nur', 'noch', 'über', 'unter', 'bis', 'seit', 'weil', 'denn',
        'hier', 'dort', 'alle', 'alles', 'jede', 'jeder', 'jedes', 'unser', 'unsere',
        'unserer', 'unserem', 'unseren', 'euer', 'immer', 'schon', 'oft', 'viel', 'viele',
        'gut', 'gute', 'neue', 'neuen', 'grosse', 'grosser', 'kleine', 'wurden', 'worden',
        'soll', 'sollte', 'muss', 'müssen', 'darf', 'dürfen', 'will', 'wollen', 'hab',
    ]);
    $freq = [];
    $unique = [];
    foreach ($words as $w) {
        $lw = mb_strtolower($w);
        if (mb_strlen($lw) < 3 || isset($stop[$lw]) || is_numeric($lw)) {
            continue;
        }
        $unique[$lw] = true;
        $freq[$lw] = ($freq[$lw] ?? 0) + 1;
    }
    if ($freq !== []) {
        arsort($freq);
        $top = (string)array_key_first($freq);
        $out['top_term'] = $top;
        $out['top_density'] = $freq[$top] / $n;
    }
    $out['lexdiv'] = $n > 0 ? count($unique) / $n : 1.0;

    // Floskel-Liste: KI- und Werbe-Phrasen, die Uftritt selbst meidet (Voice-Blacklist)
    $floskeln = [
        'wir sind ihr', 'kompetenter partner', 'partner rund um', 'alles aus einer hand',
        'massgeschneidert', 'ganzheitlich', 'höchste qualität', 'jahrelange erfahrung',
        'langjährige erfahrung', 'individuelle lösung', 'innovative lösung', 'kundenorientiert',
        'herzlich willkommen', 'in der heutigen zeit', 'zögern sie nicht', 'noch heute',
        'nehmen sie kontakt', 'stehen wir ihnen', 'gerne beraten', 'rundum', 'einzigartig',
        'erstklassig', 'führend', 'leidenschaft für', 'nahtlos', 'exzellent', 'hochwertig',
        'auf dem neuesten stand', 'fit für die zukunft', 'in puncto',
    ];
    $haystack = ' ' . mb_strtolower($text) . ' ';
    $hits = [];
    foreach ($floskeln as $f) {
        if (mb_strpos($haystack, $f) !== false) {
            $hits[] = $f;
        }
    }
    $out['floskel_hits'] = $hits;

    // Konkretheit: Zahlen, Preise, Orte (PLZ) im Text
    $concrete = 0;
    $concrete += (int)preg_match_all('/\b\d+([.,]\d+)?\s*(%|fr\.?|chf|jahre|jahren|minuten|stunden|km|m2|m²)\b/iu', $text);
    $concrete += (int)preg_match_all('/\b[1-9]\d{3}\s+[A-ZÄÖÜ]/u', $text); // PLZ + Ort
    $out['concrete'] = $concrete;

    $out['words'] = $n;
    $out['sentences'] = $sentences;
    $out['asl'] = $asl;
    $out['asw'] = $asw;
    $out['fre'] = $fre;
    return $out;
}

// ---- Branchen-Erkennung: JSON-LD @type zuerst, dann Stichworte im Text ----
function uc_detect_industry(array $crawl): ?string
{
    static $typeMap = [
        'Restaurant' => 'Gastro', 'FoodEstablishment' => 'Gastro', 'Bakery' => 'Gastro/Bäckerei',
        'CafeOrCoffeeShop' => 'Gastro/Café', 'BarOrPub' => 'Gastro/Bar',
        'HairSalon' => 'Coiffeur', 'BeautySalon' => 'Beauty/Kosmetik', 'NailSalon' => 'Beauty/Nägel',
        'Dentist' => 'Zahnarztpraxis', 'MedicalBusiness' => 'Arzt-/Therapiepraxis',
        'Physician' => 'Arztpraxis', 'PhysicalTherapy' => 'Physiotherapie',
        'LegalService' => 'Recht/Notariat', 'Attorney' => 'Rechtsanwalt',
        'AccountingService' => 'Treuhand/Buchhaltung', 'FinancialService' => 'Finanzdienstleistung',
        'AutoRepair' => 'Garage/Carrosserie', 'AutoDealer' => 'Autohandel',
        'HomeAndConstructionBusiness' => 'Bau/Handwerk', 'GeneralContractor' => 'Bau/Handwerk',
        'Electrician' => 'Elektro', 'Plumber' => 'Sanitär', 'RoofingContractor' => 'Dachdecker',
        'RealEstateAgent' => 'Immobilien', 'Store' => 'Detailhandel',
        'ExerciseGym' => 'Fitness', 'SportsClub' => 'Sportverein',
        'School' => 'Bildung', 'EducationalOrganization' => 'Bildung/Coaching',
        'Hotel' => 'Hotellerie', 'LodgingBusiness' => 'Hotellerie/Tourismus',
        'ProfessionalService' => 'Dienstleistung', 'LocalBusiness' => 'lokaler Betrieb',
    ];
    $xp = $crawl['home_doc']['xpath'] ?? null;
    if ($xp instanceof DOMXPath) {
        foreach (uc_nodes($xp, "//script[@type='application/ld+json']") as $s) {
            $j = json_decode(trim((string)$s->textContent), true);
            if (!is_array($j)) {
                continue;
            }
            $stack = [$j];
            while ($stack !== []) {
                $node = array_pop($stack);
                if (!is_array($node)) {
                    continue;
                }
                $t = $node['@type'] ?? null;
                foreach (is_array($t) ? $t : [$t] as $tt) {
                    if (is_string($tt) && isset($typeMap[$tt])) {
                        return $typeMap[$tt];
                    }
                }
                foreach ($node as $v) {
                    if (is_array($v)) {
                        $stack[] = $v;
                    }
                }
            }
        }
    }
    // Stichwort-Karte auf Titel + Textauszug
    $hay = mb_strtolower((string)($crawl['text_excerpt'] ?? ''));
    $xp2 = $crawl['home_doc']['xpath'] ?? null;
    if ($xp2 instanceof DOMXPath) {
        foreach (uc_nodes($xp2, '//title[1]') as $t) {
            $hay .= ' ' . mb_strtolower((string)$t->nodeValue);
        }
    }
    $kw = [
        'coiffeur|frisör' => 'Coiffeur',
        'physio|osteopathie|therapie|massage|praxis' => 'Praxis/Therapie',
        'zahnarzt' => 'Zahnarztpraxis',
        'treuhand|buchhaltung|steuerberatung' => 'Treuhand/Buchhaltung',
        'garage|carrosserie|fahrzeug' => 'Garage/Fahrzeug',
        'restaurant|pizzeria|beiz|café|bäckerei|konditorei|metzgerei' => 'Gastro',
        'immobilien|liegenschaft' => 'Immobilien',
        'anwalt|notariat|rechtsberatung' => 'Recht',
        'schreinerei|holzbau|zimmerei|gipser|maurer|elektro|sanitär|heizung|gartenbau|landschaftsbau|dachdecker|maler' => 'Bau/Handwerk',
        'coaching|kurse|training|lernen|schulung|weiterbildung' => 'Bildung/Coaching',
        'kosmetik|nagelstudio' => 'Beauty/Kosmetik',
        'architektur|architekt' => 'Architektur',
        'fotografie|fotograf' => 'Fotografie',
        'hotel|ferienwohnung|ferienhaus' => 'Hotellerie/Tourismus',
        'verein' => 'Verein',
        'transport|umzug|logistik' => 'Transport/Logistik',
        'reinigung' => 'Reinigung',
        'gärtnerei|floristik' => 'Gärtnerei/Floristik',
    ];
    foreach ($kw as $pattern => $label) {
        if (preg_match('/' . $pattern . '/iu', $hay)) {
            return $label;
        }
    }
    return null;
}

// ---- Marke + Ort aus den gecrawlten Seiten (Impressum/Kontakt) ----
function uc_detect_brand(array $crawl): array
{
    $out = ['name' => '', 'place' => ''];
    $xp = $crawl['home_doc']['xpath'] ?? null;
    if ($xp instanceof DOMXPath) {
        foreach (uc_nodes($xp, "//meta[@property='og:site_name']/@content") as $c) {
            $v = trim((string)$c->nodeValue);
            if ($v !== '' && mb_strlen($v) <= 60) {
                $out['name'] = $v;
                break;
            }
        }
    }
    // PLZ + Ort auf Home + Unterseiten suchen
    $docs = array_merge(['__home' => $crawl['home_doc']], (array)($crawl['sub_docs'] ?? []));
    foreach ($docs as $d) {
        $px = $d['xpath'] ?? null;
        if (!$px instanceof DOMXPath) {
            continue;
        }
        $text = uc_visible_text($px);
        if (preg_match('/\b([1-9]\d{3})\s+([A-ZÄÖÜ][\p{L}äöüéàè\-]+)/u', $text, $m)) {
            $out['place'] = trim($m[2]);
            break;
        }
    }
    return $out;
}

// ---- Farbpalette aus Inline-Styles und <style>-Bloecken ----
// Rueckgabe: ['colors' => [['hex'=>'#014080','count'=>12], …], 'gradients' => bool]
function uc_extract_palette(string $html): array
{
    $css = '';
    if (preg_match_all('/<style[^>]*>(.*?)<\/style>/is', $html, $m)) {
        $css .= implode("\n", $m[1]);
    }
    if (preg_match_all('/\sstyle\s*=\s*"([^"]*)"/i', $html, $m)) {
        $css .= "\n" . implode("\n", $m[1]);
    }
    if (preg_match_all("/\\sstyle\\s*=\\s*'([^']*)'/i", $html, $m)) {
        $css .= "\n" . implode("\n", $m[1]);
    }
    $out = ['colors' => [], 'gradients' => stripos($css, 'gradient') !== false];
    if (trim($css) === '') {
        return $out;
    }
    $counts = [];
    if (preg_match_all('/#([0-9a-f]{3}|[0-9a-f]{6})\b/i', $css, $m)) {
        foreach ($m[1] as $hex) {
            $hex = strtolower($hex);
            if (strlen($hex) === 3) {
                $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
            }
            $counts['#' . $hex] = ($counts['#' . $hex] ?? 0) + 1;
        }
    }
    // Rein schwarz/weiss rausfiltern, sie sagen nichts ueber die Gestaltung
    unset($counts['#000000'], $counts['#ffffff']);
    arsort($counts);
    foreach (array_slice($counts, 0, 6, true) as $hex => $c) {
        $out['colors'][] = ['hex' => $hex, 'count' => $c];
    }
    return $out;
}

// ---- Palette ueber den Crawl: erst Inline-CSS, sonst bis 2 eigene Stylesheets nachladen ----
function uc_palette_from_crawl(array $crawl): array
{
    $home = (array)$crawl['home'];
    $palette = uc_extract_palette((string)($home['body'] ?? ''));
    if ($palette['colors'] !== []) {
        return $palette;
    }
    $xp = $crawl['home_doc']['xpath'] ?? null;
    if (!$xp instanceof DOMXPath || uc_deadline_close(6)) {
        return $palette;
    }
    $base = (string)($home['url'] ?? '');
    $baseHost = strtolower((string)parse_url($base, PHP_URL_HOST));
    $cssUrls = [];
    foreach (uc_nodes($xp, "//link[translate(@rel,'ABCDEFGHIJKLMNOPQRSTUVWXYZ','abcdefghijklmnopqrstuvwxyz')='stylesheet']/@href") as $l) {
        $abs = uc_resolve_relative($base, trim((string)$l->nodeValue));
        if ($abs === null) {
            continue;
        }
        // Eigene Domain zuerst, CDN-Stylesheets danach (Baukasten laden CSS oft extern).
        // uc_fetch_multi validiert jede URL selbst (Schema, Port, oeffentliche IP).
        $sameHost = strtolower((string)parse_url($abs, PHP_URL_HOST)) === $baseHost;
        $cssUrls[$abs] = $sameHost;
    }
    // erst gleiche Domain, dann externe, max. 2 insgesamt
    $pick = array_keys(array_filter($cssUrls));
    if (count($pick) < 2) {
        foreach ($cssUrls as $u => $same) {
            if ($same) {
                continue;
            }
            $pick[] = $u;
            if (count($pick) >= 2) {
                break;
            }
        }
    }
    $pick = array_slice($pick, 0, 2);
    if ($pick === []) {
        return $palette;
    }
    $res = uc_fetch_multi(array_combine($pick, $pick), 5, ['max_bytes' => 400000]);
    $css = '';
    foreach ($res as $r) {
        if (!empty($r['ok']) && (int)($r['status'] ?? 0) === 200) {
            $css .= "\n" . (string)($r['body'] ?? '');
        }
    }
    if (trim($css) === '') {
        return $palette;
    }
    return uc_extract_palette('<style>' . $css . '</style>');
}

// ---- Chrome UX Report (Felddaten echter Besucher) ----
// Nutzt denselben Google-API-Key wie PSI (Config psi_api_key). Schneller Call (<2 s),
// liefert p75 fuer LCP/INP/CLS oder null, wenn keine Daten/kein Key.
function uc_crux_fetch(string $url): ?array
{
    $key = trim((string)uc_config('psi_api_key'));
    if ($key === '') {
        return null;
    }
    $api = 'https://chromeuxreport.googleapis.com/v1/records:queryRecord?key=' . urlencode($key);
    $ch = curl_init($api);
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 6,
        CURLOPT_CONNECTTIMEOUT => 3,
        CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
        CURLOPT_POSTFIELDS => json_encode(['url' => $url], JSON_UNESCAPED_UNICODE),
    ]);
    $resp = curl_exec($ch);
    $status = (int)curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    curl_close($ch);
    if ($status !== 200 || !is_string($resp)) {
        return null;
    }
    $j = json_decode($resp, true);
    $metrics = $j['record']['metrics'] ?? null;
    if (!is_array($metrics)) {
        return null;
    }
    $read = static function (string $name) use ($metrics): ?float {
        $v = $metrics[$name]['percentiles']['p75'] ?? null;
        return is_numeric($v) ? (float)$v : null;
    };
    return [
        'lcp_ms' => $read('largest_contentful_paint'),
        'inp_ms' => $read('interaction_to_next_paint'),
        'cls' => $read('cumulative_layout_shift'),
    ];
}
