---
paths:
  - "**/resources/js/**"
---

# フロントエンド規約：JavaScript（Vanilla JS）

`resources/js/` 以下は Vanilla JS 専用。`resources/ts/` の React + TypeScript とは分離して管理する。関連: [scss.md](scss.md) / [blade.md](blade.md) / [vite.md](vite.md) / [accessibility.md](accessibility.md)

## ビルド・依存管理

- `npm` で依存を管理し、CDN importmap は使わない
- Vite でバンドルするため `type="module"` の直書きは不要
- エントリは `resources/js/script.js`
- `@js` エイリアスで `resources/js/` を参照（`vite.config.js` の `resolve.alias` で設定済み）
- **外部ライブラリは使うモジュールの中で個別に import する**（グローバル読み込みはしない）。Vite が import を辿ってバンドル・tree-shaking するため
- 標準ライブラリ: **gsap**（アニメーション）/ **Splide**（スライダー）
- **ライブラリの CSS も JS から import する**（`import '@splidejs/splide/css';`）。そのライブラリを使うページにだけ CSS が乗る

```js
import { gsap } from "gsap";
import { Splide } from "@splidejs/splide";
import "@splidejs/splide/css";
```

## ディレクトリ構成

```
resources/js/
├── script.js              # エントリポイント
├── base/                  # 全ページ共通インフラ（自律起動するスクリプト）
├── services/              # ドメインロジック（pages / components から呼ぶ）
├── repositories/          # fetch / API 呼び出し（services/ からのみ呼ぶ）
├── pages/                 # ページ別 JS
├── components/            # 再利用される UI コンポーネント
├── constants/             # 定数
└── utils/                 # 汎用ユーティリティ
```

## 呼び出し関係

```
pages/ components/
  └→ services/      ← ドメインロジック
       └→ repositories/  ← fetch / API 呼び出し
```

- `repositories/` を直接呼べるのは `services/` のみ。`pages/` / `components/` / `base/` からは呼ばない
- `services/` を直接呼べるのは `pages/` と `components/` のみ
- `base/` はどの層にも依存しない（自律起動）

`base/` に置くスクリプトはページ判定なしで全ページ実行される。`script.js` から直接 import + 呼び出す。

`services/` は PHP の `app/Services/` に相当するドメインロジック層。`pages/` や `components/` に書くには重い処理（データ加工・複数 repository の組み合わせなど）を逃がす場所。`script.js` からは呼ばない。JSDoc 必須。

`repositories/` は fetch / REST API 呼び出しの薄いラッパー。ビジネスロジックを持たず、リクエストとレスポンスの変換だけを担う。`services/` からのみ呼ぶ。JSDoc 必須。

## 層ごとの実装パターン

### services / repositories はオブジェクトリテラル（メソッド集約）

`services/` `repositories/` は関数を並べず、**1つの `const xService = { ... }` オブジェクトにメソッドを集約**して名前付き export する。メソッド同士の呼び出しは **`this` ではなく自分のオブジェクト名で参照**する（コールバックに渡しても `this` バインドが外れて壊れない）。

```js
const shareService = {
  FEEDBACK_RESET_MS: 2000, // 関連する定数もオブジェクトに同居

  getElement() {
    /* … */
  },

  buildShareText() {
    const { name, message, url } = shareService.getData(); // ← this ではなく自名参照
    const shareUrl = shareService.buildShareUrl(url, name);
    return [`${name}${message}`, shareUrl].filter(Boolean).join("\n");
  },
};

export { shareService };
```

### DOM 取得は `getElement(s)` に集約する

各モジュールで DOM 取得を専用メソッド `getElement()` / `getElements()` に集約する。取得対象はすべて `data-element` 属性。**「要素が無いとき」の振る舞いは層で分ける**。

- **services は `throw`**（その要素が無ければ機能が成立しない前提）
- **components / pages は早期 return**（そのページに要素が無いのは正常。何もせず抜ける）

