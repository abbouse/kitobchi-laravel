<?php

namespace App\Services;

use App\Models\SplitContract;
use App\Models\SplitInstallment;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Split (nasiya) shartnomasi va undirish xati PDF hujjatlari uchun
 * ma'lumot va tarjimalarni tayyorlaydi. Til — mijoz locale'siga qarab:
 * ru bo'lsa ruscha, aks holda o'zbekcha (lotin).
 */
class SplitDocumentService
{
    /* ─────────────────────────── Umumiy ─────────────────────────── */

    public function resolveLocale(?User $user): string
    {
        return strtolower((string) ($user?->locale ?? '')) === 'ru' ? 'ru' : 'uz';
    }

    public function contractNumber(SplitContract $contract): string
    {
        return 'N-'.str_pad((string) $contract->id, 5, '0', STR_PAD_LEFT);
    }

    private function lender(string $locale): array
    {
        $cfg = (array) config('services.split_lender', []);

        return [
            'name' => $locale === 'ru' ? ($cfg['name_ru'] ?: $cfg['name']) : $cfg['name'],
            'inn' => (string) ($cfg['inn'] ?? ''),
            'account' => (string) ($cfg['account'] ?? ''),
            'mfo' => (string) ($cfg['mfo'] ?? ''),
            'bank' => $locale === 'ru' ? ($cfg['bank_ru'] ?: $cfg['bank']) : (string) ($cfg['bank'] ?? ''),
            'email' => (string) ($cfg['email'] ?? ''),
            'phone' => (string) ($cfg['phone'] ?? ''),
            'address' => $locale === 'ru'
                ? ((string) ($cfg['address_ru'] ?? '') ?: (string) ($cfg['address'] ?? ''))
                : (string) ($cfg['address'] ?? ''),
        ];
    }

    /**
     * Buyurtma tarkibi: nomi, soni, narxi. seller_order_items aniqroq
     * manba; bo'lmasa order items json'idan olinadi.
     */
    private function orderItems(SplitContract $contract): array
    {
        $order = $contract->order;
        if (! $order) {
            return [];
        }

        $rows = [];

        if (Schema::hasTable('seller_order_items')) {
            $items = DB::table('seller_order_items')
                ->where('order_id', $order->id)
                ->whereNull('cancelled_at')
                ->get(['product_id', 'type', 'quantity', 'price']);

            foreach ($items as $item) {
                $type = strtolower((string) $item->type);
                if ($type === 'gift') {
                    continue;
                }
                $name = str_contains($type, 'stationer')
                    ? DB::table('stationeries')->where('id', $item->product_id)->value('name')
                    : DB::table('books')->where('id', $item->product_id)->value('name');

                $rows[] = [
                    'name' => (string) ($name ?: ('#'.$item->product_id)),
                    'quantity' => max(1, (int) $item->quantity),
                    'price' => (int) $item->price,
                ];
            }
        }

        if (empty($rows)) {
            foreach ((array) ($order->items ?? []) as $item) {
                $item = (array) $item;
                if (strtolower((string) ($item['type'] ?? '')) === 'gift') {
                    continue;
                }
                $rows[] = [
                    'name' => (string) ($item['name'] ?? ('#'.($item['item_id'] ?? '?'))),
                    'quantity' => max(1, (int) ($item['quantity'] ?? $item['count'] ?? 1)),
                    'price' => (int) ($item['price'] ?? 0),
                ];
            }
        }

        return $rows;
    }

    private function overdueRows(SplitContract $contract): array
    {
        $today = now()->startOfDay();

        return $contract->installments
            ->where('status', SplitInstallment::STATUS_OVERDUE)
            ->sortBy('sequence')
            ->map(function (SplitInstallment $installment) use ($today) {
                $due = $installment->due_at?->copy()->startOfDay();

                return [
                    'sequence' => (int) $installment->sequence,
                    'due_at' => $installment->due_at?->format('d.m.Y'),
                    'amount' => max(0, (int) $installment->amount - (int) $installment->paid_amount),
                    'days_overdue' => $due && $due->lessThan($today) ? $due->diffInDays($today) : 0,
                ];
            })
            ->values()
            ->all();
    }

