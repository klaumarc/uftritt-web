<?php
// Gemeinsames Lead-Log für die Formular-Handler (kontakt.php, rueckruf.php,
// besprechung.php). Schreibt jede Submission als JSON-Zeile ausserhalb des
// Webroots mit, damit kein Lead verloren geht, falls mail() scheitert oder
// die Benachrichtigung im Spam landet. Logging-Fehler brechen das Formular nie.

function lead_log(string $quelle, array $daten): void
{
    try {
        // __DIR__ = <docroot>/inc, zwei Ebenen hoch = Webspace-Root
        $dir  = dirname(__DIR__, 2) . '/uftritt-leads';
        $file = $dir . '/leads.jsonl';

        $eintrag = array_merge([
            'zeit'   => date('c'),
            'quelle' => $quelle,
        ], $daten);

        $zeile = json_encode($eintrag, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($zeile === false) {
            return;
        }

        if (!is_dir($dir)) {
            @mkdir($dir, 0750, true);
        }
        if (@file_put_contents($file, $zeile . "\n", FILE_APPEND | LOCK_EX) === false) {
            @error_log('lead_log: Schreiben fehlgeschlagen: ' . $zeile);
        }
    } catch (\Throwable $e) {
        @error_log('lead_log: ' . $e->getMessage());
    }
}
