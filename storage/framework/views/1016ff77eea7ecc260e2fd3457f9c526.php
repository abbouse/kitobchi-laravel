<?php $__env->startSection('title', $seller->shop_name); ?>
<?php $__env->startSection('page-title', 'Sotuvchi profili'); ?>

<?php $__env->startSection('content'); ?>
<?php
    $safeFormatDate = function ($value, string $format = 'Y-m-d') {
        return rescue(function () use ($value, $format) {
            if (blank($value)) {
                return null;
            }

            return \Illuminate\Support\Carbon::parse($value)->format($format);
        }, null, false);
    };

    $safeDiffForHumans = function ($value) {
        return rescue(function () use ($value) {
            if (blank($value)) {
                return null;
            }

            return \Illuminate\Support\Carbon::parse($value)->diffForHumans();
        }, null, false);
    };

    $safeDayDiff = function ($value) {
        return rescue(function () use ($value) {
            if (blank($value)) {
                return null;
            }

            return (int) now()->startOfDay()->diffInDays(\Illuminate\Support\Carbon::parse($value), false);
        }, null, false);
    };
?>

<?php if(!empty($debugMode)): ?>
    <div class="a122-section mb-6 border border-amber-300 bg-amber-50">
        <div class="a122-section-body">
            <div class="flex items-center gap-2 mb-3 text-amber-800">
                <i data-lucide="bug" class="w-4 h-4"></i>
                <strong>Seller show debug mode</strong>
            </div>

            <?php if(empty($debugIssues)): ?>
                <p class="text-sm text-amber-700">Controller darajasida xato ushlanmadi. Muammo layout/frontend yoki PHP-FPM tomonda bo‘lishi mumkin.</p>
            <?php else: ?>
                <div class="space-y-2">
                    <?php $__currentLoopData = $debugIssues; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $issue): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <div class="rounded-xl border border-amber-200 bg-white px-3 py-2 text-sm text-slate-700">
                            <div class="font-semibold"><?php echo e($issue['type'] ?? 'issue'); ?>: <?php echo e($issue['key'] ?? 'unknown'); ?></div>
                            <div class="mt-1 text-slate-600"><?php echo e($issue['message'] ?? 'No message'); ?></div>
                        </div>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
<?php endif; ?>

<?php if (isset($component)) { $__componentOriginal0c1345684b2d774f43a544669f5684b0 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal0c1345684b2d774f43a544669f5684b0 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.a122.page-header','data' => ['backHref' => ''.e(route('admin.sellers.index')).'']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('a122.page-header'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['back-href' => ''.e(route('admin.sellers.index')).'']); ?>
     <?php $__env->slot('heading', null, []); ?> <?php echo e($seller->shop_name); ?> <?php $__env->endSlot(); ?>
     <?php $__env->slot('meta', null, []); ?> <?php echo e(trim($seller->firstname . ' ' . $seller->lastname) ?: 'Sotuvchi profili'); ?> · <?php echo e($seller->region ?: 'Hudud ko‘rsatilmagan'); ?> <?php $__env->endSlot(); ?>
     <?php $__env->slot('actions', null, []); ?> 
        <?php if($seller->status === 'blocked'): ?>
            <form method="POST" action="<?php echo e(route('admin.sellers.unblock', $seller)); ?>" onsubmit="return confirm('Sotuvchini blokdan chiqarmoqchimisiz?')">
                <?php echo csrf_field(); ?>
                <?php echo method_field('PATCH'); ?>
                <button type="submit" class="btn-p success flex items-center gap-2">
                    <i data-lucide="unlock" class="w-4 h-4"></i> Blokdan chiqarish
                </button>
            </form>
        <?php endif; ?>
        <form method="POST" action="<?php echo e(route('admin.sellers.reset-password', $seller)); ?>" onsubmit="return confirm('Yangi parol sotuvchining telefon raqamiga SMS orqali yuborilsinmi?')">
            <?php echo csrf_field(); ?>
            <button type="submit" class="btn-p ghost flex items-center gap-2">
                <i data-lucide="key-round" class="w-4 h-4"></i> Parolni SMS bilan yangilash
            </button>
        </form>
        <a href="<?php echo e(route('admin.sellers.edit', $seller)); ?>" class="btn-p primary flex items-center gap-2">
            <i data-lucide="pencil" class="w-4 h-4"></i> Tahrirlash
        </a>
     <?php $__env->endSlot(); ?>
 <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal0c1345684b2d774f43a544669f5684b0)): ?>
