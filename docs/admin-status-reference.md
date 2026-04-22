# Admin Status Reference

Bu hujjat `kitobchi` loyihasidagi admin panel va unga bog'liq backend logikada ishlatiladigan asosiy `status`, `paymentStatus`, `is_approved`, `moderation`, `type` va shunga o'xshash qiymatlarni jamlaydi.

Maqsad:

- admin paneldagi mappinglarni tez tushunish
- raqamli yoki qisqa kodli statuslarni adashtirmaslik
- biror modulda noto'g'ri mapping bo'lsa tez topish
- A122 UI o'zgarganda backend status ma'nolari yo'qolib ketmasligi

Muhim:

- Bu hujjat hozirgi kodga tayangan holda tuzilgan.
- Agar status logikasi o'zgarsa, shu fayl ham yangilanib borishi kerak.
- Quyidagi "Manba" ustunida status qayerdan olingani ko'rsatilgan.

## 1. Asosiy buyurtma (`Sold`)

Admin paneldagi asosiy order holati `Sold.status` maydonida harfli kodlar bilan yuradi.

| Qiymat | Ma'nosi | Admindagi label | Izoh | Manba |
|---|---|---|---|---|
| `A` | yangi / kutilmoqda | `Kutilmoqda` yoki ba'zi joylarda `Yangi` | boshlang'ich holat | `app/Http/Controllers/A122/OrderController.php`, `resources/views/a122/orders/*.blade.php` |
| `P` | qadoqlanmoqda | `Qadoqlanmoqda` | index tablarda ko'pincha `A` bilan birga pendingga kiradi | `app/Http/Controllers/A122/OrderController.php`, `app/Services/AdminOrderStatusSyncService.php` |
| `B` | yo'lda | `Yo'lda` | kuryerga berilgan / yetkazilmoqda | shu fayllar |
| `C` | yakunlangan | `Yakunlangan` yoki `Yetkazildi` | shu bosqichda `paymentStatus` ham `2` ga ko'tariladi | `app/Services/AdminOrderStatusSyncService.php` |
| `F` | bekor qilingan | `Bekor qilingan` | `cancelOrder()` ishga tushadi | `app/Services/AdminOrderStatusSyncService.php` |

### `Sold.paymentStatus`

| Qiymat | Ma'nosi | Admindagi label | Izoh | Manba |
|---|---|---|---|---|
| `0` | naqd / yetkazilganda to'lov | `Cash` | checkoutda `paymentStatus == false` bo'lsa yoziladi | `app/Http/Controllers/Api/PurchaseController.php` |
| `1` | online to'lov jarayonida | `Card` yoki `Kutilmoqda` | Payme kutilayotgan holat | `app/Http/Controllers/Api/PurchaseController.php`, `resources/views/a122/orders/index.blade.php` |
| `2` | to'langan | `Paid` yoki `To'langan` | Payme tasdiqlagandan keyin | `app/Http/Controllers/Api/PaymeController.php`, `resources/views/a122/orders/show.blade.php` |

Eslatma:

- Ayrim show sahifalarda `paymentStatus != 2` bo'lsa umumiy qilib `Kutilmoqda` yoki `Jarayonda` deb ko'rsatiladi.

## 2. Seller order (`SellerOrder`)

Seller order uchun asosiy haqiqat manbasi seller API oqimi hisoblanadi. Shu oqimga ko'ra seller order raqamli status bilan yuradi.

