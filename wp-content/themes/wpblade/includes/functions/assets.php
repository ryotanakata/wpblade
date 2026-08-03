<?php

/**
 * アセットの enqueue（Vite 連携）。
 *
 * @package WpBlade
 */

use WpBlade\View\ViteAssets;

add_action("wp_enqueue_scripts", function (): void {
    $vite = new ViteAssets();

    if (is_page(["contact"])) {
        $vite->enqueueReact();
        return;
    }

    $vite->enqueue();
});
