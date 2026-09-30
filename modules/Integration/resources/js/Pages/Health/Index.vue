<script setup lang="ts">
import PageHeader from "@admin/Components/PageHeader.vue";
import { inputClass, secondaryButton } from "@admin/styles";
import { Head, router } from "@inertiajs/vue3";
import { ref } from "vue";

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
    filters: {
        box: "outbox" | "inbox";
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
        brand_ids: number[] | null;
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

const statusLabels: Record<string, string> = {
    pending: "Chờ gửi",
    received: "Đã nhận",
    processing: "Đang xử lý",
    sent: "Đã gửi",
    processed: "Đã xử lý",
    failed: "Lỗi (chờ sửa)",
    dead: "Hết lượt thử",
    ignored_stale: "Bỏ qua (bản cũ)",
};
const opened = ref<number | null>(null);

function filter(changes: Partial<typeof props.filters>): void {
    const next = { ...props.filters, ...changes };
    router.get(
        props.baseUrl,
        Object.fromEntries(Object.entries(next).filter(([, value]) => value)),
        { preserveState: true },
    );
}

function replay(ids: number[] | null): void {
    const label = ids ? `${ids.length} message` : "mọi message lỗi đang lọc";
    if (
        !confirm(
            `Gửi lại ${label}? Message giữ nguyên ID nên phía nhận vẫn khử trùng lặp được.`,
        )
    )
        return;
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
    router.post(
        `${props.baseUrl}/subscriptions/${id}/resume`,
        {},
        { preserveScroll: true },
    );
}
</script>

<template>
    <Head title="Tích hợp" />
    <PageHeader
        title="Tích hợp"
        subtitle="Outbox/Inbox, message lỗi và gửi lại. Payload đã che thông tin cá nhân."
    />

    <div class="mb-6 grid gap-4 md:grid-cols-3">
        <div class="rounded-lg border border-slate-200 bg-white p-4">
            <div class="text-sm text-slate-500">Outbox chờ gửi</div>
            <div class="text-2xl font-semibold">
                {{ summary.outbox.pending ?? 0 }}
            </div>
            <div class="text-xs text-slate-400">
                Cũ nhất:
                {{
                    summary.oldest_pending_minutes === null
                        ? "—"
                        : `${summary.oldest_pending_minutes} phút`
                }}
            </div>
        </div>
        <div class="rounded-lg border border-slate-200 bg-white p-4">
            <div class="text-sm text-slate-500">Outbox lỗi / hết lượt</div>
            <div
                class="text-2xl font-semibold"
                :class="
                    (summary.outbox.failed ?? 0) + (summary.outbox.dead ?? 0) >
                    0
                        ? 'text-red-600'
                        : ''
                "
            >
                {{ summary.outbox.failed ?? 0 }} /
                {{ summary.outbox.dead ?? 0 }}
            </div>
        </div>
        <div class="rounded-lg border border-slate-200 bg-white p-4">
            <div class="text-sm text-slate-500">Inbox lỗi / hết lượt</div>
            <div
                class="text-2xl font-semibold"
                :class="
                    (summary.inbox.failed ?? 0) + (summary.inbox.dead ?? 0) > 0
                        ? 'text-red-600'
                        : ''
                "
            >
                {{ summary.inbox.failed ?? 0 }} / {{ summary.inbox.dead ?? 0 }}
            </div>
            <div class="text-xs text-slate-400">
                Connector đang bật: {{ summary.connectors.join(", ") || "—" }}
            </div>
        </div>
    </div>

    <div class="mb-3 flex flex-wrap items-center gap-2">
        <button
            v-for="box in ['outbox', 'inbox'] as const"
            :key="box"
            :class="[
                secondaryButton,
                filters.box === box ? 'border-indigo-500 text-indigo-700' : '',
            ]"
            @click="filter({ box, target: null })"
        >
            {{ box === "outbox" ? "Gửi đi" : "Nhận vào" }}
        </button>
        <select
            :class="[inputClass, 'w-48']"
            :value="filters.status ?? ''"
            @change="
                filter({
                    status: ($event.target as HTMLSelectElement).value || null,
                })
            "
        >
            <option value="">Lỗi + hết lượt</option>
            <option v-for="status in statuses" :key="status" :value="status">
                {{ statusLabels[status] ?? status }}
            </option>
        </select>
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
        <button
            v-if="can.replay && messages.length"
            :class="secondaryButton"
            @click="replay(null)"
        >
            Gửi lại tất cả đang lọc
        </button>
    </div>

    <table
        class="mb-8 w-full rounded-lg border border-slate-200 bg-white text-sm"
    >
        <thead class="bg-slate-50 text-left text-slate-500">
            <tr>
                <th class="px-4 py-2">#</th>
                <th class="px-4 py-2">Target</th>
                <th class="px-4 py-2">Loại</th>
                <th class="px-4 py-2">Tham chiếu</th>
                <th class="px-4 py-2">Trạng thái</th>
                <th class="px-4 py-2">Lỗi gần nhất</th>
                <th />
            </tr>
        </thead>
        <tbody>
            <template v-for="message in messages" :key="message.id">
                <tr class="border-t border-slate-100 align-top">
                    <td class="px-4 py-2 font-mono text-xs">
                        {{ message.id }}
                        <div class="text-slate-400">{{ message.at }}</div>
                    </td>
                    <td class="px-4 py-2 font-mono text-xs">
                        {{ message.target }}
                    </td>
                    <td class="px-4 py-2 font-mono text-xs">
                        {{ message.type }}
                    </td>
                    <td class="px-4 py-2 font-mono text-xs">
                        {{ message.reference }}
                    </td>
                    <td class="px-4 py-2">
                        {{ statusLabels[message.status] ?? message.status }} ·
                        {{ message.attempts }} lần
                    </td>
                    <td
                        class="max-w-xs px-4 py-2 text-xs break-words text-red-700"
                    >
                        {{ message.last_error }}
                    </td>
                    <td class="px-4 py-2 text-right whitespace-nowrap">
                        <button
                            class="mr-3 text-indigo-600 hover:underline"
                            @click="
                                opened =
                                    opened === message.id ? null : message.id
                            "
                        >
                            Payload
                        </button>
                        <button
                            v-if="
                                can.replay &&
                                ['failed', 'dead'].includes(message.status)
                            "
                            class="text-indigo-600 hover:underline"
                            @click="replay([message.id])"
                        >
                            Gửi lại
                        </button>
                    </td>
                </tr>
                <tr
                    v-if="opened === message.id"
                    class="border-t border-slate-100 bg-slate-50"
                >
                    <td colspan="7" class="px-4 py-2">
                        <div class="mb-1 text-xs text-slate-500">
                            Correlation ID: {{ message.correlation_id ?? "—" }}
                        </div>
                        <pre class="max-h-80 overflow-auto text-xs">{{
                            JSON.stringify(message.payload, null, 2)
                        }}</pre>
                    </td>
                </tr>
            </template>
            <tr v-if="!messages.length">
                <td colspan="7" class="px-4 py-6 text-center text-slate-500">
                    Không có message nào khớp bộ lọc.
                </td>
            </tr>
        </tbody>
    </table>

    <h2 class="mb-2 text-lg font-semibold">Integration Client</h2>
    <p class="mb-3 text-sm text-slate-500">
        Tạo client, cấp key và đăng ký webhook bằng lệnh
        <code>php artisan vani:integration:client</code> và
        <code>vani:integration:webhook</code>.
    </p>
    <table class="w-full rounded-lg border border-slate-200 bg-white text-sm">
        <thead class="bg-slate-50 text-left text-slate-500">
            <tr>
                <th class="px-4 py-2">Client</th>
                <th class="px-4 py-2">Scope</th>
                <th class="px-4 py-2">Brand</th>
                <th class="px-4 py-2">Key</th>
                <th class="px-4 py-2">Webhook</th>
            </tr>
        </thead>
        <tbody>
            <tr
                v-for="client in clients"
                :key="client.id"
                class="border-t border-slate-100 align-top"
            >
                <td class="px-4 py-2">
                    <div class="font-medium">{{ client.name }}</div>
                    <div class="font-mono text-xs text-slate-400">
                        {{ client.code
                        }}<span v-if="client.status !== 'active'">
                            · tạm dừng</span
                        >
                    </div>
                </td>
                <td class="px-4 py-2 font-mono text-xs">
                    {{ client.scopes.join(", ") }}
                </td>
                <td class="px-4 py-2 text-xs">
                    {{
                        client.brand_ids === null
                            ? "Tất cả"
                            : client.brand_ids.join(", ")
                    }}
                </td>
                <td class="px-4 py-2 text-xs">
                    {{ client.active_keys }} còn hiệu lực
                </td>
                <td class="px-4 py-2 text-xs">
                    <div
                        v-for="subscription in client.subscriptions"
                        :key="subscription.id"
                        class="mb-1"
                    >
                        <span class="font-mono">{{ subscription.url }}</span>
                        <span class="text-slate-400">
                            · {{ subscription.event_types.join(", ") }}</span
                        >
                        <span
                            v-if="subscription.status === 'paused'"
                            class="text-red-600"
                        >
                            · tạm dừng</span
                        >
                        <span
                            v-else-if="subscription.failing_since"
                            class="text-amber-600"
                        >
                            · lỗi từ {{ subscription.failing_since }}</span
                        >
                        <button
                            v-if="
                                can.manage && subscription.status === 'paused'
                            "
                            class="ml-2 text-indigo-600 hover:underline"
                            @click="resume(subscription.id)"
                        >
                            Bật lại
                        </button>
                    </div>
                    <span
                        v-if="!client.subscriptions.length"
                        class="text-slate-400"
                        >—</span
                    >
                </td>
            </tr>
            <tr v-if="!clients.length">
                <td colspan="5" class="px-4 py-6 text-center text-slate-500">
                    Chưa có client nào.
                </td>
            </tr>
        </tbody>
    </table>
</template>
