{{-- Trang sản phẩm (slot pdp.before_add_to_cart): mã công khai áp cho sản phẩm này. Giống nhau với mọi khách (cache được). --}}
<section class="my-4 space-y-2 text-sm" data-vani-coupon-offers>
    <h2 class="font-medium">{{ __('vani-coupon-code::texts.pdp.title') }}</h2>
    <ul class="space-y-2">
        @foreach ($offers as $offer)
            <li class="flex items-start gap-3 rounded border border-dashed border-border p-2">
                @include('vani-coupon-code::offer', ['offer' => $offer])
                <button type="button" class="shrink-0 rounded border border-slate-300 px-2 py-1 text-xs" data-vani-coupon-copy="{{ $offer['code'] }}">{{ __('vani-coupon-code::texts.pdp.copy') }}</button>
            </li>
        @endforeach
    </ul>
    <script src="{{ $scriptSrc }}" defer data-vani-coupon-script data-copied="{{ __('vani-coupon-code::texts.pdp.copied') }}"></script>
</section>
