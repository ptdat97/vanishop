@extends('theme::layouts.app')

@section('title', $lookbook['meta_title'])
@if ($lookbook['meta_description'])
    @section('description', $lookbook['meta_description'])
@endif

{{-- Thẻ chia sẻ do layout của theme in (og:title = tiêu đề trang, og:url = canonical). --}}
@if ($lookbook['cover_url'])
    @section('og_image', $lookbook['cover_url'])
@endif

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
            <figure class="mb-4 break-inside-avoid">
                <div class="relative">
                    <a href="{{ $image['full_url'] }}" target="_blank" rel="noopener" class="block">
                        @include('theme::partials.image', [
                            'src' => $image['url'], 'srcset' => $image['srcset'], 'alt' => $image['alt'], 'layout' => [1152, 3, 1],
                            'width' => $image['width'], 'height' => $image['height'], 'loading' => $loop->first ? 'eager' : 'lazy', 'class' => 'w-full rounded-[var(--radius-theme)]',
                        ])
                    </a>
                    @if ($image['products'] !== [])
                        <div class="absolute inset-x-2 bottom-2 flex flex-wrap gap-1" data-lookbook-product-markers>
                            @foreach ($image['products'] as $product)
                                <a href="{{ $product['url'] }}" aria-label="{{ __('vani-lookbook::storefront.view_product', ['name' => $product['name']]) }}" class="grid size-9 place-items-center rounded-full bg-white text-sm font-medium text-slate-900 shadow focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary">{{ $loop->iteration }}</a>
                            @endforeach
                        </div>
                    @endif
                </div>
                @if ($image['products'] !== [])
                    <figcaption class="mt-2 px-1">
                        <ol class="list-inside list-decimal text-sm" data-lookbook-products>
                            @foreach ($image['products'] as $product)
                                <li><a href="{{ $product['url'] }}" class="inline-block py-1 underline underline-offset-2 focus-visible:outline-2 focus-visible:outline-primary">{{ $product['name'] }}</a></li>
                            @endforeach
                        </ol>
                    </figcaption>
                @endif
            </figure>
        @endforeach
    </div>

    @if ($lookbook['category'])
        <p class="mt-8"><a href="{{ $lookbook['category']['url'] }}" class="rounded-[var(--radius-theme)] bg-primary px-6 py-3 font-medium text-white" data-lookbook-category>{{ __('vani-lookbook::storefront.shop_look', ['name' => $lookbook['category']['name']]) }}</a></p>
    @endif
@endsection
