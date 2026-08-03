---
paths:
  - "**/*.blade.php"
---

# フロントエンド規約：Blade / HTML

`resources/views/` の Blade テンプレートと HTML 記述規約をまとめる。標準テンプレート（薄い Controller）からのデータの渡し方は [backend/php.md](../backend/php.md)、出力エスケープの詳細は [security.md](../security.md)、スタイルは [scss.md](scss.md)、画像は [image.md](image.md)、アクセシビリティは [accessibility.md](accessibility.md) を参照。

## Blade テンプレート

### ディレクトリ構成

```
resources/views/
├── layout.blade.php        # マスターレイアウト（@yield('content') を持つ）
├── base/                   # <head>/<body> 内のパーシャル（scss/base・images/base と同じ層）
│   ├── meta.blade.php
│   ├── link.blade.php
│   ├── script.blade.php
│   ├── jsonld.blade.php
│   └── noscript.blade.php
├── components/             # header / footer など全ページ共通パーツ
│   ├── header.blade.php
│   └── footer.blade.php
├── mail/                   # メール本文テンプレート（layout は継承しない）
│   ├── contact.blade.php        # 管理者通知メール本文
│   └── reply.blade.php          # 自動返信メール本文
└── pages/                  # ページ別テンプレート（ページ = ディレクトリ + index.blade.php）
    ├── 404.blade.php            # フレームワーク規定の単一ファイルは例外
    ├── top/
    │   ├── index.blade.php      # @extends + 各セクションを @include するだけ
    │   ├── mv.blade.php         # セクション（肥大時に分割）
    │   ├── price.blade.php
    │   └── faq.blade.php
    └── column/
        ├── index.blade.php      # 一覧（archive）
        └── detail/              # 詳細（single）も「ディレクトリ + index.blade.php」
            └── index.blade.php
```

- マスターレイアウト: `resources/views/layout.blade.php`（`@yield('content')` を持つ）
- **ページは必ず「ディレクトリ + `index.blade.php`」で作る**。`column/detail.blade.php` のような単一ファイルのページ名は作らず、`column/detail/index.blade.php` にする（例外は `404.blade.php` などフレームワーク規定のもの）
- ページテンプレート: `resources/views/pages/<name>/index.blade.php`
- **ページテンプレート（`pages/` 配下の `index.blade.php` と `404.blade.php` 等の例外ファイル）は必ず `@extends('layout')` + `@section('content')` で構成する**。パーシャル（`base/` / `components/` / ページ内セクション）と `mail/`（メール本文）は layout を継承しない（このルールの対象外）

### ページが肥大したらセクション分割

- **1つの `.blade.php` は目安 150 行以内**に収める。超えそうなら（＝意味的に独立したセクションが複数ある証拠）、**セクション単位のパーシャルに分割**し、`index.blade.php` は `@extends('layout')` + 各セクションの `@include` だけにする（実測でも index は 17〜51 行、セクション単体でも最大 150 行程度に収まっている）
- セクションパーシャルは **同じページディレクトリ内** に置く（`pages/top/mv.blade.php`, `pages/top/price.blade.php` …）。`index.blade.php` が親として `@include` し、存在チェックもここで行う（後述「条件分岐と存在チェック」）
- **SCSS も同じ粒度・同じ名前で分割する**（[scss.md](scss.md) の「Blade 分割と SCSS 分割を一致させる」）
- 150 行はあくまで「ページをセクションに割るか」の目安。分割後の1セクションが1つの意味単位として 150 行を超えるのは可（無理に細切れにしない）

### 共有変数（`shared_` プレフィックス）

`Blade::instance()->share()` で全ビューに注入する変数は `shared_` プレフィックスで統一する。登録は `app/View/Blade.php` の `instance()` に集約する。

| 変数                 | 内容                                                                 |
| -------------------- | -------------------------------------------------------------------- |
| `$shared_image_url`  | 画像ディレクトリ URI（`resources/images/` まで。末尾スラッシュ付き） |
| `$shared_header_nav` | グローバルナビ項目（`NavConstant::PRIMARY`）                         |

**ナビゲーションのコード管理**: WordPress 管理画面の「外観 → メニュー」は使わず、`app/Constants/NavConstant.php` にコードで定義する。ナビ項目の追加・変更は `NavConstant` を編集し、`Blade.php` の `share()` は変えない。

## Blade: `{{ }}` を優先する（生 HTML を渡さない）

