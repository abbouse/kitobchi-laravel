<?php $__env->startSection('title', $seller->shop_name); ?>
<?php $__env->startSection('page-title', 'Sotuvchi profili'); ?>

<?php $__env->startSection('content'); ?>
<div class="mb-4 flex items-center justify-between flex-wrap gap-2">
    <a href="<?php echo e(route('admin.sellers.index')); ?>" class="btn btn-secondary flex items-center gap-2">
        <i data-lucide="arrow-left" class="w-4 h-4"></i> Orqaga
    </a>
    <div class="flex items-center gap-2 flex-wrap">
        <?php if($seller->status === 'blocked'): ?>
            <form method="POST" action="<?php echo e(route('admin.sellers.unblock', $seller)); ?>" onsubmit="return confirm('Sotuvchini blokdan chiqarmoqchimisiz?')">
                <?php echo csrf_field(); ?>
                <?php echo method_field('PATCH'); ?>
                <button type="submit" class="btn btn-success flex items-center gap-2">
                    <i data-lucide="unlock" class="w-4 h-4"></i> Blokdan chiqarish
                </button>
            </form>
        <?php endif; ?>
        <form method="POST" action="<?php echo e(route('admin.sellers.reset-password', $seller)); ?>" onsubmit="return confirm('Yangi parol sotuvchining telefon raqamiga SMS orqali yuborilsinmi?')">
            <?php echo csrf_field(); ?>
            <button type="submit" class="btn btn-warning flex items-center gap-2">
                <i data-lucide="key-round" class="w-4 h-4"></i> Parolni SMS bilan yangilash
            </button>
        </form>
        <a href="<?php echo e(route('admin.sellers.edit', $seller)); ?>" class="btn btn-primary flex items-center gap-2">
            <i data-lucide="pencil" class="w-4 h-4"></i> Tahrirlash
        </a>
    </div>
</div>

<?php if(session('success')): ?>
    <div class="mb-4 rounded-lg bg-green-100 text-green-800 dark:bg-green-500/10 dark:text-green-400 px-4 py-3 text-sm font-medium">
        <?php echo e(session('success')); ?>

    </div>
<?php endif; ?>
<?php if(session('error')): ?>
    <div class="mb-4 rounded-lg bg-red-100 text-red-800 dark:bg-red-500/10 dark:text-red-400 px-4 py-3 text-sm font-medium">
        <?php echo e(session('error')); ?>

    </div>
<?php endif; ?>


