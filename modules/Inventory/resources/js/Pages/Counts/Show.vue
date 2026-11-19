<script setup lang="ts">
import { t } from '@admin/i18n';
import DataTable from '@admin/Components/DataTable.vue';
import FileInput from '@admin/Components/FileInput.vue';
import PageHeader from '@admin/Components/PageHeader.vue';
import StatusBadge from '@admin/Components/StatusBadge.vue';
import { confirmAction } from '@admin/confirm';
import { HttpError, postJson } from '@admin/http';
import { inputClass, primaryButton, secondaryButton, dangerButton } from '@admin/styles';
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

type Line = { variant_id: number; sku: string; name: string; counted: number; on_hand_at_count: number; on_hand: number; delta: number; new_on_hand: number; applied_delta: number | null };

/**
 * Phiên kiểm kê (A7): quét hoặc gõ SKU rồi Enter (mỗi lần +1, "SKU 12" = +12) — lưu ngay, nhiều người cùng đếm được; sửa số
 * đếm trong bảng. "Áp dụng" ghi phần chênh `số đếm − tồn lúc đếm` cho mọi SKU đã đếm.
 */
const props = defineProps<{
    count: { id: number; number: string; location: string | null; note: string | null; status: string; created_at: string; applied_at: string | null };
    lines: Line[];
    statusLabels: Record<string, string>;
    canManage: boolean;
    urls: { index: string; record: string; line: string; apply: string; cancel: string };
}>();

const scan = ref('');
const file = ref<File | null>(null);
const errors = ref<string[]>([]);
const onlyDiff = ref(false);
const shown = computed(() => (onlyDiff.value ? props.lines.filter((line) => line.delta !== 0) : props.lines));
const summary = computed(() => ({
    skus: props.lines.length,
    diff: props.lines.filter((line) => line.delta !== 0).length,
    plus: props.lines.reduce((sum, line) => sum + Math.max(0, line.delta), 0),
    minus: props.lines.reduce((sum, line) => sum + Math.min(0, line.delta), 0),
}));
const columns = computed(() => [
    { key: 'sku', label: 'SKU', class: 'font-mono text-xs' },
    { key: 'name', label: t('inventory::admin.counts_show.products') },
    { key: 'counted', label: t('inventory::admin.counts_show.counted'), align: 'right' as const },
    { key: 'on_hand_at_count', label: t('inventory::admin.counts_show.stock_count'), align: 'right' as const, class: 'tabular-nums' },
    { key: 'delta', label: props.count.status === 'applied' ? t('inventory::admin.counts_show.recorded') : t('inventory::admin.counts_show.difference'), align: 'right' as const, class: 'tabular-nums' },
    { key: 'new_on_hand', label: t('inventory::admin.counts_show.stock_after_apply'), align: 'right' as const, class: 'tabular-nums' },
]);

// Hàng đợi: mỗi lần quét gửi lần lượt (không huỷ lần trước), danh sách tải lại khi hàng đợi trống.
let queue: Promise<void> = Promise.resolve();
let pending = 0;
function send(body: FormData | Record<string, unknown>): void {
    pending++;
    queue = queue
        .then(async () => {
            const result = await postJson<{ recorded: number; errors: string[] }>(props.urls.record, body);
            if (result.errors.length) errors.value = [...result.errors, ...errors.value].slice(0, 20);
        })
        .catch((error) => {
            errors.value = [error instanceof HttpError ? error.message : t('inventory::admin.counts_show.could_not_record_count'), ...errors.value].slice(0, 20);
        })
        .finally(() => {
            if (--pending === 0) router.reload({ only: ['lines'] });
        });
}

function addScan(): void {
    const text = scan.value.trim();
    scan.value = '';
    if (text !== '') send({ text });
}

function addFile(): void {
    if (!file.value) return;
    const body = new FormData();
    body.append('file', file.value);
    send(body);
    file.value = null;
}

function setCounted(line: Line, value: string): void {
    router.put(props.urls.line.replace(/\/0$/, `/${line.variant_id}`), { counted: value === '' ? null : Number(value) }, { preserveScroll: true, only: ['lines'] });
}

async function apply(): Promise<void> {
    const message = t('inventory::admin.counts_show.skus_skus_counted_diff_skus', { skus: summary.value.skus, diff: summary.value.diff, plus: summary.value.plus, minus: summary.value.minus });
    if ((await confirmAction({ title: t('inventory::admin.counts_show.apply_stock_count_number', { number: props.count.number }), message, confirmLabel: t('inventory::admin.counts_show.apply') })) !== false) {
        router.post(props.urls.apply);
    }
}

