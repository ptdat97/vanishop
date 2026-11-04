{{--
    Ảnh responsive (§3.16-5): $src, $srcset (null = chỉ src), $alt, bố cục $layout = [vùng chứa px|null, số cột desktop, số cột mobile],
    $width/$height (tỉ lệ khung, chống nhảy trang), $class, $loading.
--}}
@php([$container, $columns, $mobileColumns] = $layout ?? [null, 1, 1])
<img src="{{ $src }}" alt="{{ $alt ?? '' }}"
     @if ($srcset ?? null) srcset="{{ $srcset }}" sizes="{{ \Modules\Storefront\View\ResponsiveImage::sizes($container, $columns, $mobileColumns) }}" @endif
     @if ($width ?? null) width="{{ $width }}" @endif @if ($height ?? null) height="{{ $height }}" @endif
     loading="{{ $loading ?? 'lazy' }}" class="{{ $class ?? '' }}">
