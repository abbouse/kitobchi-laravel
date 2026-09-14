const fmt = (n: number) => new Intl.NumberFormat('uz-UZ').format(n);
export { fmt };

// ====== UMUMIY STATISTIKA ======
export const stats = {
  revenue: 48_720_500,
  orders: 1284,
  customers: 3412,
  books: 2156,
  stationeries: 847,
  pending: 38,
  grossRevenue: 48_720_500,
  cogs: 28_450_200,
  grossProfit: 20_270_300,
  operatingExpenses: 8_140_000,
  marketingSpend: 4_250_000,
  shippingCost: 1_890_000,
  netProfit: 5_990_300,
  refunds: 842_500,
  cancelled: 1_240_000,
  avgOrderValue: 37_940,
  conversionRate: 3.42,
  cartAbandonment: 68.2,
  returnRate: 4.8,
  customerRetention: 72.5,
  cac: 18_500,
  ltv: 485_000,
  ltvCacRatio: 26.2,
  roas: 4.8,
  roi: 32.4,
  grossMargin: 41.6,
  netMargin: 12.3,
};

export const salesByMonth = [
  { month: 'Yan', revenue: 3200000, orders: 180, profit: 412000, loss: 84000, returns: 12 },
  { month: 'Fev', revenue: 3800000, orders: 210, profit: 488000, loss: 95000, returns: 15 },
  { month: 'Mar', revenue: 4100000, orders: 240, profit: 525000, loss: 102000, returns: 18 },
  { month: 'Apr', revenue: 3900000, orders: 225, profit: 498000, loss: 110000, returns: 20 },
  { month: 'May', revenue: 4600000, orders: 260, profit: 595000, loss: 88000, returns: 14 },
  { month: 'Iyun', revenue: 5200000, orders: 295, profit: 685000, loss: 75000, returns: 11 },
  { month: 'Iyul', revenue: 5800000, orders: 320, profit: 768000, loss: 92000, returns: 16 },
  { month: 'Avg', revenue: 6100000, orders: 340, profit: 812000, loss: 68000, returns: 9 },
  { month: 'Sen', revenue: 5700000, orders: 310, profit: 742000, loss: 78000, returns: 13 },
  { month: 'Okt', revenue: 6400000, orders: 360, profit: 845000, loss: 55000, returns: 8 },
  { month: 'Noy', revenue: 6900000, orders: 390, profit: 912000, loss: 48000, returns: 7 },
  { month: 'Dek', revenue: 7200000, orders: 410, profit: 958000, loss: 52000, returns: 9 },
];

export const hourlySales = Array.from({ length: 24 }, (_, h) => {
  const peak = h >= 10 && h <= 22 ? 1 : 0.3;
  const lunch = h >= 12 && h <= 14 ? 0.4 : 0;
  const evening = h >= 19 && h <= 21 ? 0.5 : 0;
  const base = 2 + Math.floor(Math.random() * 6);
  const orders = Math.floor((base + lunch * 8 + evening * 10) * peak);
  return { hour: `${String(h).padStart(2, '0')}:00`, orders, revenue: orders * (25000 + Math.floor(Math.random() * 25000)) };
});

export const weeklySales = [
  { day: 'Du', revenue: 1240000, orders: 180 },
  { day: 'Se', revenue: 1420000, orders: 210 },
  { day: 'Ch', revenue: 1580000, orders: 240 },
  { day: 'Pa', revenue: 1490000, orders: 225 },
  { day: 'Ju', revenue: 1720000, orders: 265 },
  { day: 'Sh', revenue: 2180000, orders: 340 },
  { day: 'Ya', revenue: 1980000, orders: 310 },
];

export const categoryShare = [
  { name: 'Badiiy', value: 38, color: '#0B0342', revenue: 18_513_790, profit: 7_405_516, margin: 40, returns: 4.2, units: 892 },
  { name: 'Ilmiy', value: 22, color: '#4A3A7A', revenue: 10_718_510, profit: 4_823_329, margin: 45, returns: 2.8, units: 412 },
  { name: 'Bolalar', value: 18, color: '#8E3A63', revenue: 8_769_690, profit: 3_507_876, margin: 40, returns: 5.1, units: 624 },
  { name: 'Kanselyariya', value: 14, color: '#0F6A46', revenue: 6_820_870, profit: 2_728_348, margin: 40, returns: 3.5, units: 1845 },
  { name: 'Darslik', value: 8, color: '#8A5709', revenue: 3_897_640, profit: 1_169_292, margin: 30, returns: 6.8, units: 312 },
];

export const topBooks = [
  { id: 1, title: "O'tkan kunlar", author: 'Abdulla Qodiriy', price: 85000, cost: 52000, stock: 42, sold: 284, sold30: 48, rating: 4.9, revenue: 24_140_000, profit: 9_402_000, cover: '📕' },
  { id: 2, title: 'Mehrobdan chayon', author: 'Abdulla Qodiriy', price: 72000, cost: 45000, stock: 18, sold: 219, sold30: 38, rating: 4.8, revenue: 15_768_000, profit: 5_913_000, cover: '📗' },
  { id: 3, title: 'Sarob', author: 'Abdulla Qahhor', price: 65000, cost: 41000, stock: 27, sold: 187, sold30: 31, rating: 4.7, revenue: 12_155_000, profit: 4_488_000, cover: '📘' },
  { id: 4, title: 'Kecha va kunduz', author: "Cho'lpon", price: 78000, cost: 48000, stock: 31, sold: 176, sold30: 28, rating: 4.9, revenue: 13_728_000, profit: 5_280_000, cover: '📙' },
  { id: 5, title: "Ulug'bek xazinasi", author: "O'. Yoqubov", price: 55000, cost: 35000, stock: 12, sold: 154, sold30: 22, rating: 4.6, revenue: 8_470_000, profit: 3_080_000, cover: '📕' },
  { id: 6, title: "Yulduzli tunlar", author: 'P. Qodirov', price: 95000, cost: 58000, stock: 8, sold: 143, sold30: 26, rating: 4.9, revenue: 13_585_000, profit: 5_291_000, cover: '📗' },
];

export const stationeries = [
  { id: 1, name: 'Parker ruchka', category: 'Ruchka', price: 245000, cost: 155000, stock: 34, sold: 89, sold30: 18, revenue: 21_805_000, profit: 8_010_000, icon: '✒️' },
  { id: 2, name: 'Moleskine daftari', category: 'Daftar', price: 185000, cost: 110000, stock: 56, sold: 124, sold30: 24, revenue: 22_940_000, profit: 9_300_000, icon: '📓' },
  { id: 3, name: 'Stabilo marker 10pk', category: 'Marker', price: 95000, cost: 62000, stock: 78, sold: 210, sold30: 42, revenue: 19_950_000, profit: 6_930_000, icon: '🖍️' },
  { id: 4, name: 'Faber-Castell qalam', category: 'Qalam', price: 42000, cost: 26000, stock: 120, sold: 342, sold30: 68, revenue: 14_364_000, profit: 5_472_000, icon: '✏️' },
  { id: 5, name: "A4 qog'oz 500 varaq", category: "Qog'oz", price: 78000, cost: 55000, stock: 45, sold: 156, sold30: 31, revenue: 12_168_000, profit: 3_588_000, icon: '📄' },
  { id: 6, name: 'Ofis papkasi', category: 'Papkalar', price: 28000, cost: 17000, stock: 89, sold: 97, sold30: 19, revenue: 2_716_000, profit: 1_067_000, icon: '📁' },
];

