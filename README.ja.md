# WPBlade

**BladeOne・Vite・Claude Code を組み合わせたモダンな WordPress テーマスターター。**

![License: MIT](https://img.shields.io/badge/License-MIT-blue.svg)
![PHP](https://img.shields.io/badge/PHP-8.2%2B-777BB4)
![Node.js](https://img.shields.io/badge/Node.js-v22-339933)
![WordPress](https://img.shields.io/badge/WordPress-6.x-21759B)

[English README](README.md)

![WPBlade screenshot](.github/screenshot.png)

---

## 背景

WordPress 開発はいつの間にか `functions.php` に集約されていく。関心の分離がなく、テンプレートは生 PHP で溢れ、ビジネスロジックとフック登録が同じファイルに同居する。プロジェクトが長くなるほど、保守コストは指数的に増える。

WPBlade はその問題を、WordPress を捨てずに解決する。PSR-4 名前空間・Service 層・テンプレートエンジンというモダンなソフトウェア設計を、WordPress のテンプレート階層の中に持ち込む。独自フレームワークなし。プラットフォームと戦わない。

## 対象

| やりたいこと | WPBlade が提供するもの |
| --- | --- |
| WordPress で Blade テンプレートを使いたい | BladeOne を `Blade::render()` で統合。Laravel と同じ `@extends` / `@section` / `@include` が使える |
| `functions.php` の肥大化から脱却したい | フック登録は `includes/functions/`、ドメインロジックは PSR-4 の `app/` に分離 |
| Claude Code で WordPress 開発したい | `.claude/rules/` がファイル種別でスコープされ、AI が常にアーキテクチャに沿ったコードを生成する |
| パフォーマンスを犠牲にしたくない | React は必要なページのみ読み込み、それ以外は Vanilla JS |
| セキュリティを後回しにしたくない | Service 層でのエスケープ、CRLF セーフなメールヘッダー、nonce 検証が最初から組み込まれている |

![](.github/code-comparison.png)

## 設計思想

**WordPress テンプレート階層をルーティングとして使う**
WordPress にはすでにルーティングがある。`front-page.php`・`single-{post_type}.php`・`archive-{post_type}.php` がそれだ。各テンプレートを「薄い Controller」として扱い、データを取得して `Blade::render()` に渡すだけにする。1ファイル = 1ルート = 1Controller の対応を保つ。別のルーティング層は不要。

**PSR-4 で `functions.php` を解体する**
フック登録は `includes/functions/`（関心ごとに1ファイル）に集約し、ドメインロジックは `WpBlade\` 名前空間の `app/` に置く。`functions.php` は `require_once` の呼び出しだけ。WordPress の配線とビジネスロジックが同じファイルに入らない。

**テンプレートエンジンに BladeOne を使う**
HTML の中に `<?php if ?>` や `<?php foreach ?>` が散らばると読みにくく、ミスを誘発する。Blade の `@extends`・`@section`・`@include` は Laravel と同じテンプレート継承モデルをフルフレームワークなしで実現する。

**Vanilla JS と React を排他にする**
React はインタラクティブな状態管理が必要なページ（`is_page(['contact'])`）にのみ読み込む。それ以外のページは Vanilla JS バンドルを使い、React ランタイムを不要なページに配信しない。

**全リソースを3層構造で揃える**
`views/`・`scss/`・`js/`・`ts/`・`images/` をすべて `base / components / pages` の3層で構成する。どこに何があるかを「共通基盤か・再利用コンポーネントか・ページ固有か」の問いで即座に判断できる。

**セキュリティをデフォルトにする**
出力エスケープ（`esc_html`・`esc_attr`・`esc_url`）はテンプレートに届く前の Service 層で行い、Blade 側で忘れる事故を防ぐ。メールヘッダーインジェクション（CRLF）は下流ライブラリに依存せず明示的に除去する。REST API の nonce 検証は `permission_callback` で強制する。

**Claude Code ルールを最初から組み込む**
`.claude/rules/` のコーディング規約はファイル種別でスコープされており、編集中のファイルに関連するルールだけがロードされる。AI 支援開発でも手動開発と同じアーキテクチャ・セキュリティ基準が一貫して適用される。

## 技術スタック

| カテゴリ | ツール |
| --- | --- |
| テンプレートエンジン | [BladeOne](https://github.com/EFTEC/BladeOne) 4.x |
| フロントエンドビルド | [Vite](https://vitejs.dev/) 6 |
| CSS プリプロセッサ | Sass（SCSS） |
| React（一部ページ） | React 19 + TypeScript + React Hook Form + Zod |
| アニメーション | [GSAP](https://gsap.com/) 3 / [Splide](https://splidejs.com/) 4 |
| バリデーション（PHP） | [Respect\Validation](https://respect-validation.readthedocs.io/) |
| コードフォーマッター | [Prettier](https://prettier.io/) 3.x |
| PHP | 8.2+ |
| Node.js | v22（`.nvmrc` 固定） |
| データベース | MySQL 8.0 |
| ローカル環境 | Docker（WordPress + MySQL + phpMyAdmin + Mailpit） |

## 前提条件

- [Docker Desktop](https://www.docker.com/products/docker-desktop/) がインストール済みであること
- [Node.js v22](https://nodejs.org/) がインストール済みであること（`nvm use` 推奨）
- [Composer](https://getcomposer.org/) がインストール済みであること

## クイックスタート

```bash
# 1. クローンして移動
git clone https://github.com/RyotaNakata/wpblade.git && cd wpblade

# 2. 環境変数を設定
cp .env.example .env
```

デフォルトのまま動作する。ポートや認証情報を変更したい場合のみ編集する：

```env
MYSQL_ROOT_PASSWORD=rootpass
MYSQL_USER=wp
MYSQL_PASSWORD=wppass
MYSQL_DATABASE=wordpress

WP_PORT=8080
PMA_PORT=8081
```

```bash
# 3. Docker を起動
docker-compose up -d

# 4. テーマ依存をインストール
cd wp-content/themes/wpblade && composer install && npm install

# 5. 開発サーバを起動
npm run dev
```

`http://localhost:8080` を開いて WordPress のインストールを完了し、**外観 → テーマ** から **WPBlade** を有効化する。

## アクセス先

| サービス | URL |
| --- | --- |
| WordPress | http://localhost:8080 |
| phpMyAdmin | http://localhost:8081 |
| Vite dev サーバ | http://localhost:5173 |
| Mailpit（メール） | http://localhost:8025 |

## 主なコマンド

```bash
npm run dev           # 開発サーバ起動（HMR あり）
npm run build         # 本番ビルド
npm run format        # JS / SCSS / Blade / PHP をフォーマット
npm run format:check  # フォーマットチェックのみ（書き換えなし）
```

## ディレクトリ構成

```
.
├── docker-compose.yml
├── .env.example
└── wp-content/themes/wpblade/
    ├── functions.php               # エントリポイント（require_once のみ）
    ├── front-page.php              # トップページ    ┐
    ├── page-store.php              # 店舗ページ      │ 薄い Controller:
    ├── page-contact.php            # お問い合わせ    │ データ取得 + Blade::render()
    ├── archive-column.php          # コラム一覧      │
    ├── single-column.php           # コラム詳細      ┘
    ├── 404.php
    │
    ├── app/                        # PSR-4（WpBlade\）
    │   ├── View/Blade.php          # BladeOne ラッパ
    │   ├── View/ViteAssets.php     # Vite アセット解決
    │   ├── Http/Controllers/       # REST API エンドポイント
    │   ├── Services/               # ドメインロジック
    │   ├── Constants/              # 定数（final class）
    │   └── Helpers/
    │
    ├── includes/functions/         # フック登録のみ
    │   ├── theme-support.php
    │   ├── post-types.php
    │   ├── assets.php              # Vanilla JS ↔ React を is_page() で排他切替
    │   ├── contact.php
    │   └── mail.php
    │
    └── resources/                  # 全ソースが base/components/pages の3層で揃う
        ├── views/                  # Blade テンプレート
        ├── scss/                   # Sass
        ├── js/                     # Vanilla JS
        ├── ts/                     # React + TypeScript
        └── images/
```

## アーキテクチャ

#### 通常ページのレンダリングフロー

```
front-page.php（薄い Controller）
  → Blade::render('pages.top.index', compact(...))
    → resources/views/pages/top/index.blade.php
      → @extends('layout')
        → @include('components.header') / @include('components.footer')
```

#### React ページのフロー（お問い合わせ）

```
page-contact.php
  → Blade::render('pages.contact.index')   ← <div data-element="contact-form"> を出力
  → ViteAssets::enqueueReact()             ← window.wpblade = { restUrl, nonce }
  → contactPage() → createRoot(el).render(<ContactForm />)
```

#### お問い合わせフォームの送信フロー

```
ContactForm（React）
  → POST /wp-json/wpblade/v1/contact  [X-WP-Nonce ヘッダー]
    → ContactController::checkPermission  [wp_verify_nonce]
    → ContactController::handle
      → ContactService::submit
          → ハニーポット判定（検知時はサイレント成功）
          → Respect\Validation でバリデーション
          → MailService::sendNotification（管理者・店舗通知）
          → MailService::sendAutoReply（送信者への自動返信）
```

#### dev / prod モードの切り替え

`npm run dev` を実行すると `public/hot` ファイルが生成される。`ViteAssets` はこのファイルの有無でモードを判定する。

| モード | 条件 | 動作 |
| --- | --- | --- |
| dev | `public/hot` あり | `<script type="module">` で HMR を有効化 |
| prod | `public/hot` なし | `manifest.json` からハッシュ付きファイルを `wp_enqueue_*` で登録 |

#### アプリケーション層の責務

| レイヤー | 場所 | 責務 |
| --- | --- | --- |
| View | `app/View/` | Blade ラッパ・ViteAssets |
| Controller | `app/Http/Controllers/` | REST API エンドポイント |
| Service | `app/Services/` | ドメインロジック（WordPress 非依存を原則） |
| Constants | `app/Constants/` | 定数（`final class` で定義） |
| Helpers | `app/Helpers/` | グローバルヘルパ関数 |

## Claude Code との開発

このスターターは [Claude Code](https://claude.ai/code) での開発に最適化されている。`.claude/` ディレクトリに AI 支援開発をプロジェクトのアーキテクチャと一致させるための設定が揃っている。

#### どう機能するか

`.claude/rules/` には関心ごとにルールファイルが1つずつある。ルールはファイル種別でスコープされており、Claude が特定のファイルを編集するときに関連する規約だけがロードされる。

| ルールファイル | ロードされるタイミング |
| --- | --- |
| `architecture.md` | 常時 |
| `security.md` | 常時 |
| `backend/php.md` | `app/`・`includes/`・`*.php` 編集時 |
| `frontend/blade.md` | `*.blade.php` 編集時 |
| `frontend/scss.md` | `*.scss` 編集時 |
| `frontend/typescript.md` | `resources/ts/` 編集時 |
| `frontend/javascript.md` | `resources/js/` 編集時 |
| `frontend/accessibility.md` | blade / js / ts / scss 編集時 |
| `frontend/vite.md` | vite 設定・js・ts・scss 編集時 |
| `frontend/image.md` | `resources/images/`・blade・scss 編集時 |

つまり Claude は、毎回指示しなくても、薄い Controller パターン・Service 層でのエスケープ・BEM 派生の命名規約・nonce 検証を守ったコードを生成する。

#### spec 駆動の開発フロー

```
.claude/specs/YYYYMMDD-feature-name/
├── requirement.md   # 目的・スコープ・完了条件
├── design.md        # アーキテクチャ・実装方針
└── tasks.md         # Phase 別タスク一覧
```

1. 追加したい機能を Claude Code に伝える
2. 対話しながら `requirement.md` を埋める
3. `design.md` で実装方針を固める
4. `tasks.md` を上から順に実行する

Claude Code を使わない場合でも、ルールファイルはプロジェクトに参加する開発者向けのコーディング規約ドキュメントとして機能する。

## メール

`WPBLADE_ENV` が `production` 以外の場合、すべてのメールを Mailpit（`http://localhost:8025`）でキャプチャする。

| 変数 | 用途 |
| --- | --- |
| `WPBLADE_ENV` | `production` のみ実際の宛先へ送信 |
| `WPBLADE_SMTP_HOST` | SMTP ホスト（デフォルト: `mailpit`） |
| `WPBLADE_SMTP_PORT` | SMTP ポート（デフォルト: `1025`） |

## カスタム投稿タイプ

| スラッグ | 説明 |
| --- | --- |
| `column` | コラム記事（アーカイブあり・REST API 有効） |

## ライセンス

MIT
