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
    role: "PALIMED AG",
    rating: 5,
    text: "Es war eine Freude, mit Marcel und seinem Team zusammenzuarbeiten. Wir haben uns «nur» via Zoom, Telefon und Mail ausgetauscht und das lief super. Marcel ist auf die Kundenwünsche eingegangen und hat sie kreativ umgesetzt. Ich habe die unkomplizierte Zusammenarbeit und die raschen Rückmeldungen sehr geschätzt!",
  },
  {
    author: "Michael Dufner",
    role: "SAM Global",
    rating: 5,
    text: "Mit Uftritt ist die Zusammenarbeit super angenehm, unkompliziert und zuvorkommend. Ihre persönliche Art, die Nähe zu uns, aber auch ihre Profession zu unserer Arbeit haben mich überzeugt. Ich liebe die Zusammenarbeit mit Uftritt!!!",
  },
  {
    author: "Dominique Ziegler",
    role: "Seatalytics",
    rating: 5,
    text: "Die Zusammenarbeit mit Uftritt war unkompliziert und angenehm. Sie haben sich Zeit genommen für meine Wünsche und die Webseite sehr zügig umgesetzt. Ich bin mit dem Ergebnis wirklich sehr zufrieden und empfehle sie gerne weiter.",
  },
  {
    author: "Marco Zolliker",
    role: "zollikhair GmbH",
    rating: 5,
    text: "Uftritt überzeugt mich mit kompetenter Beratung. Meine Homepage wurde überarbeitet und auf den aktuellen Stand gebracht. Super einfache Kontaktaufnahme, gute Gespräche und das zu mir passende Know-how. Ich bin sehr zufrieden.",
  },
  {
    author: "Raphael Schmid",
    role: "Emotions Hinwil",
    rating: 5,
    text: "Nachdem wir uns entschieden hatten, unser Erscheinungsbild zu überarbeiten, konnten wir mit Uftritt den perfekten Partner ins Boot holen. Die Zusammenarbeit war sehr unkompliziert. Besonders positiv überrascht hat uns die Art, auf Wünsche einzugehen und diese in einen bestehenden Entwurf einzuarbeiten. So ist ein hervorragendes Logo entstanden, das perfekt auf uns abgestimmt ist und unsere Identität bildlich widerspiegelt. Wir können diesen Service mit bestem Gewissen weiterempfehlen, denn uns ist bewusst, wie wichtig eine natürliche Zusammenarbeit und die Entwicklung einer neuen visuellen Identität für ein nicht nur schönes, sondern auch passendes Logo ist.",
  },
];
