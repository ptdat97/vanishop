<script setup lang="ts">
import MediaManager from '@admin/Components/Media/MediaManager.vue';
import PageHeader from '@admin/Components/PageHeader.vue';
import { Head } from '@inertiajs/vue3';

defineProps<{ libraryUrl: string; canManage: boolean }>();
</script>

<template>
    <Head title="Thư viện ảnh" />
    <PageHeader
        title="Thư viện ảnh"
        subtitle="Ảnh dùng chung cho sản phẩm, danh mục và nội dung. Ảnh trùng nội dung chỉ lưu một lần; ảnh đang dùng không xoá được."
    />
    <div class="h-[calc(100vh-12rem)] min-h-[32rem]">
        <MediaManager :library-url="libraryUrl" />
    </div>
</template>
