{{-- Block Builder "Combo sản phẩm": combo đã ẩn, hết hạn hoặc có món ngừng bán thì không hiện gì. --}}
@if ($bundle !== null)
    <div class="mb-12" data-block="bundle">
        @if ($title !== '')
            <h2 class="mb-4 text-2xl font-medium tracking-tight">{{ $title }}</h2>
        @endif
        @include('vani-bundles::bundle', ['bundle' => $bundle, 'scriptSrc' => $scriptSrc])
    </div>
@endif
