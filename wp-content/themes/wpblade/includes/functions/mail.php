<?php

/**
 * メール設定。
 *
 * 送信先ルーティング（環境別）:
 *   - WPBLADE_ENV=production                      → 実際の宛先に送信
 *   - WPBLADE_ENV 未設定 + MailConstant::DEV_MAIL 設定済み → DEV_MAIL へリダイレクト（ステージング）
 *   - WPBLADE_ENV 未設定 + MailConstant::DEV_MAIL 未設定   → WPBLADE_SMTP_HOST の SMTP サーバへ（ローカル Mailpit 等）
 *
 * @package WpBlade
 */

use PHPMailer\PHPMailer\PHPMailer;
use WpBlade\Constants\MailConstant;

// ローカル開発用 SMTP 設定（Mailpit 等）
add_action("phpmailer_init", function (PHPMailer $phpmailer): void {
    $host = (string) getenv("WPBLADE_SMTP_HOST");
    if ($host === "") {
        return;
    }

    $phpmailer->isSMTP();
    $phpmailer->Host = $host;
    $phpmailer->Port = (int) (getenv("WPBLADE_SMTP_PORT") ?: 1025);
    $phpmailer->SMTPAuth = false;
    $phpmailer->SMTPAutoTLS = false;
});

// WordPress のデフォルト From（wordpress@localhost）は無効なため差し替える
add_filter("wp_mail_from", function (string $from): string {
    if ($from === "wordpress@localhost") {
        return "no-reply@wpblade.local";
    }
    return $from;
});

// 非本番環境では MailConstant::DEV_MAIL へリダイレクト
add_filter("wp_mail", function (array $args): array {
    if (getenv("WPBLADE_ENV") === "production") {
        return $args;
    }

    if (MailConstant::DEV_MAIL === "") {
        return $args;
    }

    $original_to     = is_array($args["to"]) ? implode(", ", $args["to"]) : $args["to"];
    $args["subject"] = sprintf("[TEST: %s] %s", $original_to, $args["subject"]);
    $args["to"]      = MailConstant::DEV_MAIL;

    return $args;
});
