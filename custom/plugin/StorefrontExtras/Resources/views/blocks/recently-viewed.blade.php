{{-- Khung rỗng — JS (extras.js) điền từ danh sách đã xem trong trình duyệt; rỗng thì giữ ẩn. --}}
<section class="mb-10" hidden data-extras-recent data-limit="{{ $limit }}" data-exclude-slugs="{{ implode(',', $exclude) }}"
         data-products-url="{{ route('api.storefront.v1.products.index') }}" data-locale="{{ app()->getLocale() }}"
         data-product-url="{{ vani_route('storefront.product', '__slug__') }}">
    @if ($title !== '')<h2 class="mb-4 text-lg font-semibold">{{ $title }}</h2>@endif
    <div @class(['grid grid-cols-2 gap-6 md:grid-cols-4' => $layout === 'grid', 'flex snap-x gap-4 overflow-x-auto pb-2' => $layout === 'scroll']) data-extras-recent-list data-layout="{{ $layout }}"></div>
</section>
