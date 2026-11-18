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
    @include('theme::partials.breadcrumb', ['crumbs' => $crumbs])
    <h1 class="mb-4 text-3xl font-semibold">{{ $lookbook['name'] }}</h1>
    @if ($lookbook['description_html'])
        {{-- Markdown đã bỏ HTML thô và link không an toàn. --}}
        <div class="vani-prose mb-8 max-w-3xl">{!! $lookbook['description_html'] !!}</div>
    @endif

    {{-- Thư viện ảnh: bấm mở ảnh gốc (không cần JS). Ảnh qua partial ảnh của theme (lưới 3 cột trong khung 1152px → `sizes`). --}}
    <div class="columns-1 gap-4 sm:columns-2 lg:columns-3" data-lookbook-gallery>
        @foreach ($lookbook['images'] as $image)
            <a href="{{ $image['full_url'] }}" target="_blank" rel="noopener" class="mb-4 block break-inside-avoid">
                @include('theme::partials.image', [
                    'src' => $image['url'], 'srcset' => $image['srcset'], 'alt' => $image['alt'], 'layout' => [1152, 3, 1],
                    'width' => $image['width'], 'height' => $image['height'], 'loading' => $loop->first ? 'eager' : 'lazy', 'class' => 'w-full rounded-[var(--radius-theme)]',
                ])
            </a>
        @endforeach
    </div>

    @if ($lookbook['category'])
        <p class="mt-8"><a href="{{ $lookbook['category']['url'] }}" class="rounded-[var(--radius-theme)] bg-primary px-6 py-3 font-medium text-white" data-lookbook-category>{{ __('Xem thêm tại :name', ['name' => $lookbook['category']['name']]) }}</a></p>
    @endif
@endsection
