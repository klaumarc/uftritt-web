<?php
// GET /api/report-unlock.php?session_id=cs_… – Rücksprung von Stripe Checkout.
// Verifiziert die Zahlung serverseitig, erzeugt den Report (idempotent),
// schickt Mails und leitet auf die Report-Seite weiter.

declare(strict_types=1);

require __DIR__ . '/_lib/bootstrap.php';
require __DIR__ . '/_lib/store.php';
require __DIR__ . '/_lib/fetch.php';
require __DIR__ . '/_lib/checks.php';
require __DIR__ . '/_lib/score.php';
require __DIR__ . '/_lib/llm.php';
require __DIR__ . '/_lib/mail.php';
require __DIR__ . '/_lib/stripe.php';

uc_cleanup_maybe();

// Claim-Handle + Pfad für den atomaren Unlock-Claim (gebraucht im catch)
$claimed = null;
$unlockPath = '';

// Kleine Fehlerseite im Report-Stil (kein JSON – der Nutzer kommt aus dem Browser)
function uc_unlock_fail(string $msg, int $status = 400): void
{
    http_response_code($status);
    if (!headers_sent()) {
        header('Content-Type: text/html; charset=utf-8');
        header('X-Robots-Tag: noindex, nofollow');
        header('X-Content-Type-Options: nosniff');
    }
    $m = htmlspecialchars($msg, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    echo '<!doctype html><html lang="de-CH"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">'
        . '<meta name="robots" content="noindex, nofollow"><title>Zahlung · Website-Check uftritt.ch</title>'
        . '<style>body{margin:0;font-family:system-ui,sans-serif;color:#014080;background:#f5f7fa}'
        . '.band{background:#ffd23c;padding:.7rem 1.25rem;font-weight:700}'
        . '.card{max-width:640px;margin:3rem auto;background:#fff;padding:2rem;line-height:1.6}'
        . 'a{color:#014080;font-weight:700}</style></head><body>'
        . '<div class="band">uftritt.ch · Website-Check</div><div class="card"><p>' . $m . '</p>'
        . '<p><a href="/website-check/">Zurück zum Website-Check</a> · Fragen: <a href="mailto:info@uftritt.ch">info@uftritt.ch</a></p>'
        . '</div></body></html>';
    exit;
}

try {
    $sessionId = isset($_GET['session_id']) && is_string($_GET['session_id']) ? trim($_GET['session_id']) : '';
    $session = $sessionId !== '' ? uc_stripe_session_get($sessionId) : null;
    if ($session === null) {
        uc_unlock_fail('Dieser Zahlungslink ist ungültig oder abgelaufen.', 404);
    }

    if ((string)($session['payment_status'] ?? '') !== 'paid') {
        uc_unlock_fail('Die Zahlung ist (noch) nicht abgeschlossen. Falls du gerade bezahlt hast, lade diese Seite in einer Minute erneut – oder melde dich bei uns.', 402);
    }

    // Missbrauchsbremse: max. 60 Unlock-Aufrufe pro IP/Tag (Fake-ID-Dauerfeuer
    // würde sonst unlimitiert als GET an die Stripe-API weitergegeben)
    if (uc_ratelimit_hit('unlock_ip', uc_ip_hash(), 60) !== null) {
        uc_unlock_fail('Zu viele Anfragen von deinem Anschluss. Bitte versuche es morgen erneut.', 429);
    }

    // Atomarer Claim: nur EIN Request pro Session erzeugt Report + Mails.
    // fopen 'x' scheitert, wenn die Datei schon existiert (fertig oder in Arbeit) –
    // dann auf den parallelen Request warten und dessen Report verwenden.
    $unlockKey = hash('sha256', $sessionId);
    $unlockPath = uc_path('unlocks', $unlockKey . '.json');
    $claimed = @fopen($unlockPath, 'x');
    if ($claimed === false) {
        for ($i = 0; $i < 35; $i++) {
            $existing = uc_json_read($unlockPath);
            if (is_array($existing) && !empty($existing['report_id'])) {
                header('Location: /api/report.php?id=' . rawurlencode((string)$existing['report_id']), true, 303);
                exit;
            }
            sleep(1);
            clearstatcache(true, $unlockPath);
        }
        uc_unlock_fail('Dein Report wird gerade erstellt. Bitte lade diese Seite in einer Minute erneut.', 409);
    }

    $meta = (array)($session['metadata'] ?? []);
    $roastId = (string)($meta['roast_id'] ?? '');
    $url = (string)($meta['url'] ?? '');

    // Analyse: Roast (< 1 h) -> URL-Cache (24 h) -> frisch (mode none, Volltext folgt hier)
    $analysis = null;
    if ($roastId !== '') {
        $roast = uc_roast_get($roastId);
        if (is_array($roast) && !empty($roast['analysis'])) {
            $analysis = $roast['analysis'];
            // Die Roast-URL hat Vorrang vor der mitgeschickten (Vertrauensgrenze)
            $url = (string)($roast['url'] ?? '') !== '' ? (string)$roast['url'] : $url;
        }
    }
    $norm = $url !== '' ? uc_normalize_url($url) : null;
    if ($norm === null) {
        uc_unlock_fail('Zur Zahlung fehlt die Website-Angabe. Bitte melde dich bei uns – wir lösen das.', 400);
    }
    if ($analysis === null) {
        $cache = uc_cache_get($norm);
        if ($cache !== null && !empty($cache['analysis'])) {
            $analysis = $cache['analysis'];
        }
    }
    if ($analysis === null) {
        $analysis = uc_analyze($norm, false, 'none');
        if (isset($analysis['error'])) {
            // Fehlgeschlagen: Claim freigeben, damit ein erneuter Versuch möglich bleibt
            fclose($claimed);
            $claimed = null;
            @unlink($unlockPath);
            uc_unlock_fail('Deine Website konnte gerade nicht analysiert werden. Die Zahlung ist erhalten – schreib uns kurz an, wir liefern den Report manuell nach.', 502);
        }
    }

    // Volltext sicherstellen (LLM, sonst Fallback) – gleiche Logik wie beim Gratis-Flow
    if (empty($analysis['llm']['full'])) {
        $full = uc_llm_full_analysis(
            (string)$analysis['domain'],
            (array)$analysis['scores'],
            (array)$analysis['overall'],
            (array)$analysis['findings'],
            (string)($analysis['text_excerpt'] ?? '')
        );
        if ($full !== null) {
            $analysis['llm']['full'] = $full['full'];
            if (empty($analysis['llm']['teaser'])) {
                $analysis['llm']['teaser'] = $full['teaser'];
            }
            $analysis['llm_used'] = true;
        } else {
            $fb = uc_llm_fallback(
                (string)$analysis['domain'],
                (array)$analysis['scores'],
                (array)$analysis['overall'],
                (array)$analysis['findings']
            );
            $analysis['llm']['full'] = $fb['full'];
            if (empty($analysis['llm']['teaser'])) {
                $analysis['llm']['teaser'] = $fb['teaser'];
            }
        }
    }
    // Cache unter der tatsächlichen Analyse-URL schreiben (nie unter einer
    // abweichend mitgeschickten URL – sonst Cache-Poisoning möglich)
    uc_cache_set((string)$analysis['url'], $analysis);

    // Report speichern + Idempotenz-Marke
    $reportId = bin2hex(random_bytes(16));
    $report = [
        'id' => $reportId,
        'created' => date('c'),
        'url' => $analysis['url'],
        'domain' => $analysis['domain'],
        'scores' => $analysis['scores'],
        'overall' => $analysis['overall'],
        'findings' => $analysis['findings'],
        'llm' => $analysis['llm'],
        'llm_used' => (bool)($analysis['llm_used'] ?? false),
        'pages_crawled' => (int)($analysis['pages_crawled'] ?? 1),
        'psi' => $analysis['psi'] ?? null,
        'paid' => true,
    ];
    uc_report_save($reportId, $report);
    // Claim mit der fertigen Report-ID füllen (parallele Requests lesen sie hier)
    fwrite($claimed, json_encode(['ts' => time(), 'report_id' => $reportId]));
    fflush($claimed);
    fclose($claimed);
    $claimed = null;
    @chmod($unlockPath, 0640);

    $reportUrlAbs = $GLOBALS['UC']['base_url'] . '/api/report.php?id=' . $reportId;

    // Lead erfassen (Kauf): E-Mail kommt von der Stripe-Session (Receipt-Adresse)
    $email = trim((string)($session['customer_details']['email'] ?? ''));
    $lead = [
        'ts' => date('c'),
        'type' => 'sale',
        'email' => $email,
        'url' => $analysis['url'],
        'overall' => $analysis['overall'],
        'scores' => $analysis['scores'],
        'report_url' => $reportUrlAbs,
        'amount_rappen' => (int)($session['amount_total'] ?? UC_PRICE_CHF_RAPPEN),
        'currency' => (string)($session['currency'] ?? 'chf'),
        'stripe_session' => $sessionId,
        'env' => $GLOBALS['UC']['env'],
    ];
    uc_lead_append($lead);

    // Mails – ein Versandproblem bricht den Unlock nicht, der Report-Link gilt trotzdem
    $mailLead = $email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL)
        ? uc_mail_report($email, $report, $reportUrlAbs)
        : false;
    $mailIntern = uc_mail_sale($lead, $reportUrlAbs);
    if (!$mailLead || !$mailIntern) {
        error_log('website-check unlock: mail-Problem (lead=' . (int)$mailLead . ', intern=' . (int)$mailIntern . ')');
    }

    header('Location: /api/report.php?id=' . $reportId, true, 303);
    exit;
} catch (Throwable $e) {
    // Claim gehalten, aber kein Report fertig geworden: freigeben für Retries
    if ($claimed) {
        fclose($claimed);
        @unlink($unlockPath);
    }
    error_log('website-check unlock: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
    uc_unlock_fail('Da ist auf unserer Seite etwas schiefgelaufen. Falls du bereits bezahlt hast, schreib uns kurz an – wir liefern den Report nach.', 502);
}
