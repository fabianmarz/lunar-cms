<script setup lang="ts">
import { computed, ref } from 'vue';
import { Link, router, useForm } from '@inertiajs/vue3';
import {
    Button,
    ConfirmDialog,
    DatePicker,
    FieldLabel,
    PageHeader,
    PageZone,
    Select,
    SideCard,
    TextInput,
} from '@lunarphp/panel';
import BlockEditor from '../components/BlockEditor.vue';

interface Block {
    id: string;
    type: string;
    enabled: boolean;
    data: Record<string, unknown>;
}

interface PageForm {
    id: number;
    title: string;
    slug: string;
    status: 'draft' | 'published';
    published_at: string | null;
    blocks: Block[];
}

const props = defineProps<{
    page: PageForm | null;
    media: Record<number, { id: number; url: string; name: string }>;
    urls: { store?: string; update?: string; destroy?: string; media?: string; index: string };
}>();

const isEdit = computed(() => props.page !== null);
const confirmingDelete = ref(false);

const form = useForm({
    title: props.page?.title ?? '',
    slug: props.page?.slug ?? '',
    status: props.page?.status ?? 'draft',
    published_at: props.page?.published_at ?? '',
    blocks: props.page?.blocks ?? [],
});

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

const statusOptions = [
    { value: 'draft', label: 'Draft' },
    { value: 'published', label: 'Published' },
];
</script>

<template>
    <div class="contents">
        <PageHeader :title="page?.title || 'New page'">
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
                <Button type="submit" form="cms-page-form" :disabled="form.processing">
                    {{ isEdit ? 'Save changes' : 'Create page' }}
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
                                <div>
                                    <FieldLabel>Title</FieldLabel>
                                    <TextInput v-model="form.title" :invalid="!!form.errors.title" />
                                    <p v-if="form.errors.title" class="mt-1 text-[11px] text-danger">
                                        {{ form.errors.title }}
                                    </p>
                                </div>
                                <div>
                                    <FieldLabel hint="Leave empty to derive it from the title">Slug</FieldLabel>
                                    <TextInput v-model="form.slug" placeholder="e.g. imprint" :invalid="!!form.errors.slug" />
                                    <p v-if="form.errors.slug" class="mt-1 text-[11px] text-danger">
                                        {{ form.errors.slug }}
                                    </p>
                                </div>
                            </div>
                        </section>

                        <section class="pt-6">
                            <h2 class="text-sm font-semibold tracking-[-0.01em] text-ink-900 mb-1">Content</h2>
                            <p class="text-xs text-ink-500 max-w-[640px] mb-4">
                                Compose the page from blocks. Drag to reorder, toggle a block to keep it as draft.
                            </p>
                            <BlockEditor v-model="form.blocks" :media="media" :media-upload-url="urls.media" />
                            <p v-if="form.errors.blocks" class="mt-1 text-[11px] text-danger">{{ form.errors.blocks }}</p>
                        </section>

                        <PageZone region="main" position="after" />
                    </div>

                    <aside>
                        <div class="lg:sticky lg:top-[60px] flex flex-col gap-4">
                            <PageZone region="sidebar" position="before" />

                            <SideCard title="Publication">
                                <FieldLabel>Status</FieldLabel>
                                <Select v-model="form.status" :options="statusOptions" />
                                <p v-if="form.errors.status" class="mt-1 text-[11px] text-danger">
                                    {{ form.errors.status }}
                                </p>
                                <div class="mt-3.5">
                                    <FieldLabel hint="Optional">Publish date</FieldLabel>
                                    <DatePicker v-model="form.published_at" :invalid="!!form.errors.published_at" />
                                    <p v-if="form.errors.published_at" class="mt-1 text-[11px] text-danger">
                                        {{ form.errors.published_at }}
                                    </p>
                                </div>
                            </SideCard>

                            <PageZone region="sidebar" position="after" />

                            <Link :href="urls.index" class="text-[12.5px] text-ink-500 hover:text-ink-700">
                                ← Back to pages
                            </Link>
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
