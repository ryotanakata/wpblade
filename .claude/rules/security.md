# セキュリティ規約（wpblade）

## 出力エスケープ（必須）

| 出力先        | 使う関数                                                    |
| ------------- | ----------------------------------------------------------- |
| HTML テキスト | `esc_html()`                                                |
| HTML 属性値   | `esc_attr()`                                                |
| URL           | `esc_url()`                                                 |
| JS インライン（文字列リテラル） | `esc_js()`                                    |
| JS インライン（構造データ） | `wp_json_encode($data, JSON_HEX_TAG \| JSON_HEX_AMP \| JSON_HEX_APOS \| JSON_HEX_QUOT)`。`esc_js()` は単一引用符リテラル向けで `</script>` を塞げない |
| Blade `{{ }}` | 自動エスケープ（生 HTML は `{!! !!}` を必要な場合のみ使う） |
| メール本文（text/plain） | `{!! !!}`（`resources/views/mail/` のみ。HTML として解釈されない出力先のため、`{{ }}` だと `&amp;` 等の実体参照が文面にそのまま残る） |

## 入力値の検証

- REST API のリクエストパラメータは型・範囲・形式を必ず検証する
- `$_GET` / `$_POST` を直接使わず、`sanitize_text_field()` / `absint()` 等でサニタイズする

## 権限チェック

- 管理画面向けの処理は `current_user_can()` で権限を確認する
- REST API の `permission_callback` は適切な権限チェックを行う（公開エンドポイントだけ `'__return_true'`）

## nonce

| ケース                           | 方法                                                                              |
| -------------------------------- | --------------------------------------------------------------------------------- |
| 通常フォーム・AJAX               | `wp_nonce_field()` で hidden フィールドを出力し、`check_admin_referer()` で検証   |
| REST API（フロントエンド Fetch） | `X-WP-Nonce` ヘッダーで nonce を送り、`wp_verify_nonce($nonce, 'wp_rest')` で検証 |

REST API の nonce は `ViteAssets::enqueueReact()` が `window.wpblade.nonce` として自動出力する。クライアント側は `fetch()` の `headers` に `'X-WP-Nonce': window.wpblade.nonce` を付けること。

## SQL

- 直接クエリが必要な場合は `$wpdb->prepare()` を使い SQL インジェクションを防ぐ
- 可能な限り WP の API（`WP_Query`, `get_posts` 等）を使う

## ファイル操作

- ユーザー入力をファイルパスに含めない
- `WP_CONTENT_DIR` 等の定数を使い、パストラバーサルを防ぐ
- **コンパイル・キャッシュ生成物を web 公開下に置く場合は直リードを塞ぐ**。Blade のコンパイル結果（`cache/*.bladec`）はテーマ配下＝公開ディレクトリにあり、未知拡張子として平文配信され得る。`Blade::instance()` が `cache/` 作成時に `.htaccess`（`Require all denied`）を書き出す。**nginx では `.htaccess` が効かない**ので、サーバ側で `location ~ \.bladec$ { deny all; }` を設定する
