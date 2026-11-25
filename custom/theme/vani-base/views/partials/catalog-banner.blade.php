<section class="vani-catalog-banner mb-8 overflow-hidden rounded-[var(--radius-theme)] bg-subtle md:mb-12" aria-labelledby="{{ $titleId }}" {{ $bannerAttribute }}>
    @if ($bannerImage)
        <div class="vani-catalog-banner-media overflow-hidden">
            <img src="{{ $bannerImage }}" alt="" class="h-full w-full object-cover" width="1600" height="900" fetchpriority="high" decoding="async"
                @if ($bannerImageSrcset) srcset="{{ $bannerImageSrcset }}" sizes="(min-width: 1440px) 700px, (min-width: 768px) 60vw, 100vw" @endif>
        </div>
    @endif
    <div class="vani-catalog-banner-copy flex flex-col items-start justify-center">
        @if (! empty($logoUrl))
            <img src="{{ $logoUrl }}" @if (! empty($logoSrcset)) srcset="{{ $logoSrcset }}" sizes="128px" @endif alt="{{ $logoAlt ?? '' }}" width="128" height="56" class="mb-4 h-14 w-32 max-w-full object-contain object-left">
        @endif
        @if ($eyebrow)
            <p class="mb-3 text-xs font-medium uppercase tracking-[0.14em] text-accent">{{ $eyebrow }}</p>
        @endif
        <h1 id="{{ $titleId }}" class="vani-editorial-title">{{ $heading }}</h1>
        @if ($intro)
            <p class="mt-3 max-w-prose text-sm leading-relaxed text-accent sm:text-base">{{ $intro }}</p>
        @endif
        <a href="{{ $actionUrl }}" class="vani-text-link mt-3">{{ $actionLabel }}</a>
    </div>
</section>
