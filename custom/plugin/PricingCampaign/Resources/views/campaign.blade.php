{{-- Một chiến dịch (trang Ưu đãi, block Builder): banner, nhãn, đếm ngược, nội dung, lưới sản phẩm của theme. --}}
<section class="mb-12 space-y-4" data-vani-campaign="{{ $campaign['id'] }}">
    @if ($campaign['banner_url'])
        <img src="{{ $campaign['banner_url'] }}" alt="" class="w-full rounded-[var(--radius-theme)] object-cover" loading="lazy">
    @endif
    <div class="flex flex-wrap items-baseline justify-between gap-2">
        <h2 class="text-2xl font-medium tracking-tight" @if ($campaign['accent_color']) style="color: {{ $campaign['accent_color'] }}" @endif>{{ $title ?? $campaign['label'] }}</h2>
        @if ($campaign['show_countdown'])
            <p class="text-sm">
                {{ $campaign['running'] ? __('vani-pricing-campaign::texts.storefront.ends_in') : __('vani-pricing-campaign::texts.storefront.starts_in') }}
                @include('vani-pricing-campaign::countdown', ['at' => $campaign['running'] ? $campaign['ends_at'] : $campaign['starts_at']])
            </p>
        @endif
    </div>
    @if ($campaign['content'] !== '')
        <div class="prose prose-sm max-w-none text-slate-600">{!! \Illuminate\Support\Str::markdown($campaign['content'], ['html_input' => 'strip', 'allow_unsafe_links' => false, 'max_nesting_level' => 20]) !!}</div>
    @endif
    @if ($campaign['products'] !== [])
        @include('theme::partials.product-grid', ['products' => $campaign['products']])
    @endif
</section>
