<script setup lang="ts">
import { t } from '@admin/i18n';
import PageHeader from '@admin/Components/PageHeader.vue';
import Select from '@admin/Components/Select.vue';
import { secondaryButton } from '@admin/styles';
import { Head, Link, router } from '@inertiajs/vue3';
import { computed } from 'vue';
import SettingGroupIcon from '../../Components/SettingGroupIcon.vue';
import SettingsForm, { type SettingField } from '../../Components/SettingsForm.vue';

type NavLink = { code: string; title: string; url: string; icon?: string; namespace?: string };

/**
 * Một trang cấu hình: nhóm của Cài đặt hệ thống (`core`) hoặc một plugin. Cột trái chuyển nhanh giữa các nhóm/plugin
 * (điện thoại: ô chọn).
 */
const props = defineProps<{
    baseUrl: string;
    action: string;
    namespace: string;
    section: { code: string; title: string; url: string; description: string; icon?: string };
    fields: SettingField[];
    active: string;
    nav: { system: NavLink[]; plugins: NavLink[] };
}>();

// Điện thoại: một ô chọn (nhóm hệ thống trước, plugin sau — tiền tố cho biết phần).
const mobileOptions = computed(() => [
    ...props.nav.system.map((link) => ({ value: link.code, label: `${t('extension::admin.settings_index.system_settings')} · ${link.title}` })),
    ...props.nav.plugins.map((link) => ({ value: link.code, label: `${t('extension::admin.settings_index.plugin_settings')} · ${link.title}` })),
]);
function go(code: string | null): void {
    const link = [...props.nav.system, ...props.nav.plugins].find((item) => item.code === code);
    if (link) router.visit(link.url);
}

const linkClass = (active: boolean): string =>
    `flex items-center gap-2 rounded-lg px-3 py-2 text-sm transition ${active ? 'bg-indigo-50 font-medium text-indigo-700' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900'}`;
</script>

<template>
    <div>
        <Head :title="t('extension::admin.settings_show.settings_title', { title: section.title })" />
        <PageHeader :title="section.title" :subtitle="section.description">
            <Link :href="baseUrl" :class="secondaryButton">{{ t('extension::admin.settings_show.back_list') }}</Link>
        </PageHeader>

        <div class="grid gap-6 lg:grid-cols-[15rem_minmax(0,1fr)]">
            <nav :aria-label="t('extension::admin.settings_show.settings_navigation')" data-settings-nav>
                <div class="lg:hidden">
                    <Select
                        :model-value="active"
                        :options="mobileOptions"
                        :aria-label="t('extension::admin.settings_show.settings_navigation')"
                        @update:model-value="go"
                    />
                </div>
                <div class="hidden space-y-5 lg:sticky lg:top-4 lg:block">
                    <div v-if="nav.system.length">
                        <p class="mb-1 px-3 text-xs font-semibold uppercase tracking-wide text-slate-400">{{ t('extension::admin.settings_index.system_settings') }}</p>
                        <ul class="space-y-0.5">
                            <li v-for="link in nav.system" :key="link.code">
                                <Link :href="link.url" :class="linkClass(link.code === active)" :aria-current="link.code === active ? 'page' : undefined">
                                    <SettingGroupIcon :name="link.icon ?? 'other'" />
                                    {{ link.title }}
                                </Link>
                            </li>
                        </ul>
                    </div>
                    <div v-if="nav.plugins.length">
                        <p class="mb-1 px-3 text-xs font-semibold uppercase tracking-wide text-slate-400">{{ t('extension::admin.settings_index.plugin_settings') }}</p>
                        <ul class="max-h-[50vh] space-y-0.5 overflow-y-auto">
                            <li v-for="link in nav.plugins" :key="link.code">
                                <Link :href="link.url" :class="linkClass(link.code === active)" :aria-current="link.code === active ? 'page' : undefined">
                                    <span class="truncate">{{ link.title }}</span>
                                </Link>
                            </li>
                        </ul>
                    </div>
                </div>
            </nav>

            <div class="min-w-0 space-y-3">
                <p class="text-xs text-slate-500">{{ t('extension::admin.settings_show.leave_empty_use_default_value') }}</p>
                <!-- key: phòng khi chuyển trang giữ state (preserveState) — form luôn theo nhóm/plugin đang mở. -->
                <SettingsForm :key="active" :action="action" :namespace="namespace" :fields="fields" />
            </div>
        </div>
    </div>
</template>
