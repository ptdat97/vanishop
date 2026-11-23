<?php

// Chuỗi hiển thị từ server (ADR-049): nhãn định nghĩa, thông báo, lỗi — đọc bằng __('<namespace>::texts.<mục>.<khoá>').

return [
    'image_sizes' => [
        'banner_desktop' => 'Banner danh mục trên máy tính',
        'banner_mobile' => 'Banner danh mục trên điện thoại',
        'post_cover' => 'Ảnh bìa bài viết CMS',
    ],
    'article_category_link_type' => [
        'news_categories' => 'Chuyên mục tin tức',
    ],
    'article_link_type' => [
        'posts' => 'Bài viết',
    ],
    'banner_controller' => [
        'banner_deleted' => 'Đã xoá banner.',
        'banner_saved' => 'Đã lưu banner.',
        'category_does_not_exist' => 'Danh mục không tồn tại.',
        'category_id_deleted' => '#:category_id (đã xoá)',
        'hidden' => ' (đang ẩn)',
    ],
    'category_controller' => [
        'categories' => ' chuyên mục.',
        'category_deleted' => 'Đã xoá chuyên mục.',
        'category_saved' => 'Đã lưu chuyên mục.',
        'most' => 'Tối đa ',
        'path_already_belongs_post' => 'Đường dẫn đã là của một bài viết.',
    ],
    'cms_service_provider' => [
        'announcement_bar_background_colour' => 'Màu nền thanh thông báo',
        'announcement_bar_text_colour' => 'Màu chữ thanh thông báo',
        'announcement_lines_locale' => 'Các dòng thông báo (:locale)',
        'content' => 'Nội dung',
        'enable_announcement_bar' => 'Bật thanh thông báo đầu trang',
        'enable_offer_box_product_pages' => 'Bật hộp ưu đãi ở trang sản phẩm',
        'extra_content' => 'Nội dung thêm',
        'format_2026_11_20_0800' => 'Dạng 2026-11-20 08:00; trống = ngay.',
        'format_2026_11_30_2359' => 'Dạng 2026-11-30 23:59; trống = không hết hạn.',
        'format_rrggbb_empty_theme_s' => 'Dạng #rrggbb; trống = màu chính của giao diện.',
        'format_rrggbb_empty_white' => 'Dạng #rrggbb; trống = trắng.',
        'hide_shared_offer_box_product' => 'Ẩn hộp ưu đãi dùng chung ở sản phẩm này',
        'highlights_locale' => 'Điểm nổi bật (:locale)',
        'line_transition_time_ms_0' => 'Thời gian chuyển dòng (ms, 0–2000)',
        'multiple_lines_rotating_automatically_when' => 'Nhiều dòng, tự xoay vòng. Giao diện vani-base hiện dòng thông báo của theme khi thanh CMS không hiển thị.',
        'offer_box_content_locale_markdown' => 'Nội dung hộp ưu đãi (:locale, Markdown)',
        'offer_box_position' => 'Vị trí hộp ưu đãi',
        'one_announcement_per_line_single' => 'Mỗi dòng một thông báo; Markdown một dòng: **đậm**, [link](/duong-dan). Trống = theo ngôn ngữ mặc định.',
        'one_point_per_line_most' => 'Mỗi dòng một ý, tối đa ',
        'one_shared_content_every_product' => 'Một nội dung chung cho mọi sản phẩm; ẩn riêng từng sản phẩm ở form sản phẩm → Nội dung thêm.',
        'show_from_store_time' => 'Hiện từ (giờ cửa hàng)',
        'show_until_store_time' => 'Hiện đến (giờ cửa hàng)',
        'shown_below_product_name' => '; hiện dưới tên sản phẩm.',
        'time_each_line_shown_ms' => 'Thời gian hiện mỗi dòng (ms, 1000–15000)',
        'view_content_pages_posts' => 'Xem nội dung (trang, bài viết)',
        'write_publish_delete_content' => 'Soạn, đăng, xoá nội dung',
    ],
    'content_controller' => [
        'cover_image_no_longer_media' => 'Ảnh bìa không còn trong Thư viện ảnh.',
        'deleted' => 'Đã xoá.',
        'path_already_belongs_category' => 'Đường dẫn đã là của một chuyên mục.',
        'saved' => 'Đã lưu.',
    ],
    'content_link_type' => [
        'title_not_public_yet' => ':title (chưa công khai)',
    ],
    'extra_content' => [
        'banner_image_required' => 'Tệp được chọn phải là ảnh.',
        'after_add_cart_button' => 'Sau nút thêm vào giỏ',
        'after_detailed_description' => 'Sau mô tả chi tiết',
        'after_price' => 'Sau giá',
        'button_link_must_store_path' => 'Link nút phải là đường dẫn trong cửa hàng (bắt đầu bằng /) hoặc https://.',
        'category_already_has_banner' => 'Danh mục này đã có banner.',
        'choose_desktop_image_before_mobile' => 'Chọn ảnh desktop trước khi chọn ảnh mobile.',
        'some_images_no_longer_media' => 'Có ảnh không còn trong Thư viện ảnh.',
    ],
    'latest_posts_block' => [
        'latest_posts' => 'Bài viết mới',
        'number_posts_1_12' => 'Số bài (1–12)',
        'title' => 'Tiêu đề',
    ],
    'page_link_type' => [
        'content_page' => 'Trang nội dung',
    ],
];
