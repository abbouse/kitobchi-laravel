<?php $__env->startSection('title', trim(($courier->first_name ?? '') . ' ' . ($courier->last_name ?? ''))); ?>
<?php $__env->startSection('page-title', 'Kuryer profili'); ?>

<?php $__env->startSection('content'); ?>
<div class="mb-4 flex items-center justify-between flex-wrap gap-2">
    <a href="<?php echo e(route('admin.couriers.index')); ?>" class="btn btn-secondary flex items-center gap-2">
        <i data-lucide="arrow-left" class="w-4 h-4"></i> Orqaga
    </a>
    <div class="flex items-center gap-2 flex-wrap">
        <form method="POST" action="<?php echo e(route('admin.couriers.reset-password', $courier)); ?>" onsubmit="return confirm('Yangi parol kuryerning telefon raqamiga SMS orqali yuborilsinmi?')">
            <?php echo csrf_field(); ?>
            <button type="submit" class="btn btn-warning flex items-center gap-2">
                <i data-lucide="key-round" class="w-4 h-4"></i> Parolni SMS bilan yangilash
            </button>
        </form>
        <a href="<?php echo e(route('admin.couriers.edit', $courier)); ?>" class="btn btn-primary flex items-center gap-2">
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
            <?php if($courier->photo): ?>
                <img
                    src="<?php echo e(Str::startsWith($courier->photo, 'http') ? $courier->photo : asset('storage/' . $courier->photo)); ?>"
                    alt="<?php echo e(trim(($courier->first_name ?? '') . ' ' . ($courier->last_name ?? ''))); ?>"
                    class="w-20 h-20 rounded-full object-cover ring-4 ring-blue-100 dark:ring-blue-500/20"
                >
            <?php else: ?>
                <div class="w-20 h-20 rounded-full bg-gray-200 dark:bg-white/10 flex items-center justify-center text-2xl font-bold text-gray-500 dark:text-gray-300">
                    <?php echo e(strtoupper(substr($courier->first_name ?? 'K', 0, 1))); ?>

                </div>
            <?php endif; ?>
        </div>

        
        <div class="flex-1">
            <div class="flex items-center gap-3 flex-wrap">
                <h2 class="text-xl font-bold">
                    <?php echo e(trim(($courier->first_name ?? '') . ' ' . ($courier->last_name ?? '')) ?: '—'); ?>

                </h2>
                <?php if($courier->status === 'approved'): ?>
                    <span class="badge badge-success">Tasdiqlangan</span>
                <?php elseif($courier->status === 'pending'): ?>
                    <span class="badge badge-warning">Kutilmoqda</span>
                <?php elseif($courier->status === 'rejected'): ?>
                    <span class="badge badge-danger">Rad etilgan</span>
                <?php elseif($courier->status === 'blocked'): ?>
                    <span class="badge badge-danger flex items-center gap-1">
                        <i data-lucide="ban" class="w-3.5 h-3.5"></i> Bloklangan
                    </span>
                <?php else: ?>
                    <span class="badge badge-muted"><?php echo e($courier->status ?? '—'); ?></span>
                <?php endif; ?>

                
                <?php if(($warningCount ?? 0) > 0): ?>
                    <?php
                        $wnClass = $warningCount >= 3
                            ? 'bg-red-100 text-red-700 dark:bg-red-500/10 dark:text-red-400'
                            : ($warningCount >= 2
                                ? 'bg-amber-100 text-amber-700 dark:bg-amber-500/10 dark:text-amber-400'
                                : 'bg-yellow-100 text-yellow-700 dark:bg-yellow-500/10 dark:text-yellow-400');
                    ?>
                    <span class="badge <?php echo e($wnClass); ?> flex items-center gap-1">
                        <i data-lucide="alert-triangle" class="w-3.5 h-3.5"></i>
                        Ogohlantirish <?php echo e($warningCount); ?>/3
                    </span>
                <?php endif; ?>
                
                <span class="badge bg-blue-100 text-blue-700 dark:bg-blue-500/10 dark:text-blue-400 flex items-center gap-1">
                    <i data-lucide="<?php echo e($courier->transport_icon); ?>" class="w-3.5 h-3.5"></i> <?php echo e($courier->transport_label); ?>

                </span>
                
                <?php
                    $vColor = $courier->verification_color;
                    $vClass = match($vColor) {
                        'emerald' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-400',
                        'amber'   => 'bg-amber-100 text-amber-700 dark:bg-amber-500/10 dark:text-amber-400',
                        'red'     => 'bg-red-100 text-red-700 dark:bg-red-500/10 dark:text-red-400',
                        default   => 'bg-gray-200 text-gray-600 dark:bg-white/10 dark:text-gray-400',
                    };
                    $vIcon = match($courier->verification_status) {
                        'verified'   => 'shield-check',
                        'pending'    => 'shield-question',
                        'rejected'   => 'shield-x',
                        default      => 'shield',
                    };
                ?>
                <span class="badge <?php echo e($vClass); ?> flex items-center gap-1">
                    <i data-lucide="<?php echo e($vIcon); ?>" class="w-3.5 h-3.5"></i> <?php echo e($courier->verification_label); ?>

                </span>
            </div>
            <div class="flex items-center gap-4 mt-2 text-sm text-gray-600 dark:text-gray-400 flex-wrap">
                <?php if($courier->phone_number ?? $courier->phone): ?>
                    <span class="flex items-center gap-1">
                        <i data-lucide="phone" class="w-4 h-4"></i>
                        <?php echo e($courier->phone_number ?? $courier->phone); ?>

                    </span>
                <?php endif; ?>
                <?php if($courier->region): ?>
                    <span class="flex items-center gap-1">
                        <i data-lucide="map-pin" class="w-4 h-4"></i>
                        <?php echo e($courier->region); ?>

                    </span>
                <?php endif; ?>
                <span class="flex items-center gap-1">
                    <i data-lucide="calendar" class="w-4 h-4"></i>
                    <?php echo e($courier->created_at ? $courier->created_at->format('d.m.Y') : '—'); ?>

                </span>
            </div>
        </div>

        
        <div class="flex items-center gap-2 shrink-0 flex-wrap">
            <?php if($courier->status !== 'approved' && $courier->status !== 'blocked'): ?>
                <form method="POST" action="<?php echo e(route('admin.couriers.approve', $courier)); ?>">
                    <?php echo csrf_field(); ?>
                    <?php echo method_field('PATCH'); ?>
                    <button type="submit" class="btn btn-primary flex items-center gap-2">
                        <i data-lucide="check-circle" class="w-4 h-4"></i> Tasdiqlash
                    </button>
                </form>
            <?php endif; ?>

            <?php if($courier->status === 'blocked'): ?>
                <button type="button"
                        onclick="document.getElementById('unblock-modal').classList.remove('hidden')"
                        class="btn btn-primary flex items-center gap-2">
                    <i data-lucide="unlock" class="w-4 h-4"></i> Blokdan chiqarish
                </button>
            <?php elseif($courier->status === 'approved'): ?>
                <button type="button"
                        onclick="document.getElementById('warn-modal').classList.remove('hidden')"
                        class="btn btn-warning flex items-center gap-2">
                    <i data-lucide="alert-triangle" class="w-4 h-4"></i> Ogohlantirish
                </button>
            <?php endif; ?>

            <?php if($courier->status !== 'rejected' && $courier->status !== 'blocked'): ?>
                <form method="POST" action="<?php echo e(route('admin.couriers.reject', $courier)); ?>"
                      onsubmit="return confirm('Kuryerni rad etishga ishonchingiz komilmi?')">
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


