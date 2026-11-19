<script setup lang="ts">
import { t } from '@admin/i18n';
import { useListQuery, type ListSortState } from '@admin/list';
import DataTable from '@admin/Components/DataTable.vue';
import PageHeader from '@admin/Components/PageHeader.vue';
import Pagination from '@admin/Components/Pagination.vue';
import Select from '@admin/Components/Select.vue';
import StatusBadge from '@admin/Components/StatusBadge.vue';
import { codeOptions } from '@admin/options';
import { inputClass, secondaryButton } from '@admin/styles';
import { Head, Link } from '@inertiajs/vue3';
import { computed, reactive } from 'vue';

const props = defineProps<{
    baseUrl: string;
    labels: { log_status: Record<string, string> };
    typeLabels: Record<string, string>;
    filters: { status: string | null; q: string; per_page: number };
    sort: ListSortState | null;
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
    { key: 'created_at', label: t('notification::admin.logs_index.time'), class: 'text-xs', sortable: true },
    { key: 'type', label: t('notification::admin.logs_index.type_channel'), class: 'font-mono text-xs' },
    { key: 'recipient', label: t('notification::admin.logs_index.recipient'), class: 'font-mono text-xs' },
    { key: 'status', label: t('notification::admin.logs_index.status'), sortable: true },
    { key: 'error', label: t('notification::admin.logs_index.error'), class: 'max-w-xs text-xs break-words text-red-700' },
];
const tone = (status: string): 'neutral' | 'info' | 'success' | 'warning' | 'danger' =>
    (({ queued: 'info', sent: 'success', failed: 'danger' }) as const)[status as 'queued'] ?? 'neutral';
const filters = reactive({ q: props.filters.q, status: props.filters.status ?? '' });
const perPageOptions = [25, 50, 100];
const list = useListQuery(() => props.baseUrl);
const hasActiveFilters = computed(() => Boolean(filters.q || filters.status));

function search(page?: number, pageSize = props.pagination.per_page, sort = props.sort): void {
    list.visit({ ...filters, ...list.sortQuery(sort), page, per_page: pageSize });
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
    <Head :title="t('notification::admin.logs_index.notification_log')" />
    <PageHeader :title="t('notification::admin.logs_index.notification_log')" :subtitle="t('notification::admin.logs_index.total_messages_recipients_masked', { total: pagination.total })">
        <Link :href="baseUrl.replace(/logs$/, 'templates')" :class="secondaryButton">{{ t('notification::admin.logs_index.message_templates') }}</Link>
    </PageHeader>

    <form class="mb-3 flex flex-wrap items-center gap-2" @submit.prevent="search()">
        <input v-model="filters.q" :class="[inputClass, 'w-72']" :placeholder="t('notification::admin.logs_index.recipient_key_order_placed12')" :aria-label="t('notification::admin.logs_index.search_notification_log')" />
        <div class="w-40">
            <Select v-model="filters.status" :options="codeOptions(statuses, labels.log_status)" :empty-label="t('notification::admin.logs_index.all_statuses')" :aria-label="t('notification::admin.logs_index.status')" />
        </div>
        <button type="submit" :class="secondaryButton">{{ t('notification::admin.logs_index.apply') }}</button>
        <button v-if="hasActiveFilters" type="button" :class="secondaryButton" @click="clearFilters">{{ t('notification::admin.logs_index.clear_filters') }}</button>
        <span v-if="hasActiveFilters" class="text-xs text-indigo-700" aria-live="polite">{{ t('notification::admin.logs_index.filtering') }}</span>
    </form>

    <DataTable :columns="columns" :rows="logs" :sort="sort" :loading="list.loading.value" @sort="(next) => search(1, pagination.per_page, next)" :row-class="() => 'align-top'" :empty-text="t('notification::admin.logs_index.no_messages_yet')">
        <template #cell-created_at="{ row }">
            {{ row.created_at }}
            <div v-if="row.sent_at" class="text-slate-400">{{ t('notification::admin.logs_index.sent_sent', { sent_at: row.sent_at }) }}</div>
        </template>
        <template #cell-type="{ row }">
            {{ typeLabels[row.type] ?? row.type }}
            <div class="text-xs text-slate-400">{{ row.channel }}</div>
        </template>
        <template #cell-status="{ row }">
            <StatusBadge :label="labels.log_status[row.status] ?? row.status" :tone="tone(row.status)" />
            <span class="ml-1 text-xs text-slate-500">{{ t('notification::admin.logs_index.attempts_attempts', { attempts: row.attempts }) }}</span>
        </template>
    </DataTable>

    <Pagination :current="pagination.page" :last="pagination.last_page" :from="pagination.from" :to="pagination.to" :total="pagination.total" :per-page="pagination.per_page" :per-page-options="perPageOptions" @change="search($event)" @per-page-change="changePageSize" />
</template>
