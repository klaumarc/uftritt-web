# Betriebshandbuch uftritt.ch

Für Marcel (und Jeff). Stand 2026-09-07. Geschrieben für Nicht-Entwickler:
Du musst den Code nicht schreiben können, du musst wissen, was wo liegt,
wie etwas live geht und was im Notfall zu tun ist. Genau das steht hier.

---

## 1. Das Wichtigste in 60 Sekunden

- uftritt.ch ist eine **statische Website**. Es gibt keine Datenbank, kein
  Login, kein CMS, keine Admin-Oberfläche. Inhalte stehen in Textdateien,
  ein Build-Werkzeug (**Astro**) baut daraus fertige HTML-Seiten.
- Es gibt **zwei Orte**:
  1. **Das Repo** (der Code) auf deinem Rechner:
     `0.0 Uftritt/08_Website_Uftritt/Website_2026/uftritt-web`
  2. **Der Server** bei Kreativ Media (`janus.kreativmedia.ch`): liefert
     uftritt.ch aus. Was dort liegt, sehen die Besucher.
- Live schalten geht **nur** so: Datei im Repo ändern → Skript
  `scripts/deploy.sh` laufen lassen → fertig. Das Skript baut die Seite und
  lädt die geänderten Dateien per FTP hoch.
- **Goldene Regeln:**
  - Nie Dateien direkt auf dem Server von Hand ändern (wird beim nächsten
    Deploy überschrieben und verwirrt alle).
  - Niemals Netlify anfassen. Existiert für uftritt.ch nicht (Hard-Stop
    Marcel, 2026-09-03).
  - Es geht nichts zu GitHub (Marcel, 2026-11-12). Vor grösseren
    Änderungen: `git commit`, das ist dein lokaler Rückgängig-Knopf.

---

## 2. Landkarte: Was liegt wo

Im Repo (`uftritt-web/`):

| Ort | Was drin ist | Wann du reinschauen musst |
|---|---|---|
| `src/pages/` | Jede `.astro`-Datei = eine Seite der Website. `startup-website.astro` wird zu uftritt.ch/startup-website/ | Text, Preise oder Aufbau einer Seite ändern |
| `src/pages/angebot/` | Die 7 Angebotsseiten (Webdesign, Grafikdesign, Video usw.) | Angebotspflege |
| `src/pages/projekte/` | Referenz-Übersicht + Detailseiten (automatisch aus Daten gebaut) | fast nie, steuert sich über `src/data/` |
| `src/pages/aktuell/` | News-Übersicht + Detailseiten (automatisch aus Daten gebaut) | fast nie |
| `src/pages/_drafts/` | Entwürfe. Astro ignoriert Ordner mit `_`, diese Seiten sind offline | Parkplatz für unfertiges |
| `src/data/` | **Die Inhalte als Listen:** `projects.ts` (Referenzen), `news.ts` (Aktuell-Beiträge), `team.ts`, `reviews.ts`, `projectDetails.ts`, `projectStories.ts` | Referenz, News, Teammitglied oder Bewertung hinzufügen |
| `src/components/` | Wiederverwendbare Bausteine (Header, Footer, Kontakt-Modal, OfferLink usw.) | nur mit KI-Hilfe |
| `src/layouts/Layout.astro` | Der Rahmen um jede Seite (Kopf, Tracking, Footer) | nur mit KI-Hilfe |
| `src/styles/global.css` | Alle Farben, Schriften, Abstände (Design-Tokens) | nur mit KI-Hilfe, Regeln stehen in `DESIGN.md` |
| `public/` | Alles was 1:1 auf den Server kopiert wird: Bilder (`img/`, `projekte/`, `news/`, `team/`), Fonts, Videos, `robots.txt`, `llms.txt` und die **PHP-Formulare** | Bilder austauschen |
| `public/kontakt.php`, `besprechung.php`, `rueckruf.php` | Empfangen die Formulare und schicken Mails an info@uftritt.ch | nur mit KI-Hilfe |
| `public/api/` | Das Website-Check-Tool (siehe Kapitel 6) | nur mit KI-Hilfe |
| `scripts/deploy.sh` | Das Live-Schalt-Skript | Kapitel 5 |
| `scripts/optimize-images.mjs` | Macht aus grossen Bildern schnelle Web-Varianten | nach neuen Projektbildern |
| `DESIGN.md` | Das Design-Regelwerk (Farben, Typo, Animationen) | Referenz bei Gestaltungsfragen |
| `dist/` | Der fertig gebaute Stand (wird bei jedem Build neu erzeugt) | nie von Hand anfassen |

