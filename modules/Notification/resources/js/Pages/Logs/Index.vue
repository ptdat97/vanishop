<script setup lang="ts">
import PageHeader from '@admin/Components/PageHeader.vue';
import { inputClass, secondaryButton } from '@admin/styles';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps<{
    baseUrl: string;
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

const statusLabels: Record<string, string> = {
    queued: 'Chờ gửi',
    sent: 'Đã gửi',
    failed: 'Lỗi',
    skipped: 'Bỏ qua',
};
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
        <select
            :class="[inputClass, 'w-40']"
            :value="filters.status ?? ''"
            @change="
                search({
                    status: ($event.target as HTMLSelectElement).value || null,
                })
            "
        >
            <option value="">Mọi trạng thái</option>
            <option v-for="status in statuses" :key="status" :value="status">
                {{ statusLabels[status] ?? status }}
            </option>
        </select>
        <button type="submit" :class="secondaryButton">Lọc</button>
    </form>

    <table class="w-full rounded-lg border border-slate-200 bg-white text-sm">
        <thead class="bg-slate-50 text-left text-slate-500">
            <tr>
                <th class="px-4 py-2">Thời gian</th>
                <th class="px-4 py-2">Loại / kênh</th>
                <th class="px-4 py-2">Người nhận</th>
                <th class="px-4 py-2">Trạng thái</th>
                <th class="px-4 py-2">Lỗi</th>
            </tr>
        </thead>
        <tbody>
            <tr v-for="log in logs" :key="log.id" class="border-t border-slate-100 align-top">
                <td class="px-4 py-2 text-xs">
                    {{ log.created_at }}
                    <div v-if="log.sent_at" class="text-slate-400">gửi {{ log.sent_at }}</div>
                </td>
                <td class="px-4 py-2 font-mono text-xs">{{ log.type }} · {{ log.channel }}</td>
                <td class="px-4 py-2 font-mono text-xs">{{ log.recipient }}</td>
                <td class="px-4 py-2">{{ statusLabels[log.status] ?? log.status }} · {{ log.attempts }} lần</td>
                <td class="max-w-xs px-4 py-2 text-xs break-words text-red-700">
                    {{ log.error }}
                </td>
            </tr>
            <tr v-if="!logs.length">
                <td colspan="5" class="px-4 py-6 text-center text-slate-500">Chưa có tin nào.</td>
            </tr>
        </tbody>
    </table>

    <div v-if="pagination.last_page > 1" class="mt-3 flex gap-2">
        <button :class="secondaryButton" :disabled="pagination.page <= 1" @click="search({ page: pagination.page - 1 })">Trước</button>
        <span class="px-2 py-2 text-sm text-slate-500">Trang {{ pagination.page }} / {{ pagination.last_page }}</span>
        <button :class="secondaryButton" :disabled="pagination.page >= pagination.last_page" @click="search({ page: pagination.page + 1 })">Sau</button>
    </div>
</template>
