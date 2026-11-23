<fieldset id="checkout-shipping" tabindex="-1" class="vani-checkout-group space-y-2" data-checkout-shipping>
    <legend class="mb-3 text-lg font-medium">{{ __('theme::checkout.delivery') }}</legend>
    @forelse ($quote['shipping_options'] as $option)
        <label class="vani-checkout-choice">
            <input type="radio" name="shipping_method" value="{{ $option['code'] }}" @checked(($values['shipping_method'] ?? $quote['shipping']['code'] ?? null) === $option['code'])>
            <span class="min-w-0 flex-1 break-words">{{ $option['label'] }}</span><span class="shrink-0 font-medium">{{ $option['fee']['formatted'] }}</span>
        </label>
    @empty
        <p class="text-sm text-red-600">{{ __('theme::checkout.no_delivery_method') }}</p>
    @endforelse
    @error('shipping_method')<p class="text-sm text-red-600">{{ $message }}</p>@enderror
</fieldset>
