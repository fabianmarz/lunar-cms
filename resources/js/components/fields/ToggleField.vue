<script setup lang="ts">
import { Toggle } from '@lunarphp/panel';
import type { BlockField } from './types';

defineProps<{
    field: BlockField;
    modelValue?: unknown;
    invalid?: boolean;
}>();

const emit = defineEmits<{ 'update:modelValue': [value: boolean] }>();
</script>

<template>
    <div class="h-8 flex items-center">
        <Toggle :on="!!modelValue" @toggle="emit('update:modelValue', !modelValue)" />
    </div>
</template>