Blade の自動エスケープを最大限活用するため、**Blade に渡すデータは原則「スカラ・配列・オブジェクト」で渡す**。HTML 文字列を返す WordPress 関数は標準テンプレ（薄い Controller）で直接使わず、配列を返す関数に置き換えて Blade 側で `@foreach` で組み立てる。

### 避ける関数 → 代替

| 避ける（HTML を返す）                               | 使う（配列・スカラを返す）                        |
| --------------------------------------------------- | ------------------------------------------------- |
| `get_the_category_list()`                           | `get_the_category()`                              |
| `get_the_tag_list()`                                | `get_the_tags()`                                  |
| `wp_nav_menu()`                                     | `wp_get_nav_menu_items()`                         |
| `get_the_post_thumbnail()`                          | `get_the_post_thumbnail_url()` + alt メタ         |
| `get_previous_post_link()` / `get_next_post_link()` | `get_previous_post()` / `get_next_post()`         |
| `paginate_links()`                                  | 自前で `current` / `total` を組み、Blade でループ |

### 例

```php
// Controller（標準テンプレ）
$categories = get_the_category();
$prev       = get_previous_post();
```

```blade
{{-- Blade --}}
@foreach ($categories as $cat)
  <a href="{{ get_category_link($cat->term_id) }}">{{ $cat->name }}</a>
@endforeach

@if ($prev)
  <a href="{{ get_permalink($prev) }}">← {{ get_the_title($prev) }}</a>
@endif
```

### `{!! !!}` の許可ライン

`{!! !!}` を使ってよいのは次の2ケースのみ。

- **`apply_filters('the_content', ...)` の戻り値**（投稿本文）
- **`mail/*.blade.php` のメール本文変数**（`Content-Type: text/plain` で送るため HTML エスケープ不要。`{{ }}` を使うと `&amp;` 等が文字化けして届く）

それ以外で HTML を出したくなったら、Controller で配列に分解して Blade で組み立て直す。詳細は [.claude/rules/security.md](../security.md) を参照。

## Blade ディレクティブ早見表（BladeOne 4.x）

BladeOne で使える主要ディレクティブを WP テーマ用途で整理。

### 出力

| ディレクティブ | 用途                                                                |
| -------------- | ------------------------------------------------------------------- |
| `{{ $var }}`   | 自動エスケープ付き出力（**基本これだけ使う**）                      |
| `{!! $var !!}` | 生 HTML 出力（投稿本文のみ許可）                                    |
| `@json($data)` | 配列・オブジェクトを JSON 文字列として出力（JS への受け渡しに便利） |

### 制御構文

| ディレクティブ                                             | 用途               |
| ---------------------------------------------------------- | ------------------ |
| `@if` / `@elseif` / `@else` / `@endif`                     | 条件分岐           |
| `@unless` / `@endunless`                                   | `if (!...)` の糖衣 |
| `@isset` / `@endisset`                                     | `isset()` の糖衣   |
| `@empty` / `@endempty`                                     | `empty()` の糖衣   |
| `@switch` / `@case` / `@break` / `@default` / `@endswitch` | switch 文          |

### ループ

| ディレクティブ                        | 用途                                                                   |
| ------------------------------------- | ---------------------------------------------------------------------- |
| `@foreach` / `@endforeach`            | 配列ループ。`$loop->index` / `$loop->first` / `$loop->last` 等が使える |
| `@forelse` / `@empty` / `@endforelse` | ループ + 空時のフォールバック                                          |
| `@for` / `@endfor`                    | for 文                                                                 |
| `@while` / `@endwhile`                | while 文                                                               |
| `@continue` / `@break`                | ループ制御（`@continue($condition)` で条件付きも可）                   |
| `@each('view', $items, 'item')`       | 配列の各要素にビューを適用                                             |

### レイアウト継承

| ディレクティブ                     | 用途                           |
| ---------------------------------- | ------------------------------ |
| `@extends('layout')`               | マスターレイアウトを継承       |
| `@section('name')` / `@endsection` | セクション定義                 |
| `@yield('name')`                   | セクションを呼び出し           |
| `@show`                            | セクション定義してすぐ表示     |
| `@stop`                            | `@endsection` の別名           |
| `@parent`                          | 親セクションの内容を埋め込む   |
| `@hasSection('name')`              | セクション定義があるかチェック |
| `@overwrite`                       | セクションを強制上書き         |

### インクルード

| ディレクティブ                         | 用途                               |
| -------------------------------------- | ---------------------------------- |
| `@include('partial', ['key' => $val])` | 別ファイルを取り込み               |
| `@includeIf('partial')`                | ファイルが存在する場合のみ取り込み |
| `@includeWhen($condition, 'partial')`  | 条件付きインクルード               |
| `@includeFirst(['a', 'b'])`            | 最初に見つかったものを取り込み     |