    /* ────────────────────── Shartnoma (PDF) ─────────────────────── */

    public function contractData(SplitContract $contract): array
    {
        $contract->loadMissing(['user.location', 'plan', 'order', 'installments']);
        $user = $contract->user;
        $locale = $this->resolveLocale($user);

        $upfront = $contract->installments->firstWhere('is_upfront', true);
        $acceptedAt = $contract->starts_at ?? $contract->created_at;

        $schedule = $contract->installments->map(fn (SplitInstallment $installment) => [
            'sequence' => (int) $installment->sequence,
            'due_at' => $installment->is_upfront
                ? optional($acceptedAt)->format('d.m.Y')
                : optional($installment->due_at)->format('d.m.Y'),
            'amount' => (int) $installment->amount,
            'is_upfront' => (bool) $installment->is_upfront,
            'status' => $installment->status,
            'paid_at' => optional($installment->paid_at)->format('d.m.Y'),
        ])->values()->all();

        $totalInterestPercent = round(
            (float) $contract->monthly_interest_percent * max(1, (int) $contract->months),
            2,
        );

        return [
            'locale' => $locale,
            't' => $this->contractStrings($locale),
            'number' => $this->contractNumber($contract),
            'date' => optional($acceptedAt)->format('d.m.Y'),
            'acceptedAtFull' => optional($acceptedAt)->format('d.m.Y H:i'),
            'lender' => $this->lender($locale),
            'buyer' => [
                'name' => trim(($user?->name ?? '').' '.($user?->lastname ?? '')) ?: ('ID '.$contract->user_id),
                'phone' => (string) ($user?->phone_number ?? ''),
                'userId' => (int) $contract->user_id,
                'address' => (string) ($user?->location?->fullAddress ?? ''),
            ],
            'orderId' => $contract->order_id,
            'items' => $this->orderItems($contract),
            'plan' => [
                'name' => (string) ($contract->plan?->name ?? ($contract->months.' oy')),
                'months' => (int) $contract->months,
                'periodUnit' => (string) $contract->period_unit,
                'periodEvery' => (int) $contract->period_every,
                'monthlyPercent' => (float) $contract->monthly_interest_percent,
                'totalPercent' => $totalInterestPercent,
                'installmentsCount' => (int) $contract->installments_count,
                'debitDay' => $contract->debit_day,
            ],
            'sums' => [
                'principal' => (int) $contract->principal_amount,
                'interest' => (int) $contract->interest_amount,
                'total' => (int) $contract->total_amount,
                'upfront' => $upfront ? (int) $upfront->amount : 0,
                'deliveryFee' => (int) data_get($contract->meta, 'delivery_fee', 0),
                'packagingFee' => (int) data_get($contract->meta, 'packaging_fee', 0),
            ],
            'schedule' => $schedule,
            'status' => $contract->status,
            'generatedAt' => now()->format('d.m.Y H:i'),
        ];
    }

    /* ─────────────────── Undirish xati (PDF) ────────────────────── */

    public function demandLetterData(SplitContract $contract): array
    {
        $contract->loadMissing(['user.location', 'plan', 'installments']);
        $user = $contract->user;
        $locale = $this->resolveLocale($user);
        $overdue = $this->overdueRows($contract);

        $overdueTotal = array_sum(array_column($overdue, 'amount'));
        $maxDays = empty($overdue) ? 0 : max(array_column($overdue, 'days_overdue'));

        return [
            'locale' => $locale,
            't' => $this->demandStrings($locale),
            'number' => $this->contractNumber($contract),
            'contractDate' => optional($contract->starts_at ?? $contract->created_at)->format('d.m.Y'),
            'letterDate' => now()->format('d.m.Y'),
            'lender' => $this->lender($locale),
            'buyer' => [
                'name' => trim(($user?->name ?? '').' '.($user?->lastname ?? '')) ?: ('ID '.$contract->user_id),
                'phone' => (string) ($user?->phone_number ?? ''),
                'address' => (string) ($user?->location?->fullAddress ?? ''),
            ],
            'orderId' => $contract->order_id,
            'overdue' => $overdue,
            'overdueTotal' => $overdueTotal,
            'maxDaysOverdue' => $maxDays,
            'remainingTotal' => (int) $contract->remaining_amount,
            'paidTotal' => (int) $contract->paid_amount,
            'contractTotal' => (int) $contract->total_amount,
            'demandDays' => max(5, (int) config('services.split_lender.demand_days', 10)),
            'overdueSince' => optional($contract->overdue_since)->format('d.m.Y'),
            'generatedAt' => now()->format('d.m.Y H:i'),
        ];
    }

