/** @type {import("prettier").Config} */
export default {
  tabWidth: 2,
  semi: true,
  singleQuote: false,
  trailingComma: "es5",
  plugins: ["@prettier/plugin-php", "@trivago/prettier-plugin-sort-imports"],

  // JS / TS の import 順序
  importOrder: [
    "^[a-zA-Z]", // サードパーティ（jquery など）
    "^@js/(.*)$", // vite.config.js の @js エイリアス
    "^@ts/(.*)$", // vite.config.js の @ts エイリアス
    "^[./]", // 相対パス
  ],
  importOrderSortSpecifiers: true,

  // ファイル種別ごとの上書き設定
  overrides: [
    {
      files: "*.php",
      options: {
        parser: "php",
        phpVersion: "8.2",
        tabWidth: 4,
        trailingCommaPHP: true,
        braceStyle: "psr-2",
      },
    },
    {
      files: "*.blade.php",
      options: {
        parser: "php",
        phpVersion: "8.2",
        tabWidth: 2,
        trailingCommaPHP: true,
        braceStyle: "psr-2",
      },
    },
  ],
};