**Wie eine Seite aufgebaut ist** (am Beispiel `startup-website.astro`):
Oben zwischen zwei `---`-Linien steht das **Frontmatter**: dort liegen die
Inhalte als Listen, z. B. die drei Pakete mit Preisen (`CHF 5950` usw.),
der Ablauf, die FAQ. Darunter kommt der HTML-Teil, der diese Inhalte
anordnet. Für Text- und Preisänderungen bleibst du fast immer im
Frontmatter, der HTML-Teil muss meist nicht angefasst werden.

---

## 3. Häufige Änderungen, Schritt für Schritt

### 3.1 Text oder Preis auf einer Seite ändern

1. Datei in `src/pages/` öffnen (z. B. `startup-website.astro`).
2. Im Frontmatter den Text/die Zahl suchen und ändern.
3. Live schalten wie in Kapitel 5.

Achtung bei Preisen: Es gelten die geltenden Preis-Regeln (keine Preise
auf Themen- und Branchen-Landingpages, Fixpreise nur auf den
Angebotsseiten in den FAQ). Details stehen im Hub-Gedächtnis
(`memory/website-content-regeln.md`).

### 3.2 Referenz (Projekt) eintragen

1. Bild des Projekts nach `public/projekte/<slug>.jpg` legen.
2. In `src/data/projects.ts` eine Zeile ins Array `projects` einfügen,
   Vorlage kopieren:
   `{ title: "Kundenname", slug: "kundenname", categories: ["Webdesign"], image: "/projekte/kundenname.jpg", website: "https://..." },`
3. Danach einmal `npm run optimize:images` laufen lassen (macht die
   schnellen Bildvarianten).
4. Deploy.

Regeln: Auf Landingpages werden Referenzen über die Komponente OfferLink
gezeigt, **immer genau 3 Slugs** eintragen (sie zeigt maximal 3). Kunden
ohne eigene Projektseite im Text nennen und extern verlinken, nie auf
nicht existierende `/projekte/`-Adressen. **Keine neuen Projektseiten
ohne Design-Freigabe** (Marcel, 2026-09-04).

### 3.3 News-Beitrag (Aktuell) veröffentlichen

1. Bild nach `public/news/<slug>.jpg`.
2. In `src/data/news.ts` oben einen Eintrag einfügen (neueste zuerst):
   Titel, slug, category, date, image, excerpt, body (Absätze als Liste).
   Nur Originaltexte, keine KI-Platzhalter.
3. Deploy. Die Seiten `/aktuell/` und `/aktuell/<slug>/` entstehen
   automatisch.

### 3.4 Bild austauschen

1. Neue Datei unter `public/` an denselben Pfad legen (gleicher Name =
   ersetzt das alte).
2. Bei Projektbildern: `npm run optimize:images`.
3. Deploy.

### 3.5 Teammitglied oder Bewertung

- Team: `src/data/team.ts` (+ Foto nach `public/team/`).
- Bewertungen: `src/data/reviews.ts`.

### 3.6 Neue Landingpage

Nicht von Hand bauen. Dafür gibt es den Skill `uftritt-landingpage` bzw.
die KI. Die verbindlichen Regeln (kein «–», CTA «Kostenloses
Erstgespräch», 3 Referenzen, Lottie im Hero usw.) stehen im Hub-Gedächtnis
`memory/website-content-regeln.md`.

---

## 4. Lokal anschauen, bevor etwas live geht

Voraussetzung: **Node.js** (LTS-Version) ist installiert.

