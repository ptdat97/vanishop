<script setup lang="ts">
import { t } from '@admin/i18n';
import DataTable from '@admin/Components/DataTable.vue';
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
    { key: 'category', label: t('vani-cms::admin.banners_index.category') },
    { key: 'title', label: t('vani-cms::admin.banners_index.title') },
    { key: 'active', label: t('vani-cms::admin.banners_index.status') },
];
</script>

<template>
    <Head :title="t('vani-cms::admin.banners_index.category_banners')" />
    <PageHeader :title="t('vani-cms::admin.banners_index.category_banners')" :subtitle="t('vani-cms::admin.banners_index.image_intro_text_top_category')">
        <Link :href="urls.pages" :class="secondaryButton">{{ t('vani-cms::admin.banners_index.content') }}</Link>
        <Link v-if="can.manage" :href="urls.create" :class="primaryButton">{{ t('vani-cms::admin.banners_index.add_banner') }}</Link>
    </PageHeader>

    <DataTable :columns="columns" :rows="banners" :row-label="(banner) => banner.category" :empty-text="t('vani-cms::admin.banners_index.no_banners_yet')">
        <template #cell-thumb="{ row }">
            <img v-if="row.thumb_url" :src="row.thumb_url" alt="" class="h-10 w-16 rounded object-cover" />
        </template>
        <template #cell-category="{ row }">
            <Link :href="`${urls.index}/${row.id}`" class="font-medium text-red-700 hover:underline">{{ row.category }}</Link>
        </template>
        <template #cell-title="{ row }">{{ row.title ?? '—' }}</template>
        <template #cell-active="{ row }">{{ row.active ? t('vani-cms::admin.banners_index.active') : t('vani-cms::admin.banners_index.off') }}</template>
    </DataTable>
</template>