| Qiymat | Ma'nosi | Admindagi label | Izoh | Manba |
|---|---|---|---|---|
| `0` | to'lov jarayonida | `To'lov jarayonida` | checkout online bo'lsa seller API buni yashirib turadi (`status != 0`) | `app/Http/Controllers/Api/PurchaseController.php`, `app/Http/Controllers/Api/Seller/OrderController.php` |
| `1` | yangi buyurtma | `Yangi buyurtma` | to'lov tushganidan keyin sellerga kelgan aktiv buyurtma | `app/Services/OrderService.php`, `app/Http/Controllers/Api/Seller/OrderController.php` |
| `2` | kuryerga berildi | `Kuryerga berildi` | seller QR orqali courierga topshirgan | `app/Http/Controllers/Api/Seller/OrderController.php` |
| `3` | legacy holat | `Kuryerga berildi (legacy)` | hozirgi seller API ishlatmaydi, eski admin mapping izi | `app/Services/AdminOrderStatusSyncService.php` |
| `4` | bekor qilindi | `Bekor qilindi` | asosiy order ham cancel bo'ladi | shu fayl |

Muhim legacy nuqta:

- `0` qiymat legacy emas, real checkout oqimida ishlatiladi.
- Seller API order list, view va count endpointlari `status != 0` bilan ishlaydi, ya'ni online to'lov hali tushmagan order seller ilovasida ko'rinmaydi.
- `3` esa aksincha legacy holat bo'lib, seller API ning hozirgi oqimida ishlatilmaydi.

## 3. Courier order (`CourierOrder`)

| Qiymat | Ma'nosi | Admindagi label | Izoh | Manba |
|---|---|---|---|---|
| `pay_process` | to'lov jarayonida | `To'lov jarayonida` | online payment hali tasdiqlanmagan | `app/Services/AdminOrderStatusSyncService.php` |
| `pending` | kutilmoqda | `Kutilmoqda` | courier API da ham available, ham courier biriktirilgan lekin hali topshirilmagan holat sifatida uchraydi | `app/Http/Controllers/Api/Courier/CourierOrderController.php` |
| `in_delivery` | yetkazilmoqda | `Yo'lda` | faol delivery | shu fayl |
| `delivered` | yetkazildi | `Yetkazildi` | tugallangan | shu fayl |
| `rejected` | bekor qilindi | `Bekor qilindi` | asosiy order cancel bo'lishi mumkin | shu fayl |

Muhim:

- Courier API `confirmOrder()` ichida courier orderga `courier_id` biriktiriladi, lekin `status` hali ham `pending` bo'lib qoladi.
- `toCustomer()` esa `in_delivery` holatini kutadi.
- Demak amaldagi kodda `pending -> in_delivery` o'tishi admin/service oqimida bor, courier API oqimida esa bu o'tish to'liq izchil emas.

## 4. Statuslar o'rtasidagi sinxronizatsiya

Admin panelda order statusini o'zgartirsangiz, boshqa bog'liq orderlar ham sinxron bo'ladi.

### Main order -> seller order

| `Sold.status` | `SellerOrder.status` |
|---|---|
| `A` + `paymentStatus = 1` | `0` |
| `A` / `P` | `1` |
| `B` | `2` |
| `C` | `2` |
| `F` | `4` |

### Main order -> courier order

| `Sold.status` | `CourierOrder.status` |
|---|---|
| `A` / `P` + `paymentStatus = 1` | `pay_process` |
| `A` / `P` | `pending` |
| `B` | `in_delivery` |
| `C` | `delivered` |
| `F` | `rejected` |

### Courier order -> main order

| `CourierOrder.status` | `Sold.status` | `SellerOrder.status` |
|---|---|---|
| `pending` | `A` | `1` |
| `in_delivery` | `B` | `2` |
| `delivered` | `C` | `2` |
| `rejected` | cancel | `4` |

Manba: `app/Services/AdminOrderStatusSyncService.php`

## 5. Kitob va kanstovar moderatsiyasi

`Books.is_approved`, `Stationery.is_approved`, ehtimol `Gifts.is_approved` bir xil semantikada yuradi.

| Qiymat | Ma'nosi | Admindagi label | Manba |
|---|---|---|---|
| `0` | moderatsiyada | `Pending`, `Moderatsiyada` | `app/Http/Controllers/A122/BookController.php`, `StationeryController.php` |
| `1` | tasdiqlangan | `Active`, `Tasdiqlangan` | shu fayllar |
| `2` | rad etilgan | `Rejected`, `Rad etilgan` | shu fayllar |

Qo'shimcha `status` boolean:

| Maydon | Qiymat | Ma'nosi |
|---|---|---|
| `Books.status` | `1/true` | faol |
| `Books.status` | `0/false` | nofaol |
| `Stationery.status` | `1/true` | faol |
| `Stationery.status` | `0/false` | nofaol |

## 6. Seller va courier onboarding statuslari

### Seller (`Seller.status`)

