<script setup lang="ts">
import DataTable from '@admin/Components/DataTable.vue';
import PageHeader from '@admin/Components/PageHeader.vue';
import { primaryButton, secondaryButton } from '@admin/styles';
import { Head, Link } from '@inertiajs/vue3';

const columns = [
    { key: 'name', label: 'Vai trò' },
    { key: 'permissions_count', label: 'Số quyền' },
    { key: 'assignments_count', label: 'Đang gán' },
    { key: 'actions', label: '', align: 'right' as const },
];

defineProps<{
    baseUrl: string;
    staffUrl: string;
    roles: Array<{ id: number; code: string; name: string; is_system: boolean; permissions_count: number; assignments_count: number }>;
}>();
</script>

<template>
    <Head title="Vai trò" />
    <PageHeader title="Vai trò" subtitle="Nhóm quyền gán cho nhân viên.">
        <Link :href="staffUrl" :class="secondaryButton">Nhân viên</Link>
        <Link :href="`${baseUrl}/create`" :class="primaryButton">Thêm vai trò</Link>
    </PageHeader>
    <DataTable :columns="columns" :rows="roles" :row-label="(role) => role.name">
        <template #cell-name="{ row }">
            <div class="font-medium">{{ row.name }}</div>
            <div class="font-mono text-xs text-slate-400">{{ row.code }}<span v-if="row.is_system"> · hệ thống</span></div>
        </template>
        <template #cell-permissions_count="{ row }">{{ row.is_system && row.permissions_count === 1 ? 'Toàn quyền' : row.permissions_count }}</template>
        <template #cell-actions="{ row }"><Link :href="`${baseUrl}/${row.id}/edit`" class="text-indigo-600 hover:underline">{{ row.is_system ? 'Xem' : 'Sửa' }}</Link></template>
    </DataTable>
</template>