<div class="card p-5 mb-6">
    <div class="flex flex-col sm:flex-row sm:items-center gap-4">
        
        <div class="shrink-0">
            <?php if($seller->photo): ?>
                <img
                    src="<?php echo e(Str::startsWith($seller->photo, 'http') ? $seller->photo : asset('storage/' . $seller->photo)); ?>"
                    alt="<?php echo e($seller->shop_name); ?>"
                    class="w-20 h-20 rounded-full object-cover ring-4 ring-emerald-100 dark:ring-emerald-500/20"
                >
            <?php else: ?>
                <div class="w-20 h-20 rounded-full bg-gray-200 dark:bg-white/10 flex items-center justify-center text-2xl font-bold text-gray-500 dark:text-gray-300">
                    <?php echo e(strtoupper(substr($seller->shop_name ?? 'S', 0, 1))); ?>

                </div>
            <?php endif; ?>
        </div>

        
        <div class="flex-1">
            <div class="flex items-center gap-3 flex-wrap">
                <h2 class="text-xl font-bold"><?php echo e($seller->shop_name); ?></h2>
                <?php if($seller->status === 'approved'): ?>
                    <span class="badge badge-success">Tasdiqlangan</span>
                <?php elseif($seller->status === 'pending'): ?>
                    <span class="badge badge-warning">Kutilmoqda</span>
                <?php elseif($seller->status === 'rejected'): ?>
                    <span class="badge badge-danger">Rad etilgan</span>
                <?php elseif($seller->status === 'blocked'): ?>
                    <span class="badge badge-danger">Bloklangan</span>
                <?php else: ?>
                    <span class="badge badge-muted"><?php echo e($seller->status); ?></span>
                <?php endif; ?>
                <span class="badge <?php echo e($warningCount >= 3 ? 'badge-danger' : ($warningCount > 0 ? 'badge-warning' : 'badge-muted')); ?>">
                    <?php echo e($warningCount); ?>/3 ogohlantirish
                </span>
                <?php if($seller->isPremiumShop && $seller->isPremiumExpiresAt && $seller->isPremiumExpiresAt->isFuture()): ?>
                    <span class="badge bg-amber-100 text-amber-700 dark:bg-amber-400/10 dark:text-amber-300 flex items-center gap-1">
                        <i data-lucide="crown" class="w-3.5 h-3.5"></i>
                        Premium · <?php echo e($seller->isPremiumExpiresAt->format('Y-m-d')); ?>

                    </span>
                <?php elseif($seller->isPremiumShop && $seller->isPremiumExpiresAt && $seller->isPremiumExpiresAt->isPast()): ?>
                    <span class="badge bg-gray-100 text-gray-500 dark:bg-white/5 dark:text-gray-400 flex items-center gap-1">
                        <i data-lucide="crown-off" class="w-3.5 h-3.5"></i>
                        Premium tugagan
                    </span>
                <?php endif; ?>
                <?php if($seller->isVerified): ?>
                    <span class="badge bg-sky-100 text-sky-700 dark:bg-sky-400/10 dark:text-sky-300 flex items-center gap-1">
                        <i data-lucide="badge-check" class="w-3.5 h-3.5"></i> Verified
                    </span>
                <?php endif; ?>
                
                <?php if($seller->contract_signed): ?>
                    <span class="badge bg-emerald-100 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-400 flex items-center gap-1">
                        <i data-lucide="file-signature" class="w-3.5 h-3.5"></i> Shartnoma imzolangan
                    </span>
                <?php else: ?>
                    <span class="badge bg-gray-200 text-gray-600 dark:bg-white/10 dark:text-gray-400 flex items-center gap-1">
                        <i data-lucide="file-pen-line" class="w-3.5 h-3.5"></i> Imzolanmagan
                    </span>
                <?php endif; ?>
                <?php if($seller->contract_expires_at): ?>
                    <?php
                        $cDays = (int) now()->startOfDay()->diffInDays($seller->contract_expires_at, false);
                        if ($seller->contract_status === 'terminated') {
                            $cBadgeCls = 'bg-gray-200 text-gray-600 dark:bg-white/10 dark:text-gray-300';
                            $cIcon = 'file-x';
                            $cText = 'Shartnoma to\'xtatilgan';
                        } elseif ($cDays < 0) {
                            $cBadgeCls = 'bg-red-100 text-red-700 dark:bg-red-500/10 dark:text-red-400';
                            $cIcon = 'file-warning';
                            $cText = 'Shartnoma tugagan ('.abs($cDays).' kun)';
                        } elseif ($cDays <= 30) {
                            $cBadgeCls = 'bg-amber-100 text-amber-700 dark:bg-amber-500/10 dark:text-amber-400';
                            $cIcon = 'clock-alert';
                            $cText = 'Shartnoma '.$cDays.' kunda tugaydi';
                        } else {
                            $cBadgeCls = 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-400';
                            $cIcon = 'file-check';
                            $cText = 'Shartnoma faol';
                        }
                    ?>
                    <span class="badge <?php echo e($cBadgeCls); ?> flex items-center gap-1">
                        <i data-lucide="<?php echo e($cIcon); ?>" class="w-3.5 h-3.5"></i> <?php echo e($cText); ?>

                    </span>
                <?php endif; ?>
            </div>
            <p class="text-sm text-gray-500 mt-1"><?php echo e(trim($seller->firstname . ' ' . $seller->lastname)); ?></p>
            <div class="flex items-center gap-4 mt-2 text-sm text-gray-600 dark:text-gray-400 flex-wrap">
                <?php if($seller->phone_number): ?>
                    <span class="flex items-center gap-1">
                        <i data-lucide="phone" class="w-4 h-4"></i> <?php echo e($seller->phone_number); ?>

                    </span>
                <?php endif; ?>
                <?php if($seller->region): ?>
                    <span class="flex items-center gap-1">
                        <i data-lucide="map-pin" class="w-4 h-4"></i> <?php echo e($seller->region); ?>

                    </span>
                <?php endif; ?>
            </div>
        </div>

        
        <div class="flex items-center gap-2 shrink-0">
            <?php if($seller->status !== 'approved'): ?>
                <form method="POST" action="<?php echo e(route('admin.sellers.approve', $seller)); ?>">
                    <?php echo csrf_field(); ?>
                    <?php echo method_field('PATCH'); ?>
                    <button type="submit" class="btn btn-primary flex items-center gap-2">
                        <i data-lucide="check-circle" class="w-4 h-4"></i> Tasdiqlash
                    </button>
                </form>
            <?php endif; ?>
            <?php if($seller->status !== 'rejected'): ?>
                <form method="POST" action="<?php echo e(route('admin.sellers.reject', $seller)); ?>" onsubmit="return confirm('Sotuvchini rad etishga ishonchingiz komilmi?')">
                    <?php echo csrf_field(); ?>
                    <?php echo method_field('PATCH'); ?>
                    <button type="submit" class="btn btn-danger flex items-center gap-2">
                        <i data-lucide="x-circle" class="w-4 h-4"></i> Rad etish
                    </button>
                </form>
            <?php endif; ?>
        </div>
    </div>
