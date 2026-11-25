<script setup lang="ts">
import { t } from '@admin/i18n';
import DataTable from '@admin/Components/DataTable.vue';
import PageHeader from '@admin/Components/PageHeader.vue';
import StatusBadge from '@admin/Components/StatusBadge.vue';
import { primaryButton, secondaryButton } from '@admin/styles';
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';

interface Row {
    id: number;
    name: string;
    slug: string;
    items_count: number;
    discount_percent: number;
    active: boolean;
    running: boolean;
    period: string[];
    edit_url: string;
    promotion_url: string | null;
}

/** Admin → Sản phẩm → Combo (vani.bundles): mỗi combo là một khuyến mãi tự đồng bộ ở Khuyến mãi. */
defineProps<{
    bundles: Row[];
    bundleUrls: { create: string; storefront: string };
    can: { manage: boolean };
}>();

const columns = computed(() => [
    { key: 'name', label: t('vani-bundles::admin.index.bundle'), class: 'font-medium' },
    { key: 'items_count', label: t('vani-bundles::admin.index.products'), align: 'right' as const },
    { key: 'discount_percent', label: t('vani-bundles::admin.index.discount'), align: 'right' as const },
    { key: 'status', label: t('vani-bundles::admin.index.status') },
]);
</script>

<template>
    <div>
        <Head :title="t('vani-bundles::admin.index.title')" />
        <PageHeader :title="t('vani-bundles::admin.index.title')" :subtitle="t('vani-bundles::admin.index.subtitle')">
            <a :href="bundleUrls.storefront" :class="secondaryButton">{{ t('vani-bundles::admin.index.view_page') }}</a>
            <Link v-if="can.manage" :href="bundleUrls.create" :class="primaryButton">{{ t('vani-bundles::admin.index.add') }}</Link>
        </PageHeader>
        <DataTable :columns="columns" :rows="bundles" :row-label="(row) => row.name" :empty-text="t('vani-bundles::admin.index.empty')">
            <template #cell-name="{ row }">
                <Link :href="row.edit_url" class="hover:underline">{{ row.name }}</Link>
                <div class="text-xs text-slate-400">{{ row.period.join(' → ') }}</div>
            </template>
            <template #cell-discount_percent="{ row }">{{ row.discount_percent }}%</template>
            <template #cell-status="{ row }">
                <StatusBadge :label="row.running ? t('vani-bundles::admin.index.running') : row.active ? t('vani-bundles::admin.index.outside_period') : t('vani-bundles::admin.index.off')" :tone="row.running ? 'success' : 'neutral'" />
                <Link v-if="row.promotion_url" :href="row.promotion_url" class="ms-2 text-xs text-indigo-600 hover:underline">{{ t('vani-bundles::admin.index.promotion') }}</Link>
            </template>
        </DataTable>
    </div>
</template>
