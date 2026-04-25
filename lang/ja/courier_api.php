<?php

return [
    // ── Auth ────────────────────────────────────────────────
    'login_success'         => 'ログインに成功しました。',
    'invalid_credentials'   => '電話番号またはパスワードが正しくありません。',
    'account_inactive'      => 'アカウントが有効ではありません。管理者にお問い合わせください。',
    'account_blocked'       => 'アカウントがブロックされています。管理者にお問い合わせください。',
    'logout_success'        => 'ログアウトしました。',
    'phone_not_found'       => '電話番号が見つかりません。',
    'phone_not_uz'          => 'ウズベキスタンの電話番号 (+998) のみ受け付けています。',
    'phone_invalid'         => '電話番号の形式が正しくありません。',
    'reset_too_many'        => 'パスワードリセット回数の上限に達しました。後で再試行してください。',
    'reset_sms_sent'        => '新しいパスワードを SMS で送信しました。',
    'reset_sms_failed'      => 'パスワードのリセットに失敗しました。後で再試行してください。',
    'request_already_sent'  => 'すでに登録済み、または現在アクティブな配達員です。',
    'request_updated'       => '申請内容を更新しました。',
    'request_submitted'     => '申請を送信しました。管理者がまもなく確認します。',

    // ── Orders ──────────────────────────────────────────────
    'order_not_found'       => '注文が見つかりません。',
    'order_already_taken'   => 'この注文はすでに他の配達員に受け付けられています。',
    'order_confirmed'       => '注文を受け付けました。',
    'order_delivered'       => '注文を配達完了しました。',
    'order_invalid_qr'      => 'QR コードが無効または期限切れです。',

    // ── Warnings & Block ────────────────────────────────────
    'warning_title'         => '警告が届きました',
    'warning_count'         => '3 回の警告でアカウントがブロックされます。',
    'blocked_by_warnings'   => '3 回の警告を受け、アカウントがブロックされました。',
    'unblocked'             => 'アカウントのブロックが解除されました。',

    // ── Misc ────────────────────────────────────────────────
    'unauthorized'          => '認証が必要です。',
    'fetch_failed'          => 'データの取得に失敗しました。',
    'validation_failed'     => '送信されたデータに誤りがあります。',
    'server_error'          => 'サーバーエラーです。後で再試行してください。',

    // ── ボーナス制度 (Phase 3) ─────────────────────────────
    'bonus_threshold_push_title' => '💰 高ボーナスの注文！',
    'bonus_threshold_push_body'  => '注文のボーナスが :amount スムに達しました。最初に受け付けてください！',
    'sla_warning_push_title'     => '⏰ 配達まで残り 5 分',
    'sla_warning_push_body'      => '注文 #:id の配達期限が迫っています。遅れるとボーナスが減ります。',
    'customer_delay_marked'      => 'お客様と連絡が取れません — タイマーを一時停止しました。',
    'customer_delay_resumed'     => '一時停止を解除しました。タイマーを再開します。',
    'customer_delay_invalid'     => 'この注文では一時停止は利用できません。',
];