| Qiymat | Ma'nosi | Admindagi label | Manba |
|---|---|---|---|
| `pending` | ko'rib chiqilmoqda | `Kutilmoqda` | `app/Http/Controllers/A122/SellerController.php` |
| `approved` | tasdiqlangan | `Tasdiqlangan` | shu fayl |
| `rejected` | rad etilgan | `Rad etilgan` | shu fayl |

### Courier (`Couriers.status`)

| Qiymat | Ma'nosi | Admindagi label | Manba |
|---|---|---|---|
| `pending` | ko'rib chiqilmoqda | `Kutilmoqda` | `app/Http/Controllers/A122/CourierController.php` |
| `approved` | tasdiqlangan | `Tasdiqlangan` | shu fayl |
| `rejected` | rad etilgan | `Rad etilgan` | shu fayl |

Eslatma:

- `app/Models/Couriers.php` ichida `return (int) $this->status === 1;` degan eski helper izi bor. Lekin A122 panel logikasi string statuslar bilan ishlaydi: `approved/pending/rejected`.

## 7. Seller transaction

| Qiymat | Ma'nosi | Admindagi label | Manba |
|---|---|---|---|
| `pending` | kutmoqda | `Kutilmoqda` | `app/Models/SellerTransaction.php`, `A122/TransactionController.php` |
| `approved` | tasdiqlangan | `Tasdiqlangan` | shu fayllar |
| `rejected` | rad etilgan | `Rad etildi` | shu fayllar |

## 8. Courier transaction

Kod bo'yicha asosiy ishlatiladigan qiymatlar:

| Qiymat | Ma'nosi | Izoh | Manba |
|---|---|---|---|
| `pending` | kutmoqda | payout request yuborilgan | `app/Http/Controllers/Api/Courier/CourierTransactionController.php` |
| `approved` | tasdiqlangan | dashboard va courier show shu qiymatga tayangan | `app/Http/Controllers/A122/CourierController.php` |
| `rejected` | rad etilgan | bekor qilingan payout | `app/Http/Controllers/Api/Courier/CourierTransactionController.php` |

## 9. Promokod (`Promocode`)

### `Promocode.status`

| Qiymat | Ma'nosi | Admindagi segment | Manba |
|---|---|---|---|
| `1` / `true` | faol | `active` | `app/Http/Controllers/A122/PromocodeController.php`, `app/Models/Promocode.php` |
| `0` / `false` | nofaol | `expired` segmentiga ham tushishi mumkin | shu fayllar |

Muhim:

- Adminda `expired` segment faqat `status = 0` degani emas.
- `expires_at <= now()` bo'lgan promokod ham `expired` hisoblanadi, hatto `status = 1` bo'lsa ham.

### `Promocode.type`

| Qiymat | Ma'nosi | Manba |
|---|---|---|
| `percent` | foizli chegirma | `app/Http/Controllers/A122/PromocodeController.php` |
| `fixed` | qat'iy summa | shu fayl |

## 10. Siyosatlar (`Policy`)

### `Policy.is_active`

| Qiymat | Ma'nosi | Admindagi label | Manba |
|---|---|---|---|
| `1/true` | faol | `Faol` | `app/Http/Controllers/A122/PolicyController.php`, `app/Models/Policy.php` |
| `0/false` | nofaol | `Nofaol` | shu fayllar |

### `Policy.show_in_app`

| Qiymat | Ma'nosi | Izoh |
|---|---|---|
| `1/true` | ilovada ko'rsatiladi | public/mobile uchun |
| `0/false` | faqat saqlanadi | ilovada chiqmasligi mumkin |

## 11. Reklama (`SellerAd`)

### `SellerAd.moderation`

| Qiymat | Ma'nosi | Admindagi label | Manba |
|---|---|---|---|
| `pending` | ko'rib chiqilmoqda | `Kutilmoqda` | `app/Http/Controllers/A122/SellerAdController.php`, `resources/views/a122/ads/index.blade.php` |
| `approved` | tasdiqlangan | `Tasdiqlangan` | shu fayllar |
| `rejected` | rad etilgan | `Rad etilgan` | shu fayllar |

### `SellerAd.paymentStatus`

