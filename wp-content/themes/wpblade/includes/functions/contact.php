<?php

/**
 * お問い合わせ REST API の登録。
 *
 * @package WpBlade
 */

use WpBlade\Http\Controllers\ContactController;

add_action("rest_api_init", function (): void {
    $contact_controller = new ContactController();
    $contact_controller->register();
});
