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
    <h1 class="mb-2 text-2xl font-semibold">{{ $category['name'] }}</h1>
    @if ($category['description'])
        <p class="mb-6 text-slate-600">{{ $category['description'] }}</p>
    @endif
    @include('theme::partials.listing', ['listingContext' => ['type' => 'category', 'category' => $category, 'brand' => null]])
@endsection
