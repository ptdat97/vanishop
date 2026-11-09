{{--
    Drawer dùng chung cho storefront. Mở bằng nút/link có data-vani-drawer-open="<id>".
    Truyền id, title, contentView; contentData, dialogClass, dataAttributes và closeAttributes là tuỳ chọn.
--}}
<dialog id="{{ $id }}" class="vani-drawer {{ $dialogClass ?? '' }}" aria-labelledby="{{ $id }}-title"
    @foreach ($dataAttributes ?? [] as $attribute => $value)
        @if ($value !== false)
            {{ $attribute }}@if ($value !== true)="{{ $value }}"@endif
        @endif
    @endforeach
>
    <div class="flex h-full flex-col">
        <div class="flex items-center justify-between border-b border-slate-200 px-4 py-3">
            <h2 id="{{ $id }}-title" class="text-lg font-semibold">{{ $title }}</h2>
            <button type="button" class="px-2 text-2xl leading-none text-slate-500 hover:text-slate-900"
                data-vani-drawer-close
                @foreach ($closeAttributes ?? [] as $attribute => $value)
                    @if ($value !== false)
                        {{ $attribute }}@if ($value !== true)="{{ $value }}"@endif
                    @endif
                @endforeach
                aria-label="{{ __('Đóng') }}">×</button>
        </div>
        @include($contentView, $contentData ?? [])
    </div>
</dialog>
