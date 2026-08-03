<?php

namespace WpBlade\View;

use eftec\bladeone\BladeOne;
use WpBlade\Constants\NavConstant;

/**
 * BladeOne レンダラの薄いラッパ。
 *
 * - ビュー: resources/views（ドット記法。例 'pages.single'）
 * - コンパイルキャッシュ: cache/（テーマ直下・.gitignore・本番で書き込み可能にする）
 *
 * @package WpBlade\View
 */
class Blade
{
    /**
     * BladeOne のシングルトンインスタンス。
     * 初回 {@see self::instance()} 呼び出し時に生成される。
     *
     * @var BladeOne|null
     */
    private static $instance = null;

    /**
     * BladeOne インスタンスをシングルトンで返す。
     *
     * 初回呼び出し時にビューディレクトリ・キャッシュディレクトリを解決し、
     * キャッシュディレクトリが存在しない場合は {@see wp_mkdir_p()} で作成する。
     * また、すべてのビューで使用できる共有変数（`shared_` プレフィックスで統一）を登録する。
     *
     * @return BladeOne 初期化済みの BladeOne インスタンス。
     */
    public static function instance(): BladeOne
    {
        if (self::$instance === null) {
            $views = get_theme_file_path("/resources/views");
            $cache = get_theme_file_path("/cache");

            if (!is_dir($cache)) {
                wp_mkdir_p($cache);
            }

            // cache/ はテーマ配下＝web 公開下にあるため、コンパイル結果（*.bladec）の
            // 直リードを塞ぐ。nginx では効かないのでサーバ側設定も必要（security.md 参照）
            $htaccess = $cache . "/.htaccess";
            if (!file_exists($htaccess)) {
                $written = @file_put_contents(
                    $htaccess,
                    "<IfModule mod_authz_core.c>\n  Require all denied\n</IfModule>\n<IfModule !mod_authz_core.c>\n  Deny from all\n</IfModule>\n",
                );
                // 失敗を黙って飲むと保護が無いまま *.bladec が公開下に残るのでログに残す
                if ($written === false) {
                    error_log(
                        "wpblade: cache/.htaccess を作成できませんでした（{$htaccess}）。web サーバ側で *.bladec への直アクセスを塞いでください。",
                    );
                }
            }

            self::$instance = new BladeOne($views, $cache, BladeOne::MODE_AUTO);
            self::$instance->share(
                "shared_image_url",
                get_template_directory_uri() . "/resources/images/",
            );
            self::$instance->share("shared_header_nav", NavConstant::PRIMARY);
        }

        return self::$instance;
    }

    /**
     * ビューを描画して HTML 文字列を返す。
     *
     * @param  string               $view ドット記法のビュー名（例: `'pages.single'`）。
     * @param  array<string, mixed> $data ビューに渡す変数の連想配列。省略時は空配列。
     * @return string               レンダリング済みの HTML 文字列。
     *
     * @throws \Exception BladeOne がコンパイルまたはレンダリングに失敗した場合。
     */
    public static function render(string $view, array $data = []): string
    {
        return self::instance()->run($view, $data);
    }
}
