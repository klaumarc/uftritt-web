<?php
// Moonshot-Client (OpenAI-kompatibel) + Fallback-Texte bei LLM-Ausfall.
// WICHTIG: Ans LLM gehen nur Analyse-Daten (Domain, Scores, Findings, Textauszug).
// Die Lead-E-Mail und andere Personendaten werden NIE mitgesendet.

declare(strict_types=1);

// Rückgabe: ['teaser' => …, 'full' => […], 'leseprobe' => ?[…], 'farbeindruck' => ?string] oder null
// $ctx: ['industry' => ?string, 'brand' => ['name','place'], 'palette' => ['colors'=>…]] (keine Personendaten!)
function uc_llm_full_analysis(string $domain, array $scores, array $overall, array $findingsByCat, string $textExcerpt, array $ctx = []): ?array
{
    if (!uc_config('llm_enabled')) {
        return null;
    }
    if (uc_deadline_close(40.0)) {
        return null; // Volltext braucht ~25–35 s (Reasoning) – lieber sauberer Fallback als Timeout
    }

    $body = [
        'model' => (string)uc_config('moonshot_model'),
        'messages' => [
            ['role' => 'system', 'content' => uc_llm_system_prompt()],
            ['role' => 'user', 'content' => uc_llm_payload($domain, $scores, $overall, $findingsByCat, $textExcerpt, $ctx)],
        ],
        'temperature' => (float)uc_config('moonshot_temperature'),
        'reasoning_effort' => (string)(uc_config('moonshot_reasoning') ?? 'low'),
        'max_tokens' => (int)(uc_config('moonshot_max_tokens') ?? 4000),
        'response_format' => ['type' => 'json_object'],
    ];

    $resp = uc_llm_request($body);
    // Manche Modelle lehnen den temperature-Parameter ab -> einmal ohne wiederholen
    if (($resp['status'] ?? 0) === 400 && stripos((string)$resp['body'], 'temperature') !== false) {
        unset($body['temperature']);
        $resp = uc_llm_request($body);
    }
    if (($resp['status'] ?? 0) !== 200) {
        return null;
    }
    $data = json_decode((string)$resp['body'], true);
    $content = $data['choices'][0]['message']['content'] ?? null;
    if (!is_string($content) || trim($content) === '') {
        return null;
    }
    return uc_llm_parse($content);
}

// Roher POST an die Moonshot-API (fester, vertrauenswürdiger Endpunkt – kein SSRF-Thema)
// Restbudget für den LLM-Call: nie mehr als 38 s, immer 4 s Reserve fürs Antworten.
function uc_llm_budget(): float
{
    return max(0.0, min(38.0, uc_time_left() - 4.0));
}

function uc_llm_request(array $body, ?float $timeout = null): array
{
    $timeout = max(5.0, $timeout ?? uc_llm_budget());
    $ch = curl_init('https://api.moonshot.ai/v1/chat/completions');
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => (int)ceil($timeout), // Reasoning-Modelle brauchen auch mit effort=low ~25 s
        CURLOPT_CONNECTTIMEOUT => 5,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . (string)uc_config('moonshot_api_key'),
        ],
        CURLOPT_POSTFIELDS => json_encode($body, JSON_UNESCAPED_UNICODE),
    ]);
    $resp = curl_exec($ch);
    $status = (int)curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    curl_close($ch);
    return ['status' => $status, 'body' => is_string($resp) ? $resp : ''];
}

