@php
    $slug = \Illuminate\Support\Str::slug($book->name);
    $url = route('web.books.show', ['id' => $book->id, 'slug' => $slug]);
    $img = $book->first_image ? asset('storage/' . $book->first_image) : asset('images/logo/logo_blue.png');
    $isDisc = $book->discountPrice > 0 && $book->discountPrice < $book->price;
    $price = $isDisc ? $book->discountPrice : $book->price;
    $discPct = $isDisc ? round((($book->price - $price) / $book->price) * 100) : 0;
@endphp

<div style="position:relative;display:flex;flex-direction:column;border-radius:1rem;background:#fff;border:1px solid #f1f5f9;overflow:hidden;transition:all 0.3s;box-shadow:0 1px 3px rgba(0,0,0,0.05);"
     onmouseover="this.style.boxShadow='0 10px 25px rgba(0,0,0,0.1)'" onmouseout="this.style.boxShadow='0 1px 3px rgba(0,0,0,0.05)'">

    <!-- Image area with aspect 3/4 -->
    <a href="{{ $url }}" style="position:relative;width:100%;background:#f8fafc;overflow:hidden;display:block;aspect-ratio:3/4;">
        <img src="{{ $img }}"
             alt="{{ $book->name }}"
             style="width:100%;height:100%;object-fit:cover;transition:transform 0.5s;"
             loading="lazy"
             onmouseover="this.style.transform='scale(1.05)'" onmouseout="this.style.transform='scale(1)'">

        <!-- Discount badge -->
        @if($isDisc)
            <div style="position:absolute;bottom:0.5rem;left:0.5rem;z-index:10;">
                <span style="font-size:0.75rem;font-weight:700;border-radius:0.375rem;color:#fff;padding:0.2rem 0.4rem;background:#ED3131;">
                    -{{ $discPct }}%
                </span>
            </div>
        @endif

        <!-- Favorite button -->
        <button aria-label="Sevimlilar"
                onclick="event.preventDefault();this.querySelector('svg').style.fill='#ef4444';this.querySelector('svg').style.stroke='#ef4444';"
                style="position:absolute;top:0.5rem;right:0.5rem;z-index:10;width:2.25rem;height:2.25rem;display:flex;align-items:center;justify-content:center;border-radius:9999px;background:rgba(255,255,255,0.8);border:none;cursor:pointer;backdrop-filter:blur(4px);-webkit-backdrop-filter:blur(4px);transition:all 0.2s;">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#374151" stroke-width="2">
                <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/>
            </svg>
        </button>
    </a>

    <!-- Info area -->
    <div style="padding:0.875rem;display:flex;flex-direction:column;flex:1;">
        <a href="{{ $url }}" style="text-decoration:none;color:#111827;flex:1;">
            <div style="font-size:0.875rem;font-weight:600;line-height:1.4;overflow:hidden;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;margin-bottom:0.5rem;">
                {{ $book->name }}
            </div>
        </a>

        <div style="margin-top:auto;">
            <div style="font-size:1rem;font-weight:800;color:var(--color-tima-500);">
                {{ number_format($price) }} so'm
            </div>
            @if($isDisc)
                <div style="font-size:0.75rem;color:#9ca3af;text-decoration:line-through;">
                    {{ number_format($book->price) }} so'm
                </div>
            @endif

            <button onclick="addToCart({{ $book->id }}, '{{ addslashes($book->name) }}', {{ $price }}, '{{ $img }}')"
                    style="margin-top:0.75rem;width:100%;height:2.375rem;border:none;border-radius:9999px;background:var(--color-tima-500);color:#fff;font-size:0.8125rem;font-weight:700;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:0.375rem;transition:opacity 0.2s;"
                    onmouseover="this.style.opacity='0.9'" onmouseout="this.style.opacity='1'">
                <span>🛒</span> Savatga
            </button>
        </div>
    </div>
</div>
