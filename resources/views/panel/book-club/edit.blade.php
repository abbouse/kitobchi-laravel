@extends('panel.layouts.panel')
@section('title', 'Post tahrirlash #'.$bookClub->id)
@section('page-title', 'Post tahrirlash')

@section('content')
<div class="kc-page-inner w-full min-w-0">
    <x-panel.page-header back-href="{{ route('panel.book-club.show', $bookClub) }}">
  <x-slot name="heading">Post #{{ $bookClub->id }} tahrirlash</x-slot>
  <x-slot name="meta">Muallif:
          <a href="{{ route('panel.users.show', $bookClub->user_id) }}"
             style="color:var(--p-accent)">
            {{ $bookClub->user?->name }} {{ $bookClub->user?->lastname }}
          </a></x-slot>
</x-panel.page-header>


    <form method="POST" action="{{ route('panel.book-club.update', $bookClub) }}">
      @csrf @method('PUT')

      {{-- Matn --}}
      <div class="p-card mb-3 fade-up">
        <div class="dash-card-head"><div class="dash-card-title">Post matni</div></div>
        <div class="dash-card-body">
          <textarea name="text" rows="6"
                    class="p-form-control @error('text') is-invalid @enderror"
                    required maxlength="2000"
                    placeholder="Post matni...">{{ old('text', $bookClub->text) }}</textarea>
          @error('text')<div class="invalid-feedback">{{ $message }}</div>@enderror
          <div style="font-size:11px;color:var(--p-hint);margin-top:4px;text-align:right">
            {{ strlen($bookClub->text) }} / 2000
          </div>
        </div>
      </div>

      {{-- Rasmlar (faqat ko'rish + o'chirish) --}}
      @if($bookClub->images->count())
      <div class="p-card mb-3 fade-up">
        <div class="dash-card-head">
          <div class="dash-card-title">Rasmlar</div>
          <div class="dash-card-sub">O'chirish uchun X bosing</div>
        </div>
        <div class="dash-card-body">
          <div class="flex flex-wrap gap-2">
            @foreach($bookClub->images as $img)
            <div style="position:relative">
              <img src="{{ asset('storage/'.$img->image) }}"
                   style="width:90px;height:90px;border-radius:8px;object-fit:cover;
                          border:1px solid var(--p-border)">
              <form method="POST"
                    action="{{ route('panel.book-club.image.delete', $img) }}"
                    style="position:absolute;top:4px;right:4px"
                    onsubmit="return confirm('Rasm o\'chirilsinmi?')">
                @csrf @method('DELETE')
                <button style="width:22px;height:22px;border-radius:50%;
                               background:rgba(0,0,0,.65);border:none;color:#fff;
                               font-size:10px;cursor:pointer;
                               display:flex;align-items:center;justify-content:center">
                  <i class="bi bi-x"></i>
                </button>
              </form>
            </div>
            @endforeach
          </div>
        </div>
      </div>
      @endif

      {{-- So'rovnoma (faqat ko'rish) --}}
      @if($bookClub->votes->count())
      <div class="p-card mb-3 fade-up" style="opacity:.7">
        <div class="dash-card-head">
          <div class="dash-card-title">So'rovnoma</div>
          <div class="dash-card-sub"><i class="bi bi-lock"></i> Faqat ko'rish</div>
        </div>
        <div class="dash-card-body">
          @foreach($bookClub->votes as $vote)
          <div style="background:var(--p-elevated);border-radius:7px;padding:10px 14px;margin-bottom:8px;
                      font-size:13px;color:var(--p-text)">
            {{ $vote->option_text }}
          </div>
          @endforeach
        </div>
      </div>
      @endif

      {{-- Ogohlantirish --}}
      <div style="padding:12px 16px;background:var(--p-warning-d);border-radius:8px;
                  border:1px solid rgba(245,166,35,.2);margin-bottom:20px" class="fade-up">
        <div style="font-size:12px;color:var(--p-warning);display:flex;gap:8px">
          <i class="bi bi-exclamation-triangle-fill" style="flex-shrink:0"></i>
          Faqat matnni tahrirlash mumkin. Rasmlar va so'rovnomalar alohida boshqariladi.
        </div>
      </div>

      <div class="flex gap-2 justify-end fade-up">
        <a href="{{ route('panel.book-club.show', $bookClub) }}" class="btn-p ghost">
          Bekor qilish
        </a>
        <button type="submit" class="btn-p primary">
          <i class="bi bi-check-lg"></i> Saqlash
        </button>
      </div>
    </form>
</div>
@endsection