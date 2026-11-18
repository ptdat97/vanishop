@extends('theme::layouts.app')

{{-- $meta (PageMeta): tiêu đề/mô tả/ảnh chia sẻ đã có dự phòng. --}}
@section('title', $meta['title'])
@if ($meta['description'] !== null)
    @section('description', $meta['description'])
@endif
@if ($meta['image'] !== null)
    @section('og_image', $meta['image'])
@endif

@section('content')
    @include('theme::partials.breadcrumb', ['crumbs' => $crumbs])
    <header class="mb-6 flex items-center gap-4">
        @if ($brand['logo_url'])
            <img src="{{ $brand['logo_url'] }}" alt="{{ $brand['name'] }}" class="h-14" data-vani-motion-hover>
        @endif
        <div>
            <h1 class="text-2xl font-semibold">{{ $brand['name'] }}</h1>
            @if ($brand['description'])
                <p class="text-slate-600">{{ $brand['description'] }}</p>
            @endif
        </div>
    </header>
    @include('theme::partials.listing', ['listingContext' => ['type' => 'brand', 'category' => null, 'brand' => $brand]])
@endsection
