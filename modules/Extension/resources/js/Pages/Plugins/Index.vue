<script setup lang="ts">
import PageHeader from '@admin/Components/PageHeader.vue';
import { inputClass } from '@admin/styles';
import { Head, Link } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import PluginIcon from '../../Components/PluginIcon.vue';
import { kindLabels, statusClasses, statusLabels } from '../../pluginLabels';

type Plugin = {
    id: string;
    name: string;
    description: string | null;
    author: string | null;
    version: string;
    installed_version: string | null;
    kind: string;
    bundled: boolean;
    status: string;
    error: string | null;
    health: { status: 'ok' | 'warning' | 'error'; message: string; checked_at: string } | null;
    has_icon: boolean;
    upgrade_available: boolean;
    has_settings: boolean;
};

const props = defineProps<{ baseUrl: string; plugins: Plugin[]; kinds: string[]; invalid: string[] }>();

const q = ref('');
const kind = ref('');
const status = ref('');
const shown = computed(() => {
    const term = q.value.trim().toLowerCase();

    return props.plugins.filter(
        (plugin) =>
            (!term || [plugin.name, plugin.id, plugin.description ?? '', plugin.author ?? ''].some((text) => text.toLowerCase().includes(term))) &&
            (!kind.value || plugin.kind === kind.value) &&
            (!status.value || plugin.status === status.value),
    );
});
const usedKinds = computed(() => props.kinds.filter((code) => props.plugins.some((plugin) => plugin.kind === code)));
</script>

<template>
    <Head title="Plugin" />
    <PageHeader title="Plugin" :subtitle="`${plugins.length} plugin trong custom/plugin`" />

    <div class="mb-3 flex flex-wrap gap-2">
        <input v-model="q" :class="[inputClass, 'w-72']" placeholder="Tên, id, mô tả hoặc tác giả" />
        <select v-model="kind" :class="[inputClass, 'w-48']">
            <option value="">Mọi loại</option>
            <option v-for="code in usedKinds" :key="code" :value="code">{{ kindLabels[code] ?? code }}</option>
        </select>
        <select v-model="status" :class="[inputClass, 'w-48']">
            <option value="">Mọi trạng thái</option>
            <option v-for="(label, code) in statusLabels" :key="code" :value="code">{{ label }}</option>
        </select>
    </div>

    <table class="w-full overflow-hidden rounded-lg border border-slate-200 bg-white text-sm">
        <thead class="bg-slate-50 text-left text-slate-500">
            <tr>
                <th class="px-4 py-2">Plugin</th>
                <th class="px-4 py-2">Loại</th>
                <th class="px-4 py-2">Phiên bản</th>
                <th class="px-4 py-2">Trạng thái</th>
                <th />
            </tr>
        </thead>
        <tbody>
            <tr v-for="plugin in shown" :key="plugin.id" class="border-t border-slate-100 align-top">
                <td class="px-4 py-2">
                    <div class="flex gap-3">
                        <PluginIcon :src="plugin.has_icon ? `${baseUrl}/${plugin.id}/icon` : null" :name="plugin.name" />
                        <div>
                            <Link :href="`${baseUrl}/${plugin.id}`" class="font-medium hover:underline">{{ plugin.name }}</Link>
                            <span v-if="plugin.bundled" class="ml-1 rounded bg-slate-100 px-1.5 py-0.5 text-xs text-slate-600" title="Plugin hệ thống: vani:install tự cài + bật">Hệ thống</span>
                            <div class="text-xs text-slate-500">{{ plugin.id }}<span v-if="plugin.author"> · {{ plugin.author }}</span></div>
                            <div v-if="plugin.description" class="mt-0.5 text-xs text-slate-600">{{ plugin.description }}</div>
                            <div v-if="plugin.error" class="text-xs text-red-600">{{ plugin.error }}</div>
                            <div v-if="plugin.health && plugin.health.status !== 'ok'" class="text-xs" :class="plugin.health.status === 'error' ? 'text-red-600' : 'text-amber-700'">
                                {{ plugin.health.status === 'error' ? 'Lỗi' : 'Cảnh báo' }}: {{ plugin.health.message }}
                            </div>
                        </div>
                    </div>
                </td>
                <td class="px-4 py-2">{{ kindLabels[plugin.kind] ?? plugin.kind }}</td>
                <td class="px-4 py-2">
                    {{ plugin.installed_version ?? plugin.version }}
                    <div v-if="plugin.upgrade_available" class="text-xs text-indigo-600">Có bản {{ plugin.version }}</div>
                </td>
                <td class="px-4 py-2">
                    <span :class="[statusClasses[plugin.status], 'rounded px-2 py-0.5 text-xs']">{{ statusLabels[plugin.status] ?? plugin.status }}</span>
                </td>
                <td class="px-4 py-2 text-right whitespace-nowrap">
                    <Link v-if="plugin.has_settings" :href="`${baseUrl}/${plugin.id}/settings`" class="mr-3 text-indigo-600 hover:underline">Cấu hình</Link>
                    <Link :href="`${baseUrl}/${plugin.id}`" class="text-indigo-600 hover:underline">Chi tiết</Link>
                </td>
            </tr>
            <tr v-if="!shown.length">
                <td colspan="5" class="px-4 py-6 text-center text-slate-500">{{ plugins.length ? 'Không có plugin phù hợp.' : 'Chưa có plugin nào trong custom/plugin.' }}</td>
            </tr>
        </tbody>
    </table>
    <ul v-if="invalid.length" class="mt-4 space-y-1 text-sm text-red-600">
        <li v-for="message in invalid" :key="message">{{ message }}</li>
    </ul>
</template>
