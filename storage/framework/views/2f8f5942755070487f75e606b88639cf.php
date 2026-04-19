<?php $__env->startSection('title', isset($courier) ? 'Tahrirlash: '.$courier->first_name : 'Yangi kuryer'); ?>
<?php $__env->startSection('page-title', isset($courier) ? 'Kuryerni tahrirlash' : 'Yangi kuryer'); ?>

<?php $__env->startSection('content'); ?>

<?php if (isset($component)) { $__componentOriginal8e8786879b90e52c629eb07e5f5bbd4a = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal8e8786879b90e52c629eb07e5f5bbd4a = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.panel.page-header','data' => ['backHref' => ''.e(isset($courier) ? route('panel.couriers.show', $courier) : route('panel.couriers.index')).'']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('panel.page-header'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['back-href' => ''.e(isset($courier) ? route('panel.couriers.show', $courier) : route('panel.couriers.index')).'']); ?>
   <?php $__env->slot('heading', null, []); ?> <?php echo e(isset($courier) ? $courier->first_name.' '.$courier->last_name : 'Yangi kuryer'); ?> <?php $__env->endSlot(); ?>
   <?php $__env->slot('meta', null, []); ?> <?php echo e(isset($courier) ? "ID #$courier->id" : "Yangi kuryer qo'shish"); ?> <?php $__env->endSlot(); ?>
 <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal8e8786879b90e52c629eb07e5f5bbd4a)): ?>
<?php $attributes = $__attributesOriginal8e8786879b90e52c629eb07e5f5bbd4a; ?>
<?php unset($__attributesOriginal8e8786879b90e52c629eb07e5f5bbd4a); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal8e8786879b90e52c629eb07e5f5bbd4a)): ?>
<?php $component = $__componentOriginal8e8786879b90e52c629eb07e5f5bbd4a; ?>
<?php unset($__componentOriginal8e8786879b90e52c629eb07e5f5bbd4a); ?>
<?php endif; ?>