export const authors = [
  { id: 1, name: 'Abdulla Qodiriy', country: "O'zbekiston", books: 12, followers: 18420, revenue: 45_280_000, profit: 17_210_000, bio: 'Jadid adabiyotining yirik vakili' },
  { id: 2, name: "Cho'lpon", country: "O'zbekiston", books: 8, followers: 14230, revenue: 28_450_000, profit: 10_820_000, bio: 'Shoirlar sultoni' },
  { id: 3, name: 'Abdulla Qahhor', country: "O'zbekiston", books: 15, followers: 11890, revenue: 22_180_000, profit: 8_450_000, bio: 'Zamonaviy nasr ustasi' },
  { id: 4, name: 'Pirimqul Qodirov', country: "O'zbekiston", books: 9, followers: 9540, revenue: 18_920_000, profit: 7_120_000, bio: 'Tarixiy roman yozuvchisi' },
  { id: 5, name: "O'tkir Hoshimov", country: "O'zbekiston", books: 11, followers: 12340, revenue: 15_680_000, profit: 5_890_000, bio: "Xalq yozuvchisi" },
  { id: 6, name: 'Erkin Vohidov', country: "O'zbekiston", books: 14, followers: 16780, revenue: 12_450_000, profit: 4_680_000, bio: "She'riyat darg'asi" },
];

export const publishers = [
  { id: 1, name: 'Sharq nashriyoti', city: 'Toshkent', books: 420, revenue: 125_400_000, profit: 48_200_000, contact: '+998 71 233 44 55', rating: 4.8, hubCount: 3 },
  { id: 2, name: "O'zbekiston nashriyoti", city: 'Toshkent', books: 380, revenue: 98_700_000, profit: 36_800_000, contact: '+998 71 244 55 66', rating: 4.7, hubCount: 2 },
  { id: 3, name: 'Yangi asr avlodi', city: 'Toshkent', books: 215, revenue: 64_200_000, profit: 24_100_000, contact: '+998 71 255 66 77', rating: 4.6, hubCount: 2 },
  { id: 4, name: 'Adabiyot uchqunlari', city: 'Samarqand', books: 142, revenue: 42_800_000, profit: 15_600_000, contact: '+998 66 222 33 44', rating: 4.5, hubCount: 1 },
  { id: 5, name: "G'afur G'ulom nashriyoti", city: 'Toshkent', books: 298, revenue: 88_500_000, profit: 32_900_000, contact: '+998 71 266 77 88', rating: 4.9, hubCount: 3 },
];

export const users = [
  { id: 1, name: 'Aziza Karimova', email: 'aziza@mail.uz', phone: '+998 90 123 45 67', orders: 18, spent: 2_450_000, status: 'Active', role: 'Customer', segment: 'Loyal' },
  { id: 2, name: 'Bobur Aliyev', email: 'bobur@mail.uz', phone: '+998 91 234 56 78', orders: 7, spent: 890_000, status: 'Active', role: 'Customer', segment: 'New' },
  { id: 3, name: 'Dilnoza Rashidova', email: 'dilnoza@mail.uz', phone: '+998 93 345 67 89', orders: 32, spent: 4_120_000, status: 'VIP', role: 'Customer', segment: 'VIP' },
  { id: 4, name: 'Jamshid Tursunov', email: 'jamshid@mail.uz', phone: '+998 94 456 78 90', orders: 4, spent: 320_000, status: 'Active', role: 'Customer', segment: 'New' },
  { id: 5, name: 'Malika Usmonova', email: 'malika@mail.uz', phone: '+998 95 567 89 01', orders: 15, spent: 1_780_000, status: 'Active', role: 'Seller', segment: 'Active Seller' },
  { id: 6, name: 'Nodir Sobirov', email: 'nodir@mail.uz', phone: '+998 97 678 90 12', orders: 0, spent: 0, status: 'Blocked', role: 'Customer', segment: 'Churned' },
  { id: 7, name: 'Shaxlo Yusupova', email: 'shaxlo@mail.uz', phone: '+998 99 789 01 23', orders: 22, spent: 2_980_000, status: 'VIP', role: 'Customer', segment: 'VIP' },
];

export const orders = [
  { id: '#ORD-8842', customer: 'Aziza Karimova', items: 3, total: 385000, profit: 148000, status: 'Delivered', date: '2026-01-14', payment: 'Karta', channel: 'Mobile', hubId: 'hub-1', sellerId: 'sel-1' },
  { id: '#ORD-8843', customer: 'Bobur Aliyev', items: 1, total: 95000, profit: 38000, status: 'Shipping', date: '2026-01-14', payment: 'Naqd', channel: 'iOS', hubId: 'hub-2', sellerId: 'sel-2' },
  { id: '#ORD-8844', customer: 'Dilnoza Rashidova', items: 5, total: 612000, profit: 244800, status: 'Processing', date: '2026-01-14', payment: 'Karta', channel: 'Android', hubId: 'hub-1', sellerId: 'sel-3' },
  { id: '#ORD-8845', customer: 'Jamshid Tursunov', items: 2, total: 156000, profit: 62400, status: 'Pending', date: '2026-01-13', payment: 'Naqd', channel: 'Android', hubId: 'hub-3', sellerId: 'sel-1' },
  { id: '#ORD-8846', customer: 'Malika Usmonova', items: 4, total: 428000, profit: 171200, status: 'Delivered', date: '2026-01-13', payment: 'Karta', channel: 'iOS', hubId: 'hub-2', sellerId: 'sel-4' },
  { id: '#ORD-8847', customer: 'Shaxlo Yusupova', items: 2, total: 238000, profit: 95200, status: 'Cancelled', date: '2026-01-12', payment: 'Karta', channel: 'Android', hubId: 'hub-1', sellerId: 'sel-5' },
  { id: '#ORD-8848', customer: 'Aziza Karimova', items: 6, total: 745000, profit: 298000, status: 'Shipping', date: '2026-01-12', payment: 'Naqd', channel: 'Android', hubId: 'hub-4', sellerId: 'sel-3' },
];

export const recentActivity = [
  { icon: 'bi-bag-check', color: '#0F6A46', text: "Yangi buyurtma #ORD-8848 qabul qilindi", time: '2 daqiqa oldin', amount: 745000 },
  { icon: 'bi-person-plus', color: '#0B0342', text: "Yangi mijoz ro'yxatdan o'tdi", time: '8 daqiqa oldin', amount: 0 },
  { icon: 'bi-truck', color: '#8A5709', text: 'Buyurtma #ORD-8843 Hub-2 ga yetkazildi', time: '15 daqiqa oldin', amount: 0 },
  { icon: 'bi-star', color: '#8E3A63', text: 'Dilnoza R. 5 yulduzli sharh qoldirdi', time: '22 daqiqa oldin', amount: 0 },
  { icon: 'bi-box-seam', color: '#4A3A7A', text: "Hub-1 dan 24 ta buyurtma pochta orqali jo'natildi", time: '30 daqiqa oldin', amount: 0 },
  { icon: 'bi-cash-stack', color: '#0F6A46', text: "Seller 'Kitob olami' ga 2,450,000 so'm to'landi", time: '45 daqiqa oldin', amount: 2450000 },
];