<div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
    <div class="card p-5 flex items-center gap-4">
        <div class="w-12 h-12 rounded-xl bg-blue-100 dark:bg-blue-500/10 flex items-center justify-center">
            <i data-lucide="package" class="w-6 h-6 text-blue-500"></i>
        </div>
        <div>
            <p class="text-xs text-gray-500">Jami buyurtmalar</p>
            <p class="text-2xl font-bold"><?php echo e(number_format($orderCount, 0, '.', ' ')); ?></p>
        </div>
    </div>

    <div class="card p-5 flex items-center gap-4">
        <div class="w-12 h-12 rounded-xl bg-green-100 dark:bg-green-500/10 flex items-center justify-center">
            <i data-lucide="banknote" class="w-6 h-6 text-green-500"></i>
        </div>
        <div>
            <p class="text-xs text-gray-500">Jami daromad</p>
            <p class="text-2xl font-bold"><?php echo e(number_format($totalEarned, 0, '.', ' ')); ?></p>
            <p class="text-xs text-gray-400">UZS</p>
        </div>
    </div>

    <div class="card p-5 flex items-center gap-4">
        <div class="w-12 h-12 rounded-xl bg-yellow-100 dark:bg-yellow-500/10 flex items-center justify-center">
            <i data-lucide="wallet" class="w-6 h-6 text-yellow-500"></i>
        </div>
        <div>
            <p class="text-xs text-gray-500">Balans</p>
            <p class="text-2xl font-bold"><?php echo e(number_format($courier->balance ?? 0, 0, '.', ' ')); ?></p>
            <p class="text-xs text-gray-400">UZS</p>
        </div>
    </div>