```js
// services: 無ければ throw（どの data-element が無いかを日本語で明記）
getElement() {
  const cta = document.querySelector('[data-element="cta"]');
  const guards = document.querySelectorAll("[data-hide-cta]");
  if (!cta || !guards.length) {
    throw new Error('追従CTA（data-element="cta"）または非表示トリガー（data-hide-cta）が見つかりません');
  }
  return { cta, guards };
}

// components: 無ければ黙って return
const getElements = () => ({ modals: document.querySelectorAll('[data-element="store-modal"]') });
const { modals } = getElements();
if (!modals.length) return;
```

### 層ごとのエラー戦略

| 層            | 要素/機能が無いとき                     | 例外の扱い                                |
| ------------- | --------------------------------------- | ----------------------------------------- |
| `utils/`      | 純粋関数。必要なら `throw` / `reject`   | 呼び出し側が処理                          |
| `base/`       | 自律起動。自前で完結                    | —                                         |
| `components/` | ルート要素が無ければ**早期 return**     | modal / header は本体を `try-catch` も    |
| `services/`   | `getElement` で**`throw`**              | 呼び出し側（page）の `try-catch` が受ける |
| `pages/`      | `pathMatch` で対象外なら**早期 return** | 本体を `try-catch` で囲む                 |

開発時は `throw` でマークアップ漏れが目立ち、本番は無関係ページで静かに抜ける、というバランスを保つ（後述の「エラーハンドリング」参照）。

## コンポーネントの実装パターン

### スライダーは Splide の薄いラッパ

各スライダーは `components/sliders/` に1ファイル。中身は **「`data-element` を引く → 無ければ早期 return → `new Splide(el, config).mount()`」だけ**の薄いコンポーネント。共通 config は `constants/sliderConstant.js`、個別差分はスプレッドで上書きする。**ページから中身を差し替える必要があるものだけ instance を return** する。

```js
// 設定だけ・戻り値なし
const mvSlider = () => {
  const el = document.querySelector('[data-element="mv-slider"]');
  if (!el) return;
  new Splide(el, { type: "fade", autoplay: true, interval: 6000 }).mount();
};

// 中身を後で差し替えるので instance を返す
const locationSlider = () => {
  const el = document.querySelector('[data-element="location-slider"]');
  if (!el) return null;
  const splide = new Splide(el, { ...SLIDER_BASE_CONFIG, arrows: false });
  splide.mount();
  return splide; // page が slider.refresh()/go() する
};
```

データの動的差し替えなどのロジックはスライダーに持たせず、**service 側**（`studioService.updateNearbySlider(slider)`）に置いてページがつなぐ。

### モーダル / ドロワーは `<dialog>` ＋ `createDialog()`

モーダル・ドロワーはネイティブ `<dialog>` を、共通ユーティリティ `utils/dialog.js` の `createDialog(dialog, options)` でラップして扱う。

- **開閉の土台（外側クリック・Escape・スクロールロック・暗幕フェード）は util が共通処理**
- **モーダルごとの動き（スライド方向など）はコンポーネントがコールバックで差し込む**（`openContentAnimation` / `closeContentAnimation`）
- backdrop（`<dialog>` 本体）は常に `autoAlpha` フェードのみ（動かさない）。移動はコンテンツ本体に当てる
- レイアウト・暗幕色・初期非表示は **SCSS 側**、開閉アニメは **JS 側**
- `modal: true` = `showModal()`（背景 inert・`::backdrop` あり・Escape の `cancel` を差し替え）/ `modal: false` = `show()`（ドロワー用・背景操作可・Escape は自前 keydown）
- 背面スクロールは `utils/toggleScrollLock.js`（`<body>` を `overflow: hidden`）
- **動的に差し込まれるトリガーにも効くよう、クリックは `document` へイベント委譲**（`data-modal-target` の値で `data-modal-id` の dialog を引く）

```js
createDialog(modal, {
  contentSelector: '[data-element="store-modal-content"]',
  openContentAnimation: (content) =>
    gsap.fromTo(content, { y: 24 }, { y: 0, duration: 0.25 }),
  closeContentAnimation: (content) =>
    gsap.to(content, { y: 24, duration: 0.25 }),
});
```

## 要素状態の操作は `utils/element.js` ヘルパ経由

