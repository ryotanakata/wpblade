# アーキテクチャ概要（全体像）

`paths` を持たない**常時ロード**のルール。wpblade テーマの全体像（ディレクトリ構成・レンダリングフロー）をどのタスクでも context に載せる。各層の詳細なコーディング規約は同階層のスコープ別ルール（`frontend/*` / `backend/php.md`、編集ファイルに応じて自動ロード）が単一の真実源で、記述が食い違う場合はそちらを優先する。開発コマンド・環境変数・ルール索引は [CLAUDE.md](../../CLAUDE.md) 参照。

## ディレクトリ構成

```
wp-content/themes/wpblade/
├── functions.php               # エントリポイント（Composer オートロード + require_once）
├── front-page.php              # トップページ（固定フロントページ）
├── page-store.php              # 固定ページテンプレート（店舗・アクセス）
├── page-contact.php            # お問い合わせページ（React フォームをマウント）
├── archive-column.php          # コラム一覧ページ
├── single-column.php           # コラム詳細ページ
├── 404.php                     # 404 エラーページ
├── index.php                   # フォールバック
├── style.css                   # テーマ情報（WordPress がテーマとして認識するのに必須）
├── (composer.json / package.json / vite.config.js / tsconfig.json / prettier.config.js / .nvmrc)
│
├── app/                        # PSR-4 オートロード（名前空間: WpBlade\）
│   ├── View/
│   │   ├── Blade.php           # BladeOne ラッパ（シングルトン）
│   │   └── ViteAssets.php      # Vite アセット解決・enqueue / enqueueReact
│   ├── Http/
│   │   └── Controllers/
│   │       └── ContactController.php  # REST API: POST /wp-json/wpblade/v1/contact
│   ├── Services/
│   │   ├── ColumnService.php   # コラムの表示用データ組み立て（アーカイブカード・サムネイル）
│   │   ├── ContactService.php  # フォーム送信オーケストレーション（バリデーション・メール）
│   │   └── MailService.php     # 宛先解決・管理者通知・自動返信
│   ├── Constants/
│   │   ├── NavConstant.php     # グローバルナビ項目（PRIMARY）
│   │   ├── ContactConstant.php # バリデーション用定数・エラーメッセージ
│   │   └── MailConstant.php    # 宛先ルーティング（ALWAYS_NOTIFY / ROUTES）
│   └── Helpers/                # ヘルパ関数
│
├── includes/functions/         # WordPress フック登録（functions.php から require_once）
│   ├── theme-support.php       # after_setup_theme
│   ├── post-types.php          # init（CPT column 登録）
│   ├── assets.php              # wp_enqueue_scripts（ViteAssets 経由）
│   ├── contact.php             # rest_api_init（ContactController::register）
│   └── mail.php                # phpmailer_init / wp_mail_from / wp_mail フィルタ
│
├── resources/
│   ├── views/                  # Blade テンプレート
│   │   ├── layout.blade.php    # マスターレイアウト（@yield('content') を持つ）
│   │   ├── base/               # <head>/<body> 内のパーシャル（meta / link / script など）
│   │   ├── components/
│   │   │   ├── header.blade.php
│   │   │   └── footer.blade.php
│   │   ├── pages/
│   │   │   ├── 404.blade.php
│   │   │   ├── top/index.blade.php
│   │   │   ├── store/index.blade.php
│   │   │   ├── contact/index.blade.php   # React マウント先 <div data-element="contact-form">
│   │   │   └── column/
│   │   │       ├── index.blade.php       # 一覧（archive）
│   │   │       └── detail/
│   │   │           └── index.blade.php   # 詳細（single）
│   │   └── mail/               # メール本文テンプレート
│   │       ├── contact.blade.php         # 管理者通知メール本文
│   │       └── reply.blade.php           # 自動返信メール本文
│   ├── scss/                   # Sass ソース（Vite でコンパイル）
│   │   ├── style.scss          # エントリ（@use で各層を束ねる）
│   │   ├── base/               # reset / base スタイル / variable / function / mixin / utility
│   │   ├── components/         # コンポーネントスタイル（c- プレフィックス）
│   │   └── pages/              # ページ固有スタイル（pg- プレフィックス）
│   ├── js/                     # Vanilla JS ソース（Vite でバンドル）
│   │   ├── script.js           # エントリポイント
│   │   ├── base/               # 全ページ共通インフラ（dataLayerInsight など）
│   │   ├── services/           # ドメインロジック（PHP app/Services/ の JS 版）
│   │   ├── repositories/       # fetch / API 呼び出し（services/ からのみ呼ぶ）
│   │   ├── utils/              # element / pathMatch / toggleScrollLock など
│   │   ├── components/         # UI コンポーネント
│   │   ├── pages/              # ページ固有スクリプト
│   │   └── constants/          # JS 定数
│   ├── ts/                     # React + TypeScript ソース（Vite でバンドル）
│   │   ├── script.tsx          # エントリポイント（pages/ 関数を import して呼ぶ）
│   │   ├── vite-env.d.ts       # CSS Modules 型定義
│   │   ├── types/              # 型定義（globalTypes.ts / {名前}Types.ts）
│   │   ├── schemas/            # Zod スキーマ（ファクトリ関数 + 型エクスポート）
│   │   ├── constants/          # TS 定数（validateConstant.ts など）
│   │   ├── components/
│   │   │   └── ContactForm/
│   │   │       ├── index.tsx          # JSX のみ
│   │   │       ├── hooks.ts           # useContactFormHooks()
│   │   │       └── style.module.scss  # CSS Modules
│   │   ├── pages/
│   │   │   └── contactPage.tsx        # React マウント関数
│   │   ├── services/           # ドメインロジック（JS 版 services/ と同じ役割）
│   │   ├── repositories/       # fetch / API 呼び出し
│   │   └── utils/              # 汎用ユーティリティ
│   └── images/                 # 静的画像
│       ├── base/               # ロゴ・アイコン・OGP など全ページ共通素材
│       ├── components/         # ヘッダー・フッターなどコンポーネント固有の素材
│       └── pages/              # ページ固有素材（top/ column/ store/ など）
│
├── public/                     # Vite ビルド出力（.gitignore）
│   ├── .vite/manifest.json     # prod モードでのアセット解決に使用
│   └── hot                     # dev サーバ起動中のみ存在（dev/prod 切り替えフラグ）
│
├── vendor/                     # Composer 依存（BladeOne / Respect\Validation ほか。.gitignore 対象。`composer install` で生成）
└── cache/                      # Blade コンパイルキャッシュ（.gitignore）
```

