<script setup lang="ts">
import PageHeader from '@admin/Components/PageHeader.vue';
import { inputClass, primaryButton, secondaryButton } from '@admin/styles';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

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
    locations: Array<{ id: number; code: string; name: string; external: boolean }>;
    transfers: Transfer[];
    canManage: boolean;
}>();

const labels: Record<string, string> = { pending: 'Chờ gửi', shipped: 'Đang đi đường', received: 'Đã nhận', cancelled: 'Đã huỷ' };
const errors = computed(() => usePage().props.errors as Record<string, string>);
const internal = computed(() => props.locations.filter((location) => !location.external));

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

function cancel(id: number): void {
    const reason = prompt('Lý do huỷ phiếu chuyển kho:');
    if (reason) router.post(`${props.baseUrl}/${id}/cancel`, { reason }, { preserveScroll: true });
}
</script>

<template>
    <Head title="Chuyển kho" />
    <p class="mb-2 text-sm text-slate-500">Tồn kho</p>
    <PageHeader title="Chuyển kho" subtitle="pending → đã gửi → đã nhận. Khi gửi, tồn kho đi được trừ; hàng đang đi đường không bán được; khi nhận, tồn kho đến được cộng." />

    <p v-if="errors.business" class="mb-4 rounded bg-red-50 p-3 text-sm text-red-700">{{ errors.business }}</p>

    <form v-if="canManage" class="mb-6 grid gap-3 rounded-lg border border-slate-200 bg-white p-5" @submit.prevent="create">
        <div class="grid gap-3 sm:grid-cols-2">
            <label class="text-sm">
                <span class="mb-1 block text-slate-500">Kho đi</span>
                <select v-model.number="form.from_location_id" :class="inputClass">
                    <option v-for="location in internal" :key="location.id" :value="location.id">{{ location.code }} — {{ location.name }}</option>
                </select>
            </label>
            <label class="text-sm">
                <span class="mb-1 block text-slate-500">Kho đến</span>
                <select v-model.number="form.to_location_id" :class="inputClass">
                    <option v-for="location in internal" :key="location.id" :value="location.id">{{ location.code }} — {{ location.name }}</option>
                </select>
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

    <table class="w-full rounded-lg border border-slate-200 bg-white text-sm">
        <thead class="bg-slate-50 text-left text-slate-500">
            <tr>
                <th class="px-4 py-2">Mã</th>
                <th class="px-4 py-2">Lộ trình</th>
                <th class="px-4 py-2">Trạng thái</th>
                <th class="px-4 py-2">Dòng</th>
                <th />
            </tr>
        </thead>
        <tbody>
            <tr v-for="transfer in transfers" :key="transfer.id" class="border-t border-slate-100 align-top">
                <td class="px-4 py-2">
                    <div class="font-mono text-xs">{{ transfer.public_id }}</div>
                    <div class="text-xs text-slate-400">{{ transfer.at }}</div>
                </td>
                <td class="px-4 py-2 font-mono">{{ transfer.from }} → {{ transfer.to }}</td>
                <td class="px-4 py-2">
                    {{ labels[transfer.status] ?? transfer.status }}
                    <div v-if="transfer.cancel_reason" class="text-xs text-red-600">{{ transfer.cancel_reason }}</div>
                </td>
                <td class="px-4 py-2 text-xs">{{ transfer.items }} sản phẩm</td>
                <td class="space-x-2 px-4 py-2 text-right">
                    <template v-if="canManage">
                        <button v-if="transfer.status === 'pending'" type="button" :class="secondaryButton" class="!px-2 !py-1 text-xs" @click="ship(transfer.id)">Gửi hàng</button>
                        <button v-if="transfer.status === 'shipped'" type="button" :class="secondaryButton" class="!px-2 !py-1 text-xs" @click="receive(transfer.id)">Nhận hàng</button>
                        <button v-if="transfer.status === 'pending' || transfer.status === 'shipped'" type="button" class="text-xs text-red-600 hover:underline" @click="cancel(transfer.id)">Huỷ</button>
                    </template>
                </td>
            </tr>
            <tr v-if="!transfers.length">
                <td colspan="5" class="px-4 py-6 text-center text-slate-500">Chưa có phiếu chuyển kho.</td>
            </tr>
        </tbody>
    </table>
</template>
