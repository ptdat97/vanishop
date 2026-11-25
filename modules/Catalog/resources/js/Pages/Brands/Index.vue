<script setup lang="ts">
import { t } from '@admin/i18n';
import PageHeader from '@admin/Components/PageHeader.vue';
import { primaryButton } from '@admin/styles';
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import CatalogTabs from '../../Components/CatalogTabs.vue';
import TaxonomyTable from '../../Components/Taxonomy/TaxonomyTable.vue';
import type { CatalogLabels, NavItem, TaxonomyRow } from '../../types';

const props = defineProps<{ nav: NavItem[]; labels: CatalogLabels; brands: TaxonomyRow[]; canManage: boolean }>();
const baseUrl = computed(() => props.nav.find((item) => item.key === 'brands')?.url ?? '');
</script>

<template>
    <Head :title="t('catalog::admin.brands.title')" />
    <CatalogTabs :nav="nav" active="brands" />
    <PageHeader :title="t('catalog::admin.brands.title')" :subtitle="t('catalog::admin.brands.subtitle')">
        <Link v-if="canManage" :href="`${baseUrl}/create`" :class="primaryButton">{{ t('catalog::admin.brands.add') }}</Link>
    </PageHeader>
    <TaxonomyTable :rows="brands" :base-url="baseUrl" :can-manage="canManage" :visibility="labels.visibility" :empty-text="t('catalog::admin.brands.empty')" show-code />
</template>
