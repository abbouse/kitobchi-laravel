<?php

// Kitobchi marketplace (do'kon) qismi uchun tarjimalar — header, footer,
// katalog, savat, sevimlilar, profil va h.k. Standart til doim shu fayl
// (uz) — App\Http\Middleware\SetLandingLocale kc_locale cookie'si bo'lmasa
// har doim 'uz'ga tushadi.

return [
    // Header
    'catalogs' => 'Kataloglar',
    'cart' => 'Savatcha',
    'favorites' => 'Sevimlilar',
    'login' => 'Kirish',
    'search_placeholder' => 'Mahsulotni izlash...',
    'search_placeholder_mobile' => "Kitobchi'da izlash",
    'close' => 'Yopish',

    // Kataloglar drawer
    'books' => 'Kitoblar',
    'stationery' => 'Kanselyariya',
    'all_categories' => 'Barchasi',

    // Breadcrumb
    'breadcrumb_home' => 'Asosiy',
    'breadcrumb_catalog' => 'Katalog',
    'back_to_home' => 'Bosh sahifaga qaytish',
    'back_to_catalog' => 'Katalogga qaytish',

    // Catalog / filters
    'sort' => 'Saralash',
    'sort_popular' => 'Ommabop',
    'sort_new' => 'Yangi',
    'sort_price_asc' => 'Arzon narx',
    'sort_price_desc' => 'Qimmat narx',
    'price' => 'Narx',
    'price_from' => 'Dan',
    'price_to' => 'Gacha',
    'apply' => "Qo'llash",
    'shops' => "Do'konlar",
    'publishers' => 'Nashriyotlar',
    'authors' => 'Mualliflar',
    'cover_type' => 'Muqova',
    'cover_type_soft' => 'Yumshoq muqova',
    'cover_type_hard' => 'Qattiq muqova',
    'material' => 'Material',
    'clear_filters' => 'Tozalash',
    'products_count' => ':count ta mahsulot',
    'products_not_found' => 'Mahsulotlar topilmadi',
    'products_not_found_desc' => "Kiritilgan so'rov bo'yicha hech narsa topilmadi. Qidiruvni o'zgartiring yoki barcha katalogga qaytish.",
    'all_catalog' => 'Barcha katalog',
    'new_products' => 'Yangi kelgan mahsulotlar',
    'popular_products' => 'Ommabop mahsulotlar',
    'search_results' => ':query bo\'yicha qidiruv',
    'currency' => "so'm",
    'instead_of_price' => ':price o\'rniga',

    // Cart
    'cart_title' => 'Savatcha',
    'cart_empty_title' => "Savatingiz bo'sh",
    'cart_empty_desc' => "Yoqqan mahsulotlaringizni savatga qo'shing — xaridni shu yerdan bir necha bosishda yakunlaysiz",
    'go_to_catalog' => "Katalogga o'tish",
    'select_all' => 'Barcha mahsulotlarni tanlash',
    'selected_count' => ':count ta mahsulot tanlandi',
    'price_label' => 'Narxi:',
    'promo_code' => 'Promokod',
    'order_summary' => 'Buyurtmangiz',
    'products_label' => 'Mahsulotlar (:count):',
    'delivery' => 'Yetkazib berish:',
    'delivery_by_region' => 'Hududga qarab',
    'products_colon' => 'Mahsulotlar:',
    'cart_total_label' => 'Jami:',
    'delivery_note' => 'Yetkazib berish narxi va promokod chegirmasi keyingi bosqichda hisoblanadi',
    'continue_purchase' => 'Xaridni davom ettirish',

    // Favorites
    'favorites_title' => 'Sevimlilar',
    'favorites_empty_title' => "Sevimlilar ro'yxati bo'sh",
    'favorites_empty_desc' => "Yoqqan mahsulotlaringizni saqlab qo'yish uchun ularning ustidagi",

    // Footer
    'footer_general' => 'Umumiy',
    'footer_about' => 'Biz haqimizda',
    'footer_contact' => 'Aloqa',
    'footer_careers' => 'Karyera',
    'footer_catalogs' => 'Kataloglar',
    'footer_all_books' => 'Barcha kitoblar',
    'footer_stationery' => 'Kanselyariya',
    'footer_see_all' => "Hammasini ko'rish",
    'footer_customer_service' => 'Mijozlar xizmati',
    'footer_delivery' => 'Yetkazib berish',
    'footer_payments' => "To'lovlar",
    'footer_privacy' => 'Maxfiylik siyosati',
    'footer_social' => 'Ijtimoiy tarmoqlar',
    'footer_description' => "Kitobchi — O'zbekistondagi eng ulkan onlayn kitoblar va kanselyariya marketpleysi. Foydalanuvchilarga sifatli va hamyonbop mahsulotlarni tezda yetkazamiz.",
    'footer_rights' => 'Barcha huquqlar himoyalangan.',
    'footer_privacy_short' => 'Maxfiylik',
    'footer_terms' => 'Shartlar',

    // Mobile nav
    'nav_home' => 'Bosh sahifa',
    'nav_catalog' => 'Katalog',
    'nav_cart' => 'Savatcha',
    'nav_profile' => 'Profil',
    'nav_login' => 'Kirish',

    // Auth modal
    'auth_title' => 'Tizimga kirish',
    'auth_desc' => 'Buyurtmalaringizni kuzatish va xarid qilish uchun telefon raqamingizni kiriting.',
    'phone_number' => 'Telefon raqami',
    'send_code' => "Kodni yuborish →",
    'sending' => 'Yuborilmoqda...',
    'enter_code_title' => 'Kodni kiriting',
    'enter_code_desc' => 'SMS orqali yuborilgan 6 xonali kodni kiriting.',
    'verify' => 'Tasdiqlash va Kirish',
    'verifying' => 'Tekshirilmoqda...',
    'change_number' => "← Raqamni o'zgartirish",
    'phone_incomplete' => "Telefon raqamni to'liq kiriting.",
    'code_incomplete' => '6 xonali kodni kiriting.',
    'generic_error' => 'Xatolik yuz berdi.',
    'connection_error' => 'Ulanishda xatolik.',
    'code_wrong' => "Kod noto'g'ri.",
    'code_sent_to' => '{phone} raqamiga yuborilgan tasdiqlash kodini kiriting.',

    // Toasts
    'toast_added_to_cart' => "Mahsulot savatchaga qo'shildi!",
    'toast_go_to_cart' => "Savatchaga o'tish →",

    // Product page CTA
    'place_order' => 'Buyurtma berish',
    'add_to_cart' => "Savatchaga qo'shish",

    // Product reviews (BookClub postlari — piyolamarket uslubida sharh sifatida)
    'reviews_title' => 'Xaridorlar sharhlari',
    'reviews_helpful' => ':count kishi foydali deb topdi',
    'reviews_comments_count' => ':count ta izoh',

    // Seller/shop info (product sahifasida)
    'seller_shop' => "Do'kon",

    // AI tavsiya (item.dart'dagi kabi, vektor-asosidagi o'xshashlik)
    'ai_recommendations_title' => 'AI tavsiya',
    'ai_recommendations_desc' => "Sun'iy intellekt shu mahsulot bilan o'xshash bo'lgan variantlarni tanladi",
    'similar_products_title' => "O'xshash mahsulotlar",

    // Mahsulot haqida (tavsif bo'limi, product sahifasida)
    'about_product_title' => 'Mahsulot haqida',
    'about_product_expand' => "To'liq o'qish",
    'about_product_collapse' => 'Qisqartirish',

    // Profile
    'profile_title' => 'Profil',
    'profile_orders' => 'Buyurtmalarim',
    'profile_info' => "Ma'lumotlarim",
    'profile_logout' => 'Hisobdan chiqish',
    'profile_user' => 'Foydalanuvchi',
    'profile_orders_title' => 'Buyurtmalaringiz',
    'profile_no_orders' => "Sizda hozircha buyurtmalar yo'q",
    'profile_no_orders_desc' => "Katalogdan o'zingizga yoqqan kitoblarni xarid qilishingiz mumkin.",
    'profile_order_number' => 'Buyurtma #:number',
    'profile_status_paid' => "Muvaffaqiyatli to'langan",
    'profile_status_pending' => 'Kutilmoqda',
    'profile_status_accepted' => 'Qabul qilindi',
    'profile_address' => 'Manzil:',
    'profile_address_unset' => 'Belgilanmagan',
    'profile_total' => 'Jami summa:',
    'profile_full_name' => "To'liq ism",
    'profile_not_entered' => 'Kiritilmagan',
    'profile_phone' => 'Telefon raqam',
    'profile_addresses' => 'Mening manzillarim',
    'profile_add_address' => "Yangi manzil qo'shish",
    'profile_address_placeholder' => "Xaritadan tanlang yoki o'zingiz kiriting",
    'profile_address_full_name_label' => "Manzil to'liq nomi",
    'profile_save_address' => 'Manzilni saqlash',
    'profile_set_main' => 'Asosiy qilish',
    'profile_delete' => "O'chirish",
    'profile_main' => 'Asosiy',
    'profile_confirm_delete_address' => "Manzilni o'chirmoqchimisiz?",
    'profile_map_select_alert' => "Iltimos xaritadan manzilni belgilang!",

    // Profile — Sharhlarim (BookClub postlari, piyolamarket.uz'dagi
    // "Sharhlarim" bo'limiga o'xshab)
    'profile_reviews' => 'Sharhlarim',
    'profile_no_reviews' => "Sizda hozircha sharhlar yo'q",
    'profile_no_reviews_desc' => "Sotib olgan mahsulotlaringiz haqida fikr bildirsangiz, shu yerda ko'rinadi.",
    'profile_addresses_empty_title' => 'Saqlangan manzillar mavjud emas',
    'profile_addresses_empty_desc' => 'Yetkazib berish manzilini qo\'shing',
];
