<?php

/**
 * カスタム投稿タイプの登録。
 *
 * @package WpBlade
 */

add_action("init", function (): void {
    register_post_type("column", [
        "labels" => [
            "name" => "Column",
            "singular_name" => "Column",
            "add_new_item" => "Add New Column",
            "edit_item" => "Edit Column",
            "view_item" => "View Column",
            "search_items" => "Search Columns",
            "not_found" => "No columns found",
        ],
        "public" => true,
        "has_archive" => true,
        "show_in_rest" => true,
        "menu_icon" => "dashicons-edit-large",
        "supports" => ["title", "editor", "thumbnail", "excerpt"],
        "rewrite" => ["slug" => "column"],
    ]);
});
