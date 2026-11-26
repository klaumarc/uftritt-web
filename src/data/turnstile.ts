// Cloudflare Turnstile (Botschutz der Formulare). Der Sitekey ist öffentlich
// und darf ins Repo; das Secret liegt als uftritt-forms-config.php auf dem
// Server (Subscription-Root, nicht im Repo).
// Leerer Sitekey = Widget wird nicht gerendert und die serverseitige Prüfung
// bleibt aus. Zeit-Check und Linkfilter laufen trotzdem.
export const TURNSTILE_SITEKEY = "0x4AAAAAAGB5cP5yIqbua64i";
