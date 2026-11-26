@if ($items === [])
    <p class="text-slate-500">{{ __('vani-wishlist::storefront.empty') }}</p>
@else
    <div class="grid grid-cols-2 gap-6 md:grid-cols-3" data-vani-wishlist-page>
        @foreach ($items as $item)
            <div data-vani-wishlist-item>
                @if ($item['card'] !== null)
                    @include('theme::partials.product-card', ['product' => $item['card'], 'layout' => $layout ?? [896, 3, 2]])
                @else
                    <p class="rounded-[var(--radius-theme)] bg-slate-100 p-6 text-sm text-slate-500">{{ __('vani-wishlist::storefront.no_longer_available') }}</p>
                @endif
                <button type="button" class="mt-2 text-sm text-slate-500 underline" data-vani-wishlist-remove="{{ $item['product_id'] }}">{{ __('vani-wishlist::storefront.remove_from_wishlist') }}</button>
            </div>
        @endforeach
    </div>
@endif
