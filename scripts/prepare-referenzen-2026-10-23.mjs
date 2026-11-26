/**
 * Einmal-Skript 2026-10-23: Bilder/Videos der 6 neuen Referenzen
 * (Good Tone, GZO Video-Podcast, Metallfaktur, Palimed, SOS Concept,
 * Youngstars Magazin) aus ../Referenz-Inhalte nach public/projekte kopieren.
 * - max. 2500 px Breite (kein Upscale), JPEG q84 progressive
 * - Video wird 1:1 kopiert
 * Aufruf: node scripts/prepare-referenzen-2026-10-23.mjs
 */
import { mkdir, copyFile } from "node:fs/promises";
import path from "node:path";
import { fileURLToPath } from "node:url";
import sharp from "sharp";

const ROOT = path.resolve(path.dirname(fileURLToPath(import.meta.url)), "..");
const SRC = path.join(ROOT, "..", "Referenz-Inhalte");
const DST = path.join(ROOT, "public", "projekte");

// [quelle (rel. zu Referenz-Inhalte), ziel (rel. zu public/projekte)]
const IMAGES = [
  ["GoodTone_Website/Bilder/website-mockup-goodtone.jpg", "goodtone-pickups/hero.jpg"],
  ["GoodTone_Website/Bilder/tremolo-machine-heads-1500x1000px.jpg", "goodtone-pickups/tremolo.jpg"],
  ["GoodTone_Website/Bilder/Produktbilder_Webshop_1500x1000px.jpg", "goodtone-pickups/produktbilder.jpg"],
  ["GoodTone_Website/Bilder/website-mockup-goodtone.jpg", "goodtone-pickups.jpg"],

  ["GZO_Video_Podcast/Bilder/gzo-podcast-mockup.jpg", "gzo-video-podcast/hero.jpg"],
  ["GZO_Video_Podcast/Bilder/gzo-youtube-mockup.jpg", "gzo-video-podcast/youtube.jpg"],
  ["GZO_Video_Podcast/Bilder/gzo-podcast-mockup.jpg", "gzo-video-podcast.jpg"],

  ["Metallfaktur_Website/Bilder/metallfaktur-website-mockup.jpg", "metallfaktur/hero.jpg"],
  ["Metallfaktur_Website/Bilder/metallfaktur-produkt.jpg", "metallfaktur/produkt.jpg"],
  ["Metallfaktur_Website/Bilder/metallfaktur-icons.jpg", "metallfaktur/icons.jpg"],
  ["Metallfaktur_Website/Bilder/metallfaktur-website-mockup.jpg", "metallfaktur.jpg"],

  ["Palimed/Bilder/palimed-main.jpg", "palimed/hero.jpg"],
  ["Palimed/Bilder/palimed-farben.jpg", "palimed/farben.jpg"],
  ["Palimed/Bilder/palimed-karte.jpg", "palimed/karte.jpg"],
  ["Palimed/Bilder/palimed-mockup-website.jpg", "palimed/website.jpg"],
  ["Palimed/Bilder/palimed-main.jpg", "palimed.jpg"],

  ["SOS_Concept_Logo/BIlder/sos-concept-main.jpg", "sos-concept/hero.jpg"],
  ["SOS_Concept_Logo/BIlder/sos-concept-briefbogen.jpg", "sos-concept/briefbogen.jpg"],
  ["SOS_Concept_Logo/BIlder/sos-concept-visitenkarte.jpg", "sos-concept/visitenkarte.jpg"],
  ["SOS_Concept_Logo/BIlder/sos-concept-main.jpg", "sos-concept.jpg"],

  ["Youngstars_Magazin/Bilder/youngstars-news-1.jpg", "youngstars-magazin/hero.jpg"],
  ["Youngstars_Magazin/Bilder/youngstars-news-2.jpg", "youngstars-magazin/news-2.jpg"],
  ["Youngstars_Magazin/Bilder/youngstars-news-3.jpg", "youngstars-magazin/news-3.jpg"],
  ["Youngstars_Magazin/Bilder/youngstars-news-4.jpg", "youngstars-magazin/news-4.jpg"],
  ["Youngstars_Magazin/Bilder/youngstars-news-5.jpg", "youngstars-magazin/news-5.jpg"],
  ["Youngstars_Magazin/Bilder/youngstars-news-1.jpg", "youngstars-magazin.jpg"],
];

const VIDEOS = [
  ["GoodTone_Website/Bilder/website-mockup-goodtone-video.mp4", "goodtone-pickups/website.mp4"],
];

let n = 0;
for (const [src, dst] of IMAGES) {
  const from = path.join(SRC, src);
  const to = path.join(DST, dst);
  await mkdir(path.dirname(to), { recursive: true });
  const img = sharp(from);
  const meta = await img.metadata();
  if (meta.width > 2500) img.resize({ width: 2500 });
  await img.jpeg({ quality: 84, progressive: true }).toFile(to);
  const out = await sharp(to).metadata();
  console.log(`${dst}: ${out.width}x${out.height}`);
  n++;
}
for (const [src, dst] of VIDEOS) {
  const to = path.join(DST, dst);
  await mkdir(path.dirname(to), { recursive: true });
  await copyFile(path.join(SRC, src), to);
  console.log(`${dst}: kopiert (Video)`);
  n++;
}
console.log(`Fertig: ${n} Dateien.`);
