<?php

/**
 * 固定ページテンプレート。
 * 「アクセス」「店舗について」などに使用される。
 *
 * @package WpBlade
 */

use WpBlade\View\Blade;

if (have_posts()) {
    the_post();
}

$title = get_the_title();
$content = apply_filters("the_content", get_the_content());

echo Blade::render("pages.store.index", compact("title", "content"));
