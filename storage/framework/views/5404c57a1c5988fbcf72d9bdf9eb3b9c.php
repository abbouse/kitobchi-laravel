
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

<div class="flex items-center gap-3 px-4 py-3 rounded-2xl bg-white dark:bg-[#0f1218] border border-gray-100 dark:border-white/5 shadow-soft min-w-[180px]">
  <div class="w-9 h-9 rounded-xl bg-gray-50 dark:bg-white/5 flex items-center justify-center">
    <i data-lucide="<?php echo e($icon); ?>" class="w-4 h-4 <?php echo e($t); ?>"></i>
  </div>
  <div class="leading-tight">
    <div class="text-[11px] text-gray-500 dark:text-gray-400"><?php echo e($label); ?></div>
    <div class="text-sm font-bold"><?php echo e($value); ?></div>
  </div>
</div>
<?php /**PATH /Users/abbos/PROJECTS/MY/kitobchi-server/kitobchi/resources/views/components/stat-pill.blade.php ENDPATH**/ ?>