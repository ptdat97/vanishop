<script setup lang="ts">
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
    { key: 'id', label: '#', class: 'font-mono text-xs' },
    { key: 'target', label: 'Target', class: 'font-mono text-xs' },
    { key: 'type', label: 'Loại', class: 'font-mono text-xs' },
    { key: 'reference', label: 'Tham chiếu', class: 'font-mono text-xs' },
    { key: 'status', label: 'Trạng thái' },
    { key: 'last_error', label: 'Lỗi gần nhất', class: 'max-w-xs text-xs break-words text-red-700' },
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

function filter(changes: Partial<typeof props.filters>): void {
    const next = { ...props.filters, ...changes };
    router.get(props.baseUrl, Object.fromEntries(Object.entries(next).filter(([, value]) => value)), { preserveState: true });
}

async function replay(ids: number[] | null): Promise<void> {
    const label = ids ? `${ids.length} message` : 'mọi message lỗi đang lọc';
    if ((await confirmAction({ title: `Gửi lại ${label}?`, message: 'Message giữ nguyên ID nên phía nhận vẫn khử trùng lặp được.', confirmLabel: 'Gửi lại' })) === false) return;
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
    <Head title="Tích hợp" />
    <PageHeader title="Tích hợp" subtitle="Outbox/Inbox, message lỗi và gửi lại. Payload đã che thông tin cá nhân." />

    <div class="mb-6 grid gap-4 md:grid-cols-3">
        <div class="rounded-lg border border-slate-200 bg-white p-4">
            <div class="text-sm text-slate-500">Outbox chờ gửi</div>
            <div class="text-2xl font-semibold">
                {{ summary.outbox.pending ?? 0 }}
            </div>
            <div class="text-xs text-slate-400">
                Cũ nhất:
                {{ summary.oldest_pending_minutes === null ? '—' : `${summary.oldest_pending_minutes} phút` }}
            </div>
        </div>
        <div class="rounded-lg border border-slate-200 bg-white p-4">
            <div class="text-sm text-slate-500">Outbox lỗi / hết lượt</div>
            <div class="text-2xl font-semibold" :class="(summary.outbox.failed ?? 0) + (summary.outbox.dead ?? 0) > 0 ? 'text-red-600' : ''">
                {{ summary.outbox.failed ?? 0 }} /
                {{ summary.outbox.dead ?? 0 }}
            </div>
        </div>
        <div class="rounded-lg border border-slate-200 bg-white p-4">
            <div class="text-sm text-slate-500">Inbox lỗi / hết lượt</div>
            <div class="text-2xl font-semibold" :class="(summary.inbox.failed ?? 0) + (summary.inbox.dead ?? 0) > 0 ? 'text-red-600' : ''">
                {{ summary.inbox.failed ?? 0 }} / {{ summary.inbox.dead ?? 0 }}
            </div>
            <div class="text-xs text-slate-400">Connector đang bật: {{ summary.connectors.join(', ') || '—' }}</div>
        </div>
    </div>

    <div class="mb-3 flex flex-wrap items-center gap-2">
        <button
            v-for="box in ['outbox', 'inbox'] as const"
            :key="box"
            :class="[secondaryButton, filters.box === box ? 'border-indigo-500 text-red-700' : '']"
            @click="filter({ box, target: null })"
        >
            {{ box === 'outbox' ? 'Gửi đi' : 'Nhận vào' }}
        </button>
        <div class="w-48">
            <Select :model-value="filters.status ?? ''" :options="codeOptions(statuses, labels.message_status)" empty-label="Lỗi + hết lượt" aria-label="Trạng thái" @change="filter({ status: $event || null })" />
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
        <button v-if="can.replay && messages.length" :class="secondaryButton" @click="replay(null)">Gửi lại tất cả đang lọc</button>
    </div>

    <div class="mb-8">
        <DataTable :columns="messageColumns" :rows="messages" :row-class="() => 'align-top'" :expanded="(message) => opened === message.id" empty-text="Không có message nào khớp bộ lọc.">
            <template #cell-id="{ row }">
                {{ row.id }}
                <div class="text-slate-400">{{ row.at }}</div>
            </template>
            <template #cell-status="{ row }">
                <StatusBadge :label="labels.message_status[row.status] ?? row.status" :tone="statusTone(row.status)" />
                <span class="ml-1 text-xs text-slate-500">{{ row.attempts }} lần</span>
            </template>
            <template #cell-actions="{ row }">
                <button class="mr-3 text-indigo-600 hover:underline" @click="opened = opened === row.id ? null : row.id">Payload</button>
                <button v-if="can.replay && ['failed', 'dead'].includes(row.status)" class="text-indigo-600 hover:underline" @click="replay([row.id])">Gửi lại</button>
            </template>
            <template #expanded="{ row }">
                <div class="mb-1 text-xs text-slate-500">Correlation ID: {{ row.correlation_id ?? '—' }}</div>
                <pre class="max-h-80 overflow-auto text-xs">{{ JSON.stringify(row.payload, null, 2) }}</pre>
            </template>
        </DataTable>
    </div>

    <h2 class="mb-2 text-lg font-semibold">Integration Client</h2>
    <p class="mb-3 text-sm text-slate-500">
        Tạo client, cấp key và đăng ký webhook bằng lệnh
        <code>php artisan vani:integration:client</code> và <code>vani:integration:webhook</code>.
    </p>
    <DataTable :columns="clientColumns" :rows="clients" :row-class="() => 'align-top'" empty-text="Chưa có client nào.">
        <template #cell-name="{ row }">
            <div class="font-medium">{{ row.name }}</div>
            <div class="font-mono text-xs text-slate-400">{{ row.code }}<span v-if="row.status !== 'active'"> · tạm dừng</span></div>
        </template>
        <template #cell-scopes="{ row }">{{ row.scopes.join(', ') }}</template>
        <template #cell-active_keys="{ row }">{{ row.active_keys }} còn hiệu lực</template>
        <template #cell-subscriptions="{ row }">
            <div v-for="subscription in row.subscriptions" :key="subscription.id" class="mb-1">
                <span class="font-mono">{{ subscription.url }}</span>
                <span class="text-slate-400"> · {{ subscription.event_types.join(', ') }}</span>
                <span v-if="subscription.status === 'paused'" class="text-red-600"> · tạm dừng</span>
                <span v-else-if="subscription.failing_since" class="text-amber-600"> · lỗi từ {{ subscription.failing_since }}</span>
                <button v-if="can.manage && subscription.status === 'paused'" class="ml-2 text-indigo-600 hover:underline" @click="resume(subscription.id)">Bật lại</button>
            </div>
            <span v-if="!row.subscriptions.length" class="text-slate-400">—</span>
        </template>
    </DataTable>
</template>