### コンポーネント

| ディレクティブ                          | 用途                   |
| --------------------------------------- | ---------------------- |
| `@component('alert')` / `@endComponent` | コンポーネント呼び出し |
| `@slot('title')` / `@endSlot`           | 名前付きスロット       |

### スタック（`<head>` 系の積み上げに便利）

| ディレクティブ                          | 用途               |
| --------------------------------------- | ------------------ |
| `@push('scripts')` / `@endpush`         | スタックに追加     |
| `@prepend('scripts')` / `@endprepend`   | スタック先頭に追加 |
| `@pushOnce('scripts')` / `@endpushOnce` | 1 回だけプッシュ   |
| `@stack('scripts')`                     | スタックを出力     |

### PHP 埋め込み

| ディレクティブ            | 用途                                            |
| ------------------------- | ----------------------------------------------- |
| `@php` / `@endphp`        | 生 PHP コード（`wp_head()` 等の呼び出しに使用） |
| `@use('Namespace\Class')` | use 文                                          |
| `@set('name', $value)`    | 変数代入                                        |
| `@unset($var)`            | 変数破棄                                        |

> これらは BladeOne で使えるが、**本テーマでは原則使わない**。ロジック・データ加工は Controller 側で行い、Blade には完成データを渡す（後述「PHP・ロジックを Blade に書かない」）。`@php` の例外は `wp_head()` など Blade でしか呼べない WP 関数の呼び出しのみ。

### 属性ヘルパ（Laravel 8+ 互換）

| ディレクティブ                           | 用途                             |
| ---------------------------------------- | -------------------------------- |
| `@class(['active' => $isActive, 'btn'])` | 条件付き class 属性              |
| `@style(['color: red' => $isError])`     | 条件付き style 属性              |
| `@checked($condition)`                   | `checked="checked"` を条件出力   |
| `@selected($condition)`                  | `selected="selected"` を条件出力 |
| `@disabled($condition)`                  | `disabled` を条件出力            |
| `@required($condition)`                  | `required` を条件出力            |
| `@readonly($condition)`                  | `readonly` を条件出力            |

### デバッグ

| ディレクティブ | 用途                 |
| -------------- | -------------------- |
| `@dump($var)`  | `var_dump`           |
| `@dd($var)`    | dump して停止        |
| `@viewName`    | 現在のビュー名を出力 |

### WP テーマでは使わない / 効かない

Laravel のサービスコンテナや認証システム前提のディレクティブは WP では別の仕組みで代替する。

| Blade ディレクティブ                  | WP での代替                                                           |
| ------------------------------------- | --------------------------------------------------------------------- |
| `@auth` / `@guest`                    | `is_user_logged_in()` を `@if` で使う                                 |
| `@can` / `@cannot` / `@canAny`        | `current_user_can('cap')` を `@if` で使う                             |
| `@inject`                             | DI コンテナ前提。WP では PSR-4 + `use` で十分                         |
| `@user`                               | `wp_get_current_user()`                                               |
| `@asset` / `@assetCDN` / `@canonical` | `get_template_directory_uri()` 等、画像は共有変数 `$shared_image_url` |
| `@method`                             | フォームの method override。WP では不要                               |
| `@csrf`                               | `wp_nonce_field()` を使う                                             |

### 条件分岐と存在チェック

- **条件分岐は `@if` を基本にする**（コードベースの基調。`@if` が多数を占め、`@class` はごく少数）。`@class([...])` / 三項演算子は、真偽値が確定した **単一の状態・ラベルクラス（`--xxx`）の付け外し** に限って使う。複数条件やロジックが絡むものは `@if` で書く
- **存在チェックは親（呼び出し側）で行う**。親が `@if ($data)` でガードしてから `@include` し、**include されるコンポーネントは「データがある前提」で書く**（コンポーネント内で存在チェックを重複させない）

```blade
{{-- 親: 存在すれば include。banner / price コンポーネントは「データがある前提」で書く --}}
@if ($campaign_banner)
  @include('pages.top.banner')
@endif
@if ($price_view['count'])
  @include('pages.top.price')
@endif

{{-- @class は単一の状態クラスに限定（複数条件・ロジックは @if へ） --}}
<li @class(['--current' => $current_step === 1])>STEP 1</li>
```

### 実例

