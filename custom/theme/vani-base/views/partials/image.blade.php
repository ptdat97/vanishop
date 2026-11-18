{{--
    Ảnh responsive (§3.16-5): $src, $srcset (null = chỉ src), $alt, bố cục $layout = [vùng chứa px|null, số cột desktop, số cột mobile],
    $width/$height (tỉ lệ khung, chống nhảy trang), $class, $loading. `$sizes` do Core dựng từ $layout (ImageComposer).
--}}
<img src="{{ $src }}" alt="{{ $alt ?? '' }}"
     @if ($sizes) srcset="{{ $srcset }}" sizes="{{ $sizes }}" @endif
     @if ($width ?? null) width="{{ $width }}" @endif @if ($height ?? null) height="{{ $height }}" @endif
     loading="{{ $loading ?? 'lazy' }}" decoding="async" @if (($loading ?? 'lazy') === 'eager') fetchpriority="high" @endif class="{{ $class ?? '' }}">
