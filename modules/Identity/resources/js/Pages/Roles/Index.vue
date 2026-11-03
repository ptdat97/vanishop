<script setup lang="ts">
import PageHeader from '@admin/Components/PageHeader.vue';
import { primaryButton, secondaryButton } from '@admin/styles';
import { Head, Link } from '@inertiajs/vue3';

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
    <table class="w-full rounded-lg border border-slate-200 bg-white text-sm">
        <thead class="bg-slate-50 text-left text-slate-500">
            <tr>
                <th class="px-4 py-2">Vai trò</th>
                <th class="px-4 py-2">Số quyền</th>
                <th class="px-4 py-2">Đang gán</th>
                <th />
            </tr>
        </thead>
        <tbody>
            <tr v-for="role in roles" :key="role.id" class="border-t border-slate-100">
                <td class="px-4 py-2">
                    <div class="font-medium">{{ role.name }}</div>
                    <div class="font-mono text-xs text-slate-400">{{ role.code }}<span v-if="role.is_system"> · hệ thống</span></div>
                </td>
                <td class="px-4 py-2">{{ role.is_system && role.permissions_count === 1 ? 'Toàn quyền' : role.permissions_count }}</td>
                <td class="px-4 py-2">{{ role.assignments_count }}</td>
                <td class="px-4 py-2 text-right">
                    <Link :href="`${baseUrl}/${role.id}/edit`" class="text-indigo-600 hover:underline">{{ role.is_system ? 'Xem' : 'Sửa' }}</Link>
                </td>
            </tr>
        </tbody>
    </table>
</template>
