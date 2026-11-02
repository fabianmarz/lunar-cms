<script setup lang="ts">
import { computed, ref, toRaw } from 'vue';
import { router, useForm } from '@inertiajs/vue3';
import {
    Breadcrumbs,
    Button,
    ConfirmDialog,
    DatePicker,
    FieldLabel,
    PageHeader,
    PageZone,
    SideCard,
    StatusBadge,
    TextInput,
    type BreadcrumbItem,
} from '@lunarphp/panel';
import BlockEditor from '../components/BlockEditor.vue';
import Section from '../components/Section.vue';
import TranslatedField from '../components/TranslatedField.vue';
import type { LanguageOption, Translations } from '../translations';

interface Block {
    id: string;
    type: string;
    enabled: boolean;
    data: Record<string, unknown>;
}

interface PageForm {
    id: number;
    title: Translations;
    slug: Translations;
    status: 'draft' | 'published';
    published_at: string | null;
    blocks: Record<string, Block[]>;
}

const props = defineProps<{
    page: PageForm | null;
    media: Record<number, { id: number; url: string; original_url?: string; name: string }>;
    blockTypes: Record<
        string,
        {
            label: string;
            icon: string;
            editor?: string | null;
            fields: { key: string; type: string; label: string; required?: boolean; cols?: number }[];
            defaults: Record<string, unknown>;
        }
    >;
    languages: LanguageOption[];
    urls: {
        store?: string;
        update?: string;
        destroy?: string;
        media?: string;
        mediaPicker?: string | null;
        mediaLibraryUpload?: string | null;
        index: string;
    };
}>();

const isEdit = computed(() => props.page !== null);
const confirmingDelete = ref(false);

const form = useForm({
    title: props.page?.title ?? {},
    slug: props.page?.slug ?? {},
    status: props.page?.status ?? 'draft',
    published_at: props.page?.published_at ?? '',
    blocks: props.page?.blocks ?? {},
});

// The content has its own language switch: every language keeps a separate
// block list, so translations can differ in structure, not just text.
const contentLocale = ref(props.languages.find((language) => language.default)?.code ?? props.languages[0]?.code ?? 'en');

const blocks = computed({
    get: () => form.blocks[contentLocale.value] ?? [],
    set: (value: Block[]) => (form.blocks = { ...form.blocks, [contentLocale.value]: value }),
});

const defaultLocale = computed(() => props.languages.find((language) => language.default)?.code ?? props.languages[0]?.code ?? 'en');

const canCopyFromDefault = computed(
    () => contentLocale.value !== defaultLocale.value && (form.blocks[defaultLocale.value]?.length ?? 0) > 0,
);

// Starts a translation from the default language's blocks, with fresh ids.
const copyFromDefault = (): void => {
    blocks.value = (form.blocks[defaultLocale.value] ?? []).map((block) => ({
        ...structuredClone(toRaw(block)),
        id: crypto.randomUUID(),
    }));
};

// Validation errors are keyed per language (`title.en`); surface the first.
const fieldError = (field: 'title' | 'slug'): string | undefined =>
    form.errors[field] ?? Object.entries(form.errors).find(([key]) => key.startsWith(`${field}.`))?.[1];

const displayTitle = computed(() => props.page?.title?.[defaultLocale.value] ?? Object.values(props.page?.title ?? {})[0]);

const breadcrumbs = computed<BreadcrumbItem[]>(() => [
    { label: 'Content' },
    { label: 'Pages', href: props.urls.index },
    { label: displayTitle.value || 'New page', current: true },
]);

const statusOptions = [
    { value: 'draft', label: 'Draft', tone: 'warn' },
    { value: 'published', label: 'Published', tone: 'sage' },
] as const;

const submit = (): void => {
    if (isEdit.value) {
        form.put(props.urls.update!);
    } else {
        form.post(props.urls.store!);
    }
};

const destroy = (): void => {
    router.delete(props.urls.destroy!, { preserveScroll: true });
};
</script>

