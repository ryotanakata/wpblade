<?php

/**
 * サイトのトップページ（固定フロントページ）。
 *
 * @package WpBlade
 */

use WpBlade\Services\ColumnService;
use WpBlade\View\Blade;

// 最新コラム（最新3件）: 日付整形などの加工は ColumnService に逃がし、表示用の配列で受け取る
$latest_posts = get_posts([
    "post_type" => "column",
    "numberposts" => 3,
    "post_status" => "publish",
    "orderby" => "date",
    "order" => "DESC",
]);

$column_service = new ColumnService();
$latest_columns = $column_service->buildArchiveItems($latest_posts);

// ページの h1（見出し階層の起点。視覚的には隠す）
$site_name = get_bloginfo("name");

// コラム一覧へのリンク: Blade の {{ }} は HTML エスケープのみで URL コンテキストを満たさない
$column_archive_link = esc_url((string) get_post_type_archive_link("column"));

echo Blade::render(
    "pages.top.index",
    compact("latest_columns", "site_name", "column_archive_link"),
);
