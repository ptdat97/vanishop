<script setup lang="ts">
import DataTable from '@admin/Components/DataTable.vue';
import PageHeader from '@admin/Components/PageHeader.vue';
import { primaryButton, secondaryButton } from '@admin/styles';
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import CatalogTabs from '../../Components/CatalogTabs.vue';
import type { NavItem } from '../../types';

type Product = { code: string; lines: number[]; action: 'create' | 'update' | 'error'; name: string | null; new_skus: number; updated_skus: number; notes: string[]; errors: string[] };

/** Xem trước nhập sản phẩm (A4): chưa ghi gì cho tới khi bấm Áp dụng; sản phẩm lỗi được bỏ qua. */
const props = defineProps<{
    nav: NavItem[];
    preview: { token: string | null; file: string; products: Product[]; summary: { create: number; update: number; error: number } };
    urls: { back: string; apply: string | null };
    actionLabels: Record<Product['action'], string>;
}>();

const columns = [
    { key: 'code', label: 'Mã sản phẩm', class: 'font-mono' },
    { key: 'name', label: 'Tên' },
    { key: 'lines', label: 'Dòng' },
    { key: 'skus', label: 'SKU' },
    { key: 'action', label: 'Kết quả' },
];
const onlyErrors = ref(false);
const shown = computed(() => (onlyErrors.value ? props.preview.products.filter((product) => product.action === 'error') : props.preview.products).slice(0, 1000));
const applying = ref(false);

function apply(): void {
    if (!props.urls.apply) return;
    applying.value = true;
    router.post(props.urls.apply, {}, { onFinish: () => (applying.value = false) });
}

function lineRange(lines: number[]): string {
    return lines.length === 1 ? String(lines[0]) : `${lines[0]}–${lines[lines.length - 1]}`;
}
</script>

<template>
    <Head title="Xem trước nhập sản phẩm" />
    <CatalogTabs :nav="nav" active="products" />
    <PageHeader :title="`Xem trước: ${preview.file}`" subtitle="Chưa ghi gì. Kiểm tra rồi bấm Áp dụng — sản phẩm có lỗi sẽ được bỏ qua.">
        <Link :href="urls.back" :class="secondaryButton">Quay lại</Link>
        <button v-if="urls.apply" type="button" :class="primaryButton" :disabled="applying" data-apply @click="apply">
            Áp dụng: tạo {{ preview.summary.create }}, cập nhật {{ preview.summary.update }}
        </button>
    </PageHeader>

    <div class="mb-4 flex flex-wrap gap-2 text-sm">
        <span class="rounded-full bg-emerald-50 px-3 py-1 text-emerald-800">Tạo mới: {{ preview.summary.create }}</span>
        <span class="rounded-full bg-slate-100 px-3 py-1">Cập nhật: {{ preview.summary.update }}</span>
        <span class="rounded-full px-3 py-1" :class="preview.summary.error ? 'bg-red-50 text-red-700' : 'bg-slate-100'">Lỗi: {{ preview.summary.error }}</span>
    </div>
    <p v-if="!urls.apply" class="mb-4 text-sm text-slate-600">Không có sản phẩm nào ghi được — sửa lỗi trong file rồi tải lên lại.</p>
    <label class="mb-2 flex items-center gap-2 text-sm"><input v-model="onlyErrors" type="checkbox" /> Chỉ hiện sản phẩm lỗi</label>

    <DataTable :columns="columns" :rows="shown" :row-key="(product) => product.code" :row-label="(product) => product.code" empty-text="File không có dòng sản phẩm nào.">
        <template #cell-name="{ row }">{{ row.name ?? '—' }}</template>
        <template #cell-lines="{ row }">{{ lineRange(row.lines) }}</template>
        <template #cell-skus="{ row }">
            <span v-if="row.new_skus">+{{ row.new_skus }} mới</span>
            <span v-if="row.new_skus && row.updated_skus">, </span>
            <span v-if="row.updated_skus">{{ row.updated_skus }} cập nhật</span>
        </template>
        <template #cell-action="{ row }">
            <span :class="row.action === 'error' ? 'text-red-600' : row.action === 'create' ? 'text-emerald-700' : 'text-indigo-700'">{{ actionLabels[row.action] }}</span>
            <ul v-if="row.errors.length || row.notes.length" class="mt-1 list-inside list-disc text-xs">
                <li v-for="error in row.errors" :key="error" class="text-red-600">{{ error }}</li>
                <li v-for="note in row.notes" :key="note" class="text-slate-500">{{ note }}</li>
            </ul>
        </template>
    </DataTable>
    <p v-if="preview.products.length > 1000" class="mt-2 text-xs text-slate-500">Chỉ hiện 1000 sản phẩm đầu.</p>
</template>
