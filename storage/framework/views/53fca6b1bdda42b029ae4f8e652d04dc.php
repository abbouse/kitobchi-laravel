<?php $__env->startSection('title', 'Siyosatlar'); ?>
<?php $__env->startSection('page-title', 'Siyosatlar'); ?>

<?php $__env->startPush('styles'); ?>
<style>
.legal-toolbar {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 1rem;
  margin-bottom: 1rem;
  flex-wrap: wrap;
}
.legal-toolbar__title {
  font-size: 1.1rem;
  font-weight: 900;
  color: var(--p-text);
}
.legal-toolbar__meta {
  margin-top: 0.22rem;
  color: var(--p-hint);
  font-size: 0.78rem;
}
.legal-stats {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 1rem;
  margin-bottom: 1rem;
}
.legal-stat {
  position: relative;
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 1rem;
  padding: 1.15rem;
  border-radius: 1.35rem;
  border: 1px solid var(--p-border);
  background:
    linear-gradient(180deg, color-mix(in srgb, var(--p-surface-strong) 94%, transparent), color-mix(in srgb, var(--p-surface) 92%, transparent));
  box-shadow: var(--p-shadow);
  overflow: hidden;
}
.legal-stat::after {
  content: "";
  position: absolute;
  inset: auto auto 0 0;
  width: 100%;
  height: 3px;
  background: linear-gradient(90deg, color-mix(in srgb, var(--p-accent) 78%, transparent), transparent);
  opacity: 0.75;
}
.legal-stat__value {
  font-size: 1.8rem;
  line-height: 1;
  font-weight: 900;
  color: var(--p-text);
  font-family: 'JetBrains Mono', monospace;
}
.legal-stat__label {
  margin-top: 0.28rem;
  color: var(--p-hint);
  font-size: 0.7rem;
  font-weight: 800;
  letter-spacing: 0.12em;
  text-transform: uppercase;
}
.legal-stat__icon {
  width: 3rem;
  height: 3rem;
  border-radius: 1.05rem;
  flex-shrink: 0;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  font-size: 1.05rem;
  border: 1px solid var(--p-border);
}
.legal-shell {
  overflow: hidden;
  border-radius: 1.45rem;
  border: 1px solid var(--p-border);
  background: var(--p-surface);
  box-shadow: var(--p-shadow);
}
.legal-shell__head {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 1rem;
  padding: 1rem 1.15rem;
  border-bottom: 1px solid var(--p-border);
  background: linear-gradient(180deg, color-mix(in srgb, var(--p-elevated) 78%, transparent), transparent);
}
.legal-shell__title {
  font-size: 0.98rem;
  font-weight: 900;
  color: var(--p-text);
}
.legal-shell__sub {
  margin-top: 0.22rem;
  color: var(--p-hint);
  font-size: 0.76rem;
}
.legal-badge {
  display: inline-flex;
  align-items: center;
  gap: 0.45rem;
  min-height: 2rem;
  padding: 0.3rem 0.78rem;
  border-radius: 999px;
  border: 1px solid var(--p-border);
  background: var(--p-elevated);
  color: var(--p-muted);
  font-size: 0.75rem;
  font-weight: 800;
}
.legal-badge strong {
  color: var(--p-text);
  font-family: 'JetBrains Mono', monospace;
}
.legal-row:hover {
  background: var(--p-hover);
}
.legal-grip {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 2rem;
  height: 2rem;
  border-radius: 0.8rem;
  color: var(--p-hint);
  background: transparent;
}
.legal-title {
  min-width: 0;
}
.legal-title__text {
  font-size: 0.86rem;
  font-weight: 800;
  color: var(--p-text);
}
.legal-title__meta {
  margin-top: 0.24rem;
  color: var(--p-hint);
  font-size: 0.72rem;
}
.legal-slug {
  display: inline-flex;
  align-items: center;
  min-height: 1.9rem;
  padding: 0 0.7rem;
  border-radius: 999px;
  background: var(--p-elevated);
  border: 1px solid var(--p-border);
  color: var(--p-muted);
  font-size: 0.74rem;
  font-family: 'JetBrains Mono', monospace;
}
.legal-preview {
  max-width: 20rem;
  color: var(--p-hint);
  font-size: 0.76rem;
  line-height: 1.55;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}
