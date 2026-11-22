<div class="flex flex-wrap gap-4" data-vani-motion-stagger>
    @foreach ($brands as $brand)
        <a href="{{ vani_route('storefront.brand', $brand['slug']) }}" class="flex h-16 min-w-32 items-center justify-center rounded-[var(--radius-theme)] border border-slate-200 px-4" data-vani-motion-item data-vani-motion-hover>
            @if ($brand['logo_url'])
                <img src="{{ $brand['logo_url'] }}" @if (! empty($brand['logo_srcset'])) srcset="{{ $brand['logo_srcset'] }}" sizes="96px" @endif alt="{{ $brand['name'] }}" width="96" height="40" class="w-24 h-10 max-w-full object-contain" loading="lazy">
            @else
                {{ $brand['name'] }}
            @endif
        </a>
    @endforeach
</div>
