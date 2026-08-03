---
paths:
  - "**/*.scss"
---

# フロントエンド規約：Sass / SCSS

`resources/scss/` の記法・命名規則・デザイントークン・共通 mixin / 関数をまとめる。DOM フック（`data-*` 属性）との責務分離は [javascript.md](javascript.md)、CSS Modules での利用は [typescript.md](typescript.md)、画像アセット規約は [image.md](image.md) を参照。

## ファイル構成とビルド

### `@use` / `@forward`（バレル方式）

- ファイルはパーシャル（`_*.scss`）として作成し、エントリから `@use` で読み込む
- **`@import` は使わない**（`@use` / `@forward` のみ。Sass の非推奨機能）
- **エントリ `style.scss` は層を束ねるだけ**にする

```scss
// style.scss
@use "base";
@use "components";
@use "pages";
```

- **各ディレクトリに `_index.scss`（バレル）を置き、メンバーを `@forward` で集約する**。ファイル追加時は該当階層の `_index.scss` に1行足すだけにする
- **`@forward` の行はファイル名のアルファベット順（a→z）に並べる**。ファイル追加時も該当位置に挿入し、順序を崩さない（**ページ単位のセクションバレルだけは例外**——a→z ではなくページ内のセクション出現順に並べる。後述「Blade 分割と SCSS 分割を一致させる」）
- **`base/_index.scss` だけは a→z ではなく依存・カスケード順に並べる**（`function` → `mixin` → `variable` → `reset` → `base` → `utility`）。a→z にすると `reset` が `base` より後に出力され、`body` の `line-height` など同詳細度の宣言が上書きされて壊れる

```scss
// components/_index.scss
@forward "c-cta";
@forward "c-drawer";
@forward "c-footer";
// …
```

- **各パーシャルの冒頭で `@use "../base" as *;` を宣言する**。これで mixin（`media-size` / `hover`）・関数（`fluid-clamp`）・トークンを名前空間なしで使える。`@use` はファイルごとに宣言が必要なので、新規パーシャル作成時は忘れず先頭に書く

```scss
// components/_c-cta.scss
@use "../base" as *;

.c-cta { … }
```

### Blade 分割と SCSS 分割を一致させる

ページの分量が大きい場合、**Blade をセクション単位に分割したら SCSS も同じ粒度・同じ名前で分割する**（1対1対応）。ページ単位のエントリ `_index.scss` で自セクションを `@forward` して束ねる。

```
resources/views/pages/top/          resources/scss/pages/top/
├── index.blade.php                 ├── _pg-top.scss          # @forward で束ねる
├── mv.blade.php            ⇔      ├── _pg-top-mv.scss
├── price.blade.php         ⇔      ├── _pg-top-price.scss
└── faq.blade.php           ⇔      └── _pg-top-faq.scss
```

```scss
// pages/top/_pg-top.scss
// index.blade.php の @include 順（＝ページを上から下に見たセクション順）に合わせる。a→z にはしない
@forward "pg-top-mv";
@forward "pg-top-price";
@forward "pg-top-faq";
```

- **ページ単位のセクションバレルは `@forward` を「a→z」ではなく「ページ内のセクション出現順」に並べる**（＝`index.blade.php` の `@include` 順と一致させる）。上例の `mv` → `price` → `faq` はページを上から下に見た並びであって、アルファベット順（`faq` → `mv` → `price`）ではない
- **理由**: バレルがそのままページの目次になり、Blade 側のセクション並びと 1 対 1 で追える。加えて `@forward` の順は出力される CSS のソース順になるため、カスケードもページの流れ（上→下）に沿う。順序に意味のない `base/` / `components/` / `utility/` などのバレルは従来どおり a→z にする（前掲「`@use` / `@forward`（バレル方式）」）

### 日付名前空間（世代管理）

期間限定 LP・A/B テストなど、**旧版を消さずに並走・切り戻ししたいページ**は、ページ名に年月を付けて世代管理する（`pg-top-202607`, `pg-trial-202603`）。ファイルも年月ディレクトリ（`pages/lp/top/202607/`）で隔離し、公開切り替えは `_index.scss` の `@forward` 差し替えで行う。

- **クラス／ディレクトリ名は実際のページスラッグと一対一で対応させる**（`pg-top-202607` ⇔ スラッグ `/top-202607/`）。世代名と URL の対応を崩さない

## プロパティの並び順

