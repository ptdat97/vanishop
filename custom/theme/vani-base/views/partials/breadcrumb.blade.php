{{--
    Breadcrumb + JSON-LD BreadcrumbList (§3.16-6). $crumbs = list<{label, url?: string|null, link?: LinkTarget|{type, value}}>;
    mục cuối là trang hiện tại (không link). Trang chủ tự thêm ở đầu. Link không còn tồn tại → hiện chữ, không link.
--}}
@php
    $trail = [['label' => __('Trang chủ'), 'url' => vani_route('storefront.home')]];
    foreach ($crumbs as $crumb) {
        $trail[] = ['label' => (string) $crumb['label'], 'url' => $crumb['url'] ?? (isset($crumb['link']) ? vani_link($crumb['link']) : null)];
    }
    $trail[array_key_last($trail)]['url'] = null;
@endphp
<nav aria-label="Breadcrumb" class="mb-4 text-sm text-slate-500" data-vani-breadcrumb>
    <ol class="flex flex-wrap gap-1">
        @foreach ($trail as $crumb)
            <li @if ($loop->last) aria-current="page" class="text-slate-700" @endif>
                @unless ($loop->first)<span aria-hidden="true">/</span>@endunless
                @if ($crumb['url'])<a href="{{ $crumb['url'] }}">{{ $crumb['label'] }}</a>@else{{ $crumb['label'] }}@endif
            </li>
        @endforeach
    </ol>
</nav>
<script type="application/ld+json">{!! \Modules\Storefront\View\StructuredData::breadcrumbs($trail) !!}</script>
