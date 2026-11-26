// Redaktionelle "Storytelling"-Detailseiten (nach Jeffreys XD-Export).
// Projekte MIT Eintrag hier rendern das flexible Block-Layout;
// alle anderen fallen auf das einfache Galerie-Layout zurück.

export type StoryCell = {
  img?: string;
  lottie?: string;
  video?: string;
  bg?: string; // Vollflächen-Hintergrund der Zelle (z. B. Primärblau)
  contain?: boolean; // object-fit: contain (Logos/Icons)
  label?: string; // kleine pinke Beschriftung
  alt?: string;
};

export type StoryBlock =
  | { type: "hero"; img: string; alt?: string }
  | { type: "text"; body: string[] }
  | { type: "split"; left: StoryCell; right: StoryCell }
  | { type: "duo"; cells: StoryCell[] }
  | { type: "slider"; cells: StoryCell[]; label?: string } // Karussell (Auto-Loop, Pause bei Hover/Fokus)
  | { type: "tech"; label?: string; rows: { name: string; desc: string }[] }
  | {
      type: "full";
      img?: string;
      video?: string;
      youtube?: string; // YouTube-Video-ID (youtube-nocookie-Embed)
      lottie?: string;
      grey?: boolean;
      contain?: boolean;
      narrow?: boolean; // schmaler, zentriert (z. B. Icons)
      label?: string;
      alt?: string;
    };

export interface ProjectStory {
  intro: string[]; // Einleitungstext neben dem Titel
  services: string[];
  website?: string; // Link zur Live-Website des Projekts
  links?: { label: string; href: string }[]; // mehrere Live-Links (schlägt website, z. B. Embed + Standalone)
  blocks: StoryBlock[];
}

