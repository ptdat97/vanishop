{{-- Phần đầu trang danh mục, thương hiệu, bộ sưu tập (cùng một bố cục, có h1).
     $page: name, description, image_url?, image_srcset?, logo_url?; $type: category|brand|collection.
     $banner (tuỳ chọn): dữ liệu khối Hero khi admin đặt Hero ở "Đầu trang" — chữ/ảnh trống thì dùng của trang. --}}
@php($banner ??= [])
@include('theme::partials.catalog-banner', [
    'titleId' => 'listing-title',
    'bannerAttribute' => new \Illuminate\Support\HtmlString('data-listing-banner data-'.e($type).'-banner'),
    'eyebrow' => null,
    'logoUrl' => $page['logo_url'] ?? null,
    'logoSrcset' => $page['logo_srcset'] ?? null,
    'logoAlt' => $page['name'],
    'heading' => ($banner['title'] ?? '') ?: $page['name'],
    'intro' => ($banner['subtitle'] ?? '') ?: ($page['description'] ?? null),
    'bannerImage' => ($banner['imageUrl'] ?? '') ?: ($page['image_url'] ?? null),
    'bannerImageSrcset' => ($banner['imageSrcset'] ?? '') ?: ($page['image_srcset'] ?? null),
    'actionUrl' => empty($banner['linkLabel']) ? '#listing-products' : (($banner['linkUrl'] ?? '') ?: '#listing-products'),
    'actionLabel' => ($banner['linkLabel'] ?? '') ?: __('theme::catalog.browse_products'),
])
