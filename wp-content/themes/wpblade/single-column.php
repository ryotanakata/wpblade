<?php

/**
 * コラム個別ページテンプレート（CPT: column）。
 *
 * @package WpBlade
 */

use WpBlade\Services\ColumnService;
use WpBlade\View\Blade;

if (have_posts()) {
    the_post();
}

$title = get_the_title();
$content = apply_filters("the_content", get_the_content());
$date = get_the_date("Y.m.d");
$date_attr = get_the_date("Y-m-d");

// サムネイル: HTML ではなく URL + サイズ + alt をスカラで渡す（組み立ては Service）
$column_service = new ColumnService();
$thumbnail = $column_service->buildThumbnail((int) get_the_ID());

// 前後の投稿: リンク HTML ではなく WP_Post を渡す
$prev_post = get_previous_post();
$next_post = get_next_post();
$prev_permalink = $prev_post ? esc_url((string) get_permalink($prev_post)) : null;
$next_permalink = $next_post ? esc_url((string) get_permalink($next_post)) : null;

echo Blade::render(
    "pages.column.detail.index",
    compact(
        "title",
        "content",
        "date",
        "date_attr",
        "thumbnail",
        "prev_post",
        "next_post",
        "prev_permalink",
        "next_permalink",
    ),
);
