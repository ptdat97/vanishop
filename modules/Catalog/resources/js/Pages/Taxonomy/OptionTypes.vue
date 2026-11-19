<script setup lang="ts">
import { t } from '@admin/i18n';
import DataTable from '@admin/Components/DataTable.vue';
import FormField from '@admin/Components/FormField.vue';
import PageHeader from '@admin/Components/PageHeader.vue';
import Select from '@admin/Components/Select.vue';
import { confirmAction } from '@admin/confirm';
import { codeOptions } from '@admin/options';
import { inputClass, primaryButton, secondaryButton } from '@admin/styles';
import { translationsFor, useStoreLocales } from '@admin/locales';
import { Head, router, useForm } from '@inertiajs/vue3';
import { computed, nextTick, ref } from 'vue';
import CatalogTabs from '../../Components/CatalogTabs.vue';
import type { NavItem, Translations } from '../../types';

/**
 * Tuỳ chọn biến thể (ADR-045): loại tuỳ chọn dùng chung (Màu, Size, Chất liệu…) — kiểu hiển thị, lọc được ở danh mục — và
 * giá trị của từng loại. Sản phẩm chọn 1–3 loại làm trục biến thể.
 */
interface ValueRow {
    id: number;
    code: string;
    group: string | null;
    hex: string | null;
    family: string | null;
    position: number;
    translations: Translations<'name'>;
    products: number;
}
interface TypeRow {
    id: number;
    code: string;
    display_type: string;
    is_filterable: boolean;
    position: number;
    translations: Translations<'name'>;
    products: number;
    values: ValueRow[];
}

const props = defineProps<{ nav: NavItem[]; types: TypeRow[]; displayTypes: string[]; displayLabels: Record<string, string>; families: string[]; canManage: boolean }>();
const baseUrl = computed(() => props.nav.find((item) => item.key === 'option-types')?.url ?? '');
const { locales, defaultLocale } = useStoreLocales();
const displayOptions = computed(() => props.displayTypes.map((value) => ({ value, label: props.displayLabels[value] ?? value })));

const selectedId = ref<number | null>(props.types[0]?.id ?? null);
const selected = computed(() => props.types.find((type) => type.id === selectedId.value) ?? null);
const typeName = (type: TypeRow): string => type.translations[defaultLocale]?.name || type.code;

// Loại tuỳ chọn
const editingTypeId = ref<number | null>(null);
const typeForm = useForm({ code: '', display_type: 'button', is_filterable: false, position: 0, translations: translationsFor(locales, { name: '' }) });
const typeErrors = computed(() => typeForm.errors as Record<string, string | undefined>);

function editType(type: TypeRow): void {
    editingTypeId.value = type.id;
    typeForm.defaults({ code: type.code, display_type: type.display_type, is_filterable: type.is_filterable, position: type.position, translations: translationsFor(locales, { name: '' }, type.translations) });
    typeForm.reset();
}

function resetType(): void {
    editingTypeId.value = null;
    typeForm.defaults({ code: '', display_type: 'button', is_filterable: false, position: 0, translations: translationsFor(locales, { name: '' }) });
    typeForm.reset();
    typeForm.clearErrors();
}

function submitType(): void {
    const options = { preserveScroll: true, onSuccess: resetType };
    if (editingTypeId.value) typeForm.put(`${baseUrl.value}/${editingTypeId.value}`, options);
    else typeForm.post(baseUrl.value, options);
}

/** Nút "Thêm loại tuỳ chọn" / "Thêm giá trị": về chế độ tạo mới và đưa con trỏ tới ô tên. */
const typeFormEl = ref<HTMLFormElement | null>(null);
const valueFormEl = ref<HTMLFormElement | null>(null);
function focusForm(el: HTMLFormElement | null): void {
    el?.scrollIntoView({ behavior: 'smooth', block: 'center' });
    el?.querySelector<HTMLInputElement>('input')?.focus();
}
function startNewType(): void {
    resetType();
    nextTick(() => focusForm(typeFormEl.value));
}
function startNewValue(): void {
    resetValue();
    nextTick(() => focusForm(valueFormEl.value));
}

async function destroyType(type: TypeRow): Promise<void> {
    if ((await confirmAction({ title: t('catalog::admin.taxonomy_option_types.delete_option_type_type', { type: typeName(type) }), message: t('catalog::admin.taxonomy_option_types.values_deleted_too'), confirmLabel: t('catalog::admin.taxonomy_option_types.delete'), tone: 'danger' })) !== false) {
        router.delete(`${baseUrl.value}/${type.id}`, { preserveScroll: true });
    }
}

// Giá trị
const editingValueId = ref<number | null>(null);
const valueForm = useForm({ code: '', group: '' as string | null, hex: '' as string | null, family: null as string | null, position: 0, translations: translationsFor(locales, { name: '' }) });
const valueErrors = computed(() => valueForm.errors as Record<string, string | undefined>);
const isSwatch = computed(() => selected.value?.display_type === 'swatch');

