<?php

return [
    // ── Auth ────────────────────────────────────────────────
    'login_success'         => 'Tizimga muvaffaqiyatli kirdingiz!',
    'invalid_credentials'   => 'Telefon raqam yoki parol noto\'g\'ri.',
    'account_inactive'      => 'Sizning hisobingiz faol emas. Iltimos, administrator bilan bog\'laning.',
    'account_blocked'       => 'Hisobingiz bloklangan. Administrator bilan bog\'laning.',
    'logout_success'        => 'Siz tizimdan chiqdingiz.',
    'phone_not_found'       => 'Telefon raqami topilmadi.',
    'phone_not_uz'          => 'Faqat O\'zbekiston raqamlari (+998) qabul qilinadi.',
    'phone_invalid'         => 'Telefon raqami noto\'g\'ri formatda.',
    'reset_too_many'        => 'Parol tiklash limiti tugadi. Keyinroq qayta urinib ko\'ring.',
    'reset_sms_sent'        => 'Yangi parol telefon raqamiga SMS tarzida yuborildi.',
    'reset_sms_failed'      => 'Parolni tiklashda xatolik yuz berdi. Iltimos, keyinroq qayta urinib ko\'ring.',
    'request_already_sent'  => 'Siz allaqachon ro\'yxatdan o\'tgansiz yoki faol kuryersiz.',
    'request_updated'       => 'So\'rov ma\'lumotlari yangilandi.',
    'request_submitted'     => 'So\'rov muvaffaqiyatli yuborildi. Tez orada admin tomonidan ko\'rib chiqiladi.',

    // ── Orders ──────────────────────────────────────────────
    'order_not_found'       => 'Buyurtma topilmadi.',
    'order_already_taken'   => 'Bu buyurtma allaqachon boshqa kuryer tomonidan qabul qilingan.',
    'order_confirmed'       => 'Buyurtma muvaffaqiyatli qabul qilindi.',
    'order_delivered'       => 'Buyurtma yetkazib berildi.',
    'order_invalid_qr'      => 'QR-kod noto\'g\'ri yoki muddati o\'tgan.',

    // ── Warnings & Block ────────────────────────────────────
    'warning_title'         => 'Sizga ogohlantirish berildi',
    'warning_count'         => '3 tagacha ogohlantirilgandan keyin akauntingiz bloklanadi.',
    'blocked_by_warnings'   => 'Sizga 3 marta ogohlantirish berildi va akauntingiz bloklandi.',
    'unblocked'             => 'Akauntingiz blokdan chiqarildi.',

    // ── Misc ────────────────────────────────────────────────
    'unauthorized'          => 'Avtorizatsiya talab qilinadi.',
    'fetch_failed'          => 'Ma\'lumotlarni olishda xatolik yuz berdi.',
    'validation_failed'     => 'Yuborilgan ma\'lumotlarda xatolik bor.',
    'server_error'          => 'Server xatosi. Iltimos, keyinroq qayta urinib ko\'ring.',

    // ── Bonus tizimi (Phase 3) ──────────────────────────────
    'bonus_threshold_push_title' => '💰 Yuqori bonusli buyurtma!',
    'bonus_threshold_push_body'  => 'Hozir buyurtma uchun bonus :amount so\'mga yetdi. Birinchi bo\'lib qabul qiling!',
    'sla_warning_push_title'     => '⏰ Yetkazishga 5 daqiqa qoldi',
    'sla_warning_push_body'      => 'Buyurtma #:id muddati tez orada tugaydi. Kechiksangiz bonus kamayadi.',
    'customer_delay_marked'      => 'Mijoz javob bermayapti — hisoblagich pauza qilindi.',
    'customer_delay_resumed'     => 'Pauza tugatildi, hisoblagich davom etyapti.',
    'customer_delay_invalid'     => 'Bu buyurtma uchun pauza qo\'llanmaydi.',
];
