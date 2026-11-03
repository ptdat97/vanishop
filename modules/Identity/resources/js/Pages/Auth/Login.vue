<script setup lang="ts">
import GuestLayout from '@admin/Layouts/GuestLayout.vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

defineOptions({ layout: GuestLayout });

const props = defineProps<{ action: string; forgotUrl: string }>();
const page = usePage<{ flash?: { success?: string | null } }>();
const notice = computed(() => page.props.flash?.success);

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

function submit(): void {
    form.post(props.action, { onFinish: () => form.reset('password') });
}
</script>

<template>
    <Head title="Đăng nhập" />
    <div class="flex min-h-screen items-center justify-center px-4">
        <form class="w-full max-w-sm space-y-4 rounded-lg border border-slate-200 bg-white p-6" @submit.prevent="submit">
            <h1 class="text-lg font-semibold">Đăng nhập quản trị</h1>
            <p v-if="notice" class="rounded-md bg-emerald-50 px-3 py-2 text-sm text-emerald-800">{{ notice }}</p>
            <div>
                <label for="email" class="mb-1 block text-sm font-medium">Email</label>
                <input id="email" v-model="form.email" type="email" autocomplete="username" required class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm" />
                <p v-if="form.errors.email" class="mt-1 text-sm text-red-600">{{ form.errors.email }}</p>
            </div>
            <div>
                <label for="password" class="mb-1 block text-sm font-medium">Mật khẩu</label>
                <input id="password" v-model="form.password" type="password" autocomplete="current-password" required class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm" />
                <p v-if="form.errors.password" class="mt-1 text-sm text-red-600">{{ form.errors.password }}</p>
            </div>
            <label class="flex items-center gap-2 text-sm">
                <input v-model="form.remember" type="checkbox" />
                Ghi nhớ đăng nhập
            </label>
            <button type="submit" :disabled="form.processing" class="w-full rounded-md bg-indigo-600 px-3 py-2 text-sm font-medium text-white disabled:opacity-50">
                Đăng nhập
            </button>
            <Link :href="forgotUrl" class="block text-center text-sm text-indigo-600 hover:underline">Quên mật khẩu?</Link>
        </form>
    </div>
</template>
