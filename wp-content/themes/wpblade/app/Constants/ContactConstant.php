<?php

namespace WpBlade\Constants;

final class ContactConstant
{
    // 末尾は `$` ではなく `\z` を使う（`$` は文字列末尾の改行1個の前でもマッチするため、
    // メールヘッダーに補間される name に末尾 \n が通り得る）
    public const REGEX_ALLOWED_NAME = '/^[ぁ-んーァ-ヶ一-龠々a-zA-Zａ-ｚＡ-Ｚ0-9０-９ 　]+\z/u';

    public const REGEX_ALLOWED_MESSAGE = '/^[ぁ-んァ-ヶ一-龠々a-zA-Z0-9ａ-ｚＡ-Ｚ０-９ .,、。！？「」『』【】（）()[\]{}<>＜＞《》～ー\-_=+*\/\\\\|:;"\'@#$%^&\n\x{1F300}-\x{1F6FF}\x{1F900}-\x{1F9FF}\x{1F1E6}-\x{1F1FF}\x{2600}-\x{26FF}\x{2700}-\x{27BF}]+$/u';

    public const REGEX_CONTAINS_JAPANESE = "/[ぁ-んァ-ヶ一-龠々]/u";

    public const ERROR_NAME_REQUIRED = "お名前は必須です。";
    public const ERROR_NAME_TOO_LONG = "お名前は100文字以内で入力してください。";
    public const ERROR_NAME_INVALID_CHARS = "お名前に使用できない文字が含まれています。";
    public const ERROR_EMAIL_REQUIRED = "メールアドレスは必須です。";
    public const ERROR_EMAIL_INVALID = "メールアドレスの形式が正しくありません。";
    public const ERROR_MESSAGE_REQUIRED = "お問い合わせ内容は必須です。";
    public const ERROR_MESSAGE_TOO_SHORT = "お問い合わせ内容は10文字以上入力してください。";
    public const ERROR_MESSAGE_TOO_LONG = "お問い合わせ内容は2000文字以内で入力してください。";
    public const ERROR_MESSAGE_INVALID_CHARS = "お問い合わせ内容に使用できない文字が含まれています。";
    public const ERROR_MESSAGE_NOT_JAPANESE = "お問い合わせ内容は日本語でご記入ください。";
    public const ERROR_MAIL_FAILED = "メールの送信に失敗しました。しばらく経ってからもう一度お試しください。";

    public const NAME_MAX_LENGTH = 100;
    public const MESSAGE_MIN_LENGTH = 10;
    public const MESSAGE_MAX_LENGTH = 2000;
}
