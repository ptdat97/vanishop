<script setup lang="ts">
import type { SharedProps } from '@admin/types';
import { type DateValue, parseDate, parseDateTime } from '@internationalized/date';
import { usePage } from '@inertiajs/vue3';
import {
    DatePickerCalendar,
    DatePickerCell,
    DatePickerCellTrigger,
    DatePickerContent,
    DatePickerField,
    DatePickerGrid,
    DatePickerGridBody,
    DatePickerGridHead,
    DatePickerGridRow,
    DatePickerHeadCell,
    DatePickerHeader,
    DatePickerHeading,
    DatePickerInput,
    DatePickerNext,
    DatePickerPrev,
    DatePickerRoot,
    DatePickerTrigger,
} from 'reka-ui';
import { computed } from 'vue';

/**
 * Chọn ngày (`YYYY-MM-DD`) hoặc ngày giờ (`withTime`: `YYYY-MM-DDTHH:mm`, cùng định dạng `datetime-local`); chuỗi rỗng
 * = chưa chọn. Thứ tự ngày/tháng và tên tháng theo ngôn ngữ Admin.
 */
const props = defineProps<{ withTime?: boolean; id?: string; ariaLabel?: string; disabled?: boolean }>();
const model = defineModel<string | null>({ required: true });

const locale = usePage<SharedProps>().props.app.locale;
const value = computed<DateValue | undefined>({
    get: () => {
        if (!model.value) {
            return undefined;
        }
        try {
            return props.withTime ? parseDateTime(model.value.slice(0, 16)) : parseDate(model.value.slice(0, 10));
        } catch {
            return undefined;
        }
    },
    set: (date) => (model.value = date === undefined ? '' : date.toString().slice(0, props.withTime ? 16 : 10)),
});
</script>

<template>
    <DatePickerRoot v-model="value" :locale="locale" :granularity="withTime ? 'minute' : 'day'" :hour-cycle="24" :disabled="disabled" :week-starts-on="1" fixed-weeks>
        <DatePickerField
            :id="id"
            v-slot="{ segments }"
            :aria-label="ariaLabel"
            class="flex w-full items-center rounded-md border border-slate-300 bg-white px-3 py-2 text-sm focus-within:border-indigo-500 data-disabled:bg-slate-50 data-disabled:text-slate-500"
        >
            <div class="flex flex-1 items-center">
                <template v-for="segment in segments" :key="segment.part">
                    <DatePickerInput v-if="segment.part === 'literal'" :part="segment.part" class="text-slate-400">{{ segment.value }}</DatePickerInput>
                    <DatePickerInput v-else :part="segment.part" class="rounded px-0.5 tabular-nums focus:bg-indigo-50 focus:text-indigo-700 focus:outline-none data-placeholder:text-slate-400">{{ segment.value }}</DatePickerInput>
                </template>
            </div>
            <DatePickerTrigger class="ml-2 text-slate-400 hover:text-slate-700" aria-label="Mở lịch">📅</DatePickerTrigger>
        </DatePickerField>
        <DatePickerContent :side-offset="4" class="z-50 rounded-md border border-slate-200 bg-white p-3 shadow-lg">
            <DatePickerCalendar v-slot="{ weekDays, grid }">
                <DatePickerHeader class="mb-2 flex items-center justify-between">
                    <DatePickerPrev class="rounded px-2 py-1 text-slate-600 hover:bg-slate-100" aria-label="Tháng trước">‹</DatePickerPrev>
                    <DatePickerHeading class="text-sm font-medium" />
                    <DatePickerNext class="rounded px-2 py-1 text-slate-600 hover:bg-slate-100" aria-label="Tháng sau">›</DatePickerNext>
                </DatePickerHeader>
                <DatePickerGrid v-for="month in grid" :key="month.value.toString()" class="w-full border-collapse select-none">
                    <DatePickerGridHead>
                        <DatePickerGridRow class="flex">
                            <DatePickerHeadCell v-for="day in weekDays" :key="day" class="w-8 text-xs font-normal text-slate-500">{{ day }}</DatePickerHeadCell>
                        </DatePickerGridRow>
                    </DatePickerGridHead>
                    <DatePickerGridBody>
                        <DatePickerGridRow v-for="(week, index) in month.rows" :key="index" class="flex">
                            <DatePickerCell v-for="day in week" :key="day.toString()" :date="day" class="p-0">
                                <DatePickerCellTrigger
                                    :day="day"
                                    :month="month.value"
                                    class="flex h-8 w-8 items-center justify-center rounded text-sm text-slate-700 outline-none hover:bg-indigo-50 focus:ring-2 focus:ring-indigo-400 data-outside-view:text-slate-300 data-selected:bg-indigo-600 data-selected:text-white data-today:font-semibold"
                                />
                            </DatePickerCell>
                        </DatePickerGridRow>
                    </DatePickerGridBody>
                </DatePickerGrid>
            </DatePickerCalendar>
        </DatePickerContent>
    </DatePickerRoot>
</template>
