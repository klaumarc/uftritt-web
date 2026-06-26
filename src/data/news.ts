export interface NewsItem {
  title: string;
  slug: string;
  category: string;
  date: string;
  image: string;
  excerpt: string;
  body: string[];
  gallery?: string[]; // optionale weitere Bilder für die Detail-Galerie
  youtube?: string; // optionale YouTube-Video-ID
}

// Veröffentlichte Beiträge – Quelle: uftritt.ch/news (Live) + gelieferte Texte.
// Reihenfolge: neueste zuerst. Nur Originaltexte, keine KI-Platzhalter.
export const news: NewsItem[] = [
  {
    title: "natürli Käsemarkt",
    slug: "natuerli-kaesemarkt",
    category: "Agentur",
    date: "2026",
    image: "/news/natuerli-kaesemarkt.jpg",
    excerpt:
      "Wir öffnen unsere Schiebetüren am 1. Mai während der Dauer des natürli Käsemarktes.",
    body: [
      "Wir öffnen unsere Schiebetüren am 1. Mai während der Dauer des natürli Käsemarktes🧀 und wir freuen uns auf deinen Besuch!",
    ],
  },
  {
    title: "SAM focus",
    slug: "sam-focus",
    category: "Editorial",
    date: "2026",
    image: "/news/sam-focus.jpg",
    excerpt:
      "serve and multiply – wir übernehmen neu die redaktionelle Arbeit und Gestaltung des Magazins SAM focus.",
    body: [
      "serve and multiply – dies ist der Grundsatz der christlichen NGO SAM global. Das Magazin SAM focus oder SAM allons im westschweizerischen Raum, ist nach wie vor ein wichtiges Kommunikationsorgan des schweizerischen Hilfswerks. In enger Zusammenarbeit mit dem Kommunikationsteam dürfen wir neu die redaktionelle Arbeit und die Gestaltung des Magazins übernehmen.",
      "Damit dieses auch visuell weiterhin attraktiv bleibt für die Lesenden, haben wir ein zeitgemässes Redesign umgesetzt. Das Magazin glänzt nun mit einem luftigen Layout, einem ausgewogenen Bild-Text-Verhältnis, klaren Texthierarchien und passenden Illustrationen.",
      "Auf der Website von SAM global können die Ausgaben im frischen Kleid mit spannenden Berichten gelesen oder das Magazin gleich abonniert werden > www.sam-global.org",
    ],
  },
  {
    title: "Pink bewegt Hinwil",
    slug: "emotion-hinwil",
    category: "Corporate Design",
    date: "Juni 2025",
    image: "/news/emotion-hinwil.jpg",
    excerpt:
      "Für den Unihockeyverein emotion Hinwil haben wir das Erscheinungsbild von Grund auf neu entwickelt.",
    body: [
      "Der Unihockeyverein emotion Hinwil mit seiner markanten Vereinsfarbe ist bereits seit 2011 mit verschiedenen Teams erfolgreich in den Ligen von swiss unihockey unterwegs – mit dem Herren 1 Team sogar in der höchsten Kleinfeld-Liga der Schweiz. Das bisherige Erscheinungsbild war jedoch in die Jahre gekommen und aus diesem Grund galt es dieses von Grund auf neu zu entwickeln.",
      "Für uns als Unihockeyfans war es eine grosse Freude, dass wir in Zusammenarbeit mit dem Verein ein neues Logo, neue Gestaltungselemente und Design-Richtlinien für die verschiedenen Kommunikationskanäle wie Social Media, Printmedien und Kleidung entwickeln durften. Dabei wurde an der starken, identitätsstiftenden Farbe Pink festgehalten, wobei der Farbton frischer und moderner überarbeitet wurde. An der Mitgliederversammlung im Juni wurde das Rebranding dem ganzen Verein vorgestellt.",
    ],
  },
  {
    title: "Baumerziitig",
    slug: "baumerziitig",
    category: "Agentur",
    date: "2025",
    image: "/news/baumerziitig.jpg",
    excerpt:
      "In der Ausgabe 20/2025 der Baumerziitig stellen wir uns anlässlich unseres Umzuges nach Saland vor.",
    body: [
      "In der Ausgabe 20/2025 der Baumerziitig findest du bekannte Gesichter🤓😊 mit einem Bericht, in dem wir uns anlässlich unseres Umzuges nach Saland der Baumer Bevölkerung kurz vorstellen.",
      "👉 baumerziitig.ch",
    ],
  },
  {
    title: "Erscheinungsbild für Immobilie in Zürich",
    slug: "spinnereiplatz",
    category: "Webdesign",
    date: "2025",
    image: "/news/spinnereiplatz.jpg",
    excerpt:
      "Für eine aussergewöhnliche Wohnung in der ehemaligen Spinnerei Mannegg entwickelten wir eine Verkaufs-Website.",
    body: [
      "Ein schmucke Oase in der Grossstadt🏡",
      "Die ehemalige Spinnerei Mannegg am Spinnereiplatz 1 steht als beeindruckender Zeitzeuge mitten im modernen Zürcher Quartier Green City. Der geschichtsträchtige Bau wird heute als Wohngebäude genutzt wozu auch eine grosszügige und aussergewöhnlich gestaltete Wohnung in der 3. Etage gehört.",
      "Um das leerstehende Objekt für den Verkauf optimal zu bewerben und in das beste Licht zu rücken, durften wir in Zusammenarbeit mit dem zuständigen Makler eine informative und anschauliche Website entwickeln. Diese lebt unter anderem von den – durch uns gemachten – hochwertigen Fotos der Räumlichkeiten, welche die vielen Vorzüge und die einmalige Atmosphäre der Wohneinheit und deren Umgebung zum Ausdruck bringen. Zudem verfassten wir kurze, informative Texte.",
    ],
  },
  {
    title: "Von Rüti ZH nach Saland",
    slug: "umzug-saland",
    category: "Agentur",
    date: "April 2025",
    image: "/news/umzug-saland.jpg",
    excerpt:
      "Wir beziehen ein neues Büro im Tösstal – bei den Festhütten in Saland.",
    body: [
      "Wir beziehen eine neue Station im Zürcher Oberland! 🥳",
      "Mit der Startphase in Rüti durften wir eine wertvolle Grundlage für unsere Agentur legen und wir sind dankbar dafür. Nun nehmen wir die nächste Stufe und werden ab 1. April 2025 ein schönes neues Büro im Tösstal, genauer gesagt bei den Festhütten in Saland beziehen. Der Charme von Schindler & Scheibling Bauwerk wird unser kreatives Schaffen mit Sicherheit sehr positiv beeinflussen. Das Büro teilen wir uns mit dem it-stall, Partner für Netzwerk, Support und IoT.",
      "Stay tuned, sobald wir eingerichtet sind, zeigen wir dir hier mehr Impressionen! 📷",
    ],
  },
  {
    title: "Corporate Identity in Grüningen",
    slug: "smart-navigator",
    category: "Corporate Design",
    date: "2025",
    image: "/news/smart-navigator.jpg",
    excerpt:
      "Für die Unternehmensberatung smart navigator bauten wir die Marke von Grund auf – Name, Logo, Briefschaften, Website.",
    body: [
      "Auf klarem Kurs zum Ziel ⛵",
      "So werden Besucherinnen und Besucher der Anfang diesen Jahres gelaunchten Webseite der Unternehmensberatung smart navigator begrüsst.",
      "Der smart navigator selbst, Hans-Peter Bareth, ist Organisationsentwickler, Sparring-Partner und Trainer für vielfältige unternehmerische Fragestellungen. Mit seinem reichen Erfahrungsschatz ist er ein wertvolles Gegenüber, das eine gesunde und erfolgreiche Entwicklung von Firmen und Organisationen begleitet und fördert.",
      "Seine Leidenschaft für den Segelsport und damit zusammenhängende Narrative und Prinzipien sind das leitende Motiv im Erscheinungsbild von smart navigator. Für den Aufbau seiner Marke durften wir den Kunden von Beginn weg begleiten – von der Namensfindung, der Entwicklung eines Logos, den Briefschaften bis zum Aufbau einer informativen Webseite.",
    ],
  },
  {
    title: "Unser Podcast «Ein Jahr Selbstständigkeit»",
    slug: "podcast-selbststaendigkeit",
    category: "Agentur",
    date: "2025",
    image: "/news/podcast-selbststaendigkeit.jpg",
    excerpt:
      "Part 2 ist da: Marcel und Jeffrey blicken zurück auf das erste Jahr der Selbstständigkeit.",
    body: [
      "Get ready for part 2! (mit ein wenig Verspätung) 🎙️",
      "Höre dir jetzt auf Spotify die neue Podcast-Folge an: Uf Tritt und Schritt mit Uftritt.",
      "In der neuen Folge blicken wir, Marcel und Jeffrey, zurück auf unser erstes, gut überstandenes Jahr der Selbstständigkeit. Erfahre, wer den ersten Schritt gemacht hatte, wieso wir grün hinter den Ohren sind und welches die Highlights der hinter uns liegenden Zeit sind.",
    ],
  },
  {
    title: "Erscheinungsbild für Architekturunternehmen",
    slug: "architekturmacher",
    category: "Corporate Design",
    date: "2025",
    image: "/news/architekturmacher.jpg",
    excerpt:
      "Für die Architekturmacher GmbH entwickelten wir eine elegante Wortmarke und ein stimmiges Erscheinungsbild.",
    body: [
      "Wenn sich ein erfahrener Architekt selbständig macht, muss das Äussere unbedingt der hohen Qualität entsprechen, denn auch Ästhetik spielt in der Architektur eine grosse Rolle.",
      "Für die Architekturmacher GmbH und ihren Inhaber Lukas Pfister entwickelten wir eine elegante Wortmarke, definierten eine kräftige Corporate-Farbe und ein passendes Schriftenkonzept. Das Unternehmen wurde ausserdem mit Geschäftspapieren wie Visitenkarten, Briefbogen und Couvert ausgestattet.",
      "Verschaffe dir in der Bildstrecke einen Eindruck von dem neuen Erscheinungsbild!",
    ],
  },
  {
    title: "Standortförderung Zürioberland",
    slug: "standortfoerderung",
    category: "Agentur",
    date: "2025",
    image: "/news/standortfoerderung.jpg",
    excerpt:
      "Wir zählen neu zu den Mitgliedern der Standortförderung Zürioberland.",
    body: [
      "Für die Region",
      "Das Zürcher Oberland ist unser Zuhause und liegt uns am Herzen. Da wir gemeinsam mit vielen anderen Unternehmen den regionalen Wirtschafts- und Lebensraum stärken möchten, zählen wir uns seit kurzem zu den Mitgliedern der Standortförderung Zürioberland.",
      "Schau doch mal auf unserem Unternehmensprofil auf der Webseite der Standortförderung vorbei: www.zuerioberland.ch",
    ],
  },
  {
    title: "Broschüre für Non-Profit",
    slug: "yi-broschuere",
    category: "Grafikdesign",
    date: "Oktober 2024",
    image: "/news/yi-broschuere.jpg",
    excerpt:
      "Eine Fundraising-Broschüre für Youngstars International – konzipiert, getextet und gestaltet.",
    body: [
      "Eine Broschüre, ein Flyer oder eine Visitenkarte können am Bildschirm noch so ansprechend aussehen, wenn der Druck nicht stimmt, verliert das ganze Produkt.",
      "Unsere Drucksachen lassen wir deshalb bei unserer Druckerei des Vertrauens produzieren: der dt druck-team ag in Wetzikon. Vielen Dank für die gute Zusammenarbeit!",
      "Die abgebildete und bei der DT Druck-Team AG produzierte Broschüre durften wir als Fundraising-Werkzeug für Youngstars International konzipieren, verfassen und gestalten. Youngstars International baut Jungscharverbände auf der ganzen Welt aus und auf. Durch ihre wichtige Arbeit erfahren Kinder weltweit, was wirkliches Miteinander bedeutet, sie lernen Verantwortung zu übernehmen und ihr Potenzial auszuschöpfen.",
    ],
  },
  {
    title: "Branding für IT-Unternehmen",
    slug: "it-stall",
    category: "Corporate Design",
    date: "Oktober 2024",
    image: "/news/it-stall.jpg",
    excerpt:
      "Für Martin Brunner vom it-stall entwickelten wir Erscheinungsbild und Webseite.",
    body: [
      "Hast du schon einmal versucht, eine 100kg schwere Stalltüre per App öffnen zu lassen? Oder die Garagentür so zu programmieren, dass sie bei deiner Einfahrt selbst aufgeht?",
      "Für genau solche Projekte ist Martin Brunner vom it-stall dein Partner. Sein Traum, dass aus «Was-wäre-wenn»-Wünschen funktionale it-Lösungen werden, ist nun in ein von uns entwickeltes Erscheinungsbild gehüllt sowie mit einer durch uns gestalteten und getexteten Webseite ausgerüstet.",
      "Vielen Dank für das Vertrauen, Martin Brunner!",
      "Eine verrückte IT-Idee im Kopf? Hier geht's zum it-Stall: it-stall.ch",
    ],
  },
  {
    title: "Wichtige Partnerschaft",
    slug: "ratsam",
    category: "Agentur",
    date: "September 2024",
    image: "/news/ratsam.jpg",
    excerpt:
      "Bevor man an den neuen Auftritt denkt, ist es ratsam, seine Identität zu kennen – mit Sam Urech.",
    body: [
      "Bevor man an den neuen #uftritt denkt, ist es #ratsam seine Identität zu kennen.",
      "Wer bin ich? Was macht mich aus? Diese Fragen sollten immer am Anfang einer Unternehmensgründung, eines Rebrandings, einer neuen Webseite oder eines Imagefilmes stehen. Gut, dass wir uns mit diesen Fragen jetzt an einen echten Experten wenden können: Sam Urech – Potenzialentdecker, Identitätsfinder und Kommunikationsberater bei ratsam.",
      "Sam legt die Grundlage für einen authentischen kommunikativen Auftritt, indem er einem Unternehmen auf den Zahn fühlt und den Herzschlag herausschält. Ist die Identität geklärt, kümmern wir uns um den nächsten Schritt: den passenden visuellen Auftritt und die Umsetzung von Kommunikationsmassnahmen.",
      "Wir freuen uns auf dein Wissen, dein Feingefühl, dein ehrliches Interesse an deinem Gegenüber und vor allem auf die Zusammenarbeit mit dir, Sam. Schön, dürfen wir mit dir unterwegs sein.",
    ],
  },
  {
    title: "Webseite für Ferienhaus",
    slug: "regan",
    category: "Webdesign",
    date: "August 2024",
    image: "/news/regan.jpg",
    excerpt:
      "Für das Ferienhaus Regan in Obersaxen konzipierten, gestalteten und texteten wir die Webseite.",
    body: [
      "Eichen sollst du weichen, Buchen sollst du suchen. 🌳",
      "Wir würden beide (be)suchen – zumindest wenn es um das Ferienhaus Regan in Obersaxen geht. Denn sowohl die Esche, als auch die Buche, der Ahorn, die Ulme und weitere edle Holzarten sind da stilvoll eingerichteten Ferienwohnungen, die es sich zu suchen lohnt.",
      "Einen guten Einblick in die Heimat auf Zeit findest du jetzt auf der von uns konzipierten, gestalteten und getexteten Webseite: www.regan.swiss",
      "Wir wünschen: Erholsame Ferien!",
    ],
  },
  {
    title: "KI als Kommunikationswerkzeug",
    slug: "ki",
    category: "Agentur",
    date: "2024",
    image: "/news/ki.jpg",
    excerpt:
      "KI revolutioniert die Kommunikation – menschliche Expertise bleibt unverzichtbar.",
    body: [
      "KI revolutioniert die Kommunikation, aber menschliche Expertise bleibt unverzichtbar!",
      "🤖 + 🧠 = 💡",
      "Während KI-Tools die Effizienz steigern und Datenanalyse optimieren, bringen Kommunikationsexpertinnen und -experten einzigartige Kreativität, strategisches Denken und emotionale Intelligenz ein. Sie verstehen kulturelle Nuancen, bauen echte Kundenbeziehungen auf und lösen komplexe Probleme. Für uns ist klar: Die Zukunft liegt in der perfekten Symbiose: KI als leistungsstarkes Werkzeug, gesteuert von menschlicher Expertise und Verantwortung!",
    ],
  },
  {
    title: "Produktefilm für Sportgetränk",
    slug: "empower",
    category: "Video",
    date: "2024",
    image: "/news/empower.jpg",
    excerpt:
      "Für das EMPOWER High Carb Pulver entwickelten wir einen kraftvollen Produktfilm.",
    body: [
      "Empower your performance!",
      "Ein starkes Sportgetränk braucht eine starke Plattform. Das EMPOWER High Carb Pulver sorgt für langanhaltende Energie während des Trainings und schmeckt erfrischend dazu. Um das Pulvergetränk zu promoten, durften wir in Zusammenarbeit mit den Gründern Stefan Müller und Angie Müller von Crossfit Zug einen kraftvollen Produktfilm entwickeln und umsetzen. Hauptakteure waren die Sportlerinnen und Sportler, für welche das Pulvergetränk entwickelt wurde. Überzeuge dich selbst vom Video – und dem Getränk.",
      "Und was möchtest du mit uns ins beste Licht rücken?",
    ],
  },
  {
    title: "Hallo Christian Angst",
    slug: "christian",
    category: "Team",
    date: "2024",
    image: "/news/christian.jpg",
    excerpt:
      "Christian Angst ergänzt unser Team im Bereich Videografie.",
    body: [
      "Das Uftritt-Team wächst!",
      "Christian Angst ergänzt uns seit Anfang April mit seinen Skills im Bereich Videografie. Von prägnanten Social-Media-Inhalten über Produktvideos hin zu aussagekräftigen Imagefilmen – wir freuen uns darauf, mit ihm die spannenden Geschichten und Botschaften unserer Kundschaft im Bewegtbild zu erzählen!",
      "Film ab für Christian Angst!",
    ],
  },
  {
    title: "Branding in Hinwil",
    slug: "cynterim",
    category: "Corporate Design",
    date: "2024",
    image: "/news/cynterim.jpg",
    excerpt:
      "Für die Cynterim GmbH gestalteten wir Logo, Branding und Website zum Markteinstieg.",
    body: [
      "Wenn, dann gleich richtig.",
      "Es zahlt sich aus, den visuellen Auftritt bereits bei der Firmengründung ernst zu nehmen. Die Cynterim GmbH aus Hinwil hat sich dies zu Herzen genommen. So durften wir das Salesforce-Partnerunternehmen für den Markteinstieg mit der Kreation des Logos, dem Branding und einer Website unterstützen. Entstanden ist dabei ein dynamisches, prägnantes Erscheinungsbild, das sich auf allen Kanälen stringent anwenden lässt.",
      "Auf der Website erhältst du einen umfangreichen Einblick: cynterim.ch",
      "Du stehst ebenfalls vor dem Schritt in die Selbständigkeit oder dein Unternehmen benötigt einen zeitgemässen Neuanstrich? Wir möchten dich gerne auf dem Weg zum Erfolg begleiten.",
    ],
  },
  {
    title: "Webentwicklung in Pfäffikon ZH",
    slug: "ripa",
    category: "Webdesign",
    date: "2024",
    image: "/news/ripa.jpg",
    excerpt:
      "Für die RIPA Immobilien GmbH entwickelten wir Webseite, Fotoshooting und Texte.",
    body: [
      "Mehr als nur eine neue Fassade",
      "Wenn es um Immobilienverwaltung und -vermittlung mit langjähriger Erfahrung geht, ist die RIPA Immobilien GmbH in Pfäffikon ZH die kompetente Partnerin im Zürcher Oberland.",
      "Mit einer neu entwickelten Webseite verpassten wir der RIPA Immobilien GmbH digital einen frischen Anstrich, sodass die individuellen Verwaltungslösungen und das Engagement des Unternehmens bestens zur Geltung kommen.",
      "Auch für die Feinausstattung hinter der Fassade waren wir verantwortlich: Die beim Fotoshooting entstandenen Bilder erlauben neu einen authentischen Blick in das Unternehmen und die überarbeiteten Texte bringen die fachlichen Kompetenzen der Immobilienverwaltung zielgerichtet auf den Punkt.",
      "Überzeuge dich selbst: ripa-immobilien.ch",
    ],
  },
  {
    title: "Visuelle Kommunikation in Hinwil",
    slug: "stahel-koeng",
    category: "Grafikdesign",
    date: "2024",
    image: "/news/stahel-koeng.jpg",
    excerpt:
      "Zum 125-Jahr-Jubiläum der Stahel+Köng AG entwickelten wir ein Jubiläumslogo.",
    body: [
      "Ein bemerkenswertes Jubiläum",
      "Das traditionsreiche Unternehmen und Massivholzspezialist Stahel+Köng AG aus Hinwil feiert 2024 sein 125-jähriges Bestehen. Dem grossen Anlass entsprechend haben wir ein Logo entwickelt, das im Jubiläumsjahr verwendet wird. Es kommt unter anderem auf einer edlen, hochwertigen Getränkeflasche zum Einsatz, die als Geschenk an die Kundschaft abgegeben wird.",
      "Steht bei dir auch bald ein Jubiläum an? Dann unterstützen wir dich gerne bei der Umsetzung von kreativen Kommunikationsmitteln, die diesen Anlass würdig präsentieren.",
    ],
  },
  {
    title: "Fahrschule Schweiz",
    slug: "fahrschule-schweiz",
    category: "Corporate Design",
    date: "2024",
    image: "/news/fahrschule-schweiz.jpg",
    excerpt:
      "Für die Motorradfahrschule «Fahrschuleschweiz.ch» entwickelten wir Logo, Webseite und Bildmaterial.",
    body: [
      "Die Motorradfahrschule «Fahrschuleschweiz.ch» aus Sursee fährt mit frischem Erscheinungsbild auf.",
      "Vom Logo über die Webseite zu Fotos und kurzen Filmausschnitten für den Webauftritt durften wir den neuen Auftritt entwickeln und realisieren.",
      "Für das neue Logo haben wir das Schweizerkreuz, das Lehrfahr-«L» sowie die Farben aus dem bisherigen Logo aufgenommen und zu einer dynamischen und einzigartigen Bildmarke kombiniert. Diese kommt auch auf dem gut besuchten YouTube-Kanal der Fahrschule zum Einsatz.",
      "Die neue Website wird durch das extra hergestellte Bild- und Videomaterial geprägt und führt die Besucherinnen und Besucher übersichtlich durch das Angebot der Fahrschule.",
    ],
  },
  {
    title: "Webauftritt in Bauma",
    slug: "physio-rothe",
    category: "Webdesign",
    date: "2024",
    image: "/news/physio-rothe.jpg",
    excerpt:
      "Ein frischer Webauftritt inkl. Fotoshooting für die Physiotherapie & Sport-Reha in Bauma.",
    body: [
      "Vorhang auf für den neuen Webauftritt der Physiotherapie & Sport-Reha in Bauma!",
      "Die Therapeutinnen und Therapeuten der Physiopraxis begleiten ihre Klientinnen und Klienten durch sorgfältige Analyse, individuelle Beratung und regelmässige Überprüfung zum körperlichen Wohlbefinden. Nun ist die Praxis auch mit ihrem Internetauftritt fit unterwegs.",
      "Zusammen mit Inhaber und Geschäftsführer Michael Rothe haben wir die Struktur der Webseite optimiert und für ein frisches und aktuelles Erscheinungsbild gesorgt, das mit der Konkurrenz Schritt hält.",
      "Nicht nur die neue Webseite stammt aus unserer Feder, auch die verschiedenen Therapiebehandlungen und die Räumlichkeiten der Praxis durften wir fotografisch in Szene setzen und auf der Webseite integrieren.",
      "Schau hier bei der neuen Physio-Welt vorbei: www.sports-physio.ch",
    ],
  },
  {
    title: "Hallo Tamara Schmutz",
    slug: "tamara",
    category: "Team",
    date: "2024",
    image: "/news/tamara.jpg",
    excerpt:
      "Tamara Schmutz unterstützt uns im Bereich Content Creation und Texterstellung.",
    body: [
      "Neues Jahr, neue Woman Power",
      "Im Oktober 2023 fiel der Startschuss zu unserem Abenteuer Uftritt. Mit viel Freude dürfen wir bereits zu Beginn des neuen Jahres verkünden, dass eine talentierte Ergänzung zu unserem Team gestossen ist. Tamara Schmutz wird uns mit ihrer Expertise im Bereich Content Creation und Texterstellung unterstützen. Sie stellt sicher, dass Social-Media-Inhalte, Webseitentexte und weitere Kommunikationsmittel auf den Punkt und wirkungsvoll kommuniziert werden.",
      "Herzlich willkommen Tamara!",
    ],
  },
  {
    title: "Webentwicklung im Maschinenbau",
    slug: "w-rs",
    category: "Webdesign",
    date: "November 2023",
    image: "/news/w-rs.jpg",
    excerpt:
      "Ein neuer Webauftritt für die W-RS Maschinenbau AG aus Wald ZH.",
    body: [
      "Ein neuer Webauftritt für die W-RS Maschinenbau AG",
      "Die W-RS Maschinenbau AG aus Wald ZH entwickelt und produziert Geräte für die Kanalsanierung und legt grossen Wert auf den Kundenservice. Wir durften die neue Webseite entwickeln mit dem Ziel, die Produktauswahl ins Zentrum zu rücken und zeitgemäss die Stärken des Maschinenbauunternehmens zu kommunizieren.",
      "Hier kannst du dir einen eigenen Eindruck verschaffen: www.w-rs.ch",
    ],
  },
  {
    title: "Saison Guide",
    slug: "dt-baeretswil",
    category: "Editorial",
    date: "Oktober 2023",
    image: "/news/dt-baeretswil.jpg",
    excerpt:
      "Dem Saison Guide des Unihockeyvereins DT Bäretswil verliehen wir einen neuen Anstrich.",
    body: [
      "Wir können auch Sport.",
      "Der Unihockeyverein DT Bäretswil ist mit der Herren-1-Mannschaft in der höchsten Kleinfeldliga der Schweiz unterwegs und mit einem weiteren Team in der 3. Liga sowie mit einer A-Junioren-Mannschaft vertreten. Der Verein bringt für jede Saison eine informative gedruckte Broschüre heraus, welche einen Rückblick auf die vergangene Saison beinhaltet, Ausblicke auf die Ziele der verschiedenen Teams präsentiert und Einblicke in das Vereinsleben und -wesen gewährt. Der Spielplan für alle Teams ist als heraustrennbare Doppelseite in der Heftmitte integriert.",
      "Im Zuge des neuen Auftritts, welcher dieses Jahr realisiert wurde, durften wir dem Saison Guide (auch Vereinsbroschüre) einen neuen Anstrich verleihen. Ziel war es, das Layout lesefreundlicher und leichter zu gestalten sowie klare Text-Bild-Strukturen zu entwickeln.",
      "Let's go Bäri!",
    ],
  },
  {
    title: "Das Team",
    slug: "team",
    category: "Team",
    date: "Oktober 2023",
    image: "/news/team.jpg",
    excerpt:
      "Uftritt – das sind wir: Marcel Klausberger und Jeffrey Meier.",
    body: [
      "Uftritt – das sind wir, Marcel Klausberger und Jeffrey Meier.",
      "Marcel ist die Schaltzentrale der Agentur. Ob kreatives Visionieren, initiatives Vorantreiben von Ideen, Buchhaltung, Akquise oder Netzwerken, er verleiht Uftritt den Auftrieb. Der Familienvater ist als Webentwickler zudem dafür verantwortlich, dass die Webprojekte unserer Kundschaft zum vollen Erfolg werden. Dafür wählt er gerne neue und individuelle Wege, die auf die Vielfalt der Menschen und deren Anforderungen eingehen.",
      "Jeffrey ist das kreative Hirn von Uftritt. Er konzipiert und kreiert visuelle Auftritte, die den jeweiligen Ansprüchen exakt auf den Körper geschneidert sind. Als leidenschaftlicher Musiker bringt er auch die trockenste Materie gestalterisch zum Klingen und sorgt dafür, dass sich Kundinnen und Kunden mit Stolz präsentieren können – sei es eine Visitenkarte oder das gesamte Erscheinungsbild.",
      "Und wer bist du? Wir freuen uns darauf, dich einmal unverbindlich kennenzulernen. Schreib uns dazu einfach eine Nachricht.",
    ],
  },
  {
    title: "Servus Webseite",
    slug: "servus",
    category: "Webdesign",
    date: "Oktober 2023",
    image: "/news/servus.jpg",
    excerpt:
      "Für die servus GmbH entwickelten wir die brandneue Website inkl. Portraitshooting.",
    body: [
      "servus setzt Potenziale frei!",
      "Von Organisationsentwicklung und Leadership-Training über Business Consulting zu Coaching/Sparring-Partnerschaften – Die Berater der servus GmbH begleiten und unterstützen internationale Konzerne, KMU oder soziale Institutionen bei der Multiplikation von Fähigkeiten, Kompetenzen und Werten.",
      "Wir durften für und mit dem Unternehmen ihre brandneue Website entwickeln und umsetzen inklusive Portraitshooting der Mitarbeitenden. Die Website glänzt mit einer übersichtlichen Struktur sowie der Spür- und Sichtbarkeit des optimistischen Unternehmensgeistes.",
      "Verschaffe dir selbst einen Eindruck: www.servus.ch",
    ],
  },
  {
    title: "Gründung",
    slug: "gruendung",
    category: "Agentur",
    date: "Oktober 2023",
    image: "/news/gruendung.jpg",
    excerpt:
      "Wir feiern den Start von Uftritt – spezialisiert auf visuelle Kommunikation.",
    body: [
      "🎉 Wir feiern den Start von Uftritt! 🎉",
      "Liebe Freunde, Bekannte, Geschäftspartner und Unternehmer",
      "Wir sind stolz darauf, euch heute die Gründung der Uftritt GmbH bekannt geben zu können! Unser Unternehmen ist auf visuelle Kommunikation spezialisiert und bietet umfassende Dienstleistungen in den Bereichen Grafikdesign, Webentwicklung und Marketingbetreuung an.",
      "Marcel Klausberger ist für die technische Umsetzung von Webseiten, Buchhaltung, Marketing und Akquisition verantwortlich. Jeffrey Meier ist unser kreatives Hirn und für die visuellen Kommunikationskonzepte zuständig.",
      "Wir würden uns freuen, euch bei einer Tasse Kaffee persönlich kennenzulernen. Lasst uns vernetzen und gemeinsam starke Projekte umsetzen!",
    ],
  },
];
