# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

このリポジトリはモダンな WordPress テーマ `wp-content/themes/wpblade/` を管理する（コアの WordPress ファイルは管理対象外、`wp-content/` のみ）。詳細なコーディング規約は [.claude/rules/](.claude/rules/) に分割してあり、**編集するファイル種別に応じて自動ロードされる**（下記「ルールの地図」）。

## 開発環境の起動

```bash
# Docker コンテナ起動（WordPress + MySQL + phpMyAdmin + Mailpit）
docker-compose up -d

# テーマディレクトリで依存インストール
cd wp-content/themes/wpblade
composer install
npm install

# フロントエンド開発サーバ起動（HMR あり）
npm run dev

# フロントエンド本番ビルド
npm run build

# コードフォーマット（Prettier）
npm run format        # resources/ 以下の JS / TS / SCSS / Blade / PHP を整形
npm run format:check  # 整形チェックのみ（ファイルを書き換えない）

# 型チェック
npm run typecheck     # tsc --noEmit
```

- WordPress: http://localhost:8080
- phpMyAdmin: http://localhost:8081
- Vite dev サーバ: http://localhost:5173
- Mailpit（開発用メール UI）: http://localhost:8025

環境変数は `.env.example` をコピーして `.env` を作成する。

**テストは意図的に同梱しない**（PHPUnit / Pest の選択とテスト戦略はプロジェクト側の判断領域。README 参照）。CI（[.github/workflows/ci.yml](.github/workflows/ci.yml)）は `npm run typecheck` と `npm run format:check` のみを実行する。この2つはローカルでも push 前に通しておく。

**メール環境変数**（`.env.example` 参照。振り分けの挙動は [backend/php.md](.claude/rules/backend/php.md) の「メール機能」）:

| 変数 | 用途 |
| --- | --- |
| `WPBLADE_ENV` | `production` のみ実際の宛先へ送信。未設定は開発モード |
| `WPBLADE_SMTP_HOST` | SMTP ホスト（デフォルト: `mailhog` → `mailpit`） |
| `WPBLADE_SMTP_PORT` | SMTP ポート（デフォルト: `1025`） |

## 全体不変則

ファイル種別に依らず常に効く要点。詳細は各ルール参照。

- **薄い Controller**: 標準テンプレ（`front-page.php` / `single-*.php` / `archive-*.php` / `page-*.php`）はデータ取得＋`Blade::render()` のみ。汎用テンプレ（`single.php` 等）は使わず専用テンプレで受ける → [backend/php.md](.claude/rules/backend/php.md)
- **フックは `includes/functions/` に1ファイル、ロジックは `app/`**（`WpBlade\` 名前空間・PSR-4）。`functions.php` に直接書かない → [backend/php.md](.claude/rules/backend/php.md)
- **Vanilla JS と React は排他**: `assets.php` の `is_page(["contact"])` で切替 → [typescript.md](.claude/rules/frontend/typescript.md) / [javascript.md](.claude/rules/frontend/javascript.md)
- **ナビはコード管理**: `app/Constants/NavConstant.php` で定義。管理画面「外観 → メニュー」は使わない → [blade.md](.claude/rules/frontend/blade.md)
- **DOM フックは `data-*`、クラスは見た目**: JS フック用のクラスを付けない → [javascript.md](.claude/rules/frontend/javascript.md) / [scss.md](.claude/rules/frontend/scss.md)
- **メール振り分け**: `WPBLADE_ENV=production` 以外は `MailConstant::DEV_MAIL` へリダイレクト → [backend/php.md](.claude/rules/backend/php.md)
- `vendor/`（Composer）・`cache/`（Blade コンパイル）・`public/`（Vite 出力）・`wp-content/plugins/` は `.gitignore`

## ルールの地図

`.claude/rules/` 配下。マッチするファイルを編集するときに自動ロードされる（`architecture.md` と `security.md` は `paths` 無しのため常時ロード）。

| ルール | 発火契機（編集ファイル） | 内容 |
| --- | --- | --- |
| [architecture.md](.claude/rules/architecture.md) | 常時 | 全体ディレクトリ構成・レンダリングフロー・PSR-4 名前空間 |
| [security.md](.claude/rules/security.md) | 常時 | 出力エスケープ・入力検証・権限・nonce・SQL |
| [backend/php.md](.claude/rules/backend/php.md) | `app/` `includes/` テーマ直下 `*.php` | 名前空間/PSR-4・薄い Controller・REST API・フック登録・CPT・メール |
| [frontend/blade.md](.claude/rules/frontend/blade.md) | `*.blade.php` | Blade/HTML・ディレクティブ・セマンティック・共有変数・div レイアウト |
| [frontend/scss.md](.claude/rules/frontend/scss.md) | `*.scss` | Sass 記法・デザイントークン・命名（`__` を使わない BEM 派生）・mixin |
| [frontend/javascript.md](.claude/rules/frontend/javascript.md) | `resources/js/` | Vanilla JS 層構成・`data-*` 契約・イベント設計・命名 |
| [frontend/typescript.md](.claude/rules/frontend/typescript.md) | `resources/ts/` | React/TS・Zod・React Hook Form・`enqueueReact()` |
| [frontend/accessibility.md](.claude/rules/frontend/accessibility.md) | blade / js / ts / scss | WCAG AA・ARIA・フォーム・キーボード操作 |
| [frontend/image.md](.claude/rules/frontend/image.md) | `resources/images/` blade scss | 画像の命名/拡張子/解像度/配置3層 |
| [frontend/vite.md](.claude/rules/frontend/vite.md) | vite 設定・js/ts/scss | エントリ・エイリアス・`loadPaths`・dev/prod 切替 |

## 自律開発ループ（Notion連携）

Notion をタスク管理に使った自律開発ループを導入している。開発フローは次のとおり:

```
Notion起票（背景・要求・受け入れ条件AC） → ルーチン実行（実装・自己レビュー・PR作成） → 人間レビュー → マージ
```

- **Notion 参照元**: `.claude/notion.json`（DB ID・Statusプロパティの値・base ブランチ・テストコマンドを集約。値をコード側に直書きしない）
- **ルーチンのプロンプト**: `.claude/routine-prompt.md`（クラウド側ルーチンの設定にはこのファイルの内容をそのまま使う。プロンプトをリポジトリ内で管理することで、コードと一緒にレビューされずに実態とドリフトするのを防ぐ）
- **コミット前の規約レビュー**: `.claude/skills/wpblade-rules-review/SKILL.md`（`.claude/agents/rules-reviewer.md` を並列起動して照合）。`git commit` は `.claude/hooks/rules-review-gate.sh` のゲート（`.claude/settings.json` の `PreToolUse` hook）を通過しないとブロックされる
- **出荷（レビュー→意味単位コミット→PR説明文の生成）**: `.claude/skills/wpblade-ship/SKILL.md`。ただし push・`gh pr create` はこのスキルの範囲外（ユーザーが明示的に指示したときだけ）で、Notionルーチンからの無人実行時は `.claude/routine-prompt.md` が push・PR作成まで直接行う
- **PRテンプレート**: `.github/pull_request_template.md`。`## 受け入れ条件（AC）` の見出しは表記を変えない（AC の箇条書きを差分と照合する仕組みが前提のため）
- ルーチンが作った PR は、人間がレビュー・マージするまで Notion 上のタスクを完了にしない
