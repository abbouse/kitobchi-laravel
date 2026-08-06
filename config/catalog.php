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
];
