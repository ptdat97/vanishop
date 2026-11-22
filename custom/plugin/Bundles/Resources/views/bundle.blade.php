{{-- Một combo: $bundle (BundlePresenter). Chọn phân loại từng món rồi thêm cả bộ bằng window.vani.cart.addItems (bundles.js). --}}
<section class="border border-border p-4 sm:p-5" data-vani-bundle="{{ $bundle['id'] }}" aria-labelledby="bundle-{{ $bundle['id'] }}-title">
    <div class="mb-4 flex flex-wrap items-baseline justify-between gap-2">
        <h3 id="bundle-{{ $bundle['id'] }}-title" class="text-lg font-medium">{{ $bundle['name'] }}</h3>
        <span class="bg-primary px-2 py-0.5 text-xs font-medium text-white">{{ __('vani-bundles::texts.save_percent', ['percent' => $bundle['discount_percent']]) }}</span>
    </div>
    @if ($bundle['description'] !== '')
        <p class="mb-4 text-sm text-accent">{{ $bundle['description'] }}</p>
    @endif
    <form data-vani-bundle-form>
        <ul class="divide-y divide-border">
            @foreach ($bundle['items'] as $index => $item)
                @php($product = $item['product'])
                <li class="flex gap-3 py-3" data-vani-bundle-item>
                    <a href="{{ vani_route('storefront.product', $product['slug']) }}" class="shrink-0">
                        @if (! empty($product['image_url']))
                            <img src="{{ $product['image_url'] }}" alt="" class="h-20 w-16 object-cover" loading="lazy" width="64" height="80">
                        @endif
                    </a>
                    <div class="min-w-0 flex-1">
                        <a href="{{ vani_route('storefront.product', $product['slug']) }}" class="text-sm font-medium hover:underline">{{ $product['name'] }}</a>
                        @if ($item['quantity'] > 1)<span class="text-sm text-accent"> × {{ $item['quantity'] }}</span>@endif
                        <div class="mt-1 text-sm">@include('theme::partials.price', ['price' => $product['price'] ?? null, 'product' => $product])</div>
                        <label class="mt-2 block text-xs text-accent">
                            <span class="sr-only">{{ __('vani-bundles::texts.choose_variant_for', ['name' => $product['name']]) }}</span>
                            <select name="items[{{ $index }}][variant_id]" data-quantity="{{ $item['quantity'] }}" class="mt-1 w-full border border-border bg-transparent px-2 py-1.5 text-sm" required>
                                <option value="">{{ __('vani-bundles::texts.choose_variant') }}</option>
                                @foreach ($item['variants'] as $variant)
                                    <option value="{{ $variant['id'] }}" @disabled(! $variant['available']) @selected(count($item['variants']) === 1 && $variant['available'])>
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
            <button type="submit" class="vani-button-primary">{{ __('vani-bundles::texts.add_all') }}</button>
        </div>
        <p class="mt-2 text-sm text-red-600" data-vani-bundle-error role="alert" hidden></p>
    </form>
</section>
@if (($scriptSrc ?? '') !== '')
    <script src="{{ $scriptSrc }}" defer></script>
@endif