</div>


<?php if(!$seller->parent_id && !empty($seller->qr_token)): ?>
<?php
    $qrUrl    = $seller->qrUrl();
    $qrImgSrc = 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&margin=12&data=' . urlencode($qrUrl);
?>
<div class="card p-5 mb-6">
    <div class="flex items-start gap-5 flex-wrap">
        <div class="shrink-0 bg-white p-3 rounded-lg border border-slate-200 dark:border-slate-700">
            <img src="<?php echo e($qrImgSrc); ?>" alt="Do'kon QR" width="180" height="180" loading="lazy">
        </div>

        <div class="flex-1 min-w-[260px]">
            <div class="flex items-center gap-2 mb-2">
                <i data-lucide="qr-code" class="w-5 h-5 text-teal-500"></i>
                <h3 class="text-base font-semibold">Do'kon QR kodi</h3>
            </div>
            <p class="text-sm text-gray-500 dark:text-gray-400 mb-3">
                Mijoz do'konga kirib shu QR'ni Kitobchi ilovasi orqali skaner qilsa,
                "Do'kon ichida" rejimi yoqiladi va faqat shu sotuvchi mahsulotlari ko'rinadi.
                QR'ni A4 yoki kichikroq formatda chop etib do'konning ko'rinarli joyiga osib qo'ying.
            </p>

            <dl class="grid grid-cols-3 gap-2 text-xs mb-3">
                <dt class="text-gray-500">URL</dt>
                <dd class="col-span-2 font-mono break-all text-gray-700 dark:text-gray-300"><?php echo e($qrUrl); ?></dd>

                <dt class="text-gray-500">Token</dt>
                <dd class="col-span-2 font-mono text-gray-700 dark:text-gray-300"><?php echo e($seller->qr_token); ?></dd>

                <?php if($seller->qr_rotated_at): ?>
                <dt class="text-gray-500">Yangilangan</dt>
                <dd class="col-span-2"><?php echo e($seller->qr_rotated_at->format('Y-m-d H:i')); ?></dd>
                <?php endif; ?>
            </dl>

            <div class="flex items-center gap-2 flex-wrap">
                <a href="<?php echo e($qrImgSrc); ?>" target="_blank" rel="noopener"
                   class="btn btn-outline-primary btn-sm flex items-center gap-1">
                    <i data-lucide="external-link" class="w-4 h-4"></i> Katta hajmda ochish
                </a>
                <a href="<?php echo e(str_replace('size=300x300', 'size=600x600', $qrImgSrc)); ?>" download="kitobchi-shop-<?php echo e($seller->id); ?>.png"
                   class="btn btn-outline-secondary btn-sm flex items-center gap-1">
                    <i data-lucide="download" class="w-4 h-4"></i> Yuklab olish (600px)
                </a>
                <form method="POST" action="<?php echo e(route('admin.sellers.qr.rotate', $seller)); ?>"
                      onsubmit="return confirm('Eski QR ishlamay qoladi. Yangi QR\'ni do\'konga osib qo\'ying. Davom etamizmi?')">
                    <?php echo csrf_field(); ?>
                    <button type="submit" class="btn btn-outline-warning btn-sm flex items-center gap-1">
                        <i data-lucide="refresh-cw" class="w-4 h-4"></i> QR'ni yangilash
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<div class="grid grid-cols-1 xl:grid-cols-3 gap-6 mb-6">
    <div class="card p-5 xl:col-span-2">
        <div class="flex items-center justify-between gap-3 mb-4 flex-wrap">
            <div>
                <h3 class="font-bold text-base">Ogohlantirish yuborish</h3>
                <p class="text-sm text-gray-500 mt-1">3 ta faol ogohlantirishdan keyin sotuvchi avtomatik bloklanadi.</p>
            </div>
            <?php if($isBlocked): ?>
                <span class="badge badge-danger">Seller hozir bloklangan</span>
            <?php endif; ?>
        </div>
        <form method="POST" action="<?php echo e(route('admin.sellers.warn', $seller)); ?>" class="grid grid-cols-1 gap-3">
            <?php echo csrf_field(); ?>
            <div>
                <label class="text-xs text-gray-500 mb-1 block">Sarlavha</label>
                <input name="title" class="input" maxlength="120" placeholder="Masalan: Qoida buzilishi" value="<?php echo e(old('title')); ?>">
            </div>
            <div>
                <label class="text-xs text-gray-500 mb-1 block">Xabar</label>
                <textarea name="message" rows="4" class="input" placeholder="Sellerga ko‘rinadigan ogohlantirish matni"><?php echo e(old('message')); ?></textarea>
            </div>
            <div class="flex items-center justify-end">
                <button type="submit" class="btn btn-warning flex items-center gap-2">
                    <i data-lucide="triangle-alert" class="w-4 h-4"></i> Ogohlantirish yuborish
                </button>
            </div>
        </form>
    </div>

    <div class="card p-5">
        <h3 class="font-bold text-base mb-4">Bloklash holati</h3>
        <div class="space-y-3 text-sm">
            <div class="flex items-center justify-between gap-3">
                <span class="text-gray-500">Do‘kon statusi</span>
                <span class="badge <?php echo e($isBlocked ? 'badge-danger' : 'badge-success'); ?>">
                    <?php echo e($isBlocked ? 'Bloklangan' : 'Faol'); ?>

                </span>
            </div>
            <div class="flex items-center justify-between gap-3">
                <span class="text-gray-500">Faol ogohlantirishlar</span>
                <span class="font-semibold"><?php echo e($warningCount); ?>/3</span>
            </div>
            <div class="rounded-2xl bg-gray-50 dark:bg-white/5 p-4 text-xs text-gray-500">
                Blokdan chiqarilganda ogohlantirish hisobi qayta boshlanadi. Eski ogohlantirishlar audit uchun tarixda saqlanadi.
            </div>
        </div>
    </div>
