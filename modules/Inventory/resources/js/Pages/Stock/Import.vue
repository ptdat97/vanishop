<script setup lang="ts">
import DataTable from '@admin/Components/DataTable.vue';
import PageHeader from '@admin/Components/PageHeader.vue';
import { primaryButton, secondaryButton } from '@admin/styles';
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

type Line = { line: number; sku: string; location: string; current: number | null; new: number | null; status: string };

/** Xem trước nhập tồn CSV: chỉ dòng "Thay đổi" được ghi (như kiểm kê); chưa ghi gì cho tới khi bấm Áp dụng. */
const props = defineProps<{
    preview: { token: string | null; file: string; lines: Line[]; summary: Record<string, number> };
    statusLabels: Record<string, string>;
    urls: { back: string; apply: string | null };
}>();

const columns = [
    { key: 'line', label: 'Dòng' },
    { key: 'sku', label: 'SKU', class: 'font-mono' },
    { key: 'location', label: 'Kho' },
    { key: 'current', label: 'Hiện tại', align: 'right' as const },
    { key: 'new', label: 'Mới', align: 'right' as const },
    { key: 'status', label: 'Kết quả' },
];
const problem = (status: string) => !['change', 'same'].includes(status);
const onlyRelevant = ref(true);
const shown = computed(() => (onlyRelevant.value ? props.preview.lines.filter((line) => line.status !== 'same') : props.preview.lines).slice(0, 1000));
const applying = ref(false);

function apply(): void {
    if (!props.urls.apply) return;
    applying.value = true;
    router.post(props.urls.apply, {}, { onFinish: () => (applying.value = false) });
}
</script>

<template>
    <Head title="Xem trước nhập tồn" />
    <PageHeader :title="`Xem trước: ${preview.file}`" subtitle="Chưa ghi gì. Kiểm tra rồi bấm Áp dụng — dòng có tồn thay đổi sau bước này sẽ được bỏ qua.">
        <Link :href="urls.back" :class="secondaryButton">Quay lại</Link>
        <button v-if="urls.apply" type="button" :class="primaryButton" :disabled="applying" data-apply @click="apply">Áp dụng {{ preview.summary.change ?? 0 }} thay đổi</button>
    </PageHeader>

    <div class="mb-4 flex flex-wrap gap-2 text-sm">
        <span v-for="(count, status) in preview.summary" :key="status" class="rounded-full px-3 py-1" :class="problem(String(status)) ? 'bg-red-50 text-red-700' : 'bg-slate-100'">
            {{ statusLabels[status] ?? status }}: {{ count }}
        </span>
    </div>
    <p v-if="!urls.apply" class="mb-4 text-sm text-slate-600">File không có dòng nào cần thay đổi.</p>
    <label class="mb-2 flex items-center gap-2 text-sm"><input v-model="onlyRelevant" type="checkbox" /> Ẩn dòng không đổi</label>

    <DataTable :columns="columns" :rows="shown" :row-key="(line) => line.line" :row-label="(line) => line.sku" empty-text="Không có dòng nào.">
        <template #cell-location="{ row }">{{ row.location || '—' }}</template>
        <template #cell-current="{ row }">{{ row.current ?? '—' }}</template>
        <template #cell-new="{ row }"><span class="font-medium">{{ row.new ?? '—' }}</span></template>
        <template #cell-status="{ row }">
            <span :class="problem(row.status) ? 'text-red-600' : row.status === 'change' ? 'text-indigo-700' : 'text-slate-500'">{{ statusLabels[row.status] ?? row.status }}</span>
        </template>
    </DataTable>
    <p v-if="preview.lines.length > 1000" class="mt-2 text-xs text-slate-500">Chỉ hiện 1000 dòng đầu.</p>
</template>
