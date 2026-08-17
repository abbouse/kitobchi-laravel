{!! '<'.'?' . 'xml version="1.0" encoding="UTF-8"'.'?' . '>' !!}
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
    <!-- Asosiy sahifalar -->
    <url>
        <loc>{{ url('/') }}</loc>
        <lastmod>{{ date('Y-m-d') }}</lastmod>
        <changefreq>daily</changefreq>
        <priority>1.0</priority>
    </url>
    <url>
        <loc>{{ route('web.catalog') }}</loc>
        <lastmod>{{ date('Y-m-d') }}</lastmod>
        <changefreq>daily</changefreq>
        <priority>0.95</priority>
    </url>
    <url>
        <loc>{{ route('web.catalog', ['type' => 'book']) }}</loc>
        <lastmod>{{ date('Y-m-d') }}</lastmod>
        <changefreq>daily</changefreq>
        <priority>0.90</priority>
    </url>
    <url>
        <loc>{{ route('web.catalog', ['type' => 'stationery']) }}</loc>
        <lastmod>{{ date('Y-m-d') }}</lastmod>
        <changefreq>daily</changefreq>
        <priority>0.90</priority>
    </url>
    <url>
        <loc>{{ route('careers.index') }}</loc>
        <changefreq>weekly</changefreq>
        <priority>0.6</priority>
    </url>
    <url>
        <loc>{{ route('contact.index') }}</loc>
        <changefreq>monthly</changefreq>
        <priority>0.5</priority>
    </url>

    <!-- Huquqiy sahifalar -->
    @foreach($policies as $p)
    <url>
        <loc>{{ route('legal.policy', $p->slug) }}</loc>
        <lastmod>{{ $p->updated_at?->format('Y-m-d') ?? date('Y-m-d') }}</lastmod>
        <changefreq>monthly</changefreq>
        <priority>0.5</priority>
    </url>
    @endforeach

    <!-- Kitob toifalari -->
    @if(isset($bookCategories))
    @foreach($bookCategories as $bCat)
    <url>
        <loc>{{ route('web.catalog', ['type' => 'book', 'category' => $bCat->id]) }}</loc>
        <lastmod>{{ $bCat->updated_at?->format('Y-m-d') ?? date('Y-m-d') }}</lastmod>
        <changefreq>weekly</changefreq>
        <priority>0.85</priority>
    </url>
    @endforeach
    @endif

    <!-- Kanselyariya toifalari -->
    @if(isset($stationeryCategories))
    @foreach($stationeryCategories as $sCat)
    <url>
        <loc>{{ route('web.catalog', ['type' => 'stationery', 'category' => $sCat->id]) }}</loc>
        <lastmod>{{ $sCat->updated_at?->format('Y-m-d') ?? date('Y-m-d') }}</lastmod>
        <changefreq>weekly</changefreq>
        <priority>0.80</priority>
    </url>
    @endforeach
    @endif

    <!-- Barcha Kitoblar -->
    @foreach($books as $book)
    <url>
        <loc>{{ route('web.books.show', ['id' => $book->id, 'slug' => \Illuminate\Support\Str::slug($book->name)]) }}</loc>
        <lastmod>{{ $book->updated_at?->format('Y-m-d') ?? date('Y-m-d') }}</lastmod>
        <changefreq>weekly</changefreq>
        <priority>0.80</priority>
    </url>
    @endforeach

    <!-- Barcha Kanselyariya mahsulotlari -->
    @foreach($stationeries as $item)
    <url>
        <loc>{{ route('web.stationery.show', ['id' => $item->id, 'slug' => \Illuminate\Support\Str::slug($item->name)]) }}</loc>
        <lastmod>{{ $item->updated_at?->format('Y-m-d') ?? date('Y-m-d') }}</lastmod>
        <changefreq>weekly</changefreq>
        <priority>0.75</priority>
    </url>
    @endforeach
</urlset>