// Tolerantes Parsen: Code-Fences strippen, erstes { … letztes } als JSON lesen
function uc_llm_parse(string $content): ?array
{
    $c = trim($content);
    $c = (string)preg_replace('/^```(?:json)?\s*|\s*```$/mi', '', $c);
    $start = strpos($c, '{');
    $end = strrpos($c, '}');
    if ($start === false || $end === false || $end <= $start) {
        return null;
    }
    $j = json_decode(substr($c, $start, $end - $start + 1), true);
    if (!is_array($j)) {
        return null;
    }
    $teaser = isset($j['teaser']) && is_string($j['teaser']) ? trim($j['teaser']) : '';
    $full = $j['full'] ?? null;
    if ($teaser === '' || !is_array($full)) {
        return null;
    }
    $pc = [];
    foreach (uc_category_order() as $cat) {
        $v = $full['per_category'][$cat] ?? '';
        $pc[$cat] = is_string($v) ? trim($v) : '';
    }
    // V2: optionale Felder (fehlen sie, rendert der Report die Boxen einfach nicht)
    $leseprobe = null;
    $lp = $j['leseprobe'] ?? null;
    if (is_array($lp)) {
        $lpScore = (int)($lp['score'] ?? 0);
        $lpKommentar = is_string($lp['kommentar'] ?? null) ? trim($lp['kommentar']) : '';
        $lpZitat = is_string($lp['zitat'] ?? null) ? trim($lp['zitat']) : '';
        if ($lpScore >= 1 && $lpScore <= 5 && $lpKommentar !== '') {
            $leseprobe = ['score' => $lpScore, 'kommentar' => $lpKommentar, 'zitat' => $lpZitat];
        }
    }
    $farbeindruck = null;
    if (isset($j['farbeindruck']) && is_string($j['farbeindruck']) && trim($j['farbeindruck']) !== '') {
        $farbeindruck = trim($j['farbeindruck']);
    }
    return [
        'teaser' => $teaser,
        'full' => [
            'intro' => is_string($full['intro'] ?? null) ? trim($full['intro']) : '',
            'per_category' => $pc,
            'closing' => is_string($full['closing'] ?? null) ? trim($full['closing']) : '',
        ],
        'leseprobe' => $leseprobe,
        'farbeindruck' => $farbeindruck,
    ];
}

// System-Prompt aus Datei (versionierbar), Fallback inline
function uc_llm_system_prompt(): string
{
    static $prompt = null;
    if ($prompt === null) {
        $file = __DIR__ . '/prompt-roast.txt';
        $prompt = is_file($file) ? trim((string)file_get_contents($file)) : '';
        if ($prompt === '') {
            $prompt = 'Du bist ein ehrlicher, konstruktiver Website-Berater der Schweizer Marketingagentur Uftritt. '
                . 'Schreibe Schweizer Standarddeutsch (ss statt ß), «…», Du-Form. Antworte ausschliesslich als JSON.';
        }
    }
    return $prompt;
}

// Gemeinsame User-Payload für beide LLM-Calls (nur Analyse-Daten, nie Personendaten)
function uc_llm_payload(string $domain, array $scores, array $overall, array $findingsByCat, string $textExcerpt, array $ctx = []): string
{
    $findings = [];
    foreach (uc_category_order() as $cat) {
        foreach ($findingsByCat[$cat] ?? [] as $f) {
            if (($f['status'] ?? 'ok') === 'ok') {
                continue;
            }
            $findings[] = [
                'category' => $cat,
                'status' => $f['status'],
                'title' => $f['title'],
                'detail' => $f['detail'],
                'impact' => $f['impact'],
                'effort' => $f['effort'],
            ];
        }
    }
    $findings = array_slice($findings, 0, 40);

    $payload = [
        'domain' => $domain,
        'scores' => $scores,
        'overall' => $overall,
        'findings' => $findings,
        'text_excerpt' => mb_substr($textExcerpt, 0, 2000),
    ];
    // Kontext nur mitsenden, wenn erkannt (kein erfundener Branchenbezug)
    if (!empty($ctx['industry'])) {
        $payload['industry'] = (string)$ctx['industry'];
    }
    $brand = (array)($ctx['brand'] ?? []);
    if (trim((string)($brand['name'] ?? '')) !== '' || trim((string)($brand['place'] ?? '')) !== '') {
        $payload['brand'] = [
            'name' => (string)($brand['name'] ?? ''),
            'place' => (string)($brand['place'] ?? ''),
        ];
    }
    $palette = (array)($ctx['palette'] ?? []);
    $colors = (array)($palette['colors'] ?? []);
    if ($colors !== []) {
        $hexes = [];
        foreach (array_slice($colors, 0, 6) as $c) {
            if (is_array($c) && isset($c['hex'])) {
                $hexes[] = (string)$c['hex'];
            }
        }
        if ($hexes !== []) {
            $payload['palette'] = ['colors' => $hexes, 'gradients' => !empty($palette['gradients'])];
        }
    }

    return json_encode($payload, JSON_UNESCAPED_UNICODE);
}

