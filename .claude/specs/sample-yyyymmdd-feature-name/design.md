# Design

<!--
雛形の使い方:
要件（requirement.md）が固まったあと、実装方針を記述する。
-->

## 1. アーキテクチャ概要

<!--
変更の全体像を1〜2段落で説明する。
どの層に何を追加・変更するかを明示する。
-->

## 2. ディレクトリ構成（差分）

```
wp-content/themes/wpblade/
├── app/
│   ├── Http/Controllers/     ← REST API / フック登録
│   ├── Services/             ← ドメインロジック
│   └── Constants/            ← 定数クラス
├── includes/functions/       ← フック登録ファイル（require_once で読み込む）
└── resources/
    ├── scss/                 ← Sass ソース（npm run build でコンパイル）
    ├── js/                   ← JavaScript エントリ
    └── views/pages/          ← Blade テンプレート
```

## 3. 実装詳細

### 3-1. （コンポーネント名）

<!--
各コンポーネントの設計を記述する。
PSR-4 名前空間、クラス構造、メソッド責務などを明示する。
-->

### 3-2. Blade テンプレート設計

<!--
テンプレートの継承関係を記述する。
@extends('layout') → @section('content') のフロー。
-->

### 3-3. Vite / Sass

<!--
CSS/JS のエントリ、出力先、prod/dev の切り替えを記述する。
-->

## 4. データフロー

```
WordPress テンプレート (.php)
  → Blade::render('pages.<name>.index', $data)
    → resources/views/pages/<name>/index.blade.php
      → @extends('layout') + @section('content')
```

## 5. リスクと対策

| リスク | 対策 |
|---|---|
| Blade キャッシュが古い | `cache/` を手動削除してから確認 |
| Vite manifest が古い | `npm run build` を再実行 |
| ... | ... |

## 6. 移行フェーズ（複数フェーズに分ける場合）

| Phase | 内容 | 状態 |
|---|---|---|
| A | ... | 未着手 |
| B | ... | 未着手 |
