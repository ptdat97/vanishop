<script setup lang="ts">
import { t } from '@admin/i18n';
import { useAdminForm } from '@admin/forms';
import { getJson } from '@admin/http';
import Combobox from '@admin/Components/Combobox.vue';
import DataTable from '@admin/Components/DataTable.vue';
import DateInput from '@admin/Components/DateInput.vue';
import FormField from '@admin/Components/FormField.vue';
import MediaField from '@admin/Components/Media/MediaField.vue';
import MoneyText from '@admin/Components/MoneyText.vue';
import PageHeader from '@admin/Components/PageHeader.vue';
import Select from '@admin/Components/Select.vue';
import WeeklyScheduleInput, { type WeeklyWindow } from '@admin/Components/WeeklyScheduleInput.vue';
import { confirmAction } from '@admin/confirm';
import { dangerButton, inputClass, primaryButton, secondaryButton } from '@admin/styles';
import type { SharedProps } from '@admin/types';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

type Strategy = 'fixed_price' | 'percent' | 'amount';
type Item = { product_id: number; label: string; strategy: Strategy; value: number; quota: number | null; per_customer: number | null; list_min: number | null; list_max: number | null };
type ProductOption = { value: string; label: string; list_min?: number | null; list_max?: number | null };

/** Form chiến dịch giá: hiển thị, lịch (có lặp theo tuần), đối tượng, suất, sản phẩm với cách giảm từng sản phẩm. */
const props = defineProps<{
    campaign: null | {
        id: number; template: string; name: string; label: string; badge: string; content: string; badge_style: string; accent_color: string | null;
        banner_media_id: number | null; show_countdown: boolean; show_stock: boolean; priority: number; customer_group_id: number | null;
        starts_at: string; ends_at: string; recurrence: WeeklyWindow[] | null; quota_total: number | null; per_customer_total: number | null;
        status: string; state: string; lock_version: number; items: Item[]; warnings: string[];
    };
    groups: Array<{ value: number; label: string }>;
    options: { templates: string[]; badge_styles: string[]; statuses: string[]; strategies: Strategy[]; max_items: number };
    urls: { index: string; save: string; delete: string | null; products: string; categories: string };
    can: { manage: boolean };
}>();

const symbol = usePage<SharedProps>().props.app.money.symbol;
const { form, save } = useAdminForm({
    template: props.campaign?.template ?? 'flash_sale',
    name: props.campaign?.name ?? '',
    label: props.campaign?.label ?? '',
    badge: props.campaign?.badge ?? '',
    content: props.campaign?.content ?? '',
    badge_style: props.campaign?.badge_style ?? 'danger',
    accent_color: props.campaign?.accent_color ?? (null as string | null),
    banner_media_id: props.campaign?.banner_media_id ?? (null as number | null),
    show_countdown: props.campaign?.show_countdown ?? true,
    show_stock: props.campaign?.show_stock ?? true,
    priority: props.campaign?.priority ?? 40,
    customer_group_id: props.campaign?.customer_group_id ?? (null as number | null),
    starts_at: props.campaign?.starts_at ?? (null as string | null),
    ends_at: props.campaign?.ends_at ?? (null as string | null),
    recurrence: (props.campaign?.recurrence ?? null) as WeeklyWindow[] | null,
    quota_total: props.campaign?.quota_total ?? (null as number | null),
    per_customer_total: props.campaign?.per_customer_total ?? (null as number | null),
    status: props.campaign?.status ?? 'draft',
    items: (props.campaign?.items ?? []).map((item) => ({ ...item })) as Item[],
    lock_version: props.campaign?.lock_version ?? (null as number | null),
}, { lockVersion: () => props.campaign?.lock_version });
const errors = computed(() => form.errors as Record<string, string | undefined>);

