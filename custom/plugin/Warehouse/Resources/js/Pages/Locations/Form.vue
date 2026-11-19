<script setup lang="ts">
import { t } from '@admin/i18n';
import { useAdminForm } from '@admin/forms';
import FormField from '@admin/Components/FormField.vue';
import PageHeader from '@admin/Components/PageHeader.vue';
import Select from '@admin/Components/Select.vue';
import { codeOptions, labelOptions } from '@admin/options';
import { inputClass, primaryButton, secondaryButton } from '@admin/styles';
import { Head, Link } from '@inertiajs/vue3';
import type { WarehouseLabels } from '../../labels';

const props = defineProps<{
    baseUrl: string;
    labels: WarehouseLabels;
    location: null | {
        id: number;
        code: string;
        name: string;
        type: string;
        address: string | null;
        province_code: string | null;
        ships_online_orders: boolean;
        allows_pickup: boolean;
        accepts_returns: boolean;
        stock_authority: string;
        priority: number;
        status: string;
        lock_version: number;
    };
    types: string[];
}>();

const { form, save } = useAdminForm({
    code: props.location?.code ?? '',
    name: props.location?.name ?? '',
    type: props.location?.type ?? 'warehouse',
    address: props.location?.address ?? '',
    province_code: props.location?.province_code ?? '',
    ships_online_orders: props.location?.ships_online_orders ?? true,
    allows_pickup: props.location?.allows_pickup ?? false,
    accepts_returns: props.location?.accepts_returns ?? true,
    stock_authority: props.location?.stock_authority ?? 'vanishop',
    priority: props.location?.priority ?? 0,
    status: props.location?.status ?? 'active',
    lock_version: props.location?.lock_version ?? null,
}, { lockVersion: () => props.location?.lock_version });

function submit(): void {
    if (props.location) {
        save('put', `${props.baseUrl}/${props.location.id}`);
    } else {
        save('post', props.baseUrl);
    }
}
</script>

<template>
    <Head :title="location ? t('vani-warehouse::admin.locations_form.edit_location') : t('vani-warehouse::admin.locations_form.add_location')" />
    <PageHeader :title="location ? t('vani-warehouse::admin.locations_form.edit_location_name', { name: location.name }) : t('vani-warehouse::admin.locations_form.add_location')">
        <Link :href="baseUrl" :class="secondaryButton">{{ t('vani-warehouse::admin.locations_form.back') }}</Link>
    </PageHeader>

    <form class="grid max-w-3xl gap-4 rounded-lg border border-slate-200 bg-white p-5 sm:grid-cols-2" @submit.prevent="submit">
        <FormField :label="t('vani-warehouse::admin.locations_form.name')" :error="form.errors.name"><input v-model="form.name" :class="inputClass" /></FormField>
        <FormField :label="t('vani-warehouse::admin.locations_form.code')" :hint="t('vani-warehouse::admin.locations_form.uppercase_letters_digits_example_wh')" :error="form.errors.code"><input v-model="form.code" :class="inputClass" /></FormField>
        <FormField :label="t('vani-warehouse::admin.locations_form.type')" :error="form.errors.type">
            <Select v-model="form.type" :options="codeOptions(types, labels.location_type)" />
        </FormField>
        <FormField :label="t('vani-warehouse::admin.locations_form.address')" :error="form.errors.address"><input v-model="form.address" :class="inputClass" /></FormField>
        <FormField :label="t('vani-warehouse::admin.locations_form.province_city_code')" :error="form.errors.province_code"><input v-model="form.province_code" :class="inputClass" /></FormField>
        <FormField label="Priority" :hint="t('vani-warehouse::admin.locations_form.higher_priority_reserves_stock_first')" :error="form.errors.priority">
            <input v-model.number="form.priority" :class="inputClass" type="number" />
        </FormField>
        <FormField :label="t('vani-warehouse::admin.locations_form.manages_physical_stock')" :hint="t('vani-warehouse::admin.locations_form.vanishop_adjusted_here_another_code')" :error="form.errors.stock_authority">
            <input v-model="form.stock_authority" :class="inputClass" />
        </FormField>
        <FormField :label="t('vani-warehouse::admin.locations_form.capabilities')" :error="form.errors.ships_online_orders">
            <div class="space-y-1 text-sm">
                <label class="flex items-center gap-2"><input v-model="form.ships_online_orders" type="checkbox" /> {{ t('vani-warehouse::admin.locations_form.ships_online_orders') }}</label>
                <label class="flex items-center gap-2"><input v-model="form.allows_pickup" type="checkbox" /> {{ t('vani-warehouse::admin.locations_form.store_pickup') }}</label>
                <label class="flex items-center gap-2"><input v-model="form.accepts_returns" type="checkbox" /> {{ t('vani-warehouse::admin.locations_form.receives_returns') }}</label>
            </div>
        </FormField>
        <FormField :label="t('vani-warehouse::admin.locations_form.status')" :error="form.errors.status">
            <Select v-model="form.status" :options="labelOptions(labels.location_status)" />
        </FormField>
        <div class="sm:col-span-2"><button type="submit" :class="primaryButton" :disabled="form.processing">{{ t('vani-warehouse::admin.locations_form.save') }}</button></div>
    </form>
</template>