.legal-status-btn {
  min-width: 7rem;
}
.legal-modal .modal-content {
  border-radius: 1.5rem !important;
  overflow: hidden;
}
.legal-modal .modal-dialog {
  width: min(96vw, 1600px);
  max-width: none;
  margin: 1rem auto;
}
.legal-modal .modal-header {
  padding: 1rem 1.1rem;
  border-bottom: 1px solid var(--p-border);
  background: linear-gradient(180deg, color-mix(in srgb, var(--p-elevated) 84%, transparent), transparent);
}
.legal-modal__title {
  font-size: 1rem;
  font-weight: 900;
  color: var(--p-text);
}
.legal-modal .modal-body {
  padding: 1rem;
}
.legal-modal-grid {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}
.legal-main,
.legal-side {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}
.legal-side {
  width: 100%;
}
.legal-side .legal-panel {
  width: 100%;
}
.legal-panel {
  border: 1px solid var(--p-border);
  border-radius: 1.2rem;
  background: var(--p-surface);
  overflow: hidden;
}
.legal-panel__head {
  padding: 0.85rem 1rem;
  border-bottom: 1px solid var(--p-border);
  background: linear-gradient(180deg, color-mix(in srgb, var(--p-elevated) 82%, transparent), transparent);
}
.legal-panel__title {
  font-size: 0.86rem;
  font-weight: 900;
  color: var(--p-text);
}
.legal-panel__body {
  padding: 0.95rem;
}
.legal-field {
  display: flex;
  flex-direction: column;
  gap: 0.45rem;
}
.legal-field--surface {
  padding: 0.9rem;
  border-radius: 1rem;
  border: 1px solid var(--p-border);
  background: linear-gradient(180deg, color-mix(in srgb, var(--p-elevated) 88%, transparent), var(--p-surface));
}
.legal-field__meta {
  font-size: 0.72rem;
  color: var(--p-hint);
}
.legal-input,
.legal-textarea {
  width: 100%;
  min-height: 2.95rem;
  padding: 0.8rem 0.95rem;
  border-radius: 0.95rem;
  border: 1px solid var(--p-border2);
  background: color-mix(in srgb, var(--p-surface-strong) 94%, transparent);
  color: var(--p-text);
  box-shadow: inset 0 1px 0 rgba(255,255,255,0.05);
  transition: 0.18s ease;
}
.legal-input::placeholder,
.legal-textarea::placeholder {
  color: var(--p-hint);
}
.legal-input:focus,
.legal-textarea:focus {
  outline: none;
  border-color: var(--p-accent);
  box-shadow: 0 0 0 4px var(--p-accent-d);
}
.legal-textarea {
  min-height: 7.5rem;
  resize: vertical;
}
.legal-fields {
  display: grid;
  grid-template-columns: repeat(12, minmax(0, 1fr));
  gap: 0.9rem;
}
.legal-span-12 { grid-column: span 12; }
.legal-span-8 { grid-column: span 8; }
.legal-span-6 { grid-column: span 6; }
.legal-span-4 { grid-column: span 4; }
.legal-toggle {
  min-height: 100%;
  padding: 0.95rem;
  border-radius: 1rem;
  border: 1px solid var(--p-border);
  background: linear-gradient(180deg, color-mix(in srgb, var(--p-elevated) 92%, transparent), var(--p-surface));
}
.legal-toggle__title {
  font-size: 0.78rem;
  font-weight: 800;
  color: var(--p-text);
}
.legal-toggle__control {
  margin-top: 0.85rem;
  display: flex;
  align-items: center;
  gap: 0.7rem;
  font-size: 0.82rem;
  color: var(--p-muted);
  cursor: pointer;
}
.legal-toggle__control input {
  width: 1rem;
  height: 1rem;
  cursor: pointer;
  accent-color: var(--p-accent);
}
.legal-translations {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 0.9rem;
}
.legal-translation {
  border: 1px solid var(--p-border);
  border-radius: 1rem;
  background: linear-gradient(180deg, color-mix(in srgb, var(--p-elevated) 84%, transparent), var(--p-surface));
  overflow: hidden;
}
.legal-translation__head {
  padding: 0.7rem 0.85rem;
  border-bottom: 1px solid var(--p-border);
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 0.75rem;
}
.legal-translation__title {
  font-size: 0.83rem;
  font-weight: 800;
  color: var(--p-text);
}
.legal-translation__body {
  padding: 0.85rem;
}
.legal-modal .modal-footer {
  padding: 0.95rem 1rem;
  border-top: 1px solid var(--p-border);
  background: linear-gradient(180deg, transparent, color-mix(in srgb, var(--p-elevated) 55%, transparent));
}
.legal-actions {
  display: flex;
  justify-content: flex-end;
  gap: 0.75rem;
  width: 100%;
}
.editor-toolbar {
  display: flex;
  flex-wrap: wrap;
  gap: 4px;
  padding: 10px 12px;
  background: linear-gradient(180deg, color-mix(in srgb, var(--p-elevated) 92%, transparent), var(--p-surface));
  border: 1px solid var(--p-border);
  border-bottom: none;
  border-radius: 14px 14px 0 0;
}
.editor-toolbar button {
  min-height: 2rem;
  padding: 5px 10px;
  border: 1px solid var(--p-border);
  border-radius: 10px;
  background: var(--p-surface);
  color: var(--p-muted);
  font-size: 12px;
  cursor: pointer;
  transition: all .15s;
  display: flex;
  align-items: center;
  gap: 4px;
}
.editor-toolbar button:hover {
  background: var(--p-hover);
  color: var(--p-text);
  border-color: var(--p-border2);
  transform: translateY(-1px);
}
.editor-toolbar .sep {
  width: 1px;
  background: var(--p-border);
  margin: 2px 4px;
  align-self: stretch;
}
.rich-editor {
  min-height: 340px;
  padding: 18px;
  background: var(--p-surface);
  border: 1px solid var(--p-border);
  border-radius: 0 0 14px 14px;
  color: var(--p-text);
  outline: none;
  font-size: 14px;
  line-height: 1.7;
  overflow-y: auto;
}
.rich-editor:focus {
  border-color: var(--p-accent);
  box-shadow: 0 0 0 4px var(--p-accent-d);
}
.rich-editor h1,.rich-editor h2,.rich-editor h3 { color: var(--p-text); margin: .75em 0 .35em; font-weight: 600; }
.rich-editor h1 { font-size: 1.5em; }
.rich-editor h2 { font-size: 1.25em; }
.rich-editor h3 { font-size: 1.1em; }
.rich-editor p { margin: 0 0 .75em; }
.rich-editor ul,.rich-editor ol { padding-left: 1.4em; margin-bottom: .75em; }
.rich-editor li { margin-bottom: .25em; }
.rich-editor strong { color: var(--p-text); font-weight: 600; }
.rich-editor a { color: var(--p-accent); }
.rich-editor blockquote {
  border-left: 3px solid var(--p-accent);
  margin: .75em 0;
  padding: .5em 1em;
  background: var(--p-accent-d);
  border-radius: 0 8px 8px 0;
  color: var(--p-muted);
}
.rich-editor hr { border: none; border-top: 1px solid var(--p-border); margin: 1em 0; }
.slug-input { font-family: 'JetBrains Mono', monospace; font-size: 13px; }
.legal-toolbar,
.legal-shell {
  display: none;
}
@media (max-width: 1199px) {
  .legal-translations {
    grid-template-columns: 1fr;
  }
}
@media (max-width: 767px) {
  .legal-modal .modal-dialog {
    width: calc(100vw - 0.75rem);
    margin: 0.375rem auto;
  }
  .legal-stats {
    grid-template-columns: 1fr;
  }
  .legal-fields {
    grid-template-columns: 1fr;
  }
  .legal-span-12,
  .legal-span-8,
  .legal-span-6,
  .legal-span-4 {
    grid-column: auto;
  }
  .legal-actions {
    flex-direction: column-reverse;
    align-items: stretch;
  }
}
</style>
<?php $__env->stopPush(); ?>

