{{-- CategorySection — tiêu đề nhóm căn giữa "— Tên —" + SubcategoryGrid. --}}
<template data-cnav-template="section">
    <section class="mb-5">
        <h3 class="mb-4 flex items-center justify-center gap-2.5 text-xs text-gray-500">
            <span class="h-px w-5 bg-gray-300" aria-hidden="true"></span>
            <a class="max-w-[70%] truncate" data-cnav-section-title></a>
            <span class="h-px w-5 bg-gray-300" aria-hidden="true"></span>
        </h3>
        @include('theme::partials.mobile-category-nav.subcategory-grid')
    </section>
</template>
