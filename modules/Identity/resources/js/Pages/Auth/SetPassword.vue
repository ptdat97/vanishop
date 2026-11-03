<script setup lang="ts">
import GuestLayout from '@admin/Layouts/GuestLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';

defineOptions({ layout: GuestLayout });

const props = defineProps<{ action: string; title: string; token: string; email: string }>();
const form = useForm({ token: props.token, email: props.email, password: '', password_confirmation: '' });

function submit(): void {
    form.post(props.action, { onFinish: () => form.reset('password', 'password_confirmation') });
}
</script>

<template>
    <Head :title="title" />
    <div class="flex min-h-screen items-center justify-center px-4">
        <form class="w-full max-w-sm space-y-4 rounded-lg border border-slate-200 bg-white p-6" @submit.prevent="submit">
            <h1 class="text-lg font-semibold">{{ title }}</h1>
            <div>
                <label for="email" class="mb-1 block text-sm font-medium">Email</label>
                <input id="email" v-model="form.email" type="email" autocomplete="username" required class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm" />
                <p v-if="form.errors.email" class="mt-1 text-sm text-red-600">{{ form.errors.email }}</p>
            </div>
            <div>
                <label for="password" class="mb-1 block text-sm font-medium">Mật khẩu mới</label>
                <input id="password" v-model="form.password" type="password" autocomplete="new-password" required class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm" />
                <p v-if="form.errors.password" class="mt-1 text-sm text-red-600">{{ form.errors.password }}</p>
                <p v-else class="mt-1 text-xs text-slate-500">Tối thiểu 12 ký tự.</p>
            </div>
            <div>
                <label for="password_confirmation" class="mb-1 block text-sm font-medium">Nhập lại mật khẩu</label>
                <input id="password_confirmation" v-model="form.password_confirmation" type="password" autocomplete="new-password" required class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm" />
            </div>
            <button type="submit" :disabled="form.processing" class="w-full rounded-md bg-indigo-600 px-3 py-2 text-sm font-medium text-white disabled:opacity-50">Đặt mật khẩu</button>
        </form>
    </div>
</template>
