{{-- `pageHeader` có khi Hero làm phần đầu trang (vd. vùng banner trang bộ sưu tập): dùng bố cục banner có h1. --}}
@isset($pageHeader)
    @include('theme::partials.collection-banner', ['collection' => $pageHeader, 'banner' => compact('title', 'subtitle', 'imageUrl', 'imageSrcset', 'linkUrl', 'linkLabel')])
@else
<section class="mb-12 overflow-hidden bg-subtle md:mb-16" data-block="hero" data-vani-motion="rise">
    <div @class(['grid items-center', 'md:grid-cols-[minmax(0,2fr)_minmax(0,3fr)]' => $imageUrl !== ''])>
        <div class="min-w-0 px-5 py-7 sm:p-8 lg:p-12">
            <h2 class="vani-editorial-title text-primary">{{ $title }}</h2>
            @if ($subtitle !== '')<p class="mt-3 max-w-prose break-words text-sm leading-relaxed text-accent sm:text-base">{{ $subtitle }}</p>@endif
            @if ($linkUrl !== '' && $linkLabel !== '')
                <a href="{{ $linkUrl }}" class="vani-button-primary mt-6">{{ $linkLabel }}</a>
            @endif
        </div>
        @if ($imageUrl !== '')
            {{-- Banner đầu trang thường là ảnh lớn nhất (LCP): tải ngay, ưu tiên cao; srcset khi ảnh từ Thư viện ảnh. --}}
            <div class="row-start-1 aspect-[4/3] max-h-[36rem] overflow-hidden md:col-start-2">
                <img src="{{ $imageUrl }}" alt="" class="h-full w-full object-cover" width="800" height="600" fetchpriority="high" decoding="async"
                     @if (($imageSrcset ?? '') !== '') srcset="{{ $imageSrcset }}" sizes="(min-width: 1440px) 760px, (min-width: 768px) 60vw, 100vw" @endif>
            </div>
        @endif
    </div>
</section>
@endisset