/** Mẫu điền sẵn khi tạo mới (không ghi đè ô đã nhập). */
function applyTemplate(template: string): void {
    form.template = template;
    if (props.campaign) return;
    const presets: Record<string, Partial<typeof form>> = {
        flash_sale: { badge: 'Flash sale', badge_style: 'danger', show_stock: true, priority: 40, recurrence: null },
        hot_deal: { badge: 'Hot deal', badge_style: 'warning', show_stock: false, priority: 30, recurrence: null },
        member: { badge: t('vani-pricing-campaign::admin.template.member'), badge_style: 'dark', show_stock: false, priority: 25, recurrence: null },
        weekend: { badge: t('vani-pricing-campaign::admin.template.weekend'), badge_style: 'info', priority: 30, recurrence: [{ days: [6, 7], from: '00:00', to: '00:00' }] },
    };
    Object.assign(form, presets[template] ?? {});
}

const defaults = ref({ strategy: 'percent' as Strategy, value: 20, quota: null as number | null, per_customer: null as number | null });
function addProduct(option: ProductOption): void {
    if (form.items.length >= props.options.max_items || form.items.some((item) => String(item.product_id) === option.value)) return;
    form.items.push({ product_id: Number(option.value), label: option.label, ...defaults.value, list_min: option.list_min ?? null, list_max: option.list_max ?? null });
}
async function searchProducts(term: string): Promise<ProductOption[]> {
    const { data } = await getJson<{ data: ProductOption[] }>(props.urls.products, { q: term });
    return data.filter((option) => !form.items.some((item) => String(item.product_id) === option.value));
}
async function searchCategories(term: string): Promise<ProductOption[]> {
    return (await getJson<{ data: ProductOption[] }>(props.urls.categories, { q: term })).data;
}
async function addCategory(option: ProductOption): Promise<void> {
    const { data } = await getJson<{ data: ProductOption[] }>(props.urls.products, { category_id: option.value });
    data.forEach(addProduct);
}
function applyDefaults(onlyEmpty: boolean): void {
    form.items.forEach((item) => {
        if (!onlyEmpty || !item.value) Object.assign(item, { strategy: defaults.value.strategy, value: defaults.value.value });
        if (!onlyEmpty || item.quota === null) item.quota = defaults.value.quota;
        if (!onlyEmpty || item.per_customer === null) item.per_customer = defaults.value.per_customer;
    });
}

/** Giá sau giảm (cùng cách tính của server: làm tròn lên 1.000đ). */
function salePrice(item: Item, list: number | null): number | null {
    if (list === null || !item.value) return null;
    const raw = item.strategy === 'fixed_price' ? item.value : item.strategy === 'percent' ? list - Math.floor((list * item.value) / 100) : list - item.value;
    const price = Math.ceil(Math.max(0, raw) / 1000) * 1000;
    return price < list ? price : null;
}

const itemColumns = computed(() => [
    { key: 'label', label: t('vani-pricing-campaign::admin.form.product') },
    { key: 'list', label: t('vani-pricing-campaign::admin.form.list_price'), class: 'whitespace-nowrap text-xs' },
    { key: 'strategy', label: t('vani-pricing-campaign::admin.form.strategy') },
    { key: 'value', label: t('vani-pricing-campaign::admin.form.value') },
    { key: 'sale', label: t('vani-pricing-campaign::admin.form.sale_price'), class: 'whitespace-nowrap text-xs' },
    { key: 'quota', label: t('vani-pricing-campaign::admin.form.quota') },
    { key: 'per_customer', label: t('vani-pricing-campaign::admin.form.per_customer') },
    { key: 'actions', label: '', align: 'right' as const },
]);
function itemError(item: Item): string | undefined {
    const index = form.items.indexOf(item);
    return errors.value[`items.${index}.value`] ?? errors.value[`items.${index}.product_id`];
}

function submit(): void {
    form.transform((data) => ({ ...data, items: data.items.map(({ product_id, strategy, value, quota, per_customer }) => ({ product_id, strategy, value, quota, per_customer })) }));
    save(props.campaign ? 'put' : 'post', props.urls.save);
}

