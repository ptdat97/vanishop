@php($text = $offer['show_stock'] && $offer['remaining'] !== null
    ? $offer['badge'].' · '.__('vani-pricing-campaign::texts.storefront.remaining_short', ['count' => $offer['remaining']])
    : $offer['badge'])

<x-vani::product-card-label :text="$text" :accent-color="$offer['accent_color']"
    :class="$offer['accent_color'] ? 'text-white' : $offer['badge_class']" data-vani-campaign-badge />