<?php $attributes = $__attributesOriginal0c1345684b2d774f43a544669f5684b0; ?>
<?php unset($__attributesOriginal0c1345684b2d774f43a544669f5684b0); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal0c1345684b2d774f43a544669f5684b0)): ?>
<?php $component = $__componentOriginal0c1345684b2d774f43a544669f5684b0; ?>
<?php unset($__componentOriginal0c1345684b2d774f43a544669f5684b0); ?>
<?php endif; ?>


<div class="a122-section mb-6">
    <div class="a122-section-body">
    <div class="flex flex-col sm:flex-row sm:items-center gap-4">
        
        <div class="shrink-0">
            <?php echo $__env->make('a122.partials.avatar', [
                'name' => $seller->shop_name,
                'image' => $seller->photo,
                'class' => 'w-20 h-20 rounded-full text-2xl font-bold ring-4 ring-emerald-100 dark:ring-emerald-500/20',
            ], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
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
                <?php if(($premiumState['is_premium'] ?? false) && !empty($premiumState['premium_expires_at'])): ?>
                    <span class="badge bg-amber-100 text-amber-700 dark:bg-amber-400/10 dark:text-amber-300 flex items-center gap-1">
                        <i data-lucide="crown" class="w-3.5 h-3.5"></i>
                        Premium · <?php echo e($safeFormatDate($premiumState['premium_expires_at'], 'Y-m-d') ?? '—'); ?>

                    </span>
                <?php elseif(!empty($premiumState['premium_expires_at'])): ?>
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
                        $cDays = $safeDayDiff($seller->getRawOriginal('contract_expires_at'));
                        $cDays ??= 0;
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
            <p class="text-sm text-gray-500 mt-1"><?php echo e(trim(($seller->firstname ?? '') . ' ' . ($seller->lastname ?? ''))); ?></p>
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
                    <button type="submit" class="btn-p primary flex items-center gap-2">
                        <i data-lucide="check-circle" class="w-4 h-4"></i> Tasdiqlash
                    </button>
                </form>
            <?php endif; ?>
            <?php if($seller->status !== 'rejected'): ?>
                <form method="POST" action="<?php echo e(route('admin.sellers.reject', $seller)); ?>" onsubmit="return confirm('Sotuvchini rad etishga ishonchingiz komilmi?')">
                    <?php echo csrf_field(); ?>
                    <?php echo method_field('PATCH'); ?>
                    <button type="submit" class="btn-p danger flex items-center gap-2">
                        <i data-lucide="x-circle" class="w-4 h-4"></i> Rad etish
                    </button>
                </form>
            <?php endif; ?>
        </div>
    </div>
    </div>
</div>


<?php if(!$seller->parent_id && $locations->count() > 0): ?>
<div class="a122-section mb-6">
    <div class="a122-section-head">
        <div>
            <div class="flex items-center gap-2 mb-1">
                <i data-lucide="qr-code" class="w-5 h-5 text-teal-500"></i>
                <div class="a122-section-head__title">Filial QR kodlari</div>
            </div>
            <div class="a122-section-head__meta max-w-3xl">
                Har bir filial uchun alohida QR ishlatiladi. Mijoz qaysi filialdagi QR'ni skaner qilsa, aynan o'sha filialning "do'kon ichida" rejimi boshlanadi.
                <span class="font-medium">Asosiy filial</span> belgisi esa faqat kuryerlar borishi kerak bo'lgan default manzilni bildiradi.
            </div>
        </div>
    </div>

    <div class="a122-section-body">
    <div class="grid grid-cols-1 xl:grid-cols-2 gap-5">
        <?php $__currentLoopData = $locations; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $location): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <?php
                $qrUrl = $location->qr_url;
                $qrImgSrc = 'https://api.qrserver.com/v1/create-qr-code/?size=520x520&margin=22&format=png&ecc=Q&data=' . urlencode($qrUrl);
            ?>
            <div class="rounded-[28px] border border-slate-200 dark:border-slate-700 bg-[radial-gradient(circle_at_top_left,_rgba(20,184,166,0.12),_transparent_42%),linear-gradient(135deg,#ffffff,_#f8fafc)] dark:bg-slate-900 p-5 shadow-sm">
                <div class="flex items-start gap-4 flex-wrap">
                    <div class="shrink-0 rounded-[24px] bg-white p-3 border border-slate-200 shadow-sm">
                        <div class="rounded-2xl overflow-hidden bg-white">
                            <img src="<?php echo e($qrImgSrc); ?>" alt="Filial QR" width="170" height="170" loading="lazy">
                        </div>
                    </div>
                    <div class="flex-1 min-w-[250px]">
                        <div class="flex items-center gap-2 mb-3 flex-wrap">
                            <span class="badge <?php echo e($location->is_main ? 'badge-warning' : 'badge-secondary'); ?>">
                                <?php echo e($location->is_main ? 'Asosiy filial' : 'Filial'); ?>

                            </span>
                            <span class="badge badge-light">ID: <?php echo e($location->id); ?></span>
                            <?php if($location->is_main): ?>
                                <span class="badge badge-light">Kuryer default filial</span>
                            <?php endif; ?>
                        </div>

                        <p class="text-sm font-semibold text-slate-800 dark:text-slate-100 mb-1 leading-6">
                            <?php echo e($location->fullAddress); ?>

                        </p>
                        <?php if($location->description): ?>
                            <p class="text-xs text-slate-500 dark:text-slate-400 mb-3"><?php echo e($location->description); ?></p>
                        <?php endif; ?>

                        <dl class="grid grid-cols-3 gap-2 text-xs mb-3">
                            <dt class="text-slate-500">URL</dt>
                            <dd class="col-span-2 font-mono break-all text-slate-700 dark:text-slate-300"><?php echo e($qrUrl); ?></dd>

                            <dt class="text-slate-500">Token</dt>
                            <dd class="col-span-2 font-mono text-slate-700 dark:text-slate-300"><?php echo e($location->qr_token); ?></dd>

                            <?php if($location->getRawOriginal('qr_rotated_at')): ?>
                                <dt class="text-slate-500">Yangilangan</dt>
                                <dd class="col-span-2"><?php echo e($safeFormatDate($location->getRawOriginal('qr_rotated_at'), 'Y-m-d H:i') ?? '—'); ?></dd>
                            <?php endif; ?>
                        </dl>

                        <div class="flex items-center gap-2 flex-wrap pt-1">
                            <a href="<?php echo e($qrImgSrc); ?>" target="_blank" rel="noopener"
                               class="btn-p ghost sm flex items-center gap-1">
                                <i data-lucide="external-link" class="w-4 h-4"></i> Ochish
                            </a>
                            <a href="<?php echo e($qrImgSrc); ?>" download="kitobchi-location-<?php echo e($location->id); ?>.png"
                               class="btn-p ghost sm flex items-center gap-1">
                                <i data-lucide="download" class="w-4 h-4"></i> Yuklab olish
                            </a>
                            <form method="POST" action="<?php echo e(route('admin.sellers.locations.qr.rotate', [$storeSeller, $location])); ?>"
                                  onsubmit="return confirm('Eski filial QR ishlamay qoladi. Yangi QR\'ni shu filialga almashtiramizmi?')">
                                <?php echo csrf_field(); ?>
                                <button type="submit" class="btn-p sm flex items-center gap-1">
                                    <i data-lucide="refresh-cw" class="w-4 h-4"></i> QR'ni yangilash
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </div>
    <?php if($locations->hasPages()): ?>
        <div class="mt-4">
            <?php echo e($locations->links()); ?>

        </div>
    <?php endif; ?>
    </div>
