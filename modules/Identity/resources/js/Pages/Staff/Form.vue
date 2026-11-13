<script setup lang="ts">
import { useAdminForm } from '@admin/forms';
import FormField from '@admin/Components/FormField.vue';
import PageHeader from '@admin/Components/PageHeader.vue';
import Select from '@admin/Components/Select.vue';
import { confirmAction } from '@admin/confirm';
import { recordOptions } from '@admin/options';
import { dangerButton, inputClass, primaryButton, secondaryButton } from '@admin/styles';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

type Assignment = { role_id: number | null; scope_type: string; scope_id: number | null };

const props = defineProps<{
    baseUrl: string;
    labels: { staff_status: Record<string, string>; scope_type: Record<string, string> };
    staff: null | { id: number; name: string; email: string; status: string; last_login_at: string | null; is_self: boolean };
    assignments: Assignment[];
    roles: Array<{ id: number; code: string; name: string }>;
    /** Loại phạm vi có id (vd. location) → id → nhãn. Phạm vi `owner` (toàn cửa hàng) luôn có. */
    scopes: Record<string, Record<string, string>>;
}>();

const scopeTypeOptions = computed(() => ['owner', ...Object.keys(props.scopes)].map((type) => ({ value: type, label: props.labels.scope_type[type] ?? type })));
const scopeOptions = (type: string): Array<{ value: number; label: string }> => Object.entries(props.scopes[type] ?? {}).map(([id, label]) => ({ value: Number(id), label }));
const { form, save } = useAdminForm({
    name: props.staff?.name ?? '',
    email: props.staff ? undefined : '',
    assignments: props.assignments.map((assignment) => ({ ...assignment })) as Assignment[],
});

function addAssignment(): void {
    form.assignments.push({ role_id: props.roles[0]?.id ?? null, scope_type: 'owner', scope_id: null });
}

function submit(): void {
    if (props.staff) {
        save('put', `${props.baseUrl}/${props.staff.id}`);
    } else {
        save('post', props.baseUrl);
    }
}

async function action(path: string, confirm?: { title: string; message: string; confirmLabel: string }): Promise<void> {
    if (props.staff && (!confirm || (await confirmAction({ ...confirm, tone: 'danger' })) !== false)) {
        router.post(`${props.baseUrl}/${props.staff.id}/${path}`, {}, { preserveScroll: true });
    }
}
// Lỗi nghiệp vụ (tự khoá mình, Owner cuối cùng…) — cả từ form lẫn nút khoá/xoá.
const errors = computed(() => usePage().props.errors as Record<string, string>);
</script>

<template>
    <Head :title="staff ? `Nhân viên: ${staff.name}` : 'Mời nhân viên'" />
    <PageHeader :title="staff ? `Nhân viên: ${staff.name}` : 'Mời nhân viên'" :subtitle="staff ? `${staff.email} · ${labels.staff_status[staff.status] ?? staff.status}` : 'Nhân viên nhận email để tự đặt mật khẩu (link 24 giờ).'">
        <Link :href="baseUrl" :class="secondaryButton">Quay lại</Link>
    </PageHeader>


    <form class="max-w-3xl space-y-4 rounded-lg border border-slate-200 bg-white p-5" @submit.prevent="submit">
        <div class="grid gap-4 sm:grid-cols-2">
            <FormField label="Họ tên" :error="form.errors.name"><input v-model="form.name" :class="inputClass" /></FormField>
            <FormField v-if="!staff" label="Email" :error="form.errors.email"><input v-model="form.email" type="email" :class="inputClass" /></FormField>
        </div>

        <fieldset :disabled="staff?.is_self">
            <legend class="mb-2 text-sm font-medium text-slate-700">Vai trò</legend>
            <p v-if="staff?.is_self" class="mb-2 text-xs text-slate-500">Không tự đổi vai trò của chính mình.</p>
            <div v-for="(assignment, index) in form.assignments" :key="index" class="mb-2 flex flex-wrap gap-2">
                <div class="w-56"><Select v-model="assignment.role_id" :options="recordOptions(roles, (role) => role.name)" aria-label="Vai trò" :disabled="staff?.is_self" /></div>
                <div class="w-48">
                    <Select v-model="assignment.scope_type" :options="scopeTypeOptions" aria-label="Phạm vi" :disabled="staff?.is_self" @change="assignment.scope_id = null" />
                </div>
                <div v-if="assignment.scope_type !== 'owner'" class="w-56">
                    <Select v-model="assignment.scope_id" :options="scopeOptions(assignment.scope_type)" aria-label="Kho/cửa hàng" :disabled="staff?.is_self" />
                </div>
                <button type="button" :class="secondaryButton" @click="form.assignments.splice(index, 1)">Bỏ</button>
            </div>
            <p v-for="(message, key) in form.errors" v-show="String(key).startsWith('assignments')" :key="key" class="text-sm text-red-600">{{ message }}</p>
            <button type="button" :class="secondaryButton" @click="addAssignment">Thêm vai trò</button>
        </fieldset>

        <div><button type="submit" :class="primaryButton" :disabled="form.processing">{{ staff ? 'Lưu' : 'Gửi lời mời' }}</button></div>
    </form>

    <div v-if="staff && !staff.is_self" class="mt-4 flex max-w-3xl flex-wrap gap-2">
        <button v-if="staff.status === 'invited'" :class="secondaryButton" @click="action('resend-invite')">Gửi lại lời mời</button>
        <button v-if="staff.status !== 'locked'" :class="dangerButton" @click="action('lock', { title: 'Khoá nhân viên này?', message: 'Nhân viên bị đăng xuất và không đăng nhập được.', confirmLabel: 'Khoá' })">Khoá</button>
        <button v-else :class="secondaryButton" @click="action('unlock')">Mở khoá</button>
    </div>
</template>
