{{-- Block Builder "Chiến dịch giá": không có chiến dịch phù hợp thì không hiện gì. --}}
@if ($campaign !== null)
    <div data-block="pricing_campaign">
        @include('vani-pricing-campaign::campaign', ['campaign' => $campaign, 'title' => $title !== '' ? $title : null])
        <script src="{{ $scriptSrc }}" defer data-vani-campaign-script
                data-units="{{ json_encode(['d' => __('vani-pricing-campaign::texts.storefront.day'), 'h' => __('vani-pricing-campaign::texts.storefront.hour'), 'm' => __('vani-pricing-campaign::texts.storefront.minute'), 's' => __('vani-pricing-campaign::texts.storefront.second')]) }}"></script>
    </div>
@endif
