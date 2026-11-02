<script setup lang="ts">
import { FieldLabel } from '@lunarphp/panel';
import { fieldTypes, type BlockField } from './fields';

export type { BlockField };

const props = defineProps<{
    fields: BlockField[];
    data: Record<string, unknown>;
    errors?: Record<string, string>;
}>();

// The block's data object is already reactive inside the page form — writing
// straight to it is the whole reactivity contract, no per-field emits.
const set = (field: BlockField, value: unknown): void => {
    props.data[field.key] = value;
};
</script>

<template>
    <div class="grid grid-cols-2 gap-3">
        <!-- grid-column inline: add-ons share the panel's compiled CSS, so
             col-span-N classes are not guaranteed to exist. -->
        <div v-for="field in fields" :key="field.key" :style="{ gridColumn: 'span ' + (field.cols ?? 2) }">
            <FieldLabel :required="field.required">{{ field.label }}</FieldLabel>

            <component
                :is="fieldTypes[field.type]"
                v-if="fieldTypes[field.type]"
                :field="field"
                :model-value="data[field.key]"
                :invalid="!!errors?.[field.key]"
                @update:model-value="(value) => set(field, value)"
            />
            <p v-else class="text-[12px] text-ink-500">Unsupported field type: {{ field.type }}</p>

            <p v-if="errors?.[field.key]" class="mt-1 text-[11px] text-danger">{{ errors[field.key] }}</p>
        </div>
    </div>
</template>
