<script setup lang="ts">
import FormField from '@admin/Components/FormField.vue';
import PageHeader from '@admin/Components/PageHeader.vue';
import Select from '@admin/Components/Select.vue';
import { inputClass, primaryButton } from '@admin/styles';
import { Head, useForm } from '@inertiajs/vue3';

/** Hồ sơ của tôi (§3.3-3): tên, ngôn ngữ Admin riêng (thông báo, lỗi, nhãn từ server), đổi mật khẩu. */
const props = defineProps<{
    profile: { name: string; email: string; locale: string | null };
    locales: Array<{ code: string; label: string }>;
    defaultLocale: string;
}>();

const profile = useForm({ name: props.profile.name, locale: props.profile.locale });
const password = useForm({ current_password: '', password: '', password_confirmation: '' });
const localeOptions = props.locales.map((locale) => ({ value: locale.code, label: locale.label }));
const defaultLabel = props.locales.find((locale) => locale.code === props.defaultLocale)?.label ?? props.defaultLocale;

function saveProfile(): void {
    profile.put(window.location.pathname, { preserveScroll: true });
}

function savePassword(): void {
    password.put(`${window.location.pathname}/mat-khau`, { preserveScroll: true, onSuccess: () => password.reset() });
}
</script>

<template>
    <Head title="Hồ sơ của tôi" />
    <PageHeader title="Hồ sơ của tôi" :subtitle="props.profile.email" />

    <div class="grid gap-6 lg:grid-cols-2">
        <form class="space-y-3 rounded-lg border border-slate-200 bg-white p-5" @submit.prevent="saveProfile">
            <h2 class="text-sm font-medium">Thông tin</h2>
            <FormField label="Tên hiển thị" for="profile-name" :error="profile.errors.name"><input id="profile-name" v-model="profile.name" :class="inputClass" /></FormField>
            <FormField label="Ngôn ngữ Admin" hint="Ngôn ngữ của thông báo, lỗi và nhãn trạng thái trong Admin." :error="profile.errors.locale">
                <Select v-model="profile.locale" :options="localeOptions" :empty-label="`Mặc định của cửa hàng (${defaultLabel})`" :empty-value="null" />
            </FormField>
            <button type="submit" :class="primaryButton" :disabled="profile.processing">Lưu</button>
        </form>

        <form class="space-y-3 rounded-lg border border-slate-200 bg-white p-5" @submit.prevent="savePassword">
            <h2 class="text-sm font-medium">Đổi mật khẩu</h2>
            <FormField label="Mật khẩu hiện tại" for="current-password" :error="password.errors.current_password">
                <input id="current-password" v-model="password.current_password" :class="inputClass" type="password" autocomplete="current-password" />
            </FormField>
            <FormField label="Mật khẩu mới" for="new-password" :error="password.errors.password">
                <input id="new-password" v-model="password.password" :class="inputClass" type="password" autocomplete="new-password" />
            </FormField>
            <FormField label="Nhập lại mật khẩu mới" for="new-password-confirmation">
                <input id="new-password-confirmation" v-model="password.password_confirmation" :class="inputClass" type="password" autocomplete="new-password" />
            </FormField>
            <button type="submit" :class="primaryButton" :disabled="password.processing">Đổi mật khẩu</button>
        </form>
    </div>
</template>
