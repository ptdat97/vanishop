{{-- "Đặt trước" theo đúng SKU đang chọn (JS đọc bảng variant → ngày dự kiến). Không JS / chưa chọn SKU: không hiện gì. --}}
<p class="text-sm font-medium text-accent" data-preorder='@json($dates)' data-template="{{ __('Đặt trước — dự kiến có hàng :date', ['date' => '__D__']) }}" data-undated="{{ __('Đặt trước — hàng đang về') }}" hidden></p>
@if ($scriptSrc !== '')
    <script src="{{ $scriptSrc }}" defer></script>
@endif
