@extends('theme::layouts.app')

{{-- Sản phẩm đã ngừng bán (HTTP 410, L44): $name, $categoryUrl (null nếu không còn danh mục), $suggestedProducts. --}}
@section('title', __('Sản phẩm đã ngừng bán'))

@section('content')
    <div class="mb-8 rounded-[var(--radius-theme)] border border-slate-200 p-6 text-center" data-product-gone>
        <h1 class="text-2xl font-semibold">{{ __('Sản phẩm đã ngừng bán') }}</h1>
        <p class="mt-2 text-slate-600">{{ __(':name không còn bán nữa. Bạn có thể xem các sản phẩm tương tự bên dưới.', ['name' => $name]) }}</p>
        <a href="{{ $categoryUrl ?? vani_route('storefront.search') }}" class="mt-4 inline-block rounded-[var(--radius-theme)] bg-primary px-6 py-3 font-medium text-white">
            {{ $categoryUrl ? __('Xem cùng danh mục') : __('Xem tất cả sản phẩm') }}
        </a>
    </div>
    @if ($suggestedProducts !== [])
        <h2 class="mb-4 text-lg font-semibold">{{ __('Có thể bạn thích') }}</h2>
        @include('theme::partials.product-grid', ['products' => $suggestedProducts, 'stagger' => true])
    @endif
@endsection
