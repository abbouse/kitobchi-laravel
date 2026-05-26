<?php $__env->startSection('title', 'Sotuvchini tahrirlash'); ?>
<?php $__env->startSection('page-title', 'Sotuvchini tahrirlash'); ?>

<?php $__env->startSection('content'); ?>
<div class="mb-4">
    <a href="<?php echo e(route('admin.sellers.show', $seller)); ?>" class="btn btn-secondary flex items-center gap-2 w-fit">
        <i data-lucide="arrow-left" class="w-4 h-4"></i> Orqaga
    </a>
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

<?php if($errors->any()): ?>
    <div class="mb-4 rounded-lg bg-red-100 text-red-800 dark:bg-red-500/10 dark:text-red-400 px-4 py-3 text-sm">
        <ul class="list-disc list-inside space-y-1">
            <?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $error): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <li><?php echo e($error); ?></li>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </ul>
    </div>
<?php endif; ?>


<div class="tab-pills fade-up mb-4" role="tablist">
    <?php $__currentLoopData = [
        'main'       => ['bi-person-circle', 'Asosiy'],
        'legal'      => ['bi-bank',          'Rekvizitlar'],
        'contract'   => ['bi-file-earmark-text', 'Shartnoma'],
        'documents'  => ['bi-folder',       'Hujjatlar'],
        'premium'    => ['bi-star',         'Premium'],
    ]; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => [$icon, $label]): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <button type="button"
                class="tab-pill"
                data-seller-tab="<?php echo e($key); ?>"
                <?php if($loop->first): ?> data-seller-tab-active <?php endif; ?>>
            <i class="bi <?php echo e($icon); ?>"></i> <?php echo e($label); ?>

        </button>
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
</div>