</div>
<?php endif; ?>

<div class="grid grid-cols-1 xl:grid-cols-3 gap-6 mb-6">
    <div class="a122-section xl:col-span-2">
        <div class="a122-section-head">
            <div>
                <div class="a122-section-head__title">Ogohlantirish yuborish</div>
                <div class="a122-section-head__meta">3 ta faol ogohlantirishdan keyin sotuvchi avtomatik bloklanadi.</div>
            </div>
            <?php if($isBlocked): ?>
                <span class="badge badge-danger">Seller hozir bloklangan</span>
            <?php endif; ?>
        </div>
        <div class="a122-section-body">
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
                <button type="submit" class="btn-p flex items-center gap-2">
                    <i data-lucide="triangle-alert" class="w-4 h-4"></i> Ogohlantirish yuborish
                </button>
            </div>
        </form>
        </div>
    </div>

    <div class="a122-section">
        <div class="a122-section-head">
            <div>
                <div class="a122-section-head__title">Bloklash holati</div>
                <div class="a122-section-head__meta">Ogohlantirishlar limiti va joriy blok holati.</div>
            </div>
        </div>
        <div class="a122-section-body">
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
            <div class="a122-soft-note">
                Blokdan chiqarilganda ogohlantirish hisobi qayta boshlanadi. Eski ogohlantirishlar audit uchun tarixda saqlanadi.
            </div>
        </div>
        </div>
    </div>
