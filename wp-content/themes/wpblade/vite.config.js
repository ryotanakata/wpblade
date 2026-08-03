import fs from "node:fs";
import { resolve } from "node:path";
import react from "@vitejs/plugin-react";
import { defineConfig } from "vite";

/**
 * dev サーバ起動中だけ `public/hot`（dev サーバ URL を書いたファイル）を作る。
 * PHP 側（ViteAssets）はこのファイルの有無で dev/prod を判定する。
 */
const hotFilePlugin = () => {
  const hotPath = resolve(import.meta.dirname, "public/hot");
  const clean = () => {
    try {
      if (fs.existsSync(hotPath)) fs.unlinkSync(hotPath);
    } catch {}
  };
  return {
    name: "wpblade-hot-file",
    apply: "serve",
    configureServer(server) {
      const write = () => {
        const port = server.config.server.port ?? 5173;
        fs.mkdirSync(resolve(import.meta.dirname, "public"), {
          recursive: true,
        });
        fs.writeFileSync(hotPath, `http://localhost:${port}`);
      };
      server.httpServer?.once("listening", write);
      ["SIGINT", "SIGTERM", "exit"].forEach((sig) =>
        process.on(sig, () => {
          clean();
          if (sig !== "exit") process.exit();
        })
      );
    },
  };
};

export default defineConfig(({ command }) => ({
  base: command === "build" ? "/wp-content/themes/wpblade/public/" : "/",
  plugins: [hotFilePlugin(), react()],
  resolve: {
    alias: {
      "@js": resolve(import.meta.dirname, "resources/js"),
      "@ts": resolve(import.meta.dirname, "resources/ts"),
    },
  },
  build: {
    manifest: true,
    outDir: "public",
    emptyOutDir: true,
    rollupOptions: {
      input: [
        "resources/js/script.js",
        "resources/scss/style.scss",
        "resources/ts/script.tsx",
      ],
    },
  },
  css: {
    preprocessorOptions: {
      scss: {
        loadPaths: [resolve(import.meta.dirname, "resources/scss")],
      },
    },
  },
  server: {
    host: "localhost",
    port: 5173,
    strictPort: true,
    cors: true,
  },
}));