<?php $__env->startSection('content'); ?>
<?php
  $active = $counts['active'] ?? 0;
  $total = $counts['total'] ?? $policies->total();
?>

<?php if (isset($component)) { $__componentOriginalcb19cb35a534439097b02b8af91726ee = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalcb19cb35a534439097b02b8af91726ee = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.admin.page-header','data' => ['eyebrow' => 'Legal center','title' => 'Siyosatlar','subtitle' => ''.e($policies->total()).' ta huquqiy sahifa topildi']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('admin.page-header'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['eyebrow' => 'Legal center','title' => 'Siyosatlar','subtitle' => ''.e($policies->total()).' ta huquqiy sahifa topildi']); ?>
  <button class="btn-primary-gradient" data-bs-toggle="modal" data-bs-target="#createModal">
    <i class="bi bi-plus-lg me-2"></i>Yangi siyosat
  </button>
 <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalcb19cb35a534439097b02b8af91726ee)): ?>
<?php $attributes = $__attributesOriginalcb19cb35a534439097b02b8af91726ee; ?>
<?php unset($__attributesOriginalcb19cb35a534439097b02b8af91726ee); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalcb19cb35a534439097b02b8af91726ee)): ?>
<?php $component = $__componentOriginalcb19cb35a534439097b02b8af91726ee; ?>
<?php unset($__componentOriginalcb19cb35a534439097b02b8af91726ee); ?>
<?php endif; ?>

