@if ($cells !== [])
    <section @class(['mb-10 grid gap-4', 'md:grid-cols-2' => $layout === 2 || $layout === 4, 'md:grid-cols-3 md:grid-rows-2' => $layout === 3]) data-block="extras_banner_grid">
        @foreach ($cells as $cell)
            <a href="{{ $cell['href'] }}" @class(['group relative block overflow-hidden rounded-[var(--radius-theme)]', 'md:col-span-2 md:row-span-2' => $layout === 3 && $cell['wide']])>
                {{-- Partial ảnh của theme tính `sizes` từ `image_layout` (BannerGridBlock dựng sẵn). --}}
                @include('theme::partials.image', ['src' => $cell['image'], 'srcset' => $cell['srcset'], 'alt' => $cell['title'], 'layout' => $cell['image_layout'], 'class' => 'h-full max-h-[480px] w-full object-cover transition group-hover:scale-105'])
                @if ($cell['title'] !== '')<span class="absolute bottom-3 left-3 rounded bg-white/90 px-3 py-1 text-sm font-medium text-slate-900">{{ $cell['title'] }}</span>@endif
            </a>
        @endforeach
    </section>
@endif
