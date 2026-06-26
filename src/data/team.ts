export interface TeamMember {
  name: string;
  role: string;
  photo: string;
  email: string;
  bio: string;
}

// Inhalte an der bestehenden Website orientiert, leicht überarbeitet.
// Später aus Sanity (Collection "Mitarbeiter") befüllbar.
export const team: TeamMember[] = [
  {
    name: "Marcel Klausberger",
    role: "Webentwickler",
    photo: "/team/marcel.jpg",
    email: "mk@uftritt.ch",
    bio: "Marcel sammelte während sechs Jahren als selbstständiger Webentwickler umfangreiche Erfahrung und hat Projekte aus den unterschiedlichsten Branchen umgesetzt. Er schätzt die Vielfalt der Menschen, mit denen er zusammenarbeitet. Seine Freizeit verbringt er am liebsten mit seiner Familie oder bei der Bibellesung.",
  },
  {
    name: "Jeffrey Meier",
    role: "Visueller Gestalter",
    photo: "/team/jeffrey.jpg",
    email: "jm@uftritt.ch",
    bio: "Die Ausbildung und Tätigkeit als Polygraf legte für Jeffrey ein solides Fundament für sein späteres HF-Studium der Visuellen Gestaltung. Mit seiner Leidenschaft für passgenaue Gestaltung kreiert er wirkungsvolle und zielgerichtete Kommunikationskonzepte. Sein Sinn fürs Detail verleiht jedem Projekt den nötigen Charakter. Daneben begleitet ihn das Musikmachen bereits seit jungen Jahren und bei den unterschiedlichsten sportlichen Aktivitäten findet er seinen Ausgleich.",
  },
  {
    name: "Christian Angst",
    role: "Videograf",
    photo: "/team/christian.jpg",
    email: "info@uftritt.ch",
    bio: "Christian hat seine Kamerafähigkeiten durch eine Fachausbildung und mehrere Praktika professionalisiert. Unkompliziert und mit Gespür setzt er Imagefilme, Produktfilme und Social-Media-Videos um. In seiner Freizeit verbringt er Zeit mit seiner Familie und der Musik.",
  },
  {
    name: "Tamara Schmutz",
    role: "Texterstellung und Social Media",
    photo: "/team/tamara.jpg",
    email: "info@uftritt.ch",
    bio: "Mit ihrem Studium in Journalismus und Organisationskommunikation und mehrjähriger Erfahrung in Kommunikation und Marketing bringt Tamara Botschaften auf den Punkt. Ihr Credo: «Lieber kurz auf den Punkt als lange drum herum.» Den Ausgleich findet sie auf dem Volleyballfeld.",
  },
  {
    name: "Sam Urech",
    role: "Kommunikationsberater",
    photo: "/team/sam.jpg",
    email: "info@uftritt.ch",
    bio: "Sam macht Authentizität sichtbar, indem er die richtigen Fragen stellt: Wer bin ich? Was macht mich aus? Als Geschäftsführer von ratsam unterstützt er bei Identitäts- und Kommunikationsfragen und hilft dabei, den eigenen Kern zu finden.",
  },
];
