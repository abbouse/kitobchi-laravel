<?php $__env->startSection('title', 'Kuryerni tahrirlash'); ?>
<?php $__env->startSection('page-title', 'Kuryerni tahrirlash'); ?>

<?php $__env->startSection('content'); ?>
<div class="mb-4">
    <a href="<?php echo e(route('admin.couriers.show', $courier)); ?>" class="btn btn-secondary flex items-center gap-2 w-fit">
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


<div class="tab-pills flex items-center gap-2 mb-4 flex-wrap">
    <button type="button" class="tab-pill active" data-courier-tab="main">
        <i class="bi bi-person"></i> Asosiy
    </button>
    <button type="button" class="tab-pill" data-courier-tab="transport">
        <i class="bi bi-bicycle"></i> Transport va karta
    </button>
    <button type="button" class="tab-pill" data-courier-tab="documents">
        <i class="bi bi-file-earmark-text"></i> Hujjatlar
        <?php if($courier->documents->count() > 0): ?>
            <span class="ml-1 inline-flex items-center justify-center min-w-[18px] px-1 text-[10px] rounded-full bg-blue-100 text-blue-700 dark:bg-blue-500/20 dark:text-blue-300">
                <?php echo e($courier->documents->count()); ?>

            </span>
        <?php endif; ?>
    </button>
    <button type="button" class="tab-pill" data-courier-tab="verification">
        <i class="bi bi-shield-check"></i> Verifikatsiya
    </button>
</div>