DOM の状態変更は直接いじらず、`utils/element.js` の小さなヘルパを通す（存在チェック `element && …` 内蔵で安全）。単数形と複数形（`...elements`）のペアで提供する。

| ヘルパ                                            | 操作                                    |
| ------------------------------------------------- | --------------------------------------- |
| `showElement` / `hideElement`                     | 表示 / 非表示（`el.hidden` プロパティ） |
| `enableElement` / `disableElement`                | `disabled` の切替                       |
| `ariaDisableElement` / `ariaEnableElement`        | `aria-disabled` の切替                  |
| `ariaSelectElement` / `ariaUnselectElements`      | `aria-selected` の切替                  |
| `setTabindexFocusable` / `setTabindexUnfocusable` | `tabindex="0"` / `"-1"`                 |
| `focusElement`                                    | `focus()`                               |

- **表示 / 非表示は `hidden` プロパティで行う**（`showElement` / `hideElement`）。これは SCSS の「`hidden`（遷移不要の完全非表示）と `data-show`（遷移ありの出し入れ）の使い分け」の JS 側の対。遷移が要らない出し入れはこのヘルパ、遷移が要るものは `data-show` を書く
- `disabled` / ARIA / `tabindex` / `focus` をヘルパ経由に統一すると、状態変更と ARIA 同期が漏れにくい（[accessibility.md](accessibility.md) 参照）

## スクロール連動は `IntersectionObserver`

「スクロールでヘッダーを縮める」「画面内に入ったら計測 / 表示切替」などは、`scroll` イベントリスナーではなく **`IntersectionObserver`** で実現する（`scroll` の高頻度発火・位置計算を避ける）。判定結果は `data-*` に書き、見た目は CSS が担当する。

```js
// base/windowScroll.js: 最上部に番兵 div を置き、画面外に出たかを監視して <html> に反映
const sentinel = document.createElement("div");
document.body.append(sentinel);
new IntersectionObserver(([entry]) => {
  root.dataset.windowScrolled = String(!entry.isIntersecting);
}).observe(sentinel);
```

各コンポーネントは `scroll` を張らず `[data-window-scrolled="true"]` で見た目を切り替える。

## 互換・堅牢性ユーティリティ（feature-detect ＋ fallback）

ブラウザ差・既知の不具合を吸収する util は、**機能検出して分岐し、非対応時はフォールバック／穏当に失敗**する形で書き、`utils/` に隔離する（pages / services はハッピーパスに集中させる）。

```js
// Clipboard API 優先、非対応・失敗は textarea + execCommand へ
const writeClipboard = async (text) => {
  if (navigator.clipboard?.writeText) {
    try {
      await navigator.clipboard.writeText(text);
      return true;
    } catch (e) {
      console.error(e); // 権限拒否等 → 下へフォールバック
    }
  }
  // textarea + execCommand フォールバック …
};
```

- `navigator.xxx?.` の optional chaining で機能検出する（Clipboard / Geolocation など）
- ブラウザ固有バグ（iOS Safari の bfcache で lazy 画像が固まる等）への対処は発火条件を絞って副作用を最小化する（`pageshow` の `e.persisted` のみ）

## dataLayerInsight（GTM トラッキング）

`resources/js/base/dataLayerInsight.js` が実装本体。`script.js` から無条件で呼ばれる全ページ共通インフラ。

3種のトラッカーを提供する。いずれも HTML 属性で計測対象を宣言し、JS 側はイベント委譲 / Observer で受け取る。

| 関数                  | 属性                 | 発火タイミング                                       |
| --------------------- | -------------------- | ---------------------------------------------------- |
| `trackingDataClick()` | `data-click-insight` | クリック時                                           |
| `trackingDataInput()` | `data-input-insight` | フォーカスアウト / change 時（バリデーション通過後） |
| `trackingDataShow()`  | `data-show-insight`  | 要素がビューポートに入った時（1回限り）              |

