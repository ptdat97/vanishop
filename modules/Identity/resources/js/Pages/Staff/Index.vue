<script setup lang="ts">
import PageHeader from '@admin/Components/PageHeader.vue';
import { inputClass, primaryButton, secondaryButton } from '@admin/styles';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps<{
    baseUrl: string;
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

const statusLabels: Record<string, string> = { active: 'Hoạt động', invited: 'Chờ nhận lời mời', locked: 'Đã khoá' };
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
        <select :class="[inputClass, 'w-48']" :value="filters.role ?? ''" @change="search({ role: Number(($event.target as HTMLSelectElement).value) || null })">
            <option value="">Mọi vai trò</option>
            <option v-for="role in roles" :key="role.id" :value="role.id">{{ role.name }}</option>
        </select>
        <select :class="[inputClass, 'w-48']" :value="filters.status ?? ''" @change="search({ status: ($event.target as HTMLSelectElement).value || null })">
            <option value="">Mọi trạng thái</option>
            <option v-for="(label, value) in statusLabels" :key="value" :value="value">{{ label }}</option>
        </select>
        <button type="submit" :class="secondaryButton">Tìm</button>
    </form>

    <table class="w-full rounded-lg border border-slate-200 bg-white text-sm">
        <thead class="bg-slate-50 text-left text-slate-500">
            <tr>
                <th class="px-4 py-2">Nhân viên</th>
                <th class="px-4 py-2">Vai trò</th>
                <th class="px-4 py-2">Trạng thái</th>
                <th class="px-4 py-2">Đăng nhập gần nhất</th>
                <th />
            </tr>
        </thead>
        <tbody>
            <tr v-for="member in staff.data" :key="member.id" class="border-t border-slate-100">
                <td class="px-4 py-2">
                    <div class="font-medium">{{ member.name }}</div>
                    <div class="text-xs text-slate-400">{{ member.email }}</div>
                </td>
                <td class="px-4 py-2">{{ member.roles.join(', ') || '—' }}</td>
                <td class="px-4 py-2">
                    <span :class="{ 'text-red-600': member.status === 'locked', 'text-amber-600': member.status === 'invited' }">{{ statusLabels[member.status] ?? member.status }}</span>
                </td>
                <td class="px-4 py-2 text-slate-500">{{ member.last_login_at ? new Date(member.last_login_at).toLocaleString('vi-VN') : '—' }}</td>
                <td class="px-4 py-2 text-right">
                    <Link :href="`${baseUrl}/${member.id}/edit`" class="text-indigo-600 hover:underline">Sửa</Link>
                </td>
            </tr>
            <tr v-if="!staff.data.length">
                <td colspan="5" class="px-4 py-6 text-center text-slate-500">Không có nhân viên phù hợp.</td>
            </tr>
        </tbody>
    </table>
    <div v-if="staff.last_page > 1" class="mt-3 flex gap-2">
        <button :class="secondaryButton" :disabled="staff.current_page <= 1" @click="search({ page: staff.current_page - 1 })">Trước</button>
        <span class="px-2 py-2 text-sm text-slate-500">Trang {{ staff.current_page }} / {{ staff.last_page }}</span>
        <button :class="secondaryButton" :disabled="staff.current_page >= staff.last_page" @click="search({ page: staff.current_page + 1 })">Sau</button>
    </div>
</template>
