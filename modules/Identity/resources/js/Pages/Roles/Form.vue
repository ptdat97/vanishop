<script setup lang="ts">
import FormField from '@admin/Components/FormField.vue';
import PageHeader from '@admin/Components/PageHeader.vue';
import { dangerButton, inputClass, primaryButton, secondaryButton } from '@admin/styles';
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps<{
    baseUrl: string;
    role: null | { id: number; code: string; name: string; is_system: boolean; permissions: string[]; assigned: boolean };
    groups: Array<{ key: string; permissions: Array<{ code: string; label: string }> }>;
}>();

const form = useForm({
    code: props.role ? undefined : '',
    name: props.role?.name ?? '',
    permissions: [...(props.role?.permissions ?? [])],
});

function toggleGroup(codes: string[], checked: boolean): void {
    form.permissions = checked ? [...new Set([...form.permissions, ...codes])] : form.permissions.filter((code) => !codes.includes(code));
}

function submit(): void {
    if (props.role) {
        form.put(`${props.baseUrl}/${props.role.id}`, { preserveScroll: true });
    } else {
        form.post(props.baseUrl);
    }
}

function destroy(): void {
    if (props.role && window.confirm(`Xoá vai trò ${props.role.name}?`)) {
        router.delete(`${props.baseUrl}/${props.role.id}`);
    }
}
// Lỗi nghiệp vụ (tự khoá mình, Owner cuối cùng…) — cả từ form lẫn nút khoá/xoá.
const errors = computed(() => usePage().props.errors as Record<string, string>);
</script>

<template>
    <Head :title="role ? `Vai trò: ${role.name}` : 'Thêm vai trò'" />
    <PageHeader :title="role ? `Vai trò: ${role.name}` : 'Thêm vai trò'" :subtitle="role?.is_system ? 'Vai trò hệ thống — không sửa được.' : undefined">
        <Link :href="baseUrl" :class="secondaryButton">Quay lại</Link>
    </PageHeader>

    <p v-if="errors.business" class="mb-4 rounded-md border border-red-200 bg-red-50 px-4 py-2 text-sm text-red-700">{{ errors.business }}</p>

    <form class="max-w-3xl space-y-4 rounded-lg border border-slate-200 bg-white p-5" @submit.prevent="submit">
        <fieldset :disabled="role?.is_system" class="space-y-4">
            <div class="grid gap-4 sm:grid-cols-2">
                <FormField label="Tên" :error="form.errors.name"><input v-model="form.name" :class="inputClass" /></FormField>
                <FormField v-if="!role" label="Mã" hint="Chữ thường, số, _. Ví dụ: kho_hcm" :error="form.errors.code"><input v-model="form.code" :class="inputClass" /></FormField>
            </div>
            <p v-if="role?.is_system && role.permissions.includes('*')" class="text-sm text-slate-600">Toàn quyền (mọi quyền hiện có và quyền plugin thêm sau).</p>
            <div v-else class="space-y-3">
                <p v-if="form.errors.permissions" class="text-sm text-red-600">{{ form.errors.permissions }}</p>
                <div v-for="group in groups" :key="group.key" class="rounded-md border border-slate-200 p-3">
                    <label class="mb-2 flex items-center gap-2 text-sm font-medium">
                        <input
                            type="checkbox"
                            :checked="group.permissions.every((permission) => form.permissions.includes(permission.code))"
                            @change="toggleGroup(group.permissions.map((permission) => permission.code), ($event.target as HTMLInputElement).checked)"
                        />
                        <span class="font-mono">{{ group.key }}</span>
                    </label>
                    <div class="grid gap-1 pl-6 sm:grid-cols-2">
                        <label v-for="permission in group.permissions" :key="permission.code" class="flex items-start gap-2 text-sm">
                            <input v-model="form.permissions" type="checkbox" :value="permission.code" class="mt-1" />
                            <span>{{ permission.label }} <span class="font-mono text-xs text-slate-400">{{ permission.code }}</span></span>
                        </label>
                    </div>
                </div>
            </div>
            <div v-if="!role?.is_system"><button type="submit" :class="primaryButton" :disabled="form.processing">Lưu</button></div>
        </fieldset>
    </form>

    <div v-if="role && !role.is_system" class="mt-4">
        <button :class="dangerButton" :disabled="role.assigned" :title="role.assigned ? 'Vai trò đang được gán' : undefined" @click="destroy">Xoá vai trò</button>
    </div>
</template>