**`resources/` 配下の `views/` `scss/` `js/` `images/` は `base` / `components` / `pages` の3層で並行して構成する**（同じ関心を同じ位置に置く）。ページは「ディレクトリ + `index.blade.php`」で作り、`column/detail.blade.php` のような単一ファイルのページ名は使わない（詳細は [frontend/blade.md](frontend/blade.md)）。

## テーマのレンダリングフロー（通常ページ）

WordPress のテンプレートファイル（`front-page.php`, `page-*.php` など）は PHP のエントリポイントとして機能し、データを取得してから `Blade::render()` に渡す。Blade テンプレートは `resources/views/` に格納され、ドット記法（例: `"pages.top.index"`）で参照する。

```
WordPress テンプレート (front-page.php)
  → Blade::render("pages.top.index", $data)
    → BladeOne がコンパイル・キャッシュ (cache/)
      → resources/views/pages/top/index.blade.php
        → @extends('layout') + @section('content')
          → @include('components.header') / @include('components.footer')
```

## React ページのレンダリングフロー（お問い合わせページ）

React ページは `enqueueReact()` で JS/CSS を読み込み、Blade テンプレートに埋め込まれた DOM 要素に React をマウントする。

```
includes/functions/assets.php                   # wp_enqueue_scripts / is_page(["contact"]) で分岐
  → ViteAssets::enqueueReact()
      → window.wpblade = { restUrl, nonce } を wp_head に出力
      → resources/ts/script.tsx を type="module" で enqueue

page-contact.php
  → Blade::render("pages.contact.index")        # <div data-element="contact-form"> を出力
  → contactPage()
      → document.querySelector('[data-element="contact-form"]')
      → createRoot(el).render(<ContactForm />)
```

## お問い合わせフォームの送信フロー

```
ContactForm（React）
  → fetch POST /wp-json/wpblade/v1/contact  (X-WP-Nonce ヘッダー付き)
    → ContactController::checkPermission    (wp_verify_nonce)
    → ContactController::handle
      → ContactService::submit
          → ハニーポット判定（true → サイレント成功）
          → Respect\Validation でバリデーション
          → MailService::sendNotification   (管理者・店舗通知)
          → MailService::sendAutoReply      (送信者への自動返信)
  → 成功 → submitStatus を "success" に設定してフォームをインライン完了メッセージに差し替え
  → 失敗 → submitStatus を "error" に設定してエラーメッセージを表示
```

## PHP の名前空間と PSR-4 オートロード

`app/` ディレクトリが `WpBlade\` 名前空間にマッピングされる（Composer PSR-4）。

| ディレクトリ     | 名前空間             | 用途                                   |
| ---------------- | -------------------- | -------------------------------------- |
| `app/View/`      | `WpBlade\View\`      | Blade ラッパ・ViteAssets               |
| `app/Http/`      | `WpBlade\Http\`      | Controllers（REST API エンドポイント） |
| `app/Services/`  | `WpBlade\Services\`  | ドメインロジック                       |
| `app/Constants/` | `WpBlade\Constants\` | 定数クラス（`final class` で定義）      |
| `app/Helpers/`   | `WpBlade\Helpers\`   | ヘルパ関数                             |

## 各層の詳細ルール

全ルールの索引は [CLAUDE.md](../../CLAUDE.md) の「ルールの地図」。特に本ページのフロー関連:

- dev/prod 切替・Vite・エイリアス → [frontend/vite.md](frontend/vite.md)
- フック登録一覧・CPT・REST API・メール・薄い Controller → [backend/php.md](backend/php.md)
