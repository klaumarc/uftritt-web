// MapLibre GL lädt seinen Web-Worker zur Laufzeit als Datei neben dem JS-Bundle
// (import.meta.url, dynamisch). Vite kann diese URL nicht statisch bündeln, darum
// kopieren wir Worker + Shared-Chunk aus node_modules nach public/_astro/.
// Astro reicht sie unverändert nach dist/_astro/ durch (same-origin, CSP-konform).
// Läuft automatisch als npm-prebuild (also auch im deploy.sh).
import { copyFileSync, mkdirSync } from "node:fs";
import { dirname, join } from "node:path";
import { fileURLToPath } from "node:url";

const root = join(dirname(fileURLToPath(import.meta.url)), "..");
const dist = join(root, "node_modules", "maplibre-gl", "dist");
const out = join(root, "public", "_astro");

mkdirSync(out, { recursive: true });
for (const f of ["maplibre-gl-worker.mjs", "maplibre-gl-shared.mjs"]) {
  copyFileSync(join(dist, f), join(out, f));
  console.log(`kopiert: public/_astro/${f}`);
}
