{{--
    Menu di động — Split Category Navigation (CategoryDiscovery): header + ô tìm kiếm, cột trái mục cấp 1, cột phải
    banner / nhóm / lưới của mục đang chọn, thanh điều hướng dưới.

    Nguồn dữ liệu (js/mobile-category-nav.js chuẩn hoá về cùng một dạng, không gắn ngành hàng):
    1. $items = menu vị trí "mobile" của Admin (Giao diện → Menu; chưa xuất bản → menu "header"), đã phân giải ở server
       — nhúng JSON, không gọi thêm API. Cấp 1 → cột trái (ảnh cấp 1 = banner; cấp 1 không có con mà có link → mở link),
       cấp 2 có con → tiêu đề nhóm, mục lá → ô lưới (ảnh của mục).
    2. $items = null (chưa có menu nào) → cây danh mục Catalog qua Storefront API `GET /api/storefront/v1/categories`
       (ADR-044), tải lần đầu mở drawer, skeleton trong lúc chờ. Banner: plugin thêm `banner` qua filter
       `vani.api.storefront.categories.index`.
    Markup nằm trong <template> của các partial `theme::partials.mobile-category-nav.*` — theme con override từng file.
--}}
<div class="flex min-h-0 flex-1 flex-col bg-white" data-vani-mobile-menu data-vani-category-nav
    data-source="{{ route('api.storefront.v1.categories.index') }}" data-locale="{{ app()->getLocale() }}"
    data-category-url="{{ vani_route('storefront.category', '__vani_slug__') }}">
    @if ($items !== null)
        <script type="application/json" data-cnav-items>@json($items)</script>
    @endif
    @include('theme::partials.mobile-category-nav.header')

    <div class="flex min-h-0 flex-1 border-t border-gray-100">
        @include('theme::partials.mobile-category-nav.sidebar')
        @include('theme::partials.mobile-category-nav.content')
    </div>

    @include('theme::partials.mobile-category-nav.bottom-nav', ['current' => 'categories'])
</div>