<form method="POST" action="<?php echo e(route('admin.couriers.update', $courier)); ?>" enctype="multipart/form-data" class="space-y-4">
    <?php echo csrf_field(); ?>
    <?php echo method_field('PUT'); ?>

    
    <section data-courier-panel="main" class="card-panel p-5 grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
            <label class="text-xs text-gray-500 mb-1 block">Ism <span class="text-red-500">*</span></label>
            <input name="first_name" class="input" required placeholder="Ism"
                   value="<?php echo e(old('first_name', $courier->first_name)); ?>">
        </div>

        <div>
            <label class="text-xs text-gray-500 mb-1 block">Familiya <span class="text-red-500">*</span></label>
            <input name="last_name" class="input" required placeholder="Familiya"
                   value="<?php echo e(old('last_name', $courier->last_name)); ?>">
        </div>

        <div>
            <label class="text-xs text-gray-500 mb-1 block">Telefon raqam <span class="text-red-500">*</span></label>
            <input name="phone_number" class="input" required placeholder="+998901234567"
                   value="<?php echo e(old('phone_number', $courier->phone_number)); ?>">
        </div>

        <div>
            <label class="text-xs text-gray-500 mb-1 block">Viloyat</label>
            <input name="region" class="input" placeholder="Viloyat nomi"
                   value="<?php echo e(old('region', $courier->region)); ?>">
        </div>

        <div>
            <label class="text-xs text-gray-500 mb-1 block">Yangi parol <span class="text-gray-400">(ixtiyoriy)</span></label>
            <input name="password" type="password" class="input" placeholder="Bo'sh qoldirilsa, o'zgarmaydi" autocomplete="new-password">
        </div>

        <div>
            <label class="text-xs text-gray-500 mb-1 block">Holat</label>
            <select name="status" class="select">
                <option value="pending"  <?php if(old('status', $courier->status) === 'pending'): echo 'selected'; endif; ?>>Kutilmoqda</option>
                <option value="approved" <?php if(old('status', $courier->status) === 'approved'): echo 'selected'; endif; ?>>Tasdiqlangan</option>
                <option value="rejected" <?php if(old('status', $courier->status) === 'rejected'): echo 'selected'; endif; ?>>Rad etilgan</option>
            </select>
        </div>

        <div>
            <label class="text-xs text-gray-500 mb-1 block">Tug'ilgan sana</label>
            <input name="birthdate" type="date" class="input"
                   value="<?php echo e(old('birthdate', optional($courier->birthdate)->format('Y-m-d'))); ?>">
        </div>

        <div>
            <label class="text-xs text-gray-500 mb-1 block">Yashash manzili</label>
            <input name="home_address" class="input" placeholder="Toshkent, ..."
                   value="<?php echo e(old('home_address', $courier->home_address)); ?>">
        </div>

        <div class="md:col-span-2">
            <label class="text-xs text-gray-500 mb-1 block">Profil rasmi</label>
            <?php if($courier->photo): ?>
                <div class="mb-2 flex items-center gap-3">
                    <img src="<?php echo e(Str::startsWith($courier->photo, 'http') ? $courier->photo : asset('storage/' . $courier->photo)); ?>"
                         alt="Joriy rasm" class="w-12 h-12 rounded-full object-cover border border-gray-200 dark:border-white/10">
                    <span class="text-xs text-gray-400">Joriy rasm. Yangi rasm yuklash uchun faylni tanlang.</span>
                </div>
            <?php endif; ?>
            <input name="photo" type="file" class="input" accept="image/*">
        </div>
    </section>

    
    <section data-courier-panel="transport" class="card-panel p-5 grid grid-cols-1 md:grid-cols-2 gap-4 hidden">
        <div class="md:col-span-2">
            <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-200 mb-1">Transport va to'lov ma'lumotlari</h3>
            <p class="text-[11px] text-gray-400">Kuryerning yetkazib berish vositasi va karta egasi haqida ma'lumotlar.</p>
        </div>

        <div>
            <label class="text-xs text-gray-500 mb-1 block">Transport turi <span class="text-red-500">*</span></label>
            <select name="transport_type" class="select" id="transportTypeSelect">
                <?php $__currentLoopData = [
                    'foot'       => 'Piyoda',
                    'bicycle'    => 'Velosiped',
                    'motorcycle' => 'Mototsikl',
                    'car'        => 'Avtomobil',
                ]; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $v => $l): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <option value="<?php echo e($v); ?>" <?php if(old('transport_type', $courier->transport_type) === $v): echo 'selected'; endif; ?>><?php echo e($l); ?></option>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </select>
        </div>

        <div>
            <label class="text-xs text-gray-500 mb-1 block">STIR (INN)</label>
            <input name="inn" class="input" placeholder="123456789"
                   value="<?php echo e(old('inn', $courier->inn)); ?>">
        </div>

        
        <?php $needsVehicle = in_array(old('transport_type', $courier->transport_type), ['motorcycle', 'car']); ?>
        <div class="md:col-span-2 grid grid-cols-1 md:grid-cols-2 gap-4 <?php echo e($needsVehicle ? '' : 'hidden'); ?>" id="vehicleFields">
            <div class="md:col-span-2 -mb-2">
                <h4 class="text-xs font-semibold text-gray-600 dark:text-gray-300">Transport vositasi</h4>
            </div>
            <div>
                <label class="text-xs text-gray-500 mb-1 block">Marka</label>
                <input name="vehicle_brand" class="input" placeholder="Chevrolet"
                       value="<?php echo e(old('vehicle_brand', $courier->vehicle_brand)); ?>">
            </div>
            <div>
                <label class="text-xs text-gray-500 mb-1 block">Model</label>
                <input name="vehicle_model" class="input" placeholder="Cobalt"
                       value="<?php echo e(old('vehicle_model', $courier->vehicle_model)); ?>">
            </div>
            <div>
                <label class="text-xs text-gray-500 mb-1 block">Rang</label>
                <input name="vehicle_color" class="input" placeholder="Oq"
                       value="<?php echo e(old('vehicle_color', $courier->vehicle_color)); ?>">
            </div>
            <div>
                <label class="text-xs text-gray-500 mb-1 block">Davlat raqami</label>
                <input name="vehicle_plate_number" class="input" placeholder="01 A 123 BC" style="text-transform: uppercase"
                       value="<?php echo e(old('vehicle_plate_number', $courier->vehicle_plate_number)); ?>">
            </div>

            <div class="md:col-span-2 -mb-2 mt-2">
                <h4 class="text-xs font-semibold text-gray-600 dark:text-gray-300">Haydovchi guvohnomasi</h4>
            </div>
            <div>
                <label class="text-xs text-gray-500 mb-1 block">Guvohnoma raqami</label>
                <input name="driver_license_number" class="input" placeholder="AA1234567"
                       value="<?php echo e(old('driver_license_number', $courier->driver_license_number)); ?>">
            </div>
            <div>
                <label class="text-xs text-gray-500 mb-1 block">Berilgan sana</label>
                <input name="driver_license_issued_at" type="date" class="input"
                       value="<?php echo e(old('driver_license_issued_at', optional($courier->driver_license_issued_at)->format('Y-m-d'))); ?>">
            </div>
            <div>
                <label class="text-xs text-gray-500 mb-1 block">Tugash sanasi</label>
                <input name="driver_license_expires_at" type="date" class="input"
                       value="<?php echo e(old('driver_license_expires_at', optional($courier->driver_license_expires_at)->format('Y-m-d'))); ?>">
                <?php if($courier->driver_license_expires_at): ?>
                    <?php $ldays = (int) now()->startOfDay()->diffInDays($courier->driver_license_expires_at, false); ?>
                    <p class="text-[10px] mt-1 <?php echo e($ldays < 0 ? 'text-red-500' : ($ldays <= 60 ? 'text-amber-500' : 'text-gray-400')); ?>">
                        <?php if($ldays < 0): ?>
                            <?php echo e(abs($ldays)); ?> kun oldin tugagan.
                        <?php else: ?>
                            <?php echo e($ldays); ?> kun qoldi.
                        <?php endif; ?>
                    </p>
                <?php endif; ?>
            </div>
        </div>

        <div class="md:col-span-2 -mb-2 mt-2">
            <h4 class="text-xs font-semibold text-gray-600 dark:text-gray-300">Pasport</h4>
        </div>
        <div>
            <label class="text-xs text-gray-500 mb-1 block">Pasport seriyasi</label>
            <input name="passport_series" class="input" placeholder="AA"
                   value="<?php echo e(old('passport_series', $courier->passport_series)); ?>">
        </div>
        <div>
            <label class="text-xs text-gray-500 mb-1 block">Pasport raqami</label>
            <input name="passport_number" class="input" placeholder="1234567"
                   value="<?php echo e(old('passport_number', $courier->passport_number)); ?>">
        </div>
        <div class="md:col-span-2 grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="text-xs text-gray-500 mb-1 block">Kim tomonidan berilgan</label>
                <input name="passport_issued_by" class="input" placeholder="IIB / Mirzo Ulug'bek tumani"
                       value="<?php echo e(old('passport_issued_by', $courier->passport_issued_by)); ?>">
            </div>
            <div>
                <label class="text-xs text-gray-500 mb-1 block">Berilgan sana</label>
                <input name="passport_issued_at" type="date" class="input"
                       value="<?php echo e(old('passport_issued_at', optional($courier->passport_issued_at)->format('Y-m-d'))); ?>">
            </div>
        </div>

        <div class="md:col-span-2 -mb-2 mt-2">
            <h4 class="text-xs font-semibold text-gray-600 dark:text-gray-300">Karta ma'lumotlari</h4>
        </div>
        <div>
            <label class="text-xs text-gray-500 mb-1 block">Karta raqami</label>
            <input name="payment_card" class="input" placeholder="8600 1234 5678 9012"
                   value="<?php echo e(old('payment_card', $courier->payment_card)); ?>">
        </div>
        <div>
            <label class="text-xs text-gray-500 mb-1 block">Karta egasi</label>
            <input name="card_holder" class="input" placeholder="ALI VALIYEV" style="text-transform: uppercase"
                   value="<?php echo e(old('card_holder', $courier->card_holder)); ?>">
        </div>
    </section>

    
    <section data-courier-panel="verification" class="card-panel p-5 grid grid-cols-1 md:grid-cols-2 gap-4 hidden">
        <div class="md:col-span-2">
            <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-200 mb-1">Verifikatsiya holati</h3>
            <p class="text-[11px] text-gray-400">Kuryer hujjatlarini ko'rib chiqib, tasdiqlash yoki rad etish.</p>
        </div>

        <div>
            <label class="text-xs text-gray-500 mb-1 block">Holat</label>
            <select name="verification_status" class="select">
                <?php $__currentLoopData = [
                    'unverified' => 'Tekshirilmagan',
                    'pending'    => 'Ko\'rib chiqilmoqda',
                    'verified'   => 'Tasdiqlangan',
                    'rejected'   => 'Rad etilgan',
                ]; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $v => $l): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <option value="<?php echo e($v); ?>" <?php if(old('verification_status', $courier->verification_status) === $v): echo 'selected'; endif; ?>><?php echo e($l); ?></option>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </select>
            <?php if($courier->verified_at): ?>
                <p class="text-[10px] text-gray-400 mt-1">
                    Tasdiqlangan: <span class="font-mono"><?php echo e($courier->verified_at->format('Y-m-d H:i')); ?></span>
                </p>
            <?php endif; ?>
        </div>

        <div class="md:col-span-2">
            <label class="text-xs text-gray-500 mb-1 block">Verifikatsiya bo'yicha izohlar</label>
            <textarea name="verification_notes" class="input" rows="3"
                      placeholder="Hujjatlardagi muammolar, qaytarish sabablari va h.k."><?php echo e(old('verification_notes', $courier->verification_notes)); ?></textarea>
        </div>
    </section>

    
    <div class="flex items-center justify-end gap-2 pt-2 border-t border-gray-100 dark:border-white/10">
        <a href="<?php echo e(route('admin.couriers.show', $courier)); ?>" class="btn btn-secondary">Bekor</a>
        <button type="submit" class="btn btn-primary flex items-center gap-2">
            <i data-lucide="save" class="w-4 h-4"></i> Saqlash
        </button>
    </div>
