<script setup lang="ts">
import { TextInput } from '@lunarphp/panel';
import { withTranslation, type Translations } from '../../translations';

type CtaData = { label?: Translations; url?: string };

const props = defineProps<{ data: CtaData; locale: string }>();
const emit = defineEmits<{ 'update:data': [value: CtaData] }>();

const setLabel = (value: string): void => {
    emit('update:data', { ...props.data, label: withTranslation(props.data.label, props.locale, value) });
};

const setUrl = (value: string): void => {
    emit('update:data', { ...props.data, url: value });
};
</script>

<template>
    <div class="grid grid-cols-2 gap-3">
        <TextInput :model-value="data.label?.[locale] ?? ''" placeholder="Button label" @update:model-value="setLabel" />
        <TextInput :model-value="data.url ?? ''" placeholder="URL (e.g. /contact)" @update:model-value="setUrl" />
    </div>
</template>