<form method="GET" class="kc-filter-card p-3 mb-3">
  <div class="row g-3 align-items-end">
    <input type="hidden" name="tab" value="<?php echo e($tab ?? 'active'); ?>">
    <div class="col-12 col-lg-9">
      <label class="form-label small text-uppercase fw-semibold text-secondary">Qidiruv</label>
      <div class="position-relative">
        <i class="bi bi-search position-absolute top-50 translate-middle-y ms-3 text-secondary"></i>
        <input type="search" name="search" value="<?php echo e(request('search')); ?>" class="form-control ps-5" placeholder="Sarlavha, slug yoki kontent bo‘yicha qidiring">
      </div>
    </div>
    <div class="col-12 col-lg-3 d-grid">
      <button class="btn btn-dark" type="submit"><i class="bi bi-funnel me-2"></i>Filtrlash</button>
    </div>
  </div>
</form>

<div class="d-flex flex-wrap gap-2 mb-3">
  <?php $__currentLoopData = [
    'active' => ['Faol', $counts['active'] ?? 0],
    'inactive' => ['Nofaol', $counts['inactive'] ?? 0],
    'all' => ['Barchasi', $counts['total'] ?? 0],
  ]; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => [$label, $count]): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
    <a href="<?php echo e(request()->fullUrlWithQuery(['tab' => $key, 'page' => null])); ?>" class="chip text-decoration-none <?php echo e(($tab ?? 'active') === $key ? 'chip-purple' : 'chip-gray'); ?>">
      <?php echo e($label); ?> <span><?php echo e($count); ?></span>
    </a>
  <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
</div>

<div class="row g-3 mb-4">
  <?php $__currentLoopData = [
    ['Jami', $total, 'chip-info', 'bi-file-earmark-text'],
    ['Faol', $active, 'chip-success', 'bi-check-circle'],
    ['Nofaol', $total - $active, 'chip-gray', 'bi-eye-slash'],
  ]; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as [$label, $value, $chip, $icon]): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
    <div class="col-12 col-md-4">
      <div class="card-panel p-3 h-100 d-flex align-items-center justify-content-between">
        <div>
          <div class="small text-secondary text-uppercase fw-bold mb-2" style="letter-spacing:.08em;"><?php echo e($label); ?></div>
          <div class="h3 fw-bold mb-0"><?php echo e($value); ?></div>
        </div>
        <span class="chip <?php echo e($chip); ?>"><i class="bi <?php echo e($icon); ?>"></i></span>
      </div>
    </div>
  <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
</div>

