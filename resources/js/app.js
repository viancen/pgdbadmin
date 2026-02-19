import './bootstrap';
import { createApp, h } from 'vue';
import { createInertiaApp } from '@inertiajs/vue3';

const pages = import.meta.glob('./Pages/**/*.vue');

createInertiaApp({
    title: (title) => (title ? `PGDB:: ${title}` : 'PGDB'),
    resolve: (name) => {
        const page = pages[`./Pages/${name}.vue`];
        if (!page) throw new Error(`Page not found: ${name}`);
        return page();
    },
    setup({ el, App, props, plugin }) {
        const app = createApp({ render: () => h(App, props) });
        app.use(plugin);
        // Ziggy route() is on window from @routes; expose it for Vue templates
        app.config.globalProperties.route = typeof window !== 'undefined' && typeof window.route === 'function'
            ? window.route
            : () => '#';
        app.mount(el);
    },
});
