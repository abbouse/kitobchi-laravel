<?php

return [
    'seo' => [
        'title' => '採用 — 募集中の職種とオンライン応募 | Kitobchi',
        'desc' => 'Kitobchi チームに参加しませんか：募集中のポジション、リモート可、オンラインでの履歴書送付、オープン応募。書籍と文房具のマーケットプレイス。',
    ],

    'hero' => [
        'eyebrow' => '私たちと',
        'heading_l1' => 'Kitobchi で、',
        'heading_l2' => '次の章を',
        'heading_l3' => '書きましょう。',
        'p1' => '地域の書籍・文房具マーケットプレイス — 店舗とお客様をつなぎます。募集中の職種にはオンラインで応募できます。',
        'p2' => '該当する求人がなくても、オープン応募を送ることができます。',
        'open_roles' => '募集中の職種',
        'open_inq' => 'オープン応募',
    ],

    'manifesto' => [
        'h2_l1' => 'あなたのミッションは？',
        'h2_l2' => '新しい章を書くこと。',
        'p1' => 'Kitobchi はモバイルアプリとマーケットプレイスとして成長しています：販売者、カタログ、配送、ユーザー体験 — すべて実在の人々のために。',
        'p2' => '地域のECや「身近なモノづくり」に共感できるなら、今が参加の好機です。',
    ],

    'roles' => [
        'heading' => '募集中の職種',
        'intro' => '各行を開いて、その職種用の応募フォームに入力してください（履歴書は必須、Telegram のユーザー名が必要です）。',
        'empty' => '現在公開されている求人はありません。下の「オープン応募」からご提案をお送りください。',
    ],

    'form' => [
        'full_name' => '氏名 *',
        'email' => 'メールアドレス *',
        'telegram' => 'Telegram ユーザー名 *',
        'telegram_ph' => '@username または username',
        'cover' => '短い志望動機（任意）',
        'cover_ph' => '自己紹介と志望動機…',
        'cv' => '履歴書（PDF, DOC, DOCX）*',
        'submit_apply' => '応募を送る',
        'message' => 'メッセージ本文 *（20文字以上）',
        'message_ph' => '希望する役割、経験など…',
        'attachment' => '添付ファイル（任意、PDF/DOC/DOCX）',
        'submit_inq' => 'メッセージを送る',
    ],

    'inquiry' => [
        'heading' => '別の方向性？',
        'intro' => 'リストにない場合はオープン応募を送ってください。チームが管理画面で確認し、ご連絡します。',
    ],

    'ld' => [
        'default_place' => 'ウズベキスタン',
    ],

    'flash' => [
        'application_sent' => '応募を受け付けました。追ってご連絡します。',
        'inquiry_sent' => 'メッセージを受け付けました。',
    ],

    'attributes' => [
        'full_name' => '氏名',
        'email' => 'メールアドレス',
        'telegram_username' => 'Telegram ユーザー名',
        'cover_message' => '志望動機',
        'cv' => '履歴書ファイル',
        'message' => 'メッセージ',
        'attachment' => '添付ファイル',
    ],

    'validation' => [
        'full_name.required' => '氏名を入力してください。',
        'email.required' => 'メールアドレスを入力してください。',
        'email.email' => 'メールアドレスの形式が正しくありません。',
        'telegram_username.required' => 'Telegram のユーザー名を入力してください。',
        'cv.required' => '履歴書をアップロードしてください。',
        'cv.file' => '履歴書ファイルが無効です。',
        'cv.mimes' => '履歴書は PDF、DOC、または DOCX 形式にしてください。',
        'cv.max' => '履歴書は 10 MB 以下にしてください。',
        'message.required' => 'メッセージを入力してください。',
        'message.min' => 'メッセージは 20 文字以上にしてください。',
        'attachment.mimes' => '添付は PDF、DOC、または DOCX 形式にしてください。',
        'attachment.max' => '添付は 10 MB 以下にしてください。',
    ],
];
