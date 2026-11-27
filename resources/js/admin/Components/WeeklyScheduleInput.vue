<script lang="ts">
export type WeeklyWindow = { days: number[]; from: string; to: string };
</script>

<script setup lang="ts">
import { t } from '@admin/i18n';
import { inputClass, secondaryButton } from '@admin/styles';
import { computed } from 'vue';

/**
 * Lịch lặp theo tuần (0.5.116, `Shared\Domain\Schedule\WeeklySchedule`): tối đa 3 khung, mỗi khung = các ngày trong tuần +
 * giờ bắt đầu/kết thúc theo giờ cửa hàng. Kết thúc ≤ bắt đầu = qua nửa đêm. `null` = không lặp (chạy suốt khoảng thời gian).
 */
const model = defineModel<WeeklyWindow[] | null>({ required: true });
defineProps<{ disabled?: boolean; error?: string }>();

const MAX_WINDOWS = 3;
const days = computed(() => [1, 2, 3, 4, 5, 6, 7].map((value) => ({ value, label: t(`shared::admin.weekly_schedule.day_${value}`) })));
const enabled = computed({
    get: () => model.value !== null && model.value.length > 0,
    set: (on: boolean) => (model.value = on ? [{ days: [6, 7], from: '00:00', to: '00:00' }] : null),
});

function toggleDay(window: WeeklyWindow, day: number): void {
    window.days = window.days.includes(day) ? window.days.filter((value) => value !== day) : [...window.days, day].sort();
}

function addWindow(): void {
    if (model.value && model.value.length < MAX_WINDOWS) {
        model.value = [...model.value, { days: [1, 2, 3, 4, 5], from: '12:00', to: '14:00' }];
    }
}

function removeWindow(index: number): void {
    const next = (model.value ?? []).filter((_, position) => position !== index);
    model.value = next.length ? next : null;
}
</script>

<template>
    <div class="space-y-2" data-weekly-schedule>
        <label class="flex items-center gap-2 text-sm"><input v-model="enabled" type="checkbox" :disabled="disabled" /> {{ t('shared::admin.weekly_schedule.repeat_weekly') }}</label>
        <template v-if="model && model.length">
            <div v-for="(window, index) in model" :key="index" class="flex flex-wrap items-center gap-2 rounded border border-slate-200 p-2">
                <div class="flex flex-wrap gap-1" role="group" :aria-label="t('shared::admin.weekly_schedule.days')">
                    <button
                        v-for="day in days"
                        :key="day.value"
                        type="button"
                        :disabled="disabled"
                        :aria-pressed="window.days.includes(day.value)"
                        class="rounded border px-2 py-1 text-xs"
                        :class="window.days.includes(day.value) ? 'border-indigo-500 bg-indigo-50 text-indigo-700' : 'border-slate-300 text-slate-600'"
                        @click="toggleDay(window, day.value)"
                    >{{ day.label }}</button>
                </div>
                <input v-model="window.from" type="time" :class="[inputClass, 'w-28']" :disabled="disabled" :aria-label="t('shared::admin.weekly_schedule.from')" />
                <span class="text-slate-400">→</span>
                <input v-model="window.to" type="time" :class="[inputClass, 'w-28']" :disabled="disabled" :aria-label="t('shared::admin.weekly_schedule.to')" />
                <button v-if="!disabled" type="button" class="text-xs text-red-600 hover:underline" @click="removeWindow(index)">{{ t('shared::admin.weekly_schedule.remove') }}</button>
            </div>
            <button v-if="!disabled && model.length < MAX_WINDOWS" type="button" :class="secondaryButton" @click="addWindow">{{ t('shared::admin.weekly_schedule.add_window') }}</button>
            <p class="text-xs text-slate-500">{{ t('shared::admin.weekly_schedule.hint') }}</p>
        </template>
        <p v-if="error" class="text-sm text-red-600">{{ error }}</p>
    </div>
</template>