Ko'ringan mapping:

| Qiymat | Ma'nosi | Admindagi label | Manba |
|---|---|---|---|
| `pending` yoki `null` | kutilmoqda | `Kutilmoqda` | `resources/views/a122/ads/index.blade.php`, `show.blade.php` |
| `paid` | to'langan | `To'langan` | shu blade fayllar |
| `failed` | muvaffaqiyatsiz | `Muvaffaqiyatsiz` | shu blade fayllar |

### `SellerAd.type`

| Qiymat | Ma'nosi | Manba |
|---|---|---|
| `top_banner` | yuqori banner | `app/Http/Controllers/A122/SellerAdController.php` |
| `middle_banner` | o'rta banner | shu fayl |
| `story` | story joylashuvi | shu fayl |

## 12. Gift certificate

| Qiymat | Ma'nosi | Admin / helper label | Manba |
|---|---|---|---|
| `pending_payment` | to'lov kutilmoqda | `To'lov kutilmoqda` | `app/Models/GiftCertificate.php` |
| `paid` | to'langan, hali aktivlanmagan | `To'langan (aktivlanmagan)` | shu fayl |
| `active` | faol | `Faol` | shu fayl |
| `used` | ishlatilgan | `Ishlatilgan` | shu fayl |
| `cancelled` | bekor qilingan | `Bekor qilingan` | shu fayl |

Qo'shimcha:

- `STATUS_SENT = 'active'` backward compatibility uchun alias.

## 13. Mystery Box

### Subscription (`MysteryBoxSubscription.status`)

| Qiymat | Ma'nosi | Label | Manba |
|---|---|---|---|
| `pending_payment` | to'lov kutilmoqda | `To'lov kutilmoqda` | `app/Models/MysteryBoxSubscription.php` |
| `active` | faol | `Faol` | shu fayl |
| `paused` | to'xtatilgan | `To'xtatilgan` | shu fayl |
| `cancelled` | bekor qilingan | `Bekor qilindi` | shu fayl |
| `completed` | yakunlangan | `Yakunlandi` | shu fayl |

### Delivery (`MysteryBoxDelivery.status`)

| Qiymat | Ma'nosi | Label | Manba |
|---|---|---|---|
| `pending` | kutilmoqda | `Kutilmoqda` | `app/Models/MysteryBoxDelivery.php` |
| `preparing` | tayyorlanmoqda | `Tayyorlanmoqda` | shu fayl |
| `shipped` | jo'natildi | `Jo'natildi` | shu fayl |
| `delivered` | yetkazildi | `Yetkazildi` | shu fayl |

### Mystery Box plan (`MysteryBoxPlan.is_active`)

| Qiymat | Ma'nosi | Manba |
|---|---|---|
| `1/true` | faol plan | `app/Models/MysteryBoxPlan.php` |
| `0/false` | nofaol plan | shu model va `A122/MysteryBoxController.php` |

## 14. Karyera arizalari (`CareerApplication`)

### `CareerApplication.type`

| Qiymat | Ma'nosi | Manba |
|---|---|---|
| `vacancy` | vakansiyaga ariza | `app/Models/CareerApplication.php` |
| `inquiry` | umumiy murojaat | shu model |

### `CareerApplication.status`

| Qiymat | Ma'nosi | Admindagi label | Manba |
|---|---|---|---|
| `new` | yangi | `Yangi` | `app/Models/CareerApplication.php`, `A122/CareerApplicationController.php` |
| `reviewed` | ko'rilgan | `Ko‘rilgan` | shu fayllar |
| `replied` | javob berilgan | `Javob yuborilgan` | shu fayllar |
| `closed` | yopilgan | `Yopilgan` | shu fayllar |

## 15. Shikoyatlar (`Report`)

### `Report.status`

| Qiymat | Ma'nosi | Admindagi label | Manba |
|---|---|---|---|
| `pending` | ko'rib chiqilmagan | `Pending` | `app/Http/Controllers/A122/ComplaintController.php` |
| `reviewed` | ko'rib chiqilgan | `Reviewed` | shu fayl |
| `dismissed` | rad etilgan / yopilgan | `Dismissed` | shu fayl |

### `Report.reportable_type`

