<script setup lang="ts">
import DataTable from '@admin/Components/DataTable.vue';
import FlashMessage from '@admin/Components/FlashMessage.vue';
import PageHeader from '@admin/Components/PageHeader.vue';
import StatusBadge from '@admin/Components/StatusBadge.vue';
import { primaryButton } from '@admin/styles';
import { Head, Link } from '@inertiajs/vue3';

defineProps<{
    charts: Array<{ id: number; name: string; unit: string; is_default: boolean; active: boolean; assignments: Record<string, number> }>;
    can: { manage: boolean };
    urls: { create: string; base: string };
}>();

const targetLabels: Record<string, string> = { product: 'sản phẩm', category: 'danh mục', brand: 'thương hiệu' };
const columns = [
    { key: 'name', label: 'Tên' },
    { key: 'unit', label: 'Đơn vị' },
    { key: 'assignments', label: 'Áp dụng cho' },
    { key: 'active', label: 'Trạng thái' },
];
</script>

<template>
    <Head title="Bảng size" />
    <PageHeader title="Bảng size" subtitle="Gán bảng ở form sản phẩm, danh mục hoặc thương hiệu. Sản phẩm chưa có bảng nào dùng bảng mặc định.">
        <Link v-if="can.manage" :href="urls.create" :class="primaryButton">Thêm bảng size</Link>
    </PageHeader>
    <FlashMessage />

    <DataTable :columns="columns" :rows="charts" :row-label="(chart) => chart.name" empty-text="Chưa có bảng size.">
        <template #cell-name="{ row }">
            <Link :href="`${urls.base}/${row.id}`" class="font-medium text-indigo-600">{{ row.name }}</Link>
            <span v-if="row.is_default" class="ml-2 rounded bg-indigo-50 px-1.5 py-0.5 text-xs text-indigo-700">Mặc định</span>
        </template>
        <template #cell-assignments="{ row }">
            <span v-if="!Object.keys(row.assignments).length" class="text-slate-400">—</span>
            <span v-for="(count, target) in row.assignments" :key="target" class="mr-2">{{ count }} {{ targetLabels[target] ?? target }}</span>
        </template>
        <template #cell-active="{ row }"><StatusBadge :label="row.active ? 'Đang bật' : 'Tắt'" :tone="row.active ? 'success' : 'neutral'" /></template>
    </DataTable>
</template>
