{{-- Phần đầu trang danh mục, thương hiệu, bộ sưu tập (cùng một bố cục, có h1).
     $page: name, description, image_url?, image_srcset?, logo_url?; $type: category|brand|collection. --}}
@include('theme::partials.catalog-banner', [
    'titleId' => 'listing-title',
    'bannerAttribute' => new \Illuminate\Support\HtmlString('data-listing-banner data-'.e($type).'-banner'),
    'eyebrow' => null,
    'logoUrl' => $page['logo_url'] ?? null,
    'logoSrcset' => $page['logo_srcset'] ?? null,
    'logoAlt' => $page['name'],
    'heading' => $page['name'],
    'intro' => $page['description'] ?? null,
    'bannerImage' => $page['image_url'] ?? null,
    'bannerImageSrcset' => $page['image_srcset'] ?? null,
    'actionUrl' => '#listing-products',
    'actionLabel' => __('theme::catalog.browse_products'),
])
