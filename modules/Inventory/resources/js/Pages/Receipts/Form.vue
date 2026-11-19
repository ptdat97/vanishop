<script setup lang="ts">
import { t } from '@admin/i18n';
import DataTable from '@admin/Components/DataTable.vue';
import FileInput from '@admin/Components/FileInput.vue';
import PageHeader from '@admin/Components/PageHeader.vue';
import Select from '@admin/Components/Select.vue';
import StatusBadge from '@admin/Components/StatusBadge.vue';
import { confirmAction } from '@admin/confirm';
import { HttpError, postJson } from '@admin/http';
import { recordOptions } from '@admin/options';
import { dangerButton, inputClass, primaryButton, secondaryButton } from '@admin/styles';
import { useAdminForm } from '@admin/forms';
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

type Line = { variant_id: number; sku: string; name: string; quantity: number };

/**
 * Phiếu nhập nhanh (A6): gõ hoặc quét SKU rồi Enter (quét lại = cộng thêm 1), dán nhiều dòng "SKU số lượng", hoặc tải file
 * (.xlsx/.csv: SKU, số lượng). "Nhập kho" cộng tồn một lần; phiếu đã nhập chỉ xem.
 */
const props = defineProps<{
    receipt: { id: number; number: string; lock_version: number; location_id: number; location: string | null; supplier: string | null; note: string | null; status: string; received_at: string | null; lines: Line[] } | null;
    locations: Array<{ id: number; label: string }>;
    statusLabels: Record<string, string>;
    canManage: boolean;
    urls: { index: string; save: string; parse: string; receive: string | null; cancel: string | null };
}>();

const editable = computed(() => props.canManage && (props.receipt === null || props.receipt.status === 'draft'));
const { form, save: submit } = useAdminForm(
    {
        location_id: props.receipt?.location_id ?? props.locations[0]?.id ?? null,
        supplier: props.receipt?.supplier ?? '',
        note: props.receipt?.note ?? '',
        lines: (props.receipt?.lines ?? []) as Line[],
        receive: false,
        lock_version: props.receipt?.lock_version ?? null,
    },
    { lockVersion: () => props.receipt?.lock_version },
);
const lineColumns = computed(() => [
    { key: 'sku', label: 'SKU', class: 'font-mono text-xs' },
    { key: 'name', label: t('inventory::admin.receipts_form.products') },
    { key: 'quantity', label: t('inventory::admin.receipts_form.quantity'), align: 'right' as const },
    ...(editable.value ? [{ key: 'remove', label: '' }] : []),
]);
const scan = ref('');
const paste = ref('');
const file = ref<File | null>(null);
const parseErrors = ref<string[]>([]);
const parsing = ref(false);
const units = computed(() => form.lines.reduce((sum, line) => sum + Number(line.quantity || 0), 0));

function merge(lines: Line[]): void {
    for (const line of lines) {
        const existing = form.lines.find((item) => item.variant_id === line.variant_id);
        if (existing) existing.quantity += line.quantity;
        else form.lines.push({ ...line });
    }
}

async function parse(body: FormData | Record<string, unknown>): Promise<void> {
    parsing.value = true;
    parseErrors.value = [];
    try {
        const result = await postJson<{ lines: Line[]; errors: string[] }>(props.urls.parse, body);
        merge(result.lines);
        parseErrors.value = result.errors;
    } catch (error) {
        parseErrors.value = [error instanceof HttpError ? error.message : t('inventory::admin.receipts_form.could_not_read_data')];
    } finally {
        parsing.value = false;
    }
}

async function addScan(): Promise<void> {
    // Lấy mã và xoá ô ngay: máy quét gửi mã kế tiếp trước khi request trước xong — không được xoá mất mã đó.
    const text = scan.value.trim();
    scan.value = '';
    if (text !== '') await parse({ text });
}

async function addPaste(): Promise<void> {
    await parse({ text: paste.value });
    paste.value = '';
}

async function addFile(): Promise<void> {
    if (!file.value) return;
    const body = new FormData();
    body.append('file', file.value);
    await parse(body);
    file.value = null;
}

function save(receive: boolean): void {
    form.receive = receive;
    submit(props.receipt ? 'put' : 'post', props.urls.save);
}

async function saveAndReceive(): Promise<void> {
    if ((await confirmAction({ title: t('inventory::admin.receipts_form.receive_into_stock'), message: t('inventory::admin.receipts_form.adds_units_items_count_skus', { units: units.value, count: form.lines.length }), confirmLabel: t('inventory::admin.receipts_form.receive_into_stock_2') })) !== false) {
        save(true);
    }
}

async function cancel(): Promise<void> {
    if (props.urls.cancel && (await confirmAction({ title: t('inventory::admin.receipts_form.cancel_goods_receipt'), confirmLabel: t('inventory::admin.receipts_form.cancel_transfer'), tone: 'danger' })) !== false) {
        router.post(props.urls.cancel);
    }
}
</script>

