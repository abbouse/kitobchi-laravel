<?php $__env->startSection('title', 'Shikoyatlar'); ?>
<?php $__env->startSection('page-title', 'Shikoyatlar'); ?>

<?php $__env->startSection('content'); ?>

<?php if (isset($component)) { $__componentOriginal0c1345684b2d774f43a544669f5684b0 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal0c1345684b2d774f43a544669f5684b0 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.a122.page-header','data' => []] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('a122.page-header'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
   <?php $__env->slot('heading', null, []); ?> Shikoyatlar <?php $__env->endSlot(); ?>
   <?php $__env->slot('meta', null, []); ?> Foydalanuvchilar yuborgan shikoyatlar <?php $__env->endSlot(); ?>
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

<div class="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-4 mb-4">
  <?php $__currentLoopData = [
    [$counts['pending'] ?? 0, 'Kutilmoqda', 'warning', 'bi-hourglass-split'],
    [$counts['reviewed'] ?? 0, "Ko'rilgan", 'success', 'bi-check2-circle'],
    [$counts['dismissed'] ?? 0, 'Rad etilgan', 'muted', 'bi-slash-circle'],
    [($counts['pending'] ?? 0) + ($counts['reviewed'] ?? 0) + ($counts['dismissed'] ?? 0), 'Jami shikoyat', 'accent', 'bi-flag'],
  ]; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as [$value, $label, $tone, $icon]): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
    <div class="p-card flex items-center gap-3 fade-up" style="padding:14px">
      <div style="width:40px;height:40px;border-radius:12px;display:flex;align-items:center;justify-content:center;background:var(--p-<?php echo e($tone); ?>-d,var(--p-elevated));color:var(--p-<?php echo e($tone); ?>)">
        <i class="bi <?php echo e($icon); ?>"></i>
      </div>
      <div>
        <div style="font-size:22px;font-weight:700;color:var(--p-text)"><?php echo e($value); ?></div>
        <div style="font-size:11px;letter-spacing:.08em;text-transform:uppercase;color:var(--p-hint)"><?php echo e($label); ?></div>
      </div>
    </div>
  <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
</div>

<div class="a122-index-header">
  <div>
    <div class="a122-index-header__title">Shikoyatlar ro'yxati</div>
    <div class="a122-index-header__meta"><?php echo e($reports->total()); ?> ta shikoyat ko'rinmoqda</div>
  </div>
</div>


