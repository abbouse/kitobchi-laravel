<?php

return [
    'seo' => [
        'title' => 'Karyera — ochiq lavozimlar va onlayn ariza | Kitobchi',
        'desc' => 'Kitobchi jamoasiga qoʻshiling: ochiq lavozimlar, masofaviy ish, onlayn CV yuborish va ochiq murojaat. Kitob va kanselyariya marketpleysi.',
    ],

    'hero' => [
        'eyebrow' => 'Biz bilan',
        'heading_l1' => 'Kitobchi bilan',
        'heading_l2' => 'keyingi bosqichni',
        'heading_l3' => 'yozing.',
        'p1' => 'Mahalliy kitob va kanselyariya marketpleysi — doʻkonlar va mijozlarni birlashtiramiz. Ochiq lavozimlar boʻyicha onlayn ariza qoldiring.',
        'p2' => 'Vakansiya boʻlmasa ham ochiq murojaat yuborishingiz mumkin.',
        'open_roles' => 'Ochiq lavozimlar',
        'open_inq' => 'Ochiq murojaat',
    ],

    'manifesto' => [
        'h2_l1' => 'Sizning vazifangiz?',
        'h2_l2' => 'Yangi bob yozish.',
        'p1' => 'Kitobchi mobil ilova va marketpleys sifatida oʻsib bormoqda: sotuvchilar, katalog, yetkazib berish va foydalanuvchi tajribasi — har biri real insonlar uchun.',
        'p2' => 'Agar mahalliy e-tijorat va mahsulotni yaqinida qilish sizga yaqin boʻlsa, hozir qoʻshilish uchun yaxshi vaqt.',
    ],

    'roles' => [
        'heading' => 'Ochiq lavozimlar',
        'intro' => 'Har bir qatorni ochib, shu lavozim uchun ariza formasini toʻldiring (CV majburiy, Telegram username soʻraladi).',
        'empty' => 'Hozircha eʼlon qilingan vakansiya yoʻq. Pastdagi «Ochiq murojaat» orqali oʻz gʻoyangizni yuboring.',
    ],

    'form' => [
        'full_name' => 'Ism va familiya *',
        'email' => 'Email *',
        'telegram' => 'Telegram username *',
        'telegram_ph' => '@username yoki username',
        'cover' => 'Qisqa xat (ixtiyoriy)',
        'cover_ph' => 'Oʻzingiz va motivatsiyangiz…',
        'cv' => 'CV (PDF, DOC, DOCX) *',
        'submit_apply' => 'Arizani yuborish',
        'message' => 'Murojaat matni * (kamida 20 belgi)',
        'message_ph' => 'Kim boʻlishni xohlaysiz, qanday tajriba…',
        'attachment' => 'Ilova (ixtiyoriy, PDF/DOC/DOCX)',
        'submit_inq' => 'Murojaatni yuborish',
    ],

    'inquiry' => [
        'heading' => 'Boshqa yoʻnalish?',
        'intro' => 'Agar roʻyxatda lavozimingiz boʻlmasa, ochiq murojaat qoldiring — jamoamiz dashboard orqali koʻradi va siz bilan bogʻlanadi.',
    ],

    'ld' => [
        'default_place' => 'Oʻzbekiston',
    ],

    'flash' => [
        'application_sent' => 'Arizangiz qabul qilindi. Tez orada aloqaga chiqamiz.',
        'inquiry_sent' => 'Murojaatingiz qabul qilindi.',
    ],

    'attributes' => [
        'full_name' => 'ism va familiya',
        'email' => 'email',
        'telegram_username' => 'Telegram username',
        'cover_message' => 'qisqa xat',
        'cv' => 'CV fayli',
        'message' => 'murojaat matni',
        'attachment' => 'ilova fayli',
    ],

    'validation' => [
        'full_name.required' => 'Ism va familiyani kiriting.',
        'email.required' => 'Email manzilini kiriting.',
        'email.email' => 'Email formati notoʻgʻri.',
        'telegram_username.required' => 'Telegram username kiriting.',
        'cv.required' => 'CV faylini yuklang.',
        'cv.file' => 'CV fayli notoʻgʻri.',
        'cv.mimes' => 'CV faqat PDF, DOC yoki DOCX boʻlishi kerak.',
        'cv.max' => 'CV hajmi 10 MB dan oshmasligi kerak.',
        'message.required' => 'Murojaat matnini kiriting.',
        'message.min' => 'Murojaat kamida 20 belgidan iborat boʻlsin.',
        'attachment.mimes' => 'Ilova faqat PDF, DOC yoki DOCX boʻlishi kerak.',
        'attachment.max' => 'Ilova hajmi 10 MB dan oshmasligi kerak.',
    ],
];
