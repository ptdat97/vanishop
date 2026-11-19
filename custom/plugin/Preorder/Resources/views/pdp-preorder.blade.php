{{-- "Đặt trước" theo đúng SKU đang chọn (JS đọc bảng variant → ngày dự kiến). Chưa chọn SKU: không hiện gì. --}}
<p class="text-sm font-medium text-accent" data-preorder='@json($dates)' data-template="{{ __('vani-preorder::storefront.preorder_expected', ['date' => '__D__']) }}" data-undated="{{ __('vani-preorder::storefront.preorder_incoming') }}" hidden></p>
@if ($scriptSrc !== '')
    <script src="{{ $scriptSrc }}" defer></script>
@endif
