<script setup lang="ts">
import { computed, ref } from 'vue';
import { TextInput } from '@lunarphp/panel';
import { withTranslation, type LanguageOption, type Translations } from '../translations';

// Mirrors the panel's TranslatedInput (which add-ons can't import): the
// language picker is an input-group prefix, one picker per field.
const props = defineProps<{
    modelValue: Translations | undefined;
    languages: LanguageOption[];
    invalid?: boolean;
    placeholder?: string;
}>();

const emit = defineEmits<{ 'update:modelValue': [value: Translations] }>();

const activeCode = ref(props.languages.find((language) => language.default)?.code ?? props.languages[0]?.code ?? 'en');

const currentValue = computed(() => props.modelValue?.[activeCode.value] ?? '');

const update = (value: string): void => {
    emit('update:modelValue', withTranslation(props.modelValue, activeCode.value, value));
};
</script>

<template>
    <TextInput :model-value="currentValue" :invalid="invalid" :placeholder="placeholder" @update:model-value="update">
        <template v-if="languages.length > 1" #prefix>
            <select
                v-model="activeCode"
                class="bg-transparent text-[10.5px] font-mono uppercase tracking-wide text-ink-700 cursor-pointer focus:outline-none"
                aria-label="Language"
            >
                <option v-for="language in languages" :key="language.code" :value="language.code" :title="language.name ?? language.code">
                    {{ language.code.toUpperCase() }}
                </option>
            </select>
        </template>
    </TextInput>
</template>
