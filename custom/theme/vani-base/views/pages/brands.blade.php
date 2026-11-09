@extends('theme::layouts.app')

@section('title', __('Thương hiệu'))

@section('content')
    <h1 class="mb-6 text-2xl font-semibold">{{ __('Thương hiệu') }}</h1>
    {{-- Nhóm theo chữ cái đầu (§3.5-3): thanh chữ cái nhảy tới nhóm, không cần JS. --}}
    <nav aria-label="{{ __('Theo chữ cái') }}" class="mb-6 flex flex-wrap gap-1 text-sm" data-brand-letters>
        @foreach (array_keys($brandGroups) as $letter)
            <a href="#brands-{{ $loop->index }}" class="rounded border border-slate-200 px-2 py-1 font-medium">{{ $letter }}</a>
        @endforeach
    </nav>
    @foreach ($brandGroups as $letter => $group)
        <section id="brands-{{ $loop->index }}" class="mb-8" data-brand-letter="{{ $letter }}" data-vani-motion="rise">
            <h2 class="mb-3 text-lg font-semibold">{{ $letter }}</h2>
            <ul class="grid grid-cols-2 gap-4 md:grid-cols-4" data-vani-motion-stagger>
                @foreach ($group as $brand)
                    <li data-vani-motion-item>
                        <a href="{{ vani_route('storefront.brand', $brand['slug']) }}" class="block rounded-[var(--radius-theme)] border border-slate-200 p-4 text-center" data-vani-motion-hover>
                            @if ($brand['logo_url'])
                                <img src="{{ $brand['logo_url'] }}" alt="" class="mx-auto mb-2 max-h-12" loading="lazy">
                            @endif
                            {{ $brand['name'] }}
                        </a>
                    </li>
                @endforeach
            </ul>
        </section>
    @endforeach
    <x-vani::hook-slot name="vani.storefront.brands.after_list" :args="[$brandGroups]" />
@endsection
