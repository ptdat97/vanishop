<script setup lang="ts">
import DataTable from '@admin/Components/DataTable.vue';
import PageHeader from '@admin/Components/PageHeader.vue';
import Pagination from '@admin/Components/Pagination.vue';
import Select from '@admin/Components/Select.vue';
import StatusBadge from '@admin/Components/StatusBadge.vue';
import { codeOptions } from '@admin/options';
import { inputClass, secondaryButton } from '@admin/styles';
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, reactive } from 'vue';

const props = defineProps<{
    baseUrl: string;
    labels: { log_status: Record<string, string> };
    typeLabels: Record<string, string>;
    filters: { status: string | null; q: string; per_page: number };
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
    pagination: { page: number; last_page: number; from: number | null; to: number | null; total: number; per_page: number };
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
const filters = reactive({ q: props.filters.q, status: props.filters.status ?? '' });
const perPageOptions = [25, 50, 100];
const hasActiveFilters = computed(() => Boolean(filters.q || filters.status));

function search(page?: number, pageSize = props.pagination.per_page): void {
    router.get(props.baseUrl, { q: filters.q || undefined, status: filters.status || undefined, page, per_page: pageSize }, { preserveState: true });
}

function clearFilters(): void {
    filters.q = '';
    filters.status = '';
    search();
}

function changePageSize(pageSize: number): void {
    search(1, pageSize);
}
</script>

<template>
    <Head title="Nhật ký gửi tin" />
    <PageHeader title="Nhật ký gửi tin" :subtitle="`${pagination.total} tin · người nhận đã được che`">
        <Link :href="baseUrl.replace(/logs$/, 'templates')" :class="secondaryButton">Mẫu tin</Link>
    </PageHeader>

    <form class="mb-3 flex flex-wrap items-center gap-2" @submit.prevent="search()">
        <input v-model="filters.q" :class="[inputClass, 'w-72']" placeholder="Người nhận hoặc khoá (vd. order_placed:12)" aria-label="Tìm nhật ký gửi tin" />
        <div class="w-40">
            <Select v-model="filters.status" :options="codeOptions(statuses, labels.log_status)" empty-label="Mọi trạng thái" aria-label="Trạng thái" />
        </div>
        <button type="submit" :class="secondaryButton">Áp dụng</button>
        <button v-if="hasActiveFilters" type="button" :class="secondaryButton" @click="clearFilters">Bỏ bộ lọc</button>
        <span v-if="hasActiveFilters" class="text-xs text-indigo-700" aria-live="polite">Đang lọc</span>
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

    <Pagination :current="pagination.page" :last="pagination.last_page" :from="pagination.from" :to="pagination.to" :total="pagination.total" :per-page="pagination.per_page" :per-page-options="perPageOptions" @change="search($event)" @per-page-change="changePageSize" />
</template>
