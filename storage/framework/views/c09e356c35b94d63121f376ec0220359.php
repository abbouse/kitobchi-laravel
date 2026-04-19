<?php $__env->startSection('title', 'Import: Kitoblar'); ?>
<?php $__env->startSection('page-title', 'Kitoblar import'); ?>

<?php $__env->startSection('content'); ?>

<?php if (isset($component)) { $__componentOriginal8e8786879b90e52c629eb07e5f5bbd4a = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal8e8786879b90e52c629eb07e5f5bbd4a = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.panel.page-header','data' => ['backHref' => ''.e(route('panel.books.index')).'']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('panel.page-header'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['back-href' => ''.e(route('panel.books.index')).'']); ?>
   <?php $__env->slot('heading', null, []); ?> Import <?php $__env->endSlot(); ?>
   <?php $__env->slot('meta', null, []); ?> Excel yoki CSV fayldan kitoblar import qilish <?php $__env->endSlot(); ?>
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

  
  <div class="xl:col-span-6 fade-up">
    <div class="p-card">
      <div class="dash-card-head">
        <div class="dash-card-title">Fayl yuklash</div>
        <div class="dash-card-sub">.xlsx, .xls, .csv</div>
      </div>
      <div class="dash-card-body">
        <form method="POST" action="<?php echo e(route('panel.books.import.post')); ?>"
              enctype="multipart/form-data">
          <?php echo csrf_field(); ?>

          <div style="border:2px dashed var(--p-border);border-radius:10px;
                      padding:36px;text-align:center;cursor:pointer;
                      transition:border-color .2s"
               onclick="document.getElementById('importFile').click()"
               onmouseenter="this.style.borderColor='var(--p-accent)'"
               onmouseleave="this.style.borderColor='var(--p-border)'">
            <i class="bi bi-cloud-upload"
               style="font-size:40px;color:var(--p-hint);display:block;margin-bottom:10px"></i>
            <div style="font-size:14px;font-weight:500;color:var(--p-text)">Fayl tanlash</div>
            <div style="font-size:12px;color:var(--p-hint);margin-top:4px">yoki bu yerga tashlang</div>
            <div id="fileNameDisplay"
                 style="margin-top:10px;font-size:13px;font-weight:500;color:var(--p-accent)"></div>
          </div>

          <input type="file" id="importFile" name="file"
                 accept=".xlsx,.xls,.csv" style="display:none"
                 onchange="document.getElementById('fileNameDisplay').textContent = this.files[0]?.name || ''">

          <?php $__errorArgs = ['file'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
          <div style="font-size:12px;color:var(--p-danger);margin-top:8px"><?php echo e($message); ?></div>
          <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>

          <button type="submit" class="btn-p mt-3" style="width:100%">
            <i class="bi bi-upload"></i> Import qilish
          </button>
        </form>

        
        <?php if(session('success')): ?>
        <div class="p-alert success mt-3">
          <i class="bi bi-check-circle-fill"></i> <?php echo e(session('success')); ?>

        </div>
        <?php endif; ?>
        <?php if(session('error')): ?>
        <div class="p-alert danger mt-3">
          <i class="bi bi-exclamation-triangle-fill"></i> <?php echo e(session('error')); ?>

        </div>
        <?php endif; ?>
      </div>
    </div>
  </div>

  
  <div class="xl:col-span-6 fade-up">
    <div class="p-card">
      <div class="dash-card-head">
        <div class="dash-card-title">Fayl formati</div>
        <div class="dash-card-sub">Birinchi qatordagi sarlavhalar</div>
      </div>
      <div class="dash-card-body">
        <div class="table-responsive kc-twrap">
          <table class="p-table">
            <thead>
              <tr>
                <th>Ustun nomi</th>
                <th>Ma'lumot turi</th>
                <th>Majburiy</th>
              </tr>
            </thead>
            <tbody>
              <?php $__currentLoopData = [
                ['name',        'Kitob nomi',      'string',  true],
                ['author',      'Muallif',         'string',  true],
                ['category_id', 'Kategoriya ID',   'integer', true],
                ['price',       'Narx (UZS)',       'integer', true],
                ['description', 'Tavsif',           'text',    true],
                ['lang',        'Til (Oʻzbek...)',  'string',  false],
                ['year',        'Nashr yili',       'integer', false],
                ['pages',       'Sahifalar soni',   'integer', false],
              ]; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as [$col, $label, $type, $required]): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
              <tr>
                <td>
                  <code style="font-family:'JetBrains Mono',monospace;font-size:12px;
                               background:var(--p-elevated);padding:2px 8px;
                               border-radius:4px;color:var(--p-accent)"><?php echo e($col); ?></code>
                </td>
                <td style="font-size:12px;color:var(--p-muted)"><?php echo e($label); ?></td>
                <td>
                  <?php if($required): ?>
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

        
        <div style="margin-top:16px;padding:12px 14px;background:var(--p-warning-d);
                    border-radius:8px;border:1px solid rgba(245,166,35,.2)">
          <div style="font-size:12px;color:var(--p-warning);display:flex;align-items:flex-start;gap:8px">
            <i class="bi bi-info-circle-fill" style="margin-top:1px;flex-shrink:0"></i>
            <div>
              <strong>Eslatma:</strong> Import qilingan kitoblar avtomatik ravishda
              <code style="background:rgba(245,166,35,.2);padding:1px 5px;border-radius:3px">is_approved = 0</code>
              (kutilmoqda) holatida saqlanadi va moderatsiyadan o'tkazilishi kerak.
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

</div>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('panel.layouts.panel', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/abbos/PROJECTS/MY/kitobchi-server/kitobchi/resources/views/panel/books/import.blade.php ENDPATH**/ ?>