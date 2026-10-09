@switch($block['type'])
    @case('code')
        <figure class="code-panel">
            <figcaption><svg aria-hidden="true" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="m8 7-5 5 5 5m8-10 5 5-5 5m-3-14-2 18"/></svg> {{ $block['language'] ?: 'Kode' }}</figcaption>
            <pre tabindex="0" aria-label="Contoh kode"><code>{{ $block['text'] }}</code></pre>
        </figure>
        @break
    @case('image')
        <figure class="slide-image" x-data="{ failed: false }">
            <img src="{{ $block['url'] }}" alt="{{ $block['text'] }}" loading="lazy" decoding="async" x-on:error="failed = true" x-show="!failed">
            <p x-show="failed" x-cloak class="p-4 text-slate-300">Gambar tidak dapat dimuat.</p>
            @if($block['text'])<figcaption>{{ $block['text'] }}</figcaption>@endif
        </figure>
        @break
    @case('table')
        <div class="comparison-scroll" tabindex="0" role="region" aria-label="Tabel perbandingan">
            <table>
                <thead><tr>@foreach($block['headers'] as $cell)<th scope="col">{{ $cell }}</th>@endforeach</tr></thead>
                <tbody>@foreach($block['rows'] as $row)<tr>@foreach($row as $cell)<td>{!! $presenter->signalText(e($cell)) !!}</td>@endforeach</tr>@endforeach</tbody>
            </table>
        </div>
        @break
    @case('heading')
        <h3 class="text-xl font-semibold text-indigo-200">{{ $block['text'] }}</h3>
        @break
    @case('quote')
        <blockquote class="slide-quote">{!! $presenter->signalText(e($block['text'])) !!}</blockquote>
        @break
    @case('callout')
        <aside class="learning-callout" data-label="{{ $block['label'] }}">
            <h3 class="slide-kicker">
                @switch($block['label'])
                    @case('PENTING') <svg aria-hidden="true" class="inline w-4 h-4 mr-1 text-red-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 9v4m0 4h.01M12 2l10 18H2L12 2Z"/></svg> @break
                    @case('TIPS') <svg aria-hidden="true" class="inline w-4 h-4 mr-1 text-green-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 18h6m-5 4h4M12 2a7 7 0 0 1 4 12.7V17H8v-2.3A7 7 0 0 1 12 2Z"/></svg> @break
                    @case('CONTOH') <svg aria-hidden="true" class="inline w-4 h-4 mr-1 text-violet-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m8 7-5 5 5 5m8-10 5 5-5 5"/></svg> @break
                    @case('INGAT')
                    @case('PERHATIKAN') <svg aria-hidden="true" class="inline w-4 h-4 mr-1 text-yellow-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2v4m0 12v4m-7-7H1m22 0h-4m-2.6-7.4 2.8-2.8M4.2 19.8l2.8-2.8m0-10L4.2 4.2m15.6 15.6-2.8-2.8"/></svg> @break
                    @default <svg aria-hidden="true" class="inline w-4 h-4 mr-1 text-indigo-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4m0-4h.01"/></svg>
                @endswitch
                {{ $block['label'] }}
            </h3>
            <p>{!! $presenter->signalText(e($block['text'])) !!}</p>
        </aside>
        @break
    @default
        {{-- ITEM / PARAGRAPH: Layout-aware rendering --}}
        <div class="learning-point">
            @if($layout === 'process' && $block['type'] === 'item')
                {{-- Process step marker --}}
                <span class="step-marker" aria-label="Langkah {{ $block['marker'] }}">{{ rtrim($block['marker'], '.)') }}</span>
            @elseif($layout === 'summary')
                {{-- Summary checkmark --}}
                <svg class="point-icon" aria-hidden="true" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m5 12 4 4L19 6"/></svg>
            @elseif($layout === 'comparison')
                {{-- Comparison diamond icon --}}
                <svg class="point-icon" aria-hidden="true" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="m12 3 9 9-9 9-9-9 9-9Z"/><path d="M9 12h6"/></svg>
            @elseif($layout === 'key-points')
                {{-- Key point diamond --}}
                <svg class="point-icon" aria-hidden="true" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="m12 3 9 9-9 9-9-9 9-9Z"/><path d="M12 8v8m-4-4h8"/></svg>
            @elseif($layout === 'example')
                {{-- Example lightbulb --}}
                <svg class="point-icon" aria-hidden="true" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M9 18h6m-5 4h4M12 2a7 7 0 0 1 4 12.7V17H8v-2.3A7 7 0 0 1 12 2Z"/></svg>
            @elseif($layout === 'checkpoint')
                {{-- Checkpoint question mark --}}
                <svg class="point-icon" aria-hidden="true" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/><path d="M12 17h.01"/></svg>
            @elseif($layout === 'concept')
                {{-- Concept: no icon, clean text --}}
            @endif
            <div class="min-w-0">
                @if($block['label'])<h3 class="point-label">{!! $presenter->signalText(e($block['label'])) !!}</h3>@endif
                <p class="point-text">
                    @if($block['type'] === 'item' && $block['ordered'] && !in_array($layout, ['process', 'key-points']))
                        <span class="text-[color:var(--slide-accent)]">{{ $block['marker'] }}</span>
                    @endif
                    {!! $presenter->signalText(e($block['text'])) !!}
                </p>
            </div>
        </div>
@endswitch
