@php
    $v = $vacancy ?? null;
@endphp

@if($errors->any())
    <div class="mb-4 rounded-lg border border-red-500/40 bg-red-500/10 px-4 py-3 text-sm text-red-200">
        <ul class="list-inside list-disc space-y-1">
            @foreach($errors->all() as $err)
                <li>{{ $err }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="card-panel mb-4">
    <div class="p-4 space-y-3">
        <div>
            <label class="p-label">Lavozim nomi *</label>
            <input type="text" name="title" class="p-form-control" required maxlength="255"
                   value="{{ old('title', $v->title ?? '') }}">
        </div>
        <div>
            <label class="p-label">Ikonka (karyera sahifasida)</label>
            <select name="icon" class="p-form-control">
                <option value="">— standart (portfel) —</option>
                @foreach(\App\Models\Vacancy::iconOptions() as $slug => $label)
                    <option value="{{ $slug }}" {{ old('icon', $v->icon ?? '') === $slug ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="grid gap-3 md:grid-cols-2">
            <div>
                <label class="p-label">Shartnoma turi</label>
                <input type="text" name="contract_type" class="p-form-control" maxlength="120"
                       placeholder="Masalan: To‘liq stavka, masofaviy"
                       value="{{ old('contract_type', $v->contract_type ?? '') }}">
            </div>
            <div>
                <label class="p-label">Joylashuv</label>
                <input type="text" name="location" class="p-form-control" maxlength="255"
                       placeholder="Toshkent yoki Masofaviy"
                       value="{{ old('location', $v->location ?? '') }}">
            </div>
        </div>
        <div>
            <label class="p-label">Tavsif * (oʻzbekcha)</label>
            <textarea name="description" class="p-form-control" rows="10" required
                      placeholder="Vazifalar, talablar, ish sharoiti...">{{ old('description', $v->description ?? '') }}</textarea>
        </div>

        <div class="rounded-lg border border-slate-600/40 bg-slate-900/30 p-4">
            <div class="mb-3 text-sm font-semibold text-slate-100">Tarjimalar (ixtiyoriy)</div>
            <p class="mb-4 text-xs text-slate-400">Rus, ingliz yoki yapon tilidagi sarlavha va tavsifni kiriting. Boʻsh qoldirsangiz, sayt oʻzbekcha matndan foydalanadi.</p>
            @foreach (['ru' => 'Русский', 'en' => 'English', 'ja' => '日本語'] as $loc => $label)
                @php
                    $tr = ($v?->translations ?? collect())->firstWhere('locale', $loc);
                @endphp
                <div class="mb-6 rounded-md border border-slate-700/60 p-3 last:mb-0">
                    <div class="mb-2 text-xs font-bold uppercase tracking-wide text-slate-300">{{ $label }}</div>
                    <div class="mb-2">
                        <label class="p-label">Lavozim nomi</label>
                        <input type="text" name="translations[{{ $loc }}][title]" class="p-form-control" maxlength="255"
                               value="{{ old('translations.'.$loc.'.title', $tr->title ?? '') }}">
                    </div>
                    <div class="grid gap-3 md:grid-cols-2 mb-2">
                        <div>
                            <label class="p-label">Shartnoma turi</label>
                            <input type="text" name="translations[{{ $loc }}][contract_type]" class="p-form-control" maxlength="120"
                                   value="{{ old('translations.'.$loc.'.contract_type', $tr->contract_type ?? '') }}">
                        </div>
                        <div>
                            <label class="p-label">Joylashuv</label>
                            <input type="text" name="translations[{{ $loc }}][location]" class="p-form-control" maxlength="255"
                                   value="{{ old('translations.'.$loc.'.location', $tr->location ?? '') }}">
                        </div>
                    </div>
                    <div>
                        <label class="p-label">Tavsif</label>
                        <textarea name="translations[{{ $loc }}][description]" class="p-form-control" rows="6"
                                  placeholder="...">{{ old('translations.'.$loc.'.description', $tr->description ?? '') }}</textarea>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="grid gap-3 md:grid-cols-2">
            <div>
                <label class="p-label">Tartib raqami</label>
                <input type="number" name="sort_order" class="p-form-control" min="0" step="1"
                       value="{{ old('sort_order', $v->sort_order ?? 0) }}">
            </div>
            <div class="flex items-end pb-1">
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="hidden" name="is_active" value="0">
                    <input type="checkbox" name="is_active" value="1" class="rounded border-slate-600"
                           {{ old('is_active', $v->is_active ?? true) ? 'checked' : '' }}>
                    <span class="text-sm text-slate-200">Saytda ko‘rsatish (faol)</span>
                </label>
            </div>
        </div>
    </div>
</div>

<div class="flex flex-wrap gap-2">
    <button type="submit" class="btn-p primary">
        <i class="bi bi-check-lg"></i> Saqlash
    </button>
    <a href="{{ route('admin.jobs.index') }}" class="btn-p ghost">Bekor qilish</a>
</div>
