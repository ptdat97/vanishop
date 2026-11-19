{{-- $price: khoảng giá của ProductViews (min/max/compare_at/discount_percent) hoặc null. $product (tuỳ chọn): khi
     `price_hidden` (cửa hàng ẩn giá với khách chưa đăng nhập) hiện link đăng nhập; JS điền giá cho khách đã đăng nhập. --}}
@if (! empty($product['price_hidden']))
    <a href="{{ vani_route('storefront.account.login') }}" class="text-sm font-medium text-primary underline" data-vani-price-for="{{ $product['id'] }}">{{ __('storefront::messages.sign_in_for_price') }}</a>
@elseif ($price === null)
    <span class="text-sm text-slate-500">{{ __('theme::product.no_price_yet') }}</span>
@else
    {{-- data-vani-value/-amount: số liệu thô để test so khớp trang với Storefront API (L46) — theme con giữ khi override. --}}
    <span class="font-semibold text-primary" data-price data-vani-value="price.min" data-vani-amount="{{ $price['min']['amount'] }}" data-vani-amount-max="{{ $price['max']['amount'] }}">
        {{ $price['min']['formatted'] }}@if ($price['max']['amount'] !== $price['min']['amount']) – {{ $price['max']['formatted'] }}@endif
    </span>
    @if ($price['compare_at'])
        <s class="ml-1 text-sm text-slate-400" data-vani-value="price.compare_at" data-vani-amount="{{ $price['compare_at']['amount'] }}">{{ $price['compare_at']['formatted'] }}</s>
    @endif
    @if ($price['discount_percent'])
        <span class="ml-1 text-xs font-medium text-accent" data-vani-value="price.discount_percent" data-vani-amount="{{ $price['discount_percent'] }}">-{{ $price['discount_percent'] }}%</span>
    @endif
@endif