</div>


<div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-4 mb-6">
    <div class="card p-4 flex items-center gap-3">
        <div class="w-10 h-10 rounded-xl bg-blue-100 dark:bg-blue-500/10 flex items-center justify-center shrink-0">
            <i data-lucide="shopping-bag" class="w-5 h-5 text-blue-500"></i>
        </div>
        <div class="min-w-0">
            <p class="text-[10px] text-gray-500 uppercase tracking-wide">Jami buyurtma</p>
            <p class="text-xl font-bold leading-tight"><?php echo e(number_format($orderCount, 0, '.', ' ')); ?></p>
        </div>
    </div>

    <div class="card p-4 flex items-center gap-3">
        <div class="w-10 h-10 rounded-xl bg-emerald-100 dark:bg-emerald-500/10 flex items-center justify-center shrink-0">
            <i data-lucide="circle-check" class="w-5 h-5 text-emerald-500"></i>
        </div>
        <div class="min-w-0">
            <p class="text-[10px] text-gray-500 uppercase tracking-wide">Muvaffaqiyatli</p>
            <p class="text-xl font-bold leading-tight"><?php echo e(number_format($seller->successful_orders ?? 0, 0, '.', ' ')); ?></p>
        </div>
    </div>

    <div class="card p-4 flex items-center gap-3">
        <div class="w-10 h-10 rounded-xl bg-green-100 dark:bg-green-500/10 flex items-center justify-center shrink-0">
            <i data-lucide="banknote" class="w-5 h-5 text-green-500"></i>
        </div>
        <div class="min-w-0">
            <p class="text-[10px] text-gray-500 uppercase tracking-wide">Balans</p>
            <p class="text-xl font-bold leading-tight"><?php echo e(number_format($seller->balance ?? 0, 0, '.', ' ')); ?></p>
            <p class="text-[10px] text-gray-400">UZS</p>
        </div>
    </div>

    <div class="card p-4 flex items-center gap-3">
        <div class="w-10 h-10 rounded-xl bg-yellow-100 dark:bg-yellow-500/10 flex items-center justify-center shrink-0">
            <i data-lucide="star" class="w-5 h-5 text-yellow-500"></i>
        </div>
        <div class="min-w-0">
            <p class="text-[10px] text-gray-500 uppercase tracking-wide">Reyting</p>
            <p class="text-xl font-bold leading-tight"><?php echo e(number_format($seller->rating ?? 0, 2)); ?></p>
            <p class="text-[10px] text-gray-400"><?php echo e($seller->total_reviews ?? 0); ?> sharh</p>
        </div>
    </div>

    <div class="card p-4 flex items-center gap-3">
        <div class="w-10 h-10 rounded-xl bg-indigo-100 dark:bg-indigo-500/10 flex items-center justify-center shrink-0">
            <i data-lucide="timer" class="w-5 h-5 text-indigo-500"></i>
        </div>
        <div class="min-w-0">
            <p class="text-[10px] text-gray-500 uppercase tracking-wide">Javob vaqti</p>
            <p class="text-xl font-bold leading-tight"><?php echo e(number_format($seller->response_time_hours ?? 0, 1)); ?></p>
            <p class="text-[10px] text-gray-400">soat</p>
        </div>
    </div>

    <div class="card p-4 flex items-center gap-3">
        <div class="w-10 h-10 rounded-xl bg-purple-100 dark:bg-purple-500/10 flex items-center justify-center shrink-0">
            <i data-lucide="book-open" class="w-5 h-5 text-purple-500"></i>
        </div>
        <div class="min-w-0">
            <p class="text-[10px] text-gray-500 uppercase tracking-wide">Kitoblar</p>
            <p class="text-xl font-bold leading-tight"><?php echo e($seller->books_count ?? 0); ?></p>
        </div>
    </div>
