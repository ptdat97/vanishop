<script setup lang="ts">
import { t } from '@admin/i18n';
import DataTable from '@admin/Components/DataTable.vue';
import MoneyText from '@admin/Components/MoneyText.vue';
import PageHeader from '@admin/Components/PageHeader.vue';
import StatusBadge from '@admin/Components/StatusBadge.vue';
import { primaryButton } from '@admin/styles';
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';

type State = 'running' | 'scheduled' | 'ended' | 'off';

interface Row {
    id: number;
    name: string;
    code: string | null;
    kind: 'shared' | 'template';
    offer: { percent?: number; max?: number | null; amount?: number };
    min_subtotal: number | null;
    used: number;
    discount_total: number;
    usage_limit: number | null;
    state: State;
    public: boolean;
    period: string[];
    edit_url: string;
}

/** Admin → Marketing → Mã giảm giá (vani.coupon-code): mỗi mã là một khuyến mãi cần nhập mã, tự đồng bộ ở Khuyến mãi. */
defineProps<{
    coupons: Row[];
    couponUrls: { create: string };
    can: { manage: boolean };
}>();

const tones: Record<State, 'success' | 'info' | 'neutral'> = { running: 'success', scheduled: 'info', ended: 'neutral', off: 'neutral' };
const columns = computed(() => [
    { key: 'name', label: t('vani-coupon-code::admin.index.coupon'), class: 'font-medium' },
    { key: 'offer', label: t('vani-coupon-code::admin.index.offer') },
    { key: 'used', label: t('vani-coupon-code::admin.index.used'), align: 'right' as const },
    { key: 'discount_total', label: t('vani-coupon-code::admin.index.discount_total'), align: 'right' as const },
    { key: 'state', label: t('vani-coupon-code::admin.index.status') },
]);
</script>

<template>
    <div>
        <Head :title="t('vani-coupon-code::admin.index.title')" />
        <PageHeader :title="t('vani-coupon-code::admin.index.title')" :subtitle="t('vani-coupon-code::admin.index.subtitle')">
            <Link v-if="can.manage" :href="couponUrls.create" :class="primaryButton">{{ t('vani-coupon-code::admin.index.add') }}</Link>
        </PageHeader>
        <DataTable :columns="columns" :rows="coupons" :row-label="(row) => row.name" :empty-text="t('vani-coupon-code::admin.index.empty')">
            <template #cell-name="{ row }">
                <Link :href="row.edit_url" class="hover:underline">{{ row.name }}</Link>
                <div class="font-mono text-xs text-slate-500">{{ row.code ?? t('vani-coupon-code::admin.index.template') }}</div>
                <div class="text-xs text-slate-400">{{ row.period.join(' → ') }}</div>
            </template>
            <template #cell-offer="{ row }">
                <span v-if="row.offer.percent !== undefined">{{ t('vani-coupon-code::admin.index.percent', { percent: row.offer.percent }) }}<template v-if="row.offer.max"> · {{ t('vani-coupon-code::admin.index.max') }} <MoneyText :amount="row.offer.max" /></template></span>
                <span v-else>{{ t('vani-coupon-code::admin.index.amount') }} <MoneyText :amount="row.offer.amount ?? 0" /></span>
                <div v-if="row.min_subtotal" class="text-xs text-slate-500">{{ t('vani-coupon-code::admin.index.min_subtotal') }} <MoneyText :amount="row.min_subtotal" /></div>
            </template>
            <template #cell-used="{ row }">{{ row.used }}<span v-if="row.usage_limit"> / {{ row.usage_limit }}</span></template>
            <template #cell-discount_total="{ row }"><MoneyText :amount="row.discount_total" /></template>
            <template #cell-state="{ row }">
                <StatusBadge :label="t(`vani-coupon-code::admin.state.${row.state}`)" :tone="tones[row.state]" />
                <span v-if="row.public" class="ms-2 text-xs text-slate-500">{{ t('vani-coupon-code::admin.index.public') }}</span>
            </template>
        </DataTable>
    </div>
</template>
