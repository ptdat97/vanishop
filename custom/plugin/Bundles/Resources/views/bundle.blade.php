{{-- Một combo: $bundle (BundlePresenter). Chọn phân loại từng món rồi thêm cả bộ bằng window.vani.cart.addItems (bundles.js). --}}
<section class="border border-border p-4 sm:p-5" data-vani-bundle="{{ $bundle['id'] }}" aria-label="{{ $bundle['name'] }}">
    @if ($bundle['image_url'] !== null)
        <img src="{{ $bundle['image_url'] }}" alt="" class="mb-4 h-40 w-full object-cover" loading="lazy">
    @endif
    <div class="mb-4 flex flex-wrap items-baseline justify-between gap-2">
        @if ($pageHeading ?? false)
            <h2 class="text-lg font-medium">{{ $bundle['name'] }}</h2>
        @else
            <h3 class="text-lg font-medium">{{ $bundle['name'] }}</h3>
        @endif
        <span class="bg-primary px-2 py-0.5 text-xs font-medium text-white">{{ __('vani-bundles::texts.save_percent', ['percent' => $bundle['discount_percent']]) }}</span>
    </div>
    @if ($bundle['description'] !== '')
        <p class="mb-4 text-sm text-accent">{{ $bundle['description'] }}</p>
    @endif
    <form action="{{ vani_route('storefront.cart.add') }}" method="post" data-vani-bundle-form>
        <ul class="divide-y divide-border">
            @foreach ($bundle['items'] as $index => ['product' => $product, 'quantity' => $quantity, 'variants' => $variants, 'available' => $available, 'selected_variant_id' => $selectedVariantId])
                <li class="flex gap-3 py-3" data-vani-bundle-item>
                    @if (! empty($product['image_url']))
                        <img src="{{ $product['image_url'] }}" alt="" class="h-20 w-16 shrink-0 object-cover" loading="lazy" width="64" height="80">
                    @endif
                    <div class="min-w-0 flex-1">
                        <a href="{{ vani_route('storefront.product', $product['slug']) }}" class="text-sm font-medium hover:underline">{{ $product['name'] }}</a>
                        @if ($quantity > 1)<span class="text-sm text-accent"> × {{ $quantity }}</span>@endif
                        <div class="mt-1 text-sm">@include('theme::partials.price', ['price' => $product['price'] ?? null, 'product' => $product])</div>
                        <label class="mt-2 block text-xs text-accent">
                            <span class="sr-only">{{ __('vani-bundles::texts.choose_variant_for', ['name' => $product['name']]) }}</span>
                            <input type="hidden" name="items[{{ $index }}][quantity]" value="{{ $quantity }}">
                            <select name="items[{{ $index }}][variant_id]" class="mt-1 w-full border border-border bg-transparent px-2 py-1.5 text-sm" required @disabled(! $available)>
                                <option value="">{{ __('vani-bundles::texts.choose_variant') }}</option>
                                @foreach ($variants as $variant)
                                    <option value="{{ $variant['id'] }}" @disabled(! $variant['available']) @selected($selectedVariantId === $variant['id'])>
                                        {{ $variant['label'] }}@if (! $variant['available']) — {{ __('vani-bundles::texts.out_of_stock') }}@endif
                                    </option>
                                @endforeach
                            </select>
                        </label>
                    </div>
                </li>
            @endforeach
        </ul>
        <div class="mt-4 flex flex-wrap items-center justify-between gap-3 border-t border-border pt-4">
            @if ($bundle['total'] !== null)
                <p class="text-sm">
                    <span class="text-accent">{{ __('vani-bundles::texts.total_from') }}</span>
                    <span class="ms-1 text-lg font-medium tabular-nums" data-vani-bundle-total data-vani-amount="{{ $bundle['total']['amount'] }}">{{ $bundle['total']['formatted'] }}</span>
                    <s class="ms-1 text-sm tabular-nums text-accent">{{ $bundle['compare_at']['formatted'] }}</s>
                    <span class="block text-xs text-accent">{{ __('vani-bundles::texts.saving', ['amount' => $bundle['saving']['formatted']]) }}</span>
                </p>
            @endif
            <button type="submit" class="vani-button-primary" @disabled(! $bundle['available'])>
                {{ __('vani-bundles::texts.' . ($bundle['available'] ? 'add_all' : 'bundle_unavailable')) }}
            </button>
        </div>
        <p class="mt-2 text-sm text-red-600" data-vani-bundle-error role="alert" hidden></p>
    </form>
</section>
@if (($scriptSrc ?? '') !== '')
    @once('vani-bundles-script')
        <script src="{{ $scriptSrc }}" defer></script>
    @endonce
@endif
