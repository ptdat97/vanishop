<script setup lang="ts">
import { Head } from '@inertiajs/vue3';

defineProps<{
    plugins: Array<{
        id: string;
        name: string;
        version: string;
        kind: string;
        status: string;
        error: string | null;
        scopes: string[];
    }>;
    invalid: string[];
}>();
</script>

<template>
    <Head title="Plugin" />
    <h1 class="mb-2 text-xl font-semibold">Plugin</h1>
    <p class="mb-6 text-sm text-slate-500">Cài và bật plugin bằng lệnh <code>php artisan vani:plugin:*</code>.</p>
    <table class="w-full overflow-hidden rounded-lg border border-slate-200 bg-white text-sm">
        <thead class="bg-slate-50 text-left text-slate-500">
            <tr>
                <th class="px-4 py-2">Plugin</th>
                <th class="px-4 py-2">Phiên bản</th>
                <th class="px-4 py-2">Loại</th>
                <th class="px-4 py-2">Trạng thái</th>
                <th class="px-4 py-2">Phạm vi</th>
            </tr>
        </thead>
        <tbody>
            <tr v-for="plugin in plugins" :key="plugin.id" class="border-t border-slate-100">
                <td class="px-4 py-2">
                    <div class="font-medium">{{ plugin.name }}</div>
                    <div class="text-xs text-slate-500">{{ plugin.id }}</div>
                    <div v-if="plugin.error" class="text-xs text-red-600">{{ plugin.error }}</div>
                </td>
                <td class="px-4 py-2">{{ plugin.version }}</td>
                <td class="px-4 py-2">{{ plugin.kind }}</td>
                <td class="px-4 py-2">{{ plugin.status }}</td>
                <td class="px-4 py-2">{{ plugin.scopes.join(', ') || '—' }}</td>
            </tr>
            <tr v-if="!plugins.length">
                <td colspan="5" class="px-4 py-6 text-center text-slate-500">Chưa có plugin nào trong custom/plugin.</td>
            </tr>
        </tbody>
    </table>
    <ul v-if="invalid.length" class="mt-4 space-y-1 text-sm text-red-600">
        <li v-for="message in invalid" :key="message">{{ message }}</li>
    </ul>
</template>
