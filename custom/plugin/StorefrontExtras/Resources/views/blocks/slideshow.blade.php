{{-- Trình chiếu: không JS → trượt ngang (scroll-snap); JS tự chuyển theo data-interval (dừng khi rê chuột/giảm chuyển động). --}}
@if ($slides !== [])
    <section class="relative mb-10" data-block="extras_slideshow" data-extras-slideshow data-interval="{{ $interval }}" aria-roledescription="carousel">
        <div class="flex snap-x snap-mandatory overflow-x-auto rounded-[var(--radius-theme)]" data-extras-slides>
            @foreach ($slides as $slide)
                <figure class="relative w-full shrink-0 snap-start" aria-roledescription="slide" aria-label="{{ $loop->iteration }} / {{ count($slides) }}">
                    {{-- Slide đầu là ảnh lớn nhất trên trang (LCP): tải ngay, ưu tiên cao; slide sau tải lười. --}}
                    <img src="{{ $slide['image'] }}" alt="{{ $slide['title'] }}" class="h-[50vh] max-h-[560px] w-full object-cover" decoding="async"
                         @if (($slide['srcset'] ?? '') !== '') srcset="{{ $slide['srcset'] }}" sizes="(min-width: 1152px) 1152px, 100vw" @endif
                         @if ($loop->first) fetchpriority="high" @else loading="lazy" @endif>
                    @if ($slide['title'] !== '' || $slide['text'] !== '')
                        <figcaption class="absolute inset-x-0 bottom-0 bg-gradient-to-t from-black/60 to-transparent p-6 text-white">
                            @if ($slide['title'] !== '')<p class="text-2xl font-semibold">{{ $slide['title'] }}</p>@endif
                            @if ($slide['text'] !== '')<p>{{ $slide['text'] }}</p>@endif
                            @if ($slide['link'] !== '' && $slide['label'] !== '')<a href="{{ $slide['link'] }}" class="mt-3 inline-block rounded-[var(--radius-theme)] bg-white px-4 py-2 text-sm font-medium text-slate-900">{{ $slide['label'] }}</a>@endif
                        </figcaption>
                    @elseif ($slide['link'] !== '')
                        <a href="{{ $slide['link'] }}" class="absolute inset-0"><span class="sr-only">{{ __('Xem') }}</span></a>
                    @endif
                </figure>
            @endforeach
        </div>
    </section>
@endif
