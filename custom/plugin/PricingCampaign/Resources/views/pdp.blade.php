{{-- Trang sản phẩm (slot pdp.after_price): chiến dịch đang chạy (đếm ngược tới hết lượt, suất còn lại, giới hạn mỗi khách) hoặc sắp chạy. --}}
<div class="my-3 space-y-2 rounded border p-3 text-sm" style="border-color: {{ $offer['accent_color'] ?? 'currentColor' }}" data-vani-campaign-offer data-live-url="{{ $liveUrl }}">
    <p class="font-medium" @if ($offer['accent_color']) style="color: {{ $offer['accent_color'] }}" @endif>{{ $offer['label'] }}</p>
    @if ($offer['show_countdown'])
        <p>
            {{ $offer['running'] ? __('vani-pricing-campaign::texts.storefront.ends_in') : __('vani-pricing-campaign::texts.storefront.starts_in') }}
            @include('vani-pricing-campaign::countdown', ['at' => $offer['running'] ? $offer['ends_at'] : $offer['starts_at']])
        </p>
    @endif
    @if ($offer['running'] && $offer['show_stock'] && $offer['limit'] !== null)
        <div class="h-2 overflow-hidden rounded bg-slate-200" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ $offer['sold_percent'] ?? 0 }}">
            <div class="h-full bg-red-600" style="width: {{ $offer['sold_percent'] ?? 0 }}%" data-vani-campaign-progress></div>
        </div>
        <p data-vani-campaign-remaining>{{ $offer['remaining'] === 0 ? __('vani-pricing-campaign::texts.storefront.sold_out') : __('vani-pricing-campaign::texts.storefront.remaining', ['count' => $offer['remaining']]) }}</p>
    @endif
    @if ($offer['per_customer'] !== null)
        <p class="text-slate-600">{{ __('vani-pricing-campaign::texts.storefront.per_customer', ['count' => $offer['per_customer']]) }}</p>
    @endif
    <script src="{{ $scriptSrc }}" defer data-vani-campaign-script
            data-sold-out="{{ __('vani-pricing-campaign::texts.storefront.sold_out') }}" data-remaining="{{ __('vani-pricing-campaign::texts.storefront.remaining', ['count' => ':count']) }}"
            data-units="{{ json_encode(['d' => __('vani-pricing-campaign::texts.storefront.day'), 'h' => __('vani-pricing-campaign::texts.storefront.hour'), 'm' => __('vani-pricing-campaign::texts.storefront.minute'), 's' => __('vani-pricing-campaign::texts.storefront.second')]) }}"></script>
</div>
