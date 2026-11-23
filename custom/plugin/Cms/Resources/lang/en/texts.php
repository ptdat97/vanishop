<?php

// Chuỗi hiển thị từ server (ADR-049): nhãn định nghĩa, thông báo, lỗi — đọc bằng __('<namespace>::texts.<mục>.<khoá>').

return [
    'image_sizes' => [
        'banner_desktop' => 'Desktop category banner',
        'banner_mobile' => 'Mobile category banner',
        'post_cover' => 'CMS article cover',
    ],
    'article_category_link_type' => [
        'news_categories' => 'News categories',
    ],
    'article_link_type' => [
        'posts' => 'Posts',
    ],
    'banner_controller' => [
        'banner_deleted' => 'Banner deleted.',
        'banner_saved' => 'Banner saved.',
        'category_does_not_exist' => 'The category does not exist.',
        'category_id_deleted' => '#:category_id (deleted)',
        'hidden' => ' (hidden)',
    ],
    'category_controller' => [
        'categories' => ' categories.',
        'category_deleted' => 'Category deleted.',
        'category_saved' => 'Category saved.',
        'most' => 'At most',
        'path_already_belongs_post' => 'The path already belongs to a post.',
    ],
    'cms_service_provider' => [
        'announcement_bar_background_colour' => 'Announcement bar background colour',
        'announcement_bar_text_colour' => 'Announcement bar text colour',
        'announcement_lines_locale' => 'Announcement lines (:locale)',
        'content' => 'Content',
        'enable_announcement_bar' => 'Enable the announcement bar',
        'enable_offer_box_product_pages' => 'Enable the offer box on product pages',
        'extra_content' => 'Extra content',
        'format_2026_11_20_0800' => 'Format 2026-11-20 08:00; empty = now.',
        'format_2026_11_30_2359' => 'Format 2026-11-30 23:59; empty = never expires.',
        'format_rrggbb_empty_theme_s' => 'Format #rrggbb; empty = the theme\'s primary colour.',
        'format_rrggbb_empty_white' => 'Format #rrggbb; empty = white.',
        'hide_shared_offer_box_product' => 'Hide the shared offer box on this product',
        'highlights_locale' => 'Highlights (:locale)',
        'line_transition_time_ms_0' => 'Line transition time (ms, 0–2000)',
        'multiple_lines_rotating_automatically_when' => 'Multiple lines rotate automatically. The vani-base theme shows its announcement when the CMS bar is hidden.',
        'offer_box_content_locale_markdown' => 'Offer box content (:locale, Markdown)',
        'offer_box_position' => 'Offer box position',
        'one_announcement_per_line_single' => 'One announcement per line; single-line Markdown: **bold**, [link](/path). Empty = use the default language.',
        'one_point_per_line_most' => 'One point per line, at most',
        'one_shared_content_every_product' => 'One shared content for every product; hide it per product in the product form → Extra content.',
        'show_from_store_time' => 'Show from (store time)',
        'show_until_store_time' => 'Show until (store time)',
        'shown_below_product_name' => '; shown below the product name.',
        'time_each_line_shown_ms' => 'Time each line is shown (ms, 1000–15000)',
        'view_content_pages_posts' => 'View content (pages, posts)',
        'write_publish_delete_content' => 'Write, publish and delete content',
    ],
    'content_controller' => [
        'cover_image_no_longer_media' => 'The cover image is no longer in the Media library.',
        'deleted' => 'Deleted.',
        'path_already_belongs_category' => 'The path already belongs to a category.',
        'saved' => 'Saved.',
    ],
    'content_link_type' => [
        'title_not_public_yet' => ':title (not public yet)',
    ],
    'extra_content' => [
        'banner_image_required' => 'The selected file must be an image.',
        'after_add_cart_button' => 'After the add-to-cart button',
        'after_detailed_description' => 'After the detailed description',
        'after_price' => 'After the price',
        'button_link_must_store_path' => 'The button link must be a store path (starting with /) or https://.',
        'category_already_has_banner' => 'This category already has a banner.',
        'choose_desktop_image_before_mobile' => 'Choose the desktop image before the mobile image.',
        'some_images_no_longer_media' => 'Some images are no longer in the Media library.',
    ],
    'latest_posts_block' => [
        'latest_posts' => 'Latest posts',
        'number_posts_1_12' => 'Number of posts (1–12)',
        'title' => 'Title',
    ],
    'page_link_type' => [
        'content_page' => 'Content page',
    ],
];
