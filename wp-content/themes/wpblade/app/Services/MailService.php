<?php

namespace WpBlade\Services;

use WpBlade\Constants\MailConstant;
use WpBlade\View\Blade;

/**
 * メール送信サービス。
 *
 * 宛先解決・管理者通知・自動返信の3つの責務を持つ。
 * ContactService から呼び出す。
 *
 * @package WpBlade\Services
 */
class MailService
{
    /**
     * ルーティングキーから通知先メールアドレス一覧を返す。
     *
     * MailConstant::ALWAYS_NOTIFY に、キーが一致した店舗アドレスを追加する。
     * キーが空文字または未定義の場合は ALWAYS_NOTIFY のみ返す。
     *
     * @param  string      $route_key  フォームの store 値
     * @return list<string>
     */
    public function resolveRecipients(string $route_key): array
    {
        $recipients = MailConstant::ALWAYS_NOTIFY;

        if ($route_key !== '' && isset(MailConstant::ROUTES[$route_key])) {
            $recipients[] = MailConstant::ROUTES[$route_key]['email'];
        }

        return $recipients;
    }

    /**
     * 管理者（本部 + 振り分け先店舗）への通知メールを送信する。
     *
     * @param  string $name
     * @param  string $email     送信者のメールアドレス（Reply-To に設定）
     * @param  string $message
     * @param  string $route_key  フォームの store 値（未選択は空文字）
     * @return bool   すべての宛先への送信が成功したか
     */
    public function sendNotification(
        string $name,
        string $email,
        string $message,
        string $route_key,
    ): bool {
        $recipients = $this->resolveRecipients($route_key);
        $site_name  = (string) get_option('blogname');
        // 件名・ヘッダーに補間する値は下流ライブラリの CRLF 除去に依存せず、ここで落とす
        $header_name  = (string) preg_replace('/[\r\n]+/', '', $name);
        $header_email = (string) preg_replace('/[\r\n]+/', '', $email);
        $subject    = sprintf('【お問い合わせ】%s様より', $header_name);
        $body       = Blade::render('mail.contact', compact('name', 'email', 'message', 'site_name'));
        $headers    = [
            'Content-Type: text/plain; charset=UTF-8',
            sprintf('Reply-To: %s <%s>', $header_name, $header_email),
        ];

        foreach ($recipients as $to) {
            if (!wp_mail($to, $subject, $body, $headers)) {
                return false;
            }
        }

        return true;
    }

    /**
     * 送信者への自動返信メールを送信する。
     *
     * 失敗しても致命的エラーにしない想定で、呼び出し側が判断する。
     *
     * @param  string $name
     * @param  string $email    返信先（送信者）のメールアドレス
     * @param  string $message
     * @return bool
     */
    public function sendAutoReply(
        string $name,
        string $email,
        string $message,
    ): bool {
        $site_name = (string) get_option('blogname');
        $subject   = sprintf('【%s】お問い合わせを受け付けました', $site_name);
        $body      = Blade::render('mail.reply', compact('name', 'message', 'site_name'));
        $headers   = ['Content-Type: text/plain; charset=UTF-8'];

        return wp_mail($email, $subject, $body, $headers);
    }
}