export const regionalData = [
  { region: 'Toshkent shahri', orders: 486, revenue: 18_450_200, profit: 7_380_080, customers: 1240, avgOrder: 37_960, returns: 3.8 },
  { region: 'Samarqand', orders: 164, revenue: 6_210_400, profit: 2_484_160, customers: 485, avgOrder: 37_870, returns: 4.2 },
  { region: 'Farg\'ona', orders: 142, revenue: 5_420_600, profit: 2_168_240, customers: 382, avgOrder: 38_180, returns: 5.1 },
  { region: 'Buxoro', orders: 118, revenue: 4_480_500, profit: 1_792_200, customers: 312, avgOrder: 37_970, returns: 4.6 },
  { region: 'Andijon', orders: 96, revenue: 3_680_200, profit: 1_472_080, customers: 248, avgOrder: 38_335, returns: 5.4 },
  { region: 'Namangan', orders: 84, revenue: 3_190_800, profit: 1_276_320, customers: 198, avgOrder: 37_985, returns: 4.8 },
  { region: 'Qashqadaryo', orders: 72, revenue: 2_740_300, profit: 1_096_120, customers: 164, avgOrder: 38_060, returns: 5.8 },
  { region: 'Xorazm', orders: 62, revenue: 2_360_700, profit: 944_280, customers: 142, avgOrder: 38_075, returns: 4.9 },
  { region: 'Surxondaryo', orders: 54, revenue: 2_060_400, profit: 824_160, customers: 128, avgOrder: 38_155, returns: 5.2 },
  { region: 'Jizzax', orders: 48, revenue: 1_830_200, profit: 732_080, customers: 108, avgOrder: 38_130, returns: 5.0 },
  { region: 'Sirdaryo', orders: 32, revenue: 1_220_800, profit: 488_320, customers: 68, avgOrder: 38_150, returns: 5.4 },
  { region: 'Navoiy', orders: 26, revenue: 980_300, profit: 392_120, customers: 58, avgOrder: 37_705, returns: 4.6 },
];

export const customerSegments = [
  { segment: 'VIP (Top 5%)', count: 171, share: 5.0, revenue: 14_840_000, avgSpent: 868_000, orders: 18.2, retention: 94.5, color: '#8A5709' },
  { segment: 'Loyal', count: 892, share: 26.1, revenue: 16_920_000, avgSpent: 189_700, orders: 5.8, retention: 82.4, color: '#0F6A46' },
  { segment: 'Occasional', count: 1428, share: 41.9, revenue: 11_420_000, avgSpent: 79_970, orders: 2.1, retention: 58.6, color: '#24509B' },
  { segment: 'New (30 kun)', count: 384, share: 11.3, revenue: 3_840_000, avgSpent: 10_000, orders: 1.0, retention: 0, color: '#6B4E8E' },
  { segment: 'Churned (60+ kun)', count: 537, share: 15.7, revenue: 1_890_000, avgSpent: 0, orders: 0, retention: 0, color: '#A32A2E' },
];

export const topPerformers = {
  best: [
    { product: "O'tkan kunlar", revenue: 24_140_000, growth: 28.4 },
    { product: 'Moleskine daftari', revenue: 22_940_000, growth: 42.1 },
    { product: 'Parker ruchka', revenue: 21_805_000, growth: 18.6 },
    { product: 'Stabilo marker', revenue: 19_950_000, growth: 34.2 },
    { product: 'Mehrobdan chayon', revenue: 15_768_000, growth: 12.8 },
  ],
  worst: [
    { product: 'Ofis papkasi', revenue: 2_716_000, decline: -8.4 },
    { product: "A4 qog'oz", revenue: 12_168_000, decline: -4.2 },
    { product: "Ulug'bek xazinasi", revenue: 8_470_000, decline: -2.8 },
    { product: 'Faber-Castell', revenue: 14_364_000, decline: -1.4 },
    { product: 'Darslik (pastki)', revenue: 3_897_640, decline: -12.5 },
  ],
};

export const conversionFunnel = [
  { stage: 'Saytga tashrif', value: 56840, percent: 100, color: '#0B0342' },
  { stage: 'Mahsulot ko\'rish', value: 34120, percent: 60, color: '#4A3A7A' },
  { stage: 'Savatga qo\'shish', value: 8420, percent: 14.8, color: '#8E3A63' },
  { stage: 'Checkout boshlash', value: 4280, percent: 7.5, color: '#8A5709' },
  { stage: 'Xaridni yakunlash', value: 1944, percent: 3.42, color: '#0F6A46' },
];

export const inventoryAlerts = [
  { product: "Yulduzli tunlar", stock: 8, threshold: 15, status: 'Kam', daysLeft: 12 },
  { product: "Ulug'bek xazinasi", stock: 12, threshold: 20, status: 'Kam', daysLeft: 18 },
  { product: 'Mehrobdan chayon', stock: 18, threshold: 25, status: 'Kam', daysLeft: 24 },
  { product: 'Parker ruchka', stock: 34, threshold: 40, status: 'Ogohlantirish', daysLeft: 38 },
  { product: "A4 qog'oz", stock: 45, threshold: 50, status: 'Ogohlantirish', daysLeft: 42 },
];

export const appPlatformData = [
  { platform: 'Android', activeUsers: 2684, orders: 904, revenue: 34_290_000, profit: 13_716_000, crashRate: 0.42, avgSession: '6m 18s', conversion: 3.7, appVersion: '2.8.4', color: '#0F6A46' },
  { platform: 'iOS', activeUsers: 728, orders: 380, revenue: 14_430_500, profit: 5_772_200, crashRate: 0.18, avgSession: '7m 42s', conversion: 4.1, appVersion: '2.8.1', color: '#24509B' },
];

export const appVersionData = [
  { version: '2.8.4', platform: 'Android', users: 1840, orders: 642, crashRate: 0.21, paymentFailRate: 2.8, revenue: 24_310_000, status: 'Stable' },
  { version: '2.8.3', platform: 'Android', users: 620, orders: 190, crashRate: 0.65, paymentFailRate: 4.1, revenue: 7_230_000, status: 'Update kerak' },
  { version: '2.7.9', platform: 'Android', users: 224, orders: 72, crashRate: 1.42, paymentFailRate: 5.8, revenue: 2_750_000, status: 'Risk' },
  { version: '2.8.1', platform: 'iOS', users: 520, orders: 282, crashRate: 0.12, paymentFailRate: 1.9, revenue: 10_720_000, status: 'Stable' },
];

export const appEventFunnel = [
  { stage: 'App launch', users: 56840, drop: 0, conversion: 100, problem: 'Bazaviy event', color: '#0B0342' },
  { stage: 'Home loaded', users: 53620, drop: 5.7, conversion: 94.3, problem: 'Sekin internet / cache', color: '#3A3475' },
  { stage: 'Product list viewed', users: 34120, drop: 36.4, conversion: 60.0, problem: 'Home -> katalog o\'tish past', color: '#4A3A7A' },
  { stage: 'Product detail viewed', users: 18440, drop: 45.9, conversion: 32.4, problem: 'CTR past / rasm va narx optimizatsiya', color: '#6B4E8E' },
  { stage: 'Added to cart', users: 8420, drop: 54.3, conversion: 14.8, problem: 'Narx yoki mavjudlik muammosi', color: '#8E3A63' },
  { stage: 'Checkout started', users: 4280, drop: 49.2, conversion: 7.5, problem: 'Yetkazish narxi va forma uzun', color: '#8A5709' },
  { stage: 'Payment method selected', users: 2580, drop: 39.7, conversion: 4.54, problem: 'Karta ulashdagi ishonch / naqd tanlash', color: '#1B6273' },
  { stage: 'Order completed', users: 1944, drop: 24.7, conversion: 3.42, problem: 'Karta xatosi va bekor qilish', color: '#0F6A46' },
];

export const paymentData = [
  { method: 'Ulangan karta', orders: 842, revenue: 31_980_000, share: 65.6, avg: 37_980, failed: 38, successRate: 95.7 },
  { method: 'Naqd', orders: 442, revenue: 16_740_500, share: 34.4, avg: 37_874, failed: 0, successRate: 100 },
];