async function destroy(): Promise<void> {
    if (props.urls.delete && (await confirmAction({ title: t('vani-pricing-campaign::admin.form.delete_title'), message: t('vani-pricing-campaign::admin.form.delete_message'), confirmLabel: t('vani-pricing-campaign::admin.form.delete'), tone: 'danger' })) !== false) {
        router.delete(props.urls.delete);
    }
}

const option = (prefix: string, values: string[]) => values.map((value) => ({ value, label: t(`vani-pricing-campaign::admin.${prefix}.${value}`) }));
</script>

<template>
    <div>
        <Head :title="campaign ? campaign.name : t('vani-pricing-campaign::admin.form.add')" />
        <PageHeader :title="campaign ? campaign.name : t('vani-pricing-campaign::admin.form.add')" :subtitle="campaign ? t(`vani-pricing-campaign::admin.state.${campaign.state}`) : undefined">
            <Link :href="urls.index" :class="secondaryButton">{{ t('vani-pricing-campaign::admin.form.list') }}</Link>
            <button v-if="campaign && can.manage" type="button" :class="dangerButton" @click="destroy">{{ t('vani-pricing-campaign::admin.form.delete') }}</button>
        </PageHeader>

        <ul v-if="campaign?.warnings.length" class="mb-4 max-w-4xl space-y-1 rounded border border-amber-200 bg-amber-50 p-3 text-sm text-amber-900" data-campaign-warnings>
            <li v-for="warning in campaign.warnings" :key="warning">{{ warning }}</li>
        </ul>

        <form class="grid max-w-4xl gap-4" @submit.prevent="submit">
            <fieldset :disabled="!can.manage" class="contents">
                <section class="grid gap-4 rounded-lg border border-slate-200 bg-white p-5 sm:grid-cols-2">
                    <h2 class="font-medium sm:col-span-2">{{ t('vani-pricing-campaign::admin.form.display') }}</h2>
                    <FormField :label="t('vani-pricing-campaign::admin.form.template')" :hint="t('vani-pricing-campaign::admin.form.template_hint')">
                        <Select :model-value="form.template" :options="option('template', options.templates)" @update:model-value="applyTemplate" />
                    </FormField>
                    <FormField :label="t('vani-pricing-campaign::admin.form.name') + ' *'" :error="errors.name" :hint="t('vani-pricing-campaign::admin.form.name_hint')">
                        <input v-model="form.name" :class="inputClass" maxlength="120" />
                    </FormField>
                    <FormField :label="t('vani-pricing-campaign::admin.form.label')" :error="errors.label" :hint="t('vani-pricing-campaign::admin.form.label_hint')">
                        <input v-model="form.label" :class="inputClass" maxlength="120" />
                    </FormField>
                    <FormField :label="t('vani-pricing-campaign::admin.form.badge')" :error="errors.badge">
                        <input v-model="form.badge" :class="inputClass" maxlength="40" />
                    </FormField>
                    <FormField :label="t('vani-pricing-campaign::admin.form.badge_style')">
                        <Select v-model="form.badge_style" :options="option('badge_style', options.badge_styles)" />
                    </FormField>
                    <FormField :label="t('vani-pricing-campaign::admin.form.accent_color')" :error="errors.accent_color" :hint="t('vani-pricing-campaign::admin.form.accent_color_hint')">
                        <input v-model="form.accent_color" :class="inputClass" placeholder="#d0021b" maxlength="7" />
                    </FormField>
                    <FormField class="sm:col-span-2" :label="t('vani-pricing-campaign::admin.form.content')" :error="errors.content">
                        <textarea v-model="form.content" :class="inputClass" rows="2" maxlength="1000" />
                    </FormField>
                    <label class="flex items-center gap-2 text-sm"><input v-model="form.show_countdown" type="checkbox" /> {{ t('vani-pricing-campaign::admin.form.show_countdown') }}</label>
                    <label class="flex items-center gap-2 text-sm"><input v-model="form.show_stock" type="checkbox" /> {{ t('vani-pricing-campaign::admin.form.show_stock') }}</label>
                    <div class="sm:col-span-2">
                        <MediaField v-model="form.banner_media_id" :label="t('vani-pricing-campaign::admin.form.banner')" :disabled="!can.manage" />
                        <p v-if="errors.banner_media_id" class="mt-2 text-sm text-red-600">{{ errors.banner_media_id }}</p>
                    </div>
                </section>

                <section class="grid gap-4 rounded-lg border border-slate-200 bg-white p-5 sm:grid-cols-2">
                    <h2 class="font-medium sm:col-span-2">{{ t('vani-pricing-campaign::admin.form.schedule') }}</h2>
                    <FormField :label="t('vani-pricing-campaign::admin.form.starts_at') + ' *'" :error="errors.starts_at"><DateInput v-model="form.starts_at" with-time :disabled="!can.manage" /></FormField>
                    <FormField :label="t('vani-pricing-campaign::admin.form.ends_at') + ' *'" :error="errors.ends_at"><DateInput v-model="form.ends_at" with-time :disabled="!can.manage" /></FormField>
                    <div class="sm:col-span-2"><WeeklyScheduleInput v-model="form.recurrence" :disabled="!can.manage" :error="errors.recurrence" /></div>
                    <FormField :label="t('vani-pricing-campaign::admin.form.status')" :hint="t('vani-pricing-campaign::admin.form.status_hint')">
                        <Select v-model="form.status" :options="option('status', options.statuses)" />
                    </FormField>
                    <FormField :label="t('vani-pricing-campaign::admin.form.priority')" :error="errors.priority" :hint="t('vani-pricing-campaign::admin.form.priority_hint')">
                        <input v-model.number="form.priority" type="number" min="1" max="1000" :class="inputClass" />
                    </FormField>
                    <FormField v-if="groups.length" :label="t('vani-pricing-campaign::admin.form.customer_group')" :hint="t('vani-pricing-campaign::admin.form.customer_group_hint')" :error="errors.customer_group_id">
                        <Select v-model="form.customer_group_id" :options="groups" :empty-label="t('vani-pricing-campaign::admin.form.everyone')" :empty-value="null" />
                    </FormField>
                    <FormField :label="t('vani-pricing-campaign::admin.form.quota_total')" :hint="t('vani-pricing-campaign::admin.form.empty_unlimited')" :error="errors.quota_total">
                        <input v-model.number="form.quota_total" type="number" min="1" :class="inputClass" />
                    </FormField>
                    <FormField :label="t('vani-pricing-campaign::admin.form.per_customer_total')" :hint="t('vani-pricing-campaign::admin.form.empty_unlimited')" :error="errors.per_customer_total">
                        <input v-model.number="form.per_customer_total" type="number" min="1" :class="inputClass" />
                    </FormField>
                </section>

                <section class="rounded-lg border border-slate-200 bg-white p-5" data-campaign-items>
                    <h2 class="mb-3 font-medium">{{ t('vani-pricing-campaign::admin.form.products', { count: form.items.length }) }}</h2>
                    <div v-if="can.manage" class="mb-3 grid gap-2 sm:grid-cols-2">
                        <Combobox :key="`p${form.items.length}`" model-value="" :search="searchProducts" :placeholder="t('vani-pricing-campaign::admin.form.add_product')" :aria-label="t('vani-pricing-campaign::admin.form.add_product')" @select="addProduct" />
                        <Combobox model-value="" :search="searchCategories" :placeholder="t('vani-pricing-campaign::admin.form.add_category')" :aria-label="t('vani-pricing-campaign::admin.form.add_category')" @select="addCategory" />
                    </div>
                    <div v-if="can.manage" class="mb-3 flex flex-wrap items-end gap-2 rounded bg-slate-50 p-2 text-sm">
                        <span class="text-slate-500">{{ t('vani-pricing-campaign::admin.form.defaults') }}</span>
                        <div class="w-40"><Select v-model="defaults.strategy" :options="option('strategy', options.strategies)" /></div>
                        <input v-model.number="defaults.value" type="number" min="1" :class="[inputClass, 'w-28']" :aria-label="t('vani-pricing-campaign::admin.form.value')" />
                        <input v-model.number="defaults.quota" type="number" min="1" :class="[inputClass, 'w-24']" :placeholder="t('vani-pricing-campaign::admin.form.quota')" :aria-label="t('vani-pricing-campaign::admin.form.quota')" />
                        <input v-model.number="defaults.per_customer" type="number" min="1" :class="[inputClass, 'w-24']" :placeholder="t('vani-pricing-campaign::admin.form.per_customer')" :aria-label="t('vani-pricing-campaign::admin.form.per_customer')" />
                        <button type="button" :class="secondaryButton" @click="applyDefaults(true)">{{ t('vani-pricing-campaign::admin.form.apply_empty') }}</button>
                        <button type="button" :class="secondaryButton" @click="applyDefaults(false)">{{ t('vani-pricing-campaign::admin.form.apply_all') }}</button>
                    </div>
                    <DataTable bare :columns="itemColumns" :rows="form.items" :row-key="(row) => row.product_id" :row-label="(row) => row.label" :expanded="(row) => itemError(row) !== undefined" :empty-text="t('vani-pricing-campaign::admin.form.no_products')">
                        <template #cell-label="{ row }"><span class="block max-w-56 truncate">{{ row.label }}</span></template>
                        <template #cell-list="{ row }"><template v-if="row.list_min !== null"><MoneyText :amount="row.list_min" /><template v-if="row.list_max !== row.list_min"> – <MoneyText :amount="row.list_max ?? 0" /></template></template><span v-else class="text-red-600">—</span></template>
                        <template #cell-strategy="{ row }"><div class="w-36"><Select v-model="row.strategy" :options="option('strategy', options.strategies)" :aria-label="t('vani-pricing-campaign::admin.form.strategy')" /></div></template>
                        <template #cell-value="{ row }"><input v-model.number="row.value" type="number" min="1" :class="[inputClass, 'w-28']" :aria-label="t('vani-pricing-campaign::admin.form.value_for', { name: row.label })" /></template>
                        <template #cell-sale="{ row }"><template v-if="salePrice(row, row.list_min) !== null"><MoneyText :amount="salePrice(row, row.list_min) ?? 0" /><template v-if="row.list_max !== row.list_min && salePrice(row, row.list_max) !== null && salePrice(row, row.list_max) !== salePrice(row, row.list_min)"> – <MoneyText :amount="salePrice(row, row.list_max) ?? 0" /></template></template><span v-else class="text-red-600">{{ t('vani-pricing-campaign::admin.form.no_discount') }}</span></template>
                        <template #cell-quota="{ row }"><input v-model.number="row.quota" type="number" min="1" :class="[inputClass, 'w-20']" :aria-label="t('vani-pricing-campaign::admin.form.quota')" /></template>
                        <template #cell-per_customer="{ row }"><input v-model.number="row.per_customer" type="number" min="1" :class="[inputClass, 'w-20']" :aria-label="t('vani-pricing-campaign::admin.form.per_customer')" /></template>
                        <template #cell-actions="{ row, index }"><button v-if="can.manage" type="button" class="text-red-600" :aria-label="t('vani-pricing-campaign::admin.form.remove')" @click="form.items.splice(index, 1)">×</button><span v-else class="sr-only">{{ row.product_id }}</span></template>
                        <template #expanded="{ row }"><p class="text-sm text-red-600">{{ itemError(row) }}</p></template>
                    </DataTable>
                    <p class="mt-2 text-xs text-slate-500">{{ t('vani-pricing-campaign::admin.form.strategy_hint', { symbol }) }}</p>
                    <p v-if="errors.items" class="mt-2 text-sm text-red-600">{{ errors.items }}</p>
                </section>

                <div v-if="can.manage"><button type="submit" :class="primaryButton" :disabled="form.processing">{{ t('vani-pricing-campaign::admin.form.save') }}</button></div>
            </fieldset>
        </form>
    </div>
</template>
