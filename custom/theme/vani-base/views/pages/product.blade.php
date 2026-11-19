@extends('theme::layouts.app')

{{-- $page: dữ liệu hiển thị Core dựng sẵn (Storefront\View\ProductPage) — metadata, breadcrumb, gallery, bộ chọn biến thể. --}}
@section('title', $page['meta']['title'])
@if ($page['meta']['description'] !== null)
    @section('description', $page['meta']['description'])
@endif
@if ($page['meta']['image'] !== null)
    @section('og_image', $page['meta']['image'])
@endif
@section('og_type', 'product')

@push('head')
    @foreach ($page['meta']['json_ld'] as $jsonLd)
        <script type="application/ld+json">{!! $jsonLd !!}</script>
    @endforeach
@endpush

@section('content')
    @include('theme::partials.breadcrumb', ['crumbs' => $page['crumbs']])

    <div class="grid gap-8 md:grid-cols-2">
        @php($gallery = $page['gallery'])
        {{--
            Gallery: Swiper (vuốt, mũi tên) + PhotoSwipe (phóng to) — cài qua npm, tải theo nhu cầu (app.js). Không JS:
            dải ảnh cuộn ngang (scroll-snap), bấm ảnh mở bản lớn nhất. Khung theo tỉ lệ ảnh sản phẩm (Admin → Kích thước ảnh).
        --}}
        <x-vani::hook-slot name="vani.storefront.pdp.before_gallery" :args="[$product]" />
        <div data-product-gallery>
            <div class="vani-carousel swiper rounded-[var(--radius-theme)] bg-slate-100" style="aspect-ratio: {{ $gallery['aspect'] }}" data-gallery-slider
                 aria-roledescription="carousel" aria-label="{{ $gallery['label'] }}">
                <div class="swiper-wrapper">
                    @foreach ($gallery['slides'] as $slide)
                        <div class="swiper-slide w-full" data-gallery-slide>
                            <a href="{{ $slide['zoom']['url'] }}" data-pswp-width="{{ $slide['zoom']['width'] }}" data-pswp-height="{{ $slide['zoom']['height'] }}" target="_blank" rel="noopener" class="block h-full cursor-zoom-in"
                               aria-label="{{ $slide['zoom_label'] }}">
                                <img {{ $slide['main'] ? 'data-gallery-main' : 'data-gallery-image' }} src="{{ $slide['url'] }}" alt="{{ $slide['alt'] }}" width="{{ $slide['width'] }}" height="{{ $slide['height'] }}"
                                     @if ($slide['srcset']) srcset="{{ $slide['srcset'] }}" sizes="{{ $slide['sizes'] }}" @endif
                                     @if ($slide['main']) fetchpriority="high" @else loading="lazy" @endif decoding="async" class="h-full w-full object-cover">
                            </a>
                        </div>
                    @endforeach
                </div>
                @if ($gallery['navigation'])
                    <button type="button" class="vani-carousel-nav left-2" data-carousel-prev data-js-only aria-label="{{ __('theme::common.previous_image') }}">‹</button>
                    <button type="button" class="vani-carousel-nav right-2" data-carousel-next data-js-only aria-label="{{ __('theme::common.next_image') }}">›</button>
                @endif
            </div>
            @if ($gallery['thumbs'] !== [])
                <div class="mt-3 flex gap-2 overflow-x-auto pb-1" data-gallery-thumbs>
                    @foreach ($gallery['thumbs'] as $thumb)
                        <button type="button" data-gallery-thumb data-src="{{ $thumb['url'] }}" data-index="{{ $thumb['index'] }}" @class(['w-1/5 shrink-0 overflow-hidden rounded border', 'border-primary' => $thumb['active'], 'border-slate-200' => ! $thumb['active']]) style="aspect-ratio: {{ $gallery['aspect'] }}" aria-label="{{ $thumb['label'] }}">
                            <img src="{{ $thumb['thumb_url'] }}" alt="" loading="lazy" decoding="async" width="{{ $thumb['width'] }}" height="{{ $thumb['height'] }}" class="h-full w-full object-cover">
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

            <p class="mt-3 text-lg" data-product-price>@include('theme::partials.price', ['price' => $product['price'], 'product' => $product])</p>
            {{-- Giá thành viên (Phase 8): trang cache được hiện giá chung; JS điền giá theo nhóm của khách đăng nhập. --}}
            <p class="mt-1 text-sm font-medium text-accent" data-vani-member-price data-route="{{ route('api.storefront.v1.me.prices') }}" hidden></p>
            <x-vani::hook-slot name="vani.storefront.pdp.after_price" :args="[$product]" />
            <x-vani::hook-slot name="vani.storefront.pdp.before_add_to_cart" :args="[$product]" />

            {{-- Không @csrf/old() để HTML như nhau ở cả chế độ cache CDN (không phiên); POST /gio-hang miễn CSRF (cookie phiên SameSite=Lax). --}}
            @php($selector = $page['selector'])
            @php($primary = $selector['primary'])
            <form action="{{ vani_route('storefront.cart.add') }}" method="post" class="mt-6 space-y-4" data-variant-form data-choose-message="{{ $selector['choose_message'] }}">
                {{-- Bộ chọn 1–3 trục (ADR-045): trục đầu là radio/danh sách (ô màu / nút / thả xuống); mỗi giá trị một nhóm SKU theo
                     các trục còn lại, chỉ nhóm của giá trị đang chọn hiện ra — CSS :has() ($page['variant_css']), không cần JS. --}}
                @if ($selector['hidden_variant_id'] !== null)
                    <input type="hidden" name="variant_id" value="{{ $selector['hidden_variant_id'] }}">
                @endif
                @if ($primary !== null && $primary['values'] !== [])
                    <style>{!! $page['variant_css'] !!}</style>
                    <fieldset data-primary-option="{{ $primary['code'] }}">
                        <legend class="mb-2 text-sm">
                            {{ $primary['name'] }}:
                            @foreach ($primary['values'] as $value)
                                <span class="font-medium" data-option-label="{{ $value['code'] }}">{{ $value['name'] }}</span>
                            @endforeach
                        </legend>
                        @if ($primary['display_type'] === 'dropdown')
                            <select name="{{ $primary['input_name'] }}" class="rounded-[var(--radius-theme)] border border-slate-300 px-3 py-2 text-sm" aria-label="{{ $primary['name'] }}">
                                @foreach ($primary['values'] as $value)
                                    <option value="{{ $value['input_value'] }}" data-primary data-option-value="{{ $value['code'] }}" data-images='@json($value['images'])'
                                        @if ($value['price_html']) data-price-html="{{ $value['price_html'] }}" @endif
                                        @selected($value['checked']) @disabled($value['disabled'])>{{ $value['name'] }}@unless ($value['available']) ({{ __('theme::product.out_of_stock_lower') }})@endunless</option>
                                @endforeach
                            </select>
                        @else
                            @php($swatch = $primary['display_type'] === 'swatch')
                            <div class="flex flex-wrap gap-2">
                                @foreach ($primary['values'] as $value)
                                    <label @class([
                                        'relative cursor-pointer has-[:focus-visible]:outline has-[:focus-visible]:outline-2',
                                        'rounded-full p-0.5 ring-1 ring-slate-200 has-[:checked]:ring-2 has-[:checked]:ring-primary' => $swatch,
                                        'flex min-w-12 flex-col items-center rounded-[var(--radius-theme)] border border-slate-300 px-3 py-2 text-sm has-[:checked]:border-primary has-[:checked]:bg-primary has-[:checked]:text-white' => ! $swatch,
                                        'cursor-not-allowed border-dashed text-slate-400 line-through' => ! $swatch && $value['disabled'],
                                    ]) title="{{ $value['name'] }}">
                                        <input type="radio" name="{{ $primary['input_name'] }}" value="{{ $value['input_value'] }}" class="sr-only" data-primary data-option-value="{{ $value['code'] }}"
                                            data-images='@json($value['images'])' @checked($value['checked']) @disabled($value['disabled'])
                                            @if ($value['price_html']) data-price-html="{{ $value['price_html'] }}" @endif>
                                        @if (! $swatch)
                                            <span class="font-medium">{{ $value['name'] }}</span>
                                            @if ($value['low_stock'])<span class="text-[11px] leading-none text-accent no-underline">{{ __('theme::product.low_stock') }}</span>@endif
                                        @elseif ($value['hex'])
                                            <span class="block h-8 w-8 rounded-full border border-black/10" style="background: {{ $value['hex'] }}"></span>
                                        @elseif ($value['image_url'])
                                            <img src="{{ $value['image_url'] }}" alt="" class="block h-8 w-8 rounded-full object-cover" loading="lazy">
                                        @else
                                            <span class="flex h-8 min-w-8 items-center justify-center rounded-full px-2 text-xs">{{ $value['name'] }}</span>
                                        @endif
                                        @if ($swatch && ! $value['available'])
                                            <span aria-hidden="true" class="pointer-events-none absolute inset-0 m-auto h-px w-9 -rotate-45 bg-slate-500"></span>
                                        @endif
                                        <span class="sr-only">{{ $value['name'] }}@unless ($value['available']) {{ __('theme::product.out_of_stock_parenthesized') }}@endunless</span>
                                    </label>
                                @endforeach
                            </div>
                        @endif
                    </fieldset>

                    @foreach ($selector['groups'] as $code => $skus)
                        <fieldset data-option-group="{{ $code }}">
                            <legend class="mb-2 text-sm">{{ $selector['rest_label'] }}</legend>
                            <div class="flex flex-wrap gap-2">
                                @foreach ($skus as $variant)
                                    <label @class(['relative flex min-w-12 cursor-pointer flex-col items-center rounded-[var(--radius-theme)] border border-slate-300 px-3 py-2 text-sm has-[:checked]:border-primary has-[:checked]:bg-primary has-[:checked]:text-white has-[:focus-visible]:outline has-[:focus-visible]:outline-2', 'cursor-not-allowed border-dashed text-slate-400 line-through' => $variant['disabled']])>
                                        <input type="radio" name="variant_id" value="{{ $variant['id'] }}" class="sr-only" data-images='@json($variant['images'])' @disabled($variant['disabled']) @checked($variant['checked'])
                                            @if ($variant['price_html']) data-price-html="{{ $variant['price_html'] }}" @endif>
                                        <span class="font-medium">{{ $variant['label'] }}</span>
                                        @if ($variant['availability'] === 'low_stock')<span class="text-[11px] leading-none text-accent no-underline">{{ __('theme::product.low_stock') }}</span>@endif
                                        @if ($variant['availability'] === 'out_of_stock')<span class="sr-only">{{ __('theme::product.out_of_stock_parenthesized') }}</span>@endif
                                    </label>
                                @endforeach
                            </div>
                        </fieldset>
                    @endforeach
                @endif
                {{-- Không CDN: lỗi của lần gửi trước render sẵn; trang cache CDN: JS điền từ /_vani/phien. --}}
                <p role="alert" class="text-sm text-red-600" data-vani-error-for="variant_id" @unless ($errors->has('variant_id')) hidden @endunless>{{ $errors->first('variant_id') }}</p>

                <div class="flex items-center gap-3">
                    <label for="quantity" class="text-sm">{{ __('theme::common.quantity') }}</label>
                    <input id="quantity" name="quantity" type="number" min="{{ $product['purchase']['min_quantity'] }}" max="{{ $product['purchase']['max_quantity'] }}" value="{{ $product['purchase']['min_quantity'] }}" class="w-20 rounded border border-slate-300 px-2 py-1.5">
                </div>
                <x-vani::hook-slot name="vani.storefront.pdp.add_to_cart_fields" :args="[$product]" />
                <div class="flex gap-3">
                    <button type="submit" class="flex-1 rounded-[var(--radius-theme)] bg-primary px-6 py-3 font-medium text-white disabled:opacity-50" @disabled(! $page['can_buy'])>
                        {{ $product['in_stock'] ? __('theme::product.add_to_cart') : __('theme::product.out_of_stock') }}
                    </button>
                    {{-- Mua ngay (§3.8): thêm rồi chỉ thanh toán riêng món này; món khác vẫn ở lại giỏ. --}}
                    @if ($product['in_stock'])
                        <button type="submit" name="buy_now" value="1" class="flex-1 rounded-[var(--radius-theme)] border border-primary px-6 py-3 font-medium text-primary disabled:opacity-50" @disabled(! $page['can_buy'])>{{ __('theme::product.buy_now') }}</button>
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
            <div class="grid gap-6 md:grid-cols-2" data-vani-motion-stagger>
                @foreach ($product['videos'] as $video)
                    @if ($video['embed_url'] !== null)
                        <div class="aspect-video overflow-hidden rounded-[var(--radius-theme)] bg-slate-100" data-vani-motion-item>
                            <iframe src="{{ $video['embed_url'] }}" title="{{ $video['title'] ?? $product['name'] }}" loading="lazy" class="h-full w-full" allow="accelerometer; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" referrerpolicy="strict-origin-when-cross-origin" allowfullscreen></iframe>
                        </div>
                    @else
                        <a href="{{ $video['watch_url'] }}" target="_blank" rel="noopener" class="block text-sm underline">{{ $video['title'] ?? $video['watch_url'] }}</a>
                    @endif
                @endforeach
            </div>
        </section>
    @endif
    <x-vani::hook-slot name="vani.storefront.pdp.after_videos" :args="[$product]" />

    @foreach ($product['links'] ?? [] as $link)
        <section class="mt-12">
            <h2 class="mb-4 text-lg font-medium">{{ $link['name'] ?? $link['code'] }}</h2>
            @include('theme::partials.product-grid', ['products' => array_slice($link['products'], 0, 8), 'stagger' => true])
        </section>
    @endforeach
    <x-vani::hook-slot name="vani.storefront.pdp.after_related_products" :args="[$product]" />

    {{-- Lượt xem + "Đã xem gần đây" (§3.5-2): JS gửi beacon sau khi trang tải, lưu tối đa 12 mẫu trong trình duyệt và hiện
         các mẫu đã xem khác (thẻ lấy từ Storefront API). HTML giống nhau với mọi khách; không JS → khung ẩn. --}}
    <section class="mt-12" hidden data-vani-recently-viewed data-style-id="{{ $product['id'] }}"
             data-view-url="{{ route('api.storefront.v1.products.views') }}" data-products-url="{{ route('api.storefront.v1.products.index') }}" data-locale="{{ app()->getLocale() }}"
             data-product-url="{{ vani_route('storefront.product', '__slug__') }}">
        <h2 class="mb-4 text-lg font-medium">{{ __('theme::product.recently_viewed') }}</h2>
        <div class="grid grid-cols-2 gap-6 md:grid-cols-6" data-vani-recently-viewed-list></div>
    </section>
@endsection
