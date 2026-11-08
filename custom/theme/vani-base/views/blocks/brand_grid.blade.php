<section class="mb-10" data-block="brand_grid" data-vani-motion="rise">
    @if ($title !== '')<h2 class="mb-4 text-lg font-semibold">{{ $title }}</h2>@endif
    <div class="flex flex-wrap gap-4" data-vani-motion-stagger>
        @foreach ($brands as $brand)
            <a href="{{ vani_route('storefront.brand', $brand['slug']) }}" class="flex h-16 min-w-32 items-center justify-center rounded-[var(--radius-theme)] border border-slate-200 px-4" data-vani-motion-item data-vani-motion-hover>
                @if ($brand['logo_url'])<img src="{{ $brand['logo_url'] }}" alt="{{ $brand['name'] }}" class="max-h-10" loading="lazy">@else{{ $brand['name'] }}@endif
            </a>
        @endforeach
    </div>
</section>
