<script setup lang="ts">
import { TextInput } from '@lunarphp/panel';
import type { LanguageOption, Translations } from '../../translations';
import TranslatedField from '../TranslatedField.vue';

type CtaData = { label?: Translations; url?: string };

const props = defineProps<{ data: CtaData; languages: LanguageOption[] }>();
const emit = defineEmits<{ 'update:data': [value: CtaData] }>();

const setLabel = (value: Translations): void => {
    emit('update:data', { ...props.data, label: value });
};

const setUrl = (value: string): void => {
    emit('update:data', { ...props.data, url: value });
};
</script>

<template>
    <div class="grid grid-cols-2 gap-3">
        <TranslatedField :model-value="data.label" :languages="languages" placeholder="Button label" @update:model-value="setLabel" />
        <TextInput :model-value="data.url ?? ''" placeholder="URL (e.g. /contact)" @update:model-value="setUrl" />
    </div>
</template>