</div>


<div class="grid grid-cols-1 lg:grid-cols-2 gap-4 mb-6">
    <div class="card p-5 flex items-center gap-4">
        <div class="w-12 h-12 rounded-xl bg-green-100 dark:bg-green-500/10 flex items-center justify-center">
            <i data-lucide="trending-up" class="w-6 h-6 text-green-500"></i>
        </div>
        <div class="flex-1">
            <p class="text-xs text-gray-500">Jami daromad (tasdiqlangan yechib olishlar)</p>
            <p class="text-2xl font-bold"><?php echo e(number_format($totalRevenue, 0, '.', ' ')); ?> <span class="text-sm text-gray-400 font-normal">UZS</span></p>
        </div>
    </div>
    <div class="card p-5 flex items-center gap-4">
        <div class="w-12 h-12 rounded-xl bg-amber-100 dark:bg-amber-500/10 flex items-center justify-center">
            <i data-lucide="crown" class="w-6 h-6 text-amber-500"></i>
        </div>
        <div class="flex-1">
            <p class="text-xs text-gray-500">Premium holati</p>
            <?php if($seller->isPremiumShop && $seller->isPremiumExpiresAt && $seller->isPremiumExpiresAt->isFuture()): ?>
                <p class="text-lg font-bold text-amber-600 dark:text-amber-300">Faol</p>
                <p class="text-xs text-gray-400">
                    Tugaydi: <span class="font-mono"><?php echo e($seller->isPremiumExpiresAt->format('Y-m-d H:i')); ?></span>
                    (<?php echo e($seller->isPremiumExpiresAt->diffForHumans()); ?>)
                </p>
            <?php else: ?>
                <p class="text-lg font-bold text-gray-400">Yo'q</p>
                <p class="text-xs text-gray-400">Tahrirlash sahifasidan berish mumkin</p>
            <?php endif; ?>
        </div>
    </div>
</div>


