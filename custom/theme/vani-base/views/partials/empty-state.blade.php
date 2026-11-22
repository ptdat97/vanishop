<div class="{{ $emptyClass }}">
    <p>{{ $emptyMessage }}</p>
    @if (isset($emptyAction))
        <a href="{{ $emptyAction['url'] }}" class="inline-flex min-h-10 items-center underline underline-offset-4" @if ($emptyAction['close_cart'] ?? false) data-cart-close @endif>{{ $emptyAction['label'] }}</a>
    @endif
</div>