</div>


<div class="a122-stat-grid mb-6">
    <div class="a122-stat-tile">
        <div class="a122-stat-tile__icon bg-blue-100 dark:bg-blue-500/10 text-blue-500">
            <i data-lucide="shopping-bag" class="w-5 h-5 text-blue-500"></i>
        </div>
        <div class="min-w-0">
            <p class="a122-stat-tile__label">Jami buyurtma</p>
            <p class="a122-stat-tile__value"><?php echo e(number_format($orderCount, 0, '.', ' ')); ?></p>
        </div>
    </div>

    <div class="a122-stat-tile">
        <div class="a122-stat-tile__icon bg-emerald-100 dark:bg-emerald-500/10 text-emerald-500">
            <i data-lucide="circle-check" class="w-5 h-5 text-emerald-500"></i>
        </div>
        <div class="min-w-0">
            <p class="a122-stat-tile__label">Muvaffaqiyatli</p>
            <p class="a122-stat-tile__value"><?php echo e(number_format($seller->successful_orders ?? 0, 0, '.', ' ')); ?></p>
        </div>
    </div>

    <div class="a122-stat-tile">
        <div class="a122-stat-tile__icon bg-green-100 dark:bg-green-500/10 text-green-500">
            <i data-lucide="banknote" class="w-5 h-5 text-green-500"></i>
        </div>
        <div class="min-w-0">
            <p class="a122-stat-tile__label">Balans</p>
            <p class="a122-stat-tile__value"><?php echo e(number_format($seller->balance ?? 0, 0, '.', ' ')); ?></p>
            <p class="a122-stat-tile__meta">UZS</p>
        </div>
    </div>

    <div class="a122-stat-tile">
        <div class="a122-stat-tile__icon bg-yellow-100 dark:bg-yellow-500/10 text-yellow-500">
            <i data-lucide="star" class="w-5 h-5 text-yellow-500"></i>
        </div>
        <div class="min-w-0">
            <p class="a122-stat-tile__label">Reyting</p>
            <p class="a122-stat-tile__value"><?php echo e(number_format($seller->rating ?? 0, 2)); ?></p>
            <p class="a122-stat-tile__meta"><?php echo e($seller->rating_reviews_count ?? 0); ?> sharh</p>
        </div>
    </div>

    <div class="a122-stat-tile">
        <div class="a122-stat-tile__icon bg-indigo-100 dark:bg-indigo-500/10 text-indigo-500">
            <i data-lucide="timer" class="w-5 h-5 text-indigo-500"></i>
        </div>
        <div class="min-w-0">
            <p class="a122-stat-tile__label">Javob vaqti</p>
            <p class="a122-stat-tile__value"><?php echo e(number_format($seller->response_time_hours ?? 0, 1)); ?></p>
            <p class="a122-stat-tile__meta">soat</p>
        </div>
    </div>

    <div class="a122-stat-tile">
        <div class="a122-stat-tile__icon bg-purple-100 dark:bg-purple-500/10 text-purple-500">
            <i data-lucide="book-open" class="w-5 h-5 text-purple-500"></i>
        </div>
        <div class="min-w-0">
            <p class="a122-stat-tile__label">Kitoblar</p>
            <p class="a122-stat-tile__value"><?php echo e($seller->books_count ?? 0); ?></p>
        </div>
    </div>
