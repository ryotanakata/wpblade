---
paths:
  - "**/app/**"
  - "**/includes/**"
  - "**/themes/*/*.php"
---

# バックエンド規約（wpblade）

## PHP / 名前空間

- PHP 8.2+ 構文を使う（ターゲット 8.3）。`composer.json` の `require.php` と一致させる（standalone `true` 型を使っているため 8.0 / 8.1 では動かない）
- PSR-4 名前空間 `WpBlade\` を守る（`app/` ディレクトリがルート）
- クラスファイルは 1 ファイル 1 クラス

**app/ ファイル名・クラス名のサフィックス規則**

| ディレクトリ            | サフィックス | 例                                                |
| ----------------------- | ------------ | ------------------------------------------------- |
| `app/Constants/`        | `Constant`   | `PostTypeConstant.php` → `class PostTypeConstant` |
| `app/Helpers/`          | `Helper`     | `DateHelper.php` → `class DateHelper`             |
| `app/Services/`         | `Service`    | `ColumnService.php` → `class ColumnService`       |
| `app/Http/Controllers/` | `Controller` | `ColumnController.php` → `class ColumnController` |

ファイル名・クラス名ともにアッパーキャメルケース。`app/View/` はフレームワーク的な固有名（`Blade`, `ViteAssets` など）のためサフィックス規則の対象外。

## app/ 層の責務分離

| レイヤー         | 場所                                                                             | 責務                                                                                                |
| ---------------- | -------------------------------------------------------------------------------- | --------------------------------------------------------------------------------------------------- |
| 標準テンプレート | テーマ直下（`front-page.php`, `single-*.php`, `archive-*.php`, `page-*.php` 等） | **薄い Controller として扱う**。データ取得 + `Blade::render()` 呼び出しのみ                         |
| Controller       | `app/Http/Controllers/`                                                          | REST API・WP-CLI 等、テンプレ階層に乗らないエントリポイントのみ。フック登録・ルーティングだけを書く |
| Service          | `app/Services/`                                                                  | ドメインロジック本体。WordPress に依存してよいが Controller には依存しない                          |
| Constants        | `app/Constants/`                                                                 | ハードコード値。`final class` で定義                                                                |
| Helpers          | `app/Helpers/`                                                                   | グローバルヘルパ関数                                                                                |
| View             | `app/View/`                                                                      | Blade ラッパ・ViteAssets                                                                            |

## 標準テンプレート = 薄い Controller

WordPress のテンプレート階層がそのままルーティングとして機能するため、テーマ表示用に別途 Controller クラスを切らない。標準テンプレートファイル（`front-page.php`, `single-*.php`, `archive-*.php`, `page-*.php` 等）を **薄い Controller として扱う**。

### 汎用テンプレートを使わず、専用テンプレートで拡張する

`single.php` / `archive.php` / `page.php` のような汎用テンプレートは **使わない**。代わりに WordPress のテンプレート階層を活用し、対象を絞った専用テンプレートを Controller として作る。

| 使わない（汎用） | 使う（専用 = Controller）                                         | 対象                               | URL 例            |
| ---------------- | ----------------------------------------------------------------- | ---------------------------------- | ----------------- |
| `home.php`       | `front-page.php`                                                  | サイトトップ（固定フロントページ） | `/`               |
| `single.php`     | `single-{post_type}.php`（例: `single-column.php`）               | 投稿タイプごとの個別ページ         | `/column/{slug}/` |
| `archive.php`    | `archive-{post_type}.php`（例: `archive-column.php`）             | 投稿タイプごとの一覧               | `/column/`        |
| `page.php`       | `page-{slug}.php`（例: `page-store.php`）                         | スラッグごとの固定ページ           | `/store/`         |
| `category.php`   | `category-{slug}.php`（例: `category-news.php`）                  | 特定カテゴリのアーカイブ           | `/category/news/` |
| `tag.php`        | `tag-{slug}.php`（例: `tag-event.php`）                           | 特定タグのアーカイブ               | `/tag/event/`     |
| `taxonomy.php`   | `taxonomy-{taxonomy}-{term}.php`（例: `taxonomy-genre-rock.php`） | カスタムタクソノミーの特定ターム   | `/genre/rock/`    |
| `author.php`     | `author-{nicename}.php`（例: `author-john.php`）                  | 特定著者のアーカイブ               | `/author/john/`   |
| `date.php`       | （原則不要、必要時のみ作る）                                      | 日付アーカイブ                     | `/2026/05/`       |

**理由**: 汎用テンプレートを使うと「複数の投稿タイプ／ページが同じファイルを経由する」ため、テンプレ内で `get_post_type()` / `is_page('xxx')` 等の分岐が必要になり、Controller としての責務がぼやける。専用テンプレートに分けることで **「1 ファイル = 1 ルート = 1 Controller」** の対応関係を保つ。

**例外（フォールバック専用、引き続き使う）**:

| ファイル     | 対象                                                   | URL                              |
| ------------ | ------------------------------------------------------ | -------------------------------- |
| `index.php`  | 最終フォールバック（どのテンプレも当てはまらない場合） | 上記すべてが存在しない場合に発火 |
| `404.php`    | 404 エラー                                             | 存在しない URL すべて            |
| `search.php` | 検索結果                                               | `/?s=keyword`                    |

### 標準テンプレートに書いてよいこと

- WordPress 関数によるデータ取得（`get_posts`, `get_the_*`, `apply_filters` 等）
- 取得したデータを `compact()` でまとめて `Blade::render()` に渡す

### 書いてはいけないこと（`app/Services/` に逃がす）

- 外部 API 呼び出し・キャッシュ制御
- バリデーション・状態遷移
- 複数テンプレートで共有する派生データ生成
- 条件分岐や `foreach` を伴う加工処理
- `get_post_type()` / `is_page()` などによる **テンプレ内分岐**（→ 専用テンプレートを分けて解消する）

### Service 化の目安

テンプレ内に **`if` / `foreach` を書きたくなった、または 30 行を超えた** タイミングで `app/Services/` に逃がす。テンプレは取得と `compact()` だけで済む状態を保つ。

### REST API・WP-CLI 等

テンプレート階層に乗らないエントリポイントが必要な場合のみ `app/Http/Controllers/` にクラスを作る。テーマ表示のために Controller クラスを増やさない。

## フック登録

- 新規フック登録は `includes/functions/` に専用ファイルを作成し、`functions.php` から `require_once` で読み込む
- 新規ドメインロジック・REST API は `app/` 層に書く（`functions.php` に直接書かない）

`includes/functions/` 以下の各ファイルはフック登録のみを担う。現在の登録一覧:

| ファイル | フック | 内容 |
| --- | --- | --- |
| `theme-support.php` | `after_setup_theme` | title-tag / post-thumbnails / html5 |
| `post-types.php` | `init` | CPT `column` の登録 |
| `assets.php` | `wp_enqueue_scripts` | ViteAssets 経由でアセット登録（Vanilla JS と React を `is_page(["contact"])` で排他切替） |
| `contact.php` | `rest_api_init` | `ContactController::register()` でエンドポイント登録 |
| `mail.php` | `phpmailer_init` / `wp_mail_from` / `wp_mail` | SMTP 設定・From 修正・開発環境でのメール振り分け |

## カスタム投稿タイプ

`post-types.php` が `init` フックで登録する。

| CPT | アーカイブ | REST API | スラッグ | supports |
| --- | --- | --- | --- | --- |
| `column`（コラム） | あり | 有効 | `/column/` | title / editor / thumbnail / excerpt |

新規 CPT も `post-types.php` に追加し、表示は専用テンプレート（`archive-{post_type}.php` / `single-{post_type}.php`）で受ける（前掲「汎用テンプレートを使わず、専用テンプレートで拡張する」）。

## 出力エスケープ

[security.md](../security.md)（常時ロード）の「出力エスケープ」に従う。

## REST API

### Controller クラスの実装パターン

`app/Http/Controllers/` に Controller クラスを作り、`register()` メソッドでルート登録を集約する。フック登録は `includes/functions/` の専用ファイルに書く。

```php
// app/Http/Controllers/ContactController.php
namespace WpBlade\Http\Controllers;