</div>


<div class="grid grid-cols-1 lg:grid-cols-2 gap-4 mb-6">
    
    <div class="card p-5">
        <div class="flex items-center justify-between mb-4">
            <h3 class="font-bold text-base flex items-center gap-2">
                <i data-lucide="<?php echo e($courier->transport_icon); ?>" class="w-5 h-5 text-blue-500"></i>
                Transport va karta
            </h3>
            <a href="<?php echo e(route('admin.couriers.edit', $courier)); ?>#transport" class="text-xs text-blue-500 hover:underline">Tahrirlash</a>
        </div>
        <dl class="grid grid-cols-3 gap-y-2 gap-x-3 text-sm">
            <dt class="col-span-1 text-gray-500">Transport</dt>
            <dd class="col-span-2"><?php echo e($courier->transport_label); ?></dd>

            <?php if(in_array($courier->transport_type, ['motorcycle', 'car']) && ($courier->vehicle_brand || $courier->vehicle_plate_number)): ?>
                <dt class="col-span-1 text-gray-500">Vosita</dt>
                <dd class="col-span-2">
                    <?php echo e(trim(($courier->vehicle_brand ?? '') . ' ' . ($courier->vehicle_model ?? '')) ?: '—'); ?>

                    <?php if($courier->vehicle_color): ?> · <?php echo e($courier->vehicle_color); ?> <?php endif; ?>
                </dd>
                <?php if($courier->vehicle_plate_number): ?>
                    <dt class="col-span-1 text-gray-500">Davlat raqami</dt>
                    <dd class="col-span-2 font-mono"><?php echo e($courier->vehicle_plate_number); ?></dd>
                <?php endif; ?>
                <?php if($courier->driver_license_expires_at): ?>
                    <dt class="col-span-1 text-gray-500">Guvohnoma tugaydi</dt>
                    <dd class="col-span-2">
                        <?php echo e($courier->driver_license_expires_at->format('Y-m-d')); ?>

                        <?php $ld = (int) now()->startOfDay()->diffInDays($courier->driver_license_expires_at, false); ?>
                        <span class="text-xs <?php echo e($ld < 0 ? 'text-red-500' : ($ld <= 60 ? 'text-amber-500' : 'text-gray-400')); ?>">
                            (<?php echo e($ld < 0 ? abs($ld).' kun oldin tugagan' : $ld.' kun qoldi'); ?>)
                        </span>
                    </dd>
                <?php endif; ?>
            <?php endif; ?>

            <?php if($courier->payment_card): ?>
                <dt class="col-span-1 text-gray-500 mt-2">Karta</dt>
                <dd class="col-span-2 font-mono mt-2"><?php echo e($courier->masked_card); ?></dd>
            <?php endif; ?>
            <?php if($courier->card_holder): ?>
                <dt class="col-span-1 text-gray-500">Karta egasi</dt>
                <dd class="col-span-2"><?php echo e($courier->card_holder); ?></dd>
            <?php endif; ?>
            <?php if($courier->inn): ?>
                <dt class="col-span-1 text-gray-500">STIR</dt>
                <dd class="col-span-2 font-mono"><?php echo e($courier->inn); ?></dd>
            <?php endif; ?>
            <?php if($courier->home_address): ?>
                <dt class="col-span-1 text-gray-500">Manzil</dt>
                <dd class="col-span-2"><?php echo e($courier->home_address); ?></dd>
            <?php endif; ?>
            <?php if($courier->birthdate): ?>
                <dt class="col-span-1 text-gray-500">Tug'ilgan</dt>
                <dd class="col-span-2"><?php echo e($courier->birthdate->format('Y-m-d')); ?></dd>
            <?php endif; ?>
        </dl>
    </div>

    
    <div class="card p-5">
        <div class="flex items-center justify-between mb-4">
            <h3 class="font-bold text-base flex items-center gap-2">
                <i data-lucide="folder" class="w-5 h-5 text-blue-500"></i>
                Hujjatlar
                <?php if($courier->documents->count() > 0): ?>
                    <span class="text-xs text-gray-400">(<?php echo e($courier->documents->count()); ?>)</span>
                <?php endif; ?>
            </h3>
            <a href="<?php echo e(route('admin.couriers.edit', $courier)); ?>#documents" class="text-xs text-blue-500 hover:underline">Boshqarish</a>
        </div>
        <?php if($courier->documents->isEmpty()): ?>
            <p class="text-sm text-gray-400 text-center py-6">Hujjatlar yuklanmagan.</p>
        <?php else: ?>
            <div class="space-y-2">
                <?php $__currentLoopData = $courier->documents->take(6); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $doc): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <a href="<?php echo e($doc->file_url); ?>" target="_blank"
                       class="flex items-center gap-3 p-2 rounded-lg border border-gray-200 dark:border-white/10 hover:bg-gray-50 dark:hover:bg-white/5">
                        <i data-lucide="<?php echo e($doc->type_icon); ?>" class="w-4 h-4 text-blue-500 flex-shrink-0"></i>
                        <div class="flex-1 min-w-0">
                            <div class="text-sm font-medium text-gray-700 dark:text-gray-200 truncate"><?php echo e($doc->type_label); ?></div>
                            <?php if($doc->original_name): ?>
                                <div class="text-[10px] text-gray-400 truncate"><?php echo e($doc->original_name); ?></div>
                            <?php endif; ?>
                        </div>
                        <i data-lucide="external-link" class="w-3.5 h-3.5 text-gray-400 flex-shrink-0"></i>
                    </a>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </div>
        <?php endif; ?>

        <?php if($courier->verification_notes): ?>
            <div class="mt-4 p-3 rounded-lg bg-amber-50/60 dark:bg-amber-400/5 border border-amber-200 dark:border-amber-400/20 text-xs">
                <p class="font-semibold text-amber-700 dark:text-amber-300 mb-1">Verifikatsiya izohlari</p>
                <p class="text-gray-600 dark:text-gray-400"><?php echo e($courier->verification_notes); ?></p>
            </div>
        <?php endif; ?>
    </div>
