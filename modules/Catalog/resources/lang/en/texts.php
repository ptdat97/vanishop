<?php

// Chuỗi hiển thị từ server (ADR-049): nhãn định nghĩa, thông báo, lỗi — đọc bằng __('<namespace>::texts.<mục>.<khoá>').

return [
    'admin_queries' => [
        'no_category' => 'No category',
        'no_description' => 'No description',
        'no_image' => 'No image',
        'no_sku' => 'No SKU',
        'sku_missing_weight' => 'SKU without weight (using the default)',
    ],
    'catalog_navigation' => [
        'attributes' => 'Attributes',
        'brand' => 'Brand',
        'category' => 'Category',
        'collections' => 'Collections',
        'filters' => 'Filters',
        'link_type' => 'Link type',
        'products' => 'Products',
        'variant_options' => 'Variant options',
    ],
    'catalog_service_provider' => [
        'default_weight_gram' => 'Default weight per item (grams)',
        'default_weight_gram_help' => 'Used when a SKU has no weight: shipping rates at checkout and carrier files. Enter real weights per SKU if shipping is charged by weight.',
        'edit_catalogue_products_brands_categories' => 'Edit the catalogue (products, brands, categories, attributes, colours, sizes)',
        'manage_media_library_upload_folders' => 'Manage the Media library (upload, folders, rename, delete unused images)',
        'media_library' => 'Media library',
        'placeholder_image' => 'Placeholder image',
        'products' => 'Products',
        'shown_products_without_images_product' => 'Shown for products without images (product cards, Storefront API) and for vani_image() when an image is missing. Leave empty: the theme\'s empty frame.',
        'style_code_digits' => 'Digits in suggested code',
        'style_code_digits_help' => 'Length of the number part when a prefix is set, e.g. 4 → SP0001.',
        'style_code_prefix' => 'Product code prefix',
        'style_code_prefix_help' => 'Suggested code for new products = prefix + sequence, e.g. SP → SP0001, SP0002. Empty: increment the trailing number of the latest code (AO012 → AO013).',
        'view_choose_images_from_media' => 'View and choose images from the Media library',
    ],
    'image_size_controller' => [
        'image_cache_cleared_thumbnails_regenerated' => 'Image cache cleared; thumbnails are regenerated as people view them.',
        'image_sizes_saved_new_images' => 'Image sizes saved. New images are generated gradually as people view them.',
    ],
    'image_sizes' => [
        'keep_original_ratio' => 'Keep the original ratio',
        'product_widthpx' => 'Product :widthpx',
        'requires_1' => 'Requires 1–',
        'text_11_square' => '1:1 (square)',
        'text_23_portrait_fashion' => '2:3 (portrait, fashion)',
        'widths' => ' widths.',
    ],
    'product_controller' => [
        'add_category' => 'Add to category',
        'add_collection' => 'Add to collection',
        'brand_leave_empty_remove_brand' => 'Brand (leave empty = remove the brand)',
        'category' => 'Category',
        'change_brand' => 'Change brand',
        'collections' => 'Collections',
        'delete_draft' => 'Delete draft',
        'export_file' => 'Export file',
        'put_sale' => 'Put on sale',
        'remove_from_category' => 'Remove from category',
        'remove_from_collection' => 'Remove from collection',
        'stop_selling_hide_from_store' => 'Stop selling (hide from the store)',
    ],
    'stale_record' => [
        'data_was_updated_someone_else' => 'The data was updated by someone else. Please reload the page and edit again.',
    ],
];