Einmalig nach dem ersten Holen des Repos:

```bash
npm install
```

Danach jederzeit:

```bash
npm run dev
```

Dann läuft die komplette Website auf `http://localhost:4321` im Browser,
mit Live-Neuladen bei jeder Dateiänderung. So siehst du Änderungen, bevor
sie jemand anderes sieht. Stoppen mit `Ctrl+C`.

Build-Test ohne Livegang: `npm run build` (baut nach `dist/` und zeigt
Fehler an, die sonst der Deploy zeigen würde).

---

## 5. Live schalten (Deploy)

Das Skript `scripts/deploy.sh` baut die Seite und lädt **nur geänderte
Dateien** per FTP hoch (Prüfsummen-Vergleich, deshalb schnell). Es braucht
das FTP-Passwort zur Laufzeit. **Das Passwort kommt aus dem Secrets-Vault
und wird nie in eine Datei geschrieben.**

**Schritt 1: Staging** (Test-Adresse, niemand sieht das ausser dir):

```bash
FTP_PW='PasswortAusDemVault' bash scripts/deploy.sh
```

Danach auf `https://uftritt.mklkreativ.ch` anschauen und klicken.

**Schritt 2: Produktion** (uftritt.ch):

```bash
FTP_DIR=uftritt.ch-neu FTP_PW='PasswortAusDemVault' bash scripts/deploy.sh
```

Nützliche Varianten:

- `bash scripts/deploy.sh --full` lädt alles neu hoch (wenn der Stand auf
  dem Server unklar ist; dauert länger).
- `bash scripts/deploy.sh --seed` setzt nur die lokale Prüfsummen-Datei,
  ohne hochzuladen (Sonderfall: Server entspricht bereits dem Build).

Technik-Hintergrund kurz: Der Server ist `janus.kreativmedia.ch`, FTP-User
`uftritt_1988`. Produktion heisst beim Upload `uftritt.ch-neu` (so heisst
der Ordner, aus dem uftritt.ch seit dem Launch 2026-08-14 ausgeliefert
wird). FTPS funktioniert auf diesem Server nicht, darum läuft der Upload
über einfaches FTP mit Einzeldateien. In `package.json` liegt noch ein
`netlify-cli` als Altlast: ignorieren, Netlify ist tabu.

---

## 6. Formulare, Leads und das Website-Check-Tool

### Formulare

- Drei Empfänger-Dateien: `kontakt.php` (Kontaktseite + Modal),
  `besprechung.php` (Erstgespräch) und `rueckruf.php` (Rückruf-Popup).
  Alle senden per PHP `mail()` an **info@uftritt.ch**, Absender
  `no-reply@uftritt.ch` (muss auf der Domain liegen, sonst blockt der
  Spam-Schutz). Der SPF-Eintrag der Domain deckt den Server, darum kommen
  die Mails an.
- **Uftakt-Startformular** (`/uftakt/start/`, Empfänger `uftakt-start.php`):
  Schritt-Formular mit Paket-Routing. Das gewählte Paket steuert, welche
  Schritte erscheinen: mit Webseite 6 bis 7 Schritte, Paket «Logo» allein
  nur 5 (keine Webseiten-, Domain- und Impressum-Fragen, dafür die
  Logo-Fragen: 3 Stichworte, Firmenname im Logo, Logoform, Claim). Ohne
  JavaScript steht alles als ein langes Formular da und funktioniert
  klassisch per POST. Im PHP ist das Impressum nur Pflicht, wenn das Paket
  eine Webseite enthält. Dateien werden clientseitig als base64
  mitgeschickt, weil die Server-WAF echte Multipart-Uploads blockt.
- **Honeypot:** Bots, die das unsichtbare Falle-Feld ausfüllen, werden
  still auf `/danke/` geleitet. Im Statistik-Tool zählt das als
  Conversion, es geht aber kein Mail raus. Bei seltsamen
  «Leads ohne Mail» ist das die Erklärung.
