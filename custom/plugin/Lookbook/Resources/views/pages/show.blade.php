@extends('theme::layouts.app')

@section('title', $lookbook['meta_title'])
@if ($lookbook['meta_description'])
    @section('description', $lookbook['meta_description'])
@endif

@push('head')
    @if ($lookbook['cover_url'])<meta property="og:image" content="{{ $lookbook['cover_url'] }}">@endif
    <meta property="og:title" content="{{ $lookbook['name'] }}">
@endpush

@section('content')
    @include('theme::partials.breadcrumb', ['crumbs' => [['label' => __('Lookbook'), 'url' => vani_route('storefront.p.vani-lookbook.index')], ['label' => $lookbook['name']]]])
    <h1 class="mb-4 text-3xl font-semibold">{{ $lookbook['name'] }}</h1>
    @if ($lookbook['description_html'])
        {{-- Markdown đã bỏ HTML thô và link không an toàn. --}}
        <div class="vani-prose mb-8 max-w-3xl">{!! $lookbook['description_html'] !!}</div>
    @endif

    {{-- Thư viện ảnh: bấm mở ảnh gốc (không cần JS). --}}
    <div class="columns-1 gap-4 sm:columns-2 lg:columns-3" data-lookbook-gallery>
        @foreach ($lookbook['images'] as $image)
            <a href="{{ $image['full_url'] }}" target="_blank" rel="noopener" class="mb-4 block break-inside-avoid">
                <img src="{{ $image['url'] }}" @if ($image['srcset']) srcset="{{ $image['srcset'] }}" sizes="{{ \Modules\Storefront\View\ResponsiveImage::sizes(1152, 3, 1) }}" @endif
                     alt="{{ $image['alt'] }}" @if ($image['width']) width="{{ $image['width'] }}" height="{{ $image['height'] }}" @endif
                     @if (! $loop->first) loading="lazy" @endif class="w-full rounded-[var(--radius-theme)]">
            </a>
        @endforeach
    </div>

    @if ($lookbook['category'])
        <p class="mt-8"><a href="{{ $lookbook['category']['url'] }}" class="rounded-[var(--radius-theme)] bg-primary px-6 py-3 font-medium text-white" data-lookbook-category>{{ __('Xem thêm tại :name', ['name' => $lookbook['category']['name']]) }}</a></p>
    @endif
@endsection