export const projectStories: Record<string, ProjectStory> = {
  "ehc-wetzikon": {
    intro: [
      "Der Zürcher Oberländer Eishockey-Club Wetzikon ist ein traditionsreicher Verein mit zahlreichen Mannschaften von der ersten Mannschaft bis zum breiten Nachwuchs. Für den in die Jahre gekommenen Webauftritt wünschte sich der Verein eine zeitgemässe Erneuerung, damit die hochstehende Qualität, die Professionalität und die Vielfalt des Clubs optimal widergespiegelt werden.",
    ],
    services: ["Konzept", "UX/UI-Design", "Prototyping (Figma)", "Webdesign", "Webentwicklung"],
    website: "https://www.ehcw.ch/",
    blocks: [
      { type: "hero", img: "/projekte/ehc-wetzikon/hero.jpg", alt: "Neue EHCW-Website auf Laptop und Smartphone" },
      {
        type: "text",
        body: [
          "Entwickeln und umsetzen durften wir eine umfangreiche neue Website mit rund 50 Unterseiten. Damit Struktur, Nutzerführung und Gestaltung von Anfang an stimmten, haben wir den gesamten Auftritt zuerst als interaktiven Prototyp in Figma konzipiert und mit dem Verein abgestimmt. Erst danach folgte die technische Umsetzung. Auf Basis von Webflow ist ein moderner, durchgehend responsiver Auftritt entstanden, der den Verein, seine Mannschaften und aktuellen Neuigkeiten übersichtlich präsentiert und auf allen Geräten überzeugt. Klare Struktur, das Erscheinungsbild des Clubs und eine unkomplizierte Pflege standen dabei im Vordergrund. Ergänzt wird der Auftritt durch stets aktuelle Spielpläne, Resultate und Tabellen aller Teams, möglich dank einer eigens entwickelten Schnittstelle zum Verband Swiss Ice Hockey (siehe separate Referenz). So entsteht ein lebendiger Vereinsauftritt, der immer auf dem neuesten Stand ist.",
        ],
      },
      {
        type: "full",
        video: "/projekte/ehc-wetzikon/website.mp4",
        label: "Website",
      },
    ],
  },
  aweso: {
    intro: [
      "Die Aweso AG in Wetzikon ZH entwickelt und produziert seit fast 100 Jahren qualitativ hochwertige und individuelle Beschlaglösungen aus Metall für Glasschiebe- und Regalsysteme. Da die Mitbewerber nicht schlafen, war es an der Zeit, die Marketingtätigkeiten neu zu aktivieren und das traditionsreiche Unternehmen mit einer geschärften Identität klar zu positionieren.",
    ],
    services: [
      "Marketingbegleitung",
      "Identitätsworkshop",
      "Text",
      "Corporate Design",
      "Webentwicklung",
      "Fotografie",
    ],
    website: "https://aweso.ch/",
    blocks: [
      { type: "hero", img: "/projekte/aweso/hero.jpg", alt: "Schweissarbeit bei Aweso" },
      {
        type: "split",
        left: { img: "/projekte/aweso/workshop.jpg", alt: "Identitätsworkshop mit Wortkarten" },
        right: { lottie: "/lottie/aweso-logo.json", contain: true, label: "Logo" },
      },
      {
        type: "text",
        body: [
          "Ein solides Rebranding kann nur auf einem gefestigten Fundament stehen. Aus diesem Grund führten wir in einem ersten Schritt Identitätsworkshops mit der Geschäftsleitung durch. Diese hatten zum Ziel, das Wesen und die Anliegen von Aweso zu klären, um daraus authentische Botschaften und weitere Massnahmen für die Zielgruppen zu definieren.",
        ],
      },
      {
        type: "split",
        left: { lottie: "/lottie/aweso-claim.json", bg: "#005A9D", contain: true, label: "Claim" },
        right: { img: "/projekte/aweso/aerial.jpg", alt: "Luftbild Aweso Areal" },
      },
      {
        type: "text",
        body: [
          "Auf der Grundlage der Workshops wurde der Claim, die sprachlich zusammengefasste Essenz des Unternehmens, neu entwickelt: «Qualität, die bleibt». Ergänzt wird dieser durch die beschreibenden Stichworte: «individuell, durchdacht, bewährt».",
        ],
      },
      { type: "full", img: "/projekte/aweso/magazin.jpg", alt: "Imagebroschüre und Produktkatalog" },
      { type: "full", lottie: "/lottie/aweso-icons.json", narrow: true, label: "Icons" },
      {
        type: "text",
        body: [
          "Im gleichen Zug hat das visuelle Erscheinungsbild ein Redesign erhalten, um auf zeitgemässe und authentische Art die Botschaften zu transportieren. So wurde das bestehende Logo leicht überarbeitet, eine neue Corporate Schrift definiert, die Farbwelt optimiert und ein neues Layoutkonzept für die Kommunikationsmittel entwickelt. Für die drei Produktkategorien Regalsysteme, Innenverglasung und Aussenverglasung haben wir je ein Icon mit eigener Bildsprache entwickelt, zu deren grafisch klaren Abgrenzung.",
        ],
      },
      {
        type: "duo",
        cells: [
          { img: "/projekte/aweso/car.jpg", alt: "Aweso Geschäftsfahrzeug" },
          { img: "/projekte/aweso/worker.jpg", alt: "Produktion bei Aweso" },
        ],
      },
      {
        type: "text",
        body: [
          "Den neu definierten Grundlagen folgten weitere Massnahmen: Wir verfassten Projektberichte für Fachmagazine, gestalteten Inserate und überarbeiteten Imagebroschüre und Produktkataloge. Ein Fotoshooting für aktuelle Image-Bilder und Mitarbeitenden-Porträts zur Erweiterung des Medienpools gehörte ebenfalls dazu.",
          "Die Website von Aweso haben wir ebenfalls komplett neu konzipiert und aufgebaut.",
        ],
      },
      {
        type: "full",
        video: "/projekte/aweso/website.mp4",
        label: "Website Mockup",
      },
    ],
  },
  "gewerbe-bewegt": {
    intro: [
      "«Gewerbe bewegt» ist die Gewerbeschau des Gewerbevereins Bubikon Wolfhausen. Am 6. Juni 2026 öffneten 53 regionale Betriebe ihre Türen und liessen die Besucherinnen und Besucher ihren Arbeitsalltag hautnah erleben, vom Malergeschäft über die Metzgerei bis zum Maschinenbau. Ein Anlass, der die ganze Region bewegt: über zwei Dörfer verteilt, verbunden mit einem Shuttle-Service des Vereins.",
    ],
    services: ["Marketingbegleitung", "Strategie", "Webentwicklung", "Festführer", "Video-Teaser", "Rückblick-Video", "Social-Media-Ads", "Inserate", "Blachen und Plakate"],
    website: "https://gewerbebewegt.ch/",
    blocks: [
      { type: "hero", img: "/projekte/gewerbe-bewegt/hero.jpg", alt: "Drohnenaufnahme von Bubikon mit dem Logo von Gewerbe bewegt" },
      {
        type: "text",
        body: [
          "Für «Gewerbe bewegt» durften wir in Zusammenarbeit mit dem OK den gesamten Auftritt entwickeln und umsetzen, von der ersten Strategie bis zum Rückblick. Uftritt war dabei nicht nur Dienstleister, sondern mittendrin: Marcel Klausberger war im Organisationskomitee für das Ressort Werbung verantwortlich. Die grösste Knacknuss war der Anlass selbst, denn dieser fand über zwei Dörfer verteilt und mit vielen offenen Türen statt.",
        ],
      },
      {
        type: "slider",
        label: "Festführer Gewerbe bewegt 2026",
        cells: [
          { img: "/projekte/gewerbe-bewegt/festfuehrer-1.jpg", alt: "Festführer von Gewerbe bewegt 2026 in der Hand" },
          { img: "/projekte/gewerbe-bewegt/festfuehrer-2.jpg", alt: "Aufgeschlagener Festführer mit Wettbewerb und Situationsplan" },
          { img: "/projekte/gewerbe-bewegt/festfuehrer-3.jpg", alt: "Herausnehmbarer Situationsplan mit allen Betrieben und Shuttle-Routen" },
        ],
      },
      {
        type: "duo",
        cells: [
          { img: "/projekte/gewerbe-bewegt/event-3.jpg", alt: "Kind bohrt unter Anleitung an einem Werkstück aus Holz" },
          { img: "/projekte/gewerbe-bewegt/event-2.jpg", alt: "Junge Besucher spielen am Stand von Uftritt" },
        ],
      },
      {
        type: "text",
        body: [
          "Für den Festführer liefen alle Fäden bei uns zusammen: Wir haben sämtliche Aussteller-Inserate eingeholt und aufbereitet und die ganze Produktion über die Druckerei bis zum Postversand begleitet. So trat der Anlass überall einheitlich und einladend auf und wurde für Betriebe wie Besuchende zum Erlebnis.",
        ],
      },
      {
        type: "duo",
        cells: [
          { img: "/projekte/gewerbe-bewegt/plan.jpg", alt: "Besucher studieren den Situationsplan an der Haltestelle" },
          { img: "/projekte/gewerbe-bewegt/event-1.jpg", alt: "Gäste steigen in den Shuttle zwischen Bubikon und Wolfhausen" },
        ],
      },
      {
        type: "text",
        body: [
          "Damit die Gäste den Überblick behielten, haben wir den vom Verein organisierten Routen- und Shuttle-Fahrplan verständlich aufbereitet und in Festführer und Website integriert sowie für die Strassenbeschilderung gleich auch die nötigen Bewilligungen eingeholt.",
        ],
      },
      {
        type: "duo",
        cells: [
          { img: "/projekte/gewerbe-bewegt/website.jpg", alt: "Website gewerbebewegt.ch auf Laptop und Smartphone" },
          { img: "/projekte/gewerbe-bewegt/social-media.jpg", alt: "Instagram-Reel von Gewerbe bewegt auf dem Smartphone" },
        ],
      },
      {
        type: "text",
        body: [
          "Rundherum entstand ein durchgängiger Auftritt aus einer Hand: Website, Festführer, Video-Teaser, Social-Media-Ads, Inserate, Blachen und Plakate, Konsumationsgutscheine, E-Mail-Signatur und nach dem Anlass ein Rückblick-Video, das die tolle Stimmung festhält.",
        ],
      },
      {
        type: "full",
        youtube: "aAHZPlGD59M",
        label: "Video-Teaser Gewerbe bewegt 2026",
      },
    ],
  },
  "ehc-wetzikon-sihf": {
    intro: [
      "Der Eishockey-Club Wetzikon spielt mit zahlreichen Mannschaften in verschiedenen Ligen, von der ersten Mannschaft bis zum Nachwuchs. Alle Spieldaten, Resultate und Tabellen laufen zentral beim Verband Swiss Ice Hockey zusammen. Diese Informationen auf der Vereins-Website von Hand aktuell zu halten, wäre aufwändig und fehleranfällig.",
    ],
    services: ["Konzept", "App-/Software-Entwicklung", "Schnittstelle und Integration"],
    website: "https://www.ehcw.ch/",
    blocks: [
      { type: "hero", img: "/projekte/ehc-wetzikon-sihf/hero.jpg", alt: "Automatisch aktualisierter Spielplan der MyHockey League auf dem Smartphone" },
      {
        type: "text",
        body: [
          "Für den Eishockey-Club Wetzikon durften wir eine massgeschneiderte Schnittstelle zwischen der offiziellen Datenzentrale von Swiss Ice Hockey und der Vereins-Website entwickeln. Die Anbindung empfängt die Verbandsdaten automatisch, bereitet sie sauber auf und stellt sie der Website als fertige Inhalte zur Verfügung: nächstes Spiel, kompletter Spielplan, Resultate und Ranglisten, für jede Mannschaft einzeln. Die Inhalte erscheinen direkt auf der Seite und nicht in einem eingebetteten Fenster. Sie übernehmen dadurch Schrift und Layout der Website und funktionieren auf allen Geräten. Das Ergebnis ist eine Website, die sich rund um die Uhr von selbst aktualisiert, ohne jede Handarbeit, immer korrekt, direkt aus offizieller Quelle.",
        ],
      },
      {
        type: "full",
        img: "/projekte/ehc-wetzikon-sihf/grafik.jpg",
        alt: "Schema: Die Verbandsdaten von Swiss Ice Hockey laufen über die neue Schnittstelle automatisch auf die EHCW-Website",
      },
      {
        type: "tech",
        label: "Technologien",
        rows: [
          { name: "PHP-Middleware", desc: "Empfängt die Verbandsdaten automatisch, bereitet sie auf und liefert sie als fertige Inhalte an die Website." },
          { name: "Swiss-Ice-Hockey-Schnittstelle (SIHF)", desc: "Die offizielle Datenzentrale des Verbands: Spieldaten, Resultate und Tabellen aller Mannschaften und Ligen." },
          { name: "JSON-API", desc: "Standardisiertes Datenformat, damit die Inhalte schlank und zuverlässig in jedes Website-Layout passen." },
        ],
      },
    ],
  },
  "tenini-webshop": {
    intro: [
      "Die Tenini Sport AG aus Wetzikon stattet Sportvereine mit Teamwear und Fanartikeln aus. Für jeden Verein einen eigenen Onlineshop zu bauen, wäre aufwendig und teuer. Darum haben wir genau einen Shop gebaut, der überall funktioniert: Er lässt sich in beliebig viele Websites einbinden und tritt doch jedes Mal im Look des jeweiligen Vereins auf.",
    ],
    services: ["Konzept", "UX/UI-Design", "Applikationsentwicklung", "White-Label-System"],
    links: [
      { label: "EHCW-Shop: eingebettet in ehcw.ch", href: "https://ehcw.ch/webshop" },
      { label: "FCW-Shop: eigene Domain fcw-onlineshop.ch", href: "https://fcw-onlineshop.ch/" },
    ],
    blocks: [
      { type: "hero", img: "/projekte/tenini-webshop/hero.jpg", alt: "Tenini Webshop im Vereins-Look auf dem Laptop" },
      {
        type: "text",
        body: [
          "Der grösste Vorteil dieses Systems: Ein und derselbe Shop kann auf vielen Seiten gleichzeitig laufen. Beim EHC Wetzikon ist er direkt in die Vereins-Website eingebettet, der FC Wetzikon nutzt ihn unter einer eigenen Domain. Für die Besucherinnen und Besucher fühlt sich beides wie ein eigens gebauter Shop an, mit den Farben, dem Logo und den Schriften ihres Vereins. Kommt ein neuer Verein dazu, passen wir lediglich das Erscheinungsbild an. Der Shop selbst ist schon fertig, der neue Auftritt ist innert kürzester Zeit online.",
        ],
      },
      { type: "full", img: "/projekte/tenini-webshop/white-label.jpg", alt: "White-Label-Prinzip: ein Shop-System, viele Vereins-Auftritte" },
      {
        type: "tech",
        label: "Technik",
        rows: [
          { name: "Shopify", desc: "Das Backend im Hintergrund: Produkte, Preise, Lagerbestände, Bestellungen und Bezahlung laufen über die bewährte Shop-Plattform." },
          { name: "Vanilla JavaScript", desc: "Das selbst programmierte Frontend: schlank und schnell, holt die Produktdaten bei Shopify ab und zieht sich automatisch Farben, Logo und Schriften des jeweiligen Vereins an." },
          { name: "Hosting", desc: "Schneller, stabiler Betrieb. Über 80 Produkte laufen heute auf dieser Basis." },
        ],
      },
      {
        type: "text",
        body: [
          "Auch im Detail ist der Shop auf Vereine zugeschnitten: Grössen sind übersichtlich nach Herren, Damen und Kindern gruppiert statt in einer endlosen Liste, die Produktbilder lassen sich in einer klaren Galerie durchblättern, und der Bestellvorgang bleibt bewusst einfach. Personalisierung ist eingebaut: Trikots lassen sich direkt mit Wunschnamen oder Initialen bestellen. Und weil auch die Bestellverwaltung von uns stammt, sieht Tenini jede Bestellung mit allen Angaben auf einen Blick und exportiert sie als Druckliste für die Produktion. So entsteht für jeden Verein ein eigenständiger Auftritt, und für Tenini ein System, das mit jedem neuen Kunden mitwächst.",
        ],
      },
      {
        type: "full",
        video: "/projekte/tenini-webshop/website.mp4",
        label: "Webshop",
      },
    ],
  },
  "schoener-feiern": {
    intro: [
      "«Schöner Feiern» vermietet in Saland ZH zwei besondere und beliebte Event-Locations, eine Alphütte und einen Eventschopf, für Hochzeiten, Geburtstage, Firmenanlässe und Familienfeste. Der Anspruch ist es, ein Fest-Erlebnis zu bieten, das so einzigartig ist wie die Gäste selbst, extravagant und bodenständig zugleich.",
    ],
    services: ["Corporate Design", "Logodesign", "Webdesign", "Webentwicklung"],
    website: "https://xn--schner-feiern-kmb.ch/",
    blocks: [
      { type: "hero", img: "/projekte/schoener-feiern/hero.jpg", alt: "Signet von Schöner Feiern" },
      {
        type: "full",
        video: "/projekte/schoener-feiern/logo-animation.mp4",
        label: "Logo-Animation",
      },
      {
        type: "text",
        body: [
          "Für «Schöner Feiern» durften wir das Erscheinungsbild auffrischen und den neuen Auftritt ins Netz bringen. Das Logo bringt den Charakter der Locations auf den Punkt: ein kräftiger Schriftzug, kombiniert mit einem handschriftlichen «Feiern», elegant und urchig zugleich. Das neue Farbkonzept, bestehend aus einem bodenständigen Dunkelblau, einem frischen Warmorange und dem cremefarbigen Farbton für Weissraum, trägt massgebend zur zeitgemässen, hochwertigen und frohen Erscheinung bei.",
        ],
      },
      {
        type: "split",
        left: { img: "/projekte/schoener-feiern/typo.jpg", alt: "Typografie von Schöner Feiern" },
        right: { img: "/projekte/schoener-feiern/signet.jpg", alt: "Signet von Schöner Feiern" },
      },
      {
        type: "text",
        body: [
          "Die elegante Typografie trägt den feierlichen Charakter der Hütten zusätzlich nach aussen und verspricht ein hochwertiges Erlebnis.",
          "Auf der Grundlage des neuen Corporate Designs entstand eine übersichtliche Website, die Lust aufs Feiern macht. Sie zeigt die beiden Locations von ihrer schönsten Seite und führt Interessierte mit wenigen Klicks zur Anfrage. So hat «Schöner Feiern» einen Auftritt, der zur besonderen Stimmung der Location passt und neue Gäste unkompliziert zur Buchung bringt.",
        ],
      },
      {
        type: "full",
        video: "/projekte/schoener-feiern/website.mp4",
        label: "Website",
      },
    ],
  },
  "egli-jona": {
    intro: [
      "Die egli jona AG ist ein Garten- und Poolbau-Unternehmen aus Rapperswil-Jona mit über 80 Jahren Familientradition und einem Anspruch, der hochstehendes Handwerk und Innovation verbindet. Für die neue Firmenwagenflotte aus mehreren elektrischen Volvo EX30 trat egli jona mit dem Wunsch an uns, eine Fahrzeugbeschriftung zu entwickeln, die auffällt und sich bewusst von den typischen, austauschbaren Gärtnerei-Fahrzeugen abhebt.",
    ],
    services: ["Konzept", "Ideenentwicklung", "Fahrzeugbeschriftung", "Grafikdesign"],
    website: "https://eglijona.ch/",
    blocks: [
      { type: "hero", img: "/projekte/egli-jona/hero.jpg", alt: "Volvo EX30 mit egli jona Beschriftung, Frontansicht" },
      {
        type: "text",
        body: [
          "Im Designprozess suchten wir eine Lösung, die zum modernen, nachhaltigen Auftritt des Elektrofahrzeugs passt und die innovative, vielschichtige DNA von egli jona widerspiegelt. Wir haben mehrere Varianten ausgearbeitet und am Fahrzeug durchgespielt, bis das Ergebnis überzeugte: ein mehrschichtiges, vielfältiges Blättermuster in mehreren Farbabstufungen für einen dynamischen Verlauf von vorne nach hinten. So ist egli jona im ganzen Einzugsgebiet unterwegs und bleibt im Gedächtnis.",
        ],
      },
      {
        type: "duo",
        cells: [
          { img: "/projekte/egli-jona/hinten.jpg", alt: "Volvo EX30 Heckansicht mit dem Claim «Gärten fürs Leben»" },
          { img: "/projekte/egli-jona/seite.jpg", alt: "Volvo EX30 Seitenansicht mit Blättermuster" },
        ],
      },
    ],
  },
  "100pro-openair-2026": {
    intro: [
      "Das 100Pro Openair ist ein christliches Musikfestival im Tösstal: drei Tage Lobpreismusik, Workshops und Begegnung auf dem Eventgelände Saland, organisiert von den Baumer Kirchen RegiChile und der Reformierten Kirche. Wir begleiten den Anlass bereits seit 2019. Auch zur 4. Ausgabe vom 5.–7. Juni 2026 durften wir für dieses Herzensprojekt den gesamten Auftritt verantworten.",
    ],
    services: ["Strategie & Konzept", "Corporate Design", "Webdesign & -entwicklung", "Social Media", "Werbekampagne", "Ticketing"],
    website: "https://100proopenair.ch/",
    blocks: [
      { type: "hero", img: "/projekte/100pro-openair-2026/hero.jpg", alt: "Flyer 100Pro Openair 2026, Vorder- und Rückseite" },
      {
        type: "text",
        body: [
          "Von der Strategie bis zur Umsetzung vor Ort haben wir für das Openair die komplette Werbung aus einer Hand entwickelt. Am Anfang standen Konzept und ein frisches Design für die Ausgabe 2026: Logo, Farben, Schriften und die durchgängige Bildsprache. Darauf aufbauend entstand alles, was ein Festival sichtbar macht: die Website, Social Media organisch wie auch als Ads, eine Kampagne mit Texten, Bildern und Videos, Inserate, Video-Teaser für Ads sowie die Bühnen-Screens.",
        ],
      },
      {
        type: "full",
        video: "/projekte/100pro-openair-2026/logo-animation.mp4",
        label: "Logo-Animation",
      },
      {
        type: "duo",
        cells: [
          { img: "/projekte/100pro-openair-2026/social.jpg", alt: "Instagram-Auftritt 100Pro Openair auf dem Smartphone" },
          { img: "/projekte/100pro-openair-2026/haengekarton.jpg", alt: "Hängekarton der Buswerbung 100Pro Openair 2026" },
        ],
      },
      {
        type: "text",
        body: [
          "Auch die Werbemittel für draussen kamen von uns: Strassenblachen, Buswerbung, Rollups für die Ticketvorverkäufe, Sponsorenwand und Signaletik. Und weil ein Festival auch reibungslos funktionieren muss, haben wir das Ticketing mit Eventfrog komplett aufgesetzt und eingerichtet. So trat das 100Pro Openair auch 2026 über alle Kanäle einheitlich und stark auf: online, im Print, auf der Bühne und beim Ticketkauf.",
        ],
      },
      {
        type: "full",
        img: "/projekte/100pro-openair-2026/rollup.jpg",
        alt: "Rollup für den Ticketvorverkauf 100Pro Openair 2026",
      },
      {
        type: "full",
        video: "/projekte/100pro-openair-2026/website-animation.mp4",
        label: "Website",
      },
    ],
  },
  "gzo-video-podcast": {
    intro: [
      "Das GZO Spital Wetzikon stellt die medizinische Grundversorgung des Zürcher Oberlands sicher und stand zuletzt vor grossen herausfordernden Themen: von der Sanierungsplanung über die Finanzierung bis zur Notfallversorgung. Um diese offen und aus erster Hand zu erklären, wollte das GZO ein eigenes Format schaffen, das Nähe und Transparenz vermittelt: den Podcast «GZO im Fokus».",
    ],
    services: ["Video-Podcast-Produktion", "Kanalaufbau", "Intro und Outro", "Social-Media-Shorts", "Inhaltliche Beratung"],
    links: [
      { label: "Zum Podcast «GZO im Fokus»", href: "https://www.gzo.ch/ueber-uns/podcast-gzo" },
    ],
    blocks: [
      { type: "hero", img: "/projekte/gzo-video-podcast/hero.jpg", alt: "Podcast-Mockup von «GZO im Fokus» auf dem Smartphone" },
      {
        type: "text",
        body: [
          "Das GZO durften wir mit einem Video-Podcast unterstützen und dabei von Anfang an begleiten. Wir haben beim Aufbau des Kanals geholfen, ein wiedererkennbares Intro und Outro entwickelt und das GZO auch inhaltlich beraten, damit die Folgen klar und souverän rüberkommen.",
        ],
      },
      {
        type: "full",
        img: "/projekte/gzo-video-podcast/youtube.jpg",
        alt: "Video-Podcast «GZO im Fokus» auf dem YouTube-Kanal des GZO",
      },
      {
        type: "text",
        body: [
          "Aus jeder Episode haben wir zusätzlich Video-Shorts für Social Media geschnitten: kurze, pointierte Ausschnitte, die neugierig machen und die Reichweite über die reine Podcast-Hörerschaft hinaus vergrössern. So hat das GZO ein Format, mit dem es komplexe Themen verständlich erklärt, Vertrauen aufbaut und regelmässig sichtbar bleibt.",
        ],
      },
    ],
  },
  metallfaktur: {
    intro: [
      "Die Metallfaktur GmbH aus Rüti ZH ist Spezialistin für Präzisions-Metall- und Kunststoffbearbeitung: von der Lösungsfindung über Konstruktion und Fertigung bis zur Montage. Ihre gefrästen und gedrehten Bauteile stecken in Maschinen, in der Feinwerktechnik und in der Luftfahrt. Ein solches Können verdient einen Auftritt, der genauso präzise und hochwertig wirkt wie die Teile selbst.",
    ],
    services: ["Konzept", "UX/UI-Design (Figma-Prototyp)", "Webentwicklung", "Text", "Icons", "Fotografie"],
    website: "https://metallfaktur.ch/",
    blocks: [
      { type: "hero", img: "/projekte/metallfaktur/hero.jpg", alt: "Neue Metallfaktur-Website auf Laptop und Smartphone" },
      {
        type: "text",
        body: [
          "Für Metallfaktur durften wir die Website neu entwickeln. Die Website entstand zuerst als Prototyp in Figma, bevor sie technisch umgesetzt wurde. Dazu haben wir die Texte überarbeitet und eigene Icons für die Kernkompetenzen (Fräsen, Drehen, Baugruppen) gestaltet, die dem Angebot Struktur geben.",
        ],
      },
      {
        type: "duo",
        cells: [
          { img: "/projekte/metallfaktur/produkt.jpg", alt: "Gefrästes Präzisionsbauteil aus der Fertigung von Metallfaktur" },
          { img: "/projekte/metallfaktur/icons.jpg", alt: "Icons für die Kernkompetenzen Fräsen, Drehen und Baugruppen" },
        ],
      },
      {
        type: "text",
        body: [
          "Einige Produkte sowie Porträtbilder haben wir ebenfalls für den neuen Webauftritt fotografiert. So hat Metallfaktur einen Auftritt, der die eigene Präzision sichtbar macht und aus einem technischen Angebot ein überzeugendes Aushängeschild formt.",
        ],
      },
    ],
  },
  "goodtone-pickups": {
    intro: [
      "Ralph Schönberger baut in seiner Werkstatt «Ralph's Guitar Lounge & Repair» in Bauma im Zürcher Oberland von Hand Tonabnehmer für E-Gitarren und -Bässe, dazu bietet er Zubehör, Reparaturen und Umbauten von Instrumenten an. «Good Tone Pickups» ist ein Herzensprojekt für anspruchsvolle Musikerinnen und Musiker, die auf handgemachte, schweizerische Qualität setzen. Seine bisherige Website und der Online-Shop wurden diesem Anspruch aber nicht mehr gerecht.",
    ],
    services: ["Konzept", "UX/UI-Design", "Webentwicklung", "Webshop-Entwicklung", "Fotografie"],
    website: "https://goodtone-pickups.ch/",
    blocks: [
      { type: "hero", img: "/projekte/goodtone-pickups/hero.jpg", alt: "Neue Website und Webshop von Good Tone Pickups auf Laptop und Smartphone" },
      {
        type: "text",
        body: [
          "Für Ralph's Guitar Lounge & Repair durften wir die Webpräsenz von Grund auf neu gestalten, mit dem Ziel, das Handwerk hinter den Pickups sichtbar und das Bestellen im Shop so einfach wie möglich zu machen. Der neue Shop rückt die Produkte klar in den Vordergrund, führt durch die Vielfalt an Modellen und macht den Weg von der Auswahl bis zur Bestellung kurz und verständlich.",
        ],
      },
      {
        type: "duo",
        cells: [
          { img: "/projekte/goodtone-pickups/tremolo.jpg", alt: "Tremolo und Mechaniken einer E-Gitarre aus der Werkstatt von Ralph Schönberger" },
          { img: "/projekte/goodtone-pickups/produktbilder.jpg", alt: "Produktbilder der Tonabnehmer im Good Tone Webshop" },
        ],
      },
      {
        type: "text",
        body: [
          "Die hochwertigen Produkte im Shop durften wir mit entsprechenden Fotografien optimal in Szene setzen. Das Ergebnis spricht für sich: Seit dem Relaunch gehen bei Ralph spürbar mehr Bestellungen ein. Aus einer in die Jahre gekommenen Website wurde so ein Verkaufskanal, der die Leidenschaft hinter «Good Tone» transportiert und sich direkt in mehr Aufträgen niederschlägt.",
        ],
      },
      {
        type: "full",
        video: "/projekte/goodtone-pickups/website.mp4",
        label: "Website",
      },
      {
        type: "tech",
        label: "Technologien",
        rows: [
          { name: "WordPress", desc: "Das bewährte System hinter der Website: Inhalte und Produkte pflegt Ralph damit selbst." },
          { name: "WooCommerce", desc: "Der Shop im Hintergrund: Produkte, Bestellungen und Bezahlung laufen über die Shop-Erweiterung von WordPress." },
        ],
      },
    ],
  },
  palimed: {
    intro: [
      "Die Palimed AG bietet spezialisierte ärztliche Betreuung für schwerkranke Menschen in ihrer letzten Lebensphase zu Hause oder im Pflegeheim. Getragen von den Spitälern Uster und Männedorf und unter der Leitung von Dr. Andreas Weber, steht Palimed für Palliative Care in der Region Zürich-Ost. Für dieses sensible Angebot brauchte es einen Auftritt, der Kompetenz und menschliche Zuwendung gleichermassen ausstrahlt.",
    ],
    services: ["Corporate Design", "Logo", "UX/UI-Design", "Webentwicklung (Onepager)"],
    website: "https://palimed.ch/",
    blocks: [
      { type: "hero", img: "/projekte/palimed/hero.jpg", alt: "Erscheinungsbild von Palimed" },
      {
        type: "text",
        body: [
          "Für Palimed haben wir das Erscheinungsbild und einen Onepager umgesetzt, in enger Zusammenarbeit mit Ladina Spiess, die die Inhalte beisteuerte. Wir entwickelten ein einfaches Corporate Design mit Logo, Farben und Schrift: eine visuelle Sprache, die seriös, aber nicht bedrückend wirkt, mit einem warmen Akzent.",
        ],
      },
      {
        type: "duo",
        cells: [
          { img: "/projekte/palimed/farben.jpg", alt: "Farbwelt und Schrift des Palimed-Erscheinungsbildes" },
          { img: "/projekte/palimed/karte.jpg", alt: "Print-Anwendung des Palimed-Erscheinungsbildes" },
        ],
      },
      {
        type: "text",
        body: [
          "Aufbauend auf dem Erscheinungsbild entstand eine übersichtliche Website auf einer Seite, die Betroffene, Angehörige und Fachpersonen schnell zum Wesentlichen führt: was Palimed leistet, wer dahintersteht und wie man das Team erreicht. Gerade bei einem so sensiblen Thema zählt, dass Informationen klar, ruhig und vertrauenswürdig vermittelt werden. So hat Palimed einen Auftritt, der Sicherheit gibt und im richtigen Moment die richtige Anlaufstelle erreichbar macht.",
        ],
      },
      {
        type: "full",
        img: "/projekte/palimed/website.jpg",
        alt: "Palimed-Onepager auf Laptop und Smartphone",
      },
    ],
  },
  "sos-concept": {
    intro: [
      "Die SOS Concept GmbH berät, plant und begleitet Sicherheitslösungen für Gebäude: von der Analyse über die Projektierung bis zur Fachbauleitung. Das Besondere: SOS Concept arbeitet herstellerunabhängig und damit vollständig objektiv. Für diese Haltung und mehr als 25 Jahre Erfahrung brauchte es einen Auftritt, der Kompetenz und Vertrauen auf den ersten Blick vermittelt.",
    ],
    services: ["Logo-Entwicklung", "Corporate Design", "Geschäftsausstattung"],
    blocks: [
      { type: "hero", img: "/projekte/sos-concept/hero.jpg", alt: "Logo von SOS Concept: verschlungenes S-Monogramm in Ockergold" },
      {
        type: "text",
        body: [
          "Für SOS Concept durften wir das Logo und den darauf aufbauenden Auftritt entwickeln. Im Fokus steht eine Bildmarke, die die drei Buchstaben «SOS» zu einem einzigen, verschlungenen S-Monogramm verwebt, gesetzt in einem edlen Ockergold neben dem Schriftzug «CONCEPT» in sachlichem Anthrazit. So entsteht ein Logo, das gleichzeitig hochwertig und unaufgeregt wirkt: passend zu einem Unternehmen, das im Hintergrund für Sicherheit sorgt.",
        ],
      },
      {
        type: "duo",
        cells: [
          { img: "/projekte/sos-concept/briefbogen.jpg", alt: "Briefbogen von SOS Concept" },
          { img: "/projekte/sos-concept/visitenkarte.jpg", alt: "Visitenkarte von SOS Concept" },
        ],
      },
      {
        type: "text",
        body: [
          "Damit die Marke im Alltag konsequent auftritt, haben wir das Logo bis in die Anwendung durchdekliniert: Briefschaften, Visitenkarte, C5-Couvert und E-Mail-Signatur aus einem Guss. So hat SOS Concept einen Auftritt, der überall gleich souverän wirkt.",
        ],
      },
    ],
  },
  "youngstars-magazin": {
    intro: [
      "Youngstars International ist eine christliche Non-Profit-Organisation aus St. Gallen, die weltweit Jungschar-Programme aufbaut und Jugendleiterinnen und -leiter ausbildet. Über ihre Partner in vielen Ländern kommen laufend viele spannende Geschichten zusammen. Diese wurden lange Zeit nur in einem digitalen Newsletter weitergegeben, den «Youngstar News», der der Wirkung der Organisation kaum gerecht wurde.",
    ],
    services: ["Magazinbetreuung", "Redaktionsbegleitung (DE/EN)", "Konzept", "Layout"],
    blocks: [
      { type: "hero", img: "/projekte/youngstars-magazin/hero.jpg", alt: "Das Magazin Youngstars News in gedruckter Form" },
      {
        type: "text",
        body: [
          "Für Youngstars International durften wir aus dem bisherigen Newsletter ein richtiges Magazin machen: zweisprachig in Deutsch und Englisch, gedruckt und digital versendet. Es erscheint nun seit 2026 in dieser Form und erzählt die Geschichten aus den Partnerländern greifbar und wertig.",
        ],
      },
      {
        type: "slider",
        label: "Einblicke in die Youngstars News",
        cells: [
          { img: "/projekte/youngstars-magazin/news-2.jpg", alt: "Doppelseite der Youngstars News mit Geschichten aus den Partnerländern" },
          { img: "/projekte/youngstars-magazin/news-3.jpg", alt: "Englische Ausgabe der Youngstars News" },
          { img: "/projekte/youngstars-magazin/news-4.jpg", alt: "Aufgeschlagene Youngstars News mit Fotos aus der Jungschararbeit" },
          { img: "/projekte/youngstars-magazin/news-5.jpg", alt: "Weitere Doppelseite der Youngstars News" },
        ],
      },
      {
        type: "text",
        body: [
          "Damit die Produktion der Youngstars News verlässlich funktioniert, haben wir nicht nur das Layout entwickelt, sondern den ganzen Ablauf aufgegleist: In Strategiemeetings legen wir gemeinsam die Inhalte fest, Texte und Bilder werden aus aller Welt geliefert, und wir bringen alles in Form, bis zum versandfertigen Druck und PDF. So hat Youngstars ein wiederkehrendes Format, das die eigene Arbeit spürbar macht und die Spenderinnen und Partner näher an die Organisation bindet.",
        ],
      },
    ],
  },
  "youngstars-hub": {
    intro: [
      "Youngstars International ist eine christliche Non-Profit-Organisation aus St. Gallen, die weltweit Jungschar-Programme aufbaut. Die Kontakte zu Spenderinnen und Spendern, Gebetspartnerinnen und Gebetspartnern, Kirchen und Freiwilligen lagen lange in Excel-Listen, verteilt auf mehrere Personen im Team. Wer doppelt erfasst war oder umzog, fiel leicht durch die Maschen, und jede Versandliste für Magazin oder Gebetsbrief entstand von Hand.",
    ],
    services: ["Konzept und Datenmodell", "Applikationsentwicklung", "UX/UI-Design", "Webentwicklung", "Datenmigration", "Mailversand"],
    blocks: [
      { type: "hero", img: "/projekte/youngstars-hub/hero.jpg", alt: "Der Youngstars Hub auf dem Monitor: der Newsletter-Versand im Überblick" },
      {
        type: "text",
        body: [
          "Für Youngstars International durften wir den YI-Hub entwickeln: eine Vereinsplattform, mit der das Team seine Kontakte verwaltet und Newsletter direkt aus dem Hub versendet. Zum Start haben wir über 1000 Personen aus den bisherigen Excel-Listen übernommen, Duplikate zusammengeführt und die Herkunft jeder Angabe dokumentiert. Die Adressverwaltung zeigt jede Person mit Rollen, Sprachen und Einwilligungen, und auf einen Blick, wer wann wie viel gespendet hat. Daraus filtert das Team seine Versandlisten selbst und exportiert sie als Excel: die Youngstars News als Print-Auflage oder digitale Ausgabe, den Prayerletter für die Gebetspartner. Beide versendet das Office-Team direkt aus dem Hub, auf Deutsch und Englisch, mit persönlicher Anrede, Magazin-PDF im Anhang und Abmeldelink im Footer.",
        ],
      },
      {
        type: "full",
        img: "/projekte/youngstars-hub/grafik.jpg",
        alt: "Die Module des Youngstars Hubs im Überblick: Kontakte, Gruppen, Termine, interne News und Mailingversand",
      },
      {
        type: "text",
        body: [
          "Auch die Plattform selbst ist zweisprachig bedienbar. Und weil der Hub direkt mit der Website verbunden ist, gelangen Anmeldungen für Magazin, Gebet oder Mitarbeit ohne Umweg in die Datenbank. Aus Handarbeit und Listenpflege ist ein Werkzeug geworden, mit dem das kleine Team seine Versände selbst steuert.",
        ],
      },
      {
        type: "tech",
        label: "Technologien",
        rows: [
          { name: "Next.js", desc: "Die selbst programmierte Plattform: schnelle Oberfläche und Server-Logik aus einem Guss, zweisprachig bedienbar." },
          { name: "Supabase", desc: "Die Datenbank im Hintergrund: Kontakte, Rollen, Sprachen und Einwilligungen liegen bereinigt an einem Ort." },
          { name: "Hosting", desc: "Betrieb und Hosting der Plattform: stabil, schnell und weltweit erreichbar." },
          { name: "Microsoft 365 (Versand)", desc: "Der Mailversand läuft über die bestehende Microsoft-Umgebung von Youngstars, inklusive PDF-Anhang und Abmeldelink." },
        ],
      },
    ],
  },
};
