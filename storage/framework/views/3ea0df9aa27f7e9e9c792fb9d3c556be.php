
<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames(([
  'title'  => '',
  'value'  => '',
  'change' => null,
  'trend'  => 'up',         // up|down|flat
  'icon'   => 'activity',
  'color'  => 'emerald',    // emerald|sky|violet|amber|rose|slate
]));

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

foreach (array_filter(([
  'title'  => '',
  'value'  => '',
  'change' => null,
  'trend'  => 'up',         // up|down|flat
  'icon'   => 'activity',
  'color'  => 'emerald',    // emerald|sky|violet|amber|rose|slate
]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars); ?>

<?php
  $colorMap = [
    'emerald' => ['bg'=>'bg-emerald-50 dark:bg-emerald-500/10','txt'=>'text-emerald-600 dark:text-emerald-400'],
    'sky'     => ['bg'=>'bg-sky-50 dark:bg-sky-500/10',         'txt'=>'text-sky-600 dark:text-sky-400'],
    'violet'  => ['bg'=>'bg-violet-50 dark:bg-violet-500/10',   'txt'=>'text-violet-600 dark:text-violet-400'],
    'amber'   => ['bg'=>'bg-amber-50 dark:bg-amber-500/10',     'txt'=>'text-amber-600 dark:text-amber-400'],
    'rose'    => ['bg'=>'bg-rose-50 dark:bg-rose-500/10',       'txt'=>'text-rose-600 dark:text-rose-400'],
    'slate'   => ['bg'=>'bg-slate-100 dark:bg-white/5',         'txt'=>'text-slate-600 dark:text-slate-300'],
  ];
  $c = $colorMap[$color] ?? $colorMap['emerald'];
  $trendCls = $trend === 'up'   ? 'text-emerald-600 dark:text-emerald-400'
            : ($trend === 'down' ? 'text-rose-600 dark:text-rose-400' : 'text-gray-500');
  $trendIcon = $trend === 'up'  ? 'trending-up'
             : ($trend === 'down' ? 'trending-down' : 'minus');
?>

<div class="card-panel p-4 h-100">
  <div class="d-flex align-items-start justify-content-between gap-3 mb-3">
    <div class="small text-secondary fw-semibold"><?php echo e($title); ?></div>
    <div class="d-inline-grid place-items-center rounded-3 <?php echo e($c['bg']); ?>" style="width:38px;height:38px;">
      <i data-lucide="<?php echo e($icon); ?>" class="w-4 h-4 <?php echo e($c['txt']); ?>"></i>
    </div>
  </div>
  <div class="h3 fw-bold mb-0"><?php echo e($value); ?></div>
  <?php if($change !== null): ?>
    <div class="mt-2 d-flex align-items-center gap-1 small fw-semibold <?php echo e($trendCls); ?>">
      <i data-lucide="<?php echo e($trendIcon); ?>" class="w-3.5 h-3.5"></i>
      <span><?php echo e($change); ?></span>
      <span class="text-secondary fw-normal">o'tgan davrga nisbatan</span>
    </div>
  <?php endif; ?>
</div>
<?php /**PATH /Users/abbos/PROJECTS/MY/kitobchi-server/kitobchi-laravel/resources/views/components/kpi-card.blade.php ENDPATH**/ ?>