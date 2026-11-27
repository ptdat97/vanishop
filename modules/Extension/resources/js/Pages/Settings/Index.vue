<script setup lang="ts">
import { t, tc } from '@admin/i18n';
import PageHeader from '@admin/Components/PageHeader.vue';
import { inputClass, secondaryButton } from '@admin/styles';
import { confirmAction } from '@admin/confirm';
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import SettingGroupIcon from '../../Components/SettingGroupIcon.vue';

type SystemGroup = { code: string; icon: string; title: string; url: string; description: string; labels: string[]; count: number; customized: number };
type PluginCard = { code: string; namespace: string; title: string; url: string; count: number; customized: number };

/**
 * Admin → Cài đặt: "Cài đặt hệ thống" (nhóm nhỏ — Cửa hàng, Giao diện, SEO…) và "Cài đặt Plugin" (mỗi plugin một thẻ).
 * Ô tìm lọc theo tên nhóm/plugin và tên từng mục cấu hình.
 */
const props = defineProps<{ system: SystemGroup[]; plugins: PluginCard[]; clearCacheUrl: string | null }>();

const query = ref('');
const normalize = (text: string): string => text.normalize('NFD').replace(/[̀-ͯ]/g, '').replace(/đ/gi, 'd').toLowerCase();
const matches = (...texts: string[]): boolean => {
    const needle = normalize(query.value.trim());
    return needle === '' || texts.some((text) => normalize(text).includes(needle));
};
const system = computed(() => props.system.filter((group) => matches(group.title, group.description, ...group.labels)));
const plugins = computed(() => props.plugins.filter((plugin) => matches(plugin.title, plugin.namespace)));
const clearing = ref(false);

async function clearCache(): Promise<void> {
    if (props.clearCacheUrl === null || clearing.value) return;
    const confirmed = await confirmAction({
        title: t('extension::admin.settings_index.clear_cache_confirm'),
        message: t('extension::admin.settings_index.clear_cache_message'),
        confirmLabel: t('extension::admin.settings_index.clear_cache'),
    });
    if (confirmed === false) return;

    clearing.value = true;
    router.post(props.clearCacheUrl, {}, { preserveScroll: true, onFinish: () => (clearing.value = false) });
}

const preview = (labels: string[]): string => labels.slice(0, 3).join(' · ') + (labels.length > 3 ? ' …' : '');
</script>

