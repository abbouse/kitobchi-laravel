@extends('a122.layouts.admin')
@section('title', 'Yangilikni tahrirlash')
@section('page-title', 'Yangilikni tahrirlash')

@section('content')
<div class="mb-4">
    <a href="{{ route('admin.news.index') }}" class="btn btn-secondary flex items-center gap-2 w-fit">
        <i data-lucide="arrow-left" class="w-4 h-4"></i> Orqaga
    </a>
</div>

@if(session('success'))
    <div class="mb-4 rounded-lg bg-green-100 text-green-800 dark:bg-green-500/10 dark:text-green-400 px-4 py-3 text-sm font-medium">
        {{ session('success') }}
    </div>
@endif
@if(session('error'))
    <div class="mb-4 rounded-lg bg-red-100 text-red-800 dark:bg-red-500/10 dark:text-red-400 px-4 py-3 text-sm font-medium">
        {{ session('error') }}
    </div>
@endif

@if($errors->any())
    <div class="mb-4 rounded-lg bg-red-100 text-red-800 dark:bg-red-500/10 dark:text-red-400 px-4 py-3 text-sm">
        <ul class="list-disc list-inside space-y-1">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="card-panel p-5">
    <form method="POST" action="{{ route('admin.news.update', $news) }}" enctype="multipart/form-data" class="grid grid-cols-1 md:grid-cols-2 gap-4">
        @csrf
        @method('PUT')

        <div class="md:col-span-2">
            <label class="text-xs text-gray-500 mb-1 block">Sarlavha <span class="text-red-500">*</span></label>
            <input
                name="title"
                class="input"
                required
                placeholder="Yangilik sarlavhasini kiriting"
                value="{{ old('title', $news->title) }}"
            >
        </div>

        <div class="md:col-span-2">
            <label class="text-xs text-gray-500 mb-1 block">Tavsif</label>
            <textarea
                name="description"
                class="textarea"
                rows="4"
                placeholder="Yangilik haqida tavsif"
            >{{ old('description', $news->description) }}</textarea>
        </div>

        <div>
            <label class="text-xs text-gray-500 mb-1 block">Rasm (imgUrl)</label>
            @if($news->imgUrl)
                <div class="mb-2">
                    <img
                        src="{{ Str::startsWith($news->imgUrl, 'http') ? $news->imgUrl : asset('storage/' . $news->imgUrl) }}"
                        alt="Joriy rasm"
                        class="h-24 w-auto rounded-lg object-cover border border-gray-200 dark:border-white/10"
                    >
                    <p class="text-xs text-gray-400 mt-1">Joriy rasm. Yangi rasm yuklash uchun quyidagi maydondan foydalaning.</p>
                </div>
            @endif
            <input name="imgUrl" type="file" class="input" accept="image/*">
        </div>

        <div>
            <label class="text-xs text-gray-500 mb-1 block">Joylashuv (align)</label>
            <select name="align" class="select">
                <option value="top" @selected(old('align', $news->align) === 'top')>Yuqori (top)</option>
                <option value="center" @selected(old('align', $news->align) === 'center')>Markaz (center)</option>
            </select>
        </div>

        <div>
            <label class="text-xs text-gray-500 mb-1 block">Harakat turi (action)</label>
            <select name="action" class="select">
                <option value="news" @selected(old('action', $news->action) === 'news')>Yangilik (news)</option>
                <option value="to_shop" @selected(old('action', $news->action) === 'to_shop')>Do'konga (to_shop)</option>
                <option value="to_product" @selected(old('action', $news->action) === 'to_product')>Mahsulotga (to_product)</option>
            </select>
        </div>

        <div>
            <label class="text-xs text-gray-500 mb-1 block">Harakat ID <span class="text-gray-400">(ixtiyoriy)</span></label>
            <input
                name="action_id"
                type="number"
                class="input"
                placeholder="Do'kon yoki mahsulot IDsi"
                value="{{ old('action_id', $news->action_id) }}"
                min="1"
            >
        </div>

        <div class="md:col-span-2 flex items-center gap-3">
            <label class="flex items-center gap-2 cursor-pointer">
                <input
                    name="status"
                    type="checkbox"
                    value="1"
                    class="w-4 h-4 rounded accent-emerald-500"
                    @checked(old('status', $news->status === 'active' || $news->status == 1))
                >
                <span class="text-sm font-medium">Faol holat</span>
            </label>
            <span class="text-xs text-gray-400">Belgilanmasa, yangilik yashirin bo'ladi</span>
        </div>

        <div class="md:col-span-2 flex items-center justify-between pt-2 border-t border-gray-100 dark:border-white/10">
            <a href="{{ route('admin.news.show', $news) }}" class="btn btn-secondary flex items-center gap-2">
                <i data-lucide="eye" class="w-4 h-4"></i> Ko'rish
            </a>
            <div class="flex items-center gap-2">
                <a href="{{ route('admin.news.index') }}" class="btn btn-secondary">Bekor</a>
                <button type="submit" class="btn btn-primary flex items-center gap-2">
                    <i data-lucide="save" class="w-4 h-4"></i> Saqlash
                </button>
            </div>
        </div>
    </form>
</div>
@endsection
