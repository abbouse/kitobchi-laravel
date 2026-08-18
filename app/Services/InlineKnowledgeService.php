<?php

namespace App\Services;

use SergiX44\Nutgram\Telegram\Types\Inline\InlineQueryResultArticle;
use SergiX44\Nutgram\Telegram\Types\Input\InputTextMessageContent;
use SergiX44\Nutgram\Telegram\Types\Keyboard\InlineKeyboardButton;
use SergiX44\Nutgram\Telegram\Types\Keyboard\InlineKeyboardMarkup;

class InlineKnowledgeService
{
    /**
     * Inline query qidiruvini amalga oshirish (4 tilda: UZ, RU, EN, JA).
     */
    public static function search(string $query = ""): array
    {
        $query = trim(mb_strtolower($query));
        $all = self::getAllArticles();
        $results = [];

        // Til bo\x27yicha filtr (masalan "uz", "ru", "en", "ja")
        $langFilter = null;
        if (in_array($query, ["uz", "o\x27zbek", "ozbek", "uzbek"], true)) {
            $langFilter = "uz";
            $query = "";
        } elseif (in_array($query, ["ru", "rus", "рус", "русский"], true)) {
            $langFilter = "ru";
            $query = "";
        } elseif (in_array($query, ["en", "eng", "english"], true)) {
            $langFilter = "en";
            $query = "";
        } elseif (in_array($query, ["ja", "jap", "japan", "japanese", "япон", "日本語"], true)) {
            $langFilter = "ja";
            $query = "";
        }

        foreach ($all as $key => $item) {
            if ($langFilter && ($item["lang"] ?? "") !== $langFilter) {
                continue;
            }

            if ($query !== "") {
                $titleMatch    = str_contains(mb_strtolower($item["title"]), $query);
                $summaryMatch  = str_contains(mb_strtolower($item["summary"] ?? ""), $query);
                $textMatch     = str_contains(mb_strtolower($item["text"]), $query);
                $keywordsMatch = false;

                if (!empty($item["keywords"])) {
                    foreach ($item["keywords"] as $kw) {
                        if (str_contains(mb_strtolower($kw), $query)) {
                            $keywordsMatch = true;
                            break;
                        }
                    }
                }

                if (!$titleMatch && !$summaryMatch && !$textMatch && !$keywordsMatch) {
                    continue;
                }
            }

            $keyboard = null;
            if (!empty($item["button_url"]) && !empty($item["button_text"])) {
                $keyboard = InlineKeyboardMarkup::make()->addRow(
                    InlineKeyboardButton::make($item["button_text"], url: $item["button_url"])
                );
            }

            $results[] = InlineQueryResultArticle::make(
                id: (string) $key,
                title: $item["title"],
                input_message_content: InputTextMessageContent::make(
                    message_text: $item["text"],
                    parse_mode: "HTML",
                    disable_web_page_preview: false
                ),
                reply_markup: $keyboard,
                description: $item["summary"] ?? mb_substr(strip_tags($item["text"]), 0, 80)
            );
        }

        return $results;
    }

    /**
     * Barcha 4 tildagi shablon va bilimlar bazasi.
     */
    public static function getAllArticles(): array
    {
        return array_merge(
            self::getUzbekArticles(),
            self::getRussianArticles(),
            self::getEnglishArticles(),
            self::getJapaneseArticles()
        );
    }

