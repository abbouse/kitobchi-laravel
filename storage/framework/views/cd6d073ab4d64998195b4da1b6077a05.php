<?php $__env->startSection('title', 'Shikoyat #'.$report->id); ?>
<?php $__env->startSection('page-title', 'Shikoyat #'.$report->id); ?>

<?php $__env->startSection('content'); ?>

<?php
  $stCls = match($report->status){'reviewed'=>'success','dismissed'=>'muted',default=>'warning'};
  $stLbl = match($report->status){'reviewed'=>'Ko\'rib chiqilgan','dismissed'=>'Rad etilgan',default=>'Kutilmoqda'};
?>

<?php if (isset($component)) { $__componentOriginal0c1345684b2d774f43a544669f5684b0 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal0c1345684b2d774f43a544669f5684b0 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.a122.page-header','data' => ['backHref' => ''.e(route('admin.complaints.index')).'']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('a122.page-header'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['back-href' => ''.e(route('admin.complaints.index')).'']); ?>
   <?php $__env->slot('heading', null, []); ?> Shikoyat #<?php echo e($report->id); ?> <?php $__env->endSlot(); ?>
   <?php $__env->slot('meta', null, []); ?> 
    <p class="page-sub flex flex-wrap items-center gap-2">
      <?php echo e(\Carbon\Carbon::parse($report->created_at)->format('d.m.Y H:i')); ?>

      <span class="s-pill <?php echo e($stCls); ?>" style="font-size:11px"><?php echo e($stLbl); ?></span>
    </p>
   <?php $__env->endSlot(); ?>
   <?php $__env->slot('actions', null, []); ?> 
    <div class="flex flex-wrap gap-2">
      <?php if($report->status === 'pending'): ?>
        <form method="POST" action="<?php echo e(route('admin.complaints.status',$report)); ?>">
          <?php echo csrf_field(); ?> <?php echo method_field('PATCH'); ?>
          <input type="hidden" name="status" value="reviewed">
          <button class="btn-p success">
            <i class="bi bi-check-lg"></i> Ko'rildi
          </button>
        </form>
        <form method="POST" action="<?php echo e(route('admin.complaints.status',$report)); ?>">
          <?php echo csrf_field(); ?> <?php echo method_field('PATCH'); ?>
          <input type="hidden" name="status" value="dismissed">
          <button class="btn-p ghost">
            <i class="bi bi-x-lg"></i> Rad etish
          </button>
        </form>
      <?php else: ?>
        <form method="POST" action="<?php echo e(route('admin.complaints.status',$report)); ?>">
          <?php echo csrf_field(); ?> <?php echo method_field('PATCH'); ?>
          <input type="hidden" name="status" value="pending">
          <button class="btn-p ghost" style="color:var(--p-warning)">
            <i class="bi bi-arrow-counterclockwise"></i> Qayta ochish
          </button>
        </form>
      <?php endif; ?>

      <form method="POST" action="<?php echo e(route('admin.complaints.destroy',$report)); ?>"
            onsubmit="return confirm('O\'chirilsinmi?')">
        <?php echo csrf_field(); ?> <?php echo method_field('DELETE'); ?>
        <button class="btn-p danger ghost"><i class="bi bi-trash"></i></button>
      </form>
    </div>
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

<section class="a122-section mb-4">
  <div class="a122-section-body">
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
      <div class="kpi-soft">
        <div class="metric-label">Holat</div>
        <div class="metric-value text-xl"><?php echo e($stLbl); ?></div>
        <div class="metric-meta">Moderatsiya bosqichi</div>
      </div>
      <div class="kpi-soft">
        <div class="metric-label">Tur</div>
        <div class="metric-value text-xl"><?php echo e($report->reportable_type === 'conversation_message' ? 'Chat' : ($report->reportable_type === 'book_club' ? 'Book Club' : 'Other')); ?></div>
        <div class="metric-meta">Report category</div>
      </div>
      <div class="kpi-soft">
        <div class="metric-label">Qo‘shimcha reportlar</div>
        <div class="metric-value text-xl"><?php echo e(number_format($otherReports->count())); ?></div>
        <div class="metric-meta">Shu obyekt bo‘yicha</div>
      </div>
      <div class="kpi-soft">
        <div class="metric-label">Yaratilgan</div>
        <div class="metric-value text-xl"><?php echo e(\Carbon\Carbon::parse($report->created_at)->format('d.m')); ?></div>
        <div class="metric-meta"><?php echo e(\Carbon\Carbon::parse($report->created_at)->format('H:i')); ?></div>
      </div>
    </div>
  </div>
</section>

<div class="grid grid-cols-1 md:grid-cols-2 gap-3">

  <div class="xl:col-span-4">

    
    <div class="card-panel mb-3 fade-up">
      <div class="card-panel-header"><div class="card-panel-title">Shikoyatchi</div></div>
      <div style="padding:14px 18px">
        <?php if($report->user): ?>
        <div class="flex items-center gap-3 mb-3">
          <div style="width:46px;height:46px;border-radius:50%;overflow:hidden;flex-shrink:0;
                      background:linear-gradient(135deg,var(--p-accent),#7c5cfc);
                      display:flex;align-items:center;justify-content:center;
                      font-size:18px;font-weight:700;color:#fff">
            <?php if($report->user->avatar): ?>
              <img src="<?php echo e($report->user->avatar); ?>" style="width:100%;height:100%;object-fit:cover">
            <?php else: ?>
              <?php echo e(strtoupper(substr($report->user->name,0,1))); ?>

            <?php endif; ?>
          </div>
          <div>
            <div style="font-size:14px;font-weight:600;color:var(--p-text)">
              <?php echo e($report->user->name); ?> <?php echo e($report->user->lastname); ?>

            </div>
            <div style="font-size:12px;color:var(--p-hint);font-family:'JetBrains Mono',monospace">
              <?php echo e($report->user->phone_number); ?>

            </div>
          </div>
        </div>
        <a href="<?php echo e(route('admin.users.show',$report->user_id)); ?>"
           class="btn-p ghost" style="width:100%;justify-content:center;font-size:12px">
          <i class="bi bi-person"></i> Profil
        </a>
        <?php else: ?>
          <div style="color:var(--p-hint)">User #<?php echo e($report->user_id); ?></div>
        <?php endif; ?>
      </div>
    </div>

    
    <div class="card-panel mb-3 fade-up">
      <div class="card-panel-header"><div class="card-panel-title">Tafsilotlar</div></div>
      <div style="padding:0 18px 14px">
        <?php $__currentLoopData = [
          ['Tur', match($report->reportable_type){
            'conversation_message'=>'💬 Xabar',
            'book_club'=>'📚 Book Club',
            default=>$report->reportable_type
          }],
          ['Obyekt ID', '#'.$report->reportable_id],
          ['Holat', $stLbl],
        ]; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as [$k,$v]): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <div style="display:flex;justify-content:space-between;align-items:center;
                    padding:9px 0;border-bottom:1px solid var(--p-border)">
          <span style="font-size:12px;color:var(--p-hint)"><?php echo e($k); ?></span>
          <span style="font-size:12px;font-weight:500;color:var(--p-text)"><?php echo e($v); ?></span>
        </div>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
      </div>
    </div>

    
    <?php if($otherReports->count()): ?>
    <div class="card-panel fade-up" style="border-color:rgba(245,166,35,.3)">
      <div class="card-panel-header">
        <div class="card-panel-title" style="color:var(--p-warning)">
          <i class="bi bi-exclamation-triangle-fill mr-1"></i>Boshqa shikoyatlar
        </div>
        <span class="s-pill warning" style="font-size:10px"><?php echo e($otherReports->count()); ?></span>
      </div>
      <div style="padding:0 18px 14px">
        <?php $__currentLoopData = $otherReports; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $or): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <div style="padding:8px 0;border-bottom:1px solid var(--p-border)">
          <div class="flex items-center justify-between">
            <span style="font-size:12px;color:var(--p-text)"><?php echo e($or->reason); ?></span>
            <a href="<?php echo e(route('admin.complaints.show',$or)); ?>"
               style="font-size:11px;color:var(--p-accent)">Ko'rish</a>
          </div>
          <div style="font-size:10px;color:var(--p-hint)">
            <?php echo e(\Carbon\Carbon::parse($or->created_at)->format('d.m.Y')); ?>

            · <span class="s-pill <?php echo e(match($or->status){'reviewed'=>'success','dismissed'=>'muted',default=>'warning'}); ?>"
                   style="font-size:9px"><?php echo e($or->status); ?></span>
          </div>
        </div>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
      </div>
    </div>
    <?php endif; ?>

  </div>

  <div class="xl:col-span-8">

    
    <div class="card-panel mb-3 fade-up">
      <div class="card-panel-header"><div class="card-panel-title">Shikoyat matni</div></div>
      <div style="padding:16px 18px">
        <div style="margin-bottom:14px">
          <div style="font-size:10px;color:var(--p-hint);text-transform:uppercase;
                      letter-spacing:.07em;margin-bottom:6px">Sabab</div>
          <div style="font-size:15px;font-weight:600;color:var(--p-text)">
            <?php echo e($report->reason); ?>

          </div>
        </div>
        <?php if($report->comment): ?>
        <div>
          <div style="font-size:10px;color:var(--p-hint);text-transform:uppercase;
                      letter-spacing:.07em;margin-bottom:6px">Izoh</div>
          <div style="font-size:13px;color:var(--p-muted);line-height:1.7;
                      background:var(--p-elevated);padding:13px 15px;
                      border-radius:10px;border:1px solid var(--p-border)">
            <?php echo e($report->comment); ?>

          </div>
        </div>
        <?php endif; ?>
      </div>
    </div>

    
    <?php if($reportable): ?>
    <div class="card-panel fade-up" style="border-color:rgba(255,92,106,.2)">
      <div class="card-panel-header">
        <div class="card-panel-title" style="color:var(--p-danger)">
          <i class="bi bi-flag-fill mr-1"></i>Shikoyat qilingan kontent
        </div>
        <span class="s-pill danger" style="font-size:10px">
          #<?php echo e($report->reportable_id); ?>

        </span>
      </div>
      <div style="padding:14px 18px">

        <?php if($report->reportable_type === 'conversation_message'): ?>
        <div style="background:var(--p-elevated);border-radius:10px;padding:14px;
                    border:1px solid var(--p-border)">
          <?php if($reportable->message ?? null): ?>
          <div style="font-size:13px;color:var(--p-text);line-height:1.7;margin-bottom:8px">
            <?php echo e($reportable->message); ?>

          </div>
          <?php endif; ?>
          <?php if($reportable->image ?? null): ?>
          <img src="<?php echo e($reportable->image); ?>"
               style="max-width:200px;border-radius:8px;display:block;margin-bottom:8px">
          <?php endif; ?>
          <div style="font-size:10px;color:var(--p-hint);font-family:'JetBrains Mono',monospace">
            User #<?php echo e($reportable->user_id ?? $reportable->sender_id); ?>

            · <?php echo e(\Carbon\Carbon::parse($reportable->created_at)->format('d.m.Y H:i')); ?>

          </div>
        </div>
        <?php if($reportable->conversation_id ?? null): ?>
        <a href="<?php echo e(route('admin.chats.show',$reportable->conversation_id)); ?>"
           class="btn-p ghost sm mt-2">
          <i class="bi bi-chat-dots"></i> Suhbatni ko'rish
        </a>
        <?php endif; ?>

        <?php elseif($report->reportable_type === 'book_club'): ?>
        <div style="background:var(--p-elevated);border-radius:10px;padding:14px;
                    border:1px solid var(--p-border)">
          <?php if($reportable->text ?? null): ?>
          <div style="font-size:13px;color:var(--p-text);line-height:1.7;margin-bottom:8px">
            <?php echo e($reportable->text); ?>

          </div>
          <?php endif; ?>
          <div style="font-size:10px;color:var(--p-hint);font-family:'JetBrains Mono',monospace">
            Post #<?php echo e($reportable->id); ?>

            · User #<?php echo e($reportable->user_id); ?>

            · <?php echo e(\Carbon\Carbon::parse($reportable->created_at)->format('d.m.Y H:i')); ?>

          </div>
        </div>
        <a href="<?php echo e(route('admin.book-club.show',$reportable->id)); ?>"
           class="btn-p ghost sm mt-2">
          <i class="bi bi-chat-quote"></i> Postni ko'rish
        </a>
        <?php endif; ?>

      </div>
    </div>

    <?php else: ?>
    <div class="card-panel fade-up">
      <div style="padding:30px;text-align:center;color:var(--p-hint)">
        <i class="bi bi-question-circle" style="font-size:28px;display:block;margin-bottom:8px"></i>
        Kontent topilmadi (o'chirilgan bo'lishi mumkin)
      </div>
    </div>
    <?php endif; ?>

  </div>
</div>

<?php $__env->stopSection(); ?>

<?php echo $__env->make('a122.layouts.admin', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/abbos/PROJECTS/MY/kitobchi-server/kitobchi-laravel/resources/views/a122/complaints/show.blade.php ENDPATH**/ ?>