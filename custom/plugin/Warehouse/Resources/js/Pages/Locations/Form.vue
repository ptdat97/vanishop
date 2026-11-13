<script setup lang="ts">
import FormField from '@admin/Components/FormField.vue';
import PageHeader from '@admin/Components/PageHeader.vue';
import Select from '@admin/Components/Select.vue';
import { codeOptions, labelOptions } from '@admin/options';
import { inputClass, primaryButton, secondaryButton } from '@admin/styles';
import { Head, Link, useForm } from '@inertiajs/vue3';
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

const form = useForm({
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
});

function submit(): void {
    if (props.location) {
        form.put(`${props.baseUrl}/${props.location.id}`, { preserveScroll: true });
    } else {
        form.post(props.baseUrl);
    }
}
</script>

<template>
    <Head :title="location ? 'Sửa location' : 'Thêm location'" />
    <PageHeader :title="location ? `Sửa location: ${location.name}` : 'Thêm location'">
        <Link :href="baseUrl" :class="secondaryButton">Quay lại</Link>
    </PageHeader>

    <form class="grid max-w-3xl gap-4 rounded-lg border border-slate-200 bg-white p-5 sm:grid-cols-2" @submit.prevent="submit">
        <FormField label="Tên" :error="form.errors.name"><input v-model="form.name" :class="inputClass" /></FormField>
        <FormField label="Mã" hint="Chữ hoa, số, -. Ví dụ: WH-HCM, ST-Q1" :error="form.errors.code"><input v-model="form.code" :class="inputClass" /></FormField>
        <FormField label="Loại" :error="form.errors.type">
            <Select v-model="form.type" :options="codeOptions(types, labels.location_type)" />
        </FormField>
        <FormField label="Địa chỉ" :error="form.errors.address"><input v-model="form.address" :class="inputClass" /></FormField>
        <FormField label="Mã tỉnh/thành" :error="form.errors.province_code"><input v-model="form.province_code" :class="inputClass" /></FormField>
        <FormField label="Priority" hint="Cao hơn được giữ hàng trước." :error="form.errors.priority">
            <input v-model.number="form.priority" :class="inputClass" type="number" />
        </FormField>
        <FormField label="Quản lý tồn vật lý" hint="vanishop = điều chỉnh tại đây; mã khác (vd. erp) = chỉ nhận đồng bộ." :error="form.errors.stock_authority">
            <input v-model="form.stock_authority" :class="inputClass" />
        </FormField>
        <FormField label="Khả năng" :error="form.errors.ships_online_orders">
            <div class="space-y-1 text-sm">
                <label class="flex items-center gap-2"><input v-model="form.ships_online_orders" type="checkbox" /> Giao đơn online</label>
                <label class="flex items-center gap-2"><input v-model="form.allows_pickup" type="checkbox" /> Nhận tại cửa hàng</label>
                <label class="flex items-center gap-2"><input v-model="form.accepts_returns" type="checkbox" /> Nhận hàng trả</label>
            </div>
        </FormField>
        <FormField label="Trạng thái" :error="form.errors.status">
            <Select v-model="form.status" :options="labelOptions(labels.location_status)" />
        </FormField>
        <div class="sm:col-span-2"><button type="submit" :class="primaryButton" :disabled="form.processing">Lưu</button></div>
    </form>
</template>
