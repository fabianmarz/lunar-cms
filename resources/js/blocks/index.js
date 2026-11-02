import ImageBlock from '../components/blocks/ImageBlock.vue';

// Custom block editors, keyed by the `editor` name in LunarCms\Blocks\BlockRegistry.
// Schema blocks (no editor) render through components/BlockFields.vue from the
// registry's field declarations — adding one needs no JS at all.
export const customEditors = {
    image: ImageBlock,
};
