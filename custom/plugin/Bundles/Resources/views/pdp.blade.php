{{-- Trang sản phẩm (slot pdp.after_details): các combo có sản phẩm này. --}}
<div class="mt-8 space-y-4" data-vani-bundles>
    <h2 class="text-base font-medium">{{ __('vani-bundles::texts.section_title') }}</h2>
    @foreach ($bundles as $bundle)
        @include('vani-bundles::bundle', ['bundle' => $bundle, 'scriptSrc' => $loop->first ? $scriptSrc : ''])
    @endforeach
</div>
