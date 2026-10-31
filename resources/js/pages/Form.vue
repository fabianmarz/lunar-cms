<script setup lang="ts">
import { computed, ref } from 'vue';
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
import LanguageSelect from '../components/LanguageSelect.vue';
import { withTranslation, type LanguageOption, type Translations } from '../translations';

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
    blocks: Block[];
}

const props = defineProps<{
    page: PageForm | null;
    media: Record<number, { id: number; url: string; original_url?: string; name: string }>;
    languages: LanguageOption[];
    urls: { store?: string; update?: string; destroy?: string; media?: string; index: string };
}>();

const isEdit = computed(() => props.page !== null);
const confirmingDelete = ref(false);

const form = useForm({
    title: props.page?.title ?? {},
    slug: props.page?.slug ?? {},
    status: props.page?.status ?? 'draft',
    published_at: props.page?.published_at ?? '',
    blocks: props.page?.blocks ?? [],
});

// One language is edited at a time; every translatable field in the form,
// blocks included, reads and writes the selected language's value.
const locale = ref(props.languages.find((language) => language.default)?.code ?? props.languages[0]?.code ?? 'en');
const defaultLocale = computed(() => props.languages.find((language) => language.default)?.code ?? locale.value);

const title = computed({
    get: () => form.title[locale.value] ?? '',
    set: (value: string) => (form.title = withTranslation(form.title, locale.value, value)),
});

const slug = computed({
    get: () => form.slug[locale.value] ?? '',
    set: (value: string) => (form.slug = withTranslation(form.slug, locale.value, value)),
});

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
            <PageZone region="main" position="before" />

            <form id="cms-page-form" @submit.prevent="submit">
                <div class="flex flex-col gap-8 lg:grid lg:grid-cols-[minmax(0,1fr)_320px]">
                    <div class="min-w-0">
                        <section class="pb-6 border-b border-line">
                            <h2 class="text-sm font-semibold tracking-[-0.01em] text-ink-900 mb-1">Details</h2>
                            <p class="text-xs text-ink-500 max-w-[640px] mb-4">
                                The page title and the slug it will be reachable at.
                            </p>
                            <div class="space-y-4">
                                <div v-if="languages.length > 1" class="flex items-center gap-2">
                                    <FieldLabel>Editing language</FieldLabel>
                                    <LanguageSelect v-model="locale" :languages="languages" />
                                </div>
                                <div>
                                    <FieldLabel>Title</FieldLabel>
                                    <TextInput v-model="title" :invalid="!!form.errors[`title.${locale}`] || !!form.errors.title" />
                                    <p v-if="form.errors[`title.${locale}`] || form.errors.title" class="mt-1 text-[11px] text-danger">
                                        {{ form.errors[`title.${locale}`] || form.errors.title }}
                                    </p>
                                </div>
                                <div>
                                    <FieldLabel hint="Leave empty to derive it from the title">Slug</FieldLabel>
                                    <TextInput v-model="slug" placeholder="e.g. imprint" :invalid="!!form.errors[`slug.${locale}`]" />
                                    <p v-if="form.errors[`slug.${locale}`]" class="mt-1 text-[11px] text-danger">
                                        {{ form.errors[`slug.${locale}`] }}
                                    </p>
                                </div>
                            </div>
                        </section>

                        <section class="pt-6">
                            <h2 class="text-sm font-semibold tracking-[-0.01em] text-ink-900 mb-1">Content</h2>
                            <p class="text-xs text-ink-500 max-w-[640px] mb-4">
                                Compose the page from blocks. Drag to reorder, toggle a block to keep it as draft.
                            </p>
                            <BlockEditor v-model="form.blocks" :media="media" :media-upload-url="urls.media" :locale="locale" />
                            <p v-if="form.errors.blocks" class="mt-1 text-[11px] text-danger">{{ form.errors.blocks }}</p>
                        </section>

                        <PageZone region="main" position="after" />
                    </div>

                    <aside>
                        <div class="lg:sticky lg:top-[60px] flex flex-col gap-4">
                            <PageZone region="sidebar" position="before" />

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

                            <PageZone region="sidebar" position="after" />
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
