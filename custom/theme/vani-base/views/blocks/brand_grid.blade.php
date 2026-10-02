<section class="mb-10" data-block="brand_grid">
    @if ($title !== '')<h2 class="mb-4 text-lg font-semibold">{{ $title }}</h2>@endif
    <div class="flex flex-wrap gap-4">
        @foreach ($brands as $brand)
            <a href="{{ route('storefront.brand', $brand['slug']) }}" class="flex h-16 min-w-32 items-center justify-center rounded-[var(--radius-theme)] border border-slate-200 px-4">
                @if ($brand['logo_url'])<img src="{{ $brand['logo_url'] }}" alt="{{ $brand['name'] }}" class="max-h-10" loading="lazy">@else{{ $brand['name'] }}@endif
            </a>
        @endforeach
    </div>
</section>
