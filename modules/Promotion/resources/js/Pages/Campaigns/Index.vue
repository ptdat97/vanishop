<script setup lang="ts">
import { t } from '@admin/i18n';
import DataTable from '@admin/Components/DataTable.vue';
import PageHeader from '@admin/Components/PageHeader.vue';
import StatusBadge from '@admin/Components/StatusBadge.vue';
import { primaryButton, secondaryButton } from '@admin/styles';
import { Head, Link, router } from '@inertiajs/vue3';
import { campaignStateTone, type PromotionLabels } from '../../labels';

const props = defineProps<{
    baseUrl: string;
    labels: PromotionLabels;
    promotionsUrl: string;
    campaigns: Array<{ id: number; code: string; name: string; state: string; starts_at: string; ends_at: string; promotions_count: number; price_lists_count: number }>;
    canManage: boolean;
}>();

const columns = [
    { key: 'name', label: 'Campaign' },
    { key: 'state', label: t('promotion::admin.campaigns_index.status') },
    { key: 'period', label: t('promotion::admin.campaigns_index.period_store_time'), class: 'text-xs' },
    { key: 'members', label: t('promotion::admin.campaigns_index.member'), class: 'text-xs' },
];
</script>

<template>
    <Head title="Campaign" />
    <PageHeader title="Campaign" :subtitle="t('promotion::admin.campaigns_index.bundle_promotions_sale_member_price')">
        <Link :href="promotionsUrl" :class="secondaryButton">{{ t('promotion::admin.campaigns_index.promotion') }}</Link>
        <Link v-if="canManage" :href="`${baseUrl}/create`" :class="primaryButton">{{ t('promotion::admin.campaigns_index.add_campaign') }}</Link>
    </PageHeader>
    <DataTable :columns="columns" :rows="campaigns" :row-label="(campaign) => campaign.code" :row-click="(campaign) => router.visit(`${props.baseUrl}/${campaign.id}/edit`)" :empty-text="t('promotion::admin.campaigns_index.no_campaigns_yet')">
        <template #cell-name="{ row }">
            <span class="font-medium">{{ row.name }}</span>
            <div class="font-mono text-xs text-slate-400">{{ row.code }}</div>
        </template>
        <template #cell-state="{ row }"><StatusBadge :label="labels.campaign_state[row.state] ?? row.state" :tone="campaignStateTone(row.state)" /></template>
        <template #cell-period="{ row }">{{ row.starts_at }} → {{ row.ends_at }}</template>
        <template #cell-members="{ row }">{{ t('promotion::admin.campaigns_index.promotions_count_promotions_price_lists', { promotions_count: row.promotions_count, price_lists_count: row.price_lists_count }) }}</template>
    </DataTable>
</template>
