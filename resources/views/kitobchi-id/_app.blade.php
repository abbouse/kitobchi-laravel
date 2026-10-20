<div class="app">
    @if ($client->logo_url && str_starts_with($client->logo_url, 'https://'))
        <img src="{{ $client->logo_url }}" alt="" referrerpolicy="no-referrer">
    @else
        <span class="ph">{{ mb_strtoupper(mb_substr($client->name, 0, 1)) }}</span>
    @endif
    <div>
        <b>{{ $client->name }}</b>
        @if ($client->homepage_url)
            <small>{{ parse_url($client->homepage_url, PHP_URL_HOST) ?: $client->homepage_url }}</small>
        @endif
    </div>
</div>
