<script setup lang="ts">
import { t } from '@admin/i18n';
import DataTable from '@admin/Components/DataTable.vue';
import PageHeader from '@admin/Components/PageHeader.vue';
import { primaryButton } from '@admin/styles';
import { Head, Link, router } from '@inertiajs/vue3';
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
    { key: 'name', label: t('catalog::admin.taxonomy_attributes.name'), class: 'font-medium' },
    { key: 'code', label: t('catalog::admin.taxonomy_attributes.code'), class: 'font-mono text-xs' },
    { key: 'kind', label: t('catalog::admin.taxonomy_attributes.type') },
    { key: 'input_type', label: t('catalog::admin.taxonomy_attributes.input_type') },
    { key: 'is_filterable', label: t('catalog::admin.taxonomy_attributes.filter') },
    { key: 'values_count', label: t('catalog::admin.taxonomy_attributes.value') },
];
</script>

<template>
    <Head :title="t('catalog::admin.taxonomy_attributes.attributes')" />
    <CatalogTabs :nav="nav" active="attributes" />
    <PageHeader :title="t('catalog::admin.taxonomy_attributes.attributes')" :subtitle="t('catalog::admin.taxonomy_attributes.material_fit_season_internal_attributes')">
        <Link v-if="canManage" :href="`${baseUrl}/create`" :class="primaryButton">{{ t('catalog::admin.taxonomy_attributes.add_attribute') }}</Link>
    </PageHeader>
    <DataTable :columns="columns" :rows="attributes" :row-label="(attribute) => attribute.code" :row-click="canManage ? (attribute) => router.visit(`${baseUrl}/${attribute.id}/edit`) : undefined" :empty-text="t('catalog::admin.taxonomy_attributes.no_attributes_yet')">
        <template #cell-kind="{ row }">{{ labels.attribute_kind[row.kind] ?? row.kind }}</template>
        <template #cell-is_filterable="{ row }">{{ row.is_filterable ? t('catalog::admin.taxonomy_attributes.yes') : '—' }}</template>
        <template #cell-values_count="{ row }">{{ row.values_count || '—' }}</template>
    </DataTable>
</template>