    /**
     * O\x27ZBEKCHA MAQOLALAR
     */
    private static function getUzbekArticles(): array
    {
        return [
            "uz_order_tracking" => [
                "lang"     => "uz",
                "title"    => "🇺🇿 📦 Buyurtma holatini tekshirish",
                "summary"  => "Mijozdan buyurtma raqamini so\x27rash va tekshirish",
                "keywords" => ["buyurtma", "qayerda", "holati", "tracking", "zakaz", "tekshirish"],
                "text"     => "📦 <b>Buyurtmangiz holatini tekshirish:</b>\n\nBuyurtmangiz holatini aniqlashimiz uchun, iltimos, <b>buyurtma raqamini</b> (masalan: <code>#10452</code>) yoki ilovada ro\x27yxatdan o\x27tgan <b>telefon raqamingizni</b> yozib yuboring.\n\nOperatorlarimiz darhol tekshirib, batafsil ma\x27lumot berishadi!",
                "button_text" => "🌐 Saytda tekshirish",
                "button_url"  => "https://kitobchi.com",
            ],
            "uz_order_cancel" => [
                "lang"     => "uz",
                "title"    => "🇺🇿 ❌ Buyurtmani bekor qilish",
                "summary"  => "Buyurtmani bekor qilish shartlari va tartibi",
                "keywords" => ["bekor", "otmena", "cancel", "qaytarish", "otmen"],
                "text"     => "❌ <b>Buyurtmani bekor qilish:</b>\n\nAgar buyurtmangiz hali kurerga yoki yetkazib berish xizmatiga topshirilmagan bo\x27lsa, uni bekor qilishimiz mumkin.\n\nIltimos, buyurtma raqamingizni yuboring, uni tizimdan darhol to\x27xtatamiz.",
            ],
            "uz_delivery_terms" => [
                "lang"     => "uz",
                "title"    => "🇺🇿 🚚 Yetkazib berish muddatlari",
                "summary"  => "Toshkent shahri va viloyatlarga yetkazish muddatlari",
                "keywords" => ["yetkazish", "dostavka", "muddat", "vaqt", "toshkent", "viloyat"],
                "text"     => "🚚 <b>Yetkazib berish muddatlari:</b>\n\n• <b>Toshkent shahri bo\x27yicha:</b> 24 soat ichida eshikkacha yetkaziladi.\n• <b>Viloyat va tuman markazlariga:</b> 2-3 ish kuni ichida ishonchli kurerlik/pochta orqali yetkaziladi.\n\nBuyurtma yo\x27lga chiqqanda sizga SMS-xabarnoma yuboriladi.",
            ],
            "uz_delivery_price" => [
                "lang"     => "uz",
                "title"    => "🇺🇿 💰 Yetkazib berish narxi",
                "summary"  => "Yetkazish to\x27lovi va bepul yetkazib berish shartlari",
                "keywords" => ["yetkazish narxi", "narx", "dostavka narxi", "bepul", "free"],
                "text"     => "💰 <b>Yetkazib berish narxi haqida:</b>\n\nYetkazib berish narxi siz ko\x27rsatgan manzil va buyurtma hajmiga qarab savatchada avtomatik hisoblanadi.\n\n🎁 Shuningdek, belgilangan summadan yuqori xaridlar uchun <b>bepul yetkazib berish</b> aksiyalari amal qiladi!",
            ],
            "uz_payment_card" => [
                "lang"     => "uz",
                "title"    => "🇺🇿 💳 To\x27lov usullari (Uzcard/Humo/Visa)",
                "summary"  => "Karta orqali xavfsiz onlayn to\x27lov qilish",
                "keywords" => ["tolov", "to\x27lov", "karta", "uzcard", "humo", "visa", "payme", "click"],
                "text"     => "💳 <b>To\x27lov usullari:</b>\n\nKitobchi ilovasida va saytida quyidagi usullar orqali to\x27lov qilishingiz mumkin:\n• <b>Uzcard</b> va <b>Humo</b> milliy kartalari\n• <b>Visa</b> va <b>Mastercard</b> xalqaro kartalari\n• Payme va Click tizimlari orqali to\x27g\x27ridan-to\x27g\x27ri to\x27lov\n\n🔒 Barcha tranzaksiyalar 100% xavfsiz va himoyalangan.",
            ],
            "uz_payment_problem" => [
                "lang"     => "uz",
                "title"    => "🇺🇿 ⚠️ To\x27lovdagi muammolar",
                "summary"  => "Pul yechilib buyurtma ochilmaganda nima qilish kerak",
                "keywords" => ["tolov otmadi", "pul yechildi", "xatolik", "chek", "muammo"],
                "text"     => "⚠️ <b>To\x27lov amalga oshmay qolgan bo\x27lsa:</b>\n\n1. Kartangizda SMS-xabarnoma (3DS kod) yoqilganligini tekshiring.\n2. Balansda mablag\x27 yetarli ekanligini aniqlang.\n3. Agar kartadan pul yechilib, buyurtma faollashmagan bo\x27lsa, to\x27lov <b>chekining skrinshotini</b> shu yerga yuboring — hisobchilarimiz tekshirib, darhol hal qilib berishadi!",
            ],
            "uz_app_download" => [
                "lang"     => "uz",
                "title"    => "🇺🇿 📲 Mobil ilovani yuklab olish",
                "summary"  => "iOS va Android rasmiy yuklab olish havolalari",
                "keywords" => ["ilova", "app", "yuklash", "ios", "android", "play market", "app store"],
                "text"     => "📲 <b>Kitobchi rasmiy mobil ilovasi:</b>\n\nO\x27zbekistondagi eng katta kitob do\x27koni va audio kitoblar platformasi!\n\n🍏 <a href=\"https://apps.apple.com/uz/app/kitobchi/id6753818078\">App Store orqali yuklash (iPhone / iPad)</a>\n🤖 <a href=\"https://play.google.com/store/apps/details?id=com.kitobchi.kitobchi\">Google Play orqali yuklash (Android)</a>\n🌐 <a href=\"https://kitobchi.com\">Rasmiy veb-sayt: kitobchi.com</a>",
                "button_text" => "📲 Ilovani yuklash",
                "button_url"  => "https://kitobchi.com",
            ],
            "uz_cashback" => [
                "lang"     => "uz",
                "title"    => "🇺🇿 🎁 Keshbek tizimi",
                "summary"  => "Har bir xariddan keshbek to\x27plash va ishlatish",
                "keywords" => ["keshbek", "bonus", "ball", "cashback", "chegirma"],
                "text"     => "🎁 <b>Kitobchi Keshbek tizimi:</b>\n\n• Har bir amalga oshirgan kitob xaridingizdan shaxsiy hisobingizga keshbek qaytadi.\n• Yig\x27ilgan keshbeklarni keyingi kitob xaridlaringizda 100% chegirma sifatida ishlatishingiz mumkin.\n• Keshbek balansini ilovaning <b>Profil</b> bo\x27limida kuzatib borasiz.",
            ],
            "uz_work_hours" => [
                "lang"     => "uz",
                "title"    => "🇺🇿 ⏰ Ish tartibi va aloqa",
                "summary"  => "Qo\x27llab-quvvatlash xizmati ish vaqti",
                "keywords" => ["ish vaqti", "ish tartibi", "telefon", "manzil", "kontakt"],
                "text"     => "⏰ <b>Ish tartibimiz:</b>\n\n• <b>Qo\x27llab-quvvatlash xizmati:</b> Har kuni 09:00 dan 22:00 gacha\n• <b>Mobil ilova va Sayt:</b> 24/7 (istalgan vaqtda buyurtma berish mumkin)\n• <b>Yetkazib berish xizmati:</b> Dushanba - Shanba kunlari faoliyat yuritadi.",
            ],
            "uz_return_policy" => [
                "lang"     => "uz",
                "title"    => "🇺🇿 🔄 Kitobni qaytarish va almashtirish",
                "summary"  => "Nuqsonli yoki noto\x27g\x27ri kitoblarni almashtirish kafolati",
                "keywords" => ["qaytarish", "almashtirish", "brak", "nuqson", "vozvrat"],
                "text"     => "🔄 <b>Qaytarish va almashtirish qoidalari:</b>\n\nAgar kitobda bosma nuqson (brak), sahifalar yetishmasligi yoki shikastlanish aniqlansa, biz uni <b>mutlaqo bepul</b> yangisiga almashtirib beramiz yoki pulingizni to\x27liq qaytaramiz!\n\nIltimos, nuqson aks etgan rasm yoki videoni yuboring.",
            ],
            "uz_audiobooks" => [
                "lang"     => "uz",
                "title"    => "🇺🇿 🎧 Audio va elektron kitoblar",
                "summary"  => "Mobil ilovadagi audio kitoblar bo\x27limi haqida",
                "keywords" => ["audio", "audio kitob", "elektron", "pdf", "tinglash"],
                "text"     => "🎧 <b>Audio va Elektron kitoblar:</b>\n\nKitobchi mobil ilovasida yuzlab professional diktorlar tomonidan o\x27qilgan audio kitoblar va elektron nashrlar mavjud.\n\nIlovani yuklab olib, internet bo\x27lmaganda ham oflayn rejimda tinglashingiz mumkin!",
            ],
            "uz_store_locations" => [
                "lang"     => "uz",
                "title"    => "🇺🇿 📍 Do\x27konlarimiz manzili",
                "summary"  => "Filiallar va kitob do\x27konlari joylashuvi",
                "keywords" => ["manzil", "filial", "qayerda", "lokatsiya", "dokon"],
                "text"     => "📍 <b>Kitobchi do\x27konlari:</b>\n\nBizning barcha kitoblarimiz va filiallarimiz haqida to\x27liq ma\x27lumotni rasmiy saytimiz yoki ilovamizdagi do\x27konlar xaritasidan topishingiz mumkin.\n\n🌐 Veb-sayt: <a href=\"https://kitobchi.com\">kitobchi.com</a>",
            ],
            "uz_seller_join" => [
                "lang"     => "uz",
                "title"    => "🇺🇿 💼 Hamkorlik va kitob sotuvchilar uchun",
                "summary"  => "Nashriyotlar va mualliflar bilan hamkorlik",
                "keywords" => ["hamkorlik", "nashriyot", "sotuvchi", "seller", "kitob sotish"],
                "text"     => "💼 <b>Kitobchi hamkorlik dasturi:</b>\n\nSiz nashriyot, kitob do\x27koni yoki muallifmisiz? Kitobchi platformasida o\x27z kitoblaringizni butun O\x27zbekiston bo\x27ylab millionlab kitobxonlarga soting!\n\nBatafsil ma\x27lumot uchun rasmiy sahifamizga tashrif buyuring: <a href=\"https://kitobchi.com\">kitobchi.com/business</a>",
            ],
            "uz_privacy_security" => [
                "lang"     => "uz",
                "title"    => "🇺🇿 🔒 Maxfiylik va xavfsizlik",
                "summary"  => "Shaxsiy ma\x27lumotlar va to\x27lov xavfsizligi kafolati",
                "keywords" => ["xavfsizlik", "maxfiylik", "karta xavfsizligi", "garantiya"],
                "text"     => "🔒 <b>Xavfsizlik va Maxfiylik:</b>\n\nSizning barcha shaxsiy ma\x27lumotlaringiz va to\x27lov tranzaksiyalaringiz xalqaro PCI DSS xavfsizlik standartlari asosida shifrlanadi. Karta ma\x27lumotlaringiz uchinchi shaxslarga berilmaydi.",
            ],
            "uz_greeting" => [
                "lang"     => "uz",
                "title"    => "🇺🇿 👋 Salomlashish",
                "summary"  => "Mijozga xush kelibsiz xabari",
                "keywords" => ["salom", "assalom", "hello", "salomlashish"],
                "text"     => "Assalomu alaykum! Kitobchi mijozlarni qo\x27llab-quvvatlash xizmatiga xush kelibsiz! 😊\n\nSizga qanday yordam bera olaman?",
            ],
            "uz_waiting" => [
                "lang"     => "uz",
                "title"    => "🇺🇿 ⏳ Kuting (Tekshirilmoqda)",
                "summary"  => "Ma\x27lumot tekshirilayotganida kuttirish xabari",
                "keywords" => ["kuting", "tekshirmoqda", "sabr"],
                "text"     => "⏳ <b>Ma\x27lumotlaringizni tekshirmoqdaman</b>, iltimos 1-2 daqiqa kuting...",
            ],
            "uz_thank_you" => [
                "lang"     => "uz",
                "title"    => "🇺🇿 🙏 Minnatdorchilik",
                "summary"  => "Mijozga tashakkur bildirish",
                "keywords" => ["rahmat", "tashakkur", "salomat boling"],
                "text"     => "Kitobchi xizmatini tanlaganingiz uchun tashakkur! Agar yana biror savolingiz bo\x27lsa, bemalol murojaat qiling. Kunningiz xayrli va maroqli o\x27tsin! 📖✨",
            ],
        ];
    }

