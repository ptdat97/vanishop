<script setup lang="ts">
import DataTable from '@admin/Components/DataTable.vue';
import PageHeader from '@admin/Components/PageHeader.vue';
import { primaryButton } from '@admin/styles';
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import CatalogTabs from '../../Components/CatalogTabs.vue';
import type { CatalogLabels, NavItem } from '../../types';

const props = defineProps<{
    nav: NavItem[];
    labels: CatalogLabels;
    attributes: Array<{ id: number; code: string; name: string | null; kind: string; input_type: string; is_filterable: boolean; values_count: number }>;
    canManage: boolean;
}>();
const baseUrl = computed(() => props.nav.find((item) => item.key === 'attributes')?.url ?? '');
const columns = [
    { key: 'name', label: 'Tên', class: 'font-medium' },
    { key: 'code', label: 'Mã', class: 'font-mono text-xs' },
    { key: 'kind', label: 'Loại' },
    { key: 'input_type', label: 'Kiểu nhập' },
    { key: 'is_filterable', label: 'Lọc' },
    { key: 'values_count', label: 'Giá trị' },
    { key: 'actions', label: '', align: 'right' as const },
];
</script>

<template>
    <Head title="Thuộc tính" />
    <CatalogTabs :nav="nav" active="attributes" />
    <PageHeader title="Thuộc tính" subtitle="Chất liệu, form dáng, mùa… Loại internal không hiển thị ra storefront.">
        <Link v-if="canManage" :href="`${baseUrl}/create`" :class="primaryButton">Thêm thuộc tính</Link>
    </PageHeader>
    <DataTable :columns="columns" :rows="attributes" :row-label="(attribute) => attribute.code" empty-text="Chưa có thuộc tính nào.">
        <template #cell-kind="{ row }">{{ labels.attribute_kind[row.kind] ?? row.kind }}</template>
        <template #cell-is_filterable="{ row }">{{ row.is_filterable ? 'Có' : '—' }}</template>
        <template #cell-values_count="{ row }">{{ row.values_count || '—' }}</template>
        <template #cell-actions="{ row }">
            <Link v-if="canManage" :href="`${baseUrl}/${row.id}/edit`" class="text-indigo-600 hover:underline">Sửa</Link>
        </template>
    </DataTable>
</template>
