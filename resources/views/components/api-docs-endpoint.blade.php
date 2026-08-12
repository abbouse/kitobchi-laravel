@props(['endpoint'])

@php
    $ep = $endpoint;
    $samples = $ep['samples'] ?? [];
    $hasParams = ! empty($ep['path_params']) || ! empty($ep['query_params']) || ! empty($ep['body_params']);
    $uid = $ep['id'] ?? \Illuminate\Support\Str::random(6);
@endphp

<div class="endpoint" id="{{ $ep['id'] ?? '' }}">
  <!-- Endpoint Top Header -->
  <div class="endpoint-top">
    <span class="method method--{{ $ep['method'] }}">{{ $ep['method'] }}</span>
    <span class="path">{{ $ep['full_path'] ?? $ep['path'] }}</span>
    @if(! empty($ep['cache']))
      <span class="ability" title="GET javob qisqa muddat keshlanadi">⚡ Keshlanadi</span>
    @endif
    <span class="ability ability--{{ \Illuminate\Support\Str::slug($ep['ability'] ?? 'read') }}">{{ $ep['ability'] ?? 'read' }}</span>
  </div>

  <div class="endpoint-body">
    @if(! empty($ep['title']))
      <h3 class="endpoint-title">{{ $ep['title'] }}</h3>
    @endif
    @if(! empty($ep['summary']))
      <p class="endpoint-summary">{{ $ep['summary'] }}</p>
    @endif

    <!-- Parameters Table -->
    @if($hasParams)
      <table class="docs-table endpoint-params">
        <thead>
          <tr>
            <th>Parametr</th>
            <th>Turi</th>
            <th>Joyi</th>
            <th>Majburiy</th>
            <th>Tavsifi</th>
          </tr>
        </thead>
        <tbody>
          @foreach($ep['path_params'] ?? [] as $p)
            <tr>
              <td><span class="docs-inline">{{ $p['name'] }}</span></td>
              <td><span class="type-chip">{{ $p['type'] ?? 'string' }}</span></td>
              <td>path</td>
              <td><strong>Ha</strong></td>
              <td>{{ $p['desc'] ?? '' }}</td>
            </tr>
          @endforeach
          @foreach($ep['query_params'] ?? [] as $p)
            <tr>
              <td><span class="docs-inline">{{ $p['name'] }}</span></td>
              <td><span class="type-chip">{{ $p['type'] ?? 'string' }}</span></td>
              <td>query</td>
              <td>{{ ($p['required'] ?? false) ? 'Ha' : 'Yo‘q' }}</td>
              <td>{{ $p['desc'] ?? '' }}</td>
            </tr>
          @endforeach
          @foreach($ep['body_params'] ?? [] as $p)
            <tr>
              <td><span class="docs-inline">{{ $p['name'] }}</span></td>
              <td><span class="type-chip">{{ $p['type'] ?? 'string' }}</span></td>
              <td>body</td>
              <td>{{ ($p['required'] ?? false) ? 'Ha' : 'Yo‘q' }}</td>
              <td>{{ $p['desc'] ?? '' }}</td>
            </tr>
          @endforeach
        </tbody>
      </table>
    @endif

    <!-- Request Body JSON -->
    @if(! empty($ep['request_json']))
      <div class="endpoint-response">
        <div class="endpoint-response-head">
          <span>So'rov tanasi (Body) <em>JSON</em></span>
          <button type="button" class="code-copy" data-copy>Nusxalash</button>
        </div>
        <div class="docs-code response-code"><pre><code>{{ $ep['request_json'] }}</code></pre></div>
      </div>
    @endif

    <!-- Multi-Language Code Samples -->
    @if(! empty($samples))
      <div class="code-tabs" data-tabs>
        <div class="code-tabs-head">
          <div class="code-tabs-langs">
            @foreach($samples as $lang => $sample)
              <button type="button" class="code-tab {{ $loop->first ? 'active' : '' }}" data-tab="{{ $uid }}-{{ $lang }}">{{ $sample['label'] }}</button>
            @endforeach
          </div>
          <button type="button" class="code-copy" data-copy>Nusxalash</button>
        </div>
        @foreach($samples as $lang => $sample)
          <div class="code-pane {{ $loop->first ? 'active' : '' }}" data-pane="{{ $uid }}-{{ $lang }}">
            <pre><code>{{ $sample['code'] }}</code></pre>
          </div>
        @endforeach
      </div>
    @endif

    <!-- Response Preview -->
    @if(! empty($ep['response_json']))
      <div class="endpoint-response">
        <div class="endpoint-response-head">
          <span>Muvaffaqiyatli javob <em>200 OK</em></span>
          <button type="button" class="code-copy" data-copy>Nusxalash</button>
        </div>
        <div class="docs-code response-code"><pre><code>{{ $ep['response_json'] }}</code></pre></div>
      </div>
    @endif

    <!-- Interactive Try It Out Console (GET methods) -->
    @if(strtoupper($ep['method']) === 'GET')
      <details class="try-it" data-tryit data-url-template="{{ $ep['url_template'] ?? $ep['full_path'] ?? '' }}">
        <summary>⚡ Live Konsolda Sinab Ko'rish (Try It Out)</summary>
        <div class="try-it-body">
          <div class="try-grid">
            <label>X-App-ID<input data-try="appid" placeholder="app_xxxxxxxxxxxx" autocomplete="off"></label>
            <label>X-App-Secret<input data-try="secret" type="password" placeholder="your-secret" autocomplete="off"></label>
            @foreach($ep['path_params'] ?? [] as $p)
              <label>{{ $p['name'] }}<input data-try-path="{{ $p['name'] }}" value="{{ $p['example'] ?? '' }}"></label>
            @endforeach
            @foreach($ep['query_params'] ?? [] as $p)
              <label>{{ $p['name'] }}@if($p['required'] ?? false) <em style="color:#ef4444">*</em>@endif<input data-try-query="{{ $p['name'] }}" value="{{ $p['example'] ?? '' }}" placeholder="{{ $p['type'] ?? '' }}"></label>
            @endforeach
          </div>
          <button type="button" class="try-send" data-try-send>So'rovni Yuborish</button>
          <div class="try-status" data-try-status></div>
          <pre class="try-result" data-try-result hidden></pre>
        </div>
      </details>
    @endif
  </div>
</div>
