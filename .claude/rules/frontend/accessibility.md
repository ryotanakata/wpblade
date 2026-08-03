---
paths:
  - "**/*.blade.php"
  - "**/resources/js/**"
  - "**/resources/ts/**"
  - "**/*.scss"
---

# フロントエンド規約：アクセシビリティ

WCAG 2.1 の **レベル AA を目標**、AAA は努力目標とする。マークアップ・スタイル・スクリプトに横断する規約のため、blade / tsx / scss いずれの編集時にも読み込まれる。画像の代替テキスト詳細は [image.md](image.md)、`hover` mixin など SCSS ヘルパは [scss.md](scss.md)、マークアップ規約は [blade.md](blade.md) を参照。

## 画像

- **意味のある画像**: `alt` に内容を記述
- **装飾目的の画像**: `alt=""` + `aria-hidden="true"`
- **CSS 背景画像**: alt 不要（装飾扱い）

詳細は [image.md](image.md) 参照。WCAG 1.1.1（レベル A）。

## リンクの目的（リンクテキスト）

リンクテキストだけで行き先がわかるようにする。「詳しくはこちら」だけは NG。文脈で明確化できない場合は **`aria-label` で補足**する。

```html
<!-- 良い例 -->
<a href="{{ home_url('/company') }}" aria-label="会社概要ページはこちら"
  >詳しくはこちら</a
>

<!-- 悪い例 -->
<a href="{{ home_url('/company') }}">詳しくはこちら</a>
```

WCAG 2.4.4（レベル A）。

## セマンティックマークアップ

- 表は `<table>` を使う（CSS でレイアウト目的の表にしない）
- 見出しは `<h1>`〜`<h6>` を順番に使う（レベルを飛ばさない）
- リストは `<ul>` / `<ol>` / `<dl>` を使う
- `<button>` と `<a>` を用途で使い分ける（**行き先 = `<a>`、操作 = `<button>`**）
- `<div>` / `<span>` を避け、意味のあるタグを使う（[blade.md](blade.md) の Blade / HTML 記述規約と整合）

WCAG H42 / H48 / H51。

## キーボード操作

すべてのクリッカブル要素は **キーボード操作可能** であること。

- Tab でフォーカス移動できる
- Enter / Space で実行できる
- フォーカス状態が見える（`:focus-visible`）
- フォーカス順序が視覚順序と一致する

`hover` mixin が `:focus-visible` も適用するため、これを使えば自動で対応される。

```scss
.c-button {
  color: $_color01;
  @include hover {
    color: $_color02;
    outline: 2px solid currentColor;
  }
}
```

WCAG 2.1.1 / 2.4.3 / 2.4.7（レベル A〜AA）。

## ページ言語

`<html>` タグに `lang` 属性を必ず付ける。[layout.blade.php](../../../wp-content/themes/wpblade/resources/views/layout.blade.php) は `language_attributes()` 経由で自動出力済み。

```html
<html lang="ja"></html>
```

WCAG 3.1.1（レベル A）。

## ARIA 属性の使い分け

| 属性                 | 用途                                      |
| -------------------- | ----------------------------------------- |
| `aria-label`         | 視覚情報を補う代替テキスト                |
| `aria-hidden="true"` | 支援技術から要素を隠す                    |
| `aria-disabled`      | 操作不可状態（HTML の `disabled` と併用） |
| `aria-selected`      | タブ・選択肢の選択状態                    |
| `aria-expanded`      | アコーディオン・メニューの開閉状態        |
| `aria-invalid`       | フォーム入力エラー状態                    |

`utils/element.js` に `ariaDisableElement` / `ariaEnableElement` / `ariaSelectElement` / `setTabindexFocusable` 等のヘルパが定義されている。JS からはこれらを使う。

## フォーム

- `<input>` には対応する `<label>` を必ず付ける
- **可能な限り `<label>` で `<input>` をネストして関連付け、`for` / `id` を省略する**。ネストできないレイアウトのときだけ `for` / `id` で紐付ける
- 必須は `required` 属性で明示
- エラー時は `aria-invalid="true"` + 視覚的なエラーメッセージを表示

```blade
{{-- 推奨: ネストで関連付け（for / id 不要） --}}
<label>
  お名前
  <input type="text" name="name" required>
</label>

{{-- ネストできないレイアウトのときだけ for / id --}}
<label for="tel">電話番号</label>
<input id="tel" type="tel" name="tel">
```

WCAG 3.3.1 / 3.3.2（レベル A）。

## 動きの制御

自動再生やカルーセル等の **勝手に動くコンテンツは停止可能** にする。

WCAG 2.2.2（レベル A）。

## 努力目標（WCAG AAA）

強制ルールではないが、可能なら対応する：

- フォントサイズ 200% 拡大でも崩れない
- 略語に `<abbr title="...">` で説明
- 現在位置がわかるパンくずリスト

## 参考リンク

- [WCAG 2.1 解説書（日本語）](https://waic.jp/translations/WCAG21/Understanding/)
- [WCAG 2.0 達成方法集 - HTML](https://waic.jp/translations/WCAG-TECHS/html.html)
- [Ameba Accessibility Guidelines](https://a11y-guidelines.ameba.design/)