Hozir show sahifasida explicit ishlatilayotgan turlar:

| Qiymat | Nimaga tegishli | Manba |
|---|---|---|
| `conversation_message` | chat xabari | `app/Http/Controllers/A122/ComplaintController.php` |
| `book_club` | book club posti | shu fayl |

## 16. Book Club moderatsiyasi

### Post UGC status (`BookClub.kangaroo_post_ugc_status`)

| Qiymat | Ma'nosi | Manba |
|---|---|---|
| `pending_admin` | admin bahosini kutmoqda | `app/Http/Controllers/A122/BookClubController.php`, `resources/views/a122/book-club/show.blade.php` |
| `admin_scored` | admin baholab bo'lgan | shu fayllar |

### Comment UGC status (`BookClubComment.kangaroo_ugc_status`)

| Qiymat | Ma'nosi | Manba |
|---|---|---|
| `pending_admin` | admin bahosini kutmoqda | `app/Http/Controllers/A122/BookClubController.php`, `resources/views/a122/book-club/show.blade.php` |
| `admin_scored` | admin baholab bo'lgan | shu fayllar |

Qo'shimcha maydonlar:

- `is_deleted` — post yashirilgan/o'chirilganligini bildiradi
- `repost` — repost ekanligini bildiradi

## 17. Faol/Nofaol boolean maydonlar

Quyidagi modullarda `is_active` yoki unga o'xshash boolean maydonlar ishlatiladi:

| Modul | Maydon | `1/true` | `0/false` | Manba |
|---|---|---|---|---|
| `Policy` | `is_active` | faol | nofaol | `app/Models/Policy.php` |
| `Policy` | `show_in_app` | ilovada ko'rinadi | ko'rinmaydi | `app/Models/Policy.php` |
| `BookCategories` | `is_active` | faol | yashirin/nofaol | `A122/BookCategoryController.php` |
| `StationeryCategory` | `is_active` | faol | yashirin/nofaol | `A122/StationeryCategoryController.php` |
| `Vacancy` | `is_active` | faol | yashirin | `A122/VacancyController.php`, `app/Models/Vacancy.php` |
| `ApiClient` | `is_active` | faol | nofaol | `A122/ApiClientController.php`, `app/Models/ApiClient.php` |
| `Admin` | `is_active` | faol | bloklangan | `A122/AdminController.php`, `app/Models/Admin.php` |
| `MysteryBoxPlan` | `is_active` | faol | nofaol | `app/Models/MysteryBoxPlan.php` |
| `BotOperator` | `is_active` | faol operator | nofaol | `A122/SupportController.php` |

## 18. Qo'shimcha ehtiyot bo'ladigan joylar

1. `Sold.status` harfli kodlar bilan yuradi, integer emas.
2. `Sold.paymentStatus` `0/1/2` ko'rinishida, lekin UI ba'zi joylarda buni 2 ta holatga soddalashtiradi.
3. `SellerOrder.status` uchun admin mapping `1..4`, lekin checkout oqimida `0` uchrashi mumkin.
4. `Couriers` modelida eski `int` asosli helper izi bor, ammo A122 panel realda string statuslarga tayangan.
5. `Promocode.status = 1` bo'lsa ham, `expires_at` o'tib ketgan bo'lsa admin uni expiredga chiqaradi.
6. `SellerAd.paymentStatus` uchun hozir ko'rinib turgan mapping blade ichida, model constantlari yo'q.

## 19. Tavsiya

Kelajakda statuslar bilan ishlashni yanada xavfsiz qilish uchun:

- model constantlari yoki PHP enumlar joriy qilish
- admin blade ichidagi `match` mappinglarni service/helper ga ko'chirish
- har bir modul uchun `getStatusLabelAttribute()` va `getStatusColorAttribute()` ni standartlashtirish
- bu faylni status o'zgargan har bir PR da yangilash

## 20. Client App Verification

Quyidagi bo'lim statuslar backendda qanday saqlanishi bilan emas, mobil ilovalar ularni realda qanday ishlatayotgani bilan tasdiqlangan.

### 20.1 `kitobchi-app` (`../kitobchi-app`)

User app order statuslari `Sold.status` bo'yicha quyidagicha ishlatiladi:

