<script setup lang="ts">
import { onBeforeUnmount, ref, watch } from 'vue';
import { EditorContent, useEditor } from '@tiptap/vue-3';
import StarterKit from '@tiptap/starter-kit';
import { Button } from '@lunarphp/panel';
import { withTranslation, type LanguageOption, type Translations } from '../../translations';

type RichTextData = { content?: Translations };

const props = defineProps<{ data: RichTextData; languages: LanguageOption[] }>();
const emit = defineEmits<{ 'update:data': [value: RichTextData] }>();

// Per-field language picker, as on the panel's translated rich text fields.
const activeCode = ref(props.languages.find((language) => language.default)?.code ?? props.languages[0]?.code ?? 'en');

const currentHtml = (): string => props.data.content?.[activeCode.value] ?? '';

// Stores HTML per language, like the panel's own rich text fields, so the
// value renders on the storefront without a document-to-HTML step.
const editor = useEditor({
    content: currentHtml(),
    extensions: [StarterKit],
    onUpdate: ({ editor: instance }) => {
        emit('update:data', {
            ...props.data,
            content: withTranslation(props.data.content, activeCode.value, instance.isEmpty ? '' : instance.getHTML()),
        });
    },
});

// Switching language swaps the document the editor shows.
watch(
    activeCode,
    () => editor.value?.commands.setContent(currentHtml(), { emitUpdate: false }),
);

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
    <div>
        <div v-if="languages.length > 1" class="flex mb-1.5">
            <select
                v-model="activeCode"
                class="h-[22px] px-1 border border-line-strong rounded-md bg-surface-2 text-[10.5px] font-mono uppercase tracking-wide text-ink-700 cursor-pointer focus:outline-none focus-visible:ring-2 focus-visible:ring-sage/35"
                aria-label="Language"
            >
                <option v-for="language in languages" :key="language.code" :value="language.code" :title="language.name ?? language.code">
                    {{ language.code.toUpperCase() }}
                </option>
            </select>
        </div>
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
    </div>
</template>