<template>
    <Head :title="receipt ? t('inventory::admin.receipts_form.goods_receipt_number', { number: receipt.number }) : t('inventory::admin.receipts_form.create_goods_receipt')" />
    <PageHeader :title="receipt ? t('inventory::admin.receipts_form.goods_receipt_number', { number: receipt.number }) : t('inventory::admin.receipts_form.create_goods_receipt')" :subtitle="receipt?.received_at ? t('inventory::admin.receipts_form.received_received', { received_at: receipt.received_at }) : undefined">
        <StatusBadge v-if="receipt" :label="statusLabels[receipt.status] ?? receipt.status" :tone="receipt.status === 'received' ? 'success' : receipt.status === 'draft' ? 'warning' : 'neutral'" />
        <Link :href="urls.index" :class="secondaryButton">{{ t('inventory::admin.receipts_form.receipt_list') }}</Link>
        <button v-if="editable && receipt" type="button" :class="dangerButton" data-cancel @click="cancel">{{ t('inventory::admin.receipts_form.cancel_transfer') }}</button>
    </PageHeader>

    <div class="grid gap-6 lg:grid-cols-3">
        <section class="space-y-4 lg:col-span-2">
            <div v-if="editable" class="space-y-3 rounded-lg border border-slate-200 bg-white p-4 text-sm">
                <form class="flex gap-2" @submit.prevent="addScan">
                    <input v-model="scan" :class="inputClass" autofocus :placeholder="t('inventory::admin.receipts_form.type_scan_sku_then_enter')" :aria-label="t('inventory::admin.receipts_form.scan_sku')" data-scan />
                    <!-- Không disable khi đang đọc: trình duyệt bỏ Enter nếu nút gửi mặc định bị disable — máy quét gửi liên tục. -->
                    <button type="submit" :class="secondaryButton">{{ t('inventory::admin.receipts_form.add') }}</button>
                </form>
                <details>
                    <summary class="cursor-pointer text-slate-600">{{ t('inventory::admin.receipts_form.paste_multiple_lines_upload_file') }}</summary>
                    <div class="mt-3 grid gap-3">
                        <textarea v-model="paste" :class="inputClass" rows="5" :placeholder="t('inventory::admin.receipts_form.each_line_sku_quantity')" :aria-label="t('inventory::admin.receipts_form.paste_multiple_lines')"></textarea>
                        <div><button type="button" :class="secondaryButton" :disabled="parsing || !paste.trim()" @click="addPaste">{{ t('inventory::admin.receipts_form.add_pasted_lines') }}</button></div>
                        <FileInput v-model="file" accept=".xlsx,.csv" :max-kb="2048" :label="t('inventory::admin.receipts_form.excel_csv_file_sku_quantity')" />
                        <div><button type="button" :class="secondaryButton" :disabled="parsing || !file" @click="addFile">{{ t('inventory::admin.receipts_form.add_from_file') }}</button></div>
                    </div>
                </details>
                <ul v-if="parseErrors.length" class="list-inside list-disc text-red-600" role="alert">
                    <li v-for="error in parseErrors" :key="error">{{ error }}</li>
                </ul>
            </div>

            <DataTable :columns="lineColumns" :rows="form.lines" :row-key="(line) => line.variant_id" :row-label="(line) => line.sku" :empty-text="t('inventory::admin.receipts_form.no_skus_yet')" data-receipt-lines>
                <template #cell-quantity="{ row }">
                    <input v-if="editable" v-model.number="row.quantity" :class="inputClass" class="w-24 text-right tabular-nums" type="number" min="1" max="1000000" required :aria-label="t('inventory::admin.receipts_form.quantity_sku', { sku: row.sku })" />
                    <span v-else class="tabular-nums">{{ row.quantity }}</span>
                </template>
                <template #cell-remove="{ row }">
                    <button type="button" class="text-red-600 hover:underline" @click="form.lines.splice(form.lines.indexOf(row), 1)">{{ t('inventory::admin.receipts_form.remove') }}</button>
                </template>
            </DataTable>
            <p v-if="form.lines.length" class="text-right text-sm font-medium" data-receipt-total>{{ t('inventory::admin.receipts_form.count_skus_units_items', { count: form.lines.length, units }) }}</p>
            <p v-for="(error, key) in form.errors" :key="key" class="text-sm text-red-600">{{ error }}</p>
        </section>

        <aside class="space-y-3 rounded-lg border border-slate-200 bg-white p-4 text-sm">
            <label class="block">
                <span class="mb-1 block font-medium">{{ t('inventory::admin.receipts_form.receiving_warehouse') }}</span>
                <Select v-if="editable" v-model="form.location_id" :options="recordOptions(locations)" :aria-label="t('inventory::admin.receipts_form.receiving_warehouse')" />
                <span v-else>{{ receipt?.location ?? '—' }}</span>
            </label>
            <label class="block">
                <span class="mb-1 block font-medium">{{ t('inventory::admin.receipts_form.supplier') }}</span>
                <input v-if="editable" v-model="form.supplier" :class="inputClass" maxlength="120" />
                <span v-else>{{ receipt?.supplier ?? '—' }}</span>
            </label>
            <label class="block">
                <span class="mb-1 block font-medium">{{ t('inventory::admin.receipts_form.note') }}</span>
                <textarea v-if="editable" v-model="form.note" :class="inputClass" rows="3" maxlength="500"></textarea>
                <span v-else>{{ receipt?.note ?? '—' }}</span>
            </label>
            <div v-if="editable" class="flex flex-wrap gap-2 pt-2">
                <button type="button" :class="secondaryButton" :disabled="form.processing || !form.lines.length" data-save @click="save(false)">{{ t('inventory::admin.receipts_form.save_draft') }}</button>
                <button type="button" :class="primaryButton" :disabled="form.processing || !form.lines.length" data-receive @click="saveAndReceive">{{ t('inventory::admin.receipts_form.save_receive') }}</button>
            </div>
        </aside>
    </div>
</template>
