# Uftritt Website – Design- & Animations-System

Dieses Dokument erfasst das komplette Styling, die Konventionen und die Animationen
der Uftritt-Website (Astro, statisch, ohne CMS). Es ist die Referenz für konsistente
Weiterentwicklung.

- **Tech:** Astro (statisch), Inhalte datei-basiert in `src/data/*.ts`
- **Zentrale Stildatei:** `src/styles/global.css` (Design-Tokens, Reset, Typo, Motion)
- **Hausschrift:** Rational (Webfonts in `public/fonts/`)
- **Deploy:** inkrementell via `scripts/deploy.sh` auf Kreativmedia/Plesk (siehe unten)

---

## 1. Farben (Design-Tokens)

Definiert in `:root` in `global.css`:

| Token | Wert | Verwendung |
|---|---|---|
| `--color-blue` | `#014080` | Primärblau – **Fliesstext UND Überschriften**, Buttons, Icons |
| `--color-blue-dark` | `#013366` | Hover für blaue Flächen |
| `--color-yellow` | `#ffd23c` | **nur Akzent/Hintergrundfläche**, kein Fliesstext |
| `--color-pink` | `#da2c67` | **nur** „Schriib eus"-Kreis + Flächen-Buttons auf farbigem Grund |
| `--color-pink-dark` | `#bd1f53` | Hover für Pink-Buttons |
| `--color-white` | `#ffffff` | Text auf dunklem Grund, Standard-Hintergrund |
| `--color-ink` | `#014080` | = Blau, semantischer Fliesstext-Token |
| `--color-muted` | `rgba(1,64,128,.55)` | sekundärer Text (helleres Blau) |
| `--color-bg-soft` | `#f5f7fa` | dezente Sektionsflächen, Scrollbar-Track |

**Farbregeln (verbindlich):**
- **Text ist immer Blau** (`#014080`) oder **Weiss** (auf dunklem Grund) – **kein Schwarz**. Gilt für Fliesstext UND Überschriften.
- **Pink** ausschliesslich für den „Schriib eus"-Kreis und für Flächen-Buttons, die auf einer farbigen Fläche (z. B. Gelb) stehen. Nicht in normalem Text/UI.
- **Gelb** nur als Akzent oder Hintergrundfläche, nie als Textfarbe.
- Projektspezifische Fremdfarben sind erlaubt, wo sie zum Kunden gehören (z. B. Aweso-Blau `#005A9D` als Bildhintergrund auf der Aweso-Detailseite).

---

## 2. Typografie

- **Schrift:** `--font-base: "Rational", system-ui, …` – eine Familie mit echten Weights (400/400i/500/600/700/800/900), Webfonts `font-display: swap`.
- **Fliesstext:** `font-weight: 500` (Rational Medium), `font-size: 1.0625rem` (~17px), `line-height: 1.5`.
- **Überschriften:** Blau, `line-height: 1.04`, `letter-spacing: -0.015em`, alle `font-weight: 700` (Rational Bold).

Skala (aus CD-Export gemessen, responsiv via `clamp`):

| Element | Desktop | Mobile (≤600px) |
|---|---|---|
| `h1` | `clamp(2.85rem, 7.2vw, 6.5rem)` (~104px) | `clamp(2.05rem, 8.5vw, 2.85rem)` |
| `h2` | `clamp(1.95rem, 4vw, 3rem)` (~48px) | `clamp(1.6rem, 6vw, 1.95rem)` |
| `h3` | `clamp(1.45rem, 2.6vw, 2.15rem)` (~34px) | `clamp(1.25rem, 4.8vw, 1.45rem)` |
| `.eyebrow` | `1.05rem`, `font-weight:700`, Label über Sektionen | – |

**Silbentrennung:** `hyphens: auto` mit `lang="de-CH"`. **Desktop (≥601px) OHNE Silbentrennung** (Headings laufen in breiter Spalte aus), Mobile MIT Trennung.