// Nur den kurzen Teaser generieren (Phase 1, Free-Check) – kleiner Output = schnell.
// Rückgabe: Teaser-String oder null (Fallback greift dann).
function uc_llm_teaser(string $domain, array $scores, array $overall, array $findingsByCat, string $textExcerpt, array $ctx = []): ?string
{
    if (!uc_config('llm_enabled')) {
        return null;
    }
    if (uc_deadline_close(16.0)) {
        return null; // Teaser braucht ~8–15 s – bei knappem Restbudget sofort Fallback
    }

    $body = [
        'model' => (string)uc_config('moonshot_model'),
        'messages' => [
            ['role' => 'system', 'content' => uc_llm_teaser_prompt()],
            ['role' => 'user', 'content' => uc_llm_payload($domain, $scores, $overall, $findingsByCat, $textExcerpt, $ctx)],
        ],
        'temperature' => (float)uc_config('moonshot_temperature'),
        'reasoning_effort' => (string)(uc_config('moonshot_reasoning') ?? 'low'),
        'max_tokens' => 1200,
        'response_format' => ['type' => 'json_object'],
    ];

    $resp = uc_llm_request($body, min(18.0, uc_llm_budget()));
    if (($resp['status'] ?? 0) === 400 && stripos((string)$resp['body'], 'temperature') !== false) {
        unset($body['temperature']);
        $resp = uc_llm_request($body, min(18.0, uc_llm_budget()));
    }
    if (($resp['status'] ?? 0) !== 200) {
        return null;
    }
    $data = json_decode((string)$resp['body'], true);
    $content = $data['choices'][0]['message']['content'] ?? null;
    if (!is_string($content) || trim($content) === '') {
        return null;
    }
    $c = trim((string)preg_replace('/^```(?:json)?\s*|\s*```$/mi', '', trim($content)));
    $start = strpos($c, '{');
    $end = strrpos($c, '}');
    if ($start === false || $end === false || $end <= $start) {
        return null;
    }
    $j = json_decode(substr($c, $start, $end - $start + 1), true);
    $teaser = is_array($j) && isset($j['teaser']) && is_string($j['teaser']) ? trim($j['teaser']) : '';
    return $teaser !== '' ? $teaser : null;
}

// Teaser-System-Prompt aus Datei, Fallback inline
function uc_llm_teaser_prompt(): string
{
    static $prompt = null;
    if ($prompt === null) {
        $file = __DIR__ . '/prompt-teaser.txt';
        $prompt = is_file($file) ? trim((string)file_get_contents($file)) : '';
        if ($prompt === '') {
            $prompt = 'Du bist der Website-Berater der Uftritt GmbH (Schweizer Marketingagentur). '
                . 'Kommentiere die Check-Ergebnisse eines KMU: Schweizer Standarddeutsch (ss statt ß), «…», Du-Form, '
                . 'Dezimalzahlen mit Komma. Ton: ehrlich, konstruktiv, augenzwinkernd, nie beleidigend. '
                . 'Kommentiere NUR die Befunde im JSON, erfinde nichts dazu. '
                . 'Antworte ausschliesslich als JSON: {"teaser": "2–4 Sätze: Gesamteindruck mit Score und Note, die zwei grössten Hebel, eine ehrliche positive Feststellung."}';
        }
    }
    return $prompt;
}