<?php if (isset($component)) { $__componentOriginal6c55ae2c9251ebabe977f3f2190280eb = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal6c55ae2c9251ebabe977f3f2190280eb = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.admin.section-card','data' => ['title' => 'Siyosatlar ro‘yxati','meta' => 'Slug, preview, ilova ko‘rinishi va status boshqaruvi']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('admin.section-card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => 'Siyosatlar ro‘yxati','meta' => 'Slug, preview, ilova ko‘rinishi va status boshqaruvi']); ?>
   <?php $__env->slot('actions', null, []); ?> 
    <span class="chip chip-gray"><?php echo e($total); ?> jami</span>
    <span class="chip chip-success"><?php echo e($active); ?> faol</span>
   <?php $__env->endSlot(); ?>
  <div class="table-responsive kc-table-shell">
    <table class="table data-table align-middle mb-0" data-index-grid>
      <thead>
        <tr>
          <th style="width:40px"></th>
          <th>#</th>
          <th>Sarlavha</th>
          <th>Slug</th>
          <th>Kontent</th>
          <th>Tartib</th>
          <th>Ilovada</th>
          <th>Status</th>
          <th>Yangilandi</th>
          <th style="width:110px"></th>
        </tr>
      </thead>
      <tbody>
        <?php $__empty_1 = true; $__currentLoopData = $policies; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $policy): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
        <tr class="legal-row">
          <td>
            <span class="legal-grip" title="Tartibni o'zgartirish">
              <i class="bi bi-grip-vertical"></i>
            </span>
          </td>
          <td style="font-family:'JetBrains Mono',monospace;color:var(--p-accent);font-size:12px">
            #<?php echo e($policy->id); ?>

          </td>
          <td>
            <div class="legal-title">
              <div class="legal-title__text"><?php echo e($policy->title); ?></div>
              <div class="legal-title__meta">Legal page</div>
            </div>
          </td>
          <td>
            <span class="legal-slug">/legal/<?php echo e($policy->slug); ?></span>
          </td>
          <td>
            <div class="legal-preview"><?php echo e(strip_tags($policy->content)); ?></div>
          </td>
          <td style="font-family:'JetBrains Mono',monospace;font-size:13px;color:var(--p-muted)">
            <?php echo e($policy->sort_order); ?>

          </td>
          <td>
            <?php if($policy->show_in_app): ?>
              <span class="chip chip-success"><i class="bi bi-check-lg"></i> Ha</span>
            <?php else: ?>
              <span class="chip chip-gray">Yo'q</span>
            <?php endif; ?>
          </td>
          <td>
            <form method="POST" action="<?php echo e(route('admin.policies.toggle', $policy)); ?>" style="display:inline">
              <?php echo csrf_field(); ?> <?php echo method_field('PATCH'); ?>
              <button type="submit"
                      class="btn btn-sm <?php echo e($policy->is_active ? 'btn-success' : 'btn-outline-secondary'); ?> legal-status-btn"
                      title="<?php echo e($policy->is_active ? 'Faol — o\'chirish' : 'Nofaol — yoqish'); ?>">
                <i class="bi <?php echo e($policy->is_active ? 'bi-toggle-on' : 'bi-toggle-off'); ?>"></i>
                <?php echo e($policy->is_active ? 'Faol' : 'Nofaol'); ?>

              </button>
            </form>
          </td>
          <td style="font-size:11px;color:var(--p-hint)">
            <?php echo e($policy->updated_at->format('d.m.Y')); ?>

          </td>
          <td>
            <div class="flex gap-1 justify-end">
              <a href="/legal/<?php echo e($policy->slug); ?>" target="_blank" class="btn btn-sm btn-outline-secondary" title="Ko'rish">
                <i class="bi bi-eye"></i>
              </a>
              <button class="btn btn-sm btn-outline-secondary" onclick="openEdit(<?php echo e($policy->id); ?>)" title="Tahrirlash">
                <i class="bi bi-pencil"></i>
              </button>
              <form method="POST" action="<?php echo e(route('admin.policies.destroy', $policy)); ?>" onsubmit="return confirm('Siyosatni o\'chirishga ishonchingiz komilmi?')">
                <?php echo csrf_field(); ?> <?php echo method_field('DELETE'); ?>
                <button type="submit" class="btn btn-sm btn-outline-danger" title="O'chirish">
                  <i class="bi bi-trash"></i>
                </button>
              </form>
            </div>
          </td>
        </tr>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
        <tr>
          <td colspan="10">
            <div class="empty-state p-4 text-center">
              <i class="bi bi-file-earmark-text" style="font-size:36px;display:block;margin-bottom:12px;opacity:.4"></i>
              <div style="font-size:14px">Hali siyosat qo'shilmagan</div>
            </div>
          </td>
        </tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
 <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal6c55ae2c9251ebabe977f3f2190280eb)): ?>
<?php $attributes = $__attributesOriginal6c55ae2c9251ebabe977f3f2190280eb; ?>
<?php unset($__attributesOriginal6c55ae2c9251ebabe977f3f2190280eb); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal6c55ae2c9251ebabe977f3f2190280eb)): ?>
<?php $component = $__componentOriginal6c55ae2c9251ebabe977f3f2190280eb; ?>
<?php unset($__componentOriginal6c55ae2c9251ebabe977f3f2190280eb); ?>
<?php endif; ?>

