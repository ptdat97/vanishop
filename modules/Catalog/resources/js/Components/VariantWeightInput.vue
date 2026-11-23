<script setup lang="ts">
import { t } from '@admin/i18n';
import { inputClass } from '@admin/styles';
import { computed, useId } from 'vue';

defineProps<{ defaultGrams: number }>();
const weight = defineModel<number | null>({ required: true });
const helpId = useId();
const usesDefault = computed(() => weight.value === null || weight.value <= 0);

function updateWeight(event: Event): void {
    const grams = (event.target as HTMLInputElement).valueAsNumber;
    weight.value = Number.isNaN(grams) ? null : grams;
}
</script>

<template>
    <div class="w-24">
        <input
            type="number"
            min="0"
            :value="weight"
            :placeholder="String(defaultGrams)"
            :class="inputClass"
            :aria-label="t('catalog::admin.variant_weight_input.label')"
            :aria-describedby="usesDefault ? helpId : undefined"
            @input="updateWeight"
        />
        <p v-if="usesDefault" :id="helpId" class="mt-1 text-xs text-slate-600" data-default-weight>
            {{ t('catalog::admin.variant_weight_input.default_grams', { grams: defaultGrams }) }}
        </p>
    </div>
</template>
