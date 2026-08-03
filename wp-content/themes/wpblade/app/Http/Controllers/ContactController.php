<?php

namespace WpBlade\Http\Controllers;

use Fig\Http\Message\StatusCodeInterface;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use WpBlade\Services\ContactService;

/**
 * お問い合わせ REST API エンドポイント。
 *
 * POST wpblade/v1/contact
 *
 * @package WpBlade\Http\Controllers
 */
class ContactController
{
    public function register(): void
    {
        register_rest_route("wpblade/v1", "/contact", [
            "methods" => "POST",
            "callback" => [$this, "handle"],
            "permission_callback" => [$this, "checkPermission"],
        ]);
    }

    public function checkPermission(WP_REST_Request $request): bool|WP_Error
    {
        $nonce = $request->get_header("X-WP-Nonce");

        if (!wp_verify_nonce((string) $nonce, "wp_rest")) {
            return new WP_Error("invalid_nonce", "Nonce が不正です。", [
                "status" => StatusCodeInterface::STATUS_FORBIDDEN,
            ]);
        }

        return true;
    }

    public function handle(WP_REST_Request $request): WP_REST_Response|WP_Error
    {
        $data = $request->get_json_params();

        if (!is_array($data)) {
            return new WP_Error(
                "invalid_request",
                "リクエストの形式が正しくありません。",
                ["status" => StatusCodeInterface::STATUS_BAD_REQUEST],
            );
        }

        $contact_service = new ContactService();
        $result = $contact_service->submit($data);

        if (is_wp_error($result)) {
            return $result;
        }

        return new WP_REST_Response(
            ["message" => "お問い合わせを受け付けました。"],
            StatusCodeInterface::STATUS_OK,
        );
    }
}
