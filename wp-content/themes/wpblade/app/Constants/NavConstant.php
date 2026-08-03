<?php

namespace WpBlade\Constants;

/**
 * ナビゲーションメニューの定義。
 *
 * 管理画面（外観 → メニュー）ではなくコードで定義する方針のため、ここに集約する。
 * `url` はサイトルートからの相対パスで持ち、出力時に Blade 側で `home_url()` を通す。
 *
 * @package WpBlade\Constants
 */
final class NavConstant
{
    /**
     * グローバルナビ（旧 primary ロケーション）の項目。
     *
     * @var array<int, array{label: string, path: string}>
     */
    public const PRIMARY = [
        ["label" => "Home", "path" => "/"],
        ["label" => "Store", "path" => "/store/"],
        ["label" => "Column", "path" => "/column/"],
    ];
}
