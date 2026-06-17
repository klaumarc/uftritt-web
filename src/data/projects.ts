export interface Project {
  title: string;
  slug: string;
  categories: string[]; // Mehrfach-Tags wie auf uftritt.ch
  image: string;
}

// Kategorien/Tags von der aktuellen Website uftritt.ch übernommen.
export const projects: Project[] = [
  { title: "RIPA Immobilien GmbH", slug: "ripa-immobilien", categories: ["Grafikdesign", "Webdesign"], image: "/projekte/ripa.jpg" },
  { title: "Regan", slug: "regan", categories: ["Webdesign"], image: "/projekte/regan.jpg" },
  { title: "Fahrschule Schweiz", slug: "fahrschule-schweiz", categories: ["Videoproduktion", "Grafikdesign", "Webdesign"], image: "/projekte/fahrschule-schweiz.jpg" },
  { title: "Physio & Sportreha Rothe", slug: "physio-rothe", categories: ["Webdesign"], image: "/projekte/physio-rothe.jpg" },
  { title: "Spinnereiplatz", slug: "spinnereiplatz", categories: ["Grafikdesign", "Webdesign"], image: "/projekte/spinnereiplatz.jpg" },
  { title: "smart navigator", slug: "smart-navigator", categories: ["Grafikdesign", "Webdesign"], image: "/projekte/smart-navigator.jpg" },
  { title: "Seatalytics", slug: "seatalytics", categories: ["Webdesign"], image: "/projekte/seatalytics.jpg" },
  { title: "it-stall", slug: "it-stall", categories: ["Grafikdesign", "Webdesign"], image: "/projekte/it-stall.jpg" },
  { title: "Cynterim", slug: "cynterim", categories: ["Grafikdesign", "Webdesign"], image: "/projekte/cynterim.jpg" },
  { title: "DT Bäretswil – Branding", slug: "dtb-redesign", categories: ["Grafikdesign"], image: "/projekte/dtb-redesign.jpg" },
  { title: "100Pro Openair 2024", slug: "100pro-openair-2024", categories: ["Videoproduktion", "Grafikdesign", "Webdesign"], image: "/projekte/100pro-openair.jpg" },
  { title: "Arte & Bellezza", slug: "arte-bellezza", categories: ["Grafikdesign"], image: "/projekte/arte-bellezza.jpg" },
  { title: "Beat Brugger Impulse", slug: "beat-brugger", categories: ["Grafikdesign"], image: "/projekte/beat-brugger.jpg" },
  { title: "CVA Management & Consulting AG", slug: "cva", categories: ["Grafikdesign"], image: "/projekte/cva.jpg" },
  { title: "DT Bäretswil – Magazin", slug: "dt-baeretswil", categories: ["Grafikdesign"], image: "/projekte/dt-baeretswil.jpg" },
  { title: "RIPA Weihnachtskarte", slug: "ripa-weihnachtskarte", categories: ["Grafikdesign"], image: "/projekte/ripa-weihnachtskarte.jpg" },
  { title: "Stahel + Köng", slug: "stahel-koeng", categories: ["Grafikdesign"], image: "/projekte/stahel-koeng.jpg" },
  { title: "Youngstars Broschüre", slug: "youngstars-broschuere", categories: ["Grafikdesign"], image: "/projekte/youngstars-broschuere.jpg" },
  { title: "Youngstars International", slug: "youngstars", categories: ["Videoproduktion", "Grafikdesign", "Webdesign"], image: "/projekte/youngstars.jpg" },
  { title: "Architekturmacher", slug: "architekturmacher", categories: ["Grafikdesign"], image: "/projekte/architekturmacher.jpg" },
  { title: "Autogarage Frei", slug: "garage-frei", categories: ["Webdesign"], image: "/projekte/garage-frei.jpg" },
  { title: "emotion Hinwil", slug: "emotion-hinwil", categories: ["Grafikdesign"], image: "/projekte/emotion-hinwil.jpg" },
  { title: "AAS Freight AG", slug: "aas-freight", categories: ["Videoproduktion", "Grafikdesign", "Webdesign"], image: "/projekte/aas-freight.jpg" },
  { title: "EMPOWER Nutrition", slug: "empower", categories: ["Videoproduktion"], image: "/projekte/empower.jpg" },
  { title: "Heusser Holzbau", slug: "heusser-holzbau", categories: ["Videoproduktion"], image: "/projekte/heusser-holzbau.jpg" },
  { title: "Fahrschule Peter Ott", slug: "peter-ott", categories: ["Videoproduktion"], image: "/projekte/peter-ott.jpg" },
  { title: "SAM FOCUS", slug: "sam-focus", categories: ["Magazinbetreuung"], image: "/projekte/sam-focus.jpg" },
];
