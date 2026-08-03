<?php

namespace WpBlade\Services;

use Fig\Http\Message\StatusCodeInterface;
use Respect\Validation\Exceptions\ValidationException;
use Respect\Validation\Validator as v;
use WP_Error;
use WpBlade\Constants\ContactConstant;

/**
 * お問い合わせフォームの送信処理。
 *
 * ハニーポット判定 → バリデーション → MailService 経由でメール送信の順に実行する。
 * （ボットにはバリデーションエラーを返さず、先にサイレント成功で弾く）
 *
 * @package WpBlade\Services
 */
class ContactService
{
    /**
     * フォームデータを受け取り、バリデーション・メール送信を実行する。
     *
     * ハニーポットが true の場合はメールを送らず成功を返す（ボットに検知を悟らせない）。
     *
     * @param  array<string, mixed> $data
     * @return true|WP_Error
     */
    public function submit(array $data): true|WP_Error
    {
        if (!empty($data["honeypot"])) {
            return true;
        }

        $validated = $this->validate($data);
        if (is_wp_error($validated)) {
            return $validated;
        }

        return $this->sendMail(
            $validated["name"],
            $validated["email"],
            $validated["message"],
            $validated["store"],
        );
    }

    /**
     * 入力値を文字列に正規化して検証し、正規化済みの値を返す。
     *
     * @param  array<string, mixed> $data
     * @return array{name: string, email: string, message: string, store: string}|WP_Error
     */
    private function validate(array $data): array|WP_Error
    {
        $name    = is_string($data["name"]    ?? null) ? $data["name"]    : "";
        $email   = is_string($data["email"]   ?? null) ? $data["email"]   : "";
        $message = is_string($data["message"] ?? null) ? $data["message"] : "";
        $store   = is_string($data["store"]   ?? null) ? $data["store"]   : "";

        /** @var array<int, array{0: string, 1: \Respect\Validation\Validatable, 2: string}> */
        $rules = [
            [$name,    v::notEmpty(),                                        ContactConstant::ERROR_NAME_REQUIRED],
            [$name,    v::length(null, ContactConstant::NAME_MAX_LENGTH),    ContactConstant::ERROR_NAME_TOO_LONG],
            [$name,    v::regex(ContactConstant::REGEX_ALLOWED_NAME),        ContactConstant::ERROR_NAME_INVALID_CHARS],
            [$email,   v::notEmpty(),                                        ContactConstant::ERROR_EMAIL_REQUIRED],
            [$email,   v::email(),                                           ContactConstant::ERROR_EMAIL_INVALID],
            [$message, v::notEmpty(),                                        ContactConstant::ERROR_MESSAGE_REQUIRED],
            [$message, v::length(ContactConstant::MESSAGE_MIN_LENGTH, null), ContactConstant::ERROR_MESSAGE_TOO_SHORT],
            [$message, v::length(null, ContactConstant::MESSAGE_MAX_LENGTH), ContactConstant::ERROR_MESSAGE_TOO_LONG],
            [$message, v::regex(ContactConstant::REGEX_ALLOWED_MESSAGE),     ContactConstant::ERROR_MESSAGE_INVALID_CHARS],
            [$message, v::regex(ContactConstant::REGEX_CONTAINS_JAPANESE),   ContactConstant::ERROR_MESSAGE_NOT_JAPANESE],
        ];

        foreach ($rules as [$value, $validator, $error_message]) {
            try {
                $validator->assert($value);
            } catch (ValidationException) {
                return new WP_Error("validation_error", $error_message, [
                    "status" => StatusCodeInterface::STATUS_BAD_REQUEST,
                ]);
            }
        }

        return compact("name", "email", "message", "store");
    }

    /**
     * @param  string $route_key  フォームの store 値（未選択は空文字 → 本部のみ）
     * @return true|WP_Error
     */
    private function sendMail(
        string $name,
        string $email,
        string $message,
        string $route_key,
    ): true|WP_Error {
        $mail = new MailService();

        if (!$mail->sendNotification($name, $email, $message, $route_key)) {
            return new WP_Error(
                "mail_error",
                ContactConstant::ERROR_MAIL_FAILED,
                ["status" => StatusCodeInterface::STATUS_INTERNAL_SERVER_ERROR],
            );
        }

        $mail->sendAutoReply($name, $email, $message);

        return true;
    }
}
