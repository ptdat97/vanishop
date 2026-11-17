<script setup lang="ts">
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
    { key: 'line', label: 'Dòng' },
    { key: 'tracking', label: 'Mã vận đơn', class: 'font-mono' },
    { key: 'order', label: 'Đơn' },
    { key: 'amount', label: 'Hãng ghi thu hộ' },
    { key: 'expected', label: 'COD vận đơn' },
    { key: 'fee', label: 'Phí hãng' },
    { key: 'status', label: 'Kết quả' },
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
    <Head title="Xem trước bảng kê COD" />
    <PageHeader :title="`Bảng kê COD: ${preview.file}`" subtitle="Chưa ghi gì. Dòng khớp được ghi là đã đối soát (và đã thu nếu chưa ghi nhận); dòng lệch vào mục &quot;Đối soát lệch&quot; ở trang Thanh toán để xử lý.">
        <Link :href="urls.back" :class="secondaryButton">Quay lại</Link>
        <button v-if="urls.apply" type="button" :class="primaryButton" :disabled="applying" data-apply @click="apply">
            Áp dụng: {{ matched }} khớp, {{ mismatched }} lệch
        </button>
    </PageHeader>

    <div class="mb-4 flex flex-wrap gap-2 text-sm">
        <span class="rounded-full bg-emerald-50 px-3 py-1 text-emerald-800">Khớp: {{ matched }} · <MoneyText :amount="preview.total" /></span>
        <span v-for="(count, status) in preview.summary" v-show="status !== 'matched'" :key="status" class="rounded-full px-3 py-1" :class="MISMATCH.includes(String(status)) ? 'bg-red-50 text-red-700' : 'bg-slate-100'">
            {{ statusLabels[status] ?? status }}: {{ count }}
        </span>
    </div>
    <p v-if="!urls.apply" class="mb-4 text-sm text-slate-600">Không có dòng nào cần ghi — file thiếu cột, hoặc mọi dòng đã đối soát trước đó.</p>
    <label class="mb-2 flex items-center gap-2 text-sm"><input v-model="onlyIssues" type="checkbox" /> Chỉ hiện dòng không khớp</label>

    <DataTable :columns="columns" :rows="shown" :row-key="(line) => `${line.line}`" :row-label="(line) => line.tracking" empty-text="File không có dòng vận đơn nào.">
        <template #cell-order="{ row }">
            <Link v-if="row.order_url" :href="row.order_url" class="text-indigo-600 hover:underline">{{ row.order_number ?? '—' }}</Link>
            <span v-else>—</span>
        </template>
        <template #cell-amount="{ row }"><MoneyText v-if="row.amount !== null" :amount="row.amount" /><span v-else>—</span></template>
        <template #cell-expected="{ row }"><MoneyText v-if="row.expected !== null" :amount="row.expected" /><span v-else>—</span></template>
        <template #cell-fee="{ row }"><MoneyText v-if="row.fee !== null" :amount="row.fee" /><span v-else>—</span></template>
        <template #cell-status="{ row }"><span :class="tone(row.status)">{{ statusLabels[row.status] ?? row.status }}</span></template>
    </DataTable>
    <p v-if="preview.lines.length > 1000" class="mt-2 text-xs text-slate-500">Chỉ hiện 1000 dòng đầu.</p>
</template>
