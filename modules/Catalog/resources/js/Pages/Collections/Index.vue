<script setup lang="ts">
import { t } from '@admin/i18n';
import PageHeader from '@admin/Components/PageHeader.vue';
import { primaryButton } from '@admin/styles';
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import CatalogTabs from '../../Components/CatalogTabs.vue';
import TaxonomyTable from '../../Components/Taxonomy/TaxonomyTable.vue';
import type { CatalogLabels, NavItem, TaxonomyRow } from '../../types';

const props = defineProps<{ nav: NavItem[]; labels: CatalogLabels; collections: TaxonomyRow[]; canManage: boolean }>();
const baseUrl = computed(() => props.nav.find((item) => item.key === 'collections')?.url ?? '');
</script>

<template>
    <Head :title="t('catalog::admin.collections.title')" />
    <CatalogTabs :nav="nav" active="collections" />
    <PageHeader :title="t('catalog::admin.collections.title')" :subtitle="t('catalog::admin.collections.subtitle')">
        <Link v-if="canManage" :href="`${baseUrl}/create`" :class="primaryButton">{{ t('catalog::admin.collections.add') }}</Link>
    </PageHeader>
    <TaxonomyTable :rows="collections" :base-url="baseUrl" :can-manage="canManage" :visibility="labels.visibility" :empty-text="t('catalog::admin.collections.empty')" />
</template>
