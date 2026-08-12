{!! '<'.'?' . 'xml version="1.0" encoding="UTF-8"'.'?' . '>' !!}
<rss version="2.0" xmlns:g="http://base.google.com/ns/1.0">
  <channel>
    <title>Kitobchi — Google Merchant Product Feed</title>
    <link>{{ url('/') }}</link>
    <description>Kitobchi kitob va kanselyariya marketpleysi mahsulotlar lentalari</description>
    @foreach($books as $book)
      @php
        $slug = \Illuminate\Support\Str::slug($book->name);
        $link = route('web.books.show', ['id' => $book->id, 'slug' => $slug]);
        $img = $book->first_image ? asset('storage/' . $book->first_image) : url('/images/logo/logo_blue.png');
        $price = $book->discountPrice && $book->discountPrice < $book->price ? $book->discountPrice : $book->price;
        $inStock = $book->count > 0;
      @endphp
      <item>
        <g:id>BOOK-{{ $book->id }}</g:id>
        <g:title><![CDATA[{{ $book->name }}]]></g:title>
        <g:description><![CDATA[{{ \Illuminate\Support\Str::limit(strip_tags($book->description ?? $book->name), 400) }}]]></g:description>
        <g:link>{{ $link }}</g:link>
        <g:image_link>{{ $img }}</g:image_link>
        <g:availability>{{ $inStock ? 'in_stock' : 'out_of_stock' }}</g:availability>
        <g:price>{{ number_format($price, 2, '.', '') }} UZS</g:price>
        <g:condition>new</g:condition>
        <g:brand><![CDATA[{{ $book->publisher?->name ?: ($book->author ?: 'Kitobchi') }}]]></g:brand>
        @if($book->isbn)
          <g:gtin>{{ preg_replace('/[^0-9]/', '', $book->isbn) }}</g:gtin>
          <g:identifier_exists>yes</g:identifier_exists>
        @else
          <g:identifier_exists>no</g:identifier_exists>
        @endif
      </item>
    @endforeach
  </channel>
</rss>