class ContactController {
    public function register(): void {
        register_rest_route('wpblade/v1', '/contact', [
            'methods'             => 'POST',
            'callback'            => [$this, 'handle'],
            'permission_callback' => [$this, 'checkPermission'],
        ]);
    }

    public function checkPermission(\WP_REST_Request $request): bool|\WP_Error {
        $nonce = $request->get_header('X-WP-Nonce');
        if (!wp_verify_nonce($nonce, 'wp_rest')) {
            return new \WP_Error('invalid_nonce', 'Nonce が不正です', ['status' => 403]);
        }
        return true;
    }

    public function handle(\WP_REST_Request $request): \WP_REST_Response|\WP_Error {
        $body = json_decode($request->get_body(), true);
        if (!is_array($body)) {
            return new \WP_Error('invalid_request', 'リクエスト形式が不正です', ['status' => 400]);
        }
        $contact_service = new ContactService();
        $result = $contact_service->submit($body);
        if (is_wp_error($result)) {
            return $result;
        }
        return new \WP_REST_Response(['message' => 'お問い合わせを受け付けました。'], 200);
    }
}
```

```php
// includes/functions/contact.php
add_action('rest_api_init', function (): void {
    $contact_controller = new ContactController();
    $contact_controller->register();
});
```

### 認証・権限チェック

| ケース | 方法 |
| --- | --- |
| フロントエンドから Fetch | `X-WP-Nonce` ヘッダー + `wp_verify_nonce($nonce, 'wp_rest')` |
| 管理画面ユーザー限定 | `current_user_can('manage_options')` を `permission_callback` で確認 |
| 公開エンドポイント | `'__return_true'` を使ってよい（意図的に公開するときのみ） |

### エラーコード体系

`WP_Error` には意味のあるコードを付ける（クライアントがハンドリングしやすくなる）。

| コード | HTTP ステータス | 意味 |
| --- | --- | --- |
| `invalid_request` | 400 | リクエスト形式不正（JSON パース失敗など） |
| `validation_error` | 400 | バリデーション失敗 |
| `mail_error` | 500 | メール送信失敗 |
| `invalid_nonce` | 403 | Nonce 不正 |

### PHP 側バリデーション

フォームデータの検証は Service 層で行う。Respect\Validation を使用。バリデーション失敗は `WP_Error` で即座に返す。**`validate()` は入力を文字列に正規化してから検証し、成功時は正規化済みの値を返す**（呼び出し側が元の `$data` を再度取り出さない。抽出ロジックの二重化を防ぐ）。

```php
// app/Services/ContactService.php（抜粋）
use Respect\Validation\Validator as v;

