@props(['endpoint'])

@php [$method, $path, $description, $ability] = $endpoint; @endphp

<div class="endpoint">
  <div class="endpoint-top">
    <span class="method">{{ $method }}</span>
    <span class="path">{{ $path }}</span>
    <span class="ability">{{ $ability }}</span>
  </div>
  <p>{{ $description }}</p>
</div>
