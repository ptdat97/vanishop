<script setup lang="ts">
import DataTable from '@admin/Components/DataTable.vue';
import PageHeader from '@admin/Components/PageHeader.vue';
import Select from '@admin/Components/Select.vue';
import StatusBadge from '@admin/Components/StatusBadge.vue';
import { codeOptions, labelOptions } from '@admin/options';
import { inputClass } from '@admin/styles';
import { Head, Link } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import PluginIcon from '../../Components/PluginIcon.vue';
import { type PluginLabels, pluginStatusTone } from '../../pluginLabels';

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

const props = defineProps<{ baseUrl: string; labels: PluginLabels; plugins: Plugin[]; kinds: string[]; invalid: string[] }>();

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
const columns = [
    { key: 'name', label: 'Plugin' },
    { key: 'kind', label: 'Loại' },
    { key: 'version', label: 'Phiên bản' },
    { key: 'status', label: 'Trạng thái' },
    { key: 'actions', label: '', align: 'right' as const, class: 'whitespace-nowrap' },
];
</script>

<template>
    <Head title="Plugin" />
    <PageHeader title="Plugin" :subtitle="`${plugins.length} plugin trong custom/plugin`" />

    <div class="mb-3 flex flex-wrap gap-2">
        <input v-model="q" :class="[inputClass, 'w-72']" placeholder="Tên, id, mô tả hoặc tác giả" />
        <div class="w-48"><Select v-model="kind" :options="codeOptions(usedKinds, labels.plugin_kind)" empty-label="Mọi loại" aria-label="Loại plugin" /></div>
        <div class="w-48"><Select v-model="status" :options="labelOptions(labels.plugin_status)" empty-label="Mọi trạng thái" aria-label="Trạng thái" /></div>
    </div>

    <DataTable :columns="columns" :rows="shown" :row-key="(plugin) => plugin.id" :row-class="() => 'align-top'" :empty-text="plugins.length ? 'Không có plugin phù hợp.' : 'Chưa có plugin nào trong custom/plugin.'">
        <template #cell-name="{ row }">
            <div class="flex gap-3">
                <PluginIcon :src="row.has_icon ? `${baseUrl}/${row.id}/icon` : null" :name="row.name" />
                <div>
                    <Link :href="`${baseUrl}/${row.id}`" class="font-medium hover:underline">{{ row.name }}</Link>
                    <span v-if="row.bundled" class="ml-1 rounded bg-slate-100 px-1.5 py-0.5 text-xs text-slate-600" title="Plugin hệ thống: vani:install tự cài + bật">Hệ thống</span>
                    <div class="text-xs text-slate-500">{{ row.id }}<span v-if="row.author"> · {{ row.author }}</span></div>
                    <div v-if="row.description" class="mt-0.5 text-xs text-slate-600">{{ row.description }}</div>
                    <div v-if="row.error" class="text-xs text-red-600">{{ row.error }}</div>
                    <div v-if="row.health && row.health.status !== 'ok'" class="text-xs" :class="row.health.status === 'error' ? 'text-red-600' : 'text-amber-700'">
                        {{ row.health.status === 'error' ? 'Lỗi' : 'Cảnh báo' }}: {{ row.health.message }}
                    </div>
                </div>
            </div>
        </template>
        <template #cell-kind="{ row }">{{ labels.plugin_kind[row.kind] ?? row.kind }}</template>
        <template #cell-version="{ row }">
            {{ row.installed_version ?? row.version }}
            <div v-if="row.upgrade_available" class="text-xs text-indigo-600">Có bản {{ row.version }}</div>
        </template>
        <template #cell-status="{ row }"><StatusBadge :label="labels.plugin_status[row.status] ?? row.status" :tone="pluginStatusTone(row.status)" /></template>
        <template #cell-actions="{ row }">
            <Link v-if="row.has_settings" :href="`${baseUrl}/${row.id}/settings`" class="mr-3 text-indigo-600 hover:underline">Cấu hình</Link>
            <Link :href="`${baseUrl}/${row.id}`" class="text-indigo-600 hover:underline">Chi tiết</Link>
        </template>
    </DataTable>
    <ul v-if="invalid.length" class="mt-4 space-y-1 text-sm text-red-600">
        <li v-for="message in invalid" :key="message">{{ message }}</li>
    </ul>
</template>
