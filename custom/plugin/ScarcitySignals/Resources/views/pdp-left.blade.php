{{-- "Chỉ còn N" theo đúng size đang chọn (JS đọc bảng variant → số còn lại). Không JS / chưa chọn size: không hiện gì. --}}
<p class="text-sm font-medium text-accent" data-scarcity-left='@json($lowStock)' data-template="{{ __('Chỉ còn :count sản phẩm', ['count' => '__N__']) }}" hidden></p>
@php($src = vani_asset('vani.scarcity-signals', 'scarcity.js'))
@if ($src !== '')
    <script src="{{ $src }}" defer></script>
@endif
