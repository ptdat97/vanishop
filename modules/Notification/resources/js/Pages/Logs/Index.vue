<script setup lang="ts">
import DataTable from '@admin/Components/DataTable.vue';
import PageHeader from '@admin/Components/PageHeader.vue';
import Pagination from '@admin/Components/Pagination.vue';
import Select from '@admin/Components/Select.vue';
import StatusBadge from '@admin/Components/StatusBadge.vue';
import { codeOptions } from '@admin/options';
import { inputClass, secondaryButton } from '@admin/styles';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps<{
    baseUrl: string;
    labels: { log_status: Record<string, string> };
    typeLabels: Record<string, string>;
    filters: { status: string | null; q: string };
    statuses: string[];
    logs: Array<{
        id: number;
        type: string;
        channel: string;
        recipient: string;
        status: string;
        attempts: number;
        error: string | null;
        subject: string | null;
        created_at: string;
        sent_at: string | null;
    }>;
    pagination: { page: number; last_page: number; total: number };
}>();

const columns = [
    { key: 'created_at', label: 'Thời gian', class: 'text-xs' },
    { key: 'type', label: 'Loại / kênh', class: 'font-mono text-xs' },
    { key: 'recipient', label: 'Người nhận', class: 'font-mono text-xs' },
    { key: 'status', label: 'Trạng thái' },
    { key: 'error', label: 'Lỗi', class: 'max-w-xs text-xs break-words text-red-700' },
];
const tone = (status: string): 'neutral' | 'info' | 'success' | 'warning' | 'danger' =>
    (({ queued: 'info', sent: 'success', failed: 'danger' }) as const)[status as 'queued'] ?? 'neutral';
const q = ref(props.filters.q);

function search(changes: { status?: string | null; page?: number } = {}): void {
    const next = {
        q: q.value || null,
        status: props.filters.status,
        ...changes,
    };
    router.get(props.baseUrl, Object.fromEntries(Object.entries(next).filter(([, value]) => value)), { preserveState: true });
}
</script>

<template>
    <Head title="Nhật ký gửi tin" />
    <PageHeader title="Nhật ký gửi tin" :subtitle="`${pagination.total} tin · người nhận đã được che`">
        <Link :href="baseUrl.replace(/logs$/, 'templates')" :class="secondaryButton">Mẫu tin</Link>
    </PageHeader>

    <form class="mb-3 flex flex-wrap gap-2" @submit.prevent="search()">
        <input v-model="q" :class="[inputClass, 'w-72']" placeholder="Người nhận hoặc khoá (vd. order_placed:12)" />
        <div class="w-40">
            <Select :model-value="filters.status ?? ''" :options="codeOptions(statuses, labels.log_status)" empty-label="Mọi trạng thái" aria-label="Trạng thái" @change="search({ status: $event || null })" />
        </div>
        <button type="submit" :class="secondaryButton">Lọc</button>
    </form>

    <DataTable :columns="columns" :rows="logs" :row-class="() => 'align-top'" empty-text="Chưa có tin nào.">
        <template #cell-created_at="{ row }">
            {{ row.created_at }}
            <div v-if="row.sent_at" class="text-slate-400">gửi {{ row.sent_at }}</div>
        </template>
        <template #cell-type="{ row }">
            {{ typeLabels[row.type] ?? row.type }}
            <div class="text-xs text-slate-400">{{ row.channel }}</div>
        </template>
        <template #cell-status="{ row }">
            <StatusBadge :label="labels.log_status[row.status] ?? row.status" :tone="tone(row.status)" />
            <span class="ml-1 text-xs text-slate-500">{{ row.attempts }} lần</span>
        </template>
    </DataTable>

    <Pagination :current="pagination.page" :last="pagination.last_page" @change="search({ page: $event })" />
</template>
