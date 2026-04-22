@extends('a122.layouts.admin')
@section('title', 'Reelni tahrirlash')
@section('page-title', 'Reelni tahrirlash')

@section('content')
<div class="mb-4">
    <a href="{{ route('admin.reels.index') }}" class="btn btn-secondary flex items-center gap-2 w-fit">
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

<div class="card p-5">
    <form method="POST" action="{{ route('admin.reels.update', $reel) }}" class="grid grid-cols-1 md:grid-cols-2 gap-4">
        @csrf
        @method('PUT')

        <div class="md:col-span-2">
            <label class="text-xs text-gray-500 mb-1 block">Sarlavha <span class="text-red-500">*</span></label>
            <input
                name="title"
                class="input"
                required
                placeholder="Reel sarlavhasini kiriting"
                value="{{ old('title', $reel->title) }}"
            >
        </div>

        <div class="md:col-span-2">
            <label class="text-xs text-gray-500 mb-1 block">Tavsif</label>
            <textarea
                name="description"
                class="textarea"
                rows="4"
                placeholder="Reel haqida qisqacha tavsif (ixtiyoriy)"
            >{{ old('description', $reel->description) }}</textarea>
        </div>

        <div>
            <label class="text-xs text-gray-500 mb-1 block">Tartib raqami</label>
            <input
                name="order"
                type="number"
                class="input"
                min="0"
                value="{{ old('order', $reel->order) }}"
                placeholder="Tartib raqami"
            >
        </div>

        <div class="md:col-span-2 flex items-center justify-between pt-2 border-t border-gray-100 dark:border-white/10">
            <a href="{{ route('admin.reels.show', $reel) }}" class="btn btn-secondary flex items-center gap-2">
                <i data-lucide="eye" class="w-4 h-4"></i> Ko'rish
            </a>
            <div class="flex items-center gap-2">
                <a href="{{ route('admin.reels.index') }}" class="btn btn-secondary">Bekor</a>
                <button type="submit" class="btn btn-primary flex items-center gap-2">
                    <i data-lucide="save" class="w-4 h-4"></i> Saqlash
                </button>
            </div>
        </div>
    </form>
</div>
@endsection
