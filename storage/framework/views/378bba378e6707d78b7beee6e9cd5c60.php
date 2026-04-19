<?php $__env->startSection('title','Import: Foydalanuvchilar'); ?>
<?php $__env->startSection('page-title','Foydalanuvchilar import'); ?>
<?php $__env->startSection('breadcrumb','Panel / Foydalanuvchilar / Import'); ?>

<?php $__env->startSection('content'); ?>

<?php if (isset($component)) { $__componentOriginal8e8786879b90e52c629eb07e5f5bbd4a = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal8e8786879b90e52c629eb07e5f5bbd4a = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.panel.page-header','data' => ['backHref' => ''.e(route('panel.users.index')).'']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('panel.page-header'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['back-href' => ''.e(route('panel.users.index')).'']); ?>
   <?php $__env->slot('heading', null, []); ?> Import <?php $__env->endSlot(); ?>
   <?php $__env->slot('meta', null, []); ?> Excel yoki CSV fayldan foydalanuvchilar import qilish <?php $__env->endSlot(); ?>
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


<div class="grid grid-cols-1 md:grid-cols-2 gap-3">
  <div class="fade-up d1">
    <div class="p-card">
      <div class="p-card-title mb-1">Fayl yuklash</div>
      <div style="font-size:12px;color:var(--p-hint);margin-bottom:20px">
        Qo'llab-quvvatlanadigan formatlar: .xlsx, .xls, .csv
      </div>

      <form method="POST" action="<?php echo e(route('panel.users.import.post')); ?>" enctype="multipart/form-data">
        <?php echo csrf_field(); ?>
        <div style="border:2px dashed var(--p-border);border-radius:10px;padding:30px;text-align:center;margin-bottom:20px;cursor:pointer"
             onclick="document.getElementById('importFile').click()">
          <i class="bi bi-cloud-upload" style="font-size:36px;color:var(--p-hint);display:block;margin-bottom:8px"></i>
          <div style="font-size:14px;font-weight:500;color:var(--p-text)">Fayl tanlash</div>
          <div style="font-size:12px;color:var(--p-hint);margin-top:4px">yoki bu yerga tashlang</div>
          <div id="fileNameDisplay" style="margin-top:10px;font-size:12px;color:var(--p-accent)"></div>
        </div>
        <input type="file" id="importFile" name="file"
               accept=".xlsx,.xls,.csv" style="display:none"
               onchange="document.getElementById('fileNameDisplay').textContent = this.files[0]?.name || ''">

        <?php $__errorArgs = ['file'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
        <div style="font-size:12px;color:var(--p-danger);margin-bottom:12px"><?php echo e($message); ?></div>
        <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>

        <button type="submit" class="btn-p primary" style="width:100%">
          <i class="bi bi-upload"></i> Import qilish
        </button>
      </form>
    </div>
  </div>

  <div class="fade-up d2">
    <div class="p-card">
      <div class="p-card-title mb-3">Fayl formati</div>
      <div style="font-size:13px;color:var(--p-muted);margin-bottom:14px">
        Excel faylning birinchi qatorida quyidagi sarlavhalar bo'lishi kerak:
      </div>
      <div class="table-responsive kc-twrap">
        <table class="p-table">
          <thead>
            <tr><th>Ustun nomi</th><th>Ma'lumot turi</th><th>Majburiy</th></tr>
          </thead>
          <tbody>
            <?php $__currentLoopData = [
              ['ism','Matn','Ha'],
              ['familiya','Matn','Yo\'q'],
              ['telefon','Matn (+998...)','Ha'],
              ['email','Email','Yo\'q'],
              ['premium','Ha / Yo\'q','Yo\'q'],
              ['tasdiqlangan','Ha / Yo\'q','Yo\'q'],
              ['balans_uzs','Son','Yo\'q'],
            ]; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $col): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <tr>
              <td><code style="font-family:'JetBrains Mono',monospace;font-size:12px;color:var(--p-accent)"><?php echo e($col[0]); ?></code></td>
              <td style="font-size:12px"><?php echo e($col[1]); ?></td>
              <td>
                <?php if($col[2] === 'Ha'): ?>
                  <span class="s-pill danger" style="font-size:10px">Majburiy</span>
                <?php else: ?>
                  <span class="s-pill muted" style="font-size:10px">Ixtiyoriy</span>
                <?php endif; ?>
              </td>
            </tr>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
          </tbody>
        </table>
      </div>

      <div style="margin-top:16px;padding:12px;background:var(--p-elevated);border-radius:8px">
        <div style="font-size:12px;font-weight:500;color:var(--p-text);margin-bottom:6px">
          <i class="bi bi-info-circle mr-1" style="color:var(--p-accent)"></i> Muhim eslatmalar:
        </div>
        <ul style="font-size:12px;color:var(--p-muted);margin:0;padding-left:16px;line-height:1.8">
          <li>Mavjud telefon raqamlar o'tkazib yuboriladi</li>
          <li>Yangi foydalanuvchilarga standart parol: <code style="font-family:'JetBrains Mono',monospace">12345678</code></li>
          <li>Maksimal fayl hajmi: 10MB</li>
        </ul>
      </div>

      <a href="#" class="btn-p ghost" style="width:100%;justify-content:center;margin-top:12px">
        <i class="bi bi-download"></i> Namuna fayl yuklash
      </a>
    </div>
  </div>
</div>

<?php $__env->stopSection(); ?>
<?php echo $__env->make('panel.layouts.panel', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/abbos/PROJECTS/MY/kitobchi-server/kitobchi/resources/views/panel/users/import.blade.php ENDPATH**/ ?>