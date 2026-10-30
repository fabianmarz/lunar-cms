<script setup lang="ts">
import { EditorContent, useEditor } from '@tiptap/vue-3';
import StarterKit from '@tiptap/starter-kit';
import { watch } from 'vue';

const props = defineProps<{ data: { content?: unknown } }>();
const emit = defineEmits<{ 'update:data': [value: { content: unknown }] }>();

const editor = useEditor({
    content: props.data.content ?? '',
    extensions: [StarterKit],
    onUpdate: ({ editor }) => {
        emit('update:data', { content: editor.getJSON() });
    },
});
</script>

<template>
    <div class="rounded-md border border-line bg-surface px-3 py-2 text-[13px] [&_.tiptap]:min-h-24 [&_.tiptap]:outline-none">
        <EditorContent :editor="editor" />
    </div>
</template>
