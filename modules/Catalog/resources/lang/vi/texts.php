<?php

// Chuỗi hiển thị từ server (ADR-049): nhãn định nghĩa, thông báo, lỗi — đọc bằng __('<namespace>::texts.<mục>.<khoá>').

return [
    'admin_queries' => [
        'no_category' => 'Chưa có danh mục',
        'no_description' => 'Chưa có mô tả',
        'no_image' => 'Chưa có ảnh',
        'no_sku' => 'Chưa có SKU',
        'sku_missing_weight' => 'SKU thiếu khối lượng',
    ],
    'catalog_navigation' => [
        'attributes' => 'Thuộc tính',
        'brand' => 'Thương hiệu',
        'category' => 'Danh mục',
        'collections' => 'Bộ sưu tập',
        'filters' => 'Bộ lọc',
        'link_type' => 'Loại liên kết',
        'products' => 'Sản phẩm',
        'variant_options' => 'Tuỳ chọn biến thể',
    ],
    'catalog_service_provider' => [
        'edit_catalogue_products_brands_categories' => 'Sửa catalog (sản phẩm, thương hiệu, danh mục, thuộc tính, màu, size)',
        'manage_media_library_upload_folders' => 'Quản lý Thư viện ảnh (tải lên, thư mục, đổi tên, xoá ảnh không dùng)',
        'media_library' => 'Thư viện ảnh',
        'placeholder_image' => 'Ảnh thay thế',
        'product_search' => 'Tìm kiếm sản phẩm',
        'products' => 'Sản phẩm',
        'shown_products_without_images_product' => 'Hiện thay cho sản phẩm chưa có ảnh (thẻ sản phẩm, Storefront API) và cho vani_image() khi thiếu ảnh. Để trống: khung trống của theme.',
        'view_choose_images_from_media' => 'Xem và chọn ảnh từ Thư viện ảnh',
    ],
    'image_size_controller' => [
        'image_cache_cleared_thumbnails_regenerated' => 'Đã xoá cache ảnh; bản thu nhỏ được tạo lại khi có người xem.',
        'image_sizes_saved_new_images' => 'Đã lưu kích thước ảnh. Ảnh mới được tạo dần khi có người xem.',
    ],
    'image_sizes' => [
        'keep_original_ratio' => 'Giữ tỉ lệ gốc',
        'product_widthpx' => 'Sản phẩm :widthpx',
        'requires_1' => 'Cần 1–',
        'text_11_square' => '1:1 (vuông)',
        'text_23_portrait_fashion' => '2:3 (dọc, thời trang)',
        'widths' => ' bề rộng.',
    ],
    'product_controller' => [
        'add_category' => 'Thêm vào danh mục',
        'add_collection' => 'Thêm vào bộ sưu tập',
        'brand_leave_empty_remove_brand' => 'Thương hiệu (để trống = bỏ thương hiệu)',
        'category' => 'Danh mục',
        'change_brand' => 'Đổi thương hiệu',
        'collections' => 'Bộ sưu tập',
        'delete_draft' => 'Xoá bản nháp',
        'export_file' => 'Xuất file',
        'put_sale' => 'Mở bán',
        'remove_from_category' => 'Gỡ khỏi danh mục',
        'remove_from_collection' => 'Gỡ khỏi bộ sưu tập',
        'stop_selling_hide_from_store' => 'Ngừng bán (ẩn khỏi cửa hàng)',
    ],
    'stale_record' => [
        'data_was_updated_someone_else' => 'Dữ liệu đã được người khác cập nhật. Vui lòng tải lại trang rồi sửa lại.',
    ],
];