</div>


<div class="grid grid-cols-1 lg:grid-cols-2 gap-4 mb-6">
    <div class="a122-section">
        <div class="a122-section-body flex items-center gap-4">
        <div class="w-12 h-12 rounded-xl bg-green-100 dark:bg-green-500/10 flex items-center justify-center">
            <i data-lucide="trending-up" class="w-6 h-6 text-green-500"></i>
        </div>
        <div class="flex-1">
            <p class="text-xs text-gray-500">Jami daromad (tasdiqlangan yechib olishlar)</p>
            <p class="text-2xl font-bold"><?php echo e(number_format($totalRevenue, 0, '.', ' ')); ?> <span class="text-sm text-gray-400 font-normal">UZS</span></p>
        </div>
        </div>
    </div>
    <div class="a122-section">
        <div class="a122-section-body flex items-center gap-4">
        <div class="w-12 h-12 rounded-xl bg-amber-100 dark:bg-amber-500/10 flex items-center justify-center">
            <i data-lucide="crown" class="w-6 h-6 text-amber-500"></i>
        </div>
        <div class="flex-1">
            <p class="text-xs text-gray-500">Premium holati</p>
            <?php if(($premiumState['is_premium'] ?? false) && !empty($premiumState['premium_expires_at'])): ?>
                <p class="text-lg font-bold text-amber-600 dark:text-amber-300">Faol</p>
                <p class="text-xs text-gray-400">
                    Tugaydi: <span class="font-mono"><?php echo e($safeFormatDate($premiumState['premium_expires_at'], 'Y-m-d H:i') ?? '—'); ?></span>
                    <?php if($safeDiffForHumans($premiumState['premium_expires_at'])): ?>
                        (<?php echo e($safeDiffForHumans($premiumState['premium_expires_at'])); ?>)
                    <?php endif; ?>
                </p>
            <?php else: ?>
                <p class="text-lg font-bold text-gray-400">Yo'q</p>
                <p class="text-xs text-gray-400">Plan bo‘yicha premiumni tahrirlash sahifasidan berish mumkin</p>
            <?php endif; ?>
        </div>
        </div>
    </div>
</div>


