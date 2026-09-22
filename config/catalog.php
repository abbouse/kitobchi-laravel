<?php

return [
    /*
     * Mahsulot sahifasida mijozga ko'rsatiladigan "qoldiq" yozuvi uchun
     * yuqori chegara. Haqiqiy stock (branch_stocks yig'indisi) shu qiymatdan
     * katta bo'lsa, mijozga aniq raqam o'rniga "{cap}+" ko'rsatiladi —
     * raqobatchilar yoki botlar aniq ombor hajmini bilib olmasin, va katta
     * raqam ko'rinishi tasodifiy "kam qoldi" tuyg'usini yo'qotmasin.
     *
     * MUHIM: bu FAQAT ko'rinish (display) uchun — savat/checkout tekshiruvi
     * va "qancha dona sotib olish mumkin" cheklovi hamon HAQIQIY stockka
     * qarab ishlaydi (bu yerga tegishli emas).
     */
    'stock_display_cap' => max(1, (int) env('CATALOG_STOCK_DISPLAY_CAP', 10)),

    // GLOBAL KATALOG: yangi taklif (eski ilova, admin, import) yaratilganda kartaga avtomatik ulash
    'auto_link' => env('CATALOG_AUTO_LINK', true),

    /*
     * "O'chirgich": false bo'lsa mijoz ro'yxatlari avvalgidek ishlaydi — har
     * do'kon taklifi alohida karta bo'lib chiqadi (katalog, arizalar va
     * boshqaruv o'z ishini davom ettiraveradi). Deploydan keyin kutilmagan
     * holat bo'lsa, kodni qaytarmasdan .env orqali darhol orqaga qaytarish
     * mumkin: CATALOG_DEDUPE=false + php artisan config:cache
     */
    'dedupe' => env('CATALOG_DEDUPE', true),

    // Orqa muqovadagi shtrix-kodni server tomonda o'qish:
    //   zbarimg (apt install zbar-tools) → bo'lmasa OpenAI vision → bo'lmasa "o'qilmadi"
    'zbarimg_path' => env('CATALOG_ZBARIMG_PATH', 'zbarimg'),
    'vision_fallback' => env('CATALOG_VISION_ISBN', true),
];
