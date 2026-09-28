<script setup lang="ts">
import FormField from '@admin/Components/FormField.vue';
import PageHeader from '@admin/Components/PageHeader.vue';
import { inputClass, primaryButton, secondaryButton } from '@admin/styles';
import { Head, Link, useForm } from '@inertiajs/vue3';

const props = defineProps<{
    baseUrl: string;
    location: null | {
        id: number;
        code: string;
        name: string;
        type: string;
        legal_entity_id: number;
        address: string | null;
        province_code: string | null;
        ships_online_orders: boolean;
        allows_pickup: boolean;
        accepts_returns: boolean;
        stock_authority: string;
        priority: number;
        status: string;
        lock_version: number;
        brand_ids: number[];
        channel_ids: number[];
    };
    brands: Array<{ id: number; name: string }>;
    channels: Array<{ id: number; code: string; name: string }>;
    legalEntities: Array<{ id: number; code: string; name: string }>;
    types: string[];
}>();

const typeLabels: Record<string, string> = { warehouse: 'Kho', store: 'Cửa hàng', virtual: 'Ảo (dropship, kho đối tác)' };
const form = useForm({
    code: props.location?.code ?? '',
    name: props.location?.name ?? '',
    type: props.location?.type ?? 'warehouse',
    legal_entity_id: props.location?.legal_entity_id ?? props.legalEntities[0]?.id ?? null,
    address: props.location?.address ?? '',
    province_code: props.location?.province_code ?? '',
    ships_online_orders: props.location?.ships_online_orders ?? true,
    allows_pickup: props.location?.allows_pickup ?? false,
    accepts_returns: props.location?.accepts_returns ?? true,
    stock_authority: props.location?.stock_authority ?? 'vanishop',
    priority: props.location?.priority ?? 0,
    status: props.location?.status ?? 'active',
    brand_ids: props.location?.brand_ids ?? ([] as number[]),
    channel_ids: props.location?.channel_ids ?? ([] as number[]),
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
            <select v-model="form.type" :class="inputClass">
                <option v-for="type in types" :key="type" :value="type">{{ typeLabels[type] ?? type }}</option>
            </select>
        </FormField>
        <FormField label="Pháp nhân" :error="form.errors.legal_entity_id">
            <select v-model="form.legal_entity_id" :class="inputClass">
                <option v-for="entity in legalEntities" :key="entity.id" :value="entity.id">{{ entity.name }}</option>
            </select>
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
            <select v-model="form.status" :class="inputClass">
                <option value="active">Đang dùng</option>
                <option value="inactive">Tắt</option>
            </select>
        </FormField>
        <FormField label="Brand có hàng tại đây" :error="form.errors.brand_ids">
            <div class="space-y-1">
                <label v-for="brand in brands" :key="brand.id" class="flex items-center gap-2 text-sm">
                    <input v-model="form.brand_ids" type="checkbox" :value="brand.id" /> {{ brand.name }}
                </label>
            </div>
        </FormField>
        <FormField label="Kênh bán lấy hàng từ đây" :error="form.errors.channel_ids">
            <div class="space-y-1">
                <label v-for="channel in channels" :key="channel.id" class="flex items-center gap-2 text-sm">
                    <input v-model="form.channel_ids" type="checkbox" :value="channel.id" />
                    {{ channel.name }} <span class="font-mono text-xs text-slate-400">{{ channel.code }}</span>
                </label>
            </div>
        </FormField>
        <p v-if="form.errors.lock_version" class="text-sm text-red-600 sm:col-span-2">{{ form.errors.lock_version }}</p>
        <div class="sm:col-span-2"><button type="submit" :class="primaryButton" :disabled="form.processing">Lưu</button></div>
    </form>
</template>
