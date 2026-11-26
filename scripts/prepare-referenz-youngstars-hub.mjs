/**
 * Einmal-Skript 2026-11-03: Bilder der neuen Referenz "Youngstars International, Hub"
 * aus ../Referenz-Inhalte nach public/projekte kopieren.
 * - max. 2500 px Breite (kein Upscale), JPEG q84 progressive
 * - PNG-Screenshots werden nach JPEG konvertiert (weisser Hintergrund)
 * Aufruf: node scripts/prepare-referenz-youngstars-hub.mjs
 */
import { mkdir } from "node:fs/promises";
import path from "node:path";
import { fileURLToPath } from "node:url";
import sharp from "sharp";

const ROOT = path.resolve(path.dirname(fileURLToPath(import.meta.url)), "..");
const SRC = path.join(ROOT, "..", "Referenz-Inhalte", "Youngstars_International_Hub", "Bilder");
const DST = path.join(ROOT, "public", "projekte");

// [quelle (rel. zu Bilder), ziel (rel. zu public/projekte)]
// Hinweis 2026-11-04: AD = Arbeitsdateien. Dateien aus dem Unterordner AD/
// grundsätzlich NICHT als Website-Inhalte verwenden (Marcel, Ausnahmen
// vorbehalten). Diese Referenz zeigt nur hero.jpg und grafik.jpg; die
// Position der Inhalte definiert das Word im Referenz-Ordner.
const IMAGES = [
  ["yi-international-hub.jpg", "youngstars-hub/hero.jpg"],
  ["yi-international-hub-grafik.jpg", "youngstars-hub/grafik.jpg"],
  ["yi-international-hub.jpg", "youngstars-hub.jpg"],
];

let n = 0;
for (const [src, dst] of IMAGES) {
  const from = path.join(SRC, src);
  const to = path.join(DST, dst);
  await mkdir(path.dirname(to), { recursive: true });
  const img = sharp(from);
  const meta = await img.metadata();
  if (meta.width > 2500) img.resize({ width: 2500 });
  await img.flatten({ background: "#ffffff" }).jpeg({ quality: 84, progressive: true }).toFile(to);
  const out = await sharp(to).metadata();
  console.log(`${dst}: ${out.width}x${out.height}`);
  n++;
}
console.log(`Fertig: ${n} Dateien.`);
