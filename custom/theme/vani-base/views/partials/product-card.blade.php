<article class="group vani-product-card" data-vani-motion="rise" data-vani-motion-item>
    <a href="{{ vani_route('storefront.product', $product['slug']) }}" class="block">
        @php([$ratioW, $ratioH] = vani_product_ratio())
        <div class="overflow-hidden rounded-[var(--radius-theme)] bg-slate-100" style="aspect-ratio: {{ $ratioW }} / {{ $ratioH }}">
            @if ($product['image_url'])
                {{-- $layout: [vùng chứa px, cột desktop, cột mobile] của lưới đang dùng; mặc định lưới 4/2 trong khung 72rem. Khung theo tỉ lệ ảnh sản phẩm. --}}
                @include('theme::partials.image', ['src' => $product['image_url'], 'srcset' => $product['image_srcset'] ?? null, 'alt' => $product['name'], 'layout' => $layout ?? [1152, 4, 2], 'width' => 300, 'height' => (int) round(300 * $ratioH / $ratioW), 'class' => 'h-full w-full object-cover'])
            @endif
        </div>
        <x-vani::hook-slot name="vani.storefront.plp.card_badges" :args="[$product]" />
        @if ($product['brand'])
            <p class="mt-2 text-xs uppercase tracking-wide text-slate-500">{{ $product['brand']['name'] }}</p>
        @endif
        <h3 class="text-sm font-medium group-hover:underline">{{ $product['name'] }}</h3>
    </a>
    <p class="mt-1">@include('theme::partials.price', ['price' => $product['price'], 'product' => $product])</p>
    @unless ($product['in_stock'])
        <p class="text-xs text-slate-500">{{ __('Hết hàng') }}</p>
    @endunless
    <x-vani::hook-slot name="vani.storefront.plp.card.actions" :args="[$product]" />
</article>
