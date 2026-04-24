
<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames(([
  'title'              => '',
  'createUrl'          => null,
  'searchPlaceholder'  => 'Qidirish...',
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
  'title'              => '',
  'createUrl'          => null,
  'searchPlaceholder'  => 'Qidirish...',
]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars); ?>

<div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-3 mb-4">
  <div>
    <h2 class="text-xl font-bold tracking-tight"><?php echo e($title); ?></h2>
    <p class="text-xs text-gray-500 mt-0.5" x-text="filtered.length + ' ta yozuv topildi'"></p>
  </div>

  <div class="flex flex-wrap items-center gap-2">
    
    <div class="relative flex-1 min-w-[180px]">
      <i data-lucide="search" class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
      <input x-model="search" type="text" placeholder="<?php echo e($searchPlaceholder); ?>"
        class="input !pl-9 !py-2 w-full">
    </div>

    
    <button @click="filterOpen = !filterOpen" class="btn btn-secondary">
      <i data-lucide="filter" class="w-4 h-4"></i>
      <span class="hidden sm:inline">Filter</span>
    </button>

    
    <button @click="importOpen = true" class="btn btn-secondary">
      <i data-lucide="upload" class="w-4 h-4"></i>
      <span class="hidden sm:inline">Import</span>
    </button>

    
    <div x-data="{ open: false }" class="relative">
      <button @click="open = !open" class="btn btn-secondary">
        <i data-lucide="download" class="w-4 h-4"></i>
        <span class="hidden sm:inline">Export</span>
        <i data-lucide="chevron-down" class="w-3 h-3"></i>
      </button>
      <div x-show="open" @click.outside="open = false" x-transition x-cloak
           class="absolute right-0 mt-2 w-44 rounded-xl bg-white dark:bg-[#14171f] border border-gray-100 dark:border-white/5 shadow-xl py-1 z-30">
        <button @click="exportCSV(); open = false" class="w-full flex items-center gap-2 px-3 py-2 text-sm hover:bg-gray-50 dark:hover:bg-white/5 text-left">
          <i data-lucide="file-text" class="w-4 h-4"></i> CSV
        </button>
        <button @click="exportJSON(); open = false" class="w-full flex items-center gap-2 px-3 py-2 text-sm hover:bg-gray-50 dark:hover:bg-white/5 text-left">
          <i data-lucide="braces" class="w-4 h-4"></i> JSON
        </button>
        <button @click="print(); open = false" class="w-full flex items-center gap-2 px-3 py-2 text-sm hover:bg-gray-50 dark:hover:bg-white/5 text-left">
          <i data-lucide="printer" class="w-4 h-4"></i> Chop etish (PDF)
        </button>
      </div>
    </div>

    
    <button x-show="selected.length" x-cloak @click="bulkDelete()" class="btn btn-danger">
      <i data-lucide="trash-2" class="w-4 h-4"></i>
      <span x-text="'O‘chirish (' + selected.length + ')'"></span>
    </button>

    
    <?php if($createUrl): ?>
      <a href="<?php echo e($createUrl); ?>" class="btn btn-primary">
        <i data-lucide="plus" class="w-4 h-4"></i>
        <span>Qo‘shish</span>
      </a>
    <?php endif; ?>
  </div>
</div>


<div x-show="filterOpen" x-transition x-cloak class="card p-4 mb-4">
  <?php echo e($slot); ?>

</div>


<div x-show="importOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4">
  <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" @click="importOpen = false"></div>
  <div class="relative card w-full max-w-md p-6" x-transition>
    <div class="flex items-center justify-between mb-4">
      <h3 class="font-bold text-lg flex items-center gap-2">
        <i data-lucide="upload" class="w-5 h-5 text-emerald-500"></i> Ma'lumot import qilish
      </h3>
      <button @click="importOpen = false" class="btn-ghost p-1.5 rounded-lg">
        <i data-lucide="x" class="w-4 h-4"></i>
      </button>
    </div>
    <p class="text-sm text-gray-500 mb-4">CSV, Excel (.xlsx) yoki JSON formatdagi faylni tanlang.</p>
    <label class="block border-2 border-dashed border-gray-300 dark:border-white/10 rounded-xl p-8 text-center cursor-pointer hover:border-emerald-500 transition">
      <input type="file" class="hidden" accept=".csv,.xlsx,.json">
      <i data-lucide="cloud-upload" class="w-10 h-10 text-gray-400 mx-auto mb-2"></i>
      <div class="text-sm font-semibold">Faylni shu yerga torting yoki bosing</div>
      <div class="text-xs text-gray-500 mt-1">Maksimal hajm: 10MB</div>
    </label>
    <div class="flex justify-end gap-2 mt-5">
      <button @click="importOpen = false" class="btn btn-secondary">Bekor qilish</button>
      <button class="btn btn-primary">
        <i data-lucide="upload" class="w-4 h-4"></i> Yuklash
      </button>
    </div>
  </div>
</div>


<div x-show="deleteOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4">
  <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" @click="deleteOpen = false"></div>
  <div class="relative card w-full max-w-sm p-6" x-transition>
    <div class="w-12 h-12 rounded-full bg-rose-100 dark:bg-rose-500/10 flex items-center justify-center mx-auto mb-3">
      <i data-lucide="alert-triangle" class="w-6 h-6 text-rose-600"></i>
    </div>
    <h3 class="text-center font-bold text-lg">O‘chirishni tasdiqlang</h3>
    <p class="text-center text-sm text-gray-500 mt-1">Bu amalni qaytarib bo‘lmaydi.</p>
    <div class="flex gap-2 mt-5">
      <button @click="deleteOpen = false" class="btn btn-secondary flex-1">Yo‘q</button>
      <button @click="confirmDelete()" class="btn btn-danger flex-1">Ha, o‘chir</button>
    </div>
  </div>
</div>
<?php /**PATH /Users/abbos/PROJECTS/MY/kitobchi-server/kitobchi-laravel/resources/views/components/crud-toolbar.blade.php ENDPATH**/ ?>