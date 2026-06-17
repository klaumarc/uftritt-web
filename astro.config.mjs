import { defineConfig } from "astro/config";
import sitemap from "@astrojs/sitemap";

// https://astro.build/config
export default defineConfig({
  site: "https://www.uftritt.ch",
  vite: {
    // Vite-Cache ausserhalb von OneDrive – sonst sperrt OneDrive node_modules/.vite (EPERM).
    cacheDir: "C:/Users/marce/AppData/Local/Temp/uftritt-vite",
  },
  integrations: [sitemap()],
});