    /* ───────────────────────── Matnlar ──────────────────────────── */

    private function contractStrings(string $locale): array
    {
        if ($locale === 'ru') {
            return [
                'title' => 'ДОГОВОР КУПЛИ-ПРОДАЖИ ТОВАРОВ В РАССРОЧКУ',
                'number' => 'Договор №',
                'date' => 'Дата заключения',
                'preamble' => 'Настоящий договор заключён между нижеуказанными сторонами в электронной форме посредством мобильного приложения «Kitobchi» в соответствии со статьями 367–370, 464 Гражданского кодекса Республики Узбекистан и Законом Республики Узбекистан «Об электронной коммерции». Оформление заказа с выбором способа оплаты «Рассрочка (Split)» и подтверждение в приложении признаются акцептом настоящей оферты (ст. 370 ГК РУз, акцепт конклюдентными действиями).',
                'seller' => 'ПРОДАВЕЦ (КРЕДИТОР)',
                'buyer' => 'ПОКУПАТЕЛЬ',
                'buyer_phone' => 'Телефон',
                'buyer_account' => 'Аккаунт в приложении (ID)',
                'buyer_address' => 'Адрес доставки',
                's1_title' => '1. ПРЕДМЕТ ДОГОВОРА',
                's1_text' => 'Продавец передаёт в собственность Покупателя нижеуказанные товары (заказ №:orderId), а Покупатель обязуется принять товары и оплатить их стоимость частями (в рассрочку) в порядке и сроки, установленные настоящим договором (ст. 464–465 ГК РУз). Право собственности на товар переходит к Покупателю с момента передачи товара.',
                'goods' => 'Наименование товара',
                'qty' => 'Кол-во',
                'price' => 'Цена (сум)',
                's2_title' => '2. СУММА ДОГОВОРА И ПОРЯДОК ОПЛАТЫ',
                's2_principal' => 'Стоимость товаров (основной долг)',
                's2_delivery' => 'Доставка и упаковка',
                's2_interest' => 'Наценка за рассрочку (:monthly% в месяц × :months мес. = :total%)',
                's2_total' => 'ОБЩАЯ СУММА К ОПЛАТЕ',
                's2_upfront' => 'Первоначальный платёж (списывается при оформлении)',
                's2_text' => 'Оплата производится автоматическим списанием с банковской карты Покупателя, привязанной в приложении, согласно графику платежей (Приложение №1 — неотъемлемая часть договора). Покупатель обязан обеспечить наличие достаточных средств на карте в даты платежей. Все суммы указаны в узбекских сумах.',
                's3_title' => '3. ГРАФИК ПЛАТЕЖЕЙ (ПРИЛОЖЕНИЕ №1)',
                'sch_no' => '№',
                'sch_date' => 'Срок оплаты',
                'sch_amount' => 'Сумма (сум)',
                'sch_note' => 'Примечание',
                'sch_upfront' => 'Первоначальный платёж',
                'sch_paid' => 'Оплачен',
                's4_title' => '4. ПРАВА И ОБЯЗАННОСТИ СТОРОН',
                's4_items' => [
                    'Продавец обязуется передать товар надлежащего качества в порядке, предусмотренном условиями заказа и публичной офертой маркетплейса «Kitobchi».',
                    'Покупатель обязуется своевременно и в полном объёме вносить платежи согласно графику (Приложение №1).',
                    'При просрочке платежа Продавец вправе производить повторные попытки списания с привязанных карт Покупателя.',
                    'При просрочке платежа доступ Покупателя к сервису рассрочки приостанавливается до полного погашения просроченной задолженности.',
                    'Покупатель вправе досрочно погасить рассрочку полностью или частично; при полном досрочном погашении непогашенная часть наценки не взимается.',
                    'При отмене/возврате товара в соответствии с правилами маркетплейса производится перерасчёт: стоимость возвращённого товара и соразмерная часть наценки зачитываются в счёт оставшихся платежей.',
                ],
                's5_title' => '5. ОТВЕТСТВЕННОСТЬ СТОРОН И ПРОСРОЧКА',
                's5_text' => 'При нарушении сроков оплаты более чем на 3 (три) банковских дня Продавец вправе направить Покупателю письменное требование (претензию) о погашении задолженности. При неисполнении требования Продавец вправе обратиться в суд по месту нахождения Продавца о принудительном взыскании задолженности; при этом расходы по взысканию (государственная пошлина, почтовые и иные документально подтверждённые расходы) возлагаются на Покупателя (ст. 324–333 ГК РУз). Стороны освобождаются от ответственности при обстоятельствах непреодолимой силы.',
                's6_title' => '6. ЭЛЕКТРОННАЯ ФОРМА ДОГОВОРА',
                's6_text' => 'Договор заключён в электронной форме и имеет юридическую силу, равную договору, составленному на бумажном носителе. Акцепт зафиксирован в информационной системе «Kitobchi»: дата и время — :acceptedAt, аккаунт — ID :userId, номер телефона — :phone. Электронный журнал системы признаётся достаточным доказательством заключения договора.',
                's7_title' => '7. ЗАКЛЮЧИТЕЛЬНЫЕ ПОЛОЖЕНИЯ',
                's7_text' => 'Во всём, что не предусмотрено настоящим договором, стороны руководствуются законодательством Республики Узбекистан и публичной офертой маркетплейса «Kitobchi» (kitobchi.com/legal). Споры разрешаются путём переговоров, а при недостижении согласия — в судебном порядке.',
                's8_title' => '8. РЕКВИЗИТЫ И ПОДПИСИ СТОРОН',
                'inn' => 'ИНН (СТИР)',
                'account' => 'Расчётный счёт',
                'mfo' => 'МФО',
                'bank' => 'Банк',
                'email' => 'E-mail',
                'accepted_mark' => 'Договор акцептован в электронной форме через приложение «Kitobchi»',
                'footer' => 'Документ сформирован автоматически информационной системой «Kitobchi» и действителен без собственноручной подписи (Закон РУз «Об электронной коммерции»).',
                'generated' => 'Сформировано',
                'som' => 'сум',
            ];
        }

        return [
            'title' => "TOVARLARNI BO'LIB TO'LASH (NASIYA) SHARTIDA OLDI-SOTDI SHARTNOMASI",
            'number' => 'Shartnoma №',
            'date' => 'Tuzilgan sana',
            'preamble' => "Ushbu shartnoma quyida ko'rsatilgan tomonlar o'rtasida O'zbekiston Respublikasi Fuqarolik kodeksining 367–370, 464-moddalari hamda O'zbekiston Respublikasining «Elektron tijorat to'g'risida»gi Qonuniga muvofiq «Kitobchi» mobil ilovasi orqali elektron shaklda tuzildi. Buyurtmani «Bo'lib to'lash (Split)» to'lov usuli bilan rasmiylashtirish va ilovada tasdiqlash ushbu ofertaning aksepti hisoblanadi (FK 370-modda, konklyudent harakatlar orqali aksept).",
            'seller' => 'SOTUVCHI (KREDITOR)',
            'buyer' => 'XARIDOR',
            'buyer_phone' => 'Telefon',
            'buyer_account' => 'Ilova akkaunti (ID)',
            'buyer_address' => 'Yetkazish manzili',
            's1_title' => '1. SHARTNOMA PREDMETI',
            's1_text' => "Sotuvchi quyida ko'rsatilgan tovarlarni (buyurtma №:orderId) Xaridor mulkiga topshiradi, Xaridor esa tovarlarni qabul qilib, ularning qiymatini ushbu shartnomada belgilangan tartib va muddatlarda bo'lib-bo'lib to'lash majburiyatini oladi (FK 464–465-moddalar). Tovarga mulk huquqi tovar topshirilgan paytdan Xaridorga o'tadi.",
            'goods' => 'Tovar nomi',
            'qty' => 'Soni',
            'price' => 'Narxi (so\'m)',
            's2_title' => "2. SHARTNOMA SUMMASI VA TO'LOV TARTIBI",
            's2_principal' => 'Tovarlar qiymati (asosiy qarz)',
            's2_delivery' => 'Yetkazib berish va qadoqlash',
            's2_interest' => "Bo'lib to'lash ustamasi (oyiga :monthly% × :months oy = :total%)",
            's2_total' => "JAMI TO'LANADIGAN SUMMA",
            's2_upfront' => "Boshlang'ich to'lov (rasmiylashtirishda yechiladi)",
            's2_text' => "To'lovlar Xaridorning ilovaga bog'langan bank kartasidan to'lov jadvaliga (1-ilova — shartnomaning ajralmas qismi) muvofiq avtomatik yechib olish yo'li bilan amalga oshiriladi. Xaridor to'lov sanalarida kartada yetarli mablag' bo'lishini ta'minlashi shart. Barcha summalar O'zbekiston so'mida ko'rsatilgan.",
            's3_title' => "3. TO'LOV JADVALI (1-ILOVA)",
            'sch_no' => '№',
            'sch_date' => "To'lov muddati",
            'sch_amount' => "Summa (so'm)",
            'sch_note' => 'Izoh',
            'sch_upfront' => "Boshlang'ich to'lov",
            'sch_paid' => "To'langan",
            's4_title' => '4. TOMONLARNING HUQUQ VA MAJBURIYATLARI',
            's4_items' => [
                "Sotuvchi tovarni buyurtma shartlari va «Kitobchi» marketpleysi ommaviy ofertasida nazarda tutilgan tartibda, tegishli sifatda topshirish majburiyatini oladi.",
                "Xaridor to'lovlarni jadvalga (1-ilova) muvofiq o'z vaqtida va to'liq hajmda amalga oshirish majburiyatini oladi.",
                "To'lov kechiktirilganda Sotuvchi Xaridorning bog'langan kartalaridan takroriy yechib olish urinishlarini amalga oshirishga haqli.",
                "To'lov kechiktirilganda Xaridorning bo'lib to'lash xizmatidan foydalanish huquqi muddati o'tgan qarz to'liq so'ndirilgunga qadar to'xtatib turiladi.",
                "Xaridor nasiyani muddatidan oldin to'liq yoki qisman so'ndirishga haqli; to'liq muddatidan oldin so'ndirilganda ustamaning to'lanmagan qismi undirilmaydi.",
                "Marketpleys qoidalariga muvofiq tovar bekor qilinganda/qaytarilganda qayta hisob-kitob o'tkaziladi: qaytarilgan tovar qiymati va ustamaning mutanosib qismi qolgan to'lovlar hisobiga o'tkaziladi.",
            ],
            's5_title' => "5. TOMONLARNING JAVOBGARLIGI VA MUDDATI O'TGAN QARZ",
            's5_text' => "To'lov muddati 3 (uch) bank kunidan ortiq buzilganda Sotuvchi Xaridorga qarzni so'ndirish to'g'risida yozma talabnoma (pretenziya) yuborishga haqli. Talabnoma bajarilmaganda Sotuvchi qarzni majburiy undirish uchun Sotuvchi joylashgan yerdagi sudga murojaat qilishga haqli; bunda undirish bilan bog'liq xarajatlar (davlat boji, pochta va boshqa hujjatlar bilan tasdiqlangan xarajatlar) Xaridor zimmasiga yuklatiladi (FK 324–333-moddalar). Fors-major holatlarida tomonlar javobgarlikdan ozod etiladi.",
            's6_title' => '6. SHARTNOMANING ELEKTRON SHAKLI',
            's6_text' => "Shartnoma elektron shaklda tuzilgan bo'lib, qog'ozda tuzilgan shartnoma bilan teng yuridik kuchga ega. Aksept «Kitobchi» axborot tizimida qayd etilgan: sana va vaqt — :acceptedAt, akkaunt — ID :userId, telefon raqami — :phone. Tizimning elektron jurnali shartnoma tuzilganligining yetarli dalili hisoblanadi.",
            's7_title' => '7. YAKUNIY QOIDALAR',
            's7_text' => "Ushbu shartnomada nazarda tutilmagan barcha masalalarda tomonlar O'zbekiston Respublikasi qonunchiligiga va «Kitobchi» marketpleysining ommaviy ofertasiga (kitobchi.com/legal) amal qiladilar. Nizolar muzokaralar yo'li bilan, kelishuvga erishilmaganda — sud tartibida hal etiladi.",
            's8_title' => '8. TOMONLARNING REKVIZITLARI VA IMZOLARI',
            'inn' => 'STIR',
            'account' => 'Hisob raqami',
            'mfo' => 'MFO',
            'bank' => 'Bank',
            'email' => 'E-mail',
            'accepted_mark' => "Shartnoma «Kitobchi» ilovasi orqali elektron shaklda akseptlangan",
            'footer' => "Hujjat «Kitobchi» axborot tizimi tomonidan avtomatik shakllantirilgan va shaxsiy imzosiz haqiqiy hisoblanadi (O'zR «Elektron tijorat to'g'risida»gi Qonuni).",
            'generated' => 'Shakllantirilgan vaqt',
            'som' => "so'm",
        ];
    }

