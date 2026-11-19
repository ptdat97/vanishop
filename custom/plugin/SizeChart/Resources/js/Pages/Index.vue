<script setup lang="ts">
import { t } from '@admin/i18n';
import DataTable from '@admin/Components/DataTable.vue';
import PageHeader from '@admin/Components/PageHeader.vue';
import StatusBadge from '@admin/Components/StatusBadge.vue';
import { primaryButton } from '@admin/styles';
import { Head, Link } from '@inertiajs/vue3';

defineProps<{
    charts: Array<{ id: number; name: string; unit: string; is_default: boolean; active: boolean; assignments: Record<string, number> }>;
    can: { manage: boolean };
    urls: { create: string; base: string };
}>();

const targetLabels: Record<string, string> = { product: t('vani-size-chart::admin.index.products'), category: t('vani-size-chart::admin.index.categories'), brand: t('vani-size-chart::admin.index.brands') };
const columns = [
    { key: 'name', label: t('vani-size-chart::admin.index.name') },
    { key: 'unit', label: t('vani-size-chart::admin.index.unit') },
    { key: 'assignments', label: t('vani-size-chart::admin.index.applies') },
    { key: 'active', label: t('vani-size-chart::admin.index.status') },
];
</script>

<template>
    <Head :title="t('vani-size-chart::admin.index.size_charts')" />
    <PageHeader :title="t('vani-size-chart::admin.index.size_charts')" :subtitle="t('vani-size-chart::admin.index.assign_charts_product_category_brand')">
        <Link v-if="can.manage" :href="urls.create" :class="primaryButton">{{ t('vani-size-chart::admin.index.add_size_chart') }}</Link>
    </PageHeader>

    <DataTable :columns="columns" :rows="charts" :row-label="(chart) => chart.name" :empty-text="t('vani-size-chart::admin.index.no_size_charts_yet')">
        <template #cell-name="{ row }">
            <Link :href="`${urls.base}/${row.id}`" class="font-medium text-indigo-600">{{ row.name }}</Link>
            <span v-if="row.is_default" class="ml-2 rounded bg-indigo-50 px-1.5 py-0.5 text-xs text-indigo-700">{{ t('vani-size-chart::admin.index.default') }}</span>
        </template>
        <template #cell-assignments="{ row }">
            <span v-if="!Object.keys(row.assignments).length" class="text-slate-400">—</span>
            <span v-for="(count, target) in row.assignments" :key="target" class="mr-2">{{ count }} {{ targetLabels[target] ?? target }}</span>
        </template>
        <template #cell-active="{ row }"><StatusBadge :label="row.active ? t('vani-size-chart::admin.index.active') : t('vani-size-chart::admin.index.off')" :tone="row.active ? 'success' : 'neutral'" /></template>
    </DataTable>
</template>
