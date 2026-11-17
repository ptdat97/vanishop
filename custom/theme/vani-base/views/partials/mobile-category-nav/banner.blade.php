{{-- CategoryBanner — ảnh ngang 5:2, bo góc nhẹ, slogan ở giữa (tuỳ chọn). Danh mục không có banner → không dựng. --}}
<template data-cnav-template="banner">
    <a class="relative mb-4 block aspect-[5/2] overflow-hidden rounded bg-gray-100" data-cnav-banner>
        <img alt="" decoding="async" class="size-full object-cover" data-cnav-image>
        <span class="absolute inset-0 flex flex-col items-center justify-center bg-black/10 px-4 text-center text-white [text-shadow:0_1px_2px_rgb(0_0_0/0.35)]" data-cnav-banner-text hidden>
            <span class="text-sm font-medium tracking-wide" data-cnav-banner-title></span>
            <span class="mt-0.5 text-[11px] opacity-90" data-cnav-banner-subtitle></span>
        </span>
    </a>
</template>