</div>


<div class="card p-5 mb-6">
    <div class="flex items-center justify-between mb-4 flex-wrap gap-2">
        <h3 class="font-bold text-base flex items-center gap-2">
            <i data-lucide="alert-triangle" class="w-5 h-5 text-amber-500"></i>
            Ogohlantirish va blok tarixi
        </h3>
        <div class="flex items-center gap-2 flex-wrap">
            <?php
                $progressClass = match(true) {
                    ($warningCount ?? 0) >= 3 => 'text-red-600 dark:text-red-400',
                    ($warningCount ?? 0) >= 2 => 'text-amber-600 dark:text-amber-400',
                    ($warningCount ?? 0) >= 1 => 'text-yellow-600 dark:text-yellow-400',
                    default                  => 'text-gray-500',
                };
            ?>
            <span class="text-xs <?php echo e($progressClass); ?>">
                Faol ogohlantirishlar: <strong><?php echo e($warningCount ?? 0); ?>/3</strong>
            </span>
            <?php if($courier->status === 'approved'): ?>
                <button type="button"
                        onclick="document.getElementById('warn-modal').classList.remove('hidden')"
                        class="btn btn-warning btn-sm flex items-center gap-1 text-xs">
                    <i data-lucide="alert-triangle" class="w-3.5 h-3.5"></i> Ogohlantirish
                </button>
            <?php endif; ?>
            <?php if($courier->status === 'blocked'): ?>
                <button type="button"
                        onclick="document.getElementById('unblock-modal').classList.remove('hidden')"
                        class="btn btn-primary btn-sm flex items-center gap-1 text-xs">
                    <i data-lucide="unlock" class="w-3.5 h-3.5"></i> Blokdan chiqarish
                </button>
            <?php endif; ?>
        </div>
    </div>

    
    <div class="mb-4">
        <div class="h-2 rounded-full bg-gray-200 dark:bg-white/10 overflow-hidden">
            <?php
                $pct = min(100, (($warningCount ?? 0) / 3) * 100);
                $barClass = match(true) {
                    ($warningCount ?? 0) >= 3 => 'bg-red-500',
                    ($warningCount ?? 0) >= 2 => 'bg-amber-500',
                    ($warningCount ?? 0) >= 1 => 'bg-yellow-500',
                    default                  => 'bg-gray-300 dark:bg-white/20',
                };
            ?>
            <div class="h-full <?php echo e($barClass); ?> transition-all" style="width: <?php echo e($pct); ?>%"></div>
        </div>
        <p class="text-[10px] text-gray-400 mt-1">3 ta ogohlantirishdan keyin kuryer avtomatik bloklanadi.</p>
    </div>

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
                                    <?php echo e($banLog->type === 'warning' ? 'Ogohlantirish' : 'Blokdan chiqarish'); ?>

                                </span>
                            </td>
                            <td><?php echo e($banLog->title ?? '—'); ?></td>
                            <td class="text-sm text-gray-600 dark:text-gray-300"><?php echo e($banLog->message ?? '—'); ?></td>
                        </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                        <tr>
                            <td colspan="4" class="text-center text-gray-400 py-6">
                                Ogohlantirishlar tarixi topilmadi.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>


