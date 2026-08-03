<?php

/**
 * コラムアーカイブ（一覧）テンプレート（CPT: column）。
 *
 * @package WpBlade
 */

use WpBlade\Services\ColumnService;
use WpBlade\View\Blade;

// アーカイブ見出し: HTML を含む get_the_archive_title() ではなくプレーンな名称を渡す
$archive_title = post_type_archive_title("", false);

// 一覧カード: サムネイル解決などの加工は ColumnService に逃がし、表示用の配列で受け取る
global $wp_query;
$column_service = new ColumnService();
$columns = $column_service->buildArchiveItems($wp_query->posts);

// ページネーション: paginate_links() の HTML ではなく番号リストを渡し、Blade で組む
$current = max(1, (int) get_query_var("paged"));
$total_pages = (int) $wp_query->max_num_pages;
$page_numbers = range(1, max(1, $total_pages));
$pagination_links = $column_service->buildPaginationLinks($page_numbers);

echo Blade::render(
    "pages.column.index",
    compact("columns", "archive_title", "current", "total_pages", "page_numbers", "pagination_links"),
);