```blade
@extends('layout')

@section('content')
  @include('components.header')

  <main class="pg-column" data-element="column-archive-main">
    <div>
      @foreach ($posts as $post)
        <article>
          <h2>{{ get_the_title($post) }}</h2>
          @if ($post->is_featured)
            <span class="--featured">注目</span>
          @endif
          <a href="{{ get_permalink($post) }}">続きを読む</a>
        </article>
      @endforeach

      {{-- 存在チェックは親で。pager コンポーネントは「ページャがある前提」で書く --}}
      @if ($pager)
        @include('pages.column.pager')
      @endif
    </div>
  </main>

  @include('components.footer')
@endsection
```

## Blade / HTML 記述規約

### インデント幅

| ファイル                                        | インデント幅 | 補足                                |
| ----------------------------------------------- | ------------ | ----------------------------------- |
| `*.php`（標準テンプレ・Controller・Service 等） | 4 スペース   | PSR-2 準拠                          |
| `*.blade.php`                                   | 2 スペース   | HTML ネストが深くなりがちなので狭く |

Prettier が自動整形するため手書きでズレても気にしなくてよい（`npm run format` で揃う）。タブは使わない。

### 空白行

タグ種別での空白行ルールは設けず、**Prettier の整形結果に任せる**。読みやすさのために適宜空白行を入れるのは構わないが、人によるルールは付けない。

### PHP・ロジックを Blade に書かない

Blade はプレゼンテーション専用。**データ加工・分岐ロジックは標準テンプレート（薄い Controller）側で済ませ、Blade には完成したデータだけを渡す**（[backend/php.md](../backend/php.md) の「標準テンプレート = 薄い Controller」参照）。

- **`@php` / `@set` / `@unset` / `@use` は原則使わない**。唯一の例外は `wp_head()` / `wp_footer()` など Blade でしか呼べない WordPress 関数の呼び出し
- `@if` / `@foreach` は **表示の出し分け・繰り返しに限る**。条件やループの中で計算・整形（`number_format`・日付整形・文字列連結など）をしない。計算は Controller で済ませ、結果（真偽値・整形済み文字列・配列）を渡す
- Blade に書いてよいのは `{{ }}` 出力・`@include`・`@extends` / `@section`・単純な `@if` / `@foreach` まで

```blade
{{-- ✕ Blade で加工している --}}
@php $price = number_format($product->price); @endphp
<p>{{ $price }}円</p>

{{-- ○ Controller で整形済みの値を渡し、Blade は出力だけ --}}
<p>{{ $price }}円</p>
```

### インライン CSS / JS は禁止

- `style="..."` 属性を使わず、SCSS に記述する
- `<script>` でのインライン JS は使わず、`resources/js/` 配下に記述する
- 例外: `@push('head')` での JSON-LD やページ別 meta タグなど、**HTML に直接書く意味があるもの** は許可
- 例外: **計測タグの提供元スニペットは原文のまま置く**（GTM の `<script>` ローダ = `base/script.blade.php`、`<noscript>` iframe = `base/noscript.blade.php`）。`style` 属性・インライン `<script>` を含んだままでよい。CSS / JS の読み込みに依存せず動く必要があり、提供元のコードと差分を作らないため。**装飾目的で属性を足すこともしない**（`display: none` の要素に `title` を付けても支援技術には読まれない）

### セマンティックマークアップ

- **コンテンツには意味のあるタグを使う**：表は `<table>`、見出しは `<h1>`〜`<h6>`、リストは `<ul>` / `<ol>` / `<dl>`、本文は `<p>`。見出しやリストの代わりに `<div>` を使わない
- これは**コンテンツの意味付け**の話であって「div を減らす」話ではない。レイアウトのための `<div>` ネストは積極的に使ってよい（下記）

### レイアウトのための div ネストは積極的に使う

1要素に複数のレイアウト責務（全幅・中央寄せ・領域分割・内側 padding・整列）を負わせると、後から1軸だけ変えたいときに破綻する（＝無理なレイアウト）。**役割ごとに `<div>` を分けてネストし、各階層は1つのレイアウト責務だけ**を持たせる。

```blade
<section class="pg-top-mv">   {{-- ブロック識別・背景・全幅 --}}
  <div>                       {{-- 中央寄せコンテナ（SCSS: width: min(); margin-inline: auto） --}}
    <div class="--background"> … </div>  {{-- 名前付き領域（縦方向の分割・領域ごとの余白） --}}
    <div class="--header">
      <div> … </div>          {{-- 領域内インナー（padding / flex / grid / 整列） --}}
    </div>
    <div class="--main"><div> … </div></div>
    <div class="--footer"> … </div>
  </div>
</section>
```

