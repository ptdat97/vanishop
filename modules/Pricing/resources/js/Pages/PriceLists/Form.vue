<script setup lang="ts">
import { t } from '@admin/i18n';
import { useAdminForm } from '@admin/forms';
import DateInput from '@admin/Components/DateInput.vue';
import FormField from '@admin/Components/FormField.vue';
import PageHeader from '@admin/Components/PageHeader.vue';
import Select from '@admin/Components/Select.vue';
import { confirmAction } from '@admin/confirm';
import { recordOptions } from '@admin/options';
import { dangerButton, inputClass, primaryButton, secondaryButton } from '@admin/styles';
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

/**
 * Tạo/sửa bảng giá — thao tác gọn (đợt 2): tạo chỉ cần Loại (Sale / Thành viên), Tên, khung giờ; mã và độ ưu tiên tự sinh
 * (sửa được trong "Nâng cao"). Bảng giá niêm yết là duy nhất (mã `base`): không tạo thêm, không đổi loại.
 */
const props = defineProps<{
    baseUrl: string;
    labels: { price_list_type: Record<string, string>; price_list_status: Record<string, string> };
    priceList: null | {
        id: number;
        code: string;
        name: string;
        type: string;
        customer_group_id: number | null;
        priority: number;
        starts_at: string | null;
        ends_at: string | null;
        status: string;
        lock_version: number;
    };
    types: string[];
    defaultPriorities: Record<string, number>;
    customerGroups: Array<{ id: number; code: string; name: string }>;
}>();

const isMainBase = computed(() => props.priceList?.code === 'base' && props.priceList.type === 'base');
/** Bảng loại niêm yết thừa (dữ liệu cũ, mã khác `base`): phải chọn lại Sale hoặc Thành viên. */
const needsType = computed(() => props.priceList !== null && props.priceList.type === 'base' && !isMainBase.value);
const typeHelp: Record<string, string> = {
    sale: t('pricing::admin.price_lists_form.time_limited_discounted_prices_end'),
    member: t('pricing::admin.price_lists_form.special_prices_customer_group_vip'),
    base: t('pricing::admin.price_lists_form.store_s_regular_selling_price'),
};

const { form, save } = useAdminForm(
    {
        code: props.priceList?.code ?? '',
        name: props.priceList?.name ?? '',
        type: needsType.value ? '' : (props.priceList?.type ?? 'sale'),
        customer_group_id: props.priceList?.customer_group_id ?? (null as number | null),
        priority: props.priceList?.priority ?? (null as number | null),
        starts_at: props.priceList?.starts_at ?? null,
        ends_at: props.priceList?.ends_at ?? null,
        status: props.priceList?.status ?? 'active',
        lock_version: props.priceList?.lock_version ?? null,
    },
    { lockVersion: () => props.priceList?.lock_version },
);
const advanced = ref(Boolean(props.priceList && (form.errors.code || form.errors.priority)));

function submit(): void {
    if (props.priceList) {
        save('put', `${props.baseUrl}/${props.priceList.id}`);
    } else {
        save('post', props.baseUrl);
    }
}

async function destroy(): Promise<void> {
    if (props.priceList && (await confirmAction({ title: t('pricing::admin.price_lists_form.delete_price_list'), message: t('pricing::admin.price_lists_form.all_prices_list_deleted_too'), confirmLabel: t('pricing::admin.price_lists_form.delete'), tone: 'danger' })) !== false) {
        router.delete(`${props.baseUrl}/${props.priceList.id}`);
    }
}
</script>

