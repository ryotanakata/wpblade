---
paths:
  - "**/vite.config.*"
  - "**/package.json"
  - "**/resources/js/**"
  - "**/resources/ts/**"
  - "**/*.scss"
---

# フロントエンド規約：Vite / ビルド

ビルドツール（Vite）とエントリ・エイリアスの設定。言語別の詳細は [javascript.md](javascript.md) / [typescript.md](typescript.md) / [scss.md](scss.md) を参照。

- JS エントリ: `resources/js/script.js`
- TS/React エントリ: `resources/ts/script.tsx`（単一エントリ）
- CSS エントリ: `resources/scss/style.scss`
- ビルド出力: `public/`（`.gitignore` 対象）
- dev サーバ: `npm run dev`（`http://localhost:5173`、HMR あり）
- 本番ビルド: `npm run build`

エイリアス:

| エイリアス | 解決先              |
| ---------- | ------------------- |
| `@js`      | `resources/js/`     |
| `@ts`      | `resources/ts/`     |

SCSS の `loadPaths` に `resources/scss/` を設定済み。CSS Modules 内で `@use "base" as *;` と書けばパスを省略できる。

## dev/prod モード切り替え

`npm run dev` を実行すると Vite の `hotFilePlugin` が `public/hot` ファイルに dev サーバ URL を書き込む。`ViteAssets::isDev()` はこのファイルの有無でモードを判定する。

- **dev モード**: `public/hot` あり → `wp_head` に `type="module"` タグを直接出力して HMR を有効化
- **prod モード**: `public/hot` なし → `public/.vite/manifest.json` を参照してハッシュ付きファイルを `wp_enqueue_*` で登録

`public/`（ビルド出力）と `cache/`（Blade コンパイルキャッシュ）はいずれも `.gitignore` 対象。Vanilla JS バンドルと React バンドルの排他切替は `includes/functions/assets.php` が `is_page()` で行う（詳細は [typescript.md](typescript.md) の `enqueueReact()` 節）。