- **レイアウト目的の wrapper / inner div にはクラスを付けない**。SCSS 側は `& > div` / `:nth-child(n)` で当てる（[scss.md](scss.md) の「wrapper / inner にクラスを付けない」と整合）
- **`--` クラスはブロック直下の主要な領域だけに付ける**（`--background` / `--header` / `--main` / `--footer`、目安3〜4個）。wrapper / 見出し・本文・画像などの中身要素・状態には付けない（乱発・ネスト禁止）。詳細は [scss.md](scss.md) の「名前付き `--` 要素コンテナ（乱発しない）」参照

### フォーム（form / fieldset / legend）

マークアップを制御できるフォーム（React・自前テンプレート）では次の構造にする。プラグイン（MW WP Form 等）出力でマークアップを変えられない場合はこの限りでない。

- フォーム全体を `<form>` で囲む
- **関連する入力のまとまりは `<fieldset>` でグループ化し、そのグループの見出しを `<legend>` で付ける**（ラジオ / チェックボックスの選択肢群、姓名・住所など複数フィールドで1つの意味を成すもの）
- 各コントロールのラベルは `<label>`（原則 `<input>` をネスト。[accessibility.md](accessibility.md) の「フォーム」参照）
- 必須は `required`、エラーは `aria-invalid`（同上）

```blade
<form action="..." method="post">
  <fieldset>
    <legend>ご希望の連絡方法</legend>
    <label><input type="radio" name="contact" value="tel"> 電話</label>
    <label><input type="radio" name="contact" value="mail"> メール</label>
  </fieldset>
</form>
```

### 画像（picture / img / figure / source）

- **SP と PC で画像を出し分ける（アートディレクション／レスポンシブ）ときは `<picture>` + `<source media="...">`** を使う。`<source>` に `type="image/webp"` と `srcset`（PC は `-l` サイズ）、フォールバックの `<img>` を**最後**に置く
- `<img>` は `alt` / `width` / `height` を必ず指定（内容の書き方・装飾画像の `alt=""` は [image.md](image.md) / [accessibility.md](accessibility.md)）。`width` / `height` は実寸で CLS を防ぐ
- **読み込み戦略**: ファーストビュー外は `loading="lazy"`、ファーストビュー（MV 等）は `loading="eager"` + `fetchpriority="high"`。Splide 管理のスライダー画像は native `loading` でなく `data-splide-lazy`
- スライダー等でドラッグを抑止したい `<img>` は `draggable="false"`
- **キャプション付き画像は `<figure>` + `<figcaption>`**（キャプション不要でも、画像＋説明の意味的まとまりとして `<figure>` を使ってよい）

```blade
{{-- SP / PC 出し分け（picture） --}}
<picture>
  <source media="(min-width: 768px)" srcset="{{ $shared_image_url }}pages/top/mv_01-l--20260101.webp" type="image/webp" width="1920" height="1024">
  <img src="{{ $shared_image_url }}pages/top/mv_01--20260101.webp" alt="" width="390" height="850" loading="eager" fetchpriority="high" draggable="false">
</picture>

{{-- キャプション付き（figure） --}}
<figure>
  <img src="{{ $shared_image_url }}pages/top/proof_01--20260101.webp" alt="導入実績のグラフ" width="140" height="100" loading="lazy">
  <figcaption>※画像はイメージです</figcaption>
</figure>
```

### id 属性の付け方

`id` は JS フックには使わない（フックは `data-element`。[javascript.md](javascript.md)）。`id` を振る用途は次の2つに限られ、いずれもページ内で一意にする。

- **ページ内アンカーリンクの飛び先**: `scroll-` プレフィックスを付ける（`id="scroll-benefit"` ⇔ `href="#scroll-benefit"`）
- **ARIA 紐付けの参照先**: `aria-labelledby` などが指す要素（例: モーダルのタイトルは `modal-...-title`）

```blade
<a href="#scroll-benefit">特典を見る</a>
{{-- … --}}
<section id="scroll-benefit"> … </section>
```

`<label>` と `<input>` の関連付けは原則ネストで行い `id` を省略する（ネストできないときだけ `for` / `id`。[accessibility.md](accessibility.md) の「フォーム」参照）。

### Blade コメント

Blade のコメントは `{{-- --}}` を使う（HTML 出力に残らない）。`<!-- -->` は HTML として出力されてしまうので、**内部メモには使わない**。

```blade
{{-- これは HTML に出ない（推奨）--}}
<!-- これは HTML に出力される（公開コメントなら OK）-->
```
