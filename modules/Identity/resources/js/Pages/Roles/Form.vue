<script setup lang="ts">
import { t } from '@admin/i18n';
import { useAdminForm } from '@admin/forms';
import FormField from '@admin/Components/FormField.vue';
import PageHeader from '@admin/Components/PageHeader.vue';
import { confirmAction } from '@admin/confirm';
import { dangerButton, inputClass, primaryButton, secondaryButton } from '@admin/styles';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps<{
    baseUrl: string;
    role: null | { id: number; code: string; name: string; is_system: boolean; permissions: string[]; assigned: boolean };
    groups: Array<{ key: string; permissions: Array<{ code: string; label: string }> }>;
}>();

const { form, save } = useAdminForm({
    code: props.role ? undefined : '',
    name: props.role?.name ?? '',
    permissions: [...(props.role?.permissions ?? [])],
});

function toggleGroup(codes: string[], checked: boolean): void {
    form.permissions = checked ? [...new Set([...form.permissions, ...codes])] : form.permissions.filter((code) => !codes.includes(code));
}

function submit(): void {
    if (props.role) {
        save('put', `${props.baseUrl}/${props.role.id}`);
    } else {
        save('post', props.baseUrl);
    }
}

async function destroy(): Promise<void> {
    if (props.role && (await confirmAction({ title: t('identity::admin.roles_form.delete_role_name', { name: props.role.name }), confirmLabel: t('identity::admin.roles_form.delete'), tone: 'danger' })) !== false) {
        router.delete(`${props.baseUrl}/${props.role.id}`);
    }
}
// Lỗi nghiệp vụ (tự khoá mình, Owner cuối cùng…) — cả từ form lẫn nút khoá/xoá.
const errors = computed(() => usePage().props.errors as Record<string, string>);
</script>

<template>
    <Head :title="role ? t('identity::admin.roles_form.role_name', { name: role.name }) : t('identity::admin.roles_form.add_role')" />
    <PageHeader :title="role ? t('identity::admin.roles_form.role_name', { name: role.name }) : t('identity::admin.roles_form.add_role')" :subtitle="role?.is_system ? t('identity::admin.roles_form.system_role_cannot_edited') : undefined">
        <Link :href="baseUrl" :class="secondaryButton">{{ t('identity::admin.roles_form.back') }}</Link>
    </PageHeader>


    <form class="max-w-3xl space-y-4 rounded-lg border border-slate-200 bg-white p-5" @submit.prevent="submit">
        <fieldset :disabled="role?.is_system" class="space-y-4">
            <div class="grid gap-4 sm:grid-cols-2">
                <FormField :label="t('identity::admin.roles_form.name')" :error="form.errors.name"><input v-model="form.name" :class="inputClass" /></FormField>
                <FormField v-if="!role" :label="t('identity::admin.roles_form.code')" :hint="t('identity::admin.roles_form.lowercase_letters_digits_example_kho')" :error="form.errors.code"><input v-model="form.code" :class="inputClass" /></FormField>
            </div>
            <p v-if="role?.is_system && role.permissions.includes('*')" class="text-sm text-slate-600">{{ t('identity::admin.roles_form.full_access_every_current_permission') }}</p>
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
            <div v-if="!role?.is_system"><button type="submit" :class="primaryButton" :disabled="form.processing">{{ t('identity::admin.roles_form.save') }}</button></div>
        </fieldset>
    </form>

    <div v-if="role && !role.is_system" class="mt-4">
        <button :class="dangerButton" :disabled="role.assigned" :title="role.assigned ? t('identity::admin.roles_form.role_assigned') : undefined" @click="destroy">{{ t('identity::admin.roles_form.delete_role') }}</button>
    </div>
</template>