<template>
    <div class="space-y-8">
        <Head :title="t('extension::admin.settings_index.settings')" />
        <PageHeader :title="t('extension::admin.settings_index.settings')" :subtitle="t('extension::admin.settings_index.choose_group_view_edit_settings')">
            <input
                v-model="query"
                type="search"
                :class="[inputClass, 'w-64 max-w-full']"
                :placeholder="t('extension::admin.settings_index.search_settings')"
                :aria-label="t('extension::admin.settings_index.search_settings')"
                data-settings-search
            />
        </PageHeader>

        <section v-if="props.system.length" aria-labelledby="settings-system" data-settings-section="system">
            <h2 id="settings-system" class="text-base font-semibold text-slate-900">{{ t('extension::admin.settings_index.system_settings') }}</h2>
            <p class="mt-1 text-sm text-slate-500">{{ t('extension::admin.settings_index.system_settings_hint') }}</p>
            <div v-if="system.length" class="mt-4 grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                <Link
                    v-for="group in system"
                    :key="group.code"
                    :href="group.url"
                    class="group flex gap-4 rounded-xl border border-slate-200 bg-white p-5 text-sm shadow-xs transition hover:border-slate-300 hover:shadow-sm focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600"
                    :data-settings-card="group.code"
                >
                    <span class="flex size-10 shrink-0 items-center justify-center rounded-lg bg-indigo-50 text-indigo-600">
                        <SettingGroupIcon :name="group.icon" />
                    </span>
                    <span class="min-w-0 flex-1">
                        <span class="flex items-center justify-between gap-2">
                            <span class="text-base font-semibold text-slate-900">{{ group.title }}</span>
                            <span class="shrink-0 text-slate-400 transition group-hover:translate-x-1 group-hover:text-slate-700" aria-hidden="true">→</span>
                        </span>
                        <span class="mt-1 block text-slate-600">{{ group.description }}</span>
                        <span class="mt-2 block truncate text-xs text-slate-400">{{ preview(group.labels) }}</span>
                        <span class="mt-3 flex flex-wrap gap-2 text-xs">
                            <span class="rounded-full bg-slate-100 px-2 py-0.5 text-slate-600">{{ tc('extension::admin.settings_index.count_settings', group.count, { count: group.count }) }}</span>
                            <span v-if="group.customized" class="rounded-full bg-amber-50 px-2 py-0.5 text-amber-700">{{ tc('extension::admin.settings_index.count_customized', group.customized, { count: group.customized }) }}</span>
                        </span>
                    </span>
                </Link>
            </div>
            <p v-else class="mt-4 text-sm text-slate-500">{{ t('extension::admin.settings_index.no_matching_settings') }}</p>
        </section>

        <section aria-labelledby="settings-plugins" data-settings-section="plugins">
            <h2 id="settings-plugins" class="text-base font-semibold text-slate-900">{{ t('extension::admin.settings_index.plugin_settings') }}</h2>
            <p class="mt-1 text-sm text-slate-500">{{ t('extension::admin.settings_index.plugin_settings_hint') }}</p>
            <div v-if="plugins.length" class="mt-4 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                <Link
                    v-for="plugin in plugins"
                    :key="plugin.code"
                    :href="plugin.url"
                    class="group flex items-center justify-between gap-3 rounded-xl border border-slate-200 bg-white p-4 text-sm shadow-xs transition hover:border-slate-300 hover:shadow-sm focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600"
                    :data-settings-card="plugin.code"
                >
                    <span class="min-w-0">
                        <span class="block font-semibold text-slate-900">{{ plugin.title }}</span>
                        <span class="mt-0.5 block truncate font-mono text-xs text-slate-500">{{ plugin.namespace }}</span>
                        <span class="mt-2 flex flex-wrap gap-2 text-xs">
                            <span class="rounded-full bg-slate-100 px-2 py-0.5 text-slate-600">{{ tc('extension::admin.settings_index.count_settings', plugin.count, { count: plugin.count }) }}</span>
                            <span v-if="plugin.customized" class="rounded-full bg-amber-50 px-2 py-0.5 text-amber-700">{{ tc('extension::admin.settings_index.count_customized', plugin.customized, { count: plugin.customized }) }}</span>
                        </span>
                    </span>
                    <span class="shrink-0 text-slate-400 transition group-hover:translate-x-1 group-hover:text-slate-700" aria-hidden="true">→</span>
                </Link>
            </div>
            <p v-else-if="props.plugins.length" class="mt-4 text-sm text-slate-500">{{ t('extension::admin.settings_index.no_matching_settings') }}</p>
            <div v-else class="mt-4 rounded-xl border border-dashed border-slate-300 bg-white p-6 text-center text-sm text-slate-500">
                {{ t('extension::admin.settings_index.no_plugin_settings') }}
            </div>
        </section>

        <section v-if="props.clearCacheUrl" aria-labelledby="settings-cache" data-settings-section="cache">
            <h2 id="settings-cache" class="text-base font-semibold text-slate-900">{{ t('extension::admin.settings_index.cache') }}</h2>
            <div class="mt-4 flex flex-wrap items-center justify-between gap-4 rounded-xl border border-slate-200 bg-white p-5 text-sm shadow-xs">
                <p class="max-w-2xl text-slate-600">{{ t('extension::admin.settings_index.cache_hint') }}</p>
                <button
                    type="button"
                    :class="secondaryButton"
                    :disabled="clearing"
                    data-clear-cache
                    @click="clearCache"
                >
                    {{ t('extension::admin.settings_index.clear_cache') }}
                </button>
            </div>
        </section>
    </div>
</template>
