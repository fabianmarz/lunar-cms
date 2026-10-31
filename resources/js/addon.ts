import IndexPage from './pages/Index.vue';
import FormPage from './pages/Form.vue';

// The add-on shares the panel's compiled stylesheet: a utility class only
// exists if the panel's own build emitted it. These rules cover the custom
// sizes/spacings/states we use that the panel doesn't ship — scoped to our
// markup via the cms-* wrapper and arbitrary-value class names we invented.
const styles = `
/* text sizes the panel doesn't emit */
.text-\\[13px\\]{font-size:13px}.text-\\[12\\.5px\\]{font-size:12.5px}
.text-\\[12px\\]{font-size:12px}.text-\\[11\\.5px\\]{font-size:11.5px}
.text-\\[11px\\]{font-size:11px}.text-\\[10px\\]{font-size:10px}
/* spacing/size utilities not in the panel build */
.px-2\\.5{padding-inline:.625rem}.py-1\\.5{padding-block:.375rem}
.py-2\\.5{padding-block:.625rem}.mt-2\\.5{margin-top:.625rem}.mt-3\\.5{margin-top:.875rem}
.min-w-40{min-width:10rem}.w-64{width:16rem}.w-16{width:4rem}.h-16{height:4rem}
.space-y-2>:not([hidden])~:not([hidden]){margin-top:.5rem}
.space-y-4>:not([hidden])~:not([hidden]){margin-top:1rem}
.h-\\[28px\\]{height:28px}.tracking-\\[-0\\.01em\\]{letter-spacing:-.01em}
.max-w-\\[1400px\\]{max-width:1400px}.max-w-\\[640px\\]{max-width:640px}
/* state variants on our elements */
.hover\\:text-ink-900:hover{color:var(--color-ink-900)}
.hover\\:text-ink-700:hover{color:var(--color-ink-700)}
.hover\\:text-danger:hover{color:var(--color-danger)}
.hover\\:border-ink-300:hover{border-color:var(--color-ink-300)}
.hover\\:bg-surface-2:hover{background:var(--color-surface-2)}
.hover\\:bg-surface\\/60:hover{background:color-mix(in srgb,var(--color-surface) 60%,transparent)}
.focus-visible\\:opacity-100:focus-visible,.hover\\:opacity-100:hover{opacity:1}
.active\\:cursor-grabbing:active{cursor:grabbing}
@media(min-width:640px){.sm\\:px-5{padding-inline:1.25rem}}
@media(min-width:1024px){.lg\\:top-\\[60px\\]{top:60px}.lg\\:grid{display:grid}
.lg\\:grid-cols-\\[minmax\\(0\\,1fr\\)_320px\\]{grid-template-columns:minmax(0,1fr) 320px}
.lg\\:px-7{padding-inline:1.75rem}.lg\\:sticky{position:sticky}}
/* component pieces */
.cms-block-add{position:absolute;bottom:-10px;left:50%;transform:translateX(-50%);z-index:10;opacity:0;transition:opacity .1s}
.cms-block:hover .cms-block-add,.cms-block-add:focus-visible{opacity:1}
.cms-menu{min-width:10rem}
.cms-block .tiptap{min-height:6rem;outline:none}
.cms-block .tiptap p{margin:0}
`;

const styleEl = document.createElement('style');
styleEl.textContent = styles;
document.head.appendChild(styleEl);

// Registration must be eager: the panel resolves the initial page on
// DOMContentLoaded, so anything deferred to booting() runs too late.
window.LunarPanel.registerPages({
    'cms::Pages/Index': IndexPage,
    'cms::Pages/Form': FormPage,
});
