@php($isList = ($style ?? 'grid') === 'list')
<article @class(['group vani-product-card' => true, 'flex items-start gap-3 border-b border-border pb-4 last:border-0' => $isList]) data-vani-motion="rise" data-vani-motion-item data-vani-product="{{ $product['slug'] }}">
    <div @class(['relative min-w-0' => true, 'flex flex-1 items-start gap-3' => $isList])>
        <div @class(['relative' => true, 'w-20 shrink-0 sm:w-28' => $isList])>
            {{-- $productFrame do ProductFrameComposer dựng từ tỉ lệ ảnh sản phẩm của cửa hàng. --}}
            <div class="overflow-hidden rounded-[var(--radius-theme)] bg-subtle" style="aspect-ratio: {{ $productFrame['aspect'] }}">
                @if ($product['image_url'])
                    @include('theme::partials.image', ['src' => $product['image_url'], 'srcset' => $product['image_srcset'] ?? null, 'alt' => '', 'layout' => $layout ?? [1152, 4, 2], 'width' => $productFrame['card']['width'], 'height' => $productFrame['card']['height'], 'class' => 'h-full w-full object-cover', 'loading' => $loading ?? 'lazy'])
                @endif
            </div>
            <x-vani::hook-slot name="vani.storefront.plp.card_badges" :args="[$product]" />
        </div>
        <div @class(['min-w-0 break-words' => true, 'pt-3' => ! $isList])>
            <h3 class="text-sm leading-snug font-medium sm:text-base">
                <a class="vani-product-card-link" href="{{ vani_route('storefront.product', $product['slug']) }}">{{ $product['name'] }}</a>
            </h3>
            <p class="mt-1 text-sm leading-snug">@include('theme::partials.price', ['price' => $product['price'], 'product' => $product])</p>
            @if ($product['brand'])
                <p class="mt-1 text-xs tracking-wide text-accent">{{ $product['brand']['name'] }}</p>
            @endif
            @unless ($product['in_stock'])
                <p class="mt-1 text-xs text-accent">{{ __('theme::product.out_of_stock') }}</p>
            @endunless
        </div>
    </div>
    <x-vani::hook-slot name="vani.storefront.plp.card.actions" :args="[$product]" />
</article>
