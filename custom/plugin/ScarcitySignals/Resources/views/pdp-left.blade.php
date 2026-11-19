{{-- "Chỉ còn N" theo đúng SKU đang chọn (JS đọc bảng variant → số còn lại). Không JS / chưa chọn SKU: không hiện gì. --}}
<p class="text-sm font-medium text-accent" data-scarcity-left='@json($lowStock)' data-template="{{ __('vani-scarcity-signals::storefront.only_count_left', ['count' => '__N__']) }}" hidden></p>
@if ($scriptSrc !== '')
    <script src="{{ $scriptSrc }}" defer></script>
@endif
