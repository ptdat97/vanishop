<script setup lang="ts">
import DataTable from '@admin/Components/DataTable.vue';
import FlashMessage from '@admin/Components/FlashMessage.vue';
import PageHeader from '@admin/Components/PageHeader.vue';
import { primaryButton, secondaryButton } from '@admin/styles';
import { Head, Link } from '@inertiajs/vue3';

interface Banner {
    id: number;
    category: string;
    title: string | null;
    active: boolean;
    thumb_url: string | null;
}

/** Banner đầu trang danh mục (vani.cms 1.5.0): mỗi danh mục một banner, hiện trước bộ lọc. */
defineProps<{ banners: Banner[]; urls: { index: string; create: string; pages: string }; can: { manage: boolean } }>();

const columns = [
    { key: 'thumb', label: '' },
    { key: 'category', label: 'Danh mục' },
    { key: 'title', label: 'Tiêu đề' },
    { key: 'active', label: 'Trạng thái' },
];
</script>

<template>
    <Head title="Banner danh mục" />
    <PageHeader title="Banner danh mục" subtitle="Ảnh và lời giới thiệu ở đầu trang danh mục, trước bộ lọc.">
        <Link :href="urls.pages" :class="secondaryButton">Nội dung</Link>
        <Link v-if="can.manage" :href="urls.create" :class="primaryButton">Thêm banner</Link>
    </PageHeader>
    <FlashMessage />

    <DataTable :columns="columns" :rows="banners" :row-label="(banner) => banner.category" empty-text="Chưa có banner nào.">
        <template #cell-thumb="{ row }">
            <img v-if="row.thumb_url" :src="row.thumb_url" alt="" class="h-10 w-16 rounded object-cover" />
        </template>
        <template #cell-category="{ row }">
            <Link :href="`${urls.index}/${row.id}`" class="font-medium text-red-700 hover:underline">{{ row.category }}</Link>
        </template>
        <template #cell-title="{ row }">{{ row.title ?? '—' }}</template>
        <template #cell-active="{ row }">{{ row.active ? 'Đang bật' : 'Tắt' }}</template>
    </DataTable>
</template>
