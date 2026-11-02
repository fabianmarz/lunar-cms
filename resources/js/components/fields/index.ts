import { shallowReactive, type Component } from 'vue';
import TextField from './TextField.vue';
import TextareaField from './TextareaField.vue';
import SelectField from './SelectField.vue';
import ToggleField from './ToggleField.vue';
import RichTextField from './RichTextField.vue';
import type { BlockField } from './types';

export type { BlockField };
export { fieldString } from './types';

/**
 * Field-type → renderer registry — the JS mirror of BlockRegistry. A renderer
 * accepts { field, modelValue, invalid } and emits update:modelValue; add a new
 * type by registering a component, no edits to BlockFields needed.
 */
export const fieldTypes = shallowReactive<Record<string, Component>>({
    text: TextField,
    url: TextField,
    number: TextField,
    textarea: TextareaField,
    select: SelectField,
    toggle: ToggleField,
    richtext: RichTextField,
});

export const registerFieldType = (type: string, component: Component): void => {
    fieldTypes[type] = component;
};