async function cancel(): Promise<void> {
    if ((await confirmAction({ title: t('inventory::admin.counts_show.cancel_stock_count'), message: t('inventory::admin.counts_show.counts_not_written_stock'), confirmLabel: t('inventory::admin.counts_show.cancel_session'), tone: 'danger' })) !== false) {
        router.post(props.urls.cancel);
    }
}
</script>

<template>
    <Head :title="t('inventory::admin.counts_show.stock_count_number', { number: count.number })" />
    <PageHeader :title="t('inventory::admin.counts_show.stock_count_number', { number: count.number })" :subtitle="[count.location, count.note, count.applied_at ? t('inventory::admin.counts_show.applied_applied', { applied_at: count.applied_at }) : null].filter(Boolean).join(' · ')">
        <StatusBadge :label="statusLabels[count.status] ?? count.status" :tone="count.status === 'applied' ? 'success' : count.status === 'counting' ? 'warning' : 'neutral'" />
        <Link :href="urls.index" :class="secondaryButton">{{ t('inventory::admin.counts_show.sessions') }}</Link>
        <button v-if="canManage" type="button" :class="dangerButton" data-cancel @click="cancel">{{ t('inventory::admin.counts_show.cancel_session') }}</button>
        <button v-if="canManage" type="button" :class="primaryButton" :disabled="!lines.length" data-apply @click="apply">{{ t('inventory::admin.counts_show.apply') }}</button>
    </PageHeader>

    <div v-if="canManage" class="mb-4 space-y-3 rounded-lg border border-slate-200 bg-white p-4 text-sm">
        <form class="flex gap-2" @submit.prevent="addScan">
            <input v-model="scan" :class="inputClass" autofocus :placeholder="t('inventory::admin.counts_show.scan_type_sku_then_enter')" :aria-label="t('inventory::admin.counts_show.scan_sku')" data-scan />
            <button type="submit" :class="secondaryButton">{{ t('inventory::admin.counts_show.add') }}</button>
        </form>
        <details>
            <summary class="cursor-pointer text-slate-600">{{ t('inventory::admin.counts_show.upload_count_file') }}</summary>
            <div class="mt-3 flex flex-wrap items-center gap-2">
                <FileInput v-model="file" accept=".xlsx,.csv" :max-kb="2048" :label="t('inventory::admin.counts_show.excel_csv_file_sku_quantity')" />
                <button type="button" :class="secondaryButton" :disabled="!file" @click="addFile">{{ t('inventory::admin.counts_show.add_from_file') }}</button>
            </div>
        </details>
        <ul v-if="errors.length" class="list-inside list-disc text-red-600" role="alert">
            <li v-for="error in errors" :key="error">{{ error }}</li>
        </ul>
    </div>

    <div class="mb-2 flex flex-wrap items-center gap-3 text-sm" data-count-summary>
        <span>{{ t('inventory::admin.counts_show.skus_skus_counted', { skus: summary.skus }) }}</span>
        <span :class="summary.diff ? 'font-medium text-amber-700' : 'text-slate-500'">{{ t('inventory::admin.counts_show.diff_skus_differ_plus_minus', { diff: summary.diff, plus: summary.plus, minus: summary.minus }) }}</span>
        <label class="flex items-center gap-2"><input v-model="onlyDiff" type="checkbox" /> {{ t('inventory::admin.counts_show.show_only_skus_differ') }}</label>
    </div>
    <DataTable :columns="columns" :rows="shown" :row-key="(line) => line.variant_id" :row-label="(line) => line.sku" :empty-text="t('inventory::admin.counts_show.no_skus_counted_yet')">
        <template #cell-counted="{ row }">
            <input v-if="canManage" :value="row.counted" :class="inputClass" class="w-24 text-right tabular-nums" type="number" min="0" max="1000000" :aria-label="t('inventory::admin.counts_show.count_sku', { sku: row.sku })" @change="setCounted(row, ($event.target as HTMLInputElement).value)" />
            <span v-else class="tabular-nums">{{ row.counted }}</span>
        </template>
        <template #cell-delta="{ row }">
            <span :class="(row.applied_delta ?? row.delta) > 0 ? 'text-emerald-700' : (row.applied_delta ?? row.delta) < 0 ? 'text-red-700' : 'text-slate-400'">
                {{ (row.applied_delta ?? row.delta) > 0 ? '+' : '' }}{{ row.applied_delta ?? row.delta }}
            </span>
        </template>
    </DataTable>
</template>
