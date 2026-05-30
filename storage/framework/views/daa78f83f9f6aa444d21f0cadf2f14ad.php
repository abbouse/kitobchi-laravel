<?php $__env->startSection('title', 'Tahrirlash: '.$apiClient->name); ?>
<?php $__env->startSection('page-title', 'API mijoz tahrirlash'); ?>

<?php $__env->startSection('content'); ?>
<div class="space-y-4">
  <?php if (isset($component)) { $__componentOriginal0c1345684b2d774f43a544669f5684b0 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal0c1345684b2d774f43a544669f5684b0 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.a122.page-header','data' => ['backHref' => ''.e(route('admin.api-clients.index')).'']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('a122.page-header'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['back-href' => ''.e(route('admin.api-clients.index')).'']); ?>
     <?php $__env->slot('heading', null, []); ?> <?php echo e($apiClient->name); ?> <?php $__env->endSlot(); ?>
     <?php $__env->slot('meta', null, []); ?> Mijoz holati, huquqlari va secret boshqaruvi shu sahifada. <?php $__env->endSlot(); ?>
     <?php $__env->slot('actions', null, []); ?> 
      <a href="<?php echo e(route('admin.api-clients.logs', ['client_id' => $apiClient->id])); ?>" class="btn-p ghost">To‘liq audit</a>
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

  <form method="POST" action="<?php echo e(route('admin.api-clients.update', $apiClient)); ?>">
    <?php echo csrf_field(); ?>
    <?php echo method_field('PUT'); ?>
    <?php echo $__env->make('a122.api-clients._form', compact('apiClient'), array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
  </form>

  <section class="card-panel fade-up">
    <div class="dash-card-head">
      <div class="dash-card-title">So‘nggi audit loglar</div>
    </div>

    <div class="table-responsive kc-twrap">
      <table class="table data-table align-middle mb-0">
        <thead>
          <tr>
            <th>Vaqt</th>
            <th>Method</th>
            <th>Path</th>
            <th>Status</th>
            <th>Davomiylik</th>
            <th>IP</th>
          </tr>
        </thead>
        <tbody>
          <?php $__empty_1 = true; $__currentLoopData = $recentLogs; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $log): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
            <tr>
              <td style="white-space:nowrap;font-family:'JetBrains Mono',monospace;font-size:12px;color:var(--p-hint)">
                <?php echo e($log->created_at?->format('d.m.Y H:i:s')); ?>

              </td>
              <td><span class="s-pill accent"><?php echo e($log->method); ?></span></td>
              <td>
                <code style="font-size:12px"><?php echo e($log->path); ?></code>
              </td>
              <td>
                <span class="s-pill <?php echo e($log->status_code >= 500 ? 'danger' : ($log->status_code >= 400 ? 'warning' : 'success')); ?>">
                  <?php echo e($log->status_code); ?>

                </span>
              </td>
              <td style="white-space:nowrap"><?php echo e($log->duration_ms); ?> ms</td>
              <td style="white-space:nowrap"><?php echo e($log->ip_address ?: '—'); ?></td>
            </tr>
          <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
            <tr>
              <td colspan="6" style="text-align:center;padding:32px;color:var(--p-hint)">
                Hali request loglar yo‘q
              </td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </section>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('a122.layouts.admin', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/abbos/PROJECTS/MY/kitobchi-server/kitobchi-laravel/resources/views/a122/api-clients/edit.blade.php ENDPATH**/ ?>