<template>
    <Head :title="priceList ? t('pricing::admin.price_lists_form.price_list_details') : t('pricing::admin.price_lists_form.add_price_list')" />
    <PageHeader :title="priceList ? t('pricing::admin.price_lists_form.price_list_details_name', { name: priceList.name }) : t('pricing::admin.price_lists_form.add_price_list')">
        <Link :href="priceList ? `${baseUrl}/${priceList.id}/prices` : baseUrl" :class="secondaryButton">{{ priceList ? t('pricing::admin.price_lists_form.back_prices') : t('pricing::admin.price_lists_form.back') }}</Link>
        <button v-if="priceList && !isMainBase" type="button" :class="dangerButton" @click="destroy">{{ t('pricing::admin.price_lists_form.delete') }}</button>
    </PageHeader>

    <div v-if="needsType" class="mb-4 max-w-3xl rounded-lg border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900" data-needs-type>
        {{ t('pricing::admin.price_lists_form.store_has_only_one_list') }} <span class="font-mono">base</span>{{ t('pricing::admin.price_lists_form.list_surplus_list_price_type') }} <strong>Sale</strong> {{ t('pricing::admin.price_lists_form.text') }}
        <strong>{{ t('pricing::admin.price_lists_form.member') }}</strong> {{ t('pricing::admin.price_lists_form.then_save_delete_if_unused') }}
    </div>

    <form class="grid max-w-3xl gap-4 rounded-lg border border-slate-200 bg-white p-5 sm:grid-cols-2" @submit.prevent="submit">
        <FormField :label="t('pricing::admin.price_lists_form.type')" class="sm:col-span-2" :error="form.errors.type">
            <div v-if="isMainBase" class="text-sm text-slate-600">{{ labels.price_list_type.base }} — {{ typeHelp.base }}</div>
            <div v-else class="grid gap-2 sm:grid-cols-2" role="radiogroup" :aria-label="t('pricing::admin.price_lists_form.price_list_type')">
                <label v-for="type in types" :key="type" class="flex cursor-pointer gap-2 rounded-md border p-3 text-sm" :class="form.type === type ? 'border-indigo-500 bg-indigo-50' : 'border-slate-200'">
                    <input v-model="form.type" type="radio" :value="type" class="mt-0.5" />
                    <span><span class="font-medium">{{ type === 'sale' ? 'Sale' : t('pricing::admin.price_lists_form.member') }}</span><span class="block text-xs text-slate-500">{{ typeHelp[type] }}</span></span>
                </label>
            </div>
        </FormField>
        <FormField v-if="form.type === 'member'" :label="t('pricing::admin.price_lists_form.customer_groups')" class="sm:col-span-2" :hint="t('pricing::admin.price_lists_form.customers_group_see_these_prices')" :error="form.errors.customer_group_id">
            <Select v-model="form.customer_group_id" :options="recordOptions(customerGroups, (group) => group.name)" :empty-label="t('pricing::admin.price_lists_form.choose_customer_group')" :empty-value="null" />
        </FormField>
        <FormField :label="t('pricing::admin.price_lists_form.name')" class="sm:col-span-2" :error="form.errors.name"><input v-model="form.name" :class="inputClass" :placeholder="t('pricing::admin.price_lists_form.end_season_sale_12_2026')" :aria-label="t('pricing::admin.price_lists_form.price_list_name')" /></FormField>
        <FormField :label="t('pricing::admin.price_lists_form.started')" :hint="t('pricing::admin.price_lists_form.leave_empty_starts_now')" :error="form.errors.starts_at"><DateInput v-model="form.starts_at" with-time /></FormField>
        <FormField :label="t('pricing::admin.price_lists_form.ends')" :hint="t('pricing::admin.price_lists_form.leave_empty_never_expires')" :error="form.errors.ends_at"><DateInput v-model="form.ends_at" with-time /></FormField>
        <FormField v-if="priceList" :label="t('pricing::admin.price_lists_form.use')" :error="form.errors.status">
            <label class="flex items-center gap-2 text-sm"><input v-model="form.status" type="checkbox" true-value="active" false-value="inactive" /> {{ form.status === 'active' ? t('pricing::admin.price_lists_form.enable') : t('pricing::admin.price_lists_form.off_customers_do_not_see') }}</label>
        </FormField>

        <details class="sm:col-span-2 text-sm" :open="advanced">
            <summary class="cursor-pointer text-slate-500">{{ t('pricing::admin.price_lists_form.advanced_code_priority') }}</summary>
            <div class="mt-3 grid gap-4 sm:grid-cols-2">
                <FormField :label="t('pricing::admin.price_lists_form.code')" :hint="priceList ? t('pricing::admin.price_lists_form.lowercase_letters_digits') : t('pricing::admin.price_lists_form.leave_empty_generated_from_name')" :error="form.errors.code">
                    <input v-model="form.code" :class="inputClass" :disabled="isMainBase" />
                </FormField>
                <FormField :label="t('pricing::admin.price_lists_form.priority')" :hint="t('pricing::admin.price_lists_form.higher_list_wins_when_sku', { type: defaultPriorities[form.type] ?? 10 })" :error="form.errors.priority">
                    <input v-model.number="form.priority" :class="inputClass" type="number" />
                </FormField>
            </div>
        </details>

        <div class="sm:col-span-2"><button type="submit" :class="primaryButton" :disabled="form.processing">{{ priceList ? t('pricing::admin.price_lists_form.save') : t('pricing::admin.price_lists_form.create_price_list') }}</button></div>
    </form>
</template>
