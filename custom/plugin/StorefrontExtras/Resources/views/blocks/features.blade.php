@if ($items !== [])
    <section class="mb-10 grid gap-6 rounded-[var(--radius-theme)] bg-slate-50 p-6 sm:grid-cols-2 md:grid-cols-4" data-block="extras_features">
        @foreach ($items as $item)
            <div class="flex gap-3">
                <svg aria-hidden="true" viewBox="0 0 24 24" class="h-8 w-8 shrink-0 text-primary" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="{{ $item['icon_path'] }}"/></svg>
                <div>
                    <p class="font-medium">{{ $item['title'] }}</p>
                    @if ($item['text'] !== '')<p class="text-sm text-slate-600">{{ $item['text'] }}</p>@endif
                </div>
            </div>
        @endforeach
    </section>
@endif