export const paymentFailureReasons = [
  { reason: 'Karta balansi yetarli emas', count: 18, amount: 1_240_000, share: 47.4 },
  { reason: 'Karta ulash jarayoni yakunlanmagan', count: 9, amount: 620_000, share: 23.7 },
  { reason: 'Bank 3DS tasdiqlash xatosi', count: 6, amount: 410_000, share: 15.8 },
  { reason: 'Mijoz to\'lovni bekor qildi', count: 5, amount: 350_000, share: 13.1 },
];

export const operationalQuality = [
  { metric: 'Laravel API p95 latency', value: '186ms', target: '<250ms', status: 'Yaxshi', color: '#0F6A46' },
  { metric: 'Order create error rate', value: '0.18%', target: '<0.5%', status: 'Yaxshi', color: '#0F6A46' },
  { metric: 'Payment callback delay', value: '1.8s', target: '<3s', status: 'Yaxshi', color: '#0F6A46' },
  { metric: 'Admin processing SLA', value: '12m', target: '<15m', status: 'Yaxshi', color: '#0F6A46' },
  { metric: 'Courier assignment SLA', value: '26m', target: '<20m', status: 'Diqqat', color: '#8A5709' },
  { metric: 'Stock sync lag', value: '4m', target: '<5m', status: 'Yaxshi', color: '#0F6A46' },
];

export const cohortRetention = [
  { cohort: 'Yanvar', day1: 72, day7: 46, day14: 34, day30: 24, revenue: 4_820_000 },
  { cohort: 'Fevral', day1: 74, day7: 48, day14: 35, day30: 26, revenue: 5_140_000 },
  { cohort: 'Mart', day1: 76, day7: 51, day14: 38, day30: 28, revenue: 5_680_000 },
  { cohort: 'Aprel', day1: 75, day7: 49, day14: 37, day30: 27, revenue: 5_420_000 },
  { cohort: 'May', day1: 78, day7: 53, day14: 40, day30: 31, revenue: 6_210_000 },
];

export const adminWorkload = [
  { queue: 'Yangi buyurtmalar', count: 38, avgHandle: '6m', slaMiss: 2, priority: 'High' },
  { queue: 'Karta to\'lov tekshiruvi', count: 11, avgHandle: '4m', slaMiss: 1, priority: 'Medium' },
  { queue: 'Naqd buyurtmalar tasdiqi', count: 26, avgHandle: '9m', slaMiss: 3, priority: 'High' },
  { queue: 'Hub qabul qilish', count: 14, avgHandle: '11m', slaMiss: 2, priority: 'High' },
  { queue: 'Hub qadoqlash navbati', count: 22, avgHandle: '8m', slaMiss: 1, priority: 'Medium' },
  { queue: 'Qaytarish so\'rovlari', count: 7, avgHandle: '18m', slaMiss: 2, priority: 'Medium' },
  { queue: 'Ombor zaxira ogohlantirish', count: 5, avgHandle: '22m', slaMiss: 1, priority: 'Low' },
];

export const profitLeakage = [
  { source: 'Bekor qilingan buyurtmalar', amount: 1_240_000, impact: '2.5%', fix: 'Naqd buyurtmalarni tezroq tasdiqlash' },
  { source: 'Qaytarishlar', amount: 842_500, impact: '1.7%', fix: 'Muqova shikastlanishi uchun qadoqlashni kuchaytirish' },
  { source: 'Kuryer qayta yetkazish', amount: 420_000, impact: '0.9%', fix: 'Manzil validatsiyasi va qo\'ng\'iroq tasdiqi' },
  { source: 'Karta to\'lov xatolari', amount: 350_000, impact: '0.7%', fix: 'Karta ulash UX va bank xabarlarini yaxshilash' },
  { source: 'Stock-out yo\'qotish', amount: 2_400_000, impact: '4.9%', fix: 'Reorder point avtomatlashtirish' },
];

// ====================================================================
//  HUB FULFILLMENT SYSTEM — eng muhim yangi modul
// ====================================================================
export interface Hub {
  id: string;
  name: string;
  region: string;
  address: string;
  manager: string;
  phone: string;
  employees: number;
  capacity: number;      // kunlik maksimal qadoqlash
  dailyThroughput: number; // bugungi qadoqlangan
  pendingOrders: number;
  shippedToday: number;
  status: 'Active' | 'Maintenance' | 'Overloaded';
  efficiency: number;    // %
  avgPackingTime: string; // o'rtacha qadoqlash vaqti
  inventory: {
    books: number;
    stationery: number;
  };
  courierArrivals: number; // bugungi kuryer kelishlari
  mailOrders: number;       // pochta orqali jo'natilgan
  createdAt: string;
}

export const hubs: Hub[] = [
  {
    id: 'hub-toshkent',
    name: 'Hub Toshkent — Chilonzor',
    region: 'Toshkent',
    address: 'Chilonzor 12-mavze, 42-uy',
    manager: 'Murodjon Karimov',
    phone: '+998 90 100 20 30',
    employees: 18,
    capacity: 1200,
    dailyThroughput: 842,
    pendingOrders: 64,
    shippedToday: 776,
    status: 'Active',
    efficiency: 94,
    avgPackingTime: '4m 28s',
    inventory: { books: 12420, stationery: 6840 },
    courierArrivals: 14,
    mailOrders: 312,
    createdAt: '2024-03-15',
  },
  {
    id: 'hub-samarqand',
    name: 'Hub Samarqand',
    region: 'Samarqand',
    address: 'Registon ko\'chasi, 7',
    manager: 'Bobur Xolmatov',
    phone: '+998 66 210 40 50',
    employees: 10,
    capacity: 600,
    dailyThroughput: 384,
    pendingOrders: 28,
    shippedToday: 356,
    status: 'Active',
    efficiency: 91,
    avgPackingTime: '5m 12s',
    inventory: { books: 5420, stationery: 2180 },
    courierArrivals: 8,
    mailOrders: 142,
    createdAt: '2024-06-01',
  },
  {
    id: 'hub-fargona',
    name: 'Hub Farg\'ona',
    region: 'Farg\'ona',
    address: 'Oltiariq yo\'li, 18',
    manager: 'Diyorbek Raximov',
    phone: '+998 73 215 60 70',
    employees: 8,
    capacity: 500,
    dailyThroughput: 312,
    pendingOrders: 42,
    shippedToday: 270,
    status: 'Active',
    efficiency: 87,
    avgPackingTime: '5m 48s',
    inventory: { books: 3840, stationery: 1560 },
    courierArrivals: 6,
    mailOrders: 98,
    createdAt: '2024-08-20',
  },
  {
    id: 'hub-buxoro',
    name: 'Hub Buxoro',
    region: 'Buxoro',
    address: 'Mirzo Ulug\'bek ko\'chasi, 25',
    manager: 'Jamila Qosimova',
    phone: '+998 65 225 80 90',
    employees: 6,
    capacity: 400,
    dailyThroughput: 245,
    pendingOrders: 18,
    shippedToday: 227,
    status: 'Active',
    efficiency: 93,
    avgPackingTime: '4m 55s',
    inventory: { books: 2810, stationery: 1140 },
    courierArrivals: 5,
    mailOrders: 76,
    createdAt: '2024-09-10',
  },
  {
    id: 'hub-andijon',
    name: 'Hub Andijon',
    region: 'Andijon',
    address: 'Boburshoh ko\'chasi, 12',
    manager: 'Sardor Tursunov',
    phone: '+998 74 230 10 20',
    employees: 5,
    capacity: 300,
    dailyThroughput: 178,
    pendingOrders: 24,
    shippedToday: 154,
    status: 'Active',
    efficiency: 82,
    avgPackingTime: '6m 30s',
    inventory: { books: 1890, stationery: 820 },
    courierArrivals: 4,
    mailOrders: 52,
    createdAt: '2024-11-01',
  },
  {
    id: 'hub-xorazm',
    name: 'Hub Xorazm (Yangibozor)',
    region: 'Xorazm',
    address: 'Yangibozor tumani, Katta ko\'cha',
    manager: 'Azizbek Matkarimov',
    phone: '+998 62 215 30 40',
    employees: 3,
    capacity: 150,
    dailyThroughput: 86,
    pendingOrders: 14,
    shippedToday: 72,
    status: 'Overloaded',
    efficiency: 68,
    avgPackingTime: '8m 15s',
    inventory: { books: 720, stationery: 340 },
    courierArrivals: 2,
    mailOrders: 28,
    createdAt: '2025-01-15',
  },
];

