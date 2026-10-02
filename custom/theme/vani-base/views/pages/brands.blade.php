@extends('theme::layouts.app')

@section('title', 'Thương hiệu')

@section('content')
    <h1 class="mb-6 text-2xl font-semibold">Thương hiệu</h1>
    <ul class="grid grid-cols-2 gap-4 md:grid-cols-4">
        @foreach ($brands as $brand)
            <li>
                <a href="{{ route('storefront.brand', $brand['slug']) }}" class="block rounded-[var(--radius-theme)] border border-slate-200 p-4 text-center">
                    @if ($brand['logo_url'])
                        <img src="{{ $brand['logo_url'] }}" alt="" class="mx-auto mb-2 max-h-12" loading="lazy">
                    @endif
                    {{ $brand['name'] }}
                </a>
            </li>
        @endforeach
    </ul>
@endsection
