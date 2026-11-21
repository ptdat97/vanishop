@if ($slides !== [])
    @php($multiple = count($slides) > 1)
    <section @class(['relative mb-10 overflow-hidden rounded-[var(--radius-theme)]', 'vani-carousel swiper' => $multiple])
             data-block="extras_slideshow" data-extras-slideshow data-interval="{{ $interval }}"
             @if ($multiple) data-carousel="{{ json_encode(['rewind' => true, 'autoplay' => $interval > 0 ? ['delay' => $interval * 1000] : false]) }}" @endif
             aria-roledescription="carousel" aria-label="{{ __('vani-storefront-extras::texts.slideshow_block.image_slideshow') }}">
        <div @class(['swiper-wrapper' => $multiple]) data-extras-slides>
            @foreach ($slides as $slide)
                <figure @class(['relative w-full', 'swiper-slide' => $multiple]) aria-roledescription="slide" aria-label="{{ $loop->iteration }} / {{ count($slides) }}">
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
                        <a href="{{ $slide['link'] }}" class="absolute inset-0"><span class="sr-only">{{ __('vani-storefront-extras::storefront.view') }}</span></a>
                    @endif
                </figure>
            @endforeach
        </div>
        @if ($multiple)
            <button type="button" class="vani-carousel-nav left-2" data-carousel-prev aria-label="{{ __('theme::common.previous_image') }}">‹</button>
            <button type="button" class="vani-carousel-nav right-2" data-carousel-next aria-label="{{ __('theme::common.next_image') }}">›</button>
        @endif
    </section>
@endif