export const hubEmployees = [
  { id: 1, name: 'Akmal Toshmatov', hubId: 'hub-toshkent', role: 'Manager Assistant', shift: 'Morning', activeOrders: 12, productivity: 95, salary: 3_200_000 },
  { id: 2, name: 'Shahlo Rahimova', hubId: 'hub-toshkent', role: 'Packer', shift: 'Morning', activeOrders: 24, productivity: 112, salary: 2_800_000 },
  { id: 3, name: 'Bakhrom Umarov', hubId: 'hub-toshkent', role: 'Packer', shift: 'Evening', activeOrders: 18, productivity: 104, salary: 2_800_000 },
  { id: 4, name: 'Dildora Norqulova', hubId: 'hub-toshkent', role: 'Quality Check', shift: 'Morning', activeOrders: 0, productivity: 98, salary: 3_500_000 },
  { id: 5, name: 'Javohir Abdullayev', hubId: 'hub-samarqand', role: 'Hub Operator', shift: 'Morning', activeOrders: 8, productivity: 88, salary: 2_600_000 },
  { id: 6, name: 'Mahmudjon Ergashev', hubId: 'hub-samarqand', role: 'Packer', shift: 'Evening', activeOrders: 14, productivity: 76, salary: 2_400_000 },
  { id: 7, name: 'Nozima Sattarova', hubId: 'hub-xorazm', role: 'Hub Operator', shift: 'Full', activeOrders: 12, productivity: 62, salary: 2_200_000 },
];

export const hubFulfillmentQueue = [
  { id: 'FQ-001', orderId: 'ORD-8901', hubId: 'hub-toshkent', status: 'Received', time: '10:24', eta: '10:55', priority: 'Normal', sellerId: 'sel-1' },
  { id: 'FQ-002', orderId: 'ORD-8902', hubId: 'hub-toshkent', status: 'Packing', time: '10:18', eta: '10:40', priority: 'Express', sellerId: 'sel-2' },
  { id: 'FQ-003', orderId: 'ORD-8903', hubId: 'hub-samarqand', status: 'Labeled', time: '09:52', eta: '10:30', priority: 'Normal', sellerId: 'sel-3' },
  { id: 'FQ-004', orderId: 'ORD-8904', hubId: 'hub-toshkent', status: 'Packed', time: '09:45', eta: '11:00', priority: 'Normal', sellerId: 'sel-1' },
  { id: 'FQ-005', orderId: 'ORD-8905', hubId: 'hub-fargona', status: 'Received', time: '10:10', eta: '11:30', priority: 'Normal', sellerId: 'sel-4' },
  { id: 'FQ-006', orderId: 'ORD-8906', hubId: 'hub-toshkent', status: 'Shipped', time: '09:30', eta: '—', priority: 'Normal', sellerId: 'sel-5' },
  { id: 'FQ-007', orderId: 'ORD-8907', hubId: 'hub-andijon', status: 'Waiting Courier', time: '09:15', eta: '12:00', priority: 'Express', sellerId: 'sel-2' },
];

// ====================================================================
//  SELLER SYSTEM - shartnoma, hodim, premium, tranzaksiya
// ====================================================================
export interface SellerContract {
  id: string;
  type: 'Royalty' | 'Revenue Share' | 'Fixed';
  share: number;            // 15 = 15%
  startDate: string;
  endDate: string;
  autoRenew: boolean;
  status: 'Active' | 'Expired' | 'Terminated';
  document: string;
}

export interface SellerEmployee {
  id: number;
  name: string;
  role: string;
  phone: string;
  salary: number;
  status: 'Active' | 'Inactive';
  joinedAt: string;
}

export interface SellerTransaction {
  id: string;
  type: 'withdrawal' | 'commission' | 'payout' | 'penalty';
  amount: number;
  fee: number;
  netAmount: number;
  date: string;
  status: 'Completed' | 'Pending' | 'Failed';
  method: 'Bank' | 'Card' | 'Wallet';
  note: string;
}

export interface SellerPremiumPlan {
  id: string;
  name: string;
  price: number;       // oylik
  features: string[];
  discount: number;    // %
  active: boolean;
  startDate: string;
  endDate: string;
}

export interface Seller {
  id: string;
  name: string;
  legalName: string;
  inn: string;
  phone: string;
  email: string;
  region: string;
  status: 'Active' | 'Suspended' | 'Pending' | 'Blocked';
  tier: 'Basic' | 'Premium' | 'Enterprise';
  rating: number;
  products: number;
  orders: number;
  revenue: number;
  profit: number;
  commissionRate: number;  // platforma ulushi %
  joinedAt: string;
  lastActive: string;
  totalWithdrawn: number;
  balance: number;
  contract: SellerContract;
  employees: SellerEmployee[];
  transactions: SellerTransaction[];
  plan: SellerPremiumPlan | null;
  hubs: string[];      // qaysi hub'lardan foydalanadi
}

const selDate = '2026-01-14';