<div id="warn-modal" class="fixed inset-0 z-50 hidden bg-black/50 flex items-center justify-center p-4">
    <div class="bg-white dark:bg-zinc-900 rounded-xl shadow-xl w-full max-w-md p-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="font-bold text-lg flex items-center gap-2">
                <i data-lucide="alert-triangle" class="w-5 h-5 text-amber-500"></i>
                Kuryerga ogohlantirish
            </h3>
            <button type="button"
                    onclick="document.getElementById('warn-modal').classList.add('hidden')"
                    class="text-gray-400 hover:text-gray-600">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>
        <form method="POST" action="<?php echo e(route('admin.couriers.warn', $courier)); ?>" class="space-y-3">
            <?php echo csrf_field(); ?>
            <div>
                <label class="block text-xs font-semibold text-gray-500 mb-1">Sarlavha *</label>
                <input type="text" name="title" maxlength="120" required
                       placeholder="Masalan: Buyurtma kechiktirish"
                       class="form-input w-full">
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-500 mb-1">Xabar *</label>
                <textarea name="message" rows="4" maxlength="2000" required
                          placeholder="Kuryerga yuboriladigan ogohlantirish matni..."
                          class="form-input w-full"></textarea>
            </div>
            <div class="rounded-lg bg-amber-50 dark:bg-amber-400/5 border border-amber-200 dark:border-amber-400/20 p-3 text-xs text-amber-700 dark:text-amber-300">
                <i data-lucide="info" class="w-3.5 h-3.5 inline -mt-0.5"></i>
                Eslatma: 3-marta ogohlantirilganda kuryer avtomatik bloklanadi.
                Hozirgi soni: <strong><?php echo e($warningCount ?? 0); ?>/3</strong>
            </div>
            <div class="flex justify-end gap-2 pt-2">
                <button type="button"
                        onclick="document.getElementById('warn-modal').classList.add('hidden')"
                        class="btn btn-secondary">Bekor qilish</button>
                <button type="submit" class="btn btn-warning">Yuborish</button>
            </div>
        </form>
    </div>
