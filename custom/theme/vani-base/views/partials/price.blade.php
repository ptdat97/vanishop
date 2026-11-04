{{-- $price: khoảng giá của ProductViews (min/max/compare_at/discount_percent) hoặc null. $product (tuỳ chọn): khi
     `price_hidden` (cửa hàng ẩn giá với khách chưa đăng nhập) hiện link đăng nhập; JS điền giá cho khách đã đăng nhập. --}}
@if (! empty($product['price_hidden']))
    <a href="{{ vani_route('storefront.account.login') }}" class="text-sm font-medium text-primary underline" data-vani-price-for="{{ $product['id'] }}">{{ __('storefront::messages.sign_in_for_price') }}</a>
@elseif ($price === null)
    <span class="text-sm text-slate-500">{{ __('Chưa có giá') }}</span>
@else
    <span class="font-semibold text-primary" data-price>
        {{ $price['min']['formatted'] }}@if ($price['max']['amount'] !== $price['min']['amount']) – {{ $price['max']['formatted'] }}@endif
    </span>
    @if ($price['compare_at'])
        <s class="ml-1 text-sm text-slate-400">{{ $price['compare_at']['formatted'] }}</s>
    @endif
    @if ($price['discount_percent'])
        <span class="ml-1 text-xs font-medium text-accent">-{{ $price['discount_percent'] }}%</span>
    @endif
@endif
