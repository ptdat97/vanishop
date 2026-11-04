{{-- Menu dạng danh sách dọc (chân trang, di động): mục không có link là tiêu đề nhóm; cấp con thụt vào. --}}
<ul class="space-y-1">
    @foreach ($items as $item)
        <li>
            @if ($item['url'] !== null)
                <a href="{{ $item['url'] }}" class="block py-0.5">{{ $item['label'] }}@if ($item['badge']) <span class="text-xs font-semibold uppercase text-red-600">{{ $item['badge'] === 'new' ? __('Mới') : 'Sale' }}</span>@endif</a>
            @else
                <span class="block py-0.5 font-medium text-slate-700">{{ $item['label'] }}</span>
            @endif
            @if ($item['children'] !== [])
                <div class="pl-3">@include('theme::partials.menu-list', ['items' => $item['children']])</div>
            @endif
        </li>
    @endforeach
</ul>
