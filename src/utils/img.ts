// Ableitung der webp-Varianten fuer Projekt-Bilder unter /projekte/.
// Quelle ist das von scripts/optimize-images.mjs generierte Manifest
// (npm run optimize:images): Nur dort verzeichnete Varianten landen im srcset,
// damit das Markup nie auf fehlende Dateien zeigt. Die Daten-Dateien
// (projects.ts, projectDetails.ts, projectStories.ts) bleiben unveraendert.
import variants from "../data/imgVariants.json";

/**
 * Liefert das webp-srcset ("…-480.webp 480w, …-960.webp 960w") fuer ein
 * Bild unter /projekte/ — oder null, wenn keine Varianten existieren
 * (z. B. Skript noch nicht gelaufen oder kein /projekte/-Bild).
 * Bei null einfach das schlichte <img> ohne <source> rendern.
 */
export function webpSrcSet(src: string): string | null {
  const widths = (variants as Record<string, number[]>)[src];
  if (!widths || widths.length === 0) return null;
  const base = src.replace(/\.jpe?g$/i, "");
  return widths.map((w) => `${base}-${w}.webp ${w}w`).join(", ");
}
