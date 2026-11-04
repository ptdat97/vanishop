<script setup lang="ts">
import DataTable from '@admin/Components/DataTable.vue';
import FormField from '@admin/Components/FormField.vue';
import PageHeader from '@admin/Components/PageHeader.vue';
import Select from '@admin/Components/Select.vue';
import { useDateTime } from '@admin/datetime';
import { codeOptions } from '@admin/options';
import { inputClass, primaryButton, secondaryButton } from '@admin/styles';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import type { ReturnLabels } from '../../labels';

const props = defineProps<{
    storeUrl: string;
    labels: ReturnLabels;
    orderUrl: string;
    order: { id: number; number: string };
    deadline: string | null;
    lines: Array<{
        id: number;
        sku: string;
        name: string;
        color: string | null;
        size: string;
        ordered: number;
        returnable: number;
    }>;
    reasons: string[];
}>();

const formatDate = useDateTime();
const columns = computed(() => [
    { key: 'name', label: 'Sản phẩm' },
    { key: 'ordered', label: 'Đã mua', align: 'right' as const },
    { key: 'returnable', label: 'Còn trả được', align: 'right' as const },
    { key: 'quantity', label: 'Số lượng trả', align: 'right' as const },
    ...(form.exchange ? [{ key: 'exchange', label: 'Đổi sang SKU' }] : []),
]);
const form = useForm({
    order_id: props.order.id,
    lines: Object.fromEntries(props.lines.map((line) => [line.id, 0])) as Record<number, number>,
    reason_code: props.reasons[0] ?? 'other',
    note: '',
    exchange: false,
    exchange_skus: Object.fromEntries(props.lines.map((line) => [line.id, ''])) as Record<number, string>,
});
const errors = computed(() => usePage().props.errors as Record<string, string>);
const total = computed(() => Object.values(form.lines).reduce((sum, quantity) => sum + Number(quantity || 0), 0));
const deadlineText = computed(() => (props.deadline ? formatDate(props.deadline, 'date') : null));

function submit(): void {
    // Đổi hàng: gửi SKU thay thế (mặc định SKU cũ — nhân viên sửa thành size/màu khách muốn); không đổi thì bỏ trống.
    form.transform((data) => ({ ...data, exchange_skus: data.exchange ? data.exchange_skus : {} })).post(props.storeUrl, { preserveScroll: true });
}
</script>

<template>
    <Head :title="`Tạo đổi/trả — ${order.number}`" />
    <PageHeader
        :title="`Tạo yêu cầu đổi/trả cho đơn ${order.number}`"
        subtitle="Tạo hộ khách (gọi điện, nhắn tin). Áp dụng cùng chính sách đổi/trả như khi khách tự gửi."
    >
        <Link :href="orderUrl" :class="secondaryButton">← Đơn {{ order.number }}</Link>
    </PageHeader>

    <p v-if="errors.business" class="mb-4 rounded bg-red-50 p-3 text-sm text-red-700">
        {{ errors.business }}
    </p>
    <p v-if="deadlineText" class="mb-4 text-sm text-slate-600">Hạn đổi/trả theo chính sách: {{ deadlineText }}</p>

    <form class="space-y-6" @submit.prevent="submit">
        <DataTable :columns="columns" :rows="lines" :row-label="(line) => line.sku">
            <template #cell-name="{ row }">
                <div class="font-medium">{{ row.name }}</div>
                <div class="text-xs text-slate-500">{{ row.sku }} · {{ [row.color, row.size].filter(Boolean).join(' / ') }}</div>
            </template>
            <template #cell-quantity="{ row }">
                <input
                    v-model.number="form.lines[row.id]"
                    type="number"
                    min="0"
                    :max="row.returnable"
                    :disabled="row.returnable === 0"
                    :class="[inputClass, 'w-24 text-right']"
                    :aria-label="`Số lượng trả ${row.sku}`"
                />
            </template>
            <template #cell-exchange="{ row }">
                <input v-model="form.exchange_skus[row.id]" :disabled="!form.lines[row.id]" :placeholder="row.sku" :class="[inputClass, 'w-48 font-mono uppercase']" :aria-label="`Đổi sang SKU cho ${row.sku}`" />
                <p v-if="errors[`exchange_skus.${row.id}`]" class="mt-1 text-xs text-red-600">{{ errors[`exchange_skus.${row.id}`] }}</p>
            </template>
        </DataTable>

        <div class="grid max-w-xl gap-4">
            <label class="flex items-start gap-2 text-sm">
                <input v-model="form.exchange" type="checkbox" class="mt-1" />
                <span>
                    <span class="font-medium">Đổi hàng</span> thay vì hoàn tiền — nhập SKU thay thế cho từng dòng trả. Cùng mẫu (đổi size/màu): không tính chênh;
                    mẫu khác: theo giá hiện tại, khách bù phần thiếu (COD) hoặc được hoàn phần thừa. Đơn thay thế tạo khi nhận hàng trả, miễn phí giao.
                </span>
            </label>
            <FormField label="Lý do" :error="form.errors.reason_code">
                <Select v-model="form.reason_code" :options="codeOptions(reasons, labels.reason)" />
            </FormField>
            <FormField label="Ghi chú (nội dung khách báo)" :error="form.errors.note">
                <textarea v-model="form.note" rows="3" maxlength="500" :class="inputClass" />
            </FormField>
            <p v-if="form.errors.lines" class="text-sm text-red-600">
                {{ form.errors.lines }}
            </p>
            <div>
                <button type="submit" :class="primaryButton" :disabled="form.processing || total === 0">Tạo yêu cầu ({{ total }} sản phẩm)</button>
            </div>
        </div>
    </form>
</template>
