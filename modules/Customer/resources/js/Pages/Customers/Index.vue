<script setup lang="ts">
import ExtensionFilters from '@admin/Components/Extensions/ExtensionFilters.vue';
import PageHeader from '@admin/Components/PageHeader.vue';
import { inputClass, secondaryButton } from '@admin/styles';
import type { ExtensionList } from '@admin/types';
import { Head, Link, router } from '@inertiajs/vue3';
import { reactive, ref } from 'vue';

const props = defineProps<{
    baseUrl: string;
    filters: { q: string | null; status: string | null; group: number | null; tag: string | null };
    statuses: string[];
    groups: Array<{ id: number; code: string; name: string }>;
    groupsUrl: string;
    customers: Array<{
        id: string;
        ref: number;
        phone: string | null;
        email: string | null;
        full_name: string | null;
        status: string;
        registered: boolean;
        created_at: string | null;
        group: string | null;
    }>;
    pagination: { page: number; last_page: number; total: number };
    extensions: Omit<ExtensionList, 'actions'>;
}>();

const statusLabels: Record<string, string> = {
    active: 'Hoạt động',
    merged: 'Đã hợp nhất',
    anonymized: 'Đã ẩn danh',
};
const q = ref(props.filters.q ?? '');
const tag = ref(props.filters.tag ?? '');
const ext = reactive<Record<string, string>>({ ...props.extensions.filterValues });

function search(changes: { q?: string | null; status?: string | null; group?: number | null; page?: number } = {}): void {
    const next = {
        q: q.value || null,
        status: props.filters.status,
        group: props.filters.group,
        tag: tag.value || null,
        ...changes,
    };
    router.get(props.baseUrl, { ...Object.fromEntries(Object.entries(next).filter(([, value]) => value)), ext }, { preserveState: true });
}
</script>

<template>
    <Head title="Khách hàng" />
    <PageHeader title="Khách hàng" :subtitle="`${pagination.total} khách`">
        <Link :href="groupsUrl" :class="secondaryButton">Nhóm khách</Link>
    </PageHeader>

    <form class="mb-3 flex flex-wrap gap-2" @submit.prevent="search()">
        <input v-model="q" :class="[inputClass, 'w-72']" placeholder="SĐT, email, tên hoặc mã khách" />
        <select
            :class="[inputClass, 'w-48']"
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
        <select
            :class="[inputClass, 'w-44']"
            :value="filters.group ?? ''"
            @change="search({ group: Number(($event.target as HTMLSelectElement).value) || null })"
        >
            <option value="">Mọi nhóm</option>
            <option v-for="group in groups" :key="group.id" :value="group.id">{{ group.name }}</option>
        </select>
        <input v-model="tag" :class="[inputClass, 'w-36']" placeholder="Tag" />
        <ExtensionFilters v-model="ext" :filters="extensions.filters" />
        <button type="submit" :class="secondaryButton">Tìm</button>
    </form>

    <table class="w-full rounded-lg border border-slate-200 bg-white text-sm">
        <thead class="bg-slate-50 text-left text-slate-500">
            <tr>
                <th class="px-4 py-2">Khách</th>
                <th class="px-4 py-2">SĐT</th>
                <th class="px-4 py-2">Email</th>
                <th class="px-4 py-2">Loại</th>
                <th class="px-4 py-2">Nhóm</th>
                <th class="px-4 py-2">Tạo lúc</th>
                <th v-for="column in extensions.columns" :key="column.key" class="px-4 py-2">{{ column.label }}</th>
            </tr>
        </thead>
        <tbody>
            <tr v-for="customer in customers" :key="customer.id" class="border-t border-slate-100">
                <td class="px-4 py-2">
                    <Link :href="`${baseUrl}/${customer.id}`" class="font-medium text-indigo-600 hover:underline">
                        {{ customer.full_name ?? '(chưa có tên)' }}
                    </Link>
                    <div class="font-mono text-xs text-slate-400">
                        {{ customer.id }}<span v-if="customer.status !== 'active'"> · {{ statusLabels[customer.status] }}</span>
                    </div>
                </td>
                <td class="px-4 py-2 font-mono text-xs">
                    {{ customer.phone ?? '—' }}
                </td>
                <td class="px-4 py-2 text-xs">{{ customer.email ?? '—' }}</td>
                <td class="px-4 py-2 text-xs">
                    {{ customer.registered ? 'Tài khoản' : 'Khách vãng lai' }}
                </td>
                <td class="px-4 py-2 text-xs">{{ customer.group ?? '—' }}</td>
                <td class="px-4 py-2 text-xs">{{ customer.created_at }}</td>
                <td v-for="column in extensions.columns" :key="column.key" class="px-4 py-2 text-xs">{{ extensions.values[customer.ref]?.[column.key] ?? '—' }}</td>
            </tr>
            <tr v-if="!customers.length">
                <td :colspan="6 + extensions.columns.length" class="px-4 py-6 text-center text-slate-500">Không có khách nào.</td>
            </tr>
        </tbody>
    </table>

    <div v-if="pagination.last_page > 1" class="mt-3 flex gap-2">
        <button :class="secondaryButton" :disabled="pagination.page <= 1" @click="search({ page: pagination.page - 1 })">Trước</button>
        <span class="px-2 py-2 text-sm text-slate-500">Trang {{ pagination.page }} / {{ pagination.last_page }}</span>
        <button :class="secondaryButton" :disabled="pagination.page >= pagination.last_page" @click="search({ page: pagination.page + 1 })">Sau</button>
    </div>
</template>