function editValue(value: ValueRow): void {
    editingValueId.value = value.id;
    valueForm.defaults({ code: value.code, group: value.group, hex: value.hex, family: value.family, position: value.position, translations: translationsFor(locales, { name: '' }, value.translations) });
    valueForm.reset();
}

function resetValue(): void {
    editingValueId.value = null;
    valueForm.defaults({ code: '', group: '', hex: '', family: null, position: (selected.value?.values.length ?? 0) + 1, translations: translationsFor(locales, { name: '' }) });
    valueForm.reset();
    valueForm.clearErrors();
}

function submitValue(): void {
    if (!selected.value) return;
    const url = `${baseUrl.value}/${selected.value.id}/values`;
    const options = { preserveScroll: true, onSuccess: resetValue };
    if (editingValueId.value) valueForm.put(`${url}/${editingValueId.value}`, options);
    else valueForm.post(url, options);
}

async function destroyValue(value: ValueRow): Promise<void> {
    if (selected.value && (await confirmAction({ title: t('catalog::admin.taxonomy_option_types.delete_value_code', { code: value.code }), confirmLabel: t('catalog::admin.taxonomy_option_types.delete'), tone: 'danger' })) !== false) {
        router.delete(`${baseUrl.value}/${selected.value.id}/values/${value.id}`, { preserveScroll: true });
    }
}

const typeColumns = [
    { key: 'name', label: t('catalog::admin.taxonomy_option_types.option_types') },
    { key: 'display_type', label: t('catalog::admin.taxonomy_option_types.display') },
    { key: 'meta', label: '' },
    { key: 'actions', label: '', align: 'right' as const },
];
const valueColumns = [
    { key: 'name', label: t('catalog::admin.taxonomy_option_types.value') },
    { key: 'code', label: t('catalog::admin.taxonomy_option_types.code'), class: 'font-mono text-xs' },
    { key: 'group', label: t('catalog::admin.taxonomy_option_types.group') },
    { key: 'products', label: t('catalog::admin.taxonomy_option_types.used_products') },
    { key: 'actions', label: '', align: 'right' as const },
];
</script>

