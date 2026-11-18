@php($isList = ($style ?? 'grid') === 'list')
<article @class(['group vani-product-card' => true, 'flex items-start gap-3 border-b border-slate-200 pb-4 last:border-0' => $isList]) data-vani-motion="rise" data-vani-motion-item>
    <a href="{{ vani_route('storefront.product', $product['slug']) }}" @class(['relative block' => ! $isList, 'relative flex min-w-0 flex-1 gap-3' => $isList])>
        {{-- $productFrame: khung theo tỉ lệ ảnh sản phẩm, Core dựng sẵn (ProductFrameComposer). --}}
        <div @class(['overflow-hidden rounded-[var(--radius-theme)] bg-slate-100' => true, 'w-20 shrink-0 sm:w-28' => $isList]) style="aspect-ratio: {{ $productFrame['aspect'] }}">
            @if ($product['image_url'])
                {{-- $layout: [vùng chứa px, cột desktop, cột mobile] của lưới đang dùng; mặc định lưới 4/2 trong khung 72rem. Khung theo tỉ lệ ảnh sản phẩm. --}}
                @include('theme::partials.image', ['src' => $product['image_url'], 'srcset' => $product['image_srcset'] ?? null, 'alt' => $product['name'], 'layout' => $layout ?? [1152, 4, 2], 'width' => $productFrame['card']['width'], 'height' => $productFrame['card']['height'], 'class' => 'h-full w-full object-cover', 'loading' => $loading ?? 'lazy'])
            @endif
        </div>
        <x-vani::hook-slot name="vani.storefront.plp.card_badges" :args="[$product]" />
        <div @class(['min-w-0' => $isList])>
            @if ($product['brand'])
                <p @class(['mt-2 text-xs uppercase tracking-wide text-slate-500' => ! $isList, 'text-xs uppercase tracking-wide text-slate-500' => $isList])>{{ $product['brand']['name'] }}</p>
            @endif
            <h3 class="text-sm font-medium group-hover:underline">{{ $product['name'] }}</h3>
            @if ($isList)
                <p class="mt-1">@include('theme::partials.price', ['price' => $product['price'], 'product' => $product])</p>
                @unless ($product['in_stock'])
                    <p class="mt-1 text-xs text-slate-500">{{ __('Hết hàng') }}</p>
                @endunless
            @endif
        </div>
    </a>
    @unless ($isList)
        <p class="mt-1">@include('theme::partials.price', ['price' => $product['price'], 'product' => $product])</p>
        @unless ($product['in_stock'])
            <p class="text-xs text-slate-500">{{ __('Hết hàng') }}</p>
        @endunless
    @endunless
    <x-vani::hook-slot name="vani.storefront.plp.card.actions" :args="[$product]" />
</article>
