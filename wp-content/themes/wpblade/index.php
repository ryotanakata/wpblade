<?php

/**
 * フォールバックテンプレート。
 * いずれのテンプレートにも一致しない場合に 404 として処理する。
 *
 * @package WpBlade
 */

use WpBlade\View\Blade;

status_header(404);
echo Blade::render("pages.404");
