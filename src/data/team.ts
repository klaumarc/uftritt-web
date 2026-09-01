export interface TeamMember {
  name: string;
  role: string;
  photo: string;
  email: string;
  bio: string;
  // true = externer Partner (Christian, Tamara, Sam) -> "Partner"-Badge im Grid + Lightbox
  partner?: boolean;
}

// Inhalte an der bestehenden Website orientiert, leicht überarbeitet.
// Später aus Sanity (Collection "Mitarbeiter") befüllbar.
export const team: TeamMember[] = [
  {
    name: "Marcel Klausberger",
    role: "Webentwickler",
    photo: "/team/marcel.jpg",
    email: "mk@uftritt.ch",
    bio: "Marcel sammelte während vielen Jahren als selbstständiger Webentwickler umfangreiche Erfahrung und hat Projekte aus den unterschiedlichsten Branchen umgesetzt. Er schätzt die Vielfalt der Menschen, mit denen er zusammenarbeitet. Seine Freizeit verbringt er am liebsten mit seiner Familie.",
  },
  {
    name: "Jeffrey Meier",
    role: "Grafikdesigner",
    photo: "/team/jeffrey.jpg",
    email: "jm@uftritt.ch",
    bio: "Die Ausbildung und Tätigkeit als Polygraf legten für Jeffrey ein solides Fundament für sein späteres HF-Studium der Visuellen Gestaltung. Mit seiner Leidenschaft für durchdachtes Design kreiert er wirkungsvolle und zielgerichtete Gestaltungskonzepte. Daneben begleitet ihn das Musikmachen bereits seit jungen Jahren und bei unterschiedlichsten sportlichen Aktivitäten findet er seinen Ausgleich.",
  },
  {
    name: "Christian Angst",
    partner: true,
    role: "Videograf",
    photo: "/team/christian.jpg",
    email: "info@uftritt.ch",
    bio: "Christian hat seine Kamerafähigkeiten durch eine Fachausbildung und mehrere Praktika professionalisiert. Unkompliziert und mit gutem Gespür setzt er Imagefilme, Produktfilme und Social-Media-Videos um. Seine freie Zeit verbringt er mit seiner Familie und der Musik.",
  },
  {
    name: "Tamara Schmutz",
    partner: true,
    role: "Texterstellung und Social Media",
    photo: "/team/tamara.jpg",
    email: "info@uftritt.ch",
    bio: "Mit ihrem Studium in Journalismus und Organisationskommunikation und mehrjähriger Erfahrung in Kommunikation und Marketing bringt Tamara Botschaften auf den Punkt. Ihr Credo: «Lieber kurz auf den Punkt als lange drum herum.» Den Ausgleich findet sie auf dem Volleyballfeld.",
  },
  {
    name: "Sam Urech",
    partner: true,
    role: "Kommunikationsberater",
    photo: "/team/sam.jpg",
    email: "info@uftritt.ch",
    bio: "Sam macht Authentizität sichtbar, indem er die richtigen Fragen stellt: Wer bin ich? Was macht mich aus? Als Geschäftsführer von ratsam unterstützt er bei Identitäts- und Kommunikationsfragen und hilft dabei, den eigenen Kern zu finden.",
  },
];
