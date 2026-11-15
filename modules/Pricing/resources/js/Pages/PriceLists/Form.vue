<script setup lang="ts">
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
    sale: 'Giá giảm theo đợt (sale cuối mùa, 11.11…) cho mọi khách.',
    member: 'Giá riêng cho một nhóm khách (VIP, thành viên…).',
    base: 'Giá bán thường của cửa hàng — sửa giá ở sản phẩm.',
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
    if (props.priceList && (await confirmAction({ title: 'Xoá bảng giá?', message: 'Toàn bộ giá trong bảng cũng bị xoá.', confirmLabel: 'Xoá', tone: 'danger' })) !== false) {
        router.delete(`${props.baseUrl}/${props.priceList.id}`);
    }
}
</script>

<template>
    <Head :title="priceList ? 'Thông tin bảng giá' : 'Thêm bảng giá'" />
    <PageHeader :title="priceList ? `Thông tin bảng giá: ${priceList.name}` : 'Thêm bảng giá'">
        <Link :href="priceList ? `${baseUrl}/${priceList.id}/prices` : baseUrl" :class="secondaryButton">{{ priceList ? 'Về trang giá' : 'Quay lại' }}</Link>
        <button v-if="priceList && !isMainBase" type="button" :class="dangerButton" @click="destroy">Xoá</button>
    </PageHeader>

    <div v-if="needsType" class="mb-4 max-w-3xl rounded-lg border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900" data-needs-type>
        Cửa hàng chỉ có một bảng giá niêm yết (mã <span class="font-mono">base</span>). Bảng này đang là loại niêm yết thừa — chọn <strong>Sale</strong> hoặc
        <strong>Thành viên</strong> rồi Lưu, hoặc Xoá nếu không dùng.
    </div>

    <form class="grid max-w-3xl gap-4 rounded-lg border border-slate-200 bg-white p-5 sm:grid-cols-2" @submit.prevent="submit">
        <FormField label="Loại" class="sm:col-span-2" :error="form.errors.type">
            <div v-if="isMainBase" class="text-sm text-slate-600">{{ labels.price_list_type.base }} — {{ typeHelp.base }}</div>
            <div v-else class="grid gap-2 sm:grid-cols-2" role="radiogroup" aria-label="Loại bảng giá">
                <label v-for="type in types" :key="type" class="flex cursor-pointer gap-2 rounded-md border p-3 text-sm" :class="form.type === type ? 'border-indigo-500 bg-indigo-50' : 'border-slate-200'">
                    <input v-model="form.type" type="radio" :value="type" class="mt-0.5" />
                    <span><span class="font-medium">{{ type === 'sale' ? 'Sale' : 'Thành viên' }}</span><span class="block text-xs text-slate-500">{{ typeHelp[type] }}</span></span>
                </label>
            </div>
        </FormField>
        <FormField v-if="form.type === 'member'" label="Nhóm khách" class="sm:col-span-2" hint="Khách thuộc nhóm này thấy giá trong bảng khi đăng nhập." :error="form.errors.customer_group_id">
            <Select v-model="form.customer_group_id" :options="recordOptions(customerGroups, (group) => group.name)" empty-label="— Chọn nhóm khách —" :empty-value="null" />
        </FormField>
        <FormField label="Tên" class="sm:col-span-2" :error="form.errors.name"><input v-model="form.name" :class="inputClass" placeholder="vd. Sale cuối mùa 12/2026" aria-label="Tên bảng giá" /></FormField>
        <FormField label="Bắt đầu" hint="Để trống = chạy ngay" :error="form.errors.starts_at"><DateInput v-model="form.starts_at" with-time /></FormField>
        <FormField label="Kết thúc" hint="Để trống = không hết hạn" :error="form.errors.ends_at"><DateInput v-model="form.ends_at" with-time /></FormField>
        <FormField v-if="priceList" label="Đang dùng" :error="form.errors.status">
            <label class="flex items-center gap-2 text-sm"><input v-model="form.status" type="checkbox" true-value="active" false-value="inactive" /> {{ form.status === 'active' ? 'Bật' : 'Tắt — khách không thấy giá trong bảng này' }}</label>
        </FormField>

        <details class="sm:col-span-2 text-sm" :open="advanced">
            <summary class="cursor-pointer text-slate-500">Nâng cao (mã, độ ưu tiên)</summary>
            <div class="mt-3 grid gap-4 sm:grid-cols-2">
                <FormField label="Mã" :hint="priceList ? 'Chữ thường, số, _ -.' : 'Để trống = tự sinh từ tên.'" :error="form.errors.code">
                    <input v-model="form.code" :class="inputClass" :disabled="isMainBase" />
                </FormField>
                <FormField label="Độ ưu tiên" :hint="`Bảng cao hơn thắng khi một SKU có nhiều giá. Để trống = ${defaultPriorities[form.type] ?? 10}.`" :error="form.errors.priority">
                    <input v-model.number="form.priority" :class="inputClass" type="number" />
                </FormField>
            </div>
        </details>

        <div class="sm:col-span-2"><button type="submit" :class="primaryButton" :disabled="form.processing">{{ priceList ? 'Lưu' : 'Tạo bảng giá' }}</button></div>
    </form>
</template>