<div class="grid grid-cols-1 lg:grid-cols-2 gap-4 mb-6">
    
    <div class="card p-5">
        <div class="flex items-center justify-between mb-4">
            <h3 class="font-bold text-base flex items-center gap-2">
                <i data-lucide="file-signature" class="w-5 h-5 text-blue-500"></i>
                Shartnoma
            </h3>
            <a href="<?php echo e(route('admin.sellers.edit', $seller)); ?>#contract" class="text-xs text-blue-500 hover:underline">Tahrirlash</a>
        </div>
        <?php if(empty($seller->contract_number) && empty($seller->contract_expires_at) && !$seller->contract_signed): ?>
            <p class="text-sm text-gray-400 text-center py-4">Shartnoma ma'lumotlari kiritilmagan.</p>
        <?php else: ?>
            <dl class="grid grid-cols-3 gap-y-2 gap-x-3 text-sm">
                
                <dt class="col-span-1 text-gray-500">Imzo holati</dt>
                <dd class="col-span-2">
                    <?php if($seller->contract_signed): ?>
                        <span class="inline-flex items-center gap-1 px-2 py-0.5 text-xs rounded-full bg-emerald-100 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-400">
                            <i data-lucide="check-circle-2" class="w-3.5 h-3.5"></i> Imzolangan
                        </span>
                    <?php else: ?>
                        <span class="inline-flex items-center gap-1 px-2 py-0.5 text-xs rounded-full bg-gray-200 text-gray-600 dark:bg-white/10 dark:text-gray-400">
                            <i data-lucide="circle-dashed" class="w-3.5 h-3.5"></i> Imzolanmagan
                        </span>
                    <?php endif; ?>
                </dd>
                <?php if($seller->contract_number): ?>
                    <dt class="col-span-1 text-gray-500">№</dt>
                    <dd class="col-span-2 font-mono"><?php echo e($seller->contract_number); ?></dd>
                <?php endif; ?>
                <?php if($seller->contract_signed_at): ?>
                    <dt class="col-span-1 text-gray-500">Imzolandi</dt>
                    <dd class="col-span-2"><?php echo e($seller->contract_signed_at->format('Y-m-d')); ?></dd>
                <?php endif; ?>
                <?php if($seller->contract_expires_at): ?>
                    <dt class="col-span-1 text-gray-500">Tugaydi</dt>
                    <dd class="col-span-2">
                        <?php echo e($seller->contract_expires_at->format('Y-m-d')); ?>

                        <?php $d = (int) now()->startOfDay()->diffInDays($seller->contract_expires_at, false); ?>
                        <span class="text-xs <?php echo e($d < 0 ? 'text-red-500' : ($d <= 30 ? 'text-amber-500' : 'text-gray-400')); ?>">
                            (<?php echo e($d < 0 ? abs($d).' kun oldin tugagan' : $d.' kun qoldi'); ?>)
                        </span>
                    </dd>
                <?php endif; ?>
                <?php if($seller->contract_notes): ?>
                    <dt class="col-span-3 text-gray-500 mt-1">Izoh</dt>
                    <dd class="col-span-3 text-gray-600 dark:text-gray-400"><?php echo e($seller->contract_notes); ?></dd>
                <?php endif; ?>
            </dl>
            <?php if($seller->contract_expires_at): ?>
                <form method="POST" action="<?php echo e(route('admin.sellers.contract.extend', $seller)); ?>" class="mt-3 flex items-center gap-2">
                    <?php echo csrf_field(); ?>
                    <?php echo method_field('PATCH'); ?>
                    <select name="months" class="select h-9 text-xs flex-1">
                        <option value="3">+3 oy</option>
                        <option value="6">+6 oy</option>
                        <option value="12" selected>+12 oy</option>
                        <option value="24">+24 oy</option>
                    </select>
                    <button type="submit" class="btn btn-secondary text-xs flex items-center gap-1 whitespace-nowrap">
                        <i data-lucide="calendar-plus" class="w-3.5 h-3.5"></i> Uzaytirish
                    </button>
                </form>
            <?php endif; ?>
        <?php endif; ?>
    </div>

    
    <div class="card p-5">
        <div class="flex items-center justify-between mb-4">
            <h3 class="font-bold text-base flex items-center gap-2">
                <i data-lucide="landmark" class="w-5 h-5 text-emerald-500"></i>
                Rekvizitlar
            </h3>
            <a href="<?php echo e(route('admin.sellers.edit', $seller)); ?>#legal" class="text-xs text-blue-500 hover:underline">Tahrirlash</a>
        </div>
        <?php
            $hasAny = $seller->legal_type || $seller->inn || $seller->bank_account || $seller->payment_card;
        ?>
        <?php if(!$hasAny): ?>
            <p class="text-sm text-gray-400 text-center py-4">Rekvizitlar kiritilmagan.</p>
        <?php else: ?>
            <dl class="grid grid-cols-3 gap-y-2 gap-x-3 text-sm">
                <?php if($seller->legal_type): ?>
                    <dt class="col-span-1 text-gray-500">Turi</dt>
                    <dd class="col-span-2">
                        <?php switch($seller->legal_type):
                            case ('individual'): ?>   Jismoniy shaxs <?php break; ?>
                            <?php case ('entrepreneur'): ?> Yakka tartibdagi tadbirkor <?php break; ?>
                            <?php case ('llc'): ?>          MChJ <?php break; ?>
                            <?php case ('jsc'): ?>          AJ / OAJ <?php break; ?>
                            <?php default: ?>              <?php echo e($seller->legal_type); ?>

                        <?php endswitch; ?>
                    </dd>
                <?php endif; ?>
                <?php if($seller->inn): ?>
                    <dt class="col-span-1 text-gray-500">STIR</dt>
                    <dd class="col-span-2 font-mono"><?php echo e($seller->inn); ?></dd>
                <?php endif; ?>
                <?php if($seller->passport_series || $seller->passport_number): ?>
                    <dt class="col-span-1 text-gray-500">Pasport</dt>
                    <dd class="col-span-2 font-mono"><?php echo e($seller->passport_series); ?> <?php echo e($seller->passport_number); ?></dd>
                <?php endif; ?>
                <?php if($seller->bank_name): ?>
                    <dt class="col-span-1 text-gray-500">Bank</dt>
                    <dd class="col-span-2"><?php echo e($seller->bank_name); ?></dd>
                <?php endif; ?>
                <?php if($seller->bank_account): ?>
                    <dt class="col-span-1 text-gray-500">Hisob</dt>
                    <dd class="col-span-2 font-mono"><?php echo e($seller->masked_bank_account); ?></dd>
                <?php endif; ?>
                <?php if($seller->bank_mfo): ?>
                    <dt class="col-span-1 text-gray-500">MFO</dt>
                    <dd class="col-span-2 font-mono"><?php echo e($seller->bank_mfo); ?></dd>
                <?php endif; ?>
                <?php if($seller->payment_card): ?>
                    <dt class="col-span-1 text-gray-500">Karta</dt>
                    <dd class="col-span-2 font-mono"><?php echo e($seller->masked_card); ?></dd>
                <?php endif; ?>
                <?php if($seller->card_holder): ?>
                    <dt class="col-span-1 text-gray-500">Egasi</dt>
                    <dd class="col-span-2"><?php echo e($seller->card_holder); ?></dd>
                <?php endif; ?>
                <?php if($seller->legal_address): ?>
                    <dt class="col-span-3 text-gray-500 mt-1">Manzil</dt>
                    <dd class="col-span-3"><?php echo e($seller->legal_address); ?></dd>
                <?php endif; ?>
            </dl>
        <?php endif; ?>
    </div>
