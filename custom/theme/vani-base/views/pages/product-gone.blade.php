@extends('theme::layouts.app')

{{-- Sản phẩm đã ngừng bán (HTTP 410, L44): $name, $categoryUrl (null nếu không còn danh mục), $suggestedProducts. --}}
@section('title', __('theme::product.product_no_longer_available'))

@section('content')
    <div class="mb-8 rounded-[var(--radius-theme)] border border-slate-200 p-6 text-center" data-product-gone>
        <h1 class="text-2xl font-semibold">{{ __('theme::product.product_no_longer_available') }}</h1>
        <p class="mt-2 text-slate-600">{{ __('theme::product.no_longer_sold', ['name' => $name]) }}</p>
        <a href="{{ $categoryUrl ?? vani_route('storefront.search') }}" class="mt-4 inline-block rounded-[var(--radius-theme)] bg-primary px-6 py-3 font-medium text-white">
            {{ $categoryUrl ? __('theme::product.browse_category') : __('theme::product.browse_all_products') }}
        </a>
    </div>
    @if ($suggestedProducts !== [])
        <h2 class="mb-4 text-lg font-semibold">{{ __('theme::product.may_also_like') }}</h2>
        @include('theme::partials.product-grid', ['products' => $suggestedProducts, 'stagger' => true])
    @endif
@endsection