<div class="grid grid-cols-1 lg:grid-cols-2 gap-4 mb-6">
    
    <div class="a122-section">
        <div class="a122-section-head">
            <div>
            <div class="a122-section-head__title flex items-center gap-2">
                <i data-lucide="file-signature" class="w-5 h-5 text-blue-500"></i>
                Shartnoma
            </div>
            <div class="a122-section-head__meta">Shartnoma holati, muddati va uzaytirish nazorati.</div>
            </div>
            <div class="a122-section-head__actions">
                <a href="<?php echo e(route('admin.sellers.edit', $seller)); ?>#contract" class="btn-p ghost sm">Tahrirlash</a>
            </div>
        </div>
        <div class="a122-section-body">
        <?php if(empty($seller->contract_number) && empty($seller->contract_expires_at) && !$seller->contract_signed): ?>
            <p class="text-sm text-gray-400 text-center py-4">Shartnoma ma'lumotlari kiritilmagan.</p>
        <?php else: ?>
            <dl class="a122-kv">
                
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
                <?php if($seller->getRawOriginal('contract_signed_at')): ?>
                    <dt class="col-span-1 text-gray-500">Imzolandi</dt>
                    <dd class="col-span-2"><?php echo e($safeFormatDate($seller->getRawOriginal('contract_signed_at')) ?? '—'); ?></dd>
                <?php endif; ?>
                <?php if($seller->getRawOriginal('contract_expires_at')): ?>
                    <dt class="col-span-1 text-gray-500">Tugaydi</dt>
                    <dd class="col-span-2">
                        <?php echo e($safeFormatDate($seller->getRawOriginal('contract_expires_at')) ?? '—'); ?>

                        <?php $d = $safeDayDiff($seller->getRawOriginal('contract_expires_at')) ?? 0; ?>
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
            <?php if($seller->getRawOriginal('contract_expires_at')): ?>
                <form method="POST" action="<?php echo e(route('admin.sellers.contract.extend', $seller)); ?>" class="mt-3 flex items-center gap-2">
                    <?php echo csrf_field(); ?>
                    <?php echo method_field('PATCH'); ?>
                    <select name="months" class="select h-9 text-xs flex-1">
                        <option value="3">+3 oy</option>
                        <option value="6">+6 oy</option>
                        <option value="12" selected>+12 oy</option>
                        <option value="24">+24 oy</option>
                    </select>
                    <button type="submit" class="btn-p ghost sm text-xs flex items-center gap-1 whitespace-nowrap">
                        <i data-lucide="calendar-plus" class="w-3.5 h-3.5"></i> Uzaytirish
                    </button>
                </form>
            <?php endif; ?>
        <?php endif; ?>
        </div>
    </div>

    
    <div class="a122-section">
        <div class="a122-section-head">
            <div>
            <div class="a122-section-head__title flex items-center gap-2">
                <i data-lucide="landmark" class="w-5 h-5 text-emerald-500"></i>
                Rekvizitlar
            </div>
            <div class="a122-section-head__meta">Yuridik va to‘lov rekvizitlari, bank va karta ma’lumotlari.</div>
            </div>
            <div class="a122-section-head__actions">
                <a href="<?php echo e(route('admin.sellers.edit', $seller)); ?>#legal" class="btn-p ghost sm">Tahrirlash</a>
            </div>
        </div>
        <div class="a122-section-body">
        <?php
            $hasAny = $seller->legal_type || $seller->inn || $seller->bank_account || $seller->payment_card;
        ?>
        <?php if(!$hasAny): ?>
            <p class="text-sm text-gray-400 text-center py-4">Rekvizitlar kiritilmagan.</p>
        <?php else: ?>
            <dl class="a122-kv">
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
</div>


