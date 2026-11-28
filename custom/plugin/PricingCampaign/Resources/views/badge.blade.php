{{-- Thẻ sản phẩm (slot plp.card_badges): badge của chiến dịch đang chạy có sản phẩm này. --}}
<span class="absolute start-2 top-2 rounded px-2 py-0.5 text-xs font-medium {{ $offer['accent_color'] ? 'text-white' : $offer['badge_class'] }}"
      @if ($offer['accent_color']) style="background-color: {{ $offer['accent_color'] }}" @endif data-vani-campaign-badge>
    {{ $offer['badge'] }}@if ($offer['show_stock'] && $offer['remaining'] !== null) · {{ __('vani-pricing-campaign::texts.storefront.remaining_short', ['count' => $offer['remaining']]) }}@endif
</span>
