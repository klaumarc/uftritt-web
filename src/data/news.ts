export interface NewsItem {
  title: string;
  slug: string;
  category: string;
  date: string;
  image: string;
  excerpt: string;
  body: string[];
}

// Inhalte aus dem Social-Media-Ordner / den Projekt-Beschrieben – neueste zuerst.
export const news: NewsItem[] = [
  {
    title: "natürli Käsemarkt",
    slug: "natuerli-kaesemarkt",
    category: "Event",
    date: "2026",
    image: "/news/natuerli-kaesemarkt.jpg",
    excerpt: "Genuss trifft Auftritt: Am natürli Käsemarkt waren wir mit dabei und haben den Event visuell begleitet.",
    body: [
      "Genuss trifft Auftritt: Am natürli Käsemarkt waren wir mit dabei und haben den Event visuell begleitet.",
    ],
  },
  {
    title: "SAM FOCUS",
    slug: "sam-focus",
    category: "Magazinbetreuung",
    date: "2026",
    image: "/news/sam-focus.jpg",
    excerpt: "Neue, fokussierte Bildwelten – klar und wirkungsvoll in Szene gesetzt.",
    body: [
      "Neue, fokussierte Bildwelten – klar und wirkungsvoll in Szene gesetzt.",
    ],
  },
  {
    title: "Pink bewegt Hinwil",
    slug: "emotion-hinwil",
    category: "Corporate Design",
    date: "2025",
    image: "/news/emotion-hinwil.jpg",
    excerpt: "Für den Unihockeyverein emotion Hinwil mit seiner markanten Vereinsfarbe haben wir den Auftritt geschärft.",
    body: [
      "Der Unihockeyverein emotion Hinwil mit seiner markanten Vereinsfarbe ist bereits seit 2011 mit verschiedenen Teams erfolgreich in den Ligen von swiss unihockey unterwegs – mit dem Herren-1-Team sogar in der höchsten Kleinfeld-Liga der Schweiz. Das bisherige Erscheinungsbild war jedoch in die Jahre gekommen, weshalb es galt, dieses von Grund auf neu zu entwickeln.",
      "Für uns als Unihockeyfans war es eine grosse Freude, in Zusammenarbeit mit dem Verein ein neues Logo, neue Gestaltungselemente und Design-Richtlinien für die verschiedenen Kommunikationskanäle wie Social Media, Printmedien und Kleidung zu entwickeln. Dabei wurde an der starken, identitätsstiftenden Farbe Pink festgehalten, wobei der Farbton frischer und moderner überarbeitet wurde.",
    ],
  },
  {
    title: "Neue Website für Physio & Sportreha Rothe",
    slug: "website-physio-rothe",
    category: "Webdesign",
    date: "2025",
    image: "/news/physio-rothe.jpg",
    excerpt: "Ein klarer, vertrauensvoller Webauftritt für die Praxis – mit Fokus auf Orientierung und einladende Bildwelten.",
    body: [
      "Die Therapeutinnen und Therapeuten der Physiotherapie & Sport-Reha in Bauma begleiten ihre Klientinnen und Klienten durch sorgfältige Analyse, individuelle Beratung und regelmässige Überprüfung zum körperlichen Wohlbefinden. Die Praxis ist auch mit ihrem Internetauftritt fit unterwegs.",
      "Zusammen mit Inhaber und Geschäftsführer Michael Rothe haben wir die Struktur der Webseite optimiert und für ein frisches, aktuelles Erscheinungsbild gesorgt. Auch die verschiedenen Therapiebehandlungen und die Räumlichkeiten durften wir fotografisch in Szene setzen und auf der Webseite integrieren.",
    ],
  },
  {
    title: "Fahrschule.ch ist online",
    slug: "fahrschule-ch",
    category: "Webdesign",
    date: "2024",
    image: "/news/fahrschule.jpg",
    excerpt: "Eine moderne, mobil-optimierte Website für den Fahrschul-Auftritt – schnell, übersichtlich und auf Anmeldungen ausgelegt.",
    body: [
      "Vom Logo über die Webseite bis zu Fotos und kurzen Filmausschnitten für den Webauftritt durften wir den neuen Auftritt entwickeln und realisieren.",
      "Für das neue Logo haben wir das Schweizerkreuz, das Lehrfahr-«L» sowie die Farben aus dem bisherigen Logo aufgenommen und zu einer dynamischen, einzigartigen Bildmarke kombiniert. Die neue Website wird durch das extra hergestellte Bild- und Videomaterial geprägt und führt übersichtlich durch das Angebot der Fahrschule.",
    ],
  },
  {
    title: "Neuer Webauftritt für W-RS",
    slug: "website-w-rs",
    category: "Webdesign",
    date: "2024",
    image: "/news/w-rs.jpg",
    excerpt: "Durchdachter Webauftritt mit Fokus auf Übersicht und eine klare Nutzerführung.",
    body: [
      "Die W-RS Maschinenbau AG aus Wald ZH entwickelt und produziert Geräte für die Kanalsanierung und legt grossen Wert auf den Kundenservice.",
      "Wir durften die neue Webseite entwickeln – mit dem Ziel, die Produktauswahl ins Zentrum zu rücken und die Stärken des Maschinenbauunternehmens zeitgemäss zu kommunizieren.",
    ],
  },
  {
    title: "Website für servus",
    slug: "website-servus",
    category: "Webdesign",
    date: "2023",
    image: "/news/servus.jpg",
    excerpt: "Frischer Webauftritt, der die Marke servus klar und einladend ins Netz bringt.",
    body: [
      "Von Organisationsentwicklung und Leadership-Training über Business Consulting bis zu Coaching- und Sparring-Partnerschaften: Die Berater der servus GmbH begleiten internationale Konzerne, KMU und soziale Institutionen bei der Multiplikation von Fähigkeiten, Kompetenzen und Werten.",
      "Wir durften für und mit dem Unternehmen die brandneue Website entwickeln und umsetzen. Diese glänzt mit einer übersichtlichen Struktur sowie der Spür- und Sichtbarkeit des optimistischen Unternehmensgeistes.",
    ],
  },
  {
    title: "Neue Mitarbeiterin: Tamara",
    slug: "neue-mitarbeiterin-tamara",
    category: "Team",
    date: "2024",
    image: "/team/tamara.jpg",
    excerpt: "Neues Jahr, neue Woman Power: Tamara Schmutz verstärkt unser Team in Content Creation und Texterstellung.",
    body: [
      "Neues Jahr, neue Woman Power: Im Oktober 2023 fiel der Startschuss zu unserem Abenteuer Uftritt. Mit viel Freude dürfen wir bereits zu Beginn des neuen Jahres verkünden, dass eine talentierte Ergänzung zu unserem Team gestossen ist.",
      "Tamara Schmutz unterstützt uns mit ihrer Expertise im Bereich Content Creation und Texterstellung. Sie stellt sicher, dass Social-Media-Inhalte, Webseitentexte und weitere Kommunikationsmittel auf den Punkt und wirkungsvoll kommuniziert werden. Herzlich willkommen, Tamara!",
    ],
  },
  {
    title: "Wir feiern den Start von Uftritt",
    slug: "gruendung-uftritt",
    category: "Agentur",
    date: "September 2023",
    image: "/news/gruendung.jpg",
    excerpt: "Spezialisiert auf visuelle Kommunikation – Grafikdesign, Webentwicklung und Marketingbetreuung.",
    body: [
      "Wir sind stolz, die Gründung der Uftritt GmbH bekannt geben zu können! Unser Unternehmen ist auf visuelle Kommunikation spezialisiert und bietet umfassende Dienstleistungen in den Bereichen Grafikdesign, Webentwicklung und Marketingbetreuung – durchdacht, individuell und mit langfristiger Begleitung.",
      "Marcel Klausberger ist für die technische Umsetzung von Webseiten, Buchhaltung, Marketing und Akquisition verantwortlich. Jeffrey Meier ist unser kreatives Hirn und für die visuellen Kommunikationskonzepte zuständig.",
      "Wir freuen uns auf eine erfolgreiche Zusammenarbeit und darauf, gemeinsam starke Projekte umzusetzen.",
    ],
  },
];
