{{-- $style = grid|list; $gridClass và $layout tùy chỉnh theo vùng hiển thị; $eager = số ảnh đầu tải ngay (lưới là nội dung chính, hàng đầu nằm trong màn hình đầu). --}}
@php($productStyle = $style ?? 'grid')
@php($imageLayout = $layout ?? ($productStyle === 'list' ? [null, 10, 5] : [1152, 4, 2]))
<div class="{{ $gridClass ?? ($productStyle === 'list' ? 'space-y-4' : 'grid grid-cols-2 gap-6 md:grid-cols-4') }}" @if ($stagger ?? false) data-vani-motion-stagger @endif>
    @foreach ($products as $product)
        @include('theme::partials.product-card', ['product' => $product, 'layout' => $imageLayout, 'style' => $productStyle, 'loading' => $loop->index < ($eager ?? 0) ? 'eager' : 'lazy'])
    @endforeach
</div>