**Schreibweisen:** „&" → „und" (ausser in Firmennamen wie „Arte & Bellezza"). Schweizer Anführungszeichen «…» statt gerader `"` (gerade Quotes brechen den Build in dt. Texten). CH-Deutsch-Button-Labels liefert der Kunde.

---

## 3. Layout

| Token | Wert | Bedeutung |
|---|---|---|
| `--container` | `min(2100px, 90vw)` | Inhalt nimmt ~90% der Screenbreite |
| `--gutter` | `clamp(1rem, 2.5vw, 2.5rem)` | seitliches Padding |
| `--section-y` | `clamp(3.5rem, 8vw, 7rem)` (Mobile kleiner) | vertikaler Sektionsabstand |
| `--radius` | `0px` | **Marke ist eckig – keine abgerundeten Ecken** (Kreise ok) |

- **Keine Schatten** (`box-shadow`/`drop-shadow`) – nur auf explizite Anweisung.
- `.container` = zentriert, max `--container`, `padding-inline: --gutter`.
- Sektions-Flächen: `.section` (vertikales Padding), `.section--yellow`, `.section--blue` (Text weiss, Headings weiss), `.section--soft`.
- **Kein horizontaler Scroll:** `html { overflow-x: clip }` (full-bleed-Elemente wie der Team-Slider ragen sonst über die Scrollbar-Breite hinaus). `scrollbar-gutter: stable` verhindert Springen beim Menü-Öffnen.

---

## 4. Buttons (`src/components/Button.astro`)

Varianten über `variant`-Prop:

| Variant | Aussehen |
|---|---|
| `blue` (Standard) | blaue Fläche, weisser Text, Hover `--color-blue-dark` |
| `yellow` | gelbe Fläche, blauer Text |
| `text` | transparent, blauer Text, kein Padding (Link-Optik) |
| `pink` | pinke Fläche, weisser Text (Flächen-Button) |

**Marken-Pfeil-Effekt (überall gleich):** Ein CD-Pfeil (`/img/icons/pfeil-rechts-blau.svg`, als CSS-Maske eingefärbt über `--arrow-color`) **gleitet bei Hover/Focus von links herein** (`max-width` 0→1.1em, Icon `opacity` 0→1 + `translateX(-16px)→0`), Übergänge `0.5–0.6s var(--ease)`. Beim Verlassen gleitet er wieder raus.

**Pink-auf-Farbfläche-Regel (Revision Jeffrey):** Innerhalb `.section--yellow` werden `.btn--blue`/`.btn--yellow` automatisch pink. In `.section--blue` wird `.btn--text` weiss.

> Für „Zurück"-Links (z. B. „Zur Übersicht") wird ebenfalls `<Button variant="text">` genutzt – gleicher Pfeil-Effekt, konsistent mit allen Seiten.

---

## 5. „Schriib eus"-CTA

- **Sticky FloatingCta** (`src/components/FloatingCta.astro`): pinker Kreis, dreht/skaliert bei Hover. Das frühere Dauer-Wippen (`animation: float`) ist entfernt; nur noch Hover-Effekt.
- **`.cta-circle`** (global.css): pinkes CD-Kreis-Bild, `width: clamp(110px,12vw,150px)`, Hover `transform: scale(1.06) rotate(-4deg)`.

---

## 6. Motion / Animationen

### Scroll-Reveal (`[data-reveal]`)
Elemente mit `data-reveal` starten `opacity:0; translateY(28px)` und blenden bei Sichtbarkeit sanft ein (`.is-visible`, Transition `0.95s var(--ease)`). Optionaler `--reveal-delay` für Staffelung. Die Sichtbarkeit setzt ein Script im Layout via IntersectionObserver.

### Globales Easing
`--ease: cubic-bezier(0.22, 1, 0.36, 1)` – für praktisch alle Übergänge.

### Reduced Motion
`@media (prefers-reduced-motion: reduce)` schaltet Animationen/Transitions ab und zeigt `[data-reveal]` sofort.

### FAQ-Akkordeon
Sanftes Auf-/Zuklappen über `interpolate-size: allow-keywords` + `::details-content` (progressive enhancement; ältere Browser klappen sofort). Erste FAQ offen, exklusiv via gemeinsames `name`-Attribut.

### Lottie-System (`src/components/Lottie.astro`)
Zentrale Komponente, lädt `lottie-web` lazy und initialisiert per IntersectionObserver (`rootMargin: 200px`). Modi über Props:

| Modus | Verhalten |
|---|---|
| **Standard** (autoplay, `loop=false`) | spielt **einmal** beim Reinscrollen, bleibt dann als Standbild stehen |
| **`loop={true}`** | spielt, **pausiert `HOLD_MS` (1400ms) am Ende**, dann erneut → keine Dauerbewegung |
| **`hover={true}`** | spielt vorwärts bei `mouseenter`, rückwärts bei `mouseleave` (z. B. Hero-Lotties, Logo im Header) |
| **`group="…"`** | Gruppen-Sequencer: spielt immer genau **ein** Lottie der Gruppe, dann (nach `HOLD_MS`) das nächste, im Kreis. Tempo `setSpeed(1.35)`. Genutzt für die Service-Icons auf der Startseite |

**Wichtig – Verzögerung:** Standbild-Pausen werden **zentral im Player** gemacht (`HOLD_MS`), nicht mehr im JSON. `loop` wird immer selbst gesteuert (`loadAnimation` mit `loop:false`), damit die Pause am Ende möglich ist. `prefers-reduced-motion` → springt auf letztes Bild.

---

## 7. Hero-Komponenten

- **`PageHero.astro`:** Lottie 2-spaltig, eingerückt (Text + Lottie nebeneinander). Für Angebot-Detailseiten.
- **`SubHero.astro`:** Lottie am Bildschirmrand, Text max. 780px. Für Projekte/Kontakt/Agentur/Angebot-Übersicht.
- Beide binden das Lottie mit `hover={true}` ein (spielt nur bei Hover → keine Dauerbewegung).

---

## 8. Projekte-Detailseite: Story-Block-System

`src/pages/projekte/[slug].astro` rendert zwei Layouts:

1. **Story-Layout** (redaktionell, nach Jeffreys XD) für Projekte mit Eintrag in `src/data/projectStories.ts`.
2. **Fallback** (einfaches Bildraster + optionales YouTube) für alle anderen.

**Block-Typen** (`StoryBlock` in `projectStories.ts`):

| Typ | Beschreibung |
|---|---|
| `hero` | grosses Titelbild, volle Containerbreite, `aspect-ratio: 3/2` |
| `text` | Textabsatz, zentriert, `max-width: 680px` |
| `split` | zwei Zellen nebeneinander (`StoryCell`: `img` \| `lottie` \| `video`, optional `bg`, `contain`) |
| `duo` | zwei Bilder nebeneinander |
| `full` | volle Breite; `img`/`video`/`lottie`, optional `grey` (grauer Fond), `narrow` (schmal zentriert, max 600px) |

**Format-Konvention:** Bilder **und** Lotties sind **1500×1000 px (3:2)** → alle Story-Media-Boxen (`.st-media`) stehen auf `aspect-ratio: 3/2`, damit beides exakt und gleich gross ins Raster passt.

**Navigation oben:** „Zur Übersicht" als `<Button variant="text">` links; Prev/Next als CD-Pfeil-Icons (`pfeil-links/rechts-blau.svg` als Maske, blau → Hover pink) rechts. Grosszügiger Abstand zum Titel.

**Videos** (z. B. Website-Mockup): `muted loop playsinline preload="auto"`, spielen beim Reinscrollen (IntersectionObserver) und loopen. `preload="auto"` puffert vorab → kein Ruckeln.

Pilot-Projekt: **Aweso** (`slug: aweso`) – Kategorie primär „Marketingbegleitung". Assets unter `public/projekte/aweso/`, Lotties `public/lottie/aweso-{logo,claim,icons}.json`.

---

## 9. Asset-Konventionen

- **Icons/Pfeile:** unter `public/img/icons/` (NICHT `public/icons/` – Apache hat einen reservierten `/icons/`-Alias, der Top-Level-`/icons/` überschattet → 404).
- **Lotties:** `public/lottie/*.json`.
- **Projektbilder:** `public/projekte/<slug>/…`, Cover `public/projekte/<slug>.jpg`.
- **News-Bilder:** `public/news/…`.
- **Cursor-PNGs:** `public/cursor/` (Custom-Cursor aktuell deaktiviert).

---

## 10. Deploy

- **Inkrementell** via `scripts/deploy.sh` (baut Astro, vergleicht lokal per SHA-256-Manifest, lädt nur Geändertes per Plain-FTP hoch, löscht entfernte Dateien).
  `FTP_PW='…' bash scripts/deploy.sh` (Passwort nur zur Laufzeit).
- **Nur geänderte Dateien** hochladen – nie den ganzen Ordner ersetzen (kein clean-slate).
- Neue Assets: immer sicherstellen, dass sie im Build/`dist` landen (bei git: `git add -A`, sonst fehlen Bilder/Lotties live).
- Test-Host: `https://uftritt.mklkreativ.ch`.
