<script setup lang="ts">
import { t } from '@admin/i18n';
import { useListQuery, type ListSortState } from '@admin/list';
import DataTable from '@admin/Components/DataTable.vue';
import PageHeader from '@admin/Components/PageHeader.vue';
import Select from '@admin/Components/Select.vue';
import StatusBadge from '@admin/Components/StatusBadge.vue';
import { confirmAction } from '@admin/confirm';
import { codeOptions } from '@admin/options';
import { inputClass, secondaryButton } from '@admin/styles';
import { Head, router } from '@inertiajs/vue3';
import { ref } from 'vue';

type Message = {
    id: number;
    target: string;
    type: string;
    reference: string;
    status: string;
    attempts: number;
    last_error: string | null;
    at: string | null;
    payload: Record<string, unknown>;
    correlation_id: string | null;
};

const props = defineProps<{
    baseUrl: string;
    labels: { message_status: Record<string, string> };
    filters: {
        box: 'outbox' | 'inbox';
        status: string | null;
        target: string | null;
    };
    sort: ListSortState | null;
    statuses: string[];
    summary: {
        outbox: Record<string, number>;
        inbox: Record<string, number>;
        oldest_pending_minutes: number | null;
        by_target: Array<{ target: string; status: string; total: number }>;
        connectors: string[];
    };
    messages: Message[];
    clients: Array<{
        id: number;
        code: string;
        name: string;
        status: string;
        scopes: string[];
        rate_limit: number;
        active_keys: number;
        subscriptions: Array<{
            id: number;
            url: string;
            event_types: string[];
            status: string;
            failing_since: string | null;
        }>;
    }>;
    can: { replay: boolean; manage: boolean };
}>();

const opened = ref<number | null>(null);
const messageColumns = [
    { key: 'id', label: '#', class: 'font-mono text-xs', sortable: true },
    { key: 'target', label: 'Target', class: 'font-mono text-xs' },
    { key: 'type', label: t('integration::admin.health_index.type'), class: 'font-mono text-xs' },
    { key: 'reference', label: t('integration::admin.health_index.reference'), class: 'font-mono text-xs' },
    { key: 'status', label: t('integration::admin.health_index.status') },
    { key: 'attempts', label: t('integration::admin.health_index.attempts'), align: 'right' as const, class: 'text-xs', sortable: true },
    { key: 'last_error', label: t('integration::admin.health_index.last_error'), class: 'max-w-xs text-xs break-words text-red-700' },
    { key: 'actions', label: '', align: 'right' as const, class: 'whitespace-nowrap' },
];
const clientColumns = [
    { key: 'name', label: 'Client' },
    { key: 'scopes', label: 'Scope', class: 'font-mono text-xs' },
    { key: 'active_keys', label: 'Key', class: 'text-xs' },
    { key: 'subscriptions', label: 'Webhook', class: 'text-xs' },
];

function statusTone(status: string): 'neutral' | 'info' | 'success' | 'warning' | 'danger' {
    if (['failed', 'dead'].includes(status)) return 'danger';
    if (['sent', 'processed'].includes(status)) return 'success';
    if (['pending', 'received', 'processing'].includes(status)) return 'info';

    return 'neutral';
}

const list = useListQuery(() => props.baseUrl);

function filter(changes: Partial<typeof props.filters>, sort = props.sort): void {
    list.visit({ ...props.filters, ...changes, ...list.sortQuery(sort) });
}

async function replay(ids: number[] | null): Promise<void> {
    const label = ids ? `${ids.length} message` : t('integration::admin.health_index.every_failed_message_filter');
    if ((await confirmAction({ title: t('integration::admin.health_index.resend_label', { label }), message: t('integration::admin.health_index.messages_keep_their_id_so'), confirmLabel: t('integration::admin.health_index.resend') })) === false) return;
    const payload: {
        box: string;
        ids?: number[];
        status?: string;
        target?: string;
    } = { box: props.filters.box };
    if (ids) payload.ids = ids;
    if (!ids && props.filters.status) payload.status = props.filters.status;
    if (!ids && props.filters.target) payload.target = props.filters.target;
    router.post(`${props.baseUrl}/replay`, payload, { preserveScroll: true });
}

function resume(id: number): void {
    router.post(`${props.baseUrl}/subscriptions/${id}/resume`, {}, { preserveScroll: true });
}
</script>