<div class="grid grid-cols-1 lg:grid-cols-2 gap-4 mb-6">
    
    <div class="a122-section">
        <div class="a122-section-head">
            <div>
            <div class="a122-section-head__title flex items-center gap-2">
                <i data-lucide="folder" class="w-5 h-5 text-indigo-500"></i>
                Hujjatlar (<?php echo e($documents->total()); ?>)
            </div>
            <div class="a122-section-head__meta">Yuklangan fayllar va sotuvchining tekshiruv hujjatlari.</div>
            </div>
            <div class="a122-section-head__actions">
                <a href="<?php echo e(route('admin.sellers.edit', $seller)); ?>#documents" class="btn-p ghost sm">Yuklash / boshqarish</a>
            </div>
        </div>
        <div class="a122-section-body">
        <?php if($documents->isEmpty()): ?>
            <p class="text-sm text-gray-400 text-center py-4">Hujjatlar yuklanmagan.</p>
        <?php else: ?>
            <ul class="divide-y divide-gray-100 dark:divide-white/10">
                <?php $__currentLoopData = $documents; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $doc): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <li class="flex items-center gap-3 py-2.5">
                        <i data-lucide="<?php echo e($doc->type_icon); ?>" class="w-4 h-4 text-gray-400 flex-shrink-0"></i>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm truncate"><?php echo e($doc->type_label); ?><?php if($doc->original_name): ?> — <span class="text-gray-400"><?php echo e($doc->original_name); ?></span><?php endif; ?></p>
                            <p class="text-[10px] text-gray-400"><?php echo e($safeFormatDate($doc->getRawOriginal('created_at'), 'Y-m-d H:i') ?? '—'); ?></p>
                        </div>
                        <?php if($doc->file_url): ?>
                            <a href="<?php echo e($doc->file_url); ?>" target="_blank" class="text-blue-500 hover:underline text-xs flex items-center gap-1 flex-shrink-0">
                                <i data-lucide="external-link" class="w-3 h-3"></i> Ochish
                            </a>
                        <?php else: ?>
                            <span class="text-gray-400 text-xs flex items-center gap-1 flex-shrink-0">
                                <i data-lucide="file-x" class="w-3 h-3"></i> Fayl yo'q
                            </span>
                        <?php endif; ?>
                    </li>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </ul>
            <?php if($documents->hasPages()): ?>
                <div class="mt-4">
                    <?php echo e($documents->links()); ?>

                </div>
            <?php endif; ?>
        <?php endif; ?>
        </div>
    </div>

    
    <div class="a122-section">
        <div class="a122-section-head">
            <div>
        <div class="a122-section-head__title flex items-center gap-2">
            <i data-lucide="history" class="w-5 h-5 text-amber-500"></i>
            Shartnoma tarixi
        </div>
        <div class="a122-section-head__meta">Uzaytirish, to‘xtatish va boshqa harakatlar auditi.</div>
            </div>
        </div>
        <div class="a122-section-body">
        <?php if($contractHistory->isEmpty()): ?>
            <p class="text-sm text-gray-400 text-center py-4">Tarix yo'q.</p>
        <?php else: ?>
            <ol class="space-y-2">
                <?php $__currentLoopData = $contractHistory; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $h): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
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
                                <?php echo e($safeFormatDate($h->getRawOriginal('created_at'), 'Y-m-d H:i') ?? '—'); ?>

                                <?php if($h->performer): ?>
                                    · <?php echo e(trim($h->performer->name . ' ' . $h->performer->lastname)); ?>

                                <?php endif; ?>
                            </p>
                        </div>
                    </li>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </ol>
            <?php if($contractHistory->hasPages()): ?>
                <div class="mt-4">
                    <?php echo e($contractHistory->links()); ?>

                </div>
            <?php endif; ?>
        <?php endif; ?>
        </div>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    
    <div class="a122-section">
        <div class="a122-section-head">
            <div>
                <div class="a122-section-head__title">So'nggi buyurtmalar</div>
                <div class="a122-section-head__meta">Seller bo‘yicha eng oxirgi savdo yozuvlari.</div>
            </div>
        </div>
        <div class="a122-section-body">
        <div class="table-wrap">
            <div class="overflow-x-auto">
                <table class="tbl">
                    <thead>
                        <tr>
                            <th>Seller-order</th>
                            <th>Mijoz</th>
                            <th>Yo'nalish</th>
                            <th>Summa</th>
                            <th>Holat</th>
                            <th>Sana</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $__empty_1 = true; $__currentLoopData = $recentOrders; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $order): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                            <?php
                                $deliveryType = match ((string) ($order->delivery_type ?? data_get($order, 'order.deliveryType'))) {
                                    'pickup' => "Do'kondan olib ketish",
                                    'courier', '1' => 'Kuryer',
                                    '2' => "Do'kondan olib ketish",
                                    default => 'Standart',
                                };
                                $orderStatus = match ((string) ($order->status ?? data_get($order, 'order.status'))) {
                                    'accepted', 'B' => 'Jarayonda',
                                    'delivered', 'C' => 'Yetib bordi',
                                    'customer_received', 'D' => 'Mijoz qabul qildi',
                                    'cancelled', 'F' => 'Bekor qilingan',
                                    'A', 'P', 'pending' => 'Kutilmoqda',
                                    default => (string) ($order->status ?? data_get($order, 'order.status') ?? '—'),
                                };
                            ?>
                            <tr>
                                <td class="text-sm">
                                    <a href="<?php echo e(route('admin.seller-orders.show', $order)); ?>" class="font-semibold text-[var(--p-accent)] hover:underline">#<?php echo e($order->id); ?></a>
                                    <div class="text-xs text-[var(--p-muted)] mt-1"><?php echo e(data_get($order, 'seller.shop_name') ?: $seller->shop_name); ?></div>
                                </td>
                                <td>
                                    <?php if($order->user): ?>
                                        <a href="<?php echo e(route('admin.users.show', $order->user)); ?>" class="font-semibold hover:underline">
                                            <?php echo e(trim(($order->user->name ?? '').' '.($order->user->lastname ?? '')) ?: 'Foydalanuvchi'); ?>

                                        </a>
                                        <div class="text-xs text-[var(--p-muted)] mt-1"><?php echo e($order->user->phone_number ?: 'Telefon yo‘q'); ?></div>
                                    <?php else: ?>
                                        <span class="text-gray-400">—</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-sm text-[var(--p-muted)]"><?php echo e($deliveryType); ?></td>
                                <td class="font-semibold"><?php echo e(number_format((float)($order->amount ?? $order->total ?? 0), 0, '.', ' ')); ?> UZS</td>
                                <td><span class="badge badge-muted"><?php echo e($orderStatus); ?></span></td>
                                <td class="text-sm text-gray-500">
                                <?php echo e($safeFormatDate($order->getRawOriginal('created_at'), 'd.m.Y H:i') ?? '—'); ?>

                                </td>
                            </tr>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                            <tr>
                                <td colspan="6" class="text-center text-gray-400 py-6">Buyurtmalar yo'q</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php if($recentOrders->hasPages()): ?>
            <div class="mt-4">
                <?php echo e($recentOrders->links()); ?>

            </div>
        <?php endif; ?>
        </div>
    </div>

    
    <div class="a122-section">
        <div class="a122-section-head">
            <div>
                <div class="a122-section-head__title">So'nggi tranzaksiyalar</div>
                <div class="a122-section-head__meta">To‘lov va balansga ta’sir qilgan oxirgi moliyaviy yozuvlar.</div>
            </div>
        </div>
        <div class="a122-section-body">
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
                                <td class="text-gray-500 text-sm">
                                    <a href="<?php echo e(route('admin.transactions.show', $tx)); ?>" class="font-semibold text-[var(--p-accent)] hover:underline">#<?php echo e($tx->id); ?></a>
                                </td>
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
                                    <?php echo e($safeFormatDate($tx->getRawOriginal('created_at'), 'd.m.Y') ?? '—'); ?>

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
        <?php if($transactions->hasPages()): ?>
            <div class="mt-4">
                <?php echo e($transactions->links()); ?>

            </div>
        <?php endif; ?>
        </div>
    </div>
