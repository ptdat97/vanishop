@extends('theme::layouts.app')

{{-- $meta (PageMeta): tiêu đề/mô tả/ảnh chia sẻ đã có dự phòng. --}}
@section('title', $meta['title'])
@if ($meta['description'] !== null)
    @section('description', $meta['description'])
@endif
@if ($meta['image'] !== null)
    @section('og_image', $meta['image'])
@endif

{{-- Đầu trang: block admin đặt ở "Đầu trang" (Builder, chung hoặc riêng của category) thay phần đầu mặc định. --}}
@php($listingContext = ['type' => 'category', 'category' => $category, 'brand' => null, 'collection' => null])
@section('content')
    @include('theme::partials.breadcrumb', ['crumbs' => $crumbs])
    <x-vani::hook-slot name="vani.storefront.plp.header" :args="[$listingContext]">
        @include('theme::partials.listing-banner', ['type' => 'category', 'page' => $category])
    </x-vani::hook-slot>
    <div id="listing-products" class="scroll-mt-24">
        @include('theme::partials.listing', ['listingContext' => $listingContext])
    </div>
@endsection
