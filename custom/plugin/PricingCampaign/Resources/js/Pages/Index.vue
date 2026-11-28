<script setup lang="ts">
import { t } from '@admin/i18n';
import DataTable from '@admin/Components/DataTable.vue';
import PageHeader from '@admin/Components/PageHeader.vue';
import StatusBadge from '@admin/Components/StatusBadge.vue';
import { primaryButton, secondaryButton } from '@admin/styles';
import { Head, Link, router } from '@inertiajs/vue3';
import { computed } from 'vue';

type State = 'draft' | 'scheduled' | 'running' | 'waiting' | 'ended' | 'stopped';

interface Row {
    id: number;
    name: string;
    template: string;
    state: State;
    period: string[];
    recurring: boolean;
    items_count: number;
    sold: number | null;
    quota: number | null;
    edit_url: string;
}

/** Admin → Marketing → Chiến dịch giá (vani.pricing-campaign). */
defineProps<{
    campaigns: Row[];
    urls: { create: string; storefront: string };
    can: { manage: boolean };
}>();

const tones: Record<State, 'success' | 'info' | 'warning' | 'neutral' | 'danger'> = { running: 'success', scheduled: 'info', waiting: 'warning', draft: 'neutral', ended: 'neutral', stopped: 'danger' };
const columns = computed(() => [
    { key: 'name', label: t('vani-pricing-campaign::admin.index.campaign'), class: 'font-medium' },
    { key: 'items_count', label: t('vani-pricing-campaign::admin.index.products'), align: 'right' as const },
    { key: 'sold', label: t('vani-pricing-campaign::admin.index.sold'), align: 'right' as const },
    { key: 'state', label: t('vani-pricing-campaign::admin.index.status') },
]);
</script>

<template>
    <div>
        <Head :title="t('vani-pricing-campaign::admin.index.title')" />
        <PageHeader :title="t('vani-pricing-campaign::admin.index.title')" :subtitle="t('vani-pricing-campaign::admin.index.subtitle')">
            <a :href="urls.storefront" :class="secondaryButton">{{ t('vani-pricing-campaign::admin.index.view_page') }}</a>
            <Link v-if="can.manage" :href="urls.create" :class="primaryButton">{{ t('vani-pricing-campaign::admin.index.add') }}</Link>
        </PageHeader>
        <DataTable :columns="columns" :rows="campaigns" :row-label="(row) => row.name" :row-click="(row) => router.visit(row.edit_url)" :empty-text="t('vani-pricing-campaign::admin.index.empty')">
            <template #cell-name="{ row }">
                <Link :href="row.edit_url" class="hover:underline">{{ row.name }}</Link>
                <div class="text-xs text-slate-500">{{ t(`vani-pricing-campaign::admin.template.${row.template}`) }}<template v-if="row.recurring"> · {{ t('vani-pricing-campaign::admin.index.recurring') }}</template></div>
                <div class="text-xs text-slate-400">{{ row.period.join(' → ') }}</div>
            </template>
            <template #cell-sold="{ row }">{{ row.sold ?? '—' }}<span v-if="row.quota"> / {{ row.quota }}</span></template>
            <template #cell-state="{ row }"><StatusBadge :label="t(`vani-pricing-campaign::admin.state.${row.state}`)" :tone="tones[row.state]" /></template>
        </DataTable>
    </div>
</template>
