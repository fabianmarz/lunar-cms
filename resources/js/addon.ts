import IndexPage from './pages/Index.vue';
import FormPage from './pages/Form.vue';

// Registration must be eager: the panel resolves the initial page on
// DOMContentLoaded, so anything deferred to booting() runs too late.
window.LunarPanel.registerPages({
    'cms::Pages/Index': IndexPage,
    'cms::Pages/Form': FormPage,
});
