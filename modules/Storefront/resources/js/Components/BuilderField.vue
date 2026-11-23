<script setup lang="ts">
import DateInput from '@admin/Components/DateInput.vue';
import FormField from '@admin/Components/FormField.vue';
import MediaField from '@admin/Components/Media/MediaField.vue';
import Select from '@admin/Components/Select.vue';
import Sortable from '@admin/Components/Sortable.vue';
import SortHandle from '@admin/Components/SortHandle.vue';
import { t } from '@admin/i18n';
import { labelOptions } from '@admin/options';
import { dangerButton, inputClass, secondaryButton } from '@admin/styles';
import { computed } from 'vue';
import type { BuilderField, BuilderValue, RepeaterRow } from '../builderFields';
import RichTextEditor from './RichTextEditor.vue';

const props = defineProps<{
    field: BuilderField;
    path: string;
    errors: Record<string, string | undefined>;
    richText?: boolean;
}>();
const value = defineModel<BuilderValue>({ required: true });
const rows = computed<RepeaterRow[]>({
    get: () => Array.isArray(value.value) ? value.value : [],
    set: (ordered) => (value.value = ordered),
});

function addRow(): void {
    if (props.field.type === 'repeater' && rows.value.length < props.field.max) {
        const row = Object.fromEntries(props.field.fields.map((field) => [field.key, field.type === 'bool' ? false : null]));
        rows.value = [...rows.value, row];
    }
}

function updateCell(row: RepeaterRow, key: string, cell: BuilderValue): void {
    if (!Array.isArray(cell)) row[key] = cell;
}
</script>

<template>
    <fieldset v-if="field.type === 'repeater'" class="min-w-0 space-y-3 rounded-md border border-slate-200 p-3 md:col-span-2" data-builder-repeater>
        <legend class="px-1 text-sm font-medium">{{ field.label }}{{ field.required ? ' *' : '' }}</legend>
        <p v-if="errors[path]" class="text-sm text-red-600">{{ errors[path] }}</p>
        <p v-else-if="field.help" class="text-sm text-slate-500">{{ field.help }}</p>
        <Sortable v-model="rows" v-slot="{ item: row, index, handle }" list-class="space-y-2" item-class="rounded-md border border-slate-200 p-3">
            <div class="mb-2 flex items-center gap-2">
                <SortHandle v-bind="handle" />
                <span class="flex-1 text-sm font-medium">{{ t('storefront::admin.builder.repeater_row', { number: index + 1 }) }}</span>
                <button type="button" :class="dangerButton" :aria-label="t('storefront::admin.builder.remove_row', { number: index + 1 })" @click="rows = rows.filter((_, position) => position !== index)">
                    {{ t('storefront::admin.design_home_blocks.delete') }}
                </button>
            </div>
            <div class="grid gap-3 md:grid-cols-2">
                <BuilderField
                    v-for="child in field.fields"
                    :key="child.key"
                    :field="child"
                    :path="`${path}.${index}.${child.key}`"
                    :errors="errors"
                    :model-value="row[child.key]"
                    @update:model-value="updateCell(row, child.key, $event)"
                />
            </div>
        </Sortable>
        <button type="button" :class="secondaryButton" :disabled="rows.length >= field.max" @click="addRow">{{ t('storefront::admin.builder.add_row') }}</button>
    </fieldset>
    <FormField v-else :label="field.label + (field.required ? ' *' : '')" :hint="field.help ?? undefined" :error="errors[path]">
        <RichTextEditor v-if="richText" v-model="value as string | null" />
        <textarea v-else-if="field.type === 'text'" v-model="value as string | null" :class="inputClass" rows="3" />
        <Select v-else-if="field.type === 'select'" v-model="value as string | null" :options="labelOptions(field.options)" empty-label="—" :empty-value="null" />
        <DateInput v-else-if="field.type === 'date'" v-model="value as string | null" />
        <input v-else-if="field.type === 'bool'" v-model="value as boolean" type="checkbox" />
        <MediaField v-else-if="field.type === 'media' || field.type === 'video'" v-model="value as number | null" :kind="field.type === 'video' ? 'video' : 'image'" :label="field.label.toLowerCase()" />
        <input v-else v-model="value as string | null" :class="inputClass" :type="field.type === 'int' ? 'number' : 'text'" />
    </FormField>
</template>
