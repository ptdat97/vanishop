{{-- SubcategoryItem — ảnh vuông + tên (12px); dẫn tới trang danh sách sản phẩm. Thiếu ảnh → chữ cái đầu. --}}
<template data-cnav-template="item">
    <li class="min-w-0">
        <a class="flex flex-col items-center gap-2 text-center [-webkit-tap-highlight-color:transparent] active:opacity-70" data-cnav-link>
            <span class="relative block aspect-square w-[72%] overflow-hidden rounded bg-gray-50" data-cnav-thumb>
                <img alt="" loading="lazy" decoding="async" class="size-full object-cover" data-cnav-image>
                <span class="absolute inset-0 flex items-center justify-center text-base font-medium uppercase text-gray-400" data-cnav-initial hidden aria-hidden="true"></span>
                <span class="absolute left-0 top-0 rounded-br bg-emerald-600 px-1 text-[9px] font-semibold uppercase leading-4 text-white" data-cnav-badge="new" hidden>{{ __('theme::catalog.new') }}</span>
                <span class="absolute left-0 top-0 rounded-br bg-red-600 px-1 text-[9px] font-semibold uppercase leading-4 text-white" data-cnav-badge="sale" hidden>Sale</span>
            </span>
            <span class="line-clamp-2 w-full break-words text-xs leading-snug text-gray-700" data-cnav-name></span>
        </a>
    </li>
</template>
