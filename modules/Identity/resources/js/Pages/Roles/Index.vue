<script setup lang="ts">
import { t } from '@admin/i18n';
import DataTable from '@admin/Components/DataTable.vue';
import PageHeader from '@admin/Components/PageHeader.vue';
import { primaryButton, secondaryButton } from '@admin/styles';
import { Head, Link, router } from '@inertiajs/vue3';

const columns = [
    { key: 'name', label: t('identity::admin.roles_index.roles') },
    { key: 'permissions_count', label: t('identity::admin.roles_index.permissions') },
    { key: 'assignments_count', label: t('identity::admin.roles_index.assigned') },
];

const props = defineProps<{
    baseUrl: string;
    staffUrl: string;
    roles: Array<{ id: number; code: string; name: string; is_system: boolean; permissions_count: number; assignments_count: number }>;
}>();
</script>

<template>
    <Head :title="t('identity::admin.roles_index.roles')" />
    <PageHeader :title="t('identity::admin.roles_index.roles')" :subtitle="t('identity::admin.roles_index.permission_sets_assigned_staff')">
        <Link :href="staffUrl" :class="secondaryButton">{{ t('identity::admin.roles_index.staff') }}</Link>
        <Link :href="`${baseUrl}/create`" :class="primaryButton">{{ t('identity::admin.roles_index.add_role') }}</Link>
    </PageHeader>
    <DataTable :columns="columns" :rows="roles" :row-label="(role) => role.name" :row-click="(role) => router.visit(`${props.baseUrl}/${role.id}/edit`)">
        <template #cell-name="{ row }">
            <div class="font-medium">{{ row.name }}</div>
            <div class="font-mono text-xs text-slate-400">{{ row.code }}<span v-if="row.is_system"> {{ t('identity::admin.roles_index.system') }}</span></div>
        </template>
        <template #cell-permissions_count="{ row }">{{ row.is_system && row.permissions_count === 1 ? t('identity::admin.roles_index.full_access') : row.permissions_count }}</template>
    </DataTable>
</template>
