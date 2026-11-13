<script setup lang="ts">
import GuestLayout from '@admin/Layouts/GuestLayout.vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

defineOptions({ layout: GuestLayout });

const props = defineProps<{ action: string; loginUrl: string }>();
const page = usePage();
const sent = computed(() => (page.flash.toasts as Array<{ type: string; message: string }> | undefined)?.find((item) => item.type === 'success')?.message ?? null);
const form = useForm({ email: '' });
</script>

<template>
    <Head title="Quên mật khẩu" />
    <div class="flex min-h-screen items-center justify-center px-4">
        <form class="w-full max-w-sm space-y-4 rounded-lg border border-slate-200 bg-white p-6" @submit.prevent="form.post(props.action)">
            <h1 class="text-lg font-semibold">Quên mật khẩu</h1>
            <p v-if="sent" class="rounded-md bg-emerald-50 px-3 py-2 text-sm text-emerald-800">{{ sent }}</p>
            <p v-else class="text-sm text-slate-600">Nhập email đăng nhập quản trị. Chúng tôi gửi link đặt lại mật khẩu (hiệu lực 60 phút).</p>
            <div>
                <label for="email" class="mb-1 block text-sm font-medium">Email</label>
                <input id="email" v-model="form.email" type="email" autocomplete="username" required class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm" />
                <p v-if="form.errors.email" class="mt-1 text-sm text-red-600">{{ form.errors.email }}</p>
            </div>
            <button type="submit" :disabled="form.processing" class="w-full rounded-md bg-indigo-600 px-3 py-2 text-sm font-medium text-white disabled:opacity-50">Gửi link</button>
            <Link :href="loginUrl" class="block text-center text-sm text-indigo-600 hover:underline">Quay lại đăng nhập</Link>
        </form>
    </div>
</template>
