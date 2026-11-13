<script setup lang="ts">
import DataTable from '@admin/Components/DataTable.vue';
import PageHeader from '@admin/Components/PageHeader.vue';
import Select from '@admin/Components/Select.vue';
import StatusBadge from '@admin/Components/StatusBadge.vue';
import { confirmAction } from '@admin/confirm';
import { recordOptions } from '@admin/options';
import { inputClass, primaryButton, secondaryButton } from '@admin/styles';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import type { WarehouseLabels } from '../../labels';

type TransferLine = { variant_id: number; quantity: number; received_quantity: number | null };
type Transfer = {
    id: number;
    public_id: string;
    from: string;
    to: string;
    status: string;
    note: string | null;
    reference: string | null;
    cancel_reason: string | null;
    items: number;
    at: string | null;
    lines: TransferLine[];
};

const props = defineProps<{
    baseUrl: string;
    labels: WarehouseLabels;
    locations: Array<{ id: number; code: string; name: string; external: boolean }>;
    transfers: Transfer[];
    canManage: boolean;
}>();

const errors = computed(() => usePage().props.errors as Record<string, string>);
const internal = computed(() => props.locations.filter((location) => !location.external));
const locationOptions = computed(() => recordOptions(internal.value, (location) => `${location.code} — ${location.name}`));
const columns = [
    { key: 'public_id', label: 'Mã' },
    { key: 'route', label: 'Lộ trình', class: 'font-mono' },
    { key: 'status', label: 'Trạng thái' },
    { key: 'items', label: 'Dòng', class: 'text-xs' },
    { key: 'actions', label: '', align: 'right' as const, class: 'space-x-2' },
];
const tone = (status: string): 'neutral' | 'info' | 'success' | 'warning' | 'danger' =>
    (({ pending: 'warning', shipped: 'info', received: 'success', cancelled: 'danger' }) as const)[status as 'pending'] ?? 'neutral';

const form = useForm({
    from_location_id: internal.value[0]?.id ?? 0,
    to_location_id: internal.value[1]?.id ?? internal.value[0]?.id ?? 0,
    note: '',
    reference: '',
    lines: [{ sku: '', quantity: 1 }] as Array<{ sku: string; quantity: number }>,
});

function addLine(): void {
    form.lines.push({ sku: '', quantity: 1 });
}

function removeLine(index: number): void {
    if (form.lines.length > 1) form.lines.splice(index, 1);
}

function create(): void {
    form.post(props.baseUrl, { preserveScroll: true, onSuccess: () => form.reset('note', 'reference') });
}

function ship(id: number): void {
    router.post(`${props.baseUrl}/${id}/ship`, {}, { preserveScroll: true });
}

function receive(id: number): void {
    router.post(`${props.baseUrl}/${id}/receive`, {}, { preserveScroll: true });
}

async function cancel(id: number): Promise<void> {
    const reason = await confirmAction({ title: 'Huỷ phiếu chuyển kho?', confirmLabel: 'Huỷ phiếu', tone: 'danger', reason: { label: 'Lý do huỷ phiếu chuyển kho', required: true } });
    if (reason !== false) {
        router.post(`${props.baseUrl}/${id}/cancel`, { reason }, { preserveScroll: true });
    }
}
</script>

<template>
    <Head title="Chuyển kho" />
    <p class="mb-2 text-sm text-slate-500">Tồn kho</p>
    <PageHeader title="Chuyển kho" subtitle="pending → đã gửi → đã nhận. Khi gửi, tồn kho đi được trừ; hàng đang đi đường không bán được; khi nhận, tồn kho đến được cộng." />


    <form v-if="canManage" class="mb-6 grid gap-3 rounded-lg border border-slate-200 bg-white p-5" @submit.prevent="create">
        <div class="grid gap-3 sm:grid-cols-2">
            <label class="text-sm">
                <span class="mb-1 block text-slate-500">Kho đi</span>
                <Select v-model="form.from_location_id" :options="locationOptions" />
            </label>
            <label class="text-sm">
                <span class="mb-1 block text-slate-500">Kho đến</span>
                <Select v-model="form.to_location_id" :options="locationOptions" />
            </label>
        </div>
        <p v-if="!internal.length" class="text-sm text-amber-700">Chưa có kho nào do VaniShop quản lý tồn để chuyển.</p>

        <div class="grid gap-2">
            <div v-for="(line, index) in form.lines" :key="index" class="flex gap-2">
                <input v-model="line.sku" :class="inputClass" placeholder="SKU, vd. LM-DR01-M" />
                <input v-model.number="line.quantity" :class="inputClass" type="number" min="1" class="max-w-28" />
                <button type="button" :class="secondaryButton" @click="removeLine(index)">Xoá</button>
            </div>
            <p v-for="(error, key) in form.errors" :key="key" class="text-sm text-red-600">{{ error }}</p>
            <div class="flex gap-2">
                <button type="button" :class="secondaryButton" @click="addLine">+ Dòng</button>
                <input v-model="form.reference" :class="inputClass" placeholder="Tham chiếu (tuỳ chọn)" />
                <input v-model="form.note" :class="inputClass" placeholder="Ghi chú (tuỳ chọn)" />
                <button type="submit" :class="primaryButton" :disabled="form.processing">Tạo phiếu</button>
            </div>
        </div>
    </form>

    <DataTable :columns="columns" :rows="transfers" :row-class="() => 'align-top'" :row-label="(transfer) => transfer.public_id" empty-text="Chưa có phiếu chuyển kho.">
        <template #cell-public_id="{ row }">
            <div class="font-mono text-xs">{{ row.public_id }}</div>
            <div class="text-xs text-slate-400">{{ row.at }}</div>
        </template>
        <template #cell-route="{ row }">{{ row.from }} → {{ row.to }}</template>
        <template #cell-status="{ row }">
            <StatusBadge :label="labels.transfer_status[row.status] ?? row.status" :tone="tone(row.status)" />
            <div v-if="row.cancel_reason" class="mt-1 text-xs text-red-600">{{ row.cancel_reason }}</div>
        </template>
        <template #cell-items="{ row }">{{ row.items }} sản phẩm</template>
        <template #cell-actions="{ row }">
            <template v-if="canManage">
                <button v-if="row.status === 'pending'" type="button" :class="secondaryButton" class="!px-2 !py-1 text-xs" @click="ship(row.id)">Gửi hàng</button>
                <button v-if="row.status === 'shipped'" type="button" :class="secondaryButton" class="!px-2 !py-1 text-xs" @click="receive(row.id)">Nhận hàng</button>
                <button v-if="row.status === 'pending' || row.status === 'shipped'" type="button" class="text-xs text-red-600 hover:underline" @click="cancel(row.id)">Huỷ</button>
            </template>
        </template>
    </DataTable>
</template>