<template>
    <div class="contents">
        <Breadcrumbs :items="breadcrumbs">
            <template #actions>
                <Button type="submit" form="cms-page-form" :disabled="form.processing">
                    {{ isEdit ? 'Save changes' : 'Create page' }}
                </Button>
            </template>
        </Breadcrumbs>

        <PageHeader :title="displayTitle || 'New page'">
            <template #description>
                <div class="flex gap-2 items-center flex-wrap">
                    <StatusBadge :tone="form.status === 'published' ? 'sage' : 'warn'" dot>
                        {{ form.status === 'published' ? 'Published' : 'Draft' }}
                    </StatusBadge>
                    <template v-if="page?.slug?.[defaultLocale]">
                        <span class="text-ink-500">·</span>
                        <span class="font-mono">/{{ page.slug[defaultLocale] }}</span>
                    </template>
                </div>
            </template>
            <template #actions>
                <Button
                    v-if="isEdit"
                    icon="trash"
                    class="!text-danger"
                    :disabled="form.processing"
                    @click="confirmingDelete = true"
                >
                    Delete
                </Button>
            </template>
        </PageHeader>

        <div class="px-4 sm:px-5 lg:px-7 max-w-[1400px] w-full mx-auto pt-5 pb-7">
            <PageZone region="main" position="before" :page="page" />

            <form id="cms-page-form" @submit.prevent="submit">
                <div class="flex flex-col gap-8 lg:grid lg:grid-cols-[minmax(0,1fr)_320px]">
                    <div class="min-w-0">
                        <Section title="Details">
                            <template #desc>The page title and the slug it will be reachable at.</template>
                            <div class="space-y-4">
                                <div>
                                    <FieldLabel>Title</FieldLabel>
                                    <TranslatedField v-model="form.title" :languages="languages" :invalid="!!fieldError('title')" />
                                    <p v-if="fieldError('title')" class="mt-1 text-[11px] text-danger">{{ fieldError('title') }}</p>
                                </div>
                                <div>
                                    <FieldLabel hint="Leave empty to derive it from the title">Slug</FieldLabel>
                                    <TranslatedField v-model="form.slug" :languages="languages" placeholder="e.g. imprint" :invalid="!!fieldError('slug')" />
                                    <p v-if="fieldError('slug')" class="mt-1 text-[11px] text-danger">{{ fieldError('slug') }}</p>
                                </div>
                            </div>
                        </Section>

                        <PageZone region="details" position="after" :page="page" />

                        <Section title="Content">
                            <template #desc>Compose the page from blocks. Drag to reorder, toggle a block to keep it as draft.</template>
                            <template #actions>
                                <Button v-if="canCopyFromDefault" size="sm" @click="copyFromDefault">
                                    Copy from {{ defaultLocale.toUpperCase() }}
                                </Button>
                                <select
                                    v-if="languages.length > 1"
                                    v-model="contentLocale"
                                    class="h-[22px] px-1 border border-line-strong rounded-md bg-surface-2 text-[10.5px] font-mono uppercase tracking-wide text-ink-700 cursor-pointer focus:outline-none focus-visible:ring-2 focus-visible:ring-sage/35"
                                    aria-label="Language"
                                >
                                    <option v-for="language in languages" :key="language.code" :value="language.code" :title="language.name ?? language.code">
                                        {{ language.code.toUpperCase() }}
                                    </option>
                                </select>
                            </template>
                            <BlockEditor :key="contentLocale" v-model="blocks" :block-types="blockTypes"
                                :locale="contentLocale" :errors="form.errors" :media="media"
                                :media-upload-url="urls.media"
                                :media-picker-url="urls.mediaPicker"
                                :media-library-upload-url="urls.mediaLibraryUpload"
                            />
                            <p v-if="form.errors.blocks" class="mt-1 text-[11px] text-danger">{{ form.errors.blocks }}</p>
                        </Section>

                        <PageZone region="content" position="after" :page="page" />

                        <PageZone region="main" position="after" :page="page" />
                    </div>

                    <aside>
                        <div class="lg:sticky lg:top-[60px] flex flex-col gap-4">
                            <PageZone region="sidebar" position="before" :page="page" />

                            <SideCard title="Publication">
                                <div class="grid grid-flow-col auto-cols-fr gap-1 p-1 bg-surface-2 border border-line rounded-lg" role="radiogroup">
                                    <button
                                        v-for="option in statusOptions"
                                        :key="option.value"
                                        type="button"
                                        role="radio"
                                        :aria-checked="option.value === form.status"
                                        :class="[
                                            'h-[28px] px-2 rounded-md text-[12px] font-medium transition-[background-color,color,box-shadow] duration-100',
                                            option.value === form.status
                                                ? option.tone === 'sage'
                                                    ? 'bg-sage-soft border border-sage-border text-sage-ink shadow-sm'
                                                    : 'bg-warn-soft border border-warn-border text-warn-ink shadow-sm'
                                                : 'text-ink-500 hover:text-ink-900 hover:bg-surface/60',
                                        ]"
                                        @click="form.status = option.value"
                                    >{{ option.label }}</button>
                                </div>
                                <div class="text-[11.5px] text-ink-500 mt-2.5">
                                    {{ form.status === 'published' ? 'Visible on the storefront.' : 'Hidden from the storefront. Only visible to staff.' }}
                                </div>
                                <div class="mt-3.5">
                                    <FieldLabel hint="Optional">Publish date</FieldLabel>
                                    <DatePicker v-model="form.published_at" :invalid="!!form.errors.published_at" />
                                    <p v-if="form.errors.published_at" class="mt-1 text-[11px] text-danger">
                                        {{ form.errors.published_at }}
                                    </p>
                                </div>
                            </SideCard>

                            <PageZone region="sidebar" position="after" :page="page" />
                        </div>
                    </aside>
                </div>
            </form>
        </div>

        <ConfirmDialog
            v-model:open="confirmingDelete"
            title="Delete page?"
            confirm-label="Delete"
            tone="danger"
            @confirm="destroy"
        >
            This permanently removes the page and its blocks.
        </ConfirmDialog>
    </div>
</template>