<form method="POST"
      action="<?php echo e(isset($courier) ? route('panel.couriers.update', $courier) : route('panel.couriers.store')); ?>"
      enctype="multipart/form-data">
  <?php echo csrf_field(); ?>
  <?php if(isset($courier)): ?> <?php echo method_field('PUT'); ?> <?php endif; ?>

  <div class="grid grid-cols-1 md:grid-cols-2 gap-3">

    <div class="xl:col-span-8 fade-up">
      <div class="p-card">
        <div class="p-card-header"><div class="p-card-title">Asosiy ma'lumotlar</div></div>
        <div style="padding:0 18px 18px">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-3">

            <div class="">
              <label class="p-form-label">Ism <span style="color:var(--p-danger)">*</span></label>
              <input type="text" name="first_name"
                     class="p-form-control <?php $__errorArgs = ['first_name'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> border-danger <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                     value="<?php echo e(old('first_name', $courier->first_name ?? '')); ?>"
                     required maxlength="25">
              <?php $__errorArgs = ['first_name'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                <div style="font-size:11px;color:var(--p-danger);margin-top:4px"><?php echo e($message); ?></div>
              <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
            </div>

            <div class="">
              <label class="p-form-label">Familiya <span style="color:var(--p-danger)">*</span></label>
              <input type="text" name="last_name"
                     class="p-form-control <?php $__errorArgs = ['last_name'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> border-danger <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                     value="<?php echo e(old('last_name', $courier->last_name ?? '')); ?>"
                     required maxlength="25">
              <?php $__errorArgs = ['last_name'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                <div style="font-size:11px;color:var(--p-danger);margin-top:4px"><?php echo e($message); ?></div>
              <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
            </div>

            <div class="">
              <label class="p-form-label">Telefon <span style="color:var(--p-danger)">*</span></label>
              <input type="text" name="phone_number"
                     class="p-form-control <?php $__errorArgs = ['phone_number'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> border-danger <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                     value="<?php echo e(old('phone_number', $courier->phone_number ?? '')); ?>"
                     required maxlength="20" placeholder="998901234567">
              <?php $__errorArgs = ['phone_number'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                <div style="font-size:11px;color:var(--p-danger);margin-top:4px"><?php echo e($message); ?></div>
              <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
            </div>

            <div class="">
              <label class="p-form-label">Viloyat <span style="color:var(--p-danger)">*</span></label>
              <select name="region"
                      class="p-form-control <?php $__errorArgs = ['region'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> border-danger <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                      required>
                <option value="">Tanlang...</option>
                <?php $__currentLoopData = [
                  "Toshkent shahri","Toshkent viloyati","Samarqand","Buxoro",
                  "Namangan","Andijon","Farg'ona","Qashqadaryo","Surxondaryo",
                  "Jizzax","Sirdaryo","Navoiy","Xorazm","Qoraqalpog'iston"
                ]; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $region): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <option value="<?php echo e($region); ?>"
                  <?php echo e(old('region', $courier->region ?? '') === $region ? 'selected' : ''); ?>>
                  <?php echo e($region); ?>

                </option>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
              </select>
              <?php $__errorArgs = ['region'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                <div style="font-size:11px;color:var(--p-danger);margin-top:4px"><?php echo e($message); ?></div>
              <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
            </div>

            <div class="">
              <label class="p-form-label">
                Parol
                <?php if(isset($courier)): ?>
                  <span style="color:var(--p-hint);font-size:11px">(o'zgartirish uchun to'ldiring)</span>
                <?php else: ?>
                  <span style="color:var(--p-danger)">*</span>
                <?php endif; ?>
              </label>
              <input type="password" name="password" class="p-form-control"
                     placeholder="<?php echo e(isset($courier) ? 'Yangi parol...' : 'Parol kiriting'); ?>"
                     <?php echo e(isset($courier) ? '' : 'required'); ?> minlength="6"
                     autocomplete="new-password">
            </div>

            <div class="">
              <label class="p-form-label">Balans (UZS)</label>
              <input type="number" name="balance" class="p-form-control"
                     value="<?php echo e(old('balance', $courier->balance ?? 0)); ?>"
                     min="0" step="1">
            </div>

          </div>
        </div>
      </div>
    </div>

    <div class="xl:col-span-4 fade-up">

      
      <div class="p-card mb-3">
        <div class="p-card-header"><div class="p-card-title">Foto</div></div>
        <div style="padding:0 18px 18px">
          <?php if(isset($courier) && $courier->photo): ?>
          <div style="margin-bottom:12px;text-align:center">
            <img src="<?php echo e(Storage::url($courier->photo)); ?>"
                 style="width:72px;height:72px;border-radius:50%;object-fit:cover;
                        border:2px solid var(--p-border)">
          </div>
          <?php endif; ?>
          <label class="p-form-label">Rasm yuklash</label>
          <input type="file" name="photo" class="p-form-control" accept="image/*">
          <div style="font-size:11px;color:var(--p-hint);margin-top:4px">JPG, PNG · max 2MB</div>
        </div>
      </div>

      
      <div class="p-card">
        <div class="p-card-header"><div class="p-card-title">Holat</div></div>
        <div style="padding:0 18px 18px">
          <?php $curStatus = old('status', $courier->status ?? 'pending'); ?>
          <?php $__currentLoopData = [
            ['approved', 'Tasdiqlangan', 'success', 'Buyurtma qabul qila oladi'],
            ['pending',  'Kutilmoqda',  'warning', 'Ko\'rib chiqilmoqda'],
            ['rejected', 'Rad etildi',  'danger',  'Tizimga kirishi bloklangan'],
          ]; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as [$val, $lbl, $clr, $hint]): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
          <label style="display:flex;align-items:center;gap:10px;padding:10px;
                         border-radius:8px;cursor:pointer;margin-bottom:4px;
                         background:<?php echo e(trim((string)$curStatus)===$val ? 'var(--p-'.$clr.'-d)' : 'transparent'); ?>;
                         border:1px solid <?php echo e(trim((string)$curStatus)===$val ? 'var(--p-'.$clr.')' : 'transparent'); ?>;
                         transition:all .15s"
                 onclick="this.parentElement.querySelectorAll('label').forEach(l=>l.style.background='transparent'&&(l.style.border='1px solid transparent'));this.style.background='var(--p-<?php echo e($clr); ?>-d)';this.style.border='1px solid var(--p-<?php echo e($clr); ?>)'">
            <input type="radio" name="status" value="<?php echo e($val); ?>"
                   <?php echo e(trim((string)$curStatus)===$val ? 'checked' : ''); ?>

                   style="accent-color:var(--p-<?php echo e($clr); ?>)">
            <div style="flex:1">
              <div style="font-size:13px;font-weight:500;color:var(--p-text)">
                <span class="s-pill <?php echo e($clr); ?>" style="font-size:10px;margin-right:4px"><?php echo e($lbl); ?></span>
              </div>
              <div style="font-size:11px;color:var(--p-hint);margin-top:1px"><?php echo e($hint); ?></div>
            </div>
          </label>
          <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
          <?php $__errorArgs = ['status'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
            <div style="font-size:11px;color:var(--p-danger);margin-top:4px"><?php echo e($message); ?></div>
          <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
        </div>
      </div>

    </div>

    <div class=" fade-up">
      <div class="flex gap-2">
        <button type="submit" class="btn-p primary">
          <i class="bi bi-check-lg"></i>
          <?php echo e(isset($courier) ? 'Saqlash' : 'Yaratish'); ?>

        </button>
        <a href="<?php echo e(isset($courier) ? route('panel.couriers.show',$courier) : route('panel.couriers.index')); ?>"
           class="btn-p ghost">Bekor qilish</a>
      </div>
    </div>

  </div>
</form>

<?php $__env->stopSection(); ?>
<?php echo $__env->make('panel.layouts.panel', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/abbos/PROJECTS/MY/kitobchi-server/kitobchi/resources/views/panel/couriers/edit.blade.php ENDPATH**/ ?>