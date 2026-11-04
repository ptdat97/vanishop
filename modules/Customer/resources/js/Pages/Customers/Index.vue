<script setup lang="ts">
import DataTable from '@admin/Components/DataTable.vue';
import ExtensionFilters from '@admin/Components/Extensions/ExtensionFilters.vue';
import PageHeader from '@admin/Components/PageHeader.vue';
import Pagination from '@admin/Components/Pagination.vue';
import Select from '@admin/Components/Select.vue';
import { codeOptions, recordOptions } from '@admin/options';
import { inputClass, secondaryButton } from '@admin/styles';
import type { ExtensionList } from '@admin/types';
import { Head, Link, router } from '@inertiajs/vue3';
import { reactive, ref } from 'vue';

const props = defineProps<{
    baseUrl: string;
    labels: { customer_status: Record<string, string> };
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

const columns = [
    { key: 'full_name', label: 'Khách' },
    { key: 'phone', label: 'SĐT', class: 'font-mono text-xs' },
    { key: 'email', label: 'Email', class: 'text-xs' },
    { key: 'registered', label: 'Loại', class: 'text-xs' },
    { key: 'group', label: 'Nhóm', class: 'text-xs' },
    { key: 'created_at', label: 'Tạo lúc', class: 'text-xs' },
];
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
        <div class="w-48">
            <Select :model-value="filters.status ?? ''" :options="codeOptions(statuses, labels.customer_status)" empty-label="Mọi trạng thái" aria-label="Trạng thái" @change="search({ status: $event || null })" />
        </div>
        <div class="w-44">
            <Select :model-value="filters.group" :options="recordOptions(groups, (group) => group.name)" empty-label="Mọi nhóm" :empty-value="null" aria-label="Nhóm khách" @change="search({ group: $event })" />
        </div>
        <input v-model="tag" :class="[inputClass, 'w-36']" placeholder="Tag" />
        <ExtensionFilters v-model="ext" :filters="extensions.filters" />
        <button type="submit" :class="secondaryButton">Tìm</button>
    </form>

    <DataTable :columns="columns" :rows="customers" :extensions="{ ...extensions, actions: [] }" :extension-id="(customer) => customer.ref" :row-label="(customer) => customer.id" empty-text="Không có khách nào.">
        <template #cell-full_name="{ row }">
            <Link :href="`${baseUrl}/${row.id}`" class="font-medium text-indigo-600 hover:underline">{{ row.full_name ?? '(chưa có tên)' }}</Link>
            <div class="font-mono text-xs text-slate-400">
                {{ row.id }}<span v-if="row.status !== 'active'"> · {{ labels.customer_status[row.status] ?? row.status }}</span>
            </div>
        </template>
        <template #cell-phone="{ row }">{{ row.phone ?? '—' }}</template>
        <template #cell-email="{ row }">{{ row.email ?? '—' }}</template>
        <template #cell-registered="{ row }">{{ row.registered ? 'Tài khoản' : 'Khách vãng lai' }}</template>
        <template #cell-group="{ row }">{{ row.group ?? '—' }}</template>
    </DataTable>

    <Pagination :current="pagination.page" :last="pagination.last_page" @change="search({ page: $event })" />
</template>