共有属性 `data-param-insight`: 個々の要素を区別する識別子を `n_param` として送信する。**イベント名を増やさず、差分はここ（param）で表す**のが基本（後述「param でイベント数を減らす」）。固定 ID・選択肢コードなど非 PII の識別子のみ（ユーザー入力値＝PII は入れない）。

**HTML での使い方**

```html
<button data-click-insight="click_top_header_cta">CTA</button>
<button
  data-click-insight="click_choice_page_increment"
  data-param-insight="1001"
>
  カートへ
</button>
<input type="text" name="zip" data-input-insight="input_checkout_page_zip" />
<input
  type="checkbox"
  data-input-insight="input_mypage_preferences_allergy"
  data-param-insight="25"
/>
<section data-show-insight="show_toppage_plan">
  <section
    data-show-insight="show_toppage_plan"
    data-param-insight="meal_6"
  ></section>
</section>
```

**イベント名の命名規則**

`ALLOW_EVENT_PREFIXES` に含まれるプレフィックスで始まる名前だけが送信される。パターン: `{prefix}{ページ名 または global}_{セクション名}_{要素の種類}`

- **`{要素の種類}` は役割の種類（`nav` / `cta` / `link` / `card` / `section` など）を書き、個々の項目名は書かない**。個々の違いは `data-param-insight` で区別する（下記）
- サイト共通パーツ（ヘッダー・フッター・モーダル等）は `{ページ名}` の代わりに `global` を使う

| プレフィックス | トラッカー          | 例                        |
| -------------- | ------------------- | ------------------------- |
| `click_`       | `trackingDataClick` | `click_global_header_nav` |
| `input_`       | `trackingDataInput` | `input_checkout_page_zip` |
| `show_`        | `trackingDataShow`  | `show_top_section`        |

**param でイベント数を減らす（重要）**

**同じ意味の要素の集まり（ナビ・タブ・カード・選択肢・各セクション等）には共通のイベント名を1つだけ与え、個々の違いは `data-param-insight` で区別する**。要素ごとに別イベント名を作らない（イベント名の増殖を防ぎ、集計をしやすくする）。

```blade
{{-- ✕ 項目ごとにイベント名を増やす --}}
<a data-click-insight="click_global_header_about">会社概要</a>
<a data-click-insight="click_global_header_store">店舗</a>
<a data-click-insight="click_global_header_faq">FAQ</a>

{{-- ○ イベント名は1つ、項目は param で区別 --}}
<a data-click-insight="click_global_header_nav" data-param-insight="about">会社概要</a>
<a data-click-insight="click_global_header_nav" data-param-insight="store">店舗</a>
<a data-click-insight="click_global_header_nav" data-param-insight="faq">FAQ</a>
```

- 動的な項目（一覧・投稿・店舗など）は `data-param-insight="{{ $item['id'] }}"` のように ID を入れる
- セクションの表示計測も同様に `show_top_section` を共有し、`data-param-insight="mv" | "flow" | …` で区別する
- 迷ったら「**イベント名 = 何をしたか（種類）／ param = どれで**」で切り分ける

**注意事項**

- **PII 禁止**: `data-param-insight` にユーザー入力値（メール・名前等）を入れない。固定 ID や選択肢コードのみ許可
- **広告ブロック回避**: `analytics` / `tracking` / `gtm` / `ga4` 等を HTML 属性名に含めない。`insight` を使う
- `data-input-insight` は INPUT / TEXTAREA / SELECT に直接付ける（wrapper 要素には付けない）

## ページ別 JS のテンプレ

**ページ関数はオーケストレーション層**。主な役割は「どの部品を・どのイベントで・どこにつなぐか」の結線。順序は次の通り。

1. ページ判定（`pathMatch`）で対象外なら早期 return
2. 要素取得（`service.getElement()` / ページ内 `getElements()`）
3. `component` を初期化（差し替えが要るものは instance を受け取る）
4. ページ固有の処理関数を定義（`initList` 等。下記の判断基準参照）
5. DOM イベントを service メソッド・処理関数に配線する
6. 全体を `try-catch` で囲む

**ロジックの置き場所の判断基準**：

