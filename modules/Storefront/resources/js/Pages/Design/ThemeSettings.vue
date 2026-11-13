<script setup lang="ts">
import { useAdminForm } from '@admin/forms';
import DateInput from '@admin/Components/DateInput.vue';
import FormField from '@admin/Components/FormField.vue';
import PageHeader from '@admin/Components/PageHeader.vue';
import Select from '@admin/Components/Select.vue';
import { labelOptions } from '@admin/options';
import { inputClass, primaryButton } from '@admin/styles';
import type { ExtensionField, ExtensionFieldValue } from '@admin/types';
import { Head } from '@inertiajs/vue3';
import { computed } from 'vue';
import DesignTabs from '../../Components/DesignTabs.vue';

type Field = ExtensionField & { format: 'url' | 'email' | null; translatable: boolean };
type Section = { code: string; label: string; fields: Field[] };
type Value = ExtensionFieldValue | Record<string, ExtensionFieldValue>;

/** Form sinh từ schema `settings` của theme (§3.16-3): Core không cứng trường nào; trường dịch có một ô mỗi ngôn ngữ. */
const props = defineProps<{ sections: Section[]; values: Record<string, Record<string, Value>>; locales: string[]; defaultLocale: string }>();

const initial: Record<string, Record<string, Value>> = {};
for (const section of props.sections) {
    initial[section.code] = {};
    for (const field of section.fields) {
        const stored = props.values[section.code]?.[field.key];
        if (field.translatable) {
            const byLocale = (stored && typeof stored === 'object' ? stored : {}) as Record<string, ExtensionFieldValue>;
            initial[section.code][field.key] = Object.fromEntries(props.locales.map((locale) => [locale, byLocale[locale] ?? null]));
        } else {
            initial[section.code][field.key] = (stored as ExtensionFieldValue | undefined) ?? (field.type === 'bool' ? false : null);
        }
    }
}

const { form, save } = useAdminForm<{ settings: Record<string, Record<string, Value>> }>({ settings: initial });
const errors = computed(() => form.errors as Record<string, string | undefined>);
const localized = (section: string, field: string): Record<string, ExtensionFieldValue> => form.settings[section][field] as Record<string, ExtensionFieldValue>;
const inputType = (field: Field): string => (field.type === 'int' ? 'number' : field.format === 'email' ? 'email' : field.format === 'url' ? 'url' : 'text');

function submit(): void {
    save('put', window.location.pathname);
}
</script>

<template>
    <Head title="Đầu & chân trang" />
    <p class="mb-2 text-sm text-slate-500">Giao diện</p>
    <PageHeader title="Đầu & chân trang" subtitle="Các trường do giao diện đang dùng khai báo">
        <button v-if="sections.length" type="button" :class="primaryButton" :disabled="form.processing" @click="submit">Lưu</button>
    </PageHeader>
    <DesignTabs active="theme-settings" />

    <p v-if="!sections.length" class="rounded-lg border border-dashed border-slate-300 p-6 text-center text-sm text-slate-500">Giao diện đang dùng không khai báo cấu hình đầu/chân trang.</p>

    <div class="space-y-4">
        <section v-for="section in sections" :key="section.code" class="rounded-lg border border-slate-200 bg-white p-4" :data-section="section.code">
            <h2 class="mb-3 font-semibold">{{ section.label }}</h2>
            <div class="grid gap-3 md:grid-cols-2">
                <template v-for="field in section.fields" :key="field.key">
                    <template v-if="field.translatable">
                        <FormField
                            v-for="locale in locales"
                            :key="locale"
                            :label="`${field.label} (${locale.toUpperCase()})` + (field.required && locale === defaultLocale ? ' *' : '')"
                            :hint="locale === defaultLocale ? (field.help ?? undefined) : 'Để trống = dùng bản ngôn ngữ mặc định'"
                            :error="errors[`settings.${section.code}.${field.key}.${locale}`]"
                        >
                            <textarea v-if="field.type === 'text'" v-model="localized(section.code, field.key)[locale] as string | null" :class="inputClass" rows="3" />
                            <input v-else v-model="localized(section.code, field.key)[locale] as string | null" :class="inputClass" type="text" />
                        </FormField>
                    </template>
                    <FormField v-else :label="field.label + (field.required ? ' *' : '')" :hint="field.help ?? undefined" :error="errors[`settings.${section.code}.${field.key}`]">
                        <textarea v-if="field.type === 'text'" v-model="form.settings[section.code][field.key] as string | null" :class="inputClass" rows="3" />
                        <Select v-else-if="field.type === 'select'" v-model="form.settings[section.code][field.key] as string | null" :options="labelOptions(field.options)" empty-label="—" :empty-value="null" />
                        <DateInput v-else-if="field.type === 'date'" v-model="form.settings[section.code][field.key] as string | null" />
                        <input v-else-if="field.type === 'bool'" v-model="form.settings[section.code][field.key] as boolean" type="checkbox" />
                        <input v-else v-model="form.settings[section.code][field.key] as string | null" :class="inputClass" :type="inputType(field)" />
                    </FormField>
                </template>
            </div>
        </section>
    </div>
</template>
