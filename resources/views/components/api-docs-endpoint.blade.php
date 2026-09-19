@props(['endpoint'])

@php
    $ep      = $endpoint;
    $samples = $ep['samples'] ?? [];
    $uid     = $ep['id'] ?? \Illuminate\Support\Str::random(6);
    $hasTry  = strtoupper($ep['method']) === 'GET';
    $ability = $ep['ability'] ?? 'read';

    $hasPath  = ! empty($ep['path_params']);
    $hasQuery = ! empty($ep['query_params']);
    $hasBody  = ! empty($ep['body_params']);
    $hasParams = $hasPath || $hasQuery || $hasBody;
@endphp

<style>
  /* Endpoint card — scoped here so it doesn't bleed */
  .ep {
    border: 1px solid var(--border-med);
    border-radius: 9px; background: var(--panel);
    margin: 20px 0; overflow: hidden;
    scroll-margin-top: calc(var(--header-h) + 16px);
  }
  .ep-head {
    display: flex; align-items: center; gap: 10px;
    padding: 10px 14px;
    border-bottom: 1px solid var(--border);
    background: var(--bg);
  }
  .ep-path-str {
    font-family: 'JetBrains Mono', monospace;
    font-size: 13px; font-weight: 500;
    color: var(--text); word-break: break-all; flex: 1;
  }
  .ep-tags { display: flex; gap: 6px; flex-shrink: 0; }
  .ep-tag {
    font-size: 10.5px; font-weight: 600; padding: 2px 7px;
    border-radius: 3px; white-space: nowrap;
  }
  .ep-tag-cached { background: var(--bg-hover); color: var(--faint); border: 1px solid var(--border); }
  .ep-tag-write  { background: rgba(245,158,11,0.07); color: #b45309; border: 1px solid rgba(245,158,11,0.18); }
  html[data-theme="dark"] .ep-tag-write { color: #fbbf24; }

  .ep-body { padding: 16px 18px; }
  .ep-title { font-size: 15px; font-weight: 600; margin-bottom: 5px; }
  .ep-summary { font-size: 13.5px; color: var(--muted); line-height: 1.65; margin-bottom: 14px; }
  .ep-summary a { color: var(--link); }

  .ep-params-label {
    font-size: 11px; font-weight: 600; letter-spacing: 0.04em;
    text-transform: uppercase; color: var(--faint);
    margin: 14px 0 8px;
  }

  .ep-resp-head {
    display: flex; align-items: center; justify-content: space-between;
    margin: 16px 0 6px;
  }
  .ep-resp-label {
    font-size: 12px; font-weight: 600; color: var(--text-2);
    display: flex; align-items: center; gap: 7px;
  }
  .badge-200 {
    font-size: 10.5px; font-weight: 700; padding: 1.5px 6px; border-radius: 3px;
    background: rgba(16,185,129,0.08); color: #059669;
  }
  html[data-theme="dark"] .badge-200 { color: #34d399; }

  .ep-resp-copy {
    font: 500 11px 'Inter', sans-serif;
    background: var(--bg); color: var(--faint);
    border: 1px solid var(--border-med); border-radius: 4px;
    padding: 2px 8px;
    transition: all 0.12s;
  }
  .ep-resp-copy:hover { color: var(--text-2); }
  .ep-resp-copy.ok { color: #10b981; border-color: rgba(16,185,129,0.3); }
</style>

<div class="ep" id="{{ $uid }}">

  {{-- Header: method + path + tags --}}
  <div class="ep-head">
    <span class="method m-{{ $ep['method'] }}">{{ $ep['method'] }}</span>
    <span class="ep-path-str">{{ $ep['full_path'] ?? $ep['path'] }}</span>
    <div class="ep-tags">
      @if(! empty($ep['cache']))
        <span class="ep-tag ep-tag-cached">Kesh</span>
      @endif
      @if($ability === 'stock:write')
        <span class="ep-tag ep-tag-write">stock:write</span>
      @endif
    </div>
  </div>

  {{-- Body --}}
  <div class="ep-body">

    @if(! empty($ep['title']))
      <div class="ep-title">{{ $ep['title'] }}</div>
    @endif
    @if(! empty($ep['summary']))
      <p class="ep-summary">{!! $ep['summary'] !!}</p>
    @endif

    {{-- Parameters --}}
    @if($hasParams)
      @if($hasPath)
        <div class="ep-params-label">Path parametrlari</div>
        <table class="doc-table">
          <thead><tr><th>Parametr</th><th>Turi</th><th>Tavsifi</th></tr></thead>
          <tbody>
            @foreach($ep['path_params'] as $p)
              <tr>
                <td><span class="ic">{{ $p['name'] }}</span> <span class="chip chip-req" style="font-size:10px">majburiy</span></td>
                <td><span class="chip chip-type">{{ $p['type'] ?? 'string' }}</span></td>
                <td>{!! $p['desc'] ?? '' !!}</td>
              </tr>
            @endforeach
          </tbody>
        </table>
      @endif

      @if($hasQuery)
        <div class="ep-params-label">Query parametrlari</div>
        <table class="doc-table">
          <thead><tr><th>Parametr</th><th>Turi</th><th>Talab</th><th>Tavsifi</th></tr></thead>
          <tbody>
            @foreach($ep['query_params'] as $p)
              <tr>
                <td><span class="ic">{{ $p['name'] }}</span></td>
                <td><span class="chip chip-type">{{ $p['type'] ?? 'string' }}</span></td>
                <td>
                  @if($p['required'] ?? false)
                    <span class="chip chip-req">majburiy</span>
                  @else
                    <span class="chip chip-opt">ixtiyoriy</span>
                  @endif
                </td>
                <td>{!! $p['desc'] ?? '' !!}</td>
              </tr>
            @endforeach
          </tbody>
        </table>
      @endif

      @if($hasBody)
        <div class="ep-params-label">Body parametrlari</div>
        <table class="doc-table">
          <thead><tr><th>Parametr</th><th>Turi</th><th>Talab</th><th>Tavsifi</th></tr></thead>
          <tbody>
            @foreach($ep['body_params'] as $p)
              <tr>
                <td><span class="ic">{{ $p['name'] }}</span></td>
                <td><span class="chip chip-type">{{ $p['type'] ?? 'string' }}</span></td>
                <td>
                  @if($p['required'] ?? false)
                    <span class="chip chip-req">majburiy</span>
                  @else
                    <span class="chip chip-opt">ixtiyoriy</span>
                  @endif
                </td>
                <td>{!! $p['desc'] ?? '' !!}</td>
              </tr>
            @endforeach
          </tbody>
        </table>
      @endif
    @endif

    {{-- Request body JSON --}}
    @if(! empty($ep['request_json']))
      <div class="ep-params-label">So'rov tanasi</div>
      <div class="codeblock">
        <div class="codeblock-header">
          <span class="codeblock-title">json</span>
          <button class="copy-btn" type="button">Ko'chirish</button>
        </div>
        <pre>{{ $ep['request_json'] }}</pre>
      </div>
    @endif

    {{-- Code samples --}}
    @if(! empty($samples))
      <div class="code-tabs">
        <div class="ctabs-bar">
          <div class="ctabs-langs">
            @foreach($samples as $lang => $sample)
              <button type="button" class="ctab {{ $loop->first ? 'active' : '' }}" data-tab="{{ $uid }}-{{ $lang }}">
                {{ $sample['label'] }}
              </button>
            @endforeach
          </div>
          <button type="button" class="ctab-copy">Ko'chirish</button>
        </div>
        @foreach($samples as $lang => $sample)
          <div class="code-pane {{ $loop->first ? 'active' : '' }}" data-pane="{{ $uid }}-{{ $lang }}">
            <pre>{{ $sample['code'] }}</pre>
          </div>
        @endforeach
      </div>
    @endif

    {{-- Response --}}
    @if(! empty($ep['response_json']))
      <div class="ep-resp-head">
        <span class="ep-resp-label">
          Javob
          <span class="badge-200">200 OK</span>
        </span>
        <button class="ep-resp-copy" type="button" data-resp-copy>Ko'chirish</button>
      </div>
      <div class="codeblock ep-resp-block">
        <pre><code>{{ $ep['response_json'] }}</code></pre>
      </div>
    @endif

    {{-- Try It (GET only) --}}
    @if($hasTry)
      <details
        class="try-it"
        data-tryit
        data-url-template="{{ $ep['url_template'] ?? $ep['full_path'] ?? '' }}"
      >
        <summary class="try-summary">
          <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="9 18 15 12 9 6"/></svg>
          Sinab ko'rish
        </summary>
        <div class="try-body">
          <div class="try-fields">
            <div class="try-field">
              <div class="try-lbl">X-App-ID</div>
              <input class="try-inp" data-try="appid" placeholder="app_xxxxxxxxxxxx" autocomplete="off" spellcheck="false">
            </div>
            <div class="try-field">
              <div class="try-lbl">X-App-Secret</div>
              <input class="try-inp" data-try="secret" type="password" placeholder="your-secret" autocomplete="off">
            </div>
            @foreach($ep['path_params'] ?? [] as $p)
              <div class="try-field">
                <div class="try-lbl">{{ $p['name'] }}</div>
                <input class="try-inp" data-try-path="{{ $p['name'] }}" value="{{ $p['example'] ?? '' }}" placeholder="{{ $p['type'] ?? '' }}">
              </div>
            @endforeach
            @foreach($ep['query_params'] ?? [] as $p)
              <div class="try-field">
                <div class="try-lbl">{{ $p['name'] }}@if($p['required'] ?? false) <span style="color:#ef4444">*</span>@endif</div>
                <input class="try-inp" data-try-query="{{ $p['name'] }}" value="{{ $p['example'] ?? '' }}" placeholder="{{ $p['type'] ?? '' }}">
              </div>
            @endforeach
          </div>
          <div class="try-actions">
            <button type="button" class="try-send" data-try-send>Yuborish</button>
            <span class="try-status" data-try-status></span>
          </div>
          <pre class="try-result" data-try-result hidden></pre>
        </div>
      </details>
    @endif

  </div>
</div>

<script>
/* Response copy for this specific card */
(function() {
  document.querySelectorAll('[data-resp-copy]').forEach(function(btn) {
    if (btn._bound) return; btn._bound = true;
    btn.addEventListener('click', async function() {
      const block = btn.closest('.ep-body')?.querySelector('.ep-resp-block code');
      if (!block) return;
      try {
        await navigator.clipboard.writeText(block.innerText);
        const orig = btn.textContent;
        btn.classList.add('ok'); btn.textContent = 'Nusxalandi';
        setTimeout(() => { btn.classList.remove('ok'); btn.textContent = orig; }, 1500);
      } catch(_) {}
    });
  });
})();
</script>