    /**
     * RUSSIAN ARTICLES
     */
    private static function getRussianArticles(): array
    {
        return [
            "ru_order_tracking" => [
                "lang"     => "ru",
                "title"    => "🇷🇺 📦 Статус и отслеживание заказа",
                "summary"  => "Запрос номера заказа и проверка местонахождения",
                "keywords" => ["заказ", "статус", "где заказ", "отслеживание", "трекинг", "проверить"],
                "text"     => "📦 <b>Проверка статуса заказа:</b>\n\nЧтобы мы могли проверить статус вашего заказа, пожалуйста, отправьте <b>номер заказа</b> (например: <code>#10452</code>) или <b>номер телефона</b>, указанный при регистрации.\n\nНаши операторы немедленно предоставят подробную информацию!",
                "button_text" => "🌐 Проверить на сайте",
                "button_url"  => "https://kitobchi.com",
            ],
            "ru_order_cancel" => [
                "lang"     => "ru",
                "title"    => "🇷🇺 ❌ Отмена заказа",
                "summary"  => "Условия и порядок отмены заказа",
                "keywords" => ["отмена", "отменить заказ", "отказ", "вернуть"],
                "text"     => "❌ <b>Отмена заказа:</b>\n\nЕсли ваш заказ еще не передан в службу доставки или курьеру, мы можем быстро отменить его.\n\nПожалуйста, отправьте номер заказа, и мы оперативно приостановим его в системе.",
            ],
            "ru_delivery_terms" => [
                "lang"     => "ru",
                "title"    => "🇷🇺 🚚 Сроки и условия доставки",
                "summary"  => "Сроки доставки по Ташкенту и в регионы Узбекистана",
                "keywords" => ["доставка", "сроки", "курьер", "ташкент", "регионы", "сколько ждать"],
                "text"     => "🚚 <b>Сроки доставки Kitobchi:</b>\n\n• <b>По городу Ташкент:</b> в течение 24 часов прямо до двери.\n• <b>В регионы и областные центры:</b> 2-3 рабочих дня надежной курьерской службой/почтой.\n\nПри отправке заказа вам придет SMS-уведомление с деталями доставки.",
            ],
            "ru_delivery_price" => [
                "lang"     => "ru",
                "title"    => "🇷🇺 💰 Стоимость доставки",
                "summary"  => "Тарифы на доставку и условия бесплатной доставки",
                "keywords" => ["цена доставки", "стоимость доставки", "бесплатная доставка", "тариф"],
                "text"     => "💰 <b>Стоимость доставки:</b>\n\nСтоимость доставки рассчитывается автоматически в корзине в зависимости от вашего точного адреса и объема заказа.\n\n🎁 Также действуют акции с <b>бесплатной доставкой</b> при заказе на определенную сумму!",
            ],
            "ru_payment_card" => [
                "lang"     => "ru",
                "title"    => "🇷🇺 💳 Способы оплаты (Uzcard/Humo/Visa)",
                "summary"  => "Безопасная онлайн-оплата картами Uzcard, Humo, Visa",
                "keywords" => ["оплата", "карта", "uzcard", "humo", "visa", "mastercard", "payme", "click"],
                "text"     => "💳 <b>Способы оплаты:</b>\n\nВ приложении и на сайте Kitobchi доступны следующие безопасные способы оплаты:\n• Национальные карты <b>Uzcard</b> и <b>Humo</b>\n• Международные карты <b>Visa</b> и <b>Mastercard</b>\n• Прямая оплата через Payme и Click\n\n🔒 Все платежи защищены современными стандартами шифрования.",
            ],
            "ru_payment_problem" => [
                "lang"     => "ru",
                "title"    => "🇷🇺 ⚠️ Проблемы с оплатой",
                "summary"  => "Если деньги списались, а заказ не оформился",
                "keywords" => ["ошибка оплаты", "списались деньги", "чек", "не прошла оплата"],
                "text"     => "⚠️ <b>Если возникла ошибка при оплате:</b>\n\n1. Проверьте, включены ли SMS-уведомления (3DS код) на вашей карте.\n2. Убедитесь в наличии достаточной суммы на балансе.\n3. Если средства списались, но заказ не подтвердился — отправьте <b>скриншот квитанции/чека</b> сюда, и мы мгновенно все решим!",
            ],
            "ru_app_download" => [
                "lang"     => "ru",
                "title"    => "🇷🇺 📲 Скачать приложение Kitobchi",
                "summary"  => "Официальные ссылки на App Store и Google Play",
                "keywords" => ["приложение", "скачать", "ios", "android", "play market", "app store"],
                "text"     => "📲 <b>Официальное приложение Kitobchi:</b>\n\nКрупнейший книжный маркетплейс и библиотека аудиокниг в Узбекистане!\n\n🍏 <a href=\"https://apps.apple.com/uz/app/kitobchi/id6753818078\">Скачать в App Store (iOS)</a>\n🤖 <a href=\"https://play.google.com/store/apps/details?id=com.kitobchi.kitobchi\">Скачать в Google Play (Android)</a>\n🌐 <a href=\"https://kitobchi.com\">Официальный сайт: kitobchi.com</a>",
                "button_text" => "📲 Скачать приложение",
                "button_url"  => "https://kitobchi.com",
            ],
            "ru_cashback" => [
                "lang"     => "ru",
                "title"    => "🇷🇺 🎁 Программа кешбэка",
                "summary"  => "Начисление и использование бонусов за покупки",
                "keywords" => ["кешбэк", "бонусы", "баллы", "скидка", "cashback"],
                "text"     => "🎁 <b>Система кешбэка Kitobchi:</b>\n\n• С каждой покупки книг на ваш личный счет начисляется кешбэк.\n• Накопленные баллы вы можете использовать для 100% оплаты следующих заказов.\n• Баланс кешбэка всегда доступен в разделе <b>Профиль</b> приложения.",
            ],
            "ru_work_hours" => [
                "lang"     => "ru",
                "title"    => "🇷🇺 ⏰ График работы службы поддержки",
                "summary"  => "Часы работы операторов и оформление заказов",
                "keywords" => ["время работы", "график", "поддержка", "режим работы", "контакты"],
                "text"     => "⏰ <b>Режим работы:</b>\n\n• <b>Служба поддержки:</b> ежедневно с 09:00 до 22:00\n• <b>Мобильное приложение и сайт:</b> 24/7 (заказы принимаются круглосуточно)\n• <b>Служба доставки:</b> Понедельник - Суббота.",
            ],
            "ru_return_policy" => [
                "lang"     => "ru",
                "title"    => "🇷🇺 🔄 Возврат и обмен книг",
                "summary"  => "Гарантия замены при заводском браке или повреждении",
                "keywords" => ["возврат", "обмен", "брак", "дефект", "замена"],
                "text"     => "🔄 <b>Правила возврата и обмена:</b>\n\nЕсли в книге обнаружен типографский брак, повреждение или отсутствие страниц, мы <b>абсолютно бесплатно</b> заменим ее на новый экземпляр или вернем полную стоимость!\n\nПожалуйста, отправьте фото или видео дефекта.",
            ],
            "ru_audiobooks" => [
                "lang"     => "ru",
                "title"    => "🇷🇺 🎧 Аудиокниги и электронные книги",
                "summary"  => "Слушайте и читайте книги в приложении Kitobchi",
                "keywords" => ["аудиокниги", "электронные книги", "слушать", "онлайн"],
                "text"     => "🎧 <b>Аудио и электронные книги в Kitobchi:</b>\n\nВ нашем приложении собраны сотни аудиокниг, озвученных профессиональными дикторами, а также популярные электронные издания.\n\nСлушайте любимые произведения онлайн и офлайн без доступа к интернету!",
            ],
            "ru_store_locations" => [
                "lang"     => "ru",
                "title"    => "🇷🇺 📍 Адреса магазинов и пунктов выдачи",
                "summary"  => "Где находятся филиалы и шоурумы Kitobchi",
                "keywords" => ["адрес", "филиалы", "магазины", "где забрать", "локация"],
                "text"     => "📍 <b>Адреса и точки выдачи Kitobchi:</b>\n\nАктуальный список филиалов и карту пунктов выдачи вы найдете на нашем сайте или в приложении в разделе «Магазины».\n\n🌐 Сайт: <a href=\"https://kitobchi.com\">kitobchi.com</a>",
            ],
            "ru_seller_join" => [
                "lang"     => "ru",
                "title"    => "🇷🇺 💼 Партнерство для издательств и авторов",
                "summary"  => "Продавайте свои книги на маркетплейсе Kitobchi",
                "keywords" => ["партнерство", "издательствам", "продавцам", "авторам", "сотрудничество"],
                "text"     => "💼 <b>Станьте продавцом на Kitobchi:</b>\n\nВы издатель, книжный магазин или автор? Продавайте свои книги миллионам читателей по всему Узбекистану через платформу Kitobchi!\n\nПодробности для партнеров: <a href=\"https://kitobchi.com\">kitobchi.com/business</a>",
            ],
            "ru_privacy_security" => [
                "lang"     => "ru",
                "title"    => "🇷🇺 🔒 Безопасность и конфиденциальность",
                "summary"  => "Защита персональных данных и платежей",
                "keywords" => ["безопасность", "конфиденциальность", "защита данных"],
                "text"     => "🔒 <b>Безопасность и Защита данных:</b>\n\nВсе ваши персональные данные и платежные транзакции надежно зашифрованы по международному протоколу PCI DSS. Данные карт никогда не передаются посторонним лицам.",
            ],
            "ru_greeting" => [
                "lang"     => "ru",
                "title"    => "🇷🇺 👋 Приветствие",
                "summary"  => "Приветственное сообщение службы заботы",
                "keywords" => ["здравствуйте", "привет", "добрый день", "hello"],
                "text"     => "Здравствуйте! Добро пожаловать в службу заботы о клиентах Kitobchi! 😊\n\nЧем я могу вам помочь?",
            ],
            "ru_waiting" => [
                "lang"     => "ru",
                "title"    => "🇷🇺 ⏳ Пожалуйста, подождите",
                "summary"  => "Сообщение о проверке информации",
                "keywords" => ["подождите", "минуту", "проверяю"],
                "text"     => "⏳ <b>Проверяю информацию по вашему вопросу</b>, пожалуйста, уделите 1-2 минуты...",
            ],
            "ru_thank_you" => [
                "lang"     => "ru",
                "title"    => "🇷🇺 🙏 Спасибо за обращение",
                "summary"  => "Благодарность клиенту за диалог",
                "keywords" => ["спасибо", "благодарю", "всего доброго"],
                "text"     => "Спасибо, что выбираете Kitobchi! Если у вас появятся новые вопросы, мы всегда на связи. Приятного чтения и отличного дня! 📖✨",
            ],
        ];
    }

