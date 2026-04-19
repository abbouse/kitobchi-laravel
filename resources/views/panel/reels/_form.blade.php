{{-- resources/views/panel/reels/_form.blade.php --}}
{{-- $reel (null = create, object = edit) --}}

<div class="p-card mb-3 fade-up">
  <div class="p-card-header"><div class="p-card-title">Ma'lumotlar</div></div>
  <div style="padding:0 18px 18px">
    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">

      <div class="">
        <label class="p-form-label">
          Sarlavha <span style="color:var(--p-danger)">*</span>
        </label>
        <input type="text" name="title" class="p-form-control @error('title') border-danger @enderror"
               value="{{ old('title', $reel?->title) }}"
               placeholder="Reel sarlavhasi..." required>
        @error('title')
          <div style="font-size:11px;color:var(--p-danger);margin-top:4px">{{ $message }}</div>
        @enderror
      </div>

      <div class="md:col-span-8">
        <label class="p-form-label">Tavsif</label>
        <textarea name="description" class="p-form-control" rows="3"
                  placeholder="Ixtiyoriy tavsif...">{{ old('description', $reel?->description) }}</textarea>
      </div>

      <div class="md:col-span-4">
        <label class="p-form-label">
          Tartib (order) <span style="color:var(--p-danger)">*</span>
        </label>
        <input type="number" name="order" class="p-form-control @error('order') border-danger @enderror"
               value="{{ old('order', $reel?->order ?? $nextOrder ?? 1) }}"
               min="0" required>
        <div style="font-size:11px;color:var(--p-hint);margin-top:4px">
          Kichik raqam = oldin ko'rinadi
        </div>
        @error('order')
          <div style="font-size:11px;color:var(--p-danger);margin-top:4px">{{ $message }}</div>
        @enderror
      </div>

    </div>
  </div>
</div>

<div class="flex gap-2 fade-up">
  <button type="submit" class="btn-p primary">
    <i class="bi bi-check-lg"></i>
    {{ $reel ? 'Saqlash' : 'Yaratish' }}
  </button>
  <a href="{{ $reel ? route('panel.reels.show', $reel) : route('panel.reels.index') }}"
     class="btn-p ghost">
    Bekor
  </a>
</div>