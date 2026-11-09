{{--
    Modal dùng chung cho storefront. Mở bằng nút/link có data-vani-modal-open="<id>".
    Truyền id, title, contentView; contentData, dialogClass và dataAttributes là tuỳ chọn.
--}}
<dialog id="{{ $id }}" class="vani-modal {{ $dialogClass ?? '' }}" aria-labelledby="{{ $id }}-title"
    @foreach ($dataAttributes ?? [] as $attribute => $value)
        @if ($value !== false)
            {{ $attribute }}@if ($value !== true)="{{ $value }}"@endif
        @endif
    @endforeach
>
    <div class="flex max-h-[85dvh] flex-col">
        <div class="flex items-center justify-between border-b border-slate-200 px-4 py-3">
            <h2 id="{{ $id }}-title" class="text-lg font-semibold">{{ $title }}</h2>
            <button type="button" class="cursor-pointer px-2 text-2xl leading-none text-slate-500 hover:text-slate-900"
                data-vani-modal-close
                aria-label="{{ __('Đóng') }}">×</button>
        </div>
        @include($contentView, $contentData ?? [])
    </div>
</dialog>