<form method="POST" action="<?php echo e(route('admin.sellers.update', $seller)); ?>" enctype="multipart/form-data" class="space-y-4" id="sellerEditForm">
    <?php echo csrf_field(); ?>
    <?php echo method_field('PUT'); ?>

    
    <section data-seller-panel="main" class="card p-5 grid grid-cols-1 md:grid-cols-2 gap-4">
        <div class="md:col-span-2">
            <label class="text-xs text-gray-500 mb-1 block">Do'kon nomi <span class="text-red-500">*</span></label>
            <input name="shop_name" class="input" required placeholder="Do'kon nomini kiriting"
                   value="<?php echo e(old('shop_name', $seller->shop_name)); ?>">
        </div>

        <div>
            <label class="text-xs text-gray-500 mb-1 block">Ism</label>
            <input name="firstname" class="input" placeholder="Ism"
                   value="<?php echo e(old('firstname', $seller->firstname)); ?>">
        </div>

        <div>
            <label class="text-xs text-gray-500 mb-1 block">Familiya</label>
            <input name="lastname" class="input" placeholder="Familiya"
                   value="<?php echo e(old('lastname', $seller->lastname)); ?>">
        </div>

        <div>
            <label class="text-xs text-gray-500 mb-1 block">Telefon raqam <span class="text-red-500">*</span></label>
            <input name="phone_number" class="input" placeholder="+998901234567" required
                   value="<?php echo e(old('phone_number', $seller->phone_number)); ?>">
        </div>

        <div>
            <label class="text-xs text-gray-500 mb-1 block">Viloyat <span class="text-red-500">*</span></label>
            <input name="region" class="input" placeholder="Viloyat nomi" required
                   value="<?php echo e(old('region', $seller->region)); ?>">
        </div>

        <div>
            <label class="text-xs text-gray-500 mb-1 block">Holat</label>
            <select name="status" class="select">
                <option value="pending"  <?php if(old('status', $seller->status) === 'pending'): echo 'selected'; endif; ?>>Kutilmoqda</option>
                <option value="approved" <?php if(old('status', $seller->status) === 'approved'): echo 'selected'; endif; ?>>Tasdiqlangan</option>
                <option value="rejected" <?php if(old('status', $seller->status) === 'rejected'): echo 'selected'; endif; ?>>Rad etilgan</option>
                <option value="blocked"  <?php if(old('status', $seller->status) === 'blocked'): echo 'selected'; endif; ?>>Bloklangan</option>
            </select>
        </div>

        <div>
            <label class="text-xs text-gray-500 mb-1 block">Balans (UZS)</label>
            <input name="balance" type="number" class="input" min="0" step="1"
                   placeholder="0" value="<?php echo e(old('balance', $seller->balance ?? 0)); ?>">
        </div>

        <div>
            <label class="text-xs text-gray-500 mb-1 block">Komissiya foizi (%)</label>
            <input name="commission_percent" type="number" class="input" min="0" max="100" step="1"
                   placeholder="Default CommissionSetting"
                   value="<?php echo e(old('commission_percent', $seller->commission_percent)); ?>">
            <p class="text-[10px] text-gray-400 mt-1">Bo'sh qoldirilsa, umumiy CommissionSetting'dan olinadi.</p>
        </div>

        <div>
            <label class="text-xs text-gray-500 mb-1 block">Profil rasmi</label>
            <?php if($seller->photo): ?>
                <div class="mb-2 flex items-center gap-3">
                    <img src="<?php echo e(Str::startsWith($seller->photo, 'http') ? $seller->photo : asset('storage/' . $seller->photo)); ?>"
                         alt="Joriy rasm"
                         class="w-12 h-12 rounded-full object-cover border border-gray-200 dark:border-white/10">
                    <span class="text-xs text-gray-400">Joriy rasm. Yangi rasm yuklash uchun faylni tanlang.</span>
                </div>
            <?php endif; ?>
            <input name="photo" type="file" class="input" accept="image/*">
        </div>

        <div>
            <label class="text-xs text-gray-500 mb-1 block">Yangi parol <span class="text-gray-400">(ixtiyoriy)</span></label>
            <input name="password" type="password" class="input"
                   placeholder="Bo'sh qoldirilsa, o'zgarmaydi" autocomplete="new-password">
        </div>
    </section>

    
    <section data-seller-panel="legal" class="card p-5 grid grid-cols-1 md:grid-cols-2 gap-4 hidden">
        <div class="md:col-span-2">
            <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-200 mb-1">Huquqiy ma'lumotlar</h3>
            <p class="text-[11px] text-gray-400">Shartnoma tuzish uchun zarur rekvizitlar.</p>
        </div>

        <div>
            <label class="text-xs text-gray-500 mb-1 block">Huquqiy shakl</label>
            <select name="legal_type" class="select">
                <option value="">— tanlang —</option>
                <?php $__currentLoopData = [
                    'individual'   => 'Jismoniy shaxs',
                    'entrepreneur' => 'Yakka tartibdagi tadbirkor',
                    'llc'          => 'MChJ',
                    'jsc'          => 'AJ / OAJ',
                ]; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $v => $l): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <option value="<?php echo e($v); ?>" <?php if(old('legal_type', $seller->legal_type) === $v): echo 'selected'; endif; ?>><?php echo e($l); ?></option>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </select>
        </div>

        <div>
            <label class="text-xs text-gray-500 mb-1 block">STIR (INN)</label>
            <input name="inn" class="input" placeholder="12345678"
                   value="<?php echo e(old('inn', $seller->inn)); ?>">
        </div>

        
        <div>
            <label class="text-xs text-gray-500 mb-1 block">Pasport seriyasi</label>
            <input name="passport_series" class="input" placeholder="AB"
                   value="<?php echo e(old('passport_series', $seller->passport_series)); ?>">
        </div>
        <div>
            <label class="text-xs text-gray-500 mb-1 block">Pasport raqami</label>
            <input name="passport_number" class="input" placeholder="1234567"
                   value="<?php echo e(old('passport_number', $seller->passport_number)); ?>">
        </div>
        <div>
            <label class="text-xs text-gray-500 mb-1 block">Kim tomonidan berilgan</label>
            <input name="passport_issued_by" class="input" placeholder="Toshkent shahar IIB"
                   value="<?php echo e(old('passport_issued_by', $seller->passport_issued_by)); ?>">
        </div>
        <div>
            <label class="text-xs text-gray-500 mb-1 block">Berilgan sana</label>
            <input name="passport_issued_at" type="date" class="input"
                   value="<?php echo e(old('passport_issued_at', optional($seller->passport_issued_at)->format('Y-m-d'))); ?>">
        </div>

        
        <div class="md:col-span-2 pt-2 border-t border-gray-100 dark:border-white/10">
            <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-200 mt-2 mb-1">Bank rekvizitlari</h4>
        </div>
        <div>
            <label class="text-xs text-gray-500 mb-1 block">Bank nomi</label>
            <input name="bank_name" class="input" placeholder="Ipak Yo'li Banki"
                   value="<?php echo e(old('bank_name', $seller->bank_name)); ?>">
        </div>
        <div>
            <label class="text-xs text-gray-500 mb-1 block">Hisob raqami (20 xona)</label>
            <input name="bank_account" class="input" placeholder="20208000000000000000"
                   value="<?php echo e(old('bank_account', $seller->bank_account)); ?>">
        </div>
        <div>
            <label class="text-xs text-gray-500 mb-1 block">MFO</label>
            <input name="bank_mfo" class="input" placeholder="00420"
                   value="<?php echo e(old('bank_mfo', $seller->bank_mfo)); ?>">
        </div>
        <div>
            <label class="text-xs text-gray-500 mb-1 block">SWIFT <span class="text-gray-400">(ixtiyoriy)</span></label>
            <input name="bank_swift" class="input" placeholder="UZSBUZ22"
                   value="<?php echo e(old('bank_swift', $seller->bank_swift)); ?>">
        </div>

        
        <div>
            <label class="text-xs text-gray-500 mb-1 block">Karta raqami</label>
            <input name="payment_card" class="input" placeholder="8600 0304 1234 5678"
                   value="<?php echo e(old('payment_card', $seller->payment_card)); ?>">
        </div>
        <div>
            <label class="text-xs text-gray-500 mb-1 block">Karta egasi</label>
            <input name="card_holder" class="input" placeholder="ABBOS TOORDALIEV"
                   value="<?php echo e(old('card_holder', $seller->card_holder)); ?>">
        </div>

        <div class="md:col-span-2">
            <label class="text-xs text-gray-500 mb-1 block">Huquqiy manzil</label>
            <input name="legal_address" class="input" placeholder="Toshkent sh., Yunusobod tumani, ..."
                   value="<?php echo e(old('legal_address', $seller->legal_address)); ?>">
        </div>
    </section>

    
    <section data-seller-panel="contract" class="card p-5 grid grid-cols-1 md:grid-cols-2 gap-4 hidden">
        <div class="md:col-span-2">
            <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-200 mb-1">Shartnoma ma'lumotlari</h3>
            <p class="text-[11px] text-gray-400">Shartnoma raqami, sanasi va holati. "Tez uzaytirish" tugmasi Hujjatlar tabida.</p>
        </div>

        
        <div class="md:col-span-2">
            <div class="p-3 rounded-lg border <?php echo e($seller->contract_signed ? 'border-emerald-300 bg-emerald-50/60 dark:bg-emerald-400/5 dark:border-emerald-400/20' : 'border-gray-200 bg-gray-50/60 dark:bg-white/5 dark:border-white/10'); ?> flex items-start gap-3">
                <input type="checkbox" name="contract_signed" id="contract_signed" value="1"
                       class="mt-1 w-4 h-4 rounded border-emerald-300 text-emerald-500 focus:ring-emerald-400"
                       <?php echo e(old('contract_signed', $seller->contract_signed) ? 'checked' : ''); ?>>
                <div class="flex-1">
                    <label for="contract_signed" class="block text-sm font-semibold text-gray-700 dark:text-gray-200 cursor-pointer">
                        Shartnoma imzolangan
                    </label>
                    <p class="text-[11px] text-gray-500 dark:text-gray-400 mt-0.5">
                        Ushbu seller bilan shartnoma imzolanganini belgilang. Sana bilmasangiz ham belgilash mumkin —
                        sana keyinroq qo'shilishi mumkin. Belgisiz seller "shartnomasi imzolanmagan" deb hisoblanadi.
                    </p>
                </div>
            </div>
        </div>

        <div>
            <label class="text-xs text-gray-500 mb-1 block">Shartnoma raqami</label>
            <input name="contract_number" class="input" placeholder="№ 2026-042"
                   value="<?php echo e(old('contract_number', $seller->contract_number)); ?>">
        </div>

        <div>
            <label class="text-xs text-gray-500 mb-1 block">Holat</label>
            <select name="contract_status" class="select">
                <?php $__currentLoopData = [
                    'none'       => 'Yo\'q',
                    'active'     => 'Faol',
                    'expiring'   => 'Tugashga yaqin',
                    'expired'    => 'Tugagan',
                    'terminated' => 'To\'xtatilgan',
                ]; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $v => $l): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <option value="<?php echo e($v); ?>" <?php if(old('contract_status', $seller->contract_status) === $v): echo 'selected'; endif; ?>><?php echo e($l); ?></option>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </select>
        </div>

        <div>
            <label class="text-xs text-gray-500 mb-1 block">Imzolangan sana</label>
            <input name="contract_signed_at" type="date" class="input"
                   value="<?php echo e(old('contract_signed_at', optional($seller->contract_signed_at)->format('Y-m-d'))); ?>">
        </div>

        <div>
            <label class="text-xs text-gray-500 mb-1 block">Tugash sanasi</label>
            <input name="contract_expires_at" type="date" class="input"
                   value="<?php echo e(old('contract_expires_at', optional($seller->contract_expires_at)->format('Y-m-d'))); ?>">
            <?php if($seller->contract_expires_at): ?>
                <?php $days = (int) now()->startOfDay()->diffInDays($seller->contract_expires_at, false); ?>
                <p class="text-[10px] mt-1 <?php echo e($days < 0 ? 'text-red-500' : ($days <= 30 ? 'text-amber-500' : 'text-gray-400')); ?>">
                    <?php if($days < 0): ?>
                        <?php echo e(abs($days)); ?> kun oldin tugagan.
                    <?php else: ?>
                        <?php echo e($days); ?> kun qoldi.
                    <?php endif; ?>
                </p>
            <?php endif; ?>
        </div>

        <div class="md:col-span-2">
            <label class="text-xs text-gray-500 mb-1 block">Izohlar</label>
            <textarea name="contract_notes" class="input" rows="2" placeholder="Shartnoma bo'yicha qo'shimcha izohlar"><?php echo e(old('contract_notes', $seller->contract_notes)); ?></textarea>
        </div>
    </section>

    
    <section data-seller-panel="premium" class="card p-5 hidden">
        <div class="p-4 rounded-lg border border-amber-200 bg-amber-50/60 dark:bg-amber-400/5 dark:border-amber-400/20 space-y-4">
            <div>
                <div class="text-sm font-semibold text-amber-700 dark:text-amber-300">Premium obuna</div>
                <p class="text-[11px] text-gray-500 dark:text-gray-400 mt-0.5">
                    Admin endi premiumni ilovadagi planlar bilan bir xil beradi. Berilgan plan seller uchun haqiqiy subscription yaratadi yoki amaldagini uzaytiradi.
                </p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="rounded-xl border border-amber-200/70 dark:border-amber-400/20 bg-white/80 dark:bg-white/5 p-4">
                    <div class="text-xs uppercase tracking-wide text-gray-500 mb-2">Hozirgi holat</div>
                    <?php if(($premiumState['is_premium'] ?? false) && !empty($premiumState['premium_expires_at'])): ?>
                        <div class="text-lg font-semibold text-amber-600 dark:text-amber-300">Faol premium</div>
                        <div class="text-xs text-gray-500 mt-1">
                            Plan: <span class="font-medium"><?php echo e($premiumState['subscription_plan'] ?? 'manual'); ?></span>
                        </div>
                        <div class="text-xs text-gray-500 mt-1">
                            Tugaydi:
                            <span class="font-mono">
                                <?php echo e(\Illuminate\Support\Carbon::parse($premiumState['premium_expires_at'])->format('Y-m-d H:i')); ?>

                            </span>
                        </div>
                        <div class="text-xs text-gray-500 mt-1">
                            Qoldi: <?php echo e($premiumState['premium_days_left'] ?? 0); ?> kun
                        </div>
                    <?php else: ?>
                        <div class="text-lg font-semibold text-gray-500">Premium yo'q</div>
                        <div class="text-xs text-gray-500 mt-1">Seller hozir faol premium obunada emas.</div>
                    <?php endif; ?>
                </div>

                <div class="rounded-xl border border-amber-200/70 dark:border-amber-400/20 bg-white/80 dark:bg-white/5 p-4 space-y-3">
                    <div>
                        <label class="text-xs text-gray-500 mb-1 block">Admin premium action</label>
                        <select name="premium_action" id="premiumActionSelect" class="select"
                                onchange="document.getElementById('premiumPlanWrap').classList.toggle('hidden', this.value !== 'grant')">
                            <option value="keep" <?php if(old('premium_action', 'keep') === 'keep'): echo 'selected'; endif; ?>>O'zgartirmaslik</option>
                            <option value="grant" <?php if(old('premium_action') === 'grant'): echo 'selected'; endif; ?>>Plan bo'yicha premium berish</option>
                            <option value="revoke" <?php if(old('premium_action') === 'revoke'): echo 'selected'; endif; ?>>Premiumni o'chirish</option>
                        </select>
                    </div>

                    <div id="premiumPlanWrap" class="<?php echo e(old('premium_action') === 'grant' ? '' : 'hidden'); ?>">
                        <label class="text-xs text-gray-500 mb-1 block">Premium plan <span class="text-red-500">*</span></label>
                        <select name="premium_plan" class="select">
                            <option value="">Planni tanlang</option>
                            <?php $__currentLoopData = $premiumPlans; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $plan): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <option value="<?php echo e($plan['type']); ?>" <?php if(old('premium_plan') === $plan['type']): echo 'selected'; endif; ?>>
                                    <?php echo e($plan['label']); ?> · <?php echo e(number_format($plan['price'], 0, '.', ' ')); ?> UZS
                                </option>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </select>
                        <p class="text-[10px] text-gray-400 mt-1">
                            Bu action seller uchun active subscription yaratadi yoki amaldagi obunani uzaytiradi.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <div class="flex items-center justify-end gap-2 pt-2 border-t border-gray-100 dark:border-white/10">
        <a href="<?php echo e(route('admin.sellers.show', $seller)); ?>" class="btn btn-secondary">Bekor</a>
        <button type="submit" class="btn btn-primary flex items-center gap-2">
            <i data-lucide="save" class="w-4 h-4"></i> Saqlash
        </button>
    </div>