- **service へ**：再利用する／複数ページで使う／重い（データ加工・複数 repository の組み合わせ）処理
- **ページ内に持ってよい**：そのページだけの一回限りの小さな処理（無理に service 化しない）。ページ内の「処理関数」として定義する
- どちらでも `repositories/` はページから直接呼ばない（「呼び出し関係」の節参照）

以下の型で書く（try-catch でラップ）。ページ固有処理の `initList` はページ内に置く例。

```js
import { isNotCurrentStaticPath } from "@js/utils/pathMatch";

const columnArchivePage = () => {
  // 1. 対象ページ判定（早期 return）
  if (isNotCurrentStaticPath("/column")) return;

  // 2. 要素取得関数の定義
  const getElements = () => ({
    list: document.querySelector('[data-element="column-archive-list"]'),
  });

  // 3. 処理関数の定義
  const initList = () => {
    /* ... */
  };

  try {
    // 4. 変数宣言（要素取得）
    const { list } = getElements();

    // 5. エラーハンドリング
    if (!list) {
      throw new Error("コラム一覧のリスト要素が見つかりません");
    }

    // 6. イベントリスナー設定
    window.addEventListener("load", initList);
  } catch (error) {
    console.error(error);
  }
};

export { columnArchivePage };
```

**実行時のデータの流れ**（page は結線するだけで、値は service／処理関数を通って流れる）:

```
ユーザー操作 (click / input / scroll)
  → page が張ったリスナー
    → service のメソッド（再利用・重い処理）／ページ内の処理関数（ページ固有）
        ├─ data-* / 属性を読む     （markup からの入力）
        ├─ repository で fetch      （サーバとの入出力。service 経由のみ）
        └─ element.js で DOM 更新
    → 状態を data-* に書き戻す
      → CSS が [data-*] で見た目に反映
```

**オーケストレーションの実例**（`topPage`）:

```js
const topPage = () => {
  if (isNotCurrentStaticPath("/")) return; // 1. ページ判定

  try {
    const { locationButton } = studioService.getElement(); // 2. 要素取得（service）
    const locationSplide = locationSlider(); // 3. component 初期化（instance 受け取り）
    mvSlider();
    storeModal();

    // 5. DOM イベントを service メソッドへ配線（重い処理は service 側）
    locationButton.addEventListener("click", () =>
      studioService.updateNearbySlider(locationSplide),
    );
    ctaService.hideBottomCtaOnScroll();
  } catch (error) {
    console.error("Error in topPage:", error); // 6. try-catch
  }
};
```

`updateNearbySlider` の中身（位置情報取得・fetch・スライド生成）は再利用・重い処理なので `studioService` の責務。一方、そのページだけの小さな処理はページ内の処理関数に置いてよい。**判断基準は「他ページでも使うか／重いか」——両方 No ならページ内、どちらか Yes なら service**。ページが太ってきて重い処理を抱え始めたら、service へ引き剥がすサイン。

## イベントリスナーをどこで張るか

「ページで張る」か「service / component 内で張る」かは、**イベントの所有者が誰か**で決める。

|              | ページで張る                                                         | service / component 内で張る                                                       |
| ------------ | -------------------------------------------------------------------- | ---------------------------------------------------------------------------------- |
| 対象         | 特定要素への**離散的なユーザー操作**（`click` / `input` / `submit`） | **自己完結する継続的な監視**（scroll・交差・グローバル keydown・動的要素への委譲） |
| コールバック | **薄い**（service メソッド or 処理関数を呼ぶだけ）                   | 監視・反応の**ロジック本体**を内包                                                 |
| 要素取得     | ページが `service.getElement()` で取得して自分で張る                 | service / component が自分で `getElement()` して張る                               |
| ページの関与 | 配線そのもの（結線マップがページに見える）                           | `setupXxx()` を**一度呼ぶだけ**（張り方を知らない）                                |

### ページで張る：離散イベント → service メソッドへ委譲

「このボタン → この操作」の**結線がページの責務**。service は操作メソッドを公開し、**リスナー登録はしない**（ページに委ねる）。ページは実行時インスタンス（Splide 等）をメソッドに渡す。