- **Botschutz (seit 2026-11-15):** Vier Stufen in allen Formularen:
  Honeypot, Zeit-Check (Absenden in unter 2,5 Sekunden = Bot), Linkfilter
  (ab 3 Links im Freitext = Spam) und **Cloudflare Turnstile** (das kleine
  Prüf-Widget über dem Absende-Button). Die Zeit-/Link-Stufen verwerfen
  Bots still (kein Mail, kein Fehler), Turnstile zeigt echten Besuchern
  bei Problemen die Fehlerseite. Geblockte Einsendungen stehen im
  Lead-Log mit dem Vermerk `geblockt`. Technik: `public/inc/botguard.php`,
  das Turnstile-Geheimnis liegt als `uftritt-forms-config.php` auf dem
  Server (nie im Repo). Details: Hub-Gedächtnis `botschutz-formulare`.
- **Sicherheits-Kopfzeilen und Zwischenspeicher** kommen aus der
  `.htaccess` (in `public/`). Damit sie wirken, ist in Plesk für
  uftritt.ch und die Staging-Domain der Schalter «nginx transparent mode»
  AN (nginx reicht alles an Apache weiter). Diesen Schalter nicht
  ausschalten, sonst verschwinden die Kopfzeilen bei echten Besuchen
  wieder.
- **Lead-Log:** Jede Formular-Einsendung schreibt zusätzlich eine Zeile
  nach `uftritt-leads/leads.jsonl` (liegt ausserhalb des Webroots, per FTP
  erreichbar). Dort siehst du, ob eine Anfrage ankam, auch wenn das Mail
  unterwegs verloren ging.
- `/rueckruf/` als Seite ist **bewusst offline** (Entwurf in
  `src/pages/_drafts/`, Revi steht noch aus). Das Rückruf-Popup auf den
  anderen Seiten funktioniert trotzdem, das ist etwas anderes.
- Das Gewinn-Feature des Spiels (`/api/gewinn.php`) läuft über denselben
  Mail-Mechanismus.

### Website-Check-Tool (`/website-check/`)

Freemium-Tool: 2 Gratis-Checks pro Tag und IP, Vollreport für CHF 9 über
Stripe (Karte + TWINT, live seit 2026-08-18). Code in `public/api/`.
Drei Dinge musst du kennen:

- **Die geheime Konfiguration liegt nur auf dem Server**
  (`uftritt-check-config.php` im FTP-Root: Moonshot-Key, Stripe-Keys,
  IP-Salt, optional Google-Key für PSI/CrUX). Sie gehört nie ins Repo oder
  nach OneDrive. Neue Keys kommen aus dem Vault und werden per FTP hochgeladen.
- **Datenhygiene:** Käufer- und Lead-Daten müssen quartalsweise manuell
  gelöscht werden (Kaufdaten 12 Monate, Buchhaltung 10 Jahre). Nächste
  Fälligkeit: **2026-11-18**. Speicherort: `uftritt-check-storage/` im
  FTP-Root.
- **Seit der V2 (2026-11-11) prüft das Tool über 45 Kriterien**, neu u. a.:
  Textqualität (Lesbarkeit nach Amstad, Keyword-Stuffing, Floskeln),
  Vertrauenssignale (Impressum/UWG, Datenschutz/nDSG), Stockfoto-Erkennung,
  Bildgewicht, hreflang, KI-Sichtbarkeit (robots.txt/llms.txt), echte
  Ladezeit-Daten aus dem Chrome UX Report (sobald ein Google-Key in der
  Config liegt) und im bezahlten Report zusätzlich eine Lighthouse-Labormessung
  (inkl. Farbkontrast). Neu dabei: Branchenerkennung und Farbpalette, die
  KI-Texte beziehen Branche und Ort mit ein.

Alle Details zum Tool stehen im Hub-Gedächtnis
(`memory/website-check-tool.md`).

---

## 7. Messung: Wo du siehst, ob es läuft