    private function demandStrings(string $locale): array
    {
        if ($locale === 'ru') {
            return [
                'title' => 'ПРЕТЕНЗИЯ',
                'subtitle' => '(досудебное требование о погашении просроченной задолженности)',
                'to' => 'Кому',
                'address' => 'Адрес',
                'phone' => 'Телефон',
                'from' => 'От кого',
                'body_1' => 'Между Вами и :lender заключён договор купли-продажи товаров в рассрочку №:number от :date (заказ №:orderId в маркетплейсе «Kitobchi»), в соответствии с которым Вы приняли обязательство оплачивать стоимость товаров согласно графику платежей.',
                'body_2' => 'По состоянию на :letterDate обязательства по оплате исполняются Вами ненадлежащим образом. Просроченная задолженность составляет :overdueTotal сум, просрочка длится :maxDays календарных дней. Сведения о просроченных платежах:',
                'tbl_no' => '№ платежа',
                'tbl_due' => 'Срок оплаты',
                'tbl_amount' => 'Сумма (сум)',
                'tbl_days' => 'Дней просрочки',
                'total_overdue' => 'ИТОГО просроченная задолженность',
                'debt_info' => 'Общий остаток задолженности по договору составляет :remaining сум (из них просрочено :overdueTotal сум). Оплачено по договору: :paid сум из :total сум.',
                'demand_title' => 'ТРЕБУЮ:',
                'demand_text' => 'в срок не позднее :days календарных дней с даты получения настоящей претензии полностью погасить просроченную задолженность в размере :overdueTotal сум одним из способов: через приложение «Kitobchi» (автосписание/оплата картой) либо перечислением на нижеуказанный расчётный счёт с указанием номера договора :number.',
                'warning' => 'В случае неисполнения настоящего требования в указанный срок я буду вынужден обратиться в суд с иском о принудительном взыскании задолженности в соответствии со ст. 324–333 Гражданского кодекса Республики Узбекистан. При этом на Вас дополнительно будут возложены судебные расходы (государственная пошлина), а также иные документально подтверждённые расходы по взысканию. Сведения о неисполнении обязательств также влияют на Вашу возможность пользоваться рассрочкой в дальнейшем.',
                'requisites' => 'Реквизиты для оплаты',
                'inn' => 'ИНН (СТИР)',
                'account' => 'Расчётный счёт',
                'mfo' => 'МФО',
                'bank' => 'Банк',
                'contract_ref' => 'Назначение платежа: погашение задолженности по договору №:number',
                'sign' => 'С уважением,',
                'date_label' => 'Дата',
                'footer' => 'Претензия сформирована автоматически информационной системой «Kitobchi». Приложение: выписка по графику платежей из договора №:number.',
                'som' => 'сум',
                'days_unit' => 'дн.',
            ];
        }

        return [
            'title' => 'TALABNOMA (PRETENZIYA)',
            'subtitle' => "(muddati o'tgan qarzni so'ndirish to'g'risida suddan oldingi talab)",
            'to' => 'Kimga',
            'address' => 'Manzil',
            'phone' => 'Telefon',
            'from' => 'Kimdan',
            'body_1' => "Siz bilan :lender o'rtasida :date kuni tovarlarni bo'lib to'lash shartida oldi-sotdi shartnomasi №:number tuzilgan («Kitobchi» marketpleysidagi №:orderId buyurtma), unga ko'ra Siz tovarlar qiymatini to'lov jadvaliga muvofiq to'lash majburiyatini olgansiz.",
            'body_2' => "Holat :letterDate sanasiga ko'ra to'lov majburiyatlari Siz tomondan lozim darajada bajarilmayapti. Muddati o'tgan qarz :overdueTotal so'mni tashkil etadi, kechikish :maxDays kalendar kun davom etmoqda. Muddati o'tgan to'lovlar to'g'risida ma'lumot:",
            'tbl_no' => "To'lov №",
            'tbl_due' => "To'lov muddati",
            'tbl_amount' => "Summa (so'm)",
            'tbl_days' => 'Kechikish (kun)',
            'total_overdue' => "JAMI muddati o'tgan qarz",
            'debt_info' => "Shartnoma bo'yicha umumiy qarz qoldig'i :remaining so'mni tashkil etadi (shundan :overdueTotal so'mi muddati o'tgan). Shartnoma bo'yicha to'langan: :total so'mdan :paid so'mi.",
            'demand_title' => 'TALAB QILAMAN:',
            'demand_text' => "ushbu talabnoma olingan sanadan boshlab :days kalendar kundan kechiktirmay :overdueTotal so'm miqdoridagi muddati o'tgan qarzni quyidagi usullardan biri bilan to'liq so'ndirishingizni: «Kitobchi» ilovasi orqali (avtoyechish/karta bilan to'lov) yoki shartnoma raqami :number ni ko'rsatgan holda quyidagi hisob raqamiga o'tkazish yo'li bilan.",
            'warning' => "Ushbu talab ko'rsatilgan muddatda bajarilmagan taqdirda, O'zbekiston Respublikasi Fuqarolik kodeksining 324–333-moddalariga muvofiq qarzni majburiy undirish to'g'risidagi da'vo bilan sudga murojaat qilishga majbur bo'laman. Bunda sud xarajatlari (davlat boji) hamda undirish bilan bog'liq boshqa hujjatlar bilan tasdiqlangan xarajatlar qo'shimcha ravishda Siz zimmangizga yuklatiladi. Majburiyatlarning bajarilmaganligi to'g'risidagi ma'lumotlar kelgusida bo'lib to'lash xizmatidan foydalanish imkoniyatingizga ham ta'sir qiladi.",
            'requisites' => "To'lov uchun rekvizitlar",
            'inn' => 'STIR',
            'account' => 'Hisob raqami',
            'mfo' => 'MFO',
            'bank' => 'Bank',
            'contract_ref' => "To'lov maqsadi: №:number shartnoma bo'yicha qarzni so'ndirish",
            'sign' => 'Hurmat bilan,',
            'date_label' => 'Sana',
            'footer' => "Talabnoma «Kitobchi» axborot tizimi tomonidan avtomatik shakllantirilgan. Ilova: №:number shartnomaning to'lov jadvali ko'chirmasi.",
            'som' => "so'm",
            'days_unit' => 'kun',
        ];
    }
}