export const sellers: Seller[] = [
  {
    id: 'sel-1',
    name: 'Kitob olami MCHJ',
    legalName: '"Kitob olami" masuliyati cheklangan jamiyati',
    inn: '304582718',
    phone: '+998 71 200 11 22',
    email: 'info@kitobolami.uz',
    region: 'Toshkent',
    status: 'Active',
    tier: 'Enterprise',
    rating: 4.8,
    products: 1420,
    orders: 584,
    revenue: 18_420_000,
    profit: 5_526_000,
    commissionRate: 12,
    joinedAt: '2023-04-12',
    lastActive: selDate,
    totalWithdrawn: 42_180_000,
    balance: 3_240_000,
    contract: {
      id: 'CTR-SEL-001',
      type: 'Revenue Share',
      share: 12,
      startDate: '2024-01-01',
      endDate: '2026-12-31',
      autoRenew: true,
      status: 'Active',
      document: 'kitob_olami_shartnoma_2024.pdf'
    },
    employees: [
      { id: 1, name: 'Dilshod Karimov', role: 'Menejer', phone: '+998 90 111 22 33', salary: 5_000_000, status: 'Active', joinedAt: '2023-04-15' },
      { id: 2, name: 'Mohigul Rustamova', role: 'Omborchi', phone: '+998 91 222 33 44', salary: 3_200_000, status: 'Active', joinedAt: '2023-05-01' },
      { id: 3, name: 'Azizbek Qodirov', role: "Yetkazuvchi (Do'kon kuryeri)", phone: '+998 93 333 44 55', salary: 2_800_000, status: 'Active', joinedAt: '2024-02-12' },
    ],
    transactions: [
      { id: 'TXN-1001', type: 'payout', amount: 3_240_000, fee: 0, netAmount: 3_240_000, date: '2026-01-14', status: 'Completed', method: 'Bank', note: 'Dekabr oyida sotilgan mahsulotlar uchun to\'lov' },
      { id: 'TXN-1002', type: 'commission', amount: -420_000, fee: 0, netAmount: -420_000, date: '2026-01-10', status: 'Completed', method: 'Wallet', note: 'Platforma komissiyasi (12%)' },
      { id: 'TXN-1003', type: 'withdrawal', amount: 5_000_000, fee: 25_000, netAmount: 4_975_000, date: '2026-01-05', status: 'Completed', method: 'Bank', note: 'Pul yechish — hisob raqamiga' },
      { id: 'TXN-1004', type: 'penalty', amount: -200_000, fee: 0, netAmount: -200_000, date: '2026-01-02', status: 'Completed', method: 'Wallet', note: 'Kechiktirilgan yetkazish (3 soat)' },
    ],
    plan: {
      id: 'PLAN-EPR-001',
      name: 'Enterprise Pro',
      price: 2_500_000,
      features: ['Cheksiz mahsulot', 'Dedicated account manager', 'Priority support', 'Custom analytics', 'API access', 'Multi-hub distribution', 'Premium badge'],
      discount: 20,
      active: true,
      startDate: '2024-06-01',
      endDate: '2026-06-01',
    },
    hubs: ['hub-toshkent', 'hub-samarqand'],
  },
  {
    id: 'sel-2',
    name: 'Sharq kitoblari',
    legalName: '"Sharq kitoblari" xususiy korxonasi',
    inn: '305812719',
    phone: '+998 71 210 33 44',
    email: 'info@sharqkitob.uz',
    region: 'Toshkent',
    status: 'Active',
    tier: 'Premium',
    rating: 4.7,
    products: 680,
    orders: 312,
    revenue: 8_420_000,
    profit: 2_526_000,
    commissionRate: 15,
    joinedAt: '2023-08-20',
    lastActive: selDate,
    totalWithdrawn: 18_200_000,
    balance: 1_120_000,
    contract: {
      id: 'CTR-SEL-003',
      type: 'Revenue Share',
      share: 15,
      startDate: '2024-03-01',
      endDate: '2026-03-01',
      autoRenew: true,
      status: 'Active',
      document: 'sharq_kitoblari_shartnoma.pdf'
    },
    employees: [
      { id: 4, name: 'Oybek Sultonov', role: 'Menejer', phone: '+998 90 444 55 66', salary: 4_200_000, status: 'Active', joinedAt: '2023-09-01' },
      { id: 5, name: 'Nodira Salimova', role: 'Ombor Menejeri', phone: '+998 91 555 66 77', salary: 3_000_000, status: 'Active', joinedAt: '2024-01-15' },
    ],
    transactions: [
      { id: 'TXN-2001', type: 'payout', amount: 1_120_000, fee: 0, netAmount: 1_120_000, date: '2026-01-13', status: 'Completed', method: 'Card', note: 'To\'lov' },
      { id: 'TXN-2002', type: 'commission', amount: -210_000, fee: 0, netAmount: -210_000, date: '2026-01-12', status: 'Completed', method: 'Wallet', note: 'Platforma komissiyasi' },
      { id: 'TXN-2003', type: 'withdrawal', amount: 3_000_000, fee: 15_000, netAmount: 2_985_000, date: '2026-01-08', status: 'Completed', method: 'Card', note: 'Pul yechish' },
    ],
    plan: {
      id: 'PLAN-PRM-001',
      name: 'Premium Monthly',
      price: 890_000,
      features: ['200 tagacha mahsulot', 'Priority support', 'Hub access', 'Analytics dashboard', 'Premium badge'],
      discount: 0,
      active: true,
      startDate: '2025-10-01',
      endDate: '2026-10-01',
    },
    hubs: ['hub-toshkent'],
  },
  {
    id: 'sel-3',
    name: "Adabiyot do'koni",
    legalName: '"Adabiyot" yakka tartibdagi tadbirkori',
    inn: '308914520',
    phone: '+998 90 300 50 60',
    email: 'adabiyot@gmail.com',
    region: 'Samarqand',
    status: 'Active',
    tier: 'Basic',
    rating: 4.6,
    products: 245,
    orders: 142,
    revenue: 3_890_000,
    profit: 1_167_000,
    commissionRate: 18,
    joinedAt: '2024-05-10',
    lastActive: selDate,
    totalWithdrawn: 5_800_000,
    balance: 680_000,
    contract: {
      id: 'CTR-SEL-005',
      type: 'Royalty',
      share: 18,
      startDate: '2024-05-10',
      endDate: '2025-05-10',
      autoRenew: true,
      status: 'Active',
      document: 'adabiyot_shartnoma.pdf'
    },
    employees: [],
    transactions: [
      { id: 'TXN-3001', type: 'payout', amount: 680_000, fee: 0, netAmount: 680_000, date: '2026-01-12', status: 'Pending', method: 'Bank', note: 'To\'lov kutilmoqda' },
      { id: 'TXN-3002', type: 'commission', amount: -124_000, fee: 0, netAmount: -124_000, date: '2026-01-10', status: 'Completed', method: 'Wallet', note: 'Platforma komissiyasi' },
    ],
    plan: null,
    hubs: ['hub-samarqand'],
  },
  {
    id: 'sel-4',
    name: 'Iman books',
    legalName: '"Iman yuridik shaxs" MCHJ',
    inn: '310215634',
    phone: '+998 94 700 80 90',
    email: 'info@imanbooks.uz',
    region: 'Farg\'ona',
    status: 'Active',
    tier: 'Premium',
    rating: 4.5,
    products: 420,
    orders: 218,
    revenue: 5_650_000,
    profit: 1_695_000,
    commissionRate: 15,
    joinedAt: '2024-09-01',
    lastActive: selDate,
    totalWithdrawn: 3_200_000,
    balance: 890_000,
    contract: {
      id: 'CTR-SEL-008',
      type: 'Revenue Share',
      share: 15,
      startDate: '2024-09-01',
      endDate: '2026-09-01',
      autoRenew: true,
      status: 'Active',
      document: 'iman_kitoblar_shartnoma.docx'
    },
    employees: [
      { id: 6, name: 'Shaxboz Ergashev', role: 'Sotuv Menejeri', phone: '+998 93 777 88 99', salary: 3_800_000, status: 'Active', joinedAt: '2024-09-05' },
    ],
    transactions: [
      { id: 'TXN-4001', type: 'payout', amount: 890_000, fee: 0, netAmount: 890_000, date: '2026-01-11', status: 'Completed', method: 'Card', note: 'To\'lov' },
      { id: 'TXN-4002', type: 'commission', amount: -141_000, fee: 0, netAmount: -141_000, date: '2026-01-11', status: 'Completed', method: 'Wallet', note: '15% komissiya' },
    ],
    plan: {
      id: 'PLAN-PRM-002',
      name: 'Premium Monthly',
      price: 890_000,
      features: ['200 tagacha mahsulot', 'Priority support', 'Hub access', 'Analytics dashboard', 'Premium badge'],
      discount: 10,
      active: true,
      startDate: '2025-12-01',
      endDate: '2026-12-01',
    },
    hubs: ['hub-fargona'],
  },
  {
    id: 'sel-5',
    name: 'Kanselyariya PLUS',
    legalName: '"Kanselyariya PLUS" masuliyati cheklangan jamiyati',
    inn: '312518720',
    phone: '+998 71 230 100 200',
    email: 'admin@kansplus.uz',
    region: 'Toshkent',
    status: 'Active',
    tier: 'Enterprise',
    rating: 4.9,
    products: 2180,
    orders: 1240,
    revenue: 28_400_000,
    profit: 8_520_000,
    commissionRate: 10,
    joinedAt: '2023-01-10',
    lastActive: selDate,
    totalWithdrawn: 78_500_000,
    balance: 4_800_000,
    contract: {
      id: 'CTR-SEL-002',
      type: 'Fixed',
      share: 8,
      startDate: '2024-01-01',
      endDate: '2027-01-01',
      autoRenew: true,
      status: 'Active',
      document: 'kans_plus_shartnoma.pdf'
    },
    employees: [
      { id: 7, name: 'Askar Tolipov', role: 'Direktor', phone: '+998 90 888 99 00', salary: 8_000_000, status: 'Active', joinedAt: '2023-01-10' },
      { id: 8, name: "Gulira'no Raximova", role: 'Menejer', phone: '+998 91 777 88 99', salary: 4_500_000, status: 'Active', joinedAt: '2023-03-01' },
      { id: 9, name: 'Shohruh Mirzayev', role: 'Ombor Menejeri', phone: '+998 93 666 77 88', salary: 3_500_000, status: 'Active', joinedAt: '2023-06-15' },
      { id: 10, name: 'Zuxra Abdurahmonova', role: 'Logistik', phone: '+998 97 555 66 77', salary: 3_200_000, status: 'Active', joinedAt: '2024-01-20' },
    ],
    transactions: [
      { id: 'TXN-5001', type: 'payout', amount: 4_800_000, fee: 0, netAmount: 4_800_000, date: '2026-01-14', status: 'Completed', method: 'Bank', note: 'Dekabr to\'lovi' },
      { id: 'TXN-5002', type: 'commission', amount: -480_000, fee: 0, netAmount: -480_000, date: '2026-01-14', status: 'Completed', method: 'Wallet', note: 'Platforma komissiyasi (10%)' },
      { id: 'TXN-5003', type: 'withdrawal', amount: 12_000_000, fee: 60_000, netAmount: 11_940_000, date: '2026-01-08', status: 'Completed', method: 'Bank', note: 'Pul yechish' },
      { id: 'TXN-5004', type: 'penalty', amount: -150_000, fee: 0, netAmount: -150_000, date: '2026-01-05', status: 'Completed', method: 'Wallet', note: 'Sifatsiz qadoqlash (2 ta holat)' },
    ],
    plan: {
      id: 'PLAN-EPR-002',
      name: 'Enterprise Pro',
      price: 2_500_000,
      features: ['Cheksiz mahsulot', 'Dedicated account manager', 'Priority support', 'Custom analytics', 'API access', 'Multi-hub distribution', 'Premium badge'],
      discount: 30,
      active: true,
      startDate: '2024-01-01',
      endDate: '2027-01-01',
    },
    hubs: ['hub-toshkent', 'hub-samarqand', 'hub-fargona', 'hub-buxoro'],
  },
];

