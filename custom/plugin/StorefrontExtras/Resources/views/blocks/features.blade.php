@php
    $paths = [
        'truck' => 'M3 7h11v8H3zM14 10h4l3 3v2h-7zM7 18a1.5 1.5 0 1 0 0-.01M17 18a1.5 1.5 0 1 0 0-.01',
        'return' => 'M4 9h11a5 5 0 0 1 0 10H8M4 9l4-4M4 9l4 4',
        'shield' => 'M12 3l7 3v5c0 5-3.5 8.5-7 10-3.5-1.5-7-5-7-10V6z M9 12l2 2 4-4',
        'support' => 'M4 13v-1a8 8 0 0 1 16 0v1M4 13h3v5H4zM17 13h3v5h-3z',
        'gift' => 'M4 10h16v10H4zM3 7h18v3H3zM12 7v13M12 7c-2-4-6-3-5 0M12 7c2-4 6-3 5 0',
        'card' => 'M3 6h18v12H3zM3 10h18M7 15h4',
    ];
@endphp
@if ($items !== [])
    <section class="mb-10 grid gap-6 rounded-[var(--radius-theme)] bg-slate-50 p-6 sm:grid-cols-2 md:grid-cols-4" data-block="extras_features">
        @foreach ($items as $item)
            <div class="flex gap-3">
                <svg aria-hidden="true" viewBox="0 0 24 24" class="h-8 w-8 shrink-0 text-primary" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="{{ $paths[$item['icon']] }}"/></svg>
                <div>
                    <p class="font-medium">{{ $item['title'] }}</p>
                    @if ($item['text'] !== '')<p class="text-sm text-slate-600">{{ $item['text'] }}</p>@endif
                </div>
            </div>
        @endforeach
    </section>
@endif