</form>


<section data-courier-panel="documents" class="card-panel p-5 hidden mt-4">
    <div class="mb-4 flex items-center justify-between flex-wrap gap-2">
        <div>
            <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-200 mb-0.5">Hujjatlar</h3>
            <p class="text-[11px] text-gray-400">Pasport, haydovchi guvohnomasi, transport texpasporti va h.k.</p>
        </div>
    </div>

    
    <form method="POST" action="<?php echo e(route('admin.couriers.documents.store', $courier)); ?>" enctype="multipart/form-data"
          class="grid grid-cols-1 md:grid-cols-3 gap-3 p-3 mb-4 rounded-lg border border-dashed border-gray-300 dark:border-white/10">
        <?php echo csrf_field(); ?>
        <div>
            <label class="text-xs text-gray-500 mb-1 block">Hujjat turi</label>
            <select name="type" class="select" required>
                <?php $__currentLoopData = [
                    'passport'            => 'Pasport',
                    'driver_license'      => 'Haydovchi guvohnomasi',
                    'vehicle_reg'         => 'Transport guvohnomasi',
                    'vehicle_insurance'   => 'Sug\'urta polisi',
                    'inn_certificate'     => 'STIR guvohnomasi',
                    'medical_cert'        => 'Tibbiy ma\'lumotnoma',
                    'photo_with_passport' => 'Pasport bilan selfi',
                    'other'               => 'Boshqa',
                ]; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $v => $l): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <option value="<?php echo e($v); ?>"><?php echo e($l); ?></option>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </select>
        </div>
        <div>
            <label class="text-xs text-gray-500 mb-1 block">Fayl (PDF/JPG/PNG, &le; 10 MB)</label>
            <input name="file" type="file" class="input" accept=".pdf,image/*" required>
        </div>
        <div>
            <label class="text-xs text-gray-500 mb-1 block">Izoh (ixtiyoriy)</label>
            <input name="description" class="input" placeholder="Pasportning 2-3 sahifasi">
        </div>
        <div class="md:col-span-3 flex justify-end">
            <button type="submit" class="btn btn-primary text-sm flex items-center gap-2">
                <i data-lucide="upload" class="w-4 h-4"></i> Yuklash
            </button>
        </div>
    </form>

    
    <?php if($courier->documents->isEmpty()): ?>
        <p class="text-sm text-gray-400 text-center py-6">Hujjatlar yo'q.</p>
    <?php else: ?>
        <div class="space-y-2">
            <?php $__currentLoopData = $courier->documents; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $doc): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <div class="flex items-center gap-3 p-3 rounded-lg border border-gray-200 dark:border-white/10">
                    <i data-lucide="<?php echo e($doc->type_icon); ?>" class="w-5 h-5 text-blue-500 flex-shrink-0"></i>
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2 flex-wrap">
                            <span class="font-medium text-sm text-gray-700 dark:text-gray-200"><?php echo e($doc->type_label); ?></span>
                            <?php if($doc->original_name): ?>
                                <span class="text-xs text-gray-400 truncate"><?php echo e($doc->original_name); ?></span>
                            <?php endif; ?>
                            <?php if($doc->file_size_kb): ?>
                                <span class="text-[10px] text-gray-400"><?php echo e(number_format($doc->file_size_kb)); ?> KB</span>
                            <?php endif; ?>
                        </div>
                        <?php if($doc->description): ?>
                            <p class="text-xs text-gray-500 mt-0.5"><?php echo e($doc->description); ?></p>
                        <?php endif; ?>
                        <p class="text-[10px] text-gray-400 mt-0.5">
                            <?php echo e($doc->created_at->format('Y-m-d H:i')); ?>

                            <?php if($doc->uploader): ?>
                                · <?php echo e(trim(($doc->uploader->name ?? '') . ' ' . ($doc->uploader->lastname ?? '')) ?: 'Admin'); ?>

                            <?php endif; ?>
                        </p>
                    </div>
                    <div class="flex items-center gap-2 flex-shrink-0">
                        <a href="<?php echo e($doc->file_url); ?>" target="_blank" class="btn btn-secondary text-xs flex items-center gap-1">
                            <i data-lucide="external-link" class="w-3.5 h-3.5"></i> Ko'rish
                        </a>
                        <form method="POST" action="<?php echo e(route('admin.couriers.documents.destroy', [$courier, $doc])); ?>"
                              onsubmit="return confirm('Hujjatni o\'chirishni tasdiqlaysizmi?');">
                            <?php echo csrf_field(); ?>
                            <?php echo method_field('DELETE'); ?>
                            <button type="submit" class="btn btn-danger text-xs flex items-center gap-1">
                                <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                            </button>
                        </form>
                    </div>
                </div>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </div>
    <?php endif; ?>
