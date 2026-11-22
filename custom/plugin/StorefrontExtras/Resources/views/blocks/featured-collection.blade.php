@if ($url !== null && $products !== [])
    <section @class(['mb-10 grid gap-6 lg:gap-10', 'md:grid-cols-2 md:items-start' => $image !== null]) data-block="extras_featured_collection">
        @if ($image !== null)
            <div class="aspect-[4/5] overflow-hidden bg-subtle">
                @include('theme::partials.image', [
                    'src' => $image['url'], 'srcset' => $image['srcset'], 'alt' => $alt,
                    'layout' => [1152, 2, 1], 'width' => $image['width'], 'height' => $image['height'],
                    'class' => 'h-full w-full object-cover',
                ])
            </div>
        @endif
        <div class="min-w-0">
            <h2 class="vani-editorial-title text-primary">{{ $title }}</h2>
            @if ($description !== '')
                <p class="mt-3 max-w-prose text-slate-600">{{ $description }}</p>
            @endif
            <a href="{{ $url }}" class="mt-3 inline-flex min-h-10 items-center text-sm font-medium underline underline-offset-4">{{ __('vani-storefront-extras::storefront.view_collection') }}</a>
            <div class="mt-4">
                @include('theme::partials.product-grid', [
                    'products' => $products, 'gridClass' => 'grid grid-cols-2 gap-x-2.5 gap-y-6 md:gap-x-6',
                    'layout' => $image === null ? [1152, 2, 2] : [576, 2, 2],
                ])
            </div>
        </div>
    </section>
@endif