// ====================================================================
//  COURIER SYSTEM - shartnoma, tranzaksiya
// ====================================================================
export interface CourierContract {
  id: string;
  type: 'Full-time' | 'Part-time' | 'Freelance';
  startDate: string;
  endDate: string;
  autoRenew: boolean;
  status: 'Active' | 'Expired' | 'Terminated';
  document: string;
  baseSalary: number;
  perDelivery: number;   // yetkazilgan har bir buyurtma uchun
  insurance: boolean;
}

export interface CourierTransaction {
  id: string;
  type: 'salary' | 'bonus' | 'penalty' | 'withdrawal' | 'payout';
  amount: number;
  date: string;
  status: 'Completed' | 'Pending' | 'Failed';
  note: string;
}

export interface Courier {
  id: string;
  name: string;
  phone: string;
  vehicle: 'Bicycle' | 'Scooter' | 'Motorbike' | 'Car';
  region: string;
  assignedHub: string;
  status: 'Available' | 'On Route' | 'Offline' | 'Suspended';
  ordersToday: number;
  deliveredToday: number;
  failedToday: number;
  onTimeRate: number;
  rating: number;
  totalDeliveries: number;
  totalRevenue: number;
  balance: number;
  joinedAt: string;
  lastActive: string;
  contract: CourierContract;
  transactions: CourierTransaction[];
}

const curDate = '2026-01-14';

