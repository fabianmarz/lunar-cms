<script setup lang="ts">
import { onBeforeUnmount } from 'vue';
import { EditorContent, useEditor } from '@tiptap/vue-3';
import StarterKit from '@tiptap/starter-kit';
import { Button } from '@lunarphp/panel';

type RichTextData = { content?: string };

const props = defineProps<{ data: RichTextData }>();
const emit = defineEmits<{ 'update:data': [value: RichTextData] }>();

// Stores HTML, like the panel's own rich text fields, so the value renders
// on the storefront without a document-to-HTML step. The editor is remounted
// when the content language changes, so it only ever holds one block's value.
const editor = useEditor({
    content: props.data.content ?? '',
    extensions: [StarterKit],
    onUpdate: ({ editor: instance }) => {
        emit('update:data', {
            ...props.data,
            content: instance.isEmpty ? '' : instance.getHTML(),
        });
    },
});

onBeforeUnmount(() => editor.value?.destroy());

const setLink = (): void => {
    if (!editor.value) {
        return;
    }

    const previous = editor.value.getAttributes('link').href as string | undefined;
    const url = window.prompt('URL', previous ?? '');

    if (url === null) {
        return;
    }

    const chain = editor.value.chain().focus().extendMarkRange('link');

    if (url === '') {
        chain.unsetLink().run();
    } else {
        chain.setLink({ href: url }).run();
    }
};

const active = 'bg-surface border-line-strong shadow-sm';
</script>

<template>
    <div
        class="border rounded-md overflow-hidden bg-surface border-line-strong hover:border-ink-300 focus-within:border-sage focus-within:ring-3 focus-within:ring-sage/35 transition-[border-color,box-shadow] duration-100"
    >
        <div v-if="editor" class="flex flex-wrap gap-0.5 px-2 py-1.5 border-b border-line bg-surface-2">
            <Button variant="ghost" size="sm" :class="editor.isActive('bold') ? active : ''" aria-label="Bold" @click="editor.chain().focus().toggleBold().run()"><b>B</b></Button>
            <Button variant="ghost" size="sm" :class="editor.isActive('italic') ? active : ''" aria-label="Italic" @click="editor.chain().focus().toggleItalic().run()"><i>I</i></Button>
            <Button variant="ghost" size="sm" :class="editor.isActive('underline') ? active : ''" aria-label="Underline" @click="editor.chain().focus().toggleUnderline().run()"><span class="underline">U</span></Button>
            <div class="w-px bg-line my-0.5 mx-1" />
            <Button variant="ghost" size="sm" :class="editor.isActive('heading', { level: 2 }) ? active : ''" aria-label="Heading 2" @click="editor.chain().focus().toggleHeading({ level: 2 }).run()">H2</Button>
            <Button variant="ghost" size="sm" :class="editor.isActive('heading', { level: 3 }) ? active : ''" aria-label="Heading 3" @click="editor.chain().focus().toggleHeading({ level: 3 }).run()">H3</Button>
            <div class="w-px bg-line my-0.5 mx-1" />
            <Button variant="ghost" size="sm" :class="editor.isActive('bulletList') ? active : ''" aria-label="Bullet list" @click="editor.chain().focus().toggleBulletList().run()">• List</Button>
            <Button variant="ghost" size="sm" :class="editor.isActive('orderedList') ? active : ''" aria-label="Numbered list" @click="editor.chain().focus().toggleOrderedList().run()">1. List</Button>
            <Button variant="ghost" size="sm" :class="editor.isActive('blockquote') ? active : ''" aria-label="Quote" @click="editor.chain().focus().toggleBlockquote().run()">“ ”</Button>
            <div class="w-px bg-line my-0.5 mx-1" />
            <Button variant="ghost" size="sm" icon="link" :class="editor.isActive('link') ? active : ''" aria-label="Link" @click="setLink" />
        </div>
        <div class="px-2.5 py-2 text-[13px] text-ink-900 leading-normal">
            <EditorContent :editor="editor" />
        </div>
    </div>
</template>