<template>
    <Head :title="t('catalog::admin.taxonomy_option_types.variant_options')" />
    <CatalogTabs :nav="nav" active="option-types" />
    <PageHeader :title="t('catalog::admin.taxonomy_option_types.variant_options')" :subtitle="t('catalog::admin.taxonomy_option_types.shared_option_types_colour_size')">
        <button v-if="canManage" type="button" :class="primaryButton" @click="startNewType">{{ t('catalog::admin.taxonomy_option_types.add_option_type') }}</button>
    </PageHeader>

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="space-y-6 lg:col-span-2">
            <DataTable :columns="typeColumns" :rows="types" :row-label="(type) => type.code" :row-click="(type) => (selectedId = type.id)" :row-class="(type) => (type.id === selectedId ? 'bg-indigo-50' : undefined)" :empty-text="t('catalog::admin.taxonomy_option_types.no_option_types_yet')">
                <template #cell-name="{ row }">
                    <span class="font-medium">{{ typeName(row) }}</span> <span class="font-mono text-xs text-slate-400">{{ row.code }}</span>
                </template>
                <template #cell-display_type="{ row }">{{ displayLabels[row.display_type] ?? row.display_type }}</template>
                <template #cell-meta="{ row }">
                    <span class="text-xs text-slate-500">{{ t('catalog::admin.taxonomy_option_types.values_products', { count: row.values.length, products: row.products, filterable: row.is_filterable ? t('catalog::admin.taxonomy_option_types.filterable_suffix') : '' }) }}</span>
                </template>
                <template #cell-actions="{ row }">
                    <template v-if="canManage">
                        <button type="button" class="text-indigo-600 hover:underline" @click.stop="editType(row)">{{ t('catalog::admin.taxonomy_option_types.edit') }}</button>
                        <button type="button" class="ml-3 text-red-600 hover:underline" :disabled="row.products > 0" @click.stop="destroyType(row)">{{ t('catalog::admin.taxonomy_option_types.delete') }}</button>
                    </template>
                </template>
            </DataTable>

            <section v-if="selected" class="space-y-3">
                <div class="flex items-center justify-between">
                    <h2 class="text-sm font-medium">{{ t('catalog::admin.taxonomy_option_types.values_selected', { selected: typeName(selected) }) }} <span class="font-normal text-slate-500">{{ t('catalog::admin.taxonomy_option_types.click_type_table_above_see') }}</span></h2>
                    <button v-if="canManage" type="button" :class="secondaryButton" @click="startNewValue">{{ t('catalog::admin.taxonomy_option_types.add_value') }}</button>
                </div>
                <DataTable :columns="valueColumns" :rows="selected.values" :row-label="(value) => value.code" :empty-text="t('catalog::admin.taxonomy_option_types.no_values_yet')">
                    <template #cell-name="{ row }">
                        <span class="flex items-center gap-2">
                            <span v-if="isSwatch" class="inline-block h-4 w-4 rounded-full border border-slate-300" :style="{ background: row.hex ?? 'transparent' }" />
                            {{ row.translations[defaultLocale]?.name || row.code }}
                        </span>
                    </template>
                    <template #cell-group="{ row }">{{ isSwatch ? row.family ?? '—' : row.group ?? '—' }}</template>
                    <template #cell-actions="{ row }">
                        <template v-if="canManage">
                            <button type="button" class="text-indigo-600 hover:underline" @click="editValue(row)">{{ t('catalog::admin.taxonomy_option_types.edit') }}</button>
                            <button type="button" class="ml-3 text-red-600 hover:underline" :disabled="row.products > 0" @click="destroyValue(row)">{{ t('catalog::admin.taxonomy_option_types.delete') }}</button>
                        </template>
                    </template>
                </DataTable>
            </section>
        </div>

        <div v-if="canManage" class="space-y-6">
            <form ref="typeFormEl" class="space-y-3 rounded-lg border border-slate-200 bg-white p-5" @submit.prevent="submitType">
                <h2 class="text-sm font-medium">{{ editingTypeId ? t('catalog::admin.taxonomy_option_types.edit_option_type') : t('catalog::admin.taxonomy_option_types.add_option_type') }}</h2>
                <FormField v-for="option in locales" :key="option.code" :label="t('catalog::admin.taxonomy_option_types.name_label', { label: option.label })" :error="typeErrors[`translations.${option.code}.name`]">
                    <input v-model="typeForm.translations[option.code].name" :class="inputClass" />
                </FormField>
                <FormField :label="t('catalog::admin.taxonomy_option_types.code')" :hint="t('catalog::admin.taxonomy_option_types.lowercase_material')" :error="typeErrors.code"><input v-model="typeForm.code" :class="inputClass" /></FormField>
                <FormField :label="t('catalog::admin.taxonomy_option_types.shown_product_page')" :error="typeErrors.display_type"><Select v-model="typeForm.display_type" :options="displayOptions" /></FormField>
                <label class="flex items-center gap-2 text-sm"><input v-model="typeForm.is_filterable" type="checkbox" /> {{ t('catalog::admin.taxonomy_option_types.use_category_filter') }}</label>
                <FormField :label="t('catalog::admin.taxonomy_option_types.order')" :error="typeErrors.position"><input v-model.number="typeForm.position" :class="inputClass" type="number" min="0" /></FormField>
                <div class="flex gap-2">
                    <button type="submit" :class="primaryButton" :disabled="typeForm.processing">{{ t('catalog::admin.taxonomy_option_types.save') }}</button>
                    <button v-if="editingTypeId" type="button" :class="secondaryButton" @click="resetType">{{ t('catalog::admin.taxonomy_option_types.cancel_editing') }}</button>
                </div>
            </form>

            <form v-if="selected" ref="valueFormEl" class="space-y-3 rounded-lg border border-slate-200 bg-white p-5" @submit.prevent="submitValue">
                <h2 class="text-sm font-medium">{{ editingValueId ? t('catalog::admin.taxonomy_option_types.edit_value') : t('catalog::admin.taxonomy_option_types.add_value_selected', { selected: typeName(selected) }) }}</h2>
                <FormField v-for="option in locales" :key="option.code" :label="t('catalog::admin.taxonomy_option_types.name_label', { label: option.label })" :error="valueErrors[`translations.${option.code}.name`]">
                    <input v-model="valueForm.translations[option.code].name" :class="inputClass" />
                </FormField>
                <FormField :label="t('catalog::admin.taxonomy_option_types.code')" hint="vd. IVR, M, COTTON" :error="valueErrors.code"><input v-model="valueForm.code" :class="inputClass" /></FormField>
                <template v-if="isSwatch">
                    <FormField :label="t('catalog::admin.taxonomy_option_types.hex_code')" hint="#FFFFF0" :error="valueErrors.hex"><input v-model="valueForm.hex" :class="inputClass" /></FormField>
                    <FormField :label="t('catalog::admin.taxonomy_option_types.colour_family_filter')" :error="valueErrors.family"><Select v-model="valueForm.family" :options="codeOptions(families)" placeholder="—" /></FormField>
                </template>
                <FormField v-else :label="t('catalog::admin.taxonomy_option_types.group')" :hint="t('catalog::admin.taxonomy_option_types.optional_size_system_alpha_vn')" :error="valueErrors.group"><input v-model="valueForm.group" :class="inputClass" /></FormField>
                <FormField :label="t('catalog::admin.taxonomy_option_types.order')" :error="valueErrors.position"><input v-model.number="valueForm.position" :class="inputClass" type="number" min="0" /></FormField>
                <div class="flex gap-2">
                    <button type="submit" :class="primaryButton" :disabled="valueForm.processing">{{ t('catalog::admin.taxonomy_option_types.save') }}</button>
                    <button v-if="editingValueId" type="button" :class="secondaryButton" @click="resetValue">{{ t('catalog::admin.taxonomy_option_types.cancel') }}</button>
                </div>
            </form>
        </div>
    </div>
</template>
