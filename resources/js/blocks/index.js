import RichTextBlock from '../components/blocks/RichTextBlock.vue';
import ImageBlock from '../components/blocks/ImageBlock.vue';
import CtaBlock from '../components/blocks/CtaBlock.vue';

// Mirrors LunarCms\Blocks\BlockRegistry — a type added server-side needs an
// entry here or the editor renders an "unsupported block" notice.
export const blockTypes = {
    rich_text: { label: 'Rich text', icon: 'type', component: RichTextBlock, defaults: () => ({ content: '' }) },
    image: { label: 'Image', icon: 'image', component: ImageBlock, defaults: () => ({ media_id: null, alt: '' }) },
    cta: { label: 'Button', icon: 'link', component: CtaBlock, defaults: () => ({ label: '', url: '' }) },
};
