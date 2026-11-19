<script setup lang="ts">
import { t } from '@admin/i18n';
import PageHeader from '@admin/Components/PageHeader.vue';
import StatusBadge from '@admin/Components/StatusBadge.vue';
import { confirmAction } from '@admin/confirm';
import { useDateTime } from '@admin/datetime';
import { dangerButton, inputClass, primaryButton, secondaryButton } from '@admin/styles';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import PluginIcon from '../../Components/PluginIcon.vue';
import { type PluginLabels, pluginStatusTone } from '../../pluginLabels';

const props = defineProps<{
    baseUrl: string;
    labels: PluginLabels;
    canManage: boolean;
    canConfigure: boolean;
    plugin: {
        id: string;
        name: string;
        description: string | null;
        author: string | null;
        version: string;
        installed_version: string | null;
        installed_at: string | null;
        kind: string;
        bundled: boolean;
        status: string;
        error: string | null;
        health: { status: 'ok' | 'warning' | 'error'; message: string; checked_at: string } | null;
        has_icon: boolean;
        has_settings: boolean;
        path: string;
        requires_core: string | null;
        requires_extension_api: string | null;
        requires_plugins: Array<{ id: string; constraint: string; name: string | null; status: string }>;
        dependents: string[];
        conflicts: string[];
        capabilities: string[];
        permissions: string[];
        data: { owned: string[]; references: Record<string, string>; retained: string[] };
        problems: string[];
        in_use: string[];
        hooks: string[];
        seeders: Array<{ seeder: string; ran_at: string | null }>;
        admin_pages: Array<{ label: string; url: string }>;
        actions: string[];
    };
}>();

const errors = computed(() => usePage().props.errors as Record<string, string>);
const url = (action: string): string => `${props.baseUrl}/${props.plugin.id}/${action}`;
const can = (action: string): boolean => props.canManage && props.plugin.actions.includes(action);
const busy = ref(false);

function post(action: string, data: Record<string, string | boolean> = {}): void {
    router.post(url(action), data, { preserveScroll: true, onStart: () => (busy.value = true), onFinish: () => (busy.value = false) });
}

// Tắt: còn việc dở dang → chọn ngừng an toàn (drain) hoặc tắt ngay (force, phải tick xác nhận).
const confirmForce = ref(false);
// Gỡ: xoá dữ liệu phải gõ lại id.
const purge = ref(false);
const dropRetained = ref(false);
const confirmId = ref('');

async function uninstall(): Promise<void> {
    if (!purge.value && (await confirmAction({ title: t('extension::admin.plugins_show.uninstall_name', { name: props.plugin.name }), message: t('extension::admin.plugins_show.plugin_s_data_kept'), confirmLabel: t('extension::admin.plugins_show.uninstall_plugin'), tone: 'danger' })) === false) {
        return;
    }
    post('uninstall', { purge: purge.value, drop_retained: purge.value && dropRetained.value, confirm_id: confirmId.value });
}

const formatDate = useDateTime();
</script>

