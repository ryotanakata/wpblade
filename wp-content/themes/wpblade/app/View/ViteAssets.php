<?php

namespace WpBlade\View;

/**
 * Vite アセットの解決・enqueue。
 *
 * - dev: テーマ直下に `public/hot`（dev サーバ URL を書いたファイル）があれば dev モード。
 *   `@vite/client` と各エントリを dev サーバから type=module で読む（HMR）。
 * - prod: `public/.vite/manifest.json` を読み、論理名 → ハッシュ付き出力ファイルを
 *   解決して enqueue する。
 *
 * @package WpBlade\View
 */
class ViteAssets
{
    /**
     * JS エントリポイントの論理パス。
     *
     * @var string
     */
    private const JS_ENTRY = "resources/js/script.js";

    /**
     * CSS エントリポイントの論理パス。
     *
     * @var string
     */
    private const CSS_ENTRY = "resources/scss/style.scss";

    /**
     * TS/React エントリポイントの論理パス。
     *
     * @var string
     */
    private const TS_ENTRY = "resources/ts/script.tsx";

    /**
     * wp_enqueue_* に渡すスクリプト・スタイルのハンドル名。
     *
     * @var string
     */
    private const SCRIPT_HANDLE = "wpblade-vite";

    /**
     * manifest.json のデコード済みキャッシュ。
     * 未読み込みの場合は null。
     *
     * @var array<string, mixed>|null
     */
    private $manifest = null;

    /**
     * window.wpblade が既に出力済みかどうか。
     * modernize() を複数回呼んでも重複しないためのフラグ。
     *
     * @var bool
     */
    private bool $wpbladeInjected = false;

    /**
     * Vite dev サーバが起動中か（hot ファイルの有無）を判定する。
     *
     * @return bool dev モードであれば true。
     */
    public function isDev(): bool
    {
        return file_exists(get_theme_file_path("/public/hot"));
    }

    /**
     * hot ファイルに書かれた dev サーバの URL を返す。
     *
     * @return string 末尾スラッシュを除いた dev サーバ URL（例: `http://localhost:5173`）。
     */
    public function devUrl(): string
    {
        return rtrim(
            (string) file_get_contents(get_theme_file_path("/public/hot")),
        );
    }

    /**
     * 現在の環境（dev / prod）に応じてアセットを enqueue する。
     *
     * @return void
     */
    public function enqueue(): void
    {
        if ($this->isDev()) {
            $this->enqueueDev();
            return;
        }
        $this->enqueueProd();
    }

    /**
     * dev モード用 enqueue。
     *
     * `wp_head` アクションに `type="module"` スクリプトタグを出力するコールバックを登録し、
     * `@vite/client`・CSS エントリ・JS エントリを dev サーバから読み込む。
     *
     * @return void
     */
    private function enqueueDev(): void
    {
        $base = $this->devUrl();
        $urls = [
            $base . "/@vite/client",
            $base . "/" . self::CSS_ENTRY,
            $base . "/" . self::JS_ENTRY,
        ];
        add_action(
            "wp_head",
            function () use ($urls) {
                foreach ($urls as $url) {
                    echo '<script type="module" src="' .
                        esc_url($url) .
                        '"></script>' .
                        "\n";
                }
            },
            5,
        );
    }

    /**
     * prod モード用 enqueue。
     *
     * manifest.json を参照し、JS エントリに紐づく vendor CSS → メイン CSS → JS の順に
     * WordPress の enqueue システムへ登録する。
     *
     * @return void
     */
    private function enqueueProd(): void
    {
        $manifest = $this->manifest();
        $deps = [];

        if (!empty($manifest[self::JS_ENTRY]["css"])) {
            foreach ($manifest[self::JS_ENTRY]["css"] as $i => $file) {
                $handle = self::SCRIPT_HANDLE . "-vendor-" . $i;
                wp_enqueue_style(
                    $handle,
                    get_theme_file_uri("/public/" . $file),
                    [],
                    null,
                );
                $deps[] = $handle;
            }
        }

        $css = $this->asset(self::CSS_ENTRY);
        if ($css !== null) {
            wp_enqueue_style(self::SCRIPT_HANDLE, $css, $deps, null);
        }

        $js = $this->asset(self::JS_ENTRY);
        if ($js !== null) {
            wp_enqueue_script(self::SCRIPT_HANDLE, $js, [], null, true);
            add_filter("script_loader_tag", [$this, "moduleTypeTag"], 10, 3);
        }
    }

