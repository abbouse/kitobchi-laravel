{{-- ================================================================
    <x-crud-toolbar
       title="..."
       :create-url="route('admin.users.create')"
       search-placeholder="Qidirish..."
    />
    ================================================================ --}}
@props([
  'title'              => '',
  'createUrl'          => null,
  'searchPlaceholder'  => 'Qidirish...',
])

<div class="card-panel p-3 d-flex flex-column flex-lg-row align-items-lg-center justify-content-lg-between gap-3 mb-4">
  <div>
    <h2 class="card-panel-title mb-1">{{ $title }}</h2>
    <p class="card-panel-sub mb-0" x-text="filtered.length + ' ta yozuv topildi'"></p>
  </div>

  <div class="d-flex flex-wrap align-items-center gap-2">
    {{-- Search --}}
    <div class="position-relative flex-grow-1" style="min-width:180px;">
      <i data-lucide="search" class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
      <input x-model="search" type="text" placeholder="{{ $searchPlaceholder }}"
        class="input !pl-9 !py-2 w-full">
    </div>

    {{-- Filter button --}}
    <button @click="filterOpen = !filterOpen" class="btn btn-secondary">
      <i data-lucide="filter" class="w-4 h-4"></i>
      <span class="hidden sm:inline">Filter</span>
    </button>

    {{-- Import --}}
    <button @click="importOpen = true" class="btn btn-secondary">
      <i data-lucide="upload" class="w-4 h-4"></i>
      <span class="hidden sm:inline">Import</span>
    </button>

    {{-- Export dropdown --}}
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

    {{-- Bulk delete (visible if any selected) --}}
    <button x-show="selected.length" x-cloak @click="bulkDelete()" class="btn btn-danger">
      <i data-lucide="trash-2" class="w-4 h-4"></i>
      <span x-text="'O‘chirish (' + selected.length + ')'"></span>
    </button>

    {{-- Create --}}
    @if($createUrl)
      <a href="{{ $createUrl }}" class="btn btn-primary">
        <i data-lucide="plus" class="w-4 h-4"></i>
        <span>Qo‘shish</span>
      </a>
    @endif
  </div>
</div>

{{-- Filter panel (collapsible) --}}
<div x-show="filterOpen" x-transition x-cloak class="card-panel p-4 mb-4">
  {{ $slot }}
</div>

{{-- ===== Import modal ===== --}}
<div x-show="importOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4">
  <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" @click="importOpen = false"></div>
  <div class="relative card-panel w-full max-w-md p-4" x-transition>
    <div class="flex items-center justify-between mb-4">
      <h3 class="card-panel-title d-flex align-items-center gap-2 mb-0">
        <i data-lucide="upload" class="w-5 h-5 text-emerald-500"></i> Ma'lumot import qilish
      </h3>
      <button @click="importOpen = false" class="btn btn-outline-secondary p-1.5 rounded-lg">
        <i data-lucide="x" class="w-4 h-4"></i>
      </button>
    </div>
    <p class="card-panel-sub mb-4">CSV, Excel (.xlsx) yoki JSON formatdagi faylni tanlang.</p>
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

{{-- ===== Delete confirm modal ===== --}}
<div x-show="deleteOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4">
  <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" @click="deleteOpen = false"></div>
  <div class="relative card-panel w-full max-w-sm p-4" x-transition>
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
