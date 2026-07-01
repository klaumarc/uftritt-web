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
  | {
      type: "full";
      img?: string;
      video?: string;
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
  blocks: StoryBlock[];
}

export const projectStories: Record<string, ProjectStory> = {
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
};
