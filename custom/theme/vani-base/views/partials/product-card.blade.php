<article class="group">
    <a href="{{ route('storefront.product', $product['slug']) }}" class="block">
        <div class="aspect-[3/4] overflow-hidden rounded-[var(--radius-theme)] bg-slate-100">
            @if ($product['image_url'])
                <img src="{{ $product['image_url'] }}" alt="{{ $product['name'] }}" loading="lazy" width="300" height="400" class="h-full w-full object-cover">
            @endif
        </div>
        <x-vani::hook-slot name="vani.storefront.plp.card_badges" :args="[$product]" />
        @if ($product['brand'])
            <p class="mt-2 text-xs uppercase tracking-wide text-slate-500">{{ $product['brand']['name'] }}</p>
        @endif
        <h3 class="text-sm font-medium group-hover:underline">{{ $product['name'] }}</h3>
    </a>
    <p class="mt-1">@include('theme::partials.price', ['price' => $product['price']])</p>
    @unless ($product['in_stock'])
        <p class="text-xs text-slate-500">Hết hàng</p>
    @endunless
</article>