<?php if($policies->hasPages()): ?>
<div class="mt-4">
  <?php echo e($policies->links('a122.partials.pagination')); ?>

</div>
<?php endif; ?>

<div class="modal fade legal-modal" id="createModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-scrollable">
    <form method="POST" action="<?php echo e(route('admin.policies.store')); ?>" id="createForm">
      <?php echo csrf_field(); ?>
      <div class="modal-content">
        <div class="modal-header">
          <div class="legal-modal__title">Yangi siyosat</div>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="legal-modal-grid">
            <div class="legal-main">
              <section class="legal-panel">
                <div class="legal-panel__head">
                  <div class="legal-panel__title">Asosiy ma'lumotlar</div>
                </div>
                <div class="legal-panel__body">
                  <div class="legal-fields">
                    <div class="legal-span-8">
                      <div class="legal-field legal-field--surface">
                        <label class="p-form-label">Sarlavha <span style="color:var(--p-danger)">*</span></label>
                        <input type="text" name="title" class="legal-input" placeholder="Masalan: Maxfiylik siyosati" oninput="autoSlug(this,'create')" required>
                        <div class="legal-field__meta">Foydalanuvchi ko‘radigan asosiy hujjat nomi.</div>
                      </div>
                    </div>
                    <div class="legal-span-4">
                      <div class="legal-field legal-field--surface">
                        <label class="p-form-label">Slug</label>
                        <input type="text" name="slug" id="create-slug" class="legal-input slug-input" placeholder="maxfiylik-siyosati">
                        <div class="legal-field__meta">URL ichida ishlatiladigan qisqa identifikator.</div>
                      </div>
                    </div>
                    <div class="legal-span-4">
                      <div class="legal-field legal-field--surface">
                        <label class="p-form-label">Tartib raqami</label>
                        <input type="number" name="sort_order" class="legal-input" value="0" min="0">
                        <div class="legal-field__meta">Ro‘yxatda chiqish tartibini belgilaydi.</div>
                      </div>
                    </div>
                    <div class="legal-span-4">
                      <div class="legal-toggle">
                        <div class="legal-toggle__title">Ilovada ko'rsatish</div>
                        <label class="legal-toggle__control">
                          <input type="hidden" name="show_in_app" value="0">
                          <input type="checkbox" name="show_in_app" value="1" checked>
                          Ilovada ko‘rsatilsin
                        </label>
                      </div>
                    </div>
                    <div class="legal-span-4">
                      <div class="legal-toggle">
                        <div class="legal-toggle__title">Holati</div>
                        <label class="legal-toggle__control">
                          <input type="hidden" name="is_active" value="0">
                          <input type="checkbox" name="is_active" value="1" checked style="accent-color:var(--p-success)">
                          Faol holatda saqlash
                        </label>
                      </div>
                    </div>
                  </div>
                </div>
              </section>

              <section class="legal-panel">
                <div class="legal-panel__head">
                  <div class="legal-panel__title">Asosiy kontent</div>
                </div>
                <div class="legal-panel__body">
                  <label class="p-form-label">Kontent (oʻzbekcha) <span style="color:var(--p-danger)">*</span></label>
                  <?php echo $__env->make('a122.policies.partials.editor', ['id' => 'create'], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                  <input type="hidden" name="content" id="create-content">
                </div>
              </section>
            </div>

            <section class="legal-side">
              <section class="legal-panel">
                <div class="legal-panel__head">
                  <div class="legal-panel__title">Tarjimalar</div>
                </div>
                <div class="legal-panel__body">
                  <div class="legal-translations">
                    <?php $__currentLoopData = ['ru' => 'Русский', 'en' => 'English', 'ja' => '日本語']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $loc => $lab): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <div class="legal-translation">
                      <div class="legal-translation__head">
                        <div class="legal-translation__title"><?php echo e($lab); ?></div>
                        <span class="badge badge-muted"><?php echo e(strtoupper($loc)); ?></span>
                      </div>
                      <div class="legal-translation__body">
                        <div class="legal-field legal-field--surface mb-3">
                          <label class="p-form-label">Sarlavha</label>
                          <input type="text" name="translations[<?php echo e($loc); ?>][title]" class="legal-input" maxlength="255" placeholder="Tarjima sarlavhasi">
                        </div>
                        <label class="p-form-label">Kontent</label>
                        <?php echo $__env->make('a122.policies.partials.editor', ['id' => 'create-'.$loc, 'content' => ''], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                        <input type="hidden" name="translations[<?php echo e($loc); ?>][content]" id="create-<?php echo e($loc); ?>-content">
                      </div>
                    </div>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                  </div>
                </div>
              </section>
            </section>
          </div>
        </div>
        <div class="modal-footer">
          <div class="legal-actions">
            <button type="button" class="btn-p ghost" data-bs-dismiss="modal">Bekor qilish</button>
            <button type="submit" class="btn-p primary" onclick="['create','create-ru','create-en','create-ja'].forEach(syncContent)">
              <i class="bi bi-floppy-fill mr-1"></i> Saqlash
            </button>
          </div>
        </div>
      </div>
    </form>
  </div>
</div>

<?php $__currentLoopData = $policies; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $policy): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
<div class="modal fade legal-modal" id="editModal-<?php echo e($policy->id); ?>" tabindex="-1">
  <div class="modal-dialog modal-dialog-scrollable">
    <form method="POST" action="<?php echo e(route('admin.policies.update', $policy)); ?>" id="editForm-<?php echo e($policy->id); ?>">
      <?php echo csrf_field(); ?> <?php echo method_field('PUT'); ?>
      <div class="modal-content">
        <div class="modal-header">
          <div class="legal-modal__title">Tahrirlash: <?php echo e($policy->title); ?></div>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="legal-modal-grid">
            <div class="legal-main">
              <section class="legal-panel">
                <div class="legal-panel__head">
                  <div class="legal-panel__title">Asosiy ma'lumotlar</div>
                </div>
                <div class="legal-panel__body">
                  <div class="legal-fields">
                    <div class="legal-span-8">
                      <div class="legal-field legal-field--surface">
                        <label class="p-form-label">Sarlavha <span style="color:var(--p-danger)">*</span></label>
                        <input type="text" name="title" class="legal-input" value="<?php echo e($policy->title); ?>" oninput="autoSlug(this,'edit-<?php echo e($policy->id); ?>')" required>
                        <div class="legal-field__meta">Foydalanuvchi ko‘radigan asosiy hujjat nomi.</div>
                      </div>
                    </div>
                    <div class="legal-span-4">
                      <div class="legal-field legal-field--surface">
                        <label class="p-form-label">Slug</label>
                        <input type="text" name="slug" id="edit-<?php echo e($policy->id); ?>-slug" class="legal-input slug-input" value="<?php echo e($policy->slug); ?>">
                        <div class="legal-field__meta">URL ichida ishlatiladigan qisqa identifikator.</div>
                      </div>
                    </div>
                    <div class="legal-span-4">
                      <div class="legal-field legal-field--surface">
                        <label class="p-form-label">Tartib raqami</label>
                        <input type="number" name="sort_order" class="legal-input" value="<?php echo e($policy->sort_order); ?>" min="0">
                        <div class="legal-field__meta">Ro‘yxatda chiqish tartibini belgilaydi.</div>
                      </div>
                    </div>
                    <div class="legal-span-4">
                      <div class="legal-toggle">
                        <div class="legal-toggle__title">Ilovada ko'rsatish</div>
                        <label class="legal-toggle__control">
                          <input type="hidden" name="show_in_app" value="0">
                          <input type="checkbox" name="show_in_app" value="1" <?php echo e($policy->show_in_app ? 'checked' : ''); ?>>
                          Ilovada ko‘rsatilsin
                        </label>
                      </div>
                    </div>
                    <div class="legal-span-4">
                      <div class="legal-toggle">
                        <div class="legal-toggle__title">Holati</div>
                        <label class="legal-toggle__control">
                          <input type="hidden" name="is_active" value="0">
                          <input type="checkbox" name="is_active" value="1" <?php echo e($policy->is_active ? 'checked' : ''); ?> style="accent-color:var(--p-success)">
                          Faol holatda saqlash
                        </label>
                      </div>
                    </div>
                  </div>
                </div>
              </section>

              <section class="legal-panel">
                <div class="legal-panel__head">
                  <div class="legal-panel__title">Asosiy kontent</div>
                </div>
                <div class="legal-panel__body">
                  <label class="p-form-label">Kontent (oʻzbekcha) <span style="color:var(--p-danger)">*</span></label>
                  <?php echo $__env->make('a122.policies.partials.editor', ['id' => 'edit-'.$policy->id, 'content' => $policy->content], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                  <input type="hidden" name="content" id="edit-<?php echo e($policy->id); ?>-content">
                </div>
              </section>
            </div>

            <section class="legal-side">
              <section class="legal-panel">
                <div class="legal-panel__head">
                  <div class="legal-panel__title">Tarjimalar</div>
                </div>
                <div class="legal-panel__body">
                  <div class="legal-translations">
                    <?php $__currentLoopData = ['ru' => 'Русский', 'en' => 'English', 'ja' => '日本語']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $loc => $lab): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <?php $tr = $policy->translations->firstWhere('locale', $loc); ?>
                    <div class="legal-translation">
                      <div class="legal-translation__head">
                        <div class="legal-translation__title"><?php echo e($lab); ?></div>
                        <span class="badge badge-muted"><?php echo e(strtoupper($loc)); ?></span>
                      </div>
                      <div class="legal-translation__body">
                        <div class="legal-field legal-field--surface mb-3">
                          <label class="p-form-label">Sarlavha</label>
                          <input type="text" name="translations[<?php echo e($loc); ?>][title]" class="legal-input" maxlength="255" value="<?php echo e($tr?->title ?? ''); ?>" placeholder="Tarjima sarlavhasi">
                        </div>
                        <label class="p-form-label">Kontent</label>
                        <?php echo $__env->make('a122.policies.partials.editor', ['id' => 'edit-'.$policy->id.'-'.$loc, 'content' => $tr?->content ?? ''], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                        <input type="hidden" name="translations[<?php echo e($loc); ?>][content]" id="edit-<?php echo e($policy->id); ?>-<?php echo e($loc); ?>-content">
                      </div>
                    </div>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                  </div>
                </div>
              </section>
            </section>
          </div>
        </div>
        <div class="modal-footer">
          <div class="legal-actions">
            <button type="button" class="btn-p ghost" data-bs-dismiss="modal">Bekor qilish</button>
            <button type="submit" class="btn-p primary" onclick="['edit-<?php echo e($policy->id); ?>','edit-<?php echo e($policy->id); ?>-ru','edit-<?php echo e($policy->id); ?>-en','edit-<?php echo e($policy->id); ?>-ja'].forEach(syncContent)">
              <i class="bi bi-floppy-fill mr-1"></i> Saqlash
            </button>
          </div>
        </div>
      </div>
    </form>
  </div>
</div>
<?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>

<?php $__env->startPush('scripts'); ?>
<script>
function autoSlug(input, prefix) {
  const slugInput = document.getElementById(prefix + '-slug');
  if (!slugInput || slugInput.dataset.manual) return;
  slugInput.value = input.value
    .toLowerCase()
    .replace(/[^a-z0-9\s\-а-яёўқғҳ]/gi, '')
    .replace(/\s+/g, '-')
    .replace(/-+/g, '-');
}

document.querySelectorAll('.slug-input').forEach(el => {
  el.addEventListener('input', () => { el.dataset.manual = '1'; });
});

function syncContent(prefix) {
  const editor = document.getElementById(prefix + '-editor');
  const hidden = document.getElementById(prefix + '-content');
  if (editor && hidden) hidden.value = editor.innerHTML;
}

function openEdit(id) {
  const modal = new bootstrap.Modal(document.getElementById('editModal-' + id));
  modal.show();
}

function execCmd(cmd, value, prefix) {
  const editor = document.getElementById(prefix + '-editor');
  editor.focus();
  document.execCommand(cmd, false, value || null);
}

function insertHTML(html, prefix) {
  const editor = document.getElementById(prefix + '-editor');
  editor.focus();
  document.execCommand('insertHTML', false, html);
}
</script>
<?php $__env->stopPush(); ?>

<?php $__env->stopSection(); ?>

<?php echo $__env->make('a122.layouts.admin', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/abbos/PROJECTS/MY/kitobchi-server/kitobchi-laravel/resources/views/a122/policies/index.blade.php ENDPATH**/ ?>