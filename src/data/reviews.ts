export interface Review {
  author: string;
  role?: string;
  rating: number; // 1–5
  text: string;
}

// Echte Google-Bewertungen (Uftritt GmbH). Alle 5 Sterne, Schnitt 5.0.
// Hier eine kuratierte Auswahl für die Startseite – weitere können auf einer
// eigenen Seite oder später via Google Places API automatisch ergänzt werden.
export const reviewsMeta = {
  average: 5.0,
  count: 13,
  source: "Google",
  placeholder: false,
};

export const reviews: Review[] = [
  {
    author: "Ladina Defila",
    rating: 5,
    text: "Es war eine Freude, mit Marcel und seinem Team zusammenzuarbeiten. Wir haben uns «nur» via Zoom, Telefon und Mail ausgetauscht und das lief super. Marcel ist auf die Kundenwünsche eingegangen und hat sie kreativ umgesetzt. Ich habe die unkomplizierte Zusammenarbeit und die raschen Rückmeldungen sehr geschätzt!",
  },
  {
    author: "Michael Dufner",
    rating: 5,
    text: "Mit Uftritt ist die Zusammenarbeit super angenehm, unkompliziert und zuvorkommend. Ihre persönliche Art, die Nähe zu uns, aber auch ihre Profession zu unserer Arbeit haben mich überzeugt. Ich liebe die Zusammenarbeit mit Uftritt!!!",
  },
  {
    author: "Kobithan Yoganathan",
    rating: 5,
    text: "Seine sympathische und hilfsbereite Art macht jede Zusammenarbeit angenehm. Kommunikation auf Augenhöhe, unkompliziert und immer lösungsorientiert – genau so wünscht man sich einen Partner. Auf Marcel kann man sich jederzeit verlassen.",
  },
  {
    author: "Dominique Ziegler",
    rating: 5,
    text: "Die Zusammenarbeit mit Uftritt war unkompliziert und angenehm. Sie haben sich Zeit genommen für meine Wünsche und die Webseite sehr zügig umgesetzt. Ich bin mit dem Ergebnis wirklich sehr zufrieden und empfehle sie gerne weiter.",
  },
  {
    author: "Eric Wetzel",
    rating: 5,
    text: "Professionelle Beratung von Herrn Klausberger. Haben in kurzer Zeit eine wunderschöne und top moderne Homepage für uns erstellt. Immer wieder gern.",
  },
  {
    author: "Marco Zolliker",
    role: "zollikhair GmbH",
    rating: 5,
    text: "Uftritt überzeugt mich mit kompetenter Beratung. Meine Homepage wurde überarbeitet und auf den aktuellen Stand gebracht. Super einfache Kontaktaufnahme, gute Gespräche und das zu mir passende Know-how. Ich bin sehr zufrieden.",
  },
];
