<script setup lang="ts">
import { FieldLabel, Select, Textarea, TextInput, Toggle } from '@lunarphp/panel';
import RichTextField from './fields/RichTextField.vue';

export interface BlockField {
    key: string;
    type: string;
    label: string;
    required?: boolean;
    cols?: number;
    config?: { options?: { label: string; value: string | number }[] };
}

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

const str = (field: BlockField): string => {
    const value = props.data[field.key];

    return typeof value === 'string' || typeof value === 'number' ? String(value) : '';
};
</script>

<template>
    <div class="grid grid-cols-2 gap-3">
        <!-- grid-column inline: add-ons share the panel's compiled CSS, so
             col-span-N classes are not guaranteed to exist. -->
        <div v-for="field in fields" :key="field.key" :style="{ gridColumn: 'span ' + (field.cols ?? 2) }">
            <FieldLabel :required="field.required">{{ field.label }}</FieldLabel>

            <RichTextField
                v-if="field.type === 'richtext'"
                :model-value="str(field)"
                @update:model-value="(value) => set(field, value)"
            />
            <Textarea
                v-else-if="field.type === 'textarea'"
                :model-value="str(field)"
                :invalid="!!errors?.[field.key]"
                @update:model-value="(value) => set(field, value)"
            />
            <Select
                v-else-if="field.type === 'select'"
                :model-value="str(field)"
                :aria-label="field.label"
                @update:model-value="(value) => set(field, value)"
            >
                <option value=""></option>
                <option
                    v-for="option in field.config?.options ?? []"
                    :key="String(option.value)"
                    :value="option.value"
                >
                    {{ option.label }}
                </option>
            </Select>
            <div v-else-if="field.type === 'toggle'" class="h-8 flex items-center">
                <Toggle :on="!!data[field.key]" @toggle="set(field, !data[field.key])" />
            </div>
            <TextInput
                v-else-if="['text', 'url', 'number'].includes(field.type)"
                :type="field.type === 'number' ? 'number' : field.type === 'url' ? 'url' : 'text'"
                :model-value="str(field)"
                :invalid="!!errors?.[field.key]"
                :aria-label="field.label"
                @update:model-value="(value) => set(field, field.type === 'number' ? (value === '' ? null : Number(value)) : value)"
            />
            <p v-else class="text-[12px] text-ink-500">Unsupported field type: {{ field.type }}</p>

            <p v-if="errors?.[field.key]" class="mt-1 text-[11px] text-danger">{{ errors[field.key] }}</p>
        </div>
    </div>
</template>
