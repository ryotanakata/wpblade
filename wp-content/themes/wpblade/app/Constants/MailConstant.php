<?php

namespace WpBlade\Constants;

final class MailConstant
{
    /**
     * 非本番環境（WPBLADE_ENV !== 'production'）でのキャッチオール転送先。
     * 本番以外の全宛先をここへリダイレクトし、誤送信を防ぐ。
     * 空文字にすると SMTP_HOST（MailHog 等）の設定に委ねる。
     */
    public const DEV_MAIL = 'dev@example.com';

    /**
     * 必ず通知する宛先（本部など）。
     * ルーティング先とは別に常に届く。
     *
     * @var list<string>
     */
    public const ALWAYS_NOTIFY = [
        'honbu@example.com',
    ];

    /**
     * ルーティングマップ。
     * フォームの store 値をキーに、届け先の店舗名とメールアドレスを管理する。
     * 店舗を追加するときはここだけ編集する。
     *
     * @var array<string, array{name: string, email: string}>
     */
    public const ROUTES = [
        'shibuya'  => ['name' => '渋谷店', 'email' => 'shibuya@example.com'],
        'shinjuku' => ['name' => '新宿店', 'email' => 'shinjuku@example.com'],
    ];
}
