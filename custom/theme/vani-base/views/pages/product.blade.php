@extends('theme::layouts.app')

@section('title', $product['meta_title'] ?? $product['name'])
@section('description', $product['meta_description'] ?? '')

@push('head')
    <script type="application/ld+json">{!! \Modules\Storefront\View\StructuredData::product($product) !!}</script>
@endpush

@section('content')
    @include('theme::partials.breadcrumb', ['crumbs' => [
        ...array_map(fn (array $crumb): array => ['label' => $crumb['name'], 'url' => vani_route('storefront.category', $crumb['slug'])], $product['breadcrumb']),
        ['label' => $product['name']],
    ]])

    <div class="grid gap-8 md:grid-cols-2">
        {{-- Mỗi ảnh là {url, alt} (cùng hình dạng Storefront API). --}}
        @php($images = collect($product['colors'])->flatMap(fn ($color) => $color['images'])->values()->all())
        @php($mainUrl = $images[0]['url'] ?? $product['image_url'])
        <div data-product-gallery>
            <div class="aspect-[3/4] overflow-hidden rounded-[var(--radius-theme)] bg-slate-100">
                @if ($mainUrl !== null)
                    <img data-gallery-main src="{{ $mainUrl }}" alt="{{ $images[0]['alt'] ?? $product['name'] }}" width="600" height="800" class="h-full w-full object-cover">
                @endif
            </div>
            @if (count($images) > 1)
                <div class="mt-3 grid grid-cols-4 gap-2" data-gallery-thumbs>
                    @foreach ($images as $index => $image)
                        <button type="button" data-gallery-thumb data-src="{{ $image['url'] }}" @class(['aspect-[3/4] overflow-hidden rounded border', 'border-primary' => $index === 0, 'border-slate-200' => $index !== 0]) aria-label="{{ __('Ảnh :index của :name', ['index' => $index + 1, 'name' => $product['name']]) }}">
                            <img src="{{ $image['url'] }}" alt="" loading="lazy" width="150" height="200" class="h-full w-full object-cover">
                        </button>
                    @endforeach
                </div>
            @endif
            <x-vani::hook-slot name="vani.storefront.pdp.gallery_after" :args="[$product]" />
        </div>

        <div>
            @if ($product['brand'])
                <a href="{{ vani_route('storefront.brand', $product['brand']['slug']) }}" class="text-sm uppercase tracking-wide text-slate-500">{{ $product['brand']['name'] }}</a>
            @endif
            <h1 class="text-2xl font-semibold">{{ $product['name'] }}</h1>
            <x-vani::hook-slot name="vani.storefront.pdp.after_title" :args="[$product]" />

            <p class="mt-3 text-lg">@include('theme::partials.price', ['price' => $product['price'], 'product' => $product])</p>
            {{-- Giá thành viên (Phase 8): trang cache được hiện giá chung; JS điền giá theo nhóm của khách đăng nhập. --}}
            <p class="mt-1 text-sm font-medium text-accent" data-vani-member-price data-route="{{ vani_route('storefront.session.prices') }}" hidden></p>
            <x-vani::hook-slot name="vani.storefront.pdp.after_price" :args="[$product]" />

            {{-- Trang cache được (không phiên): không có @csrf/old(); POST /gio-hang miễn CSRF (cookie phiên SameSite=Lax). --}}
            <form action="{{ vani_route('storefront.cart.add') }}" method="post" class="mt-6 space-y-4" data-variant-form>
                @foreach ($product['colors'] as $color)
                    @php($variants = array_values(array_filter($product['variants'], fn ($variant) => $variant['color_code'] === $color['code'])))
                    @continue($variants === [])
                    <fieldset data-color-group data-images='@json(array_values($color['images']))'>
                        <legend class="mb-2 text-sm font-medium">{{ __('Màu: :name', ['name' => $color['name']]) }}</legend>
                        <div class="flex flex-wrap gap-2">
                            @foreach ($variants as $variant)
                                {{-- Giá bị ẩn (khách chưa đăng nhập): vẫn chọn được; thêm giỏ chuyển sang đăng nhập. --}}
                                @php($unpriced = $variant['price'] === null && empty($product['price_hidden']))
                                <label @class(['cursor-pointer rounded border px-3 py-1.5 text-sm has-[:checked]:border-primary has-[:checked]:font-semibold', 'opacity-50' => ! $variant['available'] || $unpriced])>
                                    <input type="radio" name="variant_id" value="{{ $variant['id'] }}" class="sr-only" @disabled(! $variant['available'] || $unpriced)>
                                    {{ $variant['size_code'] }}
                                    @if ($variant['low_stock'])<span class="text-xs text-accent">{{ __('sắp hết') }}</span>@endif
                                    @unless ($variant['available'])<span class="sr-only">{{ __('(hết hàng)') }}</span>@endunless
                                </label>
                            @endforeach
                        </div>
                    </fieldset>
                @endforeach
                <p role="alert" class="text-sm text-red-600" data-vani-error-for="variant_id" hidden></p>

                <div class="flex items-center gap-3">
                    <label for="quantity" class="text-sm">{{ __('Số lượng') }}</label>
                    <input id="quantity" name="quantity" type="number" min="1" max="20" value="1" class="w-20 rounded border border-slate-300 px-2 py-1.5">
                </div>
                <x-vani::hook-slot name="vani.storefront.pdp.add_to_cart_fields" :args="[$product]" />
                @php($cannotBuy = ! $product['in_stock'] || ($product['price'] === null && empty($product['price_hidden'])))
                <div class="flex gap-3">
                    <button type="submit" class="flex-1 rounded-[var(--radius-theme)] bg-primary px-6 py-3 font-medium text-white disabled:opacity-50" @disabled($cannotBuy)>
                        {{ $product['in_stock'] ? __('Thêm vào giỏ') : __('Hết hàng') }}
                    </button>
                    {{-- Mua ngay (§3.8): thêm rồi chỉ thanh toán riêng món này; món khác vẫn ở lại giỏ. --}}
                    @if ($product['in_stock'])
                        <button type="submit" name="buy_now" value="1" class="flex-1 rounded-[var(--radius-theme)] border border-primary px-6 py-3 font-medium text-primary disabled:opacity-50" @disabled($cannotBuy)>{{ __('Mua ngay') }}</button>
                    @endif
                </div>
            </form>
            <x-vani::hook-slot name="vani.storefront.pdp.after_add_to_cart" :args="[$product]" />

            @if ($product['description'])
                <section class="prose mt-8 max-w-none text-sm">{!! nl2br(e($product['description'])) !!}</section>
            @endif
            @if ($product['attributes'] !== [])
                <dl class="mt-6 grid grid-cols-[8rem_1fr] gap-y-1 text-sm">
                    @foreach ($product['attributes'] as $attribute)
                        <dt class="text-slate-500">{{ $attribute['name'] }}</dt>
                        <dd>{{ is_array($attribute['value']) ? implode(', ', $attribute['value']) : $attribute['value'] }}</dd>
                    @endforeach
                </dl>
            @endif
            @if ($product['care_instructions'])
                <p class="mt-4 text-sm text-slate-600">{{ $product['care_instructions'] }}</p>
            @endif
            <x-vani::hook-slot name="vani.storefront.pdp.after_details" :args="[$product]" />
        </div>
    </div>

    {{-- Video: embed_url do plugin VideoDriver trả về (https), theme tự dựng iframe. --}}
    @if (($product['videos'] ?? []) !== [])
        <section class="mt-12">
            <h2 class="mb-4 text-lg font-medium">Video</h2>
            <div class="grid gap-6 md:grid-cols-2">
                @foreach ($product['videos'] as $video)
                    @if ($video['embed_url'] !== null)
                        <div class="aspect-video overflow-hidden rounded-[var(--radius-theme)] bg-slate-100">
                            <iframe src="{{ $video['embed_url'] }}" title="{{ $video['title'] ?? $product['name'] }}" loading="lazy" class="h-full w-full" allow="accelerometer; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" referrerpolicy="strict-origin-when-cross-origin" allowfullscreen></iframe>
                        </div>
                    @else
                        <a href="{{ $video['watch_url'] }}" target="_blank" rel="noopener" class="block text-sm underline">{{ $video['title'] ?? $video['watch_url'] }}</a>
                    @endif
                @endforeach
            </div>
        </section>
    @endif

    @foreach ($product['links'] ?? [] as $link)
        <section class="mt-12">
            <h2 class="mb-4 text-lg font-medium">{{ $link['name'] ?? $link['code'] }}</h2>
            <div class="grid grid-cols-2 gap-6 md:grid-cols-4">
                @foreach (array_slice($link['products'], 0, 8) as $linked)
                    @include('theme::partials.product-card', ['product' => $linked])
                @endforeach
            </div>
        </section>
    @endforeach
@endsection
