<input type="hidden" name="expected_total" value="{{ $quote['total']['amount'] }}">
@include('theme::partials.checkout-shipping')
@include('theme::partials.checkout-payment')
@include('theme::partials.checkout-summary')
@include('theme::partials.checkout-bar')
