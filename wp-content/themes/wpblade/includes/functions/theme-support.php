<?php

/**
 * テーマサポートの登録。
 *
 * @package WpBlade
 */

add_action("after_setup_theme", function (): void {
    add_theme_support("title-tag");
    add_theme_support("post-thumbnails");
    add_theme_support("html5", [
        "search-form",
        "comment-form",
        "comment-list",
        "gallery",
        "caption",
        "style",
        "script",
    ]);
});
