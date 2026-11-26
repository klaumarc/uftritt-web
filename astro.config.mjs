import { defineConfig } from "astro/config";
import sitemap from "@astrojs/sitemap";
import os from "node:os";
import path from "node:path";

// https://astro.build/config
export default defineConfig({
  site: "https://www.uftritt.ch",
  build: {
    // CSS direkt ins HTML einbetten: entfernt die render-blockierenden
    // CSS-Requests aus dem kritischen Pfad (PSI 2026-08-14: ~880 ms Einsparung mobile).
    inlineStylesheets: "always",
  },
  vite: {
    // Vite-Cache ausserhalb von OneDrive – sonst sperrt OneDrive node_modules/.vite (EPERM).
    // Benutzerunabhängig via os.tmpdir() (früher hart codiert auf C:/Users/marce/... –
    // schlug auf anderen Maschinen mit EPERM fehl).
    cacheDir: path.join(os.tmpdir(), "uftritt-vite"),
  },
  integrations: [
    sitemap({
      // Utility-Seiten nicht in die Sitemap: Danke-Seite (noindex) + 404
      // 2026-10-20 (Doorway-Sanierung, SEO): diese 10 Orts-Landingpages stehen auf
      // noindex und fliegen deshalb auch aus der Sitemap, damit sich das
      // Ranking-Signal auf die starken Orts-Seiten konzentriert.
      filter: (page) =>
        !page.includes("/danke") &&
        !page.includes("/404") &&
        ![
          "/webdesign-baeretswil/",
          "/webdesign-bauma/",
          "/webdesign-duernten/",
          "/webdesign-pfaeffikon/",
          "/webdesign-rueti/",
          "/webdesign-saland/",
          "/webdesign-toesstal/",
          "/webdesign-wald/",
          "/webdesign-zuerichsee/",
          "/webdesign-zuerich/",
        ].some((slug) => page.endsWith(slug)),
    }),
  ],
});
