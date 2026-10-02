<script setup lang="ts">
import FormField from '@admin/Components/FormField.vue';
import { inputClass } from '@admin/styles';
import type { ExtensionSection, ExtensionValues } from '@admin/types';

/** Phần form do plugin khai báo — render chung theo kiểu trường; lỗi lấy theo khoá extensions.<input>.<section>.<field>. */
const props = defineProps<{ sections: ExtensionSection[]; errors: Record<string, string | undefined> }>();
const values = defineModel<ExtensionValues>({ required: true });
const id = (section: ExtensionSection, field: string): string => `ext-${section.plugin}-${section.key}-${field}`.replace(/[^a-z0-9-]/gi, '-');
const error = (section: ExtensionSection, field: string): string | undefined => props.errors[`extensions.${section.input}.${section.key}.${field}`];
</script>

<template>
    <section v-for="section in sections" :key="`${section.plugin}:${section.key}`" class="space-y-3 rounded-lg border border-slate-200 bg-white p-4" :data-extension="`${section.plugin}:${section.key}`">
        <h2 class="font-semibold">{{ section.label }}</h2>
        <FormField v-for="field in section.fields" :key="field.key" :label="field.label + (field.required ? ' *' : '')" :for="id(section, field.key)" :hint="field.help ?? undefined" :error="error(section, field.key)">
            <textarea v-if="field.type === 'text'" :id="id(section, field.key)" v-model="values[section.input][section.key][field.key] as string | null" :class="inputClass" rows="3" />
            <select v-else-if="field.type === 'select'" :id="id(section, field.key)" v-model="values[section.input][section.key][field.key]" :class="inputClass">
                <option :value="null">—</option>
                <option v-for="(label, value) in field.options" :key="value" :value="value">{{ label }}</option>
            </select>
            <input v-else-if="field.type === 'bool'" :id="id(section, field.key)" v-model="values[section.input][section.key][field.key] as boolean" type="checkbox" />
            <input v-else :id="id(section, field.key)" v-model="values[section.input][section.key][field.key] as string | null" :class="inputClass" :type="field.type === 'int' ? 'number' : field.type === 'date' ? 'date' : 'text'" />
        </FormField>
    </section>
</template>
