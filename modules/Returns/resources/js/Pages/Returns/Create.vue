<script setup lang="ts">
import { t } from '@admin/i18n';
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
        variant: string;
        ordered: number;
        returnable: number;
    }>;
    reasons: string[];
}>();

const formatDate = useDateTime();
const columns = computed(() => [
    { key: 'name', label: t('returns::admin.returns_create.products') },
    { key: 'ordered', label: t('returns::admin.returns_create.purchased'), align: 'right' as const },
    { key: 'returnable', label: t('returns::admin.returns_create.still_returnable'), align: 'right' as const },
    { key: 'quantity', label: t('returns::admin.returns_create.quantity_return'), align: 'right' as const },
    ...(form.exchange ? [{ key: 'exchange', label: t('returns::admin.returns_create.exchange_sku') }] : []),
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
    <Head :title="t('returns::admin.returns_create.create_return_exchange_number', { number: order.number })" />
    <PageHeader
        :title="t('returns::admin.returns_create.create_return_exchange_request_order', { number: order.number })"
        :subtitle="t('returns::admin.returns_create.create_customer_s_behalf_phone')"
    >
        <Link :href="orderUrl" :class="secondaryButton">{{ t('returns::admin.returns_create.order_number', { number: order.number }) }}</Link>
    </PageHeader>

    <p v-if="deadlineText" class="mb-4 text-sm text-slate-600">{{ t('returns::admin.returns_create.return_deadline_under_policy_deadline', { deadline_text: deadlineText }) }}</p>

    <form class="space-y-6" @submit.prevent="submit">
        <DataTable :columns="columns" :rows="lines" :row-label="(line) => line.sku">
            <template #cell-name="{ row }">
                <div class="font-medium">{{ row.name }}</div>
                <div class="text-xs text-slate-500">{{ [row.sku, row.variant].filter(Boolean).join(' · ') }}</div>
            </template>
            <template #cell-quantity="{ row }">
                <input
                    v-model.number="form.lines[row.id]"
                    type="number"
                    min="0"
                    :max="row.returnable"
                    :disabled="row.returnable === 0"
                    :class="[inputClass, 'w-24 text-right']"
                    :aria-label="t('returns::admin.returns_create.quantity_return_sku', { sku: row.sku })"
                />
            </template>
            <template #cell-exchange="{ row }">
                <input v-model="form.exchange_skus[row.id]" :disabled="!form.lines[row.id]" :placeholder="row.sku" :class="[inputClass, 'w-48 font-mono uppercase']" :aria-label="t('returns::admin.returns_create.exchange_sku_sku', { sku: row.sku })" />
                <p v-if="errors[`exchange_skus.${row.id}`]" class="mt-1 text-xs text-red-600">{{ errors[`exchange_skus.${row.id}`] }}</p>
            </template>
        </DataTable>

        <div class="grid max-w-xl gap-4">
            <label class="flex items-start gap-2 text-sm">
                <input v-model="form.exchange" type="checkbox" class="mt-1" />
                <span>
                    <span class="font-medium">{{ t('returns::admin.returns_create.exchange') }}</span> {{ t('returns::admin.returns_create.instead_refund_enter_replacement_sku') }}
                </span>
            </label>
            <FormField :label="t('returns::admin.returns_create.reason')" :error="form.errors.reason_code">
                <Select v-model="form.reason_code" :options="codeOptions(reasons, labels.reason)" />
            </FormField>
            <FormField :label="t('returns::admin.returns_create.note_what_customer_reported')" :error="form.errors.note">
                <textarea v-model="form.note" rows="3" maxlength="500" :class="inputClass" />
            </FormField>
            <p v-if="form.errors.lines" class="text-sm text-red-600">
                {{ form.errors.lines }}
            </p>
            <div>
                <button type="submit" :class="primaryButton" :disabled="form.processing || total === 0">{{ t('returns::admin.returns_create.create_request_total_items', { total }) }}</button>
            </div>
        </div>
    </form>
</template>
