export interface TeamMember {
  name: string;
  role: string;
  photo: string;
  // optionales Alternativbild fuer die Lightbox (Kern-Team: Marcel, Jeffrey, Sarah);
  // ohne Angabe zeigt die Lightbox das normale photo
  photoLarge?: string;
  email: string;
  bio: string;
  // true = externer Partner (Christian, Sam) -> "Partner"-Badge im Grid + Lightbox
  partner?: boolean;
}

// Inhalte an der bestehenden Website orientiert, leicht überarbeitet.
// Später aus Sanity (Collection "Mitarbeiter") befüllbar.
export const team: TeamMember[] = [
  {
    name: "Marcel Klausberger",
    role: "Webentwickler",
    photo: "/team/marcel-klausberger-portrait.jpg",
    photoLarge: "/team/marcel-klausberger-portrait-lightbox.jpg",
    email: "mk@uftritt.ch",
    bio: "Marcel sammelte während vielen Jahren als selbstständiger Webentwickler umfangreiche Erfahrung und hat Projekte aus den unterschiedlichsten Branchen umgesetzt. Er schätzt die Vielfalt der Menschen, mit denen er zusammenarbeitet. Seine Freizeit verbringt er am liebsten mit seiner Familie.",
  },
  {
    name: "Jeffrey Meier",
    role: "Grafikdesigner",
    photo: "/team/jeffrey-meier-portrait.jpg",
    photoLarge: "/team/jeffrey-meier-portrait-lightbox.jpg",
    email: "jm@uftritt.ch",
    bio: "Die Ausbildung und Tätigkeit als Polygraf legten für Jeffrey ein solides Fundament für sein späteres HF-Studium der Visuellen Gestaltung. Mit seiner Leidenschaft für durchdachtes Design kreiert er wirkungsvolle und zielgerichtete Gestaltungskonzepte. Daneben begleitet ihn das Musikmachen bereits seit jungen Jahren und bei unterschiedlichsten sportlichen Aktivitäten findet er seinen Ausgleich.",
  },
  {
    name: "Sarah Link",
    role: "Redaktion und Text",
    photo: "/team/sarah-link-portrait.jpg",
    photoLarge: "/team/sarah-link-portrait-lightbox.jpg",
    email: "info@uftritt.ch",
    bio: "Sarah bringt Texte auf den Punkt. Als Redaktorin des Magazins SAM focus verantwortet sie den roten Faden, vom Themenkonzept über Titel und Leads bis zum Gut zum Druck. Bei Uftritt kümmert sie sich um Redaktion und Text.",
  },
  {
    name: "Christian Angst",
    partner: true,
    role: "Videograf",
    photo: "/team/christian-angst-portrait.jpg",
    email: "info@uftritt.ch",
    bio: "Christian hat seine Kamerafähigkeiten durch eine Fachausbildung und mehrere Praktika professionalisiert. Unkompliziert und mit gutem Gespür setzt er Imagefilme, Produktfilme und Social-Media-Videos um. Seine freie Zeit verbringt er mit seiner Familie und der Musik.",
  },
  {
    name: "Sam Urech",
    partner: true,
    role: "Kommunikationsberater",
    photo: "/team/sam-urech-portrait.jpg",
    email: "info@uftritt.ch",
    bio: "Sam macht Authentizität sichtbar, indem er die richtigen Fragen stellt: Wer bin ich? Was macht mich aus? Als Geschäftsführer von ratsam unterstützt er bei Identitäts- und Kommunikationsfragen und hilft dabei, den eigenen Kern zu finden.",
  },
];
