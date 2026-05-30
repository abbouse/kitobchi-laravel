
<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames((['label'=>'','value'=>'','icon'=>'activity','tone'=>'emerald']));

foreach ($attributes->all() as $__key => $__value) {
    if (in_array($__key, $__propNames)) {
        $$__key = $$__key ?? $__value;
    } else {
        $__newAttributes[$__key] = $__value;
    }
}

$attributes = new \Illuminate\View\ComponentAttributeBag($__newAttributes);

unset($__propNames);
unset($__newAttributes);

foreach (array_filter((['label'=>'','value'=>'','icon'=>'activity','tone'=>'emerald']), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars); ?>

<?php
  $tones = [
    'emerald' => 'text-emerald-600 dark:text-emerald-400',
    'sky'     => 'text-sky-600 dark:text-sky-400',
    'violet'  => 'text-violet-600 dark:text-violet-400',
    'amber'   => 'text-amber-600 dark:text-amber-400',
    'rose'    => 'text-rose-600 dark:text-rose-400',
  ];
  $t = $tones[$tone] ?? $tones['emerald'];
?>

<div class="card-panel d-flex align-items-center gap-3 px-3 py-3" style="min-width:180px;">
  <div class="d-inline-grid place-items-center rounded-3" style="width:36px;height:36px;background:color-mix(in srgb,var(--template-brand) 9%,var(--template-card));">
    <i data-lucide="<?php echo e($icon); ?>" class="w-4 h-4 <?php echo e($t); ?>"></i>
  </div>
  <div>
    <div class="small text-secondary fw-semibold"><?php echo e($label); ?></div>
    <div class="fw-bold"><?php echo e($value); ?></div>
  </div>
</div>
<?php /**PATH /Users/abbos/PROJECTS/MY/kitobchi-server/kitobchi-laravel/resources/views/components/stat-pill.blade.php ENDPATH**/ ?>