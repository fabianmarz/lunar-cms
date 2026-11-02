<script setup lang="ts">
import { Select } from '@lunarphp/panel';
import { fieldString, type BlockField } from './types';

defineProps<{
    field: BlockField;
    modelValue?: unknown;
    invalid?: boolean;
}>();

const emit = defineEmits<{ 'update:modelValue': [value: string | number | null] }>();
</script>

<template>
    <Select
        :model-value="fieldString(modelValue)"
        :invalid="invalid"
        :aria-label="field.label"
        @update:model-value="(value) => emit('update:modelValue', value)"
    >
        <option value=""></option>
        <option v-for="option in field.config?.options ?? []" :key="String(option.value)" :value="option.value">
            {{ option.label }}
        </option>
    </Select>
</template>
