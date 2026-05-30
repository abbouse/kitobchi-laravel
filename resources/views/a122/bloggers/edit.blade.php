@extends('a122.layouts.admin')
@php $isEdit = isset($blogger); @endphp
@section('title', $isEdit ? 'Blogerni tahrirlash' : 'Yangi bloger')
@section('page-title', $isEdit ? 'Blogerni tahrirlash' : 'Yangi bloger')

@section('content')
<div class="max-w-5xl space-y-6">
  <x-a122.page-header back-href="{{ $isEdit ? route('admin.bloggers.show', $blogger) : route('admin.bloggers.index') }}">
    <x-slot name="heading">{{ $isEdit ? $blogger->full_name : 'Yangi hamkor bloger' }}</x-slot>
    <x-slot name="meta">Aloqa, manzil, tarmoq havolalari va faol muddatni belgilang.</x-slot>
  </x-a122.page-header>

  <div class="card-panel p-6">
    <form method="POST" action="{{ $isEdit ? route('admin.bloggers.update', $blogger) : route('admin.bloggers.store') }}">
      @csrf
      @if($isEdit) @method('PUT') @endif

      @if($errors->any())
        <div class="mb-4 p-4 rounded-xl bg-rose-50 dark:bg-rose-500/10 text-rose-700 dark:text-rose-400 text-sm">
          <ul class="space-y-1">
            @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
          </ul>
        </div>
      @endif

      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
          <label class="block text-xs font-semibold text-gray-500 mb-1">Ism <span class="text-rose-500">*</span></label>
          <input name="first_name" value="{{ old('first_name', $blogger->first_name ?? '') }}" class="input" required>
        </div>
        <div>
          <label class="block text-xs font-semibold text-gray-500 mb-1">Familiya</label>
          <input name="last_name" value="{{ old('last_name', $blogger->last_name ?? '') }}" class="input">
        </div>
        <div>
          <label class="block text-xs font-semibold text-gray-500 mb-1">Telefon raqami</label>
          <input name="phone_number" value="{{ old('phone_number', $blogger->phone_number ?? '') }}" class="input" placeholder="+998 99 000 00 00">
        </div>
        <div>
          <label class="block text-xs font-semibold text-gray-500 mb-1">Faol muddati <span class="text-rose-500">*</span></label>
          <input type="datetime-local" name="active_until" value="{{ old('active_until', isset($blogger) && $blogger->active_until ? $blogger->active_until->format('Y-m-d\TH:i') : '') }}" class="input" required>
        </div>
      </div>

      <div class="mt-4">
        <label class="block text-xs font-semibold text-gray-500 mb-1">Manzil</label>
        <textarea name="address" rows="3" class="input">{{ old('address', $blogger->address ?? '') }}</textarea>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
        <div>
          <label class="block text-xs font-semibold text-gray-500 mb-1">Instagram</label>
          <input name="instagram_url" value="{{ old('instagram_url', $blogger->instagram_url ?? '') }}" class="input" placeholder="@username yoki https://instagram.com/...">
          <p class="mt-1 text-xs text-gray-400">Username yoki to‘liq link yozishingiz mumkin.</p>
        </div>
        <div>
          <label class="block text-xs font-semibold text-gray-500 mb-1">Telegram</label>
          <input name="telegram_url" value="{{ old('telegram_url', $blogger->telegram_url ?? '') }}" class="input" placeholder="@username yoki https://t.me/...">
          <p class="mt-1 text-xs text-gray-400">Username yoki to‘liq link yozishingiz mumkin.</p>
        </div>
        <div>
          <label class="block text-xs font-semibold text-gray-500 mb-1">YouTube linki</label>
          <input name="youtube_url" value="{{ old('youtube_url', $blogger->youtube_url ?? '') }}" class="input" placeholder="https://youtube.com/...">
        </div>
        <div>
          <label class="block text-xs font-semibold text-gray-500 mb-1">TikTok linki</label>
          <input name="tiktok_url" value="{{ old('tiktok_url', $blogger->tiktok_url ?? '') }}" class="input" placeholder="https://tiktok.com/...">
        </div>
      </div>

      <div class="flex items-center gap-3 mt-6 pt-5 border-t border-gray-100 dark:border-white/5">
        <button type="submit" class="btn btn-primary">{{ $isEdit ? 'Saqlash' : "Qo'shish" }}</button>
        <a href="{{ $isEdit ? route('admin.bloggers.show', $blogger) : route('admin.bloggers.index') }}" class="btn btn-secondary">Bekor qilish</a>
      </div>
    </form>
  </div>
</div>
@endsection