</section>


<script>
(function () {
    const tabs   = document.querySelectorAll('[data-courier-tab]');
    const panels = document.querySelectorAll('[data-courier-panel]');

    function activate(name) {
        tabs.forEach(t => t.classList.toggle('active', t.dataset.courierTab === name));
        panels.forEach(p => p.classList.toggle('hidden', p.dataset.courierPanel !== name));
        if (history && history.replaceState) {
            history.replaceState(null, '', '#' + name);
        }
    }

    tabs.forEach(t => t.addEventListener('click', () => activate(t.dataset.courierTab)));

    // URL hash bilan ochish
    const hash = (location.hash || '').replace('#', '');
    const valid = ['main', 'transport', 'documents', 'verification'];
    if (valid.includes(hash)) activate(hash);

    // Transport turi o'zgarsa, transport vositasi maydonlari ko'rinishini sozlash
    const transportSel = document.getElementById('transportTypeSelect');
    const vehicleFields = document.getElementById('vehicleFields');
    if (transportSel && vehicleFields) {
        transportSel.addEventListener('change', () => {
            const needs = ['motorcycle', 'car'].includes(transportSel.value);
            vehicleFields.classList.toggle('hidden', !needs);
        });
    }
})();
</script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('a122.layouts.admin', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/abbos/PROJECTS/MY/kitobchi-server/kitobchi-laravel/resources/views/a122/couriers/edit.blade.php ENDPATH**/ ?>