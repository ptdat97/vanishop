{{-- CategorySidebarItem — mục đang chọn: aria-selected="true" (màu nhấn, chữ lớn hơn, vạch bên trái); huy hiệu Mới/Sale của menu. --}}
<template data-cnav-template="tab">
    <button type="button" role="tab" aria-selected="false" tabindex="-1"
        class="relative flex min-h-12 w-full items-center justify-center px-1.5 py-2 text-center text-sm leading-tight text-gray-700 [-webkit-tap-highlight-color:transparent] before:absolute before:inset-y-3 before:left-0 before:w-0.5 before:bg-accent before:opacity-0 focus-visible:outline-2 focus-visible:-outline-offset-2 focus-visible:outline-primary aria-selected:text-base aria-selected:font-medium aria-selected:text-accent aria-selected:before:opacity-100">
        <span class="line-clamp-2 break-words" data-cnav-name></span>
        <span class="absolute right-1 top-1.5 rounded-sm bg-emerald-600 px-0.5 text-[8px] font-semibold uppercase leading-3 text-white" data-cnav-badge="new" hidden>{{ __('Mới') }}</span>
        <span class="absolute right-1 top-1.5 rounded-sm bg-red-600 px-0.5 text-[8px] font-semibold uppercase leading-3 text-white" data-cnav-badge="sale" hidden>Sale</span>
    </button>
</template>
