@extends('a122.layouts.admin')
@section('title', $news->title)

@section('content')
<x-a122.page-header back-href="{{ route('admin.news.index') }}">
    <x-slot name="heading">{{ $news->title }}</x-slot>
    <x-slot name="meta">Bozor yangiligi, banner va target action tafsilotlari</x-slot>
    <x-slot name="actions">
        <form method="POST" action="{{ route('admin.news.toggle', $news) }}">
            @csrf
            @method('PATCH')
            <button type="submit" class="btn-p {{ $news->status ? 'ghost' : 'primary' }}">
                <i class="bi {{ $news->status ? 'bi-eye-slash' : 'bi-eye' }}"></i>
                {{ $news->status ? 'Yashirish' : 'Faollashtirish' }}
            </button>
        </form>
        <a href="{{ route('admin.news.edit', $news) }}" class="btn-p primary">
            <i class="bi bi-pencil"></i> Tahrirlash
        </a>
        <form method="POST" action="{{ route('admin.news.destroy', $news) }}" onsubmit="return confirm('Bu yangilik o\'chirilsinmi?')">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn-p danger">
                <i class="bi bi-trash"></i> O'chirish
            </button>
        </form>
    </x-slot>
</x-a122.page-header>

<div class="grid grid-cols-1 xl:grid-cols-12 gap-4">
    <div class="xl:col-span-8 space-y-4">
        <div class="p-card">
            <div class="p-card-header">
                <div>
                    <div class="p-card-title"><i class="bi bi-image mr-2" style="color:var(--p-accent)"></i>Banner va kontent</div>
                    <div class="p-card-sub">Foydalanuvchi ilovasida ko‘rinadigan asosiy blok</div>
                </div>
                <span class="s-pill {{ $news->status ? 'success' : 'danger' }}">
                    {{ $news->status ? 'Faol' : 'Yashirin' }}
                </span>
            </div>

            @if($news->imgUrl)
                <img
                    src="{{ Str::startsWith($news->imgUrl, 'http') ? $news->imgUrl : asset('storage/' . $news->imgUrl) }}"
                    alt="{{ $news->title }}"
                    style="width:100%;max-height:380px;object-fit:cover;border-radius:18px;border:1px solid var(--p-border)"
                >
            @else
                <div class="hero-panel" style="min-height:220px;display:flex;align-items:center;justify-content:center">
                    <div style="text-align:center;color:var(--p-hint)">
                        <i class="bi bi-card-image" style="font-size:42px;display:block;margin-bottom:8px"></i>
                        Banner rasmi yuklanmagan
                    </div>
                </div>
            @endif

            <div class="content-prose" style="margin-top:18px">
                <h3>Tavsif</h3>
                <p>{{ $news->description ?: 'Tavsif kiritilmagan.' }}</p>
            </div>
        </div>

        <div class="p-card">
            <div class="p-card-header">
                <div>
                    <div class="p-card-title"><i class="bi bi-cursor mr-2" style="color:var(--p-info)"></i>Action target</div>
                    <div class="p-card-sub">Yangilik bosilganda foydalanuvchi qayerga o‘tadi</div>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                <div class="data-kv">
                    <div class="label">Action</div>
                    <div class="value">{{ $news->action_label }}</div>
                </div>
                <div class="data-kv">
                    <div class="label">Action kodi</div>
                    <div class="value">{{ $news->action }}</div>
                </div>
                <div class="data-kv">
                    <div class="label">Action ID</div>
                    <div class="value">{{ $news->action_id ? '#'.$news->action_id : '—' }}</div>
                </div>
                <div class="data-kv">
                    <div class="label">Align</div>
                    <div class="value">{{ $news->align ?: '—' }}</div>
                </div>
            </div>

            <div class="mt-4">
                @if($target)
                    <div class="module-link-card">
                        <div>
                            <div class="module-link-card__title">{{ $target->shop_name ?? $target->name ?? ('#'.$target->id) }}</div>
                            <div class="module-link-card__meta">Bog‘langan target topildi va action bilan mos.</div>
                        </div>
                        <div class="s-pill accent">ID {{ $target->id }}</div>
                    </div>
                @elseif($news->action !== 'news')
                    <div class="p-quote-block">
                        Action ID bor, lekin bog‘langan obyekt topilmadi. Bu odatda o‘chirilgan shop yoki mahsulotga ishora qiladi.
                    </div>
                @endif
            </div>
        </div>
    </div>

    <div class="xl:col-span-4 space-y-4">
        <div class="p-card">
            <div class="p-card-header">
                <div class="p-card-title"><i class="bi bi-info-circle mr-2" style="color:var(--p-warning)"></i>Meta</div>
            </div>
            <div class="space-y-3">
                <div class="data-kv">
                    <div class="label">ID</div>
                    <div class="value">#{{ $news->id }}</div>
                </div>
                <div class="data-kv">
                    <div class="label">Yaratilgan</div>
                    <div class="value">{{ $news->created_at?->format('d.m.Y H:i') ?: '—' }}</div>
                </div>
                <div class="data-kv">
                    <div class="label">Yangilangan</div>
                    <div class="value">{{ $news->updated_at?->format('d.m.Y H:i') ?: '—' }}</div>
                </div>
                <div class="data-kv">
                    <div class="label">Status</div>
                    <div class="value">
                        <span class="s-pill {{ $news->status ? 'success' : 'danger' }}">
                            {{ $news->status ? 'Faol' : 'Yashirin' }}
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <div class="p-card">
            <div class="p-card-header">
                <div class="p-card-title"><i class="bi bi-lightning-charge mr-2" style="color:var(--p-accent)"></i>Admin eslatma</div>
            </div>
            <div class="p-quote-block">
                `to_shop` va `to_product` actionlarida target doimo mavjud bo‘lishi kerak. Aks holda banner bosilganda foydalanuvchi oqimi uziladi.
            </div>
        </div>
    </div>
</div>
@endsection