/**
 * @return array{name: string, email: string, message: string, store: string}|\WP_Error
 */
private function validate(array $data): array|\WP_Error {
    // 文字列以外（配列・null 等）は空文字に正規化 → notEmpty で弾かれる
    $name    = is_string($data['name']    ?? null) ? $data['name']    : '';
    $email   = is_string($data['email']   ?? null) ? $data['email']   : '';
    $message = is_string($data['message'] ?? null) ? $data['message'] : '';
    $store   = is_string($data['store']   ?? null) ? $data['store']   : '';

    $rules = [
        [$name,    v::notEmpty(),               ContactConstant::ERROR_NAME_REQUIRED],
        [$email,   v::email(),                  ContactConstant::ERROR_EMAIL_INVALID],
        // … 長さ・許可文字などのルールを列挙 …
    ];
    foreach ($rules as [$value, $validator, $error_message]) {
        try {
            $validator->assert($value);
        } catch (ValidationException) {
            return new \WP_Error('validation_error', $error_message, ['status' => 400]);
        }
    }
    return compact('name', 'email', 'message', 'store');
}
```

### ハニーポットの PHP 側処理

`honeypot` フィールドが `true` の場合、Service 層でサイレントに `true` を返す。ボットに検知されたと悟らせないため、`WP_Error` にせず成功と同じレスポンスを返すこと。

```php
public function submit(array $data): true|\WP_Error {
    if ($data['honeypot'] ?? false) {
        return true; // ボット → 何もせず成功扱い
    }
    // バリデーション → メール送信 ...
}
```

- `wp_remote_get` の失敗は `is_wp_error()` で処理する

## メール機能

### Service の責務分担

`MailService` は「宛先解決」「管理者通知」「自動返信」の 3 責務を持つ。

| メソッド | 役割 | 失敗時の扱い |
| --- | --- | --- |
| `resolveRecipients()` | `ALWAYS_NOTIFY` + `ROUTES[$routeKey]` で宛先を組み立てる | - |
| `sendNotification()` | 管理者（本部 + 振り分け先）へ通知 | 全宛先成功で `true`、1件でも失敗で `false`（致命的エラー） |
| `sendAutoReply()` | 送信者へ自動返信 | 失敗しても致命的エラーにしない（Controller で `mail_error` を返さない） |

`ContactService::submit()` が `MailService` を呼ぶ側。`sendNotification()` が `false` を返したときのみ `mail_error` の `WP_Error` を返す。

### MailConstant の構造

宛先ルーティングは `MailConstant` のみで管理する。新店舗追加時もここだけ編集。

```php
// app/Constants/MailConstant.php
final class MailConstant {
    const DEV_MAIL      = 'dev@example.com';        // 空文字でキャプチャ無効化
    const ALWAYS_NOTIFY = ['honbu@example.com'];    // 常に通知する本部アドレス
    const ROUTES = [
        'shibuya'  => ['name' => '渋谷店', 'email' => 'shibuya@example.com'],
        'shinjuku' => ['name' => '新宿店', 'email' => 'shinjuku@example.com'],
    ];
}
```

- フォームの `store` 値（`routeKey`）が `ROUTES` のキーと一致した場合のみ、該当店舗を宛先に追加する
- `ALWAYS_NOTIFY` の本部は常に全通知を受け取る

### 環境変数による SMTP・開発振り分け

メール関連のフック設定は `includes/functions/mail.php` に集約する。

| 環境変数 | 用途 |
| --- | --- |
| `WPBLADE_ENV` | `production` の場合のみ実際の宛先に送信 |
| `WPBLADE_SMTP_HOST` | SMTP ホスト（未設定なら PHP デフォルト） |
| `WPBLADE_SMTP_PORT` | SMTP ポート（デフォルト 1025 = MailHog 用） |

- `WPBLADE_ENV=production` 以外では、`MailConstant::DEV_MAIL` に全メールをリダイレクトし、件名に元の宛先を付与する
- `DEV_MAIL` が空文字の場合は SMTP ホスト（MailHog など）に委ねる
- 本番・開発の分岐は `wp_mail` フィルタで実装し、Service クラスには環境判定を書かない
