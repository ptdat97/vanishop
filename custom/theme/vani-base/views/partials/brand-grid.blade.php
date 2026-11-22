<div class="grid grid-cols-2 gap-x-4 sm:grid-cols-3 lg:grid-cols-5" data-vani-motion-stagger>
    @foreach ($brands as $brand)
        <a href="{{ vani_route('storefront.brand', $brand['slug']) }}" class="grid min-h-20 min-w-0 place-items-center border-b border-border px-3 py-4 text-center text-sm font-medium break-words hover:bg-subtle hover:underline" data-vani-motion-item>
            @if ($brand['logo_url'])
                <img src="{{ $brand['logo_url'] }}" @if (! empty($brand['logo_srcset'])) srcset="{{ $brand['logo_srcset'] }}" sizes="96px" @endif alt="{{ $brand['name'] }}" width="96" height="40" class="w-24 h-10 max-w-full object-contain" loading="lazy">
            @else
                <span class="min-w-0 max-w-full">{{ $brand['name'] }}</span>
            @endif
        </a>
    @endforeach
</div>