</form>


<section data-seller-panel="documents" class="card p-5 hidden mt-4">
    <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-200 mb-3">Hujjatlar ro'yxati</h3>

    
    <form method="POST" action="<?php echo e(route('admin.sellers.documents.store', $seller)); ?>" enctype="multipart/form-data"
          class="grid grid-cols-1 md:grid-cols-4 gap-3 p-4 rounded-lg bg-gray-50 dark:bg-white/5 mb-4">
        <?php echo csrf_field(); ?>
        <div>
            <label class="text-xs text-gray-500 mb-1 block">Turi</label>
            <select name="type" class="select" required>
                <?php $__currentLoopData = [
                    'passport'        => 'Pasport',
                    'contract'        => 'Shartnoma',
                    'inn_certificate' => 'STIR guvohnomasi',
                    'license'         => 'Litsenziya',
                    'bank_details'    => 'Bank rekvizitlari',
                    'addendum'        => 'Qo\'shimcha kelishuv',
                    'other'           => 'Boshqa',
                ]; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $v => $l): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <option value="<?php echo e($v); ?>"><?php echo e($l); ?></option>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </select>
        </div>
        <div class="md:col-span-2">
            <label class="text-xs text-gray-500 mb-1 block">Fayl (PDF/JPG/PNG, ≤10MB)</label>
            <input name="file" type="file" class="input" accept="application/pdf,image/*" required>
        </div>
        <div class="md:col-span-1 flex items-end">
            <button type="submit" class="btn btn-primary w-full flex items-center justify-center gap-2">
                <i data-lucide="upload" class="w-4 h-4"></i> Yuklash
            </button>
        </div>
        <div class="md:col-span-4">
            <label class="text-xs text-gray-500 mb-1 block">Izoh <span class="text-gray-400">(ixtiyoriy)</span></label>
            <input name="description" class="input" placeholder="Masalan: 2026 yilgi shartnoma, pasport 1-2 beti">
        </div>
    </form>

    
    <?php if($seller->documents->isEmpty()): ?>
        <p class="text-sm text-gray-400 text-center py-4">Hali hech qanday hujjat yuklanmagan.</p>
    <?php else: ?>
        <div class="divide-y divide-gray-100 dark:divide-white/10">
            <?php $__currentLoopData = $seller->documents; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $doc): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <div class="flex items-center gap-3 py-3">
                    <div class="w-10 h-10 rounded-lg bg-gray-100 dark:bg-white/10 flex items-center justify-center flex-shrink-0">
                        <i data-lucide="<?php echo e($doc->type_icon); ?>" class="w-5 h-5 text-gray-500"></i>
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2">
                            <span class="text-sm font-medium"><?php echo e($doc->type_label); ?></span>
                            <?php if($doc->original_name): ?>
                                <span class="text-xs text-gray-400 truncate">— <?php echo e($doc->original_name); ?></span>
                            <?php endif; ?>
                        </div>
                        <div class="text-xs text-gray-400 flex flex-wrap gap-x-3 gap-y-0.5 mt-0.5">
                            <?php if($doc->file_size_kb): ?>
                                <span><?php echo e(number_format($doc->file_size_kb)); ?> KB</span>
                            <?php endif; ?>
                            <span><?php echo e($doc->created_at?->format('Y-m-d H:i')); ?></span>
                            <?php if($doc->uploader): ?>
                                <span>yukladi: <?php echo e(trim($doc->uploader->name . ' ' . $doc->uploader->lastname)); ?></span>
                            <?php endif; ?>
                        </div>
                        <?php if($doc->description): ?>
                            <p class="text-xs text-gray-500 mt-1"><?php echo e($doc->description); ?></p>
                        <?php endif; ?>
                    </div>
                    <div class="flex items-center gap-2 flex-shrink-0">
                        <a href="<?php echo e($doc->file_url); ?>" target="_blank"
                           class="btn btn-secondary px-3 py-1.5 text-xs flex items-center gap-1">
                            <i data-lucide="eye" class="w-3.5 h-3.5"></i> Ko'rish
                        </a>
                        <form method="POST" action="<?php echo e(route('admin.sellers.documents.destroy', [$seller, $doc])); ?>"
                              onsubmit="return confirm('Hujjatni o\'chirishga ishonchingiz komilmi?')">
                            <?php echo csrf_field(); ?>
                            <?php echo method_field('DELETE'); ?>
                            <button type="submit" class="btn btn-danger px-3 py-1.5 text-xs flex items-center gap-1">
                                <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                            </button>
                        </form>
                    </div>
                </div>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </div>
    <?php endif; ?>

    
    <div class="mt-6 pt-4 border-t border-gray-100 dark:border-white/10">
        <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-200 mb-2">Tez uzaytirish</h4>
        <p class="text-xs text-gray-400 mb-3">Mavjud <span class="font-mono">contract_expires_at</span> sanasiga tanlangan oy qo'shadi va <span class="font-mono">seller_contract_history</span> ga log yozadi.</p>
        <form method="POST" action="<?php echo e(route('admin.sellers.contract.extend', $seller)); ?>"
              class="grid grid-cols-1 md:grid-cols-4 gap-3">
            <?php echo csrf_field(); ?>
            <?php echo method_field('PATCH'); ?>
            <div>
                <label class="text-xs text-gray-500 mb-1 block">Oy soni</label>
                <select name="months" class="select" required>
                    <option value="3">3 oy</option>
                    <option value="6">6 oy</option>
                    <option value="12" selected>12 oy</option>
                    <option value="24">24 oy</option>
                </select>
            </div>
            <div class="md:col-span-2">
                <label class="text-xs text-gray-500 mb-1 block">Izoh</label>
                <input name="notes" class="input" placeholder="Masalan: seller o'z vaqtida to'lov qildi">
            </div>
            <div class="flex items-end">
                <button type="submit" class="btn btn-primary w-full flex items-center justify-center gap-2">
                    <i data-lucide="calendar-plus" class="w-4 h-4"></i> Uzaytirish
                </button>
            </div>
        </form>
    </div>

    
    <?php if($seller->contractHistory->isNotEmpty()): ?>
        <div class="mt-6 pt-4 border-t border-gray-100 dark:border-white/10">
            <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-200 mb-3">Shartnoma tarixi</h4>
            <ol class="space-y-2">
                <?php $__currentLoopData = $seller->contractHistory; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $h): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <li class="flex items-start gap-3 text-xs">
                        <span class="inline-flex items-center justify-center px-2 py-0.5 rounded-full bg-<?php echo e($h->action_color); ?>-100 text-<?php echo e($h->action_color); ?>-700 dark:bg-<?php echo e($h->action_color); ?>-500/10 dark:text-<?php echo e($h->action_color); ?>-400 font-medium flex-shrink-0">
                            <?php echo e($h->action_label); ?>

                        </span>
                        <div class="flex-1">
                            <div class="text-gray-700 dark:text-gray-300">
                                <?php if($h->old_expires_at && $h->new_expires_at): ?>
                                    <span class="font-mono"><?php echo e($h->old_expires_at->format('Y-m-d')); ?></span>
                                    →
                                    <span class="font-mono font-semibold"><?php echo e($h->new_expires_at->format('Y-m-d')); ?></span>
                                <?php elseif($h->new_expires_at): ?>
                                    Tugash: <span class="font-mono"><?php echo e($h->new_expires_at->format('Y-m-d')); ?></span>
                                <?php endif; ?>
                                <?php if($h->contract_number): ?>
                                    · № <?php echo e($h->contract_number); ?>

                                <?php endif; ?>
                            </div>
                            <?php if($h->notes): ?>
                                <p class="text-gray-500 mt-0.5"><?php echo e($h->notes); ?></p>
                            <?php endif; ?>
                            <div class="text-[10px] text-gray-400 mt-0.5">
                                <?php echo e($h->created_at?->format('Y-m-d H:i')); ?>

                                <?php if($h->performer): ?>
                                    · <?php echo e(trim($h->performer->name . ' ' . $h->performer->lastname)); ?>

                                <?php endif; ?>
                            </div>
                        </div>
                    </li>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </ol>
        </div>
    <?php endif; ?>
</section>


<script>
(function(){
    const pills   = document.querySelectorAll('[data-seller-tab]');
    const panels  = document.querySelectorAll('[data-seller-panel]');
    function activate(key){
        pills.forEach(p => p.classList.toggle('active', p.dataset.sellerTab === key));
        panels.forEach(s => s.classList.toggle('hidden', s.dataset.sellerPanel !== key));
        if (history && history.replaceState) {
            history.replaceState(null, '', '#' + key);
        }
    }
    pills.forEach(p => p.addEventListener('click', () => activate(p.dataset.sellerTab)));
    // Initial: URL hash dan yoki birinchi pill dan
    const hash = (location.hash || '').replace('#','');
    const valid = ['main','legal','contract','documents','premium'];
    activate(valid.includes(hash) ? hash : 'main');
})();
</script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('a122.layouts.admin', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/abbos/PROJECTS/MY/kitobchi-server/kitobchi-laravel/resources/views/a122/sellers/edit.blade.php ENDPATH**/ ?>