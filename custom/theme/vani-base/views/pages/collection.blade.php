@extends('theme::layouts.app')

@section('title', $meta['title'])
@if ($meta['description'] !== null)
    @section('description', $meta['description'])
@endif
@if ($meta['image'] !== null)
    @section('og_image', $meta['image'])
@endif

@section('content')
    @include('theme::partials.breadcrumb', ['crumbs' => $crumbs])
    @forelse ($zones['banner'] as $block)
        {{ $block['html'] }}
    @empty
        @include('theme::partials.collection-banner', ['banner' => []])
    @endforelse

    @foreach ($zones['before'] as $block)
        {{ $block['html'] }}
    @endforeach

    <div id="collection-products" class="scroll-mt-24">
        @include('theme::partials.listing', ['listingContext' => ['type' => 'collection', 'collection' => $collection, 'category' => null, 'brand' => null]])
    </div>

    @if ($zones['after'] !== [])
        <div class="mt-10 border-t border-border pt-8">
            @foreach ($zones['after'] as $block)
                {{ $block['html'] }}
            @endforeach
        </div>
    @endif
@endsection