```js
// page: 要素を取得し、薄いコールバックで service メソッドを呼ぶ
const { locationButton } = studioService.getElement();
const locationSplide = locationSlider();
locationButton.addEventListener(
  "click",
  () => studioService.updateNearbySlider(locationSplide), // 中身は service。page は渡すだけ
);
```

```js
// service: 操作メソッドを公開するが、click リスナーは張らない
async updateNearbySlider(slider) {
  // ボタンのクリックリスナー登録は呼び出し側＝ページで行う
  const stores = await studioService.findNearby();
  /* … スライド差し替え … */
}
```

### service / component 内で張る：自己完結する監視は隠蔽

「監視して反応する」こと自体がその層の仕事なら、**リスナー / Observer を層の中に閉じ、ページは起動メソッドを一度呼ぶだけ**にする。ページは「どう監視するか」を知らない。

```js
// page: 起動するだけ
ctaService.hideBottomCtaOnScroll();
```

```js
// service: 監視の実装を内包（自分で getElement して Observer を張る）
hideBottomCtaOnScroll() {
  const { cta, guards } = ctaService.getElement();
  const observer = new IntersectionObserver((entries) => {
    /* … cta.dataset.show を切替 … */
  }, { threshold: 0 });
  guards.forEach((el) => observer.observe(el)); // service が監視を所有
}
```

- **動的に増減する要素**へのリスナーは、要素ごとに張らず **`document` へイベント委譲**して component / service 内に閉じる（`storeModal` の `data-modal-target`、`dataLayerInsight` のクリック計測）
- `<dialog>` の外側クリック・Escape のような**部品固有の挙動**は `createDialog`（util）内で張る。ページ・呼び出し側は関与しない

**迷ったら**：コールバックが1〜2行の委譲で済む離散操作はページ、複数要素の監視・ライフサイクル管理・動的委譲が要るものは service / component。

## DOM フックと `data-*` 契約

`data-*` 属性を、マークアップ・JS・CSS の3者をつなぐ**契約**として役割ごとに使い分ける。核は **「markup が意図を宣言 → JS が読む → 状態を書き戻す → CSS が拾う」**。クラス名と JS の責務を分離する（クラス = 見た目、`data-*` = フックと状態）。

| 属性                                    | 役割                                              | 書く / 読む                    |
| --------------------------------------- | ------------------------------------------------- | ------------------------------ |
| `data-element`                          | JS フック・要素識別（核）                         | JS が読む（`querySelector`）   |
| `data-show="true｜false"`               | 状態。CSS が見た目を分岐（核）                    | **JS が書く／CSS が読む**      |
| `data-window-scrolled`                  | `<html>` のスクロール状態（核）                   | JS(base) が書く／CSS が読む    |
| `data-hide-cta`                         | 宣言的マーカー（この要素が画面内なら CTA を隠す） | markup が宣言／JS が読む       |
| `data-modal-target` / `data-modal-id`   | トリガー ↔ dialog の対応付け                      | markup が宣言／JS が突き合わせ |
| `data-share-message` / `data-share-url` | サーバ値の受け渡し（Blade が出力）                | Blade が書く／JS が読む        |

### `data-element`（JS フック）

DOM 操作対象は **`data-element="<unique-name>"` 属性で識別する**。

- 値はページ名 + 要素名でユニークになるよう命名（ケバブケース）
- 一意性を保つことで `querySelector` で確実に取得できる

```html
<button data-element="header-hamburger-toggle"></button>
<ul data-element="column-archive-list"></ul>
<form data-element="search-form"></form>
```

```js
document.querySelector('[data-element="header-hamburger-toggle"]');
```

### 状態の書き戻し（`data-show` 等）

JS が状態を `data-*` に書き、CSS 側が属性セレクタで見た目を分岐する。

```js
cta.dataset.show = "false"; // → SCSS: .c-cta[data-show="false"] { … }
```

### サーバ → JS のデータ受け渡し

サーバの値は inline `<script>` を書かず **`data-*` 属性経由**で渡す（Blade がテンプレートで属性に出力 → JS が読む。[blade.md](blade.md) と連携）。

