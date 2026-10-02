@extends('theme::layouts.app')

@section('content')
    @if ($blocks !== null)
        @foreach ($blocks as $block)
            {{ $block['html'] }}
        @endforeach
    @else
    @if ($categories !== [])
        <nav aria-label="Danh mục" class="mb-8 flex flex-wrap gap-3 text-sm">
            @foreach ($categories as $category)
                <a href="{{ route('storefront.category', $category['slug']) }}" class="rounded-full border border-slate-300 px-4 py-1.5">{{ $category['name'] }}</a>
            @endforeach
        </nav>
    @endif

    @if ($brands !== [])
        <section class="mb-10">
            <h2 class="mb-4 text-lg font-semibold">Thương hiệu</h2>
            <div class="flex flex-wrap gap-4">
                @foreach ($brands as $brand)
                    <a href="{{ route('storefront.brand', $brand['slug']) }}" class="flex h-16 min-w-32 items-center justify-center rounded-[var(--radius-theme)] border border-slate-200 px-4">
                        @if ($brand['logo_url'])
                            <img src="{{ $brand['logo_url'] }}" alt="{{ $brand['name'] }}" class="max-h-10" loading="lazy">
                        @else
                            {{ $brand['name'] }}
                        @endif
                    </a>
                @endforeach
            </div>
        </section>
    @endif

    <section>
        <h2 class="mb-4 text-lg font-semibold">Sản phẩm mới</h2>
        <div class="grid grid-cols-2 gap-6 md:grid-cols-4">
            @foreach ($newest as $product)
                @include('theme::partials.product-card', ['product' => $product])
            @endforeach
        </div>
    </section>
    @endif
@endsection