- **Matomo** (Besucher, Conversions, cookieless):
  `https://analytics.mklkreativ.ch/matomo/` (der Pfad `/matomo/` ist
  wichtig, die nackte Subdomain zeigt nur eine Platzhalterseite, das ist
  kein Defekt). uftritt.ch ist Site ID 1.
  Die wahre Lead-Zahl = **Goal 1 + 2 + 6** (Kontakt + Rückruf +
  Erstgespräch-Modal). Staging wird nicht getrackt.
- **Google Search Console:** Nur die Property `https://www.uftritt.ch/`
  zeigt Daten. Die Domain-Property `sc-domain:uftritt.ch` zeigt aus
  technischen Gründen null, das ist eine bekannte Falle, kein Defekt.
- **Bing Webmaster Tools:** Sitemap ist eingereicht und gecrawlt.
- **Sitemap:** uftritt.ch/sitemap-index.xml (baut Astro automatisch,
  `/danke/` und `/404/` bleiben absichtlich draussen).

---

## 8. Notfall-Kapitel

Ruhe bewahren: Die Seite ist statisch. Es gibt fast nichts, das «von
selbst» kaputt gehen kann. Die häufigsten Fälle:

### 8.1 uftritt.ch ist komplett weg

1. Eigene Leitung prüfen: Geht `uftritt.mklkreativ.ch` (Staging)? Gehen
   andere Websites?
2. Geht auch Staging nicht → liegt am Server oder Hoster.
   Plesk prüfen: `https://janus.kreativmedia.ch:8443` (Zugang Marcel).
3. Nichts geht → Ticket an **support@mail.kreativmedia.ch**
   (Ansprechperson bisher: Robie Atienza). Hosting-Probleme sind deren
   Baustelle, nicht deine.
4. Wichtig: Nichts «reparieren», bevor du weisst, was kaputt ist.

### 8.2 Nach einem Deploy sieht die Seite kaputt aus

1. Erst harter Reload im Browser: `Strg+F5` (der Server liefert statische
   Dateien sehr aggressiv aus dem Cache).
2. Bleibt es kaputt: **Rollback über Git.** Darum ist der lokale
   `git commit` vor grösseren Änderungen so wichtig. Der sicherste Weg für dich:

   ```bash
   git log --oneline -10
   ```

   zeigt die letzten Stände. Der Stand vor der kaputten Änderung lässt
   sich wiederherstellen und neu deployen. Wenn du unsicher bist: Das ist
   ein Fall für die KI oder jemanden mit Git-Erfahrung, Auftrag wörtlich:
   «Stell den Stand von gestern wieder her und deploye ihn.» Keine
   Panik-Aktionen auf dem Server selbst.

### 8.3 Formular kommt nicht an

1. Junk-Ordner von info@uftritt.ch prüfen.
2. `uftritt-leads/leads.jsonl` per FTP öffnen:
   - Eintrag vorhanden → die Anfrage kam an, nur das Mail ging verloren
     (Mail-Problem, meist Hoster oder Spamfilter).
   - Kein Eintrag → Formular oder Server-Problem. Selbst eine
     Testanfrage schicken und schauen, ob sie im Log landet.
3. Mail-Problem bleibt → Kreativ Media ticketen (SPF ist korrekt
   konfiguriert, sollte also selten sein).

### 8.4 Deploy bricht ab oder alles meldet 403

- Passwort falsch oder nicht gesetzt → Fehlermeldung lesen, Passwort aus
  dem Vault neu setzen.
- **IP-Sperre:** Nach sehr vielen FTP-/API-Aktionen in kurzer Zeit sperrt
  der Server die eigene IP vorübergehend (fail2ban, Antwort 403). Ca. 30
  Minuten warten oder in Plesk unter «IP Address Banning» die eigene IP
  freigeben.
- **EPERM-/Sperr-Fehler beim Bauen:** OneDrive hält eine Datei fest.
  OneDrive-Sync kurz pausieren und erneut versuchen. (Der Build-Cache
  liegt schon absichtlich ausserhalb von OneDrive.)

### 8.5 Browser meldet Zertifikatsfehler