</div>


<div id="unblock-modal" class="fixed inset-0 z-50 hidden bg-black/50 flex items-center justify-center p-4">
    <div class="bg-white dark:bg-zinc-900 rounded-xl shadow-xl w-full max-w-md p-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="font-bold text-lg flex items-center gap-2">
                <i data-lucide="unlock" class="w-5 h-5 text-emerald-500"></i>
                Kuryerni blokdan chiqarish
            </h3>
            <button type="button"
                    onclick="document.getElementById('unblock-modal').classList.add('hidden')"
                    class="text-gray-400 hover:text-gray-600">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>
        <form method="POST" action="<?php echo e(route('admin.couriers.unblock', $courier)); ?>" class="space-y-3">
            <?php echo csrf_field(); ?>
            <?php echo method_field('PATCH'); ?>
            <div>
                <label class="block text-xs font-semibold text-gray-500 mb-1">Izoh (ixtiyoriy)</label>
                <textarea name="message" rows="3" maxlength="2000"
                          placeholder="Blokdan chiqarish sababi..."
                          class="form-input w-full"></textarea>
            </div>
            <div class="rounded-lg bg-emerald-50 dark:bg-emerald-400/5 border border-emerald-200 dark:border-emerald-400/20 p-3 text-xs text-emerald-700 dark:text-emerald-300">
                <i data-lucide="info" class="w-3.5 h-3.5 inline -mt-0.5"></i>
                Blokdan chiqarilgandan keyin ogohlantirishlar hisobi qayta boshlanadi.
            </div>
            <div class="flex justify-end gap-2 pt-2">
                <button type="button"
                        onclick="document.getElementById('unblock-modal').classList.add('hidden')"
                        class="btn btn-secondary">Bekor qilish</button>
                <button type="submit" class="btn btn-primary">Blokdan chiqarish</button>
            </div>
        </form>
    </div>
</div>


<div class="card p-5">
    <h3 class="font-bold text-base mb-4">So'nggi buyurtmalar</h3>
    <div class="table-wrap">
        <div class="overflow-x-auto">
            <table class="tbl">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Foydalanuvchi</th>
                        <th>Sana</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $__empty_1 = true; $__currentLoopData = $recentOrders; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $order): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        <tr>
                            <td class="text-gray-500 text-sm">#<?php echo e($order->id); ?></td>
                            <td>
                                <?php if($order->user): ?>
                                    <?php echo e(trim(($order->user->first_name ?? $order->user->name ?? '') . ' ' . ($order->user->last_name ?? '')) ?: '—'); ?>

                                <?php else: ?>
                                    <span class="text-gray-400">—</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-sm text-gray-500">
                                <?php echo e($order->created_at ? $order->created_at->format('d.m.Y H:i') : '—'); ?>

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
<?php $__env->stopSection(); ?>

<?php echo $__env->make('a122.layouts.admin', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/abbos/PROJECTS/MY/kitobchi-server/kitobchi-laravel/resources/views/a122/couriers/show.blade.php ENDPATH**/ ?>