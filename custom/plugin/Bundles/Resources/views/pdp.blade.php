{{-- Trang sản phẩm (slot pdp.after_details): các combo có sản phẩm này. --}}
<div class="mt-8 space-y-4" data-vani-bundles>
    <div class="flex flex-wrap items-baseline justify-between gap-2">
        <h2 class="text-base font-medium">{{ __('vani-bundles::texts.section_title') }}</h2>
        <a class="vani-text-link text-sm" href="{{ vani_route('storefront.p.vani-bundles.index') }}">{{ __('vani-bundles::texts.view_all') }}</a>
    </div>
    @foreach ($bundles as $bundle)
        @include('vani-bundles::bundle', ['bundle' => $bundle, 'scriptSrc' => $loop->first ? $scriptSrc : ''])
    @endforeach
</div>
