@if ($lookbooks !== [])
    <section class="mb-10" data-block="lookbook_latest">
        <div class="mb-4 flex items-baseline justify-between">
            <h2 class="text-lg font-semibold">{{ $title !== '' ? $title : __('Lookbook') }}</h2>
            <a href="{{ vani_route('storefront.p.vani-lookbook.index') }}" class="text-sm underline">{{ __('Xem tất cả') }}</a>
        </div>
        <div class="grid grid-cols-2 gap-6 md:grid-cols-3">
            @foreach ($lookbooks as $lookbook)
                @include('vani-lookbook::_card', ['lookbook' => $lookbook])
            @endforeach
        </div>
    </section>
@endif
