<script setup lang="ts">
import DataTable from '@admin/Components/DataTable.vue';
import PageHeader from '@admin/Components/PageHeader.vue';
import Pagination from '@admin/Components/Pagination.vue';
import Select from '@admin/Components/Select.vue';
import StatusBadge from '@admin/Components/StatusBadge.vue';
import { useDateTime } from '@admin/datetime';
import { labelOptions, recordOptions } from '@admin/options';
import { inputClass, primaryButton, secondaryButton } from '@admin/styles';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps<{
    baseUrl: string;
    labels: { staff_status: Record<string, string>; scope_type: Record<string, string> };
    rolesUrl: string;
    filters: { q: string; role: number | null; status: string | null };
    roles: Array<{ id: number; name: string }>;
    staff: {
        data: Array<{ id: number; name: string; email: string; status: string; roles: string[]; last_login_at: string | null }>;
        current_page: number;
        last_page: number;
        total: number;
    };
}>();

const formatDate = useDateTime();
const columns = [
    { key: 'name', label: 'Nhân viên' },
    { key: 'roles', label: 'Vai trò' },
    { key: 'status', label: 'Trạng thái' },
    { key: 'last_login_at', label: 'Đăng nhập gần nhất', class: 'text-slate-500' },
    { key: 'actions', label: '', align: 'right' as const },
];
const q = ref(props.filters.q);

function search(changes: { role?: number | null; status?: string | null; page?: number } = {}): void {
    const next = { q: q.value || null, role: props.filters.role, status: props.filters.status, ...changes };
    router.get(props.baseUrl, Object.fromEntries(Object.entries(next).filter(([, value]) => value)), { preserveState: true });
}
</script>

<template>
    <Head title="Nhân viên" />
    <PageHeader title="Nhân viên" :subtitle="`${staff.total} nhân viên`">
        <Link :href="rolesUrl" :class="secondaryButton">Vai trò</Link>
        <Link :href="`${baseUrl}/create`" :class="primaryButton">Mời nhân viên</Link>
    </PageHeader>

    <form class="mb-3 flex flex-wrap gap-2" @submit.prevent="search()">
        <input v-model="q" :class="[inputClass, 'w-72']" placeholder="Tên hoặc email" />
        <div class="w-48">
            <Select :model-value="filters.role" :options="recordOptions(roles, (role) => role.name)" empty-label="Mọi vai trò" :empty-value="null" aria-label="Vai trò" @change="search({ role: $event })" />
        </div>
        <div class="w-48">
            <Select :model-value="filters.status ?? ''" :options="labelOptions(labels.staff_status)" empty-label="Mọi trạng thái" aria-label="Trạng thái" @change="search({ status: $event || null })" />
        </div>
        <button type="submit" :class="secondaryButton">Tìm</button>
    </form>

    <DataTable :columns="columns" :rows="staff.data" :row-label="(member) => member.email" empty-text="Không có nhân viên phù hợp.">
        <template #cell-name="{ row }">
            <div class="font-medium">{{ row.name }}</div>
            <div class="text-xs text-slate-400">{{ row.email }}</div>
        </template>
        <template #cell-roles="{ row }">{{ row.roles.join(', ') || '—' }}</template>
        <template #cell-status="{ row }">
            <StatusBadge :label="labels.staff_status[row.status] ?? row.status" :tone="row.status === 'locked' ? 'danger' : row.status === 'invited' ? 'warning' : 'success'" />
        </template>
        <template #cell-last_login_at="{ row }">{{ formatDate(row.last_login_at) }}</template>
        <template #cell-actions="{ row }"><Link :href="`${baseUrl}/${row.id}/edit`" class="text-indigo-600 hover:underline">Sửa</Link></template>
    </DataTable>
    <Pagination :current="staff.current_page" :last="staff.last_page" @change="search({ page: $event })" />
</template>
