<?php $__env->startSection('title', 'Book Club'); ?>
<?php $__env->startSection('page-title', 'Book Club postlari'); ?>

<?php $__env->startSection('content'); ?>
<div x-data="{
  view: localStorage.getItem('a122-book-club-view') || 'grid',
  setView(next) {
    this.view = next;
    localStorage.setItem('a122-book-club-view', next);
  }
}">

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
   <?php $__env->slot('heading', null, []); ?> Book Club <?php $__env->endSlot(); ?>
   <?php $__env->slot('meta', null, []); ?> Foydalanuvchilar postlari va repostlari <?php $__env->endSlot(); ?>
   <?php $__env->slot('actions', null, []); ?> 
    <?php
      try {
        $ugcPending = \App\Models\BookClubComment::query()
          ->where('kangaroo_ugc_status', 'pending_admin')
          ->whereNull('parent_id')
          ->count()
          + \App\Models\BookClub::query()
            ->where('is_deleted', false)
            ->where('kangaroo_post_ugc_status', 'pending_admin')
            ->count();
      } catch (\Exception $e) {
        $ugcPending = 0;
      }
    ?>
    <a href="<?php echo e(route('admin.book-club.moderation-queue')); ?>" class="btn-p ghost">
      <i class="bi bi-shield-exclamation"></i> UGC navbati
      <?php if($ugcPending > 0): ?>
        <span class="tab-badge" style="margin-left:6px"><?php echo e($ugcPending); ?></span>
      <?php endif; ?>
    </a>
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

<div class="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-4 mb-4 fade-up">
  <?php $__currentLoopData = [
    [$counts['all'] ?? 0, 'Jami postlar', 'accent', 'bi-chat-square-text'],
    [$counts['posts'] ?? 0, 'Asl postlar', 'info', 'bi-pencil-square'],
    [$counts['reposts'] ?? 0, 'Repostlar', 'warning', 'bi-arrow-repeat'],
    [$ugcPending ?? 0, 'UGC navbat', 'success', 'bi-shield-exclamation'],
  ]; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as [$value, $label, $tone, $icon]): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
    <div class="p-card flex items-center gap-3" style="padding:14px">
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

<div class="a122-index-header mb-3 fade-up">
  <div>
    <div class="a122-index-header__title">Book Club oqimi</div>
    <div class="a122-index-header__meta"><?php echo e($posts->total()); ?> ta post ko'rinmoqda</div>
  </div>
  <div class="flex flex-wrap items-center justify-between gap-3">
    <div class="index-table-segment" role="tablist" aria-label="Ko‘rinish">
      <button type="button" @click="setView('list')" :class="{ 'is-active': view === 'list' }">
        <i class="bi bi-list-ul"></i>
        <span>List</span>
      </button>
      <button type="button" @click="setView('grid')" :class="{ 'is-active': view === 'grid' }">
        <i class="bi bi-grid-3x3-gap"></i>
        <span>Grid</span>
      </button>
    </div>
  </div>
</div>

<?php $__empty_1 = true; $__currentLoopData = $posts; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $post): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
  <?php if($loop->first): ?>
  <div class="grid gap-3"
       :class="view === 'grid' ? 'grid-cols-1 lg:grid-cols-2 2xl:grid-cols-3' : 'grid-cols-1'">
  <?php endif; ?>

  <div class="fade-up">
    <?php echo $__env->make('a122.book-club._post-card', ['post' => $post, 'showUser' => true], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
  </div>

  <?php if($loop->last): ?></div><?php endif; ?>
<?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
<div class="p-card fade-up" style="text-align:center;padding:50px;color:var(--p-hint)">
  <i class="bi bi-chat-square-text" style="font-size:36px;display:block;margin-bottom:12px"></i>
  Postlar topilmadi
</div>
<?php endif; ?>

<div class="mt-3">
  <?php echo e($posts->links('a122.partials.pagination')); ?>

</div>

</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('a122.layouts.admin', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/abbos/PROJECTS/MY/kitobchi-server/kitobchi/resources/views/a122/book-club/index.blade.php ENDPATH**/ ?>