</div>


<div class="grid grid-cols-1 lg:grid-cols-2 gap-4 mb-6">
    
    <div class="card p-5">
        <div class="flex items-center justify-between mb-4">
            <h3 class="font-bold text-base flex items-center gap-2">
                <i data-lucide="folder" class="w-5 h-5 text-indigo-500"></i>
                Hujjatlar (<?php echo e($seller->documents->count()); ?>)
            </h3>
            <a href="<?php echo e(route('admin.sellers.edit', $seller)); ?>#documents" class="text-xs text-blue-500 hover:underline">Yuklash / boshqarish</a>
        </div>
        <?php if($seller->documents->isEmpty()): ?>
            <p class="text-sm text-gray-400 text-center py-4">Hujjatlar yuklanmagan.</p>
        <?php else: ?>
            <ul class="divide-y divide-gray-100 dark:divide-white/10">
                <?php $__currentLoopData = $seller->documents->take(6); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $doc): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <li class="flex items-center gap-3 py-2.5">
                        <i data-lucide="<?php echo e($doc->type_icon); ?>" class="w-4 h-4 text-gray-400 flex-shrink-0"></i>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm truncate"><?php echo e($doc->type_label); ?><?php if($doc->original_name): ?> — <span class="text-gray-400"><?php echo e($doc->original_name); ?></span><?php endif; ?></p>
                            <p class="text-[10px] text-gray-400"><?php echo e($doc->created_at?->format('Y-m-d H:i')); ?></p>
                        </div>
                        <a href="<?php echo e($doc->file_url); ?>" target="_blank" class="text-blue-500 hover:underline text-xs flex items-center gap-1 flex-shrink-0">
                            <i data-lucide="external-link" class="w-3 h-3"></i> Ochish
                        </a>
                    </li>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </ul>
            <?php if($seller->documents->count() > 6): ?>
                <p class="text-xs text-gray-400 mt-2 text-center">Yana <?php echo e($seller->documents->count() - 6); ?> ta hujjat...</p>
            <?php endif; ?>
        <?php endif; ?>
    </div>

    
    <div class="card p-5">
        <h3 class="font-bold text-base flex items-center gap-2 mb-4">
            <i data-lucide="history" class="w-5 h-5 text-amber-500"></i>
            Shartnoma tarixi
        </h3>
        <?php if($seller->contractHistory->isEmpty()): ?>
            <p class="text-sm text-gray-400 text-center py-4">Tarix yo'q.</p>
        <?php else: ?>
            <ol class="space-y-2">
                <?php $__currentLoopData = $seller->contractHistory->take(8); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $h): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <li class="flex items-start gap-3 text-xs">
                        <span class="inline-flex items-center justify-center px-2 py-0.5 rounded-full bg-<?php echo e($h->action_color); ?>-100 text-<?php echo e($h->action_color); ?>-700 dark:bg-<?php echo e($h->action_color); ?>-500/10 dark:text-<?php echo e($h->action_color); ?>-400 font-medium flex-shrink-0">
                            <?php echo e($h->action_label); ?>

                        </span>
                        <div class="flex-1 min-w-0">
                            <div class="text-gray-700 dark:text-gray-300">
                                <?php if($h->old_expires_at && $h->new_expires_at): ?>
                                    <span class="font-mono"><?php echo e($h->old_expires_at->format('Y-m-d')); ?></span>
                                    →
                                    <span class="font-mono font-semibold"><?php echo e($h->new_expires_at->format('Y-m-d')); ?></span>
                                <?php elseif($h->new_expires_at): ?>
                                    <span class="font-mono"><?php echo e($h->new_expires_at->format('Y-m-d')); ?></span>
                                <?php endif; ?>
                            </div>
                            <?php if($h->notes): ?>
                                <p class="text-gray-500 mt-0.5 truncate"><?php echo e($h->notes); ?></p>
                            <?php endif; ?>
                            <p class="text-[10px] text-gray-400 mt-0.5">
                                <?php echo e($h->created_at?->format('Y-m-d H:i')); ?>

                                <?php if($h->performer): ?>
                                    · <?php echo e(trim($h->performer->name . ' ' . $h->performer->lastname)); ?>

                                <?php endif; ?>
                            </p>
                        </div>
                    </li>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </ol>
        <?php endif; ?>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    
    <div class="card p-5">
        <h3 class="font-bold text-base mb-4">So'nggi buyurtmalar</h3>
        <div class="table-wrap">
            <div class="overflow-x-auto">
                <table class="tbl">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Summa</th>
                            <th>Sana</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $__empty_1 = true; $__currentLoopData = $recentOrders; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $order): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                            <tr>
                                <td class="text-gray-500 text-sm">#<?php echo e($order->id); ?></td>
                                <td class="font-semibold"><?php echo e(number_format((float)($order->amount ?? $order->total ?? 0), 0, '.', ' ')); ?> UZS</td>
                                <td class="text-sm text-gray-500">
                                    <?php echo e($order->created_at ? $order->created_at->format('d.m.Y') : '—'); ?>

                                </td>
                            </tr>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                            <tr>
                                <td colspan="3" class="text-center text-gray-400 py-6">Buyurtmalar yo'q</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    
    <div class="card p-5">
        <h3 class="font-bold text-base mb-4">So'nggi tranzaksiyalar</h3>
        <div class="table-wrap">
            <div class="overflow-x-auto">
                <table class="tbl">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Summa</th>
                            <th>Holat</th>
                            <th>Sana</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $__empty_1 = true; $__currentLoopData = $transactions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $tx): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                            <tr>
                                <td class="text-gray-500 text-sm">#<?php echo e($tx->id); ?></td>
                                <td class="font-semibold"><?php echo e(number_format((float)($tx->amount ?? 0), 0, '.', ' ')); ?> UZS</td>
                                <td>
                                    <?php if(in_array($tx->status, ['success', 'completed', 'approved'])): ?>
                                        <span class="badge badge-success">Muvaffaqiyatli</span>
                                    <?php elseif(in_array($tx->status, ['pending', 'processing'])): ?>
                                        <span class="badge badge-warning">Kutilmoqda</span>
                                    <?php elseif(in_array($tx->status, ['failed', 'rejected', 'cancelled'])): ?>
                                        <span class="badge badge-danger">Rad etilgan</span>
                                    <?php else: ?>
                                        <span class="badge badge-muted"><?php echo e($tx->status ?? '—'); ?></span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-sm text-gray-500">
                                    <?php echo e($tx->created_at ? $tx->created_at->format('d.m.Y') : '—'); ?>

                                </td>
                            </tr>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                            <tr>
                                <td colspan="4" class="text-center text-gray-400 py-6">Tranzaksiyalar yo'q</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="card p-5 mt-6">
    <h3 class="font-bold text-base mb-4">Seller loglari</h3>
    <div class="table-wrap">
        <div class="overflow-x-auto">
            <table class="tbl">
                <thead>
                    <tr>
                        <th>Vaqt</th>
                        <th>Log</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $__empty_1 = true; $__currentLoopData = $staffLogs; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $log): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        <tr>
                            <td class="text-sm text-gray-500 whitespace-nowrap">
                                <?php echo e($log->created_at ? $log->created_at->format('d.m.Y H:i') : '—'); ?>

                            </td>
                            <td><?php echo e($log->text ?? '—'); ?></td>
                        </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                        <tr>
                            <td colspan="2" class="text-center text-gray-400 py-6">Loglar topilmadi</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="card p-5 mt-6">
    <h3 class="font-bold text-base mb-4">Ogohlantirish va unblock tarixi</h3>
    <div class="table-wrap">
        <div class="overflow-x-auto">
            <table class="tbl">
                <thead>
                    <tr>
                        <th>Vaqt</th>
                        <th>Turi</th>
                        <th>Sarlavha</th>
                        <th>Xabar</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $__empty_1 = true; $__currentLoopData = $banLogs; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $banLog): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        <tr>
                            <td class="text-sm text-gray-500 whitespace-nowrap">
                                <?php echo e($banLog->created_at ? $banLog->created_at->format('d.m.Y H:i') : '—'); ?>

                            </td>
                            <td>
                                <span class="badge <?php echo e($banLog->type === 'warning' ? 'badge-warning' : 'badge-success'); ?>">
                                    <?php echo e($banLog->type === 'warning' ? 'Ogohlantirish' : 'Unblock'); ?>

                                </span>
                            </td>
                            <td><?php echo e($banLog->title ?? '—'); ?></td>
                            <td class="text-sm text-gray-600 dark:text-gray-300"><?php echo e($banLog->message ?? '—'); ?></td>
                        </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                        <tr>
                            <td colspan="4" class="text-center text-gray-400 py-6">Ogohlantirishlar tarixi topilmadi</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('a122.layouts.admin', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/abbos/PROJECTS/MY/kitobchi-server/kitobchi-laravel/resources/views/a122/sellers/show.blade.php ENDPATH**/ ?>