export const couriers: Courier[] = [
  {
    id: 'cr-1',
    name: 'Sardor Raximov',
    phone: '+998 90 111 22 33',
    vehicle: 'Scooter',
    region: 'Toshkent, Yunusobod',
    assignedHub: 'hub-toshkent',
    status: 'On Route',
    ordersToday: 8,
    deliveredToday: 6,
    failedToday: 1,
    onTimeRate: 94,
    rating: 4.7,
    totalDeliveries: 1248,
    totalRevenue: 18_720_000,
    balance: 2_240_000,
    joinedAt: '2023-06-12',
    lastActive: curDate,
    contract: {
      id: 'CTR-CR-001',
      type: 'Full-time',
      startDate: '2024-01-01',
      endDate: '2026-12-31',
      autoRenew: true,
      status: 'Active',
      document: 'sardor_raximov_shartnoma.pdf',
      baseSalary: 3_000_000,
      perDelivery: 5000,
      insurance: true,
    },
    transactions: [
      { id: 'CRC-1001', type: 'salary', amount: 3_500_000, date: '2026-01-10', status: 'Completed', note: 'Yanvar I yarmi oylik + bonus' },
      { id: 'CRC-1002', type: 'bonus', amount: 300_000, date: '2026-01-08', status: 'Completed', note: '100% on-time bonusi' },
      { id: 'CRC-1003', type: 'withdrawal', amount: 1_200_000, date: '2026-01-05', status: 'Completed', note: 'Pul yechish' },
      { id: 'CRC-1004', type: 'penalty', amount: -50_000, date: '2026-01-03', status: 'Completed', note: '1 ta muvaffaqiyatsiz yetkazish' },
    ],
  },
  {
    id: 'cr-2',
    name: 'Kamol Yusupov',
    phone: '+998 91 333 44 55',
    vehicle: 'Scooter',
    region: "Toshkent, Mirzo Ulug'bek",
    assignedHub: 'hub-toshkent',
    status: 'Available',
    ordersToday: 12,
    deliveredToday: 12,
    failedToday: 0,
    onTimeRate: 98,
    rating: 4.9,
    totalDeliveries: 2180,
    totalRevenue: 32_700_000,
    balance: 3_800_000,
    joinedAt: '2022-03-01',
    lastActive: curDate,
    contract: {
      id: 'CTR-CR-002',
      type: 'Full-time',
      startDate: '2024-01-01',
      endDate: '2026-12-31',
      autoRenew: true,
      status: 'Active',
      document: 'kamol_yusupov_shartnoma.pdf',
      baseSalary: 3_500_000,
      perDelivery: 6000,
      insurance: true,
    },
    transactions: [
      { id: 'CRC-2001', type: 'salary', amount: 4_200_000, date: '2026-01-12', status: 'Completed', note: 'Yanvar I yarmi oylik' },
      { id: 'CRC-2002', type: 'bonus', amount: 500_000, date: '2026-01-12', status: 'Completed', note: '0% failed bonusi' },
    ],
  },
  {
    id: 'cr-3',
    name: 'Jamol Normatov',
    phone: '+998 93 555 66 77',
    vehicle: 'Motorbike',
    region: 'Toshkent, Chilanzar',
    assignedHub: 'hub-toshkent',
    status: 'On Route',
    ordersToday: 5,
    deliveredToday: 2,
    failedToday: 1,
    onTimeRate: 88,
    rating: 4.3,
    totalDeliveries: 642,
    totalRevenue: 9_630_000,
    balance: 1_100_000,
    joinedAt: '2024-08-15',
    lastActive: curDate,
    contract: {
      id: 'CTR-CR-003',
      type: 'Full-time',
      startDate: '2024-08-15',
      endDate: '2026-08-15',
      autoRenew: true,
      status: 'Active',
      document: 'jamol_normatov_shartnoma.pdf',
      baseSalary: 2_800_000,
      perDelivery: 4500,
      insurance: true,
    },
    transactions: [
      { id: 'CRC-3001', type: 'salary', amount: 2_800_000, date: '2026-01-10', status: 'Completed', note: 'Yanvar oylik' },
      { id: 'CRC-3002', type: 'bonus', amount: 150_000, date: '2026-01-03', status: 'Completed', note: '80+% on-time bonusi' },
    ],
  },
  {
    id: 'cr-4',
    name: 'Rustam Qosimov',
    phone: '+998 97 777 88 99',
    vehicle: 'Car',
    region: 'Toshkent, Shayxontohur',
    assignedHub: 'hub-toshkent',
    status: 'Available',
    ordersToday: 10,
    deliveredToday: 10,
    failedToday: 0,
    onTimeRate: 99,
    rating: 4.9,
    totalDeliveries: 3420,
    totalRevenue: 51_300_000,
    balance: 5_600_000,
    joinedAt: '2022-01-05',
    lastActive: curDate,
    contract: {
      id: 'CTR-CR-004',
      type: 'Full-time',
      startDate: '2024-01-01',
      endDate: '2027-01-01',
      autoRenew: true,
      status: 'Active',
      document: 'rustam_qosimov_shartnoma.pdf',
      baseSalary: 4_000_000,
      perDelivery: 7000,
      insurance: true,
    },
    transactions: [
      { id: 'CRC-4001', type: 'salary', amount: 5_000_000, date: '2026-01-15', status: 'Pending', note: 'Yanvar I yarmi' },
      { id: 'CRC-4002', type: 'withdrawal', amount: 2_000_000, date: '2026-01-08', status: 'Completed', note: 'Pul yechish' },
    ],
  },
  {
    id: 'cr-5',
    name: 'Akbar Tursunov',
    phone: '+998 94 888 99 00',
    vehicle: 'Bicycle',
    region: 'Toshkent, Sergeli',
    assignedHub: 'hub-toshkent',
    status: 'Offline',
    ordersToday: 0,
    deliveredToday: 0,
    failedToday: 0,
    onTimeRate: 92,
    rating: 4.5,
    totalDeliveries: 421,
    totalRevenue: 4_210_000,
    balance: 420_000,
    joinedAt: '2025-07-01',
    lastActive: '2026-01-12',
    contract: {
      id: 'CTR-CR-005',
      type: 'Part-time',
      startDate: '2025-07-01',
      endDate: '2026-07-01',
      autoRenew: true,
      status: 'Active',
      document: 'akbar_tursunov_shartnoma.pdf',
      baseSalary: 0,
      perDelivery: 3500,
      insurance: false,
    },
    transactions: [
      { id: 'CRC-5001', type: 'payout', amount: 350_000, date: '2026-01-10', status: 'Completed', note: 'Haftalik to\'lov' },
      { id: 'CRC-5002', type: 'salary', amount: 420_000, date: '2026-01-07', status: 'Completed', note: '15 ta delivery' },
    ],
  },
  {
    id: 'cr-6',
    name: 'Shoxrux Mirzayev',
    phone: '+998 99 000 11 22',
    vehicle: 'Scooter',
    region: 'Samarqand',
    assignedHub: 'hub-samarqand',
    status: 'Available',
    ordersToday: 4,
    deliveredToday: 3,
    failedToday: 0,
    onTimeRate: 96,
    rating: 4.8,
    totalDeliveries: 284,
    totalRevenue: 2_840_000,
    balance: 680_000,
    joinedAt: '2025-10-01',
    lastActive: curDate,
    contract: {
      id: 'CTR-CR-006',
      type: 'Part-time',
      startDate: '2025-10-01',
      endDate: '2026-10-01',
      autoRenew: true,
      status: 'Active',
      document: 'shoxrux_mirzayev_shartnoma.pdf',
      baseSalary: 0,
      perDelivery: 3000,
      insurance: false,
    },
    transactions: [
      { id: 'CRC-6001', type: 'salary', amount: 240_000, date: '2026-01-14', status: 'Completed', note: 'Haftalik 80 ta delivery' },
    ],
  },
];

// Existing courier simple list for Dashboard
export const courierOrders = couriers.map(c => ({
  id: c.id,
  courier: c.name,
  orders: c.ordersToday,
  delivered: c.deliveredToday,
  failed: c.failedToday,
  status: c.status === 'On Route' ? 'On Route' : c.status === 'Available' ? 'Completed' : 'Pending',
  region: c.region,
  eta: c.status === 'On Route' ? `${Math.floor(40 + Math.random() * 80)} daqiqa` : '-',
  onTimeRate: c.onTimeRate,
}));

// ====================================================================
//  BOOK CLUB, TICKETS va boshqa kichik modullar
// ====================================================================
export const bookClubPosts = [
  { id: 1, author: 'Aziza K.', title: "O'tkan kunlar haqida taassurot", likes: 124, comments: 18, date: '2 soat oldin', tag: 'Muhokama', views: 1240 },
  { id: 2, author: 'Dilnoza R.', title: 'Yanvar uchun 5 ta kitob tavsiyasi', likes: 298, comments: 42, date: '5 soat oldin', tag: 'Tavsiya', views: 2840 },
  { id: 3, author: 'Bobur A.', title: "Cho'lpon she'riyatida ramz", likes: 87, comments: 11, date: '1 kun oldin', tag: 'Tahlil', views: 845 },
  { id: 4, author: 'Shaxlo Y.', title: 'Kanselyariya: ish uchun eng yaxshi ruchkalar', likes: 156, comments: 23, date: '1 kun oldin', tag: 'Sharh', views: 1620 },
  { id: 5, author: 'Malika U.', title: 'Bolalar uchun eng sara ertaklar', likes: 212, comments: 31, date: '2 kun oldin', tag: 'Tavsiya', views: 2180 },
];

export const tickets = [
  { id: '#TK-1101', subject: 'Buyurtma kelmadi', user: 'Bobur A.', priority: 'Yuqori', status: 'Open', date: '10 daqiqa oldin' },
  { id: '#TK-1102', subject: 'Kitob muqovasi shikastlangan', user: 'Dilnoza R.', priority: 'Yuqori', status: 'In Progress', date: '1 soat oldin' },
  { id: '#TK-1103', subject: "Qaytarish so'rovi", user: 'Jamshid T.', priority: "O'rta", status: 'Open', date: '3 soat oldin' },
  { id: '#TK-1104', subject: "To'lov muammosi", user: 'Malika U.', priority: 'Yuqori', status: 'Resolved', date: '5 soat oldin' },
  { id: '#TK-1105', subject: "Mahsulot so'rovi", user: 'Aziza K.', priority: 'Past', status: 'Closed', date: '1 kun oldin' },
  { id: '#TK-1106', subject: 'Yetkazib berish kechikdi', user: 'Shaxlo Y.', priority: "O'rta", status: 'In Progress', date: '1 kun oldin' },
];
