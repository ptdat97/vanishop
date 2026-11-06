<section class="mb-10 overflow-hidden rounded-[var(--radius-theme)] bg-slate-100" data-block="hero">
    <div class="grid items-center gap-6 md:grid-cols-2">
        <div class="p-8">
            <h2 class="text-3xl font-semibold text-primary">{{ $title }}</h2>
            @if ($subtitle !== '')<p class="mt-3 text-slate-600">{{ $subtitle }}</p>@endif
            @if ($linkUrl !== '' && $linkLabel !== '')
                <a href="{{ $linkUrl }}" class="mt-6 inline-block rounded-[var(--radius-theme)] bg-primary px-6 py-3 font-medium text-white">{{ $linkLabel }}</a>
            @endif
        </div>
        @if ($imageUrl !== '')
            {{-- Banner đầu trang thường là ảnh lớn nhất (LCP): tải ngay, ưu tiên cao; srcset khi ảnh từ Thư viện ảnh. --}}
            <img src="{{ $imageUrl }}" alt="" class="h-full max-h-96 w-full object-cover" width="800" height="600" fetchpriority="high" decoding="async"
                 @if (($imageSrcset ?? '') !== '') srcset="{{ $imageSrcset }}" sizes="(min-width: 768px) 50vw, 100vw" @endif>
        @endif
    </div>
</section>
