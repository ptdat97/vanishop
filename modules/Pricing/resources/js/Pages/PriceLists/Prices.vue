<script setup lang="ts">
import DataTable from '@admin/Components/DataTable.vue';
import PageHeader from '@admin/Components/PageHeader.vue';
import { useMoney } from '@admin/money';
import { inputClass, primaryButton, secondaryButton } from '@admin/styles';
import type { SharedProps } from '@admin/types';
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

interface Row {
    variant_id: number;
    sku: string;
    color_code: string;
    size_code: string;
    status: string;
    amount: number | null;
    compare_at_amount: number | null;
}

const props = defineProps<{
    baseUrl: string;
    priceList: { id: number; code: string; name: string; type: string };
    styleCode: string;
    styleName: string | null;
    rows: Row[];
    canManage: boolean;
}>();

const pricesUrl = computed(() => `${props.baseUrl}/${props.priceList.id}/prices`);
const styleInput = ref(props.styleCode);
const fillAmount = ref<number | null>(null);
const form = useForm({ prices: props.rows.map((row) => ({ variant_id: row.variant_id, amount: row.amount, compare_at_amount: row.compare_at_amount })) });
const errors = computed(() => form.errors as Record<string, string | undefined>);
const money = useMoney();
const symbol = usePage<SharedProps>().props.app.money.symbol;
const columns = [
    { key: 'sku', label: 'SKU', class: 'font-mono text-xs' },
    { key: 'color_code', label: 'Màu' },
    { key: 'size_code', label: 'Size' },
    { key: 'amount', label: `Giá bán (${symbol})` },
    { key: 'compare_at_amount', label: `Giá gốc (${symbol}, tuỳ chọn)` },
];

function load(): void {
    router.get(pricesUrl.value, { style: styleInput.value.trim() || undefined });
}

function fillAll(): void {
    form.prices.forEach((row) => (row.amount = fillAmount.value));
}

function save(): void {
    const payload = form.prices.map((row) => ({
        variant_id: row.variant_id,
        amount: row.amount === null || (row.amount as unknown) === '' ? null : row.amount,
        compare_at_amount: row.compare_at_amount === null || (row.compare_at_amount as unknown) === '' ? null : row.compare_at_amount,
    }));
    form.transform(() => ({ prices: payload })).put(pricesUrl.value, { preserveScroll: true });
}
</script>

<template>
    <Head :title="`Giá · ${priceList.name}`" />
    <PageHeader :title="`Nhập giá: ${priceList.name}`" :subtitle="priceList.code">
        <Link :href="baseUrl" :class="secondaryButton">Danh sách bảng giá</Link>
    </PageHeader>

    <form class="mb-4 flex gap-2" @submit.prevent="load">
        <input v-model="styleInput" :class="inputClass" class="max-w-xs font-mono" placeholder="Mã sản phẩm, ví dụ LM-SH01" />
        <button type="submit" :class="secondaryButton">Tải biến thể</button>
    </form>

    <div v-if="styleCode && !rows.length" class="rounded-lg border border-slate-200 bg-white p-6 text-sm text-slate-500">
        Không tìm thấy biến thể nào của mã <span class="font-mono">{{ styleCode }}</span>. Kiểm tra mã hoặc tạo biến thể trong Catalog.
    </div>

    <div v-if="rows.length" class="space-y-4 rounded-lg border border-slate-200 bg-white p-5">
        <h2 class="font-medium">{{ styleName }} <span class="font-mono text-xs text-slate-400">{{ styleCode }}</span></h2>
        <div v-if="canManage" class="flex items-center gap-2 text-sm">
            <input v-model.number="fillAmount" :class="inputClass" class="max-w-40" type="number" min="0" placeholder="Giá cho tất cả" />
            <button type="button" :class="secondaryButton" @click="fillAll">Áp cho mọi biến thể</button>
        </div>
        <DataTable :columns="columns" :rows="rows" :row-key="(row) => row.variant_id" :row-class="(row) => (row.status !== 'active' ? 'align-top text-slate-400' : 'align-top')">
            <template #cell-amount="{ index }">
                <input v-model.number="form.prices[index].amount" :class="inputClass" type="number" min="0" step="1000" :disabled="!canManage" aria-label="Giá bán" />
                <p v-if="form.prices[index].amount" class="mt-0.5 text-xs text-slate-400">{{ money(form.prices[index].amount ?? 0) }}</p>
                <p v-if="errors[`prices.${index}.amount`]" class="text-xs text-red-600">{{ errors[`prices.${index}.amount`] }}</p>
            </template>
            <template #cell-compare_at_amount="{ index }">
                <input v-model.number="form.prices[index].compare_at_amount" :class="inputClass" type="number" min="0" step="1000" :disabled="!canManage" aria-label="Giá gốc" />
                <p v-if="errors[`prices.${index}.compare_at_amount`]" class="text-xs text-red-600">{{ errors[`prices.${index}.compare_at_amount`] }}</p>
            </template>
        </DataTable>
        <p class="text-xs text-slate-500">Để trống giá bán = gỡ giá của biến thể khỏi bảng giá này. Mọi thay đổi được ghi vào lịch sử giá.</p>
        <button v-if="canManage" type="button" :class="primaryButton" :disabled="form.processing" @click="save">Lưu giá</button>
    </div>
</template>
