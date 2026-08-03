---
paths:
  - "**/resources/images/**"
  - "**/*.blade.php"
  - "**/*.scss"
---

# フロントエンド規約：画像

画像アセットの配置・命名・拡張子・解像度と img タグの書き方。SCSS の背景画像 mixin（`bg-image-path`）は [scss.md](scss.md)、img タグのアクセシビリティは [accessibility.md](accessibility.md) を参照。

## 配置ディレクトリ

`resources/images/` 配下を SCSS・views と同じ 3 層で整理する。

```
resources/images/
├── base/               # ロゴ・アイコン・OGP など全ページ共通素材
├── components/         # ヘッダー・フッターなどコンポーネント固有の素材
└── pages/              # ページ固有素材
    ├── top/
    ├── column/
    └── store/
```

SCSS の `$_image-dir`（`bg-image-path` mixin の引数）はサブディレクトリを含めて指定する（[scss.md](scss.md) の `bg-image-path` セクション参照）。

## ファイル命名規則

`セクション名_名前_連番-サイズ--日付.拡張子` の形式で命名する。

例: `mv_background_01-l--20260101.webp`

| パート       | 内容                                         | 例                           |
| ------------ | -------------------------------------------- | ---------------------------- |
| セクション名 | 使用するセクションの名前                     | `mv`, `cta`, `feature`       |
| 名前         | 任意の名前（何の要素か）                     | `background`, `icon`, `logo` |
| 連番         | 対象セクション内での通し番号                 | `01`, `02`, `03`             |
| サイズ       | SP/PC 共通ならなし、PC 展開時は `l`（large） | `-l` または省略              |
| 日付         | YYYYMMDD 形式の更新日                        | `20260101`                   |
| 拡張子       | 後述の許可拡張子                             | `webp`, `svg`                |

```
mv_background_01--20260101.webp          # SP/PC 共通
mv_background_01-l--20260101.webp        # PC 用
icon_arrow_01.svg                        # SVG はサイズ・日付省略可
```

**日付を含める理由**: ブラウザ・CDN キャッシュ対策。同じファイル名で内容を差し替えるとキャッシュが効いて反映されない事故を防ぐ。差し替えるときは日付を更新する。

## 拡張子

| 用途                     | 拡張子  |
| ------------------------ | ------- |
| 写真・複雑な画像         | `.webp` |
| ロゴ・アイコン・イラスト | `.svg`  |

**JPG / PNG / GIF は使わない**。デザイナーから受け取ったときは webp に変換してから配置する。

## 解像度

- **基本は 2 倍解像度**（Retina 対応）
- デザイナーからスーパーレティナ対応の打診があった場合のみ 3 倍を用意

## img タグの書き方

- 意味のある画像: `alt` 属性に内容を記述
- 装飾目的の画像: `alt=""` + `aria-hidden="true"`
- `width` / `height` 属性を必ず指定（CLS 対策）

```html
<!-- 意味のある画像 -->
<img src="..." alt="代替テキスト" width="100" height="100" />

<!-- 装飾画像 -->
<img src="..." alt="" aria-hidden="true" width="100" height="100" />
```
