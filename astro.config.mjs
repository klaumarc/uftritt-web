import { defineConfig } from "astro/config";
import react from "@astrojs/react";
import sitemap from "@astrojs/sitemap";
import sanity from "@sanity/astro";

const projectId = process.env.PUBLIC_SANITY_PROJECT_ID || "placeholder";
const dataset = process.env.PUBLIC_SANITY_DATASET || "production";

// https://astro.build/config
export default defineConfig({
  site: "https://www.uftritt.ch",
  vite: {
    // Vite-Cache ausserhalb von OneDrive – sonst sperrt OneDrive node_modules/.vite (EPERM).
    cacheDir: "C:/Users/marce/AppData/Local/Temp/uftritt-vite",
  },
  integrations: [
    sanity({
      projectId,
      dataset,
      // Studio läuft separat auf Sanity-Hosting (uftritt.sanity.studio),
      // damit die Astro-Seite vollständig statisch bleibt.
      useCdn: true,
    }),
    react(),
    sitemap(),
  ],
});
