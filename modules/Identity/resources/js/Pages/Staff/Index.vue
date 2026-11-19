<script setup lang="ts">
import { t } from '@admin/i18n';
import { useListQuery, type ListSortState } from '@admin/list';
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
    labels: { staff_status: Record<string, string> };
    rolesUrl: string;
    filters: { q: string; role: number | null; status: string | null };
    sort: ListSortState | null;
    roles: Array<{ id: number; name: string }>;
    staff: {
        data: Array<{ id: number; name: string; email: string; status: string; roles: string[]; last_login_at: string | null }>;
        current_page: number;
        last_page: number;
        from: number | null;
        to: number | null;
        total: number;
        per_page: number;
    };
}>();

const formatDate = useDateTime();
const columns = [
    { key: 'name', label: t('identity::admin.staff_index.staff'), sortable: true },
    { key: 'roles', label: t('identity::admin.staff_index.roles') },
    { key: 'status', label: t('identity::admin.staff_index.status') },
    { key: 'last_login_at', label: t('identity::admin.staff_index.last_sign'), class: 'text-slate-500', sortable: true },
];
const q = ref(props.filters.q);
const list = useListQuery(() => props.baseUrl);

const perPageOptions = [25, 50, 100];

function search(changes: { role?: number | null; status?: string | null; page?: number; per_page?: number; sort?: ListSortState | null } = {}): void {
    const { sort = props.sort, ...rest } = changes;
    list.visit({ q: q.value, role: props.filters.role, status: props.filters.status, per_page: props.staff.per_page, ...list.sortQuery(sort), ...rest });
}
</script>

<template>
    <Head :title="t('identity::admin.staff_index.staff')" />
    <PageHeader :title="t('identity::admin.staff_index.staff')" :subtitle="t('identity::admin.staff_index.total_staff', { total: staff.total })">
        <Link :href="rolesUrl" :class="secondaryButton">{{ t('identity::admin.staff_index.roles') }}</Link>
        <Link :href="`${baseUrl}/create`" :class="primaryButton">{{ t('identity::admin.staff_index.invite_staff') }}</Link>
    </PageHeader>

    <form class="mb-3 flex flex-wrap gap-2" @submit.prevent="search()">
        <input v-model="q" :class="[inputClass, 'w-72']" :placeholder="t('identity::admin.staff_index.name_email')" />
        <div class="w-48">
            <Select :model-value="filters.role" :options="recordOptions(roles, (role) => role.name)" :empty-label="t('identity::admin.staff_index.all_roles')" :empty-value="null" :aria-label="t('identity::admin.staff_index.roles')" @change="search({ role: $event })" />
        </div>
        <div class="w-48">
            <Select :model-value="filters.status ?? ''" :options="labelOptions(labels.staff_status)" :empty-label="t('identity::admin.staff_index.all_statuses')" :aria-label="t('identity::admin.staff_index.status')" @change="search({ status: $event || null })" />
        </div>
        <button type="submit" :class="secondaryButton">{{ t('identity::admin.staff_index.search') }}</button>
    </form>

    <DataTable :columns="columns" :rows="staff.data" :sort="sort" :loading="list.loading.value" @sort="(next) => search({ sort: next })" :row-label="(member) => member.email" :row-click="(member) => router.visit(`${props.baseUrl}/${member.id}/edit`)" :empty-text="t('identity::admin.staff_index.no_matching_staff')">
        <template #cell-name="{ row }">
            <div class="font-medium">{{ row.name }}</div>
            <div class="text-xs text-slate-400">{{ row.email }}</div>
        </template>
        <template #cell-roles="{ row }">{{ row.roles.join(', ') || '—' }}</template>
        <template #cell-status="{ row }">
            <StatusBadge :label="labels.staff_status[row.status] ?? row.status" :tone="row.status === 'locked' ? 'danger' : row.status === 'invited' ? 'warning' : 'success'" />
        </template>
        <template #cell-last_login_at="{ row }">{{ formatDate(row.last_login_at) }}</template>
    </DataTable>
    <Pagination :current="staff.current_page" :last="staff.last_page" :from="staff.from" :to="staff.to" :total="staff.total" :per-page="staff.per_page" :per-page-options="perPageOptions" @change="search({ page: $event })" @per-page-change="search({ page: 1, per_page: $event })" />
</template>