    /**
     * React ページ用の enqueue。
     *
     * `resources/ts/script.tsx` を解決して enqueue し、
     * `window.wpblade`（REST URL・nonce）を wp_head に出力する。
     * CSS も `resources/scss/style.scss` から読み込む。
     *
     * `enqueue()` と排他で使う（React ページでは `enqueue()` を呼ばない）。
     *
     * @return void
     */
    public function enqueueReact(): void
    {
        $handle = "wpblade-react";

        // window.wpblade を wp_head の早い段階に一度だけ出力
        if (!$this->wpbladeInjected) {
            $this->wpbladeInjected = true;
            add_action("wp_head", function (): void {
                // HEX フラグで `</script>` による分断を構造的に不可能にする
                $data = wp_json_encode(
                    [
                        "restUrl" => get_rest_url(),
                        "nonce"   => wp_create_nonce("wp_rest"),
                    ],
                    JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT,
                );
                echo "<script>window.wpblade = {$data};</script>\n";
            }, 4);
        }

        if ($this->isDev()) {
            $base = $this->devUrl();
            add_action(
                "wp_head",
                function () use ($base): void {
                    // @vitejs/plugin-react が要求する React Refresh プリアンブル
                    echo '<script type="module">' . "\n";
                    echo '  import RefreshRuntime from "' . esc_url($base . "/@react-refresh") . '";' . "\n";
                    echo '  RefreshRuntime.injectIntoGlobalHook(window);' . "\n";
                    echo '  window.$RefreshReg$ = () => {};' . "\n";
                    echo '  window.$RefreshSig$ = () => (type) => type;' . "\n";
                    echo '  window.__vite_plugin_react_preamble_installed__ = true;' . "\n";
                    echo '</script>' . "\n";

                    $urls = [
                        $base . "/@vite/client",
                        $base . "/" . self::CSS_ENTRY,
                        $base . "/" . self::TS_ENTRY,
                    ];
                    foreach ($urls as $url) {
                        echo '<script type="module" src="' .
                            esc_url($url) .
                            '"></script>' .
                            "\n";
                    }
                },
                5,
            );
            return;
        }

        $manifest = $this->manifest();

        // グローバル CSS
        $css = $this->asset(self::CSS_ENTRY);
        if ($css !== null) {
            wp_enqueue_style(self::SCRIPT_HANDLE, $css, [], null);
        }

        // CSS Modules から生成された CSS
        if (!empty($manifest[self::TS_ENTRY]["css"])) {
            foreach ($manifest[self::TS_ENTRY]["css"] as $i => $file) {
                wp_enqueue_style(
                    "{$handle}-css-{$i}",
                    get_theme_file_uri("/public/{$file}"),
                    [],
                    null,
                );
            }
        }

        // JS（type="module"）
        $js = $this->asset(self::TS_ENTRY);
        if ($js !== null) {
            wp_enqueue_script($handle, $js, [], null, true);
            add_filter(
                "script_loader_tag",
                function (
                    string $tag,
                    string $h,
                    string $src,
                ) use ($handle): string {
                    if ($h !== $handle) {
                        return $tag;
                    }
                    return '<script type="module" src="' .
                        esc_url($src) .
                        '"></script>' .
                        "\n";
                },
                10,
                3,
            );
        }
    }

    /**
     * manifest.json のエントリから公開 URL を解決して返す。
     *
     * @param  string      $entry manifest.json のキーとなる論理パス（例: `resources/js/script.js`）。
     * @return string|null 公開 URL。エントリが存在しない場合は null。
     */
    public function asset(string $entry): ?string
    {
        $manifest = $this->manifest();
        if (!isset($manifest[$entry]["file"])) {
            return null;
        }
        return get_theme_file_uri("/public/" . $manifest[$entry]["file"]);
    }

    /**
     * `script_loader_tag` フィルタのコールバック。
     *
     * 対象ハンドルのスクリプトタグを `type="module"` に書き換える。
     *
     * @param  string $tag    WordPress が生成した `<script>` タグ文字列。
     * @param  string $handle enqueue 時に指定したハンドル名。
     * @param  string $src    スクリプトの URL。
     * @return string         書き換え後の `<script>` タグ文字列。
     */
    public function moduleTypeTag(
        string $tag,
        string $handle,
        string $src,
    ): string {
        if ($handle !== self::SCRIPT_HANDLE) {
            return $tag;
        }
        return '<script type="module" src="' .
            esc_url($src) .
            '"></script>' .
            "\n";
    }

    /**
     * manifest.json を読み込み、デコード済み配列をキャッシュして返す。
     *
     * ファイルが存在しない場合は空配列を返す。
     * 2 回目以降の呼び出しはキャッシュを返すため I/O は発生しない。
     *
     * @return array<string, mixed> manifest.json の内容。ファイル不在時は空配列。
     */
    private function manifest(): array
    {
        if ($this->manifest === null) {
            $path = get_theme_file_path("/public/.vite/manifest.json");
            $this->manifest = file_exists($path)
                ? (array) json_decode((string) file_get_contents($path), true)
                : [];
        }
        return $this->manifest;
    }
}
