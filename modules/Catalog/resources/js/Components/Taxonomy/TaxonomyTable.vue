<script setup lang="ts">
import { t } from '@admin/i18n';
import DataTable from '@admin/Components/DataTable.vue';
import StatusBadge from '@admin/Components/StatusBadge.vue';
import { router } from '@inertiajs/vue3';
import type { TaxonomyRow } from '../../types';

/** Bảng danh sách chung của thương hiệu và bộ sưu tập: ảnh + tên, slug, số sản phẩm, trạng thái; bấm dòng để sửa. */
const props = defineProps<{ rows: TaxonomyRow[]; baseUrl: string; canManage: boolean; visibility: Record<string, string>; emptyText: string; showCode?: boolean }>();
const columns = [
    { key: 'name', label: t('catalog::admin.taxonomy.name'), class: 'font-medium' },
    ...(props.showCode ? [{ key: 'code', label: t('catalog::admin.brands.code'), class: 'font-mono text-xs' }] : []),
    { key: 'slug', label: 'Slug', class: 'font-mono text-xs text-slate-500' },
    { key: 'styles_count', label: t('catalog::admin.taxonomy.products') },
    { key: 'status', label: t('catalog::admin.taxonomy.status') },
];
</script>

<template>
    <DataTable :columns="columns" :rows="rows" :row-label="(row) => row.name ?? row.slug" :row-click="canManage ? (row) => router.visit(`${baseUrl}/${row.id}/edit`) : undefined" :empty-text="emptyText">
        <template #cell-name="{ row }">
            <span class="flex items-center gap-3">
                <img v-if="row.image_url" :src="row.image_url" alt="" class="h-9 w-9 rounded object-cover" />
                <span v-else class="h-9 w-9 rounded bg-slate-100" aria-hidden="true" />
                <span>{{ row.name ?? row.slug }}</span>
            </span>
        </template>
        <template #cell-slug="{ row }">/{{ row.slug }}</template>
        <template #cell-status="{ row }"><StatusBadge :label="visibility[row.status] ?? row.status" :tone="row.status === 'active' ? 'success' : 'neutral'" /></template>
    </DataTable>
</template>
