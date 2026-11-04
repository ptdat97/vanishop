{{--
    Menu đã xuất bản (MenuPresenter): $items = list<{label, url|null, badge, image_url, children}>, $level (1 = cấp trên cùng).
    Desktop: cấp 1 nằm ngang, cấp 2–3 mở khi rê chuột hoặc focus (dùng được bằng bàn phím, không cần JS).
--}}
@php($level ??= 1)
<ul @class(['flex flex-wrap items-center gap-4' => $level === 1, 'absolute left-0 top-full z-20 hidden min-w-48 rounded-[var(--radius-theme)] border border-slate-200 bg-white p-2 shadow-lg group-hover:block group-focus-within:block' => $level === 2, 'pl-3' => $level > 2])>
    @foreach ($items as $item)
        <li @class(['group relative' => $level === 1 && $item['children'] !== []])>
            @if ($item['url'] !== null)
                <a href="{{ $item['url'] }}" @class(['inline-flex items-center gap-1', 'block rounded px-3 py-1.5 hover:bg-slate-50' => $level > 1])>
            @else
                <span @class(['inline-flex items-center gap-1 font-medium', 'block px-3 py-1.5 text-xs uppercase text-slate-500' => $level > 1])>
            @endif
                @if ($level === 1 && $item['image_url'])<img src="{{ $item['image_url'] }}" alt="" class="h-6 w-6 rounded object-cover">@endif
                {{ $item['label'] }}
                @if ($item['badge'])
                    <span @class(['rounded-full px-1.5 text-[10px] font-semibold uppercase text-white', 'bg-emerald-600' => $item['badge'] === 'new', 'bg-red-600' => $item['badge'] === 'sale'])>{{ $item['badge'] === 'new' ? __('Mới') : 'Sale' }}</span>
                @endif
            @if ($item['url'] !== null)</a>@else</span>@endif
            @if ($item['children'] !== [])
                @include('theme::partials.menu', ['items' => $item['children'], 'level' => $level + 1])
            @endif
        </li>
    @endforeach
</ul>
