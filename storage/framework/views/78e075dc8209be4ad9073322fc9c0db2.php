<?php $__env->startSection('content'); ?>
<?php if (isset($component)) { $__componentOriginal781784ddc1cff9584ff159910cf34f25 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal781784ddc1cff9584ff159910cf34f25 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.common.page-breadcrumb','data' => ['pageTitle' => 'Tahrirlash: '.e($user->full_name).'']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('common.page-breadcrumb'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['pageTitle' => 'Tahrirlash: '.e($user->full_name).'']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal781784ddc1cff9584ff159910cf34f25)): ?>
<?php $attributes = $__attributesOriginal781784ddc1cff9584ff159910cf34f25; ?>
<?php unset($__attributesOriginal781784ddc1cff9584ff159910cf34f25); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal781784ddc1cff9584ff159910cf34f25)): ?>
<?php $component = $__componentOriginal781784ddc1cff9584ff159910cf34f25; ?>
<?php unset($__componentOriginal781784ddc1cff9584ff159910cf34f25); ?>
<?php endif; ?>

<?php if(session('success')): ?>
    <?php if (isset($component)) { $__componentOriginal746de018ded8594083eb43be3f1332e1 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal746de018ded8594083eb43be3f1332e1 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.ui.alert','data' => ['variant' => 'success','title' => 'Muvaffaqiyatli!','message' => ''.e(session('success')).'','class' => 'mb-5']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('ui.alert'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['variant' => 'success','title' => 'Muvaffaqiyatli!','message' => ''.e(session('success')).'','class' => 'mb-5']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal746de018ded8594083eb43be3f1332e1)): ?>
<?php $attributes = $__attributesOriginal746de018ded8594083eb43be3f1332e1; ?>
<?php unset($__attributesOriginal746de018ded8594083eb43be3f1332e1); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal746de018ded8594083eb43be3f1332e1)): ?>
<?php $component = $__componentOriginal746de018ded8594083eb43be3f1332e1; ?>
<?php unset($__componentOriginal746de018ded8594083eb43be3f1332e1); ?>
<?php endif; ?>
<?php endif; ?>
<?php if($errors->any()): ?>
    <?php if (isset($component)) { $__componentOriginal746de018ded8594083eb43be3f1332e1 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal746de018ded8594083eb43be3f1332e1 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.ui.alert','data' => ['variant' => 'error','title' => 'Xatolik!','message' => ''.e($errors->first()).'','class' => 'mb-5']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('ui.alert'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['variant' => 'error','title' => 'Xatolik!','message' => ''.e($errors->first()).'','class' => 'mb-5']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal746de018ded8594083eb43be3f1332e1)): ?>
<?php $attributes = $__attributesOriginal746de018ded8594083eb43be3f1332e1; ?>
<?php unset($__attributesOriginal746de018ded8594083eb43be3f1332e1); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal746de018ded8594083eb43be3f1332e1)): ?>
<?php $component = $__componentOriginal746de018ded8594083eb43be3f1332e1; ?>
<?php unset($__componentOriginal746de018ded8594083eb43be3f1332e1); ?>
<?php endif; ?>
<?php endif; ?>