<div class="p-card fade-up">
  <div class="table-responsive kc-twrap">
    <table class="p-table" data-index-grid>
      <thead>
        <tr>
          <th>#</th>
          <th>Shikoyatchi</th>
          <th>Tur · ID</th>
          <th>Sabab</th>
          <th>Izoh</th>
          <th>Holat</th>
          <th>Vaqt</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        <?php $__empty_1 = true; $__currentLoopData = $reports; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $r): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
        <?php
          $stCls = match($r->status){
            'reviewed'=>'success','dismissed'=>'muted',default=>'warning'
          };
          $stLbl = match($r->status){
            'reviewed'=>'Ko\'rildi','dismissed'=>'Rad',default=>'Yangi'
          };
        ?>
        <tr>
          <td style="font-family:'JetBrains Mono',monospace;color:var(--p-accent)">#<?php echo e($r->id); ?></td>

          <td>
            <?php if($r->user): ?>
            <div class="flex items-center gap-2">
              <div style="width:28px;height:28px;border-radius:50%;overflow:hidden;flex-shrink:0;
                          background:linear-gradient(135deg,var(--p-accent),#7c5cfc);
                          display:flex;align-items:center;justify-content:center;
                          font-size:11px;font-weight:700;color:#fff">
                <?php if($r->user->avatar): ?>
                  <img src="<?php echo e($r->user->avatar); ?>" style="width:100%;height:100%;object-fit:cover">
                <?php else: ?>
                  <?php echo e(strtoupper(substr($r->user->name,0,1))); ?>

                <?php endif; ?>
              </div>
              <div>
                <a href="<?php echo e(route('admin.users.show',$r->user_id)); ?>"
                   style="font-size:12.5px;font-weight:500;color:var(--p-text);text-decoration:none">
                  <?php echo e($r->user->name); ?> <?php echo e($r->user->lastname); ?>

                </a>
                <div style="font-size:10px;color:var(--p-hint);font-family:'JetBrains Mono',monospace">
                  <?php echo e($r->user->phone_number); ?>

                </div>
              </div>
            </div>
            <?php else: ?>
              <span style="color:var(--p-hint);font-size:12px">#<?php echo e($r->user_id); ?></span>
            <?php endif; ?>
          </td>

          <td>
            <span class="s-pill <?php echo e(match($r->reportable_type){
              'conversation_message'=>'info','book_club'=>'accent',default=>'muted'
            }); ?>" style="font-size:10px">
              <?php echo e(match($r->reportable_type){
                'conversation_message'=>'💬 Xabar',
                'book_club'=>'📚 Book Club',
                default=>$r->reportable_type
              }); ?>

            </span>
            <div style="font-size:10px;color:var(--p-hint);margin-top:2px">
              #<?php echo e($r->reportable_id); ?>

            </div>
          </td>

          <td style="max-width:150px">
            <div style="font-size:12.5px;color:var(--p-text);font-weight:500">
              <?php echo e($r->reason); ?>

            </div>
          </td>

          <td style="max-width:140px">
            <?php if($r->comment): ?>
            <div style="font-size:12px;color:var(--p-muted);white-space:nowrap;
                        overflow:hidden;text-overflow:ellipsis;max-width:130px"
                 title="<?php echo e($r->comment); ?>">
              <?php echo e($r->comment); ?>

            </div>
            <?php else: ?>
              <span style="color:var(--p-hint);font-size:11px">—</span>
            <?php endif; ?>
          </td>

          <td><span class="s-pill <?php echo e($stCls); ?>" style="font-size:10px"><?php echo e($stLbl); ?></span></td>

          <td style="font-size:11px;color:var(--p-hint);white-space:nowrap;
                     font-family:'JetBrains Mono',monospace">
            <?php echo e(\Carbon\Carbon::parse($r->created_at)->format('d.m.Y H:i')); ?>

          </td>

          <td>
            <div class="flex gap-1 items-center">
              <a href="<?php echo e(route('admin.complaints.show',$r)); ?>" class="btn-p ghost sm">
                <i class="bi bi-eye"></i>
              </a>

              <?php if($r->status === 'pending'): ?>
              
              <form method="POST" action="<?php echo e(route('admin.complaints.status',$r)); ?>">
                <?php echo csrf_field(); ?> <?php echo method_field('PATCH'); ?>
                <input type="hidden" name="status" value="reviewed">
                <button class="btn-p success sm" title="Ko'rildi">
                  <i class="bi bi-check-lg"></i>
                </button>
              </form>
              
              <form method="POST" action="<?php echo e(route('admin.complaints.status',$r)); ?>">
                <?php echo csrf_field(); ?> <?php echo method_field('PATCH'); ?>
                <input type="hidden" name="status" value="dismissed">
                <button class="btn-p ghost sm" title="Rad etish" style="color:var(--p-muted)">
                  <i class="bi bi-x-lg"></i>
                </button>
              </form>
              <?php endif; ?>

              
              <?php if($r->status !== 'pending'): ?>
              <form method="POST" action="<?php echo e(route('admin.complaints.status',$r)); ?>">
                <?php echo csrf_field(); ?> <?php echo method_field('PATCH'); ?>
                <input type="hidden" name="status" value="pending">
                <button class="btn-p ghost sm" title="Qayta ochish" style="color:var(--p-warning)">
                  <i class="bi bi-arrow-counterclockwise"></i>
                </button>
              </form>
              <?php endif; ?>

            </div>
          </td>
        </tr>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
        <tr>
          <td colspan="8" style="text-align:center;padding:40px;color:var(--p-hint)">
            <i class="bi bi-flag" style="font-size:28px;display:block;margin-bottom:8px"></i>
            Shikoyatlar yo'q
          </td>
        </tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
  <?php if($reports->hasPages()): ?>
  <div class="flex items-center justify-between px-3 py-2"
       style="border-top:1px solid var(--p-border)">
    <div style="font-size:12px;color:var(--p-hint)">
      <?php echo e($reports->firstItem()); ?>–<?php echo e($reports->lastItem()); ?> / <?php echo e($reports->total()); ?>

    </div>
    <?php echo e($reports->links('a122.partials.pagination')); ?>

  </div>
  <?php endif; ?>
</div>

<?php $__env->stopSection(); ?>

<?php echo $__env->make('a122.layouts.admin', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/abbos/PROJECTS/MY/kitobchi-server/kitobchi-laravel/resources/views/a122/complaints/index.blade.php ENDPATH**/ ?>