    /**
     * ENGLISH ARTICLES
     */
    private static function getEnglishArticles(): array
    {
        return [
            "en_order_tracking" => [
                "lang"     => "en",
                "title"    => "🇬🇧 📦 Order Status & Tracking",
                "summary"  => "How to check order status and delivery updates",
                "keywords" => ["order", "status", "track", "tracking", "where is my order", "delivery status"],
                "text"     => "📦 <b>Order Status & Tracking:</b>\n\nTo check the current status of your order, please reply with your <b>Order ID</b> (e.g. <code>#10452</code>) or your registered <b>phone number</b>.\n\nOur support team will immediately look into it and provide live updates!",
                "button_text" => "🌐 Check Online",
                "button_url"  => "https://kitobchi.com",
            ],
            "en_order_cancel" => [
                "lang"     => "en",
                "title"    => "🇬🇧 ❌ Cancel an Order",
                "summary"  => "Order cancellation terms and assistance",
                "keywords" => ["cancel", "cancellation", "abort order", "stop order"],
                "text"     => "❌ <b>Order Cancellation:</b>\n\nIf your order has not been dispatched to our courier service yet, we can cancel it promptly.\n\nPlease share your Order ID and we will process the cancellation right away.",
            ],
            "en_delivery_terms" => [
                "lang"     => "en",
                "title"    => "🇬🇧 🚚 Delivery Times & Shipping Terms",
                "summary"  => "Shipping duration for Tashkent and Uzbekistan regions",
                "keywords" => ["delivery", "shipping", "how long", "tashkent", "regions", "time"],
                "text"     => "🚚 <b>Shipping & Delivery Terms:</b>\n\n• <b>Tashkent city:</b> Doorstep delivery within 24 hours.\n• <b>Regions & provinces:</b> 2-3 business days via verified express couriers.\n\nYou will receive an SMS notification as soon as your package is on the way.",
            ],
            "en_delivery_price" => [
                "lang"     => "en",
                "title"    => "🇬🇧 💰 Shipping Rates & Free Delivery",
                "summary"  => "Delivery cost calculation and free shipping threshold",
                "keywords" => ["shipping fee", "delivery cost", "free shipping", "price"],
                "text"     => "💰 <b>Shipping Rates:</b>\n\nDelivery fees are automatically calculated at checkout based on your exact address and package weight.\n\n🎁 We also offer <b>Free Shipping</b> promotions on eligible qualifying orders!",
            ],
            "en_payment_card" => [
                "lang"     => "en",
                "title"    => "🇬🇧 💳 Payment Methods (Uzcard/Humo/Visa/Mastercard)",
                "summary"  => "Accepted secure payment methods online",
                "keywords" => ["payment", "card", "visa", "mastercard", "uzcard", "humo", "payme", "click"],
                "text"     => "💳 <b>Accepted Payment Methods:</b>\n\nYou can securely pay in the Kitobchi app and website using:\n• <b>Uzcard</b> & <b>Humo</b> national cards\n• <b>Visa</b> & <b>Mastercard</b> international cards\n• Direct checkout via Payme and Click\n\n🔒 All transactions are protected with end-to-end bank-grade security.",
            ],
            "en_payment_problem" => [
                "lang"     => "en",
                "title"    => "🇬🇧 ⚠️ Payment Issues & Transaction Help",
                "summary"  => "What to do if payment failed or charged without order confirmation",
                "keywords" => ["payment failed", "charged", "receipt", "error", "transaction issue"],
                "text"     => "⚠️ <b>Payment Troubleshooting:</b>\n\n1. Ensure SMS 3D-Secure authentication is enabled on your bank card.\n2. Verify that your card has sufficient available balance.\n3. If funds were deducted but your order is pending, please send a <b>screenshot of the payment receipt</b> here — we will resolve it promptly!",
            ],
            "en_app_download" => [
                "lang"     => "en",
                "title"    => "🇬🇧 📲 Download Kitobchi Mobile App",
                "summary"  => "Official links for iOS and Android devices",
                "keywords" => ["download", "app", "ios", "android", "iphone", "mobile app"],
                "text"     => "📲 <b>Kitobchi Official Mobile App:</b>\n\nUzbekistan\x27s premier online bookstore and audiobook platform!\n\n🍏 <a href=\"https://apps.apple.com/uz/app/kitobchi/id6753818078\">Download on App Store (iOS)</a>\n🤖 <a href=\"https://play.google.com/store/apps/details?id=com.kitobchi.kitobchi\">Get it on Google Play (Android)</a>\n🌐 <a href=\"https://kitobchi.com\">Official Website: kitobchi.com</a>",
                "button_text" => "📲 Download App",
                "button_url"  => "https://kitobchi.com",
            ],
            "en_cashback" => [
                "lang"     => "en",
                "title"    => "🇬🇧 🎁 Cashback & Loyalty Rewards",
                "summary"  => "Earn cashback on book purchases and redeem for discounts",
                "keywords" => ["cashback", "points", "rewards", "loyalty", "discount"],
                "text"     => "🎁 <b>Kitobchi Cashback Rewards:</b>\n\n• Earn instant cashback with every book purchase.\n• Redeem your accumulated cashback points as discounts on future orders.\n• Check your rewards balance anytime in the <b>Profile</b> tab of the mobile app.",
            ],
            "en_work_hours" => [
                "lang"     => "en",
                "title"    => "🇬🇧 ⏰ Working Hours & Support Info",
                "summary"  => "Support hours and operating schedule",
                "keywords" => ["working hours", "schedule", "opening hours", "contact", "support time"],
                "text"     => "⏰ <b>Operating Hours:</b>\n\n• <b>Customer Care Team:</b> Daily from 09:00 to 22:00 (UTC+5)\n• <b>Website & Mobile App:</b> Open 24/7 for instant ordering\n• <b>Courier Dispatch:</b> Monday through Saturday.",
            ],
            "en_return_policy" => [
                "lang"     => "en",
                "title"    => "🇬🇧 🔄 Return & Replacement Policy",
                "summary"  => "Free exchange guarantee for damaged or defective books",
                "keywords" => ["return", "exchange", "refund", "damaged book", "defective"],
                "text"     => "🔄 <b>Return & Exchange Guarantee:</b>\n\nIf you receive a book with printing defects, missing pages, or shipping damage, we provide a <b>100% free replacement</b> or a full refund!\n\nPlease send us a photo or video showing the issue.",
            ],
            "en_audiobooks" => [
                "lang"     => "en",
                "title"    => "🇬🇧 🎧 Audiobooks & Digital E-Books",
                "summary"  => "Listen to professionally narrated audiobooks anywhere",
                "keywords" => ["audiobook", "ebook", "listen", "digital library"],
                "text"     => "🎧 <b>Audiobooks & Digital Library:</b>\n\nExplore hundreds of bestselling audiobooks professionally narrated by top voice artists in the Kitobchi app.\n\nDownload titles to your device and enjoy uninterrupted listening even without internet connection!",
            ],
            "en_store_locations" => [
                "lang"     => "en",
                "title"    => "🇬🇧 📍 Store Locations & Pickup Branches",
                "summary"  => "Find our physical stores and partner pickup points",
                "keywords" => ["store", "location", "address", "branches", "pickup"],
                "text"     => "📍 <b>Kitobchi Stores & Pickup Points:</b>\n\nFind our physical locations and partner pickup spots on our official website or in the app\x27s interactive map.\n\n🌐 Website: <a href=\"https://kitobchi.com\">kitobchi.com</a>",
            ],
            "en_seller_join" => [
                "lang"     => "en",
                "title"    => "🇬🇧 💼 Partner With Us (Publishers & Authors)",
                "summary"  => "Sell your books to millions of readers across Uzbekistan",
                "keywords" => ["partner", "seller", "publisher", "author", "merchant", "business"],
                "text"     => "💼 <b>Kitobchi Business & Publisher Program:</b>\n\nAre you a publisher, author, or bookstore owner? Expand your reach to millions of book enthusiasts across Uzbekistan with Kitobchi Marketplace!\n\nLearn more: <a href=\"https://kitobchi.com\">kitobchi.com/business</a>",
            ],
            "en_privacy_security" => [
                "lang"     => "en",
                "title"    => "🇬🇧 🔒 Privacy & Security Guarantee",
                "summary"  => "Data protection standards and privacy commitment",
                "keywords" => ["privacy", "security", "data protection", "safety"],
                "text"     => "🔒 <b>Privacy & Data Security:</b>\n\nAll your personal details and payment transactions are secured using industry-standard PCI DSS encryption protocols. Your financial information is never shared with third parties.",
            ],
            "en_greeting" => [
                "lang"     => "en",
                "title"    => "🇬🇧 👋 Greeting & Welcome",
                "summary"  => "Warm welcome message for customers",
                "keywords" => ["hello", "hi", "greeting", "welcome", "good day"],
                "text"     => "Hello! Welcome to Kitobchi Customer Support! 😊\n\nHow may I assist you today?",
            ],
            "en_waiting" => [
                "lang"     => "en",
                "title"    => "🇬🇧 ⏳ Please Wait (Checking Details)",
                "summary"  => "Hold on message while verifying account or order",
                "keywords" => ["wait", "checking", "hold on", "one moment"],
                "text"     => "⏳ <b>Checking your details now</b>, please allow me 1-2 minutes...",
            ],
            "en_thank_you" => [
                "lang"     => "en",
                "title"    => "🇬🇧 🙏 Thank You for Contacting Us",
                "summary"  => "Closing appreciation message",
                "keywords" => ["thank you", "thanks", "have a nice day", "goodbye"],
                "text"     => "Thank you for choosing Kitobchi! If you need further assistance, feel free to reach out anytime. Happy reading and have a wonderful day! 📖✨",
            ],
        ];
    }