<form method="POST" action="<?php echo e(route('admin.users.update', $user->id)); ?>" enctype="multipart/form-data">
    <?php echo csrf_field(); ?>
    <?php echo method_field('PUT'); ?>

    <div class="grid grid-cols-1 gap-5 xl:grid-cols-3">

        
        <div class="space-y-5 xl:col-span-1">

            
            <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03] lg:p-6"
                x-data="{
                    preview: '<?php echo e($user->avatar); ?>',
                    handleFile(e) {
                        const f = e.target.files[0];
                        if (!f) return;
                        const r = new FileReader();
                        r.onload = ev => this.preview = ev.target.result;
                        r.readAsDataURL(f);
                    }
                }">
                <h4 class="mb-5 text-base font-semibold text-gray-800 dark:text-white/90">Profil rasmi</h4>
                <div class="flex flex-col items-center gap-4">
                    <div class="relative h-24 w-24 cursor-pointer overflow-hidden rounded-full border border-gray-200 dark:border-gray-800"
                        @click="$refs.fileInput.click()">
                        <template x-if="preview">
                            <img :src="preview" alt="avatar" class="h-full w-full object-cover" />
                        </template>
                        <template x-if="!preview">
                            <div class="flex h-full w-full items-center justify-center bg-brand-500 text-2xl font-bold text-white">
                                <?php echo e(strtoupper(substr($user->name,0,1))); ?><?php echo e(strtoupper(substr($user->lastname,0,1))); ?>

                            </div>
                        </template>
                        <div class="absolute inset-0 flex items-center justify-center bg-black/30 opacity-0 hover:opacity-100 transition-opacity">
                            <svg class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                        </div>
                    </div>
                    <input type="file" name="avatar" x-ref="fileInput" @change="handleFile($event)" accept="image/*" class="hidden" />
                    <p class="text-xs text-gray-400 dark:text-gray-500">JPG, PNG · Maks. 2 MB</p>
                </div>
            </div>

            
            <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03] lg:p-6">
                <h4 class="mb-5 text-base font-semibold text-gray-800 dark:text-white/90">Maxsus imtiyozlar</h4>
                <div class="space-y-4">

                    
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-800 dark:text-white/90">Premium ★</p>
                            <p class="text-xs leading-normal text-gray-500 dark:text-gray-400">Premium foydalanuvchi</p>
                        </div>
                        <label class="relative inline-flex cursor-pointer items-center">
                            <input type="hidden" name="is_premium" value="0">
                            <input type="checkbox" name="is_premium" value="1" <?php echo e($user->is_premium ? 'checked' : ''); ?> class="sr-only peer">
                            <div class="h-6 w-11 rounded-full bg-gray-200 peer-checked:bg-brand-500 after:absolute after:left-[2px] after:top-[2px] after:h-5 after:w-5 after:rounded-full after:bg-white after:transition-all peer-checked:after:translate-x-5 dark:bg-gray-700"></div>
                        </label>
                    </div>

                    
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">Premium muddati</label>
                        <input type="datetime-local" name="premium_until"
                            value="<?php echo e($user->premium_until ? \Carbon\Carbon::parse($user->premium_until)->format('Y-m-d\TH:i') : ''); ?>"
                            class="dark:bg-dark-900 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 shadow-theme-xs focus:border-brand-300 focus:outline-hidden focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90" />
                    </div>

                    
                    <div class="flex items-center justify-between border-t border-gray-100 pt-4 dark:border-gray-800">
                        <div>
                            <p class="text-sm font-medium text-gray-800 dark:text-white/90">Tasdiqlangan</p>
                            <p class="text-xs leading-normal text-gray-500 dark:text-gray-400">Telefon tasdiqlangan</p>
                        </div>
                        <label class="relative inline-flex cursor-pointer items-center">
                            <input type="hidden" name="isVerified" value="0">
                            <input type="checkbox" name="isVerified" value="1" <?php echo e($user->isVerified ? 'checked' : ''); ?> class="sr-only peer">
                            <div class="h-6 w-11 rounded-full bg-gray-200 peer-checked:bg-brand-500 after:absolute after:left-[2px] after:top-[2px] after:h-5 after:w-5 after:rounded-full after:bg-white after:transition-all peer-checked:after:translate-x-5 dark:bg-gray-700"></div>
                        </label>
                    </div>

                    
                    <div class="flex items-center justify-between border-t border-gray-100 pt-4 dark:border-gray-800">
                        <div>
                            <p class="text-sm font-medium text-gray-800 dark:text-white/90">Support</p>
                            <p class="text-xs leading-normal text-gray-500 dark:text-gray-400">Yordam xizmati</p>
                        </div>
                        <label class="relative inline-flex cursor-pointer items-center">
                            <input type="hidden" name="isSupport" value="0">
                            <input type="checkbox" name="isSupport" value="1" <?php echo e($user->isSupport ? 'checked' : ''); ?> class="sr-only peer">
                            <div class="h-6 w-11 rounded-full bg-gray-200 peer-checked:bg-brand-500 after:absolute after:left-[2px] after:top-[2px] after:h-5 after:w-5 after:rounded-full after:bg-white after:transition-all peer-checked:after:translate-x-5 dark:bg-gray-700"></div>
                        </label>
                    </div>

                    
                    <div class="border-t border-gray-100 pt-4 dark:border-gray-800">
                        <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">Hisob holati</label>
                        <select name="isDeleted"
                            class="dark:bg-dark-900 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 shadow-theme-xs focus:border-brand-300 focus:outline-hidden focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                            <option value="no"  <?php if($user->isDeleted !== 'yes'): echo 'selected'; endif; ?>>Faol</option>
                            <option value="yes" <?php if($user->isDeleted === 'yes'): echo 'selected'; endif; ?>>O'chirilgan</option>
                        </select>
                    </div>
                </div>
            </div>

        </div>

        
        <div class="space-y-5 xl:col-span-2">

            
            <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03] lg:p-6">
                <h4 class="mb-5 text-base font-semibold text-gray-800 dark:text-white/90 lg:mb-6">Shaxsiy ma'lumotlar</h4>
                <div class="grid grid-cols-1 gap-x-6 gap-y-5 lg:grid-cols-2">

                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">Ism</label>
                        <input type="text" name="name" value="<?php echo e(old('name', $user->name)); ?>" placeholder="Ism"
                            class="dark:bg-dark-900 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 shadow-theme-xs placeholder:text-gray-400 focus:border-brand-300 focus:outline-hidden focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30 dark:focus:border-brand-800" required />
                    </div>

                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">Familiya</label>
                        <input type="text" name="lastname" value="<?php echo e(old('lastname', $user->lastname)); ?>" placeholder="Familiya"
                            class="dark:bg-dark-900 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 shadow-theme-xs placeholder:text-gray-400 focus:border-brand-300 focus:outline-hidden focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30 dark:focus:border-brand-800" required />
                    </div>

                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">Telefon</label>
                        <input type="tel" name="phone_number" value="<?php echo e(old('phone_number', $user->phone_number)); ?>" placeholder="+998901234567"
                            class="dark:bg-dark-900 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 shadow-theme-xs placeholder:text-gray-400 focus:border-brand-300 focus:outline-hidden focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30 dark:focus:border-brand-800" />
                    </div>

                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">Email</label>
                        <input type="email" name="email" value="<?php echo e(old('email', $user->email)); ?>" placeholder="email@example.com"
                            class="dark:bg-dark-900 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 shadow-theme-xs placeholder:text-gray-400 focus:border-brand-300 focus:outline-hidden focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30 dark:focus:border-brand-800" />
                    </div>

                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">Jinsi</label>
                        <select name="sex"
                            class="dark:bg-dark-900 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 shadow-theme-xs focus:border-brand-300 focus:outline-hidden focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                            <option value="">Noma'lum</option>
                            <option value="M" <?php if($user->sex==='M'): echo 'selected'; endif; ?>>Erkak</option>
                            <option value="F" <?php if($user->sex==='F'): echo 'selected'; endif; ?>>Ayol</option>
                        </select>
                    </div>

                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">Til</label>
                        <select name="locale"
                            class="dark:bg-dark-900 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 shadow-theme-xs focus:border-brand-300 focus:outline-hidden focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                            <option value="uz" <?php if($user->locale==='uz'): echo 'selected'; endif; ?>>O'zbek</option>
                            <option value="ru" <?php if($user->locale==='ru'): echo 'selected'; endif; ?>>Rus</option>
                            <option value="en" <?php if($user->locale==='en'): echo 'selected'; endif; ?>>Ingliz</option>
                            <option value="ja" <?php if($user->locale==='ja'): echo 'selected'; endif; ?>>Yapon</option>
                        </select>
                    </div>

                    <div class="lg:col-span-2">
                        <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">Lavozim</label>
                        <input type="text" name="position" value="<?php echo e(old('position', $user->position)); ?>" placeholder="O'quvchi, Yozuvchi..."
                            class="dark:bg-dark-900 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 shadow-theme-xs placeholder:text-gray-400 focus:border-brand-300 focus:outline-hidden focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30 dark:focus:border-brand-800" />
                    </div>

                    <div class="lg:col-span-2">
                        <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">Bio / Status</label>
                        <textarea name="status" rows="3" placeholder="Foydalanuvchi haqida..."
                            class="dark:bg-dark-900 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 shadow-theme-xs placeholder:text-gray-400 focus:border-brand-300 focus:outline-hidden focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30 dark:focus:border-brand-800 resize-none"><?php echo e(old('status', $user->status)); ?></textarea>
                    </div>
                </div>
            </div>

            
            <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03] lg:p-6">
                <h4 class="mb-5 text-base font-semibold text-gray-800 dark:text-white/90 lg:mb-6">Moliya va limitlar</h4>
                <div class="grid grid-cols-1 gap-x-6 gap-y-5 lg:grid-cols-3">
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">Balans (UZS)</label>
                        <input type="number" name="real_balance" value="<?php echo e(old('real_balance', $user->real_balance)); ?>" placeholder="0"
                            class="dark:bg-dark-900 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 shadow-theme-xs placeholder:text-gray-400 focus:border-brand-300 focus:outline-hidden focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30 dark:focus:border-brand-800" />
                    </div>
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">Cashback (UZS)</label>
                        <input type="number" name="cashback" value="<?php echo e(old('cashback', $user->cashback)); ?>" placeholder="0"
                            class="dark:bg-dark-900 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 shadow-theme-xs placeholder:text-gray-400 focus:border-brand-300 focus:outline-hidden focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30 dark:focus:border-brand-800" />
                    </div>
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">AI limit</label>
                        <input type="number" name="ai_limit" value="<?php echo e(old('ai_limit', $user->ai_limit)); ?>" placeholder="25"
                            class="dark:bg-dark-900 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 shadow-theme-xs placeholder:text-gray-400 focus:border-brand-300 focus:outline-hidden focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30 dark:focus:border-brand-800" />
                    </div>
                </div>
            </div>

            
            <div class="flex items-center gap-3 lg:justify-end">
                <a href="<?php echo e(route('admin.users.show', $user->id)); ?>"
                    class="shadow-theme-xs flex w-full justify-center rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-white/[0.03] sm:w-auto">
                    Orqaga
                </a>
                <button type="submit"
                    class="flex w-full justify-center rounded-lg bg-brand-500 px-4 py-2.5 text-sm font-medium text-white hover:bg-brand-600 sm:w-auto">
                    Saqlash
                </button>
            </div>

        </div>
    </div>
</form>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /var/www/www-root/data/www/kitobchi.com/resources/views/pages/users/edit.blade.php ENDPATH**/ ?>