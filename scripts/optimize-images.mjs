/**
 * optimize-images.mjs — responsive Bildvarianten fuer uftritt.ch
 *
 * Zweck: Erzeugt neben jedem Original-JPG unter public/projekte/ kleinere
 * WebP-Varianten, damit das Markup per <picture>/srcset groessenangepasste
 * Bilder ausliefern kann (mobiler LCP, PSI 2026-08-14: 4.1 s).
 *
 * Varianten:
 *   - alle Bilder:                          <name>-480.webp, <name>-960.webp
 *   - Bilder in Unterordnern (Galerien/Story, public/projekte/<projekt>/...):
 *                                           zusaetzlich <name>-1440.webp
 * Qualitaet ~78, kein Upscale: Zielbreiten >= Originalbreite werden ausgelassen.
 * Originale bleiben unveraendert (Fallback im <img> + Sicherheit).
 *
 * Die Varianten liegen bewusst NEBEN den Originalen (kein Asset-Umzug):
 * Die Pfade in src/data/projects.ts / projectDetails.ts bleiben unveraendert,
 * src/utils/img.ts leitet die webp-Pfade zur Laufzeit vom Originalpfad ab.
 * Welche Varianten tatsaechlich existieren, steht im generierten Manifest
 * src/data/imgVariants.json — nur diese kommen ins srcset (keine 404-Kandidaten).
 *
 * Aufruf:  npm run optimize:images            idempotent: vorhandene Varianten
 *                                             werden uebersprungen
 *          npm run optimize:images -- --force alles neu erzeugen
 * Nach dem Hinzufuegen neuer JPGs einfach erneut laufen lassen.
 */
import { readdir, writeFile, readFile } from "node:fs/promises";
import { existsSync } from "node:fs";
import path from "node:path";
import { fileURLToPath } from "node:url";
import sharp from "sharp";

const ROOT = path.resolve(path.dirname(fileURLToPath(import.meta.url)), "..");
const SRC = path.join(ROOT, "public", "projekte");
const MANIFEST = path.join(ROOT, "src", "data", "imgVariants.json");

const WIDTHS_BASE = [480, 960]; // alle Bilder (Karten/Grids)
const WIDTH_GALLERY = 1440; // zusaetzlich fuer Unterordner-Bilder (grosse Galerie/Story)
const QUALITY = 78;
const FORCE = process.argv.includes("--force");

/** Rekursiv alle JPGs unter dir sammeln (Varianten sind .webp -> keine Kollision). */
async function collectJpgs(dir) {
  const out = [];
  for (const entry of await readdir(dir, { withFileTypes: true })) {
    const p = path.join(dir, entry.name);
    if (entry.isDirectory()) out.push(...(await collectJpgs(p)));
    else if (/\.jpe?g$/i.test(entry.name)) out.push(p);
  }
  return out;
}

const files = (await collectJpgs(SRC)).sort();
console.log(`${files.length} JPGs unter public/projekte gefunden${FORCE ? " (--force)" : ""}`);

const manifest = {};
let created = 0;
let skipped = 0;
let bytesOrig = 0;
let bytesVariants = 0;

for (const [i, file] of files.entries()) {
  // Oeffentlicher URL-Pfad, exakt wie in den Daten-Dateien referenziert.
  const rel = path.relative(path.join(ROOT, "public"), file).split(path.sep).join("/");
  const url = "/" + rel;
  const inSubdir = path.dirname(path.relative(SRC, file)) !== ".";
  const targets = inSubdir ? [...WIDTHS_BASE, WIDTH_GALLERY] : WIDTHS_BASE;

  const img = sharp(file);
  const meta = await img.metadata();
  const widths = [];
  for (const w of targets) {
    if (w >= meta.width) continue; // kein Upscale bei kleineren Originalen
    const out = file.replace(/\.jpe?g$/i, `-${w}.webp`);
    if (!FORCE && existsSync(out)) {
      skipped++;
    } else {
      await sharp(file).resize({ width: w }).webp({ quality: QUALITY }).toFile(out);
      created++;
    }
    widths.push(w);
  }
  if (widths.length) manifest[url] = widths;
  if ((i + 1) % 25 === 0 || i + 1 === files.length) {
    console.log(`  ${i + 1}/${files.length} verarbeitet (neu: ${created}, vorhanden: ${skipped})`);
  }
}

// Manifest stabil + nur bei Aenderung schreiben (sortierte Keys/Breiten).
const sorted = Object.fromEntries(
  Object.keys(manifest)
    .sort()
    .map((k) => [k, manifest[k].sort((a, b) => a - b)])
);
const next = JSON.stringify(sorted, null, 2) + "\n";
let prev = null;
try {
  prev = await readFile(MANIFEST, "utf8");
} catch {}
if (prev !== next) {
  await writeFile(MANIFEST, next);
  console.log(`Manifest geschrieben: ${path.relative(ROOT, MANIFEST)} (${Object.keys(sorted).length} Eintraege)`);
} else {
  console.log(`Manifest unveraendert (${Object.keys(sorted).length} Eintraege)`);
}

console.log(`Fertig: ${created} Varianten neu erzeugt, ${skipped} uebersprungen.`);
