<?php

// Kitobchi マーケットプレイス翻訳 — ヘッダー、フッター、カタログ、カート、
// お気に入り、プロフィールなど。

return [
    // Header
    'catalogs' => 'カタログ',
    'cart' => 'カート',
    'favorites' => 'お気に入り',
    'login' => 'ログイン',
    'search_placeholder' => '商品を検索...',
    'search_placeholder_mobile' => 'Kitobchiで検索',
    'close' => '閉じる',

    // Kataloglar drawer
    'books' => '本',
    'stationery' => '文房具',
    'all_categories' => 'すべて',

    // Breadcrumb
    'breadcrumb_home' => 'ホーム',
    'breadcrumb_catalog' => 'カタログ',
    'back_to_home' => 'ホームに戻る',
    'back_to_catalog' => 'カタログに戻る',

    // Catalog / filters
    'sort' => '並び替え',
    'sort_popular' => '人気順',
    'sort_new' => '新着順',
    'sort_price_asc' => '価格が安い順',
    'sort_price_desc' => '価格が高い順',
    'price' => '価格',
    'price_from' => '下限',
    'price_to' => '上限',
    'apply' => '適用',
    'shops' => 'ショップ',
    'publishers' => '出版社',
    'clear_filters' => 'クリア',
    'products_count' => '商品 :count 件',
    'products_not_found' => '商品が見つかりません',
    'products_not_found_desc' => 'ご入力の条件に一致する商品はありませんでした。検索条件を変えるか、カタログ全体に戻ってください。',
    'all_catalog' => 'すべてのカタログ',
    'new_products' => '新着商品',
    'popular_products' => '人気商品',
    'search_results' => '「:query」の検索結果',
    'currency' => 'スム',
    'instead_of_price' => ':price の代わりに',

    // Cart
    'cart_title' => 'カート',
    'cart_empty_title' => 'カートは空です',
    'cart_empty_desc' => 'お気に入りの商品をカートに追加すると、数クリックで購入を完了できます',
    'go_to_catalog' => 'カタログへ',
    'select_all' => 'すべての商品を選択',
    'selected_count' => '商品 :count 件を選択中',
    'price_label' => '価格:',
    'promo_code' => 'プロモコード',
    'order_summary' => 'ご注文内容',
    'products_label' => '商品（:count）:',
    'delivery' => '配送:',
    'delivery_by_region' => '地域により異なります',
    'products_colon' => '商品:',
    'delivery_note' => '配送料とプロモコードの割引は次のステップで計算されます',
    'continue_purchase' => '購入を続ける',

    // Favorites
    'favorites_title' => 'お気に入り',
    'favorites_empty_title' => 'お気に入りリストは空です',
    'favorites_empty_desc' => '気に入った商品を保存するには、アイコンをタップしてください',

    // Footer
    'footer_general' => '一般',
    'footer_about' => '会社概要',
    'footer_contact' => 'お問い合わせ',
    'footer_careers' => '採用情報',
    'footer_catalogs' => 'カタログ',
    'footer_all_books' => 'すべての本',
    'footer_stationery' => '文房具',
    'footer_see_all' => 'すべて見る',
    'footer_customer_service' => 'カスタマーサービス',
    'footer_delivery' => '配送',
    'footer_payments' => 'お支払い',
    'footer_privacy' => 'プライバシーポリシー',
    'footer_social' => 'ソーシャルメディア',
    'footer_description' => 'Kitobchiはウズベキスタン最大級の本・文房具オンラインマーケットプレイスです。高品質でお手頃な商品を迅速にお届けします。',
    'footer_rights' => 'All rights reserved.',
    'footer_privacy_short' => 'プライバシー',
    'footer_terms' => '利用規約',

    // Mobile nav
    'nav_home' => 'ホーム',
    'nav_catalog' => 'カタログ',
    'nav_cart' => 'カート',
    'nav_profile' => 'プロフィール',
    'nav_login' => 'ログイン',

    // Auth modal
    'auth_title' => 'ログイン',
    'auth_desc' => 'ご注文の確認やお買い物のために、電話番号を入力してください。',
    'phone_number' => '電話番号',
    'send_code' => 'コードを送信 →',
    'sending' => '送信中...',
    'enter_code_title' => 'コードを入力',
    'enter_code_desc' => 'SMSで送信された6桁のコードを入力してください。',
    'verify' => '確認してログイン',
    'verifying' => '確認中...',
    'change_number' => '← 番号を変更',
    'phone_incomplete' => '電話番号をすべて入力してください。',
    'code_incomplete' => '6桁のコードを入力してください。',
    'generic_error' => 'エラーが発生しました。',
    'connection_error' => '接続エラーです。',
    'code_wrong' => 'コードが正しくありません。',
    'code_sent_to' => '{phone} に送信された確認コードを入力してください。',

    // Toasts
    'toast_added_to_cart' => '商品をカートに追加しました！',
    'toast_go_to_cart' => 'カートへ進む →',

    // Product page CTA
    'place_order' => '注文する',
    'add_to_cart' => 'カートに追加',

    // Profile
    'profile_title' => 'プロフィール',
    'profile_orders' => '注文履歴',
    'profile_info' => '登録情報',
    'profile_logout' => 'ログアウト',
    'profile_user' => 'ユーザー',
    'profile_orders_title' => 'ご注文一覧',
    'profile_no_orders' => 'まだ注文はありません',
    'profile_no_orders_desc' => 'カタログからお好きな本をお選びいただけます。',
    'profile_order_number' => '注文 #:number',
    'profile_status_paid' => '支払い完了',
    'profile_status_pending' => '保留中',
    'profile_status_accepted' => '受付済み',
    'profile_address' => '住所:',
    'profile_address_unset' => '未設定',
    'profile_total' => '合計:',
    'profile_full_name' => '氏名',
    'profile_not_entered' => '未入力',
    'profile_phone' => '電話番号',
    'profile_addresses' => '登録住所',
    'profile_add_address' => '新しい住所を追加',
    'profile_address_placeholder' => '地図から選択するか、直接入力してください',
    'profile_address_full_name_label' => '住所の名称',
    'profile_save_address' => '住所を保存',
    'profile_set_main' => 'メインに設定',
    'profile_delete' => '削除',
    'profile_main' => 'メイン',
    'profile_confirm_delete_address' => 'この住所を削除しますか？',
    'profile_map_select_alert' => '地図上で住所を選択してください！',
];
