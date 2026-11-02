<script setup lang="ts">
import { computed } from 'vue';
import { TextInput } from '@lunarphp/panel';
import { fieldString, type BlockField } from './types';

const props = defineProps<{
    field: BlockField;
    modelValue?: unknown;
    invalid?: boolean;
}>();

const emit = defineEmits<{ 'update:modelValue': [value: string | number | null] }>();

const inputType = computed(() => (props.field.type === 'number' || props.field.type === 'url' ? props.field.type : 'text'));

const update = (value: string): void => {
    if (props.field.type !== 'number') {
        emit('update:modelValue', value);

        return;
    }

    emit('update:modelValue', value === '' ? null : Number(value));
};
</script>

<template>
    <TextInput
        :type="inputType"
        :model-value="fieldString(modelValue)"
        :invalid="invalid"
        :aria-label="field.label"
        @update:model-value="update"
    />
</template>