```js
const message = share.getAttribute("data-share-message") || "";
```

## ページ判定

`utils/pathMatch.js` の関数を使う：

| 関数                                      | 用途                 |
| ----------------------------------------- | -------------------- |
| `isCurrentStaticPath(path)`               | 完全一致             |
| `isNotCurrentStaticPath(path)`            | 完全一致しない       |
| `isCurrentDynamicPath(regex)`             | 正規表現マッチ       |
| `isNotCurrentDynamicPath(regex)`          | 正規表現マッチしない |
| `isCurrentUrlPattern({pathname, search})` | クエリパラメータ含む |

ページ関数の冒頭で `isNotCurrent*` 系を呼び、対象外なら早期 return する。

## エラーハンドリング

要素が見つからない場合は **`throw new Error('日本語のメッセージ')` で明示的に失敗させる**。規約違反やマークアップ漏れを早期発見するため。**メッセージには不足している `data-element` を明記する**。

ただし、本番でエラーが他ページの JS 実行を妨げないよう、**処理本体は `try` で囲み、`catch (error) { console.error(error); }` で受ける**。ページでは `'Error in topPage:'` のようにコンテキストを添える。

```js
try {
  const { list } = getElements();
  if (!list) {
    throw new Error(
      'コラム一覧のリスト要素（data-element="column-archive-list"）が見つかりません',
    );
  }
  // 処理
} catch (error) {
  console.error("Error in columnArchivePage:", error);
}
```

これにより、開発時はコンソールでエラーが目立ち、本番では他の JS が止まらないバランスを保つ。

## コーディングスタイル

| 項目         | ルール                                                  |
| ------------ | ------------------------------------------------------- |
| インデント   | 2 スペース                                              |
| 行の長さ     | 100 文字以内                                            |
| 変数         | キャメルケース                                          |
| 定数         | UPPER_SNAKE_CASE ＋ `Object.freeze()`（後述）           |
| 関数         | アロー関数・動詞始まり（後述）                          |
| ファイル名   | キャメルケース + ディレクトリ別サフィックス（下表参照） |
| エクスポート | 名前付きエクスポート（`export { pageFn }`）             |

**ファイル名サフィックス規則**

| ディレクトリ                       | サフィックス     | 例                                    |
| ---------------------------------- | ---------------- | ------------------------------------- |
| `constants/`                       | `Constant`       | `dataLayerConstant.js`                |
| `services/`                        | `Service`        | `cartService.js`                      |
| `repositories/`                    | `Repository`     | `productRepository.js`                |
| `pages/`                           | `Page`           | `columnArchivePage.js`                |
| `base/` / `utils/` / `components/` | サフィックスなし | `dataLayerInsight.js`, `pathMatch.js` |

### 定数（constants）

モジュールレベルの定数は **`Object.freeze()` で凍結**し、**UPPER_SNAKE_CASE** で命名する。共有 config はスプレッド（`{ ...SLIDER_BASE_CONFIG, arrows: false }`）で複製して使うため、元は不変であるべき。

```js
const SLIDER_BASE_CONFIG = Object.freeze({ type: "loop", autoplay: true });
const ALLOW_EVENT_PREFIXES = Object.freeze(["click_", "input_", "show_"]);
const STUDIOS_ENDPOINT = "/wp-json/wpblade/v1/studios"; // サーバ側ルートと一致させる
```

### 関数・メソッドの命名

動詞始まりのキャメルケース。動詞の使い分けに一貫性を持たせる。

| 動詞                        | 意味                     | 例                                |
| --------------------------- | ------------------------ | --------------------------------- |
| `get*`                      | 取得（DOM・データ）      | `getElement`, `getData`           |
| `create*`                   | 新規生成                 | `createDialog`, `createTimeline`  |
| `build*`                    | 文字列 / HTML の組み立て | `buildShareUrl`, `buildShareText` |
| `update*`                   | 既存を更新               | `updateUrlPreview`                |
| `find*` / `fetch*`          | 検索して返す / API 取得  | `findNearby`, `fetchNearby`       |
| `resolve*`                  | 加工して解決             | `resolveStoreOptionLabels`        |
| `sync*`                     | 状態を同期               | `syncAria`, `syncOpen`            |
| `toggle*`                   | 二値切替                 | `toggleScrollLock`                |
| `show/hide/enable/disable*` | 要素状態ヘルパ           | `showElement`, `disableElement`   |