プロパティの宣言順は下記の大枠に従う（[stylelint-config-recess-order](https://github.com/stormwarning/stylelint-config-recess-order) の順序を参考にしている）。方針は **外側の構造から内側の装飾へ**：位置 → 表示・ボックス → サイズ・余白 → タイポグラフィ → 装飾 → 変形・アニメーション。

| #   | バケット                   | 主なプロパティ（この順）                                                                                                            |
| --- | -------------------------- | ----------------------------------------------------------------------------------------------------------------------------------- |
| 1   | 位置                       | `position` / `inset`・`top`・`right`・`bottom`・`left` / `z-index` / `float` / `clear`                                              |
| 2   | 表示・ボックス             | `box-sizing` / `display` / `visibility` → Flexbox（`flex*`・`align-*`・`justify-*`・`gap`・`order`）→ Grid（`grid*`・`place-*`）    |
| 3   | サイズ                     | `width`・`min/max-width` / `height`・`min/max-height` / `aspect-ratio`（論理: `inline-size`・`block-size`）                         |
| 4   | 余白                       | `padding`（`padding-block`・`padding-inline`…）/ `margin`（同）                                                                     |
| 5   | オーバーフロー・コンテナ   | `overflow*` / `overscroll-behavior` / `contain` / `container`                                                                       |
| 6   | タイポグラフィ             | `font*` / `line-height` / `vertical-align` / `color` / `text-*`・`letter-spacing`・`white-space` / `text-decoration`・`text-shadow` |
| 7   | UI・操作                   | `appearance` / `pointer-events` / `cursor` / `user-select` / `outline*`                                                             |
| 8   | リスト・テーブル・生成内容 | `list-style*` / `table-layout` / `content`・`quotes`                                                                                |
| 9   | 背景・ボーダー             | `background*` / `border*` / `border-radius` / `box-shadow`                                                                          |
| 10  | 効果                       | `opacity` / `mix-blend-mode` / `filter`・`backdrop-filter` / `clip-path`・`mask*`                                                   |
| 11  | SVG                        | `fill` / `stroke*`                                                                                                                  |
| 12  | 変形・アニメーション       | `transform`・`translate`・`rotate`・`scale` / `transition*` / `animation*` / `will-change`                                          |

### 適用例

```scss
.c-card {
  // 1 位置
  position: relative;
  z-index: 1;
  // 2 表示・ボックス
  display: flex;
  gap: var(--wpb-size-2);
  // 3-4 サイズ・余白（論理プロパティ block/inline）
  width: min(100% - 32px, 480px);
  padding-block: var(--wpb-size-3);
  margin-inline: auto;
  // 6 タイポグラフィ
  color: var(--wpb-color-text);
  // 9 背景・ボーダー
  background-color: var(--wpb-color-white);
  border-radius: var(--wpb-size-1);
  // 12 変形・アニメーション
  transition: transform 0.2s ease;
  will-change: transform;

  // プロパティ宣言の後にネスト／@include ブロックを置く
  @include hover {
    transform: translateY(-2px);
  }

  & > a {
    color: inherit;
  }
}
```

- 上表の**バケットの順**に、かつ各バケット内も記載順に並べる
- **ネストしたセレクタや `@include media-size` / `@include hover` などのブロックは、そのブロックのプロパティ宣言をすべて書いた後ろに置く**
- **ベンダ由来のコード（`base/_reset.scss` の modern-css-reset 等）は並び順の対象外**。出典元の並びを保ったまま取り込み、差分を追えるようにする

## デザイントークン（`:root` CSS 変数）

色・間隔・フォントなど**共有される値は SCSS 変数ではなく `:root` の CSS カスタムプロパティ（`--wpb-*` トークン）として定義**し、全スタイルがそれを参照する。トークンは `base/variable/` に定義する。

**二層構成**：共有・意味を持つ値はトークン、**そのページだけの一回限りの値は連番ローカル変数**（後述）で持つ。

### カラー（3層：プリミティブ / ベーシック / セマンティック）

**hex の生値を持つのは①プリミティブと②ベーシックだけ（単一の真実源）。③セマンティックは必ず `var()` 参照にする。スタイル側は原則③だけを使う。**

```scss
:root {
  /* ① プリミティブ：色相 + 明度スケール（番号 = 明度。100 明るい → 900 暗い） */
  --wpb-color-orange-300: #fbb679;
  --wpb-color-orange-500: #f99237; /* ブランド基準色 */
  --wpb-color-orange-700: #cc782d; /* 暗い方（hover / 押下用） */

  --wpb-color-gray-50: #f6f6f6;
  --wpb-color-gray-100: #f1f1f1;
  --wpb-color-gray-200: #d9d9d9;
  --wpb-color-gray-300: #d5d5d5;
  --wpb-color-gray-400: #ababab;
  --wpb-color-gray-500: #8a8a8a;
  --wpb-color-gray-600: #6f6f6f;
  --wpb-color-gray-700: #404040;
  --wpb-color-gray-800: #28292b;
  --wpb-color-gray-900: #1a1a1a;

  /* 状態用の色相（フォーム検証・通知）も番号 = 明度で持つ */
  --wpb-color-red-100: #fbe4e4; /* 薄地（通知背景） */
  --wpb-color-red-500: #d33636; /* 基準（枠線・アイコン） */
  --wpb-color-red-700: #a32020; /* 濃い文字 */
  --wpb-color-green-100: #e2f3e8;
  --wpb-color-green-500: #2f9e57;
  --wpb-color-green-700: #1f7a41;

  /* ② ベーシック：スケールに乗らない普遍の絶対色 */
  --wpb-color-white: #ffffff;
  --wpb-color-black: #000000;

  /* ③ セマンティック：用途名で①②を参照（生の hex は持たない） */

  /* テキスト */
  --wpb-color-text: var(--wpb-color-gray-800); /* 本文 */
  --wpb-color-text-weak: var(--wpb-color-gray-500); /* 補足・キャプション */
  --wpb-color-text-accent: var(--wpb-color-orange-500); /* 強調 */
  --wpb-color-text-link: var(--wpb-color-orange-700); /* 本文中テキストリンク */
  --wpb-color-text-cta: var(--wpb-color-white); /* CTA ボタン上の文字 */
  --wpb-color-text-placeholder: var(--wpb-color-gray-400);
  --wpb-color-text-disabled: var(--wpb-color-gray-400);

  /* 背景・面（surface） */
  --wpb-color-background: var(--wpb-color-white); /* 既定の面（カード等） */
  --wpb-color-background-body: var(--wpb-color-gray-50); /* ページ地色 */
  --wpb-color-background-muted: var(
    --wpb-color-gray-100
  ); /* 薄いセクション地 */
  --wpb-color-background-cta: var(--wpb-color-orange-500);
  --wpb-color-background-cta-hover: var(
    --wpb-color-orange-700
  ); /* CTA hover / 押下 */
  --wpb-color-background-disabled: var(--wpb-color-gray-300);

  /* ボーダー・区切り */
  --wpb-color-border: var(--wpb-color-gray-200); /* 既定の枠線 */
  --wpb-color-border-strong: var(--wpb-color-gray-400); /* 強めの枠線 */
  --wpb-color-divider: var(--wpb-color-gray-200); /* 区切り線（hr 等） */

  /* フォーカスリング（a11y：:focus-visible の outline 色） */
  --wpb-color-focus-ring: var(--wpb-color-orange-500);

  /* オーバーレイ（モーダル暗幕）。半透明値をセマンティックに固定 */
  --wpb-color-overlay: color-mix(
    in srgb,
    var(--wpb-color-black) 60%,
    transparent
  );

  /* フィードバック：エラー（フォーム検証・失敗通知） */
  --wpb-color-danger: var(--wpb-color-red-500); /* 枠線・アイコン */
  --wpb-color-danger-text: var(--wpb-color-red-700); /* 薄地の上の濃い文字 */
  --wpb-color-danger-background: var(--wpb-color-red-100); /* 通知の地色 */

  /* フィードバック：成功（送信完了通知など） */
  --wpb-color-success: var(--wpb-color-green-500);
  --wpb-color-success-text: var(--wpb-color-green-700);
  --wpb-color-success-background: var(--wpb-color-green-100);
}
```

セマンティックは用途カテゴリで並べる（テキスト / 背景・面 / ボーダー・区切り / フォーカス / オーバーレイ / フィードバック）。

- **フィードバック（状態）は `-text` / `-background` のセットで持つ**。通知 UI は「薄い地色（`-background`）＋濃い文字（`-text`）＋基準色の枠線（無印）」の3点で成立するため。エラー・成功以外に警告・情報が要れば、同様に `amber-*` / `blue-*` をプリミティブに足して `--wpb-color-warning-*` / `-info-*` を追加する
- **半透明の固定値（オーバーレイ）もセマンティックに置ける**。値は `color-mix()` で作ってトークン化すれば、暗幕の濃さを1箇所で管理できる

**命名ルール**

- **プリミティブは「hue 名 + 明度スケール」**（`orange` / `gray` / `red` / `green`、番号 = 明度で 100 明るい → 900 暗い）。「何色か」をそのまま名前にする（値が一目で分かる）。無彩色は `gray`。中間色は `250` のような番号で足せる
- **役割（強調・CTA・本文など）はセマンティック層で表す**。プリミティブは色相と明度だけを持ち、用途は③が担う
- **スタイル側は③セマンティックだけ参照する**（`color: var(--wpb-color-text)`）。①②は直接参照しない。色を変えるときは③の参照先か①の値を差し替えれば全体に波及する
- 状態色（エラー等）もセマンティックに置く（`input[aria-invalid="true"] { border-color: var(--wpb-color-danger); }`）。個別スタイルに `#c00` などを直書きしない

### 不透明度は `color-mix()` で作る

半透明の色は **`color-mix()` でトークンから直接作る**。トークンは hex 一本で持ち、透明度は都度 `color-mix` で合成する。

```scss
background: color-mix(in srgb, var(--wpb-color-black) 6%, transparent);
```

- `color-mix(in srgb, <色> <割合>%, transparent)` で不透明度 `<割合>%` の色になる
- セマンティックトークンにもそのまま使える（`color-mix(in srgb, var(--wpb-color-text) 60%, transparent)`）

### 間隔スケール

```scss
:root {
  --wpb-size-0: 4px;
  --wpb-size-1: 8px;
  /* … 8px 系で */
  --wpb-size-10: 80px;
}
```

- **`padding` / `margin` / `gap` は直値 px を避け、`var(--wpb-size-N)` を使う**（`padding-block: var(--wpb-size-3)`）。スケールは `--wpb-size-0: 4px` を基点に **8px 刻み**（8 / 16 / 24 … 80）
- **例外は、スケールのどの値とも一致しない一回限りの値をブロック内ローカル変数（`$_size0N`）で受ける場合のみ**（後述「トークンに載せるかの判断（実務フロー）」）

### その他トークン

`base/_base.scss` などに集約する。

| トークン                            | 用途                                                               |
| ----------------------------------- | ------------------------------------------------------------------ |
| `--wpb-font-jp` / `--wpb-font-en`   | フォントファミリ（`base/_base.scss` で定義済み）                   |
| `--wpb-z-index-header` ほか         | z-index の一元管理（重なり順をトークンで統制。必要になったら追加） |
| `--wpb-radius-default-max: 100vmax` | 完全な角丸（ピル型ボタン）用（必要になったら追加）                 |
| `--wpb-mobile-width: 375px`         | モバイル基準幅（必要になったら追加）                               |

### ページ・コンポーネント固有の一回限りの値はローカル連番変数

**トークンに載せるほど共有しない、そのページ・そのコンポーネント・そのブロックだけの色／サイズ**は、ローカル変数を**連番**で機械的に命名する（命名負荷の軽減、IDE 補完、値入れ替え時の名前ズレ防止）。CSS Modules（`style.module.scss`）も同じ扱い。

```scss
.pg-top-mv {
  $_color01: #16171a; // このページだけの背景色 → ローカル変数
  $_color02: #333;
  $_image-dir: "pages/top/";

  color: var(--wpb-color-text-cta); // 共有色 → トークン
  padding-block: var(--wpb-size-3); // 共有間隔 → トークン
  background-color: $_color01; // 固有色 → ローカル変数
}
```

**判断基準**：複数ページ・複数コンポーネントで共有する／意味（用途）を持つ値＝トークン。そのページ・そのコンポーネントだけの一回限りの値＝`$_color0N` / `$_size0N`。

### トークンに載せるかの判断（実務フロー）

上の判断基準を実装時の手順に落とすと次の順で決める。判断基準の「意味（用途）を持つ値＝トークン」は 1 で、「共有する値＝トークン」は 4 で判定する（置き換えではなく判定順序の明文化）。

**対象は色（`--wpb-color-*`）と間隔（`--wpb-size-*`）**。z-index / radius / 基準幅などの構造トークンは前掲「その他トークン」の「必要になったら追加」に従い、このフローの対象外。

1. **その値に用途名を言い切れるか**
   - 言い切れる（本文 / 枠線 / フォーカスリング / CTA 背景 / セクション間の余白 … 定型の用途）→ **初回でも**③セマンティックに用途トークンを足して参照する
   - 言い切れない（「このセクションの背景」「このボタンだけの内側余白」しか言えない）→ `$_color0N` / `$_size0N` で受ける
2. **①②を直接参照したくなったら、③のトークンが足りていないサイン**。①②を借りずに③へ用途トークンを足す（①②直参照の禁止理由は前掲「カラー（3層）」）
3. **デザインの値が既存トークンと僅かに違う場合は既存トークンに丸める**
   - 色: 同じ色相で明度差が視認できない程度（`#333` ⇔ `#343434` など）→ 確認せず既存トークンに寄せる。カラーピッカーのブレであってデザインの意図ではない
   - 間隔: `--wpb-size-*` のいずれかと **2px 以内の差なら寄せる**（`10px` → `var(--wpb-size-1)`）
   - 色相が違う／差が見える／`--wpb-size-*` のどの値とも一致せず寄せると崩れる → 意図があり得るのでローカル変数で受ける（判断に迷う幅なら丸める側に倒す）
4. **用途名を言い切れず「共有するかもしれない」だけの値は、1回目はローカル変数に置く**。2ページ目・2コンポーネント目で同じ値が同じ用途で現れた時点で③へ昇格し、両方を置換する。「今後共有するか」を1回目に予測しない
   - **1 で用途名が確定している値は 1 の判定が優先**（初回でも③に足す）。この「2回目」ルールは用途名が曖昧な値の据え置き基準
5. **①プリミティブは実装前に確定させる**（③の昇格判断とは別レイヤーの作業）。Figma の全色を洗い出して近似色を統合しておく。ページを作りながら①を足すと色数が膨らみ、後から統合できなくなる

**避けたい形**：用途を説明していないトークンを③に作ること。3 で丸めるか 4 で据え置くのが正しい。

```scss
// ✕ 用途名になっていない（次の人が使い所を判断できない）
--wpb-color-background-slightly-dark: var(--wpb-color-gray-900);

// ✕ 一回限りの値を①②から借りている（①を変えたとき無関係なページが巻き添えを食う）
.pg-top-mv {
  background-color: var(--wpb-color-gray-900);
}

// ○ 用途があるなら③に足す
--wpb-color-background-section-dark: var(--wpb-color-gray-900);

// ○ 用途が無いならローカル変数で閉じる
.pg-top-mv {
  $_color01: #343434;
  background-color: $_color01;
}
```

`--wpb-size-*` のどの値とも一致しない間隔も同じ扱いにする（フォームコントロールの `6px` / `10px` / `12px` など）。トークンの連番を崩して差し込まず、ブロック内のローカル変数で受ける。

```scss
.field {
  $_size01: 6px; // --wpb-size-* に一致しないコントロール固有の値
  $_size02: 10px;

  gap: $_size01;

  input {
    padding-block: $_size02;
  }
}
```

**連番はブロック単位に 01 から振り直す**（同じ `12px` が別ブロックで `$_size01` / `$_size03` になるのは許容）。同一ファイルの複数ブロックで同じ値を共有したいときだけ、ファイル冒頭（セレクタ外）で宣言して各ブロックから参照する。役割名を持つ非値変数（`$_image-dir` など）は連番規則の対象外。

**複数ページで共有される間隔で `--wpb-size-*` に一致しないもの**（同じ `clamp()` を使う本文領域の上下余白など）は、連番スケールに割り込ませず **mixin か `fluid-clamp()` 呼び出しの共通化**で1箇所に寄せる。ローカル変数を各ページにコピーしない。

## SCSS 命名規則

### 接頭辞体系

| 接頭辞 | 用途                                               | 例                               |
| ------ | -------------------------------------------------- | -------------------------------- |
| `pg-`  | ページ固有                                         | `pg-column`, `pg-store`          |
| `c-`   | コンポーネント（再利用パーツ）                     | `c-button`, `c-card`             |
| `u-`   | ユーティリティ                                     | `u-text`, `u-display`            |
| `--`   | 状態・ラベル／名前付き要素コンテナ（マルチクラス） | `--active`, `--main`, `--header` |

JS フック用のクラスは付けない。**DOM 操作の対象は `data-*` 属性で表現する**（[javascript.md](javascript.md) 参照）。「クラス = 見た目、`data-*` = JS フック・状態」の責務を分離する。

### ブロック粒度ルール（`__` を使わない BEM 派生）

BEM をベースとしつつ、**「エレメントクラス（`__`）は使わない」** という独自ルール。クラス名が長くなる問題と粒度問題を回避する。

- **ブロック単位（セクション・コンポーネント）まではクラスを付ける**
- **ブロック内の要素はタグセレクタで指定する**（`h2`, `p`, `img`, `a` 等）
- 意味のない `<div>` にはクラスを振らない（`& > div` 等の直接子セレクタで指定できる構造を選ぶ）

```scss
.pg-column {
    &-mv {                  // .pg-column-mv ← ブロック単位までクラス
        > hgroup {          // 内側はタグセレクタで指定
            h2 { … }
            p { … }
        }
        i.--new { … }       // 状態はマルチクラス
    }

    &-card {                // .pg-column-card ← 別のブロック
        > a { … }
    }
}
```

### 要素の指定順序（`__` を使わないための優先順位）

ブロック内の要素は、次の優先順位で指定する。**既定は「クラスを付けない」**（タグセレクタで当てる）。`__` でエレメントクラスを増やさないのはもちろん、**`--` クラスも安易に付けない**——1 → 2 で当たらないときの最終手段。

1. **タグセレクタ直接指定**（`h1` ~ `h6`, `p`, `img`, `a`, `ul` 等）。
2. **`:nth-child(n)` / 隣接セレクタ / `:is()`**（`<div>` が複数並ぶなどタグで区別できないとき）
3. **名前付き `--` 要素コンテナ**（上記でも意味付けが必要なとき。乱発しない — 下記）

```scss
// 2: タグで区別できないときは :is() や :nth-child で
figure :is(img, video) { object-fit: cover; }
.--main > div:nth-child(1) { … }
```

### 名前付き `--` 要素コンテナ（乱発しない）

`--` は **「領域の名札」であって「要素のラベル」ではない**。タグ・`:nth-child` で表現しづらい**ブロック直下の主要な区画**だけに、`__` の代わりに先頭ダブルダッシュの単一クラスを付ける。典型は次の3〜4個に収まる。

```scss
.pg-top-mv {
  .--background { … }   // 背景・スライダー領域
  .--header { … }       // ロゴ・見出し領域
  .--main { … }         // 本体
  .--footer { … }       // CTA 等のフッター領域
}
```

**付けてよい条件（すべて満たすときだけ）**

- section / component が分かれる **主要な区画** である（見出し・画像などの中身要素ではない）
- タグ・`:nth-child` では他の区画と区別できない
- ブロックの **直下** の階層である

**付けない**

- コンテンツ要素（`h1`〜`h6` / `p` / `img` / `a` / `ul` / `li` / `figure` …）→ タグセレクタ（`.--header { h2 { … } }`）
- レイアウトのための wrapper / inner `<div>` → `& > div` / `:nth-child`
- 状態・JS フック → `data-*`（`--` ではない）
- **`--` 領域の中に、さらに `--` をネストしない**。領域内はタグ / `:nth-child` / `& > div` で当てる

**目安**: 1ブロックの `--` は **3〜4個まで**（`--header` / `--main` / `--footer` / `--background` 程度）。それ以上に増えるのは、タグ・`:nth-child` で当てるべき所に `--` を使っているサイン。

**before / after**（生成でありがちな乱発 → あるべき疎な形）

```blade
{{-- ✕ 要素ごとに -- を乱発（wrapper・見出し・リスト項目にまで付けている） --}}
<section class="pg-top-reason">
  <div class="--inner">
    <div class="--head"><h2 class="--title">…</h2><p class="--lead">…</p></div>
    <ul class="--list">
      <li class="--item"><h4 class="--item-title">…</h4><p class="--item-text">…</p></li>
    </ul>
  </div>
</section>

{{-- ○ -- は領域だけ。wrapper は無クラス、中身はタグセレクタで当てる --}}
<section class="pg-top-reason">
  <div>
    <div class="--header">
      <h2>…</h2>
      <p>…</p>
    </div>
    <div class="--main">
      <ul>
        <li><h4>…</h4><p>…</p></li>
      </ul>
    </div>
  </div>
</section>
```

### 状態・ラベルクラス（`--xxx`）

ケバブケースで状態・ラベルをそのまま表記する（マルチクラス）。`--` は「名前付き要素コンテナ」と「状態・ラベル」の両用途で使う。

```html
<button class="c-button --next">次へ</button>
<i class="--new"></i>
<a class="--active">タブ</a>
```

### クラス名に「ad」を含めない

広告ブロック拡張で要素ごと非表示にされるリスクがあるため。販売促進系は `pr-`, `promo-` 等を使う。

## DOM フックと状態表現（`data-*`）

`data-*` 属性は用途で2系統に分ける。**クラスは見た目、`data-*` は状態と JS フック**。JS がクラスを付け外ししてスタイル副作用を生む事故を防ぐ（[javascript.md](javascript.md) と対）。

### 2系統

1. **状態（真偽値）→ CSS で分岐**：`[data-show="true"|"false"]`, `[data-window-scrolled="true"]`

```scss
.c-cta {
  transition:
    transform 0.3s ease,
    opacity 0.3s ease,
    visibility 0.3s ease;

  &[data-show="false"] {
    visibility: hidden;
    opacity: 0;
    transform: translateY(100%);
  }
  &[data-show="true"] {
    visibility: visible;
    opacity: 1;
    transform: translateY(0);
  }
}
```

2. **JS フック／スコープ名**：`[data-element="header-drawer-content"]`。JS が要素取得に使い、CSS からも参照できる

### `hidden` と `data-show` の使い分け

要素の出し入れは、**遷移（アニメーション）の要否**で選ぶ。

|                | `hidden` 属性                                  | `[data-show="true｜false"]`                                      |
| -------------- | ---------------------------------------------- | ---------------------------------------------------------------- |
| 実体           | HTML 標準属性（UA が `display: none` 相当）    | 独自 data 属性を CSS 属性セレクタで拾う                          |
| アニメーション | **不可**（`display` 切替は遷移できない）       | **可**（`opacity` / `transform` / `visibility` を transition）   |
| a11y           | アクセシビリティツリーから除外＝読み上げ対象外 | DOM に残る（`visibility` 等で読み上げも制御）                    |
| 使いどころ     | アニメ不要の単純な有無切替・完全非表示         | フェード／スライドで出し入れする要素（CTA バー・モーダル本体等） |
| JS             | `el.hidden = true`                             | `el.dataset.show = "false"`                                      |

→ **遷移が要る隠し方＝`data-show`、要らない完全非表示＝`hidden`**。

## レイアウト・モダン CSS

### 中央寄せコンテナ

内側ラッパの横幅制御は `min()` を使う。ラッパ用 `<div>` にはクラスを振らず `& > div` で当てる（wrapper / inner にクラスを付けない）。

```scss
& > div {
  width: min(100% - 32px, 1440px); // 広ければ最大幅で頭打ち、狭ければ % で追従
  margin-inline: auto;
}
```

- CSS 変数による間接化（`--padding-inline` / `--max-width` の上書き）は使わず、**素直な `min()` 直書き**にする

### 論理プロパティ（`block` / `inline` ショートハンド）

物理プロパティ（`margin-left` 等）ではなく**論理プロパティを使う**。かつ `-start` / `-end` を分けて書かず、**`block` / `inline` の2値ショートハンド**で表す。

```scss
padding-block: var(--wpb-size-10) var(--wpb-size-3); // 上 下
padding-inline: var(--wpb-size-3); // 左右まとめて
margin-inline: auto;
inset: 0;

// ✕ padding-block-start / padding-inline-end のような片側専用プロパティは使わない
```

単独 transform プロパティ（`translate` / `rotate` / `scale`）や `:is()` / `clamp()` / `aspect-ratio` / `color-mix()` / `100dvh` などモダン CSS を積極的に使う。

### モーダル／ドロワーはネイティブ `<dialog>`

モーダル・ドロワーは div ＋自前オーバーレイではなく **`<dialog>` 要素**で実装する（開閉状態・フォーカストラップ・Esc 閉じをブラウザが持ちアクセシブル）。reset で `dialog` / `dialog::backdrop` を初期化しておく。

```scss
.c-store-modal {
  // これ自体が <dialog>
  position: fixed;
  inset: 0;
  background: var(
    --wpb-color-overlay
  ); // 暗幕は dialog 自身（セマンティックの半透明トークン）

  &[open] {
    // ネイティブの open 属性で開状態を判定
    display: flex;
  }

  [data-element="store-modal-content"] {
    // 本体（開閉アニメは JS 側）
    height: 100vh;
    height: 100dvh; // dvh フォールバック
    overscroll-behavior: contain;
  }
}
```

- 閉時は UA の `display: none` を活かす。開閉アニメは JS 側で制御する（責務分離）

## モーション

- アニメーションする要素には `will-change`（合成レイヤー昇格のヒント）を付ける
- **`@keyframes` はセクションローカルに置く**。`@keyframes` はネストしてもグローバルスコープなので、**セレクタと同じプレフィックス付きの名前**にし、使用箇所と同じファイルに同居させる（名前衝突防止）

```scss
.pg-top-marquee {
  ul {
    animation: pg-top-marquee 72s linear infinite;
  }
}
@keyframes pg-top-marquee {
  to {
    transform: translateX(-50%);
  }
}
```

- `@for` ループで stagger（連番遅延）を生成する場合も同ファイル内で完結させる
- モーション無効化は reset の `@media (prefers-reduced-motion: reduce)` で全体に効かせる（個別ページで書かない）

## mixin / 関数

`base/` 配下に共通の mixin / 関数 / トークンが定義されている。スタイル記述では原則これらを通して書き、生のメディアクエリや `background-image` を直接書かない。

### メディアクエリ

| mixin                         | 引数                               | 用途                           |
| ----------------------------- | ---------------------------------- | ------------------------------ |
| `@include media-width($数値)` | 単位なしの px 数値                 | 任意の値でブレークポイント発火 |
| `@include media-size($key)`   | 定義済みキー（省略可・既定 `"m"`） | 既定ブレークポイントで発火     |

ブレークポイント定義（`base/variable/_breakpoint.scss`）:

| キー  | 条件                | 想定端末                     |
| ----- | ------------------- | ---------------------------- |
| `xxs` | `max-width: 320px`  | 初代 SE                      |
| `xs`  | `max-width: 374px`  | iPhone 6 以降 / 古い Android |
| `s`   | `min-width: 481px`  | ファブレット・スマホ横       |
| `m`   | `min-width: 768px`  | iPad                         |
| `l`   | `min-width: 1024px` | iPad 横                      |
| `xl`  | `min-width: 1180px` | 通常ノート                   |
| `xxl` | `min-width: 1440px` | 高解像度 PC                  |

**基本は `media-size` を使い、定義にない値が必要な時だけ `media-width` を使う**。

**`m`（`min-width: 768px`）は最頻出のためデフォルト引数になっている。`m` で発火させる時は引数を省略して `@include media-size()` と書く**（`@include media-size("m")` とは書かない）。他のキーを使う時だけ明示的に渡す。

```scss
.pg-column {
  font-size: 14px;
  @include media-size() {
    // 引数省略 = "m"（768px〜）
    font-size: 18px;
  }
  @include media-size("l") {
    // m 以外は明示的に渡す
    font-size: 22px;
  }
  @include media-width(1100) {
    // 1100px は未定義なのでこちら
    font-size: 20px;
  }
}
```

### 流体タイポグラフィ

| 関数                                    | 用途                   |
| --------------------------------------- | ---------------------- |
| `fluid-clamp($min, $max, $minW, $maxW)` | clamp 値を計算して返す |

- **引数の書式を統一する**：**サイズは px 付き、基準幅は単位なし**。既定基準幅は `375` と `768`（`fluid-clamp()` のデフォルト引数）

```scss
h1 {
  font-size: fluid-clamp(18px, 32px, 375, 768);
}
```

`font-size` だけでなく `padding` / `margin` / `gap` などのスペーシングにも使える。

### 背景画像

| mixin                                  | 用途                                              |
| -------------------------------------- | ------------------------------------------------- |
| `@include bg-image-path($path, $file)` | `$imagePath` と連結して `background-image` を出力 |

`$imagePath` は `base/variable/_path.scss` で `/wp-content/themes/wpblade/resources/images/` として定義済み。`$_image-dir` は `images/` からのサブディレクトリを含めて指定する（画像の配置・命名規則は [image.md](image.md) 参照）：

```scss
// base/ 配下: ロゴ・共通アイコンなど
.c-logo {
  $_image-dir: "base/";
  @include bg-image-path($_image-dir, "logo_01--20260101.svg");
}

// pages/ 配下: ページ固有素材
.pg-column {
  $_image-dir: "pages/column/";

  &-mv {
    @include bg-image-path($_image-dir, "mv_01-l--20260101.webp");
  }
}
```

背景画像の `url()` は絶対パス（`/wp-content/themes/wpblade/resources/...`）で書く。

### ホバー

| mixin            | 用途                                                           |
| ---------------- | -------------------------------------------------------------- |
| `@include hover` | `:hover`（`any-hover: hover`）＋ `:focus-visible` を同時に適用 |

a11y 観点から、**`:hover` 単体は使わず必ず `hover` mixin を使う**。キーボード操作時のフォーカス状態にも同じスタイルが当たる。

```scss
.c-button {
  color: var(--wpb-color-text);
  @include hover {
    color: var(--wpb-color-text-accent);
  }
}
```

### a11y

| mixin                      | 用途                                                 |
| -------------------------- | ---------------------------------------------------- |
| `@include visually-hidden` | 視覚的に非表示にしつつスクリーンリーダーには読ませる |

スクリーンリーダー向けのラベル・見出しを HTML に置きつつ、視覚的には隠したい場合に使う。

### その他

| 関数               | 用途                                               |
| ------------------ | -------------------------------------------------- |
| `strip-unit($num)` | 数値から単位を取り除く（主に他の関数の内部で使用） |

### `!important` 原則禁止

`!important` は原則使わない。**例外は reset / `visually-hidden` / ユーティリティクラス / `prefers-reduced-motion` のみ**。詳細度は入れ子構造とセレクタ設計で解決する。