// =========================================================================
// Fallback-Texte ohne LLM: vorgefertigte Templates, Befund-Titel eingewebt
// =========================================================================
function uc_llm_fallback(string $domain, array $scores, array $overall, array $findingsByCat): array
{
    $labels = uc_category_labels();

    // Kategorien nach Score sortieren (schwächste zuerst)
    $byScore = uc_category_order();
    usort($byScore, static function (string $a, string $b) use ($scores): int {
        return ((float)($scores[$a] ?? 0)) <=> ((float)($scores[$b] ?? 0));
    });
    $worst1 = $labels[$byScore[0]];
    $worst2 = $labels[$byScore[1]];
    $best = $labels[end($byScore)];

    $scoreFmt = number_format((float)$overall['score'], 1, ',', '');

    if ((float)$overall['score'] >= 8.5) {
        $teaser = '«' . $domain . '» macht insgesamt einen starken Eindruck: ' . $scoreFmt . ' von 10 Punkten (Note ' . $overall['grade'] . '). '
            . 'Am ehesten ist noch bei ' . $worst1 . ' etwas zu holen. Gute Basis, jetzt geht es um Feinschliff.';
    } else {
        $teaser = '«' . $domain . '» erreicht ' . $scoreFmt . ' von 10 Punkten (Note ' . $overall['grade'] . '). '
            . 'Den grössten Hebel bieten ' . $worst1 . ' und ' . $worst2 . '. '
            . $best . ' ist dagegen bereits solide aufgestellt.';
    }

    $intro = 'Dieser Report fasst den Zustand von «' . $domain . '» zusammen: Gesamtnote ' . $overall['grade']
        . ' (' . $scoreFmt . '/10) über die fünf Kategorien Technik, SEO, Performance, Inhalt und UX. '
        . 'Die Befunde sind priorisiert: Wer oben anfängt, holt am schnellsten Wirkung heraus.';

    $perCategory = [];
    foreach (uc_category_order() as $cat) {
        $titles = [];
        foreach ($findingsByCat[$cat] ?? [] as $f) {
            if (($f['status'] ?? 'ok') === 'bad') {
                $titles[] = $f['title'];
            }
        }
        foreach ($findingsByCat[$cat] ?? [] as $f) {
            if (($f['status'] ?? 'ok') === 'warn') {
                $titles[] = $f['title'];
            }
        }
        $titles = array_slice($titles, 0, 3);
        $score = (float)($scores[$cat] ?? 0);
        $label = $labels[$cat];
        if ($titles === []) {
            $perCategory[$cat] = 'In dieser Kategorie haben wir nichts Beanstandenswertes gefunden, ' . $label . ' ist sauber umgesetzt. Weiter so.';
        } else {
            $list = '«' . implode('», «', $titles) . '»';
            if ($score >= 8.0) {
                $perCategory[$cat] = $label . ' steht auf soliden Beinen. Feinschliff ist noch möglich bei: ' . $list . '.';
            } elseif ($score >= 5.5) {
                $perCategory[$cat] = $label . ' ist grundsätzlich in Ordnung, verliert aber Punkte bei: ' . $list . '. Das ist mit überschaubarem Aufwand korrigierbar.';
            } else {
                $perCategory[$cat] = 'Hier liegt der grösste Hebel: ' . $list . '. Diese Punkte sollten zuerst angepackt werden.';
            }
        }
    }

    $closing = 'Die volle Wirkung steckt im Zusammenspiel: Technik, Sichtbarkeit und Inhalt gemeinsam verbessern. '
        . 'In einer kurzen Besprechung zeigen wir dir, welche Massnahmen sich für «' . $domain . '» zuerst lohnen.';

    return [
        'teaser' => $teaser,
        'full' => [
            'intro' => $intro,
            'per_category' => $perCategory,
            'closing' => $closing,
        ],
        'leseprobe' => null,
        'farbeindruck' => null,
    ];
}
