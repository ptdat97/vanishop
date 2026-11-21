@extends('theme::layouts.app')

@section('content')
    <x-vani::hook-slot name="vani.storefront.home.before_blocks" />
    @if ($blocks !== null)
        @foreach ($blocks as $block)
            {{ $block['html'] }}
        @endforeach
    @else
        <section class="mb-10">
            <h2 class="mb-5 text-3xl font-medium tracking-tight">{{ __('theme::catalog.new_arrivals') }}</h2>
            @include('theme::partials.product-grid', ['products' => $newest, 'stagger' => true, 'eager' => 2])
        </section>

        @if ($categories !== [])
            <nav aria-label="{{ __('theme::common.categories') }}" class="mb-8 flex flex-wrap gap-3 text-sm">
                @foreach ($categories as $category)
                    <a href="{{ vani_route('storefront.category', $category['slug']) }}" class="border-b border-border px-2 py-2 hover:border-primary">{{ $category['name'] }}</a>
                @endforeach
            </nav>
        @endif

        @if ($brands !== [])
            <section class="mb-10">
                <h2 class="mb-5 text-2xl font-medium tracking-tight">{{ __('theme::common.brands') }}</h2>
                @include('theme::partials.brand-grid', ['brands' => $brands])
            </section>
        @endif
    @endif
    <x-vani::hook-slot name="vani.storefront.home.after_blocks" />
@endsection
