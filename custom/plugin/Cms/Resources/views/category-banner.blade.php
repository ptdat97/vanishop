{{-- Banner đầu trang danh mục (store-content §3.3), trước bộ lọc. Ảnh mobile thiếu → ảnh desktop. --}}
<section class="relative mb-6 overflow-hidden rounded-[var(--radius-theme)] bg-slate-100" data-vani-cms-category-banner>
    @if ($banner['desktop'])
        <picture>
            @if ($banner['mobile'])<source media="(max-width: 767px)" srcset="{{ $banner['mobile'] }}">@endif
            <img src="{{ $banner['desktop'] }}" alt="{{ $banner['title'] ?? '' }}" class="h-48 w-full object-cover md:h-72" fetchpriority="high">
        </picture>
    @endif
    @if ($banner['title'] || $banner['html'] || $banner['button_url'])
        <div @class(['p-6', 'absolute inset-0 flex flex-col justify-center bg-black/30 text-white' => $banner['desktop']])>
            @if ($banner['title'])<h2 class="text-2xl font-semibold">{{ $banner['title'] }}</h2>@endif
            @if ($banner['html'])<div class="vani-prose mt-2 max-w-xl">{!! $banner['html'] !!}</div>@endif
            @if ($banner['button_url'])
                <a href="{{ $banner['button_url'] }}" class="mt-4 inline-block w-fit rounded-[var(--radius-theme)] bg-primary px-4 py-2 text-sm font-medium text-white">{{ $banner['button_text'] }}</a>
            @endif
        </div>
    @endif
</section>