</div>

<div class="a122-section mt-6">
    <div class="a122-section-head">
        <div>
            <div class="a122-section-head__title">Seller loglari</div>
            <div class="a122-section-head__meta">Ichki xodimlar tomonidan yozilgan barcha operatsion izohlar.</div>
        </div>
    </div>
    <div class="a122-section-body">
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
                                <?php echo e($safeFormatDate($log->getRawOriginal('created_at'), 'd.m.Y H:i') ?? '—'); ?>

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
    <?php if($staffLogs->hasPages()): ?>
        <div class="mt-4">
            <?php echo e($staffLogs->links()); ?>

        </div>
    <?php endif; ?>
    </div>
</div>

<div class="a122-section mt-6">
    <div class="a122-section-head">
        <div>
            <div class="a122-section-head__title">Ogohlantirish va unblock tarixi</div>
            <div class="a122-section-head__meta">Sellerga yuborilgan warninglar va blokdan chiqarish harakatlari.</div>
        </div>
    </div>
    <div class="a122-section-body">
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
                                <?php echo e($safeFormatDate($banLog->getRawOriginal('created_at'), 'd.m.Y H:i') ?? '—'); ?>

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
    <?php if($banLogs->hasPages()): ?>
        <div class="mt-4">
            <?php echo e($banLogs->links()); ?>

        </div>
    <?php endif; ?>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('a122.layouts.admin', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/abbos/PROJECTS/MY/kitobchi-server/kitobchi-laravel/resources/views/a122/sellers/show.blade.php ENDPATH**/ ?>