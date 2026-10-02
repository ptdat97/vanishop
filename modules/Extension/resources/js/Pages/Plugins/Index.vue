<script setup lang="ts">
import { Head } from '@inertiajs/vue3';

defineProps<{
    plugins: Array<{
        id: string;
        name: string;
        version: string;
        kind: string;
        bundled: boolean;
        status: string;
        error: string | null;
    }>;
    invalid: string[];
}>();
</script>

<template>
    <Head title="Plugin" />
    <h1 class="mb-2 text-xl font-semibold">Plugin</h1>
    <p class="mb-6 text-sm text-slate-500">Cài và bật plugin bằng lệnh <code>php artisan vani:plugin:*</code>. Plugin hệ thống được <code>vani:install</code> cài sẵn; không tắt được plugin là implementation cuối cùng của một extension point bắt buộc.</p>
    <table class="w-full overflow-hidden rounded-lg border border-slate-200 bg-white text-sm">
        <thead class="bg-slate-50 text-left text-slate-500">
            <tr>
                <th class="px-4 py-2">Plugin</th>
                <th class="px-4 py-2">Phiên bản</th>
                <th class="px-4 py-2">Loại</th>
                <th class="px-4 py-2">Trạng thái</th>
            </tr>
        </thead>
        <tbody>
            <tr v-for="plugin in plugins" :key="plugin.id" class="border-t border-slate-100">
                <td class="px-4 py-2">
                    <div class="font-medium">
                        {{ plugin.name }}
                        <span v-if="plugin.bundled" class="ml-1 rounded bg-slate-100 px-1.5 py-0.5 text-xs font-normal text-slate-600" title="Plugin hệ thống: vani:install tự cài + bật">Hệ thống</span>
                    </div>
                    <div class="text-xs text-slate-500">{{ plugin.id }}</div>
                    <div v-if="plugin.error" class="text-xs text-red-600">{{ plugin.error }}</div>
                </td>
                <td class="px-4 py-2">{{ plugin.version }}</td>
                <td class="px-4 py-2">{{ plugin.kind }}</td>
                <td class="px-4 py-2">{{ plugin.status }}</td>
            </tr>
            <tr v-if="!plugins.length">
                <td colspan="4" class="px-4 py-6 text-center text-slate-500">Chưa có plugin nào trong custom/plugin.</td>
            </tr>
        </tbody>
    </table>
    <ul v-if="invalid.length" class="mt-4 space-y-1 text-sm text-red-600">
        <li v-for="message in invalid" :key="message">{{ message }}</li>
    </ul>
</template>
