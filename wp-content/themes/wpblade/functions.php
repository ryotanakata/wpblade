<?php

/**
 * wpblade テーマのエントリポイント。
 *
 * @package WpBlade
 */

// Composer オートローダー
require_once __DIR__ . '/vendor/autoload.php';

// 機能登録
require_once __DIR__ . '/includes/functions/theme-support.php';
require_once __DIR__ . '/includes/functions/post-types.php';
require_once __DIR__ . '/includes/functions/assets.php';
require_once __DIR__ . '/includes/functions/contact.php';
require_once __DIR__ . '/includes/functions/mail.php';