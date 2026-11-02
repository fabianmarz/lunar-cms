<script setup lang="ts">
import { Textarea } from '@lunarphp/panel';
import { fieldString, type BlockField } from './types';

defineProps<{
    field: BlockField;
    modelValue?: unknown;
    invalid?: boolean;
}>();

const emit = defineEmits<{ 'update:modelValue': [value: string] }>();
</script>

<template>
    <Textarea
        :model-value="fieldString(modelValue)"
        :invalid="invalid"
        :aria-label="field.label"
        @update:model-value="(value) => emit('update:modelValue', value)"
    />
</template>
