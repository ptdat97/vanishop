<script setup lang="ts">
import { t } from '@admin/i18n';
import DataTable from '@admin/Components/DataTable.vue';
import PageHeader from '@admin/Components/PageHeader.vue';
import StatusBadge from '@admin/Components/StatusBadge.vue';
import { primaryButton } from '@admin/styles';
import { Head, Link, router } from '@inertiajs/vue3';
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
    { key: 'name', label: t('catalog::admin.collections_index.name'), class: 'font-medium' },
    { key: 'slug', label: 'Slug', class: 'text-slate-500' },
    { key: 'styles_count', label: t('catalog::admin.collections_index.products') },
    { key: 'status', label: t('catalog::admin.collections_index.status') },
];
</script>

<template>
    <Head :title="t('catalog::admin.collections_index.collections')" />
    <CatalogTabs :nav="nav" active="collections" />
    <PageHeader :title="t('catalog::admin.collections_index.collections')" :subtitle="t('catalog::admin.collections_index.product_sets_landing_pages_campaigns')">
        <Link v-if="canManage" :href="`${baseUrl}/create`" :class="primaryButton">{{ t('catalog::admin.collections_index.add_collection') }}</Link>
    </PageHeader>
    <DataTable :columns="columns" :rows="collections" :row-label="(collection) => collection.slug" :row-click="canManage ? (collection) => router.visit(`${baseUrl}/${collection.id}/edit`) : undefined" :empty-text="t('catalog::admin.collections_index.no_collections_yet')">
        <template #cell-slug="{ row }">/{{ row.slug }}</template>
        <template #cell-status="{ row }"><StatusBadge :label="labels.visibility[row.status] ?? row.status" :tone="row.status === 'active' ? 'success' : 'neutral'" /></template>
    </DataTable>
</template>
