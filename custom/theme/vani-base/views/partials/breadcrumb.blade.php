{{--
    Breadcrumb + JSON-LD BreadcrumbList (§3.16-6). Trang truyền $crumbs = list<{label, url?: string|null, link?: LinkTarget|{type, value}}>;
    Core dựng sẵn $breadcrumb (Storefront\View\Breadcrumbs: thêm Trang chủ, phân giải link, mục cuối không link) — partial chỉ hiển thị.
--}}
<nav aria-label="Breadcrumb" class="mb-4 text-sm text-slate-500" data-vani-breadcrumb>
    <ol class="flex flex-wrap gap-1">
        @foreach ($breadcrumb['items'] as $crumb)
            <li @if ($crumb['current']) aria-current="page" class="text-slate-700" @endif>
                @unless ($loop->first)<span aria-hidden="true">/</span>@endunless
                @if ($crumb['url'])<a href="{{ $crumb['url'] }}">{{ $crumb['label'] }}</a>@else{{ $crumb['label'] }}@endif
            </li>
        @endforeach
    </ol>
</nav>
<script type="application/ld+json">{!! $breadcrumb['json_ld'] !!}</script>