<template>
    <Head :title="t('integration::admin.health_index.integrations')" />
    <PageHeader :title="t('integration::admin.health_index.integrations')" :subtitle="t('integration::admin.health_index.outbox_inbox_failed_messages_resending')" />

    <div class="mb-6 grid gap-4 md:grid-cols-3">
        <div class="rounded-lg border border-slate-200 bg-white p-4">
            <div class="text-sm text-slate-500">{{ t('integration::admin.health_index.outbox_pending') }}</div>
            <div class="text-2xl font-semibold">
                {{ summary.outbox.pending ?? 0 }}
            </div>
            <div class="text-xs text-slate-400">
                {{ t('integration::admin.health_index.oldest', { age: summary.oldest_pending_minutes === null ? '—' : t('integration::admin.health_index.minutes', { count: summary.oldest_pending_minutes }) }) }}
            </div>
        </div>
        <div class="rounded-lg border border-slate-200 bg-white p-4">
            <div class="text-sm text-slate-500">{{ t('integration::admin.health_index.outbox_failed_exhausted') }}</div>
            <div class="text-2xl font-semibold" :class="(summary.outbox.failed ?? 0) + (summary.outbox.dead ?? 0) > 0 ? 'text-red-600' : ''">
                {{ summary.outbox.failed ?? 0 }} /
                {{ summary.outbox.dead ?? 0 }}
            </div>
        </div>
        <div class="rounded-lg border border-slate-200 bg-white p-4">
            <div class="text-sm text-slate-500">{{ t('integration::admin.health_index.inbox_failed_exhausted') }}</div>
            <div class="text-2xl font-semibold" :class="(summary.inbox.failed ?? 0) + (summary.inbox.dead ?? 0) > 0 ? 'text-red-600' : ''">
                {{ summary.inbox.failed ?? 0 }} / {{ summary.inbox.dead ?? 0 }}
            </div>
            <div class="text-xs text-slate-400">{{ t('integration::admin.health_index.enabled_connectors_connectors', { connectors: summary.connectors.join(', ') || '—' }) }}</div>
        </div>
    </div>

    <div class="mb-3 flex flex-wrap items-center gap-2">
        <button
            v-for="box in ['outbox', 'inbox'] as const"
            :key="box"
            :class="[secondaryButton, filters.box === box ? 'border-indigo-500 text-red-700' : '']"
            @click="filter({ box, target: null })"
        >
            {{ box === 'outbox' ? t('integration::admin.health_index.outgoing') : t('integration::admin.health_index.incoming') }}
        </button>
        <div class="w-48">
            <Select :model-value="filters.status ?? ''" :options="codeOptions(statuses, labels.message_status)" :empty-label="t('integration::admin.health_index.failed_exhausted')" :aria-label="t('integration::admin.health_index.status')" @change="filter({ status: $event || null })" />
        </div>
        <input
            :class="[inputClass, 'w-48']"
            :value="filters.target ?? ''"
            placeholder="Target / system"
            @change="
                filter({
                    target: ($event.target as HTMLInputElement).value || null,
                })
            "
        />
        <button v-if="can.replay && messages.length" :class="secondaryButton" @click="replay(null)">{{ t('integration::admin.health_index.resend_everything_filter') }}</button>
    </div>

    <div class="mb-8">
        <DataTable :columns="messageColumns" :rows="messages" :sort="sort" :loading="list.loading.value" @sort="(next) => filter({}, next)" :row-class="() => 'align-top'" :expanded="(message) => opened === message.id" :empty-text="t('integration::admin.health_index.no_messages_match_filter')">
            <template #cell-id="{ row }">
                {{ row.id }}
                <div class="text-slate-400">{{ row.at }}</div>
            </template>
            <template #cell-status="{ row }">
                <StatusBadge :label="labels.message_status[row.status] ?? row.status" :tone="statusTone(row.status)" />
            </template>
            <template #cell-actions="{ row }">
                <button class="mr-3 text-indigo-600 hover:underline" @click="opened = opened === row.id ? null : row.id">Payload</button>
                <button v-if="can.replay && ['failed', 'dead'].includes(row.status)" class="text-indigo-600 hover:underline" @click="replay([row.id])">{{ t('integration::admin.health_index.resend') }}</button>
            </template>
            <template #expanded="{ row }">
                <div class="mb-1 text-xs text-slate-500">Correlation ID: {{ row.correlation_id ?? '—' }}</div>
                <pre class="max-h-80 overflow-auto text-xs">{{ JSON.stringify(row.payload, null, 2) }}</pre>
            </template>
        </DataTable>
    </div>

    <h2 class="mb-2 text-lg font-semibold">Integration Client</h2>
    <p class="mb-3 text-sm text-slate-500">
        {{ t('integration::admin.health_index.create_clients_issue_keys_register') }}
        <code>php artisan vani:integration:client</code> {{ t('integration::admin.health_index.text') }} <code>vani:integration:webhook</code>.
    </p>
    <DataTable :columns="clientColumns" :rows="clients" :row-class="() => 'align-top'" :empty-text="t('integration::admin.health_index.no_clients_yet')">
        <template #cell-name="{ row }">
            <div class="font-medium">{{ row.name }}</div>
            <div class="font-mono text-xs text-slate-400">{{ row.code }}<span v-if="row.status !== 'active'"> {{ t('integration::admin.health_index.paused') }}</span></div>
        </template>
        <template #cell-scopes="{ row }">{{ row.scopes.join(', ') }}</template>
        <template #cell-active_keys="{ row }">{{ t('integration::admin.health_index.active_keys_active', { active_keys: row.active_keys }) }}</template>
        <template #cell-subscriptions="{ row }">
            <div v-for="subscription in row.subscriptions" :key="subscription.id" class="mb-1">
                <span class="font-mono">{{ subscription.url }}</span>
                <span class="text-slate-400"> · {{ subscription.event_types.join(', ') }}</span>
                <span v-if="subscription.status === 'paused'" class="text-red-600"> {{ t('integration::admin.health_index.paused') }}</span>
                <span v-else-if="subscription.failing_since" class="text-amber-600"> {{ t('integration::admin.health_index.failing_since_failing_since', { failing_since: subscription.failing_since }) }}</span>
                <button v-if="can.manage && subscription.status === 'paused'" class="ml-2 text-indigo-600 hover:underline" @click="resume(subscription.id)">{{ t('integration::admin.health_index.resume') }}</button>
            </div>
            <span v-if="!row.subscriptions.length" class="text-slate-400">—</span>
        </template>
    </DataTable>
</template>
