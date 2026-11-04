<script setup lang="ts">
import DataTable from '@admin/Components/DataTable.vue';
import PageHeader from '@admin/Components/PageHeader.vue';
import StatusBadge from '@admin/Components/StatusBadge.vue';
import { primaryButton } from '@admin/styles';
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import CatalogTabs from '../../Components/CatalogTabs.vue';
import type { CatalogLabels, NavItem } from '../../types';

const props = defineProps<{
    nav: NavItem[];
    labels: CatalogLabels;
    collections: Array<{ id: number; slug: string; name: string | null; status: string; styles_count: number }>;
    canManage: boolean;
}>();
const baseUrl = computed(() => props.nav.find((item) => item.key === 'collections')?.url ?? '');
const columns = [
    { key: 'name', label: 'Tên', class: 'font-medium' },
    { key: 'slug', label: 'Slug', class: 'text-slate-500' },
    { key: 'styles_count', label: 'Sản phẩm' },
    { key: 'status', label: 'Trạng thái' },
    { key: 'actions', label: '', align: 'right' as const },
];
</script>

<template>
    <Head title="Bộ sưu tập" />
    <CatalogTabs :nav="nav" active="collections" />
    <PageHeader title="Bộ sưu tập" subtitle="Tập hợp sản phẩm cho landing page, campaign.">
        <Link v-if="canManage" :href="`${baseUrl}/create`" :class="primaryButton">Thêm bộ sưu tập</Link>
    </PageHeader>
    <DataTable :columns="columns" :rows="collections" :row-label="(collection) => collection.slug" empty-text="Chưa có bộ sưu tập nào.">
        <template #cell-slug="{ row }">/{{ row.slug }}</template>
        <template #cell-status="{ row }"><StatusBadge :label="labels.visibility[row.status] ?? row.status" :tone="row.status === 'active' ? 'success' : 'neutral'" /></template>
        <template #cell-actions="{ row }">
            <Link v-if="canManage" :href="`${baseUrl}/${row.id}/edit`" class="text-indigo-600 hover:underline">Sửa</Link>
        </template>
    </DataTable>
</template>