真偽値を返す関数・保持する変数は **`is` / `has` / `should` / `can` 始まり**。否定は `isNot*` も使う。

```js
const isOpen = () => dialog.open;
const isNotCurrentStaticPath = (path) => /* … */;
if (shouldLock) { /* … */ }
```

### 変数の命名

- **DOM 要素を受ける変数は「役割＋要素種別」サフィックス**: `*Button` / `*Input` / `*List` / `*Text` / `*Template`（`locationButton`, `nameInput`, `urlText`）
- 同名の関数と衝突するときは要素側に **`El`** を足す（`locationSlider` 関数 ↔ `locationSliderEl` 要素）
- **コレクション（NodeList / 配列 / Map / Set）は複数形の名詞**（`stores`, `guards`, `modals`, `dialogs`）
- インスタンスは種別語で受ける（`splide` / `controller` / `observer`）。**後から代入する可変参照は `instance` オブジェクトに集約**（`const instance = { drawerModal: null }`）
- `getElement(s)` の戻りは**役割名キーのオブジェクト**（分割代入前提。コレクションキーは複数形）

```js
const { nameInput, lineButton, copyButton } = shareService.getElement();
const { cta, guards } = ctaService.getElement();
```

### 仮引数の短縮命名

**インラインの使い捨てコールバック引数は短縮名、意味を持つ引数はフル名**にする。短縮は「文脈から一意に決まる汎用の受け」だけに許す。

| 仮引数              | 意味                                     | 例                                             |
| ------------------- | ---------------------------------------- | ---------------------------------------------- |
| `el`                | DOM 要素（要素の反復）                   | `guards.forEach((el) => observer.observe(el))` |
| `e`                 | **イベント**オブジェクト                 | `dialog.addEventListener("click", (e) => …)`   |
| `error`             | 捕捉した例外（`e` にしない）             | `catch (error) { … }`                          |
| `v`                 | 配列要素の**値**（データの反復）         | `stores.map((v) => …)`                         |
| `i` / `j`           | インデックス（連番カウンタ。内側が `j`） | `arr.forEach((el, i) => …)`                    |
| `entry` / `entries` | IntersectionObserver のエントリ          | `new IntersectionObserver(([entry]) => …)`     |
| `[k, v]`            | キー・値（`Object.entries`）             | `Object.entries(o).every(([k, v]) => …)`       |

- **`e` はイベント専用、`catch` は `error`**（両方を `e` にしない）
- **`el` = 要素、`v` = データ値** で使い分ける
- 短縮は1〜数行で閉じる無名コールバックの中だけ。持ち回る変数・名前付き関数の引数は意味のある名前にする（`store`, `coords`, `slider`）
- `i` / `j` は純粋な連番カウンタ限定。意味のあるインデックスは `index` とフルで書く

## script.js での統合

各ページ関数を `script.js` で import + 実行する。各関数は冒頭で対象ページか判定して早期 return するため、全ページで呼んでよい。**配列でループせず、1 行ずつ明示的に呼ぶ**（grep しやすさと追加時の差分を最小にするため）。

```js
// script.js
import { columnArchivePage } from "@js/pages/columnArchivePage";
import { columnDetailPage } from "@js/pages/columnDetailPage";
import { storePage } from "@js/pages/storePage";
import { topPage } from "@js/pages/topPage";

columnArchivePage();
columnDetailPage();
storePage();
topPage();
```

## JSDoc

`utils/` `components/` `services/` `repositories/` に置く共通モジュールは **JSDoc を必須**。ページ別 JS は不要（ファイル名と関数名で内容がわかる）。

```js
/**
 * 指定されたパスと現在の location が一致するかを判定
 * @param {string} path - 比較するパス
 * @returns {boolean}
 */
const isCurrentStaticPath = (path) => ...;
```
