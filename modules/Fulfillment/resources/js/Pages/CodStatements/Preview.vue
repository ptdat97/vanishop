<script setup lang="ts">
import { t } from '@admin/i18n';
import DataTable from '@admin/Components/DataTable.vue';
import MoneyText from '@admin/Components/MoneyText.vue';
import PageHeader from '@admin/Components/PageHeader.vue';
import { primaryButton, secondaryButton } from '@admin/styles';
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

type Line = {
    line: number;
    tracking: string;
    amount: number | null;
    fee: number | null;
    expected: number | null;
    status: string;
    order_number: string | null;
    order_url: string | null;
};

/** Xem trước bảng kê COD (A9): chưa ghi gì cho tới khi bấm Áp dụng; dòng lệch vào hàng đợi "Đối soát lệch". */
const props = defineProps<{
    preview: { token: string | null; file: string; lines: Line[]; summary: Record<string, number>; total: number };
    statusLabels: Record<string, string>;
    urls: { back: string; apply: string | null };
}>();

const columns = [
    { key: 'line', label: t('fulfillment::admin.cod_statements_preview.lines') },
    { key: 'tracking', label: t('fulfillment::admin.cod_statements_preview.tracking_number'), class: 'font-mono' },
    { key: 'order', label: t('fulfillment::admin.cod_statements_preview.order') },
    { key: 'amount', label: t('fulfillment::admin.cod_statements_preview.carrier_recorded_cod') },
    { key: 'expected', label: t('fulfillment::admin.cod_statements_preview.shipment_cod') },
    { key: 'fee', label: t('fulfillment::admin.cod_statements_preview.carrier_fee') },
    { key: 'status', label: t('fulfillment::admin.cod_statements_preview.result') },
];
const MISMATCH = ['amount_mismatch', 'not_found', 'not_delivered'];
const onlyIssues = ref(false);
const shown = computed(() => (onlyIssues.value ? props.preview.lines.filter((line) => line.status !== 'matched') : props.preview.lines).slice(0, 1000));
const matched = computed(() => props.preview.summary.matched ?? 0);
const mismatched = computed(() => MISMATCH.reduce((sum, status) => sum + (props.preview.summary[status] ?? 0), 0));
const applying = ref(false);

function apply(): void {
    if (!props.urls.apply) return;
    applying.value = true;
    router.post(props.urls.apply, {}, { onFinish: () => (applying.value = false) });
}

function tone(status: string): string {
    return status === 'matched' ? 'text-emerald-700' : MISMATCH.includes(status) ? 'text-red-600' : 'text-slate-500';
}
</script>

<template>
    <Head :title="t('fulfillment::admin.cod_statements_preview.cod_statement_preview')" />
    <PageHeader :title="t('fulfillment::admin.cod_statements_preview.cod_statement_file', { file: preview.file })" :subtitle="t('fulfillment::admin.cod_statements_preview.nothing_written_yet_matching_rows')">
        <Link :href="urls.back" :class="secondaryButton">{{ t('fulfillment::admin.cod_statements_preview.back') }}</Link>
        <button v-if="urls.apply" type="button" :class="primaryButton" :disabled="applying" data-apply @click="apply">
            {{ t('fulfillment::admin.cod_statements_preview.apply_matched_matched_mismatched_mismatched', { matched, mismatched }) }}
        </button>
    </PageHeader>

    <div class="mb-4 flex flex-wrap gap-2 text-sm">
        <span class="rounded-full bg-emerald-50 px-3 py-1 text-emerald-800">{{ t('fulfillment::admin.cod_statements_preview.matched_matched', { matched }) }} <MoneyText :amount="preview.total" /></span>
        <span v-for="(count, status) in preview.summary" v-show="status !== 'matched'" :key="status" class="rounded-full px-3 py-1" :class="MISMATCH.includes(String(status)) ? 'bg-red-50 text-red-700' : 'bg-slate-100'">
            {{ statusLabels[status] ?? status }}: {{ count }}
        </span>
    </div>
    <p v-if="!urls.apply" class="mb-4 text-sm text-slate-600">{{ t('fulfillment::admin.cod_statements_preview.no_rows_write_file_missing') }}</p>
    <label class="mb-2 flex items-center gap-2 text-sm"><input v-model="onlyIssues" type="checkbox" /> {{ t('fulfillment::admin.cod_statements_preview.show_only_mismatched_rows') }}</label>

    <DataTable :columns="columns" :rows="shown" :row-key="(line) => `${line.line}`" :row-label="(line) => line.tracking" :empty-text="t('fulfillment::admin.cod_statements_preview.file_has_no_shipment_rows')">
        <template #cell-order="{ row }">
            <Link v-if="row.order_url" :href="row.order_url" class="text-indigo-600 hover:underline">{{ row.order_number ?? '—' }}</Link>
            <span v-else>—</span>
        </template>
        <template #cell-amount="{ row }"><MoneyText v-if="row.amount !== null" :amount="row.amount" /><span v-else>—</span></template>
        <template #cell-expected="{ row }"><MoneyText v-if="row.expected !== null" :amount="row.expected" /><span v-else>—</span></template>
        <template #cell-fee="{ row }"><MoneyText v-if="row.fee !== null" :amount="row.fee" /><span v-else>—</span></template>
        <template #cell-status="{ row }"><span :class="tone(row.status)">{{ statusLabels[row.status] ?? row.status }}</span></template>
    </DataTable>
    <p v-if="preview.lines.length > 1000" class="mt-2 text-xs text-slate-500">{{ t('fulfillment::admin.cod_statements_preview.only_first_1000_rows_shown') }}</p>
</template>