    /**
     * JAPANESE ARTICLES
     */
    private static function getJapaneseArticles(): array
    {
        return [
            "ja_order_tracking" => [
                "lang"     => "ja",
                "title"    => "🇯🇵 📦 注文状況の確認・配送追跡",
                "summary"  => "ご注文番号による配送状況の確認",
                "keywords" => ["注文", "配送状況", "追跡", "ステータス", "荷物", "確認"],
                "text"     => "📦 <b>ご注文状況の確認について:</b>\n\nご注文の配送状況をお調べいたしますので、<b>ご注文番号</b>（例：<code>#10452</code>）またはご登録の<b>お電話番号</b>をお知らせください。\n\nオペレーターが直ちにお調べしてご案内いたします！",
                "button_text" => "🌐 ウェブサイトで確認",
                "button_url"  => "https://kitobchi.com",
            ],
            "ja_order_cancel" => [
                "lang"     => "ja",
                "title"    => "🇯🇵 ❌ ご注文のキャンセル",
                "summary"  => "発送前のご注文キャンセル手続き",
                "keywords" => ["キャンセル", "取り消し", "注文取消", "キャンセル方法"],
                "text"     => "❌ <b>ご注文のキャンセルについて:</b>\n\n商品がまだ配送業者へ引き渡されていない場合、迅速にキャンセル手続きを承ります。\n\nご注文番号をお知らせいただければ、直ちに対応いたします。",
            ],
            "ja_delivery_terms" => [
                "lang"     => "ja",
                "title"    => "🇯🇵 🚚 お届け日数・配送案内",
                "summary"  => "タシュケント市内および各地域へのお届け日数",
                "keywords" => ["お届け日数", "配送", "配達", "タシュケント", "納期"],
                "text"     => "🚚 <b>お届け日数・配送のご案内:</b>\n\n• <b>タシュケント市内:</b> 24時間以内にご指定のご住所へお届けします。\n• <b>地方・各州の中心部:</b> 信頼できる宅配便にて2〜3営業日以内にお届けします。\n\n発送が完了しましたらSMSにて通知をお送りいたします。",
            ],
            "ja_delivery_price" => [
                "lang"     => "ja",
                "title"    => "🇯🇵 💰 配送料・送料無料のご案内",
                "summary"  => "配送料金の計算および送料無料キャンペーン",
                "keywords" => ["配送料", "送料", "送料無料", "料金"],
                "text"     => "💰 <b>配送料金について:</b>\n\n配送料はお届け先のご住所および商品のサイズに応じてカート画面にて自動計算されます。\n\n🎁 一定金額以上のお買い物で<b>送料無料</b>となるキャンペーンも実施中です！",
            ],
            "ja_payment_card" => [
                "lang"     => "ja",
                "title"    => "🇯🇵 💳 お支払い方法 (Uzcard/Humo/Visa/Mastercard)",
                "summary"  => "各種クレジットカード・決済サービスのご案内",
                "keywords" => ["支払い", "決済", "カード", "クレジットカード", "visa", "uzcard", "humo"],
                "text"     => "💳 <b>ご利用可能なお支払い方法:</b>\n\nKitobchiアプリおよびウェブサイトでは安心してお買い物いただけます:\n• <b>Uzcard</b> / <b>Humo</b> 国内決済カード\n• <b>Visa</b> / <b>Mastercard</b> 国際クレジットカード\n• Payme / Click による直接決済\n\n🔒 すべてのお取引は高度な暗号化により保護されています。",
            ],
            "ja_payment_problem" => [
                "lang"     => "ja",
                "title"    => "🇯🇵 ⚠️ 決済エラー・お支払いトラブル",
                "summary"  => "引き落とし後に注文が反映されない場合の対処法",
                "keywords" => ["決済エラー", "支払い失敗", "引き落とし", "領収書", "エラー"],
                "text"     => "⚠️ <b>決済でお困りの場合:</b>\n\n1. カードの3Dセキュア（SMS認証）が有効になっているかご確認ください。\n2. ご利用限度額・残高をご確認ください。\n3. 万が一、引き落とし完了後に注文が反映されない場合は、<b>決済レシートのスクリーンショット</b>をお送りください。迅速にお調べいたします！",
            ],
            "ja_app_download" => [
                "lang"     => "ja",
                "title"    => "🇯🇵 📲 公式アプリのダウンロード",
                "summary"  => "iOS（App Store）およびAndroid（Google Play）リンク",
                "keywords" => ["アプリ", "ダウンロード", "iphone", "android", "公式アプリ"],
                "text"     => "📲 <b>Kitobchi 公式モバイルアプリ:</b>\n\nウズベキスタン最大級のオンライン書店＆オーディオブックプラットフォーム！\n\n🍏 <a href=\"https://apps.apple.com/uz/app/kitobchi/id6753818078\">App Storeからダウンロード (iOS)</a>\n🤖 <a href=\"https://play.google.com/store/apps/details?id=com.kitobchi.kitobchi\">Google Playからダウンロード (Android)</a>\n🌐 <a href=\"https://kitobchi.com\">公式ウェブサイト: kitobchi.com</a>",
                "button_text" => "📲 アプリを入手",
                "button_url"  => "https://kitobchi.com",
            ],
            "ja_cashback" => [
                "lang"     => "ja",
                "title"    => "🇯🇵 🎁 キャッシュバック・ポイント制度",
                "summary"  => "本のご購入で貯まるポイント還元システム",
                "keywords" => ["キャッシュバック", "ポイント", "割引", "特典", "還元"],
                "text"     => "🎁 <b>Kitobchi キャッシュバック制度:</b>\n\n• 本をご購入いただくたびに、アカウントへキャッシュバックが貯まります。\n• 貯まったポイントは、次回以降のご注文時に割引としてご利用いただけます。\n• ポイント残高はアプリの「マイページ」にていつでも確認可能です。",
            ],
            "ja_work_hours" => [
                "lang"     => "ja",
                "title"    => "🇯🇵 ⏰ カスタマーサポート営業時間",
                "summary"  => "サポート対応時間および注文受付時間",
                "keywords" => ["営業時間", "サポート時間", "問い合わせ時間", "連絡先"],
                "text"     => "⏰ <b>営業時間・サポート受付:</b>\n\n• <b>カスタマーサポート:</b> 毎日 09:00 〜 22:00（ウズベキスタン時間）\n• <b>アプリ＆ウェブサイト:</b> 24時間365日いつでもご注文可能\n• <b>配送業務:</b> 月曜日〜土曜日",
            ],
            "ja_return_policy" => [
                "lang"     => "ja",
                "title"    => "🇯🇵 🔄 返品・交換ポリシーについて",
                "summary"  => "乱丁・落丁・破損時の無料交換保証",
                "keywords" => ["返品", "交換", "破損", "乱丁", "不良品", "返金"],
                "text"     => "🔄 <b>安心の返品・交換保証:</b>\n\n万が一、お届けした書籍に乱丁・落丁や配送時の破損がございましたら、<b>無償で新品と交換</b>または全額返金いたします！\n\n該当箇所の写真または動画をお送りください。",
            ],
            "ja_audiobooks" => [
                "lang"     => "ja",
                "title"    => "🇯🇵 🎧 オーディオブック・電子書籍",
                "summary"  => "プロのナレーターによる朗読をアプリで楽しむ",
                "keywords" => ["オーディオブック", "電子書籍", "朗読", "音声", "聴く本"],
                "text"     => "🎧 <b>オーディオブック＆電子書籍のご案内:</b>\n\nKitobchiアプリでは、プロの声優・ナレーターによる人気書籍の朗読音声を多数配信中！\n\n端末にダウンロードしておけば、オフライン環境でも快適に読書・聴書をお楽しみいただけます。",
            ],
            "ja_store_locations" => [
                "lang"     => "ja",
                "title"    => "🇯🇵 📍 店舗案内・ショールーム所在地",
                "summary"  => "実店舗および受取スポットのご案内",
                "keywords" => ["店舗", "ショールーム", "場所", "住所", "アクセス"],
                "text"     => "📍 <b>店舗および受取スポット:</b>\n\n実店舗の所在地や受取スポットの詳細は、公式ウェブサイトまたはアプリ内のマップよりご確認いただけます。\n\n🌐 公式サイト: <a href=\"https://kitobchi.com\">kitobchi.com</a>",
            ],
            "ja_seller_join" => [
                "lang"     => "ja",
                "title"    => "🇯🇵 💼 出品者・出版社・著者様のご提携案内",
                "summary"  => "Kitobchiプラットフォームでの書籍販売について",
                "keywords" => ["出品", "パートナー", "提携", "出版社", "著者", "出店"],
                "text"     => "💼 <b>出品者・パートナーシップのご案内:</b>\n\n出版社様、書店様、著者様へ。\nKitobchiマーケットプレイスを通じて、ウズベキスタン全土の読者へあなたの本をお届けしませんか？\n\n詳細はこちら: <a href=\"https://kitobchi.com\">kitobchi.com/business</a>",
            ],
            "ja_privacy_security" => [
                "lang"     => "ja",
                "title"    => "🇯🇵 🔒 プライバシーとセキュリティ",
                "summary"  => "個人情報保護と国際セキュリティ基準",
                "keywords" => ["プライバシー", "セキュリティ", "個人情報", "暗号化"],
                "text"     => "🔒 <b>安心のセキュリティ体制:</b>\n\nお客様の個人情報および決済データは国際基準（PCI DSS）に準拠した厳格な暗号化技術により厳重に保護されております。第三者への開示は一切行いません。",
            ],
            "ja_greeting" => [
                "lang"     => "ja",
                "title"    => "🇯🇵 👋 ご挨拶（カスタマーサポート）",
                "summary"  => "お客様へのおもてなしメッセージ",
                "keywords" => ["こんにちは", "はじめまして", "挨拶", "いらっしゃいませ"],
                "text"     => "こんにちは！Kitobchi カスタマーサポートへようこそ！😊\n\n本日はどのようなご用件でしょうか？お気軽にお申し付けください。",
            ],
            "ja_waiting" => [
                "lang"     => "ja",
                "title"    => "🇯🇵 ⏳ 確認中（少々お待ちください）",
                "summary"  => "情報確認中のお待たせメッセージ",
                "keywords" => ["お待ちください", "確認中", "少々お待ち"],
                "text"     => "⏳ <b>ただいま詳細を確認しております。</b>恐れ入りますが、1〜2分ほどお待ちください...",
            ],
            "ja_thank_you" => [
                "lang"     => "ja",
                "title"    => "🇯🇵 🙏 お問い合わせありがとうございます",
                "summary"  => "ご案内終了後の御礼メッセージ",
                "keywords" => ["ありがとう", "感謝", "良い一日を", "失礼します"],
                "text"     => "Kitobchiをご利用いただき誠にありがとうございます！また何かご不明な点がございましたら、いつでもお気軽にお問い合わせください。素敵な読書時間をお過ごしください！📖✨",
            ],
        ];
    }
}