| Qiymat | Appdagi ma'nosi | Manba |
|---|---|---|
| `A` | pending | `lib/Pages/ProfilePage/Purchases/ViewPurchase.dart`, `Purchases.dart` |
| `P` | packing | shu fayllar |
| `B` | shipped / yo'lda | shu fayllar |
| `C` | delivered | shu fayllar |
| `F` | canceled | shu fayllar |

User app ichida:

- `A` va `P` bitta umumiy `in progress` guruhiga birlashtiriladi
- `paymentStatus == 1 && status == A` bo'lsa alohida to'lov banneri ko'rsatiladi

Xulosa:

- `Sold.status` bo'yicha bizning hozirgi admin mapping user app bilan mos
- `A` va `P` ni adminda ham ma'nodosh, lekin alohida bosqichlar deb ko'rish to'g'ri

### 20.2 `kitobchibusiness` (`../kitobchibusiness`)

Business app `OrderModel.statusText` ichida quyidagi mapping bor:

| Qiymat | Hozirgi app labeli | Manba |
|---|---|---|
| `1` | `Yangi buyurtma` | `lib/models/order_model.dart` |
| `2` | `Qabul qilindi` | shu fayl |
| `3` | `Yakunlandi` | shu fayl |
| `4` | `Bekor qilindi` | shu fayl |

Lekin seller API oqimi bo'yicha real holat:

| Qiymat | Real ma'no |
|---|---|
| `0` | to'lov jarayonida |
| `1` | yangi buyurtma |
| `2` | kuryerga berildi / courier oldi |
| `3` | legacy |
| `4` | bekor qilindi |

Xulosa:

- `kitobchibusiness` ichidagi seller order label mapping eskirgan
- ayniqsa `2 = Qabul qilindi` va `3 = Yakunlandi` hozirgi backend oqimiga mos emas
- admin panel endi business appning eski mappingiga emas, seller API ning real oqimiga moslangan

### 20.3 `kitobchiexpress` (`../kitobchiexpress`)

Courier app `CourierOrder.status` bo'yicha:

| Qiymat | Appdagi ma'nosi | Manba |
|---|---|---|
| `pending` | `Kutilmoqda` | `lib/screens/order/order_list.dart` |
| `in_delivery` | `Yetkazilmoqda` | shu fayl |
| `delivered` | `Yetkazildi` | shu fayl |
| `rejected` | `Bekor qilindi` | filter labelda shunday | shu fayl |

Courier app ichida yana `item.orderStatus` bo'yicha seller kesimidagi shop status ko'rsatiladi:

| Qiymat | Appdagi ma'nosi | Manba |
|---|---|---|
| `1` | `KUTILMOQDA` | `lib/screens/order/order_view.dart` |
| `2` | `OLINDI` | shu fayl |
| `3` | `BEKOR QILINDI` | shu fayl |

Muhim tafovutlar:

- Courier app filter `rejected` ishlatadi, lekin `_statusChip()` ichida `cancelled` case bor. Bu app ichida o'zida nomuvofiqlik.
- Courier API `confirmOrder()` dan keyin `CourierOrder.status` hali `pending` qoladi.
- Courier app `toCustomer()` oqimi esa `in_delivery` ni kutadi.

Xulosa:

- Admin panelda courier statuslar `pay_process / pending / in_delivery / delivered / rejected` bo'yicha alohida turishi to'g'ri
- Courier appning `rejected` vs `cancelled` nomuvofiqligi admin mapping emas, client appning o'zidagi tafovut

## 21. Yakuniy Haqiqat Manbasi

Agar bir nechta qatlam bir-biriga zid ko'rinsa, ustuvorlik quyidagicha olinadi:

1. Real API controller oqimi
2. `OrderService` va status sync service
3. Client ilovalar ichidagi real ishlatilayotgan status mapping
4. Admin blade yoki dashboard label mapping

Shu sabab:

- user order uchun `Sold.status` haqiqat manbasi
- seller order uchun seller API oqimi haqiqat manbasi
- courier order uchun courier API oqimi va `CourierOrder.status` haqiqat manbasi
- admin UI faqat shu qatlamlarga moslashishi kerak, aksincha emas
