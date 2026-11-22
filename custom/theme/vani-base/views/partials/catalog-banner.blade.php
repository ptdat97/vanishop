<section class="relative isolate mb-12 flex min-h-80 items-end overflow-hidden rounded-[var(--radius-theme)] bg-gradient-to-br from-primary via-slate-800 to-slate-950 px-6 py-10 text-white sm:min-h-96 sm:px-10 md:px-14 md:py-14" aria-labelledby="{{ $titleId }}" {{ $bannerAttribute }}>
    @if ($bannerImage)
        <img src="{{ $bannerImage }}" alt="" class="absolute inset-0 -z-20 h-full w-full object-cover" width="1600" height="900" fetchpriority="high" decoding="async"
            @if ($bannerImageSrcset) srcset="{{ $bannerImageSrcset }}" sizes="100vw" @endif>
    @endif
    <div class="absolute inset-0 -z-10 bg-gradient-to-r from-slate-950/90 via-slate-900/65 to-slate-900/10"></div>
    <div class="max-w-2xl">
        @if ($eyebrow)
            <p class="mb-3 text-sm font-semibold uppercase tracking-[0.2em] text-white/75">{{ $eyebrow }}</p>
        @endif
        <h1 id="{{ $titleId }}" class="text-3xl font-semibold leading-tight sm:text-5xl">{{ $heading }}</h1>
        @if ($intro)
            <p class="mt-4 max-w-xl text-base text-white/85 sm:text-lg">{{ $intro }}</p>
        @endif
        <a href="{{ $actionUrl }}" class="mt-7 inline-flex rounded-[var(--radius-theme)] bg-white px-5 py-3 font-semibold text-slate-900 transition-colors hover:bg-white/90 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-white">{{ $actionLabel }}</a>
    </div>
</section>