Plesk → uftritt.ch → SSL/TLS Certificates → Let's Encrypt erneuern
(Haken «Include www»). Let's-Encrypt-Zertifikate laufen nach 90 Tagen ab,
normalerweise erneuert Plesk automatisch. Wenn nicht: manuell anstossen.

### 8.6 Keine echten Notfälle (damit du nicht grundlos suchst)

- `analytics.mklkreativ.ch` ohne `/matomo/` zeigt eine Platzhalterseite.
- `/rueckruf/` gibt 404 (gewollt, siehe Kapitel 6).
- Search Console Domain-Property zeigt überall 0 (Falsche Property,
  siehe Kapitel 7).

---

## 9. Regeln, die niemals brechen

Kurzfassung, Details in `DESIGN.md` und im Hub-Gedächtnis:

- Kein «–» (Halbgeviertstrich) in Website-Texten. Doppelpunkt oder Komma.
- CTA heisst «Kostenloses Erstgespräch».
- Preis-Regeln einhalten (keine Preise auf Themen-/Branchen-LPs).
- Referenzen: genau 3 pro Landingpage, nie auf nicht existierende
  Projektseiten verlinken, keine neuen Projektseiten ohne Design-Freigabe.
- Kein «Studio» behaupten («bei uns im Haus», «Fotobox», «Büro in Saland»).
- Niemals Netlify. Deploy nur via `scripts/deploy.sh`.
- Keine Passwörter oder API-Keys ins Repo oder nach OneDrive. Immer Vault.
- Vor grossen Änderungen: commit + push (Rückgängig-Knopf).

---

## 10. Begriffe, kurz erklärt

- **Astro:** Das Werkzeug, das aus den Dateien in `src/` fertige
  HTML-Seiten baut. Ergebnis landet in `dist/`.
- **Build:** Der Bau-Vorgang (`npm run build`). Aus Zutaten wird die
  fertige Seite.
- **Deploy:** Das Hochladen des Builds auf den Server (= live schalten).
- **FTP:** Das Übertragungs-Protokoll, mit dem Dateien zum Server kommen.
- **Staging / Produktion:** Test-Adresse (uftritt.mklkreativ.ch) versus
  echte Adresse (uftritt.ch). Immer erst Staging.
- **Frontmatter:** Der Datenblock oben in einer `.astro`-Datei zwischen
  den `---`-Linien. Da stehen Texte und Preise.
- **Komponente:** Wiederverwendbarer Baustein (z. B. Header). Einmal
  gebaut, überall benutzt.
- **Git:** Merkt sich jede Version des Codes lokal auf deinem Rechner.
  Dein Rückgängig-Knopf. (GitHub wird nicht mehr bespielt, Marcel
  2026-11-12.)
- **PHP:** Läuft auf dem Server und macht die wenigen dynamischen Dinge:
  Formular-Mails, Website-Check-Tool. Der Rest ist statisches HTML.
- **SPF / DMARC:** Regeln im DNS, die fremde Absender unter deiner Domain
  blockieren. Darum senden die Formulare mit `no-reply@uftritt.ch`.
- **Matomo / GSC:** Besucher-Statistik (selbst gehostet) bzw. Googles
  Blick auf die Seite.

---

## 11. Arbeiten mit der KI an dieser Seite

Damit du die Kontrolle behältst, auch wenn du den Code nicht liest:

- Aufträge klein halten: «Ändere den Preis X auf Seite Y» statt «mach die
  Seite besser».
- Vor der Änderung erklären lassen: «Sag mir in einem Satz, welche Dateien
  du anfasst und warum.»
- Immer den Staging-Link verlangen, bevor Produktion deployt wird.
- Danach pushen lassen: «Committe und pushe den Stand.»
- Dieses Handbuch der KI zeigen, wenn sie etwas am Projekt tut. Es steht
  im Repo, also findet sie es von selbst.

---

*Dieses Handbuch gehört zum Code. Bei grossen Änderungen an der Seite
(Deploy-Prozess, Formulare, Tools) bitte gleich hier mitpflegen.*
