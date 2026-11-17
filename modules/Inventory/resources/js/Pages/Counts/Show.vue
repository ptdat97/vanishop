<script setup lang="ts">
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
    { key: 'name', label: 'Sản phẩm' },
    { key: 'counted', label: 'Số đếm', align: 'right' as const },
    { key: 'on_hand_at_count', label: 'Tồn lúc đếm', align: 'right' as const, class: 'tabular-nums' },
    { key: 'delta', label: props.count.status === 'applied' ? 'Đã ghi' : 'Chênh', align: 'right' as const, class: 'tabular-nums' },
    { key: 'new_on_hand', label: 'Tồn sau áp dụng', align: 'right' as const, class: 'tabular-nums' },
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
            errors.value = [error instanceof HttpError ? error.message : 'Không ghi được số đếm.', ...errors.value].slice(0, 20);
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
    const message = `${summary.value.skus} SKU đã đếm, ${summary.value.diff} SKU lệch (+${summary.value.plus} / ${summary.value.minus}). SKU chưa đếm không đổi.`;
    if ((await confirmAction({ title: `Áp dụng kiểm kê ${props.count.number}?`, message, confirmLabel: 'Áp dụng' })) !== false) {
        router.post(props.urls.apply);
    }
}

async function cancel(): Promise<void> {
    if ((await confirmAction({ title: 'Huỷ phiên kiểm kê?', message: 'Số đếm không được ghi vào tồn.', confirmLabel: 'Huỷ phiên', tone: 'danger' })) !== false) {
        router.post(props.urls.cancel);
    }
}
</script>

<template>
    <Head :title="`Kiểm kê ${count.number}`" />
    <PageHeader :title="`Kiểm kê ${count.number}`" :subtitle="[count.location, count.note, count.applied_at ? `áp dụng ${count.applied_at}` : null].filter(Boolean).join(' · ')">
        <StatusBadge :label="statusLabels[count.status] ?? count.status" :tone="count.status === 'applied' ? 'success' : count.status === 'counting' ? 'warning' : 'neutral'" />
        <Link :href="urls.index" :class="secondaryButton">Các phiên</Link>
        <button v-if="canManage" type="button" :class="dangerButton" data-cancel @click="cancel">Huỷ phiên</button>
        <button v-if="canManage" type="button" :class="primaryButton" :disabled="!lines.length" data-apply @click="apply">Áp dụng</button>
    </PageHeader>

    <div v-if="canManage" class="mb-4 space-y-3 rounded-lg border border-slate-200 bg-white p-4 text-sm">
        <form class="flex gap-2" @submit.prevent="addScan">
            <input v-model="scan" :class="inputClass" autofocus placeholder="Quét hoặc gõ SKU rồi Enter (mỗi lần +1; SKU 12 = +12)" aria-label="Quét SKU" data-scan />
            <button type="submit" :class="secondaryButton">Thêm</button>
        </form>
        <details>
            <summary class="cursor-pointer text-slate-600">Tải file số đếm</summary>
            <div class="mt-3 flex flex-wrap items-center gap-2">
                <FileInput v-model="file" accept=".xlsx,.csv" :max-kb="2048" label="File Excel/CSV: cột SKU, số lượng (cộng vào số đã đếm)" />
                <button type="button" :class="secondaryButton" :disabled="!file" @click="addFile">Thêm từ file</button>
            </div>
        </details>
        <ul v-if="errors.length" class="list-inside list-disc text-red-600" role="alert">
            <li v-for="error in errors" :key="error">{{ error }}</li>
        </ul>
    </div>

    <div class="mb-2 flex flex-wrap items-center gap-3 text-sm" data-count-summary>
        <span>{{ summary.skus }} SKU đã đếm</span>
        <span :class="summary.diff ? 'font-medium text-amber-700' : 'text-slate-500'">{{ summary.diff }} SKU lệch (+{{ summary.plus }} / {{ summary.minus }})</span>
        <label class="flex items-center gap-2"><input v-model="onlyDiff" type="checkbox" /> Chỉ hiện SKU lệch</label>
    </div>
    <DataTable :columns="columns" :rows="shown" :row-key="(line) => line.variant_id" :row-label="(line) => line.sku" empty-text="Chưa đếm SKU nào.">
        <template #cell-counted="{ row }">
            <input v-if="canManage" :value="row.counted" :class="inputClass" class="w-24 text-right tabular-nums" type="number" min="0" max="1000000" :aria-label="`Số đếm ${row.sku}`" @change="setCounted(row, ($event.target as HTMLInputElement).value)" />
            <span v-else class="tabular-nums">{{ row.counted }}</span>
        </template>
        <template #cell-delta="{ row }">
            <span :class="(row.applied_delta ?? row.delta) > 0 ? 'text-emerald-700' : (row.applied_delta ?? row.delta) < 0 ? 'text-red-700' : 'text-slate-400'">
                {{ (row.applied_delta ?? row.delta) > 0 ? '+' : '' }}{{ row.applied_delta ?? row.delta }}
            </span>
        </template>
    </DataTable>
</template>
