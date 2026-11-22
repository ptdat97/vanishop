@include('theme::partials.catalog-banner', [
    'titleId' => 'collection-title',
    'bannerAttribute' => 'data-collection-banner',
    'eyebrow' => null,
    'heading' => ($banner['title'] ?? '') ?: $collection['name'],
    'intro' => ($banner['subtitle'] ?? '') ?: $collection['description'],
    'bannerImage' => $banner['imageUrl'] ?? null,
    'bannerImageSrcset' => $banner['imageSrcset'] ?? null,
    'actionUrl' => empty($banner['linkLabel']) ? '#collection-products' : (($banner['linkUrl'] ?? '') ?: '#collection-products'),
    'actionLabel' => ($banner['linkLabel'] ?? '') ?: __('theme::catalog.browse_collection'),
])
