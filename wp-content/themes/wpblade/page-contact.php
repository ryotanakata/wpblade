<?php

/**
 * お問い合わせページテンプレート。
 *
 * @package WpBlade
 */

use WpBlade\View\Blade;

if (have_posts()) {
    the_post();
}

$title = get_the_title();

echo Blade::render('pages.contact.index', compact('title'));