<template>
    <Head :title="`Plugin: ${plugin.name}`" />
    <PageHeader :title="plugin.name" :subtitle="plugin.id">
        <Link :href="baseUrl" :class="secondaryButton">{{ t('extension::admin.plugins_show.plugin_list') }}</Link>
        <Link v-if="plugin.has_settings && canConfigure" :href="url('settings')" :class="primaryButton">{{ t('extension::admin.plugins_show.settings') }}</Link>
    </PageHeader>


    <div class="grid gap-4 lg:grid-cols-3">
        <section class="space-y-4 lg:col-span-2">
            <div class="flex gap-4 rounded-lg border border-slate-200 bg-white p-4">
                <PluginIcon :src="plugin.has_icon ? url('icon') : null" :name="plugin.name" size="lg" />
                <div class="text-sm">
                    <div class="flex flex-wrap items-center gap-2">
                        <StatusBadge :label="labels.plugin_status[plugin.status] ?? plugin.status" :tone="pluginStatusTone(plugin.status)" />
                        <span class="text-slate-500">{{ labels.plugin_kind[plugin.kind] ?? plugin.kind }}</span>
                        <span v-if="plugin.bundled" class="rounded bg-slate-100 px-1.5 py-0.5 text-xs text-slate-600">{{ t('extension::admin.plugins_show.system') }}</span>
                    </div>
                    <p v-if="plugin.description" class="mt-2 text-slate-700">{{ plugin.description }}</p>
                    <p v-if="plugin.error" class="mt-2 text-red-600">{{ plugin.error }}</p>
                    <p v-if="plugin.health" class="mt-2" :class="{ 'text-red-600': plugin.health.status === 'error', 'text-amber-700': plugin.health.status === 'warning', 'text-emerald-700': plugin.health.status === 'ok' }">
                        {{ t('extension::admin.plugins_show.health', { message: plugin.health.message || t('extension::admin.plugins_show.healthy') }) }} <span class="text-xs text-slate-400">({{ formatDate(plugin.health.checked_at) }})</span>
                    </p>
                </div>
            </div>

            <dl class="grid gap-x-6 gap-y-2 rounded-lg border border-slate-200 bg-white p-4 text-sm sm:grid-cols-2">
                <div><dt class="text-slate-500">{{ t('extension::admin.plugins_show.version') }}</dt><dd>{{ plugin.installed_version ?? plugin.version }}<span v-if="plugin.installed_version && plugin.installed_version !== plugin.version" class="text-indigo-600"> → {{ plugin.version }}</span></dd></div>
                <div><dt class="text-slate-500">{{ t('extension::admin.plugins_show.author') }}</dt><dd>{{ plugin.author ?? '—' }}</dd></div>
                <div><dt class="text-slate-500">{{ t('extension::admin.plugins_show.installed') }}</dt><dd>{{ formatDate(plugin.installed_at) }}</dd></div>
                <div><dt class="text-slate-500">{{ t('extension::admin.plugins_show.requires_extension_api') }}</dt><dd class="font-mono">{{ plugin.requires_extension_api ?? '—' }}</dd></div>
                <div v-if="plugin.requires_core"><dt class="text-slate-500">{{ t('extension::admin.plugins_show.requires_core') }}</dt><dd class="font-mono">{{ plugin.requires_core }}</dd></div>
                <div class="sm:col-span-2"><dt class="text-slate-500">{{ t('extension::admin.plugins_show.directory') }}</dt><dd class="font-mono text-xs">{{ plugin.path }}</dd></div>
            </dl>

            <div class="space-y-3 rounded-lg border border-slate-200 bg-white p-4 text-sm">
                <h2 class="font-semibold">{{ t('extension::admin.plugins_show.dependencies') }}</h2>
                <div>
                    <div class="text-slate-500">{{ t('extension::admin.plugins_show.requires_plugins') }}</div>
                    <ul v-if="plugin.requires_plugins.length" class="list-inside list-disc">
                        <li v-for="dependency in plugin.requires_plugins" :key="dependency.id">
                            <Link :href="`${baseUrl}/${dependency.id}`" class="text-indigo-600 hover:underline">{{ dependency.name ?? dependency.id }}</Link>
                            <span class="font-mono text-xs"> {{ dependency.constraint }}</span> · {{ labels.plugin_status[dependency.status] ?? dependency.status }}
                        </li>
                    </ul>
                    <p v-else>—</p>
                </div>
                <div v-if="plugin.capabilities.length">
                    <div class="text-slate-500">{{ t('extension::admin.plugins_show.needs_least_one_enabled_implementation') }}</div>
                    <p class="font-mono text-xs">{{ plugin.capabilities.join(', ') }}</p>
                </div>
                <div>
                    <div class="text-slate-500">{{ t('extension::admin.plugins_show.plugins_depending_plugin') }}</div>
                    <p>
                        <template v-for="(id, index) in plugin.dependents" :key="id"><span v-if="index">, </span><Link :href="`${baseUrl}/${id}`" class="text-indigo-600 hover:underline">{{ id }}</Link></template>
                        <span v-if="!plugin.dependents.length">—</span>
                    </p>
                </div>
                <div v-if="plugin.conflicts.length"><div class="text-slate-500">{{ t('extension::admin.plugins_show.conflicts') }}</div><p class="font-mono text-xs">{{ plugin.conflicts.join(', ') }}</p></div>
                <ul v-if="plugin.problems.length" class="list-inside list-disc text-red-600">
                    <li v-for="problem in plugin.problems" :key="problem">{{ problem }}</li>
                </ul>
            </div>

            <div class="space-y-3 rounded-lg border border-slate-200 bg-white p-4 text-sm">
                <h2 class="font-semibold">{{ t('extension::admin.plugins_show.what_plugin_uses') }}</h2>
                <div><div class="text-slate-500">{{ t('extension::admin.plugins_show.declared_permissions') }}</div><p class="font-mono text-xs">{{ plugin.permissions.join(', ') || '—' }}</p></div>
                <div>
                    <div class="text-slate-500">{{ t('extension::admin.plugins_show.hooks_listened') }}</div>
                    <p v-if="plugin.hooks.length" class="font-mono text-xs">{{ plugin.hooks.join(', ') }}</p>
                    <p v-else>{{ ['enabled', 'draining'].includes(plugin.status) ? '—' : t('extension::admin.plugins_show.only_known_while_plugin_enabled') }}</p>
                </div>
                <div>
                    <div class="text-slate-500">{{ t('extension::admin.plugins_show.data_tables') }}</div>
                    <p v-if="plugin.data.owned.length" class="font-mono text-xs">
                        {{ plugin.data.owned.map((table) => (plugin.data.retained.includes(table) ? t('extension::admin.plugins_show.table_retained', { table }) : table)).join(', ') }}
                    </p>
                    <p v-else>—</p>
                </div>
                <div v-if="plugin.seeders.length">
                    <div class="text-slate-500">{{ t('extension::admin.plugins_show.initial_data_seeder_runs_automatically') }}</div>
                    <ul>
                        <li v-for="item in plugin.seeders" :key="item.seeder" class="font-mono text-xs">
                            {{ item.seeder.split('\\').pop() }} ·
                            <span :class="item.ran_at ? 'text-emerald-700' : 'text-amber-700'">{{ item.ran_at ? t('extension::admin.plugins_show.ran_ran', { ran_at: formatDate(item.ran_at) }) : t('extension::admin.plugins_show.not_run_yet') }}</span>
                        </li>
                    </ul>
                </div>
                <div v-if="plugin.admin_pages.length">
                    <div class="text-slate-500">{{ t('extension::admin.plugins_show.admin_screens') }}</div>
                    <Link v-for="page in plugin.admin_pages" :key="page.url" :href="page.url" class="mr-3 text-indigo-600 hover:underline">{{ page.label }}</Link>
                </div>
            </div>
        </section>

        <aside v-if="canManage" class="space-y-3 text-sm">
            <div class="space-y-3 rounded-lg border border-slate-200 bg-white p-4">
                <h2 class="font-semibold">{{ t('extension::admin.plugins_show.actions') }}</h2>
                <button v-if="can('install')" :class="[primaryButton, 'w-full']" :disabled="busy || plugin.problems.length > 0" @click="post('install')">{{ t('extension::admin.plugins_show.install') }}</button>
                <button v-if="can('upgrade')" :class="[primaryButton, 'w-full']" :disabled="busy" @click="post('upgrade')">{{ t('extension::admin.plugins_show.upgrade_version', { version: plugin.version }) }}</button>
                <button v-if="can('enable')" :class="[primaryButton, 'w-full']" :disabled="busy" @click="post('enable')">{{ plugin.status === 'draining' ? t('extension::admin.plugins_show.cancel_stopping_enable_again') : t('extension::admin.plugins_show.enable') }}</button>

                <template v-if="can('disable')">
                    <template v-if="plugin.in_use.length">
                        <div class="rounded-md bg-amber-50 p-3 text-amber-800">
                            <p class="font-medium">{{ t('extension::admin.plugins_show.work_progress') }}</p>
                            <ul class="list-inside list-disc">
                                <li v-for="reason in plugin.in_use" :key="reason">{{ reason }}</li>
                            </ul>
                        </div>
                        <button v-if="plugin.status === 'enabled'" :class="[secondaryButton, 'w-full']" :disabled="busy" @click="post('disable', { mode: 'drain' })">{{ t('extension::admin.plugins_show.stop_safely_disables_itself_when') }}</button>
                        <label class="flex items-start gap-2 text-xs text-red-700">
                            <input v-model="confirmForce" type="checkbox" class="mt-0.5" />
                            {{ t('extension::admin.plugins_show.i_understand_disabling_now_means') }}
                        </label>
                        <button :class="[dangerButton, 'w-full']" :disabled="busy || !confirmForce" @click="post('disable', { mode: 'force', confirm_force: true })">{{ t('extension::admin.plugins_show.disable_now') }}</button>
                    </template>
                    <button v-else :class="[secondaryButton, 'w-full']" :disabled="busy" @click="post('disable', { mode: 'normal' })">{{ t('extension::admin.plugins_show.off') }}</button>
                </template>

                <template v-if="can('uninstall')">
                    <div class="space-y-2 border-t border-slate-100 pt-3">
                        <label class="flex items-start gap-2"><input v-model="purge" type="checkbox" class="mt-0.5" /> {{ t('extension::admin.plugins_show.also_delete_plugin_s_data') }}</label>
                        <template v-if="purge">
                            <label v-if="plugin.data.retained.length" class="flex items-start gap-2 text-xs"><input v-model="dropRetained" type="checkbox" class="mt-0.5" /> {{ t('extension::admin.plugins_show.also_delete_retained_tables_retained', { retained: plugin.data.retained.join(', ') }) }}</label>
                            <input v-model="confirmId" :class="inputClass" :placeholder="t('extension::admin.plugins_show.type_id_confirm', { id: plugin.id })" />
                            <p v-if="errors.confirm_id" class="text-xs text-red-600">{{ errors.confirm_id }}</p>
                        </template>
                        <button :class="[dangerButton, 'w-full']" :disabled="busy || (purge && confirmId !== plugin.id)" @click="uninstall">{{ t('extension::admin.plugins_show.uninstall_plugin') }}</button>
                    </div>
                </template>
                <p v-if="!plugin.actions.length" class="text-slate-500">{{ t('extension::admin.plugins_show.no_actions') }}</p>
            </div>
            <p class="text-xs text-slate-500">{{ t('extension::admin.plugins_show.same_checks_audit_command') }} <code>vani:plugin:*</code>{{ t('extension::admin.plugins_show.installing_upgrading_run_plugin_s') }}</p>
        </aside>
    </div>
</template>
