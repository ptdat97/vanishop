<fieldset id="checkout-payment" tabindex="-1" class="vani-checkout-group space-y-2" data-checkout-payment>
    <legend class="mb-3 text-lg font-medium">{{ __('theme::checkout.checkout') }}</legend>
    @forelse ($quote['payment_methods'] as $method)
        <label class="vani-checkout-choice">
            <input type="radio" name="payment_method" value="{{ $method['code'] }}" @checked(($values['payment_method'] ?? $quote['payment_methods'][0]['code'] ?? null) === $method['code'])>
            {{ $method['label'] }}
        </label>
    @empty
        <p class="text-sm text-red-600">{{ __('theme::checkout.no_payment_method') }}</p>
    @endforelse
    @error('payment_method')<p class="text-sm text-red-600">{{ $message }}</p>@enderror
</fieldset>
