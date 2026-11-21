<section class="mb-10 overflow-hidden bg-subtle" data-block="hero" data-vani-motion="rise">
    <div @class(['grid items-center', 'md:grid-cols-[2fr_3fr]' => $imageUrl !== ''])>
        <div class="p-5 sm:p-8 lg:p-12">
            <h2 class="vani-editorial-title text-primary">{{ $title }}</h2>
            @if ($subtitle !== '')<p class="mt-3 text-slate-600">{{ $subtitle }}</p>@endif
            @if ($linkUrl !== '' && $linkLabel !== '')
                <a href="{{ $linkUrl }}" class="vani-button-primary mt-6">{{ $linkLabel }}</a>
            @endif
        </div>
        @if ($imageUrl !== '')
            {{-- Banner đầu trang thường là ảnh lớn nhất (LCP): tải ngay, ưu tiên cao; srcset khi ảnh từ Thư viện ảnh. --}}
            <img src="{{ $imageUrl }}" alt="" class="row-start-1 h-full max-h-[36rem] md:col-start-2 w-full object-cover" width="800" height="600" fetchpriority="high" decoding="async" data-vani-motion-hover
                 @if (($imageSrcset ?? '') !== '') srcset="{{ $imageSrcset }}" sizes="(min-width: 768px) 60vw, 100vw" @endif>
        @endif
    </div>
